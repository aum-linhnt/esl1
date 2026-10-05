<?php

namespace Tests\Feature;

use App\Services\Backup\DatabaseBackupException;
use App\Services\Backup\DatabaseBackupService;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class DatabaseBackupTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/esl-backup-test-'.bin2hex(random_bytes(6));
        config(['backup.path' => $this->directory]);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory.'/{*,.*}', GLOB_BRACE) ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        if (is_dir($this->directory)) {
            rmdir($this->directory);
        }
        parent::tearDown();
    }

    private function fakeConnection(): Connection
    {
        $db = Mockery::mock(Connection::class);
        $db->shouldReceive('getDriverName')->andReturn('mysql');
        $db->shouldReceive('transactionLevel')->andReturn(0);
        $db->shouldReceive('getDatabaseName')->andReturn('esl');
        DB::shouldReceive('connection')->with(null)->andReturn($db);

        return $db;
    }

    public function test_successful_dump_is_gzipped_private_unique_and_complete(): void
    {
        $db = $this->fakeConnection();
        $service = Mockery::mock(DatabaseBackupService::class)->makePartial();
        $service->shouldReceive('dump')->twice()->with($db, Mockery::type('callable'))
            ->andReturnUsing(fn ($db, $write) => $write("CREATE TABLE sample (id INT);\n-- Backup complete\n"));
        $first = $service->create();
        $second = $service->create();
        $this->assertNotSame($first, $second);
        $this->assertSame("CREATE TABLE sample (id INT);\n-- Backup complete\n", gzdecode(file_get_contents($first)));
        $this->assertSame(0600, fileperms($first) & 0777);
        $this->assertSame([], glob($this->directory.'/.backup-*'));
    }

    public function test_failed_dump_removes_partial_file_and_preserves_previous_backups(): void
    {
        $this->fakeConnection();
        mkdir($this->directory, 0700, true);
        file_put_contents($this->directory.'/old.sql.gz', 'existing');
        $service = Mockery::mock(DatabaseBackupService::class)->makePartial();
        $service->shouldReceive('dump')->andReturnUsing(function ($db, $write) {
            $write('partial');
            throw new RuntimeException('provider secret');
        });
        try {
            $service->create();
            $this->fail('Expected failed dump');
        } catch (RuntimeException) {
            $this->assertSame('existing', file_get_contents($this->directory.'/old.sql.gz'));
            $this->assertSame([], glob($this->directory.'/.backup-*'));
            $this->assertCount(1, glob($this->directory.'/*'));
        }
    }

    public function test_sql_values_preserve_large_numbers_unicode_quotes_null_and_binary(): void
    {
        $service = new DatabaseBackupService;
        foreach (['123', '18446744073709551615', '123.4500', "Tiếng Việt ' \\ \n"] as $value) {
            $this->assertSame("CONVERT(X'".bin2hex($value)."' USING utf8mb4)", $service->literal($value, 'text'));
        }
        $this->assertSame("X'00ff'", $service->literal("\0\xff", 'blob'));
        $this->assertSame('NULL', $service->literal(null, 'text'));
    }

    public function test_sqlite_test_database_is_rejected_without_creating_files(): void
    {
        $this->expectException(DatabaseBackupException::class);
        (new DatabaseBackupService)->create();
    }

    public function test_command_reports_failure_without_leaking_sensitive_error_content(): void
    {
        $service = Mockery::mock(DatabaseBackupService::class);
        $service->shouldReceive('create')->with(null)->andThrow(new RuntimeException('SECRET_PASSWORD'));
        $this->app->instance(DatabaseBackupService::class, $service);
        $this->artisan('db:backup')->expectsOutputToContain('Backup thất bại')->doesntExpectOutputToContain('SECRET_PASSWORD')->assertFailed();
    }

    public function test_daily_schedule_uses_vietnam_timezone_and_prevents_overlap(): void
    {
        $event = collect(Schedule::events())->first(fn ($event) => str_contains($event->command ?? '', 'db:backup'));
        $this->assertNotNull($event);
        $this->assertSame('0 2 * * *', $event->expression);
        $this->assertSame('Asia/Ho_Chi_Minh', $event->timezone);
        $this->assertTrue($event->withoutOverlapping);
    }
}
