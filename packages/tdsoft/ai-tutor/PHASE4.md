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

Limitations: no server-side full Writing data export/delete workflow or legacy assessment route
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

## Unified Writing UI — light/dark layout and results

- One responsive view/session for both themes: draft metrics, topic and plain-text
  editor on the left; assessment panel on the right; stacked on smaller screens.
- Circular overall practice score and criterion meters use API values on the
  existing 0–100 scale. Missing scores display a dash and no filled meter;
  actual zero remains a scored criterion. No conversion to an IELTS band.
- Accessible keyboard-operated tabs separate AI feedback from detailed fixes.
  Each fix shows original/replacement text and explanation, preserving verified
  UTF-16 application checks and text-only rendering. Tab selection survives edits.
- Empty/pending/error status, actual credit usage/balance, revision and autosave
  state remain visible. No fake timer, rich-text controls or unimplemented AI
  features were added. Navigation returns to the LMS or opens a new draft.
- Verification: production Vite build; 31 JS tests; focused website tests
  9 tests / 88 assertions in guarded SQLite memory with isolated view cache;
  isolated Chrome smoke including themes, keyboard tabs, missing-vs-zero scores,
  autosave, interrupted-submit recovery, Unicode fixes, conflicts and mobile.
- Local assets built under `/tmp/esl1-writing-redesign-final` and published under
  `public/build/assets-writing-ui` with an atomic manifest replacement. Previous
  manifest saved at `/tmp/esl1-writing-ui-manifest-before.json`; previous assets
  preserved. No migration, provider call or `.env` change for this UI revision.

## Unified entry and draft workspace

- The entry page now opens the same two-column workspace as existing drafts.
  Setup (profile/task/feedback language/topic) sits above the editor in the left
  column. The right result panel remains visible, with empty scores and guidance.
- Before creation, the editor and save/assessment controls are disabled. Target
  selection updates the metric immediately. “Bắt đầu viết” creates a draft using
  the existing API, replaces setup with its saved topic and enables/focuses the
  editor without leaving the workspace. History remains below the workspace.
- No backend/schema/provider changes. Browser smoke covers the initial panel,
  disabled controls, target preview, mobile width and the in-place transition,
  alongside all existing recovery/autosave/theme tests. JS 31 tests and focused
  SQLite PHP suite 9 tests / 88 assertions pass.
- Assets built in `/tmp/esl1-writing-unified-final`, copied to
  `public/build/assets-writing-unified`; verified manifest replaced atomically,
  with the previous manifest at `/tmp/esl1-writing-unified-manifest-before.json`.

## LMS shell integration for Writing

- The website sets `ai-tutor.ui.writing_layout=layouts.app` in its integration
  provider. Writing reuses the existing LMS sidebar, header, account menu,
  branding and navigation rather than drawing a separate brand/account header.
- The shared LMS layout has a head stack to load Writing's configured Vite
  entries. Package users retain the standalone default layout; other hosts can
  choose a layout with a content section and head stack.
- Writing theme tokens are scoped to its content root. The LMS shell retains
  its current styling; switching Writing's theme preserves editor state.
  Container queries collapse the editor/result columns when sidebar width
  reduces available content space.
- Focused website tests assert the authenticated user's real name/email and
  LMS sidebar are rendered, and the standalone brand/header is absent.
  SQLite/view-cache isolation remains in force (9 tests / 94 assertions).
- Assets built in `/tmp/esl1-writing-lms-verified` and published to
  `public/build/assets-writing-lms`. Previous manifest retained at
  `/tmp/esl1-writing-lms-manifest-before.json`. No database or `.env` changes.

## Writing readability and initial guidance

- Compact setup fields and larger helper text/placeholder text; the existing
  LMS header/sidebar and account integration are preserved.
- Before grading, the result panel shows a three-step guide instead of an empty
  score ring and tabs. Completed results reveal criteria, score and fix tabs.
- Before draft creation, actions are hidden. Save appears for unsynced edits;
  refresh appears for a submission or pending request. Recovery/retry safeguards
  and autosave remain unchanged. History pagination hides on a single page and
  the duplicate new-draft link was removed.
- Validation: 31 JS tests, 9 guarded SQLite PHP tests / 94 assertions, isolated
  browser smoke for initial visibility, creation, themes, recovery, Unicode fixes,
  keyboard tabs and narrow LMS content. No paid provider call or DB change.
- Verified Vite assets published to `public/build/assets-writing-polish`; previous
  manifest backed up at `/tmp/esl1-writing-polish-manifest-before.json`.

## Compact pre-writing state

- Disabled editor starts at 96px, with ancillary editor UI hidden until a draft
  is loaded. Creation expands the editor and retains all existing save safeguards.
