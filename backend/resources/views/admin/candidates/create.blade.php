@extends('admin.layouts.app')

@section('title', 'Nieuwe Kandidaat')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-user-graduate me-2"></i> Nieuwe Kandidaat
            </h1>
            <p class="text-muted">Voeg een nieuwe kandidaat toe aan het systeem</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.candidates.index') }}" class="material-btn material-btn-secondary">
                <i class="fas fa-arrow-left me-2"></i> Terug naar Overzicht
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Kandidaat Informatie</h5>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.candidates.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <!-- Persoonlijke Informatie -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="section-title">Persoonlijke Informatie</h6>
                                <hr class="section-divider">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">Voornaam *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="{{ old('first_name') }}" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Achternaam *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="{{ old('last_name') }}" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">E-mailadres *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="{{ old('email') }}" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Telefoonnummer</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="{{ old('phone') }}">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="date_of_birth" class="form-label">Geboortedatum</label>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                                       value="{{ old('date_of_birth') }}">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="country" class="form-label">Land</label>
                                <input type="text" class="form-control" id="country" name="country" 
                                       value="{{ old('country', 'Nederland') }}">
                            </div>
                        </div>

                        <!-- Adres Informatie -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="section-title">Adres Informatie</h6>
                                <hr class="section-divider">
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="address" class="form-label">Adres</label>
                                <textarea class="form-control" id="address" name="address" rows="2">{{ old('address') }}</textarea>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">Plaats</label>
                                <input type="text" class="form-control" id="city" name="city" 
                                       value="{{ old('city') }}">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="postal_code" class="form-label">Postcode</label>
                                <input type="text" class="form-control" id="postal_code" name="postal_code" 
                                       value="{{ old('postal_code') }}">
                            </div>
                        </div>

                        <!-- Professionele Informatie -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="section-title">Professionele Informatie</h6>
                                <hr class="section-divider">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="current_position" class="form-label">Huidige Functie</label>
                                <input type="text" class="form-control" id="current_position" name="current_position" 
                                       value="{{ old('current_position') }}">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="desired_position" class="form-label">Gewenste Functie</label>
                                <input type="text" class="form-control" id="desired_position" name="desired_position" 
                                       value="{{ old('desired_position') }}">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="experience_years" class="form-label">Werkervaring (jaren)</label>
                                <input type="number" class="form-control" id="experience_years" name="experience_years" 
                                       value="{{ old('experience_years', 0) }}" min="0" max="50">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="education_level" class="form-label">Opleidingsniveau</label>
                                <select class="form-select" id="education_level" name="education_level">
                                    <option value="">Selecteer niveau</option>
                                    <option value="high_school" {{ old('education_level') == 'high_school' ? 'selected' : '' }}>Middelbare School</option>
                                    <option value="vocational" {{ old('education_level') == 'vocational' ? 'selected' : '' }}>MBO</option>
                                    <option value="bachelor" {{ old('education_level') == 'bachelor' ? 'selected' : '' }}>HBO/Bachelor</option>
                                    <option value="master" {{ old('education_level') == 'master' ? 'selected' : '' }}>WO/Master</option>
                                    <option value="phd" {{ old('education_level') == 'phd' ? 'selected' : '' }}>PhD/Doctoraat</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="salary_expectation" class="form-label">Salarisverwachting (€)</label>
                                <input type="number" class="form-control" id="salary_expectation" name="salary_expectation" 
                                       value="{{ old('salary_expectation') }}" min="0" step="0.01">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="source" class="form-label">Bron *</label>
                                <select class="form-select" id="source" name="source" required>
                                    <option value="">Selecteer bron</option>
                                    <option value="website" {{ old('source') == 'website' ? 'selected' : '' }}>Website</option>
                                    <option value="linkedin" {{ old('source') == 'linkedin' ? 'selected' : '' }}>LinkedIn</option>
                                    <option value="referral" {{ old('source') == 'referral' ? 'selected' : '' }}>Doorverwijzing</option>
                                    <option value="jobboard" {{ old('source') == 'jobboard' ? 'selected' : '' }}>Vacaturebank</option>
                                    <option value="other" {{ old('source') == 'other' ? 'selected' : '' }}>Anders</option>
                                </select>
                            </div>
                        </div>

                        <!-- Voorkeuren -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="section-title">Voorkeuren</h6>
                                <hr class="section-divider">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="availability" class="form-label">Beschikbaarheid *</label>
                                <select class="form-select" id="availability" name="availability" required>
                                    <option value="">Selecteer beschikbaarheid</option>
                                    <option value="immediate" {{ old('availability') == 'immediate' ? 'selected' : '' }}>Direct beschikbaar</option>
                                    <option value="2_weeks" {{ old('availability') == '2_weeks' ? 'selected' : '' }}>Binnen 2 weken</option>
                                    <option value="1_month" {{ old('availability') == '1_month' ? 'selected' : '' }}>Binnen 1 maand</option>
                                    <option value="3_months" {{ old('availability') == '3_months' ? 'selected' : '' }}>Binnen 3 maanden</option>
                                    <option value="custom" {{ old('availability') == 'custom' ? 'selected' : '' }}>Op afspraak</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="preferred_work_type" class="form-label">Gewenste Werkvorm *</label>
                                <select class="form-select" id="preferred_work_type" name="preferred_work_type" required>
                                    <option value="">Selecteer werkvorm</option>
                                    <option value="full_time" {{ old('preferred_work_type') == 'full_time' ? 'selected' : '' }}>Volledig</option>
                                    <option value="part_time" {{ old('preferred_work_type') == 'part_time' ? 'selected' : '' }}>Deeltijd</option>
                                    <option value="freelance" {{ old('preferred_work_type') == 'freelance' ? 'selected' : '' }}>Freelance</option>
                                    <option value="contract" {{ old('preferred_work_type') == 'contract' ? 'selected' : '' }}>Contract</option>
                                    <option value="hybrid" {{ old('preferred_work_type') == 'hybrid' ? 'selected' : '' }}>Hybride</option>
                                    <option value="remote" {{ old('preferred_work_type') == 'remote' ? 'selected' : '' }}>Remote</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="preferred_location" class="form-label">Gewenste Locatie</label>
                                <input type="text" class="form-control" id="preferred_location" name="preferred_location" 
                                       value="{{ old('preferred_location') }}">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status *</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="">Selecteer status</option>
                                    <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>In Afwachting</option>
                                    <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Actief</option>
                                    <option value="rejected" {{ old('status') == 'rejected' ? 'selected' : '' }}>Afgewezen</option>
                                    <option value="hired" {{ old('status') == 'hired' ? 'selected' : '' }}>Aangenomen</option>
                                </select>
                            </div>
                        </div>

                        <!-- Vaardigheden en Talen -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="section-title">Vaardigheden en Talen</h6>
                                <hr class="section-divider">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="skills" class="form-label">Vaardigheden</label>
                                <div class="skills-container">
                                    <div class="skill-input-group">
                                        <input type="text" class="form-control skill-input" placeholder="Voeg vaardigheid toe">
                                        <button type="button" class="btn btn-outline-primary btn-sm add-skill">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    <div class="skills-list" id="skillsList"></div>
                                    <input type="hidden" name="skills" id="skillsHidden" value="{{ old('skills') }}">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="languages" class="form-label">Talen</label>
                                <div class="languages-container">
                                    <div class="language-input-group">
                                        <input type="text" class="form-control language-input" placeholder="Voeg taal toe">
                                        <button type="button" class="btn btn-outline-primary btn-sm add-language">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    <div class="languages-list" id="languagesList"></div>
                                    <input type="hidden" name="languages" id="languagesHidden" value="{{ old('languages') }}">
                                </div>
                            </div>
                        </div>

                        <!-- Bijlagen -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="section-title">Bijlagen</h6>
                                <hr class="section-divider">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="cv_path" class="form-label">CV Upload</label>
                                <input type="file" class="form-control" id="cv_path" name="cv_path" 
                                       accept=".pdf,.doc,.docx">
                                <small class="form-text text-muted">Toegestane formaten: PDF, DOC, DOCX (max 10MB)</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="cover_letter" class="form-label">Motivatiebrief</label>
                                <textarea class="form-control" id="cover_letter" name="cover_letter" 
                                          rows="4" placeholder="Motivatiebrief van de kandidaat...">{{ old('cover_letter') }}</textarea>
                            </div>
                        </div>

                        <!-- Online Profielen -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="section-title">Online Profielen</h6>
                                <hr class="section-divider">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="linkedin_url" class="form-label">LinkedIn Profiel</label>
                                <input type="url" class="form-control" id="linkedin_url" name="linkedin_url" 
                                       value="{{ old('linkedin_url') }}" placeholder="https://linkedin.com/in/...">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="website_url" class="form-label">Website</label>
                                <input type="url" class="form-control" id="website_url" name="website_url" 
                                       value="{{ old('website_url') }}" placeholder="https://...">
                            </div>
                        </div>

                        <!-- Toestemmingen -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="section-title">Toestemmingen</h6>
                                <hr class="section-divider">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="consent_gdpr" 
                                           name="consent_gdpr" value="1" {{ old('consent_gdpr') ? 'checked' : '' }} required>
                                    <label class="form-check-label" for="consent_gdpr">
                                        GDPR Toestemming * <small class="text-muted">(Verplicht)</small>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="consent_marketing" 
                                           name="consent_marketing" value="1" {{ old('consent_marketing') }}>
                                    <label class="form-check-label" for="consent_marketing">
                                        Marketing Toestemming
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Notities -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="section-title">Notities</h6>
                                <hr class="section-divider">
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="notes" class="form-label">Interne Notities</label>
                                <textarea class="form-control" id="notes" name="notes" 
                                          rows="3" placeholder="Interne notities over de kandidaat...">{{ old('notes') }}</textarea>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('admin.candidates.index') }}" class="material-btn material-btn-secondary">
                                        <i class="fas fa-times me-2"></i> Annuleren
                                    </a>
                                    <button type="submit" class="material-btn material-btn-primary">
                                        <i class="fas fa-save me-2"></i> Kandidaat Opslaan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
