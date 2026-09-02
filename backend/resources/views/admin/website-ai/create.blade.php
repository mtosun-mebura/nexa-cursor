@extends('admin.layouts.app')

@section('title', 'Genereer website AI')

@section('content')
@php
    $presetKey = old('color_preset', 'navy-gold');
    $primary = old('primary_color', '#1e3a8a');
    $secondary = old('secondary_color', '#0f172a');
@endphp
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-col items-start gap-3 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">Genereer website AI</h1>
        <p class="text-sm text-muted-foreground mb-0">Zet een professionele tenant-website op met de bestaande thema’s en componenten van de website builder. Alleen super-admin.</p>
        <a href="{{ route('admin.website-pages.index') }}" class="kt-btn kt-btn-outline">
            <i class="ki-filled ki-arrow-left me-2"></i>
            Naar pagina’s
        </a>
    </div>

    @if($errors->any())
        <div class="kt-alert kt-alert-danger mb-5">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(! $openaiConfigured)
        <div class="kt-alert kt-alert-warning mb-5">
            Er is geen OpenAI API-sleutel gezet (<code>OPENAI_API_KEY</code>). U kunt nog steeds een basiswebsite genereren; teksten en foto’s worden dan niet door het model geschreven.
        </div>
    @endif

    <form id="website-ai-form" action="{{ route('admin.website-ai.generate') }}" method="POST" data-validate="true" novalidate>
        @csrf

        <div class="kt-card w-full min-w-0 mb-6 overflow-hidden">
            <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
                <h3 class="kt-card-title mb-0">Tenant en bron</h3>
            </div>
            <div class="kt-card-content p-5">
                <div class="grid gap-5 lg:grid-cols-2">
                    <div>
                        <label class="kt-form-label mb-1.5" for="company_id">Bedrijf *</label>
                        <select class="kt-select @error('company_id') border-destructive @enderror" id="company_id" name="company_id" required>
                            <option value="">Kies een tenant</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" @selected((int) $selectedCompanyId === (int) $company->id)>
                                    {{ $company->name }}{{ $company->is_active ? '' : ' (inactief)' }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-muted-foreground mt-1.5 mb-0">
                            Pagina’s worden gekoppeld aan deze tenant.
                            @if($existingPageCount > 0)
                                Dit bedrijf heeft nu {{ $existingPageCount }} website-pagina{{ $existingPageCount === 1 ? '' : '’s' }}.
                            @endif
                        </p>
                    </div>
                    <div>
                        <label class="kt-form-label mb-1.5" for="source_url">Huidige / oude website</label>
                        <input type="url" class="kt-input @error('source_url') border-destructive @enderror" id="source_url" name="source_url" value="{{ old('source_url') }}" placeholder="https://www.bedrijf.nl" maxlength="500">
                        <p class="text-xs text-muted-foreground mt-1.5 mb-0">Optioneel. We nemen home, over-ons, diensten en contact door en gebruiken die informatie in de nieuwe site.</p>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="kt-form-label mb-1.5" for="context">Waar moet de website over gaan? *</label>
                        <textarea class="kt-textarea min-h-32 @error('context') border-destructive @enderror" id="context" name="context" required maxlength="4000" placeholder="Bijv. Taxi in Zwolle: luchthavenvervoer, zakelijke ritten en contractvervoer. Toon, doelgroep, diensten, vestigingen…">{{ old('context') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="kt-card w-full min-w-0 mb-6 overflow-hidden">
            <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
                <h3 class="kt-card-title mb-0">Omvang, thema en kleuren</h3>
            </div>
            <div class="kt-card-content p-5">
                <div class="grid gap-5 lg:grid-cols-2">
                    <div>
                        <label class="kt-form-label mb-1.5" for="max_pages">Maximaal aantal pagina’s *</label>
                        <input type="number" class="kt-input @error('max_pages') border-destructive @enderror" id="max_pages" name="max_pages" min="1" max="12" value="{{ old('max_pages', 3) }}" required>
                        <p class="text-xs text-muted-foreground mt-1.5 mb-0">Gelijk aan het aantal pagina’s dat de klant heeft afgenomen (standaard 3: home, over ons, contact). Maximum 12.</p>
                    </div>
                    <div>
                        <label class="kt-form-label mb-1.5" for="frontend_theme_id">Thema *</label>
                        <select class="kt-select @error('frontend_theme_id') border-destructive @enderror" id="frontend_theme_id" name="frontend_theme_id" required>
                            @foreach($themes as $theme)
                                <option value="{{ $theme->id }}" @selected((int) $defaultThemeId === (int) $theme->id)>
                                    {{ $theme->name }}{{ $theme->is_active ? '' : ' (niet gepubliceerd)' }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-muted-foreground mt-1.5 mb-0">Bestaande builder-thema’s en hun componenten (hero, diensten, CTA, boekingsmodule, …).</p>
                    </div>
                    <div class="lg:col-span-2">
                        <span class="kt-form-label mb-2 block">Kleurstelling</span>
                        <div class="flex flex-wrap gap-2" id="website-ai-presets">
                            @foreach($colorPresets as $preset)
                                <button type="button"
                                    class="kt-btn kt-btn-sm kt-btn-outline website-ai-preset {{ $presetKey === $preset['key'] ? 'is-active' : '' }}"
                                    data-key="{{ $preset['key'] }}"
                                    data-primary="{{ $preset['primary'] }}"
                                    data-secondary="{{ $preset['secondary'] }}">
                                    <span class="inline-block size-3 rounded-full border border-white/40 me-1.5" style="background: {{ $preset['primary'] }}"></span>
                                    {{ $preset['label'] }}
                                </button>
                            @endforeach
                        </div>
                        <input type="hidden" name="color_preset" id="color_preset" value="{{ $presetKey }}">
                        <div class="grid gap-4 sm:grid-cols-2 mt-4">
                            <div>
                                <label class="kt-form-label mb-1.5" for="primary_color">Primaire kleur</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" class="h-10 w-14 rounded-md border border-border bg-transparent p-1 cursor-pointer" id="primary_color_picker" value="{{ $primary }}" aria-label="Primaire kleur kiezen">
                                    <input type="text" class="kt-input font-mono @error('primary_color') border-destructive @enderror" id="primary_color" name="primary_color" value="{{ $primary }}" maxlength="7" required>
                                </div>
                            </div>
                            <div>
                                <label class="kt-form-label mb-1.5" for="secondary_color">Secundaire kleur</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" class="h-10 w-14 rounded-md border border-border bg-transparent p-1 cursor-pointer" id="secondary_color_picker" value="{{ $secondary }}" aria-label="Secundaire kleur kiezen">
                                    <input type="text" class="kt-input font-mono @error('secondary_color') border-destructive @enderror" id="secondary_color" name="secondary_color" value="{{ $secondary }}" maxlength="7" required>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-muted-foreground mt-1.5 mb-0">Wordt toegepast op knoppen, overlays en highlights. Het thema bepaalt layout en componenten.</p>
                    </div>
                    <div>
                        <label class="kt-label flex items-center gap-2.5 mb-0 cursor-pointer w-fit">
                            <input type="checkbox" class="kt-checkbox" name="generate_images" value="1" @checked(old('generate_images', true))>
                            <span>Hoge-kwaliteit AI-afbeeldingen genereren (hero per pagina)</span>
                        </label>
                    </div>
                    <div>
                        <label class="kt-label flex items-center gap-2.5 mb-0 cursor-pointer w-fit">
                            <input type="checkbox" class="kt-checkbox" name="replace_existing" value="1" @checked(old('replace_existing'))>
                            <span>Bestaande pagina’s met dezelfde slug overschrijven</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="kt-btn kt-btn-primary" id="website-ai-submit">
                <i class="ki-filled ki-technology-4 me-2"></i>
                Website genereren
            </button>
            <p class="text-xs text-muted-foreground mb-0">Dit kan 1–2 minuten duren (bronwebsite + OpenAI + afbeeldingen).</p>
        </div>
    </form>
</div>

<style>
    .website-ai-preset.is-active {
        border-color: color-mix(in oklab, var(--primary) 45%, var(--border));
        background: color-mix(in oklab, var(--primary) 10%, transparent);
    }
</style>
<script>
(function () {
    var form = document.getElementById('website-ai-form');
    var presetInput = document.getElementById('color_preset');
    var primary = document.getElementById('primary_color');
    var secondary = document.getElementById('secondary_color');
    var primaryPicker = document.getElementById('primary_color_picker');
    var secondaryPicker = document.getElementById('secondary_color_picker');

    document.querySelectorAll('.website-ai-preset').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.website-ai-preset').forEach(function (other) {
                other.classList.remove('is-active');
            });
            btn.classList.add('is-active');
            presetInput.value = btn.getAttribute('data-key') || 'custom';
            primary.value = btn.getAttribute('data-primary') || primary.value;
            secondary.value = btn.getAttribute('data-secondary') || secondary.value;
            primaryPicker.value = primary.value;
            secondaryPicker.value = secondary.value;
        });
    });

    function bindPicker(picker, field) {
        picker.addEventListener('input', function () {
            field.value = picker.value;
            presetInput.value = 'custom';
            document.querySelectorAll('.website-ai-preset').forEach(function (btn) {
                btn.classList.toggle('is-active', btn.getAttribute('data-key') === 'custom');
            });
        });
        field.addEventListener('input', function () {
            if (/^#[0-9A-Fa-f]{6}$/.test(field.value)) {
                picker.value = field.value;
            }
        });
    }
    bindPicker(primaryPicker, primary);
    bindPicker(secondaryPicker, secondary);

    form.addEventListener('submit', function () {
        var submit = document.getElementById('website-ai-submit');
        submit.disabled = true;
        submit.classList.add('opacity-70');
        submit.innerHTML = 'Bezig met genereren…';
    });
})();
</script>
@endsection
