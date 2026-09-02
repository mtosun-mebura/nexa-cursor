@php
    $display = admin_datetime_picker_display($value ?? '');
    $parsed = parse_admin_datetime($value ?? '');
    $carbon = $parsed ? \Carbon\Carbon::parse($parsed) : null;
@endphp
<label class="kt-input {{ $wrapperClass ?? 'w-full' }} cursor-pointer" data-admin-date-picker-click-bound="1">
    <i class="ki-outline ki-calendar"></i>
    <input class="grow"
           name="{{ $name }}"
           @isset($id) id="{{ $id }}" @endisset
           value="{{ $display }}"
           data-kt-date-picker="true"
           data-kt-date-picker-input-mode="true"
           data-kt-date-picker-position-to-input="left"
           data-kt-date-picker-date-format="DD-MM-YYYY"
           data-kt-date-picker-selection-time-mode="24"
           @if($carbon)
           data-kt-date-picker-selected-dates='["{{ $carbon->format('Y-m-d') }}"]'
           data-kt-date-picker-selected-month="{{ $carbon->format('n') - 1 }}"
           data-kt-date-picker-selected-year="{{ $carbon->format('Y') }}"
           @endif
           data-kt-date-picker-selected-time="{{ $carbon ? $carbon->format('H:i') : '09:00' }}"
           placeholder="{{ $placeholder ?? 'Selecteer datum en tijd' }}"
           readonly
           type="text"
           @if(!empty($required)) required @endif />
</label>
