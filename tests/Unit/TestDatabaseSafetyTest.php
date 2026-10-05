<?php

namespace Tests\Unit;

use Illuminate\Config\Repository;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\TestDatabaseSafety;

class TestDatabaseSafetyTest extends TestCase
{
    private function config(): Repository
    {
        return new Repository([
            'app' => ['env' => 'testing'],
            'database' => ['default' => 'sqlite', 'connections' => [
                'sqlite' => ['driver' => 'sqlite', 'database' => ':memory:', 'url' => null],
                'mysql' => ['driver' => 'mysql', 'database' => 'esl'],
                'pgsql' => ['driver' => 'pgsql', 'database' => 'live'],
            ]],
        ]);
    }

    public function test_safe_config_removes_all_named_host_connections(): void
    {
        $config = $this->config();
        TestDatabaseSafety::enforce($config);
        $this->assertSame(['sqlite'], array_keys($config->get('database.connections')));
        $this->assertSame(':memory:', $config->get('database.connections.sqlite.database'));
    }

    public function test_unsafe_configs_are_rejected_before_they_can_be_used(): void
    {
        foreach ([
            ['database.default', 'mysql'],
            ['database.connections.sqlite.driver', 'mysql'],
            ['database.connections.sqlite.database', 'database/database.sqlite'],
            ['database.connections.sqlite.url', 'mysql://host/live'],
            ['database.connections.sqlite.url', 'sqlite:///persistent.sqlite'],
            ['app.env', 'local'],
        ] as [$key, $value]) {
            $config = $this->config();
            $config->set($key, $value);
            try {
                TestDatabaseSafety::enforce($config);
                $this->fail('Unsafe test config accepted: '.$key);
            } catch (RuntimeException $error) {
                $this->assertStringContainsString('Refusing website tests', $error->getMessage());
            }
        }
    }
}
