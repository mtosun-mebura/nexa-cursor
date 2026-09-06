{{-- Test e-mail versturen: gebruikt het opgeslagen ontvangeradres uit Basis Informatie. Velden komen uit Formulier velden (bij type Informatieaanvraag). --}}
@include('admin.partials.ajax-action-button-styles')
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
            De e-mail wordt verstuurd via de <strong>NEXA Suite-mailserver</strong> naar het ontvangeradres dat bij <strong>Basis Informatie</strong> is ingesteld.
            De testmail is herkenbaar als voorbeeld: het onderwerp begint met <strong>[Voorbeeld]</strong> en bovenaan staat een gele vermelding dat dit geen echte e-mail is.
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
                        <td colspan="2" class="pt-4">
                            <div class="flex justify-end">
                                <button type="submit" class="kt-btn kt-btn-primary admin-ajax-action-btn send-test-form-submit inline-flex items-center justify-center gap-2">
                                    @include('admin.partials.ajax-action-spinner')
                                    <span class="admin-ajax-action-idle-icon send-test-form-submit-icon inline-flex items-center" aria-hidden="true">
                                        <i class="ki-filled ki-send"></i>
                                    </span>
                                    <span class="send-test-form-submit-label">Verstuur testmail</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
    </div>
</div>
@push('scripts')
<script>
(function () {
    var form = document.querySelector('.send-test-form');
    if (!form) {
        return;
    }
    var btn = form.querySelector('.send-test-form-submit');
    if (!btn) {
        return;
    }
    var iconEl = btn.querySelector('.send-test-form-submit-icon');
    var labelEl = btn.querySelector('.send-test-form-submit-label');
    var idleIcon = iconEl ? iconEl.innerHTML : '';
    var idleLabel = labelEl ? labelEl.textContent : 'Verstuur testmail';
    var resetTimer = null;

    function setButtonState(state) {
        btn.classList.remove('is-loading', 'is-success', 'is-error');
        btn.disabled = state === 'loading';
        if (state === 'loading') {
            btn.classList.add('is-loading');
            btn.setAttribute('aria-busy', 'true');
            if (iconEl) {
                iconEl.innerHTML = idleIcon;
            }
            if (labelEl) {
                labelEl.textContent = 'Versturen…';
            }
            return;
        }
        btn.removeAttribute('aria-busy');
        if (state === 'success') {
            btn.classList.add('is-success');
            if (iconEl) {
                iconEl.innerHTML = '<i class="ki-filled ki-check"></i>';
            }
            if (labelEl) {
                labelEl.textContent = 'Verstuurd';
            }
            return;
        }
        if (state === 'error') {
            btn.classList.add('is-error');
            if (iconEl) {
                iconEl.innerHTML = '<i class="ki-filled ki-cross"></i>';
            }
            if (labelEl) {
                labelEl.textContent = idleLabel;
            }
            return;
        }
        if (iconEl) {
            iconEl.innerHTML = idleIcon;
        }
        if (labelEl) {
            labelEl.textContent = idleLabel;
        }
    }

    form.addEventListener('submit', function (ev) {
        ev.preventDefault();
        if (btn.disabled) {
            return;
        }
        if (resetTimer) {
            window.clearTimeout(resetTimer);
            resetTimer = null;
        }
        setButtonState('loading');
        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        }).then(function (res) {
            return res.json().then(function (data) {
                return { ok: res.ok, data: data || {} };
            }).catch(function () {
                return { ok: res.ok, data: {} };
            });
        }).then(function (result) {
            var success = result.ok && result.data.success !== false;
            setButtonState(success ? 'success' : 'error');
            resetTimer = window.setTimeout(function () {
                setButtonState('idle');
            }, 4000);
        }).catch(function () {
            setButtonState('error');
            resetTimer = window.setTimeout(function () {
                setButtonState('idle');
            }, 4000);
        });
    });
})();
</script>
@endpush
