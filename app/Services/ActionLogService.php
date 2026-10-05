<?php

namespace App\Services;

use App\Models\UserActionLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class ActionLogService
{
    /**
     * Record a user action into database.
     */
    public function record(
        string $action,
        User $user,
        ?Model $loggable = null,
        ?string $description = null,
        array $metadata = []
    ): UserActionLog {
        $data = [
            'user_id'     => $user->id,
            'action'      => $action,
            'description' => $description,
            'ip_address'  => Request::ip(),
            'user_agent'  => substr(Request::userAgent() ?? '', 0, 500),
            'metadata'    => !empty($metadata) ? $metadata : null,
            'created_at'  => now(),
        ];

        if ($loggable) {
            $data['loggable_type'] = get_class($loggable);
            $data['loggable_id'] = $loggable->getKey();
        }

        return UserActionLog::create($data);
    }

    // ─── Static Facade API (for DI and static callers) ───

    public static function log(
        string $action,
        User $user,
        ?Model $loggable = null,
        ?string $description = null,
        array $metadata = []
    ): UserActionLog {
        return app(self::class)->record($action, $user, $loggable, $description, $metadata);
    }

    public static function logLogin(User $user): UserActionLog
    {
        return self::log('login', $user, null, "Đăng nhập hệ thống");
    }

    public static function logLogout(User $user): UserActionLog
    {
        return self::log('logout', $user, null, "Đăng xuất hệ thống");
    }

    public static function logViewCourse(User $user, Model $course): UserActionLog
    {
        return self::log('view_course', $user, $course, "Truy cập khóa học: {$course->title}");
    }

    public static function logViewLesson(User $user, Model $lesson): UserActionLog
    {
        return self::log('view_lesson', $user, $lesson, "Truy cập bài học: {$lesson->title}");
    }

    public static function logViewActivity(User $user, Model $activity): UserActionLog
    {
        $desc = "Truy cập hoạt động: {$activity->title}";
        if ($activity->lesson) {
            $desc .= " (Bài: {$activity->lesson->title})";
        }
        return self::log('view_activity', $user, $activity, $desc);
    }

    public static function logViewExam(User $user, Model $exam, ?string $testKey = null): UserActionLog
    {
        $desc = "Xem đề thi: {$exam->title}";
        return self::log('view_exam', $user, $exam, $desc, $testKey ? ['test_key' => $testKey] : []);
    }

    public static function logSubmitExam(User $user, Model $exam, ?string $testKey = null, ?float $score = null): UserActionLog
    {
        $desc = "Nộp bài thi: {$exam->title}";
        $meta = [];
        if ($testKey) $meta['test_key'] = $testKey;
        if ($score !== null) $meta['score'] = $score;
        return self::log('submit_exam', $user, $exam, $desc, $meta);
    }

    public static function logEnroll(User $user, Model $course): UserActionLog
    {
        return self::log('enroll', $user, $course, "Ghi danh khóa học: {$course->title}");
    }

    public static function logUnenroll(User $user, Model $course): UserActionLog
    {
        return self::log('unenroll', $user, $course, "Hủy ghi danh: {$course->title}");
    }

    public static function logCompleteActivity(User $user, Model $activity): UserActionLog
    {
        return self::log('complete_activity', $user, $activity, "Hoàn thành hoạt động: {$activity->title}");
    }
}
