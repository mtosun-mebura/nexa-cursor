<!DOCTYPE html>
<html lang="nl" class="h-full" data-theme="dark" data-accent="orange">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#f97316">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('taxi::partials.pwa-theme', ['section' => 'boot'])
    @include('taxi::partials.pwa-accent', ['section' => 'boot'])
    <link rel="manifest" href="{{ \Illuminate\Support\Facades\Route::has('taxi.contract.manifest') ? route('taxi.contract.manifest') : url('/taxi/contract/manifest.webmanifest') }}">
    <link rel="icon" href="{{ $faviconUrl }}" type="{{ $faviconType }}">
    <link rel="shortcut icon" href="{{ $faviconUrl }}" type="{{ $faviconType }}">
    <link rel="apple-touch-icon" href="{{ $faviconUrl }}">
    <title>Contract – Nexa Taxi</title>
    @include('taxi::partials.pwa-theme', ['section' => 'styles'])
    @include('taxi::partials.pwa-accent', ['section' => 'styles'])
    <style>
        :root {
            --bg: #121214;
            --card: #1c1c1e;
            --card-elevated: #252528;
            --chrome: #1c1c1e;
            --text: #ffffff;
            --muted: #9ca3af;
            --orange: #f97316;
            --orange-hover: #ea580c;
            --blue: #2563eb;
            --green: #22c55e;
            --amber: #d97706;
            --red: #ef4444;
            --line: rgba(255,255,255,0.08);
            --soft-text: #d1d5db;
            --safe-top: env(safe-area-inset-top, 0px);
            --safe-bottom: env(safe-area-inset-bottom, 0px);
            --bottom-nav-h: 4.75rem;
        }
        html[data-theme="dark"] {
            --nexa-pwa-bg: #121214;
            --nexa-pwa-card: #1c1c1e;
            --nexa-pwa-chrome: #1c1c1e;
            --bg: #121214;
            --card: #1c1c1e;
            --card-elevated: #252528;
            --chrome: #1c1c1e;
            --text: #ffffff;
            --muted: #9ca3af;
            --line: rgba(255,255,255,0.08);
            --soft-text: #d1d5db;
        }
        html[data-theme="light"] {
            --bg: #f1f5f9;
            --card: #ffffff;
            --card-elevated: #f8fafc;
            --chrome: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --line: rgba(15, 23, 42, 0.1);
            --soft-text: #475569;
            --nexa-pwa-bg: #f1f5f9;
            --nexa-pwa-card: #ffffff;
            --nexa-pwa-chrome: #ffffff;
            --nexa-pwa-text: #0f172a;
            --nexa-pwa-muted: #64748b;
            --nexa-pwa-border: #cbd5e1;
            --nexa-pwa-input-bg: #ffffff;
            --nexa-pwa-input-border: #cbd5e1;
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
        #app { height: 100%; min-height: 100%; display: flex; flex-direction: column; }
        .app-top-chrome {
            flex-shrink: 0;
            background: var(--chrome);
            padding-top: var(--safe-top);
            padding-left: env(safe-area-inset-left, 0px);
            padding-right: env(safe-area-inset-right, 0px);
        }
        @media (max-width: 48rem) {
            .app-top-chrome {
                padding-top: max(var(--safe-top), 3.75rem);
            }
        }
        .app-top-chrome:not(:has(#guide-hint:not([hidden]))) {
            display: none;
        }
        #app:has(#guide-hint:not([hidden])) #screen-login.screen {
            padding-top: 1rem;
        }
        #app:has(#guide-hint:not([hidden])) .home-top {
            padding-top: 0.65rem;
        }
        .screen {
            display: none;
            flex: 1;
            flex-direction: column;
            padding: calc(1rem + var(--safe-top)) 1rem 1rem;
            min-height: 0;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
        .screen.is-active { display: flex; }
        #screen-home.is-active {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
            overflow: hidden;
            padding: 0 0 calc(var(--bottom-nav-h) + var(--safe-bottom));
        }
        .home-top {
            flex: 0 0 auto;
            padding: calc(0.65rem + var(--safe-top)) 1rem 0.35rem;
            background: var(--chrome);
            border-bottom: 1px solid var(--line);
        }
        .home-top__row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            min-height: 2.25rem;
            height: auto;
            padding-right: 2.75rem;
        }
        .home-top__row h1 {
            margin: 0;
            font-size: 1.125rem;
            font-weight: 600;
            line-height: 1.25;
        }
        .home-banners {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        .home-banners:empty { display: none; }
        .home-scroll {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            padding: 0.75rem 1rem 0.35rem;
        }
        .contract-bottom-nav {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 40;
            display: none;
            align-items: stretch;
            justify-content: center;
            gap: 0.15rem;
            padding: 0.45rem 0.35rem calc(0.45rem + var(--safe-bottom));
            background: var(--chrome);
            border-top: 1px solid var(--line);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        #screen-home.is-active > .contract-bottom-nav {
            display: flex;
        }
        .contract-bottom-nav__btn {
            display: flex;
            flex: 1 1 0;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.2rem;
            min-width: 0;
            min-height: 3.6rem;
            border: none;
            background: transparent;
            color: var(--muted);
            font-size: 0.7rem;
            font-weight: 600;
            cursor: pointer;
            border-radius: 0.75rem;
            padding: 0.35rem 0.2rem;
        }
        .contract-bottom-nav__btn svg {
            width: 1.45rem;
            height: 1.45rem;
        }
        .contract-bottom-nav__btn.is-active {
            color: var(--orange);
        }
        .contract-bottom-nav__btn:active {
            background: rgba(var(--accent-rgb), 0.08);
        }
        @media (max-width: 420px) {
            .contract-bottom-nav__btn {
                font-size: 0.62rem;
                padding: 0.3rem 0.1rem;
            }
        }
        #screen-home.is-nav-tab .home-scroll {
            overflow: hidden;
            padding: 0;
            display: flex;
            flex-direction: column;
        }
        #tab-panel-navigation:not([hidden]) {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
            height: 100%;
        }
        .navigation-map {
            flex: 1 1 auto;
            min-height: 8rem;
            width: 100%;
            background: #1a1a1c;
        }
        .navigation-sheet {
            flex: 0 0 auto;
            padding: 0.75rem 1rem 0.85rem;
            background: var(--chrome);
            border-top: 1px solid var(--line);
            overflow: visible;
        }
        .navigation-status {
            margin: 0 0 0.55rem;
            font-size: 0.8125rem;
            color: var(--muted);
            line-height: 1.4;
        }
        .navigation-stops {
            margin: 0 0 0.7rem;
            padding: 0;
            list-style: none;
            max-height: min(40vh, 16rem);
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
        .navigation-stops li {
            display: flex;
            align-items: flex-start;
            gap: 0.45rem;
            padding: 0;
            font-size: 0.88rem;
            line-height: 1.35;
            color: var(--text);
            border-bottom: 1px solid var(--line);
        }
        .navigation-stops li:last-child {
            border-bottom: 0;
        }
        .navigation-stop-btn {
            display: flex;
            align-items: flex-start;
            gap: 0.45rem;
            width: 100%;
            padding: 0.55rem 0;
            border: none;
            background: transparent;
            color: inherit;
            text-align: left;
            cursor: pointer;
            font: inherit;
        }
        .navigation-stop-btn:active {
            opacity: 0.75;
        }
        .navigation-route__name {
            display: block;
            font-size: 0.95rem;
            font-weight: 700;
            line-height: 1.25;
        }
        .navigation-route__kind {
            display: block;
            color: var(--muted);
            font-size: 0.82rem;
            font-weight: 650;
            margin-top: 0.12rem;
        }
        strong.navigation-route__kind {
            color: var(--text);
            font-size: 0.92rem;
            font-weight: 700;
            margin-top: 0;
        }
        .navigation-route__stop {
            display: block;
            color: var(--muted);
            font-size: 0.8rem;
            font-weight: 500;
            margin-top: 0.12rem;
        }
        .navigation-stops__num {
            flex-shrink: 0;
            width: 1.25rem;
            height: 1.25rem;
            border-radius: 999px;
            background: rgba(var(--accent-rgb), 0.2);
            color: var(--orange);
            font-size: 0.68rem;
            font-weight: 750;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        #tab-panel-navigation #btn-start-navigation {
            width: 100%;
            margin: 0;
        }
        #tab-panel-navigation #btn-start-navigation:disabled {
            opacity: 0.45;
        }
        .driver-section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin: 0.15rem 0 0.85rem;
        }
        .driver-section-head h2 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 500;
            color: var(--text);
        }
        h1 { font-size: 1.25rem; margin: 0 0 1rem; font-weight: 600; }
        #screen-login h1 {
            min-height: 2.25rem;
            display: flex;
            align-items: center;
            padding-right: 2.75rem;
            box-sizing: border-box;
        }
        .card {
            background: var(--card);
            border-radius: 0.875rem;
            padding: 1rem;
            margin-bottom: 0.75rem;
            border: 1px solid var(--line);
        }
        label { display: block; font-size: 0.75rem; color: var(--muted); margin: 0.55rem 0 0.25rem; }
        label:first-child { margin-top: 0; }
        #login-form label {
            font-size: 0.875rem;
            margin: 0.65rem 0 0.3rem;
        }
        #login-form label:first-child {
            margin-top: 0;
        }
        input[type="email"], input[type="password"], input[type="date"], input[type="text"], textarea {
            width: 100%;
            border: 1px solid var(--line);
            background: var(--card-elevated, #252528);
            color: var(--text);
            border-radius: 0.55rem;
            padding: 0.55rem 0.7rem;
            font-size: 0.875rem;
            line-height: 1.35;
            min-height: 2.35rem;
        }
        #login-form input[type="email"],
        #login-form input[type="password"],
        #login-form input[type="text"] {
            background: #252528;
            border-color: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            -webkit-text-fill-color: #ffffff;
            caret-color: #ffffff;
            margin-bottom: 0.55rem;
            padding: 0.6rem 0.75rem;
            font-size: 0.9375rem;
            min-height: 2.5rem;
        }
        #login-form input[type="email"]::placeholder,
        #login-form input[type="password"]::placeholder,
        #login-form input[type="text"]::placeholder {
            color: #9ca3af;
            -webkit-text-fill-color: #9ca3af;
        }
        #login-form input[type="email"]:-webkit-autofill,
        #login-form input[type="email"]:-webkit-autofill:hover,
        #login-form input[type="email"]:-webkit-autofill:focus,
        #login-form input[type="password"]:-webkit-autofill,
        #login-form input[type="password"]:-webkit-autofill:hover,
        #login-form input[type="password"]:-webkit-autofill:focus,
        #login-form input[type="text"]:-webkit-autofill,
        #login-form input[type="text"]:-webkit-autofill:hover,
        #login-form input[type="text"]:-webkit-autofill:focus {
            -webkit-text-fill-color: #ffffff;
            caret-color: #ffffff;
            box-shadow: 0 0 0 1000px #252528 inset;
            transition: background-color 99999s ease-in-out 0s;
        }
        #login-form .js-pw-toggle-wrap {
            margin-bottom: 0.55rem;
        }
        #login-form .js-pw-toggle-wrap input {
            margin-bottom: 0;
        }
        #login-form .js-pw-toggle-btn {
            top: 50%;
            bottom: auto;
            transform: translateY(-50%);
            margin: 0;
            color: #9ca3af;
        }
        #login-form .login-first-login {
            margin: 0.85rem 0 0;
            text-align: center;
        }
        #login-form .login-first-login button {
            background: none;
            border: none;
            color: var(--orange);
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: underline;
            padding: 0;
        }
        #first-login-panel {
            margin-top: 0.85rem;
        }
        #first-login-panel.is-code-sent {
            margin-top: 0;
        }
        #first-login-panel .login-first-note {
            color: var(--muted);
            font-size: 0.8125rem;
            line-height: 1.45;
            margin: 0.75rem 0 0;
        }
        #first-login-panel.is-code-sent .login-first-note {
            color: var(--orange);
            margin: 0 0 1.5rem;
        }
        #first-login-panel .btn-ghost {
            margin-top: 0.5rem;
        }
        textarea {
            resize: vertical;
            min-height: 3rem;
            line-height: 1.4;
        }
        .dialog-panel label {
            margin-top: 0.45rem;
        }
        .dialog-panel input[type="date"],
        .dialog-panel input[type="text"],
        .dialog-panel input[type="email"],
        .dialog-panel textarea {
            padding: 0.5rem 0.65rem;
            font-size: 0.8125rem;
            min-height: 2.25rem;
            border-radius: 0.5rem;
        }
        .dialog-panel textarea {
            min-height: 2.75rem;
        }
        html[data-theme="light"] input[type="email"],
        html[data-theme="light"] input[type="password"],
        html[data-theme="light"] input[type="date"],
        html[data-theme="light"] input[type="text"],
        html[data-theme="light"] textarea {
            background: var(--nexa-pwa-input-bg, #ffffff);
            border-color: var(--nexa-pwa-input-border, #cbd5e1);
            color: var(--text);
        }
        html[data-theme="light"] #login-form input[type="email"],
        html[data-theme="light"] #login-form input[type="password"],
        html[data-theme="light"] #login-form input[type="text"] {
            background: #ffffff;
            border-color: #cbd5e1;
            color: #0f172a;
            -webkit-text-fill-color: #0f172a;
            caret-color: #0f172a;
        }
        html[data-theme="light"] #login-form input[type="email"]:-webkit-autofill,
        html[data-theme="light"] #login-form input[type="email"]:-webkit-autofill:hover,
        html[data-theme="light"] #login-form input[type="email"]:-webkit-autofill:focus,
        html[data-theme="light"] #login-form input[type="password"]:-webkit-autofill,
        html[data-theme="light"] #login-form input[type="password"]:-webkit-autofill:hover,
        html[data-theme="light"] #login-form input[type="password"]:-webkit-autofill:focus,
        html[data-theme="light"] #login-form input[type="text"]:-webkit-autofill,
        html[data-theme="light"] #login-form input[type="text"]:-webkit-autofill:hover,
        html[data-theme="light"] #login-form input[type="text"]:-webkit-autofill:focus {
            -webkit-text-fill-color: #0f172a;
            caret-color: #0f172a;
            box-shadow: 0 0 0 1000px #ffffff inset;
        }
        html[data-theme="light"] #login-form .js-pw-toggle-btn {
            color: #64748b;
        }
        .error { color: #fca5a5; font-size: 0.875rem; margin: 0.75rem 0 0; }
        #login-error {
            display: none;
            margin: 0.75rem 0 1rem;
            padding: 0.7rem 0.85rem;
            border-radius: 0.5rem;
            background: rgba(239, 68, 68, 0.16);
            border: 1px solid rgba(248, 113, 113, 0.45);
            color: #fecaca;
            font-size: 0.9rem;
            font-weight: 600;
            line-height: 1.35;
        }
        #login-error:not([hidden]) {
            display: block;
        }
        html[data-theme="light"] #login-error {
            background: #fef2f2;
            border-color: #fecaca;
            color: #b91c1c;
        }
        #login-form .field-error {
            display: none;
            margin: -0.3rem 0 0.55rem;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #fca5a5;
            line-height: 1.3;
        }
        #login-form .field-error:not([hidden]) {
            display: block;
        }
        #login-form input.is-invalid {
            border-color: #ef4444;
        }
        html[data-theme="light"] #login-form .field-error {
            color: #b91c1c;
        }
        html[data-theme="light"] #login-form input.is-invalid {
            border-color: #dc2626;
        }
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
        .btn-primary { background: var(--orange); color: var(--accent-on, #fff); }
        .btn-primary:hover { background: var(--orange-hover); color: var(--accent-on, #fff); }
        .btn-danger { background: var(--red); color: #fff; }
        .btn-ghost {
            background: transparent;
            color: var(--muted);
            border: 1px solid var(--nexa-pwa-border, var(--line));
        }
        .btn-sm { width: auto; margin-top: 0; padding: 0.45rem 0.75rem; font-size: 0.8125rem; }
        .passenger-name { font-weight: 650; font-size: 1rem; margin: 0; }
        .passenger-card .passenger-card-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            width: 100%;
            padding: 0;
            margin: 0;
            border: 0;
            background: transparent;
            color: inherit;
            text-align: left;
            cursor: pointer;
            touch-action: manipulation;
        }
        .passenger-card-toggle-text {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
            min-width: 0;
            flex: 1;
        }
        .passenger-card-summary {
            font-size: 0.78rem;
            color: var(--muted);
            line-height: 1.35;
        }
        .passenger-card-chevron {
            flex-shrink: 0;
            width: 2rem;
            height: 2rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--muted);
            font-size: 0.7rem;
            transition: transform 0.15s ease;
        }
        .passenger-card.is-expanded .passenger-card-chevron {
            transform: rotate(180deg);
        }
        .passenger-card-body {
            margin-top: 0.75rem;
        }
        .passenger-card .card-actions {
            margin-top: 0.75rem;
        }
        .passenger-card:not(.is-expanded) .card-actions {
            margin-top: 0.65rem;
        }
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
        .status-none, .status-expired { background: rgba(148, 163, 184, 0.15); color: #cbd5e1; }
        .status-pills { display: flex; flex-wrap: wrap; gap: 0.35rem; margin-top: 0.45rem; }
        #home-destination { font-weight: 600; color: var(--text); }
        html[data-theme="light"] .status-planned,
        html[data-theme="light"] .status-en_route,
        html[data-theme="light"] .status-picked_up { background: rgba(37, 99, 235, 0.12); color: #1d4ed8; }
        html[data-theme="light"] .status-arrived { background: rgba(217, 119, 6, 0.14); color: #b45309; }
        html[data-theme="light"] .status-completed { background: rgba(22, 163, 74, 0.14); color: #15803d; }
        html[data-theme="light"] .status-absent { background: rgba(220, 38, 38, 0.12); color: #b91c1c; }
        html[data-theme="light"] .status-none,
        html[data-theme="light"] .status-expired { background: rgba(100, 116, 139, 0.12); color: #475569; }
        .card-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.75rem; }
        .banner-guide-hint {
            position: relative;
            display: flex;
            align-items: center;
            background: rgba(var(--accent-rgb), 0.14);
            border: 1px solid rgba(var(--accent-rgb), 0.4);
            color: var(--accent-muted);
            border-radius: 0.75rem;
            padding: 0.65rem 2.75rem 0.65rem 1rem;
            font-size: 0.8125rem;
            line-height: 1.4;
        }
        html[data-theme="light"] .banner-guide-hint {
            background: var(--accent-light-bg);
            border-color: var(--accent-light-border);
            color: var(--accent-light-ink);
        }
        #guide-hint {
            margin: 0.65rem 1rem 0.85rem;
            flex-shrink: 0;
        }
        .banner-guide-hint__body {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.45rem 0.75rem;
            flex: 1;
            min-width: 0;
            padding-right: 0.85rem;
        }
        .banner-guide-hint__text {
            margin: 0;
            flex: 1 1 14rem;
        }
        .banner-guide-hint a.btn-inline {
            display: inline-flex;
            align-items: center;
            margin: 0;
            padding: 0.28rem 0.55rem;
            border-radius: 0.45rem;
            border: none;
            background: var(--orange);
            color: var(--accent-on, #fff);
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1.2;
            text-decoration: none;
            white-space: nowrap;
        }
        #guide-hint > .banner-dismiss-btn {
            top: 50%;
            right: 0.45rem;
            transform: translateY(-50%);
        }
        .profile-guide-link {
            display: flex;
            align-items: center;
            margin: 0.85rem 0 0.35rem;
            padding: 0.75rem 0.85rem;
            border-radius: 0.75rem;
            border: 1px solid var(--line);
            background: var(--card-elevated, #252528);
            color: var(--text);
            text-decoration: none;
        }
        .profile-guide-link strong {
            font-size: 0.95rem;
        }
        .banner-dismiss-btn,
        .banner-dismiss {
            position: absolute;
            top: 50%;
            right: 0.35rem;
            transform: translateY(-50%);
            width: 1.75rem;
            height: 1.75rem;
            padding: 0;
            border: none;
            border-radius: 0.4rem;
            background: rgba(0, 0, 0, 0.2);
            color: inherit;
            font-size: 1.125rem;
            line-height: 1;
            cursor: pointer;
            -webkit-appearance: none;
            touch-action: manipulation;
        }
        .banner-announcements { display: flex; flex-direction: column; gap: 0.5rem; }
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
            margin-top: 0.85rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--line);
        }
        .leg-block:first-of-type {
            margin-top: 0.55rem;
            padding-top: 0;
            border-top: 0;
        }
        .leg-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: var(--muted);
            margin: 0 0 0.55rem;
        }
        .offer-route {
            display: flex;
            flex-direction: column;
            gap: 0;
            position: relative;
            padding-left: 0;
            margin: 0 0 0.55rem;
        }
        .offer-route::before {
            content: "";
            position: absolute;
            left: 0.55rem;
            top: 1.15rem;
            bottom: 0;
            width: 0;
            border-left: 2px dashed rgba(156, 163, 175, 0.55);
        }
        html[data-theme="light"] .offer-route::before {
            border-left-color: rgba(100, 116, 139, 0.45);
        }
        .offer-route-stop {
            position: relative;
            padding-bottom: 0.85rem;
            padding-left: 0;
        }
        .offer-route-stop:last-child { padding-bottom: 0; }
        .offer-route-head {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            margin: 0 0 0.2rem;
        }
        .offer-route-dot {
            position: relative;
            width: 1.15rem;
            height: 1.15rem;
            border-radius: 999px;
            border: 2px solid var(--card);
            flex-shrink: 0;
            z-index: 1;
        }
        .offer-route-dot--pickup { background: var(--green); }
        .offer-route-dot--dropoff { background: var(--orange); }
        .offer-route-label {
            font-size: 0.8125rem;
            color: var(--soft-text);
            font-weight: 600;
            margin: 0;
            line-height: 1.3;
        }
        .offer-route-label-time {
            margin-left: auto;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--muted);
        }
        .offer-route-body {
            display: block;
            padding-left: 1.7rem;
            color: inherit;
            text-decoration: none;
        }
        .offer-route-main {
            display: block;
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text);
            line-height: 1.3;
        }
        .offer-route-sub {
            display: block;
            font-size: 0.78rem;
            color: var(--muted);
            margin-top: 0.1rem;
            line-height: 1.3;
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
            border: 1px solid var(--nexa-pwa-border, var(--line));
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
        .week-day.is-active { background: rgba(var(--accent-rgb), 0.2); border-color: var(--orange); color: var(--orange); }
        .week-day.is-active .wd-num { color: var(--text); }
        .planning-view-toggle {
            display: inline-flex;
            align-items: stretch;
            flex-shrink: 0;
            border: 1px solid var(--line);
            border-radius: 0.75rem;
            overflow: hidden;
            background: var(--card-elevated);
        }
        .planning-view-toggle__btn {
            border: none;
            background: transparent;
            color: var(--muted);
            font-size: 0.78rem;
            font-weight: 650;
            padding: 0.38rem 0.7rem;
            cursor: pointer;
            min-height: 2rem;
        }
        .planning-view-toggle__btn.is-active {
            background: rgba(var(--accent-rgb), 0.18);
            color: var(--orange);
        }
        .planning-week-nav {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            width: 100%;
            max-width: 100%;
            margin-bottom: 0.55rem;
            min-width: 0;
        }
        .planning-week-nav__spacer {
            flex: 1 1 auto;
            min-width: 0;
        }
        .planning-heading {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            gap: 0.25rem;
            width: 100%;
            margin: 0 0 0.75rem;
        }
        .planning-heading .planning-week-label {
            font-weight: 650;
            font-size: 0.9rem;
            text-align: center;
            color: var(--text);
            line-height: 1.3;
            max-width: 100%;
        }
        .planning-heading.is-day .planning-week-label {
            font-size: 1.15rem;
            font-weight: 700;
        }
        .planning-day-stack {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
        }
        .planning-day-stack .planning-heading {
            margin-bottom: 0.35rem;
        }
        .planning-day-stack .planning-empty,
        .planning-day-stack .planning-ride-card {
            width: 100%;
        }
        .planning-week-nav__btn {
            width: 2.5rem;
            height: 2.5rem;
            flex: 0 0 2.5rem;
            border-radius: 0.75rem;
            border: 1px solid var(--line);
            background: var(--card-elevated);
            color: var(--text);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            padding: 0;
            font-size: 1.05rem;
            line-height: 1;
        }
        .planning-week-nav__btn:active {
            opacity: 0.85;
        }
        .planning-week-nav__today {
            flex: 0 0 auto;
            height: 2.5rem;
            padding: 0 0.7rem;
            border-radius: 0.75rem;
            border: 1px solid var(--line);
            background: var(--card-elevated);
            color: var(--text);
            font-size: 0.8rem;
            font-weight: 650;
            cursor: pointer;
            line-height: 1;
            white-space: nowrap;
        }
        .planning-week-nav__today:active:not(:disabled) {
            opacity: 0.85;
        }
        .planning-week-nav__today:disabled {
            opacity: 0.35;
            cursor: default;
        }
        .planning-week-nav__count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.28rem;
            color: var(--orange);
            font-size: 0.875rem;
            font-weight: 650;
            line-height: 1.2;
        }
        .planning-week-nav__count svg {
            width: 1rem;
            height: 1rem;
            flex-shrink: 0;
        }
        .planning-week-days {
            display: flex;
            gap: 0.35rem;
            margin-bottom: 0.85rem;
        }
        .planning-week-day {
            flex: 1 1 0;
            min-width: 0;
            border: 1px solid var(--line);
            background: transparent;
            color: var(--muted);
            border-radius: 0.625rem;
            padding: 0.4rem 0.15rem 0.35rem;
            font-size: 0.7rem;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
        }
        .planning-week-day .wd-name {
            display: block;
            opacity: 0.8;
            text-transform: lowercase;
            font-size: 0.78rem;
        }
        .planning-week-day .wd-num {
            display: block;
            font-size: 1.05rem;
            color: var(--text);
            margin-top: 0.08rem;
        }
        .planning-week-day .wd-rides {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.15rem;
            margin-top: 0.18rem;
            color: var(--orange);
            font-size: 0.72rem;
            font-weight: 700;
            line-height: 1;
        }
        .planning-week-day .wd-rides svg {
            width: 0.85rem;
            height: 0.85rem;
        }
        .planning-week-day:not(.has-rides) .wd-rides {
            color: var(--muted);
            opacity: 0.55;
        }
        .planning-week-day.is-today {
            box-shadow: inset 0 0 0 1px rgba(var(--accent-rgb), 0.45);
        }
        .planning-week-day.is-active {
            background: rgba(var(--accent-rgb), 0.2);
            border-color: var(--orange);
            color: var(--orange);
        }
        .planning-week-day.is-active .wd-num {
            color: var(--text);
        }
        .planning-day-section {
            margin-bottom: 1rem;
        }
        .planning-day-section__title {
            margin: 0 0 0.5rem;
            font-size: 0.8125rem;
            font-weight: 650;
            color: var(--muted);
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 0.5rem;
        }
        .planning-day-section__count {
            font-weight: 650;
            font-size: 0.8rem;
            color: var(--orange);
        }
        .planning-ride-card {
            display: block;
            width: 100%;
            text-align: left;
            border: 1px solid rgba(var(--accent-rgb), 0.32);
            background: var(--card-elevated);
            box-shadow: inset 3px 0 0 var(--orange);
            color: inherit;
            border-radius: 0.85rem;
            padding: 0.75rem 0.85rem 0.75rem 1rem;
            margin: 0 0 0.5rem;
        }
        .planning-ride-card.is-assigned {
            border-color: rgba(34, 197, 94, 0.38);
            background: var(--card-elevated);
            box-shadow: inset 3px 0 0 #4ade80;
        }
        .planning-ride-card.is-completed {
            border-color: rgba(148, 163, 184, 0.4);
            background: var(--card-elevated);
            box-shadow: inset 3px 0 0 #94a3b8;
        }
        .planning-ride-card.is-absent {
            border-color: rgba(239, 68, 68, 0.38);
            background: var(--card-elevated);
            box-shadow: inset 3px 0 0 #f87171;
        }
        .planning-ride-card.is-expired {
            border-color: rgba(148, 163, 184, 0.4);
            background: var(--card-elevated);
            box-shadow: inset 3px 0 0 #94a3b8;
        }
        .planning-ride-card__hit {
            display: block;
            width: 100%;
            padding: 0;
            margin: 0;
            border: 0;
            background: transparent;
            color: inherit;
            text-align: left;
            cursor: pointer;
        }
        .planning-ride-card__hit:active {
            opacity: 0.88;
        }
        .planning-ride-card__top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            margin-bottom: 0.32rem;
        }
        .planning-ride-card__time {
            font-size: 1rem;
            font-weight: 700;
            color: var(--orange);
        }
        .planning-ride-card.is-assigned .planning-ride-card__time {
            color: #4ade80;
        }
        .planning-ride-card.is-completed .planning-ride-card__time {
            color: #94a3b8;
        }
        .planning-ride-card.is-absent .planning-ride-card__time {
            color: #f87171;
        }
        .planning-ride-card.is-expired .planning-ride-card__time {
            color: #94a3b8;
        }
        .planning-ride-card__status {
            display: inline-flex;
            align-items: center;
            padding: 0.18rem 0.5rem;
            border-radius: 999px;
            background: rgba(var(--accent-rgb), 0.2);
            color: var(--orange);
            font-size: 0.68rem;
            font-weight: 750;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            flex-shrink: 0;
        }
        .planning-ride-card.is-assigned .planning-ride-card__status {
            background: rgba(34, 197, 94, 0.2);
            color: #4ade80;
        }
        .planning-ride-card.is-completed .planning-ride-card__status {
            background: rgba(148, 163, 184, 0.2);
            color: #cbd5e1;
        }
        .planning-ride-card.is-absent .planning-ride-card__status {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
        }
        .planning-ride-card.is-expired .planning-ride-card__status {
            background: rgba(148, 163, 184, 0.2);
            color: #cbd5e1;
        }
        .planning-ride-card__route {
            margin: 0;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text);
            line-height: 1.35;
        }
        .planning-ride-card__arrow {
            color: var(--orange);
            font-weight: 800;
        }
        .planning-ride-card.is-assigned .planning-ride-card__arrow {
            color: #4ade80;
        }
        .planning-ride-card.is-completed .planning-ride-card__arrow {
            color: #94a3b8;
        }
        .planning-ride-card.is-absent .planning-ride-card__arrow {
            color: #f87171;
        }
        .planning-ride-card.is-expired .planning-ride-card__arrow {
            color: #94a3b8;
        }
        .planning-ride-card__meta {
            margin: 0.2rem 0 0;
            font-size: 0.78rem;
            color: var(--muted);
            line-height: 1.35;
        }
        .planning-ride-card__detail {
            margin-top: 0.65rem;
            padding-top: 0.65rem;
            border-top: 1px solid var(--line);
        }
        .planning-empty {
            text-align: center;
            color: var(--muted);
            padding: 1.1rem 0.75rem;
            margin: 0;
            font-size: 0.9rem;
        }
        html[data-theme="light"] .planning-ride-card.is-assigned .planning-ride-card__time,
        html[data-theme="light"] .planning-ride-card.is-assigned .planning-ride-card__status,
        html[data-theme="light"] .planning-ride-card.is-assigned .planning-ride-card__arrow {
            color: #15803d;
        }
        html[data-theme="light"] .planning-ride-card.is-completed .planning-ride-card__time,
        html[data-theme="light"] .planning-ride-card.is-completed .planning-ride-card__status,
        html[data-theme="light"] .planning-ride-card.is-completed .planning-ride-card__arrow {
            color: #475569;
        }
        html[data-theme="light"] .planning-ride-card.is-absent .planning-ride-card__time,
        html[data-theme="light"] .planning-ride-card.is-absent .planning-ride-card__status,
        html[data-theme="light"] .planning-ride-card.is-absent .planning-ride-card__arrow {
            color: #b91c1c;
        }
        html[data-theme="light"] .planning-ride-card.is-expired .planning-ride-card__time,
        html[data-theme="light"] .planning-ride-card.is-expired .planning-ride-card__status,
        html[data-theme="light"] .planning-ride-card.is-expired .planning-ride-card__arrow {
            color: #475569;
        }
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
            background: rgba(var(--accent-rgb), 0.18);
            color: var(--accent-muted);
            margin: 0 0 0.35rem;
        }
        html[data-theme="light"] .guide-platform {
            background: rgba(var(--accent-rgb), 0.12);
            color: var(--accent-ink);
        }
        .empty {
            text-align: center;
            color: var(--muted);
            padding: 2rem 1rem;
            line-height: 1.45;
        }
        .empty__sub {
            display: block;
            margin-top: 0.35rem;
            font-size: 0.8125rem;
            opacity: 0.85;
        }
        .profile-panel .profile-info { margin: 0 0 0.85rem; }
        .profile-panel .profile-info__name {
            margin: 0 0 0.85rem;
            font-size: 1.125rem;
            font-weight: 700;
            line-height: 1.3;
            color: var(--text);
            word-break: break-word;
        }
        .profile-panel .profile-info__list {
            margin: 0;
            display: grid;
            gap: 0.65rem;
        }
        .profile-panel .profile-info__row { display: grid; gap: 0.15rem; }
        .profile-panel .profile-info__label {
            margin: 0;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .profile-panel .profile-info__value {
            margin: 0;
            font-size: 0.9375rem;
            line-height: 1.4;
            color: var(--text);
            word-break: break-word;
        }
        .profile-panel .profile-info__value.is-empty {
            color: var(--muted);
            font-style: italic;
        }
        .profile-panel .profile-session-note { margin: 0 0 0.25rem; }
        .profile-panel .btn { margin-top: 0.75rem; }
        .home-meta {
            margin: 0 0 0.85rem;
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
            border: 1px solid var(--line);
        }
        .dialog-actions { display: flex; gap: 0.5rem; margin-top: 0.5rem; }
        .dialog-actions .btn { flex: 1; }
        .dialog.contract-notice .dialog-panel,
        .dialog.contract-confirm .dialog-panel {
            max-width: 22rem;
            text-align: center;
            padding: 1.5rem 1.25rem 1.25rem;
        }
        .contract-notice-icon {
            width: 3.25rem;
            height: 3.25rem;
            margin: 0 auto 1rem;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .contract-notice-icon.is-success {
            background: rgba(22, 163, 74, 0.2);
            border: 1px solid rgba(74, 222, 128, 0.4);
            color: #86efac;
        }
        .contract-notice-icon.is-error {
            background: rgba(220, 38, 38, 0.18);
            border: 1px solid rgba(248, 113, 113, 0.4);
            color: #fecaca;
        }
        .contract-notice-icon.is-warn {
            background: rgba(245, 158, 11, 0.18);
            border: 1px solid rgba(251, 191, 36, 0.4);
            color: #fcd34d;
        }
        .contract-notice-title {
            margin: 0 0 0.5rem;
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        .contract-notice-text {
            margin: 0 0 1.25rem;
            font-size: 0.9375rem;
            color: var(--muted);
            line-height: 1.45;
        }
        .dialog.contract-notice .dialog-actions,
        .dialog.contract-confirm .dialog-actions {
            flex-direction: column;
        }
        .dialog.contract-notice .dialog-actions .btn,
        .dialog.contract-confirm .dialog-actions .btn {
            min-height: 3rem;
        }
        html[data-theme="light"] .card {
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
        }
    </style>
</head>
<body>
@include('taxi::partials.pwa-theme', ['section' => 'widget'])
<div id="app">
    <div class="app-top-chrome">
        <div id="guide-hint" class="banner-guide-hint" hidden role="note">
            <button type="button" class="banner-dismiss-btn" id="btn-dismiss-guide-hint" aria-label="Melding sluiten">×</button>
            <div class="banner-guide-hint__body">
                <p class="banner-guide-hint__text">
                    <strong>Handleiding.</strong>
                    Nieuw of even niet zeker? Open de handleiding voor installeren, inloggen, ritten en afmelden.
                    Na wegklikken vind je die altijd terug onder <strong>Profiel</strong>.
                </p>
                <a class="btn-inline" id="btn-open-guide" href="{{ $guideUrl ?? url('/taxi/contract/handleiding') }}">Handleiding openen</a>
            </div>
        </div>
    </div>
    <section id="screen-login" class="screen is-active" aria-label="Inloggen">
        <h1>Contract inloggen</h1>
        <div class="card">
            <form id="login-form" autocomplete="on" novalidate>
                <div id="login-email-block">
                    <label for="email">E-mail</label>
                    <input id="email" name="email" type="email" inputmode="email" autocomplete="username" required aria-describedby="email-error">
                    <p id="email-error" class="field-error" hidden role="alert"></p>
                </div>
                <div id="login-password-block">
                    <label for="password">Wachtwoord</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required aria-describedby="password-error">
                    <p id="password-error" class="field-error" hidden role="alert"></p>
                </div>
                <p id="login-error" class="error" hidden role="alert" aria-live="assertive"></p>
                <button type="submit" class="btn btn-primary" id="login-btn">Inloggen</button>
                <p class="login-first-login" id="login-first-open-wrap">
                    <button type="button" id="btn-open-first-login">Eerste keer inloggen? Inlogcode aanvragen</button>
                </p>
                <div id="first-login-panel" hidden>
                    <input type="hidden" id="first-login-email" value="" autocomplete="off">
                    <button type="button" class="btn btn-primary" id="btn-send-login-code">Code versturen</button>
                    <p class="login-first-note"
                        data-note-idle="We sturen een eenmalige code naar je e-mail. Die is kort geldig. Daarna kies je zelf een wachtwoord."
                        data-note-sent="We hebben een eenmalige code naar je e-mailadres gestuurd. Deze is beperkt geldig, voer deze hieronder in en stel een eigen wachtwoord in."
                    >We sturen een eenmalige code naar je e-mail. Die is kort geldig. Daarna kies je zelf een wachtwoord.</p>
                    <div id="first-login-verify" hidden>
                        <label for="login-code">Code uit e-mail</label>
                        <input id="login-code" name="login_code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]*">
                        <label for="new-password">Nieuw wachtwoord</label>
                        <input id="new-password" name="new_password" type="password" autocomplete="new-password">
                        <label for="new-password-confirm">Wachtwoord bevestigen</label>
                        <input id="new-password-confirm" name="new_password_confirm" type="password" autocomplete="new-password">
                        <button type="button" class="btn btn-primary" id="btn-verify-login-code">Wachtwoord opslaan en inloggen</button>
                    </div>
                    <button type="button" class="btn btn-ghost" id="btn-cancel-first-login">Terug naar inloggen</button>
                </div>
            </form>
        </div>
    </section>

    <section id="screen-home" class="screen" aria-label="Overzicht">
        <div class="home-top">
            <div class="home-top__row">
                <h1 id="home-title">Vandaag</h1>
                <div id="planning-view-toggle" class="planning-view-toggle" role="tablist" aria-label="Planningweergave" hidden>
                    <button type="button" class="planning-view-toggle__btn is-active" data-planning-view="day" aria-selected="true">Dag</button>
                    <button type="button" class="planning-view-toggle__btn" data-planning-view="week" aria-selected="false">Week</button>
                </div>
            </div>
            <div class="home-banners">
                <div id="announcement-banners" class="banner-announcements" hidden></div>
            </div>
        </div>
        <div class="home-scroll">
            <div id="tab-panel-today" data-main-tab-panel="today">
                <p class="muted home-meta" id="home-subtitle"></p>
                <p class="muted home-meta" id="home-destination" hidden></p>
                <div id="panel-today"></div>
            </div>
            <div id="panel-week" data-main-tab-panel="week" hidden></div>
            <div id="tab-panel-navigation" class="navigation-panel" data-main-tab-panel="navigation" hidden>
                <div id="navigation-map" class="navigation-map" role="region" aria-label="Routekaart"></div>
                <div class="navigation-sheet">
                    <p id="navigation-status" class="navigation-status">Geen ophaalroute voor vandaag.</p>
                    <ol id="navigation-stops" class="navigation-stops" hidden></ol>
                    <button type="button" class="btn btn-primary" id="btn-start-navigation" disabled>Start navigatie</button>
                </div>
            </div>
            <div id="panel-absences" data-main-tab-panel="absences" hidden></div>
            <div id="panel-profile" class="profile-panel" data-main-tab-panel="profile" hidden>
                <div class="card">
                    <div class="profile-info" id="profile-info" aria-live="polite">
                        <p class="profile-info__name" id="profile-name">—</p>
                        <dl class="profile-info__list">
                            <div class="profile-info__row">
                                <dt class="profile-info__label">E-mailadres</dt>
                                <dd class="profile-info__value" id="profile-email">—</dd>
                            </div>
                            <div class="profile-info__row">
                                <dt class="profile-info__label">Telefoonnummer</dt>
                                <dd class="profile-info__value" id="profile-phone">—</dd>
                            </div>
                            <div class="profile-info__row">
                                <dt class="profile-info__label">Rol</dt>
                                <dd class="profile-info__value" id="profile-role">—</dd>
                            </div>
                            <div class="profile-info__row">
                                <dt class="profile-info__label">Bedrijf</dt>
                                <dd class="profile-info__value" id="profile-company">—</dd>
                            </div>
                        </dl>
                    </div>
                    @include('taxi::partials.pwa-accent', ['section' => 'picker'])
                    <p class="muted profile-session-note">Gegevens zijn alleen ter inzage.</p>
                    <a class="profile-guide-link" id="profile-guide-link" href="{{ $guideUrl ?? url('/taxi/contract/handleiding') }}">
                        <strong>Handleiding</strong>
                    </a>
                    <button type="button" class="btn btn-ghost" id="btn-logout">Uitloggen</button>
                </div>
            </div>
            <p id="home-error" class="error" hidden></p>
        </div>
        <nav class="contract-bottom-nav" aria-label="Hoofdnavigatie">
            <button type="button" class="contract-bottom-nav__btn is-active" data-main-tab="today" aria-current="page">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2" stroke="currentColor" stroke-width="2"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M8 3v4M16 3v4M3 10h18"/></svg>
                <span>Vandaag</span>
            </button>
            <button type="button" class="contract-bottom-nav__btn" data-main-tab="week">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M8 7h8M8 12h8M8 17h5"/><rect x="3" y="4" width="18" height="16" rx="2" stroke="currentColor" stroke-width="2"/></svg>
                <span>Planning</span>
            </button>
            <button type="button" class="contract-bottom-nav__btn" data-main-tab="navigation">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 3 4.5 20.5 12 16.5l7.5 4L12 3Z"/></svg>
                <span>Navigatie</span>
            </button>
            <button type="button" class="contract-bottom-nav__btn" data-main-tab="absences">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="m15 9-6 6M9 9l6 6"/></svg>
                <span>Afmeldingen</span>
            </button>
            <button type="button" class="contract-bottom-nav__btn" data-main-tab="profile">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="2"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M5.5 19.5c1.6-3.2 4-4.8 6.5-4.8s4.9 1.6 6.5 4.8"/></svg>
                <span>Profiel</span>
            </button>
        </nav>
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

<div id="contract-notice-dialog" class="dialog contract-notice" hidden>
    <div class="dialog-panel" role="alertdialog" aria-modal="true" aria-labelledby="contract-notice-title" aria-describedby="contract-notice-text">
        <div id="contract-notice-icon" class="contract-notice-icon is-success" aria-hidden="true">✓</div>
        <h2 id="contract-notice-title" class="contract-notice-title">Gelukt</h2>
        <p id="contract-notice-text" class="contract-notice-text"></p>
        <div class="dialog-actions">
            <button type="button" class="btn btn-primary" id="contract-notice-ok">Oké</button>
        </div>
    </div>
</div>

<div id="contract-confirm-dialog" class="dialog contract-confirm" hidden>
    <div class="dialog-panel" role="dialog" aria-modal="true" aria-labelledby="contract-confirm-title" aria-describedby="contract-confirm-text">
        <div id="contract-confirm-icon" class="contract-notice-icon is-warn" aria-hidden="true">?</div>
        <h2 id="contract-confirm-title" class="contract-notice-title">Weet je het zeker?</h2>
        <p id="contract-confirm-text" class="contract-notice-text"></p>
        <div class="dialog-actions">
            <button type="button" class="btn btn-primary" id="contract-confirm-ok">Bevestigen</button>
            <button type="button" class="btn btn-ghost" id="contract-confirm-cancel">Annuleren</button>
        </div>
    </div>
</div>

<script>
window.NEXA_TAXI_CONTRACT = {
    apiBase: @json($apiBase),
    loginUrl: @json(url('/api/taxi/v1/contract/login')),
    loginCodeRequestUrl: @json(url('/api/taxi/v1/contract/login-code/request')),
    loginCodeVerifyUrl: @json(url('/api/taxi/v1/contract/login-code/verify')),
    appUrl: @json($appUrl ?? url('/taxi/contract')),
    guideUrl: @json($guideUrl ?? url('/taxi/contract/handleiding')),
    pollMs: {{ (int) ($pollMs ?? 15000) }},
    googleMapsApiKey: @json($googleMapsApiKey ?? ''),
    googleMapsMapId: @json($googleMapsMapId ?? ''),
    googleMapsCenterLat: @json($googleMapsCenterLat ?? '52.3676'),
    googleMapsCenterLng: @json($googleMapsCenterLng ?? '4.9041'),
};
</script>
<script src="{{ asset('assets/js/taxi-pwa-accent.js') }}?v=1" defer></script>
<script src="{{ asset('assets/js/taxi-contract-app.js') }}?v=44" defer></script>
@include('partials.password-toggle')
</body>
</html>
