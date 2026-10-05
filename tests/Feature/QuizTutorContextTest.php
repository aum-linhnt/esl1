<?php

namespace Tests\Feature;

use App\Integrations\AiTutor\WebsiteLmsAdapter;
use App\Models\Activity;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\QuestionBank;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\LMS\QuizAttemptContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use TDSoft\AiTutor\Contracts\Entitlements;
use TDSoft\AiTutor\Conversations\ConversationService;
use TDSoft\AiTutor\Core\AiException;
use TDSoft\AiTutor\Core\AiRequest;
use TDSoft\AiTutor\Core\LearnerIdentity;
use TDSoft\AiTutor\Providers\MockProvider;
use Tests\TestCase;

class QuizTutorContextTest extends TestCase
{
    use RefreshDatabase;

    private User $learner;

    private Lesson $lesson;

    private Activity $quiz;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->learner = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $this->actingAs($this->learner);
        $course = Course::create(['title' => 'Quiz course', 'slug' => 'quiz-context', 'created_by' => $this->learner->id, 'is_published' => true]);
        $this->lesson = Lesson::create(['course_id' => $course->id, 'title' => 'Lesson', 'order' => 1, 'is_visible' => true, 'is_free_trial' => true]);
        $this->quiz = Activity::create(['lesson_id' => $this->lesson->id, 'title' => 'Quiz', 'type' => 'quiz',
            'is_visible' => true, 'is_free_trial' => true, 'content' => ['source_mode' => 'inline', 'questions' => [
                ['question' => 'Choose a word', 'question_type' => 'mcq', 'options' => [['text' => 'A', 'is_correct' => true], ['text' => 'B', 'score' => 0]],
                    'correct_answer' => 'SECRET_ANSWER', 'explanation' => 'SECRET_EXPLANATION'],
                ['question' => 'Second question', 'options' => ['C', 'D'], 'answer' => 'SECRET_SECOND'],
            ]]]);
        config(['ai-tutor.enabled' => true, 'ai-tutor.provider' => 'mock', 'ai-tutor.providers.mock' => MockProvider::class]);
        $this->app->instance(Entitlements::class, new class implements Entitlements
        {
            public function allows(string $module): bool
            {
                return true;
            }
        });
    }

    private function start(): QuizAttempt
    {
        return app(QuizAttemptContextService::class)->start($this->learner, $this->quiz->fresh());
    }

    private function context(QuizAttempt $attempt, string $question = 'inline:0', ?string $user = null, ?string $lesson = null)
    {
        return app(WebsiteLmsAdapter::class)->getAttemptQuestionContext($user ?? (string) $this->learner->id,
            $question, $lesson ?? (string) $this->lesson->id, (string) $attempt->id);
    }

    private function denied(callable $action): void
    {
        try {
            $action();
            $this->fail('Expected context rejection');
        } catch (AiException $error) {
            $this->assertSame('AI_CONTEXT_FORBIDDEN', $error->errorCode);
        }
    }

    public function test_start_endpoint_freezes_inline_questions_and_never_accepts_client_question_content(): void
    {
        $response = $this->postJson(route('activities.attempts.store', $this->quiz->id), [
            'questions' => [['question' => 'SPOOFED']], 'user_id' => 999,
        ])->assertCreated()->assertJsonPath('questions.0.id', 'inline:0');
        $attempt = QuizAttempt::findOrFail($response->json('attempt_id'));
        $this->assertSame($this->learner->id, $attempt->user_id);
        $this->assertSame('Choose a word', $attempt->question_snapshot[0]['question']);
        $this->assertArrayNotHasKey('question_snapshot', $attempt->toArray());
        $this->assertCount(0, $this->quiz->getUserAttempts($this->learner->id));
    }

    public function test_matching_and_word_ordering_do_not_derive_options_from_answer_keys(): void
    {
        $this->quiz->update(['content' => ['questions' => [
            ['question' => 'Match words', 'question_type' => 'matching', 'options' => ['left' => ['one', 'two'], 'right' => ['un', 'deux']],
                'correct_answer' => ['one' => 'SECRET_PAIR']],
            ['question' => 'Order words', 'question_type' => 'word_ordering', 'correct_answer' => 'SECRET_SENTENCE'],
        ]]]);
        $attempt = $this->start();
        $this->assertSame(['one', 'two', 'deux', 'un'], $this->context($attempt)->options);
        $this->assertSame([], $this->context($attempt, 'inline:1')->options);
        $this->assertStringNotContainsString('SECRET', json_encode($this->context($attempt)));
    }

    public function test_core_request_fingerprint_includes_attempt_and_keeps_legacy_shape(): void
    {
        $request = new AiRequest('tutor_message', new LearnerIdentity('actor'),
            ['message' => 'Help'], (string) Str::uuid(), 'key', 'course', 'lesson', 'question');
        $first = new AiRequest('tutor_message', $request->actor, $request->payload,
            $request->requestId, 'key', 'course', 'lesson', 'question', attemptId: 'first');
        $second = new AiRequest('tutor_message', $request->actor, $request->payload,
            $request->requestId, 'key', 'course', 'lesson', 'question', attemptId: 'second');
        $this->assertNotSame($first->fingerprint(), $second->fingerprint());
        $this->assertNotSame($request->fingerprint(), $first->fingerprint());
        $this->assertSame('first', $first->withContext(null)->attemptId);
    }

    public function test_snapshot_survives_later_content_edits_and_strips_all_answer_metadata(): void
    {
        $attempt = $this->start();
        $this->quiz->update(['content' => ['questions' => [['question' => 'CHANGED']]]]);
        $context = $this->context($attempt);
        $this->assertSame('Choose a word', $context->content);
        $this->assertSame(['A', 'B'], $context->options);
        $this->assertStringNotContainsString('SECRET', json_encode($context));
        $this->assertStringNotContainsString('is_correct', json_encode($context));
        $this->assertStringNotContainsString('score', json_encode($context));
    }

    public function test_random_bank_snapshot_is_scoped_to_course_and_selected_question_ids(): void
    {
        $foreign = Course::create(['title' => 'Other', 'slug' => 'other-quiz', 'created_by' => $this->learner->id]);
        $safe = QuestionBank::create(['course_id' => $this->lesson->course_id, 'question_text' => 'Selected bank question', 'question_type' => 'mcq',
            'options' => ['A', 'B'], 'correct_answer' => 'SECRET_BANK', 'explanation' => 'SECRET_BANK_EXPLANATION']);
        $other = QuestionBank::create(['course_id' => $foreign->id, 'question_text' => 'Foreign question', 'question_type' => 'mcq', 'options' => ['X'], 'correct_answer' => 'X']);
        $this->quiz->update(['content' => ['source_mode' => 'bank_random', 'random_count' => 1]]);
        $attempt = $this->start();
        $this->assertSame($safe->id, $attempt->question_snapshot[0]['id']);
        $safe->update(['question_text' => 'Changed bank question']);
        $this->assertSame('Selected bank question', $this->context($attempt, (string) $safe->id)->content);
        $this->denied(fn () => $this->context($attempt, (string) $other->id));
        $this->assertStringNotContainsString('SECRET', json_encode($this->context($attempt, (string) $safe->id)));
    }

    public function test_manual_bank_also_rejects_cross_course_ids_at_start(): void
    {
        $foreign = Course::create(['title' => 'Other', 'slug' => 'foreign-manual', 'created_by' => $this->learner->id]);
        $question = QuestionBank::create(['course_id' => $foreign->id, 'question_text' => 'Foreign', 'options' => ['A'], 'correct_answer' => 'A']);
        $this->quiz->update(['content' => ['source_mode' => 'bank_manual', 'question_ids' => [$question->id]]]);
        $this->postJson(route('activities.attempts.store', $this->quiz->id))->assertUnprocessable();
        $this->assertSame(0, QuizAttempt::count());
    }

    public function test_owner_lesson_membership_and_terminated_status_are_checked_on_every_read(): void
    {
        $attempt = $this->start();
        $other = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $this->denied(fn () => $this->context($attempt, user: (string) $other->id));
        $this->denied(fn () => $this->context($attempt, 'inline:999'));
        $lesson = Lesson::create(['course_id' => $this->lesson->course_id, 'title' => 'Other lesson', 'is_free_trial' => true, 'is_visible' => true]);
        $this->denied(fn () => $this->context($attempt, lesson: (string) $lesson->id));
        foreach (['completed', 'abandoned', 'timed_out'] as $status) {
            $attempt->update(['status' => $status]);
            $this->denied(fn () => $this->context($attempt));
        }
        $attempt->update(['status' => 'in_progress', 'question_snapshot' => null]);
        $this->denied(fn () => $this->context($attempt));
    }

    public function test_deadline_hidden_activity_and_revoked_enrollment_reject_context(): void
    {
        $this->quiz->update(['time_limit_minutes' => 1]);
        $attempt = $this->start();
        $attempt->update(['started_at' => now()->subMinute()]);
        $this->denied(fn () => $this->context($attempt));
        $attempt->update(['started_at' => now()]);
        $this->quiz->update(['is_visible' => false]);
        $this->denied(fn () => $this->context($attempt));
        $this->quiz->update(['is_visible' => true]);
        Enrollment::create(['course_id' => $this->lesson->course_id, 'user_id' => $this->learner->id, 'status' => 'suspended']);
        $this->denied(fn () => $this->context($attempt));
    }

    public function test_new_start_abandons_previous_attempt_and_retains_its_snapshot(): void
    {
        $first = $this->start();
        $second = $this->start();
        $this->assertNotSame($first->id, $second->id);
        $this->assertSame('abandoned', $first->fresh()->status);
        $this->assertNotEmpty($first->fresh()->question_snapshot);
        $this->denied(fn () => $this->context($first));
        $this->assertSame('Choose a word', $this->context($second)->content);
    }

    public function test_non_trial_activity_and_unavailable_activity_cannot_start(): void
    {
        $this->quiz->update(['is_free_trial' => false]);
        $this->postJson(route('activities.attempts.store', $this->quiz->id))->assertForbidden();
        $this->quiz->update(['is_free_trial' => true, 'available_from' => now()->addHour()]);
        $this->postJson(route('activities.attempts.store', $this->quiz->id))->assertForbidden();
        $this->assertSame(0, QuizAttempt::count());
    }

    public function test_submit_updates_same_attempt_and_disables_its_chat(): void
    {
        Enrollment::create(['user_id' => $this->learner->id, 'course_id' => $this->lesson->course_id, 'status' => 'active']);
        $attempt = $this->start();
        $this->postJson(route('activities.complete', $this->quiz->id), ['attempt_id' => (string) $attempt->id, 'score' => 80, 'max_score' => 100])
            ->assertOk()->assertJsonPath('attempt.id', $attempt->id)->assertJsonPath('attempt.status', 'completed');
        $this->assertSame(1, QuizAttempt::count());
        $this->assertSame(80.0, $attempt->fresh()->score);
        $this->denied(fn () => $this->context($attempt));
        $this->postJson(route('activities.complete', $this->quiz->id), ['attempt_id' => (string) $attempt->id])->assertForbidden();
    }

    public function test_trial_submission_ends_context_without_saving_a_grade(): void
    {
        $attempt = $this->start();
        $this->postJson(route('activities.complete', $this->quiz->id), ['attempt_id' => (string) $attempt->id, 'score' => 80])
            ->assertOk()->assertJsonPath('trial_mode', true);
        $this->assertSame('abandoned', $attempt->fresh()->status);
        $this->assertSame(0.0, $attempt->fresh()->score);
        $this->denied(fn () => $this->context($attempt));
    }

    public function test_context_api_and_conversation_creation_bind_attempt_and_question(): void
    {
        $attempt = $this->start();
        $data = ['lesson_id' => (string) $this->lesson->id, 'question_id' => 'inline:0', 'attempt_id' => (string) $attempt->id];
        $this->getJson('/ai-tutor/api/v1/context?'.http_build_query($data))->assertOk()->assertJsonPath('question_id', 'inline:0');
        $this->postJson('/ai-tutor/api/v1/conversations', ['lesson_id' => (string) $this->lesson->id, 'question_id' => 'inline:0'])
            ->assertUnprocessable()->assertJsonValidationErrors('attempt_id');
        $response = $this->postJson('/ai-tutor/api/v1/conversations', $data)->assertCreated()->assertJsonPath('attempt_id', (string) $attempt->id);
        $attempt->update(['status' => 'completed']);
        $this->getJson('/ai-tutor/api/v1/conversations/'.$response->json('id'))->assertForbidden();
        $this->postJson('/ai-tutor/api/v1/conversations', $data)->assertForbidden();
    }

    public function test_actual_provider_prompt_uses_only_snapshot_question_and_visible_options(): void
    {
        $attempt = $this->start();
        $provider = new MockProvider;
        $this->app->instance(MockProvider::class, $provider);
        DB::table('tutor_ai_credit_accounts')->insert(['owner_type' => 'learner', 'owner_id' => (string) $this->learner->id,
            'scope' => 'system', 'balance' => 100, 'daily_limit' => 50, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('tutor_ai_credit_rules')->insert(['feature' => 'tutor_message', 'base_units' => 1, 'max_units_per_request' => 5,
            'blocks' => json_encode([]), 'cost_rates' => json_encode([]), 'created_at' => now(), 'updated_at' => now()]);
        $tutor = app(ConversationService::class);
        $conversation = $tutor->create((string) $this->lesson->id, questionId: 'inline:0', attemptId: (string) $attempt->id);
        $tutor->send($conversation->id, 'Help', (string) Str::uuid(), (string) Str::uuid());
        $input = json_decode($provider->lastRequest->payload['input'], true);
        $this->assertSame('Choose a word', $input['question']['content']);
        $this->assertSame(['A', 'B'], $input['question']['options']);
        $this->assertStringNotContainsString('SECRET', $provider->lastRequest->payload['input']);
        $this->assertStringNotContainsString('Second question', $provider->lastRequest->payload['input']);
        $this->assertSame(1, $provider->calls);
        $attempt->update(['status' => 'completed']);
        $this->denied(fn () => $tutor->send($conversation->id, 'Again', (string) Str::uuid(), (string) Str::uuid()));
        $this->assertSame(1, $provider->calls);
    }
}
