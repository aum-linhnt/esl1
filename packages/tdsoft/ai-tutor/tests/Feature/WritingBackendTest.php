<?php

namespace TDSoft\AiTutor\Tests\Feature;

use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use TDSoft\AiTutor\Assessment\PhaseFourSchema;
use TDSoft\AiTutor\Assessment\RubricRepository;
use TDSoft\AiTutor\Billing\CreditAdministration;
use TDSoft\AiTutor\Billing\CreditAdminSchema;
use TDSoft\AiTutor\Contracts\ActorResolver;
use TDSoft\AiTutor\Contracts\BackgroundActor;
use TDSoft\AiTutor\Contracts\CreditAdministrator;
use TDSoft\AiTutor\Contracts\Entitlements;
use TDSoft\AiTutor\Core\AiException;
use TDSoft\AiTutor\Core\AiExecutionService;
use TDSoft\AiTutor\Core\AiRequest;
use TDSoft\AiTutor\Core\AiResponse;
use TDSoft\AiTutor\Core\LearnerIdentity;
use TDSoft\AiTutor\Events\WritingAssessmentCompleted;
use TDSoft\AiTutor\SubjectEnglish\EnglishProfile;
use TDSoft\AiTutor\SubjectEnglish\PracticeRubrics;
use TDSoft\AiTutor\Tests\FoundationTestCase;
use TDSoft\AiTutor\Writing\ProcessWritingSubmission;
use TDSoft\AiTutor\Writing\WritingDrafts;
use TDSoft\AiTutor\Writing\WritingIssues;
use TDSoft\AiTutor\Writing\WritingSchema;
use TDSoft\AiTutor\Writing\WritingSubmissions;

final class WritingBackendTest extends FoundationTestCase
{
    private array $rubric;

    private array $events = [];

    protected function setUp(): void
    {
        parent::setUp();
        (require __DIR__.'/../../database/migrations/'.PhaseFourSchema::MIGRATION.'.php')->up();
        (require __DIR__.'/../../database/migrations/'.WritingSchema::MIGRATION.'.php')->up();
        $this->rubric = (new PracticeRubrics)->provision(new RubricRepository)['cefr_writing'];
        foreach (['writing_assessment', 'writing_recheck'] as $feature) {
            DB::table('tutor_ai_credit_rules')->insert(['feature' => $feature, 'base_units' => 1,
                'max_units_per_request' => 1, 'created_at' => now(), 'updated_at' => now()]);
        }
        $manager = new DatabaseTransactionsManager;
        DB::connection()->setTransactionManager($manager);
        $this->app['events']->setTransactionManagerResolver(fn () => $manager);
        $this->app['events']->listen(WritingAssessmentCompleted::class, function ($event) {
            $this->assertSame(0, DB::transactionLevel());
            $this->events[] = $event;
        });
        $this->validResponse();
    }

    private function drafts(): WritingDrafts
    {
        return $this->app->make(WritingDrafts::class);
    }

    private function writing(): WritingSubmissions
    {
        return $this->app->make(WritingSubmissions::class);
    }

    private function draft(?string $lesson = null): array
    {
        return $this->drafts()->create(new EnglishProfile('cefr', 'B1'), 'cefr_writing', 'My hobby',
            'I likes reading books. 😀 Tôi học English.', $lesson, $lesson ? 'course-1' : null);
    }

    private function submit(array $draft, ?string $key = null): array
    {
        return $this->writing()->submit($draft['id'], $draft['revision'], $this->rubric['id'], (string) Str::uuid(), $key ?? (string) Str::uuid());
    }

    private function validResponse(?array $data = null): void
    {
        $data ??= ['criteria' => array_fill_keys(array_keys($this->rubric['criteria']),
            ['status' => 'assessed', 'score' => 70, 'evidence' => ['I likes']]),
            'feedback' => 'Practice agreement.', 'issues' => [['category' => 'grammar', 'start_utf16' => 2,
                'end_utf16' => 7, 'original' => 'likes', 'replacement' => 'like', 'explanation' => 'Subject agreement.']]];
        $this->provider->response = new AiResponse(json_encode($data, JSON_THROW_ON_ERROR), 'mock', 'mock-writing', ['input_tokens' => 20, 'output_tokens' => 30]);
    }

