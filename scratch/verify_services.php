<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Activity;
use App\Models\Enrollment;
use App\Models\ActivityCompletion;
use App\Models\UserProgress;
use App\Models\UserBadge;
use App\Services\CompletionService;
use App\Services\EnrollmentService;
use App\Services\GamificationService;

echo "=== START VERIFYING SERVICES ===\n\n";

// 1. Create a dummy test user
$testEmail = 'service_audit_' . time() . '@example.com';
$user = User::create([
    'name' => 'Service Audit User',
    'username' => 'service_audit_' . time(),
    'email' => $testEmail,
    'password' => bcrypt('password'),
    'role' => 'student',
    'status' => 'active',
    'xp' => 0,
    'coins' => 0,
    'streak_count' => 0,
]);
echo "1. Created Test User (ID: {$user->id})\n";

// 2. Find a course with lesson and activities
$course = Course::with(['lessons.activities'])->whereHas('lessons.activities')->first();
if (!$course) {
    echo "ERROR: No course found with lessons and activities!\n";
    exit(1);
}
echo "2. Found Course: {$course->title} (ID: {$course->id})\n";

$lesson = $course->lessons->first();
$activity = $lesson->activities->first();
echo "   Lesson: {$lesson->title} (ID: {$lesson->id})\n";
echo "   Activity: {$activity->title} (ID: {$activity->id})\n\n";

$enrollmentService = app(EnrollmentService::class);
$completionService = app(CompletionService::class);
$gamificationService = app(GamificationService::class);

// 3. Test Trial Check before Enrollment
$canCompleteTrial = $completionService->canComplete($user, $activity);
echo "3. canComplete without enrollment: " . ($canCompleteTrial['allowed'] ? 'ALLOWED' : 'BLOCKED (Trial Mode)') . "\n";
assert(!$canCompleteTrial['allowed'], 'Non-enrolled user must not be allowed to record official completion');

// 4. Test EnrollmentService::enrollUser
$enrollment = $enrollmentService->enrollUser($user, $course, 'student');
echo "4. Enrolled User via EnrollmentService (Status: {$enrollment->status}, Progress: {$enrollment->progress_percentage}%)\n";
assert($enrollment->status === 'active', 'Enrollment must be active');

// 5. Test EnrollmentService::toggleSuspend
$isNowActive = $enrollmentService->toggleSuspend($enrollment);
echo "5. Toggled Suspend -> isNowActive: " . ($isNowActive ? 'YES' : 'NO (Suspended)') . "\n";
assert(!$isNowActive, 'Enrollment should now be suspended');
$isNowActiveAgain = $enrollmentService->toggleSuspend($enrollment);
echo "   Toggled Again -> isNowActive: " . ($isNowActiveAgain ? 'YES (Active)' : 'NO') . "\n";
assert($isNowActiveAgain, 'Enrollment should now be active again');

// 6. Test CompletionService::completeActivity
$initialXp = $user->xp;
$result = $completionService->completeActivity($user, $activity, [
    'score' => 100,
    'max_score' => 100,
    'time_spent_seconds' => 45,
]);

echo "6. Completed Activity via CompletionService:\n";
echo "   - Success: " . ($result['success'] ? 'YES' : 'NO') . "\n";
echo "   - Message: {$result['message']}\n";
echo "   - Lesson Progress: {$result['lesson_progress']}%\n";
echo "   - Course Progress: {$result['course_progress']}%\n";
echo "   - XP Earned: {$result['reward']['xp_earned']}\n";
echo "   - Current Coins: {$result['reward']['current_coins']}\n";
echo "   - Streak: {$result['reward']['streak_count']}\n";
echo "   - New Badges Count: " . count($result['reward']['new_badges']) . "\n";

assert($result['success'] === true, 'Completion must succeed');
assert(ActivityCompletion::where('user_id', $user->id)->where('activity_id', $activity->id)->exists(), 'ActivityCompletion record must exist');
assert(UserProgress::where('user_id', $user->id)->where('lesson_id', $lesson->id)->exists(), 'UserProgress record must exist');

// 7. Test Lesson Completion Status Helper
$status = $completionService->getLessonCompletionStatus($user, $lesson);
echo "7. getLessonCompletionStatus:\n";
echo "   - Total Activities: {$status['total_activities']}\n";
echo "   - Completed Count: {$status['completed_count']}\n";
echo "   - Progress Percentage: {$status['progress_percentage']}%\n";
assert($status['completed_count'] >= 1, 'Completed count must be at least 1');

// 8. Cleanup test data
echo "\n8. Cleaning up test data...\n";
ActivityCompletion::where('user_id', $user->id)->delete();
UserProgress::where('user_id', $user->id)->delete();
UserBadge::where('user_id', $user->id)->delete();
Enrollment::where('user_id', $user->id)->delete();
$user->delete();

echo "\n=== ALL CHECKS PASSED SUCCESSFULLY! ===\n";
