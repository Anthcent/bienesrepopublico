<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class AssetRepository
{
    private PDO $db;

    private const BASE_SELECT = '
        SELECT a.*, l.nombre AS ubicacion_nombre, l.piso_zona AS ubicacion_piso_zona,
               r.nombre AS responsable_nombre, r.cargo AS responsable_cargo,
               c.nombre AS categoria_nombre, b.nombre AS marca_nombre, m.nombre AS modelo_nombre,
               ps.nombre AS estado_fisico_nombre, ps.codigo AS estado_fisico_codigo
        FROM assets a
        JOIN locations l ON l.id = a.location_id
        JOIN responsibles r ON r.id = a.responsible_id
        LEFT JOIN categories c ON c.id = a.category_id
        LEFT JOIN brands b ON b.id = a.brand_id
        LEFT JOIN models m ON m.id = a.model_id
        JOIN physical_states ps ON ps.id = a.physical_state_id
    ';

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * Trae también datos de contacto del responsable (cédula/teléfono/email)
     * y quién incorporó el bien — la ficha del bien los necesita pero el
     * listado no, así que quedan fuera de BASE_SELECT (usado también por
     * paginate/search) en vez de encarecer esas consultas más grandes.
     */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT a.*, l.nombre AS ubicacion_nombre, l.piso_zona AS ubicacion_piso_zona,
                    r.nombre AS responsable_nombre, r.cargo AS responsable_cargo,
                    r.cedula AS responsable_cedula, r.telefono AS responsable_telefono, r.email AS responsable_email,
                    c.nombre AS categoria_nombre, b.nombre AS marca_nombre, m.nombre AS modelo_nombre,
                    ps.nombre AS estado_fisico_nombre, ps.codigo AS estado_fisico_codigo,
                    u.nombre AS creado_por_nombre
             FROM assets a
             JOIN locations l ON l.id = a.location_id
             JOIN responsibles r ON r.id = a.responsible_id
             LEFT JOIN categories c ON c.id = a.category_id
             LEFT JOIN brands b ON b.id = a.brand_id
             LEFT JOIN models m ON m.id = a.model_id
             JOIN physical_states ps ON ps.id = a.physical_state_id
             LEFT JOIN users u ON u.id = a.usuario_creador_id
             WHERE a.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByNumero(string $numero): ?array
    {
        $stmt = $this->db->prepare(self::BASE_SELECT . ' WHERE a.numero_bien = :numero LIMIT 1');
        $stmt->execute(['numero' => $numero]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function numeroExists(string $numero): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM assets WHERE numero_bien = :numero');
        $stmt->execute(['numero' => $numero]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function serialExists(string $serial): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM assets WHERE serial = :serial');
        $stmt->execute(['serial' => $serial]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /** Sugerencia de bien similar por marca+modelo o descripción cercana. */
    public function findSimilar(?int $brandId, ?int $modelId, string $descripcion): ?array
    {
        if ($brandId && $modelId) {
            $stmt = $this->db->prepare(self::BASE_SELECT . ' WHERE a.brand_id = :brand_id AND a.model_id = :model_id ORDER BY a.created_at DESC LIMIT 1');
            $stmt->execute(['brand_id' => $brandId, 'model_id' => $modelId]);
            $row = $stmt->fetch();
            if ($row) {
                return $row;
            }
        }

        $stmt = $this->db->prepare(self::BASE_SELECT . ' WHERE a.descripcion ILIKE :desc ORDER BY a.created_at DESC LIMIT 1');
        $stmt->execute(['desc' => '%' . $descripcion . '%']);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function paginate(array $filters, int $page, int $perPage): array
    {
        [$where, $params] = $this->buildFilters($filters);
        $offset = max(0, ($page - 1) * $perPage);

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM assets a {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = self::BASE_SELECT . " {$where} ORDER BY a.created_at DESC LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return ['items' => $stmt->fetchAll(), 'total' => $total];
    }

    public function search(string $term, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            self::BASE_SELECT . ' WHERE a.numero_bien ILIKE ? OR a.serial ILIKE ? OR a.descripcion ILIKE ?
             ORDER BY a.created_at DESC LIMIT ' . $limit
        );
        $like = '%' . $term . '%';
        $stmt->execute([$like, $like, $like]);
        return $stmt->fetchAll();
    }

    public function availableForLoan(string $term, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            self::BASE_SELECT . " WHERE a.estado_administrativo = 'ACTIVO' AND a.disponibilidad = 'DISPONIBLE'
             AND (a.numero_bien ILIKE ? OR a.serial ILIKE ? OR a.descripcion ILIKE ?)
             ORDER BY a.numero_bien ASC LIMIT " . $limit
        );
        $like = '%' . $term . '%';
        $stmt->execute([$like, $like, $like]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO assets (numero_bien, serial, descripcion, category_id, brand_id, model_id, color, material,
                imagen_url, location_id, responsible_id, physical_state_id, estado_administrativo, disponibilidad,
                informacion_completa, usuario_creador_id)
             VALUES (:numero_bien, :serial, :descripcion, :category_id, :brand_id, :model_id, :color, :material,
                :imagen_url, :location_id, :responsible_id, :physical_state_id, 'ACTIVO', 'DISPONIBLE',
                :informacion_completa, :usuario_creador_id)
             RETURNING id"
        );
        $stmt->execute([
            'numero_bien' => $data['numero_bien'],
            'serial' => $data['serial'] ?: null,
            'descripcion' => $data['descripcion'],
            'category_id' => $data['category_id'] ?: null,
            'brand_id' => $data['brand_id'] ?: null,
            'model_id' => $data['model_id'] ?: null,
            'color' => $data['color'] ?? null,
            'material' => $data['material'] ?? null,
            'imagen_url' => $data['imagen_url'] ?? null,
            'location_id' => $data['location_id'],
            'responsible_id' => $data['responsible_id'],
            'physical_state_id' => $data['physical_state_id'],
            'informacion_completa' => !empty($data['color']) && !empty($data['imagen_url']) ? 1 : 0,
            'usuario_creador_id' => $data['usuario_creador_id'],
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function updateAssignment(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE assets SET location_id = :location_id, responsible_id = :responsible_id WHERE id = :id'
        );
        $stmt->execute([
            'location_id' => $data['location_id'],
            'responsible_id' => $data['responsible_id'],
            'id' => $id,
        ]);
    }

    public function updateAdministrativeState(int $id, string $estado): void
    {
        $stmt = $this->db->prepare('UPDATE assets SET estado_administrativo = :estado WHERE id = :id');
        $stmt->execute(['estado' => $estado, 'id' => $id]);
    }

    public function updateAvailability(int $id, string $disponibilidad): void
    {
        $stmt = $this->db->prepare('UPDATE assets SET disponibilidad = :d WHERE id = :id');
        $stmt->execute(['d' => $disponibilidad, 'id' => $id]);
    }

    public function updatePhysicalState(int $id, int $physicalStateId): void
    {
        $stmt = $this->db->prepare('UPDATE assets SET physical_state_id = :ps WHERE id = :id');
        $stmt->execute(['ps' => $physicalStateId, 'id' => $id]);
    }

    public function updateSerial(int $id, ?string $serial): void
    {
        $stmt = $this->db->prepare('UPDATE assets SET serial = :serial WHERE id = :id');
        $stmt->execute(['serial' => $serial ?: null, 'id' => $id]);
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE assets SET descripcion = :descripcion, color = :color, material = :material,
                imagen_url = :imagen_url, category_id = :category_id, brand_id = :brand_id, model_id = :model_id
             WHERE id = :id'
        );
        $stmt->execute([
            'descripcion' => $data['descripcion'],
            'color' => $data['color'] ?? null,
            'material' => $data['material'] ?? null,
            'imagen_url' => $data['imagen_url'] ?? null,
            'category_id' => $data['category_id'] ?: null,
            'brand_id' => $data['brand_id'] ?: null,
            'model_id' => $data['model_id'] ?: null,
            'id' => $id,
        ]);
    }

    public function counters(): array
    {
        $row = $this->db->query(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN estado_administrativo = 'ACTIVO' THEN 1 ELSE 0 END) AS activos,
                SUM(CASE WHEN estado_administrativo = 'ACTIVO' AND disponibilidad = 'DISPONIBLE' THEN 1 ELSE 0 END) AS disponibles,
                SUM(CASE WHEN disponibilidad = 'PRESTADO' THEN 1 ELSE 0 END) AS prestados,
                SUM(CASE WHEN estado_administrativo = 'DESINCORPORADO' THEN 1 ELSE 0 END) AS desincorporados,
                SUM(CASE WHEN informacion_completa = 0 THEN 1 ELSE 0 END) AS incompletos
             FROM assets"
        )->fetch();
        return $row ?: [];
    }

    private function buildFilters(array $filters): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['q'])) {
            $conditions[] = '(a.numero_bien ILIKE :q1 OR a.serial ILIKE :q2 OR a.descripcion ILIKE :q3)';
            $like = '%' . $filters['q'] . '%';
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
        }
        if (!empty($filters['estado_administrativo'])) {
            $conditions[] = 'a.estado_administrativo = :estado_administrativo';
            $params['estado_administrativo'] = $filters['estado_administrativo'];
        }
        if (!empty($filters['disponibilidad'])) {
            $conditions[] = 'a.disponibilidad = :disponibilidad';
            $params['disponibilidad'] = $filters['disponibilidad'];
        }
        if (!empty($filters['physical_state_id'])) {
            $conditions[] = 'a.physical_state_id = :physical_state_id';
            $params['physical_state_id'] = $filters['physical_state_id'];
        }
        if (!empty($filters['location_id'])) {
            $conditions[] = 'a.location_id = :location_id';
            $params['location_id'] = $filters['location_id'];
        }
        if (!empty($filters['responsible_id'])) {
            $conditions[] = 'a.responsible_id = :responsible_id';
            $params['responsible_id'] = $filters['responsible_id'];
        }
        if (!empty($filters['category_id'])) {
            $conditions[] = 'a.category_id = :category_id';
            $params['category_id'] = $filters['category_id'];
        }
        if (!empty($filters['informacion_completa']) && $filters['informacion_completa'] === 'incompleta') {
            $conditions[] = 'a.informacion_completa = 0';
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        return [$where, $params];
    }
}
