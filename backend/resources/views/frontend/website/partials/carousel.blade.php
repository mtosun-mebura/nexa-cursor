@php
    $items = $items ?? [];
    $items = is_array($items) ? $items : [];
    $items = collect($items)->sortKeys(SORT_NUMERIC)->values()->all();
    $carouselId = 'carousel-' . (isset($carouselId) ? $carouselId : str_replace(['.', ' '], '', uniqid('', true)));
    $intervalSeconds = isset($intervalSeconds) ? (int) $intervalSeconds : 5;
    $intervalSeconds = max(0, min(120, $intervalSeconds));
    $allowedCaptionAnimations = ['rise', 'fade', 'slide_left', 'zoom', 'blur'];
    $captionPositionClasses = [
        'top' => 'carousel-caption-pos-top top-0 pt-6 pb-4 sm:pt-10 sm:pb-6 md:pt-16 md:pb-10',
        'center' => 'carousel-caption-pos-center inset-0 flex items-center justify-center px-4 py-6 sm:px-6 sm:py-10 md:px-12 md:py-12',
        'bottom' => 'carousel-caption-pos-bottom bottom-0 pb-8 pt-12 sm:pb-12 sm:pt-16 md:pb-20 md:pt-28',
    ];
    $carouselCaptionBgRgba = function (string $hex, mixed $opacityPercent = null): string {
        $hex = trim($hex);
        $hasCustomHex = $hex !== '' && preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $hex);
        $defaultAlpha = $hasCustomHex ? 0.88 : 0.78;
        if ($opacityPercent !== null && $opacityPercent !== '') {
            $alpha = max(0, min(100, (int) $opacityPercent)) / 100;
        } else {
            $alpha = $defaultAlpha;
        }
        if (! $hasCustomHex) {
            return 'rgba(0, 0, 0, '.$alpha.')';
        }
        if (strlen($hex) === 4) {
            $hex = '#'.$hex[1].$hex[1].$hex[2].$hex[2].$hex[3].$hex[3];
        }
        $r = hexdec(substr($hex, 1, 2));
        $g = hexdec(substr($hex, 3, 2));
        $b = hexdec(substr($hex, 5, 2));

        return 'rgba('.$r.', '.$g.', '.$b.', '.$alpha.')';
    };
