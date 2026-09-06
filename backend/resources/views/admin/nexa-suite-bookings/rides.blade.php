@extends('admin.layouts.app')

@section('title', 'NEXA Suite ritten')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">NEXA Suite ritten</h1>
    </div>

    @include('admin.nexa-suite-bookings.partials.nav')

    <div class="kt-card w-full min-w-0">
        <div class="kt-card-header px-5 py-5 flex-wrap gap-3 justify-between items-center">
            <h3 class="kt-card-title text-sm mb-0">Alle centrale boekingen</h3>
            <form method="GET" action="{{ route('admin.nexa-suite-bookings.rides') }}" class="admin-filter-panel flex flex-wrap items-center gap-2">
                @include('admin.partials.month-picker-input', ['name' => 'period', 'value' => $period])
                <select class="kt-select w-full sm:w-48" name="company_id">
                    <option value="">Alle tenants</option>
                    @foreach($allCompanies as $company)
                        <option value="{{ $company->id }}" @selected((string) ($filterCompanyId ?? '') === (string) $company->id)>{{ $company->name }}</option>
                    @endforeach
                </select>
                <select class="kt-select w-full sm:w-40" name="status">
                    <option value="">Alle statussen</option>
                    @foreach($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="kt-btn kt-btn-outline">Filter</button>
            </form>
        </div>
        <div class="kt-card-content p-0 min-w-0">
            @if($rides->isEmpty())
                <p class="p-5 text-sm text-muted-foreground mb-0">Geen NEXA Suite-ritten in deze periode.</p>
            @else
                <div class="kt-scrollable-x-auto admin-table-scroll-wrap min-w-0">
                    <table class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full">
                        <thead>
                            <tr>
                                <th class="text-left">Datum</th>
                                <th class="text-left">Tenant</th>
                                <th class="text-left">Klant</th>
                                <th class="text-left">Route</th>
                                <th class="text-left">Status</th>
                                <th class="text-left">Bedrag</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rides as $ride)
                                <tr>
                                    <td class="whitespace-nowrap">{{ $ride->pickup_at?->format('d-m-Y H:i') }}</td>
                                    <td>{{ $companies->get($ride->company_id)?->name ?? '—' }}</td>
                                    <td>{{ $ride->customer_name }}</td>
                                    <td class="max-w-xs truncate" title="{{ $ride->pickup_address }} → {{ $ride->dropoff_address }}">{{ $ride->pickup_address }} → {{ $ride->dropoff_address }}</td>
                                    <td>{{ $statusLabels[$ride->status] ?? $ride->status }}</td>
                                    <td class="whitespace-nowrap">€ {{ number_format((float) ($ride->final_price ?? $ride->quoted_price ?? 0), 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-5">{{ $rides->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
