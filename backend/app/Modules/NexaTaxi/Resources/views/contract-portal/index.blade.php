<!DOCTYPE html>
<html lang="nl" class="h-full" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#f97316">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('taxi::partials.pwa-theme', ['section' => 'boot'])
    <link rel="manifest" href="{{ \Illuminate\Support\Facades\Route::has('taxi.contract.manifest') ? route('taxi.contract.manifest') : url('/taxi/contract/manifest.webmanifest') }}">
    <link rel="icon" href="{{ $faviconUrl }}" type="{{ $faviconType }}">
    <link rel="shortcut icon" href="{{ $faviconUrl }}" type="{{ $faviconType }}">
    <link rel="apple-touch-icon" href="{{ $faviconUrl }}">
    <title>Contract – Nexa Taxi</title>
    @include('taxi::partials.pwa-theme', ['section' => 'styles'])
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
        #app { min-height: 100%; display: flex; flex-direction: column; }
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
            height: 100dvh;
            max-height: 100dvh;
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
            min-height: 2.25rem;
            height: 2.25rem;
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
            background: rgba(249, 115, 22, 0.08);
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
        .btn-primary { background: var(--orange); color: #fff; }
        .btn-primary:hover { background: var(--orange-hover); }
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
        .banner-install-app {
            position: relative;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.35rem 0.65rem;
            min-height: 2.75rem;
            background: rgba(22, 163, 74, 0.12);
            border: 1px solid rgba(22, 163, 74, 0.35);
            color: #bbf7d0;
            border-radius: 0.75rem;
            padding: 0.65rem 2.5rem 0.65rem 1rem;
            font-size: 0.8125rem;
            margin-bottom: 0;
            line-height: 1.45;
        }
        #install-hint {
            margin: 0 0 1rem;
            flex-shrink: 0;
        }
        #screen-home > #install-hint {
            margin: 0.75rem 1rem 0;
        }
        #screen-home > #install-hint + .home-scroll {
            padding-top: 0.75rem;
        }
        html[data-theme="light"] .banner-install-app {
            background: #ecfdf5;
            border-color: #6ee7b7;
            color: #065f46;
        }
        .banner-guide-hint {
            position: relative;
            background: rgba(249, 115, 22, 0.14);
            border: 1px solid rgba(249, 115, 22, 0.4);
            color: #fed7aa;
            border-radius: 0.75rem;
            padding: 0.75rem 2.25rem 0.75rem 1rem;
            font-size: 0.8125rem;
            line-height: 1.45;
        }
        html[data-theme="light"] .banner-guide-hint {
            background: #fff7ed;
            border-color: #fdba74;
            color: #9a3412;
        }
        .banner-guide-hint a.btn-inline {
            display: inline-block;
            margin-top: 0.5rem;
            padding: 0.45rem 0.75rem;
            border-radius: 0.5rem;
            border: none;
            background: var(--orange);
            color: #fff;
            font-size: 0.8125rem;
            font-weight: 600;
            text-decoration: none;
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
        .banner-install-app .btn-inline,
        .banner-install-app .btn-link {
            display: inline;
            margin: 0;
            background: transparent;
            border: 0;
            color: inherit;
            font: inherit;
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 2px;
            padding: 0;
            cursor: pointer;
            width: auto;
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
        .week-day.is-active { background: rgba(249, 115, 22, 0.2); border-color: var(--orange); color: var(--orange); }
        .week-day.is-active .wd-num { color: var(--text); }
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
            background: rgba(249, 115, 22, 0.18);
            color: #fdba74;
            margin: 0 0 0.35rem;
        }
        html[data-theme="light"] .guide-platform {
            background: rgba(249, 115, 22, 0.12);
            color: #c2410c;
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
        html[data-theme="light"] .card {
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
        }
    </style>
</head>
<body>
@include('taxi::partials.pwa-theme', ['section' => 'widget'])
<div id="app">
    <section id="screen-login" class="screen is-active" aria-label="Inloggen">
        <div id="install-hint" class="banner-install-app" hidden role="note">
            <button type="button" class="banner-dismiss-btn" id="btn-dismiss-install" aria-label="Sluiten">×</button>
            <span>Installeer deze app op je telefoon voor snelle toegang tot status en afmelden.</span>
            <button type="button" class="btn-link" id="btn-install-guide">Hoe installeren</button>
            <button type="button" class="btn-inline" id="btn-install-app" hidden>Installeer app</button>
        </div>
        <h1>Contract inloggen</h1>
        <div class="card">
            <form id="login-form" autocomplete="on" novalidate>
                <label for="email">E-mail</label>
                <input id="email" name="email" type="email" inputmode="email" autocomplete="username" required aria-describedby="email-error">
                <p id="email-error" class="field-error" hidden role="alert"></p>
                <label for="password">Wachtwoord</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required aria-describedby="password-error">
                <p id="password-error" class="field-error" hidden role="alert"></p>
                <p id="login-error" class="error" hidden role="alert" aria-live="assertive"></p>
                <button type="submit" class="btn btn-primary" id="login-btn">Inloggen</button>
            </form>
        </div>
    </section>

    <section id="screen-home" class="screen" aria-label="Overzicht">
        <div class="home-top">
            <div class="home-top__row">
                <h1 id="home-title">Vandaag</h1>
            </div>
            <div class="home-banners">
                <div id="guide-hint" class="banner-guide-hint" hidden role="note">
                    <button type="button" class="banner-dismiss-btn" id="btn-dismiss-guide-hint" aria-label="Melding sluiten">×</button>
                    <strong>Handleiding.</strong>
                    Nieuw of even niet zeker? Open de handleiding voor inloggen, ritten en afmelden.
                    Na wegklikken vind je die altijd terug onder <strong>Profiel</strong>.
                    <a class="btn-inline" id="btn-open-guide" href="{{ $guideUrl ?? url('/taxi/contract/handleiding') }}">Handleiding openen</a>
                </div>
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
    guideUrl: @json($guideUrl ?? url('/taxi/contract/handleiding')),
    pollMs: {{ (int) ($pollMs ?? 15000) }},
};
</script>
<script src="{{ asset('assets/js/taxi-contract-app.js') }}?v=19" defer></script>
@include('partials.password-toggle')
</body>
</html>
