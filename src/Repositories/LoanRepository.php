<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class LoanRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function nextCode(): string
    {
        $year = date('Y');
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM loans WHERE codigo ILIKE :prefix");
        $stmt->execute(['prefix' => "PR-{$year}-%"]);
        $count = (int) $stmt->fetchColumn() + 1;
        return sprintf('PR-%s-%04d', $year, $count);
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO loans (codigo, responsible_id, prestatario_nombre_snapshot, prestatario_cargo_snapshot,
                prestatario_dependencia_snapshot, fecha_prestamo, fecha_vencimiento, motivo, observaciones,
                estado, usuario_creador_id)
             VALUES (:codigo, :responsible_id, :nombre, :cargo, :dependencia, :fecha_prestamo, :fecha_vencimiento,
                :motivo, :observaciones, 'ACTIVO', :usuario_creador_id)
             RETURNING id"
        );
        $stmt->execute([
            'codigo' => $data['codigo'],
            'responsible_id' => $data['responsible_id'] ?? null,
            'nombre' => $data['prestatario_nombre'],
            'cargo' => $data['prestatario_cargo'] ?? null,
            'dependencia' => $data['prestatario_dependencia'] ?? null,
            'fecha_prestamo' => $data['fecha_prestamo'],
            'fecha_vencimiento' => $data['fecha_vencimiento'],
            'motivo' => $data['motivo'] ?? null,
            'observaciones' => $data['observaciones'] ?? null,
            'usuario_creador_id' => $data['usuario_creador_id'],
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function addDetail(int $loanId, int $assetId, int $estadoSalidaId): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO loan_details (loan_id, asset_id, estado_salida_id) VALUES (:loan_id, :asset_id, :estado) RETURNING id'
        );
        $stmt->execute(['loan_id' => $loanId, 'asset_id' => $assetId, 'estado' => $estadoSalidaId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Trae también quién registró el préstamo y los datos de contacto del
     * responsable elegido (cédula/teléfono/email) — antes `find()` solo
     * traía `loans.*`, así que la ficha no podía mostrar ni el motivo/
     * observaciones (que ya existían en la tabla) ni con quién comunicarse.
     */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT l.*, u.nombre AS creado_por_nombre,
                    r.cedula AS responsable_cedula, r.telefono AS responsable_telefono, r.email AS responsable_email
             FROM loans l
             LEFT JOIN users u ON u.id = l.usuario_creador_id
             LEFT JOIN responsibles r ON r.id = l.responsible_id
             WHERE l.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function details(int $loanId): array
    {
        $stmt = $this->db->prepare(
            'SELECT ld.*, a.numero_bien, a.descripcion, a.disponibilidad,
                    ps_out.nombre AS estado_salida_nombre, ps_in.nombre AS estado_devolucion_nombre
             FROM loan_details ld
             JOIN assets a ON a.id = ld.asset_id
             JOIN physical_states ps_out ON ps_out.id = ld.estado_salida_id
             LEFT JOIN physical_states ps_in ON ps_in.id = ld.estado_devolucion_id
             WHERE ld.loan_id = :loan_id'
        );
        $stmt->execute(['loan_id' => $loanId]);
        return $stmt->fetchAll();
    }

    public function findDetail(int $detailId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM loan_details WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $detailId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function returnDetail(int $detailId, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE loan_details SET fecha_devolucion = :fecha, estado_devolucion_id = :estado,
                observacion_devolucion = :obs, imagen_devolucion_url = :img, devuelto_por_usuario_id = :usuario
             WHERE id = :id'
        );
        $stmt->execute([
            'fecha' => $data['fecha'],
            'estado' => $data['estado_devolucion_id'],
            'obs' => $data['observacion'] ?? null,
            'img' => $data['imagen_url'] ?? null,
            'usuario' => $data['usuario_id'],
            'id' => $detailId,
        ]);
    }

    public function updateEstado(int $loanId, string $estado, ?string $fechaCierre = null): void
    {
        $stmt = $this->db->prepare('UPDATE loans SET estado = :estado, fecha_cierre = :fecha_cierre WHERE id = :id');
        $stmt->execute(['estado' => $estado, 'fecha_cierre' => $fechaCierre, 'id' => $loanId]);
    }

    public function updateVencimiento(int $loanId, string $fecha): void
    {
        $stmt = $this->db->prepare('UPDATE loans SET fecha_vencimiento = :fecha WHERE id = :id');
        $stmt->execute(['fecha' => $fecha, 'id' => $loanId]);
    }

    public function addExtension(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO loan_extensions (loan_id, fecha_anterior, fecha_nueva, motivo, usuario_id)
             VALUES (:loan_id, :fecha_anterior, :fecha_nueva, :motivo, :usuario_id)
             RETURNING id'
        );
        $stmt->execute([
            'loan_id' => $data['loan_id'],
            'fecha_anterior' => $data['fecha_anterior'],
            'fecha_nueva' => $data['fecha_nueva'],
            'motivo' => $data['motivo'] ?? null,
            'usuario_id' => $data['usuario_id'],
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function paginate(array $filters, int $page, int $perPage): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['estado'])) {
            $conditions[] = 'estado = :estado';
            $params['estado'] = $filters['estado'];
        }
        if (!empty($filters['q'])) {
            $conditions[] = '(codigo ILIKE :q1 OR prestatario_nombre_snapshot ILIKE :q2)';
            $like = '%' . $filters['q'] . '%';
            $params['q1'] = $like;
            $params['q2'] = $like;
        }
        if (!empty($filters['desde'])) {
            $conditions[] = 'fecha_vencimiento >= :desde';
            $params['desde'] = $filters['desde'];
        }
        if (!empty($filters['hasta'])) {
            $conditions[] = 'fecha_vencimiento <= :hasta';
            $params['hasta'] = $filters['hasta'];
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $offset = max(0, ($page - 1) * $perPage);

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM loans {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->db->prepare(
            "SELECT * FROM loans {$where} ORDER BY fecha_vencimiento ASC LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);

        return ['items' => $stmt->fetchAll(), 'total' => $total];
    }

    public function dueSoonOrOverdue(int $days): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM loans
             WHERE estado IN ('ACTIVO','PARCIALMENTE_DEVUELTO','VENCIDO')
             AND fecha_vencimiento <= CURRENT_DATE + make_interval(days => :days)
             ORDER BY fecha_vencimiento ASC"
        );
        $stmt->execute(['days' => $days]);
        return $stmt->fetchAll();
    }

    public function dueSoon(int $days): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM loans
             WHERE estado IN ('ACTIVO','PARCIALMENTE_DEVUELTO')
             AND fecha_vencimiento BETWEEN CURRENT_DATE AND CURRENT_DATE + make_interval(days => :days)
             ORDER BY fecha_vencimiento ASC"
        );
        $stmt->execute(['days' => $days]);
        return $stmt->fetchAll();
    }

    public function allOpenPastDue(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM loans WHERE estado IN ('ACTIVO','PARCIALMENTE_DEVUELTO') AND fecha_vencimiento < CURRENT_DATE"
        );
        return $stmt->fetchAll();
    }

    public function counters(int $dueAlertDays): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                SUM(CASE WHEN estado IN ('ACTIVO','PARCIALMENTE_DEVUELTO') THEN 1 ELSE 0 END) AS activos,
                SUM(CASE WHEN estado = 'VENCIDO' THEN 1 ELSE 0 END) AS vencidos,
                SUM(CASE WHEN estado IN ('ACTIVO','PARCIALMENTE_DEVUELTO') AND fecha_vencimiento <= CURRENT_DATE + make_interval(days => :days) AND fecha_vencimiento >= CURRENT_DATE THEN 1 ELSE 0 END) AS proximos
             FROM loans"
        );
        $stmt->execute(['days' => $dueAlertDays]);
        $row = $stmt->fetch();
        return $row ?: [];
    }

    /** Vencidos "de verdad" por fecha, sin depender de que el estado ya se haya marcado VENCIDO (transición perezosa). */
    public function countOverdue(): int
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM loans
             WHERE estado IN ('ACTIVO','PARCIALMENTE_DEVUELTO','VENCIDO') AND fecha_vencimiento < CURRENT_DATE"
        );
        return (int) $stmt->fetchColumn();
    }

    public function extensions(int $loanId): array
    {
        $stmt = $this->db->prepare(
            'SELECT le.*, u.nombre AS usuario_nombre FROM loan_extensions le
             JOIN users u ON u.id = le.usuario_id
             WHERE le.loan_id = :loan_id ORDER BY le.created_at DESC'
        );
        $stmt->execute(['loan_id' => $loanId]);
        return $stmt->fetchAll();
    }

    /** Historial de préstamos en los que participó un bien (M22, pestaña "Préstamos"). */
    public function forAsset(int $assetId): array
    {
        $stmt = $this->db->prepare(
            'SELECT l.*, ld.id AS detail_id, ld.fecha_devolucion, ld.estado_salida_id,
                    ps.nombre AS estado_salida_nombre
             FROM loan_details ld
             JOIN loans l ON l.id = ld.loan_id
             JOIN physical_states ps ON ps.id = ld.estado_salida_id
             WHERE ld.asset_id = :asset_id
             ORDER BY l.fecha_prestamo DESC'
        );
        $stmt->execute(['asset_id' => $assetId]);
        return $stmt->fetchAll();
    }

    public function loanIdForAsset(int $assetId): ?int
    {
        $stmt = $this->db->prepare(
            "SELECT ld.loan_id FROM loan_details ld
             JOIN loans l ON l.id = ld.loan_id
             WHERE ld.asset_id = :asset_id AND ld.fecha_devolucion IS NULL
             AND l.estado IN ('ACTIVO','PARCIALMENTE_DEVUELTO','VENCIDO')
             ORDER BY ld.id DESC LIMIT 1"
        );
        $stmt->execute(['asset_id' => $assetId]);
        $id = $stmt->fetchColumn();
        return $id !== false ? (int) $id : null;
    }
}
