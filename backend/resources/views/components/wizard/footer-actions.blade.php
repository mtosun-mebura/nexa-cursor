@props([
    'currentStep' => 1,
    'company' => null,
])

@php
    $step = (int) $currentStep;
    $backStep = null;
    if ($company !== null && $step > 1) {
        $backStep = app(\App\Services\TenantConfigAccessService::class)
            ->previousAccessibleStep(auth()->user(), $company, $step);
    }
    $showBack = $backStep !== null && $company !== null;
@endphp
<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-3 justify-between']) }}>
    @if($showBack)
        <a href="{{ route('admin.companies.wizard.step', [$company, $backStep]) }}" class="kt-btn kt-btn-outline shrink-0">
            <i class="ki-filled ki-arrow-left me-2"></i>
            Terug
        </a>
    @endif
    <div class="flex flex-wrap items-center gap-3 {{ $showBack ? '' : 'ml-auto' }}">
        {{ $slot }}
    </div>
</div>