- Routine success/instruction banners are hidden; errors remain visible with
  live status announcements. Draft/assessment state remains in existing panels.
- Task 1 input guidance only appears for IELTS Task 1, updating when framework
  or task changes. Empty-state robot SVG centered explicitly for LMS CSS resets.
- Empty first-page history is a compact row; populated history/pagination retain
  existing behavior. LMS header/sidebar unchanged as requested.
- Production Vite build and extended isolated browser smoke pass: compact editor,
  conditional task guidance, creation/expansion, mobile, themes and recovery.
  Assets published under `public/build/assets-writing-compact`, previous manifest
  saved at `/tmp/esl1-writing-compact-manifest-before.json`.

## Exact quote recovery for Writing fixes

- Incorrect model offsets can recover from an exact quote only when it appears
  once in the immutable assessed essay. Server computes UTF-16 positions from
  the UTF-8 prefix. Missing/ambiguous quotes never enable replacement; valid
  exact offsets still disambiguate repeated quotes. No fuzzy matching.
- Blank/whitespace replacements are feedback-only; UI omits their apply button
  and labels them as manual feedback, preventing accidental essay deletion.
- Result GET revalidates stored issues so earlier completed submissions benefit
  without rewriting immutable results, scores, billing or invoking a provider.
- Read-only verification of Admin #1's latest actual assessment confirmed both
  grammar fixes are now applicable, while blank coherence feedback stays manual.
  No additional credit spent. Existing overlap/manual-edit guards stay intact.
- Validation: package 141 tests / 907 assertions, Writing JS 14 tests, production
  Vite build, extended isolated browser smoke including blank replacement UI.
  Assets published in `public/build/assets-writing-spans`; prior manifest saved
  at `/tmp/esl1-writing-spans-manifest-before.json`.

## Visible background assessment failures

- Failed result polling now displays a persistent accessible alert from the
  known error-code message map. Credit shortage explains that the draft is
  preserved and the learner should contact an administrator to add credits.
- Failed assessments no longer show the misleading waiting-for-results guidance.
  Balance zero remains visible as zero. Unknown failure codes use a safe generic
  message without exposing provider bodies. No automatic funding or paid retry.
- Read-only audit confirmed Admin #1 balance=0 and latest failure code
  AI_CREDIT_INSUFFICIENT. No extra credit granted or provider call performed.
- Writing JS 14 tests, production Vite build and extended mock browser smoke
  pass, including insufficient-credit polling alert and draft preservation.
  Assets published in `public/build/assets-writing-credit-ui`, prior manifest
  saved at `/tmp/esl1-writing-credit-ui-manifest-before.json`.

## Insufficient-credit popup

- Native modal dialog opens for AI_CREDIT_INSUFFICIENT from direct actions or
  a failed background result. It explains how to add credit through the admin
  and that the draft remains saved. The persistent panel error stays visible.
- Each failing request is shown once per page session, so edits/polling/refresh
  do not repeatedly open the popup. Keyboard focus moves into the dialog; close
  button and Escape use native dialog semantics. Theme uses Writing tokens.
- Production build and isolated browser smoke passed, including focused close
  control, dismissal and no repeat popup after refreshing the same failure.
  No provider call, credit grant or DB change. Assets published under
  `public/build/assets-writing-credit-popup`; previous manifest backed up at
  `/tmp/esl1-writing-credit-popup-manifest-before.json`.

## Writing confirmation modals

- All four browser confirm calls in Writing replaced with an accessible native
  dialog styled with Writing light/dark tokens: paid submit, confirmed paid retry,
  keeping local conflict text, and discarding local text for the server version.
- Promises resolve only after the modal closes. Cancel/Escape resolve false;
  initial focus is on Cancel. Duplicate triggers cannot replace an outstanding
  confirmation. Existing cost/retry/ownership/idempotency safeguards unchanged.
- The browser's unsaved-work beforeunload prompt remains browser-managed.
- Vite build, Writing JS 14 tests and extended browser smoke pass, including
  cancel/Escape sending zero paid POSTs, modal-confirmed submit/retry/conflict
  resolution, credit popup, themes and mobile. No live credit/provider call.
  Assets published under `public/build/assets-writing-modals`, previous manifest
  saved at `/tmp/esl1-writing-modals-manifest-before.json`.

## Writing visual refinement

- Added title icon, compact setup section heading, consistent surface shading,
  lighter typography for inputs, distinct metric icons and stronger topic/result
  accents. Editor height adapts to viewport rather than oversized row height;
  the compact pre-draft state remains intact. Modal spacing and controls align
  with the same light/dark design. No change to existing LMS shell/account.
