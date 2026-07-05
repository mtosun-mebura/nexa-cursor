@php
    $selectedPassengerIds = collect(old('transport_passenger_id', []))->map(fn ($id) => (int) $id)->all();
@endphp
<div class="transport-group-passenger-picker" id="transport-group-passenger-picker">
    @if($availablePassengers->isNotEmpty())
        <label class="kt-input w-full mb-3" for="transport-group-passenger-search">
            <i class="ki-filled ki-magnifier" aria-hidden="true"></i>
            <input type="search"
                   id="transport-group-passenger-search"
                   placeholder="Zoek op naam of adres…"
                   autocomplete="off"
                   data-transport-group-passenger-search
                   aria-label="Zoek passagier">
        </label>
        <div class="transport-group-passenger-picker__list" role="listbox" aria-multiselectable="true">
            @foreach($availablePassengers as $passenger)
                <label class="transport-group-passenger-picker__item"
                       role="option"
                       data-passenger-picker-item
                       data-search-text="{{ strtolower($passenger->full_name.' '.$passenger->pickup_address) }}">
                    <input type="checkbox"
                           class="kt-checkbox shrink-0"
                           name="transport_passenger_id[]"
                           value="{{ $passenger->id }}"
                           @checked(in_array($passenger->id, $selectedPassengerIds, true))>
                    <span class="transport-group-passenger-picker__item-body min-w-0">
                        <span class="transport-group-passenger-picker__name">{{ $passenger->full_name }}</span>
                        <span class="transport-group-passenger-picker__address">{{ $passenger->pickup_address }}</span>
                    </span>
                </label>
            @endforeach
        </div>
        <p class="text-xs text-muted-foreground mt-2 mb-0" data-transport-group-passenger-count>
            {{ $availablePassengers->count() }} beschikbare passagier{{ $availablePassengers->count() === 1 ? '' : 's' }} · selecteer één of meerdere
        </p>
        <p class="text-xs text-muted-foreground mt-2 mb-0 hidden" data-transport-group-passenger-empty>
            Geen passagiers gevonden voor je zoekopdracht.
        </p>
    @else
        <div class="transport-group-passenger-picker__empty text-sm text-muted-foreground text-center py-8 px-4 rounded-lg border border-dashed border-input">
            Alle actieve passagiers zitten al in deze groep.
        </div>
    @endif
</div>
