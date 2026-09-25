<?php

namespace TDSoft\AiTutor\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use TDSoft\AiTutor\Contracts\Entitlements;
use TDSoft\AiTutor\Contracts\KnowledgeAdministrator;
use TDSoft\AiTutor\Contracts\KnowledgeSourceAdapter;
use TDSoft\AiTutor\Knowledge\CourseSyncService;
use TDSoft\AiTutor\Knowledge\KnowledgeService;
use TDSoft\AiTutor\Knowledge\KnowledgeVisibility;
use TDSoft\AiTutor\Knowledge\PhaseThreeSchema;
use TDSoft\AiTutor\Knowledge\SyncSchema;
use TDSoft\AiTutor\Tests\FoundationTestCase;

final class CourseSyncTest extends FoundationTestCase
{
    private object $source;

    protected function setUp(): void
    {
        parent::setUp();
        (require __DIR__.'/../../database/migrations/'.PhaseThreeSchema::MIGRATION.'.php')->up();
        (require __DIR__.'/../../database/migrations/'.SyncSchema::MIGRATION.'.php')->up();
        $this->app->instance(KnowledgeAdministrator::class, new class implements KnowledgeAdministrator
        {
            public function allows(): bool
            {
                return true;
            }
        });
        $this->source = new class implements KnowledgeSourceAdapter
        {
            public bool $readAllowed = true;

            public function canReadLesson(string $actorId, string $lessonId): bool
            {
                return $this->readAllowed;
            }

            public array $rows = [
                ['lesson_id' => 'lesson-1', 'title' => 'Greetings', 'content' => 'Hello and good morning.', 'warning' => 'Summary only'],
                ['lesson_id' => 'lesson-2', 'title' => 'Empty lesson', 'content' => '', 'warning' => 'No summary'],
            ];

            public function courses(string $actorId): array
            {
                return [['id' => 'course-1', 'title' => 'English']];
            }

            public function lessons(string $actorId, string $courseId): array
            {
                return $this->rows;
            }
        };
        $this->app->instance(KnowledgeSourceAdapter::class, $this->source);
    }

    private function selected(array $row): array
    {
        return [['lesson_id' => $row['lesson_id'], 'fingerprint' => $row['fingerprint']]];
    }

    public function test_preview_and_repeat_sync_are_free_and_do_not_duplicate_versions(): void
    {
        $sync = $this->app->make(CourseSyncService::class);
        $preview = $sync->preview('course-1');
        $this->assertSame('new', $preview[0]['status']);
        $this->assertSame(1, $preview[0]['chunks']);
        $this->assertSame('empty', $preview[1]['status']);
        $this->assertSame(0, DB::table('tutor_ai_knowledge_documents')->count());
        $first = $sync->sync('course-1', $this->selected($preview[0]));
        $second = $sync->sync('course-1', $this->selected($preview[0]));
        $this->assertSame($first[0]['version_id'], $second[0]['version_id']);
        $this->assertSame('unchanged', $second[0]['status']);
        $this->assertSame(1, DB::table('tutor_ai_knowledge_document_versions')->count());
        $this->assertNull(DB::table('tutor_ai_knowledge_documents')->value('published_version_id'));
        $this->assertSame(0, $this->provider->calls);
        $this->assertSame(0, DB::table('tutor_ai_requests')->count());
    }

