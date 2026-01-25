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
                                            @foreach(\App\Models\JobMatch::with(['candidate', 'vacancy'])->get() as $match)
                                                <option value="{{ $match->id }}" {{ old('match_id', $interview->match_id) == $match->id ? 'selected' : '' }}>
                                                    {{ trim(($match->candidate->first_name ?? '') . ' ' . ($match->candidate->last_name ?? '')) ?: 'Onbekend' }} - {{ $match->vacancy->title }}
                                                </option>
                                            @endforeach
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
                                                   data-kt-date-picker-format="dd-mm-yyyy"
                                                   placeholder="Selecteer datum"
                                                   readonly
                                                   type="text"
                                                   value="{{ old('scheduled_at', $interview->scheduled_at ? $interview->scheduled_at->format('d-m-Y') : '') }}"/>
                                            <input type="hidden"
                                                   name="scheduled_at"
                                                   id="scheduled_at_hidden"
                                                   value="{{ old('scheduled_at', $interview->scheduled_at ? $interview->scheduled_at->format('Y-m-d') : '') }}"/>
                                        </div>
                                        @error('scheduled_at')
                                            <div class="validation-icon-wrapper" style="display: flex; align-items: center; justify-content: center; width: 1.25rem; height: 1.25rem; flex-shrink: 0;">
                                                <i class="ki-filled ki-cross-circle text-destructive" style="font-size: 1.25rem;"></i>
                                            </div>
                                        @enderror
                                    </div>
                                    <div class="flex items-center gap-2" style="display: inline-flex; align-items: center;">
                                        <input type="text"
                                               name="scheduled_time"
                                               id="scheduled_time"
                                               class="kt-input @error('scheduled_time') border-destructive @enderror"
                                               placeholder="hh:mm"
                                               maxlength="5"
                                               pattern="[0-9]{2}:[0-9]{2}"
                                               style="width: auto; min-width: 100px;"
                                               value="{{ old('scheduled_time', $interview->scheduled_at ? $interview->scheduled_at->format('H:i') : '') }}">
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
                                Duur (minuten)
                            </label>
                            <input type="number" 
                                   class="kt-input @error('duration') border-destructive @enderror" 
                                   id="duration" name="duration" 
                                   value="{{ old('duration', $interview->duration ?? 60) }}" 
                                   style="width: auto; min-width: 100px;"
                                   min="15" max="480">
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
                                            @foreach($companyUsers ?? [] as $user)
                                                <option value="{{ $user->first_name }} {{ $user->last_name }}" 
                                                        data-email="{{ $user->email }}"
                                                        data-user-id="{{ $user->id }}"
                                                        {{ old('interviewer_name', $interview->interviewer_name) == ($user->first_name . ' ' . $user->last_name) ? 'selected' : '' }}>
                                                    {{ $user->first_name }} {{ $user->last_name }}
                                                </option>
                                            @endforeach
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
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize datepicker
    const dateInput = document.getElementById('scheduled_at_display');
    const hiddenInput = document.getElementById('scheduled_at_hidden');
    const timeInput = document.getElementById('scheduled_time');

    // Function to update hidden input with combined date and time
    function updateHiddenInputFromDatepicker() {
        if (!hiddenInput) return;

        // Get the selected date from flatpickr instance
        let dateValue = '';
        if (window.flatpickrInstance && window.flatpickrInstance.selectedDates && window.flatpickrInstance.selectedDates.length > 0) {
            const selectedDate = window.flatpickrInstance.selectedDates[0];
            dateValue = selectedDate.getFullYear() + '-' +
                       String(selectedDate.getMonth() + 1).padStart(2, '0') + '-' +
                       String(selectedDate.getDate()).padStart(2, '0');
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
        }
    }

    if (dateInput && typeof flatpickr !== 'undefined') {
        // Get initial date value from interview
        let initialDateStr = null;
        @if($interview->scheduled_at)
            const interviewDate = @json($interview->scheduled_at->format('Y-m-d'));
            initialDateStr = interviewDate;
        @endif
        
        const fpConfig = {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd-m-Y',
            onChange: function(selectedDates, dateStr, instance) {
                // Immediately update hidden input when date is selected
                if (hiddenInput && selectedDates && selectedDates.length > 0) {
                    const selectedDate = selectedDates[0];
                    const formattedDate = selectedDate.getFullYear() + '-' +
                                        String(selectedDate.getMonth() + 1).padStart(2, '0') + '-' +
                                        String(selectedDate.getDate()).padStart(2, '0');
                    const currentTime = timeInput ? timeInput.value.trim() : '';

                    if (currentTime) {
                        const timeWithSeconds = currentTime.length === 5 ? currentTime + ':00' : currentTime;
                        hiddenInput.value = formattedDate + ' ' + timeWithSeconds;
                    } else {
                        hiddenInput.value = formattedDate;
                    }
                }
            },
            onOpen: function(selectedDates, dateStr, instance) {
                // When datepicker opens, ensure prefilled date is selected if not already selected
                if (initialDateStr) {
                    // Check if the date is already selected
                    const isDateSelected = selectedDates && selectedDates.length > 0 && 
                                         dateStr === initialDateStr;
                    if (!isDateSelected) {
                        instance.setDate(initialDateStr, false);
                    }
                }
            },
            onClose: function(selectedDates, dateStr, instance) {
                updateHiddenInputFromDatepicker();
            },
            onReady: function(selectedDates, dateStr, instance) {
                window.flatpickrInstance = instance;
                // Set initial date if available
                if (initialDateStr) {
                    instance.setDate(initialDateStr, false);
                }
                updateHiddenInputFromDatepicker();
            }
        };
        
        // Only set defaultDate if we have a valid initial date string
        if (initialDateStr) {
            fpConfig.defaultDate = initialDateStr;
        }
        
        const fp = flatpickr(dateInput, fpConfig);

        // Store instance globally
        window.flatpickrInstance = fp;
    }

    // Update hidden input when time changes - improved cursor handling
    if (timeInput) {
        let lastValue = timeInput.value || '';
        let cursorPosition = 0;
        
        timeInput.addEventListener('keydown', function(e) {
            // Store cursor position before changes
            cursorPosition = this.selectionStart || 0;
            
            // Handle arrow keys for navigation
            if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
                // Allow default behavior for arrow keys
                return;
            }
            
            // Handle backspace and delete
            if (e.key === 'Backspace' || e.key === 'Delete') {
                const currentPos = this.selectionStart || 0;
                const currentValue = this.value;
                
                // If deleting the colon, move cursor back
                if (currentPos === 3 && e.key === 'Backspace') {
                    e.preventDefault();
                    this.setSelectionRange(2, 2);
                    return;
                }
                
                // If deleting after colon, just delete the digit
                if (currentPos > 3 && e.key === 'Backspace') {
                    e.preventDefault();
                    const newValue = currentValue.substring(0, currentPos - 1) + currentValue.substring(currentPos);
                    this.value = newValue;
                    this.setSelectionRange(currentPos - 1, currentPos - 1);
                    updateHiddenInputFromDatepicker();
                    return;
                }
            }
        });
        
        timeInput.addEventListener('input', function(e) {
            const currentPos = this.selectionStart || 0;
            let value = e.target.value.replace(/[^\d]/g, '');
            
            // Limit to 4 digits
            if (value.length > 4) {
                value = value.slice(0, 4);
            }
            
            // Format with colon
            let formattedValue = '';
            if (value.length > 0) {
                formattedValue = value.slice(0, 2);
                if (value.length > 2) {
                    formattedValue += ':' + value.slice(2, 4);
                }
            }
            
            const oldValue = lastValue;
            lastValue = formattedValue;
            e.target.value = formattedValue;
            
            // Smart cursor positioning
            let newCursorPos = currentPos;
            
            // If we just added a colon (went from 2 to 3 chars), move cursor past colon
            if (oldValue.length === 2 && formattedValue.length === 3) {
                newCursorPos = 3; // Position after colon
            }
            // If we're typing in the hour section (position 0-2), keep cursor there
            else if (currentPos <= 2 && formattedValue.length >= 2) {
                newCursorPos = Math.min(currentPos, 2);
            }
            // If we're typing in the minute section (position 3-5), keep cursor there
            else if (currentPos >= 3 && formattedValue.length >= 3) {
                // Adjust position based on how many characters were added
                const addedChars = formattedValue.length - oldValue.length;
                newCursorPos = Math.min(currentPos + addedChars, formattedValue.length);
            }
            
            // Set cursor position
            setTimeout(() => {
                this.setSelectionRange(newCursorPos, newCursorPos);
            }, 0);

            // Update hidden input with combined date and time
            updateHiddenInputFromDatepicker();
        });
        
        timeInput.addEventListener('click', function(e) {
            // On click, position cursor intelligently
            const clickPos = e.target.selectionStart || 0;
            if (clickPos <= 2) {
                // Clicked in hour section
                this.setSelectionRange(0, 2);
            } else if (clickPos >= 3) {
                // Clicked in minute section
                this.setSelectionRange(3, 5);
            }
        });
        
        timeInput.addEventListener('focus', function(e) {
            // On focus, select the hour part if empty, or position cursor at start
            if (!this.value || this.value.length === 0) {
                this.setSelectionRange(0, 0);
            } else {
                // Position cursor at the start of the focused section
                const pos = this.selectionStart || 0;
                if (pos <= 2) {
                    this.setSelectionRange(0, 2);
                } else {
                    this.setSelectionRange(3, 5);
                }
            }
        });

        timeInput.addEventListener('blur', function() {
            // Validate and format on blur
            const value = this.value.trim();
            if (value && value.length === 4 && !value.includes(':')) {
                // If user entered 4 digits without colon, add it
                this.value = value.slice(0, 2) + ':' + value.slice(2, 4);
            } else if (value && value.length === 2 && !value.includes(':')) {
                // If user entered 2 digits, add colon
                this.value = value + ':';
            }
            updateHiddenInputFromDatepicker();
        });
    }

    // Get company ID from interview
    const companyId = @json($interview->company_id);
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
        // Get current location_id from the select or interview data
        const currentLocationId = locationSelect ? locationSelect.value : null;
        loadLocationsForCompany(companyId, currentLocationId);
    }
    
    // Function to load locations for a company (if company can be changed in future)
    function loadLocationsForCompany(companyId, selectedLocationId = null) {
        if (!locationSelect || !companyId) {
            return;
        }
        
        // Clear location options
        locationSelect.innerHTML = '<option value="">Selecteer locatie</option>';
        
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
                    if (selectedLocationIdStr === '0') {
                        option.selected = true;
                    }
                    locationSelect.appendChild(option);
                }
                
                // Add other locations
                if (data.locations && data.locations.length > 0) {
                    data.locations.forEach(location => {
                        const option = document.createElement('option');
                        option.value = location.id;
                        let locationText = location.name;
                        if (location.city) {
                            locationText += ' - ' + location.city;
                        }
                        option.textContent = locationText;
                        // Check if this location should be selected
                        const selectedLocationIdStr = selectedLocationId !== null && selectedLocationId !== undefined ? String(selectedLocationId) : null;
                        const locationIdStr = String(location.id);
                        if (selectedLocationIdStr === locationIdStr) {
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
                if (selectedLocationIdStr === 'remote' || selectedLocationIdStr === '-1' || selectedLocationId === -1) {
                    remoteOption.selected = true;
                }
                locationSelect.appendChild(remoteOption);
                
                // Set selected value after options are added
                if (selectedLocationId !== null && selectedLocationId !== undefined) {
                    // Convert -1 or '-1' to 'remote' for the form
                    let locationIdStr = String(selectedLocationId);
                    if (locationIdStr === '-1' || selectedLocationId === -1) {
                        locationIdStr = 'remote';
                    }
                    // Set value immediately
                    locationSelect.value = locationIdStr;
                }
                
                // Reinitialize kt-select
                if (typeof KTComponents !== 'undefined' && KTComponents.Select) {
                    const selectInstance = KTComponents.getInstance(locationSelect);
                    if (selectInstance) {
                        selectInstance.destroy();
                    }
                    new KTComponents.Select(locationSelect);
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
