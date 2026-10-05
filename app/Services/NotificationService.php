<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class NotificationService
{
    /**
     * Send a notification to a single user (instance method).
     */
    public function sendNotification(
        int|User $recipient,
        string $title,
        string $message,
        array $options = []
    ): Notification {
        $userId = $recipient instanceof User ? $recipient->id : (int) $recipient;

        $senderId = null;
        if (isset($options['sender'])) {
            $senderId = $options['sender'] instanceof User ? $options['sender']->id : (int) $options['sender'];
        } elseif (isset($options['sender_id'])) {
            $senderId = (int) $options['sender_id'];
        }

        return Notification::create([
            'user_id'    => $userId,
            'sender_id'  => $senderId,
            'type'       => $options['type'] ?? 'system',
            'title'      => $title,
            'message'    => $message,
            'action_url' => $options['action_url'] ?? null,
            'icon'       => $options['icon'] ?? 'bell',
            'data'       => $options['data'] ?? null,
            'is_read'    => false,
        ]);
    }

    /**
     * Send a notification to a collection or array of users/user IDs (instance method).
     */
    public function sendToManyRecipients(
        iterable $recipients,
        string $title,
        string $message,
        array $options = []
    ): int {
        $now = now();
        $senderId = null;
        if (isset($options['sender'])) {
            $senderId = $options['sender'] instanceof User ? $options['sender']->id : (int) $options['sender'];
        } elseif (isset($options['sender_id'])) {
            $senderId = (int) $options['sender_id'];
        }

        $type = $options['type'] ?? 'system';
        $actionUrl = $options['action_url'] ?? null;
        $icon = $options['icon'] ?? 'bell';
        $dataJson = isset($options['data']) ? json_encode($options['data']) : null;

        $records = [];
        foreach ($recipients as $recipient) {
            $userId = $recipient instanceof User ? $recipient->id : (int) $recipient;
            $records[] = [
                'user_id'    => $userId,
                'sender_id'  => $senderId,
                'type'       => $type,
                'title'      => $title,
                'message'    => $message,
                'action_url' => $actionUrl,
                'icon'       => $icon,
                'data'       => $dataJson,
                'is_read'    => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (empty($records)) {
            return 0;
        }

        foreach (array_chunk($records, 500) as $chunk) {
            Notification::insert($chunk);
        }

        return count($records);
    }

    /**
     * Broadcast a notification to all active users (instance method).
     */
    public function broadcastNotification(string $title, string $message, array $options = []): int
    {
        $userIds = User::where('status', 'active')->pluck('id');
        return $this->sendToManyRecipients($userIds, $title, $message, array_merge(['type' => 'announcement'], $options));
    }

    /**
     * Send notification to all users matching a specific role (instance method).
     */
    public function sendToRoleRecipients(string $role, string $title, string $message, array $options = []): int
    {
        $userIds = User::where('role', $role)->where('status', 'active')->pluck('id');
        return $this->sendToManyRecipients($userIds, $title, $message, $options);
    }

    /**
     * Send notification to all enrolled students of a course (instance method).
     */
    public function sendToCourseRecipients(int $courseId, string $title, string $message, array $options = []): int
    {
        $userIds = Enrollment::where('course_id', $courseId)
            ->whereIn('status', ['active', 'completed'])
            ->pluck('user_id');

        return $this->sendToManyRecipients($userIds, $title, $message, array_merge(['type' => 'course'], $options));
    }

    /**
     * Get unread notifications count for a user (instance method).
     */
    public function countUnread(int|User $user): int
    {
        $userId = $user instanceof User ? $user->id : (int) $user;
        return Notification::where('user_id', $userId)->where('is_read', false)->count();
    }

    /**
     * Get paginated or collection of notifications for a user (instance method).
     */
    public function fetchNotifications(
        int|User $user,
        int $limit = 20,
        bool $unreadOnly = false,
        bool $paginate = true
    ): Collection|LengthAwarePaginator {
        $userId = $user instanceof User ? $user->id : (int) $user;

        $query = Notification::with('sender:id,name,avatar,role')
            ->where('user_id', $userId)
            ->orderByDesc('created_at');

        if ($unreadOnly) {
            $query->where('is_read', false);
        }

        return $paginate ? $query->paginate($limit) : $query->take($limit)->get();
    }

    /**
     * Mark a single notification as read (instance method).
     */
    public function markSingleAsRead(int $notificationId, int|User $user): bool
    {
        $userId = $user instanceof User ? $user->id : (int) $user;

        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->first();

        if (!$notification) {
            return false;
        }

        return $notification->markAsRead();
    }

    /**
     * Mark all notifications of a user as read (instance method).
     */
    public function markAllRead(int|User $user): int
    {
        $userId = $user instanceof User ? $user->id : (int) $user;

        return Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Delete a single notification (instance method).
     */
    public function deleteNotification(int $notificationId, int|User $user): bool
    {
        $userId = $user instanceof User ? $user->id : (int) $user;

        return (bool) Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->delete();
    }

    /**
     * Delete all notifications of a user (instance method).
     */
    public function clearAllNotifications(int|User $user): int
    {
        $userId = $user instanceof User ? $user->id : (int) $user;

        return Notification::where('user_id', $userId)->delete();
    }

    // ─── Static Facade API (for DI and static callers) ───

    public static function send(int|User $recipient, string $title, string $message, array $options = []): Notification
    {
        return app(self::class)->sendNotification($recipient, $title, $message, $options);
    }

    public static function sendToMany(iterable $recipients, string $title, string $message, array $options = []): int
    {
        return app(self::class)->sendToManyRecipients($recipients, $title, $message, $options);
    }

    public static function broadcast(string $title, string $message, array $options = []): int
    {
        return app(self::class)->broadcastNotification($title, $message, $options);
    }

    public static function sendToRole(string $role, string $title, string $message, array $options = []): int
    {
        return app(self::class)->sendToRoleRecipients($role, $title, $message, $options);
    }

    public static function sendToCourse(int $courseId, string $title, string $message, array $options = []): int
    {
        return app(self::class)->sendToCourseRecipients($courseId, $title, $message, $options);
    }

    public static function getUnreadCount(int|User $user): int
    {
        return app(self::class)->countUnread($user);
    }

    public static function getNotifications(int|User $user, int $limit = 20, bool $unreadOnly = false, bool $paginate = true): Collection|LengthAwarePaginator
    {
        return app(self::class)->fetchNotifications($user, $limit, $unreadOnly, $paginate);
    }

    public static function markAsRead(int $notificationId, int|User $user): bool
    {
        return app(self::class)->markSingleAsRead($notificationId, $user);
    }

    public static function markAllAsRead(int|User $user): int
    {
        return app(self::class)->markAllRead($user);
    }

    public static function delete(int $notificationId, int|User $user): bool
    {
        return app(self::class)->deleteNotification($notificationId, $user);
    }

    public static function clearAll(int|User $user): int
    {
        return app(self::class)->clearAllNotifications($user);
    }
}
