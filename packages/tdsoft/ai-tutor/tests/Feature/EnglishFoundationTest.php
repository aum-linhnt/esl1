<?php

namespace TDSoft\AiTutor\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use TDSoft\AiTutor\Assessment\AssessmentResult;
use TDSoft\AiTutor\Assessment\AssessmentState;
use TDSoft\AiTutor\Assessment\PhaseFourSchema;
use TDSoft\AiTutor\Assessment\RubricDefinition;
use TDSoft\AiTutor\Assessment\RubricRepository;
use TDSoft\AiTutor\SubjectEnglish\EnglishProfile;
use TDSoft\AiTutor\SubjectEnglish\PracticeRubrics;
use TDSoft\AiTutor\Tests\FoundationTestCase;

final class EnglishFoundationTest extends FoundationTestCase
{
    private function migrate(): void
    {
        (require __DIR__.'/../../database/migrations/'.PhaseFourSchema::MIGRATION.'.php')->up();
    }

    public function test_profiles_validate_targets_tasks_and_feedback_language(): void
    {
        foreach (EnglishProfile::TARGETS as $framework => $targets) {
            foreach ($targets as $target) {
                $this->assertSame($target, (new EnglishProfile($framework, $target))->toArray()['target']);
            }
        }
        (new EnglishProfile('toeic', '650+', 'bilingual'))->validateTask('writing', 'cefr_writing');
        (new EnglishProfile('ielts', '6.5'))->validateTask('speaking', 'ielts_part_2');
        $this->assertError('AI_ENGLISH_PROFILE_INVALID', fn () => new EnglishProfile('cefr', 'C2'));
        $this->assertError('AI_ENGLISH_PROFILE_INVALID', fn () => new EnglishProfile('ielts', '6.5', 'html'));
        $this->assertError('AI_ENGLISH_TASK_INVALID', fn () => (new EnglishProfile('cefr', 'B1'))->validateTask('writing', 'ielts_task_2'));
        $this->assertError('AI_ENGLISH_TASK_INVALID', fn () => (new EnglishProfile('ielts', '6.5'))->validateTask('speaking', 'cefr_conversation'));
    }

    public function test_upgrade_inspection_detects_history_collision_and_missing_unique_index(): void
    {
        $inspector = new PhaseFourSchema;
        $this->assertSame('pending', $inspector->inspect()['state']);
        $this->migrate();
        $this->assertSame('conflict_or_partial', $inspector->inspect()['state']);
        Schema::create('migrations', function (Blueprint $t) {
            $t->id();
            $t->string('migration');
            $t->integer('batch');
        });
        DB::table('migrations')->insert(['migration' => PhaseFourSchema::MIGRATION, 'batch' => 2]);
        $this->assertSame('installed', $inspector->inspect()['state']);
        $this->assertSame(100, DB::table('tutor_ai_credit_accounts')->value('balance'));
        Schema::table('tutor_ai_writing_submissions', fn (Blueprint $t) => $t->dropUnique('tai_ws_key_uq'));
        $this->assertSame('schema_mismatch', $inspector->inspect()['state']);
        $this->assertContains('tutor_ai_writing_submissions.tai_ws_key_uq missing or not unique', $inspector->inspect()['problems']);
    }

    public function test_partial_installation_does_not_create_or_overwrite_any_other_table(): void
    {
        Schema::create('tutor_ai_writing_drafts', function (Blueprint $t) {
            $t->id();
            $t->string('customer_content');
        });
        DB::table('tutor_ai_writing_drafts')->insert(['customer_content' => 'preserve me']);
        try {
            $this->migrate();
            $this->fail('Expected collision');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('tutor_ai_writing_drafts', $error->getMessage());
        }
        $this->assertFalse(Schema::hasTable('tutor_ai_assessment_rubrics'));
        $this->assertSame('preserve me', DB::table('tutor_ai_writing_drafts')->value('customer_content'));
    }

    public function test_destructive_rollback_is_disabled(): void
    {
        $this->assertError('AI_DESTRUCTIVE_ROLLBACK_DISABLED', fn () => (require __DIR__.'/../../database/migrations/'.PhaseFourSchema::MIGRATION.'.php')->down());
    }

    public function test_rubric_provision_is_idempotent_and_versions_preserve_old_snapshot(): void
    {
        $this->migrate();
        $repository = new RubricRepository;
        $rubrics = (new PracticeRubrics)->provision($repository);
        $this->assertCount(7, $rubrics);
        $this->assertSame($rubrics, (new PracticeRubrics)->provision($repository));
        $old = $rubrics['cefr_writing'];
        $criteria = $old['criteria'];
        $criteria['grammar']['description'] = 'Updated description for next version.';
        $new = $repository->provision($old['key'], 'writing', 'cefr_writing', 'english-practice-v2', new RubricDefinition($criteria));
        $this->assertSame(2, $new['version']);
        $this->assertNotSame($old['id'], $new['id']);
        $this->assertSame($old, $repository->snapshot($old['id']));
        $this->assertSame(8, DB::table('tutor_ai_assessment_rubric_versions')->count());
        $this->assertSame(100, DB::table('tutor_ai_credit_accounts')->value('balance'));
        $this->assertSame(0, DB::table('tutor_ai_requests')->count());
        $this->assertError('AI_RUBRIC_CONTEXT_CHANGED', fn () => $repository->provision($old['key'], 'speaking', 'cefr_conversation', 'v1', new RubricDefinition($criteria)));
    }

