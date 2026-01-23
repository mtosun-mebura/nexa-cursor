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
            
            // Don't add shake class here - let startShakeAnimation handle it
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
    
    fetch('/admin/notifications/unread-count', {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        const unreadCount = data.unread_count || 0;
        const highestPriority = data.highest_priority || 'normal';
        
        // Remove all priority color classes
        notificationIcon?.classList.remove('text-red-500', 'text-orange-500', 'text-blue-500', 'text-gray-500', 'text-foreground');
        
        if (unreadCount > 0) {
            notificationButton.classList.add('has-unread');
            
            // Set icon color based on priority
            // urgent = red, high = orange, normal = blue, low = gray
            switch(highestPriority) {
                case 'urgent':
                    notificationIcon?.classList.add('text-red-500');
                    break;
                case 'high':
                    notificationIcon?.classList.add('text-orange-500');
                    break;
                case 'normal':
                    notificationIcon?.classList.add('text-blue-500');
                    break;
                case 'low':
                    notificationIcon?.classList.add('text-gray-500');
                    break;
                default:
                    notificationIcon?.classList.add('text-blue-500');
            }
            
            // Update or create badge
            if (!notificationBadge) {
                const badge = document.createElement('span');
                // Use size-5 (20px) with min-width/height for double-digit numbers, positioned slightly higher and to the right
                badge.className = 'absolute -top-1 -end-1 flex size-5 items-center justify-center rounded-full bg-danger text-[11px] font-semibold leading-none text-white notification-badge';
                badge.style.minWidth = '20px';
                badge.style.minHeight = '20px';
                badge.textContent = unreadCount.toString();
                notificationButton.appendChild(badge);
            } else {
                notificationBadge.textContent = unreadCount.toString();
                notificationBadge.style.display = 'flex';
            }
            
            // Don't add shake class here - let startShakeAnimation handle it
        } else {
            notificationButton.classList.remove('has-unread', 'shake');
            notificationIcon?.classList.add('text-foreground');
            if (notificationBadge) {
                notificationBadge.style.display = 'none';
            }
        }
    })
    .catch(error => {
        console.error('Error updating notification badge:', error);
    });
}

// Shake animation every 5 seconds if there are unread messages
// The animation lasts 2 seconds (multiple 0.5s cycles), then stops for 3 seconds before repeating
function startShakeAnimation() {
    if (shakeInterval) clearInterval(shakeInterval);
    
    // Trigger immediately if there are unread messages
    const triggerShake = () => {
        const chatButton = document.querySelector('#backend_chat_toggle, [data-kt-drawer-toggle="#chat_drawer"]');
        const notificationButton = document.querySelector('.notification-icon-button');
        
        if (chatButton?.classList.contains('has-unread')) {
            // Remove any existing shake class
            chatButton.classList.remove('shake');
            
            // Trigger shake animation 4 times (4 * 0.5s = 2s total)
            let shakeCount = 0;
            const maxShakes = 4;
            
            const doShake = () => {
                if (shakeCount < maxShakes && chatButton.classList.contains('has-unread')) {
                    // Force reflow by removing and re-adding the class
                    chatButton.classList.remove('shake');
                    requestAnimationFrame(() => {
                        requestAnimationFrame(() => {
                            chatButton.classList.add('shake');
                            shakeCount++;
                            // After 0.5s (animation duration), trigger next shake or stop
                            setTimeout(() => {
                                chatButton.classList.remove('shake');
                                if (shakeCount < maxShakes) {
                                    setTimeout(doShake, 50); // Small delay between shakes
                                }
                            }, 500);
                        });
                    });
                }
            };
            
            // Start shaking
            setTimeout(doShake, 10);
        }
        
        if (notificationButton?.classList.contains('has-unread')) {
            // Remove any existing shake class
            notificationButton.classList.remove('shake');
            
            // Trigger shake animation 4 times (4 * 0.5s = 2s total)
            let shakeCount = 0;
            const maxShakes = 4;
            
            const doShake = () => {
                if (shakeCount < maxShakes && notificationButton.classList.contains('has-unread')) {
                    // Force reflow by removing and re-adding the class
                    notificationButton.classList.remove('shake');
                    requestAnimationFrame(() => {
                        requestAnimationFrame(() => {
                            notificationButton.classList.add('shake');
                            shakeCount++;
                            // After 0.5s (animation duration), trigger next shake or stop
                            setTimeout(() => {
                                notificationButton.classList.remove('shake');
                                if (shakeCount < maxShakes) {
                                    setTimeout(doShake, 50); // Small delay between shakes
                                }
                            }, 500);
                        });
                    });
                }
            };
            
            // Start shaking
            setTimeout(doShake, 10);
        }
    };
    
    // Trigger immediately
    triggerShake();
    
    // Then trigger every 5 seconds
    shakeInterval = setInterval(triggerShake, 5000); // Trigger every 5 seconds (2s shake + 3s pause)
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

