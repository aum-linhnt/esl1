<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vocabulary extends Model
{
    protected $fillable = [
        'lesson_id', 'word', 'phonetic', 'meaning',
        'example', 'audio_url', 'image_url', 'level',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
