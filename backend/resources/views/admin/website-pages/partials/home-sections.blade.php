@php
    $sections = $homeSections ?? \App\Models\WebsitePage::defaultHomeSections();
    $hero = $sections['hero'] ?? [];
    $stats = $sections['stats'] ?? [];
    $whyNexa = $sections['why_nexa'] ?? [];
    $features = $sections['features'] ?? [];
    $cta = $sections['cta'] ?? [];
    $footer = $sections['footer'] ?? [];
    $copyright = $sections['copyright'] ?? '';
    $visibility = $sections['visibility'] ?? [];
    $featureItems = array_values($features['items'] ?? []);
    if (count($featureItems) < 2) {
        $defItems = (\App\Models\WebsitePage::defaultHomeSections())['features']['items'] ?? [['title'=>'','description'=>'','icon'=>'bulb'],['title'=>'','description'=>'','icon'=>'lightning']];
        $featureItems = array_merge($featureItems, array_slice($defItems, count($featureItems), 2 - count($featureItems)));
    }
    $imagePreviewUrl = function($url) {
        return app(\App\Services\WebsiteBuilderService::class)->storageUrlToDisplayUrl($url ?? '');
    };
    // Normaliseer hex naar #rrggbb voor type="color" (picker accepteert alleen 6-digit)
    $hexForPicker = function($v) {
        if ($v === null || $v === '') return '';
        $v = trim((string) $v);
        if (preg_match('/^#([0-9a-fA-F]{3})$/', $v, $m)) return '#' . $m[1][0].$m[1][0].$m[1][1].$m[1][1].$m[1][2].$m[1][2];
        if (preg_match('/^#?([0-9a-fA-F]{6})$/', $v, $m)) return '#' . $m[1];
        return '';
    };
    $themeSlugForOrder = $themeSlug ?? 'modern';
    $isNonHome = $isNonHomePage ?? false;
    $defaultSectionOrder = $isNonHome
        ? ((\App\Models\WebsitePage::defaultPageSectionsForNonHome($themeSlugForOrder))['section_order'] ?? ['hero'])
        : ((\App\Models\WebsitePage::defaultHomeSectionsForTheme($themeSlugForOrder))['section_order'] ?? ['hero', 'stats', 'why_nexa', 'features', 'cta']);
    $sectionOrder = $sections['section_order'] ?? $defaultSectionOrder;
    if (is_string($sectionOrder) && $sectionOrder !== '') {
        $sectionOrder = array_values(array_filter(array_map('trim', explode(',', $sectionOrder))));
    }
    if (!is_array($sectionOrder) || empty($sectionOrder)) {
        $sectionOrder = $defaultSectionOrder;
    } else {
        $sectionOrder = array_values($sectionOrder);
    }
    $singleSectionForFetch = (isset($sectionCardOnly) && $sectionCardOnly) && count($sectionOrder) === 1;
    // Niet ontbrekende default-secties terugzetten: opgeslagen section_order is bron van waarheid, zodat verwijderde secties/componenten weg blijven.
    // Normaliseer component-keys naar "component:id" (lowercase, geen dubbele prefix); verwijder duplicaten
    $sectionOrder = array_map(function ($k) {
        if (is_string($k) && str_starts_with(strtolower($k), 'component:')) {
            $rest = preg_replace('/^component:+/i', '', $k);
            return $rest !== '' ? 'component:' . $rest : $k;
        }
        return $k;
    }, $sectionOrder);
    $sectionOrder = array_values(array_unique($sectionOrder, SORT_REGULAR));
    $removedComponentKeys = array_fill_keys(\App\Services\FrontendComponentService::removedComponentSectionKeys(), true);
    $sectionOrder = array_values(array_filter($sectionOrder, static fn ($k) => ! isset($removedComponentKeys[$k])));
    $sectionOrder = array_values($sectionOrder);
    // Niet sectionOrder vervangen door thema-default: opgeslagen volgorde is bron van waarheid (verwijderde secties blijven weg).
    $componentService = app(\App\Services\FrontendComponentService::class);
    $baseTypes = ['hero', 'stats', 'why_nexa', 'features', 'cta', 'carousel', 'cards_ronde_hoeken', 'featured_services', 'email_template', 'text_block'];
    $baseType = function($key) use ($baseTypes) {
        if (in_array($key, $baseTypes, true)) return $key;
        $base = preg_replace('/_\d+$/', '', $key);
        return in_array($base, $baseTypes, true) ? $base : null;
    };
    // Zelfde titelnamen als in het "Sectie toevoegen" menu (getAvailableHomeSectionTypesForTheme)
    $sectionTypeLabels = [];
    foreach (\App\Models\WebsitePage::getAvailableHomeSectionTypesForTheme($themeSlugForOrder) as $st) {
        $sectionTypeLabels[$st['type']] = $st['label'];
    }
    $sectionLabel = function($base) use ($sectionTypeLabels) {
        return $sectionTypeLabels[$base] ?? match($base) {
            'hero' => 'Hero (banner)',
            'stats' => 'Stats (4 cijfers)',
            'why_nexa' => 'Waarom Nexa',
            'features' => 'Kenmerken',
            'cta' => 'CTA',
            'carousel' => 'Carousel',
            'cards_ronde_hoeken' => 'Cards ronde hoeken',
            'featured_services' => 'Dienstenblok (scroll-animatie)',
            'email_template' => 'E-mailtemplate (informatieaanvraag)',
            'text_block' => 'Tekstblok (rich text + component)',
            default => $base,
        };
    };
    $emailTemplatesForSelect = $emailTemplates ?? collect();
    $emailTemplateSelectedIds = $emailTemplateSelectedIds ?? [];
    // Alleen secties tonen die voor dit thema beschikbaar zijn (add-menu = bron van waarheid)
    $allowedBaseTypesForTheme = array_column(\App\Models\WebsitePage::getAvailableHomeSectionTypesForTheme($themeSlugForOrder), 'type');
    $sectionOrder = array_values(array_filter($sectionOrder, function($key) use ($allowedBaseTypesForTheme) {
        if (is_string($key) && str_starts_with($key, 'component:')) return true;
        $baseTypes = ['hero', 'stats', 'why_nexa', 'features', 'cta', 'carousel', 'cards_ronde_hoeken', 'featured_services', 'email_template', 'text_block'];
        $base = in_array($key, $baseTypes, true) ? $key : preg_replace('/_\d+$/', '', (string)$key);
        if (!in_array($base, $baseTypes, true)) return false;
        return in_array($base, $allowedBaseTypesForTheme, true);
    }));
    $adminCollapsed = $sections['admin_collapsed'] ?? [];
    if (!is_array($adminCollapsed)) {
        $adminCollapsed = [];
    }
    if (empty($adminCollapsed) && !empty($collapseSectionsByDefault ?? false)) {
        $adminCollapsed = \App\Models\WebsitePage::defaultAdminCollapsedKeys($sectionOrder);
    }
    $isFooterCollapsed = in_array('footer', $adminCollapsed, true);
    $isCopyrightCollapsed = in_array('copyright', $adminCollapsed, true);
    // Voor "Component naast de tekst" altijd alle beschikbare types + huidige section_order tonen, zodat de dropdown direct gevuld is ook bij nieuw toegevoegde tekstblokken (sectionCardOnly).
    $availableTypesForTheme = array_column(\App\Models\WebsitePage::getAvailableHomeSectionTypesForTheme($themeSlugForOrder), 'type');
    $sideComponentOptionKeys = array_values(array_unique(array_merge($availableTypesForTheme, $sectionOrder)));
    $sideComponentOptionKeys = array_values(array_filter($sideComponentOptionKeys, function ($k) use ($baseType) {
        if ($k === 'text_block' || in_array($k, ['footer', 'copyright'], true)) {
            return false;
        }
        if ($baseType($k) === 'email_template') {
            return true;
        }
        if (\App\Services\FrontendComponentService::isComponentKey($k)) {
            $componentId = \App\Services\FrontendComponentService::componentIdFromKey(
                \App\Services\FrontendComponentService::normalizeComponentSectionKey($k)
            );

            return strtolower((string) $componentId) === 'website.email_template_section';
        }

        return false;
    }));
    if (! in_array('email_template', $sideComponentOptionKeys, true)) {
        $sideComponentOptionKeys[] = 'email_template';
    }
    $websitePageCompanyIdForTaxiVehicles = isset($websitePageCompanyId) && $websitePageCompanyId !== null && $websitePageCompanyId !== '' ? (int) $websitePageCompanyId : null;
@endphp
{{-- Heroicons: eye (tonen) en eye-slash (verborgen op website) --}}
<input type="hidden" name="home_sections[section_order]" id="home-sections-order-input" value="{{ implode(',', $sectionOrder) }}">
<input type="hidden" name="home_sections[removed_section_keys]" id="home-sections-removed-keys-input" value="">
<input type="hidden" name="home_sections[admin_collapsed]" id="admin-collapsed-input" value="{{ implode(',', $adminCollapsed) }}">
<div id="home-sections-meta" class="hidden" data-section-card-url="{{ route('admin.website-pages.section-card-html') }}" data-component-section-url="{{ route('admin.website-pages.component-section-html') }}" data-theme-slug="{{ $themeSlugForOrder }}" data-section-labels="{{ json_encode($sectionTypeLabels) }}" data-website-page-company-id="{{ $websitePageCompanyIdForTaxiVehicles !== null ? (string) $websitePageCompanyIdForTaxiVehicles : '' }}" data-website-media-delete-url="{{ url('/admin/website-media') }}" data-website-media-serve-base="{{ url('/website-media') }}"></div>
{{-- Carousel slide: thumbnail groot bekijken --}}
<div id="carousel-slide-preview-modal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/60 backdrop-blur-sm" aria-hidden="true" role="dialog" aria-modal="true" aria-label="Carousel-afbeelding groot">
    <div class="relative max-h-[90vh] max-w-[90vw] p-4" id="carousel-slide-preview-modal-inner">
        <button type="button" id="carousel-slide-preview-modal-close" class="absolute -top-2 -right-2 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-background border border-input text-foreground shadow-md hover:bg-muted" aria-label="Sluiten">
            <i class="ki-filled ki-cross text-xl"></i>
        </button>
        <img id="carousel-slide-preview-modal-img" src="" alt="Carousel-afbeelding" class="max-h-[85vh] w-auto max-w-full object-contain rounded-lg shadow-xl">
    </div>
