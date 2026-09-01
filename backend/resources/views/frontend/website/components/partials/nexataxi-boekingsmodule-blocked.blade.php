<div class="booking-module-layout w-full max-w-full website-section-inner">
    <div class="booking-module-card rounded-xl border p-5 sm:p-6 shadow-sm bg-neutral-primary text-heading">
        <h2 class="booking-module-title font-bold leading-tight text-lg mb-2">Boeken tijdelijk niet mogelijk</h2>
        <p class="text-sm text-body mb-0">{{ app(\App\Services\PlatformBilling\TenantBillingAccessService::class)->bookingDeniedMessage() }}</p>
    </div>
</div>
