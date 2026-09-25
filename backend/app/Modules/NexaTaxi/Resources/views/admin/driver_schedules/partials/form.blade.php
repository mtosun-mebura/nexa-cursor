@php
    $isEdit = isset($schedule);
    $selectedDriverId = (int) old('driver_id', $isEdit ? $schedule->driver_id : '');
    $selectedVehicleId = (int) old('vehicle_id', $isEdit ? $schedule->vehicle_id : '');
    $dateValue = old('date', $isEdit ? ($start?->toDateString() ?? '') : ($defaultDate ?? now()->toDateString()));
    $startTime = old('start_time', $isEdit ? ($start?->format('H:i') ?? '08:00') : '08:00');
    $endTime = old('end_time', $isEdit ? ($end?->format('H:i') ?? '17:00') : '17:00');
    $notesValue = old('notes', $isEdit ? ($schedule->notes ?? '') : '');
    $repeatWeekly = $isEdit ? (bool) $schedule->repeat_weekly : (bool) old('repeat_weekly');
    $repeatUntilRaw = $isEdit ? $schedule->repeat_until : null;
    if ($repeatUntilRaw instanceof \DateTimeInterface) {
        $repeatUntilRaw = $repeatUntilRaw->format('Y-m-d');
    }
    $repeatUntilValue = old('repeat_until', $repeatUntilRaw ?? '');
    $weekdayLabels = [1 => 'Ma', 2 => 'Di', 3 => 'Wo', 4 => 'Do', 5 => 'Vr', 6 => 'Za', 7 => 'Zo'];
    $selectedWeekdays = old('weekdays');
    if (! is_array($selectedWeekdays)) {
        $fromSchedule = $isEdit ? array_filter(array_map('intval', explode(',', (string) ($schedule->weekdays ?? '')))) : [];
        $selectedWeekdays = $fromSchedule;
    }
    $selectedWeekdays = array_values(array_unique(array_map('intval', $selectedWeekdays)));
    if ($selectedWeekdays === []) {
        try {
            $parsedDate = parse_admin_date($dateValue) ?: $dateValue;
            $selectedWeekdays = [(int) \Carbon\Carbon::parse($parsedDate)->isoWeekday()];
        } catch (\Throwable) {
            $selectedWeekdays = [(int) now()->isoWeekday()];
        }
    }
@endphp

