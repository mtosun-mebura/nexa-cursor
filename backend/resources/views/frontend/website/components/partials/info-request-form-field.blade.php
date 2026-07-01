@php
    $fieldName = $name ?? $field->name;
    $fieldLabel = $label ?? $field->label;
    if (! isset($required)) {
        $required = isset($emailTemplate, $field)
            ? $emailTemplate->isFormFieldRequired($field)
            : (bool) ($field->is_required ?? false);
    } else {
        $required = (bool) $required;
    }
    $validationRule = $validationRule ?? ($field->validation_rule ?? null);
    $isTextarea = (bool) ($isTextarea ?? (in_array($validationRule, [null, ''], true) && str_contains(strtolower((string) $fieldLabel), 'omschrijving')));
    if ($isTextarea) {
        $validationRule = 'textarea';
    }
    $textareaMaxLength = \App\Models\InfoRequestFormField::TEXTAREA_MAX_LENGTH;
    $inputId = $inputId ?? ('email-template-' . $fieldName . '-' . $sectionKey);
    $oldValue = old($fieldName);
@endphp
<div class="info-request-field"
     data-field-name="{{ $fieldName }}"
     data-field-label="{{ $fieldLabel }}"
     data-required="{{ $required ? '1' : '0' }}"
     data-validation-rule="{{ $validationRule ?: 'text' }}">
    <label for="{{ $inputId }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $fieldLabel }}{{ $required ? ' *' : '' }}</label>
    <div class="info-request-input-wrap relative {{ $isTextarea ? 'info-request-input-wrap--textarea' : '' }}">
        @if($isTextarea)
            <textarea id="{{ $inputId }}" name="{{ $fieldName }}" rows="5" maxlength="{{ $textareaMaxLength }}" {{ $required ? 'required' : '' }} class="{{ $inputClass }} info-request-input info-request-textarea pr-10 @error($fieldName) border-red-500 dark:border-red-500 @enderror" data-info-request-input data-max-length="{{ $textareaMaxLength }}">{{ $oldValue }}</textarea>
        @else
            <input type="{{ $validationRule === 'email' ? 'email' : 'text' }}"
                   id="{{ $inputId }}"
                   name="{{ $fieldName }}"
                   value="{{ $oldValue }}"
                   {{ $required ? 'required' : '' }}
                   class="{{ $inputClass }} info-request-input pr-10 @error($fieldName) border-red-500 dark:border-red-500 @enderror"
                   data-info-request-input
                   autocomplete="{{ $validationRule === 'email' ? 'email' : ($validationRule === 'tel' ? 'tel' : 'off') }}">
        @endif
        <span class="info-request-field-status pointer-events-none absolute right-3 hidden" aria-hidden="true">
            <svg class="info-request-icon-valid hidden h-5 w-5 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"></path>
            </svg>
            <svg class="info-request-icon-invalid hidden h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"></path>
            </svg>
        </span>
    </div>
    @if($isTextarea)
        <p class="info-request-char-count mt-1 text-right text-[0.6875rem] leading-none text-gray-500 dark:text-gray-400 tabular-nums" aria-live="polite">{{ $textareaMaxLength }} tekens over · max. {{ $textareaMaxLength }}</p>
    @endif
    <span class="form-field-error block text-sm text-red-600 dark:text-red-400 mt-1" data-field="{{ $fieldName }}" role="alert">@error($fieldName){{ $message }}@enderror</span>
</div>
