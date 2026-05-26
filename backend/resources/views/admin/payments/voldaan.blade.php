@extends('admin.layouts.app')

@section('title', 'Voldane Betalingen')

@section('content')
@php
    $pageTotal = collect($payments->items())->sum(fn ($p) => (float) $p->amount);
@endphp
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Voldane Betalingen</h1>
            <div class="text-sm text-secondary-foreground">
                Tenants met actieve betaalmodule — voldane betalingen, ritten en facturen (status betaald)
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="kt-btn kt-btn-outline" href="{{ route('admin.payments.index') }}">
                <i class="ki-filled ki-arrow-left text-base me-2"></i>
                Terug naar Overzicht
            </a>
        </div>
    </div>

    @include('admin.payments.partials.tenant-filter', ['filterRoute' => 'admin.payments.voldaan'])

    <div class="grid lg:grid-cols-2 gap-5 mb-5">
        <div class="kt-card">
            <div class="kt-card-content p-5">
                <span class="text-sm text-secondary-foreground">Voldaan (filter)</span>
                <div class="text-3xl font-semibold text-mono mt-1">{{ number_format($payments->total()) }}</div>
            </div>
        </div>
        <div class="kt-card">
            <div class="kt-card-content p-5">
                <span class="text-sm text-secondary-foreground">Omzet op deze pagina</span>
                <div class="text-3xl font-semibold text-mono mt-1">€{{ number_format($pageTotal, 2, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="kt-card mb-5">
        <div class="kt-card-header">
            <h3 class="kt-card-title">Inkomsten (laatste 12 maanden)</h3>
        </div>
        <div class="kt-card-content">
            <div id="payments_voldaan_revenue_chart" style="min-height: 300px;"></div>
        </div>
    </div>

    <div class="kt-card kt-card-grid min-w-full">
        <div class="kt-card-header">
            <h3 class="kt-card-title">Voldane betalingen</h3>
            <form method="GET" action="{{ route('admin.payments.voldaan') }}" class="flex flex-wrap items-center gap-2">
                @if(request('company_id'))
                    <input type="hidden" name="company_id" value="{{ request('company_id') }}">
                @endif
                <label class="kt-input max-w-48">
                    <i class="ki-filled ki-magnifier"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Zoeken…" class="min-w-0" autocomplete="off">
                </label>
            </form>
        </div>
        <div class="kt-card-table">
            <div class="kt-scrollable-x-auto">
                <table class="kt-table kt-table-border">
                    <thead>
                        <tr>
                            <th>Tenant</th>
                            <th>Module</th>
                            <th>Bedrag</th>
                            <th>Referentie</th>
                            <th>Betaald op</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @include('admin.payments.partials.payment-rows', [
                            'payments' => $payments,
                            'emptyMessage' => 'Geen voldane betalingen voor deze tenants.',
                            'paymentsReturnUrl' => request()->getRequestUri(),
                        ])
                    </tbody>
                </table>
            </div>
        </div>
        @if($payments->hasPages())
            <div class="kt-card-footer flex items-center justify-between">
                <div class="text-sm text-secondary-foreground">
                    {{ $payments->firstItem() }}–{{ $payments->lastItem() }} van {{ $payments->total() }}
                </div>
                {{ $payments->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
(function () {
    const chartLabels = @json($chartLabels ?? []);
    const chartData = @json($chartData ?? []);

    function renderPaymentsVoldaanChart() {
        const el = document.getElementById('payments_voldaan_revenue_chart');
        if (!el || typeof ApexCharts === 'undefined' || chartLabels.length === 0) {
            return;
        }

        if (el._paymentsVoldaanChart) {
            el._paymentsVoldaanChart.destroy();
            el._paymentsVoldaanChart = null;
        }
        el.innerHTML = '';

        const chart = new ApexCharts(el, {
            series: [{ name: 'Inkomsten', data: chartData }],
            chart: { type: 'area', height: 300, toolbar: { show: false } },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2 },
            xaxis: { categories: chartLabels },
            yaxis: { labels: { formatter: function (val) { return '€' + val.toFixed(0); } } },
            tooltip: { y: { formatter: function (val) { return '€' + val.toFixed(2); } } },
            colors: ['#009ef7'],
        });
        chart.render();
        el._paymentsVoldaanChart = chart;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', renderPaymentsVoldaanChart, { once: true });
    } else {
        renderPaymentsVoldaanChart();
    }
})();
</script>
@endpush
@endsection
