@php $items = $block['data']['items'] ?? []; $blockId = 'slider-' . ($block['id'] ?? uniqid()); @endphp
@if(!empty($items))
<div class="website-block website-block-slider mb-6 w-screen relative left-1/2 right-1/2 -ml-[50vw] -mr-[50vw] overflow-hidden" id="{{ $blockId }}">
    <div class="slider-track flex overflow-x-auto snap-x snap-mandatory scroll-smooth gap-0" style="scrollbar-width: none; -ms-overflow-style: none;" role="region" aria-label="Afbeeldingen slider">
        @foreach($items as $item)
        @php $imgUrl = $item['url'] ?? ''; $cap = $item['caption'] ?? ''; @endphp
        @if($imgUrl !== '')
        <div class="slider-slide flex-shrink-0 w-full snap-center min-w-full">
            <div class="relative w-full aspect-[21/9] md:aspect-[3/1] bg-gray-100 dark:bg-gray-800">
                <img src="{{ $imgUrl }}" alt="{{ $cap ?: 'Slider' }}" class="w-full h-full object-cover">
                @if($cap !== '')
                <div class="absolute bottom-0 left-0 right-0 py-3 px-4 bg-black/50 text-white text-sm text-center">{{ $cap }}</div>
                @endif
            </div>
        </div>
        @endif
        @endforeach
    </div>
    @if(count(array_filter($items, fn($i) => !empty($i['url']))) > 1)
    <div class="slider-dots flex justify-center gap-2 py-3 bg-white dark:bg-gray-900">
        @foreach($items as $idx => $item)
        @if(!empty($item['url']))
        <button type="button" class="slider-dot w-2.5 h-2.5 rounded-full border-2 border-gray-400 dark:border-gray-500 bg-transparent hover:bg-gray-400 dark:hover:bg-gray-500 transition-colors" data-slider-index="{{ $idx }}" aria-label="Ga naar slide {{ $idx + 1 }}"></button>
        @endif
        @endforeach
    </div>
    <script>
    (function() {
        var blockId = '{{ $blockId }}';
        var track = document.querySelector('#' + blockId + ' .slider-track');
        var dots = document.querySelectorAll('#' + blockId + ' .slider-dot');
        if (!track || dots.length === 0) return;
        dots.forEach(function(dot, i) {
            dot.addEventListener('click', function() {
                var slideWidth = track.offsetWidth;
                track.scrollTo({ left: slideWidth * i, behavior: 'smooth' });
            });
        });
        track.addEventListener('scroll', function() {
            var idx = Math.round(track.scrollLeft / track.offsetWidth);
            dots.forEach(function(d, i) {
                d.classList.toggle('!bg-gray-600', i === idx);
                d.classList.toggle('dark:!bg-gray-400', i === idx);
            });
        });
    })();
    </script>
@endif
</div>
@endif
