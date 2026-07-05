function validateEmailDetailed(value) {
    const trimmed = (value || '').trim();
    if (trimmed === '') {
        return null;
    }
    if (!trimmed.includes('@')) {
        return 'Het e-mailadres moet een @ bevatten.';
    }
    const parts = trimmed.split('@');
    if (parts.length !== 2) {
        return 'Het e-mailadres mag maar één @ bevatten.';
    }
    const [local, domain] = parts;
    if (local === '') {
        return 'Geef een adresgedeelte op vóór de @.';
    }
    if (domain === '') {
        return "Geef een adresgedeelte op na de '@'. Bijvoorbeeld: voorbeeld@domein.nl";
    }
    if (!domain.includes('.')) {
        return "Het e-mailadres moet een punt (.) bevatten in het deel na de '@'. Bijvoorbeeld: voorbeeld@domein.nl";
    }
    const afterLastDot = domain.substring(domain.lastIndexOf('.') + 1);
    if (afterLastDot.length < 2) {
        return 'Het deel na de laatste punt moet minstens twee tekens zijn (bijv. nl of com).';
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(trimmed)) {
        return 'Vul een geldig e-mailadres in. Controleer op spaties of ongeldige tekens.';
    }

    return null;
}

function validateTelefoonNl(value) {
    const trimmed = (value || '').trim();
    if (trimmed === '') {
        return null;
    }
    const normalized = trimmed.replace(/[\s-]/g, '');
    if (normalized.startsWith('+31')) {
        if (!/^\+31\d{9}$/.test(normalized)) {
            return 'Bij een nummer met +31 moeten daarna precies 9 cijfers volgen (totaal 12 karakters). Bijvoorbeeld: +31612345678';
        }
    } else {
        const digitsOnly = normalized.replace(/\D/g, '');
        if (digitsOnly.length !== 10) {
            return 'Het telefoonnummer moet 10 cijfers bevatten. Bijvoorbeeld: 0612345678';
        }
    }

    return null;
}

function getInfoRequestFieldMaxLength(input) {
    const fromAttr = parseInt(input?.dataset?.maxLength || '', 10);
    if (!Number.isNaN(fromAttr) && fromAttr > 0) {
        return fromAttr;
    }
    if (input?.maxLength && input.maxLength > 0) {
        return input.maxLength;
    }

    return 5000;
}

function updateInfoRequestCharCount(fieldEl, input) {
    const counter = fieldEl.querySelector('.info-request-char-count');
    if (!counter || input.tagName !== 'TEXTAREA') {
        return;
    }

    const max = getInfoRequestFieldMaxLength(input);
    const length = input.value.length;
    const remaining = Math.max(0, max - length);
    counter.textContent = remaining + ' tekens over · max. ' + max;
    const warnThreshold = Math.min(50, Math.ceil(max * 0.1));
    counter.classList.toggle('info-request-char-count--limit', remaining <= warnThreshold);
}

function validateInfoRequestField(fieldEl) {
    const label = fieldEl.dataset.fieldLabel || fieldEl.dataset.fieldName || 'Dit veld';
    const required = fieldEl.dataset.required === '1';
    const rule = fieldEl.dataset.validationRule || 'text';
    const input = fieldEl.querySelector('[data-info-request-input]');
    const value = (input?.value || '').trim();

    if (value === '') {
        if (required) {
            return { valid: false, message: label + ' is verplicht.' };
        }

        return { valid: null, message: '' };
    }

    if (rule === 'email') {
        const emailError = validateEmailDetailed(value);
        if (emailError) {
            return { valid: false, message: emailError };
        }
    }

    if (rule === 'tel') {
        const phoneError = validateTelefoonNl(value);
        if (phoneError) {
            return { valid: false, message: phoneError };
        }
    }

    const maxLength = input ? getInfoRequestFieldMaxLength(input) : 5000;
    if (value.length > maxLength) {
        return { valid: false, message: label + ' is te lang (max. ' + maxLength + ' tekens).' };
    }

    return { valid: true, message: '' };
}

