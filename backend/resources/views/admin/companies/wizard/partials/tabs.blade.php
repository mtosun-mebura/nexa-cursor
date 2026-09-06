@php
    $steps = $wizardSteps ?? \App\Http\Controllers\Admin\AdminCompanyWizardController::stepMeta();
    $total = count($steps);
    $current = (int) ($currentStep ?? 1);
    $reachable = (int) ($maxReachable ?? 1);
@endphp

<div class="kt-card mb-6 overflow-hidden">
    <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
        <div>
            <h2 class="kt-card-title mb-0">Stappenplan</h2>
            <p class="text-sm text-muted-foreground mt-1 mb-0">
                Stap {{ $current }} van {{ $total }}
                @if(isset($steps[$current]))
                    — {{ $steps[$current]['label'] }}
                @endif
            </p>
        </div>
        <p class="text-xs text-muted-foreground mb-0 max-w-md text-end">
            @if(! empty($wizardBrowse))
                Klik een stap om die onderdelen te bewerken. Configuratiestappen zonder toestemming blijven afgeschermd.
            @else
                Volgende stappen openen pas na <strong>Volgende</strong>. Configuraties zonder toestemming blijven afgeschermd.
            @endif
        </p>
    </div>
    <div class="kt-card-content p-5 lg:p-6">
        <nav aria-label="Tenant onboarding stappen">
            <ol class="wizard-stepper m-0 p-0 list-none flex w-full">
                @foreach($steps as $num => $meta)
                    @php
                        $isCurrent = $current === $num;
                        $isProgressLocked = $company
                            ? $num > $reachable
                            : $num > 1;
                        $isAccessLocked = $company && in_array($num, $wizardAccessLockedSteps ?? [], true);
                        $isLocked = $isProgressLocked || $isAccessLocked;
                        $isDone = ! $isLocked && ! $isCurrent && $num < $current;
                        $canClick = $company && $num <= $reachable && ! $isCurrent && ! $isAccessLocked;
                        if (! $company) {
                            $canClick = false;
                            $isLocked = $num > 1;
                        }
                        $lineDone = $num < $reachable || $num < $current;
                    @endphp
                    <li class="wizard-stepper-item flex-1 min-w-0 {{ $isCurrent ? 'is-current' : '' }} {{ $isDone ? 'is-done' : '' }} {{ $isLocked ? 'is-locked' : '' }} {{ $isAccessLocked ? 'is-access-locked' : '' }}">
                        <div class="wizard-stepper-track">
                            @if($canClick)
                                <a href="{{ route('admin.companies.wizard.step', [$company, $num]) }}"
                                   class="wizard-stepper-marker {{ $isDone ? 'wizard-stepper-marker--done' : 'wizard-stepper-marker--available' }}"
                                   title="Ga naar {{ $meta['label'] }}">
                                    @if($isDone)
                                        <i class="ki-filled ki-check text-sm"></i>
                                        <span class="sr-only">{{ $meta['label'] }} (voltooid)</span>
                                    @else
                                        {{ $num }}
                                    @endif
                                </a>
                            @elseif($isCurrent && $isAccessLocked)
                                <span class="wizard-stepper-marker wizard-stepper-marker--access-locked" aria-current="step" title="Geen toegang tot deze configuratie">
                                    <i class="ki-filled ki-lock text-sm"></i>
                                    <span class="sr-only">{{ $meta['label'] }} (geen toegang)</span>
                                </span>
                            @elseif($isCurrent)
                                <span class="wizard-stepper-marker wizard-stepper-marker--current" aria-current="step">
                                    {{ $num }}
                                </span>
                            @elseif($isAccessLocked)
                                <span class="wizard-stepper-marker wizard-stepper-marker--access-locked" title="Geen toegang tot deze configuratie">
                                    <i class="ki-filled ki-lock text-sm"></i>
                                    <span class="sr-only">{{ $meta['label'] }} (geen toegang)</span>
                                </span>
                            @elseif($isLocked)
                                <span class="wizard-stepper-marker wizard-stepper-marker--locked" title="Voltooi eerst de vorige stappen">
                                    {{ $num }}
                                </span>
                            @else
                                <span class="wizard-stepper-marker">{{ $num }}</span>
                            @endif
                            @if(! $loop->last)
                                <span class="wizard-stepper-line {{ $lineDone ? 'is-done' : '' }}" aria-hidden="true"></span>
                            @endif
                        </div>
                        @if($canClick)
                            <a href="{{ route('admin.companies.wizard.step', [$company, $num]) }}"
                               class="wizard-stepper-label wizard-stepper-label--clickable">
                                {{ $meta['short'] ?? $meta['label'] }}
                            </a>
                        @else
                            <span class="wizard-stepper-label {{ $isCurrent ? 'is-current' : '' }} {{ $isLocked ? 'is-locked' : '' }} {{ $isAccessLocked ? 'is-access-locked' : '' }}">
                                {{ $meta['short'] ?? $meta['label'] }}
                            </span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    </div>
