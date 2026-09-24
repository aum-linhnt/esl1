# Phase 3 — Knowledge / Tutor Core (development candidate)

Single installation / one centre. One Composer package; no tenant multiplexing.
No License Server implementation or license bypass is included.

## Implemented

- Forward-only migration adds documents, immutable content versions, chunks/local vectors,
  processing records, conversations, message turns, source links and feedback.
- Text/Markdown/HTML ingestion as content, UTF-8/size checks and bounded paragraph chunks.
  HTML is reduced to text; it is never rendered as trusted HTML.
- Explicit draft -> ready -> published flow. Publishing a replacement archives the previous
  version. Only the current published version participates in retrieval.
- Local exact cosine vector search behind VectorStore; model/dimension checks, bounded top-k.
  VectorStore::put must be an idempotent upsert and must throw if indexing fails.
  An external implementation is not allowed to bypass the service's visibility recheck.
- Course, lesson, subject, level, visibility and answer-key filtering. V1 requires a lesson
  on every document; course-wide/global documents are not implicitly visible.
- Authenticated tutor conversations; course/lesson/question data from the LMS adapter.
  Conversation owner checks run on read, write, source, feedback and reconnect.
- Teaching modes and server-controlled answer policy. exam/no_answer/teacher_controlled
  return a deterministic refusal without an AI call. Other policies are prompt constraints,
  not a formal guarantee against a model generating an answer. The adapter never supplies
  locked answer keys. Keep exam content under no_answer and answer documents excluded.
- OpenAI Responses and embeddings, normalized usage/data DTOs, store=false for Responses,
  fixed HTTPS origin/no redirects, no automatic paid transport retries.
- All chat/embedding calls pass AiExecutionService -> BillingManager, including retrieval
  query embeddings. A RAG turn can consume BOTH embedding and chat credits.
- Real provider SSE on POST; persisted final response and read-only GET reconnect.
  Stream deltas are provisional: no completed event until usage/credits/results are saved.
  GET does not re-run inference. Intermediate deltas are not durably replayed after disconnect;
  reload/poll the conversation until completion, or retry with exactly the same request IDs.
- Queue jobs contain only a version ID. Actor ID comes from the persisted processing record
  and is reauthenticated by BackgroundActor; current rights/license are checked again.
- Basic admin Knowledge page and full-page chat, scoped light/dark CSS, text-only rendering,
  source inspection, loading/errors and same-ID retry. No provider key is sent to the browser.

API documentation used:
- https://developers.openai.com/api/reference/cli/resources/responses/methods/create
- https://developers.openai.com/api/docs/guides/embeddings

## Deploy on staging after backup/review

Do not edit vendor; it is a Composer path symlink in this repository.
No development/production migration was run while implementing this change.

~~~bash
docker compose exec -T app php artisan ai-tutor:schema-check
docker compose exec -T app php artisan migrate --path=vendor/tdsoft/ai-tutor/database/migrations
docker compose exec -T app php artisan ai-tutor:schema-check
~~~

New migration:
2026_09_25_000003_create_tutor_ai_knowledge_conversations.php

Expected extra schema-check line after migration:
AI Tutor knowledge/conversations: installed

Build assets with Node >=22.12 using the existing website Vite entries, then rebuild
configuration/views according to the existing deployment process. Restart long-lived workers
after deployment. Do not force-publish config or rerun old migrations manually.

Environment (set locally; never send keys in chat):

~~~dotenv
AI_TUTOR_ENABLED=true
AI_BILLING_MODE=customer_key
AI_DEFAULT_PROVIDER=openai
AI_DEFAULT_CHAT_MODEL=
AI_DEFAULT_EMBEDDING_MODEL=
OPENAI_API_KEY=
~~~

Choose chat/embedding model IDs available to the customer's OpenAI account; no model or
provider pricing is silently assumed. Empty model/key fails closed. Changing embedding model
requires new document versions and reindexing before publishing; vectors from different models
or dimensions are not mixed.

A valid signed license must allow ai_tutor_core and, for Knowledge/RAG, ai_tutor_knowledge.
Without it the new pages/API correctly remain forbidden. Test mocks work ONLY in testing.

## Credit setup (trusted administrator / server-side only)

Migration does not grant money/credit or change existing pricing rules.
Before a real request, configure enabled credit rules for tutor_message and knowledge_embedding,
and provision funded credit accounts for learners and for the administrator who queues ingestion.
Existing CreditAccounts::openLearner and CreditLedger::grant are the supported provisioning helpers.
Never update ledger balances directly or auto-fund through a public request.

Example rule setup in a reviewed staging seeder (1 product credit per request is illustrative,
NOT a provider price; choose your own values):

