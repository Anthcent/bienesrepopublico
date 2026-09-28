<?php

namespace App\Services;

use App\Commands\ReassignCommand;
use App\Core\Database;
use App\Repositories\AssetRepository;
use App\Repositories\MovementRepository;
use App\Repositories\VerificationRepository;

/**
 * Jornada de Verificación Patrimonial (Plan Maestro V5 §17-30).
 *
 * Decisión de diseño clave: cuando la captura corrige ubicación o
 * responsable, este servicio NO escribe `assets` directamente — reutiliza
 * AssetMovementService::reassign(), la misma ruta ya probada que usa la
 * reasignación manual (con sus validaciones y su propio movimiento +
 * documento + auditoría). Eso evita duplicar la lógica de transición de
 * estado del bien, tal como señala AUDITORIA_ESTADO_ACTUAL.md.
 *
 * Como reassign() ya administra su propia transacción completa, no puede
 * anidarse dentro de otra transacción PDO. Por eso la captura con cambios
 * se compone de pasos independientes y atómicos (reasignación, corrección
 * de estado físico/serial, marca del item) en vez de una única transacción
 * gigante — cada paso es seguro por sí mismo y queda auditado.
 */
final class VerificationService
{
    private VerificationRepository $repo;
    private AssetRepository $assets;
    private MovementRepository $movements;
    private AuditService $audit;
    private DocumentService $documents;
    private AssetMovementService $assetMovements;

    public function __construct()
    {
        $this->repo = new VerificationRepository();
        $this->assets = new AssetRepository();
        $this->movements = new MovementRepository();
        $this->audit = new AuditService();
        $this->documents = new DocumentService();
        $this->assetMovements = new AssetMovementService();
    }

    public function candidateAssets(array $filters): array
    {
        return $this->assets->paginate($filters, 1, 5000)['items'];
    }

    public function createCampaign(array $input, array $actor): ServiceResult
    {
        $titulo = trim((string) ($input['titulo'] ?? ''));
        $assetIds = array_values(array_unique(array_map('intval', $input['asset_ids'] ?? [])));
        $campos = array_values(array_intersect(
            $input['campos'] ?? [],
            ['ubicacion', 'responsable', 'estado_fisico', 'serial', 'identificacion', 'fotografia', 'observaciones']
        ));

        if ($titulo === '') {
            return ServiceResult::failure('El título de la jornada es obligatorio.', ['titulo' => 'Campo requerido.']);
        }
        if (empty($assetIds)) {
            return ServiceResult::failure('Selecciona al menos un bien para la jornada.');
        }
        if (empty($campos)) {
            return ServiceResult::failure('Selecciona al menos un campo a verificar.');
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $codigo = $this->repo->nextCode();
            $campaignId = $this->repo->createCampaign([
                'codigo' => $codigo,
                'titulo' => $titulo,
                'descripcion' => $input['descripcion'] ?? null,
                'fecha_programada' => $input['fecha_programada'] ?? null ?: null,
                'ubicacion_id' => $input['ubicacion_id'] ?? null ?: null,
                'responsible_id' => $input['responsible_id'] ?? null ?: null,
                'campos' => $campos,
                'creado_por_usuario_id' => $actor['id'],
                'estado' => 'GENERADA',
                'cantidad_bienes' => count($assetIds),
            ]);

            $itemsForDoc = [];
            $requiereFoto = in_array('fotografia', $campos, true);
            foreach ($assetIds as $assetId) {
                $asset = $this->assets->find($assetId);
                if (!$asset) {
                    continue;
                }
                $this->repo->insertItem([
                    'campaign_id' => $campaignId,
                    'asset_id' => $assetId,
                    'numero_bien_snapshot' => $asset['numero_bien'],
                    'descripcion_snapshot' => $asset['descripcion'],
                    'serial_snapshot' => $asset['serial'],
                    'location_id_snapshot' => $asset['location_id'],
                    'location_nombre_snapshot' => $asset['ubicacion_nombre'],
                    'responsible_id_snapshot' => $asset['responsible_id'],
                    'responsible_nombre_snapshot' => $asset['responsable_nombre'],
                    'physical_state_id_snapshot' => $asset['physical_state_id'],
                    'physical_state_nombre_snapshot' => $asset['estado_fisico_nombre'],
                    'requiere_fotografia' => $requiereFoto,
                ]);
                $itemsForDoc[] = [
                    'numero_bien_snapshot' => $asset['numero_bien'],
                    'descripcion_snapshot' => $asset['descripcion'],
                    'serial_snapshot' => $asset['serial'],
                    'location_nombre_snapshot' => $asset['ubicacion_nombre'],
                    'responsible_nombre_snapshot' => $asset['responsable_nombre'],
                    'physical_state_nombre_snapshot' => $asset['estado_fisico_nombre'],
                ];
            }

            $campaign = $this->repo->findCampaign($campaignId);

            $this->audit->record(
                $actor['id'], 'verification.create', 'verification_campaign', $campaignId,
                sprintf('%s creó la jornada %s con %d bien(es).', $actor['nombre'], $codigo, count($itemsForDoc))
            );

            $this->documents->generateVerificationSheet($campaign, $itemsForDoc, $campos, $actor);

            $db->commit();
            return ServiceResult::success(['campaign' => $campaign], 'Jornada creada correctamente.');
        } catch (\Throwable $e) {
            $db->rollBack();
            return ServiceResult::failure('No fue posible crear la jornada de verificación.');
        }
    }

