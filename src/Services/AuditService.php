<?php

namespace App\Services;

use App\Repositories\AuditRepository;

final class AuditService
{
    private AuditRepository $repository;

    public function __construct()
    {
        $this->repository = new AuditRepository();
    }

    public function record(?int $userId, string $accion, string $entidadTipo, int $entidadId, string $resumenHumano, ?array $antes = null, ?array $despues = null): void
    {
        $this->repository->log([
            'usuario_id' => $userId,
            'accion' => $accion,
            'entidad_tipo' => $entidadTipo,
            'entidad_id' => $entidadId,
            'resumen_humano' => $resumenHumano,
            'antes' => $antes,
            'despues' => $despues,
        ]);
    }

    public function historyFor(string $entidadTipo, int $entidadId): array
    {
        return $this->repository->forEntity($entidadTipo, $entidadId);
    }

    public function paginate(array $filters, int $page, int $perPage): array
    {
        return $this->repository->paginate($filters, $page, $perPage);
    }

    public function filterOptions(): array
    {
        return $this->repository->filterOptions();
    }
}
