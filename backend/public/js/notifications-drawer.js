// Notifications drawer functionality

let selectedNotifications = new Set();
let notifications = [];
let notificationsPollingInterval = null;
let lastNotificationCount = 0;

// Detect if we're on admin or frontend
function getNotificationBaseUrl() {
    // Check if we're on admin pages
    if (window.location.pathname.startsWith('/admin')) {
        return '/admin/notifications';
    }
    return '/notifications';
}

// Check for new notifications only (for polling - doesn't reload everything)
async function checkForNewNotifications() {
    // Only check if we already have notifications loaded
    if (notifications.length === 0) {
        return false;
    }
    
    try {
        const baseUrl = getNotificationBaseUrl();
        const response = await fetch(`${baseUrl}/list`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                'Accept': 'application/json',
            }
        });
        
        if (!response.ok) {
            return false;
        }
        
        const data = await response.json();
        const fetchedNotifications = Array.isArray(data) ? data : [];
        
        // Find truly new notifications (not in current list)
        const currentIds = new Set(notifications.map(n => n.id));
        const newNotifications = fetchedNotifications.filter(n => !currentIds.has(n.id));
        
        if (newNotifications.length > 0) {
            // Prepend new notifications at the top
            notifications = [...newNotifications, ...notifications];
            renderNotifications();
            
            // Scroll to top to show new notification (only if user is near top)
            const container = document.querySelector('#notifications_list');
            if (container && container.scrollTop < 100) {
                container.scrollTop = 0;
            }
            
            return true;
        }
        
        return false;
    } catch (error) {
        console.error('Error checking for new notifications:', error);
        return false;
    }
}

// Load notifications (full reload)
async function loadNotifications() {
    console.log('📥 Loading notifications...');
    try {
        const baseUrl = getNotificationBaseUrl();
        const response = await fetch(`${baseUrl}/list`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                'Accept': 'application/json',
            }
        });
        
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`Failed to load notifications: ${response.status}`);
        }
        
        const data = await response.json();
        const newNotifications = Array.isArray(data) ? data : [];
        
        // Preserve selected notifications before updating
        const previouslySelected = new Set(selectedNotifications);
        
        // Preserve read status and other properties from existing notifications
        const existingNotificationsMap = new Map(notifications.map(n => [n.id, n]));
        
        // Merge: keep existing notification properties, update with new data
        notifications = newNotifications.map(newNotif => {
            const existing = existingNotificationsMap.get(newNotif.id);
            if (existing) {
                // Preserve UI state (is_read, read_at, etc.) from existing
                return {
                    ...newNotif,
                    is_read: existing.is_read ?? newNotif.is_read,
                    read_at: existing.read_at ?? newNotif.read_at,
                };
            }
            return newNotif;
        });
        
        lastNotificationCount = notifications.length;
        
        // Restore selected notifications (only keep IDs that still exist)
        selectedNotifications.clear();
        notifications.forEach(notification => {
            if (previouslySelected.has(notification.id)) {
                selectedNotifications.add(notification.id);
            }
        });
        
        renderNotifications();
    } catch (error) {
        const container = document.querySelector('#notifications_list');
        if (container) {
            container.innerHTML = `
                <div class="flex items-center justify-center py-10 px-5">
                    <div class="text-center">
                        <i class="ki-filled ki-information text-4xl text-destructive mb-3"></i>
                        <p class="text-sm text-destructive">Fout bij laden van notificaties</p>
                        <p class="text-xs text-muted-foreground mt-2">${error.message}</p>
                    </div>
                </div>
            `;
        }
    }
}

// Render notifications
function renderNotifications() {
    const container = document.querySelector('#notifications_list');
    if (!container) return;
    
    // Clear existing content
    container.innerHTML = '';
    
    if (notifications.length === 0) {
        container.innerHTML = `
            <div class="flex items-center justify-center py-10 px-5">
                <div class="text-center">
                    <i class="ki-filled ki-notification text-4xl text-muted-foreground mb-3"></i>
                    <p class="text-sm text-muted-foreground">Geen notificaties</p>
                </div>
            </div>
        `;
        return;
    }
    
    console.log('📋 Rendering notifications:', notifications.length);
    notifications.forEach((notification, index) => {
        console.log(`📋 Processing notification ${index + 1}/${notifications.length}:`, notification.id, notification.title);
        const notificationEl = createNotificationElement(notification);
        container.appendChild(notificationEl);
        
        // Restore checkbox state if notification was previously selected
        if (selectedNotifications.has(notification.id)) {
            const checkbox = notificationEl.querySelector('.notification-checkbox');
            if (checkbox) {
                checkbox.checked = true;
            }
        }
        
        // Add divider except for last item
        if (index < notifications.length - 1) {
            const divider = document.createElement('div');
            divider.className = 'border-b border-border mb-2 mt-2';
            container.appendChild(divider);
        }
    });
}

