@php
    $sectionConfigRaw = (isset($sectionKey) && isset($homeSections) ? ($homeSections[$sectionKey] ?? []) : []);
    $bookingConfig = app(\App\Services\NexaTaxiBookingPricingService::class)->mergeSectionConfig(is_array($sectionConfigRaw) ? $sectionConfigRaw : []);
    $bookingPageId = isset($page) ? ($page->id ?? null) : null;
    $mapsApiKey = trim((string) ($googleMapsApiKey ?? ''));
    $sectionStyle = $bookingConfig['style'] ?? [];
    $bookingDefaultAccent = \App\Services\NexaTaxiBookingPricingService::DEFAULT_BRAND_ACCENT_HEX;
    $stepLabels = $bookingConfig['step_labels'] ?? [];
    $texts = $bookingConfig['texts'] ?? [];
    $logic = $bookingConfig['logic'] ?? [];
    $stepOrder = $bookingConfig['step_order'] ?? ['trip', 'baggage', 'offers', 'contact', 'confirm'];
    if (!is_array($stepOrder) || count($stepOrder) !== 5) {
        $stepOrder = ['trip', 'baggage', 'offers', 'contact', 'confirm'];
    }
    $stepLabelByLogical = [
        'trip' => $stepLabels['step3'] ?? 'Reisgegevens',
        'baggage' => $stepLabels['step1'] ?? 'Bagage',
        'offers' => $stepLabels['step2'] ?? 'Aanbiedingen',
        'contact' => $stepLabels['step4'] ?? 'Contactgegevens',
        'confirm' => $stepLabels['step5'] ?? 'Bevestiging',
    ];
    $moduleAlign = $sectionStyle['align'] ?? 'center';
    $moduleAlignClass = $moduleAlign === 'left' ? 'justify-start' : ($moduleAlign === 'right' ? 'justify-end' : 'justify-center');
    $moduleOuterStyleParts = ['width: 100%'];
    if (! empty($sectionStyle['container_max_width'])) {
        $moduleOuterStyleParts[] = '--booking-module-max-width: '.$sectionStyle['container_max_width'];
    }
    if (! empty($sectionStyle['container_min_height'])) {
        $moduleOuterStyleParts[] = 'min-height: '.$sectionStyle['container_min_height'];
    }
    $moduleOuterStyle = $moduleOuterStyleParts !== [] ? implode('; ', $moduleOuterStyleParts).';' : '';
    $shellStyleParts = [
        'border-color: rgba(148, 163, 184, 0.45);',
        'border-radius: ' . (int) ($sectionStyle['border_radius'] ?? 12) . 'px;',
    ];
    $moduleShellStyle = implode(' ', $shellStyleParts);
    $bookingTenantCompanyId = null;
    if (app()->bound('resolved_tenant_id')) {
        $rtid = app('resolved_tenant_id');
        if (is_numeric($rtid) && (int) $rtid > 0) {
            $bookingTenantCompanyId = (int) $rtid;
        }
    }
    $dispatchSettings = app(\App\Modules\NexaTaxi\Services\TaxiDispatchSettingsService::class);
    $bookingNotifications = app(\App\Modules\NexaTaxi\Services\TaxiBookingNotificationService::class);
    $whatsappClickToChatNumber = $dispatchSettings->bookingWhatsappNumber($bookingTenantCompanyId);
    $whatsappServerAutoSend = $bookingNotifications->whatsappAutoSendEnabled($bookingTenantCompanyId);
    $whatsappClientClickToChat = $bookingNotifications->whatsappClientClickToChatEnabled($bookingTenantCompanyId);
    $bookingConfig['address_search_url'] = url()->route('nexataxi.booking.address-search');
    $bookingConfig['payment'] = $dispatchSettings->paymentOptionsForTenant($bookingTenantCompanyId);
    $tabFontPxVal = (int) ($sectionStyle['tab_font_size_px'] ?? 14);
    $titleFontPxVal = (int) ($sectionStyle['title_font_size_px'] ?? 36);
    $titleFontPxVal = max(16, min(72, $titleFontPxVal));
    $stepHeadingFontPxVal = (int) ($sectionStyle['step_heading_font_size_px'] ?? 30);
    $stepHeadingFontPxVal = max(16, min(48, $stepHeadingFontPxVal));
    $stepHeadingStyle = 'color: '.e($sectionStyle['primary_color'] ?? $bookingDefaultAccent).';';
    $routeMapZoomVal = max(1, min(21, (int) ($sectionStyle['route_map_zoom'] ?? 14)));
    $routeMapImgScale = 0.68 + ($routeMapZoomVal - 1) * (0.64 / 20);
    $routeMapImgScale = round(max(0.65, min(1.35, $routeMapImgScale)), 4);
@endphp