- Responsive typography/spacing and reduced-motion preference preserved.
  Theme, content and control state are unchanged by styling.
- Verified Vite build, website Writing API/view tests (6 / 81 assertions), and
  isolated browser smoke for themes, mobile, modal confirmations/cancellation,
  credit popup, autosave, recovery and fixes. No real provider call or DB change.
- Assets published to `public/build/assets-writing-visual`; previous manifest
  saved at `/tmp/esl1-writing-visual-manifest-before.json`.

## Writing UI — compact editor and grouped feedback

- Keep the existing LMS header, sidebar, branding and account controls.
- Move target, save status, word count and revision into the editor header;
  remove the three separate metric cards. Use a wider editor column and a
  larger score ring with navy surfaces in dark mode and matching light mode.
- Show actual issue counts grouped by category in the assessment overview.
  Clicking a group opens detailed feedback filtered to that category. Filter
  chips support all issues or a single category, preserve selection while
  editing the same result, and reset when a different result is displayed.
- Keep verified sentence replacement, confirmation dialogs, credit handling,
  responsive layout and safe text rendering intact.
- Validation: 14 Writing JavaScript tests, 6 guarded SQLite Writing API tests
  / 81 assertions, isolated browser smoke and production Vite build passed.
  No provider calls, credit grants or database changes for this revision.
- Published assets under `public/build/assets-writing-grouped`; previous
  manifest backup: `/tmp/esl1-writing-grouped-manifest-before.json`.

## Writing UI — collapsible AI feedback

- Compact AI feedback card shows four lines by default, with an accessible
  “Xem thêm / Thu gọn” button only when the text overflows. Full feedback stays
  available as safe text; expansion persists while editing the same result
  and resets for a different result. Resize observation handles narrow layouts.
- Validation: 14 Writing JavaScript tests, production build and isolated browser
  smoke passed, including expand/collapse, preservation across category changes,
  short feedback without a toggle, light/dark and mobile. No provider calls or
  database changes. Assets published under `public/build/assets-writing-feedback`.

## Writing UI — restored summary cards

- Restore target, live word count and draft save status as three cards directly
  above the prompt in the compose column. Move their existing data selectors
  from the editor header to the cards so each value remains a single live field.
- Keep compact, expandable AI feedback and existing LMS navigation.
- Validation: isolated browser smoke and guarded SQLite Writing API tests
  (6 tests / 81 assertions) passed. Template-only change uses existing assets.

## Writing UI — compact prompt card

- Restyle the prompt with a small document icon, soft purple background and
  compact typography. Keep the summary cards above it and the existing LMS shell.
- Add an accessible “Xem gợi ý / Thu gọn gợi ý” disclosure containing general
  writing guidance. This guidance is static and uses no AI call or credit.
- Validation: production build and isolated browser smoke passed, including
  opening/closing guidance, light/dark, mobile and existing submission flows.
  Published assets: `public/build/assets-writing-prompt`.

## Writing UI — editable document toolbar

- Replace the textarea with a contenteditable document and toolbar: undo/redo,
  paragraph/headings, bold, italic, underline, strikethrough, ordered/unordered
  lists, live word count and expanded editing (Escape exits). Use shared SVG
  icons and display save status below the document.
- Keep plain-text revision storage and provider input. Formatting applies while
  composing and is not persisted by the current backend; reload or automatic
  sentence replacement reconstructs plain text. Toolbar is disabled before
  draft creation and while the session is busy or unavailable.
- Extract text from DOM block boundaries explicitly to preserve empty lines
  without layout-dependent innerText duplicates. Paste accepts plain text only;
  dropping external content is blocked. AI output is still rendered as text.
- Validation: 14 Writing JS tests, 6 guarded SQLite API tests / 81 assertions,
  production build and isolated browser smoke passed. Browser checks cover
  multiline reload, bold, lists, HTML paste exclusion, expanded mode/Escape,
  autosave, Unicode corrections, recovery, light/dark and mobile.
- Published assets: `public/build/assets-writing-editor`. No provider call,
  database migration or credit change for this UI revision.

## Writing UI — saved time

- Draft responses expose `updated_at` as ISO UTC; browser displays the last
  confirmed save time as “Đã tự động lưu lúc HH:mm” in local browser time,
  in both the summary card and editor footer. Preserve unsaved/saving/conflict
  states and use confirmed server time for load, save, recovered PATCH and
  conflict resolution. Missing timestamps show “Đã tự động lưu” without a
  fabricated time.
- Validation: 14 JS tests, 6 guarded SQLite API tests / 81 assertions,
  production build and isolated browser smoke passed. Recovery test verifies
  unsaved edits do not advance the saved timestamp. No migration or AI call.
