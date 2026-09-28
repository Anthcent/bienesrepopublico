<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class DocumentRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(array $data): int
    {
        $version = $this->nextVersion($data['entidad_tipo'], $data['entidad_id'], $data['tipo']);

        $stmt = $this->db->prepare(
            "INSERT INTO documents (tipo, entidad_tipo, entidad_id, version, estado, contenido_html, usuario_id)
             VALUES (:tipo, :entidad_tipo, :entidad_id, :version, 'VIGENTE', :contenido, :usuario_id)
             RETURNING id"
        );
        $stmt->execute([
            'tipo' => $data['tipo'],
            'entidad_tipo' => $data['entidad_tipo'],
            'entidad_id' => $data['entidad_id'],
            'version' => $version,
            'contenido' => $data['contenido_html'],
            'usuario_id' => $data['usuario_id'],
        ]);

        return (int) $stmt->fetchColumn();
    }

    private function nextVersion(string $entidadTipo, int $entidadId, string $tipo): int
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(MAX(version), 0) FROM documents WHERE entidad_tipo = :tipo_e AND entidad_id = :id AND tipo = :tipo'
        );
        $stmt->execute(['tipo_e' => $entidadTipo, 'id' => $entidadId, 'tipo' => $tipo]);
        return ((int) $stmt->fetchColumn()) + 1;
    }

    public function forEntity(string $entidadTipo, int $entidadId): array
    {
        $stmt = $this->db->prepare(
            'SELECT d.*, u.nombre AS usuario_nombre FROM documents d
             JOIN users u ON u.id = d.usuario_id
             WHERE d.entidad_tipo = :tipo AND d.entidad_id = :id
             ORDER BY d.tipo ASC, d.version DESC'
        );
        $stmt->execute(['tipo' => $entidadTipo, 'id' => $entidadId]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM documents WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function recent(int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            'SELECT d.*, u.nombre AS usuario_nombre FROM documents d JOIN users u ON u.id = d.usuario_id
             ORDER BY d.created_at DESC LIMIT ' . $limit
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
