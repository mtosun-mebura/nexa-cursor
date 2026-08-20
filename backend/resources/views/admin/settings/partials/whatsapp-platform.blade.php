{{-- Platform WhatsApp Business API (Configuraties → Algemene configuraties) --}}
<div class="kt-card mb-8 settings-collapsible-card settings-collapsible-card--collapsed" id="whatsapp">
    @include('admin.settings.partials.collapsible-header', ['titleHtml' => '<i class="ki-filled ki-whatsapp me-2"></i> WhatsApp Business API (platform)'])
    <div class="settings-collapsible-body">
        <div class="kt-card-table pb-3 min-w-0 overflow-hidden">
            @php
                $waStatus = is_array($whatsappConnectionStatus ?? null) ? $whatsappConnectionStatus : null;
            @endphp
            <div class="mx-5 mt-4 mb-4 space-y-3">
                @if($waStatus !== null && !empty($waStatus['ok']))
                    <div class="rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-300 break-words" id="whatsapp-connection-status">
                        Verbinding OK
                        @if(!empty($waStatus['meta']['verified_name']) || !empty($waStatus['meta']['display_phone_number']))
                            —
                            {{ $waStatus['meta']['verified_name'] ?? '' }}
                            {{ !empty($waStatus['meta']['display_phone_number']) ? '('.$waStatus['meta']['display_phone_number'].')' : '' }}
                        @endif
                    </div>
                @elseif($waStatus !== null)
                    <div class="rounded-lg border border-destructive/40 bg-destructive/10 px-4 py-3 text-sm text-destructive break-words" id="whatsapp-connection-status">
                        WhatsApp API-verbinding mislukt: {{ $waStatus['error'] ?? 'Onbekende fout' }}
                        <div class="mt-1 text-xs opacity-90">Vernieuw de token (permanente System User token) en sla opnieuw op.</div>
                    </div>
                @else
                    <div class="rounded-lg border border-border bg-muted/20 px-4 py-3 text-sm text-muted-foreground break-words" id="whatsapp-connection-status">
                        Nog niet getest. Klik op <strong>Verbinding testen</strong> om token + Phone Number ID bij Meta te controleren.
                    </div>
                @endif
                <form method="POST"
                      action="{{ route('admin.settings.whatsapp.platform.test') }}"
                      class="m-0"
                      id="whatsapp-platform-test-form">
                    @csrf
                    <button type="submit" class="kt-btn kt-btn-outline inline-flex items-center gap-2" id="whatsapp-platform-test-btn">
                        <i class="ki-filled ki-check-circle" aria-hidden="true"></i>
                        <span>Verbinding testen</span>
                    </button>
                </form>
            </div>
            <style>
                #whatsapp-platform-test-btn .whatsapp-test-spinner {
                    width: 1rem;
                    height: 1rem;
                    flex-shrink: 0;
                    animation: whatsapp-test-spin 0.75s linear infinite;
                }
                @keyframes whatsapp-test-spin {
                    to { transform: rotate(360deg); }
                }
            </style>

            <form method="POST" action="{{ route('admin.settings.whatsapp.platform.update') }}" data-validate="true">
                @csrf
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table">
                    <colgroup>
                        <col class="admin-form-label-col">
                        <col>
                    </colgroup>
                    <tbody>
                    <tr>
                        <td colspan="2" class="px-4 sm:px-6 pt-2 pb-2 align-top min-w-0">
                            <p class="text-xs text-muted-foreground mb-0 max-w-3xl break-words whitespace-normal">
                                Eén WhatsApp Business-account voor de hele SaaS. Boekings- en ritmeldingen van alle tenants worden hierover verstuurd.
                            </p>
                            <p class="text-xs text-muted-foreground mb-0 mt-1 max-w-3xl break-words whitespace-normal">
                                In de <strong>berichttekst</strong> staat de tenantnaam (bijv. Taxi Royaal). De WhatsApp-profielnaam in de chat is die van het Meta-telefoonnummer — die kan Meta niet per tenant wijzigen.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal align-top">WhatsApp Business API Token</td>
                        <td class="min-w-0 w-full align-top">
                            <input type="text"
                                   class="kt-input w-full max-w-xl @error('WHATSAPP_API_TOKEN') border-destructive @enderror"
                                   id="WHATSAPP_API_TOKEN"
                                   name="WHATSAPP_API_TOKEN"
                                   value="{{ old('WHATSAPP_API_TOKEN', $whatsappPlatformSettings['WHATSAPP_API_TOKEN'] ?? '') }}"
                                   placeholder="EAAxxxxxxxxxxxx"
                                   autocomplete="off">
                            <p class="text-xs text-muted-foreground mt-1 max-w-xl break-words whitespace-normal">
                                Permanente <strong>System User token</strong> (Meta Business Suite). Leeg laten bij opslaan behoudt de bestaande token.
                            </p>
                            @error('WHATSAPP_API_TOKEN')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal align-top">Phone Number ID</td>
                        <td class="min-w-0 w-full align-top">
                            <input type="text"
                                   class="kt-input w-full max-w-xl @error('WHATSAPP_PHONE_NUMBER_ID') border-destructive @enderror"
                                   id="WHATSAPP_PHONE_NUMBER_ID"
                                   name="WHATSAPP_PHONE_NUMBER_ID"
                                   value="{{ old('WHATSAPP_PHONE_NUMBER_ID', $whatsappPlatformSettings['WHATSAPP_PHONE_NUMBER_ID'] ?? '') }}"
                                   placeholder="123456789012345"
                                   data-validate-as="text"
                                   autocomplete="off"
                                   inputmode="numeric">
                            <p class="text-xs text-muted-foreground mt-1 max-w-xl break-words whitespace-normal">Meta Phone Number ID (cijfer-ID), geen telefoonnummer.</p>
                            @error('WHATSAPP_PHONE_NUMBER_ID')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal align-top">WhatsApp Business Account ID</td>
                        <td class="min-w-0 w-full align-top">
                            <input type="text"
                                   class="kt-input w-full max-w-xl @error('WHATSAPP_BUSINESS_ACCOUNT_ID') border-destructive @enderror"
                                   id="WHATSAPP_BUSINESS_ACCOUNT_ID"
                                   name="WHATSAPP_BUSINESS_ACCOUNT_ID"
                                   value="{{ old('WHATSAPP_BUSINESS_ACCOUNT_ID', $whatsappPlatformSettings['WHATSAPP_BUSINESS_ACCOUNT_ID'] ?? '') }}"
                                   placeholder="123456789012345"
                                   autocomplete="off">
                            @error('WHATSAPP_BUSINESS_ACCOUNT_ID')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal align-top">API-versie</td>
                        <td class="min-w-0 w-full align-top">
                            <input type="text"
                                   class="kt-input w-full max-w-xl @error('WHATSAPP_API_VERSION') border-destructive @enderror"
                                   id="WHATSAPP_API_VERSION"
                                   name="WHATSAPP_API_VERSION"
                                   value="{{ old('WHATSAPP_API_VERSION', $whatsappPlatformSettings['WHATSAPP_API_VERSION'] ?? 'v18.0') }}"
                                   placeholder="v18.0">
                            @error('WHATSAPP_API_VERSION')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal align-top">Webhook Verify Token</td>
                        <td class="min-w-0 w-full align-top">
                            <input type="text"
                                   class="kt-input w-full max-w-xl @error('WHATSAPP_WEBHOOK_VERIFY_TOKEN') border-destructive @enderror"
                                   id="WHATSAPP_WEBHOOK_VERIFY_TOKEN"
                                   name="WHATSAPP_WEBHOOK_VERIFY_TOKEN"
                                   value="{{ old('WHATSAPP_WEBHOOK_VERIFY_TOKEN', $whatsappPlatformSettings['WHATSAPP_WEBHOOK_VERIFY_TOKEN'] ?? '') }}"
                                   placeholder="your-verify-token"
                                   autocomplete="off">
                            <p class="text-xs text-muted-foreground mt-1 max-w-xl break-words whitespace-normal">
                                Zelfde waarde als in Meta → Configure Webhooks → Verify token.
                            </p>
                            <p class="text-xs text-muted-foreground mt-1 max-w-xl break-all">
                                Callback URL: <code class="text-xs">https://nexasuite.nl/api/whatsapp/webhook</code>
                            </p>
                            @error('WHATSAPP_WEBHOOK_VERIFY_TOKEN')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal align-top">Standaardbericht</td>
                        <td class="min-w-0 w-full align-top">
                            <textarea rows="4"
                                      class="kt-input w-full max-w-xl pt-1 @error('WHATSAPP_DEFAULT_MESSAGE') border-destructive @enderror"
                                      id="WHATSAPP_DEFAULT_MESSAGE"
                                      name="WHATSAPP_DEFAULT_MESSAGE"
                                      placeholder="Hallo, bedankt voor uw interesse...">{{ old('WHATSAPP_DEFAULT_MESSAGE', $whatsappPlatformSettings['WHATSAPP_DEFAULT_MESSAGE'] ?? '') }}</textarea>
                            @error('WHATSAPP_DEFAULT_MESSAGE')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                        </td>
                    </tr>
                    </tbody>
                </table>

                @php
                    $selectedDetailFields = old(
                        'WHATSAPP_BOOKING_DETAIL_FIELDS',
                        $whatsappPlatformSettings['WHATSAPP_BOOKING_DETAIL_FIELDS'] ?? []
                    );
                    if (! is_array($selectedDetailFields)) {
                        $selectedDetailFields = [];
                    }
                    $detailFieldOptions = $whatsappBookingDetailFieldOptions ?? [];
                    $whatsappSampleValuesAttr = json_encode([
                        'reference' => 'rit #1042',
                        'customer_name' => 'Jan de Vries',
                        'customer_phone' => '+31 6 12345678',
                        'customer_email' => 'jan@example.nl',
                        'pickup_address' => 'Stationsplein 1, Amsterdam',
                        'dropoff_address' => 'Schiphol Airport',
                        'pickup_at' => '16-08-2026 14:30',
                        'passengers' => '2',
                        'baggage' => 'Koffer x 1',
                        'stopovers' => 'Geen',
                        'return_trip' => 'Nee',
                        'offer' => 'Comfort',
                        'price' => '€ 45,00',
                        'remarks' => 'Bordje bij aankomst',
                    ], JSON_UNESCAPED_UNICODE);
                    $whatsappMetaBodyAttr = json_encode($whatsappBookingMetaBodies['customer'] ?? '', JSON_UNESCAPED_UNICODE);
                    $selectedStatusEvents = old(
                        'WHATSAPP_RIDE_STATUS_EVENTS',
                        $whatsappPlatformSettings['WHATSAPP_RIDE_STATUS_EVENTS'] ?? []
                    );
                    if (! is_array($selectedStatusEvents)) {
                        $selectedStatusEvents = [];
                    }
                    $statusEventOptions = $whatsappRideStatusEventOptions ?? [];
                @endphp

                <div class="mx-5 mt-4 mb-2 border border-border rounded-lg overflow-hidden whatsapp-template-sections">
                    <div class="settings-collapsible-section settings-collapsible-card--collapsed" id="whatsapp-booking-templates">
                        @include('admin.settings.partials.collapsible-header', ['titleHtml' => '<i class="ki-filled ki-message-text-2 me-2"></i> Boekingssjablonen'])
                        <div class="settings-collapsible-body">
                            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table mb-0">
                                <colgroup>
                                    <col class="admin-form-label-col">
                                    <col>
                                </colgroup>
                                <tbody>
                                <tr>
                                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Boekingstemplate (dispatch)</td>
                                    <td class="min-w-0 w-full align-top">
                                        <div class="flex flex-col sm:flex-row flex-wrap gap-2 max-w-xl">
                                            <input type="text"
                                                   class="kt-input w-full min-w-0 flex-1 @error('WHATSAPP_BOOKING_TEMPLATE') border-destructive @enderror"
                                                   id="WHATSAPP_BOOKING_TEMPLATE"
                                                   name="WHATSAPP_BOOKING_TEMPLATE"
                                                   value="{{ old('WHATSAPP_BOOKING_TEMPLATE', $whatsappPlatformSettings['WHATSAPP_BOOKING_TEMPLATE'] ?? '') }}"
                                                   placeholder="nieuwe_boeking_dispatch"
                                                   autocomplete="off">
                                            <input type="text"
                                                   class="kt-input w-full sm:w-24 shrink-0 @error('WHATSAPP_BOOKING_TEMPLATE_LANG') border-destructive @enderror"
                                                   id="WHATSAPP_BOOKING_TEMPLATE_LANG"
                                                   name="WHATSAPP_BOOKING_TEMPLATE_LANG"
                                                   value="{{ old('WHATSAPP_BOOKING_TEMPLATE_LANG', $whatsappPlatformSettings['WHATSAPP_BOOKING_TEMPLATE_LANG'] ?? 'nl') }}"
                                                   placeholder="nl"
                                                   autocomplete="off"
                                                   aria-label="Taalcode dispatch-template">
                                        </div>
                                        <p class="text-xs text-muted-foreground mt-1 max-w-xl break-words whitespace-normal">
                                            Goedgekeurde Meta-template (utility) om het bedrijf te informeren bij nieuwe boekingen.
                                            Het ontvangernummer stel je per tenant in onder Instellingen → WhatsApp (tenant).
                                        </p>
                                        <p class="text-xs text-muted-foreground mt-1 max-w-xl break-words whitespace-normal">
                                            Variabelen: <code>@{{1}}</code> bedrijf, <code>@{{2}}</code> klantnaam, <code>@{{3}}</code> boekingsgegevens, <code>@{{4}}</code> afzender.
                                        </p>
                                        @error('WHATSAPP_BOOKING_TEMPLATE')
                                            <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                        @enderror
                                    </td>
                                </tr>
                                <tr>
                                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Berichten naar bedrijf</td>
                                    <td class="min-w-0 w-full align-top">
                                        <div class="flex flex-wrap items-center gap-3 max-w-xl">
                                            <input type="hidden" name="WHATSAPP_COMPANY_BOOKING_NOTIFY_ENABLED" value="0">
                                            <input type="checkbox"
                                                   class="kt-switch kt-switch-sm shrink-0"
                                                   id="WHATSAPP_COMPANY_BOOKING_NOTIFY_ENABLED"
                                                   name="WHATSAPP_COMPANY_BOOKING_NOTIFY_ENABLED"
                                                   value="1"
                                                   {{ old('WHATSAPP_COMPANY_BOOKING_NOTIFY_ENABLED', $whatsappPlatformSettings['WHATSAPP_COMPANY_BOOKING_NOTIFY_ENABLED'] ?? '0') === '1' ? 'checked' : '' }}>
                                            <span class="text-sm text-secondary-foreground">Stuur bij elke boeking een WhatsApp naar het bedrijf</span>
                                        </div>
                                        <p class="text-xs text-muted-foreground mt-1 max-w-xl break-words whitespace-normal">
                                            Alleen als het tenant-WhatsApp-nummer voor bedrijfsboekingen is ingevuld.
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Boekingstemplate (klant)</td>
                                    <td class="min-w-0 w-full align-top">
                                        <div class="flex flex-col sm:flex-row flex-wrap gap-2 max-w-xl">
                                            <input type="text"
                                                   class="kt-input w-full min-w-0 flex-1 @error('WHATSAPP_BOOKING_CUSTOMER_TEMPLATE') border-destructive @enderror"
                                                   id="WHATSAPP_BOOKING_CUSTOMER_TEMPLATE"
                                                   name="WHATSAPP_BOOKING_CUSTOMER_TEMPLATE"
                                                   value="{{ old('WHATSAPP_BOOKING_CUSTOMER_TEMPLATE', $whatsappPlatformSettings['WHATSAPP_BOOKING_CUSTOMER_TEMPLATE'] ?? '') }}"
                                                   placeholder="nieuwe_boeking"
                                                   autocomplete="off">
                                            <input type="text"
                                                   class="kt-input w-full sm:w-24 shrink-0 @error('WHATSAPP_BOOKING_CUSTOMER_TEMPLATE_LANG') border-destructive @enderror"
                                                   id="WHATSAPP_BOOKING_CUSTOMER_TEMPLATE_LANG"
                                                   name="WHATSAPP_BOOKING_CUSTOMER_TEMPLATE_LANG"
                                                   value="{{ old('WHATSAPP_BOOKING_CUSTOMER_TEMPLATE_LANG', $whatsappPlatformSettings['WHATSAPP_BOOKING_CUSTOMER_TEMPLATE_LANG'] ?? 'nl') }}"
                                                   placeholder="nl"
                                                   autocomplete="off"
                                                   aria-label="Taalcode klant-template">
                                        </div>
                                        <p class="text-xs text-muted-foreground mt-1 max-w-xl break-words whitespace-normal">
                                            Template voor klantbevestiging. Leeg = zelfde als dispatch-template.
                                        </p>
                                        <p class="text-xs text-muted-foreground mt-1 max-w-xl break-words whitespace-normal">
                                            Variabelen: <code>@{{1}}</code> klantnaam, <code>@{{2}}</code> bedrijf,
                                            <code>@{{3}}</code>–<code>@{{10}}</code> boekingsvelden (vaste labels in Meta-tekst),
                                            <code>@{{11}}</code> afzender. Meta staat geen regeleinden in één variabele toe —
                                            gebruik de aanbevolen Meta-sjabloontekst hieronder (nieuwe/bijgewerkte template vereist).
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Meta-sjabloontekst (klant)</td>
                                    <td class="min-w-0 w-full align-top">
                                        <p class="text-xs text-muted-foreground mb-2 max-w-2xl break-words whitespace-normal">
                                            Kopieer deze body 1-op-1 naar Meta (Hulpmiddel). De vaste zinnen staan in Meta; in Nexa vul je alleen de variabelen.
                                        </p>
                                        <pre class="kt-input w-full max-w-2xl text-xs whitespace-pre-wrap break-words font-mono py-3 h-auto min-h-[8rem]">{{ $whatsappBookingMetaBodies['customer'] ?? '' }}</pre>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Meta-sjabloontekst (dispatch)</td>
                                    <td class="min-w-0 w-full align-top">
                                        <pre class="kt-input w-full max-w-2xl text-xs whitespace-pre-wrap break-words font-mono py-3 h-auto min-h-[8rem]">{{ $whatsappBookingMetaBodies['dispatch'] ?? '' }}</pre>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Boekingsvelden in @{{3}}</td>
                                    <td class="min-w-0 w-full align-top">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-w-2xl" id="whatsapp-booking-detail-fields">
                                            @foreach($detailFieldOptions as $fieldKey => $fieldLabel)
                                                <label class="inline-flex items-start gap-2 text-sm text-foreground cursor-pointer">
                                                    <input type="checkbox"
                                                           class="kt-checkbox mt-0.5"
                                                           name="WHATSAPP_BOOKING_DETAIL_FIELDS[]"
                                                           value="{{ $fieldKey }}"
                                                           data-whatsapp-detail-field="{{ $fieldKey }}"
                                                           data-whatsapp-detail-label="{{ $fieldLabel }}"
                                                           @checked(in_array($fieldKey, $selectedDetailFields, true))>
                                                    <span>{{ $fieldLabel }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                        <p class="text-xs text-muted-foreground mt-2 max-w-2xl break-words whitespace-normal">
                                            Bepaalt welke regels in <code>@{{3}}</code> komen (klant én dispatch). Volgorde = volgorde hierboven.
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Voorbeeldbericht</td>
                                    <td class="min-w-0 w-full align-top">
                                        <pre id="whatsapp-booking-sample-preview"
                                             class="kt-input w-full max-w-2xl text-xs whitespace-pre-wrap break-words py-3 h-auto min-h-[10rem]"
                                             data-meta-body="{{ $whatsappMetaBodyAttr }}"
                                             data-sample-values="{{ $whatsappSampleValuesAttr }}">{{ $whatsappBookingSamplePreview['preview'] ?? '' }}</pre>
                                        <p class="text-xs text-muted-foreground mt-1 max-w-2xl">Live voorbeeld o.b.v. de aangevinkte velden (sample-data).</p>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="settings-collapsible-section settings-collapsible-card--collapsed" id="whatsapp-status-templates">
                        @include('admin.settings.partials.collapsible-header', ['titleHtml' => '<i class="ki-filled ki-notification-status me-2"></i> Statussjablonen (universeel)'])
                        <div class="settings-collapsible-body">
                            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table mb-0">
                                <colgroup>
                                    <col class="admin-form-label-col">
                                    <col>
                                </colgroup>
                                <tbody>
                                <tr>
                                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Status-template</td>
                                    <td class="min-w-0 w-full align-top">
                                        <div class="flex flex-col sm:flex-row flex-wrap gap-2 max-w-xl">
                                            <input type="text"
                                                   class="kt-input w-full min-w-0 flex-1"
                                                   id="WHATSAPP_RIDE_STATUS_TEMPLATE"
                                                   name="WHATSAPP_RIDE_STATUS_TEMPLATE"
                                                   value="{{ old('WHATSAPP_RIDE_STATUS_TEMPLATE', $whatsappPlatformSettings['WHATSAPP_RIDE_STATUS_TEMPLATE'] ?? '') }}"
                                                   placeholder="rit_status_update"
                                                   autocomplete="off">
                                            <input type="text"
                                                   class="kt-input w-full sm:w-24 shrink-0"
                                                   id="WHATSAPP_RIDE_STATUS_TEMPLATE_LANG"
                                                   name="WHATSAPP_RIDE_STATUS_TEMPLATE_LANG"
                                                   value="{{ old('WHATSAPP_RIDE_STATUS_TEMPLATE_LANG', $whatsappPlatformSettings['WHATSAPP_RIDE_STATUS_TEMPLATE_LANG'] ?? 'nl') }}"
                                                   placeholder="nl"
                                                   autocomplete="off"
                                                   aria-label="Taalcode status-template">
                                        </div>
                                        <p class="text-xs text-muted-foreground mt-1 max-w-2xl break-words whitespace-normal">
                                            Eén Meta-template voor acceptatie, afwijzing (met optionele opmerking), start, afronding, annulering en herdispatch.
                                            Variabelen: <code>@{{1}}</code> klant, <code>@{{2}}</code> bedrijf, <code>@{{3}}</code> statuslabel, <code>@{{4}}</code> opmerking, <code>@{{5}}</code> chauffeur, <code>@{{6}}</code> ophaalmoment, <code>@{{7}}</code> ophaaladres.
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Meta-sjabloontekst (status)</td>
                                    <td class="min-w-0 w-full align-top">
                                        <p class="text-xs text-muted-foreground mb-2 max-w-2xl break-words whitespace-normal">
                                            Kopieer naar Meta als Hulpmiddel-sjabloon. <code>@{{3}}</code> (status) en <code>@{{4}}</code> (opmerking) wisselen per gebeurtenis; chauffeur en ophaalgegevens vullen automatisch.
                                        </p>
                                        <pre class="kt-input w-full max-w-2xl text-xs whitespace-pre-wrap break-words font-mono py-3 h-auto min-h-[8rem]">{{ $whatsappBookingMetaBodies['status'] ?? '' }}</pre>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Statusberichten versturen bij</td>
                                    <td class="min-w-0 w-full align-top">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-w-2xl">
                                            @foreach($statusEventOptions as $eventKey => $eventLabel)
                                                <label class="inline-flex items-start gap-2 text-sm text-foreground cursor-pointer">
                                                    <input type="checkbox"
                                                           class="kt-checkbox mt-0.5"
                                                           name="WHATSAPP_RIDE_STATUS_EVENTS[]"
                                                           value="{{ $eventKey }}"
                                                           @checked(in_array($eventKey, $selectedStatusEvents, true))>
                                                    <span>{{ $eventLabel }} <span class="text-muted-foreground">({{ $eventKey }})</span></span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Voorbeeld statusbericht</td>
                                    <td class="min-w-0 w-full align-top">
                                        <pre class="kt-input w-full max-w-2xl text-xs whitespace-pre-wrap break-words py-3 h-auto min-h-[10rem]">{{ $whatsappStatusSamplePreview['preview'] ?? '' }}</pre>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="settings-collapsible-section settings-collapsible-card--collapsed" id="whatsapp-pickup-proposal-template">
                        @include('admin.settings.partials.collapsible-header', ['titleHtml' => '<i class="ki-filled ki-time me-2"></i> Ophaalvoorstel (verlopen rit)'])
                        <div class="settings-collapsible-body">
                            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table mb-0">
                                <colgroup>
                                    <col class="admin-form-label-col">
                                    <col>
                                </colgroup>
                                <tbody>
                                <tr>
                                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Template-naam</td>
                                    <td class="min-w-0 w-full align-top">
                                        <div class="flex flex-col sm:flex-row flex-wrap gap-2 max-w-xl">
                                            <input type="text"
                                                   class="kt-input w-full min-w-0 flex-1"
                                                   id="WHATSAPP_PICKUP_PROPOSAL_TEMPLATE"
                                                   name="WHATSAPP_PICKUP_PROPOSAL_TEMPLATE"
                                                   value="{{ old('WHATSAPP_PICKUP_PROPOSAL_TEMPLATE', $whatsappPlatformSettings['WHATSAPP_PICKUP_PROPOSAL_TEMPLATE'] ?? 'rit_ophaal_voorstel') }}"
                                                   placeholder="rit_ophaal_voorstel"
                                                   autocomplete="off">
                                            <input type="text"
                                                   class="kt-input w-full sm:w-24 shrink-0"
                                                   id="WHATSAPP_PICKUP_PROPOSAL_TEMPLATE_LANG"
                                                   name="WHATSAPP_PICKUP_PROPOSAL_TEMPLATE_LANG"
                                                   value="{{ old('WHATSAPP_PICKUP_PROPOSAL_TEMPLATE_LANG', $whatsappPlatformSettings['WHATSAPP_PICKUP_PROPOSAL_TEMPLATE_LANG'] ?? 'nl') }}"
                                                   placeholder="nl"
                                                   autocomplete="off"
                                                   aria-label="Taalcode ophaalvoorstel-template">
                                        </div>
                                        <p class="text-xs text-muted-foreground mt-1 max-w-2xl break-words whitespace-normal">
                                            Meta-template met <strong>twee Quick Reply-knoppen</strong>:
                                            <code>Accepteren</code> (payload <code>pickup_accept</code>) en
                                            <code>Weigeren</code> (payload <code>pickup_decline</code>).
                                            Variabelen (elk maximaal 1×, in leesvolgorde):
                                            <code>@{{1}}</code> klant,
                                            <code>@{{2}}</code> bedrijf,
                                            <code>@{{3}}</code> telefoon tenant,
                                            <code>@{{4}}</code> huidig ophaalmoment,
                                            <code>@{{5}}</code> voorstel,
                                            <code>@{{6}}</code> ophaaladres,
                                            <code>@{{7}}</code> afleveradres,
                                            <code>@{{8}}</code> chauffeur.
                                            Bronnummer: bedrijfsveld <code>phone</code> van de tenant (internationaal +31…).
                                            Na Weigeren mag de klant een los tekstbericht sturen als opmerking.
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Meta-sjabloontekst</td>
                                    <td class="min-w-0 w-full align-top">
                                        <p class="text-xs text-muted-foreground mb-2 max-w-2xl break-words whitespace-normal">
                                            Kopieer naar Meta als Utility-sjabloon en voeg de twee knoppen toe.
                                            Elke parameter <code>@{{1}}</code>–<code>@{{8}}</code> mag maar 1× in de body staan.
                                            Zet het telefoonnummer (<code>@{{3}}</code>) op een eigen regel zodat het in WhatsApp klikbaar is.
                                            Geen Call-knop erbij: Quick Reply en Call-to-action mogen niet gemengd in één sjabloon.
                                        </p>
                                        <pre class="kt-input w-full max-w-2xl text-xs whitespace-pre-wrap break-words font-mono py-3 h-auto min-h-[8rem]">{{ $whatsappBookingMetaBodies['pickup_proposal'] ?? '' }}</pre>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Voorbeeld</td>
                                    <td class="min-w-0 w-full align-top">
                                        <pre class="kt-input w-full max-w-2xl text-xs whitespace-pre-wrap break-words py-3 h-auto min-h-[10rem]">{{ $whatsappPickupProposalSamplePreview['preview'] ?? '' }}</pre>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="kt-card-footer flex flex-wrap justify-end items-center gap-3 pt-5 border-t border-border px-5">
                    <button type="submit" class="kt-btn kt-btn-primary">
                        <i class="ki-filled ki-check me-2"></i> WhatsApp Business opslaan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<style>
    #whatsapp .whatsapp-template-sections .settings-collapsible-header {
        padding: 0.75rem 1rem;
        background: color-mix(in srgb, var(--muted, #f1f5f9) 55%, transparent);
    }
    #whatsapp .whatsapp-template-sections .settings-collapsible-header .kt-card-title {
        font-size: 0.875rem;
        font-weight: 600;
    }
    #whatsapp .whatsapp-template-sections .settings-collapsible-body {
        border-top: 1px solid var(--border);
    }
