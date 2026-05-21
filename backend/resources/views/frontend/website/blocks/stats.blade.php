@php
    $items = isset($sectionData['items']) && is_array($sectionData['items']) ? $sectionData['items'] : (is_array($sectionData) ? array_values($sectionData) : []);
    $items = array_slice(array_merge($items, [['value'=>'','label'=>''],['value'=>'','label'=>''],['value'=>'','label'=>''],['value'=>'','label'=>'']]), 0, 4);
    $background = isset($sectionData['background']) && is_string($sectionData['background']) && preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $sectionData['background']) ? $sectionData['background'] : '';
    $backgroundImage = isset($sectionData['background_image']) && is_string($sectionData['background_image']) ? trim($sectionData['background_image']) : '';
    $sectionClass = 'website-block website-block-stats py-16 stats-section';
    if ($background === '' && $backgroundImage === '') {
        $sectionClass .= ' bg-gray-100 dark:bg-gray-900';
    }
    $sectionStyle = '';
    if ($background !== '') {
        $sectionStyle .= ' background-color: ' . e($background) . ';';
    }
    if ($backgroundImage !== '') {
        $sectionStyle .= ' background-image: url(' . e($backgroundImage) . '); background-size: cover; background-position: center; background-repeat: no-repeat;';
    }
    // Parse value for count-up: get numeric part and suffix (e.g. "10,000+" -> 10000, "+"; "95%" -> 95, "%")
    $parseStatValue = function ($raw) {
        $raw = trim((string) $raw);
        if ($raw === '') return ['num' => 0, 'prefix' => '', 'suffix' => '', 'raw' => ''];
        if (preg_match('/^([^0-9]*)([0-9][0-9,.\s]*)(.*)$/u', $raw, $m)) {
            $prefix = $m[1];
            $numStr = preg_replace('/[\s,]/', '', $m[2]);
            $suffix = $m[3];
            $num = is_numeric($numStr) ? (float) $numStr : 0;
            return ['num' => (int) $num, 'prefix' => $prefix, 'suffix' => $suffix, 'raw' => $raw];
        }
        return ['num' => 0, 'prefix' => '', 'suffix' => '', 'raw' => $raw];
    };
@endphp
<section class="{{ $sectionClass }} stats-animate-section" data-stats-section @if($sectionStyle !== '') style="{{ $sectionStyle }}" @endif>
    <div class="website-section-inner elementor-section-boxed">
        <div class="stats-inner-row flex flex-col sm:flex-row flex-wrap gap-6 sm:gap-8 w-full max-w-4xl mx-auto md:justify-evenly md:items-stretch">
            @foreach($items as $i => $stat)
                @if($visibility[$sectionKey . '_' . $i] ?? $visibility['stats_'.$i] ?? true)
                @php
                    $val = $stat['value'] ?? '';
                    $label = $stat['label'] ?? '';
                    $parsed = $parseStatValue($val);
                    $valueColor = isset($stat['value_color']) && is_string($stat['value_color']) && preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $stat['value_color']) ? $stat['value_color'] : '';
                    $valueSize = $stat['value_size'] ?? '22';
                    $valueSizePt = in_array((int) $valueSize, range(10, 30, 2), true) ? (int) $valueSize : null;
                    $valueSizeClass = $valueSizePt === null ? (match($valueSize) { 'small' => 'text-2xl', 'large' => 'text-4xl', default => 'text-3xl' }) : '';
                    $labelSize = $stat['label_size'] ?? '16';
                    $labelSizePt = in_array((int) $labelSize, range(10, 30, 2), true) ? (int) $labelSize : null;
                    $labelSizeClass = $labelSizePt === null ? (match($labelSize) { 'small' => 'text-sm', 'large' => 'text-lg', default => 'text-base' }) : '';
                    $defaultColorClass = $valueColor === '' ? ($i === 0 ? 'text-blue-600 dark:text-blue-400' : ($i === 1 ? 'text-green-600 dark:text-green-400' : ($i === 2 ? 'text-gray-900 dark:text-white' : 'text-orange-600 dark:text-orange-400'))) : '';
                @endphp
                <div class="stats-animate-item flex-1 min-w-[180px] text-center p-6" data-stats-item data-stats-delay="{{ $i }}">
                    @php
                    $valueStyle = '';
                    if ($valueSizePt !== null) { $valueStyle .= 'font-size: ' . $valueSizePt . 'pt;'; }
                    if ($valueColor !== '') { $valueStyle .= ' color: ' . $valueColor . ';'; }
                @endphp
                <div class="{{ $valueSizeClass }} font-bold mb-2 {{ $valueColor === '' ? $defaultColorClass : '' }}" @if($valueStyle !== '') style="{{ $valueStyle }}" @endif>
                        @if($parsed['num'] > 0)
                            <span class="stats-count" data-stat-end="{{ $parsed['num'] }}" data-stat-prefix="{{ e($parsed['prefix']) }}" data-stat-suffix="{{ e($parsed['suffix']) }}">0</span>
                        @else
                            {{ $val }}
                        @endif
                    </div>
                    <div class="{{ $labelSizeClass }} text-gray-800 dark:text-gray-300" @if($labelSizePt !== null) style="font-size: {{ $labelSizePt }}pt;" @endif>{{ $label }}</div>
                </div>
                @endif
            @endforeach
        </div>
    </div>
