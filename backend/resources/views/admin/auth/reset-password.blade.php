<!--
Product: Metronic is a toolkit of UI components built with Tailwind CSS for developing scalable web applications quickly and efficiently
Version: v9.3.5
Author: Keenthemes
-->
<!DOCTYPE html>
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" dir="ltr" lang="nl">
<head>
    <base href="{{ url('/') }}">
    <title>Wachtwoord Resetten - NEXA Skillmatching</title>
    <meta charset="utf-8"/>
    <meta content="follow, index" name="robots"/>
    <meta content="width=device-width, initial-scale=1, shrink-to-fit=no" name="viewport"/>
    <meta content="Wachtwoord resetten pagina voor NEXA Skillmatching Platform" name="description"/>
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
        
        /* Form input fields 100% width */
        #reset_password_change_password_form .kt-input {
            width: 100% !important;
        }
        
        #reset_password_change_password_form .kt-input input {
            width: 100% !important;
            padding-right: 2.75rem;
        }
        
        /* Autofill: zelfde achtergrond als kt-input (geen browser-grijs) */
        #reset_password_change_password_form input:-webkit-autofill,
        #reset_password_change_password_form input:-webkit-autofill:hover,
        #reset_password_change_password_form input:-webkit-autofill:focus,
        #reset_password_change_password_form input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 1000px var(--background) inset !important;
            box-shadow: 0 0 0 1000px var(--background) inset !important;
            -webkit-text-fill-color: var(--foreground) !important;
            caret-color: var(--foreground);
            transition: background-color 5000s ease-in-out 0s;
        }

        #reset_password_change_password_form input:autofill {
            box-shadow: 0 0 0 1000px var(--background) inset !important;
            -webkit-text-fill-color: var(--foreground) !important;
            caret-color: var(--foreground);
        }

        .kt-card .kt-input.border-destructive,
        .kt-card input.kt-input.border-destructive {
            border-color: var(--destructive) !important;
        }
        .kt-card label.kt-input:focus-within,
        .kt-card div.kt-input:focus-within {
            border-color: var(--ring);
            --tw-ring-shadow: var(--tw-ring-inset,) 0 0 0 calc(3px + var(--tw-ring-offset-width)) var(--tw-ring-color, currentcolor);
            box-shadow: var(--tw-inset-shadow), var(--tw-inset-ring-shadow), var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow);
            --tw-ring-color: color-mix(in oklab, var(--ring) 30%, transparent);
            outline: none;
        }
        .kt-card label.kt-input.border-destructive:focus-within,
        .kt-card div.kt-input.border-destructive:focus-within {
            border-color: var(--destructive) !important;
            --tw-ring-color: color-mix(in oklab, var(--destructive) 30%, transparent);
        }
        .kt-card label.kt-input input:focus,
        .kt-card label.kt-input input:focus-visible,
        .kt-card div.kt-input input:focus,
        .kt-card div.kt-input input:focus-visible {
            outline: none;
            box-shadow: none;
        }

        .auth-status-danger {
            display: flex;
            align-items: flex-start;
            gap: 0.625rem;
            padding: 1rem;
            border-radius: 0.5rem;
            border: 1px solid #fca5a5;
            background: #fef2f2;
            color: #991b1b;
        }
        html.dark .auth-status-danger {
            border-color: rgb(248 113 113 / 0.35);
            background: rgb(127 29 29 / 0.4);
            color: #fecaca;
        }
        .auth-status-danger i {
            color: #dc2626;
            flex-shrink: 0;
        }
        html.dark .auth-status-danger i {
            color: #f87171;
        }
    </style>
    
    <div class="flex items-center justify-center grow bg-center bg-no-repeat page-bg">
        <div class="kt-card max-w-[370px] w-full">
            <form action="{{ route('admin.password.update') }}" class="kt-card-content flex flex-col gap-5 p-10" id="reset_password_change_password_form" method="POST" novalidate>
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                
                <div class="text-center mb-2.5">
                    <div class="mb-4">
                        @include('partials.nexa-brand-logo', ['class' => 'h-10 w-auto mx-auto object-contain'])
                        <div class="mt-2 text-xs font-medium uppercase tracking-wider text-muted-foreground">
                            Administratie paneel
                        </div>
                    </div>
                    <h3 class="text-lg font-medium text-mono">
                        Wachtwoord Resetten
                    </h3>
                    <span class="text-sm text-secondary-foreground">
                        Voer je nieuwe wachtwoord in
                    </span>
                </div>

                @error('email')
                    <div class="auth-status-danger" role="alert">
                        <i class="ki-filled ki-information-5 text-xl" aria-hidden="true"></i>
                        <div class="text-sm font-medium">{{ $message }}</div>
                    </div>
                @enderror

                <div class="flex flex-col gap-1">
                    <label class="kt-form-label text-mono" for="reset_password">
                        Nieuw Wachtwoord
                    </label>
                    <label class="kt-input @error('password') border-destructive @enderror" data-kt-toggle-password="true" data-kt-toggle-password-permanent="true" data-field-control="reset_password">
                        <input id="reset_password"
                               name="password"
                               placeholder="Nieuw wachtwoord"
                               type="password"
                               autocomplete="new-password"/>
                        <div class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon bg-transparent! -me-1.5" data-kt-toggle-password-trigger="true">
                            <span class="kt-toggle-password-active:hidden">
                                <i class="ki-filled ki-eye text-muted-foreground"></i>
                            </span>
                            <span class="hidden kt-toggle-password-active:block">
                                <i class="ki-filled ki-eye-slash text-muted-foreground"></i>
                            </span>
                        </div>
                    </label>
                    <div class="text-xs text-destructive mt-1 @error('password') @else hidden @enderror" data-field-error="reset_password">
                        @error('password'){{ $message }}@enderror
                    </div>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="kt-form-label font-normal text-mono" for="reset_password_confirmation">
                        Bevestig Nieuw Wachtwoord
                    </label>
                    <label class="kt-input" data-kt-toggle-password="true" data-kt-toggle-password-permanent="true" data-field-control="reset_password_confirmation">
                        <input id="reset_password_confirmation"
                               name="password_confirmation"
                               placeholder="Herhaal wachtwoord"
                               type="password"
                               autocomplete="new-password"/>
                        <div class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon bg-transparent! -me-1.5" data-kt-toggle-password-trigger="true">
                            <span class="kt-toggle-password-active:hidden">
                                <i class="ki-filled ki-eye text-muted-foreground"></i>
                            </span>
                            <span class="hidden kt-toggle-password-active:block">
                                <i class="ki-filled ki-eye-slash text-muted-foreground"></i>
                            </span>
                        </div>
                    </label>
                    <div class="text-xs text-destructive mt-1 hidden" data-field-error="reset_password_confirmation"></div>
                </div>

                <input type="hidden" name="email" value="{{ old('email', $email) }}">

                <button type="submit" class="kt-btn flex justify-center grow" style="background-color: #f97316; color: white !important; border-color: #f97316;">
                    Wachtwoord Resetten
                </button>
            </form>
        </div>
    </div>
    <!-- End of Page -->
    
    <!-- Scripts -->
    <script src="{{ asset('assets/vendors/ktui/ktui.min.js') }}" defer></script>
    <script>
        (function () {
            const form = document.getElementById('reset_password_change_password_form');
            const passwordInput = document.getElementById('reset_password');
            const confirmInput = document.getElementById('reset_password_confirmation');
            if (!form || !passwordInput || !confirmInput) return;

            function controlFor(id) {
                return document.querySelector('[data-field-control="' + id + '"]') || document.getElementById(id);
            }

            function clearError(id) {
                const input = document.getElementById(id);
                const control = controlFor(id);
                const errorEl = document.querySelector('[data-field-error="' + id + '"]');
                control?.classList.remove('border-destructive');
                input?.removeAttribute('aria-invalid');
                if (errorEl) {
                    errorEl.textContent = '';
                    errorEl.classList.add('hidden');
                }
            }

            function setError(id, message) {
                const input = document.getElementById(id);
                const control = controlFor(id);
                const errorEl = document.querySelector('[data-field-error="' + id + '"]');
                control?.classList.add('border-destructive');
                input?.setAttribute('aria-invalid', 'true');
                if (errorEl) {
                    errorEl.textContent = message;
                    errorEl.classList.remove('hidden');
                }
            }

            passwordInput.addEventListener('input', function () { clearError('reset_password'); });
            confirmInput.addEventListener('input', function () { clearError('reset_password_confirmation'); });

            form.addEventListener('submit', function (event) {
                clearError('reset_password');
                clearError('reset_password_confirmation');
                const password = passwordInput.value || '';
                const confirmation = confirmInput.value || '';
                let firstInvalid = null;

                if (!password) {
                    setError('reset_password', 'Vul een nieuw wachtwoord in.');
                    firstInvalid = firstInvalid || 'reset_password';
                } else if (password.length < 8) {
                    setError('reset_password', 'Wachtwoord moet minimaal 8 karakters lang zijn.');
                    firstInvalid = firstInvalid || 'reset_password';
                }
                if (!confirmation) {
                    setError('reset_password_confirmation', 'Bevestig je wachtwoord.');
                    firstInvalid = firstInvalid || 'reset_password_confirmation';
                } else if (password && password !== confirmation) {
                    setError('reset_password_confirmation', 'De wachtwoorden komen niet overeen.');
                    firstInvalid = firstInvalid || 'reset_password_confirmation';
                }

                if (firstInvalid) {
                    event.preventDefault();
                    document.getElementById(firstInvalid)?.focus();
                }
            });
        })();
    </script>
    <!-- End of Scripts -->
</body>
</html>
