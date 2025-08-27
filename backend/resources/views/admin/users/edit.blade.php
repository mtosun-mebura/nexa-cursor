@extends('admin.layouts.app')

@section('title', 'Gebruiker Bewerken')

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
                        <i class="fas fa-user-edit"></i> Gebruiker Bewerken
                    </h5>
                    <div>
                        <a href="{{ route('admin.users.show', $user) }}" class="btn btn-info me-2">
                            <i class="fas fa-eye"></i> Bekijken
                        </a>
                        <a href="{{ route('admin.users.index') }}" class="material-btn material-btn-secondary">
                            <i class="fas fa-arrow-left"></i> Terug naar Overzicht
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="material-alert material-alert-danger">
                            <ul >
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.users.update', $user) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="first_name" class="material-form-label">Voornaam *</label>
                                    <input type="text" class="material-form-control @error('first_name') is-invalid @enderror" 
                                           id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" required>
                                    @error('first_name')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="last_name" class="material-form-label">Achternaam *</label>
                                    <input type="text" class="material-form-control @error('last_name') is-invalid @enderror" 
                                           id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" required>
                                    @error('last_name')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="email" class="material-form-label">E-mail *</label>
                                    <input type="email" class="material-form-control @error('email') is-invalid @enderror" 
                                           id="email" name="email" value="{{ old('email', $user->email) }}" required>
                                    @error('email')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="password" class="material-form-label">Nieuw Wachtwoord</label>
                                    <input type="password" class="material-form-control @error('password') is-invalid @enderror" 
                                           id="password" name="password" placeholder="Laat leeg om niet te wijzigen">
                                    @error('password')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="company_id" class="material-form-label">Bedrijf</label>
                                    @if(auth()->user()->hasRole('super-admin') && session('selected_tenant'))
                                        @php $selectedCompany = \App\Models\Company::find(session('selected_tenant')); @endphp
                                        <input type="text" class="material-form-control" value="{{ $selectedCompany->name }}" readonly>
                                        <input type="hidden" name="company_id" value="{{ session('selected_tenant') }}">
                                        <small class="material-text-muted">Tenant geselecteerd: {{ $selectedCompany->name }}</small>
                                    @else
                                        <select class="material-form-select @error('company_id') is-invalid @enderror" 
                                                id="company_id" name="company_id">
                                            <option value="">Selecteer bedrijf</option>
                                            @foreach(\App\Models\Company::all() as $company)
                                                <option value="{{ $company->id }}" {{ old('company_id', $user->company_id) == $company->id ? 'selected' : '' }}>
                                                    {{ $company->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('company_id')
                                            <div class="material-invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    @endif
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="date_of_birth" class="material-form-label">Geboortedatum</label>
                                    <input type="date" class="material-form-control @error('date_of_birth') is-invalid @enderror" 
                                           id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth', $user->date_of_birth) }}">
                                    @error('date_of_birth')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="phone" class="material-form-label">Telefoon</label>
                                    <input type="tel" class="material-form-control @error('phone') is-invalid @enderror" 
                                           id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
                                    @error('phone')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="role" class="material-form-label">Rol *</label>
                                    <select class="material-form-select @error('role') is-invalid @enderror" 
                                            id="role" name="role" required>
                                        <option value="">Selecteer rol</option>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->name }}" {{ old('role', $user->roles->first()->name ?? '') == $role->name ? 'selected' : '' }}>
                                                {{ ucfirst(str_replace('-', ' ', $role->name)) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('role')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="material-form-actions">
                            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary me-2">Annuleren</a>
                            <button type="submit" class="material-btn material-btn-primary">
                                <i class="fas fa-save"></i> Wijzigingen Opslaan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