// Create notification element
function createNotificationElement(notification) {
    const div = document.createElement('div');
    // Add background class for unread notifications
    const bgClass = !notification.is_read ? 'bg-muted/30' : '';
    div.className = `flex grow gap-2.5 px-5 py-3 notification-item cursor-pointer ${bgClass}`;
    div.dataset.notificationId = notification.id;
    div.dataset.isRead = notification.is_read ? 'true' : 'false';
    div.style.cursor = 'pointer';
    
    // Get sender info - if no sender, it's a system notification
    const hasSender = notification.sender && notification.sender.id;
    const avatar = hasSender ? (notification.sender.avatar || '/assets/media/avatars/300-2.png') : '/assets/media/avatars/300-2.png';
    const senderName = hasSender ? notification.sender.name : 'Systeem';
    const senderEmail = hasSender ? (notification.sender.email || '') : '';
    
    // Check if this is an interview notification - check both type and title
    const isInterviewNotification = notification.type === 'interview' || 
                                     (notification.title && (notification.title.toLowerCase().includes('interview') || notification.title.toLowerCase().includes('afspraak')));
    
    // Check if notification has a response and show status icon (only for interview type)
    // Position it next to the unread indicator, not overlapping
    let responseIcon = '';
    if (isInterviewNotification && notification.has_response && notification.response_type) {
        if (notification.response_type === 'accept') {
            responseIcon = '<div class="flex items-center justify-center shrink-0" title="Geaccepteerd"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="color: rgb(34, 197, 94);"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div>';
        } else if (notification.response_type === 'decline') {
            responseIcon = '<div class="flex items-center justify-center shrink-0" title="Afgewezen"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="color: rgb(239, 68, 68);"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div>';
        }
    }
    
    let actionButtons = '';
    // Show action buttons for interview notifications if no response has been given yet
    // Simply check if it's an interview notification and there's no response
    if (isInterviewNotification) {
        console.log('[Notifications Drawer] Interview notification status check:', {
            notificationId: notification.id,
            hasResponse: notification.has_response,
            responseType: notification.response_type,
            matchId: notification.match_id,
            interviewExists: notification.interview_exists,
            location: notification.location,
            locationOrType: notification.location_or_type
        });
    }
    
    // Show buttons only if no response has been given yet AND no interview exists yet
    // Also check that response_type is not set (double check)
    // Only show buttons for NEW interview notifications without any response and without scheduled interview
    if (isInterviewNotification && !notification.has_response && !notification.response_type && !notification.interview_exists) {
        actionButtons = `
            <div class="flex flex-wrap gap-2.5 mt-2">
                <button class="kt-btn kt-btn-outline kt-btn-sm decline-interview" data-notification-id="${notification.id}">
                    Afwijzen
                </button>
                <button class="kt-btn kt-btn-mono kt-btn-sm accept-interview" data-notification-id="${notification.id}">
                    Accepteren
                </button>
            </div>
        `;
    } else if (isInterviewNotification && notification.has_response && notification.response_type === 'accept' && notification.match_id) {
        // Check if user is admin (on admin pages)
        const isAdmin = window.location.pathname.startsWith('/admin');
        
        if (isAdmin) {
            // Check if interview already exists
            if (notification.interview_exists) {
                // Show "Ingepland" status message
                actionButtons = `
                    <div class="flex flex-wrap gap-2.5 mt-2">
                        <div class="flex items-center gap-2 text-green-500">
                            <i class="ki-filled ki-check-circle text-green-500"></i>
                            <span class="font-medium text-green-500">Ingepland</span>
                        </div>
                    </div>
                `;
            } else {
                // No action buttons needed - "Afspraak in interviews zetten" button is shown separately
                actionButtons = '';
            }
        } else {
            // Show status message for candidates
            // If interview is scheduled (interview_exists = true), show "Ingepland", otherwise show "Verzoek geaccepteerd"
            const statusText = notification.interview_exists ? 'Ingepland' : 'Verzoek geaccepteerd';
            actionButtons = `
                <div class="flex flex-wrap gap-2.5 mt-2">
                    <div class="flex items-center gap-2 text-green-500">
                        <i class="ki-filled ki-check-circle text-green-500"></i>
                        <span class="font-medium text-green-500">${statusText}</span>
                    </div>
                </div>
            `;
        }
    } else if (isInterviewNotification && notification.has_response && notification.response_type === 'decline') {
        // Show status message for declined interviews (for candidates)
        const isAdmin = window.location.pathname.startsWith('/admin');
        if (!isAdmin) {
            actionButtons = `
                <div class="flex flex-wrap gap-2.5 mt-2">
                    <div class="flex items-center gap-2 text-red-500">
                        <i class="ki-filled ki-cross-circle text-red-500"></i>
                        <span class="font-medium text-red-500">Verzoek afgewezen</span>
                    </div>
                </div>
            `;
        }
    }
    
    let fileSection = '';
    if (notification.file_path) {
        const fileSize = notification.file_size ? (notification.file_size / 1024).toFixed(2) + ' KB' : '';
        fileSection = `
            <div class="kt-card shadow-none flex flex-col gap-2 p-2.5 rounded-lg bg-muted/40 border border-gray-300 mt-2">
                <div class="text-xs font-medium text-muted-foreground/70">Bestand verzonden</div>
                <div class="flex items-center justify-between flex-row gap-1.5">
                    <div class="flex items-center gap-1.5">
                        <i class="ki-filled ki-file text-lg text-primary"></i>
                        <div class="flex flex-col gap-0.5">
                            <a href="${notification.file_path}" target="_blank" class="hover:text-primary hover:underline font-medium text-primary text-xs">
                                ${notification.file_name || 'Bestand'}
                            </a>
                            ${fileSize ? `<span class="font-medium text-muted-foreground text-xs">${fileSize}</span>` : ''}
                        </div>
                    </div>
                    <a href="${notification.file_path}" target="_blank" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost">
                        <i class="ki-filled ki-arrow-top-right"></i>
                    </a>
                </div>
            </div>
        `;
    }
    
    div.innerHTML = `
        <div class="flex items-start gap-2.5 w-full">
            <div class="flex items-start gap-2">
                <input type="checkbox" class="notification-checkbox" data-notification-id="${notification.id}" style="align-self: center;">
                <div class="kt-avatar">
                    <div class="kt-avatar-image">
                        <img alt="${senderName}" src="${avatar}">
                    </div>
                    ${!notification.is_read ? '<div class="kt-avatar-indicator -end-2 -bottom-2"><div class="kt-avatar-status kt-avatar-status-online size-2.5" style="background-color: rgb(59, 130, 246); border: 2px solid var(--kt-body-bg, #ffffff);"></div></div>' : ''}
                </div>
            </div>
            <div class="flex flex-col gap-2 flex-1">
                <div class="flex flex-col gap-1.5">
                    <div class="flex items-center gap-2">
                        <div class="text-sm font-medium flex-1">
                            <span class="text-mono font-semibold">${senderName}</span>
                            <span class="text-secondary-foreground"> ${notification.title}</span>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            ${!notification.is_read ? '<div class="size-2 rounded-full shrink-0" style="background-color: rgb(59, 130, 246);"></div>' : ''}
                            ${responseIcon}
                        </div>
                    </div>
                    
                    ${isInterviewNotification ? `
                        ${(() => {
                            // Parse the message - new format for confirmation notifications
                            let messageText = notification.message || '';
                            let html = '';
                            
                            // Check if this is the new format (confirmation notification)
                            // Format: "Je geaccepteerde afspraak heeft de status {status} gekregen."
                            // This applies to both response notifications and confirmation notifications
                            if (messageText.includes('heeft de status') && messageText.includes('gekregen')) {
                                // New format - parse status only
                                const statusMatch = messageText.match(/heeft de status\s+([^.]+)\s+gekregen/);
                                
                                // Build HTML for new format - only show status message
                                if (statusMatch) {
                                    html += `<div class="text-sm text-muted-foreground">Je geaccepteerde afspraak heeft de status <span class="font-semibold">${statusMatch[1].trim()}</span> gekregen.</div>`;
                                }
                                
                                // Add border before appointment details
                                if (notification.scheduled_at_formatted || notification.location || notification.location_or_type) {
                                    html += `<div class="border-t border-border mt-2 pt-2"></div>`;
                                }
                                
                                // Appointment details section
                                if (notification.scheduled_at_formatted || notification.location || notification.location_or_type) {
                                    html += `<div class="flex flex-col gap-0.5 mt-1">`;
                                    html += `<div class="text-sm font-semibold text-foreground">Afspraakdetails:</div>`;
                                    if (notification.scheduled_at_formatted || notification.scheduled_date) {
                                        const dateStr = notification.scheduled_date || (notification.scheduled_at_formatted ? notification.scheduled_at_formatted.split(' ')[0] : '');
                                        const timeStr = notification.scheduled_time || (notification.scheduled_at_formatted && notification.scheduled_at_formatted.includes(' ') ? notification.scheduled_at_formatted.split(' ')[1] : '');
                                        html += `<div class="text-sm text-muted-foreground">${dateStr}${timeStr ? ' om ' + timeStr : ''}</div>`;
                                    }
                                    if (notification.location) {
                                        html += `<div class="text-sm text-muted-foreground">${notification.location.name}${notification.location.city ? ' - ' + notification.location.city : ''}</div>`;
                                    } else if (notification.location_or_type) {
                                        html += `<div class="text-sm text-muted-foreground">${notification.location_or_type}</div>`;
                                    }
                                    html += `</div>`;
                                }
                            } else if (notification.has_response) {
                                // Old format - parse date, message, and location (for response notifications)
                                let dateText = '';
                                let reasonText = '';
                                let hasReason = false;
                                
                                if (messageText.includes('Datum:')) {
                                    // Match date until comma, "Bericht:", "Locatie:", or end of string
                                    const dateMatch = messageText.match(/Datum:\s*([^,]+?)(?=\s*(?:Bericht:|Locatie:|,|$))/);
                                    if (dateMatch) {
                                        dateText = dateMatch[1].trim();
                                    }
                                }
                                
                                if (messageText.includes('Bericht:')) {
                                    const berichtIndex = messageText.indexOf('Bericht:');
                                    if (berichtIndex !== -1) {
                                        let extractedText = messageText.substring(berichtIndex + 8).trim();
                                        extractedText = extractedText.replace(/Datum:\s*[^,]+?(?=\s*(?:Bericht:|Locatie:|,|$))/g, '').trim();
                                        extractedText = extractedText.replace(/Locatie:\s*[^,]+/g, '').trim();
                                        extractedText = extractedText.replace(/\s+/g, ' ').replace(/^,\s*|\s*,$/g, '').trim();
                                        reasonText = extractedText;
                                        hasReason = reasonText.length > 0;
                                    }
                                }
                                
                                if (dateText) {
                                    html += `<div class="text-sm text-muted-foreground">Datum: ${dateText}</div>`;
                                }
                                
                                if (hasReason && reasonText) {
                                    const displayText = reasonText.startsWith('Bericht:') ? reasonText : `Bericht: ${reasonText}`;
                                    html += `<div class="text-sm text-muted-foreground">${displayText}</div>`;
                                }
                                
                                // Add border between message and appointment details (always show border)
                                if (hasReason && (notification.scheduled_at_formatted || notification.location)) {
                                    html += `<div class="border-t border-border mt-2 pt-2"></div>`;
                                }
                                
                                // Appointment details (only for old format response notifications)
                                if (notification.scheduled_at_formatted || notification.location) {
                                    html += `<div class="flex flex-col gap-0.5 ${hasReason ? '' : 'mt-1'}">`;
                                    if (notification.scheduled_at_formatted) {
                                        html += `<div class="text-sm text-muted-foreground">Afspraakdetails: ${notification.scheduled_date || notification.scheduled_at_formatted.split(' ')[0]}${notification.scheduled_time ? ' ' + notification.scheduled_time : ''}</div>`;
                                    }
                                    if (notification.location) {
                                        html += `<div class="text-sm text-muted-foreground">${notification.location.name}${notification.location.city ? ' - ' + notification.location.city : ''}</div>`;
                                    }
                                    html += `</div>`;
                                }
                            } else {
                                // New interview notification (not yet accepted/declined) - show message and appointment details
                                console.log('📅 Rendering new interview notification (no response yet) for:', notification.id);
                                html += `<div class="text-sm text-muted-foreground">${messageText.length > 100 ? messageText.substring(0, 100) + '...' : messageText}</div>`;
                                
                                // Always show appointment details for new interview notifications (even if no response yet)
                                // Add border before appointment details - always show if we have any appointment data
                                const hasAppointmentData = notification.scheduled_at_formatted || notification.scheduled_at || notification.location || notification.location_or_type || notification.scheduled_date;
                                
                                console.log('📅 Appointment data check:', {
                                    hasAppointmentData: hasAppointmentData,
                                    scheduled_at_formatted: notification.scheduled_at_formatted,
                                    scheduled_at: notification.scheduled_at,
                                    location: notification.location,
                                    location_or_type: notification.location_or_type,
                                    scheduled_date: notification.scheduled_date
                                });
                                
                                if (hasAppointmentData) {
                                    html += `<div class="border-t border-border mt-2 pt-2"></div>`;
                                }
                                
                                // Appointment details for new interview notifications - always show if available
                                if (hasAppointmentData) {
                                    html += `<div class="flex flex-col gap-0.5 mt-1">`;
                                    html += `<div class="text-sm font-semibold text-foreground">Afspraakdetails:</div>`;
                                    if (notification.scheduled_at_formatted || notification.scheduled_date) {
                                        const dateStr = notification.scheduled_date || (notification.scheduled_at_formatted ? notification.scheduled_at_formatted.split(' ')[0] : '');
                                        const timeStr = notification.scheduled_time || (notification.scheduled_at_formatted && notification.scheduled_at_formatted.includes(' ') ? notification.scheduled_at_formatted.split(' ')[1] : '');
                                        html += `<div class="text-sm text-muted-foreground">${dateStr}${timeStr ? ' om ' + timeStr : ''}</div>`;
                                    }
                                    if (notification.location) {
                                        html += `<div class="text-sm text-muted-foreground">${notification.location.name}${notification.location.city ? ' - ' + notification.location.city : ''}</div>`;
                                    } else if (notification.location_or_type) {
                                        html += `<div class="text-sm text-muted-foreground">${notification.location_or_type}</div>`;
                                    }
                                    html += `</div>`;
                                } else {
                                    console.log('⚠️ No appointment data available for notification:', notification.id);
                                }
                            }
                            
                            return html;
                        })()}
                    ` : `
                        <div class="text-sm text-muted-foreground">
                            ${(() => {
                                // Check if this is a confirmation notification (new format without response)
                                if (notification.message && notification.message.includes('heeft de status') && notification.message.includes('gekregen')) {
                                    // New format - only show status message
                                    const statusMatch = notification.message.match(/heeft de status\s+([^.]+)\s+gekregen/);
                                    if (statusMatch) {
                                        return `Je geaccepteerde afspraak heeft de status <span class="font-semibold">${statusMatch[1].trim()}</span> gekregen.`;
                                    }
                                }
                                // Default: show full message
                                return `Bericht: ${notification.message.length > 100 ? notification.message.substring(0, 100) + '...' : notification.message}`;
                            })()}
                        </div>
                        ${isInterviewNotification ? `
                            ${(() => {
                                // Only show Afspraakdetails for new format confirmation notifications
                                if (notification.message && notification.message.includes('heeft de status') && notification.message.includes('gekregen')) {
                                    if (notification.scheduled_at_formatted || notification.location_or_type) {
                                        return `
                                            <div class="border-t border-border mt-2 pt-2"></div>
                                            <div class="flex flex-col gap-0.5 mt-1">
                                                <div class="text-sm font-semibold text-foreground">Afspraakdetails:</div>
                                                ${notification.scheduled_at_formatted ? `
                                                    <div class="text-sm text-muted-foreground">
                                                        ${notification.scheduled_date || notification.scheduled_at_formatted.split(' ')[0]}${notification.scheduled_time ? ' ' + notification.scheduled_time : ''}
                                                    </div>
                                                ` : ''}
                                                ${notification.location_or_type ? `
                                                    <div class="text-sm text-muted-foreground">
                                                        ${notification.location_or_type}
                                                    </div>
                                                ` : ''}
                                            </div>
                                        `;
                                    }
                                } else if (notification.scheduled_at_formatted || notification.location) {
                                    // Old format - show as before
                                    return `
                                        <div class="flex flex-col gap-0.5 mt-1">
                                            ${notification.scheduled_at_formatted ? `
                                                <div class="text-sm text-muted-foreground">
                                                    Afspraakdetails: ${notification.scheduled_date || notification.scheduled_at_formatted.split(' ')[0]}${notification.scheduled_time ? ' ' + notification.scheduled_time : ''}
                                                </div>
                                            ` : ''}
                                            ${notification.location ? `
                                                <div class="text-sm text-muted-foreground">
                                                    ${notification.location.name}${notification.location.city ? ' - ' + notification.location.city : ''}
                                                </div>
                                            ` : ''}
                                        </div>
                                    `;
                                }
                                return '';
                            })()}
                        ` : ''}
                    `}
                    
                    <span class="flex items-center text-xs font-medium text-muted-foreground">
                        ${notification.created_at_human}
                    </span>
                </div>
                ${fileSection}
                ${actionButtons}
                ${(() => {
                    // Show button for accepted interviews - check if match_id exists in data or notification
                    // This applies to response notifications (when candidate accepts) that have match_id
                    let matchId = notification.match_id;
                    
                    // Try to get match_id from data object if not directly available
                    if (!matchId && notification.data) {
                        if (typeof notification.data === 'object' && notification.data.match_id) {
                            matchId = notification.data.match_id;
                        } else if (typeof notification.data === 'string') {
                            try {
                                const parsedData = JSON.parse(notification.data);
                                matchId = parsedData.match_id || null;
                            } catch (e) {
                                // Ignore parse errors
                            }
                        }
                    }
                    
                    // Check if this is an accepted interview response notification
                    // Response notifications have:
                    // - title === 'Interview reactie'
                    // - response_type === 'accept' OR category === 'success' (green checkmark)
                    // - match_id in data
                    const isResponseNotification = notification.title === 'Interview reactie';
                    const isAccepted = notification.response_type === 'accept' || 
                                      notification.category === 'success' ||
                                      (notification.data && typeof notification.data === 'object' && notification.data.response === 'accept') ||
                                      (notification.data && typeof notification.data === 'string' && notification.data.includes('"response":"accept"'));
                    
                    // Debug: log notification details to help troubleshoot
                    if (isInterviewNotification && isResponseNotification && isAccepted && !matchId) {
                        console.log('Accepted interview notification without match_id:', {
                            id: notification.id,
                            title: notification.title,
                            category: notification.category,
                            response_type: notification.response_type,
                            match_id: notification.match_id,
                            data: notification.data
                        });
                    }
                    
                    // Show button if it's an interview response notification that was accepted
                    // Only show if match_id is available AND interview doesn't exist yet
                    if (isInterviewNotification && isResponseNotification && isAccepted && matchId && !notification.interview_exists) {
                        return `
                            <div class="mt-2">
                                <button class="kt-btn kt-btn-primary kt-btn-sm create-interview-btn" data-notification-id="${notification.id}" data-match-id="${matchId}" data-scheduled-at="${notification.scheduled_at || ''}" data-location-id="${notification.location_id || ''}" data-scheduled-date="${notification.scheduled_date || ''}" data-scheduled-time="${notification.scheduled_time || ''}">
                                    <i class="ki-filled ki-calendar me-2"></i>
                                    Afspraak in interviews zetten
                                </button>
                            </div>
                        `;
                    }
                    return '';
                })()}
            </div>
        </div>
    `;
    
    // Add click handler for interview buttons (match the condition used to show buttons)
    if (isInterviewNotification && !notification.has_response && !notification.response_type && !notification.interview_exists) {
        const acceptBtn = div.querySelector('.accept-interview');
        const declineBtn = div.querySelector('.decline-interview');
        
        console.log('[Notifications Drawer] Setting up interview button handlers:', {
            notificationId: notification.id,
            acceptBtnFound: !!acceptBtn,
            declineBtnFound: !!declineBtn,
            acceptBtnElement: acceptBtn,
            declineBtnElement: declineBtn,
            hasResponse: notification.has_response,
            interviewHasStatus: notification.interview_has_status
        });
        
        acceptBtn?.addEventListener('click', (e) => {
            console.log('[Notifications Drawer] Accept button clicked!', {
                notificationId: notification.id,
                event: e,
                target: e.target,
                currentTarget: e.currentTarget
            });
            e.stopPropagation();
            respondToInterview(notification.id, 'accept');
        });
        declineBtn?.addEventListener('click', (e) => {
            console.log('[Notifications Drawer] Decline button clicked!', {
                notificationId: notification.id,
                event: e,
                target: e.target,
                currentTarget: e.currentTarget
            });
            e.stopPropagation();
            respondToInterview(notification.id, 'decline');
        });
    }
    
    // Add click handler for "Afspraak in interviews zetten" button
    const createInterviewBtn = div.querySelector('.create-interview-btn');
    if (createInterviewBtn) {
        createInterviewBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            createInterviewFromNotification(notification);
        });
    }
    
    // Add checkbox handler
    div.querySelector('.notification-checkbox')?.addEventListener('change', function(e) {
        e.stopPropagation(); // Prevent triggering click on notification item
        if (this.checked) {
            selectedNotifications.add(notification.id);
        } else {
            selectedNotifications.delete(notification.id);
        }
        updateBulkActions();
    });
    
    // Add click handler to show notification details
    div.addEventListener('click', function(e) {
        // Don't trigger if clicking on checkbox, buttons, or links
        if (e.target.closest('.notification-checkbox') || 
            e.target.closest('button') || 
            e.target.closest('a')) {
            return;
        }
        showNotificationDetail(notification);
    });
    
    return div;
}

