<!DOCTYPE html>
<html lang="nl" class="h-full" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#f97316">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Handleiding contract – Nexa Taxi</title>
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
    <h1>Handleiding contract</h1>
</header>
<main class="guide-wrap">
    <div class="guide-intro">
        <p>Deze handleiding is voor ouders en opdrachtgevers: inloggen, ritten van vandaag bekijken, de weekplanning en afmelden bij afwezigheid.</p>
        <p>Je opent deze pagina altijd opnieuw via <strong style="color:var(--text)">Profiel</strong> onderin de app, ook als je de banner hebt weggeklikt.</p>
    </div>

    <nav class="guide-toc" aria-label="Inhoud">
        <a href="#inloggen"><span class="num">1</span> Inloggen</a>
        <a href="#vandaag"><span class="num">2</span> Vandaag</a>
        <a href="#planning"><span class="num">3</span> Planning</a>
        <a href="#afmelden"><span class="num">4</span> Afmelden</a>
        <a href="#tabs"><span class="num">5</span> Tabbladen onderin</a>
        <a href="#telefoon"><span class="num">6</span> App op je telefoon</a>
    </nav>

    <section class="guide-section" id="inloggen">
        <h2>1. Inloggen</h2>
        <p>Open de contract-app. Vul het e-mailadres en wachtwoord in dat je van het taxibedrijf hebt gekregen.</p>
        <ol class="guide-steps">
            <li data-step="1"><strong>E-mail</strong> Gebruik een volledig adres, met een @ en een punt (bijvoorbeeld naam@school.nl).</li>
            <li data-step="2"><strong>Wachtwoord</strong> Tik op het oogje om te controleren wat je typt.</li>
            <li data-step="3"><strong>Inloggen</strong> Klopt iets niet, dan zie je een rode melding onder het veld of onder het formulier.</li>
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
            <figcaption>Inlogscherm: e-mail, wachtwoord en de oranje knop Inloggen.</figcaption>
        </figure>
        <p class="guide-tip"><strong>Tip:</strong> na het inloggen zie je bovenin een banner over deze handleiding. Klik die weg als je wilt — je vindt de handleiding daarna altijd onder Profiel.</p>
    </section>

    <section class="guide-section" id="vandaag">
        <h2>2. Vandaag</h2>
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
        <h2>3. Planning</h2>
        <p>Onder <strong style="color:var(--text)">Planning</strong> zie je de week. Tik op een dag, of gebruik de pijltjes om naar de vorige of volgende week te gaan.</p>
        <ol class="guide-steps">
            <li data-step="1"><strong>Kies een dag</strong> in de weekstrook bovenaan.</li>
            <li data-step="2"><strong>Vandaag</strong> springt terug naar de huidige dag.</li>
            <li data-step="3"><strong>Geen vervoer</strong> Op een vrije dag of uitzondering staat dat bij de reiziger.</li>
        </ol>
        <figure class="guide-shot">
            <button type="button" class="guide-zoom" aria-label="Vergroot: weekplanning">
                <div class="phone-frame">
                    <img src="{{ asset('assets/marketing/images/feature-contract-planning.png') }}" alt="Contract-app: weekplanning met ritten per dag">
                </div>
            </button>
            <figcaption>Planning: de week in één overzicht, tik een dag aan.<span class="guide-zoom-hint">Tik om te vergroten</span></figcaption>
        </figure>
    </section>

    <section class="guide-section" id="afmelden">
        <h2>4. Afmelden</h2>
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

    <section class="guide-section" id="tabs">
        <h2>5. Tabbladen onderin</h2>
        <p>Onderin de app staan vier tabbladen. Tik erop om te wisselen.</p>
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
                    <p>De weekoverzicht. Blader per week en tik een dag aan.</p>
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
                    <div class="mock-nav"><span>Vandaag</span><span>Planning</span><span>Afmeldingen</span><b>Profiel</b></div>
                </div>
            </div>
            <figcaption>Onder Profiel staat Handleiding — ook nadat je de banner hebt gesloten.</figcaption>
        </figure>
    </section>

    <section class="guide-section" id="telefoon">
        <h2>6. App op je telefoon</h2>
        <p>Zet de contract-app op het beginscherm. Geen App Store: één keer openen, daarna een icoon naast WhatsApp.</p>
        <ol class="guide-steps">
            <li data-step="1"><strong>iPhone</strong> Open in Safari → Deel → Zet op beginscherm.</li>
            <li data-step="2"><strong>Android</strong> Open in Chrome → menu → App installeren of Toevoegen aan startscherm.</li>
            <li data-step="3"><strong>Snel openen</strong> Daarna tik je het icoon aan, zonder telkens het wachtwoord te zoeken in de mail.</li>
        </ol>
        <p class="guide-tip"><strong>Onthoud:</strong> banner weggeklikt? Open de app → <strong>Profiel</strong> → <strong>Handleiding</strong>.</p>
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
