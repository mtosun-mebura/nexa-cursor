<!DOCTYPE html>
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" dir="ltr" lang="nl">
<head>
    @include('layouts.partials.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Nexa Skillmatching</title>
    
    <!-- Theme Mode -->
    <script data-navigate-once>
    (function() {
        if (!window.defaultThemeMode) {
            window.defaultThemeMode = 'light'; // light|dark|system
        }
        let themeMode;
        if (document.documentElement) {
            if (localStorage.getItem('kt-theme')) {
                themeMode = localStorage.getItem('kt-theme');
            } else if (
            document.documentElement.hasAttribute('data-kt-theme-mode')
            ) {
                themeMode =
                document.documentElement.getAttribute('data-kt-theme-mode');
            } else {
                themeMode = window.defaultThemeMode;
            }
            if (themeMode === 'system') {
                themeMode = window.matchMedia('(prefers-color-scheme: dark)').matches ?
                'dark' :
                'light';
            }
            document.documentElement.classList.add(themeMode);
        }
    })();
    </script>
    <!-- End of Theme Mode -->
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="demo1 kt-sidebar-fixed kt-header-fixed flex h-full bg-background text-base text-foreground antialiased">
    <!-- Page -->
    <!-- Main -->
    <div class="flex grow">
        @include('admin.layouts.partials.sidebar')

        <!-- Wrapper -->
        <div class="kt-wrapper flex grow flex-col">
            @include('admin.layouts.partials.header')

            <!-- Content -->
            <main class="grow pt-5" id="content" role="content">
                <!-- Container -->
                <div class="kt-container-fixed">
                    @if(session('success'))
                        <div class="kt-alert kt-alert-success mb-5">
                            <i class="ki-filled ki-check-circle"></i>
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="kt-alert kt-alert-danger mb-5">
                            <i class="ki-filled ki-information"></i>
                            {{ session('error') }}
                        </div>
                    @endif

                    @yield('content')
                </div>
                <!-- End of Container -->
            </main>
            <!-- End of Content -->

            @include('layouts.demo1.footer')
        </div>
        <!-- End of Wrapper -->
    </div>
    <!-- End of Main -->
    <!-- End of Page -->

    @include('layouts.partials.scripts')
</body>
</html>