- Published assets: `public/build/assets-writing-saved-time`.

## Writing UI — inline issue highlights and tooltips

- CSS Custom Highlight ranges mark verified grammar issues red and other
  verified suggestions yellow, without inserting markup into the editable
  document. Map UTF-16 text offsets across formatted DOM nodes and block
  boundaries using the same text extraction as autosave.
- Hover shows category, explanation and replacement as safe text. Keyboard
  caret movement can show the same tooltip; Escape, blur and scrolling dismiss
  it. Tooltips adapt to theme and available width.
- Only render highlights when current text exactly matches the assessed
  original. Edits and sentence application clear stale markers. Unverified
  spans remain in detailed feedback without inline marks. Browsers without
  CSS Custom Highlight support retain the existing detailed feedback.
- Validation: 14 Writing JS tests, production build and extended isolated
  browser smoke passed, covering red/yellow ranges, Unicode offsets, mixed
  formatting, safe tooltip text, stale clearing, autosave and mobile.
- Published assets: `public/build/assets-writing-highlights`. No provider
  request, migration or credit use for this UI revision.

## Writing UI — historical submission view

- Selecting an assessment history entry displays its immutable original text
  in the editor alongside that submission's result and verified highlights.
  Historical text is read-only; formatting, submission and automatic issue
  application controls cannot change it. Revision and word count follow the
  selected submission.
- Historical selection is separate from WritingSession state. Current text,
  pending autosave, assessment recovery and persisted result are retained.
  “Quay lại bản nháp” restores the editable current draft and current result.
- Validation: production build and extended isolated browser smoke passed,
  checking old text, read-only controls, historical highlights, unchanged
  server draft, returning to editing, reload and existing mobile/recovery flows.
- Published assets: `public/build/assets-writing-history-view`. No AI call,
  database migration or credit use.

## Writing UI — compact editor footer

- Restyle saved status/count on a soft background, align action buttons and
  highlight the submit action. Refresh uses the existing shared SVG icon.
- Combine the credit note and collapsed save guidance in a compact inset
  panel. Guidance now uses the current “Đã tự động lưu” status wording.
- Adapt controls to mobile, with the submit action spanning the available
  width. Hide notes before starting and while viewing historical submissions.
- Validation: production build and isolated browser smoke passed; mobile
  screenshot reviewed. Existing save, confirmation, history and recovery
  behavior retained. Published assets: `public/build/assets-writing-footer`.

## Writing UI — adaptive editor height and single footer

- Editor grows naturally with content from 220px to 450px, then scrolls;
  expanded editing retains its larger viewport and initial disabled editor
  remains compact. No height calculation rewrites the document or selection.
- Combine save status and actions into one footer row, wrapping on mobile.
  Remove duplicate word/byte text from the footer; word count remains in the
  toolbar and summary card. Credit disclosure remains in submission/retry
  confirmation dialogs. Save guidance opens from a small information control.
- Validation: production build, 6 guarded SQLite API tests / 81 assertions and
  extended isolated browser smoke passed. Short/long document sizing, internal
  scrolling, shrinking after deletion and save guidance were verified alongside
  historical viewing, highlighting, autosave and mobile. Screenshot reviewed.
- Published assets: `public/build/assets-writing-compact`. No AI request or
  database change.

## Writing UI — separate save guidance card

- Restore “Cách lưu bài viết” as a separate card below the editor, with the
  full guidance visible. Remove the information popover from the footer.
  Compact editor height and single-row actions remain in place.
- Validation: production build and isolated browser smoke passed, including
  visibility and position below the editor. Published assets:
  `public/build/assets-writing-save-guide`.

## Writing UI — single correction at a time

- Detailed feedback displays one filtered issue at a time, with position/total
  and previous/next controls. Category changes and new results reset the
  position; edits retain it. Navigation preserves original issue indexes for
  safe sentence replacement.
- Compact red original/green suggested comparison with a directional arrow,
  emphasis on changed words and a tinted explanation panel. Narrow mobile
  layouts stack the comparison. All provider text still uses safe text nodes.
- Preserve category filters, manual-only feedback and read-only historical
  views. Apply only verified replacements to the current editable draft.
- Validation: 14 Writing JS tests, production build and extended isolated
  browser smoke passed, including navigation limits, filters, manual feedback,
  historical views and sentence application.
- Published assets: `public/build/assets-writing-issue-carousel`.

## Writing UI — shared SVG icons

- Replace character-based toolbar symbols, navigation arrows, comparison arrow,
  feedback disclosure arrows and new-draft plus with shared outline SVG icons.
  Dynamic controls clone trusted Blade icon templates; provider strings remain
  text only. Decorative SVGs are aria-hidden and buttons retain their labels.
