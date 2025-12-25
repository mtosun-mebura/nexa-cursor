@extends('admin.layouts.app')

@section('title', 'Nieuw Job Configuratie Type')

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-col gap-5 pb-7.5">
        <div class="flex flex-wrap items-center justify-between gap-5">
            <h1 class="text-xl font-medium leading-none text-mono">
                Nieuw Job Configuratie Type
            </h1>
        </div>
        <div class="flex items-center">
            <a href="{{ route('admin.job-configuration-types.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
    </div>

    <form action="{{ route('admin.job-configuration-types.store') }}" method="POST">
        @csrf

        <div class="grid gap-5 lg:gap-7.5">
            <x-error-card :errors="$errors" />

            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Type Informatie</h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="text-secondary-foreground font-normal">Weergave Naam *</td>
                            <td>
                                <input type="text" 
                                       id="display_name"
                                       class="kt-input @error('display_name') border-destructive @enderror" 
                                       name="display_name" 
                                       value="{{ old('display_name') }}" 
                                       required 
                                       maxlength="100"
                                       placeholder="bijv. Dienstverband Type, Werkuren">
                                <div class="text-xs text-muted-foreground mt-1">De naam zoals deze wordt weergegeven in de interface</div>
                                @error('display_name')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Naam *</td>
                            <td class="min-w-48 w-full">
                                <input type="text" 
                                       id="name"
                                       class="kt-input @error('name') border-destructive @enderror" 
                                       name="name" 
                                       value="{{ old('name') }}" 
                                       required 
                                       maxlength="50"
                                       pattern="[a-z_]+"
                                       placeholder="bijv. employment_type, working_hours">
                                <div class="text-xs text-muted-foreground mt-1">Alleen kleine letters en underscores (bijv. employment_type, contract_duration). Wordt automatisch gegenereerd op basis van Weergave Naam.</div>
                                @error('name')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Beschrijving</td>
                            <td>
                                <textarea class="kt-input pt-1 @error('description') border-destructive @enderror" 
                                          name="description" 
                                          rows="3" 
                                          placeholder="Beschrijving van dit type...">{{ old('description') }}</textarea>
                                <div class="text-xs text-muted-foreground mt-1">Optionele beschrijving van wat dit type wordt gebruikt</div>
                                @error('description')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Volgorde</td>
                            <td>
                                <input type="number" 
                                       class="kt-input @error('sort_order') border-destructive @enderror" 
                                       name="sort_order" 
                                       value="{{ old('sort_order', 0) }}" 
                                       min="0"
                                       step="1">
                                <div class="text-xs text-muted-foreground mt-1">Volgorde voor sortering (lager = eerder)</div>
                                @error('sort_order')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Status</td>
                            <td>
                                <label class="kt-label flex items-center">
                                    <input type="checkbox" 
                                           class="kt-switch kt-switch-sm" 
                                           name="is_active" 
                                           value="1" 
                                           {{ old('is_active', true) ? 'checked' : '' }}>
                                    <span class="ms-2">Actief</span>
                                </label>
                                <div class="text-xs text-muted-foreground mt-1">Alleen actieve types worden getoond in dropdowns</div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5">
                <a href="{{ route('admin.job-configuration-types.index') }}" class="kt-btn kt-btn-outline">
                    <i class="ki-filled ki-cross me-2"></i>
                    Annuleren
                </a>
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-check me-2"></i>
                    Opslaan
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const displayNameInput = document.getElementById('display_name');
    const nameInput = document.getElementById('name');
    
    // Alleen automatisch genereren als naam veld leeg is of als het de oude waarde is
    let initialNameValue = nameInput.value;
    let isUserEdited = false;
    
    // Check if name was manually edited
    nameInput.addEventListener('input', function() {
        isUserEdited = true;
    });
    
    // Generate name from display name
    displayNameInput.addEventListener('input', function() {
        if (!isUserEdited || nameInput.value === initialNameValue) {
            const displayName = this.value;
            // Convert to lowercase, replace spaces and special chars with underscores
            const generatedName = displayName
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9_]+/g, '_')  // Replace non-alphanumeric (except _) with _
                .replace(/_+/g, '_')           // Replace multiple underscores with single
                .replace(/^_+|_+$/g, '');     // Remove leading/trailing underscores
            
            nameInput.value = generatedName;
            initialNameValue = generatedName;
            isUserEdited = false;
        }
    });
});
</script>
@endpush

@endsection

