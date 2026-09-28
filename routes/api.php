<?php

use App\Controllers\AssetController;
use App\Controllers\CatalogController;
use App\Controllers\LoanController;
use App\Controllers\MovementController;
use App\Controllers\NotificationController;
use App\Controllers\ReportController;
use App\Controllers\SearchController;
use App\Controllers\SettingsController;
use App\Controllers\UserController;
use App\Controllers\VerificationController;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\PermissionMiddleware;

/** @var Router $router */
$auth = [AuthMiddleware::class, 'handle'];

// Búsqueda global (M13)
$router->get('/api/search', [new SearchController(), 'global'], [$auth]);

// Bienes
$router->get('/api/assets/search', [new AssetController(), 'apiSearch'], [$auth]);
$router->get('/api/assets/search-available', [new AssetController(), 'apiSearchAvailable'], [$auth]);
$router->get('/api/assets/check-number', [new AssetController(), 'apiCheckNumber'], [$auth]);
$router->get('/api/assets/check-serial', [new AssetController(), 'apiCheckSerial'], [$auth]);
$router->post('/api/assets/check-similarity', [new AssetController(), 'apiCheckSimilarity'], [$auth]);
$router->post('/api/assets', [new AssetController(), 'store'], [$auth, PermissionMiddleware::require('asset.create')]);
$router->put('/api/assets/{id}', [new AssetController(), 'update'], [$auth, PermissionMiddleware::require('asset.edit')]);

// Movimientos administrativos
$router->post('/api/assets/{id}/reasignar', [new MovementController(), 'reassign'], [$auth, PermissionMiddleware::require('asset.reassign')]);
$router->post('/api/assets/{id}/desincorporar', [new MovementController(), 'decommission'], [$auth, PermissionMiddleware::require('asset.decommission')]);
$router->post('/api/assets/{id}/readmitir', [new MovementController(), 'readmit'], [$auth, PermissionMiddleware::require('asset.readmit')]);

// Catálogos
$router->get('/api/catalogs/{table}/search', [new CatalogController(), 'apiSearch'], [$auth]);
$router->get('/api/catalogs/brands/{brandId}/models', [new CatalogController(), 'apiModelsByBrand'], [$auth]);
$router->post('/api/catalogs/{table}', [new CatalogController(), 'store'], [$auth, PermissionMiddleware::require('catalog.manage')]);
$router->put('/api/catalogs/{table}/{id}', [new CatalogController(), 'update'], [$auth, PermissionMiddleware::require('catalog.manage')]);
$router->post('/api/catalogs/{table}/{id}/toggle', [new CatalogController(), 'toggle'], [$auth, PermissionMiddleware::require('catalog.manage')]);

// Préstamos
$router->post('/api/loans', [new LoanController(), 'store'], [$auth, PermissionMiddleware::require('loan.create')]);
$router->post('/api/loans/{id}/return', [new LoanController(), 'returnLoan'], [$auth, PermissionMiddleware::require('loan.return')]);
$router->post('/api/loans/{id}/extend', [new LoanController(), 'extend'], [$auth, PermissionMiddleware::require('loan.extend')]);
$router->post('/api/loans/{id}/cancel', [new LoanController(), 'cancel'], [$auth, PermissionMiddleware::require('loan.cancel')]);

// Notificaciones
$router->get('/api/notifications', [new NotificationController(), 'index'], [$auth]);
$router->post('/api/notifications/{id}/read', [new NotificationController(), 'markRead'], [$auth]);
$router->post('/api/notifications/read-all', [new NotificationController(), 'markAllRead'], [$auth]);
$router->post('/api/notifications/{id}/resolve', [new NotificationController(), 'resolve'], [$auth]);

// Reportes
$router->get('/api/reports/estimate', [new ReportController(), 'apiEstimate'], [$auth, PermissionMiddleware::require('report.generate')]);
$router->get('/api/reports/rows', [new ReportController(), 'apiRows'], [$auth, PermissionMiddleware::require('report.generate')]);

// Jornadas de verificación patrimonial
$router->get('/api/verification/candidates', [new VerificationController(), 'apiCandidates'], [$auth, PermissionMiddleware::require('verification.manage')]);
$router->post('/api/verification', [new VerificationController(), 'apiCreate'], [$auth, PermissionMiddleware::require('verification.manage')]);
$router->post('/api/verification/{id}/complete', [new VerificationController(), 'apiComplete'], [$auth, PermissionMiddleware::require('verification.manage')]);
$router->post('/api/verification/{id}/cancel', [new VerificationController(), 'apiCancel'], [$auth, PermissionMiddleware::require('verification.manage')]);
$router->post('/api/verification/items/{id}/no-changes', [new VerificationController(), 'apiCaptureNoChanges'], [$auth, PermissionMiddleware::require('verification.manage')]);
$router->post('/api/verification/items/{id}/changes', [new VerificationController(), 'apiCaptureChanges'], [$auth, PermissionMiddleware::require('verification.manage')]);
$router->post('/api/verification/items/{id}/not-found', [new VerificationController(), 'apiCaptureNotFound'], [$auth, PermissionMiddleware::require('verification.manage')]);

// Usuarios
$router->post('/api/users', [new UserController(), 'store'], [$auth, PermissionMiddleware::require('user.manage')]);
$router->put('/api/users/{id}', [new UserController(), 'update'], [$auth, PermissionMiddleware::require('user.manage')]);
$router->post('/api/users/{id}/toggle', [new UserController(), 'toggleActive'], [$auth, PermissionMiddleware::require('user.manage')]);

// Configuración del sistema
$router->put('/api/settings/institutional-identity', [new SettingsController(), 'updateInstitutionalIdentity'], [$auth, [CsrfMiddleware::class, 'handle'], PermissionMiddleware::require('system.configure')]);
$router->put('/api/settings/loans', [new SettingsController(), 'updateLoans'], [$auth, [CsrfMiddleware::class, 'handle'], PermissionMiddleware::require('system.configure')]);
$router->put('/api/settings/security', [new SettingsController(), 'updateSecurity'], [$auth, [CsrfMiddleware::class, 'handle'], PermissionMiddleware::require('system.configure')]);
