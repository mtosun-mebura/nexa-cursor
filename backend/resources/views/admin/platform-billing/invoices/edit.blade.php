@extends('admin.layouts.app')

@section('title', 'NEXA-factuur bewerken '.$invoice->invoice_number)

@php
    $lineItems = old('line_items', $invoice->line_items ?? []);
    if ($lineItems === []) {
        $lineItems = [['description' => '', 'quantity' => 1, 'unit_price' => 0, 'type' => 'extra']];
    }
    $hasMolliePayment = $invoice->mollieStatus() !== null;
    $catalogLineItems = $catalogLineItems ?? collect();
    $catalogPayload = $catalogLineItems->map(fn ($item) => [
        'id' => $item->id,
        'description' => $item->invoiceLineDescription(),
        'unit_price' => round((float) $item->unit_price, 2),
    ])->values();
@endphp

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                Factuur bewerken <span style="color: rgb(234 179 8);">|</span> <span style="color: rgb(59 130 246);">{{ $invoice->invoice_number }}</span>
            </h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ $invoice->company?->name }} · {{ $invoice->billing_period }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.platform-billing.invoices.show', $invoice) }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
    </div>

    @if($invoice->isPaid() || $hasMolliePayment)
        <div class="kt-alert kt-alert-warning mb-5" role="alert">
            <i class="ki-filled ki-information-2 me-2"></i>
            @if($invoice->isPaid() && $hasMolliePayment)
                Deze factuur is betaald en heeft een Mollie-betaling. Wijzigingen werken niet door naar Mollie.
            @elseif($invoice->isPaid())
                Deze factuur is als betaald gemarkeerd. Controleer of het totaal nog klopt na wijzigen.
            @else
                Er is een Mollie-betaling gekoppeld. Wijzigingen werken niet door naar Mollie.
            @endif
        </div>
    @endif

    <form method="POST" action="{{ route('admin.platform-billing.invoices.update', $invoice) }}" id="platform-invoice-edit-form">
        @csrf
        @method('PUT')

        <div class="grid gap-5 lg:gap-7.5">
            <x-error-card :errors="$errors" />

            <div class="kt-card min-w-full">
                <div class="kt-card-header px-5 py-5">
                    <h3 class="kt-card-title mb-0">Factuurgegevens</h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Tenant</td>
                            <td class="font-medium text-foreground">{{ $invoice->company?->name }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Status <span class="text-destructive">*</span></td>
                            <td>
                                <select class="kt-select w-full max-w-xs @error('status') border-destructive @enderror" name="status" required>
                                    @foreach(\App\Models\PlatformInvoice::STATUS_LABELS as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', $invoice->status) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Betaaltermijn <span class="text-destructive">*</span></td>
                            <td>
                                <input type="number" name="payment_terms_days" min="1" max="365" class="kt-input @error('payment_terms_days') border-destructive @enderror" style="width: 13ch;" value="{{ old('payment_terms_days', $invoice->payment_terms_days ?? \App\Models\PlatformBillingSetting::paymentTermsDaysForInvoice($invoice)) }}" required>
                                <span class="text-sm text-muted-foreground ms-2">dagen</span>
                                <div class="text-xs text-muted-foreground mt-1">De vervaldatum wordt herberekend vanaf de factuurdatum.</div>
                                @error('payment_terms_days')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Notities</td>
                            <td>
                                <textarea class="kt-input w-full @error('notes') border-destructive @enderror" name="notes" rows="3" placeholder="Optionele interne notitie…">{{ old('notes', $invoice->notes) }}</textarea>
                                @error('notes')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="kt-card min-w-full">
                <div class="kt-card-header px-5 py-5 flex flex-wrap items-center justify-between gap-3">
                    <h3 class="kt-card-title mb-0">Factuurregels</h3>
                    <div class="flex flex-wrap items-center gap-2">
                        @if($catalogLineItems->isNotEmpty())
                            <select class="kt-select w-full sm:w-72" data-add-catalog aria-label="Bestaande factuurregel toevoegen">
                                <option value="">Bestaande regel kiezen…</option>
                                @foreach($catalogLineItems as $catalogItem)
                                    <option value="{{ $catalogItem->id }}">
                                        {{ $catalogItem->name }} (€ {{ number_format((float) $catalogItem->unit_price, 2, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>
                        @endif
                        <button type="button" class="kt-btn kt-btn-sm kt-btn-outline" data-platform-invoice-add-line>
                            <i class="ki-filled ki-plus me-1"></i>
                            Lege regel
                        </button>
                    </div>
                </div>
                <div class="kt-card-content p-5">
                    @error('line_items')<div class="text-xs text-destructive mb-3">{{ $message }}</div>@enderror
                    <div class="kt-scrollable-x-auto min-w-0">
                        <table class="kt-table kt-table-border align-middle text-sm w-full" id="platform-invoice-lines">
                            <thead>
                                <tr>
                                    <th class="text-secondary-foreground font-normal text-left">Omschrijving</th>
                                    <th class="text-secondary-foreground font-normal text-right">Aantal</th>
                                    <th class="text-secondary-foreground font-normal text-right">Prijs excl. BTW</th>
                                    <th class="text-secondary-foreground font-normal text-right">Totaal excl. BTW</th>
                                    <th class="admin-table__actions-col text-secondary-foreground font-normal text-center">Acties</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($lineItems as $index => $item)
                                @include('admin.platform-billing.invoices.partials.line-item-row', ['index' => $index, 'item' => $item, 'catalogLineItems' => $catalogLineItems])
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="flex flex-col items-end gap-1 mt-4 text-sm">
                        <div class="text-muted-foreground">Subtotaal <span class="inline-block min-w-28 text-right tabular-nums text-foreground" data-invoice-subtotal>€ 0,00</span></div>
                        <div class="text-muted-foreground">BTW ({{ rtrim(rtrim(number_format((float) $taxRate, 2, ',', '.'), '0'), ',') }}%) <span class="inline-block min-w-28 text-right tabular-nums text-foreground" data-invoice-tax>€ 0,00</span></div>
                        <div class="font-medium text-foreground">Totaal <span class="inline-block min-w-28 text-right tabular-nums" data-invoice-total>€ 0,00</span></div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5">
                <a href="{{ route('admin.platform-billing.invoices.show', $invoice) }}" class="kt-btn kt-btn-outline">
                    <i class="ki-filled ki-cross me-2"></i>
                    Annuleren
                </a>
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-check me-2"></i>
                    Opslaan
                </button>
            </div>
        </div>
    </form>
</div>

<template id="platform-invoice-line-template">
    @include('admin.platform-billing.invoices.partials.line-item-row', ['index' => '__INDEX__', 'item' => ['description' => '', 'quantity' => 1, 'unit_price' => 0, 'type' => 'extra'], 'catalogLineItems' => $catalogLineItems])
</template>
@endsection

@push('scripts')
<script>
(function () {
    const table = document.getElementById('platform-invoice-lines');
    const template = document.getElementById('platform-invoice-line-template');
    const addBtn = document.querySelector('[data-platform-invoice-add-line]');
    const addCatalog = document.querySelector('[data-add-catalog]');
    const taxRate = {{ json_encode((float) $taxRate) }};
    const catalog = @json($catalogPayload);
    let nextIndex = {{ count($lineItems) }};

    function catalogById(id) {
        const key = String(id);
        return catalog.find(function (item) { return String(item.id) === key; }) || null;
    }

    function formatMoney(amount) {
        const n = Number.isFinite(amount) ? amount : 0;
        return '€ ' + n.toLocaleString('nl-NL', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function lineTotal(row) {
        const qty = parseFloat(row.querySelector('[data-line-qty]')?.value) || 0;
        const price = parseFloat(row.querySelector('[data-line-price]')?.value) || 0;
        return Math.round(qty * price * 100) / 100;
    }

    function recalc() {
        let subtotal = 0;
        table.querySelectorAll('tbody tr').forEach(function (row) {
            const total = lineTotal(row);
            subtotal += total;
            const cell = row.querySelector('[data-line-total]');
            if (cell) cell.textContent = formatMoney(total);
        });
        subtotal = Math.round(subtotal * 100) / 100;
        const tax = Math.round(subtotal * (taxRate / 100) * 100) / 100;
        const total = Math.round((subtotal + tax) * 100) / 100;
        const subEl = document.querySelector('[data-invoice-subtotal]');
        const taxEl = document.querySelector('[data-invoice-tax]');
        const totEl = document.querySelector('[data-invoice-total]');
        if (subEl) subEl.textContent = formatMoney(subtotal);
        if (taxEl) taxEl.textContent = formatMoney(tax);
        if (totEl) totEl.textContent = formatMoney(total);
    }

    function applyCatalog(row, id) {
        const hiddenId = row.querySelector('[data-line-catalog-id]');
        const typeInput = row.querySelector('[data-line-type]');
        const description = row.querySelector('[data-line-description]');
        const price = row.querySelector('[data-line-price]');
        const qty = row.querySelector('[data-line-qty]');
        const select = row.querySelector('[data-line-catalog]');
        const item = catalogById(id);

        if (!item) {
            if (hiddenId) hiddenId.value = '';
            if (select) select.value = '';
            recalc();
            return;
        }

        if (hiddenId) hiddenId.value = String(item.id);
        if (select) select.value = String(item.id);
        if (typeInput) typeInput.value = 'extra';
        if (description) description.value = item.description;
        if (price) price.value = Number(item.unit_price).toFixed(2);
        if (qty && !qty.value) qty.value = '1.00';
        recalc();
    }

    function addRow(catalogId) {
        const html = template.innerHTML.replaceAll('__INDEX__', String(nextIndex));
        nextIndex += 1;
        table.querySelector('tbody').insertAdjacentHTML('beforeend', html);
        const row = table.querySelector('tbody tr:last-child');
        bindRow(row);
        if (catalogId) {
            applyCatalog(row, catalogId);
        } else {
            recalc();
        }
        row.querySelector('[data-line-description]')?.focus();
        return row;
    }

    function bindRow(row) {
        row.querySelectorAll('[data-line-qty], [data-line-price]').forEach(function (input) {
            input.addEventListener('input', recalc);
            input.addEventListener('change', recalc);
        });
        const catalogSelect = row.querySelector('[data-line-catalog]');
        if (catalogSelect) {
            catalogSelect.addEventListener('change', function () {
                applyCatalog(row, catalogSelect.value);
            });
        }
        const removeBtn = row.querySelector('[data-line-remove]');
        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                const rows = table.querySelectorAll('tbody tr');
                if (rows.length <= 1) {
                    return;
                }
                row.remove();
                recalc();
            });
        }
    }

    addBtn?.addEventListener('click', function () {
        addRow('');
    });

    addCatalog?.addEventListener('change', function () {
        if (!addCatalog.value) {
            return;
        }
        addRow(addCatalog.value);
        addCatalog.value = '';
    });

    table.querySelectorAll('tbody tr').forEach(bindRow);
    recalc();
})();
</script>
@endpush
