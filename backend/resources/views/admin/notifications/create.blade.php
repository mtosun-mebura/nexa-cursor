@extends('admin.layouts.app')

@section('title', 'Nieuwe Notificatie')

@section('content')
<style>
    :root {
        --primary-color: #ff6b6b;
        --primary-light: #ee5a24;
        --primary-dark: #e74c3c;
        --primary-hover: #ff5252;
    }
</style>

@include('admin.material-design-template')


<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="material-card">
                <div class="card-header">
                    <h5 >
                        <i class="fas fa-bell"></i> Nieuwe Notificatie
                    </h5>
                    <a href="{{ route('admin.notifications.index') }}" class="material-btn material-btn-secondary">
                        <i class="fas fa-arrow-left"></i> Terug naar Overzicht
                    </a>
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

                    <form action="{{ route('admin.notifications.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="user_id" class="material-form-label">Gebruiker *</label>
                                    <select class="material-form-select @error('user_id') is-invalid @enderror" 
                                            id="user_id" name="user_id" required>
                                        <option value="">Selecteer gebruiker</option>
                                        @if(auth()->user()->hasRole('super-admin'))
                                            @php
                                                $selectedTenant = session('selected_tenant');
                                                $users = $selectedTenant 
                                                    ? \App\Models\User::where('company_id', $selectedTenant)->where('id', '!=', auth()->id())->get()
                                                    : \App\Models\User::where('id', '!=', auth()->id())->get();
                                            @endphp
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                                    {{ $user->first_name }} {{ $user->last_name }} ({{ $user->email }})
                                                </option>
                                            @endforeach
                                        @else
                                            @foreach(\App\Models\User::where('company_id', auth()->user()->company_id)->where('id', '!=', auth()->id())->get() as $user)
                                                <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                                    {{ $user->first_name }} {{ $user->last_name }} ({{ $user->email }})
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @error('user_id')
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
                                        <option value="match" {{ old('type') == 'match' ? 'selected' : '' }}>Match</option>
                                        <option value="interview" {{ old('type') == 'interview' ? 'selected' : '' }}>Interview</option>
                                        <option value="application" {{ old('type') == 'application' ? 'selected' : '' }}>Sollicitatie</option>
                                        <option value="system" {{ old('type') == 'system' ? 'selected' : '' }}>Systeem</option>
                                        <option value="email" {{ old('type') == 'email' ? 'selected' : '' }}>E-mail</option>
                                        <option value="reminder" {{ old('type') == 'reminder' ? 'selected' : '' }}>Herinnering</option>
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
                                    <label for="title" class="material-form-label">Titel *</label>
                                    <input type="text" class="material-form-control @error('title') is-invalid @enderror" 
                                           id="title" name="title" value="{{ old('title') }}" required>
                                    @error('title')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="priority" class="material-form-label">Prioriteit</label>
                                    <select class="material-form-select @error('priority') is-invalid @enderror" 
                                            id="priority" name="priority">
                                        <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Laag</option>
                                        <option value="normal" {{ old('priority', 'normal') == 'normal' ? 'selected' : '' }}>Normaal</option>
                                        <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>Hoog</option>
                                        <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                                    </select>
                                    @error('priority')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="material-form-group">
                                    <label for="message" class="material-form-label">Bericht *</label>
                                    <textarea class="material-form-control @error('message') is-invalid @enderror" 
                                              id="message" name="message" rows="4" required>{{ old('message') }}</textarea>
                                    @error('message')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="read_at" class="material-form-label">Gelezen op</label>
                                    <input type="datetime-local" class="material-form-control @error('read_at') is-invalid @enderror" 
                                           id="read_at" name="read_at" value="{{ old('read_at') }}">
                                    @error('read_at')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="material-text-muted">Laat leeg als nog niet gelezen</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="action_url" class="material-form-label">Actie URL</label>
                                    <input type="url" class="material-form-control @error('action_url') is-invalid @enderror" 
                                           id="action_url" name="action_url" value="{{ old('action_url') }}" 
                                           placeholder="https://example.com/action">
                                    @error('action_url')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="material-text-muted">URL waar de gebruiker naartoe wordt geleid bij klikken</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="data" class="material-form-label">Extra Data (JSON)</label>
                                    <textarea class="material-form-control @error('data') is-invalid @enderror" 
                                              id="data" name="data" rows="3" 
                                              placeholder='{"key": "value", "match_id": 123}'>{{ old('data') }}</textarea>
                                    @error('data')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="material-text-muted">Optionele JSON data voor extra informatie</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="scheduled_at" class="material-form-label">Gepland voor</label>
                                    <input type="datetime-local" class="material-form-control @error('scheduled_at') is-invalid @enderror" 
                                           id="scheduled_at" name="scheduled_at" value="{{ old('scheduled_at') }}">
                                    @error('scheduled_at')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="material-text-muted">Laat leeg voor directe verzending</small>
                                </div>
                            </div>
                        </div>

                        <div class="material-form-actions">
                            <a href="{{ route('admin.notifications.index') }}" class="material-btn material-btn-secondary">Annuleren</a>
                            <button type="submit" class="material-btn material-btn-primary">
                                <i class="fas fa-save"></i> Notificatie Opslaan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
