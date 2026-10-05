# Phase 4 — English foundation and Writing Studio (development)

Implemented first slice, 2026-10-05:

- English target/task/feedback-language validation: CEFR A1–B2, TOEIC targets,
  IELTS practice targets. TOEIC targets use CEFR-style practice tasks; these
  practice scores are not official TOEIC, IELTS or CEFR certification.
- Immutable rubric version provisioning with idempotent fingerprints. Trusted
  server-side `PracticeRubrics::provision(new RubricRepository)` provisions seven
  sample rubrics explicitly; migrations/boot do not seed, fund credit or call AI.
- Forward migration `2026_10_05_000007_create_tutor_ai_english_assessments`:
  rubrics/versions, writing drafts/revisions/submissions/issues, speaking
  attempts/segments, assessment scores. No LMS table or old migration rewritten
  for this schema. Destructive rollback is disabled.
- Read-only schema-check and migration preflight detect partial/colliding
  installations, missing columns, primary keys and required unique indexes.
- Assessment state transitions prevent silent requeue of terminal/uncertain
  outcomes. Application services must apply these under a database lock.
- Assessment result validation uses trusted evidence capabilities, validates
  numeric scores/evidence and calculates weighted practice scores. Missing
  acoustic/timing evidence forces pronunciation/fluency to `not_available`;
  an incomplete rubric has a null overall score, never a default passing score.

The Writing follow-ups below add services, jobs, provider support, session API
and a full-page editor. This is not yet a complete Writing/Speaking release:
recorder, legacy assessment cutover and STT remain pending. Existing legacy
AI assessment stays unchanged until the new engine/UI are ready for cutover.

## Writing backend follow-up

- `WritingDrafts`: create/get/autosave; revision conflict returns
  `AI_WRITING_REVISION_CONFLICT`. Changed saves create immutable revision rows;
  saving identical content does not create an extra revision.
- `WritingSubmissions`: submit snapshots the original/profile/topic/rubric and
  encrypted prompt/schema; no provider call occurs on submit. Same request IDs
  replay the original snapshot after later autosaves. A new ID cannot repeat
  the same revision or bypass a busy/uncertain submission.
- Revised drafts submitted after a completed assessment use `writing_recheck`;
  the browser cannot choose feature, rubric, actor, model, prompt or policy.
- Owner/module/LMS access and exam/no-answer checks run on submit, retry,
  processing, final persistence and result read. Standalone practice is allowed
  without a lesson; no website models are referenced by package services.
- `ProcessWritingSubmission` carries only the submission ID and reauthenticates
  the persisted actor through `BackgroundActor`. Queue `ai-tutor-assessments`,
  timeout 60 seconds, provider HTTP timeout 40 seconds, retry_after >60 seconds.
  HTTP submit rejects sync/unsupported queues and invalid retry_after before
  creating a submission.
- Execution uses `AiExecutionService -> BillingManager`. JSON/schema/evidence
  validation happens after provider usage is durably settled; invalid output
  marks assessment failed and cannot erase charges or generate default scores.
  Text evidence must quote the original. UTF-16 issue spans must match exactly;
  unmatched spans remain feedback-only, with null offsets and applicable=false.
- Completed results/scores/issues commit before the completion event is handled.
  Repeated jobs do not repeat provider inference, settlement or the event.
  A crash after billing completion can recover by replaying the encrypted result;
  a processing submission with no settled request remains blocked for review.
- New paid retry requires confirmation, a known safe failure, balanced
  reserve/release and no commit. Only one retry child is permitted. Admin
  reconciliation release enables retry; reconciliation commit blocks it.
  Provider results, failed requests and ledger history remain intact.

### Session API

All routes have web session/auth, module entitlement, schema guard and throttle.
Writes require CSRF; the routes are deliberately outside the host's `/api/*`
CSRF exception. Missing Phase 4 migrations return `AI_WRITING_SCHEMA_REQUIRED`.

