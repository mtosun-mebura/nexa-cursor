const FILTER_DELAY_MS = 120;
const PAGE_MORE_LIMIT = 5;
const DEFAULT_PAGE_SIZE_OPTIONS = [5, 10, 25, 50, 100];
const PAGE_QUERY = 'page';
const PERPAGE_QUERY = 'perpage';

export function adminDatatableQueryKeys(root, documentRoot = document) {
    const tables = documentRoot.querySelectorAll('[data-admin-datatable="true"]');
    const usePrefixed = tables.length > 1 && Boolean(root?.id);
    const prefix = usePrefixed ? `${root.id}_` : '';

    return {
        page: `${prefix}${PAGE_QUERY}`,
        perPage: `${prefix}${PERPAGE_QUERY}`,
    };
}

export function readPositiveIntQueryParam(name, search = window.location.search) {
    const raw = new URLSearchParams(search).get(name);
    const value = Number(raw);

    return Number.isInteger(value) && value > 0 ? value : null;
}

export function replaceAdminDatatableQueryParams(updates, locationLike = window.location, historyLike = window.history) {
    const params = new URLSearchParams(locationLike.search);
    let changed = false;

    Object.entries(updates).forEach(([key, value]) => {
        const current = params.get(key);
        if (value === null || value === undefined || value === '') {
            if (current !== null) {
                params.delete(key);
                changed = true;
            }

            return;
        }

        const next = String(value);
        if (current !== next) {
            params.set(key, next);
            changed = true;
        }
    });

    if (!changed) {
        return;
    }

    const qs = params.toString();
    const url = `${locationLike.pathname}${qs ? `?${qs}` : ''}${locationLike.hash || ''}`;
    historyLike.replaceState(historyLike.state, '', url);
}

function ensureAdminDatatableSizeSelectOptions(select, pageSize) {
    if (!select) {
        return;
    }

    if (select.options.length === 0) {
        DEFAULT_PAGE_SIZE_OPTIONS.forEach((size) => {
            const option = document.createElement('option');
            option.value = String(size);
            option.textContent = String(size);
            select.appendChild(option);
        });
    }

    const resolvedSize = DEFAULT_PAGE_SIZE_OPTIONS.includes(pageSize)
        ? pageSize
        : (Number(select.value) || DEFAULT_PAGE_SIZE_OPTIONS[0]);

    select.value = String(resolvedSize);
    refreshAdminDatatableSizeSelect(select);
}

function syncAdminDatatableKtSelectDisplay(select) {
    const wrapper = select.closest('.kt-select-wrapper')
        || select.parentElement?.querySelector?.('.kt-select-wrapper')
        || select.parentElement;
    const display = wrapper?.querySelector('[data-kt-select-display]');
    if (!display) {
        return;
    }

    const selected = select.options[select.selectedIndex];
    if (!selected) {
        return;
    }

    display.textContent = selected.textContent.trim();
    display.setAttribute('data-selected', selected.value);
    display.removeAttribute('aria-placeholder');
    display.setAttribute('aria-label', `Toon ${selected.textContent.trim()} per pagina`);
}

function refreshAdminDatatableSizeSelect(select) {
    if (!select) {
        return;
    }

    syncAdminDatatableKtSelectDisplay(select);

    if (typeof window.KTSelect === 'undefined') {
        return;
    }

    try {
        const instance = window.KTSelect.getInstance(select);
        if (instance) {
            if (typeof instance.update === 'function') {
                instance.update();
            } else if (typeof instance.setValue === 'function') {
                instance.setValue(select.value);
            } else if (typeof instance.destroy === 'function') {
                instance.destroy();
                window.KTSelect.init(select);
            }
        } else if (typeof window.KTSelect.init === 'function') {
            window.KTSelect.init(select);
        }
    } catch (error) {
        syncAdminDatatableKtSelectDisplay(select);
    }

    syncAdminDatatableKtSelectDisplay(select);
}

