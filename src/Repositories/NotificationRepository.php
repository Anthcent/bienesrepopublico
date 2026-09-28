<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class NotificationRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * Inserta si la clave única no existe (idempotente). No duplica alertas.
     */
    public function upsert(array $data): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO notifications (tipo, usuario_destino_id, titulo, mensaje, nivel, entidad_tipo, entidad_id, clave_unica)
             VALUES (:tipo, :usuario_destino_id, :titulo, :mensaje, :nivel, :entidad_tipo, :entidad_id, :clave_unica)
             ON CONFLICT (clave_unica) DO UPDATE SET
                titulo = EXCLUDED.titulo, mensaje = EXCLUDED.mensaje, nivel = EXCLUDED.nivel, updated_at = NOW()'
        );
        $stmt->execute([
            'tipo' => $data['tipo'],
            'usuario_destino_id' => $data['usuario_destino_id'],
            'titulo' => $data['titulo'],
            'mensaje' => $data['mensaje'],
            'nivel' => $data['nivel'],
            'entidad_tipo' => $data['entidad_tipo'] ?? null,
            'entidad_id' => $data['entidad_id'] ?? null,
            'clave_unica' => $data['clave_unica'],
        ]);
    }

    public function forUser(int $userId, bool $onlyUnresolved = true, int $limit = 30): array
    {
        $where = $onlyUnresolved ? 'AND resuelta = 0' : '';
        $stmt = $this->db->prepare(
            "SELECT * FROM notifications WHERE usuario_destino_id = :uid {$where} ORDER BY created_at DESC LIMIT {$limit}"
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function unreadCount(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM notifications WHERE usuario_destino_id = :uid AND leida = 0 AND resuelta = 0');
        $stmt->execute(['uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function markRead(int $id, int $userId): void
    {
        $stmt = $this->db->prepare('UPDATE notifications SET leida = 1, fecha_lectura = NOW() WHERE id = :id AND usuario_destino_id = :uid');
        $stmt->execute(['id' => $id, 'uid' => $userId]);
    }

    public function markAllRead(int $userId): void
    {
        $stmt = $this->db->prepare('UPDATE notifications SET leida = 1, fecha_lectura = NOW() WHERE usuario_destino_id = :uid AND leida = 0');
        $stmt->execute(['uid' => $userId]);
    }

    public function markResolved(int $id, int $userId): void
    {
        $stmt = $this->db->prepare('UPDATE notifications SET resuelta = 1, fecha_resolucion = NOW() WHERE id = :id AND usuario_destino_id = :uid');
        $stmt->execute(['id' => $id, 'uid' => $userId]);
    }

    public function resolveByKeyPrefix(string $prefix): void
    {
        $stmt = $this->db->prepare("UPDATE notifications SET resuelta = 1, fecha_resolucion = NOW() WHERE clave_unica ILIKE :p AND resuelta = 0");
        $stmt->execute(['p' => $prefix . '%']);
    }

    public function purgeOtherLoanAlertCycles(int $loanId, array $activeKeys): void
    {
        if (!$activeKeys) {
            $stmt = $this->db->prepare(
                "DELETE FROM notifications
                 WHERE tipo = 'loan_alert' AND entidad_id = :loan_id AND resuelta = 0"
            );
            $stmt->execute(['loan_id' => $loanId]);
            return;
        }

        $placeholders = [];
        $params = ['loan_id' => $loanId];
        foreach (array_values($activeKeys) as $index => $key) {
            $name = 'key_' . $index;
            $placeholders[] = ':' . $name;
            $params[$name] = $key;
        }

        $stmt = $this->db->prepare(
            "DELETE FROM notifications
             WHERE tipo = 'loan_alert' AND entidad_id = :loan_id AND resuelta = 0
             AND clave_unica NOT IN (" . implode(', ', $placeholders) . ')'
        );
        $stmt->execute($params);
    }

    public function purgeLoanAlertsOutside(array $activeLoanIds): void
    {
        $params = [];
        $outside = '';
        if ($activeLoanIds) {
            $placeholders = [];
            foreach (array_values($activeLoanIds) as $index => $loanId) {
                $name = 'loan_' . $index;
                $placeholders[] = ':' . $name;
                $params[$name] = $loanId;
            }
            $outside = 'AND entidad_id NOT IN (' . implode(', ', $placeholders) . ')';
        }

        $stmt = $this->db->prepare(
            "DELETE FROM notifications
             WHERE tipo = 'loan_alert' AND resuelta = 0 {$outside}"
        );
        $stmt->execute($params);
    }

    /** Todos los usuarios con permiso operativo, para difundir alertas de préstamos. */
    public function allActiveUserIds(): array
    {
        $stmt = $this->db->query('SELECT id FROM users WHERE activo = 1');
        return array_map('intval', array_column($stmt->fetchAll(), 'id'));
    }
}