<div class="kt-card w-full min-w-0">
    <div class="kt-card-header px-5 py-5">
        <h3 class="kt-card-title mb-0">Dienst</h3>
    </div>
    <div class="kt-card-content p-0">
        <div class="px-3 sm:px-5 pb-3 min-w-0">
            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Chauffeur *</td>
                    <td class="min-w-48 w-full">
                        <select name="driver_id" class="kt-select w-full @error('driver_id') border-destructive @enderror" required @error('driver_id') data-server-error="1" @enderror>
                            <option value="">Kies chauffeur</option>
                            @foreach($chauffeurs as $chauffeur)
                                <option value="{{ $chauffeur->id }}" @selected($selectedDriverId === (int) $chauffeur->id)>
                                    {{ trim($chauffeur->first_name.' '.$chauffeur->last_name) }}
                                </option>
                            @endforeach
                        </select>
                        @error('driver_id')
                            <div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="driver_id">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Voertuig *</td>
                    <td class="min-w-48 w-full">
                        <select name="vehicle_id" class="kt-select w-full @error('vehicle_id') border-destructive @enderror" required @error('vehicle_id') data-server-error="1" @enderror>
                            <option value="">Kies voertuig</option>
                            @foreach($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" @selected($selectedVehicleId === (int) $vehicle->id)>
                                    {{ $vehicle->fleetLabel() }}
                                </option>
                            @endforeach
                        </select>
                        @error('vehicle_id')
                            <div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="vehicle_id">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Vanaf datum *</td>
                    <td class="min-w-48 w-full">
                        @include('taxi::admin.transport_customers.partials.date-picker-input', [
                            'name' => 'date',
                            'id' => 'driver-schedule-date',
                            'value' => $dateValue,
                            'wrapperClass' => 'admin-field-fit w-auto max-w-full',
                            'placeholder' => 'Selecteer datum',
                            'required' => true,
                        ])
                        @error('date')
                            <div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="date">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top pt-3">Dagen *</td>
                    <td class="min-w-48 w-full">
                        <div class="admin-weekday-picks" id="driver-schedule-weekdays">
                            @foreach($weekdayLabels as $iso => $label)
                                <label>
                                    <input type="checkbox"
                                           name="weekdays[]"
                                           value="{{ $iso }}"
                                           @checked(in_array($iso, $selectedWeekdays, true))>
                                    <span class="kt-btn kt-btn-sm kt-btn-outline admin-weekday-picks__btn">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="text-xs text-muted-foreground mt-2 mb-0">Kies de dagen vanaf de startdatum. Deze dagen herhalen wekelijks tot de dienst wordt verwijderd, of tot een einddatum als je die zet.</p>
                        @error('weekdays')
                            <div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="weekdays">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Van *</td>
                    <td class="min-w-48 w-full">
                        @include('taxi::admin.transport_customers.partials.time-picker-input', [
                            'name' => 'start_time',
                            'id' => 'driver-schedule-start-time',
                            'value' => $startTime,
                            'wrapperClass' => 'admin-field-fit w-auto max-w-full',
                            'placeholder' => 'Starttijd',
                            'required' => true,
                        ])
                        @error('start_time')
                            <div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="start_time">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Tot *</td>
                    <td class="min-w-48 w-full">
                        @include('taxi::admin.transport_customers.partials.time-picker-input', [
                            'name' => 'end_time',
                            'id' => 'driver-schedule-end-time',
                            'value' => $endTime,
                            'wrapperClass' => 'admin-field-fit w-auto max-w-full',
                            'placeholder' => 'Eindtijd',
                            'required' => true,
                        ])
                        @error('end_time')
                            <div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="end_time">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Einddatum</td>
                    <td class="min-w-48 w-full">
                        <label class="kt-label flex items-center gap-2 mb-0">
                            <input type="checkbox"
                                   name="repeat_weekly"
                                   value="1"
                                   class="kt-checkbox"
                                   id="driver-schedule-repeat-weekly"
                                   @checked($repeatWeekly)>
                            <span>Begrenzen met een einddatum</span>
                        </label>
                        <p class="text-xs text-muted-foreground mt-2 mb-0">Niet aanvinken: de gekozen dagen lopen door tot je de dienst verwijdert. Aanvinken: je kunt een einddatum zetten.</p>
                    </td>
                </tr>
                <tr id="driver-schedule-repeat-until-row" class="{{ $repeatWeekly ? '' : 'hidden' }}">
                    <td class="min-w-56 text-secondary-foreground font-normal">Herhalen tot</td>
                    <td class="min-w-48 w-full">
                        <div class="flex flex-wrap items-center gap-3">
                            @include('taxi::admin.transport_customers.partials.date-picker-input', [
                                'name' => 'repeat_until',
                                'id' => 'driver-schedule-repeat-until',
                                'value' => $repeatUntilValue,
                                'wrapperClass' => 'admin-field-fit admin-field-fit--until w-auto max-w-full',
                                'placeholder' => 'Leeg = doorlopend',
                            ])
                            <button type="button"
                                    id="driver-schedule-repeat-until-clear"
                                    class="text-xs text-muted-foreground hover:text-foreground underline underline-offset-2">
                                Wissen
                            </button>
                        </div>
                        <p class="text-xs text-muted-foreground mt-2 mb-0 w-full max-w-none">Laat leeg of wis de datum om door te laten lopen tot de dienst wordt verwijderd.</p>
                        @error('repeat_until')
                            <div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="repeat_until">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top pt-3">Notitie</td>
                    <td class="min-w-48 w-full">
                        <textarea name="notes"
                                  rows="3"
                                  class="kt-input @error('notes') border-destructive @enderror"
                                  maxlength="2000"
                                  placeholder="Optioneel">{{ $notesValue }}</textarea>
                        @error('notes')
                            <div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="notes">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var checkbox = document.getElementById('driver-schedule-repeat-weekly');
    var row = document.getElementById('driver-schedule-repeat-until-row');
    var untilInput = document.getElementById('driver-schedule-repeat-until');
    var untilClear = document.getElementById('driver-schedule-repeat-until-clear');

    function clearUntilDate() {
        if (!untilInput) {
            return;
        }
        untilInput.value = '';
        untilInput.setAttribute('value', '');
        untilInput.removeAttribute('data-kt-date-picker-selected-dates');
        try {
            var picker = window.KTDatePicker && window.KTDatePicker.getInstance(untilInput);
            if (picker) {
                picker._pendingDates = [];
            }
            var calendar = picker && picker.getCalendar && picker.getCalendar();
            if (calendar) {
                calendar.selectedDates = [];
                if (calendar.context) {
                    calendar.context.selectedDates = [];
                }
                if (typeof calendar.set === 'function') {
                    calendar.set({ selectedDates: [] });
                }
            }
        } catch (e) {}
        untilInput.dispatchEvent(new Event('input', { bubbles: true }));
        untilInput.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function syncRepeat() {
        if (!checkbox || !row) {
            return;
        }
        var limited = checkbox.checked;
        row.classList.toggle('hidden', !limited);
        if (untilInput) {
            untilInput.disabled = !limited;
            if (!limited) {
                clearUntilDate();
            }
        }
    }

    if (checkbox && row) {
        checkbox.addEventListener('change', syncRepeat);
        syncRepeat();
        var form = checkbox.closest('form');
        if (form) {
            form.addEventListener('submit', function () {
                if (!checkbox.checked) {
                    clearUntilDate();
                    if (untilInput) {
                        untilInput.disabled = true;
                    }
                }
            });
        }
    }
    if (untilClear) {
        untilClear.addEventListener('click', function () {
            clearUntilDate();
        });
    }

    var dateInput = document.getElementById('driver-schedule-date');
    var weekdayWrap = document.getElementById('driver-schedule-weekdays');
    if (!dateInput || !weekdayWrap) {
        return;
    }

    function isoWeekdayFromDisplay(value) {
        var match = String(value || '').trim().match(/^(\d{2})-(\d{2})-(\d{4})$/);
        var iso = String(value || '').trim().match(/^(\d{4})-(\d{2})-(\d{2})/);
        var year, month, day;
        if (match) {
            day = parseInt(match[1], 10);
            month = parseInt(match[2], 10);
            year = parseInt(match[3], 10);
        } else if (iso) {
            year = parseInt(iso[1], 10);
            month = parseInt(iso[2], 10);
            day = parseInt(iso[3], 10);
        } else {
            return null;
        }
        var date = new Date(year, month - 1, day);
        if (Number.isNaN(date.getTime())) {
            return null;
        }
        var jsDay = date.getDay();
        return jsDay === 0 ? 7 : jsDay;
    }

    function selectWeekday(iso) {
        var input = weekdayWrap.querySelector('input[value="' + iso + '"]');
        if (input && !input.checked) {
            input.checked = true;
        }
    }

    dateInput.addEventListener('change', function () {
        var iso = isoWeekdayFromDisplay(dateInput.value);
        if (iso) {
            selectWeekday(iso);
        }
    });
});
</script>
@endpush
@endonce
