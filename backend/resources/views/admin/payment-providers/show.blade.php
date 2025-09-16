@extends('admin.layouts.app')

@section('title', 'Betalingsprovider Details - ' . $paymentProvider->name)

@section('content')
<style>
    :root {
        --primary-color: #00897b;
        --primary-light: #4db6ac;
        --primary-dark: #00695c;
        --primary-hover: #26a69a;
        --success-color: #4caf50;
        --warning-color: #ff9800;
        --danger-color: #f44336;
        --info-color: #2196f3;
        --secondary-color: #757575;
        --light-bg: #f5f5f5;
        --border-color: #e0e0e0;
        --text-primary: #212121;
        --text-secondary: #757575;
        --shadow: 0 2px 4px rgba(0,0,0,0.1);
        --shadow-hover: 0 4px 8px rgba(0,0,0,0.15);
        --border-radius: 8px;
        --transition: all 0.3s ease;
    }

    .material-card {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        margin-bottom: 24px;
        overflow: hidden;
        transition: var(--transition);
    }

    .material-card:hover {
        box-shadow: var(--shadow-hover);
    }

    .card-header {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        color: white;
        padding: 20px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }

    .card-header h5 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .material-header-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .material-btn {
        padding: 10px 20px;
        border: none;
        border-radius: var(--border-radius);
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: var(--transition);
        cursor: pointer;
        font-size: 14px;
        height: 44px;
        min-height: 44px;
    }

    .material-btn-warning {
        background: var(--warning-color);
        color: white;
    }

    .material-btn-warning:hover {
        background: #f57c00;
        color: white;
        transform: translateY(-2px);
    }

    .material-btn-secondary {
        background: var(--light-bg);
        color: var(--text-primary);
    }

    .material-btn-secondary:hover {
        background: #e0e0e0;
        color: var(--text-primary);
        transform: translateY(-2px);
    }

    .card-body {
        padding: 24px;
    }

    .payment-provider-header {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-radius: var(--border-radius);
        padding: 24px;
        margin-bottom: 24px;
        border-left: 4px solid var(--primary-color);
    }

    .payment-provider-title {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 12px;
        line-height: 1.2;
    }

    .payment-provider-meta {
        display: flex;
        align-items: center;
        gap: 24px;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--text-secondary);
        font-size: 14px;
    }

    .meta-item i {
        color: var(--primary-color);
        width: 16px;
    }

    .payment-provider-status {
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }

    .payment-provider-status:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    .status-active {
        background: linear-gradient(135deg, #f1f8e9 0%, #81c784 100%);
        color: #388e3c;
        border: 2px solid #81c784;
    }

    .status-inactive {
        background: linear-gradient(135deg, #ffcdd2 0%, #e57373 100%);
        color: #d32f2f;
        border: 2px solid #e57373;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 24px;
        margin-bottom: 24px;
    }

    .info-section {
        background: white;
        border-radius: var(--border-radius);
        padding: 20px;
        box-shadow: var(--shadow);
        border: 1px solid var(--border-color);
    }

    .section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 16px;
        padding-bottom: 8px;
        border-bottom: 2px solid var(--primary-color);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .info-table {
        width: 100%;
        border-collapse: collapse;
    }

    .info-table tr {
        border-bottom: 1px solid var(--border-color);
    }

    .info-table tr:last-child {
        border-bottom: none;
    }

    .info-table td {
        padding: 12px 0;
        vertical-align: top;
    }

    .info-table td:first-child {
        font-weight: 600;
        color: var(--text-primary);
        width: 140px;
        min-width: 140px;
    }

    .info-table td:last-child {
        color: var(--text-secondary);
    }

    .material-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        text-transform: uppercase;
    }

    .material-badge-primary {
        background: var(--primary-color);
        color: white;
    }

    .material-badge-secondary {
        background: var(--secondary-color);
        color: white;
    }

    .material-badge-success {
        background: var(--success-color);
        color: white;
    }

    .material-badge-warning {
        background: var(--warning-color);
        color: white;
    }

    .material-badge-danger {
        background: var(--danger-color);
        color: white;
    }

    .material-badge-info {
        background: var(--info-color);
        color: white;
    }

    .material-text-muted {
        color: var(--text-secondary);
        font-style: italic;
    }

    code {
        background: var(--light-bg);
        color: var(--text-primary);
        padding: 4px 8px;
        border-radius: 4px;
        font-family: 'Courier New', monospace;
        font-size: 12px;
    }
</style>

<div class="container-fluid">
    <div class="material-card">
        <div class="card-header">
            <h5>
                <i class="fas fa-credit-card"></i>
                Betalingsprovider Details: {{ $paymentProvider->name }}
            </h5>
            <div class="material-header-actions">
                <a href="{{ route('admin.payment-providers.edit', $paymentProvider) }}" class="material-btn material-btn-warning me-2">
                    <i class="fas fa-edit"></i> Bewerken
                </a>
                <a href="{{ route('admin.payment-providers.index') }}" class="material-btn material-btn-secondary">
                    <i class="fas fa-arrow-left"></i> Terug naar Overzicht
                </a>
            </div>
        </div>
        <div class="card-body">
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
                    <table class="info-table">
                        <tr>
                            <td>Naam</td>
                            <td>{{ $paymentProvider->name }}</td>
                        </tr>
                        <tr>
                            <td>Provider Type</td>
                            <td>
                                <span class="material-badge material-badge-primary">{{ ucfirst($paymentProvider->provider_type) }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td>Globale Configuratie</td>
                            <td>
                                <span class="material-badge material-badge-info">CMS Breed</span>
                                <small class="material-text-muted d-block">Deze provider is beschikbaar voor alle bedrijven</small>
                            </td>
                        </tr>
                        <tr>
                            <td>Status</td>
                            <td>
                                @if($paymentProvider->is_active)
                                    <span class="material-badge material-badge-success">Actief</span>
                                @else
                                    <span class="material-badge material-badge-secondary">Inactief</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Test Modus</td>
                            <td>
                                @if($paymentProvider->getConfigValue('test_mode'))
                                    <span class="material-badge material-badge-warning">Test</span>
                                @else
                                    <span class="material-badge material-badge-info">Live</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-cog"></i>
                        Configuratie
                    </h6>
                    <table class="info-table">
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
                    </table>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-cog"></i>
                        Systeem Informatie
                    </h6>
                    <table class="info-table">
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
                    </table>
                </div>
                
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-link"></i>
                        Acties
                    </h6>
                    <table class="info-table">
                        <tr>
                            <td>Test Verbinding</td>
                            <td>
                                <button class="material-btn material-btn-info" onclick="testConnection({{ $paymentProvider->id }})">
                                    <i class="fas fa-plug"></i> Test Verbinding
                                </button>
                            </td>
                        </tr>
                    </table>
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
