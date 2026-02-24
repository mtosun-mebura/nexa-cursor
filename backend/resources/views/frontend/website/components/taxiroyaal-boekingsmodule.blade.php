@php
    $sectionConfigRaw = (isset($sectionKey) && isset($homeSections) ? ($homeSections[$sectionKey] ?? []) : []);
    $bookingConfig = app(\App\Services\TaxiRoyaalBookingPricingService::class)->mergeSectionConfig(is_array($sectionConfigRaw) ? $sectionConfigRaw : []);
    $bookingPageId = $page->id ?? null;
    $mapsApiKey = trim((string) ($googleMapsApiKey ?? ''));
    $sectionStyle = $bookingConfig['style'] ?? [];
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
    $moduleOuterStyle = !empty($sectionStyle['container_max_width']) ? ('max-width: ' . $sectionStyle['container_max_width'] . ';') : '';
    $shellStyleParts = [
        'border-color: rgba(148, 163, 184, 0.45);',
        'border-radius: ' . (int) ($sectionStyle['border_radius'] ?? 12) . 'px;',
    ];
    $moduleShellStyle = implode(' ', $shellStyleParts);
@endphp

<section class="container-custom py-8 md:py-12" data-taxiroyaal-booking-module>
    <div class="flex {{ $moduleAlignClass }}">
    <div class="w-full" @if($moduleOuterStyle !== '') style="{{ $moduleOuterStyle }}" @endif>
    <div class="rounded-xl border p-0 overflow-hidden shadow-sm bg-neutral-primary text-heading"
        style="{{ $moduleShellStyle }}">
        <div class="px-6 py-5 border-b bg-neutral-secondary-soft" style="border-color: {{ e($sectionStyle['primary_color'] ?? '#5b21b6') }}33;">
            <h2 class="text-3xl md:text-4xl font-bold" style="color: {{ e($sectionStyle['primary_color'] ?? '#5b21b6') }};">{{ e($bookingConfig['title'] ?? 'Boek eenvoudig je taxirit') }}</h2>
            @if(!empty($bookingConfig['subtitle']))
            <p class="mt-2 text-body">{{ e($bookingConfig['subtitle']) }}</p>
            @endif
        </div>

        <div class="px-3 pt-2 border-b bg-neutral-primary" style="border-color: {{ e($sectionStyle['primary_color'] ?? '#5b21b6') }}22; border-bottom: 0 !important;">
            <div class="border-b border-default">
                <div class="sm:hidden">
                    <label for="booking-steps-select" class="sr-only">Selecteer stap</label>
                    <select id="booking-steps-select" data-booking-step-select class="bg-neutral-secondary-soft border-0 border-b border-default text-heading text-sm rounded-t-base focus:ring-brand block w-full p-2.5">
                        @foreach($stepOrder as $stepKey)
                            <option value="{{ $stepKey }}">{{ e($stepLabelByLogical[$stepKey] ?? 'Stap') }}</option>
                        @endforeach
                    </select>
                </div>
                <ul class="hidden sm:flex flex-wrap -mb-px text-sm font-medium text-center text-body" data-booking-steps-nav role="tablist">
                    @foreach($stepOrder as $idx => $stepKey)
                    <li class="me-2">
                        <button
                            id="booking-tab-{{ $stepKey }}"
                            data-step-index="{{ $idx + 1 }}"
                            data-step-key="{{ $stepKey }}"
                            data-tabs-target="#booking-panel-{{ $stepKey }}"
                            type="button"
                            role="tab"
                            aria-controls="booking-panel-{{ $stepKey }}"
                            aria-selected="{{ $idx === 0 ? 'true' : 'false' }}"
                            class="booking-step-tab inline-flex items-center justify-center p-4 border-b-2 border-transparent rounded-t-base hover:text-fg-brand group transition-colors">
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
                <div class="hidden" id="booking-panel-baggage" role="tabpanel" aria-labelledby="booking-tab-baggage" data-step-panel="baggage">
                    <h3 class="text-3xl font-semibold mb-4" style="color: {{ e($sectionStyle['primary_color'] ?? '#5b21b6') }};">{{ e($stepLabelByLogical['baggage'] ?? 'Bagage') }}</h3>
                    <div class="booking-baggage-layout">
                        <div class="space-y-4">
                            <p class="text-sm text-body">Kies je bagage en geef per type het aantal door.</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                                @foreach(($bookingConfig['baggage_items'] ?? []) as $row)
                                @php $key = $row['key'] ?? ''; @endphp
                                <div class="booking-baggage-card rounded-xl border p-4 bg-neutral-primary shadow-xs" style="border-color: {{ e($sectionStyle['primary_color'] ?? '#5b21b6') }}22;">
                                    <div class="space-y-1">
                                        <div class="text-base font-semibold text-heading">{{ e($row['title'] ?? '') }}</div>
                                        @if(!empty($row['subtitle']))<div class="text-sm text-body">{{ e($row['subtitle']) }}</div>@endif
                                        @if(!empty($row['price']) && (float)$row['price'] > 0)<div class="text-xs text-body">+ € {{ number_format((float)$row['price'], 2, ',', '.') }}</div>@endif
                                    </div>
                                    <div class="mt-4 inline-flex items-center gap-2 px-1.5 py-1 rounded-lg bg-neutral-secondary-medium shadow-xs">
                                        <button type="button" class="booking-qty-btn inline-flex items-center justify-center rounded-md border h-8 w-8 border-default-medium bg-neutral-primary text-heading hover:bg-neutral-secondary-soft transition-colors" data-target="baggage.{{ e($key) }}" data-delta="-1">-</button>
                                        <span class="min-w-5 text-center font-semibold text-base leading-none text-heading" data-qty-display="baggage.{{ e($key) }}">0</span>
                                        <button type="button" class="booking-qty-btn inline-flex items-center justify-center rounded-md border h-8 w-8 border-default-medium bg-neutral-primary text-heading hover:bg-neutral-secondary-soft transition-colors" data-target="baggage.{{ e($key) }}" data-delta="1" data-max="{{ (int)($row['max_qty'] ?? 4) }}">+</button>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="rounded-xl border p-4 bg-neutral-primary shadow-xs booking-baggage-special" style="border-color: {{ e($sectionStyle['primary_color'] ?? '#5b21b6') }}22;">
                            <label class="inline-flex items-center gap-3">
                                <input type="checkbox" class="h-5 w-5 rounded border border-default-medium bg-neutral-secondary-medium text-fg-brand focus:ring-2 focus:ring-brand-soft" data-toggle-special-baggage>
                                <span class="text-base font-semibold text-heading">Wil je bijzondere bagage meenemen?</span>
                            </label>
                            <p class="text-sm text-body mt-2">Vink aan en selecteer hieronder het aantal per type.</p>
                            <div class="hidden mt-4 grid grid-cols-1 gap-3" data-special-baggage-wrap>
                                @foreach(($bookingConfig['special_items'] ?? []) as $row)
                                @php $key = $row['key'] ?? ''; @endphp
                                <div class="rounded-lg border p-3 flex items-center justify-between bg-neutral-secondary-medium" style="border-color: {{ e($sectionStyle['primary_color'] ?? '#5b21b6') }}22;">
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
                        <h3 class="text-3xl font-semibold" style="color: {{ e($sectionStyle['primary_color'] ?? '#5b21b6') }};">{{ e($stepLabelByLogical['offers'] ?? 'Aanbiedingen') }}</h3>
                        <div class="text-sm text-slate-600 dark:text-slate-300">Passagiers: <span data-summary-passengers>1</span></div>
                    </div>
                    <div class="space-y-4" data-offers-list></div>
                    <p class="text-sm mt-3 text-slate-600 dark:text-slate-300 hidden" data-offers-empty>Geen aanbiedingen beschikbaar voor de huidige invoer.</p>
                </div>

                <div class="hidden" id="booking-panel-trip" role="tabpanel" aria-labelledby="booking-tab-trip" data-step-panel="trip">
                    <h3 class="text-3xl font-semibold mb-4" style="color: {{ e($sectionStyle['primary_color'] ?? '#5b21b6') }};">{{ e($stepLabelByLogical['trip'] ?? 'Reisgegevens') }}</h3>
                    <div class="booking-trip-layout">
                        <div class="booking-trip-left space-y-5">
                            <label class="block text-base font-semibold text-heading">Waar wil je heen?</label>
                            <div class="booking-route-wrap">
                                <div class="booking-route-icons text-fg-brand shrink-0">
                                    <div class="booking-route-icons-list" data-route-icons-list></div>
                                </div>
                                <div class="booking-route-fields flex-1">
                                    <div class="relative booking-route-field-row" data-route-row="pickup">
                                        <span class="absolute left-5 top-1/2 -translate-y-1/2 text-fg-brand text-base font-semibold leading-none">van</span>
                                        <input type="text" style="padding-left: 70px;" class="booking-route-input-short bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-lg focus:ring-brand focus:border-brand block w-full pe-3 py-3.5 shadow-xs placeholder:text-body" data-field="pickup_address" name="pickup_address" autocomplete="off" autocorrect="off" autocapitalize="none" spellcheck="false" placeholder="{{ e($texts['pickup_placeholder'] ?? 'straatnaam met huisnummer') }}">
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
                                        <span class="absolute left-5 top-1/2 -translate-y-1/2 text-fg-brand text-base font-semibold leading-none">naar</span>
                                        <input type="text" style="padding-left: 70px;" class="booking-route-input-short bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-lg focus:ring-brand focus:border-brand block w-full pe-3 py-3.5 shadow-xs placeholder:text-body" data-field="dropoff_address" name="dropoff_address" autocomplete="off" autocorrect="off" autocapitalize="none" spellcheck="false" placeholder="{{ e($texts['dropoff_placeholder'] ?? 'straatnaam met huisnummer') }}">
                                    </div>
                                </div>
                            </div>
                            <div class="pt-1">
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
                                <div class="mt-3 hidden rounded-lg border border-violet-300/60 dark:border-violet-500/40 bg-violet-50/80 dark:bg-violet-500/10 px-3 py-2.5 text-base text-violet-900 dark:text-violet-100 shadow-xs" data-route-details-banner>
                                    <div class="text-lg font-semibold mb-0.5">Route informatie</div>
                                    <div data-route-details></div>
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
                                    <span class="booking-datetime-placeholder absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-400 text-sm leading-tight pointer-events-none truncate" style="left: 70px;" data-datetime-placeholder-for="pickup_at">{{ e($texts['pickup_datetime_placeholder'] ?? 'Selecteer datum en tijd') }}</span>
                                </div>
                            </div>
                            <div>
                                <label class="inline-flex items-center gap-2">
                                    <input type="checkbox" class="w-4 h-4 border border-default-medium rounded-xs bg-neutral-secondary-medium focus:ring-2 focus:ring-brand-soft" data-field="return_trip" {{ !empty($logic['return_enabled_by_default']) ? 'checked' : '' }}>
                                    <span class="text-heading">Retour</span>
                                </label>
                                <div class="relative mt-3 booking-datetime-wrap">
                                    <svg class="w-5 h-5 text-fg-brand absolute top-1/2 -translate-y-1/2 pointer-events-none z-10 ml-2" style="left: 3px;" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 2v3m8-3v3M3 9h18M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/>
                                    </svg>
                                    <input type="datetime-local" style="padding-left: 70px; width: 300px; max-width: 100%;" class="booking-datetime-input bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-lg focus:ring-brand focus:border-brand block w-auto pe-3 py-2.5 shadow-xs cursor-pointer disabled:opacity-70" data-field="return_at" data-datetime-input data-placeholder-target="return_at" placeholder="{{ e($texts['return_datetime_placeholder'] ?? 'Selecteer datum en tijd') }}" {{ !empty($logic['return_enabled_by_default']) ? '' : 'disabled' }}>
                                    <span class="booking-datetime-placeholder absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-400 text-sm leading-tight pointer-events-none truncate" style="left: 70px;" data-datetime-placeholder-for="return_at">{{ e($texts['return_datetime_placeholder'] ?? 'Selecteer datum en tijd') }}</span>
                                </div>
                            </div>
                            <div>
                                <label class="block mb-2.5 text-sm font-medium text-heading">Aantal reizigers</label>
                                <div class="mt-1 inline-flex items-center gap-2 px-1.5 py-1 rounded-lg border border-default-medium bg-neutral-secondary-medium shadow-xs">
                                    <button type="button" class="booking-passenger-btn inline-flex items-center justify-center rounded-base h-8 w-8 text-heading hover:bg-neutral-secondary-soft transition-colors" data-delta="-1">-</button>
                                    <span class="min-w-4 text-center font-semibold text-base leading-none text-heading" data-passengers-display>{{ (int)($logic['default_passengers'] ?? 1) }}</span>
                                    <button type="button" class="booking-passenger-btn inline-flex items-center justify-center rounded-base h-8 w-8 text-fg-brand hover:bg-neutral-secondary-soft transition-colors" data-delta="1">+</button>
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
                    <h3 class="text-3xl font-semibold mb-4" style="color: {{ e($sectionStyle['primary_color'] ?? '#5b21b6') }};">{{ e($stepLabelByLogical['contact'] ?? 'Contactgegevens') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label class="block mb-2.5 text-sm font-medium text-heading">Voornaam</label><input type="text" class="mt-1 bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-lg focus:ring-brand focus:border-brand block w-full px-3 py-2.5 shadow-xs placeholder:text-body" data-field="first_name"></div>
                        <div><label class="block mb-2.5 text-sm font-medium text-heading">Achternaam</label><input type="text" class="mt-1 bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-lg focus:ring-brand focus:border-brand block w-full px-3 py-2.5 shadow-xs placeholder:text-body" data-field="last_name"></div>
                        <div><label class="block mb-2.5 text-sm font-medium text-heading">Telefoonnummer</label><input type="text" class="mt-1 bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-lg focus:ring-brand focus:border-brand block w-full px-3 py-2.5 shadow-xs placeholder:text-body" data-field="phone"></div>
                        <div><label class="block mb-2.5 text-sm font-medium text-heading">E-mailadres</label><input type="email" class="mt-1 bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-lg focus:ring-brand focus:border-brand block w-full px-3 py-2.5 shadow-xs placeholder:text-body" data-field="email"></div>
                    </div>
                </div>

                <div class="hidden" id="booking-panel-confirm" role="tabpanel" aria-labelledby="booking-tab-confirm" data-step-panel="confirm">
                    <h3 class="text-3xl font-semibold mb-4" style="color: {{ e($sectionStyle['primary_color'] ?? '#5b21b6') }};">{{ e($stepLabelByLogical['confirm'] ?? 'Bevestiging') }}</h3>
                    <div class="rounded-xl border shadow-sm bg-transparent overflow-hidden" style="border-color: rgba(148, 163, 184, 0.45);">
                        <div class="kt-card-content lg:pt-9 lg:pb-7.5 px-4 py-4 md:px-6 md:py-5 space-y-5">
                            <div class="hidden flex justify-center" data-summary-vehicle-image-wrap>
                                <div class="w-full max-w-md rounded-lg border bg-neutral-secondary-medium p-0 overflow-hidden" style="border-color: rgba(148, 163, 184, 0.45); box-shadow: 0 26px 58px rgba(15, 23, 42, 0.5), 0 10px 24px rgba(15, 23, 42, 0.28);">
                                    <img src="" alt="" class="w-full h-44 md:h-52 object-cover rounded-md mx-auto" data-summary-vehicle-image>
                                </div>
                            </div>
                            <div>
                                <div class="grid justify-center gap-1.5 mb-2 text-center">
                                    <div class="text-sm uppercase tracking-wide text-slate-500 dark:text-slate-400 font-semibold">Rit</div>
                                </div>
                                <div class="rounded-lg border bg-neutral-secondary-medium px-3 py-3 md:px-4 md:py-3 shadow-xs" style="border-color: rgba(148, 163, 184, 0.45);">
                                    <div class="flex items-start justify-between gap-4 md:gap-6">
                                        <div class="min-w-0 flex-1">
                                            <div class="text-sm md:text-base uppercase tracking-wide text-slate-500 dark:text-slate-300 font-bold mb-1">Van</div>
                                            <div class="text-base font-semibold text-heading truncate" data-summary-pickup-line1>—</div>
                                            <div class="text-base text-body truncate" data-summary-pickup-line2>—</div>
                                            <div class="text-base text-body truncate" data-summary-pickup-line3>—</div>
                                        </div>
                                        <div class="inline-flex items-center justify-center text-fg-brand shrink-0 pt-6" aria-hidden="true">
                                            <svg class="w-8 h-8 md:w-10 md:h-10" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m0 0-4-4m4 4-4 4"/>
                                            </svg>
                                        </div>
                                        <div class="min-w-0 flex-1 text-right">
                                            <div class="text-sm md:text-base uppercase tracking-wide text-slate-500 dark:text-slate-300 font-bold mb-1">Naar</div>
                                            <div class="text-base font-semibold text-heading truncate" data-summary-dropoff-line1>—</div>
                                            <div class="text-base text-body truncate" data-summary-dropoff-line2>—</div>
                                            <div class="text-base text-body truncate" data-summary-dropoff-line3>—</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="grid justify-center gap-1.5 mb-1 text-center">
                                <div class="text-sm uppercase tracking-wide text-slate-500 dark:text-slate-400 font-semibold">Ophaalmoment</div>
                                <div class="text-lg md:text-xl font-bold text-heading" data-summary-pickup-at>—</div>
                            </div>

                            <div class="rounded-lg bg-neutral-secondary-medium px-3 py-3 md:px-4 md:py-3 shadow-xs text-center">
                                <div class="text-sm uppercase tracking-wide text-slate-500 dark:text-slate-400 font-semibold">Aanbieding</div>
                                <div class="mt-1 text-base md:text-lg font-semibold text-heading text-center" data-summary-offer>—</div>
                            </div>
                        </div>

                        <div class="border-t px-4 py-4 md:px-6 md:py-5 text-center" style="border-color: rgba(148, 163, 184, 0.45);">
                            <div class="text-sm uppercase tracking-wide text-slate-500 dark:text-slate-400 font-semibold">Totaalbedrag</div>
                            <div class="mt-1 text-3xl md:text-4xl font-extrabold text-violet-700 dark:text-violet-300" data-summary-total>—</div>
                        </div>
                    </div>
                    <p class="text-sm mt-3 text-slate-600 dark:text-slate-300">Controleer je gegevens en verstuur je boeking.</p>
                </div>
            </div>

            <div class="mt-8 flex items-center justify-between">
                <button type="button" class="inline-flex justify-center items-center px-6 py-3 text-sm font-bold border-2 rounded-lg transition-all duration-200 hover:bg-white/15 hover:shadow-xl hover:-translate-y-1" style="background-color: transparent; border-color: color-mix(in srgb, {{ e($sectionStyle['primary_color'] ?? '#5b21b6') }} 45%, transparent); color: {{ e($sectionStyle['primary_color'] ?? '#5b21b6') }};" data-booking-prev>&larr; terug</button>
                <button type="button" class="inline-flex justify-center items-center px-6 py-3 text-sm font-bold border-2 rounded-lg transition-all duration-200 hover:bg-white/15 hover:shadow-xl hover:-translate-y-1" style="background-color: transparent; border-color: color-mix(in srgb, {{ e($sectionStyle['primary_color'] ?? '#5b21b6') }} 45%, transparent); color: {{ e($sectionStyle['primary_color'] ?? '#5b21b6') }};" data-booking-next>Verder</button>
            </div>
            <p class="mt-3 text-sm font-medium text-red-600 dark:text-red-300 hidden" data-booking-error></p>
            <p class="mt-3 text-sm font-medium text-green-700 dark:text-green-300 hidden" data-booking-success></p>
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
[data-taxiroyaal-booking-module] .booking-trip-layout {
    display: grid;
    grid-template-columns: 1fr;
    gap: 24px;
}

[data-taxiroyaal-booking-module] [data-booking-success-modal] {
    animation: bookingFadeIn 180ms ease-out;
}

html.booking-modal-open,
body.booking-modal-open {
    overflow: hidden !important;
}

@keyframes bookingFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

[data-taxiroyaal-booking-module] .booking-baggage-layout {
    display: grid;
    grid-template-columns: 1fr;
    gap: 24px;
}

[data-taxiroyaal-booking-module] .booking-baggage-card {
    min-height: 152px;
}

[data-taxiroyaal-booking-module] .booking-trip-left,
[data-taxiroyaal-booking-module] .booking-trip-right {
    min-width: 0;
}

[data-taxiroyaal-booking-module] .booking-trip-right {
    padding-left: 0;
    border-left: 0;
}

[data-taxiroyaal-booking-module] .booking-route-wrap {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    column-gap: 12px;
    align-items: stretch;
}

[data-taxiroyaal-booking-module] .booking-route-icons {
    width: 24px;
    position: relative;
}

[data-taxiroyaal-booking-module] .booking-route-icons-list {
    position: relative;
    width: 24px;
    min-height: 100%;
}

[data-taxiroyaal-booking-module] .booking-route-icon-row {
    width: 24px;
    height: 24px;
    position: absolute;
    left: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

[data-taxiroyaal-booking-module] .booking-route-icon-row--field {
    height: 24px;
}

[data-taxiroyaal-booking-module] .booking-route-icon-row--middle {
    height: 24px;
}

[data-taxiroyaal-booking-module] .booking-route-icon-connector {
    position: absolute;
    left: 11px;
    width: 2px;
    height: 20px;
    border-radius: 999px;
    background: currentColor;
    opacity: 0.4;
    margin: 0;
}

[data-taxiroyaal-booking-module] .booking-route-fields {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

[data-taxiroyaal-booking-module] .booking-route-field-row {
    min-height: 24px;
    display: flex;
    align-items: center;
}

[data-taxiroyaal-booking-module] .booking-stopover-row {
    position: relative;
}

[data-taxiroyaal-booking-module] [data-stopovers-list] {
    display: contents;
}

[data-taxiroyaal-booking-module] .booking-route-fields input {
    width: 100%;
}

[data-taxiroyaal-booking-module] .booking-address-suggestions-panel {
    position: absolute;
    left: 0;
    right: 0;
    top: calc(100% + 6px);
    z-index: 40;
    width: 100%;
    border: 1px solid rgba(148, 163, 184, 0.55);
    border-radius: 10px;
    background: #d1d5db;
    box-shadow: 0 14px 34px rgba(2, 6, 23, 0.25);
    max-height: 320px;
    overflow: auto;
}

[data-taxiroyaal-booking-module] .dark .booking-address-suggestions-panel,
.dark [data-taxiroyaal-booking-module] .booking-address-suggestions-panel {
    background: #1e293b;
    border-color: rgba(148, 163, 184, 0.35);
    box-shadow: 0 16px 36px rgba(2, 6, 23, 0.6);
}

[data-taxiroyaal-booking-module] .booking-address-suggestion-item {
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

[data-taxiroyaal-booking-module] .booking-address-suggestion-item:hover {
    background: rgba(91, 33, 182, 0.08);
}

[data-taxiroyaal-booking-module] .dark .booking-address-suggestion-item,
.dark [data-taxiroyaal-booking-module] .booking-address-suggestion-item {
    color: #e2e8f0;
    border-bottom-color: rgba(148, 163, 184, 0.22);
}

[data-taxiroyaal-booking-module] .dark .booking-address-suggestion-item:hover,
.dark [data-taxiroyaal-booking-module] .booking-address-suggestion-item:hover {
    background: rgba(148, 163, 184, 0.16);
}

[data-taxiroyaal-booking-module] .booking-route-fields input.booking-route-input-short {
    width: 100% !important;
    color: #0f172a;
    border-color: rgba(148, 163, 184, 0.45) !important;
}

[data-taxiroyaal-booking-module] input.border-default-medium,
[data-taxiroyaal-booking-module] textarea.border-default-medium,
[data-taxiroyaal-booking-module] select.border-default-medium {
    border-color: rgba(148, 163, 184, 0.45) !important;
}

[data-taxiroyaal-booking-module] .border-default-medium {
    border-color: rgba(148, 163, 184, 0.45) !important;
}

[data-taxiroyaal-booking-module] input.border-default-medium:focus,
[data-taxiroyaal-booking-module] textarea.border-default-medium:focus,
[data-taxiroyaal-booking-module] select.border-default-medium:focus {
    border-color: rgba(148, 163, 184, 0.45) !important;
}

[data-taxiroyaal-booking-module] .booking-route-fields input.booking-route-input-short.is-selected {
    border-color: rgba(148, 163, 184, 0.45) !important;
}

[data-taxiroyaal-booking-module] .booking-route-fields input.booking-route-input-short::placeholder {
    color: #64748b;
}

[data-taxiroyaal-booking-module] .dark .booking-route-fields input.booking-route-input-short,
.dark [data-taxiroyaal-booking-module] .booking-route-fields input.booking-route-input-short {
    color: #e2e8f0;
}

[data-taxiroyaal-booking-module] .dark .booking-route-fields input.booking-route-input-short::placeholder,
.dark [data-taxiroyaal-booking-module] .booking-route-fields input.booking-route-input-short::placeholder {
    color: #94a3b8;
}

[data-taxiroyaal-booking-module] .booking-route-fields input.booking-route-input-short:focus,
[data-taxiroyaal-booking-module] .booking-route-fields input.booking-route-input-short.is-selected:focus,
[data-taxiroyaal-booking-module] .booking-route-fields input.booking-route-input-short:focus-visible,
[data-taxiroyaal-booking-module] .booking-route-fields input.booking-route-input-short.is-selected:focus-visible {
    border-color: rgba(148, 163, 184, 0.45) !important;
    box-shadow: none !important;
    outline: none !important;
}

[data-taxiroyaal-booking-module] .dark .booking-route-fields input.booking-route-input-short.is-selected,
.dark [data-taxiroyaal-booking-module] .booking-route-fields input.booking-route-input-short.is-selected {
    color: #ffffff !important;
}

[data-taxiroyaal-booking-module] .booking-route-fields input.booking-route-input-short:-webkit-autofill,
[data-taxiroyaal-booking-module] .booking-route-fields input.booking-route-input-short:-webkit-autofill:hover,
[data-taxiroyaal-booking-module] .booking-route-fields input.booking-route-input-short:-webkit-autofill:focus {
    -webkit-text-fill-color: inherit;
    -webkit-box-shadow: 0 0 0px 1000px rgba(148, 163, 184, 0.16) inset !important;
    box-shadow: 0 0 0px 1000px rgba(148, 163, 184, 0.16) inset !important;
    transition: background-color 9999s ease-in-out 0s;
}

[data-taxiroyaal-booking-module] .booking-route-fields input.booking-route-input-short.is-selected:-webkit-autofill,
[data-taxiroyaal-booking-module] .booking-route-fields input.booking-route-input-short.is-selected:-webkit-autofill:hover,
[data-taxiroyaal-booking-module] .booking-route-fields input.booking-route-input-short.is-selected:-webkit-autofill:focus {
    -webkit-text-fill-color: #ffffff !important;
}

[data-taxiroyaal-booking-module] .booking-datetime-input {
    border-color: rgba(148, 163, 184, 0.45) !important;
}

[data-taxiroyaal-booking-module] .booking-datetime-input:focus {
    border-color: rgba(148, 163, 184, 0.45) !important;
    box-shadow: none !important;
}

@media (min-width: 1024px) {
    [data-taxiroyaal-booking-module] .booking-trip-layout {
        grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.8fr);
        gap: 28px;
    }
    [data-taxiroyaal-booking-module] .booking-trip-right {
        border-left: 1px solid rgba(148, 163, 184, 0.35);
        padding-left: 24px;
    }
    [data-taxiroyaal-booking-module] .booking-route-wrap {
        grid-template-columns: auto minmax(0, 1fr);
        column-gap: 12px;
    }
    [data-taxiroyaal-booking-module] .booking-baggage-layout {
        grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.8fr);
        gap: 28px;
    }
    [data-taxiroyaal-booking-module] .booking-baggage-special {
        border-left: 1px solid rgba(148, 163, 184, 0.35);
        padding-left: 24px;
    }
}

[data-taxiroyaal-booking-module] [data-step-panel] .rounded-xl,
[data-taxiroyaal-booking-module] [data-step-panel] .rounded-lg {
    color: #0f172a;
}
.dark [data-taxiroyaal-booking-module] [data-step-panel] .rounded-xl,
.dark [data-taxiroyaal-booking-module] [data-step-panel] .rounded-lg {
    color: #f8fafc;
}
[data-taxiroyaal-booking-module] .booking-datetime-input::-webkit-calendar-picker-indicator {
    opacity: 0;
    width: 0;
    margin: 0;
    pointer-events: none;
}
[data-taxiroyaal-booking-module] .booking-datetime-input {
    border-color: rgba(148, 163, 184, 0.45) !important;
    color: transparent !important;
    -webkit-text-fill-color: transparent !important;
    caret-color: transparent;
    text-shadow: none;
}
.dark [data-taxiroyaal-booking-module] .booking-datetime-input {
    border-color: rgba(148, 163, 184, 0.55) !important;
}
[data-taxiroyaal-booking-module] .booking-datetime-input::-webkit-clear-button,
[data-taxiroyaal-booking-module] .booking-datetime-input::-webkit-inner-spin-button {
    display: none;
}
[data-taxiroyaal-booking-module] .booking-datetime-input,
[data-taxiroyaal-booking-module] .booking-datetime-input::-webkit-datetime-edit,
[data-taxiroyaal-booking-module] .booking-datetime-input::-webkit-datetime-edit-fields-wrapper,
[data-taxiroyaal-booking-module] .booking-datetime-input::-webkit-datetime-edit-text,
[data-taxiroyaal-booking-module] .booking-datetime-input::-webkit-datetime-edit-month-field,
[data-taxiroyaal-booking-module] .booking-datetime-input::-webkit-datetime-edit-day-field,
[data-taxiroyaal-booking-module] .booking-datetime-input::-webkit-datetime-edit-year-field,
[data-taxiroyaal-booking-module] .booking-datetime-input::-webkit-datetime-edit-hour-field,
[data-taxiroyaal-booking-module] .booking-datetime-input::-webkit-datetime-edit-minute-field,
[data-taxiroyaal-booking-module] .booking-datetime-input::-webkit-datetime-edit-ampm-field {
    color: transparent !important;
    -webkit-text-fill-color: transparent !important;
}
[data-taxiroyaal-booking-module] .booking-datetime-input::-webkit-datetime-edit {
    display: none;
}
.dark [data-taxiroyaal-booking-module] .booking-datetime-input {
    color-scheme: dark;
}
.dark [data-taxiroyaal-booking-module] .booking-datetime-input::-webkit-calendar-picker-indicator {
    opacity: 0;
}

[data-taxiroyaal-booking-module] input[type="checkbox"] {
    accent-color: #6366f1;
    border-color: rgba(148, 163, 184, 0.55) !important;
    background-color: rgba(148, 163, 184, 0.14);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.14);
    transition: border-color 160ms ease, box-shadow 160ms ease, background-color 160ms ease, transform 120ms ease;
}

[data-taxiroyaal-booking-module] input[type="checkbox"]:hover {
    border-color: rgba(99, 102, 241, 0.58) !important;
}

[data-taxiroyaal-booking-module] input[type="checkbox"]:focus-visible {
    outline: none;
    border-color: rgba(99, 102, 241, 0.65) !important;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
}

[data-taxiroyaal-booking-module] input[type="checkbox"]:checked {
    border-color: rgba(99, 102, 241, 0.72) !important;
    background-color: rgba(99, 102, 241, 0.22);
}
</style>

@push('scripts')
<script>
(function() {
    var root = document.querySelector('[data-taxiroyaal-booking-module]');
    if (!root) return;

    var config = @json($bookingConfig);
    var quoteUrl = @json(route('taxiroyaal.booking.quote'));
    var submitUrl = @json(route('taxiroyaal.booking.submit'));
    var pageId = @json($bookingPageId);
    var sectionKey = @json($sectionKey ?? 'component:taxiroyaal.boekingsmodule');
    var mapsApiKey = @json($mapsApiKey);
    var activeTabColor = @json($sectionStyle['active_tab_color'] ?? '#5b21b6');
    var stepOrder = Array.isArray(config.step_order) && config.step_order.length
        ? config.step_order.slice(0, 5)
        : ['trip', 'baggage', 'offers', 'contact', 'confirm'];
    ['trip', 'baggage', 'offers', 'contact', 'confirm'].forEach(function(stepKey) {
        if (stepOrder.indexOf(stepKey) === -1) stepOrder.push(stepKey);
    });
    stepOrder = stepOrder.slice(0, 5);

    var state = {
        step: 1,
        has_baggage: true,
        passengers: parseInt(config.logic && config.logic.default_passengers ? config.logic.default_passengers : 1, 10),
        minPassengers: parseInt(config.logic && config.logic.min_passengers ? config.logic.min_passengers : 1, 10),
        maxPassengers: parseInt(config.logic && config.logic.max_passengers ? config.logic.max_passengers : 8, 10),
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

    function showError(message) {
        var el = root.querySelector('[data-booking-error]');
        if (!el) return;
        el.textContent = message || 'Er ging iets mis.';
        el.classList.remove('hidden');
    }

    function clearError() {
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

    function setStepByKey(stepKey) {
        var idx = stepOrder.indexOf(stepKey);
        if (idx === -1) return;
        setStep(idx + 1);
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
        var baggageOption = root.querySelector('[data-booking-step-select] option[value="baggage"]');
        if (baggageOption) {
            baggageOption.hidden = !state.has_baggage;
            baggageOption.disabled = !state.has_baggage;
        }
        if (!state.has_baggage && getCurrentStepKey() === 'baggage') {
            setStepByKey('offers');
        }
    }

    function syncBaggageChoiceFromUi() {
        var selected = root.querySelector('input[name="booking_has_baggage_ui"]:checked');
        state.has_baggage = selected ? selected.value === 'yes' : true;
        updateBaggageStepAvailability();
    }

    function renderRouteDetailsText(text) {
        var details = root.querySelector('[data-route-details]');
        var banner = root.querySelector('[data-route-details-banner]');
        if (!details || !banner) return;
        var normalized = String(text || '').trim();
        details.textContent = normalized;
        banner.classList.toggle('hidden', normalized === '');
    }

    function setStep(nextStep) {
        state.step = Math.max(1, Math.min(stepOrder.length, nextStep));
        var currentStepKey = getCurrentStepKey();
        root.querySelectorAll('[data-step-panel]').forEach(function(panel) {
            panel.classList.toggle('hidden', panel.getAttribute('data-step-panel') !== currentStepKey);
        });
        root.querySelectorAll('.booking-step-tab').forEach(function(tab) {
            var active = parseInt(tab.getAttribute('data-step-index'), 10) === state.step;
            tab.classList.toggle('font-semibold', active);
            tab.classList.toggle('text-heading', active);
            tab.classList.toggle('text-fg-brand', active);
            tab.classList.toggle('active', active);
            tab.classList.toggle('font-medium', !active);
            tab.classList.toggle('text-body', !active);
            tab.style.backgroundColor = 'transparent';
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
        });
        var stepSelect = root.querySelector('[data-booking-step-select]');
        if (stepSelect) stepSelect.value = currentStepKey;
        var nextBtn = root.querySelector('[data-booking-next]');
        if (nextBtn) {
            nextBtn.textContent = currentStepKey === 'confirm'
                ? (config.texts && config.texts.submit_button_text ? config.texts.submit_button_text : 'Boeking versturen')
                : 'Verder';
        }
        var prevBtn = root.querySelector('[data-booking-prev]');
        if (prevBtn) prevBtn.style.visibility = state.step === 1 ? 'hidden' : 'visible';
        if (currentStepKey === 'trip') {
            window.requestAnimationFrame(syncRouteIconAlignment);
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
        syncStopoverHint();
        syncReturnTripUi();
        syncDateTimePlaceholder();
        syncBaggageChoiceFromUi();
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
            var fieldRect = fieldRow.getBoundingClientRect();
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
                html += '<button type="button" class="booking-stopover-toggle inline-flex items-center justify-center w-6 h-6 rounded-full border border-current bg-transparent" aria-label="Tussenstop toevoegen"><svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 5v14m-7-7h14"/></svg></button>';
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
        if (!list) return;
        list.appendChild(createStopoverRow(initialValue || ''));
        syncStateFromFields();
        setupStopoverAutocompletes();
    }

    function setupStopoverAutocompletes() {
        if (!window.google || !google.maps || !google.maps.places) return;
        var options = {
            componentRestrictions: { country: (config.maps && config.maps.country ? config.maps.country : 'nl') },
            fields: ['formatted_address', 'geometry'],
        };
        root.querySelectorAll('[data-stopover-input]').forEach(function(input, idx) {
            if (input.dataset.autocompleteBound === '1') return;
            var ac = new google.maps.places.Autocomplete(input, options);
            ac.addListener('place_changed', function() {
                var place = ac.getPlace();
                if (!place) return;
                input.value = place.formatted_address || input.value;
                state.stopovers_geo[idx] = {
                    lat: place.geometry && place.geometry.location ? place.geometry.location.lat() : null,
                    lng: place.geometry && place.geometry.location ? place.geometry.location.lng() : null,
                };
                syncStateFromFields();
                if (window.__taxiroyaalBookingRouteCalc) {
                    window.__taxiroyaalBookingRouteCalc();
                }
            });
            input.dataset.autocompleteBound = '1';
        });
    }

    function syncReturnTripUi() {
        var returnTripEnabled = !!state.return_trip;
        var returnInput = root.querySelector('[data-field="return_at"]');
        var returnPlaceholder = root.querySelector('[data-datetime-placeholder-for="return_at"]');
        if (!returnInput) return;
        returnInput.disabled = !returnTripEnabled;
        if (!returnTripEnabled) {
            returnInput.value = '';
            state.return_at = '';
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

    function renderOffers() {
        var list = root.querySelector('[data-offers-list]');
        var empty = root.querySelector('[data-offers-empty]');
        if (!list) return;
        list.innerHTML = '';
        if (!state.offers.length) {
            if (empty) empty.classList.remove('hidden');
            return;
        }
        if (empty) empty.classList.add('hidden');
        state.offers.forEach(function(offer) {
            var active = state.selected_offer_id === offer.id;
            var card = document.createElement('div');
            card.className = 'rounded-xl border p-5 md:p-6 flex flex-col md:flex-row gap-4 items-center justify-between transition-all duration-200 bg-neutral-primary cursor-pointer ' + (active ? 'border-violet-500 dark:border-violet-400 shadow-lg ring-2 ring-violet-300/60 dark:ring-violet-500/40' : 'border-slate-300/60 dark:border-slate-600 shadow-xs hover:border-violet-300/70 dark:hover:border-violet-500/60');
            card.setAttribute('role', 'button');
            card.setAttribute('tabindex', '0');
            card.setAttribute('aria-pressed', active ? 'true' : 'false');
            card.setAttribute('data-offer-id', offer.id || '');
            var badge = state.offer_display_mode === 'person_range' ? '' : (offer.badge || '');
            card.innerHTML =
                '<div class="flex items-center gap-4 w-full md:w-auto">' +
                    (offer.image_url ? '<img src="' + offer.image_url + '" alt="' + (offer.title || '') + '" class="h-24 w-40 object-cover rounded-lg">' : '') +
                    '<div>' +
                        (badge ? '<div class="inline-flex mb-1 items-center rounded-full border border-violet-300/60 dark:border-violet-500/40 px-2.5 py-1 text-xs font-semibold text-violet-700 dark:text-violet-300">' + badge + '</div>' : '') +
                        '<div class="text-2xl font-bold text-slate-900 dark:text-slate-100">' + (offer.title || '') + '</div>' +
                        '<ul class="text-sm text-slate-600 dark:text-slate-300">' + ((offer.features || []).map(function(f) { return '<li>✓ ' + f + '</li>'; }).join('')) + '</ul>' +
                    '</div>' +
                '</div>' +
                '<div class="text-right w-full md:w-auto">' +
                    (offer.old_price ? '<div class="text-sm font-medium text-slate-500 dark:text-slate-400">van <span style="text-decoration: line-through; text-decoration-thickness: 2px; text-decoration-color: rgb(244 63 94 / 0.8);">' + formatEuro(offer.old_price) + '</span></div>' : '') +
                    '<div class="text-4xl font-bold text-violet-700 dark:text-violet-300">' + formatEuro(offer.price) + '</div>' +
                '</div>';
            list.appendChild(card);
        });
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
        function splitAddressLines(rawAddress) {
            var compact = compactAddress(rawAddress || '');
            if (!compact) {
                return ['—', '—', '—'];
            }
            var parts = compact.split(',').map(function(part) { return part.trim(); }).filter(Boolean);
            var line1 = parts[0] || '—';
            var line2 = parts[1] || '—';
            var line3 = parts.slice(2).join(', ') || '—';
            return [line1, line2, line3];
        }

        var pickupLines = splitAddressLines(state.pickup_address || '');
        var dropoffLines = splitAddressLines(state.dropoff_address || '');

        var pickupLine1El = root.querySelector('[data-summary-pickup-line1]');
        var pickupLine2El = root.querySelector('[data-summary-pickup-line2]');
        var pickupLine3El = root.querySelector('[data-summary-pickup-line3]');
        var dropoffLine1El = root.querySelector('[data-summary-dropoff-line1]');
        var dropoffLine2El = root.querySelector('[data-summary-dropoff-line2]');
        var dropoffLine3El = root.querySelector('[data-summary-dropoff-line3]');

        if (pickupLine1El) pickupLine1El.textContent = pickupLines[0];
        if (pickupLine2El) pickupLine2El.textContent = pickupLines[1];
        if (pickupLine3El) pickupLine3El.textContent = pickupLines[2];
        if (dropoffLine1El) dropoffLine1El.textContent = dropoffLines[0];
        if (dropoffLine2El) dropoffLine2El.textContent = dropoffLines[1];
        if (dropoffLine3El) dropoffLine3El.textContent = dropoffLines[2];

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

        var totalEl = root.querySelector('[data-summary-total]');
        if (totalEl) totalEl.textContent = total;
        var offerEl = root.querySelector('[data-summary-offer]');
        if (offerEl) offerEl.textContent = offerName;
        var pickupEl = root.querySelector('[data-summary-pickup-at]');
        if (pickupEl) pickupEl.textContent = pickupAt;
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
            if (!state.offers.some(function(offer) { return offer.id === state.selected_offer_id; })) {
                state.selected_offer_id = state.offers[0] ? state.offers[0].id : null;
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

    function fetchNominatimCoordinates(address) {
        var key = String(address || '').trim().toLowerCase();
        if (!key) return Promise.resolve(null);
        if (geocodeCache.has(key)) return Promise.resolve(geocodeCache.get(key));
        return fetch(
            'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&countrycodes='
            + encodeURIComponent((config.maps && config.maps.country ? String(config.maps.country) : 'nl').toLowerCase())
            + '&q=' + encodeURIComponent(address)
        )
            .then(function(res) { return res.ok ? res.json() : []; })
            .then(function(rows) {
                var row = Array.isArray(rows) && rows[0] ? rows[0] : null;
                if (!row) return null;
                var coord = {
                    lat: parseFloat(row.lat || '0'),
                    lng: parseFloat(row.lon || '0')
                };
                if (!isFinite(coord.lat) || !isFinite(coord.lng)) return null;
                geocodeCache.set(key, coord);
                return coord;
            })
            .catch(function() { return null; });
    }

    function calculateRouteFallback() {
        if (!state.pickup_address || !state.dropoff_address) {
            requestQuotes();
            return;
        }

        var seq = ++routeCalcSeq;
        var addressChain = [state.pickup_address]
            .concat((state.stopovers || []).filter(function(s) { return String(s || '').trim() !== ''; }))
            .concat([state.dropoff_address]);

        Promise.all(addressChain.map(fetchNominatimCoordinates))
            .then(function(points) {
                if (seq !== routeCalcSeq) return;
                if (!Array.isArray(points) || points.some(function(p) { return !p; })) {
                    requestQuotes();
                    return;
                }
                var coordsPath = points.map(function(p) { return p.lng + ',' + p.lat; }).join(';');
                return fetch('https://router.project-osrm.org/route/v1/driving/' + coordsPath + '?overview=false')
                    .then(function(res) { return res.ok ? res.json() : null; })
                    .then(function(payload) {
                        if (seq !== routeCalcSeq) return;
                        var route = payload && Array.isArray(payload.routes) && payload.routes[0] ? payload.routes[0] : null;
                        if (!route) {
                            requestQuotes();
                            return;
                        }
                        state.distance_meters = Math.max(0, Math.round(parseFloat(route.distance || 0)));
                        state.duration_seconds = Math.max(0, Math.round(parseFloat(route.duration || 0)));
                        var km = (state.distance_meters / 1000).toFixed(1).replace('.', ',');
                        var min = Math.round(state.duration_seconds / 60);
                        renderRouteDetailsText('Route: ' + km + ' km • Reistijd: ' + min + ' min');
                        requestQuotes();
                    })
                    .catch(function() {
                        requestQuotes();
                    });
            })
            .catch(function() {
                requestQuotes();
            });
    }

    function recalculateRouteOrQuote() {
        if (window.__taxiroyaalBookingRouteCalc && state.pickup_address && state.dropoff_address) {
            window.__taxiroyaalBookingRouteCalc();
            return;
        }
        requestQuotes();
    }

    function validateCurrentStep() {
        var currentStepKey = getCurrentStepKey();
        if (currentStepKey === 'offers' && !state.selected_offer_id) {
            showError('Selecteer eerst een aanbieding.');
            return false;
        }
        if (currentStepKey === 'trip') {
            if (!state.pickup_address || !state.dropoff_address || !state.pickup_at) {
                showError('Vul ophaaladres, afzetadres en ophaalmoment in.');
                return false;
            }
            if (state.return_trip && !state.return_at) {
                showError('Vul ook het retourmoment in.');
                return false;
            }
        }
        if (currentStepKey === 'contact') {
            if (!state.first_name || !state.last_name || !state.phone) {
                showError('Voornaam, achternaam en telefoonnummer zijn verplicht.');
                return false;
            }
        }
        clearError();
        return true;
    }

    function submitBooking() {
        clearError();
        var payload = {
            page_id: pageId,
            section_key: sectionKey,
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
            showSuccess(data.message || (config.texts && config.texts.success_message ? config.texts.success_message : 'Bedankt! Je boeking is ontvangen.'));
        })
        .catch(function(error) {
            showError(error.message || 'Boeking versturen mislukt');
        });
    }

    function initGoogleMaps() {
        if (!mapsApiKey) return;
        function startAutocomplete() {
            if (!window.google || !google.maps || !google.maps.places) return;
            var pickupInput = root.querySelector('[data-field="pickup_address"]');
            var dropoffInput = root.querySelector('[data-field="dropoff_address"]');
            if (!pickupInput || !dropoffInput) return;
            var options = {
                componentRestrictions: { country: (config.maps && config.maps.country ? config.maps.country : 'nl') },
                fields: ['formatted_address', 'geometry'],
                types: ['address'],
            };
            var acPickup = new google.maps.places.Autocomplete(pickupInput, options);
            var acDropoff = new google.maps.places.Autocomplete(dropoffInput, options);

            function handlePlaceUpdate(which, place) {
                if (!place) return;
                if (which === 'pickup') {
                    state.pickup_address = compactAddress(place.formatted_address || pickupInput.value);
                    state.pickup_lat = place.geometry && place.geometry.location ? place.geometry.location.lat() : null;
                    state.pickup_lng = place.geometry && place.geometry.location ? place.geometry.location.lng() : null;
                    pickupInput.value = state.pickup_address || pickupInput.value;
                } else {
                    state.dropoff_address = compactAddress(place.formatted_address || dropoffInput.value);
                    state.dropoff_lat = place.geometry && place.geometry.location ? place.geometry.location.lat() : null;
                    state.dropoff_lng = place.geometry && place.geometry.location ? place.geometry.location.lng() : null;
                    dropoffInput.value = state.dropoff_address || dropoffInput.value;
                }
                calculateRoute();
            }

            acPickup.addListener('place_changed', function() { handlePlaceUpdate('pickup', acPickup.getPlace()); });
            acDropoff.addListener('place_changed', function() { handlePlaceUpdate('dropoff', acDropoff.getPlace()); });
            setupStopoverAutocompletes();
            // If fields already contain values (e.g. prefilled in preview), compute route immediately.
            if (state.pickup_address && state.dropoff_address) {
                calculateRoute();
            }
        }

        function calculateRoute() {
            if (!window.google || !google.maps || !google.maps.DirectionsService) {
                calculateRouteFallback();
                return;
            }
            if (!state.pickup_address || !state.dropoff_address) {
                requestQuotes();
                return;
            }
            var waypoints = (state.stopovers || []).map(function(stop) {
                return { location: stop, stopover: true };
            });
            var service = new google.maps.DirectionsService();
            service.route({
                origin: state.pickup_address,
                destination: state.dropoff_address,
                waypoints: waypoints,
                travelMode: google.maps.TravelMode.DRIVING
            }, function(result, status) {
                if (status !== 'OK' || !result || !result.routes || !result.routes[0] || !result.routes[0].legs || !result.routes[0].legs[0]) {
                    calculateRouteFallback();
                    return;
                }
                var leg = result.routes[0].legs[0];
                state.distance_meters = leg.distance ? leg.distance.value : 0;
                state.duration_seconds = leg.duration ? leg.duration.value : 0;
                renderRouteDetailsText('Route: ' + (leg.distance ? leg.distance.text : '-') + ' • Reistijd: ' + (leg.duration ? leg.duration.text : '-'));
                requestQuotes();
            });
        }

        window.__taxiroyaalBookingRouteCalc = calculateRoute;
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

        var callbackName = 'initTaxiRoyaalBookingMaps_' + Math.floor(Math.random() * 1000000);
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
            var row = input.closest('.booking-route-field-row');
            if (!row) return null;
            var panel = document.createElement('div');
            panel.className = 'booking-address-suggestions-panel hidden';
            panel.setAttribute('data-suggestion-panel', key);
            row.appendChild(panel);
            panelByKey[key] = panel;
            return panel;
        }

        function hideSuggestionPanel(key) {
            if (!useCustomSuggestionPanel) return;
            var panel = panelByKey[key];
            if (!panel) return;
            panel.classList.add('hidden');
            panel.innerHTML = '';
        }

        function renderSuggestionPanel(input, key, suggestions) {
            if (!useCustomSuggestionPanel) return;
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
                btn.textContent = suggestion && suggestion.label ? suggestion.label : '';
                btn.setAttribute('data-suggestion-value', suggestion && suggestion.value ? suggestion.value : '');
                btn.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    input.value = suggestion && suggestion.value ? suggestion.value : '';
                    updateRouteInputVisualState(input);
                    syncStateFromFields();
                    if (window.__taxiroyaalBookingRouteCalc) {
                        window.__taxiroyaalBookingRouteCalc();
                    }
                    hideSuggestionPanel(key);
                });
                panel.appendChild(btn);
            });
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
            if (!row || !row.address) {
                var fallback = row && row.display_name ? compactAddress(row.display_name) : '';
                return { label: fallback, value: fallback };
            }
            var a = row.address;
            var street = a.road || a.pedestrian || a.footway || a.cycleway || '';
            var number = a.house_number || '';
            var city = a.city || a.town || a.village || a.hamlet || a.city_district || a.suburb || a.county || a.municipality || '';
            var postcode = a.postcode || '';
            var first = [street, number].filter(Boolean).join(' ').trim();
            var second = [postcode, city].filter(Boolean).join(' ').trim();
            var compact = [first, second].filter(Boolean).join(', ').trim();
            var fallback = row.display_name ? compactAddress(row.display_name) : compact;
            var value = compact || fallback;
            var compactValue = compactAddress(value);
            return { label: compactValue, value: compactValue };
        }

        function fetchNominatimPredictions(q, sourceKey) {
            var key = sourceKey || 'default';
            if (nominatimAbortByKey[key]) {
                nominatimAbortByKey[key].abort();
            }
            var controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
            if (controller) {
                nominatimAbortByKey[key] = controller;
            }
            return fetch(
                'https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&limit=8&dedupe=1&countrycodes=' + encodeURIComponent(countryCode) + '&accept-language=nl&q=' + encodeURIComponent(q),
                controller ? { signal: controller.signal } : undefined
            )
                .then(function(res) { return res.ok ? res.json() : []; })
                .then(function(rows) {
                    return Array.isArray(rows)
                        ? rows.map(formatNominatimAddress).filter(function(item) { return item && item.value; })
                        : [];
                })
                .catch(function() {
                    return [];
                });
        }

        function fetchPredictions(query, sourceKey) {
            return new Promise(function(resolve) {
                var q = String(query || '').trim();
                if (q.length < 2) {
                    resolve([]);
                    return;
                }

                var autocompleteService = getService();
                if (autocompleteService) {
                    autocompleteService.getPlacePredictions({
                        input: q,
                        types: ['address'],
                        componentRestrictions: { country: countryCode },
                    }, function(predictions, status) {
                        if (!predictions || status !== 'OK' || predictions.length === 0) {
                            fetchNominatimPredictions(q, sourceKey).then(resolve);
                            return;
                        }
                        resolve(predictions.map(function(item) {
                            var text = item && item.description ? item.description : '';
                            if (!text) return null;
                            var compactText = compactAddress(text);
                            return { label: compactText, value: compactText };
                        }).filter(Boolean));
                    });
                    return;
                }

                fetchNominatimPredictions(q, sourceKey).then(resolve);
            });
        }

        var runTypeahead = debounce(function(input) {
            if (!input) return;
            var raw = input.value || '';
            var query = raw.trim();
            var key = input.getAttribute('data-field') === 'dropoff_address' ? 'dropoff' : 'pickup';
            if (query.length < 2) {
                updateDataList(input, key, []);
                hideSuggestionPanel(key);
                lastSuggestionsByKey[key] = [];
                return;
            }
            var normalizedQuery = query.toLowerCase();
            var cacheKey = key + '::' + normalizedQuery;
            if (suggestionCache.has(cacheKey)) {
                var cachedSuggestions = suggestionCache.get(cacheKey) || [];
                lastSuggestionsByKey[key] = cachedSuggestions;
                updateDataList(input, key, cachedSuggestions);
                renderSuggestionPanel(input, key, cachedSuggestions);
                return;
            }
            requestSeqByKey[key] = (requestSeqByKey[key] || 0) + 1;
            var requestId = requestSeqByKey[key];
            fetchPredictions(query, key).then(function(suggestions) {
                if (requestId !== requestSeqByKey[key]) return;
                if (!Array.isArray(suggestions)) {
                    updateDataList(input, key, []);
                    hideSuggestionPanel(key);
                    lastSuggestionsByKey[key] = [];
                    return;
                }
                suggestionCache.set(cacheKey, suggestions);
                lastSuggestionsByKey[key] = suggestions;
                updateDataList(input, key, suggestions);
                renderSuggestionPanel(input, key, suggestions);
            });
        }, 70);

        [pickupInput, dropoffInput].forEach(function(input) {
            var key = input.getAttribute('data-field') === 'dropoff_address' ? 'dropoff' : 'pickup';
            ensureDataList(input, key);
            ensureSuggestionPanel(input, key);
            updateRouteInputVisualState(input);
            input.addEventListener('input', function() {
                updateRouteInputVisualState(input);
                runTypeahead(input);
            });
            input.addEventListener('focus', function() {
                runTypeahead(input);
            });
            input.addEventListener('change', function() {
                updateRouteInputVisualState(input);
            });
            input.addEventListener('blur', function() {
                window.setTimeout(function() {
                    hideSuggestionPanel(key);
                }, 150);
            });
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && Array.isArray(lastSuggestionsByKey[key]) && lastSuggestionsByKey[key].length > 0) {
                    var first = lastSuggestionsByKey[key][0];
                    input.value = first && first.value ? first.value : input.value;
                    updateRouteInputVisualState(input);
                    syncStateFromFields();
                    if (window.__taxiroyaalBookingRouteCalc) {
                        window.__taxiroyaalBookingRouteCalc();
                    }
                    hideSuggestionPanel(key);
                }
            });
        });
    }

    root.addEventListener('input', function(e) {
        if (e.target.matches('[data-field]')) {
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
            if (stepOrder.indexOf(selectedStepKey) >= 0) {
                clearError();
                setStepByKey(selectedStepKey);
                updateSummary();
            }
            return;
        }
        if (e.target.matches('[data-field]')) {
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
            addStopover('');
            var list = root.querySelector('[data-stopovers-list]');
            if (list && list.lastElementChild) {
                var lastInput = list.lastElementChild.querySelector('[data-stopover-input]');
                if (lastInput) lastInput.focus();
            }
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
            var targetStepKey = tabBtn.getAttribute('data-step-key') || '';
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
                setStepByKey(nextStepKey);
                if (nextStepKey === 'offers' || nextStepKey === 'confirm') {
                    requestQuotes();
                }
                updateSummary();
                return;
            }
            submitBooking();
        }
    });

    root.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
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

    window.__taxiroyaalBookingRouteCalc = calculateRouteFallback;

    setStep(1);
    syncStateFromFields();
    setupAddressTypeaheadFallback();
    initGoogleMaps();
    recalculateRouteOrQuote();
})();
</script>
@endpush

