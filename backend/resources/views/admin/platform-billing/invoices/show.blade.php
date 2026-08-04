@extends('admin.layouts.app')

@section('title', 'SaaS-factuur '.$invoice->invoice_number)

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                SaaS-factuur <span style="color: rgb(234 179 8);">|</span> <span style="color: rgb(59 130 246);">{{ $invoice->invoice_number }}</span>
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
            <a href="{{ route('admin.platform-billing.invoices.pdf', $invoice) }}" class="kt-btn kt-btn-primary" target="_blank" rel="noopener">
                <i class="ki-filled ki-file-down me-2"></i>
                PDF
            </a>
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
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    Factuurgegevens
                </h3>
            </div>
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground w-full">
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Tenant</td>
                        <td class="font-medium text-foreground">{{ $invoice->company?->name }}</td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">Periode</td>
                        <td>{{ $invoice->billing_period }}</td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">Status</td>
                        <td>{{ $invoice->status }}</td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">Incasso</td>
                        <td>{{ $invoice->collection_method ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">Subtotaal</td>
                        <td>€ {{ number_format((float) $invoice->amount, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">BTW</td>
                        <td>€ {{ number_format((float) $invoice->tax_amount, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">Totaal</td>
                        <td class="font-medium text-foreground">€ {{ number_format((float) $invoice->total_amount, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">Betaaltermijn</td>
                        <td>{{ \App\Models\PlatformBillingSetting::paymentTermsDaysForInvoice($invoice) }} dagen</td>
                    </tr>
                    @if($invoice->due_date)
                    <tr>
                        <td class="text-secondary-foreground font-normal">Vervaldatum</td>
                        <td>{{ $invoice->due_date->format('d-m-Y') }}</td>
                    </tr>
                    @endif
                    @if($invoice->paid_at)
                    <tr>
                        <td class="text-secondary-foreground font-normal">Betaald op</td>
                        <td>{{ $invoice->paid_at->format('d-m-Y H:i') }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        @if(!$invoice->isPaid())
        <form method="POST" action="{{ route('admin.platform-billing.invoices.update', $invoice) }}">
            @csrf
            @method('PUT')

            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Betaaltermijn voor deze factuur
                    </h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">Aantal dagen</td>
                            <td class="min-w-48 w-full">
                                <input type="number" name="payment_terms_days" min="1" max="365" class="kt-input w-full max-w-xs" value="{{ old('payment_terms_days', $invoice->payment_terms_days ?? \App\Models\PlatformBillingSetting::paymentTermsDaysForInvoice($invoice)) }}" required>
                                <div class="text-xs text-muted-foreground mt-1">De vervaldatum wordt herberekend vanaf de factuurdatum.</div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5 mt-5">
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-check me-2"></i>
                    Betaaltermijn opslaan
                </button>
            </div>
        </form>
        @endif
    </div>
</div>
@endsection
