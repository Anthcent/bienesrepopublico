<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class MovementRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO asset_movements (asset_id, movement_type_id, ubicacion_anterior_id, ubicacion_nueva_id,
                responsable_anterior_id, responsable_nuevo_id, estado_fisico_anterior_id, estado_fisico_nuevo_id,
                motivo, observaciones, usuario_id)
             VALUES (:asset_id, :movement_type_id, :ubicacion_anterior_id, :ubicacion_nueva_id,
                :responsable_anterior_id, :responsable_nuevo_id, :estado_fisico_anterior_id, :estado_fisico_nuevo_id,
                :motivo, :observaciones, :usuario_id)
             RETURNING id'
        );
        $stmt->execute([
            'asset_id' => $data['asset_id'],
            'movement_type_id' => $data['movement_type_id'],
            'ubicacion_anterior_id' => $data['ubicacion_anterior_id'] ?? null,
            'ubicacion_nueva_id' => $data['ubicacion_nueva_id'] ?? null,
            'responsable_anterior_id' => $data['responsable_anterior_id'] ?? null,
            'responsable_nuevo_id' => $data['responsable_nuevo_id'] ?? null,
            'estado_fisico_anterior_id' => $data['estado_fisico_anterior_id'] ?? null,
            'estado_fisico_nuevo_id' => $data['estado_fisico_nuevo_id'] ?? null,
            'motivo' => $data['motivo'] ?? null,
            'observaciones' => $data['observaciones'] ?? null,
            'usuario_id' => $data['usuario_id'],
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function forAsset(int $assetId): array
    {
        $stmt = $this->db->prepare(
            'SELECT am.*, mt.codigo AS tipo_codigo, mt.nombre AS tipo_nombre,
                    u.nombre AS usuario_nombre,
                    lo_ant.nombre AS ubicacion_anterior_nombre, lo_new.nombre AS ubicacion_nueva_nombre,
                    r_ant.nombre AS responsable_anterior_nombre, r_new.nombre AS responsable_nuevo_nombre
             FROM asset_movements am
             JOIN movement_types mt ON mt.id = am.movement_type_id
             JOIN users u ON u.id = am.usuario_id
             LEFT JOIN locations lo_ant ON lo_ant.id = am.ubicacion_anterior_id
             LEFT JOIN locations lo_new ON lo_new.id = am.ubicacion_nueva_id
             LEFT JOIN responsibles r_ant ON r_ant.id = am.responsable_anterior_id
             LEFT JOIN responsibles r_new ON r_new.id = am.responsable_nuevo_id
             WHERE am.asset_id = :asset_id
             ORDER BY am.created_at DESC'
        );
        $stmt->execute(['asset_id' => $assetId]);
        return $stmt->fetchAll();
    }

    public function recent(int $limit = 8): array
    {
        $stmt = $this->db->prepare(
            'SELECT am.*, mt.codigo AS tipo_codigo, mt.nombre AS tipo_nombre, a.numero_bien, a.descripcion,
                    u.nombre AS usuario_nombre
             FROM asset_movements am
             JOIN movement_types mt ON mt.id = am.movement_type_id
             JOIN assets a ON a.id = am.asset_id
             JOIN users u ON u.id = am.usuario_id
             ORDER BY am.created_at DESC LIMIT ' . $limit
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function typeIdByCode(string $code): ?int
    {
        $stmt = $this->db->prepare('SELECT id FROM movement_types WHERE codigo = :codigo LIMIT 1');
        $stmt->execute(['codigo' => $code]);
        $id = $stmt->fetchColumn();
        return $id !== false ? (int) $id : null;
    }
}