    public function find(int $id): ?array
    {
        return $this->repo->findCampaign($id);
    }

    public function counters(int $id): array
    {
        return $this->repo->counters($id);
    }

    public function items(int $id): array
    {
        return $this->repo->itemsForCampaign($id);
    }

    public function list(array $filters, int $page, int $perPage): array
    {
        return $this->repo->paginateCampaigns($filters, $page, $perPage);
    }

    public function globalCounters(): array
    {
        return $this->repo->globalCounters();
    }

    public function activeCampaigns(int $limit = 5): array
    {
        return $this->repo->activeCampaigns($limit);
    }

    /** Bien pendiente que corresponde mostrar en la pantalla de captura secuencial. */
    public function nextPendingItem(int $campaignId): ?array
    {
        return $this->repo->nextPendingItem($campaignId);
    }

    public function findItem(int $itemId): ?array
    {
        return $this->repo->findItem($itemId);
    }

    public function captureNoChanges(int $itemId, array $actor): ServiceResult
    {
        $item = $this->repo->findItem($itemId);
        if (!$item) {
            return ServiceResult::failure('Bien de la jornada no encontrado.');
        }
        $campaignCheck = $this->assertCampaignCapturable((int) $item['campaign_id']);
        if (!$campaignCheck->ok) {
            return $campaignCheck;
        }

        $this->repo->markVerified($itemId, [
            'sin_cambios' => true,
            'usuario_id' => $actor['id'],
            'estado_item' => 'VERIFICADO_SIN_CAMBIOS',
        ]);
        $this->markCampaignInCapture((int) $item['campaign_id']);

        $this->audit->record(
            $actor['id'], 'verification.capture', 'verification_campaign_item', $itemId,
            sprintf('%s confirmó sin cambios %s en la jornada.', $actor['nombre'], $item['numero_bien_snapshot'])
        );

        return ServiceResult::success(['next' => $this->repo->nextPendingItem((int) $item['campaign_id'])], 'Bien confirmado sin cambios.');
    }

