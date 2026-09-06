/**
 * Smooth in/uitklappen voor admin-collapsibles (portaalgebruikers, settings, website-secties, …).
 */
import '../css/nexa-smooth-accordion.css';

const COLLAPSIBLE_PAIRS = [
    { parent: '.portal-user-block', body: '.portal-user-block-body' },
    { parent: '.home-section-card', body: '.home-section-card-body' },
    { parent: '.settings-collapsible-card', body: '.settings-collapsible-body' },
    { parent: '.settings-collapsible-section', body: '.settings-collapsible-body' },
    { parent: '.home-section-field-panel', body: '.home-section-field-panel-body' },
    { parent: '.nexa-pricing-package', body: '.nexa-pricing-package-body' },
    { parent: '#nexa-facturatie-werkwijze', body: '#nexa-facturatie-werkwijze-body' },
    { parent: '.kt-accordion-item', body: '.kt-accordion-content' },
];

function isWrapped(body) {
    return !!(body && body.parentElement && body.parentElement.classList.contains('nexa-smooth-accordion__clip'));
}

export function wrapCollapsibleBody(body) {
    if (!body || body.nodeType !== 1 || isWrapped(body)) {
        return body;
    }

    body.removeAttribute('hidden');
    body.classList.remove('hidden');
    if (body.style && body.style.display === 'none') {
        body.style.removeProperty('display');
    }

    const accordion = document.createElement('div');
    accordion.className = 'nexa-smooth-accordion';
    const clip = document.createElement('div');
    clip.className = 'nexa-smooth-accordion__clip';

    body.parentNode.insertBefore(accordion, body);
    accordion.appendChild(clip);
    clip.appendChild(body);

    return body;
}

function wrapParentBody(parent, bodySelector) {
    if (!parent || !parent.querySelector) {
        return;
    }
    const body = parent.querySelector(`:scope > ${bodySelector}`);
    if (body) {
        wrapCollapsibleBody(body);
    }
}

export function enhanceCollapsibleTree(root) {
    if (!root) {
        return;
    }

    COLLAPSIBLE_PAIRS.forEach(({ parent, body }) => {
        if (root.nodeType === 1 && root.matches(body) && root.parentElement?.matches(parent)) {
            wrapCollapsibleBody(root);
        }
        if (root.nodeType === 1 && root.matches(parent)) {
            wrapParentBody(root, body);
        }
        if (root.querySelectorAll) {
            root.querySelectorAll(parent).forEach((el) => wrapParentBody(el, body));
        }
    });
}

function boot() {
    if (window.__nexaSmoothAccordionBooted) {
        return;
    }
    window.__nexaSmoothAccordionBooted = true;

    document.documentElement.classList.add('nexa-smooth-accordion--boot');
    enhanceCollapsibleTree(document);

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === 1) {
                    enhanceCollapsibleTree(node);
                }
            });
        });
    });
    observer.observe(document.documentElement, { childList: true, subtree: true });

    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            document.documentElement.classList.remove('nexa-smooth-accordion--boot');
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
} else {
    boot();
}

if (!window.__nexaSmoothAccordionClickBound) {
    window.__nexaSmoothAccordionClickBound = true;
    document.addEventListener('click', (event) => {
        const toggle = event.target.closest('.portal-user-block-toggle');
        if (!toggle) {
            return;
        }
        const block = toggle.closest('[data-portal-user-block], .portal-user-block');
        if (!block) {
            return;
        }
        const collapsed = block.classList.toggle('portal-user-block--collapsed');
        toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    }, true);
}

window.nexaEnhanceCollapsibleTree = enhanceCollapsibleTree;
