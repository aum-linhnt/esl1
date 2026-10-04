<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use TDSoft\AiTutor\Contracts\Entitlements;
use Tests\TestCase;

class LessonWorkspaceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $user = new User(['name' => 'Learner', 'role' => 'student', 'status' => 'active']);
        $user->id = 1;
        $this->actingAs($user);
        $this->app->instance(Entitlements::class, new class implements Entitlements
        {
            public function allows(string $module): bool { return false; }
        });
    }

    public function test_layout_defaults_to_classic_and_remembers_switches_per_user(): void
    {
        $session = new \Illuminate\Session\Store('lesson-layout-test', new \Illuminate\Session\ArraySessionHandler(120));
        $session->start();
        $resolve = function (array $query = [], int $userId = 1) use ($session) {
            $request = \Illuminate\Http\Request::create('/lessons/2', 'GET', $query);
            $request->setLaravelSession($session);
            $user = new User;
            $user->id = $userId;
            $request->setUserResolver(fn () => $user);

            return \App\Support\LessonLayout::resolve($request);
        };
        $this->assertSame('lessons.show', $resolve());
        $this->assertSame('lessons.tutor', $resolve(['layout' => 'tutor']));
        $this->assertSame('lessons.tutor', $resolve());
        $this->assertSame('lessons.show', $resolve([], 2));
        $this->assertSame('lessons.show', $resolve(['layout' => 'classic']));
        $this->assertSame('lessons.show', $resolve());
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $resolve(['layout' => 'untrusted-template']);
    }

    public function test_toggle_preserves_the_selected_activity(): void
    {
        $this->app->instance('request', \Illuminate\Http\Request::create('/lessons/2?activity=3'));
        $lesson = new Lesson;
        $lesson->id = 2;
        $this->view('lessons.partials.layout-toggle', ['lesson' => $lesson, 'tutorLayout' => true])
            ->assertSee('layout=classic')->assertSee('activity=3')->assertSee('Giao diện cũ');
        $this->view('lessons.partials.layout-toggle', ['lesson' => $lesson, 'tutorLayout' => false])
            ->assertSee('layout=tutor')->assertSee('Học cùng AI');
    }

    private function data(bool $trial = false): array
    {
        $course = new Course(['title' => 'English B1']);
        $course->id = 1;
        $lesson = new Lesson(['title' => 'Present Perfect', 'summary' => 'PRIVATE_LESSON_SUMMARY', 'order' => 1]);
        $lesson->id = 2;
        $activity = new Activity(['title' => 'Video lesson', 'type' => 'video']);
        $activity->id = 3;

        return [
            'lesson' => $lesson, 'course' => $course, 'completedCount' => 0, 'totalActivities' => 1,
            'completedActivityIds' => [], 'isTrialMode' => $trial, 'prevLesson' => null, 'nextLesson' => null,
            'selectedActivity' => $activity, 'canStudySelected' => ! $trial, 'initialTab' => 'lesson',
            'activityGroups' => collect(['lesson' => collect([$activity])]),
        ];
    }

    public function test_workspace_embeds_authorized_activity_and_handles_unavailable_tutor(): void
    {
        $view = $this->view('lessons.tutor', $this->data());
        $view->assertSee('Present Perfect')->assertSee('/activities/3?embedded=1', false)
            ->assertSee('PRIVATE_LESSON_SUMMARY')->assertSee('Gia sư AI chưa khả dụng')
            ->assertDontSee('floating-ai-btn')->assertDontSee('data-tai-chat');
    }

    public function test_trial_workspace_does_not_embed_locked_content_or_expose_full_summary(): void
    {
        $view = $this->view('lessons.tutor', $this->data(true));
        $view->assertSee('Hoạt động đang bị khóa')
            ->assertDontSee('data-activity-frame')->assertDontSee('PRIVATE_LESSON_SUMMARY')
            ->assertSee('aria-disabled="true"', false)
            ->assertDontSee('href="'.route('lessons.show', ['lessonId' => 2, 'activity' => 3]).'"', false);
    }

    public function test_learner_avatar_uses_profile_photo_or_name_initial(): void
    {
        auth()->user()->forceFill(['name' => 'Ánh Nguyễn', 'avatar' => null]);
        $this->view('ai-tutor::partials.avatar', ['avatarRole' => 'learner'])
            ->assertSee('Á')->assertDontSee('<img', false);
        auth()->user()->forceFill(['avatar' => 'https://example.com/avatar.jpg']);
        $this->view('ai-tutor::partials.avatar', ['avatarRole' => 'learner'])
            ->assertSee('https://example.com/avatar.jpg')->assertSee('data-learner-avatar', false)->assertSee('Á');
        auth()->user()->forceFill(['avatar' => 'missing-avatar-for-test.jpg']);
        $this->view('ai-tutor::partials.avatar', ['avatarRole' => 'learner'])
            ->assertSee('Á')->assertDontSee('<img', false);
    }

    public function test_staff_preview_keeps_paid_scheduled_activity_links_in_both_layouts(): void
    {
        foreach (['admin', 'teacher'] as $role) {
            auth()->user()->forceFill(['role' => $role]);
            $data = $this->data(true);
            $activity = $data['selectedActivity'];
            $activity->forceFill(['is_free_trial' => false, 'available_from' => now()->addDay()]);
            $data['canStudySelected'] = true;
            $data['activities'] = collect([$activity]);
            $data['trialActivitiesCount'] = 0;
            $data['isLessonCompleted'] = false;

            $this->view('lessons.tutor', $data)
                ->assertSee('data-activity-frame', false)
                ->assertSee('href="'.route('lessons.show', ['lessonId' => 2, 'activity' => 3]).'"', false)
                ->assertDontSee('aria-disabled="true"', false);
            $this->view('lessons.show', $data)
                ->assertSee('href="'.route('activities.show', 3).'"', false)
                ->assertDontSee('title="Hoạt động yêu cầu ghi danh hợp lệ và phải trong thời gian được phép truy cập."', false);
        }
    }

    public function test_panel_uses_lesson_context_and_real_credit_label(): void
    {
        $view = $this->view('ai-tutor::lesson-panel', [
            'lesson' => new \TDSoft\AiTutor\Core\LessonContext('1', '2', 'Present Perfect'),
            'actorId' => '1', 'creditLabel' => 'Còn 37 credit',
        ]);
        $view->assertSee('data-lesson="2"', false)->assertSee('Còn 37 credit')
            ->assertSee('data-quick-prompt', false)->assertSee('data-retry', false);
    }
}
