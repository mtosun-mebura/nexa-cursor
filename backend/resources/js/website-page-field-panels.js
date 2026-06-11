/**
 * In/uitklapbare subblokken binnen website-pagina sectie-componenten (admin builder).
 */

const CHEVRON_SVG =
    '<svg class="home-section-field-panel-chevron w-5 h-5 shrink-0 text-muted-foreground" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>';

const REPEATABLE_SELECTORS = [
    '.nexataxi-tarieven-item',
    '.nexataxi-booking-row',
    '.features-item-row',
    '.featured-services-item',
].join(',');

/** Sectietypes zonder inklapbare subblokken (originele editor-layout). */
function shouldSkipFieldPanels(container) {
    if (!container) {
        return false;
    }
    if (container.classList.contains('home-section-field-panels-skip')) {
        return true;
    }
    if (!container.classList.contains('home-section-card-body')) {
        return false;
    }
    const card = container.closest('.home-section-card');
    if (!card) return false;
    if (card.querySelector('.home-section-header--carousel')) {
        return true;
    }
    const sectionKey = card.getAttribute('data-section') || '';
    return sectionKey === 'carousel' || sectionKey.startsWith('carousel_');
}

function escapePanelTitle(text) {
    return String(text || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function setPanelCollapsed(panel, collapsed) {
    if (!panel) return;
    const toggle = panel.querySelector(':scope > .home-section-field-panel-toggle');
    const body = panel.querySelector(':scope > .home-section-field-panel-body');
    panel.classList.toggle('home-section-field-panel--collapsed', collapsed);
    if (toggle) toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    if (body) body.hidden = collapsed;
}

const EDITOR_STATE_KEY_PREFIX = 'website-page-editor-state:';
let persistEditorStateTimer = null;

function isWebsitePageEditor() {
    return !!(document.getElementById('website-page-form') || document.getElementById('home-sections-sortable'));
}

function getEditorStateStorageKey() {
    return EDITOR_STATE_KEY_PREFIX + (window.location.pathname || 'website-page');
}

function loadEditorState() {
    try {
        const raw = sessionStorage.getItem(getEditorStateStorageKey());
        if (!raw) return null;
        const parsed = JSON.parse(raw);
        return parsed && typeof parsed === 'object' ? parsed : null;
    } catch (err) {
        return null;
    }
}

function saveEditorState(state) {
    if (!isWebsitePageEditor()) return;
    try {
        sessionStorage.setItem(getEditorStateStorageKey(), JSON.stringify(state));
    } catch (err) {}
}

function getPanelStorageKey(panel) {
    const card = panel.closest('.home-section-card');
    const section = card?.getAttribute('data-section') || '_';
    const parts = [];
    let current = panel;
    while (current) {
        const titleEl = current.querySelector(':scope > .home-section-field-panel-toggle .home-section-field-panel-title');
        if (titleEl) {
            parts.unshift(titleEl.textContent.replace(/\s+/g, ' ').trim());
        }
        const parentPanel = current.parentElement?.closest('.home-section-field-panel');
        current = parentPanel || null;
    }
    return `${section}::${parts.join('::')}`;
}

function collectPanelState() {
    const panels = {};
    if (!isWebsitePageEditor()) return panels;
    document.querySelectorAll('.home-section-field-panel').forEach((panel) => {
        panels[getPanelStorageKey(panel)] = !panel.classList.contains('home-section-field-panel--collapsed');
    });
    return panels;
}

function collectSectionCardState() {
    const sections = {};
    document.querySelectorAll('.home-section-card[data-section]').forEach((card) => {
        const key = card.getAttribute('data-section');
        if (key) {
            sections[key] = card.classList.contains('home-section-card--collapsed');
        }
    });
    return sections;
}

function setSectionCardCollapsed(card, collapsed) {
    if (!card) return;
    const body = card.querySelector('.home-section-card-body');
    if (collapsed) {
        card.classList.add('home-section-card--collapsed');
        if (body) body.style.display = 'none';
    } else {
        card.classList.remove('home-section-card--collapsed');
        if (body) body.style.removeProperty('display');
    }
}

function persistEditorState() {
    if (!isWebsitePageEditor()) return;
    const previous = loadEditorState() || {};
    saveEditorState({
        ...previous,
        sections: collectSectionCardState(),
        panels: collectPanelState(),
        scroll: window.scrollY || window.pageYOffset || 0,
    });
}

function schedulePersistEditorState() {
    if (!isWebsitePageEditor()) return;
    clearTimeout(persistEditorStateTimer);
    persistEditorStateTimer = setTimeout(persistEditorState, 120);
}

function restoreScrollPosition() {
    const state = loadEditorState();
    const y = state?.scroll;
    if (typeof y !== 'number' || Number.isNaN(y) || y < 0) return;

    function doScroll() {
        window.scrollTo(0, y);
    }

    doScroll();
    requestAnimationFrame(doScroll);
    setTimeout(doScroll, 100);
    setTimeout(doScroll, 350);
    setTimeout(doScroll, 800);
    setTimeout(doScroll, 1500);
}

function restoreWebsitePageEditorState() {
    if (!isWebsitePageEditor()) return;
    const state = loadEditorState();
    if (!state) return;

    if (state.sections && typeof state.sections === 'object') {
        Object.entries(state.sections).forEach(([sectionKey, collapsed]) => {
            const card = document.querySelector(
                `.home-section-card[data-section="${CSS.escape(sectionKey)}"]`
            );
            if (card) setSectionCardCollapsed(card, !!collapsed);
        });
    }

    if (state.panels && typeof state.panels === 'object') {
        document.querySelectorAll('.home-section-field-panel').forEach((panel) => {
            const key = getPanelStorageKey(panel);
            if (Object.prototype.hasOwnProperty.call(state.panels, key)) {
                setPanelCollapsed(panel, !state.panels[key]);
            }
        });
    }

    restoreScrollPosition();
}

function bindWebsitePageEditorPersistence() {
    if (!isWebsitePageEditor() || document.body.dataset.websitePageEditorPersistence === '1') {
        return;
    }
    document.body.dataset.websitePageEditorPersistence = '1';

    let scrollSaveTimer;
    window.addEventListener(
        'scroll',
        () => {
            clearTimeout(scrollSaveTimer);
            scrollSaveTimer = setTimeout(schedulePersistEditorState, 150);
        },
        { passive: true }
    );

    window.addEventListener('pagehide', persistEditorState);
    window.addEventListener('beforeunload', persistEditorState);

    document.addEventListener(
        'submit',
        (e) => {
            const form = e.target?.closest ? e.target.closest('#website-page-form') : null;
            if (form) persistEditorState();
        },
        true
    );

    document.addEventListener('click', (e) => {
        if (
            e.target.closest('.home-section-collapse-toggle') ||
            e.target.closest('#home-sections-collapse-all-btn')
        ) {
            schedulePersistEditorState();
        }
    });
}

function createPanelElement(title, elements, open = false, actionsEl = null, nested = false) {
    const panel = document.createElement('div');
    panel.className =
        'home-section-field-panel' +
        (nested ? ' home-section-field-panel--nested' : '') +
        (open ? '' : ' home-section-field-panel--collapsed');

    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'home-section-field-panel-toggle';
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');

    const titleSpan = document.createElement('span');
    titleSpan.className = 'home-section-field-panel-title';
    titleSpan.textContent = title;

    toggle.appendChild(titleSpan);
    if (actionsEl) {
        const actionsWrap = document.createElement('span');
        actionsWrap.className = 'home-section-field-panel-actions';
        actionsWrap.appendChild(actionsEl);
        toggle.appendChild(actionsWrap);
    }
    toggle.insertAdjacentHTML('beforeend', CHEVRON_SVG);

    const body = document.createElement('div');
    body.className = 'home-section-field-panel-body';
    body.hidden = !open;
    elements.forEach((el) => {
        if (el && el.parentNode) body.appendChild(el);
    });

    panel.appendChild(toggle);
    panel.appendChild(body);
    return panel;
}

function inferPanelTitle(el, fallbackIndex) {
    if (el.dataset && el.dataset.panelTitle) {
        return el.dataset.panelTitle.trim();
    }
    const h4 = el.querySelector(':scope > h4, :scope h4.text-sm.font-medium');
    if (h4) {
        return h4.textContent.replace(/\s+/g, ' ').trim();
    }
    const firstLabel = el.querySelector(':scope label.text-sm.font-medium, :scope .text-sm.font-medium');
    if (firstLabel && firstLabel.textContent.length <= 48) {
        return firstLabel.textContent.replace(/\s+/g, ' ').trim();
    }
    if (el.classList.contains('row-visibility-row')) {
        return 'Inhoud';
    }
    return `Instellingen ${fallbackIndex + 1}`;
}

function inferRowGroupTitle(rows) {
    if (!rows.length) return 'Inhoud';
    const label = rows[0].querySelector('label.text-sm.font-medium, label.text-secondary-foreground');
    if (label) {
        const text = label.textContent.replace(/\s+/g, ' ').trim();
        if (text.length <= 40) return text;
    }
    return 'Inhoud';
}

function isPanelCandidate(el) {
    if (!el || el.nodeType !== 1) return false;
    if (el.dataset.fieldPanelSkip === '1') return false;
    if (el.classList.contains('home-section-field-panels')) return false;
    if (el.classList.contains('home-section-field-panel')) return false;
    if (el.classList.contains('home-section-field-panel-toolbar')) return false;
    if (el.matches('script, style, template')) return false;
    if (el.classList.contains('home-section-card-intro')) return false;
    if (el.matches('p.text-sm.text-muted-foreground') && !el.dataset.panelTitle) return false;
    return true;
}

function needsPanelizeContainer(container) {
    if (!container || container.classList.contains('home-section-component-hint')) return false;
    if (shouldSkipFieldPanels(container)) return false;
    if (container.dataset.fieldPanelsInit !== '1') return true;
    return !container.querySelector(':scope > .home-section-field-panels, :scope > .home-section-field-panel-toolbar');
}

function panelizePanelizeChildren(scope) {
    if (!scope || !scope.querySelectorAll) return;
    scope.querySelectorAll('.home-section-panelize-children').forEach((el) => {
        if (!needsPanelizeContainer(el)) return;
        if (el.dataset.fieldPanelsInit === '1') {
            el.removeAttribute('data-field-panels-init');
        }
        const parentBody = el.closest('.home-section-card-body');
        if (parentBody && parentBody.dataset.fieldPanelsInit === '1') {
            parentBody.removeAttribute('data-field-panels-init');
        }
        panelizeContainer(el, { includeToolbar: true });
    });
}

function buildPanelGroups(children) {
    const groups = [];
    let rowBuffer = [];

    function flushRows() {
        if (!rowBuffer.length) return;
        groups.push({
            title: inferRowGroupTitle(rowBuffer),
            elements: rowBuffer.splice(0),
            open: groups.length === 0,
        });
    }

    children.forEach((el, idx) => {
        if (!isPanelCandidate(el)) return;

        if (el.dataset.panelTitle) {
            flushRows();
            groups.push({
                title: el.dataset.panelTitle.trim(),
                elements: [el],
                open: groups.length === 0,
            });
            return;
        }

        if (el.classList.contains('space-y-2') && el.querySelector('h4')) {
            flushRows();
            groups.push({
                title: inferPanelTitle(el, idx),
                elements: [el],
                open: false,
            });
            return;
        }

        if (el.matches('div[class*="border-border"], div.border')) {
            flushRows();
            groups.push({
                title: inferPanelTitle(el, idx),
                elements: [el],
                open: groups.length === 0,
            });
            return;
        }

        if (el.classList.contains('row-visibility-row')) {
            rowBuffer.push(el);
            return;
        }

        if (el.id && el.id.startsWith('nexataxi-tarieven-items-')) {
            flushRows();
            groups.push({
                title: 'Tarievenkaarten',
                elements: [el],
                open: false,
            });
            return;
        }

        if (el.matches('.features-items-sortable, .featured-services-items, .carousel-slides-sortable, [id^="carousel-slides-"]')) {
            flushRows();
            groups.push({
                title: inferPanelTitle(el, idx) || 'Items',
                elements: [el],
                open: false,
            });
            return;
        }

        flushRows();
        groups.push({
            title: inferPanelTitle(el, idx),
            elements: [el],
            open: groups.length === 0,
        });
    });

    flushRows();
    return groups;
}

function extractRepeatableHeader(item) {
    const headerRow = item.querySelector(
        ':scope > .flex.items-center.justify-between, :scope > .flex.gap-3 > .flex-1 > .flex.items-center.gap-2'
    );
    if (!headerRow) {
        if (item.classList.contains('features-item-row')) {
            const removeBtn = item.querySelector('button.features-item-remove');
            return { title: null, actions: removeBtn ? removeBtn.cloneNode(true) : null };
        }
        return { title: null, actions: null };
    }

    let title = null;
    const titleEl = headerRow.querySelector(
        '.text-sm.font-medium, .text-sm.font-medium.text-secondary-foreground, .features-item-num'
    );
    if (titleEl) {
        title =
            titleEl.classList.contains('features-item-num') && headerRow.querySelector('p')
                ? headerRow.querySelector('p').textContent.replace(/\s+/g, ' ').trim()
                : titleEl.textContent.replace(/\s+/g, ' ').trim();
    }

    let actions = headerRow.querySelector(
        'button.nexataxi-tarieven-item-remove, button.features-item-remove, button.featured-services-item-remove, button.carousel-slide-remove'
    );
    if (actions) actions = actions.cloneNode(true);

    if (headerRow.parentElement === item && headerRow.matches(':scope > .flex.items-center.justify-between')) {
        headerRow.remove();
    } else if (item.classList.contains('features-item-row')) {
        const removeBtn = item.querySelector('button.features-item-remove');
        if (removeBtn) actions = removeBtn.cloneNode(true);
    }

    return { title, actions };
}

function inferRepeatableTitle(item, index) {
    if (item.classList.contains('features-item-row')) {
        const num = item.querySelector('.features-item-num');
        return num ? `Kaart ${num.textContent.trim()}` : `Kaart ${index + 1}`;
    }

    if (item.classList.contains('nexataxi-tarieven-item')) {
        const header = item.querySelector(':scope > .flex.items-center.justify-between .text-sm.font-medium');
        if (header) return header.textContent.replace(/\s+/g, ' ').trim();
    }

    if (item.classList.contains('featured-services-item')) {
        const header = item.querySelector('.text-sm.font-medium');
        if (header) return header.textContent.replace(/\s+/g, ' ').trim();
    }

    if (item.classList.contains('nexataxi-booking-row')) {
        const titleInput = item.querySelector('input[name*="[title]"]');
        if (titleInput && titleInput.value.trim()) return titleInput.value.trim();
        const list = item.closest('.nexataxi-booking-list');
        const listName = list ? list.getAttribute('data-list') || 'item' : 'item';
        return `${listName} ${index + 1}`;
    }

    return `Item ${index + 1}`;
}

function panelizeRepeatables(container) {
    if (!container) return;
    container.querySelectorAll(REPEATABLE_SELECTORS).forEach((item, index) => {
        if (item.closest('.home-section-field-panel-body > .home-section-field-panel')) return;
        if (item.dataset.panelWrapped === '1') return;
        if (item.closest('.home-section-field-panel') && item.matches('.nexataxi-booking-row, .nexataxi-tarieven-item')) {
            // Nested inside list panel — wrap individually.
        }

        item.dataset.panelWrapped = '1';
        const extracted = extractRepeatableHeader(item);
        const title = extracted.title || inferRepeatableTitle(item, index);
        const parent = item.parentNode;
        if (!parent) return;
        const next = item.nextSibling;
        const panel = createPanelElement(title, [item], false, extracted.actions, true);
        parent.insertBefore(panel, next);
    });
}

function createPanelToolbar(container) {
    const toolbar = document.createElement('div');
    toolbar.className =
        'home-section-field-panel-toolbar flex flex-wrap items-center justify-end gap-2 mb-2 min-w-0';
    toolbar.innerHTML =
        '<button type="button" class="home-section-field-panels-expand-all kt-btn kt-btn-xs kt-btn-ghost text-muted-foreground">Alles uitklappen</button>' +
        '<button type="button" class="home-section-field-panels-collapse-all kt-btn kt-btn-xs kt-btn-ghost text-muted-foreground">Alles inklappen</button>';

    toolbar.addEventListener('click', (e) => {
        const expandBtn = e.target.closest('.home-section-field-panels-expand-all');
        const collapseBtn = e.target.closest('.home-section-field-panels-collapse-all');
        if (!expandBtn && !collapseBtn) return;
        const panels = container.querySelectorAll(':scope > .home-section-field-panel');
        panels.forEach((panel) => setPanelCollapsed(panel, !!collapseBtn));
        schedulePersistEditorState();
    });

    return toolbar;
}

export function panelizeContainer(container, { includeToolbar = true } = {}) {
    if (!container || container.dataset.fieldPanelsInit === '1') return;
    if (container.classList.contains('home-section-component-hint')) return;
    if (shouldSkipFieldPanels(container)) return;

    const intro = container.querySelector(
        ':scope > p.text-sm.text-muted-foreground.home-section-card-intro, :scope > p.text-sm.text-muted-foreground:first-of-type'
    );

    const children = [...container.children].filter((el) => {
        if (el === intro) return false;
        if (el.classList.contains('home-section-field-panels')) return false;
        if (el.classList.contains('home-section-field-panel-toolbar')) return false;
        return isPanelCandidate(el);
    });

    if (children.length === 0) {
        container.dataset.fieldPanelsInit = '1';
        panelizeRepeatables(container);
        container.querySelectorAll('.home-section-panelize-children').forEach((nested) => {
            panelizeContainer(nested, { includeToolbar: true });
        });
        return;
    }

    if (children.length === 1 && children[0].classList.contains('home-section-panelize-children')) {
        container.dataset.fieldPanelsInit = '1';
        panelizeContainer(children[0], { includeToolbar: true });
        panelizeRepeatables(container);
        return;
    }

    if (children.length <= 1) {
        panelizeRepeatables(container);
        container.dataset.fieldPanelsInit = '1';
        container.querySelectorAll('.home-section-panelize-children').forEach((nested) => {
            panelizeContainer(nested, { includeToolbar: true });
        });
        return;
    }

    container.dataset.fieldPanelsInit = '1';

    const panelsWrap = document.createElement('div');
    panelsWrap.className = 'home-section-field-panels space-y-2 min-w-0';

    const groups = buildPanelGroups(children);
    groups.forEach((group, index) => {
        panelsWrap.appendChild(createPanelElement(group.title, group.elements, group.open ?? index === 0, null, false));
    });

    if (intro) {
        intro.classList.add('home-section-card-intro');
        if (includeToolbar) {
            const toolbar = createPanelToolbar(panelsWrap);
            if (intro.nextSibling) {
                container.insertBefore(toolbar, intro.nextSibling);
            } else {
                container.appendChild(toolbar);
            }
            container.insertBefore(panelsWrap, toolbar.nextSibling);
        } else {
            container.insertBefore(panelsWrap, intro.nextSibling);
        }
    } else {
        if (includeToolbar) {
            const toolbar = createPanelToolbar(panelsWrap);
            container.appendChild(toolbar);
            container.appendChild(panelsWrap);
        } else {
            container.prepend(panelsWrap);
        }
    }

    panelizeRepeatables(panelsWrap);
    container.querySelectorAll('.home-section-panelize-children').forEach((nested) => {
        panelizeContainer(nested, { includeToolbar: false });
    });
    panelizeRepeatables(container);
}

export function initHomeSectionFieldPanels(root) {
    bindWebsitePageEditorPersistence();
    const scope = root && root.querySelectorAll ? root : document;
    // Footer/copyright staan buiten #home-sections-sortable — eerst expliciet panelize-children.
    panelizePanelizeChildren(scope);
    try {
        scope.querySelectorAll('.home-section-card-body').forEach((body) => {
            if (shouldSkipFieldPanels(body)) return;
            try {
                panelizeContainer(body);
            } catch (err) {
                console.error(
                    '[website-page-field-panels]',
                    body.closest('.home-section-card')?.getAttribute('data-section') || 'section',
                    err
                );
            }
        });
    } finally {
        panelizePanelizeChildren(scope);
        ensureFooterConfigFieldPanels();
    }
    restoreWebsitePageEditorState();
}

export function ensureFooterConfigFieldPanels() {
    const el = document.getElementById('footer-config-content');
    if (!el || el.classList.contains('hidden')) return;
    if (!needsPanelizeContainer(el)) return;
    panelizePanelizeChildren(el.closest('#website-page-form') || document);
    restoreWebsitePageEditorState();
}

function runWebsitePageFieldPanelsInit() {
    if (!isWebsitePageEditor()) return;
    const root = document.getElementById('website-page-form');
    if (!root) return;
    initHomeSectionFieldPanels(root);
}

export function panelizeRepeatableElement(el) {
    if (!el || el.dataset.panelWrapped === '1') return;
    const cardBody = el.closest('.home-section-card-body');
    if (shouldSkipFieldPanels(cardBody)) return;
    const container = el.parentElement;
    if (!container) return;
    const index = [...container.querySelectorAll(REPEATABLE_SELECTORS)].indexOf(el);
    el.dataset.panelWrapped = '1';
    const extracted = extractRepeatableHeader(el);
    const title = inferRepeatableTitle(el, index >= 0 ? index : 0);
    const parent = el.parentNode;
    if (!parent) return;
    const next = el.nextSibling;
    const panel = createPanelElement(title, [el], true, extracted.actions, true);
    parent.insertBefore(panel, next);
}

function bindPanelToggleDelegation() {
    document.addEventListener('click', (e) => {
        const toggle = e.target.closest('.home-section-field-panel-toggle');
        if (!toggle) return;
        if (e.target.closest('.home-section-field-panel-actions')) return;
        const panel = toggle.closest('.home-section-field-panel');
        if (!panel) return;
        e.preventDefault();
        setPanelCollapsed(panel, !panel.classList.contains('home-section-field-panel--collapsed'));
        persistEditorState();
    });
}

document.addEventListener('DOMContentLoaded', () => {
    bindPanelToggleDelegation();
    bindWebsitePageEditorPersistence();
    runWebsitePageFieldPanelsInit();
});

window.addEventListener('load', () => {
    if (!isWebsitePageEditor()) return;
    runWebsitePageFieldPanelsInit();
    restoreWebsitePageEditorState();
});

window.initHomeSectionFieldPanels = initHomeSectionFieldPanels;
window.ensureFooterConfigFieldPanels = ensureFooterConfigFieldPanels;
window.panelizeRepeatableElement = panelizeRepeatableElement;
window.restoreWebsitePageEditorState = restoreWebsitePageEditorState;
window.persistWebsitePageEditorState = persistEditorState;
