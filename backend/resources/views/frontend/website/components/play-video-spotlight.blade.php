@php
    $sectionKey = $sectionKey ?? 'component:play.video_spotlight';
    $saved = (isset($homeSections) && is_array($homeSections) && isset($homeSections[$sectionKey]) && is_array($homeSections[$sectionKey]))
        ? $homeSections[$sectionKey]
        : [];
    $defaults = app(\App\Services\FrontendComponentService::class)->defaultSectionData('play.video_spotlight');
    $data = array_merge($defaults, $saved);
    $primary = $themeSettings['primary_color'] ?? '#4a6cf7';
    $rawUrl = trim((string) ($data['video_url'] ?? ''));
    $poster = trim((string) ($data['image_url'] ?? $defaults['image_url'] ?? ''));
    $embedSrc = null;
    $fileSrc = null;
    if ($rawUrl !== '') {
        if (preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/embed/)([A-Za-z0-9_-]{6,})#', $rawUrl, $m)) {
            $embedSrc = 'https://www.youtube.com/embed/'.$m[1];
        } elseif (preg_match('#vimeo\.com/(?:video/)?(\d+)#', $rawUrl, $m)) {
            $embedSrc = 'https://player.vimeo.com/video/'.$m[1];
        } elseif (preg_match('#\.(mp4|webm|ogg)(\?|#|$)#i', $rawUrl)) {
            $fileSrc = $rawUrl;
        } elseif (str_starts_with($rawUrl, 'https://') || str_starts_with($rawUrl, 'http://') || str_starts_with($rawUrl, '/')) {
            if (preg_match('#\.(mp4|webm|ogg)#i', $rawUrl) || str_contains($rawUrl, '/storage/')) {
                $fileSrc = $rawUrl;
            } else {
                $embedSrc = $rawUrl;
            }
        }
    }
@endphp
@include('frontend.website.components.partials.theme-component-scroll-reveal')
<section class="py-14 md:py-20 bg-[#090e34] text-white theme-scroll-reveal" data-theme-component="play.video_spotlight" data-theme-anim="video" data-scroll-reveal>
    <div class="website-section-inner">
        <div class="mx-auto mb-10 max-w-2xl text-center theme-fade" style="--theme-reveal-delay: 0ms;">
            <p class="mb-3 text-sm font-semibold uppercase tracking-widest" style="color: {{ $primary }}">{{ $data['eyebrow'] ?? 'In beeld' }}</p>
            <h2 class="mb-3 text-3xl md:text-4xl font-bold">{{ $data['title'] ?? 'Zie hoe een rit binnenkomt' }}</h2>
            @if(!empty($data['subtitle']))
                <p class="text-white/70">{{ $data['subtitle'] }}</p>
            @endif
        </div>
        <div class="theme-video-frame relative mx-auto max-w-4xl overflow-hidden rounded-[2rem] border border-white/10 shadow-2xl aspect-video bg-black">
            @if($fileSrc)
                <video class="absolute inset-0 h-full w-full object-cover" src="{{ $fileSrc }}" @if($poster !== '') poster="{{ $poster }}" @endif controls playsinline></video>
            @elseif($embedSrc)
                <iframe class="absolute inset-0 h-full w-full" src="{{ $embedSrc }}" title="{{ $data['title'] ?? 'Video' }}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            @else
                <div class="absolute inset-0 flex flex-col items-center justify-center gap-4 bg-gradient-to-br from-[#4a6cf7]/40 to-[#090e34]" @if($poster !== '') style="background-image: url('{{ $poster }}'); background-size: cover; background-position: center;" @endif>
                    <span class="relative flex h-20 w-20 items-center justify-center rounded-full bg-white text-[#4a6cf7] shadow-lg" aria-hidden="true">
                        <span class="theme-play-ring text-[#4a6cf7]"></span>
                        <svg class="relative ml-1 h-8 w-8" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                    </span>
                    <span class="text-sm text-white/80">{{ $data['cta_label'] ?? 'Bekijk de demo' }}</span>
                </div>
            @endif
        </div>
    </div>
</section>
