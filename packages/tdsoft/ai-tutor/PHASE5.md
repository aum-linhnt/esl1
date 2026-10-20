# Phase 5 — Dashboard v2 integration

Implemented in the host Laravel application; `/dashboard` remains unchanged.

## Learner skill history

- `/dashboard-v2` combines existing LMS skills with the latest AI skill snapshot by assessment time.
- `/dashboard-v2/skills` shows paginated history for the signed-in learner only.
- Completed AI Tutor Writing emits `WritingAssessmentCompleted`; the host listener records the stored, validated result.
- Replayed Writing events use a unique assessment identity and do not duplicate history.
- Completed audio assessments through `/ai/speaking/evaluate` record the provider's pronunciation practice score. Transcript-only responses, provider failures, missing/out-of-range scores and synthetic fallback results are excluded.
- Practice scores remain on 0–100; IELTS Writing stays on 0–9. IELTS bands are never compared with the 0–100 weak-skill threshold.
- AI snapshots are practice feedback, not certified proficiency or a CEFR placement assessment. Existing `learner_skills` records are preserved.

## Rule recommendations

Evaluated on each dashboard request, without another AI call or credit charge:

1. The same issue occurs in at least two assessments within the latest ten snapshots.
2. The lowest practice score is below 70/100.
3. No study activity has been recorded for more than three days, or no activity has been recorded yet.
4. An accessible, visible, unlocked lesson is unfinished.
5. A skill has no recorded assessment.

Up to three unique destination URLs are shown, linking to Writing, Speaking, the appropriate Listening/Reading practice filter or the unfinished lesson. The reason is shown on each recommendation. Rules use only the current learner's records.

## Learning goals

- `/dashboard-v2/goals` edits the learner's primary CEFR, TOEIC or IELTS goal and optional target date. The dashboard opens the same form in a keyboard-accessible native dialog, with a standalone page fallback.
- Save validates the framework/target pair and requires a deadline from today onward. An empty date clears the previous deadline. Ownership always comes from the session, never the submitted user ID.
- CEFR saves also update the existing profile `target_level`. IELTS/TOEIC goals do not fabricate a CEFR equivalent.
- Certificate cards show the saved choice. Recommendations reserve a place for the primary goal and avoid duplicate practice destinations.
- Writing links preselect a supported program/target only for new drafts; existing draft settings remain intact. IELTS targets map to the existing Foundation, 5.5, 6.5 or 7.0+ practice groups, with the group identified in the recommendation reason.
- CEFR C1/C2 links filter published courses by level because the current Writing module supports A1–B2. TOEIC links currently reinforce Listening through the existing practice hub.
- Goal completion percentages are not inferred from AI practice scores.

## Unified study time

- The host site's lesson pages, embedded/direct activities, Speaking page and Writing Studio load `resources/js/study-time.js` through their layouts. The host integration enables Writing's tracking marker with `ai-tutor.ui.study_tracking`; portable package previews do not start host telemetry.
- Active time is sampled while the page is visible and focused. Interaction resets a two-minute idle timeout; visible playing audio/video keeps sampling active. Suspended timer gaps contribute at most five seconds per sample.
- A cumulative heartbeat is sent every 15 seconds and on page hide. Server-owned sessions validate the learner and lesson/activity access on every report; Writing requires the Writing module entitlement. Trial lesson previews are not tracked.
- Server time bounds each report to elapsed time since the preceding report, capped at 60 seconds. Repeated/out-of-order totals add nothing; sessions expire after 24 hours. Telemetry never changes XP, credit or assessment scores.
- `/dashboard-v2` merges new intervals with legacy activity log ranges. All overlapping intervals count once; embedded activity has attribution priority over its lesson container, followed by Writing, Speaking and legacy/lesson intervals.
- `/dashboard-v2/study-progress` shows exact minutes/seconds by day and source. Calendar boundaries use Asia/Ho_Chi_Minh even if the configured storage timezone differs. The dashboard's chart rounds daily totals to whole minutes.
- Recent recorded study time also suppresses the inactivity recommendation. The completion streak remains governed by existing completion/reward behavior.
- Historical Speaking/Writing durations cannot be inferred from assessment scores. New time is recorded from installation onward. Legacy activity durations retain their original accuracy; heartbeat timing is an estimate and may miss a final unsent interval or discard long offline gaps.

## Credit and AI usage

