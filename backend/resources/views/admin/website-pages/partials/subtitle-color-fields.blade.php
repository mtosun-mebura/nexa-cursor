@php
    $sectionKey = $sectionKey ?? 'hero';
    $sectionData = $sectionData ?? [];
    $subtitleColorDefault = $subtitleColorDefault ?? '#bfdbfe';
    $subtitleColor = old('home_sections.'.$sectionKey.'.subtitle_color', $sectionData['subtitle_color'] ?? '');
    $subtitleColor = is_string($subtitleColor) ? trim($subtitleColor) : '';
    if ($subtitleColor !== '' && ! preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $subtitleColor)) {
        $subtitleColor = '';
    }
    $subtitleColorPicker = $subtitleColor !== '' ? $subtitleColor : $subtitleColorDefault;
@endphp
<div class="mb-3 max-w-md">
    <label class="block text-sm font-medium text-secondary-foreground mb-1" for="hero-{{ $sectionKey }}-subtitle_color">Kleur ondertitel</label>
    <div class="flex items-center gap-2">
        <input type="color"
               id="hero-{{ $sectionKey }}-subtitle_color_color"
               class="hero-subtitle-color-picker h-10 w-14 rounded border border-input cursor-pointer shrink-0"
               value="{{ $subtitleColorPicker }}"
               title="Kleur kiezen"
               data-target-input="hero-{{ $sectionKey }}-subtitle_color">
        <input type="text"
               name="home_sections[{{ $sectionKey }}][subtitle_color]"
               id="hero-{{ $sectionKey }}-subtitle_color"
               class="kt-input flex-1 font-mono text-sm hero-subtitle-color-hex-input"
               value="{{ $subtitleColor }}"
               placeholder="{{ $subtitleColorDefault }}"
               maxlength="7">
    </div>
    <p class="text-xs text-muted-foreground mt-1">Standaardkleur van de ondertitel op de website. Leeg = themakleur. Inline kleuren uit de editor blijven van kracht.</p>
</div>
