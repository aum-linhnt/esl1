<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearnerSkillSnapshot extends Model
{
    protected $table = 'tutor_ai_learner_skill_snapshots';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['score' => 'float', 'criteria' => 'array', 'issues' => 'array', 'assessed_at' => 'datetime'];
    }
}
