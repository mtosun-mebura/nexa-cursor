@extends('admin.layouts.app')

@section('title', 'Categorie Bewerken')

@section('content')
<style>
    :root {
        --primary-color: #ff9800;
        --primary-light: #ffb74d;
        --primary-dark: #f57c00;
        --primary-hover: #ff8f00;
    }
</style>

@include('admin.material-design-template')


<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="material-card">
                <div class="card-header">
                    <h5 >
                        <i class="fas fa-tags"></i> Categorie Bewerken
                    </h5>
                    <div>
                        <a href="{{ route('admin.categories.show', $category) }}" class="btn btn-info me-2">
                            <i class="fas fa-eye"></i> Bekijken
                        </a>
                        <a href="{{ route('admin.categories.index') }}" class="material-btn material-btn-secondary">
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

                    <form action="{{ route('admin.categories.update', $category) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="name" class="material-form-label">Naam *</label>
                                    <input type="text" class="material-form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name', $category->name) }}" required>
                                    @error('name')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="slug" class="material-form-label">Slug</label>
                                    <input type="text" class="material-form-control @error('slug') is-invalid @enderror" 
                                           id="slug" name="slug" value="{{ old('slug', $category->slug) }}" 
                                           placeholder="Automatisch gegenereerd">
                                    @error('slug')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="material-text-muted">Laat leeg om automatisch te genereren op basis van de naam</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="material-form-group">
                                    <label for="description" class="material-form-label">Beschrijving</label>
                                    <textarea class="material-form-control @error('description') is-invalid @enderror" 
                                              id="description" name="description" rows="4">{{ old('description', $category->description) }}</textarea>
                                    @error('description')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="color" class="material-form-label">Kleur</label>
                                    <input type="color" class="material-form-control @error('color') is-invalid @enderror" 
                                           id="color" name="color" value="{{ old('color', $category->color ?? '#007bff') }}">
                                    @error('color')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="icon" class="material-form-label">Icoon</label>
                                    <input type="text" class="material-form-control @error('icon') is-invalid @enderror" 
                                           id="icon" name="icon" value="{{ old('icon', $category->icon) }}" 
                                           placeholder="bijv. fas fa-briefcase">
                                    @error('icon')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="material-text-muted">Font Awesome icoon class</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="is_active" class="material-form-label">Status</label>
                                    <select class="material-form-select @error('is_active') is-invalid @enderror" 
                                            id="is_active" name="is_active">
                                        <option value="1" {{ old('is_active', $category->is_active) == '1' ? 'selected' : '' }}>Actief</option>
                                        <option value="0" {{ old('is_active', $category->is_active) == '0' ? 'selected' : '' }}>Inactief</option>
                                    </select>
                                    @error('is_active')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="sort_order" class="material-form-label">Sorteervolgorde</label>
                                    <input type="number" class="material-form-control @error('sort_order') is-invalid @enderror" 
                                           id="sort_order" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}" min="0">
                                    @error('sort_order')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="material-form-actions">
                            <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary me-2">Annuleren</a>
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
