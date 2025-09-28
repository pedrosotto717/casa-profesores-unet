<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Get notifications for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $notifications = $this->notificationService->getForUser($userId);

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'meta' => [
                'version' => 'v1',
                'unread_count' => $this->notificationService->getUnreadCount($userId),
            ],
        ]);
    }

    /**
     * Get unread notifications for the authenticated user.
     */
    public function unread(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $notifications = $this->notificationService->getUnreadForUser($userId);

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'meta' => [
                'version' => 'v1',
                'count' => $notifications->count(),
            ],
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $userId = $request->user()->id;
        $success = $this->notificationService->markAsRead($id, $userId);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes acceso a esta notificación.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notificación marcada como leída.',
            'meta' => [
                'version' => 'v1',
            ],
        ]);
    }

    /**
     * Mark all notifications as read for the authenticated user.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $count = $this->notificationService->markAllAsRead($userId);

        return response()->json([
            'success' => true,
            'message' => "Se marcaron {$count} notificaciones como leídas.",
            'meta' => [
                'version' => 'v1',
                'marked_count' => $count,
            ],
        ]);
    }

    /**
     * Get notification count for the authenticated user.
     */
    public function count(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $count = $this->notificationService->getUnreadCount($userId);

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $count,
            ],
            'meta' => [
                'version' => 'v1',
            ],
        ]);
    }
}
