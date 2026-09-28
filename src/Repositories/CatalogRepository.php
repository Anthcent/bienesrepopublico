<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Repositorio genérico para catálogos simples (ubicaciones, responsables,
 * categorías, marcas, modelos, estados físicos). Cada catálogo es una
 * tabla pequeña con operaciones CRUD equivalentes.
 */
final class CatalogRepository
{
    private PDO $db;

    private const TABLES = [
        'locations' => ['nombre', 'piso_zona', 'imagen_url'],
        'responsibles' => ['nombre', 'cedula', 'cargo', 'dependencia', 'email', 'telefono', 'ubicacion_habitual_id'],
        'categories' => ['nombre', 'tipo_bien', 'icono'],
        'brands' => ['nombre'],
        'models' => ['brand_id', 'nombre'],
        'physical_states' => ['codigo', 'nombre', 'orden', 'es_negativo', 'color'],
        'movement_types' => ['codigo', 'nombre'],
    ];

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function all(string $table, bool $onlyActive = true): array
    {
        $this->assertTable($table);
        $hasActivo = $table !== 'physical_states' && $table !== 'movement_types';
        $where = ($onlyActive && $hasActivo) ? 'WHERE activo = 1' : '';
        $order = $table === 'physical_states' ? 'orden' : 'nombre';
        return $this->db->query("SELECT * FROM {$table} {$where} ORDER BY {$order} ASC")->fetchAll();
    }

    /** Catálogos con muchos registros (a diferencia de all(), que trae todo para llenar selects). */
    public function paginate(string $table, int $page, int $perPage, bool $onlyActive = false): array
    {
        $this->assertTable($table);
        $hasActivo = $table !== 'physical_states' && $table !== 'movement_types';
        $where = ($onlyActive && $hasActivo) ? 'WHERE activo = 1' : '';
        $order = $table === 'physical_states' ? 'orden' : 'nombre';
        $offset = max(0, ($page - 1) * $perPage);

        $total = (int) $this->db->query("SELECT COUNT(*) FROM {$table} {$where}")->fetchColumn();
        $stmt = $this->db->prepare("SELECT * FROM {$table} {$where} ORDER BY {$order} ASC LIMIT {$perPage} OFFSET {$offset}");
        $stmt->execute();

        return ['items' => $stmt->fetchAll(), 'total' => $total];
    }

    public function find(string $table, int $id): ?array
    {
        $this->assertTable($table);
        $stmt = $this->db->prepare("SELECT * FROM {$table} WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function search(string $table, string $term, int $limit = 10): array
    {
        $this->assertTable($table);
        $hasActivo = $table !== 'physical_states' && $table !== 'movement_types';
        $activoClause = $hasActivo ? 'activo = 1 AND' : '';
        $stmt = $this->db->prepare(
            "SELECT * FROM {$table} WHERE {$activoClause} nombre ILIKE :term ORDER BY nombre ASC LIMIT {$limit}"
        );
        $stmt->execute(['term' => '%' . $term . '%']);
        return $stmt->fetchAll();
    }

    public function create(string $table, array $data): int
    {
        $this->assertTable($table);
        $columns = array_intersect_key($data, array_flip(self::TABLES[$table]));
        $fields = array_keys($columns);
        $placeholders = array_map(fn($f) => ':' . $f, $fields);

        $stmt = $this->db->prepare(
            "INSERT INTO {$table} (" . implode(', ', $fields) . ') VALUES (' . implode(', ', $placeholders) . ') RETURNING id'
        );
        $stmt->execute($columns);
        return (int) $stmt->fetchColumn();
    }

    public function update(string $table, int $id, array $data): void
    {
        $this->assertTable($table);
        $columns = array_intersect_key($data, array_flip(self::TABLES[$table]));
        $assignments = implode(', ', array_map(fn($f) => "{$f} = :{$f}", array_keys($columns)));
        $columns['id'] = $id;

        $stmt = $this->db->prepare("UPDATE {$table} SET {$assignments} WHERE id = :id");
        $stmt->execute($columns);
    }

    public function setActive(string $table, int $id, bool $active): void
    {
        $this->assertTable($table);
        if ($table === 'physical_states' || $table === 'movement_types') {
            throw new \InvalidArgumentException("El catálogo {$table} no admite activar/desactivar.");
        }
        $stmt = $this->db->prepare("UPDATE {$table} SET activo = :activo WHERE id = :id");
        $stmt->execute(['activo' => $active ? 1 : 0, 'id' => $id]);
    }

    public function modelsByBrand(int $brandId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM models WHERE brand_id = :brand_id AND activo = 1 ORDER BY nombre');
        $stmt->execute(['brand_id' => $brandId]);
        return $stmt->fetchAll();
    }

    public function createModel(int $brandId, string $nombre): int
    {
        $stmt = $this->db->prepare('INSERT INTO models (brand_id, nombre) VALUES (:brand_id, :nombre) RETURNING id');
        $stmt->execute(['brand_id' => $brandId, 'nombre' => $nombre]);
        return (int) $stmt->fetchColumn();
    }

    public function locationStats(int $locationId): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN disponibilidad = 'PRESTADO' THEN 1 ELSE 0 END) AS prestados,
                SUM(CASE WHEN estado_administrativo = 'ACTIVO' THEN 1 ELSE 0 END) AS activos
             FROM assets WHERE location_id = :id"
        );
        $stmt->execute(['id' => $locationId]);
        return $stmt->fetch() ?: ['total' => 0, 'prestados' => 0, 'activos' => 0];
    }

    public function responsibleStats(int $responsibleId): array
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) AS total FROM assets WHERE responsible_id = :id');
        $stmt->execute(['id' => $responsibleId]);
        $assets = (int) $stmt->fetchColumn();

        $stmt2 = $this->db->prepare("SELECT COUNT(*) FROM loans WHERE responsible_id = :id AND estado IN ('ACTIVO','PARCIALMENTE_DEVUELTO','VENCIDO')");
        $stmt2->execute(['id' => $responsibleId]);
        $loans = (int) $stmt2->fetchColumn();

        return ['bienes' => $assets, 'prestamos' => $loans];
    }

    private function assertTable(string $table): void
    {
        if (!isset(self::TABLES[$table])) {
            throw new \InvalidArgumentException("Catálogo no soportado: {$table}");
        }
    }
}