// Show notification detail view
function showNotificationDetail(notification) {
    const listView = document.getElementById('notifications_tab_all');
    const detailView = document.getElementById('notification_detail_view');
    const detailContent = document.getElementById('notification_detail_content');
    const detailTitle = document.getElementById('notification_detail_title');
    
    if (!listView || !detailView || !detailContent) return;
    
    // Hide list view and footer, show detail view
    listView.style.display = 'none';
    const footer = document.getElementById('notifications_all_footer');
    if (footer) {
        footer.style.display = 'none';
    }
    detailView.style.display = 'flex';
    
    // Get sender info - check if sender exists and has id
    const hasSender = notification.sender && notification.sender.id;
    let avatar = '/assets/media/avatars/300-2.png';
    let senderName = 'Systeem';
    let senderEmail = '';
    
    if (hasSender && notification.sender) {
        avatar = notification.sender.avatar || '/assets/media/avatars/300-2.png';
        senderName = notification.sender.name || notification.sender.email || 'Onbekende gebruiker';
        senderEmail = notification.sender.email || '';
    }
    
    // Debug: log sender info for troubleshooting
    if (!hasSender) {
    }
    
    // Mark as read if not already read
    if (!notification.is_read) {
        markNotificationAsRead(notification.id);
    }
    
    // Check if this is an interview notification - check both type and title
    const isInterviewNotification = notification.type === 'interview' || 
                                     (notification.title && (notification.title.toLowerCase().includes('interview') || notification.title.toLowerCase().includes('afspraak')));
    
    // Build action buttons
    let actionButtons = '';
    // Show action buttons for interview notifications if no response has been given yet AND no interview exists yet
    // Also check that response_type is not set (double check)
    // Only show buttons for NEW interview notifications without any response and without scheduled interview
    if (isInterviewNotification && !notification.has_response && !notification.response_type && !notification.interview_exists) {
        actionButtons = `
            <div class="flex flex-wrap gap-2.5 mt-4">
                <button class="kt-btn kt-btn-outline kt-btn-sm decline-interview" data-notification-id="${notification.id}">
                    Afwijzen
                </button>
                <button class="kt-btn kt-btn-mono kt-btn-sm accept-interview" data-notification-id="${notification.id}">
                    Accepteren
                </button>
            </div>
        `;
    } else if (isInterviewNotification && notification.has_response && notification.response_type === 'accept' && notification.match_id) {
        // Check if user is admin (on admin pages)
        const isAdmin = window.location.pathname.startsWith('/admin');
        
        if (isAdmin) {
            // Check if interview already exists
            if (notification.interview_exists) {
                // Show "Ingepland" status message
                actionButtons = `
                    <div class="flex flex-wrap gap-2.5 mt-4">
                        <div class="flex items-center gap-2 text-green-500">
                            <i class="ki-filled ki-check-circle text-green-500"></i>
                            <span class="font-medium text-green-500">Ingepland</span>
                        </div>
                    </div>
                `;
            } else {
                // No action buttons needed - "Afspraak in interviews zetten" button is shown separately
                actionButtons = '';
            }
        } else {
            // Show status message for candidates
            // If interview is scheduled (interview_exists = true), show "Ingepland", otherwise show "Verzoek geaccepteerd"
            const statusText = notification.interview_exists ? 'Ingepland' : 'Verzoek geaccepteerd';
            actionButtons = `
                <div class="flex flex-wrap gap-2.5 mt-4">
                    <div class="flex items-center gap-2 text-green-500">
                        <i class="ki-filled ki-check-circle text-green-500"></i>
                        <span class="font-medium text-green-500">${statusText}</span>
                    </div>
                </div>
            `;
        }
    } else if (isInterviewNotification && notification.has_response && notification.response_type === 'decline') {
        // Show status message for declined interviews (for candidates)
        const isAdmin = window.location.pathname.startsWith('/admin');
        if (!isAdmin) {
            actionButtons = `
                <div class="flex flex-wrap gap-2.5 mt-4">
                    <div class="flex items-center gap-2 text-red-500">
                        <i class="ki-filled ki-cross-circle text-red-500"></i>
                        <span class="font-medium text-red-500">Verzoek afgewezen</span>
                    </div>
                </div>
            `;
        }
    }
    
    // Build file section
    let fileSection = '';
    if (notification.file_path) {
        const fileSize = notification.file_size ? (notification.file_size / 1024).toFixed(2) + ' KB' : '';
        fileSection = `
            <div class="kt-card shadow-none flex flex-col gap-2 p-3 rounded-lg bg-muted/40 border border-gray-300 mt-4">
                <div class="text-xs font-medium text-muted-foreground/70">Bestand verzonden</div>
                <div class="flex items-center justify-between flex-row gap-1.5">
                    <div class="flex items-center gap-1.5">
                        <i class="ki-filled ki-file text-lg text-primary"></i>
                        <div class="flex flex-col gap-0.5">
                            <a href="${notification.file_path}" target="_blank" class="hover:text-primary hover:underline font-medium text-primary text-sm">
                                ${notification.file_name || 'Bestand'}
                            </a>
                            ${fileSize ? `<span class="font-medium text-muted-foreground text-xs">${fileSize}</span>` : ''}
                        </div>
                    </div>
                    <a href="${notification.file_path}" target="_blank" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost">
                        <i class="ki-filled ki-arrow-top-right"></i>
                    </a>
                </div>
            </div>
        `;
    }
    
    // Build detail content
    detailContent.innerHTML = `
        <div class="flex flex-col gap-4">
            <div class="flex items-start gap-3">
                <div class="kt-avatar">
                    <div class="kt-avatar-image">
                        <img alt="${senderName}" src="${avatar}">
                    </div>
                </div>
                <div class="flex flex-col gap-1 flex-1">
                    <div class="text-base font-semibold text-mono">${senderName || 'Systeem'}</div>
                    ${senderEmail ? `<div class="text-sm text-muted-foreground">${senderEmail}</div>` : ''}
                    <div class="text-xs text-muted-foreground">${notification.created_at_formatted}</div>
                </div>
            </div>
            <div class="text-lg font-semibold text-foreground">${notification.title}</div>
            ${isInterviewNotification ? `
                ${(() => {
                    // Parse the message - new format for confirmation notifications
                    let messageText = notification.message || '';
                    let html = '';
                    
                    // Check if this is the new format (confirmation notification)
                    // Format: "Je geaccepteerde afspraak heeft de status {status} gekregen."
                    if (messageText.includes('heeft de status') && messageText.includes('gekregen')) {
                        // New format - parse status only
                        const statusMatch = messageText.match(/heeft de status\s+([^.]+)\s+gekregen/);
                        
                        // Build HTML for new format - only show status message
                        if (statusMatch) {
                            html += `<div class="text-sm text-muted-foreground mb-2">Je geaccepteerde afspraak heeft de status <span class="font-semibold">${statusMatch[1].trim()}</span> gekregen.</div>`;
                        }
                        
                        // Add border before appointment details
                        if (notification.scheduled_at_formatted || notification.location_or_type) {
                            html += `<div class="border-t border-border mt-3 pt-3"></div>`;
                        }
                        
                        // Appointment details section
                        if (notification.scheduled_at_formatted || notification.location_or_type) {
                            html += `<div class="flex flex-col gap-2">`;
                            html += `<div class="text-sm font-semibold text-foreground mb-1">Afspraakdetails</div>`;
                            if (notification.scheduled_at_formatted) {
                                html += `<div class="flex items-center gap-2 text-sm text-foreground">
                                    <i class="ki-filled ki-calendar text-primary"></i>
                                    <span>${notification.scheduled_date || notification.scheduled_at_formatted.split(' ')[0]}${notification.scheduled_time ? ' om ' + notification.scheduled_time : ''}</span>
                                </div>`;
                            }
                            if (notification.location_or_type) {
                                html += `<div class="flex items-center gap-2 text-sm text-foreground">
                                    <i class="ki-filled ki-geolocation text-primary"></i>
                                    <span>${notification.location_or_type}</span>
                                </div>`;
                            }
                            html += `</div>`;
                        }
                    } else if (notification.has_response) {
                        // Old format - parse date, message, and location (for response notifications)
                        let dateText = '';
                        let reasonText = '';
                        let hasReason = false;
                        
                        if (messageText.includes('Datum:')) {
                            // Match date until comma, "Bericht:", "Locatie:", or end of string
                            const dateMatch = messageText.match(/Datum:\s*([^,]+?)(?=\s*(?:Bericht:|Locatie:|,|$))/);
                            if (dateMatch) {
                                dateText = dateMatch[1].trim();
                            }
                        }
                        
                        if (messageText.includes('Bericht:')) {
                            const berichtIndex = messageText.indexOf('Bericht:');
                            if (berichtIndex !== -1) {
                                let extractedText = messageText.substring(berichtIndex + 8).trim();
                                extractedText = extractedText.replace(/Datum:\s*[^,]+?(?=\s*(?:Bericht:|Locatie:|,|$))/g, '').trim();
                                extractedText = extractedText.replace(/Locatie:\s*[^,]+/g, '').trim();
                                extractedText = extractedText.replace(/\s+/g, ' ').replace(/^,\s*|\s*,$/g, '').trim();
                                reasonText = extractedText;
                                hasReason = reasonText.length > 0;
                            }
                        }
                        
                        if (dateText) {
                            html += `<div class="text-sm text-muted-foreground mb-2">Datum: ${dateText}</div>`;
                        }
                        
                        if (hasReason && reasonText) {
                            const displayText = reasonText.startsWith('Bericht:') ? reasonText : `Bericht: ${reasonText}`;
                            html += `<div class="text-sm text-muted-foreground mb-2">${displayText}</div>`;
                        }
                        
                        // Always show border between message and appointment details
                        if (hasReason && (notification.scheduled_at_formatted || notification.location)) {
                            html += `<div class="border-t border-border mt-3 pt-3"></div>`;
                        }
                        
                        // Appointment details (only for old format response notifications)
                        if (notification.scheduled_at_formatted || notification.location) {
                            html += `<div class="flex flex-col gap-2 ${hasReason ? '' : 'mt-2'}">`;
                            html += `<div class="text-sm font-semibold text-foreground mb-1">Afspraakdetails</div>`;
                            if (notification.scheduled_at_formatted) {
                                html += `<div class="flex items-center gap-2 text-sm text-foreground">
                                    <i class="ki-filled ki-calendar text-primary"></i>
                                    <span>${notification.scheduled_date || notification.scheduled_at_formatted.split(' ')[0]}${notification.scheduled_time ? ' om ' + notification.scheduled_time : ''}</span>
                                </div>`;
                            }
                            if (notification.location) {
                                html += `<div class="flex items-center gap-2 text-sm text-foreground">
                                    <i class="ki-filled ki-geolocation text-primary"></i>
                                    <span>${notification.location.name}${notification.location.city ? ' - ' + notification.location.city : ''}</span>
                                </div>`;
                            }
                            html += `</div>`;
                        }
                    } else {
                        // New interview notification (not yet accepted/declined) - show message and appointment details
                        html += `<div class="border-t border-border pt-4">
                            <div class="text-sm font-medium text-muted-foreground mb-2">Bericht</div>
                            <div class="text-sm text-foreground whitespace-pre-wrap mb-4">${notification.message}</div>
                        </div>`;
                        
                        // Always show appointment details for new interview notifications (even if no response yet)
                        const hasAppointmentData = notification.scheduled_at_formatted || notification.scheduled_at || notification.location || notification.location_or_type || notification.scheduled_date;
                        if (hasAppointmentData) {
                            html += `<div class="flex flex-col gap-2 p-3 rounded-lg bg-muted/50 border border-border mt-4">`;
                            html += `<div class="text-sm font-semibold text-foreground mb-1">Afspraakdetails</div>`;
                            if (notification.scheduled_at_formatted || notification.scheduled_date) {
                                const dateStr = notification.scheduled_date || (notification.scheduled_at_formatted ? notification.scheduled_at_formatted.split(' ')[0] : '');
                                const timeStr = notification.scheduled_time || (notification.scheduled_at_formatted && notification.scheduled_at_formatted.includes(' ') ? notification.scheduled_at_formatted.split(' ')[1] : '');
                                html += `<div class="flex items-center gap-2 text-sm text-foreground">
                                    <i class="ki-filled ki-calendar text-primary"></i>
                                    <span>${dateStr}${timeStr ? ' om ' + timeStr : ''}</span>
                                </div>`;
                            }
                            if (notification.location) {
                                html += `<div class="flex items-center gap-2 text-sm text-foreground">
                                    <i class="ki-filled ki-geolocation text-primary"></i>
                                    <span>${notification.location.name}${notification.location.city ? ' - ' + notification.location.city : ''}</span>
                                </div>`;
                            } else if (notification.location_or_type) {
                                html += `<div class="flex items-center gap-2 text-sm text-foreground">
                                    <i class="ki-filled ki-geolocation text-primary"></i>
                                    <span>${notification.location_or_type}</span>
                                </div>`;
                            }
                            html += `</div>`;
                        }
                    }
                    
                    return html;
                })()}
            ` : `
                <div class="border-t border-border pt-4">
                    <div class="text-sm font-medium text-muted-foreground mb-2">Bericht</div>
                    <div class="text-sm text-foreground whitespace-pre-wrap">${notification.message}</div>
                </div>
            `}
            ${fileSection}
            ${actionButtons}
            ${(() => {
                // Show button for accepted interviews - check if match_id exists in data or notification
                // This applies to response notifications (when candidate accepts) that have match_id
                let matchId = notification.match_id;
                
                // Try to get match_id from data object if not directly available
                if (!matchId && notification.data) {
                    if (typeof notification.data === 'object' && notification.data.match_id) {
                        matchId = notification.data.match_id;
                    } else if (typeof notification.data === 'string') {
                        try {
                            const parsedData = JSON.parse(notification.data);
                            matchId = parsedData.match_id || null;
                        } catch (e) {
                            // Ignore parse errors
                        }
                    }
                }
                
                // Check if this is an accepted interview response notification
                // Response notifications have:
                // - title === 'Interview reactie'
                // - response_type === 'accept' OR category === 'success' (green checkmark)
                // - match_id in data
                const isResponseNotification = notification.title === 'Interview reactie';
                const isAccepted = notification.response_type === 'accept' || 
                                  notification.category === 'success' ||
                                  (notification.data && typeof notification.data === 'object' && notification.data.response === 'accept') ||
                                  (notification.data && typeof notification.data === 'string' && notification.data.includes('"response":"accept"'));
                
                    // Show button if it's an interview response notification that was accepted
                    // Only show if match_id is available AND interview doesn't exist yet
                    if (isInterviewNotification && isResponseNotification && isAccepted && matchId && !notification.interview_exists) {
                    return `
                        <div class="mt-4">
                            <button class="kt-btn kt-btn-primary kt-btn-sm create-interview-btn" data-notification-id="${notification.id}" data-match-id="${matchId}" data-scheduled-at="${notification.scheduled_at || ''}" data-location-id="${notification.location_id || ''}" data-scheduled-date="${notification.scheduled_date || ''}" data-scheduled-time="${notification.scheduled_time || ''}">
                                <i class="ki-filled ki-calendar me-2"></i>
                                Afspraak in interviews zetten
                            </button>
                        </div>
                    `;
                }
                return '';
            })()}
        </div>
    `;
    
    // Add click handler for "Afspraak in interviews zetten" button in detail view
    const createInterviewBtnDetail = detailContent.querySelector('.create-interview-btn');
    if (createInterviewBtnDetail) {
        createInterviewBtnDetail.addEventListener('click', (e) => {
            e.stopPropagation();
            createInterviewFromNotification(notification);
        });
    }
    
    // Add click handlers for action buttons (match the condition used to show buttons)
    if (isInterviewNotification && !notification.has_response && !notification.response_type && !notification.interview_exists) {
        const acceptBtn = detailContent.querySelector('.accept-interview');
        const declineBtn = detailContent.querySelector('.decline-interview');
        
        console.log('[Notifications Drawer] Setting up detail view interview button handlers:', {
            notificationId: notification.id,
            acceptBtnFound: !!acceptBtn,
            declineBtnFound: !!declineBtn,
            acceptBtnElement: acceptBtn,
            declineBtnElement: declineBtn,
            hasResponse: notification.has_response,
            interviewHasStatus: notification.interview_has_status
        });
        
        acceptBtn?.addEventListener('click', (e) => {
            console.log('[Notifications Drawer] Detail view - Accept button clicked!', {
                notificationId: notification.id,
                event: e,
                target: e.target,
                currentTarget: e.currentTarget
            });
            e.stopPropagation();
            respondToInterview(notification.id, 'accept');
        });
        declineBtn?.addEventListener('click', (e) => {
            console.log('[Notifications Drawer] Detail view - Decline button clicked!', {
                notificationId: notification.id,
                event: e,
                target: e.target,
                currentTarget: e.currentTarget
            });
            e.stopPropagation();
            respondToInterview(notification.id, 'decline');
        });
    }
    
    // Update title
    if (detailTitle) {
        detailTitle.textContent = notification.title || 'Notificatie Details';
    }
}

