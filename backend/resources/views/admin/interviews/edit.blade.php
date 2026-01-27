@extends('admin.layouts.app')

@section('title', 'Interview Bewerken')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono mb-3">
                Interview Bewerken
            </h1>
            <div class="mt-3 flex gap-2">
                <a href="{{ route('admin.agenda.index') }}" class="kt-btn kt-btn-outline">
                    <i class="ki-filled ki-arrow-left me-2"></i>
                    Terug naar Agenda
                </a>
                <a href="{{ route('admin.interviews.index') }}" class="kt-btn kt-btn-outline">
                    <i class="ki-filled ki-arrow-left me-2"></i>
                    Terug naar Overzicht
                </a>
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.interviews.show', $interview) }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-eye me-2"></i>
                Bekijken
            </a>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="mb-5 flex items-center justify-center gap-2 py-3 px-4" id="success-alert" role="alert" style="background-color: #10b981; color: white;">
        <i class="ki-filled ki-check-circle"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif

{{-- Errors are shown inline with fields, no general error alert needed --}}

<div class="kt-container-fixed">
    <div class="flex flex-col items-stretch grow">
        <form action="{{ route('admin.interviews.update', $interview) }}" method="POST" class="flex flex-col gap-5 lg:gap-7.5" novalidate>
            @csrf
            @method('PUT')

            <!-- Basis Informatie -->
            <div class="kt-card min-w-full pb-2.5" id="basis-informatie">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Basis Informatie
                    </h3>
                </div>
                <div class="kt-card-content grid gap-5">
                    <div class="w-full">
                        <div class="flex items-start py-3">
                            <label class="kt-form-label flex items-center gap-1 max-w-56 pt-2">
                                Match *
                            </label>
                            <div class="flex flex-col" style="flex: 1;">
                                <div class="flex items-center gap-2" style="display: inline-flex; align-items: center;">
                                    <div style="position: relative; display: inline-block; width: auto; min-width: 400px;">
                                        <select class="kt-select @error('match_id') border-destructive @enderror" 
                                                id="match_id" name="match_id"
                                                data-kt-select="true"
                                                style="width: auto; min-width: 400px; max-width: 100%;"
                                                required>
                                            <option value="">Selecteer match</option>
                                            @if(isset($matches) && $matches->count() > 0)
                                                <optgroup label="Kandidaten">
                                                    @foreach($matches as $match)
                                                        <option value="{{ $match->id }}" {{ old('match_id', $interview->match_id) == $match->id ? 'selected' : '' }}>
                                                            {{ trim(($match->candidate->first_name ?? '') . ' ' . ($match->candidate->last_name ?? '')) ?: 'Onbekend' }} - {{ $match->vacancy->title }}
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                            @endif
                                        </select>
                                    </div>
                                    @error('match_id')
                                        <div class="validation-icon-wrapper" style="display: flex; align-items: center; justify-content: center; width: 1.25rem; height: 1.25rem; flex-shrink: 0;">
                                            <i class="ki-filled ki-cross-circle text-destructive" style="font-size: 1.25rem;"></i>
                                        </div>
                                    @enderror
                                </div>
                                @error('match_id')
                                    <div class="text-xs text-destructive mt-1">Dit veld is verplicht!</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="w-full">
                        <div class="flex items-start py-3">
                            <label class="kt-form-label flex items-center gap-1 max-w-56 pt-2">
                                Type *
                            </label>
                            <div class="flex flex-col" style="flex: 1;">
                                <div class="flex items-center gap-2" style="display: inline-flex; align-items: center;">
                                    <div style="position: relative; display: inline-block; width: auto; min-width: 200px;">
                                        <select class="kt-select @error('type') border-destructive @enderror" 
                                                id="type" name="type"
                                                data-kt-select="true"
                                                style="width: auto; min-width: 200px; max-width: 100%;"
                                                required>
                                            <option value="">Selecteer type</option>
                                            <option value="phone" {{ old('type', $interview->type) == 'phone' ? 'selected' : '' }}>Telefoon</option>
                                            <option value="video" {{ old('type', $interview->type) == 'video' ? 'selected' : '' }}>Video</option>
                                            <option value="onsite" {{ old('type', $interview->type) == 'onsite' ? 'selected' : '' }}>Op locatie</option>
                                            <option value="assessment" {{ old('type', $interview->type) == 'assessment' ? 'selected' : '' }}>Assessment</option>
                                            <option value="final" {{ old('type', $interview->type) == 'final' ? 'selected' : '' }}>Eindgesprek</option>
                                        </select>
                                    </div>
                                    @error('type')
                                        <div class="validation-icon-wrapper" style="display: flex; align-items: center; justify-content: center; width: 1.25rem; height: 1.25rem; flex-shrink: 0;">
                                            <i class="ki-filled ki-cross-circle text-destructive" style="font-size: 1.25rem;"></i>
                                        </div>
                                    @enderror
                                </div>
                                @error('type')
                                    <div class="text-xs text-destructive mt-1">Dit veld is verplicht!</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="w-full">
                        <div class="flex items-start py-3">
                            <label class="kt-form-label flex items-center gap-1 max-w-56 pt-2">
                                Geplande Datum & Tijd <span class="text-destructive">*</span>
                            </label>
                            <div class="flex flex-col" style="flex: 1;">
                                <div class="flex items-center gap-2.5" style="width: auto;">
                                    <div class="flex items-center gap-2" style="display: inline-flex; align-items: center;">
                                        <div class="kt-input @error('scheduled_at') border-destructive @enderror" style="width: auto; min-width: 200px;">
                                            <i class="ki-outline ki-calendar"></i>
                                            <input class="grow"
                                                   id="scheduled_at_display"
                                                   data-kt-date-picker="true"
                                                   data-kt-date-picker-input-mode="true"
                                                   data-kt-date-picker-position-to-input="left"
                                                   data-kt-date-picker-date-format="DD-MM-YYYY"
                                                   @if($interview->scheduled_at)
                                                   data-kt-date-picker-selected-dates='["{{ $interview->scheduled_at->format('Y-m-d') }}"]'
                                                   data-kt-date-picker-selected-month="{{ $interview->scheduled_at->format('n') - 1 }}"
                                                   data-kt-date-picker-selected-year="{{ $interview->scheduled_at->format('Y') }}"
                                                   @endif
                                                   placeholder="Selecteer datum"
                                                   readonly
                                                   type="text"
                                                   value="{{ old('scheduled_at', $interview->scheduled_at ? $interview->scheduled_at->format('d-m-Y') : '') }}"/>
                                            <input type="hidden"
                                                   name="scheduled_at"
                                                   id="scheduled_at_hidden"
                                                   value="{{ old('scheduled_at', $interview->scheduled_at ? $interview->scheduled_at->format('Y-m-d H:i') : '') }}"/>
                                        </div>
                                        @error('scheduled_at')
                                            <div class="validation-icon-wrapper" style="display: flex; align-items: center; justify-content: center; width: 1.25rem; height: 1.25rem; flex-shrink: 0;">
                                                <i class="ki-filled ki-cross-circle text-destructive" style="font-size: 1.25rem;"></i>
                                            </div>
                                        @enderror
                                    </div>
                                    <div class="flex items-center gap-2" style="display: inline-flex; align-items: center;">
                                        <div class="kt-input @error('scheduled_time') border-destructive @enderror" style="width: auto; min-width: 120px;">
                                            <i class="ki-outline ki-time"></i>
                                            <input type="time"
                                                   name="scheduled_time"
                                                   id="scheduled_time"
                                                   class="grow"
                                                   required
                                                   value="{{ old('scheduled_time', $interview->scheduled_at ? $interview->scheduled_at->format('H:i') : '') }}">
                                        </div>
                                        @error('scheduled_time')
                                            <div class="validation-icon-wrapper" style="display: flex; align-items: center; justify-content: center; width: 1.25rem; height: 1.25rem; flex-shrink: 0;">
                                                <i class="ki-filled ki-cross-circle text-destructive" style="font-size: 1.25rem;"></i>
                                            </div>
                                        @enderror
                                    </div>
                                </div>
                                @error('scheduled_at')
                                    <div class="text-xs text-destructive mt-1">Dit veld is verplicht!</div>
                                @enderror
                                @error('scheduled_time')
                                    <div class="text-xs text-destructive mt-1">Dit veld is verplicht!</div>
                                @enderror
                                <small class="text-muted-foreground text-xs mt-1">Voer tijd in als hh:mm (bijv. 14:30)</small>
                            </div>
                        </div>
                    </div>

                    <div class="w-full">
                        <div class="flex items-start py-3">
                            <label class="kt-form-label flex items-center gap-1 max-w-56 pt-2">
                                Duur
                            </label>
                            <select class="kt-select @error('duration') border-destructive @enderror"
                                    id="duration" name="duration"
                                    style="width: auto; min-width: 120px;">
                                @php
                                    $selectedDuration = old('duration', $interview->duration ?? 60);
                                    $durations = [];
                                    for ($min = 15; $min <= 480; $min += 15) {
                                        $hours = floor($min / 60);
                                        $mins = $min % 60;
                                        if ($hours == 0) {
                                            $label = '0:' . str_pad($mins, 2, '0', STR_PAD_LEFT);
                                        } elseif ($mins == 0) {
                                            $label = $hours . ' uur';
                                        } else {
                                            $label = $hours . ':' . str_pad($mins, 2, '0', STR_PAD_LEFT);
                                        }
                                        $durations[$min] = $label;
                                    }
                                @endphp
                                @foreach($durations as $value => $label)
                                    <option value="{{ $value }}" {{ $selectedDuration == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('duration')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="w-full">
                        <div class="flex items-start py-3">
                            <label class="kt-form-label flex items-center gap-1 max-w-56 pt-2">
                                Status *
                            </label>
                            <div class="flex flex-col" style="flex: 1;">
                                <div class="flex items-center gap-2" style="display: inline-flex; align-items: center;">
                                    <div style="position: relative; display: inline-block; width: auto; min-width: 200px;">
                                        <select class="kt-select @error('status') border-destructive @enderror" 
                                                id="status" name="status"
                                                data-kt-select="true"
                                                style="width: auto; min-width: 200px; max-width: 100%;"
                                                required>
                                            <option value="">Selecteer status</option>
                                            <option value="scheduled" {{ old('status', $interview->status) == 'scheduled' ? 'selected' : '' }}>Gepland</option>
                                            <option value="confirmed" {{ old('status', $interview->status) == 'confirmed' ? 'selected' : '' }}>Bevestigd</option>
                                            <option value="completed" {{ old('status', $interview->status) == 'completed' ? 'selected' : '' }}>Voltooid</option>
                                            <option value="cancelled" {{ old('status', $interview->status) == 'cancelled' ? 'selected' : '' }}>Geannuleerd</option>
                                            <option value="rescheduled" {{ old('status', $interview->status) == 'rescheduled' ? 'selected' : '' }}>Herpland</option>
                                        </select>
                                    </div>
                                    @error('status')
                                        <div class="validation-icon-wrapper" style="display: flex; align-items: center; justify-content: center; width: 1.25rem; height: 1.25rem; flex-shrink: 0;">
                                            <i class="ki-filled ki-cross-circle text-destructive" style="font-size: 1.25rem;"></i>
                                        </div>
                                    @enderror
                                </div>
                                @error('status')
                                    <div class="text-xs text-destructive mt-1">Dit veld is verplicht!</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="w-full">
                        <div class="flex items-start py-3">
                            <label class="kt-form-label flex items-center gap-1 max-w-56 pt-2">
                                Locatie
                            </label>
                            <div class="flex flex-col" style="flex: 1;">
                                <div class="flex items-center gap-2" style="display: inline-flex; align-items: center;">
                                    <div style="position: relative; display: inline-block; width: auto; min-width: 400px;">
                                        <select id="company_location_id"
                                                name="company_location_id"
                                                class="kt-select @error('company_location_id') border-destructive @enderror"
                                                data-kt-select="true"
                                                style="width: auto; min-width: 400px; max-width: 100%;">
                                            <option value="">Selecteer locatie</option>
                                            @if(isset($selectedCompany) && $selectedCompany)
                                                @php
                                                    $mainLocation = $selectedCompany->mainLocation;
                                                    $hasCompanyAddress = $selectedCompany->street || $selectedCompany->city;
                                                    $currentLocationId = old('company_location_id', $interview->company_location_id);
                                                    // If location matches main address format, set to 0
                                                    if ($interview->location && $mainLocation) {
                                                        $mainAddress = trim(($mainLocation->street ?? '') . ' ' . ($mainLocation->house_number ?? '') . ($mainLocation->house_number_extension ? '-' . $mainLocation->house_number_extension : ''));
                                                        $mainAddress = trim($mainAddress . ' ' . ($mainLocation->postal_code ?? '') . ' ' . ($mainLocation->city ?? ''));
                                                        $mainLocationText = $mainLocation->name . ($mainAddress ? ' - ' . $mainAddress : '');
                                                        if (strpos($interview->location, $mainLocation->name) !== false || strpos($interview->location, $mainAddress) !== false) {
                                                            $currentLocationId = 0;
                                                        }
                                                    }
                                                @endphp
                                                @if($mainLocation)
                                                    @php
                                                        $mainAddress = trim(($mainLocation->street ?? '') . ' ' . ($mainLocation->house_number ?? '') . ($mainLocation->house_number_extension ? '-' . $mainLocation->house_number_extension : ''));
                                                        $mainAddress = trim($mainAddress . ' ' . ($mainLocation->postal_code ?? '') . ' ' . ($mainLocation->city ?? ''));
                                                        $mainDisplayName = $mainLocation->name;
                                                        if ($mainLocation->city) {
                                                            $mainDisplayName .= ' - ' . $mainLocation->city;
                                                        }
                                                        $mainDisplayName .= ' (Hoofdadres)';
                                                    @endphp
                                                    <option value="0" {{ $currentLocationId === 0 || $currentLocationId === '0' ? 'selected' : '' }}>
                                                        {{ $mainDisplayName }}
                                                    </option>
                                                @elseif($hasCompanyAddress)
                                                    @php
                                                        $mainDisplayName = $selectedCompany->name;
                                                        if ($selectedCompany->city) {
                                                            $mainDisplayName .= ' - ' . $selectedCompany->city;
                                                        }
                                                        $mainDisplayName .= ' (Hoofdadres)';
                                                    @endphp
                                                    <option value="0" {{ $currentLocationId === 0 || $currentLocationId === '0' ? 'selected' : '' }}>
                                                        {{ $mainDisplayName }}
                                                    </option>
                                                @endif
                                                @foreach($companyLocations ?? [] as $location)
                                                    <option value="{{ $location->id }}" {{ old('company_location_id', $interview->company_location_id) == $location->id ? 'selected' : '' }}>
                                                        {{ $location->name }}@if($location->city) - {{ $location->city }}@endif
                                                    </option>
                                                @endforeach
                                            @endif
                                            {{-- Add "Op afstand" option --}}
                                            @php
                                                $isRemoteSelected = ($interview->location === 'Op afstand' || 
                                                                     old('company_location_id') === 'remote' ||
                                                                     old('company_location_id') === '-1' ||
                                                                     old('company_location_id') === -1 ||
                                                                     $interview->company_location_id === -1);
                                            @endphp
                                            <option value="remote" {{ $isRemoteSelected ? 'selected' : '' }}>
                                                Op afstand
                                            </option>
                                        </select>
                                    </div>
                                    @error('company_location_id')
                                        <div class="validation-icon-wrapper" style="display: flex; align-items: center; justify-content: center; width: 1.25rem; height: 1.25rem; flex-shrink: 0;">
                                            <i class="ki-filled ki-cross-circle text-destructive" style="font-size: 1.25rem;"></i>
                                        </div>
                                    @enderror
                                </div>
                                @error('company_location_id')
                                    <div class="text-xs text-destructive mt-1">Dit veld is verplicht!</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Interviewer Informatie -->
            <div class="kt-card min-w-full pb-2.5" id="interviewer-informatie">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Interviewer Informatie
                    </h3>
                </div>
                <div class="kt-card-content grid gap-5">
                    <div class="w-full">
                        <div class="flex items-start py-3">
                            <label class="kt-form-label flex items-center gap-1 max-w-56 pt-2">
                                Interviewer Naam <span class="text-destructive">*</span>
                            </label>
                            <div class="flex flex-col" style="flex: 1;">
                                <div class="flex items-center gap-2" style="display: inline-flex; align-items: center;">
                                    <div style="position: relative; display: inline-block; width: auto; min-width: 400px;">
                                        <select id="interviewer_name"
                                                name="interviewer_name"
                                                class="kt-select @error('interviewer_name') border-destructive @enderror"
                                                data-kt-select="true"
                                                style="width: auto; min-width: 400px; max-width: 100%;"
                                                required>
                                            <option value="">Selecteer interviewer</option>
                                            @if(isset($companyUsers) && $companyUsers->count() > 0)
                                                <optgroup label="Gebruikers van het bedrijf">
                                                    @foreach($companyUsers as $user)
                                                        <option value="{{ $user->first_name }} {{ $user->last_name }}" 
                                                                data-email="{{ $user->email }}"
                                                                data-user-id="{{ $user->id }}"
                                                                {{ old('interviewer_name', $interview->interviewer_name) == ($user->first_name . ' ' . $user->last_name) ? 'selected' : '' }}>
                                                            {{ $user->first_name }} {{ $user->last_name }}
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                            @endif
                                        </select>
                                    </div>
                                    @error('interviewer_name')
                                        <div class="validation-icon-wrapper" style="display: flex; align-items: center; justify-content: center; width: 1.25rem; height: 1.25rem; flex-shrink: 0;">
                                            <i class="ki-filled ki-cross-circle text-destructive" style="font-size: 1.25rem;"></i>
                                        </div>
                                    @enderror
                                </div>
                                @error('interviewer_name')
                                    <div class="text-xs text-destructive mt-1">Dit veld is verplicht!</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="w-full">
                        <div class="flex items-start py-3">
                            <label class="kt-form-label flex items-center gap-1 max-w-56 pt-2">
                                Interviewer E-mail <span class="text-destructive">*</span>
                            </label>
                            <div class="flex flex-col" style="flex: 1;">
                                <div class="flex items-center gap-2" style="display: inline-flex; align-items: center;">
                                    <div style="position: relative; display: inline-block; width: auto; min-width: 400px;">
                                    <input type="email"
                                           class="kt-input @error('interviewer_email') border-destructive @enderror"
                                           id="interviewer_email" name="interviewer_email"
                                           value="{{ old('interviewer_email', $interview->interviewer_email) }}"
                                           style="width: auto; min-width: 400px; max-width: 100%;"
                                           readonly
                                           required>
                                    </div>
                                    <input type="hidden" id="interviewer_user_id" name="interviewer_user_id" value="{{ old('interviewer_user_id', $interview->interviewer_user_id) }}">
                                    @error('interviewer_email')
                                        <div class="validation-icon-wrapper" style="display: flex; align-items: center; justify-content: center; width: 1.25rem; height: 1.25rem; flex-shrink: 0;">
                                            <i class="ki-filled ki-cross-circle text-destructive" style="font-size: 1.25rem;"></i>
                                        </div>
                                    @enderror
                                </div>
                                @error('interviewer_email')
                                    <div class="text-xs text-destructive mt-1">Dit veld is verplicht!</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notities & Feedback -->
            <div class="kt-card min-w-full pb-2.5" id="notities-feedback">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Notities & Feedback
                    </h3>
                </div>
                <div class="kt-card-content grid gap-5">
                    <div class="w-full">
                        <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                            <label class="kt-form-label flex items-center gap-1 max-w-56 pt-2">
                                Notities
                            </label>
                            <div class="flex-1">
                                <textarea class="kt-input pt-1 @error('notes') border-destructive @enderror" 
                                          id="notes" name="notes" rows="4">{{ old('notes', $interview->notes) }}</textarea>
                                @error('notes')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="w-full">
                        <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                            <label class="kt-form-label flex items-center gap-1 max-w-56 pt-2">
                                Feedback
                            </label>
                            <div class="flex-1">
                                <textarea class="kt-input pt-1 @error('feedback') border-destructive @enderror" 
                                          id="feedback" name="feedback" rows="4">{{ old('feedback', $interview->feedback) }}</textarea>
                                @error('feedback')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                                <div class="text-xs text-muted-foreground mt-1">Feedback na het interview</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acties -->
            <div class="flex items-center justify-end gap-2.5">
                <a href="{{ route('admin.interviews.index') }}" class="kt-btn kt-btn-outline">
                    Annuleren
                </a>
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-check me-2"></i>
                    Wijzigingen Opslaan
                </button>
            </div>
        </form>
    </div>
</div>

@push('styles')
<style>
    /* Ensure optgroups are visible in KT Select dropdowns */
    .kt-select-group-header {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        font-weight: 600 !important;
        color: var(--muted-foreground) !important;
        background-color: var(--muted) !important;
        text-transform: uppercase !important;
        padding: 0.5rem 0.75rem !important;
        font-size: 0.75rem !important;
        border-bottom: 1px solid var(--border) !important;
        margin-top: 0.5rem !important;
        margin-bottom: 0.25rem !important;
    }
    
    .kt-select-group {
        display: block !important;
        visibility: visible !important;
    }
    
    .kt-select-group:first-child .kt-select-group-header {
        margin-top: 0 !important;
    }
    
    /* Indent options within optgroups to show they belong to that group */
    .kt-select-group .kt-select-option {
        padding-left: 1.5rem !important;
        margin-left: 0.5rem !important;
    }
    
    /* Prevent kt-select from expanding to full width */
    #basis-informatie .kt-select-wrapper,
    #interviewer-informatie .kt-select-wrapper {
        display: inline-block !important;
        width: auto !important;
        max-width: 100% !important;
    }
    
    #basis-informatie .kt-select-wrapper .kt-select-display,
    #basis-informatie select.kt-select,
    #interviewer-informatie .kt-select-wrapper .kt-select-display,
    #interviewer-informatie select.kt-select {
        width: auto !important;
        max-width: 100% !important;
    }

    /* Match and Location fields - 400px min-width */
    #match_id + .kt-select-wrapper,
    #match_id + .kt-select-wrapper .kt-select-display,
    #match_id.kt-select,
    #match_id + .kt-select-wrapper .kt-select-display.kt-select,
    #company_location_id + .kt-select-wrapper,
    #company_location_id + .kt-select-wrapper .kt-select-display,
    #company_location_id.kt-select,
    #company_location_id + .kt-select-wrapper .kt-select-display.kt-select {
        min-width: 400px !important;
        width: auto !important;
        max-width: 100% !important;
    }

    /* Type and Status fields - 200px min-width */
    #type + .kt-select-wrapper,
    #type + .kt-select-wrapper .kt-select-display,
    #type.kt-select,
    #type + .kt-select-wrapper .kt-select-display.kt-select,
    #status + .kt-select-wrapper,
    #status + .kt-select-wrapper .kt-select-display,
    #status.kt-select,
    #status + .kt-select-wrapper .kt-select-display.kt-select {
        min-width: 200px !important;
        width: auto !important;
        max-width: 100% !important;
    }

    /* Interviewer field - 400px min-width (same as Match) */
    #interviewer_name + .kt-select-wrapper,
    #interviewer_name + .kt-select-wrapper .kt-select-display,
    #interviewer_name.kt-select,
    #interviewer_name + .kt-select-wrapper .kt-select-display.kt-select {
        min-width: 400px !important;
        width: auto !important;
        max-width: 100% !important;
    }

    /* Duration input should also be auto width */
    #duration {
        width: auto !important;
        min-width: 100px;
        max-width: 100% !important;
    }

    /* Interviewer email input - same width as interviewer dropdown (400px) */
    #interviewer_email {
        width: auto !important;
        min-width: 400px !important;
        max-width: 100% !important;
    }

    /* Read-only email input styling */
    #interviewer_email[readonly] {
        background-color: var(--kt-body-bg);
        cursor: not-allowed;
        opacity: 0.7;
    }
    
    /* Validation icon wrapper styling - positioned next to fields, not inside */
    .validation-icon-wrapper {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 1.25rem !important;
        height: 1.25rem !important;
        flex-shrink: 0 !important;
    }
    
    /* Zorg dat select dropdowns boven blur/overlay elementen verschijnen */
    /* Gebruik absolute positioning in plaats van fixed om correcte positionering te behouden */
    .kt-select-dropdown,
    .kt-select-dropdown[data-kt-select-dropdown],
    [data-kt-select-dropdown] {
        z-index: 100000 !important;
        /* position blijft zoals het is (meestal absolute) - niet overschrijven */
    }
    
    .kt-select-options,
    .kt-select-options[data-kt-select-options],
    [data-kt-select-options] {
        z-index: 100001 !important;
        position: relative !important;
    }
    
    /* Zorg dat de dropdown wrapper ook de juiste z-index heeft */
    .kt-select-wrapper {
        position: relative !important;
    }
    
    .kt-select-wrapper .kt-select-dropdown.open,
    .kt-select-wrapper .kt-select-dropdown[data-kt-select-dropdown].open {
        z-index: 100000 !important;
    }
    
    /* Time input styling for dark mode - make clock icon visible */
    input[type="time"].kt-input,
    input[type="time"].grow {
        color-scheme: light dark;
    }
    
    .dark input[type="time"].kt-input,
    .dark input[type="time"].grow {
        color-scheme: dark;
    }
    
    /* Ensure the time picker icon is visible in both modes */
    input[type="time"].kt-input::-webkit-calendar-picker-indicator,
    input[type="time"].grow::-webkit-calendar-picker-indicator {
        filter: invert(0);
        opacity: 1;
        cursor: pointer;
        width: 20px;
        height: 20px;
    }
    
    .dark input[type="time"].kt-input::-webkit-calendar-picker-indicator,
    .dark input[type="time"].grow::-webkit-calendar-picker-indicator {
        filter: invert(1);
        opacity: 1;
    }
    
    /* Make the time icon in kt-input visible */
    .kt-input:has(input[type="time"]) .ki-time {
        color: var(--kt-text-muted);
        opacity: 0.7;
    }
    
    .dark .kt-input:has(input[type="time"]) .ki-time {
        color: var(--kt-text-muted);
        opacity: 0.8;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize datepicker elements
    const dateInput = document.getElementById('scheduled_at_display');
    const hiddenInput = document.getElementById('scheduled_at_hidden');
    const timeInput = document.getElementById('scheduled_time');

    // Function to convert DD-MM-YYYY to YYYY-MM-DD
    function convertToISODate(displayDate) {
        if (!displayDate) return '';
        const parts = displayDate.split('-');
        if (parts.length !== 3) return displayDate;
        return parts[2] + '-' + parts[1] + '-' + parts[0];
    }

    // Function to update hidden input with date and time combined
    function updateHiddenInput() {
        if (!hiddenInput || !dateInput) return;

        // Get date from display input (DD-MM-YYYY format) and convert to YYYY-MM-DD
        let dateValue = convertToISODate(dateInput.value);
        
        // Fallback: get date from current hidden input value
        if (!dateValue) {
            const currentHiddenValue = hiddenInput.value.trim();
            if (currentHiddenValue) {
                dateValue = currentHiddenValue.split(' ')[0];
            }
        }

        // Get current time value
        const currentTime = timeInput ? timeInput.value.trim() : '';

        // Update hidden input with date and time
        if (dateValue) {
            if (currentTime) {
                hiddenInput.value = dateValue + ' ' + currentTime;
            } else {
                hiddenInput.value = dateValue;
            }
        }
    }

    // Watch for date input value changes
    if (dateInput) {
        let lastDateValue = dateInput.value;
        
        // Poll for value changes (KT datepicker may update value without firing change event)
        setInterval(() => {
            if (dateInput.value !== lastDateValue) {
                lastDateValue = dateInput.value;
                updateHiddenInput();
            }
        }, 200);
        
        // Also listen for change events
        dateInput.addEventListener('change', updateHiddenInput);
        
        // Watch for clicks on the datepicker calendar and update after selection
        document.addEventListener('click', function(e) {
            // Check if click was on a datepicker day cell (vanilla-calendar uses vc-date__btn class)
            if (e.target.classList.contains('vc-date__btn') || 
                e.target.closest('.vc-date__btn') ||
                e.target.closest('.vc')) {
                // Wait for the datepicker to update the input
                setTimeout(() => {
                    if (dateInput.value !== lastDateValue) {
                        lastDateValue = dateInput.value;
                        updateHiddenInput();
                    }
                }, 100);
            }
        });
    }

    // Update hidden input when time changes
    if (timeInput) {
        timeInput.addEventListener('change', updateHiddenInput);
        timeInput.addEventListener('input', updateHiddenInput);
        
        // Always show the time picker dropdown when clicking on the input or icon
        const timeInputWrapper = timeInput.closest('.kt-input');
        if (timeInputWrapper) {
            // Click on the wrapper (including icon) should open the picker
            timeInputWrapper.addEventListener('click', function(e) {
                // Only if not clicking directly on the input itself (to avoid double trigger)
                if (e.target !== timeInput) {
                    e.preventDefault();
                    timeInput.focus();
                    // Use showPicker() if available (modern browsers)
                    if (timeInput.showPicker) {
                        try {
                            timeInput.showPicker();
                        } catch (err) {
                            // Fallback: just focus
                            timeInput.focus();
                        }
                    }
                }
            });
        }
        
        // Also open picker when clicking directly on the input
        timeInput.addEventListener('click', function(e) {
            // Use showPicker() if available (modern browsers)
            if (timeInput.showPicker) {
                try {
                    timeInput.showPicker();
                } catch (err) {
                    // Fallback: just focus (browser will show picker on focus)
                }
            }
        });
        
        // Open picker on focus as well
        timeInput.addEventListener('focus', function() {
            if (timeInput.showPicker) {
                try {
                    timeInput.showPicker();
                } catch (err) {
                    // Fallback: browser will show picker on focus
                }
            }
        });
    }

    // Get company ID and location ID from interview
    const companyId = @json($interview->company_id);
    const interviewLocationId = @json($interview->company_location_id);
    const interviewLocation = @json($interview->location);
    const interviewerSelect = document.getElementById('interviewer_name');
    const emailInput = document.getElementById('interviewer_email');
    const locationSelect = document.getElementById('company_location_id');
    
    // Function to load users for a company
    function loadUsersForCompany(companyId) {
        if (!interviewerSelect || !companyId) {
            return;
        }
        
        // Save current selected value before clearing
        const currentValue = interviewerSelect.value;
        
        // Clear current options except the first one
        while (interviewerSelect.options.length > 1) {
            interviewerSelect.remove(1);
        }
        
        // Fetch users from API
        fetch(`/admin/companies/${companyId}/users/json`)
            .then(response => response.json())
            .then(data => {
                if (data.users && data.users.length > 0) {
                    data.users.forEach(user => {
                        const fullName = `${user.first_name || ''} ${user.last_name || ''}`.trim();
                        const option = document.createElement('option');
                        option.value = fullName;
                        option.textContent = fullName;
                        option.setAttribute('data-email', user.email || '');
                        option.setAttribute('data-user-id', user.id || '');
                        // Select if it matches the current value
                        if (currentValue === fullName) {
                            option.selected = true;
                            // Set email and user_id if option is selected
                            if (emailInput && user.email) {
                                emailInput.value = user.email;
                            }
                            const interviewerUserIdInput = document.getElementById('interviewer_user_id');
                            if (interviewerUserIdInput && user.id) {
                                interviewerUserIdInput.value = user.id;
                            }
                        }
                        interviewerSelect.appendChild(option);
                    });
                    
                    // Reinitialize kt-select
                    if (typeof KTComponents !== 'undefined' && KTComponents.Select) {
                        const selectInstance = KTComponents.getInstance(interviewerSelect);
                        if (selectInstance) {
                            selectInstance.destroy();
                        }
                        new KTComponents.Select(interviewerSelect);
                        
                        // Update email after kt-select is reinitialized
                        setTimeout(function() {
                            if (typeof updateInterviewerEmail === 'function') {
                                updateInterviewerEmail();
                            }
                        }, 200);
                    } else {
                        // Fallback: update email directly
                        if (typeof updateInterviewerEmail === 'function') {
                            updateInterviewerEmail();
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error loading users:', error);
            });
    }
    
    // Handle interviewer selection change
    // Function to update interviewer email and user_id based on selected option
    function updateInterviewerEmail() {
        if (!interviewerSelect || !emailInput) return;
        
        const selectedOption = interviewerSelect.options[interviewerSelect.selectedIndex];
        const interviewerUserIdInput = document.getElementById('interviewer_user_id');
        
        if (selectedOption && selectedOption.hasAttribute('data-email')) {
            emailInput.value = selectedOption.getAttribute('data-email');
            
            // Also set interviewer_user_id if available
            if (selectedOption.hasAttribute('data-user-id') && interviewerUserIdInput) {
                interviewerUserIdInput.value = selectedOption.getAttribute('data-user-id');
            }
        } else {
            emailInput.value = '';
            if (interviewerUserIdInput) {
                interviewerUserIdInput.value = '';
            }
        }
    }
    
    if (interviewerSelect && emailInput) {
        // Listen to change event on native select
        interviewerSelect.addEventListener('change', updateInterviewerEmail);
        
        // Also listen to clicks on kt-select dropdown items (for kt-select component)
        document.addEventListener('click', function(e) {
            const clickedItem = e.target.closest('[data-kt-select-option]');
            if (clickedItem && clickedItem.closest('.kt-select-wrapper') && 
                clickedItem.closest('.kt-select-wrapper').querySelector('select') === interviewerSelect) {
                // Wait a bit for kt-select to update the native select
                setTimeout(updateInterviewerEmail, 100);
            }
        });
        
        // Also check on form submit to ensure email is set
        const form = interviewerSelect.closest('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                updateInterviewerEmail();
            });
        }
        
        // Update email if interviewer is already selected on page load
        if (interviewerSelect.value) {
            setTimeout(updateInterviewerEmail, 500);
        }
    }
    
    // Load users and locations for the interview's company
    if (companyId) {
        loadUsersForCompany(companyId);
        // Use interview's location_id directly from PHP data
        // If location is "Op afstand", use 'remote' as the ID
        let currentLocationId = interviewLocationId;
        if (interviewLocation === 'Op afstand' || interviewLocationId === -1) {
            currentLocationId = 'remote';
        }
        loadLocationsForCompany(companyId, currentLocationId, interviewLocation);
    }
    
    // Function to load locations for a company (if company can be changed in future)
    function loadLocationsForCompany(companyId, selectedLocationId = null, savedLocationText = null) {
        if (!locationSelect || !companyId) {
            return;
        }
        
        // Clear location options
        locationSelect.innerHTML = '<option value="">Selecteer locatie</option>';
        
        // Track if we found a match by text (for when selectedLocationId is null)
        let matchedByText = null;
        
        // Fetch locations from API
        fetch(`/admin/companies/${companyId}/locations/json`)
            .then(response => response.json())
            .then(data => {
                // Add main address option if available
                if (data.mainAddress) {
                    const option = document.createElement('option');
                    option.value = '0';
                    let mainText = data.mainAddress.name || 'Hoofdadres';
                    if (data.mainAddress.city) {
                        mainText += ' - ' + data.mainAddress.city;
                    }
                    mainText += ' (Hoofdadres)';
                    option.textContent = mainText;
                    
                    // Check if selectedLocationId is 0 or '0' for main address
                    const selectedLocationIdStr = selectedLocationId !== null && selectedLocationId !== undefined ? String(selectedLocationId) : null;
                    let shouldSelect = selectedLocationIdStr === '0';
                    
                    // If no selectedLocationId, try to match by text (check if saved location contains main address info)
                    if (!shouldSelect && selectedLocationId === null && savedLocationText) {
                        const mainName = data.mainAddress.name || '';
                        const mainStreet = data.mainAddress.street || '';
                        const mainCity = data.mainAddress.city || '';
                        if ((mainName && savedLocationText.includes(mainName)) || 
                            (mainStreet && savedLocationText.includes(mainStreet)) ||
                            (mainCity && savedLocationText.includes(mainCity))) {
                            shouldSelect = true;
                            matchedByText = '0';
                        }
                    }
                    
                    if (shouldSelect) {
                        option.selected = true;
                    }
                    locationSelect.appendChild(option);
                }
                
                // Add other locations
                if (data.locations && data.locations.length > 0) {
                    data.locations.forEach(location => {
                        const option = document.createElement('option');
                        option.value = location.id;
                        let locText = location.name;
                        if (location.city) {
                            locText += ' - ' + location.city;
                        }
                        option.textContent = locText;
                        
                        // Check if this location should be selected by ID
                        const selectedLocationIdStr = selectedLocationId !== null && selectedLocationId !== undefined ? String(selectedLocationId) : null;
                        const locationIdStr = String(location.id);
                        let shouldSelect = selectedLocationIdStr === locationIdStr;
                        
                        // If no selectedLocationId and not already matched, try to match by text
                        if (!shouldSelect && selectedLocationId === null && savedLocationText && !matchedByText) {
                            if (savedLocationText.includes(location.name)) {
                                shouldSelect = true;
                                matchedByText = locationIdStr;
                            }
                        }
                        
                        if (shouldSelect) {
                            option.selected = true;
                        }
                        locationSelect.appendChild(option);
                    });
                }
                
                // Add "Op afstand" option
                const remoteOption = document.createElement('option');
                remoteOption.value = 'remote';
                remoteOption.textContent = 'Op afstand';
                const selectedLocationIdStr = selectedLocationId !== null && selectedLocationId !== undefined ? String(selectedLocationId) : null;
                // Check if selectedLocationId is -1, '-1', or 'remote' for "Op afstand"
                let shouldSelectRemote = selectedLocationIdStr === 'remote' || selectedLocationIdStr === '-1' || selectedLocationId === -1;
                
                // If no selectedLocationId and not matched, check if location text is "Op afstand"
                if (!shouldSelectRemote && selectedLocationId === null && savedLocationText && !matchedByText) {
                    if (savedLocationText === 'Op afstand') {
                        shouldSelectRemote = true;
                        matchedByText = 'remote';
                    }
                }
                
                if (shouldSelectRemote) {
                    remoteOption.selected = true;
                }
                locationSelect.appendChild(remoteOption);
                
                // Set selected value after options are added
                const effectiveLocationId = matchedByText || selectedLocationId;
                if (effectiveLocationId !== null && effectiveLocationId !== undefined) {
                    // Convert -1 or '-1' to 'remote' for the form
                    let locationIdStr = String(effectiveLocationId);
                    if (locationIdStr === '-1' || effectiveLocationId === -1) {
                        locationIdStr = 'remote';
                    }
                    // Set value immediately
                    locationSelect.value = locationIdStr;
                    
                    // Find the selected option text for display
                    const selectedOption = locationSelect.querySelector(`option[value="${locationIdStr}"]`);
                    if (selectedOption) {
                        selectedOption.selected = true;
                    }
                }
                
                // Reinitialize kt-select with proper initialization
                if (typeof KTComponents !== 'undefined' && KTComponents.Select) {
                    const selectInstance = KTComponents.getInstance(locationSelect);
                    if (selectInstance) {
                        selectInstance.destroy();
                    }
                    // Small delay to ensure DOM is ready
                    setTimeout(() => {
                        new KTComponents.Select(locationSelect);
                        // Update display after reinitialization
                        const newInstance = KTComponents.getInstance(locationSelect);
                        if (newInstance && newInstance.update) {
                            newInstance.update();
                        }
                    }, 50);
                }
            })
            .catch(error => {
                console.error('Error loading locations:', error);
            });
    }
    
    // Note: Locations are already loaded in the blade template, but this function is available if needed
});
</script>
@endpush

@push('styles')
<style>
    /* Zorg dat select dropdowns boven blur/overlay elementen verschijnen */
    .kt-select-dropdown,
    .kt-select-dropdown[data-kt-select-dropdown],
    [data-kt-select-dropdown] {
        z-index: 100000 !important;
        position: fixed !important;
    }
    
    .kt-select-options,
    .kt-select-options[data-kt-select-options],
    [data-kt-select-options] {
        z-index: 100001 !important;
        position: relative !important;
    }
    
    /* Zorg dat de dropdown wrapper ook de juiste z-index heeft */
    .kt-select-wrapper {
        position: relative !important;
    }
    
    .kt-select-wrapper .kt-select-dropdown.open,
    .kt-select-wrapper .kt-select-dropdown[data-kt-select-dropdown].open {
        z-index: 100000 !important;
    }
</style>
@endpush

@endsection