@endphp
@if(count($items) > 0)
<style>
    #{{ $carouselId }} .carousel-inner-fill {
        width: 100%;
        height: auto;
        aspect-ratio: 4 / 3;
        min-height: 11rem;
        max-height: min(42vh, 22rem);
    }
    /* Mobiel: toon de afbeelding volledig (geen bijgesneden zijkanten); hoogte volgt de actieve slide. */
    @media (max-width: 639px) {
        #{{ $carouselId }} .carousel-inner-fill {
            aspect-ratio: auto !important;
            height: auto !important;
            min-height: 0 !important;
            max-height: none !important;
        }
        #{{ $carouselId }} [data-carousel-item] {
            position: absolute !important;
        }
        #{{ $carouselId }} [data-carousel-item="active"] {
            position: relative !important;
        }
        #{{ $carouselId }} [data-carousel-item] > img {
            object-fit: contain !important;
        }
        #{{ $carouselId }} [data-carousel-item="active"] > img {
            position: relative !important;
            height: auto !important;
        }
    }
    @media (min-width: 640px) {
        #{{ $carouselId }} .carousel-inner-fill {
            aspect-ratio: 16 / 9;
            min-height: 14rem;
            max-height: min(48vh, 28rem);
        }
    }
    @media (min-width: 1024px) {
        #{{ $carouselId }} .carousel-inner-fill {
            aspect-ratio: 21 / 9;
            min-height: 18rem;
            max-height: min(56vh, 36rem);
        }
    }
    #{{ $carouselId }} .carousel-caption-text-block {
        max-width: min(36rem, 92vw);
    }
    #{{ $carouselId }} .carousel-caption-text-block p {
        line-height: 1.35;
    }
    #{{ $carouselId }} [data-carousel-item] {
        transition: opacity 0.7s ease-in-out;
        will-change: opacity;
    }
    #{{ $carouselId }} .carousel-caption-pos-top,
    #{{ $carouselId }} .carousel-caption-pos-bottom {
        left: 0;
        right: 0;
    }
    #{{ $carouselId }} .carousel-caption-word {
        transition: opacity var(--caption-anim-duration, 550ms) ease-out, transform var(--caption-anim-duration, 550ms) ease-out, filter var(--caption-anim-duration, 550ms) ease-out;
    }
    #{{ $carouselId }} .carousel-anim-rise .carousel-caption-word {
        opacity: 0;
        transform: translateY(1.35rem);
    }
    #{{ $carouselId }} .carousel-anim-fade .carousel-caption-word {
        opacity: 0;
        transform: none;
    }
    #{{ $carouselId }} .carousel-anim-slide_left .carousel-caption-word {
        opacity: 0;
        transform: translateX(-1.35rem);
    }
    #{{ $carouselId }} .carousel-anim-zoom .carousel-caption-word {
        opacity: 0;
        transform: scale(0.88);
    }
    #{{ $carouselId }} .carousel-anim-blur .carousel-caption-word {
        opacity: 0;
        filter: blur(8px);
        transform: none;
    }
    #{{ $carouselId }} .carousel-anim-rise.is-visible .carousel-caption-word,
    #{{ $carouselId }} .carousel-anim-fade.is-visible .carousel-caption-word,
    #{{ $carouselId }} .carousel-anim-slide_left.is-visible .carousel-caption-word,
    #{{ $carouselId }} .carousel-anim-zoom.is-visible .carousel-caption-word,
    #{{ $carouselId }} .carousel-anim-blur.is-visible .carousel-caption-word {
        opacity: 1;
        transform: translateY(0) translateX(0) scale(1);
        filter: blur(0);
    }
    @media (prefers-reduced-motion: reduce) {
        #{{ $carouselId }} .carousel-caption-word {
            opacity: 1;
            transform: none;
            filter: none;
            transition: none;
        }
    }
