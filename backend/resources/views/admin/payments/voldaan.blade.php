@extends('admin.layouts.app')

@section('title', 'Voldane Betalingen')

@section('content')
<div class="kt-container-fixed">
    <!-- Page Title -->
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                Voldane Betalingen
            </h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Overzicht van alle betaalde betalingen en inkomsten
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="kt-btn kt-btn-outline" href="{{ route('admin.payments.index') }}">
                <i class="ki-filled ki-arrow-left text-base me-2"></i>
                Terug naar Overzicht
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid lg:grid-cols-2 gap-5 lg:gap-7.5 mb-5">
        <div class="kt-card">
            <div class="kt-card-content p-5">
                <div class="flex flex-col gap-1">
                    <span class="text-sm font-normal text-secondary-foreground">
                        Totaal Betaald
                    </span>
                    <div class="flex items-center gap-2.5">
                        <span class="text-3xl font-semibold text-mono">
                            {{ number_format($payments->total()) }}
                        </span>
                        <span class="kt-badge kt-badge-outline kt-badge-success kt-badge-sm">
                            Betaald
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="kt-card">
            <div class="kt-card-content p-5">
                <div class="flex flex-col gap-1">
                    <span class="text-sm font-normal text-secondary-foreground">
                        Totaal Omzet
                    </span>
                    <div class="flex items-center gap-2.5">
                        <span class="text-3xl font-semibold text-mono">
                            €{{ number_format($payments->sum('amount'), 2, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Chart -->
    <div class="kt-card mb-5">
        <div class="kt-card-header">
            <h3 class="kt-card-title">
                Inkomsten Overzicht (Laatste 12 Maanden)
            </h3>
        </div>
        <div class="kt-card-content flex flex-col justify-end items-stretch grow px-3 py-1">
            <div id="revenue_chart" style="min-height: 300px;"></div>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="kt-card kt-card-grid h-full min-w-full">
        <div class="kt-card-header">
            <h3 class="kt-card-title">
                Betaalde Betalingen
            </h3>
            <label class="kt-input max-w-48" style="position: relative !important;">
                <i class="ki-filled ki-magnifier"></i>
                <form method="GET" action="{{ route('admin.payments.voldaan') }}" class="inline">
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}"
                           placeholder="Zoek bedrijven" 
                           class="min-w-0"
                           autocomplete="off">
                </form>
            </label>
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
                                    <span class="kt-table-col-label">Betaald op</span>
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
                                    {{ $payment->paid_at ? $payment->paid_at->format('d M, Y') : 'N/A' }}
                                </span>
                            </td>
                            <td>
                                <span class="kt-badge kt-badge-success kt-badge-outline rounded-[30px]">
                                    Betaald
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
                                Geen betaalde betalingen gevonden
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

@push('scripts')
<script src="{{ asset('assets/js/search-input-clear.js') }}"></script>
<script src="{{ asset('assets/vendors/apexcharts/apexcharts.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const chartLabels = @json($chartLabels);
        const chartData = @json($chartData);

        if (chartLabels.length > 0 && typeof ApexCharts !== 'undefined') {
            const options = {
                series: [{
                    name: 'Inkomsten',
                    data: chartData
                }],
                chart: {
                    type: 'area',
                    height: 300,
                    toolbar: {
                        show: false
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                xaxis: {
                    categories: chartLabels
                },
                yaxis: {
                    labels: {
                        formatter: function (val) {
                            return '€' + val.toFixed(2);
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function (val) {
                            return '€' + val.toFixed(2);
                        }
                    }
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.7,
                        opacityTo: 0.9,
                        stops: [0, 90, 100]
                    }
                },
                colors: ['#009ef7']
            };

            const chart = new ApexCharts(document.querySelector("#revenue_chart"), options);
            chart.render();
        }
    });
</script>
@endpush
@endsection




