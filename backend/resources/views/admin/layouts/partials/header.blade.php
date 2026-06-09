<!-- Header -->
<header class="kt-header fixed end-0 start-0 top-0 z-[99] flex shrink-0 items-stretch bg-background" data-kt-sticky="true"
    data-kt-sticky-class="border-b border-border" data-kt-sticky-name="header" id="header" style="z-index: 9999;">
    <!-- Container -->
    <div class="kt-container-fixed flex w-full items-stretch justify-between gap-2 lg:justify-end lg:gap-4" id="headerContainer">
        <!-- Mobiel: hamburger links, daarna logo met buiten-padding -->
        <div class="admin-mobile-header-start flex items-center gap-2.5 lg:hidden min-w-0 shrink-0">
            <button
                type="button"
                class="kt-btn kt-btn-icon kt-btn-ghost admin-mobile-menu-toggle shrink-0"
                data-kt-drawer-toggle="#sidebar"
                aria-label="Menu openen">
                <i class="ki-filled ki-menu" aria-hidden="true"></i>
            </button>
            <a class="shrink-0 flex items-center" href="{{ route('admin.dashboard') }}">
                <img class="h-[30px] w-auto max-w-[140px] object-contain" src="{{ asset('images/nexa-logo.png') }}" alt="NEXA" />
            </a>
        </div>
        <!-- Topbar -->
        <div class="flex items-center gap-2.5 shrink-0 ms-auto">
            @include('partials.topbar-notification-dropdown')
            @include('partials.topbar-chat')
            @include('partials.topbar-user-dropdown')
        </div>
        <!-- End of Topbar -->
    </div>
    <!-- End of Container -->
</header>
<!-- End of Header -->

