@php
    $homeSections = $homeSections ?? [];
    $branding = $branding ?? app(\App\Services\WebsiteBuilderService::class)->getSiteBranding();
    $themeSettings = $themeSettings ?? [];
@endphp
    <footer class="{{ !empty($homeSections) ? 'bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300' }} border-t border-gray-200 dark:border-gray-600">
        @if(!empty($homeSections) && ($homeSections['visibility']['footer'] ?? true) && (!empty($homeSections['footer']) || !empty($homeSections['copyright'])))
            @php
                $footerData = $homeSections['footer'] ?? [];
                $websiteBuilder = app(\App\Services\WebsiteBuilderService::class);
                $footerLogoUrl = ! empty($footerData['logo_url'])
                    ? $websiteBuilder->storageUrlToDisplayUrl($footerData['logo_url'])
                    : ($branding['logo_url'] ?? null);
                if (! empty($footerLogoUrl)) {
                    $footerLogoUrl = $websiteBuilder->storageUrlToDisplayUrl($footerLogoUrl);
                }
                $footerLogoDarkUrl = null;
                if (empty($footerData['logo_url']) && ! empty($branding['logo_dark_url'])) {
                    $footerLogoDarkUrl = $websiteBuilder->storageUrlToDisplayUrl($branding['logo_dark_url']);
                }
                $footerLogoAlt = !empty($footerData['logo_alt']) ? $footerData['logo_alt'] : ($branding['site_name'] ?? config('app.name'));
                $footerUsesCustomLogo = !empty($footerData['logo_url']);
                if ($footerUsesCustomLogo) {
                    $footerLogoTw = max(12, min(30, (int) ($footerData['logo_height'] ?? 12)));
                    $footerLogoImgClass = 'w-auto h-'.$footerLogoTw.' object-contain';
                    $footerLogoImgStyle = '';
                } else {
                    $footerLogoPx = (int) ($branding['logo_size_px'] ?? app(\App\Services\WebsiteBuilderService::class)->resolveLogoSizePx());
                    $footerLogoImgClass = 'w-auto max-w-[350px] object-contain';
                    $footerLogoImgStyle = 'height: '.$footerLogoPx.'px';
                }
                $footerLinkUrl = function($u) {
                    if (empty($u)) return url('/');
                    $u = trim($u);
                    return (strpos($u, 'http') === 0 || strpos($u, '//') === 0) ? $u : url($u);
                };
            @endphp
            <div class="w-full">
                <div class="py-8 container-custom site-footer-reveal scroll-reveal-section" data-scroll-reveal>
                    @php
                        $footVis = $homeSections['visibility'] ?? [];
                        $footerMapVisible = (bool) ($footVis['footer_map'] ?? true);
                        $googleMapsKeyForView = trim((string)($googleMapsApiKey ?? ''));
                        $showFooterMap = $footerMapVisible && $googleMapsKeyForView !== '';
                        $footerMapSize = $footerData['map_size'] ?? 'normal';
                        $footerMapHeightPx = $footerMapSize === 'small' ? 200 : ($footerMapSize === 'large' ? 400 : 300);
                        $footerMapWidthClass = 'w-full';
                        $footerMapCityOnly = !empty($footerData['map_city_only']);
                        $footerMapAddressStr = $footerMapCityOnly
                            ? trim((string) ($footerData['map_city'] ?? ''))
                            : trim(($footerData['map_street'] ?? '') . ' ' . ($footerData['map_huisnummer'] ?? '') . ', ' . ($footerData['map_postcode'] ?? '') . ' ' . ($footerData['map_city'] ?? ''), ' ,');
                        $footerLogoAlign = isset($footerData['logo_align']) && in_array($footerData['logo_align'], ['left', 'center', 'right'], true) ? $footerData['logo_align'] : 'left';
                        $footerLogoAlignWrapper = $footerLogoAlign === 'center' ? 'flex flex-col items-center' : ($footerLogoAlign === 'right' ? 'flex flex-col items-end' : 'flex flex-col items-start');
                        $footerLogoAlignText = $footerLogoAlign === 'center' ? 'text-center' : ($footerLogoAlign === 'right' ? 'text-right' : 'text-left');
                        $footerQuickLinksAlign = isset($footerData['quick_links_align']) && in_array($footerData['quick_links_align'], ['left', 'center', 'right'], true) ? $footerData['quick_links_align'] : 'left';
                        $footerSupportLinksAlign = isset($footerData['support_links_align']) && in_array($footerData['support_links_align'], ['left', 'center', 'right'], true) ? $footerData['support_links_align'] : 'left';
                        $footerQuickLinksAlignClass = $footerQuickLinksAlign === 'center' ? 'text-center' : ($footerQuickLinksAlign === 'right' ? 'text-right' : 'text-left');
                        $footerSupportLinksAlignClass = $footerSupportLinksAlign === 'center' ? 'text-center' : ($footerSupportLinksAlign === 'right' ? 'text-right' : 'text-left');
                        $showQuickLinks = ($footVis['footer_quick_links'] ?? true) && !empty($footerData['quick_links']);
                        $showSupportLinks = ($footVis['footer_support_links'] ?? true) && !empty($footerData['support_links']);
                        $footerLinkColumnsCount = ($showQuickLinks ? 1 : 0) + ($showSupportLinks ? 1 : 0);
                        $footerShowMapRight = $footerMapVisible;
                        $footerGridCols = $footerShowMapRight ? 'md:grid-cols-2' : ($footerLinkColumnsCount === 2 ? 'md:grid-cols-4' : ($footerLinkColumnsCount === 1 ? 'md:grid-cols-3' : 'md:grid-cols-1'));
                        $footerGridWithMapClass = $footerShowMapRight ? ' footer-grid-with-map' : '';
                        $footerFirstColSpan = $footerLinkColumnsCount === 2 ? 'md:col-span-2' : ($footerLinkColumnsCount === 1 ? 'md:col-span-2' : 'md:col-span-1');
                        $footerQuickLinksCol = $footerLinkColumnsCount === 2 ? 'md:col-start-3' : 'md:col-start-3';
                        $footerSupportLinksCol = $footerLinkColumnsCount === 2 ? 'md:col-start-4' : 'md:col-start-3';
                        $footerSocialLinks = [];
                        $footerSocialBases = ['social_facebook' => 'https://www.facebook.com/', 'social_instagram' => 'https://www.instagram.com/', 'social_x' => 'https://x.com/', 'social_linkedin' => 'https://www.linkedin.com/', 'social_youtube' => 'https://www.youtube.com/', 'social_tiktok' => 'https://www.tiktok.com/@'];
                        foreach (['facebook' => 'social_facebook', 'instagram' => 'social_instagram', 'x' => 'social_x', 'linkedin' => 'social_linkedin', 'youtube' => 'social_youtube', 'tiktok' => 'social_tiktok'] as $key => $field) {
                            $u = trim((string)($footerData[$field] ?? ''));
                            if ($u === '') continue;
                            if (strpos($u, 'http') === 0 || strpos($u, '//') === 0) {
                                $footerSocialLinks[$key] = $footerLinkUrl($u);
                            } else {
                                $base = $footerSocialBases[$field];
                                $id = $field === 'social_tiktok' ? ltrim($u, '@') : $u;
                                $footerSocialLinks[$key] = $base . $id;
                            }
                        }
                        $footerAnimStepMs = 115;
                        $footerQuickVisible = 0;
                        foreach (($footerData['quick_links'] ?? []) as $_ql) {
                            if (!empty($_ql['label'])) { $footerQuickVisible++; }
                        }
                        $footerSupportVisible = 0;
                        foreach (($footerData['support_links'] ?? []) as $_sl) {
                            if (!empty($_sl['label'])) { $footerSupportVisible++; }
                        }
                        $footerDelayQuickH3 = 1320;
                        $footerDelayQuickLi0 = 1390;
                        $footerDelaySupH3 = $footerQuickVisible > 0
                            ? ($footerDelayQuickLi0 + $footerQuickVisible * $footerAnimStepMs + 80)
                            : $footerDelayQuickH3;
                        $footerDelaySupLi0 = $footerDelaySupH3 + 70;
                        $footerDelayMap = $footerSupportVisible > 0
                            ? ($footerDelaySupLi0 + $footerSupportVisible * $footerAnimStepMs + 140)
                            : ($footerQuickVisible > 0
                                ? ($footerDelayQuickLi0 + $footerQuickVisible * $footerAnimStepMs + 200)
                                : 1680);
                        /* Kaart eerder zichtbaar / tegelijk met inhoud: cap op animatie-delay */
                        $footerDelayMap = min($footerDelayMap, 420);
                    @endphp
                    <div class="footer-reveal-soft">
                    <div class="grid grid-cols-1 {{ $footerGridCols }} gap-6 {{ $footerShowMapRight ? 'md:grid-rows-[auto]' : '' }}{{ $footerGridWithMapClass }}">
                        @if($footerShowMapRight)
                        {{-- Linkerkant (50%): logo + tagline, daaronder Snelle Links (links) en Ondersteuning (rechts) naast elkaar --}}
                        <div class="flex flex-col min-w-0">
                            <div class="{{ $footerLogoAlignWrapper }} w-full max-w-full min-w-0">
                                @if(($footVis['footer_logo'] ?? true) && !empty($footerLogoUrl))
                                    <div class="footer-animate-brand inline-block mb-4">
                                    @if(!empty($footerLogoDarkUrl))
                                        <img src="{{ $footerLogoUrl }}" alt="{{ $footerLogoAlt }}" class="fe-logo-light {{ $footerLogoImgClass }}" @if($footerLogoImgStyle !== '') style="{{ $footerLogoImgStyle }}" @endif>
                                        <img src="{{ $footerLogoDarkUrl }}" alt="{{ $footerLogoAlt }}" class="fe-logo-dark {{ $footerLogoImgClass }}" @if($footerLogoImgStyle !== '') style="{{ $footerLogoImgStyle }}" @endif>
                                    @else
                                        <img src="{{ $footerLogoUrl }}" alt="{{ $footerLogoAlt }}" class="{{ $footerLogoImgClass }}" @if($footerLogoImgStyle !== '') style="{{ $footerLogoImgStyle }}" @endif>
                                    @endif
                                    </div>
                                @elseif($footVis['footer_logo'] ?? true)
                                    <div class="footer-animate-brand inline-block mb-4">
                                        @include('frontend.layouts.partials.brand-logo', ['branding' => $branding, 'logoHref' => route('home')])
                                    </div>
                                @endif
                                @if(($footVis['footer_tagline'] ?? true) && !empty($homeSections['footer']['tagline']))
                                    <div class="footer-animate-tagline text-gray-700 dark:text-gray-200 mb-4 w-full max-w-full min-w-0 leading-relaxed prose prose-sm dark:prose-invert prose-p:my-1 prose-ul:my-1 prose-ol:my-1 max-w-none [&_*]:!text-gray-900 dark:[&_*]:!text-gray-200 {{ $footerLogoAlignText }}">
                                        {!! $homeSections['footer']['tagline'] !!}
                                    </div>
                                @endif
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-0 w-full max-w-full min-w-0">
                                @if($showQuickLinks)
                                <div class="{{ $footerQuickLinksAlignClass }} min-w-0">
                                    <h3 class="footer-footer-anim-left text-lg font-semibold text-gray-900 dark:text-white mb-3" style="animation-delay: {{ $footerDelayQuickH3 }}ms;">{{ $footerData['quick_links_title'] ?? 'Snelle Links' }}</h3>
                                    @php $footerQlAnim = 0; @endphp
                                    <ul class="footer-quick-links-list space-y-3">
                                        @foreach($footerData['quick_links'] as $link)
                                            @if(!empty($link['label']))
                                        @php $footerQlDelayMs = $footerDelayQuickLi0 + $footerQlAnim * $footerAnimStepMs; $footerQlAnim++; @endphp
                                        <li class="footer-quick-link-item footer-footer-anim-left" style="animation-delay: {{ $footerQlDelayMs }}ms;">@if(!empty(trim($link['url'] ?? '')))<a href="{{ $footerLinkUrl($link['url']) }}" class="text-gray-800 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 hover:underline transition-colors duration-200">{{ $link['label'] }}</a>@else<span class="text-gray-800 dark:text-gray-200">{{ $link['label'] }}</span>@endif</li>
                                            @endif
                                        @endforeach
                                    </ul>
                                </div>
                                @endif
                                @if($showSupportLinks)
                                <div class="{{ $footerSupportLinksAlignClass }} min-w-0">
                                    <h3 class="footer-footer-anim-left text-lg font-semibold text-gray-900 dark:text-white mb-4" style="animation-delay: {{ $footerDelaySupH3 }}ms;">{{ $footerData['support_links_title'] ?? 'Ondersteuning' }}</h3>
                                    @php $footerSlAnim = 0; @endphp
                                    <ul class="footer-support-links-list space-y-3">
                                        @foreach($footerData['support_links'] as $link)
                                            @if(!empty($link['label']))
                                        @php $footerSlDelayMs = $footerDelaySupLi0 + $footerSlAnim * $footerAnimStepMs; $footerSlAnim++; @endphp
                                        <li class="footer-support-link-item footer-footer-anim-left" style="animation-delay: {{ $footerSlDelayMs }}ms;">@if(!empty(trim($link['url'] ?? '')))<a href="{{ $footerLinkUrl($link['url']) }}" class="text-gray-800 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 hover:underline transition-colors duration-200">{{ $link['label'] }}</a>@else<span class="text-gray-800 dark:text-gray-200">{{ $link['label'] }}</span>@endif</li>
                                            @endif
                                        @endforeach
                                    </ul>
                                </div>
                                @endif
                            </div>
                        </div>
                        {{-- Rechterkant (50%): kaart over volle breedte van de rechterhelft --}}
                        <div class="w-full min-w-0 flex flex-col">
                            <div class="footer-map-reveal w-full min-w-0 flex-1 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 mt-2 md:mt-0" style="height: {{ $footerMapHeightPx }}px; animation-delay: {{ $footerDelayMap }}ms;">
                                @if($showFooterMap)
                                <div id="footer-google-map" class="w-full h-full min-h-[200px] block min-w-0 box-border" style="width: 100%; height: 100%; min-height: 200px; min-width: 0;" data-api-key="{{ $googleMapsKeyForView }}" data-map-id="{{ $googleMapsMapId ?? '' }}" data-lat="{{ $footerData['map_lat'] ?? '' }}" data-lng="{{ $footerData['map_lng'] ?? '' }}" data-zoom="{{ $footerData['map_zoom'] ?? 17 }}" data-address="{{ $footerMapAddressStr }}" data-show-address-balloon="{{ !empty($footerData['map_show_address_balloon']) ? '1' : '0' }}"></div>
                                @else
                                <div class="w-full h-full min-h-[8rem] flex items-center justify-center text-sm text-gray-500 dark:text-gray-400 px-4 text-center">
                                    Stel de Google Maps API-sleutel in via <strong>Admin → Instellingen → Maps</strong> om de kaart te tonen.
                                </div>
                                @endif
                            </div>
                        </div>
                        @else
                        {{-- Geen map of geen linkkolommen: één kolom logo+tagline+kaart --}}
                        <div class="col-span-1 {{ $footerFirstColSpan }}">
                            <div class="{{ $footerLogoAlignWrapper }}">
                                @if(($footVis['footer_logo'] ?? true) && !empty($footerLogoUrl))
                                    <div class="footer-animate-brand inline-block mb-4">
                                    @if(!empty($footerLogoDarkUrl))
                                        <img src="{{ $footerLogoUrl }}" alt="{{ $footerLogoAlt }}" class="fe-logo-light {{ $footerLogoImgClass }}" @if($footerLogoImgStyle !== '') style="{{ $footerLogoImgStyle }}" @endif>
                                        <img src="{{ $footerLogoDarkUrl }}" alt="{{ $footerLogoAlt }}" class="fe-logo-dark {{ $footerLogoImgClass }}" @if($footerLogoImgStyle !== '') style="{{ $footerLogoImgStyle }}" @endif>
                                    @else
                                        <img src="{{ $footerLogoUrl }}" alt="{{ $footerLogoAlt }}" class="{{ $footerLogoImgClass }}" @if($footerLogoImgStyle !== '') style="{{ $footerLogoImgStyle }}" @endif>
                                    @endif
                                    </div>
                                @elseif($footVis['footer_logo'] ?? true)
                                    <div class="footer-animate-brand inline-block mb-4">
                                        @include('frontend.layouts.partials.brand-logo', ['branding' => $branding, 'logoHref' => route('home')])
                                    </div>
                                @endif
                                @if(($footVis['footer_tagline'] ?? true) && !empty($homeSections['footer']['tagline']))
                                    <div class="footer-animate-tagline text-gray-700 dark:text-gray-200 mb-4 w-full leading-relaxed prose prose-sm dark:prose-invert prose-p:my-1 prose-ul:my-1 prose-ol:my-1 max-w-none [&_*]:!text-gray-900 dark:[&_*]:!text-gray-200 {{ $footerLogoAlignText }}">
                                        {!! $homeSections['footer']['tagline'] !!}
                                    </div>
                                @endif
                            </div>
                            @if($footerMapVisible)
                            <div class="footer-map-reveal w-full rounded-lg overflow-hidden border border-gray-200 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 mt-2" style="height: {{ $footerMapHeightPx }}px; animation-delay: {{ $footerDelayMap }}ms;">
                                @if($showFooterMap)
                                <div id="footer-google-map" class="w-full h-full min-h-full block" style="width: 100%; height: 100%; min-height: 100%;" data-api-key="{{ $googleMapsKeyForView }}" data-map-id="{{ $googleMapsMapId ?? '' }}" data-lat="{{ $footerData['map_lat'] ?? '' }}" data-lng="{{ $footerData['map_lng'] ?? '' }}" data-zoom="{{ $footerData['map_zoom'] ?? 17 }}" data-address="{{ $footerMapAddressStr }}" data-show-address-balloon="{{ !empty($footerData['map_show_address_balloon']) ? '1' : '0' }}"></div>
                                @else
                                <div class="w-full h-full min-h-[8rem] flex items-center justify-center text-sm text-gray-500 dark:text-gray-400 px-4 text-center">
                                    Stel de Google Maps API-sleutel in via <strong>Admin → Instellingen → Maps</strong> om de kaart te tonen.
                                </div>
                                @endif
                            </div>
                            @endif
                        </div>
                        @endif
                        @if(!$footerShowMapRight)
                        @if($showQuickLinks)
                        <div class="{{ $footerQuickLinksCol }} {{ $footerQuickLinksAlignClass }}">
                            <h3 class="footer-footer-anim-left text-lg font-semibold text-gray-900 dark:text-white mb-3" style="animation-delay: {{ $footerDelayQuickH3 }}ms;">{{ $footerData['quick_links_title'] ?? 'Snelle Links' }}</h3>
                            @php $footerQlAnim = 0; @endphp
                            <ul class="footer-quick-links-list space-y-3">
                                @foreach($footerData['quick_links'] as $link)
                                    @if(!empty($link['label']))
                                @php $footerQlDelayMs = $footerDelayQuickLi0 + $footerQlAnim * $footerAnimStepMs; $footerQlAnim++; @endphp
                                <li class="footer-quick-link-item footer-footer-anim-left" style="animation-delay: {{ $footerQlDelayMs }}ms;">@if(!empty(trim($link['url'] ?? '')))<a href="{{ $footerLinkUrl($link['url']) }}" class="text-gray-800 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 hover:underline transition-colors duration-200">{{ $link['label'] }}</a>@else<span class="text-gray-800 dark:text-gray-200">{{ $link['label'] }}</span>@endif</li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                        @endif
                        @if($showSupportLinks)
                        <div class="{{ $footerSupportLinksCol }} {{ $footerSupportLinksAlignClass }}">
                            <h3 class="footer-footer-anim-left text-lg font-semibold text-gray-900 dark:text-white mb-4" style="animation-delay: {{ $footerDelaySupH3 }}ms;">{{ $footerData['support_links_title'] ?? 'Ondersteuning' }}</h3>
                            @php $footerSlAnim = 0; @endphp
                            <ul class="footer-support-links-list space-y-3">
                                @foreach($footerData['support_links'] as $link)
                                    @if(!empty($link['label']))
                                @php $footerSlDelayMs = $footerDelaySupLi0 + $footerSlAnim * $footerAnimStepMs; $footerSlAnim++; @endphp
                                <li class="footer-support-link-item footer-footer-anim-left" style="animation-delay: {{ $footerSlDelayMs }}ms;">@if(!empty(trim($link['url'] ?? '')))<a href="{{ $footerLinkUrl($link['url']) }}" class="text-gray-800 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 hover:underline transition-colors duration-200">{{ $link['label'] }}</a>@else<span class="text-gray-800 dark:text-gray-200">{{ $link['label'] }}</span>@endif</li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                        @endif
                        @endif
                    </div>
                    @if(($footVis['footer_social'] ?? true) && count($footerSocialLinks) > 0)
                        <div class="w-full mt-6 pt-6 border-t border-gray-200 dark:border-gray-600">
                            @include('frontend.layouts.partials.footer-social-icons', ['footerSocialLinks' => $footerSocialLinks, 'footerLogoAlign' => 'center'])
                        </div>
                    @endif
                    </div>
                </div>
                @if(!empty($homeSections['copyright']))
                    <div class="border-t border-gray-300 dark:border-gray-600 py-4 container-custom">
                        <p class="text-gray-700 dark:text-gray-200 text-sm">
                            {{ str_replace('{year}', date('Y'), $homeSections['copyright']) }}
                        </p>
                    </div>
                @endif
            </div>
        @else
            <div class="container-custom py-8">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    @include('frontend.layouts.partials.brand-logo', [
                        'branding' => $branding,
                        'logoHref' => route('home'),
                        'logoImgClassExtra' => 'opacity-80',
                    ])
                    @php
                        $footerBrandName = trim((string) ($branding['site_name'] ?? config('app.name')));
                        if (strcasecmp($footerBrandName, 'NEXA Taxi') === 0) {
                            $footerBrandName = 'NEXA';
                        }
                    @endphp
                    <p class="text-sm">
                        {{ !empty($themeSettings['footer_text']) ? $themeSettings['footer_text'] : '© ' . date('Y') . ' ' . $footerBrandName }}
                    </p>
                </div>
            </div>
        @endif
    </footer>
