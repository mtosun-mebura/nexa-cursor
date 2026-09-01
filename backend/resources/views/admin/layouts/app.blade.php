<!DOCTYPE html>
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" dir="ltr" lang="nl">
<head>
    @include('layouts.partials.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">

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
    <script>
    (function () {
        try {
            var y = parseInt(sessionStorage.getItem('admin-sidebar-scroll') || '', 10);
            if (!isNaN(y) && y > 0) {
                document.documentElement.setAttribute('data-sidebar-scroll-pending', '1');
            }
        } catch (err) {}
    })();
    </script>
    <!-- End of Theme Mode -->

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')

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

        /* Warning badge - oranje */
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

        /* Yellow badge */
        .kt-badge-yellow {
            background-color: rgba(234, 179, 8, 0.12) !important;
            color: rgb(161, 98, 7) !important;
            border-color: rgba(234, 179, 8, 0.35) !important;
        }

        .dark .kt-badge-yellow {
            background-color: rgba(234, 179, 8, 0.22) !important;
            color: rgb(250, 204, 21) !important;
            border-color: rgba(234, 179, 8, 0.45) !important;
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

        /* Select-dropdown: ondoorzichtige achtergrond (ook bij position:fixed) */
        .kt-select-dropdown,
        .kt-select-dropdown[data-kt-select-dropdown],
        [data-kt-select-dropdown] {
            background-color: var(--popover, #ffffff) !important;
            color: var(--popover-foreground, var(--foreground)) !important;
            border: 1px solid var(--border) !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.18) !important;
            -webkit-backdrop-filter: none !important;
            backdrop-filter: none !important;
        }

        .kt-select-options,
        [data-kt-select-options] {
            background-color: var(--popover, #ffffff) !important;
            color: var(--popover-foreground, var(--foreground)) !important;
        }

        html.dark .kt-select-dropdown,
        html.dark [data-kt-select-dropdown],
        .dark .kt-select-dropdown,
        .dark [data-kt-select-dropdown],
        html.dark .kt-select-options,
        html.dark [data-kt-select-options],
        .dark .kt-select-options,
        .dark [data-kt-select-options] {
            background-color: #111827 !important;
            color: #f3f4f6 !important;
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
        
        /* Admin: volledige breedte; theme max-width/padding wordt in admin-responsive.css geregeld */
        html, body.demo1 {
            width: 100% !important;
            max-width: 100% !important;
        }
        body.demo1 > .flex.grow,
        .demo1 .kt-wrapper,
        main#content {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0;
            box-sizing: border-box;
        }
        #content .kt-container-fixed {
            max-width: none !important;
            width: 100% !important;
            margin-inline: 0 !important;
        }
        /* Logo light/dark: toon juiste logo volgens thema (html of body kan .dark hebben) */
        .logo-light { display: block !important; }
        .logo-dark { display: none !important; }
        html.dark .logo-light, body.dark .logo-light, .dark .logo-light { display: none !important; }
        html.dark .logo-dark, body.dark .logo-dark, .dark .logo-dark { display: block !important; }

        /* Flash success: iets donkerder groen (leesbaarder) */
        #content .kt-alert.kt-alert-success {
            background-color: rgba(5, 120, 85, 0.14) !important;
            border: 1px solid rgb(4, 120, 87) !important;
            color: rgb(6, 78, 59) !important;
        }
        #content .kt-alert.kt-alert-success .ki-filled {
            color: rgb(4, 120, 87) !important;
        }
        .dark #content .kt-alert.kt-alert-success {
            background-color: rgba(16, 185, 129, 0.18) !important;
            border-color: rgb(52, 211, 153) !important;
            color: rgb(209, 250, 229) !important;
        }
        .dark #content .kt-alert.kt-alert-success .ki-filled {
            color: rgb(167, 243, 208) !important;
        }

        /* Flash danger: rode tekst, icoon naast de bovenste regel */
        #content .kt-alert.kt-alert-danger {
            display: flex !important;
            align-items: flex-start !important;
            gap: 0.625rem;
            background-color: rgba(220, 38, 38, 0.12) !important;
            border: 1px solid rgb(185, 28, 28) !important;
            color: rgb(185, 28, 28) !important;
        }
        #content .kt-alert.kt-alert-danger.hidden {
            display: none !important;
        }
        #content .kt-alert.kt-alert-danger > .ki-filled {
            position: relative;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            align-self: flex-start;
            width: 1.25rem;
            height: 1.25rem;
            font-size: 1.25rem;
            line-height: 1 !important;
            color: rgb(220, 38, 38) !important;
        }
        #content .kt-alert.kt-alert-danger > .ki-filled::before {
            position: absolute !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
            line-height: 1;
        }
        #content .kt-alert.kt-alert-danger > .ki-filled::after {
            line-height: 1;
        }
        #content .kt-alert.kt-alert-danger > span,
        #content .kt-alert.kt-alert-danger > ul {
            min-width: 0;
            flex: 1 1 auto;
            margin-bottom: 0;
            color: inherit;
        }
        .dark #content .kt-alert.kt-alert-danger {
            background-color: rgba(239, 68, 68, 0.16) !important;
            border-color: rgb(239, 68, 68) !important;
            color: rgb(248, 113, 113) !important;
        }
        .dark #content .kt-alert.kt-alert-danger > .ki-filled {
            color: rgb(248, 113, 113) !important;
        }

        /* Vaste meldingen rechtsboven: boven sticky header (z-index 9999) */
        .admin-fixed-toast {
            position: fixed;
            right: 1.25rem;
            top: calc(var(--kt-header-height, 4.375rem) + 0.75rem);
            z-index: 10050;
            max-width: 28rem;
            width: calc(100% - 2.5rem);
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.12);
            pointer-events: auto;
        }
        @media (min-width: 640px) {
            .admin-fixed-toast {
                width: auto;
            }
        }
        .dark .admin-fixed-toast {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.35);
        }

        html[data-sidebar-scroll-pending="1"] #sidebar_scrollable {
            visibility: hidden;
        }
    </style>
