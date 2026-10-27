@php
    $returnRouteTemplate = $returnRouteTemplate ?? null;
    $returnRouteStops = $returnRouteStops ?? collect();
    $hasOutboundStops = $routeTemplate && ($routePickupStops ?? collect())->isNotEmpty();
    $hasReturnStops = $returnRouteTemplate && $returnRouteStops->isNotEmpty();
    $hasReturnOption = $hasReturnStops || ($group->has_return_trip ?? false);
    $showDirectionTabs = $hasOutboundStops || $routeTemplate || $hasReturnOption;

    $routeLeg = request('leg', 'heen');
    if (! in_array($routeLeg, ['heen', 'terug'], true)) {
        $routeLeg = 'heen';
    }
    if ($routeLeg === 'terug' && ! $hasReturnOption) {
        $routeLeg = 'heen';
    }

    $heenUrl = request()->fullUrlWithQuery(['tab' => 'route', 'leg' => 'heen']);
    $terugUrl = request()->fullUrlWithQuery(['tab' => 'route', 'leg' => 'terug']);
@endphp

@if($showDirectionTabs)
    @if($hasReturnOption)
        <div class="route-leg-tabs px-3 sm:px-5 pt-4 pb-0" role="tablist" aria-label="{{ __('Heen- of terugweg') }}">
            <a href="{{ $heenUrl }}"
               class="route-leg-tab{{ $routeLeg === 'heen' ? ' is-active' : '' }}"
               data-leg="heen"
               role="tab"
               @if($routeLeg === 'heen') aria-current="page" @endif>
                <span class="route-leg-tab__label">{{ __('Heenweg') }}</span>
                <span class="route-leg-tab__hint">{{ __('Huis → school') }}</span>
            </a>
            <a href="{{ $terugUrl }}"
               class="route-leg-tab{{ $routeLeg === 'terug' ? ' is-active' : '' }}"
               data-leg="terug"
               role="tab"
               @if($routeLeg === 'terug') aria-current="page" @endif>
                <span class="route-leg-tab__label">{{ __('Terugweg') }}</span>
                <span class="route-leg-tab__hint">
                    {{ __('School → huis') }}
                    @if($group->return_pickup_time)
                        · {{ substr((string) $group->return_pickup_time, 0, 5) }}
                    @endif
                </span>
            </a>
        </div>
    @endif

    @if($routeLeg === 'heen')
        @if($hasOutboundStops)
            @if(! $hasReturnOption)
                <div class="px-3 sm:px-5 pt-4 pb-1">
                    <h4 class="text-sm font-semibold text-foreground mb-0">{{ __('Heenweg') }}</h4>
                    <p class="text-xs text-muted-foreground mb-0 mt-0.5">{{ __('Huis → school') }}</p>
                </div>
            @endif
            @include('taxi::admin.transport_groups.partials.route-summary')
        @elseif($routeTemplate)
            <div class="px-3 sm:px-5 py-5 text-sm text-muted-foreground">
                {{ __('Route-instellingen staan klaar, maar er zijn nog geen stops berekend.') }}
                @can('rides.update')
                {!! __('Open de routeplanner en druk op <strong>Route berekenen</strong>.') !!}
                @endcan
            </div>
        @else
            <div class="px-3 sm:px-5 py-5 text-sm text-muted-foreground">
                {{ __('Nog geen heenweg gepland.') }}
                @can('rides.update')
                {{ __('Open de routeplanner om de route te berekenen.') }}
                @endcan
            </div>
        @endif
    @else
        @if($hasReturnStops)
            @include('taxi::admin.transport_groups.partials.return-route-summary')
        @else
            <div class="px-3 sm:px-5 py-5 text-sm text-muted-foreground">
                {{ __('Terugweg staat aan, maar er is nog geen retourroute berekend.') }}
                @can('rides.update')
                {{ __('Sla de groep opnieuw op of voeg passagiers toe om de terugweg te berekenen.') }}
                @endcan
            </div>
        @endif
    @endif
@else
    <div class="px-3 sm:px-5 pb-5 text-sm text-muted-foreground">
        {{ __('Nog geen route gepland.') }}
        @can('rides.update')
        {{ __('Open de routeplanner om weekdagen, stopvolgorde, tijden en vaste chauffeur in te stellen.') }}
        @endcan
    </div>
@endif
