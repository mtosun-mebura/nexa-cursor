@extends('admin.layouts.app')

@section('title', "Website Pagina's")

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <div class="min-w-0 flex-1">
            <h1 class="text-xl font-medium leading-none text-mono">Website Pagina's</h1>
            @if($activeTheme || !empty($activeModuleName))
                <p class="text-sm text-muted-foreground mt-1">
                    Pagina's worden op de website getoond in het actieve thema
                    @if($activeTheme)
                        <strong>{{ $activeTheme->name }}</strong>
                    @endif
                    @if(!empty($activeModuleName))
                        · Module <strong>{{ $activeModuleName }}</strong>
                    @endif
                </p>
            @endif
            @if(!empty($wizardBackUrl))
                <div class="mt-2">
                    <a href="{{ $wizardBackUrl }}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left me-2"></i>
                        Terug naar tenant-wizard
                    </a>
                </div>
            @endif
            @if(!empty($websitePagesManagingCentralSite))
                <p class="text-sm text-muted-foreground mt-2 mb-0">
                    Pagina&rsquo;s van de <span class="text-foreground font-medium">hoofdwebsite van NEXA Suite</span> (geen tenant).
                    Kies een tenant in de zijbalk om de website van een klantbedrijf te beheren.
                </p>
            @endif
            @php
                $wtc = $websiteTenantContext ?? ['visible' => false];
            @endphp
            @if(!empty($wtc['visible']) && !empty($wtc['effective_company']))
                <p class="text-sm text-muted-foreground mt-2 mb-0">
                    <span class="text-foreground font-medium">Actieve tenant:</span> {{ $wtc['effective_company']->name }}
                </p>
            @endif
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0 admin-page-actions">
            @php
                $websitePagePreviewUrl = $websiteDevPreviewUrl ?? route('home', [
                    'nexa_admin_preview' => 1,
                    'admin_back' => route('admin.website-pages.index', $wizardIndexQuery ?? [], false),
                ]);
                $listedPages = $pages ?? collect();
                $websitePagesSeoCount = $listedPages->count();
                $allListedPagesActive = $websitePagesSeoCount > 0 && $listedPages->every(fn ($listedPage) => (bool) $listedPage->is_active);
            @endphp
            <form method="POST" action="{{ route('admin.website-pages.set-listed-active', $wizardIndexQuery ?? []) }}" class="website-pages-live-switch m-0">
                @csrf
                @foreach ($wizardIndexQuery ?? [] as $wizKey => $wizVal)
                    <input type="hidden" name="{{ $wizKey }}" value="{{ $wizVal }}">
                @endforeach
                <input type="hidden" name="is_active" value="0">
                <label class="website-pages-live-switch__label inline-flex items-center gap-2 mb-0 {{ $websitePagesSeoCount < 1 ? 'opacity-55 cursor-not-allowed' : 'cursor-pointer' }}" title="Alleen actieve pagina's zijn zichtbaar op de website, ook in Website voorbeeld.">
                    <input type="checkbox"
                           name="is_active"
                           value="1"
                           class="kt-switch kt-switch-sm shrink-0"
                           {{ $allListedPagesActive ? 'checked' : '' }}
                           @disabled($websitePagesSeoCount < 1)
                           aria-label="Pagina's actief (zichtbaar op de website)"
                           onchange="this.form.submit()">
                    <span class="text-sm font-medium text-secondary-foreground whitespace-nowrap">Pagina's actief</span>
                </label>
            </form>
            <a href="{{ $websitePagePreviewUrl }}" target="_blank" rel="noopener" class="kt-btn website-pages-btn-preview">
                <i class="ki-filled ki-eye me-2"></i>
                Website voorbeeld
            </a>
            <form method="POST" action="{{ route('admin.website-pages.generate-seo-all', $wizardIndexQuery ?? []) }}" id="website-pages-generate-seo-all-form" class="m-0">
                @csrf
                @foreach ($wizardIndexQuery ?? [] as $wizKey => $wizVal)
                    <input type="hidden" name="{{ $wizKey }}" value="{{ $wizVal }}">
                @endforeach
                <button type="submit"
                        id="website-pages-generate-seo-all-btn"
                        class="kt-btn website-pages-btn-seo"
                        @disabled($websitePagesSeoCount < 1)
                        title="Genereer SEO-titels, meta-omschrijvingen en hero-teksten voor alle pagina's op deze lijst">
                    <i class="ki-filled ki-search-list me-2"></i>
                    SEO teksten
                </button>
            </form>
            <a href="{{ route('admin.website-pages.create', $wizardIndexQuery ?? []) }}" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-plus me-2"></i> Nieuwe pagina
            </a>
        </div>
    </div>

    @if(!empty($websitePagesTenantScopedActive))
        @include('admin.website-pages.partials.website-logo-size')
    @endif

    <div class="kt-card kt-card-grid w-full min-w-0">
        <div class="kt-card-content p-0 min-w-0">
            <div class="kt-scrollable-x-auto admin-table-scroll-wrap min-w-0">
            <table id="website-pages-table" class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full website-pages-table">
                <thead>
                    <tr>
                        <th class="website-pages-col-order" data-label="Volgorde">Volgorde</th>
                        <th class="website-pages-col-menu" data-label="Menuitem">Menuitem</th>
                        <th class="website-pages-col-slug" data-label="Slug">Slug</th>
                        <th class="website-pages-col-type" data-label="Type">Type</th>
                        <th class="website-pages-col-module" data-label="Module">Module</th>
                        @if(!empty($websiteTenantContext['visible'] ?? false) && empty($websitePagesManagingCentralSite))
                            <th class="website-pages-col-company" data-label="Bedrijf">Bedrijf</th>
                        @endif
                        <th class="website-pages-col-theme" data-label="Thema">Thema</th>
                        <th class="website-pages-col-status text-center" data-label="Status">Status</th>
                        <th class="website-pages-col-actions text-center" data-label="Acties">Acties</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pages as $page)
                        @php
                            $pageModule = $page->module_name ? '?module=' . rawurlencode($page->module_name) : '';
                            $wizSuffix = '';
                            if (! empty($wizardIndexQuery ?? [])) {
                                $wizSuffix = ($pageModule !== '' ? '&' : '?').http_build_query($wizardIndexQuery);
                            }
                        @endphp
                        @php
                            $rowEditUrl = route('admin.website-pages.builder-v2.edit', $page).$pageModule.$wizSuffix;
                            $reorderQuery = $wizardIndexQuery ?? [];
                            if ($page->module_name) {
                                $reorderQuery['module'] = $page->module_name;
                            }
                            $reorderUrl = route('admin.website-pages.reorder', $page);
                            if ($reorderQuery !== []) {
                                $reorderUrl .= '?'.http_build_query($reorderQuery);
                            }
                        @endphp
                        <tr class="website-page-row cursor-pointer hover:bg-gray-100/90 dark:hover:bg-white/[0.06] transition-colors" data-row-href="{{ $rowEditUrl }}" role="button" tabindex="0">
                            <td class="website-page-order-cell whitespace-nowrap" onclick="event.stopPropagation()">
                                <div class="flex items-center gap-1.5">
                                    <div class="flex flex-col">
                                        @if($loop->first)
                                            <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost opacity-30 cursor-not-allowed" disabled aria-label="Omhoog" title="Staat al bovenaan">
                                                <i class="ki-filled ki-arrow-up"></i>
                                            </button>
                                        @else
                                            <form action="{{ $reorderUrl }}" method="POST" class="m-0">
                                                @csrf
                                                @if($page->module_name)
                                                    <input type="hidden" name="module_name" value="{{ $page->module_name }}">
                                                @endif
                                                <input type="hidden" name="direction" value="up">
                                                <button type="submit" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" aria-label="Omhoog" title="Omhoog">
                                                    <i class="ki-filled ki-arrow-up"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @if($loop->last)
                                            <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost opacity-30 cursor-not-allowed" disabled aria-label="Omlaag" title="Staat al onderaan">
                                                <i class="ki-filled ki-arrow-down"></i>
                                            </button>
                                        @else
                                            <form action="{{ $reorderUrl }}" method="POST" class="m-0">
                                                @csrf
                                                @if($page->module_name)
                                                    <input type="hidden" name="module_name" value="{{ $page->module_name }}">
                                                @endif
                                                <input type="hidden" name="direction" value="down">
                                                <button type="submit" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" aria-label="Omlaag" title="Omlaag">
                                                    <i class="ki-filled ki-arrow-down"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                    <span class="tabular-nums">{{ $page->sort_order }}</span>
                                </div>
                            </td>
                            <td class="font-medium">{{ $page->publicNavLabel() }}</td>
                            <td><code class="text-xs break-all">{{ $page->slug }}</code></td>
                            <td>{{ $page->page_type }}</td>
                            <td>{{ $page->module_name ?? '—' }}</td>
                            @if(!empty($websiteTenantContext['visible'] ?? false) && empty($websitePagesManagingCentralSite))
                                @php
                                    $namesMap = $websitePagesCompanyNames ?? collect();
                                    $rowCompanyId = isset($page->company_id) && $page->company_id !== null && $page->company_id !== '' ? (int) $page->company_id : null;
                                    $rowCompanyName = $rowCompanyId !== null ? $namesMap->get($rowCompanyId) : null;
                                @endphp
                                <td class="text-sm">
                                    @if($rowCompanyId !== null)
                                        <span class="font-medium text-foreground">{{ $rowCompanyName ?? ('Bedrijf #'.$rowCompanyId) }}</span>
                                    @else
                                        <span class="text-muted-foreground">NEXA Suite</span>
                                    @endif
                                </td>
                            @endif
                            <td>{{ $activeTheme?->name ?? '—' }}</td>
                            <td class="website-page-status-cell text-center" onclick="event.stopPropagation()">
                                @php
                                    $toggleActiveUrl = route('admin.website-pages.toggle-active', $page);
                                    $toggleActiveQuery = $wizardIndexQuery ?? [];
                                    if ($page->module_name) {
                                        $toggleActiveQuery['module'] = $page->module_name;
                                    }
                                    if ($toggleActiveQuery !== []) {
                                        $toggleActiveUrl .= '?'.http_build_query($toggleActiveQuery);
                                    }
                                @endphp
                                <form method="POST" action="{{ $toggleActiveUrl }}" class="m-0 flex items-center justify-center">
                                    @csrf
                                    @foreach ($wizardIndexQuery ?? [] as $wizKey => $wizVal)
                                        <input type="hidden" name="{{ $wizKey }}" value="{{ $wizVal }}">
                                    @endforeach
                                    @if($page->module_name)
                                        <input type="hidden" name="module" value="{{ $page->module_name }}">
                                        <input type="hidden" name="module_name" value="{{ $page->module_name }}">
                                    @endif
                                    <input type="hidden" name="is_active" value="0">
                                    <label class="inline-flex items-center gap-2 mb-0 cursor-pointer" title="{{ $page->is_active ? 'Zichtbaar op de website' : 'Concept: niet zichtbaar op de website' }}">
                                        <input type="checkbox"
                                               name="is_active"
                                               value="1"
                                               class="kt-switch kt-switch-sm shrink-0"
                                               {{ $page->is_active ? 'checked' : '' }}
                                               aria-label="Actief (zichtbaar op de website)"
                                               onchange="this.form.submit()">
                                        <span class="sr-only">{{ $page->is_active ? 'Actief' : 'Inactief' }}</span>
                                    </label>
                                </form>
                            </td>
                            <td class="text-center relative website-page-actions-cell" onclick="event.stopPropagation()">
                                <div class="website-pages-actions-menu flex justify-center">
                                    <div class="relative">
                                        <button type="button" class="website-pages-actions-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" aria-label="Acties" aria-expanded="false" aria-haspopup="true">
                                            <i class="ki-filled ki-dots-vertical text-lg"></i>
                                        </button>
                                        <div class="website-pages-actions-dropdown kt-menu-dropdown kt-menu-default hidden absolute end-0 top-full z-[100] mt-1 min-w-[175px] rounded-lg border border-border bg-background py-1 shadow-lg" role="menu">
                                            <div class="kt-menu-item">
                                                <a class="kt-menu-link" href="{{ route('admin.website-pages.preview', $page) }}{{ $pageModule }}" target="_blank" rel="noopener">
                                                    <span class="kt-menu-icon">
                                                        <i class="ki-filled ki-eye"></i>
                                                    </span>
                                                    <span class="kt-menu-title">Voorbeeld</span>
                                                </a>
                                            </div>
                                            <div class="kt-menu-item">
                                                <form action="{{ $toggleActiveUrl }}" method="POST" class="block">
                                                    @csrf
                                                    @foreach ($wizardIndexQuery ?? [] as $wizKey => $wizVal)
                                                        <input type="hidden" name="{{ $wizKey }}" value="{{ $wizVal }}">
                                                    @endforeach
                                                    @if($page->module_name)
                                                        <input type="hidden" name="module" value="{{ $page->module_name }}">
                                                        <input type="hidden" name="module_name" value="{{ $page->module_name }}">
                                                    @endif
                                                    <input type="hidden" name="is_active" value="{{ $page->is_active ? '0' : '1' }}">
                                                    <button type="submit" class="kt-menu-link w-full text-left">
                                                        <span class="kt-menu-icon">
                                                            <i class="ki-filled {{ $page->is_active ? 'ki-cross-circle' : 'ki-check-circle' }}"></i>
                                                        </span>
                                                        <span class="kt-menu-title">{{ $page->is_active ? 'Deactiveren' : 'Activeren' }}</span>
                                                    </button>
                                                </form>
                                            </div>
                                            <div class="kt-menu-separator"></div>
                                            <div class="kt-menu-item">
                                                <a class="kt-menu-link" href="{{ route('admin.website-pages.builder-v2.edit', $page) }}{{ $pageModule }}{{ $wizSuffix }}">
                                                    <span class="kt-menu-icon">
                                                        <i class="ki-filled ki-pencil"></i>
                                                    </span>
                                                    <span class="kt-menu-title">Bewerken</span>
                                                </a>
                                            </div>
                                            <div class="kt-menu-item">
                                                <a class="kt-menu-link" href="{{ route('admin.website-pages.edit', $page) }}{{ $pageModule }}{{ $wizSuffix }}">
                                                    <span class="kt-menu-icon">
                                                        <i class="ki-filled ki-notepad-edit"></i>
                                                    </span>
                                                    <span class="kt-menu-title">Klassieke editor</span>
                                                </a>
                                            </div>
                                            <div class="kt-menu-separator"></div>
                                            <div class="kt-menu-item">
                                                <form action="{{ route('admin.website-pages.destroy', $page) }}{{ $pageModule }}{{ $wizSuffix }}"
                                                      method="POST"
                                                      class="website-page-delete-form block"
                                                      data-confirm-title="Pagina verwijderen"
                                                      data-confirm-message="Weet je zeker dat je de pagina “{{ $page->publicNavLabel() }}” wilt verwijderen? Dit kan niet ongedaan worden gemaakt.">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="kt-menu-link w-full text-left text-destructive">
                                                        <span class="kt-menu-icon">
                                                            <i class="ki-filled ki-trash"></i>
                                                        </span>
                                                        <span class="kt-menu-title">Verwijderen</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ !empty($websiteTenantContext['visible'] ?? false) ? 9 : 8 }}" class="text-center text-muted-foreground py-8">Nog geen website-pagina's. Maak er een aan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>

