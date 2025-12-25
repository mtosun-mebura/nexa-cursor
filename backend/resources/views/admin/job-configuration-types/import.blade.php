@extends('admin.layouts.app')

@section('title', 'Job Configuratie Types Importeren')

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-col gap-5 pb-7.5">
        <div class="flex flex-wrap items-center justify-between gap-5">
            <h1 class="text-xl font-medium leading-none text-mono">
                Job Configuratie Types Importeren
            </h1>
        </div>
        <div class="flex items-center">
            <a href="{{ route('admin.job-configuration-types.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
    </div>

    <form action="{{ route('admin.job-configuration-types.import') }}" method="POST" id="import-form">
        @csrf

        <div class="grid gap-5 lg:gap-7.5">
            <x-error-card :errors="$errors" />

            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">JSON Import</h3>
                </div>
                <div class="kt-card-content">
                    <div class="mb-4">
                        <label class="kt-label mb-2">
                            JSON Data *
                        </label>
                        <textarea 
                            id="json-input"
                            name="json_data" 
                            class="kt-input font-mono text-sm @error('json_data') border-destructive @enderror" 
                            rows="20"
                            placeholder='Plak hier je JSON data...'
                            required
                            style="min-height: 20rem; resize: vertical;">{{ old('json_data') }}</textarea>
                        <div class="text-xs text-muted-foreground mt-2">
                            Plak de JSON data met dienstverbanden, werkuren, salarisindicaties en statussen.
                        </div>
                        @error('json_data')
                            <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="kt-label flex items-center">
                            <input type="checkbox" 
                                   class="kt-switch kt-switch-sm" 
                                   name="skip_existing"
                                   value="1"
                                   {{ old('skip_existing', true) ? 'checked' : '' }}>
                            <span class="ms-2">Bestaande types en configuraties overslaan</span>
                        </label>
                        <div class="text-xs text-muted-foreground mt-1">
                            Als aangevinkt, worden bestaande types en configuraties niet overschreven.
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="kt-label flex items-center">
                            <input type="checkbox" 
                                   class="kt-switch kt-switch-sm" 
                                   name="create_types"
                                   value="1"
                                   {{ old('create_types', true) ? 'checked' : '' }}>
                            <span class="ms-2">Types aanmaken als ze niet bestaan</span>
                        </label>
                        <div class="text-xs text-muted-foreground mt-1">
                            Als aangevinkt, worden nieuwe types aangemaakt voor keys die nog niet bestaan.
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5">
                <a href="{{ route('admin.job-configuration-types.index') }}" class="kt-btn kt-btn-outline">
                    <i class="ki-filled ki-cross me-2"></i>
                    Annuleren
                </a>
                <button type="submit" class="kt-btn kt-btn-primary" id="import-btn">
                    <i class="ki-filled ki-upload me-2"></i>
                    Importeren
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const jsonInput = document.getElementById('json-input');
    const importForm = document.getElementById('import-form');
    const importBtn = document.getElementById('import-btn');
    
    // Validate JSON on input
    jsonInput.addEventListener('input', function() {
        const value = this.value.trim();
        if (value) {
            try {
                JSON.parse(value);
                this.classList.remove('border-destructive');
                this.classList.add('border-green-500');
            } catch (e) {
                this.classList.remove('border-green-500');
                this.classList.add('border-destructive');
            }
        } else {
            this.classList.remove('border-destructive', 'border-green-500');
        }
    });
    
    // Validate before submit
    importForm.addEventListener('submit', function(e) {
        const jsonValue = jsonInput.value.trim();
        if (!jsonValue) {
            e.preventDefault();
            alert('Voer JSON data in.');
            return false;
        }
        
        try {
            JSON.parse(jsonValue);
        } catch (e) {
            e.preventDefault();
            alert('Ongeldige JSON data: ' + e.message);
            return false;
        }
        
        // Disable button to prevent double submit
        importBtn.disabled = true;
        importBtn.innerHTML = '<i class="ki-filled ki-loading me-2"></i> Importeren...';
    });
});
</script>
@endpush

@endsection

