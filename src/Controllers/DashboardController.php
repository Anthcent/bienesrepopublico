<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\View;
use App\Services\AssetMovementService;
use App\Services\AssetService;
use App\Services\LoanService;
use App\Services\LoanSettingsService;
use App\Services\NotificationService;

final class DashboardController
{
    public function index(Request $request): void
    {
        $config = require __DIR__ . '/../Config/config.php';
        $loanSettings = (new LoanSettingsService())->current();
        $actor = Auth::user();

        $notificationService = new NotificationService();
        $notificationService->syncLoanAlertsThrottled(
            $loanSettings['due_alert_days'],
            $config['loans']['throttle_sync_segundos']
        );
        (new LoanService())->markOverdue();

        $assetService = new AssetService();
        $loanService = new LoanService();
        $movementService = new AssetMovementService();

        View::render('dashboard/index', [
            'actor' => $actor,
            'assetCounters' => $assetService->counters(),
            'loanCounters' => $loanService->counters(),
            'dueSoonLoans' => $loanService->dueSoon($loanSettings['due_alert_days']),
            'recentAssets' => $assetService->list([], 1, 6)['items'],
            'recentMovements' => $movementService->recent(6),
            'notifications' => $notificationService->forUser((int) $actor['id']),
        ]);
    }
}
