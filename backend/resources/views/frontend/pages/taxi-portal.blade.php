<!DOCTYPE html>
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" dir="ltr" lang="nl">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ ($branding['dashboard_link_label'] ?? 'Mijn Taxi').' - '.($branding['site_name'] ?? 'Nexa') }}</title>

    <link rel="stylesheet" href="{{ asset('metronic-v9.4.13/demo1/assets/vendors/keenicons/styles.bundle.css') }}">
    <link rel="stylesheet" href="{{ asset('metronic-v9.4.13/demo1/assets/css/styles.css') }}">

    <!-- Theme Mode (Metronic) -->
    <script>
        const defaultThemeMode = 'light'; // light|dark|system
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
</head>
<body class="antialiased flex h-full text-base text-foreground bg-background demo1 kt-sidebar-fixed kt-header-fixed">
    <div id="taxi-portal-app" class="w-full"></div>

    <script src="{{ asset('metronic-v9.4.13/demo1/assets/vendors/ktui/ktui.min.js') }}"></script>
    <script src="{{ asset('metronic-v9.4.13/demo1/assets/js/core.bundle.js') }}"></script>
    @vite('resources/js/taxi-portal-app.ts')
</body>
</html>
