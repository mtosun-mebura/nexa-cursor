@php
    $content = $sectionData['content'] ?? '';
    $alignment = $sectionData['alignment'] ?? 'left';
    $sideKey = $sectionData['side_component_key'] ?? '';
    $imageUrlRaw = $sectionData['image_url'] ?? '';
    $imageUrl = $imageUrlRaw !== '' ? app(\App\Services\WebsiteBuilderService::class)->storageUrlToDisplayUrl($imageUrlRaw) : '';
    $widthPercent = (int) ($sectionData['width_percent'] ?? 100);
    $widthPercent = max(30, min(100, $widthPercent));
    $emailTemplateBySectionKey = $emailTemplateBySectionKey ?? [];
    $sideTemplate = null;
    if ($sideKey !== '' && in_array($alignment, ['left', 'right'], true)) {
        $sideTemplate = $emailTemplateBySectionKey[$sideKey] ?? null;
    }
    $showSideComponent = $sideTemplate !== null;
    $showSideImage = $imageUrl !== '' && in_array($alignment, ['left', 'right'], true);
    $showSideColumn = $showSideComponent || $showSideImage;
    $sideSectionData = $showSideComponent ? ($homeSections[$sideKey] ?? []) : [];
    $sideFormFields = $sideTemplate ? $sideTemplate->getOrderedFormFields() : collect();
    $textAlignClass = match($alignment) {
        'center' => 'text-center',
        'right' => 'text-right',
        'full' => 'text-left',
        default => 'text-left',
    };
    $blockAlignClass = match($alignment) {
        'center' => 'justify-center',
        'right' => 'justify-end',
        'full' => 'justify-start',
        default => 'justify-start',
    };
    $isCenteredReading = $alignment === 'center' && ! $showSideColumn;
    $textMaxWidthClass = ($showSideColumn && in_array($alignment, ['left', 'right'], true)) ? 'max-w-none' : '';
@endphp
<style>
    /* Alinea's vliegen één voor één van links naar binnen; het plaatje krijgt een eigen
       tegengestelde animatie (van rechts, met lichte inzoom) zodra de sectie in beeld komt. */
    .text-block-content-fade-in .prose p {
        opacity: 0;
        transform: translateX(-42px);
        transition: opacity 0.7s cubic-bezier(0.16, 1, 0.3, 1), transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
        will-change: opacity, transform;
    }
    .text-block-content-fade-in .prose p:nth-child(1) { transition-delay: 0.05s; }
    .text-block-content-fade-in .prose p:nth-child(2) { transition-delay: 0.25s; }
    .text-block-content-fade-in .prose p:nth-child(3) { transition-delay: 0.45s; }
    .text-block-content-fade-in .prose p:nth-child(4) { transition-delay: 0.65s; }
    .text-block-content-fade-in .prose p:nth-child(n+5) { transition-delay: 0.85s; }
    .text-block-content-fade-in.text-block-in-view .prose p {
        opacity: 1;
        transform: translateX(0);
    }

    .text-block-content-fade-in .text-block-side-image {
        opacity: 0;
        transform: translateX(56px) scale(0.95);
        transition: opacity 0.85s cubic-bezier(0.16, 1, 0.3, 1) 0.15s, transform 0.9s cubic-bezier(0.16, 1, 0.3, 1) 0.15s;
        will-change: opacity, transform;
    }
    .text-block-content-fade-in .text-block-side-image--from-left {
        transform: translateX(-56px) scale(0.95);
    }
    .text-block-content-fade-in.text-block-in-view .text-block-side-image {
        opacity: 1;
        transform: translateX(0) scale(1);
    }

    @media (prefers-reduced-motion: reduce) {
        .text-block-content-fade-in .prose p,
        .text-block-content-fade-in .text-block-side-image {
            opacity: 1;
            transform: none;
            transition: none;
        }
    }
