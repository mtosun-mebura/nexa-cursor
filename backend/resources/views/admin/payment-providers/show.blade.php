@extends('admin.layouts.app')

@section('title', 'Betalingsprovider Details - ' . $paymentProvider->name)

@section('content')

<style>
    .hero-bg {
        background-image: url('{{ asset('assets/media/images/2600x1200/bg-1.png') }}');
    }
    .dark .hero-bg {
        background-image: url('{{ asset('assets/media/images/2600x1200/bg-1-dark.png') }}');
    }
    
    /* Success alert styling - groene achtergrond en tekst */
    .kt-alert-success {
        background-color: rgba(16, 185, 129, 0.15) !important;
        border: 2px solid rgba(16, 185, 129, 0.4) !important;
        border-left: 4px solid #10b981 !important;
        color: #059669 !important;
        padding: 1rem 1.25rem !important;
        border-radius: 0.5rem !important;
        font-weight: 500 !important;
    }
    
    .kt-alert-success i {
        color: #10b981 !important;
    }
    
    .dark .kt-alert-success {
        background-color: rgba(16, 185, 129, 0.2) !important;
        border-color: rgba(16, 185, 129, 0.5) !important;
        color: #10b981 !important;
    }
    
    /* Danger alert styling - rode achtergrond en tekst */
    .kt-alert-danger {
        background-color: rgba(239, 68, 68, 0.15) !important;
        border: 2px solid rgba(239, 68, 68, 0.4) !important;
        border-left: 4px solid #ef4444 !important;
        color: #dc2626 !important;
        padding: 1rem 1.25rem !important;
        border-radius: 0.5rem !important;
        font-weight: 500 !important;
    }
    
    .kt-alert-danger i {
        color: #ef4444 !important;
    }
    
    .dark .kt-alert-danger {
        background-color: rgba(239, 68, 68, 0.2) !important;
        border-color: rgba(239, 68, 68, 0.5) !important;
        color: #f87171 !important;
    }
</style>

