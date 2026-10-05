# Changelog

## Phase 4 — unreleased foundation

- Add English profile/task validation, immutable practice rubric versions,
  forward assessment schema, preflight checks and evidence-aware score validation.
- Add website test bootstrap/connection guard before RefreshDatabase; isolate
  all environment sources to SQLite memory and skip legacy MySQL-only MODIFY there.
- Add Writing draft/revision services, immutable submission/prompt snapshots,
  owner/context/exam guards, async assessment jobs and session/CSRF API.
- OpenAI Writing assessments use a separate configured model and strict JSON schema;
  output validation occurs after usage settlement, with evidence/span checks.
- Stable-ID replay, confirmed safe retry and admin reconciliation integration;
  no duplicate charge/completion event, fabricated scores or gradebook writes.
- Forward migration 000008 adds execution snapshots; no live migration applied.
- Add Writing Studio pages with word count, autosave/conflict comparison, credit
  confirmation, durable same-ID recovery, Unicode-safe issue fixes and light/dark mobile UI.
- Add owner-filtered paginated draft/assessment history and read-only request lookup.
- Add isolated mock Writing preview and browser smoke; no new UI migration.
- Writing export/delete, legacy route cutover, Speaking and full release verification remain pending.
  See [PHASE4.md](PHASE4.md), including the baseline database incident and
  successful recovery from the approved 10:52 database backup.

## Current-question quiz chat — unreleased

- Server snapshots at quiz start and question chat for inline/manual/random quizzes.
- Attempt owner, membership, access, status and deadline rechecked at conversation and core AI execution.
- Separate question/attempt drafts, safe context switching and same-origin iframe integration.
- Quiz snapshot/conversation attempt migrations; submission updates the started attempt.
- Website tests enforce SQLite in all environment sources and reject other configured databases.

## Lesson answer policy — unreleased

- Website lesson policy editor for administrators, owning/assigned teachers and course managers.
- Forward LMS migration with safe existing-lesson defaults; legacy lesson updates preserve omitted policy fields.
- Trusted LessonContext carries teacher solution permission and an exam override; browser cannot change these.
- Explicit hint progression 1–4, policy-specific caps and immediate exam/no_answer refusal without AI charges.
- Retry/replay preserves stage and action; policy changes reset progress and invalidate incompatible prepared contexts.
- Next-hint controls in both chat views, mock/provider and authorization regression tests.

## Chat recovery — unreleased

- Same-conversation, explicitly confirmed new attempts after known safe failures and credit release.
- Server-side recovery states, linked retry history and one child per failed turn under the conversation lock.
- Completed-result replay and interrupted retries preserve IDs; ambiguous/unsettled outcomes stay blocked.
- Reuse prepared RAG payloads with permission/source rechecks instead of charging query embeddings again.
- No schema migration or change to the immutable execution/ledger replay contract; rebuild frontend assets.

## Credit admin — unreleased

- Admin rule editor and additive credit grants through a dedicated LMS adapter.
- CSRF/auth checks, optimistic rule conflicts, idempotent grants and transactional audit.
- Forward audit migration 000005; existing quotas, pricing blocks and request snapshots preserved.
- See [CREDIT-ADMIN.md](CREDIT-ADMIN.md) for deployment and usage.

## Knowledge course sync — unreleased

- Separate LMS source adapter, admin course/lesson preview and explicit paid-queue confirmation.
- Forward-only sync mapping migration; stable fingerprint/version reuse and stale-preview rejection.
- No automatic publication; existing published versions and manually entered documents remain intact.
- Runtime summary-access check excludes paid synced sources from activity-only trial access.
- Website adapter exports visible lesson titles/summaries only, never quiz/activity answer payloads.

## License mode — unreleased

- Explicit source_owned opt-in with module allowlist; server remains the default.
- Invalid/empty modes fail closed. No HTTP toggle or automatic fallback.
- Skip domain/license checks only in source_owned; retain LMS/auth/credit controls.
- Disable license activation/refresh and background refresh in source-owned deployments.
- Admin/CLI status and regression tests; no schema or existing license-cache changes.

## Phase 3 — unreleased development candidate

- Follow-up: permission-gated lesson widget, responsive drawer and in-place fullscreen expansion.
- Public widget API, actor/lesson-scoped draft recovery and shared standalone chat partial.
- Version browser/preview/new version and withdrawal, without changing released migrations.
- Owner-only conversation export/delete; opt-in retention with default dry run and busy protection.
- Upload/PDF/Office/OCR explicitly left disabled at the owner's request.

- Local Knowledge documents/versions/chunks and replaceable VectorStore.
- Queue ingestion of text/Markdown/HTML, explicit publish and permission-filtered retrieval.
- Conversation ownership, source links, teaching policies and idempotent paid requests.
- OpenAI Responses/embedding driver, real POST SSE and read-only result reconnect.
- Forward migration, basic Knowledge admin/full-page Tutor UI and mock-only tests.
- See PHASE3.md for staging runbook and explicit remaining release limitations.

## 0.1.0 — unreleased

- Phase 2: Ed25519 verification, canonical license document and persisted installation identity.
- Encrypted local license/key cache; HTTPS activation/refresh with safe audit and retry.
- Offline deadline min(last verified contact + 7 days, signed grace_until), signed revocation.
- Offline entitlement resolver, module middleware, protected admin license page and scheduler job.
- Forward-only license migration and preflight, plus License Server protocol documentation.

- Single-package foundation, website actor/LMS contracts and safe defaults.
- Requests, rules/accounts/append-only ledger, usage and cost snapshots.
- Customer-key driver with mock provider tests; vendor-credit contract/blocked placeholder.
- Idempotent encrypted replay, transactional credit, rule snapshots and quota.
- Durable encrypted response before settlement; ambiguous failures require reconciliation.
- Entitlement contract and offline verifier, no per-request license HTTP.
- Versioned post-commit event skeleton, website Vite JS/SCSS pipeline.
- Schema preflight, initial forward migration and upgrade documentation.

The original foundation did not include a runtime provider, Tutor or Knowledge.
The unreleased Phase 3 changes above add those core paths; License Server, Speaking
and Writing are still not implemented.