~~~php
foreach (['tutor_message', 'knowledge_embedding'] as $feature) {
    DB::table('tutor_ai_credit_rules')->insertOrIgnore([
        'feature' => $feature, 'base_units' => 1, 'max_units_per_request' => 1,
        'enabled' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
}
~~~

No rule -> AI_CREDIT_RULE_INVALID. No funded account -> AI_CREDIT_INSUFFICIENT.
No cost rate configured -> estimated cost is unknown/null, not a fabricated provider cost.

## Worker

Use a real async database/Redis connection; do not use sync for document processing.
Each job indexes at most one chunk, then enqueues continuation. Job timeout 60s,
embedding HTTP timeout 40s, queue retry_after must be greater than 60s (repository default 90s).
Run the worker through the existing process manager:

~~~bash
docker compose exec -T app php artisan queue:work --queue=ai-tutor-knowledge,default --timeout=60 --tries=3
~~~

Jobs use stable chunk request IDs. Repeated jobs do not embed or charge completed chunks again.
Known provider failures remain failed under their original request ID; they do not silently create
new billable attempts. After correcting configuration, deliberately create/process a new version.
If a request is processing/reconciliation-required after a crash, inspect provider usage and local
reservation before taking action. Do not clear reservations or reset IDs automatically.
A crash can leave a processing record busy; resolve through operational review, not forced retry.

## Pages and API

- /admin/ai/knowledge — administrator only; create draft, queue processing, inspect status, publish.
- /ai-tutor?lesson_id=ID — full-page tutor for an accessible LMS lesson.

Session API prefix is /ai-tutor/api/v1, not /api/ai-tutor/v1: the host currently exempts /api/*
from CSRF. All writes require session auth + CSRF; feature routes require the signed license.
Browser cannot choose actor, answer policy, system prompt, provider, billing mode or source URLs.

~~~text
GET/POST /conversations
GET      /conversations/{id}
POST     /conversations/{id}/messages
GET      /messages/{id}/stream
GET      /messages/{id}/sources/{chunk}
POST     /messages/{id}/feedback
GET/POST /knowledge/documents
POST     /knowledge/documents/{id}/versions
GET      /knowledge/document-versions/{id}
POST     /knowledge/document-versions/{id}/process
POST     /knowledge/document-versions/{id}/publish
~~~

Messages POST: message, request_id UUID, idempotency_key (<=191 chars).
Accept: text/event-stream returns start, delta, completed or error events.
Otherwise JSON returns the completed turn. Preserve both identifiers on retry.
One turn row stores learner input plus assistant result; raw encrypted prompt snapshots are never
returned through these endpoints. Sources are filtered again when read, including after unpublish
or permission loss. Model text is always rendered as text, not Markdown/HTML execution.
Creation/list endpoints currently cap lists at 100; chat context uses the last 8 completed turns.

## Remaining work / release limitations

This is a Phase 3 core slice, not a declaration that the entire V2 specification is complete.

- Binary PDF/DOCX/PPTX/image upload, antivirus/quarantine and OCR are NOT enabled. Only text
  ingestion is accepted; unsupported formats fail closed. Do not enable uploads until safe
  extraction/scanning adapters and storage/retention policy are implemented.
- Course-content bulk sync and attempt-bound random/inline quiz contexts are not implemented.
  The existing adapter continues denying question contexts it cannot safely bind.
- Lesson drawer/launcher, expand-to-page state transfer and the public window.AITutor widget API
  are not implemented in this slice; current UI is a full page. Speaking/Writing remain Phase 4.
- Knowledge version-management UI is minimal; APIs support adding versions, not a complete browser.
- No teacher-policy editor/profile UI, automatic retention/export/delete workflow or admin
  reconciliation UI yet. Define operational retention before production learner-data use.
- No production OpenAI call, signed-license activation, browser E2E, MySQL concurrency test,
  large-corpus benchmark or release tag has been performed. SQLite tests do not prove MySQL locking.
- Exact local cosine search is intended for modest per-lesson corpora. Replace VectorStore and
  benchmark when corpus size requires ANN/vector infrastructure.

## Verification

~~~bash
docker compose exec -T app php vendor/bin/phpunit -c packages/tdsoft/ai-tutor/tests/phpunit.xml
docker compose exec -T app php artisan view:cache
npm run build
~~~

Tests use SQLite :memory:, fake entitlements confined to tests and fake HTTP responses. No external
OpenAI requests or real license bypass. Coverage includes publish filtering, answer-key exclusion,
lesson/owner revocation, duplicate billing, queue serialization/retry, native SSE parsing, CSRF
with the host's API wildcard exclusion, safe provider errors and forward schema preservation.
