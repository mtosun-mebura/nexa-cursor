<!DOCTYPE html>
<html lang="nl" class="h-full" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#f97316">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Handleiding chauffeur – Nexa Taxi</title>
    <link rel="icon" href="{{ $faviconUrl }}" type="{{ $faviconType }}">
    @include('taxi::partials.pwa-theme', ['section' => 'boot'])
    @include('taxi::partials.pwa-theme', ['section' => 'styles'])
    <style>
        :root {
            --bg: #121214;
            --card: #1c1c1e;
            --text: #ffffff;
            --muted: #9ca3af;
            --orange: #f97316;
            --line: rgba(255,255,255,0.1);
            --safe-top: env(safe-area-inset-top, 0px);
            --safe-bottom: env(safe-area-inset-bottom, 0px);
        }
        html[data-theme="light"] {
            --bg: #f1f5f9;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --line: rgba(15, 23, 42, 0.12);
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; }
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg);
            color: var(--text);
            padding-bottom: calc(1.5rem + var(--safe-bottom));
            --nexa-pwa-theme-top: calc(1.15rem + env(safe-area-inset-top, 0px));
        }
        .guide-top {
            position: sticky;
            top: 0;
            z-index: 20;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-height: 4.25rem;
            padding: calc(1.15rem + var(--safe-top)) 4rem 1.15rem 1rem;
            background: var(--bg);
            border-bottom: 1px solid var(--line);
        }
        .guide-top a.back {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            color: var(--orange);
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            white-space: nowrap;
        }
        .guide-top h1 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
            flex: 1;
        }
        .guide-wrap {
            max-width: 42rem;
            margin: 0 auto;
            padding: 1.25rem 1rem 2rem;
        }
        .guide-intro {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 1rem;
            padding: 1rem 1.1rem;
            margin-bottom: 1.25rem;
            line-height: 1.5;
        }
        .guide-intro p { margin: 0 0 0.65rem; color: var(--muted); font-size: 0.9375rem; }
        .guide-intro p:last-child { margin-bottom: 0; }
        .guide-toc {
            display: grid;
            gap: 0.45rem;
            margin: 0 0 1.75rem;
        }
        .guide-toc a {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 0.75rem;
            padding: 0.7rem 0.85rem;
            color: var(--text);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .guide-toc span.num {
            flex-shrink: 0;
            width: 1.6rem;
            height: 1.6rem;
            border-radius: 999px;
            background: var(--orange);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
        }
        .guide-section {
            margin: 0 0 2rem;
            scroll-margin-top: 5.5rem;
        }
        .guide-section h2 {
            margin: 0 0 0.65rem;
            font-size: 1.2rem;
        }
        .guide-section p, .guide-section li {
            color: var(--muted);
            font-size: 0.9375rem;
            line-height: 1.5;
        }
        .guide-section p { margin: 0 0 0.75rem; }
        .guide-steps { margin: 0 0 1rem; padding: 0; list-style: none; display: grid; gap: 0.65rem; }
        .guide-steps li {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 0.85rem;
            padding: 0.8rem 0.9rem 0.8rem 2.7rem;
            position: relative;
        }
        .guide-steps li::before {
            content: attr(data-step);
            position: absolute;
            left: 0.7rem;
            top: 0.8rem;
            width: 1.4rem;
            height: 1.4rem;
            border-radius: 999px;
            background: rgba(249, 115, 22, 0.18);
            color: var(--orange);
            font-size: 0.75rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .guide-steps strong { color: var(--text); display: block; margin-bottom: 0.2rem; }
        .guide-shot {
            margin: 0.75rem 0 1.1rem;
        }
        .guide-shot figcaption {
            margin-top: 0.5rem;
            font-size: 0.8rem;
            color: var(--muted);
            text-align: center;
        }
        .phone-frame {
            max-width: 18rem;
            margin: 0 auto;
            background: #0b0b0d;
            border-radius: 1.6rem;
            padding: 0.7rem 0.55rem 1rem;
            border: 1px solid rgba(255,255,255,0.12);
            box-shadow: 0 12px 40px rgba(0,0,0,0.35);
        }
        html[data-theme="light"] .phone-frame {
            background: #e2e8f0;
            border-color: rgba(15,23,42,0.12);
        }
        .phone-frame img {
            display: block;
            width: 100%;
            border-radius: 1rem;
            background: #111;
        }
        .guide-zoom {
            display: block;
            width: 100%;
            max-width: 18rem;
            margin: 0 auto;
            padding: 0;
            border: 0;
            background: transparent;
            cursor: zoom-in;
            color: inherit;
            font: inherit;
            text-align: inherit;
        }
        .guide-zoom .phone-frame {
            margin: 0;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .guide-zoom:hover .phone-frame,
        .guide-zoom:focus-visible .phone-frame {
            transform: scale(1.02);
            box-shadow: 0 16px 44px rgba(0,0,0,0.45);
        }
        .guide-zoom:focus-visible {
            outline: 2px solid var(--orange);
            outline-offset: 4px;
            border-radius: 1.6rem;
        }
        .guide-zoom-hint {
            display: block;
            margin-top: 0.35rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--orange);
        }
        .guide-lightbox {
            position: fixed;
            inset: 0;
            z-index: 400;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: rgba(2, 6, 23, 0.88);
        }
        .guide-lightbox.is-open {
            display: flex;
        }
        .guide-lightbox__inner {
            position: relative;
            max-width: min(96vw, 1100px);
            max-height: 92vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.65rem;
        }
        .guide-lightbox__img {
            display: block;
            max-width: min(96vw, 1100px);
            max-height: calc(92vh - 3.5rem);
            width: auto;
            height: auto;
            object-fit: contain;
            border-radius: 0.85rem;
            background: #111;
            box-shadow: 0 24px 60px rgba(0,0,0,0.5);
        }
        .guide-lightbox__caption {
            margin: 0;
            color: #e2e8f0;
            font-size: 0.9rem;
            font-weight: 600;
            text-align: center;
        }
        .guide-lightbox__close {
            position: absolute;
            top: -0.5rem;
            right: -0.5rem;
            width: 2.5rem;
            height: 2.5rem;
            border: 0;
            border-radius: 999px;
            background: #fff;
            color: #0f172a;
            font-size: 1.5rem;
            line-height: 1;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        }
        .mock {
            background: #121214;
            color: #fff;
            border-radius: 1rem;
            padding: 0.9rem 0.8rem 1rem;
            font-size: 0.78rem;
        }
        html[data-theme="light"] .mock { background: #0f172a; }
        .mock h3 { margin: 0 0 0.75rem; font-size: 0.95rem; }
        .mock-field { margin-bottom: 0.55rem; }
        .mock-field span { display: block; color: #9ca3af; font-size: 0.7rem; margin-bottom: 0.2rem; }
        .mock-input { height: 2.1rem; border-radius: 0.55rem; background: #252528; border: 1px solid rgba(255,255,255,0.1); }
        .mock-btn { height: 2.3rem; border-radius: 0.55rem; background: #f97316; margin-top: 0.45rem; }
        .mock-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #1c1c1e;
            border-radius: 0.65rem;
            padding: 0.55rem 0.7rem;
            margin-bottom: 0.55rem;
        }
        .mock-switch {
            width: 2.4rem;
            height: 1.35rem;
            border-radius: 999px;
            background: #22c55e;
            position: relative;
        }
        .mock-switch::after {
            content: '';
            position: absolute;
            width: 1.05rem;
            height: 1.05rem;
            background: #fff;
            border-radius: 50%;
            top: 0.15rem;
            right: 0.15rem;
        }
        .mock-nav {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.25rem;
            margin-top: 0.7rem;
            padding-top: 0.55rem;
            border-top: 1px solid rgba(255,255,255,0.08);
            text-align: center;
            color: #9ca3af;
            font-size: 0.65rem;
        }
        .mock-nav b { color: #f97316; display: block; font-weight: 700; }
        .guide-tip {
            background: rgba(249, 115, 22, 0.12);
            border: 1px solid rgba(249, 115, 22, 0.35);
            color: var(--text);
            border-radius: 0.85rem;
            padding: 0.8rem 0.95rem;
            font-size: 0.875rem;
            line-height: 1.45;
            margin: 0.75rem 0 0;
        }
        .guide-tip strong { color: var(--orange); }
        .guide-icon-strip {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.2rem;
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 0.85rem;
            padding: 0.7rem 0.75rem;
            margin: 0 0 0.9rem;
        }
        .guide-icons {
            margin: 0 0 1rem;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 0.65rem;
        }
        .guide-icons li {
            display: grid;
            grid-template-columns: 3.1rem 1fr;
            gap: 0.85rem;
            align-items: start;
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 0.85rem;
            padding: 0.85rem 0.95rem;
        }
        .guide-icon-pic {
            position: relative;
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 0.75rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--muted);
            background: rgba(255,255,255,0.04);
            flex-shrink: 0;
        }
        html[data-theme="light"] .guide-icon-pic {
            background: rgba(15, 23, 42, 0.06);
        }
        .guide-icon-pic svg {
            width: 1.85rem;
            height: 1.85rem;
            display: block;
        }
        .guide-icon-pic.is-ride { color: #22c55e; }
        .guide-icon-pic.is-open { color: var(--orange); }
        .guide-icon-badge {
            position: absolute;
            top: -0.35rem;
            right: -0.4rem;
            min-width: 1.15rem;
            height: 1.15rem;
            padding: 0 0.28rem;
            border-radius: 999px;
            background: #ef4444;
            color: #fff;
            font-size: 0.7rem;
            font-weight: 800;
            line-height: 1.15rem;
            text-align: center;
        }
        .guide-icons strong {
            display: block;
            color: var(--text);
            margin-bottom: 0.15rem;
            font-size: 0.95rem;
        }
        .guide-icons p {
            margin: 0;
            color: var(--muted);
            font-size: 0.875rem;
            line-height: 1.45;
        }
        .guide-footer {
            margin-top: 1.5rem;
            text-align: center;
        }
        .guide-footer a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2.75rem;
            padding: 0.7rem 1.1rem;
            border-radius: 0.75rem;
            background: var(--orange);
            color: #fff;
            font-weight: 700;
            text-decoration: none;
        }
    </style>
</head>
<body>
@include('taxi::partials.pwa-theme', ['section' => 'widget'])
<header class="guide-top">
    <a class="back" href="{{ $appUrl }}">← App</a>
    <h1>Handleiding chauffeur</h1>
</header>
<main class="guide-wrap">
    <div class="guide-intro">
        <p>Deze handleiding loopt met je mee: van inloggen tot een rit afronden en betalen. Tik een onderwerp aan, of lees van boven naar beneden.</p>
        <p>Je opent deze pagina altijd opnieuw via <strong style="color:var(--text)">Profiel</strong> onderin de app, ook als je de banner hebt weggeklikt.</p>
    </div>

    <nav class="guide-toc" aria-label="Inhoud">
        <a href="#inloggen"><span class="num">1</span> Inloggen</a>
        <a href="#online"><span class="num">2</span> Online zetten</a>
        <a href="#iconen"><span class="num">3</span> Iconen bovenin</a>
        <a href="#aanvragen"><span class="num">4</span> Nieuwe ritaanvraag</a>
        <a href="#rit"><span class="num">5</span> Rit rijden</a>
        <a href="#betalen"><span class="num">6</span> Betalen en factuur</a>
        <a href="#tabs"><span class="num">7</span> Ritten, inkomsten, profiel</a>
        <a href="#telefoon"><span class="num">8</span> App op je telefoon</a>
    </nav>

    <section class="guide-section" id="inloggen">
        <h2>1. Inloggen</h2>
        <p>Open de chauffeur-app. Vul het e-mailadres en wachtwoord in dat je van je werkgever hebt gekregen.</p>
        <ol class="guide-steps">
            <li data-step="1"><strong>E-mail</strong> Gebruik een volledig adres, met een @ en een punt (bijvoorbeeld naam@bedrijf.nl).</li>
            <li data-step="2"><strong>Wachtwoord</strong> Tik op het oogje om te controleren wat je typt.</li>
            <li data-step="3"><strong>Inloggen</strong> Klopt iets niet, dan zie je een rode melding onder het veld of onder het formulier.</li>
        </ol>
        <figure class="guide-shot">
            <div class="phone-frame">
                <div class="mock" aria-hidden="true">
                    <h3>Chauffeur inloggen</h3>
                    <div class="mock-field"><span>E-mail</span><div class="mock-input"></div></div>
                    <div class="mock-field"><span>Wachtwoord</span><div class="mock-input"></div></div>
                    <div class="mock-btn"></div>
                </div>
            </div>
            <figcaption>Inlogscherm: e-mail, wachtwoord en de oranje knop Inloggen.</figcaption>
        </figure>
    </section>

    <section class="guide-section" id="online">
        <h2>2. Online zetten</h2>
        <p>Na het inloggen kom je op Aanvragen. Rechtsboven staat de schakelaar <strong style="color:var(--text)">Online</strong>. Alleen als die aan staat, ontvang je nieuwe ritten.</p>
        <ol class="guide-steps">
            <li data-step="1"><strong>Zet Online aan</strong> als je klaar bent om te rijden.</li>
            <li data-step="2"><strong>Zet Online uit</strong> als je pauzeert of klaar bent — dan komen er geen nieuwe aanvragen binnen.</li>
            <li data-step="3"><strong>Meldingen</strong> Sta meldingen toe als de app daarom vraagt, zodat je een geluid hoort bij een nieuwe rit.</li>
        </ol>
        <figure class="guide-shot">
            <div class="phone-frame">
                <div class="mock" aria-hidden="true">
                    <div class="mock-row"><span>Online</span><span class="mock-switch"></span></div>
                    <p style="margin:0.4rem 0 0;color:#9ca3af;">Aanvragen</p>
                    <div class="mock-nav"><b>Aanvragen</b><span>Ritten</span><span>Inkomsten</span><span>Profiel</span></div>
                </div>
            </div>
            <figcaption>De groene schakelaar rechtsboven betekent: je bent online voor ritten.</figcaption>
        </figure>
        <p class="guide-tip"><strong>Tip:</strong> na het inloggen zie je bovenin een banner over deze handleiding. Klik die weg als je wilt — je vindt de handleiding daarna altijd onder Profiel.</p>
    </section>

    <section class="guide-section" id="iconen">
        <h2>3. Iconen bovenin</h2>
        <p>Als je online bent, staan in het midden van de balk iconen. Het rode cijfer is het aantal in die map. Niet elk icoon is altijd zichtbaar: de auto verschijnt alleen bij een rit onderweg, de andere als er iets in die map staat.</p>
        <div class="guide-icon-strip" aria-hidden="true">
            <span class="guide-icon-pic is-ride">
                <svg viewBox="0 0 24 24" fill="none"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M5 11h14M6 11l1.2-3.6A1.5 1.5 0 0 1 8.6 6h6.8a1.5 1.5 0 0 1 1.4 1.04L18 11M6 11v5a1 1 0 0 0 1 1h1M16 17h1a1 1 0 0 0 1-1v-5"/><circle cx="8" cy="17" r="1.3" stroke="currentColor" stroke-width="2"/><circle cx="16" cy="17" r="1.3" stroke="currentColor" stroke-width="2"/></svg>
            </span>
            <span class="guide-icon-pic is-open">
                <svg viewBox="-3 -3 30 30" fill="none"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M22 12h-6l-2 3H10l-2-3H2"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11Z"/></svg>
                <span class="guide-icon-badge">1</span>
            </span>
            <span class="guide-icon-pic">
                <svg viewBox="-3 -3 30 30" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3.2 1.8"/></svg>
                <span class="guide-icon-badge">11</span>
            </span>
            <span class="guide-icon-pic">
                <svg viewBox="-3 -3 30 30" fill="none"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M4 8h16v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8Z"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M2 8h20M10 12h4"/></svg>
                <span class="guide-icon-badge">3</span>
            </span>
        </div>
        <ul class="guide-icons">
            <li>
                <span class="guide-icon-pic is-ride" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M5 11h14M6 11l1.2-3.6A1.5 1.5 0 0 1 8.6 6h6.8a1.5 1.5 0 0 1 1.4 1.04L18 11M6 11v5a1 1 0 0 0 1 1h1M16 17h1a1 1 0 0 0 1-1v-5"/><circle cx="8" cy="17" r="1.3" stroke="currentColor" stroke-width="2"/><circle cx="16" cy="17" r="1.3" stroke="currentColor" stroke-width="2"/></svg>
                </span>
                <div>
                    <strong>Auto — actieve rit</strong>
                    <p>Groen en knippert als je een rit onderweg hebt. Tik om meteen naar die rit te gaan: navigatie, stops en afronden. Verdween het rit-scherm even? Dit icoon brengt je terug.</p>
                </div>
            </li>
            <li>
                <span class="guide-icon-pic is-open" aria-hidden="true">
                    <svg viewBox="-3 -3 30 30" fill="none"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M22 12h-6l-2 3H10l-2-3H2"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11Z"/></svg>
                    <span class="guide-icon-badge">1</span>
                </span>
                <div>
                    <strong>Inbox — open aanvragen</strong>
                    <p>Nieuwe ritten die nog op jou wachten. Oranje als je in deze map zit. Het rode cijfer is hoeveel open aanvragen er zijn. Tik om ze te bekijken, te accepteren of af te wijzen.</p>
                </div>
            </li>
            <li>
                <span class="guide-icon-pic" aria-hidden="true">
                    <svg viewBox="-3 -3 30 30" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3.2 1.8"/></svg>
                    <span class="guide-icon-badge">11</span>
                </span>
                <div>
                    <strong>Klok — verlopen</strong>
                    <p>Ritten waarvan de ophaaltijd voorbij is, of die te laat zijn. Tik om ze te openen: afronden, vrijgeven of alsnog oppakken. Het cijfer is hoeveel verlopen ritten er zijn.</p>
                </div>
            </li>
            <li>
                <span class="guide-icon-pic" aria-hidden="true">
                    <svg viewBox="-3 -3 30 30" fill="none"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M4 8h16v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8Z"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M2 8h20M10 12h4"/></svg>
                    <span class="guide-icon-badge">3</span>
                </span>
                <div>
                    <strong>Doos — archief</strong>
                    <p>Aanvragen die je hebt weggestopt. Tik om ze terug te vinden of te verwijderen. Ze staan niet meer tussen de open ritten.</p>
                </div>
            </li>
            <li>
                <span class="guide-icon-pic" aria-hidden="true">
                    <svg viewBox="-3 -3 30 30" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="m15 9-6 6M9 9l6 6"/></svg>
                </span>
                <div>
                    <strong>Kruis — afgewezen</strong>
                    <p>Verschijnt als je ritten hebt afgewezen. Tik om die lijst te zien. Soms kun je een afgewezen rit alsnog accepteren.</p>
                </div>
            </li>
        </ul>
    </section>

    <section class="guide-section" id="aanvragen">
        <h2>4. Nieuwe ritaanvraag</h2>
        <p>Een nieuwe rit komt binnen op het tabblad Aanvragen. Je ziet ophalen, bestemming, tijd, voertuig en prijs.</p>
        <ol class="guide-steps">
            <li data-step="1"><strong>Bekijk de rit</strong> Controleer adres, tijd en of het voertuig klopt.</li>
            <li data-step="2"><strong>Accepteren</strong> De rit wordt van jou. Je gaat naar het rit-scherm.</li>
            <li data-step="3"><strong>Afwijzen</strong> Optioneel een korte reden. De rit kan naar een andere chauffeur.</li>
            <li data-step="4"><strong>Meerdere ritten</strong> Met Vorige / Volgende blader je door openstaande aanvragen.</li>
        </ol>
        <figure class="guide-shot">
            <button type="button" class="guide-zoom" aria-label="Vergroot: Chauffeur-app nieuwe ritaanvraag">
                <div class="phone-frame">
                    <img src="{{ asset('assets/marketing/images/feature-chauffeur-inbox.png') }}" alt="Chauffeur-app: nieuwe ritaanvraag in de inbox">
                </div>
            </button>
            <figcaption>Inbox: nieuwe ritaanvraag. Accepteren of afwijzen.<span class="guide-zoom-hint">Tik om te vergroten</span></figcaption>
        </figure>
    </section>

    <section class="guide-section" id="rit">
        <h2>5. Rit rijden</h2>
        <p>Na accepteren start je de rit, rijd je de stops af en rond je af. Tik op een adres om navigatie te openen.</p>
        <ol class="guide-steps">
            <li data-step="1"><strong>Starten</strong> als je onderweg gaat naar de klant.</li>
            <li data-step="2"><strong>Stops</strong> Tik Aankomst of Ophalen per stop. De app merkt vaak zelf of je in de buurt bent.</li>
            <li data-step="3"><strong>Afronden</strong> als alle reizigers zijn afgezet. Daarna volgt betalen (als dat aanstaat).</li>
        </ol>
        <figure class="guide-shot">
            <button type="button" class="guide-zoom" aria-label="Vergroot: actieve rit">
                <div class="phone-frame">
                    <img src="{{ asset('assets/marketing/images/feature-chauffeur-active.png') }}" alt="Chauffeur-app: actieve rit met navigatie en stops">
                </div>
            </button>
            <figcaption>Actieve rit: navigatie, stops en afronden.<span class="guide-zoom-hint">Tik om te vergroten</span></figcaption>
        </figure>
        <figure class="guide-shot">
            <button type="button" class="guide-zoom" aria-label="Vergroot: geplande ritten">
                <div class="phone-frame">
                    <img src="{{ asset('assets/marketing/images/feature-chauffeur-app.png') }}" alt="Chauffeur-app: geplande ritten in één overzicht">
                </div>
            </button>
            <figcaption>Geplande ritten in één overzicht, ook als er nog geen nieuwe aanvraag is.<span class="guide-zoom-hint">Tik om te vergroten</span></figcaption>
        </figure>
    </section>

    <section class="guide-section" id="betalen">
        <h2>6. Betalen en factuur</h2>
        <p>Als betalen in de app aanstaat, toon je na de rit een QR-code of kies je contant. Daarna kun je een factuur-pdf naar de klant mailen — met betaald-indicatie.</p>
        <ol class="guide-steps">
            <li data-step="1"><strong>QR-code</strong> Laat de klant scannen en wacht tot de status op betaald springt.</li>
            <li data-step="2"><strong>Contant</strong> Bevestig dat je het geld hebt ontvangen.</li>
            <li data-step="3"><strong>Factuur</strong> Vul het e-mailadres van de klant in en verstuur. De pdf gaat naar dat adres.</li>
        </ol>
        <p class="guide-tip"><strong>Let op:</strong> niet elk bedrijf gebruikt in-app betaling. Zie je geen QR, dan regelt de centrale de betaling.</p>
    </section>

    <section class="guide-section" id="tabs">
        <h2>7. Ritten, inkomsten en profiel</h2>
        <p>Onderin de app staan de tabbladen. Tik erop om te wisselen — ook tijdens een rit kun je naar Ritten.</p>
        <ol class="guide-steps">
            <li data-step="1"><strong>Aanvragen</strong> Nieuwe ritten die op jou wachten.</li>
            <li data-step="2"><strong>Ritten</strong> Gepland, actief, verlopen en archief.</li>
            <li data-step="3"><strong>Inkomsten</strong> (als je daarvoor recht hebt) afgeronde ritten per dag.</li>
            <li data-step="4"><strong>Profiel</strong> Jouw gegevens, uitloggen, en de link naar deze handleiding.</li>
        </ol>
        <figure class="guide-shot">
            <div class="phone-frame">
                <div class="mock" aria-hidden="true">
                    <h3>Profiel</h3>
                    <p style="margin:0 0 0.45rem;font-weight:700;">Jan de Chauffeur</p>
                    <p style="margin:0 0 0.85rem;color:#9ca3af;">naam@bedrijf.nl</p>
                    <div class="mock-row" style="justify-content:flex-start;gap:0.5rem;"><span>Handleiding</span></div>
                    <div class="mock-input" style="height:2.2rem;margin-top:0.45rem;display:flex;align-items:center;justify-content:center;color:#9ca3af;">Uitloggen</div>
                    <div class="mock-nav"><span>Aanvragen</span><span>Ritten</span><span>Inkomsten</span><b>Profiel</b></div>
                </div>
            </div>
            <figcaption>Onder Profiel staat Handleiding — ook nadat je de banner hebt gesloten.</figcaption>
        </figure>
    </section>

    <section class="guide-section" id="telefoon">
        <h2>8. App op je telefoon</h2>
        <p>Zet de chauffeur-app op het beginscherm. Geen App Store: één keer openen, daarna een icoon naast WhatsApp.</p>
        <ol class="guide-steps">
            <li data-step="1"><strong>iPhone</strong> Open in Safari → Deel → Zet op beginscherm.</li>
            <li data-step="2"><strong>Android</strong> Open in Chrome → menu → Toevoegen aan startscherm.</li>
            <li data-step="3"><strong>Meldingen</strong> Sta ze toe, zodat nieuwe ritten een geluid geven — ook als de app op de achtergrond staat.</li>
        </ol>
        <p class="guide-tip"><strong>Onthoud:</strong> banner weggeklikt? Open de app → <strong>Profiel</strong> → <strong>Handleiding</strong>.</p>
    </section>

    <div class="guide-footer">
        <a href="{{ $appUrl }}">Terug naar de chauffeur-app</a>
    </div>
</main>
<div id="guide-lightbox" class="guide-lightbox" hidden aria-hidden="true" role="dialog" aria-modal="true" aria-label="Vergrote screenshot">
    <div class="guide-lightbox__inner">
        <button type="button" class="guide-lightbox__close" data-guide-lightbox-close aria-label="Sluiten">&times;</button>
        <img class="guide-lightbox__img" alt="">
        <p class="guide-lightbox__caption" hidden></p>
    </div>
</div>
<script>
(function () {
    var overlay = document.getElementById('guide-lightbox');
    if (!overlay) return;
    var img = overlay.querySelector('.guide-lightbox__img');
    var captionEl = overlay.querySelector('.guide-lightbox__caption');

    function closeLightbox() {
        overlay.classList.remove('is-open');
        overlay.hidden = true;
        overlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        if (img) img.removeAttribute('src');
    }

    function openLightbox(src, alt, caption) {
        if (!src || !img) return;
        img.src = src;
        img.alt = alt || '';
        if (captionEl) {
            captionEl.textContent = caption || '';
            captionEl.hidden = !caption;
        }
        overlay.hidden = false;
        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    document.addEventListener('click', function (e) {
        var trigger = e.target.closest && e.target.closest('.guide-zoom');
        if (trigger) {
            e.preventDefault();
            var shot = trigger.closest('.guide-shot');
            var thumb = trigger.querySelector('img');
            var caption = '';
            if (shot) {
                var cap = shot.querySelector('figcaption');
                if (cap) {
                    caption = cap.childNodes[0] ? cap.childNodes[0].textContent.trim() : cap.textContent.trim();
                }
            }
            openLightbox(thumb && thumb.src, thumb && thumb.alt, caption);
            return;
        }
        if (e.target === overlay || (e.target.closest && e.target.closest('[data-guide-lightbox-close]'))) {
            closeLightbox();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.classList.contains('is-open')) {
            closeLightbox();
        }
    });
})();
</script>
</body>
</html>
