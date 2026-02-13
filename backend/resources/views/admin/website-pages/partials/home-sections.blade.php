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
    // Absolute URL voor preview-afbeeldingen (relatieve paden werken in admin niet altijd)
    $imagePreviewUrl = function($url) {
        if ($url === null || $url === '') return '';
        $url = trim((string) $url);
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) return $url;
        return url($url);
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
    $missingInOrder = $singleSectionForFetch ? [] : array_diff($defaultSectionOrder, $sectionOrder);
    if (!empty($missingInOrder)) {
        foreach (array_values($missingInOrder) as $key) {
            $pos = array_search($key, $defaultSectionOrder, true);
            if ($pos !== false) {
                array_splice($sectionOrder, $pos, 0, [$key]);
            }
        }
        $sectionOrder = array_values($sectionOrder);
    }
    // Normaliseer component-keys naar "component:id" (lowercase, geen dubbele prefix); verwijder duplicaten
    $sectionOrder = array_map(function ($k) {
        if (is_string($k) && str_starts_with(strtolower($k), 'component:')) {
            $rest = preg_replace('/^component:+/i', '', $k);
            return $rest !== '' ? 'component:' . $rest : $k;
        }
        return $k;
    }, $sectionOrder);
    $sectionOrder = array_values(array_unique($sectionOrder, SORT_REGULAR));
    $sectionOrder = array_values($sectionOrder);
    // Als alle content-secties hetzelfde type zijn (bijv. alleen hero), gebruik thema-default zodat de juiste bodies getoond worden (niet bij sectionCardOnly)
    if (empty($singleSectionForFetch)) {
        $contentKeysForCheck = array_filter($sectionOrder, function ($k) {
            return is_string($k) && !preg_match('/^component:/i', (string) $k);
        });
        $baseTypeForCheck = function ($key) {
            $bt = ['hero', 'stats', 'why_nexa', 'features', 'cta', 'carousel', 'cards_ronde_hoeken'];
            if (in_array($key, $bt, true)) return $key;
            $base = preg_replace('/_\d+$/', '', (string) $key);
            return in_array($base, $bt, true) ? $base : null;
        };
        $orderBaseTypes = array_filter(array_map($baseTypeForCheck, $contentKeysForCheck));
        $defaultContentCount = count(array_filter($defaultSectionOrder, function ($k) {
            return is_string($k) && !preg_match('/^component:/i', (string) $k);
        }));
        if (count($orderBaseTypes) > 0 && count(array_unique($orderBaseTypes)) === 1 && $defaultContentCount > 1) {
            $sectionOrder = $defaultSectionOrder;
            $sectionOrder = array_values($sectionOrder);
        }
    }
    $componentService = app(\App\Services\FrontendComponentService::class);
    $baseTypes = ['hero', 'stats', 'why_nexa', 'features', 'cta', 'carousel', 'cards_ronde_hoeken'];
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
            'features' => 'Wat Wij Bieden',
            'cta' => 'CTA',
            'carousel' => 'Carousel',
            'cards_ronde_hoeken' => 'Cards ronde hoeken',
            default => $base,
        };
    };
    // Alleen secties tonen die voor dit thema beschikbaar zijn (add-menu = bron van waarheid)
    $allowedBaseTypesForTheme = array_column(\App\Models\WebsitePage::getAvailableHomeSectionTypesForTheme($themeSlugForOrder), 'type');
    $sectionOrder = array_values(array_filter($sectionOrder, function($key) use ($allowedBaseTypesForTheme) {
        if (is_string($key) && str_starts_with($key, 'component:')) return true;
        $baseTypes = ['hero', 'stats', 'why_nexa', 'features', 'cta', 'carousel', 'cards_ronde_hoeken'];
        $base = in_array($key, $baseTypes, true) ? $key : preg_replace('/_\d+$/', '', (string)$key);
        if (!in_array($base, $baseTypes, true)) return false;
        return in_array($base, $allowedBaseTypesForTheme, true);
    }));
