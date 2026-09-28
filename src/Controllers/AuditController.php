<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\View;
use App\Services\AuditService;

final class AuditController
{
    public function index(Request $request): void
    {
        $date = static function (mixed $value): string {
            $value = trim((string) $value);
            $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
            return $parsed && $parsed->format('Y-m-d') === $value ? $value : '';
        };

        $filters = [
            'q' => trim((string) $request->input('q', '')),
            'accion' => trim((string) $request->input('accion', '')),
            'entidad_tipo' => trim((string) $request->input('entidad_tipo', '')),
            'usuario_id' => trim((string) $request->input('usuario_id', '')),
            'desde' => $date($request->input('desde', '')),
            'hasta' => $date($request->input('hasta', '')),
        ];
        $page = max(1, (int) $request->input('page', 1));
        $service = new AuditService();
        $result = $service->paginate($filters, $page, 30);

        View::render('audit/index', [
            'items' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => 30,
            'filters' => $filters,
            'filterOptions' => $service->filterOptions(),
        ]);
    }
}
