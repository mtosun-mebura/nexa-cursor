@extends('admin.layouts.app')

@section('title', 'NEXA-facturen')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                NEXA-facturen
            </h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Platform → tenant facturatie
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <form method="POST" action="{{ route('admin.platform-billing.invoices.run-dunning') }}">
                @csrf
                <button type="submit" class="kt-btn kt-btn-outline" onclick="return confirm('Openstaande NEXA-facturen nu controleren bij Mollie en zo nodig aanmanen of blokkeren?')">
                    <i class="ki-filled ki-notification-status me-2"></i>
                    Betalingen controleren
                </button>
            </form>
            <form method="POST" action="{{ route('admin.platform-billing.invoices.run-now') }}">
                @csrf
                <button type="submit" class="kt-btn kt-btn-outline" onclick="return confirm('Facturatie nu uitvoeren (indien dag/tijd matcht)?')">
                    <i class="ki-filled ki-arrows-circle me-2"></i>
                    Facturatie nu draaien
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5" role="alert">
            <i class="ki-filled ki-check-circle me-2"></i>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="kt-alert kt-alert-destructive mb-5" role="alert">
            <i class="ki-filled ki-information-2 me-2"></i>
            {{ session('error') }}
        </div>
    @endif

    @include('admin.platform-billing.invoices.partials.werkwijze')

    <div class="kt-card kt-card-grid w-full min-w-0">
        <div class="kt-card-header px-5 py-5 flex-wrap gap-3 justify-between items-center">
            <h3 class="kt-card-title text-sm mb-0">
                Overzicht NEXA-facturen
            </h3>
            <form method="GET" action="{{ route('admin.platform-billing.invoices.index') }}" class="admin-filter-panel flex flex-wrap items-center gap-2">
                <label class="kt-input w-full sm:w-56 min-w-0">
                    <i class="ki-filled ki-magnifier"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Zoek nummer of tenant…" class="min-w-0" autocomplete="off">
                </label>
                    <select class="kt-select w-full sm:w-48" name="company_id" data-label="Tenant">
                        <option value="">Alle tenants</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" @selected((string) ($filterCompanyId ?? '') === (string) $company->id)>{{ $company->name }}</option>
                        @endforeach
                    </select>
                <select class="kt-select w-full sm:w-40" name="status">
                    <option value="">Alle statussen</option>
                    @foreach($statusOptions as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ \App\Models\PlatformInvoice::statusLabelFor($status) }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="kt-card-content p-0 min-w-0">
            <div class="kt-scrollable-x-auto admin-table-scroll-wrap admin-desktop-table-wrap min-w-0">
                <table id="platform-billing-invoices-table" class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full">
                    <colgroup>
                        <col class="platform-billing-invoices-col-number">
                        <col>
                        <col class="platform-billing-invoices-col-period">
                        <col class="platform-billing-invoices-col-total">
                        <col class="platform-billing-invoices-col-status">
                        <col class="admin-table__actions-col">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="text-secondary-foreground font-normal text-left whitespace-nowrap" data-label="Factuurnummer">Factuurnummer</th>
                            <th class="text-secondary-foreground font-normal text-left">Tenant</th>
                            <th class="text-secondary-foreground font-normal text-left">Periode</th>
                            <th class="text-secondary-foreground font-normal text-left">Totaal</th>
                            <th class="text-secondary-foreground font-normal text-left">Status</th>
                            <th class="admin-table__actions-col text-secondary-foreground font-normal text-center" data-label="Acties">Acties</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($invoices as $invoice)
                        <tr data-row-href="{{ route('admin.platform-billing.invoices.show', $invoice) }}">
                            <td class="platform-billing-invoices__number font-medium text-mono whitespace-nowrap">{{ $invoice->invoice_number }}</td>
                            <td class="platform-billing-invoices__tenant">{{ $invoice->company?->name }}</td>
                            <td class="platform-billing-invoices__period whitespace-nowrap">{{ $invoice->billing_period }}</td>
                            <td class="platform-billing-invoices__total whitespace-nowrap tabular-nums">€ {{ number_format((float) $invoice->total_amount, 2, ',', '.') }}</td>
                            <td class="platform-billing-invoices__status">
                                <span class="kt-badge kt-badge-outline rounded-[30px] whitespace-nowrap {{ $invoice->statusBadgeClass() }}">{{ $invoice->statusLabel() }}</span>
                            </td>
                            <td class="admin-table__actions-col" data-no-row-link>
                                <div class="kt-menu flex justify-center" data-kt-menu="true">
                                    <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                        <button type="button" class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" aria-label="Acties">
                                            <i class="ki-filled ki-dots-vertical text-lg"></i>
                                        </button>
                                        <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]" data-kt-menu-dismiss="true">
                                            <div class="kt-menu-item">
                                                <a class="kt-menu-link" href="{{ route('admin.platform-billing.invoices.show', $invoice) }}">
                                                    <span class="kt-menu-icon"><i class="ki-filled ki-eye"></i></span>
                                                    <span class="kt-menu-title">Bekijken</span>
                                                </a>
                                            </div>
                                            <div class="kt-menu-item">
                                                <a class="kt-menu-link" href="{{ route('admin.platform-billing.invoices.edit', $invoice) }}">
                                                    <span class="kt-menu-icon"><i class="ki-filled ki-pencil"></i></span>
                                                    <span class="kt-menu-title">Bewerken</span>
                                                </a>
                                            </div>
                                            <div class="kt-menu-item">
                                                <a class="kt-menu-link" href="{{ route('admin.platform-billing.invoices.pdf', $invoice) }}" target="_blank" rel="noopener">
                                                    <span class="kt-menu-icon"><i class="ki-filled ki-file-down"></i></span>
                                                    <span class="kt-menu-title">PDF</span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-5 text-secondary-foreground">Geen NEXA-facturen gevonden.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @include('admin.partials.pagination-footer', ['paginator' => $invoices])
    </div>
