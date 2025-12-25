@extends('admin.layouts.app')

@section('title', 'Nieuwe Vacature')

@section('content')

<div class="kt-container-fixed vacancy-create">
    <div class="flex flex-col gap-5 pb-7.5">
        <div class="flex flex-wrap items-center justify-between gap-5">
            <h1 class="text-xl font-medium leading-none text-mono">
                Nieuwe Vacature
            </h1>
        </div>
        <div class="flex items-center">
            <a href="{{ route('admin.vacancies.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
    </div>

    <form action="{{ route('admin.vacancies.store') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-5 lg:gap-7.5" data-validate="true">
        @csrf

        <div class="grid gap-5 lg:gap-7.5">
            <x-error-card :errors="$errors" />

            <!-- Vacature gegevens -->
            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Vacature gegevens</h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        @if(auth()->user()->hasRole('super-admin'))
                        <tr>
                            <td class="text-secondary-foreground font-normal">Status *</td>
                            <td>
                                <select name="status" class="kt-select @error('status') border-destructive @enderror" data-kt-select="true" required>
                                    <option value="">Selecteer status</option>
                                    @foreach($statuses ?? [] as $opt)
                                        <option value="{{ $opt }}" {{ old('status', 'Open') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                    @endforeach
                                </select>
                                <div class="text-xs text-muted-foreground mt-1">Selecteer uit beschikbare statussen</div>
                                @error('status')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Bedrijf *</td>
                            <td>
                                @php $selectedTenant = session('selected_tenant'); @endphp
                                <select name="company_id" class="kt-select @error('company_id') border-destructive @enderror" data-kt-select="true" required>
                                    @foreach(($companies ?? []) as $company)
                                        <option value="{{ $company->id }}" {{ (string)old('company_id', $selectedTenant) === (string)$company->id ? 'selected' : '' }}>
                                            {{ $company->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('company_id')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                                @if(auth()->user()->hasRole('super-admin') && $selectedTenant)
                                    <div class="text-xs text-muted-foreground mt-1">Tenant geselecteerd via sidebar: bedrijf wordt automatisch gezet</div>
                                @endif
                            </td>
                        </tr>
                        @else
                            <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
                        @endif
                        <tr>
                            <td class="text-secondary-foreground font-normal">Locatie</td>
                            <td>
                                <div class="flex gap-2">
                                    <select name="location" id="location-select" class="kt-select @error('location') border-destructive @enderror" data-kt-select="true" style="flex: 1;">
                                        <option value="">- Selecteer locatie -</option>
                                        @if($selectedCompany && ($selectedCompany->city || $selectedCompany->street))
                                            @php
                                                $mainAddress = $selectedCompany->city;
                                                if ($selectedCompany->street) {
                                                    $mainAddress = $selectedCompany->street;
                                                    if ($selectedCompany->house_number) {
                                                        $mainAddress .= ' ' . $selectedCompany->house_number;
                                                        if ($selectedCompany->house_number_extension) {
                                                            $mainAddress .= $selectedCompany->house_number_extension;
                                                        }
                                                    }
                                                    if ($selectedCompany->city && $selectedCompany->city != $selectedCompany->street) {
                                                        $mainAddress .= ', ' . $selectedCompany->city;
                                                    }
                                                }
                                            @endphp
                                            <option value="{{ $mainAddress }}" {{ old('location') == $mainAddress ? 'selected' : '' }}>
                                                Hoofdadres{{ $selectedCompany->city ? ' - ' . $selectedCompany->city : '' }}
                                            </option>
                                        @endif
                                        @foreach($companyLocations ?? [] as $location)
                                            <option value="{{ $location->name }}" {{ old('location') == $location->name ? 'selected' : '' }}>
                                                {{ $location->name }}
                                                @if($location->city)
                                                    @if($location->name != $location->city)
                                                        - {{ $location->city }}
                                                    @endif
                                                @endif
                                            </option>
                                        @endforeach
                                        <option value="__custom__">+ Nieuwe locatie invoeren</option>
                                    </select>
                                    <input type="text" 
                                           name="location_custom" 
                                           id="location-custom-input" 
                                           class="kt-input @error('location') border-destructive @enderror" 
                                           value="{{ old('location_custom', old('location')) }}"
                                           placeholder="Voer locatie in..."
                                           style="display: none; width: 100%; min-width: 400px;">
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Selecteer een locatie of voer een nieuwe in</div>
                                @error('location')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        @php
                            $selectedBranchId = old('branch_id');
                            $selectedBranchName = '';
                            if ($selectedBranchId) {
                                $selectedBranchName = optional(($branches ?? collect())->firstWhere('id', (int) $selectedBranchId))->name ?? '';
                            }
                        @endphp
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Branch *</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="text"
                                           id="branch-input"
                                           class="kt-input @error('branch_id') border-destructive @enderror"
                                           value="{{ $selectedBranchName }}"
                                           autocomplete="off"
                                           placeholder="Type om te zoeken..."
                                           required>
                                    <input type="hidden" id="branch-id" name="branch_id" value="{{ old('branch_id') }}">
                                    <div id="branch-suggestions" class="hidden absolute left-0 top-full z-[9999] bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-xl max-h-60 overflow-y-auto w-full mt-1" style="min-width: 100%;"></div>
                                </div>
                                @error('branch_id')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Functie *</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="text"
                                           id="function-input"
                                           name="title"
                                           class="kt-input @error('title') border-destructive @enderror"
                                           value="{{ old('title') }}"
                                           autocomplete="off"
                                           placeholder="Type om te zoeken... (of voer zelf in)"
                                           required>
                                    <input type="hidden" id="branch-function-id" value="">
                                    <div id="function-suggestions" class="hidden absolute left-0 top-full z-[9999] bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-xl max-h-60 overflow-y-auto w-full mt-1" style="min-width: 100%;"></div>
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Zoek naar een functie (branch wordt automatisch ingevuld) of kies eerst een branch om functies te filteren. Vrij invullen kan altijd.</div>
                                @error('title')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('create-users'))
                        <tr>
                            <td class="text-secondary-foreground font-normal">Contactpersoon</td>
                            <td>
                                <select name="contact_user_id" class="kt-select @error('contact_user_id') border-destructive @enderror" data-kt-select="true">
                                    <option value="">- Selecteer contactpersoon -</option>
                                    @if(isset($users) && $users->count() > 0)
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ old('contact_user_id') == $user->id ? 'selected' : '' }}>
                                                {{ $user->first_name }} {{ $user->middle_name }} {{ $user->last_name }} ({{ $user->email }})
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                <div class="text-xs text-muted-foreground mt-1">Selecteer een medewerker als contactpersoon. Als er geen selectie wordt gemaakt, wordt u automatisch als contactpersoon ingesteld.</div>
                                @error('contact_user_id')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <td class="text-secondary-foreground font-normal">Type dienstverband</td>
                            <td>
                                <select name="employment_type" class="kt-select @error('employment_type') border-destructive @enderror" data-kt-select="true">
                                    <option value="">-</option>
                                    @foreach($employmentTypes ?? [] as $opt)
                                        <option value="{{ $opt }}" {{ old('employment_type') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                    @endforeach
                                </select>
                                <div class="text-xs text-muted-foreground mt-1">Selecteer uit beschikbare dienstverband types</div>
                                @error('employment_type')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Salarisrange</td>
                            <td>
                                <select name="salary_range" id="salary_range_select" class="kt-select @error('salary_range') border-destructive @enderror" data-kt-select="true">
                                    <option value="">-</option>
                                    @foreach($salaryBrutoPerMaand ?? [] as $opt)
                                        <option value="{{ $opt }}" {{ old('salary_range') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                    @endforeach
                                </select>
                                <div class="text-xs text-muted-foreground mt-1">Selecteer uit beschikbare salarisranges</div>
                                @error('salary_range')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Referentie</td>
                            <td>
                                <input type="text" name="reference_number" class="kt-input @error('reference_number') border-destructive @enderror" value="{{ old('reference_number') }}">
                                @error('reference_number')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Werkuren</td>
                            <td>
                                <select name="working_hours" class="kt-select @error('working_hours') border-destructive @enderror" data-kt-select="true">
                                    <option value="">-</option>
                                    @foreach($workingHours ?? [] as $opt)
                                        <option value="{{ $opt }}" {{ old('working_hours') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                    @endforeach
                                </select>
                                <div class="text-xs text-muted-foreground mt-1">Selecteer uit beschikbare werkuren</div>
                                @error('working_hours')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Taal</td>
                            <td>
                                <input type="text" name="language" class="kt-input @error('language') border-destructive @enderror" value="{{ old('language', 'Nederlands') }}">
                                @error('language')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Publicatiedatum</td>
                            <td>
                                <!--begin::Input with Calendar-->
                                <div class="kt-input w-64 @error('publication_date') border-destructive @enderror">
                                    <i class="ki-outline ki-calendar"></i>
                                    <input class="grow" 
                                           name="publication_date" 
                                           id="publication_date"
                                           value="{{ old('publication_date') }}"
                                           data-kt-date-picker="true" 
                                           data-kt-date-picker-input-mode="true" 
                                           data-kt-date-picker-position-to-input="left"
                                           data-kt-date-picker-format="yyyy-MM-dd"
                                           placeholder="Selecteer datum" 
                                           readonly 
                                           type="text"/>
                                </div>
                                @error('publication_date')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Sluitingsdatum</td>
                            <td>
                                <!--begin::Input with Calendar-->
                                <div class="kt-input w-64 @error('closing_date') border-destructive @enderror">
                                    <i class="ki-outline ki-calendar"></i>
                                    <input class="grow" 
                                           name="closing_date" 
                                           id="closing_date"
                                           value="{{ old('closing_date') }}"
                                           data-kt-date-picker="true" 
                                           data-kt-date-picker-input-mode="true" 
                                           data-kt-date-picker-position-to-input="left"
                                           data-kt-date-picker-format="yyyy-MM-dd"
                                           placeholder="Selecteer datum" 
                                           readonly 
                                           type="text"/>
                                </div>
                                @error('closing_date')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Reiskosten</td>
                            <td>
                                <label class="kt-label flex items-center">
                                    <input type="checkbox" class="kt-switch kt-switch-sm" name="travel_expenses" value="1" {{ old('travel_expenses') ? 'checked' : '' }}>
                                    <span class="ms-2">Vergoed</span>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Remote</td>
                            <td>
                                <label class="kt-label flex items-center">
                                    <input type="checkbox" class="kt-switch kt-switch-sm" name="remote_work" value="1" {{ old('remote_work') ? 'checked' : '' }}>
                                    <span class="ms-2">Mogelijk</span>
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Content -->
            <div class="kt-card min-w-full">
                <div class="kt-card-header"><h3 class="kt-card-title">Inhoud</h3></div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">Beschrijving *</td>
                            <td class="min-w-48 w-full">
                                <textarea name="description" rows="4" class="kt-input pt-1 @error('description') border-destructive @enderror" required>{{ old('description') }}</textarea>
                                @error('description')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Vereisten</td>
                            <td>
                                <input type="hidden" name="required_skills" id="required-skills-input" value="{{ old('required_skills') }}">
                                <div class="flex flex-col gap-3">
                                    <div class="flex flex-wrap items-center gap-2" id="required-skills-chips"></div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" class="kt-btn kt-btn-sm kt-btn-success" id="add-skill-btn">
                                            <i class="ki-filled ki-plus me-1"></i>
                                            Toevoegen
                                        </button>
                                        <button type="button" class="kt-btn kt-btn-sm kt-btn-warning hidden" id="load-default-skills-btn">
                                            <i class="ki-filled ki-arrow-down me-1"></i>
                                            Inladen standaard vaardigheden
                                        </button>
                                        @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('edit-branches'))
                                            <label class="kt-label flex items-center gap-2 ms-2">
                                                <input type="checkbox" class="kt-switch kt-switch-sm" id="save-skill-as-default" value="1" data-validate="false">
                                                <span class="text-xs text-muted-foreground">Nieuwe skills ook opslaan als standaard voor deze functie</span>
                                            </label>
                                        @endif
                                    </div>
                                    <div class="text-xs text-muted-foreground">Klik op "Inladen standaard vaardigheden" om de vaardigheden van de gekozen functie in te laden. Je kunt altijd handmatig aanpassen.</div>
                                </div>
                                @error('required_skills')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Overige vereisten (tekst)</td>
                            <td>
                                <textarea name="requirements" rows="4" class="kt-input pt-1 @error('requirements') border-destructive @enderror">{{ old('requirements') }}</textarea>
                                @error('requirements')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Aanbod</td>
                            <td>
                                <textarea name="offer" rows="4" class="kt-input pt-1 @error('offer') border-destructive @enderror">{{ old('offer') }}</textarea>
                                @error('offer')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Sollicitatie instructies</td>
                            <td>
                                <textarea name="application_instructions" rows="4" class="kt-input pt-1 @error('application_instructions') border-destructive @enderror">{{ old('application_instructions') }}</textarea>
                                @error('application_instructions')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- SEO -->
            <div class="kt-card min-w-full">
                <div class="kt-card-header"><h3 class="kt-card-title">SEO</h3></div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Meta titel</td>
                            <td class="min-w-48 w-full">
                                <div class="flex items-center gap-2 mb-1">
                                    <input type="text" name="meta_title" id="meta_title" class="kt-input @error('meta_title') border-destructive @enderror flex-1" value="{{ old('meta_title') }}">
                                    <label class="kt-label flex items-center gap-1.5 cursor-pointer">
                                        <input type="checkbox" id="auto-meta-title" class="kt-switch kt-switch-sm" checked>
                                        <span class="text-xs text-muted-foreground">Auto</span>
                                    </label>
                                </div>
                                <div class="text-xs text-muted-foreground mb-1">
                                    <span id="meta-title-length">0</span>/60 karakters (ideaal: 50-60)
                                </div>
                                @error('meta_title')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Meta beschrijving</td>
                            <td>
                                <div class="flex items-start gap-2 mb-1">
                                    <textarea name="meta_description" id="meta_description" rows="4" class="kt-input pt-1 @error('meta_description') border-destructive @enderror flex-1">{{ old('meta_description') }}</textarea>
                                    <label class="kt-label flex items-center gap-1.5 cursor-pointer mt-1">
                                        <input type="checkbox" id="auto-meta-description" class="kt-switch kt-switch-sm" checked>
                                        <span class="text-xs text-muted-foreground">Auto</span>
                                    </label>
                                </div>
                                <div class="text-xs text-muted-foreground mb-1">
                                    <span id="meta-description-length">0</span>/160 karakters (ideaal: 150-160)
                                </div>
                                @error('meta_description')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Meta keywords</td>
                            <td>
                                <div class="flex items-start gap-2 mb-1">
                                    <textarea name="meta_keywords" id="meta_keywords" rows="4" class="kt-input pt-1 @error('meta_keywords') border-destructive @enderror flex-1" placeholder="keyword1, keyword2">{{ old('meta_keywords') }}</textarea>
                                    <label class="kt-label flex items-center gap-1.5 cursor-pointer mt-1">
                                        <input type="checkbox" id="auto-meta-keywords" class="kt-switch kt-switch-sm" checked>
                                        <span class="text-xs text-muted-foreground">Auto</span>
                                    </label>
                                </div>
                                <div class="text-xs text-muted-foreground mb-1">
                                    <span id="meta-keywords-count">0</span> keywords (optimaal: 5-10 relevante keywords)
                                </div>
                                @error('meta_keywords')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5">
                <a href="{{ route('admin.vacancies.index') }}" class="kt-btn kt-btn-outline">Annuleren</a>
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-check me-2"></i>
                    Opslaan
                </button>
            </div>
        </div>
    </form>
</div>

@push('styles')
<style>
    /* Zorg dat de card-table overflow niet blokkeert, maar card zelf niet uitrekt */
    .vacancy-create .kt-card-table {
        overflow: visible !important;
    }

    .vacancy-create .kt-card-table.kt-scrollable-x-auto {
        overflow-x: auto !important;
        overflow-y: hidden !important; /* Geen verticale scrollbar */
    }

    /* Zorg dat de kt-card zelf ook overflow toestaat, maar niet uitrekt */
    .vacancy-create .kt-card {
        overflow: visible !important;
    }

    .vacancy-create .kt-card-content {
        overflow: visible !important;
    }

    /* Voorkom dat de card-table de card uitrekt */
    .vacancy-create .kt-card-table tbody {
        position: relative !important;
    }

    /* Zorg dat de kt-select-wrapper zelf niet de card uitrekt */
    .vacancy-create .kt-card-table .kt-select-wrapper {
        position: relative !important;
        overflow: visible !important;
    }

    /* Voorkom dat table rows uitrekken door dropdown */
    .vacancy-create .kt-card-table tbody tr {
        position: relative !important;
        height: auto !important; /* Laat row hoogte bepalen door content, niet door dropdown */
    }

    /* Zorg dat td met contactpersoon select niet uitrekt */
    .vacancy-create .kt-card-table tbody tr td:has(select[name="contact_user_id"]) {
        position: relative !important;
        overflow: visible !important;
        height: auto !important; /* Laat td hoogte bepalen door content, niet door dropdown */
    }

    /* Contactpersoon dropdown - net zoals Functie dropdown, scrollbaar binnen dropdown */
    .vacancy-create select[name="contact_user_id"] + .kt-select-wrapper .kt-select-dropdown,
    .vacancy-create select[name="contact_user_id"] + .kt-select-wrapper [data-kt-select-dropdown],
    .vacancy-create .kt-select-wrapper:has(select[name="contact_user_id"]) .kt-select-dropdown,
    .vacancy-create .kt-select-wrapper:has(select[name="contact_user_id"]) [data-kt-select-dropdown] {
        max-height: none !important;
        overflow-y: visible !important;
        overflow-x: visible !important;
        position: fixed !important;
        z-index: 99999 !important;
    }

    /* Fallback voor browsers die :has() niet ondersteunen */
    .vacancy-create .kt-select-wrapper[data-contact-user-select] .kt-select-dropdown,
    .vacancy-create .kt-select-wrapper[data-contact-user-select] [data-kt-select-dropdown] {
        max-height: none !important;
        overflow-y: visible !important;
        overflow-x: visible !important;
        position: fixed !important;
        z-index: 99999 !important;
    }
    
    /* Zorg dat de dropdown opties altijd volledig zichtbaar zijn */
    .vacancy-create .kt-select-wrapper[data-contact-user-select] .kt-select-options,
    .vacancy-create select[name="contact_user_id"] + .kt-select-wrapper .kt-select-options {
        max-height: none !important;
        overflow: visible !important;
        position: relative !important;
    }

    .vacancy-create .kt-table-border-dashed.align-middle td.align-top {
        vertical-align: top !important;
        padding-top: 14px;
    }
    /* Groene knoppen voor skills */
    #add-skill-btn.kt-btn-success {
        background-color: var(--color-green-600);
        border-color: var(--color-green-600);
        color: white;
    }
    #add-skill-btn.kt-btn-success:hover {
        background-color: var(--color-green-700);
        border-color: var(--color-green-700);
    }
    #load-default-skills-btn.kt-btn-warning {
        background-color: var(--color-orange-500);
        border-color: var(--color-orange-500);
        color: white;
    }
    #load-default-skills-btn.kt-btn-warning:hover {
        background-color: var(--color-orange-600);
        border-color: var(--color-orange-600);
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('assets/js/form-validation.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const branches = @json(($branches ?? collect())->map(fn($b) => ['id' => $b->id, 'name' => $b->name])->values());
    const branchInput = document.getElementById('branch-input');
    const branchIdInput = document.getElementById('branch-id');
    const branchSuggestions = document.getElementById('branch-suggestions');
    const functionInput = document.getElementById('function-input');
    const suggestionsDiv = document.getElementById('function-suggestions');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const canSaveDefaults = {{ (auth()->user()->hasRole('super-admin') || auth()->user()->can('edit-branches')) ? 'true' : 'false' }};

    const branchFunctionIdInput = document.getElementById('branch-function-id');
    const requiredSkillsInput = document.getElementById('required-skills-input');
    const chipsWrap = document.getElementById('required-skills-chips');
    const addSkillBtn = document.getElementById('add-skill-btn');
    const loadDefaultSkillsBtn = document.getElementById('load-default-skills-btn');
    const saveSkillAsDefaultToggle = document.getElementById('save-skill-as-default');

    if (!functionInput || !suggestionsDiv || !branchInput || !branchIdInput || !branchSuggestions) return;

    let allFunctions = []; // Alle functies met branch info: [{id,name,display_name,branch_id,branch_name}]
    let functions = []; // Gefilterde functies voor huidige branch: [{id,name,display_name}]
    let selectedIndex = -1;
    let branchSelectedIndex = -1;
    let lastBranchId = null;
    let selectedFunctionId = null;
    let skills = []; // ['PHP', 'Laravel', ...]
    let skillsTouched = false;

    function normalizeSkill(name) {
        return (name || '').toString().trim().replace(/\s+/g, ' ');
    }

    function serializeSkills() {
        if (!requiredSkillsInput) return;
        requiredSkillsInput.value = JSON.stringify(skills);
    }

    function renderChips() {
        if (!chipsWrap) return;
        chipsWrap.innerHTML = '';
        skills.forEach((s, idx) => {
            const chip = document.createElement('span');
            chip.className = 'inline-flex items-center gap-2 rounded-full border border-border px-3 py-1 text-sm text-foreground bg-muted/10';
            chip.innerHTML = `<span>${escapeHtml(s)}</span>`;
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'text-danger leading-none';
            btn.innerHTML = '&times;';
            btn.addEventListener('click', function () {
                skills.splice(idx, 1);
                skillsTouched = true;
                serializeSkills();
                renderChips();
            });
            chip.appendChild(btn);
            chipsWrap.appendChild(chip);
        });
    }

    async function loadDefaultSkillsForFunction(branchId, functionId) {
        if (!branchId || !functionId) return [];
        try {
            const url = `{{ url('admin/branches') }}/${encodeURIComponent(branchId)}/functions/${encodeURIComponent(functionId)}/skills`;
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, credentials: 'same-origin' });
            if (!res.ok) return [];
            const json = await res.json();
            return (json?.skills || []).map(x => x.display_name || x.name).map(normalizeSkill).filter(Boolean);
        } catch (_) {
            return [];
        }
    }

    async function applyFunctionSkillsIfUntouched(branchId, functionId) {
        if (skillsTouched) return;
        const defaults = await loadDefaultSkillsForFunction(branchId, functionId);
        skills = Array.from(new Set(defaults.map(s => s.toLowerCase()))).map(lower => defaults.find(s => s.toLowerCase() === lower));
        serializeSkills();
        renderChips();
    }

    async function loadDefaultSkills(branchId, functionId, force = false) {
        if (!branchId || !functionId) return;
        const defaults = await loadDefaultSkillsForFunction(branchId, functionId);
        if (defaults.length > 0) {
            // Merge with existing, avoiding duplicates (case-insensitive)
            const existingLower = skills.map(s => s.toLowerCase());
            defaults.forEach(s => {
                if (!existingLower.includes(s.toLowerCase())) {
                    skills.push(s);
                }
            });
            // Remove duplicates while preserving order
            const seen = new Set();
            skills = skills.filter(s => {
                const lower = s.toLowerCase();
                if (seen.has(lower)) return false;
                seen.add(lower);
                return true;
            });
            skillsTouched = force; // Only mark as untouched if forced
            serializeSkills();
            renderChips();
        }
    }

    async function checkAndShowLoadButton(branchId, functionId) {
        if (!loadDefaultSkillsBtn) return;
        
        if (!branchId || !functionId) {
            loadDefaultSkillsBtn.classList.add('hidden');
            return;
        }
        
        try {
            const defaults = await loadDefaultSkillsForFunction(branchId, functionId);
            if (defaults && defaults.length > 0) {
                loadDefaultSkillsBtn.classList.remove('hidden');
            } else {
                loadDefaultSkillsBtn.classList.add('hidden');
            }
        } catch (error) {
            console.error('Error checking load button:', error);
            loadDefaultSkillsBtn.classList.add('hidden');
        }
    }

    async function saveDefaultSkill(branchId, functionId, skillName) {
        if (!canSaveDefaults) return;
        if (!saveSkillAsDefaultToggle || !saveSkillAsDefaultToggle.checked) return;
        try {
            const url = `{{ url('admin/branches') }}/${encodeURIComponent(branchId)}/functions/${encodeURIComponent(functionId)}/skills`;
            await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                },
                credentials: 'same-origin',
                body: JSON.stringify({ name: skillName }),
            });
        } catch (_) {}
    }

    function getBranchId() {
        return (branchIdInput && branchIdInput.value) ? String(branchIdInput.value) : '';
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function filterFunctions(query) {
        const q = (query || '').trim().toLowerCase();
        // Determine source: if branch is selected and has functions, use those; otherwise use all functions
        const branchId = getBranchId();
        const source = (branchId && functions.length > 0) ? functions : (allFunctions.length > 0 ? allFunctions : []);
        if (!source || source.length === 0) return [];
        if (!q) return source.slice(0, 50);
        return source.filter(f => {
            const displayName = (f.display_name || f.name || '').toLowerCase();
            return displayName.includes(q);
        }).slice(0, 50);
    }

    function renderSuggestions(list, query) {
        const currentQuery = (query || '').trim().toLowerCase();
        suggestionsDiv.innerHTML = '';

        if (list.length === 0) {
            if (currentQuery.length > 0) {
                const item = document.createElement('div');
                item.className = 'px-4 py-2 text-gray-500 italic';
                item.textContent = 'Geen matches — druk Enter om deze functie te gebruiken';
                suggestionsDiv.appendChild(item);
                suggestionsDiv.classList.remove('hidden');
            } else {
                suggestionsDiv.classList.add('hidden');
            }
            return;
        }

        list.forEach((f, index) => {
            const item = document.createElement('div');
            item.className = 'px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer';

            const display = f.display_name || f.name || '';
            if (currentQuery.length > 0 && display.toLowerCase().includes(currentQuery)) {
                const safe = escapeHtml(display);
                const regex = new RegExp(`(${currentQuery})`, 'gi');
                item.innerHTML = safe.replace(regex, '<strong>$1</strong>');
            } else {
                item.textContent = display;
            }

            item.dataset.index = String(index);
            item.addEventListener('click', async function () {
                functionInput.value = display;
                suggestionsDiv.classList.add('hidden');
                selectedFunctionId = f.id || null;
                if (branchFunctionIdInput) branchFunctionIdInput.value = selectedFunctionId ? String(selectedFunctionId) : '';

                // Auto-fill branch if function has branch info and branch is not yet selected
                let branchWasAutoFilled = false;
                if (f.branch_id && !getBranchId()) {
                    const branch = branches.find(b => b.id === f.branch_id);
                    if (branch) {
                        branchInput.value = branch.name;
                        branchIdInput.value = String(branch.id);
                        lastBranchId = null;
                        branchWasAutoFilled = true;
                        // Mark branch as user interacted
                        branchInput.dataset.userInteracted = 'true';
                        // Load functions for this branch
                        await loadBranchFunctions(String(branch.id));
                    }
                }

                // Mark as user interacted and trigger validation after function is selected
                functionInput.dataset.userInteracted = 'true';

                // Small delay to ensure DOM is updated
                setTimeout(() => {
                    if (window.FormValidator && functionInput.form) {
                        const validator = functionInput.form._formValidator || 
                            Array.from(document.querySelectorAll('form[data-validate="true"]'))
                                .map(f => f._formValidator)
                                .find(v => v && v.form === functionInput.form);
                        
                        if (validator) {
                            const feedbackElement = functionInput.parentElement?.querySelector('.field-feedback') ||
                                functionInput.closest('.relative')?.parentElement?.querySelector('.field-feedback') ||
                                functionInput.closest('td')?.querySelector('.field-feedback');
                            validator.validateField(functionInput, feedbackElement, true);
                            
                            // Also validate branch if it was auto-filled
                            if (branchWasAutoFilled && branchInput) {
                                const branchFeedbackElement = branchInput.parentElement?.querySelector('.field-feedback') ||
                                    branchInput.closest('.relative')?.parentElement?.querySelector('.field-feedback') ||
                                    branchInput.closest('td')?.querySelector('.field-feedback');
                                validator.validateField(branchInput, branchFeedbackElement, true);
                            }
                        } else {
                            // Fallback: trigger input and blur events to trigger validation
                            functionInput.dispatchEvent(new Event('input', { bubbles: true }));
                            setTimeout(() => {
                                functionInput.dispatchEvent(new Event('blur', { bubbles: true }));
                                // Also trigger for branch if auto-filled
                                if (branchWasAutoFilled && branchInput) {
                                    branchInput.dispatchEvent(new Event('input', { bubbles: true }));
                                    setTimeout(() => {
                                        branchInput.dispatchEvent(new Event('blur', { bubbles: true }));
                                    }, 10);
                                }
                            }, 10);
                        }
                    } else {
                        // Fallback: trigger input and blur events to trigger validation
                        functionInput.dispatchEvent(new Event('input', { bubbles: true }));
                        setTimeout(() => {
                            functionInput.dispatchEvent(new Event('blur', { bubbles: true }));
                            // Also trigger for branch if auto-filled
                            if (branchWasAutoFilled && branchInput) {
                                branchInput.dispatchEvent(new Event('input', { bubbles: true }));
                                setTimeout(() => {
                                    branchInput.dispatchEvent(new Event('blur', { bubbles: true }));
                                }, 10);
                            }
                        }, 10);
                    }
                }, 50);
                
                // Reset manual edit flags when function changes (if Auto is enabled)
                if (typeof metaTitleManuallyEdited !== 'undefined' && autoMetaTitle?.checked) {
                    metaTitleManuallyEdited = false;
                }
                if (typeof metaDescriptionManuallyEdited !== 'undefined' && autoMetaDescription?.checked) {
                    metaDescriptionManuallyEdited = false;
                }
                if (typeof metaKeywordsManuallyEdited !== 'undefined' && autoMetaKeywords?.checked) {
                    metaKeywordsManuallyEdited = false;
                }
                
                // Trigger SEO meta data regeneration immediately (force update)
                setTimeout(() => {
                    if (typeof generateMetaTitle === 'function') {
                        generateMetaTitle(true);
                    }
                    if (typeof generateMetaDescription === 'function') {
                        generateMetaDescription(true);
                    }
                    if (typeof generateMetaKeywords === 'function') {
                        generateMetaKeywords(true);
                    }
                }, 10);
                
                // reset skills for new function selection
                skillsTouched = false;
                skills = [];
                serializeSkills();
                renderChips();
                const branchId = getBranchId();
                if (branchId && selectedFunctionId) {
                    // Don't auto-load skills, just show the button
                    await checkAndShowLoadButton(branchId, selectedFunctionId);
                } else {
                    if (loadDefaultSkillsBtn) loadDefaultSkillsBtn.classList.add('hidden');
                }
            });

            suggestionsDiv.appendChild(item);
        });

        suggestionsDiv.classList.remove('hidden');
        selectedIndex = -1;
    }

    function filterBranches(query) {
        const q = (query || '').trim().toLowerCase();
        if (!q) return branches.slice(0, 50);
        return branches.filter(b => (b.name || '').toLowerCase().includes(q)).slice(0, 50);
    }

    function renderBranchSuggestions(list, query) {
        const currentQuery = (query || '').trim().toLowerCase();
        branchSuggestions.innerHTML = '';
        if (list.length === 0) {
            branchSuggestions.classList.add('hidden');
            return;
        }

        list.forEach((b, index) => {
            const item = document.createElement('div');
            item.className = 'px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer';
            if (currentQuery.length > 0 && (b.name || '').toLowerCase().includes(currentQuery)) {
                const safe = escapeHtml(b.name);
                const regex = new RegExp(`(${currentQuery})`, 'gi');
                item.innerHTML = safe.replace(regex, '<strong>$1</strong>');
            } else {
                item.textContent = b.name;
            }
            item.addEventListener('click', async function () {
                branchInput.value = b.name;
                branchIdInput.value = String(b.id);
                branchSuggestions.classList.add('hidden');
                lastBranchId = null;
                await loadBranchFunctions(getBranchId());
                renderSuggestions(filterFunctions(functionInput.value), functionInput.value);
                // Check if we should show load button for current function
                const branchId = getBranchId();
                if (branchId && selectedFunctionId) {
                    await checkAndShowLoadButton(branchId, selectedFunctionId);
                } else {
                    if (loadDefaultSkillsBtn) loadDefaultSkillsBtn.classList.add('hidden');
                }
                
                // Reset manual edit flags when branch changes (if Auto is enabled)
                if (typeof metaTitleManuallyEdited !== 'undefined' && autoMetaTitle?.checked) {
                    metaTitleManuallyEdited = false;
                }
                if (typeof metaDescriptionManuallyEdited !== 'undefined' && autoMetaDescription?.checked) {
                    metaDescriptionManuallyEdited = false;
                }
                if (typeof metaKeywordsManuallyEdited !== 'undefined' && autoMetaKeywords?.checked) {
                    metaKeywordsManuallyEdited = false;
                }
                
                // Trigger SEO meta data regeneration immediately (force update)
                setTimeout(() => {
                    if (typeof generateMetaTitle === 'function') {
                        generateMetaTitle(true);
                    }
                    if (typeof generateMetaDescription === 'function') {
                        generateMetaDescription(true);
                    }
                    if (typeof generateMetaKeywords === 'function') {
                        generateMetaKeywords(true);
                    }
                }, 10);
            });
            branchSuggestions.appendChild(item);
        });

        branchSuggestions.classList.remove('hidden');
        branchSelectedIndex = -1;
    }

    let allFunctionsLoading = false;
    let allFunctionsPromise = null;
    
    async function loadAllFunctions() {
        // If already loaded, return immediately
        if (allFunctions.length > 0) return allFunctions;
        
        // If currently loading, wait for that promise
        if (allFunctionsLoading && allFunctionsPromise) {
            return allFunctionsPromise;
        }
        
        // Start loading
        allFunctionsLoading = true;
        allFunctionsPromise = (async () => {
            try {
                const url = '{{ url("admin/branches/functions/all") }}';
                const res = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });
                
                if (!res.ok) {
                    throw new Error(`HTTP ${res.status}`);
                }
                
                const json = await res.json();
                allFunctions = Array.isArray(json?.functions) ? json.functions : [];
                
                // If no branch selected, also populate functions array
                if (!getBranchId() && allFunctions.length > 0) {
                    functions = allFunctions;
                }
                
                return allFunctions;
            } catch (e) {
                console.error('Error loading all functions:', e);
                allFunctions = [];
                return [];
            } finally {
                allFunctionsLoading = false;
                allFunctionsPromise = null;
            }
        })();
        
        return allFunctionsPromise;
    }

    async function loadBranchFunctions(branchId) {
        if (!branchId) {
            functions = [];
            lastBranchId = null;
            // Use all functions when no branch selected
            if (allFunctions.length > 0) {
                functions = allFunctions;
            }
            return;
        }

        if (lastBranchId === branchId) return;
        lastBranchId = branchId;

        try {
            const url = `{{ url('admin/branches') }}/${encodeURIComponent(branchId)}/data`;
            const res = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            });
            
            if (!res.ok) {
                throw new Error(`HTTP ${res.status}`);
            }
            
            const json = await res.json();
            functions = Array.isArray(json?.functions) ? json.functions : [];
        } catch (e) {
            console.error('Error loading branch functions:', e);
            functions = [];
        }
    }

    async function ensureFunctionExists(branchId, displayName) {
        if (!branchId) return null;
        const value = (displayName || '').trim();
        if (!value) return null;

        // If it already exists (case-insensitive), return its ID
        const existing = functions.find(f => (f.display_name || '').toLowerCase() === value.toLowerCase());
        if (existing) return existing.id;

        try {
            const res = await fetch(`{{ url('admin/branches') }}/${encodeURIComponent(branchId)}/functions`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                },
                credentials: 'same-origin',
                body: JSON.stringify({ name: value }),
            });
            const json = await res.json().catch(() => null);
            if (res.ok && json?.function) {
                functions.push({
                    id: json.function.id,
                    name: json.function.name,
                    display_name: json.function.display_name,
                });
                // return id for later
                return json.function.id;
            }
        } catch (_) {}
        return null;
    }

    // Initial load: load all functions first, then branch-specific if branch is selected
    (async function() {
        try {
            await loadAllFunctions();
            const branchId = getBranchId();
            if (branchId) {
                await loadBranchFunctions(branchId);
            } else {
                // Use all functions when no branch selected
                functions = allFunctions.length > 0 ? allFunctions : [];
            }
            
            // Preselect function id from current input if possible
            const current = (functionInput.value || '').trim().toLowerCase();
            if (current) {
                const match = functions.find(f => {
                    const displayName = (f.display_name || f.name || '').toLowerCase();
                    return displayName === current;
                }) || allFunctions.find(f => {
                    const displayName = (f.display_name || f.name || '').toLowerCase();
                    return displayName === current;
                });
                
                if (match && match.id) {
                    selectedFunctionId = match.id;
                    if (branchFunctionIdInput) branchFunctionIdInput.value = String(match.id);
                    
                    // Auto-fill branch if function has branch info
                    if (match.branch_id && !branchId) {
                        const branch = branches.find(b => b.id === match.branch_id);
                        if (branch) {
                            branchInput.value = branch.name;
                            branchIdInput.value = String(branch.id);
                            branchInput.dataset.userInteracted = 'true';
                            await loadBranchFunctions(String(branch.id));
                            
                            // Trigger validation for branch
                            setTimeout(() => {
                                if (window.FormValidator && branchInput.form) {
                                    const validator = branchInput.form._formValidator || 
                                        Array.from(document.querySelectorAll('form[data-validate="true"]'))
                                            .map(f => f._formValidator)
                                            .find(v => v && v.form === branchInput.form);
                                    
                                    if (validator) {
                                        const branchFeedbackElement = branchInput.parentElement?.querySelector('.field-feedback') ||
                                            branchInput.closest('.relative')?.parentElement?.querySelector('.field-feedback') ||
                                            branchInput.closest('td')?.querySelector('.field-feedback');
                                        validator.validateField(branchInput, branchFeedbackElement, true);
                                    } else {
                                        branchInput.dispatchEvent(new Event('input', { bubbles: true }));
                                        setTimeout(() => {
                                            branchInput.dispatchEvent(new Event('blur', { bubbles: true }));
                                        }, 10);
                                    }
                                } else {
                                    branchInput.dispatchEvent(new Event('input', { bubbles: true }));
                                    setTimeout(() => {
                                        branchInput.dispatchEvent(new Event('blur', { bubbles: true }));
                                    }, 10);
                                }
                            }, 50);
                        }
                    }
                }
            }

            // Don't auto-load skills, just check if we should show the load button
            const finalBranchId = getBranchId();
            if (finalBranchId && selectedFunctionId) {
                checkAndShowLoadButton(finalBranchId, selectedFunctionId);
            } else if (loadDefaultSkillsBtn) {
                loadDefaultSkillsBtn.classList.add('hidden');
            }
        } catch (error) {
            console.error('Error during initial load:', error);
        }
    })();

    // Branch autocomplete
    branchInput.addEventListener('input', function (e) {
        // typing invalidates selection until chosen
        branchIdInput.value = '';
        lastBranchId = null;
        renderBranchSuggestions(filterBranches(e.target.value), e.target.value);
    });

    branchInput.addEventListener('focus', function (e) {
        renderBranchSuggestions(filterBranches(e.target.value), e.target.value);
    });

    branchInput.addEventListener('keydown', async function (e) {
        const items = branchSuggestions.querySelectorAll('div');

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            branchSelectedIndex = Math.min(branchSelectedIndex + 1, items.length - 1);
            items.forEach((it, idx) => it.classList.toggle('bg-gray-100', idx === branchSelectedIndex));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            branchSelectedIndex = Math.max(branchSelectedIndex - 1, -1);
            items.forEach((it, idx) => it.classList.toggle('bg-gray-100', idx === branchSelectedIndex));
        } else if (e.key === 'Enter') {
            if (branchSelectedIndex >= 0 && items[branchSelectedIndex]) {
                e.preventDefault();
                items[branchSelectedIndex].click();
            } else {
                // If exact match exists, pick it
                const q = branchInput.value.trim().toLowerCase();
                const exact = branches.find(b => (b.name || '').toLowerCase() === q);
                if (exact) {
                    e.preventDefault();
                    branchInput.value = exact.name;
                    branchIdInput.value = String(exact.id);
                    branchSuggestions.classList.add('hidden');
                    lastBranchId = null;
                    await loadBranchFunctions(getBranchId());
                    const filtered = filterFunctions(functionInput.value);
                    renderSuggestions(filtered, functionInput.value);
                    // Check if we should show load button for current function
                    const branchId = getBranchId();
                    if (branchId && selectedFunctionId) {
                        await checkAndShowLoadButton(branchId, selectedFunctionId);
                    } else {
                        if (loadDefaultSkillsBtn) loadDefaultSkillsBtn.classList.add('hidden');
                    }
                }
            }
        } else if (e.key === 'Escape') {
            branchSuggestions.classList.add('hidden');
        }
    });

    functionInput.addEventListener('input', async function (e) {
        const query = e.target.value;

        // typing invalidates selected function id (until user picks one)
        selectedFunctionId = null;
        if (branchFunctionIdInput) branchFunctionIdInput.value = '';
        if (loadDefaultSkillsBtn) loadDefaultSkillsBtn.classList.add('hidden');
        if (!skillsTouched) {
            skills = [];
            serializeSkills();
            renderChips();
        }

        // If function field is cleared, also clear the branch field
        if (!query || query.trim() === '') {
            branchInput.value = '';
            branchIdInput.value = '';
            lastBranchId = null;
            // Reset branch validation
            branchInput.dataset.userInteracted = 'false';
            if (window.FormValidator && branchInput.form) {
                const validator = branchInput.form._formValidator || 
                    Array.from(document.querySelectorAll('form[data-validate="true"]'))
                        .map(f => f._formValidator)
                        .find(v => v && v.form === branchInput.form);
                if (validator) {
                    const branchFeedbackElement = branchInput.parentElement?.querySelector('.field-feedback') ||
                        branchInput.closest('.relative')?.parentElement?.querySelector('.field-feedback') ||
                        branchInput.closest('td')?.querySelector('.field-feedback');
                    validator.validateField(branchInput, branchFeedbackElement, false);
                }
            }
        }

        // Ensure all functions are loaded first
        await loadAllFunctions();

        // If branch is selected, load branch functions; otherwise use all functions
        const branchId = getBranchId();
        if (branchId) {
            await loadBranchFunctions(branchId);
        } else {
            // Use all functions when no branch selected
            functions = allFunctions.length > 0 ? allFunctions : [];
        }

        // Filter and render suggestions
        const filtered = filterFunctions(query);
        renderSuggestions(filtered, query);
    });

    functionInput.addEventListener('focus', async function (e) {
        const query = e.target.value || '';
        
        // Ensure all functions are loaded first
        await loadAllFunctions();
        
        const branchId = getBranchId();
        if (branchId) {
            await loadBranchFunctions(branchId);
        } else {
            // Use all functions when no branch selected
            functions = allFunctions.length > 0 ? allFunctions : [];
        }
        
        // Filter and render suggestions
        const filtered = filterFunctions(query);
        renderSuggestions(filtered, query);
        
        // Check if current function has skills
        const currentValue = query.trim().toLowerCase();
        if (currentValue) {
            const match = functions.find(f => {
                const displayName = (f.display_name || f.name || '').toLowerCase();
                return displayName === currentValue;
            }) || allFunctions.find(f => {
                const displayName = (f.display_name || f.name || '').toLowerCase();
                return displayName === currentValue;
            });
            
            if (match && match.id) {
                selectedFunctionId = match.id;
                if (branchFunctionIdInput) branchFunctionIdInput.value = String(match.id);
                
                // Auto-fill branch if function has branch info
                if (match.branch_id && !branchId) {
                    const branch = branches.find(b => b.id === match.branch_id);
                    if (branch) {
                        branchInput.value = branch.name;
                        branchIdInput.value = String(branch.id);
                        branchInput.dataset.userInteracted = 'true';
                        await loadBranchFunctions(String(branch.id));
                        
                        // Trigger validation for branch
                        setTimeout(() => {
                            if (window.FormValidator && branchInput.form) {
                                const validator = branchInput.form._formValidator || 
                                    Array.from(document.querySelectorAll('form[data-validate="true"]'))
                                        .map(f => f._formValidator)
                                        .find(v => v && v.form === branchInput.form);
                                
                                if (validator) {
                                    const branchFeedbackElement = branchInput.parentElement?.querySelector('.field-feedback') ||
                                        branchInput.closest('.relative')?.parentElement?.querySelector('.field-feedback') ||
                                        branchInput.closest('td')?.querySelector('.field-feedback');
                                    validator.validateField(branchInput, branchFeedbackElement, true);
                                } else {
                                    branchInput.dispatchEvent(new Event('input', { bubbles: true }));
                                    setTimeout(() => {
                                        branchInput.dispatchEvent(new Event('blur', { bubbles: true }));
                                    }, 10);
                                }
                            } else {
                                branchInput.dispatchEvent(new Event('input', { bubbles: true }));
                                setTimeout(() => {
                                    branchInput.dispatchEvent(new Event('blur', { bubbles: true }));
                                }, 10);
                            }
                        }, 50);
                    }
                }
                
                const finalBranchId = getBranchId();
                if (finalBranchId) {
                    checkAndShowLoadButton(finalBranchId, match.id);
                }
            }
        }
    });

    functionInput.addEventListener('keydown', async function (e) {
        const items = suggestionsDiv.querySelectorAll('div');

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
            items.forEach((it, idx) => it.classList.toggle('bg-gray-100', idx === selectedIndex));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            selectedIndex = Math.max(selectedIndex - 1, -1);
            items.forEach((it, idx) => it.classList.toggle('bg-gray-100', idx === selectedIndex));
        } else if (e.key === 'Enter') {
            // Use highlighted suggestion if any; otherwise keep free input
            if (selectedIndex >= 0 && items[selectedIndex]) {
                e.preventDefault();
                items[selectedIndex].click();
            } else {
                // Save as branch function (if branch selected) but keep value as display text
                const branchId = getBranchId();
                // If no branch selected, we can't create a function
                if (!branchId) {
                    suggestionsDiv.classList.add('hidden');
                    return;
                }
                
                const newId = await ensureFunctionExists(branchId, functionInput.value);
                if (newId) {
                    selectedFunctionId = newId;
                    if (branchFunctionIdInput) branchFunctionIdInput.value = String(newId);
                    
                    // Add to allFunctions and refresh
                    await loadAllFunctions();
                    
                    // Mark as user interacted and trigger validation after function is created/selected
                    functionInput.dataset.userInteracted = 'true';
                    
                    // Small delay to ensure DOM is updated
                    setTimeout(() => {
                        if (window.FormValidator && functionInput.form) {
                            const validator = functionInput.form._formValidator || 
                                Array.from(document.querySelectorAll('form[data-validate="true"]'))
                                    .map(f => f._formValidator)
                                    .find(v => v && v.form === functionInput.form);
                            
                            if (validator) {
                                const feedbackElement = functionInput.parentElement?.querySelector('.field-feedback') ||
                                    functionInput.closest('.relative')?.parentElement?.querySelector('.field-feedback') ||
                                    functionInput.closest('td')?.querySelector('.field-feedback');
                                validator.validateField(functionInput, feedbackElement, true);
                            } else {
                                // Fallback: trigger input and blur events to trigger validation
                                functionInput.dispatchEvent(new Event('input', { bubbles: true }));
                                setTimeout(() => {
                                    functionInput.dispatchEvent(new Event('blur', { bubbles: true }));
                                }, 10);
                            }
                        } else {
                            // Fallback: trigger input and blur events to trigger validation
                            functionInput.dispatchEvent(new Event('input', { bubbles: true }));
                            setTimeout(() => {
                                functionInput.dispatchEvent(new Event('blur', { bubbles: true }));
                            }, 10);
                        }
                    }, 50);
                    
                    // Reset manual edit flags when function changes (if Auto is enabled)
                    if (typeof metaTitleManuallyEdited !== 'undefined' && autoMetaTitle?.checked) {
                        metaTitleManuallyEdited = false;
                    }
                    if (typeof metaDescriptionManuallyEdited !== 'undefined' && autoMetaDescription?.checked) {
                        metaDescriptionManuallyEdited = false;
                    }
                    if (typeof metaKeywordsManuallyEdited !== 'undefined' && autoMetaKeywords?.checked) {
                        metaKeywordsManuallyEdited = false;
                    }
                    
                    // Trigger SEO meta data regeneration immediately (force update)
                    setTimeout(() => {
                        if (typeof generateMetaTitle === 'function') {
                            generateMetaTitle(true);
                        }
                        if (typeof generateMetaDescription === 'function') {
                            generateMetaDescription(true);
                        }
                        if (typeof generateMetaKeywords === 'function') {
                            generateMetaKeywords(true);
                        }
                    }, 10);
                    
                    // Don't auto-load skills, just show the button
                    await checkAndShowLoadButton(branchId, newId);
                } else {
                    if (loadDefaultSkillsBtn) loadDefaultSkillsBtn.classList.add('hidden');
                }
                suggestionsDiv.classList.add('hidden');
            }
        } else if (e.key === 'Escape') {
            suggestionsDiv.classList.add('hidden');
        }
    });

    document.addEventListener('click', function (e) {
        if (!functionInput.contains(e.target) && !suggestionsDiv.contains(e.target)) suggestionsDiv.classList.add('hidden');
        if (!branchInput.contains(e.target) && !branchSuggestions.contains(e.target)) branchSuggestions.classList.add('hidden');
    });

    // Prevent submit if branch isn't selected
    const form = branchInput.closest('form');
    if (form) {
        form.addEventListener('submit', function (e) {
            if (!branchIdInput.value) {
                e.preventDefault();
                branchInput.focus();
                branchInput.setCustomValidity('Selecteer een branch uit de lijst.');
                branchInput.reportValidity();
                setTimeout(() => branchInput.setCustomValidity(''), 0);
            }
        });
    }

    // Setup initial skills from old('required_skills')
    if (requiredSkillsInput && requiredSkillsInput.value) {
        try {
            const decoded = JSON.parse(requiredSkillsInput.value);
            if (Array.isArray(decoded)) {
                skills = decoded.map(normalizeSkill).filter(Boolean);
                skillsTouched = true;
                serializeSkills();
                renderChips();
            }
        } catch (_) {}
    } else {
        serializeSkills();
        renderChips();
    }

    // Modal for adding skill
    function ensureModal() {
        let modal = document.getElementById('skill-modal');
        if (modal) return modal;
        modal = document.createElement('div');
        modal.id = 'skill-modal';
        modal.className = 'fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-background rounded-lg p-6 w-full max-w-md mx-4 relative border border-border shadow-xl">
                <button type="button" id="skill-modal-close" class="absolute top-4 right-4 text-muted-foreground hover:text-foreground text-2xl leading-none">&times;</button>
                <h3 class="text-lg font-semibold mb-4">Vaardigheid Toevoegen</h3>
                <form id="skill-modal-form" class="flex flex-col gap-4">
                    <div>
                        <label class="kt-form-label font-normal text-mono mb-2">Naam</label>
                        <input type="text" id="skill-modal-input" class="kt-input" placeholder="Vaardigheid naam" required>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" id="skill-modal-cancel" class="kt-btn kt-btn-outline flex-1 justify-center">Annuleren</button>
                        <button type="submit" class="kt-btn kt-btn-primary flex-1 justify-center">Toevoegen</button>
                    </div>
                </form>
            </div>
        `;
        document.body.appendChild(modal);
        return modal;
    }

    function openModal() {
        const modal = ensureModal();
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        const input = modal.querySelector('#skill-modal-input');
        if (input) {
            input.value = '';
            input.focus();
        }
    }

    function closeModal() {
        const modal = ensureModal();
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    if (addSkillBtn) {
        addSkillBtn.addEventListener('click', openModal);
    }

    if (loadDefaultSkillsBtn) {
        loadDefaultSkillsBtn.addEventListener('click', async function () {
            const branchId = getBranchId();
            const functionId = branchFunctionIdInput?.value || selectedFunctionId;
            if (branchId && functionId) {
                await loadDefaultSkills(branchId, functionId, true);
            }
        });
    }

    const modal = ensureModal();
    modal.querySelector('#skill-modal-close')?.addEventListener('click', closeModal);
    modal.querySelector('#skill-modal-cancel')?.addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });
    
    // Close modal on Esc key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
    modal.querySelector('#skill-modal-form')?.addEventListener('submit', async function (e) {
        e.preventDefault();
        const input = modal.querySelector('#skill-modal-input');
        const value = normalizeSkill(input?.value || '');
        if (!value) return;

        const exists = skills.some(s => s.toLowerCase() === value.toLowerCase());
        if (!exists) {
            skills.push(value);
            skillsTouched = true;
            serializeSkills();
            renderChips();

            const branchId = getBranchId();
            const functionId = branchFunctionIdInput?.value || selectedFunctionId;
            if (branchId && functionId) {
                await saveDefaultSkill(branchId, functionId, value);
            }
        }

        closeModal();
    });

    // ===== SEO Meta Data Auto-Generation =====
    const metaTitleInput = document.getElementById('meta_title');
    const metaDescriptionInput = document.getElementById('meta_description');
    const metaKeywordsInput = document.getElementById('meta_keywords');
    const autoMetaTitle = document.getElementById('auto-meta-title');
    const autoMetaDescription = document.getElementById('auto-meta-description');
    const autoMetaKeywords = document.getElementById('auto-meta-keywords');
    const metaTitleLength = document.getElementById('meta-title-length');
    const metaDescriptionLength = document.getElementById('meta-description-length');
    const metaKeywordsCount = document.getElementById('meta-keywords-count');

    // Track if user manually edited meta fields
    let metaTitleManuallyEdited = false;
    let metaDescriptionManuallyEdited = false;
    let metaKeywordsManuallyEdited = false;

    // Generate SEO-friendly meta title (50-60 chars ideal)
    function generateMetaTitle(force = false) {
        if (!force && (!autoMetaTitle?.checked || metaTitleManuallyEdited)) return;
        
        const functionName = functionInput?.value?.trim() || '';
        const companyName = document.querySelector('select[name="company_id"]')?.selectedOptions[0]?.text?.trim() || 
                          document.querySelector('input[name="company_id"]')?.value || '';
        // Get location from select or custom input
        const locationSelect = document.getElementById('location-select');
        const locationCustomInput = document.getElementById('location-custom-input');
        let location = '';
        if (locationCustomInput && locationCustomInput.style.display !== 'none' && locationCustomInput.value) {
            location = locationCustomInput.value.trim();
        } else if (locationSelect && locationSelect.value && locationSelect.value !== '__custom__') {
            location = locationSelect.value.trim();
        }
        const employmentType = document.querySelector('select[name="employment_type"]')?.value?.trim() || '';
        
        let title = '';
        
        if (functionName) {
            title = functionName;
        }
        
        if (companyName && companyName !== '') {
            if (title) title += ' bij ' + companyName;
            else title = companyName;
        }
        
        if (location) {
            if (title) title += ' in ' + location;
            else title = 'Vacature in ' + location;
        }
        
        if (employmentType && employmentType !== '-') {
            title += ' | ' + employmentType;
        }
        
        if (!title) {
            title = 'Vacature';
        }
        
        // Don't truncate - show full text, but length indicator will show red if over limit
        if (metaTitleInput) {
            metaTitleInput.value = title;
            updateMetaTitleLength();
        }
    }

    // Generate SEO-friendly meta description (150-160 chars ideal)
    function generateMetaDescription(force = false) {
        if (!force && (!autoMetaDescription?.checked || metaDescriptionManuallyEdited)) return;
        
        const functionName = functionInput?.value?.trim() || '';
        // Get location from select or custom input
        const locationSelect = document.getElementById('location-select');
        const locationCustomInput = document.getElementById('location-custom-input');
        let location = '';
        if (locationCustomInput && locationCustomInput.style.display !== 'none' && locationCustomInput.value) {
            location = locationCustomInput.value.trim();
        } else if (locationSelect && locationSelect.value && locationSelect.value !== '__custom__') {
            location = locationSelect.value.trim();
        }
        const employmentType = document.querySelector('select[name="employment_type"]')?.value?.trim() || '';
        const description = document.querySelector('textarea[name="description"]')?.value?.trim() || '';
        const requirements = document.querySelector('textarea[name="requirements"]')?.value?.trim() || '';
        
        let desc = '';
        
        // Start with function name if available
        if (functionName) {
            desc = functionName;
        } else {
            desc = 'Vacature';
        }
        
        // Add location
        if (location) {
            desc += ' in ' + location;
        }
        
        // Add employment type
        if (employmentType && employmentType !== '-') {
            desc += ' (' + employmentType + ')';
        }
        
        desc += '. ';
        
        // Add description or requirements
        const content = description || requirements || '';
        if (content) {
            // Clean HTML tags and get first meaningful sentence
            const cleanContent = content.replace(/<[^>]*>/g, '').trim();
            const firstSentence = cleanContent.split(/[.!?]/)[0] || cleanContent;
            // Use full first sentence, don't truncate
            desc += firstSentence;
        } else {
            desc += 'Solliciteer nu voor deze interessante functie.';
        }
        
        // Don't truncate - show full text, but length indicator will show red if over limit
        if (metaDescriptionInput) {
            metaDescriptionInput.value = desc;
            updateMetaDescriptionLength();
        }
    }

    // Generate SEO-friendly meta keywords
    function generateMetaKeywords(force = false) {
        if (!force && (!autoMetaKeywords?.checked || metaKeywordsManuallyEdited)) return;
        
        const keywords = new Set();
        
        // Base keywords
        keywords.add('vacature');
        keywords.add('werk');
        keywords.add('baan');
        keywords.add('sollicitatie');
        keywords.add('carrière');
        
        // Function name
        const functionName = functionInput?.value?.trim() || '';
        if (functionName) {
            const functionWords = functionName.toLowerCase().split(/\s+/).filter(w => w.length > 2);
            functionWords.forEach(w => keywords.add(w));
        }
        
        // Branch
        const branchName = branchInput?.value?.trim() || '';
        if (branchName) {
            keywords.add(branchName.toLowerCase());
        }
        
        // Location
        // Get location from select or custom input
        const locationSelect = document.getElementById('location-select');
        const locationCustomInput = document.getElementById('location-custom-input');
        let location = '';
        if (locationCustomInput && locationCustomInput.style.display !== 'none' && locationCustomInput.value) {
            location = locationCustomInput.value.trim();
        } else if (locationSelect && locationSelect.value && locationSelect.value !== '__custom__') {
            location = locationSelect.value.trim();
        }
        if (location) {
            const locationWords = location.toLowerCase().split(/[,\s]+/).filter(w => w.length > 2);
            locationWords.forEach(w => keywords.add(w));
        }
        
        // Employment type
        const employmentType = document.querySelector('select[name="employment_type"]')?.value?.trim() || '';
        if (employmentType && employmentType !== '-') {
            keywords.add(employmentType.toLowerCase());
        }
        
        // Company name
        const companySelect = document.querySelector('select[name="company_id"]');
        if (companySelect) {
            const companyName = companySelect.selectedOptions[0]?.text?.trim() || '';
            if (companyName) {
                const companyWords = companyName.toLowerCase().split(/\s+/).filter(w => w.length > 2);
                companyWords.forEach(w => keywords.add(w));
            }
        }
        
        // Remote work
        const remoteWork = document.querySelector('input[name="remote_work"]')?.checked;
        if (remoteWork) {
            keywords.add('remote');
            keywords.add('thuiswerken');
            keywords.add('hybride');
        }
        
        // Skills from chips
        if (skills && skills.length > 0) {
            skills.slice(0, 5).forEach(skill => {
                const skillWords = skill.toLowerCase().split(/\s+/).filter(w => w.length > 2);
                skillWords.forEach(w => keywords.add(w));
            });
        }
        
        // Convert to comma-separated string, limit to reasonable amount
        const keywordsArray = Array.from(keywords).slice(0, 15);
        const keywordsString = keywordsArray.join(', ');
        
        if (metaKeywordsInput) {
            metaKeywordsInput.value = keywordsString;
            updateMetaKeywordsCount();
        }
    }

    // Update length indicators
    function updateMetaTitleLength() {
        if (metaTitleLength && metaTitleInput) {
            const length = metaTitleInput.value.length;
            metaTitleLength.textContent = length;
            if (length < 50) {
                metaTitleLength.className = 'text-xs text-orange-500';
            } else if (length > 60) {
                metaTitleLength.className = 'text-xs text-red-500';
            } else {
                metaTitleLength.className = 'text-xs text-green-500';
            }
        }
    }

    function updateMetaDescriptionLength() {
        if (metaDescriptionLength && metaDescriptionInput) {
            const length = metaDescriptionInput.value.length;
            metaDescriptionLength.textContent = length;
            if (length < 120) {
                metaDescriptionLength.className = 'text-xs text-orange-500';
            } else if (length > 160) {
                metaDescriptionLength.className = 'text-xs text-red-500';
            } else {
                metaDescriptionLength.className = 'text-xs text-green-500';
            }
        }
    }

    function updateMetaKeywordsCount() {
        if (metaKeywordsCount && metaKeywordsInput) {
            const keywords = metaKeywordsInput.value.split(',').filter(k => k.trim() !== '');
            metaKeywordsCount.textContent = keywords.length;
            if (keywords.length < 5) {
                metaKeywordsCount.className = 'text-xs text-orange-500';
            } else if (keywords.length > 15) {
                metaKeywordsCount.className = 'text-xs text-red-500';
            } else {
                metaKeywordsCount.className = 'text-xs text-green-500';
            }
        }
    }

    // Auto-generate on field changes
    function setupSEOAutoGeneration() {
        // Listen to relevant field changes
        const locationSelectEl = document.getElementById('location-select');
        const locationCustomInputEl = document.getElementById('location-custom-input');
        const fieldsToWatch = [
            functionInput,
            branchInput,
            locationSelectEl,
            locationCustomInputEl,
            document.querySelector('select[name="employment_type"]'),
            document.querySelector('select[name="company_id"]'),
            document.querySelector('textarea[name="description"]'),
            document.querySelector('textarea[name="requirements"]'),
            document.querySelector('input[name="remote_work"]')
        ];

        fieldsToWatch.forEach(field => {
            if (field) {
                field.addEventListener('input', () => {
                    // Only auto-generate if Auto is enabled and not manually edited
                    if (autoMetaTitle?.checked && !metaTitleManuallyEdited) {
                        generateMetaTitle();
                    }
                    if (autoMetaDescription?.checked && !metaDescriptionManuallyEdited) {
                        generateMetaDescription();
                    }
                    if (autoMetaKeywords?.checked && !metaKeywordsManuallyEdited) {
                        generateMetaKeywords();
                    }
                });
                field.addEventListener('change', () => {
                    // Only auto-generate if Auto is enabled and not manually edited
                    if (autoMetaTitle?.checked && !metaTitleManuallyEdited) {
                        generateMetaTitle();
                    }
                    if (autoMetaDescription?.checked && !metaDescriptionManuallyEdited) {
                        generateMetaDescription();
                    }
                    if (autoMetaKeywords?.checked && !metaKeywordsManuallyEdited) {
                        generateMetaKeywords();
                    }
                });
            }
        });

        // Listen to skills changes
        const originalSerializeSkills = serializeSkills;
        serializeSkills = function() {
            originalSerializeSkills();
            generateMetaKeywords();
        };

        // Track manual edits
        if (metaTitleInput) {
            metaTitleInput.addEventListener('input', function() {
                if (!autoMetaTitle?.checked) {
                    metaTitleManuallyEdited = true;
                }
                updateMetaTitleLength();
            });
            metaTitleInput.addEventListener('focus', function() {
                if (autoMetaTitle?.checked) {
                    metaTitleManuallyEdited = false;
                }
            });
        }

        if (metaDescriptionInput) {
            metaDescriptionInput.addEventListener('input', function() {
                if (!autoMetaDescription?.checked) {
                    metaDescriptionManuallyEdited = true;
                }
                updateMetaDescriptionLength();
            });
            metaDescriptionInput.addEventListener('focus', function() {
                if (autoMetaDescription?.checked) {
                    metaDescriptionManuallyEdited = false;
                }
            });
        }

        if (metaKeywordsInput) {
            metaKeywordsInput.addEventListener('input', function() {
                if (!autoMetaKeywords?.checked) {
                    metaKeywordsManuallyEdited = true;
                }
                updateMetaKeywordsCount();
            });
            metaKeywordsInput.addEventListener('focus', function() {
                if (autoMetaKeywords?.checked) {
                    metaKeywordsManuallyEdited = false;
                }
            });
        }

        // Toggle auto-generation
        if (autoMetaTitle) {
            autoMetaTitle.addEventListener('change', function() {
                if (this.checked) {
                    metaTitleManuallyEdited = false;
                    generateMetaTitle();
                }
            });
        }

        if (autoMetaDescription) {
            autoMetaDescription.addEventListener('change', function() {
                if (this.checked) {
                    metaDescriptionManuallyEdited = false;
                    generateMetaDescription();
                }
            });
        }

        if (autoMetaKeywords) {
            autoMetaKeywords.addEventListener('change', function() {
                if (this.checked) {
                    metaKeywordsManuallyEdited = false;
                    generateMetaKeywords();
                }
            });
        }

        // Initial generation
        setTimeout(() => {
            generateMetaTitle();
            generateMetaDescription();
            generateMetaKeywords();
        }, 500);
    }

    // Initialize SEO auto-generation
    if (metaTitleInput && metaDescriptionInput && metaKeywordsInput) {
        setupSEOAutoGeneration();
    }

    // Mark contact user select wrapper for dropdown z-index fix (fallback for browsers without :has() support)
    const contactUserSelect = document.querySelector('select[name="contact_user_id"]');
    if (contactUserSelect) {
        const wrapper = contactUserSelect.closest('.kt-select-wrapper') || contactUserSelect.parentElement;
        if (wrapper) {
            wrapper.setAttribute('data-contact-user-select', 'true');
        }

        // Voorkom dat dropdown over header gaat en pas positionering aan
        const selectElement = wrapper ? wrapper.querySelector('.kt-select') : contactUserSelect;
        if (selectElement) {
            // Wacht tot KT Select is geïnitialiseerd
            setTimeout(() => {
                // Zoek de dropdown element
                const findDropdown = () => {
                    return wrapper.querySelector('.kt-select-dropdown') || wrapper.querySelector('[data-kt-select-dropdown]');
                };

                // Functie om dropdown te positioneren
                const positionDropdown = (dropdown) => {
                    if (!dropdown || !dropdown.classList.contains('open')) return;
                    
                    const rect = selectElement.getBoundingClientRect();
                    const headerHeight = 90; // Header hoogte
                    const spaceBelow = window.innerHeight - rect.bottom;
                    const spaceAbove = rect.top - headerHeight;
                    
                    // Verplaats dropdown naar body - ALTIJD, om stacking context te vermijden
                    if (dropdown.parentElement !== document.body) {
                        const originalParent = dropdown.parentElement;
                        document.body.appendChild(dropdown);
                        dropdown._originalParent = originalParent;
                        dropdown._originalWrapper = wrapper;
                    }
                    
                    // Bereken beschikbare ruimte
                    const availableSpaceBelow = spaceBelow - 10;
                    const availableSpaceAbove = spaceAbove - 10;
                    const maxHeight = Math.max(300, Math.min(600, availableSpaceBelow, availableSpaceAbove));
                    
                    // Positioneer dropdown
                    dropdown.style.cssText = `
                        position: fixed !important;
                        top: ${rect.bottom + 4}px !important;
                        left: ${rect.left}px !important;
                        width: ${rect.width}px !important;
                        max-height: ${maxHeight}px !important;
                        overflow-y: auto !important;
                        overflow-x: hidden !important;
                        z-index: 999 !important;
                        margin: 0 !important;
                        padding: 0 !important;
                        display: block !important;
                        visibility: visible !important;
                    `;
                    
                    // Als er niet genoeg ruimte onder is, open naar boven
                    if (availableSpaceBelow < 300 && availableSpaceAbove > availableSpaceBelow) {
                        dropdown.style.top = 'auto';
                        dropdown.style.bottom = `${window.innerHeight - rect.top + 4}px`;
                    }
                };

                // Observer voor wanneer dropdown wordt getoond
                const observer = new MutationObserver(() => {
                    const dropdown = findDropdown();
                    if (dropdown && dropdown.classList.contains('open')) {
                        // Wacht even zodat dropdown volledig is gerenderd
                        setTimeout(() => positionDropdown(dropdown), 10);
                    } else if (dropdown && !dropdown.classList.contains('open') && dropdown._originalParent) {
                        // Verplaats dropdown terug naar originele parent wanneer gesloten
                        dropdown._originalParent.appendChild(dropdown);
                        dropdown._originalParent = null;
                        dropdown._originalWrapper = null;
                        // Reset styles
                        dropdown.style.cssText = '';
                    }
                });

                // Observeer wrapper voor changes
                observer.observe(wrapper, {
                    childList: true,
                    subtree: true,
                    attributes: true,
                    attributeFilter: ['class']
                });

                // Ook luisteren naar click events
                selectElement.addEventListener('click', () => {
                    setTimeout(() => {
                        const dropdown = findDropdown();
                        if (dropdown && dropdown.classList.contains('open')) {
                            positionDropdown(dropdown);
                        }
                    }, 100);
                });

                // Luister naar scroll en resize om dropdown positie bij te werken
                let resizeTimeout;
                const updateDropdownPosition = () => {
                    const dropdown = findDropdown();
                    if (dropdown && dropdown.classList.contains('open')) {
                        positionDropdown(dropdown);
                    }
                };
                
                window.addEventListener('scroll', updateDropdownPosition, true);
                window.addEventListener('resize', () => {
                    clearTimeout(resizeTimeout);
                    resizeTimeout = setTimeout(updateDropdownPosition, 100);
                });
            }, 500);
        }
    }
    
    // Dynamic salary range based on employment type
    const employmentTypeSelect = document.querySelector('select[name="employment_type"]');
    const salaryRangeSelect = document.getElementById('salary_range_select');
    const salaryBrutoPerMaand = @json($salaryBrutoPerMaand ?? []);
    const salaryZzpUurtarief = @json($salaryZzpUurtarief ?? []);
    
    function updateSalaryOptions() {
        if (!employmentTypeSelect || !salaryRangeSelect) return;
        
        const selectedEmploymentType = employmentTypeSelect.value;
        const currentValue = salaryRangeSelect.value;
        
        // Clear existing options except the first one
        while (salaryRangeSelect.options.length > 1) {
            salaryRangeSelect.remove(1);
        }
        
        // Determine which salary options to use
        let salaryOptions = [];
        if (selectedEmploymentType === 'Freelance/ZZP') {
            salaryOptions = salaryZzpUurtarief;
        } else {
            salaryOptions = salaryBrutoPerMaand;
        }
        
        // Add options
        salaryOptions.forEach(option => {
            const opt = document.createElement('option');
            opt.value = option;
            opt.textContent = option;
            if (option === currentValue) {
                opt.selected = true;
            }
            salaryRangeSelect.appendChild(opt);
        });
        
        // Reinitialize KT Select if it exists
        if (window.KTSelect && typeof window.KTSelect.init === 'function') {
            const selectElement = salaryRangeSelect.closest('.kt-select-wrapper');
            if (selectElement) {
                try {
                    window.KTSelect.init(selectElement);
                } catch (e) {
                    console.warn('KTSelect init error:', e);
                }
            }
        }
    }
    
    // Listen for employment type changes
    if (employmentTypeSelect) {
        employmentTypeSelect.addEventListener('change', updateSalaryOptions);
        
        // Initialize on page load
        updateSalaryOptions();
    }
    
    // Location dropdown with custom input option
    const locationSelect = document.getElementById('location-select');
    const locationCustomInput = document.getElementById('location-custom-input');
    const companySelect = document.querySelector('select[name="company_id"]');
    
    // Handle location select change
    if (locationSelect && locationCustomInput) {
        locationSelect.addEventListener('change', function() {
            if (this.value === '__custom__') {
                // Show custom input, hide select
                locationSelect.style.display = 'none';
                locationCustomInput.style.display = 'block';
                locationCustomInput.focus();
            } else {
                // Hide custom input, show select
                locationCustomInput.style.display = 'none';
                locationSelect.style.display = 'block';
            }
        });
        
        // If custom input has value on load, show it
        if (locationCustomInput.value && !locationSelect.value) {
            locationSelect.style.display = 'none';
            locationCustomInput.style.display = 'block';
        }
    }
    
    // Update location and contact person options when company changes (for super admin)
    @if(auth()->user()->hasRole('super-admin'))
    // Function to update contact users based on selected company
    function updateContactUsers(companyId) {
        const contactUserSelect = document.querySelector('select[name="contact_user_id"]');
        if (!contactUserSelect) return;
        
        if (!companyId) {
            // Clear all options except first if no company selected
            while (contactUserSelect.options.length > 1) {
                contactUserSelect.remove(1);
            }
            
            // Reinitialize KT Select
            if (window.KTSelect && typeof window.KTSelect.init === 'function') {
                const selectElement = contactUserSelect.closest('.kt-select-wrapper');
                if (selectElement) {
                    try {
                        window.KTSelect.init(selectElement);
                    } catch (e) {
                        console.warn('KTSelect init error:', e);
                    }
                }
            }
            return;
        }
        
        // Fetch users for selected company
        fetch(`/admin/companies/${companyId}/users/json`)
            .then(response => response.json())
            .then(data => {
                // Clear existing options except first
                while (contactUserSelect.options.length > 1) {
                    contactUserSelect.remove(1);
                }
                
                // Add new user options
                if (data.users && data.users.length > 0) {
                    data.users.forEach(user => {
                        const opt = document.createElement('option');
                        opt.value = user.id;
                        opt.textContent = `${user.first_name} ${user.middle_name || ''} ${user.last_name}`.trim() + ` (${user.email})`;
                        contactUserSelect.appendChild(opt);
                    });
                }
                
                // Reinitialize KT Select
                if (window.KTSelect && typeof window.KTSelect.init === 'function') {
                    const selectElement = contactUserSelect.closest('.kt-select-wrapper');
                    if (selectElement) {
                        try {
                            window.KTSelect.init(selectElement);
                        } catch (e) {
                            console.warn('KTSelect init error:', e);
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error fetching users:', error);
            });
    }
    
    if (companySelect) {
        // Update contact users when company changes
        companySelect.addEventListener('change', function() {
            const companyId = this.value;
            
            // Update contact users
            updateContactUsers(companyId);
            
            if (!companyId) return;
            
            // Fetch locations for selected company
            if (locationSelect) {
                fetch(`/admin/companies/${companyId}/locations/json`)
                    .then(response => response.json())
                    .then(data => {
                        // Clear existing options except first and custom
                        while (locationSelect.options.length > 2) {
                            locationSelect.remove(1);
                        }
                        
                        // Add main address as first option if available
                        if (data.mainAddress) {
                            const mainOpt = document.createElement('option');
                            mainOpt.value = data.mainAddress.name;
                            mainOpt.textContent = 'Hoofdadres' + (data.mainAddress.city ? ' - ' + data.mainAddress.city : '');
                            locationSelect.insertBefore(mainOpt, locationSelect.lastElementChild);
                        }
                        
                        // Add new location options
                        if (data.locations && data.locations.length > 0) {
                            data.locations.forEach(location => {
                                const opt = document.createElement('option');
                                opt.value = location.name;
                                opt.textContent = location.name + (location.city && location.name !== location.city ? ' - ' + location.city : '');
                                locationSelect.insertBefore(opt, locationSelect.lastElementChild);
                            });
                        }
                        
                        // Reinitialize KT Select
                        if (window.KTSelect && typeof window.KTSelect.init === 'function') {
                            const selectElement = locationSelect.closest('.kt-select-wrapper');
                            if (selectElement) {
                                try {
                                    window.KTSelect.init(selectElement);
                                } catch (e) {
                                    console.warn('KTSelect init error:', e);
                                }
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching locations:', error);
                    });
            }
        });
        
        // Initialize contact users on page load if company is already selected
        if (companySelect.value) {
            updateContactUsers(companySelect.value);
        }
    }
    @endif
    
    // Update form submission to use correct location value
    const vacancyForm = document.querySelector('form[action*="vacancies"]');
    if (vacancyForm) {
        vacancyForm.addEventListener('submit', function(e) {
            // If custom input is visible and has value, use that; otherwise use select value
            if (locationCustomInput && locationCustomInput.style.display !== 'none' && locationCustomInput.value) {
                // Create hidden input with location value
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'location';
                hiddenInput.value = locationCustomInput.value;
                vacancyForm.appendChild(hiddenInput);
                // Remove the custom input name to avoid duplicate
                locationCustomInput.removeAttribute('name');
            }
        });
    }
});
</script>
@endpush

@endsection
