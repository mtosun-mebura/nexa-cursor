/**
 * Admin responsive: mobiele kaarten uit tabellen + inklapbare filters.
 */

function getTableColumnLabels(table) {
    const labels = [];
    table.querySelectorAll('thead th').forEach((th) => {
        const explicit = th.getAttribute('data-label');
        if (explicit) {
            labels.push(explicit.trim());
            return;
        }
        const colLabel = th.querySelector('.kt-table-col-label');
        if (colLabel) {
            labels.push(colLabel.textContent.trim().replace(/\s+/g, ' '));
            return;
        }
        const text = th.textContent.trim().replace(/\s+/g, ' ');
        labels.push(text === '' ? '' : text);
    });
    return labels;
}

function cellHasCheckbox(td) {
    return td.querySelector('input[type="checkbox"]') !== null;
}

function isActionsColumn(td, index, totalCells) {
    if (index === totalCells - 1) {
        if (
            td.querySelector('.kt-menu') ||
            td.classList.contains('text-center') ||
            td.getAttribute('onclick')?.includes('stopPropagation')
        ) {
            return true;
        }
    }
    const label = (td.getAttribute('data-label') || '').toLowerCase();
    return label === 'acties' || label === 'actions';
}

function stripHtmlToText(html) {
    const div = document.createElement('div');
    div.innerHTML = html;
    return div.textContent.trim().replace(/\s+/g, ' ');
}

function cellDisplayHtml(td) {
    const clone = td.cloneNode(true);
    clone.querySelectorAll('script, .kt-menu, button.kt-menu-toggle').forEach((el) => el.remove());
    const html = clone.innerHTML.trim();
    if (!html) {
        return '<span class="text-muted-foreground">—</span>';
    }
    return html;
}

function resolveRowHref(tr) {
    const explicit =
        tr.getAttribute('data-row-href') ||
        tr.dataset.rowHref ||
        tr.getAttribute('data-href') ||
        tr.dataset.href;
    if (explicit) {
        return explicit;
    }

    const previewUrl = tr.getAttribute('data-preview-url');
    if (previewUrl) {
        return previewUrl;
    }

    const rowUserId = tr.getAttribute('data-user-id');
    if (rowUserId) {
        return `/admin/users/${rowUserId}`;
    }

    const companyIdEl = tr.querySelector('[data-company-id]');
    if (companyIdEl) {
        const id = companyIdEl.getAttribute('data-company-id');
        if (id) {
            return `/admin/companies/${id}`;
        }
    }

    const userIdEl = tr.querySelector('[data-user-id]');
    if (userIdEl) {
        const id = userIdEl.getAttribute('data-user-id');
        if (id) {
            return `/admin/users/${id}`;
        }
    }

    const viewLinks = tr.querySelectorAll('a[href]');
    for (const link of viewLinks) {
        if (link.closest('.kt-menu') || link.closest('td:last-child')) {
            continue;
        }
        const href = link.getAttribute('href');
        if (!href || href === '#' || href.startsWith('javascript:')) {
            continue;
        }
        if (href.includes('/edit') || href.includes('/create')) {
            continue;
        }
        return href;
    }

    for (const link of viewLinks) {
        const href = link.getAttribute('href');
        if (!href || href === '#' || href.includes('/edit')) {
            continue;
        }
        const menuLink = link.closest('.kt-menu');
        if (menuLink && link.textContent.trim().toLowerCase() === 'bekijken') {
            return href;
        }
    }

    return null;
}

const MENU_ACTION_ICON_BY_TITLE = {
    bekijken: 'ki-eye',
    details: 'ki-eye',
    bewerken: 'ki-pencil',
    'status aanpassen': 'ki-pencil',
    verwijderen: 'ki-trash',
    dupliceren: 'ki-copy',
    archiveren: 'ki-archive',
    activeren: 'ki-check-circle',
    deactiveren: 'ki-cross-circle',
    downloaden: 'ki-file-down',
    preview: 'ki-eye',
    voorbeeld: 'ki-eye',
    notificatielog: 'ki-message-text',
};

function stopCardNavigation(el) {
    el.addEventListener('click', (e) => e.stopPropagation());
    el.addEventListener('keydown', (e) => e.stopPropagation());
}

function isPrimaryViewActionLabel(label) {
    const key = (label || '')
        .toLowerCase()
        .replace(/\s*\(\d+\)\s*$/, '')
        .trim();
    return key === 'bekijken' || key === 'view' || key === 'details' || key === 'tonen';
}

function getMenuActionLabel(linkEl) {
    const fromTitle = linkEl.querySelector('.kt-menu-title')?.textContent?.trim();
    if (fromTitle) {
        return fromTitle;
    }
    return (
        linkEl.getAttribute('aria-label') ||
        linkEl.getAttribute('title') ||
        linkEl.textContent.trim()
    );
}

function getMenuActionIconClass(linkEl, label) {
    const iconEl = linkEl.querySelector('.kt-menu-icon i[class*="ki-"]');
    if (iconEl) {
        const classes = Array.from(iconEl.classList).filter((c) => c.startsWith('ki-'));
        if (classes.length > 0) {
            return classes.join(' ');
        }
    }
    const key = label.toLowerCase().replace(/\s*\(\d+\)\s*$/, '').trim();
    const ki = MENU_ACTION_ICON_BY_TITLE[key] || 'ki-eye';
    return `ki-filled ${ki}`;
}

/** Icoon uit menu-link (SVG of ki), voor gelabelde mobiele knoppen. */
function getMenuActionIconMarkup(linkEl, label) {
    const key = (label || '').toLowerCase().replace(/\s*\(\d+\)\s*$/, '').trim();
    if (MENU_ACTION_ICON_BY_TITLE[key]) {
        return `<i class="ki-filled ${MENU_ACTION_ICON_BY_TITLE[key]}" aria-hidden="true"></i>`;
    }
    const iconWrap = linkEl.querySelector('.kt-menu-icon');
    if (iconWrap && iconWrap.innerHTML.trim()) {
        return iconWrap.innerHTML.trim();
    }
    const iconClass = getMenuActionIconClass(linkEl, label);
    return `<i class="${iconClass}" aria-hidden="true"></i>`;
}

function createLabeledActionButton({ href, label, iconMarkup, isDanger, formElement }) {
    const btnClass =
        'kt-btn kt-btn-sm kt-btn-outline w-full justify-center gap-2 min-h-9' +
        (isDanger ? ' text-danger border-destructive/40 hover:bg-destructive/10' : '');

    if (formElement) {
        const wrap = document.createElement('div');
        wrap.className =
            'admin-card-action-form admin-card-action-form--labeled' +
            (isDanger ? ' admin-card-action-form--danger' : '');
        const formClone = formElement.cloneNode(true);
        const submitBtn = formClone.querySelector('button[type="submit"], button.kt-menu-link');
        if (!submitBtn) {
            return null;
        }
        submitBtn.className = btnClass;
        submitBtn.innerHTML = '';
        const iconSpan = document.createElement('span');
        iconSpan.className = 'inline-flex shrink-0 items-center text-base leading-none [&_i]:text-[1rem] [&_svg]:size-4';
        iconSpan.innerHTML = iconMarkup;
        const labelSpan = document.createElement('span');
        labelSpan.className = 'admin-list-card__action-label';
        labelSpan.textContent = label;
        submitBtn.appendChild(iconSpan);
        submitBtn.appendChild(labelSpan);
        wrap.appendChild(formClone);
        stopCardNavigation(wrap);
        return wrap;
    }

    const btn = document.createElement('a');
    btn.className = btnClass;
    btn.href = href || '#';
    const iconSpan = document.createElement('span');
    iconSpan.className = 'inline-flex shrink-0 items-center text-base leading-none [&_i]:text-[1rem] [&_svg]:size-4';
    iconSpan.innerHTML = iconMarkup;
    const labelSpan = document.createElement('span');
    labelSpan.className = 'admin-list-card__action-label';
    labelSpan.textContent = label;
    btn.appendChild(iconSpan);
    btn.appendChild(labelSpan);
    stopCardNavigation(btn);
    return btn;
}