</div>
<div id="home-sections-sortable" class="space-y-6" data-admin-collapsed="{{ json_encode($adminCollapsed) }}">
    @foreach($sectionOrder as $sectionKey)
    @php
        $base = $baseType($sectionKey);
        $sectionData = $sections[$sectionKey] ?? [];
        $vis = function($suffix) use ($visibility, $sectionKey, $base) {
            return $visibility[$sectionKey . $suffix] ?? $visibility[$base . $suffix] ?? true;
        };
        $isCardCollapsed = in_array($sectionKey, $adminCollapsed, true);
    @endphp
    @if($base === 'hero')
    <div class="kt-card home-section-card @if($isCardCollapsed) home-section-card--collapsed @endif" data-section="{{ $sectionKey }}">
        <div class="kt-card-header home-section-header home-section-header--hero flex items-center justify-between gap-2">
            <span class="home-section-drag-handle cursor-grab active:cursor-grabbing touch-none p-1 -ml-1 rounded text-muted-foreground hover:text-foreground" title="Sleep om volgorde te wijzigen" aria-label="Volgorde wijzigen"><svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" /></svg></span>
            <h3 class="kt-card-title">{{ $sectionLabel('hero') }}{{ $sectionKey !== 'hero' ? ' – ' . $sectionKey : '' }}</h3>
            <div class="flex items-center gap-1 shrink-0">
                <input type="hidden" name="home_sections[visibility][{{ $sectionKey }}]" id="visibility-{{ $sectionKey }}" value="{{ $vis('') ? '1' : '0' }}">
                <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground" data-target="visibility-{{ $sectionKey }}" title="{{ $vis('') ? 'Verbergen op website' : 'Tonen op website' }}" aria-label="Zichtbaarheid">
                    @if($vis(''))
                    <svg class="w-5 h-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    @else
                    <svg class="w-5 h-5 text-current opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                    @endif
                </button>
                <button type="button" class="home-section-collapse-toggle kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground" title="Inklappen" aria-label="Sectie inklappen of uitklappen">
                    <svg class="w-5 h-5 text-current home-section-collapse-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
                </button>
                <button type="button" class="home-section-remove kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-destructive" title="Sectie verwijderen" aria-label="Sectie verwijderen">
                    <svg class="w-5 h-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                </button>
            </div>
        </div>
        <div class="home-section-card-body kt-card-table p-4 space-y-3">
            <div class="row-visibility-row flex flex-col gap-3">
                <div class="w-full">
                    <div class="flex items-center gap-2 mb-1">
                        <label class="text-sm font-medium text-secondary-foreground">Titel</label>
                        <input type="hidden" name="home_sections[visibility][{{ $sectionKey }}_title]" id="visibility-{{ $sectionKey }}_title" value="{{ $vis('_title') ? '1' : '0' }}">
                        <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" data-target="visibility-{{ $sectionKey }}_title" title="Zichtbaar op website" aria-label="Titel tonen/verbergen">@if($vis('_title'))<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                    </div>
                    <input type="text" name="home_sections[{{ $sectionKey }}][title]" class="kt-input w-full max-w-4xl" value="{{ old('home_sections.'.$sectionKey.'.title', $sectionData['title'] ?? 'Vind je droombaan met AI') }}" placeholder="Vind je droombaan met AI">
                </div>
                <div class="w-full relative">
                    @php
                        $titleHighlightColorDefault = '#93c5fd';
                        $titleHighlightColor = old('home_sections.'.$sectionKey.'.title_highlight_color', $sectionData['title_highlight_color'] ?? '');
                        $titleHighlightColor = is_string($titleHighlightColor) ? trim($titleHighlightColor) : '';
                        if ($titleHighlightColor !== '' && ! preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $titleHighlightColor)) {
                            $titleHighlightColor = '';
                        }
                        $titleHighlightColorPicker = $titleHighlightColor !== '' ? $titleHighlightColor : $titleHighlightColorDefault;
                    @endphp
                    <label class="block text-sm font-medium text-secondary-foreground mb-1" for="hero-{{ $sectionKey }}-title_highlight">Woord benadrukt</label>
                    <input type="text"
                           name="home_sections[{{ $sectionKey }}][title_highlight]"
                           id="hero-{{ $sectionKey }}-title_highlight"
                           class="kt-input w-full max-w-md mb-3"
                           value="{{ old('home_sections.'.$sectionKey.'.title_highlight', $sectionData['title_highlight'] ?? 'droombaan') }}"
                           placeholder="droombaan">
                    <label class="block text-sm font-medium text-secondary-foreground mb-1" for="hero-{{ $sectionKey }}-title_highlight_color">Kleur benadrukking</label>
                    <div class="flex items-center gap-2 max-w-md">
                        <input type="color"
                               id="hero-{{ $sectionKey }}-title_highlight_color_color"
                               class="hero-title-highlight-color-picker h-10 w-14 rounded border border-input cursor-pointer shrink-0"
                               value="{{ $titleHighlightColorPicker }}"
                               title="Kleur kiezen"
                               data-target-input="hero-{{ $sectionKey }}-title_highlight_color">
                        <div class="home-section-hex-input-wrap shrink-0">
                        <input type="text"
                               name="home_sections[{{ $sectionKey }}][title_highlight_color]"
                               id="hero-{{ $sectionKey }}-title_highlight_color"
                               class="kt-input w-full font-mono text-sm home-section-hex-input hero-title-highlight-hex-input"
                               value="{{ $titleHighlightColor }}"
                               placeholder="{{ $titleHighlightColorDefault }}"
                               maxlength="7"
                               data-skip-validation-wrapper="1">
                        </div>
                    </div>
                    <p class="text-xs text-muted-foreground mt-1">Het woord uit de titel dat in deze kleur wordt getoond. Leeg = standaard themakleur.</p>
                </div>
            </div>
            @if(($themeSlugForOrder ?? '') === 'atom-v2')
            <div class="row-visibility-row flex flex-wrap items-center gap-2">
                <label class="block text-sm font-medium text-secondary-foreground">Donker overlay over hero</label>
                <input type="hidden" name="home_sections[{{ $sectionKey }}][overlay]" value="0">
                <input type="checkbox" name="home_sections[{{ $sectionKey }}][overlay]" class="kt-switch kt-switch-sm" value="1" {{ (old('home_sections.'.$sectionKey.'.overlay', $sectionData['overlay'] ?? true)) ? 'checked' : '' }}>
                <span class="text-xs text-muted-foreground">De laag over de gradient (bg-black/10) die tekst beter leesbaar maakt.</span>
            </div>
            @endif
            {{-- Hero-afbeeldingen: per thema andere velden --}}
            @if(in_array($themeSlugForOrder ?? '', ['nextly-template', 'next-landing-vpn'], true))
            @php
                $defaultHeroImg = ($themeSlugForOrder ?? '') === 'next-landing-vpn'
                    ? asset('frontend-themes/next-landing-vpn/public/assets/Illustration1.png')
                    : (($themeSlugForOrder ?? '') === 'nextly-template' ? asset('frontend-themes/nextly-template/public/img/hero.png') : '');
                $heroPreviewSrc = !empty($sectionData['author_image_url']) ? $sectionData['author_image_url'] : $defaultHeroImg;
            @endphp
            {{-- Nextly / Next Landing VPN: één hero-afbeelding (standaard of upload) --}}
            <div class="row-visibility-row">
                <label class="block text-sm font-medium text-secondary-foreground mb-1">Hero-afbeelding</label>
                <p class="text-xs text-muted-foreground mb-2">Afbeelding naast de titel. @if(($themeSlugForOrder ?? '') === 'next-landing-vpn')Standaard: Illustration1.png.@else(Nextly thema)@endif</p>
                <div class="flex flex-wrap items-stretch gap-3">
                    <div class="shrink-0 flex flex-col items-center">
                        <img alt="Hero afbeelding" id="hero-{{ $sectionKey }}-author-preview" class="w-full max-w-[200px] max-h-40 object-contain border border-border rounded-lg {{ $heroPreviewSrc ? '' : 'hidden' }}" src="{{ $imagePreviewUrl($heroPreviewSrc) }}" data-default-src="{{ $defaultHeroImg ?? '' }}">
                        <button type="button" class="image-remove-btn kt-btn kt-btn-xs kt-btn-ghost text-destructive mt-1 shadow hover:bg-destructive/10" data-url-input-id="hero-{{ $sectionKey }}-author_image_url" data-preview-id="hero-{{ $sectionKey }}-author-preview" title="Afbeelding verwijderen" aria-label="Afbeelding verwijderen"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                    </div>
                    <div class="hero-image-upload-area flex flex-col items-center justify-center p-5 lg:p-7 border border-input rounded-xl border-dashed bg-muted/30" style="width: 500px; min-width: 500px; height: 130px;" data-section-key="{{ $sectionKey }}" data-field="author_image_url">
                        <span class="text-xs text-muted-foreground text-center">Klik of sleep afbeelding</span>
                        <span class="text-xs text-muted-foreground">JPG, PNG, WebP (max. 5MB)</span>
                    </div>
                </div>
                <input type="file" class="hero-image-file-input hidden" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" data-section-key="{{ $sectionKey }}" data-field="author_image_url">
                <input type="hidden" name="home_sections[{{ $sectionKey }}][author_image_url]" id="hero-{{ $sectionKey }}-author_image_url" value="{{ old('home_sections.'.$sectionKey.'.author_image_url', $sectionData['author_image_url'] ?? '') }}">
                <input type="hidden" name="home_sections[{{ $sectionKey }}][background_image_url]" id="hero-{{ $sectionKey }}-background_image_url" value="{{ old('home_sections.'.$sectionKey.'.background_image_url', $sectionData['background_image_url'] ?? '') }}">
            </div>
            @else
            @if(($themeSlugForOrder ?? '') === 'modern')
            {{-- Metronic thema: alleen achtergrond --}}
            <div class="row-visibility-row">
                <label class="block text-sm font-medium text-secondary-foreground mb-1">Achtergrond banner</label>
                <p class="text-xs text-muted-foreground mb-2">Afbeelding achter de hero. (Metronic thema)</p>
                <div class="flex flex-wrap items-stretch gap-3">
                    <div class="shrink-0 flex flex-col items-center">
                        <img alt="Hero achtergrond" id="hero-{{ $sectionKey }}-bg-preview" class="w-full max-w-[200px] max-h-24 object-cover border border-border rounded {{ !empty($sectionData['background_image_url']) ? '' : 'hidden' }}" src="{{ $imagePreviewUrl($sectionData['background_image_url'] ?? '') }}">
                        <button type="button" class="image-remove-btn kt-btn kt-btn-xs kt-btn-ghost text-destructive mt-1 shadow hover:bg-destructive/10" data-url-input-id="hero-{{ $sectionKey }}-background_image_url" data-preview-id="hero-{{ $sectionKey }}-bg-preview" title="Afbeelding verwijderen" aria-label="Afbeelding verwijderen"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                    </div>
                    <div class="hero-image-upload-area flex flex-col items-center justify-center p-5 lg:p-7 border border-input rounded-xl border-dashed bg-muted/30" style="width: 500px; min-width: 500px; height: 130px;" data-section-key="{{ $sectionKey }}" data-field="background_image_url">
                        <span class="text-xs text-muted-foreground text-center">Klik of sleep afbeelding</span>
                        <span class="text-xs text-muted-foreground">JPG, PNG, WebP (max. 5MB)</span>
                    </div>
                </div>
                <input type="file" class="hero-image-file-input hidden" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" data-section-key="{{ $sectionKey }}" data-field="background_image_url">
                <input type="hidden" name="home_sections[{{ $sectionKey }}][background_image_url]" id="hero-{{ $sectionKey }}-background_image_url" value="{{ old('home_sections.'.$sectionKey.'.background_image_url', $sectionData['background_image_url'] ?? '') }}">
                <input type="hidden" name="home_sections[{{ $sectionKey }}][author_image_url]" id="hero-{{ $sectionKey }}-author_image_url" value="{{ old('home_sections.'.$sectionKey.'.author_image_url', $sectionData['author_image_url'] ?? '') }}">
            </div>
            @else
            {{-- Atom-v2 e.d.: achtergrond + ronde foto --}}
            <div class="row-visibility-row grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="min-w-0 flex flex-col gap-2">
                    <label class="block text-sm font-medium text-secondary-foreground">Achtergrond banner</label>
                    <p class="text-xs text-muted-foreground">Afbeelding achter de gradient. (Atom-v2 thema)</p>
                    <div class="flex flex-wrap items-stretch gap-3">
                        <div class="shrink-0 flex flex-col items-center">
                            <img alt="Hero achtergrond" id="hero-{{ $sectionKey }}-bg-preview" class="w-full max-w-[200px] max-h-24 object-cover border border-border rounded {{ !empty($sectionData['background_image_url']) ? '' : 'hidden' }}" src="{{ $imagePreviewUrl($sectionData['background_image_url'] ?? '') }}">
                            <button type="button" class="image-remove-btn kt-btn kt-btn-xs kt-btn-ghost text-destructive mt-1 shadow hover:bg-destructive/10" data-url-input-id="hero-{{ $sectionKey }}-background_image_url" data-preview-id="hero-{{ $sectionKey }}-bg-preview" title="Afbeelding verwijderen" aria-label="Afbeelding verwijderen"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                        </div>
                        <div class="hero-image-upload-area flex flex-col items-center justify-center p-5 lg:p-7 border border-input rounded-xl border-dashed bg-muted/30" style="width: 500px; min-width: 500px; height: 130px;" data-section-key="{{ $sectionKey }}" data-field="background_image_url">
                            <span class="text-xs text-muted-foreground text-center">Klik of sleep afbeelding</span>
                            <span class="text-xs text-muted-foreground">JPG, PNG, WebP (max. 5MB)</span>
                        </div>
                    </div>
                    <input type="file" class="hero-image-file-input hidden" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" data-section-key="{{ $sectionKey }}" data-field="background_image_url">
                    <input type="hidden" name="home_sections[{{ $sectionKey }}][background_image_url]" id="hero-{{ $sectionKey }}-background_image_url" value="{{ old('home_sections.'.$sectionKey.'.background_image_url', $sectionData['background_image_url'] ?? '') }}">
                </div>
                <div class="min-w-0 flex flex-col gap-2">
                    <label class="block text-sm font-medium text-secondary-foreground">Foto in banner (ronde afbeelding)</label>
                    <p class="text-xs text-muted-foreground">Ronde foto naast de titel. (Atom-v2 thema)</p>
                    <div class="flex flex-wrap items-stretch gap-3">
                        <div class="shrink-0 flex flex-col items-center">
                            <img alt="Hero foto" id="hero-{{ $sectionKey }}-author-preview" class="w-20 h-20 rounded-full object-cover border border-border {{ !empty($sectionData['author_image_url']) ? '' : 'hidden' }}" src="{{ $imagePreviewUrl($sectionData['author_image_url'] ?? '') }}">
                            <button type="button" class="image-remove-btn kt-btn kt-btn-xs kt-btn-ghost text-destructive mt-1 shadow hover:bg-destructive/10" data-url-input-id="hero-{{ $sectionKey }}-author_image_url" data-preview-id="hero-{{ $sectionKey }}-author-preview" title="Afbeelding verwijderen" aria-label="Afbeelding verwijderen"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                        </div>
                        <div class="hero-image-upload-area flex flex-col items-center justify-center p-5 lg:p-7 border border-input rounded-xl border-dashed bg-muted/30" style="width: 500px; min-width: 500px; height: 130px;" data-section-key="{{ $sectionKey }}" data-field="author_image_url">
                            <span class="text-xs text-muted-foreground text-center">Klik of sleep afbeelding</span>
                            <span class="text-xs text-muted-foreground">JPG, PNG, WebP (max. 5MB)</span>
                        </div>
                    </div>
                    <input type="file" class="hero-image-file-input hidden" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" data-section-key="{{ $sectionKey }}" data-field="author_image_url">
                    <input type="hidden" name="home_sections[{{ $sectionKey }}][author_image_url]" id="hero-{{ $sectionKey }}-author_image_url" value="{{ old('home_sections.'.$sectionKey.'.author_image_url', $sectionData['author_image_url'] ?? '') }}">
                </div>
            </div>
            @endif
            @endif
            {{-- Overloop (gradient) over achtergrond: kleur van/naar + helderheid — alleen bij thema's met hero-achtergrond --}}
            @if(in_array($themeSlugForOrder ?? '', ['modern', 'atom-v2'], true))
            <div class="row-visibility-row grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t border-border hero-overlay-row" data-section-key="{{ $sectionKey }}">
                <div>
                    <label class="block text-sm font-medium text-secondary-foreground mb-1">Overloop kleur van</label>
                    <div class="flex items-center gap-2">
                        <input type="color" id="hero-{{ $sectionKey }}-overlay_color_from_color" class="hero-overlay-color-picker h-10 w-14 rounded border border-input cursor-pointer" value="{{ old('home_sections.'.$sectionKey.'.overlay_color_from', $sectionData['overlay_color_from'] ?? '#1e3a8a') }}" title="Kleur kiezen" data-target-input="hero-{{ $sectionKey }}-overlay_color_from">
                        <div class="home-section-hex-input-wrap shrink-0">
                        <input type="text" name="home_sections[{{ $sectionKey }}][overlay_color_from]" id="hero-{{ $sectionKey }}-overlay_color_from" class="kt-input w-full font-mono text-sm home-section-hex-input hero-overlay-hex-input" value="{{ old('home_sections.'.$sectionKey.'.overlay_color_from', $sectionData['overlay_color_from'] ?? '#1e3a8a') }}" placeholder="#1e3a8a" maxlength="7" data-skip-validation-wrapper="1">
                        </div>
                    </div>
                    <p class="text-xs text-muted-foreground mt-1">Startkleur van de gradient over de afbeelding.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary-foreground mb-1">Overloop kleur naar</label>
                    <div class="flex items-center gap-2">
                        <input type="color" id="hero-{{ $sectionKey }}-overlay_color_to_color" class="hero-overlay-color-picker h-10 w-14 rounded border border-input cursor-pointer" value="{{ old('home_sections.'.$sectionKey.'.overlay_color_to', $sectionData['overlay_color_to'] ?? '#312e81') }}" title="Kleur kiezen" data-target-input="hero-{{ $sectionKey }}-overlay_color_to">
                        <div class="home-section-hex-input-wrap shrink-0">
                        <input type="text" name="home_sections[{{ $sectionKey }}][overlay_color_to]" id="hero-{{ $sectionKey }}-overlay_color_to" class="kt-input w-full font-mono text-sm home-section-hex-input hero-overlay-hex-input" value="{{ old('home_sections.'.$sectionKey.'.overlay_color_to', $sectionData['overlay_color_to'] ?? '#312e81') }}" placeholder="#312e81" maxlength="7" data-skip-validation-wrapper="1">
                        </div>
                    </div>
                    <p class="text-xs text-muted-foreground mt-1">Eindkleur van de gradient.</p>
                </div>
                <div class="md:col-span-2">
                    @php
                        $overlayOpacityVal = (int) old('home_sections.'.$sectionKey.'.overlay_opacity', $sectionData['overlay_opacity'] ?? 85);
                        $overlayOpacityVal = max(0, min(100, $overlayOpacityVal));
                    @endphp
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <label class="text-sm font-medium text-secondary-foreground" for="hero-{{ $sectionKey }}-overlay_opacity">Helderheid overloop</label>
                        <span id="hero-{{ $sectionKey }}-overlay_opacity-value"
                              class="hero-overlay-opacity-value inline-flex items-center justify-center min-w-[3.25rem] rounded-md bg-muted px-2 py-0.5 text-sm font-semibold tabular-nums text-foreground"
                              aria-live="polite">{{ $overlayOpacityVal }}%</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-muted-foreground shrink-0">Lichter (afbeelding duidelijker)</span>
                        <div class="hero-overlay-opacity-slider flex-1 min-w-0">
                            <input type="range"
                                   name="home_sections[{{ $sectionKey }}][overlay_opacity]"
                                   id="hero-{{ $sectionKey }}-overlay_opacity"
                                   class="hero-overlay-opacity-range w-full"
                                   min="0"
                                   max="100"
                                   step="1"
                                   value="{{ $overlayOpacityVal }}"
                                   aria-valuemin="0"
                                   aria-valuemax="100"
                                   aria-valuenow="{{ $overlayOpacityVal }}"
                                   aria-describedby="hero-{{ $sectionKey }}-overlay_opacity-value">
                        </div>
                        <span class="text-xs text-muted-foreground shrink-0">Donkerder</span>
                    </div>
                    <p class="text-xs text-muted-foreground mt-1">0 = overloop bijna transparant (achtergrond goed zichtbaar), 100 = donkerste overloop.</p>
                </div>
            </div>
            @endif
            <div class="row-visibility-row">
                <div class="flex items-center gap-2 mb-1">
                    <label class="text-sm font-medium text-secondary-foreground">Ondertitel</label>
                    <input type="hidden" name="home_sections[visibility][{{ $sectionKey }}_subtitle]" id="visibility-{{ $sectionKey }}_subtitle" value="{{ $vis('_subtitle') ? '1' : '0' }}">
                    <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" data-target="visibility-{{ $sectionKey }}_subtitle" title="Zichtbaar op website" aria-label="Ondertitel tonen/verbergen">@if($vis('_subtitle'))<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                </div>
                                @include('admin.website-pages.partials.subtitle-color-fields', ['sectionKey' => $sectionKey, 'sectionData' => $sectionData, 'subtitleColorDefault' => '#bfdbfe'])
                @php
                    $heroSubtitleWidthPct = (int) old('home_sections.'.$sectionKey.'.subtitle_width_percent', $sectionData['subtitle_width_percent'] ?? 50);
                    $heroSubtitleWidthPct = max(30, min(100, $heroSubtitleWidthPct));
                @endphp
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mb-2">
                    <label class="text-sm font-medium text-secondary-foreground shrink-0" for="hero-{{ $sectionKey }}-subtitle_width_percent">Breedte ondertitel</label>
                    <select name="home_sections[{{ $sectionKey }}][subtitle_width_percent]" id="hero-{{ $sectionKey }}-subtitle_width_percent" class="kt-input text-sm w-full max-w-[8rem]" title="Breedte ondertitel (%)">
                        @foreach ([100, 90, 80, 70, 60, 50, 40, 30] as $pct)
                            <option value="{{ $pct }}" @selected($heroSubtitleWidthPct === $pct)>{{ $pct }}%</option>
                        @endforeach
                    </select>
                    <span class="text-xs text-muted-foreground">Percentage van de bannerbreedte</span>
                </div>
                @include('admin.website-pages.partials.hero-text-bg-color-fields', ['sectionKey' => $sectionKey, 'sectionData' => $sectionData])
                @include('admin.website-pages.partials.flowbite-wysiwyg', ['editorId' => 'hero-' . $sectionKey . '-subtitle', 'name' => 'home_sections['.$sectionKey.'][subtitle]', 'value' => old('home_sections.'.$sectionKey.'.subtitle', $sectionData['subtitle'] ?? ''), 'placeholder' => 'Ons geavanceerde AI-platform...', 'textareaId' => 'home-'.$sectionKey.'-subtitle'])
            </div>
            <div class="row-visibility-row">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-sm font-medium text-secondary-foreground">Knoppen (CTA)</span>
                    <input type="hidden" name="home_sections[visibility][{{ $sectionKey }}_cta]" id="visibility-{{ $sectionKey }}_cta" value="{{ $vis('_cta') ? '1' : '0' }}">
                    <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" data-target="visibility-{{ $sectionKey }}_cta" title="Zichtbaar op website" aria-label="Knoppen tonen/verbergen">@if($vis('_cta'))<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-secondary-foreground mb-1">Knop 1 tekst</label>
                    <input type="text" name="home_sections[{{ $sectionKey }}][cta_primary_text]" class="kt-input home-section-input-400" value="{{ old('home_sections.'.$sectionKey.'.cta_primary_text', $sectionData['cta_primary_text'] ?? 'Gratis account aanmaken') }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary-foreground mb-1">Knop 1 URL</label>
                    <input type="text" name="home_sections[{{ $sectionKey }}][cta_primary_url]" class="kt-input home-section-input-400" value="{{ old('home_sections.'.$sectionKey.'.cta_primary_url', $sectionData['cta_primary_url'] ?? '/register') }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary-foreground mb-1">Knop 2 tekst</label>
                    <input type="text" name="home_sections[{{ $sectionKey }}][cta_secondary_text]" class="kt-input home-section-input-400" value="{{ old('home_sections.'.$sectionKey.'.cta_secondary_text', $sectionData['cta_secondary_text'] ?? 'Vacatures bekijken') }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary-foreground mb-1">Knop 2 URL</label>
                    <input type="text" name="home_sections[{{ $sectionKey }}][cta_secondary_url]" class="kt-input home-section-input-400" value="{{ old('home_sections.'.$sectionKey.'.cta_secondary_url', $sectionData['cta_secondary_url'] ?? '/jobs') }}">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3 pt-3 border-t border-border">
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-secondary-foreground">Knop 1 kleuren</label>
                    <div class="flex flex-col gap-2">
                        <div class="flex items-center gap-3">
                            <label class="block text-xs font-medium text-muted-foreground w-28 shrink-0">Achtergrond</label>
                            <div class="flex items-center gap-2">
                                <input type="color" id="{{ $sectionKey }}-cta-primary-bg" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($sectionData['cta_primary_bg'] ?? '') ?: '#ffffff' }}" title="Achtergrond">
                                <input type="text" name="home_sections[{{ $sectionKey }}][cta_primary_bg]" class="kt-input font-mono text-sm home-section-hex-input" value="{{ old('home_sections.'.$sectionKey.'.cta_primary_bg', $sectionData['cta_primary_bg'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="{{ $sectionKey }}-cta-primary-bg">
                                <button type="button" class="hex-clear-btn kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Leegmaken" aria-label="Leegmaken" data-color-default="#ffffff"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="block text-xs font-medium text-muted-foreground w-28 shrink-0">Tekstkleur</label>
                            <div class="flex items-center gap-2">
                                <input type="color" id="{{ $sectionKey }}-cta-primary-text-color" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($sectionData['cta_primary_text_color'] ?? '') ?: '#1e3a8a' }}" title="Tekstkleur">
                                <input type="text" name="home_sections[{{ $sectionKey }}][cta_primary_text_color]" class="kt-input font-mono text-sm home-section-hex-input" value="{{ old('home_sections.'.$sectionKey.'.cta_primary_text_color', $sectionData['cta_primary_text_color'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="{{ $sectionKey }}-cta-primary-text-color">
                                <button type="button" class="hex-clear-btn kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Leegmaken" aria-label="Leegmaken" data-color-default="#1e3a8a"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="block text-xs font-medium text-muted-foreground w-28 shrink-0">Border</label>
                            <div class="flex items-center gap-2">
                                <input type="color" id="{{ $sectionKey }}-cta-primary-border" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($sectionData['cta_primary_border'] ?? '') ?: '#1e40af' }}" title="Borderkleur">
                                <input type="text" name="home_sections[{{ $sectionKey }}][cta_primary_border]" class="kt-input font-mono text-sm home-section-hex-input" value="{{ old('home_sections.'.$sectionKey.'.cta_primary_border', $sectionData['cta_primary_border'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="{{ $sectionKey }}-cta-primary-border">
                                <button type="button" class="hex-clear-btn kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Leegmaken" aria-label="Leegmaken" data-color-default="#1e40af"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
                            </div>
                        </div>
                        @include('admin.website-pages.partials.home-section-cta-hover-colors', ['hoverPrefix' => 'cta_primary', 'hoverDefaults' => ['bg' => '#ffffff', 'text' => '#1e3a8a', 'border' => '#1e40af']])
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-secondary-foreground">Knop 2 kleuren</label>
                    <div class="flex flex-col gap-2">
                        <div class="flex items-center gap-3">
                            <label class="block text-xs font-medium text-muted-foreground w-28 shrink-0">Achtergrond</label>
                            <div class="flex items-center gap-2">
                                <input type="color" id="{{ $sectionKey }}-cta-secondary-bg" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($sectionData['cta_secondary_bg'] ?? '') ?: '#ffffff' }}" title="Achtergrond">
                                <input type="text" name="home_sections[{{ $sectionKey }}][cta_secondary_bg]" class="kt-input font-mono text-sm home-section-hex-input" value="{{ old('home_sections.'.$sectionKey.'.cta_secondary_bg', $sectionData['cta_secondary_bg'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="{{ $sectionKey }}-cta-secondary-bg">
                                <button type="button" class="hex-clear-btn kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Leegmaken" aria-label="Leegmaken" data-color-default="#ffffff"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="block text-xs font-medium text-muted-foreground w-28 shrink-0">Tekstkleur</label>
                            <div class="flex items-center gap-2">
                                <input type="color" id="{{ $sectionKey }}-cta-secondary-text-color" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($sectionData['cta_secondary_text_color'] ?? '') ?: '#1e40af' }}" title="Tekstkleur">
                                <input type="text" name="home_sections[{{ $sectionKey }}][cta_secondary_text_color]" class="kt-input font-mono text-sm home-section-hex-input" value="{{ old('home_sections.'.$sectionKey.'.cta_secondary_text_color', $sectionData['cta_secondary_text_color'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="{{ $sectionKey }}-cta-secondary-text-color">
                                <button type="button" class="hex-clear-btn kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Leegmaken" aria-label="Leegmaken" data-color-default="#1e40af"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="block text-xs font-medium text-muted-foreground w-28 shrink-0">Border</label>
                            <div class="flex items-center gap-2">
                                <input type="color" id="{{ $sectionKey }}-cta-secondary-border" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($sectionData['cta_secondary_border'] ?? '') ?: '#1e40af' }}" title="Borderkleur">
                                <input type="text" name="home_sections[{{ $sectionKey }}][cta_secondary_border]" class="kt-input font-mono text-sm home-section-hex-input" value="{{ old('home_sections.'.$sectionKey.'.cta_secondary_border', $sectionData['cta_secondary_border'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="{{ $sectionKey }}-cta-secondary-border">
                                <button type="button" class="hex-clear-btn kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Leegmaken" aria-label="Leegmaken" data-color-default="#1e40af"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
                            </div>
                        </div>
                        @include('admin.website-pages.partials.home-section-cta-hover-colors', ['hoverPrefix' => 'cta_secondary', 'hoverDefaults' => ['bg' => '#ffffff', 'text' => '#1e40af', 'border' => '#1e40af']])
                    </div>
                </div>
            </div>
            <p class="text-xs text-muted-foreground mt-2">Achtergrond, tekstkleur, border en hoverkleuren per knop. Lege hoverkleur = dezelfde kleur als de knop. Gebruik hex (bijv. #2563eb).</p>
            </div>
        </div>
    </div>
    @elseif($base === 'stats')
    @php
        $statsItems = isset($sectionData['items']) && is_array($sectionData['items']) ? $sectionData['items'] : (is_array($sectionData) ? array_values($sectionData) : []);
        $statsItems = array_slice(array_merge($statsItems, [['value'=>'','label'=>''],['value'=>'','label'=>''],['value'=>'','label'=>''],['value'=>'','label'=>'']]), 0, 4);
    @endphp
    <div class="kt-card home-section-card @if($isCardCollapsed) home-section-card--collapsed @endif" data-section="{{ $sectionKey }}">
        <div class="kt-card-header home-section-header home-section-header--stats flex items-center justify-between gap-2">
            <span class="home-section-drag-handle cursor-grab active:cursor-grabbing touch-none p-1 -ml-1 rounded text-muted-foreground hover:text-foreground" title="Sleep om volgorde te wijzigen" aria-label="Volgorde wijzigen"><svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" /></svg></span>
            <h3 class="kt-card-title">{{ $sectionLabel('stats') }}{{ $sectionKey !== 'stats' ? ' – ' . $sectionKey : '' }}</h3>
            <div class="flex items-center gap-1 shrink-0">
                <input type="hidden" name="home_sections[visibility][{{ $sectionKey }}]" id="visibility-{{ $sectionKey }}" value="{{ $vis('') ? '1' : '0' }}">
                <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground" data-target="visibility-{{ $sectionKey }}" title="{{ $vis('') ? 'Verbergen op website' : 'Tonen op website' }}" aria-label="Zichtbaarheid">
                    @if($vis(''))
                    <svg class="w-5 h-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    @else
                    <svg class="w-5 h-5 text-current opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                    @endif
                </button>
                <button type="button" class="home-section-collapse-toggle kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground" title="Inklappen" aria-label="Sectie inklappen of uitklappen">
                    <svg class="w-5 h-5 text-current home-section-collapse-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
                </button>
                <button type="button" class="home-section-remove kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-destructive" title="Sectie verwijderen" aria-label="Sectie verwijderen">
                    <svg class="w-5 h-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                </button>
            </div>
        </div>
        <div class="home-section-card-body kt-card-table p-4 space-y-3">
            @foreach([0, 1, 2, 3] as $i)
            <div class="row-visibility-row flex flex-wrap items-start gap-2 space-y-2">
                <input type="hidden" name="home_sections[visibility][{{ $sectionKey }}_{{ $i }}]" id="visibility-{{ $sectionKey }}_{{ $i }}" value="{{ ($visibility[$sectionKey.'_'.$i] ?? $visibility['stats_'.$i] ?? true) ? '1' : '0' }}">
                <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0 mt-8" data-target="visibility-{{ $sectionKey }}_{{ $i }}" title="Stat {{ $i + 1 }} tonen/verbergen" aria-label="Stat {{ $i + 1 }}">@if($visibility[$sectionKey.'_'.$i] ?? $visibility['stats_'.$i] ?? true)<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                <div class="flex-1 min-w-0 space-y-2">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-secondary-foreground mb-1">Waarde {{ $i + 1 }}</label>
                            <input type="text" name="home_sections[{{ $sectionKey }}][{{ $i }}][value]" class="kt-input home-section-input-400" value="{{ old("home_sections.{$sectionKey}.{$i}.value", ($statsItems[$i]['value'] ?? '')) }}" placeholder="10,000+">
                            @php
                                $statsDefaultColors = ['#2563eb', '#16a34a', '#111827', '#ea580c'];
                                $statsPickerColor = !empty($statsItems[$i]['value_color']) ? $statsItems[$i]['value_color'] : ($statsDefaultColors[$i] ?? '#2563eb');
                            @endphp
                            <div class="flex flex-wrap items-center gap-3 pt-1">
                                <div class="flex items-center gap-1.5">
                                    <label class="text-xs text-muted-foreground shrink-0">Kleur waarde</label>
                                    <input type="color" id="stats_value_color_picker_{{ $sectionKey }}_{{ $i }}" class="h-8 w-10 cursor-pointer rounded border border-input bg-background p-0.5 shrink-0" value="{{ $statsPickerColor }}" title="Kleur waarde" aria-label="Kleur waarde">
                                    <input type="text" name="home_sections[{{ $sectionKey }}][{{ $i }}][value_color]" class="kt-input text-xs font-mono home-section-hex-input" value="{{ old("home_sections.{$sectionKey}.{$i}.value_color", $statsItems[$i]['value_color'] ?? '') }}" placeholder="#hex" maxlength="7">
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <label class="text-xs text-muted-foreground shrink-0">Grootte waarde</label>
                                    @php
                                        $currentValueSize = $statsItems[$i]['value_size'] ?? '22';
                                        $currentValueSize = in_array($currentValueSize, ['small', 'medium', 'large'], true) ? (match($currentValueSize) { 'small' => 18, 'large' => 28, default => 22 }) : (int) $currentValueSize;
                                        if (!in_array($currentValueSize, range(10, 30, 2), true)) { $currentValueSize = 22; }
                                    @endphp
                                    <select name="home_sections[{{ $sectionKey }}][{{ $i }}][value_size]" class="kt-input text-sm w-auto min-w-[5rem]">
                                        @foreach(range(10, 30, 2) as $pt)
                                        <option value="{{ $pt }}" {{ $currentValueSize === $pt ? 'selected' : '' }}>{{ $pt }}pt</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-secondary-foreground mb-1">Label {{ $i + 1 }}</label>
                            <input type="text" name="home_sections[{{ $sectionKey }}][{{ $i }}][label]" class="kt-input home-section-input-400" value="{{ old("home_sections.{$sectionKey}.{$i}.label", ($statsItems[$i]['label'] ?? '')) }}" placeholder="Actieve vacatures">
                            <div class="flex items-center gap-1.5 pt-1">
                                <label class="text-xs text-muted-foreground shrink-0">Grootte label</label>
                                <select name="home_sections[{{ $sectionKey }}][{{ $i }}][label_size]" class="kt-input text-sm w-auto min-w-[5rem]">
                                    @php
                                        $currentLabelSize = $statsItems[$i]['label_size'] ?? '16';
                                        $currentLabelSize = in_array($currentLabelSize, ['small', 'medium', 'large'], true) ? (match($currentLabelSize) { 'small' => 12, 'large' => 20, default => 16 }) : (int) $currentLabelSize;
                                        if (!in_array($currentLabelSize, range(10, 30, 2), true)) { $currentLabelSize = 16; }
                                    @endphp
                                    @foreach(range(10, 30, 2) as $pt)
                                    <option value="{{ $pt }}" {{ $currentLabelSize === $pt ? 'selected' : '' }}>{{ $pt }}pt</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
            <div class="row-visibility-row space-y-2 pt-2 border-t border-border">
                <div class="flex items-center gap-2 mb-1">
                    <label class="text-sm font-medium text-secondary-foreground">Achtergrondplaatje</label>
                </div>
                <p class="text-xs text-muted-foreground mb-2">Leeg = geen achtergrondafbeelding.</p>
                <div class="flex flex-wrap items-start gap-2">
                    <div class="shrink-0 flex flex-col items-center">
                        <img alt="Stats achtergrond" id="hero-{{ $sectionKey }}-background_image-preview" class="w-full max-w-[200px] max-h-24 object-cover border border-border rounded {{ !empty($sectionData['background_image']) ? '' : 'hidden' }}" src="{{ $imagePreviewUrl($sectionData['background_image'] ?? '') }}">
                        <button type="button" class="image-remove-btn kt-btn kt-btn-xs kt-btn-ghost text-destructive mt-1 shadow hover:bg-destructive/10" data-url-input-id="hero-{{ $sectionKey }}-background_image" data-preview-id="hero-{{ $sectionKey }}-background_image-preview" title="Afbeelding verwijderen" aria-label="Afbeelding verwijderen"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                    </div>
                    <div class="hero-image-upload-area flex flex-col items-center justify-center p-5 lg:p-7 border border-input rounded-xl border-dashed bg-muted/30" data-section-key="{{ $sectionKey }}" data-field="background_image" style="width: 500px; min-width: 500px; height: 130px;">
                        <span class="text-xs text-muted-foreground text-center">Klik of sleep afbeelding</span>
                        <span class="text-xs text-muted-foreground">JPG, PNG, WebP (max. 5MB)</span>
                    </div>
                </div>
                <input type="file" class="hero-image-file-input hidden" accept="image/svg+xml,image/png,image/jpeg,image/jpg,image/gif,image/webp" data-section-key="{{ $sectionKey }}" data-field="background_image">
                <input type="hidden" name="home_sections[{{ $sectionKey }}][background_image]" id="hero-{{ $sectionKey }}-background_image" value="{{ old('home_sections.'.$sectionKey.'.background_image', $sectionData['background_image'] ?? '') }}">
            </div>
            <div class="space-y-2 pt-2 border-t border-border">
                <label class="block text-sm font-medium text-secondary-foreground">Achtergrondkleur sectie</label>
                <div class="flex items-center gap-2 w-full">
                    <input type="color" id="stats_bg_picker_{{ $sectionKey }}" class="h-9 w-14 cursor-pointer rounded border border-input bg-background p-1 shrink-0" value="{{ !empty($sectionData['background']) ? $sectionData['background'] : '#f3f4f6' }}" title="Kies kleur" aria-label="Achtergrondkleur">
                    <div class="home-section-hex-input-wrap shrink-0">
                    <input type="text" name="home_sections[{{ $sectionKey }}][background]" id="stats_bg_input_{{ $sectionKey }}" class="kt-input text-sm w-full font-mono home-section-hex-input" value="{{ old('home_sections.'.$sectionKey.'.background', $sectionData['background'] ?? '') }}" placeholder="#f3f4f6" maxlength="7" data-skip-validation-wrapper="1">
                    </div>
                    <button type="button" class="stats-bg-reset kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" title="Terugzetten naar standaard" aria-label="Achtergrondkleur resetten" data-picker-id="stats_bg_picker_{{ $sectionKey }}" data-input-id="stats_bg_input_{{ $sectionKey }}"><svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg></button>
                </div>
            </div>
        </div>
    </div>
    @elseif($base === 'why_nexa')
    <div class="kt-card home-section-card @if($isCardCollapsed) home-section-card--collapsed @endif" data-section="{{ $sectionKey }}">
        <div class="kt-card-header home-section-header home-section-header--why flex items-center justify-between gap-2">
            <span class="home-section-drag-handle cursor-grab active:cursor-grabbing touch-none p-1 -ml-1 rounded text-muted-foreground hover:text-foreground" title="Sleep om volgorde te wijzigen" aria-label="Volgorde wijzigen"><svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" /></svg></span>
            <h3 class="kt-card-title">{{ $sectionLabel('why_nexa') }}{{ $sectionKey !== 'why_nexa' ? ' – ' . $sectionKey : '' }}</h3>
            <div class="flex items-center gap-1 shrink-0">
                <input type="hidden" name="home_sections[visibility][{{ $sectionKey }}]" id="visibility-{{ $sectionKey }}" value="{{ $vis('') ? '1' : '0' }}">
                <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground" data-target="visibility-{{ $sectionKey }}" title="{{ $vis('') ? 'Verbergen op website' : 'Tonen op website' }}" aria-label="Zichtbaarheid">
                    @if($vis(''))
                    <svg class="w-5 h-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    @else
                    <svg class="w-5 h-5 text-current opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                    @endif
                </button>
                <button type="button" class="home-section-collapse-toggle kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground" title="Inklappen" aria-label="Sectie inklappen of uitklappen">
                    <svg class="w-5 h-5 text-current home-section-collapse-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
                </button>
                <button type="button" class="home-section-remove kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-destructive" title="Sectie verwijderen" aria-label="Sectie verwijderen">
                    <svg class="w-5 h-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                </button>
            </div>
        </div>
        <div class="home-section-card-body kt-card-table p-4 space-y-3">
            <div class="row-visibility-row">
                <div class="flex items-center gap-2 mb-1">
                    <label class="text-sm font-medium text-secondary-foreground">Titel</label>
                    <input type="hidden" name="home_sections[visibility][{{ $sectionKey }}_title]" id="visibility-{{ $sectionKey }}_title" value="{{ $vis('_title') ? '1' : '0' }}">
                    <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" data-target="visibility-{{ $sectionKey }}_title" aria-label="Titel tonen/verbergen">@if($vis('_title'))<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                </div>
                <input type="text" name="home_sections[{{ $sectionKey }}][title]" class="kt-input home-section-input-400" value="{{ old('home_sections.'.$sectionKey.'.title', $sectionData['title'] ?? 'Waarom kiezen voor Nexa?') }}">
            </div>
            <div class="row-visibility-row">
                <div class="flex items-center gap-2 mb-1">
                    <label class="text-sm font-medium text-secondary-foreground">Ondertitel</label>
                    <input type="hidden" name="home_sections[visibility][{{ $sectionKey }}_subtitle]" id="visibility-{{ $sectionKey }}_subtitle" value="{{ $vis('_subtitle') ? '1' : '0' }}">
                    <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" data-target="visibility-{{ $sectionKey }}_subtitle" aria-label="Ondertitel tonen/verbergen">@if($vis('_subtitle'))<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                </div>
                                @include('admin.website-pages.partials.subtitle-color-fields', ['sectionKey' => $sectionKey, 'sectionData' => $sectionData, 'subtitleColorDefault' => '#4b5563'])
                @include('admin.website-pages.partials.flowbite-wysiwyg', ['editorId' => 'hero-' . $sectionKey . '-subtitle', 'name' => 'home_sections['.$sectionKey.'][subtitle]', 'value' => old('home_sections.'.$sectionKey.'.subtitle', $sectionData['subtitle'] ?? ''), 'placeholder' => 'Ondertitel...', 'textareaId' => 'home-'.$sectionKey.'-subtitle'])
            </div>
            <div class="row-visibility-row space-y-2 pt-2 border-t border-border">
                <div class="flex items-center gap-2 mb-1">
                    <label class="text-sm font-medium text-secondary-foreground">Achtergrond light mode</label>
                </div>
                <p class="text-xs text-muted-foreground mb-2">Voor de lichte weergave. Wordt over de volle paginabreedte getoond.</p>
                <div class="flex flex-wrap items-start gap-2">
                    <div class="shrink-0 flex flex-col items-center">
                        <img alt="Waarom Nexa achtergrond light" id="hero-{{ $sectionKey }}-background_image-preview" class="w-full max-w-[200px] max-h-24 object-cover border border-border rounded {{ !empty($sectionData['background_image']) ? '' : 'hidden' }}" src="{{ $imagePreviewUrl($sectionData['background_image'] ?? '') }}">
                        <button type="button" class="image-remove-btn kt-btn kt-btn-xs kt-btn-ghost text-destructive mt-1 shadow hover:bg-destructive/10" data-url-input-id="hero-{{ $sectionKey }}-background_image" data-preview-id="hero-{{ $sectionKey }}-background_image-preview" title="Afbeelding verwijderen" aria-label="Afbeelding verwijderen"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                    </div>
                    <div class="hero-image-upload-area flex flex-col items-center justify-center p-5 lg:p-7 border border-input rounded-xl border-dashed bg-muted/30" data-section-key="{{ $sectionKey }}" data-field="background_image" style="width: 500px; min-width: 500px; height: 130px;">
                        <span class="text-xs text-muted-foreground text-center">Klik of sleep afbeelding</span>
                        <span class="text-xs text-muted-foreground">JPG, PNG, WebP (max. 5MB)</span>
                    </div>
                </div>
                <input type="file" class="hero-image-file-input hidden" accept="image/svg+xml,image/png,image/jpeg,image/jpg,image/gif,image/webp" data-section-key="{{ $sectionKey }}" data-field="background_image">
                <input type="hidden" name="home_sections[{{ $sectionKey }}][background_image]" id="hero-{{ $sectionKey }}-background_image" value="{{ old('home_sections.'.$sectionKey.'.background_image', $sectionData['background_image'] ?? '') }}">
            </div>
            <div class="row-visibility-row space-y-2 pt-2 border-t border-border">
                <div class="flex items-center gap-2 mb-1">
                    <label class="text-sm font-medium text-secondary-foreground">Achtergrond dark mode</label>
                </div>
                <p class="text-xs text-muted-foreground mb-2">Voor de donkere weergave. Leeg = het light-mode plaatje.</p>
                <div class="flex flex-wrap items-start gap-2">
                    <div class="shrink-0 flex flex-col items-center">
                        <img alt="Waarom Nexa achtergrond dark" id="hero-{{ $sectionKey }}-background_image_dark-preview" class="w-full max-w-[200px] max-h-24 object-cover border border-border rounded {{ !empty($sectionData['background_image_dark']) ? '' : 'hidden' }}" src="{{ $imagePreviewUrl($sectionData['background_image_dark'] ?? '') }}">
                        <button type="button" class="image-remove-btn kt-btn kt-btn-xs kt-btn-ghost text-destructive mt-1 shadow hover:bg-destructive/10" data-url-input-id="hero-{{ $sectionKey }}-background_image_dark" data-preview-id="hero-{{ $sectionKey }}-background_image_dark-preview" title="Afbeelding verwijderen" aria-label="Afbeelding verwijderen"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                    </div>
                    <div class="hero-image-upload-area flex flex-col items-center justify-center p-5 lg:p-7 border border-input rounded-xl border-dashed bg-muted/30" data-section-key="{{ $sectionKey }}" data-field="background_image_dark" style="width: 500px; min-width: 500px; height: 130px;">
                        <span class="text-xs text-muted-foreground text-center">Klik of sleep afbeelding</span>
                        <span class="text-xs text-muted-foreground">JPG, PNG, WebP (max. 5MB)</span>
                    </div>
                </div>
                <input type="file" class="hero-image-file-input hidden" accept="image/svg+xml,image/png,image/jpeg,image/jpg,image/gif,image/webp" data-section-key="{{ $sectionKey }}" data-field="background_image_dark">
                <input type="hidden" name="home_sections[{{ $sectionKey }}][background_image_dark]" id="hero-{{ $sectionKey }}-background_image_dark" value="{{ old('home_sections.'.$sectionKey.'.background_image_dark', $sectionData['background_image_dark'] ?? '') }}">
            </div>
            <div class="space-y-2 pt-2 border-t border-border">
                <label class="block text-sm font-medium text-secondary-foreground">Achtergrondkleur</label>
                <p class="text-xs text-muted-foreground mb-2">Wordt over de volle paginabreedte getoond. Leeg = standaard.</p>
                <div class="flex items-center gap-2 w-full">
                    <input type="color" id="why_bg_picker_{{ $sectionKey }}" class="h-9 w-14 cursor-pointer rounded border border-input bg-background p-1 shrink-0" value="{{ !empty($sectionData['background']) ? $sectionData['background'] : '#ffffff' }}" title="Kies kleur" aria-label="Achtergrondkleur">
                    <div class="home-section-hex-input-wrap shrink-0">
                    <input type="text" name="home_sections[{{ $sectionKey }}][background]" id="why_bg_input_{{ $sectionKey }}" class="kt-input text-sm w-full font-mono home-section-hex-input" value="{{ old('home_sections.'.$sectionKey.'.background', $sectionData['background'] ?? '') }}" placeholder="#ffffff" maxlength="7" data-skip-validation-wrapper="1">
                    </div>
                    <button type="button" class="stats-bg-reset kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" title="Terugzetten naar standaard" aria-label="Achtergrondkleur resetten" data-picker-id="why_bg_picker_{{ $sectionKey }}" data-input-id="why_bg_input_{{ $sectionKey }}"><svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg></button>
                </div>
            </div>
            @php
                $whyTitleColor = old('home_sections.'.$sectionKey.'.title_color', $sectionData['title_color'] ?? '');
                $whyTitleColor = is_string($whyTitleColor) ? trim($whyTitleColor) : '';
                if ($whyTitleColor !== '' && ! preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $whyTitleColor)) {
                    $whyTitleColor = '';
                }
                $whyTitleColorPicker = $whyTitleColor !== '' ? $whyTitleColor : '#111827';
            @endphp
            <div class="space-y-2 pt-2 border-t border-border max-w-md">
                <label class="block text-sm font-medium text-secondary-foreground" for="hero-{{ $sectionKey }}-title_color">Titelkleur</label>
                <div class="flex items-center gap-2">
                    <input type="color" id="hero-{{ $sectionKey }}-title_color_color" class="hero-subtitle-color-picker h-10 w-14 rounded border border-input cursor-pointer shrink-0" value="{{ $whyTitleColorPicker }}" title="Kleur kiezen" data-target-input="hero-{{ $sectionKey }}-title_color">
                    <div class="home-section-hex-input-wrap shrink-0">
                        <input type="text" name="home_sections[{{ $sectionKey }}][title_color]" id="hero-{{ $sectionKey }}-title_color" class="kt-input w-full font-mono text-sm home-section-hex-input hero-subtitle-color-hex-input" value="{{ $whyTitleColor }}" placeholder="#111827" maxlength="7" data-skip-validation-wrapper="1">
                    </div>
                </div>
                <p class="text-xs text-muted-foreground">Handig bij een donkere achtergrond. Leeg = standaard.</p>
            </div>
        </div>
    </div>
    @elseif($base === 'features')
    @php
        $featureSectionData = $sectionData;
        $featureSectionKey = $sectionKey;
        $featureVis = $vis;
        $featureItems = array_values($featureSectionData['items'] ?? []);
        if (count($featureItems) < 2) {
            $defItems = (\App\Models\WebsitePage::defaultHomeSections())['features']['items'] ?? [['title' => '', 'description' => '', 'icon' => 'light-bulb'], ['title' => '', 'description' => '', 'icon' => 'bolt']];
            $featureItems = array_merge($featureItems, array_slice($defItems, count($featureItems), 2 - count($featureItems)));
        }
        $heroiconList = \App\Support\HeroiconSelectOptions::sortedLabelsById();
        $heroiconSizes = config('heroicons.sizes', ['small' => ['label' => 'Klein'], 'medium' => ['label' => 'Normaal'], 'large' => ['label' => 'Groot']]);
    @endphp
    <div class="kt-card home-section-card @if($isCardCollapsed) home-section-card--collapsed @endif" data-section="{{ $sectionKey }}">
        <div class="kt-card-header home-section-header home-section-header--features flex items-center justify-between gap-2">
            <span class="home-section-drag-handle cursor-grab active:cursor-grabbing touch-none p-1 -ml-1 rounded text-muted-foreground hover:text-foreground" title="Sleep om volgorde te wijzigen" aria-label="Volgorde wijzigen"><svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" /></svg></span>
            <h3 class="kt-card-title">{{ $sectionLabel('features') }}{{ $sectionKey !== 'features' ? ' – ' . $sectionKey : '' }}</h3>
            <div class="flex items-center gap-1 shrink-0">
                <input type="hidden" name="home_sections[visibility][{{ $featureSectionKey }}]" id="visibility-{{ $featureSectionKey }}" value="{{ $featureVis('') ? '1' : '0' }}">
                <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground" data-target="visibility-{{ $featureSectionKey }}" title="{{ $featureVis('') ? 'Verbergen op website' : 'Tonen op website' }}" aria-label="Zichtbaarheid">
                    @if($featureVis(''))
                    <svg class="w-5 h-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    @else
                    <svg class="w-5 h-5 text-current opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                    @endif
                </button>
                <button type="button" class="home-section-collapse-toggle kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground" title="Inklappen" aria-label="Sectie inklappen of uitklappen">
                    <svg class="w-5 h-5 text-current home-section-collapse-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
                </button>
                <button type="button" class="home-section-remove kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-destructive" title="Sectie verwijderen" aria-label="Sectie verwijderen">
                    <svg class="w-5 h-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                </button>
            </div>
        </div>
        <div class="home-section-card-body kt-card-table p-4 space-y-4">
            @if(($themeSlugForOrder ?? '') === 'next-landing-vpn')
            @php
                $defaultFeaturesImg = asset('frontend-themes/next-landing-vpn/public/assets/Illustration2.png');
                $featuresPreviewSrc = !empty($featureSectionData['illustration_url']) ? $featureSectionData['illustration_url'] : $defaultFeaturesImg;
            @endphp
            <div class="row-visibility-row">
                <label class="block text-sm font-medium text-secondary-foreground mb-1">Illustratie Kenmerken-sectie</label>
                <p class="text-xs text-muted-foreground mb-2">Afbeelding naast de kenmerken. Standaard: Illustration2.png.</p>
                <div class="flex flex-wrap items-start gap-2">
                    <div class="shrink-0 flex flex-col items-center">
                        <img alt="Kenmerken illustratie" id="hero-{{ $featureSectionKey }}-author-preview" class="w-full max-w-[200px] max-h-40 object-contain border border-border rounded-lg {{ $featuresPreviewSrc ? '' : 'hidden' }}" src="{{ $imagePreviewUrl($featuresPreviewSrc) }}" data-default-src="{{ $defaultFeaturesImg ?? '' }}">
                        <button type="button" class="image-remove-btn kt-btn kt-btn-xs kt-btn-ghost text-destructive mt-1 shadow hover:bg-destructive/10" data-url-input-id="hero-{{ $featureSectionKey }}-illustration_url" data-preview-id="hero-{{ $featureSectionKey }}-author-preview" title="Afbeelding verwijderen" aria-label="Afbeelding verwijderen"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                    </div>
                    <div class="hero-image-upload-area flex flex-col items-center justify-center p-5 lg:p-7 border border-input rounded-xl border-dashed bg-muted/30" data-section-key="{{ $featureSectionKey }}" data-field="illustration_url" style="width: 500px; min-width: 500px; height: 130px;">
                        <span class="text-xs text-muted-foreground text-center">Klik of sleep afbeelding</span>
                        <span class="text-xs text-muted-foreground">JPG, PNG, WebP (max. 5MB)</span>
                    </div>
                </div>
                <input type="file" class="hero-image-file-input hidden" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" data-section-key="{{ $featureSectionKey }}" data-field="illustration_url">
                <input type="hidden" name="home_sections[{{ $featureSectionKey }}][illustration_url]" id="hero-{{ $featureSectionKey }}-illustration_url" value="{{ old('home_sections.'.$featureSectionKey.'.illustration_url', $featureSectionData['illustration_url'] ?? '') }}">
            </div>
            @endif
            <div class="row-visibility-row">
                <div class="flex items-center gap-2 mb-1">
                    <label class="text-sm font-medium text-secondary-foreground">Sectietitel</label>
                    <input type="hidden" name="home_sections[visibility][{{ $featureSectionKey }}_section_title]" id="visibility-{{ $featureSectionKey }}_section_title" value="{{ ($visibility[$featureSectionKey.'_section_title'] ?? $visibility['features_section_title'] ?? true) ? '1' : '0' }}">
                    <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" data-target="visibility-{{ $featureSectionKey }}_section_title" aria-label="Sectietitel tonen/verbergen">@if($visibility[$featureSectionKey.'_section_title'] ?? $visibility['features_section_title'] ?? true)<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                </div>
                <input type="text" name="home_sections[{{ $featureSectionKey }}][section_title]" class="kt-input home-section-input-400" value="{{ old('home_sections.'.$featureSectionKey.'.section_title', $featureSectionData['section_title'] ?? 'Kenmerken') }}">
            </div>
            <div class="features-items-sortable space-y-4" data-section-key="{{ $featureSectionKey }}" data-icon-options="{{ json_encode($heroiconList) }}" data-size-options="{{ json_encode(collect($heroiconSizes)->map(fn($v) => $v['label'] ?? '')->all()) }}">
            @foreach($featureItems as $i => $item)
            <div class="features-item-row row-visibility-row border border-border rounded-lg p-4 space-y-3 flex gap-3" data-features-index="{{ $i }}">
                <span class="features-item-drag-handle cursor-grab active:cursor-grabbing touch-none shrink-0 mt-1 p-1 rounded text-muted-foreground hover:text-foreground" title="Sleep om volgorde te wijzigen" aria-label="Volgorde wijzigen"><svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" /></svg></span>
                <div class="flex-1 min-w-0 space-y-3">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        <p class="text-sm font-medium text-secondary-foreground">Kaart <span class="features-item-num">{{ $i + 1 }}</span></p>
                        <input type="hidden" name="home_sections[visibility][{{ $featureSectionKey }}_item_{{ $i }}]" id="visibility-{{ $featureSectionKey }}_item_{{ $i }}" value="{{ ($visibility[$featureSectionKey.'_item_'.$i] ?? $visibility['features_item_'.$i] ?? true) ? '1' : '0' }}">
                        <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" data-target="visibility-{{ $featureSectionKey }}_item_{{ $i }}" aria-label="Kaart tonen/verbergen">@if($visibility[$featureSectionKey.'_item_'.$i] ?? $visibility['features_item_'.$i] ?? true)<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                        <button type="button" class="features-item-remove kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Kaart verwijderen" aria-label="Verwijderen"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                    </div>
                    <div>
                        <label class="block text-xs text-muted-foreground mb-1">Titel</label>
                        <input type="text" name="home_sections[{{ $featureSectionKey }}][items][{{ $i }}][title]" class="kt-input home-section-input-400 features-item-title" value="{{ old("home_sections.".$featureSectionKey.".items.{$i}.title", $item['title'] ?? '') }}">
                    </div>
                    <div>
                        <label class="block text-xs text-muted-foreground mb-1">Beschrijving</label>
                        @include('admin.website-pages.partials.flowbite-wysiwyg', ['editorId' => 'home-'.$featureSectionKey.'-item-'.$i.'-description', 'name' => 'home_sections['.$featureSectionKey.'][items]['.$i.'][description]', 'value' => old('home_sections.'.$featureSectionKey.'.items.'.$i.'.description', $item['description'] ?? ''), 'placeholder' => '', 'textareaId' => 'home-'.$featureSectionKey.'-item-'.$i.'-description'])
                    </div>
                    <div class="flex flex-col gap-2">
                        <div class="flex items-center gap-3">
                            <label class="block text-xs font-medium text-muted-foreground w-40 shrink-0">Icoon (Heroicon)</label>
                            <select name="home_sections[{{ $featureSectionKey }}][items][{{ $i }}][icon]" class="kt-input w-44 shrink-0 features-item-icon">
                                @foreach($heroiconList as $iconId => $iconLabel)
                                <option value="{{ $iconId }}" {{ ($item['icon'] ?? ($i === 0 ? 'light-bulb' : 'bolt')) === $iconId ? 'selected' : '' }}>{{ $iconLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="block text-xs font-medium text-muted-foreground w-40 shrink-0">Grootte icoon</label>
                            <select name="home_sections[{{ $featureSectionKey }}][items][{{ $i }}][icon_size]" class="kt-input w-44 shrink-0 features-item-icon-size">
                                @foreach($heroiconSizes as $sizeId => $sizeData)
                                <option value="{{ $sizeId }}" {{ ($item['icon_size'] ?? 'medium') === $sizeId ? 'selected' : '' }}>{{ $sizeData['label'] ?? $sizeId }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="block text-xs font-medium text-muted-foreground w-40 shrink-0">Positie titel en icoon</label>
                            <select name="home_sections[{{ $featureSectionKey }}][items][{{ $i }}][icon_align]" class="kt-input w-44 shrink-0 features-item-icon-align">
                                <option value="left" {{ ($item['icon_align'] ?? 'center') === 'left' ? 'selected' : '' }}>Links</option>
                                <option value="center" {{ ($item['icon_align'] ?? 'center') === 'center' ? 'selected' : '' }}>Midden</option>
                                <option value="right" {{ ($item['icon_align'] ?? 'center') === 'right' ? 'selected' : '' }}>Rechts</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
            </div>
            <div class="mt-4">
                <button type="button" class="features-item-add kt-btn kt-btn-sm kt-btn-outline" data-section-key="{{ $featureSectionKey }}"><svg class="w-4 h-4 me-1 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>Kaart toevoegen</button>
            </div>
        </div>
    </div>
    @elseif($base === 'cards_ronde_hoeken')
    @php
        $cardsItems = array_values($sectionData['items'] ?? [['image_url' => '', 'text' => '', 'font_size' => 14, 'font_style' => 'normal', 'card_size' => 'normal', 'text_align' => 'left']]);
        if (empty($cardsItems)) {
            $cardsItems = [['image_url' => '', 'text' => '', 'font_size' => 14, 'font_style' => 'normal', 'card_size' => 'normal', 'text_align' => 'left']];
        }
        $cardsFontStyles = ['normal' => 'Normaal', 'bold' => 'Vet', 'italic' => 'Cursief'];
        $cardsCardSizes = ['small' => 'Klein (300px)', 'normal' => 'Normaal (400px)', 'large' => 'Groot (600px)', 'xlarge' => 'Extra groot (800px)', 'max' => 'Maximaal (volledige breedte)', 'total_width' => 'Totaalformaat cards'];
        $cardsTextAligns = ['left' => 'Links', 'center' => 'Midden', 'right' => 'Rechts'];
        $cardsImagePaddings = [0 => '0px'] + array_combine($a = range(2, 30, 2), array_map(fn($v) => $v . 'px', $a));
    @endphp
    <div class="kt-card home-section-card @if($isCardCollapsed) home-section-card--collapsed @endif" data-section="{{ $sectionKey }}">
        <div class="kt-card-header home-section-header home-section-header--cards flex items-center justify-between gap-2">
            <span class="home-section-drag-handle cursor-grab active:cursor-grabbing touch-none p-1 -ml-1 rounded text-muted-foreground hover:text-foreground" title="Sleep om volgorde te wijzigen" aria-label="Volgorde wijzigen"><svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" /></svg></span>
            <h3 class="kt-card-title">{{ $sectionLabel('cards_ronde_hoeken') }}{{ $sectionKey !== 'cards_ronde_hoeken' ? ' – ' . $sectionKey : '' }}</h3>
            <div class="flex items-center gap-1 shrink-0">
                <input type="hidden" name="home_sections[visibility][{{ $sectionKey }}]" id="visibility-{{ $sectionKey }}" value="{{ $vis('') ? '1' : '0' }}">
                <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground" data-target="visibility-{{ $sectionKey }}" title="{{ $vis('') ? 'Verbergen op website' : 'Tonen op website' }}" aria-label="Zichtbaarheid">@if($vis(''))<svg class="w-5 h-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-5 h-5 text-current opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                <button type="button" class="home-section-collapse-toggle kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground" title="Inklappen" aria-label="Sectie inklappen of uitklappen"><svg class="w-5 h-5 text-current home-section-collapse-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg></button>
                <button type="button" class="home-section-remove kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-destructive" title="Sectie verwijderen" aria-label="Sectie verwijderen"><svg class="w-5 h-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
            </div>
        </div>
        <div class="home-section-card-body kt-card-table p-4 space-y-4">
            <div class="flex flex-col gap-2">
                <p class="text-sm text-muted-foreground">Kaarten met afbeelding en tekst eronder. Tekst per kaart kan met het oogje uitgeschakeld worden.</p>
                <div class="flex items-center gap-2">
                    <label class="text-sm text-muted-foreground shrink-0">Kaarten per regel:</label>
                    <select name="home_sections[{{ $sectionKey }}][cards_per_row]" class="kt-input w-20 text-sm">
                        @foreach([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5', 6 => '6'] as $val => $label)
                        <option value="{{ $val }}" {{ (int)($sectionData['cards_per_row'] ?? 4) === (int)$val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div id="cards-ronde-hoeken-items-{{ $sectionKey }}" class="space-y-4" data-section-key="{{ $sectionKey }}">
                @foreach($cardsItems as $i => $cardItem)
                @php
                    $cardBodyFontPx = max(10, min(24, (int) old('home_sections.'.$sectionKey.'.items.'.$i.'.font_size', $cardItem['font_size'] ?? 14)));
                @endphp
                <div class="cards-ronde-hoeken-item border border-border rounded-lg p-4 space-y-3" style="--card-fs: {{ $cardBodyFontPx }}px;" data-cards-index="{{ $i }}">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-sm font-medium">Kaart {{ $i + 1 }}</span>
                        <button type="button" class="cards-ronde-hoeken-item-remove kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive" title="Kaart verwijderen" aria-label="Verwijderen"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                    </div>
                    <div class="flex flex-wrap items-start gap-2">
                        <div class="shrink-0 flex flex-col items-center">
                            <img alt="Kaart {{ $i + 1 }}" id="hero-{{ $sectionKey }}-items_{{ $i }}_image_url-preview" class="cards-ronde-hoeken-upload-preview w-full max-w-[200px] max-h-24 object-cover border border-border rounded {{ !empty($cardItem['image_url']) ? '' : 'hidden' }}" src="{{ $imagePreviewUrl($cardItem['image_url'] ?? '') }}">
                            <button type="button" class="image-remove-btn kt-btn kt-btn-xs kt-btn-ghost text-destructive mt-1 shadow hover:bg-destructive/10" data-url-input-id="hero-{{ $sectionKey }}-items_{{ $i }}_image_url" data-preview-id="hero-{{ $sectionKey }}-items_{{ $i }}_image_url-preview" title="Afbeelding verwijderen" aria-label="Afbeelding verwijderen"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                        </div>
                        <div class="hero-image-upload-area flex flex-col items-center justify-center p-5 lg:p-7 border border-input rounded-xl border-dashed bg-muted/30" data-section-key="{{ $sectionKey }}" data-field="items_{{ $i }}_image_url" data-url-input-id="hero-{{ $sectionKey }}-items_{{ $i }}_image_url" data-file-input-id="hero-{{ $sectionKey }}-items_{{ $i }}_image_url-file" data-preview-id="hero-{{ $sectionKey }}-items_{{ $i }}_image_url-preview" style="width: 500px; min-width: 500px; height: 130px;">
                            <span class="text-xs text-muted-foreground">Klik of sleep afbeelding</span>
                            <span class="text-xs text-muted-foreground">JPG, PNG, WebP (max. 5MB)</span>
                        </div>
                    </div>
                    <input type="file" id="hero-{{ $sectionKey }}-items_{{ $i }}_image_url-file" class="hero-image-file-input hidden" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" data-section-key="{{ $sectionKey }}" data-field="items_{{ $i }}_image_url">
                    <input type="hidden" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][image_url]" id="hero-{{ $sectionKey }}-items_{{ $i }}_image_url" value="{{ old('home_sections.'.$sectionKey.'.items.'.$i.'.image_url', $cardItem['image_url'] ?? '') }}">
                    <div class="space-y-2 mt-3">
                        <div class="flex flex-wrap items-center gap-4">
                            <label class="text-sm font-medium text-secondary-foreground shrink-0">Tekst onder afbeelding</label>
                            <input type="hidden" name="home_sections[visibility][{{ $sectionKey }}_item_{{ $i }}]" id="visibility-{{ $sectionKey }}_item_{{ $i }}" value="{{ ($visibility[$sectionKey.'_item_'.$i] ?? true) ? '1' : '0' }}">
                            <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" data-target="visibility-{{ $sectionKey }}_item_{{ $i }}" aria-label="Tekst tonen/verbergen">@if($visibility[$sectionKey.'_item_'.$i] ?? true)<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                        </div>
                        <div class="flex flex-col gap-2">
                            <div class="flex items-center gap-3">
                                <label class="text-sm text-muted-foreground shrink-0 w-40">Kaartgrootte</label>
                                <select name="home_sections[{{ $sectionKey }}][items][{{ $i }}][card_size]" class="kt-input w-36 text-sm">
                                    @foreach($cardsCardSizes as $val => $label)
                                    <option value="{{ $val }}" {{ ($cardItem['card_size'] ?? 'normal') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="text-sm text-muted-foreground shrink-0 w-40">Stijl</label>
                                <select name="home_sections[{{ $sectionKey }}][items][{{ $i }}][font_style]" class="kt-input w-28 text-sm">
                                    @foreach($cardsFontStyles as $val => $label)
                                    <option value="{{ $val }}" {{ ($cardItem['font_style'] ?? 'normal') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <input type="hidden" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][font_size]" value="{{ $cardBodyFontPx }}">
                            <div class="flex items-center gap-3">
                                <label class="text-sm text-muted-foreground shrink-0 w-40">Uitlijning</label>
                                <select name="home_sections[{{ $sectionKey }}][items][{{ $i }}][text_align]" class="kt-input w-28 text-sm">
                                    @foreach($cardsTextAligns as $val => $label)
                                    <option value="{{ $val }}" {{ ($cardItem['text_align'] ?? 'left') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @php $cardImagePadding = isset($cardItem['image_padding']) ? max(0, min(30, (int) $cardItem['image_padding'])) : 2; $cardImagePadding = (int) (round($cardImagePadding / 2) * 2); @endphp
                            <div class="flex items-center gap-3">
                                <label class="text-sm text-muted-foreground shrink-0 w-40">Padding afbeelding</label>
                                <select name="home_sections[{{ $sectionKey }}][items][{{ $i }}][image_padding]" class="kt-input w-24 text-sm">
                                    @foreach($cardsImagePaddings as $px => $label)
                                    <option value="{{ $px }}" {{ $cardImagePadding === (int)$px ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="text-sm text-muted-foreground shrink-0 w-40">Achtergrondkleur afbeelding</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" id="cards-{{ $sectionKey }}-item-{{ $i }}-image-bg" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($cardItem['image_bg_color'] ?? '') ?: '#e5e7eb' }}" title="Achtergrondkleur">
                                    <input type="text" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][image_bg_color]" id="cards-{{ $sectionKey }}-item-{{ $i }}-image-bg-hex" class="kt-input font-mono text-sm home-section-hex-input" value="{{ old('home_sections.'.$sectionKey.'.items.'.$i.'.image_bg_color', $cardItem['image_bg_color'] ?? '') }}" placeholder="#hex of leeg" maxlength="7" data-sync-from="cards-{{ $sectionKey }}-item-{{ $i }}-image-bg">
                                    <button type="button" class="hex-clear-btn kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Leegmaken" aria-label="Leegmaken" data-color-default="#e5e7eb"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="text-sm text-muted-foreground shrink-0 w-40">Tekstkleur</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" id="cards-{{ $sectionKey }}-item-{{ $i }}-text-color" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($cardItem['text_color'] ?? '') ?: '#374151' }}" title="Tekstkleur">
                                    <input type="text" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][text_color]" id="cards-{{ $sectionKey }}-item-{{ $i }}-text-color-hex" class="kt-input font-mono text-sm home-section-hex-input" value="{{ old('home_sections.'.$sectionKey.'.items.'.$i.'.text_color', $cardItem['text_color'] ?? '') }}" placeholder="#hex of leeg" maxlength="7" data-sync-from="cards-{{ $sectionKey }}-item-{{ $i }}-text-color">
                                    <button type="button" class="hex-clear-btn kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Leegmaken" aria-label="Leegmaken" data-color-default="#374151"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
                                </div>
                            </div>
                        </div>
                        <div class="w-full min-w-0">
                            @include('admin.website-pages.partials.flowbite-wysiwyg', ['editorId' => 'home-cards-'.$sectionKey.'-item-'.$i.'-text', 'name' => 'home_sections['.$sectionKey.'][items]['.$i.'][text]', 'value' => old('home_sections.'.$sectionKey.'.items.'.$i.'.text', $cardItem['text'] ?? ''), 'placeholder' => 'Tekst onder de afbeelding (rich text)', 'textareaId' => 'home-cards-'.$sectionKey.'-item-'.$i.'-text', 'contentMinHeightPx' => 200, 'contentMaxHeightPx' => 220])
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mt-4">
                <button type="button" class="cards-ronde-hoeken-item-add kt-btn kt-btn-sm kt-btn-outline" data-section-key="{{ $sectionKey }}"><svg class="w-4 h-4 me-1 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>Card toevoegen</button>
            </div>
        </div>
    </div>
    @elseif($base === 'featured_services')
    @php
        $fsItems = array_values($sectionData['items'] ?? []);
        if (empty($fsItems)) {
            $fsItems = [['icon' => 'light-bulb', 'title' => '', 'description' => '']];
        }
        $heroiconListFs = \App\Support\HeroiconSelectOptions::sortedLabelsById(['bulb', 'lightning']);
        $allowedFsHeadingPx = range(10, 40, 2);
        $fsTitlePxSel = (int) old('home_sections.'.$sectionKey.'.title_font_size_px', (int) ($sectionData['title_font_size_px'] ?? 24));
        if (! in_array($fsTitlePxSel, $allowedFsHeadingPx, true)) {
            $fsTitlePxSel = 24;
        }
        $fsSubtitlePxSel = (int) old('home_sections.'.$sectionKey.'.subtitle_font_size_px', (int) ($sectionData['subtitle_font_size_px'] ?? 18));
        if (! in_array($fsSubtitlePxSel, $allowedFsHeadingPx, true)) {
            $fsSubtitlePxSel = 18;
        }
        $fsItemTitlePxSel = (int) old('home_sections.'.$sectionKey.'.item_title_font_size_px', (int) ($sectionData['item_title_font_size_px'] ?? 18));
        if (! in_array($fsItemTitlePxSel, $allowedFsHeadingPx, true)) {
            $fsItemTitlePxSel = 18;
        }
        $fsItemDescPxSel = (int) old('home_sections.'.$sectionKey.'.item_description_font_size_px', (int) ($sectionData['item_description_font_size_px'] ?? 14));
        if (! in_array($fsItemDescPxSel, $allowedFsHeadingPx, true)) {
            $fsItemDescPxSel = 14;
        }
    @endphp
    <div class="kt-card home-section-card @if($isCardCollapsed) home-section-card--collapsed @endif" data-section="{{ $sectionKey }}">
        <div class="kt-card-header home-section-header home-section-header--featured-services flex items-center justify-between gap-2">
            <span class="home-section-drag-handle cursor-grab active:cursor-grabbing touch-none p-1 -ml-1 rounded text-muted-foreground hover:text-foreground" title="Sleep om volgorde te wijzigen" aria-label="Volgorde wijzigen"><svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" /></svg></span>
            <h3 class="kt-card-title">{{ $sectionLabel('featured_services') }}{{ $sectionKey !== 'featured_services' ? ' – ' . $sectionKey : '' }}</h3>
            <div class="flex items-center gap-1 shrink-0">
                <input type="hidden" name="home_sections[visibility][{{ $sectionKey }}]" id="visibility-{{ $sectionKey }}" value="{{ $vis('') ? '1' : '0' }}">
                <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground" data-target="visibility-{{ $sectionKey }}" title="{{ $vis('') ? 'Verbergen op website' : 'Tonen op website' }}" aria-label="Zichtbaarheid">@if($vis(''))<svg class="w-5 h-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-5 h-5 text-current opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                <button type="button" class="home-section-collapse-toggle kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground" title="Inklappen" aria-label="Sectie inklappen of uitklappen"><svg class="w-5 h-5 text-current home-section-collapse-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg></button>
                <button type="button" class="home-section-remove kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-destructive" title="Sectie verwijderen" aria-label="Sectie verwijderen"><svg class="w-5 h-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
            </div>
        </div>
        <div class="home-section-card-body kt-card-table p-4 space-y-6">
            <p class="text-sm text-muted-foreground">Dienstenblok met scroll-animatie. Titel, ondertitel en per blok icoon, titel en beschrijving bewerkbaar.</p>
            <div class="grid gap-x-4" style="grid-template-columns: 11rem minmax(18rem, 1fr); row-gap: 1rem;">
                <label class="text-sm font-medium text-secondary-foreground flex items-center">Blokken per regel</label>
                <select name="home_sections[{{ $sectionKey }}][blocks_per_row]" class="kt-input text-sm w-full">
                    @foreach([2 => '2', 3 => '3', 4 => '4'] as $val => $label)
                    <option value="{{ $val }}" {{ (int)($sectionData['blocks_per_row'] ?? 3) === (int)$val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <label class="text-sm font-medium text-secondary-foreground flex items-center">Grootte blokken</label>
                <select name="home_sections[{{ $sectionKey }}][block_size]" class="kt-input text-sm w-full">
                    <option value="small" {{ ($sectionData['block_size'] ?? 'medium') === 'small' ? 'selected' : '' }}>Klein (300px)</option>
                    <option value="medium" {{ ($sectionData['block_size'] ?? 'medium') === 'medium' ? 'selected' : '' }}>Middel (500px)</option>
                    <option value="large" {{ ($sectionData['block_size'] ?? 'medium') === 'large' ? 'selected' : '' }}>Groot (700px)</option>
                    <option value="full" {{ ($sectionData['block_size'] ?? 'medium') === 'full' ? 'selected' : '' }}>Hele breedte</option>
                </select>
                <label class="text-sm font-medium text-secondary-foreground flex items-center">Uitlijning</label>
                <select name="home_sections[{{ $sectionKey }}][block_align]" class="kt-input text-sm w-full">
                    <option value="left" {{ ($sectionData['block_align'] ?? 'center') === 'left' ? 'selected' : '' }}>Links</option>
                    <option value="center" {{ ($sectionData['block_align'] ?? 'center') === 'center' ? 'selected' : '' }}>Midden</option>
                    <option value="right" {{ ($sectionData['block_align'] ?? 'center') === 'right' ? 'selected' : '' }}>Rechts</option>
                </select>
                <label class="text-sm font-medium text-secondary-foreground flex items-start gap-1 self-start">Breedte blokkenrij <span class="font-normal text-muted-foreground">(%)</span></label>
                <div class="w-full">
                    <input type="number" name="home_sections[{{ $sectionKey }}][blocks_row_width_percent]" class="kt-input text-sm w-full" min="1" max="100" step="1" value="{{ old('home_sections.'.$sectionKey.'.blocks_row_width_percent', (int)($sectionData['blocks_row_width_percent'] ?? 100)) }}" title="Ten opzichte van de inhoudscontainer op de website (100 = volle breedte)">
                    <p class="text-xs text-muted-foreground mt-1">Beperkt de breedte van het raster met de dienstblokken; uitlijning hierboven bepaalt de positie.</p>
                </div>
                <label class="text-sm font-medium text-secondary-foreground flex items-center">Icoon grootte</label>
                <select name="home_sections[{{ $sectionKey }}][icon_size]" class="kt-input text-sm w-full">
                    <option value="small" {{ ($sectionData['icon_size'] ?? 'medium') === 'small' ? 'selected' : '' }}>Klein (20px)</option>
                    <option value="medium" {{ ($sectionData['icon_size'] ?? 'medium') === 'medium' ? 'selected' : '' }}>Midden (30px)</option>
                    <option value="large" {{ ($sectionData['icon_size'] ?? 'medium') === 'large' ? 'selected' : '' }}>Groot (40px)</option>
                </select>
                <label class="text-sm font-medium text-secondary-foreground flex items-center">Icoon uitlijning</label>
                <select name="home_sections[{{ $sectionKey }}][icon_align]" class="kt-input text-sm w-full">
                    <option value="top" {{ ($sectionData['icon_align'] ?? 'center') === 'top' ? 'selected' : '' }}>Boven</option>
                    <option value="center" {{ ($sectionData['icon_align'] ?? 'center') === 'center' ? 'selected' : '' }}>Midden</option>
                    <option value="bottom" {{ ($sectionData['icon_align'] ?? 'center') === 'bottom' ? 'selected' : '' }}>Onder</option>
                </select>
                <label class="text-sm font-medium text-secondary-foreground flex items-center">Animatiesnelheid</label>
                <select name="home_sections[{{ $sectionKey }}][animation_speed]" class="kt-input text-sm w-full">
                    <option value="fast" {{ ($sectionData['animation_speed'] ?? 'slow') === 'fast' ? 'selected' : '' }}>Snel (0,4 s)</option>
                    <option value="normal" {{ ($sectionData['animation_speed'] ?? 'slow') === 'normal' ? 'selected' : '' }}>Normaal (0,6 s)</option>
                    <option value="slow" {{ ($sectionData['animation_speed'] ?? 'slow') === 'slow' ? 'selected' : '' }}>Langzaam (0,9 s)</option>
                    <option value="slower" {{ ($sectionData['animation_speed'] ?? 'slow') === 'slower' ? 'selected' : '' }}>Zeer langzaam (1,2 s)</option>
                </select>
                <label class="text-sm font-medium text-secondary-foreground flex items-center">Achtergrondkleur kaarten</label>
                <div class="flex items-center gap-2 w-full">
                    <input type="color" id="featured_services_card_bg_picker_{{ $sectionKey }}" class="h-9 w-14 cursor-pointer rounded border border-input bg-background p-1" value="{{ !empty($sectionData['card_bg_color']) ? $sectionData['card_bg_color'] : '#ffffff' }}" title="Kies kleur" aria-label="Kies achtergrondkleur">
                    <div class="home-section-hex-input-wrap shrink-0">
                    <input type="text" name="home_sections[{{ $sectionKey }}][card_bg_color]" id="featured_services_card_bg_input_{{ $sectionKey }}" class="kt-input text-sm w-full font-mono home-section-hex-input" value="{{ old('home_sections.'.$sectionKey.'.card_bg_color', $sectionData['card_bg_color'] ?? '') }}" placeholder="#f3f4f6" maxlength="7" pattern="^#?([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})?$" data-skip-validation-wrapper="1">
                    </div>
                    <button type="button" class="featured-services-card-bg-reset kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" title="Terugzetten naar standaard" aria-label="Achtergrondkleur resetten" data-picker-id="featured_services_card_bg_picker_{{ $sectionKey }}" data-input-id="featured_services_card_bg_input_{{ $sectionKey }}"><svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg></button>
                </div>
            </div>
            <div class="space-y-2 max-w-xl mt-3">
                <div>
                    <label class="text-sm font-medium text-secondary-foreground block mb-1">Sectietitel</label>
                    <input type="text" name="home_sections[{{ $sectionKey }}][title]" class="kt-input w-full" value="{{ old('home_sections.'.$sectionKey.'.title', $sectionData['title'] ?? 'Diensten') }}" placeholder="Bijv. Diensten">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2">
                    <div>
                        <label class="text-sm font-medium text-secondary-foreground block mb-1">Tekstgrootte titel</label>
                        <select name="home_sections[{{ $sectionKey }}][title_font_size_px]" class="kt-input text-sm w-full" title="Pixelgrootte op de website">
                            @foreach(range(10, 40, 2) as $fsPx)
                            <option value="{{ $fsPx }}" {{ $fsTitlePxSel === (int) $fsPx ? 'selected' : '' }}>{{ $fsPx }} px</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-secondary-foreground block mb-1">Tekstgrootte ondertitel</label>
                        <select name="home_sections[{{ $sectionKey }}][subtitle_font_size_px]" class="kt-input text-sm w-full" title="Pixelgrootte op de website">
                            @foreach(range(10, 40, 2) as $fsPx)
                            <option value="{{ $fsPx }}" {{ $fsSubtitlePxSel === (int) $fsPx ? 'selected' : '' }}>{{ $fsPx }} px</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="text-sm font-medium text-secondary-foreground block mb-1">Ondertitel</label>
                    <textarea name="home_sections[{{ $sectionKey }}][subtitle]" class="kt-input w-full min-h-[52px]" rows="2" placeholder="Korte ondertitel">{{ old('home_sections.'.$sectionKey.'.subtitle', $sectionData['subtitle'] ?? '') }}</textarea>
                </div>
            </div>
            <div class="space-y-2 max-w-xl mt-4 pt-3 border-t border-border">
                <p class="text-sm font-medium text-secondary-foreground">Tekst in de kaarten <span class="font-normal text-muted-foreground">(geldt voor alle blokken)</span></p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2">
                    <div>
                        <label class="text-sm font-medium text-secondary-foreground block mb-1">Titel per blok</label>
                        <select name="home_sections[{{ $sectionKey }}][item_title_font_size_px]" class="kt-input text-sm w-full" title="Pixelgrootte op de website">
                            @foreach(range(10, 40, 2) as $fsPx)
                            <option value="{{ $fsPx }}" {{ $fsItemTitlePxSel === (int) $fsPx ? 'selected' : '' }}>{{ $fsPx }} px</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-secondary-foreground block mb-1">Beschrijving per blok</label>
                        <select name="home_sections[{{ $sectionKey }}][item_description_font_size_px]" class="kt-input text-sm w-full" title="Pixelgrootte op de website">
                            @foreach(range(10, 40, 2) as $fsPx)
                            <option value="{{ $fsPx }}" {{ $fsItemDescPxSel === (int) $fsPx ? 'selected' : '' }}>{{ $fsPx }} px</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="featured-services-items space-y-4 mt-3" data-section-key="{{ $sectionKey }}" id="featured-services-items-{{ $sectionKey }}" data-icon-options="{{ json_encode($heroiconListFs) }}">
                @foreach($fsItems as $i => $fsItem)
                <div class="featured-services-item border border-border rounded-lg p-3 space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-sm font-medium">Blok {{ $i + 1 }}</span>
                        <button type="button" class="featured-services-item-remove kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive" title="Blok verwijderen" aria-label="Verwijderen"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                    </div>
                    <div class="flex gap-2 items-center">
                        <label class="text-sm text-muted-foreground shrink-0 w-24">Icoon</label>
                        <select name="home_sections[{{ $sectionKey }}][items][{{ $i }}][icon]" class="kt-input text-sm w-auto min-w-[10rem] max-w-full">
                            @foreach($heroiconListFs as $ic => $lbl)
                                <option value="{{ $ic }}" {{ ($fsItem['icon'] ?? 'light-bulb') === $ic ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2 items-center">
                        <label class="text-sm text-muted-foreground shrink-0 w-24">Icoonkleur</label>
                        <div class="flex items-center gap-2">
                            <input type="color" class="featured-services-icon-color-picker h-9 w-14 cursor-pointer rounded border border-input bg-background p-1" value="{{ !empty($fsItem['icon_color']) ? $fsItem['icon_color'] : '#2563eb' }}" title="Kies icoonkleur" aria-label="Icoonkleur">
                            <input type="text" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][icon_color]" class="kt-input text-sm font-mono home-section-hex-input" value="{{ old('home_sections.'.$sectionKey.'.items.'.$i.'.icon_color', $fsItem['icon_color'] ?? '') }}" placeholder="#hex" maxlength="7" pattern="^#?([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})?$">
                        </div>
                    </div>
                    <div>
                        <label class="text-sm text-muted-foreground block mb-1">Titel blok</label>
                        <input type="text" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][title]" class="kt-input w-full max-w-[50%] text-sm" value="{{ old('home_sections.'.$sectionKey.'.items.'.$i.'.title', $fsItem['title'] ?? '') }}" placeholder="Titel">
                    </div>
                    <div>
                        <label class="text-sm text-muted-foreground block mb-1">Beschrijving</label>
                        <textarea name="home_sections[{{ $sectionKey }}][items][{{ $i }}][description]" class="kt-input w-full max-w-[50%] text-sm min-h-[60px]" rows="2" placeholder="Beschrijving">{{ old('home_sections.'.$sectionKey.'.items.'.$i.'.description', $fsItem['description'] ?? '') }}</textarea>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mt-4">
                <button type="button" class="featured-services-item-add kt-btn kt-btn-sm kt-btn-outline" data-section-key="{{ $sectionKey }}"><svg class="w-4 h-4 me-1 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>Blok toevoegen</button>
            </div>
        </div>
    </div>
    @elseif($base === 'email_template')
    <div class="kt-card home-section-card @if($isCardCollapsed) home-section-card--collapsed @endif" data-section="{{ $sectionKey }}">
        <div class="kt-card-header home-section-header home-section-header--email-template flex items-center justify-between gap-2">
            <span class="home-section-drag-handle cursor-grab active:cursor-grabbing touch-none p-1 -ml-1 rounded text-muted-foreground hover:text-foreground" title="Sleep om volgorde te wijzigen" aria-label="Volgorde wijzigen"><svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" /></svg></span>
            <h3 class="kt-card-title">{{ $sectionLabel('email_template') }}{{ $sectionKey !== 'email_template' ? ' – ' . $sectionKey : '' }}</h3>
            <div class="flex items-center gap-1 shrink-0">
                <input type="hidden" name="home_sections[visibility][{{ $sectionKey }}]" id="visibility-{{ $sectionKey }}" value="{{ $vis('') ? '1' : '0' }}">
                <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground" data-target="visibility-{{ $sectionKey }}" title="{{ $vis('') ? 'Verbergen op website' : 'Tonen op website' }}" aria-label="Zichtbaarheid">@if($vis(''))<svg class="w-5 h-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-5 h-5 text-current opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                <button type="button" class="home-section-collapse-toggle kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground" title="Inklappen" aria-label="Sectie inklappen of uitklappen"><svg class="w-5 h-5 text-current home-section-collapse-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg></button>
                <button type="button" class="home-section-remove kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-destructive" title="Sectie verwijderen" aria-label="Sectie verwijderen"><svg class="w-5 h-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
            </div>
        </div>
        <div class="home-section-card-body kt-card-table p-4 space-y-4">
            <p class="text-sm text-muted-foreground">Toon een formulier op de website waarmee bezoekers een e-mail kunnen versturen op basis van een gekozen template (bijv. informatieaanvraag). Kies hier het e-mailtemplate dat gebruikt wordt.</p>
            <div class="grid gap-x-4" style="grid-template-columns: 10rem 1fr; row-gap: 1rem;">
                <label class="text-sm font-medium text-secondary-foreground flex items-center">Sectietitel</label>
                <input type="text" name="home_sections[{{ $sectionKey }}][title]" class="kt-input w-full max-w-xl" value="{{ old('home_sections.'.$sectionKey.'.title', $sectionData['title'] ?? 'Informatie aanvragen') }}" placeholder="Informatie aanvragen">
                <label class="text-sm font-medium text-secondary-foreground flex items-center">E-mailtemplate</label>
                @php
                    $selectedTemplateId = (int) old('home_sections.'.$sectionKey.'.template_id', $emailTemplateSelectedIds[$sectionKey] ?? $sectionData['template_id'] ?? 0);
                @endphp
                <input type="hidden" name="_email_template_tid_{{ $sectionKey }}" id="email-template-tid-{{ $sectionKey }}" value="{{ $selectedTemplateId ?: '' }}" data-email-template-fallback>
                <select name="home_sections[{{ $sectionKey }}][template_id]" id="home_sections_{{ $sectionKey }}_template_id" class="kt-input w-full max-w-xl" data-email-template-select data-fallback-input-id="email-template-tid-{{ $sectionKey }}" data-selected-template-id="{{ $selectedTemplateId ?: '' }}">
                    <option value="">— Geen template gekozen —</option>
                    @foreach($emailTemplatesForSelect as $et)
                        <option value="{{ $et->id }}" {{ $selectedTemplateId === (int) $et->id ? 'selected' : '' }}>{{ $et->name }} ({{ $et->type }})</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    @elseif($base === 'text_block')
    <div class="kt-card home-section-card @if($isCardCollapsed) home-section-card--collapsed @endif" data-section="{{ $sectionKey }}">
        <div class="kt-card-header home-section-header home-section-header--text-block flex items-center justify-between gap-2">
            <span class="home-section-drag-handle cursor-grab active:cursor-grabbing touch-none p-1 -ml-1 rounded text-muted-foreground hover:text-foreground" title="Sleep om volgorde te wijzigen" aria-label="Volgorde wijzigen"><svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" /></svg></span>
            <h3 class="kt-card-title">{{ $sectionLabel('text_block') }}{{ $sectionKey !== 'text_block' ? ' – ' . $sectionKey : '' }}</h3>
            <div class="flex items-center gap-1 shrink-0">
                <input type="hidden" name="home_sections[visibility][{{ $sectionKey }}]" id="visibility-{{ $sectionKey }}" value="{{ $vis('') ? '1' : '0' }}">
                <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground" data-target="visibility-{{ $sectionKey }}" title="{{ $vis('') ? 'Verbergen op website' : 'Tonen op website' }}" aria-label="Zichtbaarheid">@if($vis(''))<svg class="w-5 h-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-5 h-5 text-current opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                <button type="button" class="home-section-collapse-toggle kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground" title="Inklappen" aria-label="Sectie inklappen of uitklappen"><svg class="w-5 h-5 text-current home-section-collapse-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg></button>
                <button type="button" class="home-section-remove kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-destructive" title="Sectie verwijderen" aria-label="Sectie verwijderen"><svg class="w-5 h-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
            </div>
        </div>
        <div class="home-section-card-body kt-card-table p-4 space-y-4">
            <p class="text-sm text-muted-foreground">Rich-tekstblok met optioneel een component (bijv. formulier) ernaast. Kies de uitlijning van de tekst en eventueel welk component rechts of links van de tekst getoond moet worden.</p>
            <div class="row-visibility-row">
                <label class="block text-sm font-medium text-secondary-foreground mb-2">Tekst (rich text)</label>
                @include('admin.website-pages.partials.flowbite-wysiwyg', ['editorId' => 'text-block-' . $sectionKey . '-content', 'name' => 'home_sections['.$sectionKey.'][content]', 'value' => old('home_sections.'.$sectionKey.'.content', $sectionData['content'] ?? ''), 'placeholder' => 'Voeg hier uw tekst toe...', 'textareaId' => 'text-block-'.$sectionKey.'-content-input'])
            </div>
            <div class="row-visibility-row grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-secondary-foreground mb-1">Tekstuitlijning op de website</label>
                    <select name="home_sections[{{ $sectionKey }}][alignment]" id="text-block-{{ $sectionKey }}-alignment" class="kt-input w-full max-w-xs">
                        @php $textBlockAlignment = old('home_sections.'.$sectionKey.'.alignment', $sectionData['alignment'] ?? 'left'); @endphp
                        <option value="left" {{ $textBlockAlignment === 'left' ? 'selected' : '' }}>Links</option>
                        <option value="center" {{ $textBlockAlignment === 'center' ? 'selected' : '' }}>Midden</option>
                        <option value="right" {{ $textBlockAlignment === 'right' ? 'selected' : '' }}>Rechts</option>
                        <option value="full" {{ $textBlockAlignment === 'full' ? 'selected' : '' }}>Volledige breedte</option>
                    </select>
                    <p class="text-xs text-muted-foreground mt-1">Bepaalt hoe de tekst wordt uitgelijnd en of er ruimte is voor een component ernaast.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary-foreground mb-1">Component naast de tekst</label>
                    <select name="home_sections[{{ $sectionKey }}][side_component_key]" id="text-block-{{ $sectionKey }}-side-component" class="kt-input w-full max-w-md">
                        <option value="">— Geen —</option>
                        @foreach($sideComponentOptionKeys as $sk)
                            @if($sk !== $sectionKey)
                                @php $skBase = $baseType($sk); $skLabel = $sectionLabel($skBase ?? $sk) . ($sk !== ($skBase ?? $sk) ? ' – ' . $sk : ''); @endphp
                                <option value="{{ $sk }}" {{ old('home_sections.'.$sectionKey.'.side_component_key', $sectionData['side_component_key'] ?? '') === $sk ? 'selected' : '' }}>{{ $skLabel }}</option>
                            @endif
                        @endforeach
                    </select>
                    <p class="text-xs text-muted-foreground mt-1">Toon een informatieaanvraag-formulier naast de tekst. Alleen bij uitlijning Links of Rechts.</p>
                </div>
                <div id="text-block-{{ $sectionKey }}-side-template-row" class="{{ old('home_sections.'.$sectionKey.'.side_component_key', $sectionData['side_component_key'] ?? '') !== '' ? '' : 'hidden' }}">
                    <label class="block text-sm font-medium text-secondary-foreground mb-1">E-mailtemplate voor het formulier</label>
                    @php
                        $textBlockSideTemplateId = (int) old('home_sections.'.$sectionKey.'.side_template_id', $sectionData['side_template_id'] ?? 0);
                        $textBlockSideKey = old('home_sections.'.$sectionKey.'.side_component_key', $sectionData['side_component_key'] ?? '');
                        if ($textBlockSideTemplateId <= 0 && $textBlockSideKey !== '' && preg_replace('/_\d+$/', '', $textBlockSideKey) === 'email_template') {
                            $linkedSection = $sections[$textBlockSideKey] ?? [];
                            $textBlockSideTemplateId = (int) ($linkedSection['template_id'] ?? 0);
                        }
                    @endphp
                    <select name="home_sections[{{ $sectionKey }}][side_template_id]" id="text-block-{{ $sectionKey }}-side-template-id" class="kt-input w-full max-w-xl">
                        <option value="">— Kies een e-mailtemplate —</option>
                        @foreach($emailTemplatesForSelect as $et)
                            <option value="{{ $et->id }}" {{ $textBlockSideTemplateId === (int) $et->id ? 'selected' : '' }}>{{ $et->name }} ({{ $et->type }})</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-muted-foreground mt-1">Verplicht wanneer u een formulier naast de tekst toont. U hoeft geen aparte e-mailtemplate-sectie op de pagina te plaatsen.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary-foreground mb-1">Sectiebreedte op de website</label>
                    <select name="home_sections[{{ $sectionKey }}][width_percent]" id="text-block-{{ $sectionKey }}-width-percent" class="kt-input w-full max-w-xs">
                        @php $textBlockWidth = (int) old('home_sections.'.$sectionKey.'.width_percent', $sectionData['width_percent'] ?? 100); $textBlockWidth = max(30, min(100, $textBlockWidth)); @endphp
                        <option value="100" {{ $textBlockWidth === 100 ? 'selected' : '' }}>100%</option>
                        <option value="90" {{ $textBlockWidth === 90 ? 'selected' : '' }}>90%</option>
                        <option value="80" {{ $textBlockWidth === 80 ? 'selected' : '' }}>80%</option>
                        <option value="70" {{ $textBlockWidth === 70 ? 'selected' : '' }}>70%</option>
                        <option value="60" {{ $textBlockWidth === 60 ? 'selected' : '' }}>60%</option>
                        <option value="50" {{ $textBlockWidth === 50 ? 'selected' : '' }}>50%</option>
                        <option value="40" {{ $textBlockWidth === 40 ? 'selected' : '' }}>40%</option>
                        <option value="30" {{ $textBlockWidth === 30 ? 'selected' : '' }}>30%</option>
                    </select>
                    <p class="text-xs text-muted-foreground mt-1">Breedte van de sectie ten opzichte van de pagina (in procenten).</p>
                </div>
            </div>
            <div class="row-visibility-row">
                <label class="block text-sm font-medium text-secondary-foreground mb-1">Afbeelding naast de tekst</label>
                <p class="text-xs text-muted-foreground mb-2">Optioneel: toon een afbeelding links of rechts van de tekst (zelfde zijde als het component). Alleen bij uitlijning Links of Rechts.</p>
                @php $textBlockImageUrl = old('home_sections.'.$sectionKey.'.image_url', $sectionData['image_url'] ?? ''); @endphp
                <div class="flex flex-wrap items-stretch gap-3">
                    <div class="shrink-0 flex flex-col items-center">
                        <img alt="Tekstblok afbeelding" id="hero-{{ $sectionKey }}-image_url-preview" class="w-full max-w-[200px] max-h-40 object-contain border border-border rounded-lg {{ $textBlockImageUrl ? '' : 'hidden' }}" src="{{ $textBlockImageUrl ? $imagePreviewUrl($textBlockImageUrl) : '' }}">
                        <button type="button" class="image-remove-btn kt-btn kt-btn-xs kt-btn-ghost text-destructive mt-1 shadow hover:bg-destructive/10" data-url-input-id="hero-{{ $sectionKey }}-image_url" data-preview-id="hero-{{ $sectionKey }}-image_url-preview" title="Afbeelding verwijderen" aria-label="Afbeelding verwijderen"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                    </div>
                    <div class="hero-image-upload-area flex flex-col items-center justify-center p-5 lg:p-7 border border-input rounded-xl border-dashed bg-muted/30" style="width: 500px; min-width: 500px; height: 130px;" data-section-key="{{ $sectionKey }}" data-field="image_url" data-url-input-id="hero-{{ $sectionKey }}-image_url" data-preview-id="hero-{{ $sectionKey }}-image_url-preview">
                        <span class="text-xs text-muted-foreground text-center">Klik of sleep afbeelding</span>
                        <span class="text-xs text-muted-foreground">JPG, PNG, WebP (max. 5MB)</span>
                    </div>
                </div>
                <input type="file" class="hero-image-file-input hidden" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" data-section-key="{{ $sectionKey }}" data-field="image_url">
                <input type="hidden" name="home_sections[{{ $sectionKey }}][image_url]" id="hero-{{ $sectionKey }}-image_url" value="{{ $textBlockImageUrl }}">
            </div>
        </div>
    </div>
    @elseif($base === 'cta')
    <div class="kt-card home-section-card @if($isCardCollapsed) home-section-card--collapsed @endif" data-section="{{ $sectionKey }}">
        <div class="kt-card-header home-section-header home-section-header--cta flex items-center justify-between gap-2">
            <span class="home-section-drag-handle cursor-grab active:cursor-grabbing touch-none p-1 -ml-1 rounded text-muted-foreground hover:text-foreground" title="Sleep om volgorde te wijzigen" aria-label="Volgorde wijzigen"><svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" /></svg></span>
            <h3 class="kt-card-title">{{ $sectionLabel('cta') }}{{ $sectionKey !== 'cta' ? ' – ' . $sectionKey : '' }}</h3>
            <div class="flex items-center gap-1 shrink-0">
                <input type="hidden" name="home_sections[visibility][{{ $sectionKey }}]" id="visibility-{{ $sectionKey }}" value="{{ $vis('') ? '1' : '0' }}">
                <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground" data-target="visibility-{{ $sectionKey }}" title="{{ $vis('') ? 'Verbergen op website' : 'Tonen op website' }}" aria-label="Zichtbaarheid">
                    @if($vis(''))
                    <svg class="w-5 h-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    @else
                    <svg class="w-5 h-5 text-current opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                    @endif
                </button>
                <button type="button" class="home-section-collapse-toggle kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground" title="Inklappen" aria-label="Sectie inklappen of uitklappen">
                    <svg class="w-5 h-5 text-current home-section-collapse-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
                </button>
                <button type="button" class="home-section-remove kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-destructive" title="Sectie verwijderen" aria-label="Sectie verwijderen">
                    <svg class="w-5 h-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                </button>
            </div>
        </div>
        <div class="home-section-card-body kt-card-table p-4 space-y-3">
            {{-- Achtergrondafbeelding CTA (Atom-v2 thema) --}}
            <div class="row-visibility-row">
                <label class="block text-sm font-medium text-secondary-foreground mb-1">Achtergrondafbeelding</label>
                <p class="text-xs text-muted-foreground mb-2">Afbeelding achter de CTA-sectie. (Atom-v2 thema)</p>
                <div class="flex flex-wrap items-start gap-2">
                    <div class="shrink-0 flex flex-col items-center {{ !empty($sectionData['background_image_url']) ? '' : 'hidden' }}" id="cta-{{ $sectionKey }}-bg-preview-wrapper">
                        <img alt="CTA achtergrond" id="cta-{{ $sectionKey }}-bg-preview" class="w-full max-w-[200px] max-h-24 object-cover border border-border rounded" src="{{ $imagePreviewUrl($sectionData['background_image_url'] ?? '') }}">
                        <button type="button" class="image-remove-btn kt-btn kt-btn-xs kt-btn-ghost text-destructive mt-1 shadow hover:bg-destructive/10" data-url-input-id="cta-{{ $sectionKey }}-background_image_url" data-preview-id="cta-{{ $sectionKey }}-bg-preview" data-preview-wrapper-id="cta-{{ $sectionKey }}-bg-preview-wrapper" title="Afbeelding verwijderen" aria-label="Afbeelding verwijderen"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                    </div>
                    <div class="cta-image-upload-area flex flex-col items-center justify-center p-5 lg:p-7 border border-input rounded-xl border-dashed bg-muted/30" data-section-key="{{ $sectionKey }}" style="width: 500px; min-width: 500px; height: 130px;">
                        <span class="text-xs text-muted-foreground text-center">Klik of sleep afbeelding</span>
                        <span class="text-xs text-muted-foreground">JPG, PNG, WebP (max. 5MB)</span>
                    </div>
                </div>
                <input type="file" class="cta-image-file-input hidden" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" data-section-key="{{ $sectionKey }}">
                <input type="hidden" name="home_sections[{{ $sectionKey }}][background_image_url]" id="cta-{{ $sectionKey }}-background_image_url" value="{{ old('home_sections.'.$sectionKey.'.background_image_url', $sectionData['background_image_url'] ?? '') }}">
            </div>
            <div class="row-visibility-row">
                <div class="w-full min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <label class="text-sm font-medium text-secondary-foreground">Titel</label>
                <input type="hidden" name="home_sections[visibility][{{ $sectionKey }}_title]" id="visibility-{{ $sectionKey }}_title" value="{{ $vis('_title') ? '1' : '0' }}">
                <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" data-target="visibility-{{ $sectionKey }}_title" aria-label="Titel tonen/verbergen">@if($vis('_title'))<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                    </div>
                    <input type="text" name="home_sections[{{ $sectionKey }}][title]" class="kt-input w-full max-w-4xl" value="{{ old('home_sections.'.$sectionKey.'.title', $sectionData['title'] ?? 'Klaar om je carrière te starten?') }}">
                </div>
            </div>
            <div class="row-visibility-row">
                <div class="flex items-center gap-2 mb-1">
                    <label class="text-sm font-medium text-secondary-foreground">Ondertitel</label>
                <input type="hidden" name="home_sections[visibility][{{ $sectionKey }}_subtitle]" id="visibility-{{ $sectionKey }}_subtitle" value="{{ $vis('_subtitle') ? '1' : '0' }}">
                <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" data-target="visibility-{{ $sectionKey }}_subtitle" aria-label="Ondertitel tonen/verbergen">@if($vis('_subtitle'))<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                </div>
                                @include('admin.website-pages.partials.subtitle-color-fields', ['sectionKey' => $sectionKey, 'sectionData' => $sectionData, 'subtitleColorDefault' => '#e5e7eb'])
                @include('admin.website-pages.partials.flowbite-wysiwyg', ['editorId' => 'cta-' . $sectionKey . '-subtitle', 'name' => 'home_sections['.$sectionKey.'][subtitle]', 'value' => old('home_sections.'.$sectionKey.'.subtitle', $sectionData['subtitle'] ?? ''), 'placeholder' => '', 'textareaId' => 'home-'.$sectionKey.'-subtitle'])
            </div>
            <div class="row-visibility-row">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-sm font-medium text-secondary-foreground">Knoppen</span>
                <input type="hidden" name="home_sections[visibility][{{ $sectionKey }}_buttons]" id="visibility-{{ $sectionKey }}_buttons" value="{{ $vis('_buttons') ? '1' : '0' }}">
                <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" data-target="visibility-{{ $sectionKey }}_buttons" aria-label="Knoppen tonen/verbergen">@if($vis('_buttons'))<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-secondary-foreground mb-1">Knop 1 tekst</label>
                        <input type="text" name="home_sections[{{ $sectionKey }}][cta_primary_text]" class="kt-input home-section-input-400" value="{{ old('home_sections.'.$sectionKey.'.cta_primary_text', $sectionData['cta_primary_text'] ?? '') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-secondary-foreground mb-1">Knop 1 URL</label>
                        <input type="text" name="home_sections[{{ $sectionKey }}][cta_primary_url]" class="kt-input home-section-input-400" value="{{ old('home_sections.'.$sectionKey.'.cta_primary_url', $sectionData['cta_primary_url'] ?? '/register') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-secondary-foreground mb-1">Knop 2 tekst</label>
                        <input type="text" name="home_sections[{{ $sectionKey }}][cta_secondary_text]" class="kt-input home-section-input-400" value="{{ old('home_sections.'.$sectionKey.'.cta_secondary_text', $sectionData['cta_secondary_text'] ?? '') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-secondary-foreground mb-1">Knop 2 URL</label>
                        <input type="text" name="home_sections[{{ $sectionKey }}][cta_secondary_url]" class="kt-input home-section-input-400" value="{{ old('home_sections.'.$sectionKey.'.cta_secondary_url', $sectionData['cta_secondary_url'] ?? '/jobs') }}">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3 pt-3 border-t border-border">
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-secondary-foreground">Knop 1 kleuren</label>
                        <div class="flex flex-col gap-2">
                            <div class="flex items-center gap-3">
                                <label class="block text-xs font-medium text-muted-foreground w-28 shrink-0">Achtergrond</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" id="{{ $sectionKey }}-cta-primary-bg" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($sectionData['cta_primary_bg'] ?? '') ?: '#2563eb' }}" title="Achtergrond">
                                    <input type="text" name="home_sections[{{ $sectionKey }}][cta_primary_bg]" class="kt-input font-mono text-sm home-section-hex-input" value="{{ old('home_sections.'.$sectionKey.'.cta_primary_bg', $sectionData['cta_primary_bg'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="{{ $sectionKey }}-cta-primary-bg">
                                    <button type="button" class="hex-clear-btn kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Leegmaken" aria-label="Leegmaken" data-color-default="#2563eb"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="block text-xs font-medium text-muted-foreground w-28 shrink-0">Tekstkleur</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" id="{{ $sectionKey }}-cta-primary-text-color" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($sectionData['cta_primary_text_color'] ?? '') ?: '#ffffff' }}" title="Tekstkleur">
                                    <input type="text" name="home_sections[{{ $sectionKey }}][cta_primary_text_color]" class="kt-input font-mono text-sm home-section-hex-input" value="{{ old('home_sections.'.$sectionKey.'.cta_primary_text_color', $sectionData['cta_primary_text_color'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="{{ $sectionKey }}-cta-primary-text-color">
                                    <button type="button" class="hex-clear-btn kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Leegmaken" aria-label="Leegmaken" data-color-default="#ffffff"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="block text-xs font-medium text-muted-foreground w-28 shrink-0">Border</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" id="{{ $sectionKey }}-cta-primary-border" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($sectionData['cta_primary_border'] ?? '') ?: '#ffffff' }}" title="Borderkleur">
                                    <input type="text" name="home_sections[{{ $sectionKey }}][cta_primary_border]" class="kt-input font-mono text-sm home-section-hex-input" value="{{ old('home_sections.'.$sectionKey.'.cta_primary_border', $sectionData['cta_primary_border'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="{{ $sectionKey }}-cta-primary-border">
                                    <button type="button" class="hex-clear-btn kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Leegmaken" aria-label="Leegmaken" data-color-default="#ffffff"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
                                </div>
                            </div>
