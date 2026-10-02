<!--
Product: Metronic is a toolkit of UI components built with Tailwind CSS for developing scalable web applications quickly and efficiently
Version: v9.3.5
Author: Keenthemes
-->
<!DOCTYPE html>
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" dir="ltr" lang="nl">
<head>
    <base href="{{ url('/') }}">
    <title>Admin Login - NEXA Suite</title>
    <meta charset="utf-8"/>
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <meta content="follow, index" name="robots"/>
    <meta content="width=device-width, initial-scale=1, shrink-to-fit=no" name="viewport"/>
    <meta content="Admin login page for NEXA" name="description"/>
    @include('layouts.partials.pwa-favicon')
    @include('layouts.partials.auth-head-assets')
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
        .page-bg {
            background-color: var(--background);
            background-image:
                radial-gradient(ellipse at top left, rgb(249 115 22 / 0.10), transparent 52%),
                radial-gradient(ellipse at bottom right, rgb(37 99 235 / 0.07), transparent 48%);
        }
        @keyframes first-login-spin {
            to { transform: rotate(360deg); }
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

        /* Fout-rand =zelfde rood als fouttekst (text-destructive) */
        .kt-card .kt-input.border-destructive,
        .kt-card input.kt-input.border-destructive,
        .kt-card textarea.kt-input.border-destructive {
            border-color: var(--destructive) !important;
        }
        .kt-card input.kt-input.border-destructive:focus,
        .kt-card input.kt-input.border-destructive:focus-visible,
        .kt-card textarea.kt-input.border-destructive:focus,
        .kt-card textarea.kt-input.border-destructive:focus-visible {
            border-color: var(--destructive) !important;
            --tw-ring-color: color-mix(in oklab, var(--destructive) 30%, transparent);
        }

        /*
         * Wachtwoord-wrapper is een div.kt-input: focus zit op de inner input,
         * dus :focus-visible op de wrapper vuurt niet. focus-within = zelfde glow als andere velden.
         */
        .kt-card div.kt-input:focus-within {
            border-color: var(--ring);
            --tw-ring-shadow: var(--tw-ring-inset,) 0 0 0 calc(3px + var(--tw-ring-offset-width)) var(--tw-ring-color, currentcolor);
            box-shadow: var(--tw-inset-shadow), var(--tw-inset-ring-shadow), var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow);
            --tw-ring-color: color-mix(in oklab, var(--ring) 30%, transparent);
            outline: none;
        }
        .kt-card div.kt-input.border-destructive:focus-within {
            border-color: var(--destructive) !important;
            --tw-ring-color: color-mix(in oklab, var(--destructive) 30%, transparent);
        }
        .kt-card div.kt-input input:focus,
        .kt-card div.kt-input input:focus-visible {
            outline: none;
            box-shadow: none;
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

                <form action="{{ route('admin.login.post') }}" class="flex flex-col gap-5" id="sign_in_form" method="POST" novalidate @if($openFirstLogin) hidden @endif>
                    @csrf
                    @php $intendedValue = \App\Support\AdminReturnUrl::resolveIntended(old('intended', request()->query('intended') ?? session('url.intended'))); @endphp
                    @if($intendedValue)
                        <input type="hidden" name="intended" value="{{ $intendedValue }}">
                    @endif

                    @if(session('error'))
                        <div class="kt-alert kt-alert-warning flex items-center gap-2.5 p-4 rounded-lg border border-amber-500 bg-amber-50 dark:bg-amber-900/20">
                            <i class="ki-filled ki-information-5 text-xl text-amber-600 dark:text-amber-400"></i>
                            <div class="text-sm font-medium text-amber-800 dark:text-amber-200">{{ session('error') }}</div>
                        </div>
                    @endif

                    <div class="flex flex-col gap-2.5">
                        <label class="kt-form-label font-normal text-mono" for="login_email">
                            E-mail
                        </label>
                        <input class="kt-input @error('email') border-destructive @enderror"
                               id="login_email"
                               placeholder="email@email.com"
                               type="email"
                               name="email"
                               value="{{ old('email') }}"
                               autocomplete="username"
                               autofocus/>
                        <div class="text-xs text-destructive mt-1 @error('email') @else hidden @enderror" data-field-error="login_email">
                            @error('email'){{ $message }}@enderror
                        </div>
                    </div>

                    <div class="flex flex-col gap-2.5">
                        <label class="kt-form-label font-normal text-mono" for="login_password">
                            Wachtwoord
                        </label>
                        <div class="kt-input @error('password') border-destructive @enderror" data-kt-toggle-password="true" data-kt-toggle-password-permanent="true" data-field-control="login_password">
                            <input id="login_password"
                                   name="password"
                                   placeholder="Voer wachtwoord in"
                                   type="password"
                                   value=""
                                   autocomplete="current-password"/>
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
                        <div class="text-xs text-destructive mt-1 @error('password') @else hidden @enderror" data-field-error="login_password">
                            @error('password'){{ $message }}@enderror
                        </div>
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
                        Inloggen met e-mailcode
                    </button>
                    <button type="button" class="text-sm link text-primary w-full text-center leading-snug" id="toggle-marketplace-login">
                        Taxibedrijf inloggen of registreren<br>
                        <span class="font-normal">(Marketplace)</span>
                    </button>
                </form>

                <div id="marketplace_login_step" class="flex flex-col gap-4" hidden>
                    <p class="text-sm text-muted-foreground mb-0">
                        Je bent al aangemeld. Vul je e-mailadres in; we sturen een inlogcode.
                    </p>
                    <div id="marketplace-login-status" class="hidden flex items-start gap-2.5 p-3 rounded-lg border text-sm leading-snug" role="status">
                        <i class="ki-filled ki-information-5 text-base text-primary shrink-0 mt-0.5" data-status-icon aria-hidden="true"></i>
                        <span data-status-text></span>
                    </div>
                    <div class="flex flex-col gap-2.5">
                        <label class="kt-form-label font-normal text-mono" for="marketplace_login_email">E-mail</label>
                        <input class="kt-input" id="marketplace_login_email" type="email" autocomplete="username" placeholder="beheer@taxibedrijf.nl">
                        <div class="text-xs text-destructive mt-1 hidden" data-field-error="marketplace_login_email"></div>
                    </div>
                    <button type="button" class="kt-btn kt-btn-primary first-login-btn flex justify-center grow items-center gap-2" id="marketplace-login-submit">
                        <svg class="first-login-spinner" data-loader viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity="0.25"></circle>
                            <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                        </svg>
                        <span data-label>Inlogcode aanvragen</span>
                    </button>
                    <button type="button" class="text-sm link text-primary w-full text-center" id="toggle-marketplace-register">
                        Nog geen account? Taxibedrijf registreren
                    </button>
                    <button type="button" class="text-sm link text-primary w-full text-center" data-back-to-login>
                        Terug naar inloggen
                    </button>
                </div>

                <div id="marketplace_register_step" class="flex flex-col gap-4" hidden>
                    <p class="text-sm text-muted-foreground mb-0">
                        Geen abonnement — alleen fee over Nexa Suite-ritten. Vul je gegevens in; we sturen een code naar je e-mail.
                    </p>
                    <div id="marketplace-register-status" class="hidden flex items-start gap-2.5 p-3 rounded-lg border text-sm leading-snug" role="status">
                        <i class="ki-filled ki-information-5 text-base text-primary shrink-0 mt-0.5" data-status-icon aria-hidden="true"></i>
                        <span data-status-text></span>
                    </div>
                    <div class="flex flex-col gap-2.5">
                        <label class="kt-form-label font-normal text-mono" for="marketplace_company_name">Bedrijfsnaam</label>
                        <input class="kt-input" id="marketplace_company_name" type="text" autocomplete="organization" placeholder="Taxi Amsterdam">
                        <div class="text-xs text-destructive mt-1 hidden" id="marketplace_company_name_error" data-field-error="marketplace_company_name"></div>
                    </div>
                    <div class="flex flex-col gap-2.5">
                        <label class="kt-form-label font-normal text-mono" for="marketplace_email">E-mail</label>
                        <input class="kt-input" id="marketplace_email" type="email" autocomplete="email" placeholder="beheer@taxibedrijf.nl">
                        <div class="text-xs text-destructive mt-1 hidden" id="marketplace_email_error" data-field-error="marketplace_email"></div>
                    </div>
                    <div class="flex flex-col gap-2.5">
                        <label class="kt-form-label font-normal text-mono" for="marketplace_phone">Telefoon</label>
                        <input class="kt-input" id="marketplace_phone" type="tel" autocomplete="tel" placeholder="06…">
                        <div class="text-xs text-destructive mt-1 hidden" id="marketplace_phone_error" data-field-error="marketplace_phone"></div>
                    </div>
                    <div class="flex flex-col gap-2.5">
                        <label class="kt-form-label font-normal text-mono" for="marketplace_city">Plaats</label>
                        <input class="kt-input" id="marketplace_city" type="text" autocomplete="address-level2" placeholder="Amsterdam">
                        <div class="text-xs text-destructive mt-1 hidden" id="marketplace_city_error" data-field-error="marketplace_city"></div>
                    </div>
                    <button type="button" class="kt-btn kt-btn-primary first-login-btn flex justify-center grow items-center gap-2" id="marketplace-register-submit">
                        <svg class="first-login-spinner" data-loader viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity="0.25"></circle>
                            <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                        </svg>
                        <span data-label>Registreren en code ontvangen</span>
                    </button>
                    <button type="button" class="text-sm link text-primary w-full text-center" id="toggle-marketplace-login-from-register">
                        Al geregistreerd? Inloggen
                    </button>
                    <button type="button" class="text-sm link text-primary w-full text-center" data-back-to-login>
                        Terug naar inloggen
                    </button>
                </div>

                <div id="first_login_request_step" class="flex flex-col gap-4" @if(! $openFirstLogin) hidden @endif>
                    <p class="text-sm text-muted-foreground mb-0">
                        Vraag een eenmalige code aan via e-mail. Daarna kun je inloggen met alleen die code, of optioneel een wachtwoord aanmaken.
                    </p>
                    <div id="first-login-request-status" class="flex items-start gap-2.5 p-3 rounded-lg border text-sm leading-snug {{ $firstLoginNotice ? 'border-red-500 bg-primary/5 text-secondary-foreground' : 'hidden' }}" role="status">
                        <i class="ki-filled ki-information-5 text-base text-primary shrink-0 mt-0.5" data-status-icon aria-hidden="true"></i>
                        <span data-status-text>{{ $firstLoginNotice }}</span>
                    </div>
                    <div class="flex flex-col gap-2.5">
                        <label class="kt-form-label font-normal text-mono" for="first_login_email">E-mail</label>
                        <input class="kt-input" id="first_login_email" type="email" autocomplete="username" value="{{ old('email') }}" placeholder="email@email.com">
                        <div class="text-xs text-destructive mt-1 hidden" data-field-error="first_login_email"></div>
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
                        We hebben een eenmalige code naar je e-mail gestuurd. Vul die in om in te loggen. Een wachtwoord is optioneel.
                    </p>
                    <div id="first-login-verify-status" class="hidden flex items-start gap-2.5 p-3 rounded-lg border text-sm leading-snug" role="status">
                        <i class="ki-filled ki-information-5 text-base text-primary shrink-0 mt-0.5" data-status-icon aria-hidden="true"></i>
                        <span data-status-text></span>
                    </div>
                    <div class="flex flex-col gap-2.5">
                        <label class="kt-form-label font-normal text-mono" for="first_login_code">Code uit e-mail</label>
                        <input class="kt-input tracking-widest text-center" id="first_login_code" type="text" inputmode="numeric" maxlength="6" autocomplete="one-time-code" placeholder="000000">
                        <div class="text-xs text-destructive mt-1 hidden" data-field-error="first_login_code"></div>
                    </div>
                    <button type="button" class="kt-btn kt-btn-primary first-login-btn flex justify-center grow items-center gap-2" id="first-login-verify-code">
                        <svg class="first-login-spinner" data-loader viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity="0.25"></circle>
                            <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                        </svg>
                        <span data-label>Inloggen met code</span>
                    </button>
                    <details class="rounded-lg border border-border px-3 py-2">
                        <summary class="text-sm cursor-pointer text-primary font-medium">Optioneel: wachtwoord aanmaken</summary>
                        <div class="flex flex-col gap-3 pt-3 pb-1">
                            <div class="flex flex-col gap-2.5">
                                <label class="kt-form-label font-normal text-mono" for="first_login_password">Nieuw wachtwoord</label>
                                <div>
                                    <div class="kt-input" data-kt-toggle-password="true" data-kt-toggle-password-permanent="true" data-field-control="first_login_password">
                                        <input id="first_login_password" name="password" type="password" autocomplete="new-password" placeholder="Min. 8 tekens">
                                        <button class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon bg-transparent! -me-1.5" data-kt-toggle-password-trigger="true" type="button">
                                            <span class="kt-toggle-password-active:hidden">
                                                <i class="ki-filled ki-eye text-muted-foreground"></i>
                                            </span>
                                            <span class="hidden kt-toggle-password-active:block">
                                                <i class="ki-filled ki-eye-slash text-muted-foreground"></i>
                                            </span>
                                        </button>
                                    </div>
                                    <div class="field-feedback text-xs text-destructive mt-1 hidden" data-field-error="first_login_password" id="first-login-password-feedback"></div>
                                    <p id="first-login-password-hint" class="text-xs text-muted-foreground mt-1 mb-0">Minimaal 8 tekens, met een hoofdletter, een kleine letter en een cijfer.</p>
                                </div>
                            </div>
                            <div class="flex flex-col gap-2.5">
                                <label class="kt-form-label font-normal text-mono" for="first_login_password_confirmation">Bevestig wachtwoord</label>
                                <div>
                                    <div class="kt-input" data-kt-toggle-password="true" data-kt-toggle-password-permanent="true" data-field-control="first_login_password_confirmation">
                                        <input id="first_login_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" placeholder="Herhaal wachtwoord">
                                        <button class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon bg-transparent! -me-1.5" data-kt-toggle-password-trigger="true" type="button">
                                            <span class="kt-toggle-password-active:hidden">
                                                <i class="ki-filled ki-eye text-muted-foreground"></i>
                                            </span>
                                            <span class="hidden kt-toggle-password-active:block">
                                                <i class="ki-filled ki-eye-slash text-muted-foreground"></i>
                                            </span>
                                        </button>
                                    </div>
                                    <div class="field-feedback text-xs text-destructive mt-1 hidden" data-field-error="first_login_password_confirmation" id="first-login-password-match"></div>
                                </div>
                            </div>
                            <button type="button" class="kt-btn kt-btn-outline first-login-btn flex justify-center grow items-center gap-2" id="first-login-verify">
                                <svg class="first-login-spinner" data-loader viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity="0.25"></circle>
                                    <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                                </svg>
                                <span data-label>Wachtwoord opslaan en inloggen</span>
                            </button>
                        </div>
                    </details>
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
    <script src="{{ asset('assets/vendors/ktui/ktui.min.js') }}" defer></script>
    <script>
        (function () {
            const loginForm = document.getElementById('sign_in_form');
            const requestStep = document.getElementById('first_login_request_step');
            const verifyStep = document.getElementById('first_login_verify_step');
            const marketplaceLoginStep = document.getElementById('marketplace_login_step');
            const marketplaceStep = document.getElementById('marketplace_register_step');
            const titleEl = document.getElementById('login-title');
            const emailEl = document.getElementById('first_login_email');
            const loginEmail = document.getElementById('login_email');
            const loginPassword = document.getElementById('login_password');
            const requestStatus = document.getElementById('first-login-request-status');
            const verifyStatus = document.getElementById('first-login-verify-status');
            const marketplaceLoginStatus = document.getElementById('marketplace-login-status');
            const marketplaceStatus = document.getElementById('marketplace-register-status');
            const verifyNote = document.getElementById('first-login-verify-note');
            const requestBtn = document.getElementById('first-login-request');
            const verifyBtn = document.getElementById('first-login-verify');
            const verifyCodeBtn = document.getElementById('first-login-verify-code');
            const marketplaceLoginSubmitBtn = document.getElementById('marketplace-login-submit');
            const marketplaceSubmitBtn = document.getElementById('marketplace-register-submit');
            const marketplaceLoginEmailEl = document.getElementById('marketplace_login_email');
            const firstLoginCodeEl = document.getElementById('first_login_code');
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
                request: 'Inloggen met code',
                verify: 'Code bevestigen',
                marketplace_login: 'Taxibedrijf inloggen',
                marketplace: 'Taxibedrijf registreren',
            };

            function showScreen(name) {
                loginForm?.toggleAttribute('hidden', name !== 'login');
                requestStep?.toggleAttribute('hidden', name !== 'request');
                verifyStep?.toggleAttribute('hidden', name !== 'verify');
                marketplaceLoginStep?.toggleAttribute('hidden', name !== 'marketplace_login');
                marketplaceStep?.toggleAttribute('hidden', name !== 'marketplace');
                if (titleEl) {
                    titleEl.textContent = titles[name] || titles.login;
                }
                if (name === 'request') {
                    emailEl?.focus();
                }
                if (name === 'verify') {
                    document.getElementById('first_login_code')?.focus();
                }
                if (name === 'marketplace_login') {
                    marketplaceLoginEmailEl?.focus();
                }
                if (name === 'marketplace') {
                    document.getElementById('marketplace_company_name')?.focus();
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

            function fieldControlFor(inputId) {
                const input = document.getElementById(inputId);
                if (!input) return null;
                const named = document.querySelector('[data-field-control="' + inputId + '"]');
                return named || input.closest('[data-field-control]') || input;
            }

            function clearFieldError(inputId) {
                const input = document.getElementById(inputId);
                const control = fieldControlFor(inputId);
                const errorEl = document.querySelector('[data-field-error="' + inputId + '"]');
                if (control) control.classList.remove('border-destructive');
                if (input) input.removeAttribute('aria-invalid');
                if (errorEl) {
                    errorEl.textContent = '';
                    errorEl.classList.add('hidden');
                    errorEl.style.display = '';
                }
            }

            function setFieldError(inputId, message) {
                const input = document.getElementById(inputId);
                const control = fieldControlFor(inputId);
                const errorEl = document.querySelector('[data-field-error="' + inputId + '"]');
                if (!input && !errorEl) return false;
                if (control) control.classList.add('border-destructive');
                if (input) input.setAttribute('aria-invalid', 'true');
                if (errorEl) {
                    errorEl.textContent = message || '';
                    errorEl.classList.toggle('hidden', !message);
                    errorEl.style.display = message ? 'block' : '';
                }
                return true;
            }

            function bindClearFieldErrorOnInput(inputId) {
                const input = document.getElementById(inputId);
                if (!input || input.dataset.errorClearBound === '1') return;
                input.dataset.errorClearBound = '1';
                input.addEventListener('input', function () {
                    clearFieldError(inputId);
                });
            }

            function isValidEmail(value) {
                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value || '').trim());
            }

            const marketplaceFieldMap = {
                company_name: 'marketplace_company_name',
                email: 'marketplace_email',
                phone: 'marketplace_phone',
                city: 'marketplace_city',
            };

            function clearMarketplaceFieldErrors() {
                Object.values(marketplaceFieldMap).forEach(clearFieldError);
            }

            function setMarketplaceFieldError(field, message) {
                const inputId = marketplaceFieldMap[field];
                if (!inputId) return false;
                return setFieldError(inputId, message);
            }

            function applyMarketplaceServerErrors(errors) {
                clearMarketplaceFieldErrors();
                let firstField = null;
                let applied = false;
                Object.keys(errors || {}).forEach(function (field) {
                    const messages = errors[field];
                    const message = Array.isArray(messages) ? (messages[0] || '') : String(messages || '');
                    if (!message) return;
                    if (setMarketplaceFieldError(field, message)) {
                        applied = true;
                        if (!firstField) firstField = field;
                    }
                });
                if (firstField && marketplaceFieldMap[firstField]) {
                    document.getElementById(marketplaceFieldMap[firstField])?.focus();
                }
                return applied;
            }

            function validateMarketplaceRegister(payload) {
                clearMarketplaceFieldErrors();
                hideStatus(marketplaceStatus);
                let firstInvalid = null;

                if (!payload.company_name) {
                    setMarketplaceFieldError('company_name', 'Vul de bedrijfsnaam in.');
                    firstInvalid = firstInvalid || 'company_name';
                }
                if (!payload.email) {
                    setMarketplaceFieldError('email', 'Vul een e-mailadres in.');
                    firstInvalid = firstInvalid || 'email';
                } else if (!isValidEmail(payload.email)) {
                    setMarketplaceFieldError('email', 'Vul een geldig e-mailadres in.');
                    firstInvalid = firstInvalid || 'email';
                }
                if (!payload.phone) {
                    setMarketplaceFieldError('phone', 'Vul een telefoonnummer in.');
                    firstInvalid = firstInvalid || 'phone';
                }
                if (!payload.city) {
                    setMarketplaceFieldError('city', 'Vul de plaats in waar het bedrijf zich bevindt.');
                    firstInvalid = firstInvalid || 'city';
                } else if (payload.city.length < 2) {
                    setMarketplaceFieldError('city', 'Plaats moet minimaal 2 tekens bevatten.');
                    firstInvalid = firstInvalid || 'city';
                }

                if (firstInvalid && marketplaceFieldMap[firstInvalid]) {
                    document.getElementById(marketplaceFieldMap[firstInvalid])?.focus();
                    return false;
                }
                return true;
            }

            function validatePasswordLoginForm() {
                clearFieldError('login_email');
                clearFieldError('login_password');
                let firstInvalid = null;
                const email = (loginEmail?.value || '').trim();
                const password = loginPassword?.value || '';

                if (!email) {
                    setFieldError('login_email', 'Vul een e-mailadres in.');
                    firstInvalid = firstInvalid || 'login_email';
                } else if (!isValidEmail(email)) {
                    setFieldError('login_email', 'Vul een geldig e-mailadres in.');
                    firstInvalid = firstInvalid || 'login_email';
                }
                if (!password) {
                    setFieldError('login_password', 'Vul je wachtwoord in.');
                    firstInvalid = firstInvalid || 'login_password';
                }

                if (firstInvalid) {
                    document.getElementById(firstInvalid)?.focus();
                    return false;
                }
                return true;
            }

            [
                'login_email',
                'login_password',
                'marketplace_login_email',
                'first_login_email',
                'first_login_code',
                'first_login_password',
                'first_login_password_confirmation',
            ].concat(Object.values(marketplaceFieldMap)).forEach(bindClearFieldErrorOnInput);

            loginForm?.addEventListener('submit', function (event) {
                if (!validatePasswordLoginForm()) {
                    event.preventDefault();
                }
            });

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
                clearFieldError('first_login_email');
                hideStatus(statusEl);
                const email = (emailEl?.value || '').trim() || rememberedEmail();
                if (emailEl && email && !emailEl.value.trim()) {
                    emailEl.value = email;
                }
                if (!email) {
                    setFieldError('first_login_email', 'Vul je e-mailadres in.');
                    emailEl?.focus();
                    return;
                }
                if (!isValidEmail(email)) {
                    setFieldError('first_login_email', 'Vul een geldig e-mailadres in.');
                    emailEl?.focus();
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
                    if (result.data && result.data.errors && result.data.errors.email) {
                        setFieldError('first_login_email', result.data.errors.email[0] || message);
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
                clearFieldError('first_login_email');
                showScreen('request');
            });

            function openMarketplaceLogin() {
                hideStatus(marketplaceLoginStatus);
                clearFieldError('marketplace_login_email');
                if (loginEmail && marketplaceLoginEmailEl && !marketplaceLoginEmailEl.value) {
                    marketplaceLoginEmailEl.value = loginEmail.value;
                }
                showScreen('marketplace_login');
            }

            function openMarketplaceRegister(prefillEmail) {
                hideStatus(marketplaceStatus);
                clearMarketplaceFieldErrors();
                if (prefillEmail) {
                    const regEmail = document.getElementById('marketplace_email');
                    if (regEmail && !regEmail.value) {
                        regEmail.value = prefillEmail;
                    }
                }
                showScreen('marketplace');
            }

            document.getElementById('toggle-marketplace-login')?.addEventListener('click', openMarketplaceLogin);
            document.getElementById('toggle-marketplace-login-from-register')?.addEventListener('click', openMarketplaceLogin);
            document.getElementById('toggle-marketplace-register')?.addEventListener('click', function () {
                openMarketplaceRegister((marketplaceLoginEmailEl?.value || '').trim());
            });

            document.querySelectorAll('[data-back-to-login]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    hideStatus(requestStatus);
                    hideStatus(verifyStatus);
                    hideStatus(marketplaceLoginStatus);
                    hideStatus(marketplaceStatus);
                    clearMarketplaceFieldErrors();
                    clearFieldError('login_email');
                    clearFieldError('login_password');
                    clearFieldError('marketplace_login_email');
                    clearFieldError('first_login_email');
                    clearFieldError('first_login_code');
                    clearFieldError('first_login_password');
                    clearFieldError('first_login_password_confirmation');
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

            function setFieldFeedback(inputId, message) {
                if (message) {
                    setFieldError(inputId, message);
                } else {
                    clearFieldError(inputId);
                }
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
                    setFieldFeedback('first_login_password', showPassword ? passwordError : '');
                    setFieldFeedback('first_login_password_confirmation', showConfirm ? confirmError : '');
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
                clearFieldError('first_login_code');
                showScreen('request');
                showStatus(requestStatus, message, 'info');
            }

            marketplaceLoginSubmitBtn?.addEventListener('click', async function () {
                clearFieldError('marketplace_login_email');
                hideStatus(marketplaceLoginStatus);
                const email = (marketplaceLoginEmailEl?.value || '').trim();
                if (!email) {
                    setFieldError('marketplace_login_email', 'Vul een e-mailadres in.');
                    marketplaceLoginEmailEl?.focus();
                    return;
                }
                if (!isValidEmail(email)) {
                    setFieldError('marketplace_login_email', 'Vul een geldig e-mailadres in.');
                    marketplaceLoginEmailEl?.focus();
                    return;
                }
                lastRequestedEmail = email;
                setLoading(marketplaceLoginSubmitBtn, true);
                try {
                    const result = await postFirstLogin(@json(route('admin.login.marketplace-code')), { email: email });
                    const data = result.data;
                    if (result.response.ok) {
                        if (emailEl) emailEl.value = email;
                        if (verifyNote) {
                            verifyNote.textContent = data.message || 'We hebben een code naar je e-mail gestuurd.';
                        }
                        hideStatus(verifyStatus);
                        clearFieldError('first_login_code');
                        showScreen('verify');
                        return;
                    }
                    const message = jsonMessage(data, 'Inloggen is niet gelukt.');
                    if (data.errors && data.errors.email) {
                        setFieldError('marketplace_login_email', data.errors.email[0] || message);
                    } else {
                        showStatus(marketplaceLoginStatus, message, false);
                    }
                    if (data.register) {
                        const statusText = marketplaceLoginStatus?.querySelector('[data-status-text]');
                        if (statusText) {
                            showStatus(marketplaceLoginStatus, message, false);
                            statusText.textContent = '';
                            statusText.appendChild(document.createTextNode(message + ' '));
                            const link = document.createElement('button');
                            link.type = 'button';
                            link.className = 'link text-primary underline';
                            link.textContent = 'Ga naar registreren';
                            link.addEventListener('click', function () {
                                openMarketplaceRegister(email);
                            });
                            statusText.appendChild(link);
                        }
                    }
                } catch (e) {
                    showStatus(marketplaceLoginStatus, 'Inloggen is niet gelukt. Probeer het opnieuw.', false);
                } finally {
                    setLoading(marketplaceLoginSubmitBtn, false);
                }
            });

            marketplaceSubmitBtn?.addEventListener('click', async function () {
                const payload = {
                    company_name: (document.getElementById('marketplace_company_name')?.value || '').trim(),
                    email: (document.getElementById('marketplace_email')?.value || '').trim(),
                    phone: (document.getElementById('marketplace_phone')?.value || '').trim(),
                    city: (document.getElementById('marketplace_city')?.value || '').trim(),
                };
                if (!validateMarketplaceRegister(payload)) {
                    return;
                }
                setLoading(marketplaceSubmitBtn, true);
                try {
                    const result = await postFirstLogin(@json(route('admin.login.marketplace-register')), payload);
                    const data = result.data;
                    if (result.response.ok || result.response.status === 201) {
                        lastRequestedEmail = payload.email;
                        if (emailEl) emailEl.value = payload.email;
                        if (verifyNote) {
                            verifyNote.textContent = data.message || 'Bedrijf geregistreerd. Vul de code uit je e-mail in.';
                        }
                        hideStatus(verifyStatus);
                        clearMarketplaceFieldErrors();
                        showScreen('verify');
                        return;
                    }
                    if (data.errors && applyMarketplaceServerErrors(data.errors)) {
                        hideStatus(marketplaceStatus);
                        return;
                    }
                    const firstError = data.errors
                        ? (Object.values(data.errors)[0]?.[0] || null)
                        : null;
                    showStatus(marketplaceStatus, firstError || jsonMessage(data, 'Registreren is niet gelukt.'), false);
                } catch (e) {
                    showStatus(marketplaceStatus, 'Registreren is niet gelukt. Probeer het opnieuw.', false);
                } finally {
                    setLoading(marketplaceSubmitBtn, false);
                }
            });

            async function submitCodeLogin(btn, withPassword) {
                clearFieldError('first_login_code');
                hideStatus(verifyStatus);
                const payload = {
                    email: rememberedEmail(),
                    code: (firstLoginCodeEl?.value || '').trim(),
                    skip_password: !withPassword,
                };
                if (withPassword) {
                    payload.password = document.getElementById('first_login_password')?.value || '';
                    payload.password_confirmation = document.getElementById('first_login_password_confirmation')?.value || '';
                    if (!passwordIsValid(true)) {
                        return;
                    }
                }
                if (payload.code.length !== 6) {
                    setFieldError('first_login_code', 'Vul de 6-cijferige code uit je e-mail in.');
                    firstLoginCodeEl?.focus();
                    return;
                }
                if (!payload.email) {
                    goToRequestWithMessage('Vul je e-mailadres in en vraag een nieuwe code aan.');
                    return;
                }
                setLoading(btn, true);
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
                    if (data.errors && data.errors.code) {
                        setFieldError('first_login_code', data.errors.code[0] || jsonMessage(data, 'Inloggen is niet gelukt.'));
                        return;
                    }
                    if (data.errors && data.errors.password) {
                        setFieldError('first_login_password', data.errors.password[0]);
                        return;
                    }
                    showStatus(verifyStatus, jsonMessage(data, 'Inloggen is niet gelukt.'), false);
                } catch (e) {
                    showStatus(verifyStatus, 'Inloggen is niet gelukt. Probeer het opnieuw.', false);
                } finally {
                    setLoading(btn, false);
                }
            }

            verifyCodeBtn?.addEventListener('click', function () {
                submitCodeLogin(verifyCodeBtn, false);
            });

            verifyBtn?.addEventListener('click', function () {
                submitCodeLogin(verifyBtn, true);
            });

            if (new URLSearchParams(window.location.search).get('marketplace') === '1') {
                openMarketplaceRegister();
            }
        })();
    </script>
    <!-- End of Scripts -->
</body>
</html>
