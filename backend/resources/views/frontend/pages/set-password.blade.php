@extends('frontend.layouts.app')

@section('title', 'Wachtwoord instellen')

@section('content')
<section class="bg-gradient-to-br from-blue-600 via-blue-700 to-purple-800 dark:from-gray-900 dark:via-blue-900 dark:to-purple-900 relative overflow-hidden min-h-screen flex items-center">
    <div class="absolute inset-0 bg-black/10 dark:bg-black/20"></div>

    <div class="w-full px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="max-w-md mx-auto">
            <div class="text-center mb-8">
                <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">Wachtwoord instellen</h1>
                <p class="text-lg text-blue-100 dark:text-blue-200">Kies een wachtwoord voor je account</p>
            </div>

            <div class="card p-8 bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm">
                <form id="set-password-form" class="space-y-6" action="{{ route('frontend.set-password.post') }}" method="POST" novalidate>
                    @csrf
                    @if(!empty($intendedUrl))
                        <input type="hidden" name="intended" value="{{ $intendedUrl }}">
                    @endif

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nieuw wachtwoord</label>
                        <input id="password" name="password" type="password" autocomplete="new-password"
                               class="input w-full @error('password') border-red-500 dark:border-red-500 @enderror"
                               placeholder="Min. 8 tekens, hoofdletter, cijfer, symbool"
                               value="{{ old('password') }}">
                        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400 @error('password') @else hidden @enderror"
                           data-set-password-error="password" role="alert">@error('password'){{ $message }}@enderror</p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Herhaal wachtwoord</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                               class="input w-full @error('password_confirmation') border-red-500 dark:border-red-500 @enderror"
                               placeholder="Herhaal je wachtwoord">
                        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400 @error('password_confirmation') @else hidden @enderror"
                           data-set-password-error="password_confirmation" role="alert">@error('password_confirmation'){{ $message }}@enderror</p>
                    </div>

                    <button type="submit" class="btn btn-primary w-full btn-lg justify-center">Opslaan</button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('set-password-form');
    if (!form) return;

    function validatePassword(password) {
        var errors = [];
        if (password.length < 8) errors.push('minimaal 8 karakters');
        if (!/[A-Z]/.test(password)) errors.push('minimaal één hoofdletter');
        if (!/[a-z]/.test(password)) errors.push('minimaal één kleine letter');
        if (!/\d/.test(password)) errors.push('minimaal één cijfer');
        if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) errors.push('minimaal één speciaal karakter');
        if (errors.length > 0) {
            return 'Wachtwoord moet bevatten: ' + errors.join(', ') + '.';
        }
        return null;
    }

    function setFieldError(fieldKey, message) {
        var input = form.querySelector('[name="' + fieldKey + '"]');
        var errEl = form.querySelector('[data-set-password-error="' + fieldKey + '"]');
        if (input) {
            input.classList.toggle('border-red-500', !!message);
            input.classList.toggle('dark:border-red-500', !!message);
        }
        if (errEl) {
            errEl.textContent = message || '';
            errEl.classList.toggle('hidden', !message);
        }
    }

    function clearFieldErrors() {
        ['password', 'password_confirmation'].forEach(function (key) {
            setFieldError(key, '');
        });
    }

    form.addEventListener('submit', function (e) {
        clearFieldErrors();

        var password = (form.querySelector('[name="password"]') || {}).value || '';
        var confirmation = (form.querySelector('[name="password_confirmation"]') || {}).value || '';
        var hasError = false;

        if (!password.trim()) {
            setFieldError('password', 'Nieuw wachtwoord is verplicht.');
            hasError = true;
        } else {
            var passwordMessage = validatePassword(password);
            if (passwordMessage) {
                setFieldError('password', passwordMessage);
                hasError = true;
            }
        }

        if (!confirmation.trim()) {
            setFieldError('password_confirmation', 'Herhaal wachtwoord is verplicht.');
            hasError = true;
        } else if (password && password !== confirmation) {
            setFieldError('password_confirmation', 'Wachtwoord bevestiging komt niet overeen.');
            hasError = true;
        }

        if (hasError) {
            e.preventDefault();
        }
    });

    ['password', 'password_confirmation'].forEach(function (name) {
        var input = form.querySelector('[name="' + name + '"]');
        if (!input) return;
        input.addEventListener('input', function () {
            setFieldError(name, '');
        });
    });
})();
</script>
@endpush
