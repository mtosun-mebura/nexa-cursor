@extends('admin.layouts.app')

@section('title', 'Betalingsprovider Bewerken')

@section('content')

@push('styles')
<style>
    .info-card-blue {
        background-color: rgba(59, 130, 246, 0.15) !important;
        border-color: rgba(59, 130, 246, 0.4) !important;
    }
    .dark .info-card-blue {
        background-color: rgba(59, 130, 246, 0.2) !important;
        border-color: rgba(59, 130, 246, 0.5) !important;
    }
    /* Overschrijf de default CSS voor API Key en andere cellen met .text-xs maar zonder textarea */
    /* Gebruik een zeer specifieke selector die de bestaande regel overschrijft */
    .kt-table.kt-table-border-dashed.align-middle tr td.payment-provider-label-cell,
    .kt-card-table .kt-table.kt-table-border-dashed.align-middle tr td.payment-provider-label-cell {
        vertical-align: middle !important;
        padding-top: 0 !important;
    }
    .kt-card-table .kt-table.kt-table-border-dashed.align-middle tr:has(td:nth-child(2) .text-xs):not(:has(td:nth-child(2) textarea)) td:first-child,
    .kt-card-table .kt-table.kt-table-border-dashed.align-middle tr:has(td:nth-child(2) input[type="password"]):not(:has(td:nth-child(2) textarea)) td:first-child {
        vertical-align: middle !important;
        padding-top: 0 !important;
    }
    .kt-card-table .kt-table.kt-table-border-dashed.align-middle tr:has(td:nth-child(2) .text-xs):not(:has(td:nth-child(2) textarea)) td:nth-child(2),
    .kt-card-table .kt-table.kt-table-border-dashed.align-middle tr:has(td:nth-child(2) input[type="password"]):not(:has(td:nth-child(2) textarea)) td:nth-child(2) {
        vertical-align: middle !important;
    }
    /* Voor rijen zonder textarea en zonder .text-xs */
    .kt-card-table .kt-table.kt-table-border-dashed.align-middle tr:not(:has(td:nth-child(2) textarea)):not(:has(td:nth-child(2) .text-xs)) td:first-child {
        vertical-align: middle !important;
        padding-top: 0 !important;
    }
    .kt-card-table .kt-table.kt-table-border-dashed.align-middle tr:not(:has(td:nth-child(2) textarea)):not(:has(td:nth-child(2) .text-xs)) td:nth-child(2) {
        vertical-align: middle !important;
    }
</style>
@endpush

