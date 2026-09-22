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
            <form action="{{ route('admin.password.update') }}" class="kt-card-content flex flex-col gap-5 p-10" id="reset_password_change_password_form" method="POST">
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

                @error('password')
                    <div class="auth-status-danger" role="alert">
                        <i class="ki-filled ki-information-5 text-xl" aria-hidden="true"></i>
                        <div class="text-sm font-medium">{{ $message }}</div>
                    </div>
                @enderror

                <div class="flex flex-col gap-1">
                    <label class="kt-form-label text-mono">
                        Nieuw Wachtwoord
                    </label>
                    <label class="kt-input" data-kt-toggle-password="true">
                        <input name="password" 
                               placeholder="Nieuw wachtwoord" 
                               type="password" 
                               required/>
                        <div class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon bg-transparent! -me-1.5" data-kt-toggle-password-trigger="true">
                            <span class="kt-toggle-password-active:hidden">
                                <i class="ki-filled ki-eye text-muted-foreground"></i>
                            </span>
                            <span class="hidden kt-toggle-password-active:block">
                                <i class="ki-filled ki-eye-slash text-muted-foreground"></i>
                            </span>
                        </div>
                    </label>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="kt-form-label font-normal text-mono">
                        Bevestig Nieuw Wachtwoord
                    </label>
                    <label class="kt-input" data-kt-toggle-password="true">
                        <input name="password_confirmation" 
                               placeholder="Herhaal wachtwoord" 
                               type="password" 
                               required/>
                        <div class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon bg-transparent! -me-1.5" data-kt-toggle-password-trigger="true">
                            <span class="kt-toggle-password-active:hidden">
                                <i class="ki-filled ki-eye text-muted-foreground"></i>
                            </span>
                            <span class="hidden kt-toggle-password-active:block">
                                <i class="ki-filled ki-eye-slash text-muted-foreground"></i>
                            </span>
                        </div>
                    </label>
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
    <!-- End of Scripts -->
</body>
</html>
