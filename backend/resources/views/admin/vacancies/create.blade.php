@extends('admin.layouts.app')

@section('title', 'Nieuwe Vacature')

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

    <div class="kt-container-fixed">
    <div class="flex flex-col items-stretch grow">
        <form[^>]*class="[^"]*"
            @if($errors->any())
                <div class="alert alert-danger">
                    <strong>Er zijn fouten opgetreden:</strong>
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.vacancies.store') }}" method="POST">
                @csrf
                
                <!-- Status Sectie -->
                <div class="form-section">
                    <h6 class="section-title">
                        <i class="fas fa-info-circle"></i> Huidige Status
                    </h6>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="status" class="form-label">Status <span class="required">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" 
                                    id="status" name="status" required>
                                <option value="Open" {{ old('status') == 'Open' ? 'selected' : '' }}>Open</option>
                                <option value="In behandeling" {{ old('status') == 'In behandeling' ? 'selected' : '' }}>In behandeling</option>
                                <option value="Gesloten" {{ old('status') == 'Gesloten' ? 'selected' : '' }}>Gesloten</option>
                            </select>
                            @error('status')
                                <div class="form-help text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Basis Informatie -->
                <div class="form-section">
                    <h6 class="section-title">
                        <i class="fas fa-briefcase"></i> Basis Informatie
                    </h6>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="title" class="form-label">Titel <span class="required">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                   id="title" name="title" value="{{ old('title') }}" required>
                            @error('title')
                                <div class="form-help text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="reference_number" class="form-label">Referentienummer</label>
                            <input type="text" class="form-control @error('reference_number') is-invalid @enderror" 
                                   id="reference_number" name="reference_number" value="{{ old('reference_number') }}">
                            @error('reference_number')
                                <div class="form-help text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="company_id" class="form-label">Bedrijf <span class="required">*</span></label>
                            @if(auth()->user()->hasRole('super-admin') && session('selected_tenant'))
                                @php $selectedCompany = \App\Models\Company::find(session('selected_tenant')); @endphp
                                <input type="text" class="form-control" value="{{ $selectedCompany->name }}" readonly>
                                <input type="hidden" name="company_id" value="{{ session('selected_tenant') }}">
                                <div class="form-help">Tenant geselecteerd: {{ $selectedCompany->name }}</div>
                            @else
                                <select class="form-select @error('company_id') is-invalid @enderror" 
                                        id="company_id" name="company_id" required>
                                    <option value="">Selecteer bedrijf</option>
                                    @foreach(\App\Models\Company::all() as $company)
                                        <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                            {{ $company->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('company_id')
                                    <div class="form-help text-danger">{{ $message }}</div>
                                @enderror
                            @endif
                        </div>
                        
                        <div class="form-group">
                            <label for="branch_id" class="form-label">Branch <span class="text-muted">(optioneel)</span></label>
                            <select class="form-select @error('branch_id') is-invalid @enderror" 
                                    id="branch_id" name="branch_id">
                                <option value="">Selecteer branch</option>
                                <option value="other" {{ old('branch_id') == 'other' ? 'selected' : '' }}>Anders</option>
                                @foreach(\App\Models\Branch::orderBy('name')->get() as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('branch_id')
                                <div class="form-help text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="location" class="form-label">Locatie</label>
                            <input type="text" class="form-control @error('location') is-invalid @enderror" 
                                   id="location" name="location" value="{{ old('location') }}">
                            @error('location')
                                <div class="form-help text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="employment_type" class="form-label">Type Werk</label>
                            <select class="form-select @error('employment_type') is-invalid @enderror" 
                                    id="employment_type" name="employment_type">
                                <option value="">Selecteer type</option>
                                <option value="Fulltime" {{ old('employment_type') == 'Fulltime' ? 'selected' : '' }}>Fulltime</option>
                                <option value="Parttime" {{ old('employment_type') == 'Parttime' ? 'selected' : '' }}>Parttime</option>
                                <option value="Contract" {{ old('employment_type') == 'Contract' ? 'selected' : '' }}>Contract</option>
                                <option value="Tijdelijke" {{ old('employment_type') == 'Tijdelijke' ? 'selected' : '' }}>Tijdelijke</option>
                                <option value="Stage" {{ old('employment_type') == 'Stage' ? 'selected' : '' }}>Stage</option>
                                <option value="Traineeship" {{ old('employment_type') == 'Traineeship' ? 'selected' : '' }}>Traineeship</option>
                                <option value="Freelance" {{ old('employment_type') == 'Freelance' ? 'selected' : '' }}>Freelance</option>
                                <option value="ZZP" {{ old('employment_type') == 'ZZP' ? 'selected' : '' }}>ZZP</option>
                            </select>
                            @error('employment_type')
                                <div class="form-help text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="language" class="form-label">Taal</label>
                            <select class="form-select @error('language') is-invalid @enderror" 
                                    id="language" name="language">
                                <option value="Nederlands" {{ old('language') == 'Nederlands' ? 'selected' : '' }}>Nederlands</option>
                                <option value="Engels" {{ old('language') == 'Engels' ? 'selected' : '' }}>Engels</option>
                                <option value="Duits" {{ old('language') == 'Duits' ? 'selected' : '' }}>Duits</option>
                                <option value="Frans" {{ old('language') == 'Frans' ? 'selected' : '' }}>Frans</option>
                            </select>
                            @error('language')
                                <div class="form-help text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Salaris & Details -->
                <div class="form-section">
                    <h6 class="section-title">
                        <i class="fas fa-euro-sign"></i> Salaris & Details
                    </h6>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="salary_range" class="form-label">Salaris</label>
                            <input type="text" class="form-control @error('salary_range') is-invalid @enderror" 
                                   id="salary_range" name="salary_range" value="{{ old('salary_range') }}" 
                                   placeholder="bijv. €3.000 - €5.000 per maand">
                            @error('salary_range')
                                <div class="form-help text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="working_hours" class="form-label">Werkuren</label>
                            <input type="text" class="form-control @error('working_hours') is-invalid @enderror" 
                                   id="working_hours" name="working_hours" value="{{ old('working_hours') }}" 
                                   placeholder="bijv. 40 uur per week">
                            @error('working_hours')
                                <div class="form-help text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_date" class="form-label">Startdatum</label>
                            <div class="date-input-wrapper">
                                <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                                       id="start_date" name="start_date" value="{{ old('start_date') }}">
                            </div>
                            @error('start_date')
                                <div class="form-help text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="publication_date" class="form-label">Publicatiedatum</label>
                            <div class="date-input-wrapper">
                                <input type="date" class="form-control @error('publication_date') is-invalid @enderror"
                                       id="publication_date" name="publication_date" value="{{ old('publication_date') }}">
                            </div>
                            @error('publication_date')
                                <div class="form-help text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="closing_date" class="form-label">Sluitingsdatum</label>
                            <div class="date-input-wrapper">
                                <input type="date" class="form-control @error('closing_date') is-invalid @enderror"
                                       id="closing_date" name="closing_date" value="{{ old('closing_date') }}">
                            </div>
                            @error('closing_date')
                                <div class="form-help text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input @error('travel_expenses') is-invalid @enderror" 
                                       id="travel_expenses" name="travel_expenses" value="1" {{ old('travel_expenses') ? 'checked' : '' }}>
                                <label for="travel_expenses" class="form-check-label">Reiskosten vergoed</label>
                            </div>
                            @error('travel_expenses')
                                <div class="form-help text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input @error('remote_work') is-invalid @enderror" 
                                       id="remote_work" name="remote_work" value="1" {{ old('remote_work') ? 'checked' : '' }}>
                                <label for="remote_work" class="form-check-label">Remote werk mogelijk</label>
                            </div>
                            @error('remote_work')
                                <div class="form-help text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Content Sectie -->
                <div class="form-section">
                    <h6 class="section-title">
                        <i class="fas fa-file-alt"></i> Content
                    </h6>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">Beschrijving <span class="required">*</span></label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="6" required>{{ old('description') }}</textarea>
                        @error('description')
                            <div class="form-help text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="requirements" class="form-label">Vereisten</label>
                        <textarea class="form-control @error('requirements') is-invalid @enderror" 
                                  id="requirements" name="requirements" rows="4">{{ old('requirements') }}</textarea>
                        @error('requirements')
                            <div class="form-help text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="offer" class="form-label">Aanbod</label>
                        <textarea class="form-control @error('offer') is-invalid @enderror" 
                                  id="offer" name="offer" rows="4">{{ old('offer') }}</textarea>
                        @error('offer')
                            <div class="form-help text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="application_instructions" class="form-label">Sollicitatie-instructies</label>
                        <textarea class="form-control @error('application_instructions') is-invalid @enderror" 
                                  id="application_instructions" name="application_instructions" rows="4">{{ old('application_instructions') }}</textarea>
                        @error('application_instructions')
                            <div class="form-help text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- SEO Sectie -->
                <div class="form-section">
                    <h6 class="section-title">
                        <i class="fas fa-search"></i> SEO Instellingen
                    </h6>
                    
                    <div class="form-group">
                        <label for="meta_title" class="form-label">Meta Titel</label>
                        <input type="text" class="form-control @error('meta_title') is-invalid @enderror" 
                               id="meta_title" name="meta_title" value="{{ old('meta_title') }}" 
                               placeholder="SEO-vriendelijke titel voor zoekmachines">
                        @error('meta_title')
                            <div class="form-help text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="meta_description" class="form-label">Meta Beschrijving</label>
                        <textarea class="form-control @error('meta_description') is-invalid @enderror" 
                                  id="meta_description" name="meta_description" rows="4" 
                                  placeholder="Korte beschrijving voor zoekmachines (max 160 karakters)">{{ old('meta_description') }}</textarea>
                        @error('meta_description')
                            <div class="form-help text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="meta_keywords" class="form-label">Meta Keywords</label>
                        <input type="text" class="form-control @error('meta_keywords') is-invalid @enderror" 
                               id="meta_keywords" name="meta_keywords" value="{{ old('meta_keywords') }}" 
                               placeholder="Zoekwoorden gescheiden door komma's">
                        @error('meta_keywords')
                            <div class="form-help text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-actions">
                    <a href="{{ route('admin.vacancies.index') }}" class="kt-btn kt-btn-outline">
                        <i class="fas fa-times"></i> Annuleren
                    </a>
                    <button type="submit" class="kt-btn kt-btn-primary">
                        <i class="fas fa-save"></i> Vacature Aanmaken
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
                    // Auto-fill description if empty
                    if (descriptionField && !descriptionField.value.trim()) {
                        if (data.description) {
                            descriptionField.value = `Vacature in de ${data.name} sector.\n\n${data.description}`;
                        } else {
                            descriptionField.value = `Vacature in de ${data.name} sector.`;
                        }
                    }
                    
                    // Auto-fill meta description if empty
                    if (metaDescriptionField && !metaDescriptionField.value.trim()) {
                        metaDescriptionField.value = `Vacature in de ${data.name} sector. ${data.description || ''}`.substring(0, 160);
                    }
                    
                    // Auto-fill meta keywords if empty
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