function buildLabeledButtonFromMenuLink(linkEl) {
    const label = getMenuActionLabel(linkEl);
    if (!label) {
        return null;
    }

    const isDanger =
        linkEl.classList.contains('text-danger') ||
        label.toLowerCase().startsWith('verwijderen') ||
        label.toLowerCase() === 'delete';
    const iconMarkup = getMenuActionIconMarkup(linkEl, label);

    const parentForm = linkEl.closest('form');
    if (parentForm && linkEl.tagName === 'BUTTON') {
        return createLabeledActionButton({
            label,
            iconMarkup,
            isDanger,
            formElement: parentForm,
        });
    }

    if (linkEl.tagName === 'A') {
        return createLabeledActionButton({
            href: linkEl.getAttribute('href'),
            label,
            iconMarkup,
            isDanger,
        });
    }

    return null;
}

function createIconActionButton({ href, label, iconClass, isDanger, isSubmit, formHtml }) {
    if (formHtml) {
        const wrap = document.createElement('div');
        wrap.className = 'admin-card-action-form';
        wrap.innerHTML = formHtml;
        const form = wrap.querySelector('form');
        const btn = wrap.querySelector('button[type="submit"]');
        if (btn) {
            btn.className =
                'kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost' + (isDanger ? ' text-danger' : '');
            btn.innerHTML = `<i class="${iconClass}" aria-hidden="true"></i>`;
            btn.setAttribute('title', label);
            btn.setAttribute('aria-label', label);
        }
        stopCardNavigation(wrap);
        return wrap;
    }

    const btn = document.createElement('a');
    btn.className =
        'kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost' + (isDanger ? ' text-danger' : '');
    btn.href = href || '#';
    btn.innerHTML = `<i class="${iconClass}" aria-hidden="true"></i>`;
    btn.setAttribute('title', label);
    btn.setAttribute('aria-label', label);
    if (isSubmit) {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
        });
    }
    stopCardNavigation(btn);
    return btn;
}

function buildIconButtonFromMenuLink(linkEl) {
    const label = getMenuActionLabel(linkEl);
    if (!label) {
        return null;
    }

    const isDanger =
        linkEl.classList.contains('text-danger') ||
        label.toLowerCase() === 'verwijderen' ||
        label.toLowerCase() === 'delete';
    const iconClass = getMenuActionIconClass(linkEl, label);

    const parentForm = linkEl.closest('form');
    if (parentForm && linkEl.tagName === 'BUTTON') {
        const formClone = parentForm.cloneNode(true);
        const submitBtn = formClone.querySelector('button[type="submit"], button.kt-menu-link');
        if (!submitBtn) {
            return null;
        }
        submitBtn.className =
            'kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost' + (isDanger ? ' text-danger' : '');
        submitBtn.innerHTML = `<i class="${iconClass}" aria-hidden="true"></i>`;
        submitBtn.setAttribute('title', label);
        submitBtn.setAttribute('aria-label', label);
        const wrap = document.createElement('div');
        wrap.className = 'admin-card-action-form';
        wrap.appendChild(formClone);
        stopCardNavigation(wrap);
        return wrap;
    }

    if (linkEl.tagName === 'A') {
        return createIconActionButton({
            href: linkEl.getAttribute('href'),
            label,
            iconClass,
            isDanger,
        });
    }

    return null;
}

/** Zet kt-menu dropdown-acties om naar duidelijke knoppen met tekst (mobiele kaarten). */
function buildCardActionIcons(actionsTd, options = {}) {
    const toolbar = document.createElement('div');
    toolbar.className = 'admin-list-card__action-buttons';
    const skipView = Boolean(options.skipViewAction);
    const viewHref = options.viewHref || '';

    actionsTd.querySelectorAll('.kt-menu-dropdown .kt-menu-item, .website-pages-actions-dropdown .kt-menu-item').forEach((item) => {
        if (item.classList.contains('kt-menu-separator')) {
            return;
        }

        const form = item.querySelector(':scope > form');
        if (form) {
            const submitBtn = form.querySelector('button[type="submit"], button.kt-menu-link');
            if (submitBtn) {
                const labeledBtn = buildLabeledButtonFromMenuLink(submitBtn);
                if (labeledBtn) {
                    toolbar.appendChild(labeledBtn);
                }
            }
            return;
        }

        const link = item.querySelector('a.kt-menu-link, button.kt-menu-link');
        if (link) {
            const label = getMenuActionLabel(link);
            if (
                skipView &&
                (isPrimaryViewActionLabel(label) ||
                    (viewHref && link.getAttribute('href') === viewHref))
            ) {
                return;
            }
            const labeledBtn = buildLabeledButtonFromMenuLink(link);
            if (labeledBtn) {
                toolbar.appendChild(labeledBtn);
            }
        }
    });

    // Losse knoppen buiten dropdown (zonder kt-menu)
    actionsTd.querySelectorAll(':scope > a.kt-btn, :scope > button.kt-btn, :scope > form').forEach((el) => {
        if (el.closest('.kt-menu')) {
            return;
        }
        if (el.tagName === 'FORM') {
            const submitBtn = el.querySelector('button[type="submit"]');
            if (submitBtn) {
                const labeledBtn = buildLabeledButtonFromMenuLink(submitBtn);
                if (labeledBtn) {
                    toolbar.appendChild(labeledBtn);
                }
            }
            return;
        }
        if (el.classList.contains('kt-menu-toggle')) {
            return;
        }
        const clone = el.cloneNode(true);
        clone.classList.remove('kt-btn-icon', 'kt-btn-ghost');
        if (!clone.classList.contains('w-full')) {
            clone.classList.add('kt-btn-sm', 'kt-btn-outline', 'w-full', 'justify-center');
        }
        stopCardNavigation(clone);
        toolbar.appendChild(clone);
    });

    return toolbar.childNodes.length > 0 ? toolbar : null;
}

