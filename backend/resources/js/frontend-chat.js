// Frontend chat functionality (candidate perspective)
// Performance: Only log in development

let activeChats = [];
let currentChatId = null;
let typingInterval = null;
let messagesInterval = null;
let readStatusInterval = null;
let presenceInterval = null; // Interval for sending presence heartbeat
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
        return Promise.resolve(activeChats);
    }
    
    isLoadingChats = true;
    
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
        
        if (!Array.isArray(chats)) {
            return activeChats;
        }
        
        if (chats.error) {
            return activeChats;
        }
        
        const serverChatIds = chats.map(c => c.id);
        const previousChatIds = activeChats.map(c => c.id);
        
        
        // Preserve unread counts from previous activeChats if they exist
        // This prevents the badge from disappearing when the list refreshes
        chats.forEach(chat => {
            const previousChat = activeChats.find(c => c.id === chat.id);
            if (previousChat && previousChat.unread_count !== undefined) {
                // Always use the higher value (either previous or server)
                // This ensures unread counts don't disappear unexpectedly
                const serverUnreadCount = chat.unread_count || 0;
                const previousUnreadCount = previousChat.unread_count || 0;
                chat.unread_count = Math.max(serverUnreadCount, previousUnreadCount);
                if (previousUnreadCount > serverUnreadCount) {
                }
            }
        });
        
        const currentChatInArray = activeChats.find(c => c.id === currentChatId);
        if (currentChatId && currentChatInArray && !serverChatIds.includes(currentChatId)) {
            chats.push(currentChatInArray);
        }
        
        const oldLength = activeChats.length;
        activeChats = [...chats];
        
        
        const chatListView = document.getElementById('chat_list_view');
        const chatMessagesView = document.getElementById('chat_messages_view');
        
        if (!currentChatId || showListView) {
            if (chatListView) {
                chatListView.style.setProperty('display', 'flex', 'important');
                chatListView.style.setProperty('visibility', 'visible', 'important');
                chatListView.style.setProperty('opacity', '1', 'important');
            }
            if (chatMessagesView) {
                chatMessagesView.style.setProperty('display', 'none', 'important');
                chatMessagesView.style.setProperty('visibility', 'hidden', 'important');
                chatMessagesView.style.setProperty('opacity', '0', 'important');
            }
            if (showListView) {
                currentChatId = null;
                currentChat = null;
            }
        }
        
        renderChatList(activeChats);
        isLoadingChats = false;
        return activeChats;
    })
    .catch(error => {
        isLoadingChats = false;
        return activeChats;
    });
};

// Update chat list item after reactivation (remove gray styling and icon)
function updateChatListItemAfterReactivation(chatId) {
    const chatList = document.getElementById('chat_list');
    if (!chatList) return;
    
    const chatItem = chatList.querySelector(`.chat-item[data-chat-id="${chatId}"]`);
    if (!chatItem) return;
    
    // Remove chat-ended class
    chatItem.classList.remove('chat-ended');
    
    // Remove the ended icon
    const endedIcon = chatItem.querySelector('.ki-cross-circle');
    if (endedIcon) {
        endedIcon.remove();
    }
    
}

// Update unread counts in existing chat list without re-rendering
function updateChatListUnreadCounts(chats) {
    const chatList = document.getElementById('chat_list');
    if (!chatList) return;
    
    chats.forEach(chat => {
        const chatItem = chatList.querySelector(`.chat-item[data-chat-id="${chat.id}"]`);
        if (!chatItem) return;
        
        const unreadCount = chat.unread_count || 0;
        const avatarWrapper = chatItem.querySelector('.relative.shrink-0');
        if (!avatarWrapper) return;
        
        // Remove existing badge
        const existingBadge = avatarWrapper.querySelector('.absolute.top-0.end-0');
        if (existingBadge) {
            existingBadge.remove();
        }
        
        // Add new badge if there are unread messages
        if (unreadCount > 0) {
            const badge = document.createElement('span');
            badge.className = 'absolute top-0 end-0 flex items-center justify-center w-[18px] h-[18px] rounded-full bg-red-500 text-white text-[10px] font-semibold leading-none z-10';
            badge.style.boxSizing = 'border-box';
            badge.style.backgroundColor = 'rgb(239, 68, 68)';
            badge.style.color = 'white';
            badge.style.display = 'flex';
            badge.style.visibility = 'visible';
            badge.style.opacity = '1';
            badge.style.position = 'absolute';
            badge.style.top = '-2px';
            badge.style.right = '-2px';
            badge.style.zIndex = '10';
            badge.style.width = '18px';
            badge.style.height = '18px';
            badge.style.borderRadius = '50%';
            badge.style.transform = 'none';
            badge.textContent = unreadCount > 9 ? '9+' : unreadCount.toString();
            avatarWrapper.appendChild(badge);
        }
    });
}

