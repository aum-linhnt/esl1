<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Carbon\Carbon;

#[Fillable([
    'name', 'username', 'email', 'password', 'role', 'status', 'avatar',
    'coins', 'xp', 'streak_count', 'last_active_date', 'current_level',
    'trial_ends_at', 'phone', 'birthday', 'gender', 'address', 'city',
    'school_workplace', 'target_level', 'bio', 'facebook_url'
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'trial_ends_at' => 'datetime',
            'last_active_date' => 'date',
            'birthday' => 'date',
            'coins' => 'integer',
            'xp' => 'integer',
            'streak_count' => 'integer',
        ];
    }

    public function isTrialExpired(): bool
    {
        return $this->status === 'trial_expired';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    /**
     * Record daily study activity to increment or maintain learning streak and award bonus XP.
     */
    public function recordDailyStudy(): array
    {
        $today = Carbon::today()->startOfDay();
        $bonusXp = 0;
        $streakIncremented = false;

        if (!$this->last_active_date) {
            $this->streak_count = 1;
            $this->last_active_date = $today;
            $streakIncremented = true;
            $bonusXp = 20;
        } else {
            $lastDate = Carbon::parse($this->last_active_date)->startOfDay();

            // Already recorded today -> keep current streak
            if ($lastDate->isSameDay($today)) {
                return [
                    'streak_count' => $this->streak_count,
                    'streak_incremented' => false,
                    'bonus_xp' => 0,
                    'total_xp' => $this->xp,
                ];
            }

            // Check if last active was yesterday (continuous streak)
            $isYesterday = $lastDate->copy()->addDay()->isSameDay($today) || ((int) $lastDate->diffInDays($today) === 1);

            if ($isYesterday) {
                // Studied yesterday -> Continue and increase streak!
                $this->streak_count = max(1, (int)$this->streak_count) + 1;
                $this->last_active_date = $today;
                $streakIncremented = true;
                $bonusXp = 20 + min($this->streak_count * 5, 50); // Streak multiplier
            } else {
                // Missed 1 or more days -> Reset streak to 1
                $this->streak_count = 1;
                $this->last_active_date = $today;
                $streakIncremented = true;
                $bonusXp = 10;
            }
        }

        if ($bonusXp > 0) {
            $this->xp += $bonusXp;
        }

        $this->save();

        return [
            'streak_count' => $this->streak_count,
            'streak_incremented' => $streakIncremented,
            'bonus_xp' => $bonusXp,
            'total_xp' => $this->xp,
        ];
    }

    public function addXp(int $amount): void
    {
        $this->increment('xp', $amount);
    }

    public function createdCourses(): HasMany
    {
        return $this->hasMany(Course::class, 'created_by');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(UserProgress::class);
    }

    public function skills(): HasMany
    {
        return $this->hasMany(LearnerSkill::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(UserActivityLog::class);
    }

    public function assessmentSubmissions(): HasMany
    {
        return $this->hasMany(AssessmentSubmission::class);
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function adaptiveSessions(): HasMany
    {
        return $this->hasMany(AdaptiveTestSession::class);
    }

    public function badges(): HasMany
    {
        return $this->hasMany(UserBadge::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function enrolledCourses()
    {
        return $this->belongsToMany(Course::class, 'enrollments')
            ->withPivot('status', 'enrolled_at', 'completed_at', 'final_grade', 'progress_percentage')
            ->withTimestamps();
    }

    public function activityCompletions(): HasMany
    {
        return $this->hasMany(ActivityCompletion::class);
    }

    public function isEnrolledIn(int $courseId): bool
    {
        return $this->enrollments()
            ->where('course_id', $courseId)
            ->whereIn('status', ['active', 'completed'])
            ->exists();
    }

    public function getEnrollment(int $courseId): ?Enrollment
    {
        return $this->enrollments()->where('course_id', $courseId)->first();
    }

    public function getLessonProgress(int $lessonId): ?UserProgress
    {
        return $this->progress()->where('lesson_id', $lessonId)->first();
    }

    /**
     * Get user avatar URL with automatic fallback and storage path resolution.
     */
    public function getAvatarUrlAttribute(): string
    {
        $defaultAvatar = asset('images/default-avatar.svg');

        if (empty($this->avatar)) {
            return $defaultAvatar;
        }

        if (str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://')) {
            return $this->avatar;
        }

        $path = ltrim($this->avatar, '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, 8);
        }

        // Verify that the file actually exists on disk before attempting to serve it
        $storageDiskPath = storage_path('app/public/' . $path);
        $publicDirectPath = public_path('storage/' . $path);

        if (file_exists($storageDiskPath) || file_exists($publicDirectPath)) {
            return asset('storage/' . $path);
        }

        return $defaultAvatar;
    }
}
