<?php

namespace TDSoft\AiTutor\Tests\Feature;

use Illuminate\Support\Facades\{DB, Schema};
use TDSoft\AiTutor\Tests\FoundationTestCase;

final class SchemaTest extends FoundationTestCase
{
    public function test_upgrade_preflight_accepts_recorded_installation_and_preserves_data(): void
    {
        Schema::create('migrations', function (\Illuminate\Database\Schema\Blueprint $t) {
            $t->id(); $t->string('migration'); $t->integer('batch');
        });
        $inspector = new \TDSoft\AiTutor\Core\SchemaInspector;
        $this->assertSame('conflict_or_partial', $inspector->inspect()['state']);
        DB::table('migrations')->insert(['migration' => $inspector::MIGRATION, 'batch' => 1]);
        $this->assertSame('installed', $inspector->inspect()['state']);
        $this->assertSame(100, DB::table('tutor_ai_credit_accounts')->value('balance'));
        Schema::table('tutor_ai_requests', fn (\Illuminate\Database\Schema\Blueprint $t) => $t->dropUnique('tai_req_key_uq'));
        $this->assertSame('schema_mismatch', $inspector->inspect()['state']);
    }

    public function test_collision_stops_before_any_tables_are_overwritten(): void
    {
        $before = DB::table('tutor_ai_credit_accounts')->first();
        try {
            (require __DIR__.'/../../database/migrations/2026_09_24_000001_create_tutor_ai_foundation.php')->up();
            $this->fail('Expected collision');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('tutor_ai_requests', $error->getMessage());
            $this->assertEquals($before, DB::table('tutor_ai_credit_accounts')->first());
        }
        $this->assertTrue(Schema::hasTable('tutor_ai_cost_snapshots'));
    }

    public function test_rollback_is_explicitly_disabled(): void
    {
        $this->assertError('AI_DESTRUCTIVE_ROLLBACK_DISABLED', fn () =>
            (require __DIR__.'/../../database/migrations/2026_09_24_000001_create_tutor_ai_foundation.php')->down());
    }
}
