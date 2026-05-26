<!--
Product: Metronic is a toolkit of UI components built with Tailwind CSS for developing scalable web applications quickly and efficiently
Version: v9.3.5
Author: Keenthemes
-->
<!DOCTYPE html>
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" dir="ltr" lang="nl">
<head>
    <base href="{{ url('/') }}">
    <title>E-mailadres geverifieerd - NEXA Skillmatching</title>
    <meta charset="utf-8"/>
    <meta content="follow, index" name="robots"/>
    <meta content="width=device-width, initial-scale=1, shrink-to-fit=no" name="viewport"/>
    <meta content="E-mailadres verificatie pagina voor NEXA Skillmatching Platform" name="description"/>
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
    </style>
    <div class="flex items-center justify-center grow bg-center bg-no-repeat page-bg">
        <div class="kt-card max-w-[440px] w-full">
            <div class="kt-card-content p-10">
                <div class="flex justify-center py-10">
                    <img alt="Success" class="dark:hidden max-h-[130px]" src="{{ asset('assets/media/illustrations/30.svg') }}"/>
                    <img alt="Success" class="light:hidden max-h-[130px]" src="{{ asset('assets/media/illustrations/30-dark.svg') }}"/>
                </div>
                <h3 class="text-lg font-medium text-mono text-center mb-3">
                    @if($wasAlreadyVerified)
                        E-mailadres al geverifieerd
                    @else
                        E-mailadres succesvol geverifieerd!
                    @endif
                </h3>
                <div class="text-sm text-center text-secondary-foreground mb-7.5">
                    @if($wasAlreadyVerified)
                        Je e-mailadres was al geverifieerd. Je kunt direct inloggen.
                    @else
                        Bedankt {{ $user->first_name }}! Je e-mailadres 
                        <a class="text-sm text-mono font-medium hover:text-primary" href="mailto:{{ $user->email }}">
                            {{ $user->email }}
                        </a>
                        is nu geverifieerd. Je kunt nu inloggen op je account.
                    @endif
                </div>
                <div class="flex justify-center mb-5">
                    <a class="kt-btn kt-btn-primary flex justify-center" href="{{ route('admin.login') }}">
                        Ga naar inlogpagina
                    </a>
                </div>
                <div class="flex items-center justify-center gap-1 text-2sm">
                    <span class="text-secondary-foreground">
                        Terug naar
                    </span>
                    <a class="text-2sm font-medium kt-link" href="{{ route('admin.dashboard') }}">
                        Dashboard
                    </a>
                </div>
            </div>
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

