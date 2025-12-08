@extends('admin.layouts.app')

@section('title', 'Interview Bewerken')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono mb-3">
                Interview Bewerken
            </h1>
            <div class="mt-3 flex gap-2">
                <a href="{{ route('admin.agenda.index') }}" class="kt-btn kt-btn-outline">
                    <i class="ki-filled ki-arrow-left me-2"></i>
                    Terug naar Agenda
                </a>
                <a href="{{ route('admin.interviews.index') }}" class="kt-btn kt-btn-outline">
                    <i class="ki-filled ki-arrow-left me-2"></i>
                    Terug naar Overzicht
                </a>
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.interviews.show', $interview) }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-eye me-2"></i>
                Bekijken
            </a>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="kt-container-fixed">
        <div class="kt-alert kt-alert-success mb-5 auto-dismiss" role="alert" id="success-alert">
            <i class="ki-filled ki-check-circle me-2"></i>
            {{ session('success') }}
        </div>
    </div>
@endif

@if($errors->any())
    <div class="kt-container-fixed">
        <div class="kt-alert kt-alert-danger mb-5" role="alert">
            <i class="ki-filled ki-information-2 me-2"></i>
            <strong>Er zijn fouten opgetreden:</strong>
            <ul class="mt-2 list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<div class="kt-container-fixed">
    <div class="flex flex-col items-stretch grow">
        <form action="{{ route('admin.interviews.update', $interview) }}" method="POST" class="flex flex-col gap-5 lg:gap-7.5" novalidate>
            @csrf
            @method('PUT')

            <!-- Basis Informatie -->
            <div class="kt-card min-w-full pb-2.5" id="basis-informatie">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Basis Informatie
                    </h3>
                </div>
                <div class="kt-card-content grid gap-5">
                    <div class="w-full">
                        <div class="flex items-center py-3">
                            <label class="kt-form-label flex items-center gap-1 max-w-56">
                                Match *
                            </label>
                            <select class="kt-select @error('match_id') border-destructive @enderror" 
                                    id="match_id" name="match_id" required>
                                <option value="">Selecteer match</option>
                                @foreach(\App\Models\JobMatch::with(['user', 'vacancy'])->get() as $match)
                                    <option value="{{ $match->id }}" {{ old('match_id', $interview->match_id) == $match->id ? 'selected' : '' }}>
                                        {{ trim(($match->user->first_name ?? '') . ' ' . ($match->user->middle_name ?? '') . ' ' . ($match->user->last_name ?? '')) ?: 'Onbekend' }} - {{ $match->vacancy->title }}
                                    </option>
                                @endforeach
                            </select>
                            @error('match_id')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="w-full">
                        <div class="flex items-center py-3">
                            <label class="kt-form-label flex items-center gap-1 max-w-56">
                                Type *
                            </label>
                            <select class="kt-select @error('type') border-destructive @enderror" 
                                    id="type" name="type" required>
                                <option value="">Selecteer type</option>
                                <option value="phone" {{ old('type', $interview->type) == 'phone' ? 'selected' : '' }}>Telefoon</option>
                                <option value="video" {{ old('type', $interview->type) == 'video' ? 'selected' : '' }}>Video</option>
                                <option value="onsite" {{ old('type', $interview->type) == 'onsite' ? 'selected' : '' }}>Op locatie</option>
                                <option value="assessment" {{ old('type', $interview->type) == 'assessment' ? 'selected' : '' }}>Assessment</option>
                                <option value="final" {{ old('type', $interview->type) == 'final' ? 'selected' : '' }}>Eindgesprek</option>
                            </select>
                            @error('type')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="w-full">
                        <div class="flex items-center py-3">
                            <label class="kt-form-label flex items-center gap-1 max-w-56">
                                Geplande Datum & Tijd *
                            </label>
                            <input type="datetime-local" 
                                   class="kt-input @error('scheduled_at') border-destructive @enderror" 
                                   id="scheduled_at" name="scheduled_at" 
                                   value="{{ old('scheduled_at', $interview->scheduled_at ? $interview->scheduled_at->format('Y-m-d\TH:i') : '') }}" 
                                   required>
                            @error('scheduled_at')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="w-full">
                        <div class="flex items-center py-3">
                            <label class="kt-form-label flex items-center gap-1 max-w-56">
                                Duur (minuten)
                            </label>
                            <input type="number" 
                                   class="kt-input @error('duration') border-destructive @enderror" 
                                   id="duration" name="duration" 
                                   value="{{ old('duration', $interview->duration ?? 60) }}" 
                                   min="15" max="480">
                            @error('duration')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="w-full">
                        <div class="flex items-center py-3">
                            <label class="kt-form-label flex items-center gap-1 max-w-56">
                                Status *
                            </label>
                            <select class="kt-select @error('status') border-destructive @enderror" 
                                    id="status" name="status" required>
                                <option value="">Selecteer status</option>
                                <option value="scheduled" {{ old('status', $interview->status) == 'scheduled' ? 'selected' : '' }}>Gepland</option>
                                <option value="confirmed" {{ old('status', $interview->status) == 'confirmed' ? 'selected' : '' }}>Bevestigd</option>
                                <option value="completed" {{ old('status', $interview->status) == 'completed' ? 'selected' : '' }}>Voltooid</option>
                                <option value="cancelled" {{ old('status', $interview->status) == 'cancelled' ? 'selected' : '' }}>Geannuleerd</option>
                                <option value="rescheduled" {{ old('status', $interview->status) == 'rescheduled' ? 'selected' : '' }}>Herpland</option>
                            </select>
                            @error('status')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="w-full">
                        <div class="flex items-center py-3">
                            <label class="kt-form-label flex items-center gap-1 max-w-56">
                                Locatie
                            </label>
                            <input type="text" 
                                   class="kt-input @error('location') border-destructive @enderror" 
                                   id="location" name="location" 
                                   value="{{ old('location', $interview->location) }}" 
                                   placeholder="Adres, Zoom link, etc.">
                            @error('location')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Interviewer Informatie -->
            <div class="kt-card min-w-full pb-2.5" id="interviewer-informatie">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Interviewer Informatie
                    </h3>
                </div>
                <div class="kt-card-content grid gap-5">
                    <div class="w-full">
                        <div class="flex items-center py-3">
                            <label class="kt-form-label flex items-center gap-1 max-w-56">
                                Interviewer Naam
                            </label>
                            <input type="text" 
                                   class="kt-input @error('interviewer_name') border-destructive @enderror" 
                                   id="interviewer_name" name="interviewer_name" 
                                   value="{{ old('interviewer_name', $interview->interviewer_name) }}">
                            @error('interviewer_name')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="w-full">
                        <div class="flex items-center py-3">
                            <label class="kt-form-label flex items-center gap-1 max-w-56">
                                Interviewer E-mail
                            </label>
                            <input type="email" 
                                   class="kt-input @error('interviewer_email') border-destructive @enderror" 
                                   id="interviewer_email" name="interviewer_email" 
                                   value="{{ old('interviewer_email', $interview->interviewer_email) }}">
                            @error('interviewer_email')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notities & Feedback -->
            <div class="kt-card min-w-full pb-2.5" id="notities-feedback">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Notities & Feedback
                    </h3>
                </div>
                <div class="kt-card-content grid gap-5">
                    <div class="w-full">
                        <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                            <label class="kt-form-label flex items-center gap-1 max-w-56">
                                Notities
                            </label>
                            <div class="flex-1">
                                <textarea class="kt-input pt-1 @error('notes') border-destructive @enderror" 
                                          id="notes" name="notes" rows="4">{{ old('notes', $interview->notes) }}</textarea>
                                @error('notes')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="w-full">
                        <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                            <label class="kt-form-label flex items-center gap-1 max-w-56">
                                Feedback
                            </label>
                            <div class="flex-1">
                                <textarea class="kt-input pt-1 @error('feedback') border-destructive @enderror" 
                                          id="feedback" name="feedback" rows="4">{{ old('feedback', $interview->feedback) }}</textarea>
                                @error('feedback')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                                <div class="text-xs text-muted-foreground mt-1">Feedback na het interview</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acties -->
            <div class="flex items-center justify-end gap-2.5">
                <a href="{{ route('admin.interviews.index') }}" class="kt-btn kt-btn-outline">
                    Annuleren
                </a>
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-check me-2"></i>
                    Wijzigingen Opslaan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