function buildListCard(tr, labels, table) {
    const cells = Array.from(tr.querySelectorAll(':scope > td'));
    if (cells.length === 0) {
        return null;
    }

    if (cells.length === 1 && cells[0].hasAttribute('colspan')) {
        return null;
    }

    const href = resolveRowHref(tr);
    const card = document.createElement('article');
    card.className = 'admin-list-card' + (href ? ' admin-list-card--clickable' : '');
    const rowId = tr.getAttribute('data-admin-row-id') || '';
    if (rowId) {
        card.setAttribute('data-admin-row-id', rowId);
    }
    if (href) {
        card.setAttribute('data-row-href', href);
        card.setAttribute('tabindex', '0');
        card.setAttribute('role', 'link');
    }

    const body = document.createElement('div');
    body.className = 'admin-list-card__body';

    const fields = document.createElement('dl');
    fields.className = 'admin-list-card__fields';

    let titleSet = false;
    let actionsTd = null;

    cells.forEach((td, index) => {
        if (cellHasCheckbox(td)) {
            return;
        }
        if (isActionsColumn(td, index, cells.length)) {
            actionsTd = td;
            return;
        }

        let label = (labels[index] || td.getAttribute('data-label') || '').trim();
        if (/^veld\s+\d+$/i.test(label) || label.toLowerCase() === 'acties') {
            label = '';
        }

        const valueHtml = cellDisplayHtml(td);
        const plain = stripHtmlToText(valueHtml);
        if (!plain) {
            return;
        }

        if (!titleSet && labels.length > 0) {
            const title = document.createElement('div');
            title.className = 'admin-list-card__title';
            title.innerHTML = valueHtml;
            body.appendChild(title);
            titleSet = true;
            return;
        }

        const field = document.createElement('div');
        field.className = 'admin-list-card__field' + (label ? '' : ' admin-list-card__field--value-only');
        if (label) {
            const dt = document.createElement('dt');
            dt.textContent = label;
            field.appendChild(dt);
        }
        const dd = document.createElement('dd');
        dd.innerHTML = valueHtml;
        field.appendChild(dd);
        fields.appendChild(field);
    });

    if (!titleSet && fields.children.length > 0) {
        const first = fields.children[0];
        const dd = first.querySelector('dd');
        if (dd) {
            const title = document.createElement('div');
            title.className = 'admin-list-card__title';
            title.innerHTML = dd.innerHTML;
            body.appendChild(title);
            first.remove();
        }
    }

    if (fields.children.length > 0) {
        body.appendChild(fields);
    }

    card.appendChild(body);

    if (actionsTd) {
        const actions = document.createElement('div');
        actions.className = 'admin-list-card__actions';
        actions.addEventListener('click', (e) => e.stopPropagation());

        const iconToolbar = buildCardActionIcons(actionsTd, {
            skipViewAction: Boolean(href),
            viewHref: href,
        });
        if (iconToolbar) {
            actions.appendChild(iconToolbar);
            card.appendChild(actions);
        }

    }

    if (href) {
        const navigate = () => {
            window.location.href = href;
        };
        card.addEventListener('click', (e) => {
            if (
                e.target.closest('.admin-list-card__actions') ||
                e.target.closest('.admin-card-action-form') ||
                e.target.closest('a') ||
                e.target.closest('button')
            ) {
                return;
            }
            navigate();
        });
        card.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                if (!e.target.closest('.admin-list-card__actions')) {
                    navigate();
                }
            }
        });
        body.style.cursor = 'pointer';
    }

    return card;
}

const TABLE_SCROLL_SELECTORS =
    '.kt-scrollable-x-auto, .kt-card-table, .kt-table-responsive, .admin-table-scroll-wrap';

function isFormLayoutTable(table) {
    if (table.classList.contains('wizard-onboarding-form-table')) {
        return true;
    }
    if (
        table.classList.contains('kt-table-border-dashed') &&
        !table.closest('.kt-card-table, [data-kt-datatable]')
    ) {
        return true;
    }
    return false;
}

function shouldAutoWrapTable(table) {
    if (!table.querySelector('thead') || !table.querySelector('tbody')) {
        return false;
    }
    if (table.dataset.adminNoCards === 'true' || table.classList.contains('admin-keep-table-layout')) {
        return false;
    }
    if (isFormLayoutTable(table)) {
        return false;
    }
    if (table.closest('.admin-mobile-list, ' + TABLE_SCROLL_SELECTORS)) {
        return false;
    }
    return table.closest('#content') !== null;
}

/** Zorg dat brede lijst-tabellen in een horizontale scroll-container staan. */
function wrapTablesForScroll() {
    const root = document.getElementById('content');
    if (!root) {
        return;
    }

    root.querySelectorAll('table.kt-table').forEach((table) => {
        if (!shouldAutoWrapTable(table)) {
            return;
        }

        const responsive = table.closest('.kt-table-responsive');
        if (responsive) {
            responsive.classList.add('kt-scrollable-x-auto', 'admin-table-scroll-wrap');
            return;
        }

        const parent = table.parentElement;
        if (!parent) {
            return;
        }

        const wrap = document.createElement('div');
        wrap.className = 'kt-scrollable-x-auto admin-table-scroll-wrap';
        parent.insertBefore(wrap, table);
        wrap.appendChild(table);
    });
}

function isListContextTable(table) {
    if (table.dataset.adminNoCards === 'true' || table.classList.contains('admin-keep-table-layout')) {
        return false;
    }
    if (table.closest('form:not([method="GET"])')) {
        return false;
    }
    if (isFormLayoutTable(table)) {
        return false;
    }
    const scrollWrap = table.closest(TABLE_SCROLL_SELECTORS);
    if (!scrollWrap) {
        return false;
    }
    if (scrollWrap.closest('.admin-mobile-list')) {
        return false;
    }
    return true;
}

function getMobileListForTable(table) {
    const scrollWrap = table.closest('.admin-desktop-table-wrap, ' + TABLE_SCROLL_SELECTORS);
    if (!scrollWrap) {
        return null;
    }
    const prev = scrollWrap.previousElementSibling;
    if (prev && prev.classList.contains('admin-mobile-list')) {
        return prev;
    }
    return null;
}

/** Verberg mobiele kaarten wanneer de datatable de rij verbergt (paginatie/zoeken). */
function getTableEmptyMessage(table) {
    const emptyCell = table.querySelector('tbody tr td[colspan]');
    if (emptyCell) {
        const text = emptyCell.textContent.trim().replace(/\s+/g, ' ');
        if (text) {
            return text;
        }
    }
    return 'Geen resultaten gevonden';
}

function syncMobileListEmptyState(table, list, visibleCount) {
    let emptyEl = list.querySelector('.admin-mobile-list-empty');
    if (visibleCount === 0) {
        if (!emptyEl) {
            emptyEl = document.createElement('div');
            emptyEl.className = 'admin-mobile-list-empty';
            emptyEl.setAttribute('role', 'status');
            list.appendChild(emptyEl);
        }
        emptyEl.textContent = getTableEmptyMessage(table);
        emptyEl.hidden = false;
        emptyEl.style.display = '';
        return;
    }
    if (emptyEl) {
        emptyEl.hidden = true;
        emptyEl.style.display = 'none';
    }
}

function rebuildMobileCards(table) {
    const list = getMobileListForTable(table);
    if (!list) {
        return;
    }

    const tbody = table.querySelector('tbody');
    if (!tbody) {
        return;
    }

    const rows = Array.from(tbody.querySelectorAll(':scope > tr')).filter(
        (tr) => !tr.querySelector('td[colspan]')
    );
    const labels = getTableColumnLabels(table);

    list.querySelectorAll('.admin-list-card').forEach((card) => card.remove());

    rows.forEach((tr) => {
        const cardEl = buildListCard(tr, labels, table);
        if (cardEl) {
            list.appendChild(cardEl);
        }
    });

    syncMobileCardsVisibility(table);
}

function syncMobileCardsVisibility(table) {
    const list = getMobileListForTable(table);
    if (!list) {
        return;
    }

    const rows = Array.from(table.querySelectorAll('tbody tr')).filter(
        (tr) => !tr.querySelector('td[colspan]')
    );
    const cards = Array.from(list.querySelectorAll('.admin-list-card'));
    let visibleCount = 0;

    if (cards.length !== rows.length) {
        rebuildMobileCards(table);
        return;
    }

    cards.forEach((card, index) => {
        const row = rows[index];
        if (!row) {
            card.style.display = 'none';
            return;
        }
        const hidden = window.getComputedStyle(row).display === 'none';
        card.style.display = hidden ? 'none' : '';
        if (!hidden) {
            visibleCount += 1;
        }
    });

    syncMobileListEmptyState(table, list, visibleCount);
}

