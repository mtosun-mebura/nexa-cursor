@php
    $timeValue = preg_match('/^\d{2}:\d{2}/', (string) ($value ?? '')) ? substr((string) $value, 0, 5) : ($value ?? '');
@endphp
<div class="admin-time-picker {{ $wrapperClass ?? 'w-full' }}" data-admin-time-picker>
    <div class="kt-input cursor-pointer">
        <i class="ki-outline ki-time" aria-hidden="true"></i>
        <input class="grow"
               name="{{ $name }}"
               @isset($id) id="{{ $id }}" @endisset
               value="{{ $timeValue }}"
               placeholder="{{ $placeholder ?? 'Selecteer tijd' }}"
               readonly
               type="text"
               inputmode="numeric"
               autocomplete="off"
               data-admin-time-picker-input="true"
               @if(!empty($required)) required @endif
               @error($name) data-server-error="1" @enderror />
    </div>
</div>
