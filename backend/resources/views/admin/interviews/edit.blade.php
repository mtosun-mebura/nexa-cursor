@extends('admin.layouts.app')

@section('title', 'Interview Bewerken')

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
                        <i class="fas fa-calendar-alt"></i> Interview Bewerken
                    </h5>
                    <div>
                        <a href="{{ route('admin.interviews.show', $interview) }}" class="btn btn-info me-2">
                            <i class="fas fa-eye"></i> Bekijken
                        </a>
                        <a href="{{ route('admin.interviews.index') }}" class="material-btn material-btn-secondary">
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

                    <form action="{{ route('admin.interviews.update', $interview) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="match_id" class="material-form-label">Match *</label>
                                    <select class="material-form-select @error('match_id') is-invalid @enderror" 
                                            id="match_id" name="match_id" required>
                                        <option value="">Selecteer match</option>
                                        @foreach(\App\Models\JobMatch::with(['user', 'vacancy'])->get() as $match)
                                            <option value="{{ $match->id }}" {{ old('match_id', $interview->match_id) == $match->id ? 'selected' : '' }}>
                                                {{ $match->user->first_name }} {{ $match->user->last_name }} - {{ $match->vacancy->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('match_id')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="type" class="material-form-label">Type *</label>
                                    <select class="material-form-select @error('type') is-invalid @enderror" 
                                            id="type" name="type" required>
                                        <option value="">Selecteer type</option>
                                        <option value="phone" {{ old('type', $interview->type) == 'phone' ? 'selected' : '' }}>Telefoon</option>
                                        <option value="video" {{ old('type', $interview->type) == 'video' ? 'selected' : '' }}>Video</option>
                                        <option value="onsite" {{ old('type', $interview->type) == 'onsite' ? 'selected' : '' }}>Op locatie</option>
                                        <option value="assessment" {{ old('type', $interview->type) == 'assessment' ? 'selected' : '' }}>Assessment</option>
                                        <option value="final" {{ old('type', $interview->type) == 'final' ? 'selected' : '' }}>Eindgesprek</option>
                                    </select>
                                    @error('type')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="scheduled_at" class="material-form-label">Geplande Datum & Tijd *</label>
                                    <input type="datetime-local" class="material-form-control @error('scheduled_at') is-invalid @enderror" 
                                           id="scheduled_at" name="scheduled_at" 
                                           value="{{ old('scheduled_at', $interview->scheduled_at ? $interview->scheduled_at->format('Y-m-d\TH:i') : '') }}" required>
                                    @error('scheduled_at')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="duration" class="material-form-label">Duur (minuten)</label>
                                    <input type="number" class="material-form-control @error('duration') is-invalid @enderror" 
                                           id="duration" name="duration" value="{{ old('duration', $interview->duration ?? 60) }}" 
                                           min="15" max="480">
                                    @error('duration')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="status" class="material-form-label">Status *</label>
                                    <select class="material-form-select @error('status') is-invalid @enderror" 
                                            id="status" name="status" required>
                                        <option value="">Selecteer status</option>
                                        <option value="scheduled" {{ old('status', $interview->status) == 'scheduled' ? 'selected' : '' }}>Gepland</option>
                                        <option value="confirmed" {{ old('status', $interview->status) == 'confirmed' ? 'selected' : '' }}>Bevestigd</option>
                                        <option value="completed" {{ old('status', $interview->status) == 'completed' ? 'selected' : '' }}>Voltooid</option>
                                        <option value="cancelled" {{ old('status', $interview->status) == 'cancelled' ? 'selected' : '' }}>Geannuleerd</option>
                                        <option value="rescheduled" {{ old('status', $interview->status) == 'rescheduled' ? 'selected' : '' }}>Herpland</option>
                                    </select>
                                    @error('status')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="location" class="material-form-label">Locatie</label>
                                    <input type="text" class="material-form-control @error('location') is-invalid @enderror" 
                                           id="location" name="location" value="{{ old('location', $interview->location) }}" 
                                           placeholder="Adres, Zoom link, etc.">
                                    @error('location')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="interviewer_name" class="material-form-label">Interviewer Naam</label>
                                    <input type="text" class="material-form-control @error('interviewer_name') is-invalid @enderror" 
                                           id="interviewer_name" name="interviewer_name" value="{{ old('interviewer_name', $interview->interviewer_name) }}">
                                    @error('interviewer_name')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="interviewer_email" class="material-form-label">Interviewer E-mail</label>
                                    <input type="email" class="material-form-control @error('interviewer_email') is-invalid @enderror" 
                                           id="interviewer_email" name="interviewer_email" value="{{ old('interviewer_email', $interview->interviewer_email) }}">
                                    @error('interviewer_email')
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
                                              id="notes" name="notes" rows="4">{{ old('notes', $interview->notes) }}</textarea>
                                    @error('notes')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="material-form-group">
                                    <label for="feedback" class="material-form-label">Feedback</label>
                                    <textarea class="material-form-control @error('feedback') is-invalid @enderror" 
                                              id="feedback" name="feedback" rows="6">{{ old('feedback', $interview->feedback) }}</textarea>
                                    @error('feedback')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="material-text-muted">Feedback na het interview</small>
                                </div>
                            </div>
                        </div>

                        <div class="material-form-actions">
                            <a href="{{ route('admin.interviews.index') }}" class="btn btn-secondary me-2">Annuleren</a>
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
