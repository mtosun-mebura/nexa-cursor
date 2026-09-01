@php
    $sectionKey = $sectionKey ?? 'component:website.screenshot_gallery';
    $sectionData = (isset($homeSections) && is_array($homeSections) && isset($homeSections[$sectionKey]) && is_array($homeSections[$sectionKey]))
        ? $homeSections[$sectionKey]
        : [];
    $title = trim((string) ($sectionData['title'] ?? ''));
    $subtitle = trim((string) ($sectionData['subtitle'] ?? ''));
    $layout = trim((string) ($sectionData['layout'] ?? 'stack'));
    if (! in_array($layout, ['stack', 'grid'], true)) {
        $layout = 'stack';
    }
    $items = isset($sectionData['items']) && is_array($sectionData['items']) ? array_values($sectionData['items']) : [];
    $displayUrl = static function (?string $url): string {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }

        return app(\App\Services\WebsiteBuilderService::class)->storageUrlToDisplayUrl($url);
    };
    $visibleItems = [];
    foreach ($items as $item) {
        if (! is_array($item)) {
            continue;
        }
        $src = $displayUrl($item['image_url'] ?? '');
        if ($src === '') {
            continue;
        }
        $crop = trim((string) ($item['crop'] ?? 'none'));
        if (! in_array($crop, ['none', 'phone', 'portal'], true)) {
            $crop = 'none';
        }
        $itemUrl = trim((string) ($item['url'] ?? ''));
        $visibleItems[] = [
            'src' => $src,
            'caption' => trim((string) ($item['caption'] ?? '')),
            'alt' => trim((string) ($item['alt'] ?? $item['caption'] ?? 'Screenshot')),
            'crop' => $crop,
            'url' => $itemUrl,
        ];
    }
@endphp
@if($visibleItems !== [])
<section class="nexa-screenshot-gallery pt-6 md:pt-8 pb-8 md:pb-10 bg-white dark:bg-gray-900" data-layout="{{ $layout }}">
    <div class="website-section-inner">
        @if($title !== '' || $subtitle !== '')
        <div class="text-center mb-6 max-w-3xl mx-auto">
            @if($title !== '')
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-3">{{ $title }}</h2>
            @endif
            @if($subtitle !== '')
            <p class="text-gray-600 dark:text-gray-300">{{ $subtitle }}</p>
            @endif
        </div>
        @endif
        <div class="nexa-screenshot-gallery__grid nexa-screenshot-gallery__grid--{{ $layout }}">
            @foreach($visibleItems as $item)
            <figure class="nexa-screenshot-gallery__item">
                <button type="button"
                    class="nexa-screenshot-gallery__link nexa-screenshot-gallery__zoom"
                    data-lightbox-src="{{ $item['src'] }}"
                    data-lightbox-alt="{{ $item['alt'] }}"
                    data-lightbox-caption="{{ $item['caption'] }}"
                    aria-label="Vergroot: {{ $item['alt'] !== '' ? $item['alt'] : 'screenshot' }}">
                    <div class="nexa-screenshot-gallery__frame nexa-screenshot-gallery__frame--{{ $item['crop'] }}">
                        <img src="{{ $item['src'] }}" alt="{{ $item['alt'] }}" loading="lazy" decoding="async">
                    </div>
                </button>
                @if($item['caption'] !== '')
                    @if($item['url'] !== '')
                    <figcaption><a href="{{ $item['url'] }}">{{ $item['caption'] }}</a></figcaption>
                    @else
                    <figcaption>{{ $item['caption'] }}</figcaption>
                    @endif
                @endif
            </figure>
            @endforeach
        </div>
    </div>