@endphp
{{-- Heroicons: eye (tonen) en eye-slash (verborgen op website) --}}
<input type="hidden" name="home_sections[section_order]" id="home-sections-order-input" value="{{ implode(',', $sectionOrder) }}">
<div id="home-sections-meta" class="hidden" data-section-card-url="{{ route('admin.website-pages.section-card-html') }}" data-theme-slug="{{ $themeSlugForOrder }}" data-section-labels="{{ json_encode($sectionTypeLabels) }}"></div>
<div id="home-sections-sortable" class="space-y-6">
    @foreach($sectionOrder as $sectionKey)
    @php
        $base = $baseType($sectionKey);
        $sectionData = $sections[$sectionKey] ?? [];
        $vis = function($suffix) use ($visibility, $sectionKey, $base) {
            return $visibility[$sectionKey . $suffix] ?? $visibility[$base . $suffix] ?? true;
        };
    @endphp
    @if($base === 'hero')
    <div class="kt-card home-section-card" data-section="{{ $sectionKey }}">
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
            <div class="row-visibility-row flex flex-wrap items-start gap-2">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <label class="text-sm font-medium text-secondary-foreground">Titel</label>
                        <input type="hidden" name="home_sections[visibility][{{ $sectionKey }}_title]" id="visibility-{{ $sectionKey }}_title" value="{{ $vis('_title') ? '1' : '0' }}">
                        <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" data-target="visibility-{{ $sectionKey }}_title" title="Zichtbaar op website" aria-label="Titel tonen/verbergen">@if($vis('_title'))<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                    </div>
                    <input type="text" name="home_sections[{{ $sectionKey }}][title]" class="kt-input w-full" value="{{ old('home_sections.'.$sectionKey.'.title', $sectionData['title'] ?? 'Vind je droombaan met AI') }}" placeholder="Vind je droombaan met AI">
                </div>
                <div class="w-full md:w-auto">
                    <label class="block text-sm font-medium text-secondary-foreground mb-1">Woord benadrukt (oranje)</label>
                    <input type="text" name="home_sections[{{ $sectionKey }}][title_highlight]" class="kt-input w-full" value="{{ old('home_sections.'.$sectionKey.'.title_highlight', $sectionData['title_highlight'] ?? 'droombaan') }}" placeholder="droombaan">
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
                <div class="flex flex-wrap items-start gap-2">
                    <div class="relative shrink-0">
                        <img alt="Hero afbeelding" id="hero-{{ $sectionKey }}-author-preview" class="w-full max-w-[200px] max-h-40 object-contain border border-border rounded-lg {{ $heroPreviewSrc ? '' : 'hidden' }}" src="{{ $imagePreviewUrl($heroPreviewSrc) }}" data-default-src="{{ $defaultHeroImg ?? '' }}">
                        <button type="button" class="image-remove-btn kt-btn kt-btn-xs kt-btn-ghost text-destructive absolute top-1 right-1 shadow hover:bg-destructive/10" data-url-input-id="hero-{{ $sectionKey }}-author_image_url" data-preview-id="hero-{{ $sectionKey }}-author-preview" title="Afbeelding verwijderen" aria-label="Afbeelding verwijderen"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                    </div>
                    <div class="hero-image-upload-area flex flex-col items-center justify-center p-5 lg:p-7 border border-input rounded-xl border-dashed bg-muted/30" data-section-key="{{ $sectionKey }}" data-field="author_image_url" style="width: 500px; min-width: 500px; height: 130px;">
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
            {{-- Modern thema: alleen achtergrond --}}
            <div class="row-visibility-row">
                <label class="block text-sm font-medium text-secondary-foreground mb-1">Achtergrond banner</label>
                <p class="text-xs text-muted-foreground mb-2">Afbeelding achter de hero. (Modern thema)</p>
                <div class="flex flex-wrap items-start gap-2">
                    <div class="relative shrink-0">
                        <img alt="Hero achtergrond" id="hero-{{ $sectionKey }}-bg-preview" class="w-full max-w-[200px] max-h-24 object-cover border border-border rounded {{ !empty($sectionData['background_image_url']) ? '' : 'hidden' }}" src="{{ $imagePreviewUrl($sectionData['background_image_url'] ?? '') }}">
                        <button type="button" class="image-remove-btn kt-btn kt-btn-xs kt-btn-ghost text-destructive absolute top-1 right-1 shadow hover:bg-destructive/10" data-url-input-id="hero-{{ $sectionKey }}-background_image_url" data-preview-id="hero-{{ $sectionKey }}-bg-preview" title="Afbeelding verwijderen" aria-label="Afbeelding verwijderen"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                    </div>
                    <div class="hero-image-upload-area flex flex-col items-center justify-center p-5 lg:p-7 border border-input rounded-xl border-dashed bg-muted/30" data-section-key="{{ $sectionKey }}" data-field="background_image_url" style="width: 500px; min-width: 500px; height: 130px;">
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
                <div>
                    <label class="block text-sm font-medium text-secondary-foreground mb-1">Achtergrond banner</label>
                    <p class="text-xs text-muted-foreground mb-2">Afbeelding achter de gradient. (Atom-v2 thema)</p>
                    <div class="flex flex-wrap items-start gap-2">
                        <div class="relative shrink-0">
                            <img alt="Hero achtergrond" id="hero-{{ $sectionKey }}-bg-preview" class="w-full max-w-[200px] max-h-24 object-cover border border-border rounded {{ !empty($sectionData['background_image_url']) ? '' : 'hidden' }}" src="{{ $imagePreviewUrl($sectionData['background_image_url'] ?? '') }}">
                            <button type="button" class="image-remove-btn kt-btn kt-btn-xs kt-btn-ghost text-destructive absolute top-1 right-1 shadow hover:bg-destructive/10" data-url-input-id="hero-{{ $sectionKey }}-background_image_url" data-preview-id="hero-{{ $sectionKey }}-bg-preview" title="Afbeelding verwijderen" aria-label="Afbeelding verwijderen"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                        </div>
                        <div class="hero-image-upload-area flex flex-col items-center justify-center p-5 lg:p-7 border border-input rounded-xl border-dashed bg-muted/30" data-section-key="{{ $sectionKey }}" data-field="background_image_url" style="width: 500px; min-width: 500px; height: 130px;">
                            <span class="text-xs text-muted-foreground text-center">Klik of sleep afbeelding</span>
                            <span class="text-xs text-muted-foreground">JPG, PNG, WebP (max. 5MB)</span>
                        </div>
                    </div>
                    <input type="file" class="hero-image-file-input hidden" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" data-section-key="{{ $sectionKey }}" data-field="background_image_url">
                    <input type="hidden" name="home_sections[{{ $sectionKey }}][background_image_url]" id="hero-{{ $sectionKey }}-background_image_url" value="{{ old('home_sections.'.$sectionKey.'.background_image_url', $sectionData['background_image_url'] ?? '') }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary-foreground mb-1">Foto in banner (ronde afbeelding)</label>
                    <p class="text-xs text-muted-foreground mb-2">Ronde foto naast de titel. (Atom-v2 thema)</p>
                    <div class="flex flex-wrap items-start gap-2">
                        <div class="relative shrink-0">
                            <img alt="Hero foto" id="hero-{{ $sectionKey }}-author-preview" class="w-20 h-20 rounded-full object-cover border border-border {{ !empty($sectionData['author_image_url']) ? '' : 'hidden' }}" src="{{ $imagePreviewUrl($sectionData['author_image_url'] ?? '') }}">
                            <button type="button" class="image-remove-btn kt-btn kt-btn-xs kt-btn-ghost text-destructive absolute -top-0.5 -right-0.5 rounded-full shadow hover:bg-destructive/10" data-url-input-id="hero-{{ $sectionKey }}-author_image_url" data-preview-id="hero-{{ $sectionKey }}-author-preview" title="Afbeelding verwijderen" aria-label="Afbeelding verwijderen"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                        </div>
                        <div class="hero-image-upload-area flex flex-col items-center justify-center p-5 lg:p-7 border border-input rounded-xl border-dashed bg-muted/30" data-section-key="{{ $sectionKey }}" data-field="author_image_url" style="width: 500px; min-width: 500px; height: 130px;">
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
            <div class="row-visibility-row">
                <div class="flex items-center gap-2 mb-1">
                    <label class="text-sm font-medium text-secondary-foreground">Ondertitel</label>
                    <input type="hidden" name="home_sections[visibility][{{ $sectionKey }}_subtitle]" id="visibility-{{ $sectionKey }}_subtitle" value="{{ $vis('_subtitle') ? '1' : '0' }}">
                    <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" data-target="visibility-{{ $sectionKey }}_subtitle" title="Zichtbaar op website" aria-label="Ondertitel tonen/verbergen">@if($vis('_subtitle'))<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                </div>
                <textarea name="home_sections[{{ $sectionKey }}][subtitle]" id="home-{{ $sectionKey }}-subtitle" class="kt-input w-full pt-1 home-section-tinymce" rows="10" placeholder="Ons geavanceerde AI-platform...">{{ old('home_sections.'.$sectionKey.'.subtitle', $sectionData['subtitle'] ?? '') }}</textarea>
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
                    <input type="text" name="home_sections[{{ $sectionKey }}][cta_primary_text]" class="kt-input w-full" value="{{ old('home_sections.'.$sectionKey.'.cta_primary_text', $sectionData['cta_primary_text'] ?? 'Gratis account aanmaken') }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary-foreground mb-1">Knop 1 URL</label>
                    <input type="text" name="home_sections[{{ $sectionKey }}][cta_primary_url]" class="kt-input w-full" value="{{ old('home_sections.'.$sectionKey.'.cta_primary_url', $sectionData['cta_primary_url'] ?? '/register') }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary-foreground mb-1">Knop 2 tekst</label>
                    <input type="text" name="home_sections[{{ $sectionKey }}][cta_secondary_text]" class="kt-input w-full" value="{{ old('home_sections.'.$sectionKey.'.cta_secondary_text', $sectionData['cta_secondary_text'] ?? 'Vacatures bekijken') }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary-foreground mb-1">Knop 2 URL</label>
                    <input type="text" name="home_sections[{{ $sectionKey }}][cta_secondary_url]" class="kt-input w-full" value="{{ old('home_sections.'.$sectionKey.'.cta_secondary_url', $sectionData['cta_secondary_url'] ?? '/jobs') }}">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3 pt-3 border-t border-border">
                <div class="space-y-3">
                    <label class="block text-sm font-medium text-secondary-foreground">Knop 1 kleuren</label>
                    <div class="flex flex-wrap items-center gap-3">
                        <div>
                            <label class="block text-xs font-medium text-muted-foreground mb-1">Achtergrond</label>
                            <div class="flex items-center gap-2">
                                <input type="color" id="{{ $sectionKey }}-cta-primary-bg" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($sectionData['cta_primary_bg'] ?? '') ?: '#ffffff' }}" title="Achtergrond">
                                <input type="text" name="home_sections[{{ $sectionKey }}][cta_primary_bg]" class="kt-input w-24 font-mono text-sm" value="{{ old('home_sections.'.$sectionKey.'.cta_primary_bg', $sectionData['cta_primary_bg'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="{{ $sectionKey }}-cta-primary-bg">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-muted-foreground mb-1">Tekstkleur</label>
                            <div class="flex items-center gap-2">
                                <input type="color" id="{{ $sectionKey }}-cta-primary-text-color" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($sectionData['cta_primary_text_color'] ?? '') ?: '#1e3a8a' }}" title="Tekstkleur">
                                <input type="text" name="home_sections[{{ $sectionKey }}][cta_primary_text_color]" class="kt-input w-24 font-mono text-sm" value="{{ old('home_sections.'.$sectionKey.'.cta_primary_text_color', $sectionData['cta_primary_text_color'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="{{ $sectionKey }}-cta-primary-text-color">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-muted-foreground mb-1">Border</label>
                            <div class="flex items-center gap-2">
                                <input type="color" id="{{ $sectionKey }}-cta-primary-border" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($sectionData['cta_primary_border'] ?? '') ?: '#1e40af' }}" title="Borderkleur">
                                <input type="text" name="home_sections[{{ $sectionKey }}][cta_primary_border]" class="kt-input w-24 font-mono text-sm" value="{{ old('home_sections.'.$sectionKey.'.cta_primary_border', $sectionData['cta_primary_border'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="{{ $sectionKey }}-cta-primary-border">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="space-y-3">
                    <label class="block text-sm font-medium text-secondary-foreground">Knop 2 kleuren</label>
                    <div class="flex flex-wrap items-center gap-3">
                        <div>
                            <label class="block text-xs font-medium text-muted-foreground mb-1">Achtergrond</label>
                            <div class="flex items-center gap-2">
                                <input type="color" id="{{ $sectionKey }}-cta-secondary-bg" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($sectionData['cta_secondary_bg'] ?? '') ?: '#ffffff' }}" title="Achtergrond">
                                <input type="text" name="home_sections[{{ $sectionKey }}][cta_secondary_bg]" class="kt-input w-24 font-mono text-sm" value="{{ old('home_sections.'.$sectionKey.'.cta_secondary_bg', $sectionData['cta_secondary_bg'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="{{ $sectionKey }}-cta-secondary-bg">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-muted-foreground mb-1">Tekstkleur</label>
                            <div class="flex items-center gap-2">
                                <input type="color" id="{{ $sectionKey }}-cta-secondary-text-color" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($sectionData['cta_secondary_text_color'] ?? '') ?: '#1e40af' }}" title="Tekstkleur">
                                <input type="text" name="home_sections[{{ $sectionKey }}][cta_secondary_text_color]" class="kt-input w-24 font-mono text-sm" value="{{ old('home_sections.'.$sectionKey.'.cta_secondary_text_color', $sectionData['cta_secondary_text_color'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="{{ $sectionKey }}-cta-secondary-text-color">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-muted-foreground mb-1">Border</label>
                            <div class="flex items-center gap-2">
                                <input type="color" id="{{ $sectionKey }}-cta-secondary-border" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($sectionData['cta_secondary_border'] ?? '') ?: '#1e40af' }}" title="Borderkleur">
                                <input type="text" name="home_sections[{{ $sectionKey }}][cta_secondary_border]" class="kt-input w-24 font-mono text-sm" value="{{ old('home_sections.'.$sectionKey.'.cta_secondary_border', $sectionData['cta_secondary_border'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="{{ $sectionKey }}-cta-secondary-border">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <p class="text-xs text-muted-foreground mt-2">Achtergrond, tekstkleur en border per knop. Laat leeg voor standaardkleuren. Gebruik hex (bijv. #2563eb).</p>
            </div>
        </div>
    </div>
    @elseif($base === 'stats')
    <div class="kt-card home-section-card" data-section="{{ $sectionKey }}">
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
            <div class="row-visibility-row flex flex-wrap items-center gap-2">
                <input type="hidden" name="home_sections[visibility][{{ $sectionKey }}_{{ $i }}]" id="visibility-{{ $sectionKey }}_{{ $i }}" value="{{ ($visibility[$sectionKey.'_'.$i] ?? $visibility['stats_'.$i] ?? true) ? '1' : '0' }}">
                <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" data-target="visibility-{{ $sectionKey }}_{{ $i }}" title="Stat {{ $i + 1 }} tonen/verbergen" aria-label="Stat {{ $i + 1 }}">@if($visibility[$sectionKey.'_'.$i] ?? $visibility['stats_'.$i] ?? true)<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                <div class="grid grid-cols-2 gap-3 flex-1 min-w-0">
                    <div>
                        <label class="block text-sm font-medium text-secondary-foreground mb-1">Waarde {{ $i + 1 }}</label>
                        <input type="text" name="home_sections[{{ $sectionKey }}][{{ $i }}][value]" class="kt-input w-full" value="{{ old("home_sections.{$sectionKey}.{$i}.value", ($sectionData[$i]['value'] ?? '')) }}" placeholder="10,000+">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-secondary-foreground mb-1">Label {{ $i + 1 }}</label>
                        <input type="text" name="home_sections[{{ $sectionKey }}][{{ $i }}][label]" class="kt-input w-full" value="{{ old("home_sections.{$sectionKey}.{$i}.label", ($sectionData[$i]['label'] ?? '')) }}" placeholder="Actieve vacatures">
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @elseif($base === 'why_nexa')
    <div class="kt-card home-section-card" data-section="{{ $sectionKey }}">
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
                <input type="text" name="home_sections[{{ $sectionKey }}][title]" class="kt-input w-full" value="{{ old('home_sections.'.$sectionKey.'.title', $sectionData['title'] ?? 'Waarom kiezen voor Nexa?') }}">
            </div>
            <div class="row-visibility-row">
                <div class="flex items-center gap-2 mb-1">
                    <label class="text-sm font-medium text-secondary-foreground">Ondertitel</label>
                    <input type="hidden" name="home_sections[visibility][{{ $sectionKey }}_subtitle]" id="visibility-{{ $sectionKey }}_subtitle" value="{{ $vis('_subtitle') ? '1' : '0' }}">
                    <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" data-target="visibility-{{ $sectionKey }}_subtitle" aria-label="Ondertitel tonen/verbergen">@if($vis('_subtitle'))<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                </div>
                <textarea name="home_sections[{{ $sectionKey }}][subtitle]" id="home-{{ $sectionKey }}-subtitle" class="kt-input w-full pt-1 home-section-tinymce" rows="10">{{ old('home_sections.'.$sectionKey.'.subtitle', $sectionData['subtitle'] ?? '') }}</textarea>
            </div>
        </div>
    </div>
    @elseif($base === 'features')
    @php
        $featureSectionData = $sectionData; $featureSectionKey = $sectionKey; $featureVis = $vis;
        $heroiconList = collect(config('heroicons.icons', []))->filter(fn($v) => is_array($v) && isset($v['label']) && isset($v['svg']))->all();
        $heroiconSizes = config('heroicons.sizes', ['small' => ['label' => 'Klein'], 'medium' => ['label' => 'Normaal'], 'large' => ['label' => 'Groot']]);
    @endphp
    <div class="kt-card home-section-card" data-section="{{ $sectionKey }}">
        <div class="kt-card-header home-section-header home-section-header--features flex items-center justify-between gap-2">
            <span class="home-section-drag-handle cursor-grab active:cursor-grabbing touch-none p-1 -ml-1 rounded text-muted-foreground hover:text-foreground" title="Sleep om volgorde te wijzigen" aria-label="Volgorde wijzigen"><svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" /></svg></span>
            <h3 class="kt-card-title">{{ $sectionLabel('features') }}{{ $sectionKey !== 'features' ? ' – ' . $sectionKey : '' }}</h3>
            <div class="flex items-center gap-1 shrink-0">
                <input type="hidden" name="home_sections[visibility][features]" id="visibility-features" value="{{ ($visibility['features'] ?? true) ? '1' : '0' }}">
                <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground" data-target="visibility-features" title="{{ ($visibility['features'] ?? true) ? 'Verbergen op website' : 'Tonen op website' }}" aria-label="Zichtbaarheid">
                    @if($visibility['features'] ?? true)
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
                $featuresPreviewSrc = !empty($features['illustration_url']) ? $features['illustration_url'] : $defaultFeaturesImg;
            @endphp
            <div class="row-visibility-row">
                <label class="block text-sm font-medium text-secondary-foreground mb-1">Illustratie Kenmerken-sectie</label>
                <p class="text-xs text-muted-foreground mb-2">Afbeelding naast de kenmerken. Standaard: Illustration2.png.</p>
                <div class="flex flex-wrap items-start gap-2">
                    <div class="relative shrink-0">
                        <img alt="Kenmerken illustratie" id="hero-features-author-preview" class="w-full max-w-[200px] max-h-40 object-contain border border-border rounded-lg {{ $featuresPreviewSrc ? '' : 'hidden' }}" src="{{ $imagePreviewUrl($featuresPreviewSrc) }}" data-default-src="{{ $defaultFeaturesImg ?? '' }}">
                        <button type="button" class="image-remove-btn kt-btn kt-btn-xs kt-btn-ghost text-destructive absolute top-1 right-1 shadow hover:bg-destructive/10" data-url-input-id="hero-features-illustration_url" data-preview-id="hero-features-author-preview" title="Afbeelding verwijderen" aria-label="Afbeelding verwijderen"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                    </div>
                    <div class="hero-image-upload-area flex flex-col items-center justify-center p-5 lg:p-7 border border-input rounded-xl border-dashed bg-muted/30" data-section-key="features" data-field="illustration_url" style="width: 500px; min-width: 500px; height: 130px;">
                        <span class="text-xs text-muted-foreground text-center">Klik of sleep afbeelding</span>
                        <span class="text-xs text-muted-foreground">JPG, PNG, WebP (max. 5MB)</span>
                    </div>
                </div>
                <input type="file" class="hero-image-file-input hidden" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" data-section-key="features" data-field="illustration_url">
                <input type="hidden" name="home_sections[features][illustration_url]" id="hero-features-illustration_url" value="{{ old('home_sections.features.illustration_url', $features['illustration_url'] ?? '') }}">
            </div>
            @endif
            <div class="row-visibility-row">
                <div class="flex items-center gap-2 mb-1">
                    <label class="text-sm font-medium text-secondary-foreground">Sectietitel</label>
                    <input type="hidden" name="home_sections[visibility][features_section_title]" id="visibility-features_section_title" value="{{ ($visibility['features_section_title'] ?? true) ? '1' : '0' }}">
                    <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" data-target="visibility-features_section_title" aria-label="Sectietitel tonen/verbergen">@if($visibility['features_section_title'] ?? true)<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                </div>
                <input type="text" name="home_sections[features][section_title]" class="kt-input w-full" value="{{ old('home_sections.features.section_title', $features['section_title'] ?? 'Wat Wij Bieden') }}">
            </div>
            <div id="features-items-sortable" class="space-y-4" data-icon-options="{{ json_encode(collect($heroiconList)->map(fn($v) => $v['label'] ?? '')->all()) }}" data-size-options="{{ json_encode(collect($heroiconSizes)->map(fn($v) => $v['label'] ?? '')->all()) }}">
            @foreach($featureItems as $i => $item)
            @php $itemKey = 'features_item_'.$i; @endphp
            <div class="features-item-row row-visibility-row border border-border rounded-lg p-4 space-y-3 flex gap-3" data-features-index="{{ $i }}">
                <span class="features-item-drag-handle cursor-grab active:cursor-grabbing touch-none shrink-0 mt-1 p-1 rounded text-muted-foreground hover:text-foreground" title="Sleep om volgorde te wijzigen" aria-label="Volgorde wijzigen"><svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" /></svg></span>
                <div class="flex-1 min-w-0 space-y-3">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        <p class="text-sm font-medium text-secondary-foreground">Kaart <span class="features-item-num">{{ $i + 1 }}</span></p>
                        <input type="hidden" name="home_sections[visibility][features_item_{{ $i }}]" id="visibility-features_item_{{ $i }}" value="{{ ($visibility['features_item_'.$i] ?? true) ? '1' : '0' }}">
                        <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" data-target="visibility-features_item_{{ $i }}" aria-label="Kaart tonen/verbergen">@if($visibility['features_item_'.$i] ?? true)<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                        <button type="button" class="features-item-remove kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Kaart verwijderen" aria-label="Verwijderen"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                    </div>
                    <div>
                        <label class="block text-xs text-muted-foreground mb-1">Titel</label>
                        <input type="text" name="home_sections[features][items][{{ $i }}][title]" class="kt-input w-full features-item-title" value="{{ old("home_sections.features.items.{$i}.title", $item['title'] ?? '') }}">
                    </div>
                    <div>
                        <label class="block text-xs text-muted-foreground mb-1">Beschrijving</label>
                        <textarea name="home_sections[features][items][{{ $i }}][description]" id="home-features-item-{{ $i }}-description" class="kt-input w-full pt-1 home-section-tinymce" rows="6">{{ old("home_sections.features.items.{$i}.description", $item['description'] ?? '') }}</textarea>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-muted-foreground mb-1">Icoon (Heroicon)</label>
                            <select name="home_sections[features][items][{{ $i }}][icon]" class="kt-input w-full features-item-icon">
                                @foreach($heroiconList as $iconId => $iconData)
                                <option value="{{ $iconId }}" {{ ($item['icon'] ?? ($i === 0 ? 'light-bulb' : 'bolt')) === $iconId ? 'selected' : '' }}>{{ $iconData['label'] ?? $iconId }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-muted-foreground mb-1">Grootte icoon</label>
                            <select name="home_sections[features][items][{{ $i }}][icon_size]" class="kt-input w-full features-item-icon-size">
                                @foreach($heroiconSizes as $sizeId => $sizeData)
                                <option value="{{ $sizeId }}" {{ ($item['icon_size'] ?? 'medium') === $sizeId ? 'selected' : '' }}>{{ $sizeData['label'] ?? $sizeId }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
            </div>
            <div class="mt-4">
                <button type="button" id="features-item-add" class="kt-btn kt-btn-sm kt-btn-outline"><svg class="w-4 h-4 me-1 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>Kaart toevoegen</button>
            </div>
        </div>
    </div>
    @elseif($base === 'cards_ronde_hoeken')
    @php
        $cardsItems = array_values($sectionData['items'] ?? [['image_url' => '', 'text' => '', 'font_size' => 14, 'font_style' => 'normal', 'card_size' => 'normal', 'text_align' => 'left']]);
        if (empty($cardsItems)) {
            $cardsItems = [['image_url' => '', 'text' => '', 'font_size' => 14, 'font_style' => 'normal', 'card_size' => 'normal', 'text_align' => 'left']];
        }
        $cardsFontSizes = array_combine($a = range(10, 24, 2), $a);
        $cardsFontStyles = ['normal' => 'Normaal', 'bold' => 'Vet', 'italic' => 'Cursief'];
        $cardsCardSizes = ['small' => 'Klein (300px)', 'normal' => 'Normaal (400px)', 'large' => 'Groot (500px)', 'max' => 'Maximaal (volledige breedte)'];
        $cardsTextAligns = ['left' => 'Links', 'center' => 'Midden', 'right' => 'Rechts'];
    @endphp
    <div class="kt-card home-section-card" data-section="{{ $sectionKey }}">
        <div class="kt-card-header home-section-header flex items-center justify-between gap-2">
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
            <p class="text-sm text-muted-foreground">Kaarten met afbeelding en tekst eronder. Maximaal 4 per regel; bij 6 kaarten: 3+3. Tekst per kaart kan met het oogje uitgeschakeld worden.</p>
            <div id="cards-ronde-hoeken-items-{{ $sectionKey }}" class="space-y-4" data-section-key="{{ $sectionKey }}">
                @foreach($cardsItems as $i => $cardItem)
                <div class="cards-ronde-hoeken-item border border-border rounded-lg p-4 space-y-3" data-cards-index="{{ $i }}">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-sm font-medium">Kaart {{ $i + 1 }}</span>
                        <button type="button" class="cards-ronde-hoeken-item-remove kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive" title="Kaart verwijderen" aria-label="Verwijderen"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                    </div>
                    <div class="flex flex-wrap items-start gap-2">
                        <div class="relative shrink-0">
                            <img alt="Kaart {{ $i + 1 }}" id="hero-{{ $sectionKey }}-items_{{ $i }}_image_url-preview" class="w-full max-w-[200px] max-h-24 object-cover border border-border rounded {{ !empty($cardItem['image_url']) ? '' : 'hidden' }}" src="{{ $imagePreviewUrl($cardItem['image_url'] ?? '') }}">
                            <button type="button" class="image-remove-btn kt-btn kt-btn-xs kt-btn-ghost text-destructive absolute top-1 right-1 shadow hover:bg-destructive/10" data-url-input-id="hero-{{ $sectionKey }}-items_{{ $i }}_image_url" data-preview-id="hero-{{ $sectionKey }}-items_{{ $i }}_image_url-preview" title="Afbeelding verwijderen" aria-label="Afbeelding verwijderen"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                        </div>
                        <div class="hero-image-upload-area flex flex-col items-center justify-center p-5 lg:p-7 border border-input rounded-xl border-dashed bg-muted/30" data-section-key="{{ $sectionKey }}" data-field="items_{{ $i }}_image_url" style="width: 500px; min-width: 500px; height: 130px;">
                            <span class="text-xs text-muted-foreground">Klik of sleep afbeelding</span>
                            <span class="text-xs text-muted-foreground">JPG, PNG, WebP (max. 5MB)</span>
                        </div>
                    </div>
                    <input type="file" class="hero-image-file-input hidden" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" data-section-key="{{ $sectionKey }}" data-field="items_{{ $i }}_image_url">
                    <input type="hidden" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][image_url]" id="hero-{{ $sectionKey }}-items_{{ $i }}_image_url" value="{{ old('home_sections.'.$sectionKey.'.items.'.$i.'.image_url', $cardItem['image_url'] ?? '') }}">
                    <div class="row-visibility-row mt-2">
                        <div class="flex flex-wrap items-center gap-4 mb-1 pt-5">
                            <label class="text-sm font-medium text-secondary-foreground shrink-0">Tekst onder afbeelding</label>
                            <input type="hidden" name="home_sections[visibility][{{ $sectionKey }}_item_{{ $i }}]" id="visibility-{{ $sectionKey }}_item_{{ $i }}" value="{{ ($visibility[$sectionKey.'_item_'.$i] ?? true) ? '1' : '0' }}">
                            <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" data-target="visibility-{{ $sectionKey }}_item_{{ $i }}" aria-label="Tekst tonen/verbergen">@if($visibility[$sectionKey.'_item_'.$i] ?? true)<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 mb-2">
                            <label class="text-sm text-muted-foreground shrink-0">Kaartgrootte</label>
                            <select name="home_sections[{{ $sectionKey }}][items][{{ $i }}][card_size]" class="kt-input w-36 text-sm">
                                @foreach($cardsCardSizes as $val => $label)
                                <option value="{{ $val }}" {{ ($cardItem['card_size'] ?? 'normal') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <label class="text-sm text-muted-foreground shrink-0">Lettergrootte</label>
                            @php $cardFontSize = isset($cardItem['font_size']) ? max(10, min(24, (int) $cardItem['font_size'])) : 14; $cardFontSize = (int) (round($cardFontSize / 2) * 2); @endphp
                            <select name="home_sections[{{ $sectionKey }}][items][{{ $i }}][font_size]" class="kt-input w-20 text-sm">
                                @foreach($cardsFontSizes as $px => $label)
                                <option value="{{ $px }}" {{ $cardFontSize === (int)$px ? 'selected' : '' }}>{{ $label }}px</option>
                                @endforeach
                            </select>
                            <label class="text-sm text-muted-foreground shrink-0">Stijl</label>
                            <select name="home_sections[{{ $sectionKey }}][items][{{ $i }}][font_style]" class="kt-input w-28 text-sm">
                                @foreach($cardsFontStyles as $val => $label)
                                <option value="{{ $val }}" {{ ($cardItem['font_style'] ?? 'normal') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <label class="text-sm text-muted-foreground shrink-0">Uitlijning</label>
                            <select name="home_sections[{{ $sectionKey }}][items][{{ $i }}][text_align]" class="kt-input w-28 text-sm">
                                @foreach($cardsTextAligns as $val => $label)
                                <option value="{{ $val }}" {{ ($cardItem['text_align'] ?? 'left') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <textarea name="home_sections[{{ $sectionKey }}][items][{{ $i }}][text]" id="home-cards-{{ $sectionKey }}-item-{{ $i }}-text" class="kt-input w-full pt-1 home-section-tinymce" rows="6" placeholder="Tekst onder de afbeelding (rich text)">{{ old('home_sections.'.$sectionKey.'.items.'.$i.'.text', $cardItem['text'] ?? '') }}</textarea>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mt-4">
                <button type="button" class="cards-ronde-hoeken-item-add kt-btn kt-btn-sm kt-btn-outline" data-section-key="{{ $sectionKey }}"><svg class="w-4 h-4 me-1 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>Card toevoegen</button>
            </div>
        </div>
    </div>
    @elseif($base === 'cta')
    <div class="kt-card home-section-card" data-section="{{ $sectionKey }}">
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
                    <div class="relative shrink-0">
                        <img alt="CTA achtergrond" id="cta-{{ $sectionKey }}-bg-preview" class="w-full max-w-[200px] max-h-24 object-cover border border-border rounded {{ !empty($sectionData['background_image_url']) ? '' : 'hidden' }}" src="{{ $imagePreviewUrl($sectionData['background_image_url'] ?? '') }}">
                        <button type="button" class="image-remove-btn kt-btn kt-btn-xs kt-btn-ghost text-destructive absolute top-1 right-1 shadow hover:bg-destructive/10" data-url-input-id="cta-{{ $sectionKey }}-background_image_url" data-preview-id="cta-{{ $sectionKey }}-bg-preview" title="Afbeelding verwijderen" aria-label="Afbeelding verwijderen"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
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
                <div class="flex items-center gap-2 mb-1">
                    <label class="text-sm font-medium text-secondary-foreground">Titel</label>
                <input type="hidden" name="home_sections[visibility][{{ $sectionKey }}_title]" id="visibility-{{ $sectionKey }}_title" value="{{ $vis('_title') ? '1' : '0' }}">
                <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" data-target="visibility-{{ $sectionKey }}_title" aria-label="Titel tonen/verbergen">@if($vis('_title'))<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                </div>
                <input type="text" name="home_sections[{{ $sectionKey }}][title]" class="kt-input w-full" value="{{ old('home_sections.'.$sectionKey.'.title', $sectionData['title'] ?? 'Klaar om je carrière te starten?') }}">
            </div>
            <div class="row-visibility-row">
                <div class="flex items-center gap-2 mb-1">
                    <label class="text-sm font-medium text-secondary-foreground">Ondertitel</label>
                <input type="hidden" name="home_sections[visibility][{{ $sectionKey }}_subtitle]" id="visibility-{{ $sectionKey }}_subtitle" value="{{ $vis('_subtitle') ? '1' : '0' }}">
                <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" data-target="visibility-{{ $sectionKey }}_subtitle" aria-label="Ondertitel tonen/verbergen">@if($vis('_subtitle'))<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                </div>
                <textarea name="home_sections[{{ $sectionKey }}][subtitle]" id="home-{{ $sectionKey }}-subtitle" class="kt-input w-full pt-1 home-section-tinymce" rows="10">{{ old('home_sections.'.$sectionKey.'.subtitle', $sectionData['subtitle'] ?? '') }}</textarea>
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
                        <input type="text" name="home_sections[{{ $sectionKey }}][cta_primary_text]" class="kt-input w-full" value="{{ old('home_sections.'.$sectionKey.'.cta_primary_text', $sectionData['cta_primary_text'] ?? '') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-secondary-foreground mb-1">Knop 1 URL</label>
                        <input type="text" name="home_sections[{{ $sectionKey }}][cta_primary_url]" class="kt-input w-full" value="{{ old('home_sections.'.$sectionKey.'.cta_primary_url', $sectionData['cta_primary_url'] ?? '/register') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-secondary-foreground mb-1">Knop 2 tekst</label>
                        <input type="text" name="home_sections[{{ $sectionKey }}][cta_secondary_text]" class="kt-input w-full" value="{{ old('home_sections.'.$sectionKey.'.cta_secondary_text', $sectionData['cta_secondary_text'] ?? '') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-secondary-foreground mb-1">Knop 2 URL</label>
                        <input type="text" name="home_sections[{{ $sectionKey }}][cta_secondary_url]" class="kt-input w-full" value="{{ old('home_sections.'.$sectionKey.'.cta_secondary_url', $sectionData['cta_secondary_url'] ?? '/jobs') }}">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3 pt-3 border-t border-border">
                    <div class="space-y-3">
                        <label class="block text-sm font-medium text-secondary-foreground">Knop 1 kleuren</label>
                        <div class="flex flex-wrap items-center gap-3">
                            <div>
                                <label class="block text-xs font-medium text-muted-foreground mb-1">Achtergrond</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" id="{{ $sectionKey }}-cta-primary-bg" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($sectionData['cta_primary_bg'] ?? '') ?: '#2563eb' }}" title="Achtergrond">
                                    <input type="text" name="home_sections[{{ $sectionKey }}][cta_primary_bg]" class="kt-input w-24 font-mono text-sm" value="{{ old('home_sections.'.$sectionKey.'.cta_primary_bg', $sectionData['cta_primary_bg'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="{{ $sectionKey }}-cta-primary-bg">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-muted-foreground mb-1">Tekstkleur</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" id="{{ $sectionKey }}-cta-primary-text-color" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($sectionData['cta_primary_text_color'] ?? '') ?: '#ffffff' }}" title="Tekstkleur">
                                    <input type="text" name="home_sections[{{ $sectionKey }}][cta_primary_text_color]" class="kt-input w-24 font-mono text-sm" value="{{ old('home_sections.'.$sectionKey.'.cta_primary_text_color', $sectionData['cta_primary_text_color'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="{{ $sectionKey }}-cta-primary-text-color">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-muted-foreground mb-1">Border</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" id="{{ $sectionKey }}-cta-primary-border" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($sectionData['cta_primary_border'] ?? '') ?: '#ffffff' }}" title="Borderkleur">
                                    <input type="text" name="home_sections[{{ $sectionKey }}][cta_primary_border]" class="kt-input w-24 font-mono text-sm" value="{{ old('home_sections.'.$sectionKey.'.cta_primary_border', $sectionData['cta_primary_border'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="{{ $sectionKey }}-cta-primary-border">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <label class="block text-sm font-medium text-secondary-foreground">Knop 2 kleuren</label>
                        <div class="flex flex-wrap items-center gap-3">
                            <div>
                                <label class="block text-xs font-medium text-muted-foreground mb-1">Achtergrond</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" id="{{ $sectionKey }}-cta-secondary-bg" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($sectionData['cta_secondary_bg'] ?? '') ?: '#ffffff' }}" title="Achtergrond">
                                    <input type="text" name="home_sections[{{ $sectionKey }}][cta_secondary_bg]" class="kt-input w-24 font-mono text-sm" value="{{ old('home_sections.'.$sectionKey.'.cta_secondary_bg', $sectionData['cta_secondary_bg'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="{{ $sectionKey }}-cta-secondary-bg">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-muted-foreground mb-1">Tekstkleur</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" id="{{ $sectionKey }}-cta-secondary-text-color" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($sectionData['cta_secondary_text_color'] ?? '') ?: '#ffffff' }}" title="Tekstkleur">
                                    <input type="text" name="home_sections[{{ $sectionKey }}][cta_secondary_text_color]" class="kt-input w-24 font-mono text-sm" value="{{ old('home_sections.'.$sectionKey.'.cta_secondary_text_color', $sectionData['cta_secondary_text_color'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="{{ $sectionKey }}-cta-secondary-text-color">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-muted-foreground mb-1">Border</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" id="{{ $sectionKey }}-cta-secondary-border" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($sectionData['cta_secondary_border'] ?? '') ?: '#1f2937' }}" title="Borderkleur">
                                    <input type="text" name="home_sections[{{ $sectionKey }}][cta_secondary_border]" class="kt-input w-24 font-mono text-sm" value="{{ old('home_sections.'.$sectionKey.'.cta_secondary_border', $sectionData['cta_secondary_border'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="{{ $sectionKey }}-cta-secondary-border">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="text-xs text-muted-foreground mt-2">Achtergrond, tekstkleur en border per knop. Laat leeg voor standaardkleuren. Gebruik hex (bijv. #2563eb).</p>
            </div>
        </div>
    </div>
    @elseif($base === 'carousel')
    <div class="kt-card home-section-card" data-section="{{ $sectionKey }}">
        <div class="kt-card-header home-section-header home-section-header--carousel flex items-center justify-between gap-2">
            <span class="home-section-drag-handle cursor-grab active:cursor-grabbing touch-none p-1 -ml-1 rounded text-muted-foreground hover:text-foreground" title="Sleep om volgorde te wijzigen" aria-label="Volgorde wijzigen"><svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" /></svg></span>
            <h3 class="kt-card-title">{{ $sectionLabel('carousel') }}{{ $sectionKey !== 'carousel' ? ' – ' . $sectionKey : '' }}</h3>
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
            <p class="text-sm text-muted-foreground mb-3">Voeg afbeeldingen toe voor de carousel (geüpload via website media, versleuteld opgeslagen). Gebruik de knop + om een afbeelding te uploaden, prullenbak om een slide te verwijderen.</p>
            <div id="carousel-slides-{{ $sectionKey }}" class="space-y-2 mb-3" data-section-key="{{ $sectionKey }}">
                @foreach (($sectionData['items'] ?? []) as $idx => $item)
                    @php $uuid = $item['uuid'] ?? ''; $alt = $item['alt'] ?? ''; @endphp
                    <div class="carousel-slide-row flex items-center gap-2 rounded border border-border p-2 bg-muted/30" data-uuid="{{ $uuid }}">
                        <img src="{{ $uuid ? route('website-media.serve', ['uuid' => $uuid]) : '' }}" alt="" class="h-12 w-16 object-cover rounded flex-shrink-0" loading="lazy">
                        <input type="hidden" name="home_sections[{{ $sectionKey }}][items][{{ $idx }}][uuid]" value="{{ $uuid }}">
                        <input type="text" name="home_sections[{{ $sectionKey }}][items][{{ $idx }}][alt]" value="{{ $alt }}" placeholder="Alt-tekst (optioneel)" class="kt-input flex-1 min-w-0 text-sm">
                        <button type="button" class="carousel-slide-remove rounded p-1.5 text-destructive hover:bg-destructive/10" title="Verwijderen" aria-label="Slide verwijderen">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                @endforeach
            </div>
            <div class="flex items-center gap-2">
                <input type="file" id="carousel-upload-{{ $sectionKey }}" class="hidden" accept="image/*" multiple>
                <button type="button" class="carousel-add-slide inline-flex items-center gap-1.5 rounded-md border border-input bg-background px-3 py-1.5 text-sm font-medium hover:bg-accent" data-section-key="{{ $sectionKey }}" data-upload-url="{{ route('admin.website-media.upload') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Afbeelding(en) toevoegen
                </button>
            </div>
        </div>
    </div>
    @elseif(str_starts_with(strtolower($sectionKey ?? ''), 'component:'))
            @php
                $rawCompId = $componentService::componentIdFromKey($sectionKey);
                $compId = $rawCompId !== null ? trim(ltrim((string)$rawCompId, ':')) : '';
                $comp = $compId !== '' ? $componentService->getById($compId) : null;
                $displayName = ($comp && isset($comp->name) && trim((string)$comp->name) !== '') ? trim($comp->name) : 'Recente Vacatures';
                $moduleLabel = ($comp && isset($comp->module_name) && trim((string)$comp->module_name) !== '') ? (trim(explode(' ', (string)$comp->module_name)[0] ?? '') ?: trim($comp->module_name)) : 'Nexa';
                $componentTitle = $displayName . ' (' . $moduleLabel . ')';
            @endphp
    <div class="kt-card home-section-card home-section-card--component home-section-card--module" data-section="{{ $sectionKey }}">
        <div class="kt-card-header home-section-header home-section-header--footer flex items-center justify-between gap-2">
            <span class="home-section-drag-handle cursor-grab active:cursor-grabbing touch-none p-1 -ml-1 rounded text-muted-foreground hover:text-foreground" title="Sleep om volgorde te wijzigen" aria-label="Volgorde wijzigen"><svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" /></svg></span>
            <h3 class="kt-card-title">{{ $componentTitle }}</h3>
            <div class="flex items-center gap-1 shrink-0">
                <button type="button" class="home-section-component-remove kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-destructive" title="Component van pagina verwijderen" aria-label="Component verwijderen">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                </button>
            </div>
        </div>
    </div>
    @else
    {{-- Onbekende of dynamische sectie (bijv. hero_2): toon generieke kaart zodat volgorde zichtbaar blijft --}}
    <div class="kt-card home-section-card" data-section="{{ $sectionKey }}">
        <div class="kt-card-header home-section-header flex items-center justify-between gap-2">
            <span class="home-section-drag-handle cursor-grab active:cursor-grabbing touch-none p-1 -ml-1 rounded text-muted-foreground hover:text-foreground" title="Sleep om volgorde te wijzigen" aria-label="Volgorde wijzigen"><svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" /></svg></span>
            <h3 class="kt-card-title text-muted-foreground">Sectie: {{ $sectionKey }}</h3>
            <div class="flex items-center gap-1 shrink-0">
                <button type="button" class="home-section-component-remove kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-destructive" title="Verwijderen" aria-label="Verwijderen">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                </button>
            </div>
        </div>
    </div>
    @endif
    @endforeach
</div>{{-- /#home-sections-sortable --}}
<template id="home-section-component-card-template">
    <div class="kt-card home-section-card home-section-card--component home-section-card--module" data-section="">
        <div class="kt-card-header home-section-header home-section-header--footer flex items-center justify-between gap-2">
            <span class="home-section-drag-handle cursor-grab active:cursor-grabbing touch-none p-1 -ml-1 rounded text-muted-foreground hover:text-foreground" title="Sleep om volgorde te wijzigen" aria-label="Volgorde wijzigen"><svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" /></svg></span>
            <h3 class="kt-card-title component-card-title">Component</h3>
            <div class="flex items-center gap-1 shrink-0">
                <button type="button" class="home-section-component-remove kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-destructive" title="Component van pagina verwijderen" aria-label="Component verwijderen">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                </button>
            </div>
        </div>
    </div>
</template>

<div class="space-y-6 mt-6">
    <div class="kt-card home-section-card home-section-card--no-drag">
        <div class="kt-card-header home-section-header home-section-header--footer flex items-center justify-between gap-2">
            <h3 class="kt-card-title">Footer (bovenste blok)</h3>
            <div class="flex items-center gap-1 shrink-0">
                <input type="hidden" name="home_sections[visibility][footer]" id="visibility-footer" value="{{ ($visibility['footer'] ?? true) ? '1' : '0' }}">
                <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground" data-target="visibility-footer" title="{{ ($visibility['footer'] ?? true) ? 'Verbergen op website' : 'Tonen op website' }}" aria-label="Zichtbaarheid">
                    @if($visibility['footer'] ?? true)
                    <svg class="w-5 h-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    @else
                    <svg class="w-5 h-5 text-current opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                    @endif
                </button>
                <button type="button" class="home-section-collapse-toggle kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground" title="Inklappen" aria-label="Sectie inklappen of uitklappen">
                    <svg class="w-5 h-5 text-current home-section-collapse-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
                </button>
            </div>
        </div>
        <div class="home-section-card-body kt-card-table p-4 space-y-4">
            @php
                $footerLogoUrl = old('home_sections.footer.logo_url', $footer['logo_url'] ?? '');
                $footerLogoPreviewUrl = $footerLogoUrl ?: (app(\App\Services\WebsiteBuilderService::class)->getSiteBranding()['logo_url'] ?? '');
                $footerLogoHeight = (int) old('home_sections.footer.logo_height', $footer['logo_height'] ?? 12);
                if ($footerLogoHeight < 12 || $footerLogoHeight > 30) $footerLogoHeight = 12;
            @endphp
            <div class="row-visibility-row">
                <div class="flex items-center gap-2 mb-1">
                    <label class="text-sm font-medium text-secondary-foreground">Tagline / korte omschrijving</label>
                    <input type="hidden" name="home_sections[visibility][footer_tagline]" id="visibility-footer_tagline" value="{{ ($visibility['footer_tagline'] ?? true) ? '1' : '0' }}">
                    <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" data-target="visibility-footer_tagline" aria-label="Tagline tonen/verbergen">@if($visibility['footer_tagline'] ?? true)<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                </div>
                <textarea name="home_sections[footer][tagline]" id="home-footer-tagline" class="kt-input w-full pt-1 home-section-tinymce" rows="10" placeholder="Ontdek de perfecte match...">{{ old('home_sections.footer.tagline', $footer['tagline'] ?? '') }}</textarea>
                <p class="text-xs text-muted-foreground mt-1">Wordt onder het logo in de footer getoond. Gebruik de werkbalk voor bold, italic, lijsten, etc.</p>
            </div>
            <div class="row-visibility-row">
                <div class="flex items-center gap-2 mb-1">
                    <label class="text-sm font-medium text-secondary-foreground">Footer-logo</label>
                    <input type="hidden" name="home_sections[visibility][footer_logo]" id="visibility-footer_logo" value="{{ ($visibility['footer_logo'] ?? true) ? '1' : '0' }}">
                    <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" data-target="visibility-footer_logo" aria-label="Logo tonen/verbergen">@if($visibility['footer_logo'] ?? true)<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                </div>
                <p class="text-xs text-muted-foreground mb-2">Laat leeg om het logo uit Algemene instellingen te gebruiken.</p>
                <div class="flex flex-wrap items-start gap-4 max-w-96">
                    <img alt="Footer logo" id="footer-logo-preview" class="w-auto border border-border rounded object-contain {{ $footerLogoPreviewUrl ? '' : 'hidden' }}" style="max-height: 80px;"
                         src="{{ $footerLogoPreviewUrl ? $imagePreviewUrl($footerLogoPreviewUrl) : '' }}">
                    <div class="flex flex-col items-center justify-center p-5 lg:p-7 border border-input rounded-xl border-dashed bg-muted/30" id="footer-logo-upload-area" style="width: 500px; min-width: 500px; height: 130px;">
                        <div class="flex flex-col place-items-center place-content-center text-center w-full">
                            <div class="flex items-center mb-2.5">
                                <div class="relative size-11 shrink-0">
                                    <i class="ki-filled ki-picture text-2xl text-primary"></i>
                                </div>
                            </div>
                            <a class="text-mono text-xs font-medium hover:text-primary mb-px cursor-pointer" id="footer-logo-upload-link">Klik of Sleep &amp; Drop</a>
                            <span class="text-xs text-muted-foreground">SVG, PNG, JPG (max. 2MB)</span>
                        </div>
                    </div>
                    <input type="file" id="footer-logo-input" accept="image/svg+xml,image/png,image/jpeg,image/jpg,image/gif" class="hidden">
                </div>
                <input type="hidden" name="home_sections[footer][logo_url]" id="footer-logo-url" value="{{ $footerLogoUrl }}">
            </div>
            <div>
                <label class="block text-sm font-medium text-secondary-foreground mb-1">Logo-hoogte (px)</label>
                <select name="home_sections[footer][logo_height]" id="footer-logo-height" class="kt-input w-32">
                    @foreach([12, 14, 16, 18, 20, 22, 24, 26, 28, 30] as $px)
                        <option value="{{ $px }}" {{ $footerLogoHeight === $px ? 'selected' : '' }}>{{ $px }}px</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-secondary-foreground mb-1">Logo alt-tekst</label>
                <input type="text" name="home_sections[footer][logo_alt]" class="kt-input w-full" value="{{ old('home_sections.footer.logo_alt', $footer['logo_alt'] ?? '') }}" placeholder="Bijv. Nexa Skillmatching">
            </div>
            <div class="row-visibility-row">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-sm font-medium text-secondary-foreground">Titel kolom Snelle Links</span>
                    <input type="hidden" name="home_sections[visibility][footer_quick_links]" id="visibility-footer_quick_links" value="{{ ($visibility['footer_quick_links'] ?? true) ? '1' : '0' }}">
                    <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" data-target="visibility-footer_quick_links" aria-label="Snelle links tonen/verbergen">@if($visibility['footer_quick_links'] ?? true)<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                </div>
                <label class="block text-xs text-muted-foreground mb-1">Titel kolom</label>
                <input type="text" name="home_sections[footer][quick_links_title]" class="kt-input w-full" value="{{ old('home_sections.footer.quick_links_title', $footer['quick_links_title'] ?? 'Snelle Links') }}" placeholder="Snelle Links">
            </div>
            <div class="space-y-3">
                <p class="text-sm font-medium text-secondary-foreground">Snelle Links</p>
                <div id="footer-quick-links-list" class="space-y-3">
                    @php $quickLinks = $footer['quick_links'] ?? []; @endphp
                    @foreach($quickLinks as $i => $link)
                    <div class="footer-link-row flex flex-wrap items-center gap-3" data-index="{{ $i }}">
                        <input type="text" name="home_sections[footer][quick_links][{{ $i }}][label]" class="kt-input flex-1 min-w-[120px]" value="{{ old("home_sections.footer.quick_links.{$i}.label", $link['label'] ?? '') }}" placeholder="Label">
                        <input type="text" name="home_sections[footer][quick_links][{{ $i }}][url]" class="kt-input flex-1 min-w-[160px]" value="{{ old("home_sections.footer.quick_links.{$i}.url", $link['url'] ?? '') }}" placeholder="/pad of https://...">
                        <button type="button" class="footer-link-remove kt-btn kt-btn-icon kt-btn-sm kt-btn-outline" title="Verwijderen" aria-label="Verwijderen"><i class="ki-filled ki-trash"></i></button>
                    </div>
                    @endforeach
                </div>
                <button type="button" id="footer-quick-links-add" class="kt-btn kt-btn-sm kt-btn-outline"><i class="ki-filled ki-plus me-1"></i>Link toevoegen</button>
            </div>
            <div class="row-visibility-row">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-sm font-medium text-secondary-foreground">Ondersteuning-links</span>
                    <input type="hidden" name="home_sections[visibility][footer_support_links]" id="visibility-footer_support_links" value="{{ ($visibility['footer_support_links'] ?? true) ? '1' : '0' }}">
                    <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" data-target="visibility-footer_support_links" aria-label="Ondersteuning-links tonen/verbergen">@if($visibility['footer_support_links'] ?? true)<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                </div>
                <label class="block text-xs text-muted-foreground mb-1">Titel kolom Ondersteuning</label>
                <input type="text" name="home_sections[footer][support_links_title]" class="kt-input w-full mb-3" value="{{ old('home_sections.footer.support_links_title', $footer['support_links_title'] ?? 'Ondersteuning') }}" placeholder="Ondersteuning">
            <div class="space-y-3">
                <p class="text-sm font-medium text-secondary-foreground">Ondersteuning-links</p>
                <div id="footer-support-links-list" class="space-y-3">
                    @php $supportLinks = $footer['support_links'] ?? []; @endphp
                    @foreach($supportLinks as $i => $link)
                    <div class="footer-link-row flex flex-wrap items-center gap-3" data-index="{{ $i }}">
                        <input type="text" name="home_sections[footer][support_links][{{ $i }}][label]" class="kt-input flex-1 min-w-[120px]" value="{{ old("home_sections.footer.support_links.{$i}.label", $link['label'] ?? '') }}" placeholder="Label">
                        <input type="text" name="home_sections[footer][support_links][{{ $i }}][url]" class="kt-input flex-1 min-w-[160px]" value="{{ old("home_sections.footer.support_links.{$i}.url", $link['url'] ?? '') }}" placeholder="/pad of https://...">
                        <button type="button" class="footer-link-remove kt-btn kt-btn-icon kt-btn-sm kt-btn-outline" title="Verwijderen" aria-label="Verwijderen"><i class="ki-filled ki-trash"></i></button>
                    </div>
                    @endforeach
                </div>
                <button type="button" id="footer-support-links-add" class="kt-btn kt-btn-sm kt-btn-outline"><i class="ki-filled ki-plus me-1"></i>Link toevoegen</button>
            </div>
            </div>
        </div>
    </div>

    <div class="kt-card home-section-card home-section-card--no-drag">
        <div class="kt-card-header home-section-header home-section-header--copyright flex items-center justify-between gap-2">
            <h3 class="kt-card-title">Copyright (onderste balk)</h3>
            <button type="button" class="home-section-collapse-toggle kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground" title="Inklappen" aria-label="Sectie inklappen of uitklappen">
                <svg class="w-5 h-5 text-current home-section-collapse-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
            </button>
        </div>
        <div class="home-section-card-body kt-card-table p-4 space-y-3">
            <div>
                <label class="block text-sm font-medium text-secondary-foreground mb-1">Copyrighttekst</label>
                <input type="text" name="home_sections[copyright]" class="kt-input w-full" value="{{ old('home_sections.copyright', $copyright) }}" placeholder="© {year} Nexa Skillmatching. Alle rechten voorbehouden.">
                <p class="text-xs text-muted-foreground mt-1">Gebruik <code>{year}</code> voor het huidige jaar.</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js" crossorigin="anonymous"></script>
<script>
(function() {
    var uploadUrl = {!! json_encode(route('admin.website-pages.upload-footer-logo')) !!};
    var csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) return;

    var preview = document.getElementById('footer-logo-preview');
    var urlInput = document.getElementById('footer-logo-url');
    var area = document.getElementById('footer-logo-upload-area');
    var linkEl = document.getElementById('footer-logo-upload-link');
    var fileInput = document.getElementById('footer-logo-input');

    if (area && fileInput && urlInput) {
        linkEl && linkEl.addEventListener('click', function(e) { e.preventDefault(); fileInput.click(); });
        area.addEventListener('click', function(e) { if (e.target === area || e.target.closest('#footer-logo-upload-area')) fileInput.click(); });
        area.addEventListener('dragover', function(e) { e.preventDefault(); e.stopPropagation(); area.classList.add('border-primary'); });
        area.addEventListener('dragleave', function(e) { e.preventDefault(); area.classList.remove('border-primary'); });
        area.addEventListener('drop', function(e) {
            e.preventDefault();
            area.classList.remove('border-primary');
            if (e.dataTransfer.files.length) handleFooterLogoFile(e.dataTransfer.files[0]);
        });
        fileInput.addEventListener('change', function() { if (this.files && this.files.length) handleFooterLogoFile(this.files[0]); });

        function handleFooterLogoFile(file) {
            var allowed = ['image/svg+xml','image/png','image/jpeg','image/jpg','image/gif'];
            if (!allowed.includes(file.type)) { alert('Alleen SVG, PNG, JPG en GIF zijn toegestaan.'); fileInput.value = ''; return; }
            if (file.size > 2 * 1024 * 1024) { alert('Max. 2MB.'); fileInput.value = ''; return; }
            var reader = new FileReader();
            reader.onload = function(e) { preview.src = e.target.result; preview.classList.remove('hidden'); };
            reader.readAsDataURL(file);
            var fd = new FormData();
            fd.append('logo', file);
            fd.append('_token', csrfToken.getAttribute('content'));
            fetch(uploadUrl, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                .then(function(r) { return r.ok ? r.json() : r.json().then(function(d) { throw new Error(d.message || 'Upload mislukt'); }); })
                .then(function(d) { if (d.success && d.logo_url) { urlInput.value = d.logo_url; preview.src = d.logo_url; } })
                .catch(function(err) { alert(err.message || 'Upload mislukt'); });
        }
    }

    var heroImageUploadUrl = {!! json_encode(route('admin.website-pages.upload-hero-image')) !!};
    function handleHeroImageFile(file, fileInput, urlInput, previewEl) {
        var allowed = ['image/jpeg','image/png','image/jpg','image/gif','image/webp'];
        if (!allowed.includes(file.type)) { alert('Alleen JPEG, PNG, JPG, GIF en WebP zijn toegestaan.'); fileInput.value = ''; return; }
        if (file.size > 5 * 1024 * 1024) { alert('Max. 5MB.'); fileInput.value = ''; return; }
        var fd = new FormData();
        fd.append('image', file);
        fd.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        fetch(heroImageUploadUrl, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(function(r) { return r.ok ? r.json() : r.json().then(function(d) { throw new Error(d.message || 'Upload mislukt'); }); })
            .then(function(d) { if (d.success && d.url) { urlInput.value = d.url; if (previewEl) { previewEl.src = d.url; previewEl.classList.remove('hidden'); } } })
            .catch(function(err) { alert(err.message || 'Upload mislukt'); });
        fileInput.value = '';
    }
    function bindHeroUploadAreasIn(container) {
        var root = container || document;
        root.querySelectorAll('.hero-image-upload-area').forEach(function(area) {
            var sectionKey = area.getAttribute('data-section-key');
            var field = area.getAttribute('data-field');
            if (!sectionKey || !field) return;
            var cardRow = area.closest && area.closest('.cards-ronde-hoeken-item');
            var scope = cardRow || root;
            var fileInput = scope.querySelector('.hero-image-file-input[data-section-key="' + sectionKey + '"][data-field="' + field + '"]');
            var urlInput = scope.querySelector('[id="hero-' + sectionKey + '-' + field + '"]');
            if (!urlInput) urlInput = document.getElementById('hero-' + sectionKey + '-' + field);
            var previewId = field === 'background_image_url' ? 'hero-' + sectionKey + '-bg-preview' : (field === 'author_image_url' ? 'hero-' + sectionKey + '-author-preview' : 'hero-' + sectionKey + '-' + field + '-preview');
            var preview = scope.querySelector('[id="' + previewId + '"]');
            if (!preview) preview = document.getElementById(previewId);
            if (!fileInput || !urlInput) return;
            area.addEventListener('click', function(e) { e.preventDefault(); fileInput.click(); });
            area.addEventListener('dragover', function(e) { e.preventDefault(); e.stopPropagation(); area.classList.add('border-primary'); });
            area.addEventListener('dragleave', function(e) { e.preventDefault(); area.classList.remove('border-primary'); });
            area.addEventListener('drop', function(e) { e.preventDefault(); area.classList.remove('border-primary'); if (e.dataTransfer.files.length) handleHeroImageFile(e.dataTransfer.files[0], fileInput, urlInput, preview); });
            fileInput.addEventListener('change', function() { if (this.files && this.files.length) handleHeroImageFile(this.files[0], fileInput, urlInput, preview); });
        });
    }
    bindHeroUploadAreasIn(document);
    window.bindHeroUploadAreasIn = bindHeroUploadAreasIn;

    document.querySelectorAll('.cta-image-upload-area').forEach(function(area) {
        var sectionKey = area.getAttribute('data-section-key');
        if (!sectionKey) return;
        var fileInput = document.querySelector('.cta-image-file-input[data-section-key="' + sectionKey + '"]');
        var urlInput = document.getElementById('cta-' + sectionKey + '-background_image_url');
        var preview = document.getElementById('cta-' + sectionKey + '-bg-preview');
        if (!fileInput || !urlInput) return;
        area.addEventListener('click', function(e) { e.preventDefault(); fileInput.click(); });
        area.addEventListener('dragover', function(e) { e.preventDefault(); e.stopPropagation(); area.classList.add('border-primary'); });
        area.addEventListener('dragleave', function(e) { e.preventDefault(); area.classList.remove('border-primary'); });
        area.addEventListener('drop', function(e) { e.preventDefault(); area.classList.remove('border-primary'); if (e.dataTransfer.files.length) handleHeroImageFile(e.dataTransfer.files[0], fileInput, urlInput, preview); });
        fileInput.addEventListener('change', function() { if (this.files && this.files.length) handleHeroImageFile(this.files[0], fileInput, urlInput, preview); });
    });

    document.querySelectorAll('.image-remove-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var urlInputId = btn.getAttribute('data-url-input-id');
            var previewId = btn.getAttribute('data-preview-id');
            if (!urlInputId || !previewId) return;
            var urlInput = document.getElementById(urlInputId);
            var preview = document.getElementById(previewId);
            if (urlInput) urlInput.value = '';
            if (preview) {
                var defaultSrc = preview.getAttribute('data-default-src') || '';
                if (defaultSrc) {
                    preview.src = defaultSrc;
                    preview.classList.remove('hidden');
                } else {
                    preview.src = '';
                    preview.classList.add('hidden');
                }
            }
        });
    });

    // Carousel: add slide(s) – meerdere afbeeldingen tegelijk (upload via website-media, encrypted)
    var carouselAddBtnLabel = 'Afbeelding(en) toevoegen';
    var carouselAddBtnHtml = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>\n                ' + carouselAddBtnLabel;
    document.querySelectorAll('.carousel-add-slide').forEach(function(btn) {
        var sectionKey = btn.getAttribute('data-section-key');
        var uploadUrl = btn.getAttribute('data-upload-url');
        var listEl = document.getElementById('carousel-slides-' + sectionKey);
        var fileInput = document.getElementById('carousel-upload-' + sectionKey);
        if (!sectionKey || !uploadUrl || !listEl || !fileInput) return;
        btn.addEventListener('click', function() { fileInput.click(); });
        fileInput.addEventListener('change', function() {
            var files = this.files;
            if (!files || !files.length) return;
            var valid = [];
            for (var i = 0; i < files.length; i++) {
                var f = files[i];
                if (!f.type || !f.type.startsWith('image/')) continue;
                if (f.size > 5 * 1024 * 1024) continue;
                valid.push(f);
            }
            if (valid.length === 0) {
                alert('Geen geldige afbeeldingen (max. 5MB per bestand, alleen afbeeldingen).');
                this.value = '';
                return;
            }
            var token = (csrfToken && csrfToken.getAttribute('content')) || '';
            btn.disabled = true;
            btn.setAttribute('aria-busy', 'true');
            btn.innerHTML = '<span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" aria-hidden="true"></span> Bezig met uploaden' + (valid.length > 1 ? ' (' + valid.length + ' afbeeldingen)...' : '...');
            var uploads = valid.map(function(file) {
                var fd = new FormData();
                fd.append('file', file);
                fd.append('_token', token);
                return fetch(uploadUrl, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                    .then(function(r) { return r.ok ? r.json() : r.json().then(function(d) { throw new Error(d.message || (d.errors && d.errors.file && d.errors.file[0]) || 'Upload mislukt'); }); });
            });
            Promise.all(uploads)
                .then(function(results) {
                    var startIdx = listEl.querySelectorAll('.carousel-slide-row').length;
                    results.forEach(function(d, i) {
                        var idx = startIdx + i;
                        var url = (d && d.url) ? d.url : ('/website-media/' + (d && d.uuid ? d.uuid : ''));
                        var uuid = (d && d.uuid) ? d.uuid : '';
                        var row = document.createElement('div');
                        row.className = 'carousel-slide-row flex items-center gap-2 rounded border border-border p-2 bg-muted/30';
                        row.setAttribute('data-uuid', uuid);
                        row.innerHTML = '<img src="' + url.replace(/"/g, '&quot;') + '" alt="" class="h-12 w-16 object-cover rounded flex-shrink-0" loading="lazy">' +
                            '<input type="hidden" name="home_sections[' + sectionKey + '][items][' + idx + '][uuid]" value="' + (uuid.replace(/"/g, '&quot;')) + '">' +
                            '<input type="text" name="home_sections[' + sectionKey + '][items][' + idx + '][alt]" value="" placeholder="Alt-tekst (optioneel)" class="kt-input flex-1 min-w-0 text-sm">' +
                            '<button type="button" class="carousel-slide-remove rounded p-1.5 text-destructive hover:bg-destructive/10" title="Verwijderen" aria-label="Slide verwijderen">' +
                            '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>';
                        listEl.appendChild(row);
                    });
                    bindCarouselRemove(listEl, sectionKey);
                })
                .catch(function(err) {
                    alert(err.message || 'Upload mislukt.');
                })
                .finally(function() {
                    btn.disabled = false;
                    btn.removeAttribute('aria-busy');
                    btn.innerHTML = carouselAddBtnHtml;
                    fileInput.value = '';
                });
        });
    });
    function reindexCarouselRows(listEl, sectionKey) {
        var rows = listEl.querySelectorAll('.carousel-slide-row');
        rows.forEach(function(row, i) {
            var uuidInp = row.querySelector('input[name$="[uuid]"]');
            var altInp = row.querySelector('input[name$="[alt]"]');
            if (uuidInp) uuidInp.name = 'home_sections[' + sectionKey + '][items][' + i + '][uuid]';
            if (altInp) altInp.name = 'home_sections[' + sectionKey + '][items][' + i + '][alt]';
        });
    }
    function bindCarouselRemove(listEl, sectionKey) {
        listEl.querySelectorAll('.carousel-slide-remove').forEach(function(btn) {
            if (btn._carouselBound) return;
            btn._carouselBound = true;
            btn.addEventListener('click', function() {
                var row = btn.closest('.carousel-slide-row');
                if (row) { row.remove(); reindexCarouselRows(listEl, sectionKey); }
            });
        });
    }
    document.querySelectorAll('[id^="carousel-slides-"]').forEach(function(listEl) {
        var sectionKey = listEl.getAttribute('data-section-key');
        if (sectionKey) bindCarouselRemove(listEl, sectionKey);
    });

    function makeLinkRow(prefix, index, label, url) {
        var div = document.createElement('div');
        div.className = 'footer-link-row flex flex-wrap items-center gap-3';
        div.setAttribute('data-index', index);
        div.innerHTML = '<input type="text" name="' + prefix + '[' + index + '][label]" class="kt-input flex-1 min-w-[120px]" value="' + (label || '').replace(/"/g, '&quot;') + '" placeholder="Label">' +
            '<input type="text" name="' + prefix + '[' + index + '][url]" class="kt-input flex-1 min-w-[160px]" value="' + (url || '').replace(/"/g, '&quot;') + '" placeholder="/pad of https://...">' +
            '<button type="button" class="footer-link-remove kt-btn kt-btn-icon kt-btn-sm kt-btn-outline" title="Verwijderen" aria-label="Verwijderen"><i class="ki-filled ki-trash"></i></button>';
        return div;
    }
    function reindexLinkRows(container, prefix) {
        var rows = container.querySelectorAll('.footer-link-row');
        rows.forEach(function(row, i) {
            row.setAttribute('data-index', i);
            var labelInp = row.querySelector('input[name$="[label]"]');
            var urlInp = row.querySelector('input[name$="[url]"]');
            if (labelInp) labelInp.name = prefix + '[' + i + '][label]';
            if (urlInp) urlInp.name = prefix + '[' + i + '][url]';
        });
    }
    function bindRemove(container, prefix) {
        container.querySelectorAll('.footer-link-remove').forEach(function(btn) {
            btn.onclick = function() {
                btn.closest('.footer-link-row').remove();
                reindexLinkRows(container, prefix);
            };
        });
    }
    var quickList = document.getElementById('footer-quick-links-list');
    var quickAdd = document.getElementById('footer-quick-links-add');
    var quickPrefix = 'home_sections[footer][quick_links]';
    if (quickList && quickAdd) {
        quickAdd.addEventListener('click', function() {
            var n = quickList.querySelectorAll('.footer-link-row').length;
            var row = makeLinkRow(quickPrefix, n, '', '');
            quickList.appendChild(row);
            bindRemove(quickList, quickPrefix);
        });
        bindRemove(quickList, quickPrefix);
    }
    var supportList = document.getElementById('footer-support-links-list');
    var supportAdd = document.getElementById('footer-support-links-add');
    var supportPrefix = 'home_sections[footer][support_links]';
    if (supportList && supportAdd) {
        supportAdd.addEventListener('click', function() {
            var n = supportList.querySelectorAll('.footer-link-row').length;
            var row = makeLinkRow(supportPrefix, n, '', '');
            supportList.appendChild(row);
            bindRemove(supportList, supportPrefix);
        });
        bindRemove(supportList, supportPrefix);
    }
})();

    // Section visibility toggle (eye = tonen, eye-slash = verborgen op website)
    var eyeSvg = '<svg class="w-5 h-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>';
    var eyeSlashSvg = '<svg class="w-5 h-5 text-current opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>';
    document.querySelectorAll('.section-visibility-toggle').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = btn.getAttribute('data-target');
            var input = document.getElementById(id);
            if (!input) return;
            var visible = input.value !== '1';
            input.value = visible ? '1' : '0';
            btn.setAttribute('title', visible ? 'Verbergen op website' : 'Tonen op website');
            btn.innerHTML = visible ? eyeSvg : eyeSlashSvg;
        });
    });

    // Collapse/expand sectie-kaarten (rechts van het oogje in de header)
    var chevronDownSvg = '<svg class="w-5 h-5 text-current home-section-collapse-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>';
    var chevronUpSvg = '<svg class="w-5 h-5 text-current home-section-collapse-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>';
    function setSectionCollapsed(card, collapsed) {
        var body = card && card.querySelector('.home-section-card-body');
        var btn = card && card.querySelector('.home-section-collapse-toggle');
        if (!card) return;
        if (collapsed) {
            card.classList.add('home-section-card--collapsed');
            if (body) body.style.display = 'none';
            if (btn) { btn.setAttribute('title', 'Uitklappen'); btn.setAttribute('aria-label', 'Sectie uitklappen'); btn.innerHTML = chevronDownSvg; }
        } else {
            card.classList.remove('home-section-card--collapsed');
            if (body) body.style.display = '';
            if (btn) { btn.setAttribute('title', 'Inklappen'); btn.setAttribute('aria-label', 'Sectie inklappen'); btn.innerHTML = chevronUpSvg; }
        }
    }
    function updateCollapseAllButton() {
        var cardWrapper = document.getElementById('home_sections_card');
        var allBtn = document.getElementById('home-sections-collapse-all-btn');
        var iconEl = document.getElementById('home-sections-collapse-all-icon');
        if (!cardWrapper || !allBtn) return;
        var cards = cardWrapper.querySelectorAll('.home-section-card');
        var allCollapsed = cards.length > 0 && [].slice.call(cards).every(function(c) { return c.classList.contains('home-section-card--collapsed'); });
        var title = allCollapsed ? 'Alles uitklappen' : 'Alles inklappen';
        allBtn.setAttribute('title', title);
        allBtn.setAttribute('aria-label', title);
        if (iconEl) {
            iconEl.outerHTML = allCollapsed
                ? '<svg class="w-5 h-5 text-current" id="home-sections-collapse-all-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>'
                : '<svg class="w-5 h-5 text-current" id="home-sections-collapse-all-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 7.5m0 0L7.5 12m4.5-4.5V21" /></svg>';
        }
    }
    // Event delegation: collapse werkt voor alle sectiekaarten (in sortable + Footer/Copyright)
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.home-section-collapse-toggle');
        if (!btn) return;
        var card = btn.closest('.home-section-card');
        if (!card) return;
        var willBeCollapsed = !card.classList.contains('home-section-card--collapsed');
        setSectionCollapsed(card, willBeCollapsed);
        updateCollapseAllButton();
    });
    var collapseAllBtn = document.getElementById('home-sections-collapse-all-btn');
    if (collapseAllBtn) {
        collapseAllBtn.addEventListener('click', function() {
            var cardWrapper = document.getElementById('home_sections_card');
            if (!cardWrapper) return;
            var cards = cardWrapper.querySelectorAll('.home-section-card');
            var allCollapsed = cards.length > 0 && [].slice.call(cards).every(function(c) { return c.classList.contains('home-section-card--collapsed'); });
            cards.forEach(function(card) { setSectionCollapsed(card, !allCollapsed); });
            updateCollapseAllButton();
        });
    }
    updateCollapseAllButton();

    // Sectie of componentkaart verwijderen: event delegation (normale secties .home-section-remove, componenten .home-section-component-remove)
    var sortableContainerRemove = document.getElementById('home-sections-sortable');
    if (sortableContainerRemove) {
        sortableContainerRemove.addEventListener('click', function(e) {
            var btn = e.target.closest('.home-section-remove, .home-section-component-remove');
            if (!btn) return;
            var card = btn.closest('.home-section-card');
            if (!card) return;
            var sectionKey = card.getAttribute('data-section');
            if (!sectionKey) return;
            if (typeof tinymce !== 'undefined') {
                card.querySelectorAll('textarea[id]').forEach(function(ta) {
                    var id = ta.id;
                    if (id && tinymce.get(id)) {
                        try { tinymce.get(id).remove(); } catch (err) {}
                    }
                });
                [].slice.call(tinymce.editors || []).forEach(function(ed) {
                    try {
                        var container = ed.getContainer && ed.getContainer();
                        if (container && card.contains(container)) ed.remove();
                    } catch (err) {}
                });
            }
            card.remove();
            var orderInput = document.getElementById('home-sections-order-input');
            if (orderInput) {
                var order = (orderInput.value || '').split(',').map(function(s) { return s.trim(); }).filter(Boolean);
                var idx = order.indexOf(sectionKey);
                if (idx !== -1) { order.splice(idx, 1); orderInput.value = order.join(','); }
            }
            updateCollapseAllButton();
        });
    }

    // Kleurvelden: color-picker en hex-tekstveld synchen
    function normalizeHex(v) {
        if (!v || typeof v !== 'string') return '';
        v = v.trim().toLowerCase();
        if (/^#[0-9a-f]{3}$/.test(v)) return v[0] + v[1] + v[1] + v[2] + v[2] + v[3] + v[3];
        if (/^#[0-9a-f]{6}$/.test(v)) return v;
        if (/^[0-9a-f]{6}$/.test(v)) return '#' + v;
        return '';
    }
    document.querySelectorAll('input[data-sync-from]').forEach(function(textInp) {
        var colorId = textInp.getAttribute('data-sync-from');
        var colorInp = document.getElementById(colorId);
        if (!colorInp) return;
        colorInp.addEventListener('input', function() { textInp.value = colorInp.value; });
        colorInp.addEventListener('change', function() { textInp.value = colorInp.value; });
        textInp.addEventListener('input', function() {
            var hex = normalizeHex(textInp.value);
            if (hex) colorInp.value = hex;
        });
        textInp.addEventListener('change', function() {
            var hex = normalizeHex(textInp.value);
            if (hex) colorInp.value = hex;
        });
    });

    // Sleepbare volgorde van home-sectie componenten
    (function() {
        var container = document.getElementById('home-sections-sortable');
        var input = document.getElementById('home-sections-order-input');
        if (!container || !input) return;
        function updateOrder() {
            var order = [];
            [].slice.call(container.children).forEach(function(el) {
                var s = el.getAttribute('data-section');
                if (s) order.push(s);
            });
            input.value = order.join(',');
        }
        var desired = (input.value || '').split(',').map(function(s) { return s.trim(); }).filter(Boolean);
        if (desired.length) {
            var cards = [].slice.call(container.children);
            desired.forEach(function(key) {
                var card = cards.filter(function(c) { return c.getAttribute('data-section') === key; })[0];
                if (card) container.appendChild(card);
            });
        }
        if (typeof Sortable !== 'undefined') {
            new Sortable(container, {
                handle: '.home-section-drag-handle',
                animation: 150,
                ghostClass: 'opacity-50',
                onEnd: updateOrder
            });
        }
    })();

    // Feature-kaarten: sortable, toevoegen, verwijderen, reindex
    (function() {
        var container = document.getElementById('features-items-sortable');
        var addBtn = document.getElementById('features-item-add');
        if (!container) return;

        function initTinymceForId(id) {
            if (typeof window.initHomeSectionTinymce === 'function') window.initHomeSectionTinymce(id, 200);
        }
        function reindexFeaturesItems() {
            var rows = container.querySelectorAll('.features-item-row');
            rows.forEach(function(row, i) {
                row.setAttribute('data-features-index', i);
                var numEl = row.querySelector('.features-item-num');
                if (numEl) numEl.textContent = i + 1;
                var titleInp = row.querySelector('.features-item-title');
                if (titleInp) titleInp.name = 'home_sections[features][items][' + i + '][title]';
                var iconInp = row.querySelector('.features-item-icon');
                if (iconInp) iconInp.name = 'home_sections[features][items][' + i + '][icon]';
                var iconSizeInp = row.querySelector('.features-item-icon-size');
                if (iconSizeInp) iconSizeInp.name = 'home_sections[features][items][' + i + '][icon_size]';
                var visInput = row.querySelector('input[type="hidden"][name*="visibility"][name*="features_item"]');
                if (visInput) {
                    visInput.name = 'home_sections[visibility][features_item_' + i + ']';
                    visInput.id = 'visibility-features_item_' + i;
                }
                var ta = row.querySelector('textarea');
                if (ta) {
                    var oldId = ta.id;
                    var content = (typeof tinymce !== 'undefined' && tinymce.get(oldId)) ? tinymce.get(oldId).getContent() : ta.value;
                    var newId = 'home-features-item-' + i + '-description';
                    if (typeof tinymce !== 'undefined' && tinymce.get(oldId)) tinymce.get(oldId).remove();
                    ta.id = newId;
                    ta.name = 'home_sections[features][items][' + i + '][description]';
                    ta.value = content;
                    ta.classList.add('home-section-tinymce');
                    initTinymceForId(newId);
                }
            });
        }

        container.addEventListener('click', function(e) {
            var removeBtn = e.target.closest('.features-item-remove');
            if (!removeBtn) return;
            var row = removeBtn.closest('.features-item-row');
            if (!row) return;
            var rows = container.querySelectorAll('.features-item-row');
            if (rows.length <= 2) return;
            var ta = row.querySelector('textarea');
            if (ta && typeof tinymce !== 'undefined' && tinymce.get(ta.id)) tinymce.get(ta.id).remove();
            row.remove();
            reindexFeaturesItems();
        });

        if (addBtn) {
            addBtn.addEventListener('click', function() {
                var rows = container.querySelectorAll('.features-item-row');
                var nextIndex = rows.length;
                var isDark = document.documentElement.classList.contains('dark');
                var eyeSvg = '<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>';
                var eyeSlashSvg = '<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>';
                var dragSvg = '<svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" /></svg>';
                var trashSvg = '<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>';
                var iconOpts = '';
                try {
                    var iconData = JSON.parse(container.getAttribute('data-icon-options') || '{}');
                    for (var k in iconData) { iconOpts += '<option value="' + k + '">' + (iconData[k] || k) + '</option>'; }
                } catch (e) { iconOpts = '<option value="light-bulb">Gloeilamp</option><option value="bolt">Bliksem</option><option value="key">Sleutel</option>'; }
                var sizeOpts = '';
                try {
                    var sizeData = JSON.parse(container.getAttribute('data-size-options') || '{}');
                    for (var s in sizeData) { sizeOpts += '<option value="' + s + '"' + (s === 'medium' ? ' selected' : '') + '>' + (sizeData[s] || s) + '</option>'; }
                } catch (e) { sizeOpts = '<option value="small">Klein</option><option value="medium" selected>Normaal</option><option value="large">Groot</option>'; }
                var div = document.createElement('div');
                div.className = 'features-item-row row-visibility-row border border-border rounded-lg p-4 space-y-3 flex gap-3';
                div.setAttribute('data-features-index', nextIndex);
                div.innerHTML = '<span class="features-item-drag-handle cursor-grab active:cursor-grabbing touch-none shrink-0 mt-1 p-1 rounded text-muted-foreground hover:text-foreground" title="Sleep om volgorde te wijzigen" aria-label="Volgorde wijzigen">' + dragSvg + '</span>' +
                    '<div class="flex-1 min-w-0 space-y-3">' +
                    '<div class="flex items-center gap-2 mb-1 flex-wrap">' +
                    '<p class="text-sm font-medium text-secondary-foreground">Kaart <span class="features-item-num">' + (nextIndex + 1) + '</span></p>' +
                    '<input type="hidden" name="home_sections[visibility][features_item_' + nextIndex + ']" id="visibility-features_item_' + nextIndex + '" value="1">' +
                    '<button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" data-target="visibility-features_item_' + nextIndex + '" aria-label="Kaart tonen/verbergen">' + eyeSvg + '</button>' +
                    '<button type="button" class="features-item-remove kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Kaart verwijderen" aria-label="Verwijderen">' + trashSvg + '</button>' +
                    '</div>' +
                    '<div><label class="block text-xs text-muted-foreground mb-1">Titel</label><input type="text" name="home_sections[features][items][' + nextIndex + '][title]" class="kt-input w-full features-item-title" value=""></div>' +
                    '<div><label class="block text-xs text-muted-foreground mb-1">Beschrijving</label><textarea name="home_sections[features][items][' + nextIndex + '][description]" id="home-features-item-' + nextIndex + '-description" class="kt-input w-full pt-1 home-section-tinymce" rows="6"></textarea></div>' +
                    '<div class="grid grid-cols-1 sm:grid-cols-2 gap-3"><div><label class="block text-xs text-muted-foreground mb-1">Icoon (Heroicon)</label><select name="home_sections[features][items][' + nextIndex + '][icon]" class="kt-input w-full features-item-icon">' + iconOpts + '</select></div><div><label class="block text-xs text-muted-foreground mb-1">Grootte icoon</label><select name="home_sections[features][items][' + nextIndex + '][icon_size]" class="kt-input w-full features-item-icon-size">' + sizeOpts + '</select></div></div>' +
                    '</div>';
                container.appendChild(div);
                initTinymceForId('home-features-item-' + nextIndex + '-description');
                var newToggle = div.querySelector('.section-visibility-toggle');
                if (newToggle) {
                    newToggle.addEventListener('click', function() {
                        var id = this.getAttribute('data-target');
                        var input = document.getElementById(id);
                        if (!input) return;
                        var visible = input.value !== '1';
                        input.value = visible ? '1' : '0';
                        this.innerHTML = visible ? eyeSvg : eyeSlashSvg;
                    });
                }
            });
        }

        if (typeof Sortable !== 'undefined') {
            new Sortable(container, {
                handle: '.features-item-drag-handle',
                animation: 150,
                ghostClass: 'opacity-50',
                onEnd: reindexFeaturesItems
            });
        }
    })();

    // Cards ronde hoeken: card toevoegen/verwijderen en hero-upload voor nieuwe rijen
    (function() {
        var heroImageUploadUrl = {!! json_encode(route('admin.website-pages.upload-hero-image')) !!};
        function bindOneHeroUpload(area) {
            if (!area) return;
            var sectionKey = area.getAttribute('data-section-key');
            var field = area.getAttribute('data-field');
            if (!sectionKey || !field) return;
            var cardRow = area.closest ? area.closest('.cards-ronde-hoeken-item') : null;
            var scope = cardRow || document;
            var fileInput = scope.querySelector('.hero-image-file-input[data-section-key="' + sectionKey + '"][data-field="' + field + '"]');
            var urlInput = scope.querySelector('[id="hero-' + sectionKey + '-' + field + '"]') || document.getElementById('hero-' + sectionKey + '-' + field);
            var previewId = field === 'background_image_url' ? 'hero-' + sectionKey + '-bg-preview' : (field === 'author_image_url' ? 'hero-' + sectionKey + '-author-preview' : 'hero-' + sectionKey + '-' + field + '-preview');
            var preview = (scope.querySelector && scope.querySelector('[id="' + previewId + '"]')) || document.getElementById(previewId);
            if (!fileInput || !urlInput) return;
            function handleFile(file) {
                if (!file) return;
                var allowed = ['image/jpeg','image/png','image/jpg','image/gif','image/webp'];
                if (!allowed.includes(file.type)) { alert('Alleen JPEG, PNG, JPG, GIF en WebP zijn toegestaan.'); fileInput.value = ''; return; }
                if (file.size > 5 * 1024 * 1024) { alert('Max. 5MB.'); fileInput.value = ''; return; }
                var fd = new FormData();
                fd.append('image', file);
                fd.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                fetch(heroImageUploadUrl, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                    .then(function(r) { return r.ok ? r.json() : r.json().then(function(d) { throw new Error(d.message || 'Upload mislukt'); }); })
                    .then(function(d) { if (d.success && d.url) { urlInput.value = d.url; if (preview) { preview.src = d.url; preview.classList.remove('hidden'); } } })
                    .catch(function(err) { alert(err.message || 'Upload mislukt'); });
                fileInput.value = '';
            }
            area.addEventListener('click', function(e) { e.preventDefault(); fileInput.click(); });
            area.addEventListener('dragover', function(e) { e.preventDefault(); e.stopPropagation(); area.classList.add('border-primary'); });
            area.addEventListener('dragleave', function(e) { e.preventDefault(); area.classList.remove('border-primary'); });
            area.addEventListener('drop', function(e) { e.preventDefault(); area.classList.remove('border-primary'); if (e.dataTransfer.files.length) handleFile(e.dataTransfer.files[0]); });
            fileInput.addEventListener('change', function() { if (this.files && this.files.length) handleFile(this.files[0]); });
        }
        document.addEventListener('click', function(e) {
            var addBtn = e.target.closest('.cards-ronde-hoeken-item-add');
            if (addBtn) {
                e.preventDefault();
                var sectionKey = addBtn.getAttribute('data-section-key');
                var container = document.getElementById('cards-ronde-hoeken-items-' + sectionKey);
                if (!container) return;
                var items = container.querySelectorAll('.cards-ronde-hoeken-item');
                var nextIndex = items.length;
                var trashSvg = '<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>';
                var eyeSvg = '<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>';
                var eyeSlashSvg = '<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>';
                var div = document.createElement('div');
                div.className = 'cards-ronde-hoeken-item border border-border rounded-lg p-4 space-y-3';
                div.setAttribute('data-cards-index', nextIndex);
                div.innerHTML = '<div class="flex items-center justify-between gap-2"><span class="text-sm font-medium">Kaart ' + (nextIndex + 1) + '</span><button type="button" class="cards-ronde-hoeken-item-remove kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive" title="Kaart verwijderen" aria-label="Verwijderen">' + trashSvg + '</button></div>' +
                    '<div class="flex flex-wrap items-start gap-2"><div class="relative shrink-0"><img alt="Kaart ' + (nextIndex + 1) + '" id="hero-' + sectionKey + '-items_' + nextIndex + '_image_url-preview" class="w-full max-w-[200px] max-h-24 object-cover border border-border rounded hidden" src=""><button type="button" class="image-remove-btn kt-btn kt-btn-xs kt-btn-ghost text-destructive absolute top-1 right-1 shadow hover:bg-destructive/10" data-url-input-id="hero-' + sectionKey + '-items_' + nextIndex + '_image_url" data-preview-id="hero-' + sectionKey + '-items_' + nextIndex + '_image_url-preview" title="Afbeelding verwijderen" aria-label="Afbeelding verwijderen">' + trashSvg + '</button></div>' +
                    '<div class="hero-image-upload-area flex flex-col items-center justify-center p-5 lg:p-7 border border-input rounded-xl border-dashed bg-muted/30" data-section-key="' + sectionKey + '" data-field="items_' + nextIndex + '_image_url" style="width: 500px; min-width: 500px; height: 130px;"><span class="text-xs text-muted-foreground">Klik of sleep afbeelding</span><span class="text-xs text-muted-foreground">JPG, PNG, WebP (max. 5MB)</span></div></div>' +
                    '<input type="file" class="hero-image-file-input hidden" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" data-section-key="' + sectionKey + '" data-field="items_' + nextIndex + '_image_url">' +
                    '<input type="hidden" name="home_sections[' + sectionKey + '][items][' + nextIndex + '][image_url]" id="hero-' + sectionKey + '-items_' + nextIndex + '_image_url" value="">' +
                    '<div class="row-visibility-row mt-2"><div class="flex flex-wrap items-center gap-4 mb-1 pt-5"><label class="text-sm font-medium text-secondary-foreground shrink-0">Tekst onder afbeelding</label><input type="hidden" name="home_sections[visibility][' + sectionKey + '_item_' + nextIndex + ']" id="visibility-' + sectionKey + '_item_' + nextIndex + '" value="1"><button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" data-target="visibility-' + sectionKey + '_item_' + nextIndex + '" aria-label="Tekst tonen/verbergen">' + eyeSvg + '</button></div>' +
                    '<div class="flex flex-wrap items-center gap-3 mb-2"><label class="text-sm text-muted-foreground shrink-0">Kaartgrootte</label><select name="home_sections[' + sectionKey + '][items][' + nextIndex + '][card_size]" class="kt-input w-36 text-sm"><option value="small">Klein (300px)</option><option value="normal" selected>Normaal (400px)</option><option value="large">Groot (500px)</option><option value="max">Maximaal (volledige breedte)</option></select><label class="text-sm text-muted-foreground shrink-0">Lettergrootte</label><select name="home_sections[' + sectionKey + '][items][' + nextIndex + '][font_size]" class="kt-input w-20 text-sm">' + (function(){ var o = []; for (var px = 10; px <= 24; px += 2) o.push('<option value="' + px + '"' + (px === 14 ? ' selected' : '') + '>' + px + 'px</option>'); return o.join(''); })() + '</select><label class="text-sm text-muted-foreground shrink-0">Stijl</label><select name="home_sections[' + sectionKey + '][items][' + nextIndex + '][font_style]" class="kt-input w-28 text-sm"><option value="normal" selected>Normaal</option><option value="bold">Vet</option><option value="italic">Cursief</option></select><label class="text-sm text-muted-foreground shrink-0">Uitlijning</label><select name="home_sections[' + sectionKey + '][items][' + nextIndex + '][text_align]" class="kt-input w-28 text-sm"><option value="left" selected>Links</option><option value="center">Midden</option><option value="right">Rechts</option></select></div>' +
                    '<textarea name="home_sections[' + sectionKey + '][items][' + nextIndex + '][text]" id="home-cards-' + sectionKey + '-item-' + nextIndex + '-text" class="kt-input w-full pt-1 home-section-tinymce" rows="6" placeholder="Tekst onder de afbeelding (rich text)"></textarea></div>';
                container.appendChild(div);
                var textareaId = 'home-cards-' + sectionKey + '-item-' + nextIndex + '-text';
                if (typeof window.initHomeSectionTinymce === 'function') window.initHomeSectionTinymce(textareaId, 200);
                var newArea = div.querySelector('.hero-image-upload-area');
                if (newArea && heroImageUploadUrl) bindOneHeroUpload(newArea);
                var newToggle = div.querySelector('.section-visibility-toggle');
                if (newToggle) {
                    newToggle.addEventListener('click', function() {
                        var id = this.getAttribute('data-target');
                        var input = document.getElementById(id);
                        if (!input) return;
                        var visible = input.value !== '1';
                        input.value = visible ? '1' : '0';
                        this.innerHTML = visible ? eyeSvg : eyeSlashSvg;
                    });
                }
                return;
            }
            var removeBtn = e.target.closest('.cards-ronde-hoeken-item-remove');
            if (removeBtn) {
                e.preventDefault();
                var row = removeBtn.closest('.cards-ronde-hoeken-item');
                var container = row && row.parentElement;
                if (row && container && container.querySelectorAll('.cards-ronde-hoeken-item').length > 1) {
                    row.remove();
                }
            }
        });
    })();

    // Header: + dropdown om sectie toe te voegen (kloneren van bestaand type)
    (function() {
        var addBtn = document.getElementById('home-sections-add-btn');
        var menu = document.getElementById('home-sections-add-menu');
        var orderInput = document.getElementById('home-sections-order-input');
        var sortableContainer = document.getElementById('home-sections-sortable');
        if (!addBtn || !menu || !orderInput || !sortableContainer) return;

        addBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            var isOpen = !menu.classList.contains('hidden');
            menu.classList.toggle('hidden', isOpen);
            addBtn.setAttribute('aria-expanded', !isOpen ? 'true' : 'false');
        });
        document.addEventListener('click', function() {
            menu.classList.add('hidden');
            addBtn.setAttribute('aria-expanded', 'false');
        });
        menu.addEventListener('click', function(e) { e.stopPropagation(); });

        var metaEl = document.getElementById('home-sections-meta');
        var sectionCardUrl = metaEl ? (metaEl.getAttribute('data-section-card-url') || '') : '';
        var themeSlug = metaEl ? (metaEl.getAttribute('data-theme-slug') || 'modern') : 'modern';
        var sectionLabels = {};
        try {
            var raw = metaEl ? metaEl.getAttribute('data-section-labels') : '';
            if (raw) sectionLabels = JSON.parse(raw);
        } catch (e) {}
        var headerClassByType = { hero: 'hero', stats: 'stats', why_nexa: 'why', features: 'features', cta: 'cta' };

        function addSectionCard(baseType, newKey, sourceCard, done) {
            var clone = sourceCard.cloneNode(true);
            clone.classList.remove('home-section-card--collapsed');
            clone.setAttribute('data-section', newKey);
            var walk = function(el, fn) {
                fn(el);
                var i = 0, ch = el.children;
                while (i < ch.length) walk(ch[i++], fn);
            };
            walk(clone, function(el) {
                if (el.name && el.name.indexOf('home_sections[') === 0) {
                    el.name = el.name.replace('home_sections[' + baseType + ']', 'home_sections[' + newKey + ']');
                    el.name = el.name.replace('home_sections[visibility][' + baseType, 'home_sections[visibility][' + newKey);
                }
                if (el.id && el.id.indexOf(baseType) !== -1) {
                    el.id = el.id.replace(baseType, newKey);
                }
                if (el.getAttribute && el.getAttribute('data-target')) {
                    var t = el.getAttribute('data-target');
                    if (t.indexOf('visibility-' + baseType) === 0) el.setAttribute('data-target', 'visibility-' + newKey + t.slice(('visibility-' + baseType).length));
                }
                if (el.getAttribute && el.getAttribute('data-section-key') === baseType) {
                    el.setAttribute('data-section-key', newKey);
                }
                if (el.tagName === 'TEXTAREA') { el.value = ''; el.style.display = ''; }
                if (el.tagName === 'INPUT' && el.type !== 'hidden' && el.type !== 'submit' && el.type !== 'button') el.value = '';
            });
            clone.querySelectorAll('.tox-tinymce').forEach(function(tox) { tox.remove(); });
            clone.querySelectorAll('textarea').forEach(function(ta) { ta.style.display = ''; ta.style.visibility = ''; });
            var body = clone.querySelector('.home-section-card-body');
            if (body) body.style.display = '';
            var titleEl = clone.querySelector('.kt-card-header .kt-card-title');
            if (titleEl && !titleEl.classList.contains('component-card-title')) {
                var label = sectionLabels[baseType] || baseType;
                titleEl.textContent = label + (newKey !== baseType ? ' – ' + newKey : '');
            }
            var headerEl = clone.querySelector('.kt-card-header.home-section-header');
            if (headerEl) {
                ['hero', 'stats', 'why', 'why_nexa', 'features', 'cta'].forEach(function(c) {
                    headerEl.classList.remove('home-section-header--' + c);
                });
                var headerMod = headerClassByType[baseType] || baseType;
                headerEl.classList.add('home-section-header--' + headerMod);
            }
            sortableContainer.appendChild(clone);
            if (typeof window.bindHeroUploadAreasIn === 'function') window.bindHeroUploadAreasIn(clone);
            var textareasToInit = [];
            clone.querySelectorAll('textarea').forEach(function(ta) {
                if (!ta.classList.contains('home-section-tinymce')) ta.classList.add('home-section-tinymce', 'kt-input', 'w-full', 'pt-1');
                ta.rows = 10;
                if (ta.id) textareasToInit.push(ta.id);
            });
            if (textareasToInit.length && typeof window.initHomeSectionTinymce === 'function') {
                setTimeout(function() {
                    textareasToInit.forEach(function(id) {
                        var el = document.getElementById(id);
                        if (el && el.tagName === 'TEXTAREA' && !el.closest('.tox-tinymce')) {
                            window.initHomeSectionTinymce(id, 260);
                        }
                    });
                }, 200);
            }
            var order = (orderInput.value || '').split(',').map(function(s) { return s.trim(); }).filter(Boolean);
            order.push(newKey);
            orderInput.value = order.join(',');
            menu.classList.add('hidden');
            addBtn.setAttribute('aria-expanded', 'false');
            if (typeof done === 'function') done();
        }

        document.querySelectorAll('.home-sections-add-type').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var baseType = this.getAttribute('data-type');
                var existing = sortableContainer.querySelectorAll('[data-section^="' + baseType + '"]');
                var nextNum = existing.length;
                var newKey = nextNum === 0 ? baseType : (nextNum === 1 ? baseType + '_2' : baseType + '_' + (nextNum + 1));
                if (!sectionCardUrl) return;
                var url = sectionCardUrl + '?type=' + encodeURIComponent(baseType) + '&theme=' + encodeURIComponent(themeSlug);
                var btnEl = this;
                btnEl.disabled = true;
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' } })
                    .then(function(r) { return r.text(); })
                    .then(function(html) {
                        var wrap = document.createElement('div');
                        wrap.innerHTML = html.trim();
                        var card = wrap.querySelector('.kt-card.home-section-card');
                        if (!card) {
                            btnEl.disabled = false;
                            return;
                        }
                        addSectionCard(baseType, newKey, card, function() { btnEl.disabled = false; });
                    })
                    .catch(function() { btnEl.disabled = false; });
            });
        });

        menu.addEventListener('click', function(e) {
            var el = e.target;
            var btn = null;
            while (el && el !== menu) {
                if (el.classList && el.classList.contains('home-sections-add-component')) {
                    btn = el;
                    break;
                }
                el = el.parentElement;
            }
            if (!btn) return;
            e.preventDefault();
            e.stopPropagation();
            var sectionKey = btn.getAttribute('data-section');
            var name = btn.getAttribute('data-name') || 'Component';
            if (!sectionKey) return;
            var order = (orderInput.value || '').split(',').map(function(s) { return s.trim(); }).filter(Boolean);
            if (order.indexOf(sectionKey) !== -1) {
                var msg = document.getElementById('home-sections-component-already-msg');
                if (msg) msg.remove();
                msg = document.createElement('div');
                msg.id = 'home-sections-component-already-msg';
                msg.setAttribute('role', 'alert');
                msg.className = 'px-3 py-2 text-sm text-red-600 dark:text-red-400 bg-gray-200 dark:bg-gray-700 rounded mt-1';
                msg.textContent = '\u2018' + name + '\u2019 staat al op de pagina. Verwijder het eerst om het opnieuw toe te voegen.';
                menu.appendChild(msg);
                setTimeout(function() { if (msg.parentNode) msg.remove(); }, 4000);
                return;
            }
            var template = document.getElementById('home-section-component-card-template');
            var existingCard = sortableContainer.querySelector('.home-section-card--component[data-section="' + sectionKey + '"]');
            var card = null;
            if (existingCard) {
                card = existingCard.cloneNode(true);
            } else if (template && template.content && template.content.firstElementChild) {
                card = template.content.firstElementChild.cloneNode(true);
            }
            if (card) {
                card.setAttribute('data-section', sectionKey);
                card.classList.remove('home-section-card--collapsed');
                var titleEl = card.querySelector('.component-card-title');
                if (titleEl) titleEl.textContent = name + ' (' + (btn.getAttribute('data-module') || 'Module') + ')';
                sortableContainer.appendChild(card);
                order.push(sectionKey);
                orderInput.value = order.join(',');
            }
            menu.classList.add('hidden');
            addBtn.setAttribute('aria-expanded', 'false');
        });
    })();
</script>

@push('styles')
<style>
    /* TinyMCE: zichtbaar en klikbaar (geen overlay, geen visibility:hidden) */
    .home-section-card-body .tox,
    .home-section-card-body .tox-tinymce,
    .home-section-card-body .tox .tox-edit-area,
    .home-section-card-body .tox .tox-edit-area__iframe {
        visibility: visible !important;
        pointer-events: auto !important;
    }
    .tox-tinymce { border-radius: var(--radius, 0.375rem) !important; border-color: var(--color-input, #e5e7eb) !important; }
    .dark .tox-tinymce { border-color: var(--color-input) !important; background-color: #1f2937 !important; }
    .dark .tox .tox-edit-area__iframe { background: #1f2937 !important; }
    /* Home-sectie inklappen: body verbergen wanneer ingeklapt */
    .home-section-card--collapsed .home-section-card-body { display: none !important; }
    /* Home-sectie kopjes: duidelijke kleur per component */
    .home-section-header { border-left-width: 4px; border-radius: var(--radius, 0.375rem) var(--radius, 0.375rem) 0 0; }
    .home-section-header--hero { background-color: rgb(239 246 255); border-left-color: rgb(59 130 246); }
    .dark .home-section-header--hero { background-color: rgb(30 58 138 / 0.35); border-left-color: rgb(96 165 250); }
    .home-section-header--hero .kt-card-title { color: rgb(30 64 175); }
    .dark .home-section-header--hero .kt-card-title { color: rgb(191 219 254); }
    .home-section-header--stats { background-color: rgb(240 253 244); border-left-color: rgb(34 197 94); }
    .dark .home-section-header--stats { background-color: rgb(20 83 45 / 0.35); border-left-color: rgb(74 222 128); }
    .home-section-header--stats .kt-card-title { color: rgb(21 128 61); }
    .dark .home-section-header--stats .kt-card-title { color: rgb(134 239 172); }
    .home-section-header--why { background-color: rgb(238 242 255); border-left-color: rgb(99 102 241); }
    .dark .home-section-header--why { background-color: rgb(49 46 129 / 0.35); border-left-color: rgb(129 140 248); }
    .home-section-header--why .kt-card-title { color: rgb(67 56 202); }
    .dark .home-section-header--why .kt-card-title { color: rgb(165 180 252); }
    .home-section-header--features { background-color: rgb(255 251 235); border-left-color: rgb(245 158 11); }
    .dark .home-section-header--features { background-color: rgb(120 53 15 / 0.35); border-left-color: rgb(251 191 36); }
    .home-section-header--features .kt-card-title { color: rgb(161 98 7); }
    .dark .home-section-header--features .kt-card-title { color: rgb(253 224 71); }
    .home-section-header--cta { background-color: rgb(254 226 226); border-left-color: rgb(239 68 68); }
    .dark .home-section-header--cta { background-color: rgb(127 29 29 / 0.35); border-left-color: rgb(248 113 113); }
    .home-section-header--cta .kt-card-title { color: rgb(185 28 28); }
    .dark .home-section-header--cta .kt-card-title { color: rgb(252 165 165); }
    .home-section-header--footer { background-color: rgb(241 245 249); border-left-color: rgb(100 116 139); }
    .dark .home-section-header--footer { background-color: rgb(30 41 59 / 0.5); border-left-color: rgb(148 163 184); }
    .home-section-header--footer .kt-card-title { color: rgb(51 65 85); }
    .dark .home-section-header--footer .kt-card-title { color: rgb(203 213 225); }
    .home-section-header--copyright { background-color: rgb(248 250 252); border-left-color: rgb(71 85 105); }
    .dark .home-section-header--copyright { background-color: rgb(30 41 59 / 0.4); border-left-color: rgb(100 116 139); }
    .home-section-header--copyright .kt-card-title { color: rgb(71 85 105); }
    .dark .home-section-header--copyright .kt-card-title { color: rgb(148 163 184); }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
<script>
(function() {
    function getHomeSectionTinymceConfig(height) {
        var isDark = document.documentElement.classList.contains('dark');
        var contentStyle = 'body { font-family: Inter, ui-sans-serif, system-ui, sans-serif; font-size: 15px; line-height: 1.6; padding: 12px; } p { margin: 0 0 0.5em 0; } ul, ol { margin: 0 0 0.5em 0; padding-left: 1.5em; } a { color: #2563eb; }';
        if (isDark) {
            contentStyle = 'body { font-family: Inter, ui-sans-serif, system-ui, sans-serif; font-size: 15px; line-height: 1.6; padding: 12px; background: #1f2937; color: #f3f4f6; } p { margin: 0 0 0.5em 0; } ul, ol { margin: 0 0 0.5em 0; padding-left: 1.5em; } a { color: #93c5fd; } img { max-width: 100%; height: auto; }';
        }
        return {
            base_url: 'https://cdn.jsdelivr.net/npm/tinymce@6.8.2',
            suffix: '.min',
            height: height || 260,
            menubar: false,
            plugins: 'lists link image',
            toolbar: 'undo redo | bold italic underline strikethrough | bullist numlist | link image | removeformat',
            skin: isDark ? 'oxide-dark' : 'oxide',
            content_css: isDark ? 'dark' : 'default',
            content_style: contentStyle,
            branding: false,
            promotion: false,
            resize: true,
            image_title: true,
            automatic_uploads: false,
            images_upload_url: '',
            file_picker_types: 'image',
            setup: function(editor) {
                editor.on('change keyup', function() { editor.save(); });
            },
            init_instance_callback: function(editor) {
                var container = editor.getContainer();
                if (container) {
                    if (container.style) {
                        container.style.visibility = 'visible';
                        container.style.removeProperty('visibility');
                        container.style.setProperty('pointer-events', 'auto', 'important');
                    }
                    var editArea = container.querySelector('.tox-edit-area');
                    if (editArea) {
                        editArea.style.setProperty('pointer-events', 'auto', 'important');
                        var iframe = editArea.querySelector('iframe');
                        if (iframe) {
                            iframe.style.setProperty('pointer-events', 'auto', 'important');
                            iframe.setAttribute('tabindex', '0');
                        }
                    }
                    container.addEventListener('mousedown', function(e) {
                        if (container.contains(e.target)) {
                            var ed = editor;
                            setTimeout(function() { try { ed.focus(); var b = ed.getBody(); if (b && b.focus) b.focus(); } catch (err) {} }, 0);
                        }
                    });
                }
            }
        };
    }
    window.getHomeSectionTinymceConfig = getHomeSectionTinymceConfig;
    window.initHomeSectionTinymce = function(idOrSelector, height) {
        if (typeof tinymce === 'undefined') return;
        var sel = (typeof idOrSelector === 'string' && idOrSelector.indexOf('#') !== 0) ? '#' + idOrSelector : idOrSelector;
        var config = getHomeSectionTinymceConfig(height || 260);
        config.selector = sel;
        tinymce.init(config);
    };
    function initAllHomeSectionTinymce() {
        var selector = '.home-section-tinymce';
        if (typeof tinymce === 'undefined' || !document.querySelector(selector)) return;
        var config = getHomeSectionTinymceConfig(260);
        config.selector = selector;
        tinymce.init(config);
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllHomeSectionTinymce);
    } else {
        initAllHomeSectionTinymce();
    }
    // Sync TinyMCE naar textareas vóór form submit (zodat cards/features-tekst wordt opgeslagen)
    var form = document.getElementById('website-page-form');
    if (form) {
        form.addEventListener('submit', function() {
            if (typeof tinymce !== 'undefined' && tinymce.editors) {
                tinymce.triggerSave();
            }
        }, true);
    }
})();
</script>
@endpush
