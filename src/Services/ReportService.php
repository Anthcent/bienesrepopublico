<?php

namespace App\Services;

use App\Core\Database;

/**
 * M10 — Reportes. Flujo: plantilla -> filtros -> estimación -> preview -> PDF.
 * Cada plantilla define su SQL base; `estimate()` reutiliza el mismo WHERE
 * que `rows()` para que el número mostrado antes de generar sea exacto.
 */
final class ReportService
{
    public const TEMPLATES = [
        'inventario_general' => 'Inventario general',
        'activos' => 'Bienes activos',
        'prestados' => 'Bienes prestados',
        'prestamos_vencidos' => 'Préstamos vencidos',
        'desincorporados' => 'Bienes desincorporados',
        'movimientos' => 'Movimientos',
        'por_ubicacion' => 'Por ubicación',
        'por_responsable' => 'Por responsable',
        'por_estado_fisico' => 'Por estado físico',
        'verificacion' => 'Verificación / actualización',
        'personalizado' => 'Reporte personalizado',
    ];

    /** Columnas elegibles en la plantilla "Reporte personalizado" (whitelist — nunca se interpola texto libre del cliente en el SQL). */
    public const CUSTOM_COLUMNS = [
        'numero_bien' => 'a.numero_bien',
        'serial' => 'a.serial',
        'descripcion' => 'a.descripcion',
        'categoria_nombre' => 'c.nombre AS categoria_nombre',
        'marca_nombre' => 'b.nombre AS marca_nombre',
        'modelo_nombre' => 'mo.nombre AS modelo_nombre',
        'color' => 'a.color',
        'material' => 'a.material',
        'ubicacion_nombre' => 'l.nombre AS ubicacion_nombre',
        'responsable_nombre' => 'r.nombre AS responsable_nombre',
        'estado_administrativo' => 'a.estado_administrativo',
        'disponibilidad' => 'a.disponibilidad',
        'estado_fisico_nombre' => 'ps.nombre AS estado_fisico_nombre',
        'created_at' => 'a.created_at',
    ];

    public function estimate(string $template, array $filters): array
    {
        $rows = $this->rows($template, $filters);
        return [
            'registros' => count($rows),
            'ubicaciones' => count(array_unique(array_column($rows, 'ubicacion_nombre'))),
            'responsables' => count(array_unique(array_column($rows, 'responsable_nombre'))),
        ];
    }

    /**
     * Filtros comunes a `assets` (ubicación, responsable, estado, fechas,
     * búsqueda, bien específico). Se comparte entre el fallback genérico y
     * la plantilla "personalizado" para no duplicar el WHERE dos veces.
     *
     * @param string[] $conditions
     * @param array<string,mixed> $params
     * @return array{0: string[], 1: array<string,mixed>}
     */
    private function applyGenericFilters(array $conditions, array $params, array $filters): array
    {
        if (!empty($filters['asset_id'])) {
            $conditions[] = 'a.id = :asset_id';
            $params['asset_id'] = $filters['asset_id'];
        }
        if (!empty($filters['location_id'])) {
            $conditions[] = 'a.location_id = :location_id';
            $params['location_id'] = $filters['location_id'];
        }
        if (!empty($filters['responsible_id'])) {
            $conditions[] = 'a.responsible_id = :responsible_id';
            $params['responsible_id'] = $filters['responsible_id'];
        }
        if (!empty($filters['estado_administrativo'])) {
            $conditions[] = 'a.estado_administrativo = :estado_administrativo';
            $params['estado_administrativo'] = $filters['estado_administrativo'];
        }
        if (!empty($filters['disponibilidad'])) {
            $conditions[] = 'a.disponibilidad = :disponibilidad';
            $params['disponibilidad'] = $filters['disponibilidad'];
        }
        if (isset($filters['informacion_completa']) && $filters['informacion_completa'] !== '') {
            $conditions[] = 'a.informacion_completa = :informacion_completa';
            $params['informacion_completa'] = (int) $filters['informacion_completa'];
        }
        if (!empty($filters['desde'])) {
            $conditions[] = 'a.created_at >= :desde';
            $params['desde'] = $filters['desde'] . ' 00:00:00';
        }
        if (!empty($filters['hasta'])) {
            $conditions[] = 'a.created_at <= :hasta';
            $params['hasta'] = $filters['hasta'] . ' 23:59:59';
        }
        if (!empty($filters['q'])) {
            $conditions[] = '(a.numero_bien ILIKE :q1 OR a.serial ILIKE :q2 OR a.descripcion ILIKE :q3)';
            $like = '%' . $filters['q'] . '%';
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
        }

        return [$conditions, $params];
    }

