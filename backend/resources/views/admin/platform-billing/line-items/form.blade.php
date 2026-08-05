@extends('admin.layouts.app')

@section('title', $lineItem->exists ? 'Factuurregel bewerken' : 'Nieuwe factuurregel')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                {{ $lineItem->exists ? 'Factuurregel bewerken' : 'Nieuwe factuurregel' }}
            </h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Generieke regel voor SaaS-facturen
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.platform-billing.line-items.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
    </div>

    <form method="POST" action="{{ $lineItem->exists ? route('admin.platform-billing.line-items.update', $lineItem) : route('admin.platform-billing.line-items.store') }}" data-validate="true">
        @csrf
        @if($lineItem->exists)
            @method('PUT')
        @endif

        <div class="grid gap-5 lg:gap-7.5">
            <x-error-card :errors="$errors" />

            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Regelgegevens</h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">Naam <span class="text-destructive">*</span></td>
                            <td class="min-w-48 w-full">
                                <input class="kt-input w-full @error('name') border-destructive @enderror" type="text" name="name" value="{{ old('name', $lineItem->name) }}" required>
                                @error('name')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Omschrijving</td>
                            <td>
                                <textarea class="kt-input w-full @error('description') border-destructive @enderror" name="description" rows="3">{{ old('description', $lineItem->description) }}</textarea>
                                @error('description')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Prijs excl. BTW <span class="text-destructive">*</span></td>
                            <td>
                                <div class="inline-flex items-stretch platform-billing-monthly-amount">
                                    <span class="inline-flex items-center shrink-0 px-2.5 border border-input border-r-0 rounded-l-md bg-muted/50 text-sm font-medium text-foreground">€</span>
                                    <input class="kt-input rounded-l-none tabular-nums platform-billing-monthly-amount-input @error('unit_price') border-destructive @enderror"
                                           type="number"
                                           step="0.01"
                                           min="0"
                                           name="unit_price"
                                           value="{{ old('unit_price', $lineItem->unit_price ?? 0) }}"
                                           style="width: 7.5rem; max-width: 7.5rem;"
                                           required>
                                </div>
                                @error('unit_price')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Sorteervolgorde</td>
                            <td>
                                <input class="kt-input @error('sort_order') border-destructive @enderror" type="number" name="sort_order" value="{{ old('sort_order', $lineItem->sort_order ?? 0) }}" style="width: 13ch;">
                                @error('sort_order')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Status</td>
                            <td>
                                <label class="kt-label flex items-center gap-2" for="line_item_is_active">
                                    <input class="kt-switch kt-switch-sm"
                                           type="checkbox"
                                           name="is_active"
                                           id="line_item_is_active"
                                           value="1"
                                           @checked(old('is_active', $lineItem->is_active ?? true))>
                                    <span>Actief</span>
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5">
                <a href="{{ route('admin.platform-billing.line-items.index') }}" class="kt-btn kt-btn-outline">
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
@endsection

@push('styles')
@include('admin.platform-billing.partials.form-switch-styles')
<style>
    .platform-billing-monthly-amount-input::-webkit-outer-spin-button,
    .platform-billing-monthly-amount-input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    .platform-billing-monthly-amount-input {
        -moz-appearance: textfield;
        appearance: textfield;
    }
</style>
@endpush
