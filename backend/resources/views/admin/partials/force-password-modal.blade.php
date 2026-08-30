{{-- Blokkerende overlay: tijdelijk wachtwoord moet eerst gewijzigd worden. --}}
<div class="fixed inset-0 z-[100000] flex items-center justify-center bg-zinc-950/70 p-4 backdrop-blur-md"
     role="dialog"
     aria-modal="true"
     aria-labelledby="force-password-title"
     data-admin-force-password="1">
    <div class="w-full max-w-lg rounded-2xl border border-border bg-background shadow-2xl">
        <div class="border-b border-border px-6 py-5">
            <h2 id="force-password-title" class="text-lg font-semibold text-foreground mb-1">Wachtwoord wijzigen</h2>
            <p class="text-sm text-muted-foreground mb-0">
                U bent ingelogd met een tijdelijk wachtwoord. Kies nu een eigen wachtwoord voordat u verdergaat.
                Dit scherm kunt u niet verlaten totdat het wachtwoord is opgeslagen.
            </p>
        </div>
        <form method="post" action="{{ route('admin.password.force.update') }}" class="px-6 py-5 space-y-4" data-validate="true" novalidate>
            @csrf
            <div>
                <label for="force_password" class="kt-label mb-1">Nieuw wachtwoord</label>
                <div class="relative">
                    <input id="force_password"
                           type="password"
                           name="password"
                           class="kt-input w-full @error('password') border-destructive @enderror"
                           required
                           minlength="8"
                           autocomplete="new-password"
                           data-required-message="Nieuw wachtwoord is verplicht.">
                </div>
                <p class="text-xs text-muted-foreground mt-1">Minimaal 8 tekens, met een hoofdletter, een kleine letter en een cijfer.</p>
                <div class="field-feedback text-xs mt-1 hidden" data-field="password"></div>
                @error('password')
                    <div class="text-xs text-destructive mt-1 laravel-inline-error" data-laravel-field="password" data-laravel-message="{{ $message }}" role="alert">{{ $message }}</div>
                @enderror
            </div>
            <div>
                <label for="force_password_confirmation" class="kt-label mb-1">Herhaal wachtwoord</label>
                <div class="relative">
                    <input id="force_password_confirmation"
                           type="password"
                           name="password_confirmation"
                           class="kt-input w-full @error('password_confirmation') border-destructive @enderror"
                           required
                           minlength="8"
                           autocomplete="new-password"
                           data-required-message="Herhaal het wachtwoord.">
                </div>
                <div class="field-feedback text-xs mt-1 hidden" data-field="password_confirmation"></div>
                @error('password_confirmation')
                    <div class="text-xs text-destructive mt-1 laravel-inline-error" data-laravel-field="password_confirmation" data-laravel-message="{{ $message }}" role="alert">{{ $message }}</div>
                @enderror
            </div>
            <button type="submit" class="kt-btn kt-btn-primary w-full">Wachtwoord opslaan</button>
        </form>
        <form method="post" action="{{ route('admin.logout') }}" class="px-6 pb-5">
            @csrf
            <button type="submit" class="kt-btn kt-btn-light w-full">Uitloggen</button>
        </form>
    </div>
</div>
<script src="{{ asset('assets/js/form-validation.js') }}"></script>
<script>
(function () {
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            e.preventDefault();
            e.stopPropagation();
        }
    }, true);
    document.addEventListener('click', function (e) {
        var overlay = document.querySelector('[data-admin-force-password="1"]');
        if (!overlay) return;
        if (!overlay.contains(e.target)) {
            e.preventDefault();
            e.stopPropagation();
        }
    }, true);
})();
</script>
