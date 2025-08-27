@extends('admin.layouts.app')

@section('title', 'Notificatie Details')

@section('content')
<style>
    :root {
        --primary-color: #795548;
        --primary-light: #a1887f;
        --primary-dark: #5d4037;
        --primary-hover: #8d6e63;
    }
</style>

@include('admin.material-design-template')


<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="material-card">
                <div class="card-header">
                    <h5 >
                        <i class="fas fa-bell"></i> Notificatie Details
                    </h5>
                    <div>
                        <a href="{{ route('admin.notifications.edit', $notification) }}" class="btn btn-warning me-2">
                            <i class="fas fa-edit"></i> Bewerken
                        </a>
                        <a href="{{ route('admin.notifications.index') }}" class="material-btn material-btn-secondary">
                            <i class="fas fa-arrow-left"></i> Terug naar Overzicht
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h4>{{ $notification->title }}</h4>
                            <p class="material-section-title">
                                <span class="badge bg-{{ $notification->read_at ? 'success' : 'warning' }}">
                                    {{ $notification->read_at ? 'Gelezen' : 'Ongelezen' }}
                                </span>
                                <span class="ms-2">Type: {{ ucfirst($notification->type) }}</span>
                                <span class="ms-2">Prioriteit: {{ ucfirst($notification->priority) }}</span>
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="badge bg-info">{{ $notification->created_at->format('d-m-Y H:i') }}</span>
                        </div>
                    </div>

                    <hr class="material-divider">

                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="material-section-title">Gebruiker Informatie</h6>
                            <div class="material-card">
                                <div class="card-body">
                                    <h6>{{ $notification->user->first_name }} {{ $notification->user->last_name }}</h6>
                                    <p class="mb-1"><strong>E-mail:</strong> {{ $notification->user->email }}</p>
                                    <p class="mb-1"><strong>Bedrijf:</strong> {{ $notification->user->company->name ?? 'N/A' }}</p>
                                    <p ><strong>Telefoon:</strong> {{ $notification->user->phone ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="material-section-title">Notificatie Details</h6>
                            <table class="material-info-table">
                                <tr>
                                    <td width="150"><strong>Type:</strong></td>
                                    <td>{{ ucfirst($notification->type) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Prioriteit:</strong></td>
                                    <td>
                                        <span class="badge bg-{{ $notification->priority == 'urgent' ? 'danger' : ($notification->priority == 'high' ? 'warning' : ($notification->priority == 'low' ? 'secondary' : 'info')) }}">
                                            {{ ucfirst($notification->priority) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        <span class="badge bg-{{ $notification->read_at ? 'success' : 'warning' }}">
                                            {{ $notification->read_at ? 'Gelezen' : 'Ongelezen' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Gelezen op:</strong></td>
                                    <td>{{ $notification->read_at ? $notification->read_at->format('d-m-Y H:i') : 'Nog niet gelezen' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <hr class="material-divider">

                    <div class="row">
                        <div class="col-md-12">
                            <h6 class="material-section-title">Bericht</h6>
                            <div class="material-card">
                                <div class="card-body">
                                    {!! nl2br(e($notification->message)) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($notification->action_url)
                        <hr class="material-divider">
                        <div class="row">
                            <div class="col-md-12">
                                <h6 class="material-section-title">Actie URL</h6>
                                <div class="material-card">
                                    <div class="card-body">
                                        <a href="{{ $notification->action_url }}" target="_blank" class="btn btn-outline-primary">
                                            <i class="fas fa-external-link-alt"></i> {{ $notification->action_url }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($notification->data)
                        <hr class="material-divider">
                        <div class="row">
                            <div class="col-md-12">
                                <h6 class="material-section-title">Extra Data</h6>
                                <div class="material-card">
                                    <div class="card-header">
                                        <small class="material-text-muted">JSON data voor extra informatie</small>
                                    </div>
                                    <div class="card-body">
                                        <pre class="bg-light p-3 rounded" style="max-height: 200px; overflow-y: auto;">{{ $notification->data }}</pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <hr class="material-divider">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="material-section-title">Systeem Informatie</h6>
                            <table class="material-info-table">
                                <tr>
                                    <td width="150"><strong>ID:</strong></td>
                                    <td>{{ $notification->id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Aangemaakt:</strong></td>
                                    <td>{{ $notification->created_at->format('d-m-Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Bijgewerkt:</strong></td>
                                    <td>{{ $notification->updated_at->format('d-m-Y H:i') }}</td>
                                </tr>
                                @if($notification->scheduled_at)
                                    <tr>
                                        <td><strong>Gepland voor:</strong></td>
                                        <td>{{ $notification->scheduled_at->format('d-m-Y H:i') }}</td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="material-section-title">Acties</h6>
                            <div class="d-grid gap-2">
                                @if(!$notification->read_at)
                                    <button class="btn btn-success" onclick="markAsRead({{ $notification->id }})">
                                        <i class="fas fa-check"></i> Markeren als gelezen
                                    </button>
                                @else
                                    <button class="material-btn material-btn-warning" onclick="markAsUnread({{ $notification->id }})">
                                        <i class="fas fa-eye-slash"></i> Markeren als ongelezen
                                    </button>
                                @endif
                                
                                @if($notification->action_url)
                                    <a href="{{ $notification->action_url }}" target="_blank" class="material-btn material-btn-primary">
                                        <i class="fas fa-external-link-alt"></i> Actie uitvoeren
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function markAsRead(notificationId) {
    if (confirm('Deze notificatie markeren als gelezen?')) {
        fetch(`/admin/notifications/${notificationId}/mark-read`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            }
        });
    }
}

function markAsUnread(notificationId) {
    if (confirm('Deze notificatie markeren als ongelezen?')) {
        fetch(`/admin/notifications/${notificationId}/mark-unread`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            }
        });
    }
}
</script>
@endsection
