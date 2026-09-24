# Installation and upgrade

## Compatibility

Phase 3 development candidate: read [PHASE3.md](PHASE3.md) before applying the new
2026_09_25_000003_create_tutor_ai_knowledge_conversations.php migration. It does not
alter previously published migrations, pricing rules, accounts or license state.
Binary upload/OCR and the full lesson widget remain release limitations.

| Component | Constraint / verification |
|---|---|
| Package | Initial 0.1.0 foundation + license candidate, no release tag |
| PHP | ^8.3 + ext-sodium; automated tests on PHP 8.3 |
| Laravel | ^13.17; repository lock 13.26.1 |
| Database | MySQL 8+ target; SQLite :memory: automated tests |
| Frontend | Website Vite 8, Node >=22.12, Sass |
| LMS | ActorResolver + LmsContextAdapter, opaque string IDs |

MySQL DDL/parallel locking requires staging verification on an isolated database whose
name ends in _testing. SQLite does not prove MySQL concurrency semantics.
No previous release exists: current upgrade test checks recognized migration history,
schema and data preservation. Add previous-tag upgrade fixtures from the next release.

## First installation

Development uses the root Composer path repository. For distribution create a private
repository, tag a reviewed version and replace path repository/0.1.x-dev with the private
repository and stable constraint. Do not publish the development path lock.
Download credentials belong to deployment secret storage.

Only on target staging after backup/review:

~~~bash
composer install
php artisan package:discover
php artisan ai-tutor:schema-check
php artisan migrate --path=vendor/tdsoft/ai-tutor/database/migrations
php artisan ai-tutor:schema-check
~~~

For this repository replace php with docker compose exec -T app php.
Initial migration: 2026_09_24_000001_create_tutor_ai_foundation.php, six tutor_ai_* tables.
Phase 2 adds 2026_09_24_000002_create_tutor_ai_license_tables.php (state and refresh attempts).
Keep the already applied foundation migration intact; only the new migration is pending.
Migration stops before DDL if any target already exists. Preflight with history accepts
owned tables, checks required columns/unique indexes and reports drift.
Investigate unrecorded partial installs; never drop or silently adopt them.
Schema checker is a structural preflight, not a full forensic verifier.

Down refuses destructive rollback. Never migrate:fresh, migrate:reset, db:wipe or rollback
to repair an installation. Released migrations are immutable; add forward migrations.
No provider credentials/license keys needed for foundation installation. Feature remains
disabled and entitlement denies until provider/license phases complete.

## Upgrade runbook

1. Review changelog/compatibility; back up DB, uploads, config, APP_KEY and license public key.
2. Update package on staging, review dependency diff and commit tested Composer lock.
3. Run schema-check then pending package migrations. Stop on errors; inspect before retry.
4. Import package entries through website Vite; npm ci and npm run build on CI/staging.
   Include built manifest/assets in deployment artifact; production needs no Node.
5. Test package and adapter with mocks; check credit/history/config preservation.
6. Production: composer install --no-dev --prefer-dist --optimize-autoloader using tested lock.
7. Run schema-check and reviewed migrations (--force only in authorized deployment).
   php artisan config:cache; rebuild views as release requires.
   php artisan queue:restart if this installation runs workers.
   Phase 2 registers a queued license refresh check every minute. Configure the scheduler
   and worker using your existing process manager; see LICENSE-CLIENT.md.
8. Smoke check discovery, adapter binding, schema, existing LMS and assets.

No admin HTTP Composer/migration action. Update availability is informational in V1.
Never force-publish config on normal upgrades. Preserve settings and review view overrides;
copied views do not get upstream fixes. Recovery depends on code/schema compatibility
and post-backup data, not merely reverting code. Pause rollout before recovery.

## Verification

~~~bash
docker compose exec -T app php vendor/bin/phpunit -c packages/tdsoft/ai-tutor/tests/phpunit.xml
php8.3 /usr/local/bin/composer validate --no-check-publish
npm run build
~~~

Host defaults to PHP 7.4 and Node 20.9 in NVM; use Docker PHP and Node 22.12+.
No production/dev database migration is part of implementation verification.
