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
    
    <!-- Session Expiry Handler -->
    <script>
    (function() {
        // Only run on admin pages (not on login page)
        if (window.location.pathname.includes('/admin/login')) {
            return;
        }
        
        // Helper function to check if URL is login-related
        function isLoginUrl(url) {
            if (!url) return false;
            const urlStr = typeof url === 'string' ? url : (url.url || '');
            return urlStr.includes('/admin/login') || urlStr.includes('admin.login.post');
        }
        
        // Global AJAX error handler for expired sessions
        $(document).ajaxError(function(event, xhr, settings, thrownError) {
            // Skip handling for login-related requests
            if (isLoginUrl(settings.url)) {
                return;
            }
            
            // Skip if already on login page
            if (window.location.pathname.includes('/admin/login')) {
                return;
            }
            
            // Check for 401 (Unauthorized), 403 (Forbidden), or 419 (CSRF token mismatch) responses
            if (xhr.status === 401 || xhr.status === 419) {
                // Only redirect if not already on login page
                if (!window.location.pathname.includes('/admin/login')) {
                    window.location.href = '{{ route("admin.login") }}';
                }
                return false;
            } else if (xhr.status === 403) {
                // Check if it's a permission error or session issue
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.redirect && response.redirect.includes('login')) {
                        if (!window.location.pathname.includes('/admin/login')) {
                            window.location.href = '{{ route("admin.login") }}';
                        }
                        return false;
                    }
                } catch (e) {
                    // If response is not JSON, check if it's a redirect response
                    if (xhr.responseText && xhr.responseText.includes('admin/login')) {
                        if (!window.location.pathname.includes('/admin/login')) {
                            window.location.href = '{{ route("admin.login") }}';
                        }
                        return false;
                    }
                }
            }
        });
        
        // Also handle fetch API errors
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            // Skip if already on login page
            if (window.location.pathname.includes('/admin/login')) {
                return originalFetch.apply(this, args);
            }
            
            const url = args[0];
            
            // Skip handling for login-related requests
            if (isLoginUrl(url)) {
                return originalFetch.apply(this, args);
            }
            
            return originalFetch.apply(this, args)
                .then(response => {
                    // Check for 401, 403, or 419 status
                    if (response.status === 401 || response.status === 419) {
                        // Only redirect if not already on login page
                        if (!window.location.pathname.includes('/admin/login')) {
                            window.location.href = '{{ route("admin.login") }}';
                        }
                        return Promise.reject(new Error('Session expired'));
                    } else if (response.status === 403) {
                        // Check if response indicates redirect to login
                        return response.json().then(data => {
                            if (data.redirect && data.redirect.includes('login')) {
                                if (!window.location.pathname.includes('/admin/login')) {
                                    window.location.href = '{{ route("admin.login") }}';
                                }
                                return Promise.reject(new Error('Session expired'));
                            }
                            return response;
                        }).catch(() => {
                            // If JSON parsing fails, return original response
                            return response;
                        });
                    }
                    return response;
                })
                .catch(error => {
                    // Handle network errors or other fetch errors
                    if (error.name === 'TypeError' && error.message.includes('Failed to fetch')) {
                        // Network error, but we can't determine if it's a session issue
                        // Let it pass through
                    }
                    throw error;
                });
        };
    })();
    </script>
</body>
</html>
