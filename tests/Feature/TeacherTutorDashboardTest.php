<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use App\Services\Learning\TeacherTutorOverview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class TeacherTutorDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function course(User $teacher, string $title = 'Khóa học của tôi'): Course
    {
        return Course::create(['title' => $title, 'slug' => Str::uuid(), 'created_by' => $teacher->id, 'level' => 'B1', 'passing_grade' => 50]);
    }

    private function message(Course $course, User $user, string $question, string $status = 'completed', $time = null, string $lessonId = '1'): array
    {
        $conversation = (string) Str::uuid(); $id = (string) Str::uuid();
        $request = (string) Str::uuid(); $embedding = (string) Str::uuid();
        $time ??= now();
        DB::table('tutor_ai_conversations')->insert(['id' => $conversation, 'user_id' => (string) $user->id, 'course_id' => (string) $course->id, 'lesson_id' => $lessonId, 'teaching_mode' => 'socratic', 'created_at' => $time, 'updated_at' => $time]);
        DB::table('tutor_ai_conversation_messages')->insert(['id' => $id, 'conversation_id' => $conversation, 'idempotency_key' => $id, 'fingerprint' => str_repeat('a', 64), 'request_id' => $request, 'embedding_request_id' => $embedding, 'user_content' => $question, 'status' => $status, 'error_code' => $status === 'failed' ? 'AI_CREDIT_INSUFFICIENT' : null, 'created_at' => $time, 'updated_at' => $time]);
        return compact('id', 'request', 'embedding');
    }

    private function usage(string $request, string $currency, ?string $cost): void
    {
        DB::table('tutor_ai_requests')->insert(['request_id' => $request, 'idempotency_key' => $request, 'fingerprint' => str_repeat('b', 64), 'feature' => 'tutor_message', 'billing_mode' => 'customer_key', 'status' => 'completed', 'created_at' => now()]);
        DB::table('tutor_ai_usage_records')->insert(['request_id' => $request, 'feature' => 'tutor_message', 'billing_mode' => 'customer_key', 'provider' => 'test', 'model' => 'test', 'currency' => $currency, 'estimated_cost' => $cost, 'status' => 'completed', 'created_at' => now()]);
    }

    public function test_course_scope_is_enforced_for_view_details_and_export(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->course($teacher);
        $other = $this->course(User::factory()->create(['role' => 'teacher']), 'Khóa học riêng tư');
        $student = User::factory()->create();
        $this->message($other, $student, 'Câu hỏi riêng tư');
        $this->get('/teacher/ai-tutor')->assertRedirect('/login');
        $this->actingAs($teacher)->get('/teacher/ai-tutor')->assertOk()->assertSee('Quản lý Gia sư AI')->assertDontSee('Câu hỏi riêng tư')->assertDontSee('Khóa học riêng tư');
        foreach (['', '&section=questions', '&export=csv', '&section=learners&learner_id='.$student->id] as $extra) {
            $this->get('/teacher/ai-tutor?course_id='.$other->id.$extra)->assertForbidden();
        }
        $this->actingAs($student)->get('/teacher/ai-tutor?course_id='.$course->id)->assertForbidden();
        $this->get('/teacher/ai-tutor')->assertForbidden();
        $teacher->update(['status' => 'blocked']);
        $this->actingAs($teacher->fresh())->get('/teacher/ai-tutor')->assertForbidden();
    }

    public function test_assigned_course_manager_has_access_but_expired_and_assistant_do_not(): void
    {
        $owner = User::factory()->create(['role' => 'teacher']);
        $course = $this->course($owner);
        $user = User::factory()->create();
        $enrollment = Enrollment::create(['course_id' => $course->id, 'user_id' => $user->id, 'course_role' => 'manager', 'status' => 'active']);
        $this->actingAs($user)->get('/teacher/ai-tutor?course_id='.$course->id)->assertOk();
        $enrollment->update(['expires_at' => now()->subDay()]);
        $this->get('/teacher/ai-tutor?course_id='.$course->id)->assertForbidden();
        $enrollment->update(['expires_at' => null, 'course_role' => 'assistant']);
        $this->get('/teacher/ai-tutor?course_id='.$course->id)->assertForbidden();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/teacher/ai-tutor?course_id='.$course->id)->assertOk();
    }

    public function test_statistics_feedback_costs_and_support_table_use_real_course_data(): void
    {
        $this->travelTo(now()->startOfDay()->addHours(12));
        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->course($teacher);
        $student = User::factory()->create(['name' => 'Học viên cần trợ giúp']);
        Enrollment::create(['course_id' => $course->id, 'user_id' => $student->id, 'course_role' => 'student', 'status' => 'active', 'final_grade' => 45, 'progress_percentage' => 25]);
        $ungraded = User::factory()->create(['name' => 'Học viên chưa có điểm']);
        Enrollment::create(['course_id' => $course->id, 'user_id' => $ungraded->id, 'status' => 'active', 'course_role' => 'student']);
        $a = $this->message($course, $student, 'Làm sao học hiệu quả?');
        $b = $this->message($course, $student, 'Làm sao học hiệu quả?');
        $this->message($course, $student, 'Câu hỏi xử lý lỗi', 'failed');
        $this->message($course, $student, 'Câu hỏi kỳ trước', 'completed', now()->subDays(8));
        DB::table('tutor_ai_message_feedback')->insert([['message_id' => $a['id'], 'user_id' => (string) $student->id, 'rating' => 'helpful'], ['message_id' => $b['id'], 'user_id' => (string) $student->id, 'rating' => 'unhelpful']]);
        $this->usage($a['request'], 'USD', '1.000000');
        $this->usage($a['embedding'], 'USD', '0.250000');
        $this->usage($b['request'], 'VND', '20000.000000');
        $this->usage($b['embedding'], 'USD', null);
        $this->usage((string) Str::uuid(), 'USD', '999.000000'); // Unrelated usage is excluded.
        $report = app(TeacherTutorOverview::class)->report($course, 7);
        $this->assertSame(3, $report['questions']);
        $this->assertSame(1, $report['previousQuestions']);
        $this->assertEquals(50, $report['helpful']);
        $this->assertSame(1, $report['failed']);
        $this->assertSame(2, (int) $report['popular']->first()->total);
        $this->assertCount(1, $report['learners']);
        $this->assertEquals(1.25, $report['costs']->firstWhere('currency', 'USD')->cost);
        $this->assertEquals(1, $report['costs']->firstWhere('currency', 'USD')->unpriced);
        $this->assertEquals(20000, $report['costs']->firstWhere('currency', 'VND')->cost);
        $this->assertSame(3, array_sum(array_column($report['daily'], 'questions')));
        $this->actingAs($teacher)->get('/teacher/ai-tutor?course_id='.$course->id.'&days=7')->assertOk()->assertSee('50%')->assertSee('Học viên cần trợ giúp')->assertDontSee('Học viên chưa có điểm')->assertSee('1.250000')->assertDontSee('999.000000');
        $this->get('/teacher/ai-tutor?course_id='.$course->id.'&days=7&section=errors')->assertOk()->assertSee('AI_CREDIT_INSUFFICIENT');
        $this->get('/teacher/ai-tutor?course_id='.$course->id.'&days=7&section=learners&learner_id='.$ungraded->id)->assertOk()->assertSee('Chưa có điểm');
        $export = $this->get('/teacher/ai-tutor?course_id='.$course->id.'&days=7&export=csv')->assertOk()->assertDownload('ai-tutor-course-'.$course->id.'.csv');
        $this->assertStringContainsString('1.250000', $export->streamedContent());
        $this->assertStringNotContainsString('999.000000', $export->streamedContent());
    }

    public function test_local_day_boundaries_and_learner_details_exclude_other_courses(): void
    {
        config(['app.timezone' => 'UTC']);
        $this->travelTo(\Carbon\Carbon::parse('2026-10-06 03:00:00', 'UTC'));
        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->course($teacher);
        $otherCourse = $this->course($teacher, 'Khóa học thứ hai');
        $student = User::factory()->create();
        $this->message($course, $student, 'Đúng đầu kỳ', 'completed', '2026-09-29 17:00:00');
        $this->message($course, $student, 'Trước kỳ', 'completed', '2026-09-29 16:59:59');
        $this->message($course, $student, 'Trong tương lai', 'completed', '2026-10-06 03:00:01');
        Enrollment::create(['user_id' => $student->id, 'course_id' => $otherCourse->id, 'course_role' => 'student', 'status' => 'active', 'final_grade' => 40]);
        $report = app(TeacherTutorOverview::class)->report($course, 7);
        $this->assertSame(1, $report['questions']);
        $this->assertSame(1, $report['daily'][0]['questions']);
        $this->actingAs($teacher)->get('/teacher/ai-tutor?course_id='.$course->id.'&days=7&section=learners&learner_id='.$student->id)
            ->assertOk()->assertSee('Không có học viên phù hợp');
    }

    public function test_inline_policy_selects_one_lesson_saves_validated_fields_and_preserves_period(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->course($teacher);
        $first = Lesson::create(['course_id' => $course->id, 'title' => 'Bài thứ nhất', 'order' => 1]);
        $second = Lesson::create(['course_id' => $course->id, 'title' => 'Bài thứ hai', 'order' => 2]);
        $url = route('teacher.ai-tutor.policy.update', [$course->id, $second->id]);
        $this->actingAs($teacher)->get('/teacher/ai-tutor?course_id='.$course->id.'&lesson_id='.$second->id.'&days=7')
            ->assertOk()->assertSee($url, false)->assertSee('Lưu cấu hình');
        $this->put($url, ['ai_answer_policy' => 'teacher_controlled', 'ai_teacher_solution_allowed' => '1', 'ai_exam_mode' => '1', 'days' => 7, 'title' => 'Tên không được phép'])
            ->assertRedirect('/teacher/ai-tutor?course_id='.$course->id.'&lesson_id='.$second->id.'&days=7#course-config')->assertSessionHas('tutor-policy-saved');
        $this->assertSame('Bài thứ hai', $second->fresh()->title);
        $this->assertTrue($second->fresh()->ai_exam_mode);
        $this->assertTrue($second->fresh()->ai_teacher_solution_allowed);
        $this->assertSame('hints_only', $first->fresh()->ai_answer_policy);
        $this->assertSame('no_answer', app(\TDSoft\AiTutor\Conversations\TeachingPolicy::class)->resolve('socratic', $second->fresh()->ai_answer_policy, true, true));
        $this->get('/teacher/ai-tutor?course_id='.$course->id.'&lesson_id='.$second->id.'&days=7')->assertOk()->assertSee('Đã lưu chính sách');
        $this->put($url, ['ai_answer_policy' => 'hints_only', 'ai_teacher_solution_allowed' => '0', 'ai_exam_mode' => '0', 'days' => 30])->assertRedirect();
        $this->assertFalse($second->fresh()->ai_exam_mode);
        $this->assertFalse($second->fresh()->ai_teacher_solution_allowed);
    }

    public function test_inline_policy_rejects_invalid_data_and_cross_course_access(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->course($teacher);
        $lesson = Lesson::create(['course_id' => $course->id, 'title' => 'Chính sách an toàn', 'order' => 1]);
        $other = $this->course(User::factory()->create(['role' => 'teacher']));
        $otherLesson = Lesson::create(['course_id' => $other->id, 'title' => 'Bài học riêng tư', 'order' => 1]);
        $url = route('teacher.ai-tutor.policy.update', [$course->id, $lesson->id]);
        $valid = ['ai_answer_policy' => 'full_solution', 'ai_teacher_solution_allowed' => 1, 'ai_exam_mode' => 0];
        $this->put($url, $valid)->assertRedirect('/login');
        $this->actingAs($teacher)->put($url, ['ai_answer_policy' => ['invalid'], 'ai_teacher_solution_allowed' => 'invalid', 'ai_exam_mode' => 'invalid', 'days' => ['invalid']])
            ->assertSessionHasErrorsIn('tutorPolicy', ['ai_answer_policy', 'ai_teacher_solution_allowed', 'ai_exam_mode', 'days'])
            ->assertRedirect('/teacher/ai-tutor?course_id='.$course->id.'&lesson_id='.$lesson->id.'&days=30#course-config');
        $this->get('/teacher/ai-tutor?course_id='.$course->id.'&lesson_id='.$lesson->id)->assertOk();
        $this->assertSame('hints_only', $lesson->fresh()->ai_answer_policy);
        $this->put(route('teacher.ai-tutor.policy.update', [$course->id, $otherLesson->id]), $valid)->assertForbidden();
        $this->put(route('teacher.ai-tutor.policy.update', [$other->id, $otherLesson->id]), $valid)->assertForbidden();
        $this->get('/teacher/ai-tutor?course_id='.$course->id.'&lesson_id='.$otherLesson->id)->assertNotFound();
        $this->actingAs(User::factory()->create())->put($url, $valid)->assertForbidden();
        $teacher->update(['status' => 'blocked']);
        $this->actingAs($teacher->fresh())->put($url, $valid)->assertForbidden();
        $this->assertSame('hints_only', $otherLesson->fresh()->ai_answer_policy);
    }

    public function test_failure_filters_guide_to_related_lesson_without_exposing_other_courses(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->course($teacher);
        $lesson = Lesson::create(['course_id' => $course->id, 'title' => 'Nguồn học liệu', 'order' => 1]);
        $second = Lesson::create(['course_id' => $course->id, 'title' => 'Bài học khác', 'order' => 2]);
        $student = User::factory()->create();
        $a = $this->message($course, $student, 'Câu hỏi cần kiểm tra nguồn', 'failed', null, (string) $lesson->id);
        DB::table('tutor_ai_conversation_messages')->where('id', $a['id'])->update(['error_code' => 'AI_SOURCE_NOT_FOUND']);
        $this->message($course, $student, 'Thiếu credit bài khác', 'failed', null, (string) $second->id);
        $this->message($course, $student, 'Đã trả lời thành công', 'completed', null, (string) $lesson->id);
        $other = $this->course(User::factory()->create(['role' => 'teacher']));
        $otherLesson = Lesson::create(['course_id' => $other->id, 'title' => 'Bài học bí mật', 'order' => 1]);
        $this->message($other, $student, 'Câu hỏi bí mật', 'failed', null, (string) $otherLesson->id);
        $url = '/teacher/ai-tutor?course_id='.$course->id.'&section=errors';
        $this->actingAs($teacher)->get($url.'&error_code=AI_SOURCE_NOT_FOUND&error_lesson_id='.$lesson->id)->assertOk()
            ->assertViewHas('detail', fn ($detail) => $detail->total() === 1 && $detail->first()->lesson->id === $lesson->id)
            ->assertSee('Nguồn kiến thức cần kiểm tra')->assertSee('Cấu hình AI bài học')->assertDontSee('Câu hỏi bí mật')->assertDontSee('Quản lý kiến thức');
        $this->get($url.'&error_code=AI_SOURCE_NOT_FOUND&error_lesson_id='.$second->id)->assertOk()
            ->assertViewHas('detail', fn ($detail) => $detail->total() === 0)->assertSee('Không có yêu cầu lỗi phù hợp');
        $this->get($url.'&error_lesson_id='.$otherLesson->id)->assertNotFound();
        $this->get($url.'&error_code[]=AI_SOURCE_NOT_FOUND')->assertSessionHasErrors('error_code');
        $this->get($url.'&error_lesson_id[]=1')->assertSessionHasErrors('error_lesson_id');
        $this->assertDatabaseHas('tutor_ai_conversation_messages', ['id' => $a['id'], 'status' => 'failed']);
        $this->assertDatabaseCount('tutor_ai_requests', 0);
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get($url.'&error_code=AI_SOURCE_NOT_FOUND')->assertOk()->assertSee('Quản lý kiến thức');
    }

    public function test_unknown_failure_and_removed_lesson_are_safe_and_filters_survive_pagination(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->course($teacher);
        $student = User::factory()->create();
        for ($i = 0; $i < 21; $i++) {
            $message = $this->message($course, $student, '<script>evil()</script> '.$i, 'failed', null, '999999');
            DB::table('tutor_ai_conversation_messages')->where('id', $message['id'])->update(['error_code' => 'AI_NEW_FAILURE']);
        }
        $this->actingAs($teacher)->get('/teacher/ai-tutor?course_id='.$course->id.'&section=errors&days=7&error_code=AI_NEW_FAILURE')
            ->assertOk()->assertViewHas('detail', fn ($detail) => $detail->total() === 21 && $detail->first()->lesson === null)
            ->assertSee('Yêu cầu xử lý chưa thành công')->assertSee('Bài học không còn trong khóa học')
            ->assertDontSee('<script>evil()', false)->assertSee('error_code=AI_NEW_FAILURE', false)->assertSee('page=2', false);
        $this->get('/teacher/ai-tutor?course_id='.$course->id.'&section=errors&days=7&error_code=AI_NEW_FAILURE&page=2')
            ->assertOk()->assertViewHas('detail', fn ($detail) => $detail->count() === 1);
    }

    public function test_empty_dashboard_and_filters_have_explicit_states(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $this->actingAs($teacher)->get('/teacher/ai-tutor')->assertOk()->assertSee('Chưa có khóa học để quản lý');
        $course = $this->course($teacher);
        $lesson = Lesson::create(['course_id' => $course->id, 'title' => 'Bài học đầu', 'order' => 1, 'ai_exam_mode' => true]);
        $this->get('/teacher/ai-tutor?course_id='.$course->id)->assertOk()->assertSee('Chưa có câu hỏi')->assertSee('Chưa có dữ liệu chi phí')->assertSee('Bài học đầu')->assertSee(route('courses.lessons.ai-policy.edit', [$course->id, $lesson->id]), false);
        $this->get('/teacher/ai-tutor?days=365')->assertSessionHasErrors('days');
        $this->get('/teacher/ai-tutor?course_id[]=1')->assertSessionHasErrors('course_id');
    }
}
