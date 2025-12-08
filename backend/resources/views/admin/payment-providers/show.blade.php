@extends('admin.layouts.app')

@section('title', 'Betalingsprovider Details - ' . $paymentProvider->name)

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

    <div class="kt-card">
        <div class="kt-card-header">
            <h5>
                <i class="fas fa-credit-card"></i>
                Betalingsprovider Details: {{ $paymentProvider->name }}
            </h5>
            <div class="material-header-actions">
                <a href="{{ route('admin.payment-providers.edit', $paymentProvider) }}" class="kt-btn kt-btn-warning me-2">
                    <i class="fas fa-edit"></i> Bewerken
                </a>
                <a href="{{ route('admin.payment-providers.index') }}" class="kt-btn kt-btn-outline">
                    <i class="fas fa-arrow-left"></i> Terug naar Overzicht
                </a>
            </div>
        </div>
        <div class="kt-card-content">
            <!-- Payment Provider Header Section -->
            <div class="payment-provider-header">
                <h1 class="payment-provider-title">{{ $paymentProvider->name }}</h1>
                <div class="payment-provider-meta">
                    <div class="meta-item">
                        <i class="fas fa-credit-card"></i>
                        <span>{{ ucfirst($paymentProvider->provider_type) }}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-globe"></i>
                        <span>Globale Configuratie</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        <span>Aangemaakt: {{ $paymentProvider->created_at->format('d-m-Y') }}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-clock"></i>
                        <span>Bijgewerkt: {{ $paymentProvider->updated_at->format('d-m-Y') }}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-cog"></i>
                        <span>{{ $paymentProvider->getConfigValue('test_mode') ? 'Test Modus' : 'Live Modus' }}</span>
                    </div>
                </div>
                <div class="payment-provider-status status-{{ $paymentProvider->is_active ? 'active' : 'inactive' }}">
                    <i class="fas fa-circle"></i>
                    {{ $paymentProvider->is_active ? 'Actief' : 'Inactief' }}
                </div>
            </div>

            <div class="info-grid">
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-info-circle"></i>
                        Basis Informatie
                    </h6>
                    <kt-table class="info-kt-table">
                        <tr>
                            <td>Naam</td>
                            <td>{{ $paymentProvider->name }}</td>
                        </tr>
                        <tr>
                            <td>Provider Type</td>
                            <td>
                                <span class="kt-badge kt-badge-primary">{{ ucfirst($paymentProvider->provider_type) }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td>Globale Configuratie</td>
                            <td>
                                <span class="kt-badge kt-badge-info">CMS Breed</span>
                                <small class="material-text-muted d-block">Deze provider is beschikbaar voor alle bedrijven</small>
                            </td>
                        </tr>
                        <tr>
                            <td>Status</td>
                            <td>
                                @if($paymentProvider->is_active)
                                    <span class="kt-badge kt-badge-success">Actief</span>
                                @else
                                    <span class="kt-badge kt-badge-secondary">Inactief</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Test Modus</td>
                            <td>
                                @if($paymentProvider->getConfigValue('test_mode'))
                                    <span class="kt-badge kt-badge-warning">Test</span>
                                @else
                                    <span class="kt-badge kt-badge-info">Live</span>
                                @endif
                            </td>
                        </tr>
                    </kt-table>
                </div>
                
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-cog"></i>
                        Configuratie
                    </h6>
                    <kt-table class="info-kt-table">
                        <tr>
                            <td>API Key</td>
                            <td>
                                <code>••••••••••••••••••••••••••••••••</code>
                                <small class="material-text-muted d-block">Versleuteld opgeslagen</small>
                            </td>
                        </tr>
                        @if($paymentProvider->getConfigValue('api_secret'))
                            <tr>
                                <td>API Secret</td>
                                <td>
                                    <code>••••••••••••••••••••••••••••••••</code>
                                    <small class="material-text-muted d-block">Versleuteld opgeslagen</small>
                                </td>
                            </tr>
                        @endif
                        @if($paymentProvider->getConfigValue('webhook_url'))
                            <tr>
                                <td>Webhook URL</td>
                                <td>
                                    <a href="{{ $paymentProvider->getConfigValue('webhook_url') }}" target="_blank">
                                        {{ $paymentProvider->getConfigValue('webhook_url') }}
                                    </a>
                                </td>
                            </tr>
                        @endif
                        @if($paymentProvider->getConfigValue('description'))
                            <tr>
                                <td>Beschrijving</td>
                                <td>{{ $paymentProvider->getConfigValue('description') }}</td>
                            </tr>
                        @endif
                    </kt-table>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-cog"></i>
                        Systeem Informatie
                    </h6>
                    <kt-table class="info-kt-table">
                        <tr>
                            <td>ID</td>
                            <td>{{ $paymentProvider->id }}</td>
                        </tr>
                        <tr>
                            <td>Aangemaakt op</td>
                            <td>{{ $paymentProvider->created_at->format('d-m-Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td>Laatst bijgewerkt</td>
                            <td>{{ $paymentProvider->updated_at->format('d-m-Y H:i') }}</td>
                        </tr>
                    </kt-table>
                </div>
                
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-link"></i>
                        Acties
                    </h6>
                    <kt-table class="info-kt-table">
                        <tr>
                            <td>Test Verbinding</td>
                            <td>
                                <button class="kt-btn kt-btn-info" onclick="testConnection({{ $paymentProvider->id }})">
                                    <i class="fas fa-plug"></i> Test Verbinding
                                </button>
                            </td>
                        </tr>
                    </kt-table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function testConnection(providerId) {
    if (confirm('Wil je de verbinding met deze betalingsprovider testen?')) {
        fetch(`/admin/payment-providers/${providerId}/test-connection`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Verbinding succesvol!');
            } else {
                alert('Verbinding mislukt: ' + data.message);
            }
        })
        .catch(error => {
            alert('Er is een fout opgetreden bij het testen van de verbinding.');
        });
    }
}
</script>
@endsection