</div>

<style>
    .wizard-stepper-item {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        text-align: center;
    }
    .wizard-stepper-track {
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        width: 100%;
        height: 1.75rem;
    }
    .wizard-stepper-marker {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        position: relative;
        z-index: 1;
        width: 1.75rem;
        height: 1.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        line-height: 1;
        flex-shrink: 0;
        border: 2px solid var(--border, #e4e4e7);
        background: var(--background, #fff);
        color: var(--muted-foreground, #71717a);
    }
    .wizard-stepper-item.is-done .wizard-stepper-marker,
    .wizard-stepper-marker--done {
        background: var(--primary, #1b84ff);
        border-color: var(--primary, #1b84ff);
        color: #fff;
    }
    .wizard-stepper-marker--current,
    .wizard-stepper-item.is-current .wizard-stepper-marker {
        background: #f97316;
        border-color: #f97316;
        color: #fff;
        box-shadow: 0 0 0 4px color-mix(in srgb, #f97316 22%, transparent);
    }
    .wizard-stepper-marker--available {
        background: var(--background, #fff);
        border-color: var(--primary, #1b84ff);
        color: var(--primary, #1b84ff);
    }
    .wizard-stepper-marker--done:hover,
    .wizard-stepper-marker--available:hover {
        filter: brightness(1.08);
    }
    .wizard-stepper-marker--locked {
        opacity: 0.55;
        cursor: not-allowed;
    }
    .wizard-stepper-item.is-access-locked .wizard-stepper-marker,
    .wizard-stepper-marker--access-locked {
        background: color-mix(in srgb, var(--muted, #a1a1aa) 18%, transparent);
        border-color: #ef4444;
        border-style: dashed;
        color: #ef4444;
        opacity: 1;
        cursor: not-allowed;
    }
    .wizard-stepper-item.is-access-locked .wizard-stepper-label,
    .wizard-stepper-label.is-access-locked {
        color: #ef4444;
        opacity: 0.9;
    }
    .wizard-stepper-line {
        position: absolute;
        top: 50%;
        left: calc(50% + 1.05rem);
        right: calc(-50% + 1.05rem);
        height: 2px;
        margin: 0;
        transform: translateY(-50%);
        background: var(--border, #e4e4e7);
        pointer-events: none;
    }
    .wizard-stepper-line.is-done {
        background: var(--primary, #1b84ff);
    }
    .wizard-stepper-label {
        display: block;
        margin-top: 0.5rem;
        padding: 0 0.2rem;
        text-align: center;
        font-size: 0.7rem;
        line-height: 1.25;
        color: var(--muted-foreground, #71717a);
        font-weight: 500;
    }
    .wizard-stepper-label.is-current {
        color: #f97316;
        font-weight: 600;
    }
    .wizard-stepper-label.is-locked {
        opacity: 0.65;
    }
    .wizard-stepper-label--clickable {
        color: var(--secondary-foreground, #3f3f46);
        text-decoration: none;
    }
    .wizard-stepper-label--clickable:hover {
        color: var(--primary, #1b84ff);
    }
    @media (max-width: 767px) {
        .wizard-stepper {
            flex-wrap: wrap;
            row-gap: 1rem;
        }
        .wizard-stepper-item {
            flex: 1 1 20%;
            min-width: 4.5rem;
        }
    }
</style>
