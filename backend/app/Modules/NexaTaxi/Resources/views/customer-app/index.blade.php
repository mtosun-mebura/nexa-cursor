<!DOCTYPE html>
<html lang="nl" class="h-full" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a" data-nexa-dark-theme-color="#0f172a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://maps.googleapis.com">
    <link rel="preconnect" href="https://maps.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="https://maps.googleapis.com">
    <link rel="manifest" href="{{ $manifestUrl }}">
    <link rel="icon" href="{{ $faviconUrl }}" type="{{ $faviconType }}">
    <title>Nexa Taxi – Boeken</title>
    @include('taxi::partials.pwa-theme', ['section' => 'boot'])
    @include('taxi::partials.pwa-theme', ['section' => 'styles'])
    @include('taxi::partials.pwa-brand-metrics')
    <style>
        :root {
            --blue: #2563eb;
            --blue-hover: #1d4ed8;
            --green: #22c55e;
            --red: #ef4444;
            --taxi: #f97316;
            --safe-top: env(safe-area-inset-top, 0px);
            --safe-bottom: env(safe-area-inset-bottom, 0px);
        }
        html[data-theme="dark"] {
            --bg: #0b1220;
            --card: #121a2b;
            --card2: #182235;
            --text: #f8fafc;
            --muted: #94a3b8;
            --line: rgba(148,163,184,.16);
            --chrome: rgba(11,18,32,.92);
            --link: #93c5fd;
            --map-empty: #0f172a;
        }
        html[data-theme="light"] {
            --bg: #f1f5f9;
            --card: #ffffff;
            --card2: #f8fafc;
            --text: #0f172a;
            --muted: #64748b;
            --line: rgba(15,23,42,.1);
            --chrome: rgba(255,255,255,.94);
            --link: #2563eb;
            --map-empty: #e2e8f0;
        }
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        [hidden] { display: none !important; }
        html, body { height: 100%; margin: 0; overflow: hidden; }
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg); color: var(--text);
            overscroll-behavior: none;
        }
        #app {
            height: 100%;
            min-height: 100%;
            max-height: 100%;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .screen { flex: 1; display: flex; flex-direction: column; min-height: 0; position: relative; overflow: hidden; }

        /* Header: logo veilig onder camera (zelfde aanpak als chauffeur), daaronder navrij */
        .app-chrome {
            flex-shrink: 0;
            background: var(--chrome);
            border-bottom: 1px solid var(--line);
            position: sticky; top: 0; z-index: 20;
            backdrop-filter: blur(10px);
        }
        .app-logo-bar {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: var(--nexa-brand-logo-bar-min);
            padding-left: 1rem;
            padding-right: 1rem;
            padding-bottom: 0.2rem;
            padding-top: calc(var(--safe-top) + 0.35rem);
        }
        @media (max-width: 48rem) {
            .app-logo-bar {
                /* Notch / Dynamic Island: env() is in Safari soms 0 */
                padding-top: max(calc(var(--safe-top) + 0.35rem), 3.75rem);
            }
        }
        html[data-theme="dark"] .app-logo-light { display: none !important; }
        html[data-theme="light"] .app-logo-dark { display: none !important; }

        .app-nav-row {
            display: grid;
            grid-template-columns: var(--nexa-page-side-slot) 1fr var(--nexa-page-side-slot);
            align-items: center;
            gap: 0.35rem;
            min-height: var(--nexa-brand-logo-bar-min);
            padding: 0.25rem 0.75rem 0.4rem;
        }
        .app-nav-start,
        .app-nav-end {
            display: flex;
            align-items: center;
            height: var(--nexa-page-side-slot);
        }
        .app-nav-start { justify-content: flex-start; }
        .app-nav-end { justify-content: flex-end; }
        .app-nav-back {
            width: 2.25rem;
            height: 2.25rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 0.625rem;
            background: transparent;
            color: var(--text);
            cursor: pointer;
            padding: 0;
            -webkit-tap-highlight-color: transparent;
        }
        .app-nav-back svg {
            width: 1.35rem;
            height: 1.35rem;
            display: block;
            stroke: currentColor;
            fill: none;
            stroke-width: 2.2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .app-nav-back:active { opacity: 0.7; }
        .app-nav-end > .nexa-pwa-chrome-actions {
            position: static !important;
            top: auto !important;
            right: auto !important;
            height: 2.25rem;
            padding: 0;
            z-index: auto;
        }
        .app-nav-end .nexa-pwa-theme-toggle {
            width: 2.25rem;
            height: 2.25rem;
        }

        .auth-text-link {
            background: none !important;
            border: 0 !important;
            padding: 0 !important;
            margin: 0;
            font: inherit;
            font-weight: 700;
            color: #ffffff;
            cursor: pointer;
            text-decoration: none;
            display: inline;
            height: auto !important;
            border-radius: 0 !important;
            -webkit-tap-highlight-color: transparent;
        }
        html[data-theme="light"] .auth-text-link {
            color: #2563eb;
        }

        .profile-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
        }
        .profile-card-head h2 { margin: 0; font-size: .95rem; }
        .icon-btn {
            width: 2.25rem;
            height: 2.25rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--line);
            border-radius: 0.625rem;
            background: var(--card2);
            color: var(--text);
            cursor: pointer;
            padding: 0;
            flex-shrink: 0;
        }
        .icon-btn svg {
            width: 1.15rem;
            height: 1.15rem;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .icon-btn:active { opacity: 0.75; }
        .profile-read {
            margin: 0;
            display: grid;
            gap: 10px;
        }
        .profile-read-row {
            display: grid;
            grid-template-columns: 6.5rem 1fr;
            gap: 8px 12px;
            align-items: baseline;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--line);
        }
        .profile-read-row:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }
        .profile-read-row dt {
            margin: 0;
            font-size: .82rem;
            font-weight: 600;
            color: var(--muted);
            letter-spacing: 0;
        }
        .profile-read-row dd {
            margin: 0;
            font-size: .95rem;
            font-weight: 600;
            color: var(--text);
            word-break: break-word;
        }
        .profile-form-actions {
            display: flex;
            gap: 10px;
            margin-top: 4px;
        }
        .profile-form-actions .btn { flex: 1; }

        .content {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            padding: 16px 16px calc(96px + var(--safe-bottom));
        }
        .screen-welcome .content { padding-top: 8px; }
        .map-wrap {
            height: 280px; border-radius: 18px; overflow: hidden; border: 1px solid var(--line);
            background: var(--map-empty); margin-bottom: 14px; position: relative;
        }
        .map-wrap.map-wrap--route-reveal {
            animation: map-route-reveal 1.25s ease;
        }
        @keyframes map-route-reveal {
            0% { transform: scale(0.985); box-shadow: 0 0 0 0 rgba(37, 99, 235, 0); }
            35% { transform: scale(1.015); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.5); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(37, 99, 235, 0); }
        }
        @media (prefers-reduced-motion: reduce) {
            .map-wrap.map-wrap--route-reveal { animation: none; }
        }
        #map, #live-map { width: 100%; height: 100%; }
        .map-status {
            position: absolute; left: 10px; bottom: 10px; z-index: 2;
            background: rgba(15,23,42,.78); color: #fff; font-size: .75rem; font-weight: 600;
            padding: 6px 10px; border-radius: 999px; pointer-events: none;
            max-width: calc(100% - 72px);
        }
        .map-zoom {
            position: absolute; right: 10px; top: 10px; z-index: 2;
            display: flex; flex-direction: column; gap: 6px;
        }
        .map-zoom button {
            width: 36px; height: 36px; border-radius: 10px;
            border: 1px solid var(--line); background: var(--card); color: var(--text);
            font-size: 1.15rem; font-weight: 700; line-height: 1; cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center; padding: 0;
        }
        .map-zoom button svg {
            width: 18px; height: 18px; stroke: currentColor; fill: none;
            stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
        }
        .section-label {
            margin: 0 0 6px; font-size: .8rem; font-weight: 700; color: var(--muted);
        }
        .route-summary { margin: 0 0 4px; font-size: .95rem; font-weight: 600; color: var(--text); }
        .quote-warn { margin: 8px 0 0; color: #d97706; font-size: .85rem; }
        .qty-wrap { display: inline-flex; align-items: center; gap: 8px; }
        .qty-wrap button {
            width: 2.25rem; height: 2.25rem; border-radius: 10px; border: 1px solid var(--line);
            background: var(--card); color: var(--text); font-size: 1.2rem; font-weight: 700;
            cursor: pointer; line-height: 1;
        }
        .qty-wrap button:disabled { opacity: .4; cursor: default; }
        .qty-value { min-width: 1.5rem; text-align: center; font-size: 1.05rem; font-weight: 700; color: var(--text); }
        .field-loc-btn {
            position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
            width: 2.25rem; height: 2.25rem; border: 0; border-radius: 10px;
            background: transparent; color: var(--blue, #2563eb); cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center; padding: 0;
        }
        .field-loc-btn svg {
            width: 22px; height: 22px; stroke: currentColor; fill: none;
            stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
        }
        .field-suggest-input-wrap.has-loc-btn input { padding-right: 4.6rem; }
        .field-suggest-input-wrap.has-loc-btn .field-clear { right: 42px; }
        .field-loc-btn { z-index: 2; }
        .profile-hint { margin: 8px 0 0; color: var(--muted); font-size: .8rem; }
        .theme-row {
            display: flex; align-items: center; gap: 10px; width: 100%;
            text-align: left; border: 1px solid var(--line); background: transparent;
            color: inherit; border-radius: 12px; padding: 10px 12px; margin-top: 8px; cursor: pointer;
        }
        .theme-row.is-on { border-color: var(--blue, #2563eb); background: rgba(37, 99, 235, .12); }
        .theme-radio {
            width: 16px; height: 16px; border-radius: 999px; border: 2px solid var(--muted); flex-shrink: 0;
        }
        .theme-row.is-on .theme-radio { border-color: var(--blue, #2563eb); background: var(--blue, #2563eb); }
        .theme-row strong { display: block; font-size: .9rem; color: var(--text); }
        .theme-row span { display: block; font-size: .75rem; color: var(--muted); margin-top: 2px; }
        .ride-card__title { margin: 8px 0 0; font-size: 1rem; font-weight: 700; color: var(--text); }
        .ride-card__title--searching {
            display: flex; align-items: center; gap: 8px;
            margin: 10px 0 8px; font-size: .95rem; font-weight: 700; color: #f59e0b;
            min-height: 1.4em; line-height: 1.35;
        }
        html[data-theme="light"] .ride-card__title--searching { color: #b45309; }
        .searching-title {
            display: flex; align-items: center; gap: 8px;
            margin: 10px 0 8px; font-size: .95rem; font-weight: 700; color: #f59e0b;
            min-height: 1.4em; line-height: 1.35;
        }
        html[data-theme="light"] .searching-title { color: #b45309; }
        .searching-title[hidden] { display: none !important; }
        .searching-title__dot {
            width: 8px; height: 8px; border-radius: 999px; flex-shrink: 0;
            background: currentColor;
        }
        .searching-title__text,
        .js-searching-typed {
            min-width: 0;
            display: inline-block;
            min-height: 1.35em;
            line-height: 1.35;
        }
        .live-meta {
            margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--line);
            display: grid; gap: 12px;
        }
        .live-meta[hidden] { display: none !important; }
        .live-meta__row {
            display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
        }
        .live-meta__row > div { min-width: 0; }
        .live-meta__row > div:last-child:not(:only-child) { text-align: right; }
        .live-meta .ride-card__stop-value {
            font-weight: 700; color: var(--text);
        }
        .ride-card__line { margin: 6px 0 0; font-size: .9rem; font-weight: 600; color: var(--text); }
        .ride-card__chevron {
            display: inline-flex; align-items: center; justify-content: center;
            color: var(--muted); line-height: 1; flex-shrink: 0;
            width: 28px; height: 28px;
        }
        .ride-card__chevron-icon {
            width: 22px; height: 22px;
            stroke: currentColor; fill: none;
            stroke-width: 2.4; stroke-linecap: round; stroke-linejoin: round;
        }
        .ride-card__expanded {
            margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--line);
        }
        .ride-card.is-expanded { cursor: default; }
        html[data-theme="light"] .map-status {
            background: rgba(255,255,255,.92); color: #0f172a; border: 1px solid var(--line);
        }
        .customer-live-taxi {
            position: absolute;
            transform: translate(-50%, -50%);
            pointer-events: none;
            z-index: 5;
        }
        .customer-live-taxi__car {
            width: 28px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            filter: drop-shadow(0 2px 3px rgba(0,0,0,.4));
            transition: transform .35s linear;
            transform-origin: 50% 50%;
        }
        .customer-live-taxi__car.is-van { width: 30px; height: 42px; }
        .customer-live-taxi__car.is-bus { width: 34px; height: 56px; }
        .customer-live-taxi__car img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
            pointer-events: none;
        }
        .card {
            background: var(--card); border: 1px solid var(--line); border-radius: 18px;
            padding: 14px; margin-bottom: 12px;
        }
        .card h2 { margin: 0 0 10px; font-size: .95rem; }
        .baggage-list { display: flex; flex-direction: column; gap: 8px; }
        .baggage-row {
            display: flex; align-items: center; justify-content: space-between; gap: 10px;
            padding: 10px 12px; border: 1px solid var(--line); border-radius: 12px;
            background: var(--card2);
        }
        .baggage-row__copy { min-width: 0; }
        .baggage-row__copy strong { display: block; font-size: .9rem; }
        .baggage-row__copy span { display: block; font-size: .75rem; color: var(--muted); margin-top: 2px; }
        .baggage-qty {
            display: inline-flex; align-items: center; gap: 6px; flex-shrink: 0;
        }
        .baggage-qty button {
            width: 2rem; height: 2rem; border-radius: 10px; border: 1px solid var(--line);
            background: var(--card); color: var(--text); font-size: 1.1rem; font-weight: 700;
            cursor: pointer; line-height: 1;
        }
        .baggage-qty span {
            min-width: 1.25rem; text-align: center; font-weight: 700; font-size: .95rem;
        }
        .field { margin-bottom: 12px; }
        .field:last-child { margin-bottom: 0; }
        .field label {
            display: block; font-size: .82rem; font-weight: 600; color: var(--muted);
            letter-spacing: 0; margin-bottom: 6px;
        }
        .field input, .field textarea, .field select {
            width: 100%; border-radius: 12px; border: 1px solid var(--line);
            background: var(--card2); color: var(--text); padding: 12px 14px;
            font: inherit; outline: none;
        }
        .field-suggest-input-wrap {
            position: relative;
            display: block;
        }
        .field-suggest-input-wrap input {
            padding-right: 42px;
        }
        .field-clear {
            position: absolute;
            top: 50%;
            right: 8px;
            transform: translateY(-50%);
            width: 28px;
            height: 28px;
            margin: 0;
            padding: 0;
            border: 0;
            border-radius: 999px;
            background: transparent;
            color: var(--muted);
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2;
            -webkit-tap-highlight-color: transparent;
        }
        .field-clear svg {
            width: 1.15rem;
            height: 1.15rem;
            display: block;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .field-clear:hover,
        .field-clear:focus-visible {
            color: var(--text);
            background: rgba(148, 163, 184, 0.18);
            outline: none;
        }
        .field-suggest-input-wrap.has-value .field-clear {
            display: inline-flex;
        }
        .field input:focus, .field textarea:focus { border-color: rgba(37,99,235,.6); }
        .field .hint { margin: 6px 0 0; font-size: .8rem; color: var(--muted); }
        .location-notice {
            margin-top: 8px;
            padding: 10px 12px;
            border-radius: 12px;
            border: 1px solid rgba(245, 158, 11, 0.35);
            background: rgba(245, 158, 11, 0.12);
            color: var(--text);
        }
        html[data-theme="light"] .location-notice {
            border-color: rgba(217, 119, 6, 0.35);
            background: rgba(251, 191, 36, 0.16);
        }
        .location-notice[hidden] { display: none !important; }
        .location-notice__text {
            margin: 0;
            font-size: .85rem;
            line-height: 1.4;
        }
        .location-notice__actions {
            margin-top: 8px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .location-notice__retry {
            width: auto;
            padding: 8px 12px;
            border-radius: 10px;
            border: 1px solid rgba(37, 99, 235, 0.45);
            background: var(--blue);
            color: #fff;
            font: inherit;
            font-size: .82rem;
            font-weight: 600;
            cursor: pointer;
        }
        .location-notice__retry:active { opacity: .85; }
        .location-notice__help {
            margin: 8px 0 0;
            font-size: .78rem;
            line-height: 1.4;
            color: var(--muted);
        }
        .location-notice__help a {
            color: var(--blue);
            text-decoration: underline;
            font-weight: 600;
        }
        .row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; align-items: start; }
        .row2 .field select {
            min-height: 3.5rem;
            height: 3.5rem;
            box-sizing: border-box;
        }
        /* Zelfde hoogte/padding als .field input (Bestemming) */
        .pickup-at-btn {
            width: 100%; border-radius: 12px; border: 1px solid var(--line);
            background: var(--card2); color: var(--text); padding: 12px 14px;
            font: inherit; text-align: left; cursor: pointer;
            display: flex; align-items: center; justify-content: space-between; gap: 8px;
            overflow: hidden; box-sizing: border-box;
            min-height: calc(1em + 24px + 2px);
        }
        .pickup-at-btn:focus, .pickup-at-btn:active {
            border-color: rgba(37,99,235,.6); outline: none;
        }
        .pickup-at-btn__value {
            font-weight: 700; font-size: inherit; line-height: 1.25;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            min-width: 0;
        }
        .dt-overlay {
            position: fixed; inset: 0; z-index: 80;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            display: flex; align-items: center; justify-content: center;
            padding: max(12px, var(--safe-top)) 14px max(12px, var(--safe-bottom));
        }
        .dt-sheet {
            width: min(100%, 400px);
            max-height: min(92dvh, 720px);
            background: #0b0f19;
            color: #f8fafc;
            border: 1px solid rgba(148,163,184,.18);
            border-radius: 1rem;
            box-shadow: 0 20px 50px rgba(0,0,0,.45);
            overflow: auto;
            padding: 16px;
            animation: dt-pop .18s ease-out;
            scrollbar-width: thin;
            scrollbar-color: rgba(148,163,184,.55) transparent;
        }
        .dt-sheet::-webkit-scrollbar { width: 6px; }
        .dt-sheet::-webkit-scrollbar-track { background: transparent; }
        .dt-sheet::-webkit-scrollbar-thumb { background: rgba(148,163,184,.5); border-radius: 999px; }
        html[data-theme="light"] .dt-sheet {
            background: #ffffff;
            color: #0f172a;
            box-shadow: 0 18px 40px rgba(15,23,42,.18);
            scrollbar-color: rgba(100,116,139,.55) rgba(226,232,240,.7);
        }
        html[data-theme="light"] .dt-sheet::-webkit-scrollbar-track { background: rgba(226,232,240,.7); }
        html[data-theme="light"] .dt-sheet::-webkit-scrollbar-thumb { background: rgba(100,116,139,.5); }
        @keyframes dt-pop {
            from { transform: translateY(8px) scale(.98); opacity: .65; }
            to { transform: translateY(0) scale(1); opacity: 1; }
        }
        .dt-sheet__title { margin: 0 0 12px; font-size: 18px; font-weight: 700; }
        .dt-quick {
            display: flex; align-items: stretch; gap: 6px; margin-bottom: 12px;
        }
        .dt-chip {
            flex: 1 1 0; min-width: 0;
            border: 1px solid rgba(148,163,184,.35);
            background: transparent; color: inherit; border-radius: 999px;
            padding: 10px 4px; font: inherit; font-size: 11px; font-weight: 700;
            line-height: 1.15; text-align: center; cursor: pointer;
            white-space: nowrap;
        }
        .dt-chip.is-active {
            border-color: var(--blue);
            box-shadow: none;
            background: var(--blue);
            color: #fff;
        }
        html[data-theme="light"] .dt-chip {
            border-color: rgba(15,23,42,.18);
            color: #0f172a;
        }
        html[data-theme="light"] .dt-chip.is-active {
            border-color: var(--blue);
            box-shadow: none;
            background: var(--blue);
            color: #fff;
        }
        .dt-days {
            display: flex; flex-direction: row; align-items: stretch; gap: 8px;
            margin: 0;
            padding: 0;
            overflow-x: auto; overflow-y: hidden;
            background: transparent;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .dt-days::-webkit-scrollbar { display: none; height: 0; }
        .dt-day {
            flex: 0 0 auto; min-width: 76px;
            border: 1px solid rgba(148,163,184,.28);
            background: rgba(255,255,255,.04); color: inherit; border-radius: 12px;
            padding: 10px 12px; cursor: pointer; text-align: center; font: inherit;
        }
        html[data-theme="light"] .dt-day {
            background: #f8fafc;
            border-color: rgba(15,23,42,.12);
        }
        .dt-day__wd { display: block; font-size: 11px; font-weight: 600; color: var(--muted); margin-bottom: 2px; }
        .dt-day__nr { display: block; font-size: 16px; font-weight: 700; line-height: 1.15; }
        .dt-day__month { display: block; font-size: 11px; font-weight: 600; color: var(--muted); margin-top: 2px; text-transform: lowercase; }
        .dt-day.is-active {
            background: var(--blue); border-color: var(--blue); color: #fff;
        }
        .dt-day.is-active .dt-day__wd,
        .dt-day.is-active .dt-day__nr,
        .dt-day.is-active .dt-day__month { color: #fff; }
        .dt-time {
            display: flex; align-items: center; gap: 8px;
            margin-top: 20px;
            margin-bottom: 12px;
        }
        .dt-time__sep {
            flex: 0 0 auto; font-size: 22px; font-weight: 700; line-height: 1;
        }
        .dt-col {
            position: relative;
            flex: 1 1 0; min-width: 0;
            /* 5 × 48px: randuren/minuten volledig zichtbaar, geen fade-mask */
            height: 240px; overflow-y: auto; scroll-snap-type: y mandatory;
            -webkit-overflow-scrolling: touch;
            border: 1px solid rgba(148,163,184,.28);
            border-radius: 12px;
            background: transparent;
            padding: 96px 0;
            box-sizing: border-box;
            scrollbar-width: thin;
            scrollbar-color: rgba(148,163,184,.55) transparent;
            mask-image: none;
            -webkit-mask-image: none;
        }
        html[data-theme="light"] .dt-col {
            border-color: rgba(15,23,42,.12);
            scrollbar-color: rgba(100,116,139,.55) rgba(226,232,240,.7);
        }
        .dt-col::-webkit-scrollbar { width: 6px; }
        .dt-col::-webkit-scrollbar-track { background: transparent; }
        .dt-col::-webkit-scrollbar-thumb { background: rgba(148,163,184,.5); border-radius: 999px; }
        html[data-theme="light"] .dt-col::-webkit-scrollbar-track { background: rgba(226,232,240,.7); }
        html[data-theme="light"] .dt-col::-webkit-scrollbar-thumb { background: rgba(100,116,139,.5); }
        .dt-item {
            height: 48px; display: flex; align-items: center; justify-content: center;
            scroll-snap-align: center; font-size: 20px; font-weight: 600;
            color: var(--muted); cursor: pointer; user-select: none;
        }
        .dt-item.is-active {
            background: rgba(37,99,235,.42);
            color: #60a5fa;
            font-weight: 800;
        }
        html[data-theme="light"] .dt-item.is-active {
            background: rgba(37,99,235,.16);
            color: #1d4ed8;
        }
        .dt-preview {
            margin: 0 0 10px;
            text-align: center;
            font-size: 15px;
            font-weight: 700;
        }
        #dt-confirm {
            text-transform: none;
            letter-spacing: 0;
            border-radius: 14px;
            padding: 14px 16px;
            font-size: 16px;
        }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%; border: 0; border-radius: 14px; padding: 14px 16px;
            font-size: 1rem; font-weight: 700; cursor: pointer;
        }
        .live-actions .btn,
        .live-actions .btn-primary,
        .live-actions .btn-danger,
        .live-actions .btn-invoice {
            width: 100%;
            padding: 8px 12px;
            font-size: .82rem;
            font-weight: 700;
            line-height: 1.2;
            border-radius: 12px;
            min-height: 0;
        }
        .btn-primary { background: var(--blue); color: #fff; }
        .btn-primary:disabled { opacity: .5; cursor: not-allowed; }
        .btn-ghost {
            background: transparent; color: var(--muted); border: 1px solid var(--line);
        }
        .btn-sm { width: auto; padding: 8px 12px; font-size: .85rem; border-radius: 10px; }
        .field-suggest { position: relative; }
        .suggestions {
            list-style: none; margin: 6px 0 0; padding: 0; border: 1px solid var(--line);
            border-radius: 12px; background: var(--card2);
            max-height: 220px; overflow-x: hidden; overflow-y: auto;
            position: absolute; left: 0; right: 0; z-index: 40;
            box-shadow: 0 10px 28px rgba(0,0,0,.28);
            scrollbar-width: thin;
            scrollbar-color: rgba(148,163,184,.7) transparent;
        }
        .suggestions::-webkit-scrollbar { width: 6px; }
        .suggestions::-webkit-scrollbar-track { background: transparent; }
        .suggestions::-webkit-scrollbar-thumb { background: rgba(148,163,184,.55); border-radius: 999px; }
        html[data-theme="light"] .suggestions {
            box-shadow: 0 10px 24px rgba(15,23,42,.12);
            scrollbar-color: rgba(100,116,139,.55) transparent;
        }
        html[data-theme="light"] .suggestions::-webkit-scrollbar-thumb { background: rgba(100,116,139,.45); }
        .suggestions li {
            padding: 10px 12px; border-bottom: 1px solid var(--line); cursor: pointer;
            font-size: .88rem; line-height: 1.35;
        }
        .suggestions li:last-child { border-bottom: 0; }
        .suggestions li:active, .suggestions li:hover { background: rgba(37,99,235,.15); }
        .tabs {
            position: fixed; left: 0; right: 0; bottom: 0;
            display: grid; grid-template-columns: repeat(3, 1fr);
            gap: 4px; padding: 6px 8px calc(6px + var(--safe-bottom));
            background: var(--chrome); border-top: 1px solid var(--line);
            backdrop-filter: blur(12px); z-index: 30;
        }
        .tabs[hidden],
        .tabs.is-keyboard-hidden { display: none !important; }
        .tab {
            background: none; border: 0; color: var(--muted);
            padding: 8px 4px 6px;
            font-size: .68rem; font-weight: 600; cursor: pointer; border-radius: 12px;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 3px; line-height: 1.1; min-height: 3.25rem;
            position: relative;
        }
        .tab svg {
            width: 1.35rem; height: 1.35rem; display: block; flex-shrink: 0;
            stroke: currentColor; fill: none; stroke-width: 1.8;
            stroke-linecap: round; stroke-linejoin: round;
        }
        .tab span { display: block; }
        .tab.active { color: var(--blue); background: rgba(37,99,235,.14); }
        html[data-theme="light"] .tab.active { color: #1d4ed8; background: rgba(37,99,235,.1); }
        .tab-badge {
            position: absolute; top: 4px; right: calc(50% - 1.35rem);
            min-width: 1.05rem; height: 1.05rem; padding: 0 4px;
            border-radius: 999px; background: var(--taxi); color: #fff;
            font-size: .62rem; font-weight: 800; line-height: 1.05rem;
            display: none; align-items: center; justify-content: center;
        }
        .tab-badge.is-on { display: inline-flex; }
        .active-ride-banner {
            display: flex; align-items: center; justify-content: space-between; gap: 10px;
            margin: 0 0 12px; padding: 12px 14px; width: 100%; max-width: 100%;
            border-radius: 14px; border: 1px solid rgba(34,197,94,.4);
            background: rgba(34,197,94,.14); cursor: pointer;
            box-sizing: border-box; min-width: 0;
        }
        html[data-theme="light"] .active-ride-banner {
            background: rgba(34,197,94,.1); border-color: rgba(34,197,94,.3);
        }
        .active-ride-banner[hidden] { display: none !important; }
        .active-ride-banner__text {
            flex: 1 1 auto; min-width: 0; overflow: hidden; text-align: left;
        }
        .active-ride-banner__title {
            margin: 0; font-size: .88rem; font-weight: 700; color: #4ade80;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        html[data-theme="light"] .active-ride-banner__title { color: #15803d; }
        .active-ride-banner__meta {
            margin: 2px 0 0; font-size: .75rem; color: var(--muted); line-height: 1.35;
            display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2;
            overflow: hidden; word-break: break-word;
        }
        .active-ride-banner__cta {
            flex-shrink: 0; font-size: .78rem; font-weight: 700; color: #4ade80;
        }
        html[data-theme="light"] .active-ride-banner__cta { color: #15803d; }
        .hero-welcome { text-align: center; padding: 12px 8px 24px; }
        .hero-welcome h1 { display: none; }
        .hero-welcome p { margin: 0 auto 24px; max-width: 320px; color: var(--muted); line-height: 1.45; }
        .stack { display: grid; gap: 10px; }
        .status-pill {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 8px 12px; border-radius: 999px; font-size: .85rem; font-weight: 600;
            background: rgba(37,99,235,.18); color: #93c5fd;
            border: 1px solid transparent;
        }
        html[data-theme="light"] .status-pill { color: #1d4ed8; }
        .status-pill.ok { background: rgba(34,197,94,.15); color: #16a34a; }
        .status-pill.warn { background: rgba(234,179,8,.15); color: #ca8a04; }
        /* Native PhasePill: kleur-overloop, geen pulse-punt */
        .status-pill.is-searching {
            gap: 0;
            background: rgba(37, 99, 235, 0.12);
            border-color: #2563EB;
            color: #2563EB;
            animation: searching-pill-hue 3.6s linear infinite;
        }
        html[data-theme="light"] .status-pill.is-searching {
            background: rgba(37, 99, 235, 0.1);
        }
        @keyframes searching-pill-hue {
            0%, 100% { color: #2563EB; border-color: #2563EB; }
            16.67% { color: #06B6D4; border-color: #06B6D4; }
            33.33% { color: #14B8A6; border-color: #14B8A6; }
            50% { color: #22C55E; border-color: #22C55E; }
            66.67% { color: #14B8A6; border-color: #14B8A6; }
            83.33% { color: #06B6D4; border-color: #06B6D4; }
        }
        .ride-item {
            display: block; width: 100%; text-align: left; background: var(--card);
            border: 1px solid var(--line); border-radius: 16px; padding: 14px; margin-bottom: 10px;
            color: inherit; cursor: pointer;
        }
        .ride-item .meta { color: var(--muted); font-size: .8rem; margin-top: 6px; }
        .rides-hero {
            position: relative;
            overflow: hidden;
            border-radius: 20px;
            padding: 18px 16px 16px;
            margin-bottom: 16px;
            border: 1px solid rgba(37,99,235,.35);
            background:
                radial-gradient(120% 90% at 100% 0%, rgba(37,99,235,.35), transparent 55%),
                linear-gradient(145deg, rgba(37,99,235,.18), rgba(15,23,42,.15));
            cursor: pointer;
            text-align: left;
            width: 100%;
            color: inherit;
        }
        html[data-theme="light"] .rides-hero {
            background:
                radial-gradient(120% 90% at 100% 0%, rgba(37,99,235,.22), transparent 55%),
                linear-gradient(145deg, #eff6ff, #ffffff);
            border-color: rgba(37,99,235,.22);
        }
        .rides-hero.is-accepted {
            border-color: rgba(34,197,94,.4);
            background:
                radial-gradient(120% 90% at 100% 0%, rgba(34,197,94,.28), transparent 55%),
                linear-gradient(145deg, rgba(34,197,94,.16), rgba(15,23,42,.12));
        }
        html[data-theme="light"] .rides-hero.is-accepted {
            background:
                radial-gradient(120% 90% at 100% 0%, rgba(34,197,94,.18), transparent 55%),
                linear-gradient(145deg, #f0fdf4, #ffffff);
            border-color: rgba(34,197,94,.28);
        }
        .rides-hero__eyebrow {
            display: inline-flex; align-items: center; gap: 7px;
            font-size: .72rem; font-weight: 700; letter-spacing: .04em;
            text-transform: uppercase; color: var(--link); margin: 0 0 8px;
        }
        .rides-hero.is-accepted .rides-hero__eyebrow { color: #16a34a; }
        .rides-hero__title {
            margin: 0 0 4px; font-size: 1.15rem; font-weight: 800; line-height: 1.25;
        }
        .rides-hero__route {
            margin: 0 0 14px; color: var(--muted); font-size: .86rem; line-height: 1.4;
        }
        .rides-hero__foot {
            display: flex; align-items: center; justify-content: space-between; gap: 10px;
        }
        .rides-hero__cta {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 12px; border-radius: 999px;
            background: var(--blue); color: #fff; font-size: .8rem; font-weight: 700;
        }
        .rides-hero.is-accepted .rides-hero__cta { background: #16a34a; }
        .rides-section {
            margin: 4px 0 10px;
            font-size: .82rem; font-weight: 700; letter-spacing: 0;
            color: var(--muted);
        }
        .ride-card {
            display: block; width: 100%; text-align: left;
            background: var(--card); border: 1px solid var(--line);
            border-radius: 18px; padding: 14px; margin-bottom: 10px;
            color: inherit; cursor: pointer;
            transition: border-color .15s ease, transform .15s ease;
        }
        .ride-card:active { transform: scale(.985); }
        /* Actuele zoek-/geaccepteerde rit = alleen de live-bannerbox, geen buitenste ritkaart */
        .ride-card--live-only {
            position: relative;
            padding: 0; border: 0; background: transparent; box-shadow: none;
            cursor: default;
        }
        .ride-card--live-only:active { transform: none; }
        .ride-card--live-only .ride-follow-host { display: block; }
        .ride-card--live-only .live-banner { margin-bottom: 10px; }
        .ride-card--live-only .ride-card__collapse {
            position: absolute;
            top: 12px;
            right: 12px;
            z-index: 3;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            padding: 0;
            border: 0;
            border-radius: 10px;
            background: transparent;
            color: var(--muted);
            cursor: pointer;
        }
        .ride-card--live-only .live-banner.is-accepted {
            padding-right: 44px;
        }
        .ride-card.is-accepted .ride-card__title {
            color: var(--green);
            font-weight: 700;
        }
        .ride-card__foot--meta {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-top: 8px;
        }
        .ride-card__foot--meta > div { min-width: 0; }
        .ride-card__foot--meta > div:last-child { text-align: right; }
        .ride-card--searching-full { cursor: default; }
        .ride-card--searching-full:active { transform: none; }
        .ride-card--searching-full .live-search-desc {
            margin: 0 0 0; color: var(--muted); font-size: .9rem; line-height: 1.4;
        }
        .ride-card--searching-full .live-meta,
        .ride-card--searching-full .live-route {
            margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--line);
        }
        .ride-card--searching-full .live-meta + .live-route {
            margin-top: 12px; padding-top: 0; border-top: 0;
        }
        .ride-card--searching-full .ride-card__cancel {
            margin-top: 14px; width: 100%;
        }
        .ride-card.is-active {
            border-color: rgba(37,99,235,.4);
            box-shadow: 0 8px 24px rgba(37,99,235,.12);
        }
        .ride-card.is-accepted {
            border-color: rgba(34,197,94,.4);
            box-shadow: 0 8px 24px rgba(34,197,94,.1);
        }
        .ride-card__top {
            display: flex; align-items: center; justify-content: space-between; gap: 10px;
            margin-bottom: 12px;
        }
        .ride-card__pill {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 10px; border-radius: 999px;
            font-size: .72rem; font-weight: 700;
            background: rgba(148,163,184,.14); color: var(--muted);
        }
        .ride-card__pill.is-searching { background: rgba(37,99,235,.16); color: #93c5fd; }
        html[data-theme="light"] .ride-card__pill.is-searching { color: #1d4ed8; }
        .ride-card__pill.is-accepted { background: rgba(34,197,94,.15); color: #16a34a; }
        .ride-card__pill.is-done { background: rgba(148,163,184,.12); color: var(--muted); }
        .ride-card__pill.is-cancelled { background: rgba(239,68,68,.12); color: #ef4444; }
        .ride-card__status {
            display: inline-flex; align-items: center; gap: 8px; min-width: 0;
        }
        .ride-card__invoice-icon {
            display: inline-flex; align-items: center; justify-content: center;
            width: auto; height: auto; flex-shrink: 0;
            border: 0 !important; border-radius: 0 !important;
            background: none !important; box-shadow: none !important;
            color: var(--link);
            padding: 1px; margin: 0; cursor: pointer;
            appearance: none; -webkit-appearance: none;
        }
        .ride-card__invoice-icon:hover,
        .ride-card__invoice-icon:focus-visible,
        .ride-card__invoice-icon:active {
            background: none !important;
            color: #60a5fa;
            outline: none;
        }
        .ride-card__invoice-icon svg {
            width: 15px; height: 15px;
            stroke: currentColor; fill: none;
            stroke-width: 1.9; stroke-linecap: round; stroke-linejoin: round;
        }
        .ride-card__archive-icon {
            display: inline-flex; align-items: center; justify-content: center;
            width: auto; height: auto; flex-shrink: 0;
            border: 0 !important; border-radius: 0 !important;
            background: none !important; box-shadow: none !important;
            color: var(--muted);
            padding: 1px; margin: 0; cursor: pointer;
            appearance: none; -webkit-appearance: none;
        }
        .ride-card__archive-icon:hover,
        .ride-card__archive-icon:focus-visible,
        .ride-card__archive-icon:active {
            background: none !important;
            color: var(--text);
            outline: none;
        }
        .ride-card__archive-icon svg {
            width: 15px; height: 15px;
            stroke: currentColor; fill: none;
            stroke-width: 1.9; stroke-linecap: round; stroke-linejoin: round;
        }
        .rides-archive-link {
            display: flex; align-items: center; justify-content: space-between; gap: 10px;
            width: 100%; margin: 6px 0 4px; padding: 12px 14px;
            border: 1px dashed var(--line); border-radius: 14px;
            background: transparent; color: var(--text);
            font: inherit; cursor: pointer; text-align: left;
        }
        .rides-archive-link:hover,
        .rides-archive-link:focus-visible {
            border-color: rgba(37,99,235,.45);
            color: var(--link);
            outline: none;
        }
        .rides-archive-link__meta {
            font-size: .82rem; font-weight: 700; color: var(--muted);
        }
        .rides-archive-link__cta {
            font-size: .82rem; font-weight: 700; color: var(--link); white-space: nowrap;
        }
        .ride-card__amount {
            font-size: .94rem; font-weight: 700; color: var(--text); white-space: nowrap;
            line-height: 1.25;
        }
        .ride-card__route {
            display: grid; grid-template-columns: 14px 1fr; gap: 0 10px; align-items: stretch;
        }
        .ride-card__spine {
            position: relative; width: 14px;
        }
        .ride-card__spine::before {
            content: ''; position: absolute; left: 6px; top: 10px; bottom: 10px;
            width: 2px; border-radius: 2px; background: var(--line);
        }
        .ride-card__dot {
            position: absolute; left: 2px; width: 10px; height: 10px; border-radius: 50%;
            border: 2px solid var(--card); box-shadow: 0 0 0 1px rgba(148,163,184,.35);
        }
        .ride-card__dot.is-from { top: 4px; background: #ea580c; }
        .ride-card__dot.is-to { bottom: 4px; background: #22c55e; }
        .ride-card__stops { min-width: 0; display: grid; gap: 10px; }
        .ride-card__stop-label {
            margin: 0; font-size: .82rem; font-weight: 600; color: var(--muted);
            letter-spacing: 0;
        }
        .ride-card.is-accepted .ride-card__stop-label,
        .rides-hero.is-accepted .ride-card__stop-label,
        .live-route .ride-card__stop-label {
            font-size: .82rem;
            letter-spacing: 0;
        }
        .ride-card__stop-value {
            margin: 2px 0 0;
            font-size: .9rem;
            font-weight: 600;
            color: var(--muted);
            line-height: 1.3;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        .ride-card.is-active .ride-card__stop-value,
        .ride-card.is-accepted .ride-card__stop-value,
        .live-route.is-live-active .ride-card__stop-value,
        .rides-hero .ride-card__stop-value {
            font-size: .9rem;
            font-weight: 700;
            color: var(--text);
            line-height: 1.3;
        }
        .ride-card__foot {
            display: flex; align-items: center; justify-content: space-between; gap: 10px;
            margin-top: 4px; padding-top: 0; border-top: none;
        }
        .ride-card__when {
            font-size: 1.12rem; font-weight: 800; color: var(--text); line-height: 1.25;
            letter-spacing: -0.02em;
        }
        .ride-stops {
            position: relative;
            display: grid;
            gap: 16px;
            margin-top: 14px;
        }
        .ride-stops::before {
            content: '';
            position: absolute;
            left: 9px;
            top: 16px;
            bottom: 16px;
            width: 2px;
            background: var(--line);
        }
        .ride-stop__head {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 4px;
        }
        .ride-stop__dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            box-shadow: 0 0 0 5px rgba(34, 197, 94, 0.18);
            position: relative;
            z-index: 1;
        }
        .ride-stop__dot.is-pickup { background: #22c55e; }
        .ride-stop__dot.is-dropoff {
            background: #2563eb;
            box-shadow: 0 0 0 5px rgba(37, 99, 235, 0.18);
        }
        .ride-stop__label {
            color: var(--muted);
            font-size: .82rem;
            font-weight: 600;
        }
        .ride-stop__main {
            margin: 0 0 0 30px;
            color: var(--text);
            font-size: 1rem;
            font-weight: 800;
            line-height: 1.3;
        }
        .ride-stop__sub {
            margin: 2px 0 0 30px;
            color: var(--muted);
            font-size: .82rem;
            line-height: 1.35;
        }
        .ride-stats {
            margin-top: 14px;
            border-radius: 14px;
            border: 1px solid var(--line);
            background: rgba(148, 163, 184, 0.05);
            padding: 12px 10px;
        }
        .ride-stats__metrics {
            display: flex;
            align-items: stretch;
        }
        .ride-stats__metric {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 3px;
            text-align: center;
        }
        .ride-stats__metric svg {
            width: 22px;
            height: 22px;
            stroke: var(--muted);
            fill: none;
            stroke-width: 1.7;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .ride-stats__metric strong {
            color: var(--text);
            font-size: 1rem;
            font-weight: 800;
        }
        .ride-stats__metric span {
            color: var(--muted);
            font-size: .7rem;
            font-weight: 500;
        }
        .ride-stats__divider {
            width: 1px;
            background: var(--line);
            margin: 4px 0;
        }
        .ride-stats__bags.is-spaced {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid var(--line);
        }
        .ride-stats__bags-title {
            margin: 0 0 8px;
            color: var(--muted);
            font-size: .82rem;
            font-weight: 600;
            letter-spacing: 0;
        }
        .ride-stats__chips {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .ride-stats__chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            padding: 6px 10px;
            background: rgba(148, 163, 184, 0.12);
            border: 1px solid var(--line);
            color: var(--text);
            font-size: .82rem;
            font-weight: 700;
            line-height: 1.2;
        }
        .ride-stats__chip svg {
            width: 15px;
            height: 15px;
            flex-shrink: 0;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.7;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .ride-card__action {
            font-size: .78rem; font-weight: 700; color: var(--link);
            display: inline-flex; align-items: center; gap: 4px;
        }
        .rides-empty {
            text-align: center; padding: 36px 18px 28px;
            border: 1px dashed var(--line); border-radius: 20px;
            background: color-mix(in oklab, var(--card) 80%, transparent);
        }
        .rides-empty__icon {
            width: 56px; height: 56px; margin: 0 auto 14px; border-radius: 18px;
            display: grid; place-items: center;
            background: rgba(37,99,235,.12); color: var(--link);
        }
        .rides-empty__icon svg {
            width: 28px; height: 28px; stroke: currentColor; fill: none;
            stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round;
        }
        .rides-empty__title { margin: 0 0 6px; font-size: 1.05rem; font-weight: 800; }
        .rides-empty__text { margin: 0 0 16px; color: var(--muted); font-size: .9rem; line-height: 1.45; }
        .rides-loading {
            text-align: center; color: var(--muted); padding: 28px 12px;
            display: flex; flex-direction: column; align-items: center; gap: 10px;
        }
        .ride-card-wrap { display: grid; gap: 8px; }
        .btn-invoice {
            width: 100%;
            padding: 8px 12px;
            font-size: .82rem;
            font-weight: 700;
            line-height: 1.2;
            border-radius: 12px;
            background: var(--blue);
            color: #fff;
            border: none;
        }
        .btn-invoice:disabled { opacity: .55; }
        .live-banner {
            background: var(--card); border: 1px solid var(--line); border-radius: 18px; padding: 16px; margin-bottom: 12px;
        }
        .live-banner h3 { margin: 0 0 6px; font-size: 1.05rem; }
        .live-banner p { margin: 0; color: var(--muted); font-size: .9rem; line-height: 1.4; }
        .live-banner p.ride-card__when,
        .live-banner p.ride-card__stop-value {
            color: var(--text);
            font-weight: 800;
            font-size: 1.12rem;
            line-height: 1.25;
        }
        .live-banner p.ride-stop__main {
            margin: 0 0 0 30px;
            color: var(--text);
            font-size: 1rem;
            font-weight: 800;
            line-height: 1.3;
        }
        .live-banner p.ride-stop__sub {
            margin: 2px 0 0 30px;
            color: var(--muted);
            font-size: .82rem;
            font-weight: 400;
            line-height: 1.35;
        }
        .live-route {
            margin-top: 14px;
            padding-top: 12px;
            border-top: 1px solid var(--line);
        }
        .live-route[hidden] { display: none !important; }
        .live-pickup {
            margin-top: 14px;
            padding-top: 12px;
            border-top: 1px solid var(--line);
        }
        .live-pickup[hidden] { display: none !important; }
        .live-meta + .live-route,
        .live-pickup + .live-route {
            margin-top: 12px;
            padding-top: 0;
            border-top: 0;
        }
        .live-banner .live-meta .ride-card__stop-label,
        .live-banner .live-pickup .ride-card__stop-label {
            margin: 0;
            font-size: .82rem;
            font-weight: 600;
            color: var(--muted);
            letter-spacing: 0;
        }
        .live-banner .live-meta .ride-card__when,
        .live-banner .live-meta .ride-card__stop-value,
        .live-banner .live-pickup .ride-card__stop-value {
            margin: 2px 0 0;
            font-size: 1.12rem;
            font-weight: 800;
            color: var(--text);
            line-height: 1.25;
        }
        .live-eta {
            margin-top: 12px;
        }
        .live-eta[hidden] { display: none !important; }
        .live-banner .live-eta .ride-card__stop-label {
            margin: 0;
            font-size: .82rem;
            font-weight: 600;
            color: var(--muted);
            letter-spacing: 0;
        }
        .live-banner .live-eta .ride-card__stop-value {
            margin: 2px 0 0;
            font-size: .9rem;
            font-weight: 700;
            color: var(--text);
            line-height: 1.3;
        }
        .live-actions {
            display: flex; flex-direction: column; gap: 8px; margin-top: 14px;
        }
        .live-actions[hidden] { display: none !important; }
        .btn-danger {
            background: #dc2626; color: #fff; border: none;
        }
        .btn-danger:disabled { opacity: .55; }
        .btn-outline {
            background: transparent; color: var(--text);
            border: 1px solid var(--line);
        }
        .live-banner p.live-note {
            margin: 0;
            padding-top: 16px;
            font-size: .8rem;
            color: var(--muted);
            line-height: 1.4;
        }
        .live-banner p.live-note.is-refund {
            margin-top: 12px;
            padding: 12px 14px;
            border-radius: 12px;
            background: rgba(6, 78, 59, 0.55);
            border: 1px solid rgba(110, 231, 183, 0.4);
            color: #A7F3D0;
            font-size: .9rem;
            line-height: 1.45;
        }
        .live-note__label {
            display: block;
            margin-bottom: 4px;
            color: #6EE7B7;
            font-size: .82rem;
            font-weight: 700;
            letter-spacing: 0;
        }
        .pickup-confirm--pin {
            text-align: center;
        }
        .pickup-confirm__label {
            margin: 0 0 6px;
            font-size: .78rem;
            font-weight: 700;
            color: var(--muted);
            letter-spacing: .03em;
            text-transform: uppercase;
        }
        .pickup-confirm__pin {
            margin: 0;
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: .35em;
            color: var(--text);
            font-variant-numeric: tabular-nums;
        }
        .pickup-confirm {
            margin-top: 12px;
            padding: 12px 14px;
            border-radius: 12px;
            background: rgba(30, 58, 138, 0.35);
            border: 1px solid rgba(147, 197, 253, 0.45);
        }
        .pickup-confirm__text {
            margin: 0;
            color: var(--text);
            font-size: .95rem;
            font-weight: 600;
            line-height: 1.4;
        }
        .pickup-confirm__actions {
            display: flex;
            gap: 8px;
            margin-top: 10px;
        }
        .pickup-confirm__actions .btn { flex: 1; }
        .kv {
            display: grid;
            gap: 12px;
            margin-top: 12px;
        }
        .kv__row { min-width: 0; }
        .kv__row .ride-card__stop-label { margin: 0; }
        .kv__row .ride-card__stop-value {
            margin: 2px 0 0;
            font-size: .95rem;
            font-weight: 700;
            color: var(--text);
            line-height: 1.3;
        }
        .live-driver-card {
            margin-top: 12px;
            padding: 12px;
            border-radius: 12px;
            border: 1px solid var(--line);
            background: transparent;
            display: grid;
            gap: 10px;
        }
        .live-driver-card__logo {
            width: 120px;
            height: 40px;
            object-fit: contain;
            object-position: left center;
            background: transparent;
            display: block;
        }
        .live-driver-card__row {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .live-driver-card__photo-btn {
            appearance: none;
            border: 0;
            padding: 0;
            margin: 0;
            background: transparent;
            cursor: pointer;
            flex-shrink: 0;
            line-height: 0;
            -webkit-tap-highlight-color: transparent;
        }
        .live-driver-card__avatar {
            width: 56px;
            height: 56px;
            border-radius: 28px;
            object-fit: cover;
            background: var(--nexa-pwa-input-bg, rgba(148,163,184,.12));
            display: block;
        }
        .live-driver-card__meta {
            flex: 1;
            min-width: 0;
        }
        .live-driver-card__name {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            color: var(--text);
        }
        .live-driver-card__sub {
            margin: 2px 0 0;
            font-size: 0.85rem;
            color: var(--muted);
        }
        .live-driver-card__star { color: #F5B301; }
        .live-banner.is-accepted #live-title {
            color: var(--green);
            font-weight: 700;
            margin-bottom: 6px;
        }
        .live-banner.is-accepted #live-text {
            margin: 0;
            color: var(--text);
            font-size: 1rem;
            font-weight: 600;
            line-height: 1.35;
        }
        .live-price-after {
            margin-top: 12px;
        }
        .live-price-after[hidden] { display: none !important; }
        .live-status-updates {
            margin-top: 14px;
        }
        .live-status-updates[hidden] { display: none !important; }
        .live-status-updates__list {
            display: grid;
            gap: 10px;
            margin-top: 8px;
        }
        .live-status-updates__row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .live-status-updates__dot {
            width: 8px;
            height: 8px;
            margin-top: 6px;
            border-radius: 999px;
            background: var(--muted);
            flex-shrink: 0;
        }
        .live-status-updates__dot.is-current { background: var(--green); }
        .live-status-updates__label {
            margin: 0;
            font-size: .95rem;
            font-weight: 700;
            color: var(--text);
            line-height: 1.3;
        }
        .live-status-updates__label.is-current { color: var(--green); }
        .live-status-updates__at {
            margin: 2px 0 0;
            font-size: .8rem;
            color: var(--muted);
        }
        .live-rating {
            margin-top: 14px;
            display: grid;
            gap: 10px;
        }
        .live-rating[hidden] { display: none !important; }
        .live-rating__stars {
            display: flex;
            gap: 6px;
            align-items: center;
        }
        .live-rating__star {
            appearance: none;
            border: 0;
            background: transparent;
            padding: 4px;
            margin: 0;
            font-size: 1.75rem;
            line-height: 1;
            color: var(--muted);
            cursor: pointer;
        }
        .live-rating__star.is-on { color: #F5B301; }
        .live-rating__comment {
            width: 100%;
            min-height: 64px;
            border-radius: 10px;
            border: 1px solid var(--line);
            background: var(--nexa-pwa-input-bg, transparent);
            color: var(--text);
            padding: 10px 12px;
            font: inherit;
            resize: vertical;
        }
        .live-rating__done {
            margin: 0;
            font-size: .9rem;
            color: var(--muted);
            line-height: 1.4;
        }
        .live-driver-card__vehicle {
            width: 72px;
            height: 52px;
            object-fit: contain;
            background: transparent;
            border-radius: 8px;
            display: block;
        }
        .photo-preview-overlay {
            position: fixed;
            inset: 0;
            z-index: 90;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: max(16px, var(--safe-top)) 16px max(16px, var(--safe-bottom));
        }
        .photo-preview-overlay[hidden] { display: none !important; }
        .photo-preview-panel {
            width: min(100%, 420px);
            max-height: min(88dvh, 560px);
            background: #0b0f19;
            border: 1px solid rgba(148,163,184,.18);
            border-radius: 1rem;
            padding: 12px;
            box-shadow: 0 20px 50px rgba(0,0,0,.45);
        }
        html[data-theme="light"] .photo-preview-panel {
            background: #ffffff;
            box-shadow: 0 18px 40px rgba(15,23,42,.18);
        }
        .photo-preview-panel img {
            display: block;
            width: 100%;
            max-height: min(75dvh, 480px);
            object-fit: contain;
            background: transparent;
            border-radius: 12px;
        }
        .toast {
            position: fixed; left: 16px; right: 16px; bottom: calc(100px + var(--safe-bottom));
            background: #facc15;
            border: 1px solid #eab308;
            border-radius: 999px;
            padding: 8px 14px;
            z-index: 50;
            color: #422006;
            font-size: .88rem;
            font-weight: 700;
            line-height: 1.25;
            box-shadow: none;
            text-align: center;
        }
        html[data-theme="light"] .toast {
            background: #fef08a;
            border-color: #facc15;
            color: #713f12;
            box-shadow: none;
        }
        .empty { text-align: center; color: var(--muted); padding: 28px 12px; line-height: 1.45; }
        .pulse {
            width: 10px; height: 10px; border-radius: 50%; background: #60a5fa;
            box-shadow: 0 0 0 0 rgba(96,165,250,.5); animation: pulse 1.6s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(96,165,250,.45); }
            70% { box-shadow: 0 0 0 12px rgba(96,165,250,0); }
            100% { box-shadow: 0 0 0 0 rgba(96,165,250,0); }
        }
        .price-tag { font-size: 1.25rem; font-weight: 800; }
        .payment-options { display: grid; gap: 10px; }
        .payment-option {
            display: flex; gap: 12px; align-items: flex-start;
            padding: 12px; border-radius: 14px; border: 1px solid var(--line);
            background: var(--card); cursor: pointer;
        }
        .payment-option:has(input:checked) {
            border-color: rgba(37,99,235,.45);
            background: rgba(37,99,235,.1);
        }
        .payment-option input { margin-top: 3px; flex-shrink: 0; }
        .payment-option strong { display: block; font-size: .95rem; }
        .payment-option small { display: block; margin-top: 2px; color: var(--muted); font-size: .8rem; line-height: 1.35; }
        .map-error {
            position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
            padding: 16px; text-align: center; color: var(--muted); font-size: .9rem; line-height: 1.4;
            background: var(--map-empty); z-index: 1;
        }
    </style>
</head>
<body>
@include('taxi::partials.pwa-theme', ['section' => 'widget'])

<div id="app"
     data-api-base="{{ $apiBase }}"
     data-login-url="{{ $loginUrl }}"
     data-register-url="{{ $registerUrl }}"
     data-book-url="{{ $bookUrl }}"
     data-quote-url="{{ $quoteUrl }}"
     data-live-url="{{ $liveUrl }}"
     data-address-search-url="{{ $addressSearchUrl }}"
     data-nearby-taxis-url="{{ $nearbyTaxisUrl }}"
     data-taxi-car-url="{{ asset('images/gps/car-sedan.png').'?v='.filemtime(public_path('images/gps/car-sedan.png')) }}"
     data-taxi-van-url="{{ asset('images/gps/car-van.png').'?v='.filemtime(public_path('images/gps/car-van.png')) }}"
     data-taxi-bus-url="{{ asset('images/gps/car-bus.png').'?v='.filemtime(public_path('images/gps/car-bus.png')) }}"
     data-drop-pin-url="{{ asset('assets/img/marker-drop-green.png').'?v='.filemtime(public_path('assets/img/marker-drop-green.png')) }}"
     data-launcher-url="{{ $launcherUrl }}"
     data-maps-key="{{ $googleMapsApiKey }}"
     data-maps-map-id="{{ $googleMapsMapId }}"
     data-center-lat="{{ $googleMapsCenterLat }}"
     data-center-lng="{{ $googleMapsCenterLng }}">

    <section id="screen-welcome" class="screen screen-welcome" hidden>
        <header class="app-chrome">
            <div class="app-logo-bar">
                <img class="app-logo-light" src="{{ $logoLightUrl }}" alt="Nexa Taxi" width="176" height="44" decoding="async">
                <img class="app-logo-dark" src="{{ $logoDarkUrl }}" alt="Nexa Taxi" width="176" height="44" decoding="async">
            </div>
            <div class="app-nav-row">
                <div class="app-nav-start">
                    <button type="button" class="app-nav-back" id="btn-welcome-back" aria-label="Terug">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
                    </button>
                </div>
                <h1 class="app-nav-title">Welkom</h1>
                <div class="app-nav-end" data-theme-slot></div>
            </div>
        </header>
        <div class="content">
            <div class="hero-welcome">
                <p>Boek zonder account, of log in voor je profiel en ritgeschiedenis.</p>
                <div class="stack">
                    <button type="button" class="btn btn-primary" id="btn-guest">Doorgaan zonder account</button>
                    <button type="button" class="btn btn-ghost" id="btn-show-login">Ik heb een account</button>
                </div>
            </div>
        </div>
    </section>

    <section id="screen-auth" class="screen" hidden>
        <header class="app-chrome">
            <div class="app-logo-bar">
                <img class="app-logo-light" src="{{ $logoLightUrl }}" alt="Nexa Taxi" width="176" height="44" decoding="async">
                <img class="app-logo-dark" src="{{ $logoDarkUrl }}" alt="Nexa Taxi" width="176" height="44" decoding="async">
            </div>
            <div class="app-nav-row">
                <div class="app-nav-start">
                    <button type="button" class="app-nav-back" id="btn-auth-back" aria-label="Terug">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
                    </button>
                </div>
                <h1 class="app-nav-title" id="auth-title">Inloggen</h1>
                <div class="app-nav-end" data-theme-slot></div>
            </div>
        </header>
        <div class="content">
            <div class="card" id="auth-login-form">
                <div class="field">
                    <label for="login-email">E-mail</label>
                    <input id="login-email" type="email" autocomplete="username" placeholder="naam@email.nl">
                </div>
                <div class="field">
                    <label for="login-password">Wachtwoord</label>
                    <input id="login-password" type="password" autocomplete="current-password" placeholder="••••••••">
                </div>
                <button type="button" class="btn btn-primary" id="btn-login">Inloggen</button>
                <p class="hint" style="margin-top:12px;text-align:center;color:var(--muted);font-size:.85rem">
                    Nog geen account?
                    <button type="button" class="auth-text-link" id="btn-show-register">Registreren</button>
                </p>
            </div>
            <div class="card" id="auth-register-form" hidden>
                <div class="row2">
                    <div class="field">
                        <label for="reg-first">Voornaam</label>
                        <input id="reg-first" type="text" autocomplete="given-name">
                    </div>
                    <div class="field">
                        <label for="reg-last">Achternaam</label>
                        <input id="reg-last" type="text" autocomplete="family-name">
                    </div>
                </div>
                <div class="field">
                    <label for="reg-email">E-mail</label>
                    <input id="reg-email" type="email" autocomplete="email">
                </div>
                <div class="field">
                    <label for="reg-phone">Telefoon</label>
                    <input id="reg-phone" type="tel" autocomplete="tel" placeholder="06…">
                </div>
                <div class="field">
                    <label for="reg-password">Wachtwoord</label>
                    <input id="reg-password" type="password" autocomplete="new-password">
                </div>
                <div class="field">
                    <label for="reg-password2">Bevestig wachtwoord</label>
                    <input id="reg-password2" type="password" autocomplete="new-password">
                </div>
                <button type="button" class="btn btn-primary" id="btn-register">Account aanmaken</button>
                <p class="hint" style="margin-top:12px;text-align:center;color:var(--muted);font-size:.85rem">
                    Al een account?
                    <button type="button" class="auth-text-link" id="btn-show-login2">Inloggen</button>
                </p>
            </div>
        </div>
    </section>

    <section id="screen-book" class="screen" hidden>
        <header class="app-chrome">
            <div class="app-logo-bar">
                <img class="app-logo-light" src="{{ $logoLightUrl }}" alt="Nexa Taxi" width="176" height="44" decoding="async">
                <img class="app-logo-dark" src="{{ $logoDarkUrl }}" alt="Nexa Taxi" width="176" height="44" decoding="async">
            </div>
            <div class="app-nav-row">
                <div class="app-nav-start">
                    <button type="button" class="app-nav-back" id="btn-switch-role" aria-label="Naar startpagina">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 10.5 12 4l8 6.5"/><path d="M6 10v9h12v-9"/></svg>
                    </button>
                </div>
                <h1 class="app-nav-title">Boeken</h1>
                <div class="app-nav-end" data-theme-slot></div>
            </div>
        </header>
        <div class="content">
            <button type="button" class="active-ride-banner" id="active-ride-banner" hidden>
                <div class="active-ride-banner__text">
                    <p class="active-ride-banner__title" id="active-ride-title">Openstaande rit</p>
                    <p class="active-ride-banner__meta" id="active-ride-meta">Tik om te volgen</p>
                </div>
                <span class="active-ride-banner__cta">Volgen</span>
            </button>
            <div class="card">
                <div class="field field-suggest">
                    <label for="pickup">Ophaaladres</label>
                    <div class="field-suggest-input-wrap has-loc-btn" data-clear-for="pickup">
                        <input id="pickup" type="text" placeholder="Typ een adres en kies een suggestie" autocomplete="off">
                        <button type="button" class="field-loc-btn" id="btn-pickup-locate" aria-label="Bepaal mijn locatie">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                        </button>
                        <button type="button" class="field-clear" id="pickup-clear" aria-label="Ophaaladres wissen" tabindex="-1">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <circle cx="12" cy="12" r="9"></circle>
                                <path d="M9 9l6 6"></path>
                                <path d="M15 9l-6 6"></path>
                            </svg>
                        </button>
                    </div>
                    <ul class="suggestions" id="pickup-suggestions" hidden></ul>
                    <p class="hint" id="pickup-hint">Typ een adres of gebruik je locatie</p>
                    <div class="location-notice" id="pickup-location-notice" hidden>
                        <p class="location-notice__text" id="pickup-location-notice-text"></p>
                        <div class="location-notice__actions">
                            <button type="button" class="location-notice__retry" id="pickup-location-retry">Locatie toestaan</button>
                        </div>
                        <p class="location-notice__help" id="pickup-location-notice-help" hidden></p>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="field field-suggest">
                    <label for="dropoff">Bestemming</label>
                    <div class="field-suggest-input-wrap" data-clear-for="dropoff">
                        <input id="dropoff" type="text" placeholder="Typ een adres en kies een suggestie" autocomplete="off">
                        <button type="button" class="field-clear" id="dropoff-clear" aria-label="Bestemming wissen" tabindex="-1">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <circle cx="12" cy="12" r="9"></circle>
                                <path d="M9 9l6 6"></path>
                                <path d="M15 9l-6 6"></path>
                            </svg>
                        </button>
                    </div>
                    <ul class="suggestions" id="dropoff-suggestions" hidden></ul>
                </div>
            </div>
            <div class="card">
                <div class="field">
                    <label for="pickup-at-display">Ophalen</label>
                    <button type="button" class="pickup-at-btn" id="pickup-at-display" aria-haspopup="dialog">
                        <span class="pickup-at-btn__value" id="pickup-at-label">Kies tijd</span>
                    </button>
                    <input type="hidden" id="pickup-at" value="">
                </div>
            </div>
            <div class="map-wrap">
                <div id="map"></div>
                <div id="map-error" class="map-error" hidden>Kaart laden…</div>
                <div class="map-zoom">
                    <button type="button" id="map-zoom-in" aria-label="Inzoomen">+</button>
                    <button type="button" id="map-zoom-out" aria-label="Uitzoomen">−</button>
                    <button type="button" id="map-zoom-fit" aria-label="Toon route" hidden>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>
                    </button>
                </div>
                <div class="map-status" id="map-fleet-status">Taxi’s zoeken…</div>
            </div>
            <div class="card" id="quote-card" hidden>
                <p class="section-label">Rit &amp; prijs</p>
                <p class="route-summary" id="quote-route">— km · — min</p>
                <div class="price-tag" id="quote-price">—</div>
                <p class="hint" id="quote-meta" style="margin:6px 0 0"></p>
                <p class="quote-warn" id="quote-warn" hidden></p>
            </div>
            <div class="card" id="passengers-card">
                <h2>Passagiers</h2>
                <div class="qty-wrap" id="passenger-stepper">
                    <button type="button" id="passenger-minus" aria-label="Minder passagiers">−</button>
                    <span class="qty-value" id="passenger-qty">1</span>
                    <button type="button" id="passenger-plus" aria-label="Meer passagiers">+</button>
                </div>
                <input type="hidden" id="passengers" value="1">
            </div>
            <div class="card">
                <h2>Bagage</h2>
                <div class="baggage-list">
                    <div class="baggage-row" data-baggage-key="large" data-qty="0" data-max="6">
                        <div class="baggage-row__copy">
                            <strong>Grote ruimbagage</strong>
                            <span>85×55×35 cm</span>
                        </div>
                        <div class="baggage-qty">
                            <button type="button" data-baggage-step="-1" aria-label="Minder">−</button>
                            <span data-baggage-qty>0</span>
                            <button type="button" data-baggage-step="1" aria-label="Meer">+</button>
                        </div>
                    </div>
                    <div class="baggage-row" data-baggage-key="small" data-qty="0" data-max="6">
                        <div class="baggage-row__copy">
                            <strong>Kleine ruimbagage</strong>
                            <span>55×45×25 cm</span>
                        </div>
                        <div class="baggage-qty">
                            <button type="button" data-baggage-step="-1" aria-label="Minder">−</button>
                            <span data-baggage-qty>0</span>
                            <button type="button" data-baggage-step="1" aria-label="Meer">+</button>
                        </div>
                    </div>
                    <div class="baggage-row" data-baggage-key="hand" data-qty="0" data-max="6">
                        <div class="baggage-row__copy">
                            <strong>Handbagage</strong>
                            <span>Handtas, rugzak, etc.</span>
                        </div>
                        <div class="baggage-qty">
                            <button type="button" data-baggage-step="-1" aria-label="Minder">−</button>
                            <span data-baggage-qty>0</span>
                            <button type="button" data-baggage-step="1" aria-label="Meer">+</button>
                        </div>
                    </div>
                    <div class="baggage-row" data-baggage-key="wheelchair" data-baggage-special="1" data-qty="0" data-max="2">
                        <div class="baggage-row__copy">
                            <strong>Opvouwbare rolstoel</strong>
                            <span>Speciale bagage</span>
                        </div>
                        <div class="baggage-qty">
                            <button type="button" data-baggage-step="-1" aria-label="Minder">−</button>
                            <span data-baggage-qty>0</span>
                            <button type="button" data-baggage-step="1" aria-label="Meer">+</button>
                        </div>
                    </div>
                    <div class="baggage-row" data-baggage-key="pets" data-baggage-special="1" data-qty="0" data-max="2">
                        <div class="baggage-row__copy">
                            <strong>Huisdieren</strong>
                            <span>Optioneel</span>
                        </div>
                        <div class="baggage-qty">
                            <button type="button" data-baggage-step="-1" aria-label="Minder">−</button>
                            <span data-baggage-qty>0</span>
                            <button type="button" data-baggage-step="1" aria-label="Meer">+</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <h2>Jouw gegevens</h2>
                <div class="row2">
                    <div class="field">
                        <label for="first-name">Voornaam</label>
                        <input id="first-name" type="text" autocomplete="given-name">
                    </div>
                    <div class="field">
                        <label for="last-name">Achternaam</label>
                        <input id="last-name" type="text" autocomplete="family-name">
                    </div>
                </div>
                <div class="field">
                    <label for="phone">Telefoon</label>
                    <input id="phone" type="tel" autocomplete="tel" placeholder="06…">
                </div>
                <div class="field" id="email-field">
                    <label for="email">E-mail</label>
                    <input id="email" type="email" autocomplete="email">
                </div>
                <div class="field">
                    <label for="remarks">Opmerking</label>
                    <textarea id="remarks" rows="2" placeholder="Bijv. bakfiets, hulp bij instappen…"></textarea>
                </div>
                <p class="profile-hint">Deze gegevens kun je ook in je profiel invullen, waarna ze hier automatisch worden ingevuld.</p>
            </div>
            <div class="card" id="payment-card" hidden>
                <h2>Betaling</h2>
                <p class="live-note" id="payment-note" style="margin:0">Je betaalt nu online via iDEAL of kaart. Daarna zoeken we een taxi.</p>
                <input type="hidden" name="payment_method" value="booking" id="pay-booking">
            </div>
            <button type="button" class="btn btn-primary" id="btn-book">Taxi aanvragen</button>
        </div>
    </section>

    <section id="screen-live" class="screen" hidden>
        <header class="app-chrome">
            <div class="app-logo-bar">
                <img class="app-logo-light" src="{{ $logoLightUrl }}" alt="Nexa Taxi" width="176" height="44" decoding="async">
                <img class="app-logo-dark" src="{{ $logoDarkUrl }}" alt="Nexa Taxi" width="176" height="44" decoding="async">
            </div>
            <div class="app-nav-row">
                <div class="app-nav-start">
                    <button type="button" class="app-nav-back" id="btn-live-back" aria-label="Terug">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
                    </button>
                </div>
                <h1 class="app-nav-title">Je rit</h1>
                <div class="app-nav-end" data-theme-slot></div>
            </div>
        </header>
        <div class="content">
            <div class="map-wrap"><div id="live-map"></div></div>
            <div id="live-banner-park">
            <div class="live-banner" id="live-banner">
                <div class="status-pill is-searching" id="live-pill">Zoeken…</div>
                <div class="searching-title" id="live-searching-title" hidden>
                    <span class="searching-title__dot" aria-hidden="true"></span>
                    <span class="searching-title__text js-searching-typed" id="live-searching-typed"></span>
                </div>
                <h3 id="live-title" style="margin-top:12px">We zoeken een taxi</h3>
                <p id="live-text">Je rit is aangeboden aan aangesloten taxibedrijven in de buurt.</p>
                <div id="live-pickup-confirm" hidden></div>
                <div class="live-meta" id="live-meta-wrap" hidden>
                    <div class="live-meta__row">
                        <div id="live-pickup-wrap">
                            <p class="ride-card__stop-label">Datum</p>
                            <p class="ride-card__when" id="live-pickup-at">—</p>
                        </div>
                        <div id="live-price-wrap" hidden>
                            <p class="ride-card__stop-label">Prijs</p>
                            <p class="ride-card__stop-value" id="live-price">—</p>
                        </div>
                    </div>
                </div>
                <div id="live-route" hidden></div>
                <div id="live-stats" hidden></div>
                <div class="live-price-after" id="live-price-after" hidden>
                    <p class="ride-card__stop-label">Prijs</p>
                    <p class="ride-card__stop-value" id="live-price-after-value">—</p>
                </div>
                <div class="live-eta" id="live-eta-wrap" hidden>
                    <p class="ride-card__stop-label">Verwachte aankomsttijd</p>
                    <p class="ride-card__stop-value" id="live-eta-value">—</p>
                </div>
                <div class="live-driver-card" id="live-driver-card" hidden></div>
                <div class="kv" id="live-details" hidden></div>
                <div class="live-status-updates" id="live-status-updates" hidden></div>
                <p class="live-note" id="live-note" hidden></p>
                <div class="live-rating" id="live-rating" hidden>
                    <p class="ride-card__stop-label" id="live-rating-label">Beoordeel je rit</p>
                    <div class="live-rating__stars" id="live-rating-stars" role="group" aria-label="Sterren">
                        <button type="button" class="live-rating__star" data-stars="1" aria-label="1 ster">★</button>
                        <button type="button" class="live-rating__star" data-stars="2" aria-label="2 sterren">★</button>
                        <button type="button" class="live-rating__star" data-stars="3" aria-label="3 sterren">★</button>
                        <button type="button" class="live-rating__star" data-stars="4" aria-label="4 sterren">★</button>
                        <button type="button" class="live-rating__star" data-stars="5" aria-label="5 sterren">★</button>
                    </div>
                    <textarea id="live-rating-comment" class="live-rating__comment" rows="2" placeholder="Optionele opmerking" hidden></textarea>
                    <button type="button" class="btn btn-primary" id="btn-live-rate" hidden>Beoordeling versturen</button>
                    <p class="live-rating__done" id="live-rating-done" hidden></p>
                </div>
                <div class="live-actions" id="live-actions" hidden>
                    <button type="button" class="btn btn-primary" id="btn-live-pay" hidden>Opnieuw betalen</button>
                    <button type="button" class="btn" id="btn-live-wait" hidden>Blijven wachten</button>
                    <button type="button" class="btn btn-danger" id="btn-live-cancel" hidden>Rit annuleren</button>
                    <button type="button" class="btn btn-invoice" id="btn-live-invoice" hidden>Factuur downloaden</button>
                </div>
            </div>
            </div>
        </div>
    </section>

    <section id="screen-rides" class="screen" hidden>
        <header class="app-chrome">
            <div class="app-logo-bar">
                <img class="app-logo-light" src="{{ $logoLightUrl }}" alt="Nexa Taxi" width="176" height="44" decoding="async">
                <img class="app-logo-dark" src="{{ $logoDarkUrl }}" alt="Nexa Taxi" width="176" height="44" decoding="async">
            </div>
            <div class="app-nav-row">
                <div class="app-nav-start">
                    <button type="button" class="app-nav-back" id="btn-rides-home" aria-label="Naar startpagina">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 10.5 12 4l8 6.5"/><path d="M6 10v9h12v-9"/></svg>
                    </button>
                </div>
                <h1 class="app-nav-title">Ritten</h1>
                <div class="app-nav-end">
                    <button type="button" class="app-nav-back" id="btn-rides-book" aria-label="Terug naar ritten" hidden>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
                    </button>
                    <div data-theme-slot></div>
                </div>
            </div>
        </header>
        <div class="content" id="rides-list">
            <div class="empty">Nog geen ritten.</div>
        </div>
    </section>

    <section id="screen-profile" class="screen" hidden>
        <header class="app-chrome">
            <div class="app-logo-bar">
                <img class="app-logo-light" src="{{ $logoLightUrl }}" alt="Nexa Taxi" width="176" height="44" decoding="async">
                <img class="app-logo-dark" src="{{ $logoDarkUrl }}" alt="Nexa Taxi" width="176" height="44" decoding="async">
            </div>
            <div class="app-nav-row">
                <div class="app-nav-start">
                    <button type="button" class="app-nav-back" id="btn-profile-back" aria-label="Naar startpagina">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 10.5 12 4l8 6.5"/><path d="M6 10v9h12v-9"/></svg>
                    </button>
                </div>
                <h1 class="app-nav-title">Profiel</h1>
                <div class="app-nav-end" data-theme-slot></div>
            </div>
        </header>
        <div class="content">
            <div class="card" id="profile-guest" hidden>
                <p style="margin:0 0 12px;color:var(--muted)">Je boekt nu zonder account. Maak een profiel aan om gegevens te bewaren en ritten terug te vinden.</p>
                <button type="button" class="btn btn-primary" id="btn-profile-login">Inloggen of registreren</button>
            </div>
            <div class="card" id="profile-view" hidden>
                <div class="profile-card-head">
                    <h2>Gegevens</h2>
                    <button type="button" class="icon-btn" id="btn-edit-profile" aria-label="Profiel bewerken" title="Bewerken">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 20h9"/>
                            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>
                        </svg>
                    </button>
                </div>
                <dl class="profile-read">
                    <div class="profile-read-row">
                        <dt>Voornaam</dt>
                        <dd id="view-first">—</dd>
                    </div>
                    <div class="profile-read-row">
                        <dt>Achternaam</dt>
                        <dd id="view-last">—</dd>
                    </div>
                    <div class="profile-read-row">
                        <dt>Telefoon</dt>
                        <dd id="view-phone">—</dd>
                    </div>
                    <div class="profile-read-row">
                        <dt>E-mail</dt>
                        <dd id="view-email">—</dd>
                    </div>
                </dl>
            </div>
            <div class="card" id="profile-form" hidden>
                <div class="row2">
                    <div class="field">
                        <label for="prof-first">Voornaam</label>
                        <input id="prof-first" type="text" autocomplete="given-name">
                    </div>
                    <div class="field">
                        <label for="prof-last">Achternaam</label>
                        <input id="prof-last" type="text" autocomplete="family-name">
                    </div>
                </div>
                <div class="field">
                    <label for="prof-phone">Telefoon</label>
                    <input id="prof-phone" type="tel" autocomplete="tel">
                </div>
                <div class="field">
                    <label>E-mail</label>
                    <input id="prof-email" type="email" readonly>
                    <p class="hint" id="prof-email-hint">E-mailadres kun je hier niet wijzigen.</p>
                </div>
                <p class="profile-hint" id="profile-saved" hidden>Opgeslagen.</p>
                <div class="profile-form-actions">
                    <button type="button" class="btn btn-ghost" id="btn-cancel-profile">Annuleren</button>
                    <button type="button" class="btn btn-primary" id="btn-save-profile">Opslaan</button>
                </div>
            </div>
            <div class="card" id="profile-theme">
                <h2>Weergave</h2>
                <p class="profile-hint" style="margin-top:0">Standaard kun je light of dark kiezen. Systeem volgt de instelling van je apparaat.</p>
                <button type="button" class="theme-row" data-theme-choice="system">
                    <span class="theme-radio" aria-hidden="true"></span>
                    <span><strong>Systeem</strong><span>Volgt de telefooninstelling</span></span>
                </button>
                <button type="button" class="theme-row" data-theme-choice="light">
                    <span class="theme-radio" aria-hidden="true"></span>
                    <span><strong>Licht</strong><span>Altijd lichte weergave</span></span>
                </button>
                <button type="button" class="theme-row" data-theme-choice="dark">
                    <span class="theme-radio" aria-hidden="true"></span>
                    <span><strong>Donker</strong><span>Altijd donkere weergave</span></span>
                </button>
            </div>
            <div id="profile-account-actions" hidden>
                <button type="button" class="btn btn-ghost" id="btn-logout" style="margin-top:12px">Uitloggen</button>
                <button type="button" class="btn btn-ghost" id="btn-profile-switch" style="margin-top:10px">Andere rol kiezen</button>
            </div>
        </div>
    </section>

    <nav class="tabs" id="app-tabbar" hidden>
        <button type="button" class="tab active" data-tab="book" aria-label="Boeken">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            <span>Boeken</span>
        </button>
        <button type="button" class="tab" data-tab="rides" id="tab-rides" aria-label="Ritten">
            <span class="tab-badge" id="tab-rides-badge" aria-hidden="true">1</span>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 17h8"/><path d="M5 17l1.5-8h11L19 17"/><path d="M7 17a1.5 1.5 0 1 0 0 .01"/><path d="M17 17a1.5 1.5 0 1 0 0 .01"/><path d="M7 9l1-3h8l1 3"/></svg>
            <span>Ritten</span>
        </button>
        <button type="button" class="tab" data-tab="profile" aria-label="Profiel">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 19c1.8-3.2 4.2-4.5 7-4.5s5.2 1.3 7 4.5"/></svg>
            <span>Profiel</span>
        </button>
    </nav>

    <div id="toast" class="toast" hidden></div>

    <div id="photo-preview-overlay" class="photo-preview-overlay" hidden>
        <div class="photo-preview-panel" role="dialog" aria-modal="true" aria-label="Vergrote foto">
            <img id="photo-preview-image" src="" alt="">
        </div>
    </div>

    <div id="pickup-datetime-overlay" class="dt-overlay" hidden>
        <div class="dt-sheet" role="dialog" aria-modal="true" aria-labelledby="dt-sheet-title">
            <h2 class="dt-sheet__title" id="dt-sheet-title">Ophaalmoment</h2>
            <div class="dt-quick" id="dt-quick">
                <button type="button" class="dt-chip" data-quick="10" aria-pressed="false">Over 10 min</button>
                <button type="button" class="dt-chip" data-quick="30" aria-pressed="false">Over een half uur</button>
                <button type="button" class="dt-chip" data-quick="60" aria-pressed="false">Over 1 uur</button>
            </div>
            <div class="dt-days" id="dt-days"></div>
            <div class="dt-time">
                <div class="dt-col" id="dt-hours" aria-label="Uren"></div>
                <span class="dt-time__sep" aria-hidden="true">:</span>
                <div class="dt-col" id="dt-minutes" aria-label="Minuten"></div>
            </div>
            <p class="dt-preview" id="dt-preview"></p>
            <button type="button" class="btn btn-primary" id="dt-confirm">Bevestigen</button>
        </div>
    </div>
</div>
<script>
(function () {
    var chrome = document.getElementById('nexa-pwa-chrome-actions');
    if (!chrome) return;

    function activeSlot() {
        var screens = document.querySelectorAll('#app > .screen');
        for (var i = 0; i < screens.length; i++) {
            if (!screens[i].hidden) {
                return screens[i].querySelector('[data-theme-slot]');
            }
        }
        return document.querySelector('[data-theme-slot]');
    }

    function mountTheme() {
        var slot = activeSlot();
        if (!slot) return;
        if (chrome.parentElement !== slot) {
            slot.appendChild(chrome);
        }
    }

    var mo = new MutationObserver(mountTheme);
    document.querySelectorAll('#app > .screen').forEach(function (screen) {
        mo.observe(screen, { attributes: true, attributeFilter: ['hidden'] });
    });
    mountTheme();
    window.addEventListener('nexa-customer-screen', mountTheme);
})();
</script>
<script src="{{ asset('assets/js/taxi-customer-app.js') }}?v=55" defer></script>
</body>
</html>