function setInfoRequestFieldState(fieldEl, result, show) {
    const input = fieldEl.querySelector('[data-info-request-input]');
    const errorSpan = fieldEl.querySelector('.form-field-error');
    const statusWrap = fieldEl.querySelector('.info-request-field-status');
    const validIcon = fieldEl.querySelector('.info-request-icon-valid');
    const invalidIcon = fieldEl.querySelector('.info-request-icon-invalid');

    if (!input || !statusWrap || !validIcon || !invalidIcon) {
        return;
    }

    input.classList.remove(
        'border-red-500',
        'dark:border-red-500',
        'border-green-500',
        'dark:border-green-500',
        'focus:border-green-500',
        'dark:focus:border-green-500'
    );

    if (!show || result.valid === null) {
        statusWrap.classList.add('hidden');
        validIcon.classList.add('hidden');
        invalidIcon.classList.add('hidden');
        if (!fieldEl.dataset.serverError && errorSpan) {
            errorSpan.textContent = '';
        }

        return;
    }

    statusWrap.classList.remove('hidden');

    if (result.valid === true) {
        input.classList.add('border-green-500', 'dark:border-green-500', 'focus:border-green-500', 'dark:focus:border-green-500');
        validIcon.classList.remove('hidden');
        invalidIcon.classList.add('hidden');
        if (errorSpan) {
            errorSpan.textContent = '';
        }
        delete fieldEl.dataset.serverError;

        return;
    }

    input.classList.add('border-red-500', 'dark:border-red-500');
    validIcon.classList.add('hidden');
    invalidIcon.classList.remove('hidden');
    if (errorSpan) {
        errorSpan.textContent = result.message;
    }
}

export function initInfoRequestFormValidation(form) {
    const fields = Array.from(form.querySelectorAll('.info-request-field'));

    fields.forEach((fieldEl) => {
        const input = fieldEl.querySelector('[data-info-request-input]');
        const errorSpan = fieldEl.querySelector('.form-field-error');
        if (!input) {
            return;
        }

        let touched = false;

        updateInfoRequestCharCount(fieldEl, input);

        const run = (force) => {
            const value = (input.value || '').trim();
            if (!force && !touched && value === '') {
                setInfoRequestFieldState(fieldEl, { valid: null, message: '' }, false);

                return true;
            }

            const result = validateInfoRequestField(fieldEl);
            setInfoRequestFieldState(fieldEl, result, true);

            return result.valid !== false;
        };

        input.addEventListener('input', () => {
            touched = true;
            updateInfoRequestCharCount(fieldEl, input);
            run(true);
        });

        input.addEventListener('blur', () => {
            touched = true;
            run(true);
        });

        if (errorSpan && errorSpan.textContent.trim() !== '') {
            touched = true;
            fieldEl.dataset.serverError = '1';
            setInfoRequestFieldState(fieldEl, { valid: false, message: errorSpan.textContent.trim() }, true);
        }
    });

    return {
        validateAll() {
            let ok = true;
            fields.forEach((fieldEl) => {
                const result = validateInfoRequestField(fieldEl);
                setInfoRequestFieldState(fieldEl, result, true);
                if (result.valid === false) {
                    ok = false;
                }
            });

            return ok;
        },
        clearValidation() {
            fields.forEach((fieldEl) => {
                delete fieldEl.dataset.serverError;
                setInfoRequestFieldState(fieldEl, { valid: null, message: '' }, false);
            });
        },
        showServerErrors(errors) {
            this.clearValidation();
            Object.keys(errors || {}).forEach((name) => {
                const fieldEl = form.querySelector('.info-request-field[data-field-name="' + name + '"]');
                if (!fieldEl) {
                    return;
                }
                const msg = Array.isArray(errors[name]) ? errors[name][0] : errors[name];
                fieldEl.dataset.serverError = '1';
                setInfoRequestFieldState(fieldEl, { valid: false, message: msg }, true);
            });
        },
    };
}

function bootInfoRequestForms() {
    document.querySelectorAll('form[data-info-request-form]').forEach((form) => {
        if (form._infoRequestValidation) {
            return;
        }
        form._infoRequestValidation = initInfoRequestFormValidation(form);
    });
}

if (typeof window !== 'undefined') {
    window.NexaInfoRequestFormValidation = {
        init: initInfoRequestFormValidation,
        boot: bootInfoRequestForms,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootInfoRequestForms);
    } else {
        bootInfoRequestForms();
    }
}
