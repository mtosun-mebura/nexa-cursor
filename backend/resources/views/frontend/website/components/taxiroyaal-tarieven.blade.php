@php
    $ratesData = app(\App\Services\TaxiRoyaalPublicRatesService::class)->getRatesForDisplay();
    $hasRates = $ratesData && ($ratesData['rates_1_4'] || $ratesData['rates_5_8']);
    $sectionConfig = isset($sectionKey) && isset($homeSections) ? ($homeSections[$sectionKey] ?? []) : [];
    $sectionItems = $sectionConfig['items'] ?? [];
    $hasSectionItemsWithContent = !empty($sectionItems) && $ratesData !== null;
    $sectionTitle = isset($sectionConfig['title']) ? trim((string) $sectionConfig['title']) : 'Onze tarieven';
    if ($sectionTitle === '') {
        $sectionTitle = 'Onze tarieven';
    }
    $allowedFontSizes = array_merge([''], array_map(fn ($px) => $px . 'px', range(10, 40, 2)));
    $allowedFontStyles = ['normal', 'bold', 'italic'];
    $allowedTextAligns = ['left', 'center', 'right'];
    $sectionTitleFontSize = isset($sectionConfig['title_font_size']) ? trim((string) $sectionConfig['title_font_size']) : '';
    if (!in_array($sectionTitleFontSize, $allowedFontSizes, true)) {
        $sectionTitleFontSize = '';
    }
    $sectionTitleFontStyle = isset($sectionConfig['title_font_style']) && in_array($sectionConfig['title_font_style'], $allowedFontStyles, true)
        ? $sectionConfig['title_font_style'] : 'normal';
    $sectionTitleAlign = isset($sectionConfig['title_align']) && in_array($sectionConfig['title_align'], $allowedTextAligns, true)
        ? $sectionConfig['title_align'] : 'left';
    $sectionTitleStyle = 'font-family: var(--theme-font-heading, inherit);';
    if ($sectionTitleFontSize !== '') {
        $sectionTitleStyle .= ' font-size: ' . $sectionTitleFontSize . ';';
    }
    if ($sectionTitleFontStyle === 'bold') {
        $sectionTitleStyle .= ' font-weight: 700;';
    } elseif ($sectionTitleFontStyle === 'italic') {
        $sectionTitleStyle .= ' font-style: italic;';
    } else {
        $sectionTitleStyle .= ' font-style: normal;';
    }
    $sectionTitleAlignClass = $sectionTitleAlign === 'center' ? 'text-center' : ($sectionTitleAlign === 'right' ? 'text-right' : 'text-left');
    $vehicleDisplayService = app(\App\Services\TaxiRoyaalVehicleDisplayService::class);
    $fallbackVehicleImages = collect($vehicleDisplayService->getVehiclesForSelect())
        ->map(function ($v) {
            $url = isset($v['image_url']) ? trim((string) $v['image_url']) : '';
            if ($url === '') {
                return null;
            }
            return str_starts_with($url, 'http') ? $url : asset(ltrim($url, '/'));
        })
        ->filter()
        ->values();
    $formatPrice = function($value, $unit = '') {
        if ($value === null || $value === '' || (is_numeric($value) && (float)$value == 0)) return null;
        $num = number_format((float)$value, 2, ',', '.');
        return '€ ' . $num . ($unit ? ' ' . $unit : '');
    };
    $valueColumnWidth = '120px';
    $cardWidthPx = [
        'small' => 400,
        'normal' => 600,
        'large' => 800,
    ];
