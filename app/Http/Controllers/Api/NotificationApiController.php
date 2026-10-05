<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationApiController extends Controller
{
    /**
     * Get paginated notifications for current user.
     * Query params: unread_only (bool), per_page (int, default 15)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $unreadOnly = $request->boolean('unread_only', false);
        $perPage = min(50, max(5, (int) $request->input('per_page', 15)));

        $notifications = NotificationService::getNotifications($user, $perPage, $unreadOnly, true);
        $unreadCount = NotificationService::getUnreadCount($user);

        return response()->json([
            'success' => true,
            'unread_count' => $unreadCount,
            'data' => $notifications->items(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page'    => $notifications->lastPage(),
                'total'        => $notifications->total(),
                'per_page'     => $notifications->perPage(),
            ],
        ]);
    }

    /**
     * Get total unread count for badge indicators.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['unread_count' => 0]);
        }

        return response()->json([
            'success' => true,
            'unread_count' => NotificationService::getUnreadCount($user),
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $success = NotificationService::markAsRead((int) $id, $user);

        return response()->json([
            'success' => $success,
            'unread_count' => NotificationService::getUnreadCount($user),
            'message' => $success ? 'Đã đánh dấu đã đọc.' : 'Không tìm thấy thông báo.',
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $updatedCount = NotificationService::markAllAsRead($user);

        return response()->json([
            'success' => true,
            'updated_count' => $updatedCount,
            'unread_count' => 0,
            'message' => 'Đã đánh dấu tất cả thông báo là đã đọc.',
        ]);
    }

    /**
     * Delete a single notification.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $deleted = NotificationService::delete((int) $id, $user);

        return response()->json([
            'success' => $deleted,
            'unread_count' => NotificationService::getUnreadCount($user),
            'message' => $deleted ? 'Đã xóa thông báo.' : 'Không thể xóa thông báo.',
        ]);
    }

    /**
     * Clear all notifications for user.
     */
    public function clearAll(Request $request): JsonResponse
    {
        $user = $request->user();
        NotificationService::clearAll($user);

        return response()->json([
            'success' => true,
            'unread_count' => 0,
            'message' => 'Đã xóa toàn bộ thông báo.',
        ]);
    }

    /**
     * Send notification API (usable by Admin, Teachers, or automated Webhooks).
     */
    public function send(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || (!$user->isAdmin() && !$user->isTeacher())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized. Chỉ Admin hoặc Giáo viên mới có quyền gửi thông báo.'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'nullable|string|in:system,course,assignment,exam,grade,message,badge,announcement',
            'target' => 'required|string|in:user,users,role,course,broadcast',
            'user_id' => 'required_if:target,user|nullable|integer',
            'user_ids' => 'required_if:target,users|nullable|array',
            'role' => 'required_if:target,role|nullable|string',
            'course_id' => 'required_if:target,course|nullable|integer',
            'action_url' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:50',
        ]);

        $options = [
            'type' => $request->input('type', 'announcement'),
            'action_url' => $request->input('action_url'),
            'icon' => $request->input('icon', 'bell'),
            'sender_id' => $user->id,
        ];

        $target = $request->input('target');
        $title = $request->input('title');
        $body = $request->input('message');

        switch ($target) {
            case 'user':
                $notification = NotificationService::send((int) $request->input('user_id'), $title, $body, $options);
                $count = 1;
                break;

            case 'users':
                $count = NotificationService::sendToMany($request->input('user_ids', []), $title, $body, $options);
                break;

            case 'role':
                $count = NotificationService::sendToRole($request->input('role'), $title, $body, $options);
                break;

            case 'course':
                $count = NotificationService::sendToCourse((int) $request->input('course_id'), $title, $body, $options);
                break;

            case 'broadcast':
            default:
                $count = NotificationService::broadcast($title, $body, $options);
                break;
        }

        return response()->json([
            'success' => true,
            'sent_count' => $count,
            'message' => "Đã gửi thông báo thành công tới {$count} người dùng.",
        ]);
    }
}
