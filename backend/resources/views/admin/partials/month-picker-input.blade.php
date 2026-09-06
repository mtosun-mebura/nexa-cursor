@php
    $periodValue = $value ?? '';
    $parsedPeriod = parse_admin_month($periodValue);
    $isoDate = $parsedPeriod ? $parsedPeriod.'-01' : null;
    $selectedMonth = $parsedPeriod ? ((int) substr($parsedPeriod, 5, 2)) - 1 : null;
    $selectedYear = $parsedPeriod ? (int) substr($parsedPeriod, 0, 4) : null;
@endphp
<div class="kt-input admin-month-picker {{ $wrapperClass ?? 'w-full sm:w-44' }}">
    <input class="grow"
           name="{{ $name ?? 'period' }}"
           @isset($id) id="{{ $id }}" @endisset
           value="{{ admin_month_picker_display($periodValue) }}"
           data-kt-date-picker="true"
           data-kt-date-picker-type="month"
           data-kt-date-picker-input-mode="true"
           data-kt-date-picker-position-to-input="left"
           data-kt-date-picker-date-format="MM-YYYY"
           @if($isoDate)
           data-kt-date-picker-selected-dates='["{{ $isoDate }}"]'
           data-kt-date-picker-selected-month="{{ $selectedMonth }}"
           data-kt-date-picker-selected-year="{{ $selectedYear }}"
           @endif
           placeholder="{{ $placeholder ?? 'Selecteer maand' }}"
           readonly
           type="text"
           @if(!empty($required)) required @endif />
    <i class="ki-filled ki-calendar shrink-0" aria-hidden="true"></i>
</div>
