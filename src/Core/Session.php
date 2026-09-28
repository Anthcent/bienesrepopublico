<?php

namespace App\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/../Config/config.php';
            session_name($config['app']['session_name']);
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            $ttl = 24 * 60 * 60;
            ini_set('session.gc_maxlifetime', (string) $ttl);
            session_set_save_handler(new DatabaseSessionHandler($ttl), true);
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
                'cookie_secure' => $config['app']['cookie_secure'],
                'cookie_path' => '/',
            ]);
        }
    }

    public static function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, ?string $value = null)
    {
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }
        $val = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $val;
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function expire(string $message): void
    {
        self::destroy();
        self::start();
        self::flash('login_error', $message);
    }
}
