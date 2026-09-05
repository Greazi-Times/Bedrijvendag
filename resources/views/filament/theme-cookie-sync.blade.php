<script>
    (() => {
        const validThemes = ['light', 'dark', 'system'];
        const cookieTheme = @js(request()->cookie('appearance'));
        const storedTheme = localStorage.getItem('theme');
        const theme = validThemes.includes(cookieTheme)
            ? cookieTheme
            : (validThemes.includes(storedTheme) ? storedTheme : 'light');

        const persistTheme = (value) => {
            if (!validThemes.includes(value)) {
                return;
            }

            localStorage.setItem('theme', value);
            localStorage.setItem('appearance', value);

            const secure = window.location.protocol === 'https:' ? ';Secure' : '';
            document.cookie = `appearance=${encodeURIComponent(value)};path=/;max-age=31536000;SameSite=Lax${secure}`;
        };

        persistTheme(theme);
        window.addEventListener('theme-changed', (event) => persistTheme(event.detail));
    })();
</script>
