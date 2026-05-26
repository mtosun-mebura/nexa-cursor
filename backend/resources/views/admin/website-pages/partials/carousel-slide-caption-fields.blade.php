@php
    $sectionKey = $sectionKey ?? 'carousel';
    $idx = (int) ($idx ?? 0);
    $item = is_array($item ?? null) ? $item : [];
    $carouselCaptionAnimations = $carouselCaptionAnimations ?? [
        'rise' => 'Woorden omhoog',
        'fade' => 'Infaden',
        'slide_left' => 'Van links',
        'zoom' => 'Inzoomen',
        'blur' => 'Scherp worden',
    ];
    $slideTextSizePx = (int) old('home_sections.'.$sectionKey.'.items.'.$idx.'.text_size_px', $item['text_size_px'] ?? 24);
    $slideTextSizePx = max(12, min(50, $slideTextSizePx));
    $slideTextSizePx = (int) (round($slideTextSizePx / 2) * 2);
    $slideTextPosition = old('home_sections.'.$sectionKey.'.items.'.$idx.'.text_position', $item['text_position'] ?? 'bottom');
    $slideTextPosition = in_array($slideTextPosition, ['top', 'center', 'bottom'], true) ? $slideTextPosition : 'bottom';
    $slideTextAnimation = old('home_sections.'.$sectionKey.'.items.'.$idx.'.text_animation', $item['text_animation'] ?? 'rise');
    $slideTextAnimation = array_key_exists($slideTextAnimation, $carouselCaptionAnimations) ? $slideTextAnimation : 'rise';
    $slideAnimDurationMs = (int) old('home_sections.'.$sectionKey.'.items.'.$idx.'.text_animation_duration_ms', $item['text_animation_duration_ms'] ?? 550);
    $slideAnimDurationMs = max(200, min(5000, $slideAnimDurationMs));
    $slideAnimStaggerMs = (int) old('home_sections.'.$sectionKey.'.items.'.$idx.'.text_animation_stagger_ms', $item['text_animation_stagger_ms'] ?? 90);
    $slideAnimStaggerMs = max(0, min(1000, $slideAnimStaggerMs));
    $carouselAnimationDurationOptions = [
        300 => '0,3 s',
        450 => '0,45 s',
        550 => '0,55 s (standaard)',
        800 => '0,8 s',
        1000 => '1 s',
        1500 => '1,5 s',
        2000 => '2 s',
        3000 => '3 s',
        4000 => '4 s',
    ];
    $carouselAnimationStaggerOptions = [
        0 => 'Geen',
        50 => '0,05 s',
        90 => '0,09 s (standaard)',
        120 => '0,12 s',
        150 => '0,15 s',
        200 => '0,2 s',
        300 => '0,3 s',
        500 => '0,5 s',
    ];
@endphp
<div class="carousel-slide-caption-options flex flex-row flex-nowrap items-end gap-2 mt-2 max-w-full">
    <div class="carousel-slide-caption-field w-28 flex-none">
        <label class="block text-xs font-medium text-muted-foreground mb-1">Tekstgrootte</label>
        <select name="home_sections[{{ $sectionKey }}][items][{{ $idx }}][text_size_px]" class="kt-input w-full text-sm carousel-slide-text-size-select">
            @for ($px = 12; $px <= 50; $px += 2)
                <option value="{{ $px }}" @selected($slideTextSizePx === $px)>{{ $px }} px</option>
            @endfor
        </select>
    </div>
    <div class="carousel-slide-caption-field w-28 flex-none">
        <label class="block text-xs font-medium text-muted-foreground mb-1">Positie</label>
        <select name="home_sections[{{ $sectionKey }}][items][{{ $idx }}][text_position]" class="kt-input w-full text-sm carousel-slide-text-position-select">
            <option value="top" @selected($slideTextPosition === 'top')>Boven</option>
            <option value="center" @selected($slideTextPosition === 'center')>Midden</option>
            <option value="bottom" @selected($slideTextPosition === 'bottom')>Onder</option>
        </select>
    </div>
    <div class="carousel-slide-caption-field w-40 flex-none">
        <label class="block text-xs font-medium text-muted-foreground mb-1">Animatie</label>
        <select name="home_sections[{{ $sectionKey }}][items][{{ $idx }}][text_animation]" class="kt-input w-full text-sm carousel-slide-text-animation-select">
            @foreach ($carouselCaptionAnimations as $animKey => $animLabel)
                <option value="{{ $animKey }}" @selected($slideTextAnimation === $animKey)>{{ $animLabel }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="carousel-slide-caption-timing-options flex flex-row flex-nowrap items-end gap-2 mt-2 max-w-full">
    <div class="carousel-slide-caption-field w-44 flex-none">
        <label class="block text-xs font-medium text-muted-foreground mb-1">Animatieduur (per woord)</label>
        <select name="home_sections[{{ $sectionKey }}][items][{{ $idx }}][text_animation_duration_ms]" class="kt-input w-full text-sm carousel-slide-text-animation-duration-select">
            @foreach ($carouselAnimationDurationOptions as $ms => $label)
                <option value="{{ $ms }}" @selected($slideAnimDurationMs === $ms)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="carousel-slide-caption-field w-44 flex-none">
        <label class="block text-xs font-medium text-muted-foreground mb-1">Pauze tussen woorden</label>
        <select name="home_sections[{{ $sectionKey }}][items][{{ $idx }}][text_animation_stagger_ms]" class="kt-input w-full text-sm carousel-slide-text-animation-stagger-select">
            @foreach ($carouselAnimationStaggerOptions as $ms => $label)
                <option value="{{ $ms }}" @selected($slideAnimStaggerMs === $ms)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>
