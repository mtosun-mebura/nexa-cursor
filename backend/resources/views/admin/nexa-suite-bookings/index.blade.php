@extends('admin.layouts.app')

@section('title', 'NEXA Suite ritten')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">NEXA Suite ritten</h1>
            <div class="text-sm text-secondary-foreground">Centrale boekingen vanaf nexasuite.nl, per tenant</div>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <form method="POST" action="{{ route('admin.nexa-suite-bookings.run-dunning') }}">
                @csrf
                <button type="submit" class="kt-btn kt-btn-outline">Aanmaningen nu draaien</button>
            </form>
            <form method="POST" action="{{ route('admin.nexa-suite-bookings.run-now') }}">
                @csrf
                <button type="submit" class="kt-btn kt-btn-outline">Facturatie nu draaien</button>
            </form>
        </div>
    </div>

    @include('admin.nexa-suite-bookings.partials.nav')

    <div class="kt-card w-full min-w-0 mb-5">
        <div class="kt-card-header px-5 py-5 flex-wrap gap-3 justify-between items-center">
            <h3 class="kt-card-title text-sm mb-0">Gereden ritten per tenant</h3>
            <form method="GET" action="{{ route('admin.nexa-suite-bookings.index') }}" class="admin-filter-panel flex flex-wrap items-center gap-2">
                @include('admin.partials.month-picker-input', ['name' => 'period', 'value' => $period])
                <select class="kt-select w-full sm:w-48" name="company_id">
                    <option value="">Alle tenants</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" @selected((string) ($filterCompanyId ?? '') === (string) $company->id)>{{ $company->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="kt-btn kt-btn-outline">Filter</button>
            </form>
        </div>
        <div class="kt-card-content p-0 min-w-0">
            @if($rows->isEmpty())
                <p class="p-5 text-sm text-muted-foreground mb-0">Geen voltooide NEXA Suite-ritten in deze periode.</p>
            @else
                <div class="kt-scrollable-x-auto admin-table-scroll-wrap min-w-0">
                    <table class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full">
                        <thead>
                            <tr>
                                <th class="text-left">Tenant</th>
                                <th class="text-left">Ritten</th>
                                <th class="text-left">Ritomzet</th>
                                <th class="text-left">Fee ({{ (int) $settings->fee_percent }}%)</th>
                                <th class="text-left">Factuur</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $row)
                                <tr>
                                    <td>{{ $row['company']?->name ?? 'Tenant #'.$row['company_id'] }}</td>
                                    <td>{{ $row['ride_count'] }}</td>
                                    <td>€ {{ number_format($row['rides_subtotal'], 2, ',', '.') }}</td>
                                    <td>€ {{ number_format($row['fee_amount'], 2, ',', '.') }}</td>
                                    <td>
                                        @if($row['invoice'])
                                            <a class="text-primary hover:underline" href="{{ route('admin.nexa-suite-bookings.invoices.show', $row['invoice']) }}">{{ $row['invoice']->invoice_number }}</a>
                                            <span class="kt-badge kt-badge-outline rounded-[30px] {{ $row['invoice']->statusBadgeClass() }}">{{ $row['invoice']->statusLabel() }}</span>
                                        @else
                                            <span class="text-muted-foreground">Nog geen factuur</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(! $row['invoice'])
                                            <form method="POST" action="{{ route('admin.nexa-suite-bookings.generate') }}" class="inline">
                                                @csrf
                                                <input type="hidden" name="period" value="{{ $period }}">
                                                <input type="hidden" name="company_id" value="{{ $row['company_id'] }}">
                                                <input type="hidden" name="send" value="0">
                                                <button type="submit" class="kt-btn kt-btn-outline kt-btn-sm">Factuur maken</button>
                                            </form>
                                        @elseif($row['invoice']->status === 'draft')
                                            <form method="POST" action="{{ route('admin.nexa-suite-bookings.invoices.send', $row['invoice']) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="kt-btn kt-btn-primary kt-btn-sm">Naar tenant sturen</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="kt-card w-full min-w-0">
        <div class="kt-card-header px-5 py-5">
            <h3 class="kt-card-title text-sm mb-0">Recente facturen</h3>
        </div>
        <div class="kt-card-content p-0 min-w-0">
            @if($invoices->isEmpty())
                <p class="p-5 text-sm text-muted-foreground mb-0">Nog geen NEXA Suite boekingsfacturen.</p>
            @else
                <div class="kt-scrollable-x-auto admin-table-scroll-wrap min-w-0">
                    <table class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full">
                        <thead>
                            <tr>
                                <th class="text-left">Nummer</th>
                                <th class="text-left">Tenant</th>
                                <th class="text-left">Periode</th>
                                <th class="text-left">Ritten</th>
                                <th class="text-left">Totaal</th>
                                <th class="text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoices as $invoice)
                                <tr>
                                    <td><a class="text-primary hover:underline" href="{{ route('admin.nexa-suite-bookings.invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a></td>
                                    <td>{{ $invoice->company?->name }}</td>
                                    <td>{{ $invoice->billing_period }}</td>
                                    <td>{{ $invoice->ride_count }}</td>
                                    <td>€ {{ number_format((float) $invoice->total_amount, 2, ',', '.') }}</td>
                                    <td><span class="kt-badge kt-badge-outline rounded-[30px] {{ $invoice->statusBadgeClass() }}">{{ $invoice->statusLabel() }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
