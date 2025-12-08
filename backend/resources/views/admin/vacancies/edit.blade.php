@extends('admin.layouts.app')

@section('title', 'Vacature Bewerken')

@section('content')





<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono mb-3">
                {{ $title ?? "Pagina" }}
            </h1>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.' . str_replace(['admin.', '.create', '.edit', '.show'], ['', '.index', '.index', '.index'], request()->route()->getName())) }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
    </div>

    <div class="grid gap-5 lg:gap-7.5">
        <div class="w-full">
            <div class="kt-container-fixed">
    <div class="flex flex-col items-stretch grow">
        <form[^>]*class="[^"]*"
                    @if($errors->any())
                        <div class="kt-alert kt-alert-danger">
                            <ul >
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.vacancies.update', $vacancy) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="lg:col-span-8">
                                <div class="w-full">
                        <div class="flex items-center py-3">
                            <label for="title" class="kt-form-label flex items-center gap-1 max-w-56">
                                Titel *
                            </label>
                            <input type="text" class="kt-input @error('title') is-invalid @enderror" 
                                           id="title" name="title" value="{{ old('title', $vacancy->
                            @error('title') is-invalid @enderror
                        </div>
                    </div></div>
                            
                            <div class="lg:col-span-4">
                                <div class="w-full">
                        <div class="flex items-center py-3">
                            <label for="status" class="kt-form-label flex items-center gap-1 max-w-56">
                                Status *
                            </label>
                            <select class="kt-select @error('status') is-invalid @enderror" 
                                            id="status" name="status" required>
                                        <option value="active" {{ old('status', $vacancy->status) == 'active' ? 'selected' : '' }}>Actief</option>
                                        <option value="inactive" {{ old('status', $vacancy->status) == 'inactive' ? 'selected' : '' }}>Inactief</option>
                                        <option value="draft" {{ old('status', $vacancy->status) == 'draft' ? 'selected' : '' }}>Concept</option>
                                    </select>
                            @error('status') is-invalid @enderror
                        </div>
                    </div></div>
                        </div>

                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="lg:col-span-6">
                                <div class="w-full">
                        <div class="flex items-center py-3">
                            <label for="company_id" class="kt-form-label flex items-center gap-1 max-w-56">
                                Bedrijf *
                            </label>
                            <input type="text" class="kt-input" value="{{ $selectedCompany->
                            @error('company_id') is-invalid @enderror
                        </div>
                    </div></div>
                            
                            <div class="lg:col-span-6">
                                <div class="w-full">
                        <div class="flex items-center py-3">
                            <label for="branch_id" class="kt-form-label flex items-center gap-1 max-w-56">
                                Branch
                            </label>
                            <select class="kt-select @error('branch_id') is-invalid @enderror" 
                                            id="branch_id" name="branch_id">
                                        <option value="">Selecteer branch</option>
                                        <option value="other" {{ old('branch_id') == 'other' ? 'selected' : '' }}>Anders</option>
                                        @foreach(\App\Models\Branch::orderBy('name')->get() as $branch)
                                            <option value="{{ $branch->id }}" {{ old('branch_id', $vacancy->branch_id) == $branch->id ? 'selected' : '' }}>
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                            @error('branch_id') is-invalid @enderror
                        </div>
                    </div></div>
                        </div>

                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="lg:col-span-6">
                                <div class="w-full">
                        <div class="flex items-center py-3">
                            <label for="location" class="kt-form-label flex items-center gap-1 max-w-56">
                                Locatie
                            </label>
                            <input type="text" class="kt-input @error('location') is-invalid @enderror" 
                                           id="location" name="location" value="{{ old('location', $vacancy->
                            @error('location') is-invalid @enderror
                        </div>
                    </div></div>
                            
                            <div class="lg:col-span-6">
                                <div class="w-full">
                        <div class="flex items-center py-3">
                            <label for="employment_type" class="kt-form-label flex items-center gap-1 max-w-56">
                                Type Werk
                            </label>
                            <select class="kt-select @error('employment_type') is-invalid @enderror" 
                                            id="employment_type" name="employment_type">
                                        <option value="">Selecteer type</option>
                                        <option value="full-time" {{ old('employment_type', $vacancy->employment_type) == 'full-time' ? 'selected' : '' }}>Volledig</option>
                                        <option value="part-time" {{ old('employment_type', $vacancy->employment_type) == 'part-time' ? 'selected' : '' }}>Deeltijd</option>
                                        <option value="contract" {{ old('employment_type', $vacancy->employment_type) == 'contract' ? 'selected' : '' }}>Contract</option>
                                        <option value="temporary" {{ old('employment_type', $vacancy->employment_type) == 'temporary' ? 'selected' : '' }}>Tijdelijk</option>
                                        <option value="internship" {{ old('employment_type', $vacancy->employment_type) == 'internship' ? 'selected' : '' }}>Stage</option>
                                    </select>
                            @error('employment_type') is-invalid @enderror
                        </div>
                    </div></div>
                        </div>

                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="lg:col-span-6">
                                <div class="w-full">
                        <div class="flex items-center py-3">
                            <label for="salary_min" class="kt-form-label flex items-center gap-1 max-w-56">
                                Minimum Salaris
                            </label>
                            <input type="number" class="kt-input @error('salary_min') is-invalid @enderror" 
                                           id="salary_min" name="salary_min" value="{{ old('salary_min', $vacancy->
                            @error('salary_min') is-invalid @enderror
                        </div>
                    </div></div>
                            
                            <div class="lg:col-span-6">
                                <div class="w-full">
                        <div class="flex items-center py-3">
                            <label for="salary_max" class="kt-form-label flex items-center gap-1 max-w-56">
                                Maximum Salaris
                            </label>
                            <input type="number" class="kt-input @error('salary_max') is-invalid @enderror" 
                                           id="salary_max" name="salary_max" value="{{ old('salary_max', $vacancy->
                            @error('salary_max') is-invalid @enderror
                        </div>
                    </div></div>
                        </div>

                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="lg:col-span-12">
                                <div class="w-full">
                        <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                            <label class="kt-form-label flex items-center gap-1 max-w-56">

                                    <label for="description" class="kt-form-label">Beschrijving *</label>
                                    <textarea class="kt-input @error('description') is-invalid @enderror" 
                                              id="description" name="description" rows="6" required>{{ old('description', $vacancy->description) }}</textarea>
                                    @error('description')
                                        <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                    @enderror
                                
                        </div>
                    </div></div>
                        </div>

                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="lg:col-span-12">
                                <div class="w-full">
                        <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                            <label class="kt-form-label flex items-center gap-1 max-w-56">

                                    <label for="requirements" class="kt-form-label">Vereisten</label>
                                    <textarea class="kt-input @error('requirements') is-invalid @enderror" 
                                              id="requirements" name="requirements" rows="4">{{ old('requirements', $vacancy->requirements) }}</textarea>
                                    @error('requirements')
                                        <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                    @enderror
                                
                        </div>
                    </div></div>
                        </div>

                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="lg:col-span-12">
                                <div class="w-full">
                        <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                            <label class="kt-form-label flex items-center gap-1 max-w-56">

                                    <label for="benefits" class="kt-form-label">Voordelen</label>
                                    <textarea class="kt-input @error('benefits') is-invalid @enderror" 
                                              id="benefits" name="benefits" rows="4">{{ old('benefits', $vacancy->benefits) }}</textarea>
                                    @error('benefits')
                                        <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                    @enderror
                                
                        </div>
                    </div></div>
                        </div>

                        <div class="flex items-center justify-end gap-2.5">
                            <a href="{{ route('admin.vacancies.index') }}" class="kt-btn kt-btn-outline">Annuleren</a>
                            <button type="submit" class="kt-btn kt-btn-primary">
                                <i class="fas fa-save"></i> Wijzigingen Opslaan
                            </button>
                        </div>
                    </form>
                </div>
    </div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const branchSelect = document.getElementById('branch_id');
    const descriptionField = document.getElementById('description');
    const metaDescriptionField = document.getElementById('meta_description');
    const metaKeywordsField = document.getElementById('meta_keywords');
    
    if (branchSelect) {
        branchSelect.addEventListener('change', function() {
            const branchId = this.value;
            
            // Skip if "Anders" or empty is selected
            if (!branchId || branchId === 'other') {
                return;
            }
            
            // Fetch branch data
            fetch(`/admin/branches/${branchId}/data`)
                .then(response => response.json())
                .then(data => {
                    // Suggest description if empty or user wants to update
                    if (descriptionField && (!descriptionField.value.trim() || confirm('Wil je de beschrijving bijwerken op basis van de branch?'))) {
                        if (data.description) {
                            descriptionField.value = `Vacature in de ${data.name} sector.\n\n${data.description}`;
                        } else {
                            descriptionField.value = `Vacature in de ${data.name} sector.`;
                        }
                    }
                    
                    // Suggest meta description if empty
                    if (metaDescriptionField && !metaDescriptionField.value.trim()) {
                        metaDescriptionField.value = `Vacature in de ${data.name} sector. ${data.description || ''}`.substring(0, 160);
                    }
                    
                    // Suggest meta keywords if empty
                    if (metaKeywordsField && !metaKeywordsField.value.trim()) {
                        const keywords = [data.name, 'vacature', 'werk', data.slug].filter(Boolean).join(', ');
                        metaKeywordsField.value = keywords;
                    }
                })
                .catch(error => {
                    console.error('Error fetching branch data:', error);
                });
        });
    }
});
</script>
@endpush

@endsection
