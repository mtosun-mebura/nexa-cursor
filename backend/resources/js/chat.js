// Global chat functionality

let activeChats = [];
let currentChatId = null;
let typingInterval = null;
let messagesInterval = null;
let presenceInterval = null; // Interval for sending presence heartbeat
let chatListUpdateInterval = null; // Interval for updating chat list
let currentChat = null; // Store current chat data including user avatar
let isOpeningChat = false; // Flag to prevent observer interference when opening a chat
let isUpdatingStyles = false; // Flag to prevent style observer from triggering itself
let isDrawerExplicitlyClosed = false; // Flag to prevent drawer from reopening when explicitly closed
let pendingOptimisticMessages = new Map(); // Store optimistic messages by message text and chatId
let isWaitingForMessage = false; // Flag to prevent polling from interfering during message retry
let userHasScrolled = false; // Track if user has manually scrolled
let isAutoScrolling = false; // Track if we're auto-scrolling to prevent scroll event from interfering
let isLoadingChats = false; // Flag to prevent multiple simultaneous loadActiveChats calls

// Open chat with candidate
window.openChatWithCandidate = function(candidateId, matchOrAppId, type) {
    
    if (!candidateId) {
        return;
    }
    
    // Reset flag to allow drawer to open
    isDrawerExplicitlyClosed = false;
    
    // First, open the drawer using the same method as the header button
    const drawer = document.getElementById('chat_drawer');
    const backdrop = document.getElementById('chat_drawer_backdrop');
    
    // Remove closed attribute if present
    if (drawer) {
        drawer.removeAttribute('data-drawer-closed');
    }
    
    // First, ensure drawer is open (same as header button)
    const drawerToggle = document.querySelector('[data-kt-drawer-toggle="#chat_drawer"]');
    if (drawerToggle) {
        // Click the toggle button to open drawer (same as header button)
        drawerToggle.click();
    } else if (drawer) {
        // Fallback: manually open drawer if toggle button not found
        const drawerInstance = window.KTDrawer?.getInstance?.(drawer);
        if (drawerInstance) {
            drawerInstance.show();
        } else {
            drawer.classList.remove('hidden');
        }
    }
    
    // Prepare form data first
    const formData = new FormData();
    formData.append('candidate_id', candidateId);
    
    // Handle null values (can come as string 'null' or actual null)
    // Also handle when type is null or empty string
    if (matchOrAppId && matchOrAppId !== 'null' && matchOrAppId !== null && type && type !== 'null' && type !== '') {
        if (type === 'match') {
            formData.append('match_id', matchOrAppId);
        } else if (type === 'application') {
            formData.append('application_id', matchOrAppId);
        }
    }
    
    // Set flag to prevent observer from showing list view
    isOpeningChat = true;
    
    // Ensure chat messages view is shown immediately (not list view)
    const chatListView = document.getElementById('chat_list_view');
    const chatMessagesView = document.getElementById('chat_messages_view');
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
    
    // Start the chat immediately after drawer opens
    setTimeout(() => {
        fetch('/admin/chat/start', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                
                // Set flag to prevent observer interference
                isOpeningChat = true;
                currentChatId = data.chat_id;
                
                // Add to active chats list immediately
                if (data.chat) {
                    const existingIndex = activeChats.findIndex(c => c.id === data.chat.id);
                    if (existingIndex === -1) {
                        activeChats.unshift(data.chat);
                    } else {
                        activeChats[existingIndex] = data.chat;
                    }
                    currentChat = data.chat;
                }
                
                // Ensure drawer is open and positioned correctly
                if (drawer) {
                    // Mark as active
                    drawer.setAttribute('data-chat-active', 'true');
                    drawer.setAttribute('data-ignore-observer', 'true');
                    
                    // Temporarily disconnect observer to reduce triggers
                    if (window.chatDrawerObserver) {
                        window.chatDrawerObserver.disconnect();
                    }
                    
                    // Force transform reset to ensure correct positioning
                    const forceTransformReset = () => {
                        // Set flag to prevent observer from triggering
                        isUpdatingStyles = true;
                        
                        try {
            drawer.style.setProperty('transform', 'translateX(0)', 'important');
            drawer.style.setProperty('translate', '0 0', 'important');
            drawer.style.setProperty('right', '1.25rem', 'important');
            drawer.style.setProperty('left', 'auto', 'important');
            drawer.style.setProperty('--tw-translate-x', '0', 'important');
            drawer.style.setProperty('inset-inline-start', 'auto', 'important');
                            drawer.style.setProperty('display', 'flex', 'important');
                            drawer.style.setProperty('transition', 'none', 'important');
                            drawer.style.setProperty('animation', 'none', 'important');
                            
                            // Aggressively remove left from inline style if it exists
                            if (drawer.style.left) {
                                drawer.style.removeProperty('left');
                            }
                            
                            const inlineStyle = drawer.getAttribute('style');
                            if (inlineStyle) {
                                const cleanedStyle = inlineStyle.replace(/left\s*:\s*[^;!]+(!important)?;?/gi, '').trim();
                                if (cleanedStyle !== inlineStyle) {
                                    drawer.setAttribute('style', cleanedStyle);
                                }
                            }
                            
                            drawer.style.setProperty('left', 'auto', 'important');
                        } finally {
                            setTimeout(() => {
                                isUpdatingStyles = false;
                            }, 10);
                        }
                    };
                    
                    // Apply immediately and multiple times
                    forceTransformReset();
                    requestAnimationFrame(() => {
                        forceTransformReset();
                        requestAnimationFrame(() => {
                            forceTransformReset();
                        });
                    });
                    setTimeout(forceTransformReset, 10);
                    setTimeout(forceTransformReset, 50);
                    setTimeout(forceTransformReset, 100);
                    setTimeout(forceTransformReset, 200);
                    setTimeout(forceTransformReset, 300);
                }
                
                if (backdrop) {
                    backdrop.classList.remove('hidden');
                }
                
                // Explicitly set views before selecting chat
                const chatListView = document.getElementById('chat_list_view');
                const chatMessagesView = document.getElementById('chat_messages_view');
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
                
                // Immediately select the chat and update views
                selectChat(data.chat_id);
            
                // Remove ignore flag after a delay to allow normal operation
                setTimeout(() => {
                    if (drawer) {
                        drawer.removeAttribute('data-ignore-observer');
                        
                        // Reconnect observer after flag is removed
                        if (window.chatDrawerObserver && drawer) {
                            window.chatDrawerObserver.observe(drawer, {
                                attributes: true,
                                attributeFilter: ['class']
                            });
                        }
                    }
                }, 2000);
                
                // Update the current chat in activeChats with the latest data from server
                if (data.chat) {
                    const existingIndex = activeChats.findIndex(c => c.id === data.chat.id);
                    if (existingIndex === -1) {
                        activeChats.unshift(data.chat);
                    } else {
                        activeChats[existingIndex] = data.chat;
                    }
                    // Update currentChat if it's the same chat
                    if (currentChatId === data.chat.id) {
                        currentChat = data.chat;
                    }
                }
                
                // Load active chats in background to update last_message in all chats
                setTimeout(() => {
                    if (!isLoadingChats) {
                        loadActiveChats(false).then(() => {
                            // Re-render chat list if it's visible to update last_message
                            const chatListView = document.getElementById('chat_list_view');
                            if (chatListView && !currentChatId && window.getComputedStyle(chatListView).display !== 'none') {
                                renderChatList(activeChats);
                            }
                        });
                    }
                }, 600);
                
                // Clear flag after everything is set up
                setTimeout(() => {
                    isOpeningChat = false;
                }, 3000);
            }
        })
        .catch(error => {
            isOpeningChat = false;
        });
    }, 100); // Wait 100ms for drawer to open before starting chat
};

// Get CSRF token from meta tag
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

// Load active chats
window.loadActiveChats = function(showListView = false, openDrawer = false) {
    // Prevent multiple simultaneous calls
    if (isLoadingChats) {
        return Promise.resolve(activeChats);
    }
    
    isLoadingChats = true;
    return fetch('/admin/chat/active', {
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
        },
        cache: 'no-cache' // Prevent caching
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(chats => {
        
        // Validate that chats is an array
        if (!Array.isArray(chats)) {
            return activeChats;
        }
        
        // If there's an error in the response, log it
        if (chats.error) {
            return activeChats;
        }
        
        // Start with server response as base (most up-to-date)
        // Replace the entire array instead of merging to ensure fresh data
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
        
        // Preserve current chat if it exists and is not in server response
        const currentChatInArray = activeChats.find(c => c.id === currentChatId);
        if (currentChatId && currentChatInArray && !serverChatIds.includes(currentChatId)) {
            chats.push(currentChatInArray);
        }
        
        // Replace the entire array with server data (don't merge)
        const oldLength = activeChats.length;
        activeChats = [...chats]; // Create a new array from server data
        
        
        // If no chat is selected, or explicitly asked to show list view (e.g., from header button), ensure it's visible
        const chatListView = document.getElementById('chat_list_view');
        const chatMessagesView = document.getElementById('chat_messages_view');
        
        if (!currentChatId || showListView) {
            // Show list view, hide messages view
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
                currentChatId = null; // Clear current chat when explicitly showing list view
                currentChat = null;
            }
        }
        
        // Always render the list to ensure it's up to date
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
        const avatarContainer = chatItem.querySelector('.bg-accent\\/60.size-11, .bg-accent\\/60');
        if (!avatarContainer) return;
        
        // Remove existing badge
        const existingBadge = avatarContainer.querySelector('.absolute.top-0.end-0');
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
            avatarContainer.appendChild(badge);
        }
    });
}