function watchTableVisibility(table) {
    const tbody = table.querySelector('tbody');
    if (!tbody || tbody.dataset.adminMobileSync === '1') {
        return;
    }
    tbody.dataset.adminMobileSync = '1';

    const sync = () => syncMobileCardsVisibility(table);
    const rebuild = () => rebuildMobileCards(table);
    const scheduleRebuild = () => {
        setTimeout(rebuild, 0);
        setTimeout(rebuild, 80);
        setTimeout(rebuild, 200);
    };

    const observer = new MutationObserver((mutations) => {
        if (mutations.some((mutation) => mutation.type === 'childList')) {
            scheduleRebuild();
            return;
        }
        sync();
    });
    observer.observe(tbody, {
        attributes: true,
        subtree: true,
        attributeFilter: ['style', 'class', 'hidden'],
        childList: true,
    });

    const datatableRoot = table.closest('[data-admin-datatable], [data-kt-datatable]');
    if (datatableRoot) {
        datatableRoot.addEventListener('drew', scheduleRebuild);
        datatableRoot.addEventListener('admin-datatable:rendered', scheduleRebuild);

        datatableRoot.addEventListener('click', (e) => {
            if (
                e.target.closest(
                    '[data-admin-datatable-pagination], [data-kt-datatable-pagination], .kt-datatable-pagination, [data-admin-datatable-size], [data-kt-datatable-size]'
                )
            ) {
                scheduleRebuild();
            }
        });

        const searchSelector = datatableRoot.id
            ? `[data-admin-datatable-search="#${CSS.escape(datatableRoot.id)}"], [data-kt-datatable-search="#${CSS.escape(datatableRoot.id)}"]`
            : null;
        if (searchSelector) {
            document.querySelectorAll(searchSelector).forEach((input) => {
                input.addEventListener('input', scheduleRebuild);
                input.addEventListener('keyup', scheduleRebuild);
                input.addEventListener('search', scheduleRebuild);
            });
        }

        const cardSearch = datatableRoot.closest('.kt-card')?.querySelector('input[name="search"]');
        if (cardSearch && !cardSearch.dataset.adminMobileCardsSearchBound) {
            cardSearch.dataset.adminMobileCardsSearchBound = '1';
            cardSearch.addEventListener('input', scheduleRebuild);
            cardSearch.addEventListener('keyup', scheduleRebuild);
        }
    }

    sync();
}

function enhanceListTables() {
    const root = document.getElementById('content');
    if (!root) {
        return;
    }

    root.querySelectorAll(
        '.kt-scrollable-x-auto table.kt-table, .kt-card-table table.kt-table, .kt-table-responsive table.kt-table, .admin-table-scroll-wrap table.kt-table'
    ).forEach((table) => {
        if (table.dataset.adminCardsEnhanced === '1') {
            if (!getMobileListForTable(table) && isListContextTable(table)) {
                delete table.dataset.adminCardsEnhanced;
            } else {
                syncMobileCardsVisibility(table);
                return;
            }
        }

        if (!isListContextTable(table)) {
            return;
        }

        const scrollWrap = table.closest('.kt-scrollable-x-auto, .kt-card-table');
        if (!scrollWrap) {
            return;
        }

        const tbody = table.querySelector('tbody');
        if (!tbody) {
            return;
        }

        const rows = Array.from(tbody.querySelectorAll(':scope > tr')).filter(
            (tr) => !tr.querySelector('td[colspan]')
        );
        if (rows.length === 0) {
            return;
        }

        table.dataset.adminCardsEnhanced = '1';
        const labels = getTableColumnLabels(table);

        const list = document.createElement('div');
        list.className = 'admin-mobile-list';
        list.setAttribute('data-admin-mobile-list', '');

        rows.forEach((tr) => {
            const cardEl = buildListCard(tr, labels, table);
            if (cardEl) {
                list.appendChild(cardEl);
            }
        });

        if (list.children.length === 0) {
            return;
        }

        scrollWrap.classList.add('admin-desktop-table-wrap');
        scrollWrap.parentNode.insertBefore(list, scrollWrap);
        watchTableVisibility(table);
    });
}

let enhanceScheduled = false;

const ADMIN_LIVE_FILTER_DELAY_MS = 300;
const ADMIN_FILTER_PANEL_OPEN_KEY = 'admin-filter-panel-open:';

function filterPanelStorageKey() {
    return `${ADMIN_FILTER_PANEL_OPEN_KEY}${window.location.pathname}`;
}

function readFilterPanelOpenPref() {
    try {
        return sessionStorage.getItem(filterPanelStorageKey());
    } catch (error) {
        return null;
    }
}

function writeFilterPanelOpenPref(open) {
    try {
        sessionStorage.setItem(filterPanelStorageKey(), open ? '1' : '0');
    } catch (error) {
        // Private mode / blocked storage.
    }
}

function isGetForm(form) {
    return (form.getAttribute('method') || 'get').toLowerCase() === 'get';
}

function isLiveFilterDisabled(el) {
    return Boolean(el?.closest?.('[data-admin-live-filter="off"]'));
}

function isClientDatatableSelect(select) {
    return (
        select.hasAttribute('data-admin-datatable-filter') ||
        select.hasAttribute('data-admin-datatable-size') ||
        select.hasAttribute('data-kt-datatable-size')
    );
}

function isClientDatatableSearch(input) {
    if (!input) {
        return false;
    }
    if (
        input.hasAttribute('data-kt-datatable-search') ||
        input.hasAttribute('data-admin-datatable-search')
    ) {
        return true;
    }
    const card = input.closest('.kt-card');
    return Boolean(card?.querySelector('[data-admin-datatable="true"], [data-kt-datatable]'));
}

function formNeedsServerFilterSubmit(form) {
    const hasServerSelect = Array.from(form.querySelectorAll('select')).some(
        (select) => !isClientDatatableSelect(select)
    );
    if (hasServerSelect) {
        return true;
    }
    const search = form.querySelector('input[name="search"]');
    if (search && !isClientDatatableSearch(search)) {
        return true;
    }
    return Boolean(form.querySelector('input[data-kt-date-picker]'));
}

function isAdminLiveFilterForm(form) {
    if (!form || form.tagName !== 'FORM' || !isGetForm(form) || isLiveFilterDisabled(form)) {
        return false;
    }
    return (
        form.classList.contains('admin-filter-panel') ||
        form.classList.contains('admin-calendar-toolbar__filters') ||
        form.id === 'filters-form' ||
        form.id === 'search-form' ||
        Boolean(form.closest('.admin-filter-panel'))
    );
}

function collectAdminFilterGetForms(scope = document) {
    const forms = new Set();
    if (isAdminLiveFilterForm(scope)) {
        forms.add(scope);
    }
    if (scope?.querySelectorAll) {
        scope.querySelectorAll('form').forEach((form) => {
            if (isAdminLiveFilterForm(form)) {
                forms.add(form);
            }
        });
    }
    return Array.from(forms);
}

function cardNeedsServerFilterSubmit(form) {
    const card = form.closest('.kt-card');
    return collectAdminFilterGetForms(card || form).some(formNeedsServerFilterSubmit);
}

function setFilterQueryValue(params, name, value) {
    if (!name || name === '_token') {
        return;
    }
    if (value === '' || value == null) {
        params.delete(name);
        return;
    }
    params.set(name, String(value));
}

