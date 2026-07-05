@php
    $sectionKey = $sectionKey ?? 'hero';
    $sectionData = $sectionData ?? [];
    $textBgColor = old('home_sections.'.$sectionKey.'.text_bg_color', $sectionData['text_bg_color'] ?? '');
    $textBgColor = is_string($textBgColor) ? trim($textBgColor) : '';
    if ($textBgColor !== '' && ! preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $textBgColor)) {
        $textBgColor = '';
    }
    $textBgColorPicker = $textBgColor !== '' ? $textBgColor : '#000000';
    $textBgOpacityRaw = old('home_sections.'.$sectionKey.'.text_bg_opacity', $sectionData['text_bg_opacity'] ?? null);
    $hasStoredOpacity = $textBgOpacityRaw !== null && $textBgOpacityRaw !== '';
    $textBgOpacitySlider = $hasStoredOpacity
        ? max(0, min(100, (int) $textBgOpacityRaw))
        : ($textBgColor !== '' ? 88 : 78);
    $colorId = 'hero-text-bg-'.$sectionKey;
    $opacityId = 'hero-text-bg-opacity-'.$sectionKey;
    $textBgWidthPct = (int) old('home_sections.'.$sectionKey.'.text_bg_width_percent', $sectionData['text_bg_width_percent'] ?? 70);
    $textBgWidthPct = max(30, min(100, $textBgWidthPct));
@endphp
<div class="hero-text-bg-fields rounded-lg border border-border p-3 bg-muted/20 space-y-1">
    <p class="text-sm font-medium text-secondary-foreground mb-2">Achtergrond tekstblok</p>
    <p class="text-xs text-muted-foreground mb-2">Kleurvlak achter titel en ondertitel, zoals bij slider-tekst.</p>
    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1">
        <span class="text-xs font-medium text-muted-foreground shrink-0 w-24">Achtergrond</span>
        <input type="color"
               id="{{ $colorId }}_color"
               class="carousel-slide-text-bg-color-picker h-10 w-14 rounded border border-input cursor-pointer shrink-0"
               value="{{ $textBgColorPicker }}"
               title="Achtergrondkleur tekstblok"
               data-target-input="{{ $colorId }}">
        <div class="carousel-slide-hex-input-wrap relative shrink-0 flex-none w-[90px] min-w-[90px] max-w-[90px]">
            <input type="text"
                   name="home_sections[{{ $sectionKey }}][text_bg_color]"
                   id="{{ $colorId }}"
                   class="carousel-slide-text-bg-color-hex-input kt-input w-full font-mono text-sm home-section-hex-input"
                   value="{{ $textBgColor }}"
                   placeholder="#000000"
                   maxlength="7"
                   data-skip-validation-wrapper="1">
        </div>
    </div>
    <div class="carousel-slide-bg-opacity-control flex flex-wrap items-center gap-x-2 gap-y-1 w-full basis-full">
        <span class="text-xs font-medium text-muted-foreground shrink-0 w-24">Transparantie</span>
        <div class="carousel-slide-text-bg-opacity-slider hero-overlay-opacity-slider flex-1 min-w-[8rem] max-w-[14rem]">
            <input type="range"
                   name="home_sections[{{ $sectionKey }}][text_bg_opacity]"
                   id="{{ $opacityId }}"
                   class="carousel-slide-text-bg-opacity-range hero-overlay-opacity-range w-full"
                   min="0"
                   max="100"
                   step="1"
                   value="{{ $textBgOpacitySlider }}"
                   aria-valuemin="0"
                   aria-valuemax="100"
                   aria-valuenow="{{ $textBgOpacitySlider }}"
                   aria-describedby="{{ $opacityId }}-value"
                   title="0 = volledig doorzichtig, 100 = ondoorzichtig">
        </div>
        <span id="{{ $opacityId }}-value"
              class="carousel-slide-text-bg-opacity-value inline-flex items-center justify-center min-w-[2.75rem] rounded-md bg-muted px-2 py-0.5 text-xs font-semibold tabular-nums text-foreground shrink-0"
              aria-live="polite">{{ $textBgOpacitySlider }}%</span>
        <span id="{{ $opacityId }}-preview"
              class="carousel-slide-text-bg-opacity-preview h-10 w-10 rounded border border-input shrink-0 shadow-sm"
              title="Voorbeeld achtergrondvlak"
              role="img"
              aria-label="Voorbeeld achtergrond"></span>
    </div>
    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 w-full basis-full">
        <span class="text-xs font-medium text-muted-foreground shrink-0 w-24">Breedte</span>
        <select name="home_sections[{{ $sectionKey }}][text_bg_width_percent]" class="kt-input text-sm w-full max-w-[8rem]" title="Breedte tekstblok op banner (%)">
            @foreach ([100, 90, 80, 70, 60, 50, 40, 30] as $pct)
                <option value="{{ $pct }}" @selected($textBgWidthPct === $pct)>{{ $pct }}%</option>
            @endforeach
        </select>
        <span class="text-xs text-muted-foreground">Ten opzichte van sectiebreedte op banner</span>
    </div>
</div>
