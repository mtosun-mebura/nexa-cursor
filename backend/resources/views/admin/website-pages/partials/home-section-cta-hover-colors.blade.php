@php
    $hoverPrefix = $hoverPrefix ?? 'cta_primary';
    $hoverDefaults = $hoverDefaults ?? ['bg' => '#ffffff', 'text' => '#1e3a8a', 'border' => '#1e40af'];
@endphp
<div class="flex items-center gap-3">
    <label class="block text-xs font-medium text-muted-foreground w-28 shrink-0">Hover achtergrond</label>
    <div class="flex items-center gap-2">
        <input type="color" id="{{ $sectionKey }}-{{ $hoverPrefix }}-hover-bg" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($sectionData[$hoverPrefix.'_hover_bg'] ?? '') ?: $hoverDefaults['bg'] }}" title="Hover achtergrond">
        <input type="text" name="home_sections[{{ $sectionKey }}][{{ $hoverPrefix }}_hover_bg]" class="kt-input font-mono text-sm home-section-hex-input" value="{{ old('home_sections.'.$sectionKey.'.'.$hoverPrefix.'_hover_bg', $sectionData[$hoverPrefix.'_hover_bg'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="{{ $sectionKey }}-{{ $hoverPrefix }}-hover-bg">
        <button type="button" class="hex-clear-btn kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Leegmaken" aria-label="Leegmaken" data-color-default="{{ $hoverDefaults['bg'] }}"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
    </div>
</div>
<div class="flex items-center gap-3">
    <label class="block text-xs font-medium text-muted-foreground w-28 shrink-0">Hover tekstkleur</label>
    <div class="flex items-center gap-2">
        <input type="color" id="{{ $sectionKey }}-{{ $hoverPrefix }}-hover-text-color" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($sectionData[$hoverPrefix.'_hover_text_color'] ?? '') ?: $hoverDefaults['text'] }}" title="Hover tekstkleur">
        <input type="text" name="home_sections[{{ $sectionKey }}][{{ $hoverPrefix }}_hover_text_color]" class="kt-input font-mono text-sm home-section-hex-input" value="{{ old('home_sections.'.$sectionKey.'.'.$hoverPrefix.'_hover_text_color', $sectionData[$hoverPrefix.'_hover_text_color'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="{{ $sectionKey }}-{{ $hoverPrefix }}-hover-text-color">
        <button type="button" class="hex-clear-btn kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Leegmaken" aria-label="Leegmaken" data-color-default="{{ $hoverDefaults['text'] }}"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
    </div>
</div>
<div class="flex items-center gap-3">
    <label class="block text-xs font-medium text-muted-foreground w-28 shrink-0">Hover border</label>
    <div class="flex items-center gap-2">
        <input type="color" id="{{ $sectionKey }}-{{ $hoverPrefix }}-hover-border" class="h-10 w-14 cursor-pointer rounded border border-input" value="{{ $hexForPicker($sectionData[$hoverPrefix.'_hover_border'] ?? '') ?: $hoverDefaults['border'] }}" title="Hover borderkleur">
        <input type="text" name="home_sections[{{ $sectionKey }}][{{ $hoverPrefix }}_hover_border]" class="kt-input font-mono text-sm home-section-hex-input" value="{{ old('home_sections.'.$sectionKey.'.'.$hoverPrefix.'_hover_border', $sectionData[$hoverPrefix.'_hover_border'] ?? '') }}" placeholder="#hex" maxlength="7" data-sync-from="{{ $sectionKey }}-{{ $hoverPrefix }}-hover-border">
        <button type="button" class="hex-clear-btn kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Leegmaken" aria-label="Leegmaken" data-color-default="{{ $hoverDefaults['border'] }}"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
    </div>
</div>
