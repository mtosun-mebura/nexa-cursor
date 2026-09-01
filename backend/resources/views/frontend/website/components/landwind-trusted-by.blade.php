@php
    $sectionKey = $sectionKey ?? 'component:landwind.trusted_by';
    $saved = (isset($homeSections) && is_array($homeSections) && isset($homeSections[$sectionKey]) && is_array($homeSections[$sectionKey]))
        ? $homeSections[$sectionKey]
        : [];
    $defaults = app(\App\Services\FrontendComponentService::class)->defaultSectionData('landwind.trusted_by');
    $data = array_merge($defaults, $saved);
    $items = isset($data['items']) && is_array($data['items']) && $data['items'] !== []
        ? array_values($data['items'])
        : ($defaults['items'] ?? []);
    $primary = $themeSettings['primary_color'] ?? '#7e3af2';
    $brands = [];
    foreach ($items as $item) {
        $n = trim((string) ($item['name'] ?? ''));
        $logo = trim((string) ($item['image_url'] ?? ''));
        if ($n !== '' || $logo !== '') {
            $brands[] = ['name' => $n, 'logo' => $logo];
        }
    }
@endphp
@include('frontend.website.components.partials.theme-component-scroll-reveal')
<section class="py-12 md:py-16 bg-gray-50 dark:bg-gray-800/60 theme-scroll-reveal" data-theme-component="landwind.trusted_by" data-theme-anim="marquee" data-scroll-reveal @if($brands !== []) style="--theme-marquee-delay: {{ count($brands) * 70 + 280 }}ms;" @endif>
    <div class="website-section-inner text-center">
        <div class="theme-fade mb-8" style="--theme-reveal-delay: 0ms;">
            <p class="mb-2 text-xs font-bold uppercase tracking-[0.28em] text-gray-400 dark:text-gray-500">{{ $data['eyebrow'] ?? 'Used by' }}</p>
            <h2 class="mb-2 text-2xl md:text-3xl font-extrabold text-gray-900 dark:text-white">{{ $data['title'] ?? 'Vertrouwd door groeibedrijven' }}</h2>
            @if(!empty($data['subtitle']))
                <p class="text-gray-500 dark:text-gray-400">{{ $data['subtitle'] }}</p>
            @endif
        </div>
        @if($brands !== [])
        <div class="theme-marquee">
            <div class="theme-marquee__track">
                @foreach([0, 1] as $copy)
                <ul class="flex items-center gap-3 md:gap-4 {{ $copy === 1 ? 'ms-3 md:ms-4' : '' }}" @if($copy === 1) aria-hidden="true" @endif>
                    @foreach($brands as $i => $brand)
                        <li
                            class="theme-brand-item shrink-0 rounded-full border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900 px-5 py-2.5 text-sm md:text-base font-semibold tracking-wide text-gray-500 dark:text-gray-300 shadow-sm inline-flex items-center gap-2"
                            style="--theme-reveal-delay: {{ $i * 70 }}ms;"
                        >
                            @if($brand['logo'] !== '')
                                <img src="{{ $brand['logo'] }}" alt="" class="h-6 w-auto max-w-[7rem] object-contain">
                            @else
                                <span class="inline-block h-1.5 w-1.5 rounded-full" style="background: {{ $primary }}"></span>
                            @endif
                            @if($brand['name'] !== '')
                                {{ $brand['name'] }}
                            @endif
                        </li>
                    @endforeach
                </ul>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</section>
