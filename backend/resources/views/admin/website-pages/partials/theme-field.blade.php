@php
    $selectableThemes = $selectableThemes ?? \App\Models\FrontendTheme::getAllActive();
    $selectedThemeId = old('frontend_theme_id', $selectedThemeId ?? $defaultTheme?->id ?? '');
    $companyTheme = $companyTheme ?? null;
    $selectedTheme = $selectableThemes->firstWhere('id', (int) $selectedThemeId)
        ?? ($defaultTheme ?? null);
@endphp
@if($selectableThemes->isEmpty())
    <span class="text-sm text-muted-foreground">Geen gepubliceerd thema beschikbaar.</span>
    <input type="hidden" name="frontend_theme_id" id="frontend_theme_id" value="">
@elseif($selectableThemes->count() === 1)
    @php $onlyTheme = $selectableThemes->first(); @endphp
    <input type="hidden" name="frontend_theme_id" id="frontend_theme_id" value="{{ $onlyTheme->id }}">
    <span class="inline-flex items-center rounded-md bg-orange-100 px-3 py-1.5 text-sm font-medium text-orange-900 border border-orange-200 dark:bg-orange-500/20 dark:text-orange-100 dark:border-orange-400/40">{{ $onlyTheme->name }}</span>
@else
    <select name="frontend_theme_id"
            id="frontend_theme_id"
            class="kt-input max-w-md @error('frontend_theme_id') border-destructive @enderror"
            required>
        @foreach($selectableThemes as $theme)
            <option value="{{ $theme->id }}"
                    data-theme-slug="{{ $theme->slug }}"
                    {{ (string) $selectedThemeId === (string) $theme->id ? 'selected' : '' }}>
                {{ $theme->name }}
            </option>
        @endforeach
    </select>
    @error('frontend_theme_id')
        <div class="text-xs text-destructive mt-1">{{ $message }}</div>
    @enderror
@endif
<div class="text-xs text-muted-foreground mt-1">
    @if($companyTheme && ($selectableThemes->count() > 1))
        Standaard bedrijfsthema: <strong>{{ $companyTheme->name }}</strong>. De keuze hier geldt voor deze pagina.
    @elseif($selectableThemes->count() > 1)
        Kies uit de gepubliceerde frontend-thema's. Bij een module wordt het module-thema voorgesteld; je kunt handmatig wisselen.
    @else
        Pagina's worden getoond in het enige gepubliceerde thema.
    @endif
</div>
