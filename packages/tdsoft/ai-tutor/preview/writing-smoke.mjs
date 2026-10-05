// Browser test uses the isolated mock server only. No host auth, DB or provider.
import assert from 'node:assert/strict';
const { chromium } = await import(process.env.AI_PREVIEW_PLAYWRIGHT_MODULE ?? 'playwright');
const origin = 'http://127.0.0.1:' + (process.argv[2] ?? '9013');
const browser = await chromium.launch({ executablePath: process.env.AI_PREVIEW_CHROME, headless: true });
const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
const page = await context.newPage(); const errors = []; const posts = [];
page.on('pageerror', error => errors.push(error.message));
page.on('dialog', dialog => dialog.accept());
page.on('request', req => { if (req.method() === 'POST' && /\/(submit|retry)$/.test(req.url())) posts.push(req.postDataJSON()); });
const editor = page.locator('[data-writing-editor]');
async function waitText(selector, text) {
    for (let attempt = 0; attempt < 100; attempt++) {
        if ((await page.locator(selector).textContent())?.includes(text)) return;
        await new Promise(resolve => setTimeout(resolve, 100));
    }
    assert.fail(`Timed out waiting for ${selector}: ${text}`);
}
const saved = () => waitText('[data-writing-save]', 'Đã lưu');
try {
    await page.goto(origin + '/writing');
    await page.locator('[name=topic]').fill('Describe a hobby. <script>literal topic</script>');
    await page.locator('[data-writing-create] button[type=submit]').click();
    await editor.waitFor({ state: 'visible' });
    await editor.fill('😀 I likes reading books.'); await saved();
    const theme = await page.locator('body').getAttribute('data-ai-tutor-theme');
    await page.locator('[data-tai-theme]').click();
    assert.notEqual(await page.locator('body').getAttribute('data-ai-tutor-theme'), theme);
    assert.equal(await editor.inputValue(), '😀 I likes reading books.');
    // Abort the response after the mock server accepted a request; recovery must GET, never create a new POST.
    await page.route('**/drafts/*/submit', async route => { await route.fetch(); await route.abort('failed'); });
    await page.locator('[data-writing-submit]').click();
    await page.locator('[data-writing-resume]').waitFor({ state: 'visible' });
    assert.equal(posts.length, 1);
    await page.unroute('**/drafts/*/submit');
    await page.locator('[data-writing-refresh]').click();
    await waitText('[data-writing-feedback]', 'Điểm luyện tập: 70');
    assert.equal(posts.length, 1); assert.equal(await page.locator('[data-writing-feedback] script').count(), 0);
    assert.equal(await page.locator('[data-writing-original] mark').textContent(), 'likes');
    await page.locator('[data-writing-issues] button').click(); await saved();
    assert.equal(await editor.inputValue(), '😀 I like reading books.');
    await page.reload(); await editor.waitFor({ state: 'visible' });
    assert.equal(await editor.inputValue(), '😀 I like reading books.');
    // Independent API client simulates a second tab's save.
    const id = new URL(page.url()).pathname.split('/').at(-1);
    const url = origin + '/ai-tutor/api/v1/writing/drafts/' + id;
    const draft = await (await context.request.get(url)).json();
    await context.request.patch(url, { data: { revision: draft.revision, content: 'Remote tab writes another essay.' } });
    await editor.fill('My current local essay is preserved.');
    await page.locator('[data-writing-conflict]').waitFor({ state: 'visible' });
    assert.equal(await editor.inputValue(), 'My current local essay is preserved.');
    await page.locator('[data-writing-keep]').click(); await saved();
    assert.equal(await editor.inputValue(), 'My current local essay is preserved.');
    await editor.fill('This mock essay includes /error for a safe failure.'); await saved();
    await page.locator('[data-writing-submit]').click();
    await page.locator('[data-writing-retry]').waitFor({ state: 'visible' });
    await page.locator('[data-writing-retry]').click();
    await waitText('[data-writing-feedback]', 'Mock retry completed');
    assert.equal(posts.length, 3); assert.equal(posts[2].confirm_retry, true);
    assert.notEqual(posts[1].request_id, posts[2].request_id);
    await page.setViewportSize({ width: 390, height: 844 });
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true);
    assert.deepEqual(errors, []);
    await page.screenshot({ path: process.env.AI_PREVIEW_SCREENSHOT ?? '/tmp/esl1-writing-mobile.png', fullPage: true });
    console.log('Writing browser smoke passed: autosave, theme, lost-submit recovery, Unicode fixes, reload, two-tab conflict, confirmed retry, mobile and text-only feedback.');
} finally { await browser.close(); }
