@extends('admin.layouts.app')

@section('title', 'Match Bewerken')

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
                        <i class="fas fa-handshake"></i> Match Bewerken
                    </h5>
                    <div>
                        <a href="{{ route('admin.matches.show', $match) }}" class="btn btn-info me-2">
                            <i class="fas fa-eye"></i> Bekijken
                        </a>
                        <a href="{{ route('admin.matches.index') }}" class="material-btn material-btn-secondary">
                            <i class="fas fa-arrow-left"></i> Terug naar Overzicht
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="material-alert material-alert-danger">
                            <ul >
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.matches.update', $match) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="user_id" class="material-form-label">Gebruiker *</label>
                                    <select class="material-form-select @error('user_id') is-invalid @enderror" 
                                            id="user_id" name="user_id" required>
                                        <option value="">Selecteer gebruiker</option>
                                        @foreach(\App\Models\User::all() as $user)
                                            <option value="{{ $user->id }}" {{ old('user_id', $match->user_id) == $user->id ? 'selected' : '' }}>
                                                {{ $user->first_name }} {{ $user->last_name }} ({{ $user->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('user_id')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="vacancy_id" class="material-form-label">Vacature *</label>
                                    <select class="material-form-select @error('vacancy_id') is-invalid @enderror" 
                                            id="vacancy_id" name="vacancy_id" required>
                                        <option value="">Selecteer vacature</option>
                                        @foreach(\App\Models\Vacancy::all() as $vacancy)
                                            <option value="{{ $vacancy->id }}" {{ old('vacancy_id', $match->vacancy_id) == $vacancy->id ? 'selected' : '' }}>
                                                {{ $vacancy->title }} - {{ $vacancy->company->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('vacancy_id')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="match_score" class="material-form-label">Match Score (%)</label>
                                    <input type="number" class="material-form-control @error('match_score') is-invalid @enderror" 
                                           id="match_score" name="match_score" value="{{ old('match_score', $match->match_score) }}" 
                                           min="0" max="100" step="0.1">
                                    @error('match_score')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="status" class="material-form-label">Status *</label>
                                    <select class="material-form-select @error('status') is-invalid @enderror" 
                                            id="status" name="status" required>
                                        <option value="">Selecteer status</option>
                                        <option value="pending" {{ old('status', $match->status) == 'pending' ? 'selected' : '' }}>In afwachting</option>
                                        <option value="accepted" {{ old('status', $match->status) == 'accepted' ? 'selected' : '' }}>Geaccepteerd</option>
                                        <option value="rejected" {{ old('status', $match->status) == 'rejected' ? 'selected' : '' }}>Afgewezen</option>
                                        <option value="interview_scheduled" {{ old('status', $match->status) == 'interview_scheduled' ? 'selected' : '' }}>Interview gepland</option>
                                        <option value="hired" {{ old('status', $match->status) == 'hired' ? 'selected' : '' }}>Aangenomen</option>
                                    </select>
                                    @error('status')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="ai_recommendation" class="material-form-label">AI Aanbeveling</label>
                                    <select class="material-form-select @error('ai_recommendation') is-invalid @enderror" 
                                            id="ai_recommendation" name="ai_recommendation">
                                        <option value="">Selecteer aanbeveling</option>
                                        <option value="strong_match" {{ old('ai_recommendation', $match->ai_recommendation) == 'strong_match' ? 'selected' : '' }}>Sterke match</option>
                                        <option value="good_match" {{ old('ai_recommendation', $match->ai_recommendation) == 'good_match' ? 'selected' : '' }}>Goede match</option>
                                        <option value="moderate_match" {{ old('ai_recommendation', $match->ai_recommendation) == 'moderate_match' ? 'selected' : '' }}>Matige match</option>
                                        <option value="weak_match" {{ old('ai_recommendation', $match->ai_recommendation) == 'weak_match' ? 'selected' : '' }}>Zwakke match</option>
                                        <option value="not_recommended" {{ old('ai_recommendation', $match->ai_recommendation) == 'not_recommended' ? 'selected' : '' }}>Niet aanbevolen</option>
                                    </select>
                                    @error('ai_recommendation')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="application_date" class="material-form-label">Sollicitatiedatum</label>
                                    <input type="date" class="material-form-control @error('application_date') is-invalid @enderror" 
                                           id="application_date" name="application_date" value="{{ old('application_date', $match->application_date) }}">
                                    @error('application_date')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="material-form-group">
                                    <label for="notes" class="material-form-label">Notities</label>
                                    <textarea class="material-form-control @error('notes') is-invalid @enderror" 
                                              id="notes" name="notes" rows="4">{{ old('notes', $match->notes) }}</textarea>
                                    @error('notes')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="material-form-group">
                                    <label for="ai_analysis" class="material-form-label">AI Analyse</label>
                                    <textarea class="material-form-control @error('ai_analysis') is-invalid @enderror" 
                                              id="ai_analysis" name="ai_analysis" rows="6">{{ old('ai_analysis', $match->ai_analysis) }}</textarea>
                                    @error('ai_analysis')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="material-text-muted">Automatische analyse van de match door AI</small>
                                </div>
                            </div>
                        </div>

                        <div class="material-form-actions">
                            <a href="{{ route('admin.matches.index') }}" class="btn btn-secondary me-2">Annuleren</a>
                            <button type="submit" class="material-btn material-btn-primary">
                                <i class="fas fa-save"></i> Wijzigingen Opslaan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
