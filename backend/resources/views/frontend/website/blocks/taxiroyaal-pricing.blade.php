@php
    $ratesData = app(\App\Services\TaxiRoyaalPublicRatesService::class)->getRatesForDisplay();
@endphp
@if($ratesData && ($ratesData['rates_1_4'] || $ratesData['rates_5_8']))
    @include('frontend.website.partials.taxiroyaal-pricing-cards', [
        'rates_1_4' => $ratesData['rates_1_4'],
        'rates_5_8' => $ratesData['rates_5_8'],
        'cleaning_costs' => $ratesData['cleaning_costs'],
        'block' => $block ?? null,
    ])
@endif
