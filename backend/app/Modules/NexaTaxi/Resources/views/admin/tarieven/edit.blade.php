@extends('admin.layouts.app')

@section('title', 'Tarieven')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <div class="min-w-0">
            <h1 class="text-xl font-medium leading-none text-mono">
                Tarieven
                @if(!empty($scopeCompanyId))
                    <span class="text-muted-foreground font-normal">· {{ $scopeCompanyName ?: 'Tenant' }}</span>
                @else
                    <span class="text-muted-foreground font-normal">· NEXA Suite</span>
                @endif
            </h1>
            <p class="text-sm text-muted-foreground mt-2 mb-0 leading-relaxed">
                @if(!empty($scopeCompanyId))
                    Standaardtarieven van <span class="text-foreground font-medium">{{ $scopeCompanyName ?: 'deze tenant' }}</span>.
                    Gelden op de eigen website van dit taxibedrijf, wanneer een voertuig geen eigen tarieven heeft.
                @else
                    Standaardtarieven van de <span class="text-foreground font-medium">NEXA Suite-website</span> (Alle tenants).
                    Deze prijzen gelden voor de tarievenpagina en boekingen op de algemene website. Een marketplace-rit die naar het dichtstbijzijnde taxibedrijf gaat, houdt deze geoffreerde prijs aan — niet de eigen tarieven van die tenant.
                @endif
                De avond/nacht-toeslag geldt voor alle personenbereiken.
            </p>
        </div>
    </div>

    <form action="{{ route('admin.taxi.tarieven.update') }}" method="POST">
        @csrf
        @method('PUT')

        @php
            $ratesRows = old('rates');
            if (!is_array($ratesRows)) {
                $ratesRows = $rates->map(function ($rate) {
                    return [
                        'person_range' => $rate->person_range,
                        'base_fare' => $rate->base_fare,
                        'min_fare' => $rate->min_fare,
                        'price_per_km' => $rate->price_per_km,
                        'price_per_min' => $rate->price_per_min,
                        'cleaning_costs' => $rate->cleaning_costs,
                    ];
                })->values()->all();
            }
        @endphp

        <div class="grid gap-5 lg:gap-7.5" id="rates-editor" data-next-index="{{ count($ratesRows) }}">
            @if($errors->has('rates'))
                <div class="kt-alert kt-alert-danger">{{ $errors->first('rates') }}</div>
            @endif

            @php
                $eveningNightMultiplier = old('evening_night_multiplier', $eveningNight['multiplier'] ?? \App\Modules\NexaTaxi\Models\DefaultRate::DEFAULT_EVENING_NIGHT_MULTIPLIER);
                $eveningNightFromHour = old('evening_night_from_hour', $eveningNight['from_hour'] ?? \App\Modules\NexaTaxi\Models\DefaultRate::DEFAULT_EVENING_NIGHT_FROM_HOUR);
                $eveningNightUntilHour = old('evening_night_until_hour', $eveningNight['until_hour'] ?? \App\Modules\NexaTaxi\Models\DefaultRate::DEFAULT_EVENING_NIGHT_UNTIL_HOUR);
            @endphp

            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
                    <h3 class="kt-card-title mb-0">Avond/nacht toeslag</h3>
                </div>
                <div class="kt-card-content p-0">
                    <div class="px-3 sm:px-5 pb-3 min-w-0">
                        <p class="text-sm text-muted-foreground mb-3">
                            Wordt toegepast op kilometer-, minuut- en wachttarief bij ritten in dit tijdvenster, als in de boekingsmodule het vinkje Avond/nacht tarief aanstaat.
                        </p>
                        <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">Toeslagfactor</td>
                                <td class="min-w-48 w-full">
                                    <input type="number" name="evening_night_multiplier" class="kt-input admin-field-fit @error('evening_night_multiplier') border-destructive @enderror"
                                           value="{{ $eveningNightMultiplier }}" step="0.01" min="1" max="5" required>
                                    <div class="text-xs text-muted-foreground mt-1">Bijvoorbeeld 1,20 voor 20% extra. 1,00 betekent geen toeslag.</div>
                                    @error('evening_night_multiplier')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">Van</td>
                                <td class="min-w-48 w-full">
                                    <select name="evening_night_from_hour" class="kt-select admin-field-fit @error('evening_night_from_hour') border-destructive @enderror" data-kt-select="true">
                                        @for($h = 0; $h < 24; $h++)
                                            <option value="{{ $h }}" {{ (int) $eveningNightFromHour === $h ? 'selected' : '' }}>{{ sprintf('%02d:00', $h) }}</option>
                                        @endfor
                                    </select>
                                    @error('evening_night_from_hour')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">Tot</td>
                                <td class="min-w-48 w-full">
                                    <select name="evening_night_until_hour" class="kt-select admin-field-fit @error('evening_night_until_hour') border-destructive @enderror" data-kt-select="true">
                                        @for($h = 0; $h < 24; $h++)
                                            <option value="{{ $h }}" {{ (int) $eveningNightUntilHour === $h ? 'selected' : '' }}>{{ sprintf('%02d:00', $h) }}</option>
                                        @endfor
                                    </select>
                                    <div class="text-xs text-muted-foreground mt-1">Als tot lager is dan van, loopt het venster over middernacht (bijvoorbeeld 22:00–06:00).</div>
                                    @error('evening_night_until_hour')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end w-full min-w-0">
                <button type="button" class="kt-btn kt-btn-primary shrink-0" data-add-range>
                    <i class="ki-filled ki-plus me-2"></i> Personenbereik toevoegen
                </button>
            </div>

            <div class="grid gap-5 lg:gap-7.5" data-rates-list>
                @foreach($ratesRows as $i => $row)
                    <div class="kt-card w-full min-w-0" data-rate-row>
                        <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
                            <h3 class="kt-card-title mb-0">Standaardtarieven</h3>
                            <button type="button" class="kt-btn kt-btn-icon kt-btn-outline text-danger rates-remove-btn" data-remove-range title="Personenbereik verwijderen" aria-label="Personenbereik verwijderen">
                                <svg class="w-5 h-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                            </button>
                        </div>
                        <div class="kt-card-content p-0">
                            <div class="px-3 sm:px-5 pb-3 min-w-0">
                            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                                <tr>
                                    <td class="min-w-56 text-secondary-foreground font-normal">Personenbereik *</td>
                                    <td class="min-w-48 w-full">
                                        <input type="text" name="rates[{{ $i }}][person_range]" class="kt-input admin-field-fit @error('rates.'.$i.'.person_range') border-destructive @enderror"
                                               value="{{ old('rates.'.$i.'.person_range', $row['person_range'] ?? '') }}" placeholder="bijv. 1-4" required>
                                        <div class="text-xs text-muted-foreground mt-1">Gebruik formaat `van-tot`, bijvoorbeeld `1-4`, `5-8`, `9-12`.</div>
                                        @error('rates.'.$i.'.person_range')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                                    </td>
                                </tr>
                                <tr>
                                    <td class="min-w-56 text-secondary-foreground font-normal">Instaptarief</td>
                                    <td class="min-w-48 w-full">
                                        <div class="admin-euro-input">
                                            <span class="admin-euro-input__prefix" aria-hidden="true">€</span>
                                            <input type="number" name="rates[{{ $i }}][base_fare]" class="kt-input admin-field-fit @error('rates.'.$i.'.base_fare') border-destructive @enderror"
                                                   value="{{ ($v = old('rates.'.$i.'.base_fare', $row['base_fare'] ?? null)) !== null && $v !== '' && (float)$v != 0 ? $v : '' }}" step="0.01" min="0" placeholder="0,00">
                                        </div>
                                        @error('rates.'.$i.'.base_fare')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                                    </td>
                                </tr>
                                <tr>
                                    <td class="min-w-56 text-secondary-foreground font-normal">Wachttarief vooraf p/u</td>
                                    <td class="min-w-48 w-full">
                                        <div class="admin-euro-input">
                                            <span class="admin-euro-input__prefix" aria-hidden="true">€</span>
                                            <input type="number" name="rates[{{ $i }}][min_fare]" class="kt-input admin-field-fit @error('rates.'.$i.'.min_fare') border-destructive @enderror"
                                                   value="{{ ($v = old('rates.'.$i.'.min_fare', $row['min_fare'] ?? null)) !== null && $v !== '' && (float)$v != 0 ? $v : '' }}" step="0.01" min="0" placeholder="0,00">
                                        </div>
                                        @error('rates.'.$i.'.min_fare')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                                    </td>
                                </tr>
                                <tr>
                                    <td class="min-w-56 text-secondary-foreground font-normal">Prijs per km</td>
                                    <td class="min-w-48 w-full">
                                        <div class="admin-euro-input">
                                            <span class="admin-euro-input__prefix" aria-hidden="true">€</span>
                                            <input type="number" name="rates[{{ $i }}][price_per_km]" class="kt-input admin-field-fit @error('rates.'.$i.'.price_per_km') border-destructive @enderror"
                                                   value="{{ ($v = old('rates.'.$i.'.price_per_km', $row['price_per_km'] ?? null)) !== null && $v !== '' && (float)$v != 0 ? $v : '' }}" step="0.01" min="0" placeholder="0,00">
                                        </div>
                                        @error('rates.'.$i.'.price_per_km')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                                    </td>
                                </tr>
                                <tr>
                                    <td class="min-w-56 text-secondary-foreground font-normal">Prijs per min</td>
                                    <td class="min-w-48 w-full">
                                        <div class="admin-euro-input">
                                            <span class="admin-euro-input__prefix" aria-hidden="true">€</span>
                                            <input type="number" name="rates[{{ $i }}][price_per_min]" class="kt-input admin-field-fit @error('rates.'.$i.'.price_per_min') border-destructive @enderror"
                                                   value="{{ ($v = old('rates.'.$i.'.price_per_min', $row['price_per_min'] ?? null)) !== null && $v !== '' && (float)$v != 0 ? $v : '' }}" step="0.01" min="0" placeholder="0,00">
                                        </div>
                                        @error('rates.'.$i.'.price_per_min')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                                    </td>
                                </tr>
                                <tr>
                                    <td class="min-w-56 text-secondary-foreground font-normal">Reinigingskosten</td>
                                    <td class="min-w-48 w-full">
                                        <div class="admin-euro-input">
                                            <span class="admin-euro-input__prefix" aria-hidden="true">€</span>
                                            <input type="number" name="rates[{{ $i }}][cleaning_costs]" class="kt-input admin-field-fit @error('rates.'.$i.'.cleaning_costs') border-destructive @enderror"
                                                   value="{{ ($v = old('rates.'.$i.'.cleaning_costs', $row['cleaning_costs'] ?? null)) !== null && $v !== '' && (float)$v != 0 ? $v : '' }}" step="0.01" min="0" placeholder="0,00">
                                        </div>
                                        @error('rates.'.$i.'.cleaning_costs')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                                    </td>
                                </tr>
                            </table>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Actions -->
            <div class="admin-form-actions flex flex-wrap items-center justify-end gap-2.5 w-full min-w-0">
                <a href="{{ route('admin.taxi.vehicles.index') }}" class="kt-btn kt-btn-outline">
                    <svg class="w-4 h-4 me-2 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    Annuleren
                </a>
                <button type="submit" class="kt-btn kt-btn-primary">
                    <svg class="w-4 h-4 me-2 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    Tarieven opslaan
                </button>
            </div>
        </div>
    </form>
