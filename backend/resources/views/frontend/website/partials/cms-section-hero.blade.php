@php
    $heroTitle = $sectionData['title'] ?? 'Welkom bij Nexa';
    $heroHighlight = $sectionData['title_highlight'] ?? 'Nexa';
    $heroHighlightColor = trim((string) ($sectionData['title_highlight_color'] ?? ''));
    $heroHighlightColor = preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $heroHighlightColor) ? $heroHighlightColor : $primaryColor;
    $parts = $heroHighlight !== '' ? explode($heroHighlight, $heroTitle, 2) : [$heroTitle];
    $heroSubtitleColor = trim((string) ($sectionData['subtitle_color'] ?? ''));
    $heroSubtitleColorStyle = ($heroSubtitleColor !== '' && preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $heroSubtitleColor)) ? 'color: ' . $heroSubtitleColor . ';' : '';
@endphp

@if($heroVariant === 'overlay')
<section class="hero-overlay-section relative min-h-[28rem] py-20 lg:py-28 overflow-hidden">
    @if($heroImg)
    <div class="hero-overlay-bg absolute inset-0 bg-cover bg-center" style="background-image: url({{ $heroImg }});"></div>
    @endif
    <div class="hero-overlay-scrim absolute inset-0 bg-slate-900/75"></div>
    <div class="container mx-auto px-4 relative z-10 text-center max-w-4xl">
        @if($v('_title'))
        <h1 class="hero-overlay-title text-4xl font-bold leading-tight tracking-tight text-white lg:text-5xl xl:text-6xl">
            @if(count($parts) === 2)
                {{ trim($parts[0]) }} <span style="color: {{ $heroHighlightColor }};">{{ $heroHighlight }}</span> {{ trim($parts[1]) }}
            @else
                {{ $heroTitle }}
            @endif
        </h1>
        @endif
        @if($v('_subtitle') && !empty($sectionData['subtitle']))
        <p class="hero-overlay-subtitle mt-6 text-lg xl:text-xl text-white/90" @if($heroSubtitleColorStyle !== '') style="{{ $heroSubtitleColorStyle }}" @endif>{!! $sectionData['subtitle'] !!}</p>
        @endif
        <div class="hero-overlay-ctas">
            @include('frontend.website.partials.cms-section-hero-ctas', ['justify' => 'center', 'onDark' => true])
        </div>
    </div>
