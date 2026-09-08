<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearnerSkill extends Model
{
    protected $fillable = [
        'user_id', 'skill_type', 'mastery_score',
        'assessed_level', 'last_assessed_at',
    ];

    protected function casts(): array
    {
        return [
            'mastery_score' => 'integer',
            'last_assessed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
