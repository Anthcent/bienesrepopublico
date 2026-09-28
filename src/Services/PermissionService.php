<?php

namespace App\Services;

use App\Repositories\PermissionRepository;

final class PermissionService
{
    private PermissionRepository $repository;
    private array $cache = [];

    public function __construct()
    {
        $this->repository = new PermissionRepository();
    }

    public function userHas(int $userId, string $permissionCode): bool
    {
        return in_array($permissionCode, $this->permissionsFor($userId), true);
    }

    public function permissionsFor(int $userId): array
    {
        if (!isset($this->cache[$userId])) {
            $this->cache[$userId] = $this->repository->permissionsForUser($userId);
        }
        return $this->cache[$userId];
    }
}
