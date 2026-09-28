<?php

use App\Controllers\AssetController;
use App\Controllers\AuditController;
use App\Controllers\AuthController;
use App\Controllers\CatalogController;
use App\Controllers\DashboardController;
use App\Controllers\DocumentController;
use App\Controllers\LabelController;
use App\Controllers\LoanController;
use App\Controllers\ReportController;
use App\Controllers\SettingsController;
use App\Controllers\UserController;
use App\Controllers\VerificationController;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\PermissionMiddleware;

/** @var Router $router */

$router->get('/login', [new AuthController(), 'showLogin'], [[AuthMiddleware::class, 'guest']]);
$router->post('/login', [new AuthController(), 'login'], [[AuthMiddleware::class, 'guest']]);
$router->post('/logout', [new AuthController(), 'logout']);

$router->get('/', fn($r) => App\Core\Response::redirect('/dashboard'));
$router->get('/dashboard', [new DashboardController(), 'index'], [[AuthMiddleware::class, 'handle']]);

$router->get('/inventario', [new AssetController(), 'index'], [[AuthMiddleware::class, 'handle'], PermissionMiddleware::require('asset.view')]);
$router->get('/inventario/nuevo', [new AssetController(), 'create'], [[AuthMiddleware::class, 'handle'], PermissionMiddleware::require('asset.create')]);
$router->get('/inventario/{id}', [new AssetController(), 'show'], [[AuthMiddleware::class, 'handle'], PermissionMiddleware::require('asset.view')]);

$router->get('/prestamos', [new LoanController(), 'index'], [[AuthMiddleware::class, 'handle'], PermissionMiddleware::require('loan.view')]);
$router->get('/prestamos/{id}', [new LoanController(), 'show'], [[AuthMiddleware::class, 'handle'], PermissionMiddleware::require('loan.view')]);

$router->get('/catalogos/{table}', [new CatalogController(), 'page'], [[AuthMiddleware::class, 'handle'], PermissionMiddleware::require('catalog.manage')]);

$router->get('/usuarios', [new UserController(), 'index'], [[AuthMiddleware::class, 'handle'], PermissionMiddleware::require('user.manage')]);

$router->get('/verificacion', [new VerificationController(), 'index'], [[AuthMiddleware::class, 'handle'], PermissionMiddleware::require('verification.view')]);
$router->get('/verificacion/{id}', [new VerificationController(), 'show'], [[AuthMiddleware::class, 'handle'], PermissionMiddleware::require('verification.view')]);
$router->get('/verificacion/{id}/capturar', [new VerificationController(), 'capture'], [[AuthMiddleware::class, 'handle'], PermissionMiddleware::require('verification.manage')]);

$router->get('/reportes', [new ReportController(), 'index'], [[AuthMiddleware::class, 'handle'], PermissionMiddleware::require('report.generate')]);
$router->get('/reportes/preview', [new ReportController(), 'preview'], [[AuthMiddleware::class, 'handle'], PermissionMiddleware::require('report.generate')]);

$router->get('/etiquetas', [new LabelController(), 'index'], [[AuthMiddleware::class, 'handle'], PermissionMiddleware::require('report.generate')]);
$router->get('/etiquetas/imprimir', [new LabelController(), 'print'], [[AuthMiddleware::class, 'handle'], PermissionMiddleware::require('report.generate')]);

$router->get('/auditoria', [new AuditController(), 'index'], [[AuthMiddleware::class, 'handle'], PermissionMiddleware::require('audit.view')]);
$router->get('/configuracion', [new SettingsController(), 'index'], [[AuthMiddleware::class, 'handle'], PermissionMiddleware::require('system.configure')]);
$router->get('/configuracion/identidad-institucional', [new SettingsController(), 'institutionalIdentity'], [[AuthMiddleware::class, 'handle'], PermissionMiddleware::require('system.configure')]);
$router->get('/configuracion/prestamos-alertas', [new SettingsController(), 'loans'], [[AuthMiddleware::class, 'handle'], PermissionMiddleware::require('system.configure')]);
$router->get('/configuracion/seguridad-respaldo', [new SettingsController(), 'securityBackup'], [[AuthMiddleware::class, 'handle'], PermissionMiddleware::require('system.configure')]);

$router->get('/documentos/{id}/previsualizar', [new DocumentController(), 'preview'], [[AuthMiddleware::class, 'handle'], PermissionMiddleware::require('document.view')]);
$router->get('/documentos/{id}/descargar', [new DocumentController(), 'download'], [[AuthMiddleware::class, 'handle'], PermissionMiddleware::require('document.view')]);
