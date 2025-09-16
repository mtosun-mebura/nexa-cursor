@extends('admin.layouts.app')

@section('title', 'Kandidaat Bewerken')

@section('content')
<style>
    :root {
        --primary-color: #1976d2;
        --primary-light: #42a5f5;
        --primary-dark: #1565c0;
        --secondary-color: #e3f2fd;
        --success-color: #4caf50;
        --warning-color: #ff9800;
        --danger-color: #f44336;
        --info-color: #2196f3;
        --light-bg: #fafafa;
        --dark-text: #212121;
        --medium-text: #757575;
        --border-color: #e0e0e0;
        --shadow-light: 0 2px 4px rgba(0,0,0,0.1);
        --shadow-medium: 0 4px 8px rgba(0,0,0,0.12);
        --shadow-heavy: 0 8px 16px rgba(0,0,0,0.15);
        --border-radius: 8px;
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .material-card {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-light);
        border: none;
        margin-bottom: 24px;
        transition: var(--transition);
        overflow: hidden;
    }
    
    .material-card:hover {
        box-shadow: var(--shadow-medium);
    }
    
    .material-card .card-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
        color: white;
        border-radius: 0;
        padding: 24px 32px;
        border: none;
        position: relative;
        overflow: hidden;
    }
    
    .material-card .card-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.1) 50%, transparent 70%);
        transform: translateX(-100%);
        transition: var(--transition);
    }
    
    .material-card .card-header:hover::before {
        transform: translateX(100%);
    }
    
    .material-card .card-body {
        padding: 32px;
    }
    
    .material-btn {
        border-radius: var(--border-radius);
        text-transform: uppercase;
        font-weight: 500;
        letter-spacing: 0.5px;
        padding: 12px 24px;
        border: none;
        transition: var(--transition);
        box-shadow: var(--shadow-light);
        position: relative;
        overflow: hidden;
        cursor: pointer;
        font-size: 14px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    .material-btn::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        background: rgba(255,255,255,0.3);
        border-radius: 50%;
        transform: translate(-50%, -50%);
        transition: var(--transition);
    }
    
    .material-btn:hover::before {
        width: 300px;
        height: 300px;
    }
    
    .material-btn:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-medium);
        text-decoration: none;
    }
    
    .material-btn:active {
        transform: translateY(0);
        box-shadow: var(--shadow-light);
    }
    
    .material-btn-primary {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
        color: white;
    }
    
    .material-btn-secondary {
        background: var(--light-bg);
        color: var(--dark-text);
        border: 1px solid var(--border-color);
    }
    
    .material-btn-secondary:hover {
        background: var(--secondary-color);
        color: var(--primary-color);
        transform: translateY(-2px);
        box-shadow: var(--shadow-medium);
    }
    
    .material-btn-info {
        background: linear-gradient(135deg, var(--info-color) 0%, #42a5f5 100%);
        color: white;
    }
    
    .form-control, .form-select {
        border-radius: var(--border-radius);
        border: 1px solid var(--border-color);
        padding: 12px 16px;
        transition: var(--transition);
        background-color: white;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(25, 118, 210, 0.25);
        outline: none;
    }
    
    .form-label {
        font-weight: 600;
        color: var(--dark-text);
        margin-bottom: 8px;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .form-text {
        color: var(--medium-text);
        font-size: 12px;
        margin-top: 4px;
    }
    
    .form-check {
        margin-bottom: 16px;
    }
    
    .form-check-input {
        border-radius: 4px;
        border: 2px solid var(--border-color);
        transition: var(--transition);
    }
    
    .form-check-input:checked {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }
    
    .form-check-label {
        font-weight: 500;
        color: var(--dark-text);
        margin-left: 8px;
    }
    
    .alert {
        border-radius: var(--border-radius);
        border: none;
        padding: 16px 20px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        box-shadow: var(--shadow-light);
    }
    
    .alert-danger {
        background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
        color: #c62828;
    }
    
    .invalid-feedback {
        color: var(--danger-color);
        font-size: 12px;
        margin-top: 4px;
    }
    
    .is-invalid {
        border-color: var(--danger-color) !important;
    }
    
    .is-invalid:focus {
        box-shadow: 0 0 0 0.2rem rgba(244, 67, 54, 0.25) !important;
    }

    .section-divider {
        border: none;
        height: 2px;
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
        margin: 24px 0;
        border-radius: 1px;
    }

    .section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--primary-color);
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="material-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-user-graduate me-2"></i> Kandidaat Bewerken
                    </h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.candidates.show', $candidate) }}" class="material-btn material-btn-info">
                            <i class="fas fa-eye me-2"></i> Bekijken
                        </a>
                        <a href="{{ route('admin.candidates.index') }}" class="material-btn material-btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Terug naar Overzicht
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <div>
                                <strong>Er zijn fouten opgetreden:</strong>
                                <ul class="mb-0 mt-2">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <form action="{{ route('admin.candidates.update', $candidate) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <!-- Persoonlijke Informatie -->
                        <h6 class="section-title">
                            <i class="fas fa-user"></i>
                            Persoonlijke Informatie
                        </h6>
                        <div class="section-divider"></div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">Voornaam *</label>
                                    <input type="text" 
                                           class="form-control @error('first_name') is-invalid @enderror" 
                                           id="first_name" 
                                           name="first_name" 
                                           value="{{ old('first_name', $candidate->first_name) }}" 
                                           required>
                                    @error('first_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">Achternaam *</label>
                                    <input type="text" 
                                           class="form-control @error('last_name') is-invalid @enderror" 
                                           id="last_name" 
                                           name="last_name" 
                                           value="{{ old('last_name', $candidate->last_name) }}" 
                                           required>
                                    @error('last_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">E-mailadres *</label>
                                    <input type="email" 
                                           class="form-control @error('email') is-invalid @enderror" 
                                           id="email" 
                                           name="email" 
                                           value="{{ old('email', $candidate->email) }}" 
                                           required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Telefoonnummer</label>
                                    <input type="tel" 
                                           class="form-control @error('phone') is-invalid @enderror" 
                                           id="phone" 
                                           name="phone" 
                                           value="{{ old('phone', $candidate->phone) }}">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="date_of_birth" class="form-label">Geboortedatum</label>
                                    <input type="date" 
                                           class="form-control @error('date_of_birth') is-invalid @enderror" 
                                           id="date_of_birth" 
                                           name="date_of_birth" 
                                           value="{{ old('date_of_birth', $candidate->date_of_birth ? $candidate->date_of_birth->format('Y-m-d') : '') }}">
                                    @error('date_of_birth')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nationality" class="form-label">Nationaliteit</label>
                                    <input type="text" 
                                           class="form-control @error('nationality') is-invalid @enderror" 
                                           id="nationality" 
                                           name="nationality" 
                                           value="{{ old('nationality', $candidate->nationality) }}">
                                    @error('nationality')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="gender" class="form-label">Geslacht</label>
                                    <select class="form-select @error('gender') is-invalid @enderror" id="gender" name="gender">
                                        <option value="">Selecteer geslacht</option>
                                        <option value="male" {{ old('gender', $candidate->gender) == 'male' ? 'selected' : '' }}>Man</option>
                                        <option value="female" {{ old('gender', $candidate->gender) == 'female' ? 'selected' : '' }}>Vrouw</option>
                                        <option value="other" {{ old('gender', $candidate->gender) == 'other' ? 'selected' : '' }}>Anders</option>
                                    </select>
                                    @error('gender')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="reference_number" class="form-label">Referentienummer</label>
                                    <input type="text" 
                                           class="form-control @error('reference_number') is-invalid @enderror" 
                                           id="reference_number" 
                                           name="reference_number" 
                                           value="{{ old('reference_number', $candidate->reference_number) }}">
                                    @error('reference_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Adres Informatie -->
                        <h6 class="section-title">
                            <i class="fas fa-map-marker-alt"></i>
                            Adres Informatie
                        </h6>
                        <div class="section-divider"></div>
                        
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="address" class="form-label">Adres</label>
                                    <textarea class="form-control @error('address') is-invalid @enderror" 
                                              id="address" 
                                              name="address" 
                                              rows="2">{{ old('address', $candidate->address) }}</textarea>
                                    @error('address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="postal_code" class="form-label">Postcode</label>
                                    <input type="text" 
                                           class="form-control @error('postal_code') is-invalid @enderror" 
                                           id="postal_code" 
                                           name="postal_code" 
                                           value="{{ old('postal_code', $candidate->postal_code) }}">
                                    @error('postal_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="city" class="form-label">Plaats</label>
                                    <input type="text" 
                                           class="form-control @error('city') is-invalid @enderror" 
                                           id="city" 
                                           name="city" 
                                           value="{{ old('city', $candidate->city) }}">
                                    @error('city')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="country" class="form-label">Land</label>
                                    <input type="text" 
                                           class="form-control @error('country') is-invalid @enderror" 
                                           id="country" 
                                           name="country" 
                                           value="{{ old('country', $candidate->country) }}">
                                    @error('country')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="region" class="form-label">Regio</label>
                                    <input type="text" 
                                           class="form-control @error('region') is-invalid @enderror" 
                                           id="region" 
                                           name="region" 
                                           value="{{ old('region', $candidate->region) }}">
                                    @error('region')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Professionele Informatie -->
                        <h6 class="section-title">
                            <i class="fas fa-briefcase"></i>
                            Professionele Informatie
                        </h6>
                        <div class="section-divider"></div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="current_position" class="form-label">Huidige functie</label>
                                    <input type="text" 
                                           class="form-control @error('current_position') is-invalid @enderror" 
                                           id="current_position" 
                                           name="current_position" 
                                           value="{{ old('current_position', $candidate->current_position) }}">
                                    @error('current_position')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="desired_position" class="form-label">Gewenste functie</label>
                                    <input type="text" 
                                           class="form-control @error('desired_position') is-invalid @enderror" 
                                           id="desired_position" 
                                           name="desired_position" 
                                           value="{{ old('desired_position', $candidate->desired_position) }}">
                                    @error('desired_position')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="experience_years" class="form-label">Ervaring (jaren) *</label>
                                    <input type="number" 
                                           class="form-control @error('experience_years') is-invalid @enderror" 
                                           id="experience_years" 
                                           name="experience_years" 
                                           value="{{ old('experience_years', $candidate->experience_years) }}" 
                                           min="0" 
                                           required>
                                    @error('experience_years')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="education_level" class="form-label">Opleidingsniveau</label>
                                    <select class="form-select @error('education_level') is-invalid @enderror" id="education_level" name="education_level">
                                        <option value="">Selecteer opleidingsniveau</option>
                                        <option value="high_school" {{ old('education_level', $candidate->education_level) == 'high_school' ? 'selected' : '' }}>Middelbare School</option>
                                        <option value="vocational" {{ old('education_level', $candidate->education_level) == 'vocational' ? 'selected' : '' }}>MBO</option>
                                        <option value="bachelor" {{ old('education_level', $candidate->education_level) == 'bachelor' ? 'selected' : '' }}>HBO/Bachelor</option>
                                        <option value="master" {{ old('education_level', $candidate->education_level) == 'master' ? 'selected' : '' }}>WO/Master</option>
                                        <option value="phd" {{ old('education_level', $candidate->education_level) == 'phd' ? 'selected' : '' }}>PhD/Doctoraat</option>
                                    </select>
                                    @error('education_level')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="work_type" class="form-label">Werktype</label>
                                    <select class="form-select @error('work_type') is-invalid @enderror" id="work_type" name="work_type">
                                        <option value="">Selecteer werktype</option>
                                        <option value="full_time" {{ old('work_type', $candidate->work_type) == 'full_time' ? 'selected' : '' }}>Volledig</option>
                                        <option value="part_time" {{ old('work_type', $candidate->work_type) == 'part_time' ? 'selected' : '' }}>Deeltijd</option>
                                        <option value="freelance" {{ old('work_type', $candidate->work_type) == 'freelance' ? 'selected' : '' }}>Freelance</option>
                                        <option value="internship" {{ old('work_type', $candidate->work_type) == 'internship' ? 'selected' : '' }}>Stage</option>
                                        <option value="temporary" {{ old('work_type', $candidate->work_type) == 'temporary' ? 'selected' : '' }}>Tijdelijk</option>
                                    </select>
                                    @error('work_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="availability" class="form-label">Beschikbaarheid</label>
                                    <select class="form-select @error('availability') is-invalid @enderror" id="availability" name="availability">
                                        <option value="">Selecteer beschikbaarheid</option>
                                        <option value="immediate" {{ old('availability', $candidate->availability) == 'immediate' ? 'selected' : '' }}>Direct</option>
                                        <option value="within_month" {{ old('availability', $candidate->availability) == 'within_month' ? 'selected' : '' }}>Binnen 1 maand</option>
                                        <option value="within_3_months" {{ old('availability', $candidate->availability) == 'within_3_months' ? 'selected' : '' }}>Binnen 3 maanden</option>
                                        <option value="within_6_months" {{ old('availability', $candidate->availability) == 'within_6_months' ? 'selected' : '' }}>Binnen 6 maanden</option>
                                        <option value="flexible" {{ old('availability', $candidate->availability) == 'flexible' ? 'selected' : '' }}>Flexibel</option>
                                    </select>
                                    @error('availability')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Vaardigheden & Talen -->
                        <h6 class="section-title">
                            <i class="fas fa-star"></i>
                            Vaardigheden & Talen
                        </h6>
                        <div class="section-divider"></div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="skills" class="form-label">Vaardigheden</label>
                                    <textarea class="form-control @error('skills') is-invalid @enderror" 
                                              id="skills" 
                                              name="skills" 
                                              rows="3" 
                                              placeholder="Voer vaardigheden in, gescheiden door komma's">{{ old('skills', is_array($candidate->skills) ? implode(', ', $candidate->skills) : $candidate->skills) }}</textarea>
                                    <div class="form-text">Voer vaardigheden in, gescheiden door komma's (bijv. PHP, Laravel, MySQL)</div>
                                    @error('skills')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="languages" class="form-label">Talen</label>
                                    <textarea class="form-control @error('languages') is-invalid @enderror" 
                                              id="languages" 
                                              name="languages" 
                                              rows="3" 
                                              placeholder="Voer talen in, gescheiden door komma's">{{ old('languages', is_array($candidate->languages) ? implode(', ', $candidate->languages) : $candidate->languages) }}</textarea>
                                    <div class="form-text">Voer talen in, gescheiden door komma's (bijv. Nederlands, Engels, Duits)</div>
                                    @error('languages')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Online Profielen -->
                        <h6 class="section-title">
                            <i class="fas fa-globe"></i>
                            Online Profielen
                        </h6>
                        <div class="section-divider"></div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="linkedin_url" class="form-label">LinkedIn URL</label>
                                    <input type="url" 
                                           class="form-control @error('linkedin_url') is-invalid @enderror" 
                                           id="linkedin_url" 
                                           name="linkedin_url" 
                                           value="{{ old('linkedin_url', $candidate->linkedin_url) }}"
                                           placeholder="https://linkedin.com/in/...">
                                    @error('linkedin_url')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="github_url" class="form-label">GitHub URL</label>
                                    <input type="url" 
                                           class="form-control @error('github_url') is-invalid @enderror" 
                                           id="github_url" 
                                           name="github_url" 
                                           value="{{ old('github_url', $candidate->github_url) }}"
                                           placeholder="https://github.com/...">
                                    @error('github_url')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="portfolio_url" class="form-label">Portfolio URL</label>
                                    <input type="url" 
                                           class="form-control @error('portfolio_url') is-invalid @enderror" 
                                           id="portfolio_url" 
                                           name="portfolio_url" 
                                           value="{{ old('portfolio_url', $candidate->portfolio_url) }}"
                                           placeholder="https://...">
                                    @error('portfolio_url')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Bijlagen -->
                        <h6 class="section-title">
                            <i class="fas fa-paperclip"></i>
                            Bijlagen
                        </h6>
                        <div class="section-divider"></div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cv" class="form-label">CV (PDF)</label>
                                    <input type="file" 
                                           class="form-control @error('cv') is-invalid @enderror" 
                                           id="cv" 
                                           name="cv" 
                                           accept=".pdf,.doc,.docx">
                                    <div class="form-text">Upload een nieuwe CV om de huidige te vervangen</div>
                                    @if($candidate->cv_path)
                                        <div class="mt-2">
                                            <small class="text-muted">Huidige CV: {{ basename($candidate->cv_path) }}</small>
                                        </div>
                                    @endif
                                    @error('cv')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cover_letter" class="form-label">Motivatiebrief</label>
                                    <textarea class="form-control @error('cover_letter') is-invalid @enderror" 
                                              id="cover_letter" 
                                              name="cover_letter" 
                                              rows="4" 
                                              placeholder="Voer de motivatiebrief in">{{ old('cover_letter', $candidate->cover_letter) }}</textarea>
                                    @error('cover_letter')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Status & Notities -->
                        <h6 class="section-title">
                            <i class="fas fa-cog"></i>
                            Status & Notities
                        </h6>
                        <div class="section-divider"></div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status *</label>
                                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                        <option value="active" {{ old('status', $candidate->status) == 'active' ? 'selected' : '' }}>Actief</option>
                                        <option value="pending" {{ old('status', $candidate->status) == 'pending' ? 'selected' : '' }}>In Afwachting</option>
                                        <option value="rejected" {{ old('status', $candidate->status) == 'rejected' ? 'selected' : '' }}>Afgewezen</option>
                                        <option value="hired" {{ old('status', $candidate->status) == 'hired' ? 'selected' : '' }}>Aangenomen</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="source" class="form-label">Bron</label>
                                    <select class="form-select @error('source') is-invalid @enderror" id="source" name="source">
                                        <option value="">Selecteer bron</option>
                                        <option value="website" {{ old('source', $candidate->source) == 'website' ? 'selected' : '' }}>Website</option>
                                        <option value="linkedin" {{ old('source', $candidate->source) == 'linkedin' ? 'selected' : '' }}>LinkedIn</option>
                                        <option value="referral" {{ old('source', $candidate->source) == 'referral' ? 'selected' : '' }}>Doorverwijzing</option>
                                        <option value="job_board" {{ old('source', $candidate->source) == 'job_board' ? 'selected' : '' }}>Vacaturebank</option>
                                        <option value="other" {{ old('source', $candidate->source) == 'other' ? 'selected' : '' }}>Anders</option>
                                    </select>
                                    @error('source')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Interne Notities</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" 
                                              id="notes" 
                                              name="notes" 
                                              rows="4" 
                                              placeholder="Voer interne notities in">{{ old('notes', $candidate->notes) }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex justify-content-end gap-3 mt-4">
                            <a href="{{ route('admin.candidates.index') }}" class="material-btn material-btn-secondary">
                                <i class="fas fa-times me-2"></i> Annuleren
                            </a>
                            <button type="submit" class="material-btn material-btn-primary">
                                <i class="fas fa-save me-2"></i> Wijzigingen Opslaan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
