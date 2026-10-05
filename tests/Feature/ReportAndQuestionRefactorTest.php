<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityCompletion;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\QuestionBank;
use App\Models\User;
use App\Services\Assessment\QuestionVersioningService;
use App\Services\Reports\CompletionReportService;
use App\Services\Reports\GradeMatrixService;
use App\Services\Reports\ReportCsvExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ReportAndQuestionRefactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_grade_matrix_service_computes_matrix_and_letter_grades(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Nguyễn Văn A']);

        $course = Course::create([
            'title' => 'Advanced English',
            'slug' => 'advanced-english',
            'created_by' => $teacher->id,
        ]);

        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson 1',
            'order' => 1,
        ]);

        $activity = Activity::create([
            'lesson_id' => $lesson->id,
            'title' => 'Quiz 1',
            'type' => 'quiz',
            'order' => 1,
            'passing_grade' => 60,
        ]);

        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'active',
            'course_role' => 'student',
        ]);

        ActivityCompletion::create([
            'user_id' => $student->id,
            'activity_id' => $activity->id,
            'lesson_id' => $lesson->id,
            'score' => 85,
            'max_score' => 100,
            'completed_at' => now(),
            'time_spent_seconds' => 120,
        ]);

        /** @var GradeMatrixService $service */
        $service = app(GradeMatrixService::class);
        $result = $service->buildCourseGradeMatrix($course);

        $this->assertSame('B', $service->getLetterGrade(85));
        $this->assertSame('A', $service->getLetterGrade(95));
        $this->assertSame('F', $service->getLetterGrade(40));

        $this->assertSame(1, $result['total_enrolled']);
        $this->assertSame(85.0, $result['class_avg_score']);
        $this->assertCount(1, $result['grade_matrix']);
        $this->assertSame('B', $result['grade_matrix'][0]['letter_grade']);
    }

    public function test_completion_report_service_computes_stats(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);

        $course = Course::create([
            'title' => 'IELTS Reading',
            'slug' => 'ielts-reading',
            'created_by' => $teacher->id,
        ]);

        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Passage 1',
            'order' => 1,
        ]);

        $activity = Activity::create([
            'lesson_id' => $lesson->id,
            'title' => 'True False Not Given',
            'type' => 'quiz',
            'order' => 1,
        ]);

        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'active',
            'course_role' => 'student',
        ]);

        ActivityCompletion::create([
            'user_id' => $student->id,
            'activity_id' => $activity->id,
            'lesson_id' => $lesson->id,
            'completed_at' => now(),
            'time_spent_seconds' => 300,
            'score' => 100,
        ]);

        /** @var CompletionReportService $service */
        $service = app(CompletionReportService::class);
        $stats = $service->buildCourseCompletionStats($course);

        $this->assertSame(1, $stats['total_enrolled']);
        $this->assertSame(1, $stats['total_activities']);
        $this->assertSame(100.0, $stats['overall_completion_rate']);
        $this->assertSame(1, $stats['fully_completed_students']);
    }

    public function test_question_versioning_service_tracks_versions_and_syncs_clusters(): void
    {
        $question = QuestionBank::create([
            'skill' => 'reading',
            'difficulty' => 'B1',
            'question_type' => 'mcq',
            'question_text' => 'What is the main topic?',
            'correct_answer' => 'A. Nature',
            'version' => 1,
            'meta_data' => [
                'passage_title' => 'Ancient Egypt',
                'passage_content' => 'Old content about pyramids.',
            ],
        ]);

        /** @var QuestionVersioningService $service */
        $service = app(QuestionVersioningService::class);

        // Update with changed text -> should create new version v2
        $updated = $service->updateWithVersioning($question, [
            'question_text' => 'What is the primary theme?',
            'correct_answer' => 'A. Nature',
            'difficulty' => 'B1',
        ], true);

        $this->assertSame(2, $updated->version);

        // Check version history has both current and archived version
        $history = $service->getVersionHistory($question->id);
        $this->assertCount(2, $history);

        // Test synchronizing passage across cluster
        $childQuestion = QuestionBank::create([
            'skill' => 'reading',
            'difficulty' => 'B1',
            'question_type' => 'mcq',
            'question_text' => 'Where is Cairo located?',
            'correct_answer' => 'B. Egypt',
            'meta_data' => [
                'passage_title' => 'Ancient Egypt',
                'passage_content' => 'Old content about pyramids.',
            ],
        ]);

        $syncedCount = $service->syncPassageCluster(
            'Ancient Egypt',
            'Ancient Egypt & Nile',
            'Updated content with Nile river.'
        );

        $this->assertGreaterThanOrEqual(1, $syncedCount);

        $childQuestion->refresh();
        $this->assertSame('Ancient Egypt & Nile', $childQuestion->meta_data['passage_title']);
        $this->assertSame('Updated content with Nile river.', $childQuestion->meta_data['passage_content']);
    }

    public function test_admin_reports_endpoint_renders_successfully(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.reports.index'));
        $response->assertOk();
        $response->assertViewIs('admin.reports.index');

        $responseEnrollments = $this->actingAs($admin)->get(route('admin.reports.enrollments'));
        $responseEnrollments->assertOk();
        $responseEnrollments->assertViewIs('admin.reports.enrollments');

        $responseActivityGrades = $this->actingAs($admin)->get(route('admin.reports.activity_grades'));
        $responseActivityGrades->assertOk();
        $responseActivityGrades->assertViewIs('admin.reports.activity_grades');

        $responseCompletions = $this->actingAs($admin)->get(route('admin.reports.completions'));
        $responseCompletions->assertOk();
        $responseCompletions->assertViewIs('admin.reports.completions');
    }
}
