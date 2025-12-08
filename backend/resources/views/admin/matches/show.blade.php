@extends('admin.layouts.app')

@section('title', 'Match Details - #' . $match->id)

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
                <i class="fas fa-handshake"></i>
                Match Details: #{{ $match->id }}
            </h5>
            <div class="material-header-actions">
                <a href="{{ route('admin.matches.edit', $match) }}" class="kt-btn kt-btn-warning me-2">
                    <i class="fas fa-edit"></i> Bewerken
                </a>
                <a href="{{ route('admin.matches.index') }}" class="kt-btn kt-btn-outline">
                    <i class="fas fa-arrow-left"></i> Terug naar Overzicht
                </a>
            </div>
        </div>
        <div class="kt-card-content">
            <!-- Match Header Section -->
            <div class="match-header">
                <h1 class="match-title">Match #{{ $match->id }}</h1>
                <div class="match-meta">
                    <div class="meta-item">
                        <i class="fas fa-user"></i>
                        <span>{{ $match->user->first_name }} {{ $match->user->last_name }}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-briefcase"></i>
                        <span>{{ $match->vacancy->title }}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-building"></i>
                        <span>{{ $match->vacancy->company->name }}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-chart-line"></i>
                        <span>{{ $match->match_score ?? 'N/A' }}% match</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        <span>Aangemaakt: {{ $match->created_at->format('d-m-Y') }}</span>
                    </div>
                </div>
                <div class="match-status status-{{ $match->status }}">
                    <i class="fas fa-circle"></i>
                    {{ ucfirst(str_replace('_', ' ', $match->status)) }}
                </div>
            </div>

            <div class="info-grid">
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-user"></i>
                        Gebruiker Informatie
                    </h6>
                    <kt-table class="info-kt-table">
                        <tr>
                            <td>Naam</td>
                            <td>{{ $match->user->first_name }} {{ $match->user->last_name }}</td>
                        </tr>
                        <tr>
                            <td>E-mail</td>
                            <td>{{ $match->user->email }}</td>
                        </tr>
                        <tr>
                            <td>Bedrijf</td>
                            <td>{{ $match->user->company->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td>Telefoon</td>
                            <td>{{ $match->user->phone ?? 'N/A' }}</td>
                        </tr>
                    </kt-table>
                </div>
                
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-briefcase"></i>
                        Vacature Informatie
                    </h6>
                    <kt-table class="info-kt-table">
                        <tr>
                            <td>Titel</td>
                            <td>{{ $match->vacancy->title }}</td>
                        </tr>
                        <tr>
                            <td>Bedrijf</td>
                            <td>{{ $match->vacancy->company->name }}</td>
                        </tr>
                        <tr>
                            <td>Locatie</td>
                            <td>{{ $match->vacancy->location ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td>Type</td>
                            <td>{{ ucfirst($match->vacancy->employment_type ?? 'N/A') }}</td>
                        </tr>
                        <tr>
                            <td>Salaris</td>
                            <td>{{ $match->vacancy->salary_range ?? 'N/A' }}</td>
                        </tr>
                    </kt-table>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-chart-line"></i>
                        Match Details
                    </h6>
                    <kt-table class="info-kt-table">
                        <tr>
                            <td>Match Score</td>
                            <td>
                                @if($match->match_score)
                                    <div class="progress">
                                        <div class="progress-bar bg-{{ $match->match_score >= 80 ? 'success' : ($match->match_score >= 60 ? 'warning' : 'danger') }}" 
                                             style="width: {{ $match->match_score }}%">
                                            {{ $match->match_score }}%
                                        </div>
                                    </div>
                                @else
                                    N/A
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Status</td>
                            <td>
                                <span class="kt-badge kt-badge-{{ $match->status == 'pending' ? 'warning' : ($match->status == 'accepted' ? 'success' : ($match->status == 'rejected' ? 'danger' : 'info')) }}">
                                    {{ ucfirst(str_replace('_', ' ', $match->status)) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>Aangemaakt op</td>
                            <td>{{ $match->created_at->format('d-m-Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td>Laatst bijgewerkt</td>
                            <td>{{ $match->updated_at->format('d-m-Y H:i') }}</td>
                        </tr>
                    </kt-table>
                </div>
                
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-comments"></i>
                        Notities & Feedback
                    </h6>
                    <kt-table class="info-kt-table">
                        <tr>
                            <td>Notities</td>
                            <td>{{ $match->notes ?? 'Geen notities' }}</td>
                        </tr>
                        <tr>
                            <td>Feedback</td>
                            <td>{{ $match->feedback ?? 'Geen feedback' }}</td>
                        </tr>
                    </kt-table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
