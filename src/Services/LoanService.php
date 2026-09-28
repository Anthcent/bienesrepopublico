<?php

namespace App\Services;

use App\Commands\CreateLoanCommand;
use App\Core\Database;
use App\Repositories\AssetRepository;
use App\Repositories\CatalogRepository;
use App\Repositories\LoanRepository;
use App\Repositories\MovementRepository;

final class LoanService
{
    private LoanRepository $loans;
    private AssetRepository $assets;
    private CatalogRepository $catalogs;
    private MovementRepository $movements;
    private AuditService $audit;
    private NotificationService $notifications;
    private DocumentService $documents;
    private LoanSettingsService $settings;

    public function __construct()
    {
        $this->loans = new LoanRepository();
        $this->assets = new AssetRepository();
        $this->catalogs = new CatalogRepository();
        $this->movements = new MovementRepository();
        $this->audit = new AuditService();
        $this->notifications = new NotificationService();
        $this->documents = new DocumentService();
        $this->settings = new LoanSettingsService();
    }

    public function list(array $filters, int $page, int $perPage): array
    {
        return $this->loans->paginate($filters, $page, $perPage);
    }

    public function find(int $id): ?array
    {
        $loan = $this->loans->find($id);
        if (!$loan) {
            return null;
        }
        $loan['detalles'] = $this->loans->details($id);
        return $loan;
    }

    public function counters(): array
    {
        return $this->loans->counters($this->settings->current()['due_alert_days']);
    }

    public function countOverdue(): int
    {
        return $this->loans->countOverdue();
    }

    public function dueSoon(int $days): array
    {
        return $this->loans->dueSoon($days);
    }

    /**
     * Préstamo individual o múltiple, atómico: valida disponibilidad de
     * TODOS los bienes antes de comprometer nada; si uno falla, no se
     * crea préstamo alguno.
     */
    public function create(CreateLoanCommand $command, array $actor): ServiceResult
    {
        if (empty($command->assetIds)) {
            return ServiceResult::failure('Selecciona al menos un bien para prestar.');
        }
        if (!$command->responsibleId) {
            return ServiceResult::failure('Selecciona el prestatario.', ['responsible_id' => 'Campo requerido.']);
        }
        $responsible = $this->catalogs->find('responsibles', $command->responsibleId);
        if (!$responsible) {
            return ServiceResult::failure('El prestatario seleccionado no es válido.', ['responsible_id' => 'Selecciona un prestatario registrado.']);
        }
        $startDate = $this->parseDate($command->fechaPrestamo);
        if (!$startDate) {
            return ServiceResult::failure('La fecha de entrega no es válida.', ['fecha_prestamo' => 'Ingresa una fecha válida.']);
        }
        $dueDate = trim($command->fechaVencimiento) === ''
            ? $startDate->modify('+' . $this->settings->current()['default_loan_days'] . ' days')
            : $this->parseDate($command->fechaVencimiento);
        if (!$dueDate) {
            return ServiceResult::failure('La fecha de devolución prevista no es válida.', ['fecha_vencimiento' => 'Ingresa una fecha válida.']);
        }
        if ($dueDate < $startDate) {
            return ServiceResult::failure('La fecha de devolución prevista no puede ser anterior a la fecha de entrega.');
        }

        $assetsToLoan = [];
        foreach ($command->assetIds as $assetId) {
            $asset = $this->assets->find($assetId);
            if (!$asset) {
                return ServiceResult::failure("El bien #{$assetId} no existe.");
            }
            if ($asset['estado_administrativo'] !== 'ACTIVO' || $asset['disponibilidad'] !== 'DISPONIBLE') {
                return ServiceResult::failure("El bien {$asset['numero_bien']} no está disponible para préstamo.");
            }
            $assetsToLoan[] = $asset;
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $loanId = $this->loans->create([
                'codigo' => $this->loans->nextCode(),
                'responsible_id' => $command->responsibleId,
                'prestatario_nombre' => $responsible['nombre'],
                'prestatario_cargo' => $responsible['cargo'],
                'prestatario_dependencia' => $responsible['dependencia'],
                'fecha_prestamo' => $startDate->format('Y-m-d'),
                'fecha_vencimiento' => $dueDate->format('Y-m-d'),
                'motivo' => $command->motivo,
                'observaciones' => $command->observaciones,
                'usuario_creador_id' => $actor['id'],
            ]);

            $movementTypeId = $this->movements->typeIdByCode('PRESTAMO');

            foreach ($assetsToLoan as $asset) {
                $this->loans->addDetail($loanId, $asset['id'], $asset['physical_state_id']);
                $this->assets->updateAvailability($asset['id'], 'PRESTADO');
                $this->movements->create([
                    'asset_id' => $asset['id'],
                    'movement_type_id' => $movementTypeId,
                    'motivo' => 'Salida por préstamo ' . $this->loans->find($loanId)['codigo'],
                    'usuario_id' => $actor['id'],
                ]);
            }

            $loan = $this->loans->find($loanId);

            $this->audit->record(
                $actor['id'], 'loan.create', 'loan', $loanId,
                sprintf('%s registró el préstamo %s a %s (%d bien(es)).', $actor['nombre'], $loan['codigo'], $loan['prestatario_nombre_snapshot'], count($assetsToLoan))
            );

            $this->documents->generateLoan($loan, $this->loans->details($loanId), $actor);

            $db->commit();
            return ServiceResult::success(['loan' => $loan], 'Préstamo registrado correctamente.');
        } catch (\Throwable $e) {
            $db->rollBack();
            return ServiceResult::failure('No fue posible registrar el préstamo.');
        }
    }

