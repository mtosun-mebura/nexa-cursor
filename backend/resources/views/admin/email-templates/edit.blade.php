@extends('admin.layouts.app')

@section('title', 'E-mail Template Bewerken')

@section('content')

<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">
            E-mail Template Bewerken
        </h1>
        <a href="{{ route('admin.email-templates.index') }}" class="kt-btn kt-btn-outline shrink-0">
            <i class="ki-filled ki-arrow-left me-2"></i>
            Terug
        </a>
    </div>

    @if($errors->any())
        <div class="kt-alert kt-alert-danger mb-5">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-5 lg:gap-7.5">
        <form id="email-template-form" action="{{ route('admin.email-templates.update', $emailTemplate) }}" method="POST" style="display: contents;" data-validate="true" novalidate>
            @csrf
            @method('PUT')
            @if(auth()->user()->hasRole('super-admin'))
            <!-- Bedrijf Selectie (alleen voor Super Admin) -->
            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header">
                    <h5 class="kt-card-title mb-0">Bedrijf</h5>
                </div>
                <div class="kt-card-content p-0">
                    <div class="px-3 sm:px-5 pb-3 min-w-0">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Bedrijf</td>
                            <td class="min-w-48 w-full">
                                <select class="kt-select @error('company_id') border-destructive @enderror" 
                                        id="company_id" 
                                        name="company_id">
                                    <option value="">Algemeen (geen specifiek bedrijf)</option>
                                    @foreach($companies as $company)
                                        <option value="{{ $company->id }}" {{ old('company_id', $emailTemplate->company_id) == $company->id ? 'selected' : '' }}>
                                            {{ $company->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('company_id')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                                <div class="text-xs text-muted-foreground mt-1">
                                    Selecteer een bedrijf om deze template alleen voor dat bedrijf beschikbaar te maken, of laat leeg voor algemeen gebruik.
                                </div>
                            </td>
                        </tr>
                    </table>
                    </div>
                </div>
            </div>
            @endif
            
            <!-- Basic Information Card -->
            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header">
                    <h5 class="kt-card-title mb-0">Basis Informatie</h5>
                </div>
                <div class="kt-card-content p-0">
                    <div class="px-3 sm:px-5 pb-3 min-w-0">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Naam *</td>
                            <td class="min-w-48 w-full">
                                <input type="text" 
                                       class="kt-input @error('name') border-destructive @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name', $emailTemplate->name) }}" 
                                       required>
                                @error('name')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Onderwerp *</td>
                            <td class="min-w-48 w-full">
                                <input type="text" 
                                       class="kt-input w-full @error('subject') border-destructive @enderror" 
                                       id="subject" 
                                       name="subject" 
                                       value="{{ old('subject', $emailTemplate->subject) }}" 
                                       required>
                                @error('subject')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Type *</td>
                            <td>
                                <select class="kt-select @error('type') border-destructive @enderror" 
                                        id="type" 
                                        name="type" 
                                        required>
                                    <option value="">Selecteer type</option>
                                    @foreach($allowedTypes as $typeKey)
                                        <option value="{{ $typeKey }}" {{ old('type', $emailTemplate->type) == $typeKey ? 'selected' : '' }}>{{ $typeLabels[$typeKey] ?? $typeKey }}</option>
                                    @endforeach
                                </select>
                                @error('type')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Status</td>
                            <td>
                                <select class="kt-select @error('is_active') border-destructive @enderror" 
                                        id="is_active" 
                                        name="is_active">
                                    <option value="1" {{ old('is_active', $emailTemplate->is_active) == '1' || old('is_active', $emailTemplate->is_active) === true ? 'selected' : '' }}>Actief</option>
                                    <option value="0" {{ old('is_active', $emailTemplate->is_active) == '0' || old('is_active', $emailTemplate->is_active) === false ? 'selected' : '' }}>Inactief</option>
                                </select>
                                @error('is_active')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Beschrijving</td>
                            <td>
                                <textarea class="kt-input @error('description') border-destructive @enderror" 
                                          id="description" 
                                          name="description" 
                                          rows="4">{{ old('description', $emailTemplate->description) }}</textarea>
                                @error('description')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        @include('admin.email-templates.partials.recipient-fields', ['emailTemplate' => $emailTemplate, 'users' => $users])
                    </table>
                    </div>
                </div>
            </div>

            @include('admin.email-templates.partials.form-field-order', ['emailTemplate' => $emailTemplate, 'formFields' => $formFields ?? collect(), 'allFormFieldsPool' => $allFormFieldsPool ?? collect(), 'isInfoRequestType' => $isInfoRequestType ?? false])

            @if(isset($isInfoRequestType) && $isInfoRequestType)
            <!-- Frontend weergave (formulierpreview, volgorde synct met Formuliervelden) -->
            <div class="kt-card w-full min-w-0" id="frontend-weergave-card">
                <div class="kt-card-header">
                    <h5 class="kt-card-title mb-0">Frontend weergave</h5>
                </div>
                <div class="kt-card-content min-w-0">
                    <p class="text-sm text-muted-foreground mb-3">Zo ziet het formulier er op de website uit. De volgorde wijzigt direct wanneer je velden hierboven herschikt.</p>
                    @include('admin.email-templates.partials.formulier-preview', ['formFields' => $formFields ?? [], 'emailTemplate' => $emailTemplate])
                </div>
            </div>
            @endif

            <!-- HTML Content Card (zelfde breedte als overige secties) -->
            <div class="kt-card w-full min-w-0" id="html-content-card">
                <div class="kt-card-header">
                    <h5 class="kt-card-title mb-0">HTML Inhoud *</h5>
                </div>
                <div class="kt-card-content min-w-0">
                    <div class="mb-3">
                        <label for="html_content" class="kt-form-label mb-2">HTML Inhoud</label>
                        <textarea class="kt-input @error('html_content') border-destructive @enderror" 
                                  id="html_content" 
                                  name="html_content" 
                                  rows="10" 
                                  data-skip-validation-wrapper
                                  required>{{ old('html_content', $emailTemplate->html_content) }}</textarea>
                        @error('html_content')
                            <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="text-xs text-muted-foreground mb-2">
                        <strong>Tip:</strong> Gebruik de knoppen voor opmaak of wissel naar "Bewerk code" (knop &lt;/&gt;) om HTML en variabelen te bewerken.
                    </div>
                    <div class="text-xs text-muted-foreground mb-5">
                        <p class="mb-2"><strong>Beschikbare variabelen {{ (isset($isInfoRequestType) && $isInfoRequestType) ? '(formulier op de website)' : '' }}:</strong></p>
                        <div class="space-y-1.5">
                            @if(isset($isInfoRequestType) && $isInfoRequestType && isset($infoRequestVariables) && is_array($infoRequestVariables))
                                @foreach($infoRequestVariables as $variable => $description)
                                    <div class="flex flex-wrap items-start sm:items-center gap-2 min-w-0">
                                        <code class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-gray-600 dark:text-white font-mono text-xs font-semibold">{{ '{' }}{{ '{' }}{{ $variable }}{{ '}' }}{{ '}' }}</code>
                                        <span class="text-foreground">{{ $description }}</span>
                                    </div>
                                @endforeach
                            @elseif(isset($templateVariables) && is_array($templateVariables))
                                @foreach($templateVariables as $variable => $description)
                                    <div class="flex flex-wrap items-start sm:items-center gap-2 min-w-0">
                                        <code class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-gray-600 dark:text-white font-mono text-xs font-semibold">{{ '{' }}{{ '{' }}{{ $variable }}{{ '}' }}{{ '}' }}</code>
                                        <span class="text-foreground">{{ $description }}</span>
                                    </div>
                                @endforeach
                            @else
                                <span class="text-muted-foreground">Geen variabelen beschikbaar</span>
                            @endif
                        </div>
                        <p class="text-xs text-muted-foreground mt-3">
                            <strong>Tip:</strong> Gebruik deze variabelen in je template met dubbele accolades, bijvoorbeeld: <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-gray-600 dark:text-white font-mono text-xs">{{ '{' }}{{ '{' }}{{ (isset($isInfoRequestType) && $isInfoRequestType) ? 'VOORNAAM' : 'USER_NAME' }}{{ '}' }}{{ '}' }}</code>
                        </p>
                        @if($emailTemplate->type === 'taxi_ride_accepted')
                        <div class="mt-4 p-3 rounded-lg border border-border bg-muted/30 text-xs text-muted-foreground">
                            <p class="mb-2 text-foreground font-medium">Taxi rit geaccepteerd (klant)</p>
                            <p class="mb-0">Gebruik o.a. {{ '{' }}{{ '{' }} CUSTOMER_NAME {{ '}' }}{{ '}' }}, {{ '{' }}{{ '{' }} DRIVER_NAME {{ '}' }}{{ '}' }}, {{ '{' }}{{ '{' }} PICKUP_AT {{ '}' }}{{ '}' }}, {{ '{' }}{{ '{' }} PICKUP_ADDRESS {{ '}' }}{{ '}' }}, {{ '{' }}{{ '{' }} DROPOFF_ADDRESS {{ '}' }}{{ '}' }}, {{ '{' }}{{ '{' }} COMPANY_NAME {{ '}' }}{{ '}' }}, {{ '{' }}{{ '{' }} COMPANY_PHONE {{ '}' }}{{ '}' }}, {{ '{' }}{{ '{' }} COMPANY_LOGO {{ '}' }}{{ '}' }}. Ingeschakeld via <strong>Chauffeur dispatch → Klantmelding bij acceptatie</strong>.</p>
                        </div>
                        @endif
                        @if($emailTemplate->type === 'taxi_customer_login_code')
                        <div class="mt-4 p-3 rounded-lg border border-border bg-muted/30 text-xs text-muted-foreground">
                            <p class="mb-2 text-foreground font-medium">Taxi eenmalige inlogcode (klant)</p>
                            <p class="mb-0">Verstuurd na boeking met &quot;Account aanmaken&quot; of wanneer een klant op de loginpagina een nieuwe code aanvraagt. Belangrijkste variabelen: {{ '{' }}{{ '{' }} LOGIN_CODE {{ '}' }}{{ '}' }} (6 cijfers), {{ '{' }}{{ '{' }} LOGIN_URL {{ '}' }}{{ '}' }}, {{ '{' }}{{ '{' }} CODE_EXPIRES_MINUTES {{ '}' }}{{ '}' }} (ingevuld vanuit <a href="{{ route('admin.taxi.dispatch_settings.edit') }}" class="text-primary underline">Chauffeur dispatch → Mijn Taxi – klant inlogcode</a>), {{ '{' }}{{ '{' }} USER_NAME {{ '}' }}{{ '}' }}, {{ '{' }}{{ '{' }} COMPANY_LOGO {{ '}' }}{{ '}' }} (bij algemeen template: logo van de tenant waarvoor de mail wordt verstuurd). Maak per tenant een eigen versie door een template met uw bedrijf te koppelen; die heeft voorrang op het algemene template.</p>
                        </div>
                        @endif
                        @if($emailTemplate->type === 'invoice')
                        <div class="mt-4 p-3 rounded-lg border border-border bg-muted/30 text-xs text-muted-foreground">
                            <p class="mb-2 text-foreground font-medium">Factuur: <code>{{ '{' }}{{ '{' }} INVOICE_AMOUNTS_HTML {{ '}' }}{{ '}' }}</code></p>
                            <p class="mb-2">Deze tag staat <strong>niet</strong> in de database. Bij versturen wordt ze vervangen door een HTML-tabel met bedragen (excl. BTW, BTW, totaal) op basis van de rit en het BTW-tarief uit <a href="{{ route('admin.invoices.settings') }}" class="text-primary underline">factuurinstellingen</a>.</p>
                            <p class="mb-2">Wil je zelf de opmaak bepalen? Gebruik de knop <strong>&lt;/&gt; Bewerk code</strong> en vervang de tag door eigen HTML met bijvoorbeeld:</p>
                            <p class="font-mono text-[11px] leading-relaxed mb-0">{{ '{' }}{{ '{' }} INVOICE_AMOUNT_EXCL {{ '}' }}{{ '}' }}, {{ '{' }}{{ '{' }} INVOICE_TAX_LABEL {{ '}' }}{{ '}' }}, {{ '{' }}{{ '{' }} INVOICE_TAX_AMOUNT {{ '}' }}{{ '}' }}, {{ '{' }}{{ '{' }} INVOICE_TOTAL {{ '}' }}{{ '}' }}</p>
                        </div>
                        @endif
                        @if(isset($isInfoRequestType) && $isInfoRequestType)
                        <p class="text-xs text-muted-foreground mt-1">
                            <strong>Dynamische velden:</strong> Gebruik <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-gray-600 dark:text-white font-mono text-xs">{{ '{' }}{{ '{' }} DYNAMIC_FORM_FIELDS {{ '}' }}{{ '}' }}</code> in een tabel om alle formuliervelden (inclusief nieuw toegevoegde) automatisch te tonen.
                        </p>
                        @endif
                    </div>
                </div>
            </div>

        <!-- Form Actions -->
        <div class="admin-form-actions flex flex-wrap items-center justify-end gap-2.5 mt-5 w-full min-w-0" style="grid-column: 1 / -1;">
            <a href="{{ route('admin.email-templates.index') }}" class="kt-btn kt-btn-outline">
                Annuleren
            </a>
            <button type="submit" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-check me-2"></i>
                Wijzigingen Opslaan
            </button>
        </div>
        </form>
    </div>
</div>

@include('admin.email-templates.partials.tinymce-html-editor')

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js" crossorigin="anonymous"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle ontvanger: gebruiker uit lijst / vrije e-mail (in Basis Informatie-kaart)
    var mainForm = document.querySelector('form[action*="email-templates"]:not(.send-test-form)');
    if (mainForm) {
        var typeUser = mainForm.querySelector('input.recipient-type-radio[value="user"]');
        var userWrap = mainForm.querySelector('.recipient-user-wrap.main-form-recipient-wrap');
        var emailWrap = mainForm.querySelector('.recipient-email-wrap.main-form-recipient-wrap');
        if (typeUser && userWrap && emailWrap) {
            function toggleMainRecipient() {
                var isUser = typeUser.checked;
                userWrap.style.display = isUser ? '' : 'none';
                emailWrap.style.display = isUser ? 'none' : '';
            }
            mainForm.querySelectorAll('.recipient-type-radio.main-form-recipient').forEach(function(r) {
                r.addEventListener('change', toggleMainRecipient);
            });
            toggleMainRecipient();
        }
    }

    // Formuliervelden volgorde: klik (omhoog/omlaag/verwijderen), drag & drop, en live sync naar Frontend weergave + testform
    var orderList = document.getElementById('form-field-order-list');
    var addSelect = document.getElementById('form-field-order-add');

    function getFormFieldOrder() {
        if (!orderList) return [];
        var ids = [];
        orderList.querySelectorAll('[data-field-id]').forEach(function(el) {
            var id = el.getAttribute('data-field-id');
            if (id) ids.push(id);
        });
        return ids;
    }

    function getOrderRowLabelName(id) {
        if (!orderList) return { label: '', name: '' };
        var row = orderList.querySelector('[data-field-id="' + id + '"]');
        if (!row) return { label: '', name: '' };
        var label = row.querySelector('.font-medium') ? row.querySelector('.font-medium').textContent.trim() : '';
        var code = row.querySelector('code');
        var name = code ? code.textContent.trim() : '';
        return { label: label, name: name };
    }

    function getOrderRowRequired(id) {
        if (!orderList) return false;
        var row = orderList.querySelector('[data-field-id="' + id + '"]');
        if (!row) return false;
        var toggle = row.querySelector('.form-field-required-toggle');
        if (toggle) return toggle.checked;
        return row.getAttribute('data-default-required') === '1';
    }

    function buildOrderRowHtml(id, label, name, isRequired) {
        var varKey = (name || '').toUpperCase().replace(/-/g, '_');
        var varDisplay = '{{ ' + varKey + ' }}';
        var checked = isRequired ? ' checked' : '';
        return '<span class="form-field-drag-handle shrink-0 cursor-grab active:cursor-grabbing touch-none p-1 -ml-1 rounded text-muted-foreground hover:text-foreground select-none leading-none" title="Sleep om volgorde te wijzigen" aria-label="Volgorde wijzigen" role="button" tabindex="0">⋮⋮</span>' +
            '<div class="form-field-order-row-meta min-w-0 flex-1 flex flex-wrap items-center gap-x-2 gap-y-0">' +
            '<span class="font-medium text-foreground break-words leading-tight">' + (label || '') + '</span>' +
            '<code class="form-field-order-slug text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-white px-2 py-0.5 rounded shrink-0 leading-none">' + (name || '') + '</code>' +
            '<span class="form-field-order-var text-xs text-muted-foreground break-all leading-tight">→ <code>' + varDisplay + '</code></span>' +
            '</div>' +
            '<label class="form-field-order-required flex items-center gap-2 shrink-0 text-xs text-muted-foreground whitespace-nowrap cursor-pointer" title="Verplicht veld in dit template">' +
            '<span>Verplicht</span>' +
            '<input type="checkbox" class="kt-switch kt-switch-sm shrink-0 form-field-required-toggle" name="form_field_required[' + id + ']" value="1"' + checked + '>' +
            '</label>' +
            '<div class="form-field-order-row-actions flex items-center gap-1 shrink-0">' +
            '<button type="button" class="form-field-order-up kt-btn kt-btn-sm kt-btn-ghost" title="Omhoog"><i class="ki-filled ki-arrow-up"></i></button>' +
            '<button type="button" class="form-field-order-down kt-btn kt-btn-sm kt-btn-ghost" title="Omlaag"><i class="ki-filled ki-arrow-down"></i></button>' +
            '<button type="button" class="form-field-order-remove kt-btn kt-btn-sm kt-btn-ghost text-destructive" title="Verwijderen"><i class="ki-filled ki-trash"></i></button>' +
            '</div>' +
            '<input type="hidden" name="form_field_order[]" value="' + id + '">';
    }

    function syncFormPreviewOrder() {
        var orderedIds = getFormFieldOrder();
        var previewContainer = document.getElementById('frontend-preview-fields');
        var testTbody = document.getElementById('send-test-form-fields-tbody');
        var inputClass = 'w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-500 dark:focus:border-blue-500 opacity-75';

        if (previewContainer) {
            var previewRows = {};
            previewContainer.querySelectorAll('.form-preview-field-row[data-field-id]').forEach(function(el) {
                previewRows[el.getAttribute('data-field-id')] = el;
            });
            function makeRowEl(id) {
                var el = previewRows[id];
                var info = getOrderRowLabelName(id);
                var requiredSuffix = getOrderRowRequired(id) ? ' *' : '';
                if (el) {
                    var labelEl = el.querySelector('label');
                    if (labelEl) labelEl.textContent = (info.label || '') + requiredSuffix;
                    return el;
                }
                var name = (info.name || '').toLowerCase();
                var isOmschrijving = name.indexOf('omschrijving') !== -1;
                var isEmail = name.indexOf('email') !== -1;
                var inputHtml = isOmschrijving
                    ? '<textarea rows="5" disabled readonly class="' + inputClass + '"></textarea>'
                    : '<input type="' + (isEmail ? 'email' : 'text') + '" disabled readonly class="' + inputClass + '" value="">';
                el = document.createElement('div');
                el.className = 'form-preview-field-row';
                el.setAttribute('data-field-id', id);
                el.innerHTML = '<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">' + (info.label || '') + requiredSuffix + '</label>' + inputHtml;
                previewRows[id] = el;
                return el;
            }
            while (previewContainer.firstChild) previewContainer.removeChild(previewContainer.firstChild);
            if (orderedIds.length >= 2) {
                var gridDiv = document.createElement('div');
                gridDiv.className = 'grid gap-4 sm:grid-cols-2';
                gridDiv.appendChild(makeRowEl(orderedIds[0]));
                gridDiv.appendChild(makeRowEl(orderedIds[1]));
                previewContainer.appendChild(gridDiv);
                for (var i = 2; i < orderedIds.length; i++) previewContainer.appendChild(makeRowEl(orderedIds[i]));
            } else {
                orderedIds.forEach(function(id) { previewContainer.appendChild(makeRowEl(id)); });
            }
        }

        if (testTbody) {
            var submitRow = testTbody.querySelector('.send-test-form-submit-row');
            var testRows = {};
            testTbody.querySelectorAll('tr[data-field-id]').forEach(function(el) {
                testRows[el.getAttribute('data-field-id')] = el;
            });
            orderedIds.forEach(function(id) {
                var el = testRows[id];
                var info = getOrderRowLabelName(id);
                if (!el) {
                    el = document.createElement('tr');
                    el.setAttribute('data-field-id', id);
                    el.innerHTML = '<td class="text-secondary-foreground font-normal w-px whitespace-nowrap pr-4">' + (info.label || '') + (getOrderRowRequired(id) ? ' *' : '') + '</td><td><input type="text" class="kt-input max-w-md" name="test_' + (info.name || '') + '" value=""></td>';
                    testRows[id] = el;
                } else {
                    var td = el.querySelector('td');
                    if (td) td.textContent = (info.label || '') + (getOrderRowRequired(id) ? ' *' : '');
                }
                testTbody.appendChild(el);
            });
            if (submitRow) testTbody.appendChild(submitRow);
        }
    }

    function removeFieldFromPreviews(fieldId) {
        document.querySelectorAll('#frontend-preview-fields .form-preview-field-row[data-field-id="' + fieldId + '"]').forEach(function(el) { el.remove(); });
        document.querySelectorAll('#send-test-form-fields-tbody tr[data-field-id="' + fieldId + '"]').forEach(function(el) { el.remove(); });
    }

    if (orderList) {
        orderList.addEventListener('change', function(e) {
            if (e.target.classList.contains('form-field-required-toggle')) {
                syncFormPreviewOrder();
            }
        });

        orderList.addEventListener('click', function(e) {
            if (e.target.closest('.form-field-order-required')) {
                setTimeout(syncFormPreviewOrder, 0);
                return;
            }
            var row = e.target.closest('[data-field-id]');
            if (!row) return;
            if (e.target.closest('.form-field-order-remove')) {
                var fieldId = row.getAttribute('data-field-id');
                if (addSelect) {
                    var codes = row.querySelectorAll('code');
                    var slug = codes[0] ? codes[0].textContent.trim() : '';
                    var label = row.querySelector('.font-medium') ? row.querySelector('.font-medium').textContent.trim() : '';
                    var opt = document.createElement('option');
                    opt.value = fieldId;
                    opt.setAttribute('data-label', label);
                    opt.setAttribute('data-name', slug);
                    opt.setAttribute('data-required', row.getAttribute('data-default-required') || '0');
                    opt.textContent = label + ' (' + slug + ')';
                    addSelect.appendChild(opt);
                }
                removeFieldFromPreviews(fieldId);
                row.remove();
            } else if (e.target.closest('.form-field-order-up')) {
                var prev = row.previousElementSibling;
                if (prev) orderList.insertBefore(row, prev);
                syncFormPreviewOrder();
            } else if (e.target.closest('.form-field-order-down')) {
                var next = row.nextElementSibling;
                if (next) orderList.insertBefore(next, row);
                syncFormPreviewOrder();
            }
        });

        // Sleep (drag & drop) met Sortable.js, zelfde techniek als home-sections-sortable
        if (typeof Sortable !== 'undefined') {
            new Sortable(orderList, {
                handle: '.form-field-drag-handle',
                animation: 150,
                ghostClass: 'opacity-50',
                dragClass: 'cursor-grabbing',
                onEnd: function() { syncFormPreviewOrder(); }
            });
        }

        syncFormPreviewOrder();
    }

    if (orderList && addSelect) {
        addSelect.addEventListener('change', function() {
            var opt = this.options[this.selectedIndex];
            if (!opt || !opt.value) return;
            var id = opt.value;
            var label = opt.getAttribute('data-label') || opt.textContent;
            var name = opt.getAttribute('data-name') || '';
            var isRequired = opt.getAttribute('data-required') === '1';
            var row = document.createElement('div');
            row.className = 'form-field-order-row flex flex-wrap items-center gap-2 py-2 px-3 rounded-lg border border-border bg-muted/20 min-w-0';
            row.setAttribute('data-field-id', id);
            row.setAttribute('data-default-required', isRequired ? '1' : '0');
            row.innerHTML = buildOrderRowHtml(id, label, name, isRequired);
            orderList.appendChild(row);
            opt.remove();
            this.selectedIndex = 0;
            syncFormPreviewOrder();
        });
    }
});
</script>
@endpush

@push('scripts')
<script src="{{ asset('assets/js/form-validation.js') }}"></script>
@endpush

@endsection
