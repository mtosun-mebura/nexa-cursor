@extends('admin.layouts.app')

@section('title', 'Factuur Details')

@section('content')
<div class="kt-container-fixed">
    <!-- Page Title -->
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                Factuur Details <span style="color: rgb(234 179 8);">|</span> <span style="color: rgb(59 130 246);">{{ $invoice->invoice_number }}{{ $invoice->is_partial && $invoice->partial_number ? '-' . $invoice->partial_number : '' }}</span>
            </h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Factuurdetails en betalingsinformatie
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ $invoiceBackUrl ?? route('admin.invoices.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left text-base me-2"></i>
                Terug
            </a>
            <button type="button" class="kt-btn kt-btn-primary" onclick="window.print()">
                <i class="ki-filled ki-printer text-base me-2"></i>
                Printen
            </button>
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

    <div class="grid lg:grid-cols-3 gap-5 lg:gap-7.5">
        <!-- Invoice Details -->
        <div class="lg:col-span-2">
            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Factuurdetails
                    </h3>
                </div>
                <div class="kt-card-content p-5 lg:p-7.5">
                    <div class="flex flex-col gap-4">
                        <!-- Logo and Header Info -->
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                @if($invoice->company && $invoice->company->logo_blob)
                                    @php
                                        $companyLogoDarkUrl = ! empty($invoice->company->logo_dark_blob)
                                            ? route('admin.companies.logo.dark', $invoice->company)
                                            : route('admin.companies.logo', $invoice->company);
                                    @endphp
                                    <img class="h-12 w-auto max-w-[220px] object-contain logo-light dark:hidden"
                                         src="{{ route('admin.companies.logo', $invoice->company) }}"
                                         alt="{{ $invoice->company->name }}">
                                    <img class="h-12 w-auto max-w-[220px] object-contain logo-dark hidden dark:block"
                                         src="{{ $companyLogoDarkUrl }}"
                                         alt="{{ $invoice->company->name }}">
                                @elseif($invoice->company)
                                    <span class="text-lg font-semibold text-mono">{{ $invoice->company->name }}</span>
                                @else
                                    <img src="{{ asset('images/nexa-logo.png') }}" alt="NEXA" class="h-12 w-auto">
                                @endif
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
                                    <p class="text-sm font-semibold text-mono">{{ $invoice->invoice_date->format('d M Y') }}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-sm text-secondary-foreground">Factuurnummer</span>
                                <p class="text-sm font-semibold text-mono">{{ $invoice->invoice_number }}{{ $invoice->is_partial && $invoice->partial_number ? '-' . $invoice->partial_number : '' }}</p>
                            </div>
                            <div>
                                <span class="text-sm text-secondary-foreground">Vervaldatum</span>
                                <p class="text-sm font-semibold text-mono">{{ $invoice->due_date->format('d M Y') }}</p>
                            </div>
                        </div>
                        
                        @if($invoice->jobMatch && $invoice->jobMatch->candidate)
                        <div class="grid grid-cols-2 gap-4 mt-4">
                            <div>
                                <span class="text-sm text-secondary-foreground">Kandidaat</span>
                                <p class="text-sm font-semibold text-mono">
                                    {{ trim(($invoice->jobMatch->candidate->first_name ?? '') . ' ' . ($invoice->jobMatch->candidate->last_name ?? '')) }}
                                </p>
                            </div>
                            <div>
                                <span class="text-sm text-secondary-foreground">Vacature</span>
                                <p class="text-sm font-semibold text-mono">
                                    @if($invoice->jobMatch->vacancy)
                                        {{ $invoice->jobMatch->vacancy->title }}
                                    @else
                                        N/A
                                    @endif
                                </p>
                            </div>
                        </div>
                        @endif

                        @if($invoice->customer_name || $invoice->customer_email || $defaultReminderEmail)
                        <div class="border-t border-input pt-4">
                            <h4 class="text-sm font-semibold text-mono mb-3">Klant</h4>
                            <div class="flex flex-col gap-1">
                                @if($invoice->customer_name)
                                    <p class="text-sm font-semibold text-mono">{{ $invoice->customer_name }}</p>
                                @endif
                                <p class="text-sm text-secondary-foreground">
                                    {{ $invoice->customer_email ?: $defaultReminderEmail ?: 'Geen e-mailadres bekend' }}
                                </p>
                            </div>
                        </div>
                        @endif
                        
                        <div class="border-t border-input pt-4">
                            <h4 class="text-sm font-semibold text-mono mb-3">Bedrijf (facturant)</h4>
                            <div class="flex flex-col gap-1">
                                <p class="text-sm text-secondary-foreground">{{ $invoice->company->name ?? 'N/A' }}</p>
                                @if($invoice->company)
                                    <p class="text-sm text-secondary-foreground">{{ $invoice->company->address ?? '' }}</p>
                                    <p class="text-sm text-secondary-foreground">{{ $invoice->company->postal_code ?? '' }} {{ $invoice->company->city ?? '' }}</p>
                                @endif
                            </div>
                        </div>
                        
                        <div class="border-t border-input pt-4">
                            <h4 class="text-sm font-semibold text-mono mb-3">Omschrijving</h4>
                            @php
                                $description = 'Match fee';
                                if ($invoice->line_items && is_array($invoice->line_items) && count($invoice->line_items) > 0) {
                                    $firstItem = $invoice->line_items[0];
                                    $description = $firstItem['description'] ?? 'Match fee';
                                }
                            @endphp
                            <p class="text-sm text-secondary-foreground">{{ $description }}</p>
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
                        
                        @if(!empty($paymentTermsText))
                        <div class="border-t border-input pt-4">
                            <p class="text-xs text-secondary-foreground">
                                {{ $paymentTermsText }}
                            </p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Actions Sidebar -->
        <div class="lg:col-span-1">
            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Acties
                    </h3>
                </div>
                <div class="kt-card-content flex flex-col gap-2.5 p-5">
                    <a href="{{ route('admin.invoices.pdf', $invoice->id) }}"
                       class="kt-btn kt-btn-outline w-full justify-center"
                       target="_blank"
                       rel="noopener noreferrer">
                        <i class="ki-filled ki-file-down text-base me-2"></i>
                        PDF genereren
                    </a>

                    @if($invoice->status !== 'paid')
                        <form method="POST" action="{{ route('admin.invoices.send-reminder', $invoice->id) }}" class="w-full flex flex-col gap-2.5">
                            @csrf
                            <div>
                                <label for="reminder-email" class="text-xs font-medium text-secondary-foreground mb-1 block">
                                    E-mail klant
                                </label>
                                <input type="email"
                                       id="reminder-email"
                                       name="email"
                                       class="kt-input w-full @error('email') border-destructive @enderror"
                                       value="{{ old('email', $invoice->customer_email ?: $defaultReminderEmail) }}"
                                       placeholder="klant@voorbeeld.nl"
                                       required>
                                @error('email')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                                <p class="text-xs text-muted-foreground mt-1">
                                    Wordt opgeslagen op de factuur en gebruikt voor de aanmaning (met PDF-bijlage).
                                </p>
                            </div>
                            <button type="submit" class="kt-btn kt-btn-outline w-full justify-center">
                                <i class="ki-filled ki-send text-base me-2"></i>
                                Verstuur Aanmaning
                            </button>
                        </form>
                    @endif
                    
                    <button type="button"
                            id="btn-payment-links"
                            class="kt-btn kt-btn-outline w-full justify-center"
                            onclick="generatePaymentLinks(this)">
                        <i class="ki-filled ki-link text-base me-2"></i>
                        <span class="btn-payment-links-label">Betaallinks</span>
                    </button>

                    <div id="payment-links-panel" class="hidden rounded-lg border border-input bg-muted/30 p-3 text-sm" role="region" aria-live="polite">
                        <p id="payment-links-intro" class="text-secondary-foreground mb-2"></p>
                        <div id="payment-links-list" class="flex flex-col gap-2"></div>
                        <p id="payment-links-error" class="text-destructive text-xs mt-2 hidden"></p>
                    </div>
                    
                    <a href="{{ route('admin.invoices.edit', $invoice->id) }}" class="kt-btn kt-btn-outline w-full justify-center">
                        <i class="ki-filled ki-notepad-edit text-base me-2"></i>
                        Bewerken
                    </a>
                </div>
            </div>
            
            @if($invoice->reminders && $invoice->reminders->count() > 0)
            <div class="kt-card min-w-full mt-5">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Aanmaningen</h3>
                </div>
                <div class="kt-card-content flex flex-col gap-2.5 p-5">
                    @foreach($invoice->reminders->sortByDesc('sent_at') as $reminder)
                    <div class="flex flex-col gap-1 border-b border-input pb-2.5 last:border-0 last:pb-0">
                        <span class="text-sm font-semibold text-mono">{{ $reminder->sent_to_email }}</span>
                        <span class="text-xs text-secondary-foreground">
                            {{ $reminder->sent_at?->format('d-m-Y H:i') }}
                            · {{ ucfirst($reminder->reminder_type) }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if($invoice->payments && $invoice->payments->count() > 0)
            <div class="kt-card min-w-full mt-5">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Betalingen
                    </h3>
                </div>
                <div class="kt-card-content flex flex-col gap-2.5 p-5">
                    @foreach($invoice->payments as $payment)
                    <div class="flex items-center justify-between border-b border-input pb-2.5 last:border-0 last:pb-0">
                        <div class="flex flex-col gap-1">
                            <span class="text-sm font-semibold text-mono">€{{ number_format($payment->amount, 2, ',', '.') }}</span>
                            <span class="text-xs text-secondary-foreground">{{ $payment->paid_at ? $payment->paid_at->format('d M Y') : 'N/A' }}</span>
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
    function generatePaymentLinks(button) {
        const panel = document.getElementById('payment-links-panel');
        const list = document.getElementById('payment-links-list');
        const intro = document.getElementById('payment-links-intro');
        const errorEl = document.getElementById('payment-links-error');
        const label = button ? button.querySelector('.btn-payment-links-label') : null;
        const originalLabel = label ? label.textContent : 'Betaallinks';

        if (!panel || !list) {
            return;
        }

        if (label) {
            label.textContent = 'Laden…';
        }
        if (button) {
            button.disabled = true;
        }
        errorEl.classList.add('hidden');
        errorEl.textContent = '';
        list.innerHTML = '';
        intro.textContent = 'Betaallinks worden opgehaald…';
        panel.classList.remove('hidden');

        fetch('{{ route('admin.invoices.payment-links', $invoice->id) }}', {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Kon betaallinks niet ophalen (HTTP ' + response.status + ').');
                }
                return response.json();
            })
            .then(function (data) {
                intro.textContent = 'Factuur ' + (data.invoice_number || '') + ' · ' + (data.amount_label || '')
                    + (data.is_paid ? ' · reeds betaald' : '');

                if (!data.links || !data.links.length) {
                    list.innerHTML = '<p class="text-secondary-foreground text-xs">Geen betaallinks beschikbaar.</p>';
                    return;
                }

                data.links.forEach(function (item) {
                    const row = document.createElement('div');
                    row.className = 'flex flex-col gap-1 border-b border-input pb-2 last:border-0 last:pb-0';

                    const title = document.createElement('span');
                    title.className = 'font-medium text-mono text-xs';
                    title.textContent = item.label || 'Link';

                    const link = document.createElement('a');
                    link.href = item.url;
                    link.target = '_blank';
                    link.rel = 'noopener noreferrer';
                    link.className = 'text-primary text-xs break-all hover:underline';
                    link.textContent = item.url;

                    const actions = document.createElement('div');
                    actions.className = 'flex gap-2 mt-1';

                    const copyBtn = document.createElement('button');
                    copyBtn.type = 'button';
                    copyBtn.className = 'kt-btn kt-btn-outline kt-btn-sm';
                    copyBtn.textContent = 'Kopiëren';
                    copyBtn.addEventListener('click', function () {
                        copyPaymentLink(item.url, copyBtn);
                    });

                    const openBtn = document.createElement('a');
                    openBtn.href = item.url;
                    openBtn.target = '_blank';
                    openBtn.rel = 'noopener noreferrer';
                    openBtn.className = 'kt-btn kt-btn-outline kt-btn-sm';
                    openBtn.textContent = 'Openen';

                    actions.appendChild(copyBtn);
                    actions.appendChild(openBtn);

                    row.appendChild(title);
                    if (item.hint) {
                        const hint = document.createElement('span');
                        hint.className = 'text-xs text-muted-foreground';
                        hint.textContent = item.hint;
                        row.appendChild(hint);
                    }
                    row.appendChild(link);
                    row.appendChild(actions);
                    list.appendChild(row);
                });

                panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            })
            .catch(function (error) {
                console.error('Error:', error);
                intro.textContent = '';
                errorEl.textContent = error.message || 'Er is een fout opgetreden bij het ophalen van betaallinks.';
                errorEl.classList.remove('hidden');
            })
            .finally(function () {
                if (button) {
                    button.disabled = false;
                }
                if (label) {
                    label.textContent = originalLabel;
                }
            });
    }

    function copyPaymentLink(url, button) {
        const done = function () {
            const prev = button.textContent;
            button.textContent = 'Gekopieerd';
            setTimeout(function () {
                button.textContent = prev;
            }, 1500);
        };

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(done).catch(function () {
                window.prompt('Kopieer deze link:', url);
            });
        } else {
            window.prompt('Kopieer deze link:', url);
        }
    }
</script>
@endpush
@endsection

