<!DOCTYPE html>
<html lang="nl" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0B1220" data-nexa-dark-theme-color="#0B1220">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="icon" href="{{ $faviconUrl }}" type="{{ $faviconType }}">
    <title>NEXA Taxi marktplaats</title>
    @include('taxi::partials.pwa-theme', ['section' => 'boot'])
    @include('taxi::partials.pwa-theme', ['section' => 'styles'])
    @include('taxi::partials.pwa-brand-metrics')
    <style>
        html[data-theme="dark"] {
            --bg: #0B1220;
            --text: #F8FAFC;
            --muted: #94A3B8;
            --border: rgba(148, 163, 184, 0.22);
            --primary: #2563EB;
            --primary-pressed: #1D4ED8;
            --danger: #F87171;
            --success: #22C55E;
            --input-bg: rgba(255, 255, 255, 0.03);
        }
        html[data-theme="light"] {
            --bg: #F1F5F9;
            --text: #0F172A;
            --muted: #475569;
            --border: rgba(15, 23, 42, 0.14);
            --primary: #1D4ED8;
            --primary-pressed: #1E40AF;
            --danger: #DC2626;
            --success: #15803D;
            --input-bg: rgba(15, 23, 42, 0.05);
        }
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        html, body { height: 100%; margin: 0; }
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg);
            color: var(--text);
        }
        .screen {
            min-height: 100%;
            max-width: 480px;
            margin: 0 auto;
            padding: calc(env(safe-area-inset-top, 0px) + 8px) 0 calc(28px + env(safe-area-inset-bottom, 0px));
        }
        .logo-row {
            display: grid;
            grid-template-columns: var(--nexa-page-side-slot) 1fr var(--nexa-page-side-slot);
            align-items: center;
            min-height: var(--nexa-brand-logo-bar-min);
            padding: 2px 12px;
        }
        .side-slot {
            width: var(--nexa-page-side-slot);
            height: var(--nexa-page-side-slot);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logo-center { display: flex; justify-content: center; }
        html[data-theme="dark"] .app-logo-light { display: none !important; }
        html[data-theme="light"] .app-logo-dark { display: none !important; }
        .side-slot > .nexa-pwa-chrome-actions {
            position: static !important;
            top: auto !important;
            right: auto !important;
            height: 2.25rem;
            padding: 0;
            z-index: auto;
        }
        .screen-title {
            margin: 0;
            padding: 4px 12px 6px;
            text-align: center;
        }
        .body {
            padding: 0 20px 40px;
        }
        .sub {
            margin: 0 0 22px;
            color: var(--muted);
            font-size: 15px;
            line-height: 22px;
        }
        .field { margin-bottom: 14px; }
        .label {
            display: block;
            margin: 0 0 6px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }
        .field input {
            display: block;
            width: 100%;
            min-height: 48px;
            margin: 0;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px 14px;
            background: var(--input-bg);
            color: var(--text);
            font: inherit;
            font-size: 16px;
        }
        .field input::placeholder { color: var(--muted); }
        .field input:focus {
            outline: none;
            border-color: var(--primary);
        }
        .field input[aria-invalid="true"] { border-color: var(--danger); }
        .field input:-webkit-autofill,
        .field input:-webkit-autofill:hover,
        .field input:-webkit-autofill:focus {
            -webkit-text-fill-color: var(--text);
            caret-color: var(--text);
            box-shadow: 0 0 0 1000px var(--bg) inset;
            transition: background-color 9999s ease-out;
        }
        .field-error {
            margin: 6px 0 0;
            color: var(--danger);
            font-size: 13px;
            line-height: 1.3;
        }
        .hint, .error {
            margin: 4px 0 4px;
            font-size: 15px;
            line-height: 22px;
        }
        .hint { color: var(--muted); }
        .hint.is-success {
            color: var(--success);
            font-weight: 700;
            margin-bottom: 12px;
        }
        .error { color: var(--danger); font-size: 14px; margin-top: 8px; }
        .btn {
            display: block;
            width: 100%;
            margin-top: 8px;
            border-radius: 14px;
            padding: 14px 16px;
            text-align: center;
            text-decoration: none;
            font: inherit;
            cursor: pointer;
        }
        .btn:disabled { opacity: 0.55; cursor: default; }
        .btn-primary {
            border: 0;
            background: var(--primary);
            color: #fff;
            font-size: 16px;
            font-weight: 700;
        }
        .btn-primary:hover:not(:disabled) { background: var(--primary-pressed); }
        .btn-ghost {
            border: 1px solid var(--border);
            background: transparent;
            color: var(--muted);
            font-size: 15px;
            font-weight: 600;
        }
        [hidden] { display: none !important; }
    </style>
</head>
<body>
@include('taxi::partials.pwa-theme', ['section' => 'widget'])

<div class="screen">
    <div class="logo-row">
        <div class="side-slot" aria-hidden="true"></div>
        <div class="logo-center">
            <img class="app-logo-light nexa-brand-logo" src="{{ $logoLightUrl }}" alt="NEXA | taxi" width="168" height="42" decoding="async">
            <img class="app-logo-dark nexa-brand-logo" src="{{ $logoDarkUrl }}" alt="NEXA | taxi" width="168" height="42" decoding="async">
        </div>
        <div class="side-slot" id="theme-slot"></div>
    </div>
    <h1 class="screen-title nexa-screen-title" id="screen-title">NEXA Taxi marktplaats</h1>

    <div class="body">
        <p class="sub" id="screen-sub">Meld je taxibedrijf aan op de NEXA Taxi marktplaats.<br>Na registratie ontvang je een code per e-mail.</p>

        <div id="step-form">
            <div class="field">
                <label class="label" for="company_name">Bedrijfsnaam</label>
                <input id="company_name" type="text" autocomplete="organization" autocapitalize="words" placeholder="Taxi Amsterdam" enterkeyhint="next">
                <p class="field-error" data-field-error="company_name" hidden></p>
            </div>
            <div class="field">
                <label class="label" for="email">E-mailadres</label>
                <input id="email" type="email" autocomplete="email" inputmode="email" autocapitalize="none" placeholder="beheer@taxibedrijf.nl" enterkeyhint="next">
                <p class="field-error" data-field-error="email" hidden></p>
            </div>
            <div class="field">
                <label class="label" for="phone">Telefoon</label>
                <input id="phone" type="tel" autocomplete="tel" inputmode="tel" placeholder="06…" enterkeyhint="next">
                <p class="field-error" data-field-error="phone" hidden></p>
            </div>
            <div class="field">
                <label class="label" for="city">Plaats</label>
                <input id="city" type="text" autocomplete="address-level2" autocapitalize="words" placeholder="Amsterdam" enterkeyhint="done">
                <p class="field-error" data-field-error="city" hidden></p>
            </div>
        </div>

        <div id="step-verify" hidden>
            <div class="field">
                <label class="label" for="verify_email">E-mailadres</label>
                <input id="verify_email" type="email" autocomplete="email" inputmode="email" autocapitalize="none" placeholder="beheer@taxibedrijf.nl" enterkeyhint="next">
                <p class="field-error" data-field-error="verify_email" hidden></p>
            </div>
            <div class="field">
                <label class="label" for="verify_code">Code uit e-mail</label>
                <input id="verify_code" type="text" inputmode="numeric" maxlength="6" autocomplete="one-time-code" placeholder="000000" enterkeyhint="next">
                <p class="field-error" data-field-error="verify_code" hidden></p>
            </div>
            <div class="field">
                <label class="label" for="verify_password">Wachtwoord (optioneel)</label>
                <input id="verify_password" type="password" autocomplete="new-password" placeholder="Min. 8 tekens" enterkeyhint="done">
                <p class="field-error" data-field-error="verify_password" hidden></p>
            </div>
        </div>

        <p class="hint" id="screen-hint" hidden></p>
        <p class="error" id="screen-error" role="alert" hidden></p>

        <div id="actions-form">
            <button type="button" class="btn btn-primary" id="btn-register">Aanmelden</button>
            <button type="button" class="btn btn-ghost" id="btn-have-code">Ik heb al een code</button>
            <a class="btn btn-ghost" href="{{ $backUrl }}">Terug</a>
        </div>
        <div id="actions-verify" hidden>
            <button type="button" class="btn btn-primary" id="btn-activate">Account activeren</button>
            <button type="button" class="btn btn-ghost" id="btn-resend">Code opnieuw sturen</button>
            <button type="button" class="btn btn-ghost" id="btn-back-form">Terug naar aanmelden</button>
        </div>
    </div>
</div>
<script>
(function () {
    var chrome = document.getElementById('nexa-pwa-chrome-actions');
    var slot = document.getElementById('theme-slot');
    if (chrome && slot) slot.appendChild(chrome);

    var URLS = {
        register: @json($registerUrl),
        code: @json($codeUrl),
        verify: @json($verifyUrl)
    };
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var titleEl = document.getElementById('screen-title');
    var subEl = document.getElementById('screen-sub');
    var hintEl = document.getElementById('screen-hint');
    var errorEl = document.getElementById('screen-error');
    var stepForm = document.getElementById('step-form');
    var stepVerify = document.getElementById('step-verify');
    var actionsForm = document.getElementById('actions-form');
    var actionsVerify = document.getElementById('actions-verify');
    var companyEl = document.getElementById('company_name');
    var emailEl = document.getElementById('email');
    var phoneEl = document.getElementById('phone');
    var cityEl = document.getElementById('city');
    var verifyEmailEl = document.getElementById('verify_email');
    var codeEl = document.getElementById('verify_code');
    var passwordEl = document.getElementById('verify_password');
    var registerBtn = document.getElementById('btn-register');
    var haveCodeBtn = document.getElementById('btn-have-code');
    var activateBtn = document.getElementById('btn-activate');
    var resendBtn = document.getElementById('btn-resend');
    var busy = false;

    function csrfToken() {
        return csrfMeta ? (csrfMeta.getAttribute('content') || '') : '';
    }

    function setCsrfToken(token) {
        if (token && csrfMeta) csrfMeta.setAttribute('content', token);
    }

    function isValidEmail(value) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value || '').trim());
    }

    function setFieldError(id, message) {
        var input = document.getElementById(id);
        var error = document.querySelector('[data-field-error="' + id + '"]');
        if (input) {
            if (message) input.setAttribute('aria-invalid', 'true');
            else input.removeAttribute('aria-invalid');
        }
        if (error) {
            error.textContent = message || '';
            error.hidden = !message;
        }
    }

    function clearFormErrors() {
        ['company_name', 'email', 'phone', 'city'].forEach(function (id) { setFieldError(id, ''); });
    }

    function clearVerifyErrors() {
        ['verify_email', 'verify_code', 'verify_password'].forEach(function (id) { setFieldError(id, ''); });
    }

    ['company_name', 'email', 'phone', 'city', 'verify_email', 'verify_code', 'verify_password'].forEach(function (id) {
        var input = document.getElementById(id);
        if (!input) return;
        input.addEventListener('input', function () { setFieldError(id, ''); });
    });

    function showHint(message) {
        if (!hintEl) return;
        var text = message || '';
        hintEl.textContent = text;
        hintEl.hidden = text === '';
        hintEl.classList.toggle('is-success', /code gestuurd/i.test(text));
    }

    function showError(message) {
        if (!errorEl) return;
        errorEl.textContent = message || '';
        errorEl.hidden = !message;
    }

    function jsonMessage(data, fallback) {
        if (data && typeof data.message === 'string' && data.message !== '') return data.message;
        if (data && data.errors) {
            var first = Object.values(data.errors)[0];
            if (Array.isArray(first) && first[0]) return first[0];
        }
        return fallback;
    }

    function setBusy(on) {
        busy = on;
        [registerBtn, haveCodeBtn, activateBtn, resendBtn].forEach(function (btn) {
            if (btn) btn.disabled = on;
        });
    }

    async function postJson(url, payload, retryOnCsrf) {
        var body = Object.assign({ _token: csrfToken() }, payload);
        var response = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(body)
        });
        var data = await response.json().catch(function () { return {}; });
        if (retryOnCsrf !== false && response.status === 419 && data.csrf_token) {
            setCsrfToken(data.csrf_token);
            return postJson(url, payload, false);
        }
        return { response: response, data: data };
    }

    function showForm() {
        stepForm.hidden = false;
        actionsForm.hidden = false;
        stepVerify.hidden = true;
        actionsVerify.hidden = true;
        titleEl.textContent = 'NEXA Taxi marktplaats';
        subEl.innerHTML = 'Meld je taxibedrijf aan op de NEXA Taxi marktplaats.<br>Na registratie ontvang je een code per e-mail.';
        showHint('');
        showError('');
        clearVerifyErrors();
        companyEl.focus();
    }

    function showVerify(hint) {
        verifyEmailEl.value = (emailEl.value || verifyEmailEl.value || '').trim();
        stepForm.hidden = true;
        actionsForm.hidden = true;
        stepVerify.hidden = false;
        actionsVerify.hidden = false;
        titleEl.textContent = 'Bevestig e-mail';
        subEl.textContent = 'Vul de code uit je e-mail in om je account te activeren.';
        showError('');
        clearFormErrors();
        showHint(hint || '');
        codeEl.focus();
    }

    function applyRegisterErrors(errors) {
        var map = { company_name: 'company_name', email: 'email', phone: 'phone', city: 'city' };
        var first = null;
        var applied = false;
        Object.keys(errors || {}).forEach(function (field) {
            var id = map[field];
            if (!id) return;
            var messages = errors[field];
            var message = Array.isArray(messages) ? (messages[0] || '') : String(messages || '');
            if (!message) return;
            setFieldError(id, message);
            applied = true;
            if (!first) first = id;
        });
        if (first) document.getElementById(first).focus();
        return applied;
    }

    function validateRegister(payload) {
        clearFormErrors();
        showError('');
        var first = null;
        if (!payload.company_name) {
            setFieldError('company_name', 'Vul de bedrijfsnaam in.');
            first = first || 'company_name';
        }
        if (!payload.email) {
            setFieldError('email', 'Vul een e-mailadres in.');
            first = first || 'email';
        } else if (!isValidEmail(payload.email)) {
            setFieldError('email', 'Vul een geldig e-mailadres in.');
            first = first || 'email';
        }
        if (!payload.phone) {
            setFieldError('phone', 'Vul een telefoonnummer in.');
            first = first || 'phone';
        }
        if (!payload.city) {
            setFieldError('city', 'Vul de plaats in waar het bedrijf zich bevindt.');
            first = first || 'city';
        } else if (payload.city.length < 2) {
            setFieldError('city', 'Plaats moet minimaal 2 tekens bevatten.');
            first = first || 'city';
        }
        if (first) {
            document.getElementById(first).focus();
            return false;
        }
        return true;
    }

    function passwordError(password) {
        if (password.length < 8) return 'Wachtwoord moet minimaal 8 karakters lang zijn.';
        if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(password)) {
            return 'Wachtwoord moet minimaal 1 kleine letter, 1 hoofdletter en 1 cijfer bevatten.';
        }
        return '';
    }

    registerBtn.addEventListener('click', async function () {
        if (busy) return;
        var payload = {
            company_name: (companyEl.value || '').trim(),
            email: (emailEl.value || '').trim(),
            phone: (phoneEl.value || '').trim(),
            city: (cityEl.value || '').trim()
        };
        if (!validateRegister(payload)) return;
        setBusy(true);
        try {
            var result = await postJson(URLS.register, payload);
            var data = result.data || {};
            if (result.response.ok || result.response.status === 201) {
                emailEl.value = data.email || payload.email;
                showVerify(data.message || 'Bedrijf geregistreerd. Vul de code uit je e-mail in.');
                return;
            }
            if (data.errors && applyRegisterErrors(data.errors)) {
                showError('');
                return;
            }
            showError(jsonMessage(data, 'Registreren is niet gelukt.'));
        } catch (e) {
            showError('Registreren is niet gelukt. Probeer het opnieuw.');
        } finally {
            setBusy(false);
        }
    });

    haveCodeBtn.addEventListener('click', function () {
        if (busy) return;
        showError('');
        setFieldError('email', '');
        var email = (emailEl.value || '').trim();
        if (!email || !isValidEmail(email)) {
            setFieldError('email', 'Vul eerst je e-mailadres in om de code te bevestigen.');
            emailEl.focus();
            return;
        }
        showVerify('Vul de code uit je e-mail in. Geen code ontvangen? Stuur hem opnieuw.');
    });

    resendBtn.addEventListener('click', async function () {
        if (busy) return;
        showError('');
        setFieldError('verify_email', '');
        var email = (verifyEmailEl.value || '').trim();
        if (!email || !isValidEmail(email)) {
            setFieldError('verify_email', 'Vul een geldig e-mailadres in.');
            verifyEmailEl.focus();
            return;
        }
        emailEl.value = email;
        setBusy(true);
        try {
            var result = await postJson(URLS.code, { email: email });
            var data = result.data || {};
            if (result.response.ok) {
                showHint(data.message || 'We hebben een code naar je e-mail gestuurd.');
                return;
            }
            var message = jsonMessage(data, 'De code kon niet worden verstuurd.');
            if (data.errors && data.errors.email) {
                setFieldError('verify_email', data.errors.email[0] || message);
                return;
            }
            showError(message);
        } catch (e) {
            showError('De code kon niet worden verstuurd. Probeer het opnieuw.');
        } finally {
            setBusy(false);
        }
    });

    activateBtn.addEventListener('click', async function () {
        if (busy) return;
        clearVerifyErrors();
        showError('');
        var email = (verifyEmailEl.value || '').trim();
        var code = (codeEl.value || '').trim();
        var password = passwordEl.value || '';
        if (!email || !isValidEmail(email)) {
            setFieldError('verify_email', 'Vul een geldig e-mailadres in.');
            verifyEmailEl.focus();
            return;
        }
        if (code.length !== 6) {
            setFieldError('verify_code', 'Vul de 6-cijferige code uit je e-mail in.');
            codeEl.focus();
            return;
        }
        var payload = { email: email, code: code, skip_password: !password };
        if (password) {
            var pwError = passwordError(password);
            if (pwError) {
                setFieldError('verify_password', pwError);
                passwordEl.focus();
                return;
            }
            payload.password = password;
            payload.password_confirmation = password;
        }
        setBusy(true);
        try {
            var result = await postJson(URLS.verify, payload);
            var data = result.data || {};
            if (result.response.ok && data.redirect) {
                window.location.href = data.redirect;
                return;
            }
            if (data.errors && data.errors.code) {
                setFieldError('verify_code', data.errors.code[0] || jsonMessage(data, 'Inloggen is niet gelukt.'));
                return;
            }
            if (data.errors && data.errors.password) {
                setFieldError('verify_password', data.errors.password[0]);
                return;
            }
            showError(jsonMessage(data, 'Inloggen is niet gelukt.'));
        } catch (e) {
            showError('Inloggen is niet gelukt. Probeer het opnieuw.');
        } finally {
            setBusy(false);
        }
    });

    document.getElementById('btn-back-form').addEventListener('click', showForm);

    cityEl.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            registerBtn.click();
        }
    });
    passwordEl.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            activateBtn.click();
        }
    });
})();
</script>
</body>
</html>
