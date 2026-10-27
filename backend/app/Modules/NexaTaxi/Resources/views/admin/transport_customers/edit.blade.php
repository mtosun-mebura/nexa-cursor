@extends('admin.layouts.app')

@section('title', __('Klant bewerken'))

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">{{ __(':name bewerken', ['name' => $customer->name]) }}</h1>
        <div class="pt-3">
            <a href="{{ route('admin.taxi.transport_customers.show', $customer->id) }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                {{ __('Terug') }}
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
                    <h3 class="kt-card-title mb-0">{{ __('Klantgegevens') }}</h3>
                </div>
                <div class="kt-card-content p-0">
                    <div class="px-3 sm:px-5 pb-3 min-w-0">
                        <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Naam') }} <span class="text-danger">*</span></td>
                                <td class="min-w-48 w-full">
                                    <input type="text" name="name" value="{{ old('name', $customer->name) }}" class="kt-input w-full" required maxlength="200">
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal">{{ __('Type organisatie') }}</td>
                                <td>
                                    <select name="organization_type" class="kt-select admin-field-fit" data-kt-select="true">
                                        @foreach(\App\Modules\NexaTaxi\Models\TransportCustomer::ORGANIZATION_TYPES as $typeKey => $typeLabel)
                                            <option value="{{ $typeKey }}" @selected(old('organization_type', $customer->organizationTypeKey()) === $typeKey)>{{ __($typeLabel) }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal">{{ __('Contactpersoon') }}</td>
                                <td>
                                    <input type="text" name="contact_name" value="{{ old('contact_name', $customer->contact_name) }}" class="kt-input w-full" maxlength="200">
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal">{{ __('E-mailadres contact') }}</td>
                                <td>
                                    <input type="email" name="contact_email" value="{{ old('contact_email', $customer->contact_email) }}" class="kt-input w-full" maxlength="200">
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal">{{ __('Telefoonnummer') }}</td>
                                <td>
                                    <input type="text" name="contact_phone" value="{{ old('contact_phone', $customer->contact_phone) }}" class="kt-input w-full" maxlength="50">
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal">{{ __('Debiteurnummer') }}</td>
                                <td>
                                    <input type="text" name="debtor_number" value="{{ old('debtor_number', $customer->debtor_number) }}" class="kt-input w-full" maxlength="50">
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal">{{ __('Status') }}</td>
                                <td>
                                    <input type="hidden" name="active" value="0">
                                    <label class="kt-label flex items-center gap-2 mb-0" for="active">
                                        <input type="checkbox" name="active" id="active" value="1" class="kt-switch kt-switch-sm shrink-0" @checked(old('active', $customer->active))>
                                        <span class="text-sm text-muted-foreground">{{ __('Actief') }}</span>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header">
                    <h3 class="kt-card-title mb-0">{{ __('Factuuradres') }}</h3>
                </div>
                <div class="kt-card-content p-0">
                    <div class="px-3 sm:px-5 pb-3 min-w-0">
                        <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Adres') }}</td>
                                <td class="min-w-48 w-full">
                                    @include('admin.partials.google-address-input', [
                                        'name' => 'billing_address',
                                        'value' => old('billing_address', $customer->billing_address),
                                        'maxlength' => 300,
                                        'placeholder' => __('Zoek factuuradres...'),
                                    ])
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal">{{ __('Postcode') }}</td>
                                <td>
                                    <input type="text" name="billing_postal_code" value="{{ old('billing_postal_code', $customer->billing_postal_code) }}" class="kt-input w-full" maxlength="20">
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal">{{ __('Stad') }}</td>
                                <td>
                                    <input type="text" name="billing_city" value="{{ old('billing_city', $customer->billing_city) }}" class="kt-input w-full" maxlength="100">
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal">{{ __('Land') }}</td>
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
                    <h3 class="kt-card-title mb-0">{{ __('Notities') }}</h3>
                </div>
                <div class="kt-card-content p-0">
                    <div class="px-3 sm:px-5 pt-3 pb-3 min-w-0">
                        <textarea name="notes" rows="4" class="kt-textarea w-full">{{ old('notes', $customer->notes) }}</textarea>
                    </div>
                </div>
            </div>

        </div>

        <div class="admin-form-actions flex flex-wrap items-center justify-end gap-2.5 mt-5 w-full min-w-0">
            <a href="{{ route('admin.taxi.transport_customers.show', $customer->id) }}" class="kt-btn kt-btn-outline">{{ __('Annuleren') }}</a>
            <button type="submit" class="kt-btn kt-btn-primary">{{ __('Opslaan') }}</button>
        </div>
    </form>
</div>
@endsection
