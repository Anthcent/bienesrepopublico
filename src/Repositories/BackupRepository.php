<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class BackupRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function latest(): ?array
    {
        $row = $this->db->query('SELECT * FROM backup_runs ORDER BY completed_at DESC, id DESC LIMIT 1')->fetch();
        return $row ?: null;
    }

    public function lastVerifiedRestore(): ?array
    {
        $row = $this->db->query(
            'SELECT * FROM backup_runs WHERE restore_verified_at IS NOT NULL ORDER BY restore_verified_at DESC LIMIT 1'
        )->fetch();
        return $row ?: null;
    }

    public function recordSuccess(int $sizeBytes, string $checksum, string $storageLabel, string $startedAt): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO backup_runs (status, storage_label, size_bytes, checksum_sha256, started_at)
             VALUES ('success', :storage, :size, :checksum, :started_at) RETURNING id"
        );
        $stmt->execute([
            'storage' => $storageLabel,
            'size' => $sizeBytes,
            'checksum' => strtolower($checksum),
            'started_at' => $startedAt,
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function recordFailure(string $message, string $startedAt): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO backup_runs (status, error_message, started_at)
             VALUES ('failed', :message, :started_at) RETURNING id"
        );
        $stmt->execute(['message' => $message, 'started_at' => $startedAt]);
        return (int) $stmt->fetchColumn();
    }

    public function markRestoreVerified(int $id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE backup_runs SET restore_verified_at = NOW() WHERE id = :id AND status = 'success'"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() === 1;
    }
}