    public function test_autosave_preserves_revisions_and_stale_tab_cannot_overwrite(): void
    {
        $draft = $this->draft();
        $saved = $this->drafts()->save($draft['id'], 1, 'An updated essay that I wrote.');
        $this->assertSame(2, $saved['revision']);
        $this->assertError('AI_WRITING_REVISION_CONFLICT', fn () => $this->drafts()->save($draft['id'], 1, 'Stale tab'));
        $this->assertSame($saved, $this->drafts()->get($draft['id']));
        $this->assertSame($draft['content'], DB::table('tutor_ai_writing_revisions')->where('revision', 1)->value('content'));
        $this->assertSame(2, DB::table('tutor_ai_writing_revisions')->count());
        $this->drafts()->save($draft['id'], 2, $saved['content']);
        $this->assertSame(2, DB::table('tutor_ai_writing_revisions')->count());
    }

    public function test_submit_snapshots_and_same_id_replay_survives_later_draft_changes(): void
    {
        $draft = $this->draft();
        $submission = $this->submit($draft);
        $this->drafts()->save($draft['id'], 1, 'The later revision is unrelated.');
        $replay = $this->writing()->submit($draft['id'], 1, $this->rubric['id'], $submission['request_id'], $submission['idempotency_key']);
        $this->assertSame($submission['id'], $replay['id']);
        $this->assertSame($draft['content'], $replay['original']);
        $this->assertArrayNotHasKey('encrypted_payload', $replay);
        $this->assertSame(0, $this->provider->calls);
        $this->writing()->process($submission['id']);
        $this->assertSame($draft['content'], json_decode($this->provider->lastRequest->payload['input'], true)['essay']);
        $this->assertStringNotContainsString('The later revision', $this->provider->lastRequest->payload['input']);
    }

    public function test_completed_pipeline_settles_once_and_emits_once_after_commit(): void
    {
        $submission = $this->submit($this->draft());
        $this->writing()->process($submission['id']);
        $this->writing()->process($submission['id']);
        $result = $this->writing()->get($submission['id']);
        $this->assertSame('completed', $result['status']);
        $this->assertSame(70.0, (float) $result['result']['overall_score']);
        $this->assertSame(1, $this->provider->calls);
        $this->assertCount(1, $this->events);
        $this->assertSame($submission['id'], $this->events[0]->eventId);
        $this->assertSame(4, DB::table('tutor_ai_assessment_scores')->count());
        $this->assertSame(1, DB::table('tutor_ai_writing_issues')->count());
        $this->assertSame(1, DB::table('tutor_ai_credit_transactions')->where('type', 'commit')->count());
        $this->assertSame(99, DB::table('tutor_ai_credit_accounts')->value('balance'));
    }

    public function test_different_id_cannot_repeat_same_revision_or_queue_while_busy(): void
    {
        $draft = $this->draft();
        $this->submit($draft);
        $this->assertError('AI_WRITING_ALREADY_SUBMITTED', fn () => $this->submit($draft));
        $new = $this->drafts()->save($draft['id'], 1, 'I like reading books and writing essays.');
        $this->assertError('AI_WRITING_BUSY', fn () => $this->submit($new));
    }

    public function test_revised_submission_uses_writing_recheck_feature(): void
    {
        $draft = $this->draft();
        $submission = $this->submit($draft);
        $this->writing()->process($submission['id']);
        $draft = $this->drafts()->save($draft['id'], 1, 'I likes reading books and I like writing.');
        $next = $this->submit($draft);
        $this->assertSame('writing_recheck', $next['feature']);
        $this->writing()->process($next['id']);
        $this->assertSame('writing_recheck', $this->provider->lastRequest->feature);
    }

    public function test_owner_revocation_and_license_revocation_block_read_and_process(): void
    {
        $draft = $this->draft('lesson-1');
        $submission = $this->submit($draft);
        $this->lms->allowed = false;
        $this->assertError('AI_CONTEXT_FORBIDDEN', fn () => $this->writing()->get($submission['id']));
        $this->assertError('AI_CONTEXT_FORBIDDEN', fn () => $this->writing()->process($submission['id']));
        $this->lms->allowed = true;
        $this->app->instance(ActorResolver::class, new class implements ActorResolver
        {
            public function resolve(): LearnerIdentity
            {
                return new LearnerIdentity('someone-else');
            }
        });
        $this->assertError('AI_WRITING_FORBIDDEN', fn () => $this->drafts()->get($draft['id']));
        $this->assertError('AI_WRITING_FORBIDDEN', fn () => $this->writing()->get($submission['id']));
        $this->app->instance(Entitlements::class, new class implements Entitlements
        {
            public function allows(string $module): bool
            {
                return false;
            }
        });
        $this->assertError('LICENSE_MODULE_NOT_ALLOWED', fn () => $this->drafts()->get($draft['id']));
        $this->assertSame(0, $this->provider->calls);
    }