    public function rows(string $template, array $filters): array
    {
        if (!isset(self::TEMPLATES[$template])) {
            return [];
        }

        $db = Database::connection();

        if ($template === 'personalizado') {
            return $this->customRows($db, $filters);
        }

        $conditions = [];
        $params = [];

        $base = 'SELECT a.numero_bien, a.serial, a.descripcion, a.estado_administrativo, a.disponibilidad,
                        l.nombre AS ubicacion_nombre, r.nombre AS responsable_nombre, ps.nombre AS estado_fisico_nombre,
                        a.created_at
                 FROM assets a
                 JOIN locations l ON l.id = a.location_id
                 JOIN responsibles r ON r.id = a.responsible_id
                 JOIN physical_states ps ON ps.id = a.physical_state_id';

        switch ($template) {
            case 'activos':
                $conditions[] = "a.estado_administrativo = 'ACTIVO'";
                break;
            case 'prestados':
                $conditions[] = "a.disponibilidad = 'PRESTADO'";
                break;
            case 'desincorporados':
                $conditions[] = "a.estado_administrativo = 'DESINCORPORADO'";
                break;
            case 'por_estado_fisico':
                if (!empty($filters['physical_state_id'])) {
                    $conditions[] = 'a.physical_state_id = :ps';
                    $params['ps'] = $filters['physical_state_id'];
                }
                break;
            case 'prestamos_vencidos':
                $stmt = $db->prepare(
                    "SELECT codigo AS numero_bien, prestatario_nombre_snapshot AS responsable_nombre,
                            fecha_vencimiento, estado, created_at
                     FROM loans WHERE estado = 'VENCIDO' ORDER BY fecha_vencimiento ASC"
                );
                $stmt->execute();
                return $stmt->fetchAll();
            case 'movimientos':
                // Alias propios (tipo_movimiento/motivo) en vez de reusar
                // estado_administrativo/estado_fisico_nombre: esos dos nombres
                // están en la lista de columnas ocultas del PDF (son internos
                // en las plantillas de bienes) — reusarlos aquí hacía que la
                // columna "Tipo de movimiento" desapareciera sin querer del
                // documento final.
                $stmt = $db->prepare(
                    "SELECT a.numero_bien, mt.nombre AS tipo_movimiento, am.motivo AS motivo,
                            u.nombre AS responsable_nombre, am.created_at
                     FROM asset_movements am
                     JOIN assets a ON a.id = am.asset_id
                     JOIN movement_types mt ON mt.id = am.movement_type_id
                     JOIN users u ON u.id = am.usuario_id
                     ORDER BY am.created_at DESC LIMIT 500"
                );
                $stmt->execute();
                return $stmt->fetchAll();
            case 'verificacion':
                $vConditions = [];
                $vParams = [];
                if (!empty($filters['location_id'])) {
                    $vConditions[] = 'vci.location_id_snapshot = :location_id';
                    $vParams['location_id'] = $filters['location_id'];
                }
                if (!empty($filters['responsible_id'])) {
                    $vConditions[] = 'vci.responsible_id_snapshot = :responsible_id';
                    $vParams['responsible_id'] = $filters['responsible_id'];
                }
                $vWhere = $vConditions ? ('WHERE ' . implode(' AND ', $vConditions)) : '';
                $stmt = $db->prepare(
                    "SELECT vci.numero_bien_snapshot AS numero_bien, vc.codigo AS jornada,
                            vci.estado_item AS resultado,
                            vci.location_nombre_snapshot AS ubicacion_nombre,
                            vci.responsible_nombre_snapshot AS responsable_nombre,
                            COALESCE(vci.fecha_captura, vc.created_at) AS created_at
                     FROM verification_campaign_items vci
                     JOIN verification_campaigns vc ON vc.id = vci.campaign_id
                     $vWhere
                     ORDER BY COALESCE(vci.fecha_captura, vc.created_at) DESC LIMIT 500"
                );
                $stmt->execute($vParams);
                return $stmt->fetchAll();
        }

        [$conditions, $params] = $this->applyGenericFilters($conditions, $params, $filters);

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $stmt = $db->prepare($base . ' ' . $where . ' ORDER BY a.numero_bien ASC LIMIT 1000');
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Plantilla "personalizado": el usuario elige qué columnas quiere ver. */
    private function customRows(\PDO $db, array $filters): array
    {
        $requested = is_array($filters['columns'] ?? null)
            ? $filters['columns']
            : array_filter(explode(',', (string) ($filters['columns'] ?? '')));
        $selected = array_values(array_intersect(array_keys(ReportService::CUSTOM_COLUMNS), $requested));
        if (empty($selected)) {
            $selected = ['numero_bien', 'descripcion', 'ubicacion_nombre', 'responsable_nombre'];
        }
        if (!in_array('numero_bien', $selected, true)) {
            array_unshift($selected, 'numero_bien');
        }

        $select = implode(', ', array_map(fn ($c) => self::CUSTOM_COLUMNS[$c], $selected));

        $base = "SELECT {$select}
                 FROM assets a
                 JOIN locations l ON l.id = a.location_id
                 JOIN responsibles r ON r.id = a.responsible_id
                 JOIN physical_states ps ON ps.id = a.physical_state_id
                 LEFT JOIN categories c ON c.id = a.category_id
                 LEFT JOIN brands b ON b.id = a.brand_id
                 LEFT JOIN models mo ON mo.id = a.model_id";

        [$conditions, $params] = $this->applyGenericFilters([], [], $filters);
        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $stmt = $db->prepare($base . ' ' . $where . ' ORDER BY a.numero_bien ASC LIMIT 1000');
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
