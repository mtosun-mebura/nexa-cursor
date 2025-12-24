@extends('admin.layouts.app')

@section('title', 'Nieuwe Match')

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

    <div class="grid gap-5 lg:gap-7.5">
        <div class="w-full">
            <div class="kt-container-fixed">
    <div class="flex flex-col items-stretch grow">
        <form[^>]*class="[^"]*"
                    @if($errors->any())
                        <div class="kt-alert kt-alert-danger">
                            <ul >
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.matches.store') }}" method="POST">
                        @csrf
                        
                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="lg:col-span-6">
                                <div class="w-full">
                        <div class="flex items-center py-3">
                            <label for="candidate_id" class="kt-form-label flex items-center gap-1 max-w-56">
                                Kandidaat *
                            </label>
                            <select class="kt-select @error('candidate_id') is-invalid @enderror" 
                                            id="candidate_id" name="candidate_id" required>
                                        <option value="">Selecteer kandidaat</option>
                                        @foreach($candidates as $candidate)
                                            <option value="{{ $candidate->id }}" {{ old('candidate_id') == $candidate->id ? 'selected' : '' }}>
                                                {{ $candidate->first_name }} {{ $candidate->last_name }} (K) ({{ $candidate->email }})
                                            </option>
                                        @endforeach
                                    </select>
                            @error('candidate_id') is-invalid @enderror
                        </div>
                    </div></div>
                            
                            <div class="lg:col-span-6">
                                <div class="w-full">
                        <div class="flex items-center py-3">
                            <label for="vacancy_id" class="kt-form-label flex items-center gap-1 max-w-56">
                                Vacature *
                            </label>
                            <select class="kt-select @error('vacancy_id') is-invalid @enderror" 
                                            id="vacancy_id" name="vacancy_id" required>
                                        <option value="">Selecteer vacature</option>
                                        @foreach(\App\Models\Vacancy::all() as $vacancy)
                                            <option value="{{ $vacancy->id }}" {{ old('vacancy_id') == $vacancy->id ? 'selected' : '' }}>
                                                {{ $vacancy->title }} - {{ $vacancy->company->name }}
                                            </option>
                                        @endforeach
                                    </select>
                            @error('vacancy_id') is-invalid @enderror
                        </div>
                    </div></div>
                        </div>

                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="lg:col-span-6">
                                <div class="w-full">
                        <div class="flex items-center py-3">
                            <label for="match_score" class="kt-form-label flex items-center gap-1 max-w-56">
                                Match Score (%)
                            </label>
                            <input type="number" class="kt-input @error('match_score') is-invalid @enderror" 
                                           id="match_score" name="match_score" value="{{ old('match_score') }}" 
                                           min="0" max="100" step="0.1">
                            @error('match_score') is-invalid @enderror
                        </div>
                    </div></div>
                            
                            <div class="lg:col-span-6">
                                <div class="w-full">
                        <div class="flex items-center py-3">
                            <label for="status" class="kt-form-label flex items-center gap-1 max-w-56">
                                Status *
                            </label>
                            <select class="kt-select @error('status') is-invalid @enderror" 
                                            id="status" name="status" required>
                                        <option value="">Selecteer status</option>
                                        <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>In afwachting</option>
                                        <option value="accepted" {{ old('status') == 'accepted' ? 'selected' : '' }}>Geaccepteerd</option>
                                        <option value="rejected" {{ old('status') == 'rejected' ? 'selected' : '' }}>Afgewezen</option>
                                        <option value="interview_scheduled" {{ old('status') == 'interview_scheduled' ? 'selected' : '' }}>Interview gepland</option>
                                        <option value="hired" {{ old('status') == 'hired' ? 'selected' : '' }}>Aangenomen</option>
                                    </select>
                            @error('status') is-invalid @enderror
                        </div>
                    </div></div>
                        </div>

                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="lg:col-span-6">
                                <div class="w-full">
                        <div class="flex items-center py-3">
                            <label for="ai_recommendation" class="kt-form-label flex items-center gap-1 max-w-56">
                                AI Aanbeveling
                            </label>
                            <select class="kt-select @error('ai_recommendation') is-invalid @enderror" 
                                            id="ai_recommendation" name="ai_recommendation">
                                        <option value="">Selecteer aanbeveling</option>
                                        <option value="strong_match" {{ old('ai_recommendation') == 'strong_match' ? 'selected' : '' }}>Sterke match</option>
                                        <option value="good_match" {{ old('ai_recommendation') == 'good_match' ? 'selected' : '' }}>Goede match</option>
                                        <option value="moderate_match" {{ old('ai_recommendation') == 'moderate_match' ? 'selected' : '' }}>Matige match</option>
                                        <option value="weak_match" {{ old('ai_recommendation') == 'weak_match' ? 'selected' : '' }}>Zwakke match</option>
                                        <option value="not_recommended" {{ old('ai_recommendation') == 'not_recommended' ? 'selected' : '' }}>Niet aanbevolen</option>
                                    </select>
                            @error('ai_recommendation') is-invalid @enderror
                        </div>
                    </div></div>
                            
                            <div class="lg:col-span-6">
                                <div class="w-full">
                        <div class="flex items-center py-3">
                            <label for="application_date" class="kt-form-label flex items-center gap-1 max-w-56">
                                Sollicitatiedatum
                            </label>
                            <!--begin::Input with Calendar-->
                            <div class="kt-input w-64 @error('application_date') border-destructive @enderror">
                                <i class="ki-outline ki-calendar"></i>
                                <input class="grow" 
                                       name="application_date" 
                                       id="application_date"
                                       value="{{ old('application_date') }}"
                                       data-kt-date-picker="true" 
                                       data-kt-date-picker-input-mode="true" 
                                       data-kt-date-picker-position-to-input="left"
                                       data-kt-date-picker-format="yyyy-MM-dd"
                                       placeholder="Selecteer datum" 
                                       readonly 
                                       type="text"/>
                            </div>
                            @error('application_date')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                            <!--end::Input with Calendar-->
                        </div>
                    </div></div>
                        </div>

                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="lg:col-span-12">
                                <div class="w-full">
                        <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                            <label class="kt-form-label flex items-center gap-1 max-w-56">

                                    <label for="notes" class="kt-form-label">Notities</label>
                                    <textarea class="kt-input @error('notes') is-invalid @enderror" 
                                              id="notes" name="notes" rows="4">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                    @enderror
                                
                        </div>
                    </div></div>
                        </div>

                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="lg:col-span-12">
                                <div class="w-full">
                        <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                            <label class="kt-form-label flex items-center gap-1 max-w-56">

                                    <label for="ai_analysis" class="kt-form-label">AI Analyse</label>
                                    <textarea class="kt-input @error('ai_analysis') is-invalid @enderror" 
                                              id="ai_analysis" name="ai_analysis" rows="6">{{ old('ai_analysis') }}</textarea>
                                    @error('ai_analysis')
                                        <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                    @enderror
                                    <small class="material-text-muted">Automatische analyse van de match door AI</small>
                                
                        </div>
                    </div></div>
                        </div>

                        <div class="flex items-center justify-end gap-2.5">
                            <a href="{{ route('admin.matches.index') }}" class="kt-btn kt-btn-outline">Annuleren</a>
                            <button type="submit" class="kt-btn kt-btn-primary">
                                <i class="fas fa-save"></i> Match Opslaan
                            </button>
                        </div>
                    </form>
                </div>
    </div>
</div>
@endsection
