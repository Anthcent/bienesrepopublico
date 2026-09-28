<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Jornadas de Verificación Patrimonial (Plan Maestro V5 §17-30).
 * Los contadores de progreso (revisados/con cambios/no encontrados/
 * pendientes) nunca se guardan como columna: siempre se calculan con
 * COUNT() agrupado sobre verification_campaign_items, igual que
 * LoanRepository::counters() hace con los préstamos.
 */
final class VerificationRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function nextCode(): string
    {
        $year = date('Y');
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM verification_campaigns WHERE codigo ILIKE :prefix");
        $stmt->execute(['prefix' => "JV-{$year}-%"]);
        $count = (int) $stmt->fetchColumn() + 1;
        return sprintf('JV-%s-%03d', $year, $count);
    }

    public function createCampaign(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO verification_campaigns (codigo, titulo, descripcion, fecha_programada, ubicacion_id,
                responsible_id, campos_json, creado_por_usuario_id, estado, cantidad_bienes, observaciones)
             VALUES (:codigo, :titulo, :descripcion, :fecha_programada, :ubicacion_id, :responsible_id,
                :campos_json, :creado_por, :estado, :cantidad_bienes, :observaciones)
             RETURNING id'
        );
        $stmt->execute([
            'codigo' => $data['codigo'],
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'] ?? null,
            'fecha_programada' => $data['fecha_programada'] ?? null,
            'ubicacion_id' => $data['ubicacion_id'] ?? null,
            'responsible_id' => $data['responsible_id'] ?? null,
            'campos_json' => json_encode($data['campos'] ?? [], JSON_UNESCAPED_UNICODE),
            'creado_por' => $data['creado_por_usuario_id'],
            'estado' => $data['estado'] ?? 'GENERADA',
            'cantidad_bienes' => $data['cantidad_bienes'] ?? 0,
            'observaciones' => $data['observaciones'] ?? null,
        ]);
        return (int) $stmt->fetchColumn();
    }

    private const CAMPAIGN_SELECT = '
        SELECT vc.*, l.nombre AS ubicacion_nombre, r.nombre AS responsable_nombre, u.nombre AS creador_nombre
        FROM verification_campaigns vc
        LEFT JOIN locations l ON l.id = vc.ubicacion_id
        LEFT JOIN responsibles r ON r.id = vc.responsible_id
        JOIN users u ON u.id = vc.creado_por_usuario_id
    ';

    public function findCampaign(int $id): ?array
    {
        $stmt = $this->db->prepare(self::CAMPAIGN_SELECT . ' WHERE vc.id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function paginateCampaigns(array $filters, int $page, int $perPage): array
    {
        $conditions = [];
        $params = [];
        if (!empty($filters['estado'])) {
            $conditions[] = 'vc.estado = :estado';
            $params['estado'] = $filters['estado'];
        }
        if (!empty($filters['q'])) {
            $conditions[] = '(vc.codigo ILIKE :q1 OR vc.titulo ILIKE :q2)';
            $like = '%' . $filters['q'] . '%';
            $params['q1'] = $like;
            $params['q2'] = $like;
        }
        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $offset = max(0, ($page - 1) * $perPage);

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM verification_campaigns vc {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->db->prepare(
            self::CAMPAIGN_SELECT . " {$where} ORDER BY vc.created_at DESC LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);

        return ['items' => $stmt->fetchAll(), 'total' => $total];
    }

    public function updateCampaignEstado(int $id, string $estado, ?string $fechaCierre = null): void
    {
        $stmt = $this->db->prepare('UPDATE verification_campaigns SET estado = :estado, fecha_cierre = :fecha WHERE id = :id');
        $stmt->execute(['estado' => $estado, 'fecha' => $fechaCierre, 'id' => $id]);
    }

    public function updateCampaignObservaciones(int $id, string $observaciones): void
    {
        $stmt = $this->db->prepare('UPDATE verification_campaigns SET observaciones = :obs WHERE id = :id');
        $stmt->execute(['obs' => $observaciones, 'id' => $id]);
    }

    public function insertItem(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO verification_campaign_items (campaign_id, asset_id, numero_bien_snapshot, descripcion_snapshot,
                serial_snapshot, location_id_snapshot, location_nombre_snapshot, responsible_id_snapshot,
                responsible_nombre_snapshot, physical_state_id_snapshot, physical_state_nombre_snapshot,
                requiere_fotografia, estado_item)
             VALUES (:campaign_id, :asset_id, :numero_bien, :descripcion, :serial, :location_id, :location_nombre,
                :responsible_id, :responsible_nombre, :physical_state_id, :physical_state_nombre,
                :requiere_fotografia, \'PENDIENTE\')
             RETURNING id'
        );
        $stmt->execute([
            'campaign_id' => $data['campaign_id'],
            'asset_id' => $data['asset_id'],
            'numero_bien' => $data['numero_bien_snapshot'],
            'descripcion' => $data['descripcion_snapshot'],
            'serial' => $data['serial_snapshot'],
            'location_id' => $data['location_id_snapshot'],
            'location_nombre' => $data['location_nombre_snapshot'],
            'responsible_id' => $data['responsible_id_snapshot'],
            'responsible_nombre' => $data['responsible_nombre_snapshot'],
            'physical_state_id' => $data['physical_state_id_snapshot'],
            'physical_state_nombre' => $data['physical_state_nombre_snapshot'],
            'requiere_fotografia' => !empty($data['requiere_fotografia']) ? 1 : 0,
        ]);
        return (int) $stmt->fetchColumn();
    }

    private const ITEM_SELECT = '
        SELECT vci.*,
               lo.nombre AS ubicacion_observada_nombre,
               ro.nombre AS responsable_observado_nombre,
               pso.nombre AS estado_fisico_observado_nombre,
               cap.nombre AS capturado_por_nombre
        FROM verification_campaign_items vci
        LEFT JOIN locations lo ON lo.id = vci.ubicacion_observada_id
        LEFT JOIN responsibles ro ON ro.id = vci.responsable_observado_id
        LEFT JOIN physical_states pso ON pso.id = vci.estado_fisico_observado_id
        LEFT JOIN users cap ON cap.id = vci.capturado_por_usuario_id
    ';

    public function itemsForCampaign(int $campaignId): array
    {
        $stmt = $this->db->prepare(self::ITEM_SELECT . ' WHERE vci.campaign_id = :cid ORDER BY vci.id ASC');
        $stmt->execute(['cid' => $campaignId]);
        return $stmt->fetchAll();
    }

    public function findItem(int $id): ?array
    {
        $stmt = $this->db->prepare(self::ITEM_SELECT . ' WHERE vci.id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Primer item PENDIENTE de la jornada (captura secuencial "uno por vez"). */
    public function nextPendingItem(int $campaignId): ?array
    {
        $stmt = $this->db->prepare(
            self::ITEM_SELECT . ' WHERE vci.campaign_id = :cid AND vci.estado_item = \'PENDIENTE\' ORDER BY vci.id ASC LIMIT 1'
        );
        $stmt->execute(['cid' => $campaignId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function positionInCampaign(int $campaignId, int $itemId): array
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM verification_campaign_items WHERE campaign_id = :cid AND id <= :id'
        );
        $stmt->execute(['cid' => $campaignId, 'id' => $itemId]);
        $position = (int) $stmt->fetchColumn();
        return ['position' => $position];
    }

    public function markVerified(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE verification_campaign_items SET
                verificado = 1, sin_cambios = :sin_cambios,
                ubicacion_correcta = :ubicacion_correcta, responsable_correcto = :responsable_correcto,
                serial_correcto = :serial_correcto, estado_correcto = :estado_correcto,
                ubicacion_observada_id = :ubicacion_observada_id, responsable_observado_id = :responsable_observado_id,
                estado_fisico_observado_id = :estado_fisico_observado_id, serial_observado = :serial_observado,
                observacion_captura = :observacion_captura,
                capturado_por_usuario_id = :usuario_id, fecha_captura = NOW(), estado_item = :estado_item
             WHERE id = :id'
        );
        $stmt->execute([
            'sin_cambios' => !empty($data['sin_cambios']) ? 1 : 0,
            'ubicacion_correcta' => $data['ubicacion_correcta'] ?? null,
            'responsable_correcto' => $data['responsable_correcto'] ?? null,
            'serial_correcto' => $data['serial_correcto'] ?? null,
            'estado_correcto' => $data['estado_correcto'] ?? null,
            'ubicacion_observada_id' => $data['ubicacion_observada_id'] ?? null,
            'responsable_observado_id' => $data['responsable_observado_id'] ?? null,
            'estado_fisico_observado_id' => $data['estado_fisico_observado_id'] ?? null,
            'serial_observado' => $data['serial_observado'] ?? null,
            'observacion_captura' => $data['observacion_captura'] ?? null,
            'usuario_id' => $data['usuario_id'],
            'estado_item' => $data['estado_item'],
            'id' => $id,
        ]);
    }

    public function markNotFound(int $id, int $userId, ?string $observacion): void
    {
        $stmt = $this->db->prepare(
            "UPDATE verification_campaign_items SET
                verificado = 1, sin_cambios = 0, estado_item = 'NO_ENCONTRADO',
                observacion_captura = :obs, capturado_por_usuario_id = :usuario_id, fecha_captura = NOW()
             WHERE id = :id"
        );
        $stmt->execute(['obs' => $observacion, 'usuario_id' => $userId, 'id' => $id]);
    }

    /** Conteos de progreso — siempre calculados, nunca almacenados (ver cabecera del archivo). */
    public function counters(int $campaignId): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN estado_item <> 'PENDIENTE' THEN 1 ELSE 0 END) AS revisados,
                SUM(CASE WHEN estado_item = 'VERIFICADO_CON_CAMBIOS' THEN 1 ELSE 0 END) AS con_cambios,
                SUM(CASE WHEN estado_item = 'NO_ENCONTRADO' THEN 1 ELSE 0 END) AS no_encontrados,
                SUM(CASE WHEN estado_item = 'PENDIENTE' THEN 1 ELSE 0 END) AS pendientes
             FROM verification_campaign_items WHERE campaign_id = :cid"
        );
        $stmt->execute(['cid' => $campaignId]);
        return $stmt->fetch() ?: ['total' => 0, 'revisados' => 0, 'con_cambios' => 0, 'no_encontrados' => 0, 'pendientes' => 0];
    }

    /** Contadores globales para el dashboard y el listado de jornadas. */
    public function globalCounters(): array
    {
        $row = $this->db->query(
            "SELECT
                SUM(CASE WHEN estado IN ('GENERADA','EN_VERIFICACION','EN_CAPTURA') THEN 1 ELSE 0 END) AS activas
             FROM verification_campaigns"
        )->fetch();
        return $row ?: ['activas' => 0];
    }

    public function activeCampaigns(int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            self::CAMPAIGN_SELECT . " WHERE vc.estado IN ('GENERADA','EN_VERIFICACION','EN_CAPTURA')
             ORDER BY vc.created_at DESC LIMIT {$limit}"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
