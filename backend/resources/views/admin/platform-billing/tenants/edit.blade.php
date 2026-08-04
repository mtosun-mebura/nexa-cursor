@extends('admin.layouts.app')

@section('title', 'Tenant facturatie — '.$company->name)

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                {{ $company->name }}
            </h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                SaaS-abonnement en incasso
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.platform-billing.tenants.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5" role="alert">
            <i class="ki-filled ki-check-circle me-2"></i>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="kt-alert kt-alert-destructive mb-5" role="alert">
            <i class="ki-filled ki-information-2 me-2"></i>
            {{ session('error') }}
        </div>
    @endif

    <form method="POST"
          action="{{ route('admin.platform-billing.tenants.update', $company) }}"
          data-validate="true"
          id="tenant-billing-form">
        @csrf
        @method('PUT')

        <div class="grid gap-5 lg:gap-7.5">
            <x-error-card :errors="$errors" />

            <div class="kt-card w-full min-w-0 overflow-x-clip">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Abonnement & facturatie</h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3 min-w-0">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                        <tbody>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">Facturatiemodus</td>
                            <td class="min-w-48 w-full">
                                <select name="billing_mode" class="kt-select w-full" id="billing_mode" data-invoice-preview-trigger>
                                    <option value="package" @selected(old('billing_mode', $profile->billing_mode) === 'package')>Vast pakket</option>
                                    <option value="custom" @selected(old('billing_mode', $profile->billing_mode) === 'custom')>Maatwerk bedrag</option>
                                    <option value="free" @selected(old('billing_mode', $profile->billing_mode) === 'free')>Gratis</option>
                                </select>
                            </td>
                        </tr>
                        <tr data-billing-field-row="package">
                            <td class="text-secondary-foreground font-normal align-top">Pakket</td>
                            <td>
                                <select name="platform_billing_package_id" class="kt-select w-full" id="platform_billing_package_id" data-invoice-preview-trigger>
                                    <option value="">— Kies —</option>
                                    @foreach($packages as $pkg)
                                        <option value="{{ $pkg->id }}" @selected((int) old('platform_billing_package_id', $profile->platform_billing_package_id) === $pkg->id)>
                                            {{ $pkg->name }} (€ {{ number_format((float) $pkg->monthly_amount, 2, ',', '.') }})
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                        <tr data-billing-field-row="custom">
                            <td class="text-secondary-foreground font-normal align-top">Maatwerk maandbedrag (excl. BTW)</td>
                            <td>
                                <div class="inline-flex items-stretch platform-billing-monthly-amount">
                                    <span class="inline-flex items-center shrink-0 px-2.5 border border-input border-r-0 rounded-l-md bg-muted/50 text-sm font-medium text-foreground">€</span>
                                    <input class="kt-input rounded-l-none tabular-nums platform-billing-monthly-amount-input @error('custom_monthly_amount') border-destructive @enderror"
                                           type="number"
                                           step="0.01"
                                           min="0"
                                           name="custom_monthly_amount"
                                           id="custom_monthly_amount"
                                           data-invoice-preview-trigger
                                           value="{{ old('custom_monthly_amount', $profile->custom_monthly_amount) }}"
                                           style="width: 7.5rem; max-width: 7.5rem;">
                                </div>
                                @error('custom_monthly_amount')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Korting abonnement</td>
                            <td>
                                <div class="inline-flex items-stretch platform-billing-percent-input">
                                    <input class="kt-input rounded-r-none tabular-nums platform-billing-percent-input__field @error('discount_percent') border-destructive @enderror"
                                           type="number"
                                           step="1"
                                           min="0"
                                           max="100"
                                           inputmode="numeric"
                                           name="discount_percent"
                                           id="discount_percent"
                                           data-invoice-preview-trigger
                                           value="{{ old('discount_percent', $profile->discountPercent()) }}"
                                           style="width: 5rem; max-width: 5rem;">
                                    <span class="inline-flex items-center shrink-0 px-2.5 border border-input border-l-0 rounded-r-md bg-muted/50 text-sm font-medium text-foreground">%</span>
                                </div>
                                @error('discount_percent')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">Ingangsdatum</td>
                            <td class="min-w-48 w-full min-w-0">
                                <div class="kt-input w-full max-w-xs @error('subscription_start_date') border-destructive @enderror">
                                    <i class="ki-outline ki-calendar"></i>
                                    <input class="grow"
                                           name="subscription_start_date"
                                           id="subscription_start_date"
                                           type="text"
                                           readonly
                                           placeholder="Selecteer datum"
                                           value="{{ admin_date_picker_display(old('subscription_start_date', $profile->subscription_start_date)) }}"
                                           data-kt-date-picker="true"
                                           data-kt-date-picker-input-mode="true"
                                           data-kt-date-picker-position-to-input="left"
                                           data-kt-date-picker-date-format="DD-MM-YYYY"
                                           data-invoice-preview-trigger
                                           data-mollie-preview-trigger>
                                </div>
                                <p class="platform-billing-field-hint text-xs text-muted-foreground mt-1.5 mb-0">
                                    Vanaf wanneer het abonnement loopt. Start u midden in de maand, dan is de eerste incasso pro rata plus de volgende maand vooruit.
                                </p>
                                @error('subscription_start_date')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">Einddatum</td>
                            <td class="min-w-48 w-full min-w-0">
                                <div class="kt-input w-full max-w-xs @error('subscription_end_date') border-destructive @enderror">
                                    <i class="ki-outline ki-calendar"></i>
                                    <input class="grow"
                                           name="subscription_end_date"
                                           id="subscription_end_date"
                                           type="text"
                                           readonly
                                           placeholder="Selecteer datum"
                                           value="{{ admin_date_picker_display(old('subscription_end_date', $profile->subscription_end_date)) }}"
                                           data-kt-date-picker="true"
                                           data-kt-date-picker-input-mode="true"
                                           data-kt-date-picker-position-to-input="left"
                                           data-kt-date-picker-date-format="DD-MM-YYYY"
                                           data-invoice-preview-trigger
                                           data-mollie-preview-trigger>
                                </div>
                                <p class="platform-billing-field-hint text-xs text-muted-foreground mt-1.5 mb-0">
                                    Optioneel. Laatste incasso op de 1e van de maand vóór deze datum; daarna wordt de Mollie-subscription automatisch beëindigd.
                                </p>
                                @error('subscription_end_date')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">Facturatie-e-mail</td>
                            <td class="min-w-48 w-full min-w-0">
                                <input type="email" name="billing_email" class="kt-input w-full" value="{{ old('billing_email', $profile->billing_email ?: $company->email) }}">
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">Contactpersoon</td>
                            <td class="min-w-48 w-full min-w-0">
                                <input type="text" name="billing_contact_name" id="billing_contact_name" class="kt-input w-full" data-invoice-preview-trigger value="{{ old('billing_contact_name', $profile->billing_contact_name) }}">
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">Incasso</td>
                            <td class="min-w-48 w-full min-w-0">
                                <label class="kt-label flex items-center gap-2" for="auto_collect_enabled">
                                    <input class="kt-switch kt-switch-sm"
                                           type="checkbox"
                                           name="auto_collect_enabled"
                                           id="auto_collect_enabled"
                                           value="1"
                                           data-mollie-preview-trigger
                                           @checked(old('auto_collect_enabled', $profile->auto_collect_enabled))>
                                    <span>Automatisch incasseren via mandaat</span>
                                </label>
                                <p class="platform-billing-field-hint text-xs text-muted-foreground mt-1.5 mb-0">
                                    Maandelijkse incasso op de 1e via Mollie, na het afgeven van het mandaat.
                                </p>
                                @if($profile->mollie_subscription_id)
                                    <div class="text-xs text-muted-foreground mt-2">
                                        Mollie subscription: <span class="font-mono">{{ $profile->mollie_subscription_id }}</span>
                                        @if($profile->mollie_subscription_status)
                                            ({{ $profile->mollie_subscription_status }})
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">Notities</td>
                            <td class="min-w-48 w-full min-w-0">
                                <textarea name="notes" id="notes" class="kt-input w-full" rows="3" data-invoice-preview-trigger>{{ old('notes', $profile->notes) }}</textarea>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="kt-card w-full min-w-0 overflow-x-clip">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Extra factuurregels</h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3 min-w-0">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                        <tbody>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">Regels</td>
                            <td class="min-w-48 w-full">
                                @php
                                    $selectedLineItemIds = collect(old('platform_billing_line_item_ids', $profile->lineItems->pluck('id')->all()))
                                        ->map(fn ($id) => (int) $id)
                                        ->all();
                                @endphp
                                @include('admin.platform-billing.tenants.partials.line-item-picker', [
                                    'catalogLineItems' => $catalogLineItems,
                                    'selectedLineItemIds' => $selectedLineItemIds,
                                ])
                                <div class="text-xs text-muted-foreground mt-2.5">
                                    Klik op een regel om deze toe te voegen of te verwijderen.
                                    <a href="{{ route('admin.platform-billing.line-items.index') }}" class="text-primary hover:underline">Beheer factuurregels</a>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Korting factuurregels</td>
                            <td>
                                <div class="inline-flex items-stretch platform-billing-percent-input">
                                    <input class="kt-input rounded-r-none tabular-nums platform-billing-percent-input__field @error('extra_lines_discount_percent') border-destructive @enderror"
                                           type="number"
                                           step="1"
                                           min="0"
                                           max="100"
                                           inputmode="numeric"
                                           name="extra_lines_discount_percent"
                                           id="extra_lines_discount_percent"
                                           data-invoice-preview-trigger
                                           value="{{ old('extra_lines_discount_percent', $profile->extraLinesDiscountPercent()) }}"
                                           style="width: 5rem; max-width: 5rem;">
                                    <span class="inline-flex items-center shrink-0 px-2.5 border border-input border-l-0 rounded-r-md bg-muted/50 text-sm font-medium text-foreground">%</span>
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">
                                    Korting op de geselecteerde extra factuurregels (niet op het abonnement).
                                </div>
                                @error('extra_lines_discount_percent')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Eenmalig</td>
                            <td>
                                <label class="kt-label flex items-center gap-2" for="extra_lines_one_time">
                                    <input class="kt-switch kt-switch-sm"
                                           type="checkbox"
                                           name="extra_lines_one_time"
                                           id="extra_lines_one_time"
                                           data-invoice-preview-trigger
                                           value="1"
                                           @checked(old('extra_lines_one_time', $profile->extra_lines_one_time ?? true))>
                                    <span>Extra regels eenmalig toepassen op de volgende factuur</span>
                                </label>
                                <div class="text-xs text-muted-foreground mt-1">
                                    Uitgeschakeld = extra regels komen elke maand op de factuur terug (bijv. doorlopende wensen).
                                </div>
                                @if($profile->extra_lines_applied_at)
                                    <div class="text-xs text-muted-foreground mt-2">
                                        Eenmalige regels toegepast op: {{ $profile->extra_lines_applied_at->format('d-m-Y H:i') }}.
                                        Wijzig de selectie om opnieuw eenmalige regels te plannen.
                                    </div>
                                @endif
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5">
                <a href="{{ route('admin.platform-billing.tenants.index') }}" class="kt-btn kt-btn-outline">
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

    <div class="grid gap-5 lg:gap-7.5 mt-5 lg:mt-7.5">
        <div class="kt-card min-w-full">
            <div class="kt-card-header flex-wrap gap-2 w-full items-center">
                <h3 class="kt-card-title mb-0">Factuurvoorbeeld</h3>
                <span class="text-xs text-muted-foreground" id="invoice-preview-status"></span>
                <button type="button"
                        class="kt-btn kt-btn-icon kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0"
                        id="invoice-preview-responsive-btn"
                        title="Mobiele weergave"
                        aria-label="Factuurvoorbeeld in mobiele weergave tonen"
                        aria-pressed="false">
                    <i class="ki-filled ki-phone"></i>
                </button>
                <div class="flex items-center gap-1 ms-auto shrink-0">
                    <button type="button"
                            class="kt-btn kt-btn-icon kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0"
                            id="invoice-preview-pdf-preview-btn"
                            title="PDF bekijken"
                            aria-label="Factuurvoorbeeld als PDF bekijken">
                        <i class="ki-filled ki-eye"></i>
                    </button>
                    <button type="button"
                            class="kt-btn kt-btn-icon kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0"
                            id="invoice-preview-pdf-download-btn"
                            title="PDF downloaden"
                            aria-label="Factuurvoorbeeld als PDF downloaden">
                        <i class="ki-filled ki-file-down"></i>
                    </button>
                </div>
            </div>
            <div class="kt-card-content p-5 lg:p-7.5 min-w-0">
                <div id="invoice-preview-viewport">
                    <div id="invoice-preview-container">
                        @include('admin.platform-billing.tenants.partials.invoice-preview', ['preview' => $invoicePreview])
                    </div>
                </div>
                <div id="invoice-preview-unsaved-notice"
                     class="kt-alert kt-alert-warning mt-5 mb-0 hidden"
                     role="status"
                     aria-live="polite">
                    <i class="ki-filled ki-information-2 me-2"></i>
                    De wijzigingen zijn nog niet opgeslagen. Sla het formulier op om de tenant-facturatie definitief bij te werken.
                </div>
            </div>
        </div>

        <div class="kt-card min-w-full">
            <div class="kt-card-header flex-wrap gap-2 w-full items-center">
                <h3 class="kt-card-title mb-0">Mollie API-requests</h3>
                <span class="text-xs text-muted-foreground" id="mollie-request-preview-status"></span>
            </div>
            <div class="kt-card-content p-5 lg:p-7.5 min-w-0">
                <p class="text-sm text-secondary-foreground mb-4">
                    Voorbeeld van de JSON-requests naar de <a href="https://docs.mollie.com/reference/overview" class="text-primary hover:underline" target="_blank" rel="noopener">Mollie API v2</a> voor deze tenant (customers, eerste betaling/mandaat, subscription en opzegging).
                </p>
                <div id="mollie-request-preview-container">
                    @include('admin.platform-billing.tenants.partials.mollie-request-preview', ['requests' => $mollieRequestPlan])
                </div>
            </div>
        </div>

        <div class="kt-card min-w-full">
            <div class="kt-card-header">
                <h3 class="kt-card-title">SEPA-mandaat</h3>
            </div>
            <div class="kt-card-content p-5 lg:p-7.5">
                <p class="text-sm text-secondary-foreground mb-4">
                    Status: <strong class="text-foreground">{{ $mandate?->status ?? 'nog niet aangevraagd' }}</strong>.
                    Eerste betaling legt het SEPA-mandaat vast; bij een actief abonnement wordt de vooruitbetaling (pro rata + volgende maand) geïncasseerd.
                </p>
                <form method="POST" action="{{ route('admin.platform-billing.tenants.mandate', $company) }}" class="flex flex-wrap gap-4 items-end">
                    @csrf
                    <label class="block flex-1 min-w-[14rem]">
                        <span class="text-sm font-medium text-secondary-foreground">E-mail voor mandaat-link</span>
                        <input type="email" name="recipient_email" class="kt-input mt-1.5 w-full" value="{{ old('recipient_email', $profile->billing_email ?: $company->email) }}">
                    </label>
                    <button type="submit" class="kt-btn kt-btn-outline">
                        Mandaat aanvragen per e-mail
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function initTenantBillingInvoicePreview() {
    var form = document.getElementById('tenant-billing-form');
    var previewContainer = document.getElementById('invoice-preview-container');
    var molliePreviewContainer = document.getElementById('mollie-request-preview-container');
    var previewStatus = document.getElementById('invoice-preview-status');
    var molliePreviewStatus = document.getElementById('mollie-request-preview-status');
    var unsavedNotice = document.getElementById('invoice-preview-unsaved-notice');
    if (!form || !previewContainer) {
        return;
    }

    var previewUrl = @json(route('admin.platform-billing.tenants.invoice-preview', $company));
    var molliePreviewUrl = @json(route('admin.platform-billing.tenants.mollie-request-preview', $company));
    var previewPdfUrl = @json(route('admin.platform-billing.tenants.invoice-preview-pdf', $company));
    var previewTimer;
    var molliePreviewTimer;
    var previewRequestId = 0;
    var molliePreviewRequestId = 0;
    var initialFormSnapshot = '';

    function csrfToken() {
        return form.querySelector('input[name="_token"]')?.value
            || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            || '';
    }

    function readSelectValue(id) {
        var select = document.getElementById(id);
        return select ? String(select.value || '') : '';
    }

    function readInputValue(id) {
        var input = document.getElementById(id);
        return input ? String(input.value || '') : '';
    }

    function readCheckedLineItemIds() {
        return Array.from(form.querySelectorAll('input[name="platform_billing_line_item_ids[]"]:checked'))
            .map(function(input) { return String(input.value || ''); })
            .filter(Boolean)
            .sort();
    }

    function readFieldValue(name) {
        var field = form.querySelector('[name="' + name + '"]');
        return field ? String(field.value || '') : '';
    }

    function readDateValue(id) {
        var value = readInputValue(id);
        if (!value) {
            return '';
        }

        var match = value.match(/^(\d{2})-(\d{2})-(\d{4})$/);
        if (match) {
            return match[3] + '-' + match[2] + '-' + match[1];
        }

        return value;
    }

    function collectFormSnapshot() {
        return JSON.stringify({
            billing_mode: readSelectValue('billing_mode') || 'package',
            platform_billing_package_id: readSelectValue('platform_billing_package_id'),
            custom_monthly_amount: readInputValue('custom_monthly_amount'),
            discount_percent: readInputValue('discount_percent'),
            extra_lines_discount_percent: readInputValue('extra_lines_discount_percent'),
            subscription_start_date: readDateValue('subscription_start_date'),
            subscription_end_date: readDateValue('subscription_end_date'),
            billing_email: readFieldValue('billing_email'),
            billing_contact_name: readInputValue('billing_contact_name'),
            auto_collect_enabled: Boolean(form.querySelector('#auto_collect_enabled')?.checked),
            notes: readInputValue('notes'),
            extra_lines_one_time: Boolean(form.querySelector('#extra_lines_one_time')?.checked),
            line_item_ids: readCheckedLineItemIds(),
        });
    }

    function isFormDirty() {
        return collectFormSnapshot() !== initialFormSnapshot;
    }

    function updateUnsavedNotice() {
        if (!unsavedNotice || form.dataset.tenantBillingSubmitting === '1') {
            return;
        }

        unsavedNotice.classList.toggle('hidden', !isFormDirty());
    }

    function buildPreviewFormData() {
        var formData = new FormData();
        var token = csrfToken();

        if (token) {
            formData.set('_token', token);
        }

        formData.set('billing_mode', readSelectValue('billing_mode') || 'package');
        formData.set('platform_billing_package_id', readSelectValue('platform_billing_package_id'));
        formData.set('custom_monthly_amount', readInputValue('custom_monthly_amount'));
        formData.set('discount_percent', readInputValue('discount_percent'));
        formData.set('extra_lines_discount_percent', readInputValue('extra_lines_discount_percent'));
        formData.set('subscription_start_date', readDateValue('subscription_start_date'));
        formData.set('subscription_end_date', readDateValue('subscription_end_date'));
        formData.set('billing_contact_name', readInputValue('billing_contact_name'));
        formData.set('notes', readInputValue('notes'));

        var autoCollect = form.querySelector('#auto_collect_enabled');
        if (autoCollect && autoCollect.checked) {
            formData.set('auto_collect_enabled', '1');
        }

        var extraLinesOneTime = form.querySelector('#extra_lines_one_time');
        if (extraLinesOneTime && extraLinesOneTime.checked) {
            formData.set('extra_lines_one_time', '1');
        }

        readCheckedLineItemIds().forEach(function(id) {
            formData.append('platform_billing_line_item_ids[]', id);
        });

        return formData;
    }

    function syncBillingFieldRows() {
        var mode = readSelectValue('billing_mode') || 'package';
        form.querySelectorAll('[data-billing-field-row]').forEach(function(row) {
            var rowMode = row.getAttribute('data-billing-field-row');
            var visible = rowMode === mode;
            row.hidden = !visible;
            row.style.display = visible ? '' : 'none';
        });
    }

    function schedulePreviewUpdate() {
        syncBillingFieldRows();
        updateUnsavedNotice();
        clearTimeout(previewTimer);
        previewTimer = setTimeout(updateInvoicePreview, 200);
        clearTimeout(molliePreviewTimer);
        molliePreviewTimer = setTimeout(updateMollieRequestPreview, 200);
    }

    function updateMollieRequestPreview() {
        if (!molliePreviewContainer) {
            return;
        }

        var requestId = ++molliePreviewRequestId;
        if (molliePreviewStatus) {
            molliePreviewStatus.textContent = 'Bijwerken…';
        }

        fetch(molliePreviewUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html',
            },
            body: buildPreviewFormData(),
            credentials: 'same-origin',
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('Mollie preview failed: ' + response.status);
            }
            return response.text();
        })
        .then(function(html) {
            if (requestId !== molliePreviewRequestId) {
                return;
            }
            molliePreviewContainer.innerHTML = html;
            if (molliePreviewStatus) {
                molliePreviewStatus.textContent = '';
            }
        })
        .catch(function() {
            if (requestId !== molliePreviewRequestId) {
                return;
            }
            if (molliePreviewStatus) {
                molliePreviewStatus.textContent = 'Kon Mollie-voorbeeld niet bijwerken';
            }
        });
    }

    function updateInvoicePreview() {
        var requestId = ++previewRequestId;
        if (previewStatus) {
            previewStatus.textContent = 'Voorbeeld bijwerken…';
        }

        fetch(previewUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html',
            },
            body: buildPreviewFormData(),
            credentials: 'same-origin',
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('Preview failed: ' + response.status);
            }
            return response.text();
        })
        .then(function(html) {
            if (requestId !== previewRequestId) {
                return;
            }
            previewContainer.innerHTML = html;
            if (previewStatus) {
                previewStatus.textContent = '';
            }
        })
        .catch(function() {
            if (requestId !== previewRequestId) {
                return;
            }
            if (previewStatus) {
                previewStatus.textContent = 'Kon voorbeeld niet bijwerken';
            }
        });
    }

    function getPdfActionButtons() {
        return [
            document.getElementById('invoice-preview-pdf-preview-btn'),
            document.getElementById('invoice-preview-pdf-download-btn'),
        ].filter(Boolean);
    }

    function setPdfActionButtonsDisabled(disabled) {
        getPdfActionButtons().forEach(function(button) {
            button.disabled = disabled;
        });
    }

    function fetchInvoicePreviewPdf() {
        return fetch(previewPdfUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/pdf',
            },
            body: buildPreviewFormData(),
            credentials: 'same-origin',
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('PDF failed: ' + response.status);
            }

            var filename = 'factuur-voorbeeld.pdf';
            var disposition = response.headers.get('Content-Disposition') || '';
            var match = disposition.match(/filename=\"?([^\";]+)\"?/i);
            if (match && match[1]) {
                filename = match[1];
            }

            return response.blob().then(function(blob) {
                return { blob: blob, filename: filename };
            });
        });
    }

    function previewInvoicePreviewPdf() {
        setPdfActionButtonsDisabled(true);
        if (previewStatus) {
            previewStatus.textContent = 'PDF genereren…';
        }

        fetchInvoicePreviewPdf()
        .then(function(result) {
            var url = URL.createObjectURL(result.blob);
            var opened = window.open(url, '_blank');
            if (!opened) {
                throw new Error('Popup blocked');
            }
            window.setTimeout(function() {
                URL.revokeObjectURL(url);
            }, 60000);
            if (previewStatus) {
                previewStatus.textContent = '';
            }
        })
        .catch(function() {
            if (previewStatus) {
                previewStatus.textContent = 'Kon PDF niet openen';
            }
        })
        .finally(function() {
            setPdfActionButtonsDisabled(false);
        });
    }

    function downloadInvoicePreviewPdf() {
        setPdfActionButtonsDisabled(true);
        if (previewStatus) {
            previewStatus.textContent = 'PDF genereren…';
        }

        fetchInvoicePreviewPdf()
        .then(function(result) {
            var url = URL.createObjectURL(result.blob);
            var link = document.createElement('a');
            link.href = url;
            link.download = result.filename;
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(url);
            if (previewStatus) {
                previewStatus.textContent = '';
            }
        })
        .catch(function() {
            if (previewStatus) {
                previewStatus.textContent = 'Kon PDF niet genereren';
            }
        })
        .finally(function() {
            setPdfActionButtonsDisabled(false);
        });
    }

    form.addEventListener('input', schedulePreviewUpdate, true);
    form.addEventListener('change', schedulePreviewUpdate, true);

    form.querySelectorAll('[data-invoice-preview-trigger]').forEach(function(field) {
        field.addEventListener('input', schedulePreviewUpdate);
        field.addEventListener('change', schedulePreviewUpdate);
    });

    var lineItemPicker = document.getElementById('platform_billing_line_item_ids');
    if (lineItemPicker) {
        lineItemPicker.addEventListener('click', function(event) {
            if (event.target.closest('.platform-billing-line-item-picker__option')) {
                setTimeout(schedulePreviewUpdate, 0);
            }
        }, true);
    }

    function bindKtSelectPreview(selectId) {
        var select = document.getElementById(selectId);
        if (!select) {
            return;
        }

        select.addEventListener('change', schedulePreviewUpdate);

        function attachKtSelectHooks() {
            if (typeof window.KTSelect === 'undefined') {
                return;
            }

            try {
                var instance = window.KTSelect.getInstance(select);
                if (!instance && typeof window.KTSelect.init === 'function') {
                    instance = window.KTSelect.init(select);
                }

                if (instance && typeof instance.on === 'function') {
                    instance.on('change', schedulePreviewUpdate);
                    instance.on('select', schedulePreviewUpdate);
                }
            } catch (error) {
                // Native select change handler remains active.
            }
        }

        attachKtSelectHooks();
        window.setTimeout(attachKtSelectHooks, 300);
        window.setTimeout(attachKtSelectHooks, 1000);
    }

    document.addEventListener('click', function(event) {
        if (!event.target.closest('#tenant-billing-form')) {
            return;
        }

        if (event.target.closest('[data-kt-select-option]')) {
            window.setTimeout(schedulePreviewUpdate, 0);
        }
    }, true);

    bindKtSelectPreview('billing_mode');
    bindKtSelectPreview('platform_billing_package_id');

    var pdfPreviewButton = document.getElementById('invoice-preview-pdf-preview-btn');
    if (pdfPreviewButton) {
        pdfPreviewButton.addEventListener('click', previewInvoicePreviewPdf);
    }

    var pdfDownloadButton = document.getElementById('invoice-preview-pdf-download-btn');
    if (pdfDownloadButton) {
        pdfDownloadButton.addEventListener('click', downloadInvoicePreviewPdf);
    }

    var previewViewport = document.getElementById('invoice-preview-viewport');
    var responsiveButton = document.getElementById('invoice-preview-responsive-btn');
    if (previewViewport && responsiveButton) {
        responsiveButton.addEventListener('click', function() {
            var enabled = previewViewport.classList.toggle('is-responsive-preview');
            responsiveButton.setAttribute('aria-pressed', enabled ? 'true' : 'false');
            responsiveButton.classList.toggle('text-primary', enabled);
        });
    }

    form.addEventListener('submit', function() {
        form.dataset.tenantBillingSubmitting = '1';
        if (unsavedNotice) {
            unsavedNotice.classList.add('hidden');
        }
    });

    initialFormSnapshot = collectFormSnapshot();
    syncBillingFieldRows();
    updateUnsavedNotice();
    schedulePreviewUpdate();

    ['subscription_start_date', 'subscription_end_date'].forEach(function(id) {
        var input = document.getElementById(id);
        if (!input) {
            return;
        }

        input.addEventListener('change', schedulePreviewUpdate);
        input.addEventListener('input', schedulePreviewUpdate);
    });
})();
</script>
@endsection

