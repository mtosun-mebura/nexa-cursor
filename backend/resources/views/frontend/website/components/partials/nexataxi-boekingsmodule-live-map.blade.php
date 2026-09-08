<aside class="booking-module-v2-map-col" data-booking-live-map-panel aria-label="{{ !empty($bookingLiveFleetShowsStatus) ? 'Live taxi’s en routekaart' : (!empty($bookingMarketplaceFleet) ? 'Live taxi’s en routekaart' : 'Routekaart') }}">
    <div class="booking-module-v2-map-inner">
        <div class="booking-module-v2-map-canvas" data-booking-live-map role="presentation"></div>
        <div class="booking-module-v2-map-empty absolute inset-0 z-10 flex items-center justify-center p-4 pointer-events-none" data-booking-live-map-empty>
            <span class="booking-module-v2-map-empty-msg">{{ !empty($bookingLiveFleetShowsStatus) ? 'Taxi’s van dit bedrijf verschijnen hier live (vrij of bezet).' : (!empty($bookingMarketplaceFleet) ? 'Beschikbare taxi’s in de buurt verschijnen hier live.' : 'Kies een ophaal- of bestemmingsadres om de kaart te vullen.') }}</span>
        </div>
        @if(!empty($bookingLiveFleetShowsStatus))
        <div class="booking-module-v2-map-legend-bar">
            <div class="booking-module-v2-map-legend" data-booking-live-taxi-legend>
                Live taxi’s · <span class="booking-live-legend-free">Vrij</span> · <span class="booking-live-legend-busy">Bezet</span>
            </div>
            <button type="button" class="booking-live-show-all-btn" data-booking-live-show-all aria-label="Toon alle auto’s">
                Alle auto’s
            </button>
        </div>
        @elseif(!empty($bookingMarketplaceFleet))
        <div class="booking-module-v2-map-legend-bar">
            <div class="booking-module-v2-map-legend" data-booking-live-taxi-legend>
                Live beschikbare taxi’s
            </div>
            <button type="button" class="booking-live-show-all-btn" data-booking-live-show-all aria-label="Toon alle auto’s">
                Alle auto’s
            </button>
        </div>
        @endif
        @include('frontend.website.components.partials.nexataxi-boekingsmodule-vehicle-summary', ['summaryVariant' => 'map'])
    </div>
</aside>
