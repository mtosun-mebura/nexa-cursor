@extends('admin.layouts.app')

@section('title', 'Match Bewerken')

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-col gap-5 pb-7.5">
        <div class="flex flex-wrap items-center justify-between gap-5">
            <h1 class="text-xl font-medium leading-none text-mono">
                Match Bewerken
            </h1>
        </div>
        <div class="flex items-center">
            <a href="{{ route('admin.skillmatching.matches.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
    </div>

    <form action="{{ route('admin.skillmatching.matches.update', $match) }}" method="POST" data-validate="true">
        @csrf
        @method('PUT')

        <div class="grid gap-5 lg:gap-7.5">
            <x-error-card :errors="$errors" />

            <!-- Basis Informatie -->
            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Basis Informatie
                    </h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">
                                Kandidaat *
                            </td>
                            <td class="min-w-48 w-full">
                                <select class="kt-input @error('candidate_id') border-destructive @enderror" 
                                        name="candidate_id" 
                                        id="candidate_id"
                                        required>
                                    <option value="">-- Selecteer kandidaat --</option>
                                    @foreach(\App\Models\Candidate::orderBy('first_name')->orderBy('last_name')->get() as $candidate)
                                        <option value="{{ $candidate->id }}" {{ old('candidate_id', $match->candidate_id) == $candidate->id ? 'selected' : '' }}>
                                            {{ $candidate->first_name }} {{ $candidate->last_name }} (K) ({{ $candidate->email }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('candidate_id')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Vacature *
                            </td>
                            <td>
                                <select class="kt-input @error('vacancy_id') border-destructive @enderror" 
                                        name="vacancy_id" 
                                        id="vacancy_id"
                                        required>
                                    <option value="">-- Selecteer vacature --</option>
                                    @foreach(\App\Models\Vacancy::all() as $vacancy)
                                        <option value="{{ $vacancy->id }}" {{ old('vacancy_id', $match->vacancy_id) == $vacancy->id ? 'selected' : '' }}>
                                            {{ $vacancy->title }} - {{ $vacancy->company->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('vacancy_id')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Match Score (%)
                            </td>
                            <td class="text-foreground font-normal">
                                {{ $match->match_score ?? '-' }}
                                <input type="hidden" name="match_score" value="{{ old('match_score', $match->match_score) }}">
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Status *
                            </td>
                            <td>
                                <select class="kt-input @error('status') border-destructive @enderror" 
                                        name="status" 
                                        id="status"
                                        required>
                                    <option value="">-- Selecteer status --</option>
                                    <option value="pending" {{ old('status', $match->status) == 'pending' ? 'selected' : '' }}>In afwachting</option>
                                    <option value="accepted" {{ old('status', $match->status) == 'accepted' ? 'selected' : '' }}>Geaccepteerd</option>
                                    <option value="rejected" {{ old('status', $match->status) == 'rejected' ? 'selected' : '' }}>Afgewezen</option>
                                    <option value="interview_scheduled" {{ old('status', $match->status) == 'interview_scheduled' ? 'selected' : '' }}>Interview gepland</option>
                                    <option value="hired" {{ old('status', $match->status) == 'hired' ? 'selected' : '' }}>Aangenomen</option>
                                </select>
                                @error('status')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                AI Aanbeveling
                            </td>
                            <td>
                                <select class="kt-input @error('ai_recommendation') border-destructive @enderror" 
                                        name="ai_recommendation" 
                                        id="ai_recommendation">
                                    <option value="">-- Selecteer aanbeveling --</option>
                                    <option value="strong_match" {{ old('ai_recommendation', $match->ai_recommendation) == 'strong_match' ? 'selected' : '' }}>Sterke match</option>
                                    <option value="good_match" {{ old('ai_recommendation', $match->ai_recommendation) == 'good_match' ? 'selected' : '' }}>Goede match</option>
                                    <option value="moderate_match" {{ old('ai_recommendation', $match->ai_recommendation) == 'moderate_match' ? 'selected' : '' }}>Matige match</option>
                                    <option value="weak_match" {{ old('ai_recommendation', $match->ai_recommendation) == 'weak_match' ? 'selected' : '' }}>Zwakke match</option>
                                    <option value="not_recommended" {{ old('ai_recommendation', $match->ai_recommendation) == 'not_recommended' ? 'selected' : '' }}>Niet aanbevolen</option>
                                </select>
                                @error('ai_recommendation')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Sollicitatiedatum
                            </td>
                            <td>
                                <div class="kt-input @error('application_date') border-destructive @enderror">
                                    <i class="ki-outline ki-calendar"></i>
                                    <input class="grow" 
                                           name="application_date" 
                                           id="application_date"
                                           value="{{ old('application_date', $match->application_date ? $match->application_date->format('d-m-Y') : '') }}"
                                           data-kt-date-picker="true" 
                                           data-kt-date-picker-input-mode="true" 
                                           data-kt-date-picker-position-to-input="left"
                                           data-kt-date-picker-format="dd-MM-yyyy"
                                           placeholder="Selecteer datum" 
                                           readonly 
                                           type="text"/>
                                </div>
                                @error('application_date')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Notities -->
            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Notities
                    </h3>
                </div>
                <div class="kt-card-content">
                    <div class="flex flex-col gap-2.5">
                        <label for="notes" class="kt-form-label">Notities</label>
                        <textarea class="kt-input @error('notes') border-destructive @enderror pt-1" 
                                  id="notes" 
                                  name="notes" 
                                  rows="4">{{ old('notes', $match->notes) }}</textarea>
                        @error('notes')
                            <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- AI Analyse -->
            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        AI Analyse
                    </h3>
                </div>
                <div class="kt-card-content">
                    <div class="flex flex-col gap-2.5">
                        <label for="ai_analysis" class="kt-form-label">AI Analyse</label>
                        <textarea class="kt-input @error('ai_analysis') border-destructive @enderror pt-1" 
                                  id="ai_analysis" 
                                  name="ai_analysis" 
                                  rows="4">{{ old('ai_analysis', $match->ai_analysis) }}</textarea>
                        @error('ai_analysis')
                            <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                        @enderror
                        <small class="text-xs text-muted-foreground">Automatische analyse van de match door AI</small>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-2.5">
                <a href="{{ route('admin.skillmatching.matches.index') }}" class="kt-btn kt-btn-outline">
                    Annuleren
                </a>
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-check me-2"></i>
                    Wijzigingen Opslaan
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
