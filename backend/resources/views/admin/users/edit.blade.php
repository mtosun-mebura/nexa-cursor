@extends('admin.layouts.app')

@section('title', 'Gebruiker Bewerken')

@section('content')

<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">
            Gebruiker Bewerken
        </h1>
        <a href="{{ route('admin.users.show', $user) }}" class="kt-btn kt-btn-outline shrink-0">
            <i class="ki-filled ki-arrow-left me-2"></i>
            Terug
        </a>
    </div>

    <form action="{{ route('admin.users.update', $user) }}" method="POST"  data-validate="true" novalidate>
        @csrf
        @method('PUT')

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
                    <div class="kt-card-table kt-scrollable-x-auto pb-3 px-3 sm:px-5">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">
                                Voornaam *
                            </td>
                            <td class="min-w-48 w-full">
                                <input type="text" 
                                       class="kt-input @error('first_name') border-destructive @enderror" 
                                       name="first_name" 
                                       value="{{ old('first_name', $user->first_name) }}" 
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
                                       value="{{ old('last_name', $user->last_name) }}" 
                                       required>
                                @error('last_name')
                                    <div class="text-xs text-destructive mt-1 laravel-inline-error" data-laravel-field="last_name" role="alert">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Functie
                            </td>
                            <td>
                                <div class="relative">
                                    <input type="text" 
                                           id="function-input"
                                           class="kt-input @error('function') border-destructive @enderror" 
                                           name="function" 
                                           value="{{ old('function', $user->function) }}"
                                           autocomplete="off"
                                           placeholder="Type om te zoeken...">
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
                                       value="{{ old('email', $user->email) }}" 
                                       required
                                       autocomplete="email">
                                @error('email')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                Nieuw Wachtwoord
                            </td>
                            <td>
                                <input type="password" 
                                       class="kt-input @error('password') border-destructive @enderror" 
                                       name="password" 
                                       placeholder="Laat leeg om niet te wijzigen">
                                <div class="text-xs text-muted-foreground mt-1">Minimaal 8 tekens (laat leeg om niet te wijzigen)</div>
                                @error('password')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
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
                                       value="{{ old('phone', $user->phone) }}"
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
                                           value="{{ old('date_of_birth', $user->date_of_birth ? $user->date_of_birth->format('d-m-Y') : '') }}"
                                           data-kt-date-picker="true" 
                                           data-kt-date-picker-input-mode="true" 
                                           data-kt-date-picker-position-to-input="left"
                                           data-kt-date-picker-format="dd-MM-yyyy"
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
                    <div class="kt-card-table kt-scrollable-x-auto pb-3 px-3 sm:px-5">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top pt-4">
                                Rollen *
                            </td>
                            <td class="min-w-48 w-full pt-4">
                                @include('admin.users.partials.role-checkboxes', [
                                    'roles' => $roles,
                                    'selectedRoles' => old('roles', $user->webRoleNames()),
                                ])
                            </td>
                        </tr>
                        @if(auth()->user()->hasRole('super-admin'))
                            <tr>
                                <td class="text-secondary-foreground font-normal">
                                    Bedrijf
                                </td>
                                <td>
                                    <select class="kt-input @error('company_id') border-destructive @enderror" 
                                            name="company_id">
                                        <option value="">-- Geen bedrijf --</option>
                                        @foreach($companies as $company)
                                            <option value="{{ $company->id }}" {{ old('company_id', $user->company_id) == $company->id ? 'selected' : '' }}>
                                                {{ $company->name }}
                                            </option>
                                        @endforeach
                                    </select>
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
                <a href="{{ route('admin.users.show', $user) }}" class="kt-btn kt-btn-outline">
                    <i class="ki-filled ki-cross me-2"></i>
                    Annuleren
                </a>
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-check me-2"></i>
                    Wijzigingen Opslaan
                </button>
            </div>
        </div>
    </form>
</div>

@endsection

@push('scripts')
<script src="{{ asset('assets/js/form-validation.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const functionInput = document.getElementById('function-input');
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