    public function test_changed_content_adds_version_without_replacing_published_one_or_manual_document(): void
    {
        $knowledge = $this->app->make(KnowledgeService::class);
        $manual = $knowledge->create(['lesson_id' => 'lesson-1', 'title' => 'Manual', 'content' => 'Teacher notes']);
        $sync = $this->app->make(CourseSyncService::class);
        $first = $sync->sync('course-1', $this->selected($sync->preview('course-1')[0]))[0];
        DB::table('tutor_ai_knowledge_documents')->where('id', $first['document_id'])->update(['published_version_id' => $first['version_id']]);
        DB::table('tutor_ai_knowledge_document_versions')->where('id', $first['version_id'])->update(['status' => 'published']);
        $this->source->rows[0]['content'] = 'Changed lesson content.';
        $preview = $sync->preview('course-1');
        $this->assertSame('changed', $preview[0]['status']);
        $second = $sync->sync('course-1', $this->selected($preview[0]))[0];
        $this->assertSame($first['document_id'], $second['document_id']);
        $this->assertNotSame($first['version_id'], $second['version_id']);
        $this->assertSame($first['version_id'], $knowledge->document($first['document_id'])->published_version_id);
        $this->assertSame('Hello and good morning.', $knowledge->getVersion($first['version_id'])->content);
        $this->assertSame('Teacher notes', $knowledge->getVersion($manual->version_id)->content);
        $this->assertSame(2, DB::table('tutor_ai_knowledge_documents')->count());
    }

    public function test_stale_empty_and_cross_course_selection_are_denied_before_writes(): void
    {
        $sync = $this->app->make(CourseSyncService::class);
        $preview = $sync->preview('course-1');
        $this->source->rows[0]['content'] = 'Edited after preview';
        $this->assertError('AI_SYNC_PREVIEW_STALE', fn () => $sync->sync('course-1', $this->selected($preview[0])));
        $this->assertError('AI_SYNC_PREVIEW_STALE', fn () => $sync->sync('course-1', $this->selected($preview[1])));
        $this->assertError('AI_CONTEXT_FORBIDDEN', fn () => $sync->preview('other-course'));
        $this->assertSame(0, DB::table(SyncSchema::TABLE)->count());
        $this->assertSame(0, DB::table('tutor_ai_knowledge_documents')->count());
    }

    public function test_unauthorized_administrator_or_revoked_entitlement_cannot_sync(): void
    {
        $this->app->instance(KnowledgeAdministrator::class, new class implements KnowledgeAdministrator
        {
            public function allows(): bool
            {
                return false;
            }
        });
        $this->assertError('AI_KNOWLEDGE_FORBIDDEN', fn () => $this->app->make(CourseSyncService::class)->courses());
        $this->app->instance(Entitlements::class, new class implements Entitlements
        {
            public function allows(string $module): bool
            {
                return false;
            }
        });
        $this->assertError('LICENSE_MODULE_NOT_ALLOWED', fn () => $this->app->make(CourseSyncService::class)->preview('course-1'));
        $this->assertSame(0, $this->provider->calls);
    }

    public function test_synced_sources_are_hidden_when_summary_access_is_revoked(): void
    {
        $sync = $this->app->make(CourseSyncService::class);
        $result = $sync->sync('course-1', $this->selected($sync->preview('course-1')[0]))[0];
        DB::table('tutor_ai_knowledge_documents')->where('id', $result['document_id'])->update(['published_version_id' => $result['version_id']]);
        DB::table('tutor_ai_knowledge_document_versions')->where('id', $result['version_id'])->update(['status' => 'published']);
        DB::table('tutor_ai_knowledge_chunks')->update(['indexed' => true]);
        $context = $this->lms->getLessonContext('learner-1', 'lesson-1');
        $this->assertTrue(KnowledgeVisibility::query($context)->exists());
        $this->source->readAllowed = false;
        $this->assertFalse(KnowledgeVisibility::query($context)->exists());
    }

    public function test_upgrade_preflight_accepts_recorded_migration_and_rejects_collision(): void
    {
        $this->assertSame('conflict_or_partial', (new SyncSchema)->inspect()['state']);
        Schema::create('migrations', function ($t) {
            $t->id();
            $t->string('migration');
            $t->integer('batch');
        });
        DB::table('migrations')->insert(['migration' => SyncSchema::MIGRATION, 'batch' => 4]);
        $this->assertSame('installed', (new SyncSchema)->inspect()['state']);
        $this->assertSame(100, DB::table('tutor_ai_credit_accounts')->value('balance'));
        $this->expectException(\RuntimeException::class);
        (require __DIR__.'/../../database/migrations/'.SyncSchema::MIGRATION.'.php')->up();
    }
}
