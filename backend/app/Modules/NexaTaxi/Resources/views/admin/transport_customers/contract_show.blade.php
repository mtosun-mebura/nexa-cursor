@extends('admin.layouts.app')

@section('title', $contract->name)

@section('content')
@php
    $contractWorkspaceTab = request('tab', 'overzicht');
    if ($errors->hasAny(['account_holder', 'iban', 'bic', 'mandate_reference', 'status', 'signed_at'])) {
        $contractWorkspaceTab = 'overzicht';
    } elseif ($errors->has('period') || request('tab') === 'facturen') {
        $contractWorkspaceTab = 'facturen';
    }
    if (! in_array($contractWorkspaceTab, ['overzicht', 'facturen'], true)) {
        $contractWorkspaceTab = 'overzicht';
    }
    $contractWorkspaceCounts = [
        'passengers' => $passengerCount,
        'groups' => $groupCount,
        'bookings' => $individualBookingCount,
        'invoices' => $contractInvoices->count(),
    ];
    $passengersUrl = route('admin.taxi.transport_passengers.index', [$customer->id, $contract->id]);
    $groupsUrl = route('admin.taxi.transport_groups.index', [$customer->id, $contract->id]);
    $bookingsUrl = route('admin.taxi.transport_individual_bookings.index', [$customer->id, $contract->id]);
    $facturenUrl = route('admin.taxi.transport_customers.contract_show', [$customer->id, $contract->id, 'tab' => 'facturen']);
@endphp

<div class="kt-container-fixed min-w-0">
    @include('taxi::admin.transport_customers.partials.contract-workspace-header')

    @if($errors->any())
        <div class="kt-alert kt-alert-danger mb-5" role="alert">
            <ul class="list-disc list-inside mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-5 lg:gap-7.5 pt-5">
        @if($contractWorkspaceTab === 'overzicht')
            <div class="contract-hub-grid">
                <a href="{{ $passengersUrl }}" class="contract-hub-tile">
                    <span class="contract-hub-tile__label">Passagiers</span>
                    <span class="contract-hub-tile__value">{{ $passengerCount }}</span>
                    <span class="contract-hub-tile__meta">Personen die meereizen · beheer namen &amp; adressen</span>
                </a>
                <a href="{{ $groupsUrl }}" class="contract-hub-tile">
                    <span class="contract-hub-tile__label">Groepen &amp; routes</span>
                    <span class="contract-hub-tile__value">{{ $groupCount }}</span>
                    <span class="contract-hub-tile__meta">Vaste heen- en terugritten · leden &amp; routeplanner</span>
                </a>
                <a href="{{ $bookingsUrl }}" class="contract-hub-tile">
                    <span class="contract-hub-tile__label">Individuele ritten</span>
                    <span class="contract-hub-tile__value">{{ $individualBookingCount }}</span>
                    <span class="contract-hub-tile__meta">Eenmalige of losse contractritten</span>
                </a>
                <a href="{{ $facturenUrl }}" class="contract-hub-tile">
                    <span class="contract-hub-tile__label">Facturen</span>
                    <span class="contract-hub-tile__value">{{ $contractInvoices->count() }}</span>
                    <span class="contract-hub-tile__meta">Maandfacturen genereren &amp; verzenden</span>
                </a>
            </div>

            @include('taxi::admin.transport_customers.partials.contract-show-details')
            @include('taxi::admin.transport_customers.partials.contract-show-mandate')
        @else
            @include('taxi::admin.transport_customers.partials.contract-show-invoices')
        @endif
    </div>
</div>
@endsection

