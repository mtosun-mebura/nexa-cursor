@php
    $sectionKey = $sectionKey ?? 'component:website.comparison_table';
    $sectionData = (isset($homeSections) && is_array($homeSections) && isset($homeSections[$sectionKey]) && is_array($homeSections[$sectionKey]))
        ? $homeSections[$sectionKey]
        : [];
    $title = trim((string) ($sectionData['title'] ?? ''));
    $subtitle = trim((string) ($sectionData['subtitle'] ?? ''));
    $leftHeading = trim((string) ($sectionData['left_heading'] ?? 'Nadelen'));
    $rightHeading = trim((string) ($sectionData['right_heading'] ?? 'Voordelen'));
    if ($leftHeading === '') {
        $leftHeading = 'Nadelen';
    }
    if ($rightHeading === '') {
        $rightHeading = 'Voordelen';
    }
    $sanitizeHex = static function ($value, string $fallback): string {
        $v = trim((string) $value);
        if (preg_match('/^#([0-9a-fA-F]{3})$/', $v, $m)) {
            return '#'.$m[1][0].$m[1][0].$m[1][1].$m[1][1].$m[1][2].$m[1][2];
        }
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $v)) {
            return $v;
        }

        return $fallback;
    };
    $leftColor = $sanitizeHex($sectionData['left_color'] ?? '', '#dc2626');
    $rightColor = $sanitizeHex($sectionData['right_color'] ?? '', '#16a34a');
    $layout = trim((string) ($sectionData['layout'] ?? 'columns')) === 'stack' ? 'stack' : 'columns';
    $clampPercent = static function ($value, int $fallback): int {
        $n = (int) $value;
        if ($n < 30 || $n > 100) {
            return $fallback;
        }

        return $n;
    };
    $leftWidth = $clampPercent($sectionData['left_width_percent'] ?? 50, 50);
    $rightWidth = $clampPercent($sectionData['right_width_percent'] ?? 50, 50);
    $collectItems = static function (array $data, string $listKey, string $rowSide): array {
        $out = [];
        $raw = $data[$listKey] ?? null;
        if (is_array($raw)) {
            foreach ($raw as $item) {
                $text = '';
                if (is_string($item)) {
                    $text = trim($item);
                } elseif (is_array($item)) {
                    $text = trim((string) ($item['text'] ?? ''));
                }
                if ($text !== '') {
                    $out[] = $text;
                }
            }
        }
        if ($out !== []) {
            return $out;
        }
        $rows = isset($data['rows']) && is_array($data['rows']) ? $data['rows'] : [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $text = trim((string) ($row[$rowSide] ?? ''));
            if ($text !== '') {
                $out[] = $text;
            }
        }

        return $out;
    };
    $cons = $collectItems($sectionData, 'cons', 'left');
    $pros = $collectItems($sectionData, 'pros', 'right');
    $showCons = $cons !== [];
    $showPros = $pros !== [];
    $leftFr = $showCons ? $leftWidth : 0;
    $rightFr = $showPros ? $rightWidth : 0;
    if ($showCons && $showPros) {
        $trackWidth = min(100, $leftWidth + $rightWidth);
        $columnsMode = 'both';
    } elseif ($showCons) {
        $trackWidth = min(100, $leftWidth);
        $columnsMode = 'single';
    } else {
        $trackWidth = min(100, $rightWidth);
        $columnsMode = 'single';
    }
    $ease = 'cubic-bezier(0.22, 1, 0.36, 1)';