</style>
<div id="{{ $carouselId }}" class="relative w-full overflow-visible" data-carousel="slide" data-carousel-interval="{{ $intervalSeconds }}">
    <div class="carousel-inner-fill relative z-0 w-full overflow-hidden bg-gray-200 dark:bg-gray-700">
        @foreach($items as $index => $item)
            @php
                $uuid = $item['uuid'] ?? $item['UUID'] ?? '';
                $alt = $item['alt'] ?? '';
                $imgSrc = $uuid ? url('/website-media/' . $uuid) : '';
                $textColor = trim((string) ($item['text_color'] ?? ''));
                $captionColor = ($textColor !== '' && preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $textColor)) ? $textColor : '#ffffff';
                $textBgColor = trim((string) ($item['text_bg_color'] ?? ''));
                $textBgOpacity = $item['text_bg_opacity'] ?? null;
                $captionBgColor = $carouselCaptionBgRgba($textBgColor, $textBgOpacity);
                $textSizePx = isset($item['text_size_px']) ? (int) $item['text_size_px'] : 24;
                $textSizePx = max(12, min(50, $textSizePx));
                $textSizePx = (int) (round($textSizePx / 2) * 2);
                $textPosition = $item['text_position'] ?? 'bottom';
                $textPosition = in_array($textPosition, ['top', 'center', 'bottom'], true) ? $textPosition : 'bottom';
                $textAnimation = $item['text_animation'] ?? 'rise';
                $textAnimation = in_array($textAnimation, $allowedCaptionAnimations, true) ? $textAnimation : 'rise';
                $positionClass = $captionPositionClasses[$textPosition];
                $captionWords = $alt !== '' ? preg_split('/\s+/u', trim($alt), -1, PREG_SPLIT_NO_EMPTY) : [];
                $animDurationMs = isset($item['text_animation_duration_ms']) ? (int) $item['text_animation_duration_ms'] : 550;
                $animDurationMs = max(200, min(5000, $animDurationMs));
                $animStaggerMs = isset($item['text_animation_stagger_ms']) ? (int) $item['text_animation_stagger_ms'] : 90;
                $animStaggerMs = max(0, min(1000, $animStaggerMs));
            @endphp
            @if($imgSrc)
            <div class="absolute top-0 left-0 right-0 bottom-0 w-full h-full {{ $index === 0 ? 'opacity-100 z-10' : 'opacity-0 z-0 pointer-events-none' }}" data-carousel-item="{{ $index === 0 ? 'active' : '' }}" style="inset: 0;">
                <img src="{{ $imgSrc }}" alt="{{ $alt }}" loading="{{ $index === 0 ? 'eager' : 'lazy' }}" decoding="async" referrerpolicy="no-referrer" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; display: block;">
                @if(count($captionWords) > 0)
                <div class="carousel-slide-caption pointer-events-none absolute z-20 px-6 md:px-12 {{ $positionClass }} carousel-anim-{{ $textAnimation }}" data-carousel-caption data-carousel-animation="{{ $textAnimation }}" style="--caption-anim-duration: {{ $animDurationMs }}ms; --caption-anim-stagger: {{ $animStaggerMs }}ms;">
                    <div class="carousel-caption-text-block inline-block rounded-lg px-3 py-2 sm:px-4 sm:py-3 md:px-6 md:py-4 shadow-md" style="background-color: {{ $captionBgColor }};">
                        <p class="font-medium leading-relaxed mx-auto text-center" style="color: {{ $captionColor }}; --caption-size-max: {{ $textSizePx }}px; font-size: clamp(0.8125rem, 2.5vw + 0.4rem, var(--caption-size-max));">
                            @foreach($captionWords as $wordIndex => $word)
                                <span class="carousel-caption-word inline-block" style="transition-delay: {{ (int) $wordIndex * $animStaggerMs }}ms">{{ $word }}</span>@if(!$loop->last)<span class="inline-block" aria-hidden="true">&nbsp;</span>@endif
                            @endforeach
                        </p>
                    </div>
                </div>
                @endif
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
    <div class="absolute z-40 flex flex-row items-center justify-center -translate-x-1/2 bottom-3 sm:bottom-5 left-1/2" style="gap: 0.5rem;">
        @foreach($itemsWithUuid as $slideIndex => $entry)
            <button type="button" class="carousel-indicator rounded-full transition-colors flex-shrink-0" style="width: 10px; height: 10px; min-width: 10px; min-height: 10px; background: {{ $slideIndex === 0 ? '#ffffff' : '#9ca3af' }};" aria-current="{{ $slideIndex === 0 ? 'true' : 'false' }}" aria-label="Slide {{ $slideIndex + 1 }}" data-carousel-slide-to="{{ $slideIndex }}"></button>
        @endforeach
    </div>
    <button type="button" class="absolute top-0 start-0 z-30 flex items-center justify-center h-full px-2 sm:px-4 cursor-pointer group focus:outline-none" data-carousel-prev>
        <span class="inline-flex items-center justify-center w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-white/30 group-hover:bg-white/50 focus:ring-4 focus:ring-white/70 focus:outline-none transition-colors">
            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/></svg>
            <span class="sr-only">Previous</span>
        </span>
    </button>
    <button type="button" class="absolute top-0 end-0 z-30 flex items-center justify-center h-full px-2 sm:px-4 cursor-pointer group focus:outline-none" data-carousel-next>
        <span class="inline-flex items-center justify-center w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-white/30 group-hover:bg-white/50 focus:ring-4 focus:ring-white/70 focus:outline-none transition-colors">
            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/></svg>
            <span class="sr-only">Next</span>
        </span>
    </button>
    @endif
</div>
@endif