<section class="booking-module-scroll-reveal w-full py-6 md:py-12" data-nexataxi-booking-module data-booking-module-scroll-reveal style="--booking-tab-font-size: {{ $tabFontPxVal }}px; --booking-route-map-img-scale: {{ $routeMapImgScale }}; --booking-title-size-max: {{ $titleFontPxVal }}px; --booking-step-heading-size-max: {{ $stepHeadingFontPxVal }}px;">
    <div class="booking-module-layout website-section-inner website-section-inner--flush w-full max-w-full">
    <div class="flex {{ $moduleAlignClass }} w-full">
    <div class="booking-module-outer w-full" @if($moduleOuterStyle !== '') style="{{ $moduleOuterStyle }}" @endif>
    <div class="booking-module-card booking-module-reveal-item rounded-xl border p-0 shadow-sm bg-neutral-primary text-heading"
        style="{{ $moduleShellStyle }}">
        <div class="px-4 py-4 sm:px-6 sm:py-5 border-b bg-neutral-secondary-soft" style="border-color: {{ e($sectionStyle['primary_color'] ?? $bookingDefaultAccent) }}33;">
            <h2 class="booking-module-title font-bold leading-tight" style="color: {{ e($sectionStyle['primary_color'] ?? $bookingDefaultAccent) }};">{{ e($bookingConfig['title'] ?? 'Boek eenvoudig je taxirit') }}</h2>
            @if(!empty($bookingConfig['subtitle']))
            <p class="mt-2 text-body">{{ e($bookingConfig['subtitle']) }}</p>
            @endif
        </div>

        <div class="px-3 pt-2 border-b bg-neutral-primary" style="border-color: {{ e($sectionStyle['primary_color'] ?? $bookingDefaultAccent) }}22; border-bottom: 0 !important;">
            <div class="border-b border-default">
                <div class="sm:hidden">
                    <label for="booking-steps-select" class="sr-only">Selecteer stap</label>
                    <select id="booking-steps-select" data-booking-step-select class="bg-neutral-secondary-soft border-0 border-b border-default text-heading text-sm rounded-t-base focus:ring-brand block w-full p-2.5">
                        @foreach($stepOrder as $stepKey)
                            <option value="{{ $stepKey }}" @if($stepKey === 'baggage' && !empty($logic['skip_baggage_step'])) hidden disabled @endif>{{ e($stepLabelByLogical[$stepKey] ?? 'Stap') }}</option>
                        @endforeach
                    </select>
                </div>
                <ul class="hidden sm:flex flex-wrap -mb-px text-sm font-medium text-center text-body" data-booking-steps-nav role="tablist">
                    @foreach($stepOrder as $idx => $stepKey)
                    <li class="me-2 @if($stepKey === 'baggage' && !empty($logic['skip_baggage_step'])) hidden @endif">
                        <button
                            id="booking-tab-{{ $stepKey }}"
                            data-step-index="{{ $idx + 1 }}"
                            data-step-key="{{ $stepKey }}"
                            data-tabs-target="#booking-panel-{{ $stepKey }}"
                            type="button"
                            tabindex="-1"
                            role="tab"
                            aria-controls="booking-panel-{{ $stepKey }}"
                            aria-selected="{{ $idx === 0 ? 'true' : 'false' }}"
                            class="booking-step-tab inline-flex items-center justify-center p-4 border-b-2 border-transparent rounded-t-base cursor-default"
                            aria-label="{{ e($stepLabelByLogical[$stepKey] ?? ('Stap ' . ($idx + 1))) }}">
                            @if($stepKey === 'trip')
                                <svg class="w-4 h-4 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21s7-4.35 7-10a7 7 0 1 0-14 0c0 5.65 7 10 7 10Z"/>
                                    <circle cx="12" cy="11" r="2.5" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            @elseif($stepKey === 'baggage')
                                <svg class="w-4 h-4 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7V6a3 3 0 0 1 6 0v1m-9 0h12a1 1 0 0 1 1 1v10a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 1-1Z"/>
                                </svg>
                            @elseif($stepKey === 'offers')
                                <svg class="w-4 h-4 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h7l5 5-7 7-5-5V7Z"/>
                                    <circle cx="10" cy="10" r="1.5" fill="currentColor"/>
                                </svg>
                            @elseif($stepKey === 'contact')
                                <svg class="w-4 h-4 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 13a5 5 0 1 0 0-10 5 5 0 0 0 0 10Z"/>
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 21a8 8 0 0 1 16 0"/>
                                </svg>
                            @else
                                <svg class="w-4 h-4 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/>
                                </svg>
                            @endif
                            {{ e($stepLabelByLogical[$stepKey] ?? ('Stap ' . ($idx + 1))) }}
                        </button>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="p-6 bg-neutral-secondary-soft">
            <div class="space-y-8">
                <div class="booking-step-panels-shell w-full" data-booking-step-panels-shell>
                <div class="hidden" id="booking-panel-baggage" role="tabpanel" aria-labelledby="booking-tab-baggage" data-step-panel="baggage">
                    <h3 class="booking-module-step-heading font-semibold mb-4" style="{{ $stepHeadingStyle }}">{{ e($stepLabelByLogical['baggage'] ?? 'Bagage') }}</h3>
                    <div class="booking-baggage-layout">
                        <div class="space-y-4">
                            <p class="text-sm text-body">Kies je bagage en geef per type het aantal door.</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                                @foreach(($bookingConfig['baggage_items'] ?? []) as $row)
                                @php $key = $row['key'] ?? ''; @endphp
                                <div class="booking-baggage-card rounded-xl border p-4 bg-neutral-primary shadow-xs flex flex-col h-full" style="border-color: {{ e($sectionStyle['primary_color'] ?? $bookingDefaultAccent) }}22;">
                                    <div class="space-y-1 flex-1 min-h-0">
                                        <div class="text-base font-semibold text-heading">{{ e($row['title'] ?? '') }}</div>
                                        @if(!empty($row['subtitle']))<div class="text-sm text-body">{{ e($row['subtitle']) }}</div>@endif
                                        @if(!empty($row['price']) && (float)$row['price'] > 0)<div class="text-xs text-body">+ € {{ number_format((float)$row['price'], 2, ',', '.') }}</div>@endif
                                    </div>
                                    <div class="mt-auto pt-4 inline-flex items-center gap-2 px-1.5 py-1 rounded-lg bg-neutral-secondary-medium shadow-xs self-start">
                                        <button type="button" class="booking-qty-btn inline-flex items-center justify-center rounded-md border h-8 w-8 border-default-medium bg-neutral-primary text-heading hover:bg-neutral-secondary-soft transition-colors" data-target="baggage.{{ e($key) }}" data-delta="-1">-</button>
                                        <span class="min-w-5 text-center font-semibold text-base leading-none text-heading" data-qty-display="baggage.{{ e($key) }}">0</span>
                                        <button type="button" class="booking-qty-btn inline-flex items-center justify-center rounded-md border h-8 w-8 border-default-medium bg-neutral-primary text-heading hover:bg-neutral-secondary-soft transition-colors" data-target="baggage.{{ e($key) }}" data-delta="1" data-max="{{ (int)($row['max_qty'] ?? 4) }}">+</button>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="rounded-xl border p-4 bg-neutral-primary shadow-xs booking-baggage-special" style="border-color: {{ e($sectionStyle['primary_color'] ?? $bookingDefaultAccent) }}22;">
                            <label class="inline-flex items-center gap-3">
                                <input type="checkbox" class="h-5 w-5 rounded border border-default-medium bg-neutral-secondary-medium text-fg-brand focus:ring-2 focus:ring-brand-soft" data-toggle-special-baggage>
                                <span class="text-base font-semibold text-heading">Wil je bijzondere bagage meenemen?</span>
                            </label>
                            <p class="text-sm text-body mt-2">Vink aan en selecteer hieronder het aantal per type.</p>
                            <div class="hidden mt-4 grid grid-cols-1 gap-3" data-special-baggage-wrap>
                                @foreach(($bookingConfig['special_items'] ?? []) as $row)
                                @php $key = $row['key'] ?? ''; @endphp
                                <div class="rounded-lg border p-3 flex items-center justify-between bg-neutral-secondary-medium" style="border-color: {{ e($sectionStyle['primary_color'] ?? $bookingDefaultAccent) }}22;">
                                    <div class="pe-3">
                                        <div class="font-semibold text-heading">{{ e($row['title'] ?? '') }}</div>
                                        @if(!empty($row['price']) && (float)$row['price'] > 0)<div class="text-xs text-body">+ € {{ number_format((float)$row['price'], 2, ',', '.') }}</div>@endif
                                    </div>
                                    <div class="inline-flex items-center gap-2 px-1.5 py-1 rounded-lg bg-neutral-primary shadow-xs">
                                        <button type="button" class="booking-qty-btn inline-flex items-center justify-center rounded-md border h-8 w-8 border-default-medium bg-neutral-primary text-heading hover:bg-neutral-secondary-soft transition-colors" data-target="special_baggage.{{ e($key) }}" data-delta="-1">-</button>
                                        <span class="min-w-5 text-center font-semibold text-base leading-none text-heading" data-qty-display="special_baggage.{{ e($key) }}">0</span>
                                        <button type="button" class="booking-qty-btn inline-flex items-center justify-center rounded-md border h-8 w-8 border-default-medium bg-neutral-primary text-heading hover:bg-neutral-secondary-soft transition-colors" data-target="special_baggage.{{ e($key) }}" data-delta="1" data-max="{{ (int)($row['max_qty'] ?? 4) }}">+</button>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="hidden" id="booking-panel-offers" role="tabpanel" aria-labelledby="booking-tab-offers" data-step-panel="offers">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="booking-module-step-heading font-semibold" style="{{ $stepHeadingStyle }}">{{ e($stepLabelByLogical['offers'] ?? 'Aanbiedingen') }}</h3>
                        <div class="text-sm text-slate-600 dark:text-slate-300">Passagiers: <span data-summary-passengers>1</span></div>
                    </div>
                    <div class="space-y-4" data-offers-list></div>
                    <p class="text-sm mt-3 text-slate-600 dark:text-slate-300 hidden" data-offers-empty>Geen aanbiedingen beschikbaar voor de huidige invoer.</p>
                </div>

                <div class="hidden" id="booking-panel-trip" role="tabpanel" aria-labelledby="booking-tab-trip" data-step-panel="trip">
                    <h3 class="booking-module-step-heading font-semibold mb-4" style="{{ $stepHeadingStyle }}">{{ e($stepLabelByLogical['trip'] ?? 'Reisgegevens') }}</h3>
                    <div class="booking-trip-layout">
                        <div class="booking-trip-left space-y-5">
                            <label class="block text-base font-semibold text-heading">Waar wil je heen?</label>
                            <div class="booking-route-wrap">
                                <div class="booking-route-icons text-fg-brand shrink-0">
                                    <div class="booking-route-icons-list" data-route-icons-list></div>
                                </div>
                                <div class="booking-route-fields flex-1">
                                    <div class="relative booking-route-field-row" data-route-row="pickup">
                                        <div class="relative w-full min-w-0" data-route-icon-align-target>
                                            <span class="absolute left-5 top-1/2 -translate-y-1/2 text-fg-brand text-base font-semibold leading-none z-[1] pointer-events-none">van</span>
                                            <input type="text" style="padding-left: 70px;" class="booking-route-input-short bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-lg focus:ring-brand focus:border-brand block w-full pe-3 py-3.5 shadow-xs placeholder:text-body" data-field="pickup_address" name="pickup_address" autocomplete="off" autocorrect="off" autocapitalize="none" spellcheck="false" placeholder="{{ e($texts['pickup_placeholder'] ?? 'straatnaam met huisnummer') }}">
                                        </div>
                                        <p class="hidden mt-1.5 text-sm font-medium text-red-600 dark:text-red-300 w-full" data-booking-field-error="pickup_address" role="alert"></p>
                                    </div>
                                    <div class="booking-route-middle booking-route-field-row flex items-center justify-between" data-route-row="middle">
                                        <span class="inline-flex items-center text-fg-brand text-xs md:text-sm font-medium" data-stopover-text>
                                            tussenstop toevoegen
                                        </span>
                                        <button type="button" class="booking-route-swap-btn inline-flex items-center justify-center w-9 h-9 rounded-full text-fg-brand hover:bg-neutral-secondary-soft transition-colors ms-auto" aria-label="Wissel van en naar om">
                                            <svg class="w-7 h-7" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.25 15 12 18.75 15.75 15m-3.75 3.75V5.25M15.75 9 12 5.25 8.25 9"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <div data-stopovers-list></div>
                                    <div class="relative booking-route-field-row" data-route-row="dropoff">
                                        <div class="relative w-full min-w-0" data-route-icon-align-target>
                                            <span class="absolute left-5 top-1/2 -translate-y-1/2 text-fg-brand text-base font-semibold leading-none z-[1] pointer-events-none">naar</span>
                                            <input type="text" style="padding-left: 70px;" class="booking-route-input-short bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-lg focus:ring-brand focus:border-brand block w-full pe-3 py-3.5 shadow-xs placeholder:text-body" data-field="dropoff_address" name="dropoff_address" autocomplete="off" autocorrect="off" autocapitalize="none" spellcheck="false" placeholder="{{ e($texts['dropoff_placeholder'] ?? 'straatnaam met huisnummer') }}">
                                        </div>
                                        <p class="hidden mt-1.5 text-sm font-medium text-red-600 dark:text-red-300 w-full" data-booking-field-error="dropoff_address" role="alert"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="pt-1">
                                @if(empty($logic['skip_baggage_step']))
                                <label class="block mb-2.5 text-sm font-semibold text-heading">Reis je met bagage?</label>
                                <div class="flex flex-wrap items-center gap-5 text-heading">
                                    <label class="inline-flex items-center gap-2">
                                        <input type="radio" name="booking_has_baggage_ui" value="yes" class="w-4 h-4 border border-default-medium rounded-full bg-neutral-secondary-medium text-fg-brand focus:ring-2 focus:ring-brand-soft" data-has-baggage-choice="yes" checked>
                                        <span>Ja</span>
                                    </label>
                                    <label class="inline-flex items-center gap-2">
                                        <input type="radio" name="booking_has_baggage_ui" value="no" class="w-4 h-4 border border-default-medium rounded-full bg-neutral-secondary-medium text-fg-brand focus:ring-2 focus:ring-brand-soft" data-has-baggage-choice="no">
                                        <span>Nee, ik heb geen bagage</span>
                                    </label>
                                </div>
                                @endif
                                <div class="mt-3 hidden booking-route-details-banner" data-route-details-banner style="--booking-primary: {{ e($sectionStyle['primary_color'] ?? $bookingDefaultAccent) }};">
                                    <div class="rounded-2xl border border-slate-200/90 dark:border-slate-600/40 bg-stone-100/90 dark:bg-slate-950/40 shadow-[0_2px_12px_rgba(15,23,42,0.06)] overflow-hidden w-full booking-trip-route-card">
                                        <div class="px-4 pt-4 pb-2.5">
                                            <div class="text-sm sm:text-base font-black uppercase tracking-[0.15em] text-slate-500 dark:text-slate-400 text-center w-full">Route</div>
                                        </div>
                                        <div class="px-4 pb-3">
                                            <div class="min-h-[4.5rem] flex flex-col justify-center">
                                                <div class="hidden items-center justify-center gap-2 py-2" data-route-details-loading>
                                                    <span class="booking-route-details-spinner" aria-hidden="true"></span>
                                                    <span class="text-sm font-medium booking-route-details-loading-text">Route wordt berekend…</span>
                                                </div>
                                                <div class="hidden grid grid-cols-2 gap-3 sm:gap-4" data-route-details-stats aria-live="polite">
                                                    <div class="rounded-xl border border-slate-200/90 dark:border-slate-600/40 bg-neutral-primary px-3 py-2.5 sm:py-3 text-center shadow-sm">
                                                        <div class="text-[0.55rem] sm:text-[0.6rem] font-black uppercase tracking-[0.1em] text-slate-500 dark:text-slate-400 mb-0.5">Afstand</div>
                                                        <div class="text-lg sm:text-xl md:text-2xl font-bold text-heading tabular-nums leading-tight tracking-tight" data-route-details-km>—</div>
                                                    </div>
                                                    <div class="rounded-xl border border-slate-200/90 dark:border-slate-600/40 bg-neutral-primary px-3 py-2.5 sm:py-3 text-center shadow-sm">
                                                        <div class="text-[0.55rem] sm:text-[0.6rem] font-black uppercase tracking-[0.1em] text-slate-500 dark:text-slate-400 mb-0.5">Reistijd</div>
                                                        <div class="text-lg sm:text-xl md:text-2xl font-bold text-heading tabular-nums leading-tight tracking-tight" data-route-details-min>—</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <div class="booking-trip-route-map booking-route-map-viewport relative w-full shrink-0 overflow-hidden border-t border-slate-200/90 dark:border-slate-600/35 bg-slate-200/50 dark:bg-slate-800/50" data-trip-route-map-wrap>
                                        <div class="absolute inset-0 z-30 hidden flex-col items-center justify-center gap-3 bg-slate-200/85 dark:bg-slate-800/85 p-4 text-center" data-trip-route-map-loading aria-hidden="true">
                                            <span class="booking-route-map-spinner" aria-hidden="true"></span>
                                            <span class="text-xs sm:text-sm font-medium booking-route-details-loading-text">Kaart laden…</span>
                                        </div>
                                        <img src="" alt="" class="absolute inset-0 z-0 hidden h-full w-full object-cover" loading="eager" decoding="async" data-trip-route-map-static>
                                        <iframe
                                            title="Route op de kaart (alleen weergave)"
                                            class="absolute inset-0 z-0 hidden h-full w-full border-0"
                                            loading="eager"
                                            referrerpolicy="no-referrer-when-downgrade"
                                            data-trip-route-map-iframe
                                        ></iframe>
                                        <div class="absolute inset-0 z-10 hidden cursor-default bg-transparent" data-trip-route-map-blocker aria-hidden="true"></div>
                                        <div class="absolute inset-0 z-20 hidden flex items-center justify-center p-3 text-center text-xs sm:text-sm text-slate-500 dark:text-slate-400" data-trip-route-map-empty>
                                            Route wordt getoond zodra vertrek- en bestemming zijn ingevuld.
                                        </div>
                                        <div class="absolute inset-0 z-20 hidden flex-col items-center justify-center p-4 text-center text-xs sm:text-sm text-slate-600 dark:text-slate-300" data-trip-route-map-fallback>
                                            <a href="#" class="font-semibold text-fg-brand underline hover:no-underline" data-trip-route-map-link target="_blank" rel="noopener noreferrer">Route openen in Google Maps</a>
                                        </div>
                                    </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="booking-trip-right space-y-5">
                            <div>
                                <label class="block mb-2.5 text-base font-semibold text-heading">Ophaalmoment taxi</label>
                                <div class="relative mt-1 booking-datetime-wrap">
                                    <svg class="w-5 h-5 text-fg-brand absolute top-1/2 -translate-y-1/2 pointer-events-none z-10 ml-2" style="left: 3px;" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 2v3m8-3v3M3 9h18M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/>
                                    </svg>
                                    <input type="datetime-local" style="padding-left: 50px; width: 300px; max-width: 100%;" class="booking-datetime-input bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-lg focus:ring-brand focus:border-brand block w-auto pe-3 py-2.5 shadow-xs cursor-pointer" data-field="pickup_at" data-datetime-input data-placeholder-target="pickup_at" placeholder="{{ e($texts['pickup_datetime_placeholder'] ?? '') }}">
                                    <span class="booking-datetime-placeholder absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-400 text-sm leading-tight pointer-events-none truncate" style="left: 50px;" data-datetime-placeholder-for="pickup_at">{{ e($texts['pickup_datetime_placeholder'] ?? 'Selecteer datum en tijd') }}</span>
                                </div>
                                <p class="hidden mt-2 text-sm font-medium text-red-600 dark:text-red-300" data-booking-field-error="pickup_at" role="alert"></p>
                                <div class="hidden mt-2 text-sm text-red-600 dark:text-red-400" data-pickup-datetime-future-error role="alert">
                                    Kies een ophaalmoment in de toekomst.
                                </div>
                            </div>
                            <div>
                                <label class="inline-flex cursor-pointer select-none items-center gap-2.5">
                                    <input type="checkbox" role="switch" class="kt-switch kt-switch-sm shrink-0" data-field="return_trip" {{ !empty($logic['return_enabled_by_default']) ? 'checked' : '' }}>
                                    <span class="text-heading text-base font-semibold">Retour</span>
                                </label>
                                <div class="relative mt-3 booking-datetime-wrap booking-return-datetime-wrap">
                                    <svg class="w-5 h-5 text-fg-brand absolute top-1/2 -translate-y-1/2 pointer-events-none z-10 ml-2" style="left: 3px;" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 2v3m8-3v3M3 9h18M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/>
                                    </svg>
                                    <input type="datetime-local" style="padding-left: 70px; width: 300px; max-width: 100%;" class="booking-datetime-input bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-lg focus:ring-brand focus:border-brand block w-auto pe-3 py-2.5 shadow-xs cursor-pointer disabled:opacity-70 disabled:cursor-not-allowed" data-field="return_at" data-datetime-input data-placeholder-target="return_at" placeholder="Selecteer datum en tijd" {{ !empty($logic['return_enabled_by_default']) ? '' : 'disabled' }}>
                                    <span class="booking-datetime-placeholder absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-400 text-sm leading-tight pointer-events-none truncate" style="left: 50px;" data-datetime-placeholder-for="return_at">Selecteer datum en tijd</span>
                                </div>
                                <p class="hidden mt-2 text-sm font-medium text-red-600 dark:text-red-300" data-booking-field-error="return_at" role="alert"></p>
                            </div>
                            <div>
                                <label class="block mb-2.5 text-sm font-medium text-heading">Aantal reizigers</label>
                                <div class="booking-trip-passenger-stepper mt-1 inline-flex h-10 min-h-[2.5rem] box-border items-center gap-1.5 px-2 rounded-lg border border-default-medium bg-neutral-secondary-medium shadow-xs">
                                    <button type="button" class="booking-passenger-btn inline-flex items-center justify-center rounded-md h-8 w-8 text-sm text-heading hover:bg-neutral-secondary-soft transition-colors" data-delta="-1">-</button>
                                    <span class="min-w-[1.25rem] text-center font-semibold text-sm tabular-nums leading-none text-heading" data-passengers-display>{{ (int)($logic['default_passengers'] ?? 1) }}</span>
                                    <button type="button" class="booking-passenger-btn inline-flex items-center justify-center rounded-md h-8 w-8 text-sm text-fg-brand hover:bg-neutral-secondary-soft transition-colors" data-delta="1">+</button>
                                </div>
                            </div>
                            <div>
                                <label class="block mb-2.5 text-sm font-medium text-heading">Opmerking(en)</label>
                                <textarea class="mt-1 bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-lg focus:ring-brand focus:border-brand block w-full px-3 py-2.5 shadow-xs placeholder:text-body" rows="3" data-field="remarks" placeholder="{{ e($texts['remarks_placeholder'] ?? '') }}"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="hidden" id="booking-panel-contact" role="tabpanel" aria-labelledby="booking-tab-contact" data-step-panel="contact">
                    <h3 class="booking-module-step-heading font-semibold mb-4" style="{{ $stepHeadingStyle }}">{{ e($stepLabelByLogical['contact'] ?? 'Contactgegevens') }}</h3>
                    <div class="flex flex-col gap-4">
                        <div>
                            <label class="block mb-2.5 text-sm font-medium text-heading" for="booking-field-first_name">Voornaam <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span></label>
                            <input id="booking-field-first_name" type="text" class="mt-1 bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-lg focus:ring-brand focus:border-brand block w-full px-3 py-2.5 shadow-xs placeholder:text-body" data-field="first_name" autocomplete="given-name" aria-required="true">
                            <p class="hidden mt-1.5 text-sm font-medium text-red-600 dark:text-red-300" data-booking-field-error="first_name" role="alert"></p>
                        </div>
                        <div>
                            <label class="block mb-2.5 text-sm font-medium text-heading" for="booking-field-last_name">Achternaam <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span></label>
                            <input id="booking-field-last_name" type="text" class="mt-1 bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-lg focus:ring-brand focus:border-brand block w-full px-3 py-2.5 shadow-xs placeholder:text-body" data-field="last_name" autocomplete="family-name" aria-required="true">
                            <p class="hidden mt-1.5 text-sm font-medium text-red-600 dark:text-red-300" data-booking-field-error="last_name" role="alert"></p>
                        </div>
                        <div>
                            <label class="block mb-2.5 text-sm font-medium text-heading" for="booking-field-phone">Telefoonnummer <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span></label>
                            <input id="booking-field-phone" type="text" class="mt-1 bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-lg focus:ring-brand focus:border-brand block w-full px-3 py-2.5 shadow-xs placeholder:text-body" data-field="phone" autocomplete="tel" aria-required="true">
                            <p class="hidden mt-1.5 text-sm font-medium text-red-600 dark:text-red-300" data-booking-field-error="phone" role="alert"></p>
                        </div>
                        <div>
                            <label class="block mb-2.5 text-sm font-medium text-heading" for="booking-field-email">E-mailadres</label>
                            <input id="booking-field-email" type="email" class="mt-1 bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-lg focus:ring-brand focus:border-brand block w-full px-3 py-2.5 shadow-xs placeholder:text-body" data-field="email" autocomplete="email" inputmode="email">
                            <p class="hidden mt-1.5 text-sm font-medium text-red-600 dark:text-red-300" data-booking-field-error="email" role="alert"></p>
                        </div>
                    </div>
                    <p class="mt-6 text-sm text-body text-left" role="note"><span class="text-red-600 dark:text-red-400 font-medium" aria-hidden="true">*</span> Verplicht veld</p>
                </div>

                <div class="hidden w-full" id="booking-panel-confirm" role="tabpanel" aria-labelledby="booking-tab-confirm" data-step-panel="confirm">
                    <div class="booking-confirm-root w-full max-w-none mx-0">
                        <h3 class="booking-module-step-heading font-semibold mb-4" style="{{ $stepHeadingStyle }}">{{ e($stepLabelByLogical['confirm'] ?? 'Bevestiging') }}</h3>

                        <div class="booking-confirm-wireframe rounded-2xl border bg-stone-100/90 dark:bg-slate-950/40 shadow-[0_2px_12px_rgba(15,23,42,0.06)] overflow-hidden w-full">
                            {{-- 50/50: links route, kaart, opmerking; rechts overige details --}}
                            <div class="booking-confirm-grid grid grid-cols-1 lg:grid-cols-2 gap-0 min-w-0 divide-y lg:divide-y-0 lg:divide-x divide-slate-200/90 dark:divide-slate-600/35">
                                <div class="p-5 md:p-6 space-y-4 min-w-0 bg-white/70 dark:bg-slate-900/30 booking-confirm-col-left">
                                    <div class="booking-confirm-surface rounded-xl border bg-neutral-primary p-4 min-w-0 w-full max-w-full overflow-hidden">
                                        <div class="text-xs font-black uppercase tracking-[0.15em] text-slate-500 dark:text-slate-400 mb-2 text-center w-full">Route</div>
                                        <div class="booking-confirm-route-stack" data-summary-route-stacked></div>
                                    </div>
                                    <div class="booking-confirm-surface booking-confirm-map-surface rounded-xl border bg-neutral-primary p-0 min-w-0 overflow-hidden">
                                        <div class="booking-confirm-map-host booking-route-map-viewport relative w-full">
                                        <div class="booking-summary-route-map booking-confirm-summary-route-map overflow-hidden bg-slate-200/50 dark:bg-slate-800/50" data-summary-route-map-wrap>
                                            <div class="absolute inset-0 z-30 hidden flex-col items-center justify-center gap-3 bg-slate-200/85 dark:bg-slate-800/85 p-4 text-center" data-summary-route-map-loading aria-hidden="true">
                                                <span class="booking-route-map-spinner" aria-hidden="true"></span>
                                                <span class="text-sm font-medium booking-route-details-loading-text">Kaart laden…</span>
                                            </div>
                                            <img src="" alt="" class="absolute inset-0 z-0 hidden h-full w-full object-cover" loading="eager" decoding="async" data-summary-route-map-static>
                                            <iframe
                                                title="Route op de kaart (alleen weergave)"
                                                class="absolute inset-0 z-0 hidden h-full w-full border-0"
                                                loading="eager"
                                                referrerpolicy="no-referrer-when-downgrade"
                                                data-summary-route-map-iframe
                                            ></iframe>
                                            <div class="absolute inset-0 z-10 hidden cursor-default bg-transparent" data-summary-route-map-blocker aria-hidden="true"></div>
                                            <div class="absolute inset-0 z-20 hidden flex items-center justify-center p-4 text-center text-sm text-slate-500 dark:text-slate-400" data-summary-route-map-empty>
                                                Route wordt getoond zodra vertrek- en bestemming zijn ingevuld.
                                            </div>
                                            <div class="absolute inset-0 z-20 hidden flex-col items-center justify-center p-6 text-center text-sm text-slate-600 dark:text-slate-300" data-summary-route-map-fallback>
                                                <a href="#" class="font-semibold text-fg-brand underline hover:no-underline" data-summary-route-map-link target="_blank" rel="noopener noreferrer">Route openen in Google Maps</a>
                                            </div>
                                        </div>
                                        </div>
                                    </div>
                                    <div class="booking-confirm-surface rounded-xl border bg-neutral-primary p-4 shadow-sm">
                                        <div class="text-xs font-black uppercase tracking-[0.15em] text-slate-500 dark:text-slate-400 mb-2">Opmerking</div>
                                        <p class="text-sm text-body whitespace-pre-wrap break-words min-w-0" data-summary-remarks>—</p>
                                    </div>
                                </div>

                                <div class="p-5 md:p-6 space-y-4 min-w-0 bg-stone-50/90 dark:bg-slate-900/40 booking-confirm-col-right">
                                    <div class="booking-confirm-surface rounded-xl border bg-neutral-primary p-4 shadow-sm">
                                        <div class="text-xs font-black uppercase tracking-[0.15em] text-slate-500 dark:text-slate-400 mb-2">Ophaalmoment</div>
                                        <div class="text-lg sm:text-xl font-bold text-heading tabular-nums tracking-tight" data-summary-pickup-at>—</div>
                                    </div>
                                    <div class="booking-confirm-surface rounded-xl border bg-neutral-primary p-4 shadow-sm">
                                        <div class="text-xs font-black uppercase tracking-[0.15em] text-slate-500 dark:text-slate-400 mb-2">Passagiers</div>
                                        <div class="text-lg font-bold text-heading tabular-nums" data-summary-confirm-passengers>—</div>
                                    </div>
                                    <div class="booking-confirm-surface rounded-xl border bg-neutral-primary shadow-sm overflow-hidden">
                                        <div class="px-4 pt-4 pb-2">
                                            <div class="text-xs font-black uppercase tracking-[0.15em] text-slate-500 dark:text-slate-400">Voertuig / aanbieding</div>
                                        </div>
                                        <div class="hidden w-full" data-summary-vehicle-image-wrap>
                                            <div class="flex items-center justify-center bg-neutral-secondary/30 dark:bg-slate-800/45 min-h-[11rem] sm:min-h-[12rem]">
                                                <img src="" alt="" class="w-full max-h-52 sm:max-h-60 h-auto object-contain object-center block" data-summary-vehicle-image>
                                            </div>
                                        </div>
                                        <div class="px-4 py-3 text-center">
                                            <div class="text-base font-bold text-heading leading-snug" data-summary-offer>—</div>
                                        </div>
                                    </div>
                                    <div class="booking-confirm-surface rounded-xl border bg-neutral-primary p-4 shadow-sm">
                                        <div class="text-xs font-black uppercase tracking-[0.15em] text-slate-500 dark:text-slate-400 mb-2">Bagage</div>
                                        <div class="flex flex-wrap items-start gap-2" data-summary-baggage-list>
                                            <span class="inline-flex items-center rounded-full bg-neutral-secondary-medium px-2.5 py-1 text-sm text-body border border-default-medium/60">Geen bagage geselecteerd</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @php
                                $payBooking = !empty($bookingConfig['payment']['booking']);
                                $payDriver = !empty($bookingConfig['payment']['driver']);
                                $payChoiceVisible = $payBooking && $payDriver;
                            @endphp
                            @if($payBooking || $payDriver)
                            <div class="booking-confirm-surface mx-5 md:mx-8 mb-4 rounded-xl border bg-neutral-primary p-4 shadow-sm" data-booking-payment-block>
                                <div class="text-xs font-black uppercase tracking-[0.15em] text-slate-500 dark:text-slate-400 mb-3">Betaalwijze</div>
                                @if($payChoiceVisible)
                                <div class="space-y-2 text-sm" data-booking-payment-choice>
                                    <label class="flex items-start gap-2 cursor-pointer">
                                        <input type="radio" name="booking_payment_method" value="booking" class="mt-1" data-booking-payment-radio checked>
                                        <span><strong>Direct online betalen</strong><br><span class="text-slate-500 dark:text-slate-400">Na bevestiging ga je naar Mollie (iDEAL, kaart, …).</span></span>
                                    </label>
                                    <label class="flex items-start gap-2 cursor-pointer">
                                        <input type="radio" name="booking_payment_method" value="driver" class="mt-1" data-booking-payment-radio>
                                        <span><strong>Betalen in de taxi</strong><br><span class="text-slate-500 dark:text-slate-400">De chauffeur ontvangt een QR-code na de rit.</span></span>
                                    </label>
                                </div>
                                @elseif($payBooking)
                                <p class="text-sm text-body">Je betaalt direct online na het bevestigen van je boeking.</p>
                                <input type="hidden" data-booking-payment-fixed value="booking">
                                @else
                                <p class="text-sm text-body">Je betaalt in de taxi via de chauffeur-app (QR-code).</p>
                                <input type="hidden" data-booking-payment-fixed value="driver">
                                @endif
                            </div>
                            @endif

                            <div class="booking-confirm-total-strip flex flex-row items-center justify-between gap-4 border-t border-slate-200/90 dark:border-slate-600/40 px-5 py-3 md:px-8 md:py-4 bg-white/80 dark:bg-slate-900/50">
                                <span class="text-sm font-bold uppercase tracking-wide text-heading">Totaalbedrag</span>
                                <span class="text-lg md:text-xl font-bold tabular-nums text-heading" data-summary-total>—</span>
                            </div>
                        </div>
                        <p class="text-sm mt-4 text-slate-600 dark:text-slate-300 text-center">Controleer je gegevens en verstuur je boeking.</p>
                    </div>
                </div>
                </div>
            </div>

            <p class="mb-4 text-sm font-medium text-red-600 dark:text-red-300 hidden" data-booking-error role="alert"></p>
            <div class="mt-8 flex items-center justify-between">
                <button type="button" class="inline-flex items-center justify-center gap-2 w-max shrink-0 whitespace-nowrap px-4 py-3 text-sm font-bold border-2 rounded-lg transition-all duration-200 hover:bg-white/15 hover:shadow-xl hover:-translate-y-1" style="background-color: transparent; border-color: color-mix(in srgb, {{ e($sectionStyle['primary_color'] ?? $bookingDefaultAccent) }} 45%, transparent); color: {{ e($sectionStyle['primary_color'] ?? $bookingDefaultAccent) }};" data-booking-prev><span class="inline-flex shrink-0 leading-none" aria-hidden="true">&larr;</span><span>terug</span></button>
                <button type="button" class="inline-flex justify-center items-center px-6 py-3 text-sm font-bold border-2 rounded-lg transition-all duration-200 hover:shadow-xl hover:-translate-y-1 booking-next-default" style="background-color: transparent; border-color: color-mix(in srgb, {{ e($sectionStyle['primary_color'] ?? $bookingDefaultAccent) }} 45%, transparent); color: {{ e($sectionStyle['primary_color'] ?? $bookingDefaultAccent) }};" data-booking-next>Verder</button>
            </div>
            <p class="mt-3 text-sm font-medium text-green-700 dark:text-green-300 hidden" data-booking-success></p>
        </div>
    </div>
    </div>
    </div>
    </div>

    <div class="hidden fixed inset-0 z-[9999] flex items-center justify-center p-4 sm:p-6" data-booking-confirm-modal>
        <div class="absolute inset-0 bg-black/45 dark:bg-black/88" data-booking-confirm-backdrop style="backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);"></div>
        <div class="relative z-10 w-full max-w-md">
            <div class="absolute inset-0 rounded-2xl bg-white confirm-modal-bg" aria-hidden="true"></div>
            <div class="relative rounded-2xl border border-slate-200 confirm-modal-content text-slate-900 dark:text-slate-100 shadow-2xl p-6 md:p-7 text-center">
            <div class="flex justify-end items-start -mt-1 -mr-1 mb-1">
                <button type="button" class="p-1 text-slate-500 hover:text-slate-700 dark:text-slate-300 dark:hover:text-white transition-colors" aria-label="Sluiten" data-booking-confirm-close>
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>
            <h4 class="text-2xl font-bold mb-2">Boeking versturen</h4>
            <p class="text-base text-slate-600 dark:text-slate-200">Weet u zeker dat u de boeking wilt versturen?</p>
            <div class="mt-6 flex items-center justify-center gap-2.5 flex-wrap">
                <button type="button" class="inline-flex justify-center items-center px-4 py-2.5 text-sm font-semibold border rounded-lg transition-colors border-slate-400 text-slate-700 hover:bg-slate-200 dark:border-slate-600 dark:text-slate-100 dark:hover:bg-slate-800/80" data-booking-confirm-close>Annuleren</button>
                <button type="button" class="inline-flex justify-center items-center px-4 py-2.5 text-sm font-semibold rounded-lg transition-colors bg-blue-600 text-white hover:bg-blue-500" data-booking-confirm-submit>Bevestigen</button>
            </div>
            </div>
        </div>
    </div>

    <div class="hidden fixed inset-0 z-[9999] flex items-center justify-center p-4 sm:p-6" data-booking-success-modal>
        <div class="absolute inset-0 bg-black/75 backdrop-blur-2xl" data-booking-success-backdrop></div>
        <div class="relative z-10 w-full max-w-md rounded-2xl border border-violet-400/35 bg-slate-950/98 text-slate-100 shadow-2xl p-6 md:p-7 text-center">
            <button type="button" class="absolute top-3 right-3 inline-flex h-9 w-9 items-center justify-center rounded-full text-slate-300 hover:bg-slate-800/80 hover:text-white transition-colors" aria-label="Sluiten" data-booking-success-close>
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
            <div class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-400 mb-4 mx-auto">
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M14 10V5.8a2.8 2.8 0 0 0-2.8-2.8h-.2L8 10.2v10.8h9.2c1.2 0 2.2-.8 2.5-2l1.1-5a2.5 2.5 0 0 0-2.4-3h-4.4ZM8 10.2H5.8C4.8 10.2 4 11 4 12v7.2c0 1 .8 1.8 1.8 1.8H8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <h4 class="text-2xl font-bold mb-2">Boeking ontvangen</h4>
            <p class="text-base text-slate-300" data-booking-success-modal-message>Bedankt! Je boeking is ontvangen.</p>
            <div class="mt-6 flex items-center justify-center gap-2.5">
                <button type="button" class="inline-flex justify-center items-center px-4 py-2.5 text-sm font-semibold border rounded-lg transition-colors border-slate-600 text-slate-100 hover:bg-slate-800/80" data-booking-success-close>Sluiten</button>
            </div>
        </div>
    </div>
