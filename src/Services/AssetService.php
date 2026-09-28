<?php

namespace App\Services;

use App\Commands\CreateAssetCommand;
use App\Core\Database;
use App\Repositories\AssetRepository;
use App\Repositories\CatalogRepository;
use App\Repositories\MovementRepository;

final class AssetService
{
    private AssetRepository $assets;
    private CatalogRepository $catalogs;
    private MovementRepository $movements;
    private AuditService $audit;
    private NotificationService $notifications;
    private DocumentService $documents;

    public function __construct()
    {
        $this->assets = new AssetRepository();
        $this->catalogs = new CatalogRepository();
        $this->movements = new MovementRepository();
        $this->audit = new AuditService();
        $this->notifications = new NotificationService();
        $this->documents = new DocumentService();
    }

    public function checkNumberAvailable(string $numero): bool
    {
        return $numero !== '' && !$this->assets->numeroExists($numero);
    }

    public function checkSerialAvailable(string $serial): bool
    {
        return $serial === '' || !$this->assets->serialExists($serial);
    }

    public function findSimilar(?int $brandId, ?int $modelId, string $descripcion): ?array
    {
        if (strlen($descripcion) < 3 && !$brandId) {
            return null;
        }
        return $this->assets->findSimilar($brandId, $modelId, $descripcion);
    }

    public function list(array $filters, int $page, int $perPage): array
    {
        return $this->assets->paginate($filters, $page, $perPage);
    }

    public function find(int $id): ?array
    {
        return $this->assets->find($id);
    }

    public function counters(): array
    {
        return $this->assets->counters();
    }

    /**
     * Incorporación V3: crea el bien + movimiento inicial + documento +
     * auditoría + notificación de información incompleta, en una sola
     * transacción atómica.
     */
    public function incorporate(CreateAssetCommand $command, array $actor): ServiceResult
    {
        $errors = [];
        if ($command->numeroBien === '') {
            $errors['numero_bien'] = 'El número de bien es obligatorio.';
        } elseif ($this->assets->numeroExists($command->numeroBien)) {
            $errors['numero_bien'] = 'Ya existe un bien registrado con ese número.';
        }
        if ($command->serial && $this->assets->serialExists($command->serial)) {
            $errors['serial'] = 'Ya existe un bien registrado con ese serial.';
        }
        if ($command->descripcion === '') {
            $errors['descripcion'] = 'La descripción es obligatoria.';
        }
        if (!$this->catalogs->find('locations', $command->locationId)) {
            $errors['location_id'] = 'La ubicación seleccionada no es válida.';
        }
        if (!$this->catalogs->find('responsibles', $command->responsibleId)) {
            $errors['responsible_id'] = 'El responsable seleccionado no es válido.';
        }
        if (!$this->catalogs->find('physical_states', $command->physicalStateId)) {
            $errors['physical_state_id'] = 'El estado físico seleccionado no es válido.';
        }

        if ($errors) {
            return ServiceResult::failure('Revisa los datos del formulario.', $errors);
        }

        $db = Database::connection();
        $db->beginTransaction();

        try {
            $assetId = $this->assets->create(array_merge($command->toArray(), [
                'usuario_creador_id' => $actor['id'],
            ]));

            $movementTypeId = $this->movements->typeIdByCode('INCORPORACION');
            $movementId = $this->movements->create([
                'asset_id' => $assetId,
                'movement_type_id' => $movementTypeId,
                'ubicacion_nueva_id' => $command->locationId,
                'responsable_nuevo_id' => $command->responsibleId,
                'estado_fisico_nuevo_id' => $command->physicalStateId,
                'motivo' => 'Incorporación / registro inicial',
                'usuario_id' => $actor['id'],
            ]);

            $asset = $this->assets->find($assetId);

            $auditData = array_merge($command->toArray(), [
                'category_name' => $asset['categoria_nombre'],
                'brand_name' => $asset['marca_nombre'],
                'model_name' => $asset['modelo_nombre'],
                'location_name' => $asset['ubicacion_nombre'],
                'responsible_name' => $asset['responsable_nombre'],
                'physical_state_name' => $asset['estado_fisico_nombre'],
            ]);
            $this->audit->record(
                $actor['id'],
                'asset.incorporate',
                'asset',
                $assetId,
                sprintf('%s incorporó el bien %s (%s).', $actor['nombre'], $asset['numero_bien'], $asset['descripcion']),
                null,
                $auditData
            );

            $this->documents->generateAssetIncorporation($asset, $actor);

            if (!$asset['informacion_completa']) {
                $this->notifications->notifyIncompleteInfo($actor['id'], $assetId, $asset['numero_bien']);
            }

            $db->commit();

            return ServiceResult::success(['asset' => $asset, 'movement_id' => $movementId], 'Bien incorporado correctamente.');
        } catch (\Throwable $e) {
            $db->rollBack();
            return ServiceResult::failure('No fue posible incorporar el bien. Intenta nuevamente.');
        }
    }

    public function update(int $id, array $data, array $actor): ServiceResult
    {
        $asset = $this->assets->find($id);
        if (!$asset) {
            return ServiceResult::failure('Bien no encontrado.');
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $this->assets->update($id, $data);
            $updated = $this->assets->find($id);
            $trackedFields = ['descripcion', 'color', 'material', 'imagen_url', 'category_id', 'brand_id', 'model_id'];
            $catalogNames = [
                'category_id' => ['category_name', 'categoria_nombre'],
                'brand_id' => ['brand_name', 'marca_nombre'],
                'model_id' => ['model_name', 'modelo_nombre'],
            ];
            $before = [];
            $after = [];

            foreach ($trackedFields as $field) {
                if ((string) ($asset[$field] ?? '') === (string) ($updated[$field] ?? '')) {
                    continue;
                }
                $before[$field] = $asset[$field] ?? null;
                $after[$field] = $updated[$field] ?? null;
                if (isset($catalogNames[$field])) {
                    [$snapshotField, $resultField] = $catalogNames[$field];
                    $before[$snapshotField] = $asset[$resultField] ?? null;
                    $after[$snapshotField] = $updated[$resultField] ?? null;
                }
            }

            if ($after) {
                $this->audit->record(
                    (int) $actor['id'],
                    'asset.update',
                    'asset',
                    $id,
                    sprintf('%s actualizó la información del bien %s.', $actor['nombre'], $asset['numero_bien']),
                    $before,
                    $after
                );
            }

            $db->commit();
            return ServiceResult::success(['asset' => $updated], 'Bien actualizado.');
        } catch (\Throwable $e) {
            $db->rollBack();
            return ServiceResult::failure('No fue posible actualizar el bien.');
        }
    }
}
