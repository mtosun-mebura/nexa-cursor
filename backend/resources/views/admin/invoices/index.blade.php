@extends('admin.layouts.app')

@section('title', 'Facturen')

@section('content')
@php
    $invoiceStatusLabels = [
        'draft' => 'Concept',
        'in_progress' => 'In behandeling',
        'sent' => 'Verzonden',
        'paid' => 'Betaald',
        'overdue' => 'Achterstallig',
        'cancelled' => 'Geannuleerd',
    ];
@endphp
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                Facturen
            </h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Overzicht van alle facturen
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="kt-btn kt-btn-primary" href="{{ route('admin.invoices.create') }}">
                <i class="ki-filled ki-plus text-base me-2"></i>
                Nieuwe Factuur
            </a>
        </div>
    </div>

    <div class="kt-card mb-5">
        <div class="kt-card-content">
            <div class="flex flex-col sm:flex-row lg:px-10 py-1.5 gap-2">
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $invoiceStats['draft'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">Concept</span>
                </div>
                <span class="hidden sm:block not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $invoiceStats['sent'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">Verzonden</span>
                </div>
                <span class="hidden sm:block not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $invoiceStats['paid'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">Betaald</span>
                </div>
                <span class="hidden sm:block not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $invoiceStats['total'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">Totaal</span>
                </div>
            </div>
        </div>
    </div>

    <div class="kt-card kt-card-grid w-full min-w-0">
        <div class="kt-card-header py-5 flex-wrap gap-2 min-w-0">
            <h3 class="kt-card-title text-sm pb-3 w-full mb-0">
                <span data-admin-datatable-info="true">Toon 1 tot {{ $invoices->count() }} van {{ $invoices->count() }} facturen</span>
            </h3>
            <div class="admin-filter-panel flex flex-col sm:flex-row flex-wrap gap-2.5 w-full sm:w-auto min-w-0 items-stretch sm:items-center"
                 data-admin-live-filter="off">
                <label class="kt-input w-full sm:w-64 min-w-0">
                    <i class="ki-filled ki-magnifier"></i>
                    <input placeholder="Zoek facturen…"
                           type="text"
                           name="search"
                           id="invoices-search-input"
                           value=""
                           autocomplete="off"
                           data-admin-datatable-search="#invoices_table">
                </label>
                <select class="kt-select w-full sm:w-44"
                        id="invoices-status-filter"
                        name="status"
                        data-admin-datatable-filter="status"
                        data-kt-select="true">
                    <option value="">Alle statussen</option>
                    @foreach($invoiceStatusLabels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @if($companies->isNotEmpty() && empty($scopedTenantId))
                <select class="kt-select w-full sm:w-52"
                        id="invoices-company-filter"
                        name="company"
                        data-admin-datatable-filter="company"
                        data-kt-select="true">
                    <option value="">Alle bedrijven</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
                @endif
                <button type="button"
                        id="invoices-filter-reset"
                        data-admin-datatable-reset
                        class="kt-btn kt-btn-outline kt-btn-icon shrink-0 hidden"
                        title="Filters resetten">
                    <i class="ki-filled ki-arrows-circle text-base"></i>
                </button>
            </div>
        </div>

        <div class="kt-card-content p-0 min-w-0">
            @if($invoices->count() > 0)
            <div class="grid w-full min-w-0"
                 data-admin-datatable="true"
                 data-admin-datatable-page-size="25"
                 id="invoices_table"
                 data-admin-datatable-label="facturen">
                <div class="invoices-table-wrap min-w-0">
                    <div class="kt-scrollable-x-auto admin-table-scroll-wrap">
                        <table class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full" id="invoices-table">
                            <thead>
                                <tr>
                                    <th class="text-secondary-foreground font-normal text-left" data-label="Factuurnummer">Factuurnummer</th>
                                    <th class="text-secondary-foreground font-normal text-left" data-label="Bedrijf">Bedrijf</th>
                                    <th class="text-secondary-foreground font-normal text-left" data-label="Bedrag">Bedrag</th>
                                    <th class="text-secondary-foreground font-normal text-left" data-label="Status">Status</th>
                                    <th class="text-secondary-foreground font-normal text-left" data-label="Vervaldatum">Vervaldatum</th>
                                    <th class="invoices-table__actions-col text-secondary-foreground font-normal text-center" data-label="Acties">Acties</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoices as $invoice)
                                @php
                                    $displayNumber = $invoice->invoice_number.($invoice->is_partial && $invoice->partial_number ? '-'.$invoice->partial_number : '');
                                    $companyName = $invoice->company->name ?? 'N/A';
                                    $statusLabel = $invoiceStatusLabels[$invoice->status] ?? ucfirst($invoice->status);
                                    $amountFormatted = '€'.number_format((float) $invoice->total_amount, 2, ',', '.');
                                    $dueFormatted = $invoice->due_date?->format('d M Y') ?? '';
                                    $searchText = mb_strtolower(implode(' ', array_filter([
                                        $displayNumber,
                                        $companyName,
                                        $amountFormatted,
                                        $statusLabel,
                                        $invoice->status,
                                        $dueFormatted,
                                        $invoice->due_date?->format('d-m-Y'),
                                    ])), 'UTF-8');
                                @endphp
                                <tr data-row-href="{{ route('admin.invoices.show', $invoice->id) }}"
                                    data-status="{{ $invoice->status }}"
                                    data-company="{{ $invoice->company_id }}"
                                    data-search-text="{{ $searchText }}">
                                    <td>
                                        <a class="leading-none font-medium text-sm text-mono hover:text-primary" href="{{ route('admin.invoices.show', $invoice->id) }}">
                                            {{ $displayNumber }}
                                        </a>
                                    </td>
                                    <td class="text-secondary-foreground">{{ $companyName }}</td>
                                    <td class="text-secondary-foreground">{{ $amountFormatted }}</td>
                                    <td>
                                        @if($invoice->status === 'paid')
                                            <span class="kt-badge kt-badge-success kt-badge-outline rounded-[30px]">Betaald</span>
                                        @elseif($invoice->status === 'sent')
                                            <span class="kt-badge kt-badge-warning kt-badge-outline rounded-[30px]">Verzonden</span>
                                        @elseif($invoice->status === 'in_progress')
                                            <span class="kt-badge kt-badge-info kt-badge-outline rounded-[30px]">In behandeling</span>
                                        @elseif($invoice->status === 'overdue')
                                            <span class="kt-badge kt-badge-destructive kt-badge-outline rounded-[30px]">Achterstallig</span>
                                        @elseif($invoice->status === 'cancelled')
                                            <span class="kt-badge kt-badge-secondary kt-badge-outline rounded-[30px]">Geannuleerd</span>
                                        @else
                                            <span class="kt-badge kt-badge-outline rounded-[30px]">Concept</span>
                                        @endif
                                    </td>
                                    <td class="text-secondary-foreground">{{ $dueFormatted }}</td>
                                    <td class="invoices-table__actions-col" data-no-row-link>
                                        <div class="kt-menu flex justify-center" data-kt-menu="true">
                                            <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                                <button type="button" class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" aria-label="Acties">
                                                    <i class="ki-filled ki-dots-vertical text-lg"></i>
                                                </button>
                                                <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]" data-kt-menu-dismiss="true">
                                                    <div class="kt-menu-item">
                                                        <a class="kt-menu-link" href="{{ route('admin.invoices.show', $invoice->id) }}">
                                                            <span class="kt-menu-icon"><i class="ki-filled ki-eye"></i></span>
                                                            <span class="kt-menu-title">Bekijken</span>
                                                        </a>
                                                    </div>
                                                    @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('edit-invoices'))
                                                    <div class="kt-menu-item">
                                                        <a class="kt-menu-link" href="{{ route('admin.invoices.edit', $invoice->id) }}">
                                                            <span class="kt-menu-icon"><i class="ki-filled ki-pencil"></i></span>
                                                            <span class="kt-menu-title">Bewerken</span>
                                                        </a>
                                                    </div>
                                                    @endif
                                                    @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('delete-invoices'))
                                                    <div class="kt-menu-separator"></div>
                                                    <div class="kt-menu-item">
                                                        <form action="{{ route('admin.invoices.destroy', $invoice->id) }}"
                                                              method="POST"
                                                              onsubmit="return confirm('Weet je zeker dat je deze factuur wilt verwijderen?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="kt-menu-link w-full text-left">
                                                                <span class="kt-menu-icon"><i class="ki-filled ki-trash"></i></span>
                                                                <span class="kt-menu-title">Verwijderen</span>
                                                            </button>
                                                        </form>
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="kt-card-footer admin-datatable-footer text-secondary-foreground text-sm font-medium pt-5 min-w-0">
                    <div class="admin-datatable-footer__perpage flex flex-wrap items-center gap-2">
                        Toon
                        <select class="kt-select w-24" data-admin-datatable-size="true" data-kt-select="" name="perpage">
                            <option value="10">10</option>
                            <option value="25" selected>25</option>
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
            @else
            <div class="py-10 px-3 sm:px-5 text-center text-secondary-foreground text-sm">
                Geen facturen gevonden
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    #content #invoices-table .invoices-table__actions-col {
        width: 4rem !important;
        min-width: 4rem !important;
        max-width: 4rem !important;
        padding-inline: 0.375rem !important;
        text-align: center !important;
        vertical-align: middle !important;
        white-space: nowrap;
    }

    #content #invoices-table .invoices-table__actions-col .kt-menu {
        display: flex !important;
        justify-content: center !important;
        width: 100%;
    }

    #content #invoices-table .invoices-table__actions-col .kt-menu-dropdown {
        position: fixed !important;
        z-index: 99999 !important;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', function(e) {
        const toggle = e.target.closest('#invoices-table .kt-menu-toggle');
        if (!toggle) {
            return;
        }

        const menuItem = toggle.closest('.kt-menu-item');
        const dropdown = menuItem?.querySelector('.kt-menu-dropdown');
        if (!dropdown) {
            return;
        }

        setTimeout(function() {
            const buttonRect = toggle.getBoundingClientRect();
            dropdown.style.position = 'fixed';
            dropdown.style.left = (buttonRect.right - 175) + 'px';
            dropdown.style.top = (buttonRect.bottom + 5) + 'px';
            dropdown.style.right = 'auto';
            dropdown.style.minWidth = '175px';
            dropdown.style.width = '175px';
            dropdown.style.zIndex = '99999';
        }, 10);
    });
});
</script>
@endpush
