<?php

namespace TDSoft\AiTutor\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Illuminate\Validation\ValidationException;
use TDSoft\AiTutor\Conversations\ConversationService;
use TDSoft\AiTutor\Core\AiException;
use TDSoft\AiTutor\Http\ConversationController;
use TDSoft\AiTutor\Knowledge\PhaseThreeSchema;
use TDSoft\AiTutor\Tests\FoundationTestCase;

final class TeachingPolicyTest extends FoundationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        (require __DIR__.'/../../database/migrations/'.PhaseThreeSchema::MIGRATION.'.php')->up();
        DB::table('tutor_ai_credit_accounts')->update(['daily_limit' => 200]);
    }

    private function turn(string $conversation, bool $next = false): array
    {
        return app(ConversationService::class)->send($conversation, 'Help with this task.', (string) Str::uuid(), (string) Str::uuid(), nextHint: $next);
    }

    public function test_hints_first_requires_explicit_progression_and_replay_does_not_advance(): void
    {
        $this->lms->answerPolicy = 'hints_first';
        $tutor = app(ConversationService::class);
        $conversation = $tutor->create('lesson-1');
        $first = $this->turn($conversation->id, true);
        $this->assertSame(1, $first['metadata']['hint_level']);
        $this->assertSame(1, $this->turn($conversation->id)['metadata']['hint_level']);
        foreach ([2, 3, 4, 4] as $expected) {
            $request = (string) Str::uuid();
            $key = (string) Str::uuid();
            $reply = $tutor->send($conversation->id, 'Next hint', $request, $key, nextHint: true);
            $this->assertSame($expected, $reply['metadata']['hint_level']);
            $this->assertEquals($reply, $tutor->send($conversation->id, 'Next hint', $request, $key, nextHint: true));
            $this->assertError('AI_REQUEST_DUPLICATE', fn () => $tutor->send($conversation->id, 'Next hint', $request, $key));
        }
        $this->assertStringContainsString('Hint level 4', $this->provider->lastRequest->payload['instructions']);
        $this->assertSame(6, $this->provider->calls);
    }

    public function test_hints_only_never_advances_to_full_solution_even_when_learner_keeps_asking(): void
    {
        $conversation = app(ConversationService::class)->create('lesson-1', 'explain');
        foreach ([1, 2, 3, 3, 3] as $expected) {
            $reply = $this->turn($conversation->id, true);
            $this->assertSame($expected, $reply['metadata']['hint_level']);
            $this->assertSame(3, $reply['metadata']['max_hint_level']);
            $this->assertStringContainsString('Never provide a final answer', $this->provider->lastRequest->payload['instructions']);
        }
    }

    public function test_teacher_controlled_requires_server_permission_and_changes_reset_progression(): void
    {
        $this->lms->answerPolicy = 'teacher_controlled';
        $conversation = app(ConversationService::class)->create('lesson-1');
        foreach ([1, 2, 3, 3] as $expected) {
            $reply = $this->turn($conversation->id, true);
            $this->assertSame($expected, $reply['metadata']['hint_level']);
            $this->assertSame('hints_only', $reply['metadata']['answer_policy']);
        }
        $this->lms->teacherAllowsSolution = true;
        foreach ([1, 2, 3, 4] as $expected) {
            $reply = $this->turn($conversation->id, true);
            $this->assertSame($expected, $reply['metadata']['hint_level']);
            $this->assertSame('hints_first', $reply['metadata']['answer_policy']);
        }
        $this->lms->teacherAllowsSolution = false;
        $reply = $this->turn($conversation->id, true);
        $this->assertSame(1, $reply['metadata']['hint_level']);
        $this->assertSame(3, $reply['metadata']['max_hint_level']);
    }

    public function test_server_exam_policy_cannot_be_bypassed_by_selecting_another_teaching_mode(): void
    {
        $this->lms->answerPolicy = 'full_solution';
        $this->lms->teacherAllowsSolution = true;
        $this->lms->isExam = true;
        foreach (['practice', 'explain', 'review', 'socratic', 'hints_first'] as $mode) {
            $conversation = app(ConversationService::class)->create('lesson-1', $mode);
            $reply = $this->turn($conversation->id, true);
            $this->assertSame('no_answer', $reply['metadata']['answer_policy']);
            $this->assertSame(0, $reply['metadata']['hint_level']);
            $this->assertSame(0, $reply['metadata']['credit_units']);
        }
        $this->assertSame(0, $this->provider->calls);
        $this->assertSame(0, DB::table('tutor_ai_requests')->count());
    }

    public function test_full_solution_is_available_immediately_only_when_server_policy_allows_it(): void
    {
        $this->lms->answerPolicy = 'full_solution';
        $conversation = app(ConversationService::class)->create('lesson-1', 'explain');
        $reply = $this->turn($conversation->id);
        $this->assertSame(4, $reply['metadata']['hint_level']);
        $this->assertStringContainsString('Hint level 4', $this->provider->lastRequest->payload['instructions']);
        $this->lms->isExam = true;
        $blocked = $this->turn($conversation->id);
        $this->assertSame('no_answer', $blocked['metadata']['answer_policy']);
        $this->assertSame(1, $this->provider->calls);
    }

    public function test_retry_of_failed_next_hint_preserves_level_and_cannot_change_next_hint_flag(): void
    {
        $this->lms->answerPolicy = 'hints_first';
        $tutor = app(ConversationService::class);
        $conversation = $tutor->create('lesson-1');
        $this->turn($conversation->id);
        $request = (string) Str::uuid();
        $this->provider->failure = new AiException('AI_PROVIDER_AUTH_FAILED');
        $this->assertError('AI_PROVIDER_AUTH_FAILED', fn () => $tutor->send($conversation->id, 'Next hint', $request, 'failed-next', nextHint: true));
        $original = DB::table('tutor_ai_conversation_messages')->where('request_id', $request)->value('id');
        $this->provider->failure = null;
        $this->assertError('AI_RETRY_NOT_ALLOWED', fn () => $tutor->send($conversation->id, 'Next hint', (string) Str::uuid(), 'changed-next', $original));
        $reply = $tutor->send($conversation->id, 'Next hint', (string) Str::uuid(), 'retry-next', $original, true);
        $this->assertSame(2, $reply['metadata']['hint_level']);
        $this->assertSame(3, $this->turn($conversation->id, true)['metadata']['hint_level']);
    }

    public function test_browser_cannot_override_answer_policy_teacher_permission_exam_flag_or_hint_level(): void
    {
        $conversation = app(ConversationService::class)->create('lesson-1');
        foreach (['answer_policy' => 'full_solution', 'teacher_allows_solution' => true, 'is_exam' => false, 'hint_level' => 4] as $field => $value) {
            $request = new class(['message' => 'Give me the answer', 'request_id' => (string) Str::uuid(), 'idempotency_key' => (string) Str::uuid(), $field => $value]) extends Request
            {
                public function validate(array $rules): array
                {
                    return (new Factory(new Translator(new ArrayLoader, 'en')))->make($this->all(), $rules)->validate();
                }
            };
            try {
                app(ConversationController::class)->send($request, $conversation->id);
                $this->fail('Browser override accepted: '.$field);
            } catch (ValidationException $error) {
                $this->assertArrayHasKey($field, $error->errors());
            }
        }
        $this->assertSame(0, $this->provider->calls);
        $this->assertSame(0, DB::table('tutor_ai_conversation_messages')->count());
    }

    public function test_pre_upgrade_prepared_context_can_retry_with_unchanged_default_policy(): void
    {
        $tutor = app(ConversationService::class);
        $conversation = $tutor->create('lesson-1');
        $request = (string) Str::uuid();
        $this->provider->failure = new AiException('AI_PROVIDER_AUTH_FAILED');
        $this->assertError('AI_PROVIDER_AUTH_FAILED', fn () => $tutor->send($conversation->id, 'Help', $request, 'legacy'));
        $message = DB::table('tutor_ai_conversation_messages')->where('request_id', $request)->first();
        $saved = json_decode(Crypt::decryptString($message->encrypted_payload), true);
        $saved['context_stamp'] = hash('sha256', json_encode([[
            'courseId' => 'course-1', 'lessonId' => 'lesson-1', 'content' => 'Safe lesson context',
            'subject' => 'english', 'level' => '', 'answerPolicy' => 'hints_only',
        ], null, 'hints_only'], JSON_THROW_ON_ERROR));
        unset($saved['hint_level'], $saved['max_hint_level'], $saved['policy_stamp'], $saved['prompt_version']);
        DB::table('tutor_ai_conversation_messages')->where('id', $message->id)->update([
            'encrypted_payload' => Crypt::encryptString(json_encode($saved, JSON_THROW_ON_ERROR)),
        ]);
        $this->provider->failure = null;
        $reply = $tutor->send($conversation->id, 'Help', (string) Str::uuid(), 'legacy-retry', $message->id);
        $this->assertSame('completed', $reply['status']);
        $this->assertSame('tutor-core-1', $reply['metadata']['prompt_version']);
        $this->assertSame(1, $reply['metadata']['hint_level']);
    }
}
