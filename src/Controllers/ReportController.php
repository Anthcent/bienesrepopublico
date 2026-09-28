<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Repositories\CatalogRepository;
use App\Services\ReportService;
use App\Services\InstitutionalIdentityService;

final class ReportController
{
    public function index(Request $request): void
    {
        $catalogs = new CatalogRepository();
        View::render('reports/index', [
            'templates' => ReportService::TEMPLATES,
            'locations' => $catalogs->all('locations'),
            'responsibles' => $catalogs->all('responsibles'),
            'physicalStates' => $catalogs->all('physical_states', false),
            'customColumnGroups' => [
                'Identificación' => ['numero_bien' => 'Número de bien', 'serial' => 'Serial', 'descripcion' => 'Descripción'],
                'Clasificación' => ['categoria_nombre' => 'Categoría', 'marca_nombre' => 'Marca', 'modelo_nombre' => 'Modelo', 'color' => 'Color', 'material' => 'Material'],
                'Ubicación y custodia' => ['ubicacion_nombre' => 'Ubicación', 'responsable_nombre' => 'Responsable'],
                'Estado' => ['estado_administrativo' => 'Estado administrativo', 'disponibilidad' => 'Disponibilidad', 'estado_fisico_nombre' => 'Estado físico'],
                'Fechas' => ['created_at' => 'Fecha de incorporación'],
            ],
        ]);
    }

    public function apiEstimate(Request $request): void
    {
        $template = (string) $request->input('template');
        $filters = $request->all();
        Response::json(['ok' => true, 'estimate' => (new ReportService())->estimate($template, $filters)]);
    }

    /** Filas de muestra para la pestaña "Datos" del workspace de reportes (sin generar el documento completo). */
    public function apiRows(Request $request): void
    {
        $template = (string) $request->input('template');
        $filters = $request->all();
        $rows = (new ReportService())->rows($template, $filters);
        Response::json(['ok' => true, 'rows' => array_slice($rows, 0, 50), 'total' => count($rows)]);
    }

    public function preview(Request $request): void
    {
        $template = (string) $request->input('template');
        $filters = $request->all();
        $service = new ReportService();

        View::render('reports/preview', [
            'template' => $template,
            'templateName' => ReportService::TEMPLATES[$template] ?? $template,
            'rows' => $service->rows($template, $filters),
            'fecha' => date('d/m/Y H:i'),
            'identity' => (new InstitutionalIdentityService())->current(),
        ], 'layout/print');
    }
}
