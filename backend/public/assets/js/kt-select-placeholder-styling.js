/**
 * KT-Select Placeholder Styling
 * 
 * Maakt de display text grijs wanneer een placeholder/lege waarde is geselecteerd
 * Dit zorgt voor consistente styling tussen alle dropdowns
 */
(function() {
    'use strict';

    /**
     * Update de styling van kt-select display wanneer een lege waarde is geselecteerd
     */
    function updateSelectDisplayStyling() {
        // Vind alle kt-select elementen
        const selects = document.querySelectorAll('select.kt-select[data-kt-select="true"]');
        
        selects.forEach(select => {
            // Vind de display element
            const display = select.parentElement?.querySelector('.kt-select-display');
            if (!display) return;
            
            // Check of de geselecteerde waarde leeg is
            const selectedValue = select.value;
            const selectedOption = select.options[select.selectedIndex];
            const isEmpty = !selectedValue || selectedValue === '';
            
            // Check of dit een placeholder/lege optie is
            // Dit zijn opties zoals "Geen sortering", "Alle bedrijven", "Alle statussen", etc.
            const isEmptyOption = isEmpty || 
                (selectedOption && (
                    selectedOption.textContent.trim() === '' ||
                    selectedOption.textContent.trim() === 'Geen sortering' ||
                    selectedOption.textContent.trim() === 'Alle bedrijven' ||
                    selectedOption.textContent.trim() === 'Alle statussen' ||
                    selectedOption.textContent.trim() === 'Alle rollen' ||
                    selectedOption.textContent.trim() === 'Alle types' ||
                    selectedOption.textContent.trim() === 'Alle industrieën'
                ));
            
            // Update styling - maak grijs wanneer lege waarde of placeholder optie
            if (isEmptyOption) {
                display.style.color = 'var(--muted-foreground)';
                display.classList.add('text-muted-foreground');
            } else {
                display.style.color = '';
                display.classList.remove('text-muted-foreground');
            }
        });
    }

    /**
     * Initialiseer styling voor alle selects
     */
    function initSelectStyling() {
        updateSelectDisplayStyling();
        
        // Luister naar changes op alle selects
        document.querySelectorAll('select.kt-select[data-kt-select="true"]').forEach(select => {
            select.addEventListener('change', function() {
                // Wacht even zodat kt-select de display heeft geüpdatet
                setTimeout(updateSelectDisplayStyling, 10);
            });
        });
    }

    // Initialiseer bij DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSelectStyling);
    } else {
        initSelectStyling();
    }

    // Observeer voor dynamisch toegevoegde selects
    const observer = new MutationObserver(function(mutations) {
        let shouldUpdate = false;
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length > 0) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        if (node.tagName === 'SELECT' && node.classList.contains('kt-select') ||
                            node.querySelector && node.querySelector('select.kt-select[data-kt-select="true"]')) {
                            shouldUpdate = true;
                        }
                    }
                });
            }
        });
        
        if (shouldUpdate) {
            setTimeout(function() {
                initSelectStyling();
            }, 100);
        }
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

    // Export voor gebruik in andere scripts
    window.updateSelectDisplayStyling = updateSelectDisplayStyling;
})();





