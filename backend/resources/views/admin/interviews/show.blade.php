@extends('admin.layouts.app')

@section('title', 'Interview Details')

@section('content')
<style>
    :root {
        --primary-color: #607d8b;
        --primary-light: #90a4ae;
        --primary-dark: #455a64;
        --primary-hover: #78909c;
    }
</style>

@include('admin.material-design-template')


<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="material-card">
                <div class="card-header">
                    <h5 >
                        <i class="fas fa-calendar-alt"></i> Interview Details
                    </h5>
                    <div>
                        <a href="{{ route('admin.interviews.edit', $interview) }}" class="btn btn-warning me-2">
                            <i class="fas fa-edit"></i> Bewerken
                        </a>
                        <a href="{{ route('admin.interviews.index') }}" class="material-btn material-btn-secondary">
                            <i class="fas fa-arrow-left"></i> Terug naar Overzicht
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h4>Interview #{{ $interview->id }}</h4>
                            <p class="material-section-title">
                                <span class="badge bg-{{ $interview->status == 'scheduled' ? 'info' : ($interview->status == 'completed' ? 'success' : ($interview->status == 'cancelled' ? 'danger' : 'warning')) }}">
                                    {{ ucfirst($interview->status) }}
                                </span>
                                <span class="ms-2">{{ ucfirst($interview->type) }} Interview</span>
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="badge bg-info">{{ $interview->scheduled_at->format('d-m-Y H:i') }}</span>
                        </div>
                    </div>

                    <hr class="material-divider">

                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="material-section-title">Kandidaat Informatie</h6>
                            <div class="material-card">
                                <div class="card-body">
                                    <h6>{{ $interview->match->user->first_name }} {{ $interview->match->user->last_name }}</h6>
                                    <p class="mb-1"><strong>E-mail:</strong> {{ $interview->match->user->email }}</p>
                                    <p class="mb-1"><strong>Telefoon:</strong> {{ $interview->match->user->phone ?? 'N/A' }}</p>
                                    <p ><strong>Bedrijf:</strong> {{ $interview->match->user->company->name ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="material-section-title">Vacature Informatie</h6>
                            <div class="material-card">
                                <div class="card-body">
                                    <h6>{{ $interview->match->vacancy->title }}</h6>
                                    <p class="mb-1"><strong>Bedrijf:</strong> {{ $interview->match->vacancy->company->name }}</p>
                                    <p class="mb-1"><strong>Locatie:</strong> {{ $interview->match->vacancy->location ?? 'N/A' }}</p>
                                    <p ><strong>Match Score:</strong> {{ $interview->match->match_score ?? 'N/A' }}%</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="material-divider">

                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="material-section-title">Interview Details</h6>
                            <table class="material-info-table">
                                <tr>
                                    <td width="150"><strong>Type:</strong></td>
                                    <td>{{ ucfirst($interview->type) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        <span class="badge bg-{{ $interview->status == 'scheduled' ? 'info' : ($interview->status == 'completed' ? 'success' : ($interview->status == 'cancelled' ? 'danger' : 'warning')) }}">
                                            {{ ucfirst($interview->status) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Datum & Tijd:</strong></td>
                                    <td>{{ $interview->scheduled_at->format('d-m-Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Duur:</strong></td>
                                    <td>{{ $interview->duration ?? 'N/A' }} minuten</td>
                                </tr>
                                <tr>
                                    <td><strong>Locatie:</strong></td>
                                    <td>{{ $interview->location ?? 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="material-section-title">Interviewer Informatie</h6>
                            <table class="material-info-table">
                                <tr>
                                    <td width="150"><strong>Naam:</strong></td>
                                    <td>{{ $interview->interviewer_name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>E-mail:</strong></td>
                                    <td>{{ $interview->interviewer_email ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>ID:</strong></td>
                                    <td>{{ $interview->id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Aangemaakt:</strong></td>
                                    <td>{{ $interview->created_at->format('d-m-Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Bijgewerkt:</strong></td>
                                    <td>{{ $interview->updated_at->format('d-m-Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($interview->notes)
                        <hr class="material-divider">
                        <div class="row">
                            <div class="col-md-12">
                                <h6 class="material-section-title">Notities</h6>
                                <div class="material-card">
                                    <div class="card-body">
                                        {!! nl2br(e($interview->notes)) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($interview->feedback)
                        <hr class="material-divider">
                        <div class="row">
                            <div class="col-md-12">
                                <h6 class="material-section-title">Feedback</h6>
                                <div class="material-card">
                                    <div class="card-header">
                                        <small class="material-text-muted">Feedback na het interview</small>
                                    </div>
                                    <div class="card-body">
                                        {!! nl2br(e($interview->feedback)) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <hr class="material-divider">
                    <div class="row">
                        <div class="col-md-12">
                            <h6 class="material-section-title">Match Informatie</h6>
                            <div class="material-card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <strong>Match Score:</strong>
                                            @if($interview->match->match_score)
                                                <div class="progress mt-1" style="height: 20px;">
                                                    <div class="progress-bar bg-{{ $interview->match->match_score >= 80 ? 'success' : ($interview->match->match_score >= 60 ? 'warning' : 'danger') }}" 
                                                         style="width: {{ $interview->match->match_score }}%">
                                                        {{ $interview->match->match_score }}%
                                                    </div>
                                                </div>
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Match Status:</strong><br>
                                            <span class="badge bg-{{ $interview->match->status == 'pending' ? 'warning' : ($interview->match->status == 'accepted' ? 'success' : ($interview->match->status == 'rejected' ? 'danger' : 'info')) }}">
                                                {{ ucfirst(str_replace('_', ' ', $interview->match->status)) }}
                                            </span>
                                        </div>
                                        <div class="col-md-4">
                                            <strong>AI Aanbeveling:</strong><br>
                                            @if($interview->match->ai_recommendation)
                                                <span class="badge bg-{{ $interview->match->ai_recommendation == 'strong_match' ? 'success' : ($interview->match->ai_recommendation == 'good_match' ? 'info' : ($interview->match->ai_recommendation == 'moderate_match' ? 'warning' : 'danger')) }}">
                                                    {{ ucfirst(str_replace('_', ' ', $interview->match->ai_recommendation)) }}
                                                </span>
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