</section>

<style>
/* Mobiel: volle breedte; desktop: optionele max-breedte uit admin */
.booking-module-layout {
    width: 100%;
    max-width: 100%;
}
.booking-module-outer {
    width: 100%;
    max-width: 100%;
}
@media (min-width: 768px) {
    .booking-module-outer {
        max-width: var(--booking-module-max-width, 100%);
    }
}
@media (max-width: 767px) {
    .booking-module-scroll-reveal .booking-module-card {
        border-radius: 0 !important;
        border-left-width: 0;
        border-right-width: 0;
    }
}
.booking-module-title {
    font-size: clamp(1.125rem, 4vw + 0.5rem, var(--booking-title-size-max, 2.25rem));
}
.booking-module-step-heading {
    font-size: clamp(1rem, 2.5vw + 0.5rem, var(--booking-step-heading-size-max, 1.875rem));
}

/* Scroll-reveal: infade bij in beeld */
.booking-module-scroll-reveal .booking-module-reveal-item {
    opacity: 0;
    transform: translateY(28px);
    transition: opacity 0.55s ease-out, transform 0.55s ease-out;
}
.booking-module-scroll-reveal.is-in-view .booking-module-reveal-item {
    opacity: 1;
    transform: translateY(0);
}

[data-nexataxi-booking-module] .booking-module-card {
    overflow: visible;
}

[data-nexataxi-booking-module] .booking-step-panels-shell {
    position: relative;
    width: 100%;
}

[data-nexataxi-booking-module] [data-booking-next].booking-next-default:hover {
    background-color: rgba(255, 255, 255, 0.15);
}
[data-nexataxi-booking-module] [data-booking-next].booking-next--final {
    border-color: color-mix(in srgb, rgb(22 163 74) 50%, transparent) !important;
    color: rgb(22 163 74) !important;
}
[data-nexataxi-booking-module] [data-booking-next].booking-next--final:hover {
    background-color: rgba(22, 163, 74, 0.14) !important;
}
.dark [data-nexataxi-booking-module] [data-booking-next].booking-next--final,
html.dark [data-nexataxi-booking-module] [data-booking-next].booking-next--final {
    border-color: color-mix(in srgb, rgb(52 211 153) 55%, transparent) !important;
    color: rgb(52 211 153) !important;
}
.dark [data-nexataxi-booking-module] [data-booking-next].booking-next--final:hover,
html.dark [data-nexataxi-booking-module] [data-booking-next].booking-next--final:hover {
    background-color: rgba(52, 211, 153, 0.12) !important;
}

[data-nexataxi-booking-module] .booking-confirm-route-stack {
    width: 100%;
}
/* Zelfde viewport voor rit-stap en bevestiging → identieke static-map crop/zoom (object-cover) */
[data-nexataxi-booking-module] .booking-route-map-viewport,
[data-nexataxi-booking-module] .booking-confirm-map-host {
    position: relative;
    width: 100%;
    min-height: 280px;
    height: clamp(280px, 38vh, 420px);
    max-height: min(48vh, 28rem);
    overflow: hidden;
}
[data-nexataxi-booking-module] .booking-confirm-map-host .booking-summary-route-map {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
}
/* Geen grijze naad onderaan: slate-placeholder onder geschaalde kaart leek een losse lijn; host erft bg van kaart-surface */
[data-nexataxi-booking-module] .booking-confirm-map-surface .booking-confirm-summary-route-map {
    background-color: transparent !important;
}
[data-nexataxi-booking-module] .booking-confirm-map-surface .booking-confirm-map-host {
    background-color: inherit;
}
/* Linkerkolom: geen extra rand/schaduw onderaan de kolom */
[data-nexataxi-booking-module] .booking-confirm-col-left {
    border-bottom: none !important;
    box-shadow: none !important;
    outline: none !important;
}

/* Bevestiging: lichte borders — expliciet !important i.v.m. theme die .border / kaart-randen overschrijft */
[data-nexataxi-booking-module] .booking-confirm-wireframe {
    border-width: 1px !important;
    border-style: solid !important;
    border-color: rgb(241 245 249) !important; /* slate-100 */
}
[data-nexataxi-booking-module] .booking-confirm-surface {
    border-width: 1px !important;
    border-style: solid !important;
    border-color: rgb(241 245 249) !important;
}
[data-nexataxi-booking-module] .booking-confirm-total-strip {
    border-top-width: 1px !important;
    border-top-style: solid !important;
    border-top-color: rgb(241 245 249) !important; /* slate-100, gelijk aan overige bevestigingsranden */
}
.dark [data-nexataxi-booking-module] .booking-confirm-total-strip,
html.dark [data-nexataxi-booking-module] .booking-confirm-total-strip {
    border-top-color: rgba(71, 85, 105, 0.42) !important;
}
.dark [data-nexataxi-booking-module] .booking-confirm-wireframe,
html.dark [data-nexataxi-booking-module] .booking-confirm-wireframe {
    border-color: rgba(71, 85, 105, 0.42) !important;
}
.dark [data-nexataxi-booking-module] .booking-confirm-surface,
html.dark [data-nexataxi-booking-module] .booking-confirm-surface {
    border-color: rgba(71, 85, 105, 0.42) !important;
}
/* Linkerkolom / kaart: geen onderborder of shadow die als witte streep leest */
[data-nexataxi-booking-module] .booking-confirm-map-surface {
    border-bottom-width: 0 !important;
    box-shadow: none !important;
}
/* Grid-scheiding: lg+ verticale lijn tussen kolommen; onder sm/md geen horizontale lijn (was storend onder route-blok) */
@media (max-width: 1023px) {
    [data-nexataxi-booking-module] .booking-confirm-grid > :not([hidden]) ~ :not([hidden]) {
        border-top-width: 0 !important;
    }
}
@media (min-width: 1024px) {
    [data-nexataxi-booking-module] .booking-confirm-grid > :not([hidden]) ~ :not([hidden]) {
        border-left-color: rgb(241 245 249) !important;
    }
    .dark [data-nexataxi-booking-module] .booking-confirm-grid > :not([hidden]) ~ :not([hidden]),
    html.dark [data-nexataxi-booking-module] .booking-confirm-grid > :not([hidden]) ~ :not([hidden]) {
        border-left-color: rgba(71, 85, 105, 0.38) !important;
    }
}

/* Geselecteerde aanbiedingskaart: groene border (#0cea36); ook bij hover (niet de grijs/wit-hover van niet-geselecteerd) */
[data-nexataxi-booking-module] [data-offer-id][aria-pressed="true"],
[data-nexataxi-booking-module] [data-offer-id][aria-pressed="true"]:hover {
    border-color: #0cea36 !important;
    border-width: 2px;
    box-shadow: 0 0 0 2px rgba(12, 234, 54, 0.5);
}

/* Trip: route-kaart (zelfde sfeer als bevestiging) — lichte primaire tint (admin) */
[data-nexataxi-booking-module] .booking-trip-route-card {
    background-color: color-mix(in srgb, var(--booking-primary, {{ e($bookingDefaultAccent) }}) 11%, white) !important;
    border-color: color-mix(in srgb, var(--booking-primary, {{ e($bookingDefaultAccent) }}) 28%, rgba(148, 163, 184, 0.55)) !important;
    color: #0f172a;
}
.dark [data-nexataxi-booking-module] .booking-trip-route-card {
    background-color: color-mix(in srgb, var(--booking-primary, {{ e($bookingDefaultAccent) }}) 14%, rgb(30 41 59)) !important;
    border-color: color-mix(in srgb, var(--booking-primary, {{ e($bookingDefaultAccent) }}) 38%, rgb(71 85 105)) !important;
    color: #f8fafc;
}
[data-nexataxi-booking-module] [data-booking-steps-nav] .booking-step-tab,
[data-nexataxi-booking-module] #booking-steps-select {
    font-size: var(--booking-tab-font-size, 14px) !important;
}
[data-nexataxi-booking-module] [data-booking-steps-nav] .booking-step-tab.active {
    font-size: calc(var(--booking-tab-font-size, 14px) * 1.12) !important;
}
[data-nexataxi-booking-module] [data-summary-route-map-static],
[data-nexataxi-booking-module] [data-trip-route-map-static],
[data-nexataxi-booking-module] [data-summary-route-map-iframe],
[data-nexataxi-booking-module] [data-trip-route-map-iframe] {
    transform: scale(var(--booking-route-map-img-scale, 1));
    transform-origin: center center;
}
[data-nexataxi-booking-module] .booking-route-details-loading-text {
    color: #334155;
}
.dark [data-nexataxi-booking-module] .booking-route-details-loading-text {
    color: #e2e8f0;
}
@keyframes booking-route-details-spin {
    to { transform: rotate(360deg); }
}
[data-nexataxi-booking-module] .booking-route-details-spinner {
    display: inline-block;
    width: 1rem;
    height: 1rem;
    border: 2px solid color-mix(in srgb, var(--booking-primary, {{ e($bookingDefaultAccent) }}) 35%, #cbd5e1);
    border-top-color: var(--booking-primary, {{ e($bookingDefaultAccent) }});
    border-radius: 9999px;
    animation: booking-route-details-spin 0.65s linear infinite;
    flex-shrink: 0;
}
.dark [data-nexataxi-booking-module] .booking-route-details-spinner {
    border: 2px solid color-mix(in srgb, var(--booking-primary, {{ e($bookingDefaultAccent) }}) 40%, #64748b);
    border-top-color: color-mix(in srgb, var(--booking-primary, {{ e($bookingDefaultAccent) }}) 85%, white);
}

/* Kaart-placeholder: spinner 2× groter dan route-details-spinner (1rem → 2rem) */
[data-nexataxi-booking-module] .booking-route-map-spinner {
    display: inline-block;
    width: 2rem;
    height: 2rem;
    border: 4px solid color-mix(in srgb, var(--booking-primary, {{ e($bookingDefaultAccent) }}) 35%, #cbd5e1);
    border-top-color: var(--booking-primary, {{ e($bookingDefaultAccent) }});
    border-radius: 9999px;
    animation: booking-route-details-spin 0.65s linear infinite;
    flex-shrink: 0;
}
.dark [data-nexataxi-booking-module] .booking-route-map-spinner {
    border: 4px solid color-mix(in srgb, var(--booking-primary, {{ e($bookingDefaultAccent) }}) 40%, #64748b);
    border-top-color: color-mix(in srgb, var(--booking-primary, {{ e($bookingDefaultAccent) }}) 85%, white);
}

/* Aanbiedingen: hover niet-geselecteerd = alleen border (light: donkergrijs, dark: wit) */
[data-nexataxi-booking-module] .booking-offer-card[aria-pressed="false"]:hover {
    border-color: #334155 !important;
    box-shadow: none;
}
.dark [data-nexataxi-booking-module] .booking-offer-card[aria-pressed="false"]:hover,
html.dark [data-nexataxi-booking-module] .booking-offer-card[aria-pressed="false"]:hover {
    border-color: #ffffff !important;
    box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.4);
}

[data-nexataxi-booking-module] .booking-module-card {
    scroll-margin-top: 5.5rem;
}
[data-nexataxi-booking-module] .booking-step-panels-shell {
    overflow-anchor: none;
}
[data-nexataxi-booking-module] .booking-trip-layout {
    display: grid;
    grid-template-columns: 1fr;
    gap: 24px;
}

[data-nexataxi-booking-module] .booking-return-datetime-wrap.booking-return-readonly {
    opacity: 0.7;
    pointer-events: none;
}
[data-nexataxi-booking-module] .booking-return-datetime-wrap.booking-return-readonly .booking-datetime-input {
    cursor: default;
}

[data-nexataxi-booking-module] [data-booking-success-modal] {
    animation: bookingFadeIn 180ms ease-out;
}
/* Bevestig-modal: geen fade, blur en modal direct zichtbaar */
[data-nexataxi-booking-module] [data-booking-confirm-modal] {
    animation: none;
}

/* Bevestig-modal: light mode = witte achtergrond */
[data-booking-confirm-modal] .confirm-modal-bg {
    background-color: #ffffff;
}
[data-booking-confirm-modal] .confirm-modal-content {
    border-color: #e2e8f0;
}

/* Bevestig-modal: dark mode = rgb(15 23 42), alleen bij class .dark op html */
html.dark [data-booking-confirm-modal] .confirm-modal-bg,
.dark [data-booking-confirm-modal] .confirm-modal-bg {
    background-color: rgb(15, 23, 42);
}
html.dark [data-booking-confirm-modal] .confirm-modal-content,
.dark [data-booking-confirm-modal] .confirm-modal-content {
    border-color: rgba(148, 163, 184, 0.5);
}

html.booking-modal-open,
body.booking-modal-open {
    overflow: hidden !important;
}

@keyframes bookingFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

[data-nexataxi-booking-module] .booking-baggage-layout {
    display: grid;
    grid-template-columns: 1fr;
    gap: 24px;
}

[data-nexataxi-booking-module] .booking-baggage-card {
    min-height: 152px;
}

[data-nexataxi-booking-module] .booking-trip-left,
[data-nexataxi-booking-module] .booking-trip-right {
    min-width: 0;
}

[data-nexataxi-booking-module] .booking-trip-right {
    padding-left: 0;
    border-left: 0;
}

[data-nexataxi-booking-module] .booking-route-wrap {
    overflow: visible;
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    column-gap: 12px;
    align-items: stretch;
}

[data-nexataxi-booking-module] .booking-route-icons {
    width: 24px;
    position: relative;
}

[data-nexataxi-booking-module] .booking-route-icons-list {
    position: relative;
    width: 24px;
    min-height: 100%;
}

[data-nexataxi-booking-module] .booking-route-icon-row {
    width: 24px;
    height: 24px;
    position: absolute;
    left: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

[data-nexataxi-booking-module] .booking-route-icon-row--field {
    height: 24px;
}

[data-nexataxi-booking-module] .booking-route-icon-row--middle {
    height: 24px;
}

[data-nexataxi-booking-module] .booking-route-icon-connector {
    position: absolute;
    left: 11px;
    width: 2px;
    height: 20px;
    border-radius: 999px;
    background: currentColor;
    opacity: 0.4;
    margin: 0;
}

[data-nexataxi-booking-module] .booking-route-fields {
    display: flex;
    flex-direction: column;
    gap: 20px;
    overflow: visible;
}

[data-nexataxi-booking-module] .booking-route-field-row {
    position: relative;
    min-height: 24px;
    display: flex;
    align-items: center;
    overflow: visible;
}

/* Foutregel onder het veld, niet naast (pickup/dropoff hebben input-wrapper + p) */
[data-nexataxi-booking-module] .booking-route-field-row[data-route-row="pickup"],
[data-nexataxi-booking-module] .booking-route-field-row[data-route-row="dropoff"] {
    flex-direction: column;
    align-items: stretch;
}

[data-nexataxi-booking-module] .booking-stopover-row {
    position: relative;
}

[data-nexataxi-booking-module] [data-stopovers-list] {
    display: contents;
}

[data-nexataxi-booking-module] .booking-route-fields input {
    width: 100%;
}

[data-nexataxi-booking-module] .booking-address-suggestions-panel,
.booking-address-suggestions-panel--fixed {
    position: absolute;
    left: 0;
    right: 0;
    top: calc(100% + 6px);
    z-index: 10001;
}
.booking-address-suggestions-panel--fixed {
    right: auto;
}
.booking-address-suggestions-panel.hidden,
.booking-address-suggestions-panel--fixed.hidden {
    pointer-events: none !important;
    visibility: hidden;
}
.booking-address-suggestions-panel--fixed,
[data-nexataxi-booking-module] .booking-address-suggestions-panel {
    width: 100%;
    border: 1px solid rgba(148, 163, 184, 0.55);
    border-radius: 10px;
    background: #d1d5db;
    box-shadow: 0 14px 34px rgba(2, 6, 23, 0.25);
    max-height: 320px;
    overflow: auto;
}
.dark .booking-address-suggestions-panel--fixed {
    background: #1e293b;
    border-color: rgba(148, 163, 184, 0.35);
}
.dark .booking-address-suggestions-panel--fixed .booking-address-suggestion-item {
    color: #f1f5f9;
    border-bottom-color: rgba(148, 163, 184, 0.25);
}
.dark .booking-address-suggestions-panel--fixed .booking-address-suggestion-item:hover {
    background: rgba(148, 163, 184, 0.2);
    color: #fff;
}
.dark .booking-address-suggestions-panel--fixed .booking-address-suggestion-loading {
    color: #94a3b8;
}
.booking-address-suggestions-panel--fixed .booking-address-suggestion-item {
    width: 100%;
    display: block;
    text-align: left;
    border: 0;
    background: transparent;
    padding: 16px;
    font-size: 13px;
    line-height: 1.45;
    color: #0f172a;
    border-bottom: 1px solid rgba(148, 163, 184, 0.35);
}
.booking-address-suggestions-panel--fixed .booking-address-suggestion-item:hover {
    background: rgba(91, 33, 182, 0.08);
}
.booking-address-suggestions-panel--fixed .booking-address-suggestion-loading {
    pointer-events: none;
    color: #64748b;
}

[data-nexataxi-booking-module] .dark .booking-address-suggestions-panel,
.dark [data-nexataxi-booking-module] .booking-address-suggestions-panel {
    background: #1e293b;
    border-color: rgba(148, 163, 184, 0.35);
    box-shadow: 0 16px 36px rgba(2, 6, 23, 0.6);
}

[data-nexataxi-booking-module] .booking-address-suggestion-item {
    width: 100%;
    display: block;
    text-align: left;
    border: 0;
    background: transparent;
    padding: 16px 16px;
    font-size: 13px;
    line-height: 1.45;
    color: #0f172a;
    border-bottom: 1px solid rgba(148, 163, 184, 0.35);
}

[data-nexataxi-booking-module] .booking-address-suggestion-loading {
    pointer-events: none;
    color: var(--body, #64748b);
}

[data-nexataxi-booking-module] .booking-address-suggestion-item:hover {
    background: rgba(91, 33, 182, 0.08);
}

[data-nexataxi-booking-module] .dark .booking-address-suggestion-item,
.dark [data-nexataxi-booking-module] .booking-address-suggestion-item {
    color: #e2e8f0;
    border-bottom-color: rgba(148, 163, 184, 0.22);
}

[data-nexataxi-booking-module] .dark .booking-address-suggestion-item:hover,
.dark [data-nexataxi-booking-module] .booking-address-suggestion-item:hover {
    background: rgba(148, 163, 184, 0.16);
}

[data-nexataxi-booking-module] .booking-route-fields input.booking-route-input-short {
    width: 100% !important;
    color: #0f172a;
    border-color: rgba(148, 163, 184, 0.45) !important;
}

[data-nexataxi-booking-module] input.border-default-medium,
[data-nexataxi-booking-module] textarea.border-default-medium,
[data-nexataxi-booking-module] select.border-default-medium {
    border-color: rgba(148, 163, 184, 0.45) !important;
}

[data-nexataxi-booking-module] .border-default-medium {
    border-color: rgba(148, 163, 184, 0.45) !important;
}

[data-nexataxi-booking-module] input.border-default-medium:focus,
[data-nexataxi-booking-module] textarea.border-default-medium:focus,
[data-nexataxi-booking-module] select.border-default-medium:focus {
    border-color: rgba(148, 163, 184, 0.45) !important;
}

[data-nexataxi-booking-module] input:focus,
[data-nexataxi-booking-module] textarea:focus,
[data-nexataxi-booking-module] select:focus,
[data-nexataxi-booking-module] input:focus-visible,
[data-nexataxi-booking-module] textarea:focus-visible,
[data-nexataxi-booking-module] select:focus-visible {
    border-color: rgba(148, 163, 184, 0.45) !important;
    box-shadow: none !important;
    outline: none !important;
}

[data-nexataxi-booking-module] .booking-route-fields input.booking-route-input-short.is-selected {
    border-color: rgba(148, 163, 184, 0.45) !important;
}

[data-nexataxi-booking-module] .booking-route-fields input.booking-route-input-short::placeholder {
    color: #64748b;
}

[data-nexataxi-booking-module] .dark .booking-route-fields input.booking-route-input-short,
.dark [data-nexataxi-booking-module] .booking-route-fields input.booking-route-input-short {
    color: #e2e8f0;
}

[data-nexataxi-booking-module] .dark .booking-route-fields input.booking-route-input-short::placeholder,
.dark [data-nexataxi-booking-module] .booking-route-fields input.booking-route-input-short::placeholder {
    color: #94a3b8;
}

[data-nexataxi-booking-module] .booking-route-fields input.booking-route-input-short:focus,
[data-nexataxi-booking-module] .booking-route-fields input.booking-route-input-short.is-selected:focus,
[data-nexataxi-booking-module] .booking-route-fields input.booking-route-input-short:focus-visible,
[data-nexataxi-booking-module] .booking-route-fields input.booking-route-input-short.is-selected:focus-visible {
    border-color: rgba(148, 163, 184, 0.45) !important;
    box-shadow: none !important;
    outline: none !important;
}

[data-nexataxi-booking-module] .dark .booking-route-fields input.booking-route-input-short.is-selected,
.dark [data-nexataxi-booking-module] .booking-route-fields input.booking-route-input-short.is-selected {
    color: #ffffff !important;
}

[data-nexataxi-booking-module] .booking-route-fields input.booking-route-input-short:-webkit-autofill,
[data-nexataxi-booking-module] .booking-route-fields input.booking-route-input-short:-webkit-autofill:hover,
[data-nexataxi-booking-module] .booking-route-fields input.booking-route-input-short:-webkit-autofill:focus {
    -webkit-text-fill-color: inherit;
    -webkit-box-shadow: 0 0 0px 1000px rgba(148, 163, 184, 0.16) inset !important;
    box-shadow: 0 0 0px 1000px rgba(148, 163, 184, 0.16) inset !important;
    transition: background-color 9999s ease-in-out 0s;
}

[data-nexataxi-booking-module] .booking-route-fields input.booking-route-input-short.is-selected:-webkit-autofill,
[data-nexataxi-booking-module] .booking-route-fields input.booking-route-input-short.is-selected:-webkit-autofill:hover,
[data-nexataxi-booking-module] .booking-route-fields input.booking-route-input-short.is-selected:-webkit-autofill:focus {
    -webkit-text-fill-color: #ffffff !important;
}

[data-nexataxi-booking-module] .booking-datetime-input {
    border-color: rgba(148, 163, 184, 0.45) !important;
}

[data-nexataxi-booking-module] .booking-datetime-input:focus {
    border-color: rgba(148, 163, 184, 0.45) !important;
    box-shadow: none !important;
}

@media (min-width: 1024px) {
    [data-nexataxi-booking-module] .booking-trip-layout {
        grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.8fr);
        gap: 28px;
    }
    [data-nexataxi-booking-module] .booking-trip-right {
        border-left: 1px solid rgba(148, 163, 184, 0.35);
        padding-left: 24px;
    }
    [data-nexataxi-booking-module] .booking-route-wrap {
        grid-template-columns: auto minmax(0, 1fr);
        column-gap: 12px;
    }
}

[data-nexataxi-booking-module] [data-step-panel] .rounded-xl,
[data-nexataxi-booking-module] [data-step-panel] .rounded-lg {
    color: #0f172a;
}
.dark [data-nexataxi-booking-module] [data-step-panel] .rounded-xl,
.dark [data-nexataxi-booking-module] [data-step-panel] .rounded-lg {
    color: #f8fafc;
}
[data-nexataxi-booking-module] .booking-datetime-input::-webkit-calendar-picker-indicator {
    opacity: 0;
    width: 0;
    margin: 0;
    pointer-events: none;
}
[data-nexataxi-booking-module] .booking-datetime-input {
    border-color: rgba(148, 163, 184, 0.45) !important;
    color: transparent !important;
    -webkit-text-fill-color: transparent !important;
    caret-color: transparent;
    text-shadow: none;
    color-scheme: light;
}
.dark [data-nexataxi-booking-module] .booking-datetime-input {
    border-color: rgba(148, 163, 184, 0.55) !important;
    color-scheme: dark;
}
[data-nexataxi-booking-module] .booking-datetime-input::-webkit-clear-button,
[data-nexataxi-booking-module] .booking-datetime-input::-webkit-inner-spin-button {
    display: none;
}
[data-nexataxi-booking-module] .booking-datetime-input,
[data-nexataxi-booking-module] .booking-datetime-input::-webkit-datetime-edit,
[data-nexataxi-booking-module] .booking-datetime-input::-webkit-datetime-edit-fields-wrapper,
[data-nexataxi-booking-module] .booking-datetime-input::-webkit-datetime-edit-text,
[data-nexataxi-booking-module] .booking-datetime-input::-webkit-datetime-edit-month-field,
[data-nexataxi-booking-module] .booking-datetime-input::-webkit-datetime-edit-day-field,
[data-nexataxi-booking-module] .booking-datetime-input::-webkit-datetime-edit-year-field,
[data-nexataxi-booking-module] .booking-datetime-input::-webkit-datetime-edit-hour-field,
[data-nexataxi-booking-module] .booking-datetime-input::-webkit-datetime-edit-minute-field,
[data-nexataxi-booking-module] .booking-datetime-input::-webkit-datetime-edit-ampm-field {
    color: transparent !important;
    -webkit-text-fill-color: transparent !important;
}
[data-nexataxi-booking-module] .booking-datetime-input::-webkit-datetime-edit {
    display: none;
}
.dark [data-nexataxi-booking-module] .booking-datetime-input::-webkit-calendar-picker-indicator {
    opacity: 0;
}

[data-nexataxi-booking-module] .booking-datetime-input.booking-datetime-input--past-invalid {
    border-color: rgb(239 68 68) !important;
    box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.28);
}
[data-nexataxi-booking-module] .booking-datetime-input.booking-datetime-input--past-invalid:focus {
    border-color: rgb(239 68 68) !important;
    box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.35);
}
.dark [data-nexataxi-booking-module] .booking-datetime-input.booking-datetime-input--past-invalid {
    border-color: rgb(248 113 113) !important;
    box-shadow: 0 0 0 2px rgba(248, 113, 113, 0.3);
}

[data-nexataxi-booking-module] input.booking-field-input--error:not(.booking-datetime-input--past-invalid) {
    border-color: rgb(239 68 68) !important;
    border-width: 1px !important;
    box-shadow: none !important;
}
[data-nexataxi-booking-module] input.booking-field-input--error:not(.booking-datetime-input--past-invalid):focus {
    border-color: rgb(239 68 68) !important;
    box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.22) !important;
}
.dark [data-nexataxi-booking-module] input.booking-field-input--error:not(.booking-datetime-input--past-invalid) {
    border-color: rgb(248 113 113) !important;
    border-width: 1px !important;
    box-shadow: none !important;
}
.dark [data-nexataxi-booking-module] input.booking-field-input--error:not(.booking-datetime-input--past-invalid):focus {
    border-color: rgb(248 113 113) !important;
    box-shadow: 0 0 0 2px rgba(248, 113, 113, 0.25) !important;
}

/* Geen standaard checkbox-styling op admin-achtige schakelaars (.kt-switch) */
[data-nexataxi-booking-module] input[type="checkbox"]:not(.kt-switch) {
    accent-color: #6366f1;
    border-color: rgba(148, 163, 184, 0.55) !important;
    background-color: rgba(148, 163, 184, 0.14);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.14);
    transition: border-color 160ms ease, box-shadow 160ms ease, background-color 160ms ease, transform 120ms ease;
}

