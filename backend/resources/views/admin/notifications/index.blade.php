@extends('admin.layouts.app')

@section('title', 'Notificaties Beheer')

@section('content')


<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono mb-3">
                Notificaties Beheer
            </h1>
        </div>
        <div class="flex items-center gap-2.5">
            @can('create-notifications')
                <a href="{{ route('admin.notifications.create') }}" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-plus me-2"></i>
                    Nieuw
                </a>
            @endcan
        </div>
    </div>
    <div class="grid gap-5 lg:gap-7.5">
        <div class="w-full">
            <!-- Status Statistieken -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #4caf50 0%, #81c784 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $notifications->where('read_at', '!=', null)->count() }}</div>
                    <div class="stat-label">Gelezen</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #ff9800 0%, #ffb74d 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $notifications->where('read_at', null)->count() }}</div>
                    <div class="stat-label">Ongelezen</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $notifications->count() }}</div>
                    <div class="stat-label">Totaal</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #9c27b0 0%, #ba68c8 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $notifications->unique('user_id')->count() }}</div>
                    <div class="stat-label">Gebruikers</div>
                </div>
            </div>

            <div class="kt-card">
                <!-- Header -->
                <div class="kt-card-header flex justify-between items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-bell me-2"></i> Notificaties Beheer
                    </h5>
                    <div class="flex gap-2">
                        @can('create-notifications')
                            <a href="{{ route('admin.notifications.create') }}" class="kt-btn kt-btn-primary">
                                <i class="fas fa-plus me-2"></i> Nieuwe Notificatie
                            </a>
                        @endcan
                    </div>
                </div>

                <!-- Success Message -->
                @if(session('success'))
                    <div class="kt-alert kt-alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        {{ session('success') }}
                    </div>
                @endif

                <!-- Filters -->
                <div class="filters-section">
                    <form method="GET" action="{{ route('admin.notifications.index') }}" id="filters-form">
                        <div class="grid gap-5 lg:gap-7.5">
                            @if(auth()->user()->hasRole('super-admin'))
                                <!-- Super-admin: 5 kolommen over gehele breedte -->
                                <div class="lg:col-span-2">
                                    <div class="filter-group">
                                        <label class="filter-label">Status</label>
                                        <select name="status" class="filter-select" onchange="this.form.submit()">
                                            <option value="">Alle statussen</option>
                                            <option value="unread" {{ request('status') == 'unread' ? 'selected' : '' }}>Ongelezen</option>
                                            <option value="read" {{ request('status') == 'read' ? 'selected' : '' }}>Gelezen</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="lg:col-span-2">
                                    <div class="filter-group">
                                        <label class="filter-label">Type</label>
                                        <select name="type" class="filter-select" onchange="this.form.submit()">
                                            <option value="">Alle types</option>
                                            <option value="info" {{ request('type') == 'info' ? 'selected' : '' }}>Info</option>
                                            <option value="warning" {{ request('type') == 'warning' ? 'selected' : '' }}>Waarschuwing</option>
                                            <option value="error" {{ request('type') == 'error' ? 'selected' : '' }}>Fout</option>
                                            <option value="success" {{ request('type') == 'success' ? 'selected' : '' }}>Succes</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="lg:col-span-2">
                                    <div class="filter-group">
                                        <label class="filter-label">Prioriteit</label>
                                        <select name="priority" class="filter-select" onchange="this.form.submit()">
                                            <option value="">Alle prioriteiten</option>
                                            <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Laag</option>
                                            <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Gemiddeld</option>
                                            <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>Hoog</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="lg:col-span-2">
                                    <div class="filter-group">
                                        <label class="filter-label">Items per pagina</label>
                                        <select name="per_page" class="filter-select" onchange="this.form.submit()">
                                            <option value="5" {{ request('per_page', 15) == 5 ? 'selected' : '' }}>5</option>
                                            <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15</option>
                                            <option value="25" {{ request('per_page', 15) == 25 ? 'selected' : '' }}>25</option>
                                            <option value="50" {{ request('per_page', 15) == 50 ? 'selected' : '' }}>50</option>
                                            <option value="100" {{ request('per_page', 15) == 100 ? 'selected' : '' }}>100</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="lg:col-span-2">
                                    <div class="filter-group">
                                        <label class="filter-label">&nbsp;</label>
                                        <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-secondary w-100" style="height: 36px; display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none;">
                                            <i class="fas fa-times"></i>
                                            Filter wissen
                                        </a>
                                    </div>
                                </div>
                            @else
                                <!-- Non-super-admin: 4 kolommen over gehele breedte -->
                                <div class="lg:col-span-3">
                                    <div class="filter-group">
                                        <label class="filter-label">Status</label>
                                        <select name="status" class="filter-select" onchange="this.form.submit()">
                                            <option value="">Alle statussen</option>
                                            <option value="unread" {{ request('status') == 'unread' ? 'selected' : '' }}>Ongelezen</option>
                                            <option value="read" {{ request('status') == 'read' ? 'selected' : '' }}>Gelezen</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="lg:col-span-3">
                                    <div class="filter-group">
                                        <label class="filter-label">Type</label>
                                        <select name="type" class="filter-select" onchange="this.form.submit()">
                                            <option value="">Alle types</option>
                                            <option value="info" {{ request('type') == 'info' ? 'selected' : '' }}>Info</option>
                                            <option value="warning" {{ request('type') == 'warning' ? 'selected' : '' }}>Waarschuwing</option>
                                            <option value="error" {{ request('type') == 'error' ? 'selected' : '' }}>Fout</option>
                                            <option value="success" {{ request('type') == 'success' ? 'selected' : '' }}>Succes</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="lg:col-span-3">
                                    <div class="filter-group">
                                        <label class="filter-label">Items per pagina</label>
                                        <select name="per_page" class="filter-select" onchange="this.form.submit()">
                                            <option value="5" {{ request('per_page', 15) == 5 ? 'selected' : '' }}>5</option>
                                            <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15</option>
                                            <option value="25" {{ request('per_page', 15) == 25 ? 'selected' : '' }}>25</option>
                                            <option value="50" {{ request('per_page', 15) == 50 ? 'selected' : '' }}>50</option>
                                            <option value="100" {{ request('per_page', 15) == 100 ? 'selected' : '' }}>100</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="lg:col-span-3">
                                    <div class="filter-group">
                                        <label class="filter-label">&nbsp;</label>
                                        <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-secondary w-100" style="height: 36px; display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none;">
                                            <i class="fas fa-times"></i>
                                            Filter wissen
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </form>
                </div>

                <!-- Table -->
                <div class="kt-card-content">
                    @if($notifications->count() > 0)
                        <div class="kt-table-responsive">
                            <kt-table class="material-kt-table">
                                <thead>
                                    <tr>
                                        <th class="sorkt-table {{ request('sort') == 'id' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="id">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'id', 'order' => request('sort') == 'id' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
                                                ID
                                            </a>
                                        </th>
                                        <th class="sorkt-table {{ request('sort') == 'user_id' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="user_id">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'user_id', 'order' => request('sort') == 'user_id' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
                                                Gebruiker
                                            </a>
                                        </th>
                                        <th class="sorkt-table {{ request('sort') == 'type' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="type">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'type', 'order' => request('sort') == 'type' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
                                                Inhoud
                                            </a>
                                        </th>
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
                                    @foreach($notifications as $notification)
                                        <tr>
                                            <td>
                                                <strong>{{ $notification->id }}</strong>
                                            </td>
                                            <td>
                                                <div class="user-info">
                                                    @if($notification->user)
                                                        <div class="user-name">
                                                            {{ $notification->user->first_name }} {{ $notification->user->last_name }}
                                                        </div>
                                                        <div class="user-email">
                                                            {{ $notification->user->email }}
                                                        </div>
                                                    @else
                                                        <span class="text-muted">Gebruiker niet gevonden</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <div class="notification-content" title="{{ $notification->message }}">
                                                    {{ $notification->message }}
                                                </div>
                                            </td>
                                            <td>
                                                <span class="status-badge {{ $notification->read_at ? 'status-read' : 'status-unread' }}">
                                                    {{ $notification->read_at ? 'Gelezen' : 'Ongelezen' }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="notification-date">
                                                    {{ $notification->created_at->format('d-m-Y H:i') }}
                                                </div>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="{{ route('admin.notifications.show', $notification) }}"
                                                       class="action-btn action-btn-info"
                                                       title="Bekijken">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @can('edit-notifications')
                                                        <a href="{{ route('admin.notifications.edit', $notification) }}"
                                                           class="action-btn action-btn-warning"
                                                           title="Bewerken">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    @endcan
                                                    <form action="{{ route('admin.notifications.destroy', $notification) }}"
                                                          method="POST"
                                                          style="display: inline;"
                                                          onsubmit="return confirm('Weet je zeker dat je deze notificatie wilt verwijderen?')">
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
                                <div class="results-text">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Toon {{ $notifications->firstItem() ?? 0 }} tot {{ $notifications->lastItem() ?? 0 }} van {{ $notifications->total() }} resultaten
                                </div>
                            </div>
                        </div>

                        <!-- Pagination -->
                        @if($notifications->hasPages())
                            <div class="pagination-wrapper">
                                <nav aria-label="Paginering">
                                    <ul class="pagination">
                                        {{-- Previous Page Link --}}
                                        @if ($notifications->onFirstPage())
                                            <li class="page-item disabled">
                                                <span class="page-link">
                                                    <i class="fas fa-chevron-left"></i>
                                                </span>
                                            </li>
                                        @else
                                            <li class="page-item">
                                                <a class="page-link" href="{{ $notifications->previousPageUrl() }}">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>
                                        @endif

                                        {{-- Pagination Elements --}}
                                        @foreach ($notifications->getUrlRange(1, $notifications->lastPage()) as $page => $url)
                                            @if ($page == $notifications->currentPage())
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
                                        @if ($notifications->hasMorePages())
                                            <li class="page-item">
                                                <a class="page-link" href="{{ $notifications->nextPageUrl() }}">
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
                            <i class="fas fa-bell"></i>
                            <h4>Geen notificaties gevonden</h4>
                            <p>Er zijn nog geen notificaties aangemaakt. Maak je eerste notificatie aan om te beginnen.</p>
                            @can('create-notifications')
                                <a href="{{ route('admin.notifications.create') }}" class="kt-btn kt-btn-primary">
                                    <i class="fas fa-plus me-2"></i> Nieuwe Notificatie
                                </a>
                            @endcan
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
