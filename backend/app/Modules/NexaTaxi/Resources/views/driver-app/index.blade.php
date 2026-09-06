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
    <link rel="preconnect" href="https://maps.googleapis.com">
    <link rel="preconnect" href="https://maps.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="https://maps.googleapis.com">
    <link rel="manifest" href="{{ \Illuminate\Support\Facades\Route::has('taxi.chauffeur.manifest') ? route('taxi.chauffeur.manifest') : url('/taxi/chauffeur/manifest.webmanifest') }}">
    <link rel="icon" href="{{ $faviconUrl }}" type="{{ $faviconType }}">
    <link rel="shortcut icon" href="{{ $faviconUrl }}" type="{{ $faviconType }}">
    <link rel="apple-touch-icon" href="{{ $faviconUrl }}">
    <title>Chauffeur – Nexa Taxi</title>
    @include('taxi::partials.pwa-theme', ['section' => 'styles'])
    @include('taxi::partials.pwa-accent', ['section' => 'styles'])
    @include('taxi::partials.ride-alert-tone', ['section' => 'styles'])
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
            --ride-taxi: #f97316;
            --ride-taxi-rgb: 249, 115, 22;
            --ride-contract: #3b82f6;
            --ride-contract-rgb: 59, 130, 246;
            --green: #22c55e;
            --red: #ef4444;
            --line: rgba(255,255,255,0.08);
            --price: #ffffff;
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
            --price: #ffffff;
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
            --price: #0f172a;
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
        [hidden] {
            display: none !important;
        }
        html, body { height: 100%; margin: 0; }
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg);
            color: var(--text);
            overscroll-behavior: none;
        }
        body.driver-dialog-open {
            overflow: hidden;
        }
        body.driver-dialog-open #screen-dispatch,
        body.driver-accept-in-flight #screen-dispatch {
            pointer-events: none;
        }
        #nosleep-media-wrap,
        #nosleep-audio,
        #nosleep-canvas {
            position: fixed;
            left: -100vw;
            top: 0;
            width: 0;
            height: 0;
            overflow: hidden;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            z-index: -1;
        }
        #nosleep-media-wrap video {
            width: 1px;
            height: 1px;
            max-width: 1px;
            max-height: 1px;
        }
        .banner-ios-awake {
            position: relative;
            background: rgba(234, 179, 8, 0.12);
            border: 1px solid rgba(234, 179, 8, 0.35);
            color: #fde68a;
            border-radius: 0.75rem;
            padding: 0.75rem 2.25rem 0.75rem 1rem;
            font-size: 0.8125rem;
            margin-bottom: 1rem;
            line-height: 1.45;
        }
        #absence-alert-banner .banner-ride-link {
            display: inline-block;
            margin: 0.4rem 0 0;
            padding: 0;
            border: none;
            background: none;
            color: inherit;
            font: inherit;
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 0.15em;
            cursor: pointer;
            -webkit-appearance: none;
        }
        #absence-alert-banner .banner-ride-link:hover,
        #absence-alert-banner .banner-ride-link:focus-visible {
            text-decoration-thickness: 2px;
        }
        .offer-card.is-pickup-alert-target {
            box-shadow: 0 0 0 2px var(--orange);
        }
        #app { height: 100%; min-height: 100%; display: flex; flex-direction: column; }
        .app-top-chrome {
            flex-shrink: 0;
            background: var(--chrome);
        }
        .app-top-chrome:not(:has(#guide-hint:not([hidden]), #install-app-hint:not([hidden]))) {
            display: none;
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
        #screen-dispatch.is-active {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
            overflow: hidden;
            padding: 0 0 calc(var(--bottom-nav-h) + var(--safe-bottom));
        }
        .dispatch-top {
            flex: 0 0 auto;
            padding: calc(0.65rem + var(--safe-top)) 1rem 0.1rem;
            background: var(--chrome);
            border-bottom: 1px solid var(--line);
        }
        .driver-app-header {
            position: relative;
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            align-items: center;
            gap: 0.5rem;
            min-height: 2.75rem;
            margin-bottom: 0.25rem;
        }
        .driver-app-header__online {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text);
            z-index: 1;
            justify-self: start;
        }
        .driver-app-header__online .switch {
            width: 2.15rem;
            height: 1.25rem;
        }
        .driver-app-header__online .switch::after {
            top: 0.15rem;
            left: 0.15rem;
            width: 0.95rem;
            height: 0.95rem;
        }
        .driver-app-header__online .switch.is-on::after {
            transform: translateX(0.9rem);
        }
        .driver-vehicle-row {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin: 0.35rem 0 0.15rem;
        }
        .driver-vehicle-row label {
            font-size: 0.75rem;
            color: var(--muted);
            flex-shrink: 0;
        }
        .driver-vehicle-row select {
            flex: 1;
            min-width: 0;
            background: var(--card-elevated);
            color: var(--text);
            border: 1px solid var(--line);
            border-radius: 0.6rem;
            padding: 0.4rem 0.55rem;
            font-size: 0.8125rem;
        }
        .driver-app-header__center {
            grid-column: 2;
            justify-self: center;
            display: flex;
            align-items: center;
            min-width: 0;
        }
        .driver-app-header__end {
            grid-column: 3;
            width: 2.25rem;
            height: 2.25rem;
            flex-shrink: 0;
            pointer-events: none;
        }
        .driver-app-header__active-ride {
            width: 2.75rem;
            height: 2.75rem;
            border: none;
            border-radius: 0.75rem;
            background: transparent;
            color: var(--green);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            padding: 0;
            animation: active-ride-flicker 1.15s ease-in-out infinite;
        }
        .driver-app-header__active-ride svg {
            width: 1.85rem;
            height: 1.85rem;
        }
        .driver-app-header__active-ride:active {
            background: transparent;
            opacity: 0.85;
        }
        @keyframes active-ride-flicker {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.28; }
        }
        .driver-section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin: 0.35rem 0 0.85rem;
            padding-top: 0.7rem;
        }
        .driver-section-head h2 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 500;
            color: var(--text);
        }
        .driver-section-head__bell,
        .driver-section-head__icon {
            width: 1.25rem;
            height: 1.25rem;
            flex-shrink: 0;
            color: var(--orange);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .driver-section-head__bell svg,
        .driver-section-head__icon svg {
            width: 1.25rem;
            height: 1.25rem;
        }
        .driver-section-head__today {
            flex-shrink: 0;
            min-height: auto;
            width: auto;
            padding: 0.35rem 0.7rem;
            border-radius: 0.65rem;
            border: 1px solid var(--line);
            background: var(--card-elevated);
            color: var(--text);
            font-size: 0.78rem;
            font-weight: 650;
            cursor: pointer;
            touch-action: manipulation;
        }
        .driver-section-head__today:disabled {
            opacity: 0.35;
            cursor: default;
        }
        .driver-section-head__today:not(:disabled):active {
            opacity: 0.85;
        }
        .driver-tab-panel[hidden] { display: none !important; }
        .driver-bottom-nav {
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
        #screen-dispatch.is-active > .driver-bottom-nav {
            display: flex;
        }
        .driver-bottom-nav__btn {
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
        .driver-bottom-nav__btn svg {
            width: 1.45rem;
            height: 1.45rem;
        }
        .driver-bottom-nav__btn.is-active {
            color: var(--orange);
        }
        .driver-bottom-nav__btn:active {
            background: rgba(var(--accent-rgb), 0.08);
        }
        .dispatch-banners {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 0.65rem;
        }
        .dispatch-banners .banner-ios-awake,
        .dispatch-banners .banner-notifications-hint,
        .dispatch-banners .banner-guide-hint,
        .dispatch-banners .banner-inactive,
        .dispatch-banners #notifications-feedback {
            margin-bottom: 0;
        }
        .dispatch-banners #notifications-feedback {
            margin-top: 0;
        }
        .dispatch-scroll {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            padding: 0 1rem 0.35rem;
        }
        #screen-dispatch.is-nav-tab .dispatch-scroll {
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
        html[data-theme="light"] .navigation-map {
            background: #e2e8f0;
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
            max-height: none;
            overflow: visible;
        }
        .navigation-stops li {
            display: flex;
            align-items: flex-start;
            gap: 0.45rem;
            padding: 0.5rem 0;
            font-size: 0.88rem;
            line-height: 1.35;
            color: var(--text);
            border-bottom: 1px solid var(--line);
        }
        .navigation-stops li:last-child {
            border-bottom: 0;
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
        .navigation-route.is-done {
            opacity: 0.45;
        }
        .navigation-route.is-done .navigation-route__name,
        .navigation-route.is-done strong {
            text-decoration: line-through;
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
        .navigation-stops__meta {
            color: var(--muted);
            font-size: 0.72rem;
        }
        #tab-panel-navigation #btn-start-navigation {
            width: 100%;
            margin: 0;
        }
        #tab-panel-navigation #btn-start-navigation:disabled {
            opacity: 0.45;
        }
        #offer-strip,
        #active-ride-strip {
            margin-bottom: 0.5rem;
        }
        #active-ride-strip.is-contract-ride #btn-pay-ride,
        #active-ride-strip.is-contract-ride #btn-send-invoice,
        #active-ride-strip.is-contract-group-ride #btn-complete-ride,
        #active-ride-strip.is-contract-ride #payment-ride-error {
            display: none !important;
        }
        #active-ride-strip.active-ride-card {
            margin-bottom: 0.85rem;
        }
        #active-ride-strip .active-ride-hint {
            margin: -0.15rem 0 0.85rem;
            font-size: 0.8125rem;
        }
        .active-ride-nav-btn {
            width: 2.5rem;
            height: 2.5rem;
            border: none;
            border-radius: 0.75rem;
            background: rgba(var(--accent-rgb), 0.18);
            color: var(--orange);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            cursor: pointer;
            flex-shrink: 0;
        }
        .active-ride-nav-btn svg {
            width: 1.35rem;
            height: 1.35rem;
        }
        .active-ride-nav-btn:active {
            opacity: 0.85;
        }
        #active-ride-strip .active-ride-actions {
            margin-top: 0.85rem;
        }
        #active-ride-strip .active-ride-actions:not(:has(button:not([hidden]))) {
            display: none;
        }
        #active-ride-strip .active-ride-actions .btn[hidden] {
            display: none !important;
        }
        #btn-complete-ride,
        #btn-pay-ride,
        #btn-send-invoice,
        #btn-start-return,
        #btn-release-return {
            width: 100%;
            margin-top: 0;
            min-height: 2.55rem;
            font-size: 0.82rem;
            border-radius: 0.55rem;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }
        #btn-pay-ride { background: var(--orange); color: var(--accent-on, #fff); }
        #btn-pay-ride:hover { background: var(--orange-hover); color: var(--accent-on, #fff); }
        #btn-pay-ride.is-paid {
            background: #64748b;
            color: #fff;
            cursor: not-allowed;
        }
        #payment-ride-error {
            margin-top: 0.75rem;
            padding: 0.625rem 0.75rem;
            border-radius: 0.5rem;
            background: rgba(239, 68, 68, 0.12);
            color: #f87171;
            font-size: 0.9375rem;
            line-height: 1.4;
        }
        #payment-panel,
        #invoice-panel {
            display: none;
            flex-direction: column;
            gap: 0.75rem;
            padding-bottom: 0.5rem;
        }
        #payment-panel.is-open,
        #invoice-panel.is-open {
            display: flex;
        }
        #payment-panel .driver-section-head,
        #invoice-panel .driver-section-head {
            align-items: center;
        }
        #payment-panel .driver-section-head__close,
        #invoice-panel .driver-section-head__close {
            margin-left: auto;
            width: auto;
            min-height: 2.25rem;
            padding: 0.35rem 0.75rem;
            flex-shrink: 0;
        }
        #payment-panel .payment-amount-wrap {
            display: flex;
            align-items: center;
            border-radius: 0.75rem;
            border: 1px solid var(--nexa-pwa-input-border, var(--line));
            background: var(--card);
            margin: 0.5rem 0 1rem;
        }
        #payment-panel .payment-amount-wrap:focus-within {
            border-color: rgba(37, 99, 235, 0.6);
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.25);
        }
        #payment-panel .payment-amount-prefix {
            padding: 0.75rem 0 0.75rem 1rem;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--muted);
            flex-shrink: 0;
            line-height: 1;
        }
        #payment-panel .payment-amount-input {
            width: 100%;
            flex: 1;
            min-width: 0;
            font-size: 1.5rem;
            padding: 0.75rem 1rem 0.75rem 0.35rem;
            border: none;
            border-radius: 0;
            background: transparent;
            color: var(--text);
            margin: 0;
        }
        #payment-panel .payment-amount-input:focus {
            outline: none;
        }
        #payment-panel .payment-actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        #payment-panel .payment-actions .btn {
            width: 100%;
            min-height: 3.25rem;
            font-size: 1.0625rem;
            margin: 0;
        }
        #payment-panel #btn-cash-paid {
            background: #0d9488;
            color: #fff;
        }
        #payment-panel #btn-cash-paid:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }
        #btn-send-invoice {
            background: #2563eb;
            color: #fff;
            border: 1.5px solid #2563eb;
        }
        #btn-send-invoice:disabled,
        #btn-send-invoice.is-disabled,
        #btn-complete-ride:disabled,
        #btn-complete-ride.is-disabled {
            background: transparent;
            color: #94a3b8;
            border: 1.5px solid #64748b;
            cursor: not-allowed;
            opacity: 1;
            pointer-events: none;
        }
        #btn-send-invoice:disabled:hover,
        #btn-send-invoice.is-disabled:hover,
        #btn-complete-ride:disabled:hover,
        #btn-complete-ride.is-disabled:hover {
            background: transparent;
            color: #94a3b8;
            border-color: #64748b;
        }
        #invoice-panel .invoice-field-input,
        .driver-field-input {
            width: 100%;
            font-size: 1rem;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            border: 1px solid var(--nexa-pwa-input-border, rgba(255,255,255,0.15));
            background: var(--nexa-pwa-input-bg, var(--card));
            color: var(--text);
            margin: 0.35rem 0 1rem;
            min-height: 3rem;
        }
        #invoice-panel .invoice-field-input:focus,
        .driver-field-input:focus {
            outline: none;
            border-color: rgba(37, 99, 235, 0.6);
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.25);
        }
        textarea.driver-field-input {
            min-height: 5rem;
            resize: vertical;
            line-height: 1.4;
        }
        #invoice-send-status {
            margin-top: 0.75rem;
            font-size: 0.9375rem;
            color: #86efac;
            text-align: center;
        }
        #invoice-send-status.is-error { color: #fca5a5; }
        .driver-dialog {
            position: fixed;
            inset: 0;
            z-index: 240;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
            padding-top: calc(1.25rem + var(--safe-top));
            padding-bottom: calc(1.25rem + var(--safe-bottom));
            pointer-events: none;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s ease, visibility 0.2s ease;
        }
        .driver-dialog.is-open {
            pointer-events: auto;
            opacity: 1;
            visibility: visible;
        }
        .driver-dialog.driver-dialog--instant,
        .driver-dialog.driver-dialog--instant .driver-dialog__panel {
            transition: none !important;
        }
        .driver-dialog__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(2, 6, 23, 0.72);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }
        .driver-dialog__panel {
            position: relative;
            width: 100%;
            max-width: 22rem;
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 1rem;
            padding: 1.5rem 1.25rem 1.25rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.55);
            transform: translateY(0.75rem) scale(0.96);
            transition: transform 0.22s ease;
            text-align: center;
        }
        .driver-dialog.is-open .driver-dialog__panel {
            transform: translateY(0) scale(1);
        }
        .driver-dialog__icon {
            width: 3.25rem;
            height: 3.25rem;
            margin: 0 auto 1rem;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            background: rgba(13, 148, 136, 0.2);
            border: 1px solid rgba(45, 212, 191, 0.35);
        }
        .driver-dialog__icon.is-success {
            background: rgba(22, 163, 74, 0.2);
            border-color: rgba(74, 222, 128, 0.4);
            color: #86efac;
        }
        .driver-dialog__icon.is-error {
            background: rgba(220, 38, 38, 0.18);
            border-color: rgba(248, 113, 113, 0.4);
            color: #fecaca;
        }
        .driver-dialog__icon.is-warn {
            background: rgba(245, 158, 11, 0.18);
            border-color: rgba(251, 191, 36, 0.4);
            color: #fcd34d;
        }
        .driver-dialog__title {
            margin: 0 0 0.5rem;
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        .driver-dialog__amount {
            margin: 0 0 0.75rem;
            font-size: 2rem;
            font-weight: 700;
            color: #5eead4;
            letter-spacing: -0.03em;
            line-height: 1.15;
        }
        .driver-dialog__text {
            margin: 0 0 1.25rem;
            font-size: 0.9375rem;
            color: var(--muted);
            line-height: 1.45;
        }
        .driver-dialog__actions {
            display: flex;
            flex-direction: column;
            gap: 0.625rem;
        }
        .driver-dialog__actions .btn {
            min-height: 3rem;
        }
        .driver-dialog__actions .btn-cash-confirm {
            background: #0d9488;
            color: #fff;
        }
        .driver-dialog__actions .btn-cash-confirm:disabled {
            opacity: 0.6;
            cursor: wait;
        }
        #pickup-adjust-dialog #pickup-adjust-edit-step {
            text-align: left;
            min-width: 0;
        }
        #pickup-adjust-dialog .driver-dialog__panel {
            overflow: auto;
            max-height: min(90dvh, 36rem);
            min-width: 0;
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 0.65rem;
            padding: 1rem;
            box-shadow: none;
        }
        #pickup-adjust-dialog .driver-dialog__title {
            font-size: 1.125rem;
            margin-bottom: 0.65rem;
        }
        #pickup-adjust-dialog .driver-dialog__text {
            margin-bottom: 0.85rem;
            font-size: 0.875rem;
        }
        #pickup-adjust-dialog .driver-dialog__actions {
            gap: 0.5rem;
        }
        #pickup-adjust-dialog .driver-dialog__actions .btn {
            min-height: 2.55rem;
            text-transform: uppercase;
            font-size: 0.82rem;
            border-radius: 0.55rem;
            letter-spacing: 0.03em;
        }
        #pickup-adjust-dialog #pickup-adjust-edit-step .driver-dialog__title {
            text-align: center;
            margin-bottom: 1rem;
        }
        #pickup-adjust-dialog #pickup-adjust-edit-step label {
            display: block;
            font-size: 0.8125rem;
            color: var(--muted);
            margin: 0 0 0.35rem;
        }
        #pickup-adjust-dialog .pickup-adjust-datetime-fields {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.8fr);
            gap: 0.625rem;
            margin-bottom: 1rem;
            min-width: 0;
        }
        #pickup-adjust-dialog .pickup-adjust-datetime-field {
            min-width: 0;
        }
        #pickup-adjust-dialog .pickup-adjust-datetime-input {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            box-sizing: border-box;
            margin: 0;
            font-size: 0.9375rem;
            padding: 0.625rem 0.625rem;
            min-height: 2.55rem;
        }
        #pickup-adjust-dialog #pickup-adjust-edit-step .driver-dialog__actions {
            margin-top: 0.25rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }
        #pickup-adjust-dialog #pickup-adjust-current {
            margin: 0 0 0.75rem;
            font-size: 0.9375rem;
            font-weight: 500;
            color: var(--accent-muted);
            line-height: 1.4;
        }
        #pickup-adjust-dialog #pickup-adjust-current .pickup-adjust-current__value {
            display: block;
            margin-top: 0.25rem;
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--orange);
        }
        #decline-reason-dialog .driver-dialog__panel {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 0.65rem;
            padding: 1rem;
            box-shadow: none;
            text-align: left;
        }
        #decline-reason-dialog .driver-dialog__title {
            font-size: 1.125rem;
            margin-bottom: 0.65rem;
            text-align: center;
        }
        #decline-reason-dialog .driver-dialog__text {
            margin-bottom: 0.85rem;
            font-size: 0.875rem;
            text-align: center;
        }
        #decline-reason-dialog label.offer-meta {
            display: block;
            margin: 0 0 0.35rem;
            text-align: left;
        }
        #decline-reason-dialog #decline-reason-input {
            margin: 0 0 0.85rem;
            min-height: 5rem;
            border-radius: 0.65rem;
            border: 1px solid var(--line);
            background: var(--bg);
            font-size: 0.9375rem;
            padding: 0.75rem 0.85rem;
        }
        #decline-reason-dialog #decline-reason-input:focus {
            border-color: rgba(var(--accent-rgb), 0.55);
            box-shadow: 0 0 0 2px rgba(var(--accent-rgb), 0.18);
        }
        #decline-reason-dialog .driver-dialog__actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin-top: 0.15rem;
            padding-top: 0.7rem;
            border-top: 1px solid var(--line);
        }
        #decline-reason-dialog .driver-dialog__actions .btn {
            min-height: 2.55rem;
            text-transform: uppercase;
            font-size: 0.82rem;
            border-radius: 0.55rem;
            letter-spacing: 0.03em;
        }
        #payment-qr-wrap {
            text-align: center;
            margin: 1rem 0;
        }
        #payment-qr-wrap img {
            max-width: 280px;
            width: 100%;
            border-radius: 0.75rem;
            background: #fff;
            padding: 0.5rem;
        }
        .dispatch-footer {
            display: none;
        }
        #offer-actions-panel {
            margin: 0;
        }
        #offer-actions-panel.is-pickup-overdue {
            grid-template-columns: 1fr 1fr;
            align-items: stretch;
        }
        #offer-actions-panel.is-pickup-overdue #btn-decline,
        #offer-actions-panel.is-pickup-overdue #btn-accept {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: normal;
            line-height: 1.15;
            letter-spacing: 0.03em;
            font-size: 0.88rem;
            font-weight: 700;
            padding: 0.5rem 0.4rem;
            min-height: 3.15rem;
            text-align: center;
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
            border-radius: 1rem;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid var(--line);
        }
        label { display: block; font-size: 0.8125rem; color: var(--muted); margin-bottom: 0.35rem; }
        input[type="email"], input[type="password"], #login-form input[type="text"] {
            width: 100%;
            padding: 0.85rem 1rem;
            border-radius: 0.75rem;
            border: 1px solid var(--line);
            background: var(--card-elevated, #252528);
            color: var(--text);
            font-size: 1rem;
            margin-bottom: 0.75rem;
        }
        #login-form input[type="email"],
        #login-form input[type="password"],
        #login-form input[type="text"] {
            background: #252528;
            border-color: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            -webkit-text-fill-color: #ffffff;
            caret-color: #ffffff;
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
            margin-bottom: 0.75rem;
        }
        #login-form .js-pw-toggle-wrap input {
            margin-bottom: 0;
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
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            min-height: 3rem;
            border: none;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            touch-action: manipulation;
            letter-spacing: 0.02em;
        }
        .btn-primary { background: var(--orange); color: var(--accent-on, #fff); }
        .btn-primary:hover { background: var(--orange-hover); color: var(--accent-on, #fff); }
        .btn-accept { background: var(--orange); color: var(--accent-on, #fff); }
        .btn-accept:hover { background: var(--orange-hover); color: var(--accent-on, #fff); }
        .btn-danger {
            background: transparent;
            color: var(--text);
            border: 1.5px solid var(--orange);
        }
        .btn-danger:hover {
            background: rgba(var(--accent-rgb), 0.12);
        }
        .btn-ghost { background: transparent; color: var(--muted); border: 1px solid var(--line); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn.is-loading {
            pointer-events: none;
            opacity: 0.85;
        }
        .btn-spinner {
            width: 1.125rem;
            height: 1.125rem;
            border: 2px solid currentColor;
            border-right-color: transparent;
            border-radius: 50%;
            animation: btn-spin 0.65s linear infinite;
            flex-shrink: 0;
        }
        @keyframes btn-spin {
            to { transform: rotate(360deg); }
        }
        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        .toolbar-nav {
            display: flex;
            align-items: center;
            margin: 0;
            min-height: 0;
        }
        .toolbar-nav-buttons {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
            flex-wrap: nowrap;
            max-width: 100%;
            padding: 0;
            overflow: visible;
        }
        .dispatch-screen-title {
            text-align: center;
            font-size: 1.0625rem;
            font-weight: 700;
            margin: 0 0 0.65rem;
            line-height: 1.3;
        }
        .banner-ride-accepted {
            background: rgba(22, 163, 74, 0.25);
            border: 1px solid rgba(22, 163, 74, 0.55);
            color: #bbf7d0;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            margin-bottom: 0.75rem;
            font-weight: 600;
            font-size: 0.9375rem;
        }
        .contract-ride-badge {
            display: inline-block;
            background: rgba(var(--ride-contract-rgb), 0.2);
            border: 1px solid rgba(var(--ride-contract-rgb), 0.45);
            color: #bfdbfe;
            border-radius: 999px;
            padding: 0.15rem 0.55rem;
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            margin: 0 0 0.5rem;
        }
        .taxi-ride-badge {
            display: inline-block;
            background: rgba(var(--ride-taxi-rgb), 0.2);
            border: 1px solid rgba(var(--ride-taxi-rgb), 0.45);
            color: #fdba74;
            border-radius: 999px;
            padding: 0.15rem 0.55rem;
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            margin: 0 0 0.5rem;
        }
        .nexa-suite-ride-badge,
        .offer-badge.is-nexa-suite {
            display: inline-block;
            background: rgba(234, 179, 8, 0.22);
            border: 1px solid rgba(250, 204, 21, 0.55);
            color: #fde68a;
            border-radius: 999px;
            padding: 0.15rem 0.55rem;
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            margin: 0 0.35rem 0.5rem 0;
        }
        .return-ride-badge {
            display: inline-block;
            background: rgba(168, 85, 247, 0.18);
            border: 1px solid rgba(168, 85, 247, 0.45);
            color: #e9d5ff;
            border-radius: 999px;
            padding: 0.15rem 0.55rem;
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            margin: 0 0.35rem 0.5rem 0;
        }
        .return-outbound-done {
            color: #c4b5fd !important;
            margin: -0.15rem 0 0.5rem !important;
        }
        .offer-return-at {
            color: #ddd6fe !important;
            margin: -0.15rem 0 0.65rem !important;
        }
        #btn-start-return {
            width: 100%;
            margin-top: 0.75rem;
            min-height: 3.25rem;
            font-size: 1.0625rem;
            background: #7c3aed;
            color: #fff;
        }
        #btn-release-return {
            width: 100%;
            margin-top: 0.5rem;
            min-height: 3rem;
            font-size: 1rem;
            background: transparent;
            color: var(--soft-text);
            border: 1px solid var(--line);
        }
        .contract-stops-panel { margin-top: 0.75rem; }
        .contract-stops-progress { margin: 0 0 0.75rem !important; }
        .contract-stop-item {
            border: 1px solid var(--line);
            border-radius: 0.65rem;
            padding: 0.65rem 0.75rem;
            margin-bottom: 0.5rem;
            background: var(--card-elevated);
        }
        .contract-stop-item.is-done { opacity: 0.72; }
        .contract-stop-head {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            margin-bottom: 0.35rem;
        }
        .contract-stop-seq {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--muted);
            min-width: 1.25rem;
        }
        .contract-stop-main { flex: 1; min-width: 0; }
        .contract-stop-main strong { display: block; font-size: 0.875rem; }
        .contract-stop-time { font-size: 0.75rem; color: var(--muted); }
        .contract-stop-status {
            font-size: 0.6875rem;
            color: var(--soft-text);
            white-space: nowrap;
            font-weight: 600;
            letter-spacing: 0.02em;
        }
        .contract-stop-status--planned { color: var(--soft-text); }
        .contract-stop-status--arrived { color: #4ade80; }
        .contract-stop-status--picked_up { color: #86efac; }
        .contract-stop-status--skipped { color: #fca5a5; }
        .contract-stop-status--completed { color: var(--muted); }
        .contract-stop-status--arriving {
            position: relative;
            display: inline-block;
            min-width: 4.75rem;
            text-align: right;
            color: #4ade80;
        }
        .contract-stop-status-phase { display: inline-block; }
        .contract-stop-status--arriving .contract-stop-status-phase--from {
            color: var(--soft-text);
            animation: contract-stop-from-out 0.42s ease-in forwards;
        }
        .contract-stop-status--arriving .contract-stop-status-phase--to {
            position: absolute;
            right: 0;
            top: 0;
            color: #4ade80;
            opacity: 0;
            animation: contract-stop-to-in 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.12s forwards;
        }
        @keyframes contract-stop-from-out {
            0% { opacity: 1; transform: translateY(0) scale(1); }
            100% { opacity: 0; transform: translateY(-0.35rem) scale(0.92); }
        }
        @keyframes contract-stop-to-in {
            0% { opacity: 0; transform: translateY(0.4rem) scale(0.88); }
            55% { transform: translateY(0) scale(1.08); }
            100% { opacity: 1; transform: translateY(0) scale(1); color: #4ade80; }
        }
        @media (prefers-reduced-motion: reduce) {
            .contract-stop-status--arriving .contract-stop-status-phase--from,
            .contract-stop-status--arriving .contract-stop-status-phase--to {
                animation: none;
            }
            .contract-stop-status--arriving .contract-stop-status-phase--from { display: none; }
            .contract-stop-status--arriving .contract-stop-status-phase--to {
                position: static;
                opacity: 1;
                color: #4ade80;
            }
        }
        .contract-stop-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
            margin-top: 0.5rem;
        }
        .contract-stop-actions .btn-sm {
            font-size: 0.75rem;
            padding: 0.35rem 0.55rem;
        }
        .contract-stop-actions .btn-stop-pickup,
        .contract-stop-actions .btn-stop-skip {
            font-size: 0.9375rem;
        }
        .contract-stop-actions .btn-stop-arrive {
            background: #c2610e;
            border: 1px solid rgba(251, 191, 36, 0.45);
            color: #fff7ed;
        }
        .contract-stop-actions .btn-stop-pickup {
            background: #1a7a45;
            border: 1px solid rgba(74, 222, 128, 0.4);
            color: #ecfdf5;
        }
        .contract-stop-actions .btn-stop-skip {
            background: #b83232;
            border: 1px solid rgba(248, 113, 113, 0.4);
            color: #fef2f2;
        }
        .contract-stop-auto-hint {
            margin: 0.5rem 0 0;
            font-size: 0.75rem;
            color: var(--muted);
        }
        .active-ride-collapsed-banner {
            margin-bottom: 0.75rem;
        }
        .active-ride-collapsed-banner .btn {
            width: 100%;
            margin-top: 0.75rem;
            min-height: 3rem;
        }
        .parked-assigned-ride-card.is-active-ride {
            border-color: rgba(34, 197, 94, 0.45);
            box-shadow: 0 0 0 1px rgba(34, 197, 94, 0.18);
        }
        .parked-assigned-ride-card .offer-card-top {
            margin-bottom: 0.55rem;
            align-items: flex-start;
        }
        .parked-assigned-ride-card .offer-title {
            margin-bottom: 0.25rem;
        }
        .parked-assigned-rides-strip {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }
        .parked-assigned-ride-card .btn {
            width: 100%;
            margin-top: 0.75rem;
            min-height: 3rem;
        }
        .scheduled-ride-card.is-contract-ride .scheduled-ride-toggle-text .offer-title {
            display: inline;
            margin-left: 0.35rem;
        }
        .scheduled-ride-card.is-taxi-ride,
        .offer-card.is-taxi-ride {
            border-color: rgba(var(--ride-taxi-rgb), 0.38);
            box-shadow: inset 3px 0 0 var(--ride-taxi);
        }
        .scheduled-ride-card.is-contract-ride,
        .offer-card.is-contract-ride,
        #active-ride-strip.is-contract-ride {
            border-color: rgba(var(--ride-contract-rgb), 0.45);
            box-shadow: inset 3px 0 0 var(--ride-contract);
        }
        .active-ride-card { overflow: visible; }
        .offer-price { white-space: nowrap; }
        .offer-card {
            animation: none;
            margin-bottom: 0.85rem;
            overflow: visible;
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 0.65rem;
            padding: 1rem;
        }
        .offer-card-top {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.85rem;
        }
        .offer-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.3rem 0.65rem;
            border-radius: 0.5rem;
            background: rgba(var(--accent-rgb), 0.18);
            color: var(--orange);
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .offer-badge-row {
            display: inline-flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.4rem;
            min-width: 0;
        }
        .offer-badge.is-muted {
            background: rgba(148, 163, 184, 0.16);
            color: var(--soft-text);
        }
        .offer-badge.is-success {
            background: rgba(34, 197, 94, 0.18);
            color: #4ade80;
        }
        .offer-badge.is-danger {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }
        .offer-badge.is-warning {
            background: rgba(251, 191, 36, 0.2);
            color: #fbbf24;
        }
        .offer-card-meta-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.35rem;
            text-align: right;
        }
        #active-ride-strip .offer-card-meta-right {
            flex-direction: row;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
        }
        .offer-ago {
            font-size: 0.75rem;
            color: var(--muted);
        }
        .offer-vehicle-pill {
            display: inline-flex;
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
            background: rgba(148, 163, 184, 0.16);
            color: var(--soft-text);
            font-size: 0.7rem;
            font-weight: 600;
        }
        .offer-body-grid {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
        }
        .offer-meta-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 0.75rem 1rem;
            align-items: end;
            margin-bottom: 0.65rem;
        }
        .offer-meta-grid .offer-customer-block {
            margin: 0;
            align-self: end;
        }
        .offer-meta-grid .offer-stats {
            text-align: right;
            align-self: end;
        }
        .offer-meta-grid .offer-stats .offer-price-wrap {
            align-items: flex-end;
        }
        .offer-actions:has(> :only-child),
        .offer-actions.declined-ride-actions,
        .offer-actions.overdue-ride-actions:not(:has(.btn-start-ride)) {
            grid-template-columns: 1fr;
        }
        .scheduled-ride-actions.offer-actions,
        .overdue-ride-actions.offer-actions {
            margin-top: 0.85rem;
        }
        .offer-route {
            display: flex;
            flex-direction: column;
            gap: 0;
            position: relative;
            padding-left: 0;
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
            left: auto;
            top: auto;
            width: 1.15rem;
            height: 1.15rem;
            border-radius: 999px;
            border: 2px solid var(--bg);
            flex-shrink: 0;
            z-index: 1;
        }
        .offer-route-dot--pickup { background: var(--green); }
        .offer-route-dot--dropoff { background: var(--orange); }
        .offer-route-label {
            font-size: 0.8125rem;
            text-transform: none;
            letter-spacing: 0.01em;
            color: var(--soft-text);
            font-weight: 600;
            margin: 0;
            line-height: 1.3;
        }
        .offer-route-link {
            display: block;
            text-decoration: none;
            color: inherit;
            padding-left: 1.7rem;
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
        .offer-customer-block {
            margin-top: 0;
            margin-bottom: 0;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            min-width: 0;
        }
        .offer-customer-row {
            margin: 0;
            font-size: 0.9rem;
            color: var(--text);
            line-height: 1.4;
        }
        .offer-customer-label {
            font-size: 0.8125rem;
            letter-spacing: 0.01em;
            color: var(--soft-text);
            font-weight: 600;
            line-height: 1.3;
            margin-right: 0.25rem;
        }
        a.offer-phone {
            color: #93c5fd;
            font-weight: 600;
            text-decoration: none;
        }
        a.offer-phone:active,
        a.offer-phone:focus-visible {
            color: #bfdbfe;
            text-decoration: underline;
            outline: none;
        }
        .offer-stats {
            text-align: right;
        }
        .offer-stats .offer-price-wrap {
            margin: 0;
            align-items: flex-end;
        }
        .offer-stats .offer-price {
            margin: 0;
            font-size: 1.45rem;
            font-weight: 800;
            color: var(--price);
        }
        .offer-stats-line {
            margin: 0.25rem 0 0;
            font-size: 0.78rem;
            color: var(--muted);
            line-height: 1.35;
        }
        .offer-title { font-size: 1.125rem; font-weight: 700; margin: 0 0 0.5rem; }
        .offer-meta { font-size: 0.875rem; color: var(--muted); line-height: 1.45; }
        .offer-address-row { display: none; }
        .offer-price-wrap {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
            margin: 0;
        }
        .offer-price-wrap .offer-price-leg {
            margin: 0;
            font-size: 0.9rem;
            color: var(--text);
            line-height: 1.4;
        }
        .offer-price-wrap .offer-price-total {
            margin: 0.2rem 0 0;
            font-size: 0.75rem;
            color: var(--muted);
        }
        #offer-container { flex-shrink: 0; }
        .offer-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin-top: 0.85rem;
            padding-top: 0.7rem;
            border-top: 1px solid var(--line);
        }
        .offer-actions .btn {
            min-height: 2.55rem;
            text-transform: uppercase;
            font-size: 0.82rem;
            border-radius: 0.55rem;
            letter-spacing: 0.03em;
        }
        @keyframes pulse-border {
            0%, 100% { border-color: rgba(var(--accent-rgb), 0.35); }
            50% { border-color: rgba(var(--accent-rgb), 0.95); }
        }
        .offer-card.is-waiting {
            animation: pulse-border 1.5s ease-in-out infinite;
        }
        .toggle-row { display: none !important; }
        .dispatch-screen-title { display: none; }
        .toolbar-nav-buttons .btn-toolbar {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.75rem;
            min-width: 2.75rem;
            max-width: 2.75rem;
            height: 2.75rem;
            min-height: 2.75rem;
            max-height: 2.75rem;
            padding: 0;
            border: none;
            border-radius: 0.75rem;
            background: transparent;
            color: var(--muted);
            font-size: 0;
            font-weight: 400;
            letter-spacing: 0;
            flex-shrink: 0;
            box-sizing: border-box;
            gap: 0;
            cursor: pointer;
            -webkit-appearance: none;
            appearance: none;
        }
        .toolbar-nav-buttons .btn-toolbar.is-active {
            border: none;
            color: var(--orange);
            background: transparent;
        }
        .toolbar-nav-buttons .btn-toolbar:active {
            background: transparent;
            opacity: 0.85;
        }
        .toolbar-nav-icon {
            position: relative;
            display: inline-flex;
            width: 1.85rem;
            height: 1.85rem;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .toolbar-nav-icon svg,
        .toolbar-nav-buttons .btn-toolbar svg {
            width: 1.85rem;
            height: 1.85rem;
            display: block;
            flex-shrink: 0;
        }
        .toolbar-nav-badge {
            position: absolute;
            top: -0.55rem;
            right: -0.65rem;
            z-index: 1;
            min-width: 1.25rem;
            height: 1.25rem;
            padding: 0 0.28rem;
            border-radius: 999px;
            background: #ef4444;
            color: #fff;
            font-size: 0.75rem;
            font-weight: 800;
            line-height: 1.25rem;
            letter-spacing: 0;
            text-align: center;
            box-shadow: 0 0 0 2px var(--chrome);
        }
        .profile-panel .card {
            margin-top: 0.5rem;
        }
        .profile-panel .profile-info {
            margin: 0 0 0.85rem;
        }
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
        .profile-panel .profile-info__row {
            display: grid;
            gap: 0.15rem;
        }
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
        .profile-panel .profile-session-note {
            margin: 0 0 0.25rem;
        }
        .profile-panel .btn,
        .earnings-panel .btn {
            margin-top: 0.75rem;
        }
        .earnings-day-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            margin: 0 0 0.85rem;
        }
        .earnings-day-nav__btn {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.75rem;
            border: 1px solid var(--line);
            background: var(--card-elevated);
            color: var(--text);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            padding: 0;
            flex-shrink: 0;
        }
        .earnings-day-nav__btn:disabled {
            opacity: 0.35;
            cursor: default;
        }
        .earnings-day-nav__btn svg {
            width: 1.1rem;
            height: 1.1rem;
        }
        .earnings-day-nav__meta {
            min-width: 0;
            text-align: center;
            flex: 1;
        }
        .earnings-day-nav__label {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            color: var(--text);
            line-height: 1.25;
        }
        .earnings-day-nav__sub {
            margin: 0.15rem 0 0;
            font-size: 0.78rem;
            color: var(--muted);
        }
        .earnings-summary {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }
        .earnings-summary__card.offer-card {
            margin-bottom: 0;
            padding: 1rem;
        }
        .earnings-summary__label {
            margin: 0;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--soft-text);
            line-height: 1.3;
        }
        .earnings-summary__value {
            margin: 0.35rem 0 0;
            font-size: 1.45rem;
            font-weight: 800;
            color: var(--price);
            font-variant-numeric: tabular-nums;
            line-height: 1.2;
        }
        .earnings-summary__hint {
            margin: 0.25rem 0 0;
            font-size: 0.78rem;
            color: var(--muted);
        }
        .earnings-rides-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .earnings-ride-card.offer-card {
            margin-bottom: 0;
        }
        .earnings-ride-card .earnings-ride-toggle-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            width: 100%;
        }
        .earnings-ride-card .earnings-ride-toggle-top .offer-badge-row {
            flex-wrap: wrap;
        }
        .earnings-ride-card .earnings-ride-amount-summary {
            margin: 0.15rem 0 0;
            font-size: 1rem;
            font-weight: 700;
            color: var(--text);
        }
        .earnings-ride-card .offer-meta-grid {
            margin-bottom: 0;
        }
        .earnings-empty,
        .earnings-error,
        .earnings-loading {
            text-align: center;
            color: var(--muted);
            padding: 1.25rem 0.75rem;
            margin: 0;
            font-size: 0.9rem;
        }
        .earnings-error { color: #f87171; }
        .driver-bottom-nav__btn[hidden] {
            display: none !important;
        }
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
        .ride-kind-filter {
            display: inline-flex;
            align-items: stretch;
            flex-shrink: 0;
            border: 1px solid var(--line);
            border-radius: 0.75rem;
            overflow: hidden;
            background: var(--card-elevated);
        }
        .ride-kind-filter__btn {
            border: none;
            background: transparent;
            color: var(--muted);
            font-size: 0.78rem;
            font-weight: 650;
            padding: 0.38rem 0.7rem;
            cursor: pointer;
            min-height: 2rem;
        }
        .ride-kind-filter__btn + .ride-kind-filter__btn {
            border-left: 1px solid var(--line);
        }
        .ride-kind-filter__btn[data-ride-kind="taxi"] {
            color: #fdba74;
        }
        .ride-kind-filter__btn[data-ride-kind="contract"] {
            color: #93c5fd;
        }
        .ride-kind-filter__btn.is-active {
            background: rgba(var(--accent-rgb), 0.18);
            color: var(--orange);
        }
        .ride-kind-filter__btn[data-ride-kind="taxi"].is-active {
            background: rgba(var(--ride-taxi-rgb), 0.22);
            color: #fdba74;
            box-shadow: inset 0 -2px 0 var(--ride-taxi);
        }
        .ride-kind-filter__btn[data-ride-kind="contract"].is-active {
            background: rgba(var(--ride-contract-rgb), 0.22);
            color: #93c5fd;
            box-shadow: inset 0 -2px 0 var(--ride-contract);
        }
        .ride-kind-filter__btn[data-ride-kind="all"].is-active {
            background: rgba(148, 163, 184, 0.18);
            color: #e2e8f0;
            box-shadow: inset 0 -2px 0 #94a3b8;
        }
        .driver-section-head--with-filter {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.55rem;
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
        .planning-day-stack .planning-empty {
            width: 100%;
            padding-top: 0.35rem;
        }
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
            border: 1px solid rgba(var(--ride-taxi-rgb), 0.32);
            background: var(--card-elevated);
            box-shadow: inset 3px 0 0 var(--ride-taxi);
            color: inherit;
            border-radius: 0.85rem;
            padding: 0.75rem 0.85rem 0.75rem 1rem;
            margin: 0 0 0.5rem;
            cursor: default;
        }
        .planning-ride-card.is-taxi {
            border-color: rgba(var(--ride-taxi-rgb), 0.32);
            box-shadow: inset 3px 0 0 var(--ride-taxi);
        }
        .planning-ride-card.is-contract {
            border-color: rgba(var(--ride-contract-rgb), 0.42);
            box-shadow: inset 3px 0 0 var(--ride-contract);
        }
        .planning-ride-card.is-assigned {
            background: var(--card-elevated);
        }
        .planning-ride-card.is-completed {
            background: var(--card-elevated);
            opacity: 0.88;
        }
        button.planning-ride-card {
            cursor: pointer;
        }
        button.planning-ride-card:active {
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
            color: var(--ride-taxi);
        }
        .planning-ride-card.is-contract .planning-ride-card__time {
            color: #93c5fd;
        }
        .planning-ride-card.is-assigned .planning-ride-card__time {
            color: #4ade80;
        }
        .planning-ride-card.is-contract.is-assigned .planning-ride-card__time {
            color: #4ade80;
        }
        .planning-ride-card.is-completed .planning-ride-card__time {
            color: #94a3b8;
        }
        .planning-ride-card__status {
            display: inline-flex;
            align-items: center;
            padding: 0.18rem 0.5rem;
            border-radius: 999px;
            background: rgba(var(--ride-taxi-rgb), 0.2);
            color: #fdba74;
            font-size: 0.68rem;
            font-weight: 750;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            flex-shrink: 0;
        }
        .planning-ride-card.is-contract .planning-ride-card__status {
            background: rgba(var(--ride-contract-rgb), 0.22);
            color: #93c5fd;
        }
        .planning-ride-card.is-assigned .planning-ride-card__status {
            background: rgba(34, 197, 94, 0.2);
            color: #4ade80;
        }
        .planning-ride-card.is-completed .planning-ride-card__status {
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
            color: var(--ride-taxi);
            font-weight: 800;
        }
        .planning-ride-card.is-contract .planning-ride-card__arrow {
            color: #93c5fd;
        }
        .planning-ride-card.is-assigned .planning-ride-card__arrow {
            color: #4ade80;
        }
        .planning-ride-card.is-completed .planning-ride-card__arrow {
            color: #94a3b8;
        }
        html[data-theme="light"] .planning-ride-card.is-assigned .planning-ride-card__arrow {
            color: #15803d;
        }
        html[data-theme="light"] .planning-ride-card.is-completed .planning-ride-card__arrow {
            color: #475569;
        }
        .planning-ride-card__meta {
            margin: 0.2rem 0 0;
            font-size: 0.78rem;
            color: var(--muted);
            line-height: 1.35;
        }
        .planning-ride-card.is-contract .planning-ride-card__meta {
            color: #93c5fd;
        }
        html[data-theme="light"] .planning-ride-card.is-assigned .planning-ride-card__time,
        html[data-theme="light"] .planning-ride-card.is-assigned .planning-ride-card__status {
            color: #15803d;
        }
        html[data-theme="light"] .planning-ride-card.is-completed .planning-ride-card__time,
        html[data-theme="light"] .planning-ride-card.is-completed .planning-ride-card__status {
            color: #475569;
        }
        html[data-theme="light"] .planning-ride-card.is-contract .planning-ride-card__meta {
            color: #1d4ed8;
        }
        .planning-empty {
            text-align: center;
            color: var(--muted);
            padding: 1.1rem 0.75rem;
            margin: 0;
            font-size: 0.9rem;
        }
        .scheduled-rides-strip { margin-bottom: 1rem; }
        .scheduled-rides-title {
            margin: 0 0 0.75rem;
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--muted);
        }
        #overdue-strip #overdue-hint,
        #declined-strip .scheduled-rides-title,
        #declined-strip #declined-hint,
        #pending-approval-strip .scheduled-rides-title,
        #pending-approval-strip #pending-approval-hint {
            text-align: center;
        }
        #overdue-strip #overdue-hint {
            margin: 0 0 0.85rem;
            font-size: 0.8125rem;
            color: var(--muted);
        }
        #pending-approval-strip {
            margin-bottom: 1rem;
        }
        #pending-approval-strip .scheduled-rides-title {
            margin: 0 0 0.35rem;
        }
        #pending-approval-strip #pending-approval-hint {
            margin: 0 0 0.75rem;
            font-size: 0.8125rem;
            color: var(--muted);
        }
        #declined-strip #declined-hint {
            margin: -0.25rem 0 0.75rem;
            font-size: 0.8125rem;
            color: var(--muted);
        }
        .scheduled-ride-card .scheduled-ride-toggle {
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
        .scheduled-ride-card .scheduled-ride-toggle-text {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
            min-width: 0;
            flex: 1;
        }
        .scheduled-ride-card .scheduled-ride-toggle-text .contract-ride-badge,
        .scheduled-ride-card .scheduled-ride-toggle-text .taxi-ride-badge,
        .scheduled-ride-card .scheduled-ride-toggle-text .nexa-suite-ride-badge,
        .scheduled-ride-card .scheduled-ride-toggle-text .return-ride-badge {
            align-self: flex-start;
            width: auto;
            max-width: max-content;
        }
        .scheduled-ride-card .scheduled-ride-toggle-text .return-ride-badge {
            margin-top: 0.25rem;
            margin-bottom: 0;
        }
        .scheduled-ride-card.is-expanded .scheduled-ride-toggle-text .return-ride-badge {
            margin-bottom: 0.15rem;
        }
        .scheduled-ride-card .scheduled-ride-toggle .offer-title {
            margin: 0;
        }
        .scheduled-ride-card .scheduled-ride-toggle .scheduled-pickup-at {
            margin: 0;
            font-size: 1.0625rem;
            font-weight: 600;
            line-height: 1.3;
            color: var(--muted);
        }
        .scheduled-ride-card .scheduled-ride-toggle .scheduled-route-summary,
        .scheduled-ride-card .scheduled-ride-toggle .scheduled-proposal-summary {
            margin: 0;
            font-size: 0.78rem;
            color: var(--soft-text);
            line-height: 1.35;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        .scheduled-ride-card .scheduled-ride-toggle .scheduled-proposal-summary {
            color: #fca5a5;
            font-weight: 600;
        }
        .scheduled-ride-card .scheduled-ride-toggle .scheduled-proposal-summary.is-pending {
            color: #fbbf24;
        }
        .scheduled-ride-card.overdue-ride-card .scheduled-ride-toggle-text .offer-badge {
            align-self: flex-start;
            margin-bottom: 0.15rem;
        }
        .scheduled-ride-card + .scheduled-ride-card,
        .scheduled-ride-card + .overdue-ride-card,
        .overdue-ride-card + .scheduled-ride-card,
        .overdue-ride-card + .overdue-ride-card {
            margin-top: 0.75rem;
        }
        .scheduled-ride-chevron {
            flex-shrink: 0;
            width: 2rem;
            height: 2rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            border: 1px solid var(--line);
            color: var(--muted);
            transition: transform 0.2s ease, color 0.2s ease;
        }
        .scheduled-ride-card.is-expanded .scheduled-ride-chevron {
            transform: rotate(180deg);
            color: var(--text);
        }
        .scheduled-ride-body {
            margin-top: 0.85rem;
            padding-top: 0.85rem;
            border-top: 1px solid var(--line);
        }
        .scheduled-ride-card > .offer-actions,
        .scheduled-ride-card > .scheduled-ride-actions,
        .scheduled-ride-card > .overdue-ride-actions {
            margin-top: 0.75rem;
        }
        .scheduled-ride-card:not(.is-expanded) > .scheduled-ride-body[hidden] + .offer-actions,
        .scheduled-ride-card:not(.is-expanded) > .scheduled-ride-body[hidden] + .scheduled-ride-actions,
        .scheduled-ride-card:not(.is-expanded) > .scheduled-ride-body[hidden] + .overdue-ride-actions {
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--line);
        }
        .scheduled-ride-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-top: 0.85rem;
        }
        .contract-start-hint {
            margin: 0;
            width: 100%;
            text-align: center;
            color: var(--muted);
        }
        .scheduled-ride-card.is-contract-ride .scheduled-ride-actions {
            grid-template-columns: 1fr;
        }
        .scheduled-ride-actions .btn { margin-top: 0; width: 100%; }
        .scheduled-ride-actions .btn-release-ride,
        .overdue-ride-actions .btn-release-ride {
            background: transparent;
            color: var(--text);
            border: 1.5px solid var(--orange);
        }
        .scheduled-ride-actions .btn-release-ride:hover,
        .overdue-ride-actions .btn-release-ride:hover {
            background: rgba(var(--accent-rgb), 0.12);
        }
        .scheduled-ride-card .btn-start-ride { margin-top: 0; }
        .offer-pickup-at { margin: 0 0 0.75rem; font-size: 1.0625rem; font-weight: 600; color: #fbbf24; }
        .offer-queue-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            margin: 0.5rem 0 0.75rem;
            padding: 0.5rem 0.65rem;
            border-radius: 0.65rem;
            background: var(--card-elevated);
            border: 1px solid var(--line);
        }
        .offer-queue-nav .btn-queue {
            min-height: 2.25rem;
            width: auto;
            padding: 0.4rem 0.75rem;
            font-size: 0.8125rem;
            background: rgba(148, 163, 184, 0.12);
            color: var(--text);
            border: 1px solid var(--line);
        }
        .offer-queue-nav .btn-queue:disabled { opacity: 0.35; }
        .offer-queue-label { font-size: 0.8125rem; color: var(--muted); text-align: center; flex: 1; }
        .offer-waiting-banner {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        .offer-waiting-banner.is-visible {
            position: static;
            width: auto;
            height: auto;
            margin: 0 0 0.75rem;
            padding: 0.55rem 0.75rem;
            overflow: visible;
            clip: auto;
            white-space: normal;
            border: 1px solid rgba(248, 113, 113, 0.45);
            border-radius: 0.5rem;
            background: rgba(220, 38, 38, 0.16);
            color: #fecaca;
            font-size: 0.8125rem;
            font-weight: 700;
            letter-spacing: 0.01em;
            animation: offer-waiting-pulse 1.2s ease-in-out infinite;
        }
        .offer-waiting-banner.is-visible.is-overdue {
            border-color: rgba(239, 68, 68, 0.7);
            background: rgba(185, 28, 28, 0.22);
            color: #fecaca;
        }
        .offer-waiting-banner.is-visible.is-success-banner {
            border-color: rgba(34, 197, 94, 0.55);
            background: rgba(22, 163, 74, 0.18);
            color: #86efac;
            animation: none;
        }
        .offer-waiting-dot {
            width: 0.7rem;
            height: 0.7rem;
            border-radius: 999px;
            background: #ef4444;
            box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
            flex-shrink: 0;
            animation: offer-waiting-blink 1.1s ease-in-out infinite;
        }
        .offer-waiting-dot[hidden] {
            display: none !important;
        }
        .offer-card-top .offer-badge-row {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
        }
        @keyframes offer-waiting-blink {
            0%, 100% {
                opacity: 1;
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.55);
                transform: scale(1);
            }
            50% {
                opacity: 0.35;
                box-shadow: 0 0 0 8px rgba(239, 68, 68, 0);
                transform: scale(0.92);
            }
        }
        .offer-card.is-waiting {
            border-color: rgba(248, 113, 113, 0.55);
            box-shadow: 0 0 0 1px rgba(220, 38, 38, 0.25);
            animation: pulse-border 1.5s ease-in-out infinite;
        }
        @keyframes pulse-border-overdue {
            0%, 100% { border-color: rgba(239, 68, 68, 0.45); box-shadow: 0 0 0 1px rgba(185, 28, 28, 0.2); }
            50% { border-color: rgba(239, 68, 68, 0.95); box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.35); }
        }
        .offer-card.is-pickup-overdue {
            border-color: rgba(239, 68, 68, 0.75);
            box-shadow: 0 0 0 1px rgba(185, 28, 28, 0.35);
            animation: pulse-border-overdue 1.2s ease-in-out infinite;
        }
        @keyframes offer-waiting-pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.72; }
        }
        .empty { text-align: center; color: var(--muted); padding: 2.5rem 1rem 2rem; }
        .inbox-empty-icon {
            width: 5.5rem;
            height: 5.5rem;
            margin: 0.75rem auto 1.35rem;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(148, 163, 184, 0.1);
            border: 1px solid rgba(148, 163, 184, 0.2);
            color: var(--muted);
        }
        .inbox-empty-icon svg {
            width: 2.85rem;
            height: 2.85rem;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.35;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .inbox-empty-icon[data-state="offline"] {
            background: rgba(100, 116, 139, 0.14);
            border-color: rgba(100, 116, 139, 0.28);
            color: var(--muted);
        }
        .inbox-empty-icon[data-state="loading"] {
            background: rgba(var(--accent-rgb), 0.1);
            border-color: rgba(var(--accent-rgb), 0.28);
            color: var(--orange);
        }
        .inbox-empty-spinner {
            width: 2.35rem;
            height: 2.35rem;
            border: 3px solid rgba(var(--accent-rgb), 0.22);
            border-top-color: currentColor;
            border-radius: 50%;
            animation: btn-spin 0.75s linear infinite;
        }
        .inbox-empty-icon[data-state="inactive"],
        .inbox-empty-icon[data-state="error"] {
            background: rgba(248, 113, 113, 0.12);
            border-color: rgba(248, 113, 113, 0.28);
            color: #f87171;
        }
        #inbox-empty-title {
            margin: 0 0 0.5rem;
            font-size: 1.0625rem;
            font-weight: 600;
            color: var(--soft-text);
        }
        #inbox-empty-hint {
            margin: 0;
            max-width: 18rem;
            margin-inline: auto;
            line-height: 1.45;
        }
        .inbox-empty-actions {
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
            max-width: 18rem;
            margin: 1rem auto 0;
        }
        .inbox-empty-actions .btn {
            width: 100%;
            min-height: 2.75rem;
            margin-top: 0;
            font-size: 0.9rem;
        }
        .error { color: #fca5a5; font-size: 0.875rem; margin-top: 0.5rem; }
        .toggle-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }
        .card.toggle-row {
            padding: 0.55rem 0.85rem;
            margin-bottom: 0.5rem;
            font-size: 0.9375rem;
        }
        .switch {
            width: 2.85rem;
            height: 1.65rem;
            background: #334155;
            border-radius: 999px;
            position: relative;
            border: none;
            cursor: pointer;
            flex-shrink: 0;
        }
        .switch::after {
            content: '';
            position: absolute;
            top: 0.2rem;
            left: 0.2rem;
            width: 1.25rem;
            height: 1.25rem;
            background: #fff;
            border-radius: 50%;
            transition: transform 0.2s;
        }
        .switch.is-on { background: var(--green); }
        .switch.is-on::after { transform: translateX(1.2rem); }
        .banner-inactive {
            background: rgba(220, 38, 38, 0.15);
            border: 1px solid rgba(248, 113, 113, 0.45);
            color: #fecaca;
            border-radius: 0.75rem;
            padding: 0.85rem 1rem;
            font-size: 0.875rem;
            line-height: 1.45;
            margin-bottom: 1rem;
        }
        .banner-unclaimed {
            background: rgba(234, 88, 12, 0.18);
            border: 1px solid rgba(251, 146, 60, 0.5);
            color: #fed7aa;
            border-radius: 0.75rem;
            padding: 0.85rem 1rem;
            font-size: 0.875rem;
            line-height: 1.45;
            margin-bottom: 1rem;
        }
        .banner-unclaimed ul {
            margin: 0.5rem 0 0;
            padding-left: 1.1rem;
        }
        .banner-unclaimed li + li {
            margin-top: 0.35rem;
        }
        .toolbar-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: nowrap;
            justify-content: flex-end;
        }
        .btn-toolbar {
            width: auto;
            min-height: auto;
            padding: 0.35rem 0.65rem;
            font-size: 0.8125rem;
            white-space: nowrap;
        }
        .btn-toolbar.is-active {
            background: #2563eb;
            border-color: #2563eb;
            color: #fff;
            font-weight: 600;
        }
        .toolbar-nav-buttons .btn-toolbar,
        .toolbar-nav-buttons .btn-toolbar.is-active,
        .toolbar-nav-buttons .btn-toolbar:active {
            background: transparent;
            border: none;
            box-shadow: none;
        }
        .declined-ride-card {
            margin-bottom: 0.75rem;
        }
        .declined-ride-card .offer-title {
            margin-bottom: 0.25rem;
        }
        .declined-ride-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }
        .declined-ride-actions .btn {
            flex: 1;
            min-height: 2.75rem;
        }
        .declined-badge {
            display: inline-block;
            font-size: 0.75rem;
            font-weight: 600;
            color: #fecaca;
            background: rgba(220, 38, 38, 0.2);
            border-radius: 999px;
            padding: 0.15rem 0.5rem;
            margin-left: 0.35rem;
        }
        .overdue-badge {
            display: inline-block;
            font-size: 0.75rem;
            font-weight: 600;
            color: #fed7aa;
            background: rgba(234, 88, 12, 0.25);
            border-radius: 999px;
            padding: 0.15rem 0.5rem;
            margin-left: 0.35rem;
        }
        .overdue-ride-card {
            margin-bottom: 0.75rem;
        }
        .overdue-ride-card .offer-title {
            margin-bottom: 0.25rem;
        }
        .overdue-ride-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }
        .overdue-ride-actions .btn {
            flex: 1;
            min-height: 2.75rem;
        }
        .archived-bulk-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin: 0 0 0.85rem;
            padding: 0.65rem 0.75rem;
            border-radius: 0.75rem;
            border: 1px solid var(--line);
            background: var(--card);
        }
        .archived-bulk-bar__select-all {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text);
            cursor: pointer;
            min-width: 0;
        }
        .archived-bulk-bar__select-all input,
        .archived-offer-check {
            width: 1.15rem;
            height: 1.15rem;
            accent-color: var(--orange);
            flex-shrink: 0;
        }
        #btn-archived-delete-selected {
            width: auto;
            min-height: 2.5rem;
            padding: 0.4rem 0.85rem;
            margin: 0;
            flex-shrink: 0;
        }
        #btn-archived-delete-selected:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }
        .archived-ride-row {
            display: flex;
            align-items: flex-start;
            gap: 0.55rem;
        }
        .archived-ride-select {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding-top: 0.55rem;
            flex-shrink: 0;
            cursor: pointer;
        }
        .archived-ride-row .archived-ride-toggle {
            flex: 1;
            min-width: 0;
        }
        .overdue-ride-card--archived.is-selected {
            border-color: rgba(var(--accent-rgb), 0.55);
            box-shadow: 0 0 0 1px rgba(var(--accent-rgb), 0.25);
        }
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
            background: var(--orange, #f97316);
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
            justify-content: space-between;
            gap: 0.75rem;
            margin: 0.85rem 0 0.35rem;
            padding: 0.85rem 1rem;
            border-radius: 0.75rem;
            border: 1px solid rgba(var(--accent-rgb), 0.45);
            background: rgba(var(--accent-rgb), 0.14);
            color: var(--accent-muted);
            text-decoration: none;
            font-size: 0.9375rem;
            line-height: 1.35;
        }
        .profile-guide-link strong {
            font-size: 0.95rem;
            color: inherit;
        }
        .profile-guide-link span {
            font-size: 0.8125rem;
            font-weight: 600;
            opacity: 0.9;
            white-space: nowrap;
        }
        html[data-theme="light"] .profile-guide-link {
            background: var(--accent-light-bg);
            border-color: var(--accent-light-border);
            color: var(--accent-light-ink);
        }
        .banner-notifications-hint,
        .banner-install-app {
            position: relative;
            background: rgba(22, 163, 74, 0.12);
            border: 1px solid rgba(22, 163, 74, 0.35);
            color: #bbf7d0;
            border-radius: 0.75rem;
            padding: 0.75rem 2.25rem 0.75rem 1rem;
            font-size: 0.8125rem;
            margin-bottom: 1rem;
            line-height: 1.45;
        }
        #install-app-hint {
            margin: 1rem 1rem 0;
            flex-shrink: 0;
        }
        #guide-hint {
            margin: 1rem 1rem 0.85rem;
            flex-shrink: 0;
        }
        #guide-hint + #install-app-hint:not([hidden]) {
            margin-top: 0.65rem;
        }
        .banner-dismiss-btn {
            position: absolute;
            top: 0.35rem;
            right: 0.35rem;
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
        .banner-notifications-hint .btn-inline,
        .banner-install-app .btn-inline {
            display: inline-block;
            margin-top: 0.5rem;
            padding: 0.45rem 0.75rem;
            border-radius: 0.5rem;
            border: none;
            background: var(--green);
            color: #fff;
            font-size: 0.8125rem;
            font-weight: 600;
            cursor: pointer;
            -webkit-appearance: none;
            touch-action: manipulation;
        }
        #notifications-feedback {
            position: relative;
            margin: -0.5rem 0 1rem;
            padding: 0.65rem 2.25rem 0.65rem 0.85rem;
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            line-height: 1.4;
            background: rgba(148, 163, 184, 0.15);
            color: #e2e8f0;
        }
        #notifications-feedback.is-error {
            background: rgba(220, 38, 38, 0.15);
            color: #fecaca;
        }
        #notifications-feedback.is-success {
            background: rgba(22, 163, 74, 0.2);
            color: #bbf7d0;
        }
        .switch:disabled { opacity: 0.45; cursor: not-allowed; }

        html[data-theme="light"] .banner-ios-awake {
            background: rgba(234, 179, 8, 0.14);
            border-color: rgba(202, 138, 4, 0.35);
            color: #854d0e;
        }
        html[data-theme="light"] .error {
            color: #b91c1c;
        }
        html[data-theme="light"] .scheduled-ride-card,
        html[data-theme="light"] .offer-card,
        html[data-theme="light"] .ride-card,
        html[data-theme="light"] .card {
            border-color: var(--line);
            background: var(--card);
        }
        html[data-theme="light"] .dispatch-top,
        html[data-theme="light"] .driver-bottom-nav {
            background: var(--chrome);
            border-color: var(--line);
        }
        html[data-theme="light"] .offer-badge.is-muted,
        html[data-theme="light"] .offer-vehicle-pill {
            background: rgba(15, 23, 42, 0.06);
            color: #334155;
        }
        html[data-theme="light"] .offer-badge.is-success {
            background: rgba(22, 163, 74, 0.12);
            color: #15803d;
        }
        html[data-theme="light"] .offer-badge.is-warning {
            background: rgba(217, 119, 6, 0.14);
            color: #b45309;
        }
        html[data-theme="light"] .offer-badge.is-danger,
        html[data-theme="light"] .offer-card.is-pickup-overdue .offer-badge {
            background: rgba(220, 38, 38, 0.12);
            color: #b91c1c;
        }
        html[data-theme="light"] .scheduled-ride-card .scheduled-ride-toggle .scheduled-proposal-summary {
            color: #b91c1c;
        }
        html[data-theme="light"] .offer-waiting-banner.is-visible.is-success-banner {
            border-color: rgba(22, 163, 74, 0.45);
            background: rgba(22, 163, 74, 0.12);
            color: #166534;
        }
        html[data-theme="light"] .offer-waiting-banner.is-visible,
        html[data-theme="light"] .offer-waiting-banner.is-visible.is-overdue {
            background: rgba(254, 226, 226, 0.95);
            border-color: rgba(239, 68, 68, 0.55);
            color: #991b1b;
        }
        html[data-theme="light"] .offer-card.is-pickup-overdue {
            border-color: rgba(220, 38, 38, 0.65);
        }
        html[data-theme="light"] a.offer-phone {
            color: #2563eb;
        }
        html[data-theme="light"] a.offer-phone:active,
        html[data-theme="light"] a.offer-phone:focus-visible {
            color: #1d4ed8;
        }
        html[data-theme="light"] #inbox-empty-title {
            color: #334155;
        }
        html[data-theme="light"] #overdue-strip #overdue-hint,
        html[data-theme="light"] #declined-strip #declined-hint {
            color: var(--muted);
        }
        html[data-theme="light"] .toolbar-nav-badge {
            box-shadow: 0 0 0 2px var(--chrome);
        }
        html[data-theme="light"] .earnings-day-nav__btn,
        html[data-theme="light"] .driver-section-head__today {
            background: var(--card-elevated);
            border-color: var(--line);
            color: var(--text);
        }
        html[data-theme="light"] .offer-queue-nav {
            background: var(--card-elevated);
            border-color: var(--line);
        }
        html[data-theme="light"] .offer-queue-nav .btn-queue {
            background: rgba(15, 23, 42, 0.05);
            border-color: var(--line);
            color: var(--text);
        }
        html[data-theme="light"] .banner-ride-accepted {
            background: rgba(22, 163, 74, 0.1);
            border-color: rgba(22, 163, 74, 0.35);
            color: #166534;
        }
        html[data-theme="light"] .banner-unclaimed {
            background: rgba(234, 88, 12, 0.1);
            border-color: rgba(234, 88, 12, 0.35);
            color: #9a3412;
        }
        html[data-theme="light"] .banner-inactive {
            background: rgba(220, 38, 38, 0.1);
            border-color: rgba(220, 38, 38, 0.3);
            color: #991b1b;
        }
        html[data-theme="light"] .banner-notifications-hint,
        html[data-theme="light"] .banner-install-app {
            background: rgba(22, 163, 74, 0.1);
            border-color: rgba(22, 163, 74, 0.35);
            color: #166534;
        }
        html[data-theme="light"] #notifications-feedback {
            color: #334155;
            background: rgba(148, 163, 184, 0.18);
        }
        html[data-theme="light"] #notifications-feedback.is-error {
            color: #991b1b;
            background: rgba(220, 38, 38, 0.1);
        }
        html[data-theme="light"] #notifications-feedback.is-success {
            color: #166534;
            background: rgba(22, 163, 74, 0.12);
        }
        html[data-theme="light"] .driver-dialog__panel {
            background: var(--card);
            border-color: var(--line);
            box-shadow: 0 20px 40px -16px rgba(15, 23, 42, 0.25);
        }
        html[data-theme="light"] .driver-dialog__backdrop {
            background: rgba(15, 23, 42, 0.45);
        }
        html[data-theme="light"] .contract-stop-item {
            background: var(--card-elevated);
            border-color: var(--line);
        }
        html[data-theme="light"] .contract-stop-status--planned,
        html[data-theme="light"] .contract-stop-time,
        html[data-theme="light"] .contract-stop-seq,
        html[data-theme="light"] .contract-ride-meta,
        html[data-theme="light"] .contract-stop-auto-hint {
            color: var(--muted);
        }
        html[data-theme="light"] .taxi-ride-badge {
            background: rgba(249, 115, 22, 0.12);
            border-color: rgba(234, 88, 12, 0.4);
            color: #c2410c;
        }
        html[data-theme="light"] .contract-ride-badge {
            background: rgba(37, 99, 235, 0.1);
            border-color: rgba(37, 99, 235, 0.35);
            color: #1d4ed8;
        }
        html[data-theme="light"] .ride-kind-filter__btn[data-ride-kind="taxi"] {
            color: #c2410c;
        }
        html[data-theme="light"] .ride-kind-filter__btn[data-ride-kind="contract"] {
            color: #1d4ed8;
        }
        html[data-theme="light"] .ride-kind-filter__btn[data-ride-kind="taxi"].is-active {
            background: rgba(249, 115, 22, 0.14);
            color: #c2410c;
        }
        html[data-theme="light"] .ride-kind-filter__btn[data-ride-kind="contract"].is-active {
            background: rgba(37, 99, 235, 0.12);
            color: #1d4ed8;
        }
        html[data-theme="light"] .ride-kind-filter__btn[data-ride-kind="all"].is-active {
            background: rgba(100, 116, 139, 0.12);
            color: #334155;
            box-shadow: inset 0 -2px 0 #64748b;
        }
        html[data-theme="light"] .scheduled-ride-card.is-taxi-ride,
        html[data-theme="light"] .offer-card.is-taxi-ride {
            border-color: rgba(249, 115, 22, 0.35);
        }
        html[data-theme="light"] .scheduled-ride-card.is-contract-ride,
        html[data-theme="light"] .offer-card.is-contract-ride,
        html[data-theme="light"] #active-ride-strip.is-contract-ride {
            border-color: rgba(37, 99, 235, 0.4);
        }
        html[data-theme="light"] .planning-ride-card.is-taxi .planning-ride-card__time,
        html[data-theme="light"] .planning-ride-card:not(.is-contract) .planning-ride-card__time {
            color: #ea580c;
        }
        html[data-theme="light"] .planning-ride-card.is-contract .planning-ride-card__time {
            color: #1d4ed8;
        }
        html[data-theme="light"] .planning-ride-card.is-taxi .planning-ride-card__status,
        html[data-theme="light"] .planning-ride-card:not(.is-contract) .planning-ride-card__status {
            background: rgba(249, 115, 22, 0.12);
            color: #c2410c;
        }
        html[data-theme="light"] .planning-ride-card.is-contract .planning-ride-card__status {
            background: rgba(37, 99, 235, 0.12);
            color: #1d4ed8;
        }
        html[data-theme="light"] .nexa-suite-ride-badge,
        html[data-theme="light"] .offer-badge.is-nexa-suite {
            background: rgba(202, 138, 4, 0.12);
            border-color: rgba(202, 138, 4, 0.4);
            color: #a16207;
        }
        html[data-theme="light"] .return-ride-badge {
            background: rgba(124, 58, 237, 0.1);
            border-color: rgba(124, 58, 237, 0.35);
            color: #6d28d9;
        }
        html[data-theme="light"] .return-outbound-done,
        html[data-theme="light"] .offer-return-at {
            color: #6d28d9 !important;
        }
        html[data-theme="light"] .declined-badge {
            color: #991b1b;
            background: rgba(220, 38, 38, 0.12);
        }
        html[data-theme="light"] .overdue-badge {
            color: #9a3412;
            background: rgba(234, 88, 12, 0.14);
        }
        html[data-theme="light"] .scheduled-ride-actions .btn-release-ride,
        html[data-theme="light"] .overdue-ride-actions .btn-release-ride,
        html[data-theme="light"] #btn-release-return {
            color: var(--text);
        }
        html[data-theme="light"] .earnings-error,
        html[data-theme="light"] #payment-ride-error {
            color: #b91c1c;
        }
        html[data-theme="light"] #invoice-send-status {
            color: #15803d;
        }
        html[data-theme="light"] #invoice-send-status.is-error {
            color: #b91c1c;
        }
        html[data-theme="light"] .parked-assigned-ride-card.is-active-ride {
            border-color: rgba(22, 163, 74, 0.4);
            box-shadow: 0 0 0 1px rgba(22, 163, 74, 0.15);
        }
        html[data-theme="light"] .offer-ago,
        html[data-theme="light"] .soft-muted {
            color: var(--muted);
        }
        html[data-theme="light"] .toolbar-nav-buttons .btn-toolbar {
            color: var(--muted);
        }
        html[data-theme="light"] .toolbar-nav-buttons .btn-toolbar.is-active {
            color: var(--orange);
        }
        html[data-theme="light"] .driver-app-header__online {
            color: var(--text);
        }
        html[data-theme="light"] #payment-panel,
        html[data-theme="light"] #invoice-panel {
            background: var(--bg);
        }
        html[data-theme="light"] .release-return-note,
        html[data-theme="light"] .contract-start-hint {
            color: var(--muted);
        }
        html[data-theme="light"] #btn-send-invoice:disabled,
        html[data-theme="light"] #btn-send-invoice.is-disabled,
        html[data-theme="light"] #btn-complete-ride:disabled,
        html[data-theme="light"] #btn-complete-ride.is-disabled {
            color: var(--muted);
            border-color: #94a3b8;
        }
        html[data-theme="light"] #pickup-adjust-dialog #pickup-adjust-current {
            color: var(--accent-ink);
        }
        html[data-theme="light"] #pickup-adjust-dialog #pickup-adjust-current .pickup-adjust-current__value {
            color: var(--orange-hover);
        }
        html[data-theme="light"] input[type="email"],
        html[data-theme="light"] input[type="password"],
        html[data-theme="light"] #login-form input[type="text"] {
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
                    Nieuw of even niet zeker? Open de handleiding voor installeren, inloggen, online zetten en ritten.
                    Na wegklikken vind je die altijd terug onder <strong>Profiel</strong>.
                </p>
                <a class="btn-inline" id="btn-open-guide" href="{{ $guideUrl ?? url('/taxi/chauffeur/handleiding') }}">Handleiding openen</a>
            </div>
        </div>
        <div id="install-app-hint" class="banner-install-app" hidden role="note">
            <button type="button" class="banner-dismiss-btn" id="btn-dismiss-install-hint" aria-label="Melding sluiten">×</button>
            <span id="install-app-hint-text">Installeer de chauffeur-app op je telefoon voor snellere toegang en betere meldingen.</span>
            <button type="button" class="btn-inline" id="btn-install-app">Installeer app</button>
        </div>
    </div>
    <section id="screen-login" class="screen is-active" aria-label="Inloggen">
        <h1>Chauffeur inloggen</h1>
        <div class="card">
            <form id="login-form" autocomplete="on">
                <div id="login-email-block">
                    <label for="email">E-mail</label>
                    <input id="email" name="email" type="email" inputmode="email" autocomplete="username" required>
                </div>
                <div id="login-password-block">
                    <label for="password">Wachtwoord</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required>
                </div>
                <p id="login-error" class="error" hidden></p>
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

    <section id="screen-dispatch" class="screen" aria-label="Chauffeur">
        <div class="dispatch-top">
            <div class="driver-app-header">
                <div class="driver-app-header__online">
                    <span id="online-label">Online</span>
                    <button type="button" id="online-toggle" class="switch" aria-pressed="false" aria-label="Online"></button>
                </div>
                <div class="driver-app-header__center">
                    <button type="button" class="driver-app-header__active-ride" id="btn-active-ride-jump" hidden aria-label="Actieve rit">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M5 11h14M6 11l1.2-3.6A1.5 1.5 0 0 1 8.6 6h6.8a1.5 1.5 0 0 1 1.4 1.04L18 11M6 11v5a1 1 0 0 0 1 1h1M16 17h1a1 1 0 0 0 1-1v-5"/><circle cx="8" cy="17" r="1.3" stroke="currentColor" stroke-width="2"/><circle cx="16" cy="17" r="1.3" stroke="currentColor" stroke-width="2"/></svg>
                    </button>
                    <div class="toolbar-nav" id="toolbar-nav" hidden>
                        <div class="toolbar-nav-buttons">
                            <button type="button" class="btn-toolbar is-active" id="btn-show-offers" aria-current="page" aria-label="Open">
                                <span class="toolbar-nav-icon" aria-hidden="true">
                                    <svg viewBox="-3 -3 30 30" fill="none"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M22 12h-6l-2 3H10l-2-3H2"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11Z"/></svg>
                                    <span id="offers-count" class="toolbar-nav-badge" hidden>0</span>
                                </span>
                            </button>
                            <button type="button" class="btn-toolbar" id="btn-show-overdue" hidden aria-label="Verlopen">
                                <span class="toolbar-nav-icon" aria-hidden="true">
                                    <svg viewBox="-3 -3 30 30" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3.2 1.8"/></svg>
                                    <span id="overdue-count" class="toolbar-nav-badge" hidden>0</span>
                                </span>
                            </button>
                            <button type="button" class="btn-toolbar" id="btn-show-declined" hidden aria-label="Afgewezen">
                                <span class="toolbar-nav-icon" aria-hidden="true">
                                    <svg viewBox="-3 -3 30 30" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="m15 9-6 6M9 9l6 6"/></svg>
                                    <span id="declined-count" class="toolbar-nav-badge" hidden>0</span>
                                </span>
                            </button>
                            <button type="button" class="btn-toolbar" id="btn-show-archived" hidden aria-label="Archief">
                                <span class="toolbar-nav-icon" aria-hidden="true">
                                    <svg viewBox="-3 -3 30 30" fill="none"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M4 8h16v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8Z"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M2 8h20M10 12h4"/></svg>
                                    <span id="archived-count" class="toolbar-nav-badge" hidden>0</span>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="driver-app-header__end" aria-hidden="true"></div>
            </div>
            <div class="dispatch-banners">
                <div id="driver-vehicle-row" class="driver-vehicle-row" hidden>
                    <label for="driver-vehicle-select">Voertuig</label>
                    <select id="driver-vehicle-select" aria-label="Voertuig dat je nu bestuurt">
                        <option value="">Kies kenteken</option>
                    </select>
                </div>
                <div id="account-inactive-banner" class="banner-inactive" hidden role="alert">
                    Je chauffeuraccount is nog niet actief. Neem contact op met je werkgever of beheerder.
                </div>
                <div id="ios-awake-hint" class="banner-ios-awake" hidden role="note">
                    <button type="button" class="banner-dismiss-btn" id="btn-dismiss-ios-awake-hint" aria-label="Melding sluiten">×</button>
                    <strong>iPhone:</strong> het scherm blijft aan zolang je <strong>online</strong> bent.
                    Gaat het scherm toch uit? Tik één keer op het scherm om dit opnieuw te activeren.
                </div>
                <div id="notifications-hint" class="banner-notifications-hint" hidden>
                    <button type="button" class="banner-dismiss-btn" id="btn-dismiss-notifications-hint" aria-label="Melding sluiten">×</button>
                    <span id="notifications-hint-text">Voor een geluid en melding op je telefoon bij nieuwe ritten: sta meldingen toe voor deze app.</span>
                    <button type="button" class="btn-inline" id="btn-enable-notifications">Meldingen inschakelen</button>
                </div>
                <div id="absence-alert-banner" class="banner-ios-awake" hidden role="alert">
                    <button type="button" class="banner-dismiss-btn" id="btn-dismiss-absence-alert" aria-label="Melding sluiten">×</button>
                    <span id="absence-alert-text"></span>
                </div>
                <p id="notifications-feedback" hidden role="status" aria-live="polite">
                    <button type="button" class="banner-dismiss-btn" id="btn-dismiss-notifications-feedback" aria-label="Melding sluiten">×</button>
                    <span id="notifications-feedback-text"></span>
                </p>
            </div>
            <div class="card toggle-row">
                <span>Online voor ritten</span>
                <button type="button" id="online-toggle-legacy" class="switch" aria-pressed="false" aria-label="Online" hidden disabled></button>
            </div>
            <h1 id="dispatch-toolbar-title" class="dispatch-screen-title">Aanvragen</h1>
        </div>
        <div class="dispatch-scroll">
        <div id="tab-panel-requests" class="driver-tab-panel" data-main-tab-panel="requests">
        <div class="driver-section-head driver-section-head--with-filter" id="requests-section-head">
            <h2 id="requests-section-title">Nieuwe ritaanvraag</h2>
            <div class="ride-kind-filter" role="group" aria-label="Toon rittype">
                <button type="button" class="ride-kind-filter__btn is-active" data-ride-kind="all" aria-pressed="true">Alles</button>
                <button type="button" class="ride-kind-filter__btn" data-ride-kind="taxi" aria-pressed="false">Taxi</button>
                <button type="button" class="ride-kind-filter__btn" data-ride-kind="contract" aria-pressed="false">Contract</button>
            </div>
            <span class="driver-section-head__icon" id="requests-section-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0"/></svg>
            </span>
        </div>
        <div id="unclaimed-rides-banner" class="banner-unclaimed" hidden role="alert"></div>
        <div id="overdue-strip" hidden>
            <p class="offer-meta" id="overdue-hint">
                Ritten die je hebt afgewezen of die na het grace-venster zijn verlopen. Na alsnog accepteren met een nieuw tijdstip blijft de rit onder Aanvragen tot de klant reageert.
            </p>
            <div id="overdue-rides-list"></div>
            <div id="overdue-empty" class="empty" hidden>
                <p>Geen verlopen openstaande ritten.</p>
                <button type="button" class="btn btn-ghost" id="btn-empty-show-archived" hidden>Bekijk archief</button>
            </div>
        </div>
        <div id="archived-strip" hidden>
            <p class="offer-meta" id="archived-hint">
                Gearchiveerde verlopen ritten. Selecteer er een of meer om permanent te verwijderen.
            </p>
            <div id="archived-bulk-bar" class="archived-bulk-bar" hidden>
                <label class="archived-bulk-bar__select-all">
                    <input type="checkbox" id="archived-select-all">
                    <span>Alles selecteren</span>
                </label>
                <button type="button" class="btn btn-danger" id="btn-archived-delete-selected" disabled>Verwijderen</button>
            </div>
            <div id="archived-rides-list"></div>
            <div id="archived-empty" class="empty" hidden>
                <p>Geen gearchiveerde ritten.</p>
            </div>
        </div>
        <div id="declined-strip" hidden>
            <p class="scheduled-rides-title">Door jou afgewezen</p>
            <p class="offer-meta" id="declined-hint">
                Per ongeluk afgewezen? Je kunt een rit hier alsnog accepteren. Ritten waarvan de klant een nieuw tijdstip weigerde kun je archiveren.
            </p>
            <div id="declined-rides-list"></div>
            <div id="declined-empty" class="empty" hidden>
                <p>Je hebt nog geen ritten afgewezen.</p>
            </div>
        </div>
        <div id="pending-approval-strip" hidden>
            <p class="scheduled-rides-title">Wacht op goedkeuring klant</p>
            <p class="offer-meta" id="pending-approval-hint">
                De rit blijft hier tot de klant via WhatsApp reageert. Reageert de klant niet, dan kun je de rit archiveren; een late reactie komt terug als nieuwe aanvraag.
            </p>
            <div id="pending-approval-list"></div>
        </div>
        <div id="offer-strip" hidden>
            <div id="offer-container">
                <div class="card offer-card" id="offer-card">
                    <div class="offer-card-top">
                        <div class="offer-badge-row">
                            <span class="offer-badge" id="offer-badge">Nieuw</span>
                            <span class="offer-waiting-dot" id="offer-waiting-dot" hidden aria-hidden="true"></span>
                        </div>
                        <div class="offer-card-meta-right">
                            <span class="offer-ago" id="offer-ago"></span>
                            <span class="offer-vehicle-pill" id="offer-vehicle-badge">Sedan</span>
                        </div>
                    </div>
                    <p class="offer-waiting-banner" id="offer-waiting-banner" role="status" aria-live="polite"></p>
                    <p class="offer-title" id="offer-title" hidden>Nieuwe rit</p>
                    <div id="offer-return-meta" hidden></div>
                    <p class="offer-meta" id="offer-queue-hint" hidden style="margin:-0.25rem 0 0.5rem;font-size:0.8125rem;">
                        Je reageert op deze rit. Andere openstaande ritten blijven wachten tot je afwijst of accepteert.
                    </p>
                    <div id="offer-queue-nav" class="offer-queue-nav" hidden>
                        <button type="button" class="btn btn-queue" id="btn-offer-prev" aria-label="Vorige rit">← Vorige</button>
                        <span class="offer-queue-label" id="offer-queue-label">Rit 1 van 1</span>
                        <button type="button" class="btn btn-queue" id="btn-offer-next" aria-label="Volgende rit">Volgende →</button>
                    </div>
                    <p class="offer-meta offer-pickup-at" id="offer-pickup-at" hidden></p>
                    <div class="offer-body-grid">
                        <div class="offer-route">
                            <div class="offer-route-stop">
                                <div class="offer-route-head">
                                    <span class="offer-route-dot offer-route-dot--pickup" aria-hidden="true"></span>
                                    <p class="offer-route-label">Ophalen</p>
                                </div>
                                <a id="offer-pickup" class="offer-route-link" href="#" target="_blank" rel="noopener noreferrer">—</a>
                            </div>
                            <div class="offer-route-stop">
                                <div class="offer-route-head">
                                    <span class="offer-route-dot offer-route-dot--dropoff" aria-hidden="true"></span>
                                    <p class="offer-route-label">Afzetten</p>
                                </div>
                                <a id="offer-dropoff" class="offer-route-link" href="#" target="_blank" rel="noopener noreferrer">—</a>
                            </div>
                        </div>
                        <div class="offer-meta-grid">
                            <div class="offer-customer-block" id="offer-customer"></div>
                            <div class="offer-stats">
                                <div class="offer-price-wrap" id="offer-price"></div>
                                <p class="offer-stats-line" id="offer-stats-distance"></p>
                                <p class="offer-stats-line" id="offer-stats-duration"></p>
                            </div>
                        </div>
                    </div>
                    <div id="offer-actions-panel" class="offer-actions">
                        <button type="button" class="btn btn-danger" id="btn-decline">Weigeren</button>
                        <button type="button" class="btn btn-accept" id="btn-accept">Accepteren</button>
                    </div>
                </div>
            </div>
        </div>
        <div id="inbox-empty" class="empty">
            <div id="inbox-empty-icon" class="inbox-empty-icon" data-state="no-rides" aria-hidden="true">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M5 11h14" />
                    <path d="M6 11l1.2-3.6A1.5 1.5 0 0 1 8.62 6h6.76a1.5 1.5 0 0 1 1.42 1.04L18 11" />
                    <path d="M6 11v5a1 1 0 0 0 1 1h1" />
                    <path d="M16 17h1a1 1 0 0 0 1-1v-5" />
                    <circle cx="8" cy="17" r="1.35" />
                    <circle cx="16" cy="17" r="1.35" />
                    <path d="M9 17h6" />
                </svg>
            </div>
            <p id="inbox-empty-title">Geen openstaande ritten.</p>
            <p id="inbox-empty-hint" style="font-size:0.8125rem">Zet je status op online om aanbiedingen te ontvangen.</p>
            <div id="inbox-empty-actions" class="inbox-empty-actions" hidden>
                <button type="button" class="btn btn-ghost" id="btn-empty-show-overdue" hidden>Bekijk verlopen ritten</button>
                <button type="button" class="btn btn-ghost" id="btn-empty-show-declined" hidden>Bekijk afgewezen ritten</button>
                <button type="button" class="btn btn-ghost" id="btn-empty-show-archived-inbox" hidden>Bekijk archief</button>
            </div>
        </div>
        </div>

        <div id="tab-panel-trips" class="driver-tab-panel" data-main-tab-panel="trips" hidden>
        <div class="driver-section-head driver-section-head--with-filter"><h2>Ritten</h2>
            <div class="ride-kind-filter" role="group" aria-label="Toon rittype">
                <button type="button" class="ride-kind-filter__btn is-active" data-ride-kind="all" aria-pressed="true">Alles</button>
                <button type="button" class="ride-kind-filter__btn" data-ride-kind="taxi" aria-pressed="false">Taxi</button>
                <button type="button" class="ride-kind-filter__btn" data-ride-kind="contract" aria-pressed="false">Contract</button>
            </div>
        </div>
        <div id="parked-assigned-rides-strip" class="parked-assigned-rides-strip" hidden>
            <p class="scheduled-rides-title">Jouw actieve ritten</p>
            <div id="parked-assigned-rides-list"></div>
        </div>
        <div id="scheduled-rides-strip" class="scheduled-rides-strip" hidden>
            <p class="scheduled-rides-title">Geplande ritten</p>
            <div id="scheduled-rides-list"></div>
        </div>
        <div id="active-ride-strip" class="card offer-card active-ride-card" hidden>
            <div id="active-ride"></div>
            <p id="payment-ride-error" hidden role="alert"></p>
            <div class="offer-actions active-ride-actions" id="active-ride-actions">
                <button type="button" class="btn" id="btn-pay-ride" hidden>Betalen</button>
                <button type="button" class="btn" id="btn-send-invoice" hidden>Factuur versturen</button>
                <button type="button" class="btn btn-primary" id="btn-start-return" hidden>Retour starten</button>
                <button type="button" class="btn btn-ghost" id="btn-release-return" hidden>Retour vrijgeven</button>
                <button type="button" class="btn btn-primary" id="btn-complete-ride" hidden>Rit afronden</button>
            </div>
        </div>
        <div id="trips-empty" class="empty" hidden>
            <p id="trips-empty-title">Geen actieve of geplande ritten.</p>
            <p id="trips-empty-hint" class="offer-meta" style="margin-top:0.5rem;">
                Geaccepteerde ritten verschijnen hier. Nieuwe aanvragen staan onder Aanvragen.
            </p>
        </div>
        </div>

        <div id="tab-panel-planning" class="driver-tab-panel" data-main-tab-panel="planning" hidden>
            <div class="driver-section-head driver-section-head--with-filter">
                <h2>Planning</h2>
                <div class="ride-kind-filter" role="group" aria-label="Toon rittype">
                    <button type="button" class="ride-kind-filter__btn is-active" data-ride-kind="all" aria-pressed="true">Alles</button>
                    <button type="button" class="ride-kind-filter__btn" data-ride-kind="taxi" aria-pressed="false">Taxi</button>
                    <button type="button" class="ride-kind-filter__btn" data-ride-kind="contract" aria-pressed="false">Contract</button>
                </div>
                <div class="planning-view-toggle" role="tablist" aria-label="Planningweergave">
                    <button type="button" class="planning-view-toggle__btn is-active" data-planning-view="day" aria-selected="true">Dag</button>
                    <button type="button" class="planning-view-toggle__btn" data-planning-view="week" aria-selected="false">Week</button>
                </div>
            </div>
            <p id="planning-error" class="earnings-error" hidden role="alert"></p>
            <p id="planning-loading" class="earnings-loading" hidden>Planning laden…</p>
            <div id="planning-body"></div>
        </div>

        <div id="tab-panel-navigation" class="driver-tab-panel navigation-panel" data-main-tab-panel="navigation" hidden>
            <div id="navigation-map" class="navigation-map" role="region" aria-label="Routekaart"></div>
            <div class="navigation-sheet">
                <p id="navigation-status" class="navigation-status">Start een rit om te navigeren.</p>
                <ol id="navigation-stops" class="navigation-stops" hidden></ol>
                <button type="button" class="btn btn-primary" id="btn-start-navigation" disabled>Start navigatie</button>
            </div>
        </div>

        <div id="tab-panel-earnings" class="driver-tab-panel earnings-panel" data-main-tab-panel="earnings" hidden>
            <div class="driver-section-head">
                <h2>Inkomsten</h2>
                <button type="button" class="driver-section-head__today" id="btn-earnings-today" disabled>Vandaag</button>
            </div>
            <div class="earnings-day-nav" id="earnings-day-nav">
                <button type="button" class="earnings-day-nav__btn" id="btn-earnings-prev" aria-label="Vorige dag">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M15 6l-6 6 6 6"/></svg>
                </button>
                <div class="earnings-day-nav__meta">
                    <p class="earnings-day-nav__label" id="earnings-day-label">Vandaag</p>
                    <p class="earnings-day-nav__sub" id="earnings-day-sub"></p>
                </div>
                <button type="button" class="earnings-day-nav__btn" id="btn-earnings-next" aria-label="Volgende dag" disabled>
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/></svg>
                </button>
            </div>
            <div class="earnings-summary" id="earnings-summary" hidden>
                <div class="card offer-card earnings-summary__card">
                    <p class="earnings-summary__label">Totaal deze dag</p>
                    <p class="earnings-summary__value" id="earnings-day-total">€&nbsp;0,00</p>
                    <p class="earnings-summary__hint" id="earnings-day-count">0 ritten</p>
                </div>
                <div class="card offer-card earnings-summary__card" id="earnings-month-card" hidden>
                    <p class="earnings-summary__label" id="earnings-month-label">Totaal deze maand</p>
                    <p class="earnings-summary__value" id="earnings-month-total">€&nbsp;0,00</p>
                    <p class="earnings-summary__hint" id="earnings-month-count">0 ritten</p>
                </div>
            </div>
            <div id="earnings-rides-strip" class="scheduled-rides-strip">
                <p class="scheduled-rides-title">Afgeronde ritten</p>
                <p class="earnings-loading" id="earnings-loading" hidden>Inkomsten laden…</p>
                <p class="earnings-error" id="earnings-error" hidden></p>
                <p class="earnings-empty empty" id="earnings-empty" hidden>Geen afgeronde ritten op deze dag.</p>
                <div class="earnings-rides-list" id="earnings-rides-list"></div>
            </div>
        </div>

        <div id="tab-panel-profile" class="driver-tab-panel profile-panel" data-main-tab-panel="profile" hidden>
            <div class="driver-section-head"><h2>Profiel</h2></div>
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
                            <dt class="profile-info__label">Bedrijf</dt>
                            <dd class="profile-info__value" id="profile-company">—</dd>
                        </div>
                        <div class="profile-info__row">
                            <dt class="profile-info__label">Accountstatus</dt>
                            <dd class="profile-info__value" id="profile-account-status">—</dd>
                        </div>
                    </dl>
                </div>
                @include('taxi::partials.pwa-accent', ['section' => 'picker'])
                @include('taxi::partials.ride-alert-tone', ['section' => 'picker'])
                <p class="offer-meta profile-session-note">Gegevens zijn alleen ter inzage.</p>
                <a class="profile-guide-link" id="profile-guide-link" href="{{ $guideUrl ?? url('/taxi/chauffeur/handleiding') }}">
                    <strong>Handleiding</strong>
                    <span>Openen →</span>
                </a>
                <button type="button" class="btn btn-ghost" id="btn-logout">Uitloggen</button>
            </div>
        </div>

        <div id="payment-panel" hidden aria-label="Betaling">
            <div class="driver-section-head">
                <h2>Betaling</h2>
                <button type="button" class="btn btn-ghost driver-section-head__close" id="btn-payment-close">Sluiten</button>
            </div>
            <div class="card">
                <p class="offer-meta" style="margin:0 0 0.5rem">Te betalen bedrag</p>
                <label for="payment-amount" class="sr-only">Bedrag in euro</label>
                <div class="payment-amount-wrap">
                    <span class="payment-amount-prefix" aria-hidden="true">€</span>
                    <input type="number" id="payment-amount" class="payment-amount-input" min="0.01" step="0.01" inputmode="decimal">
                </div>
                <div class="payment-actions">
                    <button type="button" class="btn btn-primary" id="btn-payment-create">QR-code tonen</button>
                    <button type="button" class="btn" id="btn-cash-paid">Contant betalen</button>
                </div>
            </div>
            <div id="payment-qr-section" class="card" hidden>
                <p class="offer-meta" style="text-align:center;margin:0 0 0.75rem">Laat de klant deze QR scannen</p>
                <div id="payment-qr-wrap">
                    <img id="payment-qr-img" src="" alt="Mollie betaal QR-code" width="280" height="280">
                </div>
                <p id="payment-status-text" class="offer-meta" style="text-align:center;margin-top:0.75rem" role="status" aria-live="polite">Wachten op betaling…</p>
            </div>
        </div>
        <div id="invoice-panel" hidden aria-label="Factuur versturen">
            <div class="driver-section-head">
                <h2>Factuur versturen</h2>
                <button type="button" class="btn btn-ghost driver-section-head__close" id="btn-invoice-close">Sluiten</button>
            </div>
            <div class="card">
                <p class="offer-meta" style="margin:0 0 0.25rem">De factuur wordt als PDF naar de klant gemaild.</p>
                <label for="invoice-email" class="offer-meta">E-mailadres klant</label>
                <input type="email" id="invoice-email" class="invoice-field-input" inputmode="email" autocomplete="email">
                <label for="invoice-number" class="offer-meta">Factuurnummer</label>
                <input type="text" id="invoice-number" class="invoice-field-input" autocomplete="off">
                <button type="button" class="btn btn-primary" id="btn-invoice-send" style="width:100%;margin-top:0.25rem">Versturen</button>
                <p id="invoice-send-status" hidden role="status" aria-live="polite"></p>
            </div>
        </div>
        </div>
        <div class="dispatch-footer"></div>
        <nav class="driver-bottom-nav" aria-label="Hoofdnavigatie">
            <button type="button" class="driver-bottom-nav__btn is-active" data-main-tab="requests" aria-current="page">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M5 11h14M6 11l1.2-3.6A1.5 1.5 0 0 1 8.6 6h6.8a1.5 1.5 0 0 1 1.4 1.04L18 11M6 11v5a1 1 0 0 0 1 1h1M16 17h1a1 1 0 0 0 1-1v-5"/><circle cx="8" cy="17" r="1.3" stroke="currentColor" stroke-width="2"/><circle cx="16" cy="17" r="1.3" stroke="currentColor" stroke-width="2"/></svg>
                <span>Aanvragen</span>
            </button>
            <button type="button" class="driver-bottom-nav__btn" data-main-tab="trips">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2" stroke="currentColor" stroke-width="2"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M8 3v4M16 3v4M4 10h16"/></svg>
                <span>Ritten</span>
            </button>
            <button type="button" class="driver-bottom-nav__btn" data-main-tab="planning">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2" stroke="currentColor" stroke-width="2"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M8 3v4M16 3v4M4 10h16"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M8 14h2M12 14h2M16 14h.01M8 17h2M12 17h2"/></svg>
                <span>Planning</span>
            </button>
            <button type="button" class="driver-bottom-nav__btn" data-main-tab="navigation">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 3 4.5 20.5 12 16.5l7.5 4L12 3Z"/></svg>
                <span>Navigatie</span>
            </button>
            <button type="button" class="driver-bottom-nav__btn" data-main-tab="earnings" id="nav-tab-earnings" hidden>
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M12 8v8M9.5 10.5c.5-1 1.5-1.5 2.5-1.5s2 .6 2 1.5-1 1.5-2.5 1.5-2.5.5-2.5 1.5.9 1.5 2.5 1.5 2-.5 2.5-1.5"/></svg>
                <span>Inkomsten</span>
            </button>
            <button type="button" class="driver-bottom-nav__btn" data-main-tab="profile">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="9" r="3.5" stroke="currentColor" stroke-width="2"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M5.5 19a6.5 6.5 0 0 1 13 0"/></svg>
                <span>Profiel</span>
            </button>
        </nav>
    </section>