// Mark notification as read
async function markNotificationAsRead(notificationId) {
    try {
        const baseUrl = getNotificationBaseUrl();
        const response = await fetch(`${baseUrl}/${notificationId}/mark-read`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                'Accept': 'application/json',
            }
        });
        
        if (response.ok) {
            // Update notification in local array
            const notification = notifications.find(n => n.id === notificationId);
            if (notification) {
                notification.is_read = true;
                notification.read_at = new Date().toISOString();
            }
            
            // Update UI directly without reloading
            updateNotificationElementUI(notificationId);
            
            // Update status in index page if it exists
            updateNotificationStatusInIndex([notificationId]);
            
            // Update badge
            if (window.updateNotificationBadge) {
                window.updateNotificationBadge();
            }
        }
    } catch (error) {
    }
}

// Update notification element UI directly
function updateNotificationElementUI(notificationId) {
    const notification = notifications.find(n => n.id === notificationId);
    if (!notification) return;
    
    // Find the notification element in the DOM
    const notificationEl = document.querySelector(`[data-notification-id="${notificationId}"]`);
    if (!notificationEl) return;
    
    // Update data-is-read attribute (CSS will handle the background color change)
    notificationEl.dataset.isRead = 'true';
    
    // Remove unread indicator dot
    const unreadDot = notificationEl.querySelector('.size-2.rounded-full.bg-primary');
    if (unreadDot) {
        unreadDot.remove();
    }
    
    // Remove unread avatar indicator
    const avatarIndicator = notificationEl.querySelector('.kt-avatar-indicator');
    if (avatarIndicator) {
        avatarIndicator.remove();
    }
}

