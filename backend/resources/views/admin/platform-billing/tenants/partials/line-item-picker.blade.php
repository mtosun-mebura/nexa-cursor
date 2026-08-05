@php
    $selectedLineItemIds = collect($selectedLineItemIds ?? [])
        ->map(fn ($id) => (int) $id)
        ->all();
@endphp

<div class="platform-billing-line-item-picker" id="platform_billing_line_item_ids">
    @forelse($catalogLineItems as $catalogItem)
        <label class="platform-billing-line-item-picker__option">
            <input type="checkbox"
                   class="platform-billing-line-item-picker__input"
                   name="platform_billing_line_item_ids[]"
                   value="{{ $catalogItem->id }}"
                   @checked(in_array($catalogItem->id, $selectedLineItemIds, true))>
            <span class="platform-billing-line-item-picker__indicator" aria-hidden="true">
                <i class="ki-filled ki-check"></i>
            </span>
            <span class="platform-billing-line-item-picker__body">
                <span class="platform-billing-line-item-picker__title">{{ $catalogItem->name }}</span>
                @if($catalogItem->description)
                    <span class="platform-billing-line-item-picker__description">{{ $catalogItem->description }}</span>
                @endif
            </span>
            <span class="platform-billing-line-item-picker__price tabular-nums">
                € {{ number_format((float) $catalogItem->unit_price, 2, ',', '.') }}
            </span>
        </label>
    @empty
        <div class="platform-billing-line-item-picker__empty">
            Geen actieve factuurregels beschikbaar.
        </div>
    @endforelse
</div>
