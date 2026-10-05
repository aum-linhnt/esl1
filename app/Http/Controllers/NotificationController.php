<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Display the notification center.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $filter = $request->input('filter', 'all'); // 'all' or 'unread'
        $unreadOnly = ($filter === 'unread');

        $notifications = NotificationService::getNotifications($user, 20, $unreadOnly, true);
        $unreadCount = NotificationService::getUnreadCount($user);

        return view('notifications.index', compact('notifications', 'unreadCount', 'filter'));
    }

    /**
     * Mark single notification as read.
     */
    public function markAsRead(Request $request, $id)
    {
        NotificationService::markAsRead((int) $id, $request->user());

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Đã đánh dấu thông báo là đã đọc.');
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        NotificationService::markAllAsRead($request->user());

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Đã đánh dấu tất cả thông báo là đã đọc.');
    }

    /**
     * Delete a single notification.
     */
    public function destroy(Request $request, $id)
    {
        NotificationService::delete((int) $id, $request->user());

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Đã xóa thông báo.');
    }

    /**
     * Delete all notifications.
     */
    public function clearAll(Request $request)
    {
        NotificationService::clearAll($request->user());

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Đã xóa toàn bộ thông báo.');
    }
}