    /**
     * Devolución total o parcial. `returns` es un arreglo de:
     *   ['detail_id' => int, 'estado_devolucion_id' => int, 'observacion' => ?string, 'imagen_url' => ?string]
     */
    public function processReturn(int $loanId, array $returns, array $actor): ServiceResult
    {
        $loan = $this->loans->find($loanId);
        if (!$loan) {
            return ServiceResult::failure('Préstamo no encontrado.');
        }
        if (!in_array($loan['estado'], ['ACTIVO', 'PARCIALMENTE_DEVUELTO', 'VENCIDO'], true)) {
            return ServiceResult::failure('Este préstamo no admite devoluciones.');
        }
        if (empty($returns)) {
            return ServiceResult::failure('Selecciona al menos un bien a devolver.');
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $damagedAssets = [];
            $physicalStates = $this->catalogs->all('physical_states', false);
            $ordenPorId = array_column($physicalStates, 'orden', 'id');
            $requireObservation = $this->settings->current()['require_return_observation'];

            foreach ($returns as $return) {
                $detail = $this->loans->findDetail((int) $return['detail_id']);
                if (!$detail || (int) $detail['loan_id'] !== $loanId) {
                    throw new \RuntimeException('Detalle de préstamo inválido.');
                }
                if ($detail['fecha_devolucion']) {
                    continue; // ya devuelto
                }

                $asset = $this->assets->find((int) $detail['asset_id']);
                $newStateId = (int) $return['estado_devolucion_id'];
                $oldStateId = (int) $detail['estado_salida_id'];

                if (!isset($ordenPorId[$newStateId])) {
                    throw new \RuntimeException('Selecciona un estado físico de devolución válido.');
                }

                $empeoro = ($ordenPorId[$newStateId] ?? 0) > ($ordenPorId[$oldStateId] ?? 0);

                if ($requireObservation && $empeoro && trim((string) ($return['observacion'] ?? '')) === '') {
                    throw new \RuntimeException("Debes registrar una observación: el estado físico de {$asset['numero_bien']} empeoró.");
                }

                $this->loans->returnDetail($detail['id'], [
                    'fecha' => date('Y-m-d'),
                    'estado_devolucion_id' => $newStateId,
                    'observacion' => $return['observacion'] ?? null,
                    'imagen_url' => $return['imagen_url'] ?? null,
                    'usuario_id' => $actor['id'],
                ]);

                $this->assets->updateAvailability((int) $detail['asset_id'], 'DISPONIBLE');
                $this->assets->updatePhysicalState((int) $detail['asset_id'], $newStateId);

                $this->movements->create([
                    'asset_id' => (int) $detail['asset_id'],
                    'movement_type_id' => $this->movements->typeIdByCode('DEVOLUCION'),
                    'estado_fisico_anterior_id' => $oldStateId,
                    'estado_fisico_nuevo_id' => $newStateId,
                    'observaciones' => $return['observacion'] ?? null,
                    'motivo' => 'Devolución de préstamo ' . $loan['codigo'],
                    'usuario_id' => $actor['id'],
                ]);

                if ($empeoro) {
                    $damagedAssets[] = $asset['numero_bien'];
                }
            }

            $allDetails = $this->loans->details($loanId);
            $pending = array_filter($allDetails, fn($d) => $d['fecha_devolucion'] === null);
            $newEstado = empty($pending) ? 'DEVUELTO' : 'PARCIALMENTE_DEVUELTO';
            $fechaCierre = empty($pending) ? date('Y-m-d') : null;
            $this->loans->updateEstado($loanId, $newEstado, $fechaCierre);

            if ($newEstado === 'DEVUELTO') {
                $this->notifications->resolveForLoan($loanId);
            }

            $updatedLoan = $this->loans->find($loanId);

            $this->audit->record(
                $actor['id'], 'loan.return', 'loan', $loanId,
                sprintf('%s registró devolución en %s (%s).', $actor['nombre'], $loan['codigo'], $newEstado === 'DEVUELTO' ? 'total' : 'parcial')
            );

            $this->documents->generateReturn($updatedLoan, $this->loans->details($loanId), $actor);

            if ($damagedAssets) {
                $this->notifications->notifyReturnDamage(
                    (new \App\Repositories\NotificationRepository())->allActiveUserIds(),
                    $loanId,
                    $loan['codigo'],
                    implode(', ', $damagedAssets)
                );
            }

            $db->commit();
            return ServiceResult::success(['loan' => $updatedLoan], 'Devolución registrada correctamente.');
        } catch (\Throwable $e) {
            $db->rollBack();
            return ServiceResult::failure($e->getMessage() ?: 'No fue posible registrar la devolución.');
        }
    }

