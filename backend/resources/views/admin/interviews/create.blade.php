@extends('admin.layouts.app')

@section('title', 'Nieuw Interview')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono mb-3">
                Nieuw Interview
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
    </div>
</div>

@if(session('success'))
    <div class="kt-container-fixed">
        <div class="kt-alert kt-alert-success mb-5 auto-dismiss" role="alert" id="success-alert">
            <i class="ki-filled ki-check-circle me-2"></i>
            {{ session('success') }}
        </div>
    </div>
@endif

{{-- Errors are shown inline with fields, no general error alert needed --}}

<div class="kt-container-fixed">
    <div class="flex flex-col items-stretch grow">
        <form action="{{ route('admin.interviews.store') }}" method="POST" class="flex flex-col gap-5 lg:gap-7.5" novalidate>
            @csrf
            @if(isset($prefilledData['notification_id']) && $prefilledData['notification_id'])
                <input type="hidden" name="notification_id" value="{{ $prefilledData['notification_id'] }}">
            @endif

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
                                Match <span class="text-destructive">*</span>
                            </label>
                            <div class="flex flex-col" style="flex: 1;">
                                <div class="flex items-center gap-2" style="display: inline-flex; align-items: center;">
                                    <div style="position: relative; display: inline-block; width: auto; min-width: 400px;">
                                        <select id="match_id"
                                                name="match_id"
                                                class="kt-select @error('match_id') border-destructive @enderror"
                                                data-kt-select="true"
                                                style="width: auto; min-width: 400px; max-width: 100%;"
                                                required>
                                            <option value="">Selecteer match</option>
                                            @foreach($matches ?? [] as $match)
                                                <option value="{{ $match->id }}" {{ old('match_id', $prefilledData['match_id'] ?? null) == $match->id ? 'selected' : '' }}>
                                                    {{ $match->candidate->first_name ?? '' }} {{ $match->candidate->last_name ?? '' }} - {{ $match->vacancy->title ?? '' }}
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

                    @if(auth()->user()->hasRole('super-admin'))
                    <div class="w-full">
                        <div class="flex items-start py-3">
                            <label class="kt-form-label flex items-center gap-1 max-w-56 pt-2">
                                Bedrijf <span class="text-destructive">*</span>
                            </label>
                            <div class="flex flex-col" style="flex: 1;">
                                <div class="flex items-center gap-2" style="display: inline-flex; align-items: center;">
                                    <div style="position: relative; display: inline-block; width: auto; min-width: 200px;">
                                        <select id="company_id"
                                                name="company_id"
                                                class="kt-select @error('company_id') border-destructive @enderror"
                                                data-kt-select="true"
                                                style="width: auto; min-width: 200px; max-width: 100%;"
                                                required>
                                            <option value="">Selecteer bedrijf</option>
                                            @foreach($companies ?? [] as $company)
                                                <option value="{{ $company->id }}" {{ old('company_id', $prefilledData['company_id'] ?? null) == $company->id ? 'selected' : '' }}>
                                                    {{ $company->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('company_id')
                                        <div class="validation-icon-wrapper" style="display: flex; align-items: center; justify-content: center; width: 1.25rem; height: 1.25rem; flex-shrink: 0;">
                                            <i class="ki-filled ki-cross-circle text-destructive" style="font-size: 1.25rem;"></i>
                                        </div>
                                    @enderror
                                </div>
                                @error('company_id')
                                    <div class="text-xs text-destructive mt-1">Dit veld is verplicht!</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    @else
                        <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
                    @endif

                    <div class="w-full">
                        <div class="flex items-start py-3">
                            <label class="kt-form-label flex items-center gap-1 max-w-56 pt-2">
                                Type <span class="text-destructive">*</span>
                            </label>
                            <div class="flex flex-col" style="flex: 1;">
                                <div class="flex items-center gap-2" style="display: inline-flex; align-items: center;">
                                    <div style="position: relative; display: inline-block; width: auto; min-width: 200px;">
                                        <select id="type"
                                                name="type"
                                                class="kt-select @error('type') border-destructive @enderror"
                                                data-kt-select="true"
                                                style="width: auto; min-width: 200px; max-width: 100%;"
                                                required>
                                            <option value="">Selecteer type</option>
                                            <option value="phone" {{ old('type') == 'phone' ? 'selected' : '' }}>Telefoon</option>
                                            <option value="video" {{ old('type') == 'video' ? 'selected' : '' }}>Video</option>
                                            <option value="onsite" {{ old('type') == 'onsite' ? 'selected' : '' }}>Op locatie</option>
                                            <option value="assessment" {{ old('type') == 'assessment' ? 'selected' : '' }}>Assessment</option>
                                            <option value="final" {{ old('type') == 'final' ? 'selected' : '' }}>Eindgesprek</option>
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
                                                   value="{{ old('scheduled_at') ? \Carbon\Carbon::parse(old('scheduled_at'))->format('d-m-Y') : (isset($prefilledData['scheduled_at']) ? \Carbon\Carbon::parse($prefilledData['scheduled_at'])->format('d-m-Y') : (isset($prefilledData['scheduled_date']) ? \Carbon\Carbon::parse($prefilledData['scheduled_date'])->format('d-m-Y') : '')) }}"/>
                                            <input type="hidden"
                                                   name="scheduled_at"
                                                   id="scheduled_at_hidden"
                                                   value="{{ old('scheduled_at') ? \Carbon\Carbon::parse(old('scheduled_at'))->format('Y-m-d') : (isset($prefilledData['scheduled_at']) ? \Carbon\Carbon::parse($prefilledData['scheduled_at'])->format('Y-m-d') : (isset($prefilledData['scheduled_date']) ? $prefilledData['scheduled_date'] : '')) }}"/>
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
                                               value="{{ old('scheduled_time', old('scheduled_at') ? \Carbon\Carbon::parse(old('scheduled_at'))->format('H:i') : (isset($prefilledData['scheduled_at']) ? \Carbon\Carbon::parse($prefilledData['scheduled_at'])->format('H:i') : (isset($prefilledData['scheduled_time']) ? $prefilledData['scheduled_time'] : ''))) }}">
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

                    <div class="w-full">
                        <div class="flex items-start py-3">
                            <label class="kt-form-label flex items-center gap-1 max-w-56 pt-2">
                                Duur (minuten)
                            </label>
                            <input type="number"
                                   class="kt-input @error('duration') border-destructive @enderror"
                                   id="duration" name="duration"
                                   value="{{ old('duration', 60) }}"
                                   style="width: auto; min-width: 100px;"
                                   min="15" max="480">
                            @error('duration')
                                <span class="text-xs text-destructive mt-1">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="w-full">
                        <div class="flex items-start py-3">
                            <label class="kt-form-label flex items-center gap-1 max-w-56 pt-2">
                                Status <span class="text-destructive">*</span>
                            </label>
                            <div class="flex flex-col" style="flex: 1;">
                                <div class="flex items-center gap-2" style="display: inline-flex; align-items: center;">
                                    <div style="position: relative; display: inline-block; width: auto; min-width: 200px;">
                                        <select id="status"
                                                name="status"
                                                class="kt-select @error('status') border-destructive @enderror"
                                                data-kt-select="true"
                                                style="width: auto; min-width: 200px; max-width: 100%;"
                                                required>
                                            <option value="">Selecteer status</option>
                                            <option value="scheduled" {{ old('status') == 'scheduled' ? 'selected' : '' }}>Gepland</option>
                                            <option value="confirmed" {{ old('status') == 'confirmed' ? 'selected' : '' }}>Bevestigd</option>
                                            <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Voltooid</option>
                                            <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Geannuleerd</option>
                                            <option value="rescheduled" {{ old('status') == 'rescheduled' ? 'selected' : '' }}>Herpland</option>
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
                                        $hasMainLocationInList = $mainLocation && $companyLocations->contains('id', $mainLocation->id);
                                        $hasCompanyAddress = $selectedCompany->street || $selectedCompany->city;
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
                                        {{-- Use value "0" for main address (location_id 0 = hoofdadres) --}}
                                        <option value="0" {{ old('company_location_id', $prefilledData['location_id'] ?? null) == 0 || old('company_location_id', $prefilledData['location_id'] ?? null) == '0' ? 'selected' : '' }}>
                                            {{ $mainDisplayName }}
                                        </option>
                                    @elseif(!$mainLocation && $hasCompanyAddress)
                                        @php
                                            $companyAddress = trim(($selectedCompany->street ?? '') . ' ' . ($selectedCompany->house_number ?? '') . ($selectedCompany->house_number_extension ? '-' . $selectedCompany->house_number_extension : ''));
                                            $companyAddress = trim($companyAddress . ' ' . ($selectedCompany->postal_code ?? '') . ' ' . ($selectedCompany->city ?? ''));
                                            $companyDisplayName = $selectedCompany->name;
                                            if ($selectedCompany->city) {
                                                $companyDisplayName .= ' - ' . $selectedCompany->city;
                                            }
                                            $companyDisplayName .= ' (Hoofdadres)';
                                        @endphp
                                        {{-- Use value "0" for main address (location_id 0 = hoofdadres) --}}
                                        <option value="0" {{ old('company_location_id', $prefilledData['location_id'] ?? null) == 0 || old('company_location_id', $prefilledData['location_id'] ?? null) == '0' ? 'selected' : '' }}>
                                            {{ $companyDisplayName }}
                                        </option>
                                    @endif
                                @endif
                                @if(isset($companyLocations) && $companyLocations->count() > 0)
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
                                        <option value="{{ $location->id }}" {{ old('company_location_id', $prefilledData['location_id'] ?? null) == $location->id ? 'selected' : '' }}>
                                            {{ $locationDisplayName }}
                                        </option>
                                    @endforeach
                                @endif
                                {{-- Add "Op afstand" option --}}
                                @php
                                    $isRemoteSelected = old('company_location_id', $prefilledData['location_id'] ?? null) == 'remote' || 
                                                         old('company_location_id', $prefilledData['location_id'] ?? null) == '-1' ||
                                                         old('company_location_id', $prefilledData['location_id'] ?? null) == -1;
                                @endphp
                                <option value="remote" {{ $isRemoteSelected ? 'selected' : '' }}>
                                    Op afstand
                                </option>
                            </select>
                                    </select>
                                </div>
                                @error('company_location_id')
                                    <div class="validation-icon-wrapper" style="display: flex; align-items: center; justify-content: center; width: 1.25rem; height: 1.25rem; flex-shrink: 0;">
                                        <i class="ki-filled ki-cross-circle text-destructive" style="font-size: 1.25rem;"></i>
                                    </div>
                                @enderror
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
                                                        {{ old('interviewer_name') == ($user->first_name . ' ' . $user->last_name) ? 'selected' : '' }}>
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
                                               value="{{ old('interviewer_email') }}"
                                               style="width: auto; min-width: 400px; max-width: 100%;"
                                               readonly
                                               required>
                                    </div>
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
                                          id="notes" name="notes" rows="4">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <span class="text-xs text-destructive mt-1">{{ $message }}</span>
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
                                          id="feedback" name="feedback" rows="4">{{ old('feedback') }}</textarea>
                                @error('feedback')
                                    <span class="text-xs text-destructive mt-1">{{ $message }}</span>
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
                    Interview Opslaan
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
    #match_id + .kt-select-wrapper .kt-select-display.kt-select {
        min-width: 400px !important;
        width: auto !important;
        max-width: 100% !important;
    }
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

    /* Company field - 200px min-width */
    #company_id + .kt-select-wrapper,
    #company_id + .kt-select-wrapper .kt-select-display,
    #company_id.kt-select,
    #company_id + .kt-select-wrapper .kt-select-display.kt-select {
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
        // Get initial date value if prefilled (before flatpickr initialization)
        // Use string format (Y-m-d) for flatpickr, not Date object
        let initialDateStr = null;
        @if(isset($prefilledData) && !empty($prefilledData))
            const prefilledDataForDate = @json($prefilledData);
            if (prefilledDataForDate.scheduled_at) {
                // Parse scheduled_at (format: Y-m-d H:i:s or Y-m-d\TH:i or d-m-Y H:i)
                // Extract date part only (Y-m-d)
                const dateTimeStr = prefilledDataForDate.scheduled_at.replace('T', ' ');
                const datePart = dateTimeStr.split(' ')[0];
                // Check if date is in dd-mm-YYYY format and convert to YYYY-mm-dd
                if (datePart.match(/^\d{2}-\d{2}-\d{4}$/)) {
                    // Format: dd-mm-YYYY -> convert to YYYY-mm-dd
                    const [day, month, year] = datePart.split('-');
                    initialDateStr = `${year}-${month}-${day}`;
                } else {
                    // Assume it's already in Y-m-d format
                    initialDateStr = datePart;
                }
            } else if (prefilledDataForDate.scheduled_date) {
                // scheduled_date might be in d-m-Y format (dd-mm-YYYY)
                const dateStr = prefilledDataForDate.scheduled_date;
                // Check if date is in dd-mm-YYYY format and convert to YYYY-mm-dd
                if (dateStr.match(/^\d{2}-\d{2}-\d{4}$/)) {
                    // Format: dd-mm-YYYY -> convert to YYYY-mm-dd
                    const [day, month, year] = dateStr.split('-');
                    initialDateStr = `${year}-${month}-${day}`;
                } else {
                    // Assume it's already in Y-m-d format
                    initialDateStr = dateStr;
                }
            }
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
                // Set initial date if prefilled
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

        // Update hidden input when date field loses focus (blur)
        dateInput.addEventListener('blur', function() {
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

    // Prefill form fields from query parameters
    @if(isset($prefilledData) && !empty($prefilledData))
        const prefilledData = @json($prefilledData);
        
        // Debug: log prefilled data (only if not already logged for date)
        if (typeof prefilledDataForDate === 'undefined') {
            console.log('Prefilled data:', prefilledData);
            console.log('Location ID from prefilled data:', prefilledData.location_id, 'Type:', typeof prefilledData.location_id);
        }

        // Prefill match_id if provided
        if (prefilledData.match_id) {
            const matchSelect = document.getElementById('match_id');
            if (matchSelect) {
                matchSelect.value = prefilledData.match_id;
                // Trigger change event to update dependent fields if needed
                matchSelect.dispatchEvent(new Event('change'));
            }
        }

        // Prefill company_id if provided
        if (prefilledData.company_id) {
            const companySelect = document.getElementById('company_id');
            if (companySelect) {
                companySelect.value = prefilledData.company_id;
                // Trigger change event to load locations for selected company
                companySelect.dispatchEvent(new Event('change'));
            }
        }

        // Load locations when company changes
        const companySelect = document.getElementById('company_id');
        const locationSelect = document.getElementById('company_location_id');

        function loadLocationsForCompany(companyId, selectedLocationId = null) {
            if (!locationSelect) return;

            // Clear location options
            locationSelect.innerHTML = '<option value="">Selecteer locatie</option>';

            if (companyId) {
                // Fetch locations for selected company
                fetch(`/admin/companies/${companyId}/locations/json`)
                    .then(response => response.json())
                    .then(data => {
                        // Add main address option if available
                        // Always use '0' for main address (location_id 0 = hoofdadres)
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
                            // Convert to string for comparison to handle both 0 and '0'
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
                                // Convert to string for comparison
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
                            console.log('Setting location_id to:', locationIdStr, 'from selectedLocationId:', selectedLocationId);
                        }

                        // Reinitialize kt-select
                        if (typeof KTComponents !== 'undefined' && KTComponents.Select) {
                            const selectInstance = KTComponents.getInstance(locationSelect);
                            if (selectInstance) {
                                selectInstance.destroy();
                            }
                            const newSelectInstance = new KTComponents.Select(locationSelect);

                            // Update display after initialization if value was set
                            if (selectedLocationId !== null && selectedLocationId !== undefined) {
                                setTimeout(function() {
                                    const locationIdStr = String(selectedLocationId);
                                    locationSelect.value = locationIdStr;
                                    // Force update of kt-select display
                                    if (newSelectInstance) {
                                        newSelectInstance.update();
                                    }
                                    // Also trigger change to update display
                                    locationSelect.dispatchEvent(new Event('change', { bubbles: true }));
                                }, 200);
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error loading locations:', error);
                    });
            }
        }

        // Function to load users for a company
        function loadUsersForCompany(companyId) {
            const interviewerSelect = document.getElementById('interviewer_name');
            const emailInput = document.getElementById('interviewer_email');
            
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
                            // Restore selection if it matches
                            if (currentValue === fullName) {
                                option.selected = true;
                                // Set email immediately if this option is selected
                                if (emailInput && user.email) {
                                    emailInput.value = user.email;
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
        const interviewerSelect = document.getElementById('interviewer_name');
        const emailInput = document.getElementById('interviewer_email');
        
        function updateInterviewerEmail() {
            if (!interviewerSelect || !emailInput) return;
            
            const selectedOption = interviewerSelect.options[interviewerSelect.selectedIndex];
            if (selectedOption && selectedOption.hasAttribute('data-email')) {
                emailInput.value = selectedOption.getAttribute('data-email');
            } else {
                emailInput.value = '';
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
            
            // Also update email when users are loaded dynamically
            const originalLoadUsers = loadUsersForCompany;
            if (typeof loadUsersForCompany === 'function') {
                loadUsersForCompany = function(companyId) {
                    originalLoadUsers(companyId);
                    // Update email after users are loaded
                    setTimeout(updateInterviewerEmail, 300);
                };
            }
        }
        
        if (companySelect && locationSelect) {
            companySelect.addEventListener('change', function() {
                loadLocationsForCompany(this.value);
                loadUsersForCompany(this.value);
            });

            // Load locations and users for initially selected company (from prefilled data or current selection)
            const initialCompanyId = prefilledData.company_id || companySelect.value;
            if (initialCompanyId) {
                // Wait a bit to ensure company select is initialized
                setTimeout(function() {
                    loadLocationsForCompany(initialCompanyId, prefilledData.location_id);
                    loadUsersForCompany(initialCompanyId);
                }, 200);
            }
        }

        // Prefill time if provided (date is already set in flatpickr initialization above)
        if (timeInput) {
            let timeValue = '';

            if (prefilledData.scheduled_at) {
                // Parse scheduled_at datetime string
                const date = new Date(prefilledData.scheduled_at);
                timeValue = date.toTimeString().slice(0, 5); // HH:mm
            } else if (prefilledData.scheduled_time) {
                timeValue = prefilledData.scheduled_time;
            }

            if (timeValue) {
                timeInput.value = timeValue;
                if (window.flatpickrInstance) {
                    updateHiddenInputFromDatepicker();
                }
            }
        }

        // Prefill location_id if provided (after locations are loaded)
        // This is now handled in loadLocationsForCompany function

        // Set status to "confirmed" if coming from notification
        if (prefilledData.notification_id) {
            const statusSelect = document.getElementById('status');
            if (statusSelect) {
                statusSelect.value = 'confirmed';
            }
        }
    @endif
});
</script>
@endpush

@push('styles')
<style>
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

@endsection
