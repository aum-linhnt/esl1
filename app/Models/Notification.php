<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sender_id',
        'type',
        'title',
        'message',
        'action_url',
        'icon',
        'data',
        'is_read',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
            'data' => 'array',
        ];
    }

    // ─── Relationships ───

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // ─── Scopes ───

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->where('is_read', true);
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('created_at');
    }

    // ─── Helpers ───

    public function markAsRead(): bool
    {
        if ($this->is_read) {
            return true;
        }

        return $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Get descriptive icon SVG or emoji for notification type.
     */
    public function getIconMetaAttribute(): array
    {
        return match ($this->type) {
            'course' => ['emoji' => '📚', 'color' => 'indigo', 'bg' => 'bg-indigo-500/20 text-indigo-400 border-indigo-500/30'],
            'assignment' => ['emoji' => '📋', 'color' => 'rose', 'bg' => 'bg-rose-500/20 text-rose-400 border-rose-500/30'],
            'exam' => ['emoji' => '🎯', 'color' => 'emerald', 'bg' => 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30'],
            'grade' => ['emoji' => '📊', 'color' => 'amber', 'bg' => 'bg-amber-500/20 text-amber-400 border-amber-500/30'],
            'badge' => ['emoji' => '🏆', 'color' => 'yellow', 'bg' => 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30'],
            'message' => ['emoji' => '💬', 'color' => 'teal', 'bg' => 'bg-teal-500/20 text-teal-400 border-teal-500/30'],
            'announcement' => ['emoji' => '📢', 'color' => 'purple', 'bg' => 'bg-purple-500/20 text-purple-400 border-purple-500/30'],
            default => ['emoji' => '🔔', 'color' => 'blue', 'bg' => 'bg-blue-500/20 text-blue-400 border-blue-500/30'],
        };
    }
}
