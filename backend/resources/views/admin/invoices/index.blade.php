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
            <div class="flex lg:px-10 py-1.5 gap-2">
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $invoiceStats['draft'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Concept
                    </span>
                </div>
                <span class="not-last:border-e border-e-input my-1">
                </span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $invoiceStats['sent'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Verzonden
                    </span>
                </div>
                <span class="not-last:border-e border-e-input my-1">
                </span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $invoiceStats['paid'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Betaald
                    </span>
                </div>
                <span class="not-last:border-e border-e-input my-1">
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
        <div class="kt-card-header">
            <h3 class="kt-card-title">
                Facturen
            </h3>
            <div class="flex items-center gap-2">
                <label class="kt-input max-w-48" style="position: relative !important;">
                    <i class="ki-filled ki-magnifier"></i>
                    <form method="GET" action="{{ route('admin.invoices.index') }}" class="inline">
                        <input type="text" 
                               name="search" 
                               value="{{ request('search') }}"
                               placeholder="Zoek facturen" 
                               class="min-w-0"
                               autocomplete="off">
                    </form>
                </label>
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
                            <th class="w-[150px]">
                                <span class="kt-table-col">
                                    <span class="kt-table-col-label">Acties</span>
                                </span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                        <tr>
                            <td>
                                <a class="leading-none font-medium text-sm text-mono hover:text-primary" href="{{ route('admin.invoices.show', $invoice->id) }}">
                                    {{ $invoice->invoice_number }}
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
                                    {{ $invoice->due_date->format('d M, Y') }}
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.invoices.show', $invoice->id) }}" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" title="Bekijk factuur">
                                        <i class="ki-filled ki-eye"></i>
                                    </a>
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
@endpush

@endsection

