@extends('admin.layouts.app')

@section('title', 'Recht Details - ' . $permission->name)

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
                <i class="fas fa-key"></i>
                Recht Details: {{ $permission->name }}
            </h5>
            <div class="material-header-actions">
                <a href="{{ route('admin.permissions.edit', $permission) }}" class="kt-btn kt-btn-warning me-2">
                    <i class="fas fa-edit"></i> Bewerken
                </a>
                <a href="{{ route('admin.permissions.index') }}" class="kt-btn kt-btn-outline">
                    <i class="fas fa-arrow-left"></i> Terug naar Overzicht
                </a>
            </div>
        </div>
        <div class="kt-card-content">
            <!-- Permission Header Section -->
            <div class="permission-header">
                <h1 class="permission-title">{{ ucfirst(str_replace('-', ' ', $permission->name)) }}</h1>
                <div class="permission-meta">
                    <div class="meta-item">
                        <i class="fas fa-key"></i>
                        <span>{{ $permission->name }}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-folder"></i>
                        <span>{{ $permission->group ?? 'Geen groep' }}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>{{ $permission->guard_name }}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        <span>Aangemaakt: {{ $permission->created_at->format('d-m-Y') }}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-clock"></i>
                        <span>Bijgewerkt: {{ $permission->updated_at->format('d-m-Y') }}</span>
                    </div>
                </div>
                <div class="permission-status status-active">
                    <i class="fas fa-circle"></i>
                    Actief
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value">{{ $permission->roles->count() }}</div>
                    <div class="stat-label">Rollen</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">{{ $permission->users->count() }}</div>
                    <div class="stat-label">Gebruikers</div>
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
                            <td>{{ $permission->id }}</td>
                        </tr>
                        <tr>
                            <td>Naam</td>
                            <td><code>{{ $permission->name }}</code></td>
                        </tr>
                        <tr>
                            <td>Groep</td>
                            <td>
                                @if($permission->group)
                                    <span class="kt-badge kt-badge-primary">{{ $permission->group }}</span>
                                @else
                                    <span class="material-text-muted">Geen groep</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Guard</td>
                            <td>{{ $permission->guard_name }}</td>
                        </tr>
                        <tr>
                            <td>Beschrijving</td>
                            <td>{{ $permission->description ?? 'Geen beschrijving' }}</td>
                        </tr>
                    </kt-table>
                </div>
                
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-cog"></i>
                        Systeem Informatie
                    </h6>
                    <kt-table class="info-kt-table">
                        <tr>
                            <td>Aangemaakt op</td>
                            <td>{{ $permission->created_at->format('d-m-Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td>Laatst bijgewerkt</td>
                            <td>{{ $permission->updated_at->format('d-m-Y H:i') }}</td>
                        </tr>
                    </kt-table>
                </div>
            </div>

            <!-- Roles with this Permission -->
            <div class="info-section">
                <h6 class="section-title">
                    <i class="fas fa-user-shield"></i>
                    Rollen met dit Recht ({{ $permission->roles->count() }})
                </h6>
                
                @if($permission->roles->count() > 0)
                    <div class="kt-table-responsive">
                        <kt-table class="kt-table material-kt-table">
                            <thead>
                                <tr>
                                    <th>Rol Naam</th>
                                    <th>Beschrijving</th>
                                    <th>Type</th>
                                    <th>Aantal Gebruikers</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($permission->roles as $role)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-user-shield me-2 text-primary"></i>
                                                <strong>{{ $role->name }}</strong>
                                            </div>
                                        </td>
                                        <td>{{ $role->description ?? 'Geen beschrijving' }}</td>
                                        <td>
                                            @if(in_array($role->name, ['super-admin', 'company-admin', 'staff', 'candidate']))
                                                <span class="kt-badge kt-badge-warning">Systeem</span>
                                            @else
                                                <span class="kt-badge kt-badge-success">Aangepast</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="kt-badge kt-badge-secondary">{{ $role->users->count() }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </kt-table>
                    </div>
                @else
                    <div class="kt-alert kt-alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Dit recht is niet toegewezen aan rollen.
                    </div>
                @endif
            </div>

            <!-- Users with this Permission -->
            @if($permission->users->count() > 0)
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-users"></i>
                        Gebruikers met dit Recht ({{ $permission->users->count() }})
                    </h6>
                    
                    <div class="kt-table-responsive">
                        <kt-table class="kt-table material-kt-table">
                            <thead>
                                <tr>
                                    <th>Naam</th>
                                    <th>E-mail</th>
                                    <th>Bedrijf</th>
                                    <th>Rollen</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($permission->users as $user)
                                    <tr>
                                        <td>{{ $user->first_name }} {{ $user->last_name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->company->name ?? 'Geen bedrijf' }}</td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach($user->roles as $role)
                                                    <span class="kt-badge kt-badge-info">{{ $role->name }}</span>
                                                @endforeach
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </kt-table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