:root {
    --primary-color: #28a745;
    --primary-light: #34ce57;
    --primary-dark: #1e7e34;
    --primary-hover: #2fb344;
    --success-color: #4caf50;
    --warning-color: #ff9800;
    --danger-color: #f44336;
    --info-color: #2196f3;
    --secondary-color: #757575;
    --light-bg: #f5f5f5;
    --border-color: #e0e0e0;
    --text-primary: #212121;
    --text-secondary: #757575;
    --shadow: 0 2px 4px rgba(0,0,0,0.1);
    --shadow-hover: 0 4px 8px rgba(0,0,0,0.15);
    --border-radius: 8px;
    --transition: all 0.3s ease;
    --spacing: 1rem;
}

.card {
    border: none;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    margin-bottom: var(--spacing);
    transition: var(--transition);
}

.card:hover {
    box-shadow: var(--shadow-hover);
}

.card-header {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: white;
    border-bottom: 1px solid var(--border-color);
    padding: 1rem 1.5rem;
    border-radius: var(--border-radius) var(--border-radius) 0 0;
}

.card-body {
    padding: 1.5rem;
}

.section-title {
    color: var(--primary-color);
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.section-divider {
    border-color: var(--primary-color);
    opacity: 0.3;
    margin: 0.5rem 0 1rem 0;
}

.form-label {
    font-weight: 500;
    color: var(--text-color);
    margin-bottom: 0.5rem;
}

.form-control, .form-select {
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 0.75rem;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

.form-text {
    font-size: 0.875rem;
    color: var(--text-color);
    opacity: 0.7;
}

.form-check-input:checked {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

.form-check-label {
    color: var(--text-color);
    font-weight: 500;
}

.skills-container, .languages-container {
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1rem;
    background: var(--background-color);
}

.skill-input-group, .language-input-group {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.skill-input, .language-input {
    flex: 1;
}

.skills-list, .languages-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.skill-tag, .language-tag {
    background: var(--primary-color);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.skill-tag .remove-skill, .language-tag .remove-language {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    padding: 0;
    font-size: 1rem;
    line-height: 1;
}

.skill-tag .remove-skill:hover, .language-tag .remove-language:hover {
    opacity: 0.8;
}

.alert {
    border: none;
    border-radius: var(--border-radius);
    padding: 1rem 1.5rem;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border-left: 4px solid #dc3545;
}

@media (max-width: 768px) {
    .card-body {
        padding: 1rem;
    }
    
    .d-flex.justify-content-end {
        justify-content: center !important;
    }
    
    .d-flex.gap-2 {
        flex-direction: column;
        gap: 0.5rem !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Skills management
    const skillsList = document.getElementById('skillsList');
    const skillsHidden = document.getElementById('skillsHidden');
    const addSkillBtn = document.querySelector('.add-skill');
    const skillInput = document.querySelector('.skill-input');

    addSkillBtn.addEventListener('click', function() {
        const skill = skillInput.value.trim();
        if (skill) {
            addSkill(skill);
            skillInput.value = '';
            updateSkillsHidden();
        }
    });

    skillInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addSkillBtn.click();
        }
    });

    function addSkill(skill) {
        const skillTag = document.createElement('div');
        skillTag.className = 'skill-tag';
        skillTag.innerHTML = `
            ${skill}
            <button type="button" class="remove-skill" onclick="this.parentElement.remove(); updateSkillsHidden();">
                <i class="fas fa-times"></i>
            </button>
        `;
        skillsList.appendChild(skillTag);
    }

    function updateSkillsHidden() {
        const skills = Array.from(skillsList.querySelectorAll('.skill-tag'))
            .map(tag => tag.textContent.trim().replace('×', '').trim());
        skillsHidden.value = JSON.stringify(skills);
    }

    // Languages management
    const languagesList = document.getElementById('languagesList');
    const languagesHidden = document.getElementById('languagesHidden');
    const addLanguageBtn = document.querySelector('.add-language');
    const languageInput = document.querySelector('.language-input');

    addLanguageBtn.addEventListener('click', function() {
        const language = languageInput.value.trim();
        if (language) {
            addLanguage(language);
            languageInput.value = '';
            updateLanguagesHidden();
        }
    });

    languageInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addLanguageBtn.click();
        }
    });

    function addLanguage(language) {
        const languageTag = document.createElement('div');
        languageTag.className = 'language-tag';
        languageTag.innerHTML = `
            ${language}
            <button type="button" class="remove-language" onclick="this.parentElement.remove(); updateLanguagesHidden();">
                <i class="fas fa-times"></i>
            </button>
        `;
        languagesList.appendChild(languageTag);
    }

    function updateLanguagesHidden() {
        const languages = Array.from(languagesList.querySelectorAll('.language-tag'))
            .map(tag => tag.textContent.trim().replace('×', '').trim());
        languagesHidden.value = JSON.stringify(languages);
    }

    // Initialize with old values if they exist
    const oldSkills = skillsHidden.value ? JSON.parse(skillsHidden.value) : [];
    const oldLanguages = languagesHidden.value ? JSON.parse(languagesHidden.value) : [];

    oldSkills.forEach(skill => addSkill(skill));
    oldLanguages.forEach(language => addLanguage(language));
});
</script>
@endsection
