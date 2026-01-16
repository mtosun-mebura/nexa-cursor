// Global chat functionality
console.log('📦 chat.js loaded!');

let activeChats = [];
let currentChatId = null;
let typingInterval = null;
let messagesInterval = null;
let chatListUpdateInterval = null; // Interval for updating chat list
let currentChat = null; // Store current chat data including user avatar
let isOpeningChat = false; // Flag to prevent observer interference when opening a chat
let isUpdatingStyles = false; // Flag to prevent style observer from triggering itself
let isDrawerExplicitlyClosed = false; // Flag to prevent drawer from reopening when explicitly closed
let pendingOptimisticMessages = new Map(); // Store optimistic messages by message text and chatId
let isWaitingForMessage = false; // Flag to prevent polling from interfering during message retry
let userHasScrolled = false; // Track if user has manually scrolled
let isAutoScrolling = false; // Track if we're auto-scrolling to prevent scroll event from interfering

// Open chat with candidate
window.openChatWithCandidate = function(candidateId, matchOrAppId, type) {
    console.log('🚀 openChatWithCandidate called!', { candidateId, matchOrAppId, type });
    
    if (!candidateId) {
        console.error('❌ No candidateId provided!');
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
                console.log('✅ Chat started successfully:', data.chat_id);
                
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
                    console.log('✅ Chat added to activeChats:', data.chat);
                }
                
                // Ensure drawer is open and positioned correctly
                if (drawer) {
                    // Mark as active
                    drawer.setAttribute('data-chat-active', 'true');
                    drawer.setAttribute('data-ignore-observer', 'true');
                    
                    // Force transform reset to ensure correct positioning
                    const forceTransformReset = () => {
                        // Set flag to prevent observer from triggering
                        isUpdatingStyles = true;
                        
                        try {
                            drawer.style.setProperty('transform', 'translateX(0)', 'important');
                            drawer.style.setProperty('right', '1.25rem', 'important');
                            drawer.style.setProperty('left', 'unset', 'important');
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
                            
                            drawer.style.setProperty('left', 'unset', 'important');
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
                console.log('✅ Selecting chat:', data.chat_id);
                selectChat(data.chat_id);
            
                // Remove ignore flag after a delay to allow normal operation
                setTimeout(() => {
                    if (drawer) {
                        drawer.removeAttribute('data-ignore-observer');
                        console.log('✅ Removed ignore-observer flag');
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
                    loadActiveChats(false).then(() => {
                        // Re-render chat list if it's visible to update last_message
                        const chatListView = document.getElementById('chat_list_view');
                        if (chatListView && window.getComputedStyle(chatListView).display !== 'none') {
                            renderChatList(activeChats);
                        }
                    });
                }, 500);
                
                // Clear flag after everything is set up
                setTimeout(() => {
                    isOpeningChat = false;
                }, 3000);
            }
        })
        .catch(error => {
            console.error('Error starting chat:', error);
            isOpeningChat = false;
        });
    }, 100); // Wait 100ms for drawer to open before starting chat
};

// Get CSRF token from meta tag
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

