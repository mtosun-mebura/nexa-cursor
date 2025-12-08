@extends('admin.layouts.app')

@section('title', 'Gebruiker Details - ' . $user->first_name . ' ' . $user->last_name)

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

    <div class="kt-card">
        <div class="kt-card-header">
            <h5>
                <i class="fas fa-user"></i>
                Gebruiker Details: {{ $user->first_name }} {{ $user->last_name }}
            </h5>
            <div class="material-header-actions">
                <a href="{{ route('admin.users.edit', $user) }}" class="kt-btn kt-btn-warning me-2">
                    <i class="fas fa-edit"></i> Bewerken
                </a>
                <a href="{{ route('admin.users.index') }}" class="kt-btn kt-btn-outline">
                    <i class="fas fa-arrow-left"></i> Terug naar Overzicht
                </a>
            </div>
        </div>
        <div class="kt-card-content">
            <!-- User Header Section -->
            <div class="user-header">
                <h1 class="user-title">{{ $user->first_name }} {{ $user->last_name }}</h1>
                <div class="user-meta">
                    <div class="meta-item">
                        <i class="fas fa-envelope"></i>
                        <span>{{ $user->email }}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-building"></i>
                        <span>{{ $user->company->name ?? 'Geen bedrijf' }}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-user-shield"></i>
                        <span>{{ $user->roles->count() }} rollen</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        <span>Aangemaakt: {{ $user->created_at->format('d-m-Y') }}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-phone"></i>
                        <span>{{ $user->phone ?? 'Geen telefoon' }}</span>
                    </div>
                </div>
                <div class="user-status status-active">
                    <i class="fas fa-circle"></i>
                    @if($user->email_verified_at)
                        Actief
                    @else
                        Niet geverifieerd
                    @endif
                </div>
            </div>

            <div class="info-grid">
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-info-circle"></i>
                        Basis Informatie
                    </h6>
                    <kt-table class="info-kt-table">
                        <tr>
                            <td>ID</td>
                            <td>{{ $user->id }}</td>
                        </tr>
                        <tr>
                            <td>Naam</td>
                            <td>{{ $user->first_name }} {{ $user->last_name }}</td>
                        </tr>
                        <tr>
                            <td>E-mail</td>
                            <td>{{ $user->email }}</td>
                        </tr>
                        <tr>
                            <td>Telefoon</td>
                            <td>{{ $user->phone ?? 'Niet opgegeven' }}</td>
                        </tr>
                        <tr>
                            <td>Geboortedatum</td>
                            <td>{{ $user->date_of_birth ? \Carbon\Carbon::parse($user->date_of_birth)->format('d-m-Y') : 'Niet opgegeven' }}</td>
                        </tr>
                    </kt-table>
                </div>
                
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-user-shield"></i>
                        Account & Status
                    </h6>
                    <kt-table class="info-kt-table">
                        <tr>
                            <td>Status</td>
                            <td>
                                @if($user->email_verified_at)
                                    <span class="kt-badge kt-badge-success">Actief</span>
                                @else
                                    <span class="kt-badge kt-badge-warning">Niet geverifieerd</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Bedrijf</td>
                            <td>
                                @if($user->company)
                                    <span class="kt-badge kt-badge-primary">{{ $user->company->name }}</span>
                                @else
                                    <span class="material-text-muted">Geen bedrijf toegewezen</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Rollen</td>
                            <td>
                                @foreach($user->roles as $role)
                                    @if($role->name === 'super-admin')
                                        @if(auth()->user()->hasRole('super-admin'))
                                            <span class="kt-badge kt-badge-primary me-1">{{ $role->name }}</span>
                                        @else
                                            <span class="kt-badge kt-badge-secondary me-1">Verborgen</span>
                                        @endif
                                    @else
                                        <span class="kt-badge kt-badge-primary me-1">{{ $role->name }}</span>
                                    @endif
                                @endforeach
                                @if($user->roles->isEmpty())
                                    <span class="material-text-muted">Geen rollen toegewezen</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>E-mail geverifieerd</td>
                            <td>
                                @if($user->email_verified_at)
                                    <span class="kt-badge kt-badge-success">Ja ({{ \Carbon\Carbon::parse($user->email_verified_at)->format('d-m-Y H:i') }})</span>
                                @else
                                    <span class="kt-badge kt-badge-warning">Nee</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Telefoon geverifieerd</td>
                            <td>
                                @if($user->phone_verified_at)
                                    <span class="kt-badge kt-badge-success">Ja ({{ \Carbon\Carbon::parse($user->phone_verified_at)->format('d-m-Y H:i') }})</span>
                                @else
                                    <span class="kt-badge kt-badge-warning">Nee</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Aangemaakt op</td>
                            <td>{{ $user->created_at->format('d-m-Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td>Laatst bijgewerkt</td>
                            <td>{{ $user->updated_at->format('d-m-Y H:i') }}</td>
                        </tr>
                    </kt-table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
