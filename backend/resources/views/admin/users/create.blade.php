@extends('admin.layouts.app')

@section('title', __('Nieuwe Gebruiker'))

@section('content')

<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">
            {{ __('Nieuwe Gebruiker') }}
        </h1>
        <a href="{{ $userCreateBackUrl }}" class="kt-btn kt-btn-outline shrink-0">
            <i class="ki-filled ki-arrow-left me-2"></i>
            {{ __('Terug') }}
        </a>
    </div>

    <form id="admin-user-form" action="{{ route('admin.users.store') }}" method="POST" data-validate="true" novalidate autocomplete="off">
        @csrf
        <input type="hidden" name="wizard_back_url" value="{{ $userCreateBackUrl }}">
        @if(!empty($wizardContextCompanyId))
            <input type="hidden" name="from_wizard" value="1">
            <input type="hidden" name="wizard_company" value="{{ $wizardContextCompanyId }}">
            <input type="hidden" name="wizard_step" value="{{ $wizardContextStep ?? 5 }}">
        @endif

        <div class="grid gap-5 lg:gap-7.5">
            <x-error-card :errors="$errors" />

            <!-- Algemene Informatie -->
            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header">
                    <h3 class="kt-card-title mb-0">
                        {{ __('Algemene Informatie') }}
                    </h3>
                </div>
                <div class="kt-card-content p-0 sm:p-0">
                    <div class="kt-card-table pb-3 px-3 sm:px-5">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                        <tr>
                            <td class="min-w-40 text-secondary-foreground font-normal">
                                {{ __('Voornaam *') }}
                            </td>
                            <td>
                                <input type="text" 
                                       class="kt-input @error('first_name') border-destructive @enderror" 
                                       name="first_name" 
                                       value="{{ old('first_name') }}" 
                                       required>
                                @error('first_name')
                                    <div class="text-xs text-destructive mt-1 laravel-inline-error" data-laravel-field="first_name" role="alert">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                {{ __('Achternaam *') }}
                            </td>
                            <td>
                                <input type="text" 
                                       class="kt-input @error('last_name') border-destructive @enderror" 
                                       name="last_name" 
                                       value="{{ old('last_name') }}" 
                                       required>
                                @error('last_name')
                                    <div class="text-xs text-destructive mt-1 laravel-inline-error" data-laravel-field="last_name" role="alert">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr id="user-function-row" @class(['hidden' => ! ($showFunctionField ?? false)])>
                            <td class="text-secondary-foreground font-normal">
                                {{ __('Functie') }}
                            </td>
                            <td>
                                <div class="relative">
                                    <input type="text" 
                                           id="function-input"
                                           class="kt-input @error('function') border-destructive @enderror" 
                                           name="function" 
                                           value="{{ old('function') }}"
                                           autocomplete="off"
                                           placeholder="{{ __('Type om te zoeken...') }}"
                                           @disabled(! $showFunctionField)>
                                    <div id="function-suggestions" class="hidden absolute left-0 top-full z-[9999] bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-xl max-h-60 overflow-y-auto w-full mt-1" style="min-width: 100%;"></div>
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">{{ __('Type om te zoeken of voer een eigen functie in') }}</div>
                                @error('function')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                {{ __('E-mail *') }}
                            </td>
                            <td>
                                <input type="email" 
                                       class="kt-input @error('email') border-destructive @enderror" 
                                       name="email" 
                                       value="{{ old('email') }}" 
                                       required
                                       autocomplete="email">
                                @error('email')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        @php
                            $appFirstLoginRoleNames = $appFirstLoginRoleNames ?? [];
                            $selectedRolesForPassword = old('roles', $defaultRoleForForm ? [$defaultRoleForForm] : []);
                            $selectedRolesForPassword = is_array($selectedRolesForPassword) ? $selectedRolesForPassword : [];
                            $usesAppFirstLoginPassword = count(array_intersect(
                                array_map('strtolower', $selectedRolesForPassword),
                                array_map('strtolower', $appFirstLoginRoleNames)
                            )) > 0;
                        @endphp
                        <tr id="user-create-password-row">
                            <td class="text-secondary-foreground font-normal align-top">
                                <span id="user-create-password-label">{{ __('Wachtwoord') }}</span>
                            </td>
                            <td>
                                <div class="user-create-password-fields">
                                <div class="user-create-password-actions">
                                    <div class="kt-input min-w-0 flex-1 @error('password') border-destructive @enderror" data-kt-toggle-password="true">
                                        <input type="password"
                                               id="user-create-password"
                                               name="password"
                                               autocomplete="new-password"
                                               data-lpignore="true"
                                               data-1p-ignore="true">
                                        <button class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon bg-transparent! -me-1.5"
                                                data-kt-toggle-password-trigger="true"
                                                type="button"
                                                aria-label="{{ __('Wachtwoord tonen of verbergen') }}">
                                            <span class="kt-toggle-password-active:hidden">
                                                <i class="ki-filled ki-eye text-muted-foreground"></i>
                                            </span>
                                            <span class="hidden kt-toggle-password-active:block">
                                                <i class="ki-filled ki-eye-slash text-muted-foreground"></i>
                                            </span>
                                        </button>
                                    </div>
                                    <button type="button"
                                            id="user-create-password-generate"
                                            class="user-create-password-generate kt-btn kt-btn-sm kt-btn-outline shrink-0"
                                            title="{{ __('Genereer wachtwoord') }}">
                                        <i class="ki-filled ki-key me-1.5"></i>
                                        {{ __('Genereer wachtwoord') }}
                                    </button>
                                </div>
                                <div id="user-create-password-help-temp" class="text-xs text-muted-foreground mt-1" @if($usesAppFirstLoginPassword) hidden @endif>{{ __('Niet verplicht. Nieuwe gebruikers (ook admin) loggen de eerste keer in met een eenmalige code: op het inlogscherm «Eerste keer inloggen», e-mail invoeren (moet in het systeem staan) en een code aanvragen. Daarna kiezen ze zelf een wachtwoord.') }}</div>
                                <div id="user-create-password-help-app" class="text-xs text-muted-foreground mt-1" @unless($usesAppFirstLoginPassword) hidden @endunless>{{ __('Niet nodig. Chauffeur, contractant en contractouder loggen de eerste keer in met een eenmalige code in de app en kiezen daarna zelf een wachtwoord.') }}</div>
                                @error('password')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                {{ __('Telefoon') }}
                            </td>
                            <td>
                                <input type="tel" 
                                       class="kt-input @error('phone') border-destructive @enderror" 
                                       name="phone" 
                                       value="{{ old('phone') }}"
                                       pattern="(\+31|0)[1-9][0-9]{8}"
                                       placeholder="{{ __('0612345678 of +31612345678') }}"
                                       maxlength="13">
                                <div class="text-xs text-muted-foreground mt-1">{{ __('Nederlands nummer (bijv. 0612345678 of +31612345678)') }}</div>
                                @error('phone')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                {{ __('Geboortedatum') }}
                            </td>
                            <td>
                                <!--begin::Input with Calendar-->
                                <div class="kt-input w-full max-w-64 @error('date_of_birth') border-destructive @enderror">
                                    <i class="ki-outline ki-calendar"></i>
                                    <input class="grow" 
                                           name="date_of_birth" 
                                           id="date_of_birth"
                                           value="{{ old('date_of_birth') }}"
                                           data-kt-date-picker="true" 
                                           data-kt-date-picker-input-mode="true" 
                                           data-kt-date-picker-position-to-input="left"
                                           data-kt-date-picker-date-format="DD-MM-YYYY"
                                           placeholder="{{ __('Selecteer datum') }}" 
                                           readonly 
                                           type="text"/>
                                </div>
                                @error('date_of_birth')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                                <!--end::Input with Calendar-->
                            </td>
                        </tr>
                    </table>
                    </div>
                </div>
            </div>

            <!-- Account Informatie -->
            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header">
                    <h3 class="kt-card-title mb-0">
                        {{ __('Account Informatie') }}
                    </h3>
                </div>
                <div class="kt-card-content p-0 sm:p-0">
                    <div class="kt-card-table pb-3 px-3 sm:px-5">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                        <tr>
                            <td class="min-w-40 text-secondary-foreground font-normal align-top pt-4">
                                {{ __('Rollen *') }}
                            </td>
                            <td class="pt-4">
                                @include('admin.users.partials.role-checkboxes', [
                                    'roles' => $roles,
                                    'selectedRoles' => old('roles', $defaultRoleForForm ? [$defaultRoleForForm] : []),
                                ])
                            </td>
                        </tr>
                        @if(auth()->user()->hasRole('super-admin'))
                            @php
                                $preselectedCompanyId = $preselectedCompanyId ?? old('company_id', $wizardContextCompanyId ?? request('company_id') ?? session('selected_tenant'));
                                $lockedWizardCompany = ! empty($wizardContextCompanyId)
                                    ? $companies->firstWhere('id', (int) $wizardContextCompanyId)
                                    : null;
                                $skillmatchingCompanyIds = $skillmatchingCompanyIds ?? [];
                            @endphp
                            <tr>
                                <td class="text-secondary-foreground font-normal">
                                    {{ __('Bedrijf') }}
                                </td>
                                <td>
                                    @if($lockedWizardCompany)
                                        <input type="hidden" name="company_id" value="{{ $lockedWizardCompany->id }}">
                                        <div class="kt-input pointer-events-none bg-muted/40">{{ $lockedWizardCompany->name }}</div>
                                        <div class="text-xs text-muted-foreground mt-1">{{ __('Tenant uit de wizard; al geselecteerd.') }}</div>
                                    @else
                                    <select class="kt-input @error('company_id') border-destructive @enderror" 
                                            name="company_id">
                                        <option value="">{{ __('-- Geen bedrijf --') }}</option>
                                        @foreach($companies as $company)
                                            <option value="{{ $company->id }}" {{ (string) $preselectedCompanyId === (string) $company->id ? 'selected' : '' }} data-skillmatching="{{ in_array((int) $company->id, array_map('intval', $skillmatchingCompanyIds), true) ? '1' : '0' }}">
                                                {{ $company->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @endif
                                    @error('company_id')
                                        <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                    @enderror
                                </td>
                            </tr>
                        @else
                            <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
                        @endif
                    </table>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="admin-form-actions flex flex-wrap items-center justify-end gap-2.5 w-full min-w-0">
                <a href="{{ route('admin.users.index') }}" class="kt-btn kt-btn-outline">
                    <i class="ki-filled ki-cross me-2"></i>
                    {{ __('Annuleren') }}
                </a>
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-check me-2"></i>
                    {{ __('Gebruiker Opslaan') }}
                </button>
            </div>
        </div>
    </form>
</div>

@endsection

@push('styles')
<style>
    #admin-user-form .kt-card-table {
        overflow-x: visible;
    }
    #admin-user-form .wizard-onboarding-form-table input.kt-input:not(#user-create-password),
    #admin-user-form .wizard-onboarding-form-table input[type="text"]:not([data-kt-date-picker]),
    #admin-user-form .wizard-onboarding-form-table input[type="email"],
    #admin-user-form .wizard-onboarding-form-table input[type="tel"],
    #admin-user-form .wizard-onboarding-form-table select.kt-input,
    #admin-user-form #user-function-row .relative,
    #admin-user-form .wizard-onboarding-form-table .kt-select,
    #admin-user-form .wizard-onboarding-form-table [data-kt-select],
    #admin-user-form .wizard-onboarding-form-table [data-kt-select-display],
    #admin-user-form .wizard-onboarding-form-table td > .relative {
        width: 100%;
        max-width: 28rem;
    }
    #admin-user-form #user-create-password-row .user-create-password-fields,
    #admin-user-form #user-create-password-row .user-create-password-actions {
        width: 100%;
        max-width: 36rem;
    }
    #admin-user-form #user-create-password-row .user-create-password-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
    }
    #admin-user-form #user-create-password-row .user-create-password-actions .kt-input {
        flex: 1 1 12rem;
        min-width: 0;
        max-width: none;
        width: auto;
    }
    #admin-user-form #user-create-password {
        width: 100%;
        max-width: none;
        min-width: 0;
    }
    #admin-user-form .user-create-password-generate {
        flex: 0 0 auto;
        white-space: nowrap;
        writing-mode: horizontal-tb;
        text-orientation: mixed;
    }
    #admin-user-form [data-required-checkbox-group="roles"] {
        max-width: 36rem;
        row-gap: 0.5rem;
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('assets/js/form-validation.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const skillmatchingCompanyIds = @json($skillmatchingCompanyIds ?? []);
    const functionRow = document.getElementById('user-function-row');
    const functionInput = document.getElementById('function-input');
    const companySelect = document.querySelector('select[name="company_id"]');
    const companyHidden = document.querySelector('input[type="hidden"][name="company_id"]');

    function selectedCompanyId() {
        if (companySelect) {
            return companySelect.value;
        }
        return companyHidden ? companyHidden.value : '';
    }

    function syncFunctionRow() {
        if (!functionRow || !functionInput) {
            return;
        }
        const show = skillmatchingCompanyIds.map(String).includes(String(selectedCompanyId()));
        functionRow.classList.toggle('hidden', !show);
        functionInput.disabled = !show;
    }

    if (companySelect) {
        companySelect.addEventListener('change', syncFunctionRow);
    }
    syncFunctionRow();

    const passwordInput = document.getElementById('user-create-password');
    const generateBtn = document.getElementById('user-create-password-generate');
    const appFirstLoginRoles = @json($appFirstLoginRoleNames ?? []);

    function usesAppFirstLogin() {
        const checked = Array.from(document.querySelectorAll('input[name="roles[]"]:checked')).map(function (el) {
            return String(el.value || '').toLowerCase();
        });
        return appFirstLoginRoles.some(function (name) {
            return checked.indexOf(String(name).toLowerCase()) !== -1;
        });
    }

    function syncAppFirstLoginPassword() {
        const appLogin = usesAppFirstLogin();
        const helpTemp = document.getElementById('user-create-password-help-temp');
        const helpApp = document.getElementById('user-create-password-help-app');
        if (helpTemp) {
            helpTemp.hidden = appLogin;
        }
        if (helpApp) {
            helpApp.hidden = !appLogin;
        }
        if (passwordInput) {
            passwordInput.removeAttribute('required');
            if (appLogin) {
                passwordInput.value = '';
            }
        }
        if (generateBtn) {
            generateBtn.hidden = appLogin;
        }
    }

    const userCreateForm = passwordInput ? passwordInput.closest('form') : null;
    if (userCreateForm) {
        userCreateForm.addEventListener('change', function (ev) {
            if (ev.target && ev.target.name === 'roles[]') {
                syncAppFirstLoginPassword();
            }
        });
    }
    syncAppFirstLoginPassword();

    function randomFrom(chars, count) {
        const buf = new Uint32Array(count);
        crypto.getRandomValues(buf);
        let out = '';
        for (let i = 0; i < count; i++) {
            out += chars[buf[i] % chars.length];
        }
        return out;
    }

    function generateTemporaryPassword() {
        const upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        const lower = 'abcdefghijkmnopqrstuvwxyz';
        const digits = '23456789';
        const all = upper + lower + digits;
        let password;
        do {
            password = randomFrom(all, 12);
        } while (!/[a-z]/.test(password) || !/[A-Z]/.test(password) || !/[0-9]/.test(password));
        return password;
    }

    function revealPassword(input) {
        if (!input || input.type !== 'password') {
            return;
        }
        const wrap = input.closest('[data-kt-toggle-password]');
        const toggle = wrap && wrap.querySelector ? wrap.querySelector('[data-kt-toggle-password-trigger]') : null;
        if (toggle) {
            toggle.click();
            return;
        }
        input.type = 'text';
    }

    if (generateBtn && passwordInput) {
        generateBtn.addEventListener('click', function () {
            passwordInput.value = generateTemporaryPassword();
            revealPassword(passwordInput);
            passwordInput.dispatchEvent(new Event('input', { bubbles: true }));
            passwordInput.dispatchEvent(new Event('change', { bubbles: true }));
            passwordInput.focus();
            passwordInput.select();
        });
    }

    const suggestionsDiv = document.getElementById('function-suggestions');
    let debounceTimer;
    let selectedIndex = -1;

    if (!functionInput || !suggestionsDiv) return;

    functionInput.addEventListener('input', function(e) {
        const query = e.target.value.trim();
        
        clearTimeout(debounceTimer);
        
        // Show suggestions immediately while typing (with shorter debounce for better UX)
        // Debounce is shorter to make it feel more responsive
        debounceTimer = setTimeout(() => {
            loadSuggestions(query);
        }, 100);
        
        // If user is typing, show suggestions immediately (don't wait for debounce)
        if (query.length > 0) {
            loadSuggestions(query);
        } else {
            // If input is cleared, hide suggestions
            suggestionsDiv.classList.add('hidden');
        }
    });
    
    // Function to load and show suggestions
    function loadSuggestions(query = '') {
        const url = query.length > 0 
            ? `{{ route('admin.api.job-titles') }}?q=${encodeURIComponent(query)}`
            : `{{ route('admin.api.job-titles') }}`;
        
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Job titles fetched:', data);
                const currentQuery = functionInput.value.trim().toLowerCase();
                
                if (data.length > 0) {
                    suggestionsDiv.innerHTML = '';
                    data.forEach((title, index) => {
                        const item = document.createElement('div');
                        item.className = 'px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer';
                        
                        // Highlight matching part of the title
                        if (currentQuery.length > 0 && title.toLowerCase().includes(currentQuery)) {
                            const regex = new RegExp(`(${currentQuery})`, 'gi');
                            const highlightedTitle = title.replace(regex, '<strong>$1</strong>');
                            item.innerHTML = highlightedTitle;
                        } else {
                            item.textContent = title;
                        }
                        
                        item.dataset.index = index;
                        item.addEventListener('click', function() {
                            functionInput.value = title;
                            suggestionsDiv.classList.add('hidden');
                            saveJobTitle(title);
                        });
                        suggestionsDiv.appendChild(item);
                    });
                    suggestionsDiv.classList.remove('hidden');
                    selectedIndex = -1;
                } else {
                    // If no matches but user is typing, show option to create new
                    if (currentQuery.length > 0) {
                        suggestionsDiv.innerHTML = '';
                        const item = document.createElement('div');
                        item.className = 'px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer text-gray-500 italic';
                        item.innerHTML = @json(__('Nieuwe functie: "<strong>:query</strong>" (Enter om op te slaan)')).replace(':query', function () { return currentQuery; });
                        item.addEventListener('click', function() {
                            functionInput.value = currentQuery;
                            suggestionsDiv.classList.add('hidden');
                            saveJobTitle(currentQuery);
                        });
                        suggestionsDiv.appendChild(item);
                        suggestionsDiv.classList.remove('hidden');
                    } else {
                        suggestionsDiv.classList.add('hidden');
                    }
                }
            })
            .catch(error => {
                console.error('Error fetching job titles:', error);
            });
    }
    
    // Show all suggestions when input is focused or clicked
    functionInput.addEventListener('focus', function(e) {
        const query = e.target.value.trim();
        if (query.length === 0) {
            loadSuggestions();
        } else {
            loadSuggestions(query);
        }
    });
    
    functionInput.addEventListener('click', function(e) {
        const query = e.target.value.trim();
        if (query.length === 0) {
            loadSuggestions();
        } else {
            loadSuggestions(query);
        }
    });

    functionInput.addEventListener('keydown', function(e) {
        const items = suggestionsDiv.querySelectorAll('div');
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
            updateSelection(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            selectedIndex = Math.max(selectedIndex - 1, -1);
            updateSelection(items);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (selectedIndex >= 0 && items[selectedIndex]) {
                items[selectedIndex].click();
            } else if (items.length > 0) {
                // If no selection but suggestions exist, select first one
                items[0].click();
            } else {
                // If no suggestions, save current input as new job title
                const currentValue = functionInput.value.trim();
                if (currentValue.length > 0) {
                    saveJobTitle(currentValue);
                }
            }
        } else if (e.key === 'Escape') {
            suggestionsDiv.classList.add('hidden');
        }
    });

    function updateSelection(items) {
        items.forEach((item, index) => {
            if (index === selectedIndex) {
                item.classList.add('bg-gray-100', 'dark:bg-gray-700');
            } else {
                item.classList.remove('bg-gray-100', 'dark:bg-gray-700');
            }
        });
    }

    function saveJobTitle(title) {
        // Save the job title to the database if it doesn't exist
        fetch(`{{ route('admin.api.job-titles') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ name: title })
        }).catch(error => console.error('Error saving job title:', error));
    }

    // Hide suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!functionInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
            suggestionsDiv.classList.add('hidden');
        }
    });
});
</script>
@endpush
