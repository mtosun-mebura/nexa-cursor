@extends('admin.layouts.app')

@section('title', 'Betalingsproviders')

@section('content')


<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono mb-3">
                Betalingsproviders
            </h1>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route(\'admin.\' . str_replace(\'admin.\', \'\', request()->route()->getName()) . \'.create\') }}" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-plus me-2"></i>
                Nieuw
            </a>
        </div>
    </div>
    <!-- Success Alert -->
    @if(session('success'))
        <div class="kt-alert kt-alert-success alert-dismissible fade show auto-dismiss" role="alert" id="success-alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="kt-btn kt-btn-sm kt-btn-icon" data-kt-dismiss="alert"></button>
        </div>
    @endif
    
    <div class="grid gap-5 lg:gap-7.5">
        <div class="w-full">
            <!-- Status Statistieken -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $providers->where('is_active', true)->count() }}</div>
                    <div class="stat-label">Actief</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #f44336 0%, #ef5350 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $providers->where('is_active', false)->count() }}</div>
                    <div class="stat-label">Inactief</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #ff9800 0%, #ffb74d 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $providers->where('config.test_mode', true)->count() }}</div>
                    <div class="stat-label">Test Modus</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #00897b 0%, #4db6ac 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $providers->count() }}</div>
                    <div class="stat-label">Totaal</div>
                </div>
            </div>

            <div class="kt-card">
                <div class="kt-card-header flex justify-between items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-credit-card me-2"></i> Betalingsproviders Overzicht
                    </h5>
                    <div class="flex gap-2">
                        <a href="{{ route('admin.payment-providers.create') }}" class="kt-btn kt-btn-primary">
                            <i class="fas fa-plus me-2"></i> Nieuwe Provider
                        </a>
                    </div>
                </div>
                <div class="kt-card-content">

                    <!-- Filters -->
                    <div class="filters-section">
                        <form method="GET" action="{{ route('admin.payment-providers.index') }}" id="filters-form">
                            <div class="grid gap-5 lg:gap-7.5">
                                <div class="lg:col-span-3">
                                    <div class="filter-group">
                                        <label class="filter-label">Status</label>
                                        <select name="status" class="filter-select" onchange="this.form.submit()">
                                            <option value="">Alle statussen</option>
                                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actief</option>
                                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactief</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="lg:col-span-3">
                                    <div class="filter-group">
                                        <label class="filter-label">Provider Type</label>
                                        <select name="provider_type" class="filter-select" onchange="this.form.submit()">
                                            <option value="">Alle types</option>
                                            <option value="mollie" {{ request('provider_type') == 'mollie' ? 'selected' : '' }}>Mollie</option>
                                            <option value="stripe" {{ request('provider_type') == 'stripe' ? 'selected' : '' }}>Stripe</option>
                                            <option value="paypal" {{ request('provider_type') == 'paypal' ? 'selected' : '' }}>PayPal</option>
                                            <option value="adyen" {{ request('provider_type') == 'adyen' ? 'selected' : '' }}>Adyen</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="lg:col-span-3">
                                    <div class="filter-group">
                                        <label class="filter-label">Modus</label>
                                        <select name="mode" class="filter-select" onchange="this.form.submit()">
                                            <option value="">Alle modi</option>
                                            <option value="test" {{ request('mode') == 'test' ? 'selected' : '' }}>Test</option>
                                            <option value="live" {{ request('mode') == 'live' ? 'selected' : '' }}>Live</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="lg:col-span-2">
                                    <div class="filter-group">
                                        <label class="filter-label">Items per pagina</label>
                                        <select name="per_page" class="filter-select" onchange="this.form.submit()">
                                            <option value="5" {{ request('per_page', 5) == 5 ? 'selected' : '' }}>5</option>
                                            <option value="15" {{ request('per_page', 5) == 15 ? 'selected' : '' }}>15</option>
                                            <option value="25" {{ request('per_page', 5) == 25 ? 'selected' : '' }}>25</option>
                                            <option value="50" {{ request('per_page', 5) == 50 ? 'selected' : '' }}>50</option>
                                            <option value="100" {{ request('per_page', 5) == 100 ? 'selected' : '' }}>100</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="lg:col-span-1">
                                    <div class="filter-group">
                                        <label class="filter-label">&nbsp;</label>
                                        <a href="{{ route('admin.payment-providers.index') }}" class="btn btn-outline-secondary w-100" style="height: 36px; display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none;">
                                            <i class="fas fa-times"></i>
                                            Filter wissen
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    @if($providers->count() > 0)
                        <div class="kt-table-responsive">
                            <kt-table class="material-kt-table">
                                <thead>
                                    <tr>
                                        <th class="sorkt-table {{ request('sort') == 'id' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="id">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'id', 'order' => request('sort') == 'id' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
                                                ID
                                            </a>
                                        </th>
                                        <th class="sorkt-table {{ request('sort') == 'name' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="name">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'order' => request('sort') == 'name' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
                                                Naam & Beschrijving
                                            </a>
                                        </th>
                                        <th class="sorkt-table {{ request('sort') == 'provider_type' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="provider_type">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'provider_type', 'order' => request('sort') == 'provider_type' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
                                                Provider Type
                                            </a>
                                        </th>
                                        <th class="sorkt-table {{ request('sort') == 'status' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="status">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'status', 'order' => request('sort') == 'status' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
                                                Status
                                            </a>
                                        </th>
                                        <th class="sorkt-table {{ request('sort') == 'mode' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="mode">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'mode', 'order' => request('sort') == 'mode' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
                                                Modus
                                            </a>
                                        </th>
                                        <th class="sorkt-table {{ request('sort') == 'created_at' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="created_at">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'order' => request('sort') == 'created_at' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
                                                Aangemaakt
                                            </a>
                                        </th>
                                        <th>Acties</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($providers as $provider)
                                        <tr>
                                            <td>{{ $provider->id }}</td>
                                            <td>
                                                <div class="provider-info">
                                                    <div class="provider-name">{{ $provider->name }}</div>
                                                    @if($provider->getConfigValue('description'))
                                                        <div class="provider-description">
                                                            <i class="fas fa-info-circle"></i>{{ $provider->getConfigValue('description') }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <span class="provider-type">{{ ucfirst($provider->provider_type) }}</span>
                                            </td>
                                            <td>
                                                @if($provider->is_active)
                                                    <span class="status-badge status-active">Actief</span>
                                                @else
                                                    <span class="status-badge status-inactive">Inactief</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($provider->getConfigValue('test_mode'))
                                                    <span class="provider-mode">Test</span>
                                                @else
                                                    <span class="provider-mode">Live</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="date-info">
                                                    <div>{{ $provider->created_at->format('d-m-Y') }}</div>
                                                    <small>{{ $provider->created_at->format('H:i') }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="{{ route('admin.payment-providers.show', $provider) }}" class="action-btn action-btn-info" title="Bekijken">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('admin.payment-providers.edit', $provider) }}" class="action-btn action-btn-warning" title="Bewerken">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="action-btn action-btn-info test-connection-btn" 
                                                            data-provider-id="{{ $provider->id }}"
                                                            title="Test Verbinding">
                                                        <i class="fas fa-plug"></i>
                                                    </button>
                                                    <form action="{{ route('admin.payment-providers.toggle-status', $provider) }}" 
                                                          method="POST" 
                                                          style="display: inline;">
                                                        @csrf
                                                        <button type="submit" 
                                                                class="action-btn {{ $provider->is_active ? 'action-btn-danger' : 'action-btn-success' }}" 
                                                                title="{{ $provider->is_active ? 'Deactiveren' : 'Activeren' }}">
                                                            <i class="fas {{ $provider->is_active ? 'fa-pause' : 'fa-play' }}"></i>
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('admin.payment-providers.destroy', $provider) }}" 
                                                          method="POST" 
                                                          style="display: inline;"
                                                          onsubmit="return confirm('Weet je zeker dat je deze betalingsprovider wilt verwijderen?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" 
                                                                class="action-btn action-btn-danger" 
                                                                title="Verwijderen">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </kt-table>
                        </div>
                        
                        <!-- Results Info -->
                        <div class="results-info-wrapper">
                            <div class="results-info">
                                <span class="results-text">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Toon {{ $providers->firstItem() ?? 0 }} tot {{ $providers->lastItem() ?? 0 }} van {{ $providers->total() }} resultaten
                                </span>
                            </div>
                        </div>

                        <!-- Pagination -->
                        @if($providers->hasPages())
                            <div class="pagination-wrapper">
                                <nav aria-label="Paginering">
                                    <ul class="pagination">
                                        {{-- Previous Page Link --}}
                                        @if ($providers->onFirstPage())
                                            <li class="page-item disabled">
                                                <span class="page-link">
                                                    <i class="fas fa-chevron-left"></i>
                                                </span>
                                            </li>
                                        @else
                                            <li class="page-item">
                                                <a class="page-link" href="{{ $providers->previousPageUrl() }}">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>
                                        @endif

                                        {{-- Pagination Elements --}}
                                        @foreach ($providers->getUrlRange(1, $providers->lastPage()) as $page => $url)
                                            @if ($page == $providers->currentPage())
                                                <li class="page-item active">
                                                    <span class="page-link">{{ $page }}</span>
                                                </li>
                                            @else
                                                <li class="page-item">
                                                    <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                                </li>
                                            @endif
                                        @endforeach

                                        {{-- Next Page Link --}}
                                        @if ($providers->hasMorePages())
                                            <li class="page-item">
                                                <a class="page-link" href="{{ $providers->nextPageUrl() }}">
                                                    <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                        @else
                                            <li class="page-item disabled">
                                                <span class="page-link">
                                                    <i class="fas fa-chevron-right"></i>
                                                </span>
                                            </li>
                                        @endif
                                    </ul>
                                </nav>
                            </div>
                        @endif
                    @else
                        <div class="empty-state">
                            <i class="fas fa-credit-card"></i>
                            <h5>Geen betalingsproviders gevonden</h5>
                            <p class="text-muted">Maak je eerste betalingsprovider aan om te beginnen.</p>
                            <a href="{{ route('admin.payment-providers.create') }}" class="kt-btn kt-btn-primary">
                                <i class="fas fa-plus"></i>
                                Eerste Provider Aanmaken
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Test Connection Modal -->
<div class="modal fade" id="testConnectionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plug me-2"></i>Test Verbinding</h5>
                <button type="button" class="kt-btn kt-btn-sm kt-btn-icon" data-kt-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="testResult"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-kt-dismiss="modal">Sluiten</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Test connection functionality
    document.querySelectorAll('.test-connection-btn').forEach(button => {
        button.addEventListener('click', function() {
            const providerId = this.dataset.providerId;
            const modal = new bootstrap.Modal(document.getElementById('testConnectionModal'));
            const resultDiv = document.getElementById('testResult');
            
            resultDiv.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Verbinding testen...</div>';
            modal.show();
            
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
                    resultDiv.innerHTML = `<div class="kt-alert kt-alert-success"><i class="fas fa-check-circle"></i> ${data.message}</div>`;
                } else {
                    resultDiv.innerHTML = `<div class="kt-alert kt-alert-danger"><i class="fas fa-exclamation-circle"></i> ${data.message}</div>`;
                }
            })
            .catch(error => {
                resultDiv.innerHTML = `<div class="kt-alert kt-alert-danger"><i class="fas fa-exclamation-circle"></i> Fout bij testen van verbinding: ${error.message}</div>`;
            });
        });
    });
    });
    
    // Auto-dismiss success alert after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const successAlert = document.getElementById('success-alert');
        if (successAlert) {
            setTimeout(function() {
                successAlert.classList.add('fade-out');
                setTimeout(function() {
                    successAlert.remove();
                }, 300); // Match the CSS animation duration
            }, 5000); // 5 seconds
        }
    });
</script>
@endsection
