<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\User;
use App\Services\MessagingService;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * Display the Messenger / Chat inbox.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $conversations = MessagingService::getConversations($user, 30);
        $totalUnread = MessagingService::getTotalUnreadCount($user);

        $activeConversationId = $request->input('conversation_id');
        $activeConversation = null;
        $messages = collect();

        if ($activeConversationId) {
            $activeConversation = $conversations->firstWhere('id', (int) $activeConversationId);
            if (!$activeConversation) {
                $activeConversation = Conversation::with(['users', 'participants'])->find($activeConversationId);
                if ($activeConversation && $activeConversation->participants->contains('user_id', $user->id)) {
                    $activeConversation->unread_count = $activeConversation->getUnreadCountFor($user->id);
                    $activeConversation->display_title = $activeConversation->getDisplayTitleFor($user->id);
                    $activeConversation->display_avatar = $activeConversation->getDisplayAvatarFor($user->id);
                    $activeConversation->recipient = $activeConversation->getRecipientFor($user->id);
                } else {
                    $activeConversation = null;
                }
            }
        }

        // If no conversation specified, pick the first one
        if (!$activeConversation && $conversations->isNotEmpty()) {
            $activeConversation = $conversations->first();
        }

        if ($activeConversation) {
            $messages = MessagingService::getMessages($activeConversation, $user, 60);
            MessagingService::markAsRead($activeConversation, $user);
            $activeConversation->unread_count = 0;
        }

        // Users available to message based on role:
        // - Admin: All users
        // - Student: Only instructors of enrolled courses
        // - Teacher: Only students of assigned courses
        $availableUsers = MessagingService::searchUsers('', $user, 50);

        return view('messages.index', compact('conversations', 'activeConversation', 'messages', 'totalUnread', 'availableUsers'));
    }

    /**
     * Send a message to the active conversation or direct user.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'body' => 'required|string|max:5000',
            'conversation_id' => 'nullable|integer|exists:conversations,id',
            'recipient_id' => 'nullable|integer|exists:users,id',
        ]);

        if ($request->filled('conversation_id')) {
            $conversation = Conversation::findOrFail($request->conversation_id);
            $message = MessagingService::sendMessage($user, $conversation, $request->input('body'));
            $convId = $conversation->id;
        } elseif ($request->filled('recipient_id')) {
            $recipientId = (int) $request->recipient_id;
            if (!MessagingService::canUserMessage($user, $recipientId)) {
                $msg = $user->isStudent()
                    ? 'Học viên chỉ có thể nhắn tin cho giảng viên phụ trách các khóa học mình đã ghi danh.'
                    : 'Giảng viên chỉ có thể nhắn tin cho học viên thuộc các khóa học mình phụ trách.';
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 403);
                }
                return back()->with('error', $msg);
            }

            $message = MessagingService::sendDirectMessage($user, $recipientId, $request->input('body'));
            $convId = $message->conversation_id;
        } else {
            return back()->with('error', 'Chưa chọn cuộc trò chuyện hoặc người nhận.');
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'conversation_id' => $convId,
            ]);
        }

        return redirect()->route('messages.index', ['conversation_id' => $convId]);
    }

    /**
     * Start conversation with specific user and redirect to inbox.
     */
    public function startDirect(Request $request, $recipientId)
    {
        $user = $request->user();
        if ((int) $recipientId === $user->id) {
            return back()->with('error', 'Không thể nhắn tin cho chính mình.');
        }

        if (!MessagingService::canUserMessage($user, (int) $recipientId)) {
            $msg = $user->isStudent()
                ? 'Học viên chỉ có thể nhắn tin cho giảng viên phụ trách các khóa học mình đã ghi danh.'
                : 'Giảng viên chỉ có thể nhắn tin cho học viên thuộc các khóa học mình phụ trách.';
            return back()->with('error', $msg);
        }

        $conversation = MessagingService::getOrCreateDirectConversation($user, (int) $recipientId);

        return redirect()->route('messages.index', ['conversation_id' => $conversation->id]);
    }
}
