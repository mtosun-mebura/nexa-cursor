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

    <form action="{{ route('admin.notifications.store') }}" method="POST" enctype="multipart/form-data" novalidate>
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
                                <div class="flex flex-col">
                                    <div class="flex items-center gap-2" style="display: inline-flex; align-items: center;">
                                        <div style="position: relative; display: inline-block; width: auto; min-width: 300px;">
                                            <select class="kt-select @error('user_id') border-destructive @enderror" 
                                                    id="user_id" 
                                                    name="user_id" 
                                                    data-kt-select="true"
                                                    style="width: auto; min-width: 300px; max-width: 100%;"
                                                    required>
                                                <option value="">Selecteer ontvanger</option>
                                                
                                                @if($backendUsers->count() > 0)
                                                    <optgroup label="Gebruikers van het bedrijf">
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
                                        </div>
                                        @error('user_id')
                                            <div class="validation-icon-wrapper" style="display: flex; align-items: center; justify-content: center; width: 1.25rem; height: 1.25rem; flex-shrink: 0;">
                                                <i class="ki-filled ki-cross-circle text-destructive" style="font-size: 1.25rem;"></i>
                                            </div>
                                        @enderror
                                    </div>
                                    @error('user_id')
                                        <div class="text-xs text-destructive mt-1">Dit veld is verplicht!</div>
                                    @enderror
                                    <div class="text-xs text-muted-foreground mt-1">
                                        Selecteer een backend gebruiker van je bedrijf of een kandidaat die heeft gesolliciteerd op je vacatures
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Categorie *
                            </td>
                            <td>
                                <div class="flex flex-col">
                                    <div class="flex items-center gap-2" style="display: inline-flex; align-items: center;">
                                        <div style="position: relative; display: inline-block; width: auto; min-width: 200px;">
                                            <select class="kt-select @error('category') border-destructive @enderror" 
                                                    id="category" 
                                                    name="category" 
                                                    data-kt-select="true"
                                                    style="width: auto; min-width: 200px; max-width: 100%;"
                                                    required>
                                                <option value="">Selecteer categorie</option>
                                                <option value="info" {{ old('category', 'info') == 'info' ? 'selected' : '' }}>Informatie</option>
                                                <option value="warning" {{ old('category') == 'warning' ? 'selected' : '' }}>Waarschuwing</option>
                                                <option value="success" {{ old('category') == 'success' ? 'selected' : '' }}>Succes</option>
                                                <option value="error" {{ old('category') == 'error' ? 'selected' : '' }}>Fout</option>
                                                <option value="reminder" {{ old('category') == 'reminder' ? 'selected' : '' }}>Herinnering</option>
                                                <option value="update" {{ old('category') == 'update' ? 'selected' : '' }}>Update</option>
                                            </select>
                                        </div>
                                        @error('category')
                                            <div class="validation-icon-wrapper" style="display: flex; align-items: center; justify-content: center; width: 1.25rem; height: 1.25rem; flex-shrink: 0;">
                                                <i class="ki-filled ki-cross-circle text-destructive" style="font-size: 1.25rem;"></i>
                                            </div>
                                        @enderror
                                    </div>
                                    @error('category')
                                        <div class="text-xs text-destructive mt-1">Dit veld is verplicht!</div>
                                    @enderror
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Type *
                            </td>
                            <td>
                                <div class="flex flex-col">
                                    <div class="flex items-center gap-2" style="display: inline-flex; align-items: center;">
                                        <div style="position: relative; display: inline-block; width: auto; min-width: 200px;">
                                            <select class="kt-select @error('type') border-destructive @enderror" 
                                                    id="type" 
                                                    name="type" 
                                                    data-kt-select="true"
                                                    style="width: auto; min-width: 200px; max-width: 100%;"
                                                    required>
                                                <option value="">Selecteer type</option>
                                                <option value="match" {{ old('type') == 'match' ? 'selected' : '' }}>Match</option>
                                                <option value="interview" {{ old('type') == 'interview' ? 'selected' : '' }}>Sollicitatie</option>
                                                <option value="system" {{ old('type') == 'system' ? 'selected' : '' }}>Systeem</option>
                                                <option value="email" {{ old('type') == 'email' ? 'selected' : '' }}>E-mail</option>
                                                <option value="reminder" {{ old('type') == 'reminder' ? 'selected' : '' }}>Herinnering</option>
                                                <option value="file" {{ old('type') == 'file' ? 'selected' : '' }}>Bestand</option>
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
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Titel *
                            </td>
                            <td>
                                <div class="flex flex-col">
                                    <div class="flex items-center gap-2" style="display: inline-flex; align-items: center;">
                                        <input type="text" 
                                               class="kt-input @error('title') border-destructive @enderror" 
                                               name="title" 
                                               id="title"
                                               value="{{ old('title') }}" 
                                               required
                                               placeholder="Bijv. Nieuwe match gevonden">
                                        @error('title')
                                            <div class="validation-icon-wrapper" style="display: flex; align-items: center; justify-content: center; width: 1.25rem; height: 1.25rem; flex-shrink: 0;">
                                                <i class="ki-filled ki-cross-circle text-destructive" style="font-size: 1.25rem;"></i>
                                            </div>
                                        @enderror
                                    </div>
                                    @error('title')
                                        <div class="text-xs text-destructive mt-1">Dit veld is verplicht!</div>
                                    @enderror
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                Bericht *
                            </td>
                            <td>
                                <div class="flex flex-col">
                                    <div class="flex items-center gap-2" style="display: inline-flex; align-items: flex-start;">
                                        <textarea class="kt-input pt-1 @error('message') border-destructive @enderror" 
                                                  id="message" 
                                                  name="message" 
                                                  rows="4" 
                                                  required
                                                  placeholder="Voer hier het bericht in...">{{ old('message') }}</textarea>
                                        @error('message')
                                            <div class="validation-icon-wrapper" style="display: flex; align-items: flex-start; justify-content: center; width: 1.25rem; height: 1.25rem; flex-shrink: 0; padding-top: 0.5rem;">
                                                <i class="ki-filled ki-cross-circle text-destructive" style="font-size: 1.25rem;"></i>
                                            </div>
                                        @enderror
                                    </div>
                                    @error('message')
                                        <div class="text-xs text-destructive mt-1">Dit veld is verplicht!</div>
                                    @enderror
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Prioriteit
                            </td>
                            <td>
                                <div class="flex flex-col">
                                    <div class="flex items-center gap-2" style="display: inline-flex; align-items: center;">
                                        <div style="position: relative; display: inline-block; width: auto; min-width: 200px;">
                                            <select class="kt-select @error('priority') border-destructive @enderror" 
                                                    id="priority" 
                                                    name="priority"
                                                    data-kt-select="true"
                                                    style="width: auto; min-width: 200px; max-width: 100%;">
                                                <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Laag</option>
                                                <option value="normal" {{ old('priority', 'normal') == 'normal' ? 'selected' : '' }}>Normaal</option>
                                                <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>Hoog</option>
                                                <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                                            </select>
                                        </div>
                                        @error('priority')
                                            <div class="validation-icon-wrapper" style="display: flex; align-items: center; justify-content: center; width: 1.25rem; height: 1.25rem; flex-shrink: 0;">
                                                <i class="ki-filled ki-cross-circle text-destructive" style="font-size: 1.25rem;"></i>
                                            </div>
                                        @enderror
                                    </div>
                                    @error('priority')
                                        <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                    @enderror
                                    <div class="text-xs text-muted-foreground mt-1">
                                        De prioriteit bepaalt de kleur van het notificatie icoon (Laag: grijs, Normaal: blauw, Hoog: oranje, Urgent: rood)
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Actie URL
                            </td>
                            <td>
                                <div class="flex flex-col">
                                    <div class="flex items-center gap-2" style="display: inline-flex; align-items: center;">
                                        <input type="url" 
                                               class="kt-input @error('action_url') border-destructive @enderror" 
                                               name="action_url" 
                                               id="action_url"
                                               value="{{ old('action_url') }}" 
                                               placeholder="https://example.com/action">
                                        @error('action_url')
                                            <div class="validation-icon-wrapper" style="display: flex; align-items: center; justify-content: center; width: 1.25rem; height: 1.25rem; flex-shrink: 0;">
                                                <i class="ki-filled ki-cross-circle text-destructive" style="font-size: 1.25rem;"></i>
                                            </div>
                                        @enderror
                                    </div>
                                    @error('action_url')
                                        <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                    @enderror
                                    <div class="text-xs text-muted-foreground mt-1">
                                        Optionele URL waar de gebruiker naartoe wordt geleid bij klikken op de notificatie
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Gepland op
                            </td>
                            <td>
                                <div class="flex flex-col">
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex items-center gap-2" style="display: inline-flex; align-items: center;">
                                            <div class="kt-input @error('scheduled_at') border-destructive @enderror" style="max-width: 200px;">
                                                <i class="ki-outline ki-calendar"></i>
                                                @php
                                                    $createScheduledDate = old('scheduled_at') 
                                                        ? \Carbon\Carbon::parse(old('scheduled_at')) 
                                                        : null;
                                                @endphp
                                                <input class="grow"
                                                       id="scheduled_at_display"
                                                       data-kt-date-picker="true"
                                                       data-kt-date-picker-input-mode="true"
                                                       data-kt-date-picker-position-to-input="left"
                                                       data-kt-date-picker-date-format="DD-MM-YYYY"
                                                       @if($createScheduledDate)
                                                       data-kt-date-picker-selected-dates='["{{ $createScheduledDate->format('Y-m-d') }}"]'
                                                       data-kt-date-picker-selected-month="{{ $createScheduledDate->format('n') - 1 }}"
                                                       data-kt-date-picker-selected-year="{{ $createScheduledDate->format('Y') }}"
                                                       @endif
                                                       placeholder="Selecteer datum"
                                                       readonly
                                                       type="text"
                                                       value="{{ $createScheduledDate ? $createScheduledDate->format('d-m-Y') : '' }}"/>
                                                <input type="hidden"
                                                       name="scheduled_at"
                                                       id="scheduled_at_hidden"
                                                       value="{{ $createScheduledDate ? $createScheduledDate->format('Y-m-d H:i') : '' }}"/>
                                            </div>
                                            @error('scheduled_at')
                                                <div class="validation-icon-wrapper" style="display: flex; align-items: center; justify-content: center; width: 1.25rem; height: 1.25rem; flex-shrink: 0;">
                                                    <i class="ki-filled ki-cross-circle text-destructive" style="font-size: 1.25rem;"></i>
                                                </div>
                                            @enderror
                                        </div>
                                        <div class="flex items-center gap-2" style="display: inline-flex; align-items: center;">
                                            <div class="kt-input @error('scheduled_time') border-destructive @enderror" style="max-width: 120px;">
                                                <i class="ki-outline ki-time"></i>
                                                <input type="time"
                                                       name="scheduled_time"
                                                       id="scheduled_time"
                                                       class="grow"
                                                       value="{{ old('scheduled_time', $createScheduledDate ? $createScheduledDate->format('H:i') : '') }}">
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
                                    <small class="text-muted-foreground text-xs mt-1 block">Voer tijd in als hh:mm (bijv. 14:30) of gebruik het klok icoon</small>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Locatie
                            </td>
                            <td>
                                <div class="flex flex-col">
                                    <div class="flex items-center gap-2" style="display: inline-flex; align-items: center;">
                                        <div style="position: relative; display: inline-block; width: auto; min-width: 300px;">
                                            <select class="kt-select @error('location_id') border-destructive @enderror" 
                                                    id="location_id" 
                                                    name="location_id"
                                                    data-kt-select="true"
                                                    style="width: auto; min-width: 300px; max-width: 100%;">
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
                                        </div>
                                        @error('location_id')
                                            <div class="validation-icon-wrapper" style="display: flex; align-items: center; justify-content: center; width: 1.25rem; height: 1.25rem; flex-shrink: 0;">
                                                <i class="ki-filled ki-cross-circle text-destructive" style="font-size: 1.25rem;"></i>
                                            </div>
                                        @enderror
                                    </div>
                                    @error('location_id')
                                        <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                    @enderror
                                    <div class="text-xs text-muted-foreground mt-1">
                                        Optioneel: selecteer een locatie van je bedrijf
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="border-b-0">
                            <td class="text-secondary-foreground font-normal border-b-0">
                                Bestand
                            </td>
                            <td class="border-b-0">
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
                        {{-- Extra Data (JSON) field hidden - only used internally --}}
                        <tr style="display: none;">
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

