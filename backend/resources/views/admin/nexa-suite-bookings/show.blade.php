@extends('admin.layouts.app')

@section('title', 'NEXA Suite factuur '.$invoice->invoice_number)

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div>
            <h1 class="text-xl font-medium leading-none text-mono">Factuur {{ $invoice->invoice_number }}</h1>
            <div class="text-sm text-secondary-foreground mt-2">{{ $invoice->company?->name }} · periode {{ $invoice->billing_period }}</div>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('admin.nexa-suite-bookings.invoices') }}" class="kt-btn kt-btn-outline">Terug</a>
            <a href="{{ route('admin.nexa-suite-bookings.invoices.pdf', $invoice) }}" class="kt-btn kt-btn-outline" target="_blank" rel="noopener">PDF</a>
            @if($invoice->status === 'draft' && ! $invoice->isPaid())
                <form method="POST" action="{{ route('admin.nexa-suite-bookings.invoices.send', $invoice) }}">
                    @csrf
                    <button type="submit" class="kt-btn kt-btn-primary">Naar tenant sturen</button>
                </form>
            @endif
            @if($invoice->isOpen())
                <form method="POST" action="{{ route('admin.nexa-suite-bookings.invoices.reminder', $invoice) }}">
                    @csrf
                    <button type="submit" class="kt-btn kt-btn-outline">Aanmaning sturen</button>
                </form>
                <form method="POST" action="{{ route('admin.nexa-suite-bookings.invoices.mark-paid', $invoice) }}">
                    @csrf
                    <button type="submit" class="kt-btn kt-btn-outline">Markeer betaald</button>
                </form>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5" role="alert"><i class="ki-filled ki-check-circle me-2"></i>{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="kt-alert kt-alert-destructive mb-5" role="alert"><i class="ki-filled ki-information-2 me-2"></i>{{ session('error') }}</div>
    @endif

    @include('admin.nexa-suite-bookings.partials.nav')

    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header px-5 py-5"><h3 class="kt-card-title mb-0">Factuurgegevens</h3></div>
            <div class="kt-card-content p-5 space-y-3 text-sm">
                <p class="flex gap-2 mb-0"><span class="text-muted-foreground w-40 shrink-0">Status</span>
                    <span class="kt-badge kt-badge-outline rounded-[30px] {{ $invoice->statusBadgeClass() }}">{{ $invoice->statusLabel() }}</span>
                </p>
                <p class="flex gap-2 mb-0"><span class="text-muted-foreground w-40 shrink-0">Ritten</span><span>{{ $invoice->ride_count }} gereden ritten vanuit NEXA Suite</span></p>
                <p class="flex gap-2 mb-0"><span class="text-muted-foreground w-40 shrink-0">Ritomzet</span><span>€ {{ number_format((float) $invoice->rides_subtotal, 2, ',', '.') }}</span></p>
                <p class="flex gap-2 mb-0"><span class="text-muted-foreground w-40 shrink-0">Fee</span><span>{{ (int) $invoice->fee_percent }}%</span></p>
                <p class="flex gap-2 mb-0"><span class="text-muted-foreground w-40 shrink-0">Excl. BTW</span><span>€ {{ number_format((float) $invoice->amount, 2, ',', '.') }}</span></p>
                <p class="flex gap-2 mb-0"><span class="text-muted-foreground w-40 shrink-0">BTW</span><span>€ {{ number_format((float) $invoice->tax_amount, 2, ',', '.') }}</span></p>
                <p class="flex gap-2 mb-0"><span class="text-muted-foreground w-40 shrink-0">Totaal</span><span class="font-medium">€ {{ number_format((float) $invoice->total_amount, 2, ',', '.') }}</span></p>
                <p class="flex gap-2 mb-0"><span class="text-muted-foreground w-40 shrink-0">Vervaldatum</span><span>{{ $invoice->due_date?->format('d-m-Y') ?? '—' }}</span></p>
                <p class="flex gap-2 mb-0"><span class="text-muted-foreground w-40 shrink-0">Verzonden</span><span>{{ $invoice->sent_at?->format('d-m-Y H:i') ?? '—' }}</span></p>
                <p class="flex gap-2 mb-0"><span class="text-muted-foreground w-40 shrink-0">1e aanmaning</span><span>{{ $invoice->first_reminder_sent_at?->format('d-m-Y H:i') ?? '—' }}</span></p>
                <p class="flex gap-2 mb-0"><span class="text-muted-foreground w-40 shrink-0">2e aanmaning</span><span>{{ $invoice->second_reminder_sent_at?->format('d-m-Y H:i') ?? '—' }}</span></p>
                <p class="flex gap-2 mb-0"><span class="text-muted-foreground w-40 shrink-0">Betaald</span><span>{{ $invoice->paid_at?->format('d-m-Y H:i') ?? '—' }}</span></p>
            </div>
        </div>

        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header px-5 py-5"><h3 class="kt-card-title mb-0">Status aanpassen</h3></div>
            <div class="kt-card-content p-5">
                <form method="POST" action="{{ route('admin.nexa-suite-bookings.invoices.status', $invoice) }}" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div>
                        <label class="text-sm text-muted-foreground mb-1 block">Status</label>
                        <select name="status" class="kt-select w-48">
                            @foreach(\App\Models\NexaSuiteBookingInvoice::STATUS_LABELS as $value => $label)
                                <option value="{{ $value }}" @selected($invoice->status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="kt-btn kt-btn-outline">Opslaan</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