    public function captureNotFound(int $itemId, array $actor, ?string $observacion): ServiceResult
    {
        $item = $this->repo->findItem($itemId);
        if (!$item) {
            return ServiceResult::failure('Bien de la jornada no encontrado.');
        }
        $campaignCheck = $this->assertCampaignCapturable((int) $item['campaign_id']);
        if (!$campaignCheck->ok) {
            return $campaignCheck;
        }

        $this->repo->markNotFound($itemId, $actor['id'], $observacion);
        $this->markCampaignInCapture((int) $item['campaign_id']);

        $this->audit->record(
            $actor['id'], 'verification.capture', 'verification_campaign_item', $itemId,
            sprintf('%s marcó %s como no encontrado durante la jornada.', $actor['nombre'], $item['numero_bien_snapshot'])
        );

        return ServiceResult::success(['next' => $this->repo->nextPendingItem((int) $item['campaign_id'])], 'Bien marcado como no encontrado. Requiere revisión.');
    }

    /**
     * $payload: {
     *   changes: {ubicacion?, responsable?, estado_fisico?, serial?, fotografia?} (bool),
     *   ubicacion_observada_id?, responsable_observado_id?, estado_fisico_observado_id?, serial_observado?,
     *   observacion_captura?, force? (bool, para aplicar pese a conflicto)
     * }
     */
    public function captureChanges(int $itemId, array $payload, array $actor): ServiceResult
    {
        $item = $this->repo->findItem($itemId);
        if (!$item) {
            return ServiceResult::failure('Bien de la jornada no encontrado.');
        }
        $campaignCheck = $this->assertCampaignCapturable((int) $item['campaign_id']);
        if (!$campaignCheck->ok) {
            return $campaignCheck;
        }

        $campaign = $this->repo->findCampaign((int) $item['campaign_id']);
        $asset = $this->assets->find((int) $item['asset_id']);
        if (!$asset) {
            return ServiceResult::failure('El bien ya no existe en el inventario.');
        }

        $changes = $payload['changes'] ?? [];
        $locationChanged = !empty($changes['ubicacion']);
        $responsibleChanged = !empty($changes['responsable']);
        $stateChanged = !empty($changes['estado_fisico']);
        $serialChanged = !empty($changes['serial']);

        // Conflicto: el snapshot de la jornada no coincide con el dato ACTUAL del bien
        // para un campo que se está intentando corregir ahora mismo (alguien lo cambió
        // después de generarse la jornada). Ver Plan V5 §25.
        $conflicts = [];
        if ($locationChanged && (int) $item['location_id_snapshot'] !== (int) $asset['location_id']) {
            $conflicts['ubicacion'] = ['hoja' => $item['location_nombre_snapshot'], 'actual' => $asset['ubicacion_nombre']];
        }
        if ($responsibleChanged && (int) $item['responsible_id_snapshot'] !== (int) $asset['responsible_id']) {
            $conflicts['responsable'] = ['hoja' => $item['responsible_nombre_snapshot'], 'actual' => $asset['responsable_nombre']];
        }
        if ($stateChanged && (int) $item['physical_state_id_snapshot'] !== (int) $asset['physical_state_id']) {
            $conflicts['estado_fisico'] = ['hoja' => $item['physical_state_nombre_snapshot'], 'actual' => $asset['estado_fisico_nombre']];
        }
        if ($serialChanged && (string) $item['serial_snapshot'] !== (string) $asset['serial']) {
            $conflicts['serial'] = ['hoja' => $item['serial_snapshot'] ?: 'Sin serial', 'actual' => $asset['serial'] ?: 'Sin serial'];
        }

        if (!empty($conflicts) && empty($payload['force'])) {
            return ServiceResult::failure(
                'El dato actual del sistema cambió desde que se generó la hoja de verificación. Revisa el conflicto antes de continuar.',
                ['conflicts' => $conflicts]
            );
        }

        if ($locationChanged || $responsibleChanged) {
            $reassignResult = $this->assetMovements->reassign(
                (int) $asset['id'],
                new ReassignCommand(
                    locationId: $locationChanged ? (int) ($payload['ubicacion_observada_id'] ?? 0) : (int) $asset['location_id'],
                    responsibleId: $responsibleChanged ? (int) ($payload['responsable_observado_id'] ?? 0) : (int) $asset['responsible_id'],
                    motivo: sprintf('Actualizado durante jornada de verificación %s.', $campaign['codigo']),
                ),
                $actor
            );
            if (!$reassignResult->ok) {
                return ServiceResult::failure('No fue posible aplicar la corrección de ubicación/responsable: ' . $reassignResult->message);
            }
            $asset = $reassignResult->data['asset'];
        }

        if ($stateChanged || $serialChanged) {
            $db = Database::connection();
            $db->beginTransaction();
            try {
                $oldStateId = (int) $asset['physical_state_id'];
                $oldSerial = $asset['serial'];
                $newStateId = $stateChanged ? (int) ($payload['estado_fisico_observado_id'] ?? 0) : null;
                if ($stateChanged) {
                    $this->assets->updatePhysicalState((int) $asset['id'], $newStateId);
                }
                if ($serialChanged) {
                    $this->assets->updateSerial((int) $asset['id'], $payload['serial_observado'] ?? null);
                }
                $correctedAsset = $this->assets->find((int) $asset['id']);

                $observNota = $serialChanged
                    ? sprintf('Serial corregido: "%s" → "%s".', $item['serial_snapshot'] ?: 'sin serial', $payload['serial_observado'] ?? '')
                    : null;

                $this->movements->create([
                    'asset_id' => (int) $asset['id'],
                    'movement_type_id' => $this->movements->typeIdByCode('VERIFICACION'),
                    'estado_fisico_anterior_id' => $stateChanged ? $oldStateId : null,
                    'estado_fisico_nuevo_id' => $stateChanged ? $newStateId : null,
                    'motivo' => sprintf('Corrección durante jornada de verificación %s.', $campaign['codigo']),
                    'observaciones' => $observNota,
                    'usuario_id' => $actor['id'],
                ]);

                $before = [];
                $after = [];
                if ($stateChanged) {
                    $before['physical_state_id'] = $oldStateId;
                    $before['physical_state_name'] = $asset['estado_fisico_nombre'];
                    $after['physical_state_id'] = $newStateId;
                    $after['physical_state_name'] = $correctedAsset['estado_fisico_nombre'];
                }
                if ($serialChanged) {
                    $before['serial'] = $oldSerial;
                    $after['serial'] = $payload['serial_observado'] ?? null;
                }
                $this->audit->record(
                    $actor['id'], 'verification.correct', 'asset', (int) $asset['id'],
                    sprintf('%s corrigió %s durante la jornada %s.', $actor['nombre'], $asset['numero_bien'], $campaign['codigo']),
                    $before,
                    $after
                );

                $db->commit();
                $asset = $correctedAsset;
            } catch (\Throwable $e) {
                $db->rollBack();
                return ServiceResult::failure('No fue posible aplicar la corrección de estado físico/serial.');
            }
        }

        $this->repo->markVerified($itemId, [
            'sin_cambios' => false,
            'ubicacion_correcta' => $locationChanged ? 0 : null,
            'responsable_correcto' => $responsibleChanged ? 0 : null,
            'serial_correcto' => $serialChanged ? 0 : null,
            'estado_correcto' => $stateChanged ? 0 : null,
            'ubicacion_observada_id' => $locationChanged ? ($payload['ubicacion_observada_id'] ?? null) : null,
            'responsable_observado_id' => $responsibleChanged ? ($payload['responsable_observado_id'] ?? null) : null,
            'estado_fisico_observado_id' => $stateChanged ? ($payload['estado_fisico_observado_id'] ?? null) : null,
            'serial_observado' => $serialChanged ? ($payload['serial_observado'] ?? null) : null,
            'observacion_captura' => $payload['observacion_captura'] ?? null,
            'usuario_id' => $actor['id'],
            'estado_item' => 'VERIFICADO_CON_CAMBIOS',
        ]);
        $this->markCampaignInCapture((int) $item['campaign_id']);

        $this->audit->record(
            $actor['id'], 'verification.capture', 'verification_campaign_item', $itemId,
            sprintf('%s registró cambios de %s en la jornada.', $actor['nombre'], $item['numero_bien_snapshot'])
        );

        return ServiceResult::success(['next' => $this->repo->nextPendingItem((int) $item['campaign_id'])], 'Cambios registrados correctamente.');
    }

