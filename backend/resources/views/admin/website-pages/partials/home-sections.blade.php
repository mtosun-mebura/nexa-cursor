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
            'features' => 'Kenmerken',
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
            <div class="row-visibility-row flex flex-col gap-3">
                <div class="w-full">
                    <div class="flex items-center gap-2 mb-1">
                        <label class="text-sm font-medium text-secondary-foreground">Titel</label>
                        <input type="hidden" name="home_sections[visibility][{{ $sectionKey }}_title]" id="visibility-{{ $sectionKey }}_title" value="{{ $vis('_title') ? '1' : '0' }}">
                        <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" data-target="visibility-{{ $sectionKey }}_title" title="Zichtbaar op website" aria-label="Titel tonen/verbergen">@if($vis('_title'))<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                    </div>
                    <input type="text" name="home_sections[{{ $sectionKey }}][title]" class="kt-input w-full max-w-4xl" value="{{ old('home_sections.'.$sectionKey.'.title', $sectionData['title'] ?? 'Vind je droombaan met AI') }}" placeholder="Vind je droombaan met AI">
                </div>
                <div class="w-full">
                    <label class="block text-sm font-medium text-secondary-foreground mb-1">Woord benadrukt (oranje)</label>
                    <input type="text" name="home_sections[{{ $sectionKey }}][title_highlight]" class="kt-input w-full max-w-xs" value="{{ old('home_sections.'.$sectionKey.'.title_highlight', $sectionData['title_highlight'] ?? 'droombaan') }}" placeholder="droombaan">
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
                    <div class="hero-image-upload-area flex flex-col items-center justify-center p-5 lg:p-7 border border-input rounded-xl border-dashed bg-muted/30 w-full min-w-0 min-h-[130px]" data-section-key="{{ $sectionKey }}" data-field="author_image_url">
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
                    <div class="hero-image-upload-area flex flex-col items-center justify-center p-5 lg:p-7 border border-input rounded-xl border-dashed bg-muted/30 flex-1 min-w-0 min-h-[130px]" data-section-key="{{ $sectionKey }}" data-field="background_image_url">
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
                        <div class="hero-image-upload-area flex flex-1 min-w-0 min-h-[130px] flex-col items-center justify-center p-5 lg:p-7 border border-input rounded-xl border-dashed bg-muted/30" data-section-key="{{ $sectionKey }}" data-field="background_image_url">
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
                        <div class="hero-image-upload-area flex flex-1 min-w-0 min-h-[130px] flex-col items-center justify-center p-5 lg:p-7 border border-input rounded-xl border-dashed bg-muted/30" data-section-key="{{ $sectionKey }}" data-field="author_image_url">
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
                                <input type="text" name="home_sections[{{ $sectionKey }}][cta_primary_bg]" class="kt-input w-24 font-mono text-sm" value="{{ old('home_sections.'.$sectionKey.'.cta_primary_bg', $sectionData['cta_primary_bg'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="{{ $sectionKey }}-cta-primary-bg">
                                <button type="button" class="hex-clear-btn kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Leegmaken" aria-label="Leegmaken" data-color-default="#ffffff"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="block text-xs font-medium text-muted-foreground w-28 shrink-0">Tekstkleur</label>
                            <div class="flex items-center gap-2">
                                <input type="color" id="{{ $sectionKey }}-cta-primary-text-color" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($sectionData['cta_primary_text_color'] ?? '') ?: '#1e3a8a' }}" title="Tekstkleur">
                                <input type="text" name="home_sections[{{ $sectionKey }}][cta_primary_text_color]" class="kt-input w-24 font-mono text-sm" value="{{ old('home_sections.'.$sectionKey.'.cta_primary_text_color', $sectionData['cta_primary_text_color'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="{{ $sectionKey }}-cta-primary-text-color">
                                <button type="button" class="hex-clear-btn kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Leegmaken" aria-label="Leegmaken" data-color-default="#1e3a8a"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="block text-xs font-medium text-muted-foreground w-28 shrink-0">Border</label>
                            <div class="flex items-center gap-2">
                                <input type="color" id="{{ $sectionKey }}-cta-primary-border" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($sectionData['cta_primary_border'] ?? '') ?: '#1e40af' }}" title="Borderkleur">
                                <input type="text" name="home_sections[{{ $sectionKey }}][cta_primary_border]" class="kt-input w-24 font-mono text-sm" value="{{ old('home_sections.'.$sectionKey.'.cta_primary_border', $sectionData['cta_primary_border'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="{{ $sectionKey }}-cta-primary-border">
                                <button type="button" class="hex-clear-btn kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Leegmaken" aria-label="Leegmaken" data-color-default="#1e40af"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-secondary-foreground">Knop 2 kleuren</label>
                    <div class="flex flex-col gap-2">
                        <div class="flex items-center gap-3">
                            <label class="block text-xs font-medium text-muted-foreground w-28 shrink-0">Achtergrond</label>
                            <div class="flex items-center gap-2">
                                <input type="color" id="{{ $sectionKey }}-cta-secondary-bg" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($sectionData['cta_secondary_bg'] ?? '') ?: '#ffffff' }}" title="Achtergrond">
                                <input type="text" name="home_sections[{{ $sectionKey }}][cta_secondary_bg]" class="kt-input w-24 font-mono text-sm" value="{{ old('home_sections.'.$sectionKey.'.cta_secondary_bg', $sectionData['cta_secondary_bg'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="{{ $sectionKey }}-cta-secondary-bg">
                                <button type="button" class="hex-clear-btn kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Leegmaken" aria-label="Leegmaken" data-color-default="#ffffff"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="block text-xs font-medium text-muted-foreground w-28 shrink-0">Tekstkleur</label>
                            <div class="flex items-center gap-2">
                                <input type="color" id="{{ $sectionKey }}-cta-secondary-text-color" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($sectionData['cta_secondary_text_color'] ?? '') ?: '#1e40af' }}" title="Tekstkleur">
                                <input type="text" name="home_sections[{{ $sectionKey }}][cta_secondary_text_color]" class="kt-input w-24 font-mono text-sm" value="{{ old('home_sections.'.$sectionKey.'.cta_secondary_text_color', $sectionData['cta_secondary_text_color'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="{{ $sectionKey }}-cta-secondary-text-color">
                                <button type="button" class="hex-clear-btn kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Leegmaken" aria-label="Leegmaken" data-color-default="#1e40af"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="block text-xs font-medium text-muted-foreground w-28 shrink-0">Border</label>
                            <div class="flex items-center gap-2">
                                <input type="color" id="{{ $sectionKey }}-cta-secondary-border" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($sectionData['cta_secondary_border'] ?? '') ?: '#1e40af' }}" title="Borderkleur">
                                <input type="text" name="home_sections[{{ $sectionKey }}][cta_secondary_border]" class="kt-input w-24 font-mono text-sm" value="{{ old('home_sections.'.$sectionKey.'.cta_secondary_border', $sectionData['cta_secondary_border'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="{{ $sectionKey }}-cta-secondary-border">
                                <button type="button" class="hex-clear-btn kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Leegmaken" aria-label="Leegmaken" data-color-default="#1e40af"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
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
                        <input type="text" name="home_sections[{{ $sectionKey }}][{{ $i }}][value]" class="kt-input home-section-input-400" value="{{ old("home_sections.{$sectionKey}.{$i}.value", ($sectionData[$i]['value'] ?? '')) }}" placeholder="10,000+">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-secondary-foreground mb-1">Label {{ $i + 1 }}</label>
                        <input type="text" name="home_sections[{{ $sectionKey }}][{{ $i }}][label]" class="kt-input home-section-input-400" value="{{ old("home_sections.{$sectionKey}.{$i}.label", ($sectionData[$i]['label'] ?? '')) }}" placeholder="Actieve vacatures">
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
                <input type="text" name="home_sections[{{ $sectionKey }}][title]" class="kt-input home-section-input-400" value="{{ old('home_sections.'.$sectionKey.'.title', $sectionData['title'] ?? 'Waarom kiezen voor Nexa?') }}">
            </div>
            <div class="row-visibility-row">
                <div class="flex items-center gap-2 mb-1">
                    <label class="text-sm font-medium text-secondary-foreground">Ondertitel</label>
                    <input type="hidden" name="home_sections[visibility][{{ $sectionKey }}_subtitle]" id="visibility-{{ $sectionKey }}_subtitle" value="{{ $vis('_subtitle') ? '1' : '0' }}">
                    <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" data-target="visibility-{{ $sectionKey }}_subtitle" aria-label="Ondertitel tonen/verbergen">@if($vis('_subtitle'))<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                </div>
                @include('admin.website-pages.partials.flowbite-wysiwyg', ['editorId' => 'hero-' . $sectionKey . '-subtitle', 'name' => 'home_sections['.$sectionKey.'][subtitle]', 'value' => old('home_sections.'.$sectionKey.'.subtitle', $sectionData['subtitle'] ?? ''), 'placeholder' => 'Ondertitel...', 'textareaId' => 'home-'.$sectionKey.'-subtitle'])
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
                    <div class="shrink-0 flex flex-col items-center">
                        <img alt="Kenmerken illustratie" id="hero-features-author-preview" class="w-full max-w-[200px] max-h-40 object-contain border border-border rounded-lg {{ $featuresPreviewSrc ? '' : 'hidden' }}" src="{{ $imagePreviewUrl($featuresPreviewSrc) }}" data-default-src="{{ $defaultFeaturesImg ?? '' }}">
                        <button type="button" class="image-remove-btn kt-btn kt-btn-xs kt-btn-ghost text-destructive mt-1 shadow hover:bg-destructive/10" data-url-input-id="hero-features-illustration_url" data-preview-id="hero-features-author-preview" title="Afbeelding verwijderen" aria-label="Afbeelding verwijderen"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
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
                <input type="text" name="home_sections[features][section_title]" class="kt-input home-section-input-400" value="{{ old('home_sections.features.section_title', $features['section_title'] ?? 'Kenmerken') }}">
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
                        <input type="text" name="home_sections[features][items][{{ $i }}][title]" class="kt-input home-section-input-400 features-item-title" value="{{ old("home_sections.features.items.{$i}.title", $item['title'] ?? '') }}">
                    </div>
                    <div>
                        <label class="block text-xs text-muted-foreground mb-1">Beschrijving</label>
                        @include('admin.website-pages.partials.flowbite-wysiwyg', ['editorId' => 'home-features-item-'.$i.'-description', 'name' => 'home_sections[features][items]['.$i.'][description]', 'value' => old("home_sections.features.items.{$i}.description", $item['description'] ?? ''), 'placeholder' => '', 'textareaId' => 'home-features-item-'.$i.'-description'])
                    </div>
                    <div class="flex flex-col gap-2">
                        <div class="flex items-center gap-3">
                            <label class="block text-xs font-medium text-muted-foreground w-40 shrink-0">Icoon (Heroicon)</label>
                            <select name="home_sections[features][items][{{ $i }}][icon]" class="kt-input flex-1 min-w-0 features-item-icon">
                                @foreach($heroiconList as $iconId => $iconData)
                                <option value="{{ $iconId }}" {{ ($item['icon'] ?? ($i === 0 ? 'light-bulb' : 'bolt')) === $iconId ? 'selected' : '' }}>{{ $iconData['label'] ?? $iconId }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="block text-xs font-medium text-muted-foreground w-40 shrink-0">Grootte icoon</label>
                            <select name="home_sections[features][items][{{ $i }}][icon_size]" class="kt-input flex-1 min-w-0 features-item-icon-size">
                                @foreach($heroiconSizes as $sizeId => $sizeData)
                                <option value="{{ $sizeId }}" {{ ($item['icon_size'] ?? 'medium') === $sizeId ? 'selected' : '' }}>{{ $sizeData['label'] ?? $sizeId }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="block text-xs font-medium text-muted-foreground w-40 shrink-0">Positie titel en icoon</label>
                            <select name="home_sections[features][items][{{ $i }}][icon_align]" class="kt-input flex-1 min-w-0 features-item-icon-align">
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
        $cardsFontStyles = ['normal' => 'Normaal', 'bold' => 'Vet', 'italic' => 'Cursief'];
        $cardsCardSizes = ['small' => 'Klein (300px)', 'normal' => 'Normaal (400px)', 'large' => 'Groot (500px)', 'max' => 'Maximaal (volledige breedte)', 'total_width' => 'Totaalformaat cards'];
        $cardsTextAligns = ['left' => 'Links', 'center' => 'Midden', 'right' => 'Rechts'];
        $cardsImagePaddings = [0 => '0px'] + array_combine($a = range(2, 30, 2), array_map(fn($v) => $v . 'px', $a));
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
            <div class="flex flex-wrap items-center gap-4">
                <p class="text-sm text-muted-foreground">Kaarten met afbeelding en tekst eronder. Tekst per kaart kan met het oogje uitgeschakeld worden.</p>
                <div class="flex items-center gap-2">
                    <label class="text-sm text-muted-foreground shrink-0">Kaarten per regel:</label>
                    <select name="home_sections[{{ $sectionKey }}][cards_per_row]" class="kt-input w-20 text-sm">
                        @foreach([2 => '2', 3 => '3', 4 => '4', 5 => '5', 6 => '6'] as $val => $label)
                        <option value="{{ $val }}" {{ (int)($sectionData['cards_per_row'] ?? 4) === (int)$val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div id="cards-ronde-hoeken-items-{{ $sectionKey }}" class="space-y-4" data-section-key="{{ $sectionKey }}">
                @foreach($cardsItems as $i => $cardItem)
                <div class="cards-ronde-hoeken-item border border-border rounded-lg p-4 space-y-3" data-cards-index="{{ $i }}">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-sm font-medium">Kaart {{ $i + 1 }}</span>
                        <button type="button" class="cards-ronde-hoeken-item-remove kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive" title="Kaart verwijderen" aria-label="Verwijderen"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                    </div>
                    <div class="flex flex-wrap items-start gap-2">
                        <div class="shrink-0 flex flex-col items-center">
                            <img alt="Kaart {{ $i + 1 }}" id="hero-{{ $sectionKey }}-items_{{ $i }}_image_url-preview" class="w-full max-w-[200px] max-h-24 object-cover border border-border rounded {{ !empty($cardItem['image_url']) ? '' : 'hidden' }}" src="{{ $imagePreviewUrl($cardItem['image_url'] ?? '') }}">
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
                                    <input type="text" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][image_bg_color]" id="cards-{{ $sectionKey }}-item-{{ $i }}-image-bg-hex" class="kt-input w-24 font-mono text-sm" value="{{ old('home_sections.'.$sectionKey.'.items.'.$i.'.image_bg_color', $cardItem['image_bg_color'] ?? '') }}" placeholder="#hex of leeg" maxlength="7" data-sync-from="cards-{{ $sectionKey }}-item-{{ $i }}-image-bg">
                                    <button type="button" class="hex-clear-btn kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Leegmaken" aria-label="Leegmaken" data-color-default="#e5e7eb"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="text-sm text-muted-foreground shrink-0 w-40">Tekstkleur</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" id="cards-{{ $sectionKey }}-item-{{ $i }}-text-color" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($cardItem['text_color'] ?? '') ?: '#374151' }}" title="Tekstkleur">
                                    <input type="text" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][text_color]" id="cards-{{ $sectionKey }}-item-{{ $i }}-text-color-hex" class="kt-input w-24 font-mono text-sm" value="{{ old('home_sections.'.$sectionKey.'.items.'.$i.'.text_color', $cardItem['text_color'] ?? '') }}" placeholder="#hex of leeg" maxlength="7" data-sync-from="cards-{{ $sectionKey }}-item-{{ $i }}-text-color">
                                    <button type="button" class="hex-clear-btn kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Leegmaken" aria-label="Leegmaken" data-color-default="#374151"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
                                </div>
                            </div>
                        </div>
                        <div class="w-full min-w-0">
                            @include('admin.website-pages.partials.flowbite-wysiwyg', ['editorId' => 'home-cards-'.$sectionKey.'-item-'.$i.'-text', 'name' => 'home_sections['.$sectionKey.'][items]['.$i.'][text]', 'value' => old('home_sections.'.$sectionKey.'.items.'.$i.'.text', $cardItem['text'] ?? ''), 'placeholder' => 'Tekst onder de afbeelding (rich text)', 'textareaId' => 'home-cards-'.$sectionKey.'-item-'.$i.'-text'])
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
                <div class="flex items-center gap-2 mb-1">
                    <label class="text-sm font-medium text-secondary-foreground">Titel</label>
                <input type="hidden" name="home_sections[visibility][{{ $sectionKey }}_title]" id="visibility-{{ $sectionKey }}_title" value="{{ $vis('_title') ? '1' : '0' }}">
                <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" data-target="visibility-{{ $sectionKey }}_title" aria-label="Titel tonen/verbergen">@if($vis('_title'))<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                </div>
                <input type="text" name="home_sections[{{ $sectionKey }}][title]" class="kt-input home-section-input-400" value="{{ old('home_sections.'.$sectionKey.'.title', $sectionData['title'] ?? 'Klaar om je carrière te starten?') }}">
            </div>
            <div class="row-visibility-row">
                <div class="flex items-center gap-2 mb-1">
                    <label class="text-sm font-medium text-secondary-foreground">Ondertitel</label>
                <input type="hidden" name="home_sections[visibility][{{ $sectionKey }}_subtitle]" id="visibility-{{ $sectionKey }}_subtitle" value="{{ $vis('_subtitle') ? '1' : '0' }}">
                <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" data-target="visibility-{{ $sectionKey }}_subtitle" aria-label="Ondertitel tonen/verbergen">@if($vis('_subtitle'))<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                </div>
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
                                    <input type="text" name="home_sections[{{ $sectionKey }}][cta_primary_bg]" class="kt-input w-24 font-mono text-sm" value="{{ old('home_sections.'.$sectionKey.'.cta_primary_bg', $sectionData['cta_primary_bg'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="{{ $sectionKey }}-cta-primary-bg">
                                    <button type="button" class="hex-clear-btn kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Leegmaken" aria-label="Leegmaken" data-color-default="#2563eb"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="block text-xs font-medium text-muted-foreground w-28 shrink-0">Tekstkleur</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" id="{{ $sectionKey }}-cta-primary-text-color" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($sectionData['cta_primary_text_color'] ?? '') ?: '#ffffff' }}" title="Tekstkleur">
                                    <input type="text" name="home_sections[{{ $sectionKey }}][cta_primary_text_color]" class="kt-input w-24 font-mono text-sm" value="{{ old('home_sections.'.$sectionKey.'.cta_primary_text_color', $sectionData['cta_primary_text_color'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="{{ $sectionKey }}-cta-primary-text-color">
                                    <button type="button" class="hex-clear-btn kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Leegmaken" aria-label="Leegmaken" data-color-default="#ffffff"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="block text-xs font-medium text-muted-foreground w-28 shrink-0">Border</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" id="{{ $sectionKey }}-cta-primary-border" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($sectionData['cta_primary_border'] ?? '') ?: '#ffffff' }}" title="Borderkleur">
                                    <input type="text" name="home_sections[{{ $sectionKey }}][cta_primary_border]" class="kt-input w-24 font-mono text-sm" value="{{ old('home_sections.'.$sectionKey.'.cta_primary_border', $sectionData['cta_primary_border'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="{{ $sectionKey }}-cta-primary-border">
                                    <button type="button" class="hex-clear-btn kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Leegmaken" aria-label="Leegmaken" data-color-default="#ffffff"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-secondary-foreground">Knop 2 kleuren</label>
                        <div class="flex flex-col gap-2">
                            <div class="flex items-center gap-3">
                                <label class="block text-xs font-medium text-muted-foreground w-28 shrink-0">Achtergrond</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" id="{{ $sectionKey }}-cta-secondary-bg" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($sectionData['cta_secondary_bg'] ?? '') ?: '#ffffff' }}" title="Achtergrond">
                                    <input type="text" name="home_sections[{{ $sectionKey }}][cta_secondary_bg]" class="kt-input w-24 font-mono text-sm" value="{{ old('home_sections.'.$sectionKey.'.cta_secondary_bg', $sectionData['cta_secondary_bg'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="{{ $sectionKey }}-cta-secondary-bg">
                                    <button type="button" class="hex-clear-btn kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Leegmaken" aria-label="Leegmaken" data-color-default="#ffffff"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="block text-xs font-medium text-muted-foreground w-28 shrink-0">Tekstkleur</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" id="{{ $sectionKey }}-cta-secondary-text-color" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($sectionData['cta_secondary_text_color'] ?? '') ?: '#ffffff' }}" title="Tekstkleur">
                                    <input type="text" name="home_sections[{{ $sectionKey }}][cta_secondary_text_color]" class="kt-input w-24 font-mono text-sm" value="{{ old('home_sections.'.$sectionKey.'.cta_secondary_text_color', $sectionData['cta_secondary_text_color'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="{{ $sectionKey }}-cta-secondary-text-color">
                                    <button type="button" class="hex-clear-btn kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Leegmaken" aria-label="Leegmaken" data-color-default="#ffffff"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="block text-xs font-medium text-muted-foreground w-28 shrink-0">Border</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" id="{{ $sectionKey }}-cta-secondary-border" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($sectionData['cta_secondary_border'] ?? '') ?: '#1f2937' }}" title="Borderkleur">
                                    <input type="text" name="home_sections[{{ $sectionKey }}][cta_secondary_border]" class="kt-input w-24 font-mono text-sm" value="{{ old('home_sections.'.$sectionKey.'.cta_secondary_border', $sectionData['cta_secondary_border'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="{{ $sectionKey }}-cta-secondary-border">
                                    <button type="button" class="hex-clear-btn kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Leegmaken" aria-label="Leegmaken" data-color-default="#1f2937"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
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
                    <div class="carousel-slide-row flex items-center gap-2 rounded border border-border p-2" data-uuid="{{ $uuid }}">
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
    @elseif($sectionKey === 'component:taxiroyaal.tarieven')
            @php
                $tarievenSectionData = $sections[$sectionKey] ?? [];
                $tarievenItems = array_values($tarievenSectionData['items'] ?? [['rate_type' => '1-4', 'title' => 't/m 4 personen'], ['rate_type' => '5-8', 'title' => '5 t/m 8 personen']]);
                if (empty($tarievenItems)) {
                    $tarievenItems = [['rate_type' => '1-4', 'title' => 't/m 4 personen'], ['rate_type' => '5-8', 'title' => '5 t/m 8 personen']];
                }
                $tarievenRateTypes = ['1-4' => 't/m 4 personen', '5-8' => '5 t/m 8 personen', 'overige_kosten' => 'Overige kosten'];
                $tarievenVehicles = app(\App\Services\TaxiRoyaalVehicleDisplayService::class)->getVehiclesForSelect();
                $tarievenVehiclesForJs = array_map(function($v) {
                    $url = $v['image_url'] ?? null;
                    $url = $url ? (str_starts_with($url, 'http') ? $url : asset(ltrim($url, '/'))) : null;
                    return ['id' => $v['id'], 'name' => $v['name'], 'image_url' => $url];
                }, $tarievenVehicles);
                $tarievenCardSizes = ['small' => 'Klein (400px)', 'normal' => 'Normaal (600px)', 'large' => 'Groot (800px)', 'max' => 'Maximaal (volledige breedte)', 'total_width' => 'Totaalformaat cards'];
                $tarievenFontStyles = ['normal' => 'Normaal', 'bold' => 'Vet', 'italic' => 'Cursief'];
                $tarievenFontFamilies = ['' => 'Standaard', 'sans-serif' => 'Sans-serif', 'serif' => 'Serif', 'monospace' => 'Monospace', 'Inter' => 'Inter', 'Georgia' => 'Georgia'];
                $tarievenFontSizes = ['' => 'Standaard'];
                foreach (range(10, 40, 2) as $px) { $tarievenFontSizes[$px . 'px'] = $px . 'px'; }
                $tarievenTextAligns = ['left' => 'Links', 'center' => 'Midden', 'right' => 'Rechts'];
                $tarievenBlockTitle = old('home_sections.'.$sectionKey.'.title', $tarievenSectionData['title'] ?? 'Onze tarieven');
                $tarievenImagePaddings = [0 => '0px'] + array_combine($a = range(2, 30, 2), array_map(fn($v) => $v . 'px', $a));
                $tarievenRatesData = app(\App\Services\TaxiRoyaalPublicRatesService::class)->getRatesForDisplay();
                $tarievenCleaning1_4 = $tarievenRatesData && $tarievenRatesData['rates_1_4'] !== null && $tarievenRatesData['rates_1_4']->cleaning_costs !== null ? (float) $tarievenRatesData['rates_1_4']->cleaning_costs : null;
                $tarievenCleaning5_8 = $tarievenRatesData && $tarievenRatesData['rates_5_8'] !== null && $tarievenRatesData['rates_5_8']->cleaning_costs !== null ? (float) $tarievenRatesData['rates_5_8']->cleaning_costs : null;
            @endphp
    <div class="kt-card home-section-card home-section-card--component home-section-card--module" data-section="{{ $sectionKey }}">
        <div class="kt-card-header home-section-header flex items-center justify-between gap-2">
            <span class="home-section-drag-handle cursor-grab active:cursor-grabbing touch-none p-1 -ml-1 rounded text-muted-foreground hover:text-foreground" title="Sleep om volgorde te wijzigen" aria-label="Volgorde wijzigen"><svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" /></svg></span>
            <h3 class="kt-card-title">Taxi Royaal tarieven (Taxi)</h3>
            <div class="flex items-center gap-1 shrink-0">
                <button type="button" class="home-section-collapse-toggle kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground" title="Inklappen" aria-label="Sectie inklappen of uitklappen"><svg class="w-5 h-5 text-current home-section-collapse-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg></button>
                <button type="button" class="home-section-component-remove kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-destructive" title="Component van pagina verwijderen" aria-label="Component verwijderen"><svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
            </div>
        </div>
        <div class="home-section-card-body kt-card-table p-4 space-y-4">
            <p class="text-sm text-muted-foreground">Per kaart: tarief, titel, afbeelding (voertuig of eigen upload), kaartgrootte, stijl en kleuren. Op de website verschijnt de titel en de prijzen voor het gekozen tarief.</p>
            <div class="flex flex-col gap-3 p-3 border border-border rounded-lg bg-muted/20">
                <div class="flex items-center gap-3">
                    <label class="text-sm text-muted-foreground shrink-0 w-40">Bloktitel</label>
                    <input type="text" name="home_sections[{{ $sectionKey }}][title]" class="kt-input flex-1 max-w-md text-sm" value="{{ $tarievenBlockTitle }}" placeholder="Onze tarieven">
                </div>
                <div class="flex items-center gap-3">
                    <label class="text-sm text-muted-foreground shrink-0 w-40">Bloktitel grootte</label>
                    <select name="home_sections[{{ $sectionKey }}][title_font_size]" class="kt-input w-28 text-sm">
                        @foreach($tarievenFontSizes as $val => $label)
                        <option value="{{ $val }}" {{ (old('home_sections.'.$sectionKey.'.title_font_size', $tarievenSectionData['title_font_size'] ?? '')) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-3">
                    <label class="text-sm text-muted-foreground shrink-0 w-40">Bloktitel stijl</label>
                    <select name="home_sections[{{ $sectionKey }}][title_font_style]" class="kt-input w-28 text-sm">
                        @foreach($tarievenFontStyles as $val => $label)
                        <option value="{{ $val }}" {{ (old('home_sections.'.$sectionKey.'.title_font_style', $tarievenSectionData['title_font_style'] ?? 'normal')) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-3">
                    <label class="text-sm text-muted-foreground shrink-0 w-40">Bloktitel uitlijning</label>
                    <select name="home_sections[{{ $sectionKey }}][title_align]" class="kt-input w-28 text-sm">
                        @foreach($tarievenTextAligns as $val => $label)
                        <option value="{{ $val }}" {{ (old('home_sections.'.$sectionKey.'.title_align', $tarievenSectionData['title_align'] ?? 'left')) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div id="taxiroyaal-tarieven-items-{{ $sectionKey }}" class="space-y-4" data-section-key="{{ $sectionKey }}" data-vehicles="{{ json_encode($tarievenVehiclesForJs) }}" data-card-sizes="{{ json_encode($tarievenCardSizes) }}" data-font-styles="{{ json_encode($tarievenFontStyles) }}" data-font-families="{{ json_encode($tarievenFontFamilies) }}" data-font-sizes="{{ json_encode($tarievenFontSizes) }}" data-text-aligns="{{ json_encode($tarievenTextAligns) }}" data-image-paddings="{{ json_encode($tarievenImagePaddings) }}" data-cleaning-1-4="{{ $tarievenCleaning1_4 !== null ? number_format($tarievenCleaning1_4, 2, ',', '.') : '' }}" data-cleaning-5-8="{{ $tarievenCleaning5_8 !== null ? number_format($tarievenCleaning5_8, 2, ',', '.') : '' }}">
                @foreach($tarievenItems as $i => $item)
                @php
                    $itemRateType = $item['rate_type'] ?? ($i === 1 ? '5-8' : '1-4');
                    $defaultItemTitle = $itemRateType === '5-8' ? '5 t/m 8 personen' : ($itemRateType === 'overige_kosten' ? 'Overige kosten' : 't/m 4 personen');
                    $itemTitle = old('home_sections.'.$sectionKey.'.items.'.$i.'.title', ($item['title'] ?? $defaultItemTitle));

                    $itemImageUrl = $item['image_url'] ?? '';
                    $itemVehicleId = $item['vehicle_id'] ?? null;
                    $fallbackVehicle = (!empty($tarievenVehicles) && $itemRateType !== 'overige_kosten')
                        ? ($tarievenVehicles[$i % count($tarievenVehicles)] ?? null)
                        : null;
                    $fallbackVehicleId = $fallbackVehicle['id'] ?? null;
                    if (!$itemVehicleId && $itemImageUrl === '' && $fallbackVehicleId) {
                        $itemVehicleId = $fallbackVehicleId;
                    }
                    $itemImageSource = $itemImageUrl !== '' ? 'custom' : ($itemVehicleId ? (string)$itemVehicleId : '');
                    $itemImagePadding = isset($item['image_padding']) ? max(0, min(30, (int)$item['image_padding'])) : 2;
                    $itemImagePadding = (int)(round($itemImagePadding / 2) * 2);
                @endphp
                <div class="taxiroyaal-tarieven-item border border-border rounded-lg p-4 space-y-3" data-tarieven-index="{{ $i }}">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-sm font-medium">Kaart {{ $i + 1 }}</span>
                        <button type="button" class="taxiroyaal-tarieven-item-remove kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive" title="Kaart verwijderen" aria-label="Verwijderen"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                    </div>
                    <div class="flex flex-wrap items-start gap-2">
                        <div class="shrink-0 flex flex-col items-center">
                            <img alt="Kaart {{ $i + 1 }}" id="hero-{{ $sectionKey }}-taxiroyaal_items_{{ $i }}_image_url-preview" class="w-full max-w-[200px] max-h-24 object-cover border border-border rounded {{ ($itemImageUrl !== '' || $itemVehicleId) ? '' : 'hidden' }}" src="{{ $itemImageUrl !== '' ? $imagePreviewUrl($itemImageUrl) : ($itemVehicleId ? (app(\App\Services\TaxiRoyaalVehicleDisplayService::class)->getImageUrl($itemVehicleId) ?? '') : '') }}" data-taxiroyaal-preview>
                            <button type="button" class="taxiroyaal-image-remove-btn image-remove-btn kt-btn kt-btn-xs kt-btn-ghost text-destructive mt-1 shadow hover:bg-destructive/10" data-url-input-id="hero-{{ $sectionKey }}-taxiroyaal_items_{{ $i }}_image_url" data-preview-id="hero-{{ $sectionKey }}-taxiroyaal_items_{{ $i }}_image_url-preview" title="Afbeelding verwijderen" aria-label="Afbeelding verwijderen"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                        </div>
                        <div class="flex flex-col gap-2 flex-1 min-w-0">
                            <div class="flex items-center gap-3">
                                <label class="taxiroyaal-image-label text-sm text-muted-foreground shrink-0 w-40 {{ ($itemImageUrl !== '' || $itemVehicleId) ? 'hidden' : '' }}">Afbeelding</label>
                                <select name="home_sections[{{ $sectionKey }}][items][{{ $i }}][vehicle_id]" class="kt-input w-56 text-sm taxiroyaal-image-source-select" data-preview-target="hero-{{ $sectionKey }}-taxiroyaal_items_{{ $i }}_image_url-preview" data-upload-wrap="taxiroyaal-{{ $sectionKey }}-items-{{ $i }}-upload-wrap" data-image-url-input="hero-{{ $sectionKey }}-taxiroyaal_items_{{ $i }}_image_url" data-vehicles="{{ json_encode($tarievenVehiclesForJs) }}">
                                    <option value="">Geen</option>
                                    @foreach($tarievenVehicles as $v)
                                    <option value="{{ $v['id'] }}" {{ $itemVehicleId == $v['id'] ? 'selected' : '' }}>{{ $v['name'] }}</option>
                                    @endforeach
                                    <option value="custom" {{ $itemImageSource === 'custom' ? 'selected' : '' }}>Eigen afbeelding</option>
                                </select>
                            </div>
                            <div class="taxiroyaal-upload-wrap {{ $itemImageSource === 'custom' ? '' : 'hidden' }}" id="taxiroyaal-{{ $sectionKey }}-items-{{ $i }}-upload-wrap">
                                <div class="hero-image-upload-area flex flex-col items-center justify-center p-5 lg:p-7 border border-input rounded-xl border-dashed bg-muted/30" data-section-key="{{ $sectionKey }}" data-field="taxiroyaal_items_{{ $i }}_image_url" style="width: 100%; max-width: 500px; min-height: 130px;">
                                    <span class="text-xs text-muted-foreground">Klik of sleep afbeelding</span>
                                    <span class="text-xs text-muted-foreground">JPG, PNG, WebP (max. 5MB)</span>
                                </div>
                                <input type="file" class="hero-image-file-input hidden" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" data-section-key="{{ $sectionKey }}" data-field="taxiroyaal_items_{{ $i }}_image_url">
                            </div>
                            <input type="hidden" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][image_url]" id="hero-{{ $sectionKey }}-taxiroyaal_items_{{ $i }}_image_url" value="{{ old('home_sections.'.$sectionKey.'.items.'.$i.'.image_url', $itemImageUrl) }}">
                        </div>
                    </div>
                    <div class="flex flex-col gap-2 mt-3">
                        <div class="flex items-center gap-3">
                            <label class="text-sm text-muted-foreground shrink-0 w-40">Tarief</label>
                            <select name="home_sections[{{ $sectionKey }}][items][{{ $i }}][rate_type]" class="kt-input w-48 text-sm">
                                @foreach($tarievenRateTypes as $val => $label)
                                <option value="{{ $val }}" {{ $itemRateType === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="text-sm text-muted-foreground shrink-0 w-40">Titel kaart</label>
                            <input type="text" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][title]" class="kt-input flex-1 max-w-md text-sm" value="{{ $itemTitle }}" placeholder="bijv. t/m 4 personen">
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="text-sm text-muted-foreground shrink-0 w-40">Override overige kosten (€)</label>
                            <input type="number" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][cleaning_costs]" class="kt-input w-28 text-sm" value="{{ old('home_sections.'.$sectionKey.'.items.'.$i.'.cleaning_costs', $item['cleaning_costs'] ?? '') }}" placeholder="leeg = uit tarief" step="0.01" min="0">
                            <span class="text-xs text-muted-foreground">Optioneel; leeg = waarde uit gekozen tarief</span>
                        </div>
                        <div class="flex flex-col gap-3 pt-2 border-t border-border">
                            <div class="flex items-center gap-3">
                                <label class="text-sm text-muted-foreground shrink-0 w-40">Kaartgrootte</label>
                                <select name="home_sections[{{ $sectionKey }}][items][{{ $i }}][card_size]" class="kt-input w-36 text-sm">
                                    @foreach($tarievenCardSizes as $val => $label)
                                    <option value="{{ $val }}" {{ ($item['card_size'] ?? 'normal') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="text-sm text-muted-foreground shrink-0 w-40">Stijl</label>
                                <select name="home_sections[{{ $sectionKey }}][items][{{ $i }}][font_style]" class="kt-input w-28 text-sm">
                                    @foreach($tarievenFontStyles as $val => $label)
                                    <option value="{{ $val }}" {{ ($item['font_style'] ?? 'normal') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="text-sm text-muted-foreground shrink-0 w-40">Titel lettertype</label>
                                <select name="home_sections[{{ $sectionKey }}][items][{{ $i }}][title_font_family]" class="kt-input w-40 text-sm">
                                    @foreach($tarievenFontFamilies as $val => $label)
                                    <option value="{{ $val }}" {{ ($item['title_font_family'] ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="text-sm text-muted-foreground shrink-0 w-40">Titel lettergrootte</label>
                                <select name="home_sections[{{ $sectionKey }}][items][{{ $i }}][title_font_size]" class="kt-input w-28 text-sm">
                                    @foreach($tarievenFontSizes as $val => $label)
                                    <option value="{{ $val }}" {{ ($item['title_font_size'] ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="text-sm text-muted-foreground shrink-0 w-40">Titel stijl</label>
                                <select name="home_sections[{{ $sectionKey }}][items][{{ $i }}][title_font_style]" class="kt-input w-28 text-sm">
                                    @foreach($tarievenFontStyles as $val => $label)
                                    <option value="{{ $val }}" {{ ($item['title_font_style'] ?? ($item['font_style'] ?? 'normal')) === $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="text-sm text-muted-foreground shrink-0 w-40">Titel uitlijning</label>
                                <select name="home_sections[{{ $sectionKey }}][items][{{ $i }}][title_align]" class="kt-input w-28 text-sm">
                                    @foreach($tarievenTextAligns as $val => $label)
                                    <option value="{{ $val }}" {{ ($item['title_align'] ?? ($item['text_align'] ?? 'left')) === $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="text-sm text-muted-foreground shrink-0 w-40">Label lettergrootte</label>
                                <select name="home_sections[{{ $sectionKey }}][items][{{ $i }}][label_font_size]" class="kt-input w-28 text-sm">
                                    @foreach($tarievenFontSizes as $val => $label)
                                    <option value="{{ $val }}" {{ ($item['label_font_size'] ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="text-sm text-muted-foreground shrink-0 w-40">Waarde lettergrootte</label>
                                <select name="home_sections[{{ $sectionKey }}][items][{{ $i }}][value_font_size]" class="kt-input w-28 text-sm">
                                    @foreach($tarievenFontSizes as $val => $label)
                                    <option value="{{ $val }}" {{ ($item['value_font_size'] ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="text-sm text-muted-foreground shrink-0 w-40">Uitlijning</label>
                                <select name="home_sections[{{ $sectionKey }}][items][{{ $i }}][text_align]" class="kt-input w-28 text-sm">
                                    @foreach($tarievenTextAligns as $val => $label)
                                    <option value="{{ $val }}" {{ ($item['text_align'] ?? 'left') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="text-sm text-muted-foreground shrink-0 w-40">Padding afbeelding</label>
                                <select name="home_sections[{{ $sectionKey }}][items][{{ $i }}][image_padding]" class="kt-input w-24 text-sm">
                                    @foreach($tarievenImagePaddings as $px => $label)
                                    <option value="{{ $px }}" {{ $itemImagePadding === (int)$px ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="text-sm text-muted-foreground shrink-0 w-40">Achtergrondkleur afbeelding</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" id="taxiroyaal-{{ $sectionKey }}-item-{{ $i }}-image-bg" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($item['image_bg_color'] ?? '') ?: '#e5e7eb' }}" title="Achtergrondkleur">
                                    <input type="text" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][image_bg_color]" id="taxiroyaal-{{ $sectionKey }}-item-{{ $i }}-image-bg-hex" class="kt-input w-24 font-mono text-sm" value="{{ old('home_sections.'.$sectionKey.'.items.'.$i.'.image_bg_color', $item['image_bg_color'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="taxiroyaal-{{ $sectionKey }}-item-{{ $i }}-image-bg">
                                    <button type="button" class="hex-clear-btn kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Leegmaken" data-color-default="#e5e7eb"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="text-sm text-muted-foreground shrink-0 w-40">Tekstkleur</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" id="taxiroyaal-{{ $sectionKey }}-item-{{ $i }}-text-color" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($item['text_color'] ?? '') ?: '#374151' }}" title="Tekstkleur">
                                    <input type="text" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][text_color]" id="taxiroyaal-{{ $sectionKey }}-item-{{ $i }}-text-color-hex" class="kt-input w-24 font-mono text-sm" value="{{ old('home_sections.'.$sectionKey.'.items.'.$i.'.text_color', $item['text_color'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="taxiroyaal-{{ $sectionKey }}-item-{{ $i }}-text-color">
                                    <button type="button" class="hex-clear-btn kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Leegmaken" data-color-default="#374151"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mt-4">
                <button type="button" class="taxiroyaal-tarieven-item-add kt-btn kt-btn-sm kt-btn-outline" data-section-key="{{ $sectionKey }}"><svg class="w-4 h-4 me-1 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>Tarievenkaart toevoegen</button>
            </div>
        </div>
    </div>
    @elseif($sectionKey === 'component:taxiroyaal.boekingsmodule')
            @php
                $bookingData = app(\App\Services\TaxiRoyaalBookingPricingService::class)->mergeSectionConfig($sections[$sectionKey] ?? []);
                $bookingVehicles = app(\App\Services\TaxiRoyaalVehicleDisplayService::class)->getVehiclesForSelect();
                $offerPersonRangeOptions = [
                    '' => 'Alle personen',
                    '1-4' => 't/m 4 personen',
                    '5-8' => '5 t/m 8 personen',
                ];
            @endphp
    <div class="kt-card home-section-card home-section-card--component home-section-card--module" data-section="{{ $sectionKey }}">
        <div class="kt-card-header home-section-header flex items-center justify-between gap-2">
            <span class="home-section-drag-handle cursor-grab active:cursor-grabbing touch-none p-1 -ml-1 rounded text-muted-foreground hover:text-foreground" title="Sleep om volgorde te wijzigen" aria-label="Volgorde wijzigen"><svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" /></svg></span>
            <h3 class="kt-card-title">Boekingsmodule (Taxi)</h3>
            <div class="flex items-center gap-1 shrink-0">
                <button type="button" class="home-section-collapse-toggle kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground" title="Inklappen" aria-label="Sectie inklappen of uitklappen"><svg class="w-5 h-5 text-current home-section-collapse-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg></button>
                <button type="button" class="home-section-component-remove kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-destructive" title="Component van pagina verwijderen" aria-label="Component verwijderen"><svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
            </div>
        </div>
        <div class="home-section-card-body kt-card-table p-4 space-y-4">
            <p class="text-sm text-muted-foreground">Meerstaps wizard voor bagage, aanbiedingen, reisgegevens en contactgegevens. Prijsberekening gebruikt route-afstand/tijd via Google Maps.</p>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 p-3 border border-border rounded-lg bg-muted/20">
                <div><label class="text-sm text-muted-foreground">Bloktitel</label><input type="text" class="kt-input mt-1 w-full text-sm" name="home_sections[{{ $sectionKey }}][title]" value="{{ old('home_sections.'.$sectionKey.'.title', $bookingData['title'] ?? '') }}"></div>
                <div><label class="text-sm text-muted-foreground">Subtitel</label><input type="text" class="kt-input mt-1 w-full text-sm" name="home_sections[{{ $sectionKey }}][subtitle]" value="{{ old('home_sections.'.$sectionKey.'.subtitle', $bookingData['subtitle'] ?? '') }}"></div>
                <div><label class="text-sm text-muted-foreground">Primair kleur</label><input type="color" class="kt-input mt-1 h-10 w-16 p-1" name="home_sections[{{ $sectionKey }}][style][primary_color]" value="{{ old('home_sections.'.$sectionKey.'.style.primary_color', $bookingData['style']['primary_color'] ?? '#5b21b6') }}"></div>
                <div><label class="text-sm text-muted-foreground">Actieve tab kleur</label><input type="color" class="kt-input mt-1 h-10 w-16 p-1" name="home_sections[{{ $sectionKey }}][style][active_tab_color]" value="{{ old('home_sections.'.$sectionKey.'.style.active_tab_color', $bookingData['style']['active_tab_color'] ?? '#5b21b6') }}"></div>
                <div>
                    <label class="text-sm text-muted-foreground">Max breedte (%)</label>
                    <input type="text" class="kt-input mt-1 w-28 text-sm text-left" name="home_sections[{{ $sectionKey }}][style][container_max_width]" value="{{ old('home_sections.'.$sectionKey.'.style.container_max_width', $bookingData['style']['container_max_width'] ?? '100%') }}" placeholder="100%">
                </div>
                <div><label class="text-sm text-muted-foreground">Border radius (px)</label><input type="number" min="0" max="40" class="kt-input mt-1 w-28 text-sm" name="home_sections[{{ $sectionKey }}][style][border_radius]" value="{{ old('home_sections.'.$sectionKey.'.style.border_radius', $bookingData['style']['border_radius'] ?? 12) }}"></div>
                <div>
                    <label class="text-sm text-muted-foreground">Uitlijning blok</label>
                    <select class="kt-input mt-1 w-full text-sm" name="home_sections[{{ $sectionKey }}][style][align]">
                        @php $bookingAlign = old('home_sections.'.$sectionKey.'.style.align', $bookingData['style']['align'] ?? 'center'); @endphp
                        <option value="left" {{ $bookingAlign === 'left' ? 'selected' : '' }}>Links</option>
                        <option value="center" {{ $bookingAlign === 'center' ? 'selected' : '' }}>Midden</option>
                        <option value="right" {{ $bookingAlign === 'right' ? 'selected' : '' }}>Rechts</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm text-muted-foreground">Aanbiedingen tonen als</label>
                    @php $offerDisplayMode = old('home_sections.'.$sectionKey.'.logic.offer_display_mode', $bookingData['logic']['offer_display_mode'] ?? 'vehicle'); @endphp
                    <select class="kt-input mt-1 w-full text-sm" name="home_sections[{{ $sectionKey }}][logic][offer_display_mode]">
                        <option value="vehicle" {{ $offerDisplayMode === 'vehicle' ? 'selected' : '' }}>Per auto</option>
                        <option value="person_range" {{ $offerDisplayMode === 'person_range' ? 'selected' : '' }}>Per aantal personen</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-3 p-3 border border-border rounded-lg">
                @foreach(['step1','step2','step3','step4','step5'] as $stepKey)
                <div>
                    <label class="text-xs text-muted-foreground">{{ strtoupper($stepKey) }} label</label>
                    <input type="text" class="kt-input mt-1 w-full text-sm" name="home_sections[{{ $sectionKey }}][step_labels][{{ $stepKey }}]" value="{{ old('home_sections.'.$sectionKey.'.step_labels.'.$stepKey, $bookingData['step_labels'][$stepKey] ?? '') }}">
                </div>
                @endforeach
            </div>

            @php
                $bookingStepOrder = old('home_sections.'.$sectionKey.'.step_order', $bookingData['step_order'] ?? ['trip', 'baggage', 'offers', 'contact', 'confirm']);
                if (!is_array($bookingStepOrder)) {
                    $bookingStepOrder = ['trip', 'baggage', 'offers', 'contact', 'confirm'];
                }
                $bookingStepOptions = [
                    'trip' => 'Reisgegevens',
                    'baggage' => 'Bagage',
                    'offers' => 'Aanbiedingen',
                    'contact' => 'Contactgegevens',
                    'confirm' => 'Bevestiging',
                ];
                $baggageStepNumber = array_search('baggage', $bookingStepOrder, true);
                $offersStepNumber = array_search('offers', $bookingStepOrder, true);
                $baggageStepNumber = $baggageStepNumber === false ? null : ($baggageStepNumber + 1);
                $offersStepNumber = $offersStepNumber === false ? null : ($offersStepNumber + 1);
            @endphp
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-3 p-3 border border-border rounded-lg">
                @for($stepPos = 0; $stepPos < 5; $stepPos++)
                    <div>
                        <label class="text-xs text-muted-foreground">Positie {{ $stepPos + 1 }}</label>
                        <select class="kt-input mt-1 w-full text-sm" name="home_sections[{{ $sectionKey }}][step_order][]">
                            @foreach($bookingStepOptions as $stepValue => $stepName)
                                <option value="{{ $stepValue }}" {{ (($bookingStepOrder[$stepPos] ?? '') === $stepValue) ? 'selected' : '' }}>{{ $stepName }}</option>
                            @endforeach
                        </select>
                    </div>
                @endfor
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 p-3 border border-border rounded-lg">
                <div><label class="text-xs text-muted-foreground">Min passagiers</label><input type="number" min="1" max="8" class="kt-input mt-1 w-full text-sm" name="home_sections[{{ $sectionKey }}][logic][min_passengers]" value="{{ old('home_sections.'.$sectionKey.'.logic.min_passengers', $bookingData['logic']['min_passengers'] ?? 1) }}"></div>
                <div><label class="text-xs text-muted-foreground">Max passagiers</label><input type="number" min="1" max="20" class="kt-input mt-1 w-full text-sm" name="home_sections[{{ $sectionKey }}][logic][max_passengers]" value="{{ old('home_sections.'.$sectionKey.'.logic.max_passengers', $bookingData['logic']['max_passengers'] ?? 8) }}"></div>
                <div><label class="text-xs text-muted-foreground">Default passagiers</label><input type="number" min="1" max="20" class="kt-input mt-1 w-full text-sm" name="home_sections[{{ $sectionKey }}][logic][default_passengers]" value="{{ old('home_sections.'.$sectionKey.'.logic.default_passengers', $bookingData['logic']['default_passengers'] ?? 1) }}"></div>
                <div><label class="text-xs text-muted-foreground">Max tussenstops</label><input type="number" min="0" max="6" class="kt-input mt-1 w-full text-sm" name="home_sections[{{ $sectionKey }}][logic][max_stopovers]" value="{{ old('home_sections.'.$sectionKey.'.logic.max_stopovers', $bookingData['logic']['max_stopovers'] ?? 3) }}"></div>
                <div><label class="text-xs text-muted-foreground">Retour multiplier</label><input type="number" min="1" max="3" step="0.05" class="kt-input mt-1 w-full text-sm" name="home_sections[{{ $sectionKey }}][logic][return_price_multiplier]" value="{{ old('home_sections.'.$sectionKey.'.logic.return_price_multiplier', $bookingData['logic']['return_price_multiplier'] ?? 2) }}"></div>
                <div><label class="text-xs text-muted-foreground">Standaard x prijs</label><input type="number" min="0.1" max="5" step="0.05" class="kt-input mt-1 w-full text-sm" name="home_sections[{{ $sectionKey }}][logic][person_range_base_price_multiplier]" value="{{ old('home_sections.'.$sectionKey.'.logic.person_range_base_price_multiplier', $bookingData['logic']['person_range_base_price_multiplier'] ?? 1) }}"></div>
                <div><label class="text-xs text-muted-foreground">Standaard x oud</label><input type="number" min="1" max="5" step="0.05" class="kt-input mt-1 w-full text-sm" name="home_sections[{{ $sectionKey }}][logic][person_range_base_old_price_multiplier]" value="{{ old('home_sections.'.$sectionKey.'.logic.person_range_base_old_price_multiplier', $bookingData['logic']['person_range_base_old_price_multiplier'] ?? 1.2) }}"></div>
                <div class="col-span-2 lg:col-span-5 mt-8 flex flex-nowrap items-center gap-x-6 gap-y-2">
                    <label class="inline-flex items-center gap-2 text-sm whitespace-nowrap pr-4"><input type="checkbox" class="kt-switch kt-switch-sm" name="home_sections[{{ $sectionKey }}][logic][return_enabled_by_default]" value="1" {{ old('home_sections.'.$sectionKey.'.logic.return_enabled_by_default', $bookingData['logic']['return_enabled_by_default'] ?? false) ? 'checked' : '' }}> Retour standaard aan</label>
                    <label class="inline-flex items-center gap-2 text-sm whitespace-nowrap"><input type="checkbox" class="kt-switch kt-switch-sm" name="home_sections[{{ $sectionKey }}][logic][skip_baggage_step]" value="1" {{ old('home_sections.'.$sectionKey.'.logic.skip_baggage_step', $bookingData['logic']['skip_baggage_step'] ?? false) ? 'checked' : '' }}> Bagage overslaan</label>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-3 p-3 border border-border rounded-lg">
                <div><label class="text-xs text-muted-foreground">Placeholder ophaaladres</label><input type="text" class="kt-input mt-1 w-full text-sm" name="home_sections[{{ $sectionKey }}][texts][pickup_placeholder]" value="{{ old('home_sections.'.$sectionKey.'.texts.pickup_placeholder', $bookingData['texts']['pickup_placeholder'] ?? '') }}"></div>
                <div><label class="text-xs text-muted-foreground">Placeholder afzetadres</label><input type="text" class="kt-input mt-1 w-full text-sm" name="home_sections[{{ $sectionKey }}][texts][dropoff_placeholder]" value="{{ old('home_sections.'.$sectionKey.'.texts.dropoff_placeholder', $bookingData['texts']['dropoff_placeholder'] ?? '') }}"></div>
                <div><label class="text-xs text-muted-foreground">Tekst personenkaart</label><input type="text" class="kt-input mt-1 w-full text-sm" name="home_sections[{{ $sectionKey }}][texts][person_range_feature_text]" value="{{ old('home_sections.'.$sectionKey.'.texts.person_range_feature_text', $bookingData['texts']['person_range_feature_text'] ?? 'Tarief op basis van aantal personen') }}"></div>
                <div><label class="text-xs text-muted-foreground">Submit knoptekst</label><input type="text" class="kt-input mt-1 w-full text-sm" name="home_sections[{{ $sectionKey }}][texts][submit_button_text]" value="{{ old('home_sections.'.$sectionKey.'.texts.submit_button_text', $bookingData['texts']['submit_button_text'] ?? '') }}"></div>
            </div>

            <div class="space-y-2">
                <div class="flex items-center justify-between"><h4 class="text-sm font-medium" data-step-heading data-step-key="baggage" data-step-heading-base="Bagage-items">Bagage-items (Stap {{ $baggageStepNumber ?? '—' }})</h4><button type="button" class="kt-btn kt-btn-xs kt-btn-outline taxiroyaal-booking-item-add" data-list="baggage" data-section-key="{{ $sectionKey }}">+ item</button></div>
                <div class="space-y-2 taxiroyaal-booking-list" data-list="baggage" data-section-key="{{ $sectionKey }}">
                    @foreach(($bookingData['baggage_items'] ?? []) as $i => $row)
                    <div class="grid w-full gap-2 items-end border border-border rounded p-2 taxiroyaal-booking-row" data-list="baggage" style="grid-template-columns: minmax(0, 1.1fr) minmax(0, 2.6fr) minmax(0, 2.6fr) minmax(0, 1.2fr) minmax(0, 0.7fr) auto;">
                        <div><label class="text-xs">Key</label><input class="kt-input w-full text-sm" name="home_sections[{{ $sectionKey }}][baggage_items][{{ $i }}][key]" value="{{ $row['key'] ?? '' }}"></div>
                        <div><label class="text-xs">Titel</label><input class="kt-input w-full text-sm" name="home_sections[{{ $sectionKey }}][baggage_items][{{ $i }}][title]" value="{{ $row['title'] ?? '' }}"></div>
                        <div><label class="text-xs">Subtitel</label><input class="kt-input w-full text-sm" name="home_sections[{{ $sectionKey }}][baggage_items][{{ $i }}][subtitle]" value="{{ $row['subtitle'] ?? '' }}"></div>
                        <div>
                            <label class="text-xs">Prijs</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">€</span>
                                <input type="number" min="0" step="0.01" class="kt-input w-full text-sm pl-6" name="home_sections[{{ $sectionKey }}][baggage_items][{{ $i }}][price]" value="{{ $row['price'] ?? 0 }}">
                            </div>
                        </div>
                        <div><label class="text-xs">Max</label><input type="number" min="0" max="20" class="kt-input w-full text-sm" name="home_sections[{{ $sectionKey }}][baggage_items][{{ $i }}][max_qty]" value="{{ $row['max_qty'] ?? 4 }}"></div>
                        <div class="text-right"><button type="button" class="kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-destructive taxiroyaal-booking-item-remove">x</button></div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="space-y-2">
                <div class="flex items-center justify-between"><h4 class="text-sm font-medium">Speciale bagage</h4><button type="button" class="kt-btn kt-btn-xs kt-btn-outline taxiroyaal-booking-item-add" data-list="special" data-section-key="{{ $sectionKey }}">+ item</button></div>
                <div class="space-y-2 taxiroyaal-booking-list" data-list="special" data-section-key="{{ $sectionKey }}">
                    @foreach(($bookingData['special_items'] ?? []) as $i => $row)
                    <div class="grid w-full gap-2 items-end border border-border rounded p-2 taxiroyaal-booking-row" data-list="special" style="grid-template-columns: minmax(0, 1.2fr) minmax(0, 3.8fr) minmax(0, 1.2fr) minmax(0, 0.8fr) auto;">
                        <div><label class="text-xs">Key</label><input class="kt-input w-full text-sm" name="home_sections[{{ $sectionKey }}][special_items][{{ $i }}][key]" value="{{ $row['key'] ?? '' }}"></div>
                        <div><label class="text-xs">Titel</label><input class="kt-input w-full text-sm" name="home_sections[{{ $sectionKey }}][special_items][{{ $i }}][title]" value="{{ $row['title'] ?? '' }}"></div>
                        <div>
                            <label class="text-xs">Prijs</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">€</span>
                                <input type="number" min="0" step="0.01" class="kt-input w-full text-sm pl-6" name="home_sections[{{ $sectionKey }}][special_items][{{ $i }}][price]" value="{{ $row['price'] ?? 0 }}">
                            </div>
                        </div>
                        <div><label class="text-xs">Max</label><input type="number" min="0" max="20" class="kt-input w-full text-sm" name="home_sections[{{ $sectionKey }}][special_items][{{ $i }}][max_qty]" value="{{ $row['max_qty'] ?? 4 }}"></div>
                        <div class="text-right"><button type="button" class="kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-destructive taxiroyaal-booking-item-remove">x</button></div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="space-y-2">
                <div class="flex items-center justify-between"><h4 class="text-sm font-medium" data-step-heading data-step-key="offers" data-step-heading-base="Aanbiedingen">Aanbiedingen (Stap {{ $offersStepNumber ?? '—' }})</h4><button type="button" class="kt-btn kt-btn-xs kt-btn-outline taxiroyaal-booking-item-add" data-list="offers" data-section-key="{{ $sectionKey }}">+ kaart</button></div>
                <div class="space-y-2 taxiroyaal-booking-list" data-list="offers" data-section-key="{{ $sectionKey }}">
                    @foreach(($bookingData['offers'] ?? []) as $i => $row)
                    <div class="overflow-x-auto">
                    <div class="grid gap-x-2 gap-y-1 items-center border border-border rounded p-2 taxiroyaal-booking-row" data-list="offers" style="min-width: 800px; grid-template-columns: minmax(56px, 0.6fr) minmax(100px, 1.6fr) minmax(115px, 1.2fr) minmax(100px, 1.2fr) minmax(115px, 1.3fr) minmax(56px, 0.55fr) minmax(56px, 0.55fr) auto;">
                        <label class="text-xs text-muted-foreground">ID</label>
                        <label class="text-xs text-muted-foreground">Titel</label>
                        <label class="text-xs text-muted-foreground">Badge</label>
                        <label class="text-xs text-muted-foreground">Personen</label>
                        <label class="text-xs text-muted-foreground">Voertuig</label>
                        <label class="text-xs text-muted-foreground">x prijs</label>
                        <label class="text-xs text-muted-foreground">x oud</label>
                        <div class="text-right shrink-0"></div>
                        <div class="min-w-0"><input class="kt-input w-full min-w-0 text-sm" name="home_sections[{{ $sectionKey }}][offers][{{ $i }}][id]" value="{{ $row['id'] ?? '' }}"></div>
                        <div class="min-w-0"><input class="kt-input w-full min-w-0 text-sm" name="home_sections[{{ $sectionKey }}][offers][{{ $i }}][title]" value="{{ $row['title'] ?? '' }}"></div>
                        <div class="min-w-0"><input class="kt-input w-full min-w-0 text-sm" name="home_sections[{{ $sectionKey }}][offers][{{ $i }}][badge]" value="{{ $row['badge'] ?? '' }}"></div>
                        <div class="min-w-0"><select class="kt-input w-full min-w-0 text-sm" name="home_sections[{{ $sectionKey }}][offers][{{ $i }}][person_range]">@foreach($offerPersonRangeOptions as $rangeValue => $rangeLabel)<option value="{{ $rangeValue }}" {{ ($row['person_range'] ?? '') === $rangeValue ? 'selected' : '' }}>{{ $rangeLabel }}</option>@endforeach</select></div>
                        <div class="min-w-0"><select class="kt-input w-full min-w-0 text-sm" name="home_sections[{{ $sectionKey }}][offers][{{ $i }}][vehicle_id]"><option value="">Automatisch</option>@foreach($bookingVehicles as $vehicle)<option value="{{ $vehicle['id'] }}" data-person-range="{{ e($vehicle['person_range'] ?? '') }}" {{ (int)($row['vehicle_id'] ?? 0) === (int)$vehicle['id'] ? 'selected' : '' }}>{{ $vehicle['name'] }}</option>@endforeach</select></div>
                        <div class="min-w-0"><input type="number" min="0.1" step="0.05" class="kt-input w-full min-w-0 text-sm" name="home_sections[{{ $sectionKey }}][offers][{{ $i }}][price_multiplier]" value="{{ $row['price_multiplier'] ?? 1 }}"></div>
                        <div class="min-w-0"><input type="number" min="1" step="0.05" class="kt-input w-full min-w-0 text-sm" name="home_sections[{{ $sectionKey }}][offers][{{ $i }}][old_price_multiplier]" value="{{ $row['old_price_multiplier'] ?? 1.2 }}"></div>
                        <div class="text-right shrink-0"><button type="button" class="kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-destructive taxiroyaal-booking-item-remove">x</button></div>
                        <div class="min-w-0" style="grid-column: 1 / -1;"><label class="text-xs text-muted-foreground block">Features (1 per regel)</label><textarea class="kt-input w-full min-w-0 text-sm pt-1" rows="2" name="home_sections[{{ $sectionKey }}][offers][{{ $i }}][features_text]">{{ implode("\n", $row['features'] ?? []) }}</textarea></div>
                    </div>
                    </div>
                    @endforeach
                </div>
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
                    <label class="text-sm font-medium text-secondary-foreground">Footer-logo</label>
                    <input type="hidden" name="home_sections[visibility][footer_logo]" id="visibility-footer_logo" value="{{ ($visibility['footer_logo'] ?? true) ? '1' : '0' }}">
                    <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" data-target="visibility-footer_logo" aria-label="Logo tonen/verbergen">@if($visibility['footer_logo'] ?? true)<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                </div>
                <p class="text-xs text-muted-foreground mb-2">Laat leeg om het logo uit Algemene instellingen te gebruiken.</p>
                <div class="flex flex-wrap items-start gap-2">
                    <div class="shrink-0 flex flex-col items-center">
                        <img alt="Footer logo" id="footer-logo-preview" class="w-auto border border-border rounded object-contain {{ $footerLogoPreviewUrl ? '' : 'hidden' }}" style="max-height: 80px;"
                             src="{{ $footerLogoPreviewUrl ? $imagePreviewUrl($footerLogoPreviewUrl) : '' }}">
                        <button type="button" class="image-remove-btn kt-btn kt-btn-xs kt-btn-ghost text-destructive mt-1 shadow hover:bg-destructive/10" data-url-input-id="footer-logo-url" data-preview-id="footer-logo-preview" title="Logo verwijderen" aria-label="Logo verwijderen"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                    </div>
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
            <div class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="block text-sm font-medium text-secondary-foreground mb-1">Logo-hoogte (px)</label>
                    <select name="home_sections[footer][logo_height]" id="footer-logo-height" class="kt-input w-32">
                        @foreach([12, 14, 16, 18, 20, 22, 24, 26, 28, 30] as $px)
                            <option value="{{ $px }}" {{ $footerLogoHeight === $px ? 'selected' : '' }}>{{ $px }}px</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary-foreground mb-1">Logo-uitlijning</label>
                    <select name="home_sections[footer][logo_align]" id="footer-logo-align" class="kt-input w-40">
                        @php $footerLogoAlign = old('home_sections.footer.logo_align', $footer['logo_align'] ?? 'left'); @endphp
                        <option value="left" {{ $footerLogoAlign === 'left' ? 'selected' : '' }}>Links</option>
                        <option value="center" {{ $footerLogoAlign === 'center' ? 'selected' : '' }}>Midden</option>
                        <option value="right" {{ $footerLogoAlign === 'right' ? 'selected' : '' }}>Rechts</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-secondary-foreground mb-1">Logo alt-tekst</label>
                <input type="text" name="home_sections[footer][logo_alt]" class="kt-input home-section-input-400" value="{{ old('home_sections.footer.logo_alt', $footer['logo_alt'] ?? '') }}" placeholder="Bijv. Nexa Skillmatching">
            </div>
            <div class="row-visibility-row">
                <div class="flex items-center gap-2 mb-1">
                    <label class="text-sm font-medium text-secondary-foreground">Footer-kaart (Google Maps)</label>
                    <input type="hidden" name="home_sections[visibility][footer_map]" id="visibility-footer_map" value="{{ ($visibility['footer_map'] ?? true) ? '1' : '0' }}">
                    <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" data-target="visibility-footer_map" aria-label="Kaart tonen/verbergen">@if($visibility['footer_map'] ?? true)<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                </div>
                @php
                    $mapCityOnly = (bool) old('home_sections.footer.map_city_only', $footer['map_city_only'] ?? false);
                @endphp
                <p class="text-xs text-muted-foreground mb-2">Kaart links in de footer. Vul postcode en huisnummer in en klik op Zoeken, of kies "Alleen plaats invoeren".</p>
                <div class="flex items-center gap-2 mb-3">
                    <input type="hidden" name="home_sections[footer][map_city_only]" value="0">
                    <input type="checkbox"
                           name="home_sections[footer][map_city_only]"
                           id="footer-map-city-only"
                           class="kt-switch kt-switch-sm"
                           value="1"
                           {{ $mapCityOnly ? 'checked' : '' }}>
                    <label for="footer-map-city-only" class="text-sm font-medium text-secondary-foreground cursor-pointer">Alleen plaats invoeren</label>
                </div>
                <div class="flex flex-wrap items-start gap-6 w-full">
                    <div class="flex-1 min-w-0 space-y-2 min-w-[280px]">
                        <div class="flex flex-wrap items-end gap-3 mb-2">
                            <div>
                                <label class="block text-xs text-muted-foreground mb-1">Postcode</label>
                                <input type="text" name="home_sections[footer][map_postcode]" id="footer-map-postcode" class="kt-input w-24" value="{{ old('home_sections.footer.map_postcode', $footer['map_postcode'] ?? '') }}" placeholder="1234AB" maxlength="7" style="text-transform: uppercase;" {{ $mapCityOnly ? 'disabled' : '' }}>
                            </div>
                            <div>
                                <label class="block text-xs text-muted-foreground mb-1">Huisnummer</label>
                                <input type="text" name="home_sections[footer][map_huisnummer]" id="footer-map-huisnummer" class="kt-input w-20" value="{{ old('home_sections.footer.map_huisnummer', $footer['map_huisnummer'] ?? '') }}" placeholder="1" {{ $mapCityOnly ? 'disabled' : '' }}>
                            </div>
                            <button type="button" id="footer-map-lookup-btn" class="kt-btn kt-btn-sm kt-btn-outline" {{ $mapCityOnly ? 'disabled' : '' }}>Zoeken</button>
                        </div>
                        <div class="flex flex-wrap gap-3 mb-2">
                            <div class="min-w-[200px]">
                                <label class="block text-xs text-muted-foreground mb-1">Straat</label>
                                <input type="text" name="home_sections[footer][map_street]" id="footer-map-street" class="kt-input w-full" value="{{ old('home_sections.footer.map_street', $footer['map_street'] ?? '') }}" readonly>
                            </div>
                            <div class="min-w-[160px]">
                                <label class="block text-xs text-muted-foreground mb-1">Plaats</label>
                                <input type="text" name="home_sections[footer][map_city]" id="footer-map-city" class="kt-input w-full" value="{{ old('home_sections.footer.map_city', $footer['map_city'] ?? '') }}" {{ $mapCityOnly ? '' : 'readonly' }}>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-secondary-foreground mb-1">Kaartgrootte</label>
                            <select name="home_sections[footer][map_size]" id="footer-map-size" class="kt-input w-40">
                                <option value="small" {{ old('home_sections.footer.map_size', $footer['map_size'] ?? 'normal') === 'small' ? 'selected' : '' }}>Klein</option>
                                <option value="normal" {{ old('home_sections.footer.map_size', $footer['map_size'] ?? 'normal') === 'normal' ? 'selected' : '' }}>Normaal</option>
                                <option value="large" {{ old('home_sections.footer.map_size', $footer['map_size'] ?? 'normal') === 'large' ? 'selected' : '' }}>Groot</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-secondary-foreground mb-1">Zoomniveau</label>
                            @php $mapZoom = (int) old('home_sections.footer.map_zoom', $footer['map_zoom'] ?? 17); $mapZoom = $mapZoom >= 1 && $mapZoom <= 20 ? $mapZoom : 17; @endphp
                            <select name="home_sections[footer][map_zoom]" id="footer-map-zoom" class="kt-input w-40">
                                @for($z = 10; $z <= 20; $z++)
                                <option value="{{ $z }}" {{ $mapZoom === $z ? 'selected' : '' }}>{{ $z }}</option>
                                @endfor
                            </select>
                            <p class="text-xs text-muted-foreground mt-0.5">1 = ver, 20 = dichtbij</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="hidden" name="home_sections[footer][map_show_address_balloon]" value="0">
                            <input type="checkbox" name="home_sections[footer][map_show_address_balloon]" id="footer-map-show-address-balloon" class="kt-switch kt-switch-sm" value="1" {{ old('home_sections.footer.map_show_address_balloon', $footer['map_show_address_balloon'] ?? false) ? 'checked' : '' }}>
                            <label for="footer-map-show-address-balloon" class="text-sm font-medium text-secondary-foreground cursor-pointer">Adres in ballon tonen</label>
                        </div>
                        <p class="text-xs text-muted-foreground">Toont het adres in een ballon op de kaart bij de marker.</p>
                    </div>
                </div>
                {{-- Voorbeeldkaart op eigen regel, volle breedte (voor alle thema's) --}}
                <div id="footer-map-preview-wrapper" class="w-full mt-4">
                    <label class="block text-xs text-muted-foreground mb-1">Voorbeeld kaart</label>
                    @if(!empty($googleMapsApiKey ?? ''))
                    <div id="footer-map-preview" class="w-full rounded-lg border border-border bg-muted/30 overflow-hidden transition-[width,height] duration-200" data-api-key="{{ $googleMapsApiKey }}" data-map-id="{{ $googleMapsMapId ?? '' }}" data-size="{{ old('home_sections.footer.map_size', $footer['map_size'] ?? 'normal') }}" data-zoom="{{ $mapZoom }}"></div>
                    @else
                    <div class="w-full rounded-lg border border-border bg-muted/30 flex items-center justify-center min-h-[200px] text-sm text-muted-foreground text-center p-4">Stel een Google Maps API-sleutel in bij Algemene instellingen om de voorbeeldkaart te tonen.</div>
                    @endif
                </div>
                <input type="hidden" name="home_sections[footer][map_lat]" id="footer-map-lat" value="{{ old('home_sections.footer.map_lat', $footer['map_lat'] ?? '') }}">
                <input type="hidden" name="home_sections[footer][map_lng]" id="footer-map-lng" value="{{ old('home_sections.footer.map_lng', $footer['map_lng'] ?? '') }}">
            </div>
            <div class="row-visibility-row">
                <div class="flex items-center gap-2 mb-1">
                    <label class="text-sm font-medium text-secondary-foreground">Tagline / korte omschrijving</label>
                    <input type="hidden" name="home_sections[visibility][footer_tagline]" id="visibility-footer_tagline" value="{{ ($visibility['footer_tagline'] ?? true) ? '1' : '0' }}">
                    <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" data-target="visibility-footer_tagline" aria-label="Tagline tonen/verbergen">@if($visibility['footer_tagline'] ?? true)<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                </div>
                @include('admin.website-pages.partials.flowbite-wysiwyg', ['editorId' => 'home-footer-tagline', 'name' => 'home_sections[footer][tagline]', 'value' => old('home_sections.footer.tagline', $footer['tagline'] ?? ''), 'placeholder' => 'Ontdek de perfecte match...', 'textareaId' => 'home-footer-tagline'])
                <p class="text-xs text-muted-foreground mt-1">Wordt onder het logo in de footer getoond. Gebruik de werkbalk voor bold, italic, lijsten, etc.</p>
            </div>
            <div class="border border-border rounded-lg p-4 space-y-4">
                <div class="row-visibility-row">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-sm font-medium text-secondary-foreground">Titel kolom Snelle Links</span>
                        <input type="hidden" name="home_sections[visibility][footer_quick_links]" id="visibility-footer_quick_links" value="{{ ($visibility['footer_quick_links'] ?? true) ? '1' : '0' }}">
                        <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" data-target="visibility-footer_quick_links" aria-label="Snelle links tonen/verbergen">@if($visibility['footer_quick_links'] ?? true)<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                    </div>
                    <label class="block text-xs text-muted-foreground mb-1">Titel kolom</label>
                    <input type="text" name="home_sections[footer][quick_links_title]" class="kt-input w-[300px] max-w-[300px] box-border" value="{{ old('home_sections.footer.quick_links_title', $footer['quick_links_title'] ?? 'Snelle Links') }}" placeholder="Snelle Links" style="width: 300px; max-width: 300px;">
                    <label class="block text-xs text-muted-foreground mt-2 mb-1">Uitlijning</label>
                    @php $quickLinksAlign = old('home_sections.footer.quick_links_align', $footer['quick_links_align'] ?? 'left'); @endphp
                    <select name="home_sections[footer][quick_links_align]" class="kt-input w-40">
                        <option value="left" {{ $quickLinksAlign === 'left' ? 'selected' : '' }}>Links</option>
                        <option value="center" {{ $quickLinksAlign === 'center' ? 'selected' : '' }}>Midden</option>
                        <option value="right" {{ $quickLinksAlign === 'right' ? 'selected' : '' }}>Rechts</option>
                    </select>
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
            </div>
            <div class="border border-border rounded-lg p-4 space-y-4">
            <div class="row-visibility-row">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-sm font-medium text-secondary-foreground">Ondersteuning-links</span>
                    <input type="hidden" name="home_sections[visibility][footer_support_links]" id="visibility-footer_support_links" value="{{ ($visibility['footer_support_links'] ?? true) ? '1' : '0' }}">
                    <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" data-target="visibility-footer_support_links" aria-label="Ondersteuning-links tonen/verbergen">@if($visibility['footer_support_links'] ?? true)<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
                </div>
                <label class="block text-xs text-muted-foreground mb-1">Titel kolom Ondersteuning</label>
                <input type="text" name="home_sections[footer][support_links_title]" class="kt-input w-[300px] max-w-[300px] box-border" value="{{ old('home_sections.footer.support_links_title', $footer['support_links_title'] ?? 'Ondersteuning') }}" placeholder="Ondersteuning" style="width: 300px; max-width: 300px;">
                <label class="block text-xs text-muted-foreground mt-2 mb-1">Uitlijning</label>
                @php $supportLinksAlign = old('home_sections.footer.support_links_align', $footer['support_links_align'] ?? 'left'); @endphp
                <select name="home_sections[footer][support_links_align]" class="kt-input w-40 mb-3">
                    <option value="left" {{ $supportLinksAlign === 'left' ? 'selected' : '' }}>Links</option>
                    <option value="center" {{ $supportLinksAlign === 'center' ? 'selected' : '' }}>Midden</option>
                    <option value="right" {{ $supportLinksAlign === 'right' ? 'selected' : '' }}>Rechts</option>
                </select>
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
                <input type="text" name="home_sections[copyright]" class="kt-input home-section-input-400" value="{{ old('home_sections.copyright', $copyright) }}" placeholder="© {year} Nexa Skillmatching. Alle rechten voorbehouden.">
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

    // Footer map: postcode lookup en sync zichtbare velden naar hidden
    var postcodeLookupUrl = {!! json_encode(route('admin.postcode.lookup')) !!};
    var footerMapPostcode = document.getElementById('footer-map-postcode');
    var footerMapHuisnummer = document.getElementById('footer-map-huisnummer');
    var footerMapStreet = document.getElementById('footer-map-street');
    var footerMapCity = document.getElementById('footer-map-city');
    var footerMapCityOnly = document.getElementById('footer-map-city-only');
    var footerMapLat = document.getElementById('footer-map-lat');
    var footerMapLng = document.getElementById('footer-map-lng');
    var footerMapLookupBtn = document.getElementById('footer-map-lookup-btn');
    function syncFooterMapCityMode() {
        var cityOnly = !!(footerMapCityOnly && footerMapCityOnly.checked);
        if (footerMapPostcode) footerMapPostcode.disabled = cityOnly;
        if (footerMapHuisnummer) footerMapHuisnummer.disabled = cityOnly;
        if (footerMapLookupBtn) footerMapLookupBtn.disabled = cityOnly;
        if (footerMapCity) footerMapCity.readOnly = !cityOnly;
        if (cityOnly) {
            if (footerMapStreet) footerMapStreet.value = '';
            if (footerMapPostcode) footerMapPostcode.value = '';
            if (footerMapHuisnummer) footerMapHuisnummer.value = '';
            if (footerMapLat) footerMapLat.value = '';
            if (footerMapLng) footerMapLng.value = '';
        }
    }
    if (footerMapCityOnly) {
        footerMapCityOnly.addEventListener('change', syncFooterMapCityMode);
    }
    syncFooterMapCityMode();
    if (footerMapLookupBtn && csrfToken) {
        footerMapLookupBtn.addEventListener('click', function() {
            if (footerMapCityOnly && footerMapCityOnly.checked) return;
            var postcode = (footerMapPostcode ? footerMapPostcode.value : '').trim().toUpperCase().replace(/\s+/g, '');
            var huisnummer = (footerMapHuisnummer ? footerMapHuisnummer.value : '').trim();
            if (!/^[1-9][0-9]{3}[A-Z]{2}$/.test(postcode)) { alert('Ongeldig postcode formaat. Gebruik 1234AB'); return; }
            if (!huisnummer) { alert('Vul een huisnummer in.'); return; }
            footerMapLookupBtn.disabled = true;
            fetch(postcodeLookupUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken.getAttribute('content') },
                body: JSON.stringify({ postcode: postcode, huisnummer: huisnummer })
            })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.success) {
                    if (footerMapStreet) footerMapStreet.value = d.street || '';
                    if (footerMapCity) footerMapCity.value = d.city || '';
                    if (footerMapLat) footerMapLat.value = (d.latitude != null && d.longitude != null) ? d.latitude : '';
                    if (footerMapLng) footerMapLng.value = (d.latitude != null && d.longitude != null) ? d.longitude : '';
                    if (typeof window.updateFooterMapPreview === 'function' && d.latitude != null && d.longitude != null) {
                        window.updateFooterMapPreview(parseFloat(d.latitude), parseFloat(d.longitude));
                    }
                } else {
                    alert(d.message || 'Adres niet gevonden.');
                }
            })
            .catch(function() { alert('Zoeken mislukt.'); })
            .finally(function() { footerMapLookupBtn.disabled = false; });
        });
    }

    (function() {
        var previewEl = document.getElementById('footer-map-preview');
        if (!previewEl) return;
        var apiKey = (previewEl.getAttribute('data-api-key') || '').trim();
        if (!apiKey) return;
        var mapId = (previewEl.getAttribute('data-map-id') || '').trim();
        var useAdvancedMarker = mapId && mapId.length > 0;
        var footerMapPreviewMap = null;
        var footerMapPreviewMarker = null;
        var footerMapPreviewInfoWindow = null;
        var footerMapPreviewGeocoder = null;
        var footerMapGeocodeSeq = 0;
        var footerMapAuthFailed = false;
        function showFooterMapFallback() {
            footerMapAuthFailed = true;
            var wrapper = document.getElementById('footer-map-preview-wrapper');
            if (!previewEl || !wrapper) return;
            var errDialog = previewEl.querySelector('[role="alertdialog"], .xxGHyP-dialog-view, [aria-label="Fout"]');
            if (errDialog) errDialog.remove();
            previewEl.innerHTML = '<div class="footer-map-fallback p-3 text-sm text-muted-foreground bg-muted/50 rounded flex items-center justify-center h-full min-h-[120px] text-center">Kaart niet beschikbaar. Voeg in Google Cloud Console bij API-sleutel restricties o.a. <code class="text-xs bg-muted px-1 rounded">' + (window.location.origin || 'http://localhost:8000') + '/*</code> toe en controleer of facturering is ingeschakeld.</div>';
            previewEl.classList.add('footer-map-fallback-active');
        }
        window.gm_authFailure = function() { showFooterMapFallback(); };
        function createPreviewMarker(position, map) {
            if (useAdvancedMarker && google.maps.marker && google.maps.marker.AdvancedMarkerElement) {
                return new google.maps.marker.AdvancedMarkerElement({ map: map, position: position });
            }
            return new google.maps.Marker({ position: position, map: map });
        }
        function removePreviewMarker(marker) {
            if (!marker) return;
            if (marker.setMap) marker.setMap(null);
            else if (marker.map !== undefined) marker.map = null;
        }
        function getPreviewAddress() {
            var street = (document.getElementById('footer-map-street') && document.getElementById('footer-map-street').value) || '';
            var huisnummer = (document.getElementById('footer-map-huisnummer') && document.getElementById('footer-map-huisnummer').value) || '';
            var postcode = (document.getElementById('footer-map-postcode') && document.getElementById('footer-map-postcode').value) || '';
            var city = (document.getElementById('footer-map-city') && document.getElementById('footer-map-city').value) || '';
            var parts = [street, huisnummer].filter(Boolean);
            var line1 = parts.length ? parts.join(' ') : '';
            parts = [postcode, city].filter(Boolean);
            var line2 = parts.length ? parts.join(' ') : '';
            return (line1 && line2) ? line1 + ', ' + line2 : (line1 || line2 || '');
        }
        function getPreviewZoom() {
            var zoomEl = document.getElementById('footer-map-zoom');
            var z = zoomEl ? parseInt(zoomEl.value, 10) : 17;
            return (z >= 1 && z <= 20) ? z : 17;
        }
        function updatePreviewBalloon() {
            if (!footerMapPreviewMap || !footerMapPreviewMarker) return;
            var showEl = document.getElementById('footer-map-show-address-balloon');
            if (footerMapPreviewInfoWindow) {
                footerMapPreviewInfoWindow.close();
                footerMapPreviewInfoWindow = null;
            }
            if (showEl && showEl.checked) {
                var addr = (getPreviewAddress() || '').trim();
                if (addr.length && google.maps.InfoWindow) {
                    footerMapPreviewInfoWindow = new google.maps.InfoWindow({ content: '<div style="padding: 4px 8px 6px; font-size: 14px; color: #000; line-height: 1.25; margin: 0;">' + addr.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</div>' });
                    var pos = footerMapPreviewMarker.position;
                    if (pos && (typeof pos.lat === 'function' ? pos.lat() : pos.lat) != null) {
                        footerMapPreviewInfoWindow.open(footerMapPreviewMap, footerMapPreviewMarker);
                    }
                }
            }
        }
        window.updateFooterMapPreview = function(lat, lng) {
            if (footerMapAuthFailed || typeof google === 'undefined' || !google.maps || !google.maps.Map) return;
            var center = { lat: lat, lng: lng };
            var zoom = getPreviewZoom();
            var mapOptions = {
                center: center,
                zoom: zoom,
                scrollwheel: false,
                mapTypeControl: true,
                streetViewControl: false,
                fullscreenControl: false,
                zoomControl: true
            };
            if (useAdvancedMarker && mapId) mapOptions.mapId = mapId;
            if (!footerMapPreviewMap) {
                footerMapPreviewMap = new google.maps.Map(previewEl, mapOptions);
                footerMapPreviewMarker = createPreviewMarker(center, footerMapPreviewMap);
            } else {
                footerMapPreviewMap.setCenter(center);
                footerMapPreviewMap.setZoom(zoom);
                removePreviewMarker(footerMapPreviewMarker);
                footerMapPreviewMarker = createPreviewMarker(center, footerMapPreviewMap);
            }
            updatePreviewBalloon();
        };
        function getPreviewSearchAddress() {
            var cityOnlyEl = document.getElementById('footer-map-city-only');
            var cityOnly = !!(cityOnlyEl && cityOnlyEl.checked);
            var street = (document.getElementById('footer-map-street') && document.getElementById('footer-map-street').value || '').trim();
            var huisnummer = (document.getElementById('footer-map-huisnummer') && document.getElementById('footer-map-huisnummer').value || '').trim();
            var postcode = (document.getElementById('footer-map-postcode') && document.getElementById('footer-map-postcode').value || '').trim();
            var city = (document.getElementById('footer-map-city') && document.getElementById('footer-map-city').value || '').trim();
            if (cityOnly) return city;
            var parts = [];
            if (street) parts.push(street);
            if (huisnummer) parts.push(huisnummer);
            if (postcode || city) parts.push([postcode, city].filter(Boolean).join(' '));
            return parts.join(', ').trim();
        }
        function updateFooterMapPreviewFromAddress(opts) {
            if (typeof google === 'undefined' || !google.maps || !google.maps.Geocoder) return;
            if (!footerMapPreviewGeocoder) footerMapPreviewGeocoder = new google.maps.Geocoder();
            var address = getPreviewSearchAddress();
            if (!address) return;
            var mySeq = ++footerMapGeocodeSeq;
            footerMapPreviewGeocoder.geocode({ address: address }, function(results, status) {
                if (mySeq !== footerMapGeocodeSeq) return;
                if (status === 'OK' && results && results[0] && results[0].geometry && results[0].geometry.location) {
                    var loc = results[0].geometry.location;
                    var lat = typeof loc.lat === 'function' ? loc.lat() : loc.lat;
                    var lng = typeof loc.lng === 'function' ? loc.lng() : loc.lng;
                    if (typeof lat === 'number' && typeof lng === 'number') {
                        if (footerMapLat) footerMapLat.value = String(lat);
                        if (footerMapLng) footerMapLng.value = String(lng);
                        window.updateFooterMapPreview(lat, lng);
                    }
                } else if (opts && opts.notifyOnFail) {
                    alert('Kon geen locatie vinden voor deze invoer.');
                }
            });
        }
        function initFooterMapPreview() {
            if (footerMapAuthFailed) return;
            if (typeof google === 'undefined' || !google.maps || !google.maps.Map) return;
            try {
                var latInp = document.getElementById('footer-map-lat');
                var lngInp = document.getElementById('footer-map-lng');
                var lat = latInp ? parseFloat(latInp.value) : NaN;
                var lng = lngInp ? parseFloat(lngInp.value) : NaN;
                if (!isNaN(lat) && !isNaN(lng)) window.updateFooterMapPreview(lat, lng);
                else updateFooterMapPreviewFromAddress({ notifyOnFail: false });
            } catch (e) {
                showFooterMapFallback();
            }
        }
        window.initFooterMapPreview = initFooterMapPreview;
        var sizeSelect = document.getElementById('footer-map-size');
        var zoomSelect = document.getElementById('footer-map-zoom');
        var previewWrapperEl = document.getElementById('footer-map-preview-wrapper');
        var sizeToHeights = { small: 200, normal: 300, large: 400 };
        if (zoomSelect) zoomSelect.addEventListener('change', function() { if (footerMapPreviewMap) footerMapPreviewMap.setZoom(getPreviewZoom()); });
        var balloonToggle = document.getElementById('footer-map-show-address-balloon');
        if (balloonToggle) balloonToggle.addEventListener('change', updatePreviewBalloon);
        function applyPreviewSize(size) {
            var h = sizeToHeights[size] || sizeToHeights.normal;
            previewEl.style.width = '100%';
            previewEl.style.height = h + 'px';
            previewEl.setAttribute('data-size', size);
            if (footerMapPreviewMap && typeof google !== 'undefined' && google.maps && google.maps.event) {
                setTimeout(function() { google.maps.event.trigger(footerMapPreviewMap, 'resize'); }, 100);
            }
        }
        if (sizeSelect) {
            sizeSelect.addEventListener('change', function() { applyPreviewSize(this.value); });
            applyPreviewSize(sizeSelect.value);
        }
        var geocodeTimer = null;
        function scheduleAddressGeocode(delayMs) {
            if (geocodeTimer) clearTimeout(geocodeTimer);
            geocodeTimer = setTimeout(function() {
                updateFooterMapPreviewFromAddress({ notifyOnFail: false });
            }, delayMs || 250);
        }
        if (footerMapCity) {
            footerMapCity.addEventListener('input', function() {
                if (footerMapCityOnly && footerMapCityOnly.checked) scheduleAddressGeocode(350);
            });
            footerMapCity.addEventListener('blur', function() {
                if (footerMapCityOnly && footerMapCityOnly.checked) updateFooterMapPreviewFromAddress({ notifyOnFail: false });
            });
        }
        if (footerMapPostcode) {
            footerMapPostcode.addEventListener('blur', function() {
                if (!(footerMapCityOnly && footerMapCityOnly.checked)) scheduleAddressGeocode(100);
            });
        }
        if (footerMapHuisnummer) {
            footerMapHuisnummer.addEventListener('blur', function() {
                if (!(footerMapCityOnly && footerMapCityOnly.checked)) scheduleAddressGeocode(100);
            });
        }
        if (footerMapCityOnly) {
            footerMapCityOnly.addEventListener('change', function() {
                scheduleAddressGeocode(50);
            });
        }
        var mo = new MutationObserver(function(mutations) {
            if (footerMapAuthFailed) return;
            var err = previewEl.querySelector('[role="alertdialog"][aria-label="Fout"], .xxGHyP-dialog-view, .CizjDb-degraded-map-dialog-view');
            if (err) showFooterMapFallback();
        });
        mo.observe(previewEl, { childList: true, subtree: true });
        var s = document.createElement('script');
        s.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(apiKey) + '&libraries=marker&callback=initFooterMapPreview&loading=async';
        s.async = true;
        document.head.appendChild(s);
    })();

    var heroImageUploadUrl = {!! json_encode(route('admin.website-pages.upload-hero-image')) !!};
    function handleHeroImageFile(file, fileInput, urlInput, previewEl, wrapperEl) {
        if (!file || !(file instanceof File)) { alert('Geen bestand geselecteerd.'); if (fileInput) fileInput.value = ''; return; }
        var allowed = ['image/jpeg','image/png','image/jpg','image/gif','image/webp'];
        if (!allowed.includes(file.type)) { alert('Alleen JPEG, PNG, JPG, GIF en WebP zijn toegestaan.'); fileInput.value = ''; return; }
        if (file.size > 5 * 1024 * 1024) { alert('Max. 5MB.'); fileInput.value = ''; return; }
        var fd = new FormData();
        fd.append('image', file);
        var tokenEl = document.querySelector('meta[name="csrf-token"]');
        if (tokenEl) fd.append('_token', tokenEl.getAttribute('content'));
        if (urlInput && urlInput.value && urlInput.value.trim()) fd.append('previous_url', urlInput.value.trim());
        fetch(heroImageUploadUrl, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function(r) {
                return r.text().then(function(text) {
                    var d;
                    try { d = text ? JSON.parse(text) : {}; } catch (e) { d = { message: r.status === 422 ? 'Validatie mislukt.' : 'Upload mislukt.' }; }
                    if (r.ok) return d;
                    var msg = d.message || 'Upload mislukt';
                    if (r.status === 403) msg = d.message || 'Geen rechten om afbeeldingen te uploaden. Alleen super-admins hebben toegang.';
                    else if (d.errors && typeof d.errors === 'object') {
                        var parts = [];
                        if (d.errors.image && Array.isArray(d.errors.image)) parts.push(d.errors.image.join(' '));
                        if (parts.length) msg = parts.join(' ');
                    }
                    throw new Error(msg);
                });
            })
            .then(function(d) { if (d.success && d.url) { urlInput.value = d.url; if (previewEl) { previewEl.src = d.url; previewEl.classList.remove('hidden'); previewEl.removeAttribute('srcset'); } if (wrapperEl) wrapperEl.classList.remove('hidden'); } })
            .catch(function(err) { alert(err.message || 'Upload mislukt'); });
        fileInput.value = '';
    }
    function bindHeroUploadAreasIn(container) {
        var root = container || document;
        root.querySelectorAll('.hero-image-upload-area').forEach(function(area) {
            var sectionKey = area.getAttribute('data-section-key');
            var field = area.getAttribute('data-field');
            if (!sectionKey || !field) return;
            var urlInputId = area.getAttribute('data-url-input-id');
            var fileInputId = area.getAttribute('data-file-input-id');
            var previewId = area.getAttribute('data-preview-id') || (field === 'background_image_url' ? 'hero-' + sectionKey + '-bg-preview' : (field === 'author_image_url' ? 'hero-' + sectionKey + '-author-preview' : 'hero-' + sectionKey + '-' + field + '-preview'));
            var fileInput = (fileInputId && document.getElementById(fileInputId)) || (function() {
                var cardRow = area.closest && area.closest('.cards-ronde-hoeken-item');
                var scope = cardRow || root;
                return scope.querySelector('.hero-image-file-input[data-section-key="' + sectionKey + '"][data-field="' + field + '"]');
            })();
            var urlInput = (urlInputId && document.getElementById(urlInputId)) || document.getElementById('hero-' + sectionKey + '-' + field);
            var preview = (previewId && document.getElementById(previewId)) || (function() {
                var cardRow = area.closest && area.closest('.cards-ronde-hoeken-item');
                return (cardRow || root).querySelector('[id="' + previewId + '"]');
            })();
            if (!fileInput || !urlInput) return;
            area.addEventListener('click', function(e) {
                e.preventDefault();
                fileInput.value = '';
                fileInput.click();
            });
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
        var wrapper = document.getElementById('cta-' + sectionKey + '-bg-preview-wrapper');
        if (!fileInput || !urlInput) return;
        area.addEventListener('click', function(e) { e.preventDefault(); fileInput.click(); });
        area.addEventListener('dragover', function(e) { e.preventDefault(); e.stopPropagation(); area.classList.add('border-primary'); });
        area.addEventListener('dragleave', function(e) { e.preventDefault(); area.classList.remove('border-primary'); });
        area.addEventListener('drop', function(e) { e.preventDefault(); area.classList.remove('border-primary'); if (e.dataTransfer.files.length) handleHeroImageFile(e.dataTransfer.files[0], fileInput, urlInput, preview, wrapper); });
        fileInput.addEventListener('change', function() { if (this.files && this.files.length) handleHeroImageFile(this.files[0], fileInput, urlInput, preview, wrapper); });
    });

    document.querySelectorAll('.image-remove-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var urlInputId = btn.getAttribute('data-url-input-id');
            var previewId = btn.getAttribute('data-preview-id');
            var wrapperId = btn.getAttribute('data-preview-wrapper-id');
            if (!urlInputId || !previewId) return;
            var urlInput = document.getElementById(urlInputId);
            var preview = document.getElementById(previewId);
            var wrapper = wrapperId ? document.getElementById(wrapperId) : null;
            if (urlInput) urlInput.value = '';
            if (preview) {
                var defaultSrc = preview.getAttribute('data-default-src') || '';
                if (defaultSrc) {
                    preview.src = defaultSrc;
                    preview.classList.remove('hidden');
                    if (wrapper) wrapper.classList.remove('hidden');
                } else {
                    preview.src = '';
                    preview.classList.add('hidden');
                    if (wrapper) wrapper.classList.add('hidden');
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
                        row.className = 'carousel-slide-row flex items-center gap-2 rounded border border-border p-2';
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
    // Gebruik event delegation zodat alle knoppen werken (incl. footer Snelle Links/Ondersteuning)
    var eyeSvg = '<svg class="w-5 h-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>';
    var eyeSlashSvg = '<svg class="w-5 h-5 text-current opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>';
    var eyeSvgSmall = '<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>';
    var eyeSlashSvgSmall = '<svg class="w-4 h-4 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>';
    function applyVisibilityTargets(id) {
        var input = document.getElementById(id);
        if (!input) return;
        document.querySelectorAll('[data-visibility-target="' + id + '"]').forEach(function(el) {
            if (input.value === '0') el.classList.add('hidden'); else el.classList.remove('hidden');
        });
    }
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.section-visibility-toggle');
        if (!btn) return;
        var id = btn.getAttribute('data-target');
        var input = id ? document.getElementById(id) : null;
        if (!input) return;
        var visible = input.value !== '1';
        input.value = visible ? '1' : '0';
        btn.setAttribute('title', visible ? 'Verbergen op website' : 'Tonen op website');
        btn.innerHTML = btn.classList.contains('kt-btn-xs') ? (visible ? eyeSvgSmall : eyeSlashSvgSmall) : (visible ? eyeSvg : eyeSlashSvg);
        applyVisibilityTargets(id);
    });
    document.querySelectorAll('input[id^="visibility-"]').forEach(function(input) { applyVisibilityTargets(input.id); });

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
            if (typeof window.destroyFlowbiteWysiwygIn === 'function') window.destroyFlowbiteWysiwygIn(card);
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

    // Kleurvelden: color-picker en hex-tekstveld synchen (container = element of document)
    function normalizeHex(v) {
        if (!v || typeof v !== 'string') return '';
        v = v.trim().toLowerCase();
        if (/^#[0-9a-f]{3}$/.test(v)) return v[0] + v[1] + v[1] + v[2] + v[2] + v[3] + v[3];
        if (/^#[0-9a-f]{6}$/.test(v)) return v;
        if (/^[0-9a-f]{6}$/.test(v)) return '#' + v;
        return '';
    }
    function bindColorSyncIn(container) {
        var root = container && container.querySelectorAll ? container : document;
        root.querySelectorAll('input[data-sync-from]').forEach(function(textInp) {
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
    }
    window.bindColorSyncIn = bindColorSyncIn;
    bindColorSyncIn(document);

    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.hex-clear-btn');
        if (!btn) return;
        e.preventDefault();
        var hexInput = btn.previousElementSibling;
        if (!hexInput || !hexInput.matches || !hexInput.matches('input[data-sync-from]')) return;
        hexInput.value = '';
        var colorId = hexInput.getAttribute('data-sync-from');
        var defaultHex = btn.getAttribute('data-color-default') || '#e5e7eb';
        var colorInp = document.getElementById(colorId);
        if (colorInp) colorInp.value = defaultHex;
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

        function getFlowbiteWysiwygHtml(editorId, name, textareaId, placeholder) {
            var tpl = document.getElementById('flowbite-wysiwyg-tpl');
            if (!tpl || !tpl.textContent) return '';
            return tpl.textContent
                .replace(/__FLOWBITE_EDITOR_ID__/g, editorId)
                .replace(/__FLOWBITE_NAME__/g, name)
                .replace(/__FLOWBITE_TEXTAREA_ID__/g, textareaId)
                .replace(/__FLOWBITE_PLACEHOLDER__/g, placeholder || '');
        }
        window.getFlowbiteWysiwygHtml = getFlowbiteWysiwygHtml;
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
                var iconAlignInp = row.querySelector('.features-item-icon-align');
                if (iconAlignInp) iconAlignInp.name = 'home_sections[features][items][' + i + '][icon_align]';
                var visInput = row.querySelector('input[type="hidden"][name*="visibility"][name*="features_item"]');
                if (visInput) {
                    visInput.name = 'home_sections[visibility][features_item_' + i + ']';
                    visInput.id = 'visibility-features_item_' + i;
                }
                var wrapper = row.querySelector('[data-flowbite-wysiwyg]');
                var ta = wrapper ? wrapper.querySelector('[data-editor-input]') : row.querySelector('textarea');
                if (ta) {
                    ta.name = 'home_sections[features][items][' + i + '][description]';
                    ta.id = 'home-features-item-' + i + '-description';
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
                    '<div><label class="block text-xs text-muted-foreground mb-1">Titel</label><input type="text" name="home_sections[features][items][' + nextIndex + '][title]" class="kt-input home-section-input-400 features-item-title" value=""></div>' +
                    '<div><label class="block text-xs text-muted-foreground mb-1">Beschrijving</label>' + getFlowbiteWysiwygHtml('home-features-item-' + nextIndex + '-description', 'home_sections[features][items][' + nextIndex + '][description]', 'home-features-item-' + nextIndex + '-description', '') + '</div>' +
                    '<div class="flex flex-col gap-2"><div class="flex items-center gap-3"><label class="block text-xs font-medium text-muted-foreground w-40 shrink-0">Icoon (Heroicon)</label><select name="home_sections[features][items][' + nextIndex + '][icon]" class="kt-input flex-1 min-w-0 features-item-icon">' + iconOpts + '</select></div><div class="flex items-center gap-3"><label class="block text-xs font-medium text-muted-foreground w-40 shrink-0">Grootte icoon</label><select name="home_sections[features][items][' + nextIndex + '][icon_size]" class="kt-input flex-1 min-w-0 features-item-icon-size">' + sizeOpts + '</select></div><div class="flex items-center gap-3"><label class="block text-xs font-medium text-muted-foreground w-40 shrink-0">Positie titel en icoon</label><select name="home_sections[features][items][' + nextIndex + '][icon_align]" class="kt-input flex-1 min-w-0 features-item-icon-align"><option value="left">Links</option><option value="center" selected>Midden</option><option value="right">Rechts</option></select></div></div>' +
                    '</div>';
                container.appendChild(div);
                if (typeof window.initFlowbiteWysiwyg === 'function') window.initFlowbiteWysiwyg(div);
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
            var urlInputId = area.getAttribute('data-url-input-id');
            var fileInputId = area.getAttribute('data-file-input-id');
            var previewId = area.getAttribute('data-preview-id') || (field === 'background_image_url' ? 'hero-' + sectionKey + '-bg-preview' : (field === 'author_image_url' ? 'hero-' + sectionKey + '-author-preview' : 'hero-' + sectionKey + '-' + field + '-preview'));
            var cardRow = area.closest ? area.closest('.cards-ronde-hoeken-item') : null;
            var scope = cardRow || document;
            var fileInput = (fileInputId && document.getElementById(fileInputId)) || scope.querySelector('.hero-image-file-input[data-section-key="' + sectionKey + '"][data-field="' + field + '"]');
            var urlInput = (urlInputId && document.getElementById(urlInputId)) || document.getElementById('hero-' + sectionKey + '-' + field) || scope.querySelector('[id="hero-' + sectionKey + '-' + field + '"]');
            var preview = (previewId && document.getElementById(previewId)) || (scope.querySelector && scope.querySelector('[id="' + previewId + '"]'));
            if (!fileInput || !urlInput) return;
            function handleFile(file) {
                if (!file) return;
                var allowed = ['image/jpeg','image/png','image/jpg','image/gif','image/webp'];
                if (!allowed.includes(file.type)) { alert('Alleen JPEG, PNG, JPG, GIF en WebP zijn toegestaan.'); fileInput.value = ''; return; }
                if (file.size > 5 * 1024 * 1024) { alert('Max. 5MB.'); fileInput.value = ''; return; }
                var fd = new FormData();
                fd.append('image', file);
                fd.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                if (urlInput.value && urlInput.value.trim()) fd.append('previous_url', urlInput.value.trim());
                fetch(heroImageUploadUrl, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, credentials: 'same-origin' })
                    .then(function(r) { return r.ok ? r.json() : r.json().then(function(d) { throw new Error(d.message || 'Upload mislukt'); }); })
                    .then(function(d) { if (d.success && d.url) { urlInput.value = d.url; if (preview) { preview.src = d.url; preview.classList.remove('hidden'); preview.removeAttribute('srcset'); } } })
                    .catch(function(err) { alert(err.message || 'Upload mislukt'); });
                fileInput.value = '';
            }
            area.addEventListener('click', function(e) { e.preventDefault(); fileInput.value = ''; fileInput.click(); });
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
                var urlInputId = 'hero-' + sectionKey + '-items_' + nextIndex + '_image_url';
                var fileInputId = urlInputId + '-file';
                var previewId = urlInputId + '-preview';
                div.innerHTML = '<div class="flex items-center justify-between gap-2"><span class="text-sm font-medium">Kaart ' + (nextIndex + 1) + '</span><button type="button" class="cards-ronde-hoeken-item-remove kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive" title="Kaart verwijderen" aria-label="Verwijderen">' + trashSvg + '</button></div>' +
                    '<div class="flex flex-wrap items-start gap-2"><div class="shrink-0 flex flex-col items-center"><img alt="Kaart ' + (nextIndex + 1) + '" id="' + previewId + '" class="w-full max-w-[200px] max-h-24 object-cover border border-border rounded hidden" src=""><button type="button" class="image-remove-btn kt-btn kt-btn-xs kt-btn-ghost text-destructive mt-1 shadow hover:bg-destructive/10" data-url-input-id="' + urlInputId + '" data-preview-id="' + previewId + '" title="Afbeelding verwijderen" aria-label="Afbeelding verwijderen">' + trashSvg + '</button></div>' +
                    '<div class="hero-image-upload-area flex flex-col items-center justify-center p-5 lg:p-7 border border-input rounded-xl border-dashed bg-muted/30" data-section-key="' + sectionKey + '" data-field="items_' + nextIndex + '_image_url" data-url-input-id="' + urlInputId + '" data-file-input-id="' + fileInputId + '" data-preview-id="' + previewId + '" style="width: 500px; min-width: 500px; height: 130px;"><span class="text-xs text-muted-foreground">Klik of sleep afbeelding</span><span class="text-xs text-muted-foreground">JPG, PNG, WebP (max. 5MB)</span></div></div>' +
                    '<input type="file" id="' + fileInputId + '" class="hero-image-file-input hidden" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" data-section-key="' + sectionKey + '" data-field="items_' + nextIndex + '_image_url">' +
                    '<input type="hidden" name="home_sections[' + sectionKey + '][items][' + nextIndex + '][image_url]" id="' + urlInputId + '" value="">' +
                    '<div class="space-y-2 mt-3"><div class="flex flex-wrap items-center gap-4"><label class="text-sm font-medium text-secondary-foreground shrink-0">Tekst onder afbeelding</label><input type="hidden" name="home_sections[visibility][' + sectionKey + '_item_' + nextIndex + ']" id="visibility-' + sectionKey + '_item_' + nextIndex + '" value="1"><button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0" data-target="visibility-' + sectionKey + '_item_' + nextIndex + '" aria-label="Tekst tonen/verbergen">' + eyeSvg + '</button></div>' +
                    '<div class="flex flex-col gap-2"><div class="flex items-center gap-3"><label class="text-sm text-muted-foreground shrink-0 w-40">Kaartgrootte</label><select name="home_sections[' + sectionKey + '][items][' + nextIndex + '][card_size]" class="kt-input w-36 text-sm"><option value="small">Klein (300px)</option><option value="normal" selected>Normaal (400px)</option><option value="large">Groot (500px)</option><option value="max">Maximaal (volledige breedte)</option><option value="total_width">Totaalformaat cards</option></select></div><div class="flex items-center gap-3"><label class="text-sm text-muted-foreground shrink-0 w-40">Stijl</label><select name="home_sections[' + sectionKey + '][items][' + nextIndex + '][font_style]" class="kt-input w-28 text-sm"><option value="normal" selected>Normaal</option><option value="bold">Vet</option><option value="italic">Cursief</option></select></div><div class="flex items-center gap-3"><label class="text-sm text-muted-foreground shrink-0 w-40">Uitlijning</label><select name="home_sections[' + sectionKey + '][items][' + nextIndex + '][text_align]" class="kt-input w-28 text-sm"><option value="left" selected>Links</option><option value="center">Midden</option><option value="right">Rechts</option></select></div><div class="flex items-center gap-3"><label class="text-sm text-muted-foreground shrink-0 w-40">Padding afbeelding</label><select name="home_sections[' + sectionKey + '][items][' + nextIndex + '][image_padding]" class="kt-input w-24 text-sm">' + (function(){ var o = ['<option value="0">0px</option>']; for (var px = 2; px <= 30; px += 2) o.push('<option value="' + px + '"' + (px === 2 ? ' selected' : '') + '>' + px + 'px</option>'); return o.join(''); })() + '</select></div><div class="flex items-center gap-3"><label class="text-sm text-muted-foreground shrink-0 w-40">Achtergrondkleur afbeelding</label><div class="flex items-center gap-2"><input type="color" id="cards-' + sectionKey + '-item-' + nextIndex + '-image-bg" class="h-10 w-14 cursor-pointer rounded border border-input" value="#e5e7eb" title="Achtergrondkleur"><input type="text" name="home_sections[' + sectionKey + '][items][' + nextIndex + '][image_bg_color]" id="cards-' + sectionKey + '-item-' + nextIndex + '-image-bg-hex" class="kt-input w-24 font-mono text-sm" value="" placeholder="#hex of leeg" maxlength="7" data-sync-from="cards-' + sectionKey + '-item-' + nextIndex + '-image-bg"><button type="button" class="hex-clear-btn kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Leegmaken" aria-label="Leegmaken" data-color-default="#e5e7eb"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button></div></div></div><div class="flex items-center gap-3"><label class="text-sm text-muted-foreground shrink-0 w-40">Tekstkleur</label><div class="flex items-center gap-2"><input type="color" id="cards-' + sectionKey + '-item-' + nextIndex + '-text-color" class="h-10 w-14 cursor-pointer rounded border border-input" value="#374151" title="Tekstkleur"><input type="text" name="home_sections[' + sectionKey + '][items][' + nextIndex + '][text_color]" id="cards-' + sectionKey + '-item-' + nextIndex + '-text-color-hex" class="kt-input w-24 font-mono text-sm" value="" placeholder="#hex of leeg" maxlength="7" data-sync-from="cards-' + sectionKey + '-item-' + nextIndex + '-text-color"><button type="button" class="hex-clear-btn kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Leegmaken" aria-label="Leegmaken" data-color-default="#374151"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button></div></div></div>' +
                    '<div class="w-full min-w-0">' + (function(){ var editorId = 'home-cards-' + sectionKey + '-item-' + nextIndex + '-text'; var name = 'home_sections[' + sectionKey + '][items][' + nextIndex + '][text]'; return (typeof window.getFlowbiteWysiwygHtml === 'function' ? window.getFlowbiteWysiwygHtml(editorId, name, editorId, 'Tekst onder de afbeelding (rich text)') : '<textarea name="' + name + '" id="' + editorId + '" class="kt-input w-full" rows="6"></textarea>'); })() + '</div></div>';
                container.appendChild(div);
                if (typeof window.initFlowbiteWysiwyg === 'function') window.initFlowbiteWysiwyg(div);
                if (typeof window.bindColorSyncIn === 'function') window.bindColorSyncIn(div);
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

    // Taxi Royaal tarieven: image-source select, kaart toevoegen/verwijderen
    (function() {
        var heroImageUploadUrl = {!! json_encode(route('admin.website-pages.upload-hero-image')) !!};
        function updateTaxiroyaalImageLabel(row) {
            if (!row) return;
            var label = row.querySelector('.taxiroyaal-image-label');
            if (!label) return;
            var preview = row.querySelector('[data-taxiroyaal-preview]');
            var hasVisiblePreview = !!(preview && !preview.classList.contains('hidden') && (preview.getAttribute('src') || '').trim() !== '');
            label.classList.toggle('hidden', hasVisiblePreview);
        }
        document.addEventListener('click', function(e) {
            var removeBtn = e.target.closest('.taxiroyaal-image-remove-btn');
            if (removeBtn) {
                e.preventDefault();
                var urlInputId = removeBtn.getAttribute('data-url-input-id');
                var previewId = removeBtn.getAttribute('data-preview-id');
                if (urlInputId) { var u = document.getElementById(urlInputId); if (u) u.value = ''; }
                if (previewId) { var p = document.getElementById(previewId); if (p) { p.src = ''; p.classList.add('hidden'); } }
                var row = removeBtn.closest('.taxiroyaal-tarieven-item');
                var sel = row ? row.querySelector('.taxiroyaal-image-source-select') : null;
                if (sel) sel.value = '';
                var wrap = row ? row.querySelector('.taxiroyaal-upload-wrap') : null;
                if (wrap) wrap.classList.add('hidden');
                if (row) updateTaxiroyaalImageLabel(row);
                return;
            }
        });
        document.addEventListener('change', function(e) {
            var sel = e.target.closest('.taxiroyaal-image-source-select');
            if (sel) {
                var val = sel.value;
                var wrap = document.getElementById(sel.getAttribute('data-upload-wrap'));
                var preview = document.getElementById(sel.getAttribute('data-preview-target'));
                var urlInput = document.getElementById(sel.getAttribute('data-image-url-input'));
                if (wrap) wrap.classList.toggle('hidden', val !== 'custom');
                if (urlInput && val !== 'custom') urlInput.value = '';
                if (preview) {
                    if (val === '' || val === 'custom') {
                        preview.classList.add('hidden');
                        if (val === 'custom' && urlInput && urlInput.value) preview.src = urlInput.value;
                    } else {
                        var vehicles = [];
                        try { vehicles = JSON.parse(sel.getAttribute('data-vehicles') || '[]'); } catch (x) {}
                        var vid = parseInt(val, 10);
                        var v = vehicles.find(function(x) { return x.id === vid; });
                        if (v && v.image_url) { preview.src = v.image_url; preview.classList.remove('hidden'); }
                        else { preview.classList.add('hidden'); }
                    }
                }
                updateTaxiroyaalImageLabel(sel.closest('.taxiroyaal-tarieven-item'));
            }
        });
        function reindexTaxiroyaalItems(container, sectionKey) {
            var rows = container ? container.querySelectorAll('.taxiroyaal-tarieven-item') : [];
            rows.forEach(function(row, i) {
                row.setAttribute('data-tarieven-index', i);
                var titleEl = row.querySelector('.text-sm.font-medium');
                if (titleEl) titleEl.textContent = 'Kaart ' + (i + 1);

                var field = 'taxiroyaal_items_' + i + '_image_url';
                var previewId = 'hero-' + sectionKey + '-' + field + '-preview';
                var hiddenId = 'hero-' + sectionKey + '-' + field;
                var uploadWrapId = 'taxiroyaal-' + sectionKey + '-items-' + i + '-upload-wrap';

                var preview = row.querySelector('[data-taxiroyaal-preview]');
                if (preview) {
                    preview.id = previewId;
                    preview.alt = 'Kaart ' + (i + 1);
                }
                var imageRemoveBtn = row.querySelector('.taxiroyaal-image-remove-btn');
                if (imageRemoveBtn) {
                    imageRemoveBtn.setAttribute('data-url-input-id', hiddenId);
                    imageRemoveBtn.setAttribute('data-preview-id', previewId);
                }
                var sourceSelect = row.querySelector('.taxiroyaal-image-source-select');
                if (sourceSelect) {
                    sourceSelect.name = 'home_sections[' + sectionKey + '][items][' + i + '][vehicle_id]';
                    sourceSelect.setAttribute('data-preview-target', previewId);
                    sourceSelect.setAttribute('data-upload-wrap', uploadWrapId);
                    sourceSelect.setAttribute('data-image-url-input', hiddenId);
                }
                var uploadWrap = row.querySelector('.taxiroyaal-upload-wrap');
                if (uploadWrap) uploadWrap.id = uploadWrapId;
                var uploadArea = row.querySelector('.hero-image-upload-area');
                if (uploadArea) uploadArea.setAttribute('data-field', field);
                var uploadInput = row.querySelector('.hero-image-file-input');
                if (uploadInput) uploadInput.setAttribute('data-field', field);
                var hiddenImageInput = row.querySelector('input[type="hidden"][name*="[image_url]"]');
                if (hiddenImageInput) {
                    hiddenImageInput.name = 'home_sections[' + sectionKey + '][items][' + i + '][image_url]';
                    hiddenImageInput.id = hiddenId;
                }

                var rateTypeSel = row.querySelector('select[name*="[rate_type]"]');
                if (rateTypeSel) rateTypeSel.name = 'home_sections[' + sectionKey + '][items][' + i + '][rate_type]';
                var titleInput = row.querySelector('input[name*="[title]"]');
                if (titleInput) titleInput.name = 'home_sections[' + sectionKey + '][items][' + i + '][title]';
                var cleaningInput = row.querySelector('input[name*="[cleaning_costs]"]');
                if (cleaningInput) cleaningInput.name = 'home_sections[' + sectionKey + '][items][' + i + '][cleaning_costs]';
                var cardSizeSel = row.querySelector('select[name*="[card_size]"]');
                if (cardSizeSel) cardSizeSel.name = 'home_sections[' + sectionKey + '][items][' + i + '][card_size]';
                var fontStyleSel = row.querySelector('select[name*="[font_style]"]');
                if (fontStyleSel) fontStyleSel.name = 'home_sections[' + sectionKey + '][items][' + i + '][font_style]';
                var titleFontFamilySel = row.querySelector('select[name*="[title_font_family]"]');
                if (titleFontFamilySel) titleFontFamilySel.name = 'home_sections[' + sectionKey + '][items][' + i + '][title_font_family]';
                var titleFontSizeSel = row.querySelector('select[name*="[title_font_size]"]');
                if (titleFontSizeSel) titleFontSizeSel.name = 'home_sections[' + sectionKey + '][items][' + i + '][title_font_size]';
                var titleFontStyleSel = row.querySelector('select[name*="[title_font_style]"]');
                if (titleFontStyleSel) titleFontStyleSel.name = 'home_sections[' + sectionKey + '][items][' + i + '][title_font_style]';
                var titleAlignSel = row.querySelector('select[name*="[title_align]"]');
                if (titleAlignSel) titleAlignSel.name = 'home_sections[' + sectionKey + '][items][' + i + '][title_align]';
                var labelFontSizeSel = row.querySelector('select[name*="[label_font_size]"]');
                if (labelFontSizeSel) labelFontSizeSel.name = 'home_sections[' + sectionKey + '][items][' + i + '][label_font_size]';
                var valueFontSizeSel = row.querySelector('select[name*="[value_font_size]"]');
                if (valueFontSizeSel) valueFontSizeSel.name = 'home_sections[' + sectionKey + '][items][' + i + '][value_font_size]';
                var textAlignSel = row.querySelector('select[name*="[text_align]"]');
                if (textAlignSel) textAlignSel.name = 'home_sections[' + sectionKey + '][items][' + i + '][text_align]';
                var imagePaddingSel = row.querySelector('select[name*="[image_padding]"]');
                if (imagePaddingSel) imagePaddingSel.name = 'home_sections[' + sectionKey + '][items][' + i + '][image_padding]';

                var imageBgColorPicker = row.querySelector('input[type="color"][id*="-image-bg"]');
                var imageBgColorText = row.querySelector('input[type="text"][name*="[image_bg_color]"]');
                if (imageBgColorPicker && imageBgColorText) {
                    var imageBgId = 'taxiroyaal-' + sectionKey + '-item-' + i + '-image-bg';
                    imageBgColorPicker.id = imageBgId;
                    imageBgColorText.name = 'home_sections[' + sectionKey + '][items][' + i + '][image_bg_color]';
                    imageBgColorText.id = imageBgId + '-hex';
                    imageBgColorText.setAttribute('data-sync-from', imageBgId);
                }

                var textColorPicker = row.querySelector('input[type="color"][id*="-text-color"]');
                var textColorText = row.querySelector('input[type="text"][name*="[text_color]"]');
                if (textColorPicker && textColorText) {
                    var textColorId = 'taxiroyaal-' + sectionKey + '-item-' + i + '-text-color';
                    textColorPicker.id = textColorId;
                    textColorText.name = 'home_sections[' + sectionKey + '][items][' + i + '][text_color]';
                    textColorText.id = textColorId + '-hex';
                    textColorText.setAttribute('data-sync-from', textColorId);
                }
                updateTaxiroyaalImageLabel(row);
            });
        }
        document.addEventListener('click', function(e) {
            var addBtn = e.target.closest('.taxiroyaal-tarieven-item-add');
            if (addBtn) {
                e.preventDefault();
                var sectionKey = addBtn.getAttribute('data-section-key');
                var container = document.getElementById('taxiroyaal-tarieven-items-' + sectionKey);
                if (!container) return;
                reindexTaxiroyaalItems(container, sectionKey);
                var items = container.querySelectorAll('.taxiroyaal-tarieven-item');
                var nextIndex = items.length;
                var vehicles = []; var cardSizes = {}; var fontStyles = {}; var fontFamilies = {}; var fontSizes = {}; var textAligns = {}; var imagePaddings = {};
                try { vehicles = JSON.parse(container.getAttribute('data-vehicles') || '[]'); } catch (x) {}
                try { cardSizes = JSON.parse(container.getAttribute('data-card-sizes') || '{}'); } catch (x) {}
                try { fontStyles = JSON.parse(container.getAttribute('data-font-styles') || '{}'); } catch (x) {}
                try { fontFamilies = JSON.parse(container.getAttribute('data-font-families') || '{}'); } catch (x) {}
                try { fontSizes = JSON.parse(container.getAttribute('data-font-sizes') || '{}'); } catch (x) {}
                try { textAligns = JSON.parse(container.getAttribute('data-text-aligns') || '{}'); } catch (x) {}
                try { imagePaddings = JSON.parse(container.getAttribute('data-image-paddings') || '{}'); } catch (x) {}
                var field = 'taxiroyaal_items_' + nextIndex + '_image_url';
                var vehicleOpts = '<option value="">Geen</option>';
                vehicles.forEach(function(v) { vehicleOpts += '<option value="' + v.id + '">' + (v.name || '') + '</option>'; });
                vehicleOpts += '<option value="custom">Eigen afbeelding</option>';
                var cardSizeOpts = ''; for (var k in cardSizes) cardSizeOpts += '<option value="' + k + '"' + (k === 'normal' ? ' selected' : '') + '>' + cardSizes[k] + '</option>';
                var fontStyleOpts = ''; for (var k in fontStyles) fontStyleOpts += '<option value="' + k + '"' + (k === 'normal' ? ' selected' : '') + '>' + fontStyles[k] + '</option>';
                var fontFamilyOpts = ''; for (var k in fontFamilies) fontFamilyOpts += '<option value="' + k + '"' + (k === '' ? ' selected' : '') + '>' + fontFamilies[k] + '</option>';
                var fontSizeOpts = ''; for (var k in fontSizes) fontSizeOpts += '<option value="' + k + '"' + (k === '' ? ' selected' : '') + '>' + fontSizes[k] + '</option>';
                var textAlignOpts = ''; for (var k in textAligns) textAlignOpts += '<option value="' + k + '"' + (k === 'left' ? ' selected' : '') + '>' + textAligns[k] + '</option>';
                var paddingOpts = ''; for (var k in imagePaddings) paddingOpts += '<option value="' + k + '"' + (k === '2' ? ' selected' : '') + '>' + imagePaddings[k] + '</option>';
                var rateOpt1 = 't/m 4 personen';
                var rateOpt2 = '5 t/m 8 personen';
                var rateOpt3 = 'Overige kosten';
                var trashSvg = '<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>';
                var hexClearSvg = '<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>';
                var div = document.createElement('div');
                div.className = 'taxiroyaal-tarieven-item border border-border rounded-lg p-4 space-y-3';
                div.setAttribute('data-tarieven-index', nextIndex);
                div.innerHTML = '<div class="flex items-center justify-between gap-2"><span class="text-sm font-medium">Kaart ' + (nextIndex + 1) + '</span><button type="button" class="taxiroyaal-tarieven-item-remove kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive" title="Kaart verwijderen" aria-label="Verwijderen">' + trashSvg + '</button></div>' +
                    '<div class="flex flex-wrap items-start gap-2"><div class="shrink-0 flex flex-col items-center"><img alt="Kaart ' + (nextIndex + 1) + '" id="hero-' + sectionKey + '-' + field + '-preview" class="w-full max-w-[200px] max-h-24 object-cover border border-border rounded hidden" src=""><button type="button" class="image-remove-btn kt-btn kt-btn-xs kt-btn-ghost text-destructive mt-1 shadow hover:bg-destructive/10" data-url-input-id="hero-' + sectionKey + '-' + field + '" data-preview-id="hero-' + sectionKey + '-' + field + '-preview" title="Afbeelding verwijderen">' + trashSvg + '</button></div>' +
                    '<div class="flex flex-col gap-2 flex-1 min-w-0"><div class="flex items-center gap-3"><label class="taxiroyaal-image-label text-sm text-muted-foreground shrink-0 w-40">Afbeelding</label><select name="home_sections[' + sectionKey + '][items][' + nextIndex + '][vehicle_id]" class="kt-input w-56 text-sm taxiroyaal-image-source-select" data-preview-target="hero-' + sectionKey + '-' + field + '-preview" data-upload-wrap="taxiroyaal-' + sectionKey + '-items-' + nextIndex + '-upload-wrap" data-image-url-input="hero-' + sectionKey + '-' + field + '" data-vehicles="' + (container.getAttribute('data-vehicles') || '[]').replace(/"/g, '&quot;') + '">' + vehicleOpts + '</select></div>' +
                    '<div class="taxiroyaal-upload-wrap hidden" id="taxiroyaal-' + sectionKey + '-items-' + nextIndex + '-upload-wrap"><div class="hero-image-upload-area flex flex-col items-center justify-center p-5 lg:p-7 border border-input rounded-xl border-dashed bg-muted/30" data-section-key="' + sectionKey + '" data-field="' + field + '" style="width:100%;max-width:500px;min-height:130px"><span class="text-xs text-muted-foreground">Klik of sleep afbeelding</span><span class="text-xs text-muted-foreground">JPG, PNG, WebP (max. 5MB)</span></div><input type="file" class="hero-image-file-input hidden" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" data-section-key="' + sectionKey + '" data-field="' + field + '"></div>' +
                    '<input type="hidden" name="home_sections[' + sectionKey + '][items][' + nextIndex + '][image_url]" id="hero-' + sectionKey + '-' + field + '" value=""></div></div>' +
                    '<div class="flex flex-col gap-2 mt-3"><div class="flex items-center gap-3"><label class="text-sm text-muted-foreground shrink-0 w-40">Tarief</label><select name="home_sections[' + sectionKey + '][items][' + nextIndex + '][rate_type]" class="kt-input w-48 text-sm"><option value="1-4" selected>' + rateOpt1 + '</option><option value="5-8">' + rateOpt2 + '</option><option value="overige_kosten">' + rateOpt3 + '</option></select></div><div class="flex items-center gap-3"><label class="text-sm text-muted-foreground shrink-0 w-40">Titel kaart</label><input type="text" name="home_sections[' + sectionKey + '][items][' + nextIndex + '][title]" class="kt-input flex-1 max-w-md text-sm" value="" placeholder="bijv. t/m 4 personen"></div><div class="flex items-center gap-3"><label class="text-sm text-muted-foreground shrink-0 w-40">Override overige kosten (€)</label><input type="number" name="home_sections[' + sectionKey + '][items][' + nextIndex + '][cleaning_costs]" class="kt-input w-28 text-sm" value="" step="0.01" min="0" placeholder="leeg = uit tarief"><span class="text-xs text-muted-foreground">Optioneel</span></div>' +
                    '<div class="flex flex-col gap-3 pt-2 border-t border-border"><div class="flex items-center gap-3"><label class="text-sm text-muted-foreground shrink-0 w-40">Kaartgrootte</label><select name="home_sections[' + sectionKey + '][items][' + nextIndex + '][card_size]" class="kt-input w-36 text-sm">' + cardSizeOpts + '</select></div><div class="flex items-center gap-3"><label class="text-sm text-muted-foreground shrink-0 w-40">Stijl</label><select name="home_sections[' + sectionKey + '][items][' + nextIndex + '][font_style]" class="kt-input w-28 text-sm">' + fontStyleOpts + '</select></div><div class="flex items-center gap-3"><label class="text-sm text-muted-foreground shrink-0 w-40">Titel lettertype</label><select name="home_sections[' + sectionKey + '][items][' + nextIndex + '][title_font_family]" class="kt-input w-40 text-sm">' + fontFamilyOpts + '</select></div><div class="flex items-center gap-3"><label class="text-sm text-muted-foreground shrink-0 w-40">Titel lettergrootte</label><select name="home_sections[' + sectionKey + '][items][' + nextIndex + '][title_font_size]" class="kt-input w-28 text-sm">' + fontSizeOpts + '</select></div><div class="flex items-center gap-3"><label class="text-sm text-muted-foreground shrink-0 w-40">Titel stijl</label><select name="home_sections[' + sectionKey + '][items][' + nextIndex + '][title_font_style]" class="kt-input w-28 text-sm">' + fontStyleOpts + '</select></div><div class="flex items-center gap-3"><label class="text-sm text-muted-foreground shrink-0 w-40">Titel uitlijning</label><select name="home_sections[' + sectionKey + '][items][' + nextIndex + '][title_align]" class="kt-input w-28 text-sm">' + textAlignOpts + '</select></div><div class="flex items-center gap-3"><label class="text-sm text-muted-foreground shrink-0 w-40">Label lettergrootte</label><select name="home_sections[' + sectionKey + '][items][' + nextIndex + '][label_font_size]" class="kt-input w-28 text-sm">' + fontSizeOpts + '</select></div><div class="flex items-center gap-3"><label class="text-sm text-muted-foreground shrink-0 w-40">Waarde lettergrootte</label><select name="home_sections[' + sectionKey + '][items][' + nextIndex + '][value_font_size]" class="kt-input w-28 text-sm">' + fontSizeOpts + '</select></div><div class="flex items-center gap-3"><label class="text-sm text-muted-foreground shrink-0 w-40">Uitlijning</label><select name="home_sections[' + sectionKey + '][items][' + nextIndex + '][text_align]" class="kt-input w-28 text-sm">' + textAlignOpts + '</select></div><div class="flex items-center gap-3"><label class="text-sm text-muted-foreground shrink-0 w-40">Padding afbeelding</label><select name="home_sections[' + sectionKey + '][items][' + nextIndex + '][image_padding]" class="kt-input w-24 text-sm">' + paddingOpts + '</select></div><div class="flex items-center gap-3"><label class="text-sm text-muted-foreground shrink-0 w-40">Achtergrondkleur</label><div class="flex items-center gap-2"><input type="color" id="taxiroyaal-' + sectionKey + '-item-' + nextIndex + '-image-bg" class="h-10 w-14 cursor-pointer rounded border border-input" value="#e5e7eb"><input type="text" name="home_sections[' + sectionKey + '][items][' + nextIndex + '][image_bg_color]" class="kt-input w-24 font-mono text-sm" value="" data-sync-from="taxiroyaal-' + sectionKey + '-item-' + nextIndex + '-image-bg"><button type="button" class="hex-clear-btn kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" data-color-default="#e5e7eb">' + hexClearSvg + '</button></div></div><div class="flex items-center gap-3"><label class="text-sm text-muted-foreground shrink-0 w-40">Tekstkleur</label><div class="flex items-center gap-2"><input type="color" id="taxiroyaal-' + sectionKey + '-item-' + nextIndex + '-text-color" class="h-10 w-14 cursor-pointer rounded border border-input" value="#374151"><input type="text" name="home_sections[' + sectionKey + '][items][' + nextIndex + '][text_color]" class="kt-input w-24 font-mono text-sm" value="" data-sync-from="taxiroyaal-' + sectionKey + '-item-' + nextIndex + '-text-color"><button type="button" class="hex-clear-btn kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" data-color-default="#374151">' + hexClearSvg + '</button></div></div></div></div>';
                container.appendChild(div);
                reindexTaxiroyaalItems(container, sectionKey);
                updateTaxiroyaalImageLabel(div);
                if (typeof window.bindColorSyncIn === 'function') window.bindColorSyncIn(div);
                var newArea = div.querySelector('.hero-image-upload-area');
                if (newArea && heroImageUploadUrl) (function() {
                    var area = newArea;
                    var sk = sectionKey;
                    var f = field;
                    var scope = div;
                    var fileInput = scope.querySelector('.hero-image-file-input[data-section-key="' + sk + '"][data-field="' + f + '"]');
                    var urlInput = document.getElementById('hero-' + sk + '-' + f);
                    var preview = document.getElementById('hero-' + sk + '-' + f + '-preview');
                    if (!fileInput || !urlInput) return;
                    function handleFile(file) {
                        if (!file) return;
                        var allowed = ['image/jpeg','image/png','image/jpg','image/gif','image/webp'];
                        if (!allowed.includes(file.type)) { alert('Alleen JPEG, PNG, JPG, GIF en WebP.'); fileInput.value = ''; return; }
                        if (file.size > 5 * 1024 * 1024) { alert('Max. 5MB.'); fileInput.value = ''; return; }
                        var fd = new FormData();
                        fd.append('image', file);
                        fd.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                        fetch(heroImageUploadUrl, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, credentials: 'same-origin' }).then(function(r) { return r.ok ? r.json() : r.json().then(function(d) { throw new Error(d.message || 'Upload mislukt'); }); }).then(function(d) { if (d.success && d.url) { urlInput.value = d.url; if (preview) { preview.src = d.url; preview.classList.remove('hidden'); } } }).catch(function(err) { alert(err.message || 'Upload mislukt'); });
                        fileInput.value = '';
                    }
                    area.addEventListener('click', function(ev) { ev.preventDefault(); fileInput.click(); });
                    fileInput.addEventListener('change', function() { if (this.files && this.files.length) handleFile(this.files[0]); });
                })();
                return;
            }
            var removeBtn = e.target.closest('.taxiroyaal-tarieven-item-remove');
            if (removeBtn) {
                e.preventDefault();
                var row = removeBtn.closest('.taxiroyaal-tarieven-item');
                var container = row && row.parentElement;
                if (row && container && container.querySelectorAll('.taxiroyaal-tarieven-item').length > 1) {
                    row.remove();
                    reindexTaxiroyaalItems(container, container.getAttribute('data-section-key') || '');
                }
            }
        });
        document.querySelectorAll('.taxiroyaal-tarieven-item').forEach(function(row) { updateTaxiroyaalImageLabel(row); });
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
                if (el.getAttribute && el.getAttribute('data-editor-id') && el.getAttribute('data-editor-id').indexOf(baseType) !== -1) {
                    el.setAttribute('data-editor-id', el.getAttribute('data-editor-id').replace(new RegExp(baseType.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g'), newKey));
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
            /* Flowbite WYSIWYG (Tiptap): initialiseer editors in de nieuwe sectie; geen TinyMCE op deze textareas */
            var flowbiteWrappers = clone.querySelectorAll('[data-flowbite-wysiwyg]');
            if (flowbiteWrappers.length && typeof window.initFlowbiteWysiwyg === 'function') {
                window.initFlowbiteWysiwyg(clone);
            }
            var textareasToInit = [];
            clone.querySelectorAll('textarea').forEach(function(ta) {
                if (ta.closest('[data-flowbite-wysiwyg]')) return; /* skip Flowbite-editor textarea */
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

        function reindexTaxiroyaalBookingList(listEl) {
            if (!listEl) return;
            var sectionKey = listEl.getAttribute('data-section-key') || '';
            var listName = listEl.getAttribute('data-list') || '';
            var rowName = listName === 'special' ? 'special_items' : (listName === 'offers' ? 'offers' : 'baggage_items');
            var rows = listEl.querySelectorAll('.taxiroyaal-booking-row');
            rows.forEach(function(row, index) {
                row.querySelectorAll('input[name], select[name], textarea[name]').forEach(function(field) {
                    field.name = field.name.replace(/home_sections\[[^\]]+\]\[[^\]]+\]\[\d+\]/, 'home_sections[' + sectionKey + '][' + rowName + '][' + index + ']');
                });
            });
        }

        function buildTaxiroyaalBookingRowHtml(listName, sectionKey, index) {
            if (listName === 'offers') {
                var vehicleOptions = '<option value="">Automatisch</option>';
                var container = document.querySelector('.taxiroyaal-booking-list[data-section-key="' + sectionKey + '"][data-list="offers"]');
                if (container) {
                    try {
                        var vehicleSelect = container.closest('.home-section-card-body').querySelector('select[name*="[offers][0][vehicle_id]"]');
                        if (vehicleSelect) {
                            vehicleOptions = vehicleSelect.innerHTML;
                        }
                    } catch (e) {}
                }
                return '<div class="overflow-x-auto"><div class="grid gap-x-2 gap-y-1 items-center border border-border rounded p-2 taxiroyaal-booking-row" data-list="offers" style="min-width: 800px; grid-template-columns: minmax(56px, 0.6fr) minmax(100px, 1.6fr) minmax(115px, 1.2fr) minmax(100px, 1.2fr) minmax(115px, 1.3fr) minmax(56px, 0.55fr) minmax(56px, 0.55fr) auto;">'
                    + '<label class="text-xs text-muted-foreground">ID</label>'
                    + '<label class="text-xs text-muted-foreground">Titel</label>'
                    + '<label class="text-xs text-muted-foreground">Badge</label>'
                    + '<label class="text-xs text-muted-foreground">Personen</label>'
                    + '<label class="text-xs text-muted-foreground">Voertuig</label>'
                    + '<label class="text-xs text-muted-foreground">x prijs</label>'
                    + '<label class="text-xs text-muted-foreground">x oud</label>'
                    + '<div class="text-right shrink-0"></div>'
                    + '<div class="min-w-0"><input class="kt-input w-full min-w-0 text-sm" name="home_sections[' + sectionKey + '][offers][' + index + '][id]" value="offer_' + (index + 1) + '"></div>'
                    + '<div class="min-w-0"><input class="kt-input w-full min-w-0 text-sm" name="home_sections[' + sectionKey + '][offers][' + index + '][title]" value=""></div>'
                    + '<div class="min-w-0"><input class="kt-input w-full min-w-0 text-sm" name="home_sections[' + sectionKey + '][offers][' + index + '][badge]" value="Standaard taxi"></div>'
                    + '<div class="min-w-0"><select class="kt-input w-full min-w-0 text-sm" name="home_sections[' + sectionKey + '][offers][' + index + '][person_range]"><option value="">Alle personen</option><option value="1-4">t/m 4 personen</option><option value="5-8">5 t/m 8 personen</option></select></div>'
                    + '<div class="min-w-0"><select class="kt-input w-full min-w-0 text-sm" name="home_sections[' + sectionKey + '][offers][' + index + '][vehicle_id]">' + vehicleOptions + '</select></div>'
                    + '<div class="min-w-0"><input type="number" min="0.1" step="0.05" class="kt-input w-full min-w-0 text-sm" name="home_sections[' + sectionKey + '][offers][' + index + '][price_multiplier]" value="1"></div>'
                    + '<div class="min-w-0"><input type="number" min="1" step="0.05" class="kt-input w-full min-w-0 text-sm" name="home_sections[' + sectionKey + '][offers][' + index + '][old_price_multiplier]" value="1.2"></div>'
                    + '<div class="text-right shrink-0"><button type="button" class="kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-destructive taxiroyaal-booking-item-remove">x</button></div>'
                    + '<div class="min-w-0" style="grid-column: 1 / -1;"><label class="text-xs text-muted-foreground block">Features (1 per regel)</label><textarea class="kt-input w-full min-w-0 text-sm pt-1" rows="2" name="home_sections[' + sectionKey + '][offers][' + index + '][features_text]"></textarea></div>'
                    + '</div></div>';
            }

            if (listName === 'special') {
                return '<div class="grid w-full gap-2 items-end border border-border rounded p-2 taxiroyaal-booking-row" data-list="special" style="grid-template-columns: minmax(0, 1.2fr) minmax(0, 3.8fr) minmax(0, 1.2fr) minmax(0, 0.8fr) auto;">'
                    + '<div><label class="text-xs">Key</label><input class="kt-input w-full text-sm" name="home_sections[' + sectionKey + '][special_items][' + index + '][key]" value=""></div>'
                    + '<div><label class="text-xs">Titel</label><input class="kt-input w-full text-sm" name="home_sections[' + sectionKey + '][special_items][' + index + '][title]" value=""></div>'
                    + '<div><label class="text-xs">Prijs</label><div class="relative"><span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">€</span><input type="number" min="0" step="0.01" class="kt-input w-full text-sm pl-6" name="home_sections[' + sectionKey + '][special_items][' + index + '][price]" value="0"></div></div>'
                    + '<div><label class="text-xs">Max</label><input type="number" min="0" max="20" class="kt-input w-full text-sm" name="home_sections[' + sectionKey + '][special_items][' + index + '][max_qty]" value="4"></div>'
                    + '<div class="text-right"><button type="button" class="kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-destructive taxiroyaal-booking-item-remove">x</button></div>'
                    + '</div>';
            }

            return '<div class="grid w-full gap-2 items-end border border-border rounded p-2 taxiroyaal-booking-row" data-list="baggage" style="grid-template-columns: minmax(0, 1.1fr) minmax(0, 2.6fr) minmax(0, 2.6fr) minmax(0, 1.2fr) minmax(0, 0.7fr) auto;">'
                + '<div><label class="text-xs">Key</label><input class="kt-input w-full text-sm" name="home_sections[' + sectionKey + '][baggage_items][' + index + '][key]" value=""></div>'
                + '<div><label class="text-xs">Titel</label><input class="kt-input w-full text-sm" name="home_sections[' + sectionKey + '][baggage_items][' + index + '][title]" value=""></div>'
                + '<div><label class="text-xs">Subtitel</label><input class="kt-input w-full text-sm" name="home_sections[' + sectionKey + '][baggage_items][' + index + '][subtitle]" value=""></div>'
                + '<div><label class="text-xs">Prijs</label><div class="relative"><span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">€</span><input type="number" min="0" step="0.01" class="kt-input w-full text-sm pl-6" name="home_sections[' + sectionKey + '][baggage_items][' + index + '][price]" value="0"></div></div>'
                + '<div><label class="text-xs">Max</label><input type="number" min="0" max="20" class="kt-input w-full text-sm" name="home_sections[' + sectionKey + '][baggage_items][' + index + '][max_qty]" value="4"></div>'
                + '<div class="text-right"><button type="button" class="kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-destructive taxiroyaal-booking-item-remove">x</button></div>'
                + '</div>';
        }

        function refreshTaxiroyaalStepHeadings(scopeEl) {
            var scope = scopeEl || document;
            var stepOrderSelects = scope.querySelectorAll('select[name$="[step_order][]"]');
            if (!stepOrderSelects.length) return;
            var stepByKey = {};
            stepOrderSelects.forEach(function(select, idx) {
                var key = String(select.value || '').trim();
                if (key) stepByKey[key] = idx + 1;
            });
            scope.querySelectorAll('[data-step-heading]').forEach(function(heading) {
                var key = String(heading.getAttribute('data-step-key') || '').trim();
                if (!key) return;
                var base = heading.getAttribute('data-step-heading-base') || heading.textContent || '';
                var stepNumber = stepByKey[key];
                heading.textContent = base + ' (Stap ' + (stepNumber || '—') + ')';
            });
        }

        function syncTaxiroyaalOfferVehicleOptions(rowEl) {
            if (!rowEl) return;
            var personRangeSelect = rowEl.querySelector('select[name*="[offers]["][name$="[person_range]"]');
            var vehicleSelect = rowEl.querySelector('select[name*="[offers]["][name$="[vehicle_id]"]');
            if (!personRangeSelect || !vehicleSelect) return;

            var selectedRange = String(personRangeSelect.value || '').trim();
            var currentVehicleValue = String(vehicleSelect.value || '');
            var hasCurrentVehicle = false;

            vehicleSelect.querySelectorAll('option').forEach(function(option) {
                var value = String(option.value || '');
                var vehicleRange = String(option.getAttribute('data-person-range') || '').trim();
                var isAutomatic = value === '';
                var visible = isAutomatic || selectedRange === '' || (vehicleRange !== '' && vehicleRange === selectedRange);

                option.hidden = !visible;
                option.disabled = !visible;
                if (visible && value === currentVehicleValue) {
                    hasCurrentVehicle = true;
                }
            });

            if (!hasCurrentVehicle) {
                vehicleSelect.value = '';
            }
        }

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

        document.addEventListener('click', function(e) {
            var addItemBtn = e.target.closest('.taxiroyaal-booking-item-add');
            if (addItemBtn) {
                e.preventDefault();
                var sectionKey = addItemBtn.getAttribute('data-section-key') || '';
                var listName = addItemBtn.getAttribute('data-list') || 'baggage';
                var listEl = document.querySelector('.taxiroyaal-booking-list[data-section-key="' + sectionKey + '"][data-list="' + listName + '"]');
                if (!listEl) return;
                var index = listEl.querySelectorAll('.taxiroyaal-booking-row').length;
                var rowHtml = buildTaxiroyaalBookingRowHtml(listName, sectionKey, index);
                var wrap = document.createElement('div');
                wrap.innerHTML = rowHtml;
                if (wrap.firstElementChild) {
                    listEl.appendChild(wrap.firstElementChild);
                    syncTaxiroyaalOfferVehicleOptions(wrap.firstElementChild);
                }
                reindexTaxiroyaalBookingList(listEl);
                return;
            }
            var removeBtn = e.target.closest('.taxiroyaal-booking-item-remove');
            if (removeBtn) {
                e.preventDefault();
                var row = removeBtn.closest('.taxiroyaal-booking-row');
                var listEl = row ? row.closest('.taxiroyaal-booking-list') : null;
                if (row) row.remove();
                reindexTaxiroyaalBookingList(listEl);
            }
        });

        document.addEventListener('change', function(e) {
            var target = e.target;
            if (!target || !target.matches || !target.matches('select[name$="[step_order][]"]')) return;
            var cardBody = target.closest('.home-section-card-body');
            refreshTaxiroyaalStepHeadings(cardBody || document);
        });

        document.addEventListener('change', function(e) {
            var target = e.target;
            if (!target || !target.matches || !target.matches('select[name*="[offers]["][name$="[person_range]"]')) return;
            var row = target.closest('.taxiroyaal-booking-row');
            syncTaxiroyaalOfferVehicleOptions(row);
        });

        document.querySelectorAll('.home-section-card-body').forEach(function(cardBody) {
            refreshTaxiroyaalStepHeadings(cardBody);
            cardBody.querySelectorAll('.taxiroyaal-booking-row[data-list="offers"]').forEach(function(row) {
                syncTaxiroyaalOfferVehicleOptions(row);
            });
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
    .home-section-header--carousel { background-color: rgb(204 251 241); border-left-color: rgb(20 184 166); }
    .dark .home-section-header--carousel { background-color: rgb(19 78 74 / 0.4); border-left-color: rgb(45 212 191); }
    .home-section-header--carousel .kt-card-title { color: rgb(15 118 110); }
    .dark .home-section-header--carousel .kt-card-title { color: rgb(94 234 212); }
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
@php
$flowbiteWysiwygTemplate = view('admin.website-pages.partials.flowbite-wysiwyg', [
    'editorId' => '__FLOWBITE_EDITOR_ID__',
    'name' => '__FLOWBITE_NAME__',
    'value' => '',
    'textareaId' => '__FLOWBITE_TEXTAREA_ID__',
    'placeholder' => '__FLOWBITE_PLACEHOLDER__'
])->render();
@endphp
<script type="text/template" id="flowbite-wysiwyg-tpl">{!! $flowbiteWysiwygTemplate !!}</script>
<script type="importmap">
{"imports":{"https://esm.sh/v135/prosemirror-model@1.22.3/es2022/prosemirror-model.mjs":"https://esm.sh/v135/prosemirror-model@1.19.3/es2022/prosemirror-model.mjs","https://esm.sh/v135/prosemirror-model@1.22.1/es2022/prosemirror-model.mjs":"https://esm.sh/v135/prosemirror-model@1.19.3/es2022/prosemirror-model.mjs"}}
</script>
<script src="{{ asset('js/flowbite-wysiwyg-init.js') }}"></script>
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
    // Sync editors naar textareas vóór form submit
    var form = document.getElementById('website-page-form');
    if (form) {
        form.addEventListener('submit', function() {
            if (typeof tinymce !== 'undefined' && tinymce.editors) tinymce.triggerSave();
            if (typeof window.syncAllFlowbiteWysiwygEditors === 'function') window.syncAllFlowbiteWysiwygEditors();
        }, true);
    }
})();
</script>
@endpush