</div>

<div id="pickup-adjust-dialog" class="driver-dialog" hidden aria-hidden="true">
    <div class="driver-dialog__backdrop" data-pickup-adjust-dismiss tabindex="-1"></div>
    <div class="driver-dialog__panel" role="dialog" aria-modal="true" aria-labelledby="pickup-adjust-title">
        <div id="pickup-adjust-ask-step">
            <h2 id="pickup-adjust-title" class="driver-dialog__title">Ophaalmoment aanpassen?</h2>
            <p id="pickup-adjust-current" class="pickup-adjust-current">—</p>
            <p class="driver-dialog__text">Het geplande ophaalmoment is verstreken. Wil je een nieuw moment kiezen?</p>
            <div class="driver-dialog__actions">
                <button type="button" class="btn btn-primary" id="pickup-adjust-keep">Niet aanpassen</button>
                <button type="button" class="btn btn-danger" id="pickup-adjust-change">Aanpassen</button>
                <button type="button" class="btn btn-ghost" id="pickup-adjust-cancel-ask">Annuleren</button>
            </div>
        </div>
        <div id="pickup-adjust-edit-step" hidden>
            <h2 class="driver-dialog__title">Nieuw ophaalmoment</h2>
            <div class="pickup-adjust-datetime-fields">
                <div class="pickup-adjust-datetime-field">
                    <label for="pickup-adjust-date" class="offer-meta">Datum</label>
                    <input type="date" id="pickup-adjust-date" class="driver-field-input pickup-adjust-datetime-input">
                </div>
                <div class="pickup-adjust-datetime-field">
                    <label for="pickup-adjust-time" class="offer-meta">Tijd</label>
                    <input type="time" id="pickup-adjust-time" class="driver-field-input pickup-adjust-datetime-input">
                </div>
            </div>
            <input type="hidden" id="pickup-adjust-input">
            <div class="driver-dialog__actions">
                <button type="button" class="btn btn-ghost" id="pickup-adjust-back">Terug</button>
                <button type="button" class="btn btn-primary" id="pickup-adjust-confirm">Voorstellen</button>
            </div>
        </div>
    </div>