function collectAdminFilterParams(form) {
    const params = new URLSearchParams();
    const card = form.closest('.kt-card') || document.getElementById('content') || document;
    const forms = collectAdminFilterGetForms(card);

    forms.forEach((item) => {
        item.querySelectorAll('input[type="hidden"][name]').forEach((input) => {
            setFilterQueryValue(params, input.name, input.value);
        });
    });

    forms.forEach((item) => {
        item.querySelectorAll('select[name], input:not([type="hidden"])[name]').forEach((input) => {
            if (input.disabled) {
                return;
            }
            if ((input.type === 'checkbox' || input.type === 'radio') && !input.checked) {
                return;
            }
            setFilterQueryValue(params, input.name, input.value);
        });
    });

    const current = new URLSearchParams(window.location.search);
    ['perpage', 'per_page', 'direction'].forEach((key) => {
        if (!params.has(key) && current.has(key)) {
            params.set(key, current.get(key));
        }
    });

    params.delete('page');
    return params;
}

function keepFilterPanelOpen(form) {
    const panel =
        form.closest('.admin-filter-panel') ||
        form.closest('.kt-card-header')?.querySelector('.admin-filter-panel') ||
        form;
    panel.classList.add('is-open');
    const toggle = panel
        .closest('.kt-card-header')
        ?.querySelector('[data-admin-filter-toggle]');
    toggle?.setAttribute('aria-expanded', 'true');
    writeFilterPanelOpenPref(true);
}

function syncKtSelectDisplayValue(select) {
    const wrapper =
        select.closest('.kt-select-wrapper') ||
        select.parentElement?.querySelector?.('.kt-select-wrapper') ||
        select.parentElement;
    const display = wrapper?.querySelector('[data-kt-select-display]');
    const selected = select.options[select.selectedIndex];
    if (display && selected) {
        display.textContent = selected.textContent.trim();
    }
    if (!window.KTSelect) {
        return;
    }
    try {
        const instance = window.KTSelect.getInstance?.(select);
        if (instance?.update) {
            instance.update();
        } else if (instance?.setValue) {
            instance.setValue(select.value);
        }
    } catch (error) {
        // Filter-select blijft visueel in sync via display-tekst.
    }
}

function syncFilterFieldsFromRemote(localCard, remoteCard) {
    const localForms = collectAdminFilterGetForms(localCard);
    const remoteForms = collectAdminFilterGetForms(remoteCard);
    if (localForms.length === 0 || remoteForms.length === 0) {
        return;
    }

    const remoteValues = new Map();
    remoteForms.forEach((form) => {
        form.querySelectorAll('select[name], input[name]').forEach((input) => {
            if (!input.name || input.type === 'hidden') {
                return;
            }
            if ((input.type === 'checkbox' || input.type === 'radio') && !input.checked) {
                return;
            }
            remoteValues.set(input.name, input.value);
        });
    });

    localForms.forEach((form) => {
        form.querySelectorAll('select[name], input:not([type="hidden"])[name]').forEach((input) => {
            if (!input.name || !remoteValues.has(input.name)) {
                if (input.name && !remoteValues.has(input.name) && input.type !== 'checkbox') {
                    input.value = '';
                    if (input.tagName === 'SELECT') {
                        syncKtSelectDisplayValue(input);
                    }
                }
                return;
            }
            const next = remoteValues.get(input.name);
            if (input.value !== next) {
                input.value = next;
            }
            if (input.tagName === 'SELECT') {
                syncKtSelectDisplayValue(input);
            }
        });
        form.querySelectorAll('input[type="hidden"][name]').forEach((input) => {
            if (remoteValues.has(input.name)) {
                input.value = remoteValues.get(input.name);
            } else if (!['page', 'sort', 'direction', 'per_page', 'perpage'].includes(input.name)) {
                input.value = '';
            }
        });
    });
}

function syncResetFilterButton(localCard, remoteCard) {
    const localBtn = localCard.querySelector('#reset-filter-btn, a[title="Filters resetten"]');
    const remoteBtn = remoteCard.querySelector('#reset-filter-btn, a[title="Filters resetten"]');
    const panel = localCard.querySelector('.admin-filter-panel') || localCard.querySelector('.kt-card-header');
    if (remoteBtn && !localBtn && panel) {
        panel.appendChild(remoteBtn.cloneNode(true));
        return;
    }
    if (localBtn && !remoteBtn) {
        localBtn.remove();
        return;
    }
    if (localBtn && remoteBtn) {
        localBtn.setAttribute('href', remoteBtn.getAttribute('href') || localBtn.getAttribute('href'));
        localBtn.hidden = false;
        localBtn.style.display = '';
    }
}

function findRemoteFilterCard(doc, localCard, form) {
    if (form.id) {
        const remoteForm = doc.getElementById(form.id);
        const card = remoteForm?.closest('.kt-card');
        if (card) {
            return card;
        }
    }
    const localDt = localCard.querySelector('[data-admin-datatable][id], [data-kt-datatable][id]');
    if (localDt?.id) {
        const remoteDt = doc.getElementById(localDt.id);
        const card = remoteDt?.closest('.kt-card');
        if (card) {
            return card;
        }
    }
    return (
        doc.querySelector('#content .kt-card:has(.kt-card-header .admin-filter-panel)') ||
        doc.querySelector('#content .kt-card:has(#filters-form)') ||
        doc.querySelector('#content .kt-card')
    );
}

function clearAdminFilterFields(card) {
    collectAdminFilterGetForms(card).forEach((form) => {
        form.querySelectorAll('select[name], input[name]').forEach((input) => {
            if (!input.name || ['page', 'perpage', 'per_page'].includes(input.name)) {
                return;
            }
            if (input.type === 'hidden' || input.type === 'text' || input.type === 'search') {
                input.value = '';
                return;
            }
            if (input.tagName === 'SELECT') {
                input.value = '';
                syncKtSelectDisplayValue(input);
            }
        });
    });
}

function enhanceSwappedFilterResults(container) {
    if (typeof window.initAdminClientDatatables === 'function') {
        window.initAdminClientDatatables(container);
    }
    if (window.KTSelect && typeof window.KTSelect.init === 'function') {
        container.querySelectorAll('select[data-kt-select]').forEach((select) => {
            if (select.closest('.admin-filter-panel, .kt-card-header')) {
                return;
            }
            if (isClientDatatableSelect(select)) {
                return;
            }
            try {
                if (window.KTSelect.getInstance?.(select)) {
                    return;
                }
                window.KTSelect.init(select);
            } catch (error) {
                // Nieuwe lijst-selects zijn optioneel.
            }
        });
    }
    scheduleAdminResponsiveEnhance();
}

function applyAdminFilterHtml(form, html, url) {
    const localCard = form.closest('.kt-card');
    if (!localCard) {
        window.location.assign(url);
        return;
    }

    const localContent = localCard.querySelector(':scope > .kt-card-content, :scope > .kt-card-body');
    if (!localContent) {
        window.location.assign(url);
        return;
    }

    const doc = new DOMParser().parseFromString(html, 'text/html');
    const remoteCard = findRemoteFilterCard(doc, localCard, form);
    const remoteContent = remoteCard?.querySelector(':scope > .kt-card-content, :scope > .kt-card-body');
    if (!remoteCard || !remoteContent) {
        window.location.assign(url);
        return;
    }

    keepFilterPanelOpen(form);
    localContent.innerHTML = remoteContent.innerHTML;

    const localTitle = localCard.querySelector(':scope > .kt-card-header .kt-card-title, :scope > .kt-card-header h3');
    const remoteTitle = remoteCard.querySelector(':scope > .kt-card-header .kt-card-title, :scope > .kt-card-header h3');
    if (localTitle && remoteTitle) {
        localTitle.innerHTML = remoteTitle.innerHTML;
    }

    syncFilterFieldsFromRemote(localCard, remoteCard);
    syncResetFilterButton(localCard, remoteCard);
    keepFilterPanelOpen(form);

    const nextUrl = new URL(url, window.location.origin);
    const hash = window.location.hash || '';
    window.history.replaceState(window.history.state, '', `${nextUrl.pathname}${nextUrl.search}${hash}`);

    enhanceSwappedFilterResults(localContent);
}

