<style>
    /* Notification drawer styling similar to chat drawer */
    #notifications_drawer,
    #notifications_drawer.kt-drawer,
    #notifications_drawer[data-kt-drawer="true"],
    #notifications_drawer[data-kt-drawer-initialized="true"] {
        z-index: 99999 !important;
        position: fixed !important;
        right: 1.25rem !important;
        top: 1.25rem !important;
        bottom: 1.25rem !important;
        left: unset !important;
        width: 500px !important;
        max-width: 90% !important;
        transform: translateX(0) !important;
        transition: none !important;
        animation: none !important;
        margin-left: 0 !important;
        background-color: var(--kt-body-bg, #ffffff) !important;
        background: var(--kt-body-bg, #ffffff) !important;
        border-radius: 0.75rem !important; /* rounded-xl = 12px */
        overflow: hidden !important; /* Ensure rounded corners work */
    }
    .dark #notifications_drawer {
        background-color: var(--kt-body-bg-dark, #1e293b) !important;
        background: var(--kt-body-bg-dark, #1e293b) !important;
    }
    /* Force drawer to be hidden by default on page load */
    #notifications_drawer:not([data-user-opened="true"]) {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
    }
    #notifications_drawer.hidden {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
    }
    /* Force hide drawer when explicitly closed */
    #notifications_drawer[data-drawer-closed="true"] {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        transform: translateX(100%) !important;
        right: -100% !important;
        z-index: -1 !important;
    }
    #notifications_drawer:not(.hidden):not([data-drawer-closed="true"]),
    #notifications_drawer[data-notifications-active="true"]:not([data-drawer-closed="true"]) {
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    /* Force visibility when data-notifications-active is set */
    #notifications_drawer[data-notifications-active="true"]:not([data-drawer-closed="true"]) {
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
        z-index: 99999 !important;
        transform: translateX(0) !important;
        right: 1.25rem !important;
        left: unset !important;
        transition: none !important;
        animation: none !important;
        margin-left: 0 !important;
    }
    /* Notification drawer backdrop */
    #notifications_drawer_backdrop {
        z-index: 99998 !important;
        position: fixed !important;
        inset: 0 !important;
        background-color: rgba(0, 0, 0, 0.5) !important;
        backdrop-filter: blur(8px) !important;
        -webkit-backdrop-filter: blur(8px) !important;
    }
    /* Blur the entire page when drawer is open */
    body:has(#notifications_drawer:not(.hidden):not([data-drawer-closed="true"])) {
        overflow: hidden !important;
    }
    body:has(#notifications_drawer_backdrop:not(.hidden)) {
        overflow: hidden !important;
    }
    #notifications_drawer_backdrop.hidden {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
    }
    #notifications_drawer_backdrop:not(.hidden) {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    /* Ensure drawer has proper height and footer stays at bottom */
    #notifications_drawer {
        height: calc(100vh - 2.5rem) !important; /* Full height minus top and bottom padding */
        display: flex !important;
        flex-direction: column !important;
    }
    #notifications_tab_all {
        flex: 1 !important;
        display: flex !important;
        flex-direction: column !important;
        min-height: 0 !important;
    }
    #notifications_tab_all.hidden {
        display: none !important;
    }
    #notifications_all_footer {
        margin-top: auto !important;
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 0.625rem !important;
    }
    #notifications_all_footer.hidden {
        display: none !important;
    }
    /* Make footer buttons smaller like backend */
    #notifications_drawer #notifications_all_footer .kt-btn-sm {
        height: 34px !important;
        min-height: 34px !important;
        padding: 0.375rem 0.75rem !important;
        font-size: 0.875rem !important;
        line-height: 1.25rem !important;
        border: 1px solid var(--border, rgb(156, 163, 175)) !important;
        border-radius: 0.5rem !important; /* rounded-lg */
        transition: background-color 0.2s ease, border-color 0.2s ease !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        flex: 1 !important;
        min-width: 0 !important;
    }
    .dark #notifications_drawer #notifications_all_footer .kt-btn-sm {
        border-color: rgb(75, 85, 99) !important; /* gray-600 */
    }
    /* Footer button hover */
    #notifications_drawer #notifications_all_footer .kt-btn-sm:hover {
        background-color: rgba(0, 0, 0, 0.05) !important;
        border-color: var(--border, rgb(156, 163, 175)) !important;
    }
    .dark #notifications_drawer #notifications_all_footer .kt-btn-sm:hover {
        background-color: rgba(255, 255, 255, 0.05) !important;
        border-color: rgb(75, 85, 99) !important;
    }
    /* Action buttons (Accept/Decline) in notification items - rounded borders */
    #notifications_drawer .accept-interview,
    #notifications_drawer .decline-interview {
        display: inline-flex !important;
        visibility: visible !important;
        opacity: 1 !important;
        border-radius: 0.5rem !important; /* rounded-lg */
        padding-left: 1rem !important; /* px-4 */
        padding-right: 1rem !important; /* px-4 */
    }
    /* Decline button - red */
    #notifications_drawer .decline-interview {
        background-color: rgb(239, 68, 68) !important; /* red-500 */
        border: 1px solid rgb(239, 68, 68) !important;
        color: white !important;
    }
    #notifications_drawer .decline-interview:hover {
        background-color: rgb(220, 38, 38) !important; /* red-600 */
        border-color: rgb(220, 38, 38) !important;
    }
    /* Accept button - green */
    #notifications_drawer .accept-interview {
        background-color: rgb(34, 197, 94) !important; /* green-500 */
        border: 1px solid rgb(34, 197, 94) !important;
        color: white !important;
    }
    #notifications_drawer .accept-interview:hover {
        background-color: rgb(22, 163, 74) !important; /* green-600 */
        border-color: rgb(22, 163, 74) !important;
    }
    /* Checkbox styling - same color as background, only border, larger */
    /* Notification item spacing and hover */
    #notifications_drawer .notification-item {
        padding-top: 0.75rem !important; /* py-3 */
        padding-bottom: 0.75rem !important; /* py-3 */
        transition: background-color 0.2s ease !important;
        position: relative !important;
        border-radius: 0.375rem !important; /* rounded-md */
        margin-left: 0.5rem !important;
        margin-right: 0.5rem !important;
    }
    /* Hover effect for read notifications (no background by default) */
    #notifications_drawer .notification-item[data-is-read="true"]:hover {
        background-color: rgba(0, 0, 0, 0.05) !important;
    }
    @supports (color: color-mix(in lab, red, red)) {
        #notifications_drawer .notification-item[data-is-read="true"]:hover {
            background-color: color-mix(in oklab, rgba(0, 0, 0, 0.1) 50%, transparent) !important;
        }
    }
    /* Unread notification background - lighter color */
    #notifications_drawer .notification-item[data-is-read="false"] {
        background-color: rgba(0, 0, 0, 0.04) !important;
    }
    @supports (color: color-mix(in lab, red, red)) {
        #notifications_drawer .notification-item[data-is-read="false"] {
            background-color: color-mix(in oklab, rgba(0, 0, 0, 0.08) 20%, transparent) !important;
        }
    }
    /* Hover effect for unread notifications - make darker on hover */
    #notifications_drawer .notification-item[data-is-read="false"]:hover {
        background-color: rgba(0, 0, 0, 0.12) !important;
    }
    @supports (color: color-mix(in lab, red, red)) {
        #notifications_drawer .notification-item[data-is-read="false"]:hover {
            background-color: color-mix(in oklab, rgba(0, 0, 0, 0.18) 70%, transparent) !important;
        }
    }
    /* Dark mode hover for read notifications */
    .dark #notifications_drawer .notification-item[data-is-read="true"]:hover {
        background-color: rgba(255, 255, 255, 0.05) !important;
    }
    @supports (color: color-mix(in lab, red, red)) {
        .dark #notifications_drawer .notification-item[data-is-read="true"]:hover {
            background-color: color-mix(in oklab, rgba(255, 255, 255, 0.1) 50%, transparent) !important;
        }
    }
    /* Dark mode unread notification background - lighter color */
    .dark #notifications_drawer .notification-item[data-is-read="false"] {
        background-color: rgba(255, 255, 255, 0.06) !important;
    }
    @supports (color: color-mix(in lab, red, red)) {
        .dark #notifications_drawer .notification-item[data-is-read="false"] {
            background-color: color-mix(in oklab, rgba(255, 255, 255, 0.12) 20%, transparent) !important;
        }
    }
    /* Dark mode hover for unread notifications */
    .dark #notifications_drawer .notification-item[data-is-read="false"]:hover {
        background-color: rgba(255, 255, 255, 0.12) !important;
    }
    @supports (color: color-mix(in lab, red, red)) {
        .dark #notifications_drawer .notification-item[data-is-read="false"]:hover {
            background-color: color-mix(in oklab, rgba(255, 255, 255, 0.18) 70%, transparent) !important;
        }
    }
    /* Align checkbox and avatar container to top - target the flex container with checkbox and avatar */
    #notifications_drawer .notification-item > div > div.flex.items-start {
        align-self: flex-start !important;
    }
    /* Center checkbox vertically within its container */
    #notifications_drawer .notification-item .notification-checkbox {
        align-self: center !important;
    }
    /* Checkbox styling - same color as background, only border, larger */
    #notifications_drawer .notification-checkbox {
        width: 1.125rem !important; /* 18px */
        height: 1.125rem !important; /* 18px */
        background-color: var(--kt-body-bg, #ffffff) !important;
        border: 2px solid var(--border, rgb(156, 163, 175)) !important;
        border-radius: 0.25rem !important;
        cursor: pointer !important;
        appearance: none !important;
        -webkit-appearance: none !important;
        -moz-appearance: none !important;
        position: relative !important;
        flex-shrink: 0 !important;
        margin: 0 !important;
    }
    .dark #notifications_drawer .notification-checkbox {
        background-color: var(--kt-body-bg-dark, #1e293b) !important;
        border-color: rgb(75, 85, 99) !important;
    }
    #notifications_drawer .notification-checkbox:checked {
        background-color: var(--kt-primary, rgb(0, 122, 255)) !important;
        border-color: var(--kt-primary, rgb(0, 122, 255)) !important;
    }
    #notifications_drawer .notification-checkbox:checked::after {
        content: '✓' !important;
        position: absolute !important;
        top: 50% !important;
        left: 50% !important;
        transform: translate(-50%, -50%) !important;
        color: white !important;
        font-size: 0.75rem !important;
        font-weight: bold !important;
        line-height: 1 !important;
    }
    /* Avatar styling - larger with border like chats */
    #notifications_drawer .kt-avatar {
        width: 2.75rem !important; /* 44px - size-11 */
        height: 2.75rem !important; /* 44px - size-11 */
        border: 2px solid rgb(156, 163, 175) !important; /* gray-400 */
        border-radius: 50% !important;
        overflow: hidden !important;
        position: relative !important;
        box-sizing: border-box !important;
    }
    .dark #notifications_drawer .kt-avatar {
        border-color: rgb(75, 85, 99) !important; /* gray-600 */
    }
    #notifications_drawer .kt-avatar-image {
        width: 100% !important;
        height: 100% !important;
    }
    #notifications_drawer .kt-avatar-image img {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
        border-radius: 50% !important;
    }
    /* Avatar indicator positioning */
    #notifications_drawer .kt-avatar-indicator {
        position: absolute !important;
        bottom: -0.25rem !important;
        right: -0.25rem !important;
        z-index: 10 !important;
    }
    /* Unread notification indicator */
    #notifications_drawer .notification-item:not(.opacity-75) {
        position: relative !important;
    }
    /* Hover effect for clickable notifications */
    #notifications_drawer .notification-item:hover {
        background-color: var(--muted) !important;
    }
    /* Interview response modal styling */
    #interview-response-modal {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    #interview-response-modal.hidden {
        display: none !important;
    }
    #interview-response-modal > div {
        background-color: var(--kt-body-bg, #ffffff) !important;
        color: var(--kt-body-color, #1f2937) !important;
    }
    .dark #interview-response-modal > div {
        background-color: var(--kt-body-bg-dark, #1e293b) !important;
        color: var(--kt-body-color-dark, #f1f5f9) !important;
    }
    #interview-response-modal h3 {
        color: var(--kt-body-color, #1f2937) !important;
        margin: 0 !important;
    }
    .dark #interview-response-modal h3 {
        color: var(--kt-body-color-dark, #f1f5f9) !important;
    }
    #interview-response-modal label {
        color: var(--kt-body-color, #374151) !important;
        display: block !important;
        margin-bottom: 0.5rem !important;
    }
    .dark #interview-response-modal label {
        color: var(--kt-body-color-dark, #cbd5e1) !important;
    }
    #interview-response-modal textarea {
        background-color: var(--kt-body-bg, #ffffff) !important;
        color: var(--kt-body-color, #1f2937) !important;
        border: 1px solid var(--border, #e5e7eb) !important;
    }
    .dark #interview-response-modal textarea {
        background-color: var(--kt-body-bg-dark, #1e293b) !important;
        color: var(--kt-body-color-dark, #f1f5f9) !important;
        border-color: var(--border-dark, #334155) !important;
    }
</style>

<!-- Notification Drawer Backdrop -->
<div id="notifications_drawer_backdrop" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden" onclick="if(event.target === this) handleNotificationDrawerClose();"></div>

<!--Notifications Drawer-->
<div class="hidden kt-drawer kt-drawer-end card flex-col max-w-[90%] w-[500px] top-5 bottom-5 end-5 rounded-xl border border-border bg-background"
     data-kt-drawer="true"
     data-kt-drawer-container="body"
     id="notifications_drawer">
	<div class="flex items-center justify-between gap-2.5 text-sm text-mono font-semibold px-5 py-2.5 border-b border-border bg-background" id="notifications_header">
		Notificaties
		<button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0" data-kt-drawer-dismiss="true">
			<i class="ki-filled ki-cross"></i>
		</button>
	</div>

	<!-- Notifications List View -->
	<div class="flex flex-col h-full bg-background" id="notifications_tab_all">
		<div class="flex-1 overflow-y-auto bg-background" style="min-height: 0;">
			<div class="flex flex-col gap-0 pt-3 pb-4" id="notifications_list">
				<!-- Notifications will be loaded here via JavaScript -->
				<div class="flex items-center justify-center py-10 px-5">
					<div class="text-center">
						<i class="ki-filled ki-notification text-4xl text-muted-foreground mb-3"></i>
						<p class="text-sm text-muted-foreground">Laden...</p>
					</div>
				</div>
			</div>
		</div>
		<div class="border-t border-border shrink-0"></div>
		<div class="grid grid-cols-2 p-5 gap-2.5 bg-background shrink-0" id="notifications_all_footer" style="display: grid;">
			<button class="kt-btn kt-btn-sm kt-btn-outline justify-center" id="archive_all_btn">
				Alles archiveren
			</button>
			<button class="kt-btn kt-btn-sm kt-btn-outline justify-center" id="mark_all_read_btn">
				Alles als gelezen markeren
			</button>
		</div>
	</div>

	<!-- Notification Detail View -->
	<div class="flex flex-col h-full bg-background" id="notification_detail_view" style="display: none;">
		<div class="flex items-center justify-between gap-2.5 text-sm text-mono font-semibold px-5 py-2.5 border-b border-border bg-background">
			<div class="flex items-center gap-2.5">
				<button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost back-to-list-btn" title="Terug">
					<i class="ki-filled ki-arrow-left"></i>
				</button>
				<span id="notification_detail_title">Notificatie Details</span>
			</div>
			<button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0" data-kt-drawer-dismiss="true">
				<i class="ki-filled ki-cross"></i>
			</button>
		</div>
		<div class="flex-1 overflow-y-auto bg-background" style="min-height: 0;">
			<div class="p-5" id="notification_detail_content">
				<!-- Notification details will be loaded here -->
			</div>
		</div>
	</div>
</div>
<!--End of Notifications Drawer-->

