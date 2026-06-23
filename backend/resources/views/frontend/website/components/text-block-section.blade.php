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
    // Halve breedte alleen als er daadwerkelijk een kolom naast de tekst is (component of afbeelding)
    $textMaxWidthClass = ($showSideColumn && in_array($alignment, ['left', 'right'], true)) ? 'max-w-3xl' : '';
@endphp
<style>
    @keyframes text-block-fade-in {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    .text-block-content-fade-in {
        opacity: 0;
    }
    .text-block-content-fade-in.text-block-in-view {
        animation: text-block-fade-in 2s ease-out forwards;
    }
</style>
<section class="text-block-section py-12 md:py-16 max-w-full overflow-x-hidden">
    <div class="text-block-section__inner mx-auto box-border w-full max-w-full md:max-w-none" style="width: {{ $widthPercent }}%; max-width: 100%;">
        <div class="website-section-inner max-w-6xl mx-auto">
            @if($showSideColumn)
            {{-- Links of rechts: tekst en optioneel afbeelding/component op de helft van de pagina --}}
            <div class="text-block-content-fade-in flex flex-col items-center gap-2 lg:gap-3.5 py-4 lg:pt-5 lg:pb-10">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-start w-full">
                    @if($alignment === 'right')
                    {{-- Tekst rechts: afbeelding/component links; beide bovenaan uitgelijnd --}}
                    <div class="order-2 lg:order-1 w-full min-w-0 space-y-6 flex flex-col items-stretch">
                        @if($showSideImage)
                        <div class="website-image-carousel-media flex justify-center lg:justify-start w-full">
                            <img src="{{ $imageUrl }}" alt="" class="website-image-carousel-fit rounded-xl">
                        </div>
                        @endif
                        @if($showSideComponent)
                        @include('frontend.website.components.email-template-section', ['sectionData' => $sideSectionData, 'sectionKey' => $sideKey, 'emailTemplate' => $sideTemplate, 'formFields' => $sideFormFields, 'embeddedInTextBlock' => true])
                        @endif
                    </div>
                    <div class="order-1 lg:order-2 prose prose-gray dark:prose-invert format format-lg dark:format-invert max-w-none {{ $textAlignClass }}">
                        {!! $content !!}
                    </div>
                    @else
                    {{-- Tekst links: afbeelding/component rechts --}}
                    <div class="prose prose-gray dark:prose-invert format format-lg dark:format-invert max-w-none {{ $textAlignClass }}">
                        {!! $content !!}
                    </div>
                    <div class="w-full min-w-0 space-y-6 flex flex-col items-stretch">
                        @if($showSideImage)
                        <div class="website-image-carousel-media flex justify-center lg:justify-end w-full">
                            <img src="{{ $imageUrl }}" alt="" class="website-image-carousel-fit rounded-xl">
                        </div>
                        @endif
                        @if($showSideComponent)
                        @include('frontend.website.components.email-template-section', ['sectionData' => $sideSectionData, 'sectionKey' => $sideKey, 'emailTemplate' => $sideTemplate, 'formFields' => $sideFormFields, 'embeddedInTextBlock' => true])
                        @endif
                    </div>
                    @endif
                </div>
            </div>
            @else
            {{-- Alleen tekst: volledige breedte (geen component/afbeelding naast) --}}
            <div class="text-block-content-fade-in flex flex-col items-center gap-2 lg:gap-3.5 py-4 lg:pt-5 lg:pb-10">
                <div class="flex {{ $blockAlignClass }} w-full">
                    <div class="prose prose-gray dark:prose-invert format format-lg dark:format-invert max-w-none w-full {{ $textAlignClass }}">
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
