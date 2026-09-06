@php
    $sectionKey = $sectionKey ?? 'component:vue_material.info_pills';
    $saved = (isset($homeSections) && is_array($homeSections) && isset($homeSections[$sectionKey]) && is_array($homeSections[$sectionKey]))
        ? $homeSections[$sectionKey]
        : [];
    $defaults = app(\App\Services\FrontendComponentService::class)->defaultSectionData('vue_material.info_pills');
    $data = array_merge($defaults, $saved);
    $items = isset($data['items']) && is_array($data['items']) && $data['items'] !== []
        ? array_values($data['items'])
        : ($defaults['items'] ?? []);
    $accent = $themeSettings['primary_color'] ?? '#e91e63';
    $uid = 'vm-pills-'.substr(md5($sectionKey), 0, 8);
@endphp
@include('frontend.website.components.partials.theme-component-scroll-reveal')
<section class="py-10 md:py-14 bg-white dark:bg-gray-900 theme-scroll-reveal" data-theme-component="vue_material.info_pills" data-theme-anim="pills" data-scroll-reveal>
    <div class="website-section-inner max-w-3xl mx-auto text-center">
        <div class="theme-fade mb-10">
            <p class="mb-2 text-sm font-medium uppercase tracking-widest" style="color: {{ $accent }}">{{ $data['eyebrow'] ?? 'Ontdek' }}</p>
            <h2 class="mb-3 text-3xl md:text-4xl font-bold text-gray-800 dark:text-white">{{ $data['title'] ?? '' }}</h2>
            @if(!empty($data['subtitle']))
                <p class="text-gray-500 dark:text-gray-400">{{ $data['subtitle'] }}</p>
            @endif
        </div>
        <div class="mb-8 flex flex-wrap justify-center gap-2" role="tablist">
            @foreach($items as $i => $item)
                @php
                    $label = trim((string) ($item['label'] ?? ''));
                    if ($label === '') continue;
                    $tabId = $uid.'-'.$i;
                @endphp
                <button type="button"
                    class="theme-pill rounded-full px-5 py-2 text-sm font-semibold {{ $i === 0 ? 'is-active text-white' : 'text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700' }}"
                    style="{{ $i === 0 ? 'background: '.$accent.';' : '' }} --theme-reveal-delay: {{ $i * 90 }}ms;"
                    role="tab"
                    aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                    data-theme-pill="{{ $tabId }}"
                    data-theme-pill-color="{{ $accent }}">{{ $label }}</button>
            @endforeach
        </div>
        <div class="relative min-h-[8rem] text-left">
            @foreach($items as $i => $item)
                @php
                    $tabId = $uid.'-'.$i;
                    $title = trim((string) ($item['title'] ?? ''));
                    $text = trim((string) ($item['text'] ?? ''));
                @endphp
                <div class="theme-pill-panel {{ $i === 0 ? 'is-active' : '' }}" data-theme-pill-panel="{{ $tabId }}" role="tabpanel">
                    @php $photo = trim((string) ($item['image_url'] ?? '')); @endphp
                    @if($photo !== '')
                        <img src="{{ $photo }}" alt="" class="mb-4 h-40 w-full rounded-xl object-cover" loading="lazy">
                    @endif
                    @if($title !== '')
                        <h3 class="mb-2 text-xl font-semibold text-gray-800 dark:text-white">{{ $title }}</h3>
                    @endif
                    @if($text !== '')
                        <p class="text-gray-500 dark:text-gray-400 leading-relaxed">{{ $text }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>
