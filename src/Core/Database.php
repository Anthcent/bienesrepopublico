<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Envoltorio único de conexión PDO (PostgreSQL). Toda consulta del
 * sistema pasa por aquí usando prepared statements — ninguna consulta
 * concatena entrada de usuario directamente en el SQL.
 */
final class Database
{
    private static ?PDO $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../Config/config.php';
            $db = $config['db'];

            // DATABASE_URL (formato postgres://user:pass@host:port/dbname)
            // es lo que Hexper Ops/Dokploy inyectan con el PostgreSQL
            // automático. En local, si no está definida, se arma el mismo
            // formato a partir de las variables DB_* de config.php.
            $url = $db['url'] ?? null;
            if ($url) {
                $parts = parse_url($url);
                $host = $parts['host'] ?? '127.0.0.1';
                $port = $parts['port'] ?? '5432';
                $name = isset($parts['path']) ? ltrim($parts['path'], '/') : '';
                $user = isset($parts['user']) ? rawurldecode($parts['user']) : '';
                $pass = isset($parts['pass']) ? rawurldecode($parts['pass']) : '';
                $query = [];
                parse_str($parts['query'] ?? '', $query);
                $sslmode = $query['sslmode'] ?? null;
            } else {
                $host = $db['host'];
                $port = $db['port'];
                $name = $db['name'];
                $user = $db['user'];
                $pass = $db['pass'];
                $sslmode = getenv('DB_SSLMODE') ?: null;
            }

            $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $name);
            if (in_array($sslmode, ['disable', 'allow', 'prefer', 'require', 'verify-ca', 'verify-full'], true)) {
                $dsn .= ';sslmode=' . $sslmode;
            }

            try {
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                if (PHP_SAPI === 'cli') {
                    throw $e;
                }
                http_response_code(500);
                die('No fue posible conectar a la base de datos. Verifique DATABASE_URL (o DB_HOST/DB_PORT/DB_NAME/DB_USER/DB_PASS) y que el esquema haya sido importado.');
            }
        }

        return self::$instance;
    }
}
