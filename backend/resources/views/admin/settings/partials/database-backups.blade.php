{{-- Database backups (Configuraties → Systeem configuraties) --}}
<div class="kt-card min-w-full settings-collapsible-card settings-collapsible-card--collapsed mb-8" id="database-backups">
    @include('admin.settings.partials.collapsible-header', ['titleHtml' => '<i class="ki-filled ki-shield-tick me-2"></i> Database backups'])
    <div class="settings-collapsible-body">
        <div class="kt-card-content px-3 sm:px-6 pb-4 pt-3 sm:pt-4 space-y-6">
            <p class="settings-section-intro">
                Maak periodieke backups van de applicatiedatabase. Optioneel kopieer je dumps naar een doel-omgeving uit Omgeving-sync (SSH).
                Oude backups worden automatisch verwijderd na de ingestelde bewaartermijn.
            </p>

            <form method="POST" action="{{ route('admin.settings.database-backups.update') }}" id="database-backup-settings-form" class="space-y-4">
                @csrf
                <div class="flex items-center justify-between gap-3 rounded-md border border-border p-4">
                    <div>
                        <div class="text-sm font-medium text-foreground">Automatische backups</div>
                        <div class="text-xs text-muted-foreground mt-0.5">Scheduler voert backups uit op het ingestelde tijdstip.</div>
                    </div>
                    <label class="kt-label gap-2 mb-0">
                        <input type="hidden" name="database_backup_enabled" value="0">
                        <input type="checkbox" class="kt-switch kt-switch-sm" name="database_backup_enabled" value="1"
                               @checked(old('database_backup_enabled', $databaseBackupSettings['database_backup_enabled'] ?? false))>
                    </label>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label for="database_backup_frequency" class="text-sm text-secondary-foreground block mb-1">Frequentie</label>
                        <select name="database_backup_frequency" id="database_backup_frequency" class="kt-select w-full text-sm">
                            <option value="daily" @selected(old('database_backup_frequency', $databaseBackupSettings['database_backup_frequency'] ?? 'daily') === 'daily')>Dagelijks</option>
                            <option value="weekly" @selected(old('database_backup_frequency', $databaseBackupSettings['database_backup_frequency'] ?? 'daily') === 'weekly')>Wekelijks (maandag)</option>
                        </select>
                        @error('database_backup_frequency')
                            <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <label for="database_backup_time" class="text-sm text-secondary-foreground block mb-1">Tijdstip (Nederlandse tijd)</label>
                        <input type="time" name="database_backup_time" id="database_backup_time" class="kt-input w-full text-sm" step="60"
                               value="{{ old('database_backup_time', $databaseBackupSettings['database_backup_time'] ?? '03:00') }}" required>
                        <div class="text-xs text-muted-foreground mt-1">
                            {{ $databaseBackupSettings['database_backup_next_run_hint'] ?? 'Europe/Amsterdam. De planner controleert elke minuut.' }}
                        </div>
                        @error('database_backup_time')
                            <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <label for="database_backup_retention_days" class="text-sm text-secondary-foreground block mb-1">Bewaren (dagen)</label>
                        <input type="number" name="database_backup_retention_days" id="database_backup_retention_days" class="kt-input w-full text-sm"
                               min="1" max="3650" required
                               value="{{ old('database_backup_retention_days', $databaseBackupSettings['database_backup_retention_days'] ?? 30) }}">
                        <div class="text-xs text-muted-foreground mt-1">Backups ouder dan dit aantal dagen worden verwijderd.</div>
                        @error('database_backup_retention_days')
                            <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="database_backup_sync_target_id" class="text-sm text-secondary-foreground block mb-1">Kopie naar sync-doel (optioneel)</label>
                    <select name="database_backup_sync_target_id" id="database_backup_sync_target_id" class="kt-select w-full text-sm max-w-xl">
                        <option value="0">— Alleen lokaal opslaan —</option>
                        @foreach(($tenantSyncTargets ?? collect()) as $target)
                            <option value="{{ $target->id }}"
                                @selected((int) old('database_backup_sync_target_id', $databaseBackupSettings['database_backup_sync_target_id'] ?? 0) === (int) $target->id)>
                                {{ $target->name }}{{ $target->ssh_enabled ? ' · SSH' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <div class="text-xs text-muted-foreground mt-1">Gebruikt dezelfde doel-omgevingen als Omgeving-sync. Offsite-kopie vereist SSH.</div>
                </div>

                <div class="flex flex-wrap gap-2 pt-1">
                    <button type="submit" class="kt-btn kt-btn-primary">
                        <i class="ki-filled ki-check me-2"></i> Instellingen opslaan
                    </button>
                </div>
            </form>

            <form method="POST" action="{{ route('admin.settings.database-backups.run') }}" id="database-backup-run-form" class="m-0 mb-4" data-no-cmd-s>
                @csrf
                <button type="submit" class="kt-btn kt-btn-outline" id="database-backup-run-btn">
                    <i class="ki-filled ki-cloud-add me-2"></i> Nu backup maken
                </button>
            </form>

            <div class="rounded-md border border-border overflow-hidden min-w-0" id="database-backups-list">
                <div class="px-3 sm:px-4 py-3 border-b border-border bg-muted/20 flex flex-col gap-3">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h4 class="text-sm font-medium text-foreground mb-0">Beschikbare backups</h4>
                        <div class="flex items-center gap-2 shrink-0">
                            <button type="button"
                                    id="database-backups-bulk-delete"
                                    class="kt-btn kt-btn-sm kt-btn-outline shrink-0 hidden text-destructive"
                                    data-url="{{ route('admin.settings.database-backups.bulk-delete') }}"
                                    aria-label="Geselecteerde backups verwijderen"
                                    title="Geselecteerde backups verwijderen"
                                    disabled>
                                <i class="ki-filled ki-trash" aria-hidden="true"></i>
                                <span class="ms-1.5 hidden sm:inline">Verwijderen</span>
                                <span id="database-backups-bulk-count" class="ms-1 tabular-nums"></span>
                            </button>
                            <button type="button"
                                    id="database-backups-refresh"
                                    class="kt-btn kt-btn-sm kt-btn-outline shrink-0"
                                    data-url="{{ route('admin.settings.database-backups.table') }}"
                                    aria-label="Lijst vernieuwen"
                                    title="Lijst vernieuwen">
                                <i class="ki-filled ki-arrows-circle" aria-hidden="true"></i>
                                <span class="ms-1.5 hidden sm:inline">Vernieuwen</span>
                            </button>
                        </div>
                    </div>
                    <div class="admin-filter-panel flex flex-col sm:flex-row flex-wrap gap-2 w-full min-w-0 items-stretch sm:items-center sm:justify-end"
                         data-admin-live-filter="off">
                        <label class="kt-input w-full sm:w-56 min-w-0">
                            <i class="ki-filled ki-magnifier"></i>
                            <input placeholder="Zoek bestand, database, datum…"
                                   type="text"
                                   name="search"
                                   autocomplete="off"
                                   data-admin-datatable-search="#database_backups_table">
                        </label>
                        <select class="kt-select w-full sm:w-40"
                                name="status"
                                data-admin-datatable-filter="status">
                            <option value="">Alle statussen</option>
                            <option value="completed">Gereed</option>
                            <option value="pending">Bezig</option>
                            <option value="failed">Mislukt</option>
                        </select>
                        <select class="kt-select w-full sm:w-40"
                                name="trigger"
                                data-admin-datatable-filter="trigger">
                            <option value="">Alle bronnen</option>
                            <option value="scheduled">Gepland</option>
                            <option value="manual">Handmatig</option>
                        </select>
                        <button type="button"
                                data-admin-datatable-reset
                                class="kt-btn kt-btn-outline kt-btn-icon shrink-0 hidden"
                                title="Filters resetten">
                            <i class="ki-filled ki-arrows-circle text-base"></i>
                        </button>
                    </div>
                </div>
                <div class="grid w-full min-w-0"
                     data-admin-datatable="true"
                     data-admin-datatable-page-size="5"
                     id="database_backups_table"
                     data-admin-datatable-label="backups"
                     data-admin-datatable-on-page="initDatabaseBackupsTablePage">
                    @include('admin.settings.partials.database-backups-table')
                    <div class="admin-datatable-footer text-secondary-foreground text-sm font-medium px-3 sm:px-4 py-3 min-w-0">
                        <div class="admin-datatable-footer__perpage flex flex-wrap items-center gap-2">
                            Toon
                            <select class="kt-select w-24" data-admin-datatable-size="true" name="perpage">
                                <option value="5" selected>5</option>
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            per pagina
                        </div>
                        <div class="admin-datatable-footer__pagination max-w-full overflow-x-auto">
                            <div class="kt-datatable-pagination" data-admin-datatable-pagination="true"></div>
                        </div>
                        <span class="admin-datatable-footer__info" data-admin-datatable-info="true"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    #database-backups-list,
    #database-backups-table,
    #database_backups_table {
        width: 100%;
        min-width: 0;
        max-width: 100%;
    }

    #content #database-backups-table .database-backups-table {
        table-layout: fixed;
    }

    #content #database-backups-list .database-backups-status {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.2rem;
    }

    #content #database-backups-list .database-backups-status .kt-badge {
        display: inline-flex;
    }

    #content #database-backups-list .database-backups-status__trigger {
        display: block;
        width: 100%;
        font-size: 0.75rem;
        line-height: 1.2;
        color: var(--muted-foreground);
    }

    #content #database-backups-table col.database-backups-col-select,
    #content #database-backups-table th.database-backups-col-select,
    #content #database-backups-table td.database-backups-col-select {
        width: 2.75rem;
        min-width: 2.75rem;
        max-width: 2.75rem;
        padding-inline: 0.375rem !important;
    }

    #content #database-backups-table col.database-backups-col-db {
        width: 12%;
    }

    #content #database-backups-table col.database-backups-col-size {
        width: 6.75rem;
    }

    #content #database-backups-table col.database-backups-col-status {
        width: 6.5rem;
    }

    #content #database-backups-table col.database-backups-col-date {
        width: 9rem;
    }

    #content #database-backups-table td.database-backups-col-file {
        overflow-wrap: anywhere;
        word-break: break-word;
        vertical-align: middle;
    }

    #content #database-backups-table td.database-backups-col-db {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        vertical-align: middle;
    }

    #content #database-backups-table td.database-backups-col-size,
    #content #database-backups-table td.database-backups-col-date {
        word-break: normal !important;
        overflow-wrap: normal !important;
        vertical-align: middle;
    }

    #content #database-backups-table td.database-backups-col-status {
        overflow-wrap: normal !important;
        word-break: normal !important;
        vertical-align: middle;
        white-space: normal;
    }

    #database-backups-list > .admin-mobile-list {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }

    #database-backups-list .admin-datatable-footer {
        border-top: 1px solid var(--border);
    }
