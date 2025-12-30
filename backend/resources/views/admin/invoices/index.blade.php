@extends('admin.layouts.app')

@section('title', 'Facturen')

@section('content')
<div class="kt-container-fixed">
    <!-- Page Title -->
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

    <!-- Stats Cards -->
    <div class="kt-card mb-5">
        <div class="kt-card-content">
            <div class="flex flex-col sm:flex-row lg:px-10 py-1.5 gap-2">
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $invoiceStats['draft'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Concept
                    </span>
                </div>
                <span class="hidden sm:block not-last:border-e border-e-input my-1">
                </span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $invoiceStats['sent'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Verzonden
                    </span>
                </div>
                <span class="hidden sm:block not-last:border-e border-e-input my-1">
                </span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $invoiceStats['paid'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Betaald
                    </span>
                </div>
                <span class="hidden sm:block not-last:border-e border-e-input my-1">
                </span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $invoiceStats['total'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Totaal
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Card -->
    <div class="kt-card kt-card-grid h-full min-w-full">
        <div class="kt-card-header py-5 flex-wrap gap-2">
            <h3 class="kt-card-title text-sm pb-3 w-full">
                Facturen
            </h3>
            <div class="flex flex-col sm:flex-row items-center justify-center sm:justify-end gap-2 w-full sm:w-auto">
                <div class="flex w-full sm:w-auto justify-center sm:justify-start">
                    <label class="kt-input w-full sm:max-w-48" style="position: relative !important;">
                        <i class="ki-filled ki-magnifier"></i>
                        <form method="GET" action="{{ route('admin.invoices.index') }}" class="inline w-full">
                            <input type="text" 
                                   name="search" 
                                   value="{{ request('search') }}"
                                   placeholder="Zoek facturen" 
                                   class="min-w-0 w-full"
                                   autocomplete="off">
                        </form>
                    </label>
                </div>
                @if(request('status'))
                    <a href="{{ route('admin.invoices.index', request()->except('status')) }}" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost">
                        <i class="ki-filled ki-cross"></i>
                    </a>
                @endif
            </div>
        </div>
        <div class="kt-card-table">
            <div class="kt-scrollable-x-auto">
                <table class="kt-table kt-table-border">
                    <thead>
                        <tr>
                            <th class="w-[150px]">
                                <span class="kt-table-col">
                                    <span class="kt-table-col-label">Factuurnummer</span>
                                </span>
                            </th>
                            <th class="w-[200px]">
                                <span class="kt-table-col">
                                    <span class="kt-table-col-label">Bedrijf</span>
                                </span>
                            </th>
                            <th class="w-[150px]">
                                <span class="kt-table-col">
                                    <span class="kt-table-col-label">Bedrag</span>
                                </span>
                            </th>
                            <th class="w-[150px]">
                                <span class="kt-table-col">
                                    <span class="kt-table-col-label">Status</span>
                                </span>
                            </th>
                            <th class="w-[150px]">
                                <span class="kt-table-col">
                                    <span class="kt-table-col-label">Vervaldatum</span>
                                </span>
                            </th>
                            <th class="w-[60px] text-center">Acties</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                        <tr class="invoice-row cursor-pointer hover:bg-muted/50" data-invoice-id="{{ $invoice->id }}">
                            <td>
                                <a class="leading-none font-medium text-sm text-mono hover:text-primary" href="{{ route('admin.invoices.show', $invoice->id) }}">
                                    {{ $invoice->invoice_number }}{{ $invoice->is_partial && $invoice->partial_number ? '-' . $invoice->partial_number : '' }}
                                </a>
                            </td>
                            <td>
                                <span class="text-sm text-secondary-foreground font-normal">
                                    {{ $invoice->company->name ?? 'N/A' }}
                                </span>
                            </td>
                            <td>
                                <span class="text-sm text-secondary-foreground font-normal">
                                    €{{ number_format($invoice->total_amount, 2, ',', '.') }}
                                </span>
                            </td>
                            <td>
                                @if($invoice->status === 'paid')
                                    <span class="kt-badge kt-badge-success kt-badge-outline rounded-[30px]">
                                        Betaald
                                    </span>
                                @elseif($invoice->status === 'sent')
                                    <span class="kt-badge kt-badge-warning kt-badge-outline rounded-[30px]">
                                        Verzonden
                                    </span>
                                @elseif($invoice->status === 'in_progress')
                                    <span class="kt-badge kt-badge-info kt-badge-outline rounded-[30px]">
                                        In behandeling
                                    </span>
                                @elseif($invoice->status === 'overdue')
                                    <span class="kt-badge kt-badge-destructive kt-badge-outline rounded-[30px]">
                                        Achterstallig
                                    </span>
                                @elseif($invoice->status === 'cancelled')
                                    <span class="kt-badge kt-badge-secondary kt-badge-outline rounded-[30px]">
                                        Geannuleerd
                                    </span>
                                @else
                                    <span class="kt-badge kt-badge-outline rounded-[30px]">
                                        Concept
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span class="text-sm text-secondary-foreground font-normal">
                                    {{ $invoice->due_date->format('d M Y') }}
                                </span>
                            </td>
                            <td class="w-[60px]">
                                <div class="kt-menu flex justify-center" data-kt-menu="true">
                                    <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-placement-rtl="bottom-start" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                        <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost">
                                            <i class="ki-filled ki-dots-vertical text-lg"></i>
                                        </button>
                                        <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]" data-kt-menu-dismiss="true">
                                            <div class="kt-menu-item">
                                                <a class="kt-menu-link" href="{{ route('admin.invoices.show', $invoice->id) }}">
                                                    <span class="kt-menu-icon">
                                                        <i class="ki-filled ki-eye"></i>
                                                    </span>
                                                    <span class="kt-menu-title">Bekijken</span>
                                                </a>
                                            </div>
                                            @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('edit-invoices'))
                                            <div class="kt-menu-item">
                                                <a class="kt-menu-link" href="{{ route('admin.invoices.edit', $invoice->id) }}">
                                                    <span class="kt-menu-icon">
                                                        <i class="ki-filled ki-pencil"></i>
                                                    </span>
                                                    <span class="kt-menu-title">Bewerken</span>
                                                </a>
                                            </div>
                                            @endif
                                            @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('delete-invoices'))
                                            <div class="kt-menu-separator"></div>
                                            <div class="kt-menu-item">
                                                <form action="{{ route('admin.invoices.destroy', $invoice->id) }}" 
                                                      method="POST" 
                                                      style="display: inline;"
                                                      onsubmit="return confirm('Weet je zeker dat je deze factuur wilt verwijderen?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="kt-menu-link w-full text-left">
                                                        <span class="kt-menu-icon">
                                                            <i class="ki-filled ki-trash"></i>
                                                        </span>
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
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-10 text-sm text-secondary-foreground">
                                Geen facturen gevonden
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($invoices->hasPages())
        <div class="kt-card-footer">
            <div class="flex items-center justify-between">
                <div class="text-sm text-secondary-foreground">
                    Toont {{ $invoices->firstItem() }} tot {{ $invoices->lastItem() }} van {{ $invoices->total() }} resultaten
                </div>
                <div>
                    {{ $invoices->links() }}
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script src="{{ asset('assets/js/search-input-clear.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize KTMenu for action menus
    function initializeMenus() {
        if (window.KTMenu && typeof window.KTMenu.init === 'function') {
            try {
                window.KTMenu.init();
            } catch (e) {
                console.warn('KTMenu init error:', e);
            }
        } else {
            // Retry if KTMenu not loaded yet
            setTimeout(initializeMenus, 100);
        }
    }
    
    // Initialize immediately and after a short delay
    initializeMenus();
    setTimeout(initializeMenus, 300);
    
    // Position dropdown using fixed positioning to avoid stacking context issues
    document.addEventListener('click', function(e) {
        const toggle = e.target.closest('.kt-menu-toggle');
        if (toggle) {
            const menuItem = toggle.closest('.kt-menu-item');
            if (menuItem) {
                const dropdown = menuItem.querySelector('.kt-menu-dropdown');
                if (dropdown) {
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
                }
            }
        }
    });
    
    // Make table rows clickable (except actions column) - use event delegation
    const invoicesTable = document.querySelector('table');
    const invoicesTableBody = invoicesTable ? invoicesTable.querySelector('tbody') : null;
    if (invoicesTableBody) {
        // Use event delegation on the tbody to handle dynamically added rows
        invoicesTableBody.addEventListener('click', function(e) {
            const row = e.target.closest('tr.invoice-row');
            if (!row) return;
            
            // Don't navigate if clicking on actions column or menu
            if (e.target.closest('td:last-child') || e.target.closest('.kt-menu') || e.target.closest('button') || e.target.closest('a')) {
                return;
            }
            
            // Get invoice ID from data attribute
            const invoiceId = row.getAttribute('data-invoice-id');
            if (invoiceId) {
                window.location.href = '/admin/invoices/' + invoiceId;
            }
        });
    }
});
</script>
@endpush