// Back to list view
function backToListView() {
    const listView = document.getElementById('notifications_tab_all');
    const detailView = document.getElementById('notification_detail_view');
    const footer = document.getElementById('notifications_all_footer');
    
    if (listView && detailView) {
        listView.style.display = 'flex';
        detailView.style.display = 'none';
        // Show footer again when returning to list
        if (footer) {
            footer.style.display = 'grid';
        }
    }
}

// Show message input modal
function showMessageModal(response, callback) {
    // Create or get modal
    let modal = document.getElementById('interview-response-modal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'interview-response-modal';
        modal.className = 'fixed inset-0 hidden items-center justify-center';
        modal.style.zIndex = '100000';
        modal.style.setProperty('z-index', '100000', 'important');
        // Add backdrop styling directly to modal
        modal.style.backgroundColor = 'rgba(0, 0, 0, 0.6)';
        modal.style.backdropFilter = 'blur(8px)';
        modal.style.webkitBackdropFilter = 'blur(8px)';
        modal.innerHTML = `
            <div class="bg-background rounded-lg w-full max-w-md mx-4 relative border border-border shadow-xl overflow-hidden" style="background-color: var(--kt-body-bg, #ffffff);">
                <div class="flex items-center justify-between px-6 py-4 border-b border-border" style="background-color: var(--kt-body-bg, #ffffff);">
                    <h3 class="text-lg font-semibold text-foreground" id="interview-response-modal-title" style="color: var(--kt-body-color, #1f2937);">Bericht</h3>
                    <button type="button" id="interview-response-modal-close" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0" style="background: transparent; border: none;">
                        <i class="ki-filled ki-cross text-muted-foreground hover:text-foreground"></i>
                    </button>
                </div>
                <form id="interview-response-modal-form" class="flex flex-col p-6" style="background-color: var(--kt-body-bg, #ffffff);">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-foreground mb-2" id="interview-response-modal-label" style="color: var(--kt-body-color, #374151);">Bericht</label>
                        <textarea id="interview-response-modal-input" class="kt-input w-full" rows="4" placeholder="Voer hier je bericht in..." style="background-color: var(--kt-body-bg, #ffffff); color: var(--kt-body-color, #1f2937); border: 1px solid var(--border, #e5e7eb); padding: 0.75rem; border-radius: 0.375rem;"></textarea>
                    </div>
                    <div class="flex gap-2.5 mt-2">
                        <button type="button" id="interview-response-modal-cancel" class="kt-btn kt-btn-outline flex-1 justify-center" style="height: 38px; border-radius: 0.5rem; border: 1px solid var(--border, #e5e7eb);">Annuleren</button>
                        <button type="submit" class="kt-btn kt-btn-primary flex-1 justify-center" style="height: 38px; border-radius: 0.5rem; border: 1px solid var(--border, #e5e7eb);">Verzenden</button>
                    </div>
                </form>
            </div>
        `;
        document.body.appendChild(modal);
        
        // Close handlers
        const closeModal = () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            // Clean up ESC handler
            if (modal._escHandler) {
                document.removeEventListener('keydown', modal._escHandler, true);
                delete modal._escHandler;
            }
            if (modal._callback) {
                modal._callback(null); // User cancelled
            }
        };
        
        modal.querySelector('#interview-response-modal-close').addEventListener('click', closeModal);
        
        modal.querySelector('#interview-response-modal-cancel').addEventListener('click', closeModal);
        
        // Form submit - will be updated each time modal is shown
        const form = modal.querySelector('#interview-response-modal-form');
        // Remove old submit handler if exists
        if (form._submitHandler) {
            form.removeEventListener('submit', form._submitHandler);
        }
        
        // Close on backdrop click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                if (modal._callback) {
                    modal._callback(null); // User cancelled
                }
            }
        });
    }
    
    // Store current response and callback on modal
    modal._currentResponse = response;
    modal._callback = callback;
    
    // Set up form submit handler with current response
    const form = modal.querySelector('#interview-response-modal-form');
    // Remove old submit handler if exists
    if (form._submitHandler) {
        form.removeEventListener('submit', form._submitHandler);
    }
    
    // Create new submit handler that uses the current response
    form._submitHandler = (e) => {
        e.preventDefault();
        const input = modal.querySelector('#interview-response-modal-input');
        const message = input ? input.value.trim() : '';
        const currentResponse = modal._currentResponse; // Get current response from modal
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        // Clean up ESC handler
        if (modal._escHandler) {
            document.removeEventListener('keydown', modal._escHandler, true);
            delete modal._escHandler;
        }
        if (modal._callback) {
            modal._callback(message || '', currentResponse); // Pass both message and response
        }
    };
    form.addEventListener('submit', form._submitHandler);
    
    // Update modal content based on response type
    const title = modal.querySelector('#interview-response-modal-title');
    const label = modal.querySelector('#interview-response-modal-label');
    const input = modal.querySelector('#interview-response-modal-input');
    
    if (response === 'accept') {
        if (title) title.textContent = 'Interview Accepteren';
        if (label) label.textContent = 'Bericht (optioneel)';
        if (input) input.placeholder = 'Wil je een bericht toevoegen? (optioneel)';
    } else {
        if (title) title.textContent = 'Interview Afwijzen';
        if (label) label.textContent = 'Reden (optioneel)';
        if (input) input.placeholder = 'Wil je een reden opgeven voor de afwijzing? (optioneel)';
    }
    
    // Clear input and show modal
    if (input) {
        input.value = '';
    }
    
    // Always set up ESC handler when showing modal (even if modal already existed)
    // Remove existing handler if any
    if (modal._escHandler) {
        document.removeEventListener('keydown', modal._escHandler, true);
    }
    
    // Create new ESC handler that uses the current callback
    const escHandler = (e) => {
        if (e.key === 'Escape' || e.keyCode === 27) {
            // Only close if modal is visible
            if (!modal.classList.contains('hidden')) {
                e.stopPropagation(); // Prevent drawer from closing
                e.preventDefault(); // Prevent any default behavior
                // Clean up handler
                document.removeEventListener('keydown', escHandler, true);
                delete modal._escHandler;
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                if (modal._callback) {
                    modal._callback(null); // User cancelled
                }
            }
        }
    };
    
    document.addEventListener('keydown', escHandler, true); // Use capture phase
    modal._escHandler = escHandler; // Store for cleanup
    
    // Ensure modal is on top when showing
    modal.style.setProperty('z-index', '100000', 'important');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    if (input) {
        setTimeout(() => input.focus(), 100);
    }
}

