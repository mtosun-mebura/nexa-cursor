<!DOCTYPE html>
<html lang="nl" class="h-full" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a" data-nexa-dark-theme-color="#0f172a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="icon" href="{{ $faviconUrl }}" type="{{ $faviconType }}">
    <title>Nexa Taxi</title>
    @include('taxi::partials.pwa-theme', ['section' => 'boot'])
    @include('taxi::partials.pwa-theme', ['section' => 'styles'])
    <style>
        :root {
            --blue: #2563eb;
            --blue-hover: #1d4ed8;
            --line: rgba(148, 163, 184, 0.18);
            --safe-top: env(safe-area-inset-top, 0px);
            --safe-bottom: env(safe-area-inset-bottom, 0px);
        }
        html[data-theme="dark"] {
            --bg: #0b1220;
            --card: rgba(18, 26, 43, 0.92);
            --card-primary: linear-gradient(160deg, rgba(37,99,235,.28), rgba(18,26,43,.95));
            --card-primary-border: rgba(59, 130, 246, 0.45);
            --text: #f8fafc;
            --muted: #94a3b8;
            --line: rgba(148, 163, 184, 0.18);
            --badge: #93c5fd;
            --link: #93c5fd;
            --chrome: rgba(11,18,32,.92);
            --bg-glow-a: rgba(37, 99, 235, 0.35);
            --bg-glow-b: rgba(14, 165, 233, 0.18);
        }
        html[data-theme="light"] {
            --bg: #f1f5f9;
            --card: #ffffff;
            --card-primary: linear-gradient(160deg, rgba(37,99,235,.12), #ffffff);
            --card-primary-border: rgba(37, 99, 235, 0.35);
            --text: #0f172a;
            --muted: #64748b;
            --line: rgba(15, 23, 42, 0.1);
            --badge: #1d4ed8;
            --link: #2563eb;
            --chrome: rgba(255,255,255,.94);
            --bg-glow-a: rgba(37, 99, 235, 0.12);
            --bg-glow-b: rgba(14, 165, 233, 0.08);
        }
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        html, body { height: 100%; margin: 0; }
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background:
                radial-gradient(1200px 600px at 10% -10%, var(--bg-glow-a), transparent 55%),
                radial-gradient(900px 500px at 110% 10%, var(--bg-glow-b), transparent 50%),
                var(--bg);
            color: var(--text);
        }
        .app-chrome {
            background: var(--chrome);
            border-bottom: 1px solid var(--line);
            backdrop-filter: blur(10px);
        }
        .app-logo-bar {
            display: flex;
            justify-content: center;
            align-items: flex-end;
            padding-left: 1rem;
            padding-right: 1rem;
            padding-bottom: 0.3rem;
            padding-top: calc(var(--safe-top) + 0.35rem);
        }
        @media (max-width: 48rem) {
            .app-logo-bar {
                padding-top: max(calc(var(--safe-top) + 0.35rem), 3.75rem);
            }
        }
        .app-logo-bar img {
            display: block;
            width: auto;
            height: auto;
            max-width: 11rem;
            max-height: 2.75rem;
            object-fit: contain;
            object-position: center bottom;
            margin: 0 auto;
        }
        html[data-theme="dark"] .app-logo-light { display: none !important; }
        html[data-theme="light"] .app-logo-dark { display: none !important; }
        .app-nav-row {
            display: grid;
            grid-template-columns: 2.75rem 1fr 2.75rem;
            align-items: center;
            gap: 0.35rem;
            min-height: 2.75rem;
            padding: 0.15rem 0.75rem 0.55rem;
            max-width: 480px;
            margin: 0 auto;
            width: 100%;
        }
        .app-nav-start,
        .app-nav-end {
            display: flex;
            align-items: center;
            height: 2.25rem;
        }
        .app-nav-start { justify-content: flex-start; }
        .app-nav-end { justify-content: flex-end; }
        .app-nav-title {
            margin: 0;
            text-align: center;
            font-size: 1.05rem;
            font-weight: 700;
            line-height: 1.2;
            color: var(--text);
        }
        .app-nav-end > .nexa-pwa-chrome-actions {
            position: static !important;
            top: auto !important;
            right: auto !important;
            height: 2.25rem;
            padding: 0;
            z-index: auto;
        }
        .wrap {
            min-height: calc(100% - 7rem);
            display: flex;
            flex-direction: column;
            padding: 20px 20px calc(24px + var(--safe-bottom));
            max-width: 480px;
            margin: 0 auto;
        }
        .sub {
            margin: 0 0 20px;
            color: var(--muted);
            font-size: 0.95rem;
            line-height: 1.45;
            text-align: center;
        }
        .stack { display: grid; gap: 12px; flex: 1; align-content: start; }
        .card {
            display: block; text-decoration: none; color: inherit;
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 18px 18px 16px;
            transition: transform .15s ease, border-color .15s ease, background .15s ease;
            box-shadow: 0 8px 28px rgba(15, 23, 42, 0.06);
        }
        .card:active { transform: scale(0.985); }
        .card.primary {
            background: var(--card-primary);
            border-color: var(--card-primary-border);
        }
        .card h2 { margin: 0 0 6px; font-size: 1.05rem; color: var(--text); }
        .card p { margin: 0; color: var(--muted); font-size: 0.88rem; line-height: 1.4; }
        .badge {
            display: inline-block; font-size: 0.7rem; font-weight: 700; letter-spacing: .04em;
            text-transform: uppercase; color: var(--badge); margin-bottom: 8px;
        }
        .hidden { display: none !important; }
        #role-remembered {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 20px;
            text-align: center;
        }
        #role-remembered .actions { display: grid; gap: 10px; margin-top: 16px; }
        .btn {
            display: block; width: 100%; border: 0; border-radius: 14px;
            padding: 14px 16px; font-size: 1rem; font-weight: 600; cursor: pointer;
        }
        .btn-primary { background: var(--blue); color: #fff; }
        .btn-primary:hover { background: var(--blue-hover); }
        .btn-ghost {
            background: transparent; color: var(--muted);
            border: 1px solid var(--line);
        }
    </style>
</head>
<body>
@include('taxi::partials.pwa-theme', ['section' => 'widget'])

<header class="app-chrome">
    <div class="app-logo-bar">
        <img class="app-logo-light" src="{{ $logoLightUrl }}" alt="Nexa Taxi" width="176" height="44" decoding="async">
        <img class="app-logo-dark" src="{{ $logoDarkUrl }}" alt="Nexa Taxi" width="176" height="44" decoding="async">
    </div>
    <div class="app-nav-row">
        <div class="app-nav-start" aria-hidden="true"></div>
        <h1 class="app-nav-title">Kies je rol</h1>
        <div class="app-nav-end" id="launcher-theme-slot"></div>
    </div>
</header>

<div class="wrap">
    <p class="sub">Eén app voor klanten, chauffeurs en contractanten.</p>

    <div id="role-remembered" class="hidden">
        <p class="sub" id="remembered-label" style="margin:0">Je was eerder ingelogd.</p>
        <div class="actions">
            <button type="button" class="btn btn-primary" id="btn-continue">Doorgaan</button>
            <button type="button" class="btn btn-ghost" id="btn-switch">Andere rol kiezen</button>
        </div>
    </div>

    <div id="role-picker" class="stack hidden">
        <a class="card primary" href="{{ $customerUrl }}?guest=1" data-role="customer-guest">
            <span class="badge">Zonder account</span>
            <h2>Ik wil een taxi boeken</h2>
            <p>Direct boeken als gast. Locatie wordt automatisch bepaald.</p>
        </a>
        <a class="card" href="{{ $customerUrl }}?login=1" data-role="customer">
            <span class="badge">Klant</span>
            <h2>Inloggen als klant</h2>
            <p>Profiel, boekingen en live ritstatus met account.</p>
        </a>
        <a class="card" href="{{ $driverUrl }}" data-role="driver">
            <span class="badge">Chauffeur</span>
            <h2>Inloggen als chauffeur</h2>
            <p>Ritten ontvangen, accepteren en navigeren.</p>
        </a>
        <a class="card" href="{{ $contractUrl }}" data-role="contract">
            <span class="badge">Contract / ouder</span>
            <h2>Inloggen als contractant</h2>
            <p>Planning en afwezigheid voor contractvervoer.</p>
        </a>
        <a class="card" href="{{ url('/admin/login?marketplace=1') }}" data-role="company">
            <span class="badge">Taxibedrijf</span>
            <h2>Aanmelden als taxibedrijf</h2>
            <p>Marketplace zonder abonnement — alleen fee over Nexa Suite-ritten.</p>
        </a>
    </div>
</div>
<script>
(function () {
    var chrome = document.getElementById('nexa-pwa-chrome-actions');
    var slot = document.getElementById('launcher-theme-slot');
    if (chrome && slot) slot.appendChild(chrome);

    const STORAGE_KEY = 'nexa_taxi_app_role';
    const URLS = {
        'customer-guest': @json($customerUrl) + '?guest=1',
        customer: @json($customerUrl) + '?login=1',
        driver: @json($driverUrl),
        contract: @json($contractUrl)
    };
    const LABELS = {
        'customer-guest': 'Doorgaan zonder account (klant)',
        customer: 'Doorgaan als klant',
        driver: 'Doorgaan als chauffeur',
        contract: 'Doorgaan als contractant'
    };

    const remembered = document.getElementById('role-remembered');
    const picker = document.getElementById('role-picker');
    const rememberedLabel = document.getElementById('remembered-label');
    const btnContinue = document.getElementById('btn-continue');
    const btnSwitch = document.getElementById('btn-switch');

    function getRole() {
        try { return localStorage.getItem(STORAGE_KEY) || ''; } catch (e) { return ''; }
    }
    function setRole(role) {
        try { localStorage.setItem(STORAGE_KEY, role); } catch (e) {}
    }
    function clearRole() {
        try { localStorage.removeItem(STORAGE_KEY); } catch (e) {}
    }

    function showPicker() {
        remembered.classList.add('hidden');
        picker.classList.remove('hidden');
    }
    function showRemembered(role) {
        rememberedLabel.textContent = LABELS[role] || 'Doorgaan met je vorige rol';
        remembered.classList.remove('hidden');
        picker.classList.add('hidden');
        btnContinue.onclick = function () {
            window.location.href = URLS[role] || URLS.customer;
        };
    }

    document.querySelectorAll('[data-role]').forEach(function (el) {
        el.addEventListener('click', function () {
            setRole(el.getAttribute('data-role'));
        });
    });

    btnSwitch.addEventListener('click', function () {
        clearRole();
        showPicker();
    });

    const params = new URLSearchParams(window.location.search);
    if (params.get('switch') === '1') {
        clearRole();
        showPicker();
        return;
    }

    const role = getRole();
    if (role && URLS[role]) {
        if (params.get('prompt') === '1') {
            showRemembered(role);
        } else {
            window.location.replace(URLS[role]);
        }
    } else {
        showPicker();
    }
})();
</script>
</body>
</html>