<div class="bg-center bg-cover bg-no-repeat hero-bg">
    <!-- Container -->
    <div class="kt-container-fixed">
        <div class="flex flex-col items-center gap-2 lg:gap-3.5 py-4 lg:pt-5 lg:pb-10">
            <div id="status-icon" class="rounded-full border-3 size-[100px] shrink-0 flex items-center justify-center text-2xl font-semibold {{ $paymentProvider->is_active ? 'border-green-500 bg-green-500/10 text-green-500' : 'border-red-500 bg-red-500/10 text-red-500' }}">
                <x-heroicon-o-credit-card class="w-10 h-10" />
            </div>
            <div class="flex items-center gap-1.5">
                <div class="text-lg leading-5 font-semibold text-mono">
                    {{ $paymentProvider->name }}
                </div>
                @if($paymentProvider->is_active)
                    <svg class="text-green-500" fill="none" height="16" viewbox="0 0 15 16" width="15" xmlns="http://www.w3.org/2000/svg">
                        <path d="M14.5425 6.89749L13.5 5.83999C13.4273 5.76877 13.3699 5.6835 13.3312 5.58937C13.2925 5.49525 13.2734 5.39424 13.275 5.29249V3.79249C13.274 3.58699 13.2324 3.38371 13.1527 3.19432C13.0729 3.00494 12.9565 2.83318 12.8101 2.68892C12.6638 2.54466 12.4904 2.43073 12.2998 2.35369C12.1093 2.27665 11.9055 2.23801 11.7 2.23999H10.2C10.0982 2.24159 9.99722 2.22247 9.9031 2.18378C9.80898 2.1451 9.72371 2.08767 9.65249 2.01499L8.60249 0.957487C8.30998 0.665289 7.91344 0.50116 7.49999 0.50116C7.08654 0.50116 6.68999 0.665289 6.39749 0.957487L5.33999 1.99999C5.26876 2.07267 5.1835 2.1301 5.08937 2.16879C4.99525 2.20747 4.89424 2.22659 4.79249 2.22499H3.29249C3.08699 2.22597 2.88371 2.26754 2.69432 2.34731C2.50494 2.42709 2.33318 2.54349 2.18892 2.68985C2.04466 2.8362 1.93073 3.00961 1.85369 3.20013C1.77665 3.39064 1.73801 3.5945 1.73999 3.79999V5.29999C1.74159 5.40174 1.72247 5.50275 1.68378 5.59687C1.6451 5.691 1.58767 5.77627 1.51499 5.84749L0.457487 6.89749C0.165289 7.19 0.00115967 7.58654 0.00115967 7.99999C0.00115967 8.41344 0.165289 8.80998 0.457487 9.10249L1.49999 10.16C1.57267 10.2312 1.6301 10.3165 1.66878 10.4106C1.70747 10.5047 1.72659 10.6057 1.72499 10.7075V12.2075C1.72597 12.413 1.76754 12.6163 1.84731 12.8056C1.92709 12.995 2.04349 13.1668 2.18985 13.3111C2.3362 13.4553 2.50961 13.5692 2.70013 13.6463C2.89064 13.7233 3.0945 13.762 3.29999 13.76H4.79999C4.90174 13.7584 5.00275 13.7775 5.09687 13.8162C5.191 13.8549 5.27627 13.9123 5.34749 13.985L6.40499 15.0425C6.69749 15.3347 7.09404 15.4988 7.50749 15.4988C7.92094 15.4988 8.31748 15.3347 8.60999 15.0425L9.65999 14C9.73121 13.9273 9.81647 13.8699 9.9106 13.8312C10.0047 13.7925 10.1057 13.7734 10.2075 13.775H11.7075C12.1212 13.775 12.518 13.6106 12.8106 13.3181C13.1031 13.0255 13.2675 12.6287 13.2675 12.215V10.715C13.2659 10.6132 13.285 10.5122 13.3237 10.4181C13.3624 10.324 13.4198 10.2387 13.4925 10.1675L14.55 9.10999C14.6953 8.96452 14.8104 8.79176 14.8887 8.60164C14.9671 8.41152 15.007 8.20779 15.0063 8.00218C15.0056 7.79656 14.9643 7.59311 14.8847 7.40353C14.8051 7.21394 14.6888 7.04197 14.5425 6.89749ZM10.635 6.64999L6.95249 10.25C6.90055 10.3026 6.83864 10.3443 6.77038 10.3726C6.70212 10.4009 6.62889 10.4153 6.55499 10.415C6.48062 10.4139 6.40719 10.3982 6.33896 10.3685C6.27073 10.3389 6.20905 10.2961 6.15749 10.2425L4.37999 8.44249C4.32532 8.39044 4.28169 8.32793 4.25169 8.25867C4.22169 8.18941 4.20593 8.11482 4.20536 8.03934C4.20479 7.96387 4.21941 7.88905 4.24836 7.81934C4.27731 7.74964 4.31999 7.68647 4.37387 7.63361C4.42774 7.58074 4.4917 7.53926 4.56194 7.51163C4.63218 7.484 4.70726 7.47079 4.78271 7.47278C4.85816 7.47478 4.93244 7.49194 5.00112 7.52324C5.0698 7.55454 5.13148 7.59935 5.18249 7.65499L6.56249 9.05749L9.84749 5.84749C9.95296 5.74215 10.0959 5.68298 10.245 5.68298C10.394 5.68298 10.537 5.74215 10.6425 5.84749C10.6953 5.90034 10.737 5.96318 10.7653 6.03234C10.7935 6.1015 10.8077 6.1756 10.807 6.25031C10.8063 6.32502 10.7908 6.39884 10.7612 6.46746C10.7317 6.53608 10.6888 6.59813 10.635 6.64999Z" fill="currentColor"></path>
                    </svg>
                @endif
            </div>
            <div class="flex flex-wrap justify-center gap-1 lg:gap-4.5 text-sm">
                <div class="flex gap-1.25 items-center">
                    <span class="kt-badge kt-badge-sm kt-badge-info">
                        {{ ucfirst($paymentProvider->provider_type) }}
                    </span>
                </div>
                <div class="flex gap-1.25 items-center">
                    @if($paymentProvider->getConfigValue('test_mode', false))
                        <span class="kt-badge kt-badge-sm kt-badge-warning">Test Modus</span>
                    @else
                        <span class="kt-badge kt-badge-sm kt-badge-primary">Live Modus</span>
                    @endif
                </div>
                <div class="flex gap-1.25 items-center">
                    <span id="hero-status-badge" class="kt-badge kt-badge-sm {{ $paymentProvider->is_active ? 'kt-badge-success' : 'kt-badge-danger' }}">
                        {{ $paymentProvider->is_active ? 'Actief' : 'Inactief' }}
                    </span>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Container -->
</div>