- Dashboard v2 shows the learner's existing available credit balance and links to `/dashboard-v2/credits`. Reads never provision accounts, grant credits or call an AI provider. A missing account and a suspended account have explicit states.
- Credit detail is limited to the signed-in learner, with paginated transactions. Balance already excludes held reservations; holds are not deducted twice. Day/week/month quota remaining subtracts committed usage in the current window and all outstanding reservations, including older holds. Quota windows match the existing credit ledger's configured application timezone.
- `/admin/ai/usage` requires an active administrator through the existing `CreditAdministrator` integration. It provides 7/30/90-day reports in Vietnam calendar time, request statuses, token/audio totals, usage by feature/billing/provider/model and paginated recent usage.
- Costs use recorded estimates from usage rows, without joining duplicate cost snapshots. Unknown prices remain unknown; each currency has a separate total and count of priced/unpriced requests. These estimates are not provider invoices.
- Credit units computed for customer-key usage are labeled as rule units, not debited credit. Legacy AI endpoints outside the package are explicitly excluded from this report.
- Authenticated report responses use `Cache-Control: no-store`. No prompts, provider secrets, encrypted results or learner identities are exposed in the aggregated administrator report.
- Uses existing AI Tutor foundation tables; no extra migration is needed for this feature.

## Teacher management dashboard

- `/teacher/ai-tutor` follows the supplied teacher dashboard layout: fixed sidebar, profile bar, course/period filters, KPI cards, daily question/cost chart, common questions, failed requests, learner support table and lesson policy access.
- Access requires an active account and course ownership as a teacher, or a valid teacher/manager course enrollment. Active administrators can select any course. Students and assistants without management rights cannot access it. All detail and CSV requests apply the same course scope.
- Question counts use persisted conversation message creation times; feedback usefulness uses actual helpful/unhelpful votes for messages in the selected period. No feedback is shown as unknown. Previous periods are compared only when a denominator exists. Popular questions group exact question text, without an additional AI call.
- The cost chart includes tutor requests and their knowledge-retrieval embedding requests once, using recorded usage estimates. Currency selection changes the line and axis; estimates are never converted into another currency. Unknown prices have explicit counts. Calendar boundaries use Vietnam time.
- The support table uses persisted course grades below max(75%, course passing threshold). It displays the course grade and course progress, rather than inventing weak topics or interpreting chat volume as a score. Learners without a grade remain unclassified. Grades reflect their current stored state, not a new assessment in the selected period.
- The failed-request card refers to recorded processing failures, not a semantic judgment about whether an otherwise completed response answered the question. The error detail panel groups recorded codes, filters by code and course lesson, and preserves filters in pagination. Each failed turn has a plain-language title, suggested checks, its related lesson and authorized links to lesson policy, credit or knowledge management. Provider failures link active administrators to usage reports rather than the legacy Gemini settings page. Unknown errors use a generic guide; deleted/unmatched lessons have no lesson action. Guidance is an inference from the recorded code, not a confirmed root cause or a resolved status. Views do not retry or charge a learner.
- Existing policy controls operate per lesson. The configuration card now selects a lesson and edits its answer policy, teacher-controlled solution permission and exam mode directly. Saving validates the existing policy contract and course-management permission, preserves the period/lesson selection and returns named validation errors to the same panel. The old standalone policy form remains available. Native switch controls and a live preview explain which answer rule takes precedence; preview changes do not persist until Save. Credit quotas remain account-wide through the existing credit administrator; no misleading course-specific switches or quota writes were added.
- CSV export provides one row per day with separate columns per recorded currency. The dashboard does not persist demo statistics, call AI, change assessments or alter billing.

## Course learner support detail

- Selecting a learner from the support card or learner list opens course-scoped detail on the teacher dashboard. The same course-management authorization applies. A learner without a student enrollment in that course has no support profile.
- The profile shows persisted course grade, completed lessons, pending assignments and tutor question counts in the selected period. Learning outcomes reflect current stored course data, independently of the chat-period filter.
- Lesson activities show the most recently completed quiz score (normalized by its positive maximum) and the latest assignment submission's grade only when marked graded. Pending/returned/ungraded submissions have no inferred grade. In-progress, future, missing and invalid quiz scores are excluded.
- A genuine zero remains a score; absent scores remain unknown. Video/content completion scores and legacy lesson progress scores are not interpreted as assessments.
- Review suggestions use assessed activity scores below max(75%, activity passing threshold or course threshold). They link to the related activity. Lesson/activity titles are the observed learning context; no AI-generated weak-topic classification is invented.
- Reads are batched by the selected course and learner. Quiz answers, assignment content, files, written feedback and unrelated learner/global skill snapshots are not exposed.

