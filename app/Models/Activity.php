<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Activity extends Model
{
    // ─── Activity Type Constants ───
    const TYPE_VOCABULARY      = 'vocabulary';
    const TYPE_GRAMMAR         = 'grammar';
    const TYPE_VIDEO           = 'video';
    const TYPE_QUIZ            = 'quiz';
    const TYPE_AUDIO_LISTENING = 'audio_listening';
    const TYPE_PDF_DOCUMENT    = 'pdf_document';
    const TYPE_AI_SPEAKING     = 'ai_speaking';
    const TYPE_AI_WRITING      = 'ai_writing';
    const TYPE_FILE            = 'file';
    const TYPE_URL             = 'url';
    const TYPE_TEXT_PAGE       = 'text_page';
    const TYPE_ASSIGNMENT      = 'assignment';
    const TYPE_FORUM           = 'forum';
    const TYPE_LABEL           = 'label';

    // ─── Completion Type Constants ───
    const COMPLETION_MANUAL      = 'manual';
    const COMPLETION_AUTO_VIEW   = 'auto_view';
    const COMPLETION_AUTO_GRADE  = 'auto_grade';
    const COMPLETION_AUTO_SUBMIT = 'auto_submit';

    /**
     * Registry of all supported activity types with metadata.
     */
    public static array $typeRegistry = [
        self::TYPE_VOCABULARY => [
            'icon' => '📖', 'label' => 'Flashcard Từ vựng', 'category' => 'content',
            'color' => 'purple', 'description' => 'Bộ thẻ từ vựng tương tác với phiên âm và ví dụ',
        ],
        self::TYPE_GRAMMAR => [
            'icon' => '📐', 'label' => 'Bài giảng Ngữ pháp', 'category' => 'content',
            'color' => 'blue', 'description' => 'Giải thích quy tắc ngữ pháp kèm ví dụ minh họa',
        ],
        self::TYPE_VIDEO => [
            'icon' => '🎬', 'label' => 'Video bài giảng', 'category' => 'content',
            'color' => 'red', 'description' => 'Video YouTube hoặc URL trực tiếp',
        ],
        self::TYPE_AUDIO_LISTENING => [
            'icon' => '🎧', 'label' => 'Audio / Podcast', 'category' => 'content',
            'color' => 'teal', 'description' => 'File audio nghe hiểu kèm transcript',
        ],
        self::TYPE_PDF_DOCUMENT => [
            'icon' => '📑', 'label' => 'Tài liệu / Slide', 'category' => 'content',
            'color' => 'amber', 'description' => 'Hiển thị PDF hoặc slide bài giảng',
        ],
        self::TYPE_TEXT_PAGE => [
            'icon' => '📝', 'label' => 'Trang nội dung', 'category' => 'content',
            'color' => 'slate', 'description' => 'Trang văn bản/HTML nội dung tùy chỉnh',
        ],
        self::TYPE_FILE => [
            'icon' => '📁', 'label' => 'Tải lên File', 'category' => 'resource',
            'color' => 'orange', 'description' => 'File tài liệu bất kỳ (PDF, DOCX, PPTX, ZIP...)',
        ],
        self::TYPE_URL => [
            'icon' => '🔗', 'label' => 'Link tài nguyên', 'category' => 'resource',
            'color' => 'cyan', 'description' => 'Liên kết đến tài nguyên bên ngoài',
        ],
        self::TYPE_AI_SPEAKING => [
            'icon' => '🎙️', 'label' => 'AI Luyện nói', 'category' => 'ai',
            'color' => 'emerald', 'description' => 'Luyện phát âm với AI chấm điểm real-time',
        ],
        self::TYPE_AI_WRITING => [
            'icon' => '✍️', 'label' => 'AI Luyện viết', 'category' => 'ai',
            'color' => 'indigo', 'description' => 'Viết bài luận với AI chấm và phản hồi',
        ],
        self::TYPE_QUIZ => [
            'icon' => '🎯', 'label' => 'Bài kiểm tra / Quiz', 'category' => 'assessment',
            'color' => 'green', 'description' => 'Quiz trắc nghiệm tự động chấm điểm',
        ],
        self::TYPE_ASSIGNMENT => [
            'icon' => '📋', 'label' => 'Bài tập nộp bài', 'category' => 'assessment',
            'color' => 'rose', 'description' => 'Học viên nộp file bài tập để giáo viên chấm',
        ],
        self::TYPE_FORUM => [
            'icon' => '💬', 'label' => 'Diễn đàn thảo luận', 'category' => 'collaboration',
            'color' => 'violet', 'description' => 'Khu vực thảo luận nhóm cho học viên',
        ],
        self::TYPE_LABEL => [
            'icon' => '🏷️', 'label' => 'Nhãn / Tiêu đề', 'category' => 'structure',
            'color' => 'gray', 'description' => 'Nhãn phân cách hoặc tiêu đề nhóm (chỉ hiển thị)',
        ],
    ];

    /**
     * Completion type definitions.
     */
    public static array $completionTypes = [
        self::COMPLETION_MANUAL      => ['label' => 'Thủ công (Học viên tự đánh dấu)', 'icon' => '✋'],
        self::COMPLETION_AUTO_VIEW   => ['label' => 'Tự động khi xem xong', 'icon' => '👁️'],
        self::COMPLETION_AUTO_GRADE  => ['label' => 'Tự động khi đạt điểm tối thiểu', 'icon' => '🎯'],
        self::COMPLETION_AUTO_SUBMIT => ['label' => 'Tự động khi nộp bài', 'icon' => '📤'],
    ];

    protected $fillable = [
        'lesson_id', 'title', 'description', 'type', 'content', 'order', 'estimated_minutes',
        'is_visible', 'is_free_trial', 'available_from', 'available_until',
        'completion_type', 'passing_grade', 'max_attempts', 'time_limit_minutes', 'grading_method',
        'file_id',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'order' => 'integer',
            'is_visible' => 'boolean',
            'is_free_trial' => 'boolean',
            'available_from' => 'datetime',
            'available_until' => 'datetime',
            'passing_grade' => 'decimal:2',
            'max_attempts' => 'integer',
            'time_limit_minutes' => 'integer',
            'grading_method' => 'string',
        ];
    }

    /**
     * Ensure content is always returned as array, even if double-encoded or string.
     */
    protected function content(): Attribute
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

    // ─── Relationships ───

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function completions(): HasMany
    {
        return $this->hasMany(ActivityCompletion::class);
    }

    public function assignmentSubmissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class)->orderBy('attempt_number', 'asc');
    }

    /**
     * Get all attempts of a specific user.
     */
    public function getUserAttempts(int $userId)
    {
        return $this->quizAttempts()->where('user_id', $userId)->get();
    }

    /**
     * Get latest attempt of a user.
     */
    public function getUserLatestAttempt(int $userId): ?QuizAttempt
    {
        return $this->quizAttempts()->where('user_id', $userId)->latest('attempt_number')->first();
    }

    /**
     * Get highest scoring attempt of a user.
     */
    public function getUserHighestAttempt(int $userId): ?QuizAttempt
    {
        return $this->quizAttempts()->where('user_id', $userId)->orderByDesc('score')->first();
    }

    /**
     * Check if user can make a new attempt based on max_attempts.
     */
    public function canUserAttempt(int $userId): bool
    {
        $max = (int) ($this->max_attempts ?? 0);
        if ($max <= 0) {
            return true; // Unlimited
        }
        $count = $this->quizAttempts()->where('user_id', $userId)->where('status', QuizAttempt::STATUS_COMPLETED)->count();
        return $count < $max;
    }

    /**
     * Get remaining attempts count (null if unlimited).
     */
    public function getRemainingAttempts(int $userId): ?int
    {
        $max = (int) ($this->max_attempts ?? 0);
        if ($max <= 0) {
            return null; // Unlimited
        }
        $count = $this->quizAttempts()->where('user_id', $userId)->where('status', QuizAttempt::STATUS_COMPLETED)->count();
        return max(0, $max - $count);
    }

    /**
     * Calculate aggregate score based on grading_method (highest, last, average, first).
     */
    public function calculateGradingMethodScore(int $userId): float
    {
        $attempts = $this->quizAttempts()
            ->where('user_id', $userId)
            ->where('status', QuizAttempt::STATUS_COMPLETED)
            ->get();

        if ($attempts->isEmpty()) {
            return 0.0;
        }

        $method = $this->grading_method ?: 'highest';

        switch ($method) {
            case 'last':
                return (float) $attempts->last()->score;
            case 'first':
                return (float) $attempts->first()->score;
            case 'average':
                return round((float) $attempts->avg('score'), 2);
            case 'highest':
            default:
                return (float) $attempts->max('score');
        }
    }

    // ─── Helper Methods ───

    /**
     * Get type metadata from the registry.
     */
    public function getTypeInfo(): array
    {
        return self::$typeRegistry[$this->type] ?? [
            'icon' => '📄', 'label' => ucfirst($this->type), 'category' => 'other',
            'color' => 'slate', 'description' => '',
        ];
    }

    /**
     * Check if the activity is currently available (within time window).
     */
    public function isAvailable(): bool
    {
        $now = now();

        if ($this->available_from && $now->lt($this->available_from)) {
            return false; // Not yet open
        }

        if ($this->available_until && $now->gt($this->available_until)) {
            return false; // Past deadline
        }

        return true;
    }

    /**
     * Get availability status label.
     */
    public function getAvailabilityStatus(): string
    {
        if (!$this->is_visible) {
            return 'hidden';
        }

        $now = now();

        if ($this->available_from && $now->lt($this->available_from)) {
            return 'not_yet'; // scheduled but not open
        }

        if ($this->available_until && $now->gt($this->available_until)) {
            return 'expired'; // past deadline
        }

        return 'available';
    }

    /**
     * Check if a user has completed this activity.
     */
    public function isCompletedBy(int $userId): bool
    {
        return $this->completions()->where('user_id', $userId)->exists();
    }

    /**
     * Check if this is a label (non-interactive, display-only).
     */
    public function isLabel(): bool
    {
        return $this->type === self::TYPE_LABEL;
    }

    /**
     * Check if this activity has a file attached.
     */
    public function hasFile(): bool
    {
        return $this->file_id !== null;
    }

    /**
     * Get human-readable file size.
     */
    public function getFileSizeFormatted(): string
    {
        if (!$this->file) return '';
        return $this->file->getSizeFormatted();
    }

    /**
     * Get the file URL (if attached).
     */
    public function getFileUrl(): ?string
    {
        return $this->file?->getUrl();
    }

    /**
     * Get the original file name (if attached).
     */
    public function getFileOriginalName(): ?string
    {
        return $this->file?->original_name;
    }

    /**
     * Check if this activity is accessible as a free trial.
     */
    public function isTrial(): bool
    {
        return (bool) ($this->is_free_trial || ($this->lesson && $this->lesson->is_free_trial));
    }
}
