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
    const explicit = tr.getAttribute('data-row-href') || tr.dataset.rowHref;
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
    bewerken: 'ki-pencil',
    verwijderen: 'ki-trash',
    dupliceren: 'ki-copy',
    archiveren: 'ki-archive',
    activeren: 'ki-check-circle',
    deactiveren: 'ki-cross-circle',
    downloaden: 'ki-file-down',
    preview: 'ki-eye',
    voorbeeld: 'ki-eye',
};

function stopCardNavigation(el) {
    el.addEventListener('click', (e) => e.stopPropagation());
    el.addEventListener('keydown', (e) => e.stopPropagation());
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
    const key = label.toLowerCase();
    const ki = MENU_ACTION_ICON_BY_TITLE[key] || 'ki-more-2';
    return `ki-filled ${ki}`;
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

/** Zet kt-menu dropdown-acties om naar klikbare icoonknoppen (mobiele kaarten). */
function buildCardActionIcons(actionsTd) {
    const toolbar = document.createElement('div');
    toolbar.className = 'admin-list-card__action-icons';

    actionsTd.querySelectorAll('.kt-menu-dropdown .kt-menu-item, .website-pages-actions-dropdown .kt-menu-item').forEach((item) => {
        if (item.classList.contains('kt-menu-separator')) {
            return;
        }

        const form = item.querySelector(':scope > form');
        if (form) {
            const submitBtn = form.querySelector('button[type="submit"], button.kt-menu-link');
            if (submitBtn) {
                const iconBtn = buildIconButtonFromMenuLink(submitBtn);
                if (iconBtn) {
                    toolbar.appendChild(iconBtn);
                }
            }
            return;
        }

        const link = item.querySelector('a.kt-menu-link, button.kt-menu-link');
        if (link) {
            const iconBtn = buildIconButtonFromMenuLink(link);
            if (iconBtn) {
                toolbar.appendChild(iconBtn);
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
                const iconBtn = buildIconButtonFromMenuLink(submitBtn);
                if (iconBtn) {
                    toolbar.appendChild(iconBtn);
                }
            }
            return;
        }
        if (el.classList.contains('kt-menu-toggle')) {
            return;
        }
        const clone = el.cloneNode(true);
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

        const label = labels[index] || td.getAttribute('data-label') || `Veld ${index + 1}`;
        if (!label || label.toLowerCase() === 'acties') {
            return;
        }

        const valueHtml = cellDisplayHtml(td);
        const plain = stripHtmlToText(valueHtml);

        if (!titleSet && plain.length > 0) {
            const title = document.createElement('div');
            title.className = 'admin-list-card__title';
            title.innerHTML = valueHtml;
            body.appendChild(title);
            titleSet = true;
            if (index === 0) {
                return;
            }
        }

        const field = document.createElement('div');
        field.className = 'admin-list-card__field';
        const dt = document.createElement('dt');
        dt.textContent = label;
        const dd = document.createElement('dd');
        dd.innerHTML = valueHtml;
        field.appendChild(dt);
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

        const iconToolbar = buildCardActionIcons(actionsTd);
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

function isListContextTable(table) {
    if (table.dataset.adminNoCards === 'true' || table.classList.contains('admin-keep-table-layout')) {
        return false;
    }
    if (table.closest('form:not([method="GET"])')) {
        return false;
    }
    const scrollWrap = table.closest('.kt-scrollable-x-auto, .kt-card-table');
    if (!scrollWrap) {
        return false;
    }
    if (scrollWrap.closest('.admin-mobile-list')) {
        return false;
    }
    return true;
}

function enhanceListTables() {
    const root = document.getElementById('content');
    if (!root) {
        return;
    }

    root.querySelectorAll('.kt-scrollable-x-auto table.kt-table, .kt-card-table table.kt-table').forEach((table) => {
        if (table.dataset.adminCardsEnhanced === '1' || !isListContextTable(table)) {
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

function enhanceFilterPanels() {
    document.querySelectorAll('.kt-card-header').forEach((header) => {
        if (header.dataset.adminFiltersEnhanced === '1') {
            return;
        }

        const hasFilters =
            header.querySelector('#filters-form, #search-form') ||
            header.querySelector('form[method="GET"]');
        if (!hasFilters) {
            return;
        }

        const filterRow = header.querySelector(
            '.flex.flex-col.sm\\:flex-row, .flex.flex-wrap.gap-2, .flex.gap-2.flex-wrap'
        );
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
        });

        header.insertBefore(toggle, filterRow);

        filterRow.querySelectorAll('select, input').forEach((el) => {
            el.addEventListener('change', updateToggleLabel);
            el.addEventListener('input', updateToggleLabel);
        });

        if (countActiveFilters(filterRow) > 0) {
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

function markPageActionBars() {
    document.querySelectorAll('#content .kt-container-fixed > .flex.flex-wrap').forEach((bar) => {
        if (!bar.querySelector('h1')) {
            return;
        }
        const targets = bar.querySelectorAll(
            '.justify-end, [data-company-create-actions], .shrink-0.flex.flex-wrap, .flex.flex-wrap.items-center.gap-2.shrink-0'
        );
        targets.forEach((actions) => {
            if (!actions.classList.contains('admin-page-actions')) {
                actions.classList.add('admin-page-actions');
            }
        });
    });
}

export function initAdminResponsive() {
    markPageActionBars();
    enhanceFilterPanels();
    enhanceListTables();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAdminResponsive);
} else {
    initAdminResponsive();
}
