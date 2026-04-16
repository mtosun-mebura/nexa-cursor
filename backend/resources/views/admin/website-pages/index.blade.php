@extends('admin.layouts.app')

@section('title', "Website Pagina's")

@section('content')
<div class="kt-container-fixed">
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
            @php
                $wtc = $websiteTenantContext ?? ['visible' => false];
            @endphp
            @if(!empty($wtc['visible']) && !empty($wtc['effective_company']))
                <p class="text-sm text-muted-foreground mt-2 mb-0">
                    <span class="text-foreground font-medium">Actieve tenant:</span> {{ $wtc['effective_company']->name }}
                    <span class="text-muted-foreground">— nieuwe pagina&apos;s worden standaard aan dit bedrijf gekoppeld. Elke pagina hoort bij één bedrijf.</span>
                </p>
            @elseif(!empty($wtc['visible']))
                <p class="text-sm text-muted-foreground mt-2 mb-0">
                    <span class="text-foreground font-medium">Geen tenant gekozen</span> in de sidebar: bij <strong>nieuwe pagina</strong> kiest u een bedrijf in het formulier. Bestaande pagina&apos;s tonen hun gekoppelde bedrijfsnaam.
                </p>
            @endif
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            @php
                $previewAdminBack = route('admin.website-pages.index', $wizardIndexQuery ?? [], false);
            @endphp
            <a href="{{ route('home', ['nexa_admin_preview' => 1, 'admin_back' => $previewAdminBack]) }}" target="_blank" rel="noopener" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-eye me-2"></i>
                Pagina voorbeeld
            </a>
            <a href="{{ route('admin.website-pages.create', $wizardIndexQuery ?? []) }}" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-plus me-2"></i> Nieuwe pagina
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5">
            <i class="ki-filled ki-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif

    <div class="kt-card">
        <div class="kt-card-table kt-scrollable-x-auto">
            <table class="kt-table">
                <thead>
                    <tr>
                        <th>Volgorde</th>
                        <th>Titel</th>
                        <th>Slug</th>
                        <th>Type</th>
                        <th>Module (bij welke module)</th>
                        @if(!empty($websiteTenantContext['visible'] ?? false))
                            <th>Bedrijf</th>
                        @endif
                        <th>Thema</th>
                        <th>Status</th>
                        <th class="text-end">Acties</th>
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
                        <tr class="website-page-row cursor-pointer hover:bg-gray-100/90 dark:hover:bg-white/[0.06] transition-colors" data-preview-url="{{ route('admin.website-pages.preview', $page) }}{{ $pageModule }}" role="button" tabindex="0">
                            <td>{{ $page->sort_order }}</td>
                            <td>{{ $page->title }}</td>
                            <td><code>{{ $page->slug }}</code></td>
                            <td>{{ $page->page_type }}</td>
                            <td>{{ $page->module_name ?? '—' }}</td>
                            @if(!empty($websiteTenantContext['visible'] ?? false))
                                @php
                                    $namesMap = $websitePagesCompanyNames ?? collect();
                                    $rowCompanyId = isset($page->company_id) && $page->company_id !== null && $page->company_id !== '' ? (int) $page->company_id : null;
                                    $rowCompanyName = $rowCompanyId !== null ? $namesMap->get($rowCompanyId) : null;
                                    $listTenant = $websiteTenantContext['effective_company'] ?? null;
                                @endphp
                                <td class="text-sm align-top">
                                    @if($rowCompanyId !== null)
                                        <span class="font-medium text-foreground">{{ $rowCompanyName ?? ('Bedrijf #'.$rowCompanyId) }}</span>
                                        @if($rowCompanyName)
                                            <span class="block text-xs text-muted-foreground mt-0.5">Gekoppeld aan dit bedrijf.</span>
                                        @endif
                                    @elseif($listTenant)
                                        <span class="text-muted-foreground">Niet gekoppeld</span>
                                        <span class="block text-xs text-muted-foreground mt-0.5">Geen bedrijfs-id op deze pagina. Open <strong>Bewerken</strong> om het bedrijf te kiezen (of sla op met deze tenant in de sidebar).</span>
                                    @else
                                        <span class="text-muted-foreground">Niet gekoppeld</span>
                                        <span class="block text-xs text-muted-foreground mt-0.5">Kies een bedrijf bij het aanmaken of bewerken van de pagina.</span>
                                    @endif
                                </td>
                            @endif
                            <td>{{ $activeTheme?->name ?? '—' }}</td>
                            <td>
                                @if($page->is_active)
                                    <span class="kt-badge kt-badge-success">Actief</span>
                                @else
                                    <span class="kt-badge kt-badge-secondary">Inactief</span>
                                @endif
                            </td>
                            <td class="text-end relative website-page-actions-cell" onclick="event.stopPropagation()">
                                <div class="website-pages-actions-menu flex justify-end">
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
                                            <div class="kt-menu-separator"></div>
                                            <div class="kt-menu-item">
                                                <a class="kt-menu-link" href="{{ route('admin.website-pages.edit', $page) }}{{ $pageModule }}{{ $wizSuffix }}">
                                                    <span class="kt-menu-icon">
                                                        <i class="ki-filled ki-pencil"></i>
                                                    </span>
                                                    <span class="kt-menu-title">Bewerken</span>
                                                </a>
                                            </div>
                                            <div class="kt-menu-separator"></div>
                                            <div class="kt-menu-item">
                                                <form action="{{ route('admin.website-pages.destroy', $page) }}{{ $pageModule }}{{ $wizSuffix }}" method="POST" class="block" onsubmit="return confirm('Pagina verwijderen?');">
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

@push('styles')
<style>
    .website-pages-actions-dropdown.is-open { display: block !important; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var toggles = document.querySelectorAll('.website-pages-actions-toggle');
    var openDropdown = null;

    document.querySelectorAll('.website-page-row').forEach(function(row) {
        row.addEventListener('click', function(e) {
            if (e.target.closest('.website-page-actions-cell')) return;
            var url = row.getAttribute('data-preview-url');
            if (url) window.open(url, '_blank', 'noopener');
        });
        row.addEventListener('keydown', function(e) {
            if (e.target.closest('.website-page-actions-cell')) return;
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                var url = row.getAttribute('data-preview-url');
                if (url) window.open(url, '_blank', 'noopener');
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
});
</script>
@endpush
@endsection