    public function completeCampaign(int $campaignId, array $actor, ?string $observaciones = null, bool $force = false): ServiceResult
    {
        $campaign = $this->repo->findCampaign($campaignId);
        if (!$campaign) {
            return ServiceResult::failure('Jornada no encontrada.');
        }
        if (!in_array($campaign['estado'], ['GENERADA', 'EN_VERIFICACION', 'EN_CAPTURA'], true)) {
            return ServiceResult::failure('Esta jornada ya está cerrada.');
        }

        $counters = $this->repo->counters($campaignId);
        if ((int) $counters['pendientes'] > 0) {
            if (!$force) {
                return ServiceResult::failure(
                    sprintf('Quedan %d bien(es) pendientes por capturar. Marca "cerrar con pendientes" y justifica el motivo si necesitas cerrar de todas formas.', $counters['pendientes']),
                    ['pendientes' => (int) $counters['pendientes']]
                );
            }
            if (empty($observaciones)) {
                return ServiceResult::failure('Debes justificar en observaciones por qué se cierra con bienes pendientes.');
            }
        }

        if ($observaciones) {
            $this->repo->updateCampaignObservaciones($campaignId, $observaciones);
        }
        $this->repo->updateCampaignEstado($campaignId, 'COMPLETADA', date('Y-m-d H:i:s'));

        $this->audit->record(
            $actor['id'], 'verification.complete', 'verification_campaign', $campaignId,
            sprintf('%s cerró la jornada %s (%d revisados, %d con cambios, %d pendientes).', $actor['nombre'], $campaign['codigo'], $counters['revisados'], $counters['con_cambios'], $counters['pendientes'])
        );

        $updatedCampaign = $this->repo->findCampaign($campaignId);
        $this->documents->generateVerificationClosure($updatedCampaign, $counters, $actor);

        return ServiceResult::success(['campaign' => $updatedCampaign], 'Jornada cerrada correctamente.');
    }

