

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// Remove private Writing drafts when logging out from any LMS page.
document.querySelectorAll('form[action*="logout"]').forEach(form => form.addEventListener('submit', () => {
    try {
        for (const key of Object.keys(sessionStorage)) {
            if (key.startsWith('tai-writing:')) sessionStorage.removeItem(key);
        }
    } catch {}
}));
