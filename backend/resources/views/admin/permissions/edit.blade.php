@extends('admin.layouts.app')

@section('title', 'Recht Bewerken')

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
                        <i class="fas fa-key me-2"></i> Recht Bewerken: {{ $permission->name }}
                    </h5>
                    <a href="{{ route('admin.permissions.index') }}" class="material-btn material-btn-secondary">
                        <i class="fas fa-arrow-left"></i> Terug naar Overzicht
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.permissions.update', $permission) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="name" class="material-form-label">Recht Naam *</label>
                                    <input type="text" 
                                           class="material-form-control @error('name') is-invalid @enderror" 
                                           id="name" 
                                           name="name" 
                                           value="{{ old('name', $permission->name) }}" 
                                           required>
                                    @error('name')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="group" class="material-form-label">Groep *</label>
                                    <input type="text" 
                                           class="material-form-control @error('group') is-invalid @enderror" 
                                           id="group" 
                                           name="group" 
                                           value="{{ old('group', $permission->group) }}" 
                                           required>
                                    @error('group')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="material-form-group">
                            <label for="description" class="material-form-label">Beschrijving</label>
                            <textarea class="material-form-textarea @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="3">{{ old('description', $permission->description) }}</textarea>
                            @error('description')
                                <div class="material-invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="material-form-actions">
                            <a href="{{ route('admin.permissions.index') }}" class="material-btn material-btn-secondary">
                                <i class="fas fa-times"></i>
                                Annuleren
                            </a>
                            <button type="submit" class="material-btn material-btn-primary">
                                <i class="fas fa-save"></i>
                                Wijzigingen Opslaan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
