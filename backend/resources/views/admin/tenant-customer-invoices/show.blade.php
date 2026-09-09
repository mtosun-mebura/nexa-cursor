@extends('admin.layouts.app')
@section('title', 'Factuur '.$invoice->invoice_number)
@section('content')
<div class="kt-container-fixed max-w-2xl">
    <h1 class="text-xl font-semibold mb-4">{{ $invoice->invoice_number }}</h1>
    <div class="kt-card p-6 space-y-2 text-sm mb-6">
        <p><strong>Klant:</strong> {{ $invoice->customer_name }}</p>
        <p><strong>Totaal:</strong> € {{ number_format((float)$invoice->total_amount, 2, ',', '.') }}</p>
        <p><strong>Status:</strong> {{ $invoice->status }}</p>
        @if($invoice->mollie_checkout_url)
            <p><strong>Betaallink:</strong> <a href="{{ $invoice->mollie_checkout_url }}" class="text-primary underline" target="_blank" rel="noopener">Open Mollie-checkout</a></p>
        @endif
    </div>
    <div class="flex flex-wrap gap-2 mb-6">
        <a href="{{ route('admin.tenant-customer-invoices.pdf', $invoice) }}" class="kt-btn kt-btn-outline" target="_blank" rel="noopener">PDF</a>
    </div>
    @if(!$invoice->isPaid())
    <form method="POST" action="{{ route('admin.tenant-customer-invoices.send-payment-link', $invoice) }}" class="kt-card p-6 space-y-4">
        @csrf
        <h2 class="font-semibold">Verstuur met Mollie-betaallink</h2>
        <p class="text-sm text-muted-foreground">Vul het e-mailadres in waar de factuur naartoe moet. Er wordt een Mollie-betaallink in de mail gezet (via de Mollie-provider van uw tenant).</p>
        <label class="block"><span class="text-sm font-medium">E-mailadres</span>
            <input type="email" name="recipient_email" class="kt-input mt-1 w-full" value="{{ old('recipient_email', $invoice->customer_email) }}" required></label>
        <button type="submit" class="kt-btn kt-btn-primary">Versturen</button>
    </form>
    @endif
    <a href="{{ route('admin.tenant-customer-invoices.index') }}" class="kt-btn kt-btn-outline mt-4 inline-block">Terug</a>
</div>
@endsection