// Render chat list
function renderChatList(chats) {
    const chatList = document.getElementById('chat_list');
    const emptyState = document.getElementById('chat_list_empty');
    const chatListView = document.getElementById('chat_list_view');
    
    if (!chatList) {
        return;
    }
    
    // Ensure chat_list_view is visible (only if no chat is selected)
    if (chatListView && !currentChatId) {
        chatListView.style.setProperty('display', 'flex', 'important');
        chatListView.style.setProperty('visibility', 'visible', 'important');
        chatListView.style.setProperty('opacity', '1', 'important');
    } else if (chatListView && currentChatId) {
        // If a chat is selected, ensure list view is hidden
        chatListView.style.setProperty('display', 'none', 'important');
        chatListView.style.setProperty('visibility', 'hidden', 'important');
        chatListView.style.setProperty('opacity', '0', 'important');
    } else {
    }

    // Always load candidates for dropdown (even if there are chats)
    loadCandidatesForDropdown();

    // Hide empty state if there are chats
    if (emptyState) {
        emptyState.style.display = chats.length === 0 ? 'flex' : 'none';
    }

    // Remove existing chat items
    const existingChatItems = chatList.querySelectorAll('.chat-item');
    const existingCount = existingChatItems.length;
    
    // Check if we can just update unread counts instead of re-rendering
    // Only do this if the chat IDs match exactly (same chats, no additions/removals)
    const existingChatIds = Array.from(existingChatItems).map(item => parseInt(item.getAttribute('data-chat-id')));
    const newChatIds = chats.map(c => c.id).sort((a, b) => a - b);
    const existingChatIdsSorted = existingChatIds.sort((a, b) => a - b);
    const chatIdsMatch = existingCount > 0 && 
                         existingCount === chats.length && 
                         JSON.stringify(existingChatIdsSorted) === JSON.stringify(newChatIds);
    
    if (chatIdsMatch) {
        // All chats already exist in DOM with matching IDs, just update unread counts and last messages
        updateChatListUnreadCounts(chats);
        
        // Also update last message text and time for each chat
        chats.forEach(chat => {
            const chatItem = chatList.querySelector(`.chat-item[data-chat-id="${chat.id}"]`);
            if (chatItem && chat.last_message) {
                const lastMessageEl = chatItem.querySelector('.text-xs.text-muted-foreground');
                const lastMessageTimeEl = chatItem.querySelector('.text-xs.text-gray-500');
                if (lastMessageEl && chat.last_message.message) {
                    lastMessageEl.textContent = chat.last_message.message;
                }
                if (lastMessageTimeEl && chat.last_message.time) {
                    lastMessageTimeEl.textContent = chat.last_message.time;
                }
            }
        });
        
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
        // Show empty state with candidate dropdown
        return;
    }

    
    // Sort chats by latest message timestamp (descending)
    const sortedChats = [...chats].sort((a, b) => {
        // Use last_message (from server) or updated_at as fallback
        const timeA = a.last_message ? new Date(a.last_message.created_at).getTime() : (a.updated_at ? new Date(a.updated_at).getTime() : 0);
        const timeB = b.last_message ? new Date(b.last_message.created_at).getTime() : (b.updated_at ? new Date(b.updated_at).getTime() : 0);
        return timeB - timeA; // Latest first
    });
    
    
    // Render all chats (use sorted array)
    sortedChats.forEach((chat, index) => {
        const chatItem = document.createElement('div');
        chatItem.className = `chat-item p-3 border-b border-border cursor-pointer hover:bg-muted/50 ${chat.id === currentChatId ? 'bg-muted/30' : ''}`;
        chatItem.setAttribute('data-chat-id', chat.id);
        chatItem.onclick = () => selectChat(chat.id);
        
        const candidateName = chat.candidate && chat.candidate.name ? chat.candidate.name : 'Onbekend';
        const lastMessage = chat.last_message ? chat.last_message.message : '';
        const lastMessageTime = chat.last_message && chat.last_message.time ? chat.last_message.time : '';
        
        const candidateAvatar = chat.candidate && chat.candidate.avatar ? chat.candidate.avatar : null;
        const unreadCount = chat.unread_count !== undefined && chat.unread_count !== null ? parseInt(chat.unread_count) : 0;
        
        if (unreadCount > 0) {
        } else {
        }
        
        const isEndedByOtherParty = chat.is_ended_by_other_party === true;
        const isEndedByCurrentUser = chat.is_ended_by_current_user === true;
        const isEnded = !chat.is_active;
        
        // Add chat-ended class if chat is ended
        if (isEnded) {
            chatItem.classList.add('chat-ended');
        }
        
        chatItem.innerHTML = `
            <div class="flex items-center gap-3">
                <div class="bg-accent/60 flex size-11 shrink-0 items-center justify-center rounded-full border border-border relative" style="position: relative; overflow: visible;">
                    ${candidateAvatar ? 
                        `<img src="${escapeHtml(candidateAvatar)}" alt="${escapeHtml(candidateName)}" class="w-full h-full rounded-full object-cover" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; border-radius: 50%;" onerror="this.parentElement.innerHTML='<span class=\\'text-primary font-semibold text-sm\\' style=\\'position: relative; z-index: 1;\\'>${candidateName.charAt(0).toUpperCase()}</span>'">` :
                        `<span class="text-primary font-semibold text-sm" style="position: relative; z-index: 1;">${candidateName.charAt(0).toUpperCase()}</span>`
                    }
                    ${unreadCount > 0 ? `
                        <span class="absolute top-0 end-0 flex items-center justify-center w-[18px] h-[18px] rounded-full bg-red-500 text-white text-[10px] font-semibold leading-none z-10" style="box-sizing: border-box; background-color: rgb(239, 68, 68) !important; color: white !important; display: flex !important; visibility: visible !important; opacity: 1 !important; position: absolute !important; top: -2px !important; right: -2px !important; z-index: 10 !important; width: 18px !important; height: 18px !important; border-radius: 50% !important; transform: none !important;">
                            ${unreadCount > 9 ? '9+' : unreadCount}
                        </span>
                    ` : ''}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="font-semibold text-sm flex items-center gap-2">
                        ${escapeHtml(candidateName)}
                        ${isEnded ? `<i class="ki-filled ki-cross-circle text-base ${isEndedByOtherParty ? 'text-gray-400' : 'text-gray-500'}" title="${isEndedByOtherParty ? 'Chat beëindigd door kandidaat' : 'Chat beëindigd door jou'}" style="font-size: 1rem !important;"></i>` : ''}
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
    
    // Verify items are in DOM
    const renderedItems = chatList.querySelectorAll('.chat-item');
}

// Load candidates for dropdown
function loadCandidatesForDropdown() {
    fetch('/admin/chat/candidates', {
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
        }
    })
    .then(response => response.json())
    .then(candidates => {
        const select = document.getElementById('chat_candidate_select');
        if (select && candidates.length > 0) {
            select.innerHTML = '<option value="">Selecteer een kandidaat...</option>' + 
                candidates.map(candidate => 
                    `<option value="${candidate.id}" data-match-id="${candidate.match_id || ''}">${candidate.name}${candidate.vacancy_title ? ' - ' + candidate.vacancy_title : ''}</option>`
                ).join('');
        }
    })
    .catch(error => {
    });
}

// Handle candidate selection
window.handleCandidateSelect = function() {
    const select = document.getElementById('chat_candidate_select');
    if (!select || !select.value) return;

    const selectedOption = select.options[select.selectedIndex];
    const candidateId = select.value;
    const matchId = selectedOption.getAttribute('data-match-id');

    if (candidateId) {
        openChatWithCandidate(candidateId, matchId || null, matchId ? 'match' : 'application');
        select.value = ''; // Reset selection
    }
};

// Select a chat
window.selectChat = function(chatId) {
    if (!chatId) {
        return;
    }
    
    
    // Find chat in activeChats array
    let chat = activeChats.find(c => c.id === chatId);
    
    // Mark drawer as active and ensure it's open
    const drawer = document.getElementById('chat_drawer');
    const backdrop = document.getElementById('chat_drawer_backdrop');
    
    if (drawer) {
        // Reset closed flag and attribute
        isDrawerExplicitlyClosed = false;
        drawer.removeAttribute('data-drawer-closed');
        drawer.classList.remove('hidden');
        
        // Set flag to prevent observer from triggering
        isUpdatingStyles = true;
        
        try {
            drawer.style.setProperty('display', 'flex', 'important');
            drawer.style.setProperty('visibility', 'visible', 'important');
            drawer.style.setProperty('opacity', '1', 'important');
            drawer.style.setProperty('z-index', '99999', 'important');
            drawer.style.setProperty('transform', 'translateX(0)', 'important'); // Reset transform to ensure drawer is fully visible
            drawer.style.setProperty('right', '1.25rem', 'important'); // Ensure right positioning
            drawer.style.setProperty('left', 'unset', 'important'); // Force left to unset
            drawer.setAttribute('data-chat-active', 'true');
            drawer.setAttribute('data-ignore-observer', 'true');
            
            // Temporarily disconnect observer to reduce triggers
            if (window.chatDrawerObserver) {
                window.chatDrawerObserver.disconnect();
            }
            
            // Force transform reset multiple times to ensure it sticks
            const forceTransformReset = () => {
                isUpdatingStyles = true;
                try {
            drawer.style.setProperty('transform', 'translateX(0)', 'important');
            drawer.style.setProperty('translate', '0 0', 'important');
            drawer.style.setProperty('right', '1.25rem', 'important');
            drawer.style.setProperty('left', 'auto', 'important');
            drawer.style.setProperty('--tw-translate-x', '0', 'important');
            drawer.style.setProperty('inset-inline-start', 'auto', 'important');
                    drawer.style.setProperty('display', 'flex', 'important');
                    drawer.style.setProperty('transition', 'none', 'important');
                    drawer.style.setProperty('animation', 'none', 'important');
                } finally {
                    setTimeout(() => {
                        isUpdatingStyles = false;
                    }, 10);
                }
            };
            
            // Apply immediately
            forceTransformReset();
            
            // Apply again after a short delay using requestAnimationFrame
            requestAnimationFrame(() => {
                forceTransformReset();
                requestAnimationFrame(() => {
                    forceTransformReset();
                });
            });
            
            // Also apply after small timeouts to catch any late updates
            setTimeout(forceTransformReset, 10);
            setTimeout(forceTransformReset, 50);
            setTimeout(forceTransformReset, 100);
        } finally {
            setTimeout(() => {
                isUpdatingStyles = false;
            }, 150);
        }
        
    } else {
    }
    
    if (backdrop) {
        backdrop.classList.remove('hidden');
        backdrop.style.setProperty('display', 'block', 'important');
        backdrop.style.setProperty('visibility', 'visible', 'important');
        backdrop.style.setProperty('z-index', '99998', 'important');
    }
    
    // If chat not found in activeChats, try to load it
    if (!chat) {
        // Try to find it in currentChat (might be set when opening a new chat)
        if (currentChat && currentChat.id === chatId) {
            chat = currentChat;
            // Add to activeChats if not already there
            const existingIndex = activeChats.findIndex(c => c.id === chatId);
            if (existingIndex === -1) {
                activeChats.unshift(chat);
            }
        } else {
            // Reload active chats to get the chat data
            return loadActiveChats(false, false).then(() => {
                chat = activeChats.find(c => c.id === chatId);
                if (chat) {
                    currentChatId = chatId;
                    currentChat = chat;
                    updateChatViews(chat);
                    loadChatMessages(chatId, true); // Show loader when opening a new chat
                    startChatPolling(chatId);
                } else {
                }
            });
        }
    }
    
    // Set current chat
    currentChatId = chatId;
    currentChat = chat;
    
    // Clear messages container before switching chats to prevent mixing messages
    const messagesContainer = document.getElementById('chat_messages');
    if (messagesContainer) {
        messagesContainer.innerHTML = '';
    }
    
    // Update views with chat data
    updateChatViews(chat);
    
    // Load messages and start polling (show loader when opening a new chat)
    loadChatMessages(chatId, true);
    startChatPolling(chatId);
};