- Validation: production build and isolated browser smoke passed, including
  toolbar actions, feedback expansion, issue navigation, hover and mobile.
- Published assets: `public/build/assets-writing-svg`.

## Writing UI — corrections in assessment overview

- Show the same “Gợi ý chỉnh sửa câu” card at the bottom of the overview tab,
  after assessment feedback and issue groups. Both tabs share the filtered
  issue position, pagination and safe application logic. Hide the overview
  correction block when no assessed issues exist.
- Preserve read-only historical viewing and render provider strings as text.
  Navigation icons use the shared SVG templates.
- Validation: production build and extended isolated browser smoke passed,
  including overview pagination and existing detail/apply/history/mobile flows.
- Published assets: `public/build/assets-writing-overview-corrections`.

## Writing UI — assessment history rows

- Compact history rows show an SVG document icon, revision, creation time,
  colored assessment status and view action. Latest row is labelled on the
  first page. Selected history row uses aria-pressed and “Đang xem”; returning
  to the current draft clears selection. Keep repeated revisions as separate
  assessment attempts and preserve pagination.
- Submission listing returns ISO timestamps for consistent browser-local
  date/time display. Rows adapt to narrow screens without horizontal overflow.
- Validation: production build, 6 guarded SQLite API tests / 81 assertions and
  isolated browser smoke passed. Selected state, timestamps and return to draft
  were verified; mobile screenshot reviewed.
- Published assets: `public/build/assets-writing-history-polish`.

## Writing UI — strengths and improvements

- Add green “Điểm tốt” and amber “Cần cải thiện” panels after AI feedback,
  with shared SVG icons, safe bullet text and light/dark surfaces.
- New execution snapshots request essay-grounded strengths and improvements
  in the chosen feedback language. Validate up to five non-empty strings per
  field and retain them in existing result JSON; no migration is needed.
- Older snapshots/results remain supported. Missing strengths show an explicit
  no-data message; missing improvements use existing issue explanations. Do
  not infer praise from scores or automatically regrade historical submissions.
- Validation: Writing backend suite 16 tests / 93 assertions, production build
  and isolated browser smoke passed. Mock UI checks confirm both panels.
- Published assets: `public/build/assets-writing-feedback-points`. No actual
  provider call, new credit grant or migration during this change.

## Writing UI — two-line feedback previews

- Add a shared sparkle SVG to the AI feedback heading. Default feedback and
  improvements previews show at most two lines, with accessible expand/collapse
  controls when content overflows. Improvements keep full bullet content and
  expansion state during edits; new results reset the state.
- Validation: production build and isolated browser smoke passed, including
  SVG heading, two-line CSS and improvements expand/collapse.
- Published assets: `public/build/assets-writing-two-lines`.

## Writing UI — correction actions

- Add SVG action buttons below sentence corrections in both tabs: view errors,
  show improvement guidance, and resubmit. View errors opens the assessed
  original in detailed feedback; improvement guidance expands existing points
  in the overview without a provider call. IELTS uses “Gợi ý nâng band”, other
  frameworks use “Gợi ý cải thiện”.
- Resubmit follows the existing session eligibility and credit-confirmation
  modal; it is disabled for unchanged drafts and historical views. Mobile
  layouts stack the actions.
- Validation: production build and extended isolated browser smoke passed,
  including guidance expansion, original error view and cancelling resubmission
  without another POST. Published assets: `public/build/assets-writing-result-actions`.

## Writing UI — shared actions outside result card

- Move the three correction actions into a single shared row below and outside
  the result card. Keep it visible for assessed results across both tabs,
  including assessments without issues. Existing eligibility and confirmation
  behavior remain intact.
- Validation: production build and isolated browser smoke passed, verifying
  the row is outside any card, visible in both tabs, and submit exists once.
- Published assets: `public/build/assets-writing-shared-actions`.

## Writing — literal evidence recovery

- Provider evidence sometimes contains quoted essay text plus commentary.
  Recover only literal substrings inside quote wrappers; exclude parenthetical
  correction suffixes. Never accept paraphrases, invented text or fuzzy matches.
- Criteria without any valid evidence become unavailable; recalculate overall
  with the existing complete-rubric rule. Entirely unsupported claimed scores
  still fail with AI_ASSESSMENT_EVIDENCE_INVALID. Prompt now explicitly requires
  exact standalone essay substrings without explanations or quotation wrappers.
- Add a Vietnamese browser message for the evidence error.
- Validation: 18 backend tests / 102 assertions, 6 API tests / 81 assertions
  and production build passed. Read-only verification of the failing cached
  IELTS response recovered three criteria; task response remains unavailable
  and overall null. Historical failure state is unchanged; no provider call,
  credit grant or paid retry was performed.