export function normalizeAdminDatatableSearchValue(value) {
    return String(value || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/\p{M}/gu, '')
        .replace(/\s+/g, ' ')
        .trim();
}

function initAdminDatatableMenus() {
    if (!window.KTMenu) {
        return;
    }

    document.querySelectorAll('[data-admin-datatable="true"]').forEach((datatableRoot) => {
        datatableRoot.querySelectorAll('[data-kt-menu]:not([data-kt-menu="false"])').forEach((menuEl) => {
            if (menuEl.id === 'sidebar_menu' || menuEl.closest('#sidebar_menu') || menuEl.closest('table')) {
                return;
            }

            try {
                const existing = window.KTMenu.getInstance(menuEl);
                if (existing && typeof existing.destroy === 'function') {
                    existing.destroy();
                }

                new window.KTMenu(menuEl);
            } catch (error) {
                console.warn('KTMenu datatable init error:', error);
            }
        });
    });
}

export class AdminClientDatatable {
    constructor(root) {
        this.root = root;
        this.table = root.querySelector('table');
        this.tbody = this.table?.querySelector('tbody');
        this.card = root.closest('.kt-card');
        this.searchInput = this.findSearchInput();
        this.clientFilters = this.findClientFilters();
        this.resetBtn = this.findResetButton();
        this.paginationEl = root.querySelector('[data-admin-datatable-pagination]');
        this.infoEls = this.card?.querySelectorAll('[data-admin-datatable-info]')
            || root.querySelectorAll('[data-admin-datatable-info]');
        this.sizeSelect = root.querySelector('[data-admin-datatable-size]');
        this.itemLabel = root.dataset.adminDatatableLabel || 'items';
        this.queryKeys = adminDatatableQueryKeys(root);
        this.defaultPageSize = Number(root.dataset.adminDatatablePageSize || this.sizeSelect?.value) || 10;
        this.pageSize = this.defaultPageSize;
        const storedPageSize = readPositiveIntQueryParam(this.queryKeys.perPage);
        if (storedPageSize && DEFAULT_PAGE_SIZE_OPTIONS.includes(storedPageSize)) {
            this.pageSize = storedPageSize;
        }
        ensureAdminDatatableSizeSelectOptions(this.sizeSelect, this.pageSize);
        this.pageSize = Number(this.sizeSelect?.value) || this.pageSize;
        this.allRows = [];
        this.filteredRows = [];
        this.initialPage = readPositiveIntQueryParam(this.queryKeys.page) || 1;
        this.page = this.initialPage;
        this.restorePageOnce = true;
        this.filterTimer = null;
        this.lastTotalPages = null;
        this.columnWidthsLocked = false;
        this.afterPageRender = typeof window[root.dataset.adminDatatableOnPage] === 'function'
            ? window[root.dataset.adminDatatableOnPage]
            : null;
    }

    findSearchInput() {
        const tableId = this.root.id;
        if (tableId) {
            const linked = document.querySelector(
                `[data-admin-datatable-search="#${CSS.escape(tableId)}"]`
            );
            if (linked) {
                return linked;
            }
        }

        return this.card?.querySelector('input[name="search"]')
            || this.card?.querySelector('#search-input')
            || null;
    }

    findClientFilters() {
        const scoped = this.card?.querySelectorAll('[data-admin-datatable-filter]');
        if (scoped?.length) {
            return Array.from(scoped);
        }

        return [];
    }

    findResetButton() {
        return this.card?.querySelector('[data-admin-datatable-reset]')
            || this.card?.querySelector('#knowledge-filter-reset')
            || null;
    }

