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
    $cardMaxWidth = match($blockSize) {
        'small' => '300px',
        'medium' => '500px',
        'large' => '700px',
        default => null,
    };
    $alignClass = match($blockAlign) {
        'left' => 'justify-start',
        'right' => 'justify-end',
        default => 'justify-center',
    };
    $gridCols = match($blocksPerRow) {
        2 => 'md:grid-cols-2',
        4 => 'md:grid-cols-2 lg:grid-cols-4',
        default => 'md:grid-cols-2 lg:grid-cols-3',
    };
    $cardPadding = match($blockSize) {
        'small' => 'p-4',
        'large' => 'p-8',
        'full', 'medium' => 'p-6',
        default => 'p-6',
    };
    $titleSize = match($blockSize) {
        'small' => 'text-base',
        'large' => 'text-xl',
        default => 'text-lg',
    };
    $cardBgColor = isset($data['card_bg_color']) && is_string($data['card_bg_color']) ? trim($data['card_bg_color']) : '';
    $cardBgColor = $cardBgColor !== '' && preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $cardBgColor) ? $cardBgColor : '';
    $animationSpeed = $data['animation_speed'] ?? 'slow';
    if (!in_array($animationSpeed, ['fast', 'normal', 'slow', 'slower'], true)) {
        $animationSpeed = 'slow';
    }
    $revealDuration = match($animationSpeed) { 'fast' => '0.5s', 'normal' => '0.65s', 'slow' => '0.8s', 'slower' => '1s', default => '0.8s' };
    $revealFirstCardDelayMs = 90;
    $revealDelayStepMs = match($animationSpeed) { 'fast' => 100, 'normal' => 150, 'slow' => 200, 'slower' => 260, default => 200 };
