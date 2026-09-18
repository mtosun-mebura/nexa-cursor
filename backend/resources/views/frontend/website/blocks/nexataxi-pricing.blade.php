@php
    $pricingCompanyId = null;
    if (app()->bound('resolved_tenant_id')) {
        $rtid = app('resolved_tenant_id');
        if ($rtid !== null && $rtid !== '' && is_numeric($rtid) && (int) $rtid > 0) {
            $pricingCompanyId = (int) $rtid;
        }
    }
    $ratesData = app(\App\Services\NexaTaxiPublicRatesService::class)->getRatesForDisplay($pricingCompanyId);
@endphp
@if($ratesData && ($ratesData['rates_1_4'] || $ratesData['rates_5_8']))
    @include('frontend.website.partials.nexataxi-pricing-cards', [
        'rates_1_4' => $ratesData['rates_1_4'],
        'rates_5_8' => $ratesData['rates_5_8'],
        'cleaning_costs' => $ratesData['cleaning_costs'],
        'block' => $block ?? null,
    ])
@endif
