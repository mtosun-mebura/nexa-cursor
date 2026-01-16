// Backend header badge updates and shake animation
console.log('📦 backend-header-badges.js loaded!');

let badgeUpdateInterval = null;
let shakeInterval = null;

// Update chat badge count
function updateChatBadge() {
    const chatButton = document.querySelector('#backend_chat_toggle, [data-kt-drawer-toggle="#chat_drawer"]');
    const chatIcon = chatButton?.querySelector('.chat-icon');
    const chatBadge = chatButton?.querySelector('.chat-badge');
    
    if (!chatButton) return;
    
    fetch('/admin/chat/unread-count', {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        const unreadCount = data.unread_count || 0;
        
        // Update icon color
        if (unreadCount > 0) {
            chatButton.classList.add('has-unread');
            chatIcon?.classList.add('text-red-500');
            chatIcon?.classList.remove('text-foreground');
            
            // Update or create badge
            if (!chatBadge) {
                const badge = document.createElement('span');
                badge.className = 'absolute top-0 end-0 flex size-4 items-center justify-center rounded-full bg-danger text-[10px] font-semibold leading-none text-white chat-badge';
                badge.textContent = unreadCount > 9 ? '9+' : unreadCount;
                chatButton.appendChild(badge);
            } else {
                chatBadge.textContent = unreadCount > 9 ? '9+' : unreadCount;
                chatBadge.style.display = 'flex';
            }
            
            // Add shake animation
            if (!chatButton.classList.contains('shake')) {
                chatButton.classList.add('shake');
            }
        } else {
            chatButton.classList.remove('has-unread', 'shake');
            chatIcon?.classList.remove('text-red-500');
            chatIcon?.classList.add('text-foreground');
            if (chatBadge) {
                chatBadge.style.display = 'none';
            }
        }
    })
    .catch(error => {
        console.error('Error updating chat badge:', error);
    });
}

// Update notification badge count
function updateNotificationBadge() {
    const notificationButton = document.querySelector('.notification-icon-button');
    const notificationIcon = notificationButton?.querySelector('.notification-icon');
    const notificationBadge = notificationButton?.querySelector('.notification-badge');
    
    if (!notificationButton) return;
    
    // Get unread count from existing PHP variable or fetch
    const unreadCount = parseInt(notificationBadge?.textContent || '0');
    
    if (unreadCount > 0) {
        notificationButton.classList.add('has-unread');
        notificationIcon?.classList.add('text-red-500');
        notificationIcon?.classList.remove('text-foreground');
        
        if (!notificationBadge) {
            const badge = document.createElement('span');
            badge.className = 'absolute top-0 end-0 flex size-4 items-center justify-center rounded-full bg-danger text-[10px] font-semibold leading-none text-white notification-badge';
            badge.textContent = unreadCount > 9 ? '9+' : unreadCount;
            notificationButton.appendChild(badge);
        } else {
            notificationBadge.textContent = unreadCount > 9 ? '9+' : unreadCount;
            notificationBadge.style.display = 'flex';
        }
        
        if (!notificationButton.classList.contains('shake')) {
            notificationButton.classList.add('shake');
        }
    } else {
        notificationButton.classList.remove('has-unread', 'shake');
        notificationIcon?.classList.remove('text-red-500');
        notificationIcon?.classList.add('text-foreground');
        if (notificationBadge) {
            notificationBadge.style.display = 'none';
        }
    }
}

// Shake animation every 5 seconds if there are unread messages
function startShakeAnimation() {
    if (shakeInterval) clearInterval(shakeInterval);
    
    shakeInterval = setInterval(() => {
        const chatButton = document.querySelector('#backend_chat_toggle, [data-kt-drawer-toggle="#chat_drawer"]');
        const notificationButton = document.querySelector('.notification-icon-button');
        
        if (chatButton?.classList.contains('has-unread')) {
            chatButton.classList.remove('shake');
            setTimeout(() => {
                chatButton.classList.add('shake');
            }, 10);
        }
        
        if (notificationButton?.classList.contains('has-unread')) {
            notificationButton.classList.remove('shake');
            setTimeout(() => {
                notificationButton.classList.add('shake');
            }, 10);
        }
    }, 5000);
}

// Initialize badge updates
function initBadgeUpdates() {
    // Update immediately
    updateChatBadge();
    updateNotificationBadge();
    
    // Update every 10 seconds
    if (badgeUpdateInterval) clearInterval(badgeUpdateInterval);
    badgeUpdateInterval = setInterval(() => {
        updateChatBadge();
        updateNotificationBadge();
    }, 10000);
    
    // Start shake animation
    startShakeAnimation();
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBadgeUpdates);
} else {
    initBadgeUpdates();
}

// Export functions for use in other scripts
window.updateChatBadge = updateChatBadge;
window.updateNotificationBadge = updateNotificationBadge;

