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
    $revealDuration = match($animationSpeed) { 'fast' => '0.5s', 'normal' => '0.65s', 'slow' => '0.8s', 'slower' => '1s', default => '0.8s' };
    $revealFirstCardDelayMs = 90;
    $revealDelayStepMs = match($animationSpeed) { 'fast' => 100, 'normal' => 150, 'slow' => 200, 'slower' => 260, default => 200 };
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
<section class="website-block website-block-featured-services py-12 md:py-16 scroll-reveal-section" data-scroll-reveal>
    <div class="container-custom">
        <div class="max-w-4xl mx-auto text-center mb-10 md:mb-14 scroll-reveal-item" style="transition: opacity {{ $revealDuration }} cubic-bezier(0.25, 0.46, 0.45, 0.94), transform {{ $revealDuration }} cubic-bezier(0.25, 0.46, 0.45, 0.94); transition-delay: 0ms;">
            @if($title !== '')
                <h2 class="font-bold text-gray-900 dark:text-white mb-3 leading-tight" style="font-family: var(--theme-font-heading, inherit); font-size: {{ $titleFontPx }}px;">{!! e($title) !!}</h2>
            @endif
            @if($subtitle !== '')
                <p class="text-gray-600 dark:text-gray-300 leading-relaxed" style="font-size: {{ $subtitleFontPx }}px;">{!! e($subtitle) !!}</p>
            @endif
        </div>
        @if(count($items) > 0)
            <div class="flex {{ $alignClass }} w-full">
                <div
                    class="featured-services-cards flex flex-wrap justify-center gap-6 lg:gap-8 w-full max-w-full px-4 lg:px-6"
                    data-blocks-per-row="{{ $blocksPerRow }}"
                    @if($blocksRowWidthPct < 100) style="max-width: {{ $blocksRowWidthPct }}%;" @endif
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
                        $revealStyle = 'transition: opacity ' . $revealDuration . ' cubic-bezier(0.25, 0.46, 0.45, 0.94), transform ' . $revealDuration . ' cubic-bezier(0.25, 0.46, 0.45, 0.94); transition-delay: ' . $cardRevealDelayMs . 'ms;';
                    @endphp
                    <div class="featured-service-item min-w-0">
                        <div class="scroll-reveal-item min-w-0 h-full" style="{{ $revealStyle }}" data-scroll-reveal-delay="{{ $index }}">
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
                                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2 break-words [overflow-wrap:anywhere] leading-snug" style="font-size: {{ $itemTitleFontPx }}px;">{!! e($itemTitle) !!}</h3>
                                @endif
                                @if($itemDesc !== '')
                                    <p class="text-gray-600 dark:text-gray-300 leading-relaxed break-words [overflow-wrap:anywhere]" style="font-size: {{ $itemDescFontPx }}px;">{!! nl2br(e($itemDesc)) !!}</p>
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
    /* Kaarten per rij: flex + justify-center zodat een onvolledige laatste rij gecentreerd is (o.a. 3+2 bij 3 kolommen) */
    .website-block-featured-services .featured-services-cards > .featured-service-item {
        width: 100%;
        min-width: 0;
    }
    /* 2 kolommen vanaf sm; gap-6 tot lg, daarna gap-8 */
    @media (min-width: 640px) and (max-width: 1023px) {
        .website-block-featured-services .featured-services-cards[data-blocks-per-row="2"] > .featured-service-item {
            flex: 0 1 calc((100% - 1.5rem) / 2);
            max-width: calc((100% - 1.5rem) / 2);
        }
    }
    @media (min-width: 1024px) {
        .website-block-featured-services .featured-services-cards[data-blocks-per-row="2"] > .featured-service-item {
            flex: 0 1 calc((100% - 2rem) / 2);
            max-width: calc((100% - 2rem) / 2);
        }
    }
    /* 3 kolommen vanaf md */
    @media (min-width: 768px) and (max-width: 1023px) {
        .website-block-featured-services .featured-services-cards[data-blocks-per-row="3"] > .featured-service-item {
            flex: 0 1 calc((100% - 3rem) / 3);
            max-width: calc((100% - 3rem) / 3);
        }
    }
    @media (min-width: 1024px) {
        .website-block-featured-services .featured-services-cards[data-blocks-per-row="3"] > .featured-service-item {
            flex: 0 1 calc((100% - 4rem) / 3);
            max-width: calc((100% - 4rem) / 3);
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
        overflow-wrap: anywhere;
        word-break: break-word;
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