@push('styles')
<style>
    /* Ensure optgroups are visible in KT Select dropdowns - specific for user_id dropdown */
    #user_id + .kt-select-wrapper .kt-select-group-header,
    #user_id + .kt-select-wrapper [data-kt-select-group-header],
    .kt-select-wrapper:has(#user_id) .kt-select-group-header,
    .kt-select-wrapper:has(#user_id) [data-kt-select-group-header] {
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
    
    #user_id + .kt-select-wrapper .kt-select-group,
    #user_id + .kt-select-wrapper [data-kt-select-group],
    .kt-select-wrapper:has(#user_id) .kt-select-group,
    .kt-select-wrapper:has(#user_id) [data-kt-select-group] {
        display: block !important;
        visibility: visible !important;
    }
    
    #user_id + .kt-select-wrapper .kt-select-group:first-child .kt-select-group-header,
    .kt-select-wrapper:has(#user_id) .kt-select-group:first-child .kt-select-group-header {
        margin-top: 0 !important;
    }
    
    /* Indent options within optgroups to show they belong to that group */
    #user_id + .kt-select-wrapper .kt-select-group .kt-select-option,
    .kt-select-wrapper:has(#user_id) .kt-select-group .kt-select-option {
        padding-left: 1.5rem !important;
        margin-left: 0.5rem !important;
    }
    
    /* General optgroup styling for all KT Select dropdowns */
    .kt-select-group-header,
    [data-kt-select-group-header] {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        font-weight: 600 !important;
        color: #6b7280 !important; /* gray-500 fallback */
        background-color: #f3f4f6 !important; /* gray-100 fallback */
        text-transform: uppercase !important;
        padding: 0.5rem 0.75rem !important;
        font-size: 0.75rem !important;
        border-bottom: 1px solid #e5e7eb !important; /* gray-200 fallback */
        margin-top: 0.5rem !important;
        margin-bottom: 0.25rem !important;
        letter-spacing: 0.05em !important;
    }
    
    .dark .kt-select-group-header,
    .dark [data-kt-select-group-header] {
        color: #9ca3af !important; /* gray-400 for dark mode */
        background-color: #374151 !important; /* gray-700 for dark mode */
        border-bottom-color: #4b5563 !important; /* gray-600 for dark mode */
    }
    
    .kt-select-group,
    [data-kt-select-group] {
        display: block !important;
        visibility: visible !important;
    }
    
    .kt-select-group:first-child .kt-select-group-header,
    .kt-select-group:first-child [data-kt-select-group-header] {
        margin-top: 0 !important;
    }
    
    .kt-select-group .kt-select-option,
    [data-kt-select-group] .kt-select-option {
        padding-left: 1.5rem !important;
        margin-left: 0.5rem !important;
    }
    
    /* Fallback for browsers that don't support :has() */
    .kt-select-wrapper[data-user-select] .kt-select-group-header,
    .kt-select-wrapper[data-user-select] [data-kt-select-group-header] {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        font-weight: 600 !important;
        color: #6b7280 !important;
        background-color: #f3f4f6 !important;
        text-transform: uppercase !important;
        padding: 0.5rem 0.75rem !important;
        font-size: 0.75rem !important;
        border-bottom: 1px solid #e5e7eb !important;
        margin-top: 0.5rem !important;
        margin-bottom: 0.25rem !important;
    }
    
    .dark .kt-select-wrapper[data-user-select] .kt-select-group-header,
    .dark .kt-select-wrapper[data-user-select] [data-kt-select-group-header] {
        color: #9ca3af !important;
        background-color: #374151 !important;
        border-bottom-color: #4b5563 !important;
    }
    
    .kt-select-wrapper[data-user-select] .kt-select-group .kt-select-option {
        padding-left: 1.5rem !important;
        margin-left: 0.5rem !important;
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

    // Function to toggle time input required state based on date
    function toggleTimeRequired() {
        if (!timeInput) return;
        
        // Check if date input has a value
        const hasDate = dateInput && dateInput.value.trim() !== '';
        
        if (hasDate) {
            // If date is filled, time is required
            timeInput.setAttribute('required', 'required');
        } else {
            // If no date, time is not required
            timeInput.removeAttribute('required');
        }
    }

    // Initialize on page load
    toggleTimeRequired();

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
                toggleTimeRequired(); // Update time required state when date changes
            }
        }, 200);
        
        // Also listen for change events
        dateInput.addEventListener('change', function() {
            updateHiddenInput();
            toggleTimeRequired(); // Update time required state when date changes
        });
        
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
                        toggleTimeRequired(); // Update time required state when date is selected
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

    // Prevent native browser validation popups and rely on server-side validation
    const form = document.querySelector('form[action*="notifications"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Remove any native validation messages
            const invalidFields = form.querySelectorAll(':invalid');
            invalidFields.forEach(field => {
                field.setCustomValidity('');
            });
            
            // Let the form submit normally - server-side validation will show errors
            // The novalidate attribute should already prevent native popups
        });

        // Also prevent invalid event from showing native tooltips
        form.querySelectorAll('input, select, textarea').forEach(field => {
            field.addEventListener('invalid', function(e) {
                e.preventDefault();
                // Remove any custom validity message
                this.setCustomValidity('');
            });
        });
    }

    // Ensure optgroups are properly rendered in KT Select
    // Reinitialize KT Select after a short delay to ensure optgroups are visible
    setTimeout(function() {
        const userSelect = document.getElementById('user_id');
        if (userSelect) {
            // Add data attribute for fallback CSS
            const wrapper = userSelect.closest('.kt-select-wrapper') || userSelect.parentElement;
            if (wrapper) {
                wrapper.setAttribute('data-user-select', 'true');
            }
            
            if (window.KTSelect) {
                try {
                    // Get or create KT Select instance
                    let instance = window.KTSelect.getInstance(userSelect);
                    if (!instance) {
                        window.KTSelect.init(userSelect);
                        instance = window.KTSelect.getInstance(userSelect);
                    }
                    
                    // Force update to ensure optgroups are rendered
                    if (instance && typeof instance.update === 'function') {
                        instance.update();
                    }
                } catch (e) {
                    console.warn('KT Select optgroup rendering:', e);
                }
            }
        }
    }, 500);
});
</script>
@endpush

@endsection