// Helper function to update chat views
function updateChatViews(chat) {
    
    const chatListView = document.getElementById('chat_list_view');
    const chatMessagesView = document.getElementById('chat_messages_view');
    const chatHeaderName = document.getElementById('chat_header_name');
    const chatHeaderAvatar = document.getElementById('chat_header_avatar');
    const chatUserAvatar = document.getElementById('chat_user_avatar');
    const drawer = document.getElementById('chat_drawer');
    const backdrop = document.getElementById('chat_drawer_backdrop');
    
    // Ensure drawer is visible
    if (drawer) {
        // Reset closed flag and attribute
        isDrawerExplicitlyClosed = false;
        drawer.removeAttribute('data-drawer-closed');
        drawer.classList.remove('hidden');
        
        // Set flag to prevent observer from triggering
        isUpdatingStyles = true;
        
        try {
            drawer.style.setProperty('display', 'flex', 'important');
            drawer.style.setProperty('visibility', 'visible', 'important');
            drawer.style.setProperty('opacity', '1', 'important');
            drawer.style.setProperty('z-index', '99999', 'important');
            drawer.style.setProperty('transform', 'translateX(0)', 'important'); // Reset transform to ensure drawer is fully visible
            drawer.style.setProperty('right', '1.25rem', 'important'); // Ensure right positioning
            drawer.style.setProperty('left', 'unset', 'important'); // Force left to unset
            drawer.style.setProperty('transition', 'none', 'important'); // Disable transitions
            drawer.style.setProperty('animation', 'none', 'important'); // Disable animations
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
    }
    
    // Hide list view completely and show messages view
    if (chatListView) {
        chatListView.style.setProperty('display', 'none', 'important');
        chatListView.style.setProperty('visibility', 'hidden', 'important');
        chatListView.style.setProperty('opacity', '0', 'important');
    } else {
    }
    
    if (chatMessagesView) {
        chatMessagesView.style.setProperty('display', 'flex', 'important');
        chatMessagesView.style.setProperty('visibility', 'visible', 'important');
        chatMessagesView.style.setProperty('opacity', '1', 'important');
        // Ensure messages view is visible
        chatMessagesView.classList.remove('hidden');
    } else {
    }
    
    if (chat) {
        if (chatHeaderName && chat.candidate) {
            chatHeaderName.textContent = chat.candidate.name;
        }
        if (chatHeaderAvatar && chat.candidate) {
            // Update chat header avatar - show image if available, otherwise show initial
            const avatarContainer = chatHeaderAvatar.parentElement;
            if (chat.candidate.avatar && !chat.candidate.avatar.includes('/assets/media/avatars/300-5.png')) {
                // Ensure container has overflow visible to allow status indicator
                if (avatarContainer) {
                    avatarContainer.style.overflow = 'visible';
                }
                // Replace text with image
                if (chatHeaderAvatar.tagName === 'SPAN') {
                    const img = document.createElement('img');
                    img.src = chat.candidate.avatar;
                    img.alt = chat.candidate.name;
                    img.className = 'w-full h-full rounded-full object-cover';
                    img.style.cssText = 'position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; border-radius: 50%; object-fit: cover;';
                    img.onerror = function() {
                        // Fallback to initial if image fails
                        this.remove();
                        chatHeaderAvatar.textContent = chat.candidate.name.charAt(0).toUpperCase();
                        chatHeaderAvatar.style.display = 'block';
                    };
                    chatHeaderAvatar.style.display = 'none';
                    avatarContainer.appendChild(img);
                } else if (chatHeaderAvatar.tagName === 'IMG') {
                    chatHeaderAvatar.src = chat.candidate.avatar;
                    chatHeaderAvatar.style.cssText = 'position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; border-radius: 50%; object-fit: cover;';
                    chatHeaderAvatar.onerror = function() {
                        // Fallback to initial if image fails
                        const parent = this.parentElement;
                        parent.innerHTML = `<span class="text-primary font-semibold text-sm" id="chat_header_avatar" style="position: relative; z-index: 1;">${chat.candidate.name.charAt(0).toUpperCase()}</span>`;
                    };
                }
            } else {
                // Show initial
                if (chatHeaderAvatar.tagName === 'SPAN') {
                    chatHeaderAvatar.textContent = chat.candidate.name.charAt(0).toUpperCase();
                    chatHeaderAvatar.style.display = 'block';
                } else {
                    // Replace img with span
                    const parent = chatHeaderAvatar.parentElement;
                    parent.innerHTML = `<span class="text-primary font-semibold text-sm" id="chat_header_avatar" style="position: relative; z-index: 1;">${chat.candidate.name.charAt(0).toUpperCase()}</span>`;
                }
            }
            
            // Update online status indicator in header
            updateChatHeaderStatus(chat);
        }
        if (chatUserAvatar && chat.user && chat.user.avatar) {
            chatUserAvatar.src = chat.user.avatar;
        }
    }
    
    // Initialize chat input event listeners
    setTimeout(() => {
        initializeChatInput();
        setupScrollListener();
        
        // Focus the textarea so user can start typing immediately
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
    } else if (chat.candidate && chat.candidate.is_online !== undefined) {
        isOnline = chat.candidate.is_online;
    }
    
    // Add status indicator
    const indicator = document.createElement('div');
    indicator.className = 'kt-avatar-indicator -bottom-2 -end-2';
    indicator.innerHTML = `<div class="kt-avatar-status ${isOnline ? 'kt-avatar-status-online' : 'kt-avatar-status-offline'}"></div>`;
    avatarContainer.appendChild(indicator);
}

// Setup scroll listener to detect manual scrolling
function setupScrollListener() {
    const scrollableParent = document.querySelector('#chat_messages_view .kt-scrollable-y-auto');
    if (!scrollableParent) return;
    
    // Remove existing listener if any
    scrollableParent.removeEventListener('scroll', handleScroll);
    
    // Add scroll listener
    scrollableParent.addEventListener('scroll', handleScroll);
    
    // Initial button state
    updateScrollToBottomButton();
}

// Handle scroll events
function handleScroll() {
    // Don't set userHasScrolled if we're auto-scrolling
    if (isAutoScrolling) return;
    
    const scrollableParent = document.querySelector('#chat_messages_view .kt-scrollable-y-auto');
    if (!scrollableParent) return;
    
    const isAtBottom = scrollableParent.scrollHeight - scrollableParent.scrollTop <= scrollableParent.clientHeight + 50;
    
    if (!isAtBottom) {
        userHasScrolled = true;
    } else {
        userHasScrolled = false;
    }
    
    updateScrollToBottomButton();
}

// Show chat list
window.showChatList = function() {
    const drawer = document.getElementById('chat_drawer');
    
    currentChatId = null;
    currentChat = null;
    
    // If drawer should stay open (e.g., after delete), ensure it stays visible
    if (drawer && drawer.getAttribute('data-user-opened') === 'true') {
        drawer.setAttribute('data-chat-active', 'true');
        drawer.removeAttribute('data-drawer-closed');
        drawer.classList.remove('hidden');
        drawer.style.setProperty('display', 'flex', 'important');
        drawer.style.setProperty('visibility', 'visible', 'important');
        drawer.style.setProperty('opacity', '1', 'important');
        drawer.style.setProperty('z-index', '99999', 'important');
        drawer.style.setProperty('transform', 'translateX(0)', 'important');
        drawer.style.setProperty('right', '1.25rem', 'important');
        drawer.style.setProperty('left', 'unset', 'important');
        drawer.style.setProperty('top', '1.25rem', 'important');
        drawer.style.setProperty('bottom', '1.25rem', 'important');
        drawer.style.setProperty('width', '450px', 'important');
        drawer.style.setProperty('max-width', '90%', 'important');
        drawer.style.setProperty('position', 'fixed', 'important');
        drawer.style.setProperty('margin-left', '0', 'important');
    }
    
    loadActiveChats(true).then(() => {
        
        // Ensure drawer stays open after loadActiveChats
        if (drawer && drawer.getAttribute('data-user-opened') === 'true') {
            drawer.setAttribute('data-chat-active', 'true');
            drawer.removeAttribute('data-drawer-closed');
            drawer.classList.remove('hidden');
            drawer.style.setProperty('display', 'flex', 'important');
            drawer.style.setProperty('visibility', 'visible', 'important');
            drawer.style.setProperty('opacity', '1', 'important');
            drawer.style.setProperty('z-index', '99999', 'important');
            drawer.style.setProperty('transform', 'translateX(0)', 'important');
            drawer.style.setProperty('right', '1.25rem', 'important');
            drawer.style.setProperty('left', 'unset', 'important');
            drawer.style.setProperty('top', '1.25rem', 'important');
            drawer.style.setProperty('bottom', '1.25rem', 'important');
            drawer.style.setProperty('width', '450px', 'important');
            drawer.style.setProperty('max-width', '90%', 'important');
            drawer.style.setProperty('position', 'fixed', 'important');
            drawer.style.setProperty('margin-left', '0', 'important');
            
            const backdrop = document.getElementById('chat_drawer_backdrop');
            if (backdrop) {
                backdrop.classList.remove('hidden');
                backdrop.style.setProperty('display', 'block', 'important');
                backdrop.style.setProperty('visibility', 'visible', 'important');
            }
        }
        
        if (drawer) {
        }
    });
};

// Initialize chat message input event listeners
function initializeChatInput() {
    const chatInput = document.getElementById('chat_message_input');
    if (!chatInput) {
        return;
    }
    
    // Remove existing listeners by cloning the element
    const newInput = chatInput.cloneNode(true);
    chatInput.parentNode.replaceChild(newInput, chatInput);
    
    // Auto-resize textarea
    const autoResize = function() {
        newInput.style.height = 'auto';
        newInput.style.height = Math.min(newInput.scrollHeight, 200) + 'px';
    };
    
    // Add Enter key listener
    newInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            if (window.sendMessage) {
                window.sendMessage();
            } else {
            }
        } else {
            if (onChatMessageInput) {
                onChatMessageInput();
            }
        }
    });
    
    // Add input listener for typing indicator and auto-resize
    newInput.addEventListener('input', function() {
        autoResize();
        if (onChatMessageInput) {
            onChatMessageInput();
        }
    });
    
    // Initial resize
    autoResize();
    
    // Focus the textarea after initialization (with a small delay to ensure it's ready)
    setTimeout(() => {
        newInput.focus();
    }, 50);
    
    // Add event listener to Send button
    const sendButton = document.getElementById('chat_send_button');
    if (sendButton && !sendButton.hasAttribute('data-send-listener-added')) {
        sendButton.setAttribute('data-send-listener-added', 'true');
        sendButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (window.sendMessage) {
                window.sendMessage();
            } else {
            }
        });
    } else if (!sendButton) {
    }
    
}

