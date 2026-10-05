<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class MessagingService
{
    /**
     * Get allowed recipient IDs that the given user can message.
     */
    public function allowedRecipientIds(User $user): array
    {
        // 1. Admin can message anyone in the system
        if ($user->isAdmin()) {
            return User::where('id', '!=', $user->id)->pluck('id')->toArray();
        }

        // 2. Student: Only instructors/teachers of courses they are enrolled in (plus admins)
        if ($user->isStudent()) {
            $enrolledCourseIds = Enrollment::where('user_id', $user->id)
                ->whereIn('status', ['active', 'completed'])
                ->pluck('course_id')
                ->unique()
                ->toArray();

            // Teachers assigned in these courses via enrollment
            $teacherEnrollmentUserIds = Enrollment::whereIn('course_id', $enrolledCourseIds)
                ->whereIn('course_role', [Enrollment::ROLE_TEACHER, Enrollment::ROLE_ASSISTANT, Enrollment::ROLE_MANAGER])
                ->pluck('user_id')
                ->toArray();

            // Course creators/instructors of these courses
            $courseCreatorIds = Course::whereIn('id', $enrolledCourseIds)
                ->pluck('created_by')
                ->toArray();

            // Admins (for support)
            $adminIds = User::where('role', 'admin')->pluck('id')->toArray();

            $allowedIds = array_unique(array_merge($teacherEnrollmentUserIds, $courseCreatorIds, $adminIds));
            return array_values(array_filter($allowedIds, fn($id) => (int)$id !== (int)$user->id));
        }

        // 3. Teacher: Only students belonging to the courses they teach or manage (plus admins)
        if ($user->isTeacher()) {
            // Courses created by this teacher
            $createdCourseIds = Course::where('created_by', $user->id)->pluck('id')->toArray();

            // Courses where teacher is assigned role in enrollment
            $assignedCourseIds = Enrollment::where('user_id', $user->id)
                ->whereIn('course_role', [Enrollment::ROLE_TEACHER, Enrollment::ROLE_ASSISTANT, Enrollment::ROLE_MANAGER])
                ->pluck('course_id')
                ->toArray();

            $allTeacherCourseIds = array_unique(array_merge($createdCourseIds, $assignedCourseIds));

            // Students enrolled in these courses
            $studentIds = Enrollment::whereIn('course_id', $allTeacherCourseIds)
                ->where('course_role', Enrollment::ROLE_STUDENT)
                ->whereIn('status', ['active', 'completed'])
                ->pluck('user_id')
                ->toArray();

            // Admins (for support)
            $adminIds = User::where('role', 'admin')->pluck('id')->toArray();

            $allowedIds = array_unique(array_merge($studentIds, $adminIds));
            return array_values(array_filter($allowedIds, fn($id) => (int)$id !== (int)$user->id));
        }

        return [];
    }

    /**
     * Check if a sender is allowed to message a recipient.
     */
    public function canSendMessage(User $sender, User $recipient): bool
    {
        if ($sender->id === $recipient->id) {
            return false;
        }

        $allowedIds = $this->allowedRecipientIds($sender);
        return in_array($recipient->id, $allowedIds);
    }

    /**
     * Get or create a direct (1-on-1) conversation between two users.
     */
    public function findOrCreateDirectConversation(int|User $userA, int|User $userB): Conversation
    {
        $idA = $userA instanceof User ? $userA->id : (int) $userA;
        $idB = $userB instanceof User ? $userB->id : (int) $userB;

        if ($idA === $idB) {
            throw new \InvalidArgumentException('Không thể tạo cuộc trò chuyện với chính mình.');
        }

        // Check permission if user model is available
        $senderUser = $userA instanceof User ? $userA : User::find($idA);
        $recipientUser = $userB instanceof User ? $userB : User::find($idB);

        if ($senderUser && $recipientUser && !$this->canSendMessage($senderUser, $recipientUser)) {
            // Check reverse (if conversation already existed earlier, allow reading)
            $existing = Conversation::where('type', 'direct')
                ->whereHas('participants', fn($q) => $q->where('user_id', $idA))
                ->whereHas('participants', fn($q) => $q->where('user_id', $idB))
                ->first();

            if (!$existing) {
                throw new \Illuminate\Auth\Access\AuthorizationException(
                    'Bạn không có quyền gửi tin nhắn cho người dùng này theo quy định của hệ thống.'
                );
            }
            return $existing;
        }

        // Find existing direct conversation between idA and idB
        $existing = Conversation::where('type', 'direct')
            ->whereHas('participants', fn($q) => $q->where('user_id', $idA))
            ->whereHas('participants', fn($q) => $q->where('user_id', $idB))
            ->first();

        if ($existing) {
            return $existing;
        }

        // Create new direct conversation
        return DB::transaction(function () use ($idA, $idB) {
            $conv = Conversation::create([
                'type' => 'direct',
                'created_by' => $idA,
                'last_message_at' => now(),
            ]);

            ConversationParticipant::insert([
                [
                    'conversation_id' => $conv->id,
                    'user_id' => $idA,
                    'last_read_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'conversation_id' => $conv->id,
                    'user_id' => $idB,
                    'last_read_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            return $conv;
        });
    }

    /**
     * Get or create a course group conversation.
     */
    public function findOrCreateCourseConversation(int|Course $course, int|User $creator): Conversation
    {
        $courseId = $course instanceof Course ? $course->id : (int) $course;
        $courseModel = $course instanceof Course ? $course : Course::findOrFail($courseId);
        $creatorId = $creator instanceof User ? $creator->id : (int) $creator;

        $conv = Conversation::firstOrCreate(
            [
                'type' => 'course_group',
                'course_id' => $courseId,
            ],
            [
                'title' => 'Nhóm thảo luận: ' . $courseModel->title,
                'created_by' => $creatorId,
                'last_message_at' => now(),
            ]
        );

        ConversationParticipant::firstOrCreate([
            'conversation_id' => $conv->id,
            'user_id' => $creatorId,
        ], [
            'last_read_at' => now(),
        ]);

        return $conv;
    }

    /**
     * Add a participant to a conversation.
     */
    public function addMember(int|Conversation $conversation, int|User $user): ConversationParticipant
    {
        $convId = $conversation instanceof Conversation ? $conversation->id : (int) $conversation;
        $userId = $user instanceof User ? $user->id : (int) $user;

        return ConversationParticipant::firstOrCreate([
            'conversation_id' => $convId,
            'user_id' => $userId,
        ], [
            'last_read_at' => now(),
        ]);
    }

    /**
     * Remove a participant from a conversation.
     */
    public function removeMember(int|Conversation $conversation, int|User $user): bool
    {
        $convId = $conversation instanceof Conversation ? $conversation->id : (int) $conversation;
        $userId = $user instanceof User ? $user->id : (int) $user;

        return (bool) ConversationParticipant::where('conversation_id', $convId)
            ->where('user_id', $userId)
            ->delete();
    }

    /**
     * Send a message to a conversation.
     */
    public function postMessage(
        int|User $sender,
        int|Conversation $conversation,
        string $body,
        array $options = []
    ): Message {
        $senderId = $sender instanceof User ? $sender->id : (int) $sender;
        $conv = $conversation instanceof Conversation ? $conversation : Conversation::findOrFail($conversation);

        $participant = ConversationParticipant::where('conversation_id', $conv->id)
            ->where('user_id', $senderId)
            ->first();

        if (!$participant) {
            $participant = ConversationParticipant::create([
                'conversation_id' => $conv->id,
                'user_id' => $senderId,
                'last_read_at' => now(),
            ]);
        }

        $now = now();

        $message = DB::transaction(function () use ($conv, $senderId, $body, $options, $now) {
            $msg = Message::create([
                'conversation_id' => $conv->id,
                'sender_id'       => $senderId,
                'body'            => trim($body),
                'type'            => $options['type'] ?? 'text',
                'attachment_url'  => $options['attachment_url'] ?? null,
                'attachment_name' => $options['attachment_name'] ?? null,
                'attachment_size' => $options['attachment_size'] ?? null,
                'metadata'        => $options['metadata'] ?? null,
                'is_read'         => false,
            ]);

            $conv->update(['last_message_at' => $now]);

            ConversationParticipant::where('conversation_id', $conv->id)
                ->where('user_id', $senderId)
                ->update(['last_read_at' => $now]);

            return $msg;
        });

        $notify = $options['notify'] ?? true;
        event(new MessageSent($message, $conv, $senderId, $notify));

        return $message->load('sender:id,name,avatar,role');
    }

    /**
     * Send direct message to a user directly (finds or creates conversation automatically).
     */
    public function postDirectMessage(
        int|User $sender,
        int|User $recipient,
        string $body,
        array $options = []
    ): Message {
        $conversation = $this->findOrCreateDirectConversation($sender, $recipient);
        return $this->postMessage($sender, $conversation, $body, $options);
    }

    /**
     * Get conversations for a user, sorted by latest activity.
     */
    public function fetchConversations(int|User $user, int $limit = 20): Collection
    {
        $userId = $user instanceof User ? $user->id : (int) $user;

        $conversations = Conversation::whereHas('participants', fn($q) => $q->where('user_id', $userId))
            ->with([
                'users:id,name,avatar,role,email',
                'participants',
                'latestMessage.sender:id,name',
            ])
            ->orderByDesc('last_message_at')
            ->take($limit)
            ->get();

        foreach ($conversations as $conv) {
            $conv->unread_count = $conv->getUnreadCountFor($userId);
            $conv->display_title = $conv->getDisplayTitleFor($userId);
            $conv->display_avatar = $conv->getDisplayAvatarFor($userId);
            $conv->recipient = $conv->getRecipientFor($userId);
        }

        return $conversations;
    }

    /**
     * Get messages in a conversation.
     */
    public function fetchMessages(
        int|Conversation $conversation,
        int|User $user,
        int $limit = 50,
        ?int $beforeId = null
    ): Collection {
        $convId = $conversation instanceof Conversation ? $conversation->id : (int) $conversation;
        $userId = $user instanceof User ? $user->id : (int) $user;

        $isMember = ConversationParticipant::where('conversation_id', $convId)
            ->where('user_id', $userId)
            ->exists();

        if (!$isMember) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Bạn không thuộc cuộc trò chuyện này.');
        }

        $query = Message::with('sender:id,name,avatar,role')
            ->where('conversation_id', $convId);

        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }

        return $query->orderBy('created_at', 'asc')
            ->take($limit)
            ->get();
    }

    /**
     * Mark all messages in a conversation as read for a user.
     */
    public function markConversationAsRead(int|Conversation $conversation, int|User $user): int
    {
        $convId = $conversation instanceof Conversation ? $conversation->id : (int) $conversation;
        $userId = $user instanceof User ? $user->id : (int) $user;

        $now = now();

        ConversationParticipant::where('conversation_id', $convId)
            ->where('user_id', $userId)
            ->update(['last_read_at' => $now]);

        return Message::where('conversation_id', $convId)
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    /**
     * Calculate total unread messages count for a user across all conversations.
     */
    public function unreadCountTotal(int|User $user): int
    {
        $userId = $user instanceof User ? $user->id : (int) $user;

        $participants = ConversationParticipant::where('user_id', $userId)->get();
        if ($participants->isEmpty()) {
            return 0;
        }

        $totalUnread = 0;
        foreach ($participants as $p) {
            $query = Message::where('conversation_id', $p->conversation_id)
                ->where('sender_id', '!=', $userId);

            if ($p->last_read_at) {
                $query->where('created_at', '>', $p->last_read_at);
            }

            $totalUnread += $query->count();
        }

        return $totalUnread;
    }

    /**
     * Search users by name, username or email for starting chats.
     */
    public function findUsers(string $query, int|User $currentUser, int $limit = 10): Collection
    {
        $user = $currentUser instanceof User ? $currentUser : User::findOrFail($currentUser);
        $term = trim($query);

        $allowedIds = $this->allowedRecipientIds($user);
        if (empty($allowedIds)) {
            return new Collection();
        }

        $builder = User::whereIn('id', $allowedIds)->where('status', 'active');

        if (!empty($term)) {
            $builder->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('username', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%");
            });
        }

        return $builder->take($limit)->get(['id', 'name', 'username', 'email', 'avatar', 'role']);
    }

    // ─── Static Facade API (Matching exactly previous static signatures) ───

    public static function getAllowedRecipientIds(User $user): array
    {
        return app(self::class)->allowedRecipientIds($user);
    }

    public static function canMessage(User $sender, User $recipient): bool
    {
        return app(self::class)->canSendMessage($sender, $recipient);
    }

    public static function getOrCreateDirectConversation(int|User $userA, int|User $userB): Conversation
    {
        return app(self::class)->findOrCreateDirectConversation($userA, $userB);
    }

    public static function getOrCreateCourseConversation(int|Course $course, int|User $creator): Conversation
    {
        return app(self::class)->findOrCreateCourseConversation($course, $creator);
    }

    public static function addParticipant(int|Conversation $conversation, int|User $user): ConversationParticipant
    {
        return app(self::class)->addMember($conversation, $user);
    }

    public static function removeParticipant(int|Conversation $conversation, int|User $user): bool
    {
        return app(self::class)->removeMember($conversation, $user);
    }

    public static function sendMessage(int|User $sender, int|Conversation $conversation, string $body, array $options = []): Message
    {
        return app(self::class)->postMessage($sender, $conversation, $body, $options);
    }

    public static function sendDirectMessage(int|User $sender, int|User $recipient, string $body, array $options = []): Message
    {
        return app(self::class)->postDirectMessage($sender, $recipient, $body, $options);
    }

    public static function getConversations(int|User $user, int $limit = 20): Collection
    {
        return app(self::class)->fetchConversations($user, $limit);
    }

    public static function getMessages(int|Conversation $conversation, int|User $user, int $limit = 50, ?int $beforeId = null): Collection
    {
        return app(self::class)->fetchMessages($conversation, $user, $limit, $beforeId);
    }

    public static function markAsRead(int|Conversation $conversation, int|User $user): int
    {
        return app(self::class)->markConversationAsRead($conversation, $user);
    }

    public static function getTotalUnreadCount(int|User $user): int
    {
        return app(self::class)->unreadCountTotal($user);
    }

    public static function searchUsers(string $query, int|User $currentUser, int $limit = 10): Collection
    {
        return app(self::class)->findUsers($query, $currentUser, $limit);
    }
}
