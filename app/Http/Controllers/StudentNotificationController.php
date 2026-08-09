<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentNotificationController extends Controller
{
    public function unread(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isStudent(), 403);

        $notifications = $request->user()
            ->unreadNotifications()
            ->latest()
            ->limit(10)
            ->get();

        $payload = $notifications
            ->map(fn ($notification) => [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? 'Notification',
                'message' => $notification->data['message'] ?? '',
                'url' => $notification->data['url'] ?? route('student.dashboard'),
                'created_at' => $notification->created_at?->diffForHumans(),
            ])
            ->values();

        $notifications->each->markAsRead();

        return response()->json([
            'notifications' => $payload,
        ]);
    }
}
