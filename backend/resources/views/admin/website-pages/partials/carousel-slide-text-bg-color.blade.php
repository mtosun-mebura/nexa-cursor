@php
    $sectionKey = $sectionKey ?? 'carousel';
    $idx = (int) ($idx ?? 0);
    $slideTextBgColor = old('home_sections.'.$sectionKey.'.items.'.$idx.'.text_bg_color', $item['text_bg_color'] ?? '');
    $slideTextBgColor = is_string($slideTextBgColor) ? trim($slideTextBgColor) : '';
    if ($slideTextBgColor !== '' && ! preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $slideTextBgColor)) {
        $slideTextBgColor = '';
    }
    $slideTextBgColorPicker = $slideTextBgColor !== '' ? $slideTextBgColor : '#000000';
@endphp
<div class="flex items-center gap-2 mt-1">
    <span class="text-xs font-medium text-muted-foreground shrink-0 w-24">Achtergrond</span>
    <input type="color"
           id="carousel-bg-color-{{ $sectionKey }}-{{ $idx }}_color"
           class="carousel-slide-text-bg-color-picker h-10 w-14 rounded border border-input cursor-pointer shrink-0"
           value="{{ $slideTextBgColorPicker }}"
           title="Achtergrondkleur tekstblok"
           data-target-input="carousel-bg-color-{{ $sectionKey }}-{{ $idx }}">
    <input type="text"
           name="home_sections[{{ $sectionKey }}][items][{{ $idx }}][text_bg_color]"
           id="carousel-bg-color-{{ $sectionKey }}-{{ $idx }}"
           class="carousel-slide-text-bg-color-hex-input kt-input w-24 font-mono text-sm shrink-0"
           value="{{ $slideTextBgColor }}"
           placeholder="#000000"
           maxlength="7">
    <span class="text-xs text-muted-foreground">Leeg = donker semi-transparant</span>
</div>