@endphp
<section class="container-custom py-12 md:py-16" aria-labelledby="taxiroyaal-tarieven-heading">
    <h2 id="taxiroyaal-tarieven-heading" class="text-2xl md:text-3xl text-gray-900 dark:text-white mb-8 md:mb-10 {{ $sectionTitleAlignClass }}" style="{{ $sectionTitleStyle }}">{{ e($sectionTitle) }}</h2>
    @if($hasRates || $hasSectionItemsWithContent)
        @if(!empty($sectionItems))
            {{-- Per-card configuratie: één kaart per sectie-item met gekozen tarief (1-4 of 5-8), afbeelding en kaartopties --}}
            <div class="taxiroyaal-pricing my-10 md:my-14" data-taxiroyaal-pricing>
                <div class="flex flex-wrap justify-center items-stretch gap-6 lg:gap-8 mb-6 lg:mb-8">
                    @foreach($sectionItems as $itemIndex => $item)
                        @php
                            $rateType = $item['rate_type'] ?? '1-4';
                            $title = $item['title'] ?? ($rateType === '5-8' ? '5 t/m 8 personen' : ($rateType === 'overige_kosten' ? 'Overige kosten' : 't/m 4 personen'));
                            $isOverigeKosten = $rateType === 'overige_kosten';
                            $rate = $isOverigeKosten ? null : ($rateType === '5-8' ? $ratesData['rates_5_8'] : $ratesData['rates_1_4']);
                            $overigeKostenPrice = $isOverigeKosten ? (isset($item['cleaning_costs']) && $item['cleaning_costs'] !== '' && is_numeric($item['cleaning_costs']) ? (float)$item['cleaning_costs'] : (isset($ratesData['cleaning_costs']) && $ratesData['cleaning_costs'] !== null ? (float)$ratesData['cleaning_costs'] : null)) : null;
                            $imageUrl = !empty($item['image_url']) ? (str_starts_with($item['image_url'], 'http') ? $item['image_url'] : asset(ltrim($item['image_url'], '/'))) : (!empty($item['vehicle_id']) ? $vehicleDisplayService->getImageUrl((int)$item['vehicle_id']) : null);
                            if (!$isOverigeKosten && !$imageUrl && $fallbackVehicleImages->isNotEmpty()) {
                                $imageUrl = $fallbackVehicleImages->get($itemIndex % $fallbackVehicleImages->count());
                            }
                            $cardSize = isset($item['card_size']) ? (string)$item['card_size'] : 'normal';
                            $isMaxWidth = $cardSize === 'max';
                            $isTotalWidth = $cardSize === 'total_width';
                            $exactWidthPx = $cardWidthPx[$cardSize] ?? 600;
                            $wrapperClass = $isMaxWidth ? 'basis-full w-full' : 'shrink-0';
                            $wrapperStyle = $isMaxWidth
                                ? 'width:100%;max-width:100%;'
                                : ($isTotalWidth
                                    ? 'width:100%;max-width:100%;'
                                    : 'width:min(100%, ' . $exactWidthPx . 'px);max-width:' . $exactWidthPx . 'px;');
                            $fontStyle = $item['font_style'] ?? 'normal';
                            $textColor = !empty($item['text_color']) ? $item['text_color'] : '';
                            $titleFontFamily = isset($item['title_font_family']) ? trim((string) $item['title_font_family']) : '';
                            $allowedTitleFamilies = ['', 'sans-serif', 'serif', 'monospace', 'Inter', 'Georgia'];
                            if (!in_array($titleFontFamily, $allowedTitleFamilies, true)) {
                                $titleFontFamily = '';
                            }
                            $titleFontSize = isset($item['title_font_size']) ? trim((string) $item['title_font_size']) : '';
                            if (!in_array($titleFontSize, $allowedFontSizes, true)) {
                                $titleFontSize = '';
                            }
                            $labelFontSize = isset($item['label_font_size']) ? trim((string) $item['label_font_size']) : '';
                            if (!in_array($labelFontSize, $allowedFontSizes, true)) {
                                $labelFontSize = '';
                            }
                            $valueFontSize = isset($item['value_font_size']) ? trim((string) $item['value_font_size']) : '';
                            if (!in_array($valueFontSize, $allowedFontSizes, true)) {
                                $valueFontSize = '';
                            }
                            $titleStyle = $textColor !== '' ? ('color: ' . $textColor . ';') : '';
                            if ($titleFontFamily !== '') {
                                $titleStyle .= ' font-family: ' . $titleFontFamily . ';';
                            }
                            if ($titleFontSize !== '') {
                                $titleStyle .= ' font-size: ' . $titleFontSize . '; line-height: 1.2;';
                            }
                            $titleFontStyle = isset($item['title_font_style']) && in_array($item['title_font_style'], $allowedFontStyles, true)
                                ? $item['title_font_style'] : $fontStyle;
                            $titleFontClass = $titleFontStyle === 'bold' ? 'font-bold' : ($titleFontStyle === 'italic' ? 'italic' : 'font-normal');
                            $labelStyle = $labelFontSize !== '' ? ('font-size: ' . $labelFontSize . '; line-height: 1.2;') : '';
                            $valueStyle = $valueFontSize !== '' ? ('font-size: ' . $valueFontSize . '; line-height: 1.2;') : '';
                            $textAlignValue = $item['text_align'] ?? 'left';
                            $textAlign = in_array($textAlignValue, $allowedTextAligns, true) ? $textAlignValue : 'left';
                            $textAlignClass = 'text-' . $textAlign;
                            $titleAlign = isset($item['title_align']) && in_array($item['title_align'], $allowedTextAligns, true) ? $item['title_align'] : $textAlign;
                            $titleAlignClass = 'text-' . $titleAlign;
                            $imagePadding = (int)($item['image_padding'] ?? 2);
                            $imageBgColor = !empty($item['image_bg_color']) ? $item['image_bg_color'] : '#334155';
                        @endphp
                        @if($isOverigeKosten)
                        <div class="{{ $wrapperClass }}" style="{{ $wrapperStyle }}" data-taxiroyaal-card-wrapper data-card-size="{{ $cardSize }}">
                        <div class="rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 hover:border-blue-500/50 transition-colors shadow-lg w-full h-full flex flex-col {{ $textAlignClass }}" data-taxiroyaal-card data-card-size="{{ $cardSize }}">
                            @if($imageUrl)
                            <div class="aspect-[16/10] w-full overflow-hidden shrink-0" style="background-color: {{ $imageBgColor }};">
                                <img src="{{ $imageUrl }}" alt="{{ e($title) }}" class="w-full h-full object-cover object-center" loading="lazy">
                            </div>
                            @endif
                            <div class="p-5 md:p-6 flex-1 flex flex-col {{ $textColor === '' ? 'text-gray-900 dark:text-gray-100' : '' }}" @if($textColor !== '') style="color: {{ e($textColor) }};" @endif>
                                <h3 class="text-lg font-semibold mb-4 {{ $titleFontClass }} {{ $titleAlignClass }}" style="{{ $titleStyle }}">{{ e($title) }}</h3>
                                <div class="flex items-baseline gap-4 text-left">
                                    <span class="opacity-80 flex-1 text-left" style="{{ $labelStyle }}">Reinigingskosten</span>
                                    <span class="font-semibold text-lg block text-left ml-auto" style="width: {{ $valueColumnWidth }}; min-width: {{ $valueColumnWidth }}; max-width: {{ $valueColumnWidth }}; {{ $valueStyle }}">
                                        @if($overigeKostenPrice !== null)€ {{ number_format($overigeKostenPrice, 2, ',', '.') }}@else<span class="opacity-80">—</span>@endif
                                    </span>
                                </div>
                            </div>
                        </div>
                        </div>
                        @elseif($rate)
                        <div class="{{ $wrapperClass }}" style="{{ $wrapperStyle }}" data-taxiroyaal-card-wrapper data-card-size="{{ $cardSize }}">
                        <div class="rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 hover:border-blue-500/50 transition-colors shadow-lg w-full h-full flex flex-col {{ $textAlignClass }}" data-taxiroyaal-card data-card-size="{{ $cardSize }}">
                            @if($imageUrl)
                            <div class="aspect-[16/10] w-full overflow-hidden shrink-0" style="background-color: {{ $imageBgColor }};">
                                <img src="{{ $imageUrl }}" alt="{{ e($title) }}" class="w-full h-full object-cover object-center" loading="lazy">
                            </div>
                            @endif
                            <div class="p-5 md:p-6 flex-1 flex flex-col {{ $textColor === '' ? 'text-gray-900 dark:text-gray-100' : '' }}" @if($textColor !== '') style="color: {{ e($textColor) }};" @endif>
                                <h3 class="text-lg font-semibold mb-4 {{ $titleFontClass }} {{ $titleAlignClass }}" style="{{ $titleStyle }}">{{ e($title) }}</h3>
                                <ul class="space-y-3 text-sm">
                                    @if($v = $formatPrice($rate->base_fare))
                                    <li class="flex items-baseline gap-4 text-left"><span class="opacity-80 flex-1 text-left" style="{{ $labelStyle }}">Instaptarief</span><span class="font-semibold block text-left ml-auto" style="width: {{ $valueColumnWidth }}; min-width: {{ $valueColumnWidth }}; max-width: {{ $valueColumnWidth }}; {{ $valueStyle }}">{{ $v }}</span></li>
                                    @endif
                                    @if($v = $formatPrice($rate->price_per_km, '/km'))
                                    <li class="flex items-baseline gap-4 text-left"><span class="opacity-80 flex-1 text-left" style="{{ $labelStyle }}">Prijs per km</span><span class="font-semibold block text-left ml-auto" style="width: {{ $valueColumnWidth }}; min-width: {{ $valueColumnWidth }}; max-width: {{ $valueColumnWidth }}; {{ $valueStyle }}">{{ $v }}</span></li>
                                    @endif
                                    @if($v = $formatPrice($rate->price_per_min, '/min'))
                                    <li class="flex items-baseline gap-4 text-left"><span class="opacity-80 flex-1 text-left" style="{{ $labelStyle }}">Prijs per minuut</span><span class="font-semibold block text-left ml-auto" style="width: {{ $valueColumnWidth }}; min-width: {{ $valueColumnWidth }}; max-width: {{ $valueColumnWidth }}; {{ $valueStyle }}">{{ $v }}</span></li>
                                    @endif
                                    @if($v = $formatPrice($rate->min_fare, '/u'))
                                    <li class="flex items-baseline gap-4 text-left"><span class="opacity-80 flex-1 text-left" style="{{ $labelStyle }}">Wachttarief vooraf p/u</span><span class="font-semibold block text-left ml-auto" style="width: {{ $valueColumnWidth }}; min-width: {{ $valueColumnWidth }}; max-width: {{ $valueColumnWidth }}; {{ $valueStyle }}">{{ $v }}</span></li>
                                    @endif
                                </ul>
                                @php
                                    $itemCleaning = isset($item['cleaning_costs']) && $item['cleaning_costs'] !== null && (float)$item['cleaning_costs'] > 0 ? (float)$item['cleaning_costs'] : null;
                                @endphp
                                @if($itemCleaning !== null)
                                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600/50">
                                    <div class="flex items-baseline gap-4 text-left">
                                        <span class="opacity-80 flex-1 text-left" style="{{ $labelStyle }}">Overige kosten</span>
                                        <span class="font-semibold block text-left ml-auto" style="width: {{ $valueColumnWidth }}; min-width: {{ $valueColumnWidth }}; max-width: {{ $valueColumnWidth }}; {{ $valueStyle }}">€ {{ number_format($itemCleaning, 2, ',', '.') }}</span>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
            <script>
            (function() {
                function syncTaxiroyaalCardHeights(root) {
                    var cards = Array.prototype.slice.call(root.querySelectorAll('[data-taxiroyaal-card]'));
                    cards.forEach(function(card) { card.style.minHeight = ''; });
                    var groups = {};
                    cards.forEach(function(card) {
                        var size = card.getAttribute('data-card-size') || 'normal';
                        if (size === 'max' || size === 'total_width') return;
                        if (!groups[size]) groups[size] = [];
                        groups[size].push(card);
                    });
                    Object.keys(groups).forEach(function(size) {
                        var maxHeight = 0;
                        groups[size].forEach(function(card) {
                            maxHeight = Math.max(maxHeight, card.offsetHeight || 0);
                        });
                        groups[size].forEach(function(card) {
                            card.style.minHeight = maxHeight + 'px';
                        });
                    });
                }

                function syncTaxiroyaalTotalWidth(root) {
                    var wrappers = Array.prototype.slice.call(root.querySelectorAll('[data-taxiroyaal-card-wrapper]'));
                    wrappers.forEach(function(w) {
                        if ((w.getAttribute('data-card-size') || '') === 'total_width') {
                            w.style.width = '100%';
                            w.style.maxWidth = '100%';
                        }
                    });

                    wrappers.forEach(function(wrapper) {
                        if ((wrapper.getAttribute('data-card-size') || '') !== 'total_width') return;

                        var currentTop = wrapper.offsetTop;
                        var previous = wrappers.filter(function(w) { return w.offsetTop < currentTop; });
                        if (!previous.length) return;

                        var prevRowTop = Math.max.apply(null, previous.map(function(w) { return w.offsetTop; }));
                        var prevRowItems = previous.filter(function(w) {
                            return Math.abs(w.offsetTop - prevRowTop) <= 2;
                        });
                        if (!prevRowItems.length) return;

                        var minLeft = Math.min.apply(null, prevRowItems.map(function(w) { return w.offsetLeft; }));
                        var maxRight = Math.max.apply(null, prevRowItems.map(function(w) { return w.offsetLeft + w.offsetWidth; }));
                        var rowWidth = maxRight - minLeft;
                        if (rowWidth > 0) {
                            wrapper.style.width = rowWidth + 'px';
                            wrapper.style.maxWidth = rowWidth + 'px';
                        }
                    });
                }

                function run() {
                    var roots = document.querySelectorAll('[data-taxiroyaal-pricing]');
                    roots.forEach(function(root) {
                        syncTaxiroyaalTotalWidth(root);
                        syncTaxiroyaalCardHeights(root);
                    });
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', run);
                } else {
                    run();
                }
                window.addEventListener('load', run);
                window.addEventListener('resize', run);
            })();
            </script>
        @else
            @include('frontend.website.partials.taxiroyaal-pricing-cards', [
                'rates_1_4' => $ratesData['rates_1_4'],
                'rates_5_8' => $ratesData['rates_5_8'],
                'cleaning_costs' => $ratesData['cleaning_costs'],
                'block' => null,
            ])
        @endif
    @else
        <p class="text-muted-foreground">Er zijn nog geen tarieven ingesteld. Configureer ze in het adminpanel onder Taxi Royaal → Tarieven.</p>
    @endif
</section>
