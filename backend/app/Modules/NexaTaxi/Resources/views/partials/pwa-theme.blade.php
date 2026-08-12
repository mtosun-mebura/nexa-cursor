{{-- Gedeelde PWA light/dark theme (taxi/chauffeur + taxi/contract). --}}
@php
    $section = $section ?? 'all';
@endphp

@if(in_array($section, ['boot', 'all'], true))
<script>
(function () {
    try {
        var stored = localStorage.getItem('nexa-taxi-pwa-theme');
        var theme = (stored === 'light' || stored === 'dark') ? stored : 'dark';
        document.documentElement.setAttribute('data-theme', theme);
        document.documentElement.style.colorScheme = theme;
    } catch (e) {
        document.documentElement.setAttribute('data-theme', 'dark');
        document.documentElement.style.colorScheme = 'dark';
    }
})();
</script>
@endif

@if(in_array($section, ['styles', 'all'], true))
<style>
    html[data-theme="dark"] {
        --nexa-pwa-bg: #0f172a;
        --nexa-pwa-card: #1e293b;
        --nexa-pwa-text: #f8fafc;
        --nexa-pwa-muted: #94a3b8;
        --nexa-pwa-border: #334155;
        --nexa-pwa-input-bg: #0f172a;
        --nexa-pwa-input-border: #334155;
        --nexa-pwa-overlay: rgba(2, 6, 23, 0.65);
        color-scheme: dark;
    }
    html[data-theme="light"] {
        --nexa-pwa-bg: #f1f5f9;
        --nexa-pwa-card: #ffffff;
        --nexa-pwa-text: #0f172a;
        --nexa-pwa-muted: #64748b;
        --nexa-pwa-border: #cbd5e1;
        --nexa-pwa-input-bg: #ffffff;
        --nexa-pwa-input-border: #cbd5e1;
        --nexa-pwa-overlay: rgba(15, 23, 42, 0.45);
        color-scheme: light;
    }

    /* Map app tokens als die bestaan */
    html[data-theme="dark"],
    html[data-theme="light"] {
        --bg: var(--nexa-pwa-bg);
        --card: var(--nexa-pwa-card);
        --text: var(--nexa-pwa-text);
        --muted: var(--nexa-pwa-muted);
    }

    .nexa-pwa-chrome-actions {
        position: relative;
        z-index: 20;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.35rem;
        flex-shrink: 0;
    }
    /* Chauffeur-app: theme blijft rechtsboven gefixed */
    body > .nexa-pwa-chrome-actions {
        position: fixed;
        top: calc(1rem + env(safe-area-inset-top, 0px));
        right: calc(1rem + env(safe-area-inset-right, 0px));
        z-index: 200;
        padding: 0;
    }
    .nexa-pwa-theme-toggle {
        width: 2.25rem;
        height: 2.25rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 0;
        border-radius: 0.625rem;
        background: transparent;
        color: var(--nexa-pwa-muted);
        box-shadow: none;
        cursor: pointer;
        padding: 0;
        flex-shrink: 0;
        -webkit-tap-highlight-color: transparent;
    }
    .nexa-pwa-theme-toggle:hover,
    .nexa-pwa-theme-toggle:focus-visible {
        color: var(--nexa-pwa-text);
    }
    .nexa-pwa-theme-toggle:focus-visible {
        outline: 2px solid #2563eb;
        outline-offset: 2px;
    }
    .nexa-pwa-theme-toggle svg {
        width: 1.2rem;
        height: 1.2rem;
        display: block;
    }
    .nexa-pwa-chrome-actions .btn {
        width: auto;
        margin-top: 0;
        min-height: 2.25rem;
    }
    html[data-theme="dark"] .nexa-pwa-theme-icon-moon { display: none; }
    html[data-theme="light"] .nexa-pwa-theme-icon-sun { display: none; }

    /* Date icon: custom SVG (filter is onbetrouwbaar t.o.v. OS light mode) */
    input[type="date"]::-webkit-calendar-picker-indicator {
        cursor: pointer;
        opacity: 1;
        width: 1.15rem;
        height: 1.15rem;
        padding: 0;
        margin: 0;
        color: transparent;
        background-color: transparent;
        background-repeat: no-repeat;
        background-position: center;
        background-size: 1.15rem 1.15rem;
        filter: none !important;
        -webkit-appearance: none;
        appearance: none;
    }
    html[data-theme="dark"] input[type="date"] {
        color-scheme: dark;
    }
    html[data-theme="dark"] input[type="date"]::-webkit-calendar-picker-indicator {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23ffffff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2'/%3E%3Cline x1='16' y1='2' x2='16' y2='6'/%3E%3Cline x1='8' y1='2' x2='8' y2='6'/%3E%3Cline x1='3' y1='10' x2='21' y2='10'/%3E%3C/svg%3E");
    }
    html[data-theme="light"] input[type="date"] {
        color-scheme: light;
    }
    html[data-theme="light"] input[type="date"]::-webkit-calendar-picker-indicator {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2'/%3E%3Cline x1='16' y1='2' x2='16' y2='6'/%3E%3Cline x1='8' y1='2' x2='8' y2='6'/%3E%3Cline x1='3' y1='10' x2='21' y2='10'/%3E%3C/svg%3E");
    }

    html[data-theme="light"] .btn-ghost {
        border-color: var(--nexa-pwa-border);
        color: var(--nexa-pwa-muted);
    }
    html[data-theme="light"] .error {
        color: #b91c1c;
    }
    html[data-theme="light"] .dialog {
        background: var(--nexa-pwa-overlay);
    }
