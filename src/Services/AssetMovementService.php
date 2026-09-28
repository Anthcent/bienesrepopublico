<?php

namespace App\Services;

use App\Commands\DecommissionCommand;
use App\Commands\ReassignCommand;
use App\Core\Database;
use App\Repositories\AssetRepository;
use App\Repositories\MovementRepository;

/**
 * Reglas del bien (PLAN_MAESTRO_V4 §12):
 * - Solo ACTIVO puede prestarse; solo DISPONIBLE puede prestarse.
 * - PRESTADO no puede desincorporarse.
 * - DESINCORPORADO no puede prestarse.
 * - Readmisión solo desde DESINCORPORADO.
 * - La reasignación mantiene historial (no sobreescribe, versiona vía movimientos).
 */
final class AssetMovementService
{
    private AssetRepository $assets;
    private MovementRepository $movements;
    private AuditService $audit;
    private DocumentService $documents;

    public function __construct()
    {
        $this->assets = new AssetRepository();
        $this->movements = new MovementRepository();
        $this->audit = new AuditService();
        $this->documents = new DocumentService();
    }

    public function reassign(int $assetId, ReassignCommand $command, array $actor): ServiceResult
    {
        $asset = $this->assets->find($assetId);
        if (!$asset) {
            return ServiceResult::failure('Bien no encontrado.');
        }
        if ($asset['estado_administrativo'] !== 'ACTIVO') {
            return ServiceResult::failure('Solo un bien activo puede reasignarse.');
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $before = [
                'location_id' => $asset['location_id'],
                'location_name' => $asset['ubicacion_nombre'],
                'responsible_id' => $asset['responsible_id'],
                'responsible_name' => $asset['responsable_nombre'],
            ];

            $this->assets->updateAssignment($assetId, [
                'location_id' => $command->locationId,
                'responsible_id' => $command->responsibleId,
            ]);

            $movementId = $this->movements->create([
                'asset_id' => $assetId,
                'movement_type_id' => $this->movements->typeIdByCode('REASIGNACION'),
                'ubicacion_anterior_id' => $before['location_id'],
                'ubicacion_nueva_id' => $command->locationId,
                'responsable_anterior_id' => $before['responsible_id'],
                'responsable_nuevo_id' => $command->responsibleId,
                'motivo' => $command->motivo,
                'usuario_id' => $actor['id'],
            ]);

            $updated = $this->assets->find($assetId);

            $after = [
                'location_id' => $command->locationId,
                'location_name' => $updated['ubicacion_nombre'],
                'responsible_id' => $command->responsibleId,
                'responsible_name' => $updated['responsable_nombre'],
            ];
            if ($command->motivo !== null && $command->motivo !== '') {
                $after['motivo'] = $command->motivo;
            }
            $this->audit->record(
                $actor['id'], 'asset.reassign', 'asset', $assetId,
                sprintf('%s reasignó %s a %s / %s.', $actor['nombre'], $asset['numero_bien'], $updated['ubicacion_nombre'], $updated['responsable_nombre']),
                $before,
                $after
            );

            $movement = ['id' => $movementId];
            $this->documents->generateMovement($updated, $movement, $actor, 'reasignacion');

            $db->commit();
            return ServiceResult::success(['asset' => $updated], 'Bien reasignado correctamente.');
        } catch (\Throwable $e) {
            $db->rollBack();
            return ServiceResult::failure('No fue posible reasignar el bien.');
        }
    }

    public function decommission(int $assetId, DecommissionCommand $command, array $actor): ServiceResult
    {
        $asset = $this->assets->find($assetId);
        if (!$asset) {
            return ServiceResult::failure('Bien no encontrado.');
        }
        if ($asset['estado_administrativo'] !== 'ACTIVO') {
            return ServiceResult::failure('El bien ya está desincorporado.');
        }
        if ($asset['disponibilidad'] === 'PRESTADO') {
            return ServiceResult::failure('No es posible desincorporar un bien que está prestado. Regístrese primero su devolución.');
        }
        if ($command->motivo === '') {
            return ServiceResult::failure('El motivo de desincorporación es obligatorio.', ['motivo' => 'Campo requerido.']);
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $this->assets->updateAdministrativeState($assetId, 'DESINCORPORADO');

            $movementId = $this->movements->create([
                'asset_id' => $assetId,
                'movement_type_id' => $this->movements->typeIdByCode('DESINCORPORACION'),
                'motivo' => $command->motivo,
                'observaciones' => $command->observaciones,
                'usuario_id' => $actor['id'],
            ]);

            $updated = $this->assets->find($assetId);

            $after = ['estado_administrativo' => 'DESINCORPORADO', 'motivo' => $command->motivo];
            if ($command->observaciones !== null && $command->observaciones !== '') {
                $after['observaciones'] = $command->observaciones;
            }
            $this->audit->record(
                $actor['id'], 'asset.decommission', 'asset', $assetId,
                sprintf('%s desincorporó %s. Motivo: %s.', $actor['nombre'], $asset['numero_bien'], $command->motivo),
                ['estado_administrativo' => 'ACTIVO'],
                $after
            );

            $this->documents->generateMovement($updated, ['id' => $movementId], $actor, 'desincorporacion');

            $db->commit();
            return ServiceResult::success(['asset' => $updated], 'Bien desincorporado correctamente.');
        } catch (\Throwable $e) {
            $db->rollBack();
            return ServiceResult::failure('No fue posible desincorporar el bien.');
        }
    }

    public function readmit(int $assetId, array $actor, ?string $motivo = null): ServiceResult
    {
        $asset = $this->assets->find($assetId);
        if (!$asset) {
            return ServiceResult::failure('Bien no encontrado.');
        }
        if ($asset['estado_administrativo'] !== 'DESINCORPORADO') {
            return ServiceResult::failure('Solo puede readmitirse un bien desincorporado.');
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $this->assets->updateAdministrativeState($assetId, 'ACTIVO');

            $movementId = $this->movements->create([
                'asset_id' => $assetId,
                'movement_type_id' => $this->movements->typeIdByCode('READMISION'),
                'motivo' => $motivo ?: 'Readmisión de bien',
                'usuario_id' => $actor['id'],
            ]);

            $updated = $this->assets->find($assetId);

            $this->audit->record(
                $actor['id'], 'asset.readmit', 'asset', $assetId,
                sprintf('%s readmitió %s.', $actor['nombre'], $asset['numero_bien']),
                ['estado_administrativo' => 'DESINCORPORADO'],
                ['estado_administrativo' => 'ACTIVO', 'motivo' => $motivo ?: 'Readmisión de bien']
            );

            $this->documents->generateMovement($updated, ['id' => $movementId], $actor, 'readmision');

            $db->commit();
            return ServiceResult::success(['asset' => $updated], 'Bien readmitido correctamente.');
        } catch (\Throwable $e) {
            $db->rollBack();
            return ServiceResult::failure('No fue posible readmitir el bien.');
        }
    }

    public function history(int $assetId): array
    {
        return $this->movements->forAsset($assetId);
    }

    public function recent(int $limit = 8): array
    {
        return $this->movements->recent($limit);
    }
}
