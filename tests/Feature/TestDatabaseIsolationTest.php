<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PDO;
use Tests\TestCase;

class TestDatabaseIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_refresh_database_uses_real_in_memory_sqlite_and_has_no_mysql_connection(): void
    {
        $this->assertSame('testing', config('app.env'));
        $this->assertSame('sqlite', DB::connection()->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME));
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->assertSame('', DB::selectOne('PRAGMA database_list')->file);
        $this->assertNull(config('database.connections.mysql'));
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Database connection [mysql] not configured');
        DB::connection('mysql');
    }
}