@endphp
<section class="website-block website-block-featured-services py-12 md:py-16 scroll-reveal-section" data-scroll-reveal>
    <div class="container-custom">
        <div class="max-w-4xl mx-auto text-center mb-10 md:mb-14 scroll-reveal-item" style="transition: opacity {{ $revealDuration }} cubic-bezier(0.25, 0.46, 0.45, 0.94), transform {{ $revealDuration }} cubic-bezier(0.25, 0.46, 0.45, 0.94); transition-delay: 0ms;">
            @if($title !== '')
                <h2 class="text-2xl md:text-3xl lg:text-4xl font-bold text-gray-900 dark:text-white mb-3" style="font-family: var(--theme-font-heading, inherit);">{!! e($title) !!}</h2>
            @endif
            @if($subtitle !== '')
                <p class="text-lg text-gray-600 dark:text-gray-300">{!! e($subtitle) !!}</p>
            @endif
        </div>
        @if(count($items) > 0)
            <div class="flex {{ $alignClass }} w-full">
                @if($cardMaxWidth)
                <div class="featured-services-grid-fixed featured-services-align-{{ $blockAlign }} flex flex-wrap gap-6 lg:gap-8" style="--fs-cols: {{ $blocksPerRow }}; --fs-col-width: {{ $cardMaxWidth }};">
                @else
                <div class="grid grid-cols-1 {{ $gridCols }} gap-6 lg:gap-8 w-full">
                @endif
                @foreach($items as $index => $item)
                    @php
                        $itemTitle = $decode($item['title'] ?? '');
                        $itemDesc = $decode($item['description'] ?? '');
                        $iconKey = trim((string) ($item['icon'] ?? 'light-bulb'));
                        $iconDef = $icons[$iconKey] ?? $icons['light-bulb'] ?? ['svg' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />'];
                    @endphp
                    @php
                        $cardRevealDelayMs = $revealFirstCardDelayMs + $index * $revealDelayStepMs;
                        $revealStyle = 'transition: opacity ' . $revealDuration . ' cubic-bezier(0.25, 0.46, 0.45, 0.94), transform ' . $revealDuration . ' cubic-bezier(0.25, 0.46, 0.45, 0.94); transition-delay: ' . $cardRevealDelayMs . 'ms;';
                    @endphp
                    <div class="featured-service-item min-w-0">
                        <div class="scroll-reveal-item min-w-0 h-full" style="{{ $revealStyle }}" data-scroll-reveal-delay="{{ $index }}">
                        <div class="featured-service-card min-w-0 h-full rounded-xl border border-gray-200 dark:border-gray-700 {{ $cardPadding }} shadow-sm w-full {{ $cardBgColor ? '' : 'bg-white dark:bg-gray-800/50' }}" @if($cardBgColor) style="background-color: {{ $cardBgColor }};" @endif>
                        <div class="flex {{ $iconAlignClass }} gap-4">
                            <div class="featured-service-icon shrink-0 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center text-primary" style="width: {{ $iconSizePx }}px; height: {{ $iconSizePx }}px;">
                                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" style="width: {{ $iconSizePx }}px; height: {{ $iconSizePx }}px;">{!! $iconDef['svg'] ?? '' !!}</svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                @if($itemTitle !== '')
                                    <h3 class="{{ $titleSize }} font-semibold text-gray-900 dark:text-white mb-2">{!! e($itemTitle) !!}</h3>
                                @endif
                                @if($itemDesc !== '')
                                    <p class="text-gray-600 dark:text-gray-300 text-sm leading-relaxed">{!! nl2br(e($itemDesc)) !!}</p>
                                @endif
                            </div>
                            </div>
                        </div>
                        </div>
                    </div>
                @endforeach
                </div>
            </div>
        @endif
    </div>
</section>

@push('styles')
<style>
    .featured-services-grid-fixed {
        width: 100%;
    }
    .featured-services-grid-fixed .featured-service-item {
        width: 100%;
    }
    @media (min-width: 768px) {
        /* Breedte = één volle rij (zodat laatste rij met minder cards dezelfde breedte heeft en uitlijning werkt) */
        .featured-services-grid-fixed {
            width: calc(var(--fs-cols) * var(--fs-col-width) + (var(--fs-cols) - 1) * 1.5rem);
            max-width: 100%;
        }
        .featured-services-grid-fixed.featured-services-align-left {
            justify-content: flex-start;
        }
        .featured-services-grid-fixed.featured-services-align-center {
            justify-content: center;
        }
        .featured-services-grid-fixed.featured-services-align-right {
            justify-content: flex-end;
        }
        .featured-services-grid-fixed .featured-service-item {
            width: var(--fs-col-width);
            max-width: 100%;
        }
    }
    /* Zelfde invliegen als Elementor Overige Diensten: van beneden, lichte scale, cards duidelijk na elkaar */
    .website-block-featured-services.scroll-reveal-section .scroll-reveal-item {
        opacity: 0;
        transform: translateY(48px) scale(0.98);
        transform-origin: center center;
        will-change: opacity, transform;
    }
    .website-block-featured-services.scroll-reveal-section.is-in-view .scroll-reveal-item {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
    .website-block-featured-services .featured-service-card {
        transition: transform 0.25s ease-out, box-shadow 0.25s ease-out;
    }
    .website-block-featured-services.is-in-view .featured-service-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 12px 24px -8px rgb(0 0 0 / 0.15), 0 4px 8px -4px rgb(0 0 0 / 0.08);
    }
    /* Icon: ruststand iets links; bij hover soepel terug naar 0, bij loslaten weer naar ruststand */
    .website-block-featured-services .featured-service-icon {
        transition: transform 0.35s cubic-bezier(0.34, 1.2, 0.64, 1);
    }
    .website-block-featured-services.is-in-view .featured-service-card:hover .featured-service-icon {
        animation: featured-service-icon-spring 0.55s cubic-bezier(0.34, 1.2, 0.64, 1) forwards;
    }
    @keyframes featured-service-icon-spring {
        0%   { transform: translateX(0); }
        32%  { transform: translateX(-8px); }
        58%  { transform: translateX(5px); }
        78%  { transform: translateX(-1px); }
        100% { transform: translateX(-3px); }
    }
    .dark .website-block-featured-services.is-in-view .featured-service-card:hover {
        box-shadow: 0 12px 24px -8px rgb(0 0 0 / 0.25), 0 4px 8px -4px rgb(0 0 0 / 0.15);
    }
</style>
@endpush

@push('scripts')
<script>
(function() {
    function initScrollReveal() {
        var sections = document.querySelectorAll('[data-scroll-reveal]');
        if (!sections.length) return;
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-in-view');
                }
            });
        }, { rootMargin: '0px 0px -80px 0px', threshold: 0.08 });
        sections.forEach(function(section) { observer.observe(section); });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initScrollReveal);
    } else {
        initScrollReveal();
    }
})();
</script>
@endpush
