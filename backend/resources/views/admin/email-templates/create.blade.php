@extends('admin.layouts.app')

@section('title', 'Nieuw E-mail Template')

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                Nieuw E-mail Template
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

    <form action="{{ route('admin.email-templates.store') }}" method="POST" data-validate="true" novalidate>
        @csrf
        
        <div class="grid gap-5 lg:gap-7.5">
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
                                        <option value="{{ $company->id }}" {{ old('company_id', session('selected_tenant')) == $company->id ? 'selected' : '' }}>
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
                                       value="{{ old('name') }}" 
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
                                       value="{{ old('subject') }}" 
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
                                        <option value="{{ $typeKey }}" {{ old('type') == $typeKey ? 'selected' : '' }}>{{ $typeLabels[$typeKey] ?? $typeKey }}</option>
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
                                    <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Actief</option>
                                    <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactief</option>
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
                                          rows="4">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        @include('admin.email-templates.partials.recipient-fields', ['users' => $users])
                    </table>
                </div>
            </div>

            <!-- HTML Content Card (bij type Informatieaanvraag:zelfde opmaak als formulier op de website) -->
            <div class="kt-card" id="html-content-card">
                <div class="kt-card-header">
                    <h5 class="kt-card-title">HTML Inhoud *</h5>
                </div>
                <div class="kt-card-content">
                    <div class="rounded-xl border border-border bg-muted/30 p-5 mb-5" id="formulier-preview-wrap">
                        <p class="text-sm font-medium text-foreground mb-3">Bij type <strong>Informatieaanvraag</strong> ziet het formulier op de website er zo uit (zelfde opmaak als frontend):</p>
                        @include('admin.email-templates.partials.formulier-preview', ['formFields' => $formFields ?? collect()])
                    </div>
                    <div class="mb-3">
                        <label for="html_content" class="kt-form-label mb-2">HTML Inhoud</label>
                        <textarea class="kt-input @error('html_content') border-destructive @enderror"
                                  id="html_content"
                                  name="html_content"
                                  rows="10"
                                  data-skip-validation-wrapper
                                  required>{{ old('html_content', $defaultHtmlTemplate ?? '') }}</textarea>
                        @error('html_content')
                            <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="text-xs text-muted-foreground mb-2">
                        <strong>Tip:</strong> Gebruik de knoppen voor opmaak of wissel naar "Bewerk code" (knop &lt;/&gt;) om HTML en variabelen te bewerken.
                    </div>
                    <div class="text-xs text-muted-foreground">
                        <p class="mb-2"><strong>Beschikbare variabelen (algemeen):</strong></p>
                        <div class="space-y-1.5">
                            @if(isset($templateVariables) && is_array($templateVariables))
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
                        @if(isset($infoRequestVariables) && is_array($infoRequestVariables))
                            <p class="mb-2 mt-4 font-semibold text-foreground">Bij type Informatieaanvraag (formulier op de website):</p>
                            <div class="space-y-1.5 mb-2">
                                @foreach($infoRequestVariables as $variable => $description)
                                    <div class="flex items-center gap-2">
                                        <code class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-gray-600 dark:text-white font-mono text-xs font-semibold">{{ '{' }}{{ '{' }}{{ $variable }}{{ '}' }}{{ '}' }}</code>
                                        <span class="text-foreground">{{ $description }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <p class="text-xs text-muted-foreground mt-3">
                            <strong>Tip:</strong> Gebruik variabelen met dubbele accolades, bijvoorbeeld: <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-gray-600 dark:text-white font-mono text-xs">{{ '{' }}{{ '{' }}VOORNAAM{{ '}' }}{{ '}' }}</code>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Test e-mail versturen (optioneel) -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <h5 class="kt-card-title">Optioneel: testmail versturen na opslaan</h5>
                </div>
                <div class="kt-card-content">
                    <p class="text-sm text-muted-foreground mb-4">
                        De testmail wordt verstuurd naar het ontvangeradres dat bij <strong>Basis Informatie</strong> is ingesteld. De velden komen uit <a href="{{ route('admin.email-templates.form-fields.index') }}" class="text-primary underline">Formulier velden</a> (bij type Informatieaanvraag). Vul eventueel in en vink aan om direct na het opslaan een testmail te versturen.
                    </p>
                    @php
                        $testDummy = [
                            'voornaam' => 'Jan',
                            'achternaam' => 'Jansen',
                            'email_aanvraag' => 'jan@jansen.nl',
                            'emailadres' => 'jan@jansen.nl',
                            'telefoonnummer' => '0612345678',
                            'omschrijving' => 'Dit is een testaanvraag om de e-mailtemplate te controleren. De velden zijn vooraf ingevuld met dummy data.',
                        ];
                        $defaultDummy = 'Testwaarde';
                    @endphp
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        @foreach($formFields ?? [] as $field)
                            <tr>
                                <td class="text-secondary-foreground font-normal">{{ $field->label }}{{ $field->is_required ? ' *' : '' }}</td>
                                <td>
                                    @if(in_array($field->validation_rule, [null, ''], true) && str_contains(strtolower($field->label), 'omschrijving'))
                                        <textarea class="kt-input @error('test_' . $field->name) border-destructive @enderror"
                                                  name="test_{{ $field->name }}" rows="4">{{ old('test_' . $field->name, $testDummy[$field->name] ?? $defaultDummy) }}</textarea>
                                    @else
                                        <input type="{{ $field->validation_rule === 'email' ? 'email' : 'text' }}"
                                               class="kt-input @error('test_' . $field->name) border-destructive @enderror"
                                               name="test_{{ $field->name }}"
                                               value="{{ old('test_' . $field->name, $testDummy[$field->name] ?? $defaultDummy) }}">
                                    @endif
                                    @error('test_' . $field->name)
                                        <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                    @enderror
                                </td>
                            </tr>
                        @endforeach
                        <tr>
                            <td class="text-secondary-foreground font-normal"></td>
                            <td>
                                <label class="kt-label flex items-center gap-2">
                                    <input type="checkbox" name="test_send" value="1" {{ old('test_send') ? 'checked' : '' }}>
                                    Verstuur testmail na opslaan
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-end gap-2.5 mt-5">
            <a href="{{ route('admin.email-templates.index') }}" class="kt-btn kt-btn-outline">
                Annuleren
            </a>
            <button type="submit" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-check me-2"></i>
                Template Opslaan
            </button>
        </div>
    </form>
</div>

@include('admin.email-templates.partials.tinymce-html-editor')

@push('scripts')
<script src="{{ asset('assets/js/form-validation.js') }}"></script>
<script>
window.defaultTemplateInformatieaanvraag = @json($defaultHtmlTemplateInformatieaanvraag ?? '');
document.addEventListener('DOMContentLoaded', function() {
    // Toggle ontvanger in Basis Informatie (main form)
    var mainForm = document.querySelector('form[action*="email-templates/store"]');
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

    var typeSelect = document.getElementById('type');
    if (typeSelect && window.defaultTemplateInformatieaanvraag) {
        function applyInformatieaanvraagTemplate() {
            if (typeSelect.value === 'informatieaanvraag') {
                var html = window.defaultTemplateInformatieaanvraag;
                var textarea = document.getElementById('html_content');
                if (textarea) {
                    textarea.value = html;
                    if (typeof tinymce !== 'undefined') {
                        var ed = tinymce.get('html_content');
                        if (ed) ed.setContent(html);
                    }
                }
            }
        }
        typeSelect.addEventListener('change', applyInformatieaanvraagTemplate);
    }
});
</script>
@endpush

@endsection
