@extends('admin.companies.wizard.layout')

@section('title', 'Stap ' . $currentStep . ' — Geen toegang')

@section('wizard_content')
<div class="kt-card w-full min-w-0 mb-6 overflow-hidden">
    <div class="kt-card-header px-5 py-5">
        <h3 class="kt-card-title mb-0">{{ $lockedStepLabel ?? 'Configuratie' }}</h3>
    </div>
    <div class="kt-card-content p-5 min-w-0">
        <div class="rounded-xl border border-red-500 bg-primary/5 px-4 py-5">
            <p class="text-sm text-foreground font-medium mb-1">Deze configuratie is afgeschermd</p>
            <p class="text-sm text-secondary-foreground mb-0 break-words">
                {{ $lockedMessage ?? \App\Services\TenantConfigAccessService::DENIED_MESSAGE }}
            </p>
        </div>
    </div>
</div>

<x-wizard.footer-actions :current-step="$currentStep" :company="$company" />
@endsection
