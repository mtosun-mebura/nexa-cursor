@php
    $formatPrice = function($value, $unit = '') {
        if ($value === null || $value === '' || (is_numeric($value) && (float)$value == 0)) return null;
        $num = number_format((float)$value, 2, ',', '.');
        return '€ ' . $num . ($unit ? ' ' . $unit : '');
    };
    $blockData = isset($block['data']) ? $block['data'] : [];
    $imgCar = $blockData['image_1_4_url'] ?? 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=600&q=80';
    $imgVan = $blockData['image_5_8_url'] ?? 'https://images.unsplash.com/photo-1563720360172-67b8f3dce741?w=600&q=80';
@endphp
<div class="taxiroyaal-pricing my-10 md:my-14" data-taxiroyaal-pricing>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8 mb-6 lg:mb-8">
        {{-- t/m 4 personen --}}
        @if($rates_1_4)
        <div class="rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 hover:border-blue-500/50 transition-colors shadow-lg">
            <div class="aspect-[16/10] w-full bg-gray-200 dark:bg-slate-700">
                <img src="{{ $imgCar }}" alt="" class="w-full h-full object-cover" loading="lazy">
            </div>
            <div class="p-5 md:p-6 text-gray-900 dark:text-gray-100">
                <h3 class="text-lg font-semibold mb-4">t/m 4 personen</h3>
                <ul class="space-y-3 text-sm">
                    @if($v = $formatPrice($rates_1_4->base_fare))
                    <li class="flex justify-between items-baseline"><span class="text-gray-600 dark:text-gray-300">Instaptarief</span><span class="font-semibold">{{ $v }}</span></li>
                    @endif
                    @if($v = $formatPrice($rates_1_4->price_per_km, '/km'))
                    <li class="flex justify-between items-baseline"><span class="text-gray-600 dark:text-gray-300">Prijs per km</span><span class="font-semibold">{{ $v }}</span></li>
                    @endif
                    @if($v = $formatPrice($rates_1_4->price_per_min, '/min'))
                    <li class="flex justify-between items-baseline"><span class="text-gray-600 dark:text-gray-300">Prijs per minuut</span><span class="font-semibold">{{ $v }}</span></li>
                    @endif
                    @if($v = $formatPrice($rates_1_4->min_fare, '/u'))
                    <li class="flex justify-between items-baseline"><span class="text-gray-600 dark:text-gray-300">Wachttarief vooraf p/u</span><span class="font-semibold">{{ $v }}</span></li>
                    @endif
                </ul>
            </div>
        </div>
        @endif

        {{-- 5 t/m 8 personen --}}
        @if($rates_5_8)
        <div class="rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 hover:border-blue-500/50 transition-colors shadow-lg">
            <div class="aspect-[16/10] w-full bg-gray-200 dark:bg-slate-700">
                <img src="{{ $imgVan }}" alt="" class="w-full h-full object-cover" loading="lazy">
            </div>
            <div class="p-5 md:p-6 text-gray-900 dark:text-gray-100">
                <h3 class="text-lg font-semibold mb-4">5 t/m 8 personen</h3>
                <ul class="space-y-3 text-sm">
                    @if($v = $formatPrice($rates_5_8->base_fare))
                    <li class="flex justify-between items-baseline"><span class="text-gray-600 dark:text-gray-300">Instaptarief</span><span class="font-semibold">{{ $v }}</span></li>
                    @endif
                    @if($v = $formatPrice($rates_5_8->price_per_km, '/km'))
                    <li class="flex justify-between items-baseline"><span class="text-gray-600 dark:text-gray-300">Prijs per km</span><span class="font-semibold">{{ $v }}</span></li>
                    @endif
                    @if($v = $formatPrice($rates_5_8->price_per_min, '/min'))
                    <li class="flex justify-between items-baseline"><span class="text-gray-600 dark:text-gray-300">Prijs per minuut</span><span class="font-semibold">{{ $v }}</span></li>
                    @endif
                    @if($v = $formatPrice($rates_5_8->min_fare, '/u'))
                    <li class="flex justify-between items-baseline"><span class="text-gray-600 dark:text-gray-300">Wachttarief vooraf p/u</span><span class="font-semibold">{{ $v }}</span></li>
                    @endif
                </ul>
            </div>
        </div>
        @endif
    </div>

    {{-- Overige kosten --}}
    @if($cleaning_costs !== null && (float)$cleaning_costs > 0)
    <div class="rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 hover:border-blue-500/50 transition-colors shadow-lg p-5 md:p-6 text-gray-900 dark:text-gray-100">
        <h3 class="text-lg font-semibold mb-3">Overige kosten</h3>
        <div class="flex justify-between items-baseline">
            <span class="text-gray-600 dark:text-gray-300">Reinigingskosten</span>
            <span class="font-semibold text-lg">€ {{ number_format((float)$cleaning_costs, 2, ',', '.') }}</span>
        </div>
    </div>
    @endif
</div>
