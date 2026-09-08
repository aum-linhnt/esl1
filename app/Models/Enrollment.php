<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    // Moodle-like Course Context Roles
    public const ROLE_STUDENT = 'student';
    public const ROLE_TEACHER = 'teacher';
    public const ROLE_ASSISTANT = 'assistant';
    public const ROLE_MANAGER = 'manager';

    public static array $roleDefinitions = [
        self::ROLE_STUDENT => [
            'key' => 'student',
            'name' => 'Học viên (Student)',
            'icon' => '🎓',
            'badge_class' => 'bg-indigo-500/15 text-indigo-300 border-indigo-500/30',
            'desc' => 'Tham gia học tập, làm bài tập & kiểm tra, xem tiến độ cá nhân',
        ],
        self::ROLE_TEACHER => [
            'key' => 'teacher',
            'name' => 'Giáo viên phụ trách (Teacher)',
            'icon' => '👨‍🏫',
            'badge_class' => 'bg-purple-500/15 text-purple-300 border-purple-500/30',
            'desc' => 'Toàn quyền giảng dạy, tạo học liệu, chấm bài thi & xem sổ điểm lớp',
        ],
        self::ROLE_ASSISTANT => [
            'key' => 'assistant',
            'name' => 'Trợ giảng (Teaching Assistant)',
            'icon' => '🧑‍💼',
            'badge_class' => 'bg-amber-500/15 text-amber-300 border-amber-500/30',
            'desc' => 'Hỗ trợ học tập, chấm bài tập, giám sát tiến độ học viên',
        ],
        self::ROLE_MANAGER => [
            'key' => 'manager',
            'name' => 'Quản trị khóa học (Course Manager)',
            'icon' => '👑',
            'badge_class' => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30',
            'desc' => 'Quản trị toàn diện nội dung, học viên và cài đặt trong khóa học',
        ],
    ];

    protected $fillable = [
        'user_id', 'course_id', 'course_role', 'status',
        'enrolled_at', 'completed_at', 'expires_at',
        'final_grade', 'progress_percentage',
    ];

    protected function casts(): array
    {
        return [
            'enrolled_at' => 'datetime',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
            'final_grade' => 'decimal:2',
            'progress_percentage' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    // Enrolment Status Constants (Moodle-style)
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_DROPPED = 'dropped';

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isDropped(): bool
    {
        return $this->status === self::STATUS_DROPPED;
    }

    /**
     * Check if enrollment allows learning access (active/completed and not expired/suspended).
     */
    public function hasValidAccess(): bool
    {
        if ($this->isSuspended() || $this->isDropped()) {
            return false;
        }

        if ($this->isExpired()) {
            return false;
        }

        return true;
    }

    // Role check helpers in course context
    public function isCourseStudent(): bool
    {
        return $this->course_role === self::ROLE_STUDENT || empty($this->course_role);
    }

    public function isCourseTeacher(): bool
    {
        return $this->course_role === self::ROLE_TEACHER;
    }

    public function isCourseAssistant(): bool
    {
        return $this->course_role === self::ROLE_ASSISTANT;
    }

    public function isCourseManager(): bool
    {
        return $this->course_role === self::ROLE_MANAGER;
    }

    public function canManageCourseContent(): bool
    {
        return in_array($this->course_role, [self::ROLE_TEACHER, self::ROLE_MANAGER]);
    }

    public function canGradeStudents(): bool
    {
        return in_array($this->course_role, [self::ROLE_TEACHER, self::ROLE_ASSISTANT, self::ROLE_MANAGER]);
    }

    public function getRoleMetaAttribute(): array
    {
        return self::$roleDefinitions[$this->course_role] ?? self::$roleDefinitions[self::ROLE_STUDENT];
    }

    /**
     * Check if the enrollment has expired.
     */
    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false; // Unlimited access
        }

        return now()->isAfter($this->expires_at);
    }

    /**
     * Check if access is unlimited / indefinite.
     */
    public function isUnlimited(): bool
    {
        return $this->expires_at === null;
    }

    /**
     * Calculate remaining days of access.
     */
    public function remainingDays(): ?int
    {
        if ($this->expires_at === null) {
            return null;
        }

        if ($this->isExpired()) {
            return 0;
        }

        return (int) ceil(now()->diffInDays($this->expires_at, false));
    }

    /**
     * Get human-friendly expiry status info for UI badges.
     */
    public function getExpiryStatusAttribute(): array
    {
        if ($this->isUnlimited()) {
            return [
                'text' => 'Vô thời hạn (Unlimited)',
                'short_text' => 'Vô thời hạn',
                'badge_class' => 'bg-slate-800 text-gray-300 border-slate-700',
                'icon' => '♾️',
                'is_expired' => false,
            ];
        }

        if ($this->isExpired()) {
            return [
                'text' => 'Đã hết hạn (' . $this->expires_at->format('d/m/Y') . ')',
                'short_text' => 'Đã hết hạn',
                'badge_class' => 'bg-red-500/15 text-red-400 border-red-500/30',
                'icon' => '⚠️',
                'is_expired' => true,
            ];
        }

        $days = $this->remainingDays();
        if ($days <= 7) {
            return [
                'text' => 'Còn ' . $days . ' ngày (' . $this->expires_at->format('d/m/Y') . ')',
                'short_text' => 'Còn ' . $days . ' ngày',
                'badge_class' => 'bg-amber-500/15 text-amber-300 border-amber-500/30',
                'icon' => '⏳',
                'is_expired' => false,
            ];
        }

        return [
            'text' => 'Còn ' . $days . ' ngày (' . $this->expires_at->format('d/m/Y') . ')',
            'short_text' => 'Còn ' . $days . ' ngày',
            'badge_class' => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30',
            'icon' => '📅',
            'is_expired' => false,
        ];
    }
}
