@extends('admin.layouts.app')

@section('title', 'Pagina bewerken')

@section('content')
@php
    $showSuccessMessage = session('success') ?? (request()->query('saved') ? 'Pagina bijgewerkt.' : null);
@endphp
@if($showSuccessMessage)
    <script>window.__websitePageSuccessMessage = @json($showSuccessMessage);</script>
@endif
<div class="kt-container-fixed min-w-0">
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
    <p class="text-sm text-muted-foreground mb-5">De pagina hoort bij een <strong>module</strong> of bij kernpagina's (geen). Pagina's worden altijd getoond in het actieve thema. De inhoud bewerk je met de website builder onderaan.</p>
    @if($isCentralMarketingWelcome ?? false)
        <div class="kt-alert kt-alert-info mb-5">
            <p>
                <i class="ki-filled ki-information-2 me-2"></i>
                Dit is de <strong>centrale NEXA-welkomstpagina</strong> (hoofddomein zonder tenant).<br><br> Slug en bedrijfskoppeling zijn vastgezet; secties en teksten bewerk je hier zoals bij elke andere frontend-pagina.
            </p>
        </div>
    @endif
    <div class="flex flex-col gap-5 pb-7.5">
        <div class="flex flex-wrap items-center justify-between gap-5">
            <h1 class="text-xl font-medium leading-none text-mono">
                Pagina bewerken
            </h1>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if(!empty($wizardBackUrl))
                <a href="{{ $wizardBackUrl }}" class="kt-btn kt-btn-outline">
                    <i class="ki-filled ki-arrow-left me-2"></i>
                    Terug naar tenant-wizard
                </a>
            @endif
            <a href="{{ route('admin.website-pages.index', $wizardIndexQuery ?? []) }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug naar overzicht
            </a>
            @php
                $websitePagePreviewUrl = $websiteDevPreviewUrl ?? route('admin.website-pages.preview', $page).($page->module_name ? '?module='.rawurlencode($page->module_name) : '');
            @endphp
            <a href="{{ $websitePagePreviewUrl }}" target="_blank" rel="noopener" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-eye me-2"></i>
                Pagina voorbeeld
            </a>
        </div>
    </div>

    <form id="website-page-form" action="{{ route('admin.website-pages.update', $page) }}{{ $page->module_name ? '?module=' . rawurlencode($page->module_name) : '' }}" method="POST" data-success-url="{{ route('admin.website-pages.index', $wizardIndexQuery ?? []) }}" data-validate="true" data-skip-url-validation="true" novalidate>
        @csrf
        @method('PUT')
        @if(!empty($wizardIndexQuery))
            @foreach($wizardIndexQuery as $k => $v)
                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
            @endforeach
        @endif
        @if(!empty($wizardIndexQuery['wizard_company']))
            <input type="hidden" name="company_id" value="{{ (int) $wizardIndexQuery['wizard_company'] }}">
        @endif
        {{-- Fallback voor section_order bovenaan formulier (bij grote PUT-request kan section_order anders ontbreken) --}}
        @php $editSectionOrder = $page->getHomeSections()['section_order'] ?? []; $editSectionOrderStr = is_array($editSectionOrder) ? implode(',', $editSectionOrder) : (is_string($editSectionOrder) ? $editSectionOrder : ''); @endphp
        <input type="hidden" name="_section_order" id="section-order-fallback" value="{{ $editSectionOrderStr }}">
        <input type="hidden" name="_removed_section_keys" id="removed-section-keys-fallback" value="">
        {{-- Fallback Google Reviews (max_input_vars / geneste component-key) --}}
        <input type="hidden" name="_google_reviews_place_id" id="google-reviews-place-fallback" value="">
        <input type="hidden" name="_google_reviews_business_name" id="google-reviews-business-fallback" value="">
        <input type="hidden" name="_google_reviews_count" id="google-reviews-count-fallback" value="">
        <input type="hidden" name="_google_reviews_cache_hours" id="google-reviews-cache-fallback" value="">
        <input type="hidden" name="_google_reviews_min_stars" id="google-reviews-min-stars-fallback" value="">
        <input type="hidden" name="_google_reviews_section_title" id="google-reviews-section-title-fallback" value="">
        <input type="hidden" name="_google_reviews_section_background" id="google-reviews-section-background-fallback" value="">
        {{-- Fallback voor visibility footer (max_input_vars): JSON met footer_* keys bovenaan formulier --}}
        <input type="hidden" name="_visibility_footer_fallback" id="visibility-footer-fallback" value="">
        {{-- Fallback footer-config (tagline, kaart, links) — staat bovenaan i.v.m. max_input_vars --}}
        <input type="hidden" name="_footer_config_fallback" id="footer-config-fallback" value="">
        {{-- Zelfde patroon als _section_order: Volgorde-input staat laat in het formulier; bij max_input_vars vult JS deze vroege hidden. --}}
        <input type="hidden" name="_sort_order" id="sort-order-fallback-input" value="{{ old('_sort_order', old('sort_order', $page->sort_order ?? 0)) }}">
        @include('admin.website-pages.partials.sort-order-sync')

        <div class="grid gap-5 lg:gap-7.5">
            <x-error-card :errors="$errors" />

            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header website-page-info-header flex flex-wrap items-center justify-between gap-3 w-full min-w-0 pt-4 pb-4">
                    <h3 class="kt-card-title w-full sm:w-auto shrink-0">
                        Pagina-informatie
                    </h3>
                    @php
                        // Sentinel: old() met default false zou 'geen old input' niet kunnen onderscheiden van opgeslagen false.
                        $__menuOldSentinel = new \stdClass;
                        $__menuOld = old('show_in_menu', $__menuOldSentinel);
                        if ($__menuOld !== $__menuOldSentinel) {
                            $__menuParsed = filter_var($__menuOld, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                            $__menuOn = ($__menuParsed === null) ? (bool) ($page->show_in_menu ?? true) : $__menuParsed;
                        } else {
                            $__menuOn = (bool) ($page->show_in_menu ?? true);
                        }
                    @endphp
                    <div class="flex flex-wrap flex-1 items-center justify-start sm:justify-center gap-x-2 gap-y-2 min-w-0 sm:px-2 w-full sm:w-auto">
                        <label class="kt-label inline-flex flex-wrap items-center gap-2 shrink-0 w-fit max-w-full" for="show_in_menu">
                            <span class="text-sm font-medium text-secondary-foreground shrink-0">Menuitem</span>
                            {{-- Wrapper: form-validation.js gebruikt .relative op het veld; niet op <label> (anders width:100% → links uitgelijnd). --}}
                            <div class="relative w-[120px] max-w-full shrink-0">
                                <select name="show_in_menu" id="show_in_menu" class="kt-input kt-input-sm w-full" autocomplete="off">
                                    <option value="1" {{ $__menuOn ? 'selected' : '' }}>Ja</option>
                                    <option value="0" {{ ! $__menuOn ? 'selected' : '' }}>Nee</option>
                                </select>
                            </div>
                        </label>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 shrink-0 w-full sm:w-auto justify-start sm:justify-end">
                        @include('admin.website-pages.partials.website-page-seo-button')
                        <label class="kt-label inline-flex flex-wrap items-center gap-2 shrink-0" for="is_active">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox"
                                   class="kt-switch kt-switch-sm shrink-0"
                                   id="is_active"
                                   name="is_active"
                                   value="1"
                                   {{ old('is_active', $page->is_active) ? 'checked' : '' }}/>
                            <span class="text-sm font-medium text-secondary-foreground">Actief (zichtbaar op de website)</span>
                        </label>
                    </div>
                </div>
                <div id="website-page-seo-meta"
                     class="hidden"
                     data-generate-url="{{ route('admin.website-pages.generate-seo') }}"
                     data-csrf="{{ csrf_token() }}"></div>
                <div class="kt-card-content p-0">
                    <div class="px-3 sm:px-5 pb-3 min-w-0">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                        @if(!($isCentralMarketingWelcome ?? false))
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">
                                    Bij welke module hoort deze pagina? *
                                </td>
                                <td class="min-w-48 w-full">
                                    <select id="module_choice"
                                            class="kt-input"
                                            required
                                            data-default-theme-id="{{ $defaultTheme?->id ?? '' }}">
                                        <option value="" data-theme-id="{{ $defaultTheme?->id ?? '' }}"
                                            {{ !old('module_name', $page->module_name) ? 'selected' : '' }}>Geen (kernpagina's voor home, over ons, contact)</option>
                                        @foreach($installedModules as $module)
                                            @php
                                                $moduleName = $module->getName();
                                                $moduleModel = $moduleThemes[$moduleName] ?? null;
                                                $themeId = ($moduleModel && $moduleModel->theme) ? $moduleModel->theme->id : ($defaultTheme?->id ?? '');
                                            @endphp
                                            <option value="{{ $moduleName }}"
                                                data-theme-id="{{ $themeId }}"
                                                {{ old('module_name', $page->module_name) === $moduleName ? 'selected' : '' }}>{{ $module->getDisplayName() }}</option>
                                        @endforeach
                                    </select>
                                    <div class="text-xs text-muted-foreground mt-1">Home, Over ons, Contact en Custom kunnen aan een module gekoppeld worden. Alle pagina's worden getoond in het actieve thema.</div>
                                    <input type="hidden" name="module_name" id="module_name_hidden" value="{{ old('module_name', $page->module_name) }}">
                                </td>
                            </tr>
                            @include('admin.website-pages.partials.tenant-context-row')
                        @else
                            <input type="hidden" name="module_name" id="module_name_hidden" value="">
                            <input type="hidden" name="company_id" value="">
                        @endif
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Thema
                            </td>
                            <td>
                                @include('admin.website-pages.partials.theme-field')
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
                                       autocomplete="off"
                                       @if($isCentralMarketingWelcome ?? false) readonly @endif>
                                <div class="text-xs text-muted-foreground mt-1">
                                    @if($isCentralMarketingWelcome ?? false)
                                        Deze slug is gereserveerd voor de centrale welkomstpagina en kan niet worden gewijzigd.
                                    @else
                                        Wordt automatisch ingevuld op basis van de titel. Alleen kleine letters, cijfers en streepjes.
                                    @endif
                                </div>
                                @error('slug')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        @include('admin.website-pages.partials.website-page-seo-fields', ['metaDescriptionValue' => old('meta_description', $page->meta_description)])
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
            </div>

            <div id="home_sections_card" class="kt-card w-full min-w-0" data-theme-name="{{ $page->theme?->name ?? 'Metronic' }}">
                <div class="kt-card-header website-page-sections-header flex flex-wrap items-center justify-between gap-2 min-w-0">
                    <h3 class="kt-card-title" id="home_sections_card_title">Pagina-secties ({{ $page->theme?->name ?? 'Metronic' }} thema)</h3>
                    <div class="flex items-center gap-1 shrink-0">
                        <div class="relative" id="home-sections-add-wrap">
                            <button type="button" id="home-sections-add-btn" class="kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground" title="Sectie toevoegen" aria-label="Sectie toevoegen" aria-haspopup="true" aria-expanded="false">
                                <svg class="w-5 h-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            </button>
                            <div id="home-sections-add-menu" class="hidden absolute right-0 top-full mt-1 z-20 min-w-[240px] rounded-lg border border-border bg-background shadow-lg py-1 max-h-[70vh] overflow-y-auto">
                                @php
                                    $themeSlug = $page->theme?->slug ?? 'modern';
                                    $sectionTypes = \App\Models\WebsitePage::getAvailableHomeSectionTypesForTheme($themeSlug);
                                    $availableComponents = app(\App\Services\FrontendComponentService::class)->availableForPage($moduleNameForComponents ?? null);
                                @endphp
                                @include('admin.website-pages.partials.home-sections-add-menu', ['sectionTypes' => $sectionTypes, 'availableComponents' => $availableComponents])
                            </div>
                        </div>
                        <button type="button" id="home-sections-collapse-all-btn" class="kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground" title="Alles inklappen" aria-label="Alles inklappen of uitklappen">
                            <svg class="w-5 h-5 text-current" id="home-sections-collapse-all-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 7.5m0 0L7.5 12m4.5-4.5V21" /></svg>
                        </button>
                    </div>
                </div>
                <div class="kt-card-content p-3 sm:p-5 min-w-0">
                    <p class="text-sm text-muted-foreground mb-4" id="home_sections_intro">Deze secties worden getoond op de homepagina voor dit thema ({{ $page->theme?->name ?? 'Metronic' }}). Pas teksten en knoppen aan; de volgorde hangt af van het thema.</p>
                    @include('admin.website-pages.partials.home-sections', ['homeSections' => $page->getHomeSections(), 'themeSlug' => $page->theme?->slug ?? 'modern', 'isNonHomePage' => $page->page_type !== 'home' && $page->slug !== 'home', 'collapseSectionsByDefault' => $collapseSectionsByDefault ?? false, 'googleMapsApiKey' => $googleMapsApiKey ?? '', 'googleMapsMapId' => $googleMapsMapId ?? '', 'moduleNameForUploads' => $page->module_name ?? null, 'emailTemplates' => $emailTemplates ?? collect(), 'emailTemplateSelectedIds' => $emailTemplateSelectedIds ?? [], 'websitePageCompanyId' => $page->company_id])
                </div>
            </div>

            <div class="admin-form-actions flex flex-wrap items-center justify-end gap-2.5 mt-5 w-full min-w-0">
                <a href="{{ route('admin.website-pages.index', $wizardIndexQuery ?? []) }}" class="kt-btn kt-btn-outline">
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
{{-- Cmd+S / Ctrl+S: vroege capture (vóór layout/app.js), werkt ook als focus in contenteditable buiten <form>-boom lijkt --}}
<script>
(function() {
    var form = document.getElementById('website-page-form');
    if (!form) return;
    function submitPageFormFromShortcut() {
        try {
            sessionStorage.setItem('admin-scroll-after-save', String(window.scrollY || window.pageYOffset || 0));
        } catch (err) {}
        if (typeof tinymce !== 'undefined' && tinymce.triggerSave) tinymce.triggerSave();
        if (typeof window.syncAllFlowbiteWysiwygEditors === 'function') window.syncAllFlowbiteWysiwygEditors();
        if (typeof window.syncWebsitePageSortOrderFallback === 'function') {
            window.syncWebsitePageSortOrderFallback();
        }
        var btn = form.querySelector('button[type="submit"].kt-btn-primary') || form.querySelector('button[type="submit"]');
        if (!btn) return;
        // Na een submit kan de knop disabled blijven; requestSubmit() gooit dan of doet niets
        if (btn.disabled) btn.disabled = false;
        try {
            if (typeof form.requestSubmit === 'function') form.requestSubmit(btn);
            else btn.click();
        } catch (err) {
            try { btn.disabled = false; btn.click(); } catch (e2) {}
        }
    }
    window.__submitWebsitePageFormFromShortcut = submitPageFormFromShortcut;
    document.addEventListener('keydown', function(e) {
        if (!(e.ctrlKey || e.metaKey)) return;
        var keyOk = (e.key === 's' || e.key === 'S');
        var codeOk = e.keyCode === 83 || e.which === 83;
        if (!keyOk && !codeOk) return;
        var t = e.target;
        if (t && typeof t.closest === 'function') {
            if (t.closest('[role="dialog"]') || t.closest('[aria-modal="true"]') || t.closest('.modal')) return;
            var otherForm = t.closest('form');
            if (otherForm && otherForm !== form) return;
        }
        e.preventDefault();
        e.stopImmediatePropagation();
        submitPageFormFromShortcut();
    }, true);
})();
</script>
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

    // Normale form submit (geen fetch) zodat de server de POST zeker ontvangt en na redirect de pagina met opgeslagen data toont.
    // Section order altijd uit de DOM halen bij submit, zodat verwijderde secties nooit meer meegestuurd worden.
    var form = document.getElementById('website-page-form');
    if (form) {
        var submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            var saveScrollForSubmit = function() {
                try {
                    sessionStorage.setItem('admin-scroll-after-save', String(window.scrollY || window.pageYOffset || 0));
                } catch (err) {}
            };
            submitBtn.addEventListener('mousedown', saveScrollForSubmit, true);
            submitBtn.addEventListener('click', saveScrollForSubmit, true);
        }
        form.addEventListener('submit', function(e) {
            try {
                sessionStorage.setItem('admin-scroll-after-save', String(window.scrollY || window.pageYOffset || 0));
            } catch (err) {}
            if (typeof tinymce !== 'undefined' && tinymce.editors) tinymce.triggerSave();
            if (typeof window.syncAllFlowbiteWysiwygEditors === 'function') window.syncAllFlowbiteWysiwygEditors();
            if (typeof window.syncWebsitePageSortOrderFallback === 'function') {
                window.syncWebsitePageSortOrderFallback();
            }
            var footerConfigFb = document.getElementById('footer-config-fallback');
            if (footerConfigFb) {
                var footerPayload = {};
                var taglineTa = document.getElementById('home-footer-tagline');
                if (taglineTa) footerPayload.tagline = taglineTa.value || '';
                ['map_postcode', 'map_huisnummer', 'map_street', 'map_city'].forEach(function(key) {
                    var el = document.querySelector('[name="home_sections[footer][' + key + ']"]');
                    if (el) footerPayload[key] = el.value || '';
                });
                var latEl = document.getElementById('footer-map-lat');
                var lngEl = document.getElementById('footer-map-lng');
                if (latEl) footerPayload.map_lat = latEl.value || '';
                if (lngEl) footerPayload.map_lng = lngEl.value || '';
                var mapSizeEl = document.querySelector('[name="home_sections[footer][map_size]"]');
                var mapZoomEl = document.getElementById('footer-map-zoom');
                if (mapSizeEl) footerPayload.map_size = mapSizeEl.value || '';
                if (mapZoomEl) footerPayload.map_zoom = mapZoomEl.value || '';
                var cityOnlyEl = document.querySelector('[name="home_sections[footer][map_city_only]"]');
                if (cityOnlyEl && cityOnlyEl.type === 'checkbox') footerPayload.map_city_only = cityOnlyEl.checked ? 1 : 0;
                var balloonEl = document.getElementById('footer-map-show-address-balloon');
                if (balloonEl) footerPayload.map_show_address_balloon = balloonEl.checked ? 1 : 0;
                function collectFooterLinks(listId, key) {
                    var list = document.getElementById(listId);
                    if (!list) return;
                    var rows = [];
                    list.querySelectorAll('.footer-link-row').forEach(function(row) {
                        var labelInp = row.querySelector('input[name*="[label]"]');
                        var urlInp = row.querySelector('input[name*="[url]"]');
                        var label = labelInp ? (labelInp.value || '').trim() : '';
                        if (label !== '') {
                            rows.push({ label: label, url: urlInp ? (urlInp.value || '') : '' });
                        }
                    });
                    footerPayload[key] = rows;
                }
                collectFooterLinks('footer-quick-links-list', 'quick_links');
                collectFooterLinks('footer-support-links-list', 'support_links');
                footerConfigFb.value = JSON.stringify(footerPayload);
            }
            // Visibility footer-fallback: alle footer_* visibility-waarden in één veld (voorkomt verlies door max_input_vars)
            var fallbackInp = document.getElementById('visibility-footer-fallback');
            if (fallbackInp) {
                var obj = {};
                var footerKeys = ['footer_logo', 'footer_map', 'footer_tagline', 'footer_quick_links', 'footer_support_links', 'footer_social'];
                footerKeys.forEach(function(key) {
                    var inp = document.getElementById('visibility-' + key);
                    obj[key] = (inp && inp.value === '1') ? '1' : '0';
                });
                form.querySelectorAll('input[name^="home_sections[visibility][footer_"]').forEach(function(inp) {
                    var m = (inp.name || '').match(/\[visibility\]\[(footer_[^\]]+)\]/);
                    if (m && !obj.hasOwnProperty(m[1])) obj[m[1]] = (inp.value === '1' ? '1' : '0');
                });
                fallbackInp.value = JSON.stringify(obj);
            }
            // E-mailtemplate-select: waarde naar fallback-hidden kopiëren zodat template_id altijd meegestuurd wordt
            var emailTemplateSelects = form.querySelectorAll('select[data-email-template-select]');
            emailTemplateSelects.forEach(function(sel) {
                var hidId = sel.getAttribute('data-fallback-input-id');
                if (hidId) {
                    var hid = document.getElementById(hidId);
                    if (hid) hid.value = sel.value || '';
                }
            });
            var sortable = document.getElementById('home-sections-sortable');
            var orderInp = document.getElementById('home-sections-order-input');
            var fallbackInp = document.getElementById('section-order-fallback');
            var removedInp = document.getElementById('home-sections-removed-keys-input');
            var removedFallbackInp = document.getElementById('removed-section-keys-fallback');
            var collapsedInp = document.getElementById('admin-collapsed-input');
            if (sortable && orderInp) {
                var order = [];
                var collapsed = [];
                [].slice.call(sortable.children).forEach(function(el) {
                    var s = el.getAttribute('data-section');
                    if (s) {
                        order.push(s);
                        if (el.classList.contains('home-section-card--collapsed')) collapsed.push(s);
                    }
                });
                var footerCardOrder = document.querySelector('.home-section-card[data-section="footer"]');
                var copyrightCardOrder = document.getElementById('copyright-section-card');
                if (footerCardOrder && order.indexOf('footer') === -1) order.push('footer');
                if (copyrightCardOrder && order.indexOf('copyright') === -1) order.push('copyright');
                var orderStr = order.join(',');
                orderInp.value = orderStr;
                if (fallbackInp) fallbackInp.value = orderStr;
                if (removedInp && removedFallbackInp) removedFallbackInp.value = removedInp.value;
                var grCard = sortable.querySelector('.home-section-card[data-section="component:website.google_reviews"], .home-section-card[data-section="component:nexa.google_reviews"]');
                if (grCard) {
                    var grPlace = grCard.querySelector('input[name*="[place_id]"]');
                    var grBusiness = grCard.querySelector('input[name*="[business_name]"]');
                    var grCount = grCard.querySelector('input[name*="[count]"]');
                    var grCache = grCard.querySelector('input[name*="[cache_hours]"]');
                    var grMin = grCard.querySelector('input[name*="[min_stars]"]');
                    var grSectionTitle = grCard.querySelector('input[name*="[section_title]"]');
                    var grSectionBackground = grCard.querySelector('input[name*="[section_background]"]');
                    var grPlaceFb = document.getElementById('google-reviews-place-fallback');
                    var grBusinessFb = document.getElementById('google-reviews-business-fallback');
                    var grCountFb = document.getElementById('google-reviews-count-fallback');
                    var grCacheFb = document.getElementById('google-reviews-cache-fallback');
                    var grMinFb = document.getElementById('google-reviews-min-stars-fallback');
                    var grSectionTitleFb = document.getElementById('google-reviews-section-title-fallback');
                    var grSectionBackgroundFb = document.getElementById('google-reviews-section-background-fallback');
                    if (grPlaceFb && grPlace) grPlaceFb.value = grPlace.value || '';
                    if (grBusinessFb && grBusiness) grBusinessFb.value = grBusiness.value || '';
                    if (grCountFb && grCount) grCountFb.value = grCount.value || '';
                    if (grCacheFb && grCache) grCacheFb.value = grCache.value || '';
                    if (grMinFb && grMin) grMinFb.value = grMin.value || '';
                    if (grSectionTitleFb && grSectionTitle) grSectionTitleFb.value = grSectionTitle.value || '';
                    if (grSectionBackgroundFb && grSectionBackground) grSectionBackgroundFb.value = grSectionBackground.value || '';
                }
                var footerCardSubmit = document.querySelector('.home-section-card[data-section="footer"]');
                var copyrightCardSubmit = document.getElementById('copyright-section-card');
                if (footerCardSubmit && footerCardSubmit.classList.contains('home-section-card--collapsed')) {
                    collapsed.push('footer');
                }
                if (copyrightCardSubmit && copyrightCardSubmit.classList.contains('home-section-card--collapsed')) {
                    collapsed.push('copyright');
                }
                if (collapsedInp) collapsedInp.value = collapsed.join(',');
            } else if (orderInp && fallbackInp) {
                fallbackInp.value = orderInp.value;
            }
            var submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Bezig met opslaan…';
            }
        });
        // E-mailtemplate-select: bij wijziging direct hidden vullen zodat template_id bij submit altijd aanwezig is
        form.addEventListener('change', function(e) {
            var sel = e.target.closest('select[data-email-template-select]');
            if (sel) {
                var hidId = sel.getAttribute('data-fallback-input-id');
                if (hidId) {
                    var hid = document.getElementById(hidId);
                    if (hid) hid.value = sel.value || '';
                }
            }
        });
        // Bij laden: opgeslagen template_id in select zetten (prefill uit data-selected-template-id)
        document.querySelectorAll('select[data-email-template-select]').forEach(function(sel) {
            var id = sel.getAttribute('data-selected-template-id');
            if (id && sel.querySelector('option[value="' + id + '"]')) {
                sel.value = id;
                var hidId = sel.getAttribute('data-fallback-input-id');
                if (hidId) {
                    var hid = document.getElementById(hidId);
                    if (hid) hid.value = id;
                }
            }
        });
    }

    var moduleChoice = document.getElementById('module_choice');
    var pageTypeSelect = document.getElementById('page_type');
    var moduleNameHidden = document.getElementById('module_name_hidden');
    var homeSectionsCard = document.getElementById('home_sections_card');
    var contentBlocksRow = document.getElementById('content_blocks_row');

    function syncThemeUiFromSelect() {
        var themeSelect = document.getElementById('frontend_theme_id');
        if (!themeSelect || themeSelect.tagName !== 'SELECT') return;
        var opt = themeSelect.options[themeSelect.selectedIndex];
        var themeName = opt ? opt.textContent.trim() : 'thema';
        if (homeSectionsCard) {
            homeSectionsCard.setAttribute('data-theme-name', themeName);
        }
        toggleHomeAndContentRows();
    }

    function syncModuleFieldsFromChoice(applyModuleThemeSuggestion) {
        if (!moduleChoice) return;
        var choice = moduleChoice.value;
        var opt = moduleChoice.options[moduleChoice.selectedIndex];
        var themeId = opt ? opt.getAttribute('data-theme-id') : '';
        if (moduleNameHidden) moduleNameHidden.value = choice || '';
        if (applyModuleThemeSuggestion) {
            var themeSelect = document.getElementById('frontend_theme_id');
            if (themeSelect && themeSelect.tagName === 'SELECT' && themeId) {
                themeSelect.value = themeId;
            }
        }
        syncThemeUiFromSelect();
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

    if (moduleChoice) {
        moduleChoice.addEventListener('change', function() {
            syncModuleFieldsFromChoice(true);
        });
    }
    pageTypeSelect.addEventListener('change', toggleHomeAndContentRows);
    var themeSelectEl = document.getElementById('frontend_theme_id');
    if (themeSelectEl && themeSelectEl.tagName === 'SELECT') {
        themeSelectEl.addEventListener('change', syncThemeUiFromSelect);
    }
    if (moduleChoice) {
        syncModuleFieldsFromChoice(false);
    } else {
        syncThemeUiFromSelect();
        toggleHomeAndContentRows();
    }

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

    // Succesmelding na redirect (session of URL-param saved=1) als modal tonen; herstel scrollpositie
    if (window.__websitePageSuccessMessage) {
        showSuccessModal(window.__websitePageSuccessMessage);
        delete window.__websitePageSuccessMessage;
        var u = new URL(window.location.href);
        if (u.searchParams.get('saved')) {
            u.searchParams.delete('saved');
            var clean = u.pathname + (u.search || '');
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, '', clean);
            }
        }
    }
})();
</script>
@push('scripts')
<script src="{{ asset('assets/js/form-validation.js') }}"></script>
@endpush

@endsection
