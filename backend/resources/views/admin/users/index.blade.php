@extends('admin.layouts.app')

@section('title', 'Gebruikers Beheer')

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
        background: linear-gradient(135deg, #2196f3 0%, #64b5f6 100%);
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
        background: linear-gradient(135deg, #2196f3 0%, #64b5f6 100%);
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
    
    .material-badge-success {
        background: #d4edda;
        color: #155724;
    }
    
    .material-badge-warning {
        background: #fff3cd;
        color: #856404;
    }
    
    .material-badge-info {
        background: #d1ecf1;
        color: #0c5460;
    }
    
    .material-badge-primary {
        background: #e3f2fd;
        color: #1976d2;
    }
    
    .material-badge-secondary {
        background: #f5f5f5;
        color: #757575;
    }
    
    .action-buttons {
        display: flex;
        gap: 8px;
    }
    
    .action-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }
    
    .action-btn:hover {
        transform: scale(1.1);
    }
    
    .action-btn-info {
        background: #17a2b8;
        color: white;
    }
    
    .action-btn-warning {
        background: #ffc107;
        color: #212529;
    }
    
    .action-btn-danger {
        background: #dc3545;
        color: white;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6c757d;
    }
    
    .empty-state i {
        font-size: 4rem;
        margin-bottom: 20px;
        opacity: 0.5;
    }
    
    .user-info {
        display: flex;
        flex-direction: column;
    }
    
    .user-name {
        font-weight: 600;
        color: #495057;
    }
    
    .user-middle-name {
        font-size: 0.85rem;
        color: #6c757d;
        margin-top: 4px;
    }
    
    .user-email {
        color: #2196f3;
        text-decoration: none;
    }
    
    .user-email:hover {
        color: #1976d2;
        text-decoration: underline;
    }
    
    .user-phone {
        color: #2196f3;
        text-decoration: none;
    }
    
    .user-phone:hover {
        color: #1976d2;
        text-decoration: underline;
    }
    
    .user-company {
        background: #e3f2fd;
        color: #1976d2;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
    }
    
    .user-roles {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
    }
    
    .date-info {
        font-size: 0.85rem;
        color: #6c757d;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="material-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i> Gebruikers Beheer
                    </h5>
                    <a href="{{ route('admin.users.create') }}" class="material-btn material-btn-primary">
                        <i class="fas fa-plus me-2"></i> Nieuwe Gebruiker
                    </a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table material-table">
                            <thead>
                                <tr>
                                    <th>Naam</th>
                                    <th>E-mail</th>
                                    <th>Telefoon</th>
                                    <th>Bedrijf</th>
                                    <th>Rollen</th>
                                    <th>Status</th>
                                    <th>Gemaakt op</th>
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
                                                <span class="material-badge material-badge-secondary">Geen bedrijf</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="user-roles">
                                                @foreach($user->roles as $role)
                                                    @if($role->name === 'super-admin')
                                                        @if(auth()->user()->hasRole('super-admin'))
                                                            <span class="material-badge material-badge-info">{{ ucfirst(str_replace('-', ' ', $role->name)) }}</span>
                                                        @else
                                                            <span class="material-badge material-badge-secondary">Verborgen</span>
                                                        @endif
                                                    @else
                                                        <span class="material-badge material-badge-info">{{ ucfirst(str_replace('-', ' ', $role->name)) }}</span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </td>
                                        <td>
                                            @if($user->email_verified_at)
                                                <span class="material-badge material-badge-success">Geverifieerd</span>
                                            @else
                                                <span class="material-badge material-badge-warning">Niet geverifieerd</span>
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
                        </table>
                    </div>

                    @if($users->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $users->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