</div>

<div id="cash-confirm-dialog" class="driver-dialog" hidden aria-hidden="true">
    <div class="driver-dialog__backdrop" data-cash-confirm-dismiss tabindex="-1"></div>
    <div class="driver-dialog__panel" role="dialog" aria-modal="true" aria-labelledby="cash-confirm-title">
        <div class="driver-dialog__icon" aria-hidden="true">💵</div>
        <h2 id="cash-confirm-title" class="driver-dialog__title">Contant betalen?</h2>
        <p id="cash-confirm-amount" class="driver-dialog__amount">—</p>
        <p class="driver-dialog__text">Het ingevoerde bedrag wordt vastgelegd op deze rit. Controleer het bedrag voordat je bevestigt.</p>
        <div class="driver-dialog__actions">
            <button type="button" class="btn btn-cash-confirm" id="cash-confirm-ok">Bevestigen</button>
            <button type="button" class="btn btn-ghost" id="cash-confirm-cancel">Annuleren</button>
        </div>
    </div>
</div>

<div id="archive-delete-confirm-dialog" class="driver-dialog" hidden aria-hidden="true">
    <div class="driver-dialog__backdrop" data-archive-delete-dismiss tabindex="-1"></div>
    <div class="driver-dialog__panel" role="dialog" aria-modal="true" aria-labelledby="archive-delete-confirm-title">
        <h2 id="archive-delete-confirm-title" class="driver-dialog__title">Weet u het zeker?</h2>
        <p id="archive-delete-confirm-text" class="driver-dialog__text">
            U staat op het punt deze gearchiveerde rit permanent te verwijderen. Deze wijziging kan niet meer ongedaan worden gemaakt.
        </p>
        <div class="driver-dialog__actions">
            <button type="button" class="btn btn-danger" id="archive-delete-confirm-ok">Definitief verwijderen</button>
            <button type="button" class="btn btn-ghost" id="archive-delete-confirm-cancel">Annuleren</button>
        </div>
    </div>
