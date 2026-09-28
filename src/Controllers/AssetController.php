<?php

namespace App\Controllers;

use App\Commands\CreateAssetCommand;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Repositories\CatalogRepository;
use App\Services\AssetMovementService;
use App\Services\AssetService;
use App\Services\DocumentService;

final class AssetController
{
    public function index(Request $request): void
    {
        $service = new AssetService();
        $catalogs = new CatalogRepository();
        $filters = [
            'q' => $request->input('q', ''),
            'estado_administrativo' => $request->input('estado_administrativo', ''),
            'disponibilidad' => $request->input('disponibilidad', ''),
            'physical_state_id' => $request->input('physical_state_id', ''),
            'location_id' => $request->input('location_id', ''),
            'responsible_id' => $request->input('responsible_id', ''),
        ];
        $page = max(1, (int) $request->input('page', 1));
        $result = $service->list($filters, $page, 20);

        View::render('assets/index', [
            'items' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => 20,
            'filters' => $filters,
            'counters' => $service->counters(),
            'locations' => $catalogs->all('locations'),
            'responsibles' => $catalogs->all('responsibles'),
            'physicalStates' => $catalogs->all('physical_states', false),
            'categories' => $catalogs->all('categories'),
            'brands' => $catalogs->all('brands'),
        ]);
    }

    public function create(Request $request): void
    {
        $catalogs = new CatalogRepository();

        View::render('assets/wizard', [
            'categories' => $catalogs->all('categories'),
            'brands' => $catalogs->all('brands'),
            'locations' => $catalogs->all('locations'),
            'responsibles' => $catalogs->all('responsibles'),
            'physicalStates' => $catalogs->all('physical_states', false),
        ]);
    }

    public function show(Request $request): void
    {
        $id = (int) $request->param('id');
        $service = new AssetService();
        $asset = $service->find($id);
        if (!$asset) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $movementService = new AssetMovementService();
        $documentService = new DocumentService();
        $catalogs = new CatalogRepository();
        $locations = $catalogs->all('locations', false);
        $responsibles = $catalogs->all('responsibles', false);
        $active = static fn (array $item): bool => !array_key_exists('activo', $item) || (bool) $item['activo'];
        $loanRepository = new \App\Repositories\LoanRepository();
        $loans = $loanRepository->forAsset($id);
        $activeLoan = null;
        foreach ($loans as $loan) {
            if ($loan['fecha_devolucion'] === null && in_array($loan['estado'], ['ACTIVO', 'PARCIALMENTE_DEVUELTO', 'VENCIDO'], true)) {
                $activeLoan = $loan;
                break;
            }
        }

        View::render('assets/show', [
            'asset' => $asset,
            'movements' => $movementService->history($id),
            'documents' => $documentService->forEntity('asset', $id),
            'history' => (new \App\Services\AuditService())->historyFor('asset', $id),
            'loans' => $loans,
            'activeLoan' => $activeLoan,
            'locations' => array_values(array_filter($locations, $active)),
            'responsibles' => array_values(array_filter($responsibles, $active)),
            'historyLookups' => [
                'location_id' => array_column($locations, 'nombre', 'id'),
                'responsible_id' => array_column($responsibles, 'nombre', 'id'),
                'physical_state_id' => array_column($catalogs->all('physical_states', false), 'nombre', 'id'),
                'category_id' => array_column($catalogs->all('categories', false), 'nombre', 'id'),
                'brand_id' => array_column($catalogs->all('brands', false), 'nombre', 'id'),
                'model_id' => array_column($catalogs->all('models', false), 'nombre', 'id'),
            ],
        ]);
    }

    // ---- API ----

    public function apiCheckNumber(Request $request): void
    {
        $numero = trim((string) $request->input('numero_bien', ''));
        $service = new AssetService();
        Response::json(['ok' => true, 'disponible' => $service->checkNumberAvailable($numero)]);
    }

    public function apiCheckSerial(Request $request): void
    {
        $serial = trim((string) $request->input('serial', ''));
        $service = new AssetService();
        Response::json(['ok' => true, 'disponible' => $service->checkSerialAvailable($serial)]);
    }

    public function apiCheckSimilarity(Request $request): void
    {
        $brandId = $request->input('brand_id') ? (int) $request->input('brand_id') : null;
        $modelId = $request->input('model_id') ? (int) $request->input('model_id') : null;
        $descripcion = trim((string) $request->input('descripcion', ''));

        $service = new AssetService();
        $similar = $service->findSimilar($brandId, $modelId, $descripcion);
        Response::json(['ok' => true, 'similar' => $similar]);
    }

    public function apiSearch(Request $request): void
    {
        $term = trim((string) $request->input('q', ''));
        if (mb_strlen($term) < 2) {
            Response::json(['ok' => true, 'items' => []]);
        }
        $repo = new \App\Repositories\AssetRepository();
        Response::json(['ok' => true, 'items' => $repo->search($term)]);
    }

    public function apiSearchAvailable(Request $request): void
    {
        $term = trim((string) $request->input('q', ''));
        $repo = new \App\Repositories\AssetRepository();
        Response::json(['ok' => true, 'items' => $repo->availableForLoan($term)]);
    }

    public function store(Request $request): void
    {
        $command = CreateAssetCommand::fromRequest($request);
        $service = new AssetService();
        $result = $service->incorporate($command, Auth::user());

        if (!$result->ok) {
            Response::json(['ok' => false, 'message' => $result->message, 'errors' => $result->errors], 422);
        }

        Response::json(['ok' => true, 'message' => $result->message, 'asset' => $result->data['asset']]);
    }

    public function update(Request $request): void
    {
        $id = (int) $request->param('id');
        $service = new AssetService();
        $result = $service->update($id, $request->all(), Auth::user());

        if (!$result->ok) {
            Response::json(['ok' => false, 'message' => $result->message, 'errors' => $result->errors], 422);
        }
        Response::json(['ok' => true, 'message' => $result->message, 'asset' => $result->data['asset']]);
    }
}
