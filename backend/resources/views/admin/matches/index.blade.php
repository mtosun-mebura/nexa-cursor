@extends('admin.layouts.app')

@section('title', 'Matches Beheer')

@section('content')


<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono mb-3">
                Matches Beheer
            </h1>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route(\'admin.\' . str_replace(\'admin.\', \'\', request()->route()->getName()) . \'.create\') }}" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-plus me-2"></i>
                Nieuw
            </a>
        </div>
    </div>
    <div class="grid gap-5 lg:gap-7.5">
        <div class="w-full">
            <!-- Status Statistieken -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #ff9800 0%, #ffb74d 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $matches->where('status', 'pending')->count() }}</div>
                    <div class="stat-label">In Afwachting</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #4caf50 0%, #81c784 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $matches->where('status', 'accepted')->count() }}</div>
                    <div class="stat-label">Geaccepteerd</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #2196f3 0%, #64b5f6 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $matches->where('status', 'interview')->count() }}</div>
                    <div class="stat-label">Interview</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #3f51b5 0%, #7986cb 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $matches->count() }}</div>
                    <div class="stat-label">Totaal</div>
                </div>
            </div>

            <div class="kt-card">
                <!-- Header -->
                <div class="kt-card-header flex justify-between items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-handshake me-2"></i> Matches Beheer
                    </h5>
                    <div class="flex gap-2">
                        @can('create-matches')
                            <a href="{{ route('admin.matches.create') }}" class="kt-btn kt-btn-primary">
                                <i class="fas fa-plus me-2"></i> Nieuwe Match
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
                    <form method="GET" action="{{ route('admin.matches.index') }}" id="filters-form">
                        <div class="grid gap-5 lg:gap-7.5">
                            @if(auth()->user()->hasRole('super-admin'))
                                <!-- Super-admin: 5 kolommen over gehele breedte -->
                                <div class="lg:col-span-2">
                                    <div class="filter-group">
                                        <label class="filter-label">Status</label>
                                        <select name="status" class="filter-select" onchange="this.form.submit()">
                                            <option value="">Alle statussen</option>
                                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>In Afwachting</option>
                                            <option value="accepted" {{ request('status') == 'accepted' ? 'selected' : '' }}>Geaccepteerd</option>
                                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Afgewezen</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="lg:col-span-2">
                                    <div class="filter-group">
                                        <label class="filter-label">Bedrijf</label>
                                        <select name="company" class="filter-select" onchange="this.form.submit()">
                                            <option value="">Alle bedrijven</option>
                                            @foreach(\App\Models\Company::all() as $company)
                                                <option value="{{ $company->id }}" {{ request('company') == $company->id ? 'selected' : '' }}>
                                                    {{ $company->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="lg:col-span-2">
                                    <div class="filter-group">
                                        <label class="filter-label">Score</label>
                                        <select name="score" class="filter-select" onchange="this.form.submit()">
                                            <option value="">Alle scores</option>
                                            <option value="high" {{ request('score') == 'high' ? 'selected' : '' }}>Hoog (80%+)</option>
                                            <option value="medium" {{ request('score') == 'medium' ? 'selected' : '' }}>Gemiddeld (60-79%)</option>
                                            <option value="low" {{ request('score') == 'low' ? 'selected' : '' }}>Laag (<60%)</option>
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
                                        <a href="{{ route('admin.matches.index') }}" class="btn btn-outline-secondary w-100" style="height: 36px; display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none;">
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
                                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>In Afwachting</option>
                                            <option value="accepted" {{ request('status') == 'accepted' ? 'selected' : '' }}>Geaccepteerd</option>
                                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Afgewezen</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="lg:col-span-3">
                                    <div class="filter-group">
                                        <label class="filter-label">Score</label>
                                        <select name="score" class="filter-select" onchange="this.form.submit()">
                                            <option value="">Alle scores</option>
                                            <option value="high" {{ request('score') == 'high' ? 'selected' : '' }}>Hoog (80%+)</option>
                                            <option value="medium" {{ request('score') == 'medium' ? 'selected' : '' }}>Gemiddeld (60-79%)</option>
                                            <option value="low" {{ request('score') == 'low' ? 'selected' : '' }}>Laag (<60%)</option>
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
                                        <a href="{{ route('admin.matches.index') }}" class="btn btn-outline-secondary w-100" style="height: 36px; display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none;">
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
                    @if($matches->count() > 0)
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
                                        <th class="sorkt-table {{ request('sort') == 'vacancy_id' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="vacancy_id">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'vacancy_id', 'order' => request('sort') == 'vacancy_id' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
                                                Vacature
                                            </a>
                                        </th>
                                        <th>Bedrijf</th>
                                        <th class="sorkt-table {{ request('sort') == 'match_score' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="match_score">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'match_score', 'order' => request('sort') == 'match_score' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
                                                Match Score
                                            </a>
                                        </th>
                                        <th class="sorkt-table {{ request('sort') == 'status' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="status">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'status', 'order' => request('sort') == 'status' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
                                                Status
                                            </a>
                                        </th>
                                        <th class="sorkt-table {{ request('sort') == 'created_at' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="created_at">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'order' => request('sort') == 'created_at' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
                                                Datum
                                            </a>
                                        </th>
                                        <th>Acties</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($matches as $match)
                                        <tr>
                                            <td>
                                                <strong>{{ $match->id }}</strong>
                                            </td>
                                            <td>
                                                <div class="match-info">
                                                    <div class="match-user">{{ $match->user->first_name }} {{ $match->user->last_name }}</div>
                                                    <div class="match-email">{{ $match->user->email }}</div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="match-info">
                                                    <div class="match-vacancy">{{ $match->vacancy->title }}</div>
                                                    @if($match->vacancy->location)
                                                        <div class="match-location">{{ $match->vacancy->location }}</div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @if($match->vacancy->company)
                                                    <span class="match-company">{{ $match->vacancy->company->name }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($match->match_score)
                                                    <span class="score-badge">{{ $match->match_score }}%</span>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                @switch($match->status)
                                                    @case('pending')
                                                        <span class="status-badge status-pending">In afwachting</span>
                                                        @break
                                                    @case('accepted')
                                                        <span class="status-badge status-accepted">Geaccepteerd</span>
                                                        @break
                                                    @case('rejected')
                                                        <span class="status-badge status-rejected">Afgewezen</span>
                                                        @break
                                                    @case('interview')
                                                        <span class="status-badge status-interview">Interview</span>
                                                        @break
                                                    @default
                                                        <span class="status-badge status-pending">{{ ucfirst($match->status) }}</span>
                                                @endswitch
                                            </td>
                                            <td>
                                                <div class="date-info">
                                                    {{ $match->created_at->format('d-m-Y H:i') }}
                                                </div>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="{{ route('admin.matches.show', $match) }}"
                                                       class="action-btn action-btn-info"
                                                       title="Bekijken">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('admin.matches.edit', $match) }}"
                                                       class="action-btn action-btn-warning"
                                                       title="Bewerken">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    @can('delete-matches')
                                                        <form action="{{ route('admin.matches.destroy', $match) }}"
                                                              method="POST"
                                                              style="display: inline;"
                                                              onsubmit="return confirm('Weet je zeker dat je deze match wilt verwijderen?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                    class="action-btn action-btn-danger"
                                                                    title="Verwijderen">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    @endcan
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
                                    Toon {{ $matches->firstItem() ?? 0 }} tot {{ $matches->lastItem() ?? 0 }} van {{ $matches->total() }} resultaten
                                </span>
                            </div>
                        </div>

                        <!-- Pagination -->
                        @if($matches->hasPages())
                            <div class="pagination-wrapper">
                                <nav aria-label="Paginering">
                                    <ul class="pagination">
                                        {{-- Previous Page Link --}}
                                        @if ($matches->onFirstPage())
                                            <li class="page-item disabled">
                                                <span class="page-link">
                                                    <i class="fas fa-chevron-left"></i>
                                                </span>
                                            </li>
                                        @else
                                            <li class="page-item">
                                                <a class="page-link" href="{{ $matches->previousPageUrl() }}">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>
                                        @endif

                                        {{-- Pagination Elements --}}
                                        @php
                                            $currentPage = $matches->currentPage();
                                            $lastPage = $matches->lastPage();
                                            $start = max(1, $currentPage - 2);
                                            $end = min($lastPage, $currentPage + 2);
                                        @endphp

                                        @if($start > 1)
                                            <li class="page-item">
                                                <a class="page-link" href="{{ $matches->url(1) }}">1</a>
                                            </li>
                                            @if($start > 2)
                                                <li class="page-item disabled">
                                                    <span class="page-link">...</span>
                                                </li>
                                            @endif
                                        @endif

                                        @for($page = $start; $page <= $end; $page++)
                                            @if ($page == $currentPage)
                                                <li class="page-item active">
                                                    <span class="page-link">{{ $page }}</span>
                                                </li>
                                            @else
                                                <li class="page-item">
                                                    <a class="page-link" href="{{ $matches->url($page) }}">{{ $page }}</a>
                                                </li>
                                            @endif
                                        @endfor

                                        @if($end < $lastPage)
                                            @if($end < $lastPage - 1)
                                                <li class="page-item disabled">
                                                    <span class="page-link">...</span>
                                                </li>
                                            @endif
                                            <li class="page-item">
                                                <a class="page-link" href="{{ $matches->url($lastPage) }}">{{ $lastPage }}</a>
                                            </li>
                                        @endif

                                        {{-- Next Page Link --}}
                                        @if ($matches->hasMorePages())
                                            <li class="page-item">
                                                <a class="page-link" href="{{ $matches->nextPageUrl() }}">
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
                            <i class="fas fa-handshake"></i>
                            <h4>Geen matches gevonden</h4>
                            <p>Er zijn nog geen matches aangemaakt. Maak je eerste match aan om te beginnen.</p>
                            @can('create-matches')
                                <a href="{{ route('admin.matches.create') }}" class="kt-btn kt-btn-primary">
                                    <i class="fas fa-plus me-2"></i> Nieuwe Match
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
