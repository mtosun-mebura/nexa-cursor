@php
    $debugPanelEnabled = false;
    $isSuperAdmin = false;
    if (auth()->check() && auth()->user()->hasRole('super-admin')) {
        $isSuperAdmin = true;
        try {
            $envService = app(\App\Services\EnvService::class);
            $debugPanelEnabled = $envService->get('ADMIN_DEBUG_PANEL_ENABLED', 'false') === 'true';
        } catch (\Exception $e) {
            $debugPanelEnabled = config('app.debug', false);
        }
    }
    
    // Get user permissions for JavaScript
    $userPermissions = auth()->check() ? auth()->user()->getAllPermissions()->pluck('name')->toArray() : [];
@endphp

@if($debugPanelEnabled)
<style>
    /* Debug indicators styling */
    .kt-debug-indicator {
        position: relative;
    }
    
    /* Badge indicator (for links, buttons) */
    .kt-debug-badge {
        position: absolute;
        top: 2px;
        end: 2px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        border: 2px solid var(--background);
        z-index: 10;
        pointer-events: none;
    }
    
    .kt-debug-badge.has-access {
        background-color: #10b981; /* green */
    }
    
    .kt-debug-badge.no-access {
        background-color: #ef4444; /* red */
    }
    
    /* Card top border indicator */
    .kt-debug-card-overlay {
        position: absolute;
        top: 0;
        start: 0;
        end: 0;
        height: 3px;
        z-index: 5;
        pointer-events: none;
    }
    
    .kt-debug-card-overlay.has-access {
        background-color: #10b981;
    }
    
    .kt-debug-card-overlay.no-access {
        background-color: #ef4444;
    }
    
    .kt-card.kt-debug-indicator {
        position: relative;
    }
    
    /* Sidebar menu items */
    .kt-menu-link.kt-debug-indicator {
        position: relative;
    }
    
    /* Buttons */
    .kt-btn.kt-debug-indicator {
        position: relative;
    }
    
    /* Table rows */
    .kt-debug-row-indicator {
        position: absolute;
        start: 0;
        top: 0;
        bottom: 0;
        width: 3px;
        pointer-events: none;
    }
    
    .kt-debug-row-indicator.has-access {
        background-color: #10b981;
    }
    
    .kt-debug-row-indicator.no-access {
        background-color: #ef4444;
    }
    
    tr.kt-debug-indicator {
        position: relative;
    }
    
    /* Input fields and dropdowns */
    input.kt-debug-indicator,
    select.kt-debug-indicator,
    textarea.kt-debug-indicator {
        position: relative;
    }
    
    .kt-debug-input-indicator {
        position: absolute;
        top: 50%;
        end: 8px;
        transform: translateY(-50%);
        width: 10px;
        height: 10px;
        border-radius: 50%;
        border: 2px solid var(--background);
        z-index: 100;
        pointer-events: none;
        box-shadow: 0 0 0 1px rgba(0,0,0,0.1);
    }
    
    /* For select elements, position indicator more to the right to avoid dropdown arrow */
    select.kt-debug-indicator .kt-debug-input-indicator {
        end: 30px; /* More space for dropdown arrow */
    }
    
    /* Ensure indicators are visible on flex items */
    .flex select.kt-debug-indicator,
    .flex input.kt-debug-indicator,
    .flex textarea.kt-debug-indicator {
        position: relative;
    }
    
    /* kt-select wrapper indicators */
    [data-kt-select-wrapper].kt-debug-indicator {
        position: relative;
    }
    
    [data-kt-select-wrapper] .kt-debug-input-indicator {
        end: 30px; /* More space for dropdown arrow */
    }
    
    .kt-debug-input-indicator.has-access {
        background-color: #10b981;
    }
    
    .kt-debug-input-indicator.no-access {
        background-color: #ef4444;
    }
    
    /* Input wrapper for better positioning */
    .kt-debug-input-wrapper {
        position: relative;
        display: inline-block;
        width: 100%;
    }
</style>

