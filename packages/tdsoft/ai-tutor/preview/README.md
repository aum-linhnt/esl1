# Isolated UI preview (mock only)

Separate loopback-only Node server. Renders existing Blade templates without loading
bootstrap/app.php, .env, database, authentication, license client or package service providers.
No preview routes or license bypass are installed in the actual website.

Requires Node 20+, PHP 8.3+ (php8.3 by default), existing Composer dependencies and built Vite assets.
Select another PHP binary with AI_PREVIEW_PHP. No OpenAI key or license required.
Use AI_PREVIEW_BUILD_DIR for a verified temporary Vite build instead of public/build.

From the repository root:

~~~bash
node packages/tdsoft/ai-tutor/preview/server.mjs
~~~

Open http://127.0.0.1:9013 (not the LMS port). Optional port: append 9014.
Stop with Ctrl+C. In this workspace Node is also available at
/home/tuandung88/.nvm/versions/node/v20.9.0/bin/node.

Pages:
- / — sample lesson and widget.
- /tutor — standalone chat.
- /knowledge — documents/versions using the package standalone layout, not the live admin shell.
- /writing — Writing Studio with mock draft/autosave/assessment/history API.
  Use “I likes reading books.” to see a mock correction, or include /error for
  a settled failure and confirmed retry. Reload /ai-tutor/writing/{draft} to
  inspect recovery. No Writing request reaches a real database/provider.

Try sending a question, closing/opening/expanding during SSE, changing theme, viewing a source,
exporting/deleting the mock conversation. Send /error to see a simulated provider error.
In Knowledge, list lesson-1 to find a sample document; create a version, process (instant mock),
publish and withdraw. Use browser developer tools for mobile dimensions.

State exists only in server memory; restarting clears it and creates a new browser state namespace.
Temporary compiled Blade views are written under a dedicated OS temp directory.
Do not enter secrets/real learner data, expose publicly or place behind a reverse proxy.
No actual auth, billing or license validation runs here: this is a visual sandbox, not a security test.
Upload is absent. Non-allowlisted paths (including .env) return 404. Cross-origin requests and
unexpected Host headers are rejected. There is no upstream OpenAI/network request.

Run smoke checks against the running preview (mutates mock memory only):

~~~bash
node packages/tdsoft/ai-tutor/preview/smoke.mjs
~~~

Writing browser smoke (requires an available Playwright module and Chrome):

~~~bash
AI_PREVIEW_PLAYWRIGHT_MODULE=/absolute/path/to/playwright/index.mjs AI_PREVIEW_CHROME=/absolute/path/to/chrome node packages/tdsoft/ai-tutor/preview/writing-smoke.mjs
~~~

Append the preview port if using a custom port. The smoke mutates mock memory
only and writes a screenshot to `/tmp/esl1-writing-mobile.png` by default.
It tests autosave, Unicode corrections, lost-submit response recovery, reload,
two-tab conflict, confirmed retry, theme state, text-only feedback and mobile layout.

Rebuild Vite assets and restart preview after frontend changes. Website .env/database/config
are untouched. Backend regression tests remain the separate SQLite :memory: suite.
