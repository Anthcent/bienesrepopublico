<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Services\NotificationService;

final class NotificationController
{
    public function index(Request $request): void
    {
        $service = new NotificationService();
        $userId = Auth::id();
        Response::json([
            'ok' => true,
            'items' => $service->forUser($userId),
            'unread' => $service->unreadCount($userId),
        ]);
    }

    public function markRead(Request $request): void
    {
        $id = (int) $request->param('id');
        (new NotificationService())->markRead($id, Auth::id());
        Response::json(['ok' => true]);
    }

    public function markAllRead(Request $request): void
    {
        (new NotificationService())->markAllRead(Auth::id());
        Response::json(['ok' => true]);
    }

    public function resolve(Request $request): void
    {
        $id = (int) $request->param('id');
        (new NotificationService())->markResolved($id, Auth::id());
        Response::json(['ok' => true]);
    }
}
