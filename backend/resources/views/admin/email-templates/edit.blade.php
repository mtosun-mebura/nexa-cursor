@extends('admin.layouts.app')

@section('title', 'E-mail Template Bewerken')

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                E-mail Template Bewerken
            </h1>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.email-templates.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
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
        <form action="{{ route('admin.email-templates.update', $emailTemplate) }}" method="POST" style="display: contents;">
            @csrf
            @method('PUT')
            @if(auth()->user()->hasRole('super-admin'))
            <!-- Bedrijf Selectie (alleen voor Super Admin) -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <h5 class="kt-card-title">Bedrijf</h5>
                </div>
                <div class="kt-card-content">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
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
            @endif
            
            <!-- Basic Information Card -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <h5 class="kt-card-title">Basis Informatie</h5>
                </div>
                <div class="kt-card-content">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
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
                            <td>
                                <input type="text" 
                                       class="kt-input @error('subject') border-destructive @enderror" 
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

            @include('admin.email-templates.partials.form-field-order', ['formFields' => $formFields ?? collect(), 'allFormFieldsPool' => $allFormFieldsPool ?? collect(), 'isInfoRequestType' => $isInfoRequestType ?? false])

            @if(isset($isInfoRequestType) && $isInfoRequestType)
            <!-- Frontend weergave (formulierpreview, volgorde synct met Formuliervelden) -->
            <div class="kt-card" id="frontend-weergave-card">
                <div class="kt-card-header">
                    <h5 class="kt-card-title">Frontend weergave</h5>
                </div>
                <div class="kt-card-content">
                    <p class="text-sm text-muted-foreground mb-3">Zo ziet het formulier er op de website uit. De volgorde wijzigt direct wanneer je velden hierboven herschikt.</p>
                    @include('admin.email-templates.partials.formulier-preview', ['formFields' => $formFields ?? []])
                </div>
            </div>
            @endif

            <!-- HTML Content Card (zelfde breedte als overige secties) -->
            <div class="kt-card" id="html-content-card">
                <div class="kt-card-header">
                    <h5 class="kt-card-title">HTML Inhoud *</h5>
                </div>
                <div class="kt-card-content">
                    <div class="mb-3">
                        <label for="html_content" class="kt-form-label mb-2">HTML Inhoud</label>
                        <textarea class="kt-input @error('html_content') border-destructive @enderror" 
                                  id="html_content" 
                                  name="html_content" 
                                  rows="10" 
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
                                    <div class="flex items-center gap-2">
                                        <code class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-gray-600 dark:text-white font-mono text-xs font-semibold">{{ '{' }}{{ '{' }}{{ $variable }}{{ '}' }}{{ '}' }}</code>
                                        <span class="text-foreground">{{ $description }}</span>
                                    </div>
                                @endforeach
                            @elseif(isset($templateVariables) && is_array($templateVariables))
                                @foreach($templateVariables as $variable => $description)
                                    <div class="flex items-center gap-2">
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
                        @if(isset($isInfoRequestType) && $isInfoRequestType)
                        <p class="text-xs text-muted-foreground mt-1">
                            <strong>Dynamische velden:</strong> Gebruik <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-gray-600 dark:text-white font-mono text-xs">{{ '{' }}{{ '{' }} DYNAMIC_FORM_FIELDS {{ '}' }}{{ '}' }}</code> in een tabel om alle formuliervelden (inclusief nieuw toegevoegde) automatisch te tonen.
                        </p>
                        @endif
                    </div>
                </div>
            </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-end gap-2.5 mt-5" style="grid-column: 1 / -1;">
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
                if (el) return el;
                var info = getOrderRowLabelName(id);
                var name = (info.name || '').toLowerCase();
                var isOmschrijving = name.indexOf('omschrijving') !== -1;
                var isEmail = name.indexOf('email') !== -1;
                var inputHtml = isOmschrijving
                    ? '<textarea rows="5" disabled readonly class="' + inputClass + '"></textarea>'
                    : '<input type="' + (isEmail ? 'email' : 'text') + '" disabled readonly class="' + inputClass + '" value="">';
                el = document.createElement('div');
                el.className = 'form-preview-field-row';
                el.setAttribute('data-field-id', id);
                el.innerHTML = '<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">' + (info.label || '') + ' *</label>' + inputHtml;
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
                if (!el) {
                    var info = getOrderRowLabelName(id);
                    el = document.createElement('tr');
                    el.setAttribute('data-field-id', id);
                    el.innerHTML = '<td class="text-secondary-foreground font-normal w-px whitespace-nowrap pr-4">' + (info.label || '') + ' *</td><td><input type="text" class="kt-input max-w-md" name="test_' + (info.name || '') + '" value=""></td>';
                    testRows[id] = el;
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
        orderList.addEventListener('click', function(e) {
            e.preventDefault();
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
    }

    if (orderList && addSelect) {
        addSelect.addEventListener('change', function() {
            var opt = this.options[this.selectedIndex];
            if (!opt || !opt.value) return;
            var id = opt.value;
            var label = opt.getAttribute('data-label') || opt.textContent;
            var name = opt.getAttribute('data-name') || '';
            var varKey = name.toUpperCase().replace(/-/g, '_');
            var row = document.createElement('div');
            row.className = 'flex items-center gap-2 py-2 px-3 rounded-lg border border-border bg-muted/20';
            row.setAttribute('data-field-id', id);
            var varDisplay = '{{ ' + varKey + ' }}';
            row.innerHTML = '<span class="form-field-drag-handle cursor-grab active:cursor-grabbing touch-none p-1 -ml-1 rounded text-muted-foreground hover:text-foreground select-none" title="Sleep om volgorde te wijzigen" aria-label="Volgorde wijzigen" role="button">⋮⋮</span>' +
                '<span class="font-medium text-foreground">' + (label || '') + '</span>' +
                '<code class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-white px-2 py-0.5 rounded">' + (name || '') + '</code>' +
                '<span class="text-xs text-muted-foreground">→ <code>' + varDisplay + '</code></span>' +
                '<div class="ml-auto flex items-center gap-1">' +
                '<button type="button" class="form-field-order-up kt-btn kt-btn-sm kt-btn-ghost" title="Omhoog"><i class="ki-filled ki-arrow-up"></i></button>' +
                '<button type="button" class="form-field-order-down kt-btn kt-btn-sm kt-btn-ghost" title="Omlaag"><i class="ki-filled ki-arrow-down"></i></button>' +
                '<button type="button" class="form-field-order-remove kt-btn kt-btn-sm kt-btn-ghost text-destructive" title="Verwijderen"><i class="ki-filled ki-trash"></i></button>' +
                '</div>' +
                '<input type="hidden" name="form_field_order[]" value="' + id + '">';
            orderList.appendChild(row);
            opt.remove();
            this.selectedIndex = 0;
            syncFormPreviewOrder();
        });
    }
});
</script>
@endpush

@endsection
