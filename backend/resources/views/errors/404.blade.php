<!DOCTYPE html>
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" dir="ltr" lang="nl">
<head>
    <base href="{{ url('/') }}">
    <title>404 - Pagina Niet Gevonden | NEXA Skillmatching</title>
    <meta charset="utf-8"/>
    <meta content="follow, index" name="robots"/>
    <meta content="width=device-width, initial-scale=1, shrink-to-fit=no" name="viewport"/>
    <meta content="404 Error page for NEXA Skillmatching Platform" name="description"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="{{ asset('assets/vendors/apexcharts/apexcharts.css') }}" rel="stylesheet"/>
    <link href="{{ asset('assets/vendors/keenicons/styles.bundle.css') }}" rel="stylesheet"/>
    <link href="{{ asset('assets/css/styles.css') }}" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased flex h-full text-base text-foreground bg-background demo1 kt-sidebar-fixed kt-header-fixed">
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
    <!-- Main -->
    <div class="flex grow">
        <!-- Wrapper -->
        <div class="kt-wrapper flex grow flex-col">
            <!-- Content -->
            <main class="grow pt-5" id="content" role="content">
                <div class="flex flex-col items-center justify-center h-[95%]">
                    <div class="mb-10">
                        <img alt="404 Error" class="dark:hidden max-h-[160px]" src="{{ asset('assets/media/illustrations/19.svg') }}"/>
                        <img alt="404 Error" class="light:hidden max-h-[160px]" src="{{ asset('assets/media/illustrations/19-dark.svg') }}"/>
                    </div>
                    <span class="kt-badge kt-badge-primary kt-badge-outline mb-3">
                        404 Fout
                    </span>
                    <h3 class="text-2xl font-semibold text-mono text-center mb-2">
                        Deze pagina is niet gevonden
                    </h3>
                    <div class="text-base text-center text-secondary-foreground mb-4">
                        De gevraagde pagina ontbreekt of bestaat niet meer.
                    </div>
                    @php
                        $title = 'Pagina niet gevonden';
                        $message = 'U wordt binnen 5 seconden automatisch doorgestuurd. U kunt ook direct op de onderstaande link klikken.';
                        $redirectUrl = auth()->check() ? route('admin.dashboard') : route('home');
                        $redirectLabel = auth()->check() ? 'Naar dashboard' : 'Naar home';
                    @endphp
                    @include('partials.redirect-message')
                    <div class="mt-4">
                        <a href="{{ url()->previous() }}" class="kt-btn kt-btn-outline">
                            <i class="ki-filled ki-arrow-left"></i>
                            Terug
                        </a>
                    </div>
                </div>
            </main>
            <!-- End of Content -->
        </div>
        <!-- End of Wrapper -->
    </div>
    <!-- End of Main -->
    <!-- End of Page -->
    
    <!-- Scripts -->
    <script src="{{ asset('assets/js/core.bundle.js') }}"></script>
    <script src="{{ asset('assets/vendors/ktui/ktui.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/apexcharts/apexcharts.min.js') }}"></script>
    <!-- End of Scripts -->
</body>
</html>
