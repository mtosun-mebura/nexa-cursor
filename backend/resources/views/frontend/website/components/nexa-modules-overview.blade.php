@php
    $sectionKey = $sectionKey ?? 'component:website.nexa_modules_overview';
    $sectionData = (isset($homeSections) && is_array($homeSections) && isset($homeSections[$sectionKey]) && is_array($homeSections[$sectionKey]))
        ? $homeSections[$sectionKey]
        : [];

    $heroicons = config('heroicons.icons', []);
    $heroiconKeys = is_array($heroicons) ? array_keys($heroicons) : [];
    $resolveNexaModuleHeroiconKey = static function (?string $rawIcon) use ($heroiconKeys): string {
        $icon = trim((string) $rawIcon);
        $legacyMap = [
            'users' => 'user-group',
            'car' => 'truck',
            'wrench' => 'cog-6-tooth',
            'bulb' => 'light-bulb',
            'lightning' => 'bolt',
        ];
        if ($icon !== '' && isset($legacyMap[$icon])) {
            $icon = $legacyMap[$icon];
        }
        if ($icon !== '' && in_array($icon, $heroiconKeys, true)) {
            return $icon;
        }

        return 'user-group';
    };

    $defaultItems = [
        [
            'name' => 'NEXA Skillmatching',
            'description' => 'AI-gestuurde vacature-matching, kandidaatbeheer en sollicitatieflow. Van publicatie tot plaatsing in een gestroomlijnd proces.',
            'features' => [
                'Vacaturebeheer met branches en functies',
                'Kandidaat-pipeline en interviews',
                'AI-matching op skills, locatie en ervaring',
            ],
            'badge' => 'Beschikbaar',
            'badge_variant' => 'available',
            'icon' => 'user-group',
        ],
        [
            'name' => 'NEXA Taxi',
            'description' => 'Compleet ritbeheer voor taxi- en vervoersbedrijven. Van boeking tot facturatie, met voertuig- en tarievenbeheer.',
            'features' => [
                'Voertuigbeheer met foto\'s en kenmerken',
                'Ritaanvragen en boekingen',
                'Flexibele tarieven per voertuigtype',
            ],
            'badge' => 'Beschikbaar',
            'badge_variant' => 'available',
            'icon' => 'truck',
        ],
        [
            'name' => 'NEXA Garage',
            'description' => 'Werkplaatsbeheer voor garages en autobedrijven. Werkorders, planning, onderdelen en klantcommunicatie.',
            'features' => [
                'Werkorderbeheer en planning',
                'Voertuighistorie per klant',
                'Onderdelenvoorraad en leveranciers',
            ],
            'badge' => 'Binnenkort',
            'badge_variant' => 'soon',
            'icon' => 'cog-6-tooth',
        ],
    ];

    $items = isset($sectionData['items']) && is_array($sectionData['items']) ? array_values($sectionData['items']) : [];
    if (count($items) < 3) {
        $items = array_merge($items, array_slice($defaultItems, count($items)));
    }
@endphp