</div>

@push('styles')
<style>
    /* Prullenbak-knop even hoog als input (kt-input is doorgaans 40px), geen rand */
    .rates-remove-btn {
        height: 40px;
        width: 40px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: none;
    }
    #rates-editor .kt-card,
    #rates-editor .kt-card-content {
        overflow: visible;
    }
    #rates-editor .kt-select-option,
    #rates-editor .kt-select-option-text,
    #rates-editor [data-kt-select-option],
    [data-kt-select-dropdown] .kt-select-option,
    [data-kt-select-dropdown] .kt-select-option-text,
    [data-kt-select-dropdown] [data-kt-select-option] {
        font-size: 0.8125rem !important;
        line-height: 1.35 !important;
        white-space: nowrap !important;
    }
    /* Compacte tariefvelden: 8 cijfers + decimalen, niet volle rij */
    #content form #rates-editor input.kt-input.admin-field-fit {
        width: calc(11ch + 2.75rem) !important;
        min-width: calc(11ch + 2.75rem) !important;
        max-width: 100% !important;
    }
    #rates-editor .admin-euro-input {
        display: inline-flex;
        align-items: stretch;
        width: max-content;
        max-width: 100%;
    }
    #rates-editor .admin-euro-input__prefix {
        display: inline-flex;
        align-items: center;
        flex-shrink: 0;
        padding: 0 0.625rem;
        border: 1px solid var(--input, var(--border));
        border-right: 0;
        border-radius: 0.375rem 0 0 0.375rem;
        background: color-mix(in srgb, var(--muted) 50%, transparent);
        font-size: 0.8125rem;
        font-weight: 500;
        color: var(--foreground);
        line-height: 1;
    }
    #rates-editor .admin-euro-input input.kt-input {
        border-top-left-radius: 0 !important;
        border-bottom-left-radius: 0 !important;
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var root = document.getElementById('rates-editor');
    if (!root) return;
    var list = root.querySelector('[data-rates-list]');
    var addBtn = root.querySelector('[data-add-range]');
    if (!list || !addBtn) return;

    function nextIndex() {
        var current = parseInt(root.getAttribute('data-next-index') || '0', 10);
        root.setAttribute('data-next-index', String(current + 1));
        return current;
    }

    function euroField(name) {
        return '<div class="admin-euro-input">' +
            '<span class="admin-euro-input__prefix" aria-hidden="true">€</span>' +
            '<input type="number" name="' + name + '" class="kt-input admin-field-fit" step="0.01" min="0" placeholder="0,00">' +
            '</div>';
    }

    function buildRow(index) {
        var wrapper = document.createElement('div');
        wrapper.className = 'kt-card w-full min-w-0';
        wrapper.setAttribute('data-rate-row', '1');
        var trashSvg = '<svg class="w-5 h-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>';
        wrapper.innerHTML =
            '<div class="kt-card-header flex items-center justify-between gap-3 px-5 py-5">' +
                '<h3 class="kt-card-title mb-0">Standaardtarieven</h3>' +
                '<button type="button" class="kt-btn kt-btn-icon kt-btn-outline text-danger rates-remove-btn" data-remove-range title="Personenbereik verwijderen" aria-label="Personenbereik verwijderen">' + trashSvg + '</button>' +
            '</div>' +
            '<div class="kt-card-content p-0"><div class="px-3 sm:px-5 pb-3 min-w-0">' +
                '<table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">' +
                    '<tr><td class="min-w-56 text-secondary-foreground font-normal">Personenbereik *</td><td class="min-w-48 w-full"><input type="text" name="rates[' + index + '][person_range]" class="kt-input admin-field-fit" placeholder="bijv. 9-12" required></td></tr>' +
                    '<tr><td class="min-w-56 text-secondary-foreground font-normal">Instaptarief</td><td class="min-w-48 w-full">' + euroField('rates[' + index + '][base_fare]') + '</td></tr>' +
                    '<tr><td class="min-w-56 text-secondary-foreground font-normal">Wachttarief vooraf p/u</td><td class="min-w-48 w-full">' + euroField('rates[' + index + '][min_fare]') + '</td></tr>' +
                    '<tr><td class="min-w-56 text-secondary-foreground font-normal">Prijs per km</td><td class="min-w-48 w-full">' + euroField('rates[' + index + '][price_per_km]') + '</td></tr>' +
                    '<tr><td class="min-w-56 text-secondary-foreground font-normal">Prijs per min</td><td class="min-w-48 w-full">' + euroField('rates[' + index + '][price_per_min]') + '</td></tr>' +
                    '<tr><td class="min-w-56 text-secondary-foreground font-normal">Reinigingskosten</td><td class="min-w-48 w-full">' + euroField('rates[' + index + '][cleaning_costs]') + '</td></tr>' +
                '</table></div></div>';
        return wrapper;
    }

    addBtn.addEventListener('click', function () {
        list.appendChild(buildRow(nextIndex()));
    });

    list.addEventListener('click', function (e) {
        var removeBtn = e.target.closest('[data-remove-range]');
        if (!removeBtn) return;
        var cards = list.querySelectorAll('[data-rate-row]');
        if (cards.length <= 1) {
            alert('Minimaal 1 personenbereik is verplicht.');
            return;
        }
        var row = removeBtn.closest('[data-rate-row]');
        if (row) row.remove();
    });
})();
</script>
@endpush
@endsection
