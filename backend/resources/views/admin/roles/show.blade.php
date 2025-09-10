@extends('admin.layouts.app')

@section('title', 'Rol Details - ' . $role->name)

@section('content')
<style>
    :root {
        --primary-color: #2196f3;
        --primary-light: #64b5f6;
        --primary-dark: #1976d2;
        --primary-hover: #42a5f5;
        --success-color: #4CAF50;
        --warning-color: #FF9800;
        --danger-color: #F44336;
        --info-color: #00BCD4;
        --secondary-color: #757575;
        --light-bg: #f5f5f5;
        --border-color: #e0e0e0;
        --text-primary: #212121;
        --text-secondary: #757575;
        --shadow: 0 2px 4px rgba(0,0,0,0.1);
        --shadow-hover: 0 4px 8px rgba(0,0,0,0.15);
        --border-radius: 8px;
        --transition: all 0.3s ease;
    }

    .role-header {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-radius: var(--border-radius);
        padding: 10px;
        margin-bottom: 24px;
        border-left: 4px solid var(--primary-color);
    }

    .role-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 12px;
        line-height: 1.2;
    }

    .role-meta {
        display: flex;
        align-items: center;
        gap: 24px;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--text-secondary);
        font-size: 14px;
    }

    .meta-item i {
        color: var(--primary-color);
        width: 16px;
    }

    .role-status {
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }

    .role-status:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    .status-system {
        background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
        color: #e65100;
        border: 2px solid #ff9800;
    }

    .status-custom {
        background: linear-gradient(135deg, #f1f8e9 0%, #81c784 100%);
        color: #388e3c;
        border: 2px solid #4caf50;
    }

    .info-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        border-left: 4px solid #2196F3;
    }
    
    .info-section h6 {
        color: #2196F3;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 16px;
    }
    
    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #e9ecef;
    }
    
    .info-item:last-child {
        border-bottom: none;
    }
    
    .info-label {
        font-weight: 600;
        color: #424242;
    }
    
    .info-value {
        color: #757575;
    }
    
    .permission-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .permission-list li {
        padding: 8px 12px;
        margin-bottom: 4px;
        background: #f8f9fa;
        border-radius: 6px;
        border-left: 3px solid #2196F3;
        font-weight: 500;
        color: #424242;
    }
    
    .permissions-grid {
        /* No max-height or overflow to prevent scrolling */
    }
    
    .permissions-grid .row {
        margin: 0;
    }
    
    .permissions-grid .col-md-6 {
        padding: 0 8px;
    }
    
    .permission-group {
        margin-bottom: 16px;
        /* Removed background, border, padding, and box-shadow */
    }
    
    .permission-group:last-child {
        margin-bottom: 0;
    }
    
    .permission-group h6 {
        margin-bottom: 0px;
        /* Removed border-bottom */
    }
    
    .company-filter {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        border: 1px solid #e9ecef;
        margin-bottom: 20px;
    }
    
    .company-filter label {
        font-weight: 600;
        color: #424242;
        margin-bottom: 0px;
        display: block;
    }
    
    .company-filter select,
    .company-filter input {
        border: 1px solid #ddd;
        border-radius: 6px;
        padding: 8px 12px;
        background: white;
        width: 100%;
    }
    
    .company-filter .btn {
        border-radius: 6px;
        padding: 8px 16px;
    }
    
    .users-table-container {
        background: white;
        border-radius: 8px;
        border: 1px solid #e9ecef;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .users-table {
        margin: 0;
    }
    
    .table-header {
        background: linear-gradient(135deg, #2196F3, #1976D2);
        color: white;
    }
    
    .table-header th {
        border: none;
        padding: 16px 12px;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }
    
    .user-row {
        transition: all 0.3s ease;
    }
    
    .user-row:hover {
        background: #f8f9fa;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .user-row td {
        padding: 16px 12px;
        vertical-align: middle;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .user-info-cell {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .user-avatar {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #2196F3, #1976D2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1rem;
    }
    
    .user-details {
        flex: 1;
    }
    
    .user-name {
        font-weight: 600;
        color: #424242;
        font-size: 1rem;
        margin: 0;
    }
    
    .action-buttons {
        display: flex;
        gap: 8px;
    }
    
    .action-btn {
        width: 32px;
        height: 32px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: all 0.3s ease;
        text-decoration: none;
        border: none;
        font-size: 0.8rem;
    }
    
    .action-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        text-decoration: none;
    }
    
    .action-btn-info {
        background: linear-gradient(135deg, #17a2b8, #138496);
        color: white;
    }
    
    .action-btn-warning {
        background: linear-gradient(135deg, #ffc107, #e0a800);
        color: white;
    }
    
    .action-btn-danger {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    
    .stat-card {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        border: 2px solid #e0e0e0;
        transition: all 0.3s ease;
    }
    
    .stat-card:hover {
        border-color: #2196F3;
        box-shadow: 0 4px 12px rgba(33, 150, 243, 0.15);
    }
    
    .stat-number {
        font-size: 1.5rem;
        font-weight: 700;
        color: #2196F3;
        margin-bottom: 0px;
    }
    
    .stat-label {
        font-size: 0.9rem;
        color: #757575;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 500;
    }
</style>

@include('admin.material-design-template')

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="material-card">
                <div class="card-header">
                    <h5>
                        <i class="fas fa-user-shield me-2"></i> Rol Details: {{ $role->name }}
                    </h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.roles.edit', $role) }}" class="material-btn material-btn-warning">
                            <i class="fas fa-edit"></i>
                            Bewerken
                        </a>
                        <a href="{{ route('admin.roles.index') }}" class="material-btn material-btn-secondary">
                            <i class="fas fa-arrow-left"></i> Terug naar Overzicht
                        </a>
                    </div>
                </div>
                <div class="card-body">
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
                                    <span class="material-badge material-badge-warning">Systeem</span>
                                @else
                                    <span class="material-badge material-badge-success">Aangepast</span>
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
                                <div class="row">
                                    <div class="col-md-4">
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
                                    <div class="col-md-4">
                                        <label for="searchFilter" class="form-label">Zoek op naam/email:</label>
                                        <input type="text" id="searchFilter" class="form-control" placeholder="Zoek gebruikers...">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">&nbsp;</label>
                                        <button id="clearFilters" class="btn btn-outline-secondary w-100">Filters wissen</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Users Table -->
                            <div class="users-table-container">
                                <table class="table table-hover users-table">
                                    <thead class="table-header">
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
                                                    <span class="material-badge material-badge-info">{{ $user->company->name ?? 'Geen bedrijf' }}</span>
                                                </td>
                                                <td>
                                                    <span class="material-badge material-badge-success">Actief</span>
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
                                </table>
                            </div>
                        @else
                            <p class="text-muted mb-0">Geen gebruikers met deze rol.</p>
                        @endif
                    </div>

                    <!-- Two Column Layout for Basic Info and Permissions -->
                    <div class="row">
                        <!-- Basic Information -->
                        <div class="col-md-6">
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
                        <div class="col-md-6">
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
                                        
                                        <div class="row">
                                            <!-- Left Column -->
                                            <div class="col-md-6">
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
                                            <div class="col-md-6">
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
                    <div class="material-form-actions">
                        @if(!in_array($role->name, ['super-admin', 'company-admin', 'staff', 'candidate']))
                            <form action="{{ route('admin.roles.destroy', $role) }}" 
                                  method="POST" 
                                  class="d-inline"
                                  onsubmit="return confirm('Weet je zeker dat je deze rol wilt verwijderen?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="material-btn material-btn-danger">
                                    <i class="fas fa-trash"></i>
                                    Verwijderen
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('admin.roles.edit', $role) }}" class="material-btn material-btn-warning">
                            <i class="fas fa-edit"></i>
                            Bewerken
                        </a>
                        <a href="{{ route('admin.roles.index') }}" class="material-btn material-btn-secondary">
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
                row.style.display = 'table-row';
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
        const visibleRows = document.querySelectorAll('.user-row[style*="table-row"]').length;
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
