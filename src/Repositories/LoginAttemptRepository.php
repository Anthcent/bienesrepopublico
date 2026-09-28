<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class LoginAttemptRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function isBlocked(array $identifiers, int $maxAttempts, int $lockoutMinutes): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE scope = :scope AND identifier_hash = :identifier
             AND attempted_at >= NOW() - make_interval(mins => :minutes)'
        );
        foreach ($identifiers as $scope => $identifier) {
            $stmt->execute([
                'scope' => $scope,
                'identifier' => $identifier,
                'minutes' => $lockoutMinutes,
            ]);
            $limit = $scope === 'ip' ? $maxAttempts * 5 : $maxAttempts;
            if ((int) $stmt->fetchColumn() >= $limit) {
                return true;
            }
        }
        return false;
    }

    public function acquireLocks(array $identifiers): void
    {
        ksort($identifiers);
        $stmt = $this->db->prepare('SELECT pg_advisory_lock(hashtext(:lock_key))');
        foreach ($identifiers as $scope => $identifier) {
            $stmt->execute(['lock_key' => $scope . ':' . $identifier]);
        }
    }

    public function releaseLocks(array $identifiers): void
    {
        ksort($identifiers);
        $stmt = $this->db->prepare('SELECT pg_advisory_unlock(hashtext(:lock_key))');
        foreach ($identifiers as $scope => $identifier) {
            $stmt->execute(['lock_key' => $scope . ':' . $identifier]);
        }
    }

    public function recordFailure(array $identifiers): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO login_attempts (scope, identifier_hash) VALUES (:scope, :identifier)'
        );
        foreach ($identifiers as $scope => $identifier) {
            $stmt->execute(['scope' => $scope, 'identifier' => $identifier]);
        }
        $this->db->exec("DELETE FROM login_attempts WHERE attempted_at < NOW() - INTERVAL '24 hours'");
    }

    public function clearAccount(string $identifier): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM login_attempts WHERE scope = :scope AND identifier_hash = :identifier'
        );
        $stmt->execute(['scope' => 'account', 'identifier' => $identifier]);
    }
}
