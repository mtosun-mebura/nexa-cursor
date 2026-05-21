@extends('admin.layouts.app')

@section('title', 'Chauffeur dispatch')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-col gap-5 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">
            Chauffeur dispatch
        </h1>
        <p class="text-sm text-muted-foreground">
            Instellingen voor de chauffeur-app en meldingen bij nieuwe boekingen. Geldt per bedrijf (tenant).
            Zonder eigen acceptatietijd wordt de serverstandaard gebruikt (nu {{ (int) round($envDefaultSeconds / 60) }} min).
        </p>
        <div class="pt-3">
            <a href="{{ route('admin.taxi.ride_requests.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug naar ritten
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5">{{ session('success') }}</div>
    @endif

    <form action="{{ route('admin.taxi.dispatch_settings.update') }}" method="POST" class="kt-card min-w-full">
        @csrf
        @method('PUT')

        <div class="kt-card-header">
            <h3 class="kt-card-title">Acceptatietimer</h3>
        </div>

        <div class="kt-card-table kt-scrollable-x-auto pb-3">
            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Acceptatietijd (minuten)</td>
                    <td class="min-w-48 w-full">
                        <input
                            type="number"
                            name="offer_ttl_minutes"
                            id="offer_ttl_minutes"
                            class="kt-input w-full max-w-md @error('offer_ttl_minutes') border-destructive @enderror"
                            min="{{ $minMinutes }}"
                            max="{{ $maxMinutes }}"
                            step="1"
                            required
                            value="{{ old('offer_ttl_minutes', $offerTtlMinutes) }}"
                        >
                        <p class="text-xs text-muted-foreground mt-1">
                            Tussen {{ $minMinutes }} en {{ $maxMinutes }} minuten
                            ({{ $offerTtlSeconds }} seconden in de app-timer).
                        </p>
                        @error('offer_ttl_minutes')
                            <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>
            </table>
        </div>

        <div class="kt-card-header border-t border-border">
            <h3 class="kt-card-title">Boekingsmeldingen</h3>
        </div>

        <div class="kt-card-table kt-scrollable-x-auto pb-3">
            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top pt-4">WhatsApp bij boeking</td>
                    <td class="min-w-48 w-full pt-4">
                        <label class="inline-flex items-center gap-2">
                            <input type="hidden" name="booking_whatsapp_enabled" value="0">
                            <input type="checkbox"
                                   class="kt-checkbox"
                                   name="booking_whatsapp_enabled"
                                   value="1"
                                   {{ old('booking_whatsapp_enabled', $bookingWhatsappEnabled ? '1' : '0') === '1' ? 'checked' : '' }}>
                            <span class="text-sm text-secondary-foreground">Automatisch WhatsApp-bericht versturen na elke boeking</span>
                        </label>
                        <p class="text-xs text-muted-foreground mt-1">
                            @if($whatsappApiConfigured)
                                WhatsApp Business API is geconfigureerd op de server; berichten worden direct verstuurd.
                            @else
                                <span class="text-destructive">WhatsApp Business API ontbreekt in de serverinstellingen.</span>
                                Automatisch versturen werkt pas na configuratie van token en Phone Number ID (admin → Instellingen → WhatsApp).
                            @endif
                        </p>
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">WhatsApp-ontvangernummer</td>
                    <td class="min-w-48 w-full">
                        <input type="tel"
                               name="booking_whatsapp_number"
                               class="kt-input w-full max-w-md @error('booking_whatsapp_number') border-destructive @enderror"
                               value="{{ old('booking_whatsapp_number', $bookingWhatsappNumber) }}"
                               placeholder="0612345678 of +31612345678"
                               autocomplete="tel">
                        <p class="text-xs text-muted-foreground mt-1">
                            Nummer dat de boekingssamenvatting ontvangt (bijv. centrale of planner).
                            @if($envFallbackWhatsappNumber !== '' && ! $hasStoredWhatsappNumber)
                                Leeg laten gebruikt de serverstandaard: {{ $envFallbackWhatsappNumber }}.
                            @endif
                        </p>
                        @error('booking_whatsapp_number')
                            <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">WhatsApp-fallback (klant)</td>
                    <td class="min-w-48 w-full">
                        <label class="inline-flex items-center gap-2">
                            <input type="hidden" name="booking_whatsapp_click_to_chat" value="0">
                            <input type="checkbox"
                                   class="kt-checkbox"
                                   name="booking_whatsapp_click_to_chat"
                                   value="1"
                                   {{ old('booking_whatsapp_click_to_chat', $bookingWhatsappClickToChat ? '1' : '0') === '1' ? 'checked' : '' }}>
                            <span class="text-sm text-secondary-foreground">Boekingsknop opent WhatsApp bij klant (alleen zonder automatisch versturen)</span>
                        </label>
                        <p class="text-xs text-muted-foreground mt-1">
                            Fallback als automatisch versturen uit staat of de Business API niet beschikbaar is.
                        </p>
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">E-mail naar chauffeurs</td>
                    <td class="min-w-48 w-full">
                        <label class="inline-flex items-center gap-2">
                            <input type="hidden" name="booking_driver_email_enabled" value="0">
                            <input type="checkbox"
                                   class="kt-checkbox"
                                   name="booking_driver_email_enabled"
                                   value="1"
                                   {{ old('booking_driver_email_enabled', $bookingDriverEmailEnabled ? '1' : '0') === '1' ? 'checked' : '' }}>
                            <span class="text-sm text-secondary-foreground">Stuur elke chauffeur een e-mail bij een nieuwe rit</span>
                        </label>
                        <p class="text-xs text-muted-foreground mt-1">
                            Verstuurd naar het e-mailadres van elk chauffeur-account binnen dit bedrijf.
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="kt-card-header border-t border-border">
            <h3 class="kt-card-title">Betalingen (Mollie)</h3>
        </div>

        <div class="kt-card-table kt-scrollable-x-auto pb-3">
            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top pt-4">Mollie (betalingsprovider)</td>
                    <td class="min-w-48 w-full pt-4">
                        @if($mollieSummary['configured'] && $mollieSummary['provider'])
                            <p class="text-sm text-secondary-foreground mb-2">
                                <strong>{{ $mollieSummary['provider']->name }}</strong>
                                @if($mollieSummary['is_active'])
                                    <span class="text-green-600">· actief</span>
                                @else
                                    <span class="text-amber-600">· niet actief</span>
                                @endif
                                @if($mollieSummary['test_mode'])
                                    <span class="text-muted-foreground">· testmodus</span>
                                @endif
                            </p>
                            <p class="text-xs text-muted-foreground mb-1">
                                API-sleutel: <code class="text-xs">{{ $mollieSummary['api_key_preview'] }}</code>
                            </p>
                            <p class="text-xs text-muted-foreground mb-2 break-all">
                                Webhook: {{ $mollieSummary['webhook_url'] }}
                            </p>
                            @if($canManagePaymentProviders)
                                <a href="{{ route('admin.payment-providers.edit', $mollieSummary['provider']) }}" class="kt-btn kt-btn-outline kt-btn-sm">
                                    Mollie-instellingen bewerken
                                </a>
                            @endif
                        @else
                            <p class="text-sm text-secondary-foreground mb-2">
                                Er is nog geen actieve Mollie-provider voor dit bedrijf. Configureer API-sleutel en webhook onder <strong>Betalingsproviders</strong>.
                            </p>
                            <p class="text-xs text-muted-foreground mb-2">
                                Aanbevolen webhook voor taxi-betalingen: <code class="text-xs break-all">{{ $defaultTaxiWebhookUrl }}</code>
                            </p>
                            <p class="text-xs text-muted-foreground mb-2">
                                Lokaal (<code>localhost</code> of <code>192.168.x.x</code>): Mollie kan die URL niet bereiken. Betalingen werken zonder webhook via terugkeer-URL en polling in de chauffeur-app. Voor webhooks: gebruik een tunnel (ngrok) en zet <code>TAXI_MOLLIE_WEBHOOK_URL</code> in <code>.env</code>.
                            </p>
                            @if($canManagePaymentProviders)
                                <a href="{{ route('admin.payment-providers.create') }}" class="kt-btn kt-btn-outline kt-btn-sm">
                                    Mollie-provider aanmaken
                                </a>
                            @endif
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top pt-4">Direct betalen bij boeking</td>
                    <td class="min-w-48 w-full pt-4">
                        <label class="inline-flex items-center gap-2">
                            <input type="hidden" name="payment_booking_enabled" value="0">
                            <input type="checkbox"
                                   class="kt-checkbox"
                                   name="payment_booking_enabled"
                                   value="1"
                                   {{ old('payment_booking_enabled', $paymentBookingEnabled ? '1' : '0') === '1' ? 'checked' : '' }}>
                            <span class="text-sm text-secondary-foreground">Klant betaalt direct via Mollie na het bevestigen van de boeking</span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top pt-4">Betalen in chauffeur-app</td>
                    <td class="min-w-48 w-full pt-4">
                        <label class="inline-flex items-center gap-2">
                            <input type="hidden" name="payment_driver_enabled" value="0">
                            <input type="checkbox"
                                   class="kt-checkbox"
                                   name="payment_driver_enabled"
                                   value="1"
                                   {{ old('payment_driver_enabled', $paymentDriverEnabled ? '1' : '0') === '1' ? 'checked' : '' }}>
                            <span class="text-sm text-secondary-foreground">Chauffeur toont QR-code; rit afronden pas na betaling</span>
                        </label>
                        <p class="text-xs text-muted-foreground mt-1">
                            Als beide opties aan staan, kiest de klant bij de boeking. Vereist een actieve Mollie-provider voor dit bedrijf (zie hierboven).
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="kt-card-footer flex gap-2.5">
            <button type="submit" class="kt-btn kt-btn-primary">Opslaan</button>
            <a href="{{ route('admin.taxi.ride_requests.index') }}" class="kt-btn kt-btn-outline">Naar ritten</a>
        </div>
    </form>
</div>
@endsection