</style>
<script>
(function () {
    const card = document.getElementById('database-backups');
    const runForm = document.getElementById('database-backup-run-form');
    const runBtn = document.getElementById('database-backup-run-btn');
    const refreshBtn = document.getElementById('database-backups-refresh');
    const bulkDeleteBtn = document.getElementById('database-backups-bulk-delete');
    const bulkCountEl = document.getElementById('database-backups-bulk-count');
    const list = document.getElementById('database-backups-list');
    if (!card || !refreshBtn) {
        return;
    }

    let refreshInFlight = false;
    let refreshQueued = null;
    let pollTimer = null;
    let fastPollUntil = 0;
    const pollMs = 15000;
    const pendingPollMs = 2500;

    function csrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function reloadBackupDatatable() {
        const root = document.getElementById('database_backups_table');
        if (!root) {
            return;
        }
        const dt = root.__adminDatatable;
        if (dt && typeof dt.reloadRows === 'function') {
            dt.reloadRows();
            return;
        }
        if (dt && dt.tbody) {
            dt.table = root.querySelector('table');
            dt.tbody = dt.table ? dt.table.querySelector('tbody') : null;
            if (!dt.tbody) {
                return;
            }
            dt.allRows = Array.prototype.slice.call(dt.tbody.querySelectorAll(':scope > tr')).filter(function (row) {
                return !row.querySelector('td[colspan]');
            }).map(function (row) {
                return {
                    row: row,
                    searchText: (row.getAttribute('data-search-text') || row.textContent || '').toLowerCase(),
                    filters: {
                        status: row.getAttribute('data-status') || '',
                        trigger: row.getAttribute('data-trigger') || '',
                    },
                };
            });
            dt.lastTotalPages = null;
            if (typeof dt.applyFilter === 'function') {
                dt.applyFilter();
            }
            return;
        }
        if (typeof window.initAdminClientDatatables === 'function') {
            delete root.dataset.adminDatatableInit;
            window.initAdminClientDatatables(root.parentNode || document);
        }
    }

    window.initDatabaseBackupsTablePage = function (dt) {
        const root = document.getElementById('database_backups_table');
        if (root && dt) {
            root.__adminDatatable = dt;
        }
        const empty = dt && dt.tbody ? dt.tbody.querySelector('.database-backups-filter-empty') : document.querySelector('#database-backups-table .database-backups-filter-empty');
        if (empty) {
            const total = dt && dt.filteredRows ? dt.filteredRows.length : 0;
            const hasRows = !!(dt && dt.allRows && dt.allRows.length);
            empty.hidden = !hasRows || total > 0;
        }
        initBackupMenus();
        syncBulkUi();
    };

    function initBackupMenus() {
        if (window.KTMenu && typeof window.KTMenu.init === 'function') {
            try {
                window.KTMenu.init();
            } catch (e) {}
        }
    }

    function tableSignatureFromRoot(root) {
        if (!root) {
            return '';
        }
        const rows = root.querySelectorAll('tbody tr');
        const parts = [];
        rows.forEach(function (tr) {
            parts.push([
                tr.getAttribute('data-filename') || '',
                tr.getAttribute('data-status') || '',
                tr.getAttribute('data-size') || '',
            ].join(':'));
        });
        return String(rows.length) + '|' + parts.join('|');
    }

    function currentTableSignature() {
        return tableSignatureFromRoot(document.getElementById('database-backups-table'));
    }

    function tableHasPending() {
        const table = document.getElementById('database-backups-table');
        return !!(table && table.querySelector('tr[data-status="pending"]'));
    }

    function selectableCheckboxes() {
        return Array.prototype.slice.call(document.querySelectorAll('#database-backups-table .database-backup-checkbox:not(:disabled)')).filter(function (cb) {
            const row = cb.closest('tr');
            return row && !row.hidden;
        });
    }

    function selectedBackupIds() {
        return Array.prototype.slice.call(document.querySelectorAll('#database-backups-table .database-backup-checkbox:checked:not(:disabled)')).map(function (cb) {
            return cb.value;
        });
    }

    function restoreSelection(ids) {
        (ids || []).forEach(function (id) {
            const cb = document.querySelector('#database-backups-table .database-backup-checkbox[value="' + id + '"]');
            if (cb && !cb.disabled) {
                cb.checked = true;
            }
        });
        syncBulkUi();
    }

    function syncBulkUi() {
        const selectable = selectableCheckboxes();
        const selected = selectable.filter(function (cb) {
            return cb.checked;
        });
        const selectAll = document.getElementById('database-backups-select-all');
        if (selectAll) {
            selectAll.disabled = selectable.length === 0;
            selectAll.checked = selectable.length > 0 && selected.length === selectable.length;
            selectAll.indeterminate = selected.length > 0 && selected.length < selectable.length;
        }
        if (bulkDeleteBtn) {
            const n = selected.length;
            bulkDeleteBtn.disabled = n === 0;
            bulkDeleteBtn.classList.toggle('hidden', n === 0);
            if (bulkCountEl) {
                bulkCountEl.textContent = n ? '(' + n + ')' : '';
            }
        }
    }

    function startFastPoll(ms) {
        fastPollUntil = Date.now() + (ms || 120000);
        schedulePoll(true);
    }

    function showFlash(message, isError) {
        if (typeof window.showAdminHeaderFlash === 'function') {
            window.showAdminHeaderFlash(isError ? 'error' : 'success', message);
            return;
        }
        if (!message) {
            return;
        }
        window.alert(message);
    }

    function refreshBackupTable(options) {
        const opts = options || {};
        const url = refreshBtn.getAttribute('data-url');
        if (!url) {
            return Promise.resolve(false);
        }
        if (refreshInFlight) {
            refreshQueued = Object.assign({}, refreshQueued || {}, opts, {
                force: !!(refreshQueued && refreshQueued.force) || !!opts.force,
            });
            return Promise.resolve(false);
        }

        refreshInFlight = true;
        const icon = refreshBtn.querySelector('i');
        if (!opts.silent) {
            refreshBtn.disabled = true;
            refreshBtn.setAttribute('aria-busy', 'true');
            if (icon) {
                icon.classList.add('animate-spin');
            }
        }

        return fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html',
            },
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('refresh-failed');
                }
                return response.text();
            })
            .then(function (html) {
                const wrap = document.getElementById('database-backups-table');
                if (!wrap) {
                    return false;
                }
                const tmp = document.createElement('div');
                tmp.innerHTML = html;
                const next = tmp.querySelector('#database-backups-table') || tmp.firstElementChild;
                if (!next) {
                    return false;
                }
                const nextSig = tableSignatureFromRoot(next);
                if (!opts.force && nextSig === currentTableSignature()) {
                    return false;
                }
                const selected = selectedBackupIds();
                const host = document.getElementById('database-backups-list');
                if (host) {
                    host.querySelectorAll('.admin-mobile-list').forEach(function (el) {
                        el.remove();
                    });
                }
                wrap.outerHTML = next.outerHTML;
                initBackupMenus();
                reloadBackupDatatable();
                restoreSelection(selected);
                return true;
            })
            .catch(function () {
                if (!opts.silent) {
                    window.alert('Lijst vernieuwen mislukt.');
                }
                return false;
            })
            .finally(function () {
                refreshInFlight = false;
                refreshBtn.disabled = false;
                refreshBtn.removeAttribute('aria-busy');
                if (icon) {
                    icon.classList.remove('animate-spin');
                }
                if (refreshQueued) {
                    const queued = refreshQueued;
                    refreshQueued = null;
                    refreshBackupTable(queued);
                }
            });
    }

    refreshBtn.addEventListener('click', function () {
        refreshBackupTable({ force: true });
    });

    if (list) {
        list.addEventListener('change', function (e) {
            const target = e.target;
            if (!target) {
                return;
            }
            if (target.id === 'database-backups-select-all') {
                selectableCheckboxes().forEach(function (cb) {
                    cb.checked = target.checked;
                });
                syncBulkUi();
                return;
            }
            if (target.classList && target.classList.contains('database-backup-checkbox')) {
                syncBulkUi();
            }
        });
    }

    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', function () {
            const ids = selectedBackupIds();
            if (!ids.length || bulkDeleteBtn.disabled) {
                return;
            }
            const url = bulkDeleteBtn.getAttribute('data-url');
            if (!url) {
                return;
            }
            const label = ids.length === 1
                ? 'Deze backup permanent verwijderen?'
                : 'Deze ' + ids.length + ' backups permanent verwijderen?';
            if (!window.confirm(label)) {
                return;
            }

            bulkDeleteBtn.disabled = true;
            fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({ ids: ids.map(function (id) { return parseInt(id, 10); }) }),
            })
                .then(function (response) {
                    return response.json().then(function (data) {
                        return { ok: response.ok, data: data };
                    }).catch(function () {
                        return { ok: false, data: { message: 'Verwijderen mislukt.' } };
                    });
                })
                .then(function (result) {
                    if (!result.ok || (result.data && result.data.ok === false)) {
                        throw new Error((result.data && result.data.message) || 'Verwijderen mislukt.');
                    }
                    showFlash((result.data && result.data.message) || 'Backups verwijderd.', false);
                    return refreshBackupTable({ force: true, silent: true });
                })
                .catch(function (err) {
                    showFlash(err.message || 'Verwijderen mislukt.', true);
                    syncBulkUi();
                });
        });
    }

    syncBulkUi();

    function isSaveShortcut(e) {
        return (e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S' || e.keyCode === 83 || e.which === 83);
    }

    function nodeInsideBackupCard(node) {
        if (!node) {
            return false;
        }
        if (node.nodeType !== 1) {
            node = node.parentElement;
        }
        return !!(node && node.closest && node.closest('#database-backups'));
    }

    function submitBackupSettings(e) {
        const liveForm = document.getElementById('database-backup-settings-form');
        if (!liveForm) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        const submitBtn = liveForm.querySelector('button[type="submit"]');
        try {
            if (typeof liveForm.requestSubmit === 'function') {
                liveForm.requestSubmit(submitBtn || undefined);
            } else {
                liveForm.submit();
            }
        } catch (err) {
            liveForm.submit();
        }
    }

    document.addEventListener('keydown', function (e) {
        if (!isSaveShortcut(e)) {
            return;
        }
        if (!nodeInsideBackupCard(e.target) && !nodeInsideBackupCard(document.activeElement)) {
            return;
        }
        submitBackupSettings(e);
    }, true);

    card.setAttribute('data-backup-shortcuts', '1');

    if (runForm) {
        runForm.addEventListener('submit', function (e) {
            e.preventDefault();
            if (runBtn && runBtn.disabled) {
                return;
            }
            if (!window.confirm('Nu een handmatige database-backup starten?')) {
                return;
            }

            if (runBtn) {
                runBtn.disabled = true;
            }
            showFlash('Backup wordt gemaakt…', false);
            startFastPoll(180000);
            setTimeout(function () {
                refreshBackupTable({ force: true, silent: true });
            }, 600);

            fetch(runForm.getAttribute('action'), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: new FormData(runForm),
            })
                .then(function (response) {
                    return response.json().then(function (data) {
                        return { ok: response.ok, data: data };
                    }).catch(function () {
                        return { ok: false, data: { message: 'Backup mislukt.' } };
                    });
                })
                .then(function (result) {
                    if (!result.ok || (result.data && result.data.ok === false)) {
                        throw new Error((result.data && result.data.message) || 'Backup mislukt.');
                    }
                    showFlash((result.data && result.data.message) || 'Backup voltooid.', false);
                    startFastPoll(15000);
                    return refreshBackupTable({ force: true, silent: true }).then(function (updated) {
                        if (updated) {
                            return updated;
                        }
                        return new Promise(function (resolve) {
                            setTimeout(function () {
                                resolve(refreshBackupTable({ force: true, silent: true }));
                            }, 400);
                        });
                    });
                })
                .catch(function (err) {
                    showFlash(err.message || 'Backup mislukt.', true);
                    startFastPoll(15000);
                    refreshBackupTable({ force: true, silent: true });
                })
                .finally(function () {
                    if (runBtn) {
                        runBtn.disabled = false;
                    }
                });
        });
    }

    function sectionIsOpen() {
        return !card.classList.contains('settings-collapsible-card--collapsed');
    }

    function schedulePoll(immediate) {
        if (pollTimer) {
            clearTimeout(pollTimer);
        }
        const delay = immediate ? 0 : ((tableHasPending() || Date.now() < fastPollUntil) ? pendingPollMs : pollMs);
        pollTimer = setTimeout(function () {
            if (!document.hidden && sectionIsOpen()) {
                refreshBackupTable({ silent: true, force: tableHasPending() }).finally(function () {
                    schedulePoll(false);
                });
                return;
            }
            schedulePoll(false);
        }, delay);
    }

    schedulePoll(false);
})();
</script>
