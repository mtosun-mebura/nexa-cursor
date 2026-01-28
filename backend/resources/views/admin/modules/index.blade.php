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

    @if(session('error'))
        <div class="kt-alert kt-alert-danger mb-5" id="error-alert" role="alert">
            <i class="ki-filled ki-cross-circle me-2"></i>
            {{ session('error') }}
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
                                </td>
                                <td class="px-4" onclick="event.stopPropagation();">
                                    <div class="kt-menu flex justify-center" data-kt-menu="true">
                                        <div class="kt-menu-item" 
                                             data-kt-menu-item-offset="0, 10px" 
                                             data-kt-menu-item-placement="bottom-end" 
                                             data-kt-menu-item-placement-rtl="bottom-start" 
                                             data-kt-menu-item-toggle="dropdown" 
                                             data-kt-menu-item-trigger="click">
                                            <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" type="button" aria-label="Acties">
                                                <x-heroicon-o-ellipsis-vertical class="w-5 h-5" />
                                            </button>
                                            <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]" data-kt-menu-dismiss="true">
                                                @if(!$module['installed'])
                                                    <div class="kt-menu-item">
                                                        <form action="{{ route('admin.modules.install', $module['name']) }}" method="POST" style="display: inline;">
                                                            @csrf
                                                            <button type="submit" class="kt-menu-link w-full text-left">
                                                                <span class="kt-menu-icon">
                                                                    <x-heroicon-o-arrow-down-tray class="w-5 h-5" />
                                                                </span>
                                                                <span class="kt-menu-title">Installeer</span>
                                                            </button>
                                                        </form>
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

@push('styles')
<style>
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
    
    // Find all menu toggles
    const menuToggles = document.querySelectorAll('.kt-menu-toggle');
    
    // Add click handlers directly to toggle buttons
    menuToggles.forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            e.preventDefault();
            
            const menuItem = toggle.closest('.kt-menu-item');
            
            if (menuItem) {
                const dropdown = menuItem.querySelector('.kt-menu-dropdown');
                
                if (dropdown) {
                    // Toggle show class
                    const isShowing = menuItem.classList.contains('show');
                    
                    // Close all other dropdowns
                    document.querySelectorAll('.kt-menu-item.show').forEach(function(item) {
                        if (item !== menuItem) {
                            item.classList.remove('show');
                            const otherDropdown = item.querySelector('.kt-menu-dropdown');
                            if (otherDropdown) {
                                otherDropdown.style.display = 'none';
                            }
                        }
                    });
                    
                    if (!isShowing) {
                        menuItem.classList.add('show');
                    } else {
                        menuItem.classList.remove('show');
                    }
                    
                    setTimeout(function() {
                        const stillShowing = menuItem.classList.contains('show');
                        
                        if (stillShowing) {
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
                        } else {
                            dropdown.style.display = 'none';
                        }
                    }, 10);
                }
            }
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.kt-menu-item')) {
            document.querySelectorAll('.kt-menu-item.show').forEach(function(item) {
                item.classList.remove('show');
                const dropdown = item.querySelector('.kt-menu-dropdown');
                if (dropdown) {
                    dropdown.style.display = 'none';
                }
            });
        }
    });
});
</script>
@endpush

@endsection