// Load active chats
window.loadActiveChats = function(showListView = false) {
    console.log('📋 loadActiveChats called, showListView:', showListView);
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
        console.log('✅ Active chats loaded from server:', chats);
        console.log('📊 Server returned', chats.length, 'chats');
        
        // Validate that chats is an array
        if (!Array.isArray(chats)) {
            console.error('❌ Server response is not an array:', chats);
            return activeChats;
        }
        
        // If there's an error in the response, log it
        if (chats.error) {
            console.error('❌ Server returned error:', chats.error);
            return activeChats;
        }
        
        // Start with server response as base (most up-to-date)
        // Replace the entire array instead of merging to ensure fresh data
        const serverChatIds = chats.map(c => c.id);
        const previousChatIds = activeChats.map(c => c.id);
        
        console.log('📊 Previous chats:', previousChatIds);
        console.log('📊 Server chats:', serverChatIds);
        
        // Preserve current chat if it exists and is not in server response
        const currentChatInArray = activeChats.find(c => c.id === currentChatId);
        if (currentChatId && currentChatInArray && !serverChatIds.includes(currentChatId)) {
            chats.push(currentChatInArray);
            console.log('📌 Preserved current chat that is not in server response');
        }
        
        // Replace the entire array with server data (don't merge)
        const oldLength = activeChats.length;
        activeChats = [...chats]; // Create a new array from server data
        
        console.log(`🔄 Replaced ${oldLength} chats with ${activeChats.length} chats from server`);
        console.log('📋 New activeChats array:', activeChats.map(c => ({ id: c.id, candidate: c.candidate?.name || 'Unknown' })));
        
        // If no chat is selected, or explicitly asked to show list view (e.g., from header button), ensure it's visible
        const chatListView = document.getElementById('chat_list_view');
        const chatMessagesView = document.getElementById('chat_messages_view');
        
        if (!currentChatId || showListView) {
            // Show list view, hide messages view
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
                currentChatId = null; // Clear current chat when explicitly showing list view
                currentChat = null;
                console.log('📋 Switched to chat list view, currentChatId cleared.');
            }
        }
        
        // Always render the list to ensure it's up to date
        renderChatList(activeChats);
        return activeChats;
    })
    .catch(error => {
        console.error('❌ Error loading chats:', error);
        return activeChats;
    });
};

