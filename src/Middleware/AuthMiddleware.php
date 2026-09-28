<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;

final class AuthMiddleware
{
    public static function handle(Request $request): bool
    {
        if (Auth::check()) {
            return true;
        }

        if ($request->wantsJson()) {
            Response::error(401, 'Sesión requerida.');
            return false;
        }

        Response::redirect('/login?next=' . urlencode($request->path));
        return false;
    }

    public static function guest(Request $request): bool
    {
        if (!Auth::check()) {
            return true;
        }
        Response::redirect('/dashboard');
        return false;
    }
}
