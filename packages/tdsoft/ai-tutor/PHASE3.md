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

Use the admin **Rule & Credit AI** page at `/admin/ai/credits` after applying
the audit migration 000005. See [CREDIT-ADMIN.md](CREDIT-ADMIN.md) for rule setup,
granting credit to the ingestion admin and learners, and quota limitations.
The seeder example below is an alternative, not required for the admin UI.

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

### Đồng bộ Knowledge từ khóa học

Có migration tiến tới mới:
2026_09_25_000004_create_tutor_ai_knowledge_sync_links.php.
Migration cũ giữ nguyên. Bảng tutor_ai_knowledge_sync_links ghi liên kết nguồn LMS với
document/version và fingerprint; không sửa bảng LMS, credit hoặc license.

Sau backup/review tại staging:

~~~bash
docker compose exec -T app php artisan ai-tutor:schema-check
docker compose exec -T app php artisan migrate --path=vendor/tdsoft/ai-tutor/database/migrations
docker compose exec -T app php artisan ai-tutor:schema-check
~~~

Mong đợi: AI Tutor knowledge sync: installed.
Build Vite, cập nhật config/view cache và restart worker theo quy trình triển khai.
Không có biến môi trường mới. Không chạy migration trên database thật trong lúc phát triển.

Trên /admin/ai/knowledge:

1. Tải khóa học, chọn khóa học, bấm Xem trước.
2. Kiểm tra nội dung, cảnh báo, số chunk và chọn tối đa 50 bài/lần.
3. Bấm Đồng bộ để tạo bản nháp (không gọi provider, không trừ credit).
4. Nếu muốn tạo vector ngay, tick ô queue và xác nhận chi phí trước khi đồng bộ.
   Worker ai-tutor-knowledge dùng credit của người tạo version; cần rule knowledge_embedding,
   tài khoản có credit và provider/model hợp lệ. Chunk count không phải báo giá USD.
5. Bấm kết quả từng bài để chọn version, kiểm tra ready rồi publish như luồng hiện có.
   Đồng bộ không tự publish, không thay bản đang phục vụ học viên.

Contract KnowledgeSourceAdapter nằm trong package, WebsiteKnowledgeSourceAdapter nằm ở LMS.
Adapter hiện chỉ xuất tiêu đề/tóm tắt của bài đang hiển thị; không đọc activities, question banks,
đáp án, attempt, video hoặc file. Bài không có summary bị bỏ qua; nội dung ngắn có cảnh báo.
Không sinh thêm kiến thức bằng AI. Người quản trị phải duyệt nội dung trước publish.
Quyền đọc full summary được kiểm tra lại khi RAG/citation truy xuất nguồn đã đồng bộ,
tránh lộ summary trả phí cho người chỉ được xem hoạt động học thử.

Mỗi khóa học tối đa 200 bài trong một preview; danh sách khóa học tối đa 500.
Preview đọc lại nguồn trên server lúc xác nhận. Thay đổi nội dung/model sau preview trả
AI_SYNC_PREVIEW_STALE và không ghi batch. Nguồn không đổi tái sử dụng document/version;
retry không tạo version/chunk request mới. Có thể chọn bài unchanged để đưa lại version vào queue.
Queue retry vẫn tuân theo giới hạn idempotency/reconciliation hiện có.

Nguồn đổi tạo version mới trong document được quản lý bởi sync, không ghi đè nội dung cũ
hoặc tài liệu nhập tay. Đổi subject/level của document đã liên kết trả AI_SYNC_CONTEXT_CHANGED:
cần quản trị viên rà soát lại mapping/content, không âm thầm sửa phạm vi bản đã publish.
Sync không tự xóa/thu hồi tài liệu khi bài biến mất; adapter chặn truy xuất summary của bài ẩn.

API mới cùng prefix /ai-tutor/api/v1 và guard admin/module/CSRF:

~~~text
GET  /knowledge/sync/courses
GET  /knowledge/sync/preview?course_id=ID
POST /knowledge/sync
~~~

POST nhận course_id, lessons=[{lesson_id,fingerprint}], enqueue boolean.
Không nhận raw source content hoặc actor từ browser.
Preview server độc lập hiện chỉ mô phỏng luồng tài liệu thủ công, chưa có API sync khóa học.