## Local management demo

Run `php artisan db:seed --class="Database\Seeders\AiTutorDemo\AiTutorManagementDemoSeeder"` in local/testing to create the private `[DEMO] Kỹ năng quản lý thời gian` course. The seeder is deliberately not called by `DatabaseSeeder` and refuses other environments.

It provides eight labeled demo learners, five lessons, quiz/assignment results, pending grading, 60 days of deterministic conversation fixtures, feedback, 12 failures in the current 30-day period and simulated USD/VND estimates with some missing prices. UUIDs and natural identities stay stable on rerun; relative dates refresh without duplicating rows. Existing users' profiles/passwords and real courses are not overwritten. Active teachers receive management enrollment only in the demo course; administrators can already select it.

The teacher dashboard marks the course as simulated. Provider/model values are `demo` / `demo-tutor-v1`; seeded usage is customer-key mode with no credit ledger writes, provider calls or AI charges. Demo account passwords are generated randomly and never reset or printed; use your existing teacher/admin login to inspect the course. Demo costs also appear under provider `demo` in the administrator usage report and must not be interpreted as incurred costs.

IELTS demo: run `php artisan db:seed --class="Database\Seeders\AiTutorDemo\AiTutorIeltsDemoSeeder"`. This adds a separate private `[DEMO] IELTS 6.5 — Luyện 4 kỹ năng` course with Listening, Reading, Writing Task 1, Writing Task 2 and Speaking lessons, related practice questions and assignments. Users, deterministic UUIDs and request identities are separate from the time-management demo. The same repeatability/environment protections apply. The dashboard explicitly labels the stored percentage grades as practice scores, not IELTS bands; no band conversion or AI assessment is fabricated.

Customer showcase: `AiTutorIeltsShowcaseDemoSeeder` upgrades only the existing IELTS 6.5 demo to five study units, each with a text material, three-question quiz with explanations and a detailed assignment. It opens the selected course while keeping self-enrollment disabled, preserves edited/test submissions, and leaves other courses unchanged. `php artisan demo:ielts-student <email>` prepares a local student and enrollment without resetting an existing password or progress. Demo script and limitations: `docs/demo-ielts-65.md`.

IELTS level demos: run `php artisan db:seed --class="Database\Seeders\AiTutorDemo\AiTutorIeltsLevelsDemoSeeder"` to create Foundation, 4.5, 5.5, 7.0 and 7.5+ in order, preserving the existing 6.5 demo. Individual seeders are `AiTutorIeltsFoundationDemoSeeder`, `AiTutorIeltsBand45DemoSeeder`, `AiTutorIeltsBand55DemoSeeder`, `AiTutorIeltsBand70DemoSeeder` and `AiTutorIeltsBand75DemoSeeder`. Each private course has eight separate learners and five lessons covering Listening, Reading, Writing Task 1, Writing Task 2 and Speaking, with level-specific practice content and mock tutor analytics. Band names are course targets; stored percentage grades are not IELTS bands, and internal CEFR labels are not a conversion. Listening uses labeled transcripts and Speaking uses text outlines, without audio. Stable identities make repeat seeding safe without duplicating records or spending credit.

TOEIC two-skill demo: run `php artisan db:seed --class="Database\Seeders\AiTutorDemo\AiTutorToeicDemoSeeder"`. Adds a separate private `[DEMO] TOEIC 750+ — Listening & Reading` course with four lessons covering workplace conversations, announcements, grammar/vocabulary and email reading. Listening exercises use clearly labeled transcripts; no audio asset or official TOEIC score is fabricated. Demo learners, UUIDs and request identities are separate from the other demo courses. Practice percentages remain percentages, with an explicit notice that they are not official TOEIC scores.

TOEIC four-skill demo: run `php artisan db:seed --class="Database\Seeders\AiTutorDemo\AiTutorToeicFourSkillsDemoSeeder"`. Adds a separate private `[DEMO] TOEIC — Luyện 4 kỹ năng` course with Listening, Reading, Speaking and Writing lessons, eight learners and simulated tutor analytics. Listening uses labeled transcripts and Speaking uses text outlines, without recorded audio. Practice grades are simulated percentages, not official TOEIC scores or real AI assessments. User and request identities are separate from the two-skill course; repeat seeding does not duplicate records or spend credit.

