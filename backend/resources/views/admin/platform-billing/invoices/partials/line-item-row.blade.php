@php
    $qty = $item['quantity'] ?? 1;
    $price = $item['unit_price'] ?? 0;
    $catalogId = (string) ($item['platform_billing_line_item_id'] ?? '');
    $catalogLineItems = $catalogLineItems ?? collect();
@endphp
<tr>
    <td>
        <input type="hidden" name="line_items[{{ $index }}][type]" value="{{ $item['type'] ?? 'extra' }}" data-line-type>
        @if(!empty($item['billing_period']))
            <input type="hidden" name="line_items[{{ $index }}][billing_period]" value="{{ $item['billing_period'] }}">
        @endif
        <input type="hidden" name="line_items[{{ $index }}][platform_billing_line_item_id]" value="{{ $catalogId }}" data-line-catalog-id>
        <div class="flex flex-col gap-2 min-w-56">
            @if($catalogLineItems->isNotEmpty())
                <select class="kt-select w-full" data-line-catalog aria-label="Bestaande factuurregel">
                    <option value="">Zelf invullen</option>
                    @foreach($catalogLineItems as $catalogItem)
                        <option value="{{ $catalogItem->id }}" @selected($catalogId === (string) $catalogItem->id)>
                            {{ $catalogItem->name }} (€ {{ number_format((float) $catalogItem->unit_price, 2, ',', '.') }})
                        </option>
                    @endforeach
                </select>
            @endif
            <input class="kt-input w-full @error('line_items.'.$index.'.description') border-destructive @enderror"
                   type="text"
                   name="line_items[{{ $index }}][description]"
                   value="{{ $item['description'] ?? '' }}"
                   placeholder="Omschrijving"
                   data-line-description
                   required>
        </div>
    </td>
    <td class="text-right">
        <input class="kt-input tabular-nums ms-auto @error('line_items.'.$index.'.quantity') border-destructive @enderror"
               type="number"
               step="0.01"
               min="0.01"
               name="line_items[{{ $index }}][quantity]"
               value="{{ number_format((float) $qty, 2, '.', '') }}"
               data-line-qty
               style="width: 6.5rem;"
               required>
    </td>
    <td class="text-right">
        <div class="inline-flex items-stretch justify-end">
            <span class="inline-flex items-center shrink-0 px-2.5 border border-input border-r-0 rounded-l-md bg-muted/50 text-sm font-medium text-foreground">€</span>
            <input class="kt-input rounded-l-none tabular-nums @error('line_items.'.$index.'.unit_price') border-destructive @enderror"
                   type="number"
                   step="0.01"
                   name="line_items[{{ $index }}][unit_price]"
                   value="{{ number_format((float) $price, 2, '.', '') }}"
                   data-line-price
                   style="width: 7.5rem; max-width: 7.5rem;"
                   required>
        </div>
    </td>
    <td class="text-right tabular-nums whitespace-nowrap" data-line-total>€ 0,00</td>
    <td class="admin-table__actions-col text-center">
        <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost kt-btn-destructive" data-line-remove aria-label="Regel verwijderen" title="Verwijderen">
            <i class="ki-filled ki-trash"></i>
        </button>
    </td>
</tr>
