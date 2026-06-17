@extends('admin.layouts.app')

@section('title', 'Abonnement bewerken')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">{{ $contract->name }} bewerken</h1>
        <p class="text-sm text-muted-foreground pt-2">Klant: {{ $customer->name }}</p>
        <div class="pt-3">
            <a href="{{ route('admin.taxi.transport_customers.contract_show', [$customer->id, $contract->id]) }}" class="kt-btn kt-btn-outline">
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

    <form method="POST" action="{{ route('admin.taxi.transport_customers.contract_update', [$customer->id, $contract->id]) }}">
        @csrf
        @method('PUT')
        <div class="grid gap-5 lg:gap-7.5">

            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header">
                    <h3 class="kt-card-title mb-0">Abonnementsgegevens</h3>
                </div>
                <div class="kt-card-content p-0">
                    <div class="px-3 sm:px-5 pb-3 min-w-0">
                        <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">Naam <span class="text-danger">*</span></td>
                                <td class="min-w-48 w-full">
                                    <input type="text" name="name" value="{{ old('name', $contract->name) }}" class="kt-input w-full" required maxlength="200">
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal">Status</td>
                                <td>
                                    <select name="status" class="kt-select w-full">
                                        <option value="active" @selected(old('status', $contract->status) === 'active')>Actief</option>
                                        <option value="paused" @selected(old('status', $contract->status) === 'paused')>Gepauzeerd</option>
                                        <option value="ended" @selected(old('status', $contract->status) === 'ended')>Beëindigd</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal">Startdatum</td>
                                <td>
                                    @include('taxi::admin.transport_customers.partials.date-picker-input', [
                                        'name' => 'start_date',
                                        'value' => old('start_date', $contract->start_date?->format('Y-m-d') ?? ''),
                                    ])
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal">Einddatum</td>
                                <td>
                                    @include('taxi::admin.transport_customers.partials.date-picker-input', [
                                        'name' => 'end_date',
                                        'value' => old('end_date', $contract->end_date?->format('Y-m-d') ?? ''),
                                    ])
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header">
                    <h3 class="kt-card-title mb-0">Facturatie</h3>
                </div>
                <div class="kt-card-content p-0">
                    <div class="px-3 sm:px-5 pb-3 min-w-0">
                        <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">Facturatiemodel</td>
                                <td class="min-w-48 w-full">
                                    <select name="billing_model" class="kt-select w-full" id="billing_model">
                                        <option value="fixed_monthly" @selected(old('billing_model', $contract->billing_model) === 'fixed_monthly')>Vast maandbedrag</option>
                                        <option value="per_ride" @selected(old('billing_model', $contract->billing_model) === 'per_ride')>Per rit</option>
                                        <option value="hybrid" @selected(old('billing_model', $contract->billing_model) === 'hybrid')>Hybride</option>
                                    </select>
                                </td>
                            </tr>
                            <tr id="row_monthly_amount">
                                <td class="text-secondary-foreground font-normal">Maandbedrag (excl. BTW)</td>
                                <td>
                                    <div class="kt-input-group w-full max-w-xs">
                                        <span class="kt-input-addon">&euro;</span>
                                        <input type="number" name="monthly_amount" value="{{ old('monthly_amount', $contract->monthly_amount) }}" class="kt-input" step="0.01" min="0">
                                    </div>
                                </td>
                            </tr>
                            <tr id="row_price_per_ride">
                                <td class="text-secondary-foreground font-normal">Prijs per rit (excl. BTW)</td>
                                <td>
                                    <div class="kt-input-group w-full max-w-xs">
                                        <span class="kt-input-addon">&euro;</span>
                                        <input type="number" name="price_per_ride" value="{{ old('price_per_ride', $contract->price_per_ride) }}" class="kt-input" step="0.01" min="0">
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal">BTW-percentage</td>
                                <td>
                                    <div class="kt-input-group w-fit">
                                        <input type="number" name="tax_rate" value="{{ old('tax_rate', $contract->tax_rate) }}" class="kt-input w-24 grow-0" step="0.01" min="0" max="100">
                                        <span class="kt-input-addon">%</span>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal">Factuurdag van de maand</td>
                                <td>
                                    <input type="number" name="invoice_day" value="{{ old('invoice_day', $contract->invoice_day) }}" class="kt-input w-24" min="1" max="28" required>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal">Betalingstermijn (dagen)</td>
                                <td>
                                    <input type="number" name="payment_terms_days" value="{{ old('payment_terms_days', $contract->payment_terms_days) }}" class="kt-input w-24" min="1" max="90" required>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <div class="admin-form-actions flex flex-wrap items-center justify-end gap-2.5 mt-5 w-full min-w-0">
            <a href="{{ route('admin.taxi.transport_customers.contract_show', [$customer->id, $contract->id]) }}" class="kt-btn kt-btn-outline">Annuleren</a>
            <button type="submit" class="kt-btn kt-btn-primary">Opslaan</button>
        </div>
    </form>
</div>
@push('scripts')
<script>
(function () {
    var model = document.getElementById('billing_model');
    var rowMonthly = document.getElementById('row_monthly_amount');
    var rowPerRide = document.getElementById('row_price_per_ride');
    function update() {
        var v = model.value;
        rowMonthly.style.display = (v === 'per_ride') ? 'none' : '';
        rowPerRide.style.display = (v === 'fixed_monthly') ? 'none' : '';
    }
    model.addEventListener('change', update);
    update();
})();
</script>
@endpush
@endsection
