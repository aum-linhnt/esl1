<?php

namespace TDSoft\AiTutor\Tests\Feature;

use Illuminate\Database\Events\MigrationsStarted;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use TDSoft\AiTutor\Core\MigrationPreflight;
use TDSoft\AiTutor\Core\SchemaInspector;
use TDSoft\AiTutor\Licensing\LicenseSchemaInspector;
use TDSoft\AiTutor\Tests\FoundationTestCase;

final class LicenseMigrationTest extends FoundationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('migrations', function (Blueprint $t) {
            $t->id();
            $t->string('migration');
            $t->integer('batch');
        });
        DB::table('migrations')->insert(['migration' => SchemaInspector::MIGRATION, 'batch' => 1]);
    }

    public function test_forward_upgrade_keeps_foundation_data_and_recognizes_installed_schema(): void
    {
        $before = DB::table('tutor_ai_credit_accounts')->first();
        $inspector = new LicenseSchemaInspector;
        $this->assertSame('pending', $inspector->inspect()['state']);
        app(MigrationPreflight::class)->handle(new MigrationsStarted('up'));
        (require __DIR__.'/../../database/migrations/2026_09_24_000002_create_tutor_ai_license_tables.php')->up();
        DB::table('migrations')->insert(['migration' => LicenseSchemaInspector::MIGRATION, 'batch' => 2]);
        $this->assertSame('installed', $inspector->inspect()['state']);
        app(MigrationPreflight::class)->handle(new MigrationsStarted('up'));
        $this->assertEquals($before, DB::table('tutor_ai_credit_accounts')->first());
    }

    public function test_collision_is_detected_before_first_license_table_is_created(): void
    {
        Schema::create('tutor_ai_license_refresh_attempts', fn (Blueprint $t) => $t->id());
        try {
            app(MigrationPreflight::class)->handle(new MigrationsStarted('up'));
            $this->fail('Expected collision');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('tutor_ai_license_refresh_attempts', $error->getMessage());
            $this->assertFalse(Schema::hasTable('tutor_ai_license_state'));
        }
    }
}
