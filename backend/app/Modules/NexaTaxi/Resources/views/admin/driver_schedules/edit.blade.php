@extends('admin.layouts.app')

@section('title', 'Dienst bewerken')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">Dienst bewerken</h1>
        <a href="{{ route('admin.taxi.driver_schedules.index') }}" class="kt-btn kt-btn-outline shrink-0">
            <i class="ki-filled ki-arrow-left me-2"></i>
            Terug
        </a>
    </div>

    <form action="{{ route('admin.taxi.driver_schedules.update', $schedule->id) }}" method="POST" data-validate="true" novalidate>
        @csrf
        @method('PUT')
        <div class="grid gap-5 lg:gap-7.5">
            <x-error-card :errors="$errors" />

            @include('taxi::admin.driver_schedules.partials.form')

            <div class="admin-form-actions flex flex-wrap items-center justify-between gap-2.5 w-full min-w-0">
                <button type="button" class="kt-btn kt-btn-outline text-destructive" id="driver-schedule-delete-btn">
                    Verwijderen
                </button>
                <div class="flex flex-wrap gap-2.5">
                    <a href="{{ route('admin.taxi.driver_schedules.index') }}" class="kt-btn kt-btn-outline">Annuleren</a>
                    <button type="submit" class="kt-btn kt-btn-primary">Opslaan</button>
                </div>
            </div>
        </div>
    </form>

    <form id="driver-schedule-delete-form" action="{{ route('admin.taxi.driver_schedules.destroy', $schedule->id) }}" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/form-validation.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('driver-schedule-delete-btn');
    var form = document.getElementById('driver-schedule-delete-form');
    if (!btn || !form) {
        return;
    }
    btn.addEventListener('click', async function () {
        var ok = true;
        if (typeof window.showAdminConfirm === 'function') {
            ok = await window.showAdminConfirm({
                title: 'Dienst verwijderen',
                message: 'Weet je zeker dat je deze dienst wilt verwijderen? Bij een wekelijkse herhaling verdwijnen ook alle toekomstige dagen.',
                confirmLabel: 'Verwijderen'
            });
        } else {
            ok = window.confirm('Weet je zeker dat je deze ingeplande dienst wilt verwijderen?');
        }
        if (ok) {
            form.submit();
        }
    });
});
</script>
@endpush