</style>
<section class="text-block-section pt-6 md:pt-8 pb-8 md:pb-12 max-w-full overflow-x-hidden">
    <div class="text-block-section__inner mx-auto box-border w-full max-w-full" style="width: {{ $widthPercent }}%;">
        <div class="website-section-inner mx-auto w-full max-w-full">
            @if($showSideColumn)
            {{-- Links of rechts: tekst en optioneel afbeelding/component op de helft van de pagina --}}
            <div class="text-block-content-fade-in flex flex-col items-center gap-2 lg:gap-3.5 py-4 lg:pt-5 lg:pb-10">
                @if($alignment === 'right')
                {{-- Tekst rechts: afbeelding/component links; beide bovenaan uitgelijnd --}}
                <div class="grid grid-cols-1 lg:grid-cols-[2fr_3fr] gap-8 lg:gap-12 items-start w-full">
                    <div class="order-2 lg:order-1 w-full min-w-0 space-y-6 flex flex-col items-stretch">
                        @if($showSideImage)
                        <div class="text-block-side-image text-block-side-image--from-left w-full overflow-hidden rounded-2xl">
                            <img src="{{ $imageUrl }}" alt="" class="block w-full h-full min-h-[16rem] lg:min-h-[22rem] object-cover">
                        </div>
                        @endif
                        @if($showSideComponent)
                        @include('frontend.website.components.email-template-section', ['sectionData' => $sideSectionData, 'sectionKey' => $sideKey, 'emailTemplate' => $sideTemplate, 'formFields' => $sideFormFields, 'embeddedInTextBlock' => true])
                        @endif
                    </div>
                    <div class="order-1 lg:order-2 prose prose-gray dark:prose-invert format format-lg dark:format-invert max-w-none {{ $textAlignClass }}">
                        {!! $content !!}
                    </div>
                </div>
                @else
                {{-- Tekst links: afbeelding/component rechts --}}
                <div class="grid grid-cols-1 lg:grid-cols-[3fr_2fr] gap-8 lg:gap-12 items-start w-full">
                    <div class="prose prose-gray dark:prose-invert format format-lg dark:format-invert max-w-none {{ $textAlignClass }}">
                        {!! $content !!}
                    </div>
                    <div class="w-full min-w-0 space-y-6 flex flex-col items-stretch">
                        @if($showSideImage)
                        <div class="text-block-side-image w-full overflow-hidden rounded-2xl">
                            <img src="{{ $imageUrl }}" alt="" class="block w-full h-full min-h-[16rem] lg:min-h-[22rem] object-cover">
                        </div>
                        @endif
                        @if($showSideComponent)
                        @include('frontend.website.components.email-template-section', ['sectionData' => $sideSectionData, 'sectionKey' => $sideKey, 'emailTemplate' => $sideTemplate, 'formFields' => $sideFormFields, 'embeddedInTextBlock' => true])
                        @endif
                    </div>
                </div>
                @endif
            </div>
            @else
            {{-- Alleen tekst: volle breedte, of gecentreerde leeskolom in het midden van het scherm --}}
            <div class="text-block-content-fade-in flex flex-col items-center gap-2 lg:gap-3.5 py-4 lg:pt-5 lg:pb-10">
                <div class="flex {{ $blockAlignClass }} w-full">
                    <div class="prose prose-gray dark:prose-invert format format-lg dark:format-invert {{ $isCenteredReading ? 'max-w-3xl mx-auto text-center' : 'max-w-none w-full' }} {{ $textAlignClass }} {{ $textMaxWidthClass }}">
                        {!! $content !!}
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
    <script>
        (function() {
            var section = document.currentScript.closest('.text-block-section');
            var el = section ? section.querySelector('.text-block-content-fade-in') : null;
            if (!el) return;
            var opts = { rootMargin: '0px 0px -50px 0px', threshold: 0.1 };
            if (typeof window.nexaObserveWhenVisible === 'function') {
                window.nexaObserveWhenVisible(el, function(target) {
                    target.classList.add('text-block-in-view');
                }, opts);
                return;
            }
            if (!('IntersectionObserver' in window)) {
                el.style.opacity = '1';
                return;
            }
            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('text-block-in-view');
                        observer.unobserve(entry.target);
                    }
                });
            }, opts);
            observer.observe(el);
        })();
    </script>
</section>