// Render chat list (frontend - show company name and contact person)
function renderChatList(chats) {
    const chatList = document.getElementById('chat_list');
    const emptyState = document.getElementById('chat_list_empty');
    const chatListView = document.getElementById('chat_list_view');
    
    if (!chatList) {
        return;
    }
    
    if (chatListView && !currentChatId) {
        chatListView.style.setProperty('display', 'flex', 'important');
        chatListView.style.setProperty('visibility', 'visible', 'important');
        chatListView.style.setProperty('opacity', '1', 'important');
    } else if (chatListView && currentChatId) {
        chatListView.style.setProperty('display', 'none', 'important');
        chatListView.style.setProperty('visibility', 'hidden', 'important');
        chatListView.style.setProperty('opacity', '0', 'important');
    } else {
    }

    if (emptyState) {
        emptyState.style.display = chats.length === 0 ? 'flex' : 'none';
    }

    const existingChatItems = chatList.querySelectorAll('.chat-item');
    const existingCount = existingChatItems.length;
    
    // Check if we can just update unread counts instead of re-rendering
    if (existingCount > 0 && existingCount === chats.length) {
        // All chats exist, just update unread counts and ended status
        updateChatListUnreadCounts(chats);
        
        // Also update ended status for each chat item
        chats.forEach(chat => {
            const chatItem = chatList.querySelector(`.chat-item[data-chat-id="${chat.id}"]`);
            if (!chatItem) return;
            
            const isEnded = !chat.is_active;
            if (isEnded && !chatItem.classList.contains('chat-ended')) {
                chatItem.classList.add('chat-ended');
            } else if (!isEnded && chatItem.classList.contains('chat-ended')) {
                chatItem.classList.remove('chat-ended');
                // Remove the ended icon if chat is now active
                const endedIcon = chatItem.querySelector('.ki-cross-circle');
                if (endedIcon) {
                    endedIcon.remove();
                }
            }
        });
        return;
    }
    
    // Need to re-render - remove existing items
    existingChatItems.forEach(item => item.remove());
    if (existingCount > 0) {
    }
    
    if (chats.length === 0) {
        return;
    }

    
    // Sort chats by latest message timestamp (descending)
    const sortedChats = [...chats].sort((a, b) => {
        const timeA = a.last_message ? new Date(a.last_message.created_at).getTime() : (a.updated_at ? new Date(a.updated_at).getTime() : 0);
        const timeB = b.last_message ? new Date(b.last_message.created_at).getTime() : (b.updated_at ? new Date(b.updated_at).getTime() : 0);
        return timeB - timeA;
    });
    
    
    // Render all chats (frontend perspective: show company name and contact person)
    sortedChats.forEach((chat, index) => {
        const chatItem = document.createElement('div');
        chatItem.className = `chat-item p-3 border-b border-border cursor-pointer hover:bg-muted/50 ${chat.id === currentChatId ? 'bg-muted/30' : ''}`;
        chatItem.setAttribute('data-chat-id', chat.id);
        chatItem.onclick = () => selectChat(chat.id);
        
        // Frontend: show company name and contact person name
        const companyName = chat.company && chat.company.name ? chat.company.name : 'Onbekend bedrijf';
        const contactPersonName = chat.user && chat.user.name ? chat.user.name : 'Onbekend contact';
        const displayName = `${companyName} - ${contactPersonName}`;
        const lastMessage = chat.last_message ? chat.last_message.message : '';
        const lastMessageTime = chat.last_message && chat.last_message.time ? chat.last_message.time : '';
        
        const userAvatar = chat.user && chat.user.avatar ? chat.user.avatar : null;
        const unreadCount = chat.unread_count || 0;
        const isEndedByOtherParty = chat.is_ended_by_other_party === true;
        const isEndedByCurrentUser = chat.is_ended_by_current_user === true;
        const isEnded = !chat.is_active;
        
        // Add chat-ended class if chat is ended
        if (isEnded) {
            chatItem.classList.add('chat-ended');
        }
        
        chatItem.innerHTML = `
            <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                    <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center border-2" style="border-color: rgb(156, 163, 175); box-sizing: border-box; overflow: hidden; position: relative;">
                        ${userAvatar ? 
                            `<img src="${escapeHtml(userAvatar)}" alt="${escapeHtml(contactPersonName)}" class="w-full h-full rounded-full object-cover" style="z-index: 0; position: absolute; top: 0; left: 0; width: 100%; height: 100%; border-radius: 50%;" onerror="this.parentElement.innerHTML='<span class=\\'text-primary font-semibold\\' style=\\'position: relative; z-index: 1;\\'>${companyName.charAt(0).toUpperCase()}</span>'">` :
                            `<span class="text-primary font-semibold" style="z-index: 1; position: relative;">${companyName.charAt(0).toUpperCase()}</span>`
                        }
                    </div>
                    ${unreadCount > 0 ? `
                        <span class="absolute top-0 end-0 flex items-center justify-center w-[18px] h-[18px] rounded-full bg-red-500 text-white text-[10px] font-semibold leading-none z-10" style="box-sizing: border-box; background-color: rgb(239, 68, 68) !important; color: white !important; display: flex !important; visibility: visible !important; opacity: 1 !important; position: absolute !important; top: -2px !important; right: -2px !important; z-index: 10 !important; width: 18px !important; height: 18px !important; border-radius: 50% !important; transform: none !important;">
                            ${unreadCount > 9 ? '9+' : unreadCount}
                        </span>
                    ` : ''}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="font-semibold text-sm flex items-center gap-2">
                        ${escapeHtml(displayName)}
                        ${isEnded ? `<i class="ki-filled ki-cross-circle text-base ${isEndedByOtherParty ? 'text-gray-400' : 'text-gray-500'}" title="${isEndedByOtherParty ? 'Chat beëindigd door bedrijf' : 'Chat beëindigd door jou'}" style="font-size: 1rem !important;"></i>` : ''}
                    </div>
                    ${lastMessage ? `<div class="text-xs text-muted-foreground truncate">${escapeHtml(lastMessage)}</div>` : '<div class="text-xs text-muted-foreground">Geen berichten</div>'}
                </div>
                <div class="flex flex-col items-end gap-1 shrink-0">
                    ${lastMessageTime ? `<div class="text-xs text-muted-foreground">${lastMessageTime}</div>` : ''}
                </div>
            </div>
        `;
        chatList.appendChild(chatItem);
    });
    
    const renderedItems = chatList.querySelectorAll('.chat-item');
}

