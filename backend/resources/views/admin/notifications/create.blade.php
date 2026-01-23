@extends('admin.layouts.app')

@section('title', 'Nieuwe Notificatie')

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-col gap-5 pb-7.5">
        <div class="flex flex-wrap items-center justify-between gap-5">
            <h1 class="text-xl font-medium leading-none text-mono">
                Nieuwe Notificatie
            </h1>
        </div>
        <div class="flex items-center">
            <a href="{{ route('admin.notifications.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
    </div>

    <form action="{{ route('admin.notifications.store') }}" method="POST" enctype="multipart/form-data" data-validate="true">
        @csrf

        <div class="grid gap-5 lg:gap-7.5">
            <x-error-card :errors="$errors" />

            <!-- Notificatie Details -->
            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Notificatie Details
                    </h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">
                                Ontvanger *
                            </td>
                            <td class="min-w-48 w-full">
                                <select class="kt-select @error('user_id') border-destructive @enderror" 
                                        id="user_id" 
                                        name="user_id" 
                                        required>
                                    <option value="">Selecteer ontvanger</option>
                                    
                                    @if($backendUsers->count() > 0)
                                        <optgroup label="Backend Gebruikers">
                                            @foreach($backendUsers as $user)
                                                <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                                    {{ $user->first_name }} {{ $user->last_name }} ({{ $user->email }})
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                    
                                    @if($candidates->count() > 0)
                                        <optgroup label="Kandidaten">
                                            @foreach($candidates as $candidate)
                                                @php
                                                    $vacancies = $candidateVacancies[$candidate->email] ?? [];
                                                    $vacancyText = !empty($vacancies) ? ' - ' . implode(', ', array_unique($vacancies)) : '';
                                                @endphp
                                                <option value="{{ $candidate->id }}" {{ old('user_id') == $candidate->id ? 'selected' : '' }}>
                                                    {{ $candidate->first_name }} {{ $candidate->last_name }} ({{ $candidate->email }}){{ $vacancyText }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                </select>
                                @error('user_id')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                                <div class="text-xs text-muted-foreground mt-1">
                                    Selecteer een backend gebruiker van je bedrijf of een kandidaat die heeft gesolliciteerd op je vacatures
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Categorie *
                            </td>
                            <td>
                                <select class="kt-select @error('category') border-destructive @enderror" 
                                        id="category" 
                                        name="category" 
                                        required>
                                    <option value="">Selecteer categorie</option>
                                    <option value="info" {{ old('category', 'info') == 'info' ? 'selected' : '' }}>Informatie</option>
                                    <option value="warning" {{ old('category') == 'warning' ? 'selected' : '' }}>Waarschuwing</option>
                                    <option value="success" {{ old('category') == 'success' ? 'selected' : '' }}>Succes</option>
                                    <option value="error" {{ old('category') == 'error' ? 'selected' : '' }}>Fout</option>
                                    <option value="reminder" {{ old('category') == 'reminder' ? 'selected' : '' }}>Herinnering</option>
                                    <option value="update" {{ old('category') == 'update' ? 'selected' : '' }}>Update</option>
                                </select>
                                @error('category')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Type *
                            </td>
                            <td>
                                <select class="kt-select @error('type') border-destructive @enderror" 
                                        id="type" 
                                        name="type" 
                                        required>
                                    <option value="">Selecteer type</option>
                                    <option value="match" {{ old('type') == 'match' ? 'selected' : '' }}>Match</option>
                                    <option value="interview" {{ old('type') == 'interview' ? 'selected' : '' }}>Interview</option>
                                    <option value="application" {{ old('type') == 'application' ? 'selected' : '' }}>Sollicitatie</option>
                                    <option value="system" {{ old('type') == 'system' ? 'selected' : '' }}>Systeem</option>
                                    <option value="email" {{ old('type') == 'email' ? 'selected' : '' }}>E-mail</option>
                                    <option value="reminder" {{ old('type') == 'reminder' ? 'selected' : '' }}>Herinnering</option>
                                    <option value="file" {{ old('type') == 'file' ? 'selected' : '' }}>Bestand</option>
                                </select>
                                @error('type')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Titel *
                            </td>
                            <td>
                                <input type="text" 
                                       class="kt-input @error('title') border-destructive @enderror" 
                                       name="title" 
                                       id="title"
                                       value="{{ old('title') }}" 
                                       required
                                       placeholder="Bijv. Nieuwe match gevonden">
                                @error('title')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                Bericht *
                            </td>
                            <td>
                                <textarea class="kt-input pt-1 @error('message') border-destructive @enderror" 
                                          id="message" 
                                          name="message" 
                                          rows="4" 
                                          required
                                          placeholder="Voer hier het bericht in...">{{ old('message') }}</textarea>
                                @error('message')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Prioriteit
                            </td>
                            <td>
                                <select class="kt-select @error('priority') border-destructive @enderror" 
                                        id="priority" 
                                        name="priority">
                                    <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Laag</option>
                                    <option value="normal" {{ old('priority', 'normal') == 'normal' ? 'selected' : '' }}>Normaal</option>
                                    <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>Hoog</option>
                                    <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                                </select>
                                @error('priority')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                                <div class="text-xs text-muted-foreground mt-1">
                                    De prioriteit bepaalt de kleur van het notificatie icoon (Laag: grijs, Normaal: blauw, Hoog: oranje, Urgent: rood)
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Actie URL
                            </td>
                            <td>
                                <input type="url" 
                                       class="kt-input @error('action_url') border-destructive @enderror" 
                                       name="action_url" 
                                       id="action_url"
                                       value="{{ old('action_url') }}" 
                                       placeholder="https://example.com/action">
                                @error('action_url')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                                <div class="text-xs text-muted-foreground mt-1">
                                    Optionele URL waar de gebruiker naartoe wordt geleid bij klikken op de notificatie
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Gepland op
                            </td>
                            <td>
                                <div class="flex items-center gap-2.5">
                                    <div class="kt-input" style="max-width: 200px;">
                                        <i class="ki-outline ki-calendar"></i>
                                        <input class="grow"
                                               id="scheduled_at_display"
                                               data-kt-date-picker="true"
                                               data-kt-date-picker-input-mode="true"
                                               data-kt-date-picker-position-to-input="left"
                                               data-kt-date-picker-format="dd-mm-yyyy"
                                               placeholder="Selecteer datum"
                                               readonly
                                               type="text"
                                               value="{{ old('scheduled_at') ? \Carbon\Carbon::parse(old('scheduled_at'))->format('d-m-Y') : '' }}"/>
                                        <input type="hidden"
                                               name="scheduled_at"
                                               id="scheduled_at_hidden"
                                               value="{{ old('scheduled_at') ? \Carbon\Carbon::parse(old('scheduled_at'))->format('Y-m-d') : '' }}"/>
                                    </div>
                                    <input type="text"
                                           name="scheduled_time"
                                           id="scheduled_time"
                                           class="kt-input @error('scheduled_time') border-destructive @enderror"
                                           placeholder="hh:mm"
                                           maxlength="5"
                                           pattern="[0-9]{2}:[0-9]{2}"
                                           style="max-width: 100px;"
                                           value="{{ old('scheduled_time', old('scheduled_at') ? \Carbon\Carbon::parse(old('scheduled_at'))->format('H:i') : '') }}">
                                </div>
                                @error('scheduled_at')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                                @error('scheduled_time')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                                <small class="text-muted-foreground text-xs mt-1 block">Voer tijd in als hh:mm (bijv. 14:30)</small>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Locatie
                            </td>
                            <td>
                                <select class="kt-select @error('location_id') border-destructive @enderror" 
                                        id="location_id" 
                                        name="location_id">
                                    <option value="">Selecteer locatie</option>
                                    @if($company)
                                        @php
                                            $mainLocation = $company->mainLocation;
                                            $hasMainLocationInList = $mainLocation && $companyLocations->contains('id', $mainLocation->id);
                                            $hasCompanyAddress = $company->street || $company->city;
                                        @endphp
                                        @if($mainLocation && !$hasMainLocationInList)
                                            @php
                                                $mainAddress = trim(($mainLocation->street ?? '') . ' ' . ($mainLocation->house_number ?? '') . ($mainLocation->house_number_extension ? '-' . $mainLocation->house_number_extension : ''));
                                                $mainAddress = trim($mainAddress . ' ' . ($mainLocation->postal_code ?? '') . ' ' . ($mainLocation->city ?? ''));
                                                $mainDisplayName = $mainLocation->name;
                                                if ($mainLocation->city) {
                                                    $mainDisplayName .= ' - ' . $mainLocation->city;
                                                }
                                                $mainDisplayName .= ' (Hoofdadres)';
                                            @endphp
                                            <option value="{{ $mainLocation->id }}" {{ old('location_id') == $mainLocation->id ? 'selected' : '' }}>
                                                {{ $mainDisplayName }}
                                            </option>
                                        @elseif(!$mainLocation && $hasCompanyAddress)
                                            @php
                                                $companyAddress = trim(($company->street ?? '') . ' ' . ($company->house_number ?? '') . ($company->house_number_extension ? '-' . $company->house_number_extension : ''));
                                                $companyAddress = trim($companyAddress . ' ' . ($company->postal_code ?? '') . ' ' . ($company->city ?? ''));
                                                $companyDisplayName = $company->name;
                                                if ($company->city) {
                                                    $companyDisplayName .= ' - ' . $company->city;
                                                }
                                                $companyDisplayName .= ' (Hoofdadres)';
                                            @endphp
                                            <option value="company_main" {{ old('location_id') == 'company_main' ? 'selected' : '' }}>
                                                {{ $companyDisplayName }}
                                            </option>
                                        @endif
                                    @endif
                                    @if($companyLocations->count() > 0)
                                        @foreach($companyLocations as $location)
                                            @php
                                                $isMain = $mainLocation && $location->id === $mainLocation->id;
                                                $locationAddress = trim(($location->street ?? '') . ' ' . ($location->house_number ?? '') . ($location->house_number_extension ? '-' . $location->house_number_extension : ''));
                                                $locationAddress = trim($locationAddress . ' ' . ($location->postal_code ?? '') . ' ' . ($location->city ?? ''));
                                                $locationDisplayName = $location->name;
                                                if ($location->city) {
                                                    $locationDisplayName .= ' - ' . $location->city;
                                                }
                                                if ($isMain) {
                                                    $locationDisplayName .= ' (Hoofdadres)';
                                                }
                                            @endphp
                                            <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>
                                                {{ $locationDisplayName }}
                                            </option>
                                        @endforeach
                                    @endif
                                    {{-- Add "Op afstand" option --}}
                                    <option value="remote" {{ old('location_id') == 'remote' ? 'selected' : '' }}>
                                        Op afstand
                                    </option>
                                </select>
                                @error('location_id')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                                <div class="text-xs text-muted-foreground mt-1">
                                    Optioneel: selecteer een locatie van je bedrijf
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Bestand
                            </td>
                            <td>
                                <input type="file" 
                                       class="kt-input @error('file') border-destructive @enderror" 
                                       name="file" 
                                       id="file"
                                       accept="*/*">
                                @error('file')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                                <div class="text-xs text-muted-foreground mt-1">
                                    Optioneel: upload een bestand bij deze notificatie (max 10MB)
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                Extra Data (JSON)
                            </td>
                            <td>
                                <textarea class="kt-input @error('data') border-destructive @enderror" 
                                          id="data" 
                                          name="data" 
                                          rows="4" 
                                          placeholder='{"key": "value", "match_id": 123}'>{{ old('data') }}</textarea>
                                @error('data')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                                <div class="text-xs text-muted-foreground mt-1">
                                    Optionele JSON data voor extra informatie
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5">
                <a href="{{ route('admin.notifications.index') }}" class="kt-btn kt-btn-outline">
                    <i class="ki-filled ki-cross me-2"></i>
                    Annuleren
                </a>
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-check me-2"></i>
                    Notificatie Verzenden
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize datepicker
    const dateInput = document.getElementById('scheduled_at_display');
    const hiddenInput = document.getElementById('scheduled_at_hidden');
    const timeInput = document.getElementById('scheduled_time');
    
    // Function to update hidden input with combined date and time
    function updateHiddenInput() {
        if (!hiddenInput) return;
        
        let dateValue = '';
        let timeValue = timeInput ? timeInput.value.trim() : '';
        
        // Get date value from flatpickr or display input
        if (dateInput) {
            // Try to get the actual date from flatpickr instance if available
            if (window.flatpickrInstance && window.flatpickrInstance.selectedDates.length > 0) {
                const selectedDate = window.flatpickrInstance.selectedDates[0];
                dateValue = selectedDate.getFullYear() + '-' + 
                           String(selectedDate.getMonth() + 1).padStart(2, '0') + '-' + 
                           String(selectedDate.getDate()).padStart(2, '0');
            } else {
                // Fallback: parse from display value
                const displayValue = dateInput.value.trim();
                if (displayValue) {
                    const parts = displayValue.split('-');
                    if (parts.length === 3 && parts[0].length === 2) {
                        // Format is d-m-Y, convert to Y-m-d
                        dateValue = parts[2] + '-' + parts[1] + '-' + parts[0];
                    } else if (parts.length === 3 && parts[0].length === 4) {
                        // Already in Y-m-d format
                        dateValue = displayValue;
                    } else {
                        // Try to use hidden input value if available
                        dateValue = hiddenInput.value.split(' ')[0] || '';
                    }
                } else {
                    // Use existing hidden input date if available
                    dateValue = hiddenInput.value.split(' ')[0] || '';
                }
            }
        } else {
            // Use existing hidden input date if available
            dateValue = hiddenInput.value.split(' ')[0] || '';
        }
        
        // Combine date and time: Y-m-d H:i:s
        if (dateValue) {
            if (timeValue) {
                // Ensure time has seconds
                if (timeValue.length === 5) {
                    timeValue = timeValue + ':00';
                }
                hiddenInput.value = dateValue + ' ' + timeValue;
            } else {
                // Only date, no time yet
                hiddenInput.value = dateValue;
            }
        } else if (timeValue) {
            // Only time, keep existing date if any
            const existingDate = hiddenInput.value.split(' ')[0];
            if (existingDate) {
                if (timeValue.length === 5) {
                    timeValue = timeValue + ':00';
                }
                hiddenInput.value = existingDate + ' ' + timeValue;
            }
        }
    }
    
    // Update hidden input immediately on page load
    updateHiddenInput();
    
    if (dateInput) {
        // Function to update hidden input with current date and time (defined first so it's available for event listeners)
        function updateHiddenInputFromDatepicker() {
            if (!hiddenInput) {
                return;
            }
            
            // Get the selected date from flatpickr instance
            let dateValue = '';
            if (window.flatpickrInstance && window.flatpickrInstance.selectedDates && window.flatpickrInstance.selectedDates.length > 0) {
                const selectedDate = window.flatpickrInstance.selectedDates[0];
                dateValue = selectedDate.getFullYear() + '-' + 
                           String(selectedDate.getMonth() + 1).padStart(2, '0') + '-' + 
                           String(selectedDate.getDate()).padStart(2, '0');
            } else {
                // Fallback: parse from display value if flatpickr instance not ready or no selected dates
                const displayValue = dateInput ? dateInput.value.trim() : '';
                if (displayValue) {
                    const parts = displayValue.split('-');
                    if (parts.length === 3 && parts[0].length === 2) { // d-m-Y format
                        dateValue = parts[2] + '-' + parts[1] + '-' + parts[0];
                    } else if (parts.length === 3 && parts[0].length === 4) { // Y-m-d format
                        dateValue = displayValue;
                    }
                }
            }
            
            // Get current time value
            const currentTime = timeInput ? timeInput.value.trim() : '';
            
            // Update hidden input
            if (dateValue) {
                if (currentTime) {
                    const timeWithSeconds = currentTime.length === 5 ? currentTime + ':00' : currentTime;
                    hiddenInput.value = dateValue + ' ' + timeWithSeconds;
                } else {
                    hiddenInput.value = dateValue;
                }
            } else {
            }
        }
        
        // Monitor the date input value for changes - this catches all date changes including from calendar
        let lastDateValue = dateInput.value || '';
        const checkDateChange = function() {
            const currentValue = dateInput.value || '';
            if (currentValue !== lastDateValue && currentValue.trim() !== '') {
                lastDateValue = currentValue;
                // Update hidden input immediately when date value changes
                setTimeout(function() {
                    updateHiddenInputFromDatepicker();
                }, 100);
            }
        };
        
        // Check for date changes every 200ms
        const dateCheckInterval = setInterval(checkDateChange, 200);
        
        // Also monitor input events
        dateInput.addEventListener('input', function() {
            checkDateChange();
        });
        
        // Add multiple event listeners to catch clicks - use capture phase and multiple event types
        const clickHandler = function(e) {
            // Update after a short delay to allow flatpickr to process
            setTimeout(function() {
                checkDateChange();
                updateHiddenInputFromDatepicker();
            }, 200);
        };
        
        // Try multiple event types
        dateInput.addEventListener('click', clickHandler, true);
        dateInput.addEventListener('mousedown', clickHandler, true);
        dateInput.addEventListener('pointerdown', clickHandler, true);
        dateInput.addEventListener('focus', function(e) {
        }, true);
        
        // Also use event delegation on document level to catch all clicks on the input
        const dateInputParent = dateInput.closest('.kt-input');
        document.addEventListener('click', function(e) {
            // Check if click is on the input or its parent container
            const clickedElement = e.target;
            const isOnInput = clickedElement === dateInput;
            const isOnParent = dateInputParent && (clickedElement === dateInputParent || dateInputParent.contains(clickedElement));
            
            if (isOnInput || isOnParent) {
                setTimeout(function() {
                    updateHiddenInputFromDatepicker();
                }, 200);
            }
        }, true);
        
        // Also use event delegation on parent element to catch clicks
        if (dateInputParent) {
            dateInputParent.addEventListener('click', function(e) {
                // Update after a short delay
                setTimeout(function() {
                    updateHiddenInputFromDatepicker();
                }, 200);
            }, true);
        }
        
        // Wait for KTDatePicker to initialize, then hook into the flatpickr instance
        let setupAttempts = 0;
        const maxSetupAttempts = 50; // Stop after 5 seconds (50 * 100ms)
        
        function setupDatePickerListeners() {
            setupAttempts++;
            
            // Check multiple ways flatpickr instance might be stored
            let fp = dateInput._flatpickr || 
                    (window.flatpickr && window.flatpickr.getInstance && window.flatpickr.getInstance(dateInput)) ||
                    null;
            
            // Also check if KTDatePicker has initialized it
            if (!fp && typeof KTDatePicker !== 'undefined') {
                // Try to get instance from KTDatePicker
                try {
                    const instances = KTDatePicker.getInstance && KTDatePicker.getInstance(dateInput);
                    if (instances && instances._flatpickr) {
                        fp = instances._flatpickr;
                    }
                } catch(e) {
                    // Ignore errors
                }
            }
            
            if (!fp && setupAttempts < maxSetupAttempts) {
                // If KTDatePicker hasn't initialized yet, wait a bit and try again
                setTimeout(setupDatePickerListeners, 100);
                return;
            }
            
            if (fp) {
                // Store instance globally
                window.flatpickrInstance = fp;
                
                // Hook into flatpickr's onChange callback
                const originalOnChange = fp.config.onChange || function() {};
                fp.config.onChange = function(selectedDates, dateStr, instance) {
                    // Call original onChange if it exists
                    if (originalOnChange) {
                        originalOnChange(selectedDates, dateStr, instance);
                    }
                    // Update hidden input immediately
                    updateHiddenInputFromDatepicker();
                };
                
                // Hook into onClose callback
                const originalOnClose = fp.config.onClose || function() {};
                fp.config.onClose = function(selectedDates, dateStr, instance) {
                    if (originalOnClose) {
                        originalOnClose(selectedDates, dateStr, instance);
                    }
                    // Update hidden input when calendar closes
                    updateHiddenInputFromDatepicker();
                };
                
                // Also listen to clicks on the calendar days directly - use MutationObserver to wait for calendar to appear
                function setupCalendarListeners() {
                    if (fp.calendarContainer) {
                        fp.calendarContainer.addEventListener('click', function(e) {
                            const dayElement = e.target.closest('.flatpickr-day');
                            if (dayElement && !dayElement.classList.contains('flatpickr-disabled')) {
                                // Small delay to ensure flatpickr has processed the selection
                                setTimeout(function() {
                                    updateHiddenInputFromDatepicker();
                                }, 50);
                            }
                        }, true);
                    } else {
                        // Calendar not ready yet, try again
                        setTimeout(setupCalendarListeners, 100);
                    }
                }
                setupCalendarListeners();
            }
        }
        
        // Fallback: if KTDatePicker didn't initialize after 2 seconds, initialize flatpickr ourselves
        setTimeout(function() {
            if (!dateInput._flatpickr && typeof flatpickr !== 'undefined') {
                const fp = flatpickr(dateInput, {
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd-m-Y',
                    defaultDate: hiddenInput ? hiddenInput.value.split(' ')[0] : null,
                    onChange: function(selectedDates, dateStr, instance) {
                        updateHiddenInputFromDatepicker();
                    },
                    onClose: function(selectedDates, dateStr, instance) {
                        updateHiddenInputFromDatepicker();
                    },
                    onReady: function(selectedDates, dateStr, instance) {
                        window.flatpickrInstance = instance;
                        updateHiddenInputFromDatepicker();
                    }
                });
                window.flatpickrInstance = fp;
            }
        }, 2000);
        
        // Start waiting for KTDatePicker
        setupDatePickerListeners();
        
        // Also listen to any changes on the date input field directly
        dateInput.addEventListener('change', function() {
            updateHiddenInputFromDatepicker();
        });
        
        // Update hidden input when date field loses focus (blur)
        dateInput.addEventListener('blur', function() {
            // Small delay to ensure flatpickr has processed any changes
            setTimeout(function() {
                updateHiddenInputFromDatepicker();
                
                // Also try to get date from display value if flatpickr doesn't have it
                if (hiddenInput && dateInput.value) {
                    const displayValue = dateInput.value.trim();
                    if (displayValue) {
                        const parts = displayValue.split('-');
                        if (parts.length === 3 && parts[0].length === 2) {
                            // Format is d-m-Y, convert to Y-m-d
                            const formattedDate = parts[2] + '-' + parts[1] + '-' + parts[0];
                            const currentTime = timeInput ? timeInput.value.trim() : '';
                            
                            if (currentTime) {
                                const timeWithSeconds = currentTime.length === 5 ? currentTime + ':00' : currentTime;
                                hiddenInput.value = formattedDate + ' ' + timeWithSeconds;
                            } else {
                                hiddenInput.value = formattedDate;
                            }
                        }
                    }
                }
            }, 100);
        });
    }
    
    // Auto-format time input with colon
    if (timeInput) {
        timeInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^\d]/g, ''); // Remove all non-digits
            
            // Auto-add colon after 2 digits
            if (value.length >= 2) {
                value = value.substring(0, 2) + ':' + value.substring(2, 4);
            }
            
            // Limit to 5 characters (hh:mm)
            if (value.length > 5) {
                value = value.substring(0, 5);
            }
            
            e.target.value = value;
            
            // Update hidden input immediately when time changes
            updateHiddenInput();
        });
        
        // Also update on keyup for immediate feedback
        timeInput.addEventListener('keyup', function() {
            updateHiddenInput();
        });
        
        // Validate format on blur
        timeInput.addEventListener('blur', function(e) {
            const value = e.target.value;
            const timePattern = /^([0-1][0-9]|2[0-3]):[0-5][0-9]$/;
            
            if (value && !timePattern.test(value)) {
                // If invalid, try to fix it
                const digits = value.replace(/[^\d]/g, '');
                if (digits.length >= 2) {
                    const hours = digits.substring(0, 2);
                    const minutes = digits.substring(2, 4) || '00';
                    const fixedValue = hours + ':' + minutes;
                    if (timePattern.test(fixedValue)) {
                        e.target.value = fixedValue;
                    } else {
                        e.target.value = '';
                    }
                } else {
                    e.target.value = '';
                }
            }
            
            // Update hidden input when time changes
            updateHiddenInput();
        });
        
        // Also update on change
        timeInput.addEventListener('change', function() {
            updateHiddenInput();
        });
        
        // Update on paste
        timeInput.addEventListener('paste', function() {
            setTimeout(function() {
                updateHiddenInput();
            }, 10);
        });
    }
    
    // Ensure date and time are sent correctly (controller will combine them)
    const form = document.querySelector('form[data-validate="true"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            // First, ensure hidden input has the correct date value
            if (hiddenInput && dateInput) {
                const displayValue = dateInput.value.trim();
                if (displayValue && !hiddenInput.value) {
                    // Convert d-m-Y to Y-m-d if needed
                    const parts = displayValue.split('-');
                    if (parts.length === 3 && parts[0].length === 2) {
                        hiddenInput.value = parts[2] + '-' + parts[1] + '-' + parts[0];
                    } else {
                        hiddenInput.value = displayValue;
                    }
                }
            }
            
            const dateValue = hiddenInput ? hiddenInput.value.trim() : '';
            const timeValue = timeInput ? timeInput.value.trim() : '';
            
            // Combine date and time into hidden input
            if (hiddenInput && dateValue) {
                // Extract just the date part if it contains time
                const dateOnly = dateValue.split(' ')[0];
                if (timeValue) {
                    const timeWithSeconds = timeValue.length === 5 ? timeValue + ':00' : timeValue;
                    hiddenInput.value = dateOnly + ' ' + timeWithSeconds;
                } else {
                    hiddenInput.value = dateOnly;
                }
            }
            
            // If date is filled but hidden input is still empty, try to set it from display
            if (dateInput && dateInput.value && (!hiddenInput || !hiddenInput.value)) {
                const displayValue = dateInput.value.trim();
                const parts = displayValue.split('-');
                if (parts.length === 3 && parts[0].length === 2) {
                    // Format is d-m-Y, convert to Y-m-d
                    if (hiddenInput) {
                        const timeValue = timeInput ? timeInput.value.trim() : '';
                        if (timeValue) {
                            const timeWithSeconds = timeValue.length === 5 ? timeValue + ':00' : timeValue;
                            hiddenInput.value = parts[2] + '-' + parts[1] + '-' + parts[0] + ' ' + timeWithSeconds;
                        } else {
                            hiddenInput.value = parts[2] + '-' + parts[1] + '-' + parts[0];
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush

@endsection
