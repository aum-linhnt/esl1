<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityCompletion extends Model
{
    protected $fillable = [
        'user_id', 'activity_id', 'lesson_id',
        'score', 'max_score', 'time_spent_seconds',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'score' => 'integer',
            'max_score' => 'integer',
            'time_spent_seconds' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Get accuracy as percentage (0-100).
     */
    public function getAccuracyAttribute(): float
    {
        return $this->max_score > 0
            ? round(($this->score / $this->max_score) * 100, 1)
            : 0;
    }
}
