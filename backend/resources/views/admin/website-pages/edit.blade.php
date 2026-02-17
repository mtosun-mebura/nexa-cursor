@extends('admin.layouts.app')

@section('title', 'Pagina bewerken')

@section('content')
@if(session('success'))
    <script>window.__websitePageSuccessMessage = @json(session('success'));</script>
@endif
<div class="kt-container-fixed">
    @if($errors->any())
    <script>
    (function(){
        var messages = @json($errors->getMessages());
        var flat = @json($errors->all());
        console.error('[Website-pagina opslaan] Laravel-fouten:', flat);
        console.error('[Website-pagina opslaan] Per veld:', messages);
    })();
    </script>
    @endif
    <p class="text-sm text-muted-foreground mb-5">De pagina hoort bij een <strong>module</strong> of bij kernpagina's (geen). Het thema waarmee de pagina wordt getoond is per module vastgelegd bij Frontend Thema's. De inhoud bewerk je met de website builder onderaan.</p>
    <div class="flex flex-col gap-5 pb-7.5">
        <div class="flex flex-wrap items-center justify-between gap-5">
            <h1 class="text-xl font-medium leading-none text-mono">
                Pagina bewerken
            </h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.website-pages.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
            <a href="{{ route('admin.website-pages.preview', $page) }}" target="_blank" rel="noopener" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-eye me-2"></i>
                Pagina voorbeeld
            </a>
        </div>
    </div>

    <form id="website-page-form" action="{{ route('admin.website-pages.update', $page) }}" method="POST" data-success-url="{{ route('admin.website-pages.index') }}">
        @csrf
        @method('PUT')

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
                               {{ old('is_active', $page->is_active) ? 'checked' : '' }}/>
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
                                        {{ !old('module_name', $page->module_name) ? 'selected' : '' }}>Geen (kernpagina's voor home, over ons, contact)</option>
                                    @foreach($installedModules as $module)
                                        @php
                                            $moduleName = $module->getName();
                                            $moduleModel = $moduleThemes[$moduleName] ?? null;
                                            $themeName = ($moduleModel && $moduleModel->theme) ? $moduleModel->theme->name : ($defaultTheme?->name ?? 'Standaardthema');
                                        @endphp
                                        <option value="{{ $moduleName }}"
                                            data-theme-text="Thema voor {{ $module->getDisplayName() }}: {{ $themeName }}"
                                            {{ old('module_name', $page->module_name) === $moduleName ? 'selected' : '' }}>{{ $module->getDisplayName() }}</option>
                                    @endforeach
                                </select>
                                <div class="text-xs text-muted-foreground mt-1">Kernpagina's gebruiken het actieve standaardthema; bij een module het thema van die module. Home, Over ons, Contact en Custom kunnen aan een module gekoppeld worden.</div>
                                <input type="hidden" name="module_name" id="module_name_hidden" value="{{ old('module_name', $page->module_name) }}">
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Thema
                            </td>
                            <td>
                                <p id="theme_display" class="text-sm font-medium text-secondary-foreground"></p>
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
                                    <option value="custom" {{ old('page_type', $page->page_type) === 'custom' ? 'selected' : '' }}>Custom (tekstpagina)</option>
                                    <option value="home" {{ old('page_type', $page->page_type) === 'home' ? 'selected' : '' }}>Home</option>
                                    <option value="about" {{ old('page_type', $page->page_type) === 'about' ? 'selected' : '' }}>Over ons</option>
                                    <option value="contact" {{ old('page_type', $page->page_type) === 'contact' ? 'selected' : '' }}>Contact</option>
                                    <option value="module" {{ old('page_type', $page->page_type) === 'module' ? 'selected' : '' }}>Module-pagina</option>
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
                                       value="{{ old('title', $page->title) }}"
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
                                       value="{{ old('slug', $page->slug) }}"
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
                                       value="{{ old('meta_description', $page->meta_description) }}">
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
                                <p class="text-xs text-muted-foreground mb-3">Niet in gebruik: alle paginatypes gebruiken nu secties (Hero, footer, copyright + optioneel extra secties).</p>
                                @include('admin.website-pages.partials.page-builder', ['contentJson' => old('content', $page->content ?? '')])
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
                                       value="{{ old('sort_order', $page->sort_order) }}"
                                       min="0">
                                @error('sort_order')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div id="home_sections_card" class="kt-card" data-theme-name="{{ $page->theme?->name ?? 'Modern' }}">
                <div class="kt-card-header flex items-center justify-between gap-2">
                    <h3 class="kt-card-title" id="home_sections_card_title">Pagina-secties ({{ $page->theme?->name ?? 'Modern' }} thema)</h3>
                    <div class="flex items-center gap-1 shrink-0">
                        <div class="relative" id="home-sections-add-wrap">
                            <button type="button" id="home-sections-add-btn" class="kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground" title="Sectie toevoegen" aria-label="Sectie toevoegen" aria-haspopup="true" aria-expanded="false">
                                <svg class="w-5 h-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            </button>
                            <div id="home-sections-add-menu" class="hidden absolute right-0 top-full mt-1 z-20 min-w-[180px] rounded-lg border border-border bg-background shadow-lg py-1 max-h-[70vh] overflow-y-auto">
                                @php $themeSlug = $page->theme?->slug ?? 'modern'; $sectionTypes = \App\Models\WebsitePage::getAvailableHomeSectionTypesForTheme($themeSlug); @endphp
                                @foreach($sectionTypes as $st)
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
                    <p class="text-sm text-muted-foreground mb-4" id="home_sections_intro">Deze secties worden getoond op de homepagina voor dit thema ({{ $page->theme?->name ?? 'Modern' }}). Pas teksten en knoppen aan; de volgorde hangt af van het thema.</p>
                    @include('admin.website-pages.partials.home-sections', ['homeSections' => $page->getHomeSections(), 'themeSlug' => $page->theme?->slug ?? 'modern', 'isNonHomePage' => $page->page_type !== 'home' && $page->slug !== 'home', 'googleMapsApiKey' => $googleMapsApiKey ?? '', 'googleMapsMapId' => $googleMapsMapId ?? ''])
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5">
                <a href="{{ route('admin.website-pages.index') }}" class="kt-btn kt-btn-outline">
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
(function() {
    var autoCloseTimer = null;

    function showSuccessModal(message) {
        var existing = document.getElementById('success-modal-overlay');
        if (existing) existing.remove();
        var overlay = document.createElement('div');
        overlay.id = 'success-modal-overlay';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-labelledby', 'success-modal-title');
        overlay.className = 'fixed inset-0 z-[100] flex items-center justify-center p-4';
        overlay.style.backgroundColor = 'rgba(0,0,0,0.4)';
        overlay.style.backgroundColor = 'rgba(0,0,0,0.6)';
        overlay.innerHTML =
            '<div class="relative w-[500px] rounded-xl border-2 border-border bg-background p-6 shadow-xl" id="success-modal">' +
            '<button type="button" class="absolute right-2 top-2 rounded p-1 text-foreground/70 hover:bg-accent hover:text-foreground" id="success-modal-close-x" aria-label="Sluiten">' +
            '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>' +
            '</button>' +
            '<div class="flex flex-col items-center gap-3 pt-1">' +
            '<div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary">' +
            '<i class="ki-filled ki-check-circle text-xl text-primary-foreground"></i>' +
            '</div>' +
            '<p id="success-modal-title" class="text-center text-xl font-semibold text-foreground"></p>' +
            '<button type="button" class="mt-1 rounded bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground hover:opacity-90" id="success-modal-close-btn">Sluiten</button>' +
            '</div></div>';
        document.body.appendChild(overlay);
        document.getElementById('success-modal-title').textContent = message || 'Pagina bijgewerkt.';

        function closeModal() {
            if (autoCloseTimer) clearTimeout(autoCloseTimer);
            overlay.style.transition = 'opacity 0.2s ease-out';
            overlay.style.opacity = '0';
            setTimeout(function() {
                if (overlay.parentNode) overlay.remove();
            }, 200);
        }

        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) closeModal();
        });
        overlay.querySelector('#success-modal-close-x').addEventListener('click', closeModal);
        overlay.querySelector('#success-modal-close-btn').addEventListener('click', closeModal);
        document.addEventListener('keydown', function escHandler(e) {
            if (e.key === 'Escape') {
                closeModal();
                document.removeEventListener('keydown', escHandler);
            }
        });

        autoCloseTimer = setTimeout(closeModal, 3000);
    }

    // Fouten zichtbaar in console: bij submit via fetch loggen we foutrespons, na redirect loggen we Laravel-errors
    var form = document.getElementById('website-page-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (typeof tinymce !== 'undefined' && tinymce.editors) tinymce.triggerSave();
            if (typeof window.syncAllFlowbiteWysiwygEditors === 'function') window.syncAllFlowbiteWysiwygEditors();
            var formData = new FormData(form);
            var url = form.getAttribute('action');
            var submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Bezig met opslaan…';
            }
            var indexUrl = (form.getAttribute('data-success-url') || (window.location.origin + '/admin/website-pages')).replace(/\/$/, '');
            fetch(url, { method: 'POST', body: formData })
                .then(function(res) {
                    if (res.status >= 400) {
                        return res.text().then(function(body) {
                            console.error('[Website-pagina opslaan] Serverfout:', res.status, res.statusText);
                            console.error('[Website-pagina opslaan] Response body:', body);
                            throw new Error('Server: ' + res.status);
                        });
                    }
                    var resUrl = (res.url || '').replace(/\/$/, '');
                    if (res.ok && resUrl && resUrl.indexOf(indexUrl) !== -1 && resUrl.indexOf('/edit') === -1) {
                        window.location.href = indexUrl;
                        return;
                    }
                    if (res.ok) {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = '<i class="ki-filled ki-check me-2"></i>Opslaan';
                        }
                        showSuccessModal('Pagina bijgewerkt.');
                        return;
                    }
                    window.location.href = url;
                })
                .catch(function(err) {
                    console.error('[Website-pagina opslaan] Fout:', err);
                    if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = '<i class="ki-filled ki-check me-2"></i>Opslaan'; }
                });
        });
    }

    var moduleChoice = document.getElementById('module_choice');
    var themeDisplay = document.getElementById('theme_display');
    var pageTypeSelect = document.getElementById('page_type');
    var moduleNameHidden = document.getElementById('module_name_hidden');
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
    pageTypeSelect.addEventListener('change', toggleHomeAndContentRows);
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

    // Succesmelding na redirect (session) als modal tonen
    if (window.__websitePageSuccessMessage) {
        showSuccessModal(window.__websitePageSuccessMessage);
        delete window.__websitePageSuccessMessage;
    }
})();
</script>
@endsection
