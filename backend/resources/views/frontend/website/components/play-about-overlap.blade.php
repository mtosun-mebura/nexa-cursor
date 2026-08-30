@php
    $sectionKey = $sectionKey ?? 'component:play.about_overlap';
    $saved = (isset($homeSections) && is_array($homeSections) && isset($homeSections[$sectionKey]) && is_array($homeSections[$sectionKey]))
        ? $homeSections[$sectionKey]
        : [];
    $defaults = app(\App\Services\FrontendComponentService::class)->defaultSectionData('play.about_overlap');
    $data = array_merge($defaults, $saved);
    $primary = $themeSettings['primary_color'] ?? '#4a6cf7';
    $img1 = trim((string) ($data['image_url'] ?? $defaults['image_url'] ?? ''));
    $img2 = trim((string) ($data['image_url_2'] ?? $defaults['image_url_2'] ?? ''));
    $ctaLabel = trim((string) ($data['cta_label'] ?? ''));
    $ctaUrl = trim((string) ($data['cta_url'] ?? '#'));
@endphp
@include('frontend.website.components.partials.theme-component-scroll-reveal')
<section class="py-14 md:py-20 bg-white dark:bg-gray-900 theme-scroll-reveal" data-theme-component="play.about_overlap" data-theme-anim="overlap" data-scroll-reveal>
    <div class="website-section-inner grid items-center gap-12 md:grid-cols-2">
        <div class="relative mx-auto w-full max-w-md overflow-hidden" style="height: 580px;">
            @if($img1 !== '')
                <img src="{{ $img1 }}" alt="" class="theme-overlap-a absolute left-3 top-3 rounded-2xl object-cover shadow-xl" style="height: 68%; width: 70%;" loading="lazy">
            @endif
            @if($img2 !== '')
                <img src="{{ $img2 }}" alt="" class="theme-overlap-b absolute bottom-3 right-3 rounded-2xl object-cover shadow-2xl ring-8 ring-white dark:ring-gray-900" style="height: 54%; width: 56%;" loading="lazy">
            @endif
        </div>
        <div class="theme-fade">
            <p class="mb-3 text-sm font-semibold uppercase tracking-widest" style="color: {{ $primary }}">{{ $data['eyebrow'] ?? 'Over ons' }}</p>
            <h2 class="mb-4 text-3xl md:text-4xl font-bold text-gray-900 dark:text-white">{{ $data['title'] ?? '' }}</h2>
            @if(!empty($data['subtitle']))
                <p class="mb-4 text-gray-500 dark:text-gray-400">{{ $data['subtitle'] }}</p>
            @endif
            @if(!empty($data['body']))
                <p class="mb-6 text-gray-600 dark:text-gray-300 leading-relaxed">{{ $data['body'] }}</p>
            @endif
            @if($ctaLabel !== '')
                <a href="{{ $ctaUrl !== '' ? $ctaUrl : '#' }}" class="inline-flex items-center rounded-lg px-6 py-3 text-sm font-semibold text-white" style="background: {{ $primary }}">{{ $ctaLabel }}</a>
            @endif
        </div>
    </div>
</section>
