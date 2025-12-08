@extends('admin.layouts.app')

@section('title', 'E-mail Templates Beheer')

@section('content')


<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono mb-3">
                E-mail Templates Beheer
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
                    <div class="stat-number" style="background: linear-gradient(135deg, #4caf50 0%, #81c784 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $emailTemplates->where('is_active', true)->count() }}</div>
                    <div class="stat-label">Actief</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #ff9800 0%, #ffb74d 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $emailTemplates->where('is_active', false)->count() }}</div>
                    <div class="stat-label">Inactief</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #009688 0%, #4db6ac 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $emailTemplates->count() }}</div>
                    <div class="stat-label">Totaal</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #9c27b0 0%, #ba68c8 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $emailTemplates->unique('type')->count() }}</div>
                    <div class="stat-label">Types</div>
                </div>
            </div>

            <div class="kt-card">
                <!-- Header -->
                <div class="kt-card-header flex justify-between items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-envelope me-2"></i> E-mail Templates Beheer
                    </h5>
                    <div class="flex gap-2">
                        @can('create-email-templates')
                        <a href="{{ route('admin.email-templates.create') }}" class="kt-btn kt-btn-primary">
                            <i class="fas fa-plus me-2"></i> Nieuw Template
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
                    <form method="GET" action="{{ route('admin.email-templates.index') }}" id="filters-form">
                        <div class="grid gap-5 lg:gap-7.5">
                            @if(auth()->user()->hasRole('super-admin'))
                                <!-- Super-admin: 5 kolommen over gehele breedte -->
                                <div class="lg:col-span-2">
                                    <div class="filter-group">
                                        <label class="filter-label">Type</label>
                                        <select name="type" class="filter-select" onchange="this.form.submit()">
                                            <option value="">Alle types</option>
                                            <option value="welcome" {{ request('type') == 'welcome' ? 'selected' : '' }}>Welkom</option>
                                            <option value="notification" {{ request('type') == 'notification' ? 'selected' : '' }}>Notificatie</option>
                                            <option value="reminder" {{ request('type') == 'reminder' ? 'selected' : '' }}>Herinnering</option>
                                            <option value="confirmation" {{ request('type') == 'confirmation' ? 'selected' : '' }}>Bevestiging</option>
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
                                        <label class="filter-label">Status</label>
                                        <select name="status" class="filter-select" onchange="this.form.submit()">
                                            <option value="">Alle statussen</option>
                                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actief</option>
                                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactief</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="lg:col-span-2">
                                    <div class="filter-group">
                                        <label class="filter-label">Items per pagina</label>
                                        <select name="per_page" class="filter-select" onchange="this.form.submit()">
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
                                        <a href="{{ route('admin.email-templates.index') }}" class="btn btn-outline-secondary w-100" style="height: 36px; display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none;">
                                            <i class="fas fa-times"></i>
                                            Filter wissen
                                        </a>
                                    </div>
                                </div>
                            @else
                                <!-- Non-super-admin: 4 kolommen over gehele breedte -->
                                <div class="lg:col-span-3">
                                    <div class="filter-group">
                                        <label class="filter-label">Type</label>
                                        <select name="type" class="filter-select" onchange="this.form.submit()">
                                            <option value="">Alle types</option>
                                            <option value="welcome" {{ request('type') == 'welcome' ? 'selected' : '' }}>Welkom</option>
                                            <option value="notification" {{ request('type') == 'notification' ? 'selected' : '' }}>Notificatie</option>
                                            <option value="reminder" {{ request('type') == 'reminder' ? 'selected' : '' }}>Herinnering</option>
                                            <option value="confirmation" {{ request('type') == 'confirmation' ? 'selected' : '' }}>Bevestiging</option>
                                        </select>
                                    </div>
                                </div>
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
                                        <label class="filter-label">Items per pagina</label>
                                        <select name="per_page" class="filter-select" onchange="this.form.submit()">
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
                                        <a href="{{ route('admin.email-templates.index') }}" class="btn btn-outline-secondary w-100" style="height: 36px; display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none;">
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
                    @if($emailTemplates->count() > 0)
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
                                                Template & Details
                                            </a>
                                        </th>
                                        <th class="sorkt-table {{ request('sort') == 'type' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="type">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'type', 'order' => request('sort') == 'type' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
                                                Type
                                            </a>
                                        </th>
                                        <th class="sorkt-table {{ request('sort') == 'company_id' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="company_id">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'company_id', 'order' => request('sort') == 'company_id' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
                                                Bedrijf
                                            </a>
                                        </th>
                                        <th class="sorkt-table {{ request('sort') == 'is_active' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="is_active">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'is_active', 'order' => request('sort') == 'is_active' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
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
                                    @foreach($emailTemplates as $template)
                                        <tr>
                                            <td>
                                                <strong>{{ $template->id }}</strong>
                                            </td>
                                            <td>
                                                <div class="template-info">
                                                    <div class="template-name">{{ $template->name }}</div>
                                                    @if($template->description)
                                                        <div class="template-description">{{ Str::limit($template->description, 50) }}</div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <span class="template-type">{{ ucfirst($template->type) }}</span>
                                            </td>
                                            <td>
                                                @if($template->company)
                                                    <span class="template-company">{{ $template->company->name }}</span>
                                                @else
                                                    <span class="text-muted">Algemeen</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="status-badge {{ $template->is_active ? 'status-active' : 'status-inactive' }}">
                                                    {{ $template->is_active ? 'Actief' : 'Inactief' }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="date-info">
                                                    {{ $template->created_at->format('d-m-Y H:i') }}
                                                </div>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    @can('view-email-templates')
                                                    <a href="{{ route('admin.email-templates.show', $template) }}" 
                                                       class="action-btn action-btn-info" 
                                                       title="Bekijken">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @endcan
                                                    @can('edit-email-templates')
                                                    <a href="{{ route('admin.email-templates.edit', $template) }}" 
                                                       class="action-btn action-btn-warning" 
                                                       title="Bewerken">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    @endcan
                                                    @can('delete-email-templates')
                                                    <form action="{{ route('admin.email-templates.destroy', $template) }}" 
                                                          method="POST" 
                                                          style="display: inline;"
                                                          onsubmit="return confirm('Weet je zeker dat je dit template wilt verwijderen?')">
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

                        <!-- Pagination -->
                        <!-- Results Info -->
                        <div class="results-info-wrapper">
                            <div class="results-info">
                                <span class="results-text">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Toon {{ $emailTemplates->firstItem() ?? 0 }} tot {{ $emailTemplates->lastItem() ?? 0 }} van {{ $emailTemplates->total() }} resultaten
                                </span>
                            </div>
                        </div>

                        <!-- Pagination -->
                        @if($emailTemplates->hasPages())
                            <div class="pagination-wrapper">
                                <nav aria-label="Paginering">
                                    <ul class="pagination">
                                        {{-- Previous Page Link --}}
                                        @if ($emailTemplates->onFirstPage())
                                            <li class="page-item disabled">
                                                <span class="page-link">
                                                    <i class="fas fa-chevron-left"></i>
                                                </span>
                                            </li>
                                        @else
                                            <li class="page-item">
                                                <a class="page-link" href="{{ $emailTemplates->previousPageUrl() }}">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>
                                        @endif

                                        {{-- Pagination Elements --}}
                                        @foreach ($emailTemplates->getUrlRange(1, $emailTemplates->lastPage()) as $page => $url)
                                            @if ($page == $emailTemplates->currentPage())
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
                                        @if ($emailTemplates->hasMorePages())
                                            <li class="page-item">
                                                <a class="page-link" href="{{ $emailTemplates->nextPageUrl() }}">
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
                            <i class="fas fa-envelope"></i>
                            <h4>Geen e-mail templates gevonden</h4>
                            <p>Er zijn nog geen e-mail templates aangemaakt. Maak je eerste template aan om te beginnen.</p>
                            @can('create-email-templates')
                            <a href="{{ route('admin.email-templates.create') }}" class="kt-btn kt-btn-primary">
                                <i class="fas fa-plus me-2"></i> Nieuw Template
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
