@extends('admin.layouts.app')

@section('title', 'NEXA Suite facturen')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div>
            <h1 class="text-xl font-medium leading-none text-mono">NEXA Suite facturen</h1>
            <div class="text-sm text-secondary-foreground mt-2">Provisie over gereden ritten vanaf nexasuite.nl</div>
        </div>
        <form method="POST" action="{{ route('admin.nexa-suite-bookings.generate') }}" class="flex flex-wrap gap-2">
            @csrf
            @include('admin.partials.month-picker-input', [
                'name' => 'period',
                'value' => now()->subMonthNoOverflow()->format('Y-m'),
                'wrapperClass' => 'w-44',
                'required' => true,
            ])
            <label class="kt-label flex items-center gap-2">
                <input type="hidden" name="send" value="0">
                <input type="checkbox" name="send" value="1" class="kt-switch" checked>
                Direct versturen
            </label>
            <button type="submit" class="kt-btn kt-btn-primary">Facturen maken</button>
        </form>
    </div>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5" role="alert"><i class="ki-filled ki-check-circle me-2"></i>{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="kt-alert kt-alert-destructive mb-5" role="alert"><i class="ki-filled ki-information-2 me-2"></i>{{ session('error') }}</div>
    @endif

    @include('admin.nexa-suite-bookings.partials.nav')

    <div class="kt-card w-full min-w-0">
        <div class="kt-card-header px-5 py-5 flex-wrap gap-3 justify-between items-center">
            <h3 class="kt-card-title text-sm mb-0">Facturen</h3>
            <form method="GET" action="{{ route('admin.nexa-suite-bookings.invoices') }}" class="admin-filter-panel flex flex-wrap items-center gap-2">
                @include('admin.partials.month-picker-input', ['name' => 'period', 'value' => request('period')])
                <select class="kt-select w-full sm:w-48" name="company_id">
                    <option value="">Alle tenants</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" @selected((string) ($filterCompanyId ?? '') === (string) $company->id)>{{ $company->name }}</option>
                    @endforeach
                </select>
                <select class="kt-select w-full sm:w-40" name="status">
                    <option value="">Alle statussen</option>
                    @foreach(\App\Models\NexaSuiteBookingInvoice::STATUS_LABELS as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="kt-btn kt-btn-outline">Filter</button>
            </form>
        </div>
        <div class="kt-card-content p-0 min-w-0">
            @if($invoices->isEmpty())
                <p class="p-5 text-sm text-muted-foreground mb-0">Nog geen facturen.</p>
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
                                <th class="text-left">Aanmaning</th>
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
                                    <td>{{ $invoice->reminderLabel() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-5">{{ $invoices->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
