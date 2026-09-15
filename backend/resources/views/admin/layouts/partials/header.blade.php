<!-- Header -->
<header class="kt-header fixed end-0 start-0 top-0 z-[99] flex shrink-0 items-stretch bg-background" data-kt-sticky="true"
    data-kt-sticky-class="border-b border-border" data-kt-sticky-name="header" id="header" style="z-index: 9999;">
    <!-- Container -->
    <div class="kt-container-fixed flex w-full items-stretch justify-between gap-2 lg:justify-end lg:gap-4" id="headerContainer">
        <!-- Mobiel: hamburger links, daarna logo met buiten-padding -->
        <div class="admin-mobile-header-start flex items-center gap-2 lg:hidden min-w-0">
            <button
                type="button"
                class="admin-mobile-menu-toggle shrink-0"
                data-kt-drawer-toggle="#sidebar"
                aria-label="Menu openen">
                <svg class="admin-mobile-menu-icon" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
                    <path fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" d="M3.5 6.5h17M3.5 12h17M3.5 17.5h17"/>
                </svg>
            </button>
            <a class="admin-mobile-header-brand min-w-0 flex items-center" href="{{ route('admin.dashboard') }}">
                @include('partials.nexa-brand-logo', [
                    'class' => 'admin-mobile-header-lockup',
                    'alt' => 'NEXA Suite',
                ])
            </a>
        </div>
        <div id="admin-header-flash" class="admin-header-flash min-w-0 flex-1 flex items-center" aria-live="polite">
            @include('admin.layouts.partials.header-flash')
        </div>
        <!-- Topbar -->
        <div class="admin-header-actions flex items-center gap-2.5 shrink-0 ms-auto">
            @include('admin.layouts.partials.ai-chatbot-trigger')
            @include('partials.topbar-notification-dropdown')
            @include('partials.topbar-chat')
            @include('partials.topbar-user-dropdown')
        </div>
        <!-- End of Topbar -->
    </div>
    <!-- End of Container -->
</header>
<!-- End of Header -->

