<section class="container-custom pt-12 md:pt-16" aria-labelledby="taxiroyaal-tarieven-heading">
    @php
        // Wanneer alleen homeSections + sectionKey worden doorgegeven (frontend home), data zelf ophalen
        if (isset($sectionKey) && isset($homeSections) && is_array($homeSections)) {
            $sectionData = $homeSections[$sectionKey] ?? [];
            $ratesData = $ratesData ?? app(\App\Services\TaxiRoyaalPublicRatesService::class)->getRatesForDisplay();
            $sectionItems = isset($sectionData['items']) && is_array($sectionData['items']) ? $sectionData['items'] : [];
            $hasRates = $ratesData && ($ratesData['rates_1_4'] || $ratesData['rates_5_8']);
            $hasSectionItemsWithContent = !empty($sectionItems);
            $sectionTitle = $sectionData['title'] ?? 'Tarieven';
            $sectionTitleAlignClass = 'text-' . (in_array($sectionData['title_align'] ?? 'left', ['left', 'center', 'right'], true) ? ($sectionData['title_align']) : 'left');
            $sectionTitleStyle = '';
            if (!empty($sectionData['title_font_size'])) {
                $sectionTitleStyle .= ' font-size: ' . e($sectionData['title_font_size']) . ';';
            }
            if (!empty($sectionData['title_font_style']) && $sectionData['title_font_style'] !== 'normal') {
                $sectionTitleStyle .= ' font-weight: ' . ($sectionData['title_font_style'] === 'bold' ? '700' : '400') . '; font-style: ' . ($sectionData['title_font_style'] === 'italic' ? 'italic' : 'normal') . ';';
            }
            $vehicleDisplayService = $vehicleDisplayService ?? app(\App\Services\TaxiRoyaalVehicleDisplayService::class);
            $vehiclesForSelect = $vehicleDisplayService->getVehiclesForSelect();
            $fallbackVehicleImages = $fallbackVehicleImages ?? collect(array_filter(array_map(function ($v) use ($vehicleDisplayService) {
                return isset($v['id']) ? $vehicleDisplayService->getImageUrl((int) $v['id']) : null;
            }, $vehiclesForSelect)));
            $cardWidthPx = $cardWidthPx ?? ['small' => 320, 'normal' => 600, 'large' => 800, 'max' => 9999, 'total_width' => 9999];
            $allowedFontSizes = $allowedFontSizes ?? array_merge([''], array_map(fn ($px) => $px . 'px', range(10, 40, 2)));
            $valueColumnWidth = $valueColumnWidth ?? '6rem';
            $allowedFontStyles = $allowedFontStyles ?? ['normal', 'bold', 'italic'];
            $allowedTextAligns = $allowedTextAligns ?? ['left', 'center', 'right'];
            $priceAnimation = isset($sectionData['price_animation']) ? (bool) $sectionData['price_animation'] : true;
            $imageFadeDuration = isset($sectionData['image_fade_duration']) ? max(300, min(5000, (int) $sectionData['image_fade_duration'])) : 1200;
        }
        $sectionTitleAlignClass = $sectionTitleAlignClass ?? 'text-left';
        $priceAnimation = $priceAnimation ?? true;
        $imageFadeDuration = $imageFadeDuration ?? 1200;
        $sectionTitleStyle = $sectionTitleStyle ?? '';
        $sectionTitle = $sectionTitle ?? 'Tarieven';
        $hasRates = $hasRates ?? false;
        $hasSectionItemsWithContent = $hasSectionItemsWithContent ?? false;
        $sectionItems = $sectionItems ?? [];
        $ratesData = $ratesData ?? ['rates_1_4' => null, 'rates_5_8' => null, 'cleaning_costs' => null];
        $vehicleDisplayService = $vehicleDisplayService ?? app(\App\Services\TaxiRoyaalVehicleDisplayService::class);
        $fallbackVehicleImages = $fallbackVehicleImages ?? collect();
        $cardWidthPx = $cardWidthPx ?? ['small' => 320, 'normal' => 600, 'large' => 800, 'max' => 9999, 'total_width' => 9999];
        $allowedFontSizes = $allowedFontSizes ?? array_merge([''], array_map(fn ($px) => $px . 'px', range(10, 40, 2)));
        $valueColumnWidth = $valueColumnWidth ?? '6rem';
        $allowedFontStyles = $allowedFontStyles ?? ['normal', 'bold', 'italic'];
        $allowedTextAligns = $allowedTextAligns ?? ['left', 'center', 'right'];
    @endphp
    <style>.taxiroyaal-card-image-wrap.image-loaded .taxiroyaal-card-image-loader{opacity:0;pointer-events:none;}</style>
    <h2 id="taxiroyaal-tarieven-heading" class="text-2xl md:text-3xl text-gray-900 dark:text-white mb-8 md:mb-10 {{ $sectionTitleAlignClass }}" style="{{ $sectionTitleStyle }}">{{ e($sectionTitle) }}</h2>
    @if($hasRates || $hasSectionItemsWithContent)
        @if(!empty($sectionItems))
            {{-- Per-card configuratie: één kaart per sectie-item met gekozen tarief (1-4 of 5-8), afbeelding en kaartopties --}}
            <div class="taxiroyaal-pricing my-10 md:my-14" data-taxiroyaal-pricing data-price-animation="{{ $priceAnimation ? '1' : '0' }}">
                <div class="flex flex-wrap justify-center items-stretch gap-6 lg:gap-8 mb-6 lg:mb-8">
                    @foreach($sectionItems as $itemIndex => $item)
                        @php
                            $rateType = $item['rate_type'] ?? '1-4';
                            $title = $item['title'] ?? ($rateType === '5-8' ? '5 t/m 8 personen' : ($rateType === 'overige_kosten' ? 'Overige kosten' : 't/m 4 personen'));
                            $isOverigeKosten = $rateType === 'overige_kosten';
                            $rate = $isOverigeKosten ? null : ($rateType === '5-8' ? $ratesData['rates_5_8'] : $ratesData['rates_1_4']);
                            $overigeKostenPrice = $isOverigeKosten ? (isset($item['cleaning_costs']) && $item['cleaning_costs'] !== '' && is_numeric($item['cleaning_costs']) ? (float)$item['cleaning_costs'] : (isset($ratesData['cleaning_costs']) && $ratesData['cleaning_costs'] !== null ? (float)$ratesData['cleaning_costs'] : null)) : null;
                            $imageUrl = !empty($item['image_url']) ? (function($u) {
                                $u = trim((string) $u);
                                if (str_starts_with($u, 'http://') || str_starts_with($u, 'https://')) {
                                    if (preg_match('#^https?://[^/]+/storage/(.+)$#', $u, $m)) {
                                        $path = preg_replace('/[#?].*$/', '', $m[1]);
                                        return app(\App\Services\WebsiteBuilderService::class)->publicFileUrl($path);
                                    }
                                    return $u;
                                }
                                if (str_starts_with($u, '/storage/')) {
                                    $path = preg_replace('#^/storage/#', '', $u);
                                    return app(\App\Services\WebsiteBuilderService::class)->publicFileUrl($path);
                                }
                                return asset(ltrim($u, '/'));
                            })($item['image_url']) : (!empty($item['vehicle_id']) ? $vehicleDisplayService->getImageUrl((int)$item['vehicle_id']) : null);
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
                        <div class="rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 transition-all duration-700 ease-out shadow-lg w-full h-full flex flex-col opacity-0 translate-y-4 {{ $textAlignClass }} transition-transform duration-200 hover:-translate-y-2" data-taxiroyaal-card data-card-size="{{ $cardSize }}" data-card-index="{{ $itemIndex }}">
                        @if($imageUrl)
                            <div class="taxiroyaal-card-image-wrap aspect-[16/10] w-full overflow-hidden shrink-0 relative" style="background-color: {{ $imageBgColor }};">
                                <div class="taxiroyaal-card-image-loader absolute inset-0 flex items-center justify-center transition-opacity duration-300 bg-gray-100/90 dark:bg-gray-800/90" aria-hidden="true">
                                    <svg class="animate-spin h-10 w-10 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                </div>
                                <img src="{{ $imageUrl }}" alt="{{ e($title) }}" class="taxiroyaal-card-image w-full h-full object-cover object-center opacity-0 transition-opacity ease-out" style="transition-duration: {{ $imageFadeDuration }}ms;" loading="lazy" onload="this.closest('.taxiroyaal-card-image-wrap').classList.add('image-loaded')" onerror="this.closest('.taxiroyaal-card-image-wrap').classList.add('image-loaded')">
                            </div>
                            @endif
                            <div class="p-5 md:p-6 flex-1 flex flex-col {{ $textColor === '' ? 'text-gray-900 dark:text-gray-100' : '' }}" @if($textColor !== '') style="color: {{ e($textColor) }};" @endif>
                                <h3 class="text-lg font-semibold mb-4 {{ $titleFontClass }} {{ $titleAlignClass }}" style="{{ $titleStyle }}">{{ e($title) }}</h3>
                                <div class="flex items-baseline gap-4 text-left">
                                    <span class="opacity-80 flex-1 text-left" style="{{ $labelStyle }}">Reinigingskosten</span>
                                    @if($overigeKostenPrice !== null)
                                    <span class="font-semibold text-lg block text-left ml-auto price-count" style="width: {{ $valueColumnWidth }}; min-width: {{ $valueColumnWidth }}; max-width: {{ $valueColumnWidth }}; {{ $valueStyle }}" data-price-end="{{ $overigeKostenPrice }}" data-price-prefix="€ " data-price-suffix="">0,00</span>
                                    @else
                                    <span class="font-semibold text-lg block text-left ml-auto opacity-80" style="width: {{ $valueColumnWidth }}; min-width: {{ $valueColumnWidth }}; max-width: {{ $valueColumnWidth }}; {{ $valueStyle }}">—</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        </div>
                            @elseif($rate)
                            <div class="{{ $wrapperClass }}" style="{{ $wrapperStyle }}" data-taxiroyaal-card-wrapper data-card-size="{{ $cardSize }}">
                            <div class="rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 transition-all duration-700 ease-out shadow-lg w-full h-full flex flex-col opacity-0 translate-y-4 {{ $textAlignClass }} transition-transform duration-200 hover:-translate-y-2" data-taxiroyaal-card data-card-size="{{ $cardSize }}" data-card-index="{{ $itemIndex }}">
                            @if($imageUrl)
                            <div class="taxiroyaal-card-image-wrap aspect-[16/10] w-full overflow-hidden shrink-0 relative" style="background-color: {{ $imageBgColor }};">
                                <div class="taxiroyaal-card-image-loader absolute inset-0 flex items-center justify-center transition-opacity duration-300 bg-gray-100/90 dark:bg-gray-800/90" aria-hidden="true">
                                    <svg class="animate-spin h-10 w-10 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                </div>
                                <img src="{{ $imageUrl }}" alt="{{ e($title) }}" class="taxiroyaal-card-image w-full h-full object-cover object-center opacity-0 transition-opacity ease-out" style="transition-duration: {{ $imageFadeDuration }}ms;" loading="lazy" onload="this.closest('.taxiroyaal-card-image-wrap').classList.add('image-loaded')" onerror="this.closest('.taxiroyaal-card-image-wrap').classList.add('image-loaded')">
                            </div>
                            @endif
                            <div class="p-5 md:p-6 flex-1 flex flex-col {{ $textColor === '' ? 'text-gray-900 dark:text-gray-100' : '' }}" @if($textColor !== '') style="color: {{ e($textColor) }};" @endif>
                                <h3 class="text-lg font-semibold mb-4 {{ $titleFontClass }} {{ $titleAlignClass }}" style="{{ $titleStyle }}">{{ e($title) }}</h3>
                                <ul class="space-y-3 text-sm">
                                    @if($rate->base_fare !== null)
                                    <li class="flex items-baseline gap-4 text-left"><span class="opacity-80 flex-1 text-left" style="{{ $labelStyle }}">Instaptarief</span><span class="font-semibold block text-left ml-auto price-count" style="width: {{ $valueColumnWidth }}; min-width: {{ $valueColumnWidth }}; max-width: {{ $valueColumnWidth }}; {{ $valueStyle }}" data-price-end="{{ (float) $rate->base_fare }}" data-price-prefix="€ " data-price-suffix="">0,00</span></li>
                                    @endif
                                    @if($rate->price_per_km !== null)
                                    <li class="flex items-baseline gap-4 text-left"><span class="opacity-80 flex-1 text-left" style="{{ $labelStyle }}">Prijs per km</span><span class="font-semibold block text-left ml-auto price-count" style="width: {{ $valueColumnWidth }}; min-width: {{ $valueColumnWidth }}; max-width: {{ $valueColumnWidth }}; {{ $valueStyle }}" data-price-end="{{ (float) $rate->price_per_km }}" data-price-prefix="€ " data-price-suffix=" /km">0,00</span></li>
                                    @endif
                                    @if($rate->price_per_min !== null)
                                    <li class="flex items-baseline gap-4 text-left"><span class="opacity-80 flex-1 text-left" style="{{ $labelStyle }}">Prijs per minuut</span><span class="font-semibold block text-left ml-auto price-count" style="width: {{ $valueColumnWidth }}; min-width: {{ $valueColumnWidth }}; max-width: {{ $valueColumnWidth }}; {{ $valueStyle }}" data-price-end="{{ (float) $rate->price_per_min }}" data-price-prefix="€ " data-price-suffix=" /min">0,00</span></li>
                                    @endif
                                    @if($rate->min_fare !== null)
                                    <li class="flex items-baseline gap-4 text-left"><span class="opacity-80 flex-1 text-left" style="{{ $labelStyle }}">Wachttarief vooraf p/u</span><span class="font-semibold block text-left ml-auto price-count" style="width: {{ $valueColumnWidth }}; min-width: {{ $valueColumnWidth }}; max-width: {{ $valueColumnWidth }}; {{ $valueStyle }}" data-price-end="{{ (float) $rate->min_fare }}" data-price-prefix="€ " data-price-suffix=" /u">0,00</span></li>
                                    @endif
                                </ul>
                                @php
                                    $itemCleaning = isset($item['cleaning_costs']) && $item['cleaning_costs'] !== null && (float)$item['cleaning_costs'] > 0 ? (float)$item['cleaning_costs'] : null;
                                @endphp
                                @if($itemCleaning !== null)
                                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600/50">
                                    <div class="flex items-baseline gap-4 text-left">
                                        <span class="opacity-80 flex-1 text-left" style="{{ $labelStyle }}">Overige kosten</span>
                                        <span class="font-semibold block text-left ml-auto price-count" style="width: {{ $valueColumnWidth }}; min-width: {{ $valueColumnWidth }}; max-width: {{ $valueColumnWidth }}; {{ $valueStyle }}" data-price-end="{{ $itemCleaning }}" data-price-prefix="€ " data-price-suffix="">0,00</span>
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

                function animatePrice(el) {
                    var endStr = el.getAttribute('data-price-end');
                    if (endStr === null || endStr === '') return;
                    var end = parseFloat(endStr, 10);
                    if (isNaN(end)) return;
                    var prefix = el.getAttribute('data-price-prefix') || '€ ';
                    var suffix = el.getAttribute('data-price-suffix') || '';
                    var duration = 2200;
                    var start = performance.now();
                    function tick(now) {
                        var t = Math.min(1, (now - start) / duration);
                        var eased = 1 - Math.pow(1 - t, 3);
                        var current = end * eased;
                        el.textContent = prefix + current.toFixed(2).replace('.', ',') + suffix;
                        if (t < 1) requestAnimationFrame(tick);
                    }
                    requestAnimationFrame(tick);
                }

                function run() {
                    var roots = document.querySelectorAll('[data-taxiroyaal-pricing]');
                    roots.forEach(function(root) {
                        syncTaxiroyaalTotalWidth(root);
                        syncTaxiroyaalCardHeights(root);
                    });
                }

                /* Staggered fade-in + price count-up when cards enter viewport */
                function initCardAnimations() {
                    var roots = document.querySelectorAll('[data-taxiroyaal-pricing]');
                    roots.forEach(function(root) {
                        var cards = root.querySelectorAll('[data-taxiroyaal-card]');
                        if (!cards.length) return;
                        var io = new IntersectionObserver(function(entries) {
                            entries.forEach(function(entry) {
                                if (!entry.isIntersecting) return;
                                var card = entry.target;
                                var idx = parseInt(card.getAttribute('data-card-index'), 10) || 0;
                                io.unobserve(card);
                                setTimeout(function() {
                                    card.classList.remove('opacity-0', 'translate-y-4');
                                    card.classList.add('opacity-100', 'translate-y-0');
                                    card.querySelectorAll('.taxiroyaal-card-image').forEach(function(img) { img.classList.add('opacity-100'); });
                                    var animate = root.getAttribute('data-price-animation') !== '0';
                                    card.querySelectorAll('.price-count').forEach(function(el) {
                                        if (!el.getAttribute('data-price-end') || el.getAttribute('data-price-end') === '') return;
                                        if (animate) {
                                            animatePrice(el);
                                        } else {
                                            var end = parseFloat(el.getAttribute('data-price-end'), 10);
                                            if (!isNaN(end)) {
                                                var prefix = el.getAttribute('data-price-prefix') || '€ ';
                                                var suffix = el.getAttribute('data-price-suffix') || '';
                                                el.textContent = prefix + end.toFixed(2).replace('.', ',') + suffix;
                                            }
                                        }
                                    });
                                }, idx * 200);
                            });
                        }, { threshold: 0.12 });
                        cards.forEach(function(card) { io.observe(card); });
                    });
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', function() { run(); initCardAnimations(); });
                } else {
                    run();
                    initCardAnimations();
                }
                window.addEventListener('load', run);
                window.addEventListener('resize', run);
            })();
            </script>
        @else
            @php
                $formatPrice = fn ($v, $suffix = '') => ($v !== null && $v !== '' && is_numeric($v)) ? ('€ ' . number_format((float) $v, 2, ',', '.') . $suffix) : null;
                $imgCar = $fallbackVehicleImages->get(0) ?? 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="400" height="250" viewBox="0 0 400 250"><rect fill="#e5e7eb" width="400" height="250"/></svg>');
                $imgVan = $fallbackVehicleImages->get(1) ?? $fallbackVehicleImages->get(0) ?? 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="400" height="250" viewBox="0 0 400 250"><rect fill="#e5e7eb" width="400" height="250"/></svg>');
            @endphp
            @include('frontend.website.partials.taxiroyaal-pricing-cards', [
                'rates_1_4' => $ratesData['rates_1_4'],
                'rates_5_8' => $ratesData['rates_5_8'],
                'cleaning_costs' => $ratesData['cleaning_costs'],
                'block' => null,
                'formatPrice' => $formatPrice,
                'imgCar' => $imgCar,
                'imgVan' => $imgVan,
                'image_fade_duration' => $imageFadeDuration ?? 1200,
            ])
        @endif
    @else
        <p class="text-muted-foreground">Er zijn nog geen tarieven ingesteld. Configureer ze in het adminpanel onder Taxi Royaal → Tarieven.</p>
    @endif
</section>