- Published assets: `public/build/assets-writing-evidence`.

## IELTS Writing — direct band scoring

- Provision versioned IELTS-only rubrics with prompt version `ielts-band-v1`:
  Task 1 Task Achievement, Task 2 Task Response, plus Coherence & Cohesion,
  Lexical Resource and Grammatical Range & Accuracy. All weights are 25%.
  Source: https://ielts.org/cdn/ielts-guides/ielts-writing-band-descriptors.pdf
- Direct AI band estimates use 0–9 with 0.5 increments; schema and backend
  reject percentage scores and other increments. Backend calculates equal
  weighted mean rounded to nearest half band (ties upward), independently of
  provider overall. This is an AI practice estimate for one task, not a full
  Writing exam or an official score. Unsupported evidence yields unavailable
  criteria and null overall.
- Prompt gives concise band calibration, task-specific requirements and asks
  for unavailable Task Achievement when Academic Task 1 source information
  is missing. Existing text-topic input accepts source data in text; image
  upload and separate Academic/General Training selector are not included.
- Rubric snapshots carry the scale. Old IELTS snapshots/results and retries
  retain practice_0_100, while new submissions select latest IELTS version.
  CEFR/TOEIC current practice scoring is unchanged. No schema migration.
- UI displays band label, one-decimal scores, IELTS criterion names and meters
  out of nine, while old/CEFR results retain their previous scale.
- Validation: package 145 tests / 927 assertions, website Writing API 6 tests
  / 81 assertions, production build and extended isolated browser smoke passed.
  Live provision created IELTS rubric version 2 for both tasks, idempotently.
  No paid AI request or credit grant during this work.
- Published assets: `public/build/assets-writing-ielts`.

## Writing UI — selectable test examples

- New-draft setup offers six original, intentionally imperfect examples:
  CEFR A1/A2/B1/B2, Academic IELTS Task 1 with source table and IELTS Task 2.
  Selection fills framework, target, task, Vietnamese feedback, topic and
  initial content. Fields remain editable; creating the draft is explicit.
- Add an optional initial-content textarea using the existing draft API field
  with byte-size validation. Existing drafts are unaffected; selecting a
  fixture makes no request and never submits for paid assessment automatically.
- Validation: production build, 6 API tests / 81 assertions and extended
  isolated browser smoke passed. All example selectors and IELTS draft-content
  preservation were verified. No live AI call or credit change.
- Published assets: `public/build/assets-writing-examples`.

## Writing — cached evidence failure recovery

- Add trusted process maintenance flag for evidence failures only. Recovery
  requires an owned record, settled completed billing and retained encrypted
  provider response; regular jobs still leave terminal failures unchanged.
  Reuse the execution service replay path, not a new paid attempt.
- A2 failed result 2ecc898f-0b21-486f-b980-b7bd61a08b21 validated successfully
  under current code. Long-running worker was restarted to load updates;
  record recovered to completed with balance unchanged at 38.
- Validation: Writing backend 21 tests / 118 assertions, including cache replay
  recovery with one provider call and one credit commit across original/recovery.
  Worker restart/launch confirmed. No additional grant or inference performed.

## Writing analysis — criterion reasons and priority fixes

- New execution snapshots ask for per-criterion rationale and next_step in the
  selected feedback language, grounded in the rubric and essay. Priority actions
  contain up to three concrete fixes ordered by impact; no promised band gain.
- Validate and persist new fields in existing result JSON (no migration).
  Evidence verification still determines supported scores; discard score
  explanations if the criterion was downgraded for unsupported evidence.
- Overview adds a maximum-three priority list and collapsible “Vì sao đạt điểm
  này?” with criterion scores, rationale, verified evidence quotes and next steps.
  Safe text nodes preserve literal provider content. Historical results show
  missing-analysis messages; fallback fixes from existing improvements clearly
  disclose they have no dedicated priority ranking.
- Validation: package 146 tests / 937 assertions, API 6 tests / 81 assertions,
  production build and isolated browser smoke passed. Browser checks cover
  priority list, analysis disclosure, evidence and text-only output.
- Worker restarted to load current schema/validation. No actual AI call or
  credit use. Published assets: `public/build/assets-writing-analysis`.

## Writing analysis — revision comparison

- Read-only result responses compare against the latest completed lower
  revision belonging to the same actor/draft and exact rubric version.
  Require matching score scales and criterion keys. Null/unavailable scores
  do not produce a numeric change; same-revision retries are not a baseline.
- Overview shows prior/current overall, signed criterion changes and before/
  after issue counts. Clearly explain counts do not establish that particular
  errors were fixed. Historical views compare their own prior revisions.