// Render chat list
function renderChatList(chats) {
    console.log('📋 renderChatList called with chats:', chats);
    const chatList = document.getElementById('chat_list');
    const emptyState = document.getElementById('chat_list_empty');
    const chatListView = document.getElementById('chat_list_view');
    
    if (!chatList) {
        console.error('❌ chat_list element not found!');
        return;
    }
    
    // Ensure chat_list_view is visible (only if no chat is selected)
    if (chatListView && !currentChatId) {
        chatListView.style.setProperty('display', 'flex', 'important');
        chatListView.style.setProperty('visibility', 'visible', 'important');
        chatListView.style.setProperty('opacity', '1', 'important');
        console.log('✅ chat_list_view is now visible');
    } else if (chatListView && currentChatId) {
        // If a chat is selected, ensure list view is hidden
        chatListView.style.setProperty('display', 'none', 'important');
        chatListView.style.setProperty('visibility', 'hidden', 'important');
        chatListView.style.setProperty('opacity', '0', 'important');
    } else {
        console.error('❌ chat_list_view element not found!');
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
    existingChatItems.forEach(item => item.remove());
    if (existingCount > 0) {
        console.log(`🗑️ Removed ${existingCount} existing chat item(s) from DOM`);
    }
    
    if (chats.length === 0) {
        console.log('📋 No chats to display, showing empty state');
        // Show empty state with candidate dropdown
        return;
    }

    console.log('📋 Rendering', chats.length, 'chats');
    
    // Sort chats by latest message timestamp (descending)
    const sortedChats = [...chats].sort((a, b) => {
        // Use last_message (from server) or updated_at as fallback
        const timeA = a.last_message ? new Date(a.last_message.created_at).getTime() : (a.updated_at ? new Date(a.updated_at).getTime() : 0);
        const timeB = b.last_message ? new Date(b.last_message.created_at).getTime() : (b.updated_at ? new Date(b.updated_at).getTime() : 0);
        return timeB - timeA; // Latest first
    });
    
    console.log('📋 Sorted chats:', sortedChats.map(c => ({ id: c.id, latest: c.last_message?.created_at || c.updated_at || 'none' })));
    
    // Render all chats (use sorted array)
    sortedChats.forEach((chat, index) => {
        const chatItem = document.createElement('div');
        chatItem.className = `chat-item p-3 border-b border-border cursor-pointer hover:bg-muted/50 ${chat.id === currentChatId ? 'bg-muted/30' : ''}`;
        chatItem.onclick = () => selectChat(chat.id);
        
        const candidateName = chat.candidate && chat.candidate.name ? chat.candidate.name : 'Onbekend';
        const lastMessage = chat.last_message ? chat.last_message.message : '';
        const lastMessageTime = chat.last_message && chat.last_message.time ? chat.last_message.time : '';
        
        const candidateAvatar = chat.candidate && chat.candidate.avatar ? chat.candidate.avatar : null;
        
        chatItem.innerHTML = `
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                    ${candidateAvatar ? 
                        `<img src="${escapeHtml(candidateAvatar)}" alt="${escapeHtml(candidateName)}" class="w-10 h-10 rounded-full object-cover" onerror="this.parentElement.innerHTML='<span class=\\'text-primary font-semibold\\'>${candidateName.charAt(0).toUpperCase()}</span>'">` :
                        `<span class="text-primary font-semibold">${candidateName.charAt(0).toUpperCase()}</span>`
                    }
                </div>
                <div class="flex-1 min-w-0">
                    <div class="font-semibold text-sm">${escapeHtml(candidateName)}</div>
                    ${lastMessage ? `<div class="text-xs text-muted-foreground truncate">${escapeHtml(lastMessage)}</div>` : '<div class="text-xs text-muted-foreground">Geen berichten</div>'}
                </div>
                ${lastMessageTime ? `<div class="text-xs text-muted-foreground shrink-0">${lastMessageTime}</div>` : ''}
            </div>
        `;
        chatList.appendChild(chatItem);
        console.log(`✅ Added chat item ${index + 1} for chat ${chat.id} (${candidateName})`);
    });
    
    // Verify items are in DOM
    const renderedItems = chatList.querySelectorAll('.chat-item');
    console.log('✅ Chat list rendered with', chats.length, 'items. DOM contains', renderedItems.length, 'items');
    console.log('📋 Chat list container after render:', {
        children: chatList.children.length,
        innerHTML: chatList.innerHTML.substring(0, 200) + '...'
    });
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
        console.error('Error loading candidates:', error);
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
        console.error('❌ selectChat called without chatId');
        return;
    }
    
    console.log('🔵 selectChat called with chatId:', chatId);
    currentChatId = chatId;
    let chat = activeChats.find(c => c.id === chatId);
    console.log('🔵 Found chat in activeChats:', chat ? 'Yes' : 'No');
    
    // Mark drawer as active and ensure it's open
    const drawer = document.getElementById('chat_drawer');
    const backdrop = document.getElementById('chat_drawer_backdrop');
    
    if (drawer) {
        console.log('🔵 Setting drawer attributes...');
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
            
            // Force transform reset multiple times to ensure it sticks
            const forceTransformReset = () => {
                isUpdatingStyles = true;
                try {
                    drawer.style.setProperty('transform', 'translateX(0)', 'important');
                    drawer.style.setProperty('right', '1.25rem', 'important');
                    drawer.style.setProperty('left', 'unset', 'important');
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
        
        console.log('🔵 Drawer should be visible now, computed display:', window.getComputedStyle(drawer).display);
    } else {
        console.error('❌ Drawer element not found in selectChat!');
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
            loadActiveChats().then(() => {
                chat = activeChats.find(c => c.id === chatId);
                if (chat) {
                    currentChat = chat;
                    updateChatViews(chat);
                    loadChatMessages(chatId);
                    startChatPolling(chatId);
                } else {
                    // If still not found, show basic view with just the chat ID
                    updateChatViews({ id: chatId, candidate: { name: 'Loading...' } });
                    loadChatMessages(chatId);
                    startChatPolling(chatId);
                }
            });
            return;
        }
    }
    
    currentChat = chat; // Store current chat data
    
    // Clear messages container before switching chats to prevent mixing messages
    const messagesContainer = document.getElementById('chat_messages');
    if (messagesContainer) {
        console.log('🧹 Clearing messages container before switching to chat:', chatId);
        messagesContainer.innerHTML = '';
    }
    
    // Update views with chat data
    updateChatViews(chat);
    
    // Load messages and start polling
    loadChatMessages(chatId);
    startChatPolling(chatId);
};

// Helper function to update chat views
function updateChatViews(chat) {
    console.log('🟢 updateChatViews called with chat:', chat);
    
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
        
        console.log('🟢 Drawer made visible in updateChatViews, computed display:', window.getComputedStyle(drawer).display);
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
        console.log('🟢 Chat list view hidden');
    } else {
        console.error('❌ chat_list_view element not found!');
    }
    
    if (chatMessagesView) {
        chatMessagesView.style.setProperty('display', 'flex', 'important');
        chatMessagesView.style.setProperty('visibility', 'visible', 'important');
        chatMessagesView.style.setProperty('opacity', '1', 'important');
        // Ensure messages view is visible
        chatMessagesView.classList.remove('hidden');
        console.log('🟢 Chat messages view shown');
    } else {
        console.error('❌ chat_messages_view element not found!');
    }
    
    if (chat) {
        if (chatHeaderName && chat.candidate) {
            chatHeaderName.textContent = chat.candidate.name;
        }
        if (chatHeaderAvatar && chat.candidate) {
            chatHeaderAvatar.textContent = chat.candidate.name.charAt(0).toUpperCase();
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
    console.log('📋 showChatList called');
    const chatListView = document.getElementById('chat_list_view');
    const chatMessagesView = document.getElementById('chat_messages_view');
    
    // Clear current chat selection to show list view
    currentChatId = null;
    currentChat = null;
    
    // Stop polling
    if (typingInterval) clearInterval(typingInterval);
    if (messagesInterval) clearInterval(messagesInterval);
    
    // Hide messages view completely
    if (chatMessagesView) {
        chatMessagesView.style.display = 'none';
        chatMessagesView.style.visibility = 'hidden';
    }
    
    // Show list view
    if (chatListView) {
        chatListView.style.display = 'flex';
        chatListView.style.visibility = 'visible';
        chatListView.style.opacity = '1';
    }
    
    // Load and render chat list when going back
    loadActiveChats().then(() => {
        renderChatList(activeChats);
    });
};

// Initialize chat message input event listeners
function initializeChatInput() {
    const chatInput = document.getElementById('chat_message_input');
    if (!chatInput) {
        console.warn('⚠️ Chat input not found, will retry...');
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
            console.log('⌨️ Enter key pressed, calling sendMessage');
            if (window.sendMessage) {
                window.sendMessage();
            } else {
                console.error('❌ sendMessage function not found!');
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
            console.log('🔵 Send button clicked via event listener');
            if (window.sendMessage) {
                window.sendMessage();
            } else {
                console.error('❌ sendMessage function not found!');
            }
        });
        console.log('✅ Send button event listener added');
    } else if (!sendButton) {
        console.warn('⚠️ Send button not found!');
    }
    
    console.log('✅ Chat input event listeners initialized');
}

// Load chat messages
function loadChatMessages(chatId) {
    console.log('📥 Loading messages for chat:', chatId);
    
    // Clear messages container if switching to a different chat
    if (currentChatId !== chatId) {
        const messagesContainer = document.getElementById('chat_messages');
        if (messagesContainer) {
            console.log('🧹 Clearing messages container for new chat');
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
        console.log('✅ Messages loaded:', messages);
        if (Array.isArray(messages)) {
            // Only render if this is still the current chat (prevent race conditions)
            if (currentChatId === chatId) {
                renderMessages(messages, chatId);
                // Use setTimeout to ensure DOM is updated before scrolling
                setTimeout(() => {
                    scrollToBottom();
                }, 100);
            } else {
                console.log('⏭️ Skipping render - chat changed from', chatId, 'to', currentChatId);
            }
        } else {
            console.error('❌ Messages is not an array:', messages);
        }
        return messages;
    })
    .catch(error => {
        console.error('❌ Error loading messages:', error);
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
    console.log('🎨 Rendering messages:', messages, 'for chat:', expectedChatId || currentChatId);
    const messagesContainer = document.getElementById('chat_messages');
    if (!messagesContainer) {
        console.error('❌ Messages container not found!');
        return;
    }
    
    // If expectedChatId is provided and doesn't match currentChatId, don't render
    if (expectedChatId !== null && expectedChatId !== currentChatId) {
        console.log('⏭️ Skipping render - chat mismatch. Expected:', expectedChatId, 'Current:', currentChatId);
        return;
    }
    
    // Clear all existing real messages (but keep optimistic messages if they match the current chat)
    const existingRealMessages = messagesContainer.querySelectorAll('[data-optimistic="false"]');
    console.log('🧹 Removing', existingRealMessages.length, 'existing real messages');
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
            console.log('🗑️ Removing optimistic message from different chat:', chatIdAttr, 'current:', currentChatId);
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
        console.log('✅ No new messages to render, all messages already exist or are optimistic');
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
                    console.log('🔄 Removing optimistic message, matching real message exists in DOM:', text, 'ID:', closestMessage.id);
                    element.remove();
                    // Sort immediately after removing optimistic message
                    sortMessagesInDOM();
                } else {
                    // Real message not yet in DOM or not visible, keep optimistic one
                    console.log('⏳ Keeping optimistic message, real message not yet in DOM or visible:', text);
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
                            <i class="ki-filled ki-double-check absolute text-lg ${msg.is_read ? 'text-green-500' : 'text-gray-400'}"></i>
                        </div>
                    </div>
                    <div class="relative shrink-0">
                        <div class="kt-avatar size-9">
                            <div class="kt-avatar-image">
                                <img alt="${msg.sender_name}" class="size-9 rounded-full object-cover" src="${userAvatar}" />
                            </div>
                            <div class="kt-avatar-indicator -bottom-2 -end-2">
                                <div class="kt-avatar-status kt-avatar-status-online size-2.5"></div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else {
            // Other messages - left aligned with avatar on left
            const avatarUrl = msg.sender_avatar || '/assets/media/avatars/300-5.png';
            // Parse created_at timestamp for sorting
            const messageTimestamp = msg.created_at 
                ? new Date(msg.created_at).toISOString()
                : new Date().toISOString();
            
            return `
                <div class="flex items-end gap-3.5 px-5" data-optimistic="false" data-message-id="${msg.id || ''}" data-message-text="${escapeHtml(msg.message)}" data-timestamp="${messageTimestamp}" data-chat-id="${currentChatId || ''}">
                    <img alt="${msg.sender_name}" class="size-9 rounded-full object-cover" src="${avatarUrl}" />
                    <div class="flex flex-col gap-1.5">
                        <div class="kt-card bg-accent/60 rounded-bs-none text-2sm flex flex-col gap-2.5 p-3 shadow-none">
                            ${escapeHtml(msg.message)}
                        </div>
                        <span class="text-xs font-medium text-muted-foreground">${msg.time}</span>
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
            console.log('⏳ Keeping optimistic message, no matching message in response yet:', text);
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
                        console.log('🔄 Removing optimistic message, matching real message exists in DOM:', text, 'ID:', closestMessage.id, 'timeDiff:', closestTimeDiff);
                        element.remove();
                        // Sort again immediately after removing optimistic message
                        sortMessagesInDOM();
                        scrollToBottom();
                    } else {
                        // Real message not yet in DOM or not visible, keep optimistic one
                        console.log('⏳ Keeping optimistic message, real message not yet visible:', text);
                    }
                }
            }, 150); // Increased delay to ensure DOM is fully updated
        } else {
            // Keep optimistic message if no close match found
            console.log('⏳ Keeping optimistic message, no close timestamp match found:', text, 'closestTimeDiff:', closestTimeDiff);
        }
    });
    
    console.log('✅ Messages rendered, count:', messages.length, 'new messages:', newMessages.length, 'optimistic preserved:', optimisticMessagesData.length, 'container:', messagesContainer);
    
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
        console.error('❌ Messages container not found!');
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
                ${isOptimistic ? '<i class="ki-filled ki-time text-lg text-gray-400"></i>' : '<i class="ki-filled ki-double-check absolute text-lg text-gray-400"></i>'}
            </div>
        </div>
        <div class="relative shrink-0">
            <div class="kt-avatar size-9">
                <div class="kt-avatar-image">
                    <img alt="You" class="size-9 rounded-full object-cover" src="${userAvatar}" />
                </div>
                <div class="kt-avatar-indicator -bottom-2 -end-2">
                    <div class="kt-avatar-status kt-avatar-status-online size-2.5"></div>
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
    console.log('🔵 sendMessage called');
    const input = document.getElementById('chat_message_input');
    if (!input) {
        console.error('❌ Chat input element not found!');
        return;
    }
    
    if (!input.value || !input.value.trim()) {
        console.warn('⚠️ Cannot send message: input is empty');
        return;
    }
    
    if (!currentChatId) {
        console.warn('⚠️ Cannot send message: no chat selected, currentChatId:', currentChatId);
        return;
    }

    const message = input.value.trim();
    console.log('📤 Sending message:', message, 'to chat:', currentChatId);
    
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
        console.error('❌ CSRF token not found!');
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
        console.log('📡 Response status:', response.status);
        if (!response.ok) {
            return response.text().then(text => {
                console.error('❌ Response error:', text);
                throw new Error(`HTTP error! status: ${response.status}, body: ${text}`);
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('✅ Message sent successfully:', data);
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
                                    <i class="ki-filled ki-double-check absolute text-lg ${data.message.is_read ? 'text-green-500' : 'text-gray-400'}"></i>
                                </div>
                            </div>
                            <div class="relative shrink-0">
                                <div class="kt-avatar size-9">
                                    <div class="kt-avatar-image">
                                        <img alt="${data.message.sender_name}" class="size-9 rounded-full object-cover" src="${userAvatar}" />
                                    </div>
                                    <div class="kt-avatar-indicator -bottom-2 -end-2">
                                        <div class="kt-avatar-status kt-avatar-status-online size-2.5"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    messagesContainer.insertAdjacentHTML('beforeend', messageHTML);
                    realMessageAdded = true;
                    
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
                                console.log('🔄 Removing optimistic message, real message confirmed in DOM');
                                optimisticMessageElement.remove();
                                // Sort again immediately after removing optimistic
                                sortMessagesInDOM();
                                scrollToBottom();
                            }
                        } else {
                            // Real message not yet visible, keep optimistic for now
                            console.log('⏳ Keeping optimistic message, real message not yet visible');
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
                }
            }
            
            // Reload messages once to sync with server (but don't wait for it)
            // Only reload if we successfully added the message, to avoid race conditions
            if (realMessageAdded) {
                setTimeout(() => {
                    loadChatMessages(currentChatId);
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
            console.error('❌ Message send failed:', data);
            // Remove optimistic message on failure
            if (optimisticMessageElement) {
                optimisticMessageElement.remove();
            }
            input.value = message; // Restore message
        }
    })
    .catch(error => {
        console.error('❌ Error sending message:', error);
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
    
    console.log('🔒 handleDrawerClose called - closing drawer');
    
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
                console.log('⚠️ Error hiding drawer instance:', e);
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
                console.log('⚠️ Error clicking toggle:', e);
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

    if (!confirm('Weet je zeker dat je deze chat wilt beëindigen?')) return;

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
            const messagesContainer = document.getElementById('chat_messages');
            if (messagesContainer) messagesContainer.innerHTML = '';
            showChatList();
        }
    })
    .catch(error => {
        console.error('Error ending chat:', error);
    });
};

// Start chat polling
function startChatPolling(chatId) {
    if (messagesInterval) clearInterval(messagesInterval);
    if (typingInterval) clearInterval(typingInterval);

    // Poll every 500ms for faster updates
    messagesInterval = setInterval(() => {
        if (currentChatId && !isWaitingForMessage) {
            loadChatMessages(currentChatId);
        }
    }, 500);

    typingInterval = setInterval(() => {
        if (currentChatId) {
            checkTyping(currentChatId);
        }
    }, 1000);
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
        console.error('Error checking typing:', error);
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
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    // Don't interfere if drawer is marked to ignore observer
                    if (drawer && drawer.getAttribute('data-ignore-observer') === 'true') {
                        console.log('⏭️ Observer skipped - ignore flag set');
                        return;
                    }
                    
                    // Don't interfere if drawer was explicitly closed
                    if (isDrawerExplicitlyClosed || (drawer && drawer.getAttribute('data-drawer-closed') === 'true')) {
                        console.log('⏭️ Observer skipped - drawer explicitly closed');
                        return;
                    }
                    
                    const isOpen = !drawer.classList.contains('hidden');
                    console.log('👁️ Observer detected drawer state change, isOpen:', isOpen, 'currentChatId:', currentChatId);
                    
                    if (isOpen) {
                        // Don't interfere if we're opening a chat programmatically
                        if (isOpeningChat) {
                            console.log('⏭️ Observer skipped - isOpeningChat flag set');
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
                            drawer.style.setProperty('left', 'unset', 'important'); // Force left to unset
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
                        loadActiveChats().then(() => {
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
                        
                        if (hasActiveChat) {
                            // Keep drawer open if chat is active
                            if (drawer) {
                                drawer.classList.remove('hidden');
                                
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
                                        drawer.style.setProperty('left', 'unset', 'important'); // Force left to unset
                                        drawer.setAttribute('data-chat-active', 'true');
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
                            // Hide drawer and backdrop only if no chat is active
                            if (drawer) {
                                drawer.removeAttribute('data-chat-active');
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
                                console.log('🔄 Style observer: Resetting transform from', computedTransform);
                                drawer.style.setProperty('transform', 'translateX(0)', 'important');
                            }
                            if (needsRightReset) {
                                console.log('🔄 Style observer: Resetting right from', computedRight);
                                drawer.style.setProperty('right', '1.25rem', 'important');
                            }
                            if (needsLeftReset) {
                                console.log('🔄 Style observer: Resetting left from', computedLeft);
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
                                drawer.style.setProperty('left', 'unset', 'important');
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
        // Run every 10ms to catch KT Drawer library style changes immediately
        setInterval(function() {
            // Skip if we're currently updating styles to prevent observer interference
            if (isUpdatingStyles) {
                return;
            }
            
            // If drawer was explicitly closed, actively hide it
            if (isDrawerExplicitlyClosed || (drawer && drawer.getAttribute('data-drawer-closed') === 'true')) {
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
            
            const hasActiveChat = currentChatId || isOpeningChat || (drawer && drawer.getAttribute('data-chat-active') === 'true');
            
            if (hasActiveChat && drawer) {
                // Ensure drawer stays open when chat is active
                const isMarkedHidden = drawer.classList.contains('hidden');
                const computedDisplay = window.getComputedStyle(drawer).display;
                
                if (isMarkedHidden || computedDisplay === 'none') {
                    console.log('🔄 Interval: Drawer was hidden, reopening...', { isMarkedHidden, computedDisplay });
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
                    drawer.style.setProperty('right', '1.25rem', 'important'); // Ensure right positioning
                    drawer.setAttribute('data-chat-active', 'true');
                    
                    // Always aggressively reset left property, regardless of current value
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
                    drawer.style.setProperty('left', 'unset', 'important');
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
                    drawer.style.setProperty('left', 'unset', 'important');
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
                console.log('🔄 Interval: No active chat, removing active flag');
                drawer.removeAttribute('data-chat-active');
            }
        }, 10); // Check every 10ms to catch KT Drawer library style changes immediately
    }
    
    // Handle backdrop click to close drawer
    if (backdrop) {
        backdrop.addEventListener('click', function() {
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
                console.log('🔓 Toggle button clicked - resetting close flag to allow drawer to open');
            } else {
                // Drawer is currently open, so clicking will CLOSE it
                // Set flag to mark as explicitly closed
                isDrawerExplicitlyClosed = true;
                if (drawer) {
                    drawer.setAttribute('data-drawer-closed', 'true');
                }
                console.log('🔒 Toggle button clicked - setting close flag');
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
                    drawer.style.setProperty('left', 'unset', 'important'); // Force left to auto
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
                    console.log('⌨️ ESC key pressed - closing drawer');
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
            console.log('🖱️ Click outside drawer - closing drawer');
            if (window.handleDrawerClose) {
                window.handleDrawerClose();
            }
        }
    });
});