</div>

<div id="decline-reason-dialog" class="driver-dialog" hidden aria-hidden="true">
    <div class="driver-dialog__backdrop" data-decline-reason-dismiss tabindex="-1"></div>
    <div class="driver-dialog__panel" role="dialog" aria-modal="true" aria-labelledby="decline-reason-title">
        <h2 id="decline-reason-title" class="driver-dialog__title">Rit afwijzen?</h2>
        <p class="driver-dialog__text">Optioneel: voeg een opmerking toe (zichtbaar voor de klant via WhatsApp indien ingesteld).</p>
        <label for="decline-reason-input" class="offer-meta">Opmerking (niet verplicht)</label>
        <textarea id="decline-reason-input" class="driver-field-input" rows="3" maxlength="500" placeholder="Bijv. te ver weg, geen capaciteit…"></textarea>
        <div class="driver-dialog__actions">
            <button type="button" class="btn btn-ghost" id="decline-reason-cancel">Annuleren</button>
            <button type="button" class="btn btn-danger" id="decline-reason-confirm">Afwijzen</button>
        </div>
    </div>
</div>

<div id="driver-notice-dialog" class="driver-dialog" hidden aria-hidden="true">
    <div class="driver-dialog__backdrop" data-driver-notice-dismiss tabindex="-1"></div>
    <div class="driver-dialog__panel" role="alertdialog" aria-modal="true" aria-labelledby="driver-notice-title" aria-describedby="driver-notice-text">
        <div id="driver-notice-icon" class="driver-dialog__icon is-success" aria-hidden="true">✓</div>
        <h2 id="driver-notice-title" class="driver-dialog__title">Gelukt</h2>
        <p id="driver-notice-text" class="driver-dialog__text"></p>
        <div class="driver-dialog__actions">
            <button type="button" class="btn btn-primary" id="driver-notice-ok">Oké</button>
        </div>
    </div>