async function submitAdminFilterForm(form, options = {}) {
    if (form.dataset.adminLiveFilter === 'off' || isLiveFilterDisabled(form)) {
        return;
    }

    const params = collectAdminFilterParams(form);
    let url = options.url;
    if (!url) {
        const action = form.getAttribute('action') || window.location.pathname;
        const next = new URL(action, window.location.origin);
        next.search = params.toString();
        url = `${next.pathname}${next.search}`;
    }

    if (form._adminFilterAbort) {
        form._adminFilterAbort.abort();
    }
    const abort = new AbortController();
    form._adminFilterAbort = abort;
    form._adminFilterRequestId = (Number(form._adminFilterRequestId) || 0) + 1;
    const requestId = form._adminFilterRequestId;

    const card = form.closest('.kt-card');
    const content = card?.querySelector(':scope > .kt-card-content, :scope > .kt-card-body');
    content?.setAttribute('aria-busy', 'true');
    keepFilterPanelOpen(form);

    try {
        const response = await fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                Accept: 'text/html',
                'X-Requested-With': 'XMLHttpRequest',
            },
            signal: abort.signal,
        });

        if (!response.ok) {
            window.location.assign(url);
            return;
        }

        const responseUrl = new URL(response.url, window.location.origin);
        if (responseUrl.origin === window.location.origin && responseUrl.pathname !== new URL(url, window.location.origin).pathname) {
            window.location.assign(response.url);
            return;
        }

        const html = await response.text();
        applyAdminFilterHtml(form, html, url);
    } catch (error) {
        if (error?.name === 'AbortError') {
            return;
        }
        window.location.assign(url);
    } finally {
        if (form._adminFilterRequestId === requestId) {
            content?.removeAttribute('aria-busy');
        }
    }
}

function queueAdminFilterFormSubmit(form) {
    if (form._adminFilterSubmitQueued) {
        return;
    }
    form._adminFilterSubmitQueued = true;
    queueMicrotask(() => {
        form._adminFilterSubmitQueued = false;
        submitAdminFilterForm(form);
    });
}

function bindAdminFilterAjaxNavigation(card, form) {
    if (!card || card.dataset.adminFilterAjaxNavBound === '1') {
        return;
    }
    card.dataset.adminFilterAjaxNavBound = '1';

    card.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');
        if (!link || link.target === '_blank' || link.hasAttribute('download')) {
            return;
        }
        if (link.closest('.kt-menu, .admin-list-card__actions, form[method="POST"], form[method="post"]')) {
            return;
        }

        const isReset =
            link.id === 'reset-filter-btn' || link.getAttribute('title') === 'Filters resetten';
        const inResults = link.closest(
            '.kt-card-content, .kt-card-body, .kt-card-table, .kt-card-footer, .kt-table-col-sort'
        );
        if (!isReset && !inResults) {
            return;
        }

        let next;
        try {
            next = new URL(link.href, window.location.origin);
        } catch (error) {
            return;
        }
        if (next.origin !== window.location.origin) {
            return;
        }

        const formUrl = new URL(form.getAttribute('action') || window.location.pathname, window.location.origin);
        if (next.pathname !== window.location.pathname && next.pathname !== formUrl.pathname) {
            return;
        }

        event.preventDefault();
        if (isReset) {
            clearAdminFilterFields(card);
        }
        submitAdminFilterForm(form, { url: `${next.pathname}${next.search}` });
    });
}

function bindAdminFilterPanelLiveSubmit(root = document) {
    const forms = collectAdminFilterGetForms(root.querySelector?.('#content') || root);

    forms.forEach((form) => {
        if (form.dataset.adminLiveFilterBound === '1' || isLiveFilterDisabled(form)) {
            return;
        }

        form.dataset.adminLiveFilterBound = '1';

        form.dataset.adminLiveFilter = 'ajax';

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            if (cardNeedsServerFilterSubmit(form)) {
                queueAdminFilterFormSubmit(form);
            }
        });

        form.submit = () => {
            if (cardNeedsServerFilterSubmit(form)) {
                queueAdminFilterFormSubmit(form);
                return;
            }
            window.HTMLFormElement.prototype.submit.call(form);
        };

        const searchInput = form.querySelector('input[name="search"]');
        let searchTimer = null;

        if (searchInput && !isClientDatatableSearch(searchInput)) {
            const queueSearchSubmit = () => {
                if (searchTimer) {
                    clearTimeout(searchTimer);
                }

                searchTimer = setTimeout(() => {
                    searchTimer = null;
                    queueAdminFilterFormSubmit(form);
                }, ADMIN_LIVE_FILTER_DELAY_MS);
            };

            searchInput.addEventListener('input', queueSearchSubmit);
            searchInput.addEventListener('compositionend', queueSearchSubmit);
        }

        form.querySelectorAll('select').forEach((select) => {
            if (isClientDatatableSelect(select)) {
                return;
            }
            select.addEventListener('change', () => queueAdminFilterFormSubmit(form));
        });

        form.querySelectorAll('input[data-kt-date-picker]').forEach((input) => {
            let lastValue = input.value;
            input.addEventListener('change', () => {
                if (input.value === lastValue) {
                    return;
                }
                lastValue = input.value;
                queueAdminFilterFormSubmit(form);
            });
        });

        const card = form.closest('.kt-card');
        if (card && formNeedsServerFilterSubmit(form)) {
            bindAdminFilterAjaxNavigation(card, form);
        }
    });
}

function fitNativeSelectToContent(select) {
    const option = select.options[select.selectedIndex];
    const text = option ? option.text : '';
    const style = window.getComputedStyle(select);
    const probe = document.createElement('span');
    probe.textContent = text || ' ';
    probe.style.cssText = [
        'position:absolute',
        'left:-9999px',
        'top:0',
        'white-space:nowrap',
        `font:${style.font}`,
        `letter-spacing:${style.letterSpacing}`,
        `text-transform:${style.textTransform}`,
    ].join(';');
    document.body.appendChild(probe);

    const padLeft = parseFloat(style.paddingLeft) || 0;
    const padRight = parseFloat(style.paddingRight) || 0;
    const border =
        (parseFloat(style.borderLeftWidth) || 0) + (parseFloat(style.borderRightWidth) || 0);
    const width = Math.ceil(probe.offsetWidth + padLeft + padRight + border + 2);
    probe.remove();
    select.style.width = `${width}px`;
}

function bindContentWidthSelects(root = document) {
    root.querySelectorAll('#content .admin-calendar-toolbar__filters select').forEach((select) => {
        if (select.dataset.contentWidthBound === '1') {
            return;
        }
        select.dataset.contentWidthBound = '1';
        const fit = () => fitNativeSelectToContent(select);
        fit();
        select.addEventListener('change', fit);
    });
}

