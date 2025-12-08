@extends('admin.layouts.app')

@section('title', 'Nieuw Interview')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono mb-3">
                Nieuw Interview
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
        <form action="{{ route('admin.interviews.store') }}" method="POST" class="flex flex-col gap-5 lg:gap-7.5" novalidate>
            @csrf

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
                                Match <span class="text-destructive">*</span>
                            </label>
                            <select id="match_id" 
                                    name="match_id" 
                                    class="kt-select @error('match_id') border-destructive @enderror" 
                                    data-kt-select="true"
                                    required>
                                <option value="">Selecteer match</option>
                                @foreach($matches ?? [] as $match)
                                    <option value="{{ $match->id }}" {{ old('match_id') == $match->id ? 'selected' : '' }}>
                                        {{ $match->user->first_name ?? '' }} {{ $match->user->last_name ?? '' }} - {{ $match->vacancy->title ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('match_id')
                                <span class="text-xs text-destructive mt-1">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    @if(auth()->user()->hasRole('super-admin'))
                    <div class="w-full">
                        <div class="flex items-center py-3">
                            <label class="kt-form-label flex items-center gap-1 max-w-56">
                                Bedrijf <span class="text-destructive">*</span>
                            </label>
                            <select id="company_id" 
                                    name="company_id" 
                                    class="kt-select @error('company_id') border-destructive @enderror" 
                                    data-kt-select="true"
                                    required>
                                <option value="">Selecteer bedrijf</option>
                                @foreach($companies ?? [] as $company)
                                    <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                        {{ $company->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('company_id')
                                <span class="text-xs text-destructive mt-1">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    @else
                        <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
                    @endif

                    <div class="w-full">
                        <div class="flex items-center py-3">
                            <label class="kt-form-label flex items-center gap-1 max-w-56">
                                Type <span class="text-destructive">*</span>
                            </label>
                            <select id="type" 
                                    name="type" 
                                    class="kt-select @error('type') border-destructive @enderror" 
                                    data-kt-select="true"
                                    required>
                                <option value="">Selecteer type</option>
                                <option value="phone" {{ old('type') == 'phone' ? 'selected' : '' }}>Telefoon</option>
                                <option value="video" {{ old('type') == 'video' ? 'selected' : '' }}>Video</option>
                                <option value="onsite" {{ old('type') == 'onsite' ? 'selected' : '' }}>Op locatie</option>
                                <option value="assessment" {{ old('type') == 'assessment' ? 'selected' : '' }}>Assessment</option>
                                <option value="final" {{ old('type') == 'final' ? 'selected' : '' }}>Eindgesprek</option>
                            </select>
                            @error('type')
                                <span class="text-xs text-destructive mt-1">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="w-full">
                        <div class="flex items-center py-3">
                            <label class="kt-form-label flex items-center gap-1 max-w-56">
                                Geplande Datum & Tijd <span class="text-destructive">*</span>
                            </label>
                            <input type="datetime-local" 
                                   class="kt-input @error('scheduled_at') border-destructive @enderror" 
                                   id="scheduled_at" name="scheduled_at" 
                                   value="{{ old('scheduled_at') }}" 
                                   required>
                            @error('scheduled_at')
                                <span class="text-xs text-destructive mt-1">{{ $message }}</span>
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
                                   value="{{ old('duration', 60) }}" 
                                   min="15" max="480">
                            @error('duration')
                                <span class="text-xs text-destructive mt-1">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="w-full">
                        <div class="flex items-center py-3">
                            <label class="kt-form-label flex items-center gap-1 max-w-56">
                                Status <span class="text-destructive">*</span>
                            </label>
                            <select id="status" 
                                    name="status" 
                                    class="kt-select @error('status') border-destructive @enderror" 
                                    data-kt-select="true"
                                    required>
                                <option value="">Selecteer status</option>
                                <option value="scheduled" {{ old('status') == 'scheduled' ? 'selected' : '' }}>Gepland</option>
                                <option value="confirmed" {{ old('status') == 'confirmed' ? 'selected' : '' }}>Bevestigd</option>
                                <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Voltooid</option>
                                <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Geannuleerd</option>
                                <option value="rescheduled" {{ old('status') == 'rescheduled' ? 'selected' : '' }}>Herpland</option>
                            </select>
                            @error('status')
                                <span class="text-xs text-destructive mt-1">{{ $message }}</span>
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
                                   value="{{ old('location') }}" 
                                   placeholder="Adres, Zoom link, etc.">
                            @error('location')
                                <span class="text-xs text-destructive mt-1">{{ $message }}</span>
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
                                   value="{{ old('interviewer_name') }}">
                            @error('interviewer_name')
                                <span class="text-xs text-destructive mt-1">{{ $message }}</span>
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
                                   value="{{ old('interviewer_email') }}">
                            @error('interviewer_email')
                                <span class="text-xs text-destructive mt-1">{{ $message }}</span>
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
                                          id="notes" name="notes" rows="4">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <span class="text-xs text-destructive mt-1">{{ $message }}</span>
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
                                          id="feedback" name="feedback" rows="4">{{ old('feedback') }}</textarea>
                                @error('feedback')
                                    <span class="text-xs text-destructive mt-1">{{ $message }}</span>
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
                    Interview Opslaan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
