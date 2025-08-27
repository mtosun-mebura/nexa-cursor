@extends('admin.layouts.app')

@section('title', 'Nieuw Recht')

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
                    <h5>
                        <i class="fas fa-key me-2"></i> Nieuw Recht Aanmaken
                    </h5>
                    <a href="{{ route('admin.permissions.index') }}" class="material-btn material-btn-secondary">
                        <i class="fas fa-arrow-left"></i> Terug naar Overzicht
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.permissions.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="name" class="material-form-label">Recht Naam *</label>
                                    <input type="text" 
                                           class="material-form-control @error('name') is-invalid @enderror" 
                                           id="name" 
                                           name="name" 
                                           value="{{ old('name') }}" 
                                           placeholder="bijv. view-users, create-vacancies"
                                           required>
                                    @error('name')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="material-text-muted">Gebruik kebab-case formaat: [actie]-[module]</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="group" class="material-form-label">Groep *</label>
                                    <input type="text" 
                                           class="material-form-control @error('group') is-invalid @enderror" 
                                           id="group" 
                                           name="group" 
                                           value="{{ old('group') }}" 
                                           placeholder="bijv. users, vacancies, companies"
                                           required>
                                    @error('group')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="material-text-muted">Module naam voor groepering</div>
                                </div>
                            </div>
                        </div>

                        <div class="material-form-group">
                            <label for="description" class="material-form-label">Beschrijving</label>
                            <textarea class="material-form-textarea @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="3" 
                                      placeholder="Beschrijf wat dit recht doet...">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="material-invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="material-alert material-alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Tip:</strong> Voor het aanmaken van meerdere rechten tegelijk, gebruik de 
                            <a href="{{ route('admin.permissions.bulk-create') }}" class="material-link">Bulk Aanmaken</a> functie.
                        </div>

                        <div class="material-form-actions">
                            <a href="{{ route('admin.permissions.index') }}" class="material-btn material-btn-secondary">
                                <i class="fas fa-times"></i>
                                Annuleren
                            </a>
                            <button type="submit" class="material-btn material-btn-primary">
                                <i class="fas fa-save"></i>
                                Recht Aanmaken
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
