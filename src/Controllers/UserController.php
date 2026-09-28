<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Repositories\UserRepository;
use App\Services\AuditService;
use App\Services\SecuritySettingsService;

final class UserController
{
    public function index(Request $request): void
    {
        $repo = new UserRepository();
        $filters = [
            'q' => $request->input('q', ''),
            'role_id' => $request->input('role_id', ''),
            'activo' => $request->input('activo', ''),
        ];
        $page = max(1, (int) $request->input('page', 1));
        $result = $repo->paginate($filters, $page, 20);

        View::render('users/index', [
            'items' => array_map([$this, 'publicUser'], $result['items']),
            'total' => $result['total'],
            'page' => $page,
            'perPage' => 20,
            'filters' => $filters,
            'roles' => $repo->allRoles(),
            'passwordMinLength' => (new SecuritySettingsService())->current()['password_min_length'],
        ]);
    }

    public function store(Request $request): void
    {
        $repo = new UserRepository();
        $email = mb_strtolower(trim((string) $request->input('email')));
        $password = (string) $request->input('password');
        $minLength = (new SecuritySettingsService())->current()['password_min_length'];
        $roleId = (int) $request->input('role_id');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::json(['ok' => false, 'message' => 'Ingresa un correo electrónico válido.'], 422);
        }
        if (mb_strlen($password) < $minLength) {
            Response::json(['ok' => false, 'message' => "La contraseña debe tener al menos {$minLength} caracteres."], 422);
        }
        if ($repo->roleCode($roleId) === null) {
            Response::json(['ok' => false, 'message' => 'Selecciona un rol válido.'], 422);
        }

        if ($repo->emailExists($email)) {
            Response::json(['ok' => false, 'message' => 'Ya existe un usuario con ese correo.'], 422);
        }

        $id = $repo->create([
            'role_id' => $roleId,
            'nombre' => trim((string) $request->input('nombre')),
            'cargo' => $request->input('cargo'),
            'email' => $email,
            'password' => $password,
        ]);

        (new AuditService())->record(Auth::id(), 'user.create', 'user', $id, sprintf('%s creó al usuario %s.', Auth::user()['nombre'], $email));

        Response::json(['ok' => true, 'user' => $this->publicUser($repo->findById($id))]);
    }

    public function update(Request $request): void
    {
        $id = (int) $request->param('id');
        $repo = new UserRepository();
        $email = mb_strtolower(trim((string) $request->input('email')));
        $roleId = (int) $request->input('role_id');
        $target = $repo->findById($id);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::json(['ok' => false, 'message' => 'Ingresa un correo electrónico válido.'], 422);
        }
        $roleCode = $repo->roleCode($roleId);
        if ($roleCode === null) {
            Response::json(['ok' => false, 'message' => 'Selecciona un rol válido.'], 422);
        }
        if ($id === Auth::id() && $roleCode !== 'ADMIN') {
            Response::json(['ok' => false, 'message' => 'No puedes retirar tu propio rol administrador.'], 422);
        }
        if ($target && $target['role_codigo'] === 'ADMIN' && $roleCode !== 'ADMIN' && $repo->countActiveAdmins() <= 1) {
            Response::json(['ok' => false, 'message' => 'Debe permanecer al menos un administrador activo.'], 422);
        }

        if ($repo->emailExists($email, $id)) {
            Response::json(['ok' => false, 'message' => 'Ya existe un usuario con ese correo.'], 422);
        }

        $repo->update($id, [
            'role_id' => $roleId,
            'nombre' => trim((string) $request->input('nombre')),
            'cargo' => $request->input('cargo'),
            'email' => $email,
        ]);

        (new AuditService())->record(Auth::id(), 'user.update', 'user', $id, sprintf('%s actualizó al usuario %s.', Auth::user()['nombre'], $email));

        Response::json(['ok' => true, 'user' => $this->publicUser($repo->findById($id))]);
    }

    public function toggleActive(Request $request): void
    {
        $id = (int) $request->param('id');
        $active = (bool) $request->input('activo', true);
        $repo = new UserRepository();
        $target = $repo->findById($id);
        if (!$active && $id === Auth::id()) {
            Response::json(['ok' => false, 'message' => 'No puedes desactivar tu propia cuenta.'], 422);
        }
        if (!$active && $target && $target['role_codigo'] === 'ADMIN' && $repo->countActiveAdmins() <= 1) {
            Response::json(['ok' => false, 'message' => 'Debe permanecer al menos un administrador activo.'], 422);
        }
        $repo->setActive($id, $active);

        (new AuditService())->record(Auth::id(), $active ? 'user.activate' : 'user.deactivate', 'user', $id, sprintf('%s cambió el estado del usuario #%d.', Auth::user()['nombre'], $id));

        Response::json(['ok' => true, 'user' => $this->publicUser($repo->findById($id))]);
    }

    /** Nunca debe salir el hash de contraseña en una respuesta JSON. */
    private function publicUser(?array $user): ?array
    {
        if ($user === null) {
            return null;
        }
        unset($user['password_hash']);
        return $user;
    }
}
