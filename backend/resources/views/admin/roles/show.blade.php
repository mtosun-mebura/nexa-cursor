@extends('admin.layouts.app')

@section('title', 'Rol Details - ' . $role->name)

@section('content')




<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono mb-3">
                {{ $title ?? "Pagina" }}
            </h1>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.' . str_replace(['admin.', '.create', '.edit', '.show'], ['', '.index', '.index', '.index'], request()->route()->getName())) }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
    </div>

    <div class="grid gap-5 lg:gap-7.5">
        <div class="w-full">
            <div class="kt-card">
                <div class="kt-card-header">
                    <h5>
                        <i class="fas fa-user-shield me-2"></i> Rol Details: {{ $role->name }}
                    </h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.roles.edit', $role) }}" class="kt-btn kt-btn-warning">
                            <i class="fas fa-edit"></i>
                            Bewerken
                        </a>
                        <a href="{{ route('admin.roles.index') }}" class="kt-btn kt-btn-outline">
                            <i class="fas fa-arrow-left"></i> Terug naar Overzicht
                        </a>
                    </div>
                </div>
                <div class="kt-card-content">
                    <!-- Role Header Section -->
                    <div class="role-header">
                        <h1 class="role-title">{{ ucfirst($role->name) }}</h1>
                        <div class="role-meta">
                            <div class="meta-item">
                                <i class="fas fa-user-shield"></i>
                                <span>{{ $role->name }}</span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-key"></i>
                                <span>{{ $role->permissions->count() }} rechten</span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-users"></i>
                                <span>{{ $role->users->count() }} gebruikers</span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-calendar"></i>
                                <span>Aangemaakt: {{ $role->created_at->format('d-m-Y') }}</span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-clock"></i>
                                <span>Bijgewerkt: {{ $role->updated_at->format('d-m-Y') }}</span>
                            </div>
                        </div>
                        <div class="role-status status-{{ in_array($role->name, ['super-admin', 'company-admin', 'staff', 'candidate']) ? 'system' : 'custom' }}">
                            <i class="fas fa-circle"></i>
                            {{ in_array($role->name, ['super-admin', 'company-admin', 'staff', 'candidate']) ? 'Systeem' : 'Aangepast' }}
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number">{{ $role->permissions->count() }}</div>
                            <div class="stat-label">Toegewezen Rechten</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">{{ $role->users->count() }}</div>
                            <div class="stat-label">Gebruikers</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">
                                @if(in_array($role->name, ['super-admin', 'company-admin', 'staff', 'candidate']))
                                    <span class="kt-badge kt-badge-warning">Systeem</span>
                                @else
                                    <span class="kt-badge kt-badge-success">Aangepast</span>
                                @endif
                            </div>
                            <div class="stat-label">Type</div>
                        </div>
                    </div>

                    <!-- Users -->
                    <div class="info-section">
                        <h6>
                            <i class="fas fa-users material-icon"></i>
                            Gebruikers met deze rol ({{ $role->users->count() }})
                        </h6>
                        
                        @if($role->users->count() > 0)
                            <!-- Company Filter -->
                            <div class="company-filter mb-3">
                                <div class="grid gap-5 lg:gap-7.5">
                                    <div class="lg:col-span-4">
                                        <label for="companyFilter" class="form-label">Filter op bedrijf:</label>
                                        <select id="companyFilter" class="form-select">
                                            <option value="">Alle bedrijven</option>
                                            @php
                                                $companies = $role->users->pluck('company.name')->filter()->unique()->sort();
                                            @endphp
                                            @foreach($companies as $companyName)
                                                <option value="{{ $companyName }}">{{ $companyName }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="lg:col-span-4">
                                        <label for="searchFilter" class="form-label">Zoek op naam/email:</label>
                                        <input type="text" id="searchFilter" class="form-control" placeholder="Zoek gebruikers...">
                                    </div>
                                    <div class="lg:col-span-4">
                                        <label class="form-label">&nbsp;</label>
                                        <button id="clearFilters" class="btn btn-outline-secondary w-100">Filters wissen</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Users Table -->
                            <div class="users-kt-table-container">
                                <kt-table class="kt-kt-table users-kt-table">
                                    <thead class="kt-table-header">
                                        <tr>
                                            <th>Gebruiker</th>
                                            <th>Email</th>
                                            <th>Bedrijf</th>
                                            <th>Status</th>
                                            <th>Acties</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($role->users as $user)
                                            <tr class="user-row" 
                                                data-company="{{ $user->company->name ?? 'Geen bedrijf' }}"
                                                data-name="{{ strtolower($user->first_name . ' ' . $user->last_name) }}"
                                                data-email="{{ strtolower($user->email) }}">
                                                <td>
                                                    <div class="user-info-cell">
                                                        <div class="user-avatar">
                                                            <i class="fas fa-user"></i>
                                                        </div>
                                                        <div class="user-details">
                                                            <div class="user-name">{{ $user->first_name }} {{ $user->last_name }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>{{ $user->email }}</td>
                                                <td>
                                                    <span class="kt-badge kt-badge-info">{{ $user->company->name ?? 'Geen bedrijf' }}</span>
                                                </td>
                                                <td>
                                                    <span class="kt-badge kt-badge-success">Actief</span>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <a href="{{ route('admin.users.show', $user) }}" class="action-btn action-btn-info" title="Bekijken">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="{{ route('admin.users.edit', $user) }}" class="action-btn action-btn-warning" title="Bewerken">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </kt-table>
                            </div>
                        @else
                            <p class="text-muted mb-0">Geen gebruikers met deze rol.</p>
                        @endif
                    </div>

                    <!-- Two Column Layout for Basic Info and Permissions -->
                    <div class="grid gap-5 lg:gap-7.5">
                        <!-- Basic Information -->
                        <div class="lg:col-span-6">
                            <div class="info-section">
                                <h6>
                                    <i class="fas fa-info-circle material-icon"></i>
                                    Basis Informatie
                                </h6>
                                <div class="info-item">
                                    <span class="info-label">Rol Naam:</span>
                                    <span class="info-value">{{ $role->name }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Beschrijving:</span>
                                    <span class="info-value">{{ $role->description ?? 'Geen beschrijving' }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Aangemaakt:</span>
                                    <span class="info-value">{{ $role->created_at->format('d-m-Y H:i') }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Laatst bijgewerkt:</span>
                                    <span class="info-value">{{ $role->updated_at->format('d-m-Y H:i') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Permissions -->
                        <div class="lg:col-span-6">
                            <div class="info-section">
                                <h6>
                                    <i class="fas fa-key material-icon"></i>
                                    Toegewezen Rechten ({{ $role->permissions->count() }})
                                </h6>
                                @if($role->permissions->count() > 0)
                                    <div class="permissions-grid">
                                        @php
                                            $permissionGroups = $role->permissions->groupBy('group');
                                            $totalGroups = $permissionGroups->count();
                                            $groupsPerColumn = ceil($totalGroups / 2);
                                            $leftColumn = $permissionGroups->take($groupsPerColumn);
                                            $rightColumn = $permissionGroups->slice($groupsPerColumn);
                                        @endphp
                                        
                                        <div class="grid gap-5 lg:gap-7.5">
                                            <!-- Left Column -->
                                            <div class="lg:col-span-6">
                                                @foreach($leftColumn as $group => $permissions)
                                                    <div class="permission-group">
                                                        <h6 class="text-capitalize mb-2" style="color: #2196F3; font-weight: 600;">{{ $group }}</h6>
                                                        <ul class="permission-list">
                                                            @foreach($permissions as $permission)
                                                                <li>{{ ucfirst(str_replace('-', ' ', $permission->name)) }}</li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                @endforeach
                                            </div>
                                            
                                            <!-- Right Column -->
                                            <div class="lg:col-span-6">
                                                @foreach($rightColumn as $group => $permissions)
                                                    <div class="permission-group">
                                                        <h6 class="text-capitalize mb-2" style="color: #2196F3; font-weight: 600;">{{ $group }}</h6>
                                                        <ul class="permission-list">
                                                            @foreach($permissions as $permission)
                                                                <li>{{ ucfirst(str_replace('-', ' ', $permission->name)) }}</li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <p class="text-muted mb-0">Geen rechten toegewezen aan deze rol.</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end gap-2.5">
                        @if(!in_array($role->name, ['super-admin', 'company-admin', 'staff', 'candidate']))
                            <form action="{{ route('admin.roles.destroy', $role) }}" 
                                  method="POST" 
                                  class="d-inline"
                                  onsubmit="return confirm('Weet je zeker dat je deze rol wilt verwijderen?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="kt-btn kt-btn-danger">
                                    <i class="fas fa-trash"></i>
                                    Verwijderen
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('admin.roles.edit', $role) }}" class="kt-btn kt-btn-warning">
                            <i class="fas fa-edit"></i>
                            Bewerken
                        </a>
                        <a href="{{ route('admin.roles.index') }}" class="kt-btn kt-btn-outline">
                            <i class="fas fa-arrow-left"></i> Terug naar Overzicht
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const companyFilter = document.getElementById('companyFilter');
    const searchFilter = document.getElementById('searchFilter');
    const clearFilters = document.getElementById('clearFilters');
    const userRows = document.querySelectorAll('.user-row');
    
    function filterUsers() {
        const selectedCompany = companyFilter.value.toLowerCase();
        const searchTerm = searchFilter.value.toLowerCase();
        
        userRows.forEach(function(row) {
            const companyName = row.getAttribute('data-company').toLowerCase();
            const userName = row.getAttribute('data-name');
            const userEmail = row.getAttribute('data-email');
            
            const matchesCompany = selectedCompany === '' || companyName === selectedCompany;
            const matchesSearch = searchTerm === '' || 
                                userName.includes(searchTerm) || 
                                userEmail.includes(searchTerm);
            
            if (matchesCompany && matchesSearch) {
                row.style.display = 'kt-table-row';
                row.style.opacity = '1';
            } else {
                row.style.display = 'none';
                row.style.opacity = '0';
            }
        });
        
        // Update visible count
        updateVisibleCount();
    }
    
    function updateVisibleCount() {
        const visibleRows = document.querySelectorAll('.user-row[style*="kt-table-row"]').length;
        const totalRows = userRows.length;
        
        // You can add a counter element here if needed
        console.log(`Showing ${visibleRows} of ${totalRows} users`);
    }
    
    if (companyFilter) {
        companyFilter.addEventListener('change', filterUsers);
    }
    
    if (searchFilter) {
        searchFilter.addEventListener('input', filterUsers);
    }
    
    if (clearFilters) {
        clearFilters.addEventListener('click', function() {
            companyFilter.value = '';
            searchFilter.value = '';
            filterUsers();
        });
    }
    
    // Initialize count
    updateVisibleCount();
});
</script>
@endsection