    public function test_exam_policy_is_rechecked_before_inference_and_feedback_read(): void
    {
        $draft = $this->draft('lesson-1');
        $submission = $this->submit($draft);
        $this->lms->isExam = true;
        $this->assertError('AI_WRITING_ASSESSMENT_FORBIDDEN', fn () => $this->writing()->process($submission['id']));
        $this->assertSame(0, $this->provider->calls);
        $this->lms->isExam = false;
        $this->writing()->process($submission['id']);
        $this->lms->answerPolicy = 'no_answer';
        $this->assertError('AI_WRITING_ASSESSMENT_FORBIDDEN', fn () => $this->writing()->get($submission['id']));
    }

    public function test_bad_json_preserves_usage_and_never_fabricates_a_score_or_paid_retry(): void
    {
        $submission = $this->submit($this->draft());
        $this->provider->response = new AiResponse('invalid JSON', 'mock', 'mock-writing', ['input_tokens' => 20]);
        $this->assertError('AI_ASSESSMENT_RESULT_INVALID', fn () => $this->writing()->process($submission['id']));
        $result = $this->writing()->get($submission['id']);
        $this->assertNull($result['result']);
        $this->assertSame('blocked', $result['recovery']);
        $this->assertSame('completed', DB::table('tutor_ai_requests')->value('status'));
        $this->assertSame(1, DB::table('tutor_ai_usage_records')->count());
        $this->assertCount(0, $this->events);
        $this->assertError('AI_WRITING_RETRY_BLOCKED', fn () => $this->writing()->retry($submission['id'], (string) Str::uuid(), 'bad-json-retry', true));
    }

    public function test_known_safe_failure_needs_confirmation_and_has_only_one_retry_child(): void
    {
        $submission = $this->submit($this->draft());
        $this->provider->failure = new AiException('AI_PROVIDER_RATE_LIMITED');
        $this->assertError('AI_PROVIDER_RATE_LIMITED', fn () => $this->writing()->process($submission['id']));
        $this->assertSame('new_attempt', $this->writing()->get($submission['id'])['recovery']);
        $request = (string) Str::uuid();
        $this->assertError('AI_WRITING_RETRY_CONFIRMATION_REQUIRED', fn () => $this->writing()->retry($submission['id'], $request, 'retry', false));
        $retry = $this->writing()->retry($submission['id'], $request, 'retry', true);
        $this->assertSame($retry['id'], $this->writing()->retry($submission['id'], $request, 'retry', true)['id']);
        $this->provider->failure = null;
        $this->writing()->process($retry['id']);
        $this->assertSame(2, $this->provider->calls);
        $this->assertSame(1, DB::table('tutor_ai_credit_transactions')->where('type', 'commit')->count());
        $this->assertError('AI_REQUEST_DUPLICATE', fn () => $this->writing()->retry($submission['id'], (string) Str::uuid(), 'second-child', true));
    }

    public function test_unknown_outcome_cannot_retry_or_repeat_inference(): void
    {
        $submission = $this->submit($this->draft());
        $this->provider->failure = new AiException('AI_REQUEST_RECONCILIATION_REQUIRED');
        $this->assertError('AI_REQUEST_RECONCILIATION_REQUIRED', fn () => $this->writing()->process($submission['id']));
        $this->assertSame('reconciliation', $this->writing()->get($submission['id'])['recovery']);
        $this->writing()->process($submission['id']);
        $this->assertSame(1, $this->provider->calls);
        $this->assertError('AI_WRITING_RETRY_BLOCKED', fn () => $this->writing()->retry($submission['id'], (string) Str::uuid(), 'unsafe', true));
    }

