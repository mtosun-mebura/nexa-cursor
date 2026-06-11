@php
    $sectionKey = $sectionKey ?? 'carousel';
    $idx = (int) ($idx ?? 0);
    $slideTextBgColor = old('home_sections.'.$sectionKey.'.items.'.$idx.'.text_bg_color', $item['text_bg_color'] ?? '');
    $slideTextBgColor = is_string($slideTextBgColor) ? trim($slideTextBgColor) : '';
    if ($slideTextBgColor !== '' && ! preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $slideTextBgColor)) {
        $slideTextBgColor = '';
    }
    $slideTextBgColorPicker = $slideTextBgColor !== '' ? $slideTextBgColor : '#000000';
    $slideTextBgOpacityRaw = old('home_sections.'.$sectionKey.'.items.'.$idx.'.text_bg_opacity', $item['text_bg_opacity'] ?? null);
    $hasStoredOpacity = $slideTextBgOpacityRaw !== null && $slideTextBgOpacityRaw !== '';
    $slideTextBgOpacitySlider = $hasStoredOpacity
        ? max(0, min(100, (int) $slideTextBgOpacityRaw))
        : ($slideTextBgColor !== '' ? 88 : 78);
    $opacityId = 'carousel-bg-opacity-'.$sectionKey.'-'.$idx;
@endphp
<div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1">
    <span class="text-xs font-medium text-muted-foreground shrink-0 w-24">Achtergrond</span>
    <input type="color"
           id="carousel-bg-color-{{ $sectionKey }}-{{ $idx }}_color"
           class="carousel-slide-text-bg-color-picker h-10 w-14 rounded border border-input cursor-pointer shrink-0"
           value="{{ $slideTextBgColorPicker }}"
           title="Achtergrondkleur tekstblok"
           data-target-input="carousel-bg-color-{{ $sectionKey }}-{{ $idx }}">
    <div class="carousel-slide-hex-input-wrap relative shrink-0 flex-none w-[90px] min-w-[90px] max-w-[90px]">
        <input type="text"
               name="home_sections[{{ $sectionKey }}][items][{{ $idx }}][text_bg_color]"
               id="carousel-bg-color-{{ $sectionKey }}-{{ $idx }}"
               class="carousel-slide-text-bg-color-hex-input kt-input w-full font-mono text-sm home-section-hex-input"
               value="{{ $slideTextBgColor }}"
               placeholder="#000000"
               maxlength="7"
               data-skip-validation-wrapper="1">
    </div>
</div>
<div class="carousel-slide-bg-opacity-control flex flex-wrap items-center gap-x-2 gap-y-1 w-full basis-full">
    <span class="text-xs font-medium text-muted-foreground shrink-0 w-24">Transparantie</span>
    <div class="carousel-slide-text-bg-opacity-slider hero-overlay-opacity-slider flex-1 min-w-[8rem] max-w-[14rem]">
        <input type="range"
               name="home_sections[{{ $sectionKey }}][items][{{ $idx }}][text_bg_opacity]"
               id="{{ $opacityId }}"
               class="carousel-slide-text-bg-opacity-range hero-overlay-opacity-range w-full"
               min="0"
               max="100"
               step="1"
               value="{{ $slideTextBgOpacitySlider }}"
               aria-valuemin="0"
               aria-valuemax="100"
               aria-valuenow="{{ $slideTextBgOpacitySlider }}"
               aria-describedby="{{ $opacityId }}-value"
               title="0 = volledig doorzichtig, 100 = ondoorzichtig">
    </div>
    <span id="{{ $opacityId }}-value"
          class="carousel-slide-text-bg-opacity-value inline-flex items-center justify-center min-w-[2.75rem] rounded-md bg-muted px-2 py-0.5 text-xs font-semibold tabular-nums text-foreground shrink-0"
          aria-live="polite">{{ $slideTextBgOpacitySlider }}%</span>
    <span id="{{ $opacityId }}-preview"
          class="carousel-slide-text-bg-opacity-preview h-10 w-10 rounded border border-input shrink-0 shadow-sm"
          title="Voorbeeld achtergrondvlak"
          role="img"
          aria-label="Voorbeeld achtergrond"></span>
</div>
