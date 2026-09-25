@php
    $footerMapLayout = in_array($footerMapLayout ?? 'bottom', ['left', 'right', 'bottom'], true)
        ? $footerMapLayout
        : 'bottom';
    $footerMapSide = in_array($footerMapLayout, ['left', 'right'], true);
@endphp
<div
    class="footer-map-reveal w-full min-w-0 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-600 bg-gray-100 dark:bg-gray-800{{ $footerMapSide ? ' footer-map-reveal--side md:w-1/2 md:flex-1 md:self-stretch' : '' }}"
    style="{{ $footerMapSide ? 'min-height: '.$footerMapHeightPx.'px;' : 'height: '.$footerMapHeightPx.'px;' }} animation-delay: {{ $footerDelayMap }}ms;"
    data-map-position="{{ $footerMapLayout }}"
>
    @if($showFooterMap)
    <div id="footer-google-map" class="w-full h-full min-h-[200px] block min-w-0 box-border" style="width: 100%; height: 100%; min-height: 200px; min-width: 0;" data-api-key="{{ $googleMapsKeyForView }}" data-map-id="{{ $googleMapsMapId ?? '' }}" data-lat="{{ $footerData['map_lat'] ?? '' }}" data-lng="{{ $footerData['map_lng'] ?? '' }}" data-zoom="{{ $footerData['map_zoom'] ?? 17 }}" data-address="{{ $footerMapAddressStr }}" data-show-address-balloon="{{ !empty($footerData['map_show_address_balloon']) ? '1' : '0' }}"></div>
    @else
    <div class="w-full h-full min-h-[8rem] flex items-center justify-center text-sm text-gray-500 dark:text-gray-400 px-4 text-center">
        <span>Stel de Google Maps API-sleutel in via het Admin paneel om de kaart te tonen.</span>
    </div>
    @endif
</div>
