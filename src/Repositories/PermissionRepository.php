<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class PermissionRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return string[] códigos de permiso del rol de un usuario */
    public function permissionsForUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.codigo FROM permissions p
             JOIN role_permissions rp ON rp.permission_id = p.id
             JOIN users u ON u.role_id = rp.role_id
             WHERE u.id = :user_id'
        );
        $stmt->execute(['user_id' => $userId]);
        return array_column($stmt->fetchAll(), 'codigo');
    }
}
