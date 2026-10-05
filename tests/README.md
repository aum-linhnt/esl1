# Database isolation

Website PHPUnit loads `tests/bootstrap.php`, overriding Docker environment values in getenv,
`$_ENV` and `$_SERVER`. `Tests\TestCase` checks the loaded configuration immediately after
`LoadConfiguration`, before providers and `RefreshDatabase`: only the testing environment,
SQLite driver, `:memory:` database and no connection URL are permitted. Named host connections
are removed from the test configuration, so `DB::connection('mysql')` fails without connecting.
Unsafe cached configuration is refused rather than used for database resets.

```sh
docker compose exec -T app php vendor/bin/phpunit
docker compose exec -T app php artisan test
docker compose exec -T app php vendor/bin/phpunit --filter 'TestDatabaseSafetyTest|TestDatabaseIsolationTest'
docker compose exec -T app php vendor/bin/phpunit -c packages/tdsoft/ai-tutor/tests/phpunit.xml
```

The package suite builds its own Capsule connection with SQLite `:memory:` and does not boot
the host application or load its database configuration. Both suites avoid the host MySQL DB.
Ordinary application migration commands do not use this test guard.

Verified on 2026-10-05: all 51 MySQL `esl` tables, 384 rows, data hashes and schema hashes
were identical before and after the full website/package suites and the Artisan isolation test.
