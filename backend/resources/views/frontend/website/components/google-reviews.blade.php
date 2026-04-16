@php
    $data = $googleReviews ?? [];
    $reviews = isset($data['reviews']) && is_array($data['reviews'])
        ? $data['reviews']
        : (is_array($reviews ?? null) ? (isset(($reviews ?? [])['reviews']) ? ($reviews ?? [])['reviews'] : ($reviews ?? [])) : (is_array($data) && !isset($data['place_name']) ? $data : []));
    $reviews = is_array($reviews) ? $reviews : [];
    $placeName = $data['place_name'] ?? '';
    $placeRating = (float) ($data['rating'] ?? 0);
    $userRatingCount = (int) ($data['user_rating_count'] ?? 0);
    $writeReviewUrl = $data['write_review_url'] ?? '';
    $carouselId = 'google-reviews-carousel-' . bin2hex(random_bytes(4));
    $componentId = 'grw-' . bin2hex(random_bytes(3));
    $visibleCards = 3;
@endphp
@if(count($reviews) > 0)
<style>
#{{ $componentId }} .grw-slider-viewport { overflow: hidden; width: 100%; position: relative; z-index: 0; }
#{{ $componentId }} .grw-slider-track { flex-wrap: nowrap; display: flex; }
#{{ $componentId }} .grw-review-card { flex: 0 0 auto; box-sizing: border-box; }
#{{ $componentId }} .grw-btn-prev,
#{{ $componentId }} .grw-btn-next { pointer-events: auto; }
#{{ $componentId }} .grw-review-card { flex-shrink: 0; }
#{{ $componentId }} .grw-card { height: 220px; min-height: 220px; max-height: 220px; display: flex; flex-direction: column; overflow: hidden; }
#{{ $componentId }} .grw-card .grw-text { flex: 1; min-height: 0; display: flex; flex-direction: column; overflow: hidden; }
#{{ $componentId }} .grw-card .grw-text-short { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.4; }
#{{ $componentId }} .grw-card .grw-read-more { margin-top: auto; flex-shrink: 0; }
#{{ $componentId }} .grw-card .grw-text.grw-text-expanded { overflow-y: auto; }
/* Dot-navigatie: zichtbaar in light/dark; actieve dot wit in dark mode (html.dark = class-based theme) */
#{{ $componentId }} .grw-dot.grw-dot-active { background-color: #111827 !important; }
html.dark #{{ $componentId }} .grw-dot.grw-dot-active { background-color: #ffffff !important; }
#{{ $componentId }} .grw-dot:not(.grw-dot-active) { background-color: #9ca3af !important; }
#{{ $componentId }} .grw-dot:not(.grw-dot-active):hover { background-color: #6b7280 !important; }
html.dark #{{ $componentId }} .grw-dot:not(.grw-dot-active) { background-color: #6b7280 !important; }
html.dark #{{ $componentId }} .grw-dot:not(.grw-dot-active):hover { background-color: #9ca3af !important; }
/* Scroll-animaties: startstaten */
#{{ $componentId }}:not(.grw-in-view) .grw-summary { opacity: 0; transform: translateX(-48px); }
#{{ $componentId }}:not(.grw-in-view) .grw-slider-wrapper .grw-header h2 { opacity: 0; transform: translateZ(-12px); }
#{{ $componentId }}:not(.grw-in-view) .grw-slider-wrapper .grw-header p { opacity: 0; transform: translateZ(-12px); }
#{{ $componentId }}:not(.grw-in-view) .grw-review-card .grw-card { opacity: 0; transform: translateY(32px); }
#{{ $componentId }}:not(.grw-in-view) .grw-btn-prev,
#{{ $componentId }}:not(.grw-in-view) .grw-btn-next,
#{{ $componentId }}:not(.grw-in-view) .grw-dots { opacity: 0; transform: translateZ(-8px); }
/* Overgangen */
#{{ $componentId }} .grw-summary { transition: opacity 0.5s ease-out, transform 0.5s ease-out; }
#{{ $componentId }} .grw-slider-wrapper .grw-header h2 { transition: opacity 0.45s ease-out 0.12s, transform 0.45s ease-out 0.12s; }
#{{ $componentId }} .grw-slider-wrapper .grw-header p { transition: opacity 0.45s ease-out 0.22s, transform 0.45s ease-out 0.22s; }
#{{ $componentId }} .grw-review-card .grw-card { transition: opacity 0.4s ease-out, transform 0.45s ease-out; }
#{{ $componentId }} .grw-review-card:nth-child(1) .grw-card { transition-delay: 0.32s; }
#{{ $componentId }} .grw-review-card:nth-child(2) .grw-card { transition-delay: 0.42s; }
#{{ $componentId }} .grw-review-card:nth-child(3) .grw-card { transition-delay: 0.52s; }
#{{ $componentId }} .grw-review-card:nth-child(4) .grw-card { transition-delay: 0.62s; }
#{{ $componentId }} .grw-review-card:nth-child(5) .grw-card { transition-delay: 0.72s; }
#{{ $componentId }} .grw-review-card:nth-child(n+6) .grw-card { transition-delay: 0.82s; }
#{{ $componentId }} .grw-btn-prev,
#{{ $componentId }} .grw-btn-next,
#{{ $componentId }} .grw-dots { transition: opacity 0.4s ease-out 0.85s, transform 0.4s ease-out 0.85s; }
/* Eindstaten bij in view */
#{{ $componentId }}.grw-in-view .grw-summary { opacity: 1; transform: translateX(0); }
#{{ $componentId }}.grw-in-view .grw-slider-wrapper .grw-header h2 { opacity: 1; transform: translateZ(0); }
#{{ $componentId }}.grw-in-view .grw-slider-wrapper .grw-header p { opacity: 1; transform: translateZ(0); }
#{{ $componentId }}.grw-in-view .grw-review-card .grw-card { opacity: 1; transform: translateY(0); }
#{{ $componentId }}.grw-in-view .grw-btn-prev,
#{{ $componentId }}.grw-in-view .grw-btn-next,
#{{ $componentId }}.grw-in-view .grw-dots { opacity: 1; transform: translateZ(0); }
</style>
<section class="google-reviews-section py-12 md:py-16 bg-gray-100 dark:bg-gray-800" aria-labelledby="{{ $carouselId }}-heading" id="{{ $componentId }}">
    <div class="container-custom">
        {{-- Nexa Taxi layout: twee kolommen – links samenvatting, rechts carousel met meerdere kaarten --}}
        <div class="grw-layout flex flex-col lg:flex-row lg:items-stretch gap-6 lg:gap-8 max-w-6xl mx-auto">
            {{-- Links: vaste hoogte, dark-mode proof --}}
            <div class="grw-summary grw-header grw-header-inner flex-shrink-0 lg:w-[280px] xl:w-[320px] min-h-[260px] flex flex-col rounded-xl p-5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-600 shadow-sm dark:shadow-lg text-left">
                @if($placeName !== '')
                    <h2 id="{{ $carouselId }}-heading" class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ $placeName }}</h2>
                @else
                    <h2 id="{{ $carouselId }}-heading" class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white mb-2">Wat anderen zeggen</h2>
                @endif
                @if($placeRating > 0)
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-3xl font-bold text-orange-500 dark:text-orange-400" aria-hidden="true">{{ number_format($placeRating, 1, ',', '') }}</span>
                        <span class="flex items-center gap-0.5 text-orange-500 dark:text-orange-400 text-lg" aria-hidden="true">★ ★ ★ ★ ★</span>
                    </div>
                @endif
                @if($userRatingCount > 0)
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">Gebaseerd op {{ $userRatingCount }} beoordelingen</p>
                @endif
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-1.5 flex-wrap">
                    <span>powered by</span>
                    <span class="inline-flex items-center gap-px shrink-0" aria-hidden="true">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                    </span>
                    <span>Google</span>
                </p>
                @if($writeReviewUrl !== '')
                    <button type="button" class="grw-write-btn wp-google-right inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-400 text-white font-medium text-sm shadow-md hover:shadow-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-800 mt-auto w-full sm:w-auto" data-grw-review-url="{{ e($writeReviewUrl) }}" aria-label="Beoordeel ons op Google">
                        <span>beoordeel ons op</span>
                        <svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                    </button>
                @endif
            </div>

            {{-- Rechts: kop boven carousel + carousel met 3 kaarten --}}
            <div class="grw-slider-wrapper flex-1 min-w-0 flex flex-col">
                <div class="grw-header mb-4 text-center">
                    <h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white mb-1">Wat anderen zeggen</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center justify-center gap-1">
                        <span>powered by Google</span>
                        <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                    </p>
                </div>
                <div class="grw-slider flex-1 min-w-0 relative flex items-center gap-2 md:gap-4">
                @if(count($reviews) > 3)
                    <button type="button" class="grw-btn grw-btn-prev flex-shrink-0 relative z-20 p-1 md:p-2 text-gray-700 dark:text-gray-200 hover:text-primary transition-colors focus:outline-none cursor-pointer bg-transparent border-0" aria-label="Vorige review">
                        <svg class="w-6 h-6 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m15 19-7-7 7-7"/></svg>
                    </button>
                @endif
                <div class="grw-slider-viewport flex-1 min-w-0 overflow-hidden bg-white/5 dark:bg-black/10 rounded-2xl" id="{{ $carouselId }}">
                    <div class="grw-slider-track flex flex-nowrap transition-transform duration-300 ease-out will-change-transform" role="list" aria-live="polite">
                        @foreach($reviews as $index => $review)
                            @php
                                $text = is_string($review['text'] ?? null) ? $review['text'] : '';
                                $author = is_string($review['author_name'] ?? null) ? $review['author_name'] : 'Anoniem';
                                $initial = $author !== 'Anoniem' ? mb_substr(trim($author), 0, 1) : '?';
                                $rating = max(0, min(5, (int) ($review['rating'] ?? 0)));
                                $time = !empty($review['time']) && is_string($review['time']) ? $review['time'] : '';
                                $cardWidthPct = 100 / max(1, $visibleCards);
                            @endphp
                            <article class="grw-review-card flex-none px-2 box-border" role="listitem" data-grw-index="{{ $index }}">
                                <div class="grw-card h-full rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 p-4 flex flex-col shadow-sm dark:shadow-md">
                                    <div class="flex items-center gap-2 mb-1 flex-shrink-0">
                                        <div class="grw-avatar flex-shrink-0 w-9 h-9 rounded-full bg-primary/20 dark:bg-primary/30 flex items-center justify-center text-primary font-semibold text-sm border-2 border-gray-200 dark:border-gray-700 box-border" aria-hidden="true">{{ strtoupper(mb_substr($initial, 0, 1)) }}</div>
                                        <div class="min-w-0 flex-1 flex items-center gap-2">
                                            <cite class="not-italic font-semibold text-gray-900 dark:text-white text-sm truncate">{{ $author }}</cite>
                                            <span class="flex-shrink-0 ml-auto" aria-hidden="true">
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                                            </span>
                                        </div>
                                    </div>
                                    @if($time)
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1 flex-shrink-0">{{ $time }}</p>
                                    @endif
                                    <div class="flex items-center justify-start gap-0.5 w-fit mb-2 flex-shrink-0" aria-hidden="true">
                                        @for($i = 1; $i <= 5; $i++)
                                            <span class="grw-star text-sm {{ $rating >= $i ? 'text-orange-500 dark:text-orange-400' : 'text-gray-300 dark:text-gray-500' }}" aria-hidden="true">★</span>
                                        @endfor
                                    </div>
                                    <blockquote class="grw-text text-sm text-gray-700 dark:text-gray-300 leading-relaxed my-0">
                                        @if(strlen($text) > 80)
                                            <span class="grw-text-short block">{{ $text }}</span>
                                            <span class="grw-text-full hidden">{{ $text }}</span>
                                            <button type="button" class="grw-read-more pt-1 text-xs font-medium text-primary dark:text-primary-400 hover:underline focus:outline-none focus:underline" aria-expanded="false">Lees meer</button>
                                        @elseif($text !== '')
                                            <span class="block">{{ $text }}</span>
                                        @else
                                            <span class="italic text-gray-500 dark:text-gray-400 block">Geen beoordelingstekst.</span>
                                        @endif
                                    </blockquote>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
                @if(count($reviews) > 3)
                    <button type="button" class="grw-btn grw-btn-next flex-shrink-0 relative z-20 p-1 md:p-2 text-gray-700 dark:text-gray-200 hover:text-primary transition-colors focus:outline-none cursor-pointer bg-transparent border-0" aria-label="Volgende review">
                        <svg class="w-6 h-6 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7"/></svg>
                    </button>
                @endif
                </div>
                @if(count($reviews) > 3)
                    <div class="grw-dots flex justify-center gap-2 mt-4" role="tablist" aria-label="Review navigatie">
                        @php $totalSlides = max(1, count($reviews) - $visibleCards + 1); @endphp
                        @for($i = 0; $i < $totalSlides; $i++)
                            <button type="button" class="grw-dot w-2.5 h-2.5 rounded-full border-0 cursor-pointer transition-colors focus:outline-none focus:ring-2 focus:ring-primary {{ $i === 0 ? 'grw-dot-active' : '' }}" role="tab" aria-selected="{{ $i === 0 ? 'true' : 'false' }}" aria-label="Slide {{ $i + 1 }}" data-grw-slide="{{ $i }}"></button>
                        @endfor
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal: beoordeel ons op Google (geen iframe; Google blokkeert embed. Knop opent link in nieuw tabblad.) --}}
    @if($writeReviewUrl !== '')
    <div id="grw-review-modal-{{ $componentId }}" class="grw-review-modal fixed inset-0 z-[100] hidden items-center justify-center p-4 bg-black/60 dark:bg-black/70" aria-modal="true" aria-labelledby="grw-review-modal-title-{{ $componentId }}" role="dialog">
        <div class="grw-review-modal-dialog relative w-full max-w-md flex flex-col bg-white dark:bg-gray-800 rounded-xl shadow-2xl overflow-hidden" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between flex-shrink-0 px-4 py-3 border-b border-gray-200 dark:border-gray-600">
                <h3 id="grw-review-modal-title-{{ $componentId }}" class="text-lg font-semibold text-gray-900 dark:text-white">Beoordeel ons op Google</h3>
                <button type="button" class="grw-review-modal-close w-10 h-10 flex items-center justify-center rounded-lg text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors focus:outline-none focus:ring-2 focus:ring-primary" aria-label="Sluiten">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-4 pb-5 flex flex-col gap-4">
                <p class="text-sm text-gray-600 dark:text-gray-300">Je wordt doorgestuurd naar Google om een beoordeling te schrijven. De pagina opent in een nieuw tabblad.</p>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ e($writeReviewUrl) }}" target="_blank" rel="noopener noreferrer" class="grw-review-modal-open inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-400 text-white font-medium text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 no-underline">
                        <svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                        Open Google
                    </a>
                    <button type="button" class="grw-review-modal-close px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-primary">Annuleren</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <script>
    (function() {
        var componentId = @json($componentId);
        function initGrwCarousel() {
        var visibleCards = @json($visibleCards);
        var wrap = document.getElementById(componentId);
        if (!wrap) return;
        var viewport = wrap.querySelector('.grw-slider-viewport');
        var track = wrap.querySelector('.grw-slider-track');
        var cards = wrap.querySelectorAll('.grw-review-card');
        var prevBtn = wrap.querySelector('.grw-btn-prev');
        var nextBtn = wrap.querySelector('.grw-btn-next');
        var dots = wrap.querySelectorAll('.grw-dot');
        var total = cards.length;
        if (total <= 1 || !track || !viewport) return;

        var maxSlide = Math.max(0, total - visibleCards);
        var current = 0;
        var autoPlayMs = 5000;
        var autoPlayTimer = null;
        var cardWidthPx = 0;

        function setSizes() {
            var vw = viewport.offsetWidth;
            if (vw <= 0 || total === 0) return;
            cardWidthPx = Math.floor(vw / visibleCards);
            track.style.width = (total * cardWidthPx) + 'px';
            track.style.minWidth = (total * cardWidthPx) + 'px';
            for (var i = 0; i < cards.length; i++) {
                cards[i].style.width = cardWidthPx + 'px';
                cards[i].style.minWidth = cardWidthPx + 'px';
                cards[i].style.flexBasis = cardWidthPx + 'px';
            }
        }

        function updateSlider() {
            current = Math.max(0, Math.min(current, maxSlide));
            var offsetPx = -(current * cardWidthPx);
            track.style.transform = 'translate3d(' + offsetPx + 'px, 0, 0)';
            dots.forEach(function(dot, i) {
                var isActive = i === current;
                dot.setAttribute('aria-selected', isActive ? 'true' : 'false');
                if (isActive) {
                    dot.classList.add('grw-dot-active');
                } else {
                    dot.classList.remove('grw-dot-active');
                }
            });
        }

        function goNext() {
            if (current >= maxSlide) current = -1;
            current++;
            updateSlider();
        }
        function goPrev() {
            current--;
            if (current < 0) current = maxSlide;
            updateSlider();
        }
        function startAutoPlay() {
            stopAutoPlay();
            autoPlayTimer = setInterval(goNext, autoPlayMs);
        }
        function stopAutoPlay() {
            if (autoPlayTimer) { clearInterval(autoPlayTimer); autoPlayTimer = null; }
        }

        if (prevBtn) prevBtn.addEventListener('click', function(e) { e.preventDefault(); stopAutoPlay(); goPrev(); startAutoPlay(); });
        if (nextBtn) nextBtn.addEventListener('click', function(e) { e.preventDefault(); stopAutoPlay(); goNext(); startAutoPlay(); });
        dots.forEach(function(dot, i) {
            dot.addEventListener('click', function() { stopAutoPlay(); current = i - 1; goNext(); startAutoPlay(); });
        });
        viewport.addEventListener('mouseenter', stopAutoPlay);
        viewport.addEventListener('mouseleave', startAutoPlay);
        viewport.addEventListener('focusin', stopAutoPlay);
        viewport.addEventListener('focusout', startAutoPlay);
        window.addEventListener('resize', function() {
            setSizes();
            maxSlide = Math.max(0, total - visibleCards);
            current = Math.min(current, maxSlide);
            updateSlider();
        });
        setSizes();
        updateSlider();
        startAutoPlay();

        var modal = document.getElementById('grw-review-modal-' + componentId);
        var writeBtn = wrap.querySelector('.grw-write-btn');
        if (writeBtn && modal) {
            var closeBtns = modal.querySelectorAll('.grw-review-modal-close');
            var openLink = modal.querySelector('.grw-review-modal-open');
            function openModal() {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.style.overflow = 'hidden';
            }
            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = '';
            }
            writeBtn.addEventListener('click', function() { openModal(); });
            closeBtns.forEach(function(btn) { btn.addEventListener('click', closeModal); });
            if (openLink) openLink.addEventListener('click', function() { closeModal(); });
            modal.addEventListener('click', function(e) { if (e.target === modal) closeModal(); });
            modal.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeModal(); });
        }

        wrap.querySelectorAll('.grw-read-more').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var card = btn.closest('.grw-card');
                var textBlock = card && card.querySelector('.grw-text');
                var short = card && card.querySelector('.grw-text-short');
                var full = card && card.querySelector('.grw-text-full');
                var expanded = btn.getAttribute('aria-expanded') === 'true';
                if (expanded) {
                    if (short) short.classList.remove('hidden');
                    if (full) full.classList.add('hidden');
                    if (textBlock) textBlock.classList.remove('grw-text-expanded');
                    btn.textContent = 'Lees meer';
                    btn.setAttribute('aria-expanded', 'false');
                } else {
                    if (short) short.classList.add('hidden');
                    if (full) full.classList.remove('hidden');
                    if (textBlock) textBlock.classList.add('grw-text-expanded');
                    btn.textContent = 'Lees minder';
                    btn.setAttribute('aria-expanded', 'true');
                }
            });
        });
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initGrwCarousel);
        } else {
            initGrwCarousel();
        }
        (function initScrollAnimation() {
            var section = document.getElementById(componentId);
            if (!section || typeof IntersectionObserver === 'undefined') return;
            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('grw-in-view');
                        observer.unobserve(entry.target);
                    }
                });
            }, { rootMargin: '0px 0px -12% 0px', threshold: 0 });
            observer.observe(section);
        })();
    })();
    </script>
</section>
@endif
