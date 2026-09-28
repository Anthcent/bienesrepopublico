<?php
/**
 * Configuración central. En producción, mover estos valores a variables
 * de entorno (.env) fuera del control de versiones.
 */
$environment = getenv('APP_ENV') ?: 'production';
$cookieSecure = filter_var(getenv('COOKIE_SECURE') ?: ($environment === 'production' ? '1' : '0'), FILTER_VALIDATE_BOOLEAN);
$demoEnv = getenv('ENABLE_DEMO_DATA');
$demoEnabled = ($demoEnv === false || $demoEnv === '') ? true : filter_var($demoEnv, FILTER_VALIDATE_BOOLEAN);

return [
    'app' => [
        'name' => 'Sistema de Bienes Públicos',
        'base_path' => '', // ajustar si la app vive en un subdirectorio, ej: '/adolfo/public'
        'timezone' => 'America/Caracas',
        'session_name' => 'bp_session',
        'environment' => $environment,
        'cookie_secure' => $cookieSecure,
        'demo_enabled' => $demoEnabled,
        'trusted_proxies' => array_values(array_filter(array_map('trim', explode(',', getenv('TRUSTED_PROXIES') ?: '')))),
    ],
    // Conexión a PostgreSQL. En despliegue (Hexper Ops/Dokploy) se usa
    // DATABASE_URL, inyectada automáticamente al habilitar el PostgreSQL
    // automático. En local, si no hay DATABASE_URL, se arma la misma URL
    // a partir de las variables DB_* (ver src/Core/Database.php).
    'db' => [
        'url' => getenv('DATABASE_URL') ?: (getenv('POSTGRES_URL') ?: null),
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '5432',
        'name' => getenv('DB_NAME') ?: 'bienes_publicos',
        'user' => getenv('DB_USER') ?: 'postgres',
        'pass' => getenv('DB_PASS') ?: '',
    ],
    'loans' => [
        // Throttle (segundos) para re-sincronizar alertas temporales en carga del shell.
        'throttle_sync_segundos' => 300,
    ],
];
