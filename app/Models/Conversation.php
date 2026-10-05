<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'title',
        'created_by',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    // ─── Relationships ───

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    // ─── Helpers ───

    public function isDirect(): bool
    {
        return $this->type === 'direct';
    }

    public function isGroup(): bool
    {
        return $this->type === 'group';
    }

    /**
     * Get the other user in a direct conversation.
     */
    public function getRecipientFor(int|User $user): ?User
    {
        $userId = $user instanceof User ? $user->id : (int) $user;

        return $this->users->first(fn($u) => (int) $u->id !== $userId);
    }

    /**
     * Get display title of the conversation relative to a user.
     */
    public function getDisplayTitleFor(int|User $user): string
    {
        if ($this->isGroup() && !empty($this->title)) {
            return $this->title;
        }

        $recipient = $this->getRecipientFor($user);
        return $recipient?->name ?? 'Người dùng không xác định';
    }

    /**
     * Get display avatar URL relative to a user.
     */
    public function getDisplayAvatarFor(int|User $user): string
    {
        $recipient = $this->getRecipientFor($user);
        return $recipient?->avatar_url ?? asset('images/default-avatar.svg');
    }

    /**
     * Calculate unread messages count for a specific user.
     */
    public function getUnreadCountFor(int|User $user): int
    {
        $userId = $user instanceof User ? $user->id : (int) $user;

        $participant = $this->participants->firstWhere('user_id', $userId);
        $lastReadAt = $participant?->last_read_at;

        $query = $this->messages()->where('sender_id', '!=', $userId);

        if ($lastReadAt) {
            $query->where('created_at', '>', $lastReadAt);
        }

        return $query->count();
    }
}
