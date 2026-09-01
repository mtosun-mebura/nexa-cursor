@extends('admin.layouts.app')

@section('title', 'Nieuwe Gebruiker')

@section('content')

<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">
            Nieuwe Gebruiker
        </h1>
        <a href="{{ $userCreateBackUrl }}" class="kt-btn kt-btn-outline shrink-0">
            <i class="ki-filled ki-arrow-left me-2"></i>
            Terug
        </a>
    </div>

    <form id="admin-user-form" action="{{ route('admin.users.store') }}" method="POST" data-validate="true" novalidate>
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
                        Algemene Informatie
                    </h3>
                </div>
                <div class="kt-card-content p-0 sm:p-0">
                    <div class="kt-card-table pb-3 px-3 sm:px-5">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                        <tr>
                            <td class="min-w-40 text-secondary-foreground font-normal">
                                Voornaam *
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
                                Achternaam *
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
                                Functie
                            </td>
                            <td>
                                <div class="relative">
                                    <input type="text" 
                                           id="function-input"
                                           class="kt-input @error('function') border-destructive @enderror" 
                                           name="function" 
                                           value="{{ old('function') }}"
                                           autocomplete="off"
                                           placeholder="Type om te zoeken..."
                                           @disabled(! $showFunctionField)>
                                    <div id="function-suggestions" class="hidden absolute left-0 top-full z-[9999] bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-xl max-h-60 overflow-y-auto w-full mt-1" style="min-width: 100%;"></div>
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Type om te zoeken of voer een eigen functie in</div>
                                @error('function')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                E-mail *
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
                            $passwordRequired = $selectedRolesForPassword !== [] && ! $usesAppFirstLoginPassword;
                        @endphp
                        <tr id="user-create-password-row">
                            <td class="text-secondary-foreground font-normal align-top">
                                <span id="user-create-password-label">Wachtwoord</span><span id="user-create-password-required-mark" @unless($passwordRequired) hidden @endunless> *</span>
                            </td>
                            <td>
                                <div class="user-create-password-fields">
                                <div class="flex items-center gap-1.5">
                                    <input type="password"
                                           id="user-create-password"
                                           class="kt-input min-w-0 flex-1 @error('password') border-destructive @enderror"
                                           name="password"
                                           @if($passwordRequired) required @endif
                                           autocomplete="new-password">
                                    <span class="relative shrink-0">
                                        <button type="button"
                                                id="user-create-password-generate"
                                                class="user-create-password-generate kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost"
                                                aria-label="Tijdelijk wachtwoord genereren"
                                                aria-describedby="user-create-password-generate-tip">
                                            <i class="ki-filled ki-key text-lg"></i>
                                        </button>
                                        <span id="user-create-password-generate-tip" role="tooltip" class="user-create-password-generate-tip">
                                            Genereer een tijdelijk wachtwoord. Alleen bedoeld voor de eerste keer inloggen.
                                        </span>
                                    </span>
                                </div>
                                <div id="user-create-password-help-temp" class="text-xs text-muted-foreground mt-1" @if($usesAppFirstLoginPassword) hidden @endif>Optioneel bij chauffeur, contractant en contractouder (eerste login via een code in de app). Bij andere rollen: tijdelijk wachtwoord, minimaal 8 tekens, met een hoofdletter, kleine letter en cijfer.</div>
                                <div id="user-create-password-help-app" class="text-xs text-muted-foreground mt-1" @unless($usesAppFirstLoginPassword) hidden @endunless>Niet nodig. Chauffeur, contractant en contractouder loggen de eerste keer in met een eenmalige code in de app en kiezen daarna zelf een wachtwoord.</div>
                                @error('password')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                Telefoon
                            </td>
                            <td>
                                <input type="tel" 
                                       class="kt-input @error('phone') border-destructive @enderror" 
                                       name="phone" 
                                       value="{{ old('phone') }}"
                                       pattern="(\+31|0)[1-9][0-9]{8}"
                                       placeholder="0612345678 of +31612345678"
                                       maxlength="13">
                                <div class="text-xs text-muted-foreground mt-1">Nederlands nummer (bijv. 0612345678 of +31612345678)</div>
                                @error('phone')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Geboortedatum
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
                                           placeholder="Selecteer datum" 
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
                        Account Informatie
                    </h3>
                </div>
                <div class="kt-card-content p-0 sm:p-0">
                    <div class="kt-card-table pb-3 px-3 sm:px-5">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                        <tr>
                            <td class="min-w-40 text-secondary-foreground font-normal align-top pt-4">
                                Rollen *
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
                                    Bedrijf
                                </td>
                                <td>
                                    @if($lockedWizardCompany)
                                        <input type="hidden" name="company_id" value="{{ $lockedWizardCompany->id }}">
                                        <div class="kt-input pointer-events-none bg-muted/40">{{ $lockedWizardCompany->name }}</div>
                                        <div class="text-xs text-muted-foreground mt-1">Tenant uit de wizard; al geselecteerd.</div>
                                    @else
                                    <select class="kt-input @error('company_id') border-destructive @enderror" 
                                            name="company_id">
                                        <option value="">-- Geen bedrijf --</option>
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
                    Annuleren
                </a>
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-check me-2"></i>
                    Gebruiker Opslaan
                </button>
            </div>
        </div>
    </form>
</div>

@endsection

@push('styles')
<style>
    .user-create-password-generate-tip {
        position: absolute;
        right: 0;
        bottom: calc(100% + 0.45rem);
        z-index: 80;
        display: none;
        width: 16.5rem;
        padding: 0.5rem 0.7rem;
        border-radius: 0.5rem;
        background: #18181b;
        color: #fff;
        font-size: 0.75rem;
        font-weight: 400;
        line-height: 1.35;
        text-align: left;
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.28);
        pointer-events: none;
    }
    .user-create-password-generate:hover + .user-create-password-generate-tip,
    .user-create-password-generate:focus-visible + .user-create-password-generate-tip {
        display: block;
    }
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
    #admin-user-form #user-create-password-row .flex.items-center {
        width: 100%;
        max-width: 28rem;
    }
    #admin-user-form #user-create-password {
        width: auto;
        max-width: none;
        flex: 1 1 0%;
        min-width: 0;
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
        const checkedCount = document.querySelectorAll('input[name="roles[]"]:checked').length;
        const passwordRequired = checkedCount > 0 && !appLogin;
        const requiredMark = document.getElementById('user-create-password-required-mark');
        const helpTemp = document.getElementById('user-create-password-help-temp');
        const helpApp = document.getElementById('user-create-password-help-app');
        if (requiredMark) {
            requiredMark.hidden = !passwordRequired;
        }
        if (helpTemp) {
            helpTemp.hidden = appLogin;
        }
        if (helpApp) {
            helpApp.hidden = !appLogin;
        }
        if (passwordInput) {
            if (passwordRequired) {
                passwordInput.setAttribute('required', 'required');
            } else {
                passwordInput.removeAttribute('required');
            }
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
        if (input.type !== 'password') {
            return;
        }
        const wrap = input.parentNode;
        const toggle = wrap && wrap.querySelector ? wrap.querySelector('.js-pw-toggle-btn') : null;
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
                        item.innerHTML = `Nieuwe functie: "<strong>${currentQuery}</strong>" (Enter om op te slaan)`;
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
