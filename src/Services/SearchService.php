<?php

namespace App\Services;

use App\Core\Database;

/**
 * Búsqueda global agrupada (M13). Ctrl/Cmd+K en el frontend consume
 * GET /api/search?q=... y recibe resultados agrupados por entidad.
 */
final class SearchService
{
    public function global(string $term, int $limitPerGroup = 5, bool $includeUsers = false): array
    {
        $db = Database::connection();
        $like = '%' . $term . '%';

        $assets = $this->run($db,
            'SELECT id, numero_bien AS label, descripcion AS sublabel FROM assets
             WHERE numero_bien ILIKE ? OR serial ILIKE ? OR descripcion ILIKE ?
             ORDER BY created_at DESC LIMIT ' . $limitPerGroup,
            $like
        );

        $loans = $this->run($db,
            'SELECT id, codigo AS label, prestatario_nombre_snapshot AS sublabel FROM loans
             WHERE codigo ILIKE ? OR prestatario_nombre_snapshot ILIKE ?
             ORDER BY created_at DESC LIMIT ' . $limitPerGroup,
            $like
        );

        $responsibles = $this->run($db,
            'SELECT id, nombre AS label, dependencia AS sublabel FROM responsibles
             WHERE nombre ILIKE ? AND activo = 1 ORDER BY nombre LIMIT ' . $limitPerGroup,
            $like
        );

        $locations = $this->run($db,
            'SELECT id, nombre AS label, piso_zona AS sublabel FROM locations
             WHERE nombre ILIKE ? AND activo = 1 ORDER BY nombre LIMIT ' . $limitPerGroup,
            $like
        );

        $users = $includeUsers
            ? $this->run($db,
                'SELECT id, nombre AS label, email AS sublabel FROM users
                 WHERE (nombre ILIKE ? OR email ILIKE ?) AND activo = 1 ORDER BY nombre LIMIT ' . $limitPerGroup,
                $like
            )
            : [];

        $documents = $this->run($db,
            "SELECT id, tipo AS label, CONCAT('Versión ', version) AS sublabel FROM documents
             WHERE tipo ILIKE ? ORDER BY created_at DESC LIMIT " . $limitPerGroup,
            $like
        );

        $verifications = $this->run($db,
            'SELECT id, codigo AS label, titulo AS sublabel FROM verification_campaigns
             WHERE codigo ILIKE ? OR titulo ILIKE ? ORDER BY created_at DESC LIMIT ' . $limitPerGroup,
            $like
        );

        return [
            'bienes' => $assets,
            'prestamos' => $loans,
            'responsables' => $responsibles,
            'ubicaciones' => $locations,
            'usuarios' => $users,
            'documentos' => $documents,
            'jornadas' => $verifications,
        ];
    }

    private function run(\PDO $db, string $sql, string $like): array
    {
        $stmt = $db->prepare($sql);
        $placeholders = substr_count($sql, '?');
        $stmt->execute(array_fill(0, $placeholders, $like));
        return $stmt->fetchAll();
    }
}