    public function test_admin_reconciliation_release_allows_confirmed_retry_and_commit_blocks_it(): void
    {
        (require __DIR__.'/../../database/migrations/'.CreditAdminSchema::MIGRATION.'.php')->up();
        $this->app->instance(CreditAdministrator::class, new class implements CreditAdministrator
        {
            public function actorId(): ?string
            {
                return 'admin-1';
            }

            public function recipients(string $search): array
            {
                return [];
            }

            public function recipient(string $id): array
            {
                return ['id' => $id, 'name' => 'Learner'];
            }

            public function administratorName(string $id): string
            {
                return 'Admin';
            }
        });
        foreach (['release', 'commit'] as $decision) {
            $submission = $this->submit($this->draft());
            $this->provider->failure = new AiException('AI_REQUEST_RECONCILIATION_REQUIRED');
            $this->assertError('AI_REQUEST_RECONCILIATION_REQUIRED', fn () => $this->writing()->process($submission['id']));
            $this->app->make(CreditAdministration::class)->reconcile($submission['request_id'], $decision,
                $decision === 'commit' ? 1 : 0, 'Provider evidence manually reviewed.', (string) Str::uuid());
            $this->assertSame('failed', $this->writing()->get($submission['id'])['status']);
            $this->assertSame($decision === 'release' ? 'new_attempt' : 'blocked', $this->writing()->get($submission['id'])['recovery']);
            if ($decision === 'release') {
                $retry = $this->writing()->retry($submission['id'], (string) Str::uuid(), 'released-retry', true);
                $this->provider->failure = null;
                $this->writing()->process($retry['id']);
                $this->assertSame('completed', $this->writing()->get($retry['id'])['status']);
            } else {
                $this->assertError('AI_WRITING_RETRY_BLOCKED', fn () => $this->writing()->retry($submission['id'], (string) Str::uuid(), 'charged-retry', true));
            }
        }
    }

    public function test_crash_after_billing_can_finish_from_durable_same_id_result(): void
    {
        $submission = $this->submit($this->draft());
        $record = $this->writing()->record($submission['id']);
        DB::table('tutor_ai_writing_submissions')->where('id', $record->id)->update(['status' => 'processing']);
        $request = new AiRequest($record->feature, new LearnerIdentity($record->actor_id),
            json_decode(Crypt::decryptString($record->encrypted_payload), true), $record->request_id, $record->idempotency_key);
        $this->app->make(AiExecutionService::class)->execute($request);
        $this->writing()->process($record->id);
        $this->assertSame('completed', $this->writing()->get($record->id)['status']);
        $this->assertSame(1, $this->provider->calls);
        $this->assertCount(1, $this->events);
    }

    public function test_invalid_unicode_spans_are_feedback_only_and_valid_spans_are_exact(): void
    {
        $issues = WritingIssues::validate([
            ['category' => 'grammar', 'start_utf16' => 5, 'end_utf16' => 10, 'original' => 'likes', 'replacement' => 'like', 'explanation' => 'Fix'],
            ['category' => 'grammar', 'start_utf16' => 3, 'end_utf16' => 8, 'original' => 'likes', 'replacement' => 'like', 'explanation' => 'Wrong offset'],
        ], '😀 I likes reading.');
        $this->assertTrue($issues[0]['applicable']);
        $this->assertFalse($issues[1]['applicable']);
        $this->assertNull($issues[1]['start_utf16']);
    }

    public function test_model_cannot_invent_text_evidence(): void
    {
        $data = ['criteria' => array_fill_keys(array_keys($this->rubric['criteria']), ['status' => 'assessed', 'score' => 80, 'evidence' => ['fabricated text']]),
            'feedback' => 'Feedback', 'issues' => []];
        $this->validResponse($data);
        $submission = $this->submit($this->draft());
        $this->assertError('AI_ASSESSMENT_EVIDENCE_INVALID', fn () => $this->writing()->process($submission['id']));
        $this->assertSame(0, DB::table('tutor_ai_assessment_scores')->count());
    }

    public function test_job_contains_only_id_and_reauthenticates_persisted_owner(): void
    {
        $submission = $this->submit($this->draft());
        $job = new ProcessWritingSubmission($submission['id']);
        $serialized = serialize($job);
        $this->assertStringNotContainsString('I likes', $serialized);
        $this->assertStringNotContainsString('learner-1', $serialized);
        $this->assertSame('ai-tutor-assessments', $job->queue);
        $actors = new class implements BackgroundActor
        {
            public function run(string $actorId, \Closure $work): mixed
            {
                if ($actorId !== 'learner-1') {
                    throw new AiException('AI_ACTOR_INVALID');
                }

                return $work();
            }
        };
        $job->handle($actors, $this->writing());
        $job->handle($actors, $this->writing());
        $this->assertSame(1, $this->provider->calls);
    }
}