@push('styles')
@include('admin.platform-billing.partials.form-switch-styles')
@include('admin.platform-billing.partials.invoice-preview-styles')
<style>
    .platform-billing-field-hint {
        max-width: 34rem;
        line-height: 1.45;
    }

    #tenant-billing-form .wizard-onboarding-form-table {
        table-layout: fixed;
        width: 100%;
    }

    #tenant-billing-form .wizard-onboarding-form-table td:first-child {
        width: 14rem;
        vertical-align: top;
    }

    #tenant-billing-form .wizard-onboarding-form-table td:last-child {
        min-width: 0;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .platform-billing-monthly-amount-input::-webkit-outer-spin-button,
    .platform-billing-monthly-amount-input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .platform-billing-monthly-amount-input {
        -moz-appearance: textfield;
        appearance: textfield;
    }

    .platform-billing-percent-input__field::-webkit-outer-spin-button,
    .platform-billing-percent-input__field::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .platform-billing-percent-input__field {
        -moz-appearance: textfield;
        appearance: textfield;
    }

    .platform-billing-line-item-picker {
        display: flex;
        flex-direction: column;
        gap: 0.625rem;
        max-width: 42rem;
    }

    .platform-billing-line-item-picker__option {
        display: flex;
        align-items: flex-start;
        gap: 0.875rem;
        margin: 0;
        padding: 0.9375rem 1.125rem;
        border: 1px solid var(--border);
        border-radius: 0.75rem;
        background: var(--card, var(--background));
        cursor: pointer;
        transition: border-color 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease;
    }

    .platform-billing-line-item-picker__option:hover {
        border-color: color-mix(in oklab, var(--primary) 35%, var(--border));
        background: color-mix(in oklab, var(--primary) 4%, var(--card, var(--background)));
    }

    .platform-billing-line-item-picker__option:has(.platform-billing-line-item-picker__input:checked) {
        border-color: color-mix(in oklab, var(--primary) 55%, var(--border));
        background: color-mix(in oklab, var(--primary) 8%, var(--card, var(--background)));
        box-shadow: 0 0 0 1px color-mix(in oklab, var(--primary) 18%, transparent);
    }

    .platform-billing-line-item-picker__input {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    .platform-billing-line-item-picker__indicator {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        width: 1.25rem;
        height: 1.25rem;
        margin-top: 0.125rem;
        border: 1px solid var(--border);
        border-radius: 0.375rem;
        background: var(--background);
        color: transparent;
        transition: border-color 0.15s ease, background-color 0.15s ease, color 0.15s ease;
    }

    .platform-billing-line-item-picker__indicator i {
        font-size: 0.75rem;
        line-height: 1;
    }

    .platform-billing-line-item-picker__option:has(.platform-billing-line-item-picker__input:checked) .platform-billing-line-item-picker__indicator {
        border-color: var(--primary);
        background: var(--primary);
        color: var(--primary-foreground, #fff);
    }

    .platform-billing-line-item-picker__body {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        flex: 1;
        min-width: 0;
        padding-block: 0.0625rem;
    }

    .platform-billing-line-item-picker__title {
        font-size: 0.875rem;
        font-weight: 500;
        line-height: 1.4;
        color: var(--foreground);
    }

    .platform-billing-line-item-picker__description {
        font-size: 0.8125rem;
        line-height: 1.45;
        color: var(--muted-foreground);
    }

    .platform-billing-line-item-picker__price {
        flex-shrink: 0;
        align-self: center;
        padding: 0.375rem 0.625rem;
        border-radius: 9999px;
        background: color-mix(in oklab, var(--foreground) 6%, transparent);
        font-size: 0.8125rem;
        font-weight: 600;
        line-height: 1;
        color: var(--foreground);
        white-space: nowrap;
    }

    .platform-billing-line-item-picker__option:has(.platform-billing-line-item-picker__input:checked) .platform-billing-line-item-picker__price {
        background: color-mix(in oklab, var(--primary) 14%, transparent);
        color: var(--primary);
    }

    .platform-billing-line-item-picker__empty {
        padding: 1rem 1.125rem;
        border: 1px dashed var(--border);
        border-radius: 0.75rem;
        font-size: 0.875rem;
        color: var(--muted-foreground);
    }

    @media (max-width: 1023px) {
        #tenant-billing-form .platform-billing-line-item-picker {
            max-width: 100%;
            width: 100%;
            min-width: 0;
        }

        #tenant-billing-form .platform-billing-line-item-picker__option {
            flex-wrap: wrap;
            align-items: flex-start;
            min-width: 0;
            max-width: 100%;
            box-sizing: border-box;
        }

        #tenant-billing-form .platform-billing-line-item-picker__body {
            flex: 1 1 calc(100% - 2.125rem);
            min-width: 0;
        }

        #tenant-billing-form .platform-billing-line-item-picker__description {
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        #tenant-billing-form .platform-billing-line-item-picker__price {
            margin-left: 2.125rem;
            align-self: flex-start;
        }

        #tenant-billing-form .wizard-onboarding-form-table .kt-label {
            flex-wrap: wrap;
            align-items: flex-start;
            min-width: 0;
            max-width: 100%;
        }

        #tenant-billing-form .wizard-onboarding-form-table .kt-label > span {
            min-width: 0;
            flex: 1 1 auto;
            line-height: 1.45;
        }

        #tenant-billing-form .wizard-onboarding-form-table td {
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        #tenant-billing-form .platform-billing-monthly-amount > span,
        #tenant-billing-form .platform-billing-percent-input > span {
            font-size: 0.8125rem !important;
        }

        #tenant-billing-form .kt-card-table .kt-input,
        #tenant-billing-form .kt-card-table input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]),
        #tenant-billing-form .kt-card-table textarea,
        #tenant-billing-form .kt-card-table select,
        #tenant-billing-form .kt-card-table .kt-select-display,
        #tenant-billing-form .kt-card-table [data-kt-select-display] {
            font-size: 0.8125rem !important;
            line-height: 1.35 !important;
        }
    }
</style>
@endpush
