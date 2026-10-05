<script>
    // Apply before styles load to avoid a light flash on the default dark theme.
    (() => {
        let theme = 'dark';
        try {
            const saved = localStorage.getItem('esl-learning-theme');
            if (saved === 'light' || saved === 'dark') theme = saved;
        } catch {}
        try {
            const parentTheme = window.parent !== window ? window.parent.document.documentElement.dataset.learningTheme : null;
            if (parentTheme === 'light' || parentTheme === 'dark') theme = parentTheme;
        } catch {}
        document.documentElement.dataset.learningTheme = theme;
        document.documentElement.classList.toggle('dark', theme === 'dark');
    })();
</script>
