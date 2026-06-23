@if(! $hasContractPassengers)
    <div class="px-6 py-8 text-sm text-muted-foreground space-y-3">
        <p class="m-0">Er zijn nog geen passagiers op dit abonnement.</p>
        <a href="{{ route('admin.taxi.transport_passengers.create', [$customer->id, $contract->id]) }}" class="kt-btn kt-btn-primary kt-btn-sm">
            Passagier aanmaken
        </a>
    </div>
@else
    <form method="POST"
          id="transport-group-add-members-form"
          action="{{ route('admin.taxi.transport_groups.member_store', [$customer->id, $contract->id, $group->id]) }}"
          class="px-6 py-5 space-y-4 flex flex-col flex-1 min-h-0 overflow-hidden">
        @csrf
        <div class="flex flex-col flex-1 min-h-0">
            <label class="text-sm text-secondary-foreground mb-2 block" for="transport-group-passenger-search">Passagiers</label>
            <div id="transport-group-passenger-picker-panel" class="flex-1 min-h-0">
                @include('taxi::admin.transport_groups.partials.add-members-passenger-picker', [
                    'availablePassengers' => $availablePassengers,
                ])
            </div>
            @error('transport_passenger_id')
                <div class="text-xs text-destructive mt-2">{{ $message }}</div>
            @enderror
        </div>
        <div class="shrink-0">
            <label class="text-sm text-secondary-foreground mb-1 block">Ingangsdatum</label>
            @include('taxi::admin.transport_customers.partials.date-picker-input', [
                'name' => 'valid_from',
                'value' => old('valid_from', now()->format('Y-m-d')),
                'wrapperClass' => 'w-44',
            ])
        </div>
        <p class="text-xs text-muted-foreground m-0 shrink-0">
            Na toevoegen wordt de route automatisch opnieuw berekend (indien er al een route is).
        </p>
        <div class="flex flex-wrap justify-end gap-2 pt-2 shrink-0">
            <button type="button" class="kt-btn kt-btn-outline" data-transport-group-add-members-close>Annuleren</button>
            <button type="submit"
                    class="kt-btn kt-btn-primary"
                    @disabled($availablePassengers->isEmpty())>
                Toevoegen
            </button>
        </div>
    </form>
@endif
