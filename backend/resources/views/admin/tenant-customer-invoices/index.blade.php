@extends('admin.layouts.app')
@section('title', 'Klantfacturen')
@section('content')
<div class="kt-container-fixed">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-xl font-semibold">Klantfacturen</h1>
        <a href="{{ route('admin.tenant-customer-invoices.create') }}" class="kt-btn kt-btn-primary">Nieuwe factuur</a>
    </div>
    @if(session('success'))<div class="kt-alert kt-alert-success mb-4">{{ session('success') }}</div>@endif
    <div class="kt-card overflow-x-auto">
        <table class="kt-table w-full">
            <thead><tr><th>Nummer</th><th>Klant</th><th>Totaal</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($invoices as $invoice)
                <tr>
                    <td>{{ $invoice->invoice_number }}</td>
                    <td>{{ $invoice->customer_name }}</td>
                    <td>€ {{ number_format((float)$invoice->total_amount, 2, ',', '.') }}</td>
                    <td>{{ $invoice->status }}</td>
                    <td class="text-end"><a href="{{ route('admin.tenant-customer-invoices.show', $invoice) }}" class="kt-btn kt-btn-sm kt-btn-outline">Openen</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="p-4 text-muted-foreground">Nog geen klantfacturen.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $invoices->links() }}
</div>
@endsection