// Select a chat
window.selectChat = function(chatId) {
    if (!chatId) {
        return;
    }
    
    currentChatId = chatId;
    let chat = activeChats.find(c => c.id === chatId);
    
    const drawer = document.getElementById('chat_drawer');
    const backdrop = document.getElementById('chat_drawer_backdrop');
    
    if (drawer) {
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
                loadChatMessages(chatId, true); // Show loader when opening a new chat
                startChatPolling(chatId);
            }
        });
        return;
    }
    
    currentChat = chat;
    
    const messagesContainer = document.getElementById('chat_messages');
    if (messagesContainer) {
        messagesContainer.innerHTML = '';
    }
    
    updateChatViews(chat);
    loadChatMessages(chatId, true); // Show loader when opening a new chat
    startChatPolling(chatId);
};

// Update chat views (frontend - show company and contact person)
function updateChatViews(chat) {
    
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
    }
    
    if (chatMessagesView) {
        chatMessagesView.style.setProperty('display', 'flex', 'important');
        chatMessagesView.style.setProperty('visibility', 'visible', 'important');
        chatMessagesView.style.setProperty('opacity', '1', 'important');
        chatMessagesView.classList.remove('hidden');
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
            // Update chat header avatar - use the same avatar as shown in chat list (user.avatar)
            // For frontend: user.avatar is the company contact person's avatar
            const avatarContainer = chatHeaderAvatar.parentElement;
            const avatarUrl = chat && chat.user && chat.user.avatar ? chat.user.avatar : null;
            
            if (avatarUrl && !avatarUrl.includes('/assets/media/avatars/300-5.png') && !avatarUrl.includes('/assets/media/avatars/300-2.png')) {
                // Replace text with image
                if (chatHeaderAvatar.tagName === 'SPAN') {
                    // Ensure container has overflow hidden
                    if (avatarContainer) {
                        avatarContainer.style.overflow = 'hidden';
                    }
                    const img = document.createElement('img');
                    img.src = avatarUrl;
                    img.alt = contactPersonName || companyName;
                    img.className = 'w-full h-full rounded-full object-cover';
                    img.style.cssText = 'position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; border-radius: 50%; object-fit: cover;';
                    img.onerror = function() {
                        // Fallback to initial if image fails
                        this.remove();
                        chatHeaderAvatar.textContent = companyName.charAt(0).toUpperCase();
                        chatHeaderAvatar.style.display = 'block';
                    };
                    chatHeaderAvatar.style.display = 'none';
                    avatarContainer.appendChild(img);
                } else if (chatHeaderAvatar.tagName === 'IMG') {
                    // Ensure container has overflow hidden
                    if (avatarContainer) {
                        avatarContainer.style.overflow = 'hidden';
                    }
                    chatHeaderAvatar.src = avatarUrl;
                    chatHeaderAvatar.style.cssText = 'position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; border-radius: 50%; object-fit: cover;';
                    chatHeaderAvatar.onerror = function() {
                        // Fallback to initial if image fails
                        const parent = this.parentElement;
                        parent.innerHTML = `<span class="text-primary font-semibold text-sm" id="chat_header_avatar" style="position: relative; z-index: 1;">${companyName.charAt(0).toUpperCase()}</span>`;
                    };
                }
            } else {
                // Show company initial
                if (chatHeaderAvatar.tagName === 'SPAN') {
                    chatHeaderAvatar.textContent = companyName.charAt(0).toUpperCase();
                    chatHeaderAvatar.style.display = 'block';
                } else {
                    // Replace img with span
                    const parent = chatHeaderAvatar.parentElement;
                    parent.innerHTML = `<span class="text-primary font-semibold text-sm" id="chat_header_avatar" style="position: relative; z-index: 1;">${companyName.charAt(0).toUpperCase()}</span>`;
                }
            }
            
            // Update online status indicator in header
            updateChatHeaderStatus(chat);
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

// Update chat header status indicator
function updateChatHeaderStatus(chat, presenceData = null) {
    if (!chat || !chat.id) return;
    
    const avatarContainer = document.getElementById('chat_header_avatar')?.parentElement;
    if (!avatarContainer) return;
    
    // Remove existing status indicator
    const existingIndicator = avatarContainer.querySelector('.kt-avatar-indicator');
    if (existingIndicator) {
        existingIndicator.remove();
    }
    
    // Use presence data if provided (from message response), otherwise use cached value
    let isOnline = false;
    if (presenceData && presenceData.is_online !== undefined) {
        isOnline = presenceData.is_online;
    } else if (chat.user && chat.user.is_online !== undefined) {
        isOnline = chat.user.is_online;
    }
    
    // Add status indicator
    const indicator = document.createElement('div');
    indicator.className = 'kt-avatar-indicator -bottom-2 -end-2';
    indicator.innerHTML = `<div class="kt-avatar-status ${isOnline ? 'kt-avatar-status-online' : 'kt-avatar-status-offline'}"></div>`;
    avatarContainer.appendChild(indicator);
}

// Load chat messages (frontend route)
function loadChatMessages(chatId, showLoader = false) {
    
    if (!chatId) {
        return Promise.reject(new Error('No chatId provided'));
    }
    
    // Show loader only if explicitly requested (e.g., when opening a new chat)
    if (showLoader) {
        const loader = document.getElementById('chat_messages_loader');
        if (loader) {
            loader.style.display = 'flex';
        }
    }
    
    if (currentChatId !== chatId) {
        const messagesContainer = document.getElementById('chat_messages');
        if (messagesContainer) {
            messagesContainer.innerHTML = '';
        }
    }
    
    const url = `/chat/${chatId}/messages`;
    
    return fetch(url, {
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
        }
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error(`HTTP error! status: ${response.status}, body: ${text.substring(0, 200)}`);
            });
        }
        return response.json();
    })
    .then(messages => {
        let presenceData = null;
        
        // Handle both old format (array) and new format (object with messages and presence)
        if (Array.isArray(messages)) {
            // Old format - just messages array
            messages = messages;
        } else if (messages && messages.messages) {
            // New format - object with messages and presence
            presenceData = messages.presence;
            messages = messages.messages;
        }
        
        if (Array.isArray(messages)) {
            if (currentChatId === chatId) {
                renderMessages(messages, chatId);
                
                // Hide loader after messages are rendered
                const loader = document.getElementById('chat_messages_loader');
                if (loader) {
                    loader.style.display = 'none';
                }
                
                setTimeout(() => {
                    scrollToBottom();
                }, 100);
                
                // Update unread count in activeChats array after messages are marked as read
                const chatIndex = activeChats.findIndex(c => c.id === chatId);
                if (chatIndex !== -1) {
                    // Messages have been marked as read, so unread count should be 0
                    activeChats[chatIndex].unread_count = 0;
                    
                    // Update chat header status with presence data from server
                    const chat = activeChats[chatIndex];
                    if (presenceData) {
                        chat.user = chat.user || {};
                        chat.user.is_online = presenceData.is_online || false;
                    }
                    updateChatHeaderStatus(chat, presenceData);
                }
            } else {
                // Hide loader if chat changed
                const loader = document.getElementById('chat_messages_loader');
                if (loader) {
                    loader.style.display = 'none';
                }
            }
        } else {
            // Hide loader on error
            const loader = document.getElementById('chat_messages_loader');
            if (loader) {
                loader.style.display = 'none';
            }
        }
        return messages;
    })
    .catch(error => {
        // Hide loader on error
        const loader = document.getElementById('chat_messages_loader');
        if (loader) {
            loader.style.display = 'none';
        }
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
            
            // Update chat in activeChats array to mark as active
            const chatIndex = activeChats.findIndex(c => c.id === currentChatId);
            if (chatIndex !== -1) {
                activeChats[chatIndex].is_active = true;
                activeChats[chatIndex].is_ended_by_other_party = false;
                activeChats[chatIndex].is_ended_by_current_user = false;
                activeChats[chatIndex].ended_at = null;
            }
            
            // Update chat list item to remove gray styling and icon
            updateChatListItemAfterReactivation(currentChatId);
            
            // Refresh chat list to get updated data from server
            loadActiveChats(false, false).then(() => {
                // Update chat header status
                const chat = activeChats.find(c => c.id === currentChatId);
                if (chat) {
                    updateChatHeaderStatus(chat);
                }
            });
            
            // Check read status after a short delay (to allow backend to process)
            setTimeout(() => {
                if (currentChatId) {
                    updateReadStatus(currentChatId);
                }
            }, 500);
        }
    })
    .catch(error => {
        // Remove optimistic message on error
        const optimisticMsg = document.querySelector(`[data-optimistic-id="${optimisticId}"]`);
        if (optimisticMsg) {
            optimisticMsg.remove();
        }
    });
};

