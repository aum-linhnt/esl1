<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UserActionLog extends Model
{
    /**
     * Disable default timestamps (we only use created_at).
     */
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'loggable_type',
        'loggable_id',
        'ip_address',
        'user_agent',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    // ─── Relationships ───

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    // ─── Scopes ───

    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    // ─── Helpers ───

    /**
     * Map action keys to human-readable Vietnamese labels.
     */
    public static function actionLabels(): array
    {
        return [
            'login'             => '🔑 Đăng nhập',
            'logout'            => '🚪 Đăng xuất',
            'view_course'       => '📚 Xem khóa học',
            'view_lesson'       => '📖 Xem bài học',
            'view_activity'     => '📝 Xem hoạt động',
            'view_exam'         => '📋 Xem đề thi',
            'submit_exam'       => '✅ Nộp bài thi',
            'enroll'            => '📥 Ghi danh',
            'unenroll'          => '📤 Hủy ghi danh',
            'complete_activity' => '🏆 Hoàn thành hoạt động',
            'complete_lesson'   => '🎯 Hoàn thành bài học',
        ];
    }

    /**
     * Get the Vietnamese label for this log's action.
     */
    public function getActionLabelAttribute(): string
    {
        return static::actionLabels()[$this->action] ?? $this->action;
    }

    /**
     * Get badge color class for the action type.
     */
    public function getActionColorAttribute(): string
    {
        return match ($this->action) {
            'login'             => 'bg-green-500/20 text-green-400',
            'logout'            => 'bg-gray-500/20 text-gray-400',
            'view_course'       => 'bg-blue-500/20 text-blue-400',
            'view_lesson'       => 'bg-indigo-500/20 text-indigo-400',
            'view_activity'     => 'bg-cyan-500/20 text-cyan-400',
            'view_exam'         => 'bg-orange-500/20 text-orange-400',
            'submit_exam'       => 'bg-emerald-500/20 text-emerald-400',
            'enroll'            => 'bg-purple-500/20 text-purple-400',
            'unenroll'          => 'bg-red-500/20 text-red-400',
            'complete_activity' => 'bg-yellow-500/20 text-yellow-400',
            'complete_lesson'   => 'bg-teal-500/20 text-teal-400',
            default             => 'bg-gray-500/20 text-gray-400',
        };
    }
}
