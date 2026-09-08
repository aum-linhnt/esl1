<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamSet extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'title',
        'skill',
        'difficulty',
        'question_count',
        'duration_minutes',
        'reward_coins',
        'description',
        'sections',
        'question_ids',
        'is_published',
        'created_by',
    ];

    protected $casts = [
        'sections' => 'array',
        'question_ids' => 'array',
        'is_published' => 'boolean',
        'question_count' => 'integer',
        'duration_minutes' => 'integer',
        'reward_coins' => 'integer',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submissions()
    {
        return $this->hasMany(AssessmentSubmission::class, 'test_type', 'key');
    }
}