// Respond to interview
async function respondToInterview(notificationId, response) {
    // Mark notification as read immediately when button is clicked (frontend only)
    const isFrontend = !window.location.pathname.startsWith('/admin');
    if (isFrontend) {
        // Mark as read immediately
        await markNotificationAsRead(notificationId);
    }
    
    // Show modal for message input
    showMessageModal(response, async (message, actualResponse) => {
        // If user cancelled (message is null), don't proceed
        if (message === null) {
            return;
        }
        
        // Use actualResponse from callback if provided, otherwise fall back to response parameter
        const responseToUse = actualResponse || response;
        
        console.log('[Notifications Drawer] Submitting interview response:', {
            notificationId: notificationId,
            response: responseToUse,
            message: message
        });
        
        try {
            const baseUrl = getNotificationBaseUrl();
            const responseData = await fetch(`${baseUrl}/${notificationId}/respond-interview`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    response: responseToUse,
                    message: message || '',
                })
            });
            
            if (!responseData.ok) {
                const errorData = await responseData.json();
                throw new Error(errorData.message || 'Failed to respond');
            }
            
            // Update the notification in the list immediately (replace buttons with status message)
            const notificationElement = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (notificationElement) {
                // Find action buttons container
                const actionButtonsContainer = notificationElement.querySelector('div.flex.flex-wrap');
                if (actionButtonsContainer && actionButtonsContainer.querySelector('button.accept-interview, button.decline-interview')) {
                    // Replace buttons with status message (use responseToUse)
                    const statusText = responseToUse === 'accept' ? 'Verzoek geaccepteerd' : 'Verzoek afgewezen';
                    const statusColor = responseToUse === 'accept' ? 'text-green-500' : 'text-red-500';
                    const statusIcon = responseToUse === 'accept' ? 'ki-check-circle' : 'ki-cross-circle';
                    
                    actionButtonsContainer.innerHTML = `
                        <div class="flex items-center gap-2 ${statusColor}">
                            <i class="ki-filled ${statusIcon} ${statusColor}"></i>
                            <span class="font-medium ${statusColor}">${statusText}</span>
                        </div>
                    `;
                }
                
                // Update notification element UI to show as read
                updateNotificationElementUI(notificationId);
                
                // Update the notification in the notifications array immediately
                // This ensures that when notifications are re-rendered, buttons won't appear again
                const notificationIndex = notifications.findIndex(n => n.id === parseInt(notificationId));
                if (notificationIndex !== -1) {
                    notifications[notificationIndex].has_response = true;
                    notifications[notificationIndex].response_type = responseToUse;
                }
                
                // Update status in admin index page table if it exists
                updateNotificationStatusInIndex([notificationId]);
            }
            
            // Update detail view if open
            const detailView = document.getElementById('notification_detail_view');
            if (detailView && detailView.style.display !== 'none') {
                // Update action buttons in detail view
                const detailActionButtonsContainer = detailView.querySelector('div.flex.flex-wrap');
                if (detailActionButtonsContainer && detailActionButtonsContainer.querySelector('button.accept-interview, button.decline-interview')) {
                    const statusText = responseToUse === 'accept' ? 'Verzoek geaccepteerd' : 'Verzoek afgewezen';
                    const statusColor = responseToUse === 'accept' ? 'text-green-500' : 'text-red-500';
                    const statusIcon = responseToUse === 'accept' ? 'ki-check-circle' : 'ki-cross-circle';
                    
                    detailActionButtonsContainer.innerHTML = `
                        <div class="flex items-center gap-2 ${statusColor}">
                            <i class="ki-filled ${statusIcon} ${statusColor}"></i>
                            <span class="font-medium ${statusColor}">${statusText}</span>
                        </div>
                    `;
                }
                // Go back to list after a short delay to show the status
                setTimeout(() => {
                    backToListView();
                }, 1500);
            }
            
            // Reload notifications to get updated data from database
            await loadNotifications();
            
            // Double-check: Update the notification element again after reload to ensure correct status
            const notificationElementAfterReload = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (notificationElementAfterReload) {
                // Find the notification in the reloaded data
                const reloadedNotification = notifications.find(n => n.id === parseInt(notificationId));
                if (reloadedNotification) {
                    console.log('[Notifications Drawer] Reloaded notification data:', {
                        notificationId: notificationId,
                        hasResponse: reloadedNotification.has_response,
                        responseType: reloadedNotification.response_type,
                        expectedResponse: responseToUse
                    });
                    
                    // Update status based on reloaded data
                    const actionButtonsContainer = notificationElementAfterReload.querySelector('div.flex.flex-wrap');
                    if (actionButtonsContainer) {
                        if (reloadedNotification.has_response || reloadedNotification.response_type) {
                            // For candidates: if interview is scheduled, show "Ingepland", otherwise show "Verzoek geaccepteerd"
                            const isAdmin = window.location.pathname.startsWith('/admin');
                            let statusText = '';
                            if (!isAdmin && reloadedNotification.response_type === 'accept') {
                                statusText = reloadedNotification.interview_exists ? 'Ingepland' : 'Verzoek geaccepteerd';
                            } else {
                                statusText = reloadedNotification.response_type === 'accept' ? 'Verzoek geaccepteerd' : 'Verzoek afgewezen';
                            }
                            const statusColor = reloadedNotification.response_type === 'accept' ? 'text-green-500' : 'text-red-500';
                            const statusIcon = reloadedNotification.response_type === 'accept' ? 'ki-check-circle' : 'ki-cross-circle';
                            
                            actionButtonsContainer.innerHTML = `
                                <div class="flex items-center gap-2 ${statusColor}">
                                    <i class="ki-filled ${statusIcon} ${statusColor}"></i>
                                    <span class="font-medium ${statusColor}">${statusText}</span>
                                </div>
                            `;
                        }
                    }
                }
            }
            
            // Update status in admin index page table again after reload (to ensure it's marked as read)
            updateNotificationStatusInIndex([notificationId]);
            
            if (window.updateNotificationBadge) {
                window.updateNotificationBadge();
            }
        } catch (error) {
            // Try to use alert, but if it fails, log to console
            try {
                alert('Er is een fout opgetreden bij het versturen van je reactie: ' + error.message);
            } catch (e) {
                console.error('Error responding to interview:', error);
            }
        }
    });
}

// Schedule interview (when sender clicks "Inplannen")
async function scheduleInterview(notificationId, matchId) {
    try {
        // Find the notification in the current notifications array to get all data
        const notification = notifications.find(n => n.id === parseInt(notificationId));
        
        // Build query parameters with all relevant data
        const params = new URLSearchParams({
            match_id: matchId,
            notification_id: notificationId,
        });
        
        // Add notification data if available
        if (notification) {
            if (notification.scheduled_at) {
                params.append('scheduled_at', notification.scheduled_at);
            }
            
            if (notification.location_id !== null && notification.location_id !== undefined) {
                // Convert -1 (Op afstand) to "remote" for the form
                if (notification.location_id === -1 || notification.location_id === '-1') {
                    params.append('location_id', 'remote');
                } else {
                    params.append('location_id', notification.location_id);
                }
            }
            
            if (notification.scheduled_date) {
                params.append('scheduled_date', notification.scheduled_date);
            }
            
            if (notification.scheduled_time) {
                params.append('scheduled_time', notification.scheduled_time);
            }
        }
        
        // Determine the correct URL based on current path
        const isAdmin = window.location.pathname.startsWith('/admin');
        const url = isAdmin 
            ? `/admin/interviews/create?${params.toString()}`
            : `/interviews/create?${params.toString()}`;
        
        window.location.href = url;
    } catch (error) {
        console.error('Error scheduling interview:', error);
        // Fallback: redirect to interviews create page with at least match_id
        const isAdmin = window.location.pathname.startsWith('/admin');
        const url = isAdmin 
            ? `/admin/interviews/create?match_id=${matchId}&notification_id=${notificationId}`
            : `/interviews/create?match_id=${matchId}&notification_id=${notificationId}`;
        window.location.href = url;
    }
}

