<?php

namespace Tests\Feature;

use App\Models\{Activity, ActivityCompletion, AssignmentSubmission, Course, Enrollment, Lesson, QuizAttempt, User, UserProgress};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TeacherLearnerSupportTest extends TestCase
{
    use RefreshDatabase;

    private function course(User $teacher): Course
    {
        return Course::create(['title' => 'Khóa học hỗ trợ', 'slug' => Str::uuid(), 'created_by' => $teacher->id, 'passing_grade' => 50]);
    }

    private function activity(Lesson $lesson, string $title, string $type): Activity
    {
        return Activity::create(['lesson_id' => $lesson->id, 'title' => $title, 'type' => $type, 'order' => 1, 'is_visible' => true]);
    }

    private function quiz(Activity $activity, User $user, float $score, float $max, string $date, string $status = 'completed'): void
    {
        QuizAttempt::create(['user_id' => $user->id, 'activity_id' => $activity->id, 'score' => $score, 'max_score' => $max, 'status' => $status, 'completed_at' => $date]);
    }

    public function test_profile_uses_latest_assessments_distinguishes_zero_pending_and_unassessed_content(): void
    {
        $this->travelTo(now()->startOfDay()->addHours(12));
        $teacher = User::factory()->create(['role' => 'teacher']);
        $user = User::factory()->create();
        $course = $this->course($teacher);
        Enrollment::create(['course_id' => $course->id, 'user_id' => $user->id, 'course_role' => 'student', 'status' => 'active']);
        $lesson = Lesson::create(['course_id' => $course->id, 'title' => 'Bài học cần luyện', 'order' => 1, 'is_visible' => true]);
        $quiz = $this->activity($lesson, 'Quiz có kết quả mới', 'quiz');
        $zero = $this->activity($lesson, 'Quiz điểm 0', 'quiz');
        $video = $this->activity($lesson, 'Video chưa có đánh giá', 'video');
        $pending = $this->activity($lesson, 'Bài nộp đang chờ chấm', 'assignment');
        $graded = $this->activity($lesson, 'Bài tập đã chấm', 'assignment');
        $this->quiz($quiz, $user, 19, 20, now()->subDays(2)->toDateTimeString());
        $this->quiz($quiz, $user, 8, 20, now()->subDay()->toDateTimeString());
        $this->quiz($quiz, $user, 20, 20, now()->addDay()->toDateTimeString());
        $this->quiz($quiz, $user, 20, 20, now()->toDateTimeString(), 'in_progress');
        $this->quiz($zero, $user, 0, 10, now()->toDateTimeString());
        ActivityCompletion::create(['user_id' => $user->id, 'activity_id' => $video->id, 'lesson_id' => $lesson->id, 'score' => 100, 'max_score' => 100, 'completed_at' => now()]);
        UserProgress::create(['user_id' => $user->id, 'lesson_id' => $lesson->id, 'completed' => true, 'score' => 99]);
        AssignmentSubmission::create(['user_id' => $user->id, 'activity_id' => $pending->id, 'status' => 'graded', 'attempt_number' => 1, 'grade' => 90, 'submitted_at' => now()->subDays(2), 'graded_at' => now()->subDay()]);
        AssignmentSubmission::create(['user_id' => $user->id, 'activity_id' => $pending->id, 'status' => 'submitted', 'attempt_number' => 2, 'submitted_at' => now()]);
        AssignmentSubmission::create(['user_id' => $user->id, 'activity_id' => $graded->id, 'status' => 'graded', 'grade' => 68, 'submitted_at' => now()->subDay(), 'graded_at' => now()]);
        $response = $this->actingAs($teacher)->get('/teacher/ai-tutor?course_id='.$course->id.'&section=learners&learner_id='.$user->id);
        $response->assertOk()->assertSee('Hoạt động cần ôn thêm')->assertSee('Chờ chấm')->assertSee('40%')->assertSee('0%')->assertSee('Chưa có điểm');
        $profile = $response->viewData('learnerSupport');
        $items = $profile['lessons']->first()['activities']->keyBy(fn ($item) => $item['activity']->id);
        $this->assertSame(40.0, $items[$quiz->id]['score']);
        $this->assertSame(0.0, $items[$zero->id]['score']);
        $this->assertNull($items[$video->id]['score']);
        $this->assertFalse($items[$video->id]['needs_review']);
        $this->assertNull($items[$pending->id]['score']);
        $this->assertSame(1, $profile['pending_assignments']);
        $this->assertSame(1, $profile['completed_lessons']);
        $this->assertCount(3, $profile['weak']);
    }

    public function test_profile_excludes_other_learners_other_courses_and_private_submission_content(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $user = User::factory()->create();
        $other = User::factory()->create();
        $course = $this->course($teacher);
        $second = $this->course($teacher);
        Enrollment::create(['course_id' => $course->id, 'user_id' => $user->id, 'course_role' => 'student', 'status' => 'active']);
        Enrollment::create(['course_id' => $second->id, 'user_id' => $other->id, 'course_role' => 'student', 'status' => 'active']);
        $lesson = Lesson::create(['course_id' => $course->id, 'title' => 'Bài đúng khóa học', 'order' => 1]);
        $otherLesson = Lesson::create(['course_id' => $second->id, 'title' => 'Bài ngoài khóa học', 'order' => 1]);
        $a = $this->activity($lesson, 'Quiz chưa làm', 'quiz');
        $b = $this->activity($otherLesson, 'Quiz ngoài khóa học', 'quiz');
        $this->quiz($a, $other, 1, 10, now()->toDateTimeString());
        $this->quiz($b, $user, 2, 10, now()->toDateTimeString());
        $assignment = $this->activity($lesson, 'Bài tập', 'assignment');
        AssignmentSubmission::create(['user_id' => $user->id, 'activity_id' => $assignment->id, 'status' => 'graded', 'grade' => 80, 'submitted_at' => now(), 'graded_at' => now(), 'text_content' => 'Nội dung bài nộp riêng tư', 'feedback' => 'Phản hồi riêng tư']);
        $response = $this->actingAs($teacher)->get('/teacher/ai-tutor?course_id='.$course->id.'&section=learners&learner_id='.$user->id);
        $response->assertOk()->assertDontSee('Bài ngoài khóa học')->assertDontSee('Nội dung bài nộp riêng tư')->assertDontSee('Phản hồi riêng tư');
        $this->assertNull($response->viewData('learnerSupport')['lessons']->first()['activities']->first()['score']);
        $this->get('/teacher/ai-tutor?course_id='.$course->id.'&section=learners&learner_id='.$other->id)->assertOk()->assertViewHas('learnerSupport', null);
        $this->actingAs($user)->get('/teacher/ai-tutor?course_id='.$course->id.'&section=learners&learner_id='.$user->id)->assertForbidden();
    }
}
