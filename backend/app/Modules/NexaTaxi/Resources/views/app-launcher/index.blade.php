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
        .login-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 18px;
            display: grid;
            gap: 12px;
        }
        .login-card label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--muted);
            margin-bottom: 6px;
        }
        .login-card input {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 1rem;
            background: transparent;
            color: var(--text);
        }
        .login-card .row { display: grid; gap: 10px; }
        .login-error { color: #f87171; font-size: 0.85rem; margin: 0; min-height: 1.2em; }
        .login-hint { color: var(--muted); font-size: 0.8rem; margin: 0; }
        .screens-host { display: grid; gap: 12px; }
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
        <h1 class="app-nav-title" id="app-nav-title">Nexa Taxi</h1>
        <div class="app-nav-end" id="launcher-theme-slot"></div>
    </div>
</header>

<div class="wrap">
    <p class="sub" id="app-sub">Eén app: boeken, chauffeur, marketplace, network én contract.</p>

    <div id="role-remembered" class="hidden">
        <p class="sub" id="remembered-label" style="margin:0">Je was eerder ingelogd.</p>
        <div class="actions">
            <button type="button" class="btn btn-primary" id="btn-continue">Doorgaan</button>
            <button type="button" class="btn btn-ghost" id="btn-switch">Andere keuze</button>
        </div>
    </div>

    <div id="home-picker" class="stack hidden">
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
        <button type="button" class="card" id="btn-staff-login" style="text-align:left;cursor:pointer;width:100%;font:inherit;">
            <span class="badge">Chauffeur / contract / marketplace</span>
            <h2>Inloggen met e-mail</h2>
            <p>We bepalen automatisch of je chauffeur, contract, marketplace en/of network bent.</p>
        </button>
        <a class="card" href="{{ url('/admin/login?marketplace=1') }}" data-role="company">
            <span class="badge">Taxibedrijf</span>
            <h2>Aanmelden als taxibedrijf</h2>
            <p>Marketplace zonder abonnement — alleen fee over Nexa Suite-ritten.</p>
        </a>
    </div>

    <div id="staff-login" class="hidden">
        <div class="login-card">
            <div>
                <label for="login-email">E-mailadres</label>
                <input id="login-email" type="email" autocomplete="username" inputmode="email" placeholder="jij@bedrijf.nl">
            </div>
            <div class="row" id="login-password-row">
                <div>
                    <label for="login-password">Wachtwoord (optioneel)</label>
                    <input id="login-password" type="password" autocomplete="current-password" placeholder="••••••••">
                </div>
            </div>
            <div class="row hidden" id="login-code-row">
                <div>
                    <label for="login-code">Code uit e-mail</label>
                    <input id="login-code" type="text" inputmode="numeric" maxlength="6" autocomplete="one-time-code" placeholder="000000">
                </div>
            </div>
            <p class="login-hint" id="login-hint">Heb je geen wachtwoord? Vraag een eenmalige code aan.</p>
            <p class="login-error" id="login-error" aria-live="polite"></p>
            <button type="button" class="btn btn-primary" id="btn-login-submit">Inloggen</button>
            <button type="button" class="btn btn-ghost" id="btn-request-code">Code sturen</button>
            <button type="button" class="btn btn-ghost" id="btn-login-back">Terug</button>
        </div>
    </div>

    <div id="role-screens" class="screens-host hidden"></div>
</div>
<script>
(function () {
    var chrome = document.getElementById('nexa-pwa-chrome-actions');
    var slot = document.getElementById('launcher-theme-slot');
    if (chrome && slot) slot.appendChild(chrome);

    const STORAGE_KEY = 'nexa_taxi_app_role';
    const SESSION_KEY = 'nexa_taxi_app_session';
    const DRIVER_TOKEN_KEY = 'taxi_driver_token';
    const CONTRACT_TOKEN_KEY = 'taxi_contract_token';
    const URLS = {
        'customer-guest': @json($customerUrl) + '?guest=1',
        customer: @json($customerUrl) + '?login=1',
        driver: @json($driverUrl),
        contract: @json($contractUrl)
    };
    const API = {
        login: @json($appLoginUrl),
        codeRequest: @json($appCodeRequestUrl),
        codeVerify: @json($appCodeVerifyUrl)
    };

    const titleEl = document.getElementById('app-nav-title');
    const subEl = document.getElementById('app-sub');
    const remembered = document.getElementById('role-remembered');
    const homePicker = document.getElementById('home-picker');
    const staffLogin = document.getElementById('staff-login');
    const roleScreens = document.getElementById('role-screens');
    const rememberedLabel = document.getElementById('remembered-label');
    const btnContinue = document.getElementById('btn-continue');
    const btnSwitch = document.getElementById('btn-switch');
    const loginError = document.getElementById('login-error');
    const loginHint = document.getElementById('login-hint');
    const codeRow = document.getElementById('login-code-row');
    let loginChannel = null;

    function getRole() {
        try { return localStorage.getItem(STORAGE_KEY) || ''; } catch (e) { return ''; }
    }
    function setRole(role) {
        try { localStorage.setItem(STORAGE_KEY, role); } catch (e) {}
    }
    function clearRole() {
        try { localStorage.removeItem(STORAGE_KEY); } catch (e) {}
    }
    function saveSession(payload) {
        try { localStorage.setItem(SESSION_KEY, JSON.stringify(payload)); } catch (e) {}
        if (payload.tokens && payload.tokens.driver && payload.tokens.driver.token) {
            try { localStorage.setItem(DRIVER_TOKEN_KEY, payload.tokens.driver.token); } catch (e) {}
        }
        if (payload.tokens && payload.tokens.contract && payload.tokens.contract.token) {
            try { localStorage.setItem(CONTRACT_TOKEN_KEY, payload.tokens.contract.token); } catch (e) {}
        }
    }
    function clearSession() {
        try {
            localStorage.removeItem(SESSION_KEY);
            localStorage.removeItem(DRIVER_TOKEN_KEY);
            localStorage.removeItem(CONTRACT_TOKEN_KEY);
        } catch (e) {}
    }
    function readSession() {
        try {
            var raw = localStorage.getItem(SESSION_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch (e) { return null; }
    }

    function hideAll() {
        remembered.classList.add('hidden');
        homePicker.classList.add('hidden');
        staffLogin.classList.add('hidden');
        roleScreens.classList.add('hidden');
    }
    function showHome() {
        hideAll();
        titleEl.textContent = 'Nexa Taxi';
        subEl.textContent = 'Eén app: boeken, chauffeur, marketplace, network én contract.';
        homePicker.classList.remove('hidden');
    }
    function showLogin() {
        hideAll();
        titleEl.textContent = 'Inloggen';
        subEl.textContent = 'Na inloggen tonen we alleen de schermen die bij jouw rollen horen.';
        staffLogin.classList.remove('hidden');
        loginError.textContent = '';
    }
    function showRemembered(role) {
        hideAll();
        rememberedLabel.textContent = role === 'driver'
            ? 'Doorgaan als chauffeur'
            : (role === 'contract' ? 'Doorgaan als contractant' : 'Doorgaan met je vorige keuze');
        remembered.classList.remove('hidden');
        btnContinue.onclick = function () {
            window.location.href = URLS[role] || URLS.customer;
        };
    }

    function goScreen(screen) {
        if (!screen || !screen.url) return;
        setRole(screen.key);
        window.location.href = screen.url;
    }

    function renderScreens(capabilities) {
        hideAll();
        var screens = (capabilities && capabilities.screens) || [];
        titleEl.textContent = 'Kies je scherm';
        var modes = (capabilities && capabilities.modes) || {};
        var bits = [];
        if (modes.marketplace) bits.push('marketplace');
        if (modes.network) bits.push('network');
        if (modes.chauffeur && !modes.marketplace) bits.push('chauffeur');
        if (modes.contract) bits.push('contract');
        subEl.textContent = bits.length
            ? ('Account herkend: ' + bits.join(' + ') + '. Kies hoe je verder wilt.')
            : 'Kies hoe je verder wilt.';

        if (screens.length === 1) {
            goScreen(screens[0]);
            return;
        }
        if (screens.length === 0) {
            loginError.textContent = 'Geen chauffeur- of contract-toegang op dit account.';
            showLogin();
            return;
        }

        roleScreens.innerHTML = '';
        screens.forEach(function (screen) {
            var a = document.createElement('a');
            a.className = 'card' + (screen.primary ? ' primary' : '');
            a.href = screen.url;
            a.setAttribute('data-role', screen.key);
            a.innerHTML =
                '<span class="badge"></span><h2></h2><p></p>';
            a.querySelector('.badge').textContent = screen.badge || screen.key;
            a.querySelector('h2').textContent = screen.title || screen.key;
            a.querySelector('p').textContent = screen.description || '';
            a.addEventListener('click', function () { setRole(screen.key); });
            roleScreens.appendChild(a);
        });
        var back = document.createElement('button');
        back.type = 'button';
        back.className = 'btn btn-ghost';
        back.textContent = 'Andere account';
        back.addEventListener('click', function () {
            clearSession();
            clearRole();
            showHome();
        });
        roleScreens.appendChild(back);
        roleScreens.classList.remove('hidden');
    }

    async function postJson(url, body) {
        var res = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify(body || {})
        });
        var data = await res.json().catch(function () { return {}; });
        return { ok: res.ok, status: res.status, data: data };
    }

    document.getElementById('btn-staff-login').addEventListener('click', showLogin);
    document.getElementById('btn-login-back').addEventListener('click', showHome);

    document.getElementById('btn-request-code').addEventListener('click', async function () {
        loginError.textContent = '';
        var email = (document.getElementById('login-email').value || '').trim();
        if (!email) {
            loginError.textContent = 'Vul je e-mailadres in.';
            return;
        }
        var result = await postJson(API.codeRequest, { email: email });
        if (!result.ok) {
            loginError.textContent = result.data.message || 'Code versturen mislukt.';
            return;
        }
        loginChannel = result.data.channel || null;
        codeRow.classList.remove('hidden');
        loginHint.textContent = result.data.message || 'Code verstuurd. Vul de code hieronder in.';
        document.getElementById('login-code').focus();
    });

    document.getElementById('btn-login-submit').addEventListener('click', async function () {
        loginError.textContent = '';
        var email = (document.getElementById('login-email').value || '').trim();
        var password = document.getElementById('login-password').value || '';
        var code = (document.getElementById('login-code').value || '').trim();
        if (!email) {
            loginError.textContent = 'Vul je e-mailadres in.';
            return;
        }

        var result;
        if (code) {
            result = await postJson(API.codeVerify, {
                email: email,
                code: code,
                skip_password: !password,
                password: password || undefined,
                channel: loginChannel || undefined
            });
        } else if (password) {
            result = await postJson(API.login, { email: email, password: password });
        } else {
            loginError.textContent = 'Vul je wachtwoord in, of vraag een code aan.';
            return;
        }

        if (!result.ok) {
            loginError.textContent = result.data.message || 'Inloggen mislukt.';
            if (result.data.error === 'first_login_required') {
                codeRow.classList.remove('hidden');
                loginHint.textContent = 'Vraag een eenmalige code aan om verder te gaan.';
            }
            return;
        }

        saveSession(result.data);
        renderScreens(result.data.capabilities || {});
    });

    document.querySelectorAll('#home-picker [data-role]').forEach(function (el) {
        el.addEventListener('click', function () {
            setRole(el.getAttribute('data-role'));
        });
    });

    btnSwitch.addEventListener('click', function () {
        clearRole();
        showHome();
    });

    const params = new URLSearchParams(window.location.search);
    if (params.get('switch') === '1') {
        clearRole();
        clearSession();
        showHome();
        return;
    }

    var session = readSession();
    if (session && session.capabilities && Array.isArray(session.capabilities.screens) && session.capabilities.screens.length) {
        if (params.get('prompt') === '1') {
            renderScreens(session.capabilities);
            return;
        }
        var def = session.capabilities.default_screen;
        var screens = session.capabilities.screens;
        if (def && screens.length === 1) {
            goScreen(screens[0]);
            return;
        }
        if (def) {
            var match = screens.find(function (s) { return s.key === def; });
            if (match && getRole() === def) {
                goScreen(match);
                return;
            }
        }
        renderScreens(session.capabilities);
        return;
    }

    const role = getRole();
    if (role && URLS[role] && (role === 'customer' || role === 'customer-guest')) {
        if (params.get('prompt') === '1') {
            showRemembered(role);
        } else {
            window.location.replace(URLS[role]);
        }
        return;
    }

    showHome();
})();
</script>
</body>
</html>