TOEIC level demos: run `php artisan db:seed --class="Database\Seeders\AiTutorDemo\AiTutorToeicLevelsDemoSeeder"` to seed Starter (L&R 350+), Foundation (500+), Intermediate (650+), Advanced (800+) and Intensive (900+) in that order. Each also has an individual `AiTutorToeic<Name>DemoSeeder`. Every course has four distinct skill lessons, eight separate demo learners, level-specific quizzes/assignments and tutor analytics. Each four-skill title now shows three separate targets: L&R 350/500/650/800/900+, Speaking 80/110/140/160/180+, and Writing 80/110/140/160/180+. These are internal course goals, not score conversions between skills. Run `php artisan db:seed --class="Database\Seeders\AiTutorDemo\AiTutorToeicTargetsDemoSeeder"` to update only existing demo titles, preserving grades, enrollments and conversations. The normal level seeders also synchronize these titles after checking the demo owner and marker. CEFR labels are internal course organization, not an official TOEIC conversion. All courses stay private, carry the demo/percentage notice, use mock transcripts/text outlines, and preserve existing demo courses. Repeating the seed refreshes demo dates without duplicating records or spending credit.

TOEIC Speaking & Writing demos: run `php artisan db:seed --class="Database\Seeders\AiTutorDemo\AiTutorToeicSwLevelsDemoSeeder"` for Starter (Speaking 80+ / Writing 80+), Foundation (110+ / 110+), Intermediate (140+ / 140+), Advanced (160+ / 160+) and Intensive (180+ / 180+). Individual seeders follow `AiTutorToeicSw<Name>DemoSeeder`. Each private course has eight separate learners, two Speaking lessons and two Writing lessons, distinct quizzes/assignments and simulated tutor analytics. Course targets are proposed internal goals on each skill's separate 0–200 scale, not official level classifications or a conversion from L&R/CEFR. Dashboard practice percentages are not official scores. Speaking assignments use text outlines without audio or pronunciation assessment. Existing courses and credit ledgers remain unchanged; stable identities support repeat seeding.

## Installation

Run the host migration:

```sh
php artisan migrate --path=database/migrations/2026_10_06_000001_create_tutor_ai_learner_skill_snapshots.php
php artisan ai-tutor:backfill-skills
php artisan migrate --path=database/migrations/2026_10_06_000002_create_learner_learning_goals.php
php artisan migrate --path=database/migrations/2026_10_06_000003_create_learner_study_sessions.php
```

Backfill imports only completed, supported Writing results and can be repeated. It does not regrade, call a provider, change billing or overwrite existing snapshots. Historical Speaking assessments were not persisted by the previous endpoint and cannot be reconstructed.

## Validation

`DashboardV2Test`, `LearnerOverviewTest`, `LearningGoalTest`, `StudyTimeTest`, `AiUsageOverviewTest`, `TeacherTutorDashboardTest`, `TeacherLearnerSupportTest` and `SpeechAiAssessmentTest` cover dashboard isolation, replay/backfill, band scales, unavailable/failed results, audio-only persistence, repeated-error rules, practice links and ownership of history.

## Remaining Phase 5 work

A broader persisted recommendation lifecycle remains separate work. The current rules are computed on demand.

CEFR course demos: run `php artisan db:seed --class="Database\Seeders\AiTutorDemo\AiTutorCefrLevelsDemoSeeder"` for six private English courses A1, A2, B1, B2, C1 and C2 in order. Individual seeders follow `AiTutorCefr<Level>DemoSeeder`. Each course has eight separate learners, four skill lessons, level-specific quizzes and assignments, and simulated tutor analytics. The course level is a learning target; practice percentages do not certify actual CEFR proficiency. Listening uses labeled transcripts and Speaking uses text outlines without audio. Existing IELTS/TOEIC courses are preserved. Stable identities allow repeat seeding without duplicates or credit transactions.

IELTS showcase media: `AiTutorIeltsMediaDemoSeeder` adds two synthetic-voice audio samples, five original slide videos with English WebVTT captions and five PDF worksheets to the IELTS 6.5 course. Assets live in `public/demo/ielts-65`; reproduce with `scripts/build-ielts-demo-media.py`. Checksums are verified before seeding. Native video playback handles MP4/WebM/OGV URLs while existing embed URLs retain iframe playback. Transcript reveal is optional for audio activities. These are original shortened demo assets, not official IELTS materials or student recordings. Complete the new media activities before proceeding to the next lesson.
