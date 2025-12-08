@extends('admin.layouts.app')

@section('title', 'Gebruikers Beheer')

@section('content')


<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono mb-3">
                Gebruikers Beheer
            </h1>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.users.create') }}" class="kt-btn kt-btn-primary">
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
                    <div class="stat-number" style="background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $users->where('is_active', true)->count() }}</div>
                    <div class="stat-label">Actief</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #f44336 0%, #ef5350 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $users->where('is_active', false)->count() }}</div>
                    <div class="stat-label">Inactief</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #ff9800 0%, #ffb74d 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $users->total() }}</div>
                    <div class="stat-label">Totaal</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #9c27b0 0%, #ba68c8 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $users->groupBy('role')->count() }}</div>
                    <div class="stat-label">Rollen</div>
                </div>
            </div>
            <div class="kt-card">
                <!-- Header -->
                <div class="kt-card-header flex justify-between items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i> Gebruikers Beheer
                    </h5>
                    <div class="flex gap-2">
                        <a href="{{ route('admin.users.create') }}" class="kt-btn kt-btn-primary">
                            <i class="fas fa-plus me-2"></i> Nieuwe Gebruiker
                        </a>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="filters-section">
                    <form method="GET" action="{{ route('admin.users.index') }}" id="filters-form">
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
                                    <label class="filter-label">Rol</label>
                                    <select name="role" class="filter-select" onchange="this.form.submit()">
                                        <option value="">Alle rollen</option>
                                        <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                                        <option value="user" {{ request('role') == 'user' ? 'selected' : '' }}>Gebruiker</option>
                                    </select>
                                </div>
                            </div>
                            <div class="lg:col-span-3">
                                <div class="filter-group">
                                    <label class="filter-label">Sorteren</label>
                                    <select name="sort" class="filter-select" onchange="this.form.submit()">
                                        <option value="first_name" {{ request('sort') == 'first_name' ? 'selected' : '' }}>Naam</option>
                                        <option value="email" {{ request('sort') == 'email' ? 'selected' : '' }}>E-mail</option>
                                        <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>Aangemaakt</option>
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
                                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary w-100" style="height: 36px; display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none;">
                                        <i class="fas fa-times"></i>
                                        Filter wissen
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="kt-card-content">

                    <div class="kt-table-responsive" style="width: 100%;">
                        <kt-table class="material-kt-table" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th class="sorkt-table {{ request('sort') == 'first_name' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="first_name">
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'first_name', 'order' => request('sort') == 'first_name' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
                                            Naam
                                        </a>
                                    </th>
                                    <th class="sorkt-table {{ request('sort') == 'email' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="email">
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'email', 'order' => request('sort') == 'email' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
                                            E-mail
                                        </a>
                                    </th>
                                    <th class="sorkt-table {{ request('sort') == 'phone' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="phone">
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'phone', 'order' => request('sort') == 'phone' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
                                            Telefoon
                                        </a>
                                    </th>
                                    <th class="sorkt-table {{ request('sort') == 'company_id' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="company_id">
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'company_id', 'order' => request('sort') == 'company_id' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
                                            Bedrijf
                                        </a>
                                    </th>
                                    <th>Rollen</th>
                                    <th class="sorkt-table {{ request('sort') == 'status' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="status">
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'status', 'order' => request('sort') == 'status' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
                                            Status
                                        </a>
                                    </th>
                                    <th class="sorkt-table {{ request('sort') == 'created_at' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="created_at">
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'order' => request('sort') == 'created_at' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
                                            Gemaakt op
                                        </a>
                                    </th>
                                    <th>Acties</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                    <tr>
                                        <td>
                                            <div class="user-info">
                                                <div class="user-name">{{ $user->first_name }} {{ $user->last_name }}</div>
                                                @if($user->middle_name)
                                                    <div class="user-middle-name">{{ $user->middle_name }}</div>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <a href="mailto:{{ $user->email }}" class="user-email">{{ $user->email }}</a>
                                        </td>
                                        <td>
                                            @if($user->phone)
                                                <a href="tel:{{ $user->phone }}" class="user-phone">{{ $user->phone }}</a>
                                            @else
                                                <span class="text-muted">Geen telefoon</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($user->company)
                                                <span class="user-company">{{ $user->company->name }}</span>
                                            @else
                                                <span class="kt-badge kt-badge-secondary">Geen bedrijf</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="user-roles">
                                                @foreach($user->roles as $role)
                                                    @if($role->name === 'super-admin')
                                                        @if(auth()->user()->hasRole('super-admin'))
                                                            <span class="kt-badge kt-badge-info">{{ ucfirst(str_replace('-', ' ', $role->name)) }}</span>
                                                        @else
                                                            <span class="kt-badge kt-badge-secondary">Verborgen</span>
                                                        @endif
                                                    @else
                                                        <span class="kt-badge kt-badge-info">{{ ucfirst(str_replace('-', ' ', $role->name)) }}</span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </td>
                                        <td>
                                            @if($user->email_verified_at)
                                                <span class="kt-badge kt-badge-success">Geverifieerd</span>
                                            @else
                                                <span class="kt-badge kt-badge-warning">Niet geverifieerd</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="date-info">{{ $user->created_at->format('d-m-Y H:i') }}</div>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="{{ route('admin.users.show', $user) }}" class="action-btn action-btn-info" title="Bekijken">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.users.edit', $user) }}" class="action-btn action-btn-warning" title="Bewerken">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline" onsubmit="return confirm('Weet je zeker dat je deze gebruiker wilt verwijderen?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="action-btn action-btn-danger" title="Verwijderen">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8">
                                            <div class="empty-state">
                                                <i class="fas fa-users"></i>
                                                <h5>Nog geen gebruikers</h5>
                                                <p>Er zijn nog geen gebruikers aangemaakt.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </kt-table>
                    </div>

                    <!-- Results Info -->
                    <div class="results-info-wrapper">
                        <div class="results-info">
                            <span class="results-text">
                                <i class="fas fa-info-circle me-2"></i>
                                Toon {{ $users->firstItem() ?? 0 }} tot {{ $users->lastItem() ?? 0 }} van {{ $users->total() }} resultaten
                            </span>
                        </div>
                    </div>

                    <!-- Pagination -->
                    @if($users->hasPages())
                        <div class="pagination-wrapper">
                            <nav aria-label="Paginering">
                                <ul class="pagination">
                                    {{-- Previous Page Link --}}
                                    @if ($users->onFirstPage())
                                        <li class="page-item disabled">
                                            <span class="page-link">
                                                <i class="fas fa-chevron-left"></i>
                                            </span>
                                        </li>
                                    @else
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $users->previousPageUrl() }}">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    @endif

                                    {{-- Pagination Elements --}}
                                    @foreach ($users->getUrlRange(1, $users->lastPage()) as $page => $url)
                                        @if ($page == $users->currentPage())
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
                                    @if ($users->hasMorePages())
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $users->nextPageUrl() }}">
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
                </div>
            </div>
        </div>
    </div>
</div>
<script>
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
