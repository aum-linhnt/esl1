<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentSubmission extends Model
{
    protected $fillable = [
        'activity_id', 'user_id', 'file_id',
        'text_content', 'attempt_number', 'status',
        'grade', 'feedback', 'graded_by',
        'submitted_at', 'graded_at',
    ];

    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer',
            'grade' => 'decimal:2',
            'submitted_at' => 'datetime',
            'graded_at' => 'datetime',
        ];
    }

    // ─── Status Constants ───
    const STATUS_DRAFT     = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_GRADED    = 'graded';
    const STATUS_RETURNED  = 'returned';

    public static array $statusLabels = [
        self::STATUS_DRAFT     => ['label' => 'Bản nháp', 'color' => 'gray', 'icon' => '📝'],
        self::STATUS_SUBMITTED => ['label' => 'Đã nộp', 'color' => 'blue', 'icon' => '📤'],
        self::STATUS_GRADED    => ['label' => 'Đã chấm', 'color' => 'green', 'icon' => '✅'],
        self::STATUS_RETURNED  => ['label' => 'Trả lại', 'color' => 'amber', 'icon' => '🔄'],
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

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    // ─── Helpers ───

    /**
     * Check if this submission has been graded.
     */
    public function isGraded(): bool
    {
        return $this->status === self::STATUS_GRADED;
    }

    /**
     * Check if this submission is pending review.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    /**
     * Get the status display info (label, color, icon).
     */
    public function getStatusInfo(): array
    {
        return self::$statusLabels[$this->status] ?? [
            'label' => ucfirst($this->status), 'color' => 'slate', 'icon' => '❓',
        ];
    }

    /**
     * Check if the student passed (based on activity passing_grade).
     */
    public function isPassed(): bool
    {
        if (!$this->isGraded() || $this->grade === null) {
            return false;
        }

        $passingGrade = $this->activity->passing_grade ?? 0;
        return $this->grade >= $passingGrade;
    }
}
