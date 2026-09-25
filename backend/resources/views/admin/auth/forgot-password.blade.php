<!--
Product: Metronic is a toolkit of UI components built with Tailwind CSS for developing scalable web applications quickly and efficiently
Version: v9.3.5
Author: Keenthemes
-->
<!DOCTYPE html>
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" dir="ltr" lang="nl">
<head>
    <base href="{{ url('/') }}">
    <title>Wachtwoord Vergeten - NEXA Skillmatching</title>
    <meta charset="utf-8"/>
    <meta content="follow, index" name="robots"/>
    <meta content="width=device-width, initial-scale=1, shrink-to-fit=no" name="viewport"/>
    <meta content="Wachtwoord vergeten pagina voor NEXA Skillmatching Platform" name="description"/>
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
        #reset_password_enter_email_form .kt-input {
            width: 100% !important;
        }
        
        #reset_password_enter_email_form .kt-input input {
            width: 100% !important;
        }
        
        /* Autofill: zelfde achtergrond als kt-input (geen browser-grijs) */
        #reset_password_enter_email_form input:-webkit-autofill,
        #reset_password_enter_email_form input:-webkit-autofill:hover,
        #reset_password_enter_email_form input:-webkit-autofill:focus,
        #reset_password_enter_email_form input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 1000px var(--background) inset !important;
            box-shadow: 0 0 0 1000px var(--background) inset !important;
            -webkit-text-fill-color: var(--foreground) !important;
            caret-color: var(--foreground);
            transition: background-color 5000s ease-in-out 0s;
        }

        #reset_password_enter_email_form input:autofill {
            box-shadow: 0 0 0 1000px var(--background) inset !important;
            -webkit-text-fill-color: var(--foreground) !important;
            caret-color: var(--foreground);
        }

        .auth-status-success {
            display: flex;
            align-items: flex-start;
            gap: 0.625rem;
            padding: 1rem;
            border-radius: 0.5rem;
            border: 1px solid #86efac;
            background: #ecfdf5;
            color: #14532d;
        }
        html.dark .auth-status-success {
            border-color: rgb(74 222 128 / 0.35);
            background: rgb(20 83 45 / 0.45);
            color: #d1fae5;
        }
        .auth-status-success i {
            color: #16a34a;
            flex-shrink: 0;
        }
        html.dark .auth-status-success i {
            color: #4ade80;
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
            <form action="{{ route('admin.password.email') }}" class="kt-card-content flex flex-col gap-5 p-10" id="reset_password_enter_email_form" method="POST">
                @csrf
                
                <div class="text-center mb-2.5">
                    <div class="mb-4">
                        @include('partials.nexa-brand-logo', ['class' => 'h-10 w-auto mx-auto object-contain'])
                        <div class="mt-2 text-xs font-medium uppercase tracking-wider text-muted-foreground">
                            Administratie paneel
                        </div>
                    </div>
                    <h3 class="text-lg font-medium text-mono">
                        Je E-mailadres
                    </h3>
                    <span class="text-sm text-secondary-foreground">
                        Voer je e-mailadres in om je wachtwoord te resetten
                    </span>
                </div>

                @if (session('status'))
                    <div class="auth-status-success" role="status">
                        <i class="ki-filled ki-check-circle text-xl" aria-hidden="true"></i>
                        <div class="text-sm font-medium">{{ session('status') }}</div>
                    </div>
                @endif

                @error('email')
                    <div class="auth-status-danger" role="alert">
                        <i class="ki-filled ki-information-5 text-xl" aria-hidden="true"></i>
                        <div class="text-sm font-medium">{{ $message }}</div>
                    </div>
                @enderror

                @if (!session('status'))
                    <div class="flex flex-col gap-1">
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

                    <button type="submit" class="kt-btn kt-btn-primary flex justify-center grow">
                        Doorgaan
                        <i class="ki-filled ki-black-right"></i>
                    </button>
                @endif

                <div class="text-center">
                    <a class="text-sm link text-primary" href="{{ route('admin.login') }}">
                        <i class="ki-filled ki-arrow-left me-1"></i>
                        Terug naar inloggen
                    </a>
                </div>
            </form>
        </div>
    </div>
    <!-- End of Page -->
    
    <!-- Scripts -->
    <script src="{{ asset('assets/vendors/ktui/ktui.min.js') }}" defer></script>
    <!-- End of Scripts -->
</body>
</html>
