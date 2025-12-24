@extends('admin.layouts.app')

@section('title', 'Permissies Bulk Aanmaken')

@push('scripts')
<script src="{{ asset('assets/js/form-validation.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Voorkom dat de sidebar drawer sluit wanneer je binnen de content area klikt
    // Alleen blokkeer drawer backdrop clicks, niet normale clicks op knoppen/links
    const contentArea = document.getElementById('content');
    const sidebar = document.getElementById('sidebar');
    
    if (contentArea && sidebar) {
        // Blokkeer alleen backdrop clicks die de drawer sluiten
        // Gebruik een MutationObserver om de backdrop te vinden wanneer deze wordt toegevoegd
        const observer = new MutationObserver(function(mutations) {
            const backdrop = document.querySelector('.kt-drawer-backdrop');
            if (backdrop) {
                // Verwijder oude listener als die bestaat
                backdrop.removeEventListener('click', preventBackdropClose);
                // Voeg nieuwe listener toe
                backdrop.addEventListener('click', preventBackdropClose, true);
            }
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        function preventBackdropClose(e) {
            // Als de klik binnen de content area is, voorkom drawer sluiten
            if (contentArea.contains(e.target)) {
                e.preventDefault();
                e.stopImmediatePropagation();
                return false;
            }
        }
        
        // Initial check voor backdrop
        setTimeout(function() {
            const backdrop = document.querySelector('.kt-drawer-backdrop');
            if (backdrop) {
                backdrop.addEventListener('click', preventBackdropClose, true);
            }
        }, 100);
    }
});
</script>
@endpush

@push('styles')
<style>
    /* Verberg alle field-feedback elementen binnen de Acties card - gebruik alleen de oranje balk */
    [data-required-checkbox-group="actions[]"] .field-feedback:not(#actions-validation-wrapper .field-feedback),
    [data-required-checkbox-group="actions[]"] .kt-card .field-feedback:not(#actions-validation-wrapper .field-feedback) {
        display: none !important;
    }
    
    /* Zorg dat de oranje balk zichtbaar is wanneer deze niet hidden is */
    #actions-validation-wrapper:not(.hidden) {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    /* Zorg dat de tekst in de oranje balk zichtbaar is */
    #actions-validation-wrapper .field-feedback {
        color: white !important;
        font-weight: 500 !important;
    }
    
    .dark #actions-validation-wrapper .field-feedback {
        color: white !important;
    }
    
    /* Zorg dat de modules validatie balk zichtbaar is wanneer deze niet hidden is */
    #modules-validation-wrapper:not(.hidden) {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    /* Zorg dat de tekst in de modules validatie balk zichtbaar is */
    #modules-validation-wrapper .field-feedback {
        color: white !important;
        font-weight: 500 !important;
    }
    
    .dark #modules-validation-wrapper .field-feedback {
        color: white !important;
    }
    
    /* Checkbox styling voor acties en modules - zelfde als rollen create */
    [data-required-checkbox-group] .kt-checkbox,
    .kt-card-content .kt-checkbox,
    .kt-card-table .kt-checkbox {
        width: 20px !important;
        height: 20px !important;
        min-width: 20px !important;
        min-height: 20px !important;
        border-width: 1px !important;
        border-color: #555555;
        color: #555555;
        padding-right: 0 !important;
    }

    [data-required-checkbox-group] .kt-checkbox:hover,
    .kt-card-content .kt-checkbox:hover,
    .kt-card-table .kt-checkbox:hover {
        border-color: #555555;
        background-color: rgba(85, 85, 85, 0.1);
    }

    [data-required-checkbox-group] .kt-checkbox:checked,
    .kt-card-content .kt-checkbox:checked,
    .kt-card-table .kt-checkbox:checked {
        border-color: #10b981 !important;
        background-color: transparent !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 20 20' fill='none'%3E%3Cpath fill-rule='evenodd' d='M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z' clip-rule='evenodd' fill='%2310b981'/%3E%3C/svg%3E") !important;
        background-position: center !important;
        background-repeat: no-repeat !important;
        background-size: 20px 20px !important;
        border-width: 1px !important;
        color: #10b981 !important;
    }

    [data-required-checkbox-group] .kt-checkbox:focus-visible,
    .kt-card-content .kt-checkbox:focus-visible,
    .kt-card-table .kt-checkbox:focus-visible {
        --tw-ring-color: #555555;
        --tw-ring-offset-width: 2px;
    }
    
    /* Ensure validation icons are positioned at the end of input fields with gap */
    .kt-card-table .relative {
        position: relative;
        width: 100%;
    }
    
    /* Add padding-right to input fields to make room for validation icon */
    .kt-card-table .relative .kt-input {
        padding-right: 2.75rem !important;
        width: 100% !important;
    }
    
    /* Position validation icon at the end with gap - inside the input field */
    .kt-card-table .relative .validation-icon-wrapper {
        position: absolute !important;
        right: 0.75rem !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        pointer-events: none !important;
        z-index: 10 !important;
        width: 1.25rem !important;
        height: 1.25rem !important;
    }
    
