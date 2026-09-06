@php
    $sectionKey = $sectionKey ?? 'component:landwind.feature_checklist';
    $saved = (isset($homeSections) && is_array($homeSections) && isset($homeSections[$sectionKey]) && is_array($homeSections[$sectionKey]))
        ? $homeSections[$sectionKey]
        : [];
    $defaults = app(\App\Services\FrontendComponentService::class)->defaultSectionData('landwind.feature_checklist');
    $data = array_merge($defaults, $saved);
    $items = isset($data['items']) && is_array($data['items']) && $data['items'] !== []
        ? array_values($data['items'])
        : ($defaults['items'] ?? []);
    $primary = $themeSettings['primary_color'] ?? '#7e3af2';
    $image = trim((string) ($data['image_url'] ?? $defaults['image_url'] ?? ''));
@endphp
@include('frontend.website.components.partials.theme-component-scroll-reveal')
<section class="py-10 md:py-14 bg-white dark:bg-gray-900 theme-scroll-reveal" data-theme-component="landwind.feature_checklist" data-theme-anim="check" data-scroll-reveal>
    <div class="website-section-inner grid items-center gap-10 md:grid-cols-2">
        <div class="theme-reveal-left">
            @if($image !== '')
                <img src="{{ $image }}" alt="" class="w-full rounded-xl shadow-lg" loading="lazy">
            @endif
        </div>
        <div>
            <div class="theme-fade mb-6">
                <p class="mb-2 text-sm font-semibold uppercase tracking-[0.2em]" style="color: {{ $primary }}">{{ $data['eyebrow'] ?? 'Werkwijze' }}</p>
                <h2 class="mb-3 text-3xl md:text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white">{{ $data['title'] ?? '' }}</h2>
                @if(!empty($data['subtitle']))
                    <p class="text-gray-500 dark:text-gray-400">{{ $data['subtitle'] }}</p>
                @endif
            </div>
            <ul class="space-y-3">
                @foreach($items as $i => $item)
                    @php $text = trim((string) ($item['text'] ?? '')); @endphp
                    @if($text !== '')
                        <li class="theme-reveal-right flex items-start gap-3" style="--theme-reveal-delay: {{ 160 + ($i * 110) }}ms;">
                            <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full" style="background: {{ $primary }}">
                                <svg class="theme-check-icon h-3.5 w-3.5 text-white" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M5 12.5l4.2 4.2L19 7.5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <span class="text-gray-700 dark:text-gray-200 leading-relaxed">{{ $text }}</span>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
    </div>
</section>
