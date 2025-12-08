<div class="flex items-center gap-1.5">
	<!-- Notifications -->
	@php
		$unreadCount = 0;
		if (auth()->check() && auth()->user()) {
			$unreadCount = auth()->user()->notifications()->whereNull('read_at')->count();
		}
	@endphp
	<button class="kt-btn kt-btn-ghost kt-btn-icon size-8 hover:bg-background hover:[&_i]:text-primary relative" data-kt-drawer-toggle="#notifications_drawer">
		<i class="ki-filled ki-notification-status text-lg">
		</i>
		@if($unreadCount > 0)
		<span class="absolute top-0 end-0 flex size-4 items-center justify-center rounded-full bg-danger text-[10px] font-semibold leading-none text-white">
			{{ $unreadCount > 9 ? '9+' : $unreadCount }}
		</span>
		@endif
	</button>
	<!--Notifications Drawer-->
	@include('partials.notification-drawer')
	<!--End of Notifications Drawer-->
	<!-- End of Notifications -->
</div>