<div id="website-page-delete-modal"
     class="hidden fixed inset-0 z-[100000] items-center justify-center bg-zinc-950/70 p-4 backdrop-blur-md"
     role="dialog"
     aria-modal="true"
     aria-labelledby="website-page-delete-modal-title"
     hidden>
    <div class="website-page-delete-panel relative w-full max-w-md rounded-2xl border border-border shadow-2xl">
        <button type="button"
                class="absolute right-3 top-3 rounded-md p-1.5 text-muted-foreground hover:bg-accent hover:text-foreground"
                data-website-page-delete-dismiss
                aria-label="Sluiten">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
        <div class="website-page-delete-panel__header px-5 py-5 pr-12">
            <h3 id="website-page-delete-modal-title" class="text-lg font-semibold text-foreground mb-0">Pagina verwijderen</h3>
        </div>
        <div class="px-5 py-5">
            <p class="text-sm text-muted-foreground mb-0" data-website-page-delete-message>
                Weet je zeker dat je deze pagina wilt verwijderen?
            </p>
        </div>
        <div class="px-5 py-5 flex flex-wrap justify-end gap-2">
            <button type="button" class="kt-btn website-page-delete-cancel" data-website-page-delete-dismiss>Annuleren</button>
            <button type="button" class="kt-btn kt-btn-destructive" data-website-page-delete-confirm>Verwijderen</button>
        </div>
    </div>
