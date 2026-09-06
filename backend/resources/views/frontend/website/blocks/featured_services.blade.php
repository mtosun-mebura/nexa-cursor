@php
    $data = $block['data'] ?? [];
    $decode = function ($s) {
        $s = trim((string) $s);
        return $s === '' ? '' : html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    };
    $title = $decode($data['title'] ?? '');
    $subtitle = $decode($data['subtitle'] ?? '');
    $items = isset($data['items']) && is_array($data['items']) ? array_values($data['items']) : [];
    $icons = config('heroicons.icons', []);
    $blocksPerRow = (int) ($data['blocks_per_row'] ?? 3);
    if (!in_array($blocksPerRow, [2, 3, 4], true)) $blocksPerRow = 3;
    $blockSize = $data['block_size'] ?? 'medium';
    if (!in_array($blockSize, ['small', 'medium', 'large', 'full'], true)) $blockSize = 'medium';
    $blockAlign = $data['block_align'] ?? 'center';
    if (!in_array($blockAlign, ['left', 'center', 'right'], true)) $blockAlign = 'center';
    $iconSize = $data['icon_size'] ?? 'medium';
    if (!in_array($iconSize, ['small', 'medium', 'large'], true)) $iconSize = 'medium';
    $iconAlign = $data['icon_align'] ?? 'center';
    if (!in_array($iconAlign, ['top', 'center', 'bottom'], true)) $iconAlign = 'center';
    $iconAlignClass = match($iconAlign) {
        'top' => 'items-start',
        'bottom' => 'items-end',
        default => 'items-center',
    };
    $iconSizePx = match($iconSize) {
        'small' => 20,
        'large' => 40,
        default => 30,
    };
    // justify-content lijnt de kaarten binnen de rij uit; data-block-align plaatst (via de stylesheet
    // hieronder) het kaartenblok zelf met auto-marges zodra blocks_row_width_percent < 100 de rij
    // smaller maakt dan de sectie.
    $alignClass = match($blockAlign) {
        'left' => 'justify-start',
        'right' => 'justify-end',
        default => 'justify-center',
    };
    $cardPadding = match($blockSize) {
        'small' => 'p-4',
        'large' => 'p-8',
        'full', 'medium' => 'p-6',
        default => 'p-6',
    };
    $cardBgColor = isset($data['card_bg_color']) && is_string($data['card_bg_color']) ? trim($data['card_bg_color']) : '';
    $cardBgColor = $cardBgColor !== '' && preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $cardBgColor) ? $cardBgColor : '';
    $animationSpeed = $data['animation_speed'] ?? 'slow';
    if (!in_array($animationSpeed, ['fast', 'normal', 'slow', 'slower'], true)) {
        $animationSpeed = 'slow';
    }
    $revealDurationMs = match($animationSpeed) { 'fast' => 560, 'normal' => 720, 'slow' => 900, 'slower' => 1100, default => 900 };
    $revealDuration = ($revealDurationMs / 1000).'s';
    $revealEasing = 'cubic-bezier(0.16, 1, 0.3, 1)';
    $revealFirstCardDelayMs = 120;
    $revealDelayStepMs = match($animationSpeed) { 'fast' => 90, 'normal' => 130, 'slow' => 170, 'slower' => 220, default => 170 };
    $lastCardDelayMs = count($items) > 0 ? $revealFirstCardDelayMs + (count($items) - 1) * $revealDelayStepMs : 0;
    $hoverReadyMs = $revealDurationMs + $lastCardDelayMs + 80;
    $blocksRowWidthPctRaw = $data['blocks_row_width_percent'] ?? null;
    $blocksRowWidthPct = ($blocksRowWidthPctRaw === null || $blocksRowWidthPctRaw === '') ? 100 : (int) $blocksRowWidthPctRaw;
    $blocksRowWidthPct = max(1, min(100, $blocksRowWidthPct));
    $allowedSectionFsPx = range(10, 40, 2);
    $titleFontPx = isset($data['title_font_size_px']) && $data['title_font_size_px'] !== '' ? (int) $data['title_font_size_px'] : 24;
    $titleFontPx = in_array($titleFontPx, $allowedSectionFsPx, true) ? $titleFontPx : 24;
    $subtitleFontPx = isset($data['subtitle_font_size_px']) && $data['subtitle_font_size_px'] !== '' ? (int) $data['subtitle_font_size_px'] : 18;
    $subtitleFontPx = in_array($subtitleFontPx, $allowedSectionFsPx, true) ? $subtitleFontPx : 18;
    $itemTitleFontPx = isset($data['item_title_font_size_px']) && $data['item_title_font_size_px'] !== '' ? (int) $data['item_title_font_size_px'] : 18;
    $itemTitleFontPx = in_array($itemTitleFontPx, $allowedSectionFsPx, true) ? $itemTitleFontPx : 18;
    $itemDescFontPx = isset($data['item_description_font_size_px']) && $data['item_description_font_size_px'] !== '' ? (int) $data['item_description_font_size_px'] : 14;
    $itemDescFontPx = in_array($itemDescFontPx, $allowedSectionFsPx, true) ? $itemDescFontPx : 14;
