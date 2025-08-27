@extends('admin.layouts.app')

@section('title', 'Gebruiker Details')

@section('content')
<style>
    :root {
        --primary-color: #2196f3;
        --primary-light: #64b5f6;
        --primary-dark: #1976d2;
        --primary-hover: #42a5f5;
    }
</style>

@include('admin.material-design-template')


<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="material-card">
                <div class="card-header">
                    <h5 >
                        <i class="fas fa-user"></i> Gebruiker Details
                    </h5>
                    <div>
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-warning me-2">
                            <i class="fas fa-edit"></i> Bewerken
                        </a>
                        <a href="{{ route('admin.users.index') }}" class="material-btn material-btn-secondary">
                            <i class="fas fa-arrow-left"></i> Terug naar Overzicht
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="material-section-title">Persoonlijke Informatie</h6>
                            <table class="material-info-table">
                                <tr>
                                    <td width="150"><strong>Naam:</strong></td>
                                    <td>{{ $user->first_name }} {{ $user->last_name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>E-mail:</strong></td>
                                    <td>{{ $user->email }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Telefoon:</strong></td>
                                    <td>{{ $user->phone ?? 'Niet opgegeven' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Geboortedatum:</strong></td>
                                    <td>{{ $user->date_of_birth ? \Carbon\Carbon::parse($user->date_of_birth)->format('d-m-Y') : 'Niet opgegeven' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Bedrijf:</strong></td>
                                    <td>
                                        @if($user->company)
                                            <span class="badge bg-info">{{ $user->company->name }}</span>
                                        @else
                                            <span class="material-text-muted">Geen bedrijf toegewezen</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="material-section-title">Account Informatie</h6>
                            <table class="material-info-table">
                                <tr>
                                    <td width="150"><strong>ID:</strong></td>
                                    <td>{{ $user->id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Rollen:</strong></td>
                                    <td>
                                        @foreach($user->roles as $role)
                                            @if($role->name === 'super-admin')
                                                @if(auth()->user()->hasRole('super-admin'))
                                                    <span class="material-badge material-badge-primary me-1">{{ $role->name }}</span>
                                                @else
                                                    <span class="material-badge material-badge-secondary me-1">Verborgen</span>
                                                @endif
                                            @else
                                                <span class="material-badge material-badge-primary me-1">{{ $role->name }}</span>
                                            @endif
                                        @endforeach
                                        @if($user->roles->isEmpty())
                                            <span class="material-text-muted">Geen rollen toegewezen</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>E-mail geverifieerd:</strong></td>
                                    <td>
                                        @if($user->email_verified_at)
                                            <span class="material-badge material-badge-success">Ja ({{ \Carbon\Carbon::parse($user->email_verified_at)->format('d-m-Y H:i') }})</span>
                                        @else
                                            <span class="material-badge material-badge-warning">Nee</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Telefoon geverifieerd:</strong></td>
                                    <td>
                                        @if($user->phone_verified_at)
                                            <span class="material-badge material-badge-success">Ja ({{ \Carbon\Carbon::parse($user->phone_verified_at)->format('d-m-Y H:i') }})</span>
                                        @else
                                            <span class="material-badge material-badge-warning">Nee</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Aangemaakt op:</strong></td>
                                    <td>{{ $user->created_at->format('d-m-Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Laatst bijgewerkt:</strong></td>
                                    <td>{{ $user->updated_at->format('d-m-Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($user->roles->isNotEmpty())
                        <hr class="material-divider">
                        <div class="row">
                            <div class="col-12">
                                <h6 class="material-section-title">Toegewezen Permissies</h6>
                                <div class="row">
                                    @foreach($user->roles as $role)
                                        <div class="col-md-6 mb-3">
                                            <div class="material-card">
                                                <div class="card-header">
                                                    <h6 >{{ $role->name }}</h6>
                                                </div>
                                                <div class="card-body">
                                                    @if($role->permissions->isNotEmpty())
                                                        <div class="row">
                                                            @foreach($role->permissions as $permission)
                                                                <div class="col-md-6">
                                                                    <small class="material-text-muted">{{ $permission->name }}</small>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <small class="material-text-muted">Geen specifieke permissies</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