- Validation: package 147 tests / 946 assertions, API 6 tests / 81 assertions,
  production build and isolated browser smoke passed. Tests cover actual
  revision lookup, positive/negative deltas, unavailable scores and scale mismatch.
- No migration, provider call or credit charge. Published assets:
  `public/build/assets-writing-comparison`.

## Writing analysis — paragraph feedback

- Include numbered original paragraphs in new prompt snapshots, splitting on
  blank lines. Request up to eight paragraph comments and concrete next steps
  tailored to the task and feedback language. No model answer or essay rewrite.
- Validate paragraph indexes, uniqueness and text limits; excerpts are derived
  from immutable submitted text rather than provider claims. Store analysis in
  existing result JSON. Old snapshots missing the field remain supported.
- Overview adds a collapsed “Phân tích theo đoạn” disclosure with original
  excerpts, comments and next steps. Provider content is rendered as text.
- Validation: package 148 tests / 952 assertions, API 6 tests / 81 assertions,
  build and browser smoke passed, including paragraph mapping, invalid indexes,
  duplicate entries, Unicode excerpts and safe disclosure content.
- Worker reloaded. No paid inference, migration or credit use. Published assets:
  `public/build/assets-writing-paragraphs`.

## Writing analysis — task requirement checks

- New prompts request up to six topic-grounded requirements with met/partial/
  not_met/not_available status, comments and exact essay evidence. Backend
  validates fields and removes invented quotes; positive/partial claims without
  verified evidence become unavailable rather than fabricated achievements.
- Overview adds a collapsed requirement checklist. Count words from the
  immutable submitted original; IELTS Task 1/Task 2 use 150/250 reference
  minimums. Word count is approximate and does not automatically change band.
  Historical results without checks show a clear missing-data note; no new
  inference is needed to display word count.
- Validation: package 149 tests / 960 assertions, API 6 tests / 81 assertions,
  production build and browser smoke passed. Worker reloaded for new snapshots.
  No migration, live inference or credit grant.
- Published assets: `public/build/assets-writing-requirements`.

## Writing assessment report download

- A compact SVG “Tải báo cáo” button in the result heading downloads a UTF-8
  plain-text report, available from either result tab after completion.
- Exports the immutable assessed essay for the selected submission, its
  revision/ID, fixed draft topic/profile, scores, criterion evidence, feedback,
  strengths, improvements, priorities, task requirements, paragraph analysis
  and sentence corrections. Editing the current draft never changes the report.
- IELTS retains its 0–9 band scale and Task 1/Task 2 criterion names; legacy
  CEFR results retain their 0–100 scale. Missing scores remain unscored.
- Uses already authorized data in the page; no new provider call, credit
  charge, database write or server-side artifact. File names use restricted
  submission IDs, not user topics; AI text stays literal in a `.txt` file.
- This is a per-assessment download, not a complete account data export.
  Full privacy export/deletion remains release work.
- Validation: 17 JavaScript tests plus browser smoke, including download
  while the draft contains revised text and download from history.

## Writing assessment state transitions

- Separate sending confirmation, queue, processing, completed, failed and
  reconciliation states. Queue/processing show three actual workflow steps,
  not a fabricated percentage or completion time.
- Queue uses a slow breathing robot; processing uses a rotating outline.
  A 220 ms fade/slide runs only when submission identity/state changes, so
  typing and repeated polling do not restart it. Score-ring changes ease in.
- Normal processing no longer shows an administrator warning merely because
  billing reports reconciliation. Actual reconciliation/failure retains its
  recovery instructions. Live status text is updated only when it changes.
- Reduced-motion disables animated movement; loops pause when the tab is
  hidden. Existing polling, provider calls and billing semantics are unchanged.
- Validation: 19 JavaScript tests and mock browser smoke cover queued,
  processing, completion, mobile width, reduced-motion and existing recovery.

## Writing structure analysis

- Collapsed “Cấu trúc bài viết” in Overview shows component status, comment,
  exact essay evidence and a concrete next step; the text report includes it.
- Task 1 uses introduction/overview/key features/comparisons/organisation.
  Task 2 uses introduction/position/arguments/examples/conclusion/organisation.
  CEFR and other practice use main idea/details/linking/organisation, with
  level-aware guidance and no mandatory IELTS essay structure for A1/A2.
- Strict response schema restricts component codes per task. Server validation
  rejects duplicate/incompatible components, invalid status or oversized fields.
  Met/partial claims without exact supporting quotes become unavailable;
  their unsupported next steps are discarded. Missing components can have
  not_met status with no invented evidence. No score adjustment is introduced.
- Stored results without this field remain readable and show a no-data note.
  New requests capture the updated schema; existing in-flight snapshots keep
  their original prompt. No migration, paid replay or automatic recheck.
