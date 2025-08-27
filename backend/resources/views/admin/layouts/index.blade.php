@extends('admin.layouts.app')

@section('title', 'Layouts Beheer')

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
    
    .layout-info {
        display: flex;
        flex-direction: column;
    }
    
    .layout-name {
        font-weight: 600;
        color: #495057;
    }
    
    .layout-description {
        font-size: 0.85rem;
        color: #6c757d;
        margin-top: 4px;
    }
    
    .layout-type {
        background: #e3f2fd;
        color: #1976d2;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
    }
    
    .layout-version {
        background: #fff3e0;
        color: #f57c00;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
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
                        <i class="fas fa-palette me-2"></i> Layouts Beheer
                    </h5>
                    <a href="{{ route('admin.layouts.create') }}" class="material-btn material-btn-primary">
                        <i class="fas fa-plus me-2"></i> Nieuwe Layout
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

                    @if($layouts->count() > 0)
                        <div class="table-responsive">
                            <table class="table material-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Naam</th>
                                        <th>Type</th>
                                        <th>Versie</th>
                                        <th>Status</th>
                                        <th>Aangemaakt</th>
                                        <th>Acties</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($layouts as $layout)
                                        <tr>
                                            <td>{{ $layout->id }}</td>
                                            <td>
                                                <div class="layout-info">
                                                    <div class="layout-name">{{ $layout->name }}</div>
                                                    @if($layout->description)
                                                        <div class="layout-description">{{ Str::limit($layout->description, 50) }}</div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <span class="layout-type">{{ ucfirst(str_replace('_', ' ', $layout->type)) }}</span>
                                            </td>
                                            <td>
                                                @if($layout->version)
                                                    <span class="layout-version">{{ $layout->version }}</span>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($layout->is_active)
                                                    <span class="material-badge material-badge-success">Actief</span>
                                                @else
                                                    <span class="material-badge material-badge-secondary">Inactief</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="date-info">{{ $layout->created_at->format('d-m-Y H:i') }}</div>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="{{ route('admin.layouts.show', $layout) }}" class="action-btn action-btn-info" title="Bekijken">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('admin.layouts.edit', $layout) }}" class="action-btn action-btn-warning" title="Bewerken">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('admin.layouts.destroy', $layout) }}" method="POST" class="d-inline" onsubmit="return confirm('Weet je zeker dat je deze layout wilt verwijderen?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="action-btn action-btn-danger" title="Verwijderen">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($layouts->hasPages())
                            <div class="d-flex justify-content-center mt-4">
                                {{ $layouts->links() }}
                            </div>
                        @endif
                    @else
                        <div class="empty-state">
                            <i class="fas fa-palette"></i>
                            <h5>Geen layouts gevonden</h5>
                            <p>Er zijn nog geen layouts aangemaakt.</p>
                            <a href="{{ route('admin.layouts.create') }}" class="material-btn material-btn-primary">
                                <i class="fas fa-plus me-2"></i> Eerste Layout Aanmaken
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
