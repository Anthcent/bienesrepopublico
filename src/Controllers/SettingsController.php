<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\InstitutionalIdentityService;
use App\Services\LoanSettingsService;
use App\Services\BackupStatusService;
use App\Services\SecuritySettingsService;

final class SettingsController
{
    public function index(Request $request): void
    {
        View::render('settings/index');
    }

    public function institutionalIdentity(Request $request): void
    {
        View::render('settings/institutional_identity', [
            'identity' => (new InstitutionalIdentityService())->current(),
        ]);
    }

    public function updateInstitutionalIdentity(Request $request): void
    {
        $result = (new InstitutionalIdentityService())->update($request->body, (int) Auth::id());
        if (!$result->ok) {
            Response::json([
                'ok' => false,
                'message' => $result->message,
                'errors' => $result->errors,
            ], 422);
        }

        Response::json(['ok' => true, 'message' => $result->message] + $result->data);
    }

    public function loans(Request $request): void
    {
        View::render('settings/loans', [
            'settings' => (new LoanSettingsService())->current(),
        ]);
    }

    public function updateLoans(Request $request): void
    {
        $result = (new LoanSettingsService())->update($request->body, (int) Auth::id());
        if (!$result->ok) {
            Response::json([
                'ok' => false,
                'message' => $result->message,
                'errors' => $result->errors,
            ], 422);
        }

        Response::json(['ok' => true, 'message' => $result->message] + $result->data);
    }

    public function securityBackup(Request $request): void
    {
        $settings = (new SecuritySettingsService())->current();
        View::render('settings/security_backup', [
            'settings' => $settings,
            'backupStatus' => (new BackupStatusService())->status($settings['backup_warning_hours']),
        ]);
    }

    public function updateSecurity(Request $request): void
    {
        $result = (new SecuritySettingsService())->update($request->body, (int) Auth::id());
        if (!$result->ok) {
            Response::json([
                'ok' => false,
                'message' => $result->message,
                'errors' => $result->errors,
            ], 422);
        }
        Response::json(['ok' => true, 'message' => $result->message] + $result->data);
    }
}
