<!DOCTYPE html>
<html lang="nl" class="h-full" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('taxi::partials.pwa-theme', ['section' => 'boot'])
    <link rel="manifest" href="{{ route('taxi.contract.manifest') }}">
    <link rel="icon" href="{{ $faviconUrl }}" type="{{ $faviconType }}">
    <link rel="shortcut icon" href="{{ $faviconUrl }}" type="{{ $faviconType }}">
    <link rel="apple-touch-icon" href="{{ $faviconUrl }}">
    <title>Contract – Nexa Taxi</title>
    @include('taxi::partials.pwa-theme', ['section' => 'styles'])
    <style>
        :root {
            --bg: #0f172a;
            --card: #1e293b;
            --text: #f8fafc;
            --muted: #94a3b8;
            --blue: #2563eb;
            --green: #16a34a;
            --amber: #d97706;
            --red: #dc2626;
            --safe-top: env(safe-area-inset-top, 0px);
            --safe-bottom: env(safe-area-inset-bottom, 0px);
        }
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        [hidden] { display: none !important; }
        html, body { height: 100%; margin: 0; }
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg);
            color: var(--text);
            overscroll-behavior: none;
        }
        #app { min-height: 100%; display: flex; flex-direction: column; }
        .app-chrome {
            flex-shrink: 0;
            padding: calc(0.75rem + var(--safe-top)) 1rem 0.35rem;
        }
        .app-chrome .banner-install {
            margin-bottom: 0.75rem;
        }
        .app-chrome .nexa-pwa-chrome-actions {
            min-height: 2.25rem;
        }
        .screen {
            display: none;
            flex: 1;
            flex-direction: column;
            padding: 0.75rem 1rem calc(1rem + var(--safe-bottom));
            min-height: 0;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
        .screen.is-active { display: flex; }
        h1 { font-size: 1.35rem; margin: 0 0 1rem; font-weight: 700; }
        .card {
            background: var(--card);
            border-radius: 0.875rem;
            padding: 1rem;
            margin-bottom: 0.75rem;
        }
        label { display: block; font-size: 0.8125rem; color: var(--muted); margin: 0.75rem 0 0.35rem; }
        label:first-child { margin-top: 0; }
        input[type="email"], input[type="password"], input[type="date"], input[type="text"], textarea {
            width: 100%;
            border: 1px solid var(--nexa-pwa-input-border, #334155);
            background: var(--nexa-pwa-input-bg, #0f172a);
            color: var(--text);
            border-radius: 0.5rem;
            padding: 0.7rem 0.75rem;
            font-size: 1rem;
        }
        textarea { resize: vertical; min-height: 4rem; }
        .error { color: #fca5a5; font-size: 0.875rem; margin: 0.75rem 0 0; }
        .muted { color: var(--muted); font-size: 0.875rem; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            border: 0;
            border-radius: 0.625rem;
            padding: 0.75rem 1rem;
            font-size: 0.9375rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 0.75rem;
        }
        .btn:disabled { opacity: 0.55; cursor: not-allowed; }
        .btn-primary { background: var(--blue); color: #fff; }
        .btn-danger { background: var(--red); color: #fff; }
        .btn-ghost {
            background: transparent;
            color: var(--muted);
            border: 1px solid var(--nexa-pwa-border, #334155);
        }
        .btn-sm { width: auto; margin-top: 0; padding: 0.45rem 0.75rem; font-size: 0.8125rem; }
        .toolbar {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        .toolbar h1 { margin: 0; }
        .tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        .tab {
            flex: 1;
            border: 1px solid var(--nexa-pwa-border, #334155);
            background: transparent;
            color: var(--muted);
            border-radius: 0.625rem;
            padding: 0.55rem;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
        }
        .tab.is-active { background: #1e3a8a; border-color: #1d4ed8; color: #fff; }
        .passenger-name { font-weight: 650; font-size: 1rem; margin: 0 0 0.25rem; }
        .status-pill {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 0.2rem 0.55rem;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 0;
        }
        .status-planned, .status-en_route { background: rgba(37, 99, 235, 0.2); color: #93c5fd; }
        .status-arrived { background: rgba(217, 119, 6, 0.2); color: #fcd34d; }
        .status-picked_up { background: rgba(37, 99, 235, 0.22); color: #93c5fd; }
        .status-completed { background: rgba(22, 163, 74, 0.2); color: #86efac; }
        .status-absent { background: rgba(220, 38, 38, 0.2); color: #fca5a5; }
        .status-none { background: rgba(148, 163, 184, 0.15); color: #cbd5e1; }
        .status-pills { display: flex; flex-wrap: wrap; gap: 0.35rem; margin-top: 0.45rem; }
        #home-destination { font-weight: 600; color: var(--text); }
        html[data-theme="light"] .status-planned,
        html[data-theme="light"] .status-en_route,
        html[data-theme="light"] .status-picked_up { background: rgba(37, 99, 235, 0.12); color: #1d4ed8; }
        html[data-theme="light"] .status-arrived { background: rgba(217, 119, 6, 0.14); color: #b45309; }
        html[data-theme="light"] .status-completed { background: rgba(22, 163, 74, 0.14); color: #15803d; }
        html[data-theme="light"] .status-absent { background: rgba(220, 38, 38, 0.12); color: #b91c1c; }
        html[data-theme="light"] .status-none { background: rgba(100, 116, 139, 0.12); color: #475569; }
        .card-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.75rem; }
        .banner-install {
            background: rgba(37, 99, 235, 0.18);
            border: 1px solid rgba(37, 99, 235, 0.4);
            color: #dbeafe;
            border-radius: 0.75rem;
            padding: 0.75rem 2.25rem 0.75rem 1rem;
            font-size: 0.8125rem;
            margin-bottom: 0;
            position: relative;
            line-height: 1.45;
        }
        html[data-theme="light"] .banner-install {
            background: #eff6ff;
            border-color: #93c5fd;
            color: #1e3a8a;
        }
        .banner-dismiss {
            position: absolute;
            top: 50%;
            right: 0.45rem;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: inherit;
            font-size: 1.25rem;
            line-height: 1;
            cursor: pointer;
            opacity: 0.8;
        }
        .banner-dismiss:hover { opacity: 1; }
        .banner-announcements { display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 0.75rem; }
        .banner-announcement {
            border-radius: 0.75rem;
            padding: 0.75rem 2.25rem 0.75rem 1rem;
            font-size: 0.8125rem;
            position: relative;
            line-height: 1.45;
        }
        .banner-announcement .announcement-title { font-weight: 700; margin: 0 0 0.2rem; }
        .banner-announcement .announcement-body { margin: 0; opacity: 0.95; }
        .banner-announcement.severity-info {
            background: rgba(37, 99, 235, 0.18);
            border: 1px solid rgba(37, 99, 235, 0.4);
            color: #dbeafe;
        }
        .banner-announcement.severity-warning {
            background: rgba(217, 119, 6, 0.2);
            border: 1px solid rgba(217, 119, 6, 0.45);
            color: #fde68a;
        }
        .banner-announcement.severity-critical {
            background: rgba(220, 38, 38, 0.22);
            border: 1px solid rgba(220, 38, 38, 0.5);
            color: #fecaca;
        }
        html[data-theme="light"] .banner-announcement.severity-info {
            background: #eff6ff; border-color: #93c5fd; color: #1e3a8a;
        }
        html[data-theme="light"] .banner-announcement.severity-warning {
            background: #fffbeb; border-color: #fcd34d; color: #92400e;
        }
        html[data-theme="light"] .banner-announcement.severity-critical {
            background: #fef2f2; border-color: #fca5a5; color: #991b1b;
        }
        .leg-block {
            margin-top: 0.65rem;
            padding-top: 0.55rem;
            border-top: 1px solid rgba(148, 163, 184, 0.25);
        }
        .leg-block:first-of-type { margin-top: 0.45rem; }
        .leg-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: var(--muted);
            margin: 0 0 0.25rem;
        }
        .week-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }
        .week-nav .week-label { font-weight: 650; font-size: 0.9375rem; text-align: center; flex: 1; min-width: 0; }
        .week-nav #week-today { flex-shrink: 0; }
        .week-days { display: flex; gap: 0.35rem; overflow-x: auto; margin-bottom: 0.75rem; padding-bottom: 0.15rem; }
        .week-day {
            flex: 1 0 2.75rem;
            min-width: 2.75rem;
            border: 1px solid var(--nexa-pwa-border, #334155);
            background: transparent;
            color: var(--muted);
            border-radius: 0.625rem;
            padding: 0.4rem 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
        }
        .week-day .wd-name { display: block; opacity: 0.8; }
        .week-day .wd-num { display: block; font-size: 0.95rem; color: var(--text); margin-top: 0.1rem; }
        .week-day.is-active { background: #1e3a8a; border-color: #1d4ed8; color: #fff; }
        .week-day.is-active .wd-num { color: #fff; }
        .week-day.is-today:not(.is-active) { border-color: #2563eb; }
        .banner-install-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.65rem;
        }
        .banner-install-actions:empty,
        .banner-install-actions:not(:has(:not([hidden]))) {
            display: none;
            margin-top: 0;
        }
        .banner-install-actions .btn { margin-top: 0; width: auto; }
        .btn-link {
            display: inline;
            background: transparent;
            border: 0;
            color: inherit;
            font: inherit;
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 2px;
            padding: 0;
            margin: 0 0 0 0.35rem;
            cursor: pointer;
            width: auto;
        }
        .btn-link:hover { opacity: 0.85; }
        .guide-steps {
            margin: 0.75rem 0 0;
            padding-left: 1.2rem;
            color: var(--text);
            font-size: 0.9375rem;
            line-height: 1.5;
        }
        .guide-steps li + li { margin-top: 0.55rem; }
        .guide-platform {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 0.2rem 0.55rem;
            font-size: 0.75rem;
            font-weight: 600;
            background: rgba(37, 99, 235, 0.18);
            color: #93c5fd;
            margin: 0 0 0.35rem;
        }
        html[data-theme="light"] .guide-platform {
            background: rgba(37, 99, 235, 0.12);
            color: #1d4ed8;
        }
        .empty {
            text-align: center;
            color: var(--muted);
            padding: 2rem 1rem;
        }
        .dialog {
            position: fixed;
            inset: 0;
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: max(1rem, var(--safe-top)) 1rem max(1rem, var(--safe-bottom));
            background: var(--nexa-pwa-overlay, rgba(2, 6, 23, 0.65));
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
        }
        .dialog-panel {
            width: 100%;
            max-width: 28rem;
            max-height: min(90dvh, 36rem);
            overflow-y: auto;
            background: var(--card);
            border-radius: 1rem;
            padding: 1rem;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.35);
        }
        .dialog-actions { display: flex; gap: 0.5rem; margin-top: 0.5rem; }
        .dialog-actions .btn { flex: 1; }
    </style>
</head>
<body>
<div id="app">
    <header class="app-chrome">
        <div id="install-hint" class="banner-install" hidden role="note">
            <button type="button" class="banner-dismiss" id="btn-dismiss-install" aria-label="Sluiten">×</button>
            Installeer deze app op je telefoon voor snelle toegang tot status en afmelden.
            <button type="button" class="btn-link" id="btn-install-guide">Handleiding</button>
            <div class="banner-install-actions">
                <button type="button" class="btn btn-primary btn-sm" id="btn-install-app" hidden>Installeer app</button>
            </div>
        </div>
        <div id="announcement-banners" class="banner-announcements" hidden></div>
        @include('taxi::partials.pwa-theme', ['section' => 'widget', 'chromeWithLogout' => true])
    </header>

    <section id="screen-login" class="screen is-active" aria-label="Inloggen">
        <h1>Contractportaal</h1>
        <p class="muted" style="margin:0 0 1rem;">Volg ophaalstatus en meld leerlingen af bij afwezigheid.</p>
        <div class="card">
            <form id="login-form" autocomplete="on">
                <label for="email">E-mail</label>
                <input id="email" name="email" type="email" inputmode="email" autocomplete="username" required>
                <label for="password">Wachtwoord</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
                <p id="login-error" class="error" hidden></p>
                <button type="submit" class="btn btn-primary" id="login-btn">Inloggen</button>
            </form>
        </div>
    </section>

    <section id="screen-home" class="screen" aria-label="Overzicht">
        <div class="toolbar">
            <div>
                <h1 id="home-title">Vandaag</h1>
                <p class="muted" id="home-subtitle" style="margin:0.25rem 0 0;"></p>
                <p class="muted" id="home-destination" style="margin:0.35rem 0 0;" hidden></p>
            </div>
        </div>
        <div class="tabs" role="tablist">
            <button type="button" class="tab is-active" id="tab-today" data-tab="today">Vandaag</button>
            <button type="button" class="tab" id="tab-week" data-tab="week">Planning</button>
            <button type="button" class="tab" id="tab-absences" data-tab="absences">Afmeldingen</button>
        </div>
        <div id="panel-today"></div>
        <div id="panel-week" hidden></div>
        <div id="panel-absences" hidden></div>
        <p id="home-error" class="error" hidden></p>
    </section>
</div>

<div id="absence-dialog" class="dialog" hidden>
    <div class="dialog-panel" role="dialog" aria-modal="true" aria-labelledby="absence-dialog-title">
        <h2 id="absence-dialog-title" style="margin:0 0 0.5rem;font-size:1.1rem;">Afmelden</h2>
        <p class="muted" id="absence-dialog-name" style="margin:0 0 0.75rem;"></p>
        <label for="absence-date-from">Van</label>
        <input id="absence-date-from" type="date" required>
        <label for="absence-date-to">Tot</label>
        <input id="absence-date-to" type="date" required>
        <p class="muted" id="absence-days-hint" style="margin:0.35rem 0 0;"></p>
        <label for="absence-reason">Reden (optioneel)</label>
        <textarea id="absence-reason" maxlength="500" placeholder="Bijv. ziek, schoolreis…"></textarea>
        <p id="absence-error" class="error" hidden></p>
        <div class="dialog-actions">
            <button type="button" class="btn btn-ghost" id="absence-cancel">Annuleren</button>
            <button type="button" class="btn btn-danger" id="absence-confirm">Afmelden</button>
        </div>
    </div>
</div>

<div id="install-guide-dialog" class="dialog" hidden>
    <div class="dialog-panel" role="dialog" aria-modal="true" aria-labelledby="install-guide-title">
        <h2 id="install-guide-title" style="margin:0 0 0.5rem;font-size:1.1rem;">App installeren</h2>
        <span class="guide-platform" id="install-guide-platform"></span>
        <p class="muted" id="install-guide-intro" style="margin:0.35rem 0 0;"></p>
        <ol class="guide-steps" id="install-guide-steps"></ol>
        <div class="dialog-actions" style="margin-top:1rem;">
            <button type="button" class="btn btn-primary" id="install-guide-close">Sluiten</button>
        </div>
    </div>
</div>

<script>
window.NEXA_TAXI_CONTRACT = {
    apiBase: @json($apiBase),
    loginUrl: @json(url('/api/taxi/v1/contract/login')),
    appUrl: @json($appUrl ?? url('/taxi/contract')),
    pollMs: {{ (int) ($pollMs ?? 15000) }},
};
</script>
<script src="{{ asset('assets/js/taxi-contract-app.js') }}?v=8" defer></script>
@include('partials.password-toggle')
</body>
</html>