function ensureAdminTableMenuDropdownVisible(dropdown, toggle) {
    if (!dropdown || !toggle) {
        return;
    }

    const viewportPadding = 12;
    const rect = toggle.getBoundingClientRect();
    const dropdownHeight = dropdown.offsetHeight || dropdown.scrollHeight || 0;
    const dropdownWidth = dropdown.offsetWidth || dropdown.scrollWidth || 175;

    let top = rect.bottom + 4;
    const spaceBelow = window.innerHeight - rect.bottom - viewportPadding;
    const spaceAbove = rect.top - viewportPadding;

    if (dropdownHeight > spaceBelow && spaceAbove > spaceBelow) {
        top = Math.max(viewportPadding, rect.top - dropdownHeight - 4);
    }

    dropdown.style.position = 'fixed';
    dropdown.style.zIndex = '99999';
    dropdown.style.pointerEvents = 'auto';
    dropdown.style.top = `${top}px`;
    dropdown.style.left = `${Math.max(viewportPadding, rect.right - dropdownWidth)}px`;
}

function closeAdminTableActionMenus(exceptItem = null) {
    document.querySelectorAll('#content table .kt-menu-item.show').forEach((menuItem) => {
        if (menuItem === exceptItem) {
            return;
        }

        menuItem.classList.remove('show');
        const dropdown = menuItem.querySelector('.kt-menu-dropdown');
        if (dropdown) {
            dropdown.style.display = 'none';
        }
    });

    const invoicesCard = document.getElementById('transport-contract-invoices-card');
    if (invoicesCard) {
        invoicesCard.classList.toggle(
            'transport-contract-invoices-card--menu-open',
            Boolean(invoicesCard.querySelector('.kt-menu-item.show'))
        );
    }
}

function repositionOpenAdminTableMenus() {
    document.querySelectorAll('#content table .kt-menu-item.show').forEach((menuItem) => {
        const toggle = menuItem.querySelector('.kt-menu-toggle');
        const dropdown = menuItem.querySelector('.kt-menu-dropdown');
        if (toggle && dropdown) {
            ensureAdminTableMenuDropdownVisible(dropdown, toggle);
        }
    });
}

function isAdminTableActionMenuToggle(event) {
    return event.target.closest('#content table .kt-menu-toggle');
}

function isAdminTableActionMenuDropdown(event) {
    const dropdown = event.target.closest('.kt-menu-dropdown');
    return Boolean(dropdown && dropdown.closest('#content table'));
}

function bindAdminTableActionMenus() {
    if (document.documentElement.dataset.adminTableMenuBound === '1') {
        return;
    }

    document.documentElement.dataset.adminTableMenuBound = '1';

    const destroyTableKtMenus = () => {
        if (!window.KTMenu || typeof window.KTMenu.getInstance !== 'function') {
            return;
        }

        document.querySelectorAll('#content table [data-kt-menu]').forEach((menuEl) => {
            try {
                const existing = window.KTMenu.getInstance(menuEl);
                if (existing && typeof existing.destroy === 'function') {
                    existing.destroy();
                }
            } catch (error) {
                // Menu kan al zonder KT-instance bestaan.
            }
        });
    };

    destroyTableKtMenus();
    setTimeout(destroyTableKtMenus, 250);
    setTimeout(destroyTableKtMenus, 800);

    document.addEventListener(
        'click',
        (event) => {
            const toggle = isAdminTableActionMenuToggle(event);
            if (toggle) {
                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation();

                const menuItem =
                    toggle.closest('.kt-menu-item[data-kt-menu-item-toggle="dropdown"]') ||
                    toggle.closest('.kt-menu-item');
                const dropdown = menuItem?.querySelector('.kt-menu-dropdown');
                if (!menuItem || !dropdown) {
                    return;
                }

                const isOpen = menuItem.classList.contains('show');
                closeAdminTableActionMenus();
                if (!isOpen) {
                    menuItem.classList.add('show');
                    dropdown.style.display = 'block';
                    dropdown.style.visibility = 'visible';
                    dropdown.style.opacity = '1';
                    ensureAdminTableMenuDropdownVisible(dropdown, toggle);
                    requestAnimationFrame(() => ensureAdminTableMenuDropdownVisible(dropdown, toggle));
                    const invoicesCard = document.getElementById('transport-contract-invoices-card');
                    if (invoicesCard?.contains(menuItem)) {
                        invoicesCard.classList.add('transport-contract-invoices-card--menu-open');
                    }
                }

                return;
            }

            if (isAdminTableActionMenuDropdown(event)) {
                const link = event.target.closest('a[href]');
                if (link) {
                    const href = link.getAttribute('href');
                    if (href && href !== '#' && !href.toLowerCase().startsWith('javascript:')) {
                        event.preventDefault();
                        event.stopPropagation();
                        event.stopImmediatePropagation();
                        window.location.assign(link.href);
                    }
                    return;
                }

                const submitBtn = event.target.closest('button[type="submit"], button.kt-menu-link');
                if (submitBtn) {
                    const form = submitBtn.closest('form');
                    if (form) {
                        event.preventDefault();
                        event.stopPropagation();
                        event.stopImmediatePropagation();
                        closeAdminTableActionMenus();
                        if (typeof form.requestSubmit === 'function') {
                            form.requestSubmit(submitBtn);
                        } else {
                            form.submit();
                        }
                    }
                }
                return;
            }

            closeAdminTableActionMenus();
        },
        true
    );

    window.addEventListener('resize', repositionOpenAdminTableMenus);
    window.addEventListener('scroll', repositionOpenAdminTableMenus, true);
}

function bindClickableTableRows(root = document) {
    root.querySelectorAll('#content table tbody tr[data-row-href], #content table tbody tr[data-href]').forEach((row) => {
        if (row.dataset.adminRowLinkBound === '1') {
            return;
        }

        const href = row.getAttribute('data-row-href') || row.getAttribute('data-href');
        if (!href) {
            return;
        }

        row.dataset.adminRowLinkBound = '1';
        row.classList.add('admin-table-row--clickable');
        row.setAttribute('role', 'button');
        row.setAttribute('tabindex', '0');

        const navigate = () => {
            window.location.href = href;
        };

        row.addEventListener('click', (e) => {
            if (
                e.target.closest(
                    'a, button, input, select, textarea, label, [data-no-row-link], .kt-menu, .kt-menu-dropdown, .kt-menu-toggle, .website-page-actions-cell'
                )
            ) {
                return;
            }
            navigate();
        });

        row.addEventListener('keydown', (e) => {
            if (e.target.closest('[data-no-row-link], .kt-menu, .kt-menu-dropdown, .kt-menu-toggle, .website-page-actions-cell')) {
                return;
            }
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                navigate();
            }
        });
    });
}

function scheduleAdminResponsiveEnhance() {
    if (enhanceScheduled) {
        return;
    }
    enhanceScheduled = true;
    requestAnimationFrame(() => {
        enhanceScheduled = false;
        wrapTablesForScroll();
        enhanceListTables();
        bindAdminFilterPanelLiveSubmit();
        bindContentWidthSelects();
        bindAdminTableActionMenus();
        bindClickableTableRows();
    });
}