// Create interview from notification (when user clicks "Afspraak in interviews zetten")
function createInterviewFromNotification(notification) {
    // Get match_id from notification or data
    let matchId = notification.match_id;
    if (!matchId && notification.data) {
        if (typeof notification.data === 'object' && notification.data.match_id) {
            matchId = notification.data.match_id;
        } else if (typeof notification.data === 'string') {
            try {
                const parsedData = JSON.parse(notification.data);
                matchId = parsedData.match_id || null;
            } catch (e) {
                // Ignore parse errors
            }
        }
    }
    
    if (!matchId) {
        alert('Geen match gevonden voor deze notificatie.');
        return;
    }
    
    const baseUrl = getNotificationBaseUrl();
    const params = new URLSearchParams({
        match_id: matchId,
        notification_id: notification.id,
    });
    
    if (notification.scheduled_at) {
        params.append('scheduled_at', notification.scheduled_at);
    }
    
    if (notification.location_id !== null && notification.location_id !== undefined) {
        // Convert -1 (Op afstand) to "remote" for the form
        if (notification.location_id === -1 || notification.location_id === '-1') {
            params.append('location_id', 'remote');
        } else {
            params.append('location_id', notification.location_id);
        }
    }
    
    if (notification.scheduled_date) {
        params.append('scheduled_date', notification.scheduled_date);
    }
    
    if (notification.scheduled_time) {
        params.append('scheduled_time', notification.scheduled_time);
    }
    
    // Determine the correct URL based on baseUrl
    let url = '';
    if (baseUrl.includes('/admin')) {
        url = `/admin/interviews/create?${params.toString()}`;
    } else {
        url = `/interviews/create?${params.toString()}`;
    }
    
    window.location.href = url;
}

// Store original event handlers
let originalArchiveHandler = null;
let originalMarkReadHandler = null;
let currentArchiveHandler = null;
let currentMarkReadHandler = null;

// Update bulk actions visibility and button text/functionality
function updateBulkActions() {
    const footer = document.querySelector('#notifications_all_footer');
    if (!footer) return;
    
    const hasSelection = selectedNotifications.size > 0;
    const archiveAllBtn = document.getElementById('archive_all_btn');
    const markAllReadBtn = document.getElementById('mark_all_read_btn');
    
    if (archiveAllBtn) {
        // Remove current handler
        if (currentArchiveHandler) {
            archiveAllBtn.removeEventListener('click', currentArchiveHandler);
        }
        
        if (hasSelection) {
            // Change text and functionality for selected items
            archiveAllBtn.textContent = 'Selectie archiveren';
            currentArchiveHandler = async function() {
                if (selectedNotifications.size === 0) return;
                await archiveSelected();
            };
        } else {
            // Restore original text and functionality
            archiveAllBtn.textContent = 'Alles archiveren';
            currentArchiveHandler = originalArchiveHandler;
        }
        
        // Add the appropriate handler
        if (currentArchiveHandler) {
            archiveAllBtn.addEventListener('click', currentArchiveHandler);
        }
        archiveAllBtn.style.display = 'flex';
    }
    
    if (markAllReadBtn) {
        // Remove current handler
        if (currentMarkReadHandler) {
            markAllReadBtn.removeEventListener('click', currentMarkReadHandler);
        }
        
        if (hasSelection) {
            // Change text and functionality for selected items
            markAllReadBtn.textContent = 'Selectie als gelezen markeren';
            currentMarkReadHandler = async function() {
                if (selectedNotifications.size === 0) return;
                await markSelectedAsRead();
            };
        } else {
            // Restore original text and functionality
            markAllReadBtn.textContent = 'Alles als gelezen markeren';
            currentMarkReadHandler = originalMarkReadHandler;
        }
        
        // Add the appropriate handler
        if (currentMarkReadHandler) {
            markAllReadBtn.addEventListener('click', currentMarkReadHandler);
        }
        markAllReadBtn.style.display = 'flex';
    }
}

// Mark selected as read
async function markSelectedAsRead() {
    if (selectedNotifications.size === 0) return;
    
    try {
        const baseUrl = getNotificationBaseUrl();
        const response = await fetch(`${baseUrl}/mark-selected-read`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                notification_ids: Array.from(selectedNotifications),
            })
        });
        
        if (!response.ok) throw new Error('Failed to mark as read');
        
        const ids = Array.from(selectedNotifications);
        selectedNotifications.clear();
        updateBulkActions();
        
        // Update UI directly for each notification
        ids.forEach(id => {
            const notification = notifications.find(n => n.id === id);
            if (notification) {
                notification.is_read = true;
                notification.read_at = new Date().toISOString();
                updateNotificationElementUI(id);
            }
        });
        
        // Update status in index page if it exists
        updateNotificationStatusInIndex(ids);
        
        if (window.updateNotificationBadge) {
            window.updateNotificationBadge();
        }
    } catch (error) {
        try {
            alert('Er is een fout opgetreden.');
        } catch (e) {
            console.error('Error:', error);
        }
    }
}

// Archive selected
async function archiveSelected() {
    if (selectedNotifications.size === 0) return;
    
    const ids = Array.from(selectedNotifications);
    // Show confirmation using a simple approach
    let confirmed = false;
    try {
        confirmed = confirm(`Weet je zeker dat je ${ids.length} notificatie(s) wilt archiveren?`);
    } catch (e) {
        // If confirm is not available, proceed anyway (for testing/automation)
        confirmed = true;
    }
    if (!confirmed) {
        return;
    }
    
    try {
        const baseUrl = getNotificationBaseUrl();
        const response = await fetch(`${baseUrl}/archive-selected`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                notification_ids: ids,
            })
        });
        
        if (!response.ok) throw new Error('Failed to archive');
        
        selectedNotifications.clear();
        updateBulkActions();
        await loadNotifications();
        if (window.updateNotificationBadge) {
            window.updateNotificationBadge();
        }
    } catch (error) {
        try {
            alert('Er is een fout opgetreden.');
        } catch (e) {
            console.error('Error:', error);
        }
    }
}

// Mark all as read
async function markAllAsRead() {
    try {
        const baseUrl = getNotificationBaseUrl();
        const response = await fetch(`${baseUrl}/mark-all-read`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                'Accept': 'application/json',
            }
        });
        
        if (!response.ok) throw new Error('Failed to mark all as read');
        
        // Update all notifications in local array and UI
        notifications.forEach(notification => {
            notification.is_read = true;
            notification.read_at = new Date().toISOString();
            updateNotificationElementUI(notification.id);
        });
        
        // Update status in index page for all notifications
        const allIds = notifications.map(n => n.id);
        updateNotificationStatusInIndex(allIds);
        
        if (window.updateNotificationBadge) {
            window.updateNotificationBadge();
        }
    } catch (error) {
        try {
            alert('Er is een fout opgetreden.');
        } catch (e) {
            console.error('Error:', error);
        }
    }
}

// Start polling for new notifications when drawer is open (both admin and frontend)
function startNotificationPolling() {
    // Clear any existing interval
    if (notificationsPollingInterval) {
        clearInterval(notificationsPollingInterval);
    }
    
    // Poll every 5 seconds for new notifications
    notificationsPollingInterval = setInterval(() => {
        const drawer = document.getElementById('notifications_drawer');
        if (drawer && !drawer.classList.contains('hidden') && drawer.getAttribute('data-drawer-closed') !== 'true') {
            // Only poll if drawer is open and page is visible
            if (document.visibilityState === 'visible') {
                // Use checkForNewNotifications instead of loadNotifications to avoid disrupting user interactions
                checkForNewNotifications();
            }
        } else {
            // Drawer is closed, stop polling
            stopNotificationPolling();
        }
    }, 5000);
}

// Stop polling for notifications
function stopNotificationPolling() {
    if (notificationsPollingInterval) {
        clearInterval(notificationsPollingInterval);
        notificationsPollingInterval = null;
    }
}

// Handle drawer close
function handleNotificationDrawerClose() {
    // Stop polling when drawer is closed
    stopNotificationPolling();
    // Stop polling when drawer closes
    stopNotificationPolling();
    const drawer = document.getElementById('notifications_drawer');
    const backdrop = document.getElementById('notifications_drawer_backdrop');
    
    if (!drawer) return;
    
    // Set flag to prevent drawer from reopening
    drawer.setAttribute('data-drawer-closed', 'true');
    drawer.classList.add('hidden');
    drawer.classList.remove('open');
    drawer.removeAttribute('data-notifications-active');
    drawer.removeAttribute('data-user-opened');
    
    // Force hide drawer
    drawer.style.setProperty('display', 'none', 'important');
    drawer.style.setProperty('visibility', 'hidden', 'important');
    drawer.style.setProperty('opacity', '0', 'important');
    drawer.style.setProperty('transform', 'translateX(100%)', 'important');
    
    // Hide backdrop
    if (backdrop) {
        backdrop.classList.add('hidden');
        backdrop.style.setProperty('display', 'none', 'important');
        backdrop.style.setProperty('visibility', 'hidden', 'important');
        backdrop.style.setProperty('opacity', '0', 'important');
    }
    
    // Restore body scroll
    document.body.style.overflow = '';
}

// Make function globally available
window.handleNotificationDrawerClose = handleNotificationDrawerClose;

