@extends('admin.layouts.app')

@section('title', 'Nieuwe Betalingsprovider')

@section('content')
<style>
    :root {
        --primary-color: #9c27b0;
        --primary-light: #ba68c8;
        --primary-dark: #7b1fa2;
        --secondary-color: #f3e5f5;
        --success-color: #4caf50;
        --warning-color: #ff9800;
        --danger-color: #f44336;
        --info-color: #2196f3;
        --light-bg: #fafafa;
        --dark-text: #212121;
        --medium-text: #757575;
        --border-color: #e0e0e0;
        --shadow-light: 0 2px 4px rgba(0,0,0,0.1);
        --shadow-medium: 0 4px 8px rgba(0,0,0,0.12);
        --shadow-heavy: 0 8px 16px rgba(0,0,0,0.15);
        --border-radius: 8px;
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .material-card {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-light);
        border: none;
        margin-bottom: 24px;
        transition: var(--transition);
        overflow: hidden;
    }
    
    .material-card:hover {
        box-shadow: var(--shadow-medium);
    }
    
    .material-card .card-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
        color: white;
        border-radius: 0;
        padding: 24px 32px;
        border: none;
        position: relative;
        overflow: hidden;
    }
    
    .material-card .card-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.1) 50%, transparent 70%);
        transform: translateX(-100%);
        transition: var(--transition);
    }
    
    .material-card .card-header:hover::before {
        transform: translateX(100%);
    }
    
    .material-card .card-body {
        padding: 32px;
    }
    
    .material-btn {
        border-radius: var(--border-radius);
        text-transform: uppercase;
        font-weight: 500;
        letter-spacing: 0.5px;
        padding: 12px 24px;
        border: none;
        transition: var(--transition);
        box-shadow: var(--shadow-light);
        position: relative;
        overflow: hidden;
        cursor: pointer;
        font-size: 14px;
    }
    
    .material-btn::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        background: rgba(255,255,255,0.3);
        border-radius: 50%;
        transform: translate(-50%, -50%);
        transition: var(--transition);
    }
    
    .material-btn:hover::before {
        width: 300px;
        height: 300px;
    }
    
    .material-btn:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-medium);
    }
    
    .material-btn:active {
        transform: translateY(0);
        box-shadow: var(--shadow-light);
    }
    
    .material-btn-primary {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
        color: white;
    }
    
    .material-btn-secondary {
        background: var(--light-bg);
        color: var(--dark-text);
        border: 1px solid var(--border-color);
    }
    
    .material-btn-secondary:hover {
        background: var(--secondary-color);
        color: var(--primary-color);
        transform: translateY(-2px);
        box-shadow: var(--shadow-medium);
    }
    
    .form-control, .form-select {
        border-radius: var(--border-radius);
        border: 1px solid var(--border-color);
        padding: 12px 16px;
        transition: var(--transition);
        background-color: white;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(156, 39, 176, 0.25);
        outline: none;
    }
    
    .form-label {
        font-weight: 600;
        color: var(--dark-text);
        margin-bottom: 8px;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .form-text {
        color: var(--medium-text);
        font-size: 12px;
        margin-top: 4px;
    }
    
    .form-check {
        margin-bottom: 16px;
    }
    
    .form-check-input {
        border-radius: 4px;
        border: 2px solid var(--border-color);
        transition: var(--transition);
    }
    
    .form-check-input:checked {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }
    
    .form-check-label {
        font-weight: 500;
        color: var(--dark-text);
        margin-left: 8px;
    }
    
    .alert {
        border-radius: var(--border-radius);
        border: none;
        padding: 16px 20px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        box-shadow: var(--shadow-light);
    }
    
    .alert-info {
        background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
        color: #1565c0;
    }
    
    .alert-info ul {
        margin-bottom: 0;
        padding-left: 20px;
    }
    
    .alert-info li {
        margin-bottom: 4px;
    }
    
    .alert-info li:last-child {
        margin-bottom: 0;
    }
    
    .invalid-feedback {
        color: var(--danger-color);
        font-size: 12px;
        margin-top: 4px;
    }
    
    .is-invalid {
        border-color: var(--danger-color) !important;
    }
    
    .is-invalid:focus {
        box-shadow: 0 0 0 0.2rem rgba(244, 67, 54, 0.25) !important;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="material-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-plus me-2"></i> Nieuwe Betalingsprovider
                    </h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.payment-providers.index') }}" class="material-btn material-btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Terug naar Overzicht
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.payment-providers.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Naam *</label>
                                    <input type="text" 
                                           class="form-control @error('name') is-invalid @enderror" 
                                           id="name" 
                                           name="name" 
                                           value="{{ old('name') }}" 
                                           required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="provider_type" class="form-label">Provider Type *</label>
                                    <select class="form-select @error('provider_type') is-invalid @enderror" 
                                            id="provider_type" 
                                            name="provider_type" 
                                            required>
                                        <option value="">Selecteer provider type</option>
                                        @foreach($providerTypes as $key => $name)
                                            <option value="{{ $key }}" {{ old('provider_type') == $key ? 'selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('provider_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="api_key" class="form-label">API Key *</label>
                                    <input type="password" 
                                           class="form-control @error('api_key') is-invalid @enderror" 
                                           id="api_key" 
                                           name="api_key" 
                                           required>
                                    <div class="form-text">De API key wordt versleuteld opgeslagen voor veiligheid.</div>
                                    @error('api_key')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="api_secret" class="form-label">API Secret</label>
                                    <input type="password" 
                                           class="form-control @error('api_secret') is-invalid @enderror" 
                                           id="api_secret" 
                                           name="api_secret">
                                    <div class="form-text">Optioneel, afhankelijk van de provider.</div>
                                    @error('api_secret')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="webhook_url" class="form-label">Webhook URL</label>
                                    <input type="url" 
                                           class="form-control @error('webhook_url') is-invalid @enderror" 
                                           id="webhook_url" 
                                           name="webhook_url" 
                                           value="{{ old('webhook_url') }}">
                                    <div class="form-text">URL voor webhook notificaties van de provider.</div>
                                    @error('webhook_url')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="description" class="form-label">Beschrijving</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" 
                                              id="description" 
                                              name="description" 
                                              rows="3">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="is_active" 
                                               name="is_active" 
                                               value="1" 
                                               {{ old('is_active') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            Actief
                                        </label>
                                    </div>
                                    <div class="form-text">Deze provider is beschikbaar voor betalingen.</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="test_mode" 
                                               name="test_mode" 
                                               value="1" 
                                               {{ old('test_mode') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="test_mode">
                                            Test Modus
                                        </label>
                                    </div>
                                    <div class="form-text">Gebruik test API keys in plaats van live keys.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    <div>
                                        <strong>Provider-specifieke informatie:</strong>
                                        <ul class="mb-0 mt-2">
                                            <li><strong>Mollie:</strong> Gebruik je Mollie API key (begint met 'test_' of 'live_')</li>
                                            <li><strong>Stripe:</strong> Gebruik je Stripe Secret Key (begint met 'sk_test_' of 'sk_live_')</li>
                                            <li><strong>PayPal:</strong> Gebruik je PayPal Client ID en Secret</li>
                                            <li><strong>Adyen:</strong> Gebruik je Adyen API Key</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.payment-providers.index') }}" class="material-btn material-btn-secondary">
                                <i class="fas fa-times"></i>
                                Annuleren
                            </a>
                            <button type="submit" class="material-btn material-btn-primary">
                                <i class="fas fa-save"></i>
                                Opslaan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
