@extends('admin.layouts.app')

@section('title', 'Thema bewerken')

@push('styles')
<style>
    #primary_color_picker {
        min-width: 3.5rem;
        min-height: 3.5rem;
        padding: 2px;
        cursor: pointer;
    }
    #primary_color_picker::-webkit-color-swatch-wrapper { padding: 0; }
    #primary_color_picker::-webkit-color-swatch { border: none; border-radius: 6px; }
    #primary_color_picker::-moz-color-swatch { border: none; border-radius: 6px; }
</style>
@endpush

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-col gap-5 pb-7.5">
        <div class="flex flex-wrap items-center justify-between gap-5">
            <h1 class="text-xl font-medium leading-none text-mono">
                Thema: {{ $frontend_theme->name }}
            </h1>
        </div>
        <div class="flex items-center">
            <a href="{{ route('admin.frontend-themes.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
    </div>

    <form action="{{ route('admin.frontend-themes.update', $frontend_theme) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid gap-5 lg:gap-7.5">
            <x-error-card :errors="$errors" />

            @php
                $s = $frontend_theme->settings ?? [];
                $isNewTheme = in_array($frontend_theme->slug, ['atom-v2', 'nextly-template', 'next-landing-vpn'], true);
            @endphp

            @if($isNewTheme)
                <div class="kt-alert kt-alert-info mb-5">
                    <i class="ki-filled ki-information me-2"></i>
                    Dit thema komt uit de thema-map. De onderstaande instellingen (primaire kleur, lettertypen, footertekst) worden toegepast waar dit thema wordt gebruikt. Bij keuze van dit thema voor een module worden de thema-bestanden naar die module gekopieerd zodat de module zelfstandig werkt.
                </div>
            @endif

            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Thema-instellingen
                    </h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">
                                Primaire kleur
                            </td>
                            <td class="min-w-48 w-full">
                                <div class="flex flex-wrap items-center gap-3">
                                    <label class="flex flex-col items-center gap-1.5 cursor-pointer" for="primary_color_picker" title="Klik om kleur te kiezen">
                                        <input type="color"
                                               id="primary_color_picker"
                                               class="w-14 h-14 rounded-lg border-2 border-input cursor-pointer bg-transparent"
                                               value="{{ old('primary_color', $s['primary_color'] ?? '#2563eb') }}"
                                               aria-label="Kies primaire kleur">
                                        <span class="text-xs text-muted-foreground">Klik om te kiezen</span>
                                    </label>
                                    <div class="flex flex-col gap-1">
                                        <input type="text"
                                               name="primary_color"
                                               id="primary_color"
                                               class="kt-input w-32 @error('primary_color') border-destructive @enderror"
                                               value="{{ old('primary_color', $s['primary_color'] ?? '#2563eb') }}"
                                               placeholder="#2563eb"
                                               maxlength="7"
                                               pattern="^#[0-9A-Fa-f]{6}$">
                                        <span class="text-xs text-muted-foreground">Hex (bijv. #2563eb)</span>
                                    </div>
                                </div>
                                @error('primary_color')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Lettertype koppen
                            </td>
                            <td>
                                <input type="text"
                                       name="font_heading"
                                       id="font_heading"
                                       class="kt-input @error('font_heading') border-destructive @enderror"
                                       value="{{ old('font_heading', $s['font_heading'] ?? 'Inter') }}"
                                       placeholder="Inter">
                                @error('font_heading')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Lettertype body
                            </td>
                            <td>
                                <input type="text"
                                       name="font_body"
                                       id="font_body"
                                       class="kt-input @error('font_body') border-destructive @enderror"
                                       value="{{ old('font_body', $s['font_body'] ?? 'Inter') }}"
                                       placeholder="Inter">
                                @error('font_body')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Dark mode in header
                            </td>
                            <td>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="hidden" name="dark_mode_available" value="0">
                                    <input type="checkbox"
                                           name="dark_mode_available"
                                           value="1"
                                           class="kt-checkbox"
                                           {{ old('dark_mode_available', $s['dark_mode_available'] ?? true) ? 'checked' : '' }}>
                                    <span class="text-sm">Toon licht/donker-knoppen in de website-header</span>
                                </label>
                                <div class="text-xs text-muted-foreground mt-1">Indien uitgeschakeld worden de thema-knoppen (licht/donker) niet getoond.</div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                Footertekst
                            </td>
                            <td>
                                <textarea name="footer_text"
                                          id="footer_text"
                                          class="kt-input w-full @error('footer_text') border-destructive @enderror"
                                          rows="3">{{ old('footer_text', $s['footer_text'] ?? '') }}</textarea>
                                <div class="text-xs text-muted-foreground mt-1">Wordt onderaan de website getoond (leeg = standaard © jaar sitenaam)</div>
                                @error('footer_text')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5">
                <a href="{{ route('admin.frontend-themes.index') }}" class="kt-btn kt-btn-outline">
                    <i class="ki-filled ki-cross me-2"></i>
                    Annuleren
                </a>
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-check me-2"></i>
                    Opslaan
                </button>
            </div>
        </div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var picker = document.getElementById('primary_color_picker');
    var textInput = document.getElementById('primary_color');
    if (!picker || !textInput) return;

    function hexValid(val) {
        return /^#[0-9A-Fa-f]{6}$/.test(val);
    }

    picker.addEventListener('input', function() {
        textInput.value = picker.value;
    });

    textInput.addEventListener('input', function() {
        var val = textInput.value.trim();
        if (val.indexOf('#') !== 0) val = '#' + val;
        if (hexValid(val)) {
            picker.value = val;
        }
    });
    textInput.addEventListener('change', function() {
        var val = textInput.value.trim();
        if (val.indexOf('#') !== 0) val = '#' + val;
        if (hexValid(val)) {
            picker.value = val;
            textInput.value = val;
        }
    });
});
</script>
@endsection