```text
POST  /ai-tutor/api/v1/writing/drafts
GET   /ai-tutor/api/v1/writing/drafts/{id}
PATCH /ai-tutor/api/v1/writing/drafts/{id}
POST  /ai-tutor/api/v1/writing/drafts/{id}/submit
GET   /ai-tutor/api/v1/writing/submissions/{id}
POST  /ai-tutor/api/v1/writing/submissions/{id}/retry
```

Draft creation: framework, target, feedback_language (default vi), task, topic,
content (optional), lesson_id/course_id (optional pair). Content is bounded to
20,000 UTF-8 bytes; topic to 5,000 bytes. PATCH takes revision + content.

Submit: revision, request_id UUID, idempotency_key <=191 chars and
confirm_cost=true. The server selects the latest provisioned `english_{task}`
rubric; same-ID replay uses the original version even after a rubric upgrade.
Submit returns 202 until completed; GET result never calls a provider.
Retry: new request_id/idempotency_key and confirm_retry=true. Preserve IDs if
the connection drops; do not generate new IDs until recovery=new_attempt.
Recovery states: completed, same_request, new_attempt, reconciliation, blocked.

Forward migration (new, not applied to the live database):
`2026_10_05_000008_add_writing_execution_snapshot.php`, after migration 000007.
Preflight/schema-check also report `AI Tutor Writing execution: pending/installed`.
After staging backup/review, apply forward migrations through the deployment
process; do not run migrations on a real database to verify this implementation.
Provision practice rubrics explicitly using the trusted helper above.

Configuration: AI_WRITING_MODEL (empty fails closed) and
AI_WRITING_MAX_OUTPUT_TOKENS=4000. Choose a model supporting the required
structured-output schema and available in the customer's account; none is
silently selected. Provide enabled `writing_assessment` and `writing_recheck`
credit rules and a funded account through existing admin tools. No automatic
grant or provider price is introduced.

Worker command after deployment:

```bash
docker compose exec -T app php artisan queue:work --queue=ai-tutor-assessments,ai-tutor-knowledge,default --timeout=60 --tries=3
```