</head>
<body class="demo1 kt-sidebar-fixed kt-header-fixed flex h-full bg-background text-base text-foreground antialiased" @if(session('success')) data-admin-just-saved="1" @endif>
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
                    @if ($errors->any())
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        var content = document.getElementById('content');
                        if (!content) return;
                        var hash = (window.location.hash || '').replace(/^#/, '');
                        if (hash) {
                            var byId = document.getElementById(hash);
                            if (byId && typeof byId.scrollIntoView === 'function') {
                                byId.scrollIntoView({ behavior: 'auto', block: 'start' });
                                return;
                            }
                        }
                        var first = content.querySelector('[data-validation-error], .border-destructive, [data-server-error]');
                        if (first && typeof first.scrollIntoView === 'function') {
                            first.scrollIntoView({ behavior: 'auto', block: 'center' });
                        }
                    });
                    </script>
                    @endif
                    @php
                        $adminBannerError = session('error');
                        $adminBannerWarning = session('warning') ?: $errors->first('package');
                    @endphp
                    @if($adminBannerError)
                        <div class="kt-alert kt-alert-danger mb-5" role="alert">
                            <i class="ki-filled ki-information"></i>
                            {{ $adminBannerError }}
                        </div>
                    @endif
                    @if($adminBannerWarning)
                        <div class="kt-alert kt-alert-warning mb-5" role="alert">
                            <i class="ki-filled ki-information"></i>
                            {{ $adminBannerWarning }}
                        </div>
                    @endif

                    @if($adminShowTenantNotice ?? false)
                        @include('admin.partials.tenant-scope-notice', [
                            'adminTenantScopeVariant' => $adminTenantScopeVariant ?? 'default',
                            'adminTenantScopeMessage' => $adminTenantScopeMessage ?? null,
                        ])
                    @endif

                    @if(!($adminHideContentWithoutTenant ?? false))
                        @yield('content')
                    @endif
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

    @php
        $adminMustChangePassword = ($adminMustChangePassword ?? false) || (bool) (auth()->user()?->must_change_password);
    @endphp
    @if($adminMustChangePassword)
        @include('admin.partials.force-password-modal')
    @endif

    @include('layouts.partials.scripts')

    <!-- Sidebar accordions: keep submenu toggles working (also after datatable menu re-inits). -->
    <script>
    (function initAdminSidebarAccordions() {
        function bindSidebarAccordions() {
            const sidebarMenu = document.getElementById('sidebar_menu');
            if (!sidebarMenu || sidebarMenu.dataset.sidebarAccordionBound === '1') {
                return;
            }
            sidebarMenu.dataset.sidebarAccordionBound = '1';

            const accordionItems = sidebarMenu.querySelectorAll('.kt-menu-item[data-kt-menu-item-toggle="accordion"]');

            function syncAccordionContent(accordionItem, isOpen) {
                const content = accordionItem.querySelector(':scope > .kt-menu-accordion');
                if (content) {
                    content.classList.toggle('show', isOpen);
                }
            }

            function toggleAccordion(accordionItem) {
                const isOpen = accordionItem.classList.contains('show');
                const expandAll = sidebarMenu.getAttribute('data-kt-menu-accordion-expand-all') === 'true';

                if (!expandAll) {
                    accordionItems.forEach(function (item) {
                        if (item !== accordionItem && item.classList.contains('show')) {
                            item.classList.remove('show');
                            syncAccordionContent(item, false);
                        }
                    });
                }

                accordionItem.classList.toggle('show', !isOpen);
                syncAccordionContent(accordionItem, !isOpen);
            }

            accordionItems.forEach(function (accordionItem) {
                if (accordionItem.classList.contains('show') || accordionItem.classList.contains('here')) {
                    syncAccordionContent(accordionItem, accordionItem.classList.contains('show'));
                }

                const observer = new MutationObserver(function (mutations) {
                    mutations.forEach(function (mutation) {
                        if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                            syncAccordionContent(accordionItem, accordionItem.classList.contains('show'));
                        }
                    });
                });

                observer.observe(accordionItem, {
                    attributes: true,
                    attributeFilter: ['class'],
                });
            });

            sidebarMenu.addEventListener('click', function (event) {
                if (event.target.closest('.kt-menu-accordion')) {
                    return;
                }

                const accordionItem = event.target.closest('.kt-menu-item[data-kt-menu-item-trigger][data-kt-menu-item-toggle="accordion"]');
                if (!accordionItem) {
                    return;
                }

                const headerLink = accordionItem.querySelector(':scope > .kt-menu-link');
                if (!headerLink || !headerLink.contains(event.target)) {
                    return;
                }

                event.preventDefault();
                event.stopImmediatePropagation();

                const menu = window.KTMenu?.getInstance?.(sidebarMenu);
                if (menu && typeof menu.click === 'function') {
                    menu.click(headerLink, event);
                    return;
                }

                toggleAccordion(accordionItem);
            }, true);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bindSidebarAccordions);
        } else {
            bindSidebarAccordions();
        }
    })();
    </script>

    <!-- Sidebar scrollpositie bewaren bij menu-navigatie (zonder flikkering) -->
    <script>
    (function initAdminSidebarScroll() {
        var SIDEBAR_SCROLL_KEY = 'admin-sidebar-scroll';
        var isApplyingScroll = false;

        function getSidebarScrollable() {
            return document.getElementById('sidebar_scrollable');
        }

        function clampScrollTop(el, y) {
            var max = Math.max(0, el.scrollHeight - el.clientHeight);
            return Math.min(Math.max(0, y), max);
        }

        function saveSidebarScroll() {
            if (isApplyingScroll) {
                return;
            }
            try {
                var el = getSidebarScrollable();
                if (!el) {
                    return;
                }
                sessionStorage.setItem(SIDEBAR_SCROLL_KEY, String(el.scrollTop || 0));
            } catch (err) {}
        }

        function getSavedSidebarScroll() {
            try {
                var saved = sessionStorage.getItem(SIDEBAR_SCROLL_KEY);
                if (saved === null) {
                    return null;
                }
                var y = parseInt(saved, 10);
                return isNaN(y) || y < 0 ? null : y;
            } catch (err) {
                return null;
            }
        }

        function restoreSidebarScrollOnce() {
            var el = getSidebarScrollable();
            var y = getSavedSidebarScroll();

            document.documentElement.removeAttribute('data-sidebar-scroll-pending');

            if (!el || y === null || y <= 0) {
                return;
            }

            isApplyingScroll = true;
            el.scrollTop = clampScrollTop(el, y);
            isApplyingScroll = false;
        }

        function finishSidebarScrollRestore() {
            restoreSidebarScrollOnce();
        }

        function bindSidebarScrollPersistence() {
            var sidebarMenu = document.getElementById('sidebar_menu');
            var scrollable = getSidebarScrollable();
            if (!scrollable || scrollable.dataset.sidebarScrollBound === '1') {
                return;
            }
            scrollable.dataset.sidebarScrollBound = '1';

            var scrollTimer;
            scrollable.addEventListener('scroll', function () {
                clearTimeout(scrollTimer);
                scrollTimer = setTimeout(saveSidebarScroll, 150);
            }, { passive: true });

            if (sidebarMenu) {
                sidebarMenu.addEventListener('click', function (event) {
                    var link = event.target.closest('a.kt-menu-link[href]');
                    if (!link) {
                        return;
                    }
                    var href = (link.getAttribute('href') || '').trim();
                    if (!href || href === '#' || href.startsWith('javascript:')) {
                        return;
                    }
                    saveSidebarScroll();
                }, true);
            }
        }

        function init() {
            bindSidebarScrollPersistence();

            if (document.readyState === 'complete') {
                finishSidebarScrollRestore();
            } else {
                window.addEventListener('load', finishSidebarScrollRestore, { once: true });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
    </script>

    <!-- Logo light/dark sync: toon juiste logo bij thema-wissel -->
    <script>
    (function() {
        function isDark() {
            var root = document.documentElement;
            if (root.classList.contains('dark')) return true;
            if (root.classList.contains('light')) return false;
            var m = root.getAttribute('data-kt-theme-mode');
            if (m === 'dark') return true;
            if (m === 'light') return false;
            return document.body.classList.contains('dark');
        }
        function syncLogoVisibility() {
            var dark = isDark();
            document.querySelectorAll('.logo-light').forEach(function(el) { el.style.setProperty('display', dark ? 'none' : 'block', 'important'); });
            document.querySelectorAll('.logo-dark').forEach(function(el) { el.style.setProperty('display', dark ? 'block' : 'none', 'important'); });
        }
        window.syncAdminLogoVisibility = syncLogoVisibility;
        function initLogoSync() {
            syncLogoVisibility();
            var obs = new MutationObserver(syncLogoVisibility);
            obs.observe(document.documentElement, { attributes: true, attributeFilter: ['class', 'data-kt-theme-mode'] });
            obs.observe(document.body, { attributes: true, attributeFilter: ['class'] });
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initLogoSync);
        } else {
            initLogoSync();
        }
    })();
    </script>

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

    <!-- Ctrl+S / Cmd+S: hoofdformulier in #content opslaan (alle admin-pagina's met formulier) -->
    <script>
    (function() {
        function findPrimarySubmitButton(form) {
            var selectors = [
                'button[type="submit"].kt-btn-primary',
                'button[type="submit"][class*="btn-primary"]',
                'button[type="submit"].kt-btn-success',
                'input[type="submit"].kt-btn-primary',
                'button[type="submit"]',
                'input[type="submit"]'
            ];
            for (var i = 0; i < selectors.length; i++) {
                var btn = form.querySelector(selectors[i]);
                if (btn) return btn;
            }
            return null;
        }

        function firstMainFormInContent(content) {
            if (!content) return null;
            var preferred = content.querySelector('form[data-cmd-s-primary="1"]');
            if (preferred && !preferred.closest('[role="dialog"]') && !preferred.closest('.modal') && !preferred.hasAttribute('data-no-cmd-s')) {
                return preferred;
            }
            var list = content.querySelectorAll('form');
            for (var i = 0; i < list.length; i++) {
                var f = list[i];
                if (f.closest('[role="dialog"]') || f.closest('.modal')) continue;
                if (f.hasAttribute('data-no-cmd-s')) continue;
                return f;
            }
            return null;
        }

        function resolveFormForSave(active, content) {
            if (!content) return null;
            if (active && typeof active.closest === 'function') {
                var fromFocus = active.closest('form');
                if (fromFocus && !fromFocus.closest('[role="dialog"]') && !fromFocus.closest('.modal') && !fromFocus.hasAttribute('data-no-cmd-s')) {
                    return fromFocus;
                }
            }
            var websitePageForm = document.getElementById('website-page-form');
            if (websitePageForm && content.contains(websitePageForm) && active) {
                if (content.contains(active) || (active.tagName === 'IFRAME' && content.contains(active))) {
                    return websitePageForm;
                }
            }
            return firstMainFormInContent(content);
        }

        document.addEventListener('keydown', function(e) {
            var isSave = (e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S' || e.keyCode === 83 || e.which === 83);
            if (!isSave) return;

            var path = window.location.pathname || '';
            if (path.includes('/admin/login') || path.includes('/admin/meld/')) return;

            var active = document.activeElement;
            var content = document.getElementById('content');
            var form = resolveFormForSave(active, content);
            if (!form) return;

            e.preventDefault();

            var btn = findPrimarySubmitButton(form);
            if (!btn) {
                try {
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                } catch (err) {}
                return;
            }

            if (btn.disabled) btn.disabled = false;
            try {
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit(btn);
                } else {
                    form.submit();
                }
            } catch (err) {
                try { btn.disabled = false; btn.click(); } catch (e2) {}
            }
        });
    })();
    </script>

    <!-- Direct serverfout onder veld wissen bij typen/wijzigen (inline: werkt altijd, los van Vite-bundle) -->
    <script>
    (function() {
        function clearFieldServerError(el) {
            if (!el || !el.classList) return;
            /* Alleen Laravel/serverfouten wissen — niet client-side hint-rand (e-mail/telefoon) */
            if (!el.hasAttribute('data-server-error')) return;

            el.classList.remove('border-destructive');
            el.removeAttribute('data-server-error');

            var fieldName = el.getAttribute('name');
            var cell = el.closest('td');

            if (fieldName && cell) {
                var esc = fieldName.replace(/\\/g, '\\\\').replace(/"/g, '\\"');
                var byFor = cell.querySelector('[data-validation-error][data-validation-error-for="' + esc + '"]');
                if (byFor) {
                    byFor.remove();
                    return;
                }
            }

            var n = el.nextElementSibling;
            while (n) {
                if (n.nodeType === 1 && n.matches) {
                    if (n.matches('input:not([type=hidden])') || n.matches('select') || n.matches('textarea')) {
                        if (n !== el) break;
                    }
                    if (n.hasAttribute('data-validation-error')) {
                        n.remove();
                        return;
                    }
                }
                n = n.nextElementSibling;
            }

            if (cell) {
                var err = cell.querySelector('[data-validation-error]');
                if (err) err.remove();
            }
        }

        function onFieldEdit(e) {
            var t = e.target;
            if (!t || typeof t.closest !== 'function' || !t.closest('#content')) return;
            var tag = (t.tagName || '').toUpperCase();
            if (tag !== 'INPUT' && tag !== 'TEXTAREA' && tag !== 'SELECT') return;
            var type = (t.getAttribute('type') || '').toLowerCase();
            if (type === 'hidden' || type === 'button' || type === 'submit' || type === 'reset') return;
            clearFieldServerError(t);
        }

        document.addEventListener('input', onFieldEdit, true);
        document.addEventListener('change', onFieldEdit, true);
    })();
    </script>

    <!-- Scrollpositie na opslaan: standaard voor alle admin-pagina's -->
    <script>
    (function() {
        var SCROLL_KEY = 'admin-scroll-after-save';
        function saveScroll() {
            try {
                var y = window.scrollY || window.pageYOffset || 0;
                sessionStorage.setItem(SCROLL_KEY, String(y));
            } catch (err) {}
        }
        var scrollSaveTimer;
        document.addEventListener('scroll', function() {
            clearTimeout(scrollSaveTimer);
            scrollSaveTimer = setTimeout(saveScroll, 150);
        }, { passive: true });
        document.addEventListener('submit', function(e) {
            var form = e.target && e.target.tagName === 'FORM' ? e.target : (e.target && e.target.closest ? e.target.closest('form') : null);
            if (form && (form.method === 'post' || form.method === 'POST') && form.action) {
                saveScroll();
            }
        }, true);
        function restoreScrollAfterSave() {
            var justSaved = document.body && document.body.getAttribute('data-admin-just-saved') === '1';
            var u;
            try { u = window.location.href ? new URL(window.location.href) : null; } catch (e) { u = null; }
            var hasSavedParam = u && (u.searchParams.get('saved') || u.searchParams.get('updated') || u.searchParams.get('created'));
            if (!justSaved && !hasSavedParam) return;
            try {
                var saved = sessionStorage.getItem(SCROLL_KEY);
                if (saved !== null) {
                    var y = parseInt(saved, 10);
                    if (!isNaN(y) && y >= 0) {
                        function doScroll() { window.scrollTo(0, y); }
                        doScroll();
                        requestAnimationFrame(function() { doScroll(); });
                        setTimeout(doScroll, 100);
                        setTimeout(doScroll, 350);
                        setTimeout(doScroll, 800);
                        setTimeout(doScroll, 1500);
                        setTimeout(function() { doScroll(); sessionStorage.removeItem(SCROLL_KEY); }, 2500);
                    }
                }
            } catch (err) {}
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', restoreScrollAfterSave);
        } else {
            restoreScrollAfterSave();
        }
        window.addEventListener('load', function() {
            restoreScrollAfterSave();
        });
    })();
    </script>

    <!-- Flash success: na 5s uitfaden en verwijderen (alle .kt-alert-success in #content) -->
    <script>
    (function() {
        function fadeRemove(el) {
            if (!el || !el.parentNode) return;
            el.style.transition = 'opacity 0.35s ease';
            el.style.opacity = '0';
            setTimeout(function() {
                if (el.parentNode) el.parentNode.removeChild(el);
            }, 350);
        }
        function init() {
            var alerts = document.querySelectorAll('#content .kt-alert-success');
            alerts.forEach(function(el) {
                if (el.hasAttribute('data-no-auto-dismiss')) return;
                setTimeout(function() { fadeRemove(el); }, 5000);
            });
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
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

        function isSessionCheckUrl(url) {
            if (!url) return false;
            const urlStr = typeof url === 'string' ? url : (url.url || '');
            return urlStr.includes('session-check');
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

            // Sessiecheck mag nooit globale login-redirect triggeren (route gebruikt alleen auth, geen rol).
            if (isSessionCheckUrl(url)) {
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
    @include('partials.password-toggle')
    @include('admin.layouts.partials.ai-chatbot-include')
</body>
</html>
