<!DOCTYPE html>
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" dir="ltr" lang="nl">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Metronic demo1 (Vue playground) - Admin</title>

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
<div class="flex grow">
    <!-- Sidebar -->
    <div
        id="sidebar"
        class="kt-sidebar bg-background border-e border-e-border fixed top-0 bottom-0 z-20 hidden lg:flex flex-col items-stretch shrink-0 [--kt-drawer-enable:true] lg:[--kt-drawer-enable:false]"
        data-kt-drawer="true"
        data-kt-drawer-class="kt-drawer kt-drawer-start top-0 bottom-0"
    >
        <div class="kt-sidebar-header hidden lg:flex items-center relative justify-between px-3 lg:px-6 shrink-0" id="sidebar_header">
            <div class="kt-sidebar-logo min-w-0">
                <a class="dark:hidden" href="{{ route('admin.playground.metronic-demo1') }}">
                    <img class="default-logo min-h-[22px] max-w-none" src="{{ asset('metronic-v9.4.13/demo1/assets/media/app/default-logo.svg') }}" />
                    <img class="small-logo min-h-[22px] max-w-none" src="{{ asset('metronic-v9.4.13/demo1/assets/media/app/mini-logo.svg') }}" />
                </a>
                <a class="hidden dark:block" href="{{ route('admin.playground.metronic-demo1') }}">
                    <img class="default-logo min-h-[22px] max-w-none" src="{{ asset('metronic-v9.4.13/demo1/assets/media/app/default-logo-dark.svg') }}" />
                    <img class="small-logo min-h-[22px] max-w-none" src="{{ asset('metronic-v9.4.13/demo1/assets/media/app/mini-logo.svg') }}" />
                </a>
            </div>

            <button
                class="kt-btn kt-btn-outline kt-btn-icon size-[30px] absolute start-full top-2/4 z-40 -translate-x-2/4 -translate-y-2/4 rtl:translate-x-2/4"
                data-kt-toggle="body"
                data-kt-toggle-class="kt-sidebar-collapse"
                id="sidebar_toggle"
                type="button"
            >
                <i class="ki-filled ki-black-left-line kt-toggle-active:rotate-180 transition-all duration-300 rtl:translate rtl:rotate-180 rtl:kt-toggle-active:rotate-0"></i>
            </button>
        </div>

        <div class="kt-sidebar-content flex grow shrink-0 py-5 pe-2" id="sidebar_content">
            <div
                id="sidebar_scrollable"
                class="kt-scrollable-y-hover grow shrink-0 flex ps-2 lg:ps-5 pe-1 lg:pe-3"
                data-kt-scrollable="true"
                data-kt-scrollable-dependencies="#sidebar_header"
                data-kt-scrollable-height="auto"
                data-kt-scrollable-offset="0px"
                data-kt-scrollable-wrappers="#sidebar_content"
            >
                <div class="kt-menu flex flex-col grow gap-1" data-kt-menu="true" data-kt-menu-accordion-expand-all="false" id="sidebar_menu">
                    <div class="kt-menu-item here show">
                        <a class="kt-menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]" href="{{ route('admin.playground.metronic-demo1') }}">
                            <span class="kt-menu-icon items-start text-muted-foreground w-[20px]">
                                <i class="ki-filled ki-element-11 text-lg"></i>
                            </span>
                            <span class="kt-menu-title text-sm font-medium text-foreground kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary">
                                Vue component catalog
                            </span>
                        </a>
                    </div>

                    <div class="kt-menu-item pt-2.25 pb-px">
                        <span class="kt-menu-heading uppercase text-xs font-medium text-muted-foreground ps-[10px] pe-[10px]">
                            Super-admin only
                        </span>
                    </div>

                    <div class="kt-menu-item">
                        <a class="kt-menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]" href="{{ route('admin.dashboard') }}">
                            <span class="kt-menu-icon items-start text-muted-foreground w-[20px]">
                                <i class="ki-filled ki-home text-lg"></i>
                            </span>
                            <span class="kt-menu-title text-sm font-medium text-foreground kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary">
                                Terug naar admin
                            </span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Sidebar -->

    <!-- Wrapper -->
    <div class="kt-wrapper flex grow flex-col">
        <!-- Header -->
        <header class="kt-header fixed top-0 z-10 start-0 end-0 flex items-stretch shrink-0 bg-background" data-kt-sticky="true" data-kt-sticky-class="border-b border-border" data-kt-sticky-name="header" id="header">
            <div class="kt-container-fixed flex justify-between items-stretch lg:gap-4" id="headerContainer">
                <div class="flex gap-2.5 lg:hidden items-center -ms-1">
                    <a class="shrink-0" href="{{ route('admin.playground.metronic-demo1') }}">
                        <img class="max-h-[25px] w-full" src="{{ asset('metronic-v9.4.13/demo1/assets/media/app/mini-logo.svg') }}" />
                    </a>
                    <div class="flex items-center">
                        <button class="kt-btn kt-btn-icon kt-btn-ghost" data-kt-drawer-toggle="#sidebar" type="button">
                            <i class="ki-filled ki-menu"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <span class="text-sm text-muted-foreground">Metronic demo1 playground</span>
                    <span class="kt-badge kt-badge-info">super-admin only</span>
                </div>
            </div>
        </header>
        <!-- End Header -->

        <!-- Content -->
        <main class="grow pt-[--kt-header-height] lg:pt-[--kt-header-height]">
            <div class="kt-container-fixed py-6">
                <div id="metronic-vue-demo1-app">
                    <div class="kt-alert kt-alert-warning" role="alert">
                        <div class="kt-alert-title">Component catalog laden…</div>
                        <div class="kt-alert-description">
                            Als dit blijft staan: draai <code>npm run build</code> in <code>backend/</code> of start <code>npm run dev</code> voor Vite.
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <!-- End Content -->
    </div>
    <!-- End Wrapper -->
</div>

<script src="{{ asset('metronic-v9.4.13/demo1/assets/js/core.bundle.js') }}"></script>
<script src="{{ asset('metronic-v9.4.13/demo1/assets/vendors/ktui/ktui.min.js') }}"></script>
@vite('resources/js/metronic-vue-demo1.ts')
</body>
</html>