@endphp
<section class="website-block website-block-featured-services pt-6 md:pt-8 pb-8 md:pb-12 scroll-reveal-section" data-scroll-reveal data-fs-hover-ready-ms="{{ $hoverReadyMs }}">
    <div class="website-section-inner">
        <div class="featured-services-heading w-full max-w-4xl mx-auto text-center mb-6 md:mb-8 px-0 scroll-reveal-item" style="--fs-reveal-duration: {{ $revealDuration }}; --fs-reveal-ease: {{ $revealEasing }}; --fs-title-max: {{ $titleFontPx }}px; --fs-subtitle-max: {{ $subtitleFontPx }}px;">
            @if($title !== '')
                <h2 class="featured-services-title font-bold text-gray-900 dark:text-white mb-3 leading-tight" style="font-family: var(--theme-font-heading, inherit);">{!! e($title) !!}</h2>
            @endif
            @if($subtitle !== '')
                <div class="featured-services-subtitle text-gray-600 dark:text-gray-300 leading-relaxed">{!! $subtitle !!}</div>
            @endif
        </div>
        @if(count($items) > 0)
            <div
                class="featured-services-cards flex flex-col md:flex-row md:flex-wrap {{ $alignClass }} gap-3 md:gap-6 lg:gap-8 w-full max-w-full box-border px-3 md:px-0"
                data-blocks-per-row="{{ $blocksPerRow }}"
                data-block-align="{{ $blockAlign }}"
                @if($blocksRowWidthPct < 100) style="--featured-services-row-max: {{ $blocksRowWidthPct }}%;" @endif
            >
                @foreach($items as $index => $item)
                    @php
                        $itemTitle = $decode($item['title'] ?? '');
                        $itemDesc = $decode($item['description'] ?? '');
                        $iconKey = trim((string) ($item['icon'] ?? 'light-bulb'));
                        $iconDef = $icons[$iconKey] ?? $icons['light-bulb'] ?? ['svg' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />'];
                        $iconColor = isset($item['icon_color']) && is_string($item['icon_color']) && preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', trim($item['icon_color'])) ? trim($item['icon_color']) : null;
                    @endphp
                    @php
                        $cardRevealDelayMs = $revealFirstCardDelayMs + $index * $revealDelayStepMs;
                        $iconRevealDelayMs = $cardRevealDelayMs + (int) round($revealDurationMs * 0.28);
                        $revealStyle = '--fs-card-delay: '.$cardRevealDelayMs.'ms; --fs-icon-delay: '.$iconRevealDelayMs.'ms; --fs-reveal-duration: '.$revealDuration.'; --fs-reveal-ease: '.$revealEasing.';';
                    @endphp
                    <div class="featured-service-item min-w-0" data-fs-index="{{ $index }}">
                        <div class="featured-service-reveal scroll-reveal-item min-w-0 h-full" style="{{ $revealStyle }}" data-scroll-reveal-delay="{{ $index }}">
                        <div class="featured-service-card min-w-0 h-full rounded-xl border border-gray-200 dark:border-gray-700 {{ $cardPadding }} shadow-sm w-full {{ $cardBgColor ? '' : 'bg-white dark:bg-gray-800/50' }}" @if($cardBgColor) style="background-color: {{ $cardBgColor }};" @endif>
                        <div class="flex {{ $iconAlignClass }} gap-4 min-w-0">
                            @php
                                $iconColorStyle = $iconColor ? ' color: ' . e($iconColor) . ';' : '';
                            @endphp
                            <div class="featured-service-icon shrink-0 rounded-lg flex items-center justify-center {{ $iconColor ? '' : 'bg-primary/10 dark:bg-primary/20 text-primary' }}" style="width: {{ $iconSizePx }}px; height: {{ $iconSizePx }}px; @if($iconColor) background-color: transparent; @endif{{ $iconColorStyle }}">
                                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" style="width: {{ $iconSizePx }}px; height: {{ $iconSizePx }}px;">{!! $iconDef['svg'] ?? '' !!}</svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                @if($itemTitle !== '')
                                    <h3 class="featured-service-item-title font-semibold text-gray-900 dark:text-white mb-2 break-words [overflow-wrap:anywhere] leading-snug" style="--fs-item-title-max: {{ $itemTitleFontPx }}px;">{!! e($itemTitle) !!}</h3>
                                @endif
                                @if($itemDesc !== '')
                                    <div class="featured-service-item-desc text-gray-600 dark:text-gray-300 leading-relaxed break-words [overflow-wrap:anywhere]" style="--fs-item-desc-max: {{ $itemDescFontPx }}px;">{!! $itemDesc !!}</div>
                                @endif
                            </div>
                            </div>
                        </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

@push('styles')
<style>
    .website-block-featured-services .featured-services-title {
        font-size: clamp(1.125rem, 4vw + 0.5rem, var(--fs-title-max, 1.5rem));
    }
    .website-block-featured-services .featured-services-subtitle {
        font-size: clamp(0.9375rem, 2.5vw + 0.4rem, var(--fs-subtitle-max, 1.125rem));
    }
    .website-block-featured-services .featured-service-item-title {
        font-size: clamp(0.9375rem, 2vw + 0.35rem, var(--fs-item-title-max, 1.125rem));
    }
    .website-block-featured-services .featured-service-item-desc {
        font-size: clamp(0.8125rem, 1.5vw + 0.3rem, var(--fs-item-desc-max, 0.875rem));
    }
    /* Mobiel: elke kaart volle breedte met lichte zij-padding via .featured-services-cards */
    @media (max-width: 767px) {
        .website-block-featured-services .featured-services-cards {
            display: flex !important;
            flex-direction: column !important;
            align-items: stretch !important;
            width: 100% !important;
            max-width: 100% !important;
            padding-left: 0.75rem !important;
            padding-right: 0.75rem !important;
            gap: 0.75rem !important;
        }
        .website-block-featured-services .featured-services-cards > .featured-service-item {
            width: 100% !important;
            max-width: 100% !important;
            flex: none !important;
        }
        .website-block-featured-services .featured-service-card {
            width: 100% !important;
            box-sizing: border-box;
        }
    }
    /* Kaarten per rij: flex-wrap; desktop meerdere kolommen */
    .website-block-featured-services .featured-services-cards > .featured-service-item {
        width: 100%;
        min-width: 0;
        box-sizing: border-box;
    }
    @media (min-width: 768px) {
        .website-block-featured-services .featured-services-cards[style*="--featured-services-row-max"] {
            max-width: var(--featured-services-row-max);
        }
        /* Positioneer het (eventueel smallere) kaartenblok binnen de sectie volgens block_align. */
        .website-block-featured-services .featured-services-cards[data-block-align="left"] {
            margin-left: 0;
            margin-right: auto;
        }
        .website-block-featured-services .featured-services-cards[data-block-align="center"] {
            margin-left: auto;
            margin-right: auto;
        }
        .website-block-featured-services .featured-services-cards[data-block-align="right"] {
            margin-left: auto;
            margin-right: 0;
        }
    }
    /* 2 kolommen vanaf sm */
    @media (min-width: 640px) and (max-width: 1023px) {
        .website-block-featured-services .featured-services-cards[data-blocks-per-row="2"] > .featured-service-item {
            flex: 0 1 calc((100% - 1.5rem) / 2);
            max-width: calc((100% - 1.5rem) / 2);
        }
    }
    /* 3 per rij: op tablet 2 kolommen (bredere kaarten), op groot scherm 3 */
    @media (min-width: 768px) and (max-width: 1279px) {
        .website-block-featured-services .featured-services-cards[data-blocks-per-row="3"] > .featured-service-item {
            flex: 0 1 calc((100% - 1.5rem) / 2);
            max-width: calc((100% - 1.5rem) / 2);
        }
    }
    @media (min-width: 1280px) {
        .website-block-featured-services .featured-services-cards[data-blocks-per-row="3"] > .featured-service-item {
            flex: 0 1 calc((100% - 4rem) / 3);
            max-width: calc((100% - 4rem) / 3);
            min-width: min(100%, 22rem);
        }
    }
    /* 2 per rij: bredere kaarten op desktop */
    @media (min-width: 1024px) {
        .website-block-featured-services .featured-services-cards[data-blocks-per-row="2"] > .featured-service-item {
            flex: 0 1 calc((100% - 2rem) / 2);
            max-width: calc((100% - 2rem) / 2);
            min-width: min(100%, 28rem);
        }
    }
    /* 4: 2 kolommen md, 4 kolommen lg */
    @media (min-width: 768px) and (max-width: 1023px) {
        .website-block-featured-services .featured-services-cards[data-blocks-per-row="4"] > .featured-service-item {
            flex: 0 1 calc((100% - 1.5rem) / 2);
            max-width: calc((100% - 1.5rem) / 2);
        }
    }
    @media (min-width: 1024px) {
        .website-block-featured-services .featured-services-cards[data-blocks-per-row="4"] > .featured-service-item {
            flex: 0 1 calc((100% - 6rem) / 4);
            max-width: calc((100% - 6rem) / 4);
        }
    }
    .website-block-featured-services.scroll-reveal-section .featured-services-heading.scroll-reveal-item {
        opacity: 0;
        transform: scaleY(0.12);
        transform-origin: center top;
        filter: blur(8px);
        will-change: opacity, transform, filter;
        transition: opacity var(--fs-reveal-duration, 0.9s) var(--fs-reveal-ease, cubic-bezier(0.16, 1, 0.3, 1)),
            transform var(--fs-reveal-duration, 0.9s) var(--fs-reveal-ease, cubic-bezier(0.16, 1, 0.3, 1)),
            filter var(--fs-reveal-duration, 0.9s) var(--fs-reveal-ease, cubic-bezier(0.16, 1, 0.3, 1));
    }
    .website-block-featured-services.scroll-reveal-section.is-in-view .featured-services-heading.scroll-reveal-item {
        opacity: 1;
        filter: blur(0);
        transform: scaleY(1);
    }
    .website-block-featured-services .featured-services-title {
        position: relative;
        display: inline-block;
        letter-spacing: 0.01em;
    }
    .website-block-featured-services .featured-services-title::after {
        content: '';
        display: block;
        width: 3.25rem;
        height: 3px;
        margin: 0.7rem auto 0;
        border-radius: 999px;
        background: var(--theme-primary, #2563eb);
        transform: scaleX(0);
        transform-origin: left center;
        transition: transform 0.7s cubic-bezier(0.22, 1, 0.36, 1) 0.28s;
    }
    .website-block-featured-services.is-in-view .featured-services-title::after {
        transform: scaleX(1);
    }
    .website-block-featured-services.scroll-reveal-section .featured-service-reveal.scroll-reveal-item {
        opacity: 0;
        filter: saturate(0.15) blur(6px);
        transform: scale(0.38) rotate(-16deg);
        transform-origin: 1.75rem 1.75rem;
        will-change: opacity, transform, filter;
        transition: opacity var(--fs-reveal-duration, 0.9s) var(--fs-reveal-ease, cubic-bezier(0.16, 1, 0.3, 1)) var(--fs-card-delay, 0ms),
            transform var(--fs-reveal-duration, 0.9s) cubic-bezier(0.34, 1.45, 0.64, 1) var(--fs-card-delay, 0ms),
            filter var(--fs-reveal-duration, 0.9s) var(--fs-reveal-ease, cubic-bezier(0.16, 1, 0.3, 1)) var(--fs-card-delay, 0ms);
    }
    .website-block-featured-services.scroll-reveal-section.is-in-view .featured-service-reveal.scroll-reveal-item {
        opacity: 1;
        filter: saturate(1) blur(0);
        transform: scale(1) rotate(0);
    }
    .website-block-featured-services .featured-service-card {
        position: relative;
        isolation: isolate;
        overflow: hidden;
        overflow-wrap: anywhere;
        word-break: break-word;
        transform: translateZ(0);
        transition: box-shadow 0.4s ease, border-color 0.35s ease, background-color 0.4s ease;
    }
    .website-block-featured-services .featured-service-card::before {
        content: '';
        position: absolute;
        inset: auto auto 0 0;
        width: 100%;
        height: 3px;
        background: linear-gradient(90deg, var(--theme-primary, #2563eb), color-mix(in srgb, var(--theme-primary, #2563eb) 20%, transparent));
        transform: scaleX(0);
        transform-origin: left center;
        transition: transform 0.45s cubic-bezier(0.22, 1, 0.36, 1);
        z-index: 3;
        pointer-events: none;
    }
    .website-block-featured-services .featured-service-card::after {
        content: '';
        position: absolute;
        width: 9rem;
        height: 9rem;
        top: -3.5rem;
        left: -3.5rem;
        border-radius: 999px;
        background: radial-gradient(circle, color-mix(in srgb, var(--theme-primary, #2563eb) 28%, transparent) 0%, transparent 70%);
        opacity: 0;
        transform: scale(0.4);
        transition: opacity 0.45s ease, transform 0.55s cubic-bezier(0.22, 1, 0.36, 1);
        pointer-events: none;
        z-index: 1;
    }
    .website-block-featured-services .featured-service-icon {
        position: relative;
        z-index: 2;
        transform: scale(0) rotate(-170deg);
        transform-origin: center;
        transition: transform 0.7s cubic-bezier(0.34, 1.56, 0.64, 1) var(--fs-icon-delay, 180ms);
    }
    .website-block-featured-services.scroll-reveal-section.is-in-view .featured-service-icon {
        transform: scale(1) rotate(0);
    }
    .website-block-featured-services.is-hover-ready .featured-service-card:hover {
        border-color: color-mix(in srgb, var(--theme-primary, #2563eb) 62%, rgb(209 213 219));
        box-shadow:
            0 0 0 1px color-mix(in srgb, var(--theme-primary, #2563eb) 32%, transparent),
            0 18px 34px -18px color-mix(in srgb, var(--theme-primary, #2563eb) 45%, rgb(0 0 0 / 0.28));
    }
    .website-block-featured-services.is-hover-ready .featured-service-card:hover::before {
        transform: scaleX(1);
    }
    .website-block-featured-services.is-hover-ready .featured-service-card:hover::after {
        opacity: 1;
        transform: scale(1);
    }
    .website-block-featured-services.is-hover-ready .featured-service-card:hover .featured-service-icon {
        animation: featured-service-icon-orbit 1.7s ease-in-out infinite;
    }
    .website-block-featured-services.is-hover-ready .featured-service-card:hover .featured-service-item-title {
        color: var(--theme-primary, #2563eb);
        transition: color 0.3s ease;
    }
    @keyframes featured-service-icon-orbit {
        0%, 100% { transform: translate(0, 0) rotate(0) scale(1.04); }
        25% { transform: translate(3px, -5px) rotate(-9deg) scale(1.08); }
        50% { transform: translate(-2px, -7px) rotate(7deg) scale(1.06); }
        75% { transform: translate(-5px, -2px) rotate(-4deg) scale(1.08); }
    }
    .dark .website-block-featured-services.is-hover-ready .featured-service-card:hover {
        border-color: color-mix(in srgb, var(--theme-primary, #60a5fa) 55%, rgb(55 65 81));
        box-shadow:
            0 0 0 1px color-mix(in srgb, var(--theme-primary, #60a5fa) 38%, transparent),
            0 20px 38px -16px rgb(0 0 0 / 0.55);
    }
    .dark .website-block-featured-services.is-hover-ready .featured-service-card:hover .featured-service-item-title {
        color: var(--theme-primary, #60a5fa);
    }
    @media (prefers-reduced-motion: reduce) {
        .website-block-featured-services.scroll-reveal-section .featured-services-heading.scroll-reveal-item,
        .website-block-featured-services.scroll-reveal-section .featured-service-reveal.scroll-reveal-item,
        .website-block-featured-services .featured-service-icon {
            opacity: 1;
            filter: none;
            transform: none;
            transition: none;
        }
        .website-block-featured-services .featured-services-title::after,
        .website-block-featured-services .featured-service-card,
        .website-block-featured-services .featured-service-card::before,
        .website-block-featured-services .featured-service-card::after {
            animation: none !important;
            transition: none !important;
            transform: none !important;
        }
        .website-block-featured-services.is-hover-ready .featured-service-card:hover .featured-service-icon {
            animation: none !important;
        }
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    function bindFeaturedServicesHover(section) {
        if (section.getAttribute('data-fs-hover-bound') === '1') {
            return;
        }
        section.setAttribute('data-fs-hover-bound', '1');
        var timer = null;
        var wasInView = false;
        function readyMs() {
            return parseInt(section.getAttribute('data-fs-hover-ready-ms') || '900', 10);
        }
        function arm() {
            if (timer) {
                window.clearTimeout(timer);
            }
            timer = window.setTimeout(function () {
                section.classList.add('is-hover-ready');
            }, Math.max(0, readyMs()));
        }
        function disarm() {
            if (timer) {
                window.clearTimeout(timer);
                timer = null;
            }
            section.classList.remove('is-hover-ready');
        }
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            section.classList.add('is-hover-ready');
            return;
        }
        // Alleen reageren op een echte wissel van is-in-view, anders triggert het
        // toevoegen/verwijderen van is-hover-ready hierbeneden de observer opnieuw
        // (oneindige lus die de main thread blokkeert zodra de sectie in beeld komt).
        function syncFromInView() {
            var inView = section.classList.contains('is-in-view');
            if (inView === wasInView) {
                return;
            }
            wasInView = inView;
            if (inView) {
                arm();
            } else {
                disarm();
            }
        }
        syncFromInView();
        new MutationObserver(syncFromInView).observe(section, { attributes: true, attributeFilter: ['class'] });
    }
    document.querySelectorAll('.website-block-featured-services').forEach(bindFeaturedServicesHover);
})();
</script>
@endpush