- Validation: package suite 151 tests / 974 assertions, 19 JavaScript tests,
  browser smoke for CEFR/IELTS labels, literal AI text and legacy fallback.

## Writing vocabulary review

- A collapsed “Từ vựng và cách diễn đạt” section in Overview groups existing
  vocabulary issues with literal original/replacement text and AI explanations.
  Unverified spans retain the manual-review note; no invented alternatives or
  extra paid request are introduced. Existing detail-tab correction controls
  remain the place to apply verified changes.
- Frequency hints count exact English word forms in the immutable assessed
  essay, case-insensitively. Show at most five words of four or more letters
  appearing at least three times, excluding a small common-function-word list.
  No stemming, phrase splitting, synonym generation or lexical-quality score.
- Explicitly explain that topic-keyword repetition is not automatically an
  error. Empty vocabulary feedback does not claim error-free language.
- The text report includes the same frequency hints; draft edits do not change
  them and historical results use their own original essay. No backend/schema
  change, migration or worker restart is needed for this display.
- Validation: 22 JavaScript tests and mock browser smoke for vocabulary pairs,
  repeated-word counts, literal AI text, unverified spans and mobile width.

## Writing issue observations across revisions

- Existing comparison reads the previous completed lower revision with the
  same owner/draft/rubric version. It now includes three groups: exact
  category/quote reported again, previous feedback not reported in this call,
  and feedback newly reported in this call. No provider request or credit use.
- Each source quote must be a literal substring in its own submitted essay;
  empty/unmatched quotes are excluded with a count. Duplicate category/quote
  pairs are grouped, without fuzzy matching or positional assumptions.
- Not-reported feedback records whether its original quote remains in the
  current essay. Neither AI omission nor disappearance of a phrase proves a
  correction; the UI/report explicitly avoid claiming the error was fixed.
- Collapsed section inside the version comparison shows up to five entries
  per group and full group counts. History selection and reports use the
  selected immutable submissions. Older results can be compared on read;
  no resubmission, result rewrite or migration is required.
- Validation: package suite 153 tests / 985 assertions, 23 JavaScript tests,
  browser smoke for grouped observations, literal AI text and existing flows.

## Writing analysis panel continuity

- Preserve explicitly open/closed analysis sections during typing, autosave,
  polling and result refresh, including criterion reasons, requirements,
  structure, vocabulary, paragraphs, issue changes and assessed-original text.
- Preferences are keyed by submission ID; a new assessment starts collapsed,
  while returning to a previously viewed assessment restores its own choices.
  Keep at most 30 assessments in page memory; do not persist content or UI
  preferences to browser storage. Reload resets these ephemeral preferences.
- When a focused summary is rebuilt during refresh, restore keyboard focus
  without scrolling. Editor focus remains untouched during composition.
- Validation: 25 JavaScript tests and mock browser smoke check typing, refresh,
  explicitly closed panels and keyboard focus alongside existing flows.

## CEFR Writing target alignment

- A collapsed “So với mục tiêu CEFR A1/A2/B1/B2” section in Overview compares
  this submitted essay with the selected target. Three application feedback
  aspects: communication, development/linking and level-appropriate language.
  These are not official CEFR scoring scales or a proficiency certification.
- Practice expectation summaries are adapted from the Writing section of the
  [official Europass CEFR self-assessment grid](https://europass.europa.eu/en/common-european-framework-reference-language-skills).
  They are supplied to the model and persisted with descriptor version
  `cefr-writing-practice-v1`; target comes from the trusted submission profile.
- Only CEFR calls request this field. IELTS/TOEIC retain their existing output
  schema and scoring. No 0–100-to-CEFR conversion or score adjustment.
- Server validation restricts three unique aspects, field sizes and statuses.
  Positive, partial and negative observations need exact essay evidence;
  unsupported observations and absent aspects become unavailable, with no
  unsupported next step. A1 is not penalised merely for lacking connectors or
  extended detail; guidance must fit the actual task/genre and selected level.
- Existing snapshots missing this field remain readable with a no-data note.
  The report includes the new qualitative analysis. Panel expansion persists
  through typing/refresh as for other analysis sections. No migration, separate
  inference, automatic paid recheck or rewrite of historical results.
- Validation: 155 package tests / 1031 assertions, 26 JavaScript tests and
  mock browser smoke for CEFR, IELTS exclusion, literal quotes/text and legacy
  no-data display. Real provider calls were not performed for this change.

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
Full Writing data export/delete and legacy cutover remain release follow-ups. Follow
[PHASE4-PLAN.md](PHASE4-PLAN.md) for subsequent Speaking and release work.