</section>

@push('styles')
<style>
    .stats-animate-section .stats-animate-item {
        opacity: 0;
        transform: translateY(24px);
        transition: opacity 0.6s ease-out, transform 0.6s ease-out;
    }
    .stats-animate-section.is-in-view .stats-animate-item {
        opacity: 1;
        transform: translateY(0);
    }
    .stats-animate-section .stats-animate-item[data-stats-delay="0"] { transition-delay: 0ms; }
    .stats-animate-section .stats-animate-item[data-stats-delay="1"] { transition-delay: 80ms; }
    .stats-animate-section .stats-animate-item[data-stats-delay="2"] { transition-delay: 160ms; }
    .stats-animate-section .stats-animate-item[data-stats-delay="3"] { transition-delay: 240ms; }
</style>
@endpush

@push('scripts')
<script>
(function() {
    function initStatsSection() {
        var section = document.querySelector('[data-stats-section]');
        if (!section) return;
        var opts = { rootMargin: '0px 0px -60px 0px', threshold: 0.1 };
        if (typeof window.nexaObserveWhenVisible === 'function') {
            window.nexaObserveWhenVisible(section, function(el) {
                el.classList.add('is-in-view');
                runCountUp(el);
            }, opts);
            return;
        }
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-in-view');
                    runCountUp(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, opts);
        observer.observe(section);
    }
    function runCountUp(section) {
        var counters = section.querySelectorAll('.stats-count[data-stat-end]');
        var duration = 1500;
        var startTime = null;
        function easeOutQuart(t) { return 1 - (--t) * t * t * t; }
        function step(timestamp) {
            if (!startTime) startTime = timestamp;
            var elapsed = timestamp - startTime;
            var progress = Math.min(elapsed / duration, 1);
            var eased = easeOutQuart(progress);
            counters.forEach(function(el) {
                var end = parseInt(el.getAttribute('data-stat-end'), 10) || 0;
                var prefix = el.getAttribute('data-stat-prefix') || '';
                var suffix = el.getAttribute('data-stat-suffix') || '';
                var current = Math.round(end * eased);
                el.textContent = prefix + current.toLocaleString('nl-NL') + suffix;
            });
            if (progress < 1) requestAnimationFrame(step);
            else {
                counters.forEach(function(el) {
                    var end = parseInt(el.getAttribute('data-stat-end'), 10) || 0;
                    var prefix = el.getAttribute('data-stat-prefix') || '';
                    var suffix = el.getAttribute('data-stat-suffix') || '';
                    el.textContent = prefix + end.toLocaleString('nl-NL') + suffix;
                });
            }
        }
        requestAnimationFrame(step);
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initStatsSection);
    } else {
        initStatsSection();
    }
})();
</script>
@endpush
