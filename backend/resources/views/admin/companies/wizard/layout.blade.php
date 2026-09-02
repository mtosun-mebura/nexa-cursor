@extends('admin.layouts.app')

@section('title', 'Nieuwe tenant — stap ' . ($currentStep ?? 1))

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-col gap-4 pb-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-xl font-medium leading-none text-mono">
                Tenant onboarding
            </h1>
            <a href="{{ route('admin.companies.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Naar bedrijvenlijst
            </a>
        </div>
        <p class="text-sm text-secondary-foreground max-w-3xl">
            Doorloop alle stappen om de tenant in te richten, inclusief mailserver, Google SEO en integraties.
        </p>
    </div>

    @include('admin.companies.wizard.partials.tabs', ['company' => $company ?? null, 'currentStep' => $currentStep ?? 1, 'maxReachable' => $maxReachable ?? 1])

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5" role="alert">
            <i class="ki-filled ki-check-circle me-2"></i>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="kt-alert kt-alert-danger mb-5" role="alert">
            <i class="ki-filled ki-information me-2"></i>
            {{ session('error') }}
        </div>
    @endif

    @yield('wizard_content')
</div>

<style>
    .wizard-onboarding-form-table .kt-switch {
        pointer-events: auto !important;
        z-index: 1;
        position: relative;
    }
    .wizard-onboarding-form-table .kt-label {
        cursor: pointer;
    }
</style>
@endsection
