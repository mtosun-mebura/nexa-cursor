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

    <style>
        /* Maak menu heading borders grijs - voeg border-top toe aan menu items met headings */
        .kt-menu-item.pt-2\.25 {
            border-top: 1px solid var(--border) !important;
        }
        .kt-menu-item.pt-2\.25:first-child {
            border-top: none !important;
        }
        /* Maak dropdown separator borders grijs */
        .kt-dropdown-menu-separator {
            background-color: var(--border) !important;
            border-color: var(--border) !important;
        }

        /* Globale checkbox border-width: 1px voor consistentie */
        .kt-checkbox {
            border-width: 1px !important;
        }

        /* Consistente badge styling voor het hele systeem */
        .kt-badge {
            display: inline-flex !important;
            align-items: center !important;
            padding: 0.25rem 0.625rem !important;
            border-radius: 9999px !important;
            font-size: 0.75rem !important;
            font-weight: 500 !important;
            border: 1px solid transparent !important;
        }

        .kt-badge-sm {
            padding: 0.125rem 0.5rem !important;
            font-size: 0.75rem !important;
        }

        /* Success badge - groen */
        .kt-badge-success {
            background-color: rgba(16, 185, 129, 0.1) !important;
            color: rgb(2, 101, 66) !important;
            border-color: rgba(16, 185, 129, 0.3) !important;
        }

        .dark .kt-badge-success {
            background-color: rgba(16, 185, 129, 0.2) !important;
            color: rgb(16, 185, 129) !important;
            border-color: rgba(16, 185, 129, 0.4) !important;
        }

        /* Warning badge - geel/oranje */
        .kt-badge-warning {
            background-color: rgba(251, 146, 60, 0.1) !important;
            color: rgb(154, 52, 18) !important;
            border-color: rgba(251, 146, 60, 0.3) !important;
        }

        .dark .kt-badge-warning {
            background-color: rgba(251, 146, 60, 0.2) !important;
            color: rgb(251, 146, 60) !important;
            border-color: rgba(251, 146, 60, 0.4) !important;
        }

        /* Danger badge - rood */
        .kt-badge-danger {
            background-color: rgba(239, 68, 68, 0.1) !important;
            color: rgb(153, 27, 27) !important;
            border-color: rgba(239, 68, 68, 0.3) !important;
        }

        .dark .kt-badge-danger {
            background-color: rgba(239, 68, 68, 0.2) !important;
            color: rgb(239, 68, 68) !important;
            border-color: rgba(239, 68, 68, 0.4) !important;
        }

        /* Info badge - blauw */
        .kt-badge-info {
            background-color: rgba(59, 130, 246, 0.1) !important;
            color: rgb(30, 64, 175) !important;
            border-color: rgba(59, 130, 246, 0.3) !important;
        }

        .dark .kt-badge-info {
            background-color: rgba(59, 130, 246, 0.2) !important;
            color: rgb(96, 165, 250) !important;
            border-color: rgba(59, 130, 246, 0.4) !important;
        }

        /* Secondary badge - grijs */
        .kt-badge-secondary {
            background-color: rgba(107, 114, 128, 0.1) !important;
            color: rgb(55, 65, 81) !important;
            border-color: rgba(107, 114, 128, 0.3) !important;
        }

        .dark .kt-badge-secondary {
            background-color: rgba(107, 114, 128, 0.2) !important;
            color: rgb(156, 163, 175) !important;
            border-color: rgba(107, 114, 128, 0.4) !important;
        }
        /* Zorg dat alle separators in de sidebar grijs zijn */
        .kt-sidebar .kt-menu-separator {
            border-color: var(--border) !important;
            background-color: var(--border) !important;
        }
        /* Zorg dat alle borders in de sidebar grijs zijn */
        .kt-sidebar .kt-menu-item {
            border-color: var(--border) !important;
        }
        /* Zorg dat border-e (east border) ook grijs is */
        .kt-sidebar.border-e,
        [class*="border-e"] {
            border-color: var(--border) !important;
        }
        /* Zorg dat .border-border ook de juiste border-color heeft */
        .border-border {
            border-color: var(--border) !important;
        }
        /* Success Banner Bar */
        .success-banner-bar {
            position: relative;
            width: 100%;
            z-index: 10;
            background-color: #10b981 !important;
            background: #10b981 !important;
            color: white !important;
            padding: 12px 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: opacity 0.3s ease-out, transform 0.3s ease-out;
            transform: translateY(0);
            margin-bottom: 1.25rem;
        }
        .success-banner-bar * {
            background-color: transparent !important;
            background: transparent !important;
        }
        .success-banner-bar .kt-container-fixed,
        .success-banner-bar .flex,
        .success-banner-bar .kt-container-fixed *,
        .success-banner-bar .flex *,
        .success-banner-bar .kt-container-fixed div,
        .success-banner-bar .flex div {
            background-color: transparent !important;
            background: transparent !important;
        }
        .success-banner-bar i,
        .success-banner-bar span {
            color: white !important;
        }
        .success-banner-bar.fade-out {
            opacity: 0;
            transform: translateY(-20px);
        }
        .dark .success-banner-bar {
            background-color: #059669 !important;
            background: #059669 !important;
        }
        /* Rejection Banner (Red) */
        .success-banner-bar.rejection {
            background-color: #dc2626;
        }
        .dark .success-banner-bar.rejection {
            background-color: #b91c1c;
        }

        /* Notification drawer border colors */
        #notifications_drawer .border-b,
        #notifications_drawer .border-t,
        #notifications_drawer .border-border {
            border-color: var(--border) !important;
        }
        
        /* Notification drawer visibility */
        #notifications_drawer.hidden {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
        }
        #notifications_drawer:not(.hidden),
        #notifications_drawer[data-notifications-active="true"]:not(.hidden) {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            z-index: 99999 !important;
        }
        
        /* Align checkbox and avatar container to top - target the flex container with checkbox and avatar */
        #notifications_drawer .notification-item > div > div.flex.items-start {
            align-self: flex-start !important;
        }
        /* Center checkbox vertically within its container */
        #notifications_drawer .notification-item .notification-checkbox {
            align-self: center !important;
        }
        
        /* UI rule: labels next to textarea should be top-aligned */
        .kt-table.kt-table-border-dashed.align-middle td.align-top {
            vertical-align: top !important;
        }
        .kt-table.kt-table-border-dashed.align-middle td.align-top:first-child {
            padding-top: 14px;
        }
        /* UI rule: if right cell is taller (help text/textarea), top-align left label cell */
        .kt-table.kt-table-border-dashed.align-middle tr:has(td:nth-child(2) textarea) td:first-child,
        .kt-table.kt-table-border-dashed.align-middle tr:has(td:nth-child(2) .text-xs) td:first-child {
            vertical-align: top !important;
            padding-top: 14px;
        }

        /* Zorg dat alle dropdown opties volledig zichtbaar zijn in filter dropdowns */
        .kt-select-dropdown {
            min-width: max-content !important;
            width: auto !important;
            max-width: 500px !important;
        }

        /* Zorg dat de dropdown breder kan zijn dan de select button */
        .kt-select-wrapper .kt-select-dropdown {
            min-width: max-content !important;
            width: auto !important;
        }

        .kt-select-options {
            min-width: max-content !important;
            width: 100% !important;
        }

        /* Zorg dat de optie tekst volledig zichtbaar is (geen ellipsis) */
        .kt-select-option-text {
            overflow: visible !important;
            white-space: normal !important;
            text-overflow: clip !important;
            word-wrap: break-word !important;
            word-break: break-word !important;
        }

        /* Zorg dat de optie zelf ook volledig zichtbaar is */
        .kt-select-option {
            white-space: normal !important;
            word-wrap: break-word !important;
            word-break: break-word !important;
            min-width: max-content !important;
        }

        /* Zorg dat de dropdown container de volledige breedte kan gebruiken */
        .kt-select-wrapper {
            position: relative !important;
        }

        .kt-select-wrapper .kt-select-dropdown[data-kt-select-dropdown] {
            min-width: max-content !important;
            width: auto !important;
        }

        /* Fix for passive event listener warnings in responsive mode */
        /* Allow touch-action to be controlled for elements that need preventDefault */
        .kt-drawer,
        .kt-drawer-backdrop,
        .kt-sidebar,
        [data-kt-drawer] {
            touch-action: pan-y;
        }

        /* Prevent passive event listener warnings for draggable elements */
        [draggable="true"],
        .photo-container,
        #photo-container {
            touch-action: none;
        }

        /* Ensure background stays consistent - white in light mode, black in dark mode */
        body {
            background-color: #ffffff !important; /* White in light mode */
        }
        .kt-wrapper,
        main#content,
        .kt-container-fixed,
        #header,
        .kt-header,
        .kt-footer,
        footer {
            background-color: #ffffff !important; /* White in light mode */
        }
        /* Dark mode - black background */
        .dark body,
        .dark .kt-wrapper,
        .dark main#content,
        .dark .kt-container-fixed,
        .dark #header,
        .dark .kt-header,
        .dark .kt-footer,
        .dark footer {
            background-color: #000000 !important; /* Black in dark mode */
        }
        
        /* Make kt-container-fixed full width */
        .kt-container-fixed {
            max-width: 100% !important;
            width: 100% !important;
        }
    </style>
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

    <!-- Ensure Cmd+A / Ctrl+A works in input fields -->
    <script>
    (function() {
        // Force Cmd+A (Mac) / Ctrl+A (Win/Linux) to select all text,
        // even if other scripts block the default behavior.
        document.addEventListener('keydown', function(e) {
            const key = (e.key || '').toLowerCase();
            const isSelectAll = (e.metaKey || e.ctrlKey) && key === 'a';
            if (!isSelectAll) return;

            const t = e.target;
            const isInput = t instanceof HTMLInputElement;
            const isTextarea = t instanceof HTMLTextAreaElement;
            if (!isInput && !isTextarea) return;

            if (isInput) {
                const type = (t.getAttribute('type') || 'text').toLowerCase();
                // Skip non-text-like inputs
                if (['button','submit','checkbox','radio','file','color','range','date','time','datetime-local','month','week'].includes(type)) {
                    return;
                }
            }

            try {
                t.focus();
                if (typeof t.select === 'function') {
                    t.select();
                }
                if (typeof t.setSelectionRange === 'function') {
                    t.setSelectionRange(0, (t.value || '').length);
                }
            } catch (_) {
                // ignore
            }

            e.preventDefault();
            e.stopImmediatePropagation();
        }, true);
    })();
    </script>

    <!-- Ctrl+S / Cmd+S: opslaan op pagina's met een Opslaan-knop -->
    <script>
    (function() {
        document.addEventListener('keydown', function(e) {
            var isSave = (e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S');
            if (!isSave) return;
            e.preventDefault();

            var content = document.getElementById('content');
            if (!content) return;
            var forms = content.querySelectorAll('form');
            for (var i = 0; i < forms.length; i++) {
                var form = forms[i];
                if (form.closest('[role="dialog"]') || form.closest('.modal')) continue;
                var btn = form.querySelector('button[type="submit"].kt-btn-primary');
                if (!btn) btn = form.querySelector('button[type="submit"][class*="btn-primary"]');
                if (!btn) continue;
                var text = (btn.textContent || btn.innerText || '').trim();
                var isSaveBtn = /opslaan|opsla|save|wijzigingen opslaan|template opslaan|gebruiker opslaan|vestiging opslaan|match opslaan|toevoegen/i.test(text) || form.id === 'website-page-form' || form.id === 'general-settings-form' || form.id === 'coming-soon-form';
                if (isSaveBtn) {
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit(btn);
                    } else {
                        form.submit();
                    }
                    return;
                }
            }
        });
    })();
    </script>

    <!-- Success Banner Auto-Dismiss -->
    <script>
    (function() {
        const successBanner = document.getElementById('success-banner');
        if (successBanner) {
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                successBanner.classList.add('fade-out');
                setTimeout(function() {
                    successBanner.remove();
                }, 300); // Match CSS transition duration
            }, 5000); // 5 seconds
        }
    })();
    </script>

    <!-- Session Expiry Handler -->
    <script>
    (function() {
        // Alleen op beveiligde adminpagina's (niet op login of meld)
        var path = window.location.pathname || '';
        if (path.includes('/admin/login') || path.includes('/admin/meld/sessie-verlopen')) {
            return;
        }
        if (!path.startsWith('/admin')) {
            return;
        }

        // Sessiecheck bij paginaload: als geen geldige sessie, redirect naar meld met intended
        var sessionCheckUrl = '{{ url("/admin/api/session-check") }}';
        fetch(sessionCheckUrl, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function(response) {
            if (response.status === 401 || response.status === 419) {
                var meldUrl = '{{ route("admin.meld.sessie-verlopen") }}?intended=' + encodeURIComponent(window.location.href);
                window.location.href = meldUrl;
            }
        }).catch(function() { /* negeer netwerkfouten, AJAX-handler vangt later 401 */ });

        // Helper function to check if URL is login-related
        function isLoginUrl(url) {
            if (!url) return false;
            const urlStr = typeof url === 'string' ? url : (url.url || '');
            return urlStr.includes('/admin/login') || urlStr.includes('admin.login.post');
        }

        // Wait for jQuery to be available
        function initAjaxErrorHandler() {
            const $ = window.jQuery || window.$;
            if (!$) {
                setTimeout(initAjaxErrorHandler, 100);
                return;
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
                // Redirect naar meld-pagina met huidige URL zodat na inloggen teruggegaan wordt
                if (!window.location.pathname.includes('/admin/login')) {
                    var meldUrl = '{{ route("admin.meld.sessie-verlopen") }}?intended=' + encodeURIComponent(window.location.href);
                    window.location.href = meldUrl;
                }
                return false;
            } else if (xhr.status === 403) {
                // Check if it's a permission error or session issue
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.redirect && response.redirect.includes('login')) {
                        if (!window.location.pathname.includes('/admin/login')) {
                            window.location.href = '{{ route("admin.login") }}?intended=' + encodeURIComponent(window.location.href);
                        }
                        return false;
                    }
                } catch (e) {
                    // If response is not JSON, check if it's a redirect response
                    if (xhr.responseText && xhr.responseText.includes('admin/login')) {
                        if (!window.location.pathname.includes('/admin/login')) {
                            window.location.href = '{{ route("admin.login") }}?intended=' + encodeURIComponent(window.location.href);
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
                        if (!window.location.pathname.includes('/admin/login')) {
                            var meldUrl = '{{ route("admin.meld.sessie-verlopen") }}?intended=' + encodeURIComponent(window.location.href);
                            window.location.href = meldUrl;
                        }
                        return Promise.reject(new Error('Session expired'));
                    } else if (response.status === 403) {
                        // Check if response indicates redirect to login
                        return response.json().then(data => {
                            if (data.redirect && data.redirect.includes('login')) {
                                if (!window.location.pathname.includes('/admin/login')) {
                                    window.location.href = '{{ route("admin.login") }}?intended=' + encodeURIComponent(window.location.href);
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

        }

        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initAjaxErrorHandler);
        } else {
            initAjaxErrorHandler();
        }
    })();
    </script>

    <!-- Ensure chat functions are available -->
    <script>
        // Fallback: ensure chat functions are available
        if (typeof window.openChatWithCandidate === 'undefined') {
            window.openChatWithCandidate = function() {
                console.warn('Chat functionality not loaded yet. Please refresh the page.');
            };
        }
        if (typeof window.loadActiveChats === 'undefined') {
            window.loadActiveChats = function() {
                console.warn('Chat functionality not loaded yet. Please refresh the page.');
                return Promise.resolve([]);
            };
        }
    </script>
</body>
</html>
