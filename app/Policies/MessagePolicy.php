<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;
use App\Services\MessagingService;

class MessagePolicy
{
    /**
     * Determine whether the user can view the given conversation.
     */
    public function view(User $user, Conversation $conversation): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $conversation->participants()
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Determine whether the user can send a message in the given conversation.
     */
    public function sendMessage(User $user, Conversation $conversation): bool
    {
        return $this->view($user, $conversation);
    }

    /**
     * Determine whether the user can initiate a direct conversation with the target recipient.
     */
    public function initiateDirectMessage(User $user, User $recipient): bool
    {
        if ($user->id === $recipient->id) {
            return false;
        }

        if ($user->isAdmin() || $recipient->isAdmin()) {
            return true;
        }

        $allowedRecipientIds = app(MessagingService::class)->getAllowedRecipientIds($user);

        return in_array($recipient->id, $allowedRecipientIds, true);
    }
}
