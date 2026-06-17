<div class="kt-input {{ $wrapperClass ?? 'w-64' }}">
    <i class="ki-outline ki-calendar"></i>
    <input class="grow"
           name="{{ $name }}"
           @isset($id) id="{{ $id }}" @endisset
           value="{{ admin_date_picker_display($value ?? '') }}"
           data-kt-date-picker="true"
           data-kt-date-picker-input-mode="true"
           data-kt-date-picker-position-to-input="left"
           data-kt-date-picker-date-format="DD-MM-YYYY"
           placeholder="{{ $placeholder ?? 'Selecteer datum' }}"
           readonly
           type="text"
           @if(!empty($required)) required @endif />
</div>
