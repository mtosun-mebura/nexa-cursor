@php
    $sectionKey = $sectionKey ?? 'component:play.team';
    $saved = (isset($homeSections) && is_array($homeSections) && isset($homeSections[$sectionKey]) && is_array($homeSections[$sectionKey]))
        ? $homeSections[$sectionKey]
        : [];
    $defaults = app(\App\Services\FrontendComponentService::class)->defaultSectionData('play.team');
    $data = array_merge($defaults, $saved);
    $items = isset($data['items']) && is_array($data['items']) && $data['items'] !== []
        ? array_values($data['items'])
        : ($defaults['items'] ?? []);
    $primary = $themeSettings['primary_color'] ?? '#4a6cf7';
    $initialsOf = static function (string $name, string $fallback): string {
        $fallback = strtoupper(trim($fallback));
        if ($fallback !== '') {
            return mb_substr($fallback, 0, 2);
        }
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $letters = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $letters .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $letters !== '' ? $letters : 'PT';
    };
@endphp
@include('frontend.website.components.partials.theme-component-scroll-reveal')
<section class="py-14 md:py-20 bg-white dark:bg-gray-900 theme-scroll-reveal" data-theme-component="play.team" data-theme-anim="flip" data-scroll-reveal>
    <div class="website-section-inner">
        <div class="mx-auto mb-12 max-w-2xl text-center theme-fade" style="--theme-reveal-delay: 0ms;">
            <p class="mb-3 text-sm font-semibold uppercase tracking-widest" style="color: {{ $primary }}">{{ $data['eyebrow'] ?? 'Ons team' }}</p>
            <h2 class="mb-3 text-3xl md:text-4xl font-bold text-gray-900 dark:text-white">{{ $data['title'] ?? 'Mensen achter de rit' }}</h2>
            @if(!empty($data['subtitle']))
                <p class="text-gray-500 dark:text-gray-400">{{ $data['subtitle'] }}</p>
            @endif
        </div>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($items as $i => $item)
                @php
                    $name = trim((string) ($item['name'] ?? ''));
                    $role = trim((string) ($item['role'] ?? ''));
                    if ($name === '') continue;
                    $initials = $initialsOf($name, (string) ($item['initials'] ?? ''));
                @endphp
                <article class="theme-reveal-item rounded-2xl border border-gray-100 dark:border-gray-700 bg-[#f8f9ff] dark:bg-gray-800 px-6 py-10 text-center shadow-[0_10px_30px_rgba(74,108,247,0.08)]" style="--theme-reveal-delay: {{ 140 + ($i * 110) }}ms;">
                    @php $photo = trim((string) ($item['image_url'] ?? '')); @endphp
                    @if($photo !== '')
                        <img src="{{ $photo }}" alt="" class="theme-avatar mx-auto mb-5 h-24 w-24 rounded-full object-cover" loading="lazy">
                    @else
                    <div class="theme-avatar mx-auto mb-5 flex h-24 w-24 items-center justify-center rounded-full text-2xl font-bold text-white" style="background: {{ $primary }}">
                        {{ $initials }}
                    </div>
                    @endif
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $name }}</h3>
                    @if($role !== '')
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $role }}</p>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</section>
