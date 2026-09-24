<?php

namespace Tests\AiTutor;

use App\Integrations\AiTutor\WebsiteActorResolver;
use App\Integrations\AiTutor\WebsiteLmsAdapter;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use TDSoft\AiTutor\Tests\FoundationTestCase;

final class WebsiteAdapterTest extends FoundationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('role');
            $t->string('status');
            $t->string('current_level')->nullable();
        });
        Schema::create('courses', function (Blueprint $t) {
            $t->id();
            $t->string('level')->nullable();
        });
        Schema::create('lessons', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('course_id');
            $t->string('title');
            $t->text('summary')->nullable();
            $t->integer('order')->default(1);
            $t->boolean('is_free_trial')->default(false);
            $t->boolean('is_visible')->default(true);
        });
        Schema::create('activities', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('lesson_id');
            $t->string('type');
            $t->json('content')->nullable();
            $t->integer('order')->default(1);
            $t->boolean('is_free_trial')->default(false);
            $t->boolean('is_visible')->default(true);
        });
        Schema::create('enrollments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('course_id');
            $t->string('status');
            $t->string('course_role')->default('student');
            $t->timestamp('expires_at')->nullable();
        });
        Schema::create('question_banks', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('course_id')->nullable();
            $t->text('question_text');
            $t->json('options');
            $t->text('correct_answer');
            $t->text('explanation');
            $t->softDeletes();
        });
        DB::table('users')->insert(['id' => 1, 'role' => 'student', 'status' => 'active']);
        DB::table('courses')->insert(['id' => 1, 'level' => 'A1']);
        DB::table('lessons')->insert(['id' => 1, 'course_id' => 1, 'title' => 'Lesson', 'summary' => 'Paid lesson summary']);
        DB::table('activities')->insert(['id' => 1, 'lesson_id' => 1, 'type' => 'quiz', 'is_free_trial' => true,
            'content' => json_encode(['source_mode' => 'bank_manual', 'question_ids' => [1]])]);
        DB::table('question_banks')->insert(['id' => 1, 'course_id' => 1, 'question_text' => 'Choose a word',
            'options' => json_encode([['text' => 'A', 'is_correct' => true], ['text' => 'B', 'is_correct' => false]]),
            'correct_answer' => 'SECRET ANSWER', 'explanation' => 'SECRET EXPLANATION']);
    }

    public function test_trial_context_does_not_leak_paid_lesson_or_answers(): void
    {
        $adapter = new WebsiteLmsAdapter;
        $context = $adapter->getQuestionContext('1', '1', '1');
        $this->assertSame(['A', 'B'], $context->options);
        $serialized = json_encode($context);
        $this->assertStringNotContainsString('SECRET', $serialized);
        $this->assertStringNotContainsString('is_correct', $serialized);
        $this->assertStringNotContainsString('Paid lesson summary', $serialized);
        $this->assertSame('hints_only', $context->lesson->answerPolicy);
    }

    public function test_hidden_suspended_expired_or_non_trial_content_is_denied(): void
    {
        $adapter = new WebsiteLmsAdapter;
        DB::table('lessons')->update(['is_visible' => false]);
        $this->assertFalse($adapter->canAccessLesson('1', '1'));
        DB::table('lessons')->update(['is_visible' => true]);
        DB::table('enrollments')->insert(['user_id' => 1, 'course_id' => 1, 'status' => 'suspended']);
        $this->assertFalse($adapter->canAccessLesson('1', '1'));
        DB::table('enrollments')->update(['status' => 'active', 'expires_at' => now()->subDay()]);
        $this->assertFalse($adapter->canAccessLesson('1', '1'));
        DB::table('users')->update(['status' => 'blocked']);
        $this->assertFalse($adapter->canAccessLesson('1', '1'));
    }

    public function test_question_must_belong_to_requested_lesson_and_course(): void
    {
        $adapter = new WebsiteLmsAdapter;
        DB::table('question_banks')->update(['course_id' => 2]);
        $this->assertError('AI_CONTEXT_FORBIDDEN', fn () => $adapter->getQuestionContext('1', '1', '1'));
        DB::table('question_banks')->update(['course_id' => 1]);
        DB::table('activities')->update(['content' => json_encode(['source_mode' => 'bank_manual', 'question_ids' => [2]])]);
        $this->assertError('AI_CONTEXT_FORBIDDEN', fn () => $adapter->getQuestionContext('1', '1', '1'));
    }

    public function test_actor_is_resolved_from_authenticated_user(): void
    {
        $user = User::find(1);
        $auth = \Mockery::mock();
        $auth->shouldReceive('user')->once()->andReturn($user);
        Auth::swap($auth);
        $this->assertSame('1', (new WebsiteActorResolver)->resolve()->id);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