<script>
(function() {
    'use strict';
    
    // User permissions from server
    const userPermissions = @json($userPermissions);
    const isSuperAdmin = @json($isSuperAdmin);
    
    // Permission check function
    function hasPermission(permission) {
        // Super admin always has all permissions
        if (isSuperAdmin) {
            return true;
        }
        return userPermissions.includes(permission);
    }
    
    // Route to permission mapping
    const routePermissionMap = {
        'admin.dashboard': 'view-dashboard',
        'admin.users.index': 'view-users',
        'admin.users.create': 'create-users',
        'admin.users.edit': 'edit-users',
        'admin.users.show': 'view-users',
        'admin.companies.index': 'view-companies',
        'admin.companies.create': 'create-companies',
        'admin.companies.edit': 'edit-companies',
        'admin.companies.show': 'view-companies',
        'admin.vacancies.index': 'view-vacancies',
        'admin.vacancies.create': 'create-vacancies',
        'admin.vacancies.edit': 'edit-vacancies',
        'admin.vacancies.show': 'view-vacancies',
        'admin.matches.index': 'view-matches',
        'admin.matches.show': 'view-matches',
        'admin.interviews.index': 'view-interviews',
        'admin.interviews.create': 'create-interviews',
        'admin.interviews.edit': 'edit-interviews',
        'admin.notifications.index': 'view-notifications',
        'admin.notifications.create': 'create-notifications',
        'admin.roles.index': 'view-roles',
        'admin.roles.create': 'create-roles',
        'admin.roles.edit': 'edit-roles',
        'admin.roles.show': 'view-roles',
        'admin.permissions.index': 'view-permissions',
        'admin.permissions.create': 'create-permissions',
        'admin.permissions.edit': 'edit-permissions',
        'admin.permissions.show': 'view-permissions',
        'admin.categories.index': 'view-categories',
        'admin.categories.create': 'create-categories',
        'admin.categories.edit': 'edit-categories',
        'admin.agenda.index': 'view-agenda',
        'admin.email-templates.index': 'view-email-templates',
        'admin.email-templates.create': 'create-email-templates',
        'admin.email-templates.edit': 'edit-email-templates',
        'admin.email-templates.show': 'view-email-templates',
        'admin.payments.index': 'view-payments',
        'admin.payments.openstaand': 'view-payments',
        'admin.payments.voldaan': 'view-payments',
        'admin.invoices.index': 'view-invoices',
        'admin.invoices.create': 'create-invoices',
        'admin.invoices.edit': 'edit-invoices',
        'admin.invoices.show': 'view-invoices',
        'admin.invoices.settings': 'view-invoices',
        'admin.payment-providers.index': 'view-payments',
        'admin.payment-providers.create': 'view-payments',
        'admin.payment-providers.edit': 'view-payments',
        'admin.payment-providers.show': 'view-payments',
        'admin.settings.index': 'view-settings',
    };
    
    // Extract route name from URL
    function getRouteFromUrl(url) {
        if (!url || url === '#' || url.startsWith('javascript:')) return null;
        
        try {
            // Handle full URLs
            if (url.startsWith('http://') || url.startsWith('https://')) {
                const urlObj = new URL(url);
                url = urlObj.pathname;
            }
            
            const match = url.match(/\/admin\/([^\/\?]+)/);
            if (match) {
                let section = match[1];
                
                // Handle special routes
                if (section === 'payments' && url.includes('/openstaand')) {
                    return 'admin.payments.openstaand';
                }
                if (section === 'payments' && url.includes('/voldaan')) {
                    return 'admin.payments.voldaan';
                }
                if (section === 'invoices' && url.includes('/settings')) {
                    return 'admin.invoices.settings';
                }
                
                // Try common patterns
                if (url.includes('/create')) {
                    return 'admin.' + section + '.create';
                } else if (url.includes('/edit') || url.match(/\/\d+\/edit/)) {
                    return 'admin.' + section + '.edit';
                } else if (url.match(/\/\d+$/) && !url.includes('/edit')) {
                    return 'admin.' + section + '.show';
                } else {
                    return 'admin.' + section + '.index';
                }
            }
        } catch(e) {
            console.warn('Error parsing URL:', url, e);
        }
        return null;
    }
    
    // Add indicator to element
    function addIndicator(element, hasAccess, type = 'badge') {
        if (type === 'badge') {
            const badge = document.createElement('span');
            badge.className = 'kt-debug-badge ' + (hasAccess ? 'has-access' : 'no-access');
            badge.title = hasAccess ? '✓ Toegang' : '✗ Geen toegang';
            element.classList.add('kt-debug-indicator');
            element.appendChild(badge);
        } else if (type === 'overlay') {
            const overlay = document.createElement('div');
            overlay.className = 'kt-debug-card-overlay ' + (hasAccess ? 'has-access' : 'no-access');
            overlay.title = hasAccess ? '✓ Toegang' : '✗ Geen toegang';
            element.classList.add('kt-debug-indicator');
            element.insertBefore(overlay, element.firstChild);
        }
    }
    
    // Process all elements when DOM is ready
    function processElements() {
        // Process sidebar menu links
        document.querySelectorAll('.kt-menu-link[href]').forEach(function(link) {
            if (link.classList.contains('kt-debug-processed')) return;
            
            const href = link.getAttribute('href');
            const routeName = getRouteFromUrl(href);
            
            if (routeName && routePermissionMap[routeName]) {
                const permission = routePermissionMap[routeName];
                const hasAccess = hasPermission(permission);
                addIndicator(link, hasAccess, 'badge');
                link.classList.add('kt-debug-processed');
            }
        });
        
        // Process buttons with links
        document.querySelectorAll('a.kt-btn[href], button.kt-btn[href]').forEach(function(btn) {
            if (btn.classList.contains('kt-debug-processed')) return;
            
            const href = btn.getAttribute('href');
            const routeName = getRouteFromUrl(href);
            
            if (routeName && routePermissionMap[routeName]) {
                const permission = routePermissionMap[routeName];
                const hasAccess = hasPermission(permission);
                addIndicator(btn, hasAccess, 'badge');
                btn.classList.add('kt-debug-processed');
            }
        });
        
        // Process cards based on data attributes or content
        document.querySelectorAll('.kt-card').forEach(function(card) {
            if (card.classList.contains('kt-debug-processed')) return;
            
            // Check for data-permission attribute
            const dataPermission = card.getAttribute('data-debug-permission');
            if (dataPermission) {
                const hasAccess = hasPermission(dataPermission);
                addIndicator(card, hasAccess, 'overlay');
                card.classList.add('kt-debug-processed');
                return;
            }
            
            // Try to infer from card header text
            const header = card.querySelector('.kt-card-header, .kt-card-title');
            if (header) {
                const title = header.textContent.toLowerCase();
                let permission = null;
                
                if (title.includes('gebruiker') || title.includes('user')) {
                    permission = 'view-users';
                } else if (title.includes('bedrijf') || title.includes('company')) {
                    permission = 'view-companies';
                } else if (title.includes('vacature') || title.includes('vacancy')) {
                    permission = 'view-vacancies';
                } else if (title.includes('match')) {
                    permission = 'view-matches';
                } else if (title.includes('interview')) {
                    permission = 'view-interviews';
                } else if (title.includes('notificatie') || title.includes('notification')) {
                    permission = 'view-notifications';
                } else if (title.includes('rol') || title.includes('role')) {
                    permission = 'view-roles';
                } else if (title.includes('recht') || title.includes('permission')) {
                    permission = 'view-permissions';
                } else if (title.includes('categorie') || title.includes('category')) {
                    permission = 'view-categories';
                }
                
                if (permission) {
                    const hasAccess = hasPermission(permission);
                    addIndicator(card, hasAccess, 'overlay');
                    card.classList.add('kt-debug-processed');
                }
            }
        });
        
        // Process table rows with action buttons
        document.querySelectorAll('table tbody tr').forEach(function(row) {
            if (row.classList.contains('kt-debug-processed')) return;
            
            const actionLinks = row.querySelectorAll('a[href*="/admin/"]');
            let hasAnyAccess = false;
            let hasAllAccess = true;
            
            actionLinks.forEach(function(link) {
                const routeName = getRouteFromUrl(link.getAttribute('href'));
                if (routeName && routePermissionMap[routeName]) {
                    const permission = routePermissionMap[routeName];
                    const hasAccess = hasPermission(permission);
                    if (hasAccess) hasAnyAccess = true;
                    else hasAllAccess = false;
                }
            });
            
            if (hasAnyAccess || !hasAllAccess) {
                row.classList.add('kt-debug-indicator', 'kt-debug-processed');
                const indicator = document.createElement('div');
                indicator.className = 'kt-debug-row-indicator ' + (hasAllAccess ? 'has-access' : 'no-access');
                indicator.title = hasAllAccess ? '✓ Volledige toegang' : '⚠ Beperkte toegang';
                row.appendChild(indicator);
            }
        });
        
        // Process input fields and dropdowns
        // Select all inputs, selects, and textareas (with or without kt-input class)
        document.querySelectorAll('input, select, textarea').forEach(function(input) {
            if (input.classList.contains('kt-debug-processed')) return;
            
            // Skip hidden inputs and submit buttons
            if (input.type === 'hidden' || input.type === 'submit' || input.type === 'button') return;
            
            // For kt-select elements, check if they've been transformed
            // kt-select creates a wrapper, so we need to handle both the original select and the wrapper
            if (input.tagName === 'SELECT' && input.hasAttribute('data-kt-select')) {
                // Check if kt-select has created a wrapper
                const ktWrapper = input.nextElementSibling;
                if (ktWrapper && ktWrapper.hasAttribute('data-kt-select-wrapper')) {
                    // The select might be hidden, but we still want to show indicator on the wrapper
                    // Add indicator to the wrapper instead
                    if (!ktWrapper.classList.contains('kt-debug-processed')) {
                        const wrapperPermission = getPermissionForElement(input);
                        if (wrapperPermission) {
                            const hasAccess = hasPermission(wrapperPermission);
                            addInputIndicatorToWrapper(ktWrapper, hasAccess);
                            ktWrapper.classList.add('kt-debug-processed');
                        }
                        input.classList.add('kt-debug-processed');
                        return;
                    }
                }
            }
            
            // Get permission for this element
            const permission = getPermissionForElement(input);
            
            if (permission) {
                const hasAccess = hasPermission(permission);
                addInputIndicator(input, hasAccess);
                input.classList.add('kt-debug-processed');
            }
        });
    }
    
    // Get permission for an element
    function getPermissionForElement(input) {
        // Check for data-debug-permission attribute
        const dataPermission = input.getAttribute('data-debug-permission') || 
                              input.closest('.flex.flex-col')?.querySelector('label')?.getAttribute('data-debug-permission');
        
        if (dataPermission) {
            return dataPermission;
        }
        
        // Try to infer from field name/id
        const name = input.getAttribute('name') || input.getAttribute('id') || '';
        const lowerName = name.toLowerCase();
        
        let permission = null;
        
        // User related fields
        if (lowerName.includes('user') || lowerName.includes('gebruiker')) {
            permission = 'view-users';
        }
        // Company related fields
        else if (lowerName.includes('company') || lowerName.includes('bedrijf')) {
            permission = 'view-companies';
        }
        // Vacancy related fields
        else if (lowerName.includes('vacancy') || lowerName.includes('vacature')) {
            permission = 'view-vacancies';
        }
        // Match related fields
        else if (lowerName.includes('match')) {
            permission = 'view-matches';
        }
        // Interview related fields
        else if (lowerName.includes('interview')) {
            permission = 'view-interviews';
        }
        // Category related fields
        else if (lowerName.includes('category') || lowerName.includes('categorie')) {
            permission = 'view-categories';
        }
        // Role related fields
        else if (lowerName.includes('role') || lowerName.includes('rol')) {
            permission = 'view-roles';
        }
        // Permission related fields
        else if (lowerName.includes('permission') || lowerName.includes('recht')) {
            permission = 'view-permissions';
        }
        
        // Also check parent form or card context
        if (!permission) {
            const form = input.closest('form');
            const card = input.closest('.kt-card');
            
            if (form) {
                const formAction = form.getAttribute('action');
                if (formAction) {
                    const routeName = getRouteFromUrl(formAction);
                    if (routeName && routePermissionMap[routeName]) {
                        permission = routePermissionMap[routeName];
                    }
                }
            }
            
            if (!permission && card) {
                const cardHeader = card.querySelector('.kt-card-header, .kt-card-title');
                if (cardHeader) {
                    const title = cardHeader.textContent.toLowerCase();
                    if (title.includes('gebruiker') || title.includes('user')) {
                        permission = 'view-users';
                    } else if (title.includes('bedrijf') || title.includes('company')) {
                        permission = 'view-companies';
                    } else if (title.includes('vacature') || title.includes('vacancy')) {
                        permission = 'view-vacancies';
                    } else if (title.includes('match')) {
                        permission = 'view-matches';
                    } else if (title.includes('interview')) {
                        permission = 'view-interviews';
                    } else if (title.includes('categorie') || title.includes('category')) {
                        permission = 'view-categories';
                    } else if (title.includes('rol') || title.includes('role')) {
                        permission = 'view-roles';
                    } else if (title.includes('recht') || title.includes('permission')) {
                        permission = 'view-permissions';
                    }
                }
            }
        }
        
        return permission;
    }
    
    // Add indicator to kt-select wrapper
    function addInputIndicatorToWrapper(wrapper, hasAccess) {
        // Remove existing indicator if any
        const existing = wrapper.querySelector('.kt-debug-input-indicator');
        if (existing) {
            existing.remove();
        }
        
        // Make wrapper position relative
        if (getComputedStyle(wrapper).position === 'static') {
            wrapper.style.position = 'relative';
        }
        
        const indicator = document.createElement('span');
        indicator.className = 'kt-debug-input-indicator ' + (hasAccess ? 'has-access' : 'no-access');
        indicator.title = hasAccess ? '✓ Toegang' : '✗ Geen toegang';
        wrapper.classList.add('kt-debug-indicator');
        
        wrapper.appendChild(indicator);
    }
    
    // Add indicator to input field
    function addInputIndicator(input, hasAccess) {
        // Remove existing indicator if any
        const existingIndicator = input.querySelector('.kt-debug-input-indicator') || 
                                  input.parentElement?.querySelector('.kt-debug-input-indicator');
        if (existingIndicator) {
            existingIndicator.remove();
        }
        
        const parent = input.parentElement;
        const isFlexParent = getComputedStyle(parent).display === 'flex' || 
                            getComputedStyle(parent).display === 'inline-flex' ||
                            parent.classList.contains('flex');
        
        let container = input;
        
        // For elements in flex containers, add indicator directly to the input/select element
        // and make sure it has position relative
        if (isFlexParent) {
            // Make the input itself position relative so indicator can be positioned
            if (getComputedStyle(input).position === 'static') {
                input.style.position = 'relative';
            }
            container = input;
        }
        // Check if parent already has position relative and is not flex
        else if (parent.classList.contains('relative') || getComputedStyle(parent).position === 'relative') {
            container = parent;
        }
        // Only wrap if really necessary
        else {
            const needsWrapper = !parent.classList.contains('kt-debug-input-wrapper') && 
                               getComputedStyle(parent).position !== 'relative';
            
            if (needsWrapper) {
                const wrapper = document.createElement('div');
                wrapper.className = 'kt-debug-input-wrapper';
                wrapper.style.position = 'relative';
                wrapper.style.display = 'inline-block';
                wrapper.style.width = '100%';
                input.parentElement.insertBefore(wrapper, input);
                wrapper.appendChild(input);
                container = wrapper;
            } else {
                // Make input relative if not wrapped
                if (getComputedStyle(input).position === 'static') {
                    input.style.position = 'relative';
                }
                container = input;
            }
        }
        
        const indicator = document.createElement('span');
        indicator.className = 'kt-debug-input-indicator ' + (hasAccess ? 'has-access' : 'no-access');
        indicator.title = hasAccess ? '✓ Toegang' : '✗ Geen toegang';
        input.classList.add('kt-debug-indicator');
        
        container.appendChild(indicator);
    }
    
    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            // Wait a bit for kt-select to initialize
            setTimeout(processElements, 100);
        });
    } else {
        // Wait a bit for kt-select to initialize
        setTimeout(processElements, 100);
    }
    
    // Re-run after dynamic content loads (for Livewire, AJAX, etc.)
    const observer = new MutationObserver(function(mutations) {
        // Debounce to avoid too many calls
        clearTimeout(window.debugIndicatorTimeout);
        window.debugIndicatorTimeout = setTimeout(processElements, 200);
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // Also process after kt-select initialization
    if (typeof window.KTSelect !== 'undefined') {
        const originalInit = window.KTSelect.prototype.init;
        if (originalInit) {
            window.KTSelect.prototype.init = function() {
                const result = originalInit.apply(this, arguments);
                setTimeout(processElements, 50);
                return result;
            };
        }
    }
})();
</script>
@endif

