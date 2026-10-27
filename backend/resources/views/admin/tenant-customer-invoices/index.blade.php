@extends('admin.layouts.app')
@section('title', __('Klantfacturen'))
@section('content')
<div class="kt-container-fixed">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-xl font-semibold">{{ __('Klantfacturen') }}</h1>
        <a href="{{ route('admin.tenant-customer-invoices.create') }}" class="kt-btn kt-btn-primary">{{ __('Nieuwe factuur') }}</a>
    </div>
    <div class="kt-card overflow-x-auto">
        <table class="kt-table w-full">
            <thead><tr><th>{{ __('Nummer') }}</th><th>{{ __('Klant') }}</th><th>{{ __('Totaal') }}</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($invoices as $invoice)
                <tr>
                    <td>{{ $invoice->invoice_number }}</td>
                    <td>{{ $invoice->customer_name }}</td>
                    <td>€ {{ number_format((float)$invoice->total_amount, 2, ',', '.') }}</td>
                    <td>{{ $invoice->status }}</td>
                    <td class="text-end"><a href="{{ route('admin.tenant-customer-invoices.show', $invoice) }}" class="kt-btn kt-btn-sm kt-btn-outline">{{ __('Openen') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="p-4 text-muted-foreground">{{ __('Nog geen klantfacturen.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $invoices->links() }}
</div>
@endsection
