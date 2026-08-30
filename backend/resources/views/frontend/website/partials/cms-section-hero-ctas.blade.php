@php
    $justifyClass = ($justify ?? 'start') === 'center' ? 'justify-center' : 'justify-center lg:justify-start';
    $onDark = $onDark ?? false;
@endphp
@if($v('_cta') && (($v('_cta_primary') && !empty($sectionData['cta_primary_text'])) || ($v('_cta_secondary') && !empty($sectionData['cta_secondary_text']))))
<div class="mt-6 flex flex-col sm:flex-row gap-4 {{ $justifyClass }}">
    @if($v('_cta_primary') && !empty($sectionData['cta_primary_text']))
    @php
        $heroPrimaryBg = $normHex($sectionData['cta_primary_bg'] ?? null, $onDark ? '#ffffff' : $primaryColor);
        $heroPrimaryColor = $normHex($sectionData['cta_primary_text_color'] ?? null, $onDark ? $primaryColor : '#ffffff');
        $heroPrimaryBorder = $sectionData['cta_primary_border'] ?? '';
        $heroPrimaryBorder = $heroPrimaryBorder !== '' ? $normHex($heroPrimaryBorder, $primaryColor) : 'transparent';
        $heroPrimaryHoverCss = \App\Services\WebsiteBuilderService::ctaButtonHoverCss($sectionData, 'cta_primary');
    @endphp
    <a href="{{ $url($sectionData['cta_primary_url'] ?? '/register') }}" class="inline-flex justify-center items-center px-8 py-4 text-base font-medium rounded-lg border-2 transition-all duration-200 hover:brightness-90 hover:shadow-xl hover:-translate-y-1 dark:hover:brightness-125 dark:hover:shadow-2xl{{ $heroPrimaryHoverCss !== '' ? ' nexa-cta-btn--custom-hover' : '' }}" style="background-color: {{ $heroPrimaryBg }}; color: {{ $heroPrimaryColor }}; border-color: {{ $heroPrimaryBorder }};{{ $heroPrimaryHoverCss }}">
        {{ $sectionData['cta_primary_text'] }}
    </a>
    @endif
    @if($v('_cta_secondary') && !empty($sectionData['cta_secondary_text']))
    @php
        $heroSecondaryBgRaw = $sectionData['cta_secondary_bg'] ?? '';
        $heroSecondaryBg = $heroSecondaryBgRaw !== '' ? $normHex($heroSecondaryBgRaw, $primaryColor) : 'transparent';
        $heroSecondaryBorder = $normHex($sectionData['cta_secondary_border'] ?? null, $onDark ? '#ffffff' : $primaryColor);
        $heroSecondaryColor = $normHex($sectionData['cta_secondary_text_color'] ?? null, $onDark ? '#ffffff' : $primaryColor);
        $heroSecondaryHoverCss = \App\Services\WebsiteBuilderService::ctaButtonHoverCss($sectionData, 'cta_secondary');
    @endphp
    <a href="{{ $url($sectionData['cta_secondary_url'] ?? '/jobs') }}" class="inline-flex justify-center items-center px-8 py-4 text-base font-medium rounded-lg border-2 transition-all duration-200 hover:shadow-xl hover:-translate-y-1{{ $onDark ? ' hover:bg-white/15' : ' hover:bg-gray-200 dark:hover:bg-gray-600' }}{{ $heroSecondaryHoverCss !== '' ? ' nexa-cta-btn--custom-hover' : '' }}" style="background-color: {{ $heroSecondaryBg }}; border-color: {{ $heroSecondaryBorder }}; color: {{ $heroSecondaryColor }};{{ $heroSecondaryHoverCss }}">
        {{ $sectionData['cta_secondary_text'] }}
    </a>
    @endif
</div>
@endif
