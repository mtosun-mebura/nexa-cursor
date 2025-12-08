@extends('admin.layouts.app')

@section('title', 'Openstaande Betalingen')

@section('content')
<div class="kt-container-fixed">
    <!-- Page Title -->
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                Openstaande Betalingen
            </h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Overzicht van alle openstaande betalingen
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="kt-btn kt-btn-outline" href="{{ route('admin.payments.index') }}">
                <i class="ki-filled ki-arrow-left text-base me-2"></i>
                Terug naar Overzicht
            </a>
        </div>
    </div>

    <!-- Main Card -->
    <div class="kt-card kt-card-grid h-full min-w-full">
        <div class="kt-card-header">
            <h3 class="kt-card-title">
                Openstaande Betalingen
            </h3>
            <div class="kt-input max-w-48">
                <i class="ki-filled ki-magnifier"></i>
                <form method="GET" action="{{ route('admin.payments.openstaand') }}" class="inline">
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}"
                           placeholder="Zoek bedrijven" 
                           class="min-w-0"
                           autocomplete="off">
                </form>
            </div>
        </div>
        <div class="kt-card-table">
            <div class="kt-scrollable-x-auto">
                <table class="kt-table kt-table-border">
                    <thead>
                        <tr>
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
                                    <span class="kt-table-col-label">Factuur</span>
                                </span>
                            </th>
                            <th class="w-[150px]">
                                <span class="kt-table-col">
                                    <span class="kt-table-col-label">Datum</span>
                                </span>
                            </th>
                            <th class="w-[150px]">
                                <span class="kt-table-col">
                                    <span class="kt-table-col-label">Status</span>
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
                        @forelse($payments as $payment)
                        <tr>
                            <td>
                                <div class="flex flex-col gap-1">
                                    <a class="leading-none font-medium text-sm text-mono hover:text-primary" href="{{ route('admin.companies.show', $payment->company_id) }}">
                                        {{ $payment->company->name ?? 'N/A' }}
                                    </a>
                                </div>
                            </td>
                            <td>
                                <span class="text-sm text-secondary-foreground font-normal">
                                    €{{ number_format($payment->amount, 2, ',', '.') }}
                                </span>
                            </td>
                            <td>
                                @if($payment->invoice)
                                    <a class="text-sm text-primary hover:underline" href="{{ route('admin.invoices.show', $payment->invoice_id) }}">
                                        {{ $payment->invoice->invoice_number }}
                                    </a>
                                @else
                                    <span class="text-sm text-secondary-foreground">Geen factuur</span>
                                @endif
                            </td>
                            <td>
                                <span class="text-sm text-secondary-foreground font-normal">
                                    {{ $payment->created_at->format('d M, Y') }}
                                </span>
                            </td>
                            <td>
                                <span class="kt-badge kt-badge-warning kt-badge-outline rounded-[30px]">
                                    Openstaand
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    @if($payment->invoice)
                                        <a href="{{ route('admin.invoices.show', $payment->invoice_id) }}" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" title="Bekijk factuur">
                                            <i class="ki-filled ki-eye"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-10 text-sm text-secondary-foreground">
                                Geen openstaande betalingen gevonden
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($payments->hasPages())
        <div class="kt-card-footer">
            <div class="flex items-center justify-between">
                <div class="text-sm text-secondary-foreground">
                    Toont {{ $payments->firstItem() }} tot {{ $payments->lastItem() }} van {{ $payments->total() }} resultaten
                </div>
                <div>
                    {{ $payments->links() }}
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection




