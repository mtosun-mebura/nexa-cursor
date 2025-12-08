@extends('admin.layouts.app')

@section('title', 'Notificatie Bewerken')

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

                    <form action="{{ route('admin.notifications.update', $notification) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="lg:col-span-6">
                                <div class="w-full">
                        <div class="flex items-center py-3">
                            <label for="user_id" class="kt-form-label flex items-center gap-1 max-w-56">
                                Gebruiker *
                            </label>
                            <select class="kt-select @error('user_id') is-invalid @enderror" 
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
                                                <option value="{{ $user->id }}" {{ old('user_id', $notification->user_id) == $user->id ? 'selected' : '' }}>
                                                    {{ $user->first_name }} {{ $user->last_name }} ({{ $user->email }})
                                                </option>
                                            @endforeach
                                        @else
                                            @foreach(\App\Models\User::where('company_id', auth()->user()->company_id)->where('id', '!=', auth()->id())->get() as $user)
                                                <option value="{{ $user->id }}" {{ old('user_id', $notification->user_id) == $user->id ? 'selected' : '' }}>
                                                    {{ $user->first_name }} {{ $user->last_name }} ({{ $user->email }})
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                            @error('user_id') is-invalid @enderror
                        </div>
                    </div></div>
                            
                            <div class="lg:col-span-6">
                                <div class="w-full">
                        <div class="flex items-center py-3">
                            <label for="type" class="kt-form-label flex items-center gap-1 max-w-56">
                                Type *
                            </label>
                            <select class="kt-select @error('type') is-invalid @enderror" 
                                            id="type" name="type" required>
                                        <option value="">Selecteer type</option>
                                        <option value="match" {{ old('type', $notification->type) == 'match' ? 'selected' : '' }}>Match</option>
                                        <option value="interview" {{ old('type', $notification->type) == 'interview' ? 'selected' : '' }}>Interview</option>
                                        <option value="application" {{ old('type', $notification->type) == 'application' ? 'selected' : '' }}>Sollicitatie</option>
                                        <option value="system" {{ old('type', $notification->type) == 'system' ? 'selected' : '' }}>Systeem</option>
                                        <option value="email" {{ old('type', $notification->type) == 'email' ? 'selected' : '' }}>E-mail</option>
                                        <option value="reminder" {{ old('type', $notification->type) == 'reminder' ? 'selected' : '' }}>Herinnering</option>
                                    </select>
                            @error('type') is-invalid @enderror
                        </div>
                    </div></div>
                        </div>

                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="lg:col-span-6">
                                <div class="w-full">
                        <div class="flex items-center py-3">
                            <label for="title" class="kt-form-label flex items-center gap-1 max-w-56">
                                Titel *
                            </label>
                            <input type="text" class="kt-input @error('title') is-invalid @enderror" 
                                           id="title" name="title" value="{{ old('title', $notification->
                            @error('title') is-invalid @enderror
                        </div>
                    </div></div>
                            
                            <div class="lg:col-span-6">
                                <div class="w-full">
                        <div class="flex items-center py-3">
                            <label for="priority" class="kt-form-label flex items-center gap-1 max-w-56">
                                Prioriteit
                            </label>
                            <select class="kt-select @error('priority') is-invalid @enderror" 
                                            id="priority" name="priority">
                                        <option value="low" {{ old('priority', $notification->priority) == 'low' ? 'selected' : '' }}>Laag</option>
                                        <option value="normal" {{ old('priority', $notification->priority) == 'normal' ? 'selected' : '' }}>Normaal</option>
                                        <option value="high" {{ old('priority', $notification->priority) == 'high' ? 'selected' : '' }}>Hoog</option>
                                        <option value="urgent" {{ old('priority', $notification->priority) == 'urgent' ? 'selected' : '' }}>Urgent</option>
                                    </select>
                            @error('priority') is-invalid @enderror
                        </div>
                    </div></div>
                        </div>

                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="lg:col-span-12">
                                <div class="w-full">
                        <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                            <label class="kt-form-label flex items-center gap-1 max-w-56">

                                    <label for="message" class="kt-form-label">Bericht *</label>
                                    <textarea class="kt-input @error('message') is-invalid @enderror" 
                                              id="message" name="message" rows="4" required>{{ old('message', $notification->message) }}</textarea>
                                    @error('message')
                                        <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                    @enderror
                                
                        </div>
                    </div></div>
                        </div>

                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="lg:col-span-6">
                                <div class="w-full">
                        <div class="flex items-center py-3">
                            <label for="read_at" class="kt-form-label flex items-center gap-1 max-w-56">
                                Gelezen op
                            </label>
                            <input type="datetime-local" class="kt-input @error('read_at') is-invalid @enderror" 
                                           id="read_at" name="read_at" 
                                           value="{{ old('read_at', $notification->
                            @error('read_at') is-invalid @enderror
                        </div>
                    </div></div>
                            
                            <div class="lg:col-span-6">
                                <div class="w-full">
                        <div class="flex items-center py-3">
                            <label for="action_url" class="kt-form-label flex items-center gap-1 max-w-56">
                                Actie URL
                            </label>
                            <input type="url" class="kt-input @error('action_url') is-invalid @enderror" 
                                           id="action_url" name="action_url" value="{{ old('action_url', $notification->
                            @error('action_url') is-invalid @enderror
                        </div>
                    </div></div>
                        </div>

                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="lg:col-span-6">
                                <div class="w-full">
                        <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                            <label class="kt-form-label flex items-center gap-1 max-w-56">

                                    <label for="data" class="kt-form-label">Extra Data (JSON)</label>
                                    <textarea class="kt-input @error('data') is-invalid @enderror" 
                                              id="data" name="data" rows="4" 
                                              placeholder='{"key": "value", "match_id": 123}'>{{ old('data', $notification->data) }}</textarea>
                                    @error('data')
                                        <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                    @enderror
                                    <small class="material-text-muted">Optionele JSON data voor extra informatie</small>
                                
                        </div>
                    </div></div>
                            
                            <div class="lg:col-span-6">
                                <div class="w-full">
                        <div class="flex items-center py-3">
                            <label for="scheduled_at" class="kt-form-label flex items-center gap-1 max-w-56">
                                Gepland voor
                            </label>
                            <input type="datetime-local" class="kt-input @error('scheduled_at') is-invalid @enderror" 
                                           id="scheduled_at" name="scheduled_at" 
                                           value="{{ old('scheduled_at', $notification->
                            @error('scheduled_at') is-invalid @enderror
                        </div>
                    </div></div>
                        </div>

                        <div class="flex items-center justify-end gap-2.5">
                            <a href="{{ route('admin.notifications.index') }}" class="kt-btn kt-btn-outline">Annuleren</a>
                            <button type="submit" class="kt-btn kt-btn-primary">
                                <i class="fas fa-save"></i> Wijzigingen Opslaan
                            </button>
                        </div>
                    </form>
                </div>
    </div>
</div>
@endsection
