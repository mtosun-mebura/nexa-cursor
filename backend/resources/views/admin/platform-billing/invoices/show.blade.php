@extends('admin.layouts.app')

@section('title', 'NEXA-factuur '.$invoice->invoice_number)

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                NEXA-factuur <span style="color: rgb(234 179 8);">|</span> <span style="color: rgb(59 130 246);">{{ $invoice->invoice_number }}</span>
            </h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Platform → tenant factuurdetails
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.platform-billing.invoices.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
            <a href="{{ route('admin.platform-billing.invoices.edit', $invoice) }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-pencil me-2"></i>
                Bewerken
            </a>
            <a href="{{ route('admin.platform-billing.invoices.pdf', $invoice) }}" class="kt-btn kt-btn-outline" target="_blank" rel="noopener">
                <i class="ki-filled ki-file-down me-2"></i>
                PDF
            </a>
            <form method="POST" action="{{ route('admin.platform-billing.invoices.send', $invoice) }}" class="inline" onsubmit="return confirm('Factuur {{ $invoice->invoice_number }} per e-mail naar de tenant versturen?');">
                @csrf
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-sms me-2"></i>
                    Versturen
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

    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card min-w-full">
            <div class="kt-card-header px-5 py-5">
                <h3 class="kt-card-title mb-0">
                    Factuurgegevens
                </h3>
            </div>
            <div class="kt-card-table kt-scrollable-x-auto pb-3 admin-desktop-table-wrap">
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground w-full admin-fluid-table">
                    <colgroup>
                        <col class="invoice-detail-label-col" style="width: 19.5rem;">
                        <col>
                    </colgroup>
                    <tr>
                        <td class="whitespace-nowrap pe-10 text-secondary-foreground font-normal">Tenant</td>
                        <td class="font-medium text-foreground">{{ $invoice->company?->name }}</td>
                    </tr>
                    <tr>
                        <td class="whitespace-nowrap pe-10 text-secondary-foreground font-normal">Periode</td>
                        <td>{{ $invoice->billing_period }}</td>
                    </tr>
                    <tr>
                        <td class="whitespace-nowrap pe-10 text-secondary-foreground font-normal">Status</td>
                        <td>
                            <span class="kt-badge kt-badge-outline rounded-[30px] whitespace-nowrap {{ $invoice->statusBadgeClass() }}">{{ $invoice->statusLabel() }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="whitespace-nowrap pe-10 text-secondary-foreground font-normal">Incasso</td>
                        <td>{{ $invoice->collection_method ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="whitespace-nowrap pe-10 text-secondary-foreground font-normal">Subtotaal</td>
                        <td>€ {{ number_format((float) $invoice->amount, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="whitespace-nowrap pe-10 text-secondary-foreground font-normal">BTW</td>
                        <td>€ {{ number_format((float) $invoice->tax_amount, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="whitespace-nowrap pe-10 text-secondary-foreground font-normal">Totaal</td>
                        <td class="font-medium text-foreground">€ {{ number_format((float) $invoice->total_amount, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="whitespace-nowrap pe-10 text-secondary-foreground font-normal">Betaaltermijn</td>
                        <td>{{ \App\Models\PlatformBillingSetting::paymentTermsDaysForInvoice($invoice) }} dagen</td>
                    </tr>
                    @if($invoice->due_date)
                    <tr>
                        <td class="whitespace-nowrap pe-10 text-secondary-foreground font-normal">Vervaldatum</td>
                        <td>{{ $invoice->due_date->format('d-m-Y') }}</td>
                    </tr>
                    @endif
                    @if($invoice->paid_at)
                    <tr>
                        <td class="whitespace-nowrap pe-10 text-secondary-foreground font-normal">Betaald op</td>
                        <td>{{ $invoice->paid_at->format('d-m-Y H:i') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="whitespace-nowrap pe-10 text-secondary-foreground font-normal">1e aanmaning</td>
                        <td>{{ $invoice->first_reminder_sent_at?->format('d-m-Y H:i') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="whitespace-nowrap pe-10 text-secondary-foreground font-normal">2e aanmaning</td>
                        <td>{{ $invoice->second_reminder_sent_at?->format('d-m-Y H:i') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="whitespace-nowrap pe-10 text-secondary-foreground font-normal">Blokkade</td>
                        <td>
                            @if($invoice->blocked_at)
                                {{ $invoice->blocked_at->format('d-m-Y H:i') }}
                                @if($invoice->block_waived_at)
                                    <span class="text-muted-foreground">(opgeheven {{ $invoice->block_waived_at->format('d-m-Y H:i') }})</span>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                    @if($invoice->notes)
                    <tr>
                        <td class="whitespace-nowrap pe-10 text-secondary-foreground font-normal">Notities</td>
                        <td>{{ $invoice->notes }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <div class="kt-card min-w-full">
            <div class="kt-card-header px-5 py-5">
                <h3 class="kt-card-title mb-0">
                    Factuurregels
                </h3>
            </div>
            <div class="kt-card-content p-5">
                @php
                    $fmtMoney = function (float $n): string {
                        $formatted = number_format(abs($n), 2, ',', '.');

                        return $n < 0 ? '− € '.$formatted : '€ '.$formatted;
                    };
                @endphp
                <div class="kt-scrollable-x-auto min-w-0">
                    <table class="kt-table kt-table-border align-middle text-sm w-full">
                        <thead>
                            <tr>
                                <th class="text-secondary-foreground font-normal text-left" data-label="Omschrijving">Omschrijving</th>
                                <th class="text-secondary-foreground font-normal text-right" data-label="Aantal">Aantal</th>
                                <th class="text-secondary-foreground font-normal text-right" data-label="Prijs excl. BTW">Prijs excl. BTW</th>
                                <th class="text-secondary-foreground font-normal text-right" data-label="Totaal excl. BTW">Totaal excl. BTW</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($invoice->line_items ?? [] as $item)
                            @php
                                $isDiscount = ($item['type'] ?? '') === 'discount';
                                $unitPrice = (float) ($item['unit_price'] ?? 0);
                                $lineTotal = (float) ($item['total'] ?? 0);
                            @endphp
                            <tr>
                                <td class="{{ $isDiscount ? 'text-destructive' : '' }}">{{ $item['description'] ?? '—' }}</td>
                                <td class="text-right tabular-nums">{{ $item['quantity'] ?? 1 }}</td>
                                <td class="text-right tabular-nums {{ $isDiscount ? 'text-destructive' : '' }}">{{ $fmtMoney($unitPrice) }}</td>
                                <td class="text-right tabular-nums {{ $isDiscount ? 'text-destructive' : '' }}">{{ $fmtMoney($lineTotal) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-5 text-secondary-foreground">Geen factuurregels.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