</section>
@once
    @push('styles')
        <style>
            .nexa-screenshot-gallery__grid {
                display: grid;
                gap: 1.35rem;
            }
            .nexa-screenshot-gallery__grid--stack {
                grid-template-columns: 1fr;
                max-width: 52rem;
                margin-left: auto;
                margin-right: auto;
                gap: 0.85rem;
            }
            .nexa-screenshot-gallery__grid--grid {
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            }
            .nexa-screenshot-gallery__item { margin: 0; }
            .nexa-screenshot-gallery__grid--grid .nexa-screenshot-gallery__item,
            .nexa-screenshot-gallery__grid--grid .nexa-screenshot-gallery__link {
                height: 100%;
                display: flex;
                flex-direction: column;
            }
            .nexa-screenshot-gallery__frame {
                border-radius: 1rem;
                overflow: hidden;
                border: 1px solid rgba(148, 163, 184, 0.28);
                background: #111827;
                aspect-ratio: 5 / 4;
                width: 100%;
            }
            .nexa-screenshot-gallery__frame img {
                display: block;
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            .nexa-screenshot-gallery__frame--phone img,
            .nexa-screenshot-gallery__frame--portal img {
                transform: scale(1.12);
            }
            .nexa-screenshot-gallery__frame--phone img {
                object-position: 58% 38%;
                transform-origin: 58% 38%;
            }
            .nexa-screenshot-gallery__frame--portal img {
                object-position: 62% 42%;
                transform-origin: 62% 42%;
            }
            .nexa-screenshot-gallery__link {
                display: block;
                width: 100%;
                padding: 0;
                border: 0;
                background: transparent;
                text-decoration: none;
                color: inherit;
                cursor: zoom-in;
                text-align: inherit;
            }
            .nexa-screenshot-gallery figcaption {
                margin: 0.55rem 0 0;
                min-height: 2.6em;
                font-size: 0.85rem;
                font-weight: 600;
                color: #334155;
                letter-spacing: 0.02em;
                text-align: center;
            }
            .nexa-screenshot-gallery figcaption a {
                color: inherit;
                text-decoration: none;
            }
            .nexa-screenshot-gallery figcaption a:hover,
            .nexa-screenshot-gallery__link:hover + figcaption,
            .nexa-screenshot-gallery__link:hover + figcaption a {
                color: var(--theme-primary, #2563eb);
            }
            html.dark .nexa-screenshot-gallery figcaption { color: #cbd5e1; }
            html.dark .nexa-screenshot-gallery figcaption a:hover,
            html.dark .nexa-screenshot-gallery__link:hover + figcaption,
            html.dark .nexa-screenshot-gallery__link:hover + figcaption a { color: #93c5fd; }
            .nexa-screenshot-lightbox {
                position: fixed;
                inset: 0;
                z-index: 100200;
                display: none;
                align-items: center;
                justify-content: center;
                padding: 1.25rem;
                background: rgba(15, 23, 42, 0.82);
                backdrop-filter: blur(4px);
            }
            .nexa-screenshot-lightbox.is-open {
                display: flex;
            }
            .nexa-screenshot-lightbox__inner {
                position: relative;
                max-width: min(96vw, 1200px);
                max-height: 92vh;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 0.75rem;
            }
            .nexa-screenshot-lightbox__img {
                display: block;
                max-width: 96vw;
                max-height: calc(92vh - 3rem);
                width: auto;
                height: auto;
                object-fit: contain;
                border-radius: 0.75rem;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.55);
                background: #0f172a;
            }
            .nexa-screenshot-lightbox__caption {
                margin: 0;
                color: #e2e8f0;
                font-size: 0.95rem;
                font-weight: 600;
                text-align: center;
            }
            .nexa-screenshot-lightbox__close {
                position: absolute;
                top: -0.65rem;
                right: -0.65rem;
                width: 2.5rem;
                height: 2.5rem;
                border-radius: 999px;
                border: 0;
                background: #fff;
                color: #0f172a;
                font-size: 1.5rem;
                line-height: 1;
                cursor: pointer;
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
            }
            .nexa-screenshot-lightbox__close:hover {
                background: #e2e8f0;
            }
        </style>
    @endpush
    <div id="nexa-screenshot-lightbox" class="nexa-screenshot-lightbox" hidden aria-hidden="true" role="dialog" aria-modal="true" aria-label="Vergrote screenshot">
        <div class="nexa-screenshot-lightbox__inner">
            <button type="button" class="nexa-screenshot-lightbox__close" data-lightbox-close aria-label="Sluiten">&times;</button>
            <img class="nexa-screenshot-lightbox__img" alt="">
            <p class="nexa-screenshot-lightbox__caption" hidden></p>
        </div>
    </div>
    @push('scripts')
        <script>
            (function () {
                var overlay = document.getElementById('nexa-screenshot-lightbox');
                if (!overlay || overlay.getAttribute('data-bound') === '1') return;
                overlay.setAttribute('data-bound', '1');
                var img = overlay.querySelector('.nexa-screenshot-lightbox__img');
                var captionEl = overlay.querySelector('.nexa-screenshot-lightbox__caption');

                function closeLightbox() {
                    overlay.classList.remove('is-open');
                    overlay.hidden = true;
                    overlay.setAttribute('aria-hidden', 'true');
                    document.body.style.overflow = '';
                    if (img) img.removeAttribute('src');
                }

                function openLightbox(src, alt, caption) {
                    if (!src || !img) return;
                    img.src = src;
                    img.alt = alt || '';
                    if (captionEl) {
                        captionEl.textContent = caption || '';
                        captionEl.hidden = !caption;
                    }
                    overlay.hidden = false;
                    overlay.classList.add('is-open');
                    overlay.setAttribute('aria-hidden', 'false');
                    document.body.style.overflow = 'hidden';
                }

                document.addEventListener('click', function (e) {
                    var trigger = e.target.closest && e.target.closest('.nexa-screenshot-gallery__zoom');
                    if (trigger) {
                        e.preventDefault();
                        openLightbox(
                            trigger.getAttribute('data-lightbox-src'),
                            trigger.getAttribute('data-lightbox-alt'),
                            trigger.getAttribute('data-lightbox-caption')
                        );
                        return;
                    }
                    if (e.target === overlay || (e.target.closest && e.target.closest('[data-lightbox-close]'))) {
                        closeLightbox();
                    }
                });

                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && overlay.classList.contains('is-open')) {
                        closeLightbox();
                    }
                });
            })();
        </script>
    @endpush
@endonce
@endif
