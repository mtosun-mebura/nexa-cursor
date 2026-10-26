@php
    use App\Modules\NexaTaxi\Support\TaxiDispatchAccess;

    TaxiDispatchAccess::ensurePermissions();
    $dispatchSections = TaxiDispatchAccess::sections();
    $dispatchChecked = old('permissions', isset($role) ? $role->permissions->pluck('name')->all() : []);
    $dispatchEditable = $dispatchEditable ?? true;
    $dispatchAssigned = array_values(array_filter(
        $dispatchSections,
        fn (array $section) => in_array($section['permission'], $dispatchChecked, true)
    ));
@endphp
<div class="kt-card w-full min-w-0 mt-5">
    <div class="kt-card-header px-5 py-5">
        <h3 class="kt-card-title mb-0">Chauffeur dispatch</h3>
    </div>
    <div class="kt-card-content p-5">
        @if($dispatchEditable)
            <p class="text-sm text-muted-foreground mb-4">
                Kies welk onderdeel deze rol mag zien.
            </p>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach($dispatchSections as $section)
                    <label class="kt-label flex items-start gap-2 mb-0">
                        <input type="checkbox"
                               class="kt-checkbox mt-0.5"
                               name="permissions[]"
                               data-checkbox-group="permissions[]"
                               value="{{ $section['permission'] }}"
                               {{ in_array($section['permission'], $dispatchChecked, true) ? 'checked' : '' }}>
                        <span class="text-sm text-foreground">{{ $section['label'] }}</span>
                    </label>
                @endforeach
            </div>
        @elseif($dispatchAssigned === [])
            <p class="text-sm text-muted-foreground mb-0">Geen chauffeur-dispatchrechten toegewezen.</p>
        @else
            <ul class="grid gap-2 sm:grid-cols-2 list-none m-0 p-0">
                @foreach($dispatchAssigned as $section)
                    <li class="text-sm text-foreground">{{ $section['label'] }}</li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