</style>
@endpush

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-col gap-5 pb-7.5">
        <div class="flex flex-wrap items-center justify-between gap-5">
            <h1 class="text-xl font-medium leading-none text-mono">
                Permissies Bulk Aanmaken
            </h1>
        </div>
        <div class="flex items-center">
            <a href="{{ route('admin.permissions.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
    </div>

    <form action="{{ route('admin.permissions.bulk-store') }}" method="POST" data-validate="true">
        @csrf

        <div class="grid gap-5 lg:gap-7.5">
            <x-error-card :errors="$errors" />

            <!-- Info Card -->
            <div class="kt-card min-w-full" style="background-color: rgba(59, 130, 246, 0.08); border: 1px solid rgba(59, 130, 246, 0.25);">
                <div class="kt-card-content p-5 lg:px-7 lg:py-6">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            <x-heroicon-s-information-circle class="w-6 h-6 flex-shrink-0" style="color: rgb(59, 130, 246);" />
                        </div>
                        <p class="text-base text-foreground dark:text-white flex-1 min-w-0">
                            Maak snel meerdere permissies aan voor een module.
                            Het systeem zal automatisch permissies aanmaken in het formaat: [actie]-[module].
                        </p>
                    </div>
                </div>
            </div>

            <!-- Basis Informatie -->
            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Basis Informatie</h3>
                </div>
                
                <!-- Validatiemelding voor Modules - Cyan balk -->
                <div class="field-feedback-modules-wrapper mb-4 hidden" id="modules-validation-wrapper">
                    <div class="flex items-center gap-2 px-4 py-3 rounded-lg mx-5 mt-4" style="background-color: rgba(60, 219, 251, 0.1); border: 1px solid rgba(60, 219, 251, 0.3);">
                        <i class="ki-filled ki-information-5" style="font-size: 20px; color: rgb(234, 88, 12);"></i>
                        <div class="field-feedback text-sm font-medium" data-field="modules[]" style="display: block; color: white;">Selecteer minimaal één module.</div>
                    </div>
                </div>
                
                <div class="kt-card-table pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top pt-3">Modules *</td>
                            <td class="min-w-48 w-full">
                                @php
                                    $oldModules = old('modules', []);
                                    // Add "Algemeen" option
                                    $allModules = ['algemeen' => 'Algemeen'] + $moduleNames;
                                    // Split modules into two columns
                                    $modulesArray = array_chunk($allModules, ceil(count($allModules) / 2), true);
                                @endphp
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                                    @foreach($modulesArray as $column)
                                        <div class="flex flex-col gap-2">
                                            @foreach($column as $moduleKey => $moduleName)
                                                <label class="kt-label flex items-center cursor-pointer">
                                                    <input type="checkbox"
                                                           class="kt-checkbox"
                                                           name="modules[]"
                                                           value="{{ $moduleKey }}"
                                                           id="module_{{ $moduleKey }}"
                                                           data-checkbox-group="modules[]"
                                                           {{ in_array($moduleKey, $oldModules) ? 'checked' : '' }}>
                                                    <span class="ms-2">{{ $moduleName }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                                
                                <div class="field-feedback text-xs mt-1 hidden" data-field="modules[]"></div>
                                <div class="text-xs text-muted-foreground mt-2">Selecteer één of meerdere modules. "Algemeen" geldt voor alle modules.</div>
                                @error('modules')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                                @error('modules.*')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Acties -->
            <div class="kt-card min-w-full" data-required-checkbox-group="actions[]">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Acties *</h3>
                </div>
                <div class="kt-card-content">
                    @error('actions')
                        <div class="kt-alert kt-alert-danger mb-4">
                            <i class="ki-filled ki-cross-circle me-2"></i>
                            {{ $message }}
                        </div>
                    @enderror

                    <!-- Validatiemelding voor Acties - Oranje balk boven de sub-cards -->
                    <div class="field-feedback-actions-wrapper mb-4 hidden" id="actions-validation-wrapper">
                        <div class="flex items-center gap-2 px-4 py-3 rounded-lg" style="background-color: rgba(60, 219, 251, 0.1); border: 1px solid rgba(60, 219, 251, 0.3);">
                            <i class="ki-filled ki-information-5" style="font-size: 20px; color: rgb(234, 88, 12);"></i>
                            <div class="field-feedback text-sm font-medium" data-field="actions[]" style="display: block; color: white;"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        @php
                            $oldActions = old('actions', []);
                            // Split actions into CRUD and specific actions
                            $crudActions = ['view', 'create', 'edit', 'delete'];
                            $specificActions = array_diff(array_keys($actions ?? []), $crudActions);
                        @endphp
                        
                        <!-- Basis CRUD Acties -->
                        <div class="kt-card">
                            <div class="kt-card-header">
                                <h4 class="kt-card-title text-sm">Basis CRUD Acties</h4>
                            </div>
                            <div class="kt-card-content">
                                <div class="flex flex-col gap-3">
                                    @foreach($crudActions as $action)
                                        @if(isset($actions[$action]))
                                            <label class="kt-label flex items-center cursor-pointer">
                                                <input type="checkbox"
                                                       class="kt-checkbox"
                                                       name="actions[]"
                                                       value="{{ $action }}"
                                                       id="action_{{ $action }}"
                                                       data-checkbox-group="actions[]"
                                                       {{ in_array($action, $oldActions) ? 'checked' : '' }}>
                                                <span class="ms-2">
                                                    {!! str_replace($action, '<strong>' . ucfirst($action) . '</strong>', $actions[$action]) !!}
                                                </span>
                                            </label>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Specifieke Acties -->
                        <div class="kt-card">
                            <div class="kt-card-header">
                                <h4 class="kt-card-title text-sm">Specifieke Acties</h4>
                            </div>
                            <div class="kt-card-content">
                                <div class="flex flex-col gap-3">
                                    @foreach($specificActions as $action)
                                        @if(isset($actions[$action]))
                                            <label class="kt-label flex items-center cursor-pointer">
                                                <input type="checkbox"
                                                       class="kt-checkbox"
                                                       name="actions[]"
                                                       value="{{ $action }}"
                                                       id="action_{{ $action }}"
                                                       data-checkbox-group="actions[]"
                                                       {{ in_array($action, $oldActions) ? 'checked' : '' }}>
                                                <span class="ms-2">
                                                    {!! str_replace($action, '<strong>' . ucfirst($action) . '</strong>', $actions[$action]) !!}
                                                </span>
                                            </label>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Voorbeeld Preview -->
            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Voorbeeld Permissies die worden aangemaakt</h3>
                </div>
                <div class="kt-card-content">
                    <div class="kt-alert kt-alert-secondary">
                        <div id="permission-preview" class="text-sm">
                            <em>Selecteer modules en acties om een voorbeeld te zien...</em>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5">
                <a href="{{ route('admin.permissions.index') }}" class="kt-btn kt-btn-outline">
                    Annuleren
                </a>
                <button type="submit" id="submit-permissions-btn" class="kt-btn kt-btn-primary" disabled>
                    <i class="ki-filled ki-check me-2"></i>
                    Permissies Bulk Aanmaken
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const moduleCheckboxes = document.querySelectorAll('input[name="modules[]"]');
    const actionCheckboxes = document.querySelectorAll('input[name="actions[]"]');
    const preview = document.getElementById('permission-preview');
    
    // Module display names - dynamically loaded from server
    const moduleNames = @json($moduleNames);
    moduleNames['algemeen'] = 'Algemeen';

    function updatePreview() {
        const selectedModules = Array.from(moduleCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);
        const selectedActions = Array.from(actionCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);
        
        // If "algemeen" is selected, show all modules
        let modulesToShow = selectedModules;
        if (selectedModules.includes('algemeen')) {
            modulesToShow = Object.keys(moduleNames).filter(m => m !== 'algemeen');
        }

        if (modulesToShow.length > 0 && selectedActions.length > 0) {
            const permissions = [];
            modulesToShow.forEach(module => {
                selectedActions.forEach(action => {
                    permissions.push(`${action}-${module}`);
                });
            });
            preview.innerHTML = permissions.join('<br>');
        } else if (modulesToShow.length > 0 && selectedActions.length === 0) {
            preview.innerHTML = '<em>Selecteer minimaal één actie...</em>';
        } else if (modulesToShow.length === 0 && selectedActions.length > 0) {
            preview.innerHTML = '<em>Selecteer minimaal één module...</em>';
        } else {
            preview.innerHTML = '<em>Selecteer modules en acties om een voorbeeld te zien...</em>';
        }
    }

    moduleCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updatePreview();
            checkModulesValidation();
            updateSubmitButton();
        });
    });

    // Functie om validatie van modules te checken en cyan balk te tonen/verbergen
    function checkModulesValidation() {
        const checkedCount = Array.from(moduleCheckboxes).filter(cb => cb.checked).length;
        const validationWrapper = document.getElementById('modules-validation-wrapper');
        
        if (validationWrapper) {
            const feedbackElement = validationWrapper.querySelector('.field-feedback[data-field="modules[]"]');
            
            if (checkedCount === 0) {
                // Geen modules geselecteerd - toon cyan balk
                if (feedbackElement) {
                    feedbackElement.textContent = 'Selecteer minimaal één module.';
                }
                validationWrapper.classList.remove('hidden');
                validationWrapper.style.display = 'block';
                validationWrapper.style.visibility = 'visible';
                validationWrapper.style.opacity = '1';
            } else {
                // Modules geselecteerd - verberg cyan balk
                validationWrapper.classList.add('hidden');
                validationWrapper.style.display = 'none';
            }
        }
    }

    // Functie om validatie van acties te checken en cyan balk te tonen/verbergen
    function checkActionsValidation() {
        const checkedCount = Array.from(actionCheckboxes).filter(cb => cb.checked).length;
        const validationWrapper = document.getElementById('actions-validation-wrapper');
        
        if (validationWrapper) {
            const feedbackElement = validationWrapper.querySelector('.field-feedback[data-field="actions[]"]');
            
            if (checkedCount === 0) {
                // Geen acties geselecteerd - toon cyan balk
                if (feedbackElement) {
                    feedbackElement.textContent = 'Selecteer minimaal één recht.';
                }
                validationWrapper.classList.remove('hidden');
                validationWrapper.style.display = 'block';
                validationWrapper.style.visibility = 'visible';
                validationWrapper.style.opacity = '1';
            } else {
                // Acties geselecteerd - verberg cyan balk
                validationWrapper.classList.add('hidden');
                validationWrapper.style.display = 'none';
            }
        }
    }

    // Functie om submit button te enable/disable op basis van geselecteerde modules en acties
    function updateSubmitButton() {
        const submitBtn = document.getElementById('submit-permissions-btn');
        if (!submitBtn) return;

        const selectedModules = Array.from(moduleCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);
        const selectedActions = Array.from(actionCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);

        // Check of er minimaal één module en één actie is geselecteerd
        // "algemeen" telt als module geselecteerd
        const hasModules = selectedModules.length > 0;
        const hasActions = selectedActions.length > 0;

        if (hasModules && hasActions) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }

    actionCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updatePreview();
            checkActionsValidation();
            updateSubmitButton();
        });
    });

    // Initial preview update en validatie check
    updatePreview();
    checkModulesValidation();
    checkActionsValidation();
    updateSubmitButton();
});
</script>
@endpush

@endsection
