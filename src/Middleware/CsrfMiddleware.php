<?php

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;

final class CsrfMiddleware
{
    public static function handle(Request $request): bool
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $request->input('_csrf');
        if (!Csrf::verify(is_string($token) ? $token : null)) {
            Response::error(419, 'La sesión del formulario expiró. Actualiza la página e intenta nuevamente.');
            return false;
        }

        return true;
    }
}
