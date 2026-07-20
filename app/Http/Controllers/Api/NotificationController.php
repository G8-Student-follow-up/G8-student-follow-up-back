<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get paginated notifications for the authenticated user.
     *
     * GET /notifications?page=1&per_page=20
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $perPage = min((int) $request->per_page, 50) ?: 20;

        $query = Notification::forUser($user->id);

        // Optional type filter
        if ($type = $request->type) {
            $query->byType($type);
        }

        $notifications = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $unreadCount = Notification::forUser($user->id)->unread()->count();

        return response()->json([
            'notifications' => $notifications->items(),
            'unread_count' => $unreadCount,
            'total' => $notifications->total(),
            'last_page' => $notifications->lastPage(),
        ]);
    }

    /**
     * Mark a single notification as read.
     *
     * POST /notifications/{id}/read
     */
    public function markAsRead(Request $request, Notification $notification)
    {
        $user = $request->user();

        // Ensure the notification belongs to the authenticated user
        if ((int) $notification->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read']);
    }

    /**
     * Mark all notifications as read for the authenticated user.
     *
     * POST /notifications/read-all
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();

        Notification::forUser($user->id)
            ->unread()
            ->update(['is_read' => true]);

        return response()->json(['message' => 'All notifications marked as read']);
    }
}
