<?php

namespace App\Repositories;

use App\Core\ClientIp;
use App\Core\Database;
use PDO;

final class AuditRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function log(array $data): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO audit_log (usuario_id, accion, entidad_tipo, entidad_id, resumen_humano,
                valores_anteriores_json, valores_nuevos_json, ip)
             VALUES (:usuario_id, :accion, :entidad_tipo, :entidad_id, :resumen, :antes, :despues, :ip)'
        );
        $stmt->execute([
            'usuario_id' => $data['usuario_id'] ?? null,
            'accion' => $data['accion'],
            'entidad_tipo' => $data['entidad_tipo'],
            'entidad_id' => $data['entidad_id'],
            'resumen' => $data['resumen_humano'],
            'antes' => isset($data['antes']) ? json_encode($data['antes'], JSON_UNESCAPED_UNICODE) : null,
            'despues' => isset($data['despues']) ? json_encode($data['despues'], JSON_UNESCAPED_UNICODE) : null,
            'ip' => $data['ip'] ?? ClientIp::address(),
        ]);
    }

    public function forEntity(string $entidadTipo, int $entidadId): array
    {
        $stmt = $this->db->prepare(
            'SELECT al.*, u.nombre AS usuario_nombre FROM audit_log al
             LEFT JOIN users u ON u.id = al.usuario_id
             WHERE al.entidad_tipo = :tipo AND al.entidad_id = :id
             ORDER BY al.created_at DESC'
        );
        $stmt->execute(['tipo' => $entidadTipo, 'id' => $entidadId]);
        return $stmt->fetchAll();
    }

    public function paginate(array $filters, int $page, int $perPage): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['entidad_tipo'])) {
            $conditions[] = 'al.entidad_tipo = :entidad_tipo';
            $params['entidad_tipo'] = $filters['entidad_tipo'];
        }
        if (!empty($filters['accion'])) {
            $conditions[] = 'al.accion = :accion';
            $params['accion'] = $filters['accion'];
        }
        if (!empty($filters['q'])) {
            $conditions[] = '(al.resumen_humano ILIKE :q_resumen OR al.accion ILIKE :q_accion
                OR al.entidad_tipo ILIKE :q_entidad OR u.nombre ILIKE :q_usuario)';
            $like = '%' . $filters['q'] . '%';
            $params['q_resumen'] = $like;
            $params['q_accion'] = $like;
            $params['q_entidad'] = $like;
            $params['q_usuario'] = $like;
        }
        if (($filters['usuario_id'] ?? '') === 'system') {
            $conditions[] = 'al.usuario_id IS NULL';
        } elseif (!empty($filters['usuario_id']) && ctype_digit((string) $filters['usuario_id'])) {
            $conditions[] = 'al.usuario_id = :usuario_id';
            $params['usuario_id'] = (int) $filters['usuario_id'];
        }
        if (!empty($filters['desde'])) {
            $conditions[] = 'al.created_at >= :desde';
            $params['desde'] = $filters['desde'] . ' 00:00:00';
        }
        if (!empty($filters['hasta'])) {
            $conditions[] = 'al.created_at < :hasta';
            $params['hasta'] = (new \DateTimeImmutable($filters['hasta']))->modify('+1 day')->format('Y-m-d 00:00:00');
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $offset = max(0, ($page - 1) * $perPage);

        $countStmt = $this->db->prepare(
            "SELECT COUNT(*) FROM audit_log al LEFT JOIN users u ON u.id = al.usuario_id {$where}"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->db->prepare(
            "SELECT al.*, u.nombre AS usuario_nombre FROM audit_log al
             LEFT JOIN users u ON u.id = al.usuario_id
             {$where} ORDER BY al.created_at DESC LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);

        return ['items' => $stmt->fetchAll(), 'total' => $total];
    }

    public function filterOptions(): array
    {
        return [
            'actions' => $this->db->query('SELECT DISTINCT accion FROM audit_log ORDER BY accion')->fetchAll(PDO::FETCH_COLUMN),
            'entities' => $this->db->query('SELECT DISTINCT entidad_tipo FROM audit_log ORDER BY entidad_tipo')->fetchAll(PDO::FETCH_COLUMN),
            'users' => $this->db->query(
                'SELECT DISTINCT u.id, u.nombre FROM audit_log al
                 JOIN users u ON u.id = al.usuario_id ORDER BY u.nombre'
            )->fetchAll(),
        ];
    }
}