</div>
@endsection

@push('styles')
    @include('admin.platform-billing.partials.invoice-preview-styles')
<style>
    #content #platform-billing-invoices-table col.platform-billing-invoices-col-number {
        width: 13.5rem;
    }

    #content #platform-billing-invoices-table col.platform-billing-invoices-col-period {
        width: 7rem;
    }

    #content #platform-billing-invoices-table col.platform-billing-invoices-col-total {
        width: 7rem;
    }

    #content #platform-billing-invoices-table col.platform-billing-invoices-col-status {
        width: 9rem;
    }

    #content #platform-billing-invoices-table td {
        vertical-align: middle !important;
    }

    #content #platform-billing-invoices-table .platform-billing-invoices__tenant {
        max-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    #nexa-facturatie-werkwijze.nexa-werkwijze--collapsed > #nexa-facturatie-werkwijze-body {
        display: none;
    }

    #nexa-facturatie-werkwijze.nexa-werkwijze--collapsed > .nexa-werkwijze-header {
        border-bottom: none;
    }

    .nexa-werkwijze-chevron .nexa-werkwijze-icon-up {
        display: none;
    }

    .nexa-werkwijze-chevron .nexa-werkwijze-icon-down {
        display: inline-block;
    }

    #nexa-facturatie-werkwijze:not(.nexa-werkwijze--collapsed) .nexa-werkwijze-icon-down {
        display: none;
    }

    #nexa-facturatie-werkwijze:not(.nexa-werkwijze--collapsed) .nexa-werkwijze-icon-up {
        display: inline-block;
    }

    #nexa-facturatie-werkwijze.nexa-werkwijze--collapsed .nexa-werkwijze-icon-down {
        display: inline-block;
    }

    #nexa-facturatie-werkwijze.nexa-werkwijze--collapsed .nexa-werkwijze-icon-up {
        display: none;
    }

    .nexa-werkwijze-toggle:hover .kt-card-title,
    .nexa-werkwijze-chevron-btn:hover {
        color: var(--color-primary, #3b82f6);
    }

    .nexa-werkwijze-thumb {
        display: flex;
        flex-direction: column;
        width: 100%;
        margin: 0;
        padding: 0;
        border: 1px solid var(--border);
        border-radius: 0.75rem;
        overflow: hidden;
        background: #dbe4ef;
        text-align: left;
        cursor: pointer;
        color: inherit;
    }

    .nexa-werkwijze-thumb:hover,
    .nexa-werkwijze-thumb:focus-visible {
        border-color: var(--color-primary, #3b82f6);
        box-shadow: 0 0 0 1px var(--color-primary, #3b82f6);
        outline: none;
    }

    .nexa-werkwijze-thumb__label {
        display: block;
        padding: 0.625rem 0.75rem;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--foreground);
        background: var(--card, var(--background));
        border-bottom: 1px solid var(--border);
    }

    .nexa-werkwijze-thumb__clip {
        container-type: inline-size;
        height: 14rem;
        overflow: hidden;
        pointer-events: none;
    }

    .nexa-werkwijze-thumb__scale {
        display: block;
        width: 40rem;
        transform: scale(0.28);
        transform-origin: top left;
    }

    @supports (width: 1cqi) {
        .nexa-werkwijze-thumb__scale {
            transform: scale(calc(100cqi / 40rem));
        }
    }

    .nexa-werkwijze-doc {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .nexa-werkwijze-mail {
        border: 1px solid #cbd5e1;
        border-radius: 0.75rem;
        background: #f8fafc;
        color: #0f172a;
        overflow: hidden;
    }

    .nexa-werkwijze-mail__meta {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #e2e8f0;
        font-size: 0.8125rem;
        line-height: 1.45;
        background: #fff;
    }

    .nexa-werkwijze-mail__label {
        display: inline-block;
        min-width: 5.25rem;
        color: #64748b;
    }

    .nexa-werkwijze-mail__body {
        padding: 0.75rem 1rem 1rem;
        font-size: 0.8125rem;
        line-height: 1.55;
        white-space: pre-wrap;
    }

    .nexa-werkwijze-mail__attach {
        padding: 0.5rem 1rem 0.75rem;
        font-size: 0.75rem;
        color: #475569;
        border-top: 1px dashed #e2e8f0;
    }

    .nexa-werkwijze-paper {
        background: #fff;
        color: #0f172a;
        color-scheme: light;
        border: 1px solid #e2e8f0;
        border-radius: 0.25rem;
        padding: 1.25rem 1.5rem 1.5rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
    }

    .nexa-werkwijze-paper,
    .nexa-werkwijze-paper .platform-billing-invoice-preview,
    .nexa-werkwijze-paper .kt-table,
    .nexa-werkwijze-paper .kt-table td,
    .nexa-werkwijze-paper .platform-billing-invoice-preview__title,
    .nexa-werkwijze-paper .platform-billing-invoice-preview__meta-value,
    .nexa-werkwijze-paper .platform-billing-invoice-preview__meta-right,
    .nexa-werkwijze-paper .platform-billing-invoice-preview__customer-name,
    .nexa-werkwijze-paper .platform-billing-invoice-preview__totals-row,
    .nexa-werkwijze-paper .platform-billing-invoice-preview__totals-row--grand,
    .nexa-werkwijze-paper .platform-billing-invoice-preview__description,
    .nexa-werkwijze-paper .platform-billing-invoice-preview__num,
    .nexa-werkwijze-paper .platform-billing-invoice-preview__money,
    .nexa-werkwijze-paper strong,
    .nexa-werkwijze-paper .text-foreground,
    .nexa-werkwijze-paper .text-mono {
        color: #0f172a !important;
    }

    .nexa-werkwijze-paper .kt-table thead th,
    .nexa-werkwijze-paper .kt-table tbody td {
        background-color: #fff !important;
        border-color: #e2e8f0 !important;
    }

    .nexa-werkwijze-paper .kt-table thead th,
    .nexa-werkwijze-paper .kt-table thead th.text-secondary-foreground,
    .nexa-werkwijze-paper .platform-billing-invoice-preview__money-header {
        background-color: #f8fafc !important;
        color: #64748b !important;
    }

    .nexa-werkwijze-paper .kt-table tbody td {
        color: #0f172a !important;
    }

    .nexa-werkwijze-paper .platform-billing-invoice-preview__issuer,
    .nexa-werkwijze-paper .platform-billing-invoice-preview__meta-label,
    .nexa-werkwijze-paper .platform-billing-invoice-preview__payment-terms,
    .nexa-werkwijze-paper .platform-billing-invoice-preview__totals-row .text-secondary-foreground,
    .nexa-werkwijze-paper .text-muted-foreground {
        color: #64748b !important;
    }

    .nexa-werkwijze-paper .logo-dark {
        display: none !important;
    }

    .nexa-werkwijze-paper .logo-light {
        display: block !important;
    }

    .nexa-werkwijze-banner {
        margin: 0 0 1rem;
        padding: 0.625rem 0.875rem;
        font-size: 0.8125rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        text-align: center;
        border-radius: 0.375rem;
    }

    .nexa-werkwijze-banner--first {
        background: #fef3c7;
        border: 1px solid #f59e0b;
        color: #92400e;
    }

    .nexa-werkwijze-banner--second {
        background: #fee2e2;
        border: 1px solid #ef4444;
        color: #991b1b;
    }

    .nexa-werkwijze-modal {
        z-index: 100000 !important;
    }

    .nexa-werkwijze-modal__body {
        scrollbar-width: thin;
        scrollbar-color: #3f3f46 transparent;
    }

    .nexa-werkwijze-modal__body::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .nexa-werkwijze-modal__body::-webkit-scrollbar-track {
        background: transparent;
    }

    .nexa-werkwijze-modal__body::-webkit-scrollbar-thumb {
        background-color: #3f3f46;
        border-radius: 9999px;
        border: 2px solid transparent;
        background-clip: padding-box;
    }

    .nexa-werkwijze-modal__body::-webkit-scrollbar-thumb:hover {
        background-color: #27272a;
    }

    .nexa-werkwijze-modal__body::-webkit-scrollbar-corner {
        background: transparent;
    }

    .nexa-werkwijze-modal[hidden],
    .nexa-werkwijze-modal.hidden {
        display: none !important;
    }

    .nexa-werkwijze-modal:not([hidden]):not(.hidden) {
        display: flex;
    }

    body.nexa-werkwijze-preview-open {
        overflow: hidden;
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('assets/js/search-input-clear.js') }}"></script>
<script>
(function () {
    var STORAGE_KEY = 'nexa_facturatie_werkwijze_open';
    var card = document.getElementById('nexa-facturatie-werkwijze');
    var toggles = card ? card.querySelectorAll('.nexa-werkwijze-toggle') : [];
    var modal = document.getElementById('nexa-werkwijze-preview-modal');
    var modalTitle = document.getElementById('nexa-werkwijze-preview-title');
    var modalTarget = modal ? modal.querySelector('[data-nexa-preview-target]') : null;

    function setCollapsed(collapsed) {
        if (!card || !toggles.length) return;
        card.classList.toggle('nexa-werkwijze--collapsed', collapsed);
        toggles.forEach(function (btn) {
            btn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        });
        try {
            sessionStorage.setItem(STORAGE_KEY, collapsed ? '0' : '1');
        } catch (e) {}
    }

    if (card && toggles.length) {
        var stored = null;
        try {
            stored = sessionStorage.getItem(STORAGE_KEY);
        } catch (e) {}
        if (stored === '0') {
            setCollapsed(true);
        } else if (stored === '1') {
            setCollapsed(false);
        }
        toggles.forEach(function (btn) {
            btn.addEventListener('click', function () {
                setCollapsed(!card.classList.contains('nexa-werkwijze--collapsed'));
            });
        });
    }

    function closePreview() {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.setAttribute('hidden', 'hidden');
        document.body.classList.remove('nexa-werkwijze-preview-open');
        if (modalTarget) {
            modalTarget.innerHTML = '';
        }
    }

    function openPreview(id) {
        if (!modal || !modalTarget) return;
        var source = document.querySelector('[data-nexa-preview-source="' + id + '"]');
        if (!source) return;
        var trigger = document.querySelector('[data-nexa-preview-open="' + id + '"]');
        var label = trigger ? (trigger.querySelector('.nexa-werkwijze-thumb__label') || {}).textContent : '';
        if (modalTitle) {
            modalTitle.textContent = (label || 'Voorbeeld').trim();
        }
        modalTarget.innerHTML = source.innerHTML;
        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
        document.body.classList.add('nexa-werkwijze-preview-open');
        modal.classList.remove('hidden');
        modal.removeAttribute('hidden');
    }

    document.querySelectorAll('[data-nexa-preview-open]').forEach(function (el) {
        el.addEventListener('click', function () {
            openPreview(el.getAttribute('data-nexa-preview-open'));
        });
        el.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openPreview(el.getAttribute('data-nexa-preview-open'));
            }
        });
    });

    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closePreview();
            }
        });
        modal.querySelectorAll('[data-nexa-preview-close]').forEach(function (btn) {
            btn.addEventListener('click', closePreview);
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.hasAttribute('hidden')) {
                closePreview();
            }
        });
    }
})();
</script>
@endpush
