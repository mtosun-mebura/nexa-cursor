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
    /* Optgroup header styling - must be very specific to override KT Select styles */
    [data-kt-select-dropdown] .kt-select-group-header,
    [data-kt-select-dropdown] [data-kt-select-group-header],
    ul .kt-select-group-header,
    ul [data-kt-select-group-header] {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        font-weight: 600 !important;
        text-transform: uppercase !important;
        padding: 0.5rem 0.75rem !important;
        font-size: 0.75rem !important;
        margin-top: 0.5rem !important;
        margin-bottom: 0.25rem !important;
        letter-spacing: 0.05em !important;
        cursor: default !important;
        pointer-events: none !important;
        user-select: none !important;
        color: #6b7280 !important;
        background-color: #f3f4f6 !important;
        border-bottom: 1px solid #e5e7eb !important;
        list-style: none !important;
    }
    
    [data-kt-select-dropdown] .kt-select-group-header:first-child,
    [data-kt-select-dropdown] [data-kt-select-group-header]:first-child,
    ul .kt-select-group-header:first-child,
    ul [data-kt-select-group-header]:first-child {
        margin-top: 0 !important;
    }
    
    /* Dark mode styling */
    .dark [data-kt-select-dropdown] .kt-select-group-header,
    .dark [data-kt-select-dropdown] [data-kt-select-group-header],
    .dark ul .kt-select-group-header,
    .dark ul [data-kt-select-group-header] {
        color: #9ca3af !important;
        background-color: #374151 !important;
        border-bottom-color: #4b5563 !important;
    }
    
    /* Indent options that are in optgroups - JavaScript will add inline styles, but this is a fallback */
    [data-kt-select-dropdown] [data-kt-select-option][style*="padding-left"],
    [data-kt-select-dropdown] li[data-kt-select-option][style*="padding-left"] {
        padding-left: 1.5rem !important;
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
    // Manually render optgroups from native HTML select to KT Select dropdown
    let optgroupRenderingInProgress = false;
    let optgroupObserver = null;
    let optgroupIntervalId = null;
    
    function findKtSelectElements(selectElement) {
        // Find the display element (the button that opens the dropdown)
        // It should be near the select element
        let display = selectElement.parentElement?.querySelector('[data-kt-select-display]');
        if (!display) {
            // Try finding it in a wider search
            const parent = selectElement.closest('div');
            display = parent?.querySelector('[data-kt-select-display]');
        }
        if (!display) {
            // Last resort: search from the select's position
            const allDisplays = document.querySelectorAll('[data-kt-select-display]');
            // Find the one closest to our select
            for (const d of allDisplays) {
                const dSelect = d.closest('div')?.querySelector('select#user_id');
                if (dSelect === selectElement) {
                    display = d;
                    break;
                }
            }
        }
        
        if (!display) return null;
        
        // Find the dropdown - it's usually appended to body or near the display
        // KT Select often appends dropdowns to body
        let dropdown = null;
        
        // First try: find dropdown associated with this select by data attribute
        const allDropdowns = document.querySelectorAll('[data-kt-select-dropdown]');
        for (const dd of allDropdowns) {
            // Check if this dropdown is related to our select
            // KT Select usually stores a reference or we can check by position
            const ddOptions = dd.querySelectorAll('[data-kt-select-option]');
            if (ddOptions.length > 0) {
                // Check if any option matches our select's options
                const selectOptions = Array.from(selectElement.querySelectorAll('option'));
                let matchCount = 0;
                ddOptions.forEach(opt => {
                    const optValue = opt.getAttribute('data-value') || opt.getAttribute('data-kt-select-value') || '';
                    if (selectOptions.some(so => so.value === optValue)) {
                        matchCount++;
                    }
                });
                if (matchCount > 0) {
                    dropdown = dd;
                    break;
                }
            }
        }
        
        // Fallback: if we have display, try to find dropdown that appears when display is clicked
        if (!dropdown && display) {
            // Try finding by proximity or by checking which dropdown opens
            const tempDropdown = document.querySelector('[data-kt-select-dropdown].open');
            if (tempDropdown) {
                dropdown = tempDropdown;
            }
        }
        
        // Find the options list inside the dropdown
        const optionsList = dropdown?.querySelector('ul') || dropdown?.querySelector('[data-kt-select-options]');
        
        return { display, wrapper: display?.parentElement, dropdown, optionsList };
    }
    
    function renderOptgroups() {
        if (optgroupRenderingInProgress) return;
        optgroupRenderingInProgress = true;
        
        try {
            const userSelect = document.getElementById('user_id');
            if (!userSelect) {
                optgroupRenderingInProgress = false;
                return;
            }
            
            // Find KT Select elements using improved method
            const ktElements = findKtSelectElements(userSelect);
            if (!ktElements || !ktElements.dropdown || !ktElements.optionsList) {
                optgroupRenderingInProgress = false;
                return;
            }
            
            const { dropdown, optionsList } = ktElements;
            
            // Only render if dropdown is open or we're initializing
            if (!dropdown.classList.contains('open') && !dropdown.hasAttribute('data-initialized')) {
                optgroupRenderingInProgress = false;
                return;
            }
            
            // Get all optgroups from the native select
            const optgroups = userSelect.querySelectorAll('optgroup');
            if (optgroups.length === 0) {
                optgroupRenderingInProgress = false;
                return;
            }
            
            // Get all existing options (KT Select has already rendered them)
            // Try multiple selectors to find all options
            let allOptions = Array.from(optionsList.querySelectorAll('li[data-kt-select-option]'));
            if (allOptions.length === 0) {
                allOptions = Array.from(optionsList.querySelectorAll('li.kt-select-option'));
            }
            if (allOptions.length === 0) {
                allOptions = Array.from(optionsList.querySelectorAll('[data-kt-select-option]'));
            }
            if (allOptions.length === 0) {
                allOptions = Array.from(optionsList.querySelectorAll('[role="option"]'));
            }
            if (allOptions.length === 0) {
                // Last resort: get all li elements that are not headers
                allOptions = Array.from(optionsList.querySelectorAll('li:not(.kt-select-group-header):not([data-kt-select-group-header])'));
            }
            
            if (allOptions.length === 0) {
                optgroupRenderingInProgress = false;
                return;
            }
            
            // Remove existing headers first to avoid duplicates
            const existingHeaders = optionsList.querySelectorAll('.kt-select-group-header, [data-kt-select-group-header]');
            existingHeaders.forEach(h => h.remove());
            
            // Create a map of rendered options by their value and text
            const optionsByValue = new Map();
            const optionsByText = new Map();
            
            allOptions.forEach(opt => {
                // Try multiple ways to get the value
                const value = opt.getAttribute('data-value') || 
                            opt.getAttribute('value') || 
                            opt.getAttribute('data-kt-select-value') ||
                            opt.closest('[data-kt-select-option]')?.getAttribute('data-value') ||
                            '';
                
                // Get text content - try multiple ways
                let text = opt.textContent?.trim() || '';
                if (!text) {
                    const textEl = opt.querySelector('.kt-select-option-text, [data-kt-select-option-text]');
                    text = textEl?.textContent?.trim() || '';
                }
                
                if (value) {
                    optionsByValue.set(value, opt);
                }
                if (text) {
                    // Store multiple text variations
                    optionsByText.set(text, opt);
                    // Also try without extra spaces
                    optionsByText.set(text.replace(/\s+/g, ' '), opt);
                }
            });
            
            // Process each optgroup and insert headers before the relevant options
            optgroups.forEach((optgroup, groupIndex) => {
                const label = optgroup.getAttribute('label');
                const groupOptions = Array.from(optgroup.querySelectorAll('option'));
                
                if (groupOptions.length === 0) return;
                
                // Find the first option from this group in the rendered list
                let firstOptionInGroup = null;
                for (const groupOption of groupOptions) {
                    const optionValue = groupOption.value;
                    const optionText = groupOption.textContent?.trim() || '';
                    
                    // Try to find by value first (most reliable)
                    if (optionValue) {
                        const renderedOption = optionsByValue.get(optionValue);
                        if (renderedOption) {
                            firstOptionInGroup = renderedOption;
                            break;
                        }
                    }
                    
                    // Fallback: try to find by text content (exact match)
                    if (optionText) {
                        let renderedOption = optionsByText.get(optionText);
                        if (renderedOption) {
                            firstOptionInGroup = renderedOption;
                            break;
                        }
                        
                        // Try normalized text (remove extra spaces)
                        const normalizedText = optionText.replace(/\s+/g, ' ');
                        renderedOption = optionsByText.get(normalizedText);
                        if (renderedOption) {
                            firstOptionInGroup = renderedOption;
                            break;
                        }
                        
                        // Try partial match - check if any rendered option contains this text
                        for (const [text, opt] of optionsByText.entries()) {
                            if (text.includes(optionText) || optionText.includes(text)) {
                                firstOptionInGroup = opt;
                                break;
                            }
                        }
                        if (firstOptionInGroup) break;
                    }
                }
                
                if (!firstOptionInGroup) {
                    return; // Skip if we can't find the option
                }
                
                // Create group header
                const groupHeader = document.createElement('li');
                groupHeader.className = 'kt-select-group-header';
                groupHeader.setAttribute('data-kt-select-group-header', 'true');
                groupHeader.setAttribute('role', 'presentation');
                groupHeader.textContent = label;
                
                // Apply styles with !important to override any KT Select styles
                const isDark = document.documentElement.classList.contains('dark');
                groupHeader.style.cssText = `
                    display: block !important;
                    visibility: visible !important;
                    opacity: 1 !important;
                    font-weight: 600 !important;
                    text-transform: uppercase !important;
                    padding: 0.5rem 0.75rem !important;
                    font-size: 0.75rem !important;
                    margin-top: ${groupIndex === 0 ? '0' : '0.5rem'} !important;
                    margin-bottom: 0.25rem !important;
                    letter-spacing: 0.05em !important;
                    cursor: default !important;
                    pointer-events: none !important;
                    color: ${isDark ? '#9ca3af' : '#6b7280'} !important;
                    background-color: ${isDark ? '#374151' : '#f3f4f6'} !important;
                    border-bottom: 1px solid ${isDark ? '#4b5563' : '#e5e7eb'} !important;
                `;
                
                // Insert header before the first option of this group
                optionsList.insertBefore(groupHeader, firstOptionInGroup);
                
                // Add indentation to all options in this group
                groupOptions.forEach(groupOption => {
                    const optionValue = groupOption.value;
                    const optionText = groupOption.textContent?.trim() || '';
                    
                    let renderedOption = null;
                    
                    // Try by value first
                    if (optionValue) {
                        renderedOption = optionsByValue.get(optionValue);
                    }
                    
                    // Fallback: try by text
                    if (!renderedOption && optionText) {
                        renderedOption = optionsByText.get(optionText);
                        if (!renderedOption) {
                            const normalizedText = optionText.replace(/\s+/g, ' ');
                            renderedOption = optionsByText.get(normalizedText);
                        }
                    }
                    
                    if (renderedOption) {
                        // Apply indentation with !important via setProperty
                        renderedOption.style.setProperty('padding-left', '1.5rem', 'important');
                        renderedOption.style.setProperty('margin-left', '0.5rem', 'important');
                    }
                });
            });
            
            // Mark as initialized
            dropdown.setAttribute('data-initialized', 'true');
        } catch (e) {
            console.error('Error rendering optgroups:', e);
        } finally {
            optgroupRenderingInProgress = false;
        }
    }
    
    // Function to observe and re-render optgroups when dropdown content changes
    function setupOptgroupObserver() {
        const userSelect = document.getElementById('user_id');
        if (!userSelect) return;
        
        const ktElements = findKtSelectElements(userSelect);
        if (!ktElements || !ktElements.optionsList) return;
        
        // Stop existing observer if any
        if (optgroupObserver) {
            optgroupObserver.disconnect();
        }
        
        // Observe changes to the options list
        optgroupObserver = new MutationObserver(function(mutations) {
            let shouldRender = false;
            mutations.forEach(function(mutation) {
                // Check if options were added or removed
                if (mutation.addedNodes.length > 0 || mutation.removedNodes.length > 0) {
                    // Check if it's not our own header being added
                    const addedHeaders = Array.from(mutation.addedNodes).filter(node => 
                        node.nodeType === 1 && 
                        (node.classList?.contains('kt-select-group-header') || node.hasAttribute('data-kt-select-group-header'))
                    );
                    if (addedHeaders.length === 0) {
                        shouldRender = true;
                    }
                }
            });
            
            if (shouldRender) {
                // Wait a bit for KT Select to finish rendering, then render optgroups
                setTimeout(renderOptgroups, 50);
            }
        });
        
        optgroupObserver.observe(ktElements.optionsList, {
            childList: true,
            subtree: false
        });
    }
    
    // Function to start monitoring when dropdown opens
    function startOptgroupMonitoring() {
        if (optgroupIntervalId) clearInterval(optgroupIntervalId);
        
        optgroupIntervalId = setInterval(function() {
            const userSelect = document.getElementById('user_id');
            if (!userSelect) {
                if (optgroupIntervalId) clearInterval(optgroupIntervalId);
                return;
            }
            
            const ktElements = findKtSelectElements(userSelect);
            if (!ktElements || !ktElements.dropdown) return;
            
            // Only check when dropdown is open
            if (ktElements.dropdown.classList.contains('open')) {
                const optgroups = userSelect.querySelectorAll('optgroup');
                const existingHeaders = ktElements.optionsList?.querySelectorAll('.kt-select-group-header, [data-kt-select-group-header]') || [];
                
                // If dropdown is open but headers are missing, re-render immediately
                if (optgroups.length > 0 && existingHeaders.length < optgroups.length) {
                    renderOptgroups();
                }
            }
        }, 100);
    }
    
    // Wait for KT Select to initialize, then render optgroups
    function initializeOptgroups() {
        const userSelect = document.getElementById('user_id');
        if (!userSelect) return;
        
        const ktElements = findKtSelectElements(userSelect);
        if (!ktElements) {
            // Retry after a short delay
            setTimeout(initializeOptgroups, 200);
            return;
        }
        
        // Setup observer
        setupOptgroupObserver();
        
        // Render optgroups after initial delay
        setTimeout(function() {
            renderOptgroups();
            setupOptgroupObserver();
        }, 300);
        
        // Listen for dropdown open events on the display button
        if (ktElements.display) {
            const clickHandler = function() {
                startOptgroupMonitoring();
                // Multiple timeouts to catch the dropdown at different stages of opening
                setTimeout(function() {
                    renderOptgroups();
                }, 10);
                setTimeout(function() {
                    renderOptgroups();
                }, 50);
                setTimeout(function() {
                    renderOptgroups();
                }, 150);
                setTimeout(function() {
                    renderOptgroups();
                }, 300);
                setTimeout(function() {
                    renderOptgroups();
                }, 500);
            };
            ktElements.display.addEventListener('click', clickHandler);
            // Also listen for focus events
            ktElements.display.addEventListener('focus', clickHandler);
        }
        
        // Listen for dropdown open/close via class changes
        if (ktElements.dropdown) {
            const dropdownObserver = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        if (ktElements.dropdown.classList.contains('open')) {
                            startOptgroupMonitoring();
                            // Multiple timeouts to ensure headers are added
                            setTimeout(function() {
                                renderOptgroups();
                            }, 10);
                            setTimeout(function() {
                                renderOptgroups();
                            }, 50);
                            setTimeout(function() {
                                renderOptgroups();
                            }, 150);
                            setTimeout(function() {
                                renderOptgroups();
                            }, 300);
                            setTimeout(function() {
                                renderOptgroups();
                            }, 500);
                        } else {
                            if (optgroupIntervalId) {
                                clearInterval(optgroupIntervalId);
                                optgroupIntervalId = null;
                            }
                        }
                    }
                });
            });
            dropdownObserver.observe(ktElements.dropdown, { 
                attributes: true, 
                attributeFilter: ['class'] 
            });
            
            // Also observe child list changes (when options are added/removed)
            if (ktElements.optionsList) {
                const optionsObserver = new MutationObserver(function() {
                    if (ktElements.dropdown.classList.contains('open')) {
                        setTimeout(renderOptgroups, 50);
                    }
                });
                optionsObserver.observe(ktElements.optionsList, {
                    childList: true,
                    subtree: true
                });
            }
        }
    }
    
    // Start initialization after DOM is ready
    setTimeout(initializeOptgroups, 500);
});
</script>
@endpush

@endsection
