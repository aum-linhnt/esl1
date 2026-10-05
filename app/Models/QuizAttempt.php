<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAttempt extends Model
{
    protected $fillable = [
        'activity_id',
        'user_id',
        'attempt_number',
        'status',
        'score',
        'max_score',
        'percentage',
        'is_passed',
        'time_spent_seconds',
        'answers_payload',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer',
            'score' => 'float',
            'max_score' => 'float',
            'percentage' => 'float',
            'is_passed' => 'boolean',
            'time_spent_seconds' => 'integer',
            'answers_payload' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // ─── Status Constants ───
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED   = 'completed';
    const STATUS_ABANDONED   = 'abandoned';
    const STATUS_TIMED_OUT   = 'timed_out';

    public static array $statusLabels = [
        self::STATUS_IN_PROGRESS => ['label' => 'Đang làm bài', 'color' => 'amber', 'icon' => '⏳'],
        self::STATUS_COMPLETED   => ['label' => 'Đã hoàn thành', 'color' => 'emerald', 'icon' => '✅'],
        self::STATUS_ABANDONED   => ['label' => 'Đã hủy', 'color' => 'gray', 'icon' => '⏹️'],
        self::STATUS_TIMED_OUT   => ['label' => 'Hết giờ', 'color' => 'rose', 'icon' => '⏰'],
    ];

    // ─── Relationships ───

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Helpers ───

    public function getFormattedDuration(): string
    {
        $seconds = $this->time_spent_seconds ?? 0;
        if ($seconds < 60) {
            return "{$seconds} giây";
        }
        $minutes = floor($seconds / 60);
        $remSeconds = $seconds % 60;
        return $remSeconds > 0 ? "{$minutes} phút {$remSeconds} giây" : "{$minutes} phút";
    }

    public function getStatusInfo(): array
    {
        return self::$statusLabels[$this->status] ?? [
            'label' => ucfirst($this->status),
            'color' => 'blue',
            'icon' => 'ℹ️',
        ];
    }
}
