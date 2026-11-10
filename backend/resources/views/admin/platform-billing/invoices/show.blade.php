@extends('admin.layouts.app')

@section('title', __('NEXA-factuur').' '.$invoice->invoice_number)

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                {{ __('NEXA-factuur') }} <span style="color: rgb(234 179 8);">|</span> <span style="color: rgb(59 130 246);">{{ $invoice->invoice_number }}</span>
            </h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('Platform → tenant factuurdetails') }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.platform-billing.invoices.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                {{ __('Terug') }}
            </a>
            <a href="{{ route('admin.platform-billing.invoices.edit', $invoice) }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-pencil me-2"></i>
                {{ __('Bewerken') }}
            </a>
            <a href="{{ route('admin.platform-billing.invoices.pdf', $invoice) }}" class="kt-btn kt-btn-outline" target="_blank" rel="noopener">
                <i class="ki-filled ki-file-down me-2"></i>
                PDF
            </a>
            <form method="POST" action="{{ route('admin.platform-billing.invoices.send', $invoice) }}" class="inline" onsubmit="return confirm({{ \Illuminate\Support\Js::from(__('Factuur :number per e-mail naar de tenant versturen?', ['number' => $invoice->invoice_number])) }});">
                @csrf
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-sms me-2"></i>
                    {{ __('Versturen') }}
                </button>
            </form>
        </div>
    </div>

    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card min-w-full">
            <div class="kt-card-header px-5 py-5">
                <h3 class="kt-card-title mb-0">
                    {{ __('Factuurgegevens') }}
                </h3>
            </div>
            <div class="kt-card-table kt-scrollable-x-auto pb-3 admin-desktop-table-wrap">
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground w-full admin-fluid-table">
                    <colgroup>
                        <col class="invoice-detail-label-col" style="width: 19.5rem;">
                        <col>
                    </colgroup>
                    <tr>
                        <td class="whitespace-nowrap pe-10 text-secondary-foreground font-normal">{{ __('Tenant') }}</td>
                        <td class="font-medium text-foreground">{{ $invoice->company?->name }}</td>
                    </tr>
                    <tr>
                        <td class="whitespace-nowrap pe-10 text-secondary-foreground font-normal">{{ __('Periode') }}</td>
                        <td>{{ $invoice->billing_period }}</td>
                    </tr>
                    <tr>
                        <td class="whitespace-nowrap pe-10 text-secondary-foreground font-normal">{{ __('Status') }}</td>
                        <td>
                            <span class="kt-badge kt-badge-outline rounded-[30px] whitespace-nowrap {{ $invoice->statusBadgeClass() }}">{{ __($invoice->statusLabel()) }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="whitespace-nowrap pe-10 text-secondary-foreground font-normal">{{ __('Incasso') }}</td>
                        <td>{{ $invoice->collection_method ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="whitespace-nowrap pe-10 text-secondary-foreground font-normal">{{ __('Subtotaal') }}</td>
                        <td>€ {{ number_format((float) $invoice->amount, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="whitespace-nowrap pe-10 text-secondary-foreground font-normal">{{ __('BTW') }}</td>
                        <td>€ {{ number_format((float) $invoice->tax_amount, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="whitespace-nowrap pe-10 text-secondary-foreground font-normal">{{ __('Totaal') }}</td>
                        <td class="font-medium text-foreground">€ {{ number_format((float) $invoice->total_amount, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="whitespace-nowrap pe-10 text-secondary-foreground font-normal">{{ __('Betaaltermijn') }}</td>
                        <td>{{ \App\Models\PlatformBillingSetting::paymentTermsDaysForInvoice($invoice) }} {{ __('dagen') }}</td>
                    </tr>
                    @if($invoice->due_date)
                    <tr>
                        <td class="whitespace-nowrap pe-10 text-secondary-foreground font-normal">{{ __('Vervaldatum') }}</td>
                        <td>{{ $invoice->due_date->format('d-m-Y') }}</td>
                    </tr>
                    @endif
                    @if($invoice->paid_at)
                    <tr>
                        <td class="whitespace-nowrap pe-10 text-secondary-foreground font-normal">{{ __('Betaald op') }}</td>
                        <td>{{ $invoice->paid_at->format('d-m-Y H:i') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="whitespace-nowrap pe-10 text-secondary-foreground font-normal">{{ __('1e aanmaning') }}</td>
                        <td>{{ $invoice->first_reminder_sent_at?->format('d-m-Y H:i') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="whitespace-nowrap pe-10 text-secondary-foreground font-normal">{{ __('2e aanmaning') }}</td>
                        <td>{{ $invoice->second_reminder_sent_at?->format('d-m-Y H:i') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="whitespace-nowrap pe-10 text-secondary-foreground font-normal">{{ __('Blokkade') }}</td>
                        <td>
                            @if($invoice->blocked_at)
                                {{ $invoice->blocked_at->format('d-m-Y H:i') }}
                                @if($invoice->block_waived_at)
                                    <span class="text-muted-foreground">({{ __('opgeheven :date', ['date' => $invoice->block_waived_at->format('d-m-Y H:i')]) }})</span>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                    @if($invoice->notes)
                    <tr>
                        <td class="whitespace-nowrap pe-10 text-secondary-foreground font-normal">{{ __('Notities') }}</td>
                        <td>{{ $invoice->notes }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <div class="kt-card min-w-full">
            <div class="kt-card-header px-5 py-5">
                <h3 class="kt-card-title mb-0">
                    {{ __('Factuurregels') }}
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
                                <th class="text-secondary-foreground font-normal text-left" data-label="{{ __('Omschrijving') }}">{{ __('Omschrijving') }}</th>
                                <th class="text-secondary-foreground font-normal text-right" data-label="{{ __('Aantal') }}">{{ __('Aantal') }}</th>
                                <th class="text-secondary-foreground font-normal text-right" data-label="{{ __('Prijs excl. BTW') }}">{{ __('Prijs excl. BTW') }}</th>
                                <th class="text-secondary-foreground font-normal text-right" data-label="{{ __('Totaal excl. BTW') }}">{{ __('Totaal excl. BTW') }}</th>
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
                                <td colspan="4" class="p-5 text-secondary-foreground">{{ __('Geen factuurregels.') }}</td>
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