</div>

<div id="driver-confirm-dialog" class="driver-dialog" hidden aria-hidden="true">
    <div class="driver-dialog__backdrop" data-driver-confirm-dismiss tabindex="-1"></div>
    <div class="driver-dialog__panel" role="dialog" aria-modal="true" aria-labelledby="driver-confirm-title" aria-describedby="driver-confirm-text">
        <div id="driver-confirm-icon" class="driver-dialog__icon is-warn" aria-hidden="true">?</div>
        <h2 id="driver-confirm-title" class="driver-dialog__title">Weet je het zeker?</h2>
        <p id="driver-confirm-text" class="driver-dialog__text"></p>
        <div class="driver-dialog__actions">
            <button type="button" class="btn btn-primary" id="driver-confirm-ok">Bevestigen</button>
            <button type="button" class="btn btn-ghost" id="driver-confirm-cancel">Annuleren</button>
        </div>
    </div>
</div>

<div id="nosleep-media-wrap" aria-hidden="true">
    <video id="nosleep-video" loop muted playsinline webkit-playsinline preload="auto" disablePictureInPicture
        src="{{ asset('assets/media/app/nexa-chauffeur-nosleep.mp4') }}"></video>
</div>
<audio id="nosleep-audio" loop preload="auto" muted playsinline webkit-playsinline aria-hidden="true"
    src="{{ asset('assets/media/app/nexa-chauffeur-nosleep.wav') }}"></audio>