// Initialize when drawer opens
document.addEventListener('DOMContentLoaded', function() {
    const drawer = document.getElementById('notifications_drawer');
    const backdrop = document.getElementById('notifications_drawer_backdrop');
    if (!drawer) return;
    
    // Load notifications immediately on page load (like frontend)
    loadNotifications();
    
    // Also reload when drawer opens (using MutationObserver to detect when drawer becomes visible)
    let lastDrawerState = {
        isOpen: false,
        isVisible: false
    };
    
    const checkDrawerAndLoad = function() {
        const isOpen = drawer.classList.contains('open') && !drawer.classList.contains('hidden');
        const isVisible = drawer.style.display !== 'none' && 
                         drawer.style.visibility !== 'hidden' && 
                         drawer.style.opacity !== '0' &&
                         drawer.getAttribute('data-notifications-active') === 'true';
        
        // If drawer just became visible/open, load notifications and start polling
        if ((isOpen || isVisible) && (!lastDrawerState.isOpen && !lastDrawerState.isVisible)) {
            console.log('[Notifications Drawer] Drawer opened, loading notifications...');
            loadNotifications();
            // Start polling when drawer opens
            startNotificationPolling();
        } else if ((isOpen || isVisible) && (lastDrawerState.isOpen || lastDrawerState.isVisible)) {
            // Drawer is still open, make sure polling is running
            if (!notificationsPollingInterval) {
                startNotificationPolling();
            }
        } else if (!isOpen && !isVisible && (lastDrawerState.isOpen || lastDrawerState.isVisible)) {
            // Drawer just closed, stop polling
            stopNotificationPolling();
        }
        
        lastDrawerState = { isOpen, isVisible };
    };
    
    const observer = new MutationObserver(function(mutations) {
        let shouldCheck = false;
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes') {
                if (mutation.attributeName === 'class' || 
                    mutation.attributeName === 'data-notifications-active' || 
                    mutation.attributeName === 'data-user-opened' ||
                    mutation.attributeName === 'style') {
                    shouldCheck = true;
                }
            }
        });
        
        if (shouldCheck) {
            checkDrawerAndLoad();
        }
    });
    
    observer.observe(drawer, {
        attributes: true,
        attributeFilter: ['class', 'data-notifications-active', 'data-user-opened', 'style'],
        attributeOldValue: false
    });
    
    // Also check periodically if drawer is open (fallback mechanism)
    setInterval(function() {
        checkDrawerAndLoad();
    }, 1000); // Check every second
    
    // Setup bulk action buttons
    const footer = document.querySelector('#notifications_all_footer');
    if (footer) {
        const archiveAllBtn = document.getElementById('archive_all_btn');
        const markAllReadBtn = document.getElementById('mark_all_read_btn');
        
        if (archiveAllBtn) {
            // Store original handler
            originalArchiveHandler = async function() {
                let confirmed = false;
                try {
                    confirmed = confirm('Weet je zeker dat je alle notificaties wilt archiveren?');
                } catch (e) {
                    // If confirm is not available, proceed anyway
                    confirmed = true;
                }
                if (confirmed) {
                    // Archive all visible notifications
                    const allIds = notifications.map(n => n.id);
                    selectedNotifications = new Set(allIds);
                    await archiveSelected();
                }
            };
            currentArchiveHandler = originalArchiveHandler;
            archiveAllBtn.addEventListener('click', currentArchiveHandler);
        }
        
        if (markAllReadBtn) {
            // Store original handler
            originalMarkReadHandler = markAllAsRead;
            currentMarkReadHandler = originalMarkReadHandler;
            markAllReadBtn.addEventListener('click', currentMarkReadHandler);
        }
    }
    
    // Also listen for drawer toggle button clicks
    document.querySelectorAll('[data-kt-drawer-toggle="#notifications_drawer"], .notification-icon-button').forEach(btn => {
        btn.addEventListener('click', function(e) {
            // Only proceed if user is authenticated (for frontend)
            const isAuthenticated = document.querySelector('meta[name="auth-check"]')?.getAttribute('content') === 'true' || 
                                   document.querySelector('meta[name="auth-check"]') === null; // Admin pages don't have this meta
            
            if (!isAuthenticated && window.location.pathname.startsWith('/')) {
                return;
            }
            
            // Prevent default drawer toggle behavior
            e.stopPropagation();
            
            // Force open drawer similar to chat drawer
            setTimeout(() => {
                // Reset the closed flag - allow drawer to open again
                drawer.removeAttribute('data-drawer-closed');
                
                // Ensure drawer is visible (user explicitly clicked the button)
                drawer.classList.remove('hidden');
                drawer.classList.add('open');
                drawer.setAttribute('data-notifications-active', 'true');
                drawer.setAttribute('data-user-opened', 'true');
                
                drawer.style.setProperty('display', 'flex', 'important');
                drawer.style.setProperty('visibility', 'visible', 'important');
                drawer.style.setProperty('opacity', '1', 'important');
                drawer.style.setProperty('z-index', '99999', 'important');
                drawer.style.setProperty('transform', 'translateX(0)', 'important');
                drawer.style.setProperty('right', '1.25rem', 'important');
                drawer.style.setProperty('left', 'unset', 'important');
                
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
                
                loadNotifications();
                
                // Start polling for new notifications (frontend only)
                const isFrontend = !window.location.pathname.startsWith('/admin');
                if (isFrontend) {
                    startNotificationPolling();
                }
            }, 50);
        });
    });
    
    // Handle drawer close button
    drawer.querySelectorAll('[data-kt-drawer-dismiss="true"]').forEach(btn => {
        btn.addEventListener('click', function() {
            handleNotificationDrawerClose();
        });
    });
    
    // Handle back to list button (use event delegation since button is in detail view)
    drawer.addEventListener('click', function(e) {
        if (e.target.closest('.back-to-list-btn')) {
            e.preventDefault();
            backToListView();
        }
    });
    
    // Global click listener for interview buttons (event delegation as fallback)
    drawer.addEventListener('click', function(e) {
        const acceptBtn = e.target.closest('.accept-interview');
        const declineBtn = e.target.closest('.decline-interview');
        
        if (acceptBtn || declineBtn) {
            e.preventDefault();
            e.stopPropagation();
            
            const button = acceptBtn || declineBtn;
            const notificationId = button.getAttribute('data-notification-id');
            const buttonType = acceptBtn ? 'accept' : 'decline';
            
            console.log('[Notifications Drawer] Global click listener handling interview button click:', {
                buttonType: buttonType,
                notificationId: notificationId,
                target: e.target,
                buttonElement: button
            });
            
            // Call respondToInterview function to open the modal
            if (notificationId && typeof respondToInterview === 'function') {
                respondToInterview(notificationId, buttonType);
            } else {
                console.error('[Notifications Drawer] Cannot call respondToInterview:', {
                    notificationId: notificationId,
                    respondToInterviewExists: typeof respondToInterview === 'function'
                });
            }
        }
    }, true); // Use capture phase to catch event early
    
    // Handle backdrop click to close drawer
    if (backdrop) {
        backdrop.addEventListener('click', function(e) {
            // Only close if clicking directly on backdrop, not on drawer
            if (e.target === backdrop) {
                handleNotificationDrawerClose();
            }
        });
    }
    
    // Handle ESC key to close drawer - but only if no modal is open
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' || e.keyCode === 27) {
            // Check if interview response modal is open
            const interviewModal = document.getElementById('interview-response-modal');
            if (interviewModal && !interviewModal.classList.contains('hidden')) {
                // Modal is open, let it handle the ESC key (it will stop propagation)
                return;
            }
            
            // No modal open, close drawer
            const drawer = document.getElementById('notifications_drawer');
            if (drawer && !drawer.classList.contains('hidden') && drawer.getAttribute('data-drawer-closed') !== 'true') {
                e.preventDefault();
                e.stopPropagation();
                handleNotificationDrawerClose();
            }
        }
    }, true); // Use capture phase to catch event early
    
    // Listen for drawer show events
    drawer.addEventListener('shown.kt.drawer', loadNotifications);
    
    // Also try to initialize drawer if KTUI is available
    if (typeof KTUI !== 'undefined' && KTUI.Drawer) {
        const drawerInstance = KTUI.Drawer.getInstance(drawer);
        if (drawerInstance) {
            drawer.addEventListener('shown', loadNotifications);
        }
    }
    
    // Additional check: Load notifications when drawer becomes visible (for both admin and frontend)
    // Use IntersectionObserver to detect when drawer becomes visible
    const visibilityObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && entry.target === drawer) {
                // Drawer is visible, load notifications and start polling
                const container = document.querySelector('#notifications_list');
                // Always load when drawer becomes visible (not just when empty)
                // This ensures fresh data when drawer is opened
                console.log('[Notifications Drawer] Drawer became visible, loading notifications...');
                loadNotifications();
                // Start polling when drawer becomes visible
                startNotificationPolling();
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px'
    });
    
    // Observe drawer for visibility changes
    visibilityObserver.observe(drawer);
    
    // Also check on window focus (user might have opened drawer in another tab/window)
    window.addEventListener('focus', function() {
        const isOpen = drawer.classList.contains('open') && !drawer.classList.contains('hidden');
        const isVisible = drawer.style.display !== 'none' && 
                         drawer.style.visibility !== 'hidden' && 
                         drawer.getAttribute('data-notifications-active') === 'true';
        if (isOpen || isVisible) {
            console.log('[Notifications Drawer] Window focused with drawer open, loading notifications...');
            loadNotifications();
            // Make sure polling is running when window gets focus
            if (!notificationsPollingInterval) {
                startNotificationPolling();
            }
        }
    });
    
});

// Update notification status in index page
function updateNotificationStatusInIndex(notificationIds) {
    notificationIds.forEach(id => {
        const row = document.querySelector(`tr.notification-row[data-notification-id="${id}"]`);
        if (row) {
            // Find status cell - it's now the 4th td (index 3) after adding sender column
            // Columns: user (0), sender (1), message (2), status (3), date (4), actions (5)
            const cells = row.querySelectorAll('td');
            const statusCell = cells[3];
            
            if (statusCell) {
                // Update badge to "Gelezen" with success styling
                const badge = statusCell.querySelector('.kt-badge');
                if (badge) {
                    badge.textContent = 'Gelezen';
                    badge.className = 'kt-badge kt-badge-sm kt-badge-success';
                    badge.classList.remove('kt-badge-warning');
                    badge.classList.add('kt-badge-success');
                } else {
                    statusCell.innerHTML = '<span class="kt-badge kt-badge-sm kt-badge-success">Gelezen</span>';
                }
            }
        }
    });
}

// Export for use in other scripts
window.loadNotifications = loadNotifications;
window.markSelectedAsRead = markSelectedAsRead;
window.archiveSelected = archiveSelected;
