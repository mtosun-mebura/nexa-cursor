@extends('admin.layouts.app')

@section('title', 'Factuur Details')

@section('content')
<div class="kt-container-fixed">
    <!-- Page Title -->
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                Factuur Details <span style="color: rgb(234 179 8);">|</span> <span style="color: rgb(59 130 246);">{{ $invoice->invoice_number }}</span>
            </h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Factuurdetails en betalingsinformatie
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.invoices.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left text-base me-2"></i>
                Terug naar Overzicht
            </a>
            <button type="button" class="kt-btn kt-btn-primary" onclick="window.print()">
                <i class="ki-filled ki-printer text-base me-2"></i>
                Printen
            </button>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-5 lg:gap-7.5">
        <!-- Invoice Details -->
        <div class="lg:col-span-2">
            <div class="kt-card">
                <div class="kt-kt-card-header">
                    <h3 class="kt-kt-card-title">
                        Factuurdetails
                    </h3>
                </div>
                <div class="kt-card-content p-5 lg:p-7.5">
                    <div class="flex flex-col gap-4">
                        <!-- Logo and Header Info -->
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <img src="{{ asset('images/nexa-skillmatching-logo.png') }}" alt="Nexa Skillmatching" class="h-12 w-auto">
                            </div>
                            <div class="text-right ml-auto pl-8">
                                <div class="mb-2">
                                    <span class="text-sm text-secondary-foreground">Status</span>
                                    <div class="mt-1">
                                        @if($invoice->status === 'paid')
                                            <span class="kt-badge kt-badge-success kt-badge-outline rounded-[30px]">
                                                Betaald
                                            </span>
                                        @elseif($invoice->status === 'sent')
                                            <span class="kt-badge kt-badge-warning kt-badge-outline rounded-[30px]">
                                                Verzonden
                                            </span>
                                        @elseif($invoice->status === 'in_progress')
                                            <span class="kt-badge kt-badge-info kt-badge-outline rounded-[30px]">
                                                In behandeling
                                            </span>
                                        @elseif($invoice->status === 'overdue')
                                            <span class="kt-badge kt-badge-destructive kt-badge-outline rounded-[30px]">
                                                Achterstallig
                                            </span>
                                        @elseif($invoice->status === 'cancelled')
                                            <span class="kt-badge kt-badge-secondary kt-badge-outline rounded-[30px]">
                                                Geannuleerd
                                            </span>
                                        @else
                                            <span class="kt-badge kt-badge-outline rounded-[30px]">
                                                Concept
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div>
                                    <span class="text-sm text-secondary-foreground">Factuurdatum</span>
                                    <p class="text-sm font-semibold text-mono">{{ $invoice->invoice_date->format('d M, Y') }}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-sm text-secondary-foreground">Factuurnummer</span>
                                <p class="text-sm font-semibold text-mono">{{ $invoice->invoice_number }}</p>
                            </div>
                            <div>
                                <span class="text-sm text-secondary-foreground">Vervaldatum</span>
                                <p class="text-sm font-semibold text-mono">{{ $invoice->due_date->format('d M, Y') }}</p>
                            </div>
                        </div>
                        
                        <div class="border-t border-input pt-4">
                            <h4 class="text-sm font-semibold text-mono mb-3">Bedrijf</h4>
                            <div class="flex flex-col gap-1">
                                <p class="text-sm text-secondary-foreground">{{ $invoice->company->name ?? 'N/A' }}</p>
                                @if($invoice->company)
                                    <p class="text-sm text-secondary-foreground">{{ $invoice->company->address ?? '' }}</p>
                                    <p class="text-sm text-secondary-foreground">{{ $invoice->company->postal_code ?? '' }} {{ $invoice->company->city ?? '' }}</p>
                                @endif
                            </div>
                        </div>
                        
                        <div class="border-t border-input pt-4">
                            <h4 class="text-sm font-semibold text-mono mb-3">Regels</h4>
                            <kt-table class="kt-kt-table w-full">
                                <thead>
                                    <tr>
                                        <th>Omschrijving</th>
                                        <th class="text-right">Bedrag</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if($invoice->line_items && count($invoice->line_items) > 0)
                                        @foreach($invoice->line_items as $item)
                                        <tr>
                                            <td>{{ $item['description'] ?? 'N/A' }}</td>
                                            <td class="text-right">€{{ number_format($item['total'] ?? $item['price'] ?? $item['amount'] ?? 0, 2, ',', '.') }}</td>
                                        </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td>Match fee</td>
                                            <td class="text-right">€{{ number_format($invoice->amount, 2, ',', '.') }}</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </kt-table>
                        </div>
                        
                        <div class="border-t border-input pt-4">
                            <div class="flex justify-end">
                                <div class="flex flex-col gap-2 w-64">
                                    <div class="flex justify-between">
                                        <span class="text-sm text-secondary-foreground">Subtotaal:</span>
                                        <span class="text-sm font-semibold text-mono">€{{ number_format($invoice->amount, 2, ',', '.') }}</span>
                                    </div>
                                    @if($invoice->tax_amount > 0)
                                    <div class="flex justify-between">
                                        <span class="text-sm text-secondary-foreground">BTW:</span>
                                        <span class="text-sm font-semibold text-mono">€{{ number_format($invoice->tax_amount, 2, ',', '.') }}</span>
                                    </div>
                                    @endif
                                    <div class="flex justify-between border-t border-input pt-2">
                                        <span class="text-sm font-semibold text-mono">Totaal:</span>
                                        <span class="text-sm font-semibold text-mono">€{{ number_format($invoice->total_amount, 2, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        @if($invoice->notes)
                        <div class="border-t border-input pt-4">
                            <h4 class="text-sm font-semibold text-mono mb-2">Opmerkingen</h4>
                            <p class="text-sm text-secondary-foreground">{{ $invoice->notes }}</p>
                        </div>
                        @endif
                        
                        <div class="border-t border-input pt-4">
                            @php
                                $paymentTermsDays = $settings->payment_terms_days ?? 30;
                            @endphp
                            <p class="text-xs text-secondary-foreground">
                                <strong>Betaaltermijn:</strong> Deze factuur dient binnen {{ $paymentTermsDays }} {{ $paymentTermsDays == 1 ? 'dag' : 'dagen' }} na factuurdatum ({{ $invoice->due_date->format('d M Y') }}) te worden betaald.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Actions Sidebar -->
        <div class="lg:col-span-1">
            <div class="kt-card">
                <div class="kt-kt-card-header">
                    <h3 class="kt-kt-card-title">
                        Acties
                    </h3>
                </div>
                <div class="kt-card-content flex flex-col gap-2.5 p-5">
                    @if($invoice->status !== 'paid')
                        <form method="POST" action="{{ route('admin.invoices.send-reminder', $invoice->id) }}" class="w-full">
                            @csrf
                            <button type="submit" class="kt-btn kt-btn-outline w-full justify-center">
                                <i class="ki-filled ki-send text-base me-2"></i>
                                Verstuur Aanmaning
                            </button>
                        </form>
                    @endif
                    
                    <button type="button" class="kt-btn kt-btn-outline w-full justify-center" onclick="generatePaymentLinks()">
                        <i class="ki-filled ki-link text-base me-2"></i>
                        Betaallinks
                    </button>
                    
                    <a href="{{ route('admin.invoices.edit', $invoice->id) }}" class="kt-btn kt-btn-outline w-full justify-center">
                        <i class="ki-filled ki-notepad-edit text-base me-2"></i>
                        Bewerken
                    </a>
                </div>
            </div>
            
            @if($invoice->payments && $invoice->payments->count() > 0)
            <div class="kt-card mt-5">
                <div class="kt-kt-card-header">
                    <h3 class="kt-kt-card-title">
                        Betalingen
                    </h3>
                </div>
                <div class="kt-card-content flex flex-col gap-2.5 p-5">
                    @foreach($invoice->payments as $payment)
                    <div class="flex items-center justify-between border-b border-input pb-2.5 last:border-0 last:pb-0">
                        <div class="flex flex-col gap-1">
                            <span class="text-sm font-semibold text-mono">€{{ number_format($payment->amount, 2, ',', '.') }}</span>
                            <span class="text-xs text-secondary-foreground">{{ $payment->paid_at ? $payment->paid_at->format('d M, Y') : 'N/A' }}</span>
                        </div>
                        <span class="kt-badge kt-badge-success kt-badge-outline rounded-[30px]">
                            Betaald
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    function generatePaymentLinks() {
        fetch('{{ route('admin.invoices.payment-links', $invoice->id) }}')
            .then(response => response.json())
            .then(data => {
                // Display payment links in a modal or alert
                alert('Betaallinks:\n\nTikkie: ' + data.tikkie + '\nQR Code: ' + data.qr + '\nBank: ' + data.bank);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Er is een fout opgetreden bij het genereren van betaallinks.');
            });
    }
</script>
@endpush
@endsection

