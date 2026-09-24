# Changelog

## Phase 3 — unreleased development candidate

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
