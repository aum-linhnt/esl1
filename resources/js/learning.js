const frame = document.querySelector('[data-activity-frame]');
if (frame) {
    window.addEventListener('message', event => {
        if (event.origin !== window.location.origin || event.source !== frame.contentWindow
            || event.data?.type !== 'lesson-activity-completed'
            || String(event.data.activityId) !== frame.dataset.activityId) return;
        const label = document.querySelector('[data-lesson-progress]');
        if (!label) return;
        const completed = new Set(JSON.parse(label.dataset.completed).map(String));
        completed.add(String(event.data.activityId));
        label.dataset.completed = JSON.stringify([...completed]);
        const total = Number(label.dataset.total);
        // Count only activities currently visible in this lesson.
        const visibleIds = [...document.querySelectorAll('[data-completion-icon]')].map(el => el.dataset.completionIcon);
        const count = visibleIds.filter(id => completed.has(id)).length;
        label.textContent = `${count}/${total} hoạt động hoàn thành trong bài`;
        document.querySelector('.learning-progress progress').value = count;
        document.querySelector('[data-lesson-percent]').textContent = `${total ? Math.round(count / total * 100) : 0}%`;
        const completionIcon = document.querySelector(`[data-completion-icon="${frame.dataset.activityId}"]`);
        const checkTemplate = document.querySelector('[data-completion-check]');
        if (completionIcon && checkTemplate) {
            completionIcon.replaceChildren(checkTemplate.content.cloneNode(true));
        }
    });
}

// Arrow-key navigation follows the visible tab order.
document.querySelectorAll('[role="tablist"]').forEach(list => {
    const tabs = [...list.querySelectorAll('[role="tab"]')];
    list.addEventListener('keydown', event => {
        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
        const current = tabs.indexOf(document.activeElement);
        if (current < 0) return;
        event.preventDefault();
        const next = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1
            : (current + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
        tabs[next].click();
        tabs[next].focus();
    });
});
const workspace = document.querySelector('.learning-workspace');
const tutor = document.querySelector('.learning-tutor');
if (workspace && tutor) {
    const background = [...document.querySelectorAll('.learning-header, .learning-sidebar, .learning-main, .learning-tutor-toggle')];
    const mobile = window.matchMedia('(max-width: 767px)');
    let wasOpen = false;
    const syncDialog = () => {
        const open = mobile.matches && workspace.classList.contains('tutor-is-open');
        background.forEach(element => { element.inert = open; });
        if (open) {
            tutor.setAttribute('role', 'dialog');
            tutor.setAttribute('aria-modal', 'true');
            if (!wasOpen) tutor.querySelector('.learning-tutor-close')?.focus();
        } else {
            tutor.removeAttribute('role');
            tutor.removeAttribute('aria-modal');
            if (wasOpen && mobile.matches) document.querySelector('.learning-tutor-toggle')?.focus();
        }
        wasOpen = open;
    };
    new MutationObserver(syncDialog).observe(workspace, { attributes: true, attributeFilter: ['class'] });
    mobile.addEventListener('change', syncDialog);
    tutor.addEventListener('keydown', event => {
        if (!wasOpen || event.key !== 'Tab') return;
        const items = [...tutor.querySelectorAll('button, a, select, textarea, summary')]
            .filter(element => !element.disabled && element.getClientRects().length);
        if (!items.length) return;
        if (event.shiftKey && document.activeElement === items[0]) {
            event.preventDefault(); items.at(-1).focus();
        } else if (!event.shiftKey && document.activeElement === items.at(-1)) {
            event.preventDefault(); items[0].focus();
        }
    });
}

// Keep the workspace, fullscreen tutor, and same-origin activity frame in sync.
function applyLearningTheme(theme) {
    const dark = theme !== 'light';
    document.documentElement.dataset.learningTheme = dark ? 'dark' : 'light';
    document.documentElement.classList.toggle('dark', dark);
    document.querySelectorAll('[data-learning-theme-toggle]').forEach(button => {
        const label = dark ? 'Chuyển sang giao diện sáng' : 'Chuyển sang giao diện tối';
        button.setAttribute('aria-label', label);
        button.title = label;
    });
    try {
        const child = frame?.contentDocument?.documentElement;
        if (child) {
            child.dataset.learningTheme = dark ? 'dark' : 'light';
            child.classList.toggle('dark', dark);
        }
    } catch { /* External redirects must retain their own theme. */ }
}
applyLearningTheme(document.documentElement.dataset.learningTheme);
document.querySelectorAll('[data-learning-theme-toggle]').forEach(button => {
    button.addEventListener('click', () => {
        const theme = document.documentElement.dataset.learningTheme === 'dark' ? 'light' : 'dark';
        applyLearningTheme(theme);
        try { localStorage.setItem('esl-learning-theme', theme); } catch {}
    });
});
frame?.addEventListener('load', () => applyLearningTheme(document.documentElement.dataset.learningTheme));
window.addEventListener('storage', event => {
    if (event.key === 'esl-learning-theme' || event.key === null) {
        applyLearningTheme(event.newValue === 'light' ? 'light' : 'dark');
    }
});

// Initials remain underneath the photo, including when a stored image fails to load.
document.addEventListener('error', event => {
    if (event.target.matches?.('[data-learner-avatar]')) event.target.hidden = true;
}, true);
document.querySelectorAll('[data-learner-avatar]').forEach(image => {
    if (image.complete && image.naturalWidth === 0) image.hidden = true;
});
