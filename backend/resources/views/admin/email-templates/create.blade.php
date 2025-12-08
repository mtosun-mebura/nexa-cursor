@extends('admin.layouts.app')

@section('title', 'Nieuw E-mail Sjabloon')

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

                    <form action="{{ route('admin.email-templates.store') }}" method="POST">
                        @csrf
                        
                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="lg:col-span-6">
                                <div class="w-full">
                        <div class="flex items-center py-3">
                            <label for="name" class="kt-form-label flex items-center gap-1 max-w-56">
                                Naam *
                            </label>
                            <input type="text" class="kt-input @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name') }}" required>
                            @error('name') is-invalid @enderror
                        </div>
                    </div></div>
                            
                            <div class="lg:col-span-6">
                                <div class="w-full">
                        <div class="flex items-center py-3">
                            <label for="subject" class="kt-form-label flex items-center gap-1 max-w-56">
                                Onderwerp *
                            </label>
                            <input type="text" class="kt-input @error('subject') is-invalid @enderror" 
                                           id="subject" name="subject" value="{{ old('subject') }}" required>
                            @error('subject') is-invalid @enderror
                        </div>
                    </div></div>
                        </div>

                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="lg:col-span-6">
                                <div class="w-full">
                        <div class="flex items-center py-3">
                            <label for="type" class="kt-form-label flex items-center gap-1 max-w-56">
                                Type *
                            </label>
                            <select class="kt-select @error('type') is-invalid @enderror" 
                                            id="type" name="type" required>
                                        <option value="">Selecteer type</option>
                                        <option value="welcome" {{ old('type') == 'welcome' ? 'selected' : '' }}>Welkom</option>
                                        <option value="password_reset" {{ old('type') == 'password_reset' ? 'selected' : '' }}>Wachtwoord Reset</option>
                                        <option value="email_verification" {{ old('type') == 'email_verification' ? 'selected' : '' }}>E-mail Verificatie</option>
                                        <option value="match_notification" {{ old('type') == 'match_notification' ? 'selected' : '' }}>Match Notificatie</option>
                                        <option value="interview_invitation" {{ old('type') == 'interview_invitation' ? 'selected' : '' }}>Interview Uitnodiging</option>
                                        <option value="application_received" {{ old('type') == 'application_received' ? 'selected' : '' }}>Sollicitatie Ontvangen</option>
                                        <option value="application_status" {{ old('type') == 'application_status' ? 'selected' : '' }}>Sollicitatie Status</option>
                                        <option value="custom" {{ old('type') == 'custom' ? 'selected' : '' }}>Aangepast</option>
                                    </select>
                            @error('type') is-invalid @enderror
                        </div>
                    </div></div>
                            
                            <div class="lg:col-span-6">
                                <div class="w-full">
                        <div class="flex items-center py-3">
                            <label for="is_active" class="kt-form-label flex items-center gap-1 max-w-56">
                                Status
                            </label>
                            <select class="kt-select @error('is_active') is-invalid @enderror" 
                                            id="is_active" name="is_active">
                                        <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Actief</option>
                                        <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactief</option>
                                    </select>
                            @error('is_active') is-invalid @enderror
                        </div>
                    </div></div>
                        </div>

                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="lg:col-span-12">
                                <div class="w-full">
                        <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                            <label class="kt-form-label flex items-center gap-1 max-w-56">

                                    <label for="html_content" class="kt-form-label">HTML Inhoud *</label>
                                    <textarea class="kt-input @error('html_content') is-invalid @enderror" 
                                              id="html_content" name="html_content" rows="12" required>{{ old('html_content') }}</textarea>
                                    @error('html_content')
                                        <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                    @enderror
                                    <small class="material-text-muted">
                                        Beschikbare variabelen: 
                                        @foreach($templateVariables as $variable => $description)
                                            <code>{{ $variable }}</code> ({{ $description }})@if(!$loop->last), @endif
                                        @endforeach
                                    </small>
                                
                        </div>
                    </div></div>
                        </div>

                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="lg:col-span-12">
                                <div class="w-full">
                        <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                            <label class="kt-form-label flex items-center gap-1 max-w-56">

                                    <label for="text_content" class="kt-form-label">Tekst Inhoud (Plain Text)</label>
                                    <textarea class="kt-input @error('text_content') is-invalid @enderror" 
                                              id="text_content" name="text_content" rows="8">{{ old('text_content') }}</textarea>
                                    @error('text_content')
                                        <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                    @enderror
                                    <small class="material-text-muted">Tekstversie voor e-mail clients die geen HTML ondersteunen</small>
                                
                        </div>
                    </div></div>
                        </div>

                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="lg:col-span-12">
                                <div class="w-full">
                        <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                            <label class="kt-form-label flex items-center gap-1 max-w-56">

                                    <label for="description" class="kt-form-label">Beschrijving</label>
                                    <textarea class="kt-input @error('description') is-invalid @enderror" 
                                              id="description" name="description" rows="4">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                    @enderror
                                
                        </div>
                    </div></div>
                        </div>

                        <div class="flex items-center justify-end gap-2.5">
                            <a href="{{ route('admin.email-templates.index') }}" class="kt-btn kt-btn-outline">Annuleren</a>
                            <button type="submit" class="kt-btn kt-btn-primary">
                                <i class="fas fa-save"></i> Sjabloon Opslaan
                            </button>
                        </div>
                    </form>
                </div>
    </div>
</div>
@endsection
