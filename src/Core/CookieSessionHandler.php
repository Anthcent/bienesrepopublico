<?php

namespace App\Core;

use SessionHandlerInterface;

/**
 * Manejador de sesión persistido en cookie firmada (HMAC-SHA256).
 * Permite que las sesiones sobrevivan de forma confiable entre múltiples
 * instancias efímeras de contenedores serverless (Vercel Fluid Compute),
 * compartiendo el estado del cliente (CSRF, login, flashes) sin depender
 * de almacenamiento compartido.
 */
final class CookieSessionHandler implements SessionHandlerInterface
{
    private string $key;
    private string $cookieName;
    private int $ttl;
    private bool $secure;

    public function __construct(string $cookieName, string $key, int $ttl = 86400, bool $secure = true)
    {
        $this->cookieName = $cookieName . '_state';
        $this->key = hash('sha256', $key, true);
        $this->ttl = $ttl;
        $this->secure = $secure;
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
        $raw = $_COOKIE[$this->cookieName] ?? null;
        if (!is_string($raw) || !str_contains($raw, '.')) {
            return '';
        }

        [$payloadB64, $signature] = explode('.', $raw, 2);
        $expected = hash_hmac('sha256', $payloadB64, $this->key);
        if (!hash_equals($expected, $signature)) {
            return '';
        }

        $json = base64_decode($payloadB64, true);
        if (!is_string($json)) {
            return '';
        }

        $envelope = json_decode($json, true);
        if (!is_array($envelope) || !isset($envelope['data'], $envelope['exp'])) {
            return '';
        }

        if (time() > (int) $envelope['exp']) {
            return '';
        }

        return (string) $envelope['data'];
    }

    public function write(string $id, string $data): bool
    {
        if (headers_sent()) {
            return false;
        }

        $envelope = [
            'data' => $data,
            'exp' => time() + $this->ttl,
        ];
        $payloadB64 = base64_encode(json_encode($envelope));
        $signature = hash_hmac('sha256', $payloadB64, $this->key);
        $val = $payloadB64 . '.' . $signature;

        setcookie($this->cookieName, $val, [
            'expires' => time() + $this->ttl,
            'path' => '/',
            'secure' => $this->secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        return true;
    }

    public function destroy(string $id): bool
    {
        if (!headers_sent()) {
            setcookie($this->cookieName, '', [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => $this->secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        return 1;
    }
}
