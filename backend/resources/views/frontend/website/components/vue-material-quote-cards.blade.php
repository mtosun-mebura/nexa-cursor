@php
    $sectionKey = $sectionKey ?? 'component:vue_material.quote_cards';
    $saved = (isset($homeSections) && is_array($homeSections) && isset($homeSections[$sectionKey]) && is_array($homeSections[$sectionKey]))
        ? $homeSections[$sectionKey]
        : [];
    $defaults = app(\App\Services\FrontendComponentService::class)->defaultSectionData('vue_material.quote_cards');
    $data = array_merge($defaults, $saved);
    $items = isset($data['items']) && is_array($data['items']) && $data['items'] !== []
        ? array_values($data['items'])
        : ($defaults['items'] ?? []);
    $accent = $themeSettings['primary_color'] ?? '#e91e63';
@endphp
@include('frontend.website.components.partials.theme-component-scroll-reveal')
<section class="py-10 md:py-14 bg-white dark:bg-gray-900 theme-scroll-reveal" data-theme-component="vue_material.quote_cards" data-theme-anim="quote" data-scroll-reveal>
    <div class="website-section-inner">
        <div class="mx-auto mb-12 max-w-2xl text-center theme-fade" style="--theme-reveal-delay: 0ms;">
            <p class="mb-2 text-sm font-medium uppercase tracking-widest" style="color: {{ $accent }}">{{ $data['eyebrow'] ?? 'Quotes' }}</p>
            <h2 class="mb-3 text-3xl md:text-4xl font-bold text-gray-800 dark:text-white">{{ $data['title'] ?? 'Wat klanten teruggeven' }}</h2>
            @if(!empty($data['subtitle']))
                <p class="text-gray-500 dark:text-gray-400">{{ $data['subtitle'] }}</p>
            @endif
        </div>
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach($items as $i => $item)
                @php
                    $quote = trim((string) ($item['quote'] ?? ''));
                    $author = trim((string) ($item['author'] ?? ''));
                    $role = trim((string) ($item['role'] ?? ''));
                    $photo = trim((string) ($item['image_url'] ?? ''));
                    if ($quote === '') continue;
                @endphp
                <blockquote class="theme-reveal-item rounded-xl p-8" style="--theme-reveal-delay: {{ 140 + ($i * 120) }}ms;">
                    <span class="theme-quote-mark block text-5xl font-serif leading-none" style="color: {{ $accent }}">&ldquo;</span>
                    <p class="mt-2 text-gray-700 dark:text-gray-200 leading-relaxed">{{ $quote }}</p>
                    @if($author !== '' || $photo !== '')
                        <footer class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-4 italic bg-transparent flex items-center gap-3" style="background-color: transparent !important;">
                            @if($photo !== '')
                                <img src="{{ $photo }}" alt="" class="h-10 w-10 rounded-full object-cover" loading="lazy">
                            @endif
                            <span>
                            @if($author !== '')
                            <cite class="font-semibold text-gray-900 dark:text-white">{{ $author }}</cite>
                            @endif
                            @if($role !== '')
                                <span class="block text-sm text-gray-500 dark:text-gray-400">{{ $role }}</span>
                            @endif
                            </span>
                        </footer>
                    @endif
                </blockquote>
            @endforeach
        </div>
    </div>
</section>
