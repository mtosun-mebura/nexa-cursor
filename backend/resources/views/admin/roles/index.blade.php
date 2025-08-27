@extends('admin.layouts.app')

@section('title', 'Rollen Beheer')

@section('content')
<style>
    .material-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        border: none;
        margin-bottom: 24px;
        transition: box-shadow 0.3s ease;
    }
    
    .material-card:hover {
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    }
    
    .material-card .card-header {
        background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
        color: white;
        border-radius: 12px 12px 0 0;
        padding: 20px 24px;
        border: none;
    }
    
    .material-card .card-body {
        padding: 24px;
    }
    
    .material-btn {
        border-radius: 8px;
        text-transform: uppercase;
        font-weight: 500;
        letter-spacing: 0.5px;
        padding: 10px 20px;
        border: none;
        transition: all 0.3s ease;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }
    
    .material-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
    }
    
    .material-btn-primary {
        background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
        color: white;
    }
    
    .material-btn-warning {
        background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
        color: white;
    }
    
    .material-btn-danger {
        background: linear-gradient(135deg, #F44336 0%, #D32F2F 100%);
        color: white;
    }
    
    .material-table {
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .material-table thead th {
        background: #f8f9fa;
        border: none;
        font-weight: 600;
        color: #495057;
        padding: 16px 12px;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }
    
    .material-table tbody td {
        padding: 16px 12px;
        border-bottom: 1px solid #e9ecef;
        vertical-align: middle;
    }
    
    .material-table tbody tr:hover {
        background: #f8f9fa;
    }
    
    .material-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .material-badge-info {
        background: linear-gradient(135deg, #00BCD4 0%, #0097A7 100%);
        color: white;
    }
    
    .material-badge-secondary {
        background: linear-gradient(135deg, #757575 0%, #616161 100%);
        color: white;
    }
    
    .material-badge-warning {
        background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
        color: white;
    }
    
    .material-badge-success {
        background: linear-gradient(135deg, #4CAF50 0%, #388E3C 100%);
        color: white;
    }
    
    .material-alert {
        border-radius: 8px;
        border: none;
        padding: 16px 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .material-alert-success {
        background: linear-gradient(135deg, #E8F5E8 0%, #C8E6C9 100%);
        color: #2E7D32;
        border-left: 4px solid #4CAF50;
    }
    
    .material-alert-danger {
        background: linear-gradient(135deg, #FFEBEE 0%, #FFCDD2 100%);
        color: #C62828;
        border-left: 4px solid #F44336;
    }
    
    .material-icon {
        font-size: 1.2rem;
        margin-right: 8px;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #757575;
    }
    
    .empty-state i {
        font-size: 4rem;
        margin-bottom: 20px;
        opacity: 0.5;
    }
    
    .empty-state p {
        font-size: 1.1rem;
        margin: 0;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="material-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-user-shield material-icon"></i>
                        Rollen Beheer
                    </h5>
                    <a href="{{ route('admin.roles.create') }}" class="material-btn material-btn-primary">
                        <i class="fas fa-plus me-1"></i>
                        Nieuwe Rol
                    </a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="material-alert material-alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle material-icon"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="material-alert material-alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle material-icon"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table material-table">
                            <thead>
                                <tr>
                                    <th>Naam</th>
                                    <th>Beschrijving</th>
                                    <th>Aantal Rechten</th>
                                    <th>Aantal Gebruikers</th>
                                    <th>Type</th>
                                    <th>Acties</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($roles as $role)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-user-shield me-2 text-primary"></i>
                                                <strong>{{ $role->name }}</strong>
                                            </div>
                                        </td>
                                        <td>{{ $role->description ?? 'Geen beschrijving' }}</td>
                                        <td>
                                            <span class="material-badge material-badge-info">{{ $role->permissions->count() }}</span>
                                        </td>
                                        <td>
                                            <span class="material-badge material-badge-secondary">{{ $role->users->count() }}</span>
                                        </td>
                                        <td>
                                            @if(in_array($role->name, ['super-admin', 'company-admin', 'staff', 'candidate']))
                                                <span class="material-badge material-badge-warning">Systeem</span>
                                            @else
                                                <span class="material-badge material-badge-success">Aangepast</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="{{ route('admin.roles.show', $role) }}" 
                                                   class="btn btn-sm btn-info rounded-circle" 
                                                   style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;"
                                                   title="Bekijken">
                                                    <i class="fas fa-eye text-white"></i>
                                                </a>
                                                <a href="{{ route('admin.roles.edit', $role) }}" 
                                                   class="btn btn-sm btn-warning rounded-circle" 
                                                   style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;"
                                                   title="Bewerken">
                                                    <i class="fas fa-edit text-white"></i>
                                                </a>
                                                @if(!in_array($role->name, ['super-admin', 'company-admin', 'staff', 'candidate']))
                                                    <form action="{{ route('admin.roles.destroy', $role) }}" 
                                                          method="POST" 
                                                          class="d-inline"
                                                          onsubmit="return confirm('Weet je zeker dat je deze rol wilt verwijderen?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" 
                                                                class="btn btn-sm btn-danger rounded-circle" 
                                                                style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;"
                                                                title="Verwijderen">
                                                            <i class="fas fa-trash text-white"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6">
                                            <div class="empty-state">
                                                <i class="fas fa-inbox"></i>
                                                <p>Geen rollen gevonden</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
