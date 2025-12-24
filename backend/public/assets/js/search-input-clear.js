/**
 * Search Input Clear Button Module
 * 
 * Voegt automatisch een clear button (x) toe aan zoekvelden
 * De button verschijnt wanneer er tekst is ingevoerd en verdwijnt wanneer het veld leeg is
 */
(function() {
    'use strict';

    /**
     * Initialiseer clear buttons voor alle zoekvelden
     */
    function initSearchInputClear() {
        // Vind alle input velden binnen labels met kt-input class
        const searchInputs = document.querySelectorAll('label.kt-input input[type="text"], label.kt-input input[type="search"], .kt-input input[type="text"], .kt-input input[type="search"]');
        
        searchInputs.forEach(input => {
            // Skip als al geïnitialiseerd
            if (input.dataset.clearInitialized === 'true') {
                return;
            }
            
            input.dataset.clearInitialized = 'true';
            
            // Vind de parent label of div met kt-input class
            const label = input.closest('label.kt-input') || input.closest('.kt-input');
            if (!label) return;
            
            // Zorg dat label relative positioning heeft
            if (!label.style.position || label.style.position !== 'relative') {
                label.style.position = 'relative';
            }
            
            // Check of er al een clear button bestaat (voorkom duplicates)
            if (label.querySelector('.kt-input-clear')) {
                return;
            }
            
            // Maak clear button
            const clearButton = document.createElement('button');
            clearButton.type = 'button';
            clearButton.className = 'kt-input-clear';
            clearButton.setAttribute('aria-label', 'Zoekopdracht wissen');
            clearButton.innerHTML = '<i class="ki-filled ki-cross"></i>';
            clearButton.style.cssText = `
                position: absolute !important;
                right: 0.75rem !important;
                top: 50% !important;
                transform: translateY(-50%) !important;
                background: transparent !important;
                border: none !important;
                padding: 0.25rem !important;
                cursor: pointer !important;
                display: none !important;
                align-items: center !important;
                justify-content: center !important;
                color: var(--muted-foreground) !important;
                opacity: 0.7 !important;
                z-index: 10 !important;
                width: 1.5rem !important;
                height: 1.5rem !important;
                transition: opacity 0.2s ease !important;
            `;
            
            // Hover effect
            clearButton.addEventListener('mouseenter', function() {
                this.style.opacity = '1';
            });
            clearButton.addEventListener('mouseleave', function() {
                this.style.opacity = '0.7';
            });
            
            // Clear functionaliteit
            clearButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Clear input
                input.value = '';
                
                // Trigger input event voor datatables en andere listeners
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
                input.dispatchEvent(new Event('keyup', { bubbles: true }));
                
                // Focus terug naar input
                setTimeout(() => {
                    input.focus();
                }, 10);
                
                // Verberg button
                toggleClearButton(input, clearButton);
            });
            
            // Voeg button toe aan label
            label.appendChild(clearButton);
            
            // Toon/verberg button op basis van input waarde
            function toggleClearButton(input, button) {
                const hasValue = input.value && input.value.trim() !== '';
                
                if (hasValue) {
                    button.style.display = 'flex';
                    // Voeg padding toe aan input om ruimte te maken voor button
                    if (!input.style.paddingRight || !input.style.paddingRight.includes('2.5rem')) {
                        input.style.paddingRight = '2.5rem';
                    }
                } else {
                    button.style.display = 'none';
                    // Verwijder padding als button verborgen is
                    if (input.style.paddingRight) {
                        input.style.paddingRight = '';
                    }
                }
            }
            
            // Event listeners voor input changes
            input.addEventListener('input', function() {
                toggleClearButton(input, clearButton);
            });
            
            input.addEventListener('keyup', function() {
                toggleClearButton(input, clearButton);
            });
            
            // Initial check (voor pre-filled values)
            toggleClearButton(input, clearButton);
        });
    }

    // Initialiseer bij DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSearchInputClear);
    } else {
        initSearchInputClear();
    }

    // Herinitialiseer bij dynamische content changes (voor datatables, etc.)
    const observer = new MutationObserver(function(mutations) {
        let shouldReinit = false;
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length > 0) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        // Check if new search inputs were added
                        if (node.querySelector && (
                            node.querySelector('label.kt-input input[type="text"]') ||
                            node.querySelector('label.kt-input input[type="search"]') ||
                            node.querySelector('.kt-input input[type="text"]') ||
                            node.querySelector('.kt-input input[type="search"]')
                        )) {
                            shouldReinit = true;
                        }
                        // Check if the node itself is a search input
                        if (node.tagName === 'INPUT' && 
                            (node.type === 'text' || node.type === 'search') && 
                            (node.closest('label.kt-input') || node.closest('.kt-input'))) {
                            shouldReinit = true;
                        }
                    }
                });
            }
        });
        
        if (shouldReinit) {
            // Debounce reinit
            setTimeout(initSearchInputClear, 100);
        }
    });

    // Observe document body for changes
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

    // Export voor gebruik in andere scripts
    window.initSearchInputClear = initSearchInputClear;
})();





