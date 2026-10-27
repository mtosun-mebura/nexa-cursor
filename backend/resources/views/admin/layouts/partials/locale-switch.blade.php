@php
    $adminLocaleIsEn = app()->getLocale() === 'en';
    $adminLocaleTarget = $adminLocaleIsEn ? 'nl' : 'en';
    $adminLocaleLabel = $adminLocaleIsEn ? 'Nederlands' : 'English';
@endphp
<a href="{{ route('admin.locale.switch', $adminLocaleTarget) }}"
   id="admin-locale-switch"
   data-locale-target="{{ $adminLocaleTarget }}"
   class="kt-btn kt-btn-ghost kt-btn-icon size-8 hover:bg-background shrink-0"
   title="{{ $adminLocaleLabel }}"
   aria-label="{{ $adminLocaleLabel }}"
   hreflang="{{ $adminLocaleTarget }}">
    @if($adminLocaleIsEn)
        <svg viewBox="0 0 90 60" class="h-4 w-6 shrink-0 overflow-hidden rounded-xs object-cover" width="24" height="16" preserveAspectRatio="xMidYMid slice" role="img" aria-hidden="true" focusable="false">
            <rect width="90" height="20" y="0" fill="#AE1C28"/><rect width="90" height="20" y="20" fill="#fff"/><rect width="90" height="20" y="40" fill="#21468B"/>
        </svg>
    @else
        <svg viewBox="7.5 0 45 30" class="h-4 w-6 shrink-0 overflow-hidden rounded-xs object-cover" width="24" height="16" preserveAspectRatio="xMidYMid slice" role="img" aria-hidden="true" focusable="false">
            <rect width="60" height="30" fill="#012169"/>
            <path d="M0,0 60,30M60,0 0,30" stroke="#fff" stroke-width="6"/>
            <path d="M0,0 60,30M60,0 0,30" stroke="#C8102E" stroke-width="2.5"/>
            <path d="M30,0v30M0,15h60" stroke="#fff" stroke-width="10"/>
            <path d="M30,0v30M0,15h60" stroke="#C8102E" stroke-width="6"/>
        </svg>
    @endif
</a>
