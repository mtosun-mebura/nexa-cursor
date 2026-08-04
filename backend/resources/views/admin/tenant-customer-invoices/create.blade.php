@extends('admin.layouts.app')
@section('title', 'Nieuwe klantfactuur')
@section('content')
<div class="kt-container-fixed max-w-xl">
    <h1 class="text-xl font-semibold mb-6">Nieuwe klantfactuur</h1>
    <form method="POST" action="{{ route('admin.tenant-customer-invoices.store') }}" class="kt-card p-6 space-y-4">
        @csrf
        <label class="block"><span class="text-sm font-medium">Klantnaam</span><input class="kt-input mt-1 w-full" name="customer_name" required></label>
        <label class="block"><span class="text-sm font-medium">Klant e-mail (optioneel)</span><input type="email" class="kt-input mt-1 w-full" name="customer_email"></label>
        <label class="block"><span class="text-sm font-medium">Omschrijving</span><textarea class="kt-input mt-1 w-full" name="description" rows="3" required></textarea></label>
        <label class="block"><span class="text-sm font-medium">Bedrag excl. BTW</span><input type="number" step="0.01" min="0.01" class="kt-input mt-1 w-full" name="amount" required></label>
        <label class="block"><span class="text-sm font-medium">BTW %</span><input type="number" step="0.01" class="kt-input mt-1 w-full" name="tax_rate_percent" value="21"></label>
        <button type="submit" class="kt-btn kt-btn-primary">Factuur aanmaken</button>
    </form>
</div>
@endsection
