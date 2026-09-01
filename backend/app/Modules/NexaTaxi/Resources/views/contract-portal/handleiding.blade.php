<!DOCTYPE html>
<html lang="nl" class="h-full" data-theme="dark" data-accent="orange">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#f97316">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Handleiding contract – Nexa Taxi</title>
    <link rel="icon" href="{{ $faviconUrl }}" type="{{ $faviconType }}">
    @include('taxi::partials.pwa-theme', ['section' => 'boot'])
    @include('taxi::partials.pwa-accent', ['section' => 'boot'])
    @include('taxi::partials.pwa-theme', ['section' => 'styles'])
    @include('taxi::partials.pwa-accent', ['section' => 'styles'])
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
            background: rgba(var(--accent-rgb), 0.18);
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
            max-width: 19.5rem;
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
            max-width: 19.5rem;
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
            padding: 0.9rem 0.55rem 1rem;
            font-size: 0.78rem;
        }
        html[data-theme="light"] .mock { background: #0f172a; }
        .mock h3 { margin: 0 0 0.75rem; font-size: 0.95rem; }
        .mock-field { margin-bottom: 0.55rem; }
        .mock-field span { display: block; color: #9ca3af; font-size: 0.7rem; margin-bottom: 0.2rem; }
        .mock-input { height: 2.1rem; border-radius: 0.55rem; background: #252528; border: 1px solid rgba(255,255,255,0.1); }
        .mock-btn { height: 2.3rem; border-radius: 0.55rem; background: var(--orange); margin-top: 0.45rem; }
        .mock-map {
            height: 7.2rem;
            border-radius: 0.65rem;
            background: #1a1a1c;
            border: 1px solid rgba(255,255,255,0.08);
            position: relative;
            overflow: hidden;
            margin-bottom: 0.55rem;
        }
        .phone-frame .mock-map img,
        .mock-map img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            border-radius: 0;
            background: #1a1a1c;
        }
        .mock-stop {
            display: flex;
            align-items: flex-start;
            gap: 0.4rem;
            padding: 0.38rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            color: #e5e7eb;
            line-height: 1.25;
            font-size: 0.78rem;
        }
        .mock-stop:last-of-type { border-bottom: 0; }
        .mock-stop b {
            flex-shrink: 0;
            width: 1.15rem;
            height: 1.15rem;
            margin-top: 0.12rem;
            border-radius: 999px;
            background: var(--orange);
            color: #fff;
            font-size: 0.62rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .mock-stop-name {
            display: block;
            font-size: 0.84rem;
            font-weight: 700;
            color: #fff;
        }
        .mock-stop-kind {
            display: block;
            font-size: 0.72rem;
            color: #d1d5db;
            margin-top: 0.08rem;
        }
        .mock-stop-addr { display: block; color: #9ca3af; font-size: 0.68rem; margin-top: 0.08rem; }
        .mock-nav.is-five {
            display: flex;
            grid-template-columns: none;
            justify-content: space-between;
            align-items: flex-start;
            column-gap: 0.5rem;
        }
        .mock-nav.is-five > * {
            flex: 0 0 auto;
            overflow: visible;
        }
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
            grid-template-columns: repeat(4, minmax(0, 1fr));
            column-gap: 0.55rem;
            row-gap: 0.35rem;
            margin-top: 0.7rem;
            padding-top: 0.5rem;
            border-top: 1px solid rgba(255,255,255,0.08);
            text-align: center;
            color: #9ca3af;
            font-size: 0.52rem;
            font-weight: 600;
            line-height: 1.15;
        }
        .mock-nav > * {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            gap: 0.18rem;
            min-width: 0;
            padding: 0;
            box-sizing: border-box;
            overflow: hidden;
            white-space: nowrap;
        }
        .mock-nav svg {
            width: 1.15rem;
            height: 1.15rem;
            flex-shrink: 0;
        }
        .mock-nav b { color: var(--orange); font-weight: 700; }
        .guide-tip {
            background: rgba(var(--accent-rgb), 0.12);
            border: 1px solid rgba(var(--accent-rgb), 0.35);
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
    <h1>Handleiding contract</h1>
</header>
<main class="guide-wrap">
    <div class="guide-intro">
        <p>Deze handleiding is voor ouders en opdrachtgevers: eerst de app op je telefoon zetten, daarna inloggen, ritten van vandaag bekijken, navigeren langs ophaalstops, de weekplanning en afmelden bij afwezigheid.</p>
        <p>Je opent deze pagina altijd opnieuw via <strong style="color:var(--text)">Profiel</strong> onderin de app, ook als je de banner hebt weggeklikt.</p>
    </div>

    <nav class="guide-toc" aria-label="Inhoud">
        <a href="#telefoon"><span class="num">1</span> App op je telefoon</a>
        <a href="#inloggen"><span class="num">2</span> Inloggen</a>
        <a href="#vandaag"><span class="num">3</span> Vandaag</a>
        <a href="#planning"><span class="num">4</span> Planning</a>
        <a href="#afmelden"><span class="num">5</span> Afmelden</a>
        <a href="#navigatie"><span class="num">6</span> Navigatie</a>
        <a href="#tabs"><span class="num">7</span> Tabbladen onderin</a>
    </nav>

    <section class="guide-section" id="telefoon">
        <h2>1. App op je telefoon</h2>
        <p>Zet de contract-app op het beginscherm. Geen App Store: één keer openen, daarna een icoon naast WhatsApp.</p>
        <ol class="guide-steps">
            <li data-step="1"><strong>iPhone</strong> Open in Safari → Deel → Zet op beginscherm.</li>
            <li data-step="2"><strong>Android</strong> Open in Chrome → menu → App installeren of Toevoegen aan startscherm.</li>
            <li data-step="3"><strong>Snel openen</strong> Daarna tik je het icoon aan, zonder telkens het wachtwoord te zoeken in de mail.</li>
        </ol>
        <p class="guide-tip"><strong>Onthoud:</strong> banner weggeklikt? Open de app → <strong>Profiel</strong> → <strong>Handleiding</strong>.</p>
    </section>

    <section class="guide-section" id="inloggen">
        <h2>2. Inloggen</h2>
        <p>Open de contract-app. Vul je e-mailadres in. De eerste keer kies je <strong>Inlogcode aanvragen</strong>: je krijgt een eenmalige code per e-mail en kiest daarna zelf een wachtwoord. Daarna log je in met e-mail en wachtwoord. Dit geldt voor contractant en contractouder.</p>
        <ol class="guide-steps">
            <li data-step="1"><strong>E-mail</strong> Gebruik het adres waarop je de welkomstmail hebt gekregen.</li>
            <li data-step="2"><strong>Eerste keer</strong> Tik op Inlogcode aanvragen. Vul de 6-cijferige code in en kies een wachtwoord.</li>
            <li data-step="3"><strong>Daarna</strong> Log je in met e-mail en wachtwoord. Klopt iets niet, dan zie je een rode melding.</li>
        </ol>
        <figure class="guide-shot">
            <div class="phone-frame">
                <div class="mock" aria-hidden="true">
                    <h3>Contract inloggen</h3>
                    <div class="mock-field"><span>E-mail</span><div class="mock-input"></div></div>
                    <div class="mock-field"><span>Wachtwoord</span><div class="mock-input"></div></div>
                    <div class="mock-btn"></div>
                </div>
            </div>
            <figcaption>Inlogscherm: e-mail, wachtwoord of inlogcode, en de oranje knop Inloggen.</figcaption>
        </figure>
        <p class="guide-tip"><strong>Tip:</strong> na het inloggen zie je bovenin een banner over deze handleiding. Klik die weg als je wilt — je vindt de handleiding daarna altijd onder Profiel.</p>
    </section>

    <section class="guide-section" id="vandaag">
        <h2>3. Vandaag</h2>
        <p>Na het inloggen zie je <strong style="color:var(--text)">Vandaag</strong>: de reizigers van deze dag, met ophaal- en afzetadres en de status van de rit. Een <strong style="color:var(--text)">contractant</strong> ziet alle reizigers van de opdracht; een <strong style="color:var(--text)">ouder</strong> alleen het eigen kind.</p>
        <ol class="guide-steps">
            <li data-step="1"><strong>Tik op een naam</strong> om de rit open te klappen: tijden, adressen en status.</li>
            <li data-step="2"><strong>Status</strong> bijvoorbeeld gepland, onderweg, opgehaald of bestemming bereikt.</li>
            <li data-step="3"><strong>Heen en terug</strong> Als er twee ritten zijn, zie je die onder elkaar (ochtend en middag).</li>
        </ol>
        <figure class="guide-shot">
            <button type="button" class="guide-zoom" aria-label="Vergroot: vandaag als contractant">
                <div class="phone-frame">
                    <img src="{{ asset('assets/marketing/images/feature-contract-vandaag.png') }}?v=4" alt="Contract-app als contractant: meerdere reizigers, bovenste rit opengeklapt">
                </div>
            </button>
            <figcaption>Contractant: alle reizigers van vandaag. Tik op een naam om adressen en status te zien.<span class="guide-zoom-hint">Tik om te vergroten</span></figcaption>
        </figure>
        <figure class="guide-shot">
            <button type="button" class="guide-zoom" aria-label="Vergroot: vandaag als ouder">
                <div class="phone-frame">
                    <img src="{{ asset('assets/marketing/images/feature-contract-vandaag-ouder.png') }}?v=1" alt="Contract-app als ouder: één reiziger met uitgeklapte rit">
                </div>
            </button>
            <figcaption>Ouder: één reiziger in het overzicht van vandaag.<span class="guide-zoom-hint">Tik om te vergroten</span></figcaption>
        </figure>
        <figure class="guide-shot">
            <button type="button" class="guide-zoom" aria-label="Vergroot: ouder, bestemming bereikt">
                <div class="phone-frame">
                    <img src="{{ asset('assets/marketing/images/feature-contract-vandaag-ouder-bereikt.png') }}?v=1" alt="Contract-app als ouder: reiziger opgehaald en bestemming bereikt">
                </div>
            </button>
            <figcaption>Ouder: dezelfde rit, nu opgehaald en bestemming bereikt.<span class="guide-zoom-hint">Tik om te vergroten</span></figcaption>
        </figure>
    </section>

    <section class="guide-section" id="planning">
        <h2>4. Planning</h2>
        <p>Onder <strong style="color:var(--text)">Planning</strong> kies je Dag of Week. Dag toont alleen die dag; blader met de pijltjes. Week toont de dagen van de week; tik op een dag om die ritten te zien. Tik op een rit om hem te openen — vandaag gaat naar Vandaag, andere dagen klappen open in Planning.</p>
        <ol class="guide-steps">
            <li data-step="1"><strong>Dag of week</strong> Wissel rechtsboven tussen één dag en de weekstrook.</li>
            <li data-step="2"><strong>Vandaag</strong> springt terug naar de huidige dag.</li>
            <li data-step="3"><strong>Rit openen</strong> Tik op een gekleurde ritkaart. Afmelden kan in de geopende rit.</li>
            <li data-step="4"><strong>Geen vervoer</strong> Op een vrije dag of uitzondering staat dat bij de reiziger.</li>
        </ol>
        <figure class="guide-shot">
            <button type="button" class="guide-zoom" aria-label="Vergroot: weekplanning">
                <div class="phone-frame">
                    <img src="{{ asset('assets/marketing/images/feature-contract-planning.png') }}" alt="Contract-app: weekplanning met ritten per dag">
                </div>
            </button>
            <figcaption>Planning: dag of week, tik een rit aan.<span class="guide-zoom-hint">Tik om te vergroten</span></figcaption>
        </figure>
    </section>

    <section class="guide-section" id="afmelden">
        <h2>5. Afmelden</h2>
        <p>Is iemand ziek of niet mee? Meld af vanaf Vandaag of Planning. De chauffeur ziet dat dan in de rit.</p>
        <ol class="guide-steps">
            <li data-step="1"><strong>Afmelden</strong> Open de reiziger en tik op Afmelden.</li>
            <li data-step="2"><strong>Periode</strong> Kies van- en tot-datum. Dat mag tot 14 dagen vooruit, één dag of meerdere dagen.</li>
            <li data-step="3"><strong>Reden</strong> Optioneel, bijvoorbeeld ziek of schoolreis.</li>
            <li data-step="4"><strong>Intrekken</strong> Een afmelding kun je terugdraaien via Afmelding intrekken, of onder het tabblad Afmeldingen.</li>
        </ol>
        <figure class="guide-shot">
            <div class="phone-frame">
                <div class="mock" aria-hidden="true">
                    <h3>Afmelden</h3>
                    <p style="margin:0 0 0.55rem;color:#9ca3af;">Emma Jansen</p>
                    <div class="mock-field"><span>Van</span><div class="mock-input"></div></div>
                    <div class="mock-field"><span>Tot</span><div class="mock-input"></div></div>
                    <div class="mock-field"><span>Reden (optioneel)</span><div class="mock-input"></div></div>
                    <div class="mock-btn" style="background:#ef4444;"></div>
                </div>
            </div>
            <figcaption>Afmelden: kies de dagen en bevestig. Intrekken kan daarna nog.</figcaption>
        </figure>
    </section>

    <section class="guide-section" id="navigatie">
        <h2>6. Navigatie</h2>
        <p>Onder <strong style="color:var(--text)">Navigatie</strong> zie je de ophaalroute van vandaag op de kaart: elke reiziger die nog meegaat is een genummerde tussenstop, daarna de bestemming (bijvoorbeeld de school). De volgorde volgt de geplande ophaaltijden. ’s Ochtends de heenrit, ’s middags de retour — als de heenrit klaar is, schakelt de kaart vanzelf over.</p>
        <ol class="guide-steps">
            <li data-step="1"><strong>Kaart</strong> De oranje lijn loopt langs de weg, van stop 1 naar 2, 3 en de bestemming. Zoom en sleep als je wilt.</li>
            <li data-step="2"><strong>Lijst</strong> Onder de kaart staan dezelfde stops: eerst de naam, daaronder Ophalen of Afzetten, met adres en ophaaltijd.</li>
            <li data-step="3"><strong>Start navigatie</strong> Opent Google Maps met de hele rit: alle tussenstops én de bestemming, in dezelfde volgorde. Zo kun je de route narijden of meekijken.</li>
            <li data-step="4"><strong>Eén stop</strong> Tik op een regel in de lijst om Google Maps alleen naar díé tussenstop te openen.</li>
        </ol>
        <figure class="guide-shot">
            <div class="phone-frame">
                <div class="mock" aria-hidden="true">
                    <h3>Navigatie</h3>
                    <div class="mock-map">
                        <img src="{{ asset('assets/marketing/images/feature-contract-navigatie-map.png') }}?v=1" alt="" width="640" height="320">
                    </div>
                    <p style="margin:0 0 0.4rem;color:#9ca3af;font-size:0.68rem;">3 ophaalstops, daarna de school.</p>
                    <div class="mock-stop"><b>1</b><div><span class="mock-stop-name">Emma</span><span class="mock-stop-kind">Ophalen</span><span class="mock-stop-addr">Kerkstraat 1 · 07:43</span></div></div>
                    <div class="mock-stop"><b>2</b><div><span class="mock-stop-name">Noah</span><span class="mock-stop-kind">Ophalen</span><span class="mock-stop-addr">Dorpsstraat 12 · 07:51</span></div></div>
                    <div class="mock-stop"><b>3</b><div><span class="mock-stop-name">Sophie</span><span class="mock-stop-kind">Ophalen</span><span class="mock-stop-addr">Laan 8 · 07:57</span></div></div>
                    <div class="mock-stop"><b>4</b><div><span class="mock-stop-kind">Afzetten</span><span class="mock-stop-addr">Schoolplein 4</span></div></div>
                    <div class="mock-btn" style="display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.78rem;">Start navigatie</div>
                    <div class="mock-nav is-five" aria-hidden="true">
                        <span>
                            <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="16" rx="2" stroke="currentColor" stroke-width="2"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M8 3v4M16 3v4M3 10h18"/></svg>
                            Vandaag
                        </span>
                        <span>
                            <svg viewBox="0 0 24 24" fill="none"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M8 7h8M8 12h8M8 17h5"/><rect x="3" y="4" width="18" height="16" rx="2" stroke="currentColor" stroke-width="2"/></svg>
                            Planning
                        </span>
                        <b>
                            <svg viewBox="0 0 24 24" fill="none"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 3 4.5 20.5 12 16.5l7.5 4L12 3Z"/></svg>
                            Navigatie
                        </b>
                        <span>
                            <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="m15 9-6 6M9 9l6 6"/></svg>
                            Afmeldingen
                        </span>
                        <span>
                            <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="2"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M5.5 19.5c1.6-3.2 4-4.8 6.5-4.8s4.9 1.6 6.5 4.8"/></svg>
                            Profiel
                        </span>
                    </div>
                </div>
            </div>
            <figcaption>Navigatie: kaart met tussenstops. De naam staat boven Ophalen, daarna het adres.</figcaption>
        </figure>
        <p class="guide-tip"><strong>Tip:</strong> wie al is opgehaald of afgemeld valt vanzelf van de route af. Google Maps opent de rit ook zonder je locatie; sta je in de buurt, dan start de navigatie vanaf waar je bent.</p>
    </section>

    <section class="guide-section" id="tabs">
        <h2>7. Tabbladen onderin</h2>
        <p>Onderin de app staan vijf tabbladen. Tik erop om te wisselen.</p>
        <ul class="guide-icons">
            <li>
                <span class="guide-icon-pic is-open" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="16" rx="2" stroke="currentColor" stroke-width="2"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M8 3v4M16 3v4M3 10h18"/></svg>
                </span>
                <div>
                    <strong>Kalender — Vandaag</strong>
                    <p>De ritten van deze dag. Tik een naam aan voor adressen, tijden en status.</p>
                </div>
            </li>
            <li>
                <span class="guide-icon-pic" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M8 7h8M8 12h8M8 17h5"/><rect x="3" y="4" width="18" height="16" rx="2" stroke="currentColor" stroke-width="2"/></svg>
                </span>
                <div>
                    <strong>Lijst — Planning</strong>
                    <p>Dag of week, tik een dag aan, tik een rit open.</p>
                </div>
            </li>
            <li>
                <span class="guide-icon-pic" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 3 4.5 20.5 12 16.5l7.5 4L12 3Z"/></svg>
                </span>
                <div>
                    <strong>Pijl — Navigatie</strong>
                    <p>Ophaalroute van vandaag op de kaart. Start navigatie opent Google Maps met alle tussenstops; tik een naam aan voor één stop.</p>
                </div>
            </li>
            <li>
                <span class="guide-icon-pic" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="m15 9-6 6M9 9l6 6"/></svg>
                </span>
                <div>
                    <strong>Kruis — Afmeldingen</strong>
                    <p>Overzicht van wie is afgemeld. Hier trek je een afmelding ook weer in.</p>
                </div>
            </li>
            <li>
                <span class="guide-icon-pic" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="2"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M5.5 19.5c1.6-3.2 4-4.8 6.5-4.8s4.9 1.6 6.5 4.8"/></svg>
                </span>
                <div>
                    <strong>Persoon — Profiel</strong>
                    <p>Jouw gegevens (alleen ter inzage), uitloggen, en de link naar deze handleiding.</p>
                </div>
            </li>
        </ul>
        <figure class="guide-shot">
            <div class="phone-frame">
                <div class="mock" aria-hidden="true">
                    <h3>Profiel</h3>
                    <p style="margin:0 0 0.45rem;font-weight:700;">Ouder Jansen</p>
                    <p style="margin:0 0 0.85rem;color:#9ca3af;">naam@school.nl</p>
                    <div class="mock-row" style="justify-content:flex-start;gap:0.5rem;"><span>Handleiding</span></div>
                    <div class="mock-input" style="height:2.2rem;margin-top:0.45rem;display:flex;align-items:center;justify-content:center;color:#9ca3af;">Uitloggen</div>
                    <div class="mock-nav is-five" aria-hidden="true">
                        <span>
                            <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="16" rx="2" stroke="currentColor" stroke-width="2"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M8 3v4M16 3v4M3 10h18"/></svg>
                            Vandaag
                        </span>
                        <span>
                            <svg viewBox="0 0 24 24" fill="none"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M8 7h8M8 12h8M8 17h5"/><rect x="3" y="4" width="18" height="16" rx="2" stroke="currentColor" stroke-width="2"/></svg>
                            Planning
                        </span>
                        <span>
                            <svg viewBox="0 0 24 24" fill="none"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 3 4.5 20.5 12 16.5l7.5 4L12 3Z"/></svg>
                            Navigatie
                        </span>
                        <span>
                            <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="m15 9-6 6M9 9l6 6"/></svg>
                            Afmeldingen
                        </span>
                        <b>
                            <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="2"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M5.5 19.5c1.6-3.2 4-4.8 6.5-4.8s4.9 1.6 6.5 4.8"/></svg>
                            Profiel
                        </b>
                    </div>
                </div>
            </div>
            <figcaption>Onder Profiel staat Handleiding — ook nadat je de banner hebt gesloten.</figcaption>
        </figure>
    </section>

    <div class="guide-footer">
        <a href="{{ $appUrl }}">Terug naar de contract-app</a>
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
