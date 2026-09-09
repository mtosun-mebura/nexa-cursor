@extends('admin.layouts.app')

@section('title', 'SaaS-pakketten')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                SaaS-pakketten
            </h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Vaste abonnementspakketten voor tenants
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.platform-billing.packages.create') }}" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-plus me-2"></i>
                Nieuw pakket
            </a>
        </div>
    </div>

    <div class="kt-card kt-card-grid w-full min-w-0">
        <div class="kt-card-header px-5 py-5 flex-wrap gap-3 justify-between items-center">
            <div class="flex flex-wrap items-center gap-2">
                <h3 class="kt-card-title text-sm mb-0">
                    Pakketten
                </h3>
                <button type="button"
                        id="packages-bulk-delete"
                        class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-destructive hidden"
                        hidden
                        aria-label="Geselecteerde pakketten verwijderen"
                        title="Verwijderen"><i class="ki-filled ki-trash"></i><span>(<span data-packages-selected-count>0</span>)</span></button>
            </div>
            <form method="GET" action="{{ route('admin.platform-billing.packages.index') }}" class="admin-filter-panel flex flex-wrap items-center gap-2">
                <label class="kt-input w-full sm:w-56 min-w-0">
                    <i class="ki-filled ki-magnifier"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Zoeken…" class="min-w-0" autocomplete="off">
                </label>
                <select class="kt-select w-full sm:w-40" name="is_active">
                    <option value="" @selected(request('is_active', '') === '')>Alle statussen</option>
                    <option value="1" @selected(request('is_active') === '1')>Actief</option>
                    <option value="0" @selected(request('is_active') === '0')>Inactief</option>
                </select>
            </form>
        </div>
        <div class="kt-card-content p-0 min-w-0">
            <div class="kt-scrollable-x-auto admin-table-scroll-wrap admin-desktop-table-wrap min-w-0">
                <table id="platform-billing-packages-table" class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full platform-billing-packages-table">
                    <colgroup>
                        <col class="platform-billing-packages-col-check">
                        <col class="platform-billing-packages-col-name">
                        <col class="platform-billing-packages-col-description">
                        <col class="platform-billing-packages-col-price">
                        <col class="platform-billing-packages-col-active">
                        <col class="admin-table__actions-col">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="platform-billing-packages-col-check text-center" data-label="Selecteer" data-no-row-link>
                                <label class="kt-label inline-flex items-center justify-center cursor-pointer mb-0">
                                    <input type="checkbox" class="kt-checkbox" id="packages-select-all" aria-label="Alles selecteren">
                                </label>
                            </th>
                            <th class="text-secondary-foreground font-normal text-left" data-label="Naam">Naam</th>
                            <th class="text-secondary-foreground font-normal text-left" data-label="Omschrijving">Omschrijving</th>
                            <th class="text-secondary-foreground font-normal text-left" data-label="Maandprijs">Maandprijs</th>
                            <th class="text-secondary-foreground font-normal text-left" data-label="Actief">Actief</th>
                            <th class="admin-table__actions-col text-secondary-foreground font-normal text-center" data-label="Acties">Acties</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($packages as $package)
                        <tr class="platform-billing-package-row" data-row-href="{{ route('admin.platform-billing.packages.show', $package) }}">
                            <td class="platform-billing-packages-col-check text-center" data-no-row-link>
                                <label class="kt-label inline-flex items-center justify-center cursor-pointer mb-0">
                                    <input type="checkbox"
                                           class="kt-checkbox package-row-checkbox"
                                           value="{{ $package->id }}"
                                           aria-label="Selecteer {{ $package->name }}">
                                </label>
                            </td>
                            <td class="platform-billing-packages__name font-medium text-mono">{{ $package->name }}</td>
                            <td class="platform-billing-packages__description text-secondary-foreground" @if($package->description) title="{{ $package->description }}" @endif>
                                {{ $package->description ?: '—' }}
                            </td>
                            <td class="platform-billing-packages__price whitespace-nowrap tabular-nums">€ {{ number_format((float) $package->monthly_amount, 2, ',', '.') }}</td>
                            <td class="platform-billing-packages__active whitespace-nowrap">{{ $package->is_active ? 'Ja' : 'Nee' }}</td>
                            <td class="admin-table__actions-col" data-no-row-link>
                                <div class="kt-menu flex justify-center" data-kt-menu="true">
                                    <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-placement-rtl="bottom-start" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                        <button type="button" class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" aria-label="Acties">
                                            <i class="ki-filled ki-dots-vertical text-lg"></i>
                                        </button>
                                        <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]" data-kt-menu-dismiss="true">
                                            <div class="kt-menu-item">
                                                <a class="kt-menu-link" href="{{ route('admin.platform-billing.packages.show', $package) }}">
                                                    <span class="kt-menu-icon">
                                                        <i class="ki-filled ki-eye"></i>
                                                    </span>
                                                    <span class="kt-menu-title">Bekijken</span>
                                                </a>
                                            </div>
                                            <div class="kt-menu-item">
                                                <a class="kt-menu-link" href="{{ route('admin.platform-billing.packages.edit', $package) }}">
                                                    <span class="kt-menu-icon">
                                                        <i class="ki-filled ki-pencil"></i>
                                                    </span>
                                                    <span class="kt-menu-title">Bewerken</span>
                                                </a>
                                            </div>
                                            <div class="kt-menu-separator"></div>
                                            <div class="kt-menu-item">
                                                <form action="{{ route('admin.platform-billing.packages.toggle-status', $package) }}"
                                                      method="POST"
                                                      class="block"
                                                      onsubmit="return confirm('Weet je zeker dat je de status wilt wijzigen?')">
                                                    @csrf
                                                    <button type="submit" class="kt-menu-link w-full text-left">
                                                        <span class="kt-menu-icon">
                                                            <i class="ki-filled {{ $package->is_active ? 'ki-pause' : 'ki-play' }}"></i>
                                                        </span>
                                                        <span class="kt-menu-title">{{ $package->is_active ? 'Deactiveren' : 'Activeren' }}</span>
                                                    </button>
                                                </form>
                                            </div>
                                            <div class="kt-menu-separator"></div>
                                            <div class="kt-menu-item">
                                                <form action="{{ route('admin.platform-billing.packages.destroy', $package) }}"
                                                      method="POST"
                                                      class="block package-delete-form"
                                                      data-package-name="{{ $package->name }}"
                                                      data-confirm-title="Pakket verwijderen"
                                                      data-confirm-message="Weet je zeker dat je het pakket “{{ $package->name }}” wilt verwijderen? Dit kan niet ongedaan worden gemaakt.">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="kt-menu-link w-full text-left text-danger">
                                                        <span class="kt-menu-icon">
                                                            <i class="ki-filled ki-trash"></i>
                                                        </span>
                                                        <span class="kt-menu-title">Verwijderen</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-5 text-secondary-foreground">Nog geen pakketten.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @include('admin.partials.pagination-footer', ['paginator' => $packages])
    </div>