    public function cancelCampaign(int $campaignId, array $actor): ServiceResult
    {
        $campaign = $this->repo->findCampaign($campaignId);
        if (!$campaign) {
            return ServiceResult::failure('Jornada no encontrada.');
        }
        if (in_array($campaign['estado'], ['COMPLETADA', 'CANCELADA'], true)) {
            return ServiceResult::failure('Esta jornada ya no puede cancelarse.');
        }

        $this->repo->updateCampaignEstado($campaignId, 'CANCELADA', date('Y-m-d H:i:s'));

        $this->audit->record(
            $actor['id'], 'verification.cancel', 'verification_campaign', $campaignId,
            sprintf('%s canceló la jornada %s.', $actor['nombre'], $campaign['codigo'])
        );

        return ServiceResult::success([], 'Jornada cancelada.');
    }

    private function assertCampaignCapturable(int $campaignId): ServiceResult
    {
        $campaign = $this->repo->findCampaign($campaignId);
        if (!$campaign) {
            return ServiceResult::failure('Jornada no encontrada.');
        }
        if (!in_array($campaign['estado'], ['GENERADA', 'EN_VERIFICACION', 'EN_CAPTURA'], true)) {
            return ServiceResult::failure('Esta jornada ya está cerrada y no admite más capturas.');
        }
        return ServiceResult::success();
    }

    private function markCampaignInCapture(int $campaignId): void
    {
        $campaign = $this->repo->findCampaign($campaignId);
        if ($campaign && $campaign['estado'] !== 'EN_CAPTURA') {
            $this->repo->updateCampaignEstado($campaignId, 'EN_CAPTURA', null);
        }
    }
}
