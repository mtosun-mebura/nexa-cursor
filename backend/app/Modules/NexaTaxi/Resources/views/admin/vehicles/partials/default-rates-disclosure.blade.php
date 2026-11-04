@php
    $ratesByRange = $defaultRatesByPersonRange ?? [];
    $selectedRange = (string) old(
        'person_range',
        isset($vehicle) ? ($vehicle->person_range ?? '1-4') : '1-4'
    );
    if ($selectedRange === '' || ! array_key_exists($selectedRange, $ratesByRange)) {
        $selectedRange = (string) (array_key_first($ratesByRange) ?? '1-4');
    }
    $currentRates = $ratesByRange[$selectedRange] ?? null;
@endphp
<details
    class="vehicle-default-rates-disclosure rounded-md border border-border bg-muted/20 p-3 text-sm"
    data-vehicle-default-rates-disclosure
    data-rates='@json($ratesByRange)'
>
    <summary class="vehicle-default-rates-disclosure__summary flex cursor-pointer items-center gap-2 font-medium text-mono select-none">
        <span class="flex-1 min-w-0">{{ __('Bekijk standaardtarieven') }}</span>
        <i class="ki-filled ki-down vehicle-default-rates-disclosure__chevron text-sm text-secondary-foreground shrink-0" aria-hidden="true"></i>
    </summary>
    <div class="mt-3 pt-3 border-t border-border">
        <p class="text-xs text-muted-foreground mb-2" data-default-rates-range-label>
            @if($currentRates)
                {{ __('Personenbereik') }}: {{ $currentRates['label'] }}
            @endif
        </p>
        @php $ratesAvailable = (bool) data_get($currentRates, 'available', false); @endphp
        <div data-default-rates-empty class="text-xs text-muted-foreground{{ $ratesAvailable ? ' hidden' : '' }}">
            {{ __('Geen standaardtarieven geconfigureerd.') }}
        </div>
        <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground w-full{{ $ratesAvailable ? '' : ' hidden' }}" data-default-rates-table>
            <tr>
                <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Instaptarief') }}</td>
                <td class="min-w-48 w-full text-foreground" data-default-rate="base_fare">{{ data_get($currentRates, 'base_fare', '—') }}</td>
            </tr>
            <tr>
                <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Wachttarief vooraf p/u') }}</td>
                <td class="min-w-48 w-full text-foreground" data-default-rate="min_fare">{{ data_get($currentRates, 'min_fare', '—') }}</td>
            </tr>
            <tr>
                <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Prijs per km (€)') }}</td>
                <td class="min-w-48 w-full text-foreground" data-default-rate="price_per_km">{{ data_get($currentRates, 'price_per_km', '—') }}</td>
            </tr>
            <tr>
                <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Prijs per min (€)') }}</td>
                <td class="min-w-48 w-full text-foreground" data-default-rate="price_per_min">{{ data_get($currentRates, 'price_per_min', '—') }}</td>
            </tr>
            <tr>
                <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Reinigingskosten') }}</td>
                <td class="min-w-48 w-full text-foreground" data-default-rate="cleaning_costs">{{ data_get($currentRates, 'cleaning_costs', '—') }}</td>
            </tr>
        </table>
    </div>
</details>

@once
@push('styles')
<style>
    .vehicle-default-rates-disclosure > summary {
        list-style: none;
    }
    .vehicle-default-rates-disclosure > summary::-webkit-details-marker {
        display: none;
    }
    .vehicle-default-rates-disclosure__chevron {
        transition: transform 0.2s ease, color 0.15s ease;
    }
    .vehicle-default-rates-disclosure[open] .vehicle-default-rates-disclosure__chevron {
        transform: rotate(180deg);
    }
    .vehicle-default-rates-disclosure__summary:hover .vehicle-default-rates-disclosure__chevron {
        color: var(--primary);
    }
</style>
@endpush
@push('scripts')
<script>
(function () {
    function applyRates(root, rates) {
        if (!root || !rates) return;
        var labelEl = root.querySelector('[data-default-rates-range-label]');
        var emptyEl = root.querySelector('[data-default-rates-empty]');
        var tableEl = root.querySelector('[data-default-rates-table]');
        if (labelEl) {
            labelEl.textContent = rates.label
                ? (@json(__('Personenbereik')) + ': ' + rates.label)
                : '';
        }
        var available = !!rates.available;
        if (emptyEl) emptyEl.classList.toggle('hidden', available);
        if (tableEl) tableEl.classList.toggle('hidden', !available);
        if (!available) return;
        ['base_fare', 'min_fare', 'price_per_km', 'price_per_min', 'cleaning_costs'].forEach(function (key) {
            var cell = root.querySelector('[data-default-rate="' + key + '"]');
            if (cell) cell.textContent = rates[key] != null ? rates[key] : '—';
        });
    }

    function bind() {
        var root = document.querySelector('[data-vehicle-default-rates-disclosure]');
        if (!root) return;
        var map = {};
        try {
            map = JSON.parse(root.getAttribute('data-rates') || '{}') || {};
        } catch (e) {
            map = {};
        }
        var select = document.querySelector('select[name="person_range"]');
        function sync() {
            var range = select ? String(select.value || '') : '';
            var rates = map[range] || null;
            if (!rates) {
                var keys = Object.keys(map);
                rates = keys.length ? map[keys[0]] : { available: false, label: '' };
            }
            applyRates(root, rates);
        }
        if (select) {
            select.addEventListener('change', sync);
        }
        sync();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bind);
    } else {
        bind();
    }
})();
</script>
@endpush
@endonce
