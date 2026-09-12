@extends('admin.layouts.app')

@section('title', 'Nieuwe dienst')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">Nieuwe dienst</h1>
        <a href="{{ route('admin.taxi.driver_schedules.index') }}" class="kt-btn kt-btn-outline shrink-0">
            <i class="ki-filled ki-arrow-left me-2"></i>
            Terug
        </a>
    </div>

    <form action="{{ route('admin.taxi.driver_schedules.store') }}" method="POST" data-validate="true" novalidate>
        @csrf
        <div class="grid gap-5 lg:gap-7.5">
            <x-error-card :errors="$errors" />

            @if($superAdminNeedsTenant)
                <div class="kt-alert kt-alert-danger border border-destructive/40 bg-destructive/10 text-destructive dark:text-red-300" role="alert">
                    <i class="ki-filled ki-information me-2 shrink-0"></i>
                    <span>Selecteer eerst een <strong>tenant</strong> in de tenant-kiezer bovenaan om een chauffeur in te plannen.</span>
                </div>
            @endif

            @include('taxi::admin.driver_schedules.partials.form')

            <div class="admin-form-actions flex flex-wrap items-center justify-end gap-2.5 w-full min-w-0">
                <a href="{{ route('admin.taxi.driver_schedules.index') }}" class="kt-btn kt-btn-outline">Annuleren</a>
                <button type="submit" class="kt-btn kt-btn-primary" @if($superAdminNeedsTenant) disabled aria-disabled="true" @endif>
                    Dienst inplannen
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/form-validation.js') }}"></script>
@endpush
