<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentSubmission extends Model
{
    protected $fillable = [
        'user_id', 'lesson_id', 'test_type',
        'attempt_number', 'time_spent_seconds', 'status',
        'total_score', 'max_score', 'accuracy_rate',
        'is_passed', 'answers_payload',
        'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer',
            'time_spent_seconds' => 'integer',
            'total_score' => 'integer',
            'max_score' => 'integer',
            'accuracy_rate' => 'float',
            'is_passed' => 'boolean',
            'answers_payload' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
