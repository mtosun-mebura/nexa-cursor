<div class="taxiroyaal-pricing my-10 md:my-14" data-taxiroyaal-pricing>
    <style>.taxiroyaal-card-image-wrap.image-loaded .taxiroyaal-card-image-loader{opacity:0;pointer-events:none;}</style>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8 mb-6 lg:mb-8">
        {{-- t/m 4 personen --}}
        @if($rates_1_4)
        <div class="rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 transition-colors shadow-lg transition-transform duration-200 hover:-translate-y-2">
            <div class="taxiroyaal-card-image-wrap aspect-[16/10] w-full relative bg-gray-200 dark:bg-slate-700">
                <div class="taxiroyaal-card-image-loader absolute inset-0 flex items-center justify-center transition-opacity duration-300 bg-gray-100/90 dark:bg-gray-800/90" aria-hidden="true">
                    <svg class="animate-spin h-10 w-10 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </div>
                <img src="{{ $imgCar }}" alt="" class="taxiroyaal-card-image w-full h-full object-cover opacity-0 transition-opacity ease-out" style="transition-duration: {{ $image_fade_duration ?? 1200 }}ms;" loading="lazy" onload="this.closest('.taxiroyaal-card-image-wrap').classList.add('image-loaded')" onerror="this.closest('.taxiroyaal-card-image-wrap').classList.add('image-loaded')">
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
        <div class="rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 transition-colors shadow-lg transition-transform duration-200 hover:-translate-y-2">
            <div class="taxiroyaal-card-image-wrap aspect-[16/10] w-full relative bg-gray-200 dark:bg-slate-700">
                <div class="taxiroyaal-card-image-loader absolute inset-0 flex items-center justify-center transition-opacity duration-300 bg-gray-100/90 dark:bg-gray-800/90" aria-hidden="true">
                    <svg class="animate-spin h-10 w-10 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </div>
                <img src="{{ $imgVan }}" alt="" class="taxiroyaal-card-image w-full h-full object-cover opacity-0 transition-opacity ease-out" style="transition-duration: {{ $image_fade_duration ?? 1200 }}ms;" loading="lazy" onload="this.closest('.taxiroyaal-card-image-wrap').classList.add('image-loaded')" onerror="this.closest('.taxiroyaal-card-image-wrap').classList.add('image-loaded')">
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
    <div class="rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 transition-colors shadow-lg p-5 md:p-6 text-gray-900 dark:text-gray-100 transition-transform duration-200 hover:-translate-y-2">
        <h3 class="text-lg font-semibold mb-3">Overige kosten</h3>
        <div class="flex justify-between items-baseline">
            <span class="text-gray-600 dark:text-gray-300">Reinigingskosten</span>
            <span class="font-semibold text-lg">€ {{ number_format((float)$cleaning_costs, 2, ',', '.') }}</span>
        </div>
    </div>
    @endif
</div>
<script>
(function() {
    var root = document.querySelector('.taxiroyaal-pricing');
    if (!root) return;
    var imgs = root.querySelectorAll('.taxiroyaal-card-image');
    if (!imgs.length) return;
    var io = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('opacity-100');
            io.unobserve(entry.target);
        });
    }, { threshold: 0.12 });
    imgs.forEach(function(img) { io.observe(img); });
})();
</script>
