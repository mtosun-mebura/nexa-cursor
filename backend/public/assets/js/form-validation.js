/**
 * Recursieve Form Validatie Module
 * 
 * Deze module biedt:
 * - Recursieve validatie voor geneste form structuren
 * - Live validatie op input events
 * - Uniforme styling (rood/groen borders en feedback)
 * - Security checks (XSS preventie, pattern matching)
 * - Automatische detectie van required fields
 */

(function() {
    'use strict';

    /**
     * Validatie configuratie per veldtype
     */
    const validationRules = {
        email: {
            pattern: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
            message: 'Voer een geldig e-mailadres in.',
            successMessage: 'E-mailadres is geldig',
            minLength: 5,
        },
        phone: {
            pattern: /^(\+31|0)[1-9][0-9]{8}$/,
            message: 'Telefoonnummer moet een geldig Nederlands nummer zijn (bijv. 0612345678 of +31612345678).',
            successMessage: 'Telefoonnummer is geldig',
            format: function(value) {
                // Auto-format: add +31 if starts with 0 and has 10 digits
                let formatted = value.replace(/\s/g, '');
                if (formatted.startsWith('0') && formatted.length === 10) {
                    formatted = '+31' + formatted.substring(1);
                }
                return formatted;
            }
        },
        postal_code: {
            pattern: /^[1-9][0-9]{3}\s?[A-Z]{2}$/i,
            message: 'Postcode moet een geldige Nederlandse postcode zijn (bijv. 1234AB).',
            successMessage: 'Postcode is geldig',
            format: function(value) {
                // Auto-format: add space after 4 digits
                let formatted = value.replace(/\s/g, '').toUpperCase();
                if (formatted.length > 4) {
                    formatted = formatted.substring(0, 4) + ' ' + formatted.substring(4, 7);
                }
                return formatted;
            }
        },
        kvk_number: {
            pattern: /^[0-9]{8}$/,
            message: 'KVK nummer moet 8 cijfers bevatten.',
            successMessage: 'KVK nummer is geldig',
            format: function(value) {
                return value.replace(/\D/g, ''); // Remove non-digits
            }
        },
        password: {
            pattern: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/,
            message: 'Wachtwoord moet minimaal 1 kleine letter, 1 hoofdletter en 1 cijfer bevatten.',
            successMessage: 'Wachtwoord is geldig',
            minLength: 8,
        },
        url: {
            pattern: /^https?:\/\/.+/,
            message: 'URL moet beginnen met http:// of https://',
            successMessage: 'URL is geldig',
        },
        text: {
            minLength: 2,
        }
    };

    /**
     * Hoofdklasse voor form validatie
     */
    class FormValidator {
        constructor(formElement, options = {}) {
            this.form = formElement;
            this.options = {
                validateOnInput: true,
                validateOnBlur: true,
                showSuccessMessages: true,
                ...options
            };
            this.validatedFields = new Set();
            // Store validator instance on form for easy access
            this.form._formValidator = this;
            this.init();
        }

        /**
         * Initialiseer de validator
         */
        init() {
            if (!this.form) {
                console.warn('FormValidator: Geen form element gevonden');
                return;
            }

            // Vind alle input velden recursief
            const inputs = this.findInputsRecursive(this.form);
            
            // Attach event listeners
            inputs.forEach(input => {
                this.attachValidation(input);
                
                // If input already has a value, trigger validation to show icon
                const type = (input.type || '').toLowerCase();
                const isCheckboxOrRadio = input.tagName === 'INPUT' && (type === 'checkbox' || type === 'radio');
                if (!isCheckboxOrRadio) {
                    const value = input.value.trim();
                    if (value) {
                        // Mark as interacted and validate
                        input.dataset.userInteracted = 'true';
                        const feedbackElement = input.parentElement?.querySelector('.field-feedback') ||
                                              input.closest('.relative')?.parentElement?.querySelector('.field-feedback') ||
                                              input.closest('td')?.querySelector('.field-feedback');
                        if (feedbackElement || input.hasAttribute('required')) {
                            // Small delay to ensure DOM is ready
                            setTimeout(() => {
                                this.validateField(input, feedbackElement, false);
                            }, 100);
                        }
                    }
                }
            });

            // Handle form submission
            this.form.addEventListener('submit', (e) => {
                if (!this.validateForm()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });

            // Handle dynamically added fields
            this.observeDynamicFields();
        }

        /**
         * Recursieve functie om alle input velden te vinden
         * Loopt door geneste structuren (zoals locations arrays)
         */
        findInputsRecursive(container) {
            const inputs = [];
            const walker = (element) => {
                if (!element) return;

                // Check if element is an input, textarea, or select
                if (element.tagName && ['INPUT', 'TEXTAREA', 'SELECT'].includes(element.tagName)) {
                    // Skip hidden inputs en submit buttons
                    if (element.type === 'hidden' || element.type === 'submit' || element.type === 'button') {
                        return;
                    }
                    inputs.push(element);
                }

                // Recursief door children
                if (element.children) {
                    Array.from(element.children).forEach(child => walker(child));
                }
            };

            walker(container);
            return inputs;
        }

        /**
         * Attach validatie aan een input veld
         */
        attachValidation(input) {
            if (this.validatedFields.has(input)) {
                return; // Al geïnitialiseerd
            }

            this.validatedFields.add(input);

            // Create feedback element
            const feedbackElement = this.createFeedbackElement(input);

            // Attach event listeners
            const isSelect = input.tagName === 'SELECT';
            const isCheckboxOrRadio = input.tagName === 'INPUT' && ['checkbox', 'radio'].includes((input.type || '').toLowerCase());
            
            if (isSelect) {
                // For select fields, validate on change
                input.addEventListener('change', () => {
                    this.validateField(input, feedbackElement);
                });
            } else if (isCheckboxOrRadio) {
                // For checkbox/radio, validate on change
                input.addEventListener('change', () => {
                    this.validateField(input, feedbackElement);
                    // Also validate checkbox group if this is part of a required group
                    const groupName = input.getAttribute('data-checkbox-group') || input.name;
                    if (groupName && groupName.includes('[]')) {
                        this.validateCheckboxGroup(groupName);
                    }
                });
            } else {
                // For input fields, validate on input/keyup
                if (this.options.validateOnInput) {
                    input.addEventListener('input', () => {
                        this.validateField(input, feedbackElement);
                    });
                    input.addEventListener('keyup', () => {
                        this.validateField(input, feedbackElement);
                    });
                }
            }

            if (this.options.validateOnBlur) {
                input.addEventListener('blur', () => {
                    this.validateField(input, feedbackElement);
                });
            }

            // Mark field as user-interacted (voor tracking of gebruiker heeft getypt)
            input.dataset.userInteracted = 'false';
            
            // Track user interaction
            const markAsInteracted = () => {
                input.dataset.userInteracted = 'true';
            };
            
            input.addEventListener('input', markAsInteracted);
            input.addEventListener('keyup', markAsInteracted);
            if (isSelect) {
                input.addEventListener('change', markAsInteracted);
            }
            if (isCheckboxOrRadio) {
                input.addEventListener('change', markAsInteracted);
            }

            // Geen initial validation - alleen na user interaction of form submit
        }

        /**
         * Valideer een individueel veld
         */
        validateField(input, feedbackElement, forceShow = false) {
            const isSelect = input.tagName === 'SELECT';
            const type = (input.type || '').toLowerCase();
            const isCheckboxOrRadio = input.tagName === 'INPUT' && (type === 'checkbox' || type === 'radio');
            const value = isSelect
                ? input.value
                : (isCheckboxOrRadio ? (input.checked ? (input.value || '1') : '') : input.value.trim());
            const fieldName = input.name;
            const fieldType = this.detectFieldType(input);
            const isRequired = input.hasAttribute('required');
            const hasPattern = input.hasAttribute('pattern');
            
            // Check if user has interacted with this field
            // Also consider it interacted if the field has a value (user might have typed before validation initialized)
            const hasValue = value && value.trim() !== '';
            const userInteracted = input.dataset.userInteracted === 'true' || forceShow || hasValue;

            // Find feedback element if not provided
            if (!feedbackElement) {
                feedbackElement = input.parentElement?.querySelector('.field-feedback') ||
                                input.closest('.relative')?.parentElement?.querySelector('.field-feedback') ||
                                input.closest('td')?.querySelector('.field-feedback') ||
                                this.createFeedbackElement(input);
            }

            // Clear previous validation state
            this.clearValidationState(input, feedbackElement);
            
            // Als gebruiker nog niet heeft geïnteracteerd en niet geforceerd, toon geen feedback
            // Maar toon wel validatie als er al een waarde in het veld staat
            if (!userInteracted && !forceShow && !hasValue) {
                return true; // Return true om geen errors te tonen
            }

            // Special handling for SELECT dropdowns
            if (isSelect) {
                // For select fields, only validate if required
                if (isRequired && (!value || value === '')) {
                    this.setInvalid(input, feedbackElement, 'Selecteer een optie.', forceShow);
                    return false;
                }
                // Optional select fields are always valid (even if empty)
                if (!isRequired || (value && value !== '')) {
                    // Clear any error styling for valid selects
                    this.clearValidationState(input, feedbackElement);
                    return true;
                }
            }

            // Special handling for checkbox / radio
            if (isCheckboxOrRadio) {
                // For required checkbox/radio: must be checked
                if (isRequired && !input.checked) {
                    this.setInvalid(input, feedbackElement, 'Dit veld is verplicht.', forceShow);
                    return false;
                }

                // Optional checkbox/radio is always valid
                this.setValid(input, feedbackElement, fieldType, forceShow);
                return true;
            }

            // For non-select fields, check if field is empty
            if (!value) {
                if (isRequired) {
                    this.setInvalid(input, feedbackElement, 'Dit veld is verplicht.', forceShow);
                    return false;
                }
                // Optional field is valid when empty
                return true;
            }

            // Check minimum length (not applicable for selects)
            if (!isSelect) {
                const minLength = this.getMinLength(input, fieldType);
                if (minLength && value.length < minLength) {
                    this.setInvalid(input, feedbackElement, `Dit veld moet minimaal ${minLength} karakters bevatten.`, forceShow);
                    return false;
                }
            }

            // Check pattern (from attribute or from validation rules)
            // Not applicable for selects
            if (!isSelect) {
                let pattern = null;
                if (hasPattern) {
                    pattern = new RegExp(input.getAttribute('pattern'));
                } else if (validationRules[fieldType] && validationRules[fieldType].pattern) {
                    pattern = validationRules[fieldType].pattern;
                }

                if (pattern && !pattern.test(value)) {
                    const message = validationRules[fieldType]?.message || 'Deze invoer is ongeldig.';
                    this.setInvalid(input, feedbackElement, message, forceShow);
                    return false;
                }

                // Apply formatting if available
                if (validationRules[fieldType]?.format) {
                    const formatted = validationRules[fieldType].format(value);
                    if (formatted !== value) {
                        input.value = formatted;
                    }
                }
            }

            // Field is valid
            this.setValid(input, feedbackElement, fieldType, forceShow);
            return true;
        }

        /**
         * Detecteer het type van een input veld
         */
        detectFieldType(input) {
            const type = input.type.toLowerCase();
            const name = input.name.toLowerCase();

            if (type === 'email' || name.includes('email')) return 'email';
            if (type === 'tel' || name.includes('phone') || name.includes('telefoon')) return 'phone';
            if (name.includes('postal_code') || name.includes('postcode')) return 'postal_code';
            if (name.includes('kvk_number') || name.includes('kvk')) return 'kvk_number';
            if (type === 'password' || name.includes('password') || name.includes('wachtwoord')) return 'password';
            if (type === 'url' || name.includes('website') || name.includes('url')) return 'url';
            
            return 'text';
        }

        /**
         * Get minimum lengte voor een veld
         */
        getMinLength(input, fieldType) {
            // Check HTML5 minlength attribute
            if (input.hasAttribute('minlength')) {
                return parseInt(input.getAttribute('minlength'));
            }

            // Check validation rules
            if (validationRules[fieldType]?.minLength) {
                return validationRules[fieldType].minLength;
            }

            return null;
        }

        /**
         * Set veld als ongeldig
         */
        setInvalid(input, feedbackElement, message, forceShow = false) {
            const type = (input.type || '').toLowerCase();
            const isCheckboxOrRadio = input.tagName === 'INPUT' && (type === 'checkbox' || type === 'radio');
            const value = isCheckboxOrRadio ? (input.checked ? (input.value || '1') : '') : input.value.trim();
            const hasValue = value && value.trim() !== '';
            const userInteracted = input.dataset.userInteracted === 'true' || forceShow || hasValue;
            
            // Remove all validation borders
            input.classList.remove('border-green-500', 'border-green-600', 'border-red-500', 'border-destructive');
            
            // Get or create validation icon wrapper
            let iconWrapper = input.parentElement?.querySelector('.validation-icon-wrapper');
            if (!iconWrapper) {
                iconWrapper = input.closest('.relative')?.querySelector('.validation-icon-wrapper');
            }
            if (!iconWrapper) {
                iconWrapper = this.createValidationIcon(input);
            }
            
            // Alleen feedback tonen als gebruiker heeft geïnteracteerd of geforceerd
            if (userInteracted) {
                // Show red cross icon (same styling as green checkmark)
                if (iconWrapper) {
                    iconWrapper.innerHTML = '<i class="ki-filled ki-cross-circle text-red-500" style="font-size: 1.25rem; line-height: 1;"></i>';
                    iconWrapper.classList.remove('hidden');
                    iconWrapper.style.display = 'flex';
                    iconWrapper.style.position = 'absolute';
                    iconWrapper.style.right = '0.5rem';
                    iconWrapper.style.top = '50%';
                    iconWrapper.style.transform = 'translateY(-50%)';
                    iconWrapper.style.width = '1.25rem';
                    iconWrapper.style.height = '1.25rem';
                    // Add padding to input to make room for icon (0.5rem right + 1.25rem icon + 0.5rem spacing = 2.25rem)
                    input.style.paddingRight = '2.25rem';
                }
                
                // Show error message below input
                if (feedbackElement) {
                    feedbackElement.className = 'text-xs text-red-600 text-destructive mt-1';
                    feedbackElement.textContent = message;
                    feedbackElement.classList.remove('hidden');
                    feedbackElement.style.display = 'block';
                }
                
                // Also hide any server-side error messages that might conflict
                const tdWrapper = input.closest('td');
                if (tdWrapper) {
                    const serverErrors = tdWrapper.querySelectorAll('.text-destructive');
                    serverErrors.forEach(errorEl => {
                        // Only hide server errors if they're not the current feedback element
                        if (errorEl !== feedbackElement && 
                            errorEl.textContent && errorEl.textContent.trim() !== '' && 
                            !errorEl.classList.contains('text-muted-foreground')) {
                            errorEl.classList.add('hidden');
                            errorEl.style.display = 'none';
                        }
                    });
                }
            } else {
                // Hide everything if user hasn't interacted
                if (iconWrapper) {
                    iconWrapper.classList.add('hidden');
                    iconWrapper.style.display = 'none';
                    // Remove padding when icon is hidden
                    input.style.paddingRight = '';
                }
                if (feedbackElement) {
                    feedbackElement.classList.add('hidden');
                }
            }
        }

        /**
         * Set veld als geldig
         */
        setValid(input, feedbackElement, fieldType, forceShow = false) {
            const isSelect = input.tagName === 'SELECT';
            const type = (input.type || '').toLowerCase();
            const isCheckboxOrRadio = input.tagName === 'INPUT' && (type === 'checkbox' || type === 'radio');
            const value = isSelect
                ? input.value
                : (isCheckboxOrRadio ? (input.checked ? (input.value || '1') : '') : input.value.trim());
            const hasValue = value && value.trim() !== '';
            const userInteracted = input.dataset.userInteracted === 'true' || forceShow || hasValue;
            
            // Remove all validation borders
            input.classList.remove('border-red-500', 'border-destructive', 'border-green-500', 'border-green-600');
            
            // Get or create validation icon wrapper
            let iconWrapper = input.parentElement?.querySelector('.validation-icon-wrapper');
            if (!iconWrapper) {
                iconWrapper = input.closest('.relative')?.querySelector('.validation-icon-wrapper');
            }
            if (!iconWrapper) {
                iconWrapper = this.createValidationIcon(input);
            }
            
            // Only show validation icon if user has interacted
            if (userInteracted && !isCheckboxOrRadio) {
                // Show green checkmark icon
                if (iconWrapper) {
                    iconWrapper.innerHTML = '<i class="ki-filled ki-check-circle text-green-500" style="font-size: 1.25rem; line-height: 1;"></i>';
                    iconWrapper.classList.remove('hidden');
                    iconWrapper.style.display = 'flex';
                    iconWrapper.style.position = 'absolute';
                    iconWrapper.style.right = '0.5rem';
                    iconWrapper.style.top = '50%';
                    iconWrapper.style.transform = 'translateY(-50%)';
                    iconWrapper.style.width = '1.25rem';
                    iconWrapper.style.height = '1.25rem';
                    // Add padding to input to make room for icon (0.5rem right + 1.25rem icon + 0.5rem spacing = 2.25rem)
                    input.style.paddingRight = '2.25rem';
                }
                
                // Hide error message if it was shown
                if (feedbackElement) {
                    feedbackElement.classList.add('hidden');
                    feedbackElement.style.display = 'none';
                    feedbackElement.textContent = '';
                }
                
                // Also hide any server-side error messages (from @error directive)
                const tdWrapper = input.closest('td');
                if (tdWrapper) {
                    const serverErrors = tdWrapper.querySelectorAll('.text-destructive');
                    serverErrors.forEach(errorEl => {
                        // Only hide if it's an error message (not the help text)
                        if (errorEl.textContent && errorEl.textContent.trim() !== '' && 
                            !errorEl.classList.contains('text-muted-foreground')) {
                            errorEl.classList.add('hidden');
                            errorEl.style.display = 'none';
                        }
                    });
                }
            } else {
                // Hide icon if user hasn't interacted or for checkboxes/radios
                if (iconWrapper) {
                    iconWrapper.classList.add('hidden');
                    // Remove padding when icon is hidden
                    input.style.paddingRight = '';
                }
                if (feedbackElement) {
                    feedbackElement.classList.add('hidden');
                }
            }
        }

        /**
         * Clear validatie state
         */
        clearValidationState(input, feedbackElement) {
            const isSelect = input.tagName === 'SELECT';
            const isRequired = input.hasAttribute('required');
            
            // Remove all validation classes
            input.classList.remove('border-red-500', 'border-green-500', 'border-destructive', 'border-green-600');
            
            // Hide validation icon
            const iconWrapper = input.parentElement?.querySelector('.validation-icon-wrapper') || 
                               input.closest('.relative')?.querySelector('.validation-icon-wrapper');
            if (iconWrapper) {
                iconWrapper.classList.add('hidden');
                iconWrapper.style.display = 'none';
                iconWrapper.innerHTML = '';
                // Remove padding when icon is hidden
                input.style.paddingRight = '';
            }
            
            // For optional selects, ensure no validation styling is applied
            if (isSelect && !isRequired) {
                // Optional selects should have no special styling when empty
                // This is handled by the default kt-input styling
            }
            
            if (feedbackElement) {
                feedbackElement.classList.add('hidden');
                feedbackElement.style.display = 'none';
                feedbackElement.textContent = '';
            }
            
            // Also hide any server-side error messages
            const tdWrapper = input.closest('td');
            if (tdWrapper) {
                const serverErrors = tdWrapper.querySelectorAll('.text-destructive');
                serverErrors.forEach(errorEl => {
                    // Only hide if it's an error message (not the help text)
                    if (errorEl.textContent && errorEl.textContent.trim() !== '' && 
                        !errorEl.classList.contains('text-muted-foreground')) {
                        errorEl.classList.add('hidden');
                        errorEl.style.display = 'none';
                    }
                });
            }
        }

        /**
         * Create feedback element voor een input
         */
        createFeedbackElement(input) {
            // Check if feedback element already exists - look in parent or td
            const existing = input.parentElement?.querySelector('.field-feedback') ||
                            input.closest('td')?.querySelector('.field-feedback') ||
                            input.closest('.relative')?.parentElement?.querySelector('.field-feedback');
            if (existing) {
                return existing;
            }

            // Create new feedback element
            const feedback = document.createElement('div');
            feedback.className = 'field-feedback text-xs mt-1 hidden';
            feedback.setAttribute('data-field', input.name || input.id);
            
            // Insert after the relative wrapper or in td (not inside the relative div with the input)
            const relativeWrapper = input.closest('.relative');
            const tdWrapper = input.closest('td');
            
            if (relativeWrapper && relativeWrapper.parentElement) {
                // Insert after the relative wrapper (so it's outside the input container)
                relativeWrapper.parentElement.insertBefore(feedback, relativeWrapper.nextSibling);
            } else if (tdWrapper) {
                // Insert in td after all other elements
                tdWrapper.appendChild(feedback);
            } else if (input.parentElement) {
                // Fallback: insert in parent
                input.parentElement.appendChild(feedback);
            }
            
            // Create validation icon wrapper if it doesn't exist
            this.createValidationIcon(input);
            
            return feedback;
        }

        /**
         * Create validation icon element voor een input
         */
        createValidationIcon(input) {
            // Check if icon wrapper already exists
            const existing = input.parentElement?.querySelector('.validation-icon-wrapper');
            if (existing) {
                return existing;
            }

            // Find the closest relative wrapper (should be the div containing the input)
            let inputWrapper = input.closest('.relative');
            
            // If no relative wrapper found, use parent element
            if (!inputWrapper) {
                inputWrapper = input.parentElement;
            }
            
            // If input is directly in a td, find or create a relative wrapper
            if (!inputWrapper || inputWrapper.tagName === 'TD') {
                // Check if there's already a relative div we can use
                const existingRelative = input.closest('.relative');
                if (existingRelative) {
                    inputWrapper = existingRelative;
                } else {
                    // Create a new wrapper div
                    const wrapper = document.createElement('div');
                    wrapper.className = 'relative';
                    wrapper.style.position = 'relative';
                    input.parentNode.insertBefore(wrapper, input);
                    wrapper.appendChild(input);
                    inputWrapper = wrapper;
                }
            }

            // Ensure input wrapper has relative positioning
            if (inputWrapper && !inputWrapper.classList.contains('relative')) {
                inputWrapper.classList.add('relative');
                inputWrapper.style.position = 'relative';
            }

            // Create icon wrapper
            const iconWrapper = document.createElement('div');
            iconWrapper.className = 'validation-icon-wrapper absolute pointer-events-none hidden';
            iconWrapper.style.position = 'absolute';
            iconWrapper.style.right = '0.5rem';
            iconWrapper.style.top = '50%';
            iconWrapper.style.transform = 'translateY(-50%)';
            iconWrapper.style.display = 'none';
            iconWrapper.style.alignItems = 'center';
            iconWrapper.style.justifyContent = 'center';
            iconWrapper.style.zIndex = '10';
            iconWrapper.style.width = '1.25rem';
            iconWrapper.style.height = '1.25rem';
            iconWrapper.setAttribute('data-field', input.name || input.id);
            
            // Insert icon wrapper in the input wrapper (same level as input)
            if (inputWrapper) {
                inputWrapper.appendChild(iconWrapper);
            }
            
            return iconWrapper;
        }

        /**
         * Valideer het hele formulier
         */
        validateForm() {
            const inputs = this.findInputsRecursive(this.form);
            let isValid = true;

            inputs.forEach(input => {
                const feedbackElement = input.parentElement?.querySelector('.field-feedback');
                // Force show bij form submit - toon alle validaties
                if (!this.validateField(input, feedbackElement, true)) {
                    isValid = false;
                }
            });

            // Validate required checkbox groups
            const requiredCheckboxGroups = this.form.querySelectorAll('[data-required-checkbox-group]');
            requiredCheckboxGroups.forEach(groupContainer => {
                const groupName = groupContainer.getAttribute('data-required-checkbox-group');
                const checkboxes = groupContainer.querySelectorAll(`input[type="checkbox"][data-checkbox-group="${groupName}"], input[type="checkbox"][name="${groupName}"]`);
                const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
                
                if (checkedCount === 0) {
                    isValid = false;
                    // Find or create feedback element for this group
                    let feedbackElement = groupContainer.querySelector('.field-feedback[data-field]');
                    if (!feedbackElement) {
                        feedbackElement = document.createElement('div');
                        feedbackElement.className = 'field-feedback text-xs text-destructive mt-1';
                        feedbackElement.setAttribute('data-field', groupName);
                        // Insert after the error alert or at the beginning of the container
                        const errorAlert = groupContainer.querySelector('.kt-alert');
                        if (errorAlert) {
                            errorAlert.parentNode.insertBefore(feedbackElement, errorAlert.nextSibling);
                        } else {
                            groupContainer.insertBefore(feedbackElement, groupContainer.firstChild);
                        }
                    }
                    feedbackElement.textContent = 'Selecteer minimaal één recht.';
                    feedbackElement.classList.remove('hidden');
                    feedbackElement.style.display = 'block';
                } else {
                    // Clear error if at least one is checked
                    const feedbackElement = groupContainer.querySelector('.field-feedback[data-field]');
                    if (feedbackElement) {
                        feedbackElement.classList.add('hidden');
                        feedbackElement.style.display = 'none';
                    }
                }
            });

            if (!isValid) {
                // Focus op eerste ongeldige veld
                const firstInvalid = this.form.querySelector('.border-red-500, .border-destructive, [data-required-checkbox-group] .field-feedback:not(.hidden)');
                if (firstInvalid) {
                    if (firstInvalid.tagName === 'INPUT' || firstInvalid.tagName === 'SELECT' || firstInvalid.tagName === 'TEXTAREA') {
                        firstInvalid.focus();
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    } else {
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            }

            return isValid;
        }

        /**
         * Valideer een checkbox groep
         */
        validateCheckboxGroup(groupName) {
            const groupContainer = this.form.querySelector(`[data-required-checkbox-group="${groupName}"]`);
            if (!groupContainer) return true;

            const checkboxes = groupContainer.querySelectorAll(`input[type="checkbox"][data-checkbox-group="${groupName}"], input[type="checkbox"][name="${groupName}"]`);
            const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
            
            let feedbackElement = groupContainer.querySelector('.field-feedback[data-field]');
            if (!feedbackElement) {
                feedbackElement = document.createElement('div');
                feedbackElement.className = 'field-feedback text-xs text-destructive mt-1';
                feedbackElement.setAttribute('data-field', groupName);
                const errorAlert = groupContainer.querySelector('.kt-alert');
                if (errorAlert) {
                    errorAlert.parentNode.insertBefore(feedbackElement, errorAlert.nextSibling);
                } else {
                    groupContainer.insertBefore(feedbackElement, groupContainer.firstChild);
                }
            }
            
            if (checkedCount === 0) {
                feedbackElement.textContent = 'Selecteer minimaal één recht.';
                feedbackElement.classList.remove('hidden');
                feedbackElement.style.display = 'block';
                return false;
            } else {
                feedbackElement.classList.add('hidden');
                feedbackElement.style.display = 'none';
                return true;
            }
        }

        /**
         * Observeer dynamisch toegevoegde velden (bijv. via JavaScript)
         */
        observeDynamicFields() {
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === 1) { // Element node
                            const inputs = this.findInputsRecursive(node);
                            inputs.forEach(input => {
                                if (!this.validatedFields.has(input)) {
                                    this.attachValidation(input);
                                }
                            });
                        }
                    });
                });
            });

            observer.observe(this.form, {
                childList: true,
                subtree: true
            });
        }
    }

    // Auto-initialize op alle formulieren met data-validate attribute
    document.addEventListener('DOMContentLoaded', function() {
        const forms = document.querySelectorAll('form[data-validate="true"]');
        forms.forEach(form => {
            new FormValidator(form);
        });
    });

    // Export voor gebruik in andere scripts
    window.FormValidator = FormValidator;
    window.validationRules = validationRules;
})();