// Load chat messages
function loadChatMessages(chatId, showLoader = false) {
    
    // Show loader only if explicitly requested (e.g., when opening a new chat)
    if (showLoader) {
        const loader = document.getElementById('chat_messages_loader');
        if (loader) {
            loader.style.display = 'flex';
        }
    }
    
    // Clear messages container if switching to a different chat
    if (currentChatId !== chatId) {
        const messagesContainer = document.getElementById('chat_messages');
        if (messagesContainer) {
            messagesContainer.innerHTML = '';
        }
    }
    
    return fetch(`/admin/chat/${chatId}/messages`, {
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
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
            // Only render if this is still the current chat (prevent race conditions)
            if (currentChatId === chatId) {
                renderMessages(messages, chatId);
                
                // Hide loader after messages are rendered
                const loader = document.getElementById('chat_messages_loader');
                if (loader) {
                    loader.style.display = 'none';
                }
                
                // Use setTimeout to ensure DOM is updated before scrolling
                setTimeout(() => {
                    scrollToBottom();
                }, 100);
                
                // Set unread_count to 0 for the current chat after messages are loaded
                const chatIndex = activeChats.findIndex(c => c.id === chatId);
                if (chatIndex !== -1) {
                    activeChats[chatIndex].unread_count = 0;
                    // Update the badge in the chat list
                    updateChatListUnreadCounts([activeChats[chatIndex]]);
                    
                    // Update chat header status with presence data from server
                    const chat = activeChats[chatIndex];
                    if (presenceData) {
                        chat.candidate = chat.candidate || {};
                        chat.candidate.is_online = presenceData.is_online || false;
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

// Sort messages in DOM by timestamp
function sortMessagesInDOM() {
    const messagesContainer = document.getElementById('chat_messages');
    if (!messagesContainer) return;
    
    // Get all message elements
    const messageElements = Array.from(messagesContainer.querySelectorAll('.flex.items-end'));
    
    if (messageElements.length === 0) return;
    
    // Extract timestamp from each element and sort
    const messagesWithTime = messageElements.map(el => {
        // Try to get timestamp from data attribute first (most reliable)
        const timestampAttr = el.getAttribute('data-timestamp');
        let timestamp = null;
        
        if (timestampAttr) {
            timestamp = new Date(timestampAttr);
        } else {
            // Fallback: try to parse from time element
            const timeElement = el.querySelector('.text-xs.font-medium');
            if (timeElement) {
                const timeText = timeElement.textContent.trim();
                const now = new Date();
                const [hours, minutes] = timeText.split(':').map(Number);
                if (!isNaN(hours) && !isNaN(minutes)) {
                    timestamp = new Date(now.getFullYear(), now.getMonth(), now.getDate(), hours, minutes);
                }
            }
            
            // If still no timestamp, use current time
            if (!timestamp) {
                timestamp = new Date();
            }
        }
        
        return { element: el, timestamp: timestamp };
    });
    
    // Sort by timestamp
    messagesWithTime.sort((a, b) => {
        return a.timestamp.getTime() - b.timestamp.getTime();
    });
    
    // Re-append in sorted order (this preserves the order)
    messagesWithTime.forEach(({ element }) => {
        messagesContainer.appendChild(element);
    });
}

// Render messages
function renderMessages(messages, expectedChatId = null) {
    const messagesContainer = document.getElementById('chat_messages');
    if (!messagesContainer) {
        return;
    }
    
    // If expectedChatId is provided and doesn't match currentChatId, don't render
    if (expectedChatId !== null && expectedChatId !== currentChatId) {
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
    
    // Clear all existing real messages (but keep optimistic messages if they match the current chat)
    const existingRealMessages = messagesContainer.querySelectorAll('[data-optimistic="false"]');
    existingRealMessages.forEach(msg => msg.remove());

    // Preserve optimistic messages with their unique IDs and timestamps (only for current chat)
    const optimisticMessages = Array.from(messagesContainer.querySelectorAll('[data-optimistic="true"]'));
    const optimisticMessagesData = optimisticMessages.map(el => {
        const messageText = el.getAttribute('data-message-text') || el.querySelector('.kt-card p')?.textContent || '';
        const optimisticId = el.getAttribute('data-optimistic-id');
        const timestamp = el.getAttribute('data-timestamp');
        const chatIdAttr = el.getAttribute('data-chat-id');
        // Only preserve optimistic messages that belong to the current chat
        if (chatIdAttr && chatIdAttr !== String(currentChatId)) {
            el.remove();
            return null;
        }
        return { element: el, text: messageText, id: optimisticId, timestamp: timestamp ? new Date(timestamp) : new Date() };
    }).filter(item => item !== null);

    // Check if we have any messages (real or optimistic)
    const hasRealMessages = Array.isArray(messages) && messages.length > 0;
    const hasOptimisticMessages = optimisticMessages.length > 0;
    const hasAnyMessages = hasRealMessages || hasOptimisticMessages;
    
    // Show/hide empty message
    let emptyMessage = messagesContainer.querySelector('.chat-empty-message');
    
    if (!hasAnyMessages) {
        // No messages at all - show empty message
        if (!emptyMessage) {
            emptyMessage = document.createElement('div');
            emptyMessage.className = 'chat-empty-message text-center text-muted-foreground p-4';
            emptyMessage.textContent = 'Geen berichten';
            messagesContainer.appendChild(emptyMessage);
        }
    } else {
        // We have messages - remove empty message if it exists
        if (emptyMessage) {
            emptyMessage.remove();
        }
    }
    
    if (!hasRealMessages) {
        // No real messages from server, but might have optimistic ones
        if (!hasOptimisticMessages) {
            // No messages at all, empty message already shown above
            return;
        }
        // We have optimistic messages, continue to render them
        return;
    }

    // Get all existing message IDs (both optimistic and real)
    // IMPORTANT: We preserve ALL existing messages in the DOM, even if they're not in the server response
    // This prevents messages from disappearing during reloads
    const existingMessageIds = new Set();
    const existingMessageElements = new Map(); // Store element references
    
    // Also check for existing real messages by looking at all message divs
    const allMessageDivs = Array.from(messagesContainer.querySelectorAll('.flex.items-end'));
    allMessageDivs.forEach(el => {
        const isOptimistic = el.getAttribute('data-optimistic') === 'true';
        if (!isOptimistic) {
            // This is a real message, get its ID from data attribute
            const messageId = el.getAttribute('data-message-id');
            if (messageId) {
                existingMessageIds.add(messageId);
                existingMessageElements.set(messageId, el);
            }
        }
    });

    // Also track optimistic messages by text for removal later
    const optimisticTexts = new Set(optimisticMessagesData.map(({ text }) => text));

    // Render new messages (only those that don't already exist by ID)
    const newMessages = messages.filter(msg => {
        // Don't render if it already exists as a real message (check by ID)
        if (msg.id && existingMessageIds.has(String(msg.id))) {
            return false;
        }
        // Always render new messages, even if text is the same
        return true;
    });
    
    // IMPORTANT: Preserve all existing messages - never remove them
    // Only add new ones from the server response
    
    if (newMessages.length === 0) {
        // Still check if we need to remove optimistic messages that now have real counterparts
        // Match by timestamp proximity, not just text (to handle duplicate messages)
        optimisticMessagesData.forEach(({ element, text, timestamp: optimisticTimestamp }) => {
            if (!text || !optimisticTimestamp) return;
            
            // Find the real message with the same text that is closest in time to this optimistic message
            const matchingMessages = messages.filter(msg => msg.message === text);
            if (matchingMessages.length === 0) return;
            
            // Find the message with the closest timestamp
            let closestMessage = null;
            let closestTimeDiff = Infinity;
            matchingMessages.forEach(msg => {
                if (msg.created_at) {
                    const msgTimestamp = new Date(msg.created_at);
                    const timeDiff = Math.abs(msgTimestamp.getTime() - optimisticTimestamp.getTime());
                    if (timeDiff < closestTimeDiff) {
                        closestTimeDiff = timeDiff;
                        closestMessage = msg;
                    }
                }
            });
            
            if (closestMessage && closestTimeDiff < 5000) { // Within 5 seconds
                // Check if this specific real message is in the DOM
                const realMessageInDOM = messagesContainer.querySelector(`[data-optimistic="false"][data-message-id="${closestMessage.id}"]`);
                if (realMessageInDOM && realMessageInDOM.offsetParent !== null) {
                    // This specific real message exists in DOM, safe to remove this optimistic one
                    element.remove();
                    // Sort immediately after removing optimistic message
                    sortMessagesInDOM();
                } else {
                    // Real message not yet in DOM or not visible, keep optimistic one
                }
            }
        });
        // Always sort messages even if no new ones were added (to fix any ordering issues)
        sortMessagesInDOM();
        
        // Check if we have any messages after processing
        const allMessageElementsAfterSort = messagesContainer.querySelectorAll('.flex.items-end');
        const hasAnyMessagesAfterSort = allMessageElementsAfterSort.length > 0;
        
        // Update empty message visibility
        let emptyMessageAfterSort = messagesContainer.querySelector('.chat-empty-message');
        if (!hasAnyMessagesAfterSort) {
            // No messages at all - show empty message
            if (!emptyMessageAfterSort) {
                emptyMessageAfterSort = document.createElement('div');
                emptyMessageAfterSort.className = 'chat-empty-message text-center text-muted-foreground p-4';
                emptyMessageAfterSort.textContent = 'Geen berichten';
                messagesContainer.appendChild(emptyMessageAfterSort);
            }
        } else {
            // We have messages - remove empty message if it exists
            if (emptyMessageAfterSort) {
                emptyMessageAfterSort.remove();
            }
        }
        
        scrollToBottom();
        return;
    }
    
    const newMessagesHTML = newMessages.map(msg => {
        const isOwn = msg.is_own;
        const senderInitial = msg.sender_name ? msg.sender_name.charAt(0).toUpperCase() : '?';
        
        if (isOwn) {
            // Own messages - right aligned with avatar on right
            const userAvatar = currentChat && currentChat.user && currentChat.user.avatar 
                ? currentChat.user.avatar 
                : '/assets/media/avatars/300-2.png';
            // Parse created_at timestamp for sorting
            const messageTimestamp = msg.created_at 
                ? new Date(msg.created_at).toISOString()
                : new Date().toISOString();
            
            return `
                <div class="flex items-end justify-end gap-3.5 px-5" data-optimistic="false" data-message-id="${msg.id || ''}" data-message-text="${escapeHtml(msg.message)}" data-timestamp="${messageTimestamp}" data-chat-id="${currentChatId || ''}">
                    <div class="flex flex-col gap-1.5">
                        <div class="kt-card bg-primary rounded-be-none flex flex-col gap-2.5 p-3 shadow-none">
                            <p class="text-2sm text-primary-foreground font-medium">${escapeHtml(msg.message)}</p>
                        </div>
                        <div class="relative flex items-center justify-end gap-2">
                            <span class="text-xs font-medium text-secondary-foreground">${msg.time}</span>
                            ${msg.is_read !== undefined ? `<i class="ki-filled ki-double-check text-lg ${msg.is_read ? 'text-green-500' : 'text-gray-400'}"></i>` : ''}
                        </div>
                    </div>
                    <div class="relative shrink-0">
                        <div class="kt-avatar size-9">
                            <div class="kt-avatar-image">
                                <img alt="${msg.sender_name}" class="size-9 rounded-full object-cover" src="${userAvatar}" />
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else {
            // Other messages - left aligned with avatar on left (gray card with border)
            const avatarUrl = msg.sender_avatar || '/assets/media/avatars/300-5.png';
            // Get online status from message data, default to false if not provided
            const isOnline = msg.user && msg.user.is_online !== undefined ? msg.user.is_online : false;
            // Parse created_at timestamp for sorting
            const messageTimestamp = msg.created_at 
                ? new Date(msg.created_at).toISOString()
                : new Date().toISOString();
            
            return `
                <div class="flex items-end gap-3.5 px-5" data-optimistic="false" data-message-id="${msg.id || ''}" data-message-text="${escapeHtml(msg.message)}" data-timestamp="${messageTimestamp}" data-chat-id="${currentChatId || ''}">
                    <div class="relative shrink-0">
                        <div class="kt-avatar size-9">
                            <div class="kt-avatar-image">
                                <img alt="${escapeHtml(msg.sender_name)}" class="size-9 rounded-full object-cover" src="${escapeHtml(avatarUrl)}" onerror="this.src='/assets/media/avatars/300-5.png'" />
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
    
    // Append new messages instead of replacing all
    if (newMessagesHTML) {
        messagesContainer.insertAdjacentHTML('beforeend', newMessagesHTML);
    }
    
    // Sort all messages by timestamp immediately after adding new ones (synchronously)
    sortMessagesInDOM();
    scrollToBottom();
    
    // AFTER rendering, remove optimistic messages that have been replaced by real messages
    // This ensures the real message is visible before we remove the optimistic one
    // Match optimistic messages to real messages by timestamp proximity (not just text)
    // This ensures duplicate messages are handled correctly
    optimisticMessagesData.forEach(({ element, text, timestamp: optimisticTimestamp, id: optimisticId }) => {
        if (!element || !element.parentNode) {
            return; // Element already removed
        }
        
        if (!text || !optimisticTimestamp) return;
        
        // Find the real message with the same text that is closest in time to this optimistic message
        const matchingMessages = messages.filter(msg => msg.message === text);
        if (matchingMessages.length === 0) {
            return;
        }
        
        // Find the message with the closest timestamp
        let closestMessage = null;
        let closestTimeDiff = Infinity;
        matchingMessages.forEach(msg => {
            if (msg.created_at) {
                const msgTimestamp = new Date(msg.created_at);
                const timeDiff = Math.abs(msgTimestamp.getTime() - optimisticTimestamp.getTime());
                if (timeDiff < closestTimeDiff) {
                    closestTimeDiff = timeDiff;
                    closestMessage = msg;
                }
            }
        });
        
        if (closestMessage && closestTimeDiff < 5000) { // Within 5 seconds
            // Use setTimeout to ensure the real message is rendered first
            setTimeout(() => {
                if (element && element.parentNode) {
                    // Check if this specific real message is in the DOM and visible
                    const realMessageInDOM = messagesContainer.querySelector(`[data-optimistic="false"][data-message-id="${closestMessage.id}"]`);
                    if (realMessageInDOM && realMessageInDOM.offsetParent !== null) {
                        // This specific real message exists in DOM and is visible, safe to remove this optimistic one
                        element.remove();
                        // Sort again immediately after removing optimistic message
                        sortMessagesInDOM();
                        scrollToBottom();
                    } else {
                        // Real message not yet in DOM or not visible, keep optimistic one
                    }
                }
            }, 150); // Increased delay to ensure DOM is fully updated
        } else {
            // Keep optimistic message if no close match found
        }
    });
    
    
    // After rendering, check if we have any messages and update empty message visibility
    // Use requestAnimationFrame to ensure DOM is updated
    requestAnimationFrame(() => {
        const allMessageElementsFinal = messagesContainer.querySelectorAll('.flex.items-end');
        const hasAnyMessagesFinal = allMessageElementsFinal.length > 0;
        
        // Update empty message visibility
        let emptyMessageFinal = messagesContainer.querySelector('.chat-empty-message');
        if (!hasAnyMessagesFinal) {
            // No messages at all - show empty message
            if (!emptyMessageFinal) {
                emptyMessageFinal = document.createElement('div');
                emptyMessageFinal.className = 'chat-empty-message text-center text-muted-foreground p-4';
                emptyMessageFinal.textContent = 'Geen berichten';
                messagesContainer.appendChild(emptyMessageFinal);
            }
        } else {
            // We have messages - remove empty message if it exists
            if (emptyMessageFinal) {
                emptyMessageFinal.remove();
            }
        }
    });
    
    // Scroll to bottom is already handled in sortMessagesInDOM via requestAnimationFrame
}

// Helper function to add a single message to the UI (optimistic update)
function addMessageToUI(messageText, isOptimistic = false) {
    const messagesContainer = document.getElementById('chat_messages');
    if (!messagesContainer) {
        return null;
    }
    
    // Remove "Geen berichten" message if present
    const emptyMessage = messagesContainer.querySelector('.chat-empty-message');
    if (emptyMessage) {
        emptyMessage.remove();
    }
    
    const userAvatar = currentChat && currentChat.user && currentChat.user.avatar 
        ? currentChat.user.avatar 
        : '/assets/media/avatars/300-2.png';
    
    const now = new Date();
    const timeString = now.toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' });
    const timestamp = now.toISOString();
    
    // Generate unique ID for optimistic messages
    const optimisticId = isOptimistic ? `optimistic-${Date.now()}-${Math.random().toString(36).substr(2, 9)}` : null;
    
    const messageElement = document.createElement('div');
    messageElement.className = 'flex items-end justify-end gap-3.5 px-5';
    messageElement.setAttribute('data-optimistic', isOptimistic ? 'true' : 'false');
    messageElement.setAttribute('data-message-text', escapeHtml(messageText));
    messageElement.setAttribute('data-timestamp', timestamp);
    messageElement.setAttribute('data-chat-id', String(currentChatId || ''));
    if (optimisticId) {
        messageElement.setAttribute('data-optimistic-id', optimisticId);
    }
    messageElement.innerHTML = `
        <div class="flex flex-col gap-1.5">
            <div class="kt-card bg-primary rounded-be-none flex flex-col gap-2.5 p-3 shadow-none">
                <p class="text-2sm text-primary-foreground font-medium">${escapeHtml(messageText)}</p>
            </div>
            <div class="relative flex items-center justify-end gap-2">
                <span class="text-xs font-medium text-secondary-foreground">${timeString}</span>
                ${isOptimistic ? '<i class="ki-filled ki-time text-lg text-gray-400"></i>' : ''}
            </div>
        </div>
        <div class="relative shrink-0">
            <div class="kt-avatar size-9">
                <div class="kt-avatar-image">
                    <img alt="You" class="size-9 rounded-full object-cover" src="${userAvatar}" />
                </div>
            </div>
        </div>
    `;
    
    messagesContainer.appendChild(messageElement);
    
    // Sort messages immediately after adding optimistic message (synchronously)
    sortMessagesInDOM();
    scrollToBottom();
    
    return messageElement;
}

// Send message
window.sendMessage = function() {
    const input = document.getElementById('chat_message_input');
    if (!input) {
        return;
    }
    
    if (!input.value || !input.value.trim()) {
        return;
    }
    
    if (!currentChatId) {
        return;
    }

    const message = input.value.trim();
    
    // Add message to UI immediately (optimistic update)
    const optimisticMessageElement = addMessageToUI(message, true);
    
    // Clear input immediately for better UX
    input.value = '';
    // Reset textarea height
    if (input.style) {
        input.style.height = 'auto';
    }

    const formData = new FormData();
    formData.append('message', message);

    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        // Remove optimistic message on error
        if (optimisticMessageElement) {
            optimisticMessageElement.remove();
        }
        input.value = message; // Restore message
        return;
    }

    fetch(`/admin/chat/${currentChatId}/message`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error(`HTTP error! status: ${response.status}, body: ${text}`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.message) {
            // Add the real message directly from the response FIRST
            const messagesContainer = document.getElementById('chat_messages');
            let realMessageAdded = false;
            
            if (messagesContainer) {
                // Check if message already exists by ID (not by text, so identical messages can both be shown)
                const existingMsg = data.message.id 
                    ? messagesContainer.querySelector(`[data-optimistic="false"][data-message-id="${data.message.id}"]`)
                    : null;
                if (!existingMsg) {
                    // Add message directly to DOM
                    const userAvatar = currentChat && currentChat.user && currentChat.user.avatar 
                        ? currentChat.user.avatar 
                        : '/assets/media/avatars/300-2.png';
                    
                    // Parse created_at timestamp for sorting
                    const messageTimestamp = data.message.created_at 
                        ? new Date(data.message.created_at).toISOString()
                        : new Date().toISOString();
                    
                    const messageHTML = `
                        <div class="flex items-end justify-end gap-3.5 px-5" data-optimistic="false" data-message-id="${data.message.id || ''}" data-message-text="${escapeHtml(data.message.message)}" data-timestamp="${messageTimestamp}" data-chat-id="${currentChatId || ''}">
                            <div class="flex flex-col gap-1.5">
                                <div class="kt-card bg-primary rounded-be-none flex flex-col gap-2.5 p-3 shadow-none">
                                    <p class="text-2sm text-primary-foreground font-medium">${escapeHtml(data.message.message)}</p>
                                </div>
                                <div class="relative flex items-center justify-end gap-2">
                                    <span class="text-xs font-medium text-secondary-foreground">${data.message.time}</span>
                                    ${data.message.is_read !== undefined ? `<i class="ki-filled ki-double-check text-lg ${data.message.is_read ? 'text-green-500' : 'text-gray-400'}"></i>` : ''}
                                </div>
                            </div>
                            <div class="relative shrink-0">
                                <div class="kt-avatar size-9">
                                    <div class="kt-avatar-image">
                                        <img alt="${data.message.sender_name}" class="size-9 rounded-full object-cover" src="${userAvatar}" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    messagesContainer.insertAdjacentHTML('beforeend', messageHTML);
                    realMessageAdded = true;
                    
                    // Update chat header status
                    if (currentChat) {
                        updateChatHeaderStatus(currentChat);
                    }
                    
                    // Sort messages immediately (synchronously) after adding
                    sortMessagesInDOM();
                    scrollToBottom();
                    
                    // Use requestAnimationFrame to ensure DOM is updated before removing optimistic
                    requestAnimationFrame(() => {
                        // Double check that the real message is in the DOM and visible
                        const realMsgInDOM = messagesContainer.querySelector(`[data-optimistic="false"][data-message-id="${data.message.id || ''}"]`);
                        if (realMsgInDOM && realMsgInDOM.offsetParent !== null) {
                            // Real message is confirmed in DOM and visible, safe to remove optimistic
                            if (optimisticMessageElement && optimisticMessageElement.parentNode) {
                                optimisticMessageElement.remove();
                                // Sort again immediately after removing optimistic
                                sortMessagesInDOM();
                                scrollToBottom();
                            }
                        } else {
                            // Real message not yet visible, keep optimistic for now
                            // Try again after a short delay
                            setTimeout(() => {
                                const realMsgInDOM2 = messagesContainer.querySelector(`[data-optimistic="false"][data-message-id="${data.message.id || ''}"]`);
                                if (realMsgInDOM2 && realMsgInDOM2.offsetParent !== null && optimisticMessageElement && optimisticMessageElement.parentNode) {
                                    optimisticMessageElement.remove();
                                    sortMessagesInDOM();
                                    scrollToBottom();
                                }
                            }, 50);
                        }
                    });
                    
                    scrollToBottom();
                } else {
                    // Message already exists, remove optimistic immediately
                    if (optimisticMessageElement) {
                        optimisticMessageElement.remove();
                    }
                }
            }
            
            // Update the current chat's last_message immediately
            if (currentChat && data.message) {
                currentChat.last_message = {
                    message: data.message.message,
                    created_at: data.message.created_at,
                    time: data.message.time
                };
                // Also update in activeChats array
                const chatIndex = activeChats.findIndex(c => c.id === currentChatId);
                if (chatIndex !== -1) {
                    activeChats[chatIndex].last_message = currentChat.last_message;
                    activeChats[chatIndex].updated_at = data.message.created_at || new Date().toISOString();
                    // Mark chat as active after sending message
                    activeChats[chatIndex].is_active = true;
                    activeChats[chatIndex].is_ended_by_other_party = false;
                    activeChats[chatIndex].is_ended_by_current_user = false;
                    activeChats[chatIndex].ended_at = null;
                }
            }
            
            // Update chat list item to remove gray styling and icon
            updateChatListItemAfterReactivation(currentChatId);
            
            // Reload messages once to sync with server (but don't wait for it)
            // Only reload if we successfully added the message, to avoid race conditions
            if (realMessageAdded) {
                setTimeout(() => {
                    loadChatMessages(currentChatId, false); // Don't show loader on retry
                }, 500); // Increased delay to ensure message is stable
                
                // Update chat list to refresh last_message (only if list view is visible)
                setTimeout(() => {
                    const chatListView = document.getElementById('chat_list_view');
                    if (chatListView && window.getComputedStyle(chatListView).display !== 'none') {
                        // Re-render immediately with updated data
                        renderChatList(activeChats);
                        // Then refresh from server
                        loadActiveChats(false).then(() => {
                            renderChatList(activeChats);
                        });
                    } else {
                        // List view not visible, just refresh data in background
                        loadActiveChats(false);
                    }
                }, 600);
            }
        } else {
            // Remove optimistic message on failure
            if (optimisticMessageElement) {
                optimisticMessageElement.remove();
            }
            input.value = message; // Restore message
        }
    })
    .catch(error => {
        // Remove optimistic message on error
        if (optimisticMessageElement) {
            optimisticMessageElement.remove();
        }
        input.value = message; // Restore message so user can try again
    });
};

// End chat
// Handle drawer close (used by close button and ESC key)
window.handleDrawerClose = function() {
    const drawer = document.getElementById('chat_drawer');
    const backdrop = document.getElementById('chat_drawer_backdrop');
    
    if (!drawer) return;
    
    // Don't close if drawer should stay open (e.g., after delete)
    if (drawer.getAttribute('data-user-opened') === 'true') {
        // Ensure drawer stays visible
        drawer.classList.remove('hidden');
        drawer.removeAttribute('data-drawer-closed');
        drawer.style.setProperty('display', 'flex', 'important');
        drawer.style.setProperty('visibility', 'visible', 'important');
        drawer.style.setProperty('opacity', '1', 'important');
        return;
    }
    
    
    // Set flag to prevent drawer from reopening
    isDrawerExplicitlyClosed = true;
    
    // Clear chat selection
    currentChatId = null;
    currentChat = null;
    
    // Function to force hide drawer
    const forceHideDrawer = () => {
        if (!drawer) return;
        
        // Set data attribute to mark drawer as closed (CSS will handle hiding)
        drawer.setAttribute('data-drawer-closed', 'true');
        
        // Remove all inline styles first, then set new ones
        drawer.removeAttribute('style');
        
        // Force hide drawer with inline styles (aggressive)
        drawer.classList.add('hidden');
        drawer.removeAttribute('data-chat-active');
        drawer.style.cssText = 'display: none !important; visibility: hidden !important; opacity: 0 !important; transform: translateX(100%) !important; right: -100% !important; left: unset !important; z-index: -1 !important;';
        
        // Also remove the 'open' class if present
        drawer.classList.remove('open');
    };
    
    // Function to force hide backdrop
    const forceHideBackdrop = () => {
        if (!backdrop) return;
        backdrop.removeAttribute('style');
        backdrop.classList.add('hidden');
        backdrop.style.cssText = 'display: none !important; visibility: hidden !important; opacity: 0 !important; z-index: -1 !important;';
    };
    
    // First, try to use KT Drawer instance to close properly
    if (window.KTDrawer) {
        const drawerInstance = window.KTDrawer.getInstance(drawer);
        if (drawerInstance) {
            try {
                drawerInstance.hide();
            } catch (e) {
            }
        }
    }
    
    // Also try toggle button to ensure drawer closes
    const drawerToggle = document.querySelector('[data-kt-drawer-toggle="#chat_drawer"]');
    if (drawerToggle) {
        // Check if drawer is currently open before toggling
        const isOpen = !drawer.classList.contains('hidden') || 
                      window.getComputedStyle(drawer).display !== 'none';
        if (isOpen) {
            try {
                drawerToggle.click();
            } catch (e) {
            }
        }
    }
    
    // Force hide immediately
    forceHideDrawer();
    forceHideBackdrop();
    
    // Apply multiple times with delays to override any library changes
    const delays = [0, 10, 50, 100, 200, 300, 500, 1000];
    delays.forEach(delay => {
        setTimeout(() => {
            forceHideDrawer();
            forceHideBackdrop();
        }, delay);
    });
    
    // Start aggressive interval to continuously hide drawer for 3 seconds
    let hideIntervalCount = 0;
    const hideInterval = setInterval(() => {
        if (drawer && isDrawerExplicitlyClosed) {
            forceHideDrawer();
            forceHideBackdrop();
            hideIntervalCount++;
            if (hideIntervalCount >= 30) { // Stop after 3 seconds (30 * 100ms)
                clearInterval(hideInterval);
            }
        } else {
            clearInterval(hideInterval);
        }
    }, 100);
};

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

        fetch(`/admin/chat/${currentChatId}/end`, {
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

// Delete chat (backend)
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

        // Store the chat ID before deletion
        const chatIdToDelete = currentChatId;
        if (!chatIdToDelete) {
            return;
        }

        fetch(`/admin/chat/${chatIdToDelete}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                
                // Remove chat from activeChats array
                activeChats = activeChats.filter(c => c.id !== chatIdToDelete);
                
                // Clear current chat selection if it was the deleted chat
                if (currentChatId === chatIdToDelete) {
                    currentChatId = null;
                    currentChat = null;
                }
                
                // Clear messages container
                const messagesContainer = document.getElementById('chat_messages');
                if (messagesContainer) messagesContainer.innerHTML = '';
                
                // Ensure drawer stays open - don't remove data-chat-active
                const drawer = document.getElementById('chat_drawer');
                if (drawer) {
                    // Disconnect observer temporarily to prevent interference
                    if (window.chatDrawerObserver) {
                        window.chatDrawerObserver.disconnect();
                    }
                    
                    // Keep drawer open, just show list view
                    drawer.setAttribute('data-user-opened', 'true');
                    drawer.setAttribute('data-chat-active', 'true');
                    drawer.removeAttribute('data-drawer-closed');
                    drawer.classList.remove('hidden');
                    
                    // Force drawer to be visible with inline styles
                    drawer.style.setProperty('display', 'flex', 'important');
                    drawer.style.setProperty('visibility', 'visible', 'important');
                    drawer.style.setProperty('opacity', '1', 'important');
                    drawer.style.setProperty('z-index', '99999', 'important');
                    drawer.style.setProperty('transform', 'translateX(0)', 'important');
                    drawer.style.setProperty('translate', '0 0', 'important');
                    drawer.style.setProperty('right', '1.25rem', 'important');
                    drawer.style.setProperty('left', 'auto', 'important');
                    drawer.style.setProperty('--tw-translate-x', '0', 'important');
                    drawer.style.setProperty('inset-inline-start', 'auto', 'important');
                    drawer.style.setProperty('top', '1.25rem', 'important');
                    drawer.style.setProperty('bottom', '1.25rem', 'important');
                    drawer.style.setProperty('width', '450px', 'important');
                    drawer.style.setProperty('max-width', '90%', 'important');
                    drawer.style.setProperty('position', 'fixed', 'important');
                    drawer.style.setProperty('margin-left', '0', 'important');
                    
                    // Ensure backdrop is visible
                    const backdrop = document.getElementById('chat_drawer_backdrop');
                    if (backdrop) {
                        backdrop.classList.remove('hidden');
                        backdrop.style.setProperty('display', 'block', 'important');
                        backdrop.style.setProperty('visibility', 'visible', 'important');
                        backdrop.style.setProperty('z-index', '99998', 'important');
                    }
                    
                    // Reconnect observer after a delay
                    setTimeout(() => {
                        if (window.chatDrawerObserver && drawer && drawer.getAttribute('data-user-opened') === 'true') {
                            window.chatDrawerObserver.observe(drawer, {
                                attributes: true,
                                attributeFilter: ['class']
                            });
                        }
                    }, 500);
                }
                
                // Show chat list (drawer stays open) - update DOM directly without full re-render
                const chatList = document.getElementById('chat_list');
                if (chatList) {
                    // Remove only the deleted chat item from DOM
                    const deletedChatItem = chatList.querySelector(`.chat-item[data-chat-id="${chatIdToDelete}"]`);
                    if (deletedChatItem) {
                        deletedChatItem.remove();
                    } else {
                        // If not found, re-render the list to ensure it's updated
                        renderChatList(activeChats);
                    }
                }
                
                // Switch to list view without full re-render
                const chatListView = document.getElementById('chat_list_view');
                const chatMessagesView = document.getElementById('chat_messages_view');
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
                
                // Update activeChats array to remove deleted chat (already done above)
                // Don't call loadActiveChats immediately to avoid flicker
                // The periodic update will refresh the list naturally
            }
        })
        .catch(error => {
        });
    });
};

// Start chat polling
function startChatPolling(chatId) {
    if (messagesInterval) clearInterval(messagesInterval);
    if (typingInterval) clearInterval(typingInterval);
    if (presenceInterval) clearInterval(presenceInterval);

    // Send initial presence when chat is opened
    if (isPageVisible()) {
        sendChatPresence(chatId);
    }

    // Poll every 500ms for faster updates
    // Presence is included in the messages response, so no separate request needed
    messagesInterval = setInterval(() => {
        if (currentChatId && !isWaitingForMessage && isPageVisible()) {
            loadChatMessages(currentChatId);
        }
    }, 500);

    typingInterval = setInterval(() => {
        if (currentChatId && isPageVisible()) {
            checkTyping(currentChatId);
        }
    }, 1000);

    // Send presence heartbeat every 8 seconds (combined with message polling)
    // Only send if page is visible to save resources
    presenceInterval = setInterval(() => {
        if (currentChatId && isPageVisible()) {
            sendChatPresence(currentChatId);
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
    fetch(`/admin/chat/${chatId}/presence`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
        },
        keepalive: true // Allows request to complete even if page is closed
    })
    .catch(error => {
        // Silently fail - presence is not critical
    });
}

// Check typing indicators
function checkTyping(chatId) {
    fetch(`/admin/chat/${chatId}/typing`, {
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
        }
    })
    .then(response => response.json())
    .then(typingUsers => {
        const typingIndicator = document.getElementById('chat_typing_indicator_header');
        if (typingIndicator) {
            if (typingUsers.length > 0) {
                const names = typingUsers.map(u => u.name).join(', ');
                typingIndicator.textContent = `${names} ${typingUsers.length === 1 ? 'is' : 'zijn'} aan het typen...`;
                typingIndicator.style.display = 'block';
            } else {
                typingIndicator.style.display = 'none';
            }
        }
    })
    .catch(error => {
    });
}

// Send typing indicator
window.onChatMessageInput = function() {
    const input = document.getElementById('chat_message_input');
    if (!input || !currentChatId) return;

    fetch(`/admin/chat/${currentChatId}/typing`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
        }
    });
};

// Scroll to bottom
function scrollToBottom(force = false) {
    // Don't auto-scroll if user has manually scrolled (unless forced)
    if (!force && userHasScrolled) {
        updateScrollToBottomButton();
        return;
    }
    
    // Use requestAnimationFrame to ensure DOM is updated
    requestAnimationFrame(() => {
        const messagesContainer = document.getElementById('chat_messages');
        if (messagesContainer) {
            // Find the scrollable parent container
            const scrollableParent = messagesContainer.closest('.kt-scrollable-y-auto');
            if (scrollableParent) {
                isAutoScrolling = true;
                scrollableParent.scrollTop = scrollableParent.scrollHeight;
                // Reset userHasScrolled after auto-scrolling
                setTimeout(() => {
                    isAutoScrolling = false;
                    userHasScrolled = false;
                    updateScrollToBottomButton();
                }, 100);
            } else {
                // Fallback to scrolling the messages container itself
                isAutoScrolling = true;
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
                setTimeout(() => {
                    isAutoScrolling = false;
                    userHasScrolled = false;
                    updateScrollToBottomButton();
                }, 100);
            }
        }
    });
}

// Update scroll to bottom button visibility
function updateScrollToBottomButton() {
    const scrollableParent = document.querySelector('#chat_messages_view .kt-scrollable-y-auto');
    const scrollButton = document.getElementById('scroll_to_bottom_btn');
    
    if (!scrollableParent || !scrollButton) return;
    
    const isAtBottom = scrollableParent.scrollHeight - scrollableParent.scrollTop <= scrollableParent.clientHeight + 50; // 50px threshold
    
    if (isAtBottom) {
        scrollButton.style.display = 'none';
    } else {
        scrollButton.style.display = 'flex';
    }
}

// Scroll to bottom button click handler
window.scrollToBottomManually = function() {
    userHasScrolled = false; // Reset flag
    scrollToBottom(true); // Force scroll
};

// Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initialize chat when drawer is opened
document.addEventListener('DOMContentLoaded', function() {
    const drawer = document.getElementById('chat_drawer');
    const backdrop = document.getElementById('chat_drawer_backdrop');
    
    // Ensure drawer is hidden by default
    if (drawer) {
        drawer.classList.add('hidden');
        drawer.style.display = 'none';
        drawer.style.visibility = 'hidden';
        drawer.style.opacity = '0';
    }
    if (backdrop) {
        backdrop.classList.add('hidden');
        backdrop.style.display = 'none';
        backdrop.style.visibility = 'hidden';
    }
    
    if (drawer) {
        // Listen for drawer open events
        let observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    // Don't interfere if drawer is marked to ignore observer
                    if (drawer && drawer.getAttribute('data-ignore-observer') === 'true') {
                        // Silently skip - no logging to reduce console spam
                        return;
                    }
                    
                    // Don't interfere if drawer was explicitly closed
                    if (isDrawerExplicitlyClosed || (drawer && drawer.getAttribute('data-drawer-closed') === 'true')) {
                        // Silently skip - no logging to reduce console spam
                        return;
                    }
                    
                    const isOpen = !drawer.classList.contains('hidden');
                    // Only log when state actually changes (not on every mutation)
                    // Removed frequent logging to reduce console spam
                    
                    if (isOpen) {
                        // Don't interfere if we're opening a chat programmatically
                        if (isOpeningChat) {
                            // Silently skip - no logging to reduce console spam
                            return;
                        }
                        
                        // Skip if we're currently updating styles to prevent infinite loop
                        if (isUpdatingStyles) {
                            return;
                        }
                        
                        // Set flag to prevent style observer from triggering
                        isUpdatingStyles = true;
                        
                        try {
                            // Remove inline styles that might hide the drawer and ensure drawer is fully visible
                            drawer.style.setProperty('display', 'flex', 'important');
                            drawer.style.setProperty('visibility', 'visible', 'important');
                            drawer.style.setProperty('opacity', '1', 'important');
                            drawer.style.setProperty('z-index', '99999', 'important');
                            drawer.style.setProperty('transform', 'translateX(0)', 'important'); // Reset transform to ensure drawer is fully visible
                            drawer.style.setProperty('right', '1.25rem', 'important'); // Ensure right positioning
                            drawer.style.setProperty('left', 'auto', 'important'); // Force left to unset
                            backdrop.style.setProperty('display', 'block', 'important');
                            backdrop.style.setProperty('visibility', 'visible', 'important');
                            backdrop.style.setProperty('z-index', '99998', 'important');
                        } finally {
                            setTimeout(() => {
                                isUpdatingStyles = false;
                            }, 10);
                        }
                        // Show backdrop
                        if (backdrop) {
                            backdrop.classList.remove('hidden');
                        }
                        // Only show chat list view if no chat is selected
                        const chatListView = document.getElementById('chat_list_view');
                        const chatMessagesView = document.getElementById('chat_messages_view');
                        if (currentChatId) {
                            // Chat is selected, show messages view
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
                        } else {
                            // No chat selected, show list view
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
                        }
                        // Load chats when drawer opens (always load, like frontend)
                        loadActiveChats(false, false).then(() => {
                            // Ensure chat list is rendered when drawer opens
                            if (!currentChatId) {
                                renderChatList(activeChats);
                            }
                        });
                        // Setup candidate select listener
                        setTimeout(() => {
                            const candidateSelect = document.getElementById('chat_candidate_select');
                            if (candidateSelect && !candidateSelect.hasAttribute('data-listener-attached')) {
                                candidateSelect.addEventListener('change', handleCandidateSelect);
                                candidateSelect.setAttribute('data-listener-attached', 'true');
                            }
                        }, 100);
                    } else {
                        // Drawer is being closed - check if we should keep it open
                        const hasActiveChat = currentChatId || isOpeningChat || drawer?.getAttribute('data-chat-active') === 'true';
                        const hasUserOpened = drawer?.getAttribute('data-user-opened') === 'true';
                        const shouldKeepOpen = hasActiveChat || hasUserOpened;
                        
                        if (shouldKeepOpen) {
                            // Keep drawer open if chat is active or user opened it
                            if (drawer) {
                                drawer.classList.remove('hidden');
                                drawer.removeAttribute('data-drawer-closed');
                                
                                // Skip if we're currently updating styles to prevent infinite loop
                                if (!isUpdatingStyles) {
                                    isUpdatingStyles = true;
                                    try {
                                        drawer.style.setProperty('display', 'flex', 'important');
                                        drawer.style.setProperty('visibility', 'visible', 'important');
                                        drawer.style.setProperty('opacity', '1', 'important');
                                        drawer.style.setProperty('z-index', '99999', 'important');
                                        drawer.style.setProperty('transform', 'translateX(0)', 'important'); // Reset transform to ensure drawer is fully visible
                                        drawer.style.setProperty('right', '1.25rem', 'important'); // Ensure right positioning
                                        drawer.style.setProperty('left', 'auto', 'important'); // Force left to unset
                                        
                                        // Set data-chat-active if user opened it, even if no currentChatId
                                        if (hasUserOpened) {
                                            drawer.setAttribute('data-chat-active', 'true');
                                        }
                                    } finally {
                                        setTimeout(() => {
                                            isUpdatingStyles = false;
                                        }, 10);
                                    }
                                }
                            }
                            if (backdrop) {
                                backdrop.classList.remove('hidden');
                                backdrop.style.display = 'block';
                                backdrop.style.visibility = 'visible';
                            }
                        } else {
                            // Hide drawer and backdrop only if no chat is active and user didn't open it
                            // BUT: Don't set data-drawer-closed if data-user-opened is true (e.g., after delete)
                            if (drawer && drawer.getAttribute('data-user-opened') === 'true') {
                                // User explicitly opened drawer (e.g., after delete), keep it open
                                return;
                            }
                            
                            if (drawer) {
                                drawer.removeAttribute('data-chat-active');
                                drawer.removeAttribute('data-user-opened');
                                drawer.setAttribute('data-drawer-closed', 'true');
                                drawer.style.display = 'none';
                                drawer.style.visibility = 'hidden';
                                drawer.style.opacity = '0';
                            }
                            if (backdrop) {
                                backdrop.classList.add('hidden');
                                backdrop.style.display = 'none';
                                backdrop.style.visibility = 'hidden';
                            }
                        }
                    }
                }
            });
        });
        
        // Store observer reference globally so we can disconnect/reconnect it
        window.chatDrawerObserver = observer;
        
        observer.observe(drawer, {
            attributes: true,
            attributeFilter: ['class']
        });
        
        // Also observe style changes to immediately reset transform if KT Drawer library changes it
        const styleObserver = new MutationObserver(function(mutations) {
            // Skip if we're currently updating styles ourselves to prevent infinite loop
            if (isUpdatingStyles) {
                return;
            }
            
            // Don't interfere if drawer was explicitly closed
            if (isDrawerExplicitlyClosed || (drawer && drawer.getAttribute('data-drawer-closed') === 'true')) {
                return;
            }
            
            if (drawer && drawer.getAttribute('data-chat-active') === 'true') {
                // Use requestAnimationFrame to check after any style changes have been applied
                requestAnimationFrame(() => {
                    // Skip if we're currently updating styles ourselves
                    if (isUpdatingStyles) {
                        return;
                    }
                    
                    const computedTransform = window.getComputedStyle(drawer).transform;
                    const computedRight = window.getComputedStyle(drawer).right;
                    const computedLeft = window.getComputedStyle(drawer).left;
                    
                    // Check if any reset is needed
                    const needsTransformReset = computedTransform !== 'none' && computedTransform !== 'matrix(1, 0, 0, 1, 0, 0)';
                    const needsRightReset = computedRight !== '1.25rem' && computedRight !== '20px';
                    const needsLeftReset = computedLeft !== 'auto' && computedLeft !== 'unset';
                    
                    // Only update if something actually needs to be reset
                    if (needsTransformReset || needsRightReset || needsLeftReset) {
                        // Set flag to prevent observer from triggering itself
                        isUpdatingStyles = true;
                        
                        try {
                            // If transform is not translateX(0) or right is not correct, reset it
                            if (needsTransformReset) {
                                drawer.style.setProperty('transform', 'translateX(0)', 'important');
                            }
                            if (needsRightReset) {
                                drawer.style.setProperty('right', '1.25rem', 'important');
                            }
                            if (needsLeftReset) {
                                // First, remove left property directly from style object
                                if (drawer.style.left) {
                                    drawer.style.removeProperty('left');
                                }
                                
                                // Then check and clean inline style attribute
                                const inlineStyle = drawer.getAttribute('style');
                                if (inlineStyle) {
                                    const cleanedStyle = inlineStyle.replace(/left\s*:\s*[^;!]+(!important)?;?/gi, '').trim();
                                    if (cleanedStyle !== inlineStyle) {
                                        drawer.setAttribute('style', cleanedStyle);
                                    }
                                }
                                
                                // Finally, force unset with !important
                                drawer.style.setProperty('left', 'auto', 'important');
                            }
                        } finally {
                            // Reset flag after a short delay to allow any triggered mutations to settle
                            setTimeout(() => {
                                isUpdatingStyles = false;
                            }, 50);
                        }
                    }
                });
            }
        });
        
        // Observe both style and class changes, and also childList in case drawer content changes
        styleObserver.observe(drawer, {
            attributes: true,
            attributeFilter: ['style', 'class'],
            attributeOldValue: false,
            childList: false,
            subtree: false
        });
        
        // Periodically check and ensure drawer stays open if a chat is selected
        // This prevents the drawer from closing automatically when a chat is active
        // Performance: Run every 200ms instead of 10ms to reduce CPU usage
        let drawerCheckInterval = setInterval(function() {
            // Skip if we're currently updating styles to prevent observer interference
            if (isUpdatingStyles) {
                return;
            }
            
            // If drawer was explicitly closed, actively hide it
            // BUT: Don't close if data-user-opened is true (e.g., after delete)
            const hasUserOpened = drawer && drawer.getAttribute('data-user-opened') === 'true';
            if ((isDrawerExplicitlyClosed || (drawer && drawer.getAttribute('data-drawer-closed') === 'true')) && !hasUserOpened) {
                if (drawer) {
                    drawer.setAttribute('data-drawer-closed', 'true');
                    drawer.classList.add('hidden');
                    drawer.removeAttribute('data-chat-active');
                    drawer.style.setProperty('display', 'none', 'important');
                    drawer.style.setProperty('visibility', 'hidden', 'important');
                    drawer.style.setProperty('opacity', '0', 'important');
                    drawer.style.setProperty('transform', 'translateX(100%)', 'important');
                    drawer.style.setProperty('right', '-100%', 'important');
                    drawer.style.setProperty('z-index', '-1', 'important');
                    drawer.classList.remove('open');
                }
                if (backdrop) {
                    backdrop.classList.add('hidden');
                    backdrop.style.setProperty('display', 'none', 'important');
                    backdrop.style.setProperty('visibility', 'hidden', 'important');
                    backdrop.style.setProperty('opacity', '0', 'important');
                    backdrop.style.setProperty('z-index', '-1', 'important');
                }
                return;
            }
            
            // If drawer should stay open (e.g., after delete), ensure it stays visible
            if (hasUserOpened && drawer) {
                drawer.setAttribute('data-chat-active', 'true');
                drawer.removeAttribute('data-drawer-closed');
                drawer.classList.remove('hidden');
                drawer.style.setProperty('display', 'flex', 'important');
                drawer.style.setProperty('visibility', 'visible', 'important');
                drawer.style.setProperty('opacity', '1', 'important');
                drawer.style.setProperty('z-index', '99999', 'important');
                drawer.style.setProperty('transform', 'translateX(0)', 'important');
                drawer.style.setProperty('right', '1.25rem', 'important');
                drawer.style.setProperty('left', 'unset', 'important');
                drawer.style.setProperty('top', '1.25rem', 'important');
                drawer.style.setProperty('bottom', '1.25rem', 'important');
                drawer.style.setProperty('width', '450px', 'important');
                drawer.style.setProperty('max-width', '90%', 'important');
                drawer.style.setProperty('position', 'fixed', 'important');
                drawer.style.setProperty('margin-left', '0', 'important');
                
                if (backdrop) {
                    backdrop.classList.remove('hidden');
                    backdrop.style.setProperty('display', 'block', 'important');
                    backdrop.style.setProperty('visibility', 'visible', 'important');
                    backdrop.style.setProperty('z-index', '99998', 'important');
                }
            }
            
            const hasActiveChat = currentChatId || isOpeningChat || (drawer && drawer.getAttribute('data-chat-active') === 'true');
            
            if (hasActiveChat && drawer) {
                // Ensure drawer stays open when chat is active
                const isMarkedHidden = drawer.classList.contains('hidden');
                const computedDisplay = window.getComputedStyle(drawer).display;
                
                if (isMarkedHidden || computedDisplay === 'none') {
                    drawer.classList.remove('hidden');
                }
                
                // Set flag to prevent observer from triggering
                isUpdatingStyles = true;
                
                try {
                    // Force drawer to be visible with !important
                    drawer.style.setProperty('display', 'flex', 'important');
                    drawer.style.setProperty('visibility', 'visible', 'important');
                    drawer.style.setProperty('opacity', '1', 'important');
                    drawer.style.setProperty('z-index', '99999', 'important');
                    drawer.style.setProperty('transform', 'translateX(0)', 'important'); // Reset transform to ensure drawer is fully visible
                    drawer.style.setProperty('translate', '0 0', 'important'); // Reset translate property (used by KT Drawer)
                    drawer.style.setProperty('right', '1.25rem', 'important'); // Ensure right positioning
                    drawer.style.setProperty('left', 'auto', 'important');
                    drawer.style.setProperty('top', '1.25rem', 'important');
                    drawer.style.setProperty('bottom', '1.25rem', 'important');
                    drawer.style.setProperty('width', '450px', 'important');
                    drawer.style.setProperty('max-width', '90%', 'important');
                    drawer.style.setProperty('position', 'fixed', 'important');
                    drawer.style.setProperty('margin-left', '0', 'important');
                    drawer.style.setProperty('inset-inline-start', 'auto', 'important');
                    // Reset CSS custom properties used by KT Drawer
                    drawer.style.setProperty('--tw-translate-x', '0', 'important');
                    drawer.setAttribute('data-chat-active', 'true');
                    
                    // Always aggressively reset left property, regardless of current value
                    // First, remove left property directly from style object
                    if (drawer.style.left) {
                        drawer.style.removeProperty('left');
                    }
                    // Force left to auto to prevent drawer from moving to the right
                    drawer.style.setProperty('left', 'auto', 'important');
                    
                    // Then check and clean inline style attribute
                    const inlineStyle = drawer.getAttribute('style');
                    if (inlineStyle) {
                        const cleanedStyle = inlineStyle.replace(/left\s*:\s*[^;!]+(!important)?;?/gi, '').trim();
                        if (cleanedStyle !== inlineStyle) {
                            drawer.setAttribute('style', cleanedStyle);
                        }
                    }
                    
                    // Finally, force unset with !important
                    drawer.style.setProperty('left', 'auto', 'important');
                    drawer.style.setProperty('transition', 'none', 'important'); // Disable transitions
                    drawer.style.setProperty('animation', 'none', 'important'); // Disable animations
                } finally {
                    // Reset flag after a short delay
                    setTimeout(() => {
                        isUpdatingStyles = false;
                    }, 10);
                }
                
                // Also check computed styles and reset if needed
                const computedTransform = window.getComputedStyle(drawer).transform;
                const computedRight = window.getComputedStyle(drawer).right;
                const computedLeft = window.getComputedStyle(drawer).left;
                if (computedTransform !== 'none' && computedTransform !== 'matrix(1, 0, 0, 1, 0, 0)') {
                    drawer.style.setProperty('transform', 'translateX(0)', 'important');
                }
                if (computedRight !== '1.25rem' && computedRight !== '20px') {
                    drawer.style.setProperty('right', '1.25rem', 'important');
                }
                // Always reset left, even if it seems correct
                if (computedLeft !== 'unset' && computedLeft !== 'auto') {
                    // Aggressively reset left
                    if (drawer.style.left) {
                        drawer.style.removeProperty('left');
                    }
                    const inlineStyle2 = drawer.getAttribute('style');
                    if (inlineStyle2) {
                        const cleanedStyle2 = inlineStyle2.replace(/left\s*:\s*[^;!]+(!important)?;?/gi, '').trim();
                        if (cleanedStyle2 !== inlineStyle2) {
                            drawer.setAttribute('style', cleanedStyle2);
                        }
                    }
                    drawer.style.setProperty('left', 'auto', 'important');
                }
                
                // Ensure backdrop is visible
                if (backdrop) {
                    backdrop.classList.remove('hidden');
                    backdrop.style.setProperty('display', 'block', 'important');
                    backdrop.style.setProperty('visibility', 'visible', 'important');
                    backdrop.style.setProperty('z-index', '99998', 'important');
                }
            } else if (!hasActiveChat && drawer && drawer.getAttribute('data-chat-active') === 'true') {
                // Remove active flag if no chat is active
                drawer.removeAttribute('data-chat-active');
            }
        }, 200); // Performance: Check every 200ms instead of 10ms to reduce CPU usage
    }
    
    // Handle backdrop click to close drawer
    if (backdrop) {
        backdrop.addEventListener('click', function() {
            const drawer = document.getElementById('chat_drawer');
            // Don't close if drawer should stay open (e.g., after delete)
            if (drawer && drawer.getAttribute('data-user-opened') === 'true') {
                return;
            }
            if (window.handleDrawerClose) {
                window.handleDrawerClose();
            }
        });
    }
    
    // Handle chat toggle button click to ensure drawer is fully visible
    const chatToggleButton = document.querySelector('[data-kt-drawer-toggle="#chat_drawer"]');
    if (chatToggleButton) {
        chatToggleButton.addEventListener('click', function(e) {
            // Check current state BEFORE toggle
            const isCurrentlyHidden = drawer && (drawer.classList.contains('hidden') || window.getComputedStyle(drawer).display === 'none');
            
            if (isCurrentlyHidden) {
                // Drawer is currently closed, so clicking will OPEN it
                // Reset flag immediately to allow drawer to open
                isDrawerExplicitlyClosed = false;
                if (drawer) {
                    drawer.removeAttribute('data-drawer-closed');
                }
            } else {
                // Drawer is currently open, so clicking will CLOSE it
                // Set flag to mark as explicitly closed
                isDrawerExplicitlyClosed = true;
                if (drawer) {
                    drawer.setAttribute('data-drawer-closed', 'true');
                }
            }
            
            // Use setTimeout to check drawer state after toggle (as backup)
            setTimeout(() => {
                const isNowHidden = drawer && (drawer.classList.contains('hidden') || window.getComputedStyle(drawer).display === 'none');
                if (isNowHidden) {
                    // Drawer was closed, ensure flag is set
                    isDrawerExplicitlyClosed = true;
                    if (drawer) {
                        drawer.setAttribute('data-drawer-closed', 'true');
                    }
                } else {
                    // Drawer was opened, ensure flag is reset
                    isDrawerExplicitlyClosed = false;
                    if (drawer) {
                        drawer.removeAttribute('data-drawer-closed');
                    }
                }
            }, 50);
            
            const forceTransformReset = () => {
                if (drawer) {
                    drawer.classList.remove('hidden');
                    drawer.style.setProperty('display', 'flex', 'important');
                    drawer.style.setProperty('visibility', 'visible', 'important');
                    drawer.style.setProperty('opacity', '1', 'important');
                    drawer.style.setProperty('z-index', '99999', 'important');
                    drawer.style.setProperty('transform', 'translateX(0)', 'important'); // Reset transform to ensure drawer is fully visible
                    drawer.style.setProperty('right', '1.25rem', 'important'); // Ensure right positioning
                    drawer.style.setProperty('left', 'auto', 'important'); // Force left to auto
                    drawer.style.setProperty('transition', 'none', 'important'); // Disable transitions
                    drawer.style.setProperty('animation', 'none', 'important'); // Disable animations
                }
                if (backdrop) {
                    backdrop.classList.remove('hidden');
                    backdrop.style.setProperty('display', 'block', 'important');
                    backdrop.style.setProperty('visibility', 'visible', 'important');
                    backdrop.style.setProperty('z-index', '99998', 'important');
                }
            };
            
            // Apply immediately
            forceTransformReset();
            
            // Apply multiple times to ensure it sticks
            requestAnimationFrame(() => {
                forceTransformReset();
                requestAnimationFrame(() => {
                    forceTransformReset();
                });
            });
            
            setTimeout(forceTransformReset, 10);
            setTimeout(forceTransformReset, 50);
            setTimeout(forceTransformReset, 100);
            setTimeout(forceTransformReset, 200);
        });
    }
    
    // Handle close button clicks
    if (drawer) {
        const closeButtons = drawer.querySelectorAll('[data-kt-drawer-dismiss="true"]');
        closeButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (window.handleDrawerClose) {
                    window.handleDrawerClose();
                }
            });
        });
    }
    
    // Handle candidate select change
    const candidateSelect = document.getElementById('chat_candidate_select');
    if (candidateSelect) {
        candidateSelect.addEventListener('change', handleCandidateSelect);
    }
    
    // Initialize chat message input on page load
    setTimeout(() => {
        initializeChatInput();
    }, 500);
    
    // Update chat list periodically - only if not viewing a chat
    // This ensures unread counts stay accurate
    chatListUpdateInterval = setInterval(() => {
        if (!currentChatId && !isLoadingChats) {
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
    
    // Handle ESC key to close drawer
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' || e.keyCode === 27) {
            const drawer = document.getElementById('chat_drawer');
            if (drawer) {
                const computedDisplay = window.getComputedStyle(drawer).display;
                const isVisible = computedDisplay !== 'none' && !drawer.classList.contains('hidden');
                if (isVisible) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    if (window.handleDrawerClose) {
                        window.handleDrawerClose();
                    } else {
                        // Fallback if function doesn't exist
                        drawer.classList.add('hidden');
                        drawer.style.cssText = 'display: none !important; visibility: hidden !important; opacity: 0 !important;';
                        const backdrop = document.getElementById('chat_drawer_backdrop');
                        if (backdrop) {
                            backdrop.classList.add('hidden');
                            backdrop.style.cssText = 'display: none !important; visibility: hidden !important; opacity: 0 !important;';
                        }
                    }
                }
            }
        }
    }, true); // Use capture phase to catch event early
    
    // Handle clicks outside drawer to close it (same as ESC)
    document.addEventListener('click', function(e) {
        const drawer = document.getElementById('chat_drawer');
        const backdrop = document.getElementById('chat_drawer_backdrop');
        
        if (!drawer) return;
        
        // Don't interfere if drawer was explicitly closed
        if (isDrawerExplicitlyClosed || drawer.getAttribute('data-drawer-closed') === 'true') {
            return;
        }
        
        const computedDisplay = window.getComputedStyle(drawer).display;
        const isVisible = computedDisplay !== 'none' && !drawer.classList.contains('hidden');
        
        if (!isVisible) return;
        
        // Check if click is outside the drawer
        const clickedElement = e.target;
        const isClickInsideDrawer = drawer.contains(clickedElement);
        const isClickOnBackdrop = backdrop && (backdrop === clickedElement || backdrop.contains(clickedElement));
        const isClickOnToggle = clickedElement.closest('[data-kt-drawer-toggle="#chat_drawer"]');
        
        // Close drawer if click is outside (on backdrop or outside both drawer and backdrop)
        // But don't close if clicking on the toggle button (it will handle its own toggle)
        if (!isClickInsideDrawer && !isClickOnToggle) {
            // Don't close if drawer should stay open (e.g., after delete)
            if (drawer.getAttribute('data-user-opened') === 'true') {
                return;
            }
            if (window.handleDrawerClose) {
                window.handleDrawerClose();
            }
        }
    });
});

