@extends('admin.layouts.app')

@section('title', 'Betalingsproviders')

@section('content')

<style>
    .kt-table-col {
        display: flex;
        align-items: center;
        width: 100%;
        justify-content: space-between;
    }
    .kt-table-col-label {
        flex: 1;
    }
    .kt-table-col-sort {
        margin-left: auto;
        flex-shrink: 0;
        margin-right: 0;
    }
    .kt-table thead th {
        position: relative;
    }
    .kt-table thead th a.kt-table-col:hover {
        background-color: var(--muted);
        border-radius: 4px;
    }
    .kt-table thead th a.kt-table-col {
        padding: 0.5rem;
        margin: -0.5rem;
        transition: background-color 0.2s;
    }
    /* Table row hover styling */
    .payment-provider-row {
        cursor: pointer !important;
    }
    .payment-provider-row:hover {
        background-color: var(--muted) !important;
    }
    @supports (color: color-mix(in lab, red, red)) {
        .payment-provider-row:hover {
            background-color: color-mix(in oklab, var(--muted) 50%, transparent) !important;
        }
    }
</style>

<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">
            Betalingsproviders
        </h1>
        @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('create-payment-providers'))
        <a href="{{ route('admin.payment-providers.create') }}" class="kt-btn kt-btn-primary">
            <i class="ki-filled ki-plus me-2"></i>
            Nieuwe Betalingsprovider
        </a>
        @endif
    </div>

    <!-- Success Alert -->
    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5" id="success-alert" role="alert">
            <i class="ki-filled ki-check-circle me-2"></i>
            {{ session('success') }}
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="kt-card mb-5">
        <div class="kt-card-content">
            <div class="flex lg:px-10 py-1.5 gap-2">
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold text-green-600 dark:text-green-400">
                        {{ $providers->where('is_active', true)->count() }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Actief
                    </span>
                </div>
                <span class="not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold text-red-600 dark:text-red-400">
                        {{ $providers->where('is_active', false)->count() }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Inactief
                    </span>
                </div>
                <span class="not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold text-orange-600 dark:text-orange-400">
                        {{ $providers->filter(function($provider) { return $provider->getConfigValue('test_mode', false); })->count() }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Test Modus
                    </span>
                </div>
                <span class="not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $providers->count() }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Totaal
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid min-w-full">
            <div class="kt-card-header py-5 flex-wrap gap-2">
                <h3 class="kt-card-title text-sm pb-3 w-full">
                    Toon {{ $providers->firstItem() ?? 0 }} tot {{ $providers->lastItem() ?? 0 }} van {{ $providers->total() }} betalingsproviders
                </h3>
                <div class="flex flex-wrap gap-2.5 lg:gap-5 justify-end w-full">
                    <!-- Search -->
                    <div class="flex">
                        <form method="GET" action="{{ route('admin.payment-providers.index') }}" class="flex gap-2" id="search-form">
                            @if(request('status'))
                                <input type="hidden" name="status" value="{{ request('status') }}">
                            @endif
                            @if(request('provider_type'))
                                <input type="hidden" name="provider_type" value="{{ request('provider_type') }}">
                            @endif
                            @if(request('mode'))
                                <input type="hidden" name="mode" value="{{ request('mode') }}">
                            @endif
                            @if(request('sort'))
                                <input type="hidden" name="sort" value="{{ request('sort') }}">
                            @endif
                            @if(request('order'))
                                <input type="hidden" name="order" value="{{ request('order') }}">
                            @endif
                            @if(request('per_page'))
                                <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                            @endif
                            <label class="kt-input w-64" style="position: relative !important;">
                                <i class="ki-filled ki-magnifier"></i>
                                <input placeholder="Zoek betalingsproviders..." 
                                       type="text" 
                                       name="search" 
                                       value="{{ request('search') }}"
                                       id="search-input"
                                       autocomplete="off"/>
                            </label>
                        </form>
                    </div>
                    <!-- Filters -->
                    <div class="flex flex-wrap gap-2.5 items-center">
                        <form method="GET" action="{{ route('admin.payment-providers.index') }}" id="filters-form" class="flex gap-2.5">
                            @if(request('search'))
                                <input type="hidden" name="search" value="{{ request('search') }}">
                            @endif
                            <select class="kt-select w-36" 
                                    name="status" 
                                    data-kt-select="true" 
                                    data-kt-select-placeholder="Status"
                                    id="status-filter"
                                    onchange="this.form.submit()">
                                <option value="">Alle statussen</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actief</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactief</option>
                            </select>
                            
                            <select class="kt-select w-36" 
                                    name="provider_type" 
                                    data-kt-select="true" 
                                    data-kt-select-placeholder="Provider Type"
                                    id="provider-type-filter"
                                    onchange="this.form.submit()">
                                <option value="">Alle types</option>
                                <option value="mollie" {{ request('provider_type') == 'mollie' ? 'selected' : '' }}>Mollie</option>
                                <option value="stripe" {{ request('provider_type') == 'stripe' ? 'selected' : '' }}>Stripe</option>
                                <option value="paypal" {{ request('provider_type') == 'paypal' ? 'selected' : '' }}>PayPal</option>
                                <option value="adyen" {{ request('provider_type') == 'adyen' ? 'selected' : '' }}>Adyen</option>
                            </select>
                            
                            <select class="kt-select w-36" 
                                    name="mode" 
                                    data-kt-select="true" 
                                    data-kt-select-placeholder="Modus"
                                    id="mode-filter"
                                    onchange="this.form.submit()">
                                <option value="">Alle modi</option>
                                <option value="test" {{ request('mode') == 'test' ? 'selected' : '' }}>Test</option>
                                <option value="live" {{ request('mode') == 'live' ? 'selected' : '' }}>Live</option>
                            </select>
                        </form>
                        @if(request('status') || request('provider_type') || request('mode') || request('search'))
                        <a href="{{ route('admin.payment-providers.index') }}" 
                           class="kt-btn kt-btn-outline kt-btn-icon" 
                           title="Filters resetten"
                           id="reset-filter-btn"
                           style="display: inline-flex !important; visibility: visible !important; opacity: 1 !important; min-width: 34px !important; height: 34px !important; align-items: center !important; justify-content: center !important; border: 1px solid var(--input) !important; background-color: var(--background) !important; color: var(--secondary-foreground) !important; position: relative !important; z-index: 1 !important;">
                            <i class="ki-filled ki-arrows-circle text-base" style="display: block !important; visibility: visible !important; opacity: 1 !important; font-size: 1rem !important;"></i>
                        </a>
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="kt-card-content">
                @if($providers->count() > 0)
                    <div class="kt-scrollable-x-auto">
                        <table class="kt-table table-auto kt-table-border" id="payment_providers_table">
                            <thead>
                                <tr>
                                    @php
                                        $currentSort = request('sort');
                                        $currentOrder = request('order', 'desc');
                                    @endphp
                                    <th class="min-w-[250px]">
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'order' => ($currentSort == 'name' && $currentOrder == 'asc') ? 'desc' : 'asc']) }}" 
                                           class="kt-table-col" style="text-decoration: none; color: inherit; cursor: pointer;">
                                            <span class="kt-table-col-label">Naam & Beschrijving</span>
                                            <span class="kt-table-col-sort">
                                                <span class="kt-table-col-sort-btn"></span>
                                            </span>
                                        </a>
                                    </th>
                                    <th class="min-w-[150px]">
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'provider_type', 'order' => ($currentSort == 'provider_type' && $currentOrder == 'asc') ? 'desc' : 'asc']) }}" 
                                           class="kt-table-col" style="text-decoration: none; color: inherit; cursor: pointer;">
                                            <span class="kt-table-col-label">Provider Type</span>
                                            <span class="kt-table-col-sort">
                                                <span class="kt-table-col-sort-btn"></span>
                                            </span>
                                        </a>
                                    </th>
                                    <th class="min-w-[120px]">
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'status', 'order' => ($currentSort == 'status' && $currentOrder == 'asc') ? 'desc' : 'asc']) }}" 
                                           class="kt-table-col" style="text-decoration: none; color: inherit; cursor: pointer;">
                                            <span class="kt-table-col-label">Status</span>
                                            <span class="kt-table-col-sort">
                                                <span class="kt-table-col-sort-btn"></span>
                                            </span>
                                        </a>
                                    </th>
                                    <th class="min-w-[120px]">
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'mode', 'order' => ($currentSort == 'mode' && $currentOrder == 'asc') ? 'desc' : 'asc']) }}" 
                                           class="kt-table-col" style="text-decoration: none; color: inherit; cursor: pointer;">
                                            <span class="kt-table-col-label">Modus</span>
                                            <span class="kt-table-col-sort">
                                                <span class="kt-table-col-sort-btn"></span>
                                            </span>
                                        </a>
                                    </th>
                                    <th class="w-[60px] text-center">Acties</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($providers as $provider)
                                    <tr class="payment-provider-row" data-provider-id="{{ $provider->id }}">
                                        <td>
                                            <div class="flex flex-col gap-1">
                                                <span class="text-sm font-medium text-mono">{{ $provider->name }}</span>
                                                @if($provider->getConfigValue('description'))
                                                    <span class="text-xs text-secondary-foreground">
                                                        {{ $provider->getConfigValue('description') }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <span class="kt-badge kt-badge-sm kt-badge-info">
                                                {{ ucfirst($provider->provider_type) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($provider->is_active)
                                                <span class="kt-badge kt-badge-sm kt-badge-success">Actief</span>
                                            @else
                                                <span class="kt-badge kt-badge-sm kt-badge-danger">Inactief</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($provider->getConfigValue('test_mode', false))
                                                <span class="kt-badge kt-badge-sm kt-badge-warning">Test</span>
                                            @else
                                                <span class="kt-badge kt-badge-sm kt-badge-primary">Live</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="kt-menu flex justify-center" data-kt-menu="true">
                                                <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-placement-rtl="bottom-start" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                                    <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost">
                                                        <i class="ki-filled ki-dots-vertical text-lg"></i>
                                                    </button>
                                                    <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]" data-kt-menu-dismiss="true">
                                                        <div class="kt-menu-item">
                                                            <a class="kt-menu-link" href="{{ route('admin.payment-providers.show', $provider) }}">
                                                                <span class="kt-menu-icon">
                                                                    <i class="ki-filled ki-eye"></i>
                                                                </span>
                                                                <span class="kt-menu-title">Bekijken</span>
                                                            </a>
                                                        </div>
                                                        <div class="kt-menu-item">
                                                            <a class="kt-menu-link" href="{{ route('admin.payment-providers.edit', $provider) }}">
                                                                <span class="kt-menu-icon">
                                                                    <i class="ki-filled ki-notepad-edit"></i>
                                                                </span>
                                                                <span class="kt-menu-title">Bewerken</span>
                                                            </a>
                                                        </div>
                                                        <div class="kt-menu-item">
                                                            <button type="button" 
                                                                    class="kt-menu-link w-full text-left test-connection-btn" 
                                                                    data-provider-id="{{ $provider->id }}">
                                                                <span class="kt-menu-icon">
                                                                    <x-heroicon-o-bolt class="w-5 h-5" />
                                                                </span>
                                                                <span class="kt-menu-title">Test Verbinding</span>
                                                            </button>
                                                        </div>
                                                        <div class="kt-menu-separator"></div>
                                                        <div class="kt-menu-item">
                                                            <form action="{{ route('admin.payment-providers.toggle-status', $provider) }}" 
                                                                  method="POST" 
                                                                  style="display: inline;">
                                                                @csrf
                                                                <button type="submit" class="kt-menu-link w-full text-left">
                                                                    <span class="kt-menu-icon">
                                                                        @if($provider->is_active)
                                                                            <x-heroicon-o-pause class="w-5 h-5" />
                                                                        @else
                                                                            <x-heroicon-o-play class="w-5 h-5" />
                                                                        @endif
                                                                    </span>
                                                                    <span class="kt-menu-title">{{ $provider->is_active ? 'Deactiveren' : 'Activeren' }}</span>
                                                                </button>
                                                            </form>
                                                        </div>
                                                        <div class="kt-menu-separator"></div>
                                                        <div class="kt-menu-item">
                                                            <form action="{{ route('admin.payment-providers.destroy', $provider) }}" 
                                                                  method="POST" 
                                                                  style="display: inline;"
                                                                  onsubmit="return confirm('Weet je zeker dat je deze betalingsprovider wilt verwijderen?')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="kt-menu-link w-full text-left text-danger">
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
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($providers->hasPages())
                        <div class="flex items-center justify-between mt-5 pt-5 border-t border-input">
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-secondary-foreground">
                                    Pagina {{ $providers->currentPage() }} van {{ $providers->lastPage() }}
                                </span>
                                <span class="text-sm text-secondary-foreground">|</span>
                                <span class="text-sm text-secondary-foreground">
                                    Toon
                                </span>
                                <form method="GET" action="{{ route('admin.payment-providers.index') }}" class="inline">
                                    @if(request('search'))
                                        <input type="hidden" name="search" value="{{ request('search') }}">
                                    @endif
                                    @if(request('status'))
                                        <input type="hidden" name="status" value="{{ request('status') }}">
                                    @endif
                                    @if(request('provider_type'))
                                        <input type="hidden" name="provider_type" value="{{ request('provider_type') }}">
                                    @endif
                                    @if(request('mode'))
                                        <input type="hidden" name="mode" value="{{ request('mode') }}">
                                    @endif
                                    @if(request('sort'))
                                        <input type="hidden" name="sort" value="{{ request('sort') }}">
                                    @endif
                                    @if(request('order'))
                                        <input type="hidden" name="order" value="{{ request('order') }}">
                                    @endif
                                    <select class="kt-select w-20" 
                                            name="per_page" 
                                            data-kt-select="true"
                                            onchange="this.form.submit()"
                                            style="min-width: 80px;">
                                        <option value="5" {{ request('per_page', 25) == 5 ? 'selected' : '' }}>5</option>
                                        <option value="15" {{ request('per_page', 25) == 15 ? 'selected' : '' }}>15</option>
                                        <option value="25" {{ request('per_page', 25) == 25 ? 'selected' : '' }}>25</option>
                                        <option value="50" {{ request('per_page', 25) == 50 ? 'selected' : '' }}>50</option>
                                        <option value="100" {{ request('per_page', 25) == 100 ? 'selected' : '' }}>100</option>
                                    </select>
                                </form>
                                <span class="text-sm text-secondary-foreground">
                                    per pagina
                                </span>
                            </div>
                            <div class="flex items-center gap-1">
                                @if ($providers->onFirstPage())
                                    <span class="kt-btn kt-btn-sm kt-btn-icon kt-btn-disabled">
                                        <i class="ki-filled ki-left"></i>
                                    </span>
                                @else
                                    <a href="{{ $providers->previousPageUrl() }}" class="kt-btn kt-btn-sm kt-btn-icon">
                                        <i class="ki-filled ki-left"></i>
                                    </a>
                                @endif

                                @foreach ($providers->getUrlRange(1, $providers->lastPage()) as $page => $url)
                                    @if ($page == $providers->currentPage())
                                        <span class="kt-btn kt-btn-sm kt-btn-primary">{{ $page }}</span>
                                    @else
                                        <a href="{{ $url }}" class="kt-btn kt-btn-sm">{{ $page }}</a>
                                    @endif
                                @endforeach

                                @if ($providers->hasMorePages())
                                    <a href="{{ $providers->nextPageUrl() }}" class="kt-btn kt-btn-sm kt-btn-icon">
                                        <i class="ki-filled ki-right"></i>
                                    </a>
                                @else
                                    <span class="kt-btn kt-btn-sm kt-btn-icon kt-btn-disabled">
                                        <i class="ki-filled ki-right"></i>
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endif
                @else
                    <div class="flex flex-col items-center justify-center py-12 text-center">
                        <i class="ki-filled ki-credit-card text-4xl text-muted-foreground mb-4"></i>
                        <h5 class="text-lg font-semibold text-mono mb-2">Geen betalingsproviders gevonden</h5>
                        <p class="text-sm text-secondary-foreground mb-6">Maak je eerste betalingsprovider aan om te beginnen.</p>
                        <a href="{{ route('admin.payment-providers.create') }}" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-plus me-2"></i>
                            Eerste Provider Aanmaken
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Test Connection Modal -->
<div class="kt-modal" id="testConnectionModal" tabindex="-1">
    <div class="kt-modal-dialog">
        <div class="kt-modal-content">
            <div class="kt-modal-header">
                <h3 class="kt-modal-title">
                    <i class="ki-filled ki-plug me-2"></i>Test Verbinding
                </h3>
                <button type="button" class="kt-btn kt-btn-sm kt-btn-icon" data-kt-dismiss="modal">
                    <i class="ki-filled ki-cross"></i>
                </button>
            </div>
            <div class="kt-modal-body">
                <div id="testResult"></div>
            </div>
            <div class="kt-modal-footer">
                <button type="button" class="kt-btn kt-btn-outline" data-kt-dismiss="modal">Sluiten</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('assets/js/search-input-clear.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search with debounce
    let searchTimeout;
    const searchInput = document.getElementById('search-input');
    const searchForm = document.getElementById('search-form');
    
    if (searchInput && searchForm) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                searchForm.submit();
            }, 500); // 500ms debounce
        });
        
        // Submit on Enter
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(searchTimeout);
                searchForm.submit();
            }
        });
    }
    
    // Test connection functionality (works from menu or button)
    document.querySelectorAll('.test-connection-btn').forEach(button => {
        button.addEventListener('click', function() {
            const providerId = this.dataset.providerId;
            const modalElement = document.getElementById('testConnectionModal');
            const resultDiv = document.getElementById('testResult');
            
            resultDiv.innerHTML = '<div class="text-center py-4"><i class="ki-filled ki-loader-2 animate-spin text-2xl text-primary"></i><p class="mt-2 text-sm text-secondary-foreground">Verbinding testen...</p></div>';
            
            // Close menu if opened from menu
            const menu = button.closest('.kt-menu');
            if (menu) {
                const menuItem = menu.querySelector('.kt-menu-item');
                if (menuItem) {
                    menuItem.classList.remove('show');
                }
            }
            
            // Show modal (using KT modal if available, otherwise basic display)
            if (modalElement) {
                modalElement.style.display = 'block';
                modalElement.classList.add('show');
            }
            
            fetch(`/admin/payment-providers/${providerId}/test-connection`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = `<div class="kt-alert kt-alert-success"><i class="ki-filled ki-check-circle me-2"></i>${data.message}</div>`;
                } else {
                    resultDiv.innerHTML = `<div class="kt-alert kt-alert-danger"><i class="ki-filled ki-information me-2"></i>${data.message}</div>`;
                }
            })
            .catch(error => {
                resultDiv.innerHTML = `<div class="kt-alert kt-alert-danger"><i class="ki-filled ki-information me-2"></i>Fout bij testen van verbinding: ${error.message}</div>`;
            });
        });
    });
    
    // Close modal when clicking outside or on close button
    const modal = document.getElementById('testConnectionModal');
    if (modal) {
        const closeButtons = modal.querySelectorAll('[data-kt-dismiss="modal"]');
        closeButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                modal.style.display = 'none';
                modal.classList.remove('show');
            });
        });
        
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
                modal.classList.remove('show');
            }
        });
    }
    
    // Auto-dismiss success alert after 5 seconds
    const successAlert = document.getElementById('success-alert');
    if (successAlert) {
        setTimeout(function() {
            successAlert.style.opacity = '0';
            successAlert.style.transition = 'opacity 0.3s';
            setTimeout(function() {
                successAlert.remove();
            }, 300);
        }, 5000);
    }
    
    // Make table rows clickable (except actions column) - using event delegation
    // This works even after filtering/searching because we listen on tbody
    const tbody = document.querySelector('#payment_providers_table tbody');
    if (tbody) {
        tbody.addEventListener('click', function(e) {
            // Find the closest row
            const row = e.target.closest('tr.payment-provider-row');
            if (!row) {
                return;
            }
            
            // Don't navigate if clicking on actions column or menu
            if (e.target.closest('td:last-child') || e.target.closest('.kt-menu') || e.target.closest('button') || e.target.closest('a')) {
                return;
            }
            
            // Get provider ID from the row
            const providerId = row.getAttribute('data-provider-id');
            if (providerId) {
                window.location.href = '/admin/payment-providers/' + providerId;
            }
        });
    }
});
</script>
@endpush
@endsection