    init() {
        if (!this.tbody) {
            return;
        }

        ensureAdminDatatableSizeSelectOptions(this.sizeSelect, this.pageSize);
        this.pageSize = Number(this.sizeSelect?.value) || this.pageSize;
        setTimeout(() => refreshAdminDatatableSizeSelect(this.sizeSelect), 0);
        setTimeout(() => refreshAdminDatatableSizeSelect(this.sizeSelect), 150);

        this.allRows = Array.from(this.tbody.querySelectorAll(':scope > tr'))
            .filter((row) => !row.querySelector('td[colspan]'))
            .map((row) => ({
                row,
                searchText: normalizeAdminDatatableSearchValue(
                    row.getAttribute('data-search-text') || row.textContent
                ),
                filters: this.readRowFilters(row),
            }));

        this.searchInput?.addEventListener('input', () => {
            this.scheduleFilter();
            this.updateResetButton();
        });

        this.searchInput?.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
            }
        });

        this.clientFilters.forEach((select) => {
            select.addEventListener('change', () => {
                this.applyFilter();
                this.updateResetButton();
            });
        });

        this.sizeSelect?.addEventListener('change', () => {
            this.pageSize = Number(this.sizeSelect.value) || this.pageSize;
            this.page = 1;
            syncAdminDatatableKtSelectDisplay(this.sizeSelect);
            this.renderPage();
        });

        this.resetBtn?.addEventListener('click', (event) => {
            if (this.resetBtn.tagName === 'BUTTON') {
                event.preventDefault();
            }

            if (this.searchInput) {
                this.searchInput.value = '';
            }

            this.clientFilters.forEach((select) => {
                select.value = '';
            });

            this.applyFilter();
            this.updateResetButton();
        });

        this.root.__adminDatatable = this;
        this.lockColumnWidths();
        this.applyFilter();
        if (!this.columnWidthsLocked) {
            requestAnimationFrame(() => {
                this.lockColumnWidths();
                if (this.columnWidthsLocked) {
                    this.renderPage();
                }
            });
        }
        setTimeout(initAdminDatatableMenus, 200);
    }

    lockColumnWidths() {
        if (!this.table || this.columnWidthsLocked) {
            return;
        }

        if (this.table.classList.contains('admin-fluid-table')) {
            this.table.classList.add('admin-datatable-cols-locked');
            this.columnWidthsLocked = true;
            return;
        }

        const headerCells = this.table.tHead?.rows?.[0]
            ? Array.from(this.table.tHead.rows[0].cells)
            : [];
        if (headerCells.length === 0) {
            return;
        }

        this.allRows.forEach(({ row }) => {
            row.hidden = false;
        });

        this.table.classList.remove('table-auto');
        this.table.style.tableLayout = 'auto';
        this.table.style.width = '';

        const tableWidth = this.table.getBoundingClientRect().width;
        const widths = headerCells.map((cell) => cell.getBoundingClientRect().width);
        if (tableWidth <= 0 || widths.some((width) => width <= 0)) {
            return;
        }

        let colgroup = this.table.querySelector(':scope > colgroup[data-admin-datatable-cols]');
        if (!colgroup) {
            colgroup = document.createElement('colgroup');
            colgroup.setAttribute('data-admin-datatable-cols', '');
            this.table.insertBefore(colgroup, this.table.firstChild);
        }

        colgroup.replaceChildren(
            ...widths.map((width) => {
                const col = document.createElement('col');
                col.style.width = `${(width / tableWidth) * 100}%`;

                return col;
            })
        );

        this.table.style.tableLayout = 'fixed';
        this.table.style.width = '100%';
        this.table.classList.add('admin-datatable-cols-locked');
        this.columnWidthsLocked = true;
    }

    reloadRows() {
        const previousPage = this.page;
        this.table = this.root.querySelector('table');
        this.tbody = this.table?.querySelector('tbody');
        if (!this.tbody) {
            return;
        }

        this.allRows = Array.from(this.tbody.querySelectorAll(':scope > tr'))
            .filter((row) => !row.querySelector('td[colspan]'))
            .map((row) => ({
                row,
                searchText: normalizeAdminDatatableSearchValue(
                    row.getAttribute('data-search-text') || row.textContent
                ),
                filters: this.readRowFilters(row),
            }));

        this.lastTotalPages = null;
        this.columnWidthsLocked = false;
        this.lockColumnWidths();
        this.applyFilter();
        this.goToPage(previousPage);
        this.updateResetButton();
    }

    readRowFilters(row) {
        const filters = {};

        this.clientFilters.forEach((select) => {
            const key = select.dataset.adminDatatableFilter;
            if (!key) {
                return;
            }

            filters[key] = row.dataset[key] || '';
        });

        return filters;
    }

    scheduleFilter() {
        if (this.filterTimer) {
            clearTimeout(this.filterTimer);
        }

        this.filterTimer = setTimeout(() => {
            this.filterTimer = null;
            this.applyFilter();
        }, FILTER_DELAY_MS);
    }

    applyFilter() {
        const query = normalizeAdminDatatableSearchValue(this.searchInput?.value);
        const activeFilters = this.clientFilters.map((select) => ({
            key: select.dataset.adminDatatableFilter || '',
            value: select.value || '',
        })).filter((filter) => filter.key);

        this.filteredRows = this.allRows.filter(({ searchText, filters }) => {
            for (const { key, value } of activeFilters) {
                if (value && filters[key] !== value) {
                    return false;
                }
            }

            if (query && !searchText.includes(query)) {
                return false;
            }

            return true;
        });

        if (this.restorePageOnce) {
            this.restorePageOnce = false;
            this.page = this.initialPage;
        } else {
            this.page = 1;
        }
        this.renderPage();
    }

    renderPage() {
        const total = this.filteredRows.length;
        const totalPages = Math.max(1, Math.ceil(total / this.pageSize));

        if (this.page > totalPages) {
            this.page = totalPages;
        }

        this.syncQueryState();

        const start = (this.page - 1) * this.pageSize;
        const end = start + this.pageSize;
        const visibleRows = new Set(
            this.filteredRows.slice(start, end).map((entry) => entry.row)
        );

        this.allRows.forEach(({ row }) => {
            row.hidden = !visibleRows.has(row);
        });

        this.renderInfo(total, start, end);
        this.renderPagination(totalPages);

        if (this.afterPageRender) {
            this.afterPageRender(this);
        } else {
            setTimeout(initAdminDatatableMenus, 50);
        }

        this.root.dispatchEvent(new CustomEvent('admin-datatable:rendered', {
            bubbles: true,
            detail: { total, page: this.page, pageSize: this.pageSize },
        }));
    }

    renderInfo(total, start, end) {
        const from = total === 0 ? 0 : start + 1;
        const to = total === 0 ? 0 : Math.min(end, total);
        const text = `Toon ${from} tot ${to} van ${total} ${this.itemLabel}`;

        this.infoEls.forEach((el) => {
            el.textContent = text;
        });
    }

    calculatePageRange(currentPage, totalPages) {
        const maxButtons = PAGE_MORE_LIMIT;
        let start = Math.max(1, currentPage - Math.floor(maxButtons / 2));
        let end = start + maxButtons - 1;

        if (end > totalPages) {
            end = totalPages;
            start = Math.max(1, end - maxButtons + 1);
        }

        return { start, end };
    }

    renderPagination(totalPages) {
        if (!this.paginationEl) {
            return;
        }

        if (totalPages === this.lastTotalPages && this.paginationEl.childElementCount > 0) {
            this.paginationEl.querySelectorAll('button').forEach((button) => {
                const page = Number(button.dataset.page || 0);
                const isNav = button.dataset.nav === '1';
                button.disabled = isNav
                    ? (button.dataset.navType === 'prev' && this.page === 1)
                    || (button.dataset.navType === 'next' && this.page === totalPages)
                    : page === this.page;
                button.classList.toggle('active', !isNav && page === this.page);
                button.classList.toggle('disabled', button.disabled);
            });
            return;
        }

        this.lastTotalPages = totalPages;
        this.paginationEl.textContent = '';

        const createButton = (label, className, disabled, onClick, dataset = {}) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = className;
            button.textContent = label;
            button.disabled = disabled;
            Object.entries(dataset).forEach(([key, value]) => {
                button.dataset[key] = String(value);
            });
            button.addEventListener('click', onClick);
            return button;
        };

        this.paginationEl.appendChild(
            createButton(
                'Vorige',
                `kt-datatable-pagination-button kt-datatable-pagination-prev${this.page === 1 ? ' disabled' : ''}`,
                this.page === 1,
                () => this.goToPage(this.page - 1),
                { nav: '1', navType: 'prev' }
            )
        );

        if (totalPages <= PAGE_MORE_LIMIT + 2) {
            for (let page = 1; page <= totalPages; page += 1) {
                this.paginationEl.appendChild(this.createPageButton(page));
            }
        } else {
            const range = this.calculatePageRange(this.page, totalPages);

            if (range.start > 1) {
                this.paginationEl.appendChild(this.createPageButton(1));
                if (range.start > 2) {
                    this.paginationEl.appendChild(
                        createButton('…', 'kt-datatable-pagination-button', false, () => this.goToPage(range.start - 1))
                    );
                }
            }

            for (let page = range.start; page <= range.end; page += 1) {
                this.paginationEl.appendChild(this.createPageButton(page));
            }

            if (range.end < totalPages) {
                if (range.end < totalPages - 1) {
                    this.paginationEl.appendChild(
                        createButton('…', 'kt-datatable-pagination-button', false, () => this.goToPage(range.end + 1))
                    );
                }
                this.paginationEl.appendChild(this.createPageButton(totalPages));
            }
        }

        this.paginationEl.appendChild(
            createButton(
                'Volgende',
                `kt-datatable-pagination-button kt-datatable-pagination-next${this.page === totalPages ? ' disabled' : ''}`,
                this.page === totalPages,
                () => this.goToPage(this.page + 1),
                { nav: '1', navType: 'next' }
            )
        );
    }

    createPageButton(page) {
        const button = document.createElement('button');
        button.type = 'button';
        button.dataset.page = String(page);
        button.className = `kt-datatable-pagination-button${page === this.page ? ' active disabled' : ''}`;
        button.textContent = String(page);
        button.disabled = page === this.page;
        button.addEventListener('click', () => this.goToPage(page));
        return button;
    }

    goToPage(page) {
        const totalPages = Math.max(1, Math.ceil(this.filteredRows.length / this.pageSize));
        if (page < 1 || page > totalPages) {
            return;
        }

        this.page = page;
        this.renderPage();
    }

    syncQueryState() {
        replaceAdminDatatableQueryParams({
            [this.queryKeys.page]: this.page > 1 ? this.page : null,
            [this.queryKeys.perPage]: this.pageSize !== this.defaultPageSize ? this.pageSize : null,
        });
    }

    updateResetButton() {
        if (!this.resetBtn || this.resetBtn.tagName === 'A') {
            return;
        }

        const hasSearch = (this.searchInput?.value?.trim() || '') !== '';
        const hasClientFilters = this.clientFilters.some((select) => (select.value || '') !== '');
        this.resetBtn.classList.toggle('hidden', !(hasSearch || hasClientFilters));
    }
}

export function initAdminClientDatatables(root = document) {
    root.querySelectorAll('[data-admin-datatable="true"]').forEach((element) => {
        if (element.dataset.adminDatatableInit === '1') {
            return;
        }

        element.dataset.adminDatatableInit = '1';
        const datatable = new AdminClientDatatable(element);
        datatable.init();
        element.__adminDatatable = datatable;
    });
}

if (typeof window !== 'undefined') {
    window.initAdminClientDatatables = initAdminClientDatatables;
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initAdminClientDatatables());
} else {
    initAdminClientDatatables();
}