    public function extend(int $loanId, string $newDate, ?string $motivo, array $actor): ServiceResult
    {
        $loan = $this->loans->find($loanId);
        if (!$loan) {
            return ServiceResult::failure('Préstamo no encontrado.');
        }
        if (!in_array($loan['estado'], ['ACTIVO', 'PARCIALMENTE_DEVUELTO', 'VENCIDO'], true)) {
            return ServiceResult::failure('Este préstamo no puede extenderse.');
        }
        if (strtotime($newDate) <= strtotime($loan['fecha_vencimiento'])) {
            return ServiceResult::failure('La nueva fecha debe ser posterior a la fecha de vencimiento actual.');
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $this->loans->addExtension([
                'loan_id' => $loanId,
                'fecha_anterior' => $loan['fecha_vencimiento'],
                'fecha_nueva' => $newDate,
                'motivo' => $motivo,
                'usuario_id' => $actor['id'],
            ]);
            $this->loans->updateVencimiento($loanId, $newDate);
            if ($loan['estado'] === 'VENCIDO') {
                $this->loans->updateEstado($loanId, 'ACTIVO');
            }
            $this->notifications->resolveForLoan($loanId);

            $this->audit->record(
                $actor['id'], 'loan.extend', 'loan', $loanId,
                sprintf('%s extendió %s del %s al %s.', $actor['nombre'], $loan['codigo'], $loan['fecha_vencimiento'], $newDate)
            );

            $db->commit();
            return ServiceResult::success(['loan' => $this->loans->find($loanId)], 'Préstamo extendido correctamente.');
        } catch (\Throwable $e) {
            $db->rollBack();
            return ServiceResult::failure('No fue posible extender el préstamo.');
        }
    }

    public function cancel(int $loanId, array $actor): ServiceResult
    {
        $loan = $this->loans->find($loanId);
        if (!$loan) {
            return ServiceResult::failure('Préstamo no encontrado.');
        }
        if (!in_array($loan['estado'], ['ACTIVO', 'PARCIALMENTE_DEVUELTO'], true)) {
            return ServiceResult::failure('Solo se pueden anular préstamos activos.');
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            foreach ($this->loans->details($loanId) as $detail) {
                if (!$detail['fecha_devolucion']) {
                    $this->assets->updateAvailability((int) $detail['asset_id'], 'DISPONIBLE');
                }
            }
            $this->loans->updateEstado($loanId, 'ANULADO', date('Y-m-d'));
            $this->notifications->resolveForLoan($loanId);

            $this->audit->record($actor['id'], 'loan.cancel', 'loan', $loanId, sprintf('%s anuló el préstamo %s.', $actor['nombre'], $loan['codigo']));

            $db->commit();
            return ServiceResult::success(['loan' => $this->loans->find($loanId)], 'Préstamo anulado.');
        } catch (\Throwable $e) {
            $db->rollBack();
            return ServiceResult::failure('No fue posible anular el préstamo.');
        }
    }

    /** Recalcula préstamos vencidos (llamado desde NotificationService/sync points). */
    public function markOverdue(): void
    {
        foreach ($this->loans->allOpenPastDue() as $loan) {
            if ($loan['estado'] !== 'VENCIDO') {
                $this->loans->updateEstado((int) $loan['id'], 'VENCIDO');
            }
        }
    }

    private function parseDate(string $value): ?\DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = \DateTimeImmutable::getLastErrors();
        return $date && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            ? $date
            : null;
    }
}
