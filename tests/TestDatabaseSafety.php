<?php

namespace Tests;

use Illuminate\Contracts\Config\Repository;
use RuntimeException;

final class TestDatabaseSafety
{
    public static function enforce(Repository $config): void
    {
        $connection = $config->get('database.connections.sqlite', []);
        if ($config->get('app.env') !== 'testing'
            || $config->get('database.default') !== 'sqlite'
            || ($connection['driver'] ?? null) !== 'sqlite'
            || ($connection['database'] ?? null) !== ':memory:'
            || ! empty($connection['url'])) {
            throw new RuntimeException('Refusing website tests outside URL-free SQLite :memory: in testing environment');
        }

        // A test cannot accidentally opt into a named host connection either.
        $config->set('database.connections', ['sqlite' => $connection]);
    }
}
