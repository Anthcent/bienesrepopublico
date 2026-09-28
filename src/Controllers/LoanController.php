<?php

namespace App\Controllers;

use App\Commands\CreateLoanCommand;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\LoanService;
use App\Services\LoanSettingsService;
use App\Services\NotificationService;
use App\Services\ServiceResult;

final class LoanController
{
    public function index(Request $request): void
    {
        $settings = (new LoanSettingsService())->current();
        (new NotificationService())->syncLoanAlertsThrottled($settings['due_alert_days'], 0);
        $service = new LoanService();
        $service->markOverdue();

        $filters = [
            'estado' => $request->input('estado', ''),
            'q' => $request->input('q', ''),
            'desde' => $request->input('desde', ''),
            'hasta' => $request->input('hasta', ''),
        ];
        $page = max(1, (int) $request->input('page', 1));
        $result = $service->list($filters, $page, 20);

        View::render('loans/index', [
            'items' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => 20,
            'filters' => $filters,
            'counters' => $service->counters(),
        ]);
    }

    public function show(Request $request): void
    {
        $id = (int) $request->param('id');
        $service = new LoanService();
        $loan = $service->find($id);
        if (!$loan) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        View::render('loans/show', [
            'loan' => $loan,
            'extensions' => (new \App\Repositories\LoanRepository())->extensions($id),
            'documents' => (new \App\Services\DocumentService())->forEntity('loan', $id),
            'physicalStates' => (new \App\Repositories\CatalogRepository())->all('physical_states', false),
            'loanSettings' => (new LoanSettingsService())->current(),
        ]);
    }

    private function respond(ServiceResult $result): void
    {
        if (!$result->ok) {
            Response::json(['ok' => false, 'message' => $result->message, 'errors' => $result->errors], 422);
        }
        Response::json(['ok' => true, 'message' => $result->message, 'data' => $result->data]);
    }

    public function store(Request $request): void
    {
        $command = CreateLoanCommand::fromRequest($request);
        $service = new LoanService();
        $this->respond($service->create($command, Auth::user()));
    }

    public function returnLoan(Request $request): void
    {
        $id = (int) $request->param('id');
        $returns = $request->input('returns', []);
        $service = new LoanService();
        $this->respond($service->processReturn($id, $returns, Auth::user()));
    }

    public function extend(Request $request): void
    {
        $id = (int) $request->param('id');
        $newDate = (string) $request->input('fecha_nueva');
        $motivo = $request->input('motivo');
        $service = new LoanService();
        $this->respond($service->extend($id, $newDate, $motivo, Auth::user()));
    }

    public function cancel(Request $request): void
    {
        $id = (int) $request->param('id');
        $service = new LoanService();
        $this->respond($service->cancel($id, Auth::user()));
    }
}
