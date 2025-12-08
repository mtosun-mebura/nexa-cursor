@extends('admin.layouts.app')

@section('title', 'Gebruiker Bewerken')

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

                    <form action="{{ route('admin.users.update', $user) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="lg:col-span-6">
                                <div class="w-full">
                        <div class="flex items-center py-3">
                            <label for="first_name" class="kt-form-label flex items-center gap-1 max-w-56">
                                Voornaam *
                            </label>
                            <input type="text" class="kt-input @error('first_name') is-invalid @enderror" 
                                           id="first_name" name="first_name" value="{{ old('first_name', $user->
                            @error('first_name') is-invalid @enderror
                        </div>
                    </div></div>
                            
                            <div class="lg:col-span-6">
                                <div class="w-full">
                        <div class="flex items-center py-3">
                            <label for="last_name" class="kt-form-label flex items-center gap-1 max-w-56">
                                Achternaam *
                            </label>
                            <input type="text" class="kt-input @error('last_name') is-invalid @enderror" 
                                           id="last_name" name="last_name" value="{{ old('last_name', $user->
                            @error('last_name') is-invalid @enderror
                        </div>
                    </div></div>
                        </div>

                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="lg:col-span-6">
                                <div class="w-full">
                        <div class="flex items-center py-3">
                            <label for="email" class="kt-form-label flex items-center gap-1 max-w-56">
                                E-mail *
                            </label>
                            <input type="email" class="kt-input @error('email') is-invalid @enderror" 
                                           id="email" name="email" value="{{ old('email', $user->
                            @error('email') is-invalid @enderror
                        </div>
                    </div></div>
                            
                            <div class="lg:col-span-6">
                                <div class="w-full">
                        <div class="flex items-center py-3">
                            <label for="password" class="kt-form-label flex items-center gap-1 max-w-56">
                                Nieuw Wachtwoord
                            </label>
                            <input type="password" class="kt-input @error('password') is-invalid @enderror" 
                                           id="password" name="password" placeholder="Laat leeg om niet te wijzigen">
                            @error('password') is-invalid @enderror
                        </div>
                    </div></div>
                        </div>

                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="lg:col-span-6">
                                <div class="w-full">
                        <div class="flex items-center py-3">
                            <label for="company_id" class="kt-form-label flex items-center gap-1 max-w-56">
                                Bedrijf
                            </label>
                            <input type="text" class="kt-input" value="{{ $selectedCompany->
                            @error('company_id') is-invalid @enderror
                        </div>
                    </div></div>
                            
                            <div class="lg:col-span-6">
                                <div class="w-full">
                        <div class="flex items-center py-3">
                            <label for="date_of_birth" class="kt-form-label flex items-center gap-1 max-w-56">
                                Geboortedatum
                            </label>
                            <input type="date" class="kt-input @error('date_of_birth') is-invalid @enderror" 
                                           id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth', $user->
                            @error('date_of_birth') is-invalid @enderror
                        </div>
                    </div></div>
                        </div>

                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="lg:col-span-6">
                                <div class="w-full">
                        <div class="flex items-center py-3">
                            <label for="phone" class="kt-form-label flex items-center gap-1 max-w-56">
                                Telefoon
                            </label>
                            <input type="tel" class="kt-input @error('phone') is-invalid @enderror" 
                                           id="phone" name="phone" value="{{ old('phone', $user->
                            @error('phone') is-invalid @enderror
                        </div>
                    </div></div>
                            
                            <div class="lg:col-span-6">
                                <div class="w-full">
                        <div class="flex items-center py-3">
                            <label for="role" class="kt-form-label flex items-center gap-1 max-w-56">
                                Rol *
                            </label>
                            <select class="kt-select @error('role') is-invalid @enderror" 
                                            id="role" name="role" required>
                                        <option value="">Selecteer rol</option>
                                        @foreach($roles as $role)
                                            @if($role->name !== 'super-admin' || auth()->user()->hasRole('super-admin'))
                                            <option value="{{ $role->name }}" {{ old('role', $user->roles->first()->name ?? '') == $role->name ? 'selected' : '' }}>
                                                {{ ucfirst(str_replace('-', ' ', $role->name)) }}
                                            </option>
                                            @endif
                                        @endforeach
                                    </select>
                            @error('role') is-invalid @enderror
                        </div>
                    </div></div>
                        </div>

                        <div class="flex items-center justify-end gap-2.5">
                            <a href="{{ route('admin.users.index') }}" class="kt-btn kt-btn-outline">Annuleren</a>
                            <button type="submit" class="kt-btn kt-btn-primary">
                                <i class="fas fa-save"></i> Wijzigingen Opslaan
                            </button>
                        </div>
                    </form>
                </div>
    </div>
</div>
@endsection
