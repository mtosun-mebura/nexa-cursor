@php
    $items = $items ?? [];
    $items = is_array($items) ? $items : [];
    $items = collect($items)->sortKeys(SORT_NUMERIC)->values()->all();
    $carouselId = 'carousel-' . (isset($carouselId) ? $carouselId : str_replace(['.', ' '], '', uniqid('', true)));
@endphp
@if(count($items) > 0)
<style>.carousel-inner-fill { height: 22rem; min-height: 352px; } @media (min-width: 768px) { .carousel-inner-fill { height: 36rem; min-height: 576px; } }</style>
<div id="{{ $carouselId }}" class="relative w-full overflow-visible" data-carousel="slide">
    {{-- Slide-container expliciet z-0 zodat controls erboven liggen --}}
    <div class="carousel-inner-fill relative z-0 w-full overflow-hidden bg-gray-200 dark:bg-gray-700">
        @foreach($items as $index => $item)
            @php
                $uuid = $item['uuid'] ?? $item['UUID'] ?? '';
                $alt = $item['alt'] ?? '';
                $imgSrc = $uuid ? url('/website-media/' . $uuid) : '';
            @endphp
            @if($imgSrc)
            <div class="absolute top-0 left-0 right-0 bottom-0 w-full h-full duration-700 ease-in-out {{ $index === 0 ? 'opacity-100 z-10' : 'opacity-0 z-0 pointer-events-none' }}" data-carousel-item="{{ $index === 0 ? 'active' : '' }}" style="inset: 0;">
                <img src="{{ $imgSrc }}" alt="{{ $alt }}" loading="{{ $index === 0 ? 'eager' : 'lazy' }}" decoding="async" referrerpolicy="no-referrer" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; display: block;">
            </div>
            @endif
        @endforeach
    </div>
    @php
        $itemsWithUuid = [];
        foreach ($items as $idx => $item) {
            if (!empty($item['uuid'] ?? $item['UUID'] ?? '')) {
                $itemsWithUuid[] = ['index' => $idx, 'item' => $item];
            }
        }
    @endphp
    @if(count($itemsWithUuid) > 1)
    <div class="absolute z-40 flex flex-row items-center justify-center -translate-x-1/2 bottom-5 left-1/2" style="gap: 0.5rem;">
        @foreach($itemsWithUuid as $slideIndex => $entry)
            <button type="button" class="carousel-indicator rounded-full transition-colors flex-shrink-0" style="width: 10px; height: 10px; min-width: 10px; min-height: 10px; background: {{ $slideIndex === 0 ? '#ffffff' : '#9ca3af' }};" aria-current="{{ $slideIndex === 0 ? 'true' : 'false' }}" aria-label="Slide {{ $slideIndex + 1 }}" data-carousel-slide-to="{{ $slideIndex }}"></button>
        @endforeach
    </div>
    <button type="button" class="absolute top-0 start-0 z-30 flex items-center justify-center h-full px-4 cursor-pointer group focus:outline-none" data-carousel-prev>
        <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/30 group-hover:bg-white/50 focus:ring-4 focus:ring-white/70 focus:outline-none transition-colors">
            <svg class="w-5 h-5 text-white rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/></svg>
            <span class="sr-only">Previous</span>
        </span>
    </button>
    <button type="button" class="absolute top-0 end-0 z-30 flex items-center justify-center h-full px-4 cursor-pointer group focus:outline-none" data-carousel-next>
        <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/30 group-hover:bg-white/50 focus:ring-4 focus:ring-white/70 focus:outline-none transition-colors">
            <svg class="w-5 h-5 text-white rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/></svg>
            <span class="sr-only">Next</span>
        </span>
    </button>
    @endif
</div>
@endif
