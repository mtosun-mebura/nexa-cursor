@php
    $sectionKey = $sectionKey ?? 'component:vue_material.stats_counters';
    $saved = (isset($homeSections) && is_array($homeSections) && isset($homeSections[$sectionKey]) && is_array($homeSections[$sectionKey]))
        ? $homeSections[$sectionKey]
        : [];
    $defaults = app(\App\Services\FrontendComponentService::class)->defaultSectionData('vue_material.stats_counters');
    $data = array_merge($defaults, $saved);
    $items = isset($data['items']) && is_array($data['items']) && $data['items'] !== []
        ? array_values($data['items'])
        : ($defaults['items'] ?? []);
    $accent = $themeSettings['primary_color'] ?? '#e91e63';
@endphp
@include('frontend.website.components.partials.theme-component-scroll-reveal')
<section class="py-16 md:py-24 bg-[#f8f9fa] dark:bg-gray-900 theme-scroll-reveal" data-theme-component="vue_material.stats_counters" data-theme-anim="count" data-scroll-reveal>
    <div class="website-section-inner">
        <div class="theme-fade mx-auto mb-12 max-w-2xl text-center">
            <p class="mb-2 text-sm font-medium uppercase tracking-widest" style="color: {{ $accent }}">{{ $data['eyebrow'] ?? 'Impact' }}</p>
            <h2 class="mb-3 text-3xl md:text-4xl font-bold text-gray-800 dark:text-white">{{ $data['title'] ?? '' }}</h2>
            @if(!empty($data['subtitle']))
                <p class="text-gray-500 dark:text-gray-400">{{ $data['subtitle'] }}</p>
            @endif
        </div>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($items as $i => $item)
                @php
                    $value = trim((string) ($item['value'] ?? ''));
                    $suffix = (string) ($item['suffix'] ?? '');
                    $label = trim((string) ($item['label'] ?? ''));
                    $decimals = (string) ($item['decimals'] ?? '0');
                    if ($value === '') continue;
                @endphp
                <article class="theme-reveal-item rounded-xl bg-white dark:bg-gray-800 px-6 py-8 text-center shadow-[0_8px_26px_-4px_rgba(0,0,0,0.18)]" style="--theme-reveal-delay: {{ $i * 110 }}ms;">
                    <p class="theme-count-blur text-4xl font-bold tabular-nums" style="color: {{ $accent }}"
                       data-theme-count="{{ $value }}"
                       data-theme-count-suffix="{{ $suffix }}"
                       data-theme-count-decimals="{{ $decimals }}">0{{ $suffix }}</p>
                    @if($label !== '')
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $label }}</p>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</section>
