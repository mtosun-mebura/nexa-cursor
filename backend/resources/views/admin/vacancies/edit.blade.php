@extends('admin.layouts.app')

@section('title', 'Vacature Bewerken')

@section('content')
<style>
    :root {
        --primary-color: #9c27b0;
        --primary-light: #ba68c8;
        --primary-dark: #7b1fa2;
        --primary-hover: #ab47bc;
    }
</style>

@include('admin.material-design-template')


<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="material-card">
                <div class="card-header">
                    <h5 >
                        <i class="fas fa-briefcase"></i> Vacature Bewerken
                    </h5>
                    <div>
                        <a href="{{ route('admin.vacancies.show', $vacancy) }}" class="btn btn-info me-2">
                            <i class="fas fa-eye"></i> Bekijken
                        </a>
                        <a href="{{ route('admin.vacancies.index') }}" class="material-btn material-btn-secondary">
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

                    <form action="{{ route('admin.vacancies.update', $vacancy) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="material-form-group">
                                    <label for="title" class="material-form-label">Titel *</label>
                                    <input type="text" class="material-form-control @error('title') is-invalid @enderror" 
                                           id="title" name="title" value="{{ old('title', $vacancy->title) }}" required>
                                    @error('title')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="material-form-group">
                                    <label for="status" class="material-form-label">Status *</label>
                                    <select class="material-form-select @error('status') is-invalid @enderror" 
                                            id="status" name="status" required>
                                        <option value="active" {{ old('status', $vacancy->status) == 'active' ? 'selected' : '' }}>Actief</option>
                                        <option value="inactive" {{ old('status', $vacancy->status) == 'inactive' ? 'selected' : '' }}>Inactief</option>
                                        <option value="draft" {{ old('status', $vacancy->status) == 'draft' ? 'selected' : '' }}>Concept</option>
                                    </select>
                                    @error('status')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="company_id" class="material-form-label">Bedrijf *</label>
                                    @if(auth()->user()->hasRole('super-admin') && session('selected_tenant'))
                                        @php $selectedCompany = \App\Models\Company::find(session('selected_tenant')); @endphp
                                        <input type="text" class="material-form-control" value="{{ $selectedCompany->name }}" readonly>
                                        <input type="hidden" name="company_id" value="{{ session('selected_tenant') }}">
                                        <small class="material-text-muted">Tenant geselecteerd: {{ $selectedCompany->name }}</small>
                                    @else
                                        <select class="material-form-select @error('company_id') is-invalid @enderror" 
                                                id="company_id" name="company_id" required>
                                            <option value="">Selecteer bedrijf</option>
                                            @foreach(\App\Models\Company::all() as $company)
                                                <option value="{{ $company->id }}" {{ old('company_id', $vacancy->company_id) == $company->id ? 'selected' : '' }}>
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
                                    <label for="category_id" class="material-form-label">Categorie</label>
                                    <select class="material-form-select @error('category_id') is-invalid @enderror" 
                                            id="category_id" name="category_id">
                                        <option value="">Selecteer categorie</option>
                                        @foreach(\App\Models\Category::all() as $category)
                                            <option value="{{ $category->id }}" {{ old('category_id', $vacancy->category_id) == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('category_id')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="location" class="material-form-label">Locatie</label>
                                    <input type="text" class="material-form-control @error('location') is-invalid @enderror" 
                                           id="location" name="location" value="{{ old('location', $vacancy->location) }}">
                                    @error('location')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="employment_type" class="material-form-label">Type Werk</label>
                                    <select class="material-form-select @error('employment_type') is-invalid @enderror" 
                                            id="employment_type" name="employment_type">
                                        <option value="">Selecteer type</option>
                                        <option value="full-time" {{ old('employment_type', $vacancy->employment_type) == 'full-time' ? 'selected' : '' }}>Volledig</option>
                                        <option value="part-time" {{ old('employment_type', $vacancy->employment_type) == 'part-time' ? 'selected' : '' }}>Deeltijd</option>
                                        <option value="contract" {{ old('employment_type', $vacancy->employment_type) == 'contract' ? 'selected' : '' }}>Contract</option>
                                        <option value="temporary" {{ old('employment_type', $vacancy->employment_type) == 'temporary' ? 'selected' : '' }}>Tijdelijk</option>
                                        <option value="internship" {{ old('employment_type', $vacancy->employment_type) == 'internship' ? 'selected' : '' }}>Stage</option>
                                    </select>
                                    @error('employment_type')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="salary_min" class="material-form-label">Minimum Salaris</label>
                                    <input type="number" class="material-form-control @error('salary_min') is-invalid @enderror" 
                                           id="salary_min" name="salary_min" value="{{ old('salary_min', $vacancy->salary_min) }}" min="0">
                                    @error('salary_min')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="salary_max" class="material-form-label">Maximum Salaris</label>
                                    <input type="number" class="material-form-control @error('salary_max') is-invalid @enderror" 
                                           id="salary_max" name="salary_max" value="{{ old('salary_max', $vacancy->salary_max) }}" min="0">
                                    @error('salary_max')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="material-form-group">
                                    <label for="description" class="material-form-label">Beschrijving *</label>
                                    <textarea class="material-form-control @error('description') is-invalid @enderror" 
                                              id="description" name="description" rows="6" required>{{ old('description', $vacancy->description) }}</textarea>
                                    @error('description')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="material-form-group">
                                    <label for="requirements" class="material-form-label">Vereisten</label>
                                    <textarea class="material-form-control @error('requirements') is-invalid @enderror" 
                                              id="requirements" name="requirements" rows="4">{{ old('requirements', $vacancy->requirements) }}</textarea>
                                    @error('requirements')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="material-form-group">
                                    <label for="benefits" class="material-form-label">Voordelen</label>
                                    <textarea class="material-form-control @error('benefits') is-invalid @enderror" 
                                              id="benefits" name="benefits" rows="4">{{ old('benefits', $vacancy->benefits) }}</textarea>
                                    @error('benefits')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="material-form-actions">
                            <a href="{{ route('admin.vacancies.index') }}" class="btn btn-secondary me-2">Annuleren</a>
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