    public function test_rubric_fingerprint_is_independent_of_criterion_key_order(): void
    {
        $this->migrate();
        $repository = new RubricRepository;
        $old = (new PracticeRubrics)->provision($repository)['cefr_writing'];
        $criteria = array_reverse($old['criteria'], true);
        foreach ($criteria as &$criterion) {
            $criterion = array_reverse($criterion, true);
        }
        unset($criterion);
        $this->assertSame($old, $repository->provision($old['key'], 'writing', 'cefr_writing', $old['prompt_version'], new RubricDefinition($criteria)));
    }

    public function test_invalid_rubric_weights_and_evidence_types_are_rejected(): void
    {
        $this->assertError('AI_RUBRIC_INVALID', fn () => new RubricDefinition([]));
        $this->assertError('AI_RUBRIC_INVALID', fn () => new RubricDefinition([
            'grammar' => ['weight' => 99, 'description' => 'Grammar.', 'evidence_type' => 'text'],
        ]));
        $this->assertError('AI_RUBRIC_INVALID', fn () => new RubricDefinition([
            'pronunciation' => ['weight' => 100, 'description' => 'Pronunciation.', 'evidence_type' => 'made_up'],
        ]));
    }

    public function test_transcript_cannot_authorize_pronunciation_fluency_or_overall_score(): void
    {
        $this->migrate();
        $snapshot = (new PracticeRubrics)->provision(new RubricRepository)['cefr_conversation'];
        $data = $this->resultData($snapshot['criteria']);
        $data['overall_score'] = 100;
        $result = AssessmentResult::fromArray($data, new RubricDefinition($snapshot['criteria']));
        $this->assertNull($result->overallScore);
        $this->assertSame(['status' => 'not_available', 'score' => null, 'evidence' => []], $result->criteria['pronunciation']);
        $this->assertNull($result->criteria['fluency']['score']);
        $this->assertSame(80.0, $result->criteria['grammar']['score']);
    }

    public function test_complete_rubric_calculates_weighted_practice_score_without_provider_overall(): void
    {
        $rubric = new RubricDefinition([
            'grammar' => ['weight' => 60, 'description' => 'Grammar.', 'evidence_type' => 'text'],
            'vocabulary' => ['weight' => 40, 'description' => 'Vocabulary.', 'evidence_type' => 'text'],
        ]);
        $data = $this->resultData($rubric->criteria);
        $data['criteria']['grammar']['score'] = 50;
        $data['overall_score'] = 100;
        $result = AssessmentResult::fromArray($data, $rubric);
        $this->assertSame(62.0, $result->overallScore);
        $this->assertSame('practice_0_100', $result->toArray()['score_scale']);
    }

    public function test_malformed_scores_missing_criteria_and_missing_evidence_fail_closed(): void
    {
        $rubric = new RubricDefinition(['grammar' => ['weight' => 100, 'description' => 'Grammar.', 'evidence_type' => 'text']]);
        foreach (['80', 101, -1, INF, NAN, null] as $bad) {
            $data = $this->resultData($rubric->criteria);
            $data['criteria']['grammar']['score'] = $bad;
            $this->assertError('AI_ASSESSMENT_RESULT_INVALID', fn () => AssessmentResult::fromArray($data, $rubric));
        }
        $data = $this->resultData($rubric->criteria);
        $data['criteria']['grammar']['evidence'] = [];
        $this->assertError('AI_ASSESSMENT_RESULT_INVALID', fn () => AssessmentResult::fromArray($data, $rubric));
        $this->assertError('AI_ASSESSMENT_RESULT_INVALID', fn () => AssessmentResult::fromArray(['criteria' => [], 'feedback' => 'ok'], $rubric));
    }

    public function test_unavailable_required_criterion_keeps_overall_null(): void
    {
        $rubric = new RubricDefinition(['grammar' => ['weight' => 100, 'description' => 'Grammar.', 'evidence_type' => 'text']]);
        $result = AssessmentResult::fromArray(['criteria' => ['grammar' => ['status' => 'not_available']], 'feedback' => 'Insufficient evidence.'], $rubric);
        $this->assertNull($result->overallScore);
    }

    public function test_completed_or_failed_attempt_cannot_be_silently_requeued(): void
    {
        AssessmentState::assertTransition('queued', 'processing');
        AssessmentState::assertTransition('processing', 'reconciliation_required');
        foreach (['completed', 'failed', 'reconciliation_required', 'unknown'] as $from) {
            $this->assertError('AI_ASSESSMENT_STATE_INVALID', fn () => AssessmentState::assertTransition($from, 'queued'));
        }
    }

    private function resultData(array $criteria): array
    {
        return ['criteria' => array_map(fn () => ['status' => 'assessed', 'score' => 80, 'evidence' => ['Example evidence.']], $criteria), 'feedback' => 'Practice feedback.'];
    }
}
