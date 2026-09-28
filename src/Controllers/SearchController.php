<?php

namespace App\Controllers;
use App\Core\Auth;

use App\Core\Request;
use App\Core\Response;
use App\Services\SearchService;
use App\Services\PermissionService;

final class SearchController
{
    public function global(Request $request): void
    {
        $term = trim((string) $request->input('q', ''));
        if (mb_strlen($term) < 2) {
            Response::json(['ok' => true, 'groups' => []]);
        }
        try {
            $includeUsers = (new PermissionService())->userHas((int) Auth::id(), 'user.manage');
            Response::json(['ok' => true, 'groups' => (new SearchService())->global($term, 5, $includeUsers)]);
        } catch (\Throwable $e) {
            error_log('SearchController::global failed: ' . $e->getMessage());
            Response::json(['ok' => false, 'groups' => []], 500);
        }
    }
}