function countActiveFilters(panel) {
    let count = 0;
    panel.querySelectorAll('select').forEach((sel) => {
        if (sel.value && sel.value !== '' && sel.value !== 'all') {
            count += 1;
        }
    });
    panel.querySelectorAll('input[type="text"], input[type="search"]').forEach((input) => {
        if (input.name && input.value.trim() !== '') {
            count += 1;
        }
    });
    panel.querySelectorAll('input[type="hidden"]').forEach((input) => {
        const name = input.name;
        if (!name || ['page', 'sort', 'direction', 'per_page'].includes(name)) {
            return;
        }
        if (input.value && input.value !== '') {
            const textInput = panel.querySelector(`input[type="text"][name="${name}"]`);
            if (!textInput) {
                count += 1;
            }
        }
    });
    return count;
}

function findFilterRow(header) {
    const existing = header.querySelector('.admin-filter-panel');
    if (existing) {
        return existing;
    }

    const form = header.querySelector('#filters-form, #search-form, form[method="GET"]');
    if (!form) {
        return null;
    }

    let candidate = form;
    let node = form.parentElement;
    while (node && node !== header) {
        if (node.classList?.contains('flex')) {
            candidate = node;
        }
        node = node.parentElement;
    }

    return candidate;
}

function enhanceFilterPanels() {
    document.querySelectorAll('.kt-card-header').forEach((header) => {
        if (header.dataset.adminFiltersEnhanced === '1') {
            return;
        }

        const filterRow = findFilterRow(header);
        if (!filterRow || filterRow.classList.contains('admin-filter-panel')) {
            return;
        }

        header.dataset.adminFiltersEnhanced = '1';
        filterRow.classList.add('admin-filter-panel');

        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'kt-btn kt-btn-outline admin-filter-toggle';
        toggle.setAttribute('data-admin-filter-toggle', '');
        toggle.setAttribute('aria-expanded', 'false');

        const updateToggleLabel = () => {
            const active = countActiveFilters(filterRow);
            const badge =
                active > 0
                    ? ` <span class="admin-filter-toggle__badge" aria-hidden="true">${active}</span>`
                    : '';
            toggle.innerHTML = `<i class="ki-filled ki-filter me-2" aria-hidden="true"></i>Filters &amp; zoeken${badge}`;
        };
        updateToggleLabel();

        toggle.addEventListener('click', () => {
            const open = filterRow.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            writeFilterPanelOpenPref(open);
        });

        header.insertBefore(toggle, filterRow);

        filterRow.querySelectorAll('select, input').forEach((el) => {
            el.addEventListener('change', updateToggleLabel);
            el.addEventListener('input', updateToggleLabel);
        });

        if (readFilterPanelOpenPref() === '1' || countActiveFilters(filterRow) > 0) {
            filterRow.classList.add('is-open');
            toggle.setAttribute('aria-expanded', 'true');
        }

        const mq = window.matchMedia('(min-width: 1024px)');
        const syncDesktop = () => {
            if (mq.matches) {
                filterRow.classList.add('is-open');
                toggle.setAttribute('aria-expanded', 'true');
            }
        };
        mq.addEventListener('change', syncDesktop);
        syncDesktop();
    });
}

const SELECT_DROPDOWN_SELECTOR = '.kt-select-dropdown, [data-kt-select-dropdown]';
const SELECT_WRAPPER_SELECTOR = '.kt-select-wrapper, [data-kt-select-wrapper]';

function isSelectDropdownOpen(dropdown) {
    if (!dropdown || dropdown.hasAttribute('hidden') || dropdown.classList.contains('hidden')) {
        return false;
    }
    return dropdown.classList.contains('open') || dropdown.classList.contains('show');
}

function syncSelectDropdownOpenState() {
    let anyOpen = false;
    document.querySelectorAll(SELECT_WRAPPER_SELECTOR).forEach((wrapper) => {
        const dropdown = wrapper.querySelector(SELECT_DROPDOWN_SELECTOR);
        const open = isSelectDropdownOpen(dropdown);
        wrapper.classList.toggle('is-dropdown-open', open);
        anyOpen = anyOpen || open;
    });
    document.documentElement.classList.toggle('admin-kt-select-open', anyOpen);
}

/**
 * Alleen op click/Escape: geen MutationObserver op document.body.
 * Die observer + inline Popper-overrides vroor de pagina zodra het
 * filterpaneel openging (KT Select initialiseert dan alle dropdowns).
 */
function bindAdminSelectDropdowns() {
    if (document.documentElement.dataset.adminSelectDropdownsBound === '1') {
        return;
    }
    document.documentElement.dataset.adminSelectDropdownsBound = '1';

    document.addEventListener(
        'click',
        () => {
            requestAnimationFrame(syncSelectDropdownOpenState);
        },
        true
    );
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            requestAnimationFrame(syncSelectDropdownOpenState);
        }
    });
}

function markPageActionBars() {
    document
        .querySelectorAll(
            '#content .kt-container-fixed > .flex.flex-wrap, #content [class*="pb-7.5"].flex.flex-wrap'
        )
        .forEach((bar) => {
            if (!bar.querySelector('h1')) {
                return;
            }
            const targets = bar.querySelectorAll(
                '.admin-page-actions, [data-company-create-actions], .justify-end, .shrink-0.flex.flex-wrap, .flex.flex-wrap.items-center.gap-2.shrink-0, .flex.items-center.gap-2\\.5'
            );
            targets.forEach((actions) => {
                if (!actions.classList.contains('admin-page-actions')) {
                    actions.classList.add('admin-page-actions');
                }
            });
        });
}

function isAdminMobileNavViewport() {
    return window.matchMedia('(max-width: 1023px)').matches;
}

/** Mobiel: drawer alleen open na hamburger — nooit open herstellen bij paginaload. */
function closeAdminMobileNavDrawer() {
    if (!isAdminMobileNavViewport()) {
        return;
    }

    const sidebar = document.getElementById('sidebar');
    if (!sidebar) {
        return;
    }

    sidebar.classList.remove('open');
    sidebar.classList.remove('flex');
    sidebar.classList.add('hidden');
    sidebar.removeAttribute('role');
    sidebar.removeAttribute('aria-modal');
    sidebar.style.zIndex = '';
    document.body.style.overflow = '';

    try {
        const inst = window.KTDrawer?.getInstance?.(sidebar);
        if (inst && typeof inst.isOpen === 'function' && inst.isOpen() && typeof inst.hide === 'function') {
            inst.hide();
        }
    } catch (error) {
        // Drawer-instance is optioneel; CSS houdt de kolom off-canvas.
    }
}

function bindAdminMobileNavDrawerClosedByDefault() {
    if (document.documentElement.dataset.adminMobileNavDrawerBound === '1') {
        return;
    }
    document.documentElement.dataset.adminMobileNavDrawerBound = '1';

    closeAdminMobileNavDrawer();
    window.addEventListener('pageshow', closeAdminMobileNavDrawer);
}

export function initAdminResponsive() {
    bindAdminMobileNavDrawerClosedByDefault();
    markPageActionBars();
    enhanceFilterPanels();
    bindAdminFilterPanelLiveSubmit();
    bindAdminSelectDropdowns();
    bindContentWidthSelects();
    bindAdminTableActionMenus();
    bindClickableTableRows();
    scheduleAdminResponsiveEnhance();

    const content = document.getElementById('content');
    if (content && !content.dataset.adminResponsiveObserver) {
        content.dataset.adminResponsiveObserver = '1';
        const observer = new MutationObserver(() => scheduleAdminResponsiveEnhance());
        observer.observe(content, { childList: true, subtree: true });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAdminResponsive);
} else {
    initAdminResponsive();
}

window.addEventListener('resize', () => {
    document.querySelectorAll('#content table.kt-table[data-admin-cards-enhanced="1"]').forEach((table) => {
        syncMobileCardsVisibility(table);
    });
});
