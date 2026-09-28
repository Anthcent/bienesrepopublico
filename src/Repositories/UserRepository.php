<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class UserRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.*, r.codigo AS role_codigo, r.nombre AS role_nombre
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE LOWER(u.email) = LOWER(:email) LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.*, r.codigo AS role_codigo, r.nombre AS role_nombre
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function touchLastAccess(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE users SET ultimo_acceso = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function updatePasswordHash(int $id, string $passwordHash): void
    {
        $stmt = $this->db->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
        $stmt->execute(['password_hash' => $passwordHash, 'id' => $id]);
    }

    public function paginate(array $filters, int $page, int $perPage): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['q'])) {
            $conditions[] = '(u.nombre ILIKE :q1 OR u.email ILIKE :q2 OR u.cargo ILIKE :q3)';
            $like = '%' . $filters['q'] . '%';
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
        }
        if (!empty($filters['role_id'])) {
            $conditions[] = 'u.role_id = :role_id';
            $params['role_id'] = $filters['role_id'];
        }
        if (isset($filters['activo']) && $filters['activo'] !== '') {
            $conditions[] = 'u.activo = :activo';
            $params['activo'] = (int) $filters['activo'];
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $offset = max(0, ($page - 1) * $perPage);

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM users u {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->db->prepare(
            "SELECT u.*, r.codigo AS role_codigo, r.nombre AS role_nombre
             FROM users u JOIN roles r ON r.id = u.role_id
             {$where}
             ORDER BY u.nombre ASC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);

        return ['items' => $stmt->fetchAll(), 'total' => $total];
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (role_id, nombre, cargo, email, password_hash, activo)
             VALUES (:role_id, :nombre, :cargo, :email, :password_hash, :activo)
             RETURNING id'
        );
        $stmt->execute([
            'role_id' => $data['role_id'],
            'nombre' => $data['nombre'],
            'cargo' => $data['cargo'] ?? null,
            'email' => mb_strtolower($data['email']),
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'activo' => 1,
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET role_id = :role_id, nombre = :nombre, cargo = :cargo, email = :email
             WHERE id = :id'
        );
        $stmt->execute([
            'role_id' => $data['role_id'],
            'nombre' => $data['nombre'],
            'cargo' => $data['cargo'] ?? null,
            'email' => mb_strtolower($data['email']),
            'id' => $id,
        ]);
    }

    public function setActive(int $id, bool $active): void
    {
        $stmt = $this->db->prepare('UPDATE users SET activo = :activo WHERE id = :id');
        $stmt->execute(['activo' => $active ? 1 : 0, 'id' => $id]);
    }

    public function allRoles(): array
    {
        return $this->db->query('SELECT * FROM roles ORDER BY id')->fetchAll();
    }

    public function roleCode(int $roleId): ?string
    {
        $stmt = $this->db->prepare('SELECT codigo FROM roles WHERE id = :id');
        $stmt->execute(['id' => $roleId]);
        $code = $stmt->fetchColumn();
        return $code !== false ? (string) $code : null;
    }

    public function countActiveAdmins(): int
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.activo = 1 AND r.codigo = 'ADMIN'"
        );
        return (int) $stmt->fetchColumn();
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE LOWER(email) = LOWER(:email)';
        $params = ['email' => mb_strtolower($email)];
        if ($excludeId) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }
}
