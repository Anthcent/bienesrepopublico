<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\PermissionService;

/**
 * Fábrica de middleware por permiso. Uso:
 *   PermissionMiddleware::require('asset.decommission')
 * en el arreglo de middlewares de una ruta.
 */
final class PermissionMiddleware
{
    public static function require(string $permissionCode): callable
    {
        return function (Request $request) use ($permissionCode): bool {
            $userId = Auth::id();
            $service = new PermissionService();

            if (!$userId || !$service->userHas($userId, $permissionCode)) {
                if ($request->wantsJson()) {
                    Response::error(403, 'No tiene permiso para realizar esta acción.');
                    return false;
                }
                http_response_code(403);
                View::render('errors/403', ['permission' => $permissionCode]);
                return false;
            }

            return true;
        };
    }
}
