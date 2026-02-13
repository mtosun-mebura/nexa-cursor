@extends('admin.layouts.app')

@section('title', 'Pagina aanmaken')

@section('content')
<div class="kt-container-fixed">
    <p class="text-sm text-muted-foreground mb-5">Kies eerst <strong>bij welke module</strong> deze pagina hoort. Kernpagina's (geen module) gebruik je voor Home, Over ons, Contact en custom pagina's. Het thema waarmee de pagina wordt getoond is per module vastgelegd bij Frontend Thema's.</p>
    <div class="flex flex-col gap-5 pb-7.5">
        <div class="flex flex-wrap items-center justify-between gap-5">
            <h1 class="text-xl font-medium leading-none text-mono">
                Pagina aanmaken
            </h1>
        </div>
        <div class="flex items-center">
            <a href="{{ route('admin.website-pages.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
    </div>

    <form id="website-page-form" action="{{ route('admin.website-pages.store') }}" method="POST">
        @csrf

        <div class="grid gap-5 lg:gap-7.5">
            <x-error-card :errors="$errors" />

            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Pagina-informatie
                    </h3>
                    <label class="kt-label" for="is_active">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox"
                               class="kt-switch kt-switch-sm"
                               id="is_active"
                               name="is_active"
                               value="1"
                               {{ old('is_active', true) ? 'checked' : '' }}/>
                        Actief (zichtbaar op de website)
                    </label>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">
                                Bij welke module hoort deze pagina? *
                            </td>
                            <td class="min-w-48 w-full">
                                <select id="module_choice"
                                        class="kt-input"
                                        required>
                                    <option value="" data-theme-text="Standaardthema: {{ $defaultTheme?->name ?? 'Geen actief' }}"
                                        {{ !old('module_name') ? 'selected' : '' }}>Geen (kernpagina's voor home, over ons, contact)</option>
                                    @foreach($installedModules as $module)
                                        @php
                                            $moduleName = $module->getName();
                                            $moduleModel = $moduleThemes[$moduleName] ?? null;
                                            $themeName = ($moduleModel && $moduleModel->theme) ? $moduleModel->theme->name : ($defaultTheme?->name ?? 'Standaardthema');
                                        @endphp
                                        <option value="{{ $moduleName }}"
                                            data-theme-text="Thema voor {{ $module->getDisplayName() }}: {{ $themeName }}"
                                            {{ old('module_name') === $moduleName ? 'selected' : '' }}>{{ $module->getDisplayName() }}</option>
                                    @endforeach
                                </select>
                                <div class="text-xs text-muted-foreground mt-1">Kernpagina's gebruiken het actieve standaardthema; bij een module het thema van die module. Home, Over ons, Contact en Custom kunnen aan een module gekoppeld worden.</div>
                                <input type="hidden" name="module_name" id="module_name_hidden" value="{{ old('module_name') }}">
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Thema
                            </td>
                            <td>
                                <p id="theme_display" class="text-sm font-medium text-secondary-foreground">
                                    Standaardthema: {{ $defaultTheme?->name ?? 'Geen actief' }}
                                </p>
                            </td>
                        </tr>
                        <tr id="page_type_row">
                            <td class="text-secondary-foreground font-normal">
                                Paginatype *
                            </td>
                            <td>
                                <select name="page_type"
                                        id="page_type"
                                        class="kt-input @error('page_type') border-destructive @enderror"
                                        required>
                                    <option value="custom" {{ old('page_type') === 'custom' ? 'selected' : '' }}>Custom (tekstpagina)</option>
                                    <option value="home" {{ old('page_type') === 'home' ? 'selected' : '' }}>Home</option>
                                    <option value="about" {{ old('page_type') === 'about' ? 'selected' : '' }}>Over ons</option>
                                    <option value="contact" {{ old('page_type') === 'contact' ? 'selected' : '' }}>Contact</option>
                                    <option value="module" {{ old('page_type') === 'module' ? 'selected' : '' }}>Module-pagina</option>
                                </select>
                                @error('page_type')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Titel *
                            </td>
                            <td>
                                <input type="text"
                                       name="title"
                                       id="title"
                                       class="kt-input @error('title') border-destructive @enderror"
                                       value="{{ old('title') }}"
                                       required
                                       autocomplete="off">
                                @error('title')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">
                                Slug *
                            </td>
                            <td class="min-w-48 w-full">
                                <input type="text"
                                       name="slug"
                                       id="slug"
                                       class="kt-input @error('slug') border-destructive @enderror"
                                       value="{{ old('slug') }}"
                                       required
                                       pattern="[a-z0-9\-]+"
                                       placeholder="over-ons"
                                       autocomplete="off">
                                <div class="text-xs text-muted-foreground mt-1">Wordt automatisch ingevuld op basis van de titel. Alleen kleine letters, cijfers en streepjes.</div>
                                @error('slug')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Meta-omschrijving
                            </td>
                            <td>
                                <input type="text"
                                       name="meta_description"
                                       id="meta_description"
                                       class="kt-input @error('meta_description') border-destructive @enderror"
                                       value="{{ old('meta_description') }}">
                                @error('meta_description')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr id="content_blocks_row" style="display: none;">
                            <td class="text-secondary-foreground font-normal align-top">
                                Inhoud (blokken)
                            </td>
                            <td>
                                <p class="text-xs text-muted-foreground mb-3">Alle paginatypes gebruiken secties (Hero, footer, copyright + optioneel extra secties).</p>
                                @include('admin.website-pages.partials.page-builder', ['contentJson' => old('content') ?? ''])
                                @error('content')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Volgorde
                            </td>
                            <td>
                                <input type="number"
                                       name="sort_order"
                                       id="sort_order"
                                       class="kt-input w-24 @error('sort_order') border-destructive @enderror"
                                       value="{{ old('sort_order', 0) }}"
                                       min="0">
                                @error('sort_order')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            @php
                $createPageType = old('page_type', 'home');
                $createThemeSlug = $defaultTheme->slug ?? 'modern';
                $createHomeSections = $createPageType === 'home'
                    ? \App\Models\WebsitePage::defaultHomeSectionsForTheme($createThemeSlug)
                    : \App\Models\WebsitePage::defaultPageSectionsForNonHome($createThemeSlug);
            @endphp
            <div id="home_sections_card" class="kt-card" data-theme-name="{{ $defaultTheme->name ?? 'Modern' }}">
                <div class="kt-card-header flex items-center justify-between gap-2">
                    <h3 class="kt-card-title" id="home_sections_card_title">Pagina-secties ({{ $defaultTheme->name ?? 'Modern' }} thema)</h3>
                    <div class="flex items-center gap-1 shrink-0">
                        <div class="relative" id="home-sections-add-wrap">
                            <button type="button" id="home-sections-add-btn" class="kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground" title="Sectie toevoegen" aria-label="Sectie toevoegen" aria-haspopup="true" aria-expanded="false">
                                <svg class="w-5 h-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            </button>
                            <div id="home-sections-add-menu" class="hidden absolute right-0 top-full mt-1 z-20 min-w-[180px] rounded-lg border border-border bg-background shadow-lg py-1 max-h-[70vh] overflow-y-auto">
                                @php $createThemeSlug = $defaultTheme->slug ?? 'modern'; $createSectionTypes = \App\Models\WebsitePage::getAvailableHomeSectionTypesForTheme($createThemeSlug); @endphp
                                @foreach($createSectionTypes as $st)
                                <button type="button" class="home-sections-add-type w-full text-left px-3 py-2 text-sm cursor-pointer hover:bg-muted/80" data-type="{{ $st['type'] }}">{{ $st['label'] }} toevoegen</button>
                                @endforeach
                                @php $availableComponents = app(\App\Services\FrontendComponentService::class)->availableForPage(); @endphp
                                @if($availableComponents->isNotEmpty())
                                <div class="border-t border-border my-1"></div>
                                <div class="px-2 py-1 text-xs font-medium text-muted-foreground">Componenten</div>
                                @foreach($availableComponents as $comp)
                                <button type="button" class="home-sections-add-component w-full text-left px-3 py-2 text-sm cursor-pointer hover:bg-muted/80" data-section="component:{{ $comp->id }}" data-name="{{ e($comp->name) }}" data-module="{{ e(trim(Str::before($comp->module_name ?? 'Module', ' ')) ?: $comp->module_name ?? 'Module') }}">{{ $comp->name }} toevoegen</button>
                                @endforeach
                                @endif
                            </div>
                        </div>
                        <button type="button" id="home-sections-collapse-all-btn" class="kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground" title="Alles inklappen" aria-label="Alles inklappen of uitklappen">
                            <svg class="w-5 h-5 text-current" id="home-sections-collapse-all-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 7.5m0 0L7.5 12m4.5-4.5V21" /></svg>
                        </button>
                    </div>
                </div>
                <div class="kt-card-table p-4">
                    <p class="text-sm text-muted-foreground mb-4" id="home_sections_intro">Deze secties worden getoond op de homepagina voor het gekozen thema ({{ $defaultTheme->name ?? 'Modern' }}). Pas teksten en knoppen aan; de volgorde en beschikbare secties hangen af van het thema.</p>
                    @include('admin.website-pages.partials.home-sections', ['homeSections' => $createHomeSections, 'themeSlug' => $createThemeSlug, 'isNonHomePage' => $createPageType !== 'home'])
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5">
                <a href="{{ route('admin.website-pages.index') }}" class="kt-btn kt-btn-outline">
                    <i class="ki-filled ki-cross me-2"></i>
                    Annuleren
                </a>
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-check me-2"></i>
                    Pagina aanmaken
                </button>
            </div>
        </div>
    </form>
</div>
<script>
(function() {
    var moduleChoice = document.getElementById('module_choice');
    var themeDisplay = document.getElementById('theme_display');
    var moduleNameHidden = document.getElementById('module_name_hidden');
    var pageTypeSelect = document.getElementById('page_type');
    var homeSectionsCard = document.getElementById('home_sections_card');
    var contentBlocksRow = document.getElementById('content_blocks_row');

    function updateForm() {
        var choice = moduleChoice.value;
        var opt = moduleChoice.options[moduleChoice.selectedIndex];
        var themeText = opt ? opt.getAttribute('data-theme-text') : '';
        themeDisplay.textContent = themeText || '';
        if (moduleNameHidden) moduleNameHidden.value = choice || '';
        toggleHomeAndContentRows();
    }
    function toggleHomeAndContentRows() {
        var pt = pageTypeSelect ? pageTypeSelect.value : 'home';
        if (homeSectionsCard) homeSectionsCard.style.display = 'block';
        if (contentBlocksRow) contentBlocksRow.style.display = 'none';
        var titleEl = document.getElementById('home_sections_card_title');
        var introEl = document.getElementById('home_sections_intro');
        var themeName = homeSectionsCard ? (homeSectionsCard.getAttribute('data-theme-name') || 'thema') : 'thema';
        if (titleEl) titleEl.textContent = pt === 'home' ? 'Homepagina secties (' + themeName + ' thema)' : 'Pagina-secties (' + themeName + ' – standaard Hero, footer, copyright)';
        if (introEl) introEl.textContent = pt === 'home'
            ? 'Deze secties worden getoond op de homepagina voor dit thema. Pas teksten en knoppen aan; de volgorde hangt af van het thema.'
            : 'Standaard tonen deze pagina\'s alleen een Hero-banner, footer en copyright. Voeg hieronder secties toe met de knop "Sectie toevoegen" of pas de Hero aan.';
    }

    moduleChoice.addEventListener('change', updateForm);
    if (pageTypeSelect) pageTypeSelect.addEventListener('change', toggleHomeAndContentRows);
    updateForm();

    // Slug automatisch uit titel (bij intypen)
    var titleInput = document.getElementById('title');
    var slugInput = document.getElementById('slug');
    if (titleInput && slugInput) {
        function slugify(s) {
            return String(s).toLowerCase()
                .replace(/\s+/g, '-')
                .replace(/[^a-z0-9\-]/g, '')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '');
        }
        var slugManuallyEdited = false;
        slugInput.addEventListener('input', function() { slugManuallyEdited = true; });
        titleInput.addEventListener('input', function() {
            if (!slugManuallyEdited) {
                slugInput.value = slugify(titleInput.value);
            }
        });
        titleInput.addEventListener('keypress', function() {
            if (!slugManuallyEdited) {
                setTimeout(function() { slugInput.value = slugify(titleInput.value); }, 0);
            }
        });
    }
})();
</script>
@endsection
