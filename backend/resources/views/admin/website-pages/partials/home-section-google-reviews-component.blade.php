@php
    $grCompanyId = $websitePageCompanyIdForTaxiVehicles ?? \App\Models\GeneralSetting::resolveScopeCompanyId();
    $grCardData = $sections[$sectionKey] ?? [];
    $grPlaceId = trim((string) ($grCardData['place_id'] ?? ''));
    if ($grPlaceId === '' && $grCompanyId !== null) {
        $grPlaceId = trim((string) \App\Models\GeneralSetting::get('google_reviews_place_id', '', $grCompanyId));
    }
    $grBusinessName = trim((string) ($grCardData['business_name'] ?? ''));
    if ($grBusinessName === '' && $grCompanyId !== null) {
        $grBusinessName = trim((string) \App\Models\GeneralSetting::get('google_reviews_business_name', '', $grCompanyId));
    }
    $grCount = (int) ($grCardData['count'] ?? ($grCompanyId !== null ? \App\Models\GeneralSetting::get('google_reviews_count', '5', $grCompanyId) : 5));
    $grCount = max(1, min(5, $grCount));
    $grCacheHours = (int) ($grCardData['cache_hours'] ?? ($grCompanyId !== null ? \App\Models\GeneralSetting::get('google_reviews_cache_hours', '24', $grCompanyId) : 24));
    $grCacheHours = max(1, min(168, $grCacheHours));
    $grMinStars = (int) ($grCardData['min_stars'] ?? ($grCompanyId !== null ? \App\Models\GeneralSetting::get('google_reviews_min_stars', '1', $grCompanyId) : 1));
    $grMinStars = max(1, min(5, $grMinStars));
    $grSectionTitle = trim((string) ($grCardData['section_title'] ?? ''));
    if ($grSectionTitle === '' && $grCompanyId !== null) {
        $grSectionTitle = trim((string) \App\Models\GeneralSetting::get('google_reviews_section_title', '', $grCompanyId));
    }
    $grSectionBackground = trim((string) ($grCardData['section_background'] ?? ''));
    if ($grSectionBackground === '' && $grCompanyId !== null) {
        $grSectionBackground = \App\Services\GoogleReviewsService::normalizeHexColor(
            trim((string) \App\Models\GeneralSetting::get('google_reviews_section_background', '', $grCompanyId))
        );
    }
    $grMinStarsVal = (int) old('home_sections.'.$sectionKey.'.min_stars', $grMinStars);
    $grMinStarsVal = max(1, min(5, $grMinStarsVal));
    $grFieldIdSuffix = str_replace([':', '.'], ['-', '-'], $sectionKey);
    $grSettingsUrl = route('admin.settings.index').'#google-reviews';
    $grSectionBackgroundInput = trim((string) old('home_sections.'.$sectionKey.'.section_background', $grSectionBackground));
    $grSectionBgPickerValue = $grSectionBackgroundInput !== ''
        ? \App\Services\GoogleReviewsService::normalizeHexColor($grSectionBackgroundInput)
        : '';
    if ($grSectionBgPickerValue === '') {
        $grSectionBgPickerValue = '#f3f4f6';
    }
