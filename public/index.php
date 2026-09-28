<?php

if (PHP_SAPI === 'cli-server') {
    $urlPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if ($urlPath !== '/' && is_file(__DIR__ . $urlPath)) {
        return false;
    }
}

ob_start();

require __DIR__ . '/../src/Support/autoload.php';

use App\Core\Request;
use App\Core\Router;
use App\Core\Session;

$config = require __DIR__ . '/../src/Config/config.php';
date_default_timezone_set($config['app']['timezone']);

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
header('Cache-Control: no-store, private');
if ($config['app']['cookie_secure']) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

Session::start();

$router = new Router();
require __DIR__ . '/../routes/web.php';
require __DIR__ . '/../routes/api.php';

$router->dispatch(new Request());
