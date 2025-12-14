@extends('admin.layouts.app')

@section('title', 'Vacature Bewerken')

@section('content')

@php
    $status = (string)($vacancy->status ?? '');
@endphp

<style>
    .hero-bg {
        background-image: url('{{ asset('assets/media/images/2600x1200/bg-1.png') }}');
    }
    .dark .hero-bg {
        background-image: url('{{ asset('assets/media/images/2600x1200/bg-1-dark.png') }}');
    }
</style>

<div class="bg-center bg-cover bg-no-repeat hero-bg">
    <!-- Container -->
    <div class="kt-container-fixed">
        <div class="flex flex-col items-center gap-2 lg:gap-3.5 py-4 lg:pt-5 lg:pb-10">
            @if($vacancy->company && $vacancy->company->logo_blob)
                <div class="rounded-lg shrink-0 inline-block" style="background: transparent; padding: 3px;">
                    <img class="rounded-lg w-auto object-contain bg-transparent dark:bg-transparent" style="height: 80px; display: block; padding: 8px;" src="{{ route('admin.companies.logo', $vacancy->company) }}" alt="{{ $vacancy->company->name }}">
                </div>
            @elseif($vacancy->company)
                <div class="rounded-lg border-3 border-primary h-[100px] w-[100px] lg:h-[150px] lg:w-[150px] shrink-0 flex items-center justify-center bg-primary/10 text-primary text-2xl font-semibold">
                    {{ strtoupper(substr($vacancy->company->name, 0, 2)) }}
                </div>
            @else
                <div class="rounded-lg border-3 border-primary h-[100px] w-[100px] lg:h-[150px] lg:w-[150px] shrink-0 flex items-center justify-center bg-primary/10 text-primary text-2xl font-semibold">
                    <i class="ki-filled ki-briefcase text-3xl"></i>
                </div>
            @endif
            <div class="flex items-center gap-1.5">
                <div class="text-xl lg:text-2xl leading-6 font-semibold text-mono">
                    {{ $vacancy->title }}
                </div>
            </div>
            <div class="flex flex-wrap justify-center gap-1 lg:gap-4.5 text-sm">
                @if($vacancy->company)
                    <div class="flex gap-1.25 items-center">
                        <x-heroicon-o-building-office-2 class="w-4 h-4 text-muted-foreground" />
                        <span class="text-secondary-foreground font-medium">{{ $vacancy->company->name }}</span>
                    </div>
                @endif
                @if($vacancy->branch)
                    <div class="flex gap-1.25 items-center">
                        <i class="ki-filled ki-tag text-muted-foreground text-sm"></i>
                        <span class="text-secondary-foreground font-medium">{{ $vacancy->branch->name }}</span>
                    </div>
                @endif
                <div class="flex gap-1.25 items-center">
                    @if($status === 'Open')
                        <span class="kt-badge kt-badge-sm kt-badge-success">Open</span>
                    @elseif($status === 'Gesloten')
                        <span class="kt-badge kt-badge-sm kt-badge-danger">Gesloten</span>
                    @elseif($status === 'In behandeling')
                        <span class="kt-badge kt-badge-sm kt-badge-warning">In behandeling</span>
                    @else
                        <span class="kt-badge kt-badge-sm kt-badge-secondary">{{ $status ?: '-' }}</span>
                    @endif
                </div>
                @if($vacancy->location)
                    <div class="flex gap-1.25 items-center">
                        <i class="ki-filled ki-geolocation text-muted-foreground text-sm"></i>
                        <span class="text-secondary-foreground font-medium">{{ $vacancy->location }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="kt-container-fixed vacancy-edit">
    <div class="flex flex-col gap-5 pb-7.5 mt-5">
        <div class="flex flex-wrap items-center justify-between gap-5">
            <h1 class="text-xl font-medium leading-none text-mono">
                Vacature Bewerken
            </h1>
        </div>
        <div class="flex items-center">
            <a href="{{ route('admin.vacancies.show', $vacancy) }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
    </div>

    <form action="{{ route('admin.vacancies.update', $vacancy) }}" method="POST" class="flex flex-col gap-5 lg:gap-7.5" data-validate="true">
        @csrf
        @method('PUT')

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
                                    <option value="Open" {{ old('status', $vacancy->status) == 'Open' ? 'selected' : '' }}>Open</option>
                                    <option value="In behandeling" {{ old('status', $vacancy->status) == 'In behandeling' ? 'selected' : '' }}>In behandeling</option>
                                    <option value="Gesloten" {{ old('status', $vacancy->status) == 'Gesloten' ? 'selected' : '' }}>Gesloten</option>
                                </select>
                                @error('status')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Bedrijf *</td>
                            <td>
                                <select name="company_id" class="kt-select @error('company_id') border-destructive @enderror" data-kt-select="true" required>
                                    @foreach(($companies ?? []) as $company)
                                        <option value="{{ $company->id }}" {{ (string)old('company_id', $vacancy->company_id) === (string)$company->id ? 'selected' : '' }}>
                                            {{ $company->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('company_id')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        @else
                            <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
                        @endif
                        @php
                            $selectedBranchId = old('branch_id', $vacancy->branch_id);
                            $selectedBranchName = '';
                            if ($selectedBranchId) {
                                $selectedBranchName = optional(($branches ?? collect())->firstWhere('id', (int) $selectedBranchId))->name ?? '';
                            }
                            // Find function ID by matching title to function display_name
                            $selectedFunctionId = null;
                            if ($selectedBranchId && $vacancy->title) {
                                $branch = ($branches ?? collect())->firstWhere('id', (int) $selectedBranchId);
                                if ($branch && method_exists($branch, 'functions')) {
                                    $function = $branch->functions()->get()->first(function($f) use ($vacancy) {
                                        $displayName = str_replace('_', ' ', $f->name);
                                        return mb_strtolower($displayName) === mb_strtolower($vacancy->title);
                                    });
                                    if ($function) {
                                        $selectedFunctionId = $function->id;
                                    }
                                }
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
                                    <input type="hidden" id="branch-id" name="branch_id" value="{{ $selectedBranchId }}">
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
                                           value="{{ old('title', $vacancy->title) }}"
                                           autocomplete="off"
                                           placeholder="Type om te zoeken... (of voer zelf in)"
                                           required>
                                    <input type="hidden" id="branch-function-id" value="{{ $selectedFunctionId ?? '' }}">
                                    <div id="function-suggestions" class="hidden absolute left-0 top-full z-[9999] bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-xl max-h-60 overflow-y-auto w-full mt-1" style="min-width: 100%;"></div>
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Zoek naar een functie (branch wordt automatisch ingevuld) of kies eerst een branch om functies te filteren. Vrij invullen kan altijd.</div>
                                @error('title')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Locatie</td>
                            <td>
                                <input type="text" name="location" class="kt-input @error('location') border-destructive @enderror" value="{{ old('location', $vacancy->location) }}">
                                @error('location')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Type dienstverband</td>
                            <td>
                                <select name="employment_type" class="kt-select @error('employment_type') border-destructive @enderror" data-kt-select="true">
                                    <option value="">-</option>
                                    @foreach(['Fulltime','Parttime','Contract','Tijdelijke','Stage','Traineeship','Freelance','ZZP'] as $opt)
                                        <option value="{{ $opt }}" {{ old('employment_type', $vacancy->employment_type) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                    @endforeach
                                </select>
                                @error('employment_type')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Salarisrange</td>
                            <td>
                                <input type="text" name="salary_range" class="kt-input @error('salary_range') border-destructive @enderror" value="{{ old('salary_range', $vacancy->salary_range) }}" placeholder="bijv. €3000 - €4000">
                                @error('salary_range')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Referentie</td>
                            <td>
                                <input type="text" name="reference_number" class="kt-input @error('reference_number') border-destructive @enderror" value="{{ old('reference_number', $vacancy->reference_number) }}">
                                @error('reference_number')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Werkuren</td>
                            <td>
                                <input type="text" name="working_hours" class="kt-input @error('working_hours') border-destructive @enderror" value="{{ old('working_hours', $vacancy->working_hours) }}" placeholder="bijv. 32-40">
                                @error('working_hours')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Taal</td>
                            <td>
                                <input type="text" name="language" class="kt-input @error('language') border-destructive @enderror" value="{{ old('language', $vacancy->language) }}" placeholder="Nederlands">
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
                                           value="{{ old('publication_date', $vacancy->publication_date ? $vacancy->publication_date->format('Y-m-d') : '') }}"
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
                                           value="{{ old('closing_date', $vacancy->closing_date ? $vacancy->closing_date->format('Y-m-d') : '') }}"
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
                                    <input type="checkbox" class="kt-switch kt-switch-sm" name="travel_expenses" value="1" {{ old('travel_expenses', (bool)$vacancy->travel_expenses) ? 'checked' : '' }}>
                                    <span class="ms-2">Vergoed</span>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Remote</td>
                            <td>
                                <label class="kt-label flex items-center">
                                    <input type="checkbox" class="kt-switch kt-switch-sm" name="remote_work" value="1" {{ old('remote_work', (bool)$vacancy->remote_work) ? 'checked' : '' }}>
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
                                <textarea name="description" rows="4" class="kt-input pt-1 @error('description') border-destructive @enderror" required>{{ old('description', $vacancy->description) }}</textarea>
                                @error('description')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Vereisten</td>
                            <td>
                                <input type="hidden" name="required_skills" id="required-skills-input" value="{{ old('required_skills', $vacancy->required_skills ? json_encode($vacancy->required_skills) : '') }}">
                                <div class="flex flex-col gap-3">
                                    <div class="flex flex-wrap items-center gap-2" id="required-skills-chips"></div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" class="kt-btn kt-btn-sm kt-btn-success" id="add-skill-btn">
                                            <i class="ki-filled ki-plus me-1"></i>
                                            Toevoegen
                                        </button>
                                        <button type="button" class="kt-btn kt-btn-sm kt-btn-success hidden" id="load-default-skills-btn">
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
                                <textarea name="requirements" rows="4" class="kt-input pt-1 @error('requirements') border-destructive @enderror">{{ old('requirements', $vacancy->requirements) }}</textarea>
                                @error('requirements')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Aanbod</td>
                            <td>
                                <textarea name="offer" rows="4" class="kt-input pt-1 @error('offer') border-destructive @enderror">{{ old('offer', $vacancy->offer) }}</textarea>
                                @error('offer')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Sollicitatie instructies</td>
                            <td>
                                <textarea name="application_instructions" rows="4" class="kt-input pt-1 @error('application_instructions') border-destructive @enderror">{{ old('application_instructions', $vacancy->application_instructions) }}</textarea>
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
                                <input type="text" name="meta_title" class="kt-input @error('meta_title') border-destructive @enderror" value="{{ old('meta_title', $vacancy->meta_title) }}">
                                @error('meta_title')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Meta beschrijving</td>
                            <td>
                                <textarea name="meta_description" rows="4" class="kt-input pt-1 @error('meta_description') border-destructive @enderror">{{ old('meta_description', $vacancy->meta_description) }}</textarea>
                                @error('meta_description')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Meta keywords</td>
                            <td>
                                <input type="text" name="meta_keywords" class="kt-input @error('meta_keywords') border-destructive @enderror" value="{{ old('meta_keywords', $vacancy->meta_keywords) }}" placeholder="keyword1, keyword2">
                                @error('meta_keywords')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5">
                <a href="{{ route('admin.vacancies.show', $vacancy) }}" class="kt-btn kt-btn-outline">Annuleren</a>
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
    /* Align textarea labels to top (like Users forms) */
    .vacancy-edit .kt-table-border-dashed.align-middle td.align-top {
        vertical-align: top !important;
        padding-top: 14px;
    }
    /* Groene knoppen voor skills */
    #add-skill-btn.kt-btn-success,
    #load-default-skills-btn.kt-btn-success {
        background-color: var(--color-green-600);
        border-color: var(--color-green-600);
        color: white;
    }
    #add-skill-btn.kt-btn-success:hover,
    #load-default-skills-btn.kt-btn-success:hover {
        background-color: var(--color-green-700);
        border-color: var(--color-green-700);
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
    const csrf = document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content');
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
    let skills = [];
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
            const json = await res.json();
            return (json?.skills || []).map(x => x.display_name || x.name).map(normalizeSkill).filter(Boolean);
        } catch (_) {
            return [];
        }
    }

    async function applyFunctionSkillsIfUntouched(branchId, functionId) {
        if (skillsTouched) return;
        const defaults = await loadDefaultSkillsForFunction(branchId, functionId);
        const uniq = [];
        defaults.forEach(s => {
            if (!uniq.some(x => x.toLowerCase() === s.toLowerCase())) uniq.push(s);
        });
        skills = uniq;
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
        if (!branchId || !functionId || !loadDefaultSkillsBtn) {
            if (loadDefaultSkillsBtn) loadDefaultSkillsBtn.classList.add('hidden');
            return;
        }
        const defaults = await loadDefaultSkillsForFunction(branchId, functionId);
        if (defaults.length > 0) {
            loadDefaultSkillsBtn.classList.remove('hidden');
        } else {
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
            .replace(/\"/g, '&quot;')
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
                const regex = new RegExp(`(${escapeHtml(currentQuery)})`, 'gi');
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
                if (f.branch_id && !getBranchId()) {
                    const branch = branches.find(b => b.id === f.branch_id);
                    if (branch) {
                        branchInput.value = branch.name;
                        branchIdInput.value = String(branch.id);
                        lastBranchId = null;
                        // Load functions for this branch
                        await loadBranchFunctions(String(branch.id));
                    }
                }
                
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
                const filtered = filterFunctions(functionInput.value);
                renderSuggestions(filtered, functionInput.value);
                // Check if we should show load button for current function
                const branchId = getBranchId();
                if (branchId && selectedFunctionId) {
                    await checkAndShowLoadButton(branchId, selectedFunctionId);
                } else {
                    if (loadDefaultSkillsBtn) loadDefaultSkillsBtn.classList.add('hidden');
                }
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
        if (!branchId) return;
        const value = (displayName || '').trim();
        if (!value) return;

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
                            await loadBranchFunctions(String(branch.id));
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
                        await loadBranchFunctions(String(branch.id));
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
                    
                    // reset skills for newly created function
                    skillsTouched = false;
                    skills = [];
                    serializeSkills();
                    renderChips();
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

    // Setup initial skills from hidden JSON (vacancy.required_skills or old input)
    if (requiredSkillsInput && requiredSkillsInput.value) {
        try {
            const decoded = JSON.parse(requiredSkillsInput.value);
            if (Array.isArray(decoded)) {
                skills = decoded.map(normalizeSkill).filter(Boolean);
                // Mark touched so we don't overwrite with defaults
                skillsTouched = true;
                serializeSkills();
                renderChips();
            }
        } catch (_) {}
    } else {
        serializeSkills();
        renderChips();
    }

    // Modal for adding skill (same UI as create)
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
});
</script>
@endpush

@endsection