@endphp
<div class="kt-card home-section-card home-section-card--component home-section-card--module @if($isCardCollapsed) home-section-card--collapsed @endif" data-section="{{ $sectionKey }}">
    <div class="kt-card-header home-section-header home-section-header--component flex items-center justify-between gap-2">
        <span class="home-section-drag-handle cursor-grab active:cursor-grabbing touch-none p-1 -ml-1 rounded text-muted-foreground hover:text-foreground" title="Sleep om volgorde te wijzigen" aria-label="Volgorde wijzigen"><svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" /></svg></span>
        <h3 class="kt-card-title">Google Reviews (Algemeen)</h3>
        <div class="flex items-center gap-1 shrink-0">
            <input type="hidden" name="home_sections[visibility][{{ $sectionKey }}]" id="visibility-{{ $sectionKey }}" value="{{ $vis('') ? '1' : '0' }}">
            <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground" data-target="visibility-{{ $sectionKey }}" title="{{ $vis('') ? 'Verbergen op website' : 'Tonen op website' }}" aria-label="Zichtbaarheid">@if($vis(''))<svg class="w-5 h-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-5 h-5 text-current opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
            <button type="button" class="home-section-collapse-toggle kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground" title="Inklappen" aria-label="Sectie inklappen of uitklappen"><svg class="w-5 h-5 text-current home-section-collapse-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg></button>
            <button type="button" class="home-section-component-remove kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-destructive" title="Component van pagina verwijderen" aria-label="Component verwijderen"><svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
        </div>
    </div>
    <div class="home-section-card-body kt-card-table p-4 space-y-4">
        <p class="text-sm text-muted-foreground">Instellingen worden opgeslagen voor het bedrijf van deze pagina{{ $grCompanyId ? '' : ' (koppel eerst een bedrijf aan de pagina)' }}. Dezelfde waarden staan ook onder <a href="{{ $grSettingsUrl }}" class="text-primary hover:underline" target="_blank" rel="noopener">Configuraties → Google Reviews</a>.</p>
        @if($grCompanyId === null)
        <p class="text-sm text-amber-700 dark:text-amber-400">Zonder gekoppeld bedrijf kan Place ID niet worden opgeslagen.</p>
        @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pb-4" data-panel-title="Google Reviews instellingen">
            <div>
                <label class="block text-xs text-muted-foreground mb-1" for="gr-place-{{ $sectionKey }}">Place ID</label>
                <input type="text" id="gr-place-{{ $sectionKey }}" name="home_sections[{{ $sectionKey }}][place_id]" class="kt-input w-full" value="{{ old('home_sections.'.$sectionKey.'.place_id', $grPlaceId) }}" maxlength="255" placeholder="ChIJ...">
            </div>
            <div>
                <label class="block text-xs text-muted-foreground mb-1" for="gr-business-{{ $sectionKey }}">Bedrijfsnaam (fallback)</label>
                <input type="text" id="gr-business-{{ $sectionKey }}" name="home_sections[{{ $sectionKey }}][business_name]" class="kt-input w-full" value="{{ old('home_sections.'.$sectionKey.'.business_name', $grBusinessName) }}" maxlength="255" placeholder="bijv. Nexa Taxi Amsterdam">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs text-muted-foreground mb-1" for="gr-section-title-{{ $sectionKey }}">Titel boven de review-carousel</label>
                <input type="text" id="gr-section-title-{{ $sectionKey }}" name="home_sections[{{ $sectionKey }}][section_title]" class="kt-input w-full" value="{{ old('home_sections.'.$sectionKey.'.section_title', $grSectionTitle) }}" maxlength="255" placeholder="Standaard: Wat anderen zeggen">
                <p class="text-xs text-muted-foreground mt-1 mb-0">Laat leeg voor de standaardtekst «Wat anderen zeggen».</p>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs text-muted-foreground mb-1" for="gr-section-bg-{{ $grFieldIdSuffix }}">Achtergrondkleur van de sectie</label>
                <div class="flex gap-2 items-center">
                    <input type="color" class="featured-services-icon-color-picker h-9 w-14 cursor-pointer rounded border border-input bg-background p-1 shrink-0" value="{{ $grSectionBgPickerValue }}" title="Kies achtergrondkleur" aria-label="Achtergrondkleur Google Reviews-sectie">
                    <div class="home-section-hex-input-wrap shrink-0">
                        <input type="text" id="gr-section-bg-{{ $grFieldIdSuffix }}" name="home_sections[{{ $sectionKey }}][section_background]" class="kt-input text-sm w-full font-mono home-section-hex-input" value="{{ $grSectionBackgroundInput }}" placeholder="#f3f4f6" maxlength="7" pattern="^#?([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})?$" data-skip-validation-wrapper="1">
                    </div>
                </div>
                <p class="text-xs text-muted-foreground mt-1 mb-0">Hex via pipet of handmatig. Leeg = standaard thema-achtergrond.</p>
            </div>
            <div>
                <label class="block text-xs text-muted-foreground mb-1" for="gr-count-{{ $sectionKey }}">Aantal reviews (1–5)</label>
                <input type="number" id="gr-count-{{ $sectionKey }}" name="home_sections[{{ $sectionKey }}][count]" class="kt-input w-24" value="{{ old('home_sections.'.$sectionKey.'.count', $grCount) }}" min="1" max="5">
            </div>
            <div>
                <label class="block text-xs text-muted-foreground mb-1" for="gr-cache-{{ $sectionKey }}">Cacheduur (uren)</label>
                <input type="number" id="gr-cache-{{ $sectionKey }}" name="home_sections[{{ $sectionKey }}][cache_hours]" class="kt-input w-24" value="{{ old('home_sections.'.$sectionKey.'.cache_hours', $grCacheHours) }}" min="1" max="168">
            </div>
            <div class="md:col-span-2 pb-1">
                <label class="block text-xs text-muted-foreground mb-1">Min. sterren</label>
                <input type="hidden" name="home_sections[{{ $sectionKey }}][min_stars]" id="gr-min-stars-{{ $grFieldIdSuffix }}" value="{{ $grMinStarsVal }}">
                <div class="grw-admin-star-picker flex items-center gap-1" data-grw-star-picker data-grw-star-hidden="gr-min-stars-{{ $grFieldIdSuffix }}" role="group" aria-label="Minimaal aantal sterren">
                    @for($s = 1; $s <= 5; $s++)
                    <button type="button"
                            class="grw-admin-star w-8 h-8 rounded p-0 flex items-center justify-center text-xl text-muted-foreground hover:text-yellow-500 dark:hover:text-yellow-400 transition-colors focus:outline-none {{ $s <= $grMinStarsVal ? 'text-yellow-500 dark:text-yellow-400' : '' }}"
                            data-value="{{ $s }}"
                            aria-label="Minimaal {{ $s }} {{ $s === 1 ? 'ster' : 'sterren' }}">
                        <span class="grw-admin-star-icon" aria-hidden="true">★</span>
                    </button>
                    @endfor
                </div>
                <p class="text-xs text-muted-foreground mt-1 mb-0">Alleen reviews met dit aantal sterren of meer tonen.</p>
            </div>
        </div>
        @endif
    </div>
</div>
