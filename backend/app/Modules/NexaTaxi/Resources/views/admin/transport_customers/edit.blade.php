@extends('admin.layouts.app')

@section('title', 'Klant bewerken')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">{{ $customer->name }} bewerken</h1>
        <div class="pt-3">
            <a href="{{ route('admin.taxi.transport_customers.show', $customer->id) }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="kt-alert kt-alert-danger mb-5" role="alert">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.taxi.transport_customers.update', $customer->id) }}">
        @csrf
        @method('PUT')
        <div class="grid gap-5 lg:gap-7.5">

            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header">
                    <h3 class="kt-card-title mb-0">Klantgegevens</h3>
                </div>
                <div class="kt-card-content p-0">
                    <div class="px-3 sm:px-5 pb-3 min-w-0">
                        <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">Naam <span class="text-danger">*</span></td>
                                <td class="min-w-48 w-full">
                                    <input type="text" name="name" value="{{ old('name', $customer->name) }}" class="kt-input w-full" required maxlength="200">
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal">Contactpersoon</td>
                                <td>
                                    <input type="text" name="contact_name" value="{{ old('contact_name', $customer->contact_name) }}" class="kt-input w-full" maxlength="200">
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal">E-mailadres contact</td>
                                <td>
                                    <input type="email" name="contact_email" value="{{ old('contact_email', $customer->contact_email) }}" class="kt-input w-full" maxlength="200">
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal">Telefoonnummer</td>
                                <td>
                                    <input type="text" name="contact_phone" value="{{ old('contact_phone', $customer->contact_phone) }}" class="kt-input w-full" maxlength="50">
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal">Debiteurnummer</td>
                                <td>
                                    <input type="text" name="debtor_number" value="{{ old('debtor_number', $customer->debtor_number) }}" class="kt-input w-full" maxlength="50">
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal">Status</td>
                                <td>
                                    <input type="hidden" name="active" value="0">
                                    <label class="kt-label flex items-center gap-2 mb-0" for="active">
                                        <input type="checkbox" name="active" id="active" value="1" class="kt-switch kt-switch-sm shrink-0" @checked(old('active', $customer->active))>
                                        <span class="text-sm text-muted-foreground">Actief</span>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header">
                    <h3 class="kt-card-title mb-0">Factuuradres</h3>
                </div>
                <div class="kt-card-content p-0">
                    <div class="px-3 sm:px-5 pb-3 min-w-0">
                        <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">Adres</td>
                                <td class="min-w-48 w-full">
                                    @include('admin.partials.google-address-input', [
                                        'name' => 'billing_address',
                                        'value' => old('billing_address', $customer->billing_address),
                                        'maxlength' => 300,
                                        'placeholder' => 'Zoek factuuradres...',
                                    ])
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal">Postcode</td>
                                <td>
                                    <input type="text" name="billing_postal_code" value="{{ old('billing_postal_code', $customer->billing_postal_code) }}" class="kt-input w-full" maxlength="20">
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal">Stad</td>
                                <td>
                                    <input type="text" name="billing_city" value="{{ old('billing_city', $customer->billing_city) }}" class="kt-input w-full" maxlength="100">
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal">Land</td>
                                <td>
                                    <input type="text" name="billing_country" value="{{ old('billing_country', $customer->billing_country) }}" class="kt-input w-full" maxlength="100">
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header">
                    <h3 class="kt-card-title mb-0">Notities</h3>
                </div>
                <div class="kt-card-content p-0">
                    <div class="px-3 sm:px-5 pt-3 pb-3 min-w-0">
                        <textarea name="notes" rows="4" class="kt-textarea w-full">{{ old('notes', $customer->notes) }}</textarea>
                    </div>
                </div>
            </div>

        </div>

        <div class="admin-form-actions flex flex-wrap items-center justify-end gap-2.5 mt-5 w-full min-w-0">
            <a href="{{ route('admin.taxi.transport_customers.show', $customer->id) }}" class="kt-btn kt-btn-outline">Annuleren</a>
            <button type="submit" class="kt-btn kt-btn-primary">Opslaan</button>
        </div>
    </form>
</div>
@endsection