@push('styles')
@include('taxi::admin.transport_customers.partials.contract-workspace-styles')
<style>
    #content .contract-detail-table {
        width: 100%;
        table-layout: fixed;
    }

    #content .contract-detail-table td.contract-detail-table__label {
        width: 14rem;
        min-width: 14rem;
        max-width: 14rem;
        vertical-align: top;
    }

    #content .contract-detail-table td:nth-child(2) {
        min-width: 0;
        overflow-wrap: break-word;
    }

    #content #transport-contract-invoices-table .contract-invoices-table__actions-col {
        width: 4.5rem !important;
        min-width: 4.5rem !important;
        max-width: 4.5rem !important;
        padding-inline: 0.25rem !important;
        text-align: center !important;
        vertical-align: middle !important;
        white-space: nowrap;
        overflow: visible !important;
        font-size: 0.8125rem;
    }

    #content #transport-contract-invoices-table .contract-invoices-table__actions-col .kt-menu {
        display: flex !important;
        justify-content: center !important;
        width: 100%;
        margin-inline: auto;
    }

    #transport-contract-invoices-card,
    #transport-contract-invoices-card .kt-card-content {
        overflow: visible !important;
    }

    #transport-contract-invoices-card.transport-contract-invoices-card--menu-open,
    #transport-contract-invoices-card:has(.kt-menu-item.show) {
        position: relative;
        z-index: 120;
    }

    .contract-invoices-table-wrap .contract-invoices-table__actions-col .kt-menu-dropdown {
        position: fixed !important;
        z-index: 200000 !important;
    }

    .contract-invoices-table-wrap .contract-invoices-table__actions-col .kt-menu-item.show .kt-menu-dropdown,
    .contract-invoices-table-wrap .contract-invoices-table__actions-col .kt-menu-item[data-kt-menu-item-toggle="dropdown"].show .kt-menu-dropdown {
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
    }

    .contract-invoices-table-wrap .contract-invoices-table__actions-col .kt-menu-item.show {
        z-index: 200000 !important;
    }

    .contract-invoices-table-wrap .kt-scrollable-x-auto {
        overflow-x: auto !important;
        overflow-y: visible !important;
    }

    .contract-invoices-table-wrap .kt-menu-default.kt-menu-dropdown {
        overflow: hidden;
        box-sizing: border-box;
        padding: 0.25rem;
    }

    .contract-invoices-table-wrap .kt-menu-default .kt-menu-item {
        width: 100%;
        min-width: 0;
    }

    .contract-invoices-table-wrap .kt-menu-default .kt-menu-item > form.contents {
        display: contents;
    }

    .contract-invoices-table-wrap .kt-menu-default .kt-menu-item .kt-menu-link {
        display: flex;
        align-items: center;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
        margin-inline: 0 !important;
        border: 0;
        background: transparent;
        font: inherit;
        color: inherit;
        text-decoration: none;
        cursor: pointer;
        appearance: none;
        -webkit-appearance: none;
        border-radius: calc(var(--radius) - 2px);
        padding-inline: calc(var(--spacing) * 2);
        padding-block: calc(var(--spacing) * 2);
    }

    .contract-invoices-table-wrap .kt-menu-default .kt-menu-item .kt-menu-link:hover,
    .contract-invoices-table-wrap .kt-menu-default .kt-menu-item .kt-menu-link:focus-visible {
        background-color: var(--accent);
    }

    .contract-invoices-table-wrap .kt-menu-default .kt-menu-item .kt-menu-link:hover .kt-menu-title,
    .contract-invoices-table-wrap .kt-menu-default .kt-menu-item .kt-menu-link:focus-visible .kt-menu-title {
        color: var(--mono);
    }

    .contract-invoices-table-wrap .kt-menu-default .kt-menu-item .kt-menu-link:hover .kt-menu-icon svg,
    .contract-invoices-table-wrap .kt-menu-default .kt-menu-item .kt-menu-link:focus-visible .kt-menu-icon svg {
        color: var(--primary);
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var invoicePeriodMonth = document.getElementById('contract-invoice-period-month');
    var invoicePeriodYear = document.getElementById('contract-invoice-period-year');
    var invoicePeriodHidden = document.getElementById('contract-invoice-period');
    var invoiceGenerateForm = document.getElementById('transport-contract-invoice-generate-form');

    function syncContractInvoicePeriod() {
        if (!invoicePeriodMonth || !invoicePeriodYear || !invoicePeriodHidden) return;
        invoicePeriodHidden.value = invoicePeriodYear.value + '-' + invoicePeriodMonth.value;
    }

    if (invoicePeriodMonth && invoicePeriodYear) {
        invoicePeriodMonth.addEventListener('change', syncContractInvoicePeriod);
        invoicePeriodYear.addEventListener('change', syncContractInvoicePeriod);
        syncContractInvoicePeriod();
    }

    if (invoiceGenerateForm) {
        invoiceGenerateForm.addEventListener('submit', syncContractInvoicePeriod);
    }

    var mandateEditBtn = document.getElementById('transport-contract-mandate-edit-btn');
    var mandateCancelBtn = document.getElementById('transport-contract-mandate-cancel-btn');
    var mandateView = document.getElementById('transport-contract-mandate-view');
    var mandateForm = document.getElementById('transport-contract-mandate-form');

    function showMandateEditMode() {
        if (!mandateView || !mandateForm) return;
        mandateView.classList.add('hidden');
        mandateForm.classList.remove('hidden');
        if (mandateEditBtn) mandateEditBtn.classList.add('hidden');
        var firstInput = mandateForm.querySelector('input, select, textarea');
        if (firstInput) firstInput.focus();
    }

    function showMandateViewMode() {
        if (!mandateView || !mandateForm) return;
        mandateForm.reset();
        mandateForm.classList.add('hidden');
        mandateView.classList.remove('hidden');
        if (mandateEditBtn) mandateEditBtn.classList.remove('hidden');
    }

    if (mandateEditBtn && mandateView && mandateForm) {
        mandateEditBtn.addEventListener('click', showMandateEditMode);
    }

    if (mandateCancelBtn && mandateView && mandateForm) {
        mandateCancelBtn.addEventListener('click', showMandateViewMode);
    }

    function resetContractInvoiceDropdown(dropdown) {
        if (!dropdown) return;
        dropdown.style.position = '';
        dropdown.style.left = '';
        dropdown.style.top = '';
        dropdown.style.minWidth = '';
        dropdown.style.width = '';
        dropdown.style.zIndex = '';
        dropdown.style.display = '';
        dropdown.style.visibility = '';
        dropdown.style.opacity = '';
    }

    function positionContractInvoiceDropdown(toggle, dropdown) {
        var rect = toggle.getBoundingClientRect();
        dropdown.style.position = 'fixed';
        dropdown.style.left = Math.max(8, rect.right - 175) + 'px';
        dropdown.style.top = (rect.bottom + 5) + 'px';
        dropdown.style.minWidth = '175px';
        dropdown.style.width = '175px';
        dropdown.style.zIndex = '200000';
        dropdown.style.display = 'flex';
        dropdown.style.visibility = 'visible';
        dropdown.style.opacity = '1';
    }

    var transportContractInvoicesCard = document.getElementById('transport-contract-invoices-card');

    function syncTransportContractInvoicesCardMenuState() {
        if (!transportContractInvoicesCard) return;
        transportContractInvoicesCard.classList.toggle(
            'transport-contract-invoices-card--menu-open',
            !!transportContractInvoicesCard.querySelector('.kt-menu-item[data-kt-menu-item-toggle="dropdown"].show')
        );
    }

    function repositionOpenContractInvoiceMenus() {
        document.querySelectorAll('.contract-invoices-table-wrap .kt-menu-item[data-kt-menu-item-toggle="dropdown"].show').forEach(function(menuItem) {
            var toggle = menuItem.querySelector('.kt-menu-toggle');
            var dropdown = menuItem.querySelector('.kt-menu-dropdown');
            if (toggle && dropdown) {
                positionContractInvoiceDropdown(toggle, dropdown);
            }
        });
    }

    if (!window._contractInvoiceMenuListenersBound) {
        window._contractInvoiceMenuListenersBound = true;
        window.addEventListener('scroll', repositionOpenContractInvoiceMenus, true);
        document.addEventListener('scroll', repositionOpenContractInvoiceMenus, true);
        window.addEventListener('resize', repositionOpenContractInvoiceMenus);
    }

    function initContractInvoiceMenus() {
        document.querySelectorAll('.contract-invoices-table-wrap .kt-menu-toggle').forEach(function(toggle) {
            if (toggle._contractInvoiceMenuBound) return;
            toggle._contractInvoiceMenuBound = true;
            toggle.addEventListener('click', function(e) {
                e.stopPropagation();
                e.preventDefault();
                var menuItem = toggle.closest('.kt-menu-item[data-kt-menu-item-toggle="dropdown"]');
                if (!menuItem) return;
                var dropdown = menuItem.querySelector('.kt-menu-dropdown');
                if (!dropdown) return;
                var isShowing = menuItem.classList.contains('show');
                document.querySelectorAll('.contract-invoices-table-wrap .kt-menu-item[data-kt-menu-item-toggle="dropdown"].show').forEach(function(item) {
                    if (item === menuItem) return;
                    item.classList.remove('show');
                    resetContractInvoiceDropdown(item.querySelector('.kt-menu-dropdown'));
                });
                if (!isShowing) {
                    menuItem.classList.add('show');
                    positionContractInvoiceDropdown(toggle, dropdown);
                } else {
                    menuItem.classList.remove('show');
                    resetContractInvoiceDropdown(dropdown);
                }
                syncTransportContractInvoicesCardMenuState();
            });
        });
    }
    initContractInvoiceMenus();
    setTimeout(initContractInvoiceMenus, 300);
    setTimeout(initContractInvoiceMenus, 1200);

    document.addEventListener('click', function(e) {
        if (e.target.closest('.contract-invoices-table-wrap .kt-menu')) return;
        document.querySelectorAll('.contract-invoices-table-wrap .kt-menu-item[data-kt-menu-item-toggle="dropdown"].show').forEach(function(item) {
            item.classList.remove('show');
            resetContractInvoiceDropdown(item.querySelector('.kt-menu-dropdown'));
        });
        syncTransportContractInvoicesCardMenuState();
    });
});
</script>
@endpush
