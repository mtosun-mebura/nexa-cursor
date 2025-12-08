@extends('admin.layouts.app')

@section('title', 'Betalingsprovider Bewerken')

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

    <div class="kt-card min-w-full pb-2.5">
                <div class="kt-card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-edit me-2"></i> Betalingsprovider Bewerken: {{ $paymentProvider->name }}
                    </h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.payment-providers.index') }}" class="kt-btn kt-btn-outline">
                            <i class="fas fa-arrow-left me-2"></i> Terug naar Overzicht
                        </a>
                    </div>
                </div>
                <div class="kt-card-content grid gap-5">
                    <form action="{{ route('admin.payment-providers.update', $paymentProvider) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="lg:col-span-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Naam *</label>
                                    <input type="text" 
                                           class="form-control @error('name') is-invalid @enderror" 
                                           id="name" 
                                           name="name" 
                                           value="{{ old('name', $paymentProvider->name) }}" 
                                           required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="lg:col-span-6">
                                <div class="mb-3">
                                    <label for="provider_type" class="form-label">Provider Type *</label>
                                    <select class="form-select @error('provider_type') is-invalid @enderror" 
                                            id="provider_type" 
                                            name="provider_type" 
                                            required>
                                        <option value="">Selecteer provider type</option>
                                        @foreach($providerTypes as $key => $name)
                                            <option value="{{ $key }}" {{ old('provider_type', $paymentProvider->provider_type) == $key ? 'selected' : '' }}>
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

                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="lg:col-span-6">
                                <div class="mb-3">
                                    <label for="api_key" class="form-label">API Key *</label>
                                    <input type="password" 
                                           class="form-control @error('api_key') is-invalid @enderror" 
                                           id="api_key" 
                                           name="api_key" 
                                           value="{{ old('api_key', $decryptedConfig['api_key'] ?? '') }}"
                                           required>
                                    <div class="form-text">De API key wordt versleuteld opgeslagen voor veiligheid.</div>
                                    @error('api_key')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="lg:col-span-6">
                                <div class="mb-3">
                                    <label for="api_secret" class="form-label">API Secret</label>
                                    <input type="password" 
                                           class="form-control @error('api_secret') is-invalid @enderror" 
                                           id="api_secret" 
                                           name="api_secret" 
                                           value="{{ old('api_secret', $decryptedConfig['api_secret'] ?? '') }}">
                                    <div class="form-text">Optioneel, afhankelijk van de provider.</div>
                                    @error('api_secret')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="lg:col-span-6">
                                <div class="mb-3">
                                    <label for="webhook_url" class="form-label">Webhook URL</label>
                                    <input type="url" 
                                           class="form-control @error('webhook_url') is-invalid @enderror" 
                                           id="webhook_url" 
                                           name="webhook_url" 
                                           value="{{ old('webhook_url', $paymentProvider->getConfigValue('webhook_url')) }}">
                                    <div class="form-text">URL voor webhook notificaties van de provider.</div>
                                    @error('webhook_url')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="lg:col-span-6">
                                <div class="mb-3">
                                    <label for="description" class="form-label">Beschrijving</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" 
                                              id="description" 
                                              name="description" 
                                              rows="4">{{ old('description', $paymentProvider->getConfigValue('description')) }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="lg:col-span-6">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="is_active" 
                                               name="is_active" 
                                               value="1" 
                                               {{ old('is_active', $paymentProvider->is_active) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            Actief
                                        </label>
                                    </div>
                                    <div class="form-text">Deze provider is beschikbaar voor betalingen.</div>
                                </div>
                            </div>
                            
                            <div class="lg:col-span-6">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="test_mode" 
                                               name="test_mode" 
                                               value="1" 
                                               {{ old('test_mode', $paymentProvider->getConfigValue('test_mode')) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="test_mode">
                                            Test Modus
                                        </label>
                                    </div>
                                    <div class="form-text">Gebruik test API keys in plaats van live keys.</div>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="w-full">
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
                            <a href="{{ route('admin.payment-providers.index') }}" class="kt-btn kt-btn-outline">
                                <i class="fas fa-times"></i>
                                Annuleren
                            </a>
                            <button type="submit" class="kt-btn kt-btn-primary">
                                <i class="fas fa-save"></i>
                                Bijwerken
                            </button>
                        </div>
                    </form>
                </div>
    </div>
</div>
@endsection
