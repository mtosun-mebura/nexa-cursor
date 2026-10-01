@extends('admin.layouts.app')

@section('title', 'Incidentinstellingen')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <div>
            <h1 class="text-xl font-medium leading-none text-mono">Incidentinstellingen</h1>
            <div class="text-sm text-secondary-foreground mt-2">E-mailnotificatie bij nieuwe incidenten van klanten</div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.incidents.settings.update') }}">
        @csrf
        @method('PUT')

        <div class="grid gap-5 lg:gap-7.5">
            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header px-5 py-5">
                    <h3 class="kt-card-title mb-0">Notificatie-e-mail</h3>
                </div>
                <div class="kt-card-content p-5">
                    <label class="text-sm text-muted-foreground mb-1 block" for="notification_email">E-mailadres</label>
                    <input
                        type="email"
                        id="notification_email"
                        name="notification_email"
                        class="kt-input admin-field-fit @error('notification_email') border-destructive @enderror"
                        value="{{ old('notification_email', $notificationEmail) }}"
                        required
                        autocomplete="email"
                    >
                    <p class="text-xs text-muted-foreground mt-1.5 mb-0">
                        Bij elk nieuw incident van een klant wordt een e-mail naar dit adres gestuurd. Standaard: {{ \App\Support\IncidentCatalog::DEFAULT_NOTIFICATION_EMAIL }}.
                    </p>
                    @error('notification_email')
                        <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="admin-form-actions">
                <button type="submit" class="kt-btn kt-btn-primary">Opslaan</button>
            </div>
        </div>
    </form>
</div>
@endsection
