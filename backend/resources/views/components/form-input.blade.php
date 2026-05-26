@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'required' => false,
    'placeholder' => '',
    'help' => null,
    'pattern' => null,
    'minlength' => null,
    'maxlength' => null,
    'autocomplete' => null,
    'readonly' => false,
    'disabled' => false,
])

@php
    $oldValue = old($name, $value ?? '');
    $hasError = $errors->has($name);
    $errorMessage = $errors->first($name);
    
    // Bepaal input classes
    $inputClasses = 'kt-input';
    if ($hasError) {
        $inputClasses .= ' border-destructive border-red-500';
    }
    
    // Bepaal label classes
    $labelClasses = 'kt-form-label';
    if ($required) {
        $labelClasses .= ' required';
    }
@endphp

<div class="form-field-wrapper">
    @if($label)
        <label for="{{ $name }}" class="{{ $labelClasses }}">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif
    
    <div class="input-wrapper">
        <input 
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $name }}"
            value="{{ $oldValue }}"
            @if($required) required @endif
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            @if($pattern) pattern="{{ $pattern }}" @endif
            @if($minlength) minlength="{{ $minlength }}" @endif
            @if($maxlength) maxlength="{{ $maxlength }}" @endif
            @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            @if($readonly) readonly @endif
            @if($disabled) disabled @endif
            class="{{ $inputClasses }}"
            {{ $attributes }}
        />
        
        @if($hasError)
            <div class="text-xs text-red-600 text-destructive mt-1 field-error" data-field="{{ $name }}">
                {{ $errorMessage }}
            </div>
        @endif
        
        @if($help && !$hasError)
            <div class="text-xs text-muted-foreground mt-1">
                {{ $help }}
            </div>
        @endif
        
        {{-- Feedback element voor live validatie --}}
        <div class="field-feedback text-xs mt-1 hidden" data-field="{{ $name }}"></div>
    </div>
</div>





