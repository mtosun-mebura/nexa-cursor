@extends('admin.layouts.app')

@section('title', 'SaaS-facturen')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                SaaS-facturen
            </h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Platform → tenant facturatie
            </div>
        </div>
        <div class="flex items-center gap-2.5">
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

    <div class="kt-card kt-card-grid w-full min-w-0">
        <div class="kt-card-header py-5 flex-wrap gap-3 justify-between items-center">
            <h3 class="kt-card-title text-sm mb-0">
                Overzicht SaaS-facturen
            </h3>
            <form method="GET" action="{{ route('admin.platform-billing.invoices.index') }}" class="admin-filter-panel flex flex-wrap items-center gap-2">
                <label class="kt-input w-full sm:w-56 min-w-0">
                    <i class="ki-filled ki-magnifier"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Zoek nummer of tenant…" class="min-w-0" autocomplete="off">
                </label>
                @if($companies->isNotEmpty())
                    <select class="kt-select w-full sm:w-48" name="company_id">
                        <option value="">Alle tenants</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>{{ $company->name }}</option>
                        @endforeach
                    </select>
                @endif
                <select class="kt-select w-full sm:w-40" name="status">
                    <option value="">Alle statussen</option>
                    @foreach($statusOptions as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
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
                            <th class="text-secondary-foreground font-normal text-left">Nummer</th>
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
                            <td class="platform-billing-invoices__number font-medium text-mono">{{ $invoice->invoice_number }}</td>
                            <td class="platform-billing-invoices__tenant">{{ $invoice->company?->name }}</td>
                            <td class="platform-billing-invoices__period whitespace-nowrap">{{ $invoice->billing_period }}</td>
                            <td class="platform-billing-invoices__total whitespace-nowrap tabular-nums">€ {{ number_format((float) $invoice->total_amount, 2, ',', '.') }}</td>
                            <td class="platform-billing-invoices__status whitespace-nowrap">{{ $invoice->status }}</td>
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
                            <td colspan="6" class="p-5 text-secondary-foreground">Geen SaaS-facturen gevonden.</td>
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
<style>
    #content #platform-billing-invoices-table col.platform-billing-invoices-col-number {
        width: 9rem;
    }

    #content #platform-billing-invoices-table col.platform-billing-invoices-col-period {
        width: 7rem;
    }

    #content #platform-billing-invoices-table col.platform-billing-invoices-col-total {
        width: 7rem;
    }

    #content #platform-billing-invoices-table col.platform-billing-invoices-col-status {
        width: 6rem;
    }

    #content #platform-billing-invoices-table .platform-billing-invoices__number,
    #content #platform-billing-invoices-table .platform-billing-invoices__tenant {
        max-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        vertical-align: middle;
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('assets/js/search-input-clear.js') }}"></script>
@endpush