</div>

@push('styles')
<style>
    .website-pages-actions-dropdown.is-open { display: block !important; }

    .website-page-delete-panel {
        background-color: var(--color-zinc-100);
    }
    .dark .website-page-delete-panel {
        background-color: var(--color-zinc-800);
    }
    .website-page-delete-panel__header {
        border-bottom: 1px solid var(--color-zinc-300);
    }
    .dark .website-page-delete-panel__header {
        border-bottom: 1px solid var(--color-zinc-500);
    }
    .website-page-delete-cancel {
        background-color: transparent !important;
        border: 1px solid var(--color-zinc-400) !important;
        color: var(--color-zinc-700) !important;
        box-shadow: none !important;
    }
    .website-page-delete-cancel:hover,
    .website-page-delete-cancel:focus {
        background-color: transparent !important;
        border-color: var(--color-zinc-500) !important;
        color: var(--color-zinc-900) !important;
    }
    .dark .website-page-delete-cancel {
        background-color: transparent !important;
        border: 1px solid var(--color-zinc-400) !important;
        color: var(--color-zinc-200) !important;
    }
    .dark .website-page-delete-cancel:hover,
    .dark .website-page-delete-cancel:focus {
        background-color: transparent !important;
        border-color: var(--color-zinc-300) !important;
        color: #fff !important;
    }

    #content .website-pages-btn-preview {
        background-color: #ea580c;
        color: #fff;
        border-color: transparent;
    }
    #content .website-pages-btn-preview:hover {
        background-color: #c2410c;
        color: #fff;
    }
    #content .website-pages-btn-seo {
        background-color: #7c3aed;
        color: #fff;
        border-color: transparent;
    }
    #content .website-pages-btn-seo:hover {
        background-color: #6d28d9;
        color: #fff;
    }
    #content .website-pages-btn-seo:disabled {
        opacity: 0.55;
    }

    #content #website-pages-table.website-pages-table .website-pages-col-order {
        width: 7.5rem;
    }

    #content #website-pages-table.website-pages-table :is(th, td) {
        vertical-align: middle !important;
    }

    #content #website-pages-table.website-pages-table .website-page-order-cell .kt-btn-icon {
        width: 1.75rem;
        height: 1.5rem;
        min-height: 1.5rem;
    }

    #content #website-pages-table.website-pages-table .website-pages-col-type {
        width: 5.5rem;
    }

    #content .website-pages-live-switch {
        display: inline-flex;
        align-items: center;
        min-height: 2.5rem;
        padding: 0;
        border: none;
        background: transparent;
    }

    #content #website-pages-table.website-pages-table .website-pages-col-status,
    #content #website-pages-table.website-pages-table .website-page-status-cell {
        width: 5.5rem;
        text-align: center !important;
        vertical-align: middle !important;
    }

    #content #website-pages-table.website-pages-table .website-pages-col-actions,
    #content #website-pages-table.website-pages-table .website-page-actions-cell {
        width: 4.5rem;
        text-align: center !important;
        vertical-align: middle !important;
    }

    #content #website-pages-table.website-pages-table .website-pages-col-slug {
        width: 14%;
    }

    #content #website-pages-table.website-pages-table .website-pages-col-menu {
        width: 16%;
    }

    #content #website-pages-table.website-pages-table .website-pages-col-module,
    #content #website-pages-table.website-pages-table .website-pages-col-company,
    #content #website-pages-table.website-pages-table .website-pages-col-theme {
        width: 12%;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var toggles = document.querySelectorAll('.website-pages-actions-toggle');
    var seoAllForm = document.getElementById('website-pages-generate-seo-all-form');
    var seoAllBtn = document.getElementById('website-pages-generate-seo-all-btn');
    if (seoAllForm && seoAllBtn && !seoAllBtn.disabled) {
        seoAllForm.addEventListener('submit', function(e) {
            var count = {{ (int) ($pages ?? collect())->count() }};
            var msg = count === 1
                ? 'SEO-titel, meta-omschrijving en hero-tekst van deze pagina overschrijven?'
                : 'SEO-titels, meta-omschrijvingen en hero-teksten van alle ' + count + " pagina's op deze lijst overschrijven?";
            if (!window.confirm(msg)) {
                e.preventDefault();
                return;
            }
            seoAllBtn.setAttribute('aria-busy', 'true');
            seoAllBtn.innerHTML = '<i class="ki-filled ki-arrows-circle me-2 animate-spin"></i> Bezig…';
        });
    }

    var openDropdown = null;

    document.querySelectorAll('.website-page-row[data-row-href]').forEach(function(row) {
        row.addEventListener('click', function(e) {
            if (e.target.closest('.website-page-actions-cell, .website-page-order-cell')) return;
            var url = row.getAttribute('data-row-href');
            if (url) window.location.href = url;
        });
        row.addEventListener('keydown', function(e) {
            if (e.target.closest('.website-page-actions-cell, .website-page-order-cell')) return;
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                var url = row.getAttribute('data-row-href');
                if (url) window.location.href = url;
            }
        });
    });

    function closeAll() {
        if (openDropdown) {
            openDropdown.classList.remove('is-open');
            openDropdown.classList.add('hidden');
            openDropdown.style.cssText = '';
            openDropdown.previousElementSibling.setAttribute('aria-expanded', 'false');
            openDropdown = null;
        }
    }

    function positionDropdown(btn, dropdown) {
        var rect = btn.getBoundingClientRect();
        var w = 175;
        dropdown.style.position = 'fixed';
        dropdown.style.left = (rect.right - w) + 'px';
        dropdown.style.top = (rect.bottom + 6) + 'px';
        dropdown.style.minWidth = w + 'px';
        dropdown.style.zIndex = '99999';
    }

    toggles.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            var wrapper = btn.closest('.website-pages-actions-menu');
            var dropdown = wrapper.querySelector('.website-pages-actions-dropdown');

            if (dropdown && dropdown.classList.contains('is-open')) {
                closeAll();
                return;
            }
            closeAll();
            positionDropdown(btn, dropdown);
            dropdown.classList.add('is-open');
            dropdown.classList.remove('hidden');
            btn.setAttribute('aria-expanded', 'true');
            openDropdown = dropdown;
        });
    });

    document.addEventListener('click', function() { closeAll(); });

    var deleteModal = document.getElementById('website-page-delete-modal');
    var deleteTitleEl = document.getElementById('website-page-delete-modal-title');
    var deleteMessageEl = deleteModal ? deleteModal.querySelector('[data-website-page-delete-message]') : null;
    var pendingDeleteForm = null;

    function openDeleteModal(form) {
        pendingDeleteForm = form;
        if (deleteTitleEl) {
            deleteTitleEl.textContent = form.getAttribute('data-confirm-title') || 'Pagina verwijderen';
        }
        if (deleteMessageEl) {
            deleteMessageEl.textContent = form.getAttribute('data-confirm-message')
                || 'Weet je zeker dat je deze pagina wilt verwijderen? Dit kan niet ongedaan worden gemaakt.';
        }
        closeAll();
        if (deleteModal.parentElement !== document.body) {
            document.body.appendChild(deleteModal);
        }
        deleteModal.hidden = false;
        deleteModal.classList.remove('hidden');
        deleteModal.classList.add('flex');
        var cancelBtn = deleteModal.querySelector('[data-website-page-delete-dismiss].kt-btn');
        if (cancelBtn) {
            cancelBtn.focus();
        }
    }

    function closeDeleteModal() {
        pendingDeleteForm = null;
        deleteModal.hidden = true;
        deleteModal.classList.add('hidden');
        deleteModal.classList.remove('flex');
    }

    if (deleteModal) {
        document.addEventListener('submit', function(e) {
            var form = e.target.closest('.website-page-delete-form');
            if (!form) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            openDeleteModal(form);
        });

        deleteModal.addEventListener('click', function(e) {
            if (e.target.closest('[data-website-page-delete-confirm]')) {
                e.preventDefault();
                e.stopPropagation();
                if (pendingDeleteForm) {
                    pendingDeleteForm.submit();
                }
                return;
            }
            if (e.target === deleteModal || e.target.closest('[data-website-page-delete-dismiss]')) {
                e.preventDefault();
                e.stopPropagation();
                closeDeleteModal();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !deleteModal.hidden) {
                e.preventDefault();
                closeDeleteModal();
            }
        });
    }
});
</script>
@endpush
@endsection
