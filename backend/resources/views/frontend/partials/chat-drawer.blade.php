<div>
    <style>
        /* Ensure chat drawer is always on top and hidden by default */
        #chat_drawer,
        #chat_drawer.kt-drawer,
        #chat_drawer[data-kt-drawer="true"],
        #chat_drawer[data-kt-drawer-initialized="true"] {
            z-index: 99999 !important;
            position: fixed !important;
            right: 1.25rem !important; /* end-5 = 1.25rem */
            top: 1.25rem !important; /* top-5 = 1.25rem */
            bottom: 1.25rem !important; /* bottom-5 = 1.25rem */
            left: unset !important;
            width: 450px !important;
            max-width: 90% !important;
            transform: translateX(0) !important; /* Reset any transform that might hide the drawer */
            transition: none !important; /* Disable transitions to prevent animation */
            animation: none !important; /* Disable animations */
            margin-left: 0 !important; /* Ensure no left margin */
            background-color: var(--kt-body-bg, #ffffff) !important;
            background: var(--kt-body-bg, #ffffff) !important;
        }
        .dark #chat_drawer {
            background-color: var(--kt-body-bg-dark, #1e293b) !important;
            background: var(--kt-body-bg-dark, #1e293b) !important;
        }
        /* Force drawer to be hidden by default on page load */
        #chat_drawer:not([data-user-opened="true"]) {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
        }
        #chat_drawer.hidden {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
        }
        /* Force hide drawer when explicitly closed */
        #chat_drawer[data-drawer-closed="true"] {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            transform: translateX(100%) !important;
            right: -100% !important;
            z-index: -1 !important;
        }
        #chat_drawer:not(.hidden):not([data-drawer-closed="true"]),
        #chat_drawer[data-chat-active="true"]:not([data-drawer-closed="true"]) {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        /* Force visibility when data-chat-active is set, even if hidden class is present, but NOT when explicitly closed */
        #chat_drawer[data-chat-active="true"]:not([data-drawer-closed="true"]) {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            z-index: 99999 !important;
            transform: translateX(0) !important; /* Ensure drawer is fully visible */
            right: 1.25rem !important; /* Ensure right positioning */
            left: unset !important; /* Force left to unset to prevent positioning issues */
            transition: none !important; /* Disable transitions */
            animation: none !important; /* Disable animations */
            margin-left: 0 !important; /* Ensure no left margin */
        }
        #chat_drawer_backdrop {
            z-index: 99998 !important;
            position: fixed !important;
            inset: 0 !important;
            background-color: rgba(0, 0, 0, 0.5) !important;
            backdrop-filter: blur(8px) !important;
            -webkit-backdrop-filter: blur(8px) !important;
        }
        /* Blur the entire page when drawer is open */
        body:has(#chat_drawer:not(.hidden):not([data-drawer-closed="true"])) {
            overflow: hidden !important;
        }
        body:has(#chat_drawer_backdrop:not(.hidden)) {
            overflow: hidden !important;
        }
        #chat_drawer_backdrop.hidden {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
        }
        #chat_drawer_backdrop:not(.hidden) {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        /* Fix chat drawer content display */
        #chat_drawer {
            height: calc(100vh - 2.5rem) !important; /* Full height minus top and bottom padding */
        }
        #chat_drawer > .kt-drawer-body {
            height: 100% !important;
            display: flex !important;
            flex-direction: column !important;
        }
        #chat_list_view,
        #chat_messages_view {
            height: 100% !important;
            min-height: 0;
            display: flex !important;
            flex-direction: column;
            position: relative;
        }
        #chat_list_view[style*="display: none"] {
            display: none !important;
        }
        #chat_messages_view[style*="display: none"] {
            display: none !important;
        }
        #chat_messages_view[style*="display: flex"] {
            display: flex !important;
        }
        /* Ensure header stays at top */
        #chat_list_view > div:first-child,
        #chat_messages_view > div:first-child {
            flex-shrink: 0;
        }
        /* Ensure candidate info section doesn't shrink */
        #chat_messages_view > div:nth-child(2) {
            flex-shrink: 0;
        }
        /* Messages area should take all available space */
        #chat_messages_view .kt-scrollable-y-auto {
            flex: 1 1 auto !important;
            min-height: 0 !important;
            overflow-y: auto !important;
        }
        /* Message input should stay at bottom */
        #chat_messages_view > div:last-child {
            flex-shrink: 0;
        }
        #chat_list {
            min-height: 0;
            flex: 1 1 auto;
            overflow-y: auto;
        }
        #chat_list_empty {
            min-height: 200px;
        }
        /* Ensure message input is visible */
        #chat_messages_view #chat_message_input {
            display: block !important;
        }
        /* Ensure message input container stays at bottom */
        #chat_messages_view > div:last-child {
            margin-top: auto !important;
            flex-shrink: 0 !important;
        }
        /* Reduce margin on input container */
        #chat_messages_view > div:last-child > div {
            margin-left: 5px !important;
            margin-right: 5px !important;
        }
        /* Textarea wrapping */
        #chat_message_input {
            min-height: 38px;
            max-height: 200px;
            overflow-y: hidden !important;
            overflow-x: hidden !important;
            word-wrap: break-word;
            white-space: pre-wrap;
            padding-right: 70px !important; /* Space for Send button */
            line-height: 1.4 !important;
            resize: none !important;
        }
        /* Shake animation for icons with unread messages - fast vibrating bell effect */
        @keyframes shake {
            0%, 100% { transform: translateX(0) rotate(0deg); }
            10% { transform: translateX(-2px) rotate(-2deg); }
            20% { transform: translateX(2px) rotate(2deg); }
            30% { transform: translateX(-2px) rotate(-2deg); }
            40% { transform: translateX(2px) rotate(2deg); }
            50% { transform: translateX(-1px) rotate(-1deg); }
            60% { transform: translateX(1px) rotate(1deg); }
            70% { transform: translateX(-1px) rotate(-1deg); }
            80% { transform: translateX(1px) rotate(1deg); }
            90% { transform: translateX(0) rotate(0deg); }
        }
        .chat-icon-button.shake,
        .notification-icon-button.shake {
            animation: shake 0.5s ease-in-out !important;
            animation-iteration-count: 1 !important;
        }
        .chat-icon-button.has-unread .chat-icon,
        .notification-icon-button.has-unread .notification-icon {
            color: rgb(239 68 68) !important; /* text-red-500 */
        }

        /* Avatar container in chat list - add border */
        #chat_drawer #chat_list .w-10.h-10.rounded-full {
            border: 2px solid rgb(156, 163, 175) !important; /* gray-400 */
            box-sizing: border-box !important;
            overflow: visible !important; /* Ensure badge is visible */
            position: relative !important; /* Ensure positioning context */
            z-index: 1 !important; /* Border should be above avatar */
        }
        #chat_drawer #chat_list .w-10.h-10.rounded-full > img,
        #chat_drawer #chat_list .w-10.h-10.rounded-full > span {
            position: relative !important;
            z-index: 0 !important; /* Avatar should be below border */
        }
        .dark #chat_drawer #chat_list .w-10.h-10.rounded-full {
            border-color: rgb(75, 85, 99) !important; /* gray-600 */
        }

        /* Chat badge styling - ensure visibility in light mode */
        .chat-icon-button .chat-badge,
        .notification-icon-button .notification-badge {
            background-color: rgb(239, 68, 68) !important; /* red-500 */
            color: white !important;
            z-index: 10 !important;
            border: 2px solid white !important;
            box-sizing: border-box !important;
        }
        .dark .chat-icon-button .chat-badge,
        .dark .notification-icon-button .notification-badge {
            border-color: rgb(17, 24, 39) !important; /* gray-900 for dark mode */
        }

        /* Darker borders - apply to all borders including outer drawer border */
        #chat_drawer,
        #chat_drawer.border-border,
        #chat_drawer[class*="border-border"],
        #chat_drawer .border-border,
        #chat_drawer [class*="border-border"] {
            border-color: rgb(156, 163, 175) !important; /* gray-400 */
        }
        .dark #chat_drawer,
        .dark #chat_drawer.border-border,
        .dark #chat_drawer[class*="border-border"],
        .dark #chat_drawer .border-border,
        .dark #chat_drawer [class*="border-border"] {
            border-color: rgb(75, 85, 99) !important; /* gray-600 */
        }

        /* Message balloon styling - own messages (blue balloon with rounded corners, no tail) */
        #chat_drawer .kt-card.bg-primary {
            background-color: rgb(0, 122, 255) !important; /* iOS blue */
            border: none !important;
            position: relative !important;
            border-radius: 1.125rem !important; /* 18px - rounded corners */
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1) !important;
        }
        /* Remove tail */
        #chat_drawer .flex.items-end.justify-end .kt-card.bg-primary::after {
            display: none !important;
        }

        /* Other messages - white/gray balloon with rounded corners, no tail */
        #chat_drawer .kt-card.bg-accent\/60 {
            background-color: rgb(255, 255, 255) !important; /* white */
            border: 1px solid rgb(229, 231, 235) !important; /* gray-200 */
            color: rgb(17, 24, 39) !important; /* gray-900 */
            position: relative !important;
            border-radius: 1.125rem !important; /* 18px - rounded corners */
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
        }
        .dark #chat_drawer .kt-card.bg-accent\/60 {
            background-color: rgb(31, 41, 55) !important; /* gray-800 */
            border: 1px solid rgb(55, 65, 81) !important; /* gray-700 */
            color: rgb(243, 244, 246) !important; /* gray-100 */
        }
        /* Remove tail */
        #chat_drawer .flex.items-end:not(.justify-end) .kt-card.bg-accent\/60::before {
            display: none !important;
        }

        /* Larger avatars */
        #chat_drawer .kt-avatar.size-9,
        #chat_drawer img.size-9 {
            width: 3rem !important; /* 48px */
            height: 3rem !important; /* 48px */
        }
        #chat_drawer .kt-avatar-image img.size-9 {
            width: 3rem !important;
            height: 3rem !important;
        }

        /* Online/Offline status indicator - like screenshot (green dot bottom-right) */
        #chat_drawer .kt-avatar {
            position: relative !important;
        }
        #chat_drawer .kt-avatar-indicator {
            position: absolute !important;
            bottom: 0 !important;
            right: 0 !important;
            z-index: 10 !important;
            width: 1rem !important; /* 16px - larger */
            height: 1rem !important; /* 16px - larger */
        }
        #chat_drawer .kt-avatar-status {
            width: 100% !important;
            height: 100% !important;
            border-radius: 50% !important;
            border: 2px solid white !important;
            display: block !important;
            box-sizing: border-box !important;
        }
        .dark #chat_drawer .kt-avatar-status {
            border-color: rgb(17, 24, 39) !important; /* gray-900 */
        }
        #chat_drawer .kt-avatar-status-online {
            background-color: rgb(34, 197, 94) !important; /* green-500 */
        }
        #chat_drawer .kt-avatar-status-offline {
            background-color: rgb(156, 163, 175) !important; /* gray-400 */
        }

        /* Message input container with border and less rounded corners */
        #chat_drawer #chat_messages_view > div.pb-1 {
            margin: 0.5rem 0.75rem !important;
            padding: 0.375rem 0.75rem !important;
            border: 1px solid rgb(209, 213, 219) !important; /* gray-300 */
            border-radius: 0.75rem !important; /* 12px - less rounded */
            background-color: rgb(249, 250, 251) !important; /* gray-50 */
        }
        .dark #chat_drawer #chat_messages_view > div.pb-1 {
            border-color: rgb(55, 65, 81) !important; /* gray-700 */
            background-color: rgb(31, 41, 55) !important; /* gray-800 */
        }

        /* Avatar in input field - now inline */
        #chat_drawer #chat_messages_view #chat_user_avatar {
            width: 2.5rem !important; /* 40px */
            height: 2.5rem !important; /* 40px */
        }

        /* Avatar container in chat list - add border */
        #chat_drawer #chat_list .relative.shrink-0 {
            position: relative !important;
        }
        #chat_drawer #chat_list .w-10.h-10.rounded-full {
            border: 2px solid rgb(156, 163, 175) !important; /* gray-400 */
            box-sizing: border-box !important;
            overflow: hidden !important; /* Clip image to border */
            position: relative !important;
        }
        #chat_drawer #chat_list .w-10.h-10.rounded-full > img {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            z-index: 0 !important;
            border-radius: 50% !important;
            object-fit: cover !important;
        }
        #chat_drawer #chat_list .w-10.h-10.rounded-full > span.text-primary {
            position: relative !important;
            z-index: 1 !important;
        }
        /* Badge positioning - above avatar, not inside */
        #chat_drawer #chat_list .relative.shrink-0 > span.absolute {
            position: absolute !important;
            top: -2px !important;
            right: -2px !important;
            z-index: 10 !important;
            transform: none !important;
        }
        .dark #chat_drawer #chat_list .w-10.h-10.rounded-full {
            border-color: rgb(75, 85, 99) !important; /* gray-600 */
        }

        /* Chat header avatar container - ensure image stays within bounds */
        #chat_drawer #chat_messages_view .bg-accent\/60.size-11 {
            overflow: hidden !important; /* Clip image to container */
            position: relative !important;
        }
        #chat_drawer #chat_messages_view .bg-accent\/60.size-11 > img {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            z-index: 0 !important;
            border-radius: 50% !important;
            object-fit: cover !important;
        }
        #chat_drawer #chat_messages_view .bg-accent\/60.size-11 > span.text-primary {
            position: relative !important;
            z-index: 1 !important;
        }

        /* Better send button styling */
        #chat_drawer #chat_send_button {
            background-color: rgb(0, 122, 255) !important; /* iOS blue */
            color: white !important;
            border: none !important;
            border-radius: 0.5rem !important;
            padding: 0.5rem !important;
            min-width: 2.5rem !important;
            height: 2.5rem !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            transition: background-color 0.2s ease, transform 0.1s ease !important;
            box-shadow: 0 2px 4px rgba(0, 122, 255, 0.3) !important;
        }
        #chat_drawer #chat_send_button:hover {
            background-color: rgb(0, 102, 204) !important; /* darker blue */
            transform: scale(1.05) !important;
        }
        #chat_drawer #chat_send_button:active {
            background-color: rgb(0, 82, 163) !important; /* even darker */
            transform: scale(0.95) !important;
        }
        #chat_drawer #chat_send_button i {
            font-size: 1.125rem !important;
            line-height: 1 !important;
        }
        #chat_drawer #chat_send_button:disabled {
            background-color: rgb(156, 163, 175) !important;
            cursor: not-allowed !important;
            box-shadow: none !important;
        }
        #chat_drawer #chat_message_input {
            border: none !important;
            background-color: transparent !important;
            box-shadow: none !important;
            line-height: 1.4 !important;
            padding-top: 0.625rem !important;
            padding-bottom: 0.625rem !important;
            vertical-align: middle !important;
        }
        #chat_drawer #chat_message_input:focus {
            outline: none !important;
            box-shadow: none !important;
        }

        /* Remove old margin styles from inner div */
        #chat_drawer #chat_messages_view > div.pb-1 > div {
            margin-left: 0 !important;
            margin-right: 0 !important;
        }

        /* Ensure input container uses flex layout with center alignment */
        #chat_drawer #chat_messages_view > div.pb-1 > div {
            display: flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
        }

        /* Chat drawer menu dropdown styles */
        #chat_drawer .kt-menu-item {
            position: relative !important;
        }
        #chat_drawer .kt-menu-dropdown {
            display: none !important;
            position: absolute !important;
            z-index: 100000 !important;
            top: 100% !important;
            right: 0 !important;
            margin-top: 0.5rem !important;
            background-color: var(--kt-body-bg, #ffffff) !important;
            border: 1px solid var(--kt-border-color, rgb(229, 231, 235)) !important;
            border-radius: 0.5rem !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1) !important;
            padding: 0.5rem !important;
            min-width: 175px !important;
            opacity: 0 !important;
            visibility: hidden !important;
            transform: translateY(-10px) !important;
            transition: opacity 0.2s ease, visibility 0.2s ease, transform 0.2s ease !important;
        }
        .dark #chat_drawer .kt-menu-dropdown {
            background-color: var(--kt-body-bg-dark, #1e293b) !important;
            border-color: var(--kt-border-color-dark, rgb(55, 65, 81)) !important;
        }
        /* Show dropdown on hover */
        #chat_drawer .kt-menu-item:hover > .kt-menu-dropdown,
        #chat_drawer .kt-menu-item.show > .kt-menu-dropdown {
            display: flex !important;
            opacity: 1 !important;
            visibility: visible !important;
            transform: translateY(0) !important;
        }
        /* Menu link styles with background */
        #chat_drawer .kt-menu-link {
            padding: 0.5rem 0.75rem !important;
            border-radius: 0.375rem !important;
            transition: background-color 0.2s ease !important;
            background-color: transparent !important;
            display: flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
            width: 100% !important;
            text-align: left !important;
        }
        #chat_drawer .kt-menu-link:hover {
            background-color: rgba(0, 0, 0, 0.05) !important;
        }
        .dark #chat_drawer .kt-menu-link:hover {
            background-color: rgba(255, 255, 255, 0.1) !important;
        }
        #chat_drawer .kt-menu-icon {
            display: flex !important;
            align-items: center !important;
            flex-shrink: 0 !important;
        }
        #chat_drawer .kt-menu-title {
            flex: 1 !important;
        }
    </style>

    <!-- Chat Drawer Backdrop -->
    <div id="chat_drawer_backdrop" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden" data-kt-drawer-dismiss="true" onclick="if(event.target === this) handleDrawerClose();"></div>

    <!--Chat Drawer-->
    <div class="kt-drawer kt-drawer-end card bottom-5 end-5 top-5 hidden w-[450px] max-w-[90%] flex-col rounded-xl border border-border bg-background"
        data-kt-drawer="true" data-kt-drawer-container="body" id="chat_drawer">

        <!-- Chat List View -->
        <div id="chat_list_view" class="flex flex-col h-full">
            <div class="flex items-center justify-between gap-2.5 px-5 py-3.5 text-sm font-semibold text-mono border-b border-border">
                Overzicht Chats
                <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0" data-kt-drawer-dismiss="true" onclick="handleDrawerClose()">
                    <i class="ki-filled ki-cross"></i>
                </button>
            </div>
            <div id="chat_list" class="flex-1 overflow-y-auto">
                <!-- Chat list will be loaded here -->
                <div id="chat_list_empty" class="flex flex-col items-center justify-center h-full text-center p-4" style="display: none;">
                    <p class="text-muted-foreground mb-4">Geen actieve chats</p>
                </div>
            </div>
        </div>

        <!-- Chat Messages View -->
        <div id="chat_messages_view" class="flex flex-col h-full" style="display: none;">
            <!-- Chat Header with Back Button and Close Button -->
            <div class="flex items-center justify-between gap-2.5 text-sm text-mono font-semibold px-5 py-3.5 border-b border-border">
                <div class="flex items-center gap-2.5">
                    <button type="button" class="kt-btn kt-btn-icon kt-btn-sm" onclick="showChatList()" title="Terug naar chat lijst">
                        <i class="ki-filled ki-arrow-left"></i>
                    </button>
                    <span>Chat</span>
                </div>
                <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0" data-kt-drawer-dismiss="true" onclick="handleDrawerClose()">
                    <i class="ki-filled ki-cross"></i>
                </button>
            </div>

            <!-- Company/Contact Info -->
            <div class="border-b border-border py-2.5">
                <div class="flex flex-wrap items-center justify-between gap-2 px-5">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="bg-accent/60 flex size-11 shrink-0 items-center justify-center rounded-full border border-border">
                            <span class="text-primary font-semibold text-sm" id="chat_header_avatar">C</span>
                        </div>
                        <div class="flex flex-col">
                            <a class="hover:text-primary text-sm font-semibold text-mono" href="#" id="chat_header_name">
                                Selecteer een chat
                            </a>
                            <span class="text-xs font-medium italic text-muted-foreground" id="chat_typing_indicator_header" style="display: none;">
                                <!-- Typing indicator will be shown here -->
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <div class="kt-menu" data-kt-menu="true">
                            <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px"
                                data-kt-menu-item-placement="bottom-end" data-kt-menu-item-placement-rtl="bottom-start"
                                data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="hover">
                                <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" type="button">
                                    <i class="ki-filled ki-dots-vertical text-lg"></i>
                                </button>
                                <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]" data-kt-menu-dismiss="true">
                                    <div class="kt-menu-item">
                                        <button type="button" class="kt-menu-link w-full text-left" onclick="endChat()">
                                            <span class="kt-menu-icon">
                                                <i class="ki-filled ki-cross-circle"></i>
                                            </span>
                                            <span class="kt-menu-title">Chat beëindigen</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messages Area -->
            <div class="kt-scrollable-y-auto grow relative" data-kt-scrollable="true" data-kt-scrollable-dependencies="#header"
                data-kt-scrollable-max-height="auto" data-kt-scrollable-offset="230px">
                <div class="flex flex-col gap-5" id="chat_messages" style="padding-top: 0.5rem; padding-bottom: 0;">
                    <!-- Messages will be loaded here -->
                </div>
            </div>
            <!-- Scroll to bottom button -->
            <button id="scroll_to_bottom_btn" onclick="scrollToBottomManually()"
                class="absolute bottom-20 right-4 kt-btn kt-btn-sm kt-btn-icon kt-btn-primary rounded-full shadow-lg z-10"
                style="display: none;"
                title="Scroll naar beneden">
                <i class="ki-filled ki-arrow-down"></i>
            </button>

            <!-- Message Input -->
            <div class="pb-1 pt-0.5 shrink-0">
                <div class="relative grow flex items-center gap-2">
                    <div class="flex-shrink-0 size-[40px] rounded-full overflow-hidden">
                        <img alt="" class="size-[40px] rounded-full object-cover" id="chat_user_avatar" src="{{ auth()->user() && auth()->user()->photo_blob ? route('secure.photo', ['token' => auth()->user()->getPhotoToken()]) : asset('assets/media/avatars/300-2.png') }}">
                    </div>
                    <div class="relative flex-1 flex items-center gap-2">
                        <textarea class="kt-input h-auto bg-transparent px-4 resize-none overflow-hidden flex-1" placeholder="Schrijf een bericht..." id="chat_message_input" rows="1"></textarea>
                        <button class="kt-btn kt-btn-sm flex-shrink-0" type="button" id="chat_send_button" onclick="sendMessage()" title="Verstuur">
                            <i class="ki-filled ki-arrow-right text-lg"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--End of Chat Drawer-->
</div>