<!-- Container -->
<div class="kt-container-fixed">
    <div class="flex items-center flex-wrap md:flex-nowrap lg:items-center justify-between gap-3 lg:gap-6 mb-5 lg:mb-10">
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.payment-providers.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
        <div class="flex items-center gap-2.5">
            @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('edit-payment-providers'))
            <form action="{{ route('admin.payment-providers.toggle-status', $paymentProvider) }}" method="POST" id="toggle-status-form" class="inline">
                @csrf
                <label class="kt-label flex items-center">
                    <input type="checkbox" 
                           class="kt-switch kt-switch-sm" 
                           id="toggle-status-checkbox"
                           {{ $paymentProvider->is_active ? 'checked' : '' }}/>
                    <span class="ms-2">Actief</span>
                </label>
            </form>
            @else
            <label class="kt-label flex items-center">
                <input type="checkbox" 
                       class="kt-switch kt-switch-sm" 
                       {{ $paymentProvider->is_active ? 'checked' : '' }} 
                       disabled/>
                <span class="ms-2">Actief</span>
            </label>
            @endif
            @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('edit-payment-providers'))
            <span class="text-orange-500">|</span>
            <button type="button" 
                    class="kt-btn kt-btn-sm test-connection-btn" 
                    data-provider-id="{{ $paymentProvider->id }}"
                    style="background-color: #f97316; color: white !important; border-color: #f97316;">
                <x-heroicon-o-bolt class="w-4 h-4 me-1" style="color: white !important;" />
                Test Verbinding
            </button>
            <span class="text-orange-500">|</span>
            <a href="{{ route('admin.payment-providers.edit', $paymentProvider) }}" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-notepad-edit me-2"></i>
                Bewerken
            </a>
            @endif
        </div>
    </div>
</div>
<!-- End of Container -->

