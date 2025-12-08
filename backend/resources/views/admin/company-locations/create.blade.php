@extends('admin.layouts.app')

@section('title', 'Nieuwe Vestiging')

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-col gap-5 pb-7.5">
        <div class="flex flex-wrap items-center justify-between gap-5">
            <h1 class="text-xl font-medium leading-none text-mono">
                Nieuwe Vestiging
            </h1>
        </div>
        <div class="flex items-center">
            <a href="{{ route('admin.companies.show', $company) }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
    </div>

    <form action="{{ route('admin.companies.locations.store', $company) }}" method="POST">
        @csrf

        <div class="grid gap-5 lg:gap-7.5">
            @if($errors->any())
                <div class="kt-alert kt-alert-danger mb-5">
                    <i class="ki-filled ki-information-5 me-2"></i>
                    <div>
                        <strong>Er zijn fouten opgetreden:</strong>
                        <ul class="mb-0 mt-2 list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <!-- Vestiging Informatie -->
            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Vestiging Informatie
                    </h3>
                    <div class="flex items-center gap-2">
                        <label class="kt-label">
                            <input type="checkbox" 
                                   class="kt-switch kt-switch-sm" 
                                   name="is_main" 
                                   value="1"
                                   {{ old('is_main') ? 'checked' : '' }}/>
                            Hoofdkantoor
                        </label>
                        <span class="text-muted-foreground">|</span>
                        <label class="kt-label">
                            <input type="checkbox" 
                                   class="kt-switch kt-switch-sm" 
                                   name="is_active" 
                                   value="1"
                                   {{ old('is_active', true) ? 'checked' : '' }}/>
                            Actief
                        </label>
                    </div>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">
                                Naam *
                            </td>
                            <td class="min-w-48 w-full">
                                <input type="text" 
                                       class="kt-input @error('name') border-destructive @enderror" 
                                       name="name" 
                                       value="{{ old('name') }}" 
                                       required>
                                @error('name')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Straat
                            </td>
                            <td>
                                <input type="text" 
                                       class="kt-input @error('street') border-destructive @enderror" 
                                       name="street" 
                                       value="{{ old('street') }}">
                                @error('street')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Huisnummer
                            </td>
                            <td>
                                <input type="text" 
                                       class="kt-input @error('house_number') border-destructive @enderror" 
                                       name="house_number" 
                                       value="{{ old('house_number') }}">
                                @error('house_number')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Postcode
                            </td>
                            <td>
                                <input type="text" 
                                       class="kt-input @error('postal_code') border-destructive @enderror" 
                                       name="postal_code" 
                                       value="{{ old('postal_code') }}"
                                       pattern="[1-9][0-9]{3}\s?[A-Za-z]{2}"
                                       placeholder="1234AB"
                                       maxlength="7"
                                       style="text-transform: uppercase;">
                                <div class="text-xs text-muted-foreground mt-1">Nederlandse postcode (bijv. 1234AB)</div>
                                @error('postal_code')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                                <div class="text-xs text-green-600 mt-1 hidden location-postal-code-success">
                                    <i class="ki-filled ki-check-circle me-1"></i> Postcode is geldig
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Plaats
                            </td>
                            <td>
                                <input type="text" 
                                       class="kt-input @error('city') border-destructive @enderror" 
                                       name="city" 
                                       value="{{ old('city') }}">
                                @error('city')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Land
                            </td>
                            <td>
                                <input type="text" 
                                       class="kt-input @error('country') border-destructive @enderror" 
                                       name="country" 
                                       value="{{ old('country', 'Nederland') }}">
                                @error('country')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Telefoon
                            </td>
                            <td>
                                <input type="tel" 
                                       class="kt-input @error('phone') border-destructive @enderror" 
                                       name="phone" 
                                       value="{{ old('phone') }}"
                                       pattern="(\+31|0)[1-9][0-9]{8}"
                                       placeholder="0612345678 of +31612345678"
                                       maxlength="13">
                                <div class="text-xs text-muted-foreground mt-1">Nederlands nummer (bijv. 0612345678 of +31612345678)</div>
                                @error('phone')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                                <div class="text-xs text-green-600 mt-1 hidden location-phone-success">
                                    <i class="ki-filled ki-check-circle me-1"></i> Telefoonnummer is geldig
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                E-mail
                            </td>
                            <td>
                                <input type="email" 
                                       class="kt-input @error('email') border-destructive @enderror" 
                                       name="email" 
                                       value="{{ old('email') }}"
                                       autocomplete="email">
                                @error('email')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                                <div class="text-xs text-green-600 mt-1 hidden location-email-success">
                                    <i class="ki-filled ki-check-circle me-1"></i> E-mailadres is geldig
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Hoofdkantoor
                            </td>
                            <td>
                                <label class="kt-label">
                                    <input type="checkbox" 
                                           class="kt-switch kt-switch-sm" 
                                           name="is_main" 
                                           value="1"
                                           {{ old('is_main') ? 'checked' : '' }}/>
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-2.5">
                <a href="{{ route('admin.companies.show', $company) }}" class="kt-btn kt-btn-outline">
                    <i class="ki-filled ki-cross me-2"></i>
                    Annuleren
                </a>
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-check me-2"></i>
                    Vestiging Opslaan
                </button>
            </div>
        </div>
    </form>
</div>

@push('styles')
<style>
    /* Remove all borders between table rows in show forms */
    .kt-table-border-dashed tbody tr {
        border-bottom: none !important;
    }
    /* Uniform row height for all table rows */
    .kt-table-border-dashed tbody tr,
    .kt-table-border-dashed tbody tr td {
        height: auto;
        min-height: 48px;
    }
    .kt-table-border-dashed tbody tr td {
        padding-top: 12px;
        padding-bottom: 12px;
        vertical-align: middle;
    }
</style>
@endpush

@endsection

