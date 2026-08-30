@php
    $sectionKey = $sectionKey ?? 'component:play.blog_preview';
    $saved = (isset($homeSections) && is_array($homeSections) && isset($homeSections[$sectionKey]) && is_array($homeSections[$sectionKey]))
        ? $homeSections[$sectionKey]
        : [];
    $defaults = app(\App\Services\FrontendComponentService::class)->defaultSectionData('play.blog_preview');
    $data = array_merge($defaults, $saved);
    $items = isset($data['items']) && is_array($data['items']) && $data['items'] !== []
        ? array_values($data['items'])
        : ($defaults['items'] ?? []);
    $primary = $themeSettings['primary_color'] ?? '#4a6cf7';
@endphp
@include('frontend.website.components.partials.theme-component-scroll-reveal')
<section class="py-14 md:py-20 bg-[#f8f9ff] dark:bg-gray-900 theme-scroll-reveal" data-theme-component="play.blog_preview" data-theme-anim="clip" data-scroll-reveal>
    <div class="website-section-inner">
        <div class="theme-fade mx-auto mb-12 max-w-2xl text-center">
            <p class="mb-3 text-sm font-semibold uppercase tracking-widest" style="color: {{ $primary }}">{{ $data['eyebrow'] ?? 'Inzichten' }}</p>
            <h2 class="mb-3 text-3xl md:text-4xl font-bold text-gray-900 dark:text-white">{{ $data['title'] ?? '' }}</h2>
            @if(!empty($data['subtitle']))
                <p class="text-gray-500 dark:text-gray-400">{{ $data['subtitle'] }}</p>
            @endif
        </div>
        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
            @foreach($items as $i => $item)
                @php
                    $title = trim((string) ($item['title'] ?? ''));
                    $excerpt = trim((string) ($item['excerpt'] ?? ''));
                    $date = trim((string) ($item['date'] ?? ''));
                    $img = trim((string) ($item['image_url'] ?? ''));
                    $url = trim((string) ($item['url'] ?? '#'));
                    if ($title === '') continue;
                @endphp
                <article class="theme-reveal-item overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-[0_10px_30px_rgba(74,108,247,0.08)]" style="--theme-reveal-delay: {{ $i * 140 }}ms;">
                    @if($img !== '')
                        <a href="{{ $url !== '' ? $url : '#' }}" class="block overflow-hidden">
                            <img src="{{ $img }}" alt="" class="theme-clip-img h-48 w-full object-cover" loading="lazy">
                        </a>
                    @endif
                    <div class="p-6">
                        @if($date !== '')
                            <p class="mb-2 text-xs font-medium uppercase tracking-wider text-gray-400">{{ $date }}</p>
                        @endif
                        <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">
                            <a href="{{ $url !== '' ? $url : '#' }}">{{ $title }}</a>
                        </h3>
                        @if($excerpt !== '')
                            <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">{{ $excerpt }}</p>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
