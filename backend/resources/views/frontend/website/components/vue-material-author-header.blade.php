@php
    $sectionKey = $sectionKey ?? 'component:vue_material.author_header';
    $saved = (isset($homeSections) && is_array($homeSections) && isset($homeSections[$sectionKey]) && is_array($homeSections[$sectionKey]))
        ? $homeSections[$sectionKey]
        : [];
    $defaults = app(\App\Services\FrontendComponentService::class)->defaultSectionData('vue_material.author_header');
    $data = array_merge($defaults, $saved);
    $items = isset($data['items']) && is_array($data['items']) && $data['items'] !== []
        ? array_values($data['items'])
        : ($defaults['items'] ?? []);
    $accent = $themeSettings['primary_color'] ?? '#e91e63';
    $image = trim((string) ($data['image_url'] ?? $defaults['image_url'] ?? ''));
    $bio = trim((string) ($data['bio'] ?? ''));
@endphp
@include('frontend.website.components.partials.theme-component-scroll-reveal')
<section class="py-16 md:py-24 bg-[#f8f9fa] dark:bg-gray-900 theme-scroll-reveal" data-theme-component="vue_material.author_header" data-theme-anim="portrait" data-scroll-reveal>
    <div class="website-section-inner flex flex-col items-center text-center">
        <p class="theme-fade mb-6 text-sm font-medium uppercase tracking-widest" style="color: {{ $accent }}">{{ $data['eyebrow'] ?? 'Auteur' }}</p>
        @if($image !== '')
            <img src="{{ $image }}" alt="" class="theme-portrait mb-6 h-36 w-36 rounded-full object-cover shadow-[0_8px_26px_-4px_rgba(0,0,0,0.25)]" loading="lazy">
        @endif
        <div class="theme-portrait-text max-w-xl">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800 dark:text-white">{{ $data['title'] ?? '' }}</h2>
            @if(!empty($data['subtitle']))
                <p class="mt-1 text-sm font-medium" style="color: {{ $accent }}">{{ $data['subtitle'] }}</p>
            @endif
            @if($bio !== '')
                <p class="mt-4 text-gray-500 dark:text-gray-400 leading-relaxed">{{ $bio }}</p>
            @endif
        </div>
        @if($items !== [])
            <ul class="mt-6 flex flex-wrap justify-center gap-3">
                @foreach($items as $i => $item)
                    @php
                        $label = trim((string) ($item['label'] ?? ''));
                        $url = trim((string) ($item['url'] ?? '#'));
                        if ($label === '') continue;
                    @endphp
                    <li class="theme-social" style="--theme-reveal-delay: {{ 320 + ($i * 90) }}ms;">
                        <a href="{{ $url !== '' ? $url : '#' }}" class="inline-flex rounded-full border border-gray-200 dark:border-gray-700 px-4 py-1.5 text-sm text-gray-600 dark:text-gray-300 hover:border-transparent hover:text-white" style="--tw-ring-color: {{ $accent }}">{{ $label }}</a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</section>
