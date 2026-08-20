@php
    $summaryVariant = $summaryVariant ?? 'map';
@endphp
<div
    class="booking-vehicle-summary booking-vehicle-summary--{{ $summaryVariant }} hidden"
    data-booking-vehicle-summary
    data-summary-variant="{{ $summaryVariant }}"
    hidden
    aria-live="polite"
>
    <div class="booking-vehicle-summary__media" data-floating-summary-image-wrap hidden>
        <img src="" alt="" data-floating-summary-image>
    </div>
    <div class="booking-vehicle-summary__body">
        <div class="booking-vehicle-summary__title-wrap" data-floating-summary-title-wrap hidden>
            <div class="booking-vehicle-summary__title" data-floating-summary-title></div>
            <div class="booking-vehicle-summary__note" data-floating-summary-note hidden></div>
        </div>
        <div class="booking-vehicle-summary__meta">
            <span class="booking-vehicle-summary__chip" title="Personen" data-floating-summary-passengers-wrap hidden>
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="3" stroke="currentColor" stroke-width="2"/></svg>
                <span data-floating-summary-passengers>—</span>
            </span>
            <span class="booking-vehicle-summary__chip" title="Bagage" data-floating-summary-luggage-wrap hidden>
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M9 7V6a3 3 0 0 1 6 0v1m-9 0h12a1 1 0 0 1 1 1v10a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 1-1Z"/></svg>
                <span data-floating-summary-luggage>—</span>
            </span>
        </div>
        <div class="booking-vehicle-summary__price-block" data-floating-summary-price-wrap hidden>
            <div class="booking-vehicle-summary__price-label">Geschatte prijs</div>
            <div class="booking-vehicle-summary__price" data-floating-summary-price>—</div>
        </div>
    </div>
</div>
