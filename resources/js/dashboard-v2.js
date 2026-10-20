const forms = document.querySelectorAll('[data-learning-goal-form]');
for (const form of forms) {
    const framework = form.querySelector('[data-goal-framework]');
    const target = form.querySelector('[data-goal-target]');
    const targets = JSON.parse(form.dataset.targets);
    const saved = { [framework.value]: target.value };
    let previous = framework.value;
    const defaults = { cefr: 'B1', toeic: '750', ielts: '6.5' };
    const refresh = () => {
        saved[previous] = target.value;
        const selected = saved[framework.value] ?? defaults[framework.value];
        target.replaceChildren(...Object.entries(targets[framework.value]).map(([value, label]) => new Option(label, value, false, value === selected)));
        previous = framework.value;
    };
    framework.addEventListener('change', refresh);
    const dialog = form.closest('dialog');
    if (!dialog || typeof dialog.showModal !== 'function') continue;
    let opener = null;
    const close = () => { dialog.close(); opener?.focus(); };
    document.querySelectorAll('[data-goal-open]').forEach(link => link.addEventListener('click', event => {
        event.preventDefault(); opener = link;
        const chosen = link.dataset.goalOpen;
        if (targets[chosen]) { framework.value = chosen; refresh(); }
        dialog.showModal();
    }));
    dialog.querySelectorAll('[data-goal-close]').forEach(button => button.addEventListener('click', event => { event.preventDefault(); close(); }));
    dialog.addEventListener('click', event => { if (event.target === dialog) { const box = dialog.getBoundingClientRect(); if (event.clientX < box.left || event.clientX > box.right || event.clientY < box.top || event.clientY > box.bottom) close(); } });
    if (dialog.dataset.hasErrors === 'true') dialog.showModal();
}

const currencySelector = document.querySelector('[data-tutor-currency]');
currencySelector?.addEventListener('change', () => {
    document.querySelectorAll('[data-tutor-chart] svg').forEach(chart => { chart.toggleAttribute('hidden', chart.dataset.currency !== currencySelector.value); });
});
document.querySelectorAll('[data-tutor-policy-form]').forEach(form => {
    const policy = form.querySelector('[name="ai_answer_policy"]');
    const solution = form.querySelector('[data-policy-solution]');
    const exam = form.querySelector('[data-policy-exam]');
    const status = form.querySelector('[data-policy-preview]');
    const refresh = () => {
        const labels = {
            no_answer: 'Không cung cấp đáp án hoặc lời giải.',
            hints_only: 'Chỉ gợi ý ở cấp 1–3.',
            hints_first: 'Gợi ý trước; lời giải được phép ở cấp 4.',
            full_solution: 'Cho phép cung cấp lời giải ngay.',
            teacher_controlled: solution.checked ? 'Gợi ý trước; giảng viên cho phép lời giải ở cấp 4.' : 'Chỉ gợi ý ở cấp 1–3; giảng viên chưa cho phép lời giải.',
        };
        status.textContent = exam.checked ? 'Chế độ kiểm tra: không cung cấp đáp án trong mọi chế độ chat.' : labels[policy.value];
        form.querySelector('[data-solution-note]').hidden = policy.value === 'teacher_controlled';
    };
    form.addEventListener('change', refresh);
    refresh();
});
