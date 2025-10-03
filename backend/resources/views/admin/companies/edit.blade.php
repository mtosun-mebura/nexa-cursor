@extends('admin.layouts.app')

@section('title', 'Bedrijf Bewerken')

@section('content')
<style>
    .material-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        margin-bottom: 24px;
    }

    .material-card .card-header {
        background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
        color: white;
        padding: 20px 24px;
        border: none;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .material-card .card-header h5 {
        margin: 0;
        font-weight: 600;
        font-size: 1.25rem;
    }

    .material-card .card-body {
        padding: 24px;
    }

    .material-btn {
        border: none;
        border-radius: 8px;
        padding: 12px 24px;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
        cursor: pointer;
        font-size: 0.875rem;
    }

    .material-btn-primary {
        background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
        color: white;
    }

    .material-btn-primary:hover {
        background: linear-gradient(135deg, #43a047 0%, #5cb85c 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
    }

    .material-btn-secondary {
        background: #f5f5f5;
        color: #333;
    }

    .material-btn-secondary:hover {
        background: #e0e0e0;
        transform: translateY(-1px);
    }

    .material-btn-info {
        background: linear-gradient(135deg, #2196f3 0%, #42a5f5 100%);
        color: white;
    }

    .material-btn-info:hover {
        background: linear-gradient(135deg, #1976d2 0%, #1e88e5 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
    }

    .material-form-group {
        margin-bottom: 20px;
    }

    .material-form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: #333;
        font-size: 0.875rem;
    }

    .material-form-control {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 0.875rem;
        transition: all 0.2s ease;
        background: white;
    }

    .material-form-control:focus {
        outline: none;
        border-color: #4caf50;
        box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
    }

    .material-form-control.is-invalid {
        border-color: #f44336;
    }

    .material-invalid-feedback {
        color: #f44336;
        font-size: 0.75rem;
        margin-top: 4px;
    }

    .material-alert {
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 20px;
        border: none;
    }

    .material-alert-danger {
        background: #ffebee;
        color: #c62828;
        border-left: 4px solid #f44336;
    }

    .material-section-title {
        color: #666;
        font-size: 1rem;
        font-weight: 600;
        margin: 24px 0 16px 0;
        padding-bottom: 8px;
        border-bottom: 2px solid #f0f0f0;
    }

    .material-form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        margin-top: 32px;
        padding-top: 24px;
        border-top: 1px solid #f0f0f0;
    }

    .material-header-actions {
        display: flex;
        gap: 12px;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="material-card">
                <div class="card-header">
                    <h5>
                        <i class="fas fa-building me-2"></i> Bedrijf Bewerken
                    </h5>
                    <div class="material-header-actions">
                        <a href="{{ route('admin.companies.show', $company) }}" class="material-btn material-btn-info">
                            <i class="fas fa-eye"></i> Bekijken
                        </a>
                        <a href="{{ route('admin.companies.index') }}" class="material-btn material-btn-secondary">
                            <i class="fas fa-arrow-left"></i> Terug naar Overzicht
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="material-alert material-alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.companies.update', $company) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="name" class="material-form-label">Bedrijfsnaam *</label>
                                    <input type="text" class="material-form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name', $company->name) }}" required>
                                    @error('name')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="kvk_number" class="material-form-label">KVK Nummer</label>
                                    <input type="text" class="material-form-control @error('kvk_number') is-invalid @enderror" 
                                           id="kvk_number" name="kvk_number" value="{{ old('kvk_number', $company->kvk_number) }}">
                                    @error('kvk_number')
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
                                           id="email" name="email" value="{{ old('email', $company->email) }}" required>
                                    @error('email')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="phone" class="material-form-label">Telefoon</label>
                                    <input type="tel" class="material-form-control @error('phone') is-invalid @enderror" 
                                           id="phone" name="phone" value="{{ old('phone', $company->phone) }}">
                                    @error('phone')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="website" class="material-form-label">Website</label>
                                    <input type="url" class="material-form-control @error('website') is-invalid @enderror" 
                                           id="website" name="website" value="{{ old('website', $company->website) }}">
                                    @error('website')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="industry" class="material-form-label">Branche</label>
                                    <input type="text" class="material-form-control @error('industry') is-invalid @enderror" 
                                           id="industry" name="industry" value="{{ old('industry', $company->industry) }}">
                                    @error('industry')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <h6 class="material-section-title">Adres Informatie</h6>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="street" class="material-form-label">Straat</label>
                                    <input type="text" class="material-form-control @error('street') is-invalid @enderror" 
                                           id="street" name="street" value="{{ old('street', $company->street) }}">
                                    @error('street')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="material-form-group">
                                    <label for="house_number" class="material-form-label">Huisnummer</label>
                                    <input type="text" class="material-form-control @error('house_number') is-invalid @enderror" 
                                           id="house_number" name="house_number" value="{{ old('house_number', $company->house_number) }}">
                                    @error('house_number')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="material-form-group">
                                    <label for="postal_code" class="material-form-label">Postcode</label>
                                    <input type="text" class="material-form-control @error('postal_code') is-invalid @enderror" 
                                           id="postal_code" name="postal_code" value="{{ old('postal_code', $company->postal_code) }}">
                                    @error('postal_code')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="city" class="material-form-label">Plaats</label>
                                    <input type="text" class="material-form-control @error('city') is-invalid @enderror" 
                                           id="city" name="city" value="{{ old('city', $company->city) }}">
                                    @error('city')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="country" class="material-form-label">Land</label>
                                    <input type="text" class="material-form-control @error('country') is-invalid @enderror" 
                                           id="country" name="country" value="{{ old('country', $company->country) }}">
                                    @error('country')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="material-form-group">
                                    <label for="description" class="material-form-label">Beschrijving</label>
                                    <textarea class="material-form-control @error('description') is-invalid @enderror" 
                                              id="description" name="description" rows="4">{{ old('description', $company->description) }}</textarea>
                                    @error('description')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <h6 class="material-section-title">Bedrijf Type</h6>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="material-form-group">
                                    <div class="form-check">
                                        <input class="form-check-input @error('is_intermediary') is-invalid @enderror" 
                                               type="checkbox" 
                                               id="is_intermediary" 
                                               name="is_intermediary" 
                                               value="1" 
                                               {{ old('is_intermediary', $company->is_intermediary) ? 'checked' : '' }}>
                                        <input type="hidden" name="is_intermediary" value="0">
                                        <label class="form-check-label material-form-label" for="is_intermediary">
                                            Dit is een tussenpartij (recruitment, detachering, uitzendbureau, broker)
                                        </label>
                                        @error('is_intermediary')
                                            <div class="material-invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="material-form-actions">
                            <a href="{{ route('admin.companies.index') }}" class="material-btn material-btn-secondary">Annuleren</a>
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