[data-nexataxi-booking-module] input[type="checkbox"]:not(.kt-switch):hover {
    border-color: rgba(99, 102, 241, 0.58) !important;
}

[data-nexataxi-booking-module] input[type="checkbox"]:not(.kt-switch):focus-visible {
    outline: none;
    border-color: rgba(99, 102, 241, 0.65) !important;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
}

[data-nexataxi-booking-module] input[type="checkbox"]:not(.kt-switch):checked {
    border-color: rgba(99, 102, 241, 0.72) !important;
    background-color: rgba(99, 102, 241, 0.22);
}
</style>

@push('scripts')
<script>
(function() {
    var root = document.querySelector('[data-nexataxi-booking-module]');
    if (!root) return;

    var config = @json($bookingConfig);
    var quoteUrl = @json(route('nexataxi.booking.quote'));
    var submitUrl = @json(route('nexataxi.booking.submit'));
    var pageId = @json($bookingPageId);
    var sectionKey = @json($sectionKey ?? 'component:taxi.boekingsmodule');
    var bookingModuleName = @json(isset($page) && !empty($page->module_name) ? $page->module_name : null);
    var mapsApiKey = @json($mapsApiKey);
    var activeTabColor = @json($sectionStyle['active_tab_color'] ?? $bookingDefaultAccent);
    var bookingPrimaryHex = @json($sectionStyle['primary_color'] ?? $bookingDefaultAccent);
    var whatsappClickToChatEnabled = @json($whatsappClientClickToChat);
    var whatsappClickToChatNumber = @json($whatsappClickToChatNumber);
    var whatsappDraftWindow = null;
    var maxStopovers = parseInt(config.logic && config.logic.max_stopovers != null ? config.logic.max_stopovers : 3, 10);
    if (isNaN(maxStopovers)) maxStopovers = 3;
    maxStopovers = Math.max(0, Math.min(6, maxStopovers));
    var stepOrder = Array.isArray(config.step_order) && config.step_order.length
        ? config.step_order.slice(0, 5)
        : ['trip', 'baggage', 'offers', 'contact', 'confirm'];
    ['trip', 'baggage', 'offers', 'contact', 'confirm'].forEach(function(stepKey) {
        if (stepOrder.indexOf(stepKey) === -1) stepOrder.push(stepKey);
    });
    stepOrder = stepOrder.slice(0, 5);

    var skipBaggageStep = !!(config.logic && config.logic.skip_baggage_step);
    var state = {
        step: 1,
        maxStep: 1,
        has_baggage: !skipBaggageStep,
        passengers: parseInt(config.logic && config.logic.default_passengers ? config.logic.default_passengers : 1, 10),
        minPassengers: parseInt(config.logic && config.logic.min_passengers ? config.logic.min_passengers : 1, 10),
        maxPassengers: parseInt(config.logic && config.logic.max_passengers ? config.logic.max_passengers : 8, 10),
        maxStopovers: maxStopovers,
        pickup_address: '',
        stopovers: [],
        dropoff_address: '',
        pickup_at: '',
        return_trip: !!(config.logic && config.logic.return_enabled_by_default),
        return_at: '',
        remarks: '',
        first_name: '',
        last_name: '',
        phone: '',
        email: '',
        pickup_lat: null,
        pickup_lng: null,
        stopovers_geo: [],
        dropoff_lat: null,
        dropoff_lng: null,
        distance_meters: 0,
        duration_seconds: 0,
        summary_route_polyline: '',
        baggage: {},
        special_baggage: {},
        offers: [],
        selected_offer_id: null,
        offer_display_mode: (config.logic && config.logic.offer_display_mode ? config.logic.offer_display_mode : 'vehicle'),
        person_range: '1-4'
    };

    function formatEuro(value) {
        var num = (typeof value === 'number' ? value : parseFloat(value || 0));
        return '€ ' + num.toLocaleString('nl-NL', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function compactAddress(raw) {
        var text = String(raw || '').trim();
        if (!text) return '';
        var parts = text.split(',').map(function(p) { return p.trim(); }).filter(Boolean);
        if (!parts.length) return text;

        var first = parts[0] || '';
        var second = parts[1] || '';
        if (/^\d+[a-zA-Z\-]*$/.test(first) && second && !/^\d/.test(second)) {
            first = second + ' ' + first;
        }

        var postcode = '';
        var postcodeIndex = -1;
        var cityFromPostcodeToken = '';
        for (var i = 0; i < parts.length; i += 1) {
            var m = parts[i].match(/\b\d{4}\s?[A-Za-z]{2}\b/);
            if (m) {
                postcode = m[0].toUpperCase().replace(/\s+/, '');
                postcodeIndex = i;
                var rest = parts[i].replace(m[0], '').replace(/^[,\s-]+|[,\s-]+$/g, '').trim();
                if (rest && !/\d/.test(rest)) {
                    cityFromPostcodeToken = rest;
                }
                break;
            }
        }

        function normalized(v) {
            return String(v || '')
                .toLowerCase()
                .replace(/[0-9]/g, '')
                .replace(/\s+/g, '')
                .trim();
        }

        function isCountryOrProvince(token) {
            var t = String(token || '').toLowerCase().trim();
            if (!t) return true;
            var blocked = [
                'nederland', 'netherlands', 'the netherlands',
                'drenthe', 'flevoland', 'friesland', 'gelderland', 'groningen',
                'limburg', 'noord-brabant', 'noord holland', 'noord-holland',
                'overijssel', 'utrecht', 'zeeland', 'zuid holland', 'zuid-holland'
            ];
            return blocked.indexOf(t) !== -1;
        }

        var streetNorm = normalized(first);
        var city = '';
        if (cityFromPostcodeToken && isCityCandidate(cityFromPostcodeToken)) {
            city = cityFromPostcodeToken;
        }
        function isCityCandidate(token) {
            var t = String(token || '').trim();
            if (!t) return false;
            if (/\d/.test(t)) return false;
            if (isCountryOrProvince(t)) return false;
            if (normalized(t) === streetNorm) return false;
            return true;
        }

        if (!city && postcodeIndex >= 0) {
            for (var fwd = postcodeIndex + 1; fwd < parts.length; fwd += 1) {
                if (isCityCandidate(parts[fwd])) {
                    city = parts[fwd];
                    break;
                }
            }
            if (!city) {
                for (var back = postcodeIndex - 1; back >= 0; back -= 1) {
                    if (isCityCandidate(parts[back])) {
                        city = parts[back];
                        break;
                    }
                }
            }
        } else if (!city) {
            for (var j = 1; j < parts.length; j += 1) {
                if (isCityCandidate(parts[j])) {
                    city = parts[j];
                    break;
                }
            }
        }

        var out = first;
        if (postcode) out += ', ' + postcode;
        if (city) out += ', ' + city;
        return out || text;
    }

    function formatDateTimeNl(raw) {
        var text = String(raw || '').trim();
        if (!text) return '—';
        var parsed = new Date(text);
        if (!isNaN(parsed.getTime())) {
            var dd = String(parsed.getDate()).padStart(2, '0');
            var mm = String(parsed.getMonth() + 1).padStart(2, '0');
            var yyyy = String(parsed.getFullYear());
            var hh = String(parsed.getHours()).padStart(2, '0');
            var mi = String(parsed.getMinutes()).padStart(2, '0');
            return dd + '-' + mm + '-' + yyyy + ' ' + hh + ':' + mi;
        }
        return text;
    }

    function normalizeWhatsappPhone(raw) {
        var d = String(raw || '').replace(/[^0-9]/g, '');
        if (d.length === 0) {
            return '';
        }
        if (d.indexOf('06') === 0 && d.length === 10) {
            return '31' + d.substring(1);
        }
        if (d.charAt(0) === '0' && d.length === 10) {
            return '31' + d.substring(1);
        }
        if (/^31[1-9]\d{8}$/.test(d)) {
            return d;
        }
        if (d.indexOf('00') === 0 && d.length >= 12) {
            d = d.substring(2);
            if (/^31[1-9]\d{8}$/.test(d)) {
                return d;
            }
        }
        return d;
    }

    function getSelectedOffer() {
        return state.offers.find(function(offer) { return offer.id === state.selected_offer_id; }) || null;
    }

    function buildSelectedBaggageSummary() {
        var itemMap = {};
        (config.baggage_items || []).forEach(function(item) {
            if (!item || !item.key) return;
            itemMap[String(item.key)] = item.title || item.key;
        });
        (config.special_items || []).forEach(function(item) {
            if (!item || !item.key) return;
            itemMap[String(item.key)] = item.title || item.key;
        });

        var rows = [];
        Object.keys(state.baggage || {}).forEach(function(key) {
            var qty = parseInt(state.baggage[key] || 0, 10);
            if (qty > 0) rows.push((itemMap[key] || key) + ' x ' + qty);
        });
        Object.keys(state.special_baggage || {}).forEach(function(key) {
            var qty = parseInt(state.special_baggage[key] || 0, 10);
            if (qty > 0) rows.push((itemMap[key] || key) + ' x ' + qty);
        });

        return rows.length ? rows.join(', ') : 'Geen';
    }

    function buildWhatsappSummaryMessage(rideRequestId) {
        var selected = getSelectedOffer();
        var fullName = [state.first_name || '', state.last_name || ''].join(' ').trim();
        var stopovers = (state.stopovers || []).map(function(stop) { return compactAddress(stop) || stop; }).filter(Boolean);
        var lines = [
            'Nieuwe taxiboeking',
            'Naam: ' + (fullName || '—'),
            'Telefoon: ' + (state.phone || '—'),
            'E-mail: ' + (state.email || '—'),
            'Ophalen: ' + (compactAddress(state.pickup_address || '') || '—'),
            'Afzetten: ' + (compactAddress(state.dropoff_address || '') || '—'),
            'Datum/tijd: ' + formatDateTimeNl(state.pickup_at || ''),
            'Passagiers: ' + String(state.passengers || 1),
            'Bagage: ' + buildSelectedBaggageSummary()
        ];

        if (stopovers.length) {
            lines.splice(6, 0, 'Tussenstops: ' + stopovers.join(' -> '));
        }
        if (state.return_trip) {
            lines.push('Retour: ' + (state.return_at ? ('Ja (' + formatDateTimeNl(state.return_at) + ')') : 'Ja'));
        }
        if (selected) {
            lines.push('Aanbieding: ' + (selected.title || '—'));
            lines.push('Prijsindicatie: ' + formatEuro(selected.price || 0));
        }
        if (rideRequestId) {
            lines.push('Referentie: rit #' + String(rideRequestId));
        }
        if (state.remarks) {
            lines.push('Opmerking: ' + state.remarks);
        }
        return lines.join('\n');
    }

    function prepareWhatsappWindow() {
        if (!whatsappClickToChatEnabled) return;
        var phone = normalizeWhatsappPhone(whatsappClickToChatNumber);
        if (!phone) return;
        if (whatsappDraftWindow && !whatsappDraftWindow.closed) return;
        try {
            whatsappDraftWindow = window.open('about:blank', '_blank', 'noopener,noreferrer');
        } catch (e) {
            whatsappDraftWindow = null;
        }
    }

    function closePreparedWhatsappWindow() {
        if (whatsappDraftWindow && !whatsappDraftWindow.closed) {
            whatsappDraftWindow.close();
        }
        whatsappDraftWindow = null;
    }

    function openWhatsappWithSummary(rideRequestId) {
        if (!whatsappClickToChatEnabled) return;
        var phone = normalizeWhatsappPhone(whatsappClickToChatNumber);
        if (!phone) return;
        var url = 'https://wa.me/' + encodeURIComponent(phone) + '?text=' + encodeURIComponent(buildWhatsappSummaryMessage(rideRequestId));
        if (whatsappDraftWindow && !whatsappDraftWindow.closed) {
            whatsappDraftWindow.location.href = url;
            whatsappDraftWindow = null;
            return;
        }
        var popup = window.open(url, '_blank', 'noopener,noreferrer');
        if (!popup) {
            window.location.href = url;
        }
    }

    function clearAllFieldErrors() {
        root.querySelectorAll('[data-booking-field-error]').forEach(function(errEl) {
            errEl.textContent = '';
            errEl.classList.add('hidden');
        });
        root.querySelectorAll('.booking-field-input--error').forEach(function(el) {
            el.classList.remove('booking-field-input--error');
        });
    }

    function clearFieldErrorFor(fieldKey) {
        if (!fieldKey) return;
        var input = root.querySelector('[data-field="' + fieldKey + '"]');
        var errEl = root.querySelector('[data-booking-field-error="' + fieldKey + '"]');
        if (input) input.classList.remove('booking-field-input--error');
        if (errEl) {
            errEl.textContent = '';
            errEl.classList.add('hidden');
        }
    }

    function setFieldError(fieldKey, message) {
        var input = root.querySelector('[data-field="' + fieldKey + '"]');
        var errEl = root.querySelector('[data-booking-field-error="' + fieldKey + '"]');
        if (input) input.classList.add('booking-field-input--error');
        if (errEl) {
            errEl.textContent = message || '';
            errEl.classList.toggle('hidden', !message);
        }
    }

    function showError(message) {
        clearAllFieldErrors();
        var el = root.querySelector('[data-booking-error]');
        if (!el) return;
        el.textContent = message || 'Er ging iets mis.';
        el.classList.remove('hidden');
    }

    function clearError() {
        clearAllFieldErrors();
        var el = root.querySelector('[data-booking-error]');
        if (!el) return;
        el.classList.add('hidden');
        el.textContent = '';
    }

    function showSuccess(message) {
        var el = root.querySelector('[data-booking-success]');
        var modal = root.querySelector('[data-booking-success-modal]');
        var modalMessage = root.querySelector('[data-booking-success-modal-message]');
        var text = message || 'Gelukt.';
        if (el) {
            el.textContent = text;
            el.classList.add('hidden');
        }
        if (modal) {
            if (modalMessage) modalMessage.textContent = text;
            modal.classList.remove('hidden');
            document.documentElement.classList.add('booking-modal-open');
            document.body.classList.add('booking-modal-open');
        }
    }

    function closeSuccessModal() {
        var modal = root.querySelector('[data-booking-success-modal]');
        if (!modal) return;
        modal.classList.add('hidden');
        document.documentElement.classList.remove('booking-modal-open');
        document.body.classList.remove('booking-modal-open');
    }

    function getCurrentStepKey() {
        return stepOrder[Math.max(0, state.step - 1)] || 'trip';
    }

    function getStepIndexForKey(stepKey) {
        var idx = stepOrder.indexOf(stepKey);
        return idx < 0 ? 0 : idx + 1;
    }

    function isStepReachable(stepKey) {
        var stepIndex = getStepIndexForKey(stepKey);
        if (stepIndex < 1) {
            return false;
        }
        if (stepKey === 'baggage' && !state.has_baggage) {
            return false;
        }

        return stepIndex <= state.maxStep;
    }

    function updateBookingStepSelectOptions() {
        var stepSelect = root.querySelector('[data-booking-step-select]');
        if (!stepSelect) {
            return;
        }
        var currentStepKey = getCurrentStepKey();
        Array.prototype.forEach.call(stepSelect.options, function(opt) {
            var key = opt.value || '';
            if (key === 'baggage' && !state.has_baggage) {
                opt.hidden = true;
                opt.disabled = true;

                return;
            }
            opt.hidden = false;
            opt.disabled = !isStepReachable(key);
        });
        stepSelect.value = currentStepKey;
    }

    function getBookingScrollOffset() {
        var offset = 12;
        var headers = document.querySelectorAll('header.sticky, header.fixed, .preview-bar.sticky');
        headers.forEach(function(header) {
            if (!header || typeof header.getBoundingClientRect !== 'function') return;
            var h = header.getBoundingClientRect().height;
            if (h > 0) offset += Math.ceil(h);
        });
        return offset;
    }

    function scrollConfiguratorIntoView() {
        var anchor = root.querySelector('.booking-module-card') || root;
        if (!anchor || typeof anchor.getBoundingClientRect !== 'function') return;
        var top = window.scrollY + anchor.getBoundingClientRect().top - getBookingScrollOffset();
        if (top < 0) top = 0;
        window.scrollTo({ top: top, behavior: 'smooth' });
    }

    function setStepByKey(stepKey, options) {
        var idx = stepOrder.indexOf(stepKey);
        if (idx === -1) return;
        setStep(idx + 1, options);
    }

    function getNextStepKey(currentStepKey) {
        var idx = stepOrder.indexOf(currentStepKey);
        if (idx === -1) return null;
        for (var i = idx + 1; i < stepOrder.length; i += 1) {
            var candidate = stepOrder[i];
            if (candidate === 'baggage' && !state.has_baggage) continue;
            return candidate;
        }
        return null;
    }

    function getPrevStepKey(currentStepKey) {
        var idx = stepOrder.indexOf(currentStepKey);
        if (idx === -1) return null;
        for (var i = idx - 1; i >= 0; i -= 1) {
            var candidate = stepOrder[i];
            if (candidate === 'baggage' && !state.has_baggage) continue;
            return candidate;
        }
        return null;
    }

    function updateBaggageStepAvailability() {
        var baggageTab = root.querySelector('.booking-step-tab[data-step-key="baggage"]');
        if (baggageTab) {
            baggageTab.classList.toggle('hidden', !state.has_baggage);
        }
        updateBookingStepSelectOptions();
        if (!state.has_baggage && getCurrentStepKey() === 'baggage') {
            setStepByKey('offers');
        }
    }

    function syncBaggageChoiceFromUi() {
        if (skipBaggageStep) {
            state.has_baggage = false;
            updateBaggageStepAvailability();
            return;
        }
        var selected = root.querySelector('input[name="booking_has_baggage_ui"]:checked');
        state.has_baggage = selected ? selected.value === 'yes' : true;
        updateBaggageStepAvailability();
    }

    function showRouteDetailsLoading() {
        var banner = root.querySelector('[data-route-details-banner]');
        var loading = root.querySelector('[data-route-details-loading]');
        var stats = root.querySelector('[data-route-details-stats]');
        if (!banner || !loading) return;
        banner.classList.remove('hidden');
        banner.setAttribute('aria-busy', 'true');
        loading.classList.remove('hidden');
        loading.classList.add('flex');
        if (stats) stats.classList.add('hidden');
    }

    function renderRouteDetailsStats(kmStr, minStr) {
        var banner = root.querySelector('[data-route-details-banner]');
        var loading = root.querySelector('[data-route-details-loading]');
        var stats = root.querySelector('[data-route-details-stats]');
        var kmEl = root.querySelector('[data-route-details-km]');
        var minEl = root.querySelector('[data-route-details-min]');
        if (loading) {
            loading.classList.add('hidden');
            loading.classList.remove('flex');
        }
        if (kmEl) kmEl.textContent = String(kmStr) + ' km';
        if (minEl) minEl.textContent = '± ' + String(minStr) + ' min';
        if (stats) stats.classList.remove('hidden');
        if (banner) {
            banner.classList.remove('hidden');
            banner.setAttribute('aria-busy', 'false');
        }
    }

    function renderRouteDetailsText(text) {
        var banner = root.querySelector('[data-route-details-banner]');
        var loading = root.querySelector('[data-route-details-loading]');
        var stats = root.querySelector('[data-route-details-stats]');
        var kmEl = root.querySelector('[data-route-details-km]');
        var minEl = root.querySelector('[data-route-details-min]');
        if (!banner) return;
        var normalized = String(text || '').trim();
        if (loading) {
            loading.classList.add('hidden');
            loading.classList.remove('flex');
        }
        if (stats) stats.classList.add('hidden');
        if (kmEl) kmEl.textContent = '—';
        if (minEl) minEl.textContent = '—';
        banner.classList.toggle('hidden', normalized === '');
        banner.setAttribute('aria-busy', 'false');
    }

    function setStep(nextStep, options) {
        options = options || {};
        state.step = Math.max(1, Math.min(stepOrder.length, nextStep));
        if (typeof root._hideAllBookingSuggestionPanels === 'function') {
            root._hideAllBookingSuggestionPanels();
        }
        var currentStepKey = getCurrentStepKey();
        root.querySelectorAll('[data-step-panel]').forEach(function(panel) {
            panel.classList.toggle('hidden', panel.getAttribute('data-step-panel') !== currentStepKey);
        });
        root.querySelectorAll('.booking-step-tab').forEach(function(tab) {
            var tabStepIndex = parseInt(tab.getAttribute('data-step-index'), 10);
            var active = tabStepIndex === state.step;
            var reachable = tabStepIndex <= state.maxStep;
            tab.classList.toggle('font-semibold', active);
            tab.classList.toggle('text-heading', active);
            tab.classList.toggle('text-fg-brand', active);
            tab.classList.toggle('active', active);
            tab.classList.toggle('font-medium', !active);
            tab.classList.toggle('text-body', !active);
            tab.classList.toggle('booking-step-tab--reachable', reachable);
            tab.classList.toggle('hover:text-fg-brand', reachable);
            tab.classList.toggle('transition-colors', reachable);
            tab.style.backgroundColor = 'transparent';
            tab.style.cursor = reachable ? 'pointer' : 'default';
            tab.setAttribute('tabindex', reachable ? '0' : '-1');
            if (active) {
                tab.style.borderBottomColor = activeTabColor;
                tab.style.borderColor = activeTabColor;
                tab.style.color = activeTabColor;
            } else {
                tab.style.borderBottomColor = 'transparent';
                tab.style.borderColor = 'transparent';
                tab.style.color = '';
            }
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
            tab.setAttribute('aria-disabled', reachable ? 'false' : 'true');
        });
        updateBookingStepSelectOptions();
        var nextBtn = root.querySelector('[data-booking-next]');
        if (nextBtn) {
            nextBtn.textContent = currentStepKey === 'confirm'
                ? (config.texts && config.texts.submit_button_text ? config.texts.submit_button_text : 'Boeking versturen')
                : 'Verder';
            if (currentStepKey === 'confirm') {
                nextBtn.classList.add('booking-next--final');
                nextBtn.classList.remove('booking-next-default');
                nextBtn.style.borderColor = '';
                nextBtn.style.color = '';
            } else {
                nextBtn.classList.remove('booking-next--final');
                nextBtn.classList.add('booking-next-default');
                nextBtn.style.borderColor = 'color-mix(in srgb, ' + bookingPrimaryHex + ' 45%, transparent)';
                nextBtn.style.color = bookingPrimaryHex;
            }
        }
        var prevBtn = root.querySelector('[data-booking-prev]');
        if (prevBtn) prevBtn.style.visibility = state.step === 1 ? 'hidden' : 'visible';
        if (currentStepKey === 'trip') {
            window.requestAnimationFrame(function() {
                syncRouteIconAlignment();
                refreshPickupDatetimeMin();
                syncPickupDatetimeFutureValidation();
            });
        }
        if (!options.skipScroll) {
            window.requestAnimationFrame(function() {
                scrollConfiguratorIntoView();
            });
        }
    }

    function updateQty(target, delta, max) {
        var chunks = (target || '').split('.');
        if (chunks.length !== 2) return;
        var bag = chunks[0];
        var key = chunks[1];
        if (!state[bag]) state[bag] = {};
        var current = parseInt(state[bag][key] || 0, 10);
        var next = current + delta;
        if (next < 0) next = 0;
        if (typeof max === 'number' && max >= 0) next = Math.min(max, next);
        state[bag][key] = next;
        var display = root.querySelector('[data-qty-display="' + target + '"]');
        if (display) display.textContent = String(next);
    }

    function syncStateFromFields() {
        root.querySelectorAll('[data-field]').forEach(function(field) {
            var key = field.getAttribute('data-field');
            if (!key) return;
            if (field.type === 'checkbox') {
                state[key] = !!field.checked;
            } else {
                state[key] = field.value || '';
            }
        });
        state.stopovers = Array.from(root.querySelectorAll('[data-stopover-input]'))
            .map(function(input) { return (input.value || '').trim(); })
            .filter(function(v) { return v !== ''; });
        var passengerDisplay = root.querySelector('[data-passengers-display]');
        if (passengerDisplay) passengerDisplay.textContent = String(state.passengers);
        var summaryPassengers = root.querySelector('[data-summary-passengers]');
        if (summaryPassengers) summaryPassengers.textContent = String(state.passengers);
        var summaryConfirmPassengers = root.querySelector('[data-summary-confirm-passengers]');
        if (summaryConfirmPassengers) {
            var pcc = parseInt(state.passengers || 1, 10);
            summaryConfirmPassengers.textContent = pcc + ' ' + (pcc === 1 ? 'passagier' : 'passagiers');
        }
        syncStopoverHint();
        syncReturnTripUi();
        syncDateTimePlaceholder();
        syncBaggageChoiceFromUi();
        syncPickupDatetimeFutureValidation();
    }

    function formatDateTimeLocalValueFromDate(d) {
        var pad = function(n) { return n < 10 ? '0' + n : '' + n; };
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }

    function parseLocalDateTimeMs(str) {
        if (!str || typeof str !== 'string') return NaN;
        var m = str.match(/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})$/);
        if (!m) return NaN;
        return new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]), Number(m[4]), Number(m[5]), 0, 0).getTime();
    }

    function refreshPickupDatetimeMin() {
        var input = root.querySelector('[data-field="pickup_at"]');
        if (!input || input.disabled) return;
        input.setAttribute('min', formatDateTimeLocalValueFromDate(new Date()));
    }

    function syncPickupDatetimeFutureValidation() {
        var input = root.querySelector('[data-field="pickup_at"]');
        var errEl = root.querySelector('[data-pickup-datetime-future-error]');
        var missingPickErr = root.querySelector('[data-booking-field-error="pickup_at"]');
        if (!input || !errEl) return;
        var v = String(input.value || '').trim();
        if (!v) {
            errEl.classList.add('hidden');
            input.classList.remove('booking-datetime-input--past-invalid');
            return;
        }
        var t = parseLocalDateTimeMs(v);
        if (isNaN(t)) {
            errEl.classList.add('hidden');
            input.classList.remove('booking-datetime-input--past-invalid');
            return;
        }
        if (t < Date.now()) {
            errEl.classList.remove('hidden');
            input.classList.add('booking-datetime-input--past-invalid');
            if (missingPickErr) {
                missingPickErr.textContent = '';
                missingPickErr.classList.add('hidden');
            }
            input.classList.remove('booking-field-input--error');
        } else {
            errEl.classList.add('hidden');
            input.classList.remove('booking-datetime-input--past-invalid');
        }
    }

    function syncStopoverHint() {
        var list = root.querySelector('[data-stopovers-list]');
        var hint = root.querySelector('[data-stopover-text]');
        if (!list) return;
        var hasStops = list.children.length > 0;
        if (hint) {
            hint.classList.toggle('hidden', hasStops);
        }
        renderRouteIcons(list.children.length);
    }

    function getStopoverCount() {
        var list = root.querySelector('[data-stopovers-list]');
        return list ? list.children.length : 0;
    }

    function canAddStopover() {
        return getStopoverCount() < state.maxStopovers;
    }

    function syncRouteIconAlignment() {
        var wrap = root.querySelector('.booking-route-wrap');
        var iconsRoot = wrap ? wrap.querySelector('[data-route-icons-list]') : null;
        var fieldsRoot = wrap ? wrap.querySelector('.booking-route-fields') : null;
        if (!wrap || !iconsRoot || !fieldsRoot) return;

        var orderedFieldRows = [];
        var pickupField = fieldsRoot.querySelector('[data-route-row="pickup"]');
        var middleField = fieldsRoot.querySelector('[data-route-row="middle"]');
        var stopFields = Array.from(fieldsRoot.querySelectorAll('[data-route-row="stopover"]'));
        var dropoffField = fieldsRoot.querySelector('[data-route-row="dropoff"]');

        if (pickupField) orderedFieldRows.push(pickupField);
        if (middleField) orderedFieldRows.push(middleField);
        orderedFieldRows = orderedFieldRows.concat(stopFields);
        if (dropoffField) orderedFieldRows.push(dropoffField);

        var iconRows = Array.from(iconsRoot.querySelectorAll('.booking-route-icon-row'));
        var connectors = Array.from(iconsRoot.querySelectorAll('.booking-route-icon-connector'));
        if (!iconRows.length || !orderedFieldRows.length) return;

        var baseRect = iconsRoot.getBoundingClientRect();
        var centers = [];

        orderedFieldRows.forEach(function(fieldRow, index) {
            var iconRow = iconRows[index];
            if (!iconRow) return;
            var alignEl = fieldRow.querySelector('[data-route-icon-align-target]') || fieldRow;
            var fieldRect = alignEl.getBoundingClientRect();
            var center = (fieldRect.top - baseRect.top) + (fieldRect.height / 2);
            centers.push(center);
            iconRow.style.top = Math.max(0, center - 12).toFixed(2) + 'px';
        });

        for (var i = 0; i < connectors.length; i += 1) {
            var fromCenter = centers[i];
            var toCenter = centers[i + 1];
            var connector = connectors[i];
            if (typeof fromCenter !== 'number' || typeof toCenter !== 'number') continue;
            var top = fromCenter + 12 + 6;
            var bottom = toCenter - 12 - 6;
            var height = Math.max(8, bottom - top);
            connector.style.top = top.toFixed(2) + 'px';
            connector.style.height = height.toFixed(2) + 'px';
        }

        iconsRoot.style.height = Math.max(fieldsRoot.offsetHeight, 24) + 'px';
    }

    function renderRouteIcons(stopCount) {
        var iconsRoot = root.querySelector('[data-route-icons-list]');
        if (!iconsRoot) return;
        var allowAddStopover = canAddStopover();
        var rows = ['pickup', 'add'];
        for (var i = 0; i < stopCount; i += 1) rows.push('stop');
        rows.push('dropoff');

        var html = '';
        var stopIndex = 0;
        rows.forEach(function(type, idx) {
            var rowClass = type === 'add' ? 'booking-route-icon-row booking-route-icon-row--middle' : 'booking-route-icon-row booking-route-icon-row--field';
            if (type === 'pickup') {
                html += '<div class="' + rowClass + '" data-route-icon-row="pickup">';
            } else if (type === 'dropoff') {
                html += '<div class="' + rowClass + '" data-route-icon-row="dropoff">';
            } else if (type === 'stop') {
                html += '<div class="' + rowClass + '" data-route-icon-row="stopover" data-route-icon-index="' + stopIndex + '">';
                stopIndex += 1;
            } else if (type === 'add') {
                html += '<div class="' + rowClass + '" data-route-icon-row="middle">';
            } else {
                html += '<div class="' + rowClass + '">';
            }
            if (type === 'pickup') {
                html += '<svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="7" stroke="currentColor" stroke-width="2.5"/></svg>';
            } else if (type === 'add') {
                var addBtnStateClass = allowAddStopover ? '' : ' opacity-40 cursor-not-allowed';
                var addBtnDisabledAttr = allowAddStopover ? '' : ' disabled aria-disabled="true"';
                html += '<button type="button" class="booking-stopover-toggle inline-flex items-center justify-center w-6 h-6 rounded-full border border-current bg-transparent' + addBtnStateClass + '" aria-label="Tussenstop toevoegen"' + addBtnDisabledAttr + '><svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 5v14m-7-7h14"/></svg></button>';
            } else if (type === 'stop') {
                html += '<svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="7" stroke="currentColor" stroke-width="2.5"/></svg>';
            } else {
                html += '<svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a7 7 0 0 0-7 7c0 5.4 7 13 7 13s7-7.6 7-13a7 7 0 0 0-7-7Zm0 10a3 3 0 1 1 0-6 3 3 0 0 1 0 6Z"/></svg>';
            }
            html += '</div>';
            if (idx < rows.length - 1) {
                html += '<span class="booking-route-icon-connector"></span>';
            }
        });
        iconsRoot.innerHTML = html;
        syncRouteIconAlignment();
    }

    function createStopoverRow(value) {
        var row = document.createElement('div');
        row.className = 'relative booking-route-field-row booking-stopover-row';
        row.setAttribute('data-route-row', 'stopover');
        row.innerHTML =
            '<span class="absolute left-5 top-1/2 -translate-y-1/2 text-fg-brand text-base font-semibold leading-none">stop</span>' +
            '<input type="text" style="padding-left: 70px;" class="booking-route-input-short bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-lg focus:ring-brand focus:border-brand block w-full pe-10 py-3.5 shadow-xs placeholder:text-body" data-stopover-input placeholder="tussenstop adres">' +
            '<button type="button" class="booking-stopover-remove absolute right-2 top-1/2 -translate-y-1/2 inline-flex items-center justify-center w-7 h-7 rounded-md text-fg-brand hover:bg-neutral-secondary-soft" aria-label="Verwijder tussenstop">×</button>';
        var input = row.querySelector('[data-stopover-input]');
        if (input) input.value = value || '';
        return row;
    }

    function addStopover(initialValue) {
        var list = root.querySelector('[data-stopovers-list]');
        if (!list || !canAddStopover()) return false;
        list.appendChild(createStopoverRow(initialValue || ''));
        syncStateFromFields();
        setupStopoverAutocompletes();
        return true;
    }

    function applyStateToFields() {
        root.querySelectorAll('[data-field]').forEach(function(field) {
            var key = field.getAttribute('data-field');
            if (!key || state[key] === undefined) return;
            if (field.type === 'checkbox') {
                field.checked = !!state[key];
            } else {
                field.value = state[key] != null ? String(state[key]) : '';
            }
        });
        var stopList = root.querySelector('[data-stopovers-list]');
        if (stopList) {
            stopList.innerHTML = '';
            (state.stopovers || []).forEach(function(s) {
                stopList.appendChild(createStopoverRow(s));
            });
        }
        var passengerDisplay = root.querySelector('[data-passengers-display]');
        if (passengerDisplay) passengerDisplay.textContent = String(state.passengers);
        var summaryPassengers = root.querySelector('[data-summary-passengers]');
        if (summaryPassengers) summaryPassengers.textContent = String(state.passengers);
        root.querySelectorAll('[data-qty-display]').forEach(function(el) {
            var target = el.getAttribute('data-qty-display');
            if (!target) return;
            var chunks = target.split('.');
            if (chunks.length !== 2) return;
            var bag = chunks[0];
            var key = chunks[1];
            var qty = 0;
            if (bag === 'baggage' && state.baggage) {
                qty = parseInt(state.baggage[key] || 0, 10);
            } else if (bag === 'special_baggage' && state.special_baggage) {
                qty = parseInt(state.special_baggage[key] || 0, 10);
            }
            el.textContent = String(qty);
        });
        if (!skipBaggageStep) {
            var yn = state.has_baggage ? 'yes' : 'no';
            root.querySelectorAll('input[name="booking_has_baggage_ui"]').forEach(function(r) {
                r.checked = r.value === yn;
            });
        }
        syncStopoverHint();
        syncReturnTripUi();
        syncDateTimePlaceholder();
        syncBaggageChoiceFromUi();
        syncPickupDatetimeFutureValidation();
        if (root._bindAllRouteAddressInputs) root._bindAllRouteAddressInputs();
        setupStopoverAutocompletes();
    }

    function setupStopoverAutocompletes() {
        /* Stopover-velden gebruiken dezelfde snelle typeahead als pickup/dropoff (setupAddressTypeaheadFallback).
           Bij nieuwe tussenstop de typeahead opnieuw binden via root._bindAllRouteAddressInputs(). */
        if (root._bindAllRouteAddressInputs) {
            root._bindAllRouteAddressInputs();
        }
    }

    function syncReturnTripUi() {
        var returnTripEnabled = !!state.return_trip;
        var returnInput = root.querySelector('[data-field="return_at"]');
        var returnPlaceholder = root.querySelector('[data-datetime-placeholder-for="return_at"]');
        var returnWrap = root.querySelector('.booking-return-datetime-wrap');
        if (!returnInput) return;
        returnInput.disabled = !returnTripEnabled;
        if (returnWrap) {
            returnWrap.classList.toggle('booking-return-readonly', !returnTripEnabled);
        }
        if (!returnTripEnabled) {
            returnInput.value = '';
            state.return_at = '';
            clearFieldErrorFor('return_at');
            if (returnPlaceholder) {
                returnPlaceholder.textContent = returnInput.getAttribute('placeholder') || 'Selecteer datum en tijd';
            }
        }
    }

    function syncDateTimePlaceholder() {
        root.querySelectorAll('[data-datetime-input]').forEach(function(input) {
            var targetKey = input.getAttribute('data-placeholder-target');
            if (!targetKey) return;
            var placeholder = root.querySelector('[data-datetime-placeholder-for="' + targetKey + '"]');
            if (placeholder) {
                var inputValue = String(input.value || '').trim();
                if (!inputValue) {
                    placeholder.textContent = input.getAttribute('placeholder') || 'Selecteer datum en tijd';
                } else {
                    var parsed = new Date(inputValue);
                    if (!isNaN(parsed.getTime())) {
                        placeholder.textContent = parsed.toLocaleString('nl-NL', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    } else {
                        placeholder.textContent = inputValue;
                    }
                }
            }
        });
    }

    function offersForDisplayMode() {
        var mode = state.offer_display_mode === 'person_range' ? 'person_range' : 'vehicle';
        return (state.offers || []).filter(function(offer) {
            var id = String(offer && offer.id ? offer.id : '');
            var isPersonRangeCard = id.indexOf('person_range_') === 0;
            return mode === 'person_range' ? isPersonRangeCard : !isPersonRangeCard;
        });
    }

    function renderOffers() {
        var list = root.querySelector('[data-offers-list]');
        var empty = root.querySelector('[data-offers-empty]');
        if (!list) return;
        list.innerHTML = '';
        var visibleOffers = offersForDisplayMode();
        if (!visibleOffers.length) {
            if (empty) empty.classList.remove('hidden');
            return;
        }
        if (empty) empty.classList.add('hidden');
        visibleOffers.forEach(function(offer) {
            var active = state.selected_offer_id === offer.id;
            var card = document.createElement('div');
            card.className = 'booking-offer-card rounded-xl border-2 border-solid p-5 md:p-6 flex flex-col md:flex-row gap-4 items-center justify-between transition-all duration-200 bg-neutral-primary cursor-pointer ' + (active ? 'border-[#0cea36] shadow-lg ring-2 ring-[#0cea36]/50' : 'border-slate-300/60 dark:border-slate-600 shadow-xs');
            card.setAttribute('role', 'button');
            card.setAttribute('tabindex', '0');
            card.setAttribute('aria-pressed', active ? 'true' : 'false');
            card.setAttribute('data-offer-id', offer.id || '');
            var badge = state.offer_display_mode === 'person_range' ? '' : (offer.badge || '');
            card.innerHTML =
                '<div class="flex items-center gap-4 w-full md:w-auto">' +
                    (offer.image_url ? '<img src="' + offer.image_url + '" alt="' + (offer.title || '') + '" class="h-24 w-40 object-cover rounded-lg">' : '') +
                    '<div>' +
                        (badge ? '<div class="inline-flex mb-1 items-center rounded-full border border-slate-300/80 dark:border-slate-500/60 px-2.5 py-1 text-xs font-semibold text-slate-700 dark:text-slate-200">' + badge + '</div>' : '') +
                        '<div class="text-2xl font-bold text-slate-900 dark:text-slate-100">' + (offer.title || '') + '</div>' +
                        '<ul class="text-sm text-slate-600 dark:text-slate-300">' + ((offer.features || []).map(function(f) { return '<li>✓ ' + f + '</li>'; }).join('')) + '</ul>' +
                    '</div>' +
                '</div>' +
                '<div class="text-right w-full md:w-auto">' +
                    (offer.old_price ? '<div class="text-sm font-medium text-slate-500 dark:text-slate-400">van <span style="text-decoration: line-through; text-decoration-thickness: 2px; text-decoration-color: rgb(244 63 94 / 0.8);">' + formatEuro(offer.old_price) + '</span></div>' : '') +
                    '<div class="booking-offer-price text-4xl font-bold tabular-nums text-heading">' + formatEuro(offer.price) + '</div>' +
                '</div>';
            list.appendChild(card);
        });
    }

    function buildSummaryStaticMapUrl(encodedPolyline, pickupAddr, dropoffAddr, pickupLat, pickupLng, dropoffLat, dropoffLng) {
        if (!encodedPolyline || !mapsApiKey) return '';
        var pathParam = 'weight:8|color:0x1D4ED8|enc:' + encodedPolyline;
        var base = 'https://maps.googleapis.com/maps/api/staticmap?size=640x400&scale=2&maptype=roadmap&format=png&path='
            + encodeURIComponent(pathParam);

        function appendMarker(label, addr, lat, lng) {
            var style = 'size:mid|color:red|label:' + label;
            var loc = '';
            if (lat != null && lng != null && isFinite(Number(lat)) && isFinite(Number(lng))) {
                loc = Number(lat) + ',' + Number(lng);
            } else if (addr && String(addr).trim() !== '') {
                loc = String(addr).trim();
            }
            if (!loc) return;
            base += '&markers=' + encodeURIComponent(style + '|' + loc);
        }
        appendMarker('A', pickupAddr, pickupLat, pickupLng);
        appendMarker('B', dropoffAddr, dropoffLat, dropoffLng);

        /* Geen zoom: Static Maps past viewport automatisch aan path + markers (vaste zoom knipt lange routes af). */
        base += '&key=' + encodeURIComponent(mapsApiKey);
        return base;
    }

    function updateSummaryRouteMap() {
        var pickup = String(state.pickup_address || '').trim();
        var dropoff = String(state.dropoff_address || '').trim();
        var stops = (state.stopovers || []).filter(function(s) { return String(s || '').trim() !== ''; });

        var mapSlices = [
            {
                staticEl: root.querySelector('[data-summary-route-map-static]'),
                iframe: root.querySelector('[data-summary-route-map-iframe]'),
                blockerEl: root.querySelector('[data-summary-route-map-blocker]'),
                emptyEl: root.querySelector('[data-summary-route-map-empty]'),
                fallbackEl: root.querySelector('[data-summary-route-map-fallback]'),
                linkEl: root.querySelector('[data-summary-route-map-link]'),
                loadingEl: root.querySelector('[data-summary-route-map-loading]')
            },
            {
                staticEl: root.querySelector('[data-trip-route-map-static]'),
                iframe: root.querySelector('[data-trip-route-map-iframe]'),
                blockerEl: root.querySelector('[data-trip-route-map-blocker]'),
                emptyEl: root.querySelector('[data-trip-route-map-empty]'),
                fallbackEl: root.querySelector('[data-trip-route-map-fallback]'),
                linkEl: root.querySelector('[data-trip-route-map-link]'),
                loadingEl: root.querySelector('[data-trip-route-map-loading]')
            }
        ];

        function hideMapLoadingSlice(s) {
            if (!s || !s.loadingEl) return;
            s.loadingEl.classList.add('hidden');
            s.loadingEl.classList.remove('flex');
            s.loadingEl.setAttribute('aria-hidden', 'true');
        }

        function showMapLoadingSlice(s) {
            if (!s || !s.loadingEl) return;
            if (s.staticEl) {
                s.staticEl.removeAttribute('src');
                s.staticEl.classList.add('hidden');
            }
            if (s.iframe) {
                s.iframe.removeAttribute('src');
                s.iframe.classList.add('hidden');
            }
            if (s.emptyEl) s.emptyEl.classList.add('hidden');
            if (s.fallbackEl) {
                s.fallbackEl.classList.add('hidden');
                s.fallbackEl.classList.remove('flex');
            }
            if (s.blockerEl) s.blockerEl.classList.add('hidden');
            s.loadingEl.classList.remove('hidden');
            s.loadingEl.classList.add('flex');
            s.loadingEl.setAttribute('aria-hidden', 'false');
        }

        function showEmptySlice(s) {
            if (!s || !s.emptyEl) return;
            if (s._staticMapLoadTimer) {
                clearTimeout(s._staticMapLoadTimer);
                s._staticMapLoadTimer = null;
            }
            hideMapLoadingSlice(s);
            if (s.staticEl) {
                s.staticEl.removeAttribute('src');
                s.staticEl.classList.add('hidden');
            }
            if (s.blockerEl) s.blockerEl.classList.add('hidden');
            if (s.iframe) {
                s.iframe.removeAttribute('src');
                s.iframe.classList.add('hidden');
            }
            s.emptyEl.classList.remove('hidden');
            if (s.fallbackEl) {
                s.fallbackEl.classList.add('hidden');
                s.fallbackEl.classList.remove('flex');
            }
        }

        function showFallbackSlice(s, dirUrl) {
            if (!s) return;
            if (s._staticMapLoadTimer) {
                clearTimeout(s._staticMapLoadTimer);
                s._staticMapLoadTimer = null;
            }
            hideMapLoadingSlice(s);
            if (s.staticEl) {
                s.staticEl.removeAttribute('src');
                s.staticEl.classList.add('hidden');
            }
            if (s.blockerEl) s.blockerEl.classList.add('hidden');
            if (s.iframe) {
                s.iframe.removeAttribute('src');
                s.iframe.classList.add('hidden');
            }
            if (s.emptyEl) s.emptyEl.classList.add('hidden');
            if (s.fallbackEl) {
                s.fallbackEl.classList.remove('hidden');
                s.fallbackEl.classList.add('flex');
            }
            if (s.linkEl && dirUrl) s.linkEl.href = dirUrl;
        }

        function showEmbedSlice(s, dirUrl, embedUrl) {
            if (!s) return;
            if (s._staticMapLoadTimer) {
                clearTimeout(s._staticMapLoadTimer);
                s._staticMapLoadTimer = null;
            }
            if (s.staticEl) {
                s.staticEl.removeAttribute('src');
                s.staticEl.classList.add('hidden');
            }
            if (s.emptyEl) s.emptyEl.classList.add('hidden');
            if (s.fallbackEl) {
                s.fallbackEl.classList.add('hidden');
                s.fallbackEl.classList.remove('flex');
            }
            if (s.linkEl && dirUrl) s.linkEl.href = dirUrl;
            showMapLoadingSlice(s);
            if (s.iframe) {
                var embedTimer = setTimeout(function() {
                    hideMapLoadingSlice(s);
                }, 12000);
                s.iframe.onload = function() {
                    clearTimeout(embedTimer);
                    hideMapLoadingSlice(s);
                    s.iframe.onload = null;
                };
                s.iframe.src = embedUrl;
                s.iframe.classList.remove('hidden');
            }
            if (s.blockerEl) s.blockerEl.classList.remove('hidden');
        }

        function showStaticMapSlice(s, dirUrl, imageUrl, embedFallbackUrl) {
            if (!s) return;
            if (s.iframe) {
                s.iframe.removeAttribute('src');
                s.iframe.classList.add('hidden');
            }
            if (s.blockerEl) s.blockerEl.classList.add('hidden');
            if (s.emptyEl) s.emptyEl.classList.add('hidden');
            if (s.fallbackEl) {
                s.fallbackEl.classList.add('hidden');
                s.fallbackEl.classList.remove('flex');
            }
            if (s.linkEl && dirUrl) s.linkEl.href = dirUrl;
            if (s.staticEl && imageUrl) {
                var embedFb = embedFallbackUrl || '';
                if (s._staticMapLoadTimer) {
                    clearTimeout(s._staticMapLoadTimer);
                    s._staticMapLoadTimer = null;
                }
                showMapLoadingSlice(s);
                s.staticEl.classList.add('hidden');
                s.staticEl.alt = 'Route van ' + pickup + ' naar ' + dropoff;
                function finishStaticOk() {
                    if (s._staticMapLoadTimer) {
                        clearTimeout(s._staticMapLoadTimer);
                        s._staticMapLoadTimer = null;
                    }
                    hideMapLoadingSlice(s);
                    s.staticEl.classList.remove('hidden');
                    s.staticEl.onload = null;
                    s.staticEl.onerror = null;
                }
                s.staticEl.onload = finishStaticOk;
                s.staticEl.onerror = function() {
                    if (s._staticMapLoadTimer) {
                        clearTimeout(s._staticMapLoadTimer);
                        s._staticMapLoadTimer = null;
                    }
                    s.staticEl.onerror = null;
                    s.staticEl.onload = null;
                    if (embedFb && s.iframe) {
                        showEmbedSlice(s, dirUrl, embedFb);
                    } else {
                        hideMapLoadingSlice(s);
                        showFallbackSlice(s, dirUrl);
                    }
                };
                s.staticEl.src = imageUrl;
                /* lazy + verborgen img laadt soms niet; eager + fallback na timeout */
                s._staticMapLoadTimer = setTimeout(function() {
                    s._staticMapLoadTimer = null;
                    s.staticEl.onload = null;
                    s.staticEl.onerror = null;
                    if (s.staticEl.naturalWidth > 0) {
                        hideMapLoadingSlice(s);
                        s.staticEl.classList.remove('hidden');
                    } else if (embedFb && s.iframe) {
                        showEmbedSlice(s, dirUrl, embedFb);
                    } else {
                        hideMapLoadingSlice(s);
                        showFallbackSlice(s, dirUrl);
                    }
                }, 15000);
                requestAnimationFrame(function() {
                    if (s.staticEl.complete && s.staticEl.naturalWidth > 0) {
                        finishStaticOk();
                        if (s._staticMapLoadTimer) {
                            clearTimeout(s._staticMapLoadTimer);
                            s._staticMapLoadTimer = null;
                        }
                    }
                });
            }
        }

        if (!pickup || !dropoff) {
            state.summary_route_polyline = '';
            mapSlices.forEach(showEmptySlice);
            return;
        }

        var dirParams = 'origin=' + encodeURIComponent(pickup) + '&destination=' + encodeURIComponent(dropoff) + '&travelmode=driving';
        if (stops.length) {
            dirParams += '&waypoints=' + encodeURIComponent(stops.join('|'));
        }
        var dirUrl = 'https://www.google.com/maps/dir/?api=1&' + dirParams;

        if (mapsApiKey) {
            var mapLang = (config.maps && config.maps.language) ? config.maps.language : 'nl';
            /* Embed directions: geen zoom-parameter — Google past het zicht aan de volledige route aan. */
            var embed = 'https://www.google.com/maps/embed/v1/directions?key=' + encodeURIComponent(mapsApiKey)
                + '&origin=' + encodeURIComponent(pickup)
                + '&destination=' + encodeURIComponent(dropoff)
                + '&mode=driving'
                + '&units=metric'
                + '&region=nl'
                + '&language=' + encodeURIComponent(mapLang);
            if (stops.length) {
                embed += '&waypoints=' + encodeURIComponent(stops.join('|'));
            }
            var staticUrl = buildSummaryStaticMapUrl(
                state.summary_route_polyline || '',
                pickup,
                dropoff,
                state.pickup_lat,
                state.pickup_lng,
                state.dropoff_lat,
                state.dropoff_lng
            );
            mapSlices.forEach(function(s) {
                if (staticUrl && staticUrl.length < 7800) {
                    showStaticMapSlice(s, dirUrl, staticUrl, embed);
                } else if (staticUrl && staticUrl.length >= 7800) {
                    showEmbedSlice(s, dirUrl, embed);
                } else {
                    /* Geen polyline nog: geen embed (voorkomt tweede kaart zodra static binnenkomt). */
                    showMapLoadingSlice(s);
                }
            });
        } else {
            mapSlices.forEach(function(s) {
                showFallbackSlice(s, dirUrl);
            });
        }
    }

    function updateSummary() {
        var selected = state.offers.find(function(offer) { return offer.id === state.selected_offer_id; }) || null;
        var hasPickup = String(state.pickup_address || '').trim() !== '';
        var hasDropoff = String(state.dropoff_address || '').trim() !== '';
        var hasCompleteRoute = hasPickup && hasDropoff;
        var route = compactAddress(state.pickup_address || '') || '—';
        (state.stopovers || []).forEach(function(stop) { route += ' → ' + (compactAddress(stop) || stop); });
        route += ' → ' + (compactAddress(state.dropoff_address || '') || '—');
        var total = (selected && hasCompleteRoute) ? formatEuro(selected.price) : '—';
        var offerName = (selected && hasCompleteRoute) ? selected.title : '—';
        var pickupAt = formatDateTimeNl(state.pickup_at || '');

        var routeStackEl = root.querySelector('[data-summary-route-stacked]');
        if (routeStackEl) {
            var segs = [];
            var puSeg = compactAddress(state.pickup_address || '');
            if (puSeg) segs.push(puSeg);
            (state.stopovers || []).forEach(function(st) {
                var cs = compactAddress(st);
                if (cs) segs.push(cs);
            });
            var drSeg = compactAddress(state.dropoff_address || '');
            if (drSeg) segs.push(drSeg);
            routeStackEl.innerHTML = '';
            if (!segs.length) {
                var emptyP = document.createElement('p');
                emptyP.className = 'text-sm text-body w-full max-w-md mx-auto text-center';
                emptyP.textContent = '—';
                routeStackEl.appendChild(emptyP);
            } else {
                var col = document.createElement('div');
                col.className = 'flex flex-col items-center w-full max-w-md mx-auto gap-1';
                segs.forEach(function(seg, i) {
                    var line = document.createElement('div');
                    line.className = 'text-sm sm:text-base font-semibold text-heading leading-relaxed w-full text-center';
                    line.textContent = seg;
                    col.appendChild(line);
                    if (i < segs.length - 1) {
                        var arr = document.createElement('div');
                        arr.className = 'flex justify-center w-full py-1 text-fg-brand';
                        arr.setAttribute('aria-hidden', 'true');
                        arr.innerHTML = '<svg class="w-6 h-6 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>';
                        col.appendChild(arr);
                    }
                });
                routeStackEl.appendChild(col);
            }
        }

        var vehicleImageWrapEl = root.querySelector('[data-summary-vehicle-image-wrap]');
        var vehicleImageEl = root.querySelector('[data-summary-vehicle-image]');
        var selectedImageUrl = '';
        if (selected && selected.image_url) {
            selectedImageUrl = String(selected.image_url).trim();
        }
        if (!selectedImageUrl && selected && selected.vehicle_id) {
            var selectedVehicleId = String(selected.vehicle_id);
            var matchByVehicle = (state.offers || []).find(function(offer) {
                return String(offer && offer.vehicle_id ? offer.vehicle_id : '') === selectedVehicleId
                    && offer.image_url
                    && String(offer.image_url).trim() !== '';
            });
            if (matchByVehicle && matchByVehicle.image_url) {
                selectedImageUrl = String(matchByVehicle.image_url).trim();
            }
        }
        if (!selectedImageUrl) {
            var firstOfferWithImage = (state.offers || []).find(function(offer) {
                return offer && offer.image_url && String(offer.image_url).trim() !== '';
            });
            if (firstOfferWithImage && firstOfferWithImage.image_url) {
                selectedImageUrl = String(firstOfferWithImage.image_url).trim();
            }
        }
        if (vehicleImageWrapEl && vehicleImageEl) {
            if (selectedImageUrl) {
                vehicleImageEl.src = selectedImageUrl;
                vehicleImageEl.alt = selected && selected.title ? ('Voertuig: ' + selected.title) : 'Gekozen voertuig';
                vehicleImageWrapEl.classList.remove('hidden');
            } else {
                vehicleImageEl.src = '';
                vehicleImageEl.alt = '';
                vehicleImageWrapEl.classList.add('hidden');
            }
        }

        var baggageListEl = root.querySelector('[data-summary-baggage-list]');
        if (baggageListEl) {
            var itemMap = {};
            (config.baggage_items || []).forEach(function(item) {
                if (!item || !item.key) return;
                itemMap[String(item.key)] = item.title || item.key;
            });
            (config.special_items || []).forEach(function(item) {
                if (!item || !item.key) return;
                itemMap[String(item.key)] = item.title || item.key;
            });

            var selectedBaggage = [];
            Object.keys(state.baggage || {}).forEach(function(key) {
                var qty = parseInt(state.baggage[key] || 0, 10);
                if (qty > 0) selectedBaggage.push({ key: key, qty: qty });
            });
            Object.keys(state.special_baggage || {}).forEach(function(key) {
                var qty = parseInt(state.special_baggage[key] || 0, 10);
                if (qty > 0) selectedBaggage.push({ key: key, qty: qty });
            });

            if (!selectedBaggage.length) {
                baggageListEl.innerHTML = '<span class="inline-flex items-center rounded-full bg-neutral-primary px-2.5 py-1 text-sm text-body">Geen bagage geselecteerd</span>';
            } else {
                baggageListEl.innerHTML = selectedBaggage.map(function(row) {
                    var label = itemMap[row.key] || row.key;
                    return '<span class="inline-flex items-center rounded-full bg-neutral-primary px-2.5 py-1 text-sm font-medium text-heading">' + label + ' × ' + row.qty + '</span>';
                }).join('');
            }
        }

        var totalEl = root.querySelector('[data-summary-total]');
        if (totalEl) totalEl.textContent = total;
        var offerEl = root.querySelector('[data-summary-offer]');
        if (offerEl) offerEl.textContent = offerName;
        var pickupEl = root.querySelector('[data-summary-pickup-at]');
        if (pickupEl) pickupEl.textContent = pickupAt;

        var remarksEl = root.querySelector('[data-summary-remarks]');
        if (remarksEl) {
            var rm = String(state.remarks || '').trim();
            remarksEl.textContent = rm || '—';
        }

        var confirmPassEl = root.querySelector('[data-summary-confirm-passengers]');
        if (confirmPassEl) {
            var pp = parseInt(state.passengers || 1, 10);
            confirmPassEl.textContent = pp + ' ' + (pp === 1 ? 'passagier' : 'passagiers');
        }

        updateSummaryRouteMap();
    }

    function requestQuotes() {
        clearError();
        return fetch(quoteUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : ''
            },
            body: JSON.stringify({
                page_id: pageId,
                section_key: sectionKey,
                module: bookingModuleName || undefined,
                distance_meters: parseInt(state.distance_meters || 0, 10),
                duration_seconds: parseInt(state.duration_seconds || 0, 10),
                passengers: parseInt(state.passengers || 1, 10),
                return_trip: !!state.return_trip,
                pickup_at: state.pickup_at || null,
                waiting_minutes: 0,
                baggage: state.baggage || {},
                special_baggage: state.special_baggage || {},
                stopovers: state.stopovers || []
            })
        })
        .then(function(response) {
            if (!response.ok) return response.json().then(function(data) { throw new Error(data.message || 'Prijsberekening mislukt'); });
            return response.json();
        })
        .then(function(payload) {
            var data = payload && payload.data ? payload.data : {};
            state.offers = Array.isArray(data.offers) ? data.offers : [];
            state.offer_display_mode = data.offer_display_mode || state.offer_display_mode || 'vehicle';
            state.person_range = data.person_range || (state.passengers <= 4 ? '1-4' : '5-8');
            var visible = offersForDisplayMode();
            if (!visible.some(function(offer) { return offer.id === state.selected_offer_id; })) {
                state.selected_offer_id = visible[0] ? visible[0].id : null;
            }
            renderOffers();
            updateSummary();
        })
        .catch(function(error) {
            showError(error.message || 'Prijsberekening mislukt');
        });
    }

    var routeCalcSeq = 0;
    var geocodeCache = new Map();
    var GEOCODE_CACHE_MAX = 80;
    var GEOCODE_TIMEOUT_MS = 5000;
    var OSRM_TIMEOUT_MS = 6000;
    var ROUTE_TOTAL_TIMEOUT_MS = 12000;

    function fetchWithTimeout(url, ms) {
        var ctrl = typeof AbortController !== 'undefined' ? new AbortController() : null;
        var timeoutId = setTimeout(function() {
            if (ctrl) ctrl.abort();
        }, ms);
        var opts = ctrl ? { signal: ctrl.signal } : {};
        return fetch(url, opts).then(function(res) {
            clearTimeout(timeoutId);
            return res;
        }).catch(function(err) {
            clearTimeout(timeoutId);
            throw err;
        });
    }

    function fetchNominatimCoordinates(address) {
        var key = String(address || '').trim().toLowerCase();
        if (!key) return Promise.resolve(null);
        if (geocodeCache.has(key)) return Promise.resolve(geocodeCache.get(key));
        var country = (config.maps && config.maps.country ? String(config.maps.country) : 'nl').toLowerCase();
        var params = { format: 'jsonv2', limit: 1, countrycodes: country, q: address };
        var base = (config.address_search_url || '').trim();
        var url = base
            ? base + (base.indexOf('?') >= 0 ? '&' : '?') + new URLSearchParams(params).toString()
            : 'https://nominatim.openstreetmap.org/search?' + new URLSearchParams(params).toString();
        return fetchWithTimeout(url, GEOCODE_TIMEOUT_MS)
            .then(function(res) { return res.ok ? res.json() : []; })
            .then(function(rows) {
                var row = Array.isArray(rows) && rows[0] ? rows[0] : null;
                if (!row) return null;
                var coord = {
                    lat: parseFloat(row.lat || '0'),
                    lng: parseFloat(row.lon || '0')
                };
                if (!isFinite(coord.lat) || !isFinite(coord.lng)) return null;
                if (geocodeCache.size >= GEOCODE_CACHE_MAX) {
                    var firstKey = geocodeCache.keys().next().value;
                    if (firstKey !== undefined) geocodeCache.delete(firstKey);
                }
                geocodeCache.set(key, coord);
                return coord;
            })
            .catch(function() { return null; });
    }

    function calculateRouteFallback() {
        if (!state.pickup_address || !state.dropoff_address) {
            renderRouteDetailsText('');
            requestQuotes();
            return;
        }

        var seq = ++routeCalcSeq;
        showRouteDetailsLoading();
        state.summary_route_polyline = '';
        updateSummaryRouteMap();
        var addressChain = [state.pickup_address]
            .concat((state.stopovers || []).filter(function(s) { return String(s || '').trim() !== ''; }))
            .concat([state.dropoff_address]);

        var totalTimeout = new Promise(function(_, reject) {
            setTimeout(function() { reject(new Error('timeout')); }, ROUTE_TOTAL_TIMEOUT_MS);
        });

        var routePromise = Promise.all(addressChain.map(fetchNominatimCoordinates))
            .then(function(points) {
                if (seq !== routeCalcSeq) return;
                if (!Array.isArray(points) || points.some(function(p) { return !p; })) {
                    state.summary_route_polyline = '';
                    renderRouteDetailsText('');
                    requestQuotes();
                    return;
                }
                var coordsPath = points.map(function(p) { return p.lng + ',' + p.lat; }).join(';');
                return fetchWithTimeout('https://router.project-osrm.org/route/v1/driving/' + coordsPath + '?overview=simplified&geometries=polyline', OSRM_TIMEOUT_MS)
                    .then(function(res) { return res.ok ? res.json() : null; })
                    .then(function(payload) {
                        if (seq !== routeCalcSeq) return;
                        var route = payload && Array.isArray(payload.routes) && payload.routes[0] ? payload.routes[0] : null;
                        if (!route) {
                            state.summary_route_polyline = '';
                            renderRouteDetailsText('');
                            requestQuotes();
                            return;
                        }
                        state.distance_meters = Math.max(0, Math.round(parseFloat(route.distance || 0)));
                        state.duration_seconds = Math.max(0, Math.round(parseFloat(route.duration || 0)));
                        state.summary_route_polyline = (route.geometry && typeof route.geometry === 'string') ? route.geometry : '';
                        var km = (state.distance_meters / 1000).toFixed(1).replace('.', ',');
                        var min = Math.round(state.duration_seconds / 60);
                        renderRouteDetailsStats(km, min);
                        updateSummaryRouteMap();
                        requestQuotes();
                    })
                    .catch(function() {
                        state.summary_route_polyline = '';
                        renderRouteDetailsText('');
                        requestQuotes();
                    });
            })
            .catch(function() {
                state.summary_route_polyline = '';
                renderRouteDetailsText('');
                requestQuotes();
            });

        Promise.race([routePromise, totalTimeout]).catch(function() {
            if (seq === routeCalcSeq) {
                state.summary_route_polyline = '';
                renderRouteDetailsText('');
                requestQuotes();
            }
        });
    }

    function recalculateRouteOrQuote() {
        if (window.__nexataxiBookingRouteCalc && state.pickup_address && state.dropoff_address) {
            window.__nexataxiBookingRouteCalc();
            return;
        }
        requestQuotes();
    }

    function bookingPhoneDigitsOnly(value) {
        return String(value || '').replace(/\D/g, '');
    }

    /** Na internationale toegangscode 00: 0031… → 31… */
    function normalizePhoneDigitsForNl(digits) {
        if (digits.indexOf('00') === 0) {
            return digits.substring(2);
        }
        return digits;
    }

    /** NL: begint met 06 (mobiel), +31, 0031, of 11 cijfers beginnend met 31 */
    function isExplicitlyDutchPhone(value) {
        var t = String(value || '').trim();
        var d = bookingPhoneDigitsOnly(value);
        if (d.indexOf('06') === 0) return true;
        if (t.indexOf('+31') === 0) return true;
        if (/^0031/i.test(t)) return true;
        if (d.indexOf('0031') === 0) return true;
        if (/^31\d{9}$/.test(d)) return true;
        return false;
    }

    /** NL nationaal 10 cijfers (0…), of +31/0031 → 31 + 9 cijfers (11 totaal na normalisatie). */
    function isValidDutchPhoneDigits(digits) {
        var d = normalizePhoneDigitsForNl(digits);
        if (/^0[1-9]\d{8}$/.test(d)) return true;
        if (/^31[1-9]\d{8}$/.test(d)) return true;
        return false;
    }

    /** Toegestane tekens; NL-streng, anders 8–15 cijfers. 06 = exact 10 cijfers (06 + 8). */
    function isValidBookingPhone(value) {
        var t = String(value || '').trim();
        if (!t) return false;
        if (!/^[+.\d\s()\-]+$/.test(t)) return false;
        var digits = bookingPhoneDigitsOnly(value);
        if (digits.indexOf('06') === 0) {
            return /^06\d{8}$/.test(digits);
        }
        if (isExplicitlyDutchPhone(value)) {
            return isValidDutchPhoneDigits(digits);
        }
        return digits.length >= 8 && digits.length <= 15;
    }

    /** Korte melding; alleen bij herkend NL-nummer een specifieke tekst. */
    function getPhoneFieldErrorMessage(phone) {
        var t = String(phone || '').trim();
        if (!t) return 'Telefoonnummer is verplicht.';
        if (!/^[+.\d\s()\-]+$/.test(t)) {
            return 'Vul een geldig telefoonnummer in.';
        }
        var digits = bookingPhoneDigitsOnly(t);
        if (digits.indexOf('06') === 0 && !/^06\d{8}$/.test(digits)) {
            return '06-nummer: precies 10 cijfers.';
        }
        if (isExplicitlyDutchPhone(t) && !isValidDutchPhoneDigits(digits)) {
            return 'Nederlands nummer: 10 cijfers, of 11 cijfers met +31/0031.';
        }
        return 'Vul een geldig telefoonnummer in.';
    }

    function isValidBookingEmail(value) {
        var t = String(value || '').trim();
        if (!t) return false;
        return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/i.test(t);
    }

    function validateContactStepFields() {
        var fail = false;
        var fn = String(state.first_name || '').trim();
        var ln = String(state.last_name || '').trim();
        var phone = String(state.phone || '').trim();
        var em = String(state.email || '').trim();
        if (fn.length < 2) {
            setFieldError('first_name', fn.length === 0 ? 'Voornaam is verplicht.' : 'Voornaam moet minimaal 2 tekens bevatten.');
            fail = true;
        }
        if (ln.length < 2) {
            setFieldError('last_name', ln.length === 0 ? 'Achternaam is verplicht.' : 'Achternaam moet minimaal 2 tekens bevatten.');
            fail = true;
        }
        if (!phone) {
            setFieldError('phone', 'Telefoonnummer is verplicht.');
            fail = true;
        } else if (!isValidBookingPhone(phone)) {
            setFieldError('phone', getPhoneFieldErrorMessage(phone));
            fail = true;
        }
        if (em && !isValidBookingEmail(em)) {
            setFieldError('email', 'Vul een geldig e-mailadres in.');
            fail = true;
        }
        return !fail;
    }

    function validateCurrentStep() {
        clearError();
        var currentStepKey = getCurrentStepKey();
        if (currentStepKey === 'offers' && !state.selected_offer_id) {
            showError('Selecteer eerst een aanbieding.');
            return false;
        }
        if (currentStepKey === 'trip') {
            var pu = String(state.pickup_address || '').trim();
            var doAddr = String(state.dropoff_address || '').trim();
            var pa = String(state.pickup_at || '').trim();
            var tripErr = false;
            if (!pu) {
                setFieldError('pickup_address', 'Vul een ophaaladres in.');
                tripErr = true;
            }
            if (!doAddr) {
                setFieldError('dropoff_address', 'Vul een afzetadres in.');
                tripErr = true;
            }
            if (!pa) {
                setFieldError('pickup_at', 'Selecteer datum en tijd voor ophalen.');
                tripErr = true;
            }
            if (tripErr) return false;
            var pickupMs = parseLocalDateTimeMs(pa);
            if (!isNaN(pickupMs) && pickupMs < Date.now()) {
                syncPickupDatetimeFutureValidation();
                return false;
            }
            if (state.return_trip && !String(state.return_at || '').trim()) {
                setFieldError('return_at', 'Vul het retourmoment in.');
                return false;
            }
        }
        if (currentStepKey === 'contact') {
            return validateContactStepFields();
        }
        return true;
    }

    function validateAllBeforeSubmit() {
        clearError();
        var tripFail = false;
        if (!String(state.pickup_address || '').trim()) {
            setFieldError('pickup_address', 'Vul het ophaaladres in.');
            tripFail = true;
        }
        if (!String(state.dropoff_address || '').trim()) {
            setFieldError('dropoff_address', 'Vul het afzetadres in.');
            tripFail = true;
        }
        if (!String(state.pickup_at || '').trim()) {
            setFieldError('pickup_at', 'Selecteer datum en tijd voor ophalen.');
            tripFail = true;
        }
        if (tripFail) {
            setStepByKey('trip');
            return false;
        }
        var submitPickupMs = parseLocalDateTimeMs(String(state.pickup_at || '').trim());
        if (!isNaN(submitPickupMs) && submitPickupMs < Date.now()) {
            syncPickupDatetimeFutureValidation();
            setStepByKey('trip');
            return false;
        }
        if (state.return_trip && !String(state.return_at || '').trim()) {
            setFieldError('return_at', 'Vul het retourmoment in.');
            setStepByKey('trip');
            return false;
        }
        if (!state.selected_offer_id) {
            showError('Selecteer een aanbieding.');
            setStepByKey('offers');
            return false;
        }
        if (!validateContactStepFields()) {
            setStepByKey('contact');
            return false;
        }
        return true;
    }

    var confirmModalEscapeHandler = function(e) {
        if (e.key === 'Escape') {
            closeConfirmModal();
            document.removeEventListener('keydown', confirmModalEscapeHandler);
        }
    };

    function showConfirmModal() {
        var modal = root.querySelector('[data-booking-confirm-modal]');
        if (!modal) return;
        modal.classList.remove('hidden');
        document.documentElement.classList.add('booking-modal-open');
        document.body.classList.add('booking-modal-open');
        document.addEventListener('keydown', confirmModalEscapeHandler);
    }

    function closeConfirmModal() {
        var modal = root.querySelector('[data-booking-confirm-modal]');
        if (!modal) return;
        modal.classList.add('hidden');
        document.documentElement.classList.remove('booking-modal-open');
        document.body.classList.remove('booking-modal-open');
        document.removeEventListener('keydown', confirmModalEscapeHandler);
    }

    function getSelectedPaymentMethod() {
        var paymentCfg = config.payment || {};
        if (!paymentCfg.booking && !paymentCfg.driver) return null;
        if (paymentCfg.booking && !paymentCfg.driver) return 'booking';
        if (paymentCfg.driver && !paymentCfg.booking) return 'driver';
        var checked = root.querySelector('[data-booking-payment-radio]:checked');
        if (checked && checked.value) return checked.value;
        var fixed = root.querySelector('[data-booking-payment-fixed]');
        if (fixed && fixed.value) return fixed.value;
        return 'booking';
    }

    function submitBooking(sendToWhatsapp) {
        clearError();
        var paymentMethod = getSelectedPaymentMethod();
        var payload = {
            page_id: pageId,
            section_key: sectionKey,
            module: bookingModuleName || undefined,
            selected_offer_id: state.selected_offer_id,
            distance_meters: parseInt(state.distance_meters || 0, 10),
            duration_seconds: parseInt(state.duration_seconds || 0, 10),
            passengers: parseInt(state.passengers || 1, 10),
            return_trip: !!state.return_trip,
            pickup_address: state.pickup_address,
            stopovers: state.stopovers || [],
            dropoff_address: state.dropoff_address,
            pickup_at: state.pickup_at,
            return_at: state.return_at || null,
            pickup_lat: state.pickup_lat,
            pickup_lng: state.pickup_lng,
            stopovers_geo: state.stopovers_geo || [],
            dropoff_lat: state.dropoff_lat,
            dropoff_lng: state.dropoff_lng,
            remarks: state.remarks || '',
            first_name: state.first_name || '',
            last_name: state.last_name || '',
            phone: state.phone || '',
            email: state.email || '',
            baggage: state.baggage || {},
            special_baggage: state.special_baggage || {}
        };
        if (paymentMethod) {
            payload.payment_method = paymentMethod;
        }

        fetch(submitUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : ''
            },
            body: JSON.stringify(payload)
        })
        .then(function(response) {
            if (!response.ok) return response.json().then(function(data) { throw new Error(data.message || 'Boeking versturen mislukt'); });
            return response.json();
        })
        .then(function(data) {
            if (data && data.checkout_url) {
                window.location.href = data.checkout_url;
                return;
            }
            if (sendToWhatsapp) {
                openWhatsappWithSummary(data && data.ride_request_id ? data.ride_request_id : null);
            }
            showSuccess(data.message || (config.texts && config.texts.success_message ? config.texts.success_message : 'Bedankt! Je boeking is ontvangen.'));
        })
        .catch(function(error) {
            if (sendToWhatsapp) {
                closePreparedWhatsappWindow();
            }
            showError(error.message || 'Boeking versturen mislukt');
        });
    }

    function initGoogleMaps() {
        if (!mapsApiKey) return;
        function startAutocomplete() {
            if (!window.google || !google.maps || !google.maps.places) return;
            /* Pickup en dropoff gebruiken de typeahead (setupAddressTypeaheadFallback) met getPlacePredictions
               en custom panel. Geen native Autocomplete op deze velden om conflicten te voorkomen. */
            setupStopoverAutocompletes();
            var pickupInput = root.querySelector('[data-field="pickup_address"]');
            var dropoffInput = root.querySelector('[data-field="dropoff_address"]');
            if (pickupInput && dropoffInput && state.pickup_address && state.dropoff_address) {
                calculateRoute();
            }
        }

        function calculateRoute() {
            if (!state.pickup_address || !state.dropoff_address) {
                renderRouteDetailsText('');
                requestQuotes();
                return;
            }
            showRouteDetailsLoading();
            state.summary_route_polyline = '';
            updateSummaryRouteMap();
            if (!window.google || !google.maps || typeof google.maps.importLibrary !== 'function') {
                calculateRouteFallback();
                return;
            }
            google.maps.importLibrary('routes').then(function(routesLib) {
                var Route = routesLib && (routesLib.Route || routesLib);
                if (!Route || typeof Route.computeRoutes !== 'function') {
                    calculateRouteFallback();
                    return;
                }
                var request = {
                    origin: state.pickup_address,
                    destination: state.dropoff_address,
                    travelMode: 'DRIVING',
                    regionCode: 'nl',
                    computeAlternativeRoutes: false,
                    routingPreference: 'TRAFFIC_AWARE_OPTIMAL'
                };
                var stopovers = (state.stopovers || []).filter(function(s) { return String(s || '').trim() !== ''; });
                if (stopovers.length > 0) request.intermediates = stopovers;

                Route.computeRoutes(request).then(function(result) {
                    if (!result || !result.routes || !result.routes[0]) {
                        calculateRouteFallback();
                        return;
                    }
                    var route = result.routes[0];
                    var dist = route.distanceMeters;
                    var durMs = route.durationMillis;
                    if (dist == null || durMs == null) {
                        calculateRouteFallback();
                        return;
                    }
                    state.distance_meters = Math.round(Number(dist));
                    state.duration_seconds = Math.round(Number(durMs) / 1000);
                    var polyEnc = '';
                    if (route.polyline) {
                        if (typeof route.polyline.encodedPolyline === 'string') {
                            polyEnc = route.polyline.encodedPolyline;
                        } else if (typeof route.polyline === 'string') {
                            polyEnc = route.polyline;
                        }
                    }
                    state.summary_route_polyline = polyEnc;
                    var km = (state.distance_meters / 1000).toFixed(1).replace('.', ',');
                    var min = Math.round(state.duration_seconds / 60);
                    renderRouteDetailsStats(km, min);
                    updateSummaryRouteMap();
                    requestQuotes();
                }).catch(function() {
                    calculateRouteFallback();
                });
            }).catch(function() {
                calculateRouteFallback();
            });
        }

        window.__nexataxiBookingRouteCalc = calculateRoute;
        if (window.google && google.maps && google.maps.places) {
            startAutocomplete();
            return;
        }

        if (window.google && google.maps && typeof google.maps.importLibrary === 'function') {
            google.maps.importLibrary('places')
                .then(function() {
                    startAutocomplete();
                })
                .catch(function() {
                    // Keep graceful fallback from setupAddressTypeaheadFallback.
                });
            return;
        }

        var existingMapsScript = Array.from(document.querySelectorAll('script[src*="maps.googleapis.com/maps/api/js"]'));
        if (existingMapsScript.length > 0) {
            // Avoid loading Google Maps twice; duplicate loads break Places autocomplete.
            return;
        }

        var callbackName = 'initNexaTaxiBookingMaps_' + Math.floor(Math.random() * 1000000);
        window[callbackName] = function() {
            startAutocomplete();
        };
        var script = document.createElement('script');
        script.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(mapsApiKey) + '&libraries=places&language=' + encodeURIComponent((config.maps && config.maps.language) ? config.maps.language : 'nl') + '&callback=' + callbackName + '&loading=async';
        script.async = true;
        document.head.appendChild(script);
    }

    function setupAddressTypeaheadFallback() {
        var pickupInput = root.querySelector('[data-field="pickup_address"]');
        var dropoffInput = root.querySelector('[data-field="dropoff_address"]');
        var countryCode = (config.maps && config.maps.country ? String(config.maps.country) : 'nl').toLowerCase();
        if (!pickupInput || !dropoffInput) return;
        var listIdPrefix = 'booking-address-suggestions-' + Math.floor(Math.random() * 1000000);
        var useCustomSuggestionPanel = true;
        var panelByKey = {};
        var suggestionCache = new Map();
        var lastSuggestionsByKey = {};
        var requestSeqByKey = {};
        var nominatimAbortByKey = {};
        var hidePanelTimeoutByKey = {};

        function updateRouteInputVisualState(input) {
            if (!input) return;
            if (String(input.value || '').trim() !== '') {
                input.classList.add('is-selected');
            } else {
                input.classList.remove('is-selected');
            }
        }

        function ensureDataList(input, key) {
            if (!input) return null;
            // We intentionally avoid native datalist to prevent browser popups
            // from conflicting with the styled custom suggestion panel.
            input.removeAttribute('list');
            return null;
        }

        function ensureSuggestionPanel(input, key) {
            if (!useCustomSuggestionPanel) return null;
            if (!input) return null;
            if (panelByKey[key] && panelByKey[key].isConnected) return panelByKey[key];
            var panel = document.createElement('div');
            panel.className = 'booking-address-suggestions-panel booking-address-suggestions-panel--fixed hidden';
            panel.setAttribute('data-suggestion-panel', key);
            panel.setAttribute('role', 'listbox');
            panel.setAttribute('aria-label', 'Adressuggesties');
            panel.style.display = 'none';
            document.body.appendChild(panel);
            panelByKey[key] = panel;
            return panel;
        }

        function positionPanelUnderInput(panel, input) {
            if (!panel || !input) return;
            var rect = input.getBoundingClientRect();
            panel.style.position = 'fixed';
            panel.style.top = (rect.bottom + 6) + 'px';
            panel.style.left = rect.left + 'px';
            panel.style.width = Math.max(rect.width, 280) + 'px';
            panel.style.minWidth = '280px';
            panel.style.zIndex = '99999';
        }

        function hideSuggestionPanel(key) {
            if (hidePanelTimeoutByKey[key]) {
                clearTimeout(hidePanelTimeoutByKey[key]);
                hidePanelTimeoutByKey[key] = null;
            }
            if (!useCustomSuggestionPanel) return;
            var panel = panelByKey[key];
            if (!panel) return;
            panel.style.display = 'none';
            panel.classList.add('hidden');
            panel.innerHTML = '';
        }

        function showPanelLoading(input, key) {
            if (hidePanelTimeoutByKey[key]) {
                clearTimeout(hidePanelTimeoutByKey[key]);
                hidePanelTimeoutByKey[key] = null;
            }
            if (!useCustomSuggestionPanel) return;
            var panel = ensureSuggestionPanel(input, key);
            if (!panel) return;
            panel.style.display = '';
            panel.innerHTML = '<div class="booking-address-suggestion-item booking-address-suggestion-loading" role="option" aria-live="polite">Laden…</div>';
            positionPanelUnderInput(panel, input);
            panel.classList.remove('hidden');
        }

        function renderSuggestionPanel(input, key, suggestions) {
            if (!useCustomSuggestionPanel) return;
            if (hidePanelTimeoutByKey[key]) {
                clearTimeout(hidePanelTimeoutByKey[key]);
                hidePanelTimeoutByKey[key] = null;
            }
            var panel = ensureSuggestionPanel(input, key);
            if (!panel) return;
            if (!Array.isArray(suggestions) || suggestions.length === 0) {
                hideSuggestionPanel(key);
                return;
            }
            panel.innerHTML = '';
            suggestions.slice(0, 6).forEach(function(suggestion) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'booking-address-suggestion-item';
                btn.setAttribute('role', 'option');
                btn.textContent = suggestion && suggestion.label ? suggestion.label : '';
                btn.setAttribute('data-suggestion-value', suggestion && suggestion.value ? suggestion.value : '');
                btn.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (hidePanelTimeoutByKey[key]) {
                        clearTimeout(hidePanelTimeoutByKey[key]);
                        hidePanelTimeoutByKey[key] = null;
                    }
                    input.value = suggestion && suggestion.value ? suggestion.value : '';
                    updateRouteInputVisualState(input);
                    syncStateFromFields();
                    if (window.__nexataxiBookingRouteCalc) {
                        window.__nexataxiBookingRouteCalc();
                    }
                    hideSuggestionPanel(key);
                });
                panel.appendChild(btn);
            });
            positionPanelUnderInput(panel, input);
            panel.style.display = '';
            panel.classList.remove('hidden');
        }

        function updateDataList(input, key, suggestions) {
            ensureDataList(input, key);
        }

        var service = null;
        var serviceReady = function() {
            return !!(window.google && google.maps && google.maps.places && google.maps.places.AutocompleteService);
        };
        function getService() {
            if (!serviceReady()) return null;
            if (!service) service = new google.maps.places.AutocompleteService();
            return service;
        }

        function debounce(fn, wait) {
            var timer = null;
            return function() {
                var args = arguments;
                clearTimeout(timer);
                timer = setTimeout(function() {
                    fn.apply(null, args);
                }, wait);
            };
        }

        function formatNominatimAddress(row) {
            if (!row) return null;
            var displayName = (row.display_name && String(row.display_name).trim()) ? compactAddress(row.display_name) : '';
            if (!row.address) {
                return displayName ? { label: displayName, value: displayName } : null;
            }
            var a = row.address;
            var street = a.road || a.pedestrian || a.footway || a.cycleway || '';
            var number = a.house_number || '';
            var city = a.city || a.town || a.village || a.hamlet || a.city_district || a.suburb || a.county || a.municipality || '';
            var postcode = a.postcode || '';
            var first = [street, number].filter(Boolean).join(' ').trim();
            var second = [postcode, city].filter(Boolean).join(' ').trim();
            var compact = [first, second].filter(Boolean).join(', ').trim();
            var value = compact || displayName || '';
            if (!value) return null;
            var compactValue = compactAddress(value);
            return { label: compactValue, value: compactValue };
        }

        function buildNominatimUrl(params) {
            var base = (config.address_search_url || '').trim();
            if (!base && typeof window !== 'undefined' && window.location) {
                base = window.location.origin + '/nexa-taxi/booking/address-search';
            }
            if (base && base.startsWith('/') && typeof window !== 'undefined' && window.location) {
                base = window.location.origin + base;
            }
            if (!base) {
                base = 'https://nominatim.openstreetmap.org/search';
            }
            var searchParams = new URLSearchParams(params);
            return base + (base.indexOf('?') >= 0 ? '&' : '?') + searchParams.toString();
        }
        var TYPEAHEAD_FETCH_TIMEOUT_MS = 6000;
        function fetchNominatimPredictions(q, sourceKey) {
            var key = sourceKey || 'default';
            if (nominatimAbortByKey[key]) {
                nominatimAbortByKey[key].abort();
            }
            var controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
            if (controller) {
                nominatimAbortByKey[key] = controller;
            }
            var url = buildNominatimUrl({ format: 'jsonv2', addressdetails: 1, limit: 8, dedupe: 1, countrycodes: countryCode, 'accept-language': 'nl', q: q });
            var fetchPromise = fetch(url, controller ? { signal: controller.signal } : undefined)
                .then(function(res) { return res.ok ? res.json() : []; })
                .then(function(rows) {
                    return Array.isArray(rows)
                        ? rows.map(formatNominatimAddress).filter(function(item) { return item && item.value; })
                        : [];
                })
                .catch(function() {
                    return [];
                });
            var timeoutPromise = new Promise(function(resolve) {
                setTimeout(function() {
                    if (controller) controller.abort();
                    resolve([]);
                }, TYPEAHEAD_FETCH_TIMEOUT_MS);
            });
            return Promise.race([fetchPromise, timeoutPromise]);
        }

        function fetchPredictions(query, sourceKey) {
            var q = String(query || '').trim();
            if (q.length < 1) return Promise.resolve([]);
            return fetchNominatimPredictions(q, sourceKey);
        }

        function getInputKey(input) {
            var field = input.getAttribute('data-field');
            if (field === 'dropoff_address') return 'dropoff';
            if (field === 'pickup_address') return 'pickup';
            if (input.hasAttribute('data-stopover-input')) {
                var stopovers = root.querySelectorAll('[data-stopover-input]');
                var idx = Array.prototype.indexOf.call(stopovers, input);
                return 'stopover-' + (idx >= 0 ? idx : 0);
            }
            return 'pickup';
        }
        var MIN_QUERY_LENGTH = 2;
        function runTypeaheadNow(input) {
            if (!input) return;
            var raw = input.value || '';
            var query = raw.trim();
            var key = getInputKey(input);
            if (query.length < MIN_QUERY_LENGTH) {
                hideSuggestionPanel(key);
                lastSuggestionsByKey[key] = [];
                return;
            }
            var normalizedQuery = query.toLowerCase();
            var cacheKey = key + '::' + normalizedQuery;
            if (suggestionCache.has(cacheKey)) {
                var cachedSuggestions = suggestionCache.get(cacheKey) || [];
                lastSuggestionsByKey[key] = cachedSuggestions;
                renderSuggestionPanel(input, key, cachedSuggestions);
                return;
            }
            requestSeqByKey[key] = (requestSeqByKey[key] || 0) + 1;
            var requestId = requestSeqByKey[key];
            showPanelLoading(input, key);
            fetchPredictions(query, key).then(function(suggestions) {
                if (requestId !== requestSeqByKey[key]) return;
                if (!Array.isArray(suggestions)) suggestions = [];
                if (suggestions.length > 0) {
                    suggestionCache.set(cacheKey, suggestions);
                    lastSuggestionsByKey[key] = suggestions;
                    renderSuggestionPanel(input, key, suggestions);
                    return;
                }
                var parts = query.split(/\s+/).filter(Boolean);
                if (parts.length > 1) {
                    var fallbackQuery = parts.slice(0, -1).join(' ');
                    if (fallbackQuery.length >= MIN_QUERY_LENGTH) {
                        fetchPredictions(fallbackQuery, key).then(function(fallbackSuggestions) {
                            if (requestId !== requestSeqByKey[key]) return;
                            if (Array.isArray(fallbackSuggestions) && fallbackSuggestions.length > 0) {
                                lastSuggestionsByKey[key] = fallbackSuggestions;
                                renderSuggestionPanel(input, key, fallbackSuggestions);
                                return;
                            }
                            hideSuggestionPanel(key);
                            lastSuggestionsByKey[key] = [];
                        });
                        return;
                    }
                }
                hideSuggestionPanel(key);
                lastSuggestionsByKey[key] = [];
            });
        }
        var runTypeahead = debounce(runTypeaheadNow, 280);

        function bindOneAddressInput(input) {
            if (!input || input.getAttribute('data-typeahead-bound') === '1') return;
            var key = getInputKey(input);
            ensureDataList(input, key);
            ensureSuggestionPanel(input, key);
            updateRouteInputVisualState(input);
            input.addEventListener('input', function() {
                updateRouteInputVisualState(input);
                runTypeahead(input);
            });
            input.addEventListener('focus', function() {
                runTypeaheadNow(input);
            });
            input.addEventListener('click', function() {
                if ((input.value || '').trim().length >= 1) runTypeaheadNow(input);
            });
            input.addEventListener('change', function() {
                updateRouteInputVisualState(input);
            });
            input.addEventListener('blur', function() {
                var k = getInputKey(input);
                if (hidePanelTimeoutByKey[k]) clearTimeout(hidePanelTimeoutByKey[k]);
                hidePanelTimeoutByKey[k] = setTimeout(function() {
                    hidePanelTimeoutByKey[k] = null;
                    hideSuggestionPanel(k);
                }, 180);
            });
            input.addEventListener('keydown', function(e) {
                var k = getInputKey(input);
                if (e.key === 'Enter' && Array.isArray(lastSuggestionsByKey[k]) && lastSuggestionsByKey[k].length > 0) {
                    var first = lastSuggestionsByKey[k][0];
                    input.value = first && first.value ? first.value : input.value;
                    updateRouteInputVisualState(input);
                    syncStateFromFields();
                    if (window.__nexataxiBookingRouteCalc) {
                        window.__nexataxiBookingRouteCalc();
                    }
                    hideSuggestionPanel(k);
                }
            });
            input.setAttribute('data-typeahead-bound', '1');
        }

        function bindAllRouteAddressInputs() {
            [pickupInput, dropoffInput].forEach(bindOneAddressInput);
            root.querySelectorAll('[data-stopover-input]').forEach(bindOneAddressInput);
        }
        bindAllRouteAddressInputs();
        root._bindAllRouteAddressInputs = bindAllRouteAddressInputs;

        function hideAllSuggestionPanels() {
            Object.keys(panelByKey).forEach(function(k) {
                hideSuggestionPanel(k);
            });
            document.querySelectorAll('.booking-address-suggestions-panel').forEach(function(panel) {
                panel.style.display = 'none';
                panel.classList.add('hidden');
                panel.innerHTML = '';
            });
        }
        root._hideAllBookingSuggestionPanels = hideAllSuggestionPanels;
    }

    root.addEventListener('input', function(e) {
        if (e.target.matches('[data-field]')) {
            clearFieldErrorFor(e.target.getAttribute('data-field'));
            syncStateFromFields();
        }
        if (e.target.matches('[data-stopover-input]')) {
            syncStateFromFields();
        }
    });

    root.addEventListener('change', function(e) {
        if (e.target.matches('[data-booking-step-select]')) {
            var selectedStepKey = e.target.value || '';
            if (selectedStepKey === 'baggage' && !state.has_baggage) {
                selectedStepKey = 'offers';
            }
            if (!isStepReachable(selectedStepKey)) {
                e.target.value = getCurrentStepKey();
                return;
            }
            if (stepOrder.indexOf(selectedStepKey) >= 0) {
                clearError();
                setStepByKey(selectedStepKey);
                var currentStepKey = getCurrentStepKey();
                if (currentStepKey === 'offers' || currentStepKey === 'confirm') {
                    requestQuotes();
                }
                updateSummary();
            }
            return;
        }
        if (e.target.matches('[data-field]')) {
            clearFieldErrorFor(e.target.getAttribute('data-field'));
            syncStateFromFields();
            if (e.target.getAttribute('data-field') === 'pickup_address' || e.target.getAttribute('data-field') === 'dropoff_address' || e.target.getAttribute('data-field') === 'return_trip') {
                recalculateRouteOrQuote();
            }
        }
        if (e.target.matches('[data-stopover-input]')) {
            syncStateFromFields();
            recalculateRouteOrQuote();
        }
        if (e.target.matches('[data-toggle-special-baggage]')) {
            var wrap = root.querySelector('[data-special-baggage-wrap]');
            if (wrap) wrap.classList.toggle('hidden', !e.target.checked);
        }
        if (e.target.matches('input[name="booking_has_baggage_ui"]')) {
            syncBaggageChoiceFromUi();
        }
    });

    root.addEventListener('mousedown', function(e) {
        var stopoverBtn = e.target.closest('.booking-stopover-toggle');
        if (stopoverBtn) {
            e.preventDefault();
            var didAddStopover = addStopover('');
            if (!didAddStopover) return;
            var list = root.querySelector('[data-stopovers-list]');
            if (list && list.lastElementChild) {
                var lastInput = list.lastElementChild.querySelector('[data-stopover-input]');
                if (lastInput) lastInput.focus();
            }
        }
    });

    root.addEventListener('click', function(e) {
        if (e.target.matches('[data-booking-success-backdrop]')) {
            closeSuccessModal();
            return;
        }
        var successCloseBtn = e.target.closest('[data-booking-success-close]');
        if (successCloseBtn) {
            e.preventDefault();
            closeSuccessModal();
            return;
        }
        var dtInput = e.target.closest('.booking-datetime-input');
        if (dtInput) {
            if (typeof dtInput.showPicker === 'function') {
                try {
                    dtInput.showPicker();
                } catch (err) {
                    // Ignore security/user-gesture errors; native behavior still applies.
                }
            }
        }
        var stopoverBtn = e.target.closest('.booking-stopover-toggle');
        if (stopoverBtn) {
            e.preventDefault();
            return;
        }
        var stopoverRemove = e.target.closest('.booking-stopover-remove');
        if (stopoverRemove) {
            e.preventDefault();
            var row = stopoverRemove.closest('.booking-stopover-row');
            if (row) row.remove();
            syncStateFromFields();
            requestQuotes();
            return;
        }
        var swapBtn = e.target.closest('.booking-route-swap-btn');
        if (swapBtn) {
            e.preventDefault();
            var pickupInput = root.querySelector('[data-field="pickup_address"]');
            var dropoffInput = root.querySelector('[data-field="dropoff_address"]');
            if (pickupInput && dropoffInput) {
                var oldPickup = pickupInput.value || '';
                pickupInput.value = dropoffInput.value || '';
                dropoffInput.value = oldPickup;
                var lat = state.pickup_lat;
                var lng = state.pickup_lng;
                state.pickup_lat = state.dropoff_lat;
                state.pickup_lng = state.dropoff_lng;
                state.dropoff_lat = lat;
                state.dropoff_lng = lng;
                if (state.stopovers && state.stopovers.length) {
                    state.stopovers.reverse();
                    var stopoverInputs = root.querySelectorAll('[data-stopover-input]');
                    stopoverInputs.forEach(function(input, index) {
                        input.value = state.stopovers[index] || '';
                    });
                }
                syncStateFromFields();
                requestQuotes();
            }
            return;
        }
        var tabBtn = e.target.closest('.booking-step-tab');
        if (tabBtn) {
            e.preventDefault();
            var tabStepIndex = parseInt(tabBtn.getAttribute('data-step-index'), 10);
            var targetStepKey = tabBtn.getAttribute('data-step-key') || '';
            if (isStepReachable(targetStepKey)) {
                if (targetStepKey === 'baggage' && !state.has_baggage) {
                    targetStepKey = 'offers';
                }
                if (targetStepKey && stepOrder.indexOf(targetStepKey) >= 0) {
                    clearError();
                    setStepByKey(targetStepKey);
                    var currentStepKey = getCurrentStepKey();
                    if (currentStepKey === 'offers' || currentStepKey === 'confirm') {
                        requestQuotes();
                    }
                    updateSummary();
                }
            }
            e.stopPropagation();
            return;
        }
        var qtyBtn = e.target.closest('.booking-qty-btn');
        if (qtyBtn) {
            e.preventDefault();
            var target = qtyBtn.getAttribute('data-target') || '';
            var delta = parseInt(qtyBtn.getAttribute('data-delta') || '0', 10);
            var max = qtyBtn.hasAttribute('data-max') ? parseInt(qtyBtn.getAttribute('data-max') || '0', 10) : null;
            updateQty(target, delta, max);
            requestQuotes();
            return;
        }
        var passengerBtn = e.target.closest('.booking-passenger-btn');
        if (passengerBtn) {
            e.preventDefault();
            var deltaPass = parseInt(passengerBtn.getAttribute('data-delta') || '0', 10);
            var nextPassengers = state.passengers + deltaPass;
            if (nextPassengers < state.minPassengers) nextPassengers = state.minPassengers;
            if (nextPassengers > state.maxPassengers) nextPassengers = state.maxPassengers;
            state.passengers = nextPassengers;
            syncStateFromFields();
            requestQuotes();
            return;
        }
        var offerCard = e.target.closest('[data-offer-id]');
        if (offerCard) {
            e.preventDefault();
            state.selected_offer_id = offerCard.getAttribute('data-offer-id') || null;
            renderOffers();
            updateSummary();
            return;
        }
        var prevBtn = e.target.closest('[data-booking-prev]');
        if (prevBtn) {
            e.preventDefault();
            clearError();
            var prevStepKey = getPrevStepKey(getCurrentStepKey());
            if (prevStepKey) {
                setStepByKey(prevStepKey);
            }
            return;
        }
        var nextBtn = e.target.closest('[data-booking-next]');
        if (nextBtn) {
            e.preventDefault();
            syncStateFromFields();
            if (!validateCurrentStep()) return;
            var currentStepKey = getCurrentStepKey();
            var nextStepKey = getNextStepKey(currentStepKey);
            if (nextStepKey) {
                var nextIndex = getStepIndexForKey(nextStepKey);
                if (nextIndex > 0) {
                    state.maxStep = Math.max(state.maxStep, nextIndex);
                }
                setStepByKey(nextStepKey);
                if (nextStepKey === 'offers' || nextStepKey === 'confirm') {
                    requestQuotes();
                }
                updateSummary();
                return;
            }
            if (!validateAllBeforeSubmit()) return;
            showConfirmModal();
        }
    });

    root.querySelectorAll('[data-booking-confirm-close], [data-booking-confirm-backdrop]').forEach(function(el) {
        el.addEventListener('click', function() { closeConfirmModal(); });
    });
    var confirmSubmitBtn = root.querySelector('[data-booking-confirm-submit]');
    if (confirmSubmitBtn) {
        confirmSubmitBtn.addEventListener('click', function() {
            closeConfirmModal();
            prepareWhatsappWindow();
            submitBooking(whatsappClickToChatEnabled);
        });
    }

    root.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            var confirmModal = root.querySelector('[data-booking-confirm-modal]');
            if (confirmModal && !confirmModal.classList.contains('hidden')) {
                closeConfirmModal();
                return;
            }
            closeSuccessModal();
            return;
        }
        if (e.key !== 'Enter' && e.key !== ' ') return;
        var offerCard = e.target.closest('[data-offer-id]');
        if (!offerCard) return;
        e.preventDefault();
        state.selected_offer_id = offerCard.getAttribute('data-offer-id') || null;
        renderOffers();
        updateSummary();
    });

    window.addEventListener('resize', function() {
        window.requestAnimationFrame(syncRouteIconAlignment);
    });

    window.__nexataxiBookingRouteCalc = calculateRouteFallback;

    function initBookingModule() {
        try {
            sessionStorage.removeItem('nexataxi_booking_confirm_dev_v1');
        } catch (e) {}
        setStep(1, { skipScroll: true });
        updateBaggageStepAvailability();
        syncStateFromFields();
        refreshPickupDatetimeMin();
        var pickupAtInput = root.querySelector('[data-field="pickup_at"]');
        if (pickupAtInput) {
            pickupAtInput.addEventListener('focus', function() {
                refreshPickupDatetimeMin();
            });
        }
        setupAddressTypeaheadFallback();
        initGoogleMaps();
        recalculateRouteOrQuote();
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initBookingModule);
    } else {
        initBookingModule();
    }
})();
</script>
<script>
(function() {
    function initBookingModuleScrollReveal() {
        var section = document.querySelector('[data-booking-module-scroll-reveal]');
        if (!section) return;
        var opts = { rootMargin: '0px 0px -60px 0px', threshold: 0.06 };
        if (typeof window.nexaObserveWhenVisible === 'function') {
            window.nexaObserveWhenVisible(section, function(el) {
                el.classList.add('is-in-view');
            }, opts);
            return;
        }
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) entry.target.classList.add('is-in-view');
            });
        }, opts);
        observer.observe(section);
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initBookingModuleScrollReveal);
    } else {
        initBookingModuleScrollReveal();
    }
})();
</script>
@endpush

