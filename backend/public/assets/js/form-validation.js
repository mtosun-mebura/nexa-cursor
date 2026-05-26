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
                let formatted = String(value || '').trim().replace(/\s/g, '').replace(/-/g, '').replace(/\./g, '');
                if (formatted.startsWith('0031') && /^0031[1-9]\d{8}$/.test(formatted)) {
                    formatted = '+31' + formatted.substring(4);
                } else if (/^\+31[1-9]\d{8}$/.test(formatted)) {
                    return formatted;
                } else if (/^31[1-9]\d{8}$/.test(formatted)) {
                    formatted = '+' + formatted;
                } else if (formatted.startsWith('0') && formatted.length === 10 && /^0[1-9]\d{8}$/.test(formatted)) {
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
        },
        /** Zelfde regel als StoreUserRequest / UpdateUserRequest (voornaam, achternaam, contactnamen) */
        person_name: {
            pattern: /^[\p{L}\s\-'\.]+$/u,
            message: 'Alleen letters, spaties, streepjes (-), apostrofs (\') en punten zijn toegestaan (geen cijfers).',
            minLength: 2,
        },
        /** Kenteken (Nexa Taxi voertuigen): verplicht, max 20 op server; geen strikt NL-patroon i.v.m. import/varianten */
        license_plate: {
            minLength: 1,
            message: 'Voer een kenteken in.',
        },
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
                    // Andere listeners (o.a. website-pagina) kunnen submit al gemuteerd hebben — knop weer bruikbaar maken
                    this.form.querySelectorAll('button[type="submit"]').forEach((btn) => {
                        btn.disabled = false;
                    });
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
                    if (element.type === 'hidden' || element.type === 'submit' || element.type === 'button' || element.type === 'file' || element.type === 'color') {
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
                if (input.hasAttribute('data-email-template-select')) {
                    if (!value || value === '') {
                        this.setInvalid(input, feedbackElement, 'Kies een e-mailtemplate.', forceShow);

                        return false;
                    }
                    this.clearValidationState(input, feedbackElement);
                    this.setValid(input, feedbackElement, 'text', forceShow);

                    return true;
                }
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
                let patternValue = value;
                if (fieldType === 'phone' && validationRules[fieldType]?.format) {
                    const formatted = validationRules[fieldType].format(value);
                    if (formatted !== value) {
                        input.value = formatted;
                        patternValue = formatted;
                    }
                }

                let pattern = null;
                if (hasPattern) {
                    pattern = new RegExp(input.getAttribute('pattern'));
                } else if (validationRules[fieldType] && validationRules[fieldType].pattern) {
                    pattern = validationRules[fieldType].pattern;
                }

                if (pattern && !pattern.test(patternValue)) {
                    const message = validationRules[fieldType]?.message || 'Deze invoer is ongeldig.';
                    this.setInvalid(input, feedbackElement, message, forceShow);
                    return false;
                }

                if (validationRules[fieldType]?.format && fieldType !== 'phone') {
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
            const skipUrlValidation = this.form?.dataset?.skipUrlValidation === 'true';

            if (type === 'number') return 'number';
            if (
                name === 'first_name' ||
                name === 'last_name' ||
                name === 'contact_first_name' ||
                name === 'contact_last_name'
            ) {
                return 'person_name';
            }
            if (name === 'license_plate' || name.endsWith('[license_plate]')) return 'license_plate';
            // Sectie "E-mailtemplate (informatieaanvraag)": titel/template_id bevat "email" maar is geen e-mailadres
            if (/\[email_template/.test(name)) {
                return 'text';
            }
            if (type === 'email' || name.includes('email')) return 'email';
            if (type === 'tel' || name.includes('phone') || name.includes('telefoon')) return 'phone';
            if (name.includes('postal_code') || name.includes('postcode')) return 'postal_code';
            if (name.includes('kvk_number') || name.includes('kvk')) return 'kvk_number';
            if (type === 'password' || name.includes('password') || name.includes('wachtwoord')) return 'password';
            if (!skipUrlValidation && (type === 'url' || name.includes('website') || name.includes('url'))) return 'url';
            
            return 'text';
        }

        /**
         * Get minimum lengte voor een veld
         */
        getMinLength(input, fieldType) {
            const name = (input.name || '').toLowerCase();
            const isFooterLinkLabelField =
                name.includes('home_sections[footer][quick_links]') && name.endsWith('[label]') ||
                name.includes('home_sections[footer][support_links]') && name.endsWith('[label]');
            const isFooterLinkUrlField =
                name.includes('home_sections[footer][quick_links]') && name.endsWith('[url]') ||
                name.includes('home_sections[footer][support_links]') && name.endsWith('[url]');
            if (isFooterLinkLabelField) {
                return null;
            }
            if (isFooterLinkUrlField) {
                return null;
            }
            if (fieldType === 'person_name') {
                return validationRules.person_name?.minLength ?? 2;
            }
            if (input.dataset.skipMinlengthValidation === 'true') {
                return null;
            }
            // Number-velden: geen minimale tekenlengte (waarde 1 is geldig); min/max via HTML5
            if (fieldType === 'number') {
                return null;
            }
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
         * Verwijdert Laravel @error-blokken gemarkeerd met data-laravel-field (zelfde td als input).
         */
        removeLaravelInlineMessagesFor(input) {
            const fieldKey = input.getAttribute('name');
            if (!fieldKey) return;
            const td = input.closest('td');
            if (!td) return;
            td.querySelectorAll('[data-laravel-field]').forEach((el) => {
                if (el.getAttribute('data-laravel-field') === fieldKey) {
                    el.remove();
                }
            });
        }

        /**
         * Set veld als ongeldig
         */
        setInvalid(input, feedbackElement, message, forceShow = false) {
            if (this.shouldSkipValidationIcon(input)) {
                return;
            }
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
                input.classList.add('border-destructive');
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
                    feedbackElement.className = 'field-feedback text-xs text-red-600 text-destructive mt-1';
                    feedbackElement.textContent = message;
                    feedbackElement.classList.remove('hidden');
                    feedbackElement.style.display = 'block';
                }

                this.removeLaravelInlineMessagesFor(input);
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
        shouldSkipValidationIcon(input) {
            return (
                input.hasAttribute('data-skip-validation-wrapper') ||
                input.id === 'html_content'
            );
        }

        setValid(input, feedbackElement, fieldType, forceShow = false) {
            if (this.shouldSkipValidationIcon(input)) {
                this.clearValidationState(input, feedbackElement);
                return;
            }
            const isSelect = input.tagName === 'SELECT';
            const type = (input.type || '').toLowerCase();
            const isCheckboxOrRadio = input.tagName === 'INPUT' && (type === 'checkbox' || type === 'radio');
            const isRequired = input.hasAttribute('required');
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
            
            // Green success icon only for required fields
            const shouldShowSuccessIcon = userInteracted && !isCheckboxOrRadio && isRequired;
            if (shouldShowSuccessIcon) {
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
                
            } else {
                // Hide icon for optional fields, checkboxes/radios or untouched fields
                if (iconWrapper) {
                    iconWrapper.classList.add('hidden');
                    iconWrapper.style.display = 'none';
                    // Remove padding when icon is hidden
                    input.style.paddingRight = '';
                }
            }

            // For valid fields: verberg eigen feedback; Laravel-inline alleen via data-laravel-field verwijderen
            if (userInteracted) {
                if (feedbackElement) {
                    feedbackElement.classList.add('hidden');
                    feedbackElement.style.display = 'none';
                    feedbackElement.textContent = '';
                }

                this.removeLaravelInlineMessagesFor(input);
            } else if (feedbackElement) {
                feedbackElement.classList.add('hidden');
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
            // Native color pickers: geen validatie-icoon/padding — die breekt de kleurvak-weergave (smalle sliver).
            if (input.type === 'color') {
                return null;
            }
            // Carousel hex-velden (#RRGGBB): vast smal veld — geen 100%-wrapper of icoon.
            if (
                input.hasAttribute('data-skip-validation-wrapper') ||
                input.id === 'html_content' ||
                input.classList.contains('carousel-slide-text-bg-color-hex-input') ||
                input.classList.contains('carousel-slide-text-color-hex-input') ||
                input.closest('.carousel-slide-hex-input-wrap')
            ) {
                return null;
            }
            // Don't create validation icons for checkboxes or radio buttons
            if (input.type === 'checkbox' || input.type === 'radio') {
                return null;
            }
            
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

            // Never apply width:100% / relative on <label> — it stretches flex layouts (e.g. centered Menuitem).
            // Wrap the input in a div.relative instead (same as td-branch below).
            if (inputWrapper && inputWrapper.tagName === 'LABEL') {
                const wrapper = document.createElement('div');
                wrapper.className = 'relative';
                wrapper.style.position = 'relative';
                wrapper.style.width = '100%';
                input.parentNode.insertBefore(wrapper, input);
                wrapper.appendChild(input);
                inputWrapper = wrapper;
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
                    wrapper.style.width = '100%';
                    input.parentNode.insertBefore(wrapper, input);
                    wrapper.appendChild(input);
                    inputWrapper = wrapper;
                }
            }

            // Ensure input wrapper has relative positioning and full width
            if (inputWrapper) {
                const inCarouselCaptionRow = inputWrapper.closest(
                    '.carousel-slide-caption-options, .carousel-slide-caption-timing-options'
                );
                const inCarouselHexWrap = inputWrapper.classList.contains('carousel-slide-hex-input-wrap') ||
                    inputWrapper.closest('.carousel-slide-hex-input-wrap');
                if (!inputWrapper.classList.contains('relative')) {
                    inputWrapper.classList.add('relative');
                }
                inputWrapper.style.position = 'relative';
                if (inCarouselCaptionRow || inCarouselHexWrap) {
                    inputWrapper.style.width = '';
                    inputWrapper.style.maxWidth = '';
                    inputWrapper.classList.add('flex-none');
                } else if (!inputWrapper.style.width || inputWrapper.style.width === '') {
                    inputWrapper.style.width = '100%';
                }
            }

            // Create icon wrapper
            const iconWrapper = document.createElement('div');
            iconWrapper.className = 'validation-icon-wrapper absolute pointer-events-none hidden';
            iconWrapper.style.position = 'absolute';
            iconWrapper.style.right = '0.75rem';
            iconWrapper.style.top = '50%';
            iconWrapper.style.transform = 'translateY(-50%)';
            iconWrapper.style.display = 'none';
            iconWrapper.style.alignItems = 'center';
            iconWrapper.style.justifyContent = 'center';
            iconWrapper.style.zIndex = '10';
            iconWrapper.style.width = '1.25rem';
            iconWrapper.style.height = '1.25rem';
            
            // Ensure input has padding-right for the icon (but not for checkboxes or radio buttons)
            if (input.type !== 'checkbox' && input.type !== 'radio') {
                if (!input.style.paddingRight || input.style.paddingRight === '') {
                    input.style.paddingRight = '2.75rem';
                }
            }
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
                    
                    // Special handling for actions[] - show in orange bar above the card
                    if (groupName === 'actions[]') {
                        // Remove ALL existing feedback elements inside the card (including in sub-cards)
                        const existingFeedbackInCard = groupContainer.querySelectorAll('.field-feedback');
                        existingFeedbackInCard.forEach(el => {
                            // Only remove if it's related to actions[] validation
                            if (el.getAttribute('data-field') === 'actions[]' || el.textContent.includes('Selecteer minimaal één recht')) {
                                el.remove();
                            }
                        });
                        
                        // Show validation in orange bar above the card
                        let validationWrapper = document.getElementById('actions-validation-wrapper');
                        if (validationWrapper) {
                            let feedbackElement = validationWrapper.querySelector('.field-feedback[data-field="actions[]"]');
                            if (feedbackElement) {
                            feedbackElement.textContent = 'Selecteer minimaal één optie.';
                                // Remove hidden class and ensure it's visible
                                validationWrapper.classList.remove('hidden');
                                validationWrapper.style.display = 'block';
                                validationWrapper.style.visibility = 'visible';
                                validationWrapper.style.opacity = '1';
                                // Force reflow to ensure visibility
                                void validationWrapper.offsetHeight;
                            }
                        }
                    } else if (groupName === 'module_ids[]') {
                        // Modules op company edit: toon fout onderaan de modulekaart (niet in tabelcel).
                        const moduleValidationWrapper = document.getElementById('module-validation-wrapper');
                        if (moduleValidationWrapper) {
                            const feedbackElement = moduleValidationWrapper.querySelector('.field-feedback[data-field="module_ids[]"]');
                            if (feedbackElement) {
                                feedbackElement.textContent = 'Selecteer minimaal één module.';
                            }
                            moduleValidationWrapper.classList.remove('hidden');
                            moduleValidationWrapper.style.display = 'block';
                        }
                    } else {
                        // Standard handling for other checkbox groups
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
                        feedbackElement.textContent = 'Selecteer minimaal één optie.';
                        feedbackElement.classList.remove('hidden');
                        feedbackElement.style.display = 'block';
                    }
                } else {
                    // Clear error if at least one is checked
                    if (groupName === 'actions[]') {
                        // Remove ALL feedback elements inside the card (including in sub-cards)
                        const existingFeedbackInCard = groupContainer.querySelectorAll('.field-feedback');
                        existingFeedbackInCard.forEach(el => {
                            // Only remove if it's related to actions[] validation
                            if (el.getAttribute('data-field') === 'actions[]' || el.textContent.includes('Selecteer minimaal één recht')) {
                                el.remove();
                            }
                        });
                        
                        // Hide orange bar
                        let validationWrapper = document.getElementById('actions-validation-wrapper');
                        if (validationWrapper) {
                            validationWrapper.classList.add('hidden');
                            validationWrapper.style.display = 'none';
                        }
                    } else if (groupName === 'module_ids[]') {
                        const moduleValidationWrapper = document.getElementById('module-validation-wrapper');
                        if (moduleValidationWrapper) {
                            moduleValidationWrapper.classList.add('hidden');
                            moduleValidationWrapper.style.display = 'none';
                        }
                    } else {
                        const feedbackElement = groupContainer.querySelector('.field-feedback[data-field]');
                        if (feedbackElement) {
                            feedbackElement.classList.add('hidden');
                            feedbackElement.style.display = 'none';
                        }
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
            
            if (groupName === 'module_ids[]') {
                const moduleValidationWrapper = document.getElementById('module-validation-wrapper');
                if (!moduleValidationWrapper) return checkedCount > 0;

                const moduleFeedback = moduleValidationWrapper.querySelector('.field-feedback[data-field="module_ids[]"]');
                if (!moduleFeedback) return checkedCount > 0;

                if (checkedCount === 0) {
                    moduleFeedback.textContent = 'Selecteer minimaal één module.';
                    moduleValidationWrapper.classList.remove('hidden');
                    moduleValidationWrapper.style.display = 'block';
                    return false;
                }

                moduleValidationWrapper.classList.add('hidden');
                moduleValidationWrapper.style.display = 'none';
                return true;
            }

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
                feedbackElement.textContent = 'Selecteer minimaal één optie.';
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
            // Geen browser-popup (constraint validation); inline feedback via FormValidator
            form.setAttribute('novalidate', 'novalidate');
            new FormValidator(form);
        });
    });

    // Export voor gebruik in andere scripts
    window.FormValidator = FormValidator;
    window.validationRules = validationRules;
})();





