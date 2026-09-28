<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Repositories\CatalogRepository;
use App\Services\AuditService;

/**
 * Controlador genérico para catálogos (M04). Cada submódulo (ubicaciones,
 * responsables, categorías, marcas, estados físicos) comparte la misma
 * ruta parametrizada por `{table}`, validada contra una lista blanca.
 */
final class CatalogController
{
    private const ALLOWED = ['locations', 'responsibles', 'categories', 'brands', 'models', 'physical_states', 'movement_types'];

    public function page(Request $request): void
    {
        $table = $request->param('table');
        if (!in_array($table, self::ALLOWED, true)) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $repo = new CatalogRepository();
        $paginated = !in_array($table, ['physical_states', 'movement_types'], true);
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 20;

        if ($paginated) {
            $result = $repo->paginate($table, $page, $perPage);
            $items = $result['items'];
            $total = $result['total'];
        } else {
            $items = $repo->all($table);
            $total = count($items);
        }

        $stats = [];
        if ($table === 'locations') {
            foreach ($items as $item) {
                $stats[$item['id']] = $repo->locationStats((int) $item['id']);
            }
        } elseif ($table === 'responsibles') {
            foreach ($items as $item) {
                $stats[$item['id']] = $repo->responsibleStats((int) $item['id']);
            }
        }

        View::render('catalogs/' . $table, [
            'items' => $items,
            'stats' => $stats,
            'locations' => $table === 'responsibles' ? $repo->all('locations') : [],
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'paginated' => $paginated,
        ]);
    }

    public function apiSearch(Request $request): void
    {
        $table = $request->param('table');
        if (!in_array($table, self::ALLOWED, true)) {
            Response::error(404, 'Catálogo no soportado.');
        }
        $term = trim((string) $request->input('q', ''));
        $repo = new CatalogRepository();
        Response::json(['ok' => true, 'items' => $repo->search($table, $term)]);
    }

    public function apiModelsByBrand(Request $request): void
    {
        $brandId = (int) $request->param('brandId');
        $repo = new CatalogRepository();
        Response::json(['ok' => true, 'items' => $repo->modelsByBrand($brandId)]);
    }

    public function store(Request $request): void
    {
        $table = $request->param('table');
        if (!in_array($table, self::ALLOWED, true)) {
            Response::error(404, 'Catálogo no soportado.');
        }
        $repo = new CatalogRepository();
        $id = $repo->create($table, $request->all());

        (new AuditService())->record(
            Auth::id(), 'catalog.create', $table, $id,
            sprintf('%s creó un registro en el catálogo %s.', Auth::user()['nombre'], $table)
        );

        Response::json(['ok' => true, 'item' => $repo->find($table, $id)]);
    }

    public function update(Request $request): void
    {
        $table = $request->param('table');
        $id = (int) $request->param('id');
        if (!in_array($table, self::ALLOWED, true)) {
            Response::error(404, 'Catálogo no soportado.');
        }
        $repo = new CatalogRepository();
        $repo->update($table, $id, $request->all());

        (new AuditService())->record(
            Auth::id(), 'catalog.update', $table, $id,
            sprintf('%s actualizó un registro del catálogo %s.', Auth::user()['nombre'], $table)
        );

        Response::json(['ok' => true, 'item' => $repo->find($table, $id)]);
    }

    public function toggle(Request $request): void
    {
        $table = $request->param('table');
        $id = (int) $request->param('id');
        if (!in_array($table, self::ALLOWED, true)) {
            Response::error(404, 'Catálogo no soportado.');
        }
        $active = (bool) $request->input('activo', true);
        $repo = new CatalogRepository();
        try {
            $repo->setActive($table, $id, $active);
        } catch (\InvalidArgumentException $e) {
            Response::error(422, $e->getMessage());
        }
        Response::json(['ok' => true, 'item' => $repo->find($table, $id)]);
    }
}
