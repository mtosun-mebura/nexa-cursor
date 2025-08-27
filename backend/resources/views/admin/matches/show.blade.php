@extends('admin.layouts.app')

@section('title', 'Match Details')

@section('content')
<style>
    :root {
        --primary-color: #3f51b5;
        --primary-light: #7986cb;
        --primary-dark: #303f9f;
        --primary-hover: #5c6bc0;
    }
</style>

@include('admin.material-design-template')


<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="material-card">
                <div class="card-header">
                    <h5 >
                        <i class="fas fa-handshake"></i> Match Details
                    </h5>
                    <div>
                        <a href="{{ route('admin.matches.edit', $match) }}" class="btn btn-warning me-2">
                            <i class="fas fa-edit"></i> Bewerken
                        </a>
                        <a href="{{ route('admin.matches.index') }}" class="material-btn material-btn-secondary">
                            <i class="fas fa-arrow-left"></i> Terug naar Overzicht
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h4>Match #{{ $match->id }}</h4>
                            <p class="material-section-title">
                                <span class="badge bg-{{ $match->status == 'pending' ? 'warning' : ($match->status == 'accepted' ? 'success' : ($match->status == 'rejected' ? 'danger' : 'info')) }}">
                                    {{ ucfirst(str_replace('_', ' ', $match->status)) }}
                                </span>
                                <span class="ms-2">Score: {{ $match->match_score ?? 'N/A' }}%</span>
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="badge bg-info">{{ $match->created_at->format('d-m-Y H:i') }}</span>
                        </div>
                    </div>

                    <hr class="material-divider">

                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="material-section-title">Gebruiker Informatie</h6>
                            <div class="material-card">
                                <div class="card-body">
                                    <h6>{{ $match->user->first_name }} {{ $match->user->last_name }}</h6>
                                    <p class="mb-1"><strong>E-mail:</strong> {{ $match->user->email }}</p>
                                    <p class="mb-1"><strong>Bedrijf:</strong> {{ $match->user->company->name ?? 'N/A' }}</p>
                                    <p ><strong>Telefoon:</strong> {{ $match->user->phone ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="material-section-title">Vacature Informatie</h6>
                            <div class="material-card">
                                <div class="card-body">
                                    <h6>{{ $match->vacancy->title }}</h6>
                                    <p class="mb-1"><strong>Bedrijf:</strong> {{ $match->vacancy->company->name }}</p>
                                    <p class="mb-1"><strong>Locatie:</strong> {{ $match->vacancy->location ?? 'N/A' }}</p>
                                    <p class="mb-1"><strong>Type:</strong> {{ ucfirst($match->vacancy->employment_type ?? 'N/A') }}</p>
                                    <p ><strong>Salaris:</strong> {{ $match->vacancy->salary_range ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="material-divider">

                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="material-section-title">Match Details</h6>
                            <table class="material-info-table">
                                <tr>
                                    <td width="150"><strong>Match Score:</strong></td>
                                    <td>
                                        @if($match->match_score)
                                            <div class="progress" style="height: 20px;">
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
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        <span class="badge bg-{{ $match->status == 'pending' ? 'warning' : ($match->status == 'accepted' ? 'success' : ($match->status == 'rejected' ? 'danger' : 'info')) }}">
                                            {{ ucfirst(str_replace('_', ' ', $match->status)) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>AI Aanbeveling:</strong></td>
                                    <td>
                                        @if($match->ai_recommendation)
                                            <span class="badge bg-{{ $match->ai_recommendation == 'strong_match' ? 'success' : ($match->ai_recommendation == 'good_match' ? 'info' : ($match->ai_recommendation == 'moderate_match' ? 'warning' : 'danger')) }}">
                                                {{ ucfirst(str_replace('_', ' ', $match->ai_recommendation)) }}
                                            </span>
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Sollicitatiedatum:</strong></td>
                                    <td>{{ $match->application_date ? \Carbon\Carbon::parse($match->application_date)->format('d-m-Y') : 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="material-section-title">Systeem Informatie</h6>
                            <table class="material-info-table">
                                <tr>
                                    <td width="150"><strong>ID:</strong></td>
                                    <td>{{ $match->id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Aangemaakt:</strong></td>
                                    <td>{{ $match->created_at->format('d-m-Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Bijgewerkt:</strong></td>
                                    <td>{{ $match->updated_at->format('d-m-Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($match->notes)
                        <hr class="material-divider">
                        <div class="row">
                            <div class="col-md-12">
                                <h6 class="material-section-title">Notities</h6>
                                <div class="material-card">
                                    <div class="card-body">
                                        {!! nl2br(e($match->notes)) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($match->ai_analysis)
                        <hr class="material-divider">
                        <div class="row">
                            <div class="col-md-12">
                                <h6 class="material-section-title">AI Analyse</h6>
                                <div class="material-card">
                                    <div class="card-header">
                                        <small class="material-text-muted">Automatische analyse van de match door AI</small>
                                    </div>
                                    <div class="card-body">
                                        {!! nl2br(e($match->ai_analysis)) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <hr class="material-divider">
                    <div class="row">
                        <div class="col-md-12">
                            <h6 class="material-section-title">Gerelateerde Interviews</h6>
                            @if($match->interviews && $match->interviews->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Datum</th>
                                                <th>Tijd</th>
                                                <th>Type</th>
                                                <th>Status</th>
                                                <th>Acties</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($match->interviews as $interview)
                                                <tr>
                                                    <td>{{ \Carbon\Carbon::parse($interview->scheduled_at)->format('d-m-Y') }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($interview->scheduled_at)->format('H:i') }}</td>
                                                    <td>{{ ucfirst($interview->type) }}</td>
                                                    <td>
                                                        <span class="badge bg-{{ $interview->status == 'scheduled' ? 'info' : ($interview->status == 'completed' ? 'success' : 'warning') }}">
                                                            {{ ucfirst($interview->status) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('admin.interviews.show', $interview) }}" class="btn btn-sm btn-outline-info">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> Geen interviews gepland voor deze match.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
