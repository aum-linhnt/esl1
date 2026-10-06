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

## Blade cache isolation

Each website test process compiles Blade into a private random
`/tmp/esl1-phpunit-views-*` directory (0700), configured before service providers
boot. Tests do not write into `storage/framework/views`, even when the CLI runs
as root or cached test configuration has a live view path. This prevents root-owned
compiled files from breaking PHP-FPM's `touch()` operation after source edits.

For application cache maintenance, run Artisan as the PHP-FPM user:

```sh
docker compose exec -T --user application app php artisan view:cache
```

Verified on 2026-10-05: all 51 MySQL `esl` tables, 384 rows, data hashes and schema hashes
were identical before and after the full website/package suites and the Artisan isolation test.
