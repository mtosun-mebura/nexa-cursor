<!--
Product: Metronic is a toolkit of UI components built with Tailwind CSS for developing scalable web applications quickly and efficiently
Version: v9.3.5
Author: Keenthemes
-->
<!DOCTYPE html>
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" dir="ltr" lang="nl">
<head>
    <base href="{{ url('/') }}">
    <title>Admin Login - NEXA</title>
    <meta charset="utf-8"/>
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <meta content="follow, index" name="robots"/>
    <meta content="width=device-width, initial-scale=1, shrink-to-fit=no" name="viewport"/>
    <meta content="Admin login page for NEXA" name="description"/>
    @include('layouts.partials.pwa-favicon')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="{{ asset('assets/vendors/apexcharts/apexcharts.css') }}" rel="stylesheet"/>
    <link href="{{ asset('assets/vendors/keenicons/styles.bundle.css') }}" rel="stylesheet"/>
    <link href="{{ asset('assets/css/styles.css') }}" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased flex h-full text-base text-foreground bg-background">
    <!-- Theme Mode -->
    <script>
        const defaultThemeMode = 'light';
        let themeMode;

        if (document.documentElement) {
            if (localStorage.getItem('kt-theme')) {
                themeMode = localStorage.getItem('kt-theme');
            } else if (document.documentElement.hasAttribute('data-kt-theme-mode')) {
                themeMode = document.documentElement.getAttribute('data-kt-theme-mode');
            } else {
                themeMode = defaultThemeMode;
            }

            if (themeMode === 'system') {
                themeMode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }

            document.documentElement.classList.add(themeMode);
        }
    </script>
    <!-- End of Theme Mode -->
    
    <!-- Page -->
    <style>
        .branded-bg {
            background-image: url('{{ asset('assets/media/images/2600x1600/1.png') }}');
        }
        .dark .branded-bg {
            background-image: url('{{ asset('assets/media/images/2600x1600/1-dark.png') }}');
        }
        
        /* Login form input fields 100% width */
        #sign_in_form .kt-input,
        #first_login_request_step .kt-input,
        #first_login_verify_step .kt-input {
            width: 100% !important;
        }
        
        #sign_in_form .kt-input input,
        #first_login_request_step .kt-input input,
        #first_login_request_step input.kt-input,
        #first_login_verify_step .kt-input input,
        #first_login_verify_step input.kt-input {
            width: 100% !important;
        }
        
        /* Autofill: zelfde achtergrond als kt-input (geen browser-grijs) */
        #sign_in_form input:-webkit-autofill,
        #sign_in_form input:-webkit-autofill:hover,
        #sign_in_form input:-webkit-autofill:focus,
        #sign_in_form input:-webkit-autofill:active,
        #first_login_request_step input:-webkit-autofill,
        #first_login_request_step input:-webkit-autofill:hover,
        #first_login_request_step input:-webkit-autofill:focus,
        #first_login_request_step input:-webkit-autofill:active,
        #first_login_verify_step input:-webkit-autofill,
        #first_login_verify_step input:-webkit-autofill:hover,
        #first_login_verify_step input:-webkit-autofill:focus,
        #first_login_verify_step input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 1000px var(--background) inset !important;
            box-shadow: 0 0 0 1000px var(--background) inset !important;
            -webkit-text-fill-color: var(--foreground) !important;
            caret-color: var(--foreground);
            transition: background-color 5000s ease-in-out 0s;
        }

        #sign_in_form input:autofill,
        #first_login_request_step input:autofill,
        #first_login_verify_step input:autofill {
            box-shadow: 0 0 0 1000px var(--background) inset !important;
            -webkit-text-fill-color: var(--foreground) !important;
            caret-color: var(--foreground);
        }

        .first-login-btn.is-loading {
            opacity: 0.9;
            cursor: wait;
            pointer-events: none;
        }

        .first-login-spinner {
            display: none;
            width: 1rem;
            height: 1rem;
            flex-shrink: 0;
            animation: first-login-spin 0.7s linear infinite;
        }

        .first-login-btn.is-loading .first-login-spinner,
        .first-login-resend.is-loading .first-login-spinner {
            display: block;
        }

        #first-login-request-status.hidden,
        #first-login-verify-status.hidden {
            display: none !important;
        }
    </style>
    
    <div class="flex items-center justify-center grow bg-center bg-no-repeat page-bg">
        <div class="kt-card max-w-[370px] w-full">
            @php
                $openFirstLogin = (bool) session('first_login_required');
                $firstLoginNotice = session('first_login_message');
            @endphp
            <div class="kt-card-content flex flex-col gap-5 p-10">
                <div class="text-center mb-2.5">
                    <div class="mb-4">
                        @include('partials.nexa-brand-logo', ['class' => 'h-10 w-auto mx-auto object-contain'])
                        <div class="mt-2 text-xs font-medium uppercase tracking-wider text-muted-foreground">
                            Administratie paneel
                        </div>
                    </div>
                    <h3 class="text-lg font-medium text-mono leading-none mb-2.5" id="login-title">
                        {{ $openFirstLogin ? 'Eerste keer inloggen' : 'Inloggen' }}
                    </h3>
                </div>

                <form action="{{ route('admin.login.post') }}" class="flex flex-col gap-5" id="sign_in_form" method="POST" @if($openFirstLogin) hidden @endif>
                    @csrf
                    @php $intendedValue = \App\Support\AdminReturnUrl::resolveIntended(old('intended', request()->query('intended') ?? session('url.intended'))); @endphp
                    @if($intendedValue)
                        <input type="hidden" name="intended" value="{{ $intendedValue }}">
                    @endif

                    @error('email')
                        <div class="kt-alert kt-alert-danger flex items-center gap-2.5 p-4 rounded-lg border border-red-500 bg-red-50 dark:bg-red-900/20">
                            <i class="ki-filled ki-information-5 text-xl text-red-600 dark:text-red-400"></i>
                            <div class="text-sm font-medium text-red-800 dark:text-red-200">{{ $message }}</div>
                        </div>
                    @enderror

                    @if(session('error'))
                        <div class="kt-alert kt-alert-warning flex items-center gap-2.5 p-4 rounded-lg border border-amber-500 bg-amber-50 dark:bg-amber-900/20">
                            <i class="ki-filled ki-information-5 text-xl text-amber-600 dark:text-amber-400"></i>
                            <div class="text-sm font-medium text-amber-800 dark:text-amber-200">{{ session('error') }}</div>
                        </div>
                    @endif

                    <div class="flex flex-col gap-2.5">
                        <label class="kt-form-label font-normal text-mono">
                            E-mail
                        </label>
                        <input class="kt-input @error('email') border-danger @enderror"
                               placeholder="email@email.com"
                               type="email"
                               name="email"
                               value="{{ old('email') }}"
                               required
                               autofocus/>
                    </div>

                    <div class="flex flex-col gap-2.5">
                        <label class="kt-form-label font-normal text-mono">
                            Wachtwoord
                        </label>
                        <div class="kt-input" data-kt-toggle-password="true">
                            <input name="password"
                                   placeholder="Voer wachtwoord in"
                                   type="password"
                                   value=""
                                   required/>
                            <button class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon bg-transparent! -me-1.5"
                                    data-kt-toggle-password-trigger="true"
                                    type="button">
                                <span class="kt-toggle-password-active:hidden">
                                    <i class="ki-filled ki-eye text-muted-foreground"></i>
                                </span>
                                <span class="hidden kt-toggle-password-active:block">
                                    <i class="ki-filled ki-eye-slash text-muted-foreground"></i>
                                </span>
                            </button>
                        </div>
                        @error('password')
                            <div class="text-sm text-danger mt-1">{{ $message }}</div>
                        @enderror
                        <a class="text-sm link text-primary mt-1" href="{{ route('admin.password.request') }}">
                            Wachtwoord vergeten?
                        </a>
                    </div>

                    <label class="kt-label">
                        <input class="kt-checkbox kt-checkbox-sm"
                               name="remember"
                               type="checkbox"
                               value="1"/>
                        <span class="kt-checkbox-label">
                            Onthoud mij
                        </span>
                    </label>

                    <button type="submit" class="kt-btn kt-btn-primary flex justify-center grow">
                        Inloggen
                    </button>

                    <button type="button" class="text-sm link text-primary w-full text-center" id="toggle-first-login">
                        Eerste keer inloggen?
                    </button>
                </form>

                <div id="first_login_request_step" class="flex flex-col gap-4" @if(! $openFirstLogin) hidden @endif>
                    <p class="text-sm text-muted-foreground mb-0">
                        Vraag hieronder een eenmalige code aan, die via de e-mail wordt verstuurd. Met deze code kun je zelf een wachtwoord aanmaken.
                    </p>
                    <div id="first-login-request-status" class="flex items-start gap-2.5 p-3 rounded-lg border text-sm leading-snug {{ $firstLoginNotice ? 'border-red-500 bg-primary/5 text-secondary-foreground' : 'hidden' }}" role="status">
                        <i class="ki-filled ki-information-5 text-base text-primary shrink-0 mt-0.5" data-status-icon aria-hidden="true"></i>
                        <span data-status-text>{{ $firstLoginNotice }}</span>
                    </div>
                    <div class="flex flex-col gap-2.5">
                        <label class="kt-form-label font-normal text-mono" for="first_login_email">E-mail</label>
                        <input class="kt-input" id="first_login_email" type="email" autocomplete="username" value="{{ old('email') }}" placeholder="email@email.com">
                    </div>
                    <button type="button" class="kt-btn kt-btn-primary first-login-btn flex justify-center grow items-center gap-2" id="first-login-request">
                        <svg class="first-login-spinner" data-loader viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity="0.25"></circle>
                            <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                        </svg>
                        <span data-label>Inlogcode aanvragen</span>
                    </button>
                    <button type="button" class="text-sm link text-primary w-full text-center" data-back-to-login>
                        Terug naar inloggen
                    </button>
                </div>

                <div id="first_login_verify_step" class="flex flex-col gap-4" hidden>
                    <p class="text-sm text-muted-foreground mb-0" id="first-login-verify-note">
                        We hebben een eenmalige code naar je e-mail gestuurd. Vul die hieronder in en kies een wachtwoord.
                    </p>
                    <div id="first-login-verify-status" class="hidden flex items-start gap-2.5 p-3 rounded-lg border text-sm leading-snug" role="status">
                        <i class="ki-filled ki-information-5 text-base text-primary shrink-0 mt-0.5" data-status-icon aria-hidden="true"></i>
                        <span data-status-text></span>
                    </div>
                    <div class="flex flex-col gap-2.5">
                        <label class="kt-form-label font-normal text-mono" for="first_login_code">Code uit e-mail</label>
                        <input class="kt-input tracking-widest text-center" id="first_login_code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="000000">
                    </div>
                    <div class="flex flex-col gap-2.5">
                        <label class="kt-form-label font-normal text-mono" for="first_login_password">Nieuw wachtwoord</label>
                        <div>
                            <div class="kt-input" data-kt-toggle-password="true">
                                <input id="first_login_password" name="password" type="password" autocomplete="new-password" placeholder="Min. 8 tekens" required>
                                <button class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon bg-transparent! -me-1.5" data-kt-toggle-password-trigger="true" type="button">
                                    <span class="kt-toggle-password-active:hidden">
                                        <i class="ki-filled ki-eye text-muted-foreground"></i>
                                    </span>
                                    <span class="hidden kt-toggle-password-active:block">
                                        <i class="ki-filled ki-eye-slash text-muted-foreground"></i>
                                    </span>
                                </button>
                            </div>
                            <div class="field-feedback text-xs text-red-600 text-destructive mt-1 hidden" data-field="password" id="first-login-password-feedback"></div>
                            <p id="first-login-password-hint" class="text-xs text-muted-foreground mt-1 mb-0">Minimaal 8 tekens, met een hoofdletter, een kleine letter en een cijfer.</p>
                        </div>
                    </div>
                    <div class="flex flex-col gap-2.5">
                        <label class="kt-form-label font-normal text-mono" for="first_login_password_confirmation">Bevestig wachtwoord</label>
                        <div>
                            <div class="kt-input" data-kt-toggle-password="true">
                                <input id="first_login_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" placeholder="Herhaal wachtwoord" required>
                                <button class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon bg-transparent! -me-1.5" data-kt-toggle-password-trigger="true" type="button">
                                    <span class="kt-toggle-password-active:hidden">
                                        <i class="ki-filled ki-eye text-muted-foreground"></i>
                                    </span>
                                    <span class="hidden kt-toggle-password-active:block">
                                        <i class="ki-filled ki-eye-slash text-muted-foreground"></i>
                                    </span>
                                </button>
                            </div>
                            <div class="field-feedback text-xs text-red-600 text-destructive mt-1 hidden" data-field="password_confirmation" id="first-login-password-match"></div>
                        </div>
                    </div>
                    <button type="button" class="kt-btn kt-btn-primary first-login-btn flex justify-center grow items-center gap-2" id="first-login-verify">
                        <svg class="first-login-spinner" data-loader viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity="0.25"></circle>
                            <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                        </svg>
                        <span data-label>Wachtwoord instellen en inloggen</span>
                    </button>
                    <button type="button" class="first-login-resend text-sm link text-primary w-full text-center inline-flex items-center justify-center gap-2" id="first-login-resend">
                        <svg class="first-login-spinner" data-loader viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity="0.25"></circle>
                            <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                        </svg>
                        <span data-label>Nieuwe code aanvragen</span>
                    </button>
                    <button type="button" class="text-sm link text-muted-foreground w-full text-center" data-back-to-login>
                        Terug naar inloggen
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Page -->
    
    <!-- Scripts -->
    <script src="{{ asset('assets/js/core.bundle.js') }}"></script>
    <script src="{{ asset('assets/vendors/ktui/ktui.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/apexcharts/apexcharts.min.js') }}"></script>
    <script>
        (function () {
            const loginForm = document.getElementById('sign_in_form');
            const requestStep = document.getElementById('first_login_request_step');
            const verifyStep = document.getElementById('first_login_verify_step');
            const titleEl = document.getElementById('login-title');
            const emailEl = document.getElementById('first_login_email');
            const loginEmail = document.querySelector('#sign_in_form input[name="email"]');
            const requestStatus = document.getElementById('first-login-request-status');
            const verifyStatus = document.getElementById('first-login-verify-status');
            const verifyNote = document.getElementById('first-login-verify-note');
            const requestBtn = document.getElementById('first-login-request');
            const verifyBtn = document.getElementById('first-login-verify');
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfInput = document.querySelector('#sign_in_form input[name="_token"]');

            function csrfToken() {
                return csrfMeta?.getAttribute('content')
                    || csrfInput?.value
                    || '';
            }

            function setCsrfToken(token) {
                if (!token) return;
                csrfMeta?.setAttribute('content', token);
                if (csrfInput) {
                    csrfInput.value = token;
                }
            }

            function firstLoginHeaders() {
                return {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                };
            }

            async function postFirstLogin(url, payload, retryOnCsrf) {
                const body = Object.assign({ _token: csrfToken() }, payload);
                const response = await fetch(url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: firstLoginHeaders(),
                    body: JSON.stringify(body),
                });
                const data = await response.json().catch(function () { return {}; });
                if (retryOnCsrf !== false && response.status === 419) {
                    if (data.csrf_token) {
                        setCsrfToken(data.csrf_token);
                    }
                    return postFirstLogin(url, payload, false);
                }
                return { response: response, data: data };
            }
            let lastRequestedEmail = (emailEl?.value || '').trim();
            const titles = {
                login: 'Inloggen',
                request: 'Eerste keer inloggen',
                verify: 'Wachtwoord instellen',
            };

            function showScreen(name) {
                loginForm?.toggleAttribute('hidden', name !== 'login');
                requestStep?.toggleAttribute('hidden', name !== 'request');
                verifyStep?.toggleAttribute('hidden', name !== 'verify');
                if (titleEl) {
                    titleEl.textContent = titles[name] || titles.login;
                }
                if (name === 'request') {
                    emailEl?.focus();
                }
                if (name === 'verify') {
                    document.getElementById('first_login_code')?.focus();
                }
            }

            const statusToneClasses = {
                info: {
                    box: ['border-red-500', 'bg-primary/5', 'text-secondary-foreground'],
                    icon: ['text-primary'],
                },
                error: {
                    box: ['border-destructive/20', 'bg-destructive/5', 'text-destructive'],
                    icon: ['text-destructive'],
                },
                success: {
                    box: ['border-emerald-500/25', 'bg-emerald-50', 'text-emerald-800'],
                    icon: ['text-emerald-600'],
                },
            };
            const allStatusBoxClasses = Object.values(statusToneClasses).flatMap(function (tone) { return tone.box; });
            const allStatusIconClasses = Object.values(statusToneClasses).flatMap(function (tone) { return tone.icon; });

            function showStatus(el, message, tone) {
                if (!el) return;
                if (tone === true) tone = 'success';
                if (tone === false || tone == null) tone = 'error';
                const colors = statusToneClasses[tone] || statusToneClasses.info;
                const textEl = el.querySelector('[data-status-text]');
                const iconEl = el.querySelector('[data-status-icon]');
                if (textEl) {
                    textEl.textContent = message;
                } else {
                    el.textContent = message;
                }
                el.classList.remove('hidden', ...allStatusBoxClasses);
                el.classList.add(...colors.box);
                if (iconEl) {
                    iconEl.classList.remove(...allStatusIconClasses);
                    iconEl.classList.add(...colors.icon);
                }
            }

            function hideStatus(el) {
                if (!el) return;
                el.classList.add('hidden');
                const textEl = el.querySelector('[data-status-text]');
                if (textEl) {
                    textEl.textContent = '';
                } else {
                    el.textContent = '';
                }
            }

            function jsonMessage(data, fallback) {
                if (data && typeof data.message === 'string' && data.message !== '') {
                    return data.message;
                }
                if (data && data.errors) {
                    const first = Object.values(data.errors)[0];
                    if (Array.isArray(first) && first[0]) return first[0];
                }
                return fallback;
            }

            function setLoading(btn, loading) {
                if (!btn) return;
                btn.disabled = loading;
                btn.classList.toggle('is-loading', loading);
                const label = btn.querySelector('[data-label]');
                if (label) {
                    if (!label.dataset.original) {
                        label.dataset.original = label.textContent;
                    }
                    label.textContent = loading ? 'Bezig…' : label.dataset.original;
                }
            }

            function rememberedEmail() {
                return lastRequestedEmail
                    || (emailEl?.value || '').trim()
                    || (loginEmail?.value || '').trim();
            }

            function fillRequestEmail() {
                const email = rememberedEmail();
                if (emailEl && email) {
                    emailEl.value = email;
                }
            }

            async function requestCode(btn, statusEl, onSuccess) {
                const email = (emailEl?.value || '').trim() || rememberedEmail();
                if (emailEl && email && !emailEl.value.trim()) {
                    emailEl.value = email;
                }
                if (!email) {
                    showStatus(statusEl, 'Vul je e-mailadres in.', false);
                    return;
                }
                lastRequestedEmail = email;
                setLoading(btn, true);
                try {
                    const result = await postFirstLogin(@json(route('admin.login.first-code')), { email: email });
                    const message = jsonMessage(result.data, 'De code kon niet worden verstuurd.');
                    if (result.response.ok) {
                        onSuccess(message);
                        return;
                    }
                    showStatus(statusEl, message, false);
                } catch (e) {
                    showStatus(statusEl, 'De code kon niet worden verstuurd. Probeer het opnieuw.', false);
                } finally {
                    setLoading(btn, false);
                }
            }

            document.getElementById('toggle-first-login')?.addEventListener('click', function () {
                if (loginEmail && emailEl && !emailEl.value) {
                    emailEl.value = loginEmail.value;
                }
                hideStatus(requestStatus);
                showScreen('request');
            });

            document.querySelectorAll('[data-back-to-login]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    hideStatus(requestStatus);
                    hideStatus(verifyStatus);
                    showScreen('login');
                    loginEmail?.focus();
                });
            });

            requestBtn?.addEventListener('click', function () {
                requestCode(requestBtn, requestStatus, function (message) {
                    if (verifyNote) {
                        verifyNote.textContent = message;
                    }
                    hideStatus(verifyStatus);
                    showScreen('verify');
                });
            });

            document.getElementById('first-login-resend')?.addEventListener('click', function () {
                fillRequestEmail();
                hideStatus(requestStatus);
                hideStatus(verifyStatus);
                showScreen('request');
            });

            function setFieldFeedback(el, message) {
                if (!el) return;
                el.textContent = message || '';
                el.classList.toggle('hidden', !message);
                el.style.display = message ? 'block' : 'none';
            }

            function passwordFieldError(password) {
                if (!password) {
                    return 'Wachtwoord is verplicht.';
                }
                if (password.length < 8) {
                    return 'Wachtwoord moet minimaal 8 karakters lang zijn.';
                }
                if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(password)) {
                    return 'Wachtwoord moet minimaal 1 kleine letter, 1 hoofdletter en 1 cijfer bevatten.';
                }
                return '';
            }

            function confirmationFieldError(password, confirmation) {
                if (!confirmation) {
                    return 'Wachtwoord is verplicht.';
                }
                if (password !== confirmation) {
                    return 'De wachtwoorden komen niet overeen.';
                }
                return '';
            }

            function bindPasswordValidator() {
                const passwordEl = document.getElementById('first_login_password');
                const confirmEl = document.getElementById('first_login_password_confirmation');
                const passwordFeedback = document.getElementById('first-login-password-feedback');
                const matchFeedback = document.getElementById('first-login-password-match');
                if (!passwordEl || !confirmEl) {
                    return function () { return false; };
                }

                function paint(force) {
                    const password = passwordEl.value || '';
                    const confirmation = confirmEl.value || '';
                    const showPassword = force || password.length > 0;
                    const showConfirm = force || confirmation.length > 0;
                    const passwordError = passwordFieldError(password);
                    const confirmError = confirmationFieldError(password, confirmation);
                    setFieldFeedback(passwordFeedback, showPassword ? passwordError : '');
                    setFieldFeedback(matchFeedback, showConfirm ? confirmError : '');
                    return !passwordError && !confirmError;
                }

                passwordEl.addEventListener('input', function () { paint(false); });
                confirmEl.addEventListener('input', function () { paint(false); });
                return paint;
            }

            const passwordIsValid = bindPasswordValidator();

            function goToRequestWithMessage(message) {
                fillRequestEmail();
                hideStatus(verifyStatus);
                showScreen('request');
                showStatus(requestStatus, message, 'info');
            }

            verifyBtn?.addEventListener('click', async function () {
                const payload = {
                    email: rememberedEmail(),
                    code: (document.getElementById('first_login_code')?.value || '').trim(),
                    password: document.getElementById('first_login_password')?.value || '',
                    password_confirmation: document.getElementById('first_login_password_confirmation')?.value || '',
                };
                if (!passwordIsValid(true)) {
                    return;
                }
                if (payload.code.length !== 6) {
                    showStatus(verifyStatus, 'Vul de 6-cijferige code uit je e-mail in.', false);
                    return;
                }
                if (!payload.email) {
                    goToRequestWithMessage('Vul je e-mailadres in en vraag een nieuwe code aan.');
                    return;
                }
                setLoading(verifyBtn, true);
                try {
                    const result = await postFirstLogin(@json(route('admin.login.first-verify')), payload);
                    const data = result.data;
                    if (result.response.ok && data.redirect) {
                        window.location.href = data.redirect;
                        return;
                    }
                    if (data.code === 'code_expired' || result.response.status === 410) {
                        goToRequestWithMessage(jsonMessage(data, 'Je inlogcode is verlopen. Vraag een nieuwe code aan.'));
                        return;
                    }
                    showStatus(verifyStatus, jsonMessage(data, 'Activeren is niet gelukt.'), false);
                } catch (e) {
                    showStatus(verifyStatus, 'Activeren is niet gelukt. Probeer het opnieuw.', false);
                } finally {
                    setLoading(verifyBtn, false);
                }
            });
        })();
    </script>
    <!-- End of Scripts -->
</body>
</html>
