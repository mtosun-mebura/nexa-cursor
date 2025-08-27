@extends('admin.layouts.app')

@section('title', 'Recht Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-key me-2"></i>
                        Recht Details: {{ $permission->name }}
                    </h5>
                    <div>
                        <a href="{{ route('admin.permissions.edit', $permission) }}" class="btn btn-warning">
                            <i class="fas fa-edit me-1"></i>
                            Bewerken
                        </a>
                        <a href="{{ route('admin.permissions.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>
                            Terug naar Overzicht
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="mb-3">
                                <i class="fas fa-info-circle me-2"></i>
                                Basis Informatie
                            </h6>
                            
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Naam:</strong></td>
                                    <td><code>{{ $permission->name }}</code></td>
                                </tr>
                                <tr>
                                    <td><strong>Groep:</strong></td>
                                    <td>
                                        <span class="badge bg-primary">{{ $permission->group ?? 'Geen groep' }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Beschrijving:</strong></td>
                                    <td>{{ $permission->description ?? 'Geen beschrijving' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Guard:</strong></td>
                                    <td>{{ $permission->guard_name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Aangemaakt:</strong></td>
                                    <td>{{ $permission->created_at->format('d-m-Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Laatst bijgewerkt:</strong></td>
                                    <td>{{ $permission->updated_at->format('d-m-Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="mb-3">
                                <i class="fas fa-users me-2"></i>
                                Statistieken
                            </h6>
                            
                            <div class="row">
                                <div class="col-6">
                                    <div class="card bg-info text-white">
                                        <div class="card-body text-center">
                                            <h4>{{ $permission->roles->count() }}</h4>
                                            <small>Rollen</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="card bg-success text-white">
                                        <div class="card-body text-center">
                                            <h4>{{ $permission->users->count() }}</h4>
                                            <small>Gebruikers</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="mb-4">
                        <h6 class="mb-3">
                            <i class="fas fa-user-shield me-2"></i>
                            Rollen met dit Recht ({{ $permission->roles->count() }})
                        </h6>
                        
                        @if($permission->roles->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
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
                                                        <span class="badge bg-warning">Systeem</span>
                                                    @else
                                                        <span class="badge bg-success">Aangepast</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">{{ $role->users->count() }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Dit recht is niet toegewezen aan rollen.
                            </div>
                        @endif
                    </div>

                    @if($permission->users->count() > 0)
                        <div class="mb-4">
                            <h6 class="mb-3">
                                <i class="fas fa-users me-2"></i>
                                Gebruikers met dit Recht ({{ $permission->users->count() }})
                            </h6>
                            
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead class="table-light">
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
                                                            <span class="badge bg-info">{{ $role->name }}</span>
                                                        @endforeach
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
