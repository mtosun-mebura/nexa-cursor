@extends('admin.layouts.app')

@section('title', 'Modules Beheer')

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">
            Modules Beheer
        </h1>
    </div>

    <!-- Success Alert -->
    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5" id="success-alert" role="alert">
            <i class="ki-filled ki-check-circle me-2"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(session('info'))
        <div class="kt-alert kt-alert-info mb-5" id="info-alert" role="alert">
            <i class="ki-filled ki-information-5 me-2"></i>
            {{ session('info') }}
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="kt-card mb-5">
        <div class="kt-card-content">
            <div class="flex flex-col sm:flex-row lg:px-10 py-1.5 gap-2">
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['total_modules'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Totaal Modules
                    </span>
                </div>
                <span class="hidden sm:block not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['installed_modules'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Geïnstalleerd
                    </span>
                </div>
                <span class="hidden sm:block not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['active_modules'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Actief
                    </span>
                </div>
                <span class="hidden sm:block not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['internal_modules'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Intern
                    </span>
                </div>
                <span class="hidden sm:block not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['external_modules'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Extern
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid min-w-full">
            <div class="kt-card-header py-5 flex-wrap gap-2">
                <h3 class="kt-card-title text-sm pb-3 w-full">
                    Toon 1 tot {{ count($availableModules) }} van {{ count($availableModules) }} modules
                </h3>
            </div>
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                    <thead>
                        <tr>
                            <th class="min-w-[200px] text-secondary-foreground font-normal">Module</th>
                            <th class="min-w-[100px] text-secondary-foreground font-normal">Versie</th>
                            <th class="min-w-[100px] text-secondary-foreground font-normal">Type</th>
                            <th class="min-w-[100px] text-secondary-foreground font-normal">Status</th>
                            <th class="text-secondary-foreground font-normal text-center px-4">Acties</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($availableModules as $module)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="flex-shrink-0">
                                            <i class="{{ $module['icon'] }} text-2xl text-primary"></i>
                                        </div>
                                        <div>
                                            <div class="font-medium text-foreground">{{ $module['display_name'] }}</div>
                                            <div class="text-xs text-muted-foreground mt-0.5">{{ $module['description'] }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-xs px-2 py-1 rounded bg-primary/10 text-primary font-medium">
                                        {{ $module['version'] }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-xs px-2 py-1 rounded {{ $module['type'] === 'internal' ? 'bg-primary/10 text-primary' : 'bg-secondary/10 text-secondary' }}">
                                        {{ $module['type'] === 'internal' ? 'Intern' : 'Extern' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="flex flex-col gap-1">
                                        <div class="flex flex-wrap gap-1">
                                            @if($module['installed'])
                                                <span class="text-xs px-2 py-1 rounded font-medium" style="background-color: #2563eb !important; color: #ffffff !important;">
                                                    <i class="ki-filled ki-check me-1" style="color: #ffffff !important;"></i> Geïnstalleerd
                                                </span>
                                            @else
                                                <span class="text-xs px-2 py-1 rounded bg-muted/50 text-foreground border border-border">
                                                    Niet geïnstalleerd
                                                </span>
                                            @endif
                                            @if($module['active'])
                                                <span class="text-xs px-2 py-1 rounded font-medium" style="background-color: #16a34a !important; color: #ffffff !important;">
                                                    <i class="ki-filled ki-check-circle me-1" style="color: #ffffff !important;"></i> Actief
                                                </span>
                                            @endif
                                        </div>
                                        @if($module['installed'])
                                            <span class="text-xs text-muted-foreground block" title="Centrale applicatie (users, modules, settings).">
                                                Database: <code class="bg-muted/50 px-1 rounded">{{ $mainDatabaseName !== '' ? $mainDatabaseName : '—' }}</code>
                                            </span>
                                            @if(!empty($module['schema_name']))
                                                <span class="text-xs text-muted-foreground block" title="PostgreSQL-schema in dezelfde database (MODULE_DATABASE_STRATEGY=schema).">
                                                    Schema: <code class="bg-muted/50 px-1 rounded">{{ $module['schema_name'] }}</code>
                                                </span>
                                            @elseif(!empty($module['database_name']))
                                                <span class="text-xs text-muted-foreground block" title="Aparte database per module (MODULE_DATABASE_STRATEGY=database).">
                                                    Module-database: <code class="bg-muted/50 px-1 rounded">{{ $module['database_name'] }}</code>
                                                </span>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4" onclick="event.stopPropagation();">
                                    <div class="kt-menu flex justify-center" data-kt-menu="true">
                                        <div class="kt-menu-item" 
                                             data-kt-menu-item-offset="0, 10px" 
                                             data-kt-menu-item-placement="bottom-end" 
                                             data-kt-menu-item-placement-rtl="bottom-start" 
                                             data-kt-menu-item-toggle="dropdown" 
                                             data-kt-menu-item-trigger="click">
                                            <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" type="button" aria-label="Acties" aria-expanded="false" data-modules-action-toggle="true">
                                                <x-heroicon-o-ellipsis-vertical class="w-5 h-5" />
                                            </button>
                                            <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]" data-kt-menu-dismiss="true">
                                                @if(!$module['installed'])
                                                    <div class="kt-menu-item">
                                                        @if($module['installing'] ?? false)
                                                            <span class="kt-menu-link w-full text-left text-muted-foreground">
                                                                <span class="kt-menu-icon">
                                                                    <x-heroicon-o-arrow-path class="w-5 h-5 animate-spin" />
                                                                </span>
                                                                <span class="kt-menu-title">Bezig met installeren...</span>
                                                            </span>
                                                        @else
                                                            <form action="{{ route('admin.modules.install', $module['name']) }}" method="POST" style="display: inline;">
                                                                @csrf
                                                                <button type="submit" class="kt-menu-link w-full text-left">
                                                                    <span class="kt-menu-icon">
                                                                        <x-heroicon-o-arrow-down-tray class="w-5 h-5" />
                                                                    </span>
                                                                    <span class="kt-menu-title">Installeer</span>
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                @else
                                                    <div class="kt-menu-item">
                                                        <a class="kt-menu-link" href="{{ route('admin.modules.config', $module['name']) }}" title="Onderdelen selecteren">
                                                            <span class="kt-menu-icon">
                                                                <x-heroicon-o-cog-6-tooth class="w-5 h-5" />
                                                            </span>
                                                            <span class="kt-menu-title">Configureren</span>
                                                        </a>
                                                    </div>
                                                    <div class="kt-menu-item">
                                                        <form action="{{ route('admin.modules.run-migrations', $module['name']) }}" method="POST" style="display: inline;"
                                                              onsubmit="return confirm('Module-migraties opnieuw uitvoeren? Gebruik dit na wijziging van MODULE_DATABASE_STRATEGY in .env (en php artisan config:clear). Doorgaan?');">
                                                            @csrf
                                                            <button type="submit" class="kt-menu-link w-full text-left" title="Zelfde pad als bij install: hoofddatabase of eigen module-database">
                                                                <span class="kt-menu-icon">
                                                                    <x-heroicon-o-table-cells class="w-5 h-5" />
                                                                </span>
                                                                <span class="kt-menu-title">Migraties opnieuw</span>
                                                            </button>
                                                        </form>
                                                    </div>
                                                    @if(!$module['active'])
                                                        <div class="kt-menu-item">
                                                            <form action="{{ route('admin.modules.activate', $module['name']) }}" method="POST" style="display: inline;">
                                                                @csrf
                                                                <button type="submit" class="kt-menu-link w-full text-left">
                                                                    <span class="kt-menu-icon">
                                                                        <x-heroicon-o-check-circle class="w-5 h-5" />
                                                                    </span>
                                                                    <span class="kt-menu-title">Activeer</span>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    @else
                                                        <div class="kt-menu-item">
                                                            <form action="{{ route('admin.modules.deactivate', $module['name']) }}" method="POST" style="display: inline;">
                                                                @csrf
                                                                <button type="submit" class="kt-menu-link w-full text-left">
                                                                    <span class="kt-menu-icon">
                                                                        <x-heroicon-o-x-circle class="w-5 h-5" />
                                                                    </span>
                                                                    <span class="kt-menu-title">Deactiveer</span>
                                                                </button>
                                                            </form>
                                                        </div>
                                                        @php
                                                            $testRoute = 'admin.' . $module['name'] . '.test';
                                                        @endphp
                                                        @if(Route::has($testRoute))
                                                            <div class="kt-menu-item">
                                                                <a class="kt-menu-link" href="{{ route($testRoute) }}" target="_blank">
                                                                    <span class="kt-menu-icon">
                                                                        <x-heroicon-o-link class="w-5 h-5" />
                                                                    </span>
                                                                    <span class="kt-menu-title">Test</span>
                                                                </a>
                                                            </div>
                                                        @endif
                                                    @endif
                                                    @if($hasModuleDatabases)
                                                    <div class="kt-menu-separator"></div>
                                                    <div class="kt-menu-item">
                                                        <form action="{{ route('admin.modules.database-reset') }}" method="POST" style="display: inline;"
                                                              onsubmit="return confirm('Alle tabellen worden geleegd. Alleen super admin m.tosun@mebura.nl blijft bestaan met alle rechten. Weet je het zeker?');">
                                                            @csrf
                                                            <input type="hidden" name="confirm_reset" value="yes">
                                                            <button type="submit" class="kt-menu-link w-full text-left text-warning">
                                                                <span class="kt-menu-icon">
                                                                    <x-heroicon-o-arrow-path class="w-5 h-5" />
                                                                </span>
                                                                <span class="kt-menu-title">Database reset</span>
                                                            </button>
                                                        </form>
                                                    </div>
                                                    <div class="kt-menu-item">
                                                        <form action="{{ route('admin.modules.database-dummydata', $module['name']) }}" method="POST" style="display: inline;">
                                                            @csrf
                                                            <button type="submit" class="kt-menu-link w-full text-left">
                                                                <span class="kt-menu-icon">
                                                                    <x-heroicon-o-cube class="w-5 h-5" />
                                                                </span>
                                                                <span class="kt-menu-title">Database dummydata</span>
                                                            </button>
                                                        </form>
                                                    </div>
                                                    @endif
                                                    <div class="kt-menu-separator"></div>
                                                    <div class="kt-menu-item">
                                                        <form action="{{ route('admin.modules.uninstall', $module['name']) }}" 
                                                              method="POST" 
                                                              style="display: inline;"
                                                              onsubmit="return confirm('Weet je zeker dat je deze module wilt verwijderen?')">
                                                            @csrf
                                                            <button type="submit" class="kt-menu-link w-full text-left text-danger">
                                                                <span class="kt-menu-icon">
                                                                    <x-heroicon-o-trash class="w-5 h-5" />
                                                                </span>
                                                                <span class="kt-menu-title">Verwijder</span>
                                                            </button>
                                                        </form>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-10">
                                    <i class="ki-filled ki-information-5 text-4xl text-muted-foreground mb-3 block"></i>
                                    <p class="text-muted-foreground">Geen modules gevonden</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Info Card -->
        <div class="kt-card min-w-full bg-primary/5 border border-primary/20">
            <div class="kt-card-body">
                <div class="flex items-start gap-3 p-2">
                    <i class="ki-filled ki-information-5 text-2xl text-primary"></i>
                    <div>
                        <h4 class="font-semibold mb-1">Hoe werken modules?</h4>
                        <p class="text-sm text-muted-foreground mb-2">
                            Modules zijn uitbreidbare functionaliteiten die kunnen worden geïnstalleerd en geactiveerd. Wanneer een module geactiveerd is, worden de routes automatisch geregistreerd onder <code class="px-1 py-0.5 bg-background rounded">/admin/{module-name}/</code>
                        </p>
                        <ul class="text-sm text-muted-foreground space-y-1 list-disc list-inside">
                            @if(($moduleDatabaseStrategy ?? 'schema') === 'schema' && $hasModuleDatabases)
                            <li><strong>Schema per module:</strong> één database <code class="px-1 py-0.5 bg-background rounded">{{ $mainDatabaseName !== '' ? $mainDatabaseName : 'DB_DATABASE' }}</code>, module-tabellen in PostgreSQL-schema's zoals <code class="px-1 py-0.5 bg-background rounded">nexa_taxi</code> (standaard: <code class="px-1 py-0.5 bg-background rounded">MODULE_DATABASE_STRATEGY=schema</code>).</li>
                            @elseif($hasModuleDatabases)
                            <li><strong>Database per module:</strong> aparte databases <code class="px-1 py-0.5 bg-background rounded">nexa_*</code> (<code class="px-1 py-0.5 bg-background rounded">MODULE_DATABASE_STRATEGY=database</code>).</li>
                            @else
                            <li>Geen PostgreSQL-schema's of module-databases in deze setup.</li>
                            @endif
                            <li>Na activatie: Routes beschikbaar op <code class="px-1 py-0.5 bg-background rounded">/admin/skillmatching/*</code></li>
                            <li>Test route: <code class="px-1 py-0.5 bg-background rounded">/admin/skillmatching/test</code> (werkt alleen als module actief is)</li>
                            <li>Menu items worden automatisch toegevoegd wanneer module actief is</li>
                            <li>Permissions worden automatisch geregistreerd bij installatie</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loader modal bij module-acties: boven alles (sidebar, header, dropdowns) -->
<div id="modules-action-loader-modal" class="modules-loader-overlay fixed inset-0 hidden" aria-hidden="true" aria-label="Bezig">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-md modules-loader-backdrop"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="relative flex flex-col items-center gap-5 rounded-2xl bg-card border-2 border-border shadow-2xl px-12 py-10 min-w-[240px] modules-loader-card bg-gray-100 dark:bg-gray-700">
            <div class="modules-loader-spinner h-20 w-20 rounded-full border-4" style="animation: modules-spin 0.8s linear infinite;"></div>
            <p class="text-base font-semibold text-foreground">Bezig met verwerken...</p>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Loader modal: boven sidebar, header en alle dropdowns */
    .modules-loader-overlay {
        z-index: 2147483647 !important;
    }
    .modules-loader-backdrop {
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }
    /* Spinner altijd zichtbaar: light en dark mode */
    .modules-loader-spinner {
        flex-shrink: 0;
        border-color: rgba(0, 0, 0, 0.12);
        border-top-color: #2563eb;
    }
    .dark .modules-loader-spinner,
    html.dark .modules-loader-spinner {
        border-color: rgba(255, 255, 255, 0.15);
        border-top-color: #60a5fa;
    }
    @keyframes modules-spin {
        to { transform: rotate(360deg); }
    }
    /* Ensure dropdown can overflow table cells without stretching them */
    .kt-card-table td:last-child {
        position: relative;
        overflow: visible !important;
        width: auto !important;
        min-width: auto !important;
        max-width: none !important;
    }

    .kt-card-table td:last-child .kt-menu-item {
        position: static;
    }

    .kt-card-table td:last-child .kt-menu-dropdown {
        position: fixed !important;
        z-index: 99999 !important;
    }
    
    .kt-card-table td:last-child .kt-menu-item.show .kt-menu-dropdown,
    .kt-card-table td:last-child .kt-menu-item[data-kt-menu-item-toggle="dropdown"].show .kt-menu-dropdown {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    .kt-card-table td:last-child .kt-menu-item.show {
        z-index: 99999 !important;
    }

    .kt-card-table td:last-child .kt-menu-item.show .kt-menu-dropdown {
        z-index: 99999 !important;
    }
    
    /* Ensure table and card allow overflow */
    .kt-card-table {
        overflow: visible !important;
    }
    
    .kt-scrollable-x-auto {
        overflow-x: auto !important;
        overflow-y: visible !important;
    }
    
    /* Ensure thead and th allow overflow */
    .kt-card-table table thead th:last-child {
        overflow: visible !important;
    }
    
    .kt-card-table table thead {
        overflow: visible !important;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Loader-modal bij module-acties
    var loaderModal = document.getElementById('modules-action-loader-modal');
    function showLoader() {
        if (loaderModal) {
            loaderModal.classList.remove('hidden');
            loaderModal.classList.add('flex', 'flex-col');
        }
    }
    function hideLoader() {
        if (loaderModal) {
            loaderModal.classList.add('hidden');
            loaderModal.classList.remove('flex', 'flex-col');
        }
    }

    // Forms in actie-dropdown: via fetch, loader direct weg zodra response binnen is
    document.querySelectorAll('.kt-card-table .kt-menu-dropdown form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var onsubmitAttr = form.getAttribute('onsubmit');
            if (onsubmitAttr && onsubmitAttr.indexOf('confirm') >= 0) {
                var msg = 'Weet je het zeker?';
                if (onsubmitAttr.indexOf('Alle tabellen') >= 0) msg = 'Alle tabellen worden geleegd. Alleen super admin m.tosun@mebura.nl blijft bestaan. Weet je het zeker?';
                if (onsubmitAttr.indexOf('verwijderen') >= 0) msg = 'Weet je zeker dat je deze module wilt verwijderen?';
                if (!confirm(msg)) return;
            }
            showLoader();
            var formData = new FormData(form);
            var action = form.getAttribute('action') || '';
            var method = (form.getAttribute('method') || 'get').toUpperCase();
            fetch(action, { method: method, body: method === 'POST' ? formData : null, redirect: 'manual' })
                .then(function(response) {
                    hideLoader();
                    var url = response.headers.get('Location');
                    if (response.status >= 300 && response.status < 400 && url) {
                        window.location.href = url.startsWith('http') ? url : (window.location.origin + (url.startsWith('/') ? url : ('/' + url)));
                    } else {
                        window.location.reload();
                    }
                })
                .catch(function() {
                    hideLoader();
                    form.submit();
                });
        });
    });

    // Links in actie-dropdown (zelfde venster): fetch, loader weg bij response, dan navigeer
    document.querySelectorAll('.kt-card-table .kt-menu-dropdown a.kt-menu-link[href]:not([target="_blank"])').forEach(function(link) {
        link.addEventListener('click', function(e) {
            var href = this.getAttribute('href');
            if (!href || href === '#') return;
            e.preventDefault();
            showLoader();
            fetch(href, { redirect: 'manual' })
                .then(function(response) {
                    hideLoader();
                    window.location.href = href;
                })
                .catch(function() {
                    hideLoader();
                    window.location.href = href;
                });
        });
    });

    // Scroll naar melding zodat deze zichtbaar is na install/activate/etc.
    var alertEl = document.getElementById('error-alert') || document.getElementById('success-alert') || document.getElementById('info-alert');
    if (alertEl) {
        alertEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // Initialize KTMenu for action menus
    function initializeMenus() {
        if (window.KTMenu && typeof window.KTMenu.init === 'function') {
            try {
                window.KTMenu.init();
            } catch (e) {
                // Silently fail if KTMenu not available
            }
        } else {
            // Retry if KTMenu not loaded yet
            setTimeout(initializeMenus, 100);
        }
    }
    
    // Initialize immediately and after a short delay
    initializeMenus();
    setTimeout(initializeMenus, 300);
    
    function closeModuleActionMenus(exceptMenuItem) {
        document.querySelectorAll('.kt-card-table .kt-menu-item.show').forEach(function(item) {
            if (exceptMenuItem && item === exceptMenuItem) return;
            item.classList.remove('show');
            const toggle = item.querySelector('[data-modules-action-toggle="true"]');
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }
            const dropdown = item.querySelector('.kt-menu-dropdown');
            if (dropdown) {
                dropdown.style.display = 'none';
            }
        });
    }

    // Event delegation keeps working for dynamically replaced table rows
    document.addEventListener('click', function(e) {
        const toggle = e.target.closest('[data-modules-action-toggle="true"]');

        if (toggle) {
            e.preventDefault();
            e.stopPropagation();
            if (typeof e.stopImmediatePropagation === 'function') {
                e.stopImmediatePropagation();
            }

            const menuItem = toggle.closest('.kt-menu-item');
            if (!menuItem) return;

            const dropdown = menuItem.querySelector('.kt-menu-dropdown');
            if (!dropdown) return;

            const isShowing = menuItem.classList.contains('show');
            closeModuleActionMenus(menuItem);

            if (isShowing) {
                menuItem.classList.remove('show');
                toggle.setAttribute('aria-expanded', 'false');
                dropdown.style.display = 'none';
                return;
            }

            menuItem.classList.add('show');
            toggle.setAttribute('aria-expanded', 'true');

            const buttonRect = toggle.getBoundingClientRect();
            dropdown.style.position = 'fixed';
            dropdown.style.left = (buttonRect.right - 175) + 'px';
            dropdown.style.top = (buttonRect.bottom + 5) + 'px';
            dropdown.style.right = 'auto';
            dropdown.style.minWidth = '175px';
            dropdown.style.width = '175px';
            dropdown.style.zIndex = '99999';
            dropdown.style.display = 'block';
            dropdown.style.visibility = 'visible';
            dropdown.style.opacity = '1';
            return;
        }

        // Close dropdowns when clicking outside module action menus
        if (!e.target.closest('.kt-card-table .kt-menu-item')) {
            closeModuleActionMenus();
        }
    }, true);
});
</script>
@endpush

@endsection