</div>

<form method="POST"
      action="{{ route('admin.platform-billing.packages.bulk-destroy') }}"
      id="packages-bulk-delete-form"
      class="hidden">
    @csrf
    @method('DELETE')
    <div id="packages-bulk-delete-ids"></div>
</form>

<div id="package-delete-modal"
     class="hidden fixed inset-0 items-center justify-center p-4"
     role="dialog"
     aria-modal="true"
     aria-labelledby="package-delete-modal-title"
     hidden>
    <div class="absolute inset-0 bg-zinc-950/70 backdrop-blur-md" data-package-delete-dismiss></div>
    <div class="relative w-full max-w-md rounded-2xl border border-border bg-background shadow-2xl">
        <div class="px-5 py-5 border-b border-border">
            <h3 id="package-delete-modal-title" class="text-lg font-semibold text-foreground mb-0">Pakket verwijderen</h3>
        </div>
        <div class="px-5 py-5">
            <p class="text-sm text-muted-foreground mb-0" data-package-delete-message>
                Weet je zeker dat je dit pakket wilt verwijderen?
            </p>
        </div>
        <div class="px-5 py-5 border-t border-border flex flex-wrap justify-end gap-2">
            <button type="button" class="kt-btn kt-btn-outline" data-package-delete-dismiss>Annuleren</button>
            <button type="button" class="kt-btn kt-btn-destructive" data-package-delete-confirm>Verwijderen</button>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    #package-delete-modal {
        z-index: 80;
    }

    #packages-bulk-delete {
        border: 0 !important;
        box-shadow: none !important;
        background-color: transparent !important;
        padding-inline: 0.25rem;
        gap: 0.15rem;
        height: auto;
        align-items: center;
        font-variant-numeric: tabular-nums;
        color: var(--destructive) !important;
    }

    #packages-bulk-delete:hover,
    #packages-bulk-delete:focus,
    #packages-bulk-delete:focus-visible,
    #packages-bulk-delete:active {
        background-color: transparent !important;
        color: var(--destructive) !important;
    }

    #packages-bulk-delete i,
    #packages-bulk-delete:hover i,
    #packages-bulk-delete:focus i,
    #packages-bulk-delete:active i {
        font-size: 1.15rem !important;
        line-height: 1 !important;
        color: inherit !important;
    }

    #packages-bulk-delete span {
        font-size: 0.8125rem !important;
        line-height: 1 !important;
        color: inherit !important;
    }

    #content #platform-billing-packages-table col.platform-billing-packages-col-check {
        width: 2.75rem;
    }

    #content #platform-billing-packages-table .platform-billing-packages-col-check {
        width: 2.75rem !important;
        min-width: 2.75rem !important;
        max-width: 2.75rem !important;
        padding: 0 !important;
        text-align: center !important;
        vertical-align: middle !important;
    }

    #content #platform-billing-packages-table .platform-billing-packages-col-check .kt-label {
        display: flex !important;
        align-items: center;
        justify-content: center;
        width: 100%;
        min-height: 2.5rem;
        margin: 0 !important;
        padding: 0 !important;
        line-height: 0;
    }

    #content #platform-billing-packages-table .platform-billing-packages-col-check .kt-checkbox {
        margin: 0 !important;
        display: block;
        vertical-align: middle;
    }

    #content #platform-billing-packages-table col.platform-billing-packages-col-name {
        width: 11rem;
    }

    #content #platform-billing-packages-table col.platform-billing-packages-col-price {
        width: 6.5rem;
    }

    #content #platform-billing-packages-table col.platform-billing-packages-col-active {
        width: 3.75rem;
    }

    #content #platform-billing-packages-table .platform-billing-packages__name,
    #content #platform-billing-packages-table .platform-billing-packages__description {
        max-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        word-break: normal !important;
        overflow-wrap: normal !important;
        vertical-align: middle;
    }

    #content #platform-billing-packages-table .platform-billing-packages__price,
    #content #platform-billing-packages-table .platform-billing-packages__active {
        width: auto !important;
        word-break: normal !important;
        overflow-wrap: normal !important;
        vertical-align: middle;
    }

    #content #platform-billing-packages-table .platform-billing-packages__active {
        padding-inline-end: 0.5rem !important;
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('assets/js/search-input-clear.js') }}"></script>
<script>
(function () {
    const table = document.getElementById('platform-billing-packages-table');
    const selectAll = document.getElementById('packages-select-all');
    const bulkBtn = document.getElementById('packages-bulk-delete');
    const bulkForm = document.getElementById('packages-bulk-delete-form');
    const bulkIds = document.getElementById('packages-bulk-delete-ids');
    const modal = document.getElementById('package-delete-modal');
    if (!table || !modal) {
        return;
    }

    const titleEl = document.getElementById('package-delete-modal-title');
    const messageEl = modal.querySelector('[data-package-delete-message]');
    const countEl = document.querySelector('[data-packages-selected-count]');
    let pendingForm = null;

    function rowCheckboxes() {
        return Array.from(table.querySelectorAll('.package-row-checkbox'));
    }

    function selectedCheckboxes() {
        return rowCheckboxes().filter(function (cb) { return cb.checked; });
    }

    function syncSelection() {
        const boxes = rowCheckboxes();
        const selected = selectedCheckboxes();
        if (selectAll) {
            selectAll.checked = boxes.length > 0 && selected.length === boxes.length;
            selectAll.indeterminate = selected.length > 0 && selected.length < boxes.length;
        }
        if (countEl) {
            countEl.textContent = String(selected.length);
        }
        if (bulkBtn) {
            const show = selected.length > 0;
            bulkBtn.hidden = !show;
            bulkBtn.classList.toggle('hidden', !show);
        }
    }

    function openModal(title, message, form) {
        pendingForm = form;
        if (titleEl) {
            titleEl.textContent = title;
        }
        if (messageEl) {
            messageEl.textContent = message;
        }
        modal.hidden = false;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        pendingForm = null;
        modal.hidden = true;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function fillBulkForm() {
        if (!bulkIds) {
            return;
        }
        bulkIds.innerHTML = '';
        selectedCheckboxes().forEach(function (cb) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'package_ids[]';
            input.value = cb.value;
            bulkIds.appendChild(input);
        });
    }

    table.addEventListener('change', function (e) {
        if (e.target && e.target.classList.contains('package-row-checkbox')) {
            syncSelection();
        }
    });

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            rowCheckboxes().forEach(function (cb) {
                cb.checked = selectAll.checked;
            });
            syncSelection();
        });
    }

    table.querySelectorAll('.package-delete-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            openModal(
                form.getAttribute('data-confirm-title') || 'Pakket verwijderen',
                form.getAttribute('data-confirm-message') || 'Weet je zeker dat je dit pakket wilt verwijderen? Dit kan niet ongedaan worden gemaakt.',
                form
            );
        });
    });

    if (bulkBtn && bulkForm) {
        bulkBtn.addEventListener('click', function () {
            const count = selectedCheckboxes().length;
            if (count === 0) {
                return;
            }
            fillBulkForm();
            openModal(
                count === 1 ? 'Pakket verwijderen' : 'Pakketten verwijderen',
                count === 1
                    ? 'Weet je zeker dat je het geselecteerde pakket wilt verwijderen? Dit kan niet ongedaan worden gemaakt.'
                    : 'Weet je zeker dat je de ' + count + ' geselecteerde pakketten wilt verwijderen? Dit kan niet ongedaan worden gemaakt.',
                bulkForm
            );
        });
    }

    modal.querySelectorAll('[data-package-delete-dismiss]').forEach(function (el) {
        el.addEventListener('click', closeModal);
    });

    const confirmBtn = modal.querySelector('[data-package-delete-confirm]');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function () {
            if (pendingForm) {
                pendingForm.submit();
            }
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hidden) {
            closeModal();
        }
    });

    syncSelection();
})();
</script>
@endpush
