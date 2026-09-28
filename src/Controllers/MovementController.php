<?php

namespace App\Controllers;

use App\Commands\DecommissionCommand;
use App\Commands\ReassignCommand;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Services\AssetMovementService;

final class MovementController
{
    private function renderResult(\App\Services\ServiceResult $result): void
    {
        if (!$result->ok) {
            Response::json(['ok' => false, 'message' => $result->message, 'errors' => $result->errors], 422);
        }
        Response::json(['ok' => true, 'message' => $result->message, 'data' => $result->data]);
    }

    public function reassign(Request $request): void
    {
        $id = (int) $request->param('id');
        $command = ReassignCommand::fromRequest($request);
        $service = new AssetMovementService();
        $result = $service->reassign($id, $command, Auth::user());
        $this->renderResult($result);
    }

    public function decommission(Request $request): void
    {
        $id = (int) $request->param('id');
        $command = DecommissionCommand::fromRequest($request);
        $service = new AssetMovementService();
        $result = $service->decommission($id, $command, Auth::user());
        $this->renderResult($result);
    }

    public function readmit(Request $request): void
    {
        $id = (int) $request->param('id');
        $motivo = $request->input('motivo');
        $service = new AssetMovementService();
        $result = $service->readmit($id, Auth::user(), $motivo);
        $this->renderResult($result);
    }
}
