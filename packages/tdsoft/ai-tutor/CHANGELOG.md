# Changelog

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
