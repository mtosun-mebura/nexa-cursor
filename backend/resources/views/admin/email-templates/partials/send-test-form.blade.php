{{-- Test e-mail versturen: gebruikt het opgeslagen ontvangeradres uit Basis Informatie. Velden komen uit Formulier velden (bij type Informatieaanvraag). --}}
@php
    $formFields = $formFields ?? collect();
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
<div class="kt-card">
    <div class="kt-card-header">
        <h5 class="kt-card-title">Test e-mail versturen</h5>
    </div>
    <div class="kt-card-content">
        <p class="text-sm text-muted-foreground mb-4">
            De e-mail wordt verstuurd naar het ontvangeradres dat bij <strong>Basis Informatie</strong> is ingesteld.
            @if($formFields->isNotEmpty())
                Onderstaande velden komen uit <a href="{{ route('admin.email-templates.form-fields.index') }}" class="text-primary underline">Formulier velden</a>; de waarden worden in de template gebruikt (zelfde variabelen als op de website).
            @else
                Bij type <strong>Informatieaanvraag</strong> kunt u onder <a href="{{ route('admin.email-templates.form-fields.index') }}" class="text-primary underline">Formulier velden</a> velden toevoegen; die verschijnen hier en in de variabelenlijst.
            @endif
        </p>
        <form action="{{ route('admin.email-templates.send-test', $emailTemplate) }}" method="POST" class="send-test-form">
            @csrf
            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                <tbody id="send-test-form-fields-tbody">
                    @foreach($formFields as $field)
                        <tr data-field-id="{{ $field->id }}">
                            <td class="text-secondary-foreground font-normal w-px whitespace-nowrap pr-4">{{ $field->label }}{{ $emailTemplate->isFormFieldRequired($field) ? ' *' : '' }}</td>
                            <td>
                                @if(in_array($field->validation_rule, [null, ''], true) && str_contains(strtolower($field->label), 'omschrijving'))
                                    <textarea class="kt-input max-w-md @error('test_' . $field->name) border-destructive @enderror"
                                              name="test_{{ $field->name }}" rows="4">{{ old('test_' . $field->name, $testDummy[$field->name] ?? $defaultDummy) }}</textarea>
                                @else
                                    <input type="{{ $field->validation_rule === 'email' ? 'email' : 'text' }}"
                                           class="kt-input max-w-md @error('test_' . $field->name) border-destructive @enderror"
                                           name="test_{{ $field->name }}"
                                           value="{{ old('test_' . $field->name, $testDummy[$field->name] ?? $defaultDummy) }}">
                                @endif
                                @error('test_' . $field->name)
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    @endforeach
                    <tr class="send-test-form-submit-row">
                        <td class="w-px pr-4"></td>
                        <td>
                            <button type="submit" class="kt-btn kt-btn-primary">
                                <i class="ki-filled ki-send me-2"></i>
                                Verstuur testmail
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
    </div>
</div>
