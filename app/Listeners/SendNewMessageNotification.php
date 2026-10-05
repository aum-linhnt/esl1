<?php

namespace App\Listeners;

use App\Events\MessageSent;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Str;

class SendNewMessageNotification
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(MessageSent $event): void
    {
        if (!$event->shouldNotify) {
            return;
        }

        $sender = User::find($event->senderId);
        $senderName = $sender?->name ?? 'Người dùng';

        $recipients = $event->conversation->participants()
            ->where('user_id', '!=', $event->senderId)
            ->pluck('user_id');

        if ($recipients->isEmpty()) {
            return;
        }

        $preview = Str::limit(strip_tags($event->message->body), 60);

        $this->notificationService->sendToMany(
            $recipients,
            "Tin nhắn mới từ {$senderName}",
            $preview,
            [
                'type' => 'message',
                'action_url' => route('messages.index', ['conversation_id' => $event->conversation->id]),
                'icon' => 'chat',
                'sender_id' => $event->senderId,
                'data' => [
                    'conversation_id' => $event->conversation->id,
                    'message_id' => $event->message->id,
                ],
            ]
        );
    }
}
