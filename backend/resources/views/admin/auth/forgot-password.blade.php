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
        .page-bg {
            background-image: url('{{ asset('assets/media/images/2600x1200/bg-10.png') }}');
        }
        .dark .page-bg {
            background-image: url('{{ asset('assets/media/images/2600x1200/bg-10-dark.png') }}');
        }
        
        /* Form input fields 100% width */
        #reset_password_enter_email_form .kt-input {
            width: 100% !important;
        }
        
        #reset_password_enter_email_form .kt-input input {
            width: 100% !important;
        }
        
        /* Autofill achtergrond licht grijs */
        #reset_password_enter_email_form input:-webkit-autofill,
        #reset_password_enter_email_form input:-webkit-autofill:hover,
        #reset_password_enter_email_form input:-webkit-autofill:focus,
        #reset_password_enter_email_form input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 30px #f3f4f6 inset !important;
            -webkit-text-fill-color: #1f2937 !important;
            background-color: #f3f4f6 !important;
        }
        
        .dark #reset_password_enter_email_form input:-webkit-autofill,
        .dark #reset_password_enter_email_form input:-webkit-autofill:hover,
        .dark #reset_password_enter_email_form input:-webkit-autofill:focus,
        .dark #reset_password_enter_email_form input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 30px #374151 inset !important;
            -webkit-text-fill-color: #f9fafb !important;
            background-color: #374151 !important;
        }
    </style>
    
    <div class="flex items-center justify-center grow bg-center bg-no-repeat page-bg">
        <div class="kt-card max-w-[370px] w-full">
            <form action="{{ route('admin.password.email') }}" class="kt-card-content flex flex-col gap-5 p-10" id="reset_password_enter_email_form" method="POST">
                @csrf
                
                <div class="text-center">
                    <h3 class="text-lg font-medium text-mono">
                        Je E-mailadres
                    </h3>
                    <span class="text-sm text-secondary-foreground">
                        Voer je e-mailadres in om je wachtwoord te resetten
                    </span>
                </div>

                @if (session('status'))
                    <div class="kt-alert kt-alert-success flex items-center gap-2.5 p-4 rounded-lg border border-green-500 bg-green-50 dark:bg-green-900/20">
                        <i class="ki-filled ki-check-circle text-xl text-green-600 dark:text-green-400"></i>
                        <div class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('status') }}</div>
                    </div>
                @endif

                @error('email')
                    <div class="kt-alert kt-alert-danger flex items-center gap-2.5 p-4 rounded-lg border border-red-500 bg-red-50 dark:bg-red-900/20">
                        <i class="ki-filled ki-information-5 text-xl text-red-600 dark:text-red-400"></i>
                        <div class="text-sm font-medium text-red-800 dark:text-red-200">{{ $message }}</div>
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
    <script src="{{ asset('assets/js/core.bundle.js') }}"></script>
    <script src="{{ asset('assets/vendors/ktui/ktui.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/apexcharts/apexcharts.min.js') }}"></script>
    <!-- End of Scripts -->
</body>
</html>
