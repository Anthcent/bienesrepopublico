<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Repositories\CatalogRepository;
use App\Services\ServiceResult;
use App\Services\VerificationService;

final class VerificationController
{
    public function index(Request $request): void
    {
        $service = new VerificationService();
        $filters = [
            'estado' => $request->input('estado', ''),
            'q' => $request->input('q', ''),
        ];
        $page = max(1, (int) $request->input('page', 1));
        $result = $service->list($filters, $page, 12);

        $countersByCampaign = [];
        foreach ($result['items'] as $campaign) {
            $countersByCampaign[$campaign['id']] = $service->counters((int) $campaign['id']);
        }

        $catalogs = new CatalogRepository();
        View::render('verification/index', [
            'items' => $result['items'],
            'countersByCampaign' => $countersByCampaign,
            'total' => $result['total'],
            'page' => $page,
            'perPage' => 12,
            'filters' => $filters,
            'locations' => $catalogs->all('locations'),
            'responsibles' => $catalogs->all('responsibles'),
            'categories' => $catalogs->all('categories'),
            'physicalStates' => $catalogs->all('physical_states', false),
        ]);
    }

    public function show(Request $request): void
    {
        $id = (int) $request->param('id');
        $service = new VerificationService();
        $campaign = $service->find($id);
        if (!$campaign) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        View::render('verification/show', [
            'campaign' => $campaign,
            'items' => $service->items($id),
            'counters' => $service->counters($id),
            'campos' => json_decode($campaign['campos_json'], true) ?: [],
            'documents' => (new \App\Services\DocumentService())->forEntity('verification_campaign', $id),
        ]);
    }

    public function capture(Request $request): void
    {
        $id = (int) $request->param('id');
        $service = new VerificationService();
        $campaign = $service->find($id);
        if (!$campaign) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $itemId = (int) $request->input('item', 0);
        $current = $itemId ? $service->findItem($itemId) : null;
        if (!$current || (int) $current['campaign_id'] !== $id) {
            $current = $service->nextPendingItem($id);
        }

        $catalogs = new CatalogRepository();
        View::render('verification/capture', [
            'campaign' => $campaign,
            'item' => $current,
            'items' => $service->items($id),
            'counters' => $service->counters($id),
            'campos' => json_decode($campaign['campos_json'], true) ?: [],
            'locations' => $catalogs->all('locations'),
            'responsibles' => $catalogs->all('responsibles'),
            'physicalStates' => $catalogs->all('physical_states', false),
        ]);
    }

    public function apiCandidates(Request $request): void
    {
        $filters = [
            'location_id' => $request->input('location_id', ''),
            'responsible_id' => $request->input('responsible_id', ''),
            'category_id' => $request->input('category_id', ''),
            'estado_administrativo' => $request->input('estado_administrativo', 'ACTIVO'),
            'disponibilidad' => $request->input('disponibilidad', ''),
            'informacion_completa' => $request->input('informacion_completa', ''),
            'q' => $request->input('q', ''),
        ];
        $service = new VerificationService();
        $items = $service->candidateAssets($filters);
        Response::json(['ok' => true, 'total' => count($items), 'items' => $items]);
    }

    public function apiCreate(Request $request): void
    {
        $service = new VerificationService();
        $this->respond($service->createCampaign($request->all(), Auth::user()));
    }

    public function apiComplete(Request $request): void
    {
        $id = (int) $request->param('id');
        $service = new VerificationService();
        $this->respond($service->completeCampaign(
            $id,
            Auth::user(),
            $request->input('observaciones') ?: null,
            (bool) $request->input('force', false)
        ));
    }

    public function apiCancel(Request $request): void
    {
        $id = (int) $request->param('id');
        $service = new VerificationService();
        $this->respond($service->cancelCampaign($id, Auth::user()));
    }

    public function apiCaptureNoChanges(Request $request): void
    {
        $itemId = (int) $request->param('id');
        $service = new VerificationService();
        $this->respond($service->captureNoChanges($itemId, Auth::user()));
    }

    public function apiCaptureChanges(Request $request): void
    {
        $itemId = (int) $request->param('id');
        $service = new VerificationService();
        $this->respond($service->captureChanges($itemId, $request->all(), Auth::user()));
    }

    public function apiCaptureNotFound(Request $request): void
    {
        $itemId = (int) $request->param('id');
        $service = new VerificationService();
        $this->respond($service->captureNotFound($itemId, Auth::user(), $request->input('observacion') ?: null));
    }

    private function respond(ServiceResult $result): void
    {
        if (!$result->ok) {
            Response::json(['ok' => false, 'message' => $result->message, 'errors' => $result->errors], 422);
        }
        Response::json(['ok' => true, 'message' => $result->message, 'data' => $result->data]);
    }
}
