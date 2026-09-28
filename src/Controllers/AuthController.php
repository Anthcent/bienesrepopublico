<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\InstitutionalIdentityService;
use App\Services\AuditService;
use App\Services\LoginProtectionService;

final class AuthController
{
    public function showLogin(Request $request): void
    {
        View::render('auth/login', [
            'error' => Session::flash('login_error'),
            'next' => $request->input('next', '/dashboard'),
            'identity' => (new InstitutionalIdentityService())->current(),
        ], 'layout/auth');
    }

    public function login(Request $request): void
    {
        if (!Csrf::verify($request->input('_csrf'))) {
            Session::flash('login_error', 'Sesión de formulario expirada. Intenta nuevamente.');
            Response::redirect('/login');
        }

        $email = trim((string) $request->input('email'));
        $password = (string) $request->input('password');
        $protection = new LoginProtectionService();

        if ($protection->isBlocked($email)) {
            $protection->release($email);
            Session::flash('login_error', sprintf('Demasiados intentos. Espera %d minutos antes de volver a intentar.', $protection->lockoutMinutes()));
            Response::redirect('/login');
        }

        if (!Auth::attempt($email, $password)) {
            $blocked = $protection->registerFailure($email);
            if ($blocked) {
                (new AuditService())->record(null, 'auth.login.locked', 'authentication', 0, 'Se bloqueó temporalmente un origen por intentos fallidos de acceso.');
            }
            $protection->release($email);
            Session::flash('login_error', 'No fue posible iniciar sesión con esas credenciales.');
            Response::redirect('/login');
        }

        $protection->clear($email);
        $protection->release($email);
        (new AuditService())->record(Auth::id(), 'auth.login.success', 'user', (int) Auth::id(), sprintf('%s inició sesión.', Auth::user()['nombre']));

        $next = (string) $request->input('next', '/dashboard');
        $isLocalPath = str_starts_with($next, '/') && !str_starts_with($next, '//') && !str_contains($next, "\r") && !str_contains($next, "\n");
        Response::redirect($isLocalPath ? $next : '/dashboard');
    }

    public function logout(Request $request): void
    {
        $user = Auth::user();
        if ($user) {
            (new AuditService())->record((int) $user['id'], 'auth.logout', 'user', (int) $user['id'], sprintf('%s cerró sesión.', $user['nombre']));
        }
        Auth::logout();
        Response::redirect('/login');
    }
}
