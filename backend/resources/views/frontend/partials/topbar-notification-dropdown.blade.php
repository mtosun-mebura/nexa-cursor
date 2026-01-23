<div class="flex items-center gap-1.5">
    <!-- Notifications -->
    @php
        $unreadCount = 0;
        if (auth()->check() && auth()->user()) {
            $unreadCount = auth()->user()->notifications()->whereNull('read_at')->count();
        }
    @endphp
    <button class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors relative notification-icon-button" 
            data-kt-drawer-toggle="#notifications_drawer">
        <i class="ki-filled {{ $unreadCount > 0 ? 'ki-notification-on text-red-500' : 'ki-notification text-gray-600 dark:text-gray-300' }} text-lg notification-icon">
        </i>
        @if($unreadCount > 0)
        <span class="absolute top-0 end-0 flex size-5 items-center justify-center rounded-full bg-danger text-[11px] font-semibold leading-none text-white notification-badge" style="min-width: 20px; min-height: 20px;">
            {{ $unreadCount }}
        </span>
        @endif
    </button>
    <!--Notifications Drawer-->
    @include('partials.notification-drawer')
    <!--End of Notifications Drawer-->
    <!-- End of Notifications -->
</div>