</style>
<script>
(function () {
    var form = document.getElementById('whatsapp-platform-test-form');
    var btn = document.getElementById('whatsapp-platform-test-btn');
    if (form && btn && form.dataset.loaderBound !== '1') {
        form.dataset.loaderBound = '1';
        form.addEventListener('submit', function () {
            if (btn.disabled) {
                return;
            }
            btn.disabled = true;
            btn.setAttribute('aria-busy', 'true');
            btn.innerHTML =
                '<svg class="whatsapp-test-spinner" viewBox="0 0 24 24" fill="none" aria-hidden="true">' +
                    '<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity="0.25"></circle>' +
                    '<path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>' +
                '</svg>' +
                '<span>Bezig met testen…</span>';
        });
    }

    var previewEl = document.getElementById('whatsapp-booking-sample-preview');
    var fieldsRoot = document.getElementById('whatsapp-booking-detail-fields');
    if (!previewEl || !fieldsRoot || previewEl.dataset.previewBound === '1') {
        return;
    }
    previewEl.dataset.previewBound = '1';

    var metaBody = '';
    var sampleValues = {};
    try {
        metaBody = JSON.parse(previewEl.getAttribute('data-meta-body') || '""');
    } catch (e) {
        metaBody = '';
    }
    try {
        sampleValues = JSON.parse(previewEl.getAttribute('data-sample-values') || '{}');
    } catch (e) {
        sampleValues = {};
    }

    function refreshPreview() {
        var lines = [];
        fieldsRoot.querySelectorAll('input[type="checkbox"][data-whatsapp-detail-field]').forEach(function (cb) {
            if (!cb.checked) {
                return;
            }
            var key = cb.getAttribute('data-whatsapp-detail-field') || '';
            var label = cb.getAttribute('data-whatsapp-detail-label') || key;
            lines.push(label + ': ' + (sampleValues[key] || '—'));
        });
        var details = lines.join('\n');
        var params = ['Jan de Vries', 'Taxi Voorbeeld', details, 'Taxi Voorbeeld'];
        var out = String(metaBody || '');
        params.forEach(function (value, index) {
            var token = '{' + '{' + (index + 1) + '}' + '}';
            out = out.split(token).join(String(value));
        });
        previewEl.textContent = out;
    }

    fieldsRoot.addEventListener('change', refreshPreview);
})();
</script>
