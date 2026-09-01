@php
    $sectionKey = $sectionKey ?? 'component:vue_material.elevated_cards';
    $saved = (isset($homeSections) && is_array($homeSections) && isset($homeSections[$sectionKey]) && is_array($homeSections[$sectionKey]))
        ? $homeSections[$sectionKey]
        : [];
    $defaults = app(\App\Services\FrontendComponentService::class)->defaultSectionData('vue_material.elevated_cards');
    $data = array_merge($defaults, $saved);
    $items = isset($data['items']) && is_array($data['items']) && $data['items'] !== []
        ? array_values($data['items'])
        : ($defaults['items'] ?? []);
    $fallbackAccent = $themeSettings['primary_color'] ?? '#e91e63';
@endphp
@include('frontend.website.components.partials.theme-component-scroll-reveal')
<section class="py-16 md:py-24 bg-[#f8f9fa] dark:bg-gray-900 theme-scroll-reveal" data-theme-component="vue_material.elevated_cards" data-theme-anim="drop" data-scroll-reveal>
    <div class="website-section-inner">
        <div class="mx-auto mb-16 max-w-2xl text-center theme-fade" style="--theme-reveal-delay: 0ms;">
            <p class="mb-2 text-sm font-medium uppercase tracking-widest text-[#e91e63]">{{ $data['eyebrow'] ?? 'Material cards' }}</p>
            <h2 class="mb-3 text-3xl md:text-4xl font-bold text-gray-800 dark:text-white">{{ $data['title'] ?? 'Drie stappen naar een live site' }}</h2>
            @if(!empty($data['subtitle']))
                <p class="text-gray-500 dark:text-gray-400">{{ $data['subtitle'] }}</p>
            @endif
        </div>
        <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-3">
            @foreach($items as $index => $item)
                @php
                    $title = trim((string) ($item['title'] ?? ''));
                    $text = trim((string) ($item['text'] ?? ''));
                    $photo = trim((string) ($item['image_url'] ?? ''));
                    if ($title === '') continue;
                    $accent = trim((string) ($item['accent'] ?? '')) ?: $fallbackAccent;
                @endphp
                <article class="theme-reveal-item relative rounded-xl bg-white dark:bg-gray-800 px-6 pb-8 {{ $photo !== '' ? 'pt-6' : 'pt-12' }} text-center shadow-[0_8px_26px_-4px_rgba(0,0,0,0.18)]" style="--theme-reveal-delay: {{ 140 + ($index * 120) }}ms;">
                    @if($photo !== '')
                        <img src="{{ $photo }}" alt="" class="mb-5 h-36 w-full rounded-lg object-cover" loading="lazy">
                    @else
                    <div class="theme-chip absolute left-1/2 top-0 flex h-14 w-14 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full text-lg font-bold text-white shadow-lg" style="background: {{ $accent }}">
                        {{ $index + 1 }}
                    </div>
                    @endif
                    <h3 class="mb-3 text-xl font-semibold text-gray-800 dark:text-white">{{ $title }}</h3>
                    @if($text !== '')
                        <p class="text-sm leading-relaxed text-gray-500 dark:text-gray-400">{{ $text }}</p>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</section>
