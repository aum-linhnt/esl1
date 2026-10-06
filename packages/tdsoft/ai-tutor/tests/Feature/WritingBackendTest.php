<?php

namespace TDSoft\AiTutor\Tests\Feature;

use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use TDSoft\AiTutor\Assessment\AssessmentResult;
use TDSoft\AiTutor\Assessment\PhaseFourSchema;
use TDSoft\AiTutor\Assessment\RubricDefinition;
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
use TDSoft\AiTutor\SubjectEnglish\IeltsWritingRubrics;
use TDSoft\AiTutor\SubjectEnglish\PracticeRubrics;
use TDSoft\AiTutor\Tests\FoundationTestCase;
use TDSoft\AiTutor\Writing\ProcessWritingSubmission;
use TDSoft\AiTutor\Writing\WritingCefrTarget;
use TDSoft\AiTutor\Writing\WritingComparison;
use TDSoft\AiTutor\Writing\WritingDrafts;
use TDSoft\AiTutor\Writing\WritingEvidence;
use TDSoft\AiTutor\Writing\WritingIssues;
use TDSoft\AiTutor\Writing\WritingParagraphs;
use TDSoft\AiTutor\Writing\WritingPrompt;
use TDSoft\AiTutor\Writing\WritingRequirements;
use TDSoft\AiTutor\Writing\WritingSchema;
use TDSoft\AiTutor\Writing\WritingStructure;
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
            ['status' => 'assessed', 'score' => 70, 'evidence' => ['I likes'], 'rationale' => 'Agreement errors limit accuracy.', 'next_step' => 'Use I like with the base verb.']),
            'feedback' => 'Practice agreement.',
            'cefr_target_analysis' => [['aspect' => 'communication', 'status' => 'met', 'comment' => 'A familiar hobby is described.', 'next_step' => 'Develop why you enjoy it.', 'evidence' => ['reading books']]],
            'structure_analysis' => [['component' => 'main_idea', 'status' => 'met', 'comment' => 'The hobby is clear.', 'next_step' => 'Keep this idea.', 'evidence' => ['reading books']]],
            'task_requirements' => [['requirement' => 'Describe a hobby.', 'status' => 'met', 'comment' => 'The hobby is stated.', 'evidence' => ['reading books']]],
            'paragraph_analysis' => [['paragraph_number' => 1, 'comment' => 'The hobby is clear.', 'next_step' => 'Add a reason for enjoying it.']], 'strengths' => ['The topic is stated clearly.'],
            'improvements' => ['Check subject agreement.'], 'priority_actions' => ['Correct subject agreement before adding details.'], 'issues' => [['category' => 'grammar', 'start_utf16' => 2,
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
        $this->assertSame(['The topic is stated clearly.'], $result['result']['strengths']);
        $this->assertSame(['Check subject agreement.'], $result['result']['improvements']);
        $this->assertSame(['Correct subject agreement before adding details.'], $result['result']['priority_actions']);
        $this->assertSame('Agreement errors limit accuracy.', $result['result']['criteria']['grammar']['rationale']);
        $this->assertSame('Use I like with the base verb.', $result['result']['criteria']['grammar']['next_step']);
        $this->assertSame($result['original'], $result['result']['paragraph_analysis'][0]['excerpt']);
        $this->assertSame('Add a reason for enjoying it.', $result['result']['paragraph_analysis'][0]['next_step']);
        $this->assertSame('met', $result['result']['task_requirements'][0]['status']);
        $this->assertSame(['reading books'], $result['result']['task_requirements'][0]['evidence']);
        $this->assertSame('main_idea', $result['result']['structure_analysis'][0]['component']);
        $this->assertSame('B1', $result['result']['cefr_target_analysis']['target']);
        $this->assertCount(3, $result['result']['cefr_target_analysis']['aspects']);
        $this->assertSame('not_available', $result['result']['cefr_target_analysis']['aspects'][1]['status']);
        $this->assertSame(['reading books'], $result['result']['structure_analysis'][0]['evidence']);
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
        $comparison = $this->writing()->get($next['id'])['comparison'];
        $this->assertSame(1, $comparison['previous_revision']);
        $this->assertSame(0.0, $comparison['overall_delta']);
        $this->assertSame(1, $comparison['previous_issue_count']);
        $this->assertNull($this->writing()->get($submission['id'])['comparison']);
    }

    public function test_issue_comparison_requires_exact_evidence_and_does_not_claim_fixes(): void
    {
        $base = ['overall_score' => 60, 'criteria' => ['grammar' => ['score' => 60, 'status' => 'assessed']]];
        $issue = static fn ($quote, $category = 'grammar') => ['category' => $category, 'original' => $quote,
            'replacement' => 'Suggestion', 'explanation' => 'Explain.'];
        $previous = $base + ['issues' => [$issue('I likes'), $issue('I likes'), $issue('Yesterday I go'), $issue('always'), $issue('invented')]];
        $current = $base + ['issues' => [$issue('I likes'), $issue('new wording', 'vocabulary')]];
        $changes = WritingComparison::between($current, $previous, 1,
            'I likes books. Yesterday I went. I always use new wording.', 'I likes books. Yesterday I go. I always read.')['issue_changes'];
        $this->assertSame(1, $changes['recurring']['count']);
        $this->assertSame(2, $changes['not_reported']['count']);
        $this->assertFalse($changes['not_reported']['items'][0]['original_still_present']);
        $this->assertTrue($changes['not_reported']['items'][1]['original_still_present']);
        $this->assertSame(1, $changes['newly_reported']['count']);
        $this->assertSame(1, $changes['unverified_count']);
        $current['issues'] = [$issue('I likes', 'vocabulary')];
        $changedCategory = WritingComparison::between($current, $previous, 1, 'I likes books.', 'I likes books.')['issue_changes'];
        $this->assertSame(0, $changedCategory['recurring']['count']);
        $this->assertSame(1, $changedCategory['newly_reported']['count']);
    }

    public function test_issue_comparison_limits_preview_and_keeps_missing_snapshot_unavailable(): void
    {
        $base = ['overall_score' => 60, 'criteria' => [], 'issues' => []];
        $current = $base;
        for ($i = 0; $i < 8; $i++) {
            $current['issues'][] = ['category' => 'grammar', 'original' => "quote$i", 'replacement' => 'Fix', 'explanation' => 'Reason'];
        }
        $changes = WritingComparison::between($current, $base, 1, 'quote0 quote1 quote2 quote3 quote4 quote5 quote6 quote7', '')['issue_changes'];
        $this->assertSame(8, $changes['newly_reported']['count']);
        $this->assertCount(5, $changes['newly_reported']['items']);
        $this->assertNull(WritingComparison::between($current, $base, 1)['issue_changes']);
    }

    public function test_comparison_deltas_missing_scores_and_scale_mismatch(): void
    {
        $old = ['score_scale' => 'ielts_band_0_9', 'overall_score' => 6.0, 'criteria' => [
            'grammar' => ['status' => 'assessed', 'score' => 6],
            'coherence' => ['status' => 'assessed', 'score' => 7],
            'vocabulary' => ['status' => 'not_available', 'score' => null],
        ], 'issues' => [[], []]];
        $current = $old;
        $current['overall_score'] = 6.5;
        $current['criteria']['grammar']['score'] = 7;
        $current['criteria']['coherence']['score'] = 6.5;
        $current['issues'] = [[]];
        $comparison = WritingComparison::between($current, $old, 2);
        $this->assertSame(0.5, $comparison['overall_delta']);
        $this->assertSame(1.0, $comparison['criteria']['grammar']['delta']);
        $this->assertSame(-0.5, $comparison['criteria']['coherence']['delta']);
        $this->assertNull($comparison['criteria']['vocabulary']['delta']);
        $old['score_scale'] = 'practice_0_100';
        $this->assertNull(WritingComparison::between($current, $old, 2));
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

    public function test_wrong_offsets_recover_only_unique_exact_quotes_with_utf16_offsets(): void
    {
        $issues = WritingIssues::validate([
            ['category' => 'grammar', 'start_utf16' => 5, 'end_utf16' => 10, 'original' => 'likes', 'replacement' => 'like', 'explanation' => 'Fix'],
            ['category' => 'grammar', 'start_utf16' => 3, 'end_utf16' => 8, 'original' => 'likes', 'replacement' => 'like', 'explanation' => 'Wrong offset'],
        ], '😀 I likes reading.');
        $this->assertTrue($issues[0]['applicable']);
        $this->assertTrue($issues[1]['applicable']);
        $this->assertSame(5, $issues[1]['start_utf16']);
        $this->assertSame(10, $issues[1]['end_utf16']);
    }

    public function test_ambiguous_missing_quotes_and_blank_replacements_remain_feedback_only(): void
    {
        $base = ['category' => 'grammar', 'start_utf16' => -1, 'end_utf16' => -1,
            'original' => 'likes', 'replacement' => 'like', 'explanation' => 'Fix'];
        $issues = WritingIssues::validate([$base, [...$base, 'original' => 'absent'],
            [...$base, 'original' => 'I', 'replacement' => '  ']], 'I likes books and likes music.');
        foreach ($issues as $issue) {
            $this->assertFalse($issue['applicable']);
            $this->assertNull($issue['start_utf16']);
        }
        $exact = WritingIssues::validate([[...$base, 'start_utf16' => 2, 'end_utf16' => 7]], 'I likes books and likes music.');
        $this->assertTrue($exact[0]['applicable']);
        $empty = WritingIssues::validate([[...$base, 'start_utf16' => 2, 'end_utf16' => 7, 'replacement' => '']], 'I likes books.');
        $this->assertFalse($empty[0]['applicable']);
        $overlap = WritingIssues::validate([[...$base, 'original' => 'aa']], 'aaa');
        $this->assertFalse($overlap[0]['applicable']);
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

    public function test_cefr_target_analysis_is_bound_to_profile_and_exact_quotes(): void
    {
        foreach (['A1', 'A2', 'B1', 'B2'] as $level) {
            $item = ['aspect' => 'language', 'status' => 'partial', 'comment' => 'Check agreement.', 'next_step' => 'Use I like.', 'evidence' => ['I likes', 'invented']];
            $result = WritingCefrTarget::validate([$item], 'I likes books.', ['framework' => 'cefr', 'target' => $level]);
            $this->assertSame($level, $result['target']);
            $this->assertSame(['I likes'], $result['aspects'][2]['evidence']);
            $this->assertSame('partial', $result['aspects'][2]['status']);
            $item['evidence'] = ['invented'];
            foreach (['met', 'partial', 'not_met'] as $status) {
                $item['status'] = $status;
                $result = WritingCefrTarget::validate([$item], 'I likes books.', ['framework' => 'cefr', 'target' => $level]);
                $this->assertSame('not_available', $result['aspects'][2]['status']);
                $this->assertSame('', $result['aspects'][2]['next_step']);
            }
        }
        $this->assertNull(WritingCefrTarget::validate(null, 'Text', ['framework' => 'cefr', 'target' => 'A2']));
        $this->assertNull(WritingCefrTarget::validate([], 'Text', ['framework' => 'ielts', 'target' => '6.5']));
        $this->assertNull(WritingCefrTarget::validate([], 'Text', ['framework' => 'toeic', 'target' => '650+']));
    }

    public function test_cefr_target_schema_and_validation_reject_invented_aspects(): void
    {
        $item = ['aspect' => 'language', 'status' => 'met', 'comment' => 'Clear.', 'next_step' => 'Retain clarity.', 'evidence' => ['Text']];
        $profile = ['framework' => 'cefr', 'target' => 'A2'];
        $this->assertError('AI_ASSESSMENT_RESULT_INVALID', fn () => WritingCefrTarget::validate([$item, $item], 'Text', $profile));
        $item['aspect'] = 'certified_level';
        $this->assertError('AI_ASSESSMENT_RESULT_INVALID', fn () => WritingCefrTarget::validate([$item], 'Text', $profile));
        $schema = WritingPrompt::schema(['grammar' => []], false, 'cefr_writing', true);
        $this->assertContains('cefr_target_analysis', $schema['required']);
        $this->assertArrayNotHasKey('cefr_target_analysis', WritingPrompt::schema(['grammar' => []], true, 'ielts_task_2')['properties']);
    }

    public function test_structure_analysis_is_task_specific_and_evidence_bound(): void
    {
        $item = ['component' => 'overview', 'status' => 'met', 'comment' => 'Clear overview.',
            'next_step' => 'Retain it.', 'evidence' => ['Overall, numbers rose.', 'invented']];
        $result = WritingStructure::validate([$item], 'Overall, numbers rose.', 'ielts_task_1');
        $this->assertSame(['Overall, numbers rose.'], $result[0]['evidence']);
        $this->assertSame('met', $result[0]['status']);
        $item['evidence'] = ['invented'];
        $result = WritingStructure::validate([$item], 'Overall, numbers rose.', 'ielts_task_1');
        $this->assertSame('not_available', $result[0]['status']);
        $this->assertSame('', $result[0]['next_step']);
        $this->assertError('AI_ASSESSMENT_RESULT_INVALID', fn () => WritingStructure::validate([$item], 'Text', 'cefr_writing'));
        $this->assertError('AI_ASSESSMENT_RESULT_INVALID', fn () => WritingStructure::validate([$item], 'Text', 'ielts_task_2'));
        $this->assertError('AI_ASSESSMENT_RESULT_INVALID', fn () => WritingStructure::validate([$item, $item], 'Text', 'ielts_task_1'));
        $this->assertSame([], WritingStructure::validate([], 'Text', 'cefr_writing'));
        $item['component'] = 'examples';
        $item['status'] = 'not_met';
        $item['evidence'] = [];
        $this->assertSame('not_met', WritingStructure::validate([$item], 'Text', 'ielts_task_2')[0]['status']);
    }

    public function test_structure_schema_follows_the_task_without_imposing_ielts_on_cefr(): void
    {
        foreach (['cefr_writing', 'ielts_task_1', 'ielts_task_2'] as $task) {
            $schema = WritingPrompt::schema(['grammar' => []], false, $task);
            $this->assertSame(WritingStructure::components($task), $schema['properties']['structure_analysis']['items']['properties']['component']['enum']);
        }
    }

    public function test_task_requirement_claims_need_exact_evidence(): void
    {
        $items = [
            ['requirement' => 'Describe a hobby.', 'status' => 'met', 'comment' => 'Covered.', 'evidence' => ['reading books', 'invented quote']],
            ['requirement' => 'Explain why.', 'status' => 'partial', 'comment' => 'Covered.', 'evidence' => ['invented quote']],
            ['requirement' => 'Give a reason.', 'status' => 'not_met', 'comment' => 'No reason provided.', 'evidence' => []],
        ];
        $result = WritingRequirements::validate($items, 'I likes reading books.');
        $this->assertSame(['reading books'], $result[0]['evidence']);
        $this->assertSame('met', $result[0]['status']);
        $this->assertSame('not_available', $result[1]['status']);
        $this->assertSame([], $result[1]['evidence']);
        $this->assertSame('not_met', $result[2]['status']);
        $items[0]['status'] = 'invalid';
        $this->assertError('AI_ASSESSMENT_RESULT_INVALID', fn () => WritingRequirements::validate($items, 'I likes reading books.'));
    }

    public function test_paragraph_analysis_numbers_are_bound_to_original_paragraphs(): void
    {
        $original = "First paragraph.\r\n\r\nSecond paragraph.\r\n\r\nThird paragraph.";
        $items = [['paragraph_number' => 2, 'comment' => 'Develop this idea.', 'next_step' => 'Add an example.']];
        $validated = WritingParagraphs::validate($items, $original);
        $this->assertSame('Second paragraph.', $validated[0]['excerpt']);
        $this->assertSame([], WritingParagraphs::validate([], $original));
        $this->assertError('AI_ASSESSMENT_RESULT_INVALID', fn () => WritingParagraphs::validate([...$items, ...$items], $original));
        $items[0]['paragraph_number'] = 4;
        $this->assertError('AI_ASSESSMENT_RESULT_INVALID', fn () => WritingParagraphs::validate($items, $original));
    }

    public function test_failed_evidence_can_be_recovered_from_cache_without_another_provider_call(): void
    {
        $submission = $this->submit($this->draft());
        $this->writing()->process($submission['id']);
        // Simulate the legacy validator failing after execution settled.
        DB::table('tutor_ai_assessment_scores')->where('writing_submission_id', $submission['id'])->delete();
        DB::table('tutor_ai_writing_issues')->where('submission_id', $submission['id'])->delete();
        DB::table('tutor_ai_writing_submissions')->where('id', $submission['id'])->update([
            'status' => 'failed', 'result' => null, 'error_code' => 'AI_ASSESSMENT_EVIDENCE_INVALID', 'completed_at' => null,
        ]);
        $this->writing()->process($submission['id']);
        $this->assertSame('failed', $this->writing()->get($submission['id'])['status']);
        $this->writing()->process($submission['id'], true);
        $this->assertSame('completed', $this->writing()->get($submission['id'])['status']);
        $this->assertSame(1, $this->provider->calls);
        $this->assertSame(1, DB::table('tutor_ai_credit_transactions')->where('type', 'commit')->count());
        $this->assertSame(4, DB::table('tutor_ai_assessment_scores')->count());
    }

    public function test_ielts_band_pipeline_and_old_rubric_scale_are_separate(): void
    {
        $repository = new RubricRepository;
        $rubrics = (new IeltsWritingRubrics)->provision($repository);
        $this->assertArrayHasKey('task_achievement', $rubrics['ielts_task_1']['criteria']);
        $this->assertArrayNotHasKey('task_response', $rubrics['ielts_task_1']['criteria']);
        $rubric = $rubrics['ielts_task_2'];
        $draft = $this->drafts()->create(new EnglishProfile('ielts', '6.5', 'vi'), 'ielts_task_2', 'Discuss reading.', 'I likes reading books.');
        $criteria = [];
        foreach (['task_response' => 6.5, 'coherence' => 6, 'vocabulary' => 7, 'grammar' => 6] as $key => $score) {
            $criteria[$key] = ['status' => 'assessed', 'score' => $score, 'evidence' => ['I likes']];
        }
        $this->validResponse(['criteria' => $criteria, 'feedback' => 'IELTS practice.', 'issues' => []]);
        $submission = $this->writing()->submit($draft['id'], 1, $rubric['id'], (string) Str::uuid(), (string) Str::uuid());
        $this->writing()->process($submission['id']);
        $result = $this->writing()->get($submission['id']);
        $this->assertSame('ielts_band_0_9', $result['result']['score_scale']);
        $this->assertSame(6.5, (float) $result['result']['overall_score']);
        $this->assertStringContainsString('never convert a percentage', $this->provider->lastRequest->payload['instructions']);
        $legacy = $repository->provision('legacy_ielts', 'writing', 'ielts_task_2', 'english-practice-v1', new RubricDefinition($rubric['criteria']));
        $this->assertSame('practice_0_100', $legacy['score_scale']);
    }

    public function test_ielts_rejects_percentage_or_non_half_band_scores(): void
    {
        $rubric = (new IeltsWritingRubrics)->provision(new RubricRepository)['ielts_task_2'];
        foreach ([65, 6.25] as $invalid) {
            $data = ['criteria' => array_fill_keys(array_keys($rubric['criteria']), ['status' => 'assessed', 'score' => $invalid, 'evidence' => ['I likes']]), 'feedback' => 'Feedback'];
            $this->assertError('AI_ASSESSMENT_RESULT_INVALID', fn () => AssessmentResult::fromArray($data, new RubricDefinition($rubric['criteria']), ['text'], 'ielts_band_0_9'));
        }
    }

    public function test_wrapped_evidence_is_recovered_and_unsupported_criteria_are_unscored(): void
    {
        $criteria = array_fill_keys(array_keys($this->rubric['criteria']),
            ['status' => 'assessed', 'score' => 80, 'evidence' => ['"I likes" (should be "I like")']]);
        $unsupported = array_key_last($criteria);
        $criteria[$unsupported]['evidence'] = ['Discusses the topic with clear examples.'];
        $this->validResponse(['criteria' => $criteria, 'feedback' => 'Feedback', 'issues' => []]);
        $submission = $this->submit($this->draft());
        $this->writing()->process($submission['id']);
        $result = $this->writing()->get($submission['id']);
        $this->assertSame('completed', $result['status']);
        $this->assertNull($result['result']['overall_score']);
        $this->assertSame(['status' => 'not_available', 'score' => null, 'evidence' => []], $result['result']['criteria'][$unsupported]);
        unset($criteria[$unsupported]);
        foreach (array_keys($criteria) as $key) {
            $this->assertSame(['I likes'], $result['result']['criteria'][$key]['evidence']);
        }
        $this->assertSame(1, $this->provider->calls);
    }

    public function test_evidence_recovery_keeps_only_literal_original_text(): void
    {
        $criteria = ['grammar' => ['status' => 'assessed', 'score' => 70,
            'evidence' => ['Uses “I likes” and \'reading books\'.', '"I LIKES"', '"invented words"']]];
        $result = WritingEvidence::validate($criteria, 'I likes reading books.');
        $this->assertSame(['I likes', 'reading books'], $result['grammar']['evidence']);
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
