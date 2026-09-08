<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseCurriculumStudioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_create_new_course_with_metadata(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->post('/admin/courses', [
            'title' => 'IELTS Intensive B2',
            'level' => 'B2',
            'target_audience' => 'Sinh viên luyện thi IELTS 6.5+',
            'description' => 'Khóa học tập trung kỹ năng học thuật cao cấp',
            'order' => 5,
            'is_published' => 1,
            'certificate_enabled' => 1,
            'badge_reward' => 'IELTS Master B2',
        ]);

        $this->assertDatabaseHas('courses', [
            'title' => 'IELTS Intensive B2',
            'level' => 'B2',
            'is_published' => true,
        ]);
    }

    public function test_admin_can_view_curriculum_studio(): void
    {
        $admin = User::where('username', 'admin')->first();
        $course = Course::first();

        $response = $this->actingAs($admin)->get("/admin/courses/{$course->id}");

        $response->assertStatus(200);
        $response->assertSee('LMS Studio');
        $response->assertSee($course->title);
    }

    public function test_admin_can_add_lesson_to_course(): void
    {
        $admin = User::where('username', 'admin')->first();
        $course = Course::first();

        $response = $this->actingAs($admin)->post("/admin/courses/{$course->id}/lessons", [
            'title' => 'Bài 99: Luyện nghe Hội thoại Sân bay',
            'description' => 'Mẫu câu và tình huống check-in sân bay',
            'order' => 10,
            'estimated_minutes' => 30,
            'unlock_condition_score' => 70,
            'is_free_trial' => 1,
        ]);

        $response->assertRedirect(route('admin.courses.show', $course->id));
        $this->assertDatabaseHas('lessons', [
            'course_id' => $course->id,
            'title' => 'Bài 99: Luyện nghe Hội thoại Sân bay',
            'estimated_minutes' => 30,
        ]);
    }

    public function test_admin_can_add_modern_activity_formats_to_lesson(): void
    {
        $admin = User::where('username', 'admin')->first();
        $lesson = Lesson::first();

        // 1. Audio Listening activity
        $audioRes = $this->actingAs($admin)->post("/admin/lessons/{$lesson->id}/activities", [
            'title' => 'Luyện nghe Podcast Du lịch',
            'type' => 'audio_listening',
            'audio_url' => 'https://example.com/podcast.mp3',
            'audio_transcript' => 'Welcome to our travel podcast in London.',
            'order' => 5,
            'estimated_minutes' => 8,
        ]);
        $audioRes->assertRedirect(route('admin.courses.show', $lesson->course_id));

        $this->assertDatabaseHas('activities', [
            'lesson_id' => $lesson->id,
            'type' => 'audio_listening',
            'title' => 'Luyện nghe Podcast Du lịch',
        ]);

        // 2. AI Speaking Drill activity
        $speakingRes = $this->actingAs($admin)->post("/admin/lessons/{$lesson->id}/activities", [
            'title' => 'Luyện phát âm AI: Travel Sentence',
            'type' => 'ai_speaking',
            'speaking_sentence' => 'I would like to book a flight to London.',
            'speaking_ipa' => '/aɪ wʊd laɪk tuː bʊk ə flaɪt tuː ˈlʌndən/',
            'speaking_tip' => 'Nhấn trọng âm từ flight và London',
            'order' => 6,
            'estimated_minutes' => 5,
        ]);
        $speakingRes->assertRedirect(route('admin.courses.show', $lesson->course_id));

        $this->assertDatabaseHas('activities', [
            'lesson_id' => $lesson->id,
            'type' => 'ai_speaking',
            'title' => 'Luyện phát âm AI: Travel Sentence',
        ]);
    }

    public function test_admin_can_toggle_course_publish(): void
    {
        $admin = User::where('username', 'admin')->first();
        $course = Course::first();
        $originalStatus = $course->is_published;

        $response = $this->actingAs($admin)->post("/admin/courses/{$course->id}/toggle-publish");

        $response->assertSessionHas('success');
        $this->assertEquals(!$originalStatus, $course->fresh()->is_published);
    }

    public function test_trial_activities_flow_only_shows_and_allows_ticked_activities(): void
    {
        $admin = User::where('username', 'admin')->first();
        $student = User::where('username', 'tuanlinh')->first();

        // Find lesson 1 and ensure it is marked trial
        $lesson = Lesson::first();
        $lesson->update(['is_free_trial' => true]);

        // Unenroll student if enrolled
        \App\Models\Enrollment::where('user_id', $student->id)->where('course_id', $lesson->course_id)->delete();

        // Get 2 activities from this lesson
        $acts = $lesson->activities()->take(2)->get();
        $act1 = $acts[0];
        $act2 = $acts[1];

        // Turn OFF trial for both initially
        $act1->update(['is_free_trial' => false, 'is_visible' => true]);
        $act2->update(['is_free_trial' => false, 'is_visible' => true]);

        // Unenrolled student viewing lesson sees both activities, but both are locked
        $res = $this->actingAs($student)->get(route('lessons.show', $lesson->id));
        $res->assertStatus(200);
        $res->assertSee($act1->title);
        $res->assertSee($act2->title);
        $res->assertSee('🔒 Cần ghi danh');
        $res->assertDontSee(route('activities.show', $act1->id));
        $res->assertDontSee(route('activities.show', $act2->id));

        // Student directly trying to open unticked activity should be blocked
        $deniedRes = $this->actingAs($student)->get(route('activities.show', $act1->id));
        $deniedRes->assertRedirect(route('courses.show', $lesson->course_id));

        // Admin toggles trial for act1
        $toggleRes = $this->actingAs($admin)->postJson("/admin/activities/{$act1->id}/toggle-trial");
        $toggleRes->assertJson(['success' => true, 'is_free_trial' => true]);
        $this->assertTrue($act1->fresh()->is_free_trial);

        // Unenrolled student sees act1 unlocked (clickable) and act2 still locked (unclickable)
        $resAfter = $this->actingAs($student)->get(route('lessons.show', $lesson->id));
        $resAfter->assertStatus(200);
        $resAfter->assertSee($act1->title);
        $resAfter->assertSee($act2->title);
        $resAfter->assertSee(route('activities.show', $act1->id));
        $resAfter->assertDontSee(route('activities.show', $act2->id));
        $resAfter->assertSee('✨ Học thử');

        // Student CAN access act1
        $allowedRes = $this->actingAs($student)->get(route('activities.show', $act1->id));
        $allowedRes->assertStatus(200);
    }

    public function test_unenrolled_student_can_view_non_trial_lesson_activity_outline_but_activities_remain_locked(): void
    {
        $student = User::where('username', 'tuanlinh')->first();
        $lesson = Lesson::first();
        $course = $lesson->course;

        // Ensure neither lesson nor its activities are marked free trial
        $lesson->update(['is_free_trial' => false]);
        $lesson->activities()->update(['is_free_trial' => false, 'is_visible' => true]);

        // Unenroll student
        \App\Models\Enrollment::where('user_id', $student->id)->where('course_id', $course->id)->delete();

        // 1. In Course Show, student sees link to view lesson outline with "Xem nội dung"
        $courseRes = $this->actingAs($student)->get(route('courses.show', $course->id));
        $courseRes->assertStatus(200);
        $courseRes->assertSee(route('lessons.show', $lesson->id));
        $courseRes->assertSee('Xem nội dung');

        // 2. Student clicks into the lesson: can view the lesson and all activities
        $lessonRes = $this->actingAs($student)->get(route('lessons.show', $lesson->id));
        $lessonRes->assertStatus(200);
        $lessonRes->assertSee('Bài học này chưa mở học thử');
        $lessonRes->assertSee('🔒 Cần ghi danh');

        // Verify that none of the activities are clickable links
        foreach ($lesson->activities as $act) {
            $lessonRes->assertSee($act->title);
            $lessonRes->assertDontSee(route('activities.show', $act->id));
        }

        // 3. Direct access to any activity in this non-trial lesson is blocked
        $firstAct = $lesson->activities->first();
        $actRes = $this->actingAs($student)->get(route('activities.show', $firstAct->id));
        $actRes->assertRedirect(route('courses.show', $course->id));
    }
}
