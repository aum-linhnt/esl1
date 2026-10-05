<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Services\MessagingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageApiController extends Controller
{
    /**
     * List user conversations with last message, unread count and recipient details.
     */
    public function conversations(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $limit = min(50, max(5, (int) $request->input('limit', 20)));
        $conversations = MessagingService::getConversations($user, $limit);
        $totalUnread = MessagingService::getTotalUnreadCount($user);

        return response()->json([
            'success' => true,
            'total_unread' => $totalUnread,
            'data' => $conversations,
        ]);
    }

    /**
     * Get total unread messages count across all conversations.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['unread_count' => 0]);
        }

        return response()->json([
            'success' => true,
            'unread_count' => MessagingService::getTotalUnreadCount($user),
        ]);
    }

    /**
     * Start a new conversation (Direct or Group).
     */
    public function createConversation(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'type' => 'nullable|string|in:direct,group',
            'recipient_id' => 'required_if:type,direct|nullable|integer|exists:users,id',
            'participant_ids' => 'required_if:type,group|nullable|array',
            'participant_ids.*' => 'integer|exists:users,id',
            'title' => 'required_if:type,group|nullable|string|max:255',
            'initial_message' => 'nullable|string',
        ]);

        $type = $request->input('type', 'direct');

        if ($type === 'direct') {
            $recipientId = (int) $request->input('recipient_id');
            if ($recipientId === $user->id) {
                return response()->json(['success' => false, 'message' => 'Không thể tự trò chuyện với chính mình.'], 422);
            }

            if (!MessagingService::canUserMessage($user, $recipientId)) {
                $msg = $user->isStudent()
                    ? 'Học viên chỉ có thể nhắn tin cho giảng viên phụ trách các khóa học mình đã ghi danh.'
                    : 'Giảng viên chỉ có thể nhắn tin cho học viên thuộc các khóa học mình phụ trách.';
                return response()->json(['success' => false, 'message' => $msg], 403);
            }

            $conversation = MessagingService::getOrCreateDirectConversation($user, $recipientId);
        } else {
            $conversation = MessagingService::createGroupConversation(
                $user,
                $request->input('participant_ids', []),
                $request->input('title', 'Cuộc trò chuyện nhóm')
            );
        }

        if ($request->filled('initial_message')) {
            MessagingService::sendMessage($user, $conversation, $request->input('initial_message'));
        }

        $conversation->unread_count = 0;
        $conversation->display_title = $conversation->getDisplayTitleFor($user);
        $conversation->display_avatar = $conversation->getDisplayAvatarFor($user);
        $conversation->recipient = $conversation->getRecipientFor($user);

        return response()->json([
            'success' => true,
            'message' => 'Đã mở cuộc trò chuyện.',
            'data' => $conversation,
        ]);
    }

    /**
     * Show messages for a specific conversation.
     */
    public function showConversation(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $conversation = Conversation::with(['users:id,name,avatar,role,email', 'participants'])->findOrFail($id);

        $limit = min(100, max(10, (int) $request->input('limit', 50)));
        $beforeId = $request->input('before_id') ? (int) $request->input('before_id') : null;

        $messages = MessagingService::getMessages($conversation, $user, $limit, $beforeId);

        // Auto mark as read when fetching conversation messages
        MessagingService::markAsRead($conversation, $user);

        $conversation->display_title = $conversation->getDisplayTitleFor($user);
        $conversation->display_avatar = $conversation->getDisplayAvatarFor($user);
        $conversation->recipient = $conversation->getRecipientFor($user);

        return response()->json([
            'success' => true,
            'conversation' => $conversation,
            'messages' => $messages,
        ]);
    }

    /**
     * Send a message in a conversation.
     */
    public function sendMessage(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $conversation = Conversation::findOrFail($id);

        $request->validate([
            'body' => 'required|string|max:5000',
            'type' => 'nullable|string|in:text,image,file',
            'attachment_url' => 'nullable|string|max:500',
            'attachment_name' => 'nullable|string|max:255',
            'attachment_size' => 'nullable|integer',
        ]);

        $options = $request->only(['type', 'attachment_url', 'attachment_name', 'attachment_size']);

        $message = MessagingService::sendMessage($user, $conversation, $request->input('body'), $options);

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    /**
     * Quick direct message to a user (finds/creates conversation and sends message).
     */
    public function sendDirect(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'recipient_id' => 'required|integer|exists:users,id',
            'body' => 'required|string|max:5000',
            'type' => 'nullable|string|in:text,image,file',
            'attachment_url' => 'nullable|string|max:500',
        ]);

        $recipientId = (int) $request->input('recipient_id');
        if ($recipientId === $user->id) {
            return response()->json(['success' => false, 'message' => 'Không thể gửi tin nhắn cho chính mình.'], 422);
        }

        if (!MessagingService::canUserMessage($user, $recipientId)) {
            $msg = $user->isStudent()
                ? 'Học viên chỉ có thể nhắn tin cho giảng viên phụ trách các khóa học mình đã ghi danh.'
                : 'Giảng viên chỉ có thể nhắn tin cho học viên thuộc các khóa học mình phụ trách.';
            return response()->json(['success' => false, 'message' => $msg], 403);
        }

        $options = $request->only(['type', 'attachment_url']);
        $message = MessagingService::sendDirectMessage($user, $recipientId, $request->input('body'), $options);

        return response()->json([
            'success' => true,
            'conversation_id' => $message->conversation_id,
            'message' => $message,
        ]);
    }

    /**
     * Mark a conversation as read.
     */
    public function markAsRead(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $conversation = Conversation::findOrFail($id);

        $count = MessagingService::markAsRead($conversation, $user);

        return response()->json([
            'success' => true,
            'marked_count' => $count,
            'total_unread' => MessagingService::getTotalUnreadCount($user),
            'message' => 'Đã đánh dấu đã đọc.',
        ]);
    }

    /**
     * Search users to start new chat.
     */
    public function searchUsers(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = $request->input('q', '');

        $users = MessagingService::searchUsers($query, $user, 15);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }
}
