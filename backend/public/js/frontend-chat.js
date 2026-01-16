// Frontend chat functionality (candidate perspective)
console.log('📦 frontend-chat.js loaded!');

let activeChats = [];
let currentChatId = null;
let typingInterval = null;
let messagesInterval = null;
let readStatusInterval = null;
let chatListUpdateInterval = null;
let currentChat = null;
let isOpeningChat = false;
let isUpdatingStyles = false;
let isDrawerExplicitlyClosed = false;
let pendingOptimisticMessages = new Map();
let isWaitingForMessage = false;
let userHasScrolled = false;
let isAutoScrolling = false;
let isLoadingChats = false;
let lastDrawerState = null;

// Get CSRF token
function getCsrfToken() {
    const token = document.querySelector('meta[name="csrf-token"]');
    return token ? token.getAttribute('content') : '';
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Load active chats (frontend - candidate perspective)
window.loadActiveChats = function(showListView = false, openDrawer = false) {
    // Prevent infinite loops
    if (isLoadingChats) {
        console.log('⏭️ loadActiveChats already in progress, skipping...');
        return Promise.resolve(activeChats);
    }
    
    isLoadingChats = true;
    console.log('📋 loadActiveChats called (frontend), showListView:', showListView, 'openDrawer:', openDrawer);
    
    const backdrop = document.getElementById('chat_drawer_backdrop');
    const drawer = document.getElementById('chat_drawer');
    
    // Only open drawer if explicitly requested (e.g., from button click)
    if (openDrawer && drawer) {
        // Ensure drawer is visible
        isDrawerExplicitlyClosed = false;
        drawer.removeAttribute('data-drawer-closed');
        drawer.classList.remove('hidden');
        drawer.setAttribute('data-chat-active', 'true');
        
        drawer.style.setProperty('display', 'flex', 'important');
        drawer.style.setProperty('visibility', 'visible', 'important');
        drawer.style.setProperty('opacity', '1', 'important');
        drawer.style.setProperty('z-index', '99999', 'important');
    }
    
    // Only show backdrop if drawer is already open or if we're opening it
    if (backdrop && (openDrawer || (drawer && !drawer.classList.contains('hidden') && drawer.style.display !== 'none'))) {
        backdrop.classList.remove('hidden');
        backdrop.style.setProperty('display', 'block', 'important');
        backdrop.style.setProperty('visibility', 'visible', 'important');
        backdrop.style.setProperty('z-index', '99998', 'important');
        backdrop.style.setProperty('background-color', 'rgba(0, 0, 0, 0.5)', 'important');
        backdrop.style.setProperty('backdrop-filter', 'blur(4px)', 'important');
    }
    
    return fetch('/chat/active', {
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
        },
        cache: 'no-cache'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(chats => {
        console.log('✅ Active chats loaded from server:', chats);
        console.log('📊 Server returned', chats.length, 'chats');
        
        if (!Array.isArray(chats)) {
            console.error('❌ Server response is not an array:', chats);
            return activeChats;
        }
        
        if (chats.error) {
            console.error('❌ Server returned error:', chats.error);
            return activeChats;
        }
        
        const serverChatIds = chats.map(c => c.id);
        const previousChatIds = activeChats.map(c => c.id);
        
        console.log('📊 Previous chats:', previousChatIds);
        console.log('📊 Server chats:', serverChatIds);
        
        // Preserve unread counts from previous activeChats if they exist
        // This prevents the badge from disappearing when the list refreshes
        chats.forEach(chat => {
            const previousChat = activeChats.find(c => c.id === chat.id);
            if (previousChat && previousChat.unread_count !== undefined) {
                // Only preserve unread count if it's higher than the server value
                // This ensures new unread messages are still shown
                if (previousChat.unread_count > (chat.unread_count || 0)) {
                    chat.unread_count = previousChat.unread_count;
                    console.log('📌 Preserved unread count for chat', chat.id, ':', previousChat.unread_count);
                }
            }
        });
        
        const currentChatInArray = activeChats.find(c => c.id === currentChatId);
        if (currentChatId && currentChatInArray && !serverChatIds.includes(currentChatId)) {
            chats.push(currentChatInArray);
            console.log('📌 Preserved current chat that is not in server response');
        }
        
        const oldLength = activeChats.length;
        activeChats = [...chats];
        
        console.log(`🔄 Replaced ${oldLength} chats with ${activeChats.length} chats from server`);
        console.log('📋 New activeChats array:', activeChats.map(c => ({ id: c.id, company: c.company?.name || 'Unknown', user: c.user?.name || 'Unknown' })));
        
        const chatListView = document.getElementById('chat_list_view');
        const chatMessagesView = document.getElementById('chat_messages_view');
        
        if (!currentChatId || showListView) {
            if (chatListView) {
                chatListView.style.setProperty('display', 'flex', 'important');
                chatListView.style.setProperty('visibility', 'visible', 'important');
                chatListView.style.setProperty('opacity', '1', 'important');
                console.log('📋 Chat list view shown via loadActiveChats.');
            }
            if (chatMessagesView) {
                chatMessagesView.style.setProperty('display', 'none', 'important');
                chatMessagesView.style.setProperty('visibility', 'hidden', 'important');
                chatMessagesView.style.setProperty('opacity', '0', 'important');
                console.log('📋 Chat messages view hidden via loadActiveChats.');
            }
            if (showListView) {
                currentChatId = null;
                currentChat = null;
                console.log('📋 Switched to chat list view, currentChatId cleared.');
            }
        }
        
        renderChatList(activeChats);
        isLoadingChats = false;
        return activeChats;
    })
    .catch(error => {
        console.error('❌ Error loading chats:', error);
        isLoadingChats = false;
        return activeChats;
    });
};

// Render chat list (frontend - show company name and contact person)
function renderChatList(chats) {
    console.log('📋 renderChatList called with chats:', chats);
    const chatList = document.getElementById('chat_list');
    const emptyState = document.getElementById('chat_list_empty');
    const chatListView = document.getElementById('chat_list_view');
    
    if (!chatList) {
        console.error('❌ chat_list element not found!');
        return;
    }
    
    if (chatListView && !currentChatId) {
        chatListView.style.setProperty('display', 'flex', 'important');
        chatListView.style.setProperty('visibility', 'visible', 'important');
        chatListView.style.setProperty('opacity', '1', 'important');
        console.log('✅ chat_list_view is now visible');
    } else if (chatListView && currentChatId) {
        chatListView.style.setProperty('display', 'none', 'important');
        chatListView.style.setProperty('visibility', 'hidden', 'important');
        chatListView.style.setProperty('opacity', '0', 'important');
    } else {
        console.error('❌ chat_list_view element not found!');
    }

    if (emptyState) {
        emptyState.style.display = chats.length === 0 ? 'flex' : 'none';
    }

    const existingChatItems = chatList.querySelectorAll('.chat-item');
    const existingCount = existingChatItems.length;
    existingChatItems.forEach(item => item.remove());
    if (existingCount > 0) {
        console.log(`🗑️ Removed ${existingCount} existing chat item(s) from DOM`);
    }
    
    if (chats.length === 0) {
        console.log('📋 No chats to display, showing empty state');
        return;
    }

    console.log('📋 Rendering', chats.length, 'chats');
    
    // Sort chats by latest message timestamp (descending)
    const sortedChats = [...chats].sort((a, b) => {
        const timeA = a.last_message ? new Date(a.last_message.created_at).getTime() : (a.updated_at ? new Date(a.updated_at).getTime() : 0);
        const timeB = b.last_message ? new Date(b.last_message.created_at).getTime() : (b.updated_at ? new Date(b.updated_at).getTime() : 0);
        return timeB - timeA;
    });
    
    console.log('📋 Sorted chats:', sortedChats.map(c => ({ id: c.id, latest: c.last_message?.created_at || c.updated_at || 'none' })));
    
    // Render all chats (frontend perspective: show company name and contact person)
    sortedChats.forEach((chat, index) => {
        const chatItem = document.createElement('div');
        chatItem.className = `chat-item p-3 border-b border-border cursor-pointer hover:bg-muted/50 ${chat.id === currentChatId ? 'bg-muted/30' : ''}`;
        chatItem.onclick = () => selectChat(chat.id);
        
        // Frontend: show company name and contact person name
        const companyName = chat.company && chat.company.name ? chat.company.name : 'Onbekend bedrijf';
        const contactPersonName = chat.user && chat.user.name ? chat.user.name : 'Onbekend contact';
        const displayName = `${companyName} - ${contactPersonName}`;
        const lastMessage = chat.last_message ? chat.last_message.message : '';
        const lastMessageTime = chat.last_message && chat.last_message.time ? chat.last_message.time : '';
        
        const userAvatar = chat.user && chat.user.avatar ? chat.user.avatar : null;
        const unreadCount = chat.unread_count || 0;
        
        chatItem.innerHTML = `
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center shrink-0 relative">
                    ${userAvatar ? 
                        `<img src="${escapeHtml(userAvatar)}" alt="${escapeHtml(contactPersonName)}" class="w-10 h-10 rounded-full object-cover" onerror="this.parentElement.innerHTML='<span class=\\'text-primary font-semibold\\'>${companyName.charAt(0).toUpperCase()}</span>'">` :
                        `<span class="text-primary font-semibold">${companyName.charAt(0).toUpperCase()}</span>`
                    }
                    ${unreadCount > 0 ? `
                        <span class="absolute top-0 end-0 flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-semibold leading-none z-10 transform translate-x-1/2 -translate-y-1/2">
                            ${unreadCount > 9 ? '9+' : unreadCount}
                        </span>
                    ` : ''}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="font-semibold text-sm">${escapeHtml(displayName)}</div>
                    ${lastMessage ? `<div class="text-xs text-muted-foreground truncate">${escapeHtml(lastMessage)}</div>` : '<div class="text-xs text-muted-foreground">Geen berichten</div>'}
                </div>
                <div class="flex flex-col items-end gap-1 shrink-0">
                    ${lastMessageTime ? `<div class="text-xs text-muted-foreground">${lastMessageTime}</div>` : ''}
                </div>
            </div>
        `;
        chatList.appendChild(chatItem);
        console.log(`✅ Added chat item ${index + 1} for chat ${chat.id} (${displayName})`);
    });
    
    const renderedItems = chatList.querySelectorAll('.chat-item');
    console.log('✅ Chat list rendered with', chats.length, 'items. DOM contains', renderedItems.length, 'items');
}

// Select a chat
window.selectChat = function(chatId) {
    if (!chatId) {
        console.error('❌ selectChat called without chatId');
        return;
    }
    
    console.log('🔵 selectChat called with chatId:', chatId);
    currentChatId = chatId;
    let chat = activeChats.find(c => c.id === chatId);
    console.log('🔵 Found chat in activeChats:', chat ? 'Yes' : 'No');
    
    const drawer = document.getElementById('chat_drawer');
    const backdrop = document.getElementById('chat_drawer_backdrop');
    
    if (drawer) {
        console.log('🔵 Setting drawer attributes...');
        isDrawerExplicitlyClosed = false;
        drawer.removeAttribute('data-drawer-closed');
        drawer.classList.remove('hidden');
        drawer.setAttribute('data-chat-active', 'true');
        
        isUpdatingStyles = true;
        try {
            drawer.style.setProperty('display', 'flex', 'important');
            drawer.style.setProperty('visibility', 'visible', 'important');
            drawer.style.setProperty('opacity', '1', 'important');
            drawer.style.setProperty('z-index', '99999', 'important');
            drawer.style.setProperty('transform', 'translateX(0)', 'important');
            drawer.style.setProperty('right', '1.25rem', 'important');
            drawer.style.setProperty('left', 'unset', 'important');
            drawer.style.setProperty('transition', 'none', 'important');
            drawer.style.setProperty('animation', 'none', 'important');
        } finally {
            setTimeout(() => {
                isUpdatingStyles = false;
            }, 10);
        }
        
        console.log('🔵 Drawer should be visible now, computed display:', window.getComputedStyle(drawer).display);
    }
    
    if (backdrop) {
        backdrop.classList.remove('hidden');
        backdrop.style.setProperty('display', 'block', 'important');
        backdrop.style.setProperty('visibility', 'visible', 'important');
        backdrop.style.setProperty('z-index', '99998', 'important');
        backdrop.style.setProperty('background-color', 'rgba(0, 0, 0, 0.5)', 'important');
        backdrop.style.setProperty('backdrop-filter', 'blur(4px)', 'important');
    }
    
    if (!chat) {
        loadActiveChats().then(() => {
            chat = activeChats.find(c => c.id === chatId);
            if (chat) {
                currentChat = chat;
                updateChatViews(chat);
                loadChatMessages(chatId);
                startChatPolling(chatId);
            }
        });
        return;
    }
    
    currentChat = chat;
    
    const messagesContainer = document.getElementById('chat_messages');
    if (messagesContainer) {
        console.log('🧹 Clearing messages container before switching to chat:', chatId);
        messagesContainer.innerHTML = '';
    }
    
    updateChatViews(chat);
    loadChatMessages(chatId);
    startChatPolling(chatId);
};

// Update chat views (frontend - show company and contact person)
function updateChatViews(chat) {
    console.log('🟢 updateChatViews called with chat:', chat);
    
    const chatListView = document.getElementById('chat_list_view');
    const chatMessagesView = document.getElementById('chat_messages_view');
    const chatHeaderName = document.getElementById('chat_header_name');
    const chatHeaderAvatar = document.getElementById('chat_header_avatar');
    const chatUserAvatar = document.getElementById('chat_user_avatar');
    const drawer = document.getElementById('chat_drawer');
    const backdrop = document.getElementById('chat_drawer_backdrop');
    
    if (drawer) {
        isDrawerExplicitlyClosed = false;
        drawer.removeAttribute('data-drawer-closed');
        drawer.classList.remove('hidden');
        
        isUpdatingStyles = true;
        try {
            drawer.style.setProperty('display', 'flex', 'important');
            drawer.style.setProperty('visibility', 'visible', 'important');
            drawer.style.setProperty('opacity', '1', 'important');
            drawer.style.setProperty('z-index', '99999', 'important');
            drawer.style.setProperty('transform', 'translateX(0)', 'important');
            drawer.style.setProperty('right', '1.25rem', 'important');
            drawer.style.setProperty('left', 'unset', 'important');
            drawer.style.setProperty('transition', 'none', 'important');
            drawer.style.setProperty('animation', 'none', 'important');
            drawer.setAttribute('data-chat-active', 'true');
        } finally {
            setTimeout(() => {
                isUpdatingStyles = false;
            }, 10);
        }
        
        console.log('🟢 Drawer made visible in updateChatViews, computed display:', window.getComputedStyle(drawer).display);
    }
    
    if (backdrop) {
        backdrop.classList.remove('hidden');
        backdrop.style.setProperty('display', 'block', 'important');
        backdrop.style.setProperty('visibility', 'visible', 'important');
        backdrop.style.setProperty('z-index', '99998', 'important');
        backdrop.style.setProperty('background-color', 'rgba(0, 0, 0, 0.5)', 'important');
        backdrop.style.setProperty('backdrop-filter', 'blur(4px)', 'important');
    }
    
    if (chatListView) {
        chatListView.style.setProperty('display', 'none', 'important');
        chatListView.style.setProperty('visibility', 'hidden', 'important');
        chatListView.style.setProperty('opacity', '0', 'important');
        console.log('🟢 Chat list view hidden');
    }
    
    if (chatMessagesView) {
        chatMessagesView.style.setProperty('display', 'flex', 'important');
        chatMessagesView.style.setProperty('visibility', 'visible', 'important');
        chatMessagesView.style.setProperty('opacity', '1', 'important');
        chatMessagesView.classList.remove('hidden');
        console.log('🟢 Chat messages view shown');
    }
    
    if (chat) {
        // Frontend: show company name and contact person
        const companyName = chat.company && chat.company.name ? chat.company.name : 'Onbekend bedrijf';
        const contactPersonName = chat.user && chat.user.name ? chat.user.name : 'Onbekend contact';
        const displayName = `${companyName} - ${contactPersonName}`;
        
        if (chatHeaderName) {
            chatHeaderName.textContent = displayName;
        }
        if (chatHeaderAvatar) {
            chatHeaderAvatar.textContent = companyName.charAt(0).toUpperCase();
        }
        if (chatUserAvatar) {
            // Use candidate's own avatar from chat data or from user dropdown
            let avatarUrl = '/assets/media/avatars/300-5.png'; // Default
            
            // First try to get from chat data (candidate avatar)
            if (chat && chat.candidate && chat.candidate.avatar) {
                avatarUrl = chat.candidate.avatar;
            } 
            // Otherwise try to get from user dropdown in header
            else {
                const candidateAvatar = document.querySelector('[data-kt-dropdown-toggle="true"] img');
                if (candidateAvatar && candidateAvatar.src) {
                    avatarUrl = candidateAvatar.src;
                } 
                // Or use the initial src from the template
                else if (chatUserAvatar.getAttribute('src')) {
                    avatarUrl = chatUserAvatar.getAttribute('src');
                }
            }
            
            chatUserAvatar.src = avatarUrl;
            chatUserAvatar.onerror = function() {
                // Fallback to default if image fails to load
                this.src = '/assets/media/avatars/300-5.png';
            };
        }
    }
    
    setTimeout(() => {
        initializeChatInput();
        setupScrollListener();
        
        const chatInput = document.getElementById('chat_message_input');
        if (chatInput) {
            chatInput.focus();
        }
    }, 100);
}

// Load chat messages (frontend route)
function loadChatMessages(chatId) {
    console.log('📥 loadChatMessages called with chatId:', chatId);
    console.log('📥 currentChatId:', currentChatId);
    console.log('📥 activeChats:', activeChats.map(c => ({ id: c.id, company: c.company?.name })));
    
    if (!chatId) {
        console.error('❌ loadChatMessages: No chatId provided!');
        return Promise.reject(new Error('No chatId provided'));
    }
    
    if (currentChatId !== chatId) {
        const messagesContainer = document.getElementById('chat_messages');
        if (messagesContainer) {
            console.log('🧹 Clearing messages container for new chat');
            messagesContainer.innerHTML = '';
        }
    }
    
    const url = `/chat/${chatId}/messages`;
    console.log('📥 Fetching messages from:', url);
    console.log('📥 CSRF Token:', getCsrfToken() ? 'Present' : 'Missing');
    
    return fetch(url, {
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
        }
    })
    .then(response => {
        console.log('📥 Response status:', response.status, response.statusText);
        if (!response.ok) {
            console.error('❌ HTTP error! status:', response.status, 'statusText:', response.statusText);
            return response.text().then(text => {
                console.error('❌ Response body:', text);
                throw new Error(`HTTP error! status: ${response.status}, body: ${text.substring(0, 200)}`);
            });
        }
        return response.json();
    })
    .then(messages => {
        console.log('✅ Messages loaded successfully:', messages);
        console.log('✅ Messages count:', Array.isArray(messages) ? messages.length : 'Not an array');
        if (Array.isArray(messages)) {
            if (currentChatId === chatId) {
                console.log('✅ Rendering messages for chat:', chatId);
                renderMessages(messages, chatId);
                setTimeout(() => {
                    scrollToBottom();
                }, 100);
                
                // Update unread count in activeChats array after messages are marked as read
                const chatIndex = activeChats.findIndex(c => c.id === chatId);
                if (chatIndex !== -1) {
                    // Messages have been marked as read, so unread count should be 0
                    activeChats[chatIndex].unread_count = 0;
                    console.log('🔄 Updated unread count for chat', chatId, 'to 0');
                }
            } else {
                console.log('⏭️ Skipping render - chat changed from', chatId, 'to', currentChatId);
            }
        } else {
            console.error('❌ Messages is not an array:', messages);
            console.error('❌ Messages type:', typeof messages);
        }
        return messages;
    })
    .catch(error => {
        console.error('❌ Error loading messages:', error);
        console.error('❌ Error stack:', error.stack);
        throw error;
    });
}

// Send message (frontend route)
window.sendMessage = function() {
    const input = document.getElementById('chat_message_input');
    if (!input || !input.value.trim() || !currentChatId) {
        return;
    }
    
    const messageText = input.value.trim();
    input.value = '';
    
    // Reset input height to minimum height (38px) and ensure no scrollbars
    // Use requestAnimationFrame to ensure DOM update is complete
    requestAnimationFrame(() => {
        input.style.height = '38px';
        input.style.overflowY = 'hidden';
        input.style.overflowX = 'hidden';
        // Force a reflow to ensure the height is applied
        void input.offsetHeight;
    });
    
    // Optimistic UI update
    const optimisticId = 'optimistic-' + Date.now();
    addMessageToUI({
        id: optimisticId,
        message: messageText,
        sender_id: null,
        sender_type: 'App\\Models\\Candidate',
        sender_name: 'Jij',
        sender_avatar: document.getElementById('chat_user_avatar')?.src || '/assets/media/avatars/300-5.png',
        is_own: true,
        read_at: null,
        is_read: false,
        created_at: new Date().toISOString(),
        time: new Date().toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' }),
    }, currentChatId, true);
    
    fetch(`/chat/${currentChatId}/message`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify({ message: messageText })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.message) {
            // Remove optimistic message and add real one
            const optimisticMsg = document.querySelector(`[data-optimistic-id="${optimisticId}"]`);
            if (optimisticMsg) {
                optimisticMsg.remove();
            }
            addMessageToUI(data.message, currentChatId, false);
            scrollToBottom();
            loadActiveChats(false, false); // Refresh chat list without opening drawer
            
            // Check read status after a short delay (to allow backend to process)
            setTimeout(() => {
                if (currentChatId) {
                    updateReadStatus(currentChatId);
                }
            }, 500);
        }
    })
    .catch(error => {
        console.error('❌ Error sending message:', error);
        // Remove optimistic message on error
        const optimisticMsg = document.querySelector(`[data-optimistic-id="${optimisticId}"]`);
        if (optimisticMsg) {
            optimisticMsg.remove();
        }
    });
};