// Render messages (exact backend style)
function renderMessages(messages, chatId) {
    const messagesContainer = document.getElementById('chat_messages');
    if (!messagesContainer) {
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
    
    // Remove status indicators from own messages that might already exist in DOM
    const existingOwnMessages = messagesContainer.querySelectorAll('.flex.items-end.justify-end');
    existingOwnMessages.forEach(msgEl => {
        const indicator = msgEl.querySelector('.kt-avatar-indicator');
        if (indicator) {
            indicator.remove();
        }
    });
    
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
        
        // Update chat header status based on latest message from other party
        const otherMessages = messages.filter(m => !m.is_own);
        if (otherMessages.length > 0) {
            const latestOtherMessage = otherMessages[otherMessages.length - 1];
            const chat = activeChats.find(c => c.id === chatId);
            if (chat && latestOtherMessage.user) {
                chat.user = chat.user || {};
                chat.user.is_online = latestOtherMessage.user.is_online || false;
                updateChatHeaderStatus(chat);
            }
        }
    })
    .catch(error => {
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
    if (presenceInterval) {
        clearInterval(presenceInterval);
    }
    
    // Send initial presence when chat is opened
    if (isPageVisible()) {
        sendChatPresence(chatId);
    }
    
    // Poll for new messages every 5 seconds (reduced from 3s for better performance)
    // Presence is included in the messages response, so no separate request needed
    messagesInterval = setInterval(() => {
        if (currentChatId === chatId && isPageVisible()) {
            loadChatMessages(chatId, false); // Don't show loader on polling updates
        }
    }, 5000);
    
    // Poll for read status updates every 2 seconds (reduced from 1s for better performance)
    readStatusInterval = setInterval(() => {
        if (currentChatId === chatId && isPageVisible()) {
            updateReadStatus(chatId);
        }
    }, 2000);
    
    // Send presence heartbeat every 8 seconds (combined with message polling)
    // Only send if page is visible to save resources
    presenceInterval = setInterval(() => {
        if (currentChatId === chatId && isPageVisible()) {
            sendChatPresence(chatId);
        }
    }, 8000);
}

// Check if page is visible (using Page Visibility API)
function isPageVisible() {
    if (typeof document.hidden !== 'undefined') {
        return !document.hidden;
    }
    return true; // Fallback to true if API not supported
}

// Send chat presence (heartbeat) - lightweight request
function sendChatPresence(chatId) {
    // Use fetch with keepalive for better performance
    fetch(`/chat/${chatId}/presence`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
        },
        keepalive: true // Allows request to complete even if page is closed
    })
    .catch(error => {
        // Silently fail - presence is not critical
        }
    });
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

    // Create custom confirmation dialog with proper dark/light mode styling
    const isDarkMode = document.documentElement.classList.contains('dark');
    
    // Create modal overlay
    const overlay = document.createElement('div');
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: ${isDarkMode ? 'rgba(0, 0, 0, 0.7)' : 'rgba(0, 0, 0, 0.5)'};
        z-index: 999999;
        display: flex;
        align-items: center;
        justify-content: center;
    `;
    
    // Create modal dialog
    const modal = document.createElement('div');
    modal.style.cssText = `
        background-color: ${isDarkMode ? '#1e293b' : '#ffffff'};
        color: ${isDarkMode ? '#f1f5f9' : '#1e293b'};
        border: 1px solid ${isDarkMode ? '#334155' : '#e2e8f0'};
        border-radius: 0.5rem;
        padding: 1.5rem;
        max-width: 400px;
        width: 90%;
        box-shadow: ${isDarkMode ? '0 20px 25px -5px rgba(0, 0, 0, 0.5)' : '0 20px 25px -5px rgba(0, 0, 0, 0.1)'};
    `;
    
    modal.innerHTML = `
        <div style="margin-bottom: 1rem;">
            <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem; color: ${isDarkMode ? '#f1f5f9' : '#1e293b'};">
                Chat beëindigen
            </h3>
            <p style="color: ${isDarkMode ? '#cbd5e1' : '#64748b'};">
                Weet je zeker dat je deze chat wilt beëindigen?
            </p>
        </div>
        <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
            <button id="end-cancel-btn" style="
                padding: 0.5rem 1rem;
                border-radius: 0.375rem;
                border: 1px solid ${isDarkMode ? '#475569' : '#cbd5e1'};
                background-color: ${isDarkMode ? '#334155' : '#f1f5f9'};
                color: ${isDarkMode ? '#f1f5f9' : '#1e293b'};
                cursor: pointer;
                font-weight: 500;
                transition: all 0.2s;
            ">
                Annuleren
            </button>
            <button id="end-confirm-btn" style="
                padding: 0.5rem 1rem;
                border-radius: 0.375rem;
                border: none;
                background-color: #dc2626;
                color: white;
                cursor: pointer;
                font-weight: 500;
                transition: all 0.2s;
            ">
                Beëindigen
            </button>
        </div>
    `;
    
    overlay.appendChild(modal);
    document.body.appendChild(overlay);
    
    // Add hover effects
    const cancelBtn = modal.querySelector('#end-cancel-btn');
    const confirmBtn = modal.querySelector('#end-confirm-btn');
    
    cancelBtn.addEventListener('mouseenter', () => {
        cancelBtn.style.backgroundColor = isDarkMode ? '#475569' : '#e2e8f0';
    });
    cancelBtn.addEventListener('mouseleave', () => {
        cancelBtn.style.backgroundColor = isDarkMode ? '#334155' : '#f1f5f9';
    });
    
    confirmBtn.addEventListener('mouseenter', () => {
        confirmBtn.style.backgroundColor = '#b91c1c';
    });
    confirmBtn.addEventListener('mouseleave', () => {
        confirmBtn.style.backgroundColor = '#dc2626';
    });
    
    // Handle button clicks
    return new Promise((resolve) => {
        cancelBtn.addEventListener('click', () => {
            document.body.removeChild(overlay);
            resolve(false);
        });
        
        confirmBtn.addEventListener('click', () => {
            document.body.removeChild(overlay);
            resolve(true);
        });
        
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                document.body.removeChild(overlay);
                resolve(false);
            }
        });
    }).then((confirmed) => {
        if (!confirmed) return;

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
            }
        })
        .catch(error => {
        });
    });
};

// Delete chat (frontend)
window.deleteChat = function() {
    if (!currentChatId) return;

    // Create custom confirmation dialog with proper dark/light mode styling
    const isDarkMode = document.documentElement.classList.contains('dark');
    
    // Create modal overlay
    const overlay = document.createElement('div');
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: ${isDarkMode ? 'rgba(0, 0, 0, 0.7)' : 'rgba(0, 0, 0, 0.5)'};
        z-index: 999999;
        display: flex;
        align-items: center;
        justify-content: center;
    `;
    
    // Create modal dialog
    const modal = document.createElement('div');
    modal.style.cssText = `
        background-color: ${isDarkMode ? '#1e293b' : '#ffffff'};
        color: ${isDarkMode ? '#f1f5f9' : '#1e293b'};
        border: 1px solid ${isDarkMode ? '#334155' : '#e2e8f0'};
        border-radius: 0.5rem;
        padding: 1.5rem;
        max-width: 400px;
        width: 90%;
        box-shadow: ${isDarkMode ? '0 20px 25px -5px rgba(0, 0, 0, 0.5)' : '0 20px 25px -5px rgba(0, 0, 0, 0.1)'};
    `;
    
    modal.innerHTML = `
        <div style="margin-bottom: 1rem;">
            <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem; color: ${isDarkMode ? '#f1f5f9' : '#1e293b'};">
                Chat verwijderen
            </h3>
            <p style="color: ${isDarkMode ? '#cbd5e1' : '#64748b'};">
                Weet je zeker dat je deze chat wilt verwijderen? Deze actie kan niet ongedaan worden gemaakt.
            </p>
        </div>
        <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
            <button id="delete-cancel-btn" style="
                padding: 0.5rem 1rem;
                border-radius: 0.375rem;
                border: 1px solid ${isDarkMode ? '#475569' : '#cbd5e1'};
                background-color: ${isDarkMode ? '#334155' : '#f1f5f9'};
                color: ${isDarkMode ? '#f1f5f9' : '#1e293b'};
                cursor: pointer;
                font-weight: 500;
                transition: all 0.2s;
            ">
                Annuleren
            </button>
            <button id="delete-confirm-btn" style="
                padding: 0.5rem 1rem;
                border-radius: 0.375rem;
                border: none;
                background-color: #dc2626;
                color: white;
                cursor: pointer;
                font-weight: 500;
                transition: all 0.2s;
            ">
                Verwijderen
            </button>
        </div>
    `;
    
    overlay.appendChild(modal);
    document.body.appendChild(overlay);
    
    // Add hover effects
    const cancelBtn = modal.querySelector('#delete-cancel-btn');
    const confirmBtn = modal.querySelector('#delete-confirm-btn');
    
    cancelBtn.addEventListener('mouseenter', () => {
        cancelBtn.style.backgroundColor = isDarkMode ? '#475569' : '#e2e8f0';
    });
    cancelBtn.addEventListener('mouseleave', () => {
        cancelBtn.style.backgroundColor = isDarkMode ? '#334155' : '#f1f5f9';
    });
    
    confirmBtn.addEventListener('mouseenter', () => {
        confirmBtn.style.backgroundColor = '#b91c1c';
    });
    confirmBtn.addEventListener('mouseleave', () => {
        confirmBtn.style.backgroundColor = '#dc2626';
    });
    
    // Handle button clicks
    return new Promise((resolve) => {
        cancelBtn.addEventListener('click', () => {
            document.body.removeChild(overlay);
            resolve(false);
        });
        
        confirmBtn.addEventListener('click', () => {
            document.body.removeChild(overlay);
            resolve(true);
        });
        
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                document.body.removeChild(overlay);
                resolve(false);
            }
        });
    }).then((confirmed) => {
        if (!confirmed) return;

        fetch(`/chat/${currentChatId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove chat from activeChats array
                activeChats = activeChats.filter(c => c.id !== currentChatId);
                
                // Clear current chat selection
                currentChatId = null;
                currentChat = null;
                
                // Clear messages container
                const messagesContainer = document.getElementById('chat_messages');
                if (messagesContainer) messagesContainer.innerHTML = '';
                
                // Ensure drawer stays open - don't remove data-chat-active
                const drawer = document.getElementById('chat_drawer');
                if (drawer) {
                    // Keep drawer open, just show list view
                    drawer.setAttribute('data-user-opened', 'true');
                    drawer.removeAttribute('data-drawer-closed');
                    drawer.classList.remove('hidden');
                }
                
                // Show chat list (drawer stays open)
                showChatList();
                
                // Refresh chat list from server to remove deleted chat
                loadActiveChats(false, false);
            }
        })
        .catch(error => {
        });
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
    
    if (presenceInterval) {
        clearInterval(presenceInterval);
        presenceInterval = null;
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
        return;
    }
    
    
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
    
    // Update chat list periodically - only if user is authenticated and not viewing a chat
    // This ensures unread counts stay accurate
    chatListUpdateInterval = setInterval(() => {
        if (isUserAuthenticated() && !currentChatId) {
            // Only update if we're showing the chat list, not a specific chat
            const chatListView = document.getElementById('chat_list_view');
            const isListViewVisible = chatListView && 
                window.getComputedStyle(chatListView).display !== 'none' &&
                window.getComputedStyle(chatListView).visibility !== 'hidden';
            
            if (isListViewVisible) {
                loadActiveChats(true, false); // Refresh list view without opening drawer
            }
        }
    }, 30000); // Performance: Update every 30 seconds instead of 10 to reduce server load
});

