<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdaptiveTestSession extends Model
{
    protected $fillable = [
        'user_id', 'current_difficulty', 'consecutive_correct',
        'consecutive_wrong', 'question_history', 'answers_history',
        'total_questions_answered', 'correct_count', 'score',
        'status', 'final_level',
    ];

    protected function casts(): array
    {
        return [
            'consecutive_correct' => 'integer',
            'consecutive_wrong' => 'integer',
            'question_history' => 'array',
            'answers_history' => 'array',
            'total_questions_answered' => 'integer',
            'correct_count' => 'integer',
            'score' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