<canvas id="nosleep-canvas" width="1" height="1" aria-hidden="true"></canvas>

<script>
window.NEXA_TAXI_DRIVER = {
    apiBase: @json($apiBase),
    pollMs: {{ (int) $pollMs }},
    streamEnabled: @json($streamEnabled ?? false),
    loginUrl: @json(url('/api/taxi/v1/driver/login')),
    loginCodeRequestUrl: @json(url('/api/taxi/v1/driver/login-code/request')),
    loginCodeVerifyUrl: @json(url('/api/taxi/v1/driver/login-code/verify')),
    appUrl: @json($appUrl ?? url('/taxi/chauffeur')),
    guideUrl: @json($guideUrl ?? url('/taxi/chauffeur/handleiding')),
    notificationIcon: @json($notificationIcon ?? $faviconUrl),
    googleMapsApiKey: @json($googleMapsApiKey ?? ''),
    googleMapsMapId: @json($googleMapsMapId ?? ''),
    googleMapsCenterLat: @json($googleMapsCenterLat ?? '52.3676'),
    googleMapsCenterLng: @json($googleMapsCenterLng ?? '4.9041'),
};
</script>
<script src="{{ asset('assets/js/taxi-pwa-accent.js') }}?v=1" defer></script>
<script src="{{ asset('assets/js/taxi-driver-app.js') }}?v=148" defer></script>
@include('partials.password-toggle')
</body>
</html>