<!-- Container -->
<div class="kt-container-fixed">
    <!-- begin: grid -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5 lg:gap-7.5">
        <!-- Basis Informatie -->
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    Basis Informatie
                </h3>
            </div>
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">
                            Naam
                        </td>
                        <td class="min-w-48 w-full text-foreground font-normal">
                            {{ $paymentProvider->name }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            Provider Type
                        </td>
                        <td class="text-foreground font-normal">
                            <span class="kt-badge kt-badge-sm kt-badge-info">
                                {{ ucfirst($paymentProvider->provider_type) }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            Status
                        </td>
                        <td class="text-foreground font-normal">
                            <span id="status-badge" class="kt-badge kt-badge-sm {{ $paymentProvider->is_active ? 'kt-badge-success' : 'kt-badge-danger' }}">
                                {{ $paymentProvider->is_active ? 'Actief' : 'Inactief' }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            Modus
                        </td>
                        <td class="text-foreground font-normal">
                            @if($paymentProvider->getConfigValue('test_mode', false))
                                <span class="kt-badge kt-badge-sm kt-badge-warning">Test</span>
                            @else
                                <span class="kt-badge kt-badge-sm kt-badge-primary">Live</span>
                            @endif
                        </td>
                    </tr>
                    @if($paymentProvider->getConfigValue('description'))
                    <tr>
                        <td class="text-secondary-foreground font-normal align-top">
                            Beschrijving
                        </td>
                        <td class="text-foreground font-normal break-words" style="word-wrap: break-word; overflow-wrap: break-word;">
                            {{ $paymentProvider->getConfigValue('description') }}
                        </td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <!-- Configuratie -->
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    Configuratie
                </h3>
            </div>
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">
                            API Key
                        </td>
                        <td class="min-w-48 w-full text-foreground font-normal">
                            <code class="text-xs bg-muted px-2 py-1 rounded">••••••••••••••••••••••••••••••••</code>
                            <small class="text-muted-foreground d-block mt-1">Versleuteld opgeslagen</small>
                        </td>
                    </tr>
                    @if($paymentProvider->getConfigValue('api_secret'))
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            API Secret
                        </td>
                        <td class="text-foreground font-normal">
                            <code class="text-xs bg-muted px-2 py-1 rounded">••••••••••••••••••••••••••••••••</code>
                            <small class="text-muted-foreground d-block mt-1">Versleuteld opgeslagen</small>
                        </td>
                    </tr>
                    @endif
                    @if($paymentProvider->getConfigValue('webhook_url'))
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            Webhook URL
                        </td>
                        <td class="text-foreground font-normal">
                            <a href="{{ $paymentProvider->getConfigValue('webhook_url') }}" target="_blank" class="text-primary hover:underline break-all">
                                {{ $paymentProvider->getConfigValue('webhook_url') }}
                            </a>
                        </td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <!-- Systeem Informatie -->
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    Systeem Informatie
                </h3>
            </div>
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">
                            ID
                        </td>
                        <td class="min-w-48 w-full text-foreground font-normal">
                            {{ $paymentProvider->id }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            Aangemaakt op
                        </td>
                        <td class="text-foreground font-normal">
                            {{ $paymentProvider->created_at->format('d-m-Y H:i') }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            Laatst bijgewerkt
                        </td>
                        <td class="text-foreground font-normal">
                            {{ $paymentProvider->updated_at->format('d-m-Y H:i') }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Testresultaat Verbinding -->
        <div id="test-result-card" class="kt-card" style="display: none;">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    Testresultaat Verbinding
                </h3>
            </div>
            <div class="kt-card-content pb-3">
                <div id="test-result-content"></div>
            </div>
        </div>
    </div>
    <!-- end: grid -->
</div>
<!-- End of Container -->

<!-- Test Connection Modal -->
<div class="kt-modal" id="testConnectionModal" tabindex="-1">
    <div class="kt-modal-dialog">
        <div class="kt-modal-content">
            <div class="kt-modal-header">
                <h3 class="kt-modal-title">
                    <x-heroicon-o-bolt class="w-5 h-5 me-2" />
                    Test Verbinding
                </h3>
                <button type="button" class="kt-btn kt-btn-sm kt-btn-icon" data-kt-dismiss="modal">
                    <i class="ki-filled ki-cross"></i>
                </button>
            </div>
            <div class="kt-modal-body">
                <div id="testResult"></div>
            </div>
            <div class="kt-modal-footer">
                <button type="button" class="kt-btn kt-btn-outline" data-kt-dismiss="modal">Sluiten</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle status functionality with AJAX
    const toggleCheckbox = document.getElementById('toggle-status-checkbox');
    const toggleForm = document.getElementById('toggle-status-form');
    const statusIcon = document.getElementById('status-icon');
    const statusBadge = document.getElementById('status-badge');
    const heroStatusBadge = document.getElementById('hero-status-badge');
    
    if (toggleCheckbox && toggleForm) {
        toggleCheckbox.addEventListener('change', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const isActive = this.checked;
            const providerId = {{ $paymentProvider->id }};
            
            // Disable checkbox during request
            this.disabled = true;
            
            fetch(`/admin/payment-providers/${providerId}/toggle-status`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error(`HTTP error! status: ${response.status}, body: ${text}`);
                    });
                }
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json();
                } else {
                    throw new Error('Response is not JSON');
                }
            })
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Er is een fout opgetreden');
                }
                // Update icon border and colors
                if (statusIcon) {
                    if (isActive) {
                        statusIcon.className = 'rounded-full border-3 size-[100px] shrink-0 flex items-center justify-center text-2xl font-semibold border-green-500 bg-green-500/10 text-green-500';
                    } else {
                        statusIcon.className = 'rounded-full border-3 size-[100px] shrink-0 flex items-center justify-center text-2xl font-semibold border-red-500 bg-red-500/10 text-red-500';
                    }
                }
                
                // Update status badge
                if (statusBadge) {
                    if (isActive) {
                        statusBadge.className = 'kt-badge kt-badge-sm kt-badge-success';
                        statusBadge.textContent = 'Actief';
                    } else {
                        statusBadge.className = 'kt-badge kt-badge-sm kt-badge-danger';
                        statusBadge.textContent = 'Inactief';
                    }
                }
                
                // Update hero status badge
                if (heroStatusBadge) {
                    if (isActive) {
                        heroStatusBadge.className = 'kt-badge kt-badge-sm kt-badge-success';
                        heroStatusBadge.textContent = 'Actief';
                    } else {
                        heroStatusBadge.className = 'kt-badge kt-badge-sm kt-badge-danger';
                        heroStatusBadge.textContent = 'Inactief';
                    }
                }
                
                // Re-enable checkbox
                this.disabled = false;
            })
            .catch(error => {
                console.error('Error toggling status:', error);
                // Revert checkbox state
                this.checked = !isActive;
                this.disabled = false;
                
                // Show error message
                alert('Er is een fout opgetreden bij het wijzigen van de status. Probeer het opnieuw.');
            });
        });
        
        // Prevent form submission
        toggleForm.addEventListener('submit', function(e) {
            e.preventDefault();
            return false;
        });
    }
    
    // Test connection functionality
    const testConnectionBtn = document.querySelector('.test-connection-btn');
    const modalElement = document.getElementById('testConnectionModal');
    const resultDiv = document.getElementById('testResult');
    const testResultCard = document.getElementById('test-result-card');
    const testResultContent = document.getElementById('test-result-content');
    
    if (testConnectionBtn && modalElement && resultDiv) {
        testConnectionBtn.addEventListener('click', function() {
            const providerId = this.dataset.providerId;
            
            // Disable button during test
            this.disabled = true;
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="ki-filled ki-loader-2 animate-spin me-1"></i>Testen...';
            
            // Show loading state in modal
            resultDiv.innerHTML = '<div class="text-center py-4"><i class="ki-filled ki-loader-2 animate-spin text-2xl text-primary"></i><p class="mt-2 text-sm text-secondary-foreground">Verbinding testen met de API...</p></div>';
            
            // Show loading state in card
            if (testResultCard && testResultContent) {
                testResultCard.style.display = 'flex';
                testResultContent.innerHTML = '<div class="text-center py-4"><i class="ki-filled ki-loader-2 animate-spin text-2xl text-primary"></i><p class="mt-2 text-sm text-secondary-foreground">Verbinding testen met de API...</p></div>';
            }
            
            // Show modal
            modalElement.style.display = 'block';
            modalElement.classList.add('show');
            
            fetch(`/admin/payment-providers/${providerId}/test-connection`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                // Re-enable button
                this.disabled = false;
                this.innerHTML = originalText;
                
                // Update modal result
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="kt-alert kt-alert-success" style="display: block;">
                            <i class="ki-filled ki-check-circle me-2" style="font-size: 1.25rem;"></i>
                            <strong style="font-size: 1rem; display: block; margin-bottom: 0.5rem;">✓ Verbinding geslaagd!</strong>
                            <span style="font-size: 0.875rem; display: block;">${data.message}</span>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="kt-alert kt-alert-danger" style="display: block;">
                            <i class="ki-filled ki-cross-circle me-2" style="font-size: 1.25rem;"></i>
                            <strong style="font-size: 1rem; display: block; margin-bottom: 0.5rem;">✗ Verbinding gefaald!</strong>
                            <span style="font-size: 0.875rem; display: block;">${data.message}</span>
                        </div>
                    `;
                }
                
                // Update card result
                if (testResultCard && testResultContent) {
                    testResultCard.style.display = 'flex';
                    if (data.success) {
                        testResultContent.innerHTML = `
                            <div class="kt-alert kt-alert-success" style="display: block;">
                                <i class="ki-filled ki-check-circle me-2" style="font-size: 1.25rem;"></i>
                                <strong style="font-size: 1rem; display: block; margin-bottom: 0.5rem;">✓ Verbinding geslaagd!</strong>
                                <span style="font-size: 0.875rem; display: block;">${data.message}</span>
                            </div>
                        `;
                    } else {
                        testResultContent.innerHTML = `
                            <div class="kt-alert kt-alert-danger" style="display: block;">
                                <i class="ki-filled ki-cross-circle me-2" style="font-size: 1.25rem;"></i>
                                <strong style="font-size: 1rem; display: block; margin-bottom: 0.5rem;">✗ Verbinding gefaald!</strong>
                                <span style="font-size: 0.875rem; display: block;">${data.message}</span>
                            </div>
                        `;
                    }
                }
            })
            .catch(error => {
                // Re-enable button
                this.disabled = false;
                this.innerHTML = originalText;
                
                console.error('Error testing connection:', error);
                const errorMessage = `Er is een fout opgetreden bij het testen van de verbinding: ${error.message}`;
                
                // Update modal result
                resultDiv.innerHTML = `
                    <div class="kt-alert kt-alert-danger" style="display: block;">
                        <i class="ki-filled ki-cross-circle me-2" style="font-size: 1.25rem;"></i>
                        <strong style="font-size: 1rem; display: block; margin-bottom: 0.5rem;">✗ Verbinding gefaald!</strong>
                        <span style="font-size: 0.875rem; display: block;">${errorMessage}</span>
                    </div>
                `;
                
                // Update card result
                if (testResultCard && testResultContent) {
                    testResultCard.style.display = 'flex';
                    testResultContent.innerHTML = `
                        <div class="kt-alert kt-alert-danger" style="display: block;">
                            <i class="ki-filled ki-cross-circle me-2" style="font-size: 1.25rem;"></i>
                            <strong style="font-size: 1rem; display: block; margin-bottom: 0.5rem;">✗ Verbinding gefaald!</strong>
                            <span style="font-size: 0.875rem; display: block;">${errorMessage}</span>
                        </div>
                    `;
                }
            });
        });
    }
    
    // Close modal when clicking outside or on close button
    if (modalElement) {
        const closeButtons = modalElement.querySelectorAll('[data-kt-dismiss="modal"]');
        closeButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                modalElement.style.display = 'none';
                modalElement.classList.remove('show');
            });
        });
        
        modalElement.addEventListener('click', (e) => {
            if (e.target === modalElement) {
                modalElement.style.display = 'none';
                modalElement.classList.remove('show');
            }
        });
    }
});
</script>
@endpush
@endsection