<div class="kt-container-fixed">
    <div class="flex flex-col gap-5 pb-7.5">
        <div class="flex flex-wrap items-center justify-between gap-5">
            <h1 class="text-xl font-medium leading-none text-mono">
                Betalingsprovider Bewerken
            </h1>
        </div>
        <div class="flex items-center">
            <a href="{{ route('admin.payment-providers.show', $paymentProvider) }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
    </div>

    <form action="{{ route('admin.payment-providers.update', $paymentProvider) }}" method="POST" data-validate="true">
        @csrf
        @method('PUT')

        <div class="grid gap-5 lg:gap-7.5">
            <x-error-card :errors="$errors" />

            <!-- Basis Informatie -->
            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Basis Informatie
                    </h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-middle" style="padding-top: 0; vertical-align: middle;">
                                Naam *
                            </td>
                            <td class="min-w-48 w-full align-middle" style="vertical-align: middle;">
                                <input type="text" 
                                       class="kt-input @error('name') border-destructive @enderror" 
                                       name="name" 
                                       value="{{ old('name', $paymentProvider->name) }}" 
                                       required>
                                @error('name')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-middle" style="padding-top: 0; vertical-align: middle;">
                                Provider Type *
                            </td>
                            <td class="align-middle" style="vertical-align: middle;">
                                <select class="kt-select @error('provider_type') border-destructive @enderror" 
                                        name="provider_type" 
                                        data-kt-select="true"
                                        required>
                                    <option value="">Selecteer provider type</option>
                                    @foreach($providerTypes as $key => $name)
                                        <option value="{{ $key }}" {{ old('provider_type', $paymentProvider->provider_type) == $key ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('provider_type')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-middle" style="padding-top: 0; vertical-align: middle;">
                                Beschrijving
                            </td>
                            <td class="align-middle" style="vertical-align: middle;">
                                <textarea class="kt-input @error('description') border-destructive @enderror" 
                                          name="description" 
                                          rows="4">{{ old('description', $paymentProvider->getConfigValue('description')) }}</textarea>
                                @error('description')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- API Configuratie -->
            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        API Configuratie
                    </h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-middle payment-provider-label-cell" style="padding-top: 0 !important; vertical-align: middle !important;">
                                API Key *
                            </td>
                            <td class="min-w-48 w-full align-middle" style="vertical-align: middle;">
                                <input type="password" 
                                       class="kt-input @error('api_key') border-destructive @enderror" 
                                       name="api_key" 
                                       value="{{ old('api_key', $decryptedConfig['api_key'] ?? '') }}"
                                       required>
                                <div class="text-xs text-muted-foreground mt-1">De API key wordt versleuteld opgeslagen voor veiligheid.</div>
                                @error('api_key')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-middle" style="padding-top: 0; vertical-align: middle;">
                                API Secret
                            </td>
                            <td class="align-middle" style="vertical-align: middle;">
                                <input type="password" 
                                       class="kt-input @error('api_secret') border-destructive @enderror" 
                                       name="api_secret" 
                                       value="{{ old('api_secret', $decryptedConfig['api_secret'] ?? '') }}">
                                <div class="text-xs text-muted-foreground mt-1">Optioneel, afhankelijk van de provider.</div>
                                @error('api_secret')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-middle" style="padding-top: 0; vertical-align: middle;">
                                Webhook URL
                            </td>
                            <td class="align-middle" style="vertical-align: middle;">
                                <input type="url" 
                                       class="kt-input @error('webhook_url') border-destructive @enderror" 
                                       name="webhook_url" 
                                       value="{{ old('webhook_url', $paymentProvider->getConfigValue('webhook_url')) }}"
                                       placeholder="https://example.com/webhook">
                                <div class="text-xs text-muted-foreground mt-1">URL voor webhook notificaties van de provider.</div>
                                @error('webhook_url')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Instellingen -->
            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Instellingen
                    </h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-middle" style="padding-top: 0; vertical-align: middle;">
                                Status
                            </td>
                            <td class="min-w-48 w-full align-middle" style="vertical-align: middle;">
                                <label class="kt-label flex items-center">
                                    <input type="checkbox" 
                                           class="kt-switch kt-switch-sm" 
                                           name="is_active" 
                                           value="1" 
                                           {{ old('is_active', $paymentProvider->is_active) ? 'checked' : '' }}>
                                    <span class="ms-2">Actief</span>
                                </label>
                                <div class="text-xs text-muted-foreground mt-1">Deze provider is beschikbaar voor betalingen.</div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-middle" style="padding-top: 0; vertical-align: middle;">
                                Modus
                            </td>
                            <td class="align-middle" style="vertical-align: middle;">
                                <label class="kt-label flex items-center">
                                    <input type="checkbox" 
                                           class="kt-switch kt-switch-sm" 
                                           name="test_mode" 
                                           value="1" 
                                           {{ old('test_mode', $paymentProvider->getConfigValue('test_mode')) ? 'checked' : '' }}>
                                    <span class="ms-2">Test Modus</span>
                                </label>
                                <div class="text-xs text-muted-foreground mt-1">Gebruik test API keys in plaats van live keys.</div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Provider-specifieke Informatie -->
            <div class="kt-card min-w-full border-info info-card-blue">
                <div class="kt-card-header">
                    <h3 class="kt-card-title flex items-center gap-2">
                        <i class="ki-filled ki-information text-info"></i>
                        Provider-specifieke Informatie
                    </h3>
                </div>
                <div class="kt-card-content">
                    <ul class="mt-2 mb-0 text-sm space-y-1.5">
                        <li><strong>Mollie:</strong> Gebruik je Mollie API key (begint met 'test_' of 'live_')</li>
                        <li><strong>Stripe:</strong> Gebruik je Stripe Secret Key (begint met 'sk_test_' of 'sk_live_')</li>
                        <li><strong>PayPal:</strong> Gebruik je PayPal Client ID en Secret</li>
                        <li><strong>Adyen:</strong> Gebruik je Adyen API Key</li>
                    </ul>
                </div>
            </div>

            <!-- Acties -->
            <div class="flex items-center justify-end gap-2.5">
                <a href="{{ route('admin.payment-providers.show', $paymentProvider) }}" class="kt-btn kt-btn-outline">
                    <i class="ki-filled ki-cross me-2"></i>
                    Annuleren
                </a>
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-check me-2"></i>
                    Bijwerken
                </button>
            </div>
        </div>
    </form>
</div>

@endsection
