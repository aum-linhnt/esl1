import { StudyClock } from './study-clock.js';

const root = document.querySelector('[data-study-source]');
if (root) {
    const api = root.dataset.studyApi;
    const source = root.dataset.studySource;
    const context = root.dataset.studyContext;
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    let session = null, clock = null, sent = 0, pending = false, stopped = false;
    const sample = () => clock?.step(performance.now(), !document.hidden, document.hasFocus(),
        [...document.querySelectorAll('video,audio')].some(media => !media.paused && !media.ended)) ?? 0;
    const request = (url, method, data, keepalive = false) => fetch(url, {
        method, credentials: 'same-origin', keepalive,
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
        body: JSON.stringify(data),
    });
    const flush = async (keepalive = false) => {
        if (!session || pending || stopped) return;
        const seconds = sample();
        if (seconds <= sent) return;
        pending = true;
        try {
            const response = await request(`${api}/${session}`, 'PATCH', { active_seconds: seconds }, keepalive);
            if (response.ok) sent = seconds;
            else if ([401,403,404,410,419,503].includes(response.status)) stopped = true;
        } catch { /* Retry the same cumulative seconds on the next heartbeat. */ }
        finally { pending = false; }
    };
    request(api, 'POST', { source, ...(context ? { context_id: Number(context) } : {}) }).then(async response => {
        if (!response.ok) return;
        session = (await response.json()).id;
        clock = new StudyClock(performance.now());
    }).catch(() => {});
    for (const event of ['pointerdown', 'keydown', 'scroll', 'input']) {
        document.addEventListener(event, () => clock?.interact(performance.now()), { passive: true, capture: true });
    }
    window.addEventListener('focus', () => clock?.interact(performance.now()));
    document.addEventListener('visibilitychange', () => { if (document.hidden) flush(true); });
    window.addEventListener('pagehide', () => flush(true));
    window.addEventListener('online', () => flush());
    setInterval(sample, 1000);
    setInterval(() => flush(), 15000);
}