<section id="modules-overview" class="py-16 md:py-20 bg-white dark:bg-gray-900 nexa-modules-overview-scroll-reveal">
    <div class="website-section-inner">
        <div class="text-center mb-12">
            <p class="text-3xl md:text-4xl font-bold text-blue-600 dark:text-blue-300 mb-6 nexa-modules-animate-item nexa-modules-animate-eyebrow">{{ $sectionData['eyebrow'] ?? 'Onze modules' }}</p>
            <h3 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white mb-3 nexa-modules-animate-item nexa-modules-animate-title">{{ $sectionData['title'] ?? 'Alles wat uw bedrijf nodig heeft' }}</h3>
            <p class="text-gray-600 dark:text-gray-300 max-w-2xl mx-auto nexa-modules-animate-item nexa-modules-animate-subtitle">
                {{ $sectionData['subtitle'] ?? 'Elke module werkt standalone of in combinatie. Installeer alleen wat u nodig heeft.' }}
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($items as $item)
                @php
                    $iconKey = $resolveNexaModuleHeroiconKey(isset($item['icon']) ? (string) $item['icon'] : null);
                    $iconDef = is_array($heroicons[$iconKey] ?? null) ? $heroicons[$iconKey] : ($heroicons['user-group'] ?? []);
                    $iconSvg = is_array($iconDef) ? (string) ($iconDef['svg'] ?? '') : '';
                    $amberKeys = ['truck', 'paper-airplane', 'cake', 'ticket', 'shopping-cart', 'map', 'receipt-percent', 'building-storefront'];
                    $emeraldKeys = ['cog-6-tooth', 'key', 'clipboard-document-check', 'sparkles', 'wrench', 'wrench-screwdriver', 'puzzle-piece', 'lifebuoy', 'beaker', 'clipboard-document-list'];
                    $iconTone = in_array($iconKey, $amberKeys, true)
                        ? 'amber'
                        : (in_array($iconKey, $emeraldKeys, true) ? 'emerald' : 'blue');
                    $badgeVariant = $item['badge_variant'] ?? 'available';
                    $features = isset($item['features']) && is_array($item['features']) ? array_values(array_filter($item['features'], fn ($f) => trim((string) $f) !== '')) : [];
                @endphp
                <article class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/50 p-6 flex flex-col h-full shadow-sm relative transform-gpu will-change-transform transition-[transform,box-shadow] duration-1000 ease-[cubic-bezier(0.16,1,0.3,1)] nexa-modules-hover-card nexa-modules-animate-item nexa-modules-animate-card">
                    <div class="w-16 h-16 rounded-xl mx-auto flex items-center justify-center mb-4 @if($iconTone === 'amber') bg-amber-100 dark:bg-amber-500/20 text-amber-600 dark:text-amber-300 @elseif($iconTone === 'emerald') bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-300 @else bg-blue-100 dark:bg-blue-500/20 text-blue-600 dark:text-blue-300 @endif">
                        @if($iconSvg !== '')
                            <svg class="w-8 h-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">{!! $iconSvg !!}</svg>
                        @endif
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">{{ $item['name'] ?? '' }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-4 flex-grow">{{ $item['description'] ?? '' }}</p>
                    <ul class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                        @foreach($features as $feature)
                            <li class="flex items-start gap-2"><span class="text-emerald-500 mt-0.5">✓</span>{{ $feature }}</li>
                        @endforeach
                    </ul>
                    @if(!empty($item['badge']))
                        <div class="mt-5 flex justify-center">
                            <span class="text-[10px] font-semibold uppercase tracking-wider px-2 py-1 rounded-full {{ $badgeVariant === 'soon' ? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:ring-1 dark:ring-gray-500/40' : 'bg-green-100 text-green-800 dark:bg-emerald-950 dark:text-emerald-100 dark:ring-1 dark:ring-emerald-500/35' }}">{{ $item['badge'] }}</span>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</section>
@once
    @push('styles')
        <style>
            .nexa-modules-overview-scroll-reveal .nexa-modules-animate-item {
                opacity: 0;
                transform: translateY(36px);
                transition: opacity 0.7s ease, transform 0.7s ease;
            }
            .nexa-modules-overview-scroll-reveal.is-visible .nexa-modules-animate-item {
                opacity: 1;
                transform: translateY(0);
            }
            .nexa-modules-overview-scroll-reveal .nexa-modules-animate-eyebrow { transition-delay: 0ms; }
            .nexa-modules-overview-scroll-reveal .nexa-modules-animate-title { transition-delay: 130ms; }
            .nexa-modules-overview-scroll-reveal .nexa-modules-animate-subtitle { transition-delay: 260ms; }
            .nexa-modules-overview-scroll-reveal .nexa-modules-animate-card:nth-of-type(1) { transition-delay: 420ms; }
            .nexa-modules-overview-scroll-reveal .nexa-modules-animate-card:nth-of-type(2) { transition-delay: 560ms; }
            .nexa-modules-overview-scroll-reveal .nexa-modules-animate-card:nth-of-type(3) { transition-delay: 700ms; }
            .nexa-modules-overview-scroll-reveal.is-visible .nexa-modules-animate-card {
                transform: translateY(0);
            }
            .nexa-modules-overview-scroll-reveal .nexa-modules-animate-card {
                transform: translateY(42px);
            }
            @keyframes nexaModulesSoftFloat {
                0% { transform: translateY(-4px) scale(1.005); }
                50% { transform: translateY(-10px) scale(1.01); }
                100% { transform: translateY(-4px) scale(1.005); }
            }
            .nexa-modules-hover-card:hover {
                animation: nexaModulesSoftFloat 2.2s cubic-bezier(0.25, 0.8, 0.25, 1) infinite;
                box-shadow: 0 14px 28px rgba(15, 23, 42, 0.14);
            }
            .dark .nexa-modules-hover-card:hover {
                box-shadow: 0 16px 32px rgba(0, 0, 0, 0.35);
            }
            @media (prefers-reduced-motion: reduce) {
                .nexa-modules-hover-card:hover {
                    animation: none;
                    transform: translateY(-4px);
                }
            }
        </style>
    @endpush
    @push('scripts')
        <script>
            (function () {
                if (window.__nexaModulesOverviewObserverInit) return;
                window.__nexaModulesOverviewObserverInit = true;
                var opts = { threshold: 0.2 };
                var sections = document.querySelectorAll('.nexa-modules-overview-scroll-reveal');
                if (typeof window.nexaObserveWhenVisible === 'function') {
                    window.nexaObserveWhenVisible(sections, function (el) {
                        el.classList.add('is-visible');
                    }, opts);
                    return;
                }
                var observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                            observer.unobserve(entry.target);
                        }
                    });
                }, opts);
                sections.forEach(function (el) {
                    observer.observe(el);
                });
            })();
        </script>
    @endpush
@endonce