@endphp
@if($cons !== [] || $pros !== [])
<section class="nexa-comparison-table nexa-pros-cons pt-6 md:pt-8 pb-8 md:pb-12 scroll-reveal-section overflow-x-hidden" data-scroll-reveal>
    <div class="website-section-inner">
        @if($title !== '' || $subtitle !== '')
        <div class="text-center mb-8 max-w-3xl mx-auto nexa-pros-cons__intro">
            @if($title !== '')
            <h2 class="nexa-pros-cons__title nexa-pros-cons__reveal text-2xl md:text-3xl font-bold mb-3" style="--reveal-delay: 0ms;">{{ $title }}</h2>
            @endif
            @if($subtitle !== '')
            <p class="nexa-pros-cons__subtitle nexa-pros-cons__reveal" style="--reveal-delay: 120ms;">{{ $subtitle }}</p>
            @endif
        </div>
        @endif
        <div
            class="nexa-pros-cons__columns nexa-pros-cons__columns--{{ $layout }} nexa-pros-cons__columns--{{ $columnsMode }}"
            style="--track-width: {{ $trackWidth }}%; --left-fr: {{ $leftFr }}fr; --right-fr: {{ $rightFr }}fr;"
        >
            @if($cons !== [])
            <div class="nexa-pros-cons__card nexa-pros-cons__card--cons nexa-pros-cons__reveal nexa-pros-cons__reveal--from-left" style="--block-color: {{ $leftColor }}; --reveal-delay: 180ms;">
                <h3 class="nexa-pros-cons__heading">
                    <svg class="nexa-pros-cons__icon nexa-pros-cons__icon--con" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                    </svg>
                    {{ $leftHeading }}
                </h3>
                <ul class="nexa-pros-cons__list">
                    @foreach($cons as $i => $item)
                    <li class="nexa-pros-cons__item nexa-comparison-table__pain nexa-pros-cons__item-reveal" style="--item-delay: {{ 420 + ($i * 140) }}ms;">
                        <svg class="nexa-pros-cons__icon nexa-pros-cons__icon--con" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                        </svg>
                        <span>{{ $item }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
            @if($pros !== [])
            <div class="nexa-pros-cons__card nexa-pros-cons__card--pros nexa-pros-cons__reveal nexa-pros-cons__reveal--from-right" style="--block-color: {{ $rightColor }}; --reveal-delay: 280ms;">
                <h3 class="nexa-pros-cons__heading">
                    <svg class="nexa-pros-cons__icon nexa-pros-cons__icon--pro" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                    </svg>
                    {{ $rightHeading }}
                </h3>
                <ul class="nexa-pros-cons__list">
                    @foreach($pros as $i => $item)
                    <li class="nexa-pros-cons__item nexa-comparison-table__answer nexa-pros-cons__item-reveal" style="--item-delay: {{ 560 + ($i * 140) }}ms;">
                        <svg class="nexa-pros-cons__icon nexa-pros-cons__icon--pro" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>
                        <span>{{ $item }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
    </div>
</section>
@once
    @push('styles')
        <style>
            .nexa-pros-cons {
                background: #f8fafc;
                overflow-x: hidden;
                max-width: 100%;
            }
            .nexa-pros-cons__columns {
                --track-width: 100%;
                --left-fr: 1fr;
                --right-fr: 1fr;
                display: grid;
                width: min(100%, var(--track-width));
                max-width: 100%;
                margin-left: auto;
                margin-right: auto;
                gap: 1.25rem;
                align-items: stretch;
                box-sizing: border-box;
                overflow-x: hidden;
            }
            .nexa-pros-cons__title {
                color: #0f172a !important;
            }
            .nexa-pros-cons__subtitle {
                color: #475569 !important;
            }
            /* fr mag niet in calc(); --left-fr/--right-fr zijn daarom volledige track-maten (bijv. 30fr). */
            .nexa-pros-cons__columns--both:not(.nexa-pros-cons__columns--stack) {
                grid-template-columns: minmax(0, var(--left-fr)) minmax(0, var(--right-fr));
            }
            .nexa-pros-cons__columns--single,
            .nexa-pros-cons__columns--stack {
                grid-template-columns: minmax(0, 1fr);
            }
            .nexa-pros-cons__card {
                --block-color: #64748b;
                background: #ffffff;
                border: 1px solid color-mix(in srgb, var(--block-color) 38%, #e2e8f0);
                border-radius: 0.75rem;
                overflow: hidden;
                display: flex;
                flex-direction: column;
                box-sizing: border-box;
                min-width: 0;
                width: 100%;
            }
            @media (max-width: 639px) {
                .nexa-pros-cons__columns {
                    width: 100%;
                    grid-template-columns: minmax(0, 1fr);
                }
            }
            .nexa-pros-cons__heading {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                margin: 0;
                padding: 0.95rem 1.15rem;
                font-size: 0.95rem;
                font-weight: 700;
                line-height: 1.3;
                background: color-mix(in srgb, var(--block-color) 12%, #ffffff);
                color: var(--block-color);
            }
            .nexa-pros-cons__list {
                list-style: none;
                margin: 0;
                padding: 0.45rem 0 0.7rem;
            }
            .nexa-pros-cons__item {
                display: flex;
                align-items: flex-start;
                gap: 0.7rem;
                padding: 0.7rem 1.15rem;
                font-size: 0.95rem;
                line-height: 1.45;
            }
            .nexa-pros-cons__icon {
                width: 1.25rem;
                height: 1.25rem;
                flex-shrink: 0;
                margin-top: 0.12rem;
                color: var(--block-color);
            }
            .nexa-pros-cons__item.nexa-comparison-table__pain {
                color: #475569;
            }
            .nexa-pros-cons__item.nexa-comparison-table__answer {
                color: #0f172a;
                font-weight: 500;
            }
            /* Scroll reveal — Spark UI-achtige staggered motion, CSS-only */
            .nexa-pros-cons.scroll-reveal-section .nexa-pros-cons__reveal {
                opacity: 0;
                transform: translateY(28px);
                transition:
                    opacity 0.7s {{ $ease }},
                    transform 0.7s {{ $ease }};
                transition-delay: var(--reveal-delay, 0ms);
                will-change: opacity, transform;
            }
            .nexa-pros-cons.scroll-reveal-section .nexa-pros-cons__reveal--from-left {
                transform: translateX(-40px) translateY(12px);
            }
            .nexa-pros-cons.scroll-reveal-section .nexa-pros-cons__reveal--from-right {
                transform: translateX(40px) translateY(12px);
            }
            .nexa-pros-cons.scroll-reveal-section .nexa-pros-cons__item-reveal {
                opacity: 0;
                transform: translateY(14px);
                transition:
                    opacity 0.85s {{ $ease }},
                    transform 0.85s {{ $ease }};
                transition-delay: var(--item-delay, 0ms);
                will-change: opacity, transform;
            }
            .nexa-pros-cons.scroll-reveal-section.is-in-view .nexa-pros-cons__reveal,
            .nexa-pros-cons.scroll-reveal-section.is-in-view .nexa-pros-cons__item-reveal {
                opacity: 1;
                transform: translate(0, 0);
            }
            .nexa-pros-cons.scroll-reveal-section.is-in-view .nexa-pros-cons__card--cons:hover,
            .nexa-pros-cons.scroll-reveal-section.is-in-view .nexa-pros-cons__card--pros:hover {
                transform: translateY(-3px);
                box-shadow: 0 14px 28px -18px color-mix(in srgb, var(--block-color) 55%, #0f172a);
                transition: transform 0.25s ease, box-shadow 0.25s ease;
            }
            @media (prefers-reduced-motion: reduce) {
                .nexa-pros-cons.scroll-reveal-section .nexa-pros-cons__reveal,
                .nexa-pros-cons.scroll-reveal-section .nexa-pros-cons__item-reveal {
                    opacity: 1 !important;
                    transform: none !important;
                    transition: none !important;
                }
            }
            html.dark .nexa-pros-cons {
                background: #020617;
            }
            html.dark .nexa-pros-cons__title {
                color: #f8fafc !important;
            }
            html.dark .nexa-pros-cons__subtitle {
                color: #cbd5e1 !important;
            }
            html.dark .nexa-pros-cons__card {
                background: #0f172a;
                border-color: color-mix(in srgb, var(--block-color) 40%, #1e293b);
            }
            html.dark .nexa-pros-cons__heading {
                background: color-mix(in srgb, var(--block-color) 22%, #0f172a);
                color: color-mix(in srgb, var(--block-color) 45%, #ffffff);
            }
            html.dark .nexa-pros-cons__icon {
                color: color-mix(in srgb, var(--block-color) 35%, #ffffff);
            }
            html.dark .nexa-pros-cons__item.nexa-comparison-table__pain {
                color: #cbd5e1;
            }
            html.dark .nexa-pros-cons__item.nexa-comparison-table__answer {
                color: #f8fafc;
            }
        </style>
    @endpush
@endonce
@endif
