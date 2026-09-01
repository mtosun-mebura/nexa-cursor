@php
    $sectionKey = $sectionKey ?? 'component:play.contact_split';
    $saved = (isset($homeSections) && is_array($homeSections) && isset($homeSections[$sectionKey]) && is_array($homeSections[$sectionKey]))
        ? $homeSections[$sectionKey]
        : [];
    $defaults = app(\App\Services\FrontendComponentService::class)->defaultSectionData('play.contact_split');
    $data = array_merge($defaults, $saved);
    $primary = $themeSettings['primary_color'] ?? '#4a6cf7';
    $email = trim((string) ($data['email'] ?? ''));
    $phone = trim((string) ($data['phone'] ?? ''));
    $address = trim((string) ($data['address'] ?? ''));
    $hours = trim((string) ($data['hours'] ?? ''));
    $photo = trim((string) ($data['image_url'] ?? ''));
    $cta = trim((string) ($data['cta_label'] ?? 'Verstuur bericht'));
    $formId = 'play-contact-'.substr(md5($sectionKey), 0, 8);
@endphp
@include('frontend.website.components.partials.theme-component-scroll-reveal')
<section class="py-14 md:py-20 bg-white dark:bg-gray-900 theme-scroll-reveal" data-theme-component="play.contact_split" data-theme-anim="split" data-scroll-reveal>
    <div class="website-section-inner grid gap-12 md:grid-cols-2">
        <div class="theme-split-left">
            <p class="mb-3 text-sm font-semibold uppercase tracking-widest" style="color: {{ $primary }}">{{ $data['eyebrow'] ?? 'Contact' }}</p>
            <h2 class="mb-4 text-3xl md:text-4xl font-bold text-gray-900 dark:text-white">{{ $data['title'] ?? '' }}</h2>
            @if(!empty($data['subtitle']))
                <p class="mb-8 text-gray-500 dark:text-gray-400">{{ $data['subtitle'] }}</p>
            @endif
            @if($photo !== '')
                <img src="{{ $photo }}" alt="" class="mb-8 w-full max-w-sm rounded-2xl object-cover shadow-lg" loading="lazy">
            @endif
            <ul class="space-y-3 text-gray-700 dark:text-gray-200">
                @if($address !== '')
                    <li>{{ $address }}</li>
                @endif
                @if($phone !== '')
                    <li><a href="tel:{{ preg_replace('/\s+/', '', $phone) }}" class="hover:underline">{{ $phone }}</a></li>
                @endif
                @if($email !== '')
                    <li><a href="mailto:{{ $email }}" class="hover:underline">{{ $email }}</a></li>
                @endif
                @if($hours !== '')
                    <li>{{ $hours }}</li>
                @endif
            </ul>
        </div>
        <form id="{{ $formId }}" class="theme-split-right space-y-4" action="{{ $email !== '' ? 'mailto:'.$email : '#' }}" method="get">
            <div class="theme-field" style="--theme-reveal-delay: 180ms;">
                <label class="mb-1 block text-sm text-gray-500" for="{{ $formId }}-name">Naam</label>
                <input id="{{ $formId }}-name" name="name" type="text" required class="kt-input w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3">
            </div>
            <div class="theme-field" style="--theme-reveal-delay: 260ms;">
                <label class="mb-1 block text-sm text-gray-500" for="{{ $formId }}-email">E-mail</label>
                <input id="{{ $formId }}-email" name="email" type="email" required class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3">
            </div>
            <div class="theme-field" style="--theme-reveal-delay: 340ms;">
                <label class="mb-1 block text-sm text-gray-500" for="{{ $formId }}-msg">Bericht</label>
                <textarea id="{{ $formId }}-msg" name="body" rows="4" required class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3"></textarea>
            </div>
            <div class="theme-field" style="--theme-reveal-delay: 420ms;">
                <button type="submit" class="rounded-lg px-6 py-3 text-sm font-semibold text-white" style="background: {{ $primary }}">{{ $cta }}</button>
            </div>
        </form>
    </div>
</section>
