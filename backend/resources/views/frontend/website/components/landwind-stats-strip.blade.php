@php
    $sectionKey = $sectionKey ?? 'component:landwind.stats_strip';
    $saved = (isset($homeSections) && is_array($homeSections) && isset($homeSections[$sectionKey]) && is_array($homeSections[$sectionKey]))
        ? $homeSections[$sectionKey]
        : [];
    $defaults = app(\App\Services\FrontendComponentService::class)->defaultSectionData('landwind.stats_strip');
    $data = array_merge($defaults, $saved);
    $items = isset($data['items']) && is_array($data['items']) && $data['items'] !== []
        ? array_values($data['items'])
        : ($defaults['items'] ?? []);
    $primary = $themeSettings['primary_color'] ?? '#7e3af2';
@endphp
@include('frontend.website.components.partials.theme-component-scroll-reveal')
<section class="py-12 md:py-16 bg-gray-50 dark:bg-gray-800/60 theme-scroll-reveal" data-theme-component="landwind.stats_strip" data-theme-anim="pop" data-scroll-reveal>
    <div class="website-section-inner">
        <div class="theme-fade mx-auto mb-10 max-w-2xl text-center">
            <p class="mb-2 text-sm font-semibold uppercase tracking-[0.2em]" style="color: {{ $primary }}">{{ $data['eyebrow'] ?? 'In cijfers' }}</p>
            <h2 class="mb-2 text-3xl font-extrabold text-gray-900 dark:text-white">{{ $data['title'] ?? '' }}</h2>
            @if(!empty($data['subtitle']))
                <p class="text-gray-500 dark:text-gray-400">{{ $data['subtitle'] }}</p>
            @endif
        </div>
        <div class="grid grid-cols-2 gap-8 md:grid-cols-4">
            @foreach($items as $i => $item)
                @php
                    $value = trim((string) ($item['value'] ?? ''));
                    $suffix = (string) ($item['suffix'] ?? '');
                    $label = trim((string) ($item['label'] ?? ''));
                    if ($value === '') continue;
                @endphp
                <div class="text-center" style="--theme-reveal-delay: {{ $i * 120 }}ms;">
                    <p class="theme-stat-value text-4xl md:text-5xl font-extrabold tabular-nums text-gray-900 dark:text-white"
                       data-theme-count="{{ $value }}"
                       data-theme-count-suffix="{{ $suffix }}">0{{ $suffix }}</p>
                    <span class="theme-stat-rule mx-auto mt-3 mb-2 block h-0.5 w-10" style="background: {{ $primary }}"></span>
                    @if($label !== '')
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>
