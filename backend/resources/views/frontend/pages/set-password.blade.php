@extends('frontend.layouts.app')

@section('title', 'Wachtwoord instellen')

@section('content')
<section class="bg-gradient-to-br from-blue-600 via-blue-700 to-purple-800 dark:from-gray-900 dark:via-blue-900 dark:to-purple-900 relative overflow-hidden min-h-screen flex items-center">
    <div class="absolute inset-0 bg-black/10 dark:bg-black/20"></div>

    <div class="w-full px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="max-w-md mx-auto">
            <div class="text-center mb-8">
                <img src="{{ asset('images/nexa-logo.png') }}" alt="NEXA" class="h-16 w-auto mx-auto mb-6">
                <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">Wachtwoord instellen</h1>
                <p class="text-lg text-blue-100 dark:text-blue-200">Kies een wachtwoord voor je account</p>
            </div>

            <div class="card p-8 bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm">
                <form class="space-y-6" action="{{ route('frontend.set-password.post') }}" method="POST">
                    @csrf
                    @if(!empty($intendedUrl))
                        <input type="hidden" name="intended" value="{{ $intendedUrl }}">
                    @endif

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nieuw wachtwoord</label>
                        <input id="password" name="password" type="password" autocomplete="new-password" required class="input w-full" placeholder="Minimaal 8 tekens">
                        @error('password')<div class="text-sm text-red-600 mt-2">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Herhaal wachtwoord</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="input w-full" placeholder="Herhaal je wachtwoord">
                    </div>

                    <button type="submit" class="btn btn-primary w-full btn-lg">Opslaan</button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection

