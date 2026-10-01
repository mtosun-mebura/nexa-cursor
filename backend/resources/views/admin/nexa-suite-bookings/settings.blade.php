@extends('admin.layouts.app')

@section('title', 'NEXA Suite boekingsinstellingen')

@php
    $platformCollect = (bool) ($platformCollectEnabled ?? config('nexa_payout.platform_collect_enabled', true));
@endphp

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <div>
            <h1 class="text-xl font-medium leading-none text-mono">NEXA Suite boekingsinstellingen</h1>
            <div class="text-sm text-secondary-foreground mt-2">Fee-percentage en maandelijkse fee-specificatie (PDF)</div>
        </div>
        <a href="{{ route('admin.payment-flows.guide') }}" class="kt-btn kt-btn-outline shrink-0">Uitleg betalingen</a>
    </div>

    @include('admin.nexa-suite-bookings.partials.nav')

    <div class="kt-card w-full min-w-0 mb-5">
        <div class="kt-card-header px-5 py-5">
            <h3 class="kt-card-title mb-0">Hoe dit gebruikt wordt</h3>
        </div>
        <div class="kt-card-content p-5 text-sm text-muted-foreground space-y-3">
            @if($platformCollect)
                <p class="mb-0">
                    <strong class="text-foreground">Platform collect staat aan</strong>
                    (standaard). De klant betaalt NEXA; de fee hieronder wordt <strong class="text-foreground">per rit ingehouden</strong>
                    bij settlement. Netto gaat naar de taxi(’s). Zie
                    <a href="{{ route('admin.payment-flows.guide') }}" class="text-primary underline">Uitleg betalingen</a>.
                </p>
                <p class="mb-0">
                    De maandelijkse “boekingsfactuur” is een <strong class="text-foreground">specificatie / naslag-PDF</strong>
                    van die reeds ingehouden fee (voor administratie van de tenant). Het is
                    <strong class="text-foreground">geen openstaande vordering</strong> en geen tweede incasso.
                    Aanmaningen zijn bij dit model niet nodig.
                </p>
            @else
                <p class="mb-0">
                    <strong class="text-foreground">Platform collect staat uit</strong>
                    (<code class="text-xs">NEXA_PLATFORM_COLLECT_ENABLED=false</code>).
                    Dan werkt deze pagina als klassieke provisie-incasso: maandelijks een factuur die de tenant moet betalen,
                    met betaaltermijn en aanmaningen.
                </p>
            @endif
            <ol class="list-decimal ps-5 mb-0 space-y-1">
                <li><strong class="text-foreground">Fee %</strong> — bron voor zowel rit-inhouding (platform collect) als de bedragen op de maand-PDF.</li>
                <li><strong class="text-foreground">Automatische facturatie</strong> — maakt (en mailt optioneel) de maandelijkse specificatie over voltooide NEXA Suite-ritten van de vorige maand.</li>
                <li><strong class="text-foreground">BTW / factuurkop</strong> — layout van die PDF. Bij platform collect: naslagdocument; bij collect uit: inningsfactuur.</li>
            </ol>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.nexa-suite-bookings.settings.update') }}">
        @csrf
        @method('PUT')

        <div class="grid gap-5 lg:gap-7.5">
            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header px-5 py-5"><h3 class="kt-card-title mb-0">Provisie</h3></div>
                <div class="kt-card-content p-5">
                    <label class="text-sm text-muted-foreground mb-1 block">Fee over elke gereden NEXA Suite-rit (%)</label>
                    <input type="number" name="fee_percent" min="0" max="100" step="1" inputmode="numeric" class="kt-input w-40 @error('fee_percent') border-destructive @enderror" value="{{ old('fee_percent', (int) $settings->fee_percent) }}" required>
                    <p class="text-xs text-muted-foreground mt-1.5 mb-0">
                        Bijvoorbeeld 10: over €100 ritomzet is de NEXA-fee €10 excl. BTW
                        @if($platformCollect)
                            — bij platform collect wordt dit per rit ingehouden; de maand-PDF toont hetzelfde percentage als naslag.
                        @else
                            — dit bedrag wordt maandelijks gefactureerd aan de tenant.
                        @endif
                    </p>
                    @error('fee_percent')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header px-5 py-5"><h3 class="kt-card-title mb-0">Automatische specificatie (PDF)</h3></div>
                <div class="kt-card-content p-5 space-y-4">
                    <label class="kt-label flex items-center gap-2">
                        <input type="hidden" name="auto_generate" value="0">
                        <input type="checkbox" name="auto_generate" value="1" class="kt-switch" @checked(old('auto_generate', $settings->auto_generate))>
                        Maandelijks automatisch specificaties aanmaken
                    </label>
                    <label class="kt-label flex items-center gap-2">
                        <input type="hidden" name="auto_send" value="0">
                        <input type="checkbox" name="auto_send" value="1" class="kt-switch" @checked(old('auto_send', $settings->auto_send))>
                        Aangemaakte PDF’s direct naar de tenant mailen
                    </label>
                    <div class="flex flex-wrap gap-4">
                        <div>
                            <label class="text-sm text-muted-foreground mb-1 block">Dag (1–28)</label>
                            <input type="number" name="billing_day" min="1" max="28" class="kt-input w-28" value="{{ old('billing_day', $settings->billing_day) }}" required>
                        </div>
                        <div>
                            <label class="text-sm text-muted-foreground mb-1 block">Tijd</label>
                            <input type="time" name="billing_time" class="kt-input w-36" value="{{ old('billing_time', substr((string) $settings->billing_time, 0, 5)) }}" required>
                        </div>
                    </div>
                    <p class="text-xs text-muted-foreground mb-0">
                        Op deze dag/tijd worden voltooide NEXA Suite-ritten van de vorige maand samengevat
                        @if($platformCollect)
                            tot een naslag-PDF (reeds ingehouden fee). Handmatig: tab Facturen of “Facturatie nu draaien” op Overzicht.
                        @else
                            tot een provisiefactuur. Handmatig: tab Facturen of “Facturatie nu draaien” op Overzicht.
                        @endif
                    </p>
                </div>
            </div>

            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header px-5 py-5"><h3 class="kt-card-title mb-0">BTW &amp; aanmaningen</h3></div>
                <div class="kt-card-content p-5">
                    @if($platformCollect)
                        <p class="text-xs text-muted-foreground mb-4">
                            BTW geldt voor de PDF-regels. Betaaltermijn en aanmaningen worden bij platform collect
                            <strong class="text-foreground">niet gebruikt voor fee-incasso</strong> (fee is al ingehouden).
                            Laat de waarden staan voor als collect ooit uit staat, of voor historische facturen.
                        </p>
                    @else
                        <p class="text-xs text-muted-foreground mb-4">
                            Platform collect staat uit: deze velden sturen de klassieke provisie-incasso (termijn + aanmaningen).
                        </p>
                    @endif
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label class="text-sm text-muted-foreground mb-1 block">BTW %</label>
                            <input type="number" name="tax_rate_percent" min="0" max="100" step="1" inputmode="numeric" class="kt-input w-full @error('tax_rate_percent') border-destructive @enderror" value="{{ old('tax_rate_percent', (int) $settings->tax_rate_percent) }}" required>
                            @error('tax_rate_percent')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="text-sm text-muted-foreground mb-1 block">Betaaltermijn (dagen)</label>
                            <input type="number" name="payment_terms_days" min="1" max="365" class="kt-input w-full" value="{{ old('payment_terms_days', $settings->payment_terms_days) }}" required>
                        </div>
                        <div>
                            <label class="text-sm text-muted-foreground mb-1 block">1e aanmaning na (dagen)</label>
                            <input type="number" name="dunning_first_interval_days" min="1" max="90" class="kt-input w-full" value="{{ old('dunning_first_interval_days', $settings->dunning_first_interval_days) }}" required>
                        </div>
                        <div>
                            <label class="text-sm text-muted-foreground mb-1 block">2e aanmaning na (dagen)</label>
                            <input type="number" name="dunning_interval_days" min="1" max="90" class="kt-input w-full" value="{{ old('dunning_interval_days', $settings->dunning_interval_days) }}" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header px-5 py-5"><h3 class="kt-card-title mb-0">Factuurkop (PDF)</h3></div>
                <div class="kt-card-content p-5 space-y-4">
                    <div>
                        <label class="text-sm text-muted-foreground mb-1 block">Titel op PDF</label>
                        <input type="text" name="invoice_title" class="kt-input w-full" value="{{ old('invoice_title', $settings->invoice_title) }}" placeholder="{{ $platformCollect ? 'NEXA Suite fee-specificatie' : 'NEXA Suite boekingsfactuur' }}">
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="text-sm text-muted-foreground mb-1 block">Prefix factuurnummer</label>
                            <input type="text" name="invoice_number_prefix" class="kt-input w-full" value="{{ old('invoice_number_prefix', $settings->invoice_number_prefix) }}" required>
                        </div>
                        <div>
                            <label class="text-sm text-muted-foreground mb-1 block">Afzender e-mail</label>
                            <input type="email" name="sender_email" class="kt-input w-full" value="{{ old('sender_email', $settings->sender_email) }}">
                        </div>
                    </div>
                    <div>
                        <label class="text-sm text-muted-foreground mb-1 block">Afzendernaam</label>
                        <input type="text" name="sender_name" class="kt-input w-full" value="{{ old('sender_name', $settings->sender_name) }}">
                    </div>
                    <div>
                        <label class="text-sm text-muted-foreground mb-1 block">Voettekst</label>
                        <textarea name="invoice_footer" rows="3" class="kt-input w-full">{{ old('invoice_footer', $settings->invoice_footer) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="admin-form-actions">
                <button type="submit" class="kt-btn kt-btn-primary">Opslaan</button>
            </div>
        </div>
    </form>
</div>
@endsection
