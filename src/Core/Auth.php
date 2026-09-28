<?php

namespace App\Core;

use App\Repositories\UserRepository;
use App\Services\SecuritySettingsService;

/**
 * Autenticación y usuario en sesión. Las reglas de permisos viven en
 * App\Services\PermissionService — este objeto solo identifica quién
 * es el usuario actual.
 */
final class Auth
{
    private static ?array $user = null;
    private static bool $resolved = false;

    public static function attempt(string $email, string $password): bool
    {
        $repo = new UserRepository();
        $user = $repo->findByEmail(mb_strtolower(trim($email)));

        if (!$user || (int) $user['activo'] !== 1) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $now = time();
        Session::set('user_id', (int) $user['id']);
        Session::set('authenticated_at', $now);
        Session::set('last_activity', $now);
        Session::set('last_rotation', $now);
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $repo->updatePasswordHash((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
        }
        $repo->touchLastAccess((int) $user['id']);
        self::$user = $user;
        self::$resolved = true;

        return true;
    }

    public static function logout(): void
    {
        Session::destroy();
        self::$user = null;
        self::$resolved = false;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user ? (int) $user['id'] : null;
    }

    public static function user(): ?array
    {
        if (self::$resolved) {
            return self::$user;
        }
        self::$resolved = true;

        $userId = Session::get('user_id');
        if (!$userId) {
            return self::$user = null;
        }

        $now = time();
        $settings = (new SecuritySettingsService())->current();
        $authenticatedAt = Session::get('authenticated_at');
        if (!$authenticatedAt) {
            $authenticatedAt = $now;
            Session::set('authenticated_at', $now);
        }
        $authenticatedAt = (int) $authenticatedAt;
        $lastActivity = Session::get('last_activity');
        if (!$lastActivity) {
            $lastActivity = $authenticatedAt;
            Session::set('last_activity', $lastActivity);
        }
        $lastActivity = (int) $lastActivity;
        if ($now - $lastActivity > $settings['idle_timeout_minutes'] * 60) {
            Session::expire('Tu sesión terminó por inactividad. Inicia sesión nuevamente.');
            return self::$user = null;
        }
        if ($now - $authenticatedAt > $settings['absolute_session_hours'] * 3600) {
            Session::expire('Tu sesión alcanzó su duración máxima. Inicia sesión nuevamente.');
            return self::$user = null;
        }
        if ($now - (int) Session::get('last_rotation', $authenticatedAt) >= 15 * 60) {
            session_regenerate_id(true);
            Session::set('last_rotation', $now);
        }
        $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $isPassiveRequest = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET'
            && $requestPath === '/api/notifications';
        if (!$isPassiveRequest) {
            Session::set('last_activity', $now);
        }

        $repo = new UserRepository();
        $user = $repo->findById((int) $userId);
        if (!$user || (int) $user['activo'] !== 1) {
            Session::destroy();
            return self::$user = null;
        }

        return self::$user = $user;
    }

    public static function roleCode(): ?string
    {
        return self::user()['role_codigo'] ?? null;
    }
}
