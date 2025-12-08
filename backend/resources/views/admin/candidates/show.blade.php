@extends('admin.layouts.app')

@section('title', 'Kandidaat Details - ' . $candidate->full_name)

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

    <div class="kt-card">
        <div class="kt-card-header">
            <h5>
                <i class="fas fa-user-graduate"></i>
                Kandidaat Details: {{ $candidate->full_name }}
            </h5>
            <div class="material-header-actions">
                <a href="{{ route('admin.candidates.edit', $candidate) }}" class="kt-btn kt-btn-warning me-2">
                    <i class="fas fa-edit"></i> Bewerken
                </a>
                <a href="{{ route('admin.candidates.index') }}" class="kt-btn kt-btn-outline">
                    <i class="fas fa-arrow-left"></i> Terug naar Overzicht
                </a>
            </div>
        </div>
        <div class="kt-card-content">
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
                    <kt-table class="info-kt-table">
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
                    </kt-table>
                </div>

                <!-- Adres Informatie -->
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-map-marker-alt"></i>
                        Adres Informatie
                    </h6>
                    <kt-table class="info-kt-table">
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
                    </kt-table>
                </div>
            </div>

            <div class="info-grid">
                <!-- Professionele Informatie -->
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-briefcase"></i>
                        Professionele Informatie
                    </h6>
                    <kt-table class="info-kt-table">
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
                                    <span class="kt-badge kt-badge-info">
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
                                    <span class="kt-badge kt-badge-primary">
                                        {{ $candidate->work_type_display }}
                                    </span>
                                </td>
                            </tr>
                        @endif
                        @if($candidate->availability)
                            <tr>
                                <td>Beschikbaarheid</td>
                                <td>
                                    <span class="kt-badge kt-badge-success">
                                        {{ $candidate->availability_display }}
                                    </span>
                                </td>
                            </tr>
                        @endif
                    </kt-table>
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
                    <kt-table class="info-kt-table">
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
                    </kt-table>
                </div>
            </div>

             <!-- Bijlagen -->
             <div class="info-section">
                 <h6 class="section-title">
                     <i class="fas fa-paperclip"></i>
                     Bijlagen
                 </h6>
                 <kt-table class="info-kt-table">
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
                 </kt-table>
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