Responses strict output uses `text.format` with a JSON schema, based on
[official OpenAI documentation](https://developers.openai.com/api/docs/guides/structured-outputs?api-mode=responses).
Provider requests retain store=false, fixed HTTPS origin, no redirects and no
automatic paid transport retries. No real provider request was performed.
Prompt constraints do not guarantee model compliance; exam/no-answer blocks
are deterministic server checks.

Limitations: no Writing export/delete workflow or legacy assessment route
cutover yet; no auto gradebook or reward listener. Consumers must deduplicate
the stable event ID. Event delivery after a process crash between database commit
and listener execution is not guaranteed by a durable outbox in this slice.
SQLite tests do not establish MySQL locking or browser behavior.

## Writing Studio UI follow-up

- Full pages `/ai-tutor/writing` and `/ai-tutor/writing/{draft}` with owner,
  entitlement and schema guards. The LMS sidebar shows Writing Studio when
  enabled, entitled and the Writing execution schema is installed. The existing
  AI Writing endpoint is preserved until legacy cutover is implemented.
- CEFR/TOEIC/IELTS task/target selectors, Vietnamese/English/bilingual feedback
  choice, topic, editor, word count and UTF-8 byte limit. Task/profile/topic are
  fixed when creating a draft; autosave changes essay content only.
- Autosave serializes writes and preserves edits typed while a save is in flight.
  Lost PATCH responses are reconciled by reading the current server content;
  stale revisions open a comparison panel. Keeping local text creates a new
  revision only after an explicit choice; using the server version requires
  confirmation before discarding local edits.
- Actor/draft-scoped sessionStorage retains unsynced text and request IDs.
  Paid submission is blocked when recovery IDs cannot be stored. LMS logout
  clears Writing cache. Theme changes modify CSS tokens without replacing the
  editor/session; leaving with unsynced text or a pending request triggers the
  browser's unload warning.
- Submit and paid retry have explicit confirmations. An interrupted POST keeps
  exactly the original request/idempotency IDs. Read-only lookup/polling can
  recover accepted submissions without a POST; an unregistered request can be
  resumed with the same IDs. A definitive revision rejection is checked against
  owned history before clearing pending state and opening conflict resolution.
  Reconciliation/blocked outcomes never silently create another paid attempt.
- Feedback is text-only. Criteria and real credit units/balance come from the
  owner-checked result endpoint. Unknown usage/balance remain unknown in the UI.
  The immutable assessed essay has verified UTF-16 spans highlighted. Applying
  individual fixes preserves original offsets across previous fixes; overlapping
  spans or intervening manual edits disable unsafe automatic replacements.
- Paginated draft and assessment history (20/page); revoked lesson titles are
  filtered out. GET history and request-ID lookup never call providers. No
  encrypted prompts, credentials or raw provider bodies are returned.
- No new migration for this UI follow-up. Existing Phase 4 migrations 000007
  and 000008, rubric provisioning, credit/provider setup and async worker are
  still required on reviewed staging. No live database migration was run.

Additional read-only endpoints:

```text
GET /ai-tutor/api/v1/writing/drafts?page=1
GET /ai-tutor/api/v1/writing/drafts/{id}/submissions?page=1
GET /ai-tutor/api/v1/writing/drafts/{id}/submissions?request_id=UUID
```

The website Vite entry imports `resources/js/writing.js` from the package;
SCSS includes the Writing stylesheet. Hosts with their own asset pipeline must
import those modules too. No AI-created image assets or external UI libraries
were required.

UI validation: 31 JavaScript tests pass (14 Writing tests), package suite
140 tests / 897 assertions, and focused website suite 26 tests / 186 assertions.
The isolated browser smoke passes autosave, theme persistence, lost-submit
read-only recovery, text-only feedback, Unicode correction, reload, two-tab
conflict, confirmed retry and mobile layout. Its mock server never loads the
host bootstrap, database, license or provider. Real authenticated browser E2E
and MySQL concurrency validation remain pending.

Production-mode Vite build passed with Node 22.13.1 into
`/tmp/esl1-writing-build`. The regular `public/build` directory has existing
container-owned assets and initially rejected writes. The missing-style/runtime
report was subsequently fixed by building into `/tmp/esl1-writing-live-build`
with assetsDir=assets-writing, copying verified assets to
`public/build/assets-writing`, then atomically replacing the manifest after
every target file existed. Prior assets and their permissions were preserved.
The previous manifest is backed up at `/tmp/esl1-writing-assets-manifest-before.json`.
Live HTTP checks returned 200 and byte-for-byte matches for Writing JS/SCSS
and app JS; Laravel's Vite helper resolves the new manifest paths. No database
or license configuration was changed for this asset update.

## Data contracts

- Rubric snapshot records version ID, criteria, prompt version and fingerprint;
  later rubric provisioning never updates an old version.
- Draft revision is monotonically increasing. Each saved revision must be stored
  independently; submit snapshots content/profile/topic/rubric and request IDs.
- Writing issues use UTF-16 offsets for browser editor compatibility. Future
  writers must validate offsets against the submission's original text.
- A score row belongs to exactly one writing submission or speaking attempt;
  future writers must enforce this invariant and validate criterion names.
- Audio paths are internal private-storage references, never browser-selected
  URLs; endpoints must not return them. Audio validation/upload is not enabled.
- `reconciliation_required` can only be resolved through reviewed durable
  provider/ledger evidence; the state helper is not authorization to retry.
- Evidence types supplied to `AssessmentResult` are server capability metadata,
  not evidence flags from learner input or generated JSON. Text evidence still
  needs source/span checks in the consuming assessment service.

## Verification

Package tests use their isolated SQLite in-memory harness and fake providers:

```bash
docker compose exec -T app php vendor/bin/phpunit -c packages/tdsoft/ai-tutor/tests/phpunit.xml
```

Validation: package suite 121 tests / 754 assertions, including 12 new
foundation tests. Website Feature suite with the new guard: 146 tests /
660 assertions; 5 failures and 1 error remain in legacy exam/question skill
validation, exam count, avatar upload and exercise-generator call signature.
No new Phase 4 endpoint/provider/UI is exercised by that website suite.

Website tests now bootstrap all environment sources to SQLite `:memory:` and
reject other configured connections before `RefreshDatabase`. The old learner
skills MySQL ALTER migration skips its MODIFY statements on SQLite, whose
columns already store text. This is a testing portability change, not a schema
change for MySQL deployments.

Do not apply the Phase 4 migration to the live database as implementation
verification. Staging rollout remains pending the full engine/UI and review.

## Baseline incident and pending work

During the initial website baseline run on 2026-10-05, the existing PHPUnit
configuration did not override Docker's `$_SERVER` database settings. The
website `RefreshDatabase` suite used the configured MySQL database. Read-only
inspection subsequently found zero users/courses/lessons and the Phase 4
migration installed. The missing SQLite bootstrap/guard has now been added.

Two existing backups (10:43 and 10:52 local time on 2026-10-05) were found and
successfully decompressed, each containing INSERT statements for users,
courses and lessons. The owner subsequently approved recovery, and the
10:52 snapshot has now been restored successfully.

Latest backup selected for review:
`esl-2026-10-05_10-52-46-2a37a6ae-8c54-401c-954d-241615b6abb8.sql.gz`.
Compressed size 81,076 bytes; decompressed size 719,833 bytes; 51 CREATE TABLE
statements and INSERT statements for users/courses/lessons. SHA-256:
`0c569f7a95e8eaac1194c2276c0c6a83da1de0f1142aa5e53599501d55b5e3e4`.
The dump contains no DROP TABLE, USE or CREATE DATABASE statements, so simply
importing into the current schema is not a valid restore plan.

Recovery procedure performed after explicit approval:

1. Temporarily stop application writes/workers and preserve a fresh dump of the
   current database plus its migration state in a separate recovery artifact.
2. Verify the selected backup's integrity and import into an isolated temporary
   MySQL database first; inspect schema, row counts, FK consistency and migration
   history without exposing learner content or credentials.
3. Replace the affected application's database contents with the validated
   10:52 snapshot; remove tables created by the erroneous test run that were
   absent from the snapshot. Preserve configuration and storage files.
4. Verify restored counts and migration history, resume the application and
   perform read-only smoke checks. Do not apply Phase 4 migrations to it yet.

Verification: all 51 restored tables have exactly the same row counts as the
isolated validation import; no foreign-key orphan references were found.
Restored users: 3, courses: 2, lessons: 6. All 49 migration-history entries
match the backup. The erroneous Phase 4 tables/migration are no longer in
the application database; Phase 4 source code remains in the workspace.
Application and scheduler containers were unpaused after successful validation.

A separate snapshot of the database immediately before restore is preserved
under private backups as
`esl-before-phase4-restore-2026-10-05-78e43be0.sql.gz`.
The two original backups remain unchanged. Private validation reports and the
recovery script are in `/tmp/esl1-phase4-recovery`; they contain no printed
credentials or learner content. The temporary validation database was removed
after successful recovery.

Only data present in the 10:52 snapshot was recovered; later edits are not
guaranteed by this backup. No further live migration was run after recovery.

Writing backend validation: package suite 140 tests / 877 assertions;
focused website Writing/API, database-safety, quiz-context and lesson-policy
suite 24 tests / 143 assertions. Provider tests prevent stray HTTP requests;
all databases are SQLite memory. Pint and diff checks pass.

Next: Speaking audio validation/storage, STT and evidence-aware assessment;
Writing export/delete and legacy cutover remain release follow-ups. Follow
[PHASE4-PLAN.md](PHASE4-PLAN.md) for subsequent Speaking and release work.
