<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    // ─── Grading Scale Constants ───
    const SCALE_100       = 'scale_100';
    const SCALE_10        = 'scale_10';
    const SCALE_4         = 'scale_4';
    const SCALE_IELTS     = 'scale_ielts';
    const SCALE_PASS_FAIL = 'scale_pass_fail';

    /**
     * Registry of all supported grading scales with metadata.
     */
    public static array $gradingScales = [
        self::SCALE_100 => [
            'name'  => 'Thang điểm 100 (Phần trăm)',
            'short' => 'Thang 100%',
            'icon'  => '💯',
            'max'   => 100,
            'desc'  => 'Điểm từ 0% đến 100%, xếp loại A / B / C / D / F.',
        ],
        self::SCALE_10 => [
            'name'  => 'Thang điểm 10 (Hệ VN)',
            'short' => 'Thang 10',
            'icon'  => '🇻🇳',
            'max'   => 10,
            'desc'  => 'Điểm từ 0.0 đến 10.0, xếp loại Xuất sắc / Giỏi / Khá / TB / Yếu.',
        ],
        self::SCALE_4 => [
            'name'  => 'Thang GPA 4.0 (Quốc tế)',
            'short' => 'GPA 4.0',
            'icon'  => '🅰️',
            'max'   => 4,
            'desc'  => 'Điểm từ 0.0 đến 4.0 GPA, xếp loại A+ / A / B+ / B / C+ / C / D / F.',
        ],
        self::SCALE_IELTS => [
            'name'  => 'Thang IELTS Band (1.0 - 9.0)',
            'short' => 'IELTS Band',
            'icon'  => '🎯',
            'max'   => 9,
            'desc'  => 'Quy đổi thành IELTS Band Score 1.0 - 9.0 (bước nhảy 0.5).',
        ],
        self::SCALE_PASS_FAIL => [
            'name'  => 'Đạt / Không đạt (Pass/Fail)',
            'short' => 'Pass/Fail',
            'icon'  => '⚖️',
            'max'   => 100,
            'desc'  => 'Đánh giá Đạt hoặc Chưa đạt dựa trên ngưỡng điểm chuẩn.',
        ],
    ];

    protected $fillable = [
        'title', 'slug', 'description', 'thumbnail',
        'level', 'target_audience', 'order', 'is_published',
        'allow_self_enrollment', 'enrollment_key', 'enrollment_duration_days',
        'grading_scale', 'passing_grade',
        'certificate_enabled', 'badge_reward', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'allow_self_enrollment' => 'boolean',
            'enrollment_duration_days' => 'integer',
            'certificate_enabled' => 'boolean',
            'order' => 'integer',
            'passing_grade' => 'decimal:2',
        ];
    }

    // ─── Relationships ───

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('order');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function enrolledStudents()
    {
        return $this->belongsToMany(User::class, 'enrollments')
            ->withPivot('course_role', 'status', 'enrolled_at', 'completed_at', 'expires_at', 'final_grade', 'progress_percentage')
            ->withTimestamps();
    }

    // ─── Enrollment & Visibility Helpers ───

    public function activeEnrollmentsCount(): int
    {
        return $this->enrollments()->whereIn('status', ['active', 'completed'])->count();
    }

    public function isPublished(): bool
    {
        return (bool) $this->is_published;
    }

    public function isHidden(): bool
    {
        return !$this->is_published;
    }

    public function allowsSelfEnrollment(): bool
    {
        return (bool) $this->allow_self_enrollment;
    }

    public function isManualEnrollmentOnly(): bool
    {
        return !$this->allow_self_enrollment;
    }

    public function requiresEnrollmentKey(): bool
    {
        return !empty($this->enrollment_key);
    }

    // ─── Grading Scale Helpers ───

    /**
     * Get the metadata array for this course's configured grading scale.
     */
    public function getGradingScaleMeta(): array
    {
        return self::$gradingScales[$this->grading_scale] ?? self::$gradingScales[self::SCALE_100];
    }

    /**
     * Get a human-readable label for the passing grade threshold.
     */
    public function getPassingGradeLabel(): string
    {
        $meta = $this->getGradingScaleMeta();
        $raw = (float) $this->passing_grade;

        return match ($this->grading_scale) {
            self::SCALE_10        => number_format($raw / 10, 1) . ' / 10',
            self::SCALE_4         => number_format($raw / 25, 1) . ' / 4.0',
            self::SCALE_IELTS     => 'Band ' . number_format(1 + ($raw / 100) * 8, 1),
            self::SCALE_PASS_FAIL => $raw . '%',
            default               => $raw . ' / 100',
        };
    }

    /**
     * Questions belonging specifically to this course.
     */
    public function questionBanks(): HasMany
    {
        return $this->hasMany(QuestionBank::class);
    }

    /**
     * Get the accessible public URL for the course thumbnail.
     */
    public function getThumbnailUrlAttribute(): string
    {
        if (empty($this->thumbnail)) {
            return asset('/images/course-' . strtolower($this->level ?? 'a1') . '.png');
        }

        if (str_starts_with($this->thumbnail, 'http://') || str_starts_with($this->thumbnail, 'https://') || str_starts_with($this->thumbnail, '//')) {
            return $this->thumbnail;
        }

        if (str_starts_with($this->thumbnail, '/')) {
            return asset($this->thumbnail);
        }

        return url('storage/' . $this->thumbnail);
    }
}


