<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Retrieve database notifications for the authenticated employee.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->karyawan_id) {
            return ApiResponse::format(false, 401, 'Unauthorized', null);
        }

        $notifications = $user->notifications()
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => data_get($notification->data, 'title'),
                    'message' => data_get($notification->data, 'body') ?? data_get($notification->data, 'message'),
                    'status' => data_get($notification->data, 'status'),
                    'type' => data_get($notification->data, 'type'),
                    'data' => $notification->data,
                    'is_read' => $notification->read_at !== null,
                    'read_at' => optional($notification->read_at)->toDateTimeString(),
                    'created_at' => optional($notification->created_at)->toDateTimeString(),
                ];
            });

        return ApiResponse::format(true, 200, 'Notifikasi berhasil diambil.', [
            'total' => $notifications->count(),
            'unread' => $user->unreadNotifications()->count(),
            'items' => $notifications,
        ]);
    }

    /**
     * Mark all notifications as read for the authenticated employee.
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->karyawan_id) {
            return ApiResponse::format(false, 401, 'Unauthorized', null);
        }

        $updated = $user->unreadNotifications()->update([
            'read_at' => now(),
        ]);

        return ApiResponse::format(true, 200, 'Seluruh notifikasi telah ditandai sebagai dibaca.', [
            'updated' => $updated,
        ]);
    }
}