### Follow-up implemented: widget, versions, conversation lifecycle

No new migration is required for this follow-up. The already applied Phase 3 migration
is unchanged. Rebuild Vite assets and Blade views; restart scheduler/long-lived workers
through the normal deployment process. Existing client-side view overrides must include
the new shared chat partial and actorId value.

The website now embeds <x-ai-tutor::widget :course-id="..." :lesson-id="..." /> in
resources/views/lessons/show.blade.php. The component resolves permissions through the
package contracts and stays hidden when disabled/unlicensed/inaccessible.
Both namespaced and legacy x-ai-tutor-widget aliases are registered.

- Drawer/floating panel, left/right launcher, mobile fullscreen.
- Expand/collapse reuses the same DOM and stream reader: no navigation, no duplicate AI call.
  Expanded view is fullscreen in the current page, not a navigation to another browser document.
  The standalone /ai-tutor page remains available; completed conversation/draft state is scoped
  by actor and lesson in sessionStorage. Navigating away while streaming is still a disconnect;
  use in-place Expand to preserve the live stream.
- window.AITutor.open(), close(), ask(text), setContext({lessonId}) are exposed for the widget.
  setContext validates lesson access on the server and refuses changes while busy.
- Version browser lists lesson documents and versions, previews content as text, creates new
  immutable versions, and withdraws the published version without deleting its history.
- Conversation export and confirmed deletion are owner-checked. Deletion removes messages,
  sources, feedback and encrypted response replay content, but preserves credit/usage history.
  Busy/unsettled requests block deletion. No real conversation was deleted during development.

Optional configuration:

~~~dotenv
AI_TUTOR_LAUNCHER_POSITION=bottom-right
AI_TUTOR_LESSON_CHAT_MODE=drawer
AI_TUTOR_DESKTOP_PANEL_WIDTH=420
AI_TUTOR_ALLOW_EXPAND_TO_PAGE=true
AI_CONVERSATION_RETENTION_ENABLED=false
AI_CONVERSATION_RETENTION_DAYS=365
~~~

Panel width is constrained to 320–640 px; invalid configuration is rejected before rendering.
Retention is OFF by default. Review the period and backups before enabling it. Retention uses
the latest conversation/message update, skips busy requests and keeps accounting history.
Backups and existing browser downloads are outside this local deletion workflow.

Read-only retention preview:

~~~bash
docker compose exec -T app php artisan ai-tutor:purge-conversations
~~~

Only an administrator's explicit --execute invocation, or opt-in enabled scheduler, deletes data.
There is no application-level undo. Export first if necessary. The scheduler runs daily only
when AI_CONVERSATION_RETENTION_ENABLED=true.

Additional API routes under the same auth/CSRF/license-protected prefix:

~~~text
GET    /context?lesson_id=ID
GET    /knowledge/documents/{id}/versions
GET    /knowledge/document-versions/{id}/content
POST   /knowledge/documents/{id}/withdraw
GET    /conversations/{id}/export
DELETE /conversations/{id}
~~~

Frontend unit checks (no downloaded browser or real provider needed):

~~~bash
node --test packages/tdsoft/ai-tutor/tests/js/widget.test.mjs
~~~

### Still outstanding

This is a Phase 3 core slice, not a declaration that the entire V2 specification is complete.

- Binary PDF/DOCX/PPTX/image upload, antivirus/quarantine and OCR are NOT enabled. Only text
  ingestion is accepted; unsupported formats fail closed. Do not enable uploads until safe
  extraction/scanning adapters and storage/retention policy are implemented.
  The owner explicitly chose to keep upload disabled for later integration. The current
  application container has no ClamAV, pdftotext or Tesseract; no containers/tools were installed.
- Bulk sync tiêu đề/tóm tắt đã có; đồng bộ nội dung hoạt động/file và attempt-bound
  random/inline quiz chưa triển khai. Adapter tiếp tục từ chối ngữ cảnh không ràng buộc an toàn.
- Speaking/Writing remain Phase 4.
- Document/version lists currently cap at 100 entries; pagination and large-corpus administration
  remain future improvements.
- No teacher-policy editor/profile UI or admin reconciliation UI yet.
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
