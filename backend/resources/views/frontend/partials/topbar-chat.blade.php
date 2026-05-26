<div class="flex items-center gap-1.5">
    <!-- Chat -->
    @php
        $unreadChatCount = 0;
        if (auth()->check() && auth()->user()) {
            // Count unread messages in active chats for this user
            $unreadChatCount = \App\Models\Chat::whereHas('messages', function($query) {
                $query->where('sender_type', '!=', get_class(auth()->user()))
                      ->whereNull('read_at');
            })->where('user_id', auth()->id())
              ->where('is_active', true)
              ->count();
        }
    @endphp
    <button class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors relative chat-icon-button" 
            data-kt-drawer-toggle="#chat_drawer" 
            onclick="if(typeof loadActiveChats === 'function') loadActiveChats(false, true);" 
            id="frontend_chat_toggle">
        <i class="ki-filled {{ $unreadChatCount > 0 ? 'ki-messages text-red-500' : 'ki-messages text-gray-600 dark:text-gray-300' }} text-lg chat-icon">
        </i>
        @if($unreadChatCount > 0)
        <span class="absolute top-0 end-0 flex size-4 items-center justify-center rounded-full bg-danger text-[10px] font-semibold leading-none text-white chat-badge">
            {{ $unreadChatCount > 9 ? '9+' : $unreadChatCount }}
        </span>
        @endif
    </button>
    <!--Chat Drawer-->
    @include('frontend.partials.chat-drawer')
    <!--End of Chat Drawer-->
    <!-- End of Chat -->
</div>

