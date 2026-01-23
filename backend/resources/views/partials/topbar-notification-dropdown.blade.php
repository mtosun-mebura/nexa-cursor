<div class="flex items-center gap-1.5">
	<!-- Notifications -->
	@php
		$unreadCount = 0;
		if (auth()->check() && auth()->user()) {
			$unreadCount = auth()->user()->notifications()->whereNull('read_at')->count();
		}
	@endphp
	<button class="kt-btn kt-btn-ghost kt-btn-icon size-8 hover:bg-background hover:[&_i]:text-primary relative notification-icon-button {{ $unreadCount > 0 ? 'has-unread' : '' }}" data-kt-drawer-toggle="#notifications_drawer">
		<i class="ki-filled {{ $unreadCount > 0 ? 'ki-notification-on text-red-500' : 'ki-notification' }} text-lg notification-icon">
		</i>
		@if($unreadCount > 0)
		<span class="absolute -top-1 -end-1 flex size-5 items-center justify-center rounded-full bg-danger text-[11px] font-semibold leading-none text-white notification-badge" style="min-width: 20px; min-height: 20px;">
			{{ $unreadCount }}
		</span>
		@endif
	</button>
	<!--Notifications Drawer-->
	@include('partials.notification-drawer')
	<!--End of Notifications Drawer-->
	<!-- End of Notifications -->
</div>