</section>
@push('styles')
<style>
    /* Hero (overlay): eigen intro-choreografie bij het laden van de pagina (de hero staat al
       boven de vouw, dus dit start meteen, niet pas bij scrollen) — trage Ken Burns-zoom op de
       achtergrond, titel/ondertitel/knoppen komen na elkaar gefaseerd omhoog, knoppen krijgen
       een lichtstreep-hover. Bewust anders dan de scroll-reveal-animaties elders op de pagina. */
    @keyframes hero-overlay-kenburns {
        0% { transform: scale(1); }
        100% { transform: scale(1.09); }
    }
    .hero-overlay-bg {
        animation: hero-overlay-kenburns 18s ease-in-out infinite alternate;
        will-change: transform;
    }
    @keyframes hero-overlay-rise {
        from { opacity: 0; transform: translateY(28px); filter: blur(6px); }
        to { opacity: 1; transform: translateY(0); filter: blur(0); }
    }
    .hero-overlay-title,
    .hero-overlay-subtitle,
    .hero-overlay-ctas {
        opacity: 0;
        animation: hero-overlay-rise 0.9s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    .hero-overlay-title { animation-delay: 0.15s; }
    .hero-overlay-subtitle { animation-delay: 0.4s; }
    .hero-overlay-ctas { animation-delay: 0.65s; }
    .hero-overlay-ctas a,
    .hero-overlay-ctas button {
        position: relative;
        overflow: hidden;
    }
    .hero-overlay-ctas a::after,
    .hero-overlay-ctas button::after {
        content: '';
        position: absolute;
        top: 0;
        left: -60%;
        width: 40%;
        height: 100%;
        background: linear-gradient(115deg, transparent, rgba(255, 255, 255, 0.35), transparent);
        transform: skewX(-20deg);
        transition: left 0.6s ease;
        pointer-events: none;
    }
    .hero-overlay-ctas a:hover::after,
    .hero-overlay-ctas button:hover::after {
        left: 130%;
    }
    @media (prefers-reduced-motion: reduce) {
        .hero-overlay-bg {
            animation: none;
        }
        .hero-overlay-title,
        .hero-overlay-subtitle,
        .hero-overlay-ctas {
            opacity: 1;
            animation: none;
            transform: none;
            filter: none;
        }
        .hero-overlay-ctas a::after,
        .hero-overlay-ctas button::after {
            display: none;
        }
    }
</style>
@endpush
@elseif($heroVariant === 'material')
<section class="py-12 lg:py-16 bg-gray-50 dark:bg-gray-900">
    <div class="container mx-auto px-4">
        <div class="overflow-hidden rounded-3xl shadow-2xl lg:grid lg:grid-cols-2" style="background: linear-gradient(195deg, {{ $primaryColor }}, #1e293b);">
            <div class="p-10 lg:p-16 text-white flex flex-col justify-center">
                @if($v('_title'))
                <h1 class="text-4xl font-bold leading-tight tracking-tight lg:text-5xl">
                    @if(count($parts) === 2)
                        {{ trim($parts[0]) }} <span class="text-white/90">{{ $heroHighlight }}</span> {{ trim($parts[1]) }}
                    @else
                        {{ $heroTitle }}
                    @endif
                </h1>
                @endif
                @if($v('_subtitle') && !empty($sectionData['subtitle']))
                <p class="mt-5 text-lg text-white/85" @if($heroSubtitleColorStyle !== '') style="{{ $heroSubtitleColorStyle }}" @endif>{!! $sectionData['subtitle'] !!}</p>
                @endif
                @include('frontend.website.partials.cms-section-hero-ctas', ['justify' => 'start', 'onDark' => true])
            </div>
            <div class="min-h-[16rem] bg-cover bg-center" @if($heroSideImg) style="background-image: url({{ $heroSideImg }});" @endif></div>
        </div>
    </div>
</section>
@else
<section class="py-12 lg:py-20 bg-gray-50 dark:bg-gray-800/50">
    <div class="container mx-auto px-4">
        <div class="flex flex-col items-center gap-10 lg:flex-row lg:gap-16">
            <div class="w-full lg:w-1/2 order-2 lg:order-1 text-center lg:text-left">
                @if($v('_title'))
                <h1 class="text-4xl font-bold leading-tight tracking-tight text-gray-900 dark:text-white lg:text-5xl xl:text-6xl">
                    @if(count($parts) === 2)
                        {{ trim($parts[0]) }} <span style="color: {{ $heroHighlightColor }};">{{ $heroHighlight }}</span> {{ trim($parts[1]) }}
                    @else
                        {{ $heroTitle }}
                    @endif
                </h1>
                @endif
                @if($v('_subtitle') && !empty($sectionData['subtitle']))
                <p class="mt-4 text-lg xl:text-xl {{ $heroSubtitleColorStyle === '' ? 'text-gray-600 dark:text-gray-300' : '' }}" @if($heroSubtitleColorStyle !== '') style="{{ $heroSubtitleColorStyle }}" @endif>{!! $sectionData['subtitle'] !!}</p>
                @endif
                @include('frontend.website.partials.cms-section-hero-ctas', ['justify' => 'start', 'onDark' => false])
            </div>
            <div class="w-full lg:w-1/2 order-1 lg:order-2 flex justify-center">
                @if($heroSideImg)
                <img src="{{ $heroSideImg }}" alt="" class="max-w-md w-full h-auto rounded-2xl object-cover shadow-lg">
                @endif
            </div>
        </div>
    </div>
</section>
@endif
