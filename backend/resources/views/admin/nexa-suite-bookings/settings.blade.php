@extends('admin.layouts.app')

@section('title', __('NEXA Suite boekingsinstellingen'))

@php
    $platformCollect = (bool) ($platformCollectEnabled ?? config('nexa_payout.platform_collect_enabled', true));
@endphp

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <div>
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('NEXA Suite boekingsinstellingen') }}</h1>
            <div class="text-sm text-secondary-foreground mt-2">{{ __('Mollie-sleutel (test/live), fee en maandelijkse specificatie') }}</div>
        </div>
        <a href="{{ route('admin.payment-flows.guide') }}" class="kt-btn kt-btn-outline shrink-0">{{ __('Uitleg betalingen') }}</a>
    </div>

    @include('admin.nexa-suite-bookings.partials.nav')

    <div class="kt-card w-full min-w-0 mb-5">
        <div class="kt-card-header px-5 py-5">
            <h3 class="kt-card-title mb-0">{{ __('Hoe dit gebruikt wordt') }}</h3>
        </div>
        <div class="kt-card-content p-5 text-sm text-muted-foreground space-y-3">
            @if($platformCollect)
                <p class="mb-0">
                    <strong class="text-foreground">{{ __('Platform collect staat aan') }}</strong>
                    {{ __('(standaard). De klant betaalt NEXA; de fee hieronder wordt') }} <strong class="text-foreground">{{ __('per rit ingehouden') }}</strong>
                    {{ __('bij settlement. Netto gaat naar de taxi(’s). Zie') }}
                    <a href="{{ route('admin.payment-flows.guide') }}" class="text-primary underline">{{ __('Uitleg betalingen') }}</a>.
                </p>
                <p class="mb-0">
                    {{ __('De maandelijkse “boekingsfactuur” is een') }} <strong class="text-foreground">{{ __('specificatie / naslag-PDF') }}</strong>
                    {{ __('van die reeds ingehouden fee (voor administratie van de tenant). Het is') }}
                    <strong class="text-foreground">{{ __('geen openstaande vordering') }}</strong> {{ __('en geen tweede incasso.') }}
                    {{ __('Aanmaningen zijn bij dit model niet nodig.') }}
                </p>
            @else
                <p class="mb-0">
                    <strong class="text-foreground">{{ __('Platform collect staat uit') }}</strong>
                    (<code class="text-xs">NEXA_PLATFORM_COLLECT_ENABLED=false</code>).
                    {{ __('Dan werkt deze pagina als klassieke provisie-incasso: maandelijks een factuur die de tenant moet betalen,') }}
                    {{ __('met betaaltermijn en aanmaningen.') }}
                </p>
            @endif
            <p class="mb-0">
                {{ __('Ophalen, de vraag aan de klant en wanneer de chauffeur wordt uitbetaald:') }}
                <a href="{{ route('admin.nexa-suite-bookings.procedure') }}" class="text-primary underline">{{ __('Werkwijze marktplaats') }}</a>.
            </p>
            <ol class="list-decimal ps-5 mb-0 space-y-1">
                <li><strong class="text-foreground">Mollie</strong> — {{ __('platform-sleutel voor betalingen in de klant-app') }} (<code>test_</code> / <code>live_</code>). {{ __('Via Configuraties → Nexa Suite.') }}</li>
                <li><strong class="text-foreground">Fee %</strong> — {{ __('bron voor zowel rit-inhouding (platform collect) als de bedragen op de maand-PDF.') }}</li>
                <li><strong class="text-foreground">{{ __('Automatische facturatie') }}</strong> — {{ __('maakt (en mailt optioneel) de maandelijkse specificatie over voltooide NEXA Suite-ritten van de vorige maand.') }}</li>
                <li><strong class="text-foreground">{{ __('BTW / factuurkop') }}</strong> — {{ __('layout van die PDF. Bij platform collect: naslagdocument; bij collect uit: inningsfactuur.') }}</li>
            </ol>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.nexa-suite-bookings.settings.update') }}">
        @csrf
        @method('PUT')

        <div class="grid gap-5 lg:gap-7.5">
            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header px-5 py-5">
                    <h3 class="kt-card-title mb-0">{{ __('Mollie (klant-app / marktplaats)') }}</h3>
                </div>
                <div class="kt-card-content p-0">
                    <div class="px-3 sm:px-5 pb-3 min-w-0">
                        <p class="text-xs text-muted-foreground pt-3 mb-3">
                            {{ __('API-sleutel van het') }} <strong class="text-foreground">{{ __('NEXA Mollie-account') }}</strong>
                            {{ __('waarmee klanten in de Nexa Suite-app betalen. Gebruik') }} <code>test_…</code> {{ __('in staging') }}
                            {{ __('en') }} <code>live_…</code> {{ __('in productie. Geen tenant-sleutel.') }}
                        </p>
                        <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal align-top whitespace-nowrap">{{ __('Status') }}</td>
                                <td class="min-w-0 w-full">
                                    @if($mollieConfigured ?? false)
                                        <span class="kt-badge kt-badge-sm kt-badge-success">{{ __('Geconfigureerd') }}</span>
                                        @if(($mollieKeyMode ?? null) === 'live')
                                            <span class="kt-badge kt-badge-sm kt-badge-primary ms-1">live</span>
                                        @elseif(($mollieKeyMode ?? null) === 'test')
                                            <span class="kt-badge kt-badge-sm kt-badge-warning ms-1">test</span>
                                        @endif
                                    @else
                                        <span class="kt-badge kt-badge-sm kt-badge-warning">{{ __('Niet geconfigureerd') }}</span>
                                    @endif
                                    @if($mollieFromPlatformFallback ?? false)
                                        <div class="text-xs text-muted-foreground mt-1 min-w-0 whitespace-normal break-words">
                                            {{ __('Tijdelijk via NEXA facturatie /') }} <code>PLATFORM_MOLLIE_API_KEY</code>.
                                            {{ __('Sla hier een eigen sleutel op voor go-live.') }}
                                        </div>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal align-top whitespace-nowrap">{{ __('API-sleutel') }}</td>
                                <td class="min-w-0">
                                    @if(!empty($mollieApiKeyMasked))
                                        <div class="text-xs text-muted-foreground mb-1 min-w-0 whitespace-normal break-words">
                                            {{ __('Huidige sleutel:') }} <code class="break-all">{{ $mollieApiKeyMasked }}</code> {{ __('(versleuteld opgeslagen)') }}
                                        </div>
                                    @endif
                                    <div class="relative w-full max-w-md">
                                        <input type="password"
                                               name="mollie_api_key"
                                               class="kt-input w-full @error('mollie_api_key') border-destructive @enderror"
                                               value=""
                                               autocomplete="new-password"
                                               placeholder="{{ !empty($mollieApiKeyMasked) ? __('Leeg laten om huidige sleutel te behouden') : 'test_… of live_…' }}">
                                    </div>
                                    <div class="text-xs text-muted-foreground mt-1">
                                        {{ __('Alleen Nexa Suite klantbetalingen. Niet de tenant-betalingsprovider.') }}
                                    </div>
                                    @error('mollie_api_key')
                                        <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                    @enderror
                                    @if(!empty($mollieApiKeyMasked))
                                        <label class="kt-label flex items-center gap-2 mt-2 mb-0">
                                            <input type="checkbox" name="clear_mollie_api_key" value="1" class="kt-checkbox">
                                            <span class="text-sm">{{ __('API-sleutel verwijderen') }}</span>
                                        </label>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal align-top whitespace-nowrap">{{ __('Webhook-URL') }}</td>
                                <td class="min-w-0">
                                    <input type="url"
                                           name="mollie_webhook_url"
                                           class="kt-input w-full min-w-0 max-w-xl @error('mollie_webhook_url') border-destructive @enderror"
                                           value="{{ old('mollie_webhook_url', $settings->mollie_webhook_url) }}"
                                           placeholder="{{ $defaultTaxiWebhookUrl ?? url('/api/taxi/webhooks/mollie') }}">
                                    <div class="text-xs text-muted-foreground mt-1 min-w-0 whitespace-normal">
                                        {{ __('Publieke URL voor Mollie-statusupdates op ritten. Leeg laten = standaard taxi-webhook.') }}
                                        <span class="block mt-1">
                                            {{ __('Standaard:') }}
                                            <code class="break-all">{{ $defaultTaxiWebhookUrl ?? url('/api/taxi/webhooks/mollie') }}</code>
                                        </span>
                                    </div>
                                    @error('mollie_webhook_url')
                                        <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                    @enderror
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            @php
                $publicHostEditId = session('public_host_edit');
                $publicHostShowDraft = (bool) session('public_host_draft');
            @endphp
            <div class="kt-card w-full min-w-0" id="publieke-host">
                <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
                    <h3 class="kt-card-title mb-0">{{ __('Publieke host') }}</h3>
                    <button type="button" class="kt-btn kt-btn-outline shrink-0" id="nexa-public-host-add">
                        <i class="ki-filled ki-plus me-2"></i>
                        {{ __('Toevoegen') }}
                    </button>
                </div>
                <div class="kt-card-content p-0">
                    <div class="px-5 pt-5 pb-5 min-w-0">
                        <p class="text-sm text-muted-foreground mb-4">
                            {{ __('De gemarkeerde omgeving is de publieke host voor Mollie-terugkeer en webhooks. In de app kies je zelf welke server je aanroept.') }}
                        </p>
                        @if($errors->has('public_hosts'))
                            <p class="text-sm text-destructive mb-4">{{ $errors->first('public_hosts') }}</p>
                        @endif
                        <div class="flex flex-col gap-4">
                            @foreach(($publicHosts ?? []) as $publicHostRow)
                                @php
                                    $rowIsEdit = $publicHostEditId === $publicHostRow['id'];
                                    $rowNameInvalid = $rowIsEdit && $errors->has('name');
                                    $rowHostInvalid = $rowIsEdit && $errors->has('host');
                                    $rowName = $rowIsEdit ? old('name', $publicHostRow['name']) : $publicHostRow['name'];
                                    $rowHost = $rowIsEdit ? old('host', $publicHostRow['host']) : $publicHostRow['host'];
                                @endphp
                                <div class="rounded-lg border border-border px-4 py-4 min-w-0">
                                    <div class="flex flex-wrap items-start gap-3">
                                        <div class="min-w-0 w-full sm:w-56 sm:max-w-xs">
                                            <label class="text-sm text-secondary-foreground mb-1 block" for="nexa-host-name-{{ $publicHostRow['id'] }}">{{ __('Omgeving') }}</label>
                                            <input type="text"
                                                   id="nexa-host-name-{{ $publicHostRow['id'] }}"
                                                   name="name"
                                                   form="nexa-public-host-{{ $publicHostRow['id'] }}"
                                                   value="{{ $rowName }}"
                                                   autocomplete="off"
                                                   class="kt-input w-full min-w-0 {{ $rowNameInvalid ? 'border-destructive' : '' }}"
                                                   aria-invalid="{{ $rowNameInvalid ? 'true' : 'false' }}">
                                            @if($rowNameInvalid)
                                                <p class="text-sm text-destructive mt-1 mb-0">{{ $errors->first('name') }}</p>
                                            @endif
                                        </div>
                                        <div class="min-w-0 w-full sm:min-w-64 sm:flex-1">
                                            <label class="text-sm text-secondary-foreground mb-1 block" for="nexa-host-url-{{ $publicHostRow['id'] }}">{{ __('Host') }}</label>
                                            <div class="flex items-center gap-2">
                                                <input type="text"
                                                       id="nexa-host-url-{{ $publicHostRow['id'] }}"
                                                       name="host"
                                                       form="nexa-public-host-{{ $publicHostRow['id'] }}"
                                                       value="{{ $rowHost }}"
                                                       inputmode="url"
                                                       autocomplete="off"
                                                       placeholder="https://nexasuite.nl"
                                                       class="kt-input min-w-0 flex-1 {{ $rowHostInvalid ? 'border-destructive' : '' }}"
                                                       aria-invalid="{{ $rowHostInvalid ? 'true' : 'false' }}">
                                                <button type="submit"
                                                        form="nexa-public-host-{{ $publicHostRow['id'] }}"
                                                        class="kt-btn kt-btn-icon kt-btn-primary shrink-0"
                                                        aria-label="{{ __('Opslaan') }}"
                                                        title="{{ __('Opslaan') }}">
                                                    <i class="ki-filled ki-check"></i>
                                                </button>
                                                <button type="submit"
                                                        form="nexa-public-host-delete-{{ $publicHostRow['id'] }}"
                                                        class="kt-btn kt-btn-icon kt-btn-outline shrink-0"
                                                        aria-label="{{ __('Verwijderen') }}"
                                                        title="{{ __('Verwijderen') }}">
                                                    <i class="ki-filled ki-trash"></i>
                                                </button>
                                            </div>
                                            @if($rowHostInvalid)
                                                <p class="text-sm text-destructive mt-1 mb-0">{{ $errors->first('host') }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="pt-4">
                                        <button type="submit"
                                                form="nexa-public-host-pay-{{ $publicHostRow['id'] }}"
                                                class="kt-btn kt-btn-sm {{ $publicHostRow['payments'] ? 'kt-btn-primary' : 'kt-btn-outline' }}"
                                                aria-pressed="{{ $publicHostRow['payments'] ? 'true' : 'false' }}">
                                            @if($publicHostRow['payments'])
                                                <i class="ki-filled ki-check me-2"></i>
                                            @endif
                                            {{ __('Gebruik voor betalingen') }}
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                            <div id="nexa-public-host-draft" class="rounded-lg border border-border px-4 py-4 min-w-0 {{ $publicHostShowDraft ? '' : 'hidden' }}">
                                @php
                                    $draftNameInvalid = $publicHostShowDraft && $errors->has('name');
                                    $draftHostInvalid = $publicHostShowDraft && $errors->has('host');
                                @endphp
                                <div class="flex flex-wrap items-start gap-3">
                                    <div class="min-w-0 w-full sm:w-56 sm:max-w-xs">
                                        <label class="text-sm text-secondary-foreground mb-1 block" for="nexa-host-name-new">{{ __('Omgeving') }}</label>
                                        <input type="text"
                                               id="nexa-host-name-new"
                                               name="name"
                                               form="nexa-public-host-new"
                                               value="{{ $publicHostShowDraft ? old('name') : '' }}"
                                               autocomplete="off"
                                               class="kt-input w-full min-w-0 {{ $draftNameInvalid ? 'border-destructive' : '' }}"
                                               aria-invalid="{{ $draftNameInvalid ? 'true' : 'false' }}">
                                        @if($draftNameInvalid)
                                            <p class="text-sm text-destructive mt-1 mb-0">{{ $errors->first('name') }}</p>
                                        @endif
                                    </div>
                                    <div class="min-w-0 w-full sm:min-w-64 sm:flex-1">
                                        <label class="text-sm text-secondary-foreground mb-1 block" for="nexa-host-url-new">{{ __('Host') }}</label>
                                        <div class="flex items-center gap-2">
                                            <input type="text"
                                                   id="nexa-host-url-new"
                                                   name="host"
                                                   form="nexa-public-host-new"
                                                   value="{{ $publicHostShowDraft ? old('host') : '' }}"
                                                   inputmode="url"
                                                   autocomplete="off"
                                                   placeholder="https://nexasuite.nl"
                                                   class="kt-input min-w-0 flex-1 {{ $draftHostInvalid ? 'border-destructive' : '' }}"
                                                   aria-invalid="{{ $draftHostInvalid ? 'true' : 'false' }}">
                                            <button type="submit"
                                                    form="nexa-public-host-new"
                                                    class="kt-btn kt-btn-icon kt-btn-primary shrink-0"
                                                    aria-label="{{ __('Opslaan') }}"
                                                    title="{{ __('Opslaan') }}">
                                                <i class="ki-filled ki-check"></i>
                                            </button>
                                            <button type="button"
                                                    id="nexa-public-host-draft-discard"
                                                    class="kt-btn kt-btn-icon kt-btn-outline shrink-0"
                                                    aria-label="{{ __('Verwijderen') }}"
                                                    title="{{ __('Verwijderen') }}">
                                                <i class="ki-filled ki-trash"></i>
                                            </button>
                                        </div>
                                        @if($draftHostInvalid)
                                            <p class="text-sm text-destructive mt-1 mb-0">{{ $errors->first('host') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header px-5 py-5"><h3 class="kt-card-title mb-0">{{ __('Provisie') }}</h3></div>
                <div class="kt-card-content p-5">
                    <label class="text-sm text-muted-foreground mb-1 block">{{ __('Fee over elke gereden NEXA Suite-rit (%)') }}</label>
                    <input type="number" name="fee_percent" min="0" max="100" step="1" inputmode="numeric" class="kt-input w-40 @error('fee_percent') border-destructive @enderror" value="{{ old('fee_percent', (int) $settings->fee_percent) }}" required>
                    <p class="text-xs text-muted-foreground mt-1.5 mb-0">
                        {{ __('Bijvoorbeeld 10: over €100 ritomzet is de NEXA-fee €10 excl. BTW') }}
                        @if($platformCollect)
                            {{ __('— bij platform collect wordt dit per rit ingehouden; de maand-PDF toont hetzelfde percentage als naslag.') }}
                        @else
                            {{ __('— dit bedrag wordt maandelijks gefactureerd aan de tenant.') }}
                        @endif
                    </p>
                    @error('fee_percent')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                </div>
            </div>

            @php
                $fm = $feeMinimum ?? [];
                $feeMethodLabels = [
                    'ideal' => 'iDEAL / Wero',
                    'creditcard' => 'Creditcard / betaalpas (Visa, Mastercard)',
                    'applepay' => 'Apple Pay',
                    'bancontact' => 'Bancontact',
                    'default' => 'Overige / onbekende methode',
                ];
            @endphp
            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header px-5 py-5"><h3 class="kt-card-title mb-0">{{ __('Minimale fee (Mollie-kosten)') }}</h3></div>
                <div class="kt-card-content p-0">
                    <div class="px-3 sm:px-5 pb-3 min-w-0">
                        <p class="text-xs text-muted-foreground pt-4 mb-2">
                            {{ __('De fee per rit is het') }} <strong>{{ __('hoogste') }}</strong> {{ __('van het percentage hierboven en dit minimum, en nooit meer dan het ritbedrag.') }}
                            {{ __('Het minimum = Mollie-kosten voor de klantbetaling + Mollie-kosten voor de uitbetaling aan de chauffeur + veiligheidsmarge.') }}
                            {{ __('Alle bedragen excl. btw (Mollie-tarieven zijn excl. btw).') }}
                            <a href="#fee-overview" class="text-primary">{{ __('Zie het overzicht onderaan deze pagina.') }}</a>
                        </p>
                        <div class="flex flex-wrap items-center gap-3 pb-3">
                            <button type="submit" form="fee-refresh-prices-form" class="kt-btn kt-btn-outline">
                                <i class="ki-filled ki-arrows-circle me-2"></i>
                                {{ __('Prijzen bij Mollie ophalen') }}
                            </button>
                            <span class="text-xs text-muted-foreground">
                                {{ __('Prijzen:') }} {{ $feePricesInfo }}.
                                {{ __('Haalt de tarieven per betaalmethode van jouw Mollie-account op en slaat ze direct op (niet-opgeslagen wijzigingen in dit formulier gaan verloren).') }}
                                {{ __('Uitbetalingskosten zijn niet via Mollie op te halen en blijven handmatig.') }}
                            </span>
                        </div>
                        <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Minimum automatisch berekenen') }}</td>
                                <td class="min-w-48 w-full">
                                    <input type="hidden" name="fee_minimum_enabled" value="0">
                                    <label class="kt-label flex items-center gap-2 mb-0" for="fee_minimum_enabled">
                                        <input type="checkbox" name="fee_minimum_enabled" id="fee_minimum_enabled" value="1" class="kt-switch kt-switch-sm shrink-0"
                                               @checked(old('fee_minimum_enabled', $fm['enabled'] ?? true))>
                                        <span class="text-sm text-muted-foreground">{{ __('Fee nooit lager dan de Mollie-kosten van de rit') }}</span>
                                    </label>
                                </td>
                            </tr>
                            @foreach($feeMethodLabels as $key => $label)
                                @php
                                    $fixedName = 'fee_methods.'.$key.'.fixed';
                                    $pctName = 'fee_methods.'.$key.'.percent';
                                @endphp
                                <tr>
                                    <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Mollie pay-in: :method', ['method' => __($label)]) }}</td>
                                    <td class="min-w-48 w-full">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-xs">{{ __('vast €') }}</span>
                                            <input type="number" step="0.01" min="0" max="10" inputmode="decimal" name="fee_methods[{{ $key }}][fixed]"
                                                   class="kt-input w-28 @error($fixedName) border-destructive @enderror" @error($fixedName) aria-invalid="true" @enderror
                                                   value="{{ old('fee_methods.'.$key.'.fixed', number_format((float) ($fm['methods'][$key]['fixed'] ?? 0), 2, '.', '')) }}" required>
                                            <span class="text-xs">{{ __('+ %') }}</span>
                                            <input type="number" step="0.01" min="0" max="20" inputmode="decimal" name="fee_methods[{{ $key }}][percent]"
                                                   class="kt-input w-28 @error($pctName) border-destructive @enderror" @error($pctName) aria-invalid="true" @enderror
                                                   value="{{ old('fee_methods.'.$key.'.percent', number_format((float) ($fm['methods'][$key]['percent'] ?? 0), 2, '.', '')) }}" required>
                                        </div>
                                        @error($fixedName)<p class="text-sm text-destructive mt-1 mb-0">{{ $message }}</p>@enderror
                                        @error($pctName)<p class="text-sm text-destructive mt-1 mb-0">{{ $message }}</p>@enderror
                                    </td>
                                </tr>
                            @endforeach
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Mollie uitbetaling aan chauffeur (vast per rit, €)') }}</td>
                                <td class="min-w-48 w-full">
                                    <input type="number" step="0.01" min="0" max="10" inputmode="decimal" name="payout_fixed"
                                           class="kt-input w-28 @error('payout_fixed') border-destructive @enderror" @error('payout_fixed') aria-invalid="true" @enderror
                                           value="{{ old('payout_fixed', number_format((float) ($fm['payout_fixed'] ?? 0), 2, '.', '')) }}" required>
                                    <p class="text-xs text-muted-foreground mt-1.5 mb-0">{{ __('Mollie publiceert geen tarief voor Connect-overboekingen; standaard €0,25 (aanname, gelijk aan Mollie’s tarief per extra uitbetaling).') }}</p>
                                    @error('payout_fixed')<p class="text-sm text-destructive mt-1 mb-0">{{ $message }}</p>@enderror
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Veiligheidsmarge') }}</td>
                                <td class="min-w-48 w-full">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-xs">{{ __('vast €') }}</span>
                                        <input type="number" step="0.01" min="0" max="10" inputmode="decimal" name="margin_fixed"
                                               class="kt-input w-28 @error('margin_fixed') border-destructive @enderror" @error('margin_fixed') aria-invalid="true" @enderror
                                               value="{{ old('margin_fixed', number_format((float) ($fm['margin_fixed'] ?? 0), 2, '.', '')) }}" required>
                                        <span class="text-xs">{{ __('+ % van ritbedrag') }}</span>
                                        <input type="number" step="0.01" min="0" max="20" inputmode="decimal" name="margin_percent"
                                               class="kt-input w-28 @error('margin_percent') border-destructive @enderror" @error('margin_percent') aria-invalid="true" @enderror
                                               value="{{ old('margin_percent', number_format((float) ($fm['margin_percent'] ?? 0), 2, '.', '')) }}" required>
                                    </div>
                                    @error('margin_fixed')<p class="text-sm text-destructive mt-1 mb-0">{{ $message }}</p>@enderror
                                    @error('margin_percent')<p class="text-sm text-destructive mt-1 mb-0">{{ $message }}</p>@enderror
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header px-5 py-5"><h3 class="kt-card-title mb-0">NEXA Network</h3></div>
                <div class="kt-card-content p-5">
                    <input type="hidden" name="allow_marketplace_network_owner" value="0">
                    <label class="kt-label flex items-center gap-2 mb-0" for="allow_marketplace_network_owner">
                        <input type="checkbox" name="allow_marketplace_network_owner" id="allow_marketplace_network_owner" value="1" class="kt-switch kt-switch-sm shrink-0"
                               @checked(old('allow_marketplace_network_owner', $settings->allow_marketplace_network_owner ?? false))>
                        <span class="text-sm text-muted-foreground">{{ __('Marketplace-pakket mag zelf network-partners koppelen (marketplace↔marketplace)') }}</span>
                    </label>
                    <p class="text-xs text-muted-foreground mt-2 mb-0">
                        {{ __('Standaard uit: alleen Start/Pro/Business mag ritten via network uitzetten; marketplace mag wel als uitvoerder gekoppeld worden (met fee).') }}
                        {{ __('Aanzetten laat fee-only marketplace-bedrijven ook eigen partners koppelen en network inschakelen.') }}
                    </p>
                    @error('allow_marketplace_network_owner')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header px-5 py-5"><h3 class="kt-card-title mb-0">{{ __('Automatische specificatie (PDF)') }}</h3></div>
                <div class="kt-card-content p-5 space-y-4">
                    <label class="kt-label flex items-center gap-2">
                        <input type="hidden" name="auto_generate" value="0">
                        <input type="checkbox" name="auto_generate" value="1" class="kt-switch" @checked(old('auto_generate', $settings->auto_generate))>
                        {{ __('Maandelijks automatisch specificaties aanmaken') }}
                    </label>
                    <label class="kt-label flex items-center gap-2">
                        <input type="hidden" name="auto_send" value="0">
                        <input type="checkbox" name="auto_send" value="1" class="kt-switch" @checked(old('auto_send', $settings->auto_send))>
                        {{ __('Aangemaakte PDF’s direct naar de tenant mailen') }}
                    </label>
                    <div class="flex flex-wrap gap-4">
                        <div>
                            <label class="text-sm text-muted-foreground mb-1 block">{{ __('Dag (1–28)') }}</label>
                            <input type="number" name="billing_day" min="1" max="28" class="kt-input w-28" value="{{ old('billing_day', $settings->billing_day) }}" required>
                        </div>
                        <div>
                            <label class="text-sm text-muted-foreground mb-1 block">{{ __('Tijd') }}</label>
                            <input type="time" name="billing_time" class="kt-input w-36" value="{{ old('billing_time', substr((string) $settings->billing_time, 0, 5)) }}" required>
                        </div>
                    </div>
                    <p class="text-xs text-muted-foreground mb-0">
                        {{ __('Op deze dag/tijd worden voltooide NEXA Suite-ritten van de vorige maand samengevat') }}
                        @if($platformCollect)
                            {{ __('tot een naslag-PDF (reeds ingehouden fee). Handmatig: tab Facturen of “Facturatie nu draaien” op Overzicht.') }}
                        @else
                            {{ __('tot een provisiefactuur. Handmatig: tab Facturen of “Facturatie nu draaien” op Overzicht.') }}
                        @endif
                    </p>
                </div>
            </div>

            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header px-5 py-5"><h3 class="kt-card-title mb-0">{{ __('BTW & aanmaningen') }}</h3></div>
                <div class="kt-card-content p-5">
                    @if($platformCollect)
                        <p class="text-xs text-muted-foreground mb-4">
                            {{ __('BTW geldt voor de PDF-regels. Betaaltermijn en aanmaningen worden bij platform collect') }}
                            <strong class="text-foreground">{{ __('niet gebruikt voor fee-incasso') }}</strong> {{ __('(fee is al ingehouden).') }}
                            {{ __('Laat de waarden staan voor als collect ooit uit staat, of voor historische facturen.') }}
                        </p>
                    @else
                        <p class="text-xs text-muted-foreground mb-4">
                            {{ __('Platform collect staat uit: deze velden sturen de klassieke provisie-incasso (termijn + aanmaningen).') }}
                        </p>
                    @endif
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label class="text-sm text-muted-foreground mb-1 block">{{ __('BTW %') }}</label>
                            <input type="number" name="tax_rate_percent" min="0" max="100" step="1" inputmode="numeric" class="kt-input w-full @error('tax_rate_percent') border-destructive @enderror" value="{{ old('tax_rate_percent', (int) $settings->tax_rate_percent) }}" required>
                            @error('tax_rate_percent')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="text-sm text-muted-foreground mb-1 block">{{ __('Betaaltermijn (dagen)') }}</label>
                            <input type="number" name="payment_terms_days" min="1" max="365" class="kt-input w-full" value="{{ old('payment_terms_days', $settings->payment_terms_days) }}" required>
                        </div>
                        <div>
                            <label class="text-sm text-muted-foreground mb-1 block">{{ __('1e aanmaning na (dagen)') }}</label>
                            <input type="number" name="dunning_first_interval_days" min="1" max="90" class="kt-input w-full" value="{{ old('dunning_first_interval_days', $settings->dunning_first_interval_days) }}" required>
                        </div>
                        <div>
                            <label class="text-sm text-muted-foreground mb-1 block">{{ __('2e aanmaning na (dagen)') }}</label>
                            <input type="number" name="dunning_interval_days" min="1" max="90" class="kt-input w-full" value="{{ old('dunning_interval_days', $settings->dunning_interval_days) }}" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header px-5 py-5"><h3 class="kt-card-title mb-0">{{ __('Factuurkop (PDF)') }}</h3></div>
                <div class="kt-card-content p-5 space-y-4">
                    <div>
                        <label class="text-sm text-muted-foreground mb-1 block">{{ __('Titel op PDF') }}</label>
                        <input type="text" name="invoice_title" class="kt-input w-full" value="{{ old('invoice_title', $settings->invoice_title) }}" placeholder="{{ $platformCollect ? __('NEXA Suite fee-specificatie') : __('NEXA Suite boekingsfactuur') }}">
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="text-sm text-muted-foreground mb-1 block">{{ __('Prefix factuurnummer') }}</label>
                            <input type="text" name="invoice_number_prefix" class="kt-input w-full" value="{{ old('invoice_number_prefix', $settings->invoice_number_prefix) }}" required>
                        </div>
                        <div>
                            <label class="text-sm text-muted-foreground mb-1 block">{{ __('Afzender e-mail') }}</label>
                            <input type="email" name="sender_email" class="kt-input w-full" value="{{ old('sender_email', $settings->sender_email) }}">
                        </div>
                    </div>
                    <div>
                        <label class="text-sm text-muted-foreground mb-1 block">{{ __('Afzendernaam') }}</label>
                        <input type="text" name="sender_name" class="kt-input w-full" value="{{ old('sender_name', $settings->sender_name) }}">
                    </div>
                    <div>
                        <label class="text-sm text-muted-foreground mb-1 block">{{ __('Voettekst') }}</label>
                        <textarea name="invoice_footer" rows="3" class="kt-input w-full">{{ old('invoice_footer', $settings->invoice_footer) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="admin-form-actions">
                <button type="submit" class="kt-btn kt-btn-primary">{{ __('Opslaan') }}</button>
            </div>
        </div>
    </form>
    <form id="fee-refresh-prices-form" method="POST" action="{{ route('admin.nexa-suite-bookings.settings.refresh-prices') }}" class="hidden">
        @csrf
    </form>
    @foreach(($publicHosts ?? []) as $publicHostRow)
        <form id="nexa-public-host-{{ $publicHostRow['id'] }}" method="POST" action="{{ route('admin.nexa-suite-bookings.settings.public-hosts.update', $publicHostRow['id']) }}" class="hidden" novalidate>
            @csrf
            @method('PUT')
        </form>
        <form id="nexa-public-host-delete-{{ $publicHostRow['id'] }}"
              method="POST"
              action="{{ route('admin.nexa-suite-bookings.settings.public-hosts.destroy', $publicHostRow['id']) }}"
              class="hidden"
              data-confirm-title="{{ __('Omgeving verwijderen') }}"
              data-admin-confirm-label="{{ __('Verwijderen') }}"
              data-confirm-message="{{ __('Weet je zeker dat je de omgeving “:name” wilt verwijderen? Dit kan niet ongedaan worden gemaakt.', ['name' => $publicHostRow['name']]) }}">
            @csrf
            @method('DELETE')
        </form>
        <form id="nexa-public-host-pay-{{ $publicHostRow['id'] }}" method="POST" action="{{ route('admin.nexa-suite-bookings.settings.public-hosts.payments', $publicHostRow['id']) }}" class="hidden">
            @csrf
        </form>
    @endforeach
    <form id="nexa-public-host-new" method="POST" action="{{ route('admin.nexa-suite-bookings.settings.public-hosts.store') }}" class="hidden" novalidate>
        @csrf
    </form>
    <script>
    (function () {
        var add = document.getElementById('nexa-public-host-add');
        var draft = document.getElementById('nexa-public-host-draft');
        if (!add || !draft) return;
        add.addEventListener('click', function () {
            draft.classList.remove('hidden');
            var input = document.getElementById('nexa-host-name-new');
            if (input) input.focus();
        });
        var discard = document.getElementById('nexa-public-host-draft-discard');
        if (discard) {
            discard.addEventListener('click', function () {
                draft.classList.add('hidden');
                ['nexa-host-name-new', 'nexa-host-url-new'].forEach(function (id) {
                    var field = document.getElementById(id);
                    if (!field) return;
                    field.value = '';
                    field.classList.remove('border-destructive');
                    field.setAttribute('aria-invalid', 'false');
                });
                draft.querySelectorAll('.text-destructive').forEach(function (el) {
                    el.remove();
                });
            });
        }
    })();
    </script>

    @php
        $eur = fn ($v) => '€'.number_format((float) $v, 2, ',', '.');
        $feeNote = function (array $c) use ($eur) {
            return $c['capped'] ? 'afgekapt op ritbedrag' : ($c['minimum_applied'] ? 'minimum' : null);
        };
    @endphp
    <div class="kt-card w-full min-w-0 mt-5" id="fee-overview">
        <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
            <h3 class="kt-card-title mb-0">{{ __('Overzicht fee en wat je overhoudt') }}</h3>
            <form method="GET" action="{{ route('admin.nexa-suite-bookings.settings') }}#fee-overview" class="flex flex-wrap items-center gap-2">
                <label for="fee-custom-amount" class="text-sm text-muted-foreground mb-0">{{ __('Eigen ritprijs (€)') }}</label>
                <input type="text" inputmode="decimal" name="bedrag" id="fee-custom-amount" value="{{ $feeCustomRaw }}"
                       class="kt-input w-28 @if($feeCustomError) border-destructive @endif" @if($feeCustomError) aria-invalid="true" @endif placeholder="{{ __('bijv. 32,50') }}">
                <button type="submit" class="kt-btn kt-btn-outline">{{ __('Berekenen') }}</button>
            </form>
        </div>
        @if($feeCustomError)
            <p class="text-sm text-destructive px-5 mb-0 -mt-2">{{ $feeCustomError }}</p>
        @endif
        <div class="kt-card-content p-5 min-w-0">
            <p class="text-sm text-muted-foreground mb-1">{{ __('Prijzen:') }} {{ $feePricesInfo }}.</p>
            <p class="text-sm text-muted-foreground mb-4">
                {{ __('Berekend met de') }} <strong class="text-foreground">{{ __('opgeslagen') }}</strong> {{ __('instellingen: fee :percent%', ['percent' => (int) $settings->fee_percent]) }}
                @if(($feeMinimum['enabled'] ?? true))
                    {{ __('met minimum uit de Mollie-kosten. Sla wijzigingen hierboven eerst op om ze hier te zien.') }}
                @else
                    {{ __('zonder minimum (uitgeschakeld). Sla wijzigingen hierboven eerst op om ze hier te zien.') }}
                @endif
            </p>
            <div class="kt-scrollable-x-auto admin-table-scroll-wrap min-w-0">
                <table class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full">
                    <colgroup>
                        <col style="width: 11%">
                        <col style="width: 15%">
                        <col style="width: 12%">
                        <col style="width: 17%">
                        <col style="width: 14%">
                        <col style="width: 17%">
                        <col style="width: 14%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="text-left py-3" data-label="{{ __('Ritprijs') }}"><span class="block whitespace-nowrap">{{ __('Ritprijs') }}</span></th>
                            <th class="text-right py-3" data-label="{{ __('Chauffeur krijgt') }}"><span class="block whitespace-nowrap">{{ __('Chauffeur krijgt') }}</span></th>
                            <th class="text-right py-3" data-label="Fee"><span class="block whitespace-nowrap">Fee</span></th>
                            <th class="text-right py-3" data-label="{{ __('Mollie-kosten iDEAL') }}"><span class="block whitespace-nowrap">{{ __('Mollie-kosten iDEAL') }}</span><span class="block whitespace-nowrap text-xs font-normal text-muted-foreground">{{ __('pay-in + uitbetaling') }}</span></th>
                            <th class="text-right py-3" data-label="{{ __('Jij houdt over (iDEAL)') }}"><span class="block whitespace-nowrap">{{ __('Jij houdt over') }}</span><span class="block whitespace-nowrap text-xs font-normal text-muted-foreground">(iDEAL)</span></th>
                            <th class="text-right py-3" data-label="{{ __('Mollie-kosten kaart') }}"><span class="block whitespace-nowrap">{{ __('Mollie-kosten kaart') }}</span><span class="block whitespace-nowrap text-xs font-normal text-muted-foreground">{{ __('pay-in + uitbetaling') }}</span></th>
                            <th class="text-right py-3" data-label="{{ __('Jij houdt over (kaart)') }}"><span class="block whitespace-nowrap">{{ __('Jij houdt over') }}</span><span class="block whitespace-nowrap text-xs font-normal text-muted-foreground">{{ __('(kaart)') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($feeOverview as $row)
                            @php
                                $i = $row['ideal'];
                                $c = $row['card'];
                                $differs = abs($i['fee'] - $c['fee']) > 0.004;
                            @endphp
                            <tr>
                                <td class="py-4" data-label="{{ __('Ritprijs') }}">
                                    <span class="whitespace-nowrap">{{ $eur($row['gross']) }}</span>
                                    @if($row['custom'])<span class="kt-badge kt-badge-outline kt-badge-sm ms-1">{{ __('eigen bedrag') }}</span>@endif
                                </td>
                                <td class="text-right py-4" data-label="{{ __('Chauffeur krijgt') }}">
                                    <span class="whitespace-nowrap">{{ $eur($i['driver']) }}</span>
                                    @if($differs)<span class="block text-xs text-muted-foreground">{{ __('kaart:') }} {{ $eur($c['driver']) }}</span>@endif
                                </td>
                                <td class="text-right py-4" data-label="Fee">
                                    <span class="whitespace-nowrap">{{ $eur($i['fee']) }}</span>
                                    @if($note = $feeNote($i))<span class="block text-xs text-muted-foreground">{{ __($note) }} ({{ $i['percent'] }}%)</span>@endif
                                    @if($differs)
                                        <span class="block text-xs text-muted-foreground">{{ __('kaart:') }} {{ $eur($c['fee']) }}@if($note = $feeNote($c)) ({{ __($note) }})@endif</span>
                                    @endif
                                </td>
                                <td class="text-right whitespace-nowrap py-4" data-label="{{ __('Mollie-kosten iDEAL') }}">{{ $eur($i['mollie_cost']) }}</td>
                                <td class="text-right {{ $i['kept'] < 0 ? 'text-destructive' : '' }} py-4" data-label="{{ __('Jij houdt over (iDEAL)') }}">
                                    <span class="whitespace-nowrap">{{ $eur($i['kept']) }}</span>
                                    <span class="block text-xs text-muted-foreground">{{ __(':percent% van de rit', ['percent' => number_format($i['kept_percent'], 1, ',', '.')]) }}</span>
                                </td>
                                <td class="text-right whitespace-nowrap py-4" data-label="{{ __('Mollie-kosten kaart') }}">{{ $eur($c['mollie_cost']) }}</td>
                                <td class="text-right {{ $c['kept'] < 0 ? 'text-destructive' : '' }} py-4" data-label="{{ __('Jij houdt over (kaart)') }}">
                                    <span class="whitespace-nowrap">{{ $eur($c['kept']) }}</span>
                                    <span class="block text-xs text-muted-foreground">{{ __(':percent% van de rit', ['percent' => number_format($c['kept_percent'], 1, ',', '.')]) }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-muted-foreground mt-4 mb-0">
                {{ __('Bedragen excl. btw. De uitbetalingskosten zijn een aanname (Mollie publiceert geen tarief voor Connect-overboekingen).') }}
                {{ __('Refunds, chargebacks en eventuele Connect-kosten zitten er niet in.') }}
            </p>
        </div>
    </div>
</div>
@endsection
