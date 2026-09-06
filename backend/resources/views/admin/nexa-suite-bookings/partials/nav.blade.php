@php
    $nav = $nav ?? 'overview';
    $tabs = [
        ['key' => 'overview', 'label' => 'Overzicht', 'icon' => 'ki-filled ki-chart-line-up-2', 'route' => 'admin.nexa-suite-bookings.index'],
        ['key' => 'rides', 'label' => 'Ritten', 'icon' => 'ki-filled ki-route', 'route' => 'admin.nexa-suite-bookings.rides'],
        ['key' => 'invoices', 'label' => 'Facturen', 'icon' => 'ki-filled ki-bill', 'route' => 'admin.nexa-suite-bookings.invoices'],
        ['key' => 'settings', 'label' => 'Instellingen', 'icon' => 'ki-filled ki-setting-2', 'route' => 'admin.nexa-suite-bookings.settings'],
    ];
@endphp
<nav class="kt-tabs kt-tabs-line admin-page-tabs mb-5 min-w-0" aria-label="NEXA Suite boekingen">
    @foreach($tabs as $tab)
        @php $isActive = $nav === $tab['key']; @endphp
        <a href="{{ route($tab['route']) }}"
           class="kt-tab-toggle {{ $isActive ? 'active' : '' }}"
           @if($isActive) aria-current="page" @endif>
            <i class="{{ $tab['icon'] }}" aria-hidden="true"></i>
            <span class="kt-tab-title">{{ $tab['label'] }}</span>
        </a>
    @endforeach
</nav>
