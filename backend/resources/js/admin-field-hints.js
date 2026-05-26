/**
 * Inline veldvalidatie-hints (groen = geldig) voor admin-formulieren.
 * Config: verborgen element #admin-field-hints-config met data-config (JSON uit AdminFieldValidationPatterns::wizardStep1Hints()).
 */

function normalizeValue(fieldName, raw, cfg) {
    let v = (raw ?? '').trim();
    if (cfg.normalize === 'nl_postal') {
        v = v.replace(/\s+/g, '').toUpperCase();
    }
    if (cfg.normalize === 'nl_phone') {
        v = v.replace(/\s/g, '').replace(/[-.]/g, '');
    }
    return v;
}

/** Minimaal: @, lokaal deel, domein met punt en TLD ≥ 2 tekens (geen spaties). */
function isEmailFormatOk(value) {
    const t = (value ?? '').trim();
    if (!t || /\s/.test(t)) return false;
    if (!t.includes('@')) return false;
    const at = t.indexOf('@');
    if (at <= 0) return false;
    const local = t.slice(0, at);
    const domain = t.slice(at + 1);
    if (!local || !domain) return false;
    if (!domain.includes('.')) return false;
    const dot = domain.lastIndexOf('.');
    if (dot <= 0 || dot >= domain.length - 1) return false;
    if (domain.length - dot - 1 < 2) return false;
    return true;
}

function evaluateField(input, cfg) {
    const v = normalizeValue(input.name, input.value, cfg);
    const required = !!cfg.required;

    if (v === '') {
        return required ? 'empty' : 'neutral';
    }

    const kind = cfg.kind || 'regex';

    if (kind === 'email') {
        return isEmailFormatOk(input.value) ? 'valid' : 'invalid';
    }

    if (kind === 'url') {
        try {
            const u = new URL(v);
            return u.protocol === 'http:' || u.protocol === 'https:' ? 'valid' : 'invalid';
        } catch {
            return 'invalid';
        }
    }

    if (kind === 'regex' && cfg.regex) {
        try {
            const re = new RegExp(cfg.regex, cfg.flags || '');
            return re.test(v) ? 'valid' : 'invalid';
        } catch {
            return 'invalid';
        }
    }

    return 'neutral';
}

function applyHintClasses(el, state, cfg) {
    el.classList.remove('text-emerald-600', 'dark:text-emerald-400', 'text-destructive', 'text-muted-foreground');
    if (state === 'valid') {
        el.classList.add('text-emerald-600', 'dark:text-emerald-400');
        el.textContent = cfg.valid || '';
        el.classList.remove('hidden');
        return;
    }
    if (state === 'invalid') {
        el.classList.add('text-destructive');
        el.textContent = cfg.invalid || '';
        el.classList.remove('hidden');
        return;
    }
    if (state === 'empty' && cfg.required) {
        el.classList.add('text-muted-foreground');
        el.textContent = '';
        el.classList.add('hidden');
        return;
    }
    el.textContent = '';
    el.classList.add('hidden');
}

function bindField(input, fieldName, cfg) {
    const hint = document.createElement('div');
    hint.setAttribute('data-admin-field-hint-output', fieldName);
    hint.className = 'text-xs mt-1';
    hint.setAttribute('aria-live', 'polite');
    input.insertAdjacentElement('afterend', hint);

    function refresh() {
        if (input.hasAttribute('data-server-error')) {
            hint.classList.add('hidden');
            hint.textContent = '';
            return;
        }
        if (input.disabled || input.readOnly) {
            const v = normalizeValue(fieldName, input.value, cfg);
            if (v !== '' && (cfg.kind === 'regex' || cfg.kind === 'email' || cfg.kind === 'url')) {
                const st = evaluateField(input, cfg);
                if (st === 'valid') {
                    applyHintClasses(hint, 'valid', cfg);
                    return;
                }
            }
            hint.classList.add('hidden');
            return;
        }

        const state = evaluateField(input, cfg);
        if (state === 'empty') {
            applyHintClasses(hint, 'empty', cfg);
            if (!input.hasAttribute('data-server-error')) {
                input.removeAttribute('aria-invalid');
                input.classList.remove('border-destructive');
            }
            return;
        }
        if (state === 'neutral') {
            applyHintClasses(hint, 'neutral', cfg);
            return;
        }
        applyHintClasses(hint, state, cfg);
        if (!input.hasAttribute('data-server-error')) {
            if (state === 'invalid') {
                input.setAttribute('aria-invalid', 'true');
                input.classList.add('border-destructive');
            } else {
                input.removeAttribute('aria-invalid');
                input.classList.remove('border-destructive');
            }
        }
    }

    ['input', 'change', 'blur'].forEach((ev) => {
        input.addEventListener(ev, () => {
            if (input.hasAttribute('data-server-error')) {
                input.removeAttribute('data-server-error');
            }
            refresh();
        });
    });

    refresh();
}

export function initAdminFieldHints() {
    const holder = document.getElementById('admin-field-hints-config');
    if (!holder || !holder.dataset.config) {
        return;
    }

    let config;
    try {
        config = JSON.parse(holder.dataset.config);
    } catch {
        return;
    }

    Object.keys(config).forEach((fieldName) => {
        const input = document.querySelector('#content [name="' + fieldName + '"]');
        if (!input || (input.type === 'hidden')) {
            return;
        }
        bindField(input, fieldName, config[fieldName]);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initAdminFieldHints();
});
