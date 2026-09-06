@php
    $sectionKey = $sectionKey ?? 'component:landwind.faq';
    $saved = (isset($homeSections) && is_array($homeSections) && isset($homeSections[$sectionKey]) && is_array($homeSections[$sectionKey]))
        ? $homeSections[$sectionKey]
        : [];
    $defaults = app(\App\Services\FrontendComponentService::class)->defaultSectionData('landwind.faq');
    $data = array_merge($defaults, $saved);
    $items = isset($data['items']) && is_array($data['items']) && $data['items'] !== []
        ? array_values($data['items'])
        : ($defaults['items'] ?? []);
    $uid = 'lw-faq-'.substr(md5($sectionKey), 0, 8);
    $primary = $themeSettings['primary_color'] ?? '#7e3af2';
@endphp
@include('frontend.website.components.partials.theme-component-scroll-reveal')
<section class="py-10 md:py-14 bg-white dark:bg-gray-900 theme-scroll-reveal" data-theme-component="landwind.faq" data-theme-anim="wipe" data-scroll-reveal>
    <div class="website-section-inner max-w-3xl mx-auto">
        <div class="theme-fade" style="--theme-reveal-delay: 0ms;">
            <p class="mb-3 text-sm font-semibold uppercase tracking-[0.2em]" style="color: {{ $primary }}">{{ $data['eyebrow'] ?? 'FAQ' }}</p>
            <h2 class="mb-3 text-3xl md:text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white">{{ $data['title'] ?? 'Veelgestelde vragen' }}</h2>
            @if(!empty($data['subtitle']))
                <p class="mb-8 text-lg text-gray-500 dark:text-gray-400">{{ $data['subtitle'] }}</p>
            @endif
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-700 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            @foreach($items as $i => $item)
                @php
                    $q = trim((string) ($item['question'] ?? ''));
                    $a = trim((string) ($item['answer'] ?? ''));
                    if ($q === '') continue;
                    $itemId = $uid.'-'.$i;
                    $delay = 120 + ($i * 90);
                    $startsOpen = $i === 0;
                @endphp
                <div class="lw-faq-item bg-gray-50 dark:bg-gray-800 theme-reveal-item{{ $startsOpen ? ' is-open' : '' }}" data-lw-faq style="--theme-reveal-delay: {{ $delay }}ms;">
                    <button type="button" class="lw-faq-summary flex w-full cursor-pointer items-center justify-between gap-4 px-5 py-4 text-left font-medium text-gray-900 dark:text-white" aria-expanded="{{ $startsOpen ? 'true' : 'false' }}" aria-controls="{{ $itemId }}">
                        <span>{{ $q }}</span>
                        <span class="lw-faq-icon shrink-0 text-xl leading-none" style="color: {{ $primary }}" aria-hidden="true">+</span>
                    </button>
                    <div class="lw-faq-panel">
                        <div class="lw-faq-panel-clip">
                            <div id="{{ $itemId }}" class="lw-faq-panel-inner border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-5 py-4 text-gray-600 dark:text-gray-300 leading-relaxed">
                                {{ $a }}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@once
<style>
    .lw-faq-summary {
        appearance: none;
        background: transparent;
        border: 0;
    }
    .lw-faq-panel {
        display: grid;
        grid-template-rows: 0fr;
        transition: grid-template-rows 0.45s cubic-bezier(0.22, 1, 0.36, 1);
    }
    .lw-faq-item.is-open .lw-faq-panel {
        grid-template-rows: 1fr;
    }
    .lw-faq-panel-clip {
        overflow: hidden;
        min-height: 0;
    }
    .lw-faq-panel-inner {
        opacity: 0;
        transform: translateY(-8px);
        transition: opacity 0.32s ease, transform 0.4s cubic-bezier(0.22, 1, 0.36, 1);
    }
    .lw-faq-item.is-open .lw-faq-panel-inner {
        opacity: 1;
        transform: none;
        transition-delay: 0.06s;
    }
    .lw-faq-icon {
        display: inline-flex;
        width: 1.25rem;
        align-items: center;
        justify-content: center;
        transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1);
    }
    .lw-faq-item.is-open .lw-faq-icon {
        transform: rotate(45deg);
    }
    @media (prefers-reduced-motion: reduce) {
        .lw-faq-panel,
        .lw-faq-panel-inner,
        .lw-faq-icon {
            transition: none;
        }
    }
</style>
<script>
    (function () {
        if (window.__lwFaqAccordionInit) return;
        window.__lwFaqAccordionInit = true;

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.lw-faq-summary');
            if (!btn) return;
            var item = btn.closest('.lw-faq-item');
            if (!item) return;
            var open = item.classList.toggle('is-open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    })();
</script>
@endonce
