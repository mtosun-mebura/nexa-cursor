@if($routeTemplate && $routePickupStops->isNotEmpty())
    @include('taxi::admin.transport_groups.partials.route-summary')
@elseif($routeTemplate)
    <div class="px-3 sm:px-5 pb-5 text-sm text-muted-foreground">
        Route-instellingen staan klaar, maar er zijn nog geen stops berekend.
        @can('rides.update')
        Open de routeplanner en druk op <strong>Route berekenen</strong>.
        @endcan
    </div>
@else
    <div class="px-3 sm:px-5 pb-5 text-sm text-muted-foreground">
        Nog geen route gepland.
        @can('rides.update')
        Open de routeplanner om weekdagen, stopvolgorde, tijden en vaste chauffeur in te stellen.
        @endcan
    </div>
@endif
