@php
    $scopedCompany = $scopedTenantCompany ?? ($tenantCompany ?? null);
    $storedCompany = $storedTenantCompany ?? (isset($paymentProvider) ? $paymentProvider->company : null);
    $displayCompany = $scopedCompany ?? $storedCompany;
    $hasScopedTenant = $scopedCompany !== null;
    $storedCompanyId = isset($paymentProvider) ? $paymentProvider->company_id : null;
    $storedUnlinked = $storedCompanyId === null || $storedCompanyId === '';
@endphp
<div class="kt-card min-w-full border-info info-card-blue mb-0 payment-provider-tenant-card">
    <div class="kt-card-content py-4 px-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-sm">
            <div>
                <span class="font-semibold text-mono">Bedrijf (tenant)</span>
                @if($displayCompany)
                    <span class="ms-1">— {{ $displayCompany->name }}</span>
                    <span class="text-xs opacity-80 ms-1">(ID {{ $displayCompany->id }})</span>
                @else
                    <span class="ms-1 text-destructive font-medium">— geen tenant gekozen</span>
                @endif
                @if($hasScopedTenant && $storedUnlinked)
                    <span class="block text-xs opacity-80 mt-1">Wordt bij opslaan gekoppeld aan dit bedrijf.</span>
                @elseif($storedCompany && $scopedCompany && (int) $storedCompany->id !== (int) $scopedCompany->id)
                    <span class="block text-xs opacity-80 mt-1">Opgeslagen: {{ $storedCompany->name }} (ID {{ $storedCompany->id }})</span>
                @endif
            </div>
            <p class="text-xs mb-0 sm:text-end opacity-90 max-w-xl">
                @if($hasScopedTenant)
                    Geselecteerde tenant in de zijbalk. Deze betalingsprovider geldt voor dit bedrijf (o.a. Nexa Taxi-betalingen).
                @else
                    Selecteer eerst een bedrijf via de <strong>tenant-switcher</strong> in de zijbalk. Zonder tenant worden betalingen niet aan een bedrijf gekoppeld.
                @endif
            </p>
        </div>
    </div>
</div>

@once
@push('styles')
<style>
    .payment-provider-tenant-card.info-card-blue,
    .info-card-blue.payment-provider-tenant-card {
        background-color: rgba(59, 130, 246, 0.15) !important;
        border-color: rgba(59, 130, 246, 0.4) !important;
    }
    .dark .payment-provider-tenant-card.info-card-blue,
    .dark .info-card-blue.payment-provider-tenant-card {
        background-color: rgba(59, 130, 246, 0.2) !important;
        border-color: rgba(59, 130, 246, 0.5) !important;
    }
</style>
@endpush
@endonce
