@extends('admin.layouts.app')

@section('title', 'Kandidaat Details - ' . $candidate->full_name)

@section('content')
<style>
    :root {
        --primary-color: #1976d2;
        --primary-light: #42a5f5;
        --primary-dark: #1565c0;
        --primary-hover: #1976d2;
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
    }

    .material-card {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        margin-bottom: 24px;
        overflow: hidden;
        transition: var(--transition);
    }

    .material-card:hover {
        box-shadow: var(--shadow-hover);
    }

    .card-header {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        color: white;
        padding: 20px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }

    .card-header h5 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .material-header-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .material-btn {
        padding: 10px 20px;
        border: none;
        border-radius: var(--border-radius);
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: var(--transition);
        cursor: pointer;
        font-size: 14px;
        height: 44px;
        min-height: 44px;
    }

    .material-btn-warning {
        background: var(--warning-color);
        color: white;
    }

    .material-btn-warning:hover {
        background: #f57c00;
        color: white;
        transform: translateY(-2px);
    }

    .material-btn-secondary {
        background: var(--light-bg);
        color: var(--text-primary);
    }

    .material-btn-secondary:hover {
        background: #e0e0e0;
        color: var(--text-primary);
        transform: translateY(-2px);
    }

    .card-body {
        padding: 24px;
    }

    .candidate-header {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-radius: var(--border-radius);
        padding: 24px;
        margin-bottom: 24px;
        border-left: 4px solid var(--primary-color);
    }

    .candidate-title {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 12px;
        line-height: 1.2;
    }

    .candidate-meta {
        display: flex;
        align-items: center;
        gap: 24px;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--text-secondary);
        font-size: 14px;
    }

    .meta-item i {
        color: var(--primary-color);
        width: 16px;
    }

    .candidate-status {
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }

    .candidate-status:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    .status-active {
        background: linear-gradient(135deg, #f1f8e9 0%, #81c784 100%);
        color: #388e3c;
        border: 2px solid #81c784;
    }

    .status-pending {
        background: linear-gradient(135deg, #fff3e0 0%, #ffb74d 100%);
        color: #f57c00;
        border: 2px solid #ffb74d;
    }

    .status-rejected {
        background: linear-gradient(135deg, #ffcdd2 0%, #e57373 100%);
        color: #d32f2f;
        border: 2px solid #e57373;
    }

    .status-hired {
        background: linear-gradient(135deg, #e8f5e8 0%, #66bb6a 100%);
        color: #388e3c;
        border: 2px solid #66bb6a;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 24px;
        margin-bottom: 24px;
    }

    .info-section {
        background: white;
        border-radius: var(--border-radius);
        padding: 20px;
        box-shadow: var(--shadow);
        border: 1px solid var(--border-color);
    }

    .section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 16px;
        padding-bottom: 8px;
        border-bottom: 2px solid var(--primary-color);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .info-table {
        width: 100%;
        border-collapse: collapse;
    }

    .info-table tr {
        border-bottom: 1px solid var(--border-color);
    }

    .info-table tr:last-child {
        border-bottom: none;
    }

    .info-table td {
        padding: 12px 0;
        vertical-align: top;
    }

    .info-table td:first-child {
        font-weight: 600;
        color: var(--text-primary);
        width: 180px;
        min-width: 180px;
    }

    .info-table td:last-child {
        color: var(--text-secondary);
    }

    .material-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        text-transform: uppercase;
    }

    .material-badge-primary {
        background: var(--primary-color);
        color: white;
    }

    .material-badge-secondary {
        background: var(--secondary-color);
        color: white;
    }

    .material-badge-success {
        background: var(--success-color);
        color: white;
    }

    .material-badge-warning {
        background: var(--warning-color);
        color: white;
    }

    .material-badge-danger {
        background: var(--danger-color);
        color: white;
    }

    .material-badge-info {
        background: var(--info-color);
        color: white;
    }

    .material-text-muted {
        color: var(--text-secondary);
        font-style: italic;
    }

    .skills-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .skill-badge {
        background: var(--primary-color);
        color: white;
        padding: 4px 12px;
        border-radius: 16px;
        font-size: 12px;
        font-weight: 500;
    }

    .languages-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .language-badge {
        background: var(--info-color);
        color: white;
        padding: 4px 12px;
        border-radius: 16px;
        font-size: 12px;
        font-weight: 500;
    }

    .file-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--primary-color);
        text-decoration: none;
        padding: 8px 16px;
        border: 1px solid var(--primary-color);
        border-radius: var(--border-radius);
        transition: var(--transition);
    }

    .file-link:hover {
        background: var(--primary-color);
        color: white;
        transform: translateY(-2px);
    }
</style>

<div class="container-fluid">
    <div class="material-card">
        <div class="card-header">
            <h5>
                <i class="fas fa-user-graduate"></i>
                Kandidaat Details: {{ $candidate->full_name }}
            </h5>
            <div class="material-header-actions">
                <a href="{{ route('admin.candidates.edit', $candidate) }}" class="material-btn material-btn-warning me-2">
                    <i class="fas fa-edit"></i> Bewerken
                </a>
                <a href="{{ route('admin.candidates.index') }}" class="material-btn material-btn-secondary">
                    <i class="fas fa-arrow-left"></i> Terug naar Overzicht
                </a>
            </div>
        </div>
        <div class="card-body">
            <!-- Candidate Header Section -->
            <div class="candidate-header">
                <h1 class="candidate-title">{{ $candidate->full_name }}</h1>
                <div class="candidate-meta">
                    <div class="meta-item">
                        <i class="fas fa-envelope"></i>
                        <span>{{ $candidate->email }}</span>
                    </div>
                    @if($candidate->phone)
                        <div class="meta-item">
                            <i class="fas fa-phone"></i>
                            <span>{{ $candidate->phone }}</span>
                        </div>
                    @endif
                    @if($candidate->date_of_birth)
                        <div class="meta-item">
                            <i class="fas fa-birthday-cake"></i>
                            <span>{{ $candidate->date_of_birth->format('d-m-Y') }} ({{ $candidate->age }} jaar)</span>
                        </div>
                    @endif
                    @if($candidate->current_position)
                        <div class="meta-item">
                            <i class="fas fa-briefcase"></i>
                            <span>{{ $candidate->current_position }}</span>
                        </div>
                    @endif
                    @if($candidate->desired_position)
                        <div class="meta-item">
                            <i class="fas fa-bullseye"></i>
                            <span>{{ $candidate->desired_position }}</span>
                        </div>
                    @endif
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        <span>Aangemaakt: {{ $candidate->created_at->format('d-m-Y') }}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-clock"></i>
                        <span>Bijgewerkt: {{ $candidate->updated_at->format('d-m-Y') }}</span>
                    </div>
                </div>
                <div class="candidate-status status-{{ $candidate->status }}">
                    <i class="fas fa-circle"></i>
                    {{ ucfirst($candidate->status) }}
                </div>
            </div>

                        <div class="info-grid" style="grid-template-columns: repeat(2, 1fr);">
                <!-- Persoonlijke Informatie -->
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-user"></i>
                        Persoonlijke Informatie
                    </h6>
                    <table class="info-table">
                        <tr>
                            <td>Referentienummer</td>
                            <td>{{ $candidate->reference_number ?? 'Niet ingesteld' }}</td>
                        </tr>
                        <tr>
                            <td>Voornaam</td>
                            <td>{{ $candidate->first_name }}</td>
                        </tr>
                        <tr>
                            <td>Achternaam</td>
                            <td>{{ $candidate->last_name }}</td>
                        </tr>
                        <tr>
                            <td>E-mailadres</td>
                            <td>{{ $candidate->email }}</td>
                        </tr>
                        @if($candidate->phone)
                            <tr>
                                <td>Telefoonnummer</td>
                                <td>{{ $candidate->phone }}</td>
                            </tr>
                        @endif
                        @if($candidate->date_of_birth)
                            <tr>
                                <td>Geboortedatum</td>
                                <td>{{ $candidate->date_of_birth->format('d-m-Y') }} ({{ $candidate->age }} jaar)</td>
                            </tr>
                        @endif
                        @if($candidate->nationality)
                            <tr>
                                <td>Nationaliteit</td>
                                <td>{{ $candidate->nationality }}</td>
                            </tr>
                        @endif
                        @if($candidate->gender)
                            <tr>
                                <td>Geslacht</td>
                                <td>{{ ucfirst($candidate->gender) }}</td>
                            </tr>
                        @endif
                    </table>
                </div>

                <!-- Adres Informatie -->
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-map-marker-alt"></i>
                        Adres Informatie
                    </h6>
                    <table class="info-table">
                        @if($candidate->address)
                            <tr>
                                <td>Adres</td>
                                <td>{{ $candidate->address }}</td>
                            </tr>
                        @endif
                        @if($candidate->postal_code)
                            <tr>
                                <td>Postcode</td>
                                <td>{{ $candidate->postal_code }}</td>
                            </tr>
                        @endif
                        @if($candidate->city)
                            <tr>
                                <td>Plaats</td>
                                <td>{{ $candidate->city }}</td>
                            </tr>
                        @endif
                        @if($candidate->country)
                            <tr>
                                <td>Land</td>
                                <td>{{ $candidate->country }}</td>
                            </tr>
                        @endif
                        @if($candidate->region)
                            <tr>
                                <td>Regio</td>
                                <td>{{ $candidate->region }}</td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>

            <div class="info-grid">
                <!-- Professionele Informatie -->
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-briefcase"></i>
                        Professionele Informatie
                    </h6>
                    <table class="info-table">
                        @if($candidate->current_position)
                            <tr>
                                <td>Huidige functie</td>
                                <td>{{ $candidate->current_position }}</td>
                            </tr>
                        @endif
                        @if($candidate->desired_position)
                            <tr>
                                <td>Gewenste functie</td>
                                <td>{{ $candidate->desired_position }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td>Ervaring</td>
                            <td>{{ $candidate->experience_years }}+ jaar</td>
                        </tr>
                        <tr>
                            <td>Opleidingsniveau</td>
                            <td>
                                @if($candidate->education_level)
                                    <span class="material-badge material-badge-info">
                                        {{ $candidate->education_level_display }}
                                    </span>
                                @else
                                    <span class="material-text-muted">Niet ingesteld</span>
                                @endif
                            </td>
                        </tr>
                        @if($candidate->work_type)
                            <tr>
                                <td>Werktype</td>
                                <td>
                                    <span class="material-badge material-badge-primary">
                                        {{ $candidate->work_type_display }}
                                    </span>
                                </td>
                            </tr>
                        @endif
                        @if($candidate->availability)
                            <tr>
                                <td>Beschikbaarheid</td>
                                <td>
                                    <span class="material-badge material-badge-success">
                                        {{ $candidate->availability_display }}
                                    </span>
                                </td>
                            </tr>
                        @endif
                    </table>
                </div>

                <!-- Vaardigheden & Talen -->
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-star"></i>
                        Vaardigheden & Talen
                    </h6>
                    @if($candidate->skills && count($candidate->skills) > 0)
                        <div class="mb-3">
                            <strong>Vaardigheden:</strong>
                            <div class="skills-list mt-2">
                                @foreach($candidate->skills as $skill)
                                    <span class="skill-badge">{{ $skill }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @if($candidate->languages && count($candidate->languages) > 0)
                        <div class="mb-3">
                            <strong>Talen:</strong>
                            <div class="languages-list mt-2">
                                @foreach($candidate->languages as $language)
                                    <span class="language-badge">{{ $language }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @if(!$candidate->skills && !$candidate->languages)
                        <span class="material-text-muted">Geen vaardigheden of talen opgegeven</span>
                    @endif
                </div>

                <!-- Online Profielen -->
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-globe"></i>
                        Online Profielen
                    </h6>
                    <table class="info-table">
                        @if($candidate->linkedin_url)
                            <tr>
                                <td>LinkedIn</td>
                                <td>
                                    <a href="{{ $candidate->linkedin_url }}" target="_blank" class="file-link">
                                        <i class="fab fa-linkedin"></i>
                                        Bekijk profiel
                                    </a>
                                </td>
                            </tr>
                        @endif
                        @if($candidate->github_url)
                            <tr>
                                <td>GitHub</td>
                                <td>
                                    <a href="{{ $candidate->github_url }}" target="_blank" class="file-link">
                                        <i class="fab fa-github"></i>
                                        Bekijk profiel
                                    </a>
                                </td>
                            </tr>
                        @endif
                        @if($candidate->portfolio_url)
                            <tr>
                                <td>Portfolio</td>
                                <td>
                                    <a href="{{ $candidate->portfolio_url }}" target="_blank" class="file-link">
                                        <i class="fas fa-briefcase"></i>
                                        Bekijk portfolio
                                    </a>
                                </td>
                            </tr>
                        @endif
                        @if(!$candidate->linkedin_url && !$candidate->github_url && !$candidate->portfolio_url)
                            <tr>
                                <td colspan="2">
                                    <span class="material-text-muted">Geen online profielen opgegeven</span>
                                </td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>

             <!-- Bijlagen -->
             <div class="info-section">
                 <h6 class="section-title">
                     <i class="fas fa-paperclip"></i>
                     Bijlagen
                 </h6>
                 <table class="info-table">
                     @if($candidate->cv_path)
                         <tr>
                             <td>CV</td>
                             <td>
                                 <a href="{{ route('admin.candidates.downloadCV', $candidate) }}" class="file-link">
                                     <i class="fas fa-download"></i>
                                     Download CV
                                 </a>
                             </td>
                         </tr>
                     @endif
                     @if($candidate->cover_letter)
                         <tr>
                             <td>Motivatiebrief</td>
                             <td>
                                 <div class="p-3 bg-light rounded">
                                     {{ $candidate->cover_letter }}
                                 </div>
                             </td>
                         </tr>
                     @endif
                     @if(!$candidate->cv_path && !$candidate->cover_letter)
                         <tr>
                             <td colspan="2">
                                 <span class="material-text-muted">Geen bijlagen beschikbaar</span>
                             </td>
                         </tr>
                     @endif
                 </table>
             </div>

             @if($candidate->notes)
                 <div class="info-section">
                     <h6 class="section-title">
                         <i class="fas fa-sticky-note"></i>
                         Notities
                     </h6>
                     <div class="p-3 bg-light rounded">
                         {{ $candidate->notes }}
                     </div>
                 </div>
             @endif
        </div>
    </div>
</div>
@endsection