</style>
@endif

@if(in_array($section, ['widget', 'all'], true))
@php
    $chromeWithLogout = $chromeWithLogout ?? false;
@endphp
<div class="nexa-pwa-chrome-actions" id="nexa-pwa-chrome-actions">
    <button
        type="button"
        id="nexa-pwa-theme-toggle"
        class="nexa-pwa-theme-toggle"
        aria-label="Thema wisselen"
        title="Thema wisselen"
    >
        <svg class="nexa-pwa-theme-icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="4"></circle>
            <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"></path>
        </svg>
        <svg class="nexa-pwa-theme-icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21 14.5A8.5 8.5 0 1 1 9.5 3 7 7 0 0 0 21 14.5z"></path>
        </svg>
    </button>
    @if($chromeWithLogout)
        <button type="button" class="btn btn-ghost btn-sm" id="btn-logout" hidden>Uitloggen</button>
    @endif
</div>
<script>
(function () {
    var KEY = 'nexa-taxi-pwa-theme';
    var btn = document.getElementById('nexa-pwa-theme-toggle');
    if (!btn) return;

    function currentTheme() {
        var t = document.documentElement.getAttribute('data-theme');
        return t === 'light' ? 'light' : 'dark';
    }

    function applyTheme(theme) {
        theme = theme === 'light' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', theme);
        document.documentElement.style.colorScheme = theme;
        try { localStorage.setItem(KEY, theme); } catch (e) {}
        var meta = document.querySelector('meta[name="theme-color"]');
        if (meta) {
            meta.setAttribute('content', theme === 'light' ? '#f1f5f9' : (meta.getAttribute('data-nexa-dark-theme-color') || '#0f172a'));
        }
        btn.setAttribute('aria-label', theme === 'dark' ? 'Schakel naar licht thema' : 'Schakel naar donker thema');
    }

    var meta = document.querySelector('meta[name="theme-color"]');
    if (meta && !meta.getAttribute('data-nexa-dark-theme-color')) {
        meta.setAttribute('data-nexa-dark-theme-color', meta.getAttribute('content') || '#0f172a');
    }

    applyTheme(currentTheme());
    btn.addEventListener('click', function () {
        applyTheme(currentTheme() === 'dark' ? 'light' : 'dark');
    });
})();
</script>
@endif