// Render messages (exact backend style)
function renderMessages(messages, chatId) {
    console.log('🎨 renderMessages called with:', { messagesCount: messages.length, chatId });
    const messagesContainer = document.getElementById('chat_messages');
    if (!messagesContainer) {
        console.error('❌ chat_messages container not found!');
        return;
    }
    
    // Get all existing message elements
    const allMessageElements = messagesContainer.querySelectorAll('.flex.items-end');
    const existingMessageIds = new Set();
    allMessageElements.forEach(el => {
        const msgId = el.getAttribute('data-message-id');
        if (msgId && msgId !== 'optimistic') {
            existingMessageIds.add(msgId);
        }
    });
    
    // Find new messages that don't exist yet
    const newMessages = messages.filter(msg => {
        return msg.id && !existingMessageIds.has(String(msg.id));
    });
    
    console.log('🎨 Existing messages:', existingMessageIds.size, 'New messages:', newMessages.length, 'Total messages:', messages.length);
    
    // If no new messages, just ensure empty state is correct
    if (newMessages.length === 0) {
        const hasAnyMessages = messages.length > 0 || messagesContainer.querySelectorAll('.flex.items-end').length > 0;
        let emptyMessage = messagesContainer.querySelector('.chat-empty-message');
        
        if (!hasAnyMessages) {
            if (!emptyMessage) {
                emptyMessage = document.createElement('div');
                emptyMessage.className = 'chat-empty-message text-center text-muted-foreground p-4';
                emptyMessage.textContent = 'Geen berichten';
                messagesContainer.appendChild(emptyMessage);
            }
        } else {
            if (emptyMessage) {
                emptyMessage.remove();
            }
        }
        
        scrollToBottom();
        return;
    }
    
    // Render new messages (exact backend HTML structure)
    const newMessagesHTML = newMessages.map(msg => {
        const isOwn = msg.is_own;
        
        if (isOwn) {
            // Own messages - right aligned with avatar on right (balloon style)
            const candidateAvatar = document.getElementById('chat_user_avatar');
            const userAvatar = candidateAvatar && candidateAvatar.src ? candidateAvatar.src : (msg.sender_avatar || '/assets/media/avatars/300-5.png');
            const messageTimestamp = msg.created_at 
                ? new Date(msg.created_at).toISOString()
                : new Date().toISOString();
            const isOnline = true; // TODO: Get actual online status
            
            return `
                <div class="flex items-end justify-end gap-3.5 px-5" data-optimistic="false" data-message-id="${msg.id || ''}" data-message-text="${escapeHtml(msg.message)}" data-timestamp="${messageTimestamp}" data-chat-id="${chatId || ''}">
                    <div class="flex flex-col gap-1.5">
                        <div class="kt-card bg-primary rounded-be-none flex flex-col gap-2.5 p-3 shadow-none relative">
                            <p class="text-2sm text-primary-foreground font-medium">${escapeHtml(msg.message)}</p>
                        </div>
                        <div class="relative flex items-center justify-end gap-2">
                            <span class="text-xs font-medium text-secondary-foreground">${msg.time}</span>
                            <i class="ki-filled ki-double-check text-lg ${msg.is_read ? 'text-green-500' : 'text-gray-400'}"></i>
                        </div>
                    </div>
                    <div class="relative shrink-0">
                        <div class="kt-avatar size-9">
                            <div class="kt-avatar-image">
                                <img alt="${msg.sender_name || 'You'}" class="size-9 rounded-full object-cover" src="${escapeHtml(userAvatar)}" onerror="this.src='/assets/media/avatars/300-5.png'" />
                            </div>
                            <div class="kt-avatar-indicator -bottom-2 -end-2">
                                <div class="kt-avatar-status ${isOnline ? 'kt-avatar-status-online' : 'kt-avatar-status-offline'}"></div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else {
            // Other messages - left aligned with avatar on left (gray card with border)
            const avatarUrl = msg.sender_avatar || '/assets/media/avatars/300-2.png';
            const messageTimestamp = msg.created_at 
                ? new Date(msg.created_at).toISOString()
                : new Date().toISOString();
            // Get online status from message data, default to false if not provided
            const isOnline = msg.user && msg.user.is_online !== undefined ? msg.user.is_online : false;
            
            return `
                <div class="flex items-end gap-3.5 px-5" data-optimistic="false" data-message-id="${msg.id || ''}" data-message-text="${escapeHtml(msg.message)}" data-timestamp="${messageTimestamp}" data-chat-id="${chatId || ''}">
                    <div class="relative shrink-0">
                        <div class="kt-avatar size-9">
                            <div class="kt-avatar-image">
                                <img alt="${msg.sender_name || 'Unknown'}" class="size-9 rounded-full object-cover" src="${escapeHtml(avatarUrl)}" onerror="this.src='/assets/media/avatars/300-2.png'" />
                            </div>
                            <div class="kt-avatar-indicator -bottom-2 -end-2">
                                <div class="kt-avatar-status ${isOnline ? 'kt-avatar-status-online' : 'kt-avatar-status-offline'}"></div>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <div class="kt-card bg-accent/60 rounded-bs-none text-2sm flex flex-col gap-2.5 p-3 shadow-none relative">
                            ${escapeHtml(msg.message)}
                        </div>
                        <div class="relative flex items-center gap-2">
                            <span class="text-xs font-medium text-muted-foreground">${msg.time}</span>
                            ${msg.is_read !== undefined ? `<i class="ki-filled ki-double-check text-lg ${msg.is_read ? 'text-green-500' : 'text-gray-400'}"></i>` : ''}
                        </div>
                    </div>
                </div>
            `;
        }
    }).join('');
    
    // Append new messages
    if (newMessagesHTML) {
        messagesContainer.insertAdjacentHTML('beforeend', newMessagesHTML);
    }
    
    // Sort all messages by timestamp
    sortMessagesInDOM();
    scrollToBottom();
    
    // Update empty state
    requestAnimationFrame(() => {
        const allMessageElementsFinal = messagesContainer.querySelectorAll('.flex.items-end');
        const hasAnyMessagesFinal = allMessageElementsFinal.length > 0;
        
        let emptyMessageFinal = messagesContainer.querySelector('.chat-empty-message');
        if (!hasAnyMessagesFinal) {
            if (!emptyMessageFinal) {
                emptyMessageFinal = document.createElement('div');
                emptyMessageFinal.className = 'chat-empty-message text-center text-muted-foreground p-4';
                emptyMessageFinal.textContent = 'Geen berichten';
                messagesContainer.appendChild(emptyMessageFinal);
            }
        } else {
            if (emptyMessageFinal) {
                emptyMessageFinal.remove();
            }
        }
    });
    
    console.log('✅ Messages rendered, count:', messages.length, 'new messages:', newMessages.length);
}

// Add message to UI
function addMessageToUI(message, chatId, isOptimistic) {
    const messagesContainer = document.getElementById('chat_messages');
    if (!messagesContainer) return;
    
    const messageDiv = document.createElement('div');
    messageDiv.setAttribute('data-message-id', message.id || 'optimistic');
    messageDiv.setAttribute('data-optimistic', isOptimistic ? 'true' : 'false');
    messageDiv.setAttribute('data-chat-id', String(chatId));
    if (isOptimistic) {
        messageDiv.setAttribute('data-optimistic-id', message.id);
    }
    
    const isOwn = message.is_own;
    const senderName = message.sender_name || 'Onbekend';
    const senderAvatar = message.sender_avatar || '/assets/media/avatars/300-5.png';
    const messageText = escapeHtml(message.message);
    const time = message.time || new Date(message.created_at).toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' });
    
    // Add timestamp for sorting
    const messageTimestamp = message.created_at 
        ? new Date(message.created_at).toISOString()
        : new Date().toISOString();
    messageDiv.setAttribute('data-timestamp', messageTimestamp);
    messageDiv.setAttribute('data-message-text', messageText);
    
    if (isOwn) {
        // Own messages - right aligned with avatar on right (balloon style)
        messageDiv.className = `flex items-end justify-end gap-3.5 px-5`;
        // Use candidate's own avatar (logged-in user) - prefer from message data, then from input avatar
        const candidateAvatar = document.getElementById('chat_user_avatar');
        const userAvatar = message.sender_avatar || (candidateAvatar && candidateAvatar.src) || '/assets/media/avatars/300-5.png';
        const isOnline = true; // TODO: Get actual online status
        
        messageDiv.innerHTML = `
            <div class="flex flex-col gap-1.5">
                <div class="kt-card bg-primary rounded-be-none flex flex-col gap-2.5 p-3 shadow-none relative">
                    <p class="text-2sm text-primary-foreground font-medium">${messageText}</p>
                </div>
                <div class="relative flex items-center justify-end gap-2">
                    <span class="text-xs font-medium text-secondary-foreground">${time}</span>
                    ${isOptimistic ? 
                        '<i class="ki-filled ki-time text-lg text-gray-400"></i>' : 
                        `<i class="ki-filled ki-double-check text-lg ${message.is_read ? 'text-green-500' : 'text-gray-400'}"></i>`
                    }
                </div>
            </div>
            <div class="relative shrink-0">
                <div class="kt-avatar size-9">
                    <div class="kt-avatar-image">
                        <img alt="${escapeHtml(senderName)}" class="size-9 rounded-full object-cover" src="${escapeHtml(userAvatar)}" onerror="this.src='/assets/media/avatars/300-5.png'" />
                    </div>
                    <div class="kt-avatar-indicator -bottom-2 -end-2">
                        <div class="kt-avatar-status ${isOnline ? 'kt-avatar-status-online' : 'kt-avatar-status-offline'}"></div>
                    </div>
                </div>
            </div>
        `;
    } else {
        // Other messages - left aligned with avatar on left (gray card with border)
        const isOnline = message.user && message.user.is_online !== undefined ? message.user.is_online : false;
        
        messageDiv.className = `flex items-end gap-3.5 px-5`;
        messageDiv.innerHTML = `
            <div class="relative shrink-0">
                <div class="kt-avatar size-9">
                    <div class="kt-avatar-image">
                        <img alt="${escapeHtml(senderName)}" class="size-9 rounded-full object-cover" src="${escapeHtml(senderAvatar)}" onerror="this.src='/assets/media/avatars/300-2.png'" />
                    </div>
                    <div class="kt-avatar-indicator -bottom-2 -end-2">
                        <div class="kt-avatar-status ${isOnline ? 'kt-avatar-status-online' : 'kt-avatar-status-offline'}"></div>
                    </div>
                </div>
            </div>
            <div class="flex flex-col gap-1.5">
                <div class="kt-card bg-accent/60 rounded-bs-none text-2sm flex flex-col gap-2.5 p-3 shadow-none relative">
                    ${messageText}
                </div>
                <div class="relative flex items-center gap-2">
                    <span class="text-xs font-medium text-muted-foreground">${time}</span>
                    ${message.is_read !== undefined ? `<i class="ki-filled ki-double-check text-lg ${message.is_read ? 'text-green-500' : 'text-gray-400'}"></i>` : ''}
                </div>
            </div>
        `;
    }
    
    messagesContainer.appendChild(messageDiv);
    
    // Check for empty state after adding
    requestAnimationFrame(() => {
        const hasMessages = messagesContainer.querySelectorAll('[data-optimistic="false"], [data-optimistic="true"][data-chat-id="' + chatId + '"]').length > 0;
        let emptyMessage = messagesContainer.querySelector('.chat-empty-message');
        
        if (!hasMessages) {
            if (!emptyMessage) {
                emptyMessage = document.createElement('div');
                emptyMessage.className = 'chat-empty-message text-center text-muted-foreground text-sm py-8';
                emptyMessage.textContent = 'Geen berichten';
                messagesContainer.appendChild(emptyMessage);
            }
        } else {
            if (emptyMessage) {
                emptyMessage.remove();
            }
        }
    });
}

// Sort messages in DOM (exact backend style)
function sortMessagesInDOM() {
    const messagesContainer = document.getElementById('chat_messages');
    if (!messagesContainer) return;
    
    const messages = Array.from(messagesContainer.children).filter(child => 
        child.hasAttribute('data-message-id') && !child.classList.contains('chat-empty-message')
    );
    
    messages.sort((a, b) => {
        const timeA = a.getAttribute('data-timestamp') || '';
        const timeB = b.getAttribute('data-timestamp') || '';
        if (!timeA || !timeB) return 0;
        return new Date(timeA).getTime() - new Date(timeB).getTime();
    });
    
    messages.forEach(msg => messagesContainer.appendChild(msg));
}

// Scroll to bottom
function scrollToBottom() {
    const scrollableParent = document.querySelector('#chat_messages_view .kt-scrollable-y-auto');
    if (!scrollableParent) return;
    
    isAutoScrolling = true;
    scrollableParent.scrollTop = scrollableParent.scrollHeight;
    
    setTimeout(() => {
        isAutoScrolling = false;
    }, 100);
}

// Setup scroll listener
function setupScrollListener() {
    const scrollableParent = document.querySelector('#chat_messages_view .kt-scrollable-y-auto');
    if (!scrollableParent) return;
    
    scrollableParent.addEventListener('scroll', () => {
        if (!isAutoScrolling) {
            userHasScrolled = true;
        }
    });
}

// Initialize chat input
function initializeChatInput() {
    const input = document.getElementById('chat_message_input');
    if (!input) return;
    
    // Set initial height
    input.style.height = 'auto';
    input.style.height = Math.min(input.scrollHeight, 200) + 'px';
    input.style.overflowY = 'hidden';
    input.style.overflowX = 'hidden';
    
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
    
    input.addEventListener('input', () => {
        // Reset height to auto to get correct scrollHeight
        input.style.height = 'auto';
        // Set height based on content, but cap at max-height
        const newHeight = Math.min(input.scrollHeight, 200);
        input.style.height = newHeight + 'px';
        // Ensure no scrollbars
        input.style.overflowY = 'hidden';
        input.style.overflowX = 'hidden';
    });
    
    // Also handle on focus to ensure correct height
    input.addEventListener('focus', () => {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 200) + 'px';
        input.style.overflowY = 'hidden';
        input.style.overflowX = 'hidden';
    });
}

// Update read status for messages without full reload
function updateReadStatus(chatId) {
    if (!chatId || currentChatId !== chatId) return;
    
    const url = `/chat/${chatId}/messages`;
    fetch(url, {
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
        }
    })
    .then(response => response.json())
    .then(messages => {
        if (!Array.isArray(messages)) return;
        
        // Update read status for each message in the DOM
        messages.forEach(msg => {
            if (!msg.is_own) return; // Only update own messages
            
            const messageElement = document.querySelector(`[data-message-id="${msg.id}"]`);
            if (!messageElement) return;
            
            // Find the checkmark icon
            const checkmarkIcon = messageElement.querySelector('.ki-double-check');
            if (!checkmarkIcon) return;
            
            // Update icon color based on read status
            if (msg.is_read) {
                checkmarkIcon.classList.remove('text-gray-400');
                checkmarkIcon.classList.add('text-green-500');
            } else {
                checkmarkIcon.classList.remove('text-green-500');
                checkmarkIcon.classList.add('text-gray-400');
            }
        });
    })
    .catch(error => {
        console.error('Error updating read status:', error);
    });
}

// Start chat polling
function startChatPolling(chatId) {
    if (messagesInterval) {
        clearInterval(messagesInterval);
    }
    if (readStatusInterval) {
        clearInterval(readStatusInterval);
    }
    
    // Poll for new messages every 3 seconds
    messagesInterval = setInterval(() => {
        if (currentChatId === chatId) {
            loadChatMessages(chatId);
        }
    }, 3000);
    
    // Poll for read status updates every 1 second (faster for read receipts)
    readStatusInterval = setInterval(() => {
        if (currentChatId === chatId) {
            updateReadStatus(chatId);
        }
    }, 1000);
}

// Show chat list
window.showChatList = function() {
    currentChatId = null;
    currentChat = null;
    loadActiveChats(true);
};

// End chat (frontend)
window.endChat = function() {
    if (!currentChatId) return;

    if (!confirm('Weet je zeker dat je deze chat wilt beëindigen?')) return;

    fetch(`/chat/${currentChatId}/end`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            currentChatId = null;
            currentChat = null;
            const drawer = document.getElementById('chat_drawer');
            if (drawer) {
                drawer.removeAttribute('data-chat-active');
            }
            loadActiveChats();
            const messagesContainer = document.getElementById('chat_messages');
            if (messagesContainer) messagesContainer.innerHTML = '';
            showChatList();
        }
    })
    .catch(error => {
        console.error('Error ending chat:', error);
    });
};

// Handle drawer close
window.handleDrawerClose = function() {
    const drawer = document.getElementById('chat_drawer');
    const backdrop = document.getElementById('chat_drawer_backdrop');
    
    if (drawer) {
        isDrawerExplicitlyClosed = true;
        drawer.setAttribute('data-drawer-closed', 'true');
        drawer.removeAttribute('data-user-opened'); // Remove user-opened flag
        drawer.classList.add('hidden');
        
        const drawerInstance = window.KTDrawer?.getInstance?.(drawer);
        if (drawerInstance) {
            drawerInstance.hide();
        }
        
        setTimeout(() => {
            drawer.style.setProperty('display', 'none', 'important');
            drawer.style.setProperty('visibility', 'hidden', 'important');
            drawer.style.setProperty('opacity', '0', 'important');
            drawer.style.setProperty('transform', 'translateX(100%)', 'important');
            drawer.style.setProperty('right', '-100%', 'important');
        }, 10);
    }
    
    if (backdrop) {
        backdrop.classList.add('hidden');
        backdrop.style.setProperty('display', 'none', 'important');
        backdrop.style.setProperty('visibility', 'hidden', 'important');
        backdrop.style.setProperty('opacity', '0', 'important');
    }
    
    // Restore body scroll
    document.body.style.overflow = '';
    
    if (messagesInterval) {
        clearInterval(messagesInterval);
        messagesInterval = null;
    }
    if (readStatusInterval) {
        clearInterval(readStatusInterval);
        readStatusInterval = null;
    }
    
    if (typingInterval) {
        clearInterval(typingInterval);
        typingInterval = null;
    }
};

// Check if user is authenticated
function isUserAuthenticated() {
    const authMeta = document.querySelector('meta[name="auth-check"]');
    return authMeta && authMeta.getAttribute('content') === 'true';
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if user is authenticated
    if (!isUserAuthenticated()) {
        console.log('⏭️ Frontend chat skipped - user not authenticated');
        return;
    }
    
    console.log('📦 Frontend chat initialized');
    
    // Show backdrop when drawer is opened via KT Drawer
    const drawer = document.getElementById('chat_drawer');
    const backdrop = document.getElementById('chat_drawer_backdrop');
    
    if (drawer && backdrop) {
        // Watch for drawer opening/closing
        const observer = new MutationObserver(() => {
            // Skip if we're currently loading chats to prevent infinite loops
            if (isLoadingChats) {
                return;
            }
            
            const isOpen = drawer.classList.contains('open') || 
                         (!drawer.classList.contains('hidden') && 
                          drawer.style.display !== 'none' && 
                          drawer.style.display !== '' &&
                          drawer.getAttribute('data-drawer-closed') !== 'true');
            
            // Only react if the state actually changed
            if (lastDrawerState === isOpen) {
                return;
            }
            
            lastDrawerState = isOpen;
            
            if (isOpen && !isDrawerExplicitlyClosed && isUserAuthenticated()) {
                // Drawer is open (user clicked the button), show backdrop and load chats
                backdrop.classList.remove('hidden');
                backdrop.style.setProperty('display', 'block', 'important');
                backdrop.style.setProperty('visibility', 'visible', 'important');
                backdrop.style.setProperty('z-index', '99998', 'important');
                backdrop.style.setProperty('background-color', 'rgba(0, 0, 0, 0.5)', 'important');
                backdrop.style.setProperty('backdrop-filter', 'blur(4px)', 'important');
                
                // Only load chats if not already loading and drawer was opened by user
                // Don't open drawer automatically, just load chats if drawer is already open
                if (!isLoadingChats) {
                    loadActiveChats(false, false); // Don't open drawer, just load chats
                }
            } else {
                // Drawer is closed, hide backdrop
                backdrop.classList.add('hidden');
                backdrop.style.setProperty('display', 'none', 'important');
                backdrop.style.setProperty('visibility', 'hidden', 'important');
                backdrop.style.setProperty('opacity', '0', 'important');
            }
        });
        
        observer.observe(drawer, {
            attributes: true,
            attributeFilter: ['class', 'style', 'data-drawer-closed', 'data-chat-active'],
            childList: false,
            subtree: false
        });
        
        // Initial check - ensure drawer is closed on page load
        setTimeout(() => {
            if (!isUserAuthenticated()) {
                // Ensure drawer is closed if user is not authenticated
                if (drawer) {
                    drawer.classList.add('hidden');
                    drawer.style.setProperty('display', 'none', 'important');
                    drawer.setAttribute('data-drawer-closed', 'true');
                }
                if (backdrop) {
                    backdrop.classList.add('hidden');
                    backdrop.style.setProperty('display', 'none', 'important');
                }
                return;
            }
            
            // Always ensure drawer is closed on initial page load
            // Only open when user explicitly clicks the chat button
            if (drawer) {
                drawer.classList.add('hidden');
                drawer.style.setProperty('display', 'none', 'important');
                drawer.setAttribute('data-drawer-closed', 'true');
                drawer.removeAttribute('data-user-opened'); // Remove user-opened flag
                isDrawerExplicitlyClosed = true;
            }
            if (backdrop) {
                backdrop.classList.add('hidden');
                backdrop.style.setProperty('display', 'none', 'important');
                backdrop.style.setProperty('visibility', 'hidden', 'important');
                backdrop.style.setProperty('opacity', '0', 'important');
            }
        }, 200);
    }
    
    // Load active chats when drawer is opened via button click
    const chatToggle = document.getElementById('frontend_chat_toggle');
    if (chatToggle) {
        chatToggle.addEventListener('click', (e) => {
            // Only proceed if user is authenticated
            if (!isUserAuthenticated()) {
                console.log('⏭️ Chat toggle clicked but user not authenticated');
                return;
            }
            
            // Prevent default drawer toggle behavior if it exists
            e.stopPropagation();
            
            setTimeout(() => {
                // Reset the closed flag - allow drawer to open again
                isDrawerExplicitlyClosed = false;
                
                // Ensure drawer is visible (user explicitly clicked the button)
                if (drawer) {
                    drawer.removeAttribute('data-drawer-closed');
                    drawer.classList.remove('hidden');
                    drawer.setAttribute('data-chat-active', 'true');
                    drawer.setAttribute('data-user-opened', 'true'); // Mark as user-opened
                    
                    drawer.style.setProperty('display', 'flex', 'important');
                    drawer.style.setProperty('visibility', 'visible', 'important');
                    drawer.style.setProperty('opacity', '1', 'important');
                    drawer.style.setProperty('z-index', '99999', 'important');
                    drawer.style.setProperty('transform', 'translateX(0)', 'important');
                    drawer.style.setProperty('right', '1.25rem', 'important');
                }
                
                // Show backdrop with blur
                if (backdrop) {
                    backdrop.classList.remove('hidden');
                    backdrop.style.setProperty('display', 'block', 'important');
                    backdrop.style.setProperty('visibility', 'visible', 'important');
                    backdrop.style.setProperty('opacity', '1', 'important');
                    backdrop.style.setProperty('z-index', '99998', 'important');
                    backdrop.style.setProperty('background-color', 'rgba(0, 0, 0, 0.5)', 'important');
                    backdrop.style.setProperty('backdrop-filter', 'blur(8px)', 'important');
                    backdrop.style.setProperty('-webkit-backdrop-filter', 'blur(8px)', 'important');
                }
                
                // Prevent body scroll when drawer is open
                document.body.style.overflow = 'hidden';
                
                // Load chats and open drawer (explicit user action)
                loadActiveChats(false, true);
            }, 100);
        });
    }
    
    // Handle ESC key to close drawer
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' || e.keyCode === 27) {
            const drawer = document.getElementById('chat_drawer');
            if (drawer && !drawer.classList.contains('hidden') && drawer.getAttribute('data-drawer-closed') !== 'true') {
                handleDrawerClose();
            }
        }
    });
    
    // Handle backdrop click to close drawer
    if (backdrop) {
        backdrop.addEventListener('click', function(e) {
            // Only close if clicking directly on backdrop, not on drawer
            if (e.target === backdrop) {
                handleDrawerClose();
            }
        });
    }
    
    // Update chat list periodically - only if user is authenticated
    chatListUpdateInterval = setInterval(() => {
        if (isUserAuthenticated() && !currentChatId) {
            loadActiveChats();
        }
    }, 10000);
});