@push('styles')
<style>
    tr.invoice-row {
        cursor: pointer;
    }
    tr.invoice-row:hover {
        background-color: var(--muted);
    }
    @supports (color: color-mix(in lab, red, red)) {
        tr.invoice-row:hover {
            background-color: color-mix(in oklab, var(--muted) 50%, transparent);
        }
    }
    
    /* Ensure dropdown can overflow table cells without stretching them */
    table td:last-child {
        position: relative;
        overflow: visible !important;
        width: 60px !important;
        min-width: 60px !important;
        max-width: 60px !important;
    }

    table td:last-child .kt-menu-item {
        position: static;
    }

    table td:last-child .kt-menu-dropdown {
        position: fixed !important;
        z-index: 99999 !important;
    }
    
    table td:last-child .kt-menu-item.show {
        z-index: 99999 !important;
    }
    
    table td:last-child .kt-menu-item.show .kt-menu-dropdown {
        z-index: 99999 !important;
    }
    
    /* Ensure table and card allow overflow */
    .kt-card-table {
        overflow: visible !important;
    }
    
    .kt-scrollable-x-auto {
        overflow-x: auto !important;
        overflow-y: visible !important;
    }
    
    /* Ensure thead and th allow overflow */
    table thead th:last-child {
        overflow: visible !important;
    }
    
    table thead {
        overflow: visible !important;
    }
</style>
@endpush

@endsection

