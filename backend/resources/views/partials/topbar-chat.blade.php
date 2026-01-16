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
        }
        #chat_drawer_backdrop.hidden {
            display: none !important;
            visibility: hidden !important;
        }
        #chat_drawer_backdrop:not(.hidden),
        #chat_drawer[data-chat-active="true"] ~ #chat_drawer_backdrop {
            display: block !important;
            visibility: visible !important;
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
            min-height: 53px;
            max-height: 200px;
            overflow-y: auto;
            word-wrap: break-word;
            white-space: pre-wrap;
            padding-right: 70px !important; /* Space for Send button */
        }
        /* Shake animation for icons with unread messages */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-2px) rotate(-1deg); }
            75% { transform: translateX(2px) rotate(1deg); }
        }
        .chat-icon-button.shake,
        .notification-icon-button.shake {
            animation: shake 0.5s ease-in-out;
        }
        .chat-icon-button.has-unread .chat-icon,
        .notification-icon-button.has-unread .notification-icon {
            color: rgb(239 68 68) !important; /* text-red-500 */
        }
    </style>
    <!-- Chat -->
    @php
        $unreadChatCount = 0;
        if (auth()->check() && auth()->user()) {
            $unreadChatCount = \App\Models\Chat::whereHas('messages', function($query) {
                $query->where('sender_type', '!=', get_class(auth()->user()))
                      ->whereNull('read_at');
            })->where('user_id', auth()->id())
              ->where('is_active', true)
              ->count();
        }
    @endphp
    <button class="kt-btn kt-btn-ghost kt-btn-icon hover:bg-primary/10 hover:[&_i]:text-primary size-9 rounded-full relative chat-icon-button {{ $unreadChatCount > 0 ? 'has-unread' : '' }}"
        data-kt-drawer-toggle="#chat_drawer" onclick="loadActiveChats()" id="backend_chat_toggle">
        <i class="ki-filled {{ $unreadChatCount > 0 ? 'ki-messages text-red-500' : 'ki-messages' }} text-lg chat-icon">
        </i>
        @if($unreadChatCount > 0)
        <span class="absolute top-0 end-0 flex size-4 items-center justify-center rounded-full bg-danger text-[10px] font-semibold leading-none text-white chat-badge">
            {{ $unreadChatCount > 9 ? '9+' : $unreadChatCount }}
        </span>
        @endif
    </button>
    <!-- Chat Drawer Backdrop -->
    <div id="chat_drawer_backdrop" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden" data-kt-drawer-dismiss="true"></div>
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
                <!-- Default message when no chats -->
                <div id="chat_list_empty" class="flex flex-col items-center justify-center h-full text-center p-4" style="display: none;">
                    <p class="text-muted-foreground mb-4">Start een chat door een kandidaat te kiezen</p>
                    <select id="chat_candidate_select" class="kt-input w-full max-w-xs" onchange="handleCandidateSelect()">
                        <option value="">Selecteer een kandidaat...</option>
                        <!-- Candidates will be loaded here -->
                    </select>
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

            <!-- Candidate Info -->
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
                                data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click|lg:hover">
                                <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost">
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
            <div class="pb-1.5 pt-0 shrink-0" style="padding-top: 3px;">
                <div class="relative grow" style="margin-left: 5px !important; margin-right: 5px !important;">
                    <div class="absolute start-0 top-2/4 ms-2.5 size-[30px] -translate-y-2/4 rounded-full overflow-hidden">
                        <img alt="" class="size-[30px] rounded-full object-cover" id="chat_user_avatar" src="{{ auth()->user() && auth()->user()->photo_blob ? route('user.photo', auth()->user()->id) : asset('assets/media/avatars/300-2.png') }}">
                    </div>
                    <textarea class="kt-input h-auto bg-transparent py-4 ps-12 pe-20 resize-none overflow-hidden" placeholder="Schrijf een bericht..." id="chat_message_input" rows="1"></textarea>
                    <div class="absolute end-3 top-1/2 flex -translate-y-1/2 items-center gap-2.5">
                        <button class="kt-btn kt-btn-mono kt-btn-sm" type="button" id="chat_send_button" onclick="sendMessage()">
                            Verstuur
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--End of Chat Drawer-->
    <!-- End of Chat -->
</div>
