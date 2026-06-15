<!DOCTYPE html>
<html lang="nl" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#16a34a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="manifest" href="{{ route('taxi.chauffeur.manifest') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/media/app/nexa-chauffeur-icon-32.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('assets/media/app/nexa-chauffeur-icon-192.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/media/app/nexa-chauffeur-icon-180.png') }}">
    <title>Chauffeur – Nexa Taxi</title>
    <style>
        :root {
            --bg: #0f172a;
            --card: #1e293b;
            --text: #f8fafc;
            --muted: #94a3b8;
            --green: #16a34a;
            --red: #dc2626;
            --safe-top: env(safe-area-inset-top, 0px);
            --safe-bottom: env(safe-area-inset-bottom, 0px);
        }
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
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
        #screen-dispatch.is-active {
            display: flex;
            flex-direction: column;
            height: 100dvh;
            max-height: 100dvh;
            overflow: hidden;
            padding-top: calc(1rem + var(--safe-top));
            padding-bottom: calc(1rem + var(--safe-bottom));
        }
        .dispatch-scroll {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
        #offer-strip,
        #active-ride-strip {
            margin-bottom: 1rem;
        }
        #active-ride-strip .card {
            margin-bottom: 0;
        }
        #btn-complete-ride,
        #btn-pay-ride,
        #btn-send-invoice {
            width: 100%;
            margin-top: 0.75rem;
            min-height: 3.25rem;
            font-size: 1.0625rem;
        }
        #btn-pay-ride { background: #ca8a04; color: #fff; }
        #btn-pay-ride.is-paid {
            background: #64748b;
            color: #e2e8f0;
            cursor: not-allowed;
        }
        #payment-ride-error {
            margin-top: 0.75rem;
            padding: 0.625rem 0.75rem;
            border-radius: 0.5rem;
            background: rgba(239, 68, 68, 0.12);
            color: #fca5a5;
            font-size: 0.9375rem;
            line-height: 1.4;
        }
        #payment-panel {
            position: fixed;
            inset: 0;
            z-index: 50;
            background: var(--bg);
            display: none;
            flex-direction: column;
            padding: calc(1rem + var(--safe-top)) 1rem calc(1rem + var(--safe-bottom));
            overflow-y: auto;
        }
        #payment-panel.is-open,
        #invoice-panel.is-open { display: flex; }
        #invoice-panel {
            position: fixed;
            inset: 0;
            z-index: 50;
            background: var(--bg);
            display: none;
            flex-direction: column;
            padding: calc(1rem + var(--safe-top)) 1rem calc(1rem + var(--safe-bottom));
            overflow-y: auto;
        }
        #btn-send-invoice {
            background: #2563eb;
            color: #fff;
        }
        #btn-send-invoice:disabled,
        #btn-send-invoice.is-disabled,
        #btn-complete-ride:disabled,
        #btn-complete-ride.is-disabled {
            background: #64748b;
            color: #e2e8f0;
            cursor: not-allowed;
            opacity: 0.9;
        }
        #invoice-panel .invoice-field-input {
            width: 100%;
            font-size: 1rem;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            border: 1px solid rgba(255,255,255,0.15);
            background: var(--card);
            color: var(--text);
            margin: 0.35rem 0 1rem;
        }
        #invoice-send-status {
            margin-top: 0.75rem;
            font-size: 0.9375rem;
            color: #86efac;
            text-align: center;
        }
        #invoice-send-status.is-error { color: #fca5a5; }
        #payment-panel .payment-amount-input {
            width: 100%;
            font-size: 1.5rem;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            border: 1px solid rgba(255,255,255,0.15);
            background: var(--card);
            color: var(--text);
            margin: 0.5rem 0 1rem;
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
        .driver-dialog {
            position: fixed;
            inset: 0;
            z-index: 60;
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
            border: 1px solid rgba(255, 255, 255, 0.1);
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
            flex: 0 0 auto;
            padding-top: 0.75rem;
            background: var(--bg);
        }
        #offer-actions-panel {
            margin: 0;
        }
        h1 { font-size: 1.25rem; margin: 0 0 1rem; font-weight: 600; }
        .card {
            background: var(--card);
            border-radius: 1rem;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(255,255,255,0.06);
        }
        label { display: block; font-size: 0.8125rem; color: var(--muted); margin-bottom: 0.35rem; }
        input[type="email"], input[type="password"], #login-form input[type="text"] {
            width: 100%;
            padding: 0.85rem 1rem;
            border-radius: 0.75rem;
            border: 1px solid rgba(255,255,255,0.12);
            background: #0b1220;
            color: var(--text);
            font-size: 1rem;
            margin-bottom: 0.75rem;
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
            font-weight: 600;
            cursor: pointer;
            touch-action: manipulation;
        }
        .btn-primary { background: var(--green); color: #fff; }
        .btn-accept { background: #ea580c; color: #fff; }
        .btn-accept:hover { background: #c2410c; }
        .btn-danger { background: var(--red); color: #fff; }
        .btn-ghost { background: transparent; color: var(--muted); border: 1px solid rgba(255,255,255,0.15); }
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
        .status-pill {
            font-size: 0.75rem;
            padding: 0.35rem 0.65rem;
            border-radius: 999px;
            background: rgba(22, 163, 74, 0.2);
            color: #86efac;
        }
        .status-pill.offline { background: rgba(148, 163, 184, 0.2); color: var(--muted); }
        .banner-new-ride,
        .banner-ride-accepted {
            background: rgba(22, 163, 74, 0.25);
            border: 1px solid rgba(22, 163, 74, 0.55);
            color: #bbf7d0;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            font-weight: 600;
            font-size: 0.9375rem;
        }
        .banner-ride-accepted { margin-bottom: 0.75rem; }
        .active-ride-card { overflow: visible; }
        .offer-price { white-space: nowrap; }
        .offer-card { animation: pulse-border 1.5s ease-in-out infinite; }
        @keyframes pulse-border {
            0%, 100% { border-color: rgba(22, 163, 74, 0.4); }
            50% { border-color: rgba(22, 163, 74, 1); }
        }
        .offer-title { font-size: 1.125rem; font-weight: 700; margin: 0 0 0.5rem; }
        .offer-meta { font-size: 0.875rem; color: var(--muted); line-height: 1.45; }
        .offer-address-row {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            margin: 0.65rem 0;
        }
        .offer-address-icon {
            flex-shrink: 0;
            font-size: 1.25rem;
            line-height: 1.35;
        }
        .offer-address {
            flex: 1;
            font-size: 1.0625rem;
            font-weight: 500;
            line-height: 1.4;
            color: #e2e8f0;
            text-decoration: none;
            padding: 0.15rem 0;
            touch-action: manipulation;
        }
        a.offer-address:active,
        a.offer-address:focus-visible {
            color: #86efac;
            text-decoration: underline;
            outline: none;
        }
        a.offer-phone {
            color: #93c5fd;
            font-weight: 600;
            text-decoration: none;
            touch-action: manipulation;
        }
        a.offer-phone:active,
        a.offer-phone:focus-visible {
            color: #bfdbfe;
            text-decoration: underline;
            outline: none;
        }
        .offer-price { font-size: 1.5rem; font-weight: 700; color: #86efac; margin: 0.75rem 0; }
        #offer-container { flex-shrink: 0; }
        .offer-card {
            margin-bottom: 0.75rem;
            overflow: visible;
        }
        .offer-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin: 0 0 1rem;
            flex-shrink: 0;
        }
        .offer-actions .btn { min-height: 3.25rem; font-size: 1.0625rem; }
        .scheduled-rides-strip { margin-bottom: 1rem; }
        .scheduled-rides-title {
            margin: 0 0 0.75rem;
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--muted);
        }
        .scheduled-ride-card + .scheduled-ride-card { margin-top: 0.75rem; }
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
        .scheduled-ride-card .scheduled-ride-toggle .offer-title {
            margin: 0;
        }
        .scheduled-ride-card .scheduled-ride-toggle .scheduled-pickup-at {
            margin: 0;
            font-size: 0.8125rem;
            color: #94a3b8;
        }
        .scheduled-ride-chevron {
            flex-shrink: 0;
            width: 2rem;
            height: 2rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #94a3b8;
            transition: transform 0.2s ease, color 0.2s ease;
        }
        .scheduled-ride-card.is-expanded .scheduled-ride-chevron {
            transform: rotate(180deg);
            color: #e2e8f0;
        }
        .scheduled-ride-body {
            margin-top: 0.85rem;
            padding-top: 0.85rem;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }
        .scheduled-ride-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-top: 0.85rem;
        }
        .scheduled-ride-actions .btn { margin-top: 0; width: 100%; }
        .scheduled-ride-actions .btn-release-ride {
            background: transparent;
            color: #fca5a5;
            border: 1px solid rgba(248, 113, 113, 0.45);
        }
        .scheduled-ride-card .btn-start-ride { margin-top: 0; }
        .offer-pickup-at { margin: 0 0 0.75rem; font-weight: 600; color: #fbbf24; }
        .offer-queue-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            margin: 0.5rem 0 0.75rem;
            padding: 0.5rem 0.65rem;
            border-radius: 0.65rem;
            background: rgba(15, 23, 42, 0.55);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .offer-queue-nav .btn-queue {
            min-height: 2.25rem;
            width: auto;
            padding: 0.4rem 0.75rem;
            font-size: 0.8125rem;
            background: rgba(255, 255, 255, 0.08);
            color: var(--text);
            border: 1px solid rgba(255, 255, 255, 0.12);
        }
        .offer-queue-nav .btn-queue:disabled { opacity: 0.35; }
        .offer-queue-label { font-size: 0.8125rem; color: var(--muted); text-align: center; flex: 1; }
        .offer-timer {
            font-size: 0.875rem;
            font-weight: 600;
            color: #fbbf24;
            margin: 0 0 0.5rem;
        }
        .offer-timer.is-urgent {
            color: #fb923c;
        }
        .offer-timer.is-waiting,
        .offer-timer-wait.is-waiting {
            color: #f87171;
            font-size: 1rem;
            animation: offer-waiting-pulse 1.25s ease-in-out infinite;
        }
        .offer-timer-accept {
            color: #fbbf24;
            font-size: 0.875rem;
            font-weight: 600;
            margin: 0 0 0.35rem;
        }
        .offer-timer-wait {
            margin: 0 0 0.25rem;
        }
        .offer-waiting-banner {
            display: none;
            margin: 0 0 0.65rem;
            padding: 0.65rem 0.75rem;
            border-radius: 0.65rem;
            background: rgba(220, 38, 38, 0.18);
            border: 1px solid rgba(248, 113, 113, 0.55);
            color: #fecaca;
            font-size: 0.8125rem;
            font-weight: 600;
            line-height: 1.4;
        }
        .offer-waiting-banner.is-visible {
            display: block;
            animation: offer-waiting-pulse 1.25s ease-in-out infinite;
        }
        .offer-card.is-waiting {
            border-color: rgba(248, 113, 113, 0.55);
            box-shadow: 0 0 0 1px rgba(220, 38, 38, 0.25);
        }
        @keyframes offer-waiting-pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.72; }
        }
        .empty { text-align: center; color: var(--muted); padding: 2rem 1rem; }
        .error { color: #fca5a5; font-size: 0.875rem; margin-top: 0.5rem; }
        .toggle-row { display: flex; align-items: center; justify-content: space-between; }
        .switch {
            width: 3.25rem; height: 1.85rem; background: #334155; border-radius: 999px; position: relative; border: none; cursor: pointer;
        }
        .switch::after {
            content: ''; position: absolute; top: 0.2rem; left: 0.2rem; width: 1.45rem; height: 1.45rem;
            background: #fff; border-radius: 50%; transition: transform 0.2s;
        }
        .switch.is-on { background: var(--green); }
        .switch.is-on::after { transform: translateX(1.35rem); }
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
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .btn-toolbar {
            width: auto;
            min-height: auto;
            padding: 0.35rem 0.65rem;
            font-size: 0.8125rem;
            white-space: nowrap;
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
        .overdue-ride-actions .btn-release-ride {
            background: #ea580c;
            color: #fff;
            border: none;
        }
        .overdue-ride-actions .btn-release-ride:hover {
            background: #c2410c;
        }
        .banner-offline-hint {
            background: rgba(148, 163, 184, 0.12);
            border: 1px solid rgba(148, 163, 184, 0.25);
            color: var(--muted);
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            font-size: 0.8125rem;
            margin-bottom: 1rem;
        }
        .banner-notifications-hint {
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
        .banner-notifications-hint .btn-inline {
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
    </style>
</head>
<body>
<div id="app">
    <section id="screen-login" class="screen is-active" aria-label="Inloggen">
        <h1>Chauffeur inloggen</h1>
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

    <section id="screen-dispatch" class="screen" aria-label="Ritten">
        <div class="dispatch-scroll">
        <div id="account-inactive-banner" class="banner-inactive" hidden role="alert">
            Je chauffeuraccount is nog niet actief. Neem contact op met je werkgever of beheerder.
        </div>
        <div id="offline-hint" class="banner-offline-hint" hidden>
            Je bent offline. Zet je status op <strong>online</strong> om ritten te ontvangen.
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
        <p id="notifications-feedback" hidden role="status" aria-live="polite">
            <button type="button" class="banner-dismiss-btn" id="btn-dismiss-notifications-feedback" aria-label="Melding sluiten">×</button>
            <span id="notifications-feedback-text"></span>
        </p>
        <div id="new-ride-alert" class="banner-new-ride" hidden role="status" aria-live="polite">Nieuwe rit beschikbaar — reageer snel.</div>
        <div id="unclaimed-rides-banner" class="banner-unclaimed" hidden role="alert"></div>
        <div class="toolbar">
            <h1 id="dispatch-toolbar-title" style="margin:0">Ritten</h1>
            <div class="toolbar-actions">
                <button type="button" class="btn btn-ghost btn-toolbar" id="btn-show-offers" hidden>Ritten <span id="offers-count">(0)</span></button>
                <button type="button" class="btn btn-ghost btn-toolbar" id="btn-show-overdue" hidden>Verlopen <span id="overdue-count">(0)</span></button>
                <button type="button" class="btn btn-ghost btn-toolbar" id="btn-show-declined" hidden>Afgewezen <span id="declined-count">(0)</span></button>
                <span id="online-pill" class="status-pill offline">Offline</span>
            </div>
        </div>
        <div class="card toggle-row">
            <span>Online voor ritten</span>
            <button type="button" id="online-toggle" class="switch" aria-pressed="false" aria-label="Online"></button>
        </div>
        <div id="scheduled-rides-strip" class="scheduled-rides-strip" hidden>
            <p class="scheduled-rides-title">Geplande ritten</p>
            <div id="scheduled-rides-list"></div>
        </div>
        <div id="overdue-strip" hidden>
            <p class="scheduled-rides-title">Verlopen geplande ritten</p>
            <p class="offer-meta" id="overdue-hint" style="margin:-0.25rem 0 0.75rem;font-size:0.8125rem;color:#94a3b8;">
                Het ophaalmoment plus de acceptatietijd is verstreken. Start de rit alsnog bij vertraging, of geef hem vrij.
            </p>
            <div id="overdue-rides-list"></div>
            <div id="overdue-empty" class="empty" hidden>
                <p>Geen verlopen geplande ritten.</p>
            </div>
        </div>
        <div id="declined-strip" hidden>
            <p class="scheduled-rides-title">Door jou afgewezen</p>
            <p class="offer-meta" id="declined-hint" style="margin:-0.25rem 0 0.75rem;font-size:0.8125rem;color:#94a3b8;">
                Per ongeluk afgewezen? Je kunt een rit hier alsnog accepteren. Andere chauffeurs kunnen openstaande ritten ook nog overnemen.
            </p>
            <div id="declined-rides-list"></div>
            <div id="declined-empty" class="empty" hidden>
                <p>Je hebt nog geen ritten afgewezen.</p>
            </div>
        </div>
        <div id="offer-strip" hidden>
            <div id="offer-container">
                <div class="card offer-card" id="offer-card">
                    <p class="offer-title" id="offer-title">Nieuwe rit</p>
                    <p class="offer-meta" id="offer-queue-hint" hidden style="margin:-0.25rem 0 0.5rem;font-size:0.8125rem;color:#94a3b8;">
                        Je reageert op deze rit. Andere openstaande ritten blijven wachten tot je afwijst of accepteert.
                    </p>
                    <div id="offer-queue-nav" class="offer-queue-nav" hidden>
                        <button type="button" class="btn btn-queue" id="btn-offer-prev" aria-label="Vorige rit">← Vorige</button>
                        <span class="offer-queue-label" id="offer-queue-label">Rit 1 van 1</span>
                        <button type="button" class="btn btn-queue" id="btn-offer-next" aria-label="Volgende rit">Volgende →</button>
                    </div>
                    <p class="offer-waiting-banner" id="offer-waiting-banner" role="status" aria-live="polite"></p>
                    <p class="offer-meta offer-pickup-at" id="offer-pickup-at" hidden></p>
                    <p class="offer-timer offer-timer-wait is-waiting" id="offer-timer-wait" hidden aria-live="polite"></p>
                    <p class="offer-timer offer-timer-accept" id="offer-timer-accept" hidden aria-live="polite"></p>
                    <p class="offer-address-row">
                        <span class="offer-address-icon" aria-hidden="true">📍</span>
                        <a id="offer-pickup" class="offer-address" href="#" target="_blank" rel="noopener noreferrer">—</a>
                    </p>
                    <p class="offer-address-row">
                        <span class="offer-address-icon" aria-hidden="true">🏁</span>
                        <a id="offer-dropoff" class="offer-address" href="#" target="_blank" rel="noopener noreferrer">—</a>
                    </p>
                    <p class="offer-meta" id="offer-customer"></p>
                    <p class="offer-price" id="offer-price"></p>
                </div>
            </div>
            <div id="offer-actions-panel" class="offer-actions">
                <button type="button" class="btn btn-danger" id="btn-decline">Afwijzen</button>
                <button type="button" class="btn btn-accept" id="btn-accept">Accepteren</button>
            </div>
        </div>
        <div id="active-ride-strip" hidden>
            <div id="active-ride" class="card active-ride-card"></div>
            <p id="payment-ride-error" hidden role="alert"></p>
            <button type="button" class="btn" id="btn-pay-ride" hidden>Betalen</button>
            <button type="button" class="btn" id="btn-send-invoice" hidden>Factuur versturen</button>
            <button type="button" class="btn btn-primary" id="btn-complete-ride" hidden>Rit afgerond</button>
        </div>
        <div id="payment-panel" hidden aria-label="Betaling">
            <div class="toolbar" style="margin-bottom:1rem">
                <h1 style="margin:0;font-size:1.125rem">Betaling</h1>
                <button type="button" class="btn btn-ghost" id="btn-payment-close" style="width:auto;min-height:auto;padding:0.35rem 0.75rem">Sluiten</button>
            </div>
            <div class="card">
                <p class="offer-meta" style="margin:0 0 0.5rem">Te betalen bedrag</p>
                <label for="payment-amount" class="sr-only">Bedrag in euro</label>
                <input type="number" id="payment-amount" class="payment-amount-input" min="0.01" step="0.01" inputmode="decimal">
                <div class="payment-actions">
                    <button type="button" class="btn btn-primary" id="btn-payment-create">QR-code tonen</button>
                    <button type="button" class="btn" id="btn-cash-paid">Cash betaald</button>
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
            <div class="toolbar" style="margin-bottom:1rem">
                <h1 style="margin:0;font-size:1.125rem">Factuur versturen</h1>
                <button type="button" class="btn btn-ghost" id="btn-invoice-close" style="width:auto;min-height:auto;padding:0.35rem 0.75rem">Sluiten</button>
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
        <div id="inbox-empty" class="empty">
            <p id="inbox-empty-title">Geen openstaande ritten.</p>
            <p id="inbox-empty-hint" style="font-size:0.8125rem">Zet je status op online om aanbiedingen te ontvangen.</p>
        </div>
        </div>
        <div class="dispatch-footer">
            <button type="button" class="btn btn-ghost" id="btn-logout">Uitloggen</button>
        </div>
    </section>

</div>

<div id="cash-confirm-dialog" class="driver-dialog" hidden aria-hidden="true">
    <div class="driver-dialog__backdrop" data-cash-confirm-dismiss tabindex="-1"></div>
    <div class="driver-dialog__panel" role="dialog" aria-modal="true" aria-labelledby="cash-confirm-title">
        <div class="driver-dialog__icon" aria-hidden="true">💵</div>
        <h2 id="cash-confirm-title" class="driver-dialog__title">Contant betaald?</h2>
        <p id="cash-confirm-amount" class="driver-dialog__amount">—</p>
        <p class="driver-dialog__text">Het ingevoerde bedrag wordt vastgelegd op deze rit. Controleer het bedrag voordat je bevestigt.</p>
        <div class="driver-dialog__actions">
            <button type="button" class="btn btn-cash-confirm" id="cash-confirm-ok">Bevestigen</button>
            <button type="button" class="btn btn-ghost" id="cash-confirm-cancel">Annuleren</button>
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
    appUrl: @json($appUrl ?? url('/taxi/chauffeur')),
    notificationIcon: @json($notificationIcon ?? asset('assets/media/app/nexa-chauffeur-icon-192.png')),
};
</script>
<script src="{{ asset('assets/js/taxi-driver-app.js') }}?v=47" defer></script>
@include('partials.password-toggle')
</body>
</html>
