<?php

namespace App\Core;

use SessionHandlerInterface;

/**
 * Sesiones persistidas en PostgreSQL. Evita depender del filesystem local
 * del contenedor, que puede cambiar entre instancias o reinicios en Vercel.
 */
final class DatabaseSessionHandler implements SessionHandlerInterface
{
    private int $ttl;

    public function __construct(int $ttl = 86400)
    {
        $this->ttl = $ttl;
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $stmt = Database::connection()->prepare(
            'SELECT payload FROM app_sessions WHERE id = :id AND expires_at > CURRENT_TIMESTAMP'
        );
        $stmt->execute(['id' => $id]);
        $payload = $stmt->fetchColumn();
        return $payload === false ? '' : (string) $payload;
    }

    public function write(string $id, string $data): bool
    {
        $stmt = Database::connection()->prepare(
            "INSERT INTO app_sessions (id, payload, expires_at, updated_at)
             VALUES (:id, :payload, CURRENT_TIMESTAMP + (:ttl * INTERVAL '1 second'), CURRENT_TIMESTAMP)
             ON CONFLICT (id) DO UPDATE SET
                payload = EXCLUDED.payload,
                expires_at = EXCLUDED.expires_at,
                updated_at = CURRENT_TIMESTAMP"
        );
        return $stmt->execute([
            'id' => $id,
            'payload' => $data,
            'ttl' => $this->ttl,
        ]);
    }

    public function destroy(string $id): bool
    {
        $stmt = Database::connection()->prepare('DELETE FROM app_sessions WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $stmt = Database::connection()->prepare('DELETE FROM app_sessions WHERE expires_at <= CURRENT_TIMESTAMP');
        $stmt->execute();
        return $stmt->rowCount();
    }
}
