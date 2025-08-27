@extends('admin.layouts.app')

@section('title', 'E-mail Sjabloon Bewerken')

@section('content')
<style>
    :root {
        --primary-color: #009688;
        --primary-light: #4db6ac;
        --primary-dark: #00695c;
        --primary-hover: #26a69a;
    }
</style>

@include('admin.material-design-template')


<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="material-card">
                <div class="card-header">
                    <h5 >
                        <i class="fas fa-envelope"></i> E-mail Sjabloon Bewerken
                    </h5>
                    <div>
                        <a href="{{ route('admin.email-templates.show', $emailTemplate) }}" class="btn btn-info me-2">
                            <i class="fas fa-eye"></i> Bekijken
                        </a>
                        <a href="{{ route('admin.email-templates.index') }}" class="material-btn material-btn-secondary">
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

                    <form action="{{ route('admin.email-templates.update', $emailTemplate) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="name" class="material-form-label">Naam *</label>
                                    <input type="text" class="material-form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name', $emailTemplate->name) }}" required>
                                    @error('name')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="subject" class="material-form-label">Onderwerp *</label>
                                    <input type="text" class="material-form-control @error('subject') is-invalid @enderror" 
                                           id="subject" name="subject" value="{{ old('subject', $emailTemplate->subject) }}" required>
                                    @error('subject')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="type" class="material-form-label">Type *</label>
                                    <select class="material-form-select @error('type') is-invalid @enderror" 
                                            id="type" name="type" required>
                                        <option value="">Selecteer type</option>
                                        <option value="welcome" {{ old('type', $emailTemplate->type) == 'welcome' ? 'selected' : '' }}>Welkom</option>
                                        <option value="password_reset" {{ old('type', $emailTemplate->type) == 'password_reset' ? 'selected' : '' }}>Wachtwoord Reset</option>
                                        <option value="email_verification" {{ old('type', $emailTemplate->type) == 'email_verification' ? 'selected' : '' }}>E-mail Verificatie</option>
                                        <option value="match_notification" {{ old('type', $emailTemplate->type) == 'match_notification' ? 'selected' : '' }}>Match Notificatie</option>
                                        <option value="interview_invitation" {{ old('type', $emailTemplate->type) == 'interview_invitation' ? 'selected' : '' }}>Interview Uitnodiging</option>
                                        <option value="application_received" {{ old('type', $emailTemplate->type) == 'application_received' ? 'selected' : '' }}>Sollicitatie Ontvangen</option>
                                        <option value="application_status" {{ old('type', $emailTemplate->type) == 'application_status' ? 'selected' : '' }}>Sollicitatie Status</option>
                                        <option value="custom" {{ old('type', $emailTemplate->type) == 'custom' ? 'selected' : '' }}>Aangepast</option>
                                    </select>
                                    @error('type')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="is_active" class="material-form-label">Status</label>
                                    <select class="material-form-select @error('is_active') is-invalid @enderror" 
                                            id="is_active" name="is_active">
                                        <option value="1" {{ old('is_active', $emailTemplate->is_active) == '1' ? 'selected' : '' }}>Actief</option>
                                        <option value="0" {{ old('is_active', $emailTemplate->is_active) == '0' ? 'selected' : '' }}>Inactief</option>
                                    </select>
                                    @error('is_active')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="material-form-group">
                                    <label for="html_content" class="material-form-label">HTML Inhoud *</label>
                                    <textarea class="material-form-control @error('html_content') is-invalid @enderror" 
                                              id="html_content" name="html_content" rows="12" required>{{ old('html_content', $emailTemplate->html_content) }}</textarea>
                                    @error('html_content')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="material-text-muted">
                                        Beschikbare variabelen: 
                                        @foreach($templateVariables as $variable => $description)
                                            <code>{{ $variable }}</code> ({{ $description }})@if(!$loop->last), @endif
                                        @endforeach
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="material-form-group">
                                    <label for="text_content" class="material-form-label">Tekst Inhoud (Plain Text)</label>
                                    <textarea class="material-form-control @error('text_content') is-invalid @enderror" 
                                              id="text_content" name="text_content" rows="8">{{ old('text_content', $emailTemplate->text_content) }}</textarea>
                                    @error('text_content')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="material-text-muted">Tekstversie voor e-mail clients die geen HTML ondersteunen</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="material-form-group">
                                    <label for="description" class="material-form-label">Beschrijving</label>
                                    <textarea class="material-form-control @error('description') is-invalid @enderror" 
                                              id="description" name="description" rows="3">{{ old('description', $emailTemplate->description) }}</textarea>
                                    @error('description')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="material-form-actions">
                            <a href="{{ route('admin.email-templates.index') }}" class="btn btn-secondary me-2">Annuleren</a>
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
