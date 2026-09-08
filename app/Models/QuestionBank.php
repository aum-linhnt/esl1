<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuestionBank extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'course_id', 'skill', 'difficulty', 'version', 'parent_id',
        'question_type', 'question_text', 'options', 'audio_url',
        'correct_answer', 'explanation', 'meta_data',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'options' => 'array',
            'meta_data' => 'array',
        ];
    }

    /**
     * Ensure options is always returned as array, even if double-encoded or stored as string.
     */
    protected function options(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (is_null($value)) {
                    return [];
                }
                if (is_array($value)) {
                    return $value;
                }
                $decoded = json_decode($value, true);
                if (is_string($decoded)) {
                    $decoded = json_decode($decoded, true);
                }
                return is_array($decoded) ? $decoded : [];
            }
        );
    }

    public function scopeBySkill(Builder $query, string $skill): Builder
    {
        return $query->where('skill', $skill);
    }

    public function scopeByDifficulty(Builder $query, string $difficulty): Builder
    {
        return $query->where('difficulty', $difficulty);
    }

    public function course(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function scopeForCourse(Builder $query, ?int $courseId, bool $includeGlobal = false): Builder
    {
        if ($includeGlobal && $courseId) {
            return $query->where(function ($q) use ($courseId) {
                $q->where('course_id', $courseId)->orWhereNull('course_id');
            });
        }
        return is_null($courseId) ? $query->whereNull('course_id') : $query->where('course_id', $courseId);
    }

    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'parent_id');
    }

    public function versions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(QuestionBank::class, 'parent_id')->withTrashed();
    }

    /**
     * Convert this QuestionBank item to standard quiz activity question format.
     */
    public function toQuizFormat(): array
    {
        $options = is_array($this->options) ? $this->options : [];
        $answerIndex = 0;

        if ($this->question_type === 'true_false') {
            $options = ['True', 'False', 'Not Given'];
            foreach ($options as $idx => $opt) {
                if (strcasecmp($opt, (string) $this->correct_answer) === 0) {
                    $answerIndex = $idx;
                    break;
                }
            }
        } elseif (is_array($options)) {
            foreach ($options as $idx => $opt) {
                if (is_scalar($opt) && is_scalar($this->correct_answer) && trim((string) $opt) === trim((string) $this->correct_answer)) {
                    $answerIndex = $idx;
                    break;
                }
            }

            if (is_numeric($this->correct_answer) && isset($options[(int) $this->correct_answer])) {
                $answerIndex = (int) $this->correct_answer;
            }
        }

        return [
            'id' => $this->id,
            'version' => $this->version ?? 1,
            'question' => $this->question_text,
            'question_type' => $this->question_type ?: 'mcq',
            'options' => $options,
            'answer' => $answerIndex,
            'correct_answer' => $this->correct_answer,
            'audio_url' => $this->audio_url,
            'explanation' => $this->explanation,
            'skill' => $this->skill,
            'difficulty' => $this->difficulty,
        ];
    }
}
