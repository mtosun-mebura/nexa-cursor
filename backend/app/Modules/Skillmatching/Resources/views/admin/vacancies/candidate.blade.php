@extends('admin.layouts.app')

@section('title', 'Kandidaat Details - ' . $candidate->first_name . ' ' . $candidate->last_name)

@section('content')

<style>
    .hero-bg {
        background-image: url('{{ asset('assets/media/images/2600x1200/bg-1.png') }}');
    }
    .dark .hero-bg {
        background-image: url('{{ asset('assets/media/images/2600x1200/bg-1-dark.png') }}');
    }
    .match-score-high { color: #10b981; }
    .match-score-medium { color: #f59e0b; }
    .match-score-low { color: #ef4444; }
    /* Ensure flatpickr calendar stays above blurred modal backdrop */
    .flatpickr-calendar {
        z-index: 100000 !important;
    }
    /* Ensure vc datepicker popups stay above modal/backdrop */
    [data-vc-theme="light"].vc[data-vc-input] {
        z-index: 9999 !important;
    }
    /* Ensure accordion content is visible when active */
    .kt-accordion-item.active .kt-accordion-content {
        display: block !important;
    }
</style>

<div class="bg-center bg-cover bg-no-repeat hero-bg">
    <div class="kt-container-fixed">
        <div class="flex flex-col items-center gap-2 lg:gap-3.5 py-4 lg:pt-5 lg:pb-10">
            @php
                $rawStatus = $match ? $match->status : ($application ? $application->status : '');
                $borderColor = 'border-primary';
                switch($rawStatus) {
                    case 'pending':
                        $borderColor = 'border-yellow-500';
                        break;
                    case 'accepted':
                        $borderColor = 'border-green-500';
                        break;
                    case 'rejected':
                        $borderColor = 'border-red-500';
                        break;
                    case 'interview':
                    case 'interview_scheduled':
                        $borderColor = 'border-blue-500';
                        break;
                    default:
                        $borderColor = 'border-primary';
                }
            @endphp
            @if($candidate->photo_blob)
                <div class="rounded-full shrink-0 overflow-hidden border-3 {{ $borderColor }} shadow-lg" style="width: 100px; height: 100px; min-width: 100px; min-height: 100px;">
                    <img class="w-full h-full object-cover" src="{{ route('admin.candidates.photo', $candidate) }}" alt="{{ $candidate->first_name }} {{ $candidate->last_name }}">
                </div>
            @else
                <div class="rounded-full border-3 {{ $borderColor }} h-[100px] w-[100px] lg:h-[150px] lg:w-[150px] shrink-0 flex items-center justify-center bg-primary/10 text-primary text-2xl font-semibold">
                    {{ strtoupper(substr($candidate->first_name ?? 'K', 0, 1) . substr($candidate->last_name ?? '', 0, 1)) }}
                </div>
            @endif
            <div class="flex flex-col items-center gap-1">
                <div class="text-sm lg:text-base text-muted-foreground font-medium">
                    {{ $vacancy->title }}
                </div>
                <div class="flex items-center gap-1.5">
                    <div class="text-xl lg:text-2xl leading-6 font-semibold text-mono">
                        {{ $candidate->first_name }} {{ $candidate->last_name }}
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap justify-center gap-1 lg:gap-4.5 text-sm">
                @if($candidate->email)
                    <div class="flex items-center gap-1.5">
                        <i class="ki-filled ki-sms text-base"></i>
                        <span class="text-foreground">{{ $candidate->email }}</span>
                    </div>
                @endif
                @if($candidate->phone)
                    <div class="flex items-center gap-1.5">
                        <i class="ki-filled ki-phone text-base"></i>
                        <span class="text-foreground">{{ $candidate->phone }}</span>
                    </div>
                @endif
                @if($candidate->city)
                    <div class="flex items-center gap-1.5">
                        <i class="ki-filled ki-geolocation text-base"></i>
                        <span class="text-foreground">{{ $candidate->city }}</span>
                    </div>
                @endif
                @if($match)
                    <div class="flex items-center gap-1.5">
                        @php
                            $score = $match->match_score ?? 0;
                            $scoreClass = $score >= 70 ? 'match-score-high' : ($score >= 40 ? 'match-score-medium' : 'match-score-low');
                        @endphp
                        <span class="text-foreground font-semibold">Match: <span class="{{ $scoreClass }}">{{ number_format($score, 0) }}%</span></span>
                    </div>
                @endif
                @if($match && $match->status)
                    @php
                        $statusMap = [
                            'pending' => ['label' => 'In behandeling', 'color' => 'warning'],
                            'accepted' => ['label' => 'Geaccepteerd', 'color' => 'success'],
                            'rejected' => ['label' => 'Afgewezen', 'color' => 'danger'],
                            'interview' => ['label' => 'Interview', 'color' => 'info'],
                            'interview_scheduled' => ['label' => 'Interview gepland', 'color' => 'info'],
                        ];
                        $statusInfo = $statusMap[$match->status] ?? ['label' => ucfirst($match->status), 'color' => 'secondary'];
                    @endphp
                    <span class="kt-badge kt-badge-sm kt-badge-{{ $statusInfo['color'] }}">{{ $statusInfo['label'] }}</span>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="flex items-center flex-wrap md:flex-nowrap lg:items-center justify-between gap-3 lg:gap-6 mb-5 lg:mb-10 mt-5">
        <div class="flex items-center gap-2.5">
            @php
                // Determine which tab to return to based on the type parameter
                $returnUrl = route('admin.skillmatching.vacancies.show', $vacancy);
                if ($type === 'match' && $match) {
                    $returnUrl = route('admin.skillmatching.vacancies.show', $vacancy) . '#vacancy_tab_matches';
                } elseif ($type === 'application' && $application) {
                    $returnUrl = route('admin.skillmatching.vacancies.show', $vacancy) . '#vacancy_tab_applications';
                }
            @endphp
            <a href="{{ $returnUrl }}" class="kt-btn kt-btn-outline" id="back_button">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
        <div class="flex items-center gap-2.5">
            @php
                $rawStatus = $match ? $match->status : ($application ? $application->status : '');
            @endphp
            @if($rawStatus !== 'rejected')
                <form action="{{ route('admin.skillmatching.vacancies.candidate.reject', ['vacancy' => $vacancy->id, 'candidate' => $candidate->id]) }}" method="POST" class="inline">
                    @csrf
                    @if($match)
                        <input type="hidden" name="match_id" value="{{ $match->id }}">
                    @endif
                    @if($application)
                        <input type="hidden" name="application_id" value="{{ $application->id }}">
                    @endif
                    <button type="button" class="kt-btn kt-btn-outline kt-btn-danger" data-kt-modal-toggle="reject_modal" style="background-color: #dc2626 !important; border-color: #dc2626 !important; color: white !important;" onmouseover="this.style.backgroundColor='#b91c1c'" onmouseout="this.style.backgroundColor='#dc2626'">Afwijzen</button>
                </form>
            @endif
            <button type="button" class="kt-btn kt-btn-primary" id="chat_with_candidate_btn" 
                data-candidate-id="{{ $candidate->id }}"
                data-match-id="{{ $match ? $match->id : '' }}"
                data-application-id="{{ $application ? $application->id : '' }}"
                data-type="{{ $match ? 'match' : ($application ? 'application' : '') }}">
                <i class="ki-filled ki-message-text me-2"></i>
                Chat
            </button>
            <button type="button" class="kt-btn kt-btn-outline" onclick="openChatHistory()">
                <i class="ki-filled ki-history me-2"></i>
                Chat Historie
            </button>
            @if($rawStatus !== 'accepted' && $rawStatus !== 'rejected')
                <form action="{{ route('admin.skillmatching.vacancies.candidate.accept', ['vacancy' => $vacancy->id, 'candidate' => $candidate->id]) }}" method="POST" class="inline">
                    @csrf
                    @if($match)
                        <input type="hidden" name="match_id" value="{{ $match->id }}">
                    @endif
                    @if($application)
                        <input type="hidden" name="application_id" value="{{ $application->id }}">
                    @endif
                    <button type="submit" class="kt-btn" style="background-color: #10b981 !important; border-color: #10b981 !important; color: white !important;" onmouseover="this.style.backgroundColor='#059669'" onmouseout="this.style.backgroundColor='#10b981'">Accepteren</button>
                </form>
            @endif
        </div>
    </div>
</div>

<div class="kt-container-fixed mt-5">
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
        <!-- Left Column -->
        <div class="space-y-5">
            <!-- Persoonlijke informatie -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Persoonlijke informatie</h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="text-secondary-foreground font-normal" style="width: calc(var(--spacing) * 50);">Voornaam</td>
                            <td class="text-foreground font-normal">{{ $candidate->first_name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal" style="width: calc(var(--spacing) * 50);">Achternaam</td>
                            <td class="text-foreground font-normal">{{ $candidate->last_name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal" style="width: calc(var(--spacing) * 50);">E-mail</td>
                            <td class="text-foreground font-normal">{{ $candidate->email ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal" style="width: calc(var(--spacing) * 50);">Telefoon</td>
                            <td class="text-foreground font-normal">{{ $candidate->phone ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal" style="width: calc(var(--spacing) * 50);">Geboortedatum</td>
                            <td class="text-foreground font-normal">{{ optional($candidate->date_of_birth)->format('d-m-Y') ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal" style="width: calc(var(--spacing) * 50);">Adres</td>
                            <td class="text-foreground font-normal">
                                @if($candidate->address || $candidate->postal_code || $candidate->city)
                                    {{ $candidate->address }}<br>
                                    {{ $candidate->postal_code }} {{ $candidate->city }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Professionele informatie -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Professionele informatie</h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="text-secondary-foreground font-normal" style="width: calc(var(--spacing) * 50);">Huidige functie</td>
                            <td class="text-foreground font-normal">{{ $candidate->current_position ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal" style="width: calc(var(--spacing) * 50);">Gewenste functie</td>
                            <td class="text-foreground font-normal">{{ $candidate->desired_position ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal" style="width: calc(var(--spacing) * 50);">Ervaring (jaren)</td>
                            <td class="text-foreground font-normal">{{ $candidate->experience_years ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal" style="width: calc(var(--spacing) * 50);">Opleidingsniveau</td>
                            <td class="text-foreground font-normal">{{ $candidate->education_level_display ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal" style="width: calc(var(--spacing) * 50);">Beschikbaarheid</td>
                            <td class="text-foreground font-normal">{{ $candidate->availability_display ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal" style="width: calc(var(--spacing) * 50);">Voorkeur werktype</td>
                            <td class="text-foreground font-normal">{{ $candidate->work_type_display ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal" style="width: calc(var(--spacing) * 50);">Salarisverwachting</td>
                            <td class="text-foreground font-normal">@if($candidate->salary_expectation)€ {{ number_format($candidate->salary_expectation, 2, ',', '.') }}@else-@endif</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Workflow Afspraken -->
            @php
                $scheduledStages = $stageInstances->filter(function($stage) {
                    return $stage->scheduled_at !== null;
                })->sortBy('scheduled_at');
            @endphp
            
            @if($scheduledStages->isNotEmpty())
            <div class="kt-card">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Afspraken</h3>
                </div>
                <div class="kt-card-content">
                    <div class="space-y-4">
                        @foreach($scheduledStages as $index => $stage)
                            <div class="not-last:border-b border-border pb-4 {{ $index > 0 ? 'pt-4' : '' }}">
                                <div class="mb-3">
                                    <h4 class="text-base text-mono font-semibold">
                                        {{ $stage->label }}@if($stage->scheduled_at) <span class="text-sm text-muted-foreground font-normal">- {{ $stage->scheduled_at->format('d-m-Y H:i') }}</span>@endif
                                    </h4>
                                </div>
                                <div class="text-secondary-foreground text-base">
                                    <div class="space-y-2 text-sm">
                                        @if($stage->scheduled_at)
                                            <div class="flex items-start gap-2">
                                                <span class="text-muted-foreground w-36">Datum & Tijd:</span>
                                                <span class="text-foreground">{{ $stage->scheduled_at->format('d-m-Y H:i') }}</span>
                                            </div>
                                        @endif
                                        @if($stage->location)
                                            <div class="flex items-start gap-2">
                                                <span class="text-muted-foreground w-36">Locatie:</span>
                                                <span class="text-foreground">
                                                    @php
                                                        $locationParts = explode(' - ', $stage->location, 2);
                                                        $locationName = $locationParts[0];
                                                        $locationAddress = isset($locationParts[1]) ? $locationParts[1] : '';
                                                    @endphp
                                                    {{ $locationName }}
                                                    @if($locationAddress)
                                                        <br><span class="text-xs text-muted-foreground">{{ $locationAddress }}</span>
                                                    @endif
                                                </span>
                                            </div>
                                        @endif
                                        @if($stage->interviewer_name)
                                            <div class="flex items-start gap-2">
                                                <span class="text-muted-foreground w-36">Interviewer:</span>
                                                <span class="text-foreground">{{ $stage->interviewer_name }}</span>
                                            </div>
                                        @endif
                                        @if($stage->type)
                                            <div class="flex items-start gap-2">
                                                <span class="text-muted-foreground w-36">Type:</span>
                                                <span class="text-foreground">
                                                    @php
                                                        $typeMap = [
                                                            'phone' => 'Telefoon',
                                                            'video' => 'Microsoft Teams / Zoom',
                                                            'onsite' => 'Op locatie',
                                                            'assessment' => 'Assessment',
                                                            'final' => 'Eindgesprek',
                                                        ];
                                                    @endphp
                                                    {{ $typeMap[$stage->type] ?? ucfirst($stage->type) }}
                                                </span>
                                            </div>
                                        @endif
                                        @if($stage->duration)
                                            <div class="flex items-start gap-2">
                                                <span class="text-muted-foreground w-36">Duur:</span>
                                                <span class="text-foreground">{{ $stage->duration }} minuten</span>
                                            </div>
                                        @endif
                                        @if($stage->notes)
                                            <div class="flex items-start gap-2">
                                                <span class="text-muted-foreground w-36">Notities:</span>
                                                <span class="text-foreground">{{ $stage->notes }}</span>
                                            </div>
                                        @endif
                                        @php
                                            $statusLabels = [
                                                'PENDING' => 'In afwachting',
                                                'SCHEDULED' => 'Ingepland',
                                                'IN_PROGRESS' => 'Bezig',
                                                'COMPLETED' => 'Voltooid',
                                                'SKIPPED' => 'Overgeslagen',
                                                'CANCELED' => 'Geannuleerd',
                                            ];
                                            $statusLabel = $statusLabels[$stage->status] ?? $stage->status;
                                            $statusColors = [
                                                'PENDING' => 'secondary',
                                                'SCHEDULED' => 'info',
                                                'IN_PROGRESS' => 'warning',
                                                'COMPLETED' => 'success',
                                                'SKIPPED' => 'muted',
                                                'CANCELED' => 'danger',
                                            ];
                                            $statusColor = $statusColors[$stage->status] ?? 'secondary';
                                        @endphp
                                        <div class="flex items-start gap-2">
                                            <span class="text-muted-foreground w-36">Status:</span>
                                            <span class="kt-badge kt-badge-sm kt-badge-{{ $statusColor }}">{{ $statusLabel }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

        </div>

        <!-- Right Column -->
        <div class="space-y-5">
            <!-- Sollicitatie Workflow -->
            @if($stageInstances->isEmpty() && $availableTemplates->isNotEmpty())
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">Sollicitatie Workflow</h3>
                    </div>
                    <div class="kt-card-content">
                        <p class="text-sm text-muted-foreground mb-4">Nog geen workflow geïnitialiseerd. Kies een template om te starten:</p>
                        <form action="{{ route('admin.stage-instances.initialize', ['type' => $type === 'match' ? 'match' : 'application', 'id' => $match ? $match->id : $application->id]) }}" method="POST">
                            @csrf
                            <div class="flex flex-col gap-2">
                                <select name="pipeline_template_id" class="kt-input" required>
                                    <option value="">Selecteer een template</option>
                                    @foreach($availableTemplates as $template)
                                        <option value="{{ $template->id }}">{{ $template->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="kt-btn kt-btn-primary">
                                    <i class="ki-filled ki-play me-2"></i>
                                    Start Workflow
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @elseif($stageInstances->isNotEmpty())
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">Sollicitatie Workflow</h3>
                        @if($pipelineTemplate)
                            <span class="text-xs text-muted-foreground">{{ $pipelineTemplate->name }}</span>
                        @endif
                    </div>
                    <div class="kt-card-content">
                        <div class="space-y-3">
                            @foreach($stageInstances as $stage)
                                @php
                                    $statusColors = [
                                        'PENDING' => 'secondary',
                                        'SCHEDULED' => 'info',
                                        'IN_PROGRESS' => 'warning',
                                        'COMPLETED' => 'success',
                                        'SKIPPED' => 'muted',
                                        'CANCELED' => 'danger',
                                    ];
                                    $statusLabels = [
                                        'PENDING' => 'In afwachting',
                                        'SCHEDULED' => 'Ingepland',
                                        'IN_PROGRESS' => 'Bezig',
                                        'COMPLETED' => 'Voltooid',
                                        'SKIPPED' => 'Overgeslagen',
                                        'CANCELED' => 'Geannuleerd',
                                    ];
                                    $statusColor = $statusColors[$stage->status] ?? 'secondary';
                                    $statusLabel = $statusLabels[$stage->status] ?? $stage->status;
                                @endphp
                                <div class="flex items-start gap-3 p-3 border border-border rounded-lg {{ $stage->status === 'COMPLETED' ? 'bg-success/5' : ($stage->status === 'IN_PROGRESS' ? 'bg-warning/5' : '') }}">
                                    <div class="flex-shrink-0">
                                        @php
                                            // Stage type icons (fallback when no status icon)
                                            $stageTypeIcons = [
                                                'SOURCE' => 'ki-user',
                                                'SCREENING' => 'ki-file',
                                                'CV_SCREENING' => 'ki-file',
                                                'PHONE_SCREEN' => 'ki-phone',
                                                'INTAKE' => 'ki-chat',
                                                'TEAM_INTERVIEW' => 'ki-people',
                                                'TECHNICAL_INTERVIEW' => 'ki-code',
                                                'FINAL_INTERVIEW' => 'ki-star',
                                                'ASSESSMENT' => 'ki-clipboard',
                                                'REFERENCE_CHECK' => 'ki-verify',
                                                'SALARY_NEGOTIATION' => 'ki-dollar',
                                                'OFFER' => 'ki-handshake',
                                                'SIGNING' => 'ki-document',
                                                'ONBOARDING' => 'ki-rocket',
                                                'REJECTION' => 'ki-cross-circle',
                                                'WITHDRAWN' => 'ki-arrow-left',
                                            ];
                                            $stageIcon = $stageTypeIcons[$stage->stage_type_key] ?? 'ki-circle';
                                            
                                            // Status-based icon color
                                            $iconColor = 'text-muted-foreground';
                                            if ($stage->status === 'COMPLETED') {
                                                $iconColor = 'text-success';
                                            } elseif ($stage->status === 'IN_PROGRESS') {
                                                $iconColor = 'text-warning';
                                            } elseif ($stage->status === 'SCHEDULED') {
                                                $iconColor = 'text-info';
                                            }
                                        @endphp
                                        
                                        @if($stage->status === 'COMPLETED')
                                            <i class="ki-filled ki-check-circle text-success text-xl"></i>
                                        @elseif($stage->status === 'IN_PROGRESS')
                                            <i class="ki-filled ki-time text-warning text-xl"></i>
                                        @elseif($stage->status === 'SCHEDULED')
                                            <i class="ki-filled ki-calendar text-info text-xl"></i>
                                        @else
                                            <i class="ki-filled {{ $stageIcon }} {{ $iconColor }} text-xl"></i>
                                        @endif
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="font-semibold text-sm">{{ $stage->label }}</span>
                                            <span class="kt-badge kt-badge-sm kt-badge-{{ $statusColor }}">{{ $statusLabel }}</span>
                                        </div>
                                        @php
                                            $scheduledDisplay = null;
                                            if ($stage->scheduled_at) {
                                                $scheduledDisplay = $stage->scheduled_at->format('d-m-Y H:i');
                                            } elseif (!empty($stage->scheduled_time)) {
                                                $scheduledDisplay = $stage->scheduled_time;
                                            } elseif (!empty($stage->artifacts['scheduled_time'] ?? null)) {
                                                $scheduledDisplay = $stage->artifacts['scheduled_time'];
                                            }
                                        @endphp
                                        @if($scheduledDisplay)
                                            <p class="text-xs text-muted-foreground">{{ $scheduledDisplay }}</p>
                                        @endif
                                        @if($stage->outcome)
                                            @php
                                                $outcomeLabels = [
                                                    'PASS' => 'Geslaagd',
                                                    'FAIL' => 'Niet geslaagd',
                                                    'ON_HOLD' => 'On hold',
                                                    'ACCEPTED' => 'Geaccepteerd',
                                                    'DECLINED' => 'Afgewezen',
                                                ];
                                                $outcomeLabel = $outcomeLabels[$stage->outcome] ?? $stage->outcome;
                                            @endphp
                                            <p class="text-xs text-muted-foreground">Uitkomst: {{ $outcomeLabel }}</p>
                                        @endif
                            @if($stage->location)
                                <p class="text-xs text-muted-foreground">
                                    Locatie: 
                                    @php
                                        $locationParts = explode(' - ', $stage->location, 2);
                                        $locationName = $locationParts[0];
                                        $locationAddress = isset($locationParts[1]) ? $locationParts[1] : '';
                                    @endphp
                                    {{ $locationName }}
                                    @if($locationAddress)
                                        <br>{{ $locationAddress }}
                                    @endif
                                </p>
                            @endif
                            @if($stage->interviewer_name)
                                <p class="text-xs text-muted-foreground">Interviewer: {{ $stage->interviewer_name }}</p>
                            @endif
                                    </div>
                                    @if($stage->status !== 'CANCELED')
                                        <button type="button" class="kt-btn kt-btn-sm kt-btn-primary" onclick="openStageModal({{ $stage->id }})">
                                            Beheren
                                        </button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Vacancy Information -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Vacature</h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="text-secondary-foreground font-normal" style="width: calc(var(--spacing) * 50);">Titel</td>
                            <td class="text-foreground font-normal">
                                <a href="{{ route('admin.skillmatching.vacancies.show', $vacancy) }}" class="text-primary hover:underline">{{ $vacancy->title }}</a>
                            </td>
                        </tr>
                        @if($vacancy->company)
                        <tr>
                            <td class="text-secondary-foreground font-normal" style="width: calc(var(--spacing) * 50);">Bedrijf</td>
                            <td class="text-foreground font-normal">{{ $vacancy->company->name }}</td>
                        </tr>
                        @endif
                        @if($vacancy->location)
                        <tr>
                            <td class="text-secondary-foreground font-normal" style="width: calc(var(--spacing) * 50);">Locatie</td>
                            <td class="text-foreground font-normal">{{ $vacancy->location }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            <!-- Match Information -->
            @if($match)
            <div class="kt-card">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Match informatie</h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="text-secondary-foreground font-normal" style="width: calc(var(--spacing) * 50);">Match Score</td>
                            <td class="text-foreground font-normal">
                                @php
                                    $score = $match->match_score ?? 0;
                                    $scoreClass = $score >= 70 ? 'match-score-high' : ($score >= 40 ? 'match-score-medium' : 'match-score-low');
                                @endphp
                                <span class="text-2xl font-bold {{ $scoreClass }}">{{ number_format($score, 0) }}%</span>
                            </td>
                        </tr>
                        @if($match->ai_recommendation)
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top" style="width: calc(var(--spacing) * 50);">AI Aanbeveling</td>
                            <td class="text-foreground font-normal">{{ $match->ai_recommendation }}</td>
                        </tr>
                        @endif
                        @if($match->status)
                        <tr>
                            <td class="text-secondary-foreground font-normal" style="width: calc(var(--spacing) * 50);">Status</td>
                            <td class="text-foreground font-normal">
                                @php
                                    $statusMap = [
                                        'pending' => ['label' => 'In behandeling', 'color' => 'warning'],
                                        'accepted' => ['label' => 'Geaccepteerd', 'color' => 'success'],
                                        'rejected' => ['label' => 'Afgewezen', 'color' => 'danger'],
                                        'interview' => ['label' => 'Interview', 'color' => 'info'],
                                        'interview_scheduled' => ['label' => 'Interview gepland', 'color' => 'info'],
                                    ];
                                    $statusInfo = $statusMap[$match->status] ?? ['label' => ucfirst($match->status), 'color' => 'secondary'];
                                @endphp
                                <span class="kt-badge kt-badge-sm kt-badge-{{ $statusInfo['color'] }}">{{ $statusInfo['label'] }}</span>
                            </td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
            @endif

            <!-- Timeline -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Tijdlijn</h3>
                    <div class="flex items-center gap-2">
                        <label class="group text-2sm font-medium inline-flex items-center gap-2">
                            <span class="inline-flex items-center gap-2">
                                Auto refresh:
                                <span class="group-has-checked:hidden">Off</span>
                                <span class="hidden group-has-checked:inline">On</span>
                            </span>
                            <input checked class="kt-switch kt-switch-sm" name="timeline_auto_refresh" type="checkbox" value="1" id="timeline_auto_refresh">
                        </label>
                    </div>
                </div>
                <div class="kt-card-content" id="timeline_content">
                    @if(empty($timeline))
                        <div class="text-center py-8 text-muted-foreground">
                            <p class="font-medium">Nog geen activiteiten beschikbaar.</p>
                            <p class="text-sm">De tijdlijn toont hier alle actie met de kandidaat vanaf de sollicitatie.</p>
                        </div>
                    @else
                        <div class="flex flex-col">
                            @foreach($timeline as $index => $item)
                                <div class="flex items-start relative">
                                    @if($index < count($timeline) - 1)
                                        <div class="w-9 start-0 top-9 absolute bottom-0 rtl:-translate-x-1/2 translate-x-1/2 border-s border-s-input"></div>
                                    @endif
                                    <div class="flex items-center justify-center shrink-0 rounded-full bg-accent/60 border border-input size-9 text-secondary-foreground">
                                        <i class="ki-filled {{ $item['icon'] }} text-base"></i>
                                    </div>
                                    <div class="ps-2.5 mb-7 text-base grow">
                                        <div class="flex flex-col">
                                            <div class="text-sm text-foreground">
                                                {{ $item['title'] }}
                                                @if(!empty($item['description']))
                                                    <span class="text-secondary-foreground"> - {{ $item['description'] }}</span>
                                                @endif
                                            </div>
                                            <span class="text-xs text-secondary-foreground">
                                                {{ $item['date']->diffForHumans() }} ({{ $item['date']->format('d-m-Y H:i') }})
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Interview Modal -->
<div class="kt-modal" data-kt-modal="true" id="interview_modal" tabindex="-1" style="display: none; z-index: 9999;">
    <div class="kt-modal-dialog kt-modal-dialog-centered" style="display: none;">
        <div class="kt-modal-content">
            <form action="{{ route('admin.skillmatching.vacancies.candidate.interview', ['vacancy' => $vacancy->id, 'candidate' => $candidate->id]) }}" method="POST" novalidate>
                @csrf
                @if($match)
                    <input type="hidden" name="match_id" value="{{ $match->id }}">
                @endif
                @if($application)
                    <input type="hidden" name="application_id" value="{{ $application->id }}">
                @endif
                <div class="kt-modal-header">
                    <h2 class="kt-modal-title">Interview inplannen</h2>
                    <button type="button" class="kt-btn kt-btn-icon kt-btn-sm" data-kt-modal-dismiss="true">
                        <i class="ki-filled ki-cross"></i>
                    </button>
                </div>
                <div class="kt-modal-body">
                    <div class="kt-card-table kt-scrollable-x-auto pb-3">
                        <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                            <tr>
                                <td class="min-w-36 text-secondary-foreground font-normal">Datum *</td>
                                <td class="text-foreground font-normal">
                                    <div class="kt-input">
                                        <i class="ki-outline ki-calendar"></i>
                                        <input class="grow"
                                               id="scheduled_at_display"
                                               data-kt-date-picker="true"
                                               data-kt-date-picker-input-mode="true"
                                               data-kt-date-picker-position-to-input="left"
                                               data-kt-date-picker-format="dd-mm-yyyy"
                                               placeholder="Selecteer datum"
                                               readonly
                                               type="text"
                                               required/>
                                        <input type="hidden"
                                               name="scheduled_at"
                                               id="scheduled_at_hidden"
                                               value=""/>
                                        <input type="hidden"
                                               name="scheduled_at_hidden"
                                               id="scheduled_at_hidden_alt"
                                               value=""/>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-36 text-secondary-foreground font-normal">Tijd *</td>
                                <td class="text-foreground font-normal">
                                    <input type="text"
                                           name="scheduled_time"
                                           id="scheduled_time"
                                           class="kt-input"
                                           placeholder="hh:mm"
                                           maxlength="5"
                                           pattern="[0-9]{2}:[0-9]{2}"
                                           required>
                                    <small class="text-muted-foreground text-xs mt-1 block">Voer tijd in als hh:mm (bijv. 14:30)</small>
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-36 text-secondary-foreground font-normal">Type *</td>
                                <td class="text-foreground font-normal">
                                    <select name="type" class="kt-input" required>
                                        <option value="phone">Telefoon</option>
                                        <option value="video">Microsoft Teams / Zoom</option>
                                        <option value="onsite">Op locatie</option>
                                        <option value="assessment">Assessment</option>
                                        <option value="final">Eindgesprek</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-36 text-secondary-foreground font-normal">Duur (minuten) *</td>
                                <td class="text-foreground font-normal">
                                    <input type="number" name="duration" class="kt-input" value="60" min="15" max="480" required>
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-36 text-secondary-foreground font-normal">Locatie *</td>
                                <td class="text-foreground font-normal">
                                    <select name="location_type" id="location_type" class="kt-input" required>
                                        <option value="">Selecteer locatie</option>
                                        <option value="online">Online / Digitaal</option>
                                        @if($company)
                                            @php
                                                $mainLocation = $company->mainLocation;
                                                $hasMainLocationInList = $mainLocation && $companyLocations->contains('id', $mainLocation->id);
                                                $hasCompanyAddress = $company->street || $company->city;
                                            @endphp
                                            @if($mainLocation && !$hasMainLocationInList)
                                                @php
                                                    $mainAddress = trim(($mainLocation->street ?? '') . ' ' . ($mainLocation->house_number ?? '') . ($mainLocation->house_number_extension ? '-' . $mainLocation->house_number_extension : ''));
                                                    $mainAddress = trim($mainAddress . ' ' . ($mainLocation->postal_code ?? '') . ' ' . ($mainLocation->city ?? ''));
                                                    $mainDisplayName = $mainLocation->name;
                                                    if ($mainLocation->city) {
                                                        $mainDisplayName .= ' - ' . $mainLocation->city;
                                                    }
                                                    $mainDisplayName .= ' (Hoofdadres)';
                                                @endphp
                                                <option value="{{ $mainLocation->id }}" data-name="{{ $mainLocation->name }}" data-address="{{ $mainAddress }}">{{ $mainDisplayName }}</option>
                                            @elseif(!$mainLocation && $hasCompanyAddress)
                                                @php
                                                    $companyAddress = trim(($company->street ?? '') . ' ' . ($company->house_number ?? '') . ($company->house_number_extension ? '-' . $company->house_number_extension : ''));
                                                    $companyAddress = trim($companyAddress . ' ' . ($company->postal_code ?? '') . ' ' . ($company->city ?? ''));
                                                    $companyDisplayName = $company->name;
                                                    if ($company->city) {
                                                        $companyDisplayName .= ' - ' . $company->city;
                                                    }
                                                    $companyDisplayName .= ' (Hoofdadres)';
                                                @endphp
                                                <option value="company_main" data-name="{{ $company->name }}" data-address="{{ $companyAddress }}">{{ $companyDisplayName }}</option>
                                            @endif
                                        @endif
                                        @foreach($companyLocations as $location)
                                            @php
                                                $isMain = $mainLocation && $location->id === $mainLocation->id;
                                                $locationAddress = trim(($location->street ?? '') . ' ' . ($location->house_number ?? '') . ($location->house_number_extension ? '-' . $location->house_number_extension : ''));
                                                $locationAddress = trim($locationAddress . ' ' . ($location->postal_code ?? '') . ' ' . ($location->city ?? ''));
                                                $locationDisplayName = $location->name;
                                                if ($location->city) {
                                                    $locationDisplayName .= ' - ' . $location->city;
                                                }
                                                if ($isMain) {
                                                    $locationDisplayName .= ' (Hoofdadres)';
                                                }
                                            @endphp
                                            <option value="{{ $location->id }}" data-name="{{ $location->name }}" data-address="{{ $locationAddress }}">{{ $locationDisplayName }}</option>
                                        @endforeach
                                        <option value="other">Anders</option>
                                    </select>
                                    <input type="text" name="location" id="location_custom" class="kt-input mt-2 hidden" placeholder="Adres">
                                    <input type="hidden" name="company_location_id" id="company_location_id">
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-36 text-secondary-foreground font-normal align-top">Interviewer *</td>
                                <td class="text-foreground font-normal">
                                    <select name="interviewer_id" id="interviewer_id" class="kt-input" required>
                                        <option value="">Selecteer interviewer</option>
                                        @foreach($companyUsers as $user)
                                            <option value="{{ $user->id }}" data-name="{{ $user->first_name }} {{ $user->last_name }}" data-email="{{ $user->email }}">{{ $user->first_name }} {{ $user->last_name }}</option>
                                        @endforeach
                                        <option value="other">Anders</option>
                                    </select>
                                    <input type="text"
                                           name="interviewer_name_custom"
                                           id="interviewer_name_custom"
                                           class="kt-input mt-2 hidden"
                                           placeholder="Naam interviewer"
                                           minlength="2"
                                           maxlength="255"
                                           pattern="[a-zA-ZÀ-ÿĀ-žА-яа-я\s\-'\.]+">
                                    <div id="interviewer_name_custom_error" class="text-xs mt-1 hidden text-red-500"></div>
                                    <input type="hidden" name="interviewer_name" id="interviewer_name">
                                    <input type="hidden" name="user_id" id="user_id">
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-36 text-secondary-foreground font-normal">Interviewer e-mail</td>
                                <td class="text-foreground font-normal">
                                    <div class="relative" style="position: relative; width: 100%;">
                                        <input type="email"
                                               name="interviewer_email"
                                               id="interviewer_email"
                                               class="kt-input"
                                               pattern="[a-zA-Z0-9._%25+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}"
                                               placeholder="voorbeeld@email.nl"
                                               readonly
                                               style="padding-right: 2.75rem !important; width: 100%;">
                                        <div id="interviewer_email_icon"
                                             class="absolute hidden"
                                             style="position: absolute !important; right: 0.75rem !important; top: 50% !important; transform: translateY(-50%) !important; pointer-events: none !important; z-index: 10 !important; display: flex !important; align-items: center !important; justify-content: center !important; width: 1.25rem !important; height: 1.25rem !important;"></div>
                                    </div>
                                    <div id="interviewer_email_error" class="text-xs mt-1 hidden text-red-500"></div>
                                    <small class="text-muted-foreground text-xs mt-1 block">E-mailadres is verplicht bij andere interviewer</small>
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-36 text-secondary-foreground font-normal align-top">Notities</td>
                                <td class="text-foreground font-normal">
                                    <textarea name="notes" class="kt-input" rows="4"></textarea>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="kt-modal-footer">
                    <button type="button" class="kt-btn kt-btn-outline" data-kt-modal-dismiss="true">Annuleren</button>
                    <button type="submit" class="kt-btn kt-btn-primary">Interview inplannen</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="kt-modal" data-kt-modal="true" id="reject_modal" tabindex="-1" style="display: none; z-index: 9999;">
    <div class="kt-modal-dialog kt-modal-dialog-centered" style="display: none;">
        <div class="kt-modal-content">
            <form action="{{ route('admin.skillmatching.vacancies.candidate.reject', ['vacancy' => $vacancy->id, 'candidate' => $candidate->id]) }}" method="POST">
                @csrf
                @if($match)
                    <input type="hidden" name="match_id" value="{{ $match->id }}">
                @endif
                @if($application)
                    <input type="hidden" name="application_id" value="{{ $application->id }}">
                @endif
                <div class="kt-modal-header">
                    <h2 class="kt-modal-title">Kandidaat afwijzen</h2>
                    <button type="button" class="kt-btn kt-btn-icon kt-btn-sm" data-kt-modal-dismiss="true">
                        <i class="ki-filled ki-cross"></i>
                    </button>
                </div>
                <div class="kt-modal-body">
                    <div class="kt-card-table kt-scrollable-x-auto pb-3">
                        <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                            <tr>
                                <td class="min-w-36 text-secondary-foreground font-normal align-top">Reden</td>
                                <td class="text-foreground font-normal">
                                    <textarea name="reason" class="kt-input" rows="4" required placeholder="Geef een reden op voor de afwijzing..."></textarea>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="kt-modal-footer">
                    <button type="button" class="kt-btn kt-btn-outline" data-kt-modal-dismiss="true">Annuleren</button>
                    <button type="submit" class="kt-btn" style="background-color: #dc2626 !important; border-color: #dc2626 !important; color: white !important;" onmouseover="this.style.backgroundColor='#b91c1c'" onmouseout="this.style.backgroundColor='#dc2626'">Afwijzen en e-mail versturen</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Interview Modal -->
@if($latestInterview)
<div class="kt-modal" data-kt-modal="true" id="edit_interview_modal" tabindex="-1" style="display: none; z-index: 9999;">
    <div class="kt-modal-dialog kt-modal-dialog-centered" style="display: none;">
        <div class="kt-modal-content">
            <form action="{{ route('admin.skillmatching.vacancies.candidate.interview.update', ['vacancy' => $vacancy->id, 'candidate' => $candidate->id, 'interview' => $latestInterview->id]) }}" method="POST" novalidate id="edit_interview_form" data-interview-status="{{ $latestInterview->status }}">
                @csrf
                @method('PUT')
                @if($match)
                    <input type="hidden" name="match_id" value="{{ $match->id }}">
                @endif
                <div class="kt-modal-header">
                    <h2 class="kt-modal-title">Interview bewerken</h2>
                    <button type="button" class="kt-btn kt-btn-icon kt-btn-sm" data-kt-modal-dismiss="true">
                        <i class="ki-filled ki-cross"></i>
                    </button>
                </div>
                <div class="kt-modal-body">
                    <div class="kt-card-table kt-scrollable-x-auto pb-3">
                        <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                            <tr>
                                <td class="min-w-36 text-secondary-foreground font-normal">Datum *</td>
                                <td class="text-foreground font-normal">
                                    <div class="kt-input">
                                        <i class="ki-outline ki-calendar"></i>
                                        <input class="grow"
                                               id="edit_scheduled_at_display"
                                               data-kt-date-picker="true"
                                               data-kt-date-picker-input-mode="true"
                                               data-kt-date-picker-position-to-input="left"
                                               data-kt-date-picker-format="dd-mm-yyyy"
                                               placeholder="Selecteer datum"
                                               readonly
                                               type="text"
                                               value="{{ $latestInterview->scheduled_at ? $latestInterview->scheduled_at->format('d-m-Y') : '' }}"
                                               required/>
                                        <input type="hidden"
                                               name="scheduled_at"
                                               id="edit_scheduled_at_hidden"
                                               value="{{ $latestInterview->scheduled_at ? $latestInterview->scheduled_at->format('Y-m-d') : '' }}"/>
                                        <input type="hidden"
                                               name="scheduled_at_hidden"
                                               id="edit_scheduled_at_hidden_alt"
                                               value="{{ $latestInterview->scheduled_at ? $latestInterview->scheduled_at->format('Y-m-d') : '' }}"/>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-36 text-secondary-foreground font-normal">Tijd *</td>
                                <td class="text-foreground font-normal">
                                    <input type="text"
                                           name="scheduled_time"
                                           id="edit_scheduled_time"
                                           class="kt-input"
                                           placeholder="hh:mm"
                                           maxlength="5"
                                           pattern="[0-9]{2}:[0-9]{2}"
                                           value="{{ $latestInterview->scheduled_at ? $latestInterview->scheduled_at->format('H:i') : '' }}"
                                           required>
                                    <small class="text-muted-foreground text-xs mt-1 block">Voer tijd in als hh:mm (bijv. 14:30)</small>
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-36 text-secondary-foreground font-normal">Type *</td>
                                <td class="text-foreground font-normal">
                                    <select name="type" id="edit_type" class="kt-input" required>
                                        <option value="phone" {{ $latestInterview->type === 'phone' ? 'selected' : '' }}>Telefoon</option>
                                        <option value="video" {{ $latestInterview->type === 'video' ? 'selected' : '' }}>Microsoft Teams / Zoom</option>
                                        <option value="onsite" {{ $latestInterview->type === 'onsite' ? 'selected' : '' }}>Op locatie</option>
                                        <option value="assessment" {{ $latestInterview->type === 'assessment' ? 'selected' : '' }}>Assessment</option>
                                        <option value="final" {{ $latestInterview->type === 'final' ? 'selected' : '' }}>Eindgesprek</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-36 text-secondary-foreground font-normal">Duur (minuten) *</td>
                                <td class="text-foreground font-normal">
                                    <input type="number" name="duration" id="edit_duration" class="kt-input" value="{{ $latestInterview->duration ?? 60 }}" min="15" max="480" required>
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-36 text-secondary-foreground font-normal">Locatie *</td>
                                <td class="text-foreground font-normal">
                                    <select name="location_type" id="edit_location_type" class="kt-input" required>
                                        <option value="">Selecteer locatie</option>
                                        <option value="online" {{ $latestInterview->location === 'Online / Digitaal' ? 'selected' : '' }}>Online / Digitaal</option>
                                        @if($company)
                                            @php
                                                $mainLocation = $company->mainLocation;
                                                $hasMainLocationInList = $mainLocation && $companyLocations->contains('id', $mainLocation->id);
                                                $hasCompanyAddress = $company->street || $company->city;
                                            @endphp
                                            @if($mainLocation && !$hasMainLocationInList)
                                                @php
                                                    $mainAddress = trim(($mainLocation->street ?? '') . ' ' . ($mainLocation->house_number ?? '') . ($mainLocation->house_number_extension ? '-' . $mainLocation->house_number_extension : ''));
                                                    $mainAddress = trim($mainAddress . ' ' . ($mainLocation->postal_code ?? '') . ' ' . ($mainLocation->city ?? ''));
                                                    $mainDisplayName = $mainLocation->name;
                                                    if ($mainLocation->city) {
                                                        $mainDisplayName .= ' - ' . $mainLocation->city;
                                                    }
                                                    $mainDisplayName .= ' (Hoofdadres)';
                                                @endphp
                                                <option value="{{ $mainLocation->id }}"
                                                        data-name="{{ $mainLocation->name }}"
                                                        data-address="{{ $mainAddress }}"
                                                        {{ $latestInterview->company_location_id == $mainLocation->id ? 'selected' : '' }}>
                                                    {{ $mainDisplayName }}
                                                </option>
                                            @elseif(!$mainLocation && $hasCompanyAddress)
                                                @php
                                                    $companyAddress = trim(($company->street ?? '') . ' ' . ($company->house_number ?? '') . ($company->house_number_extension ? '-' . $company->house_number_extension : ''));
                                                    $companyAddress = trim($companyAddress . ' ' . ($company->postal_code ?? '') . ' ' . ($company->city ?? ''));
                                                    $companyDisplayName = $company->name;
                                                    if ($company->city) {
                                                        $companyDisplayName .= ' - ' . $company->city;
                                                    }
                                                    $companyDisplayName .= ' (Hoofdadres)';
                                                @endphp
                                                <option value="company_main"
                                                        data-name="{{ $company->name }}"
                                                        data-address="{{ $companyAddress }}">
                                                    {{ $companyDisplayName }}
                                                </option>
                                            @endif
                                        @endif
                                        @foreach($companyLocations as $location)
                                            @php
                                                $isMain = $mainLocation && $location->id === $mainLocation->id;
                                                $locationAddress = trim(($location->street ?? '') . ' ' . ($location->house_number ?? '') . ($location->house_number_extension ? '-' . $location->house_number_extension : ''));
                                                $locationAddress = trim($locationAddress . ' ' . ($location->postal_code ?? '') . ' ' . ($location->city ?? ''));
                                                $locationDisplayName = $location->name;
                                                if ($location->city) {
                                                    $locationDisplayName .= ' - ' . $location->city;
                                                }
                                                if ($isMain) {
                                                    $locationDisplayName .= ' (Hoofdadres)';
                                                }
                                            @endphp
                                            <option value="{{ $location->id }}"
                                                    data-name="{{ $location->name }}"
                                                    data-address="{{ $locationAddress }}"
                                                    {{ $latestInterview->company_location_id == $location->id ? 'selected' : '' }}>
                                                {{ $locationDisplayName }}
                                            </option>
                                        @endforeach
                                        <option value="other" {{ $latestInterview->location && $latestInterview->location !== 'Online / Digitaal' && !$latestInterview->company_location_id ? 'selected' : '' }}>Anders</option>
                                    </select>
                                    <input type="text"
                                           name="location"
                                           id="edit_location_custom"
                                           class="kt-input mt-2 {{ $latestInterview->location && $latestInterview->location !== 'Online / Digitaal' && !$latestInterview->company_location_id ? '' : 'hidden' }}"
                                           placeholder="Adres"
                                           value="{{ $latestInterview->location && $latestInterview->location !== 'Online / Digitaal' && !$latestInterview->company_location_id ? $latestInterview->location : '' }}">
                                    <input type="hidden" name="company_location_id" id="edit_company_location_id" value="{{ $latestInterview->company_location_id }}">
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-36 text-secondary-foreground font-normal align-top">Interviewer *</td>
                                <td class="text-foreground font-normal">
                                    <select name="interviewer_id" id="edit_interviewer_id" class="kt-input" required>
                                        <option value="">Selecteer interviewer</option>
                                        @foreach($companyUsers as $user)
                                            <option value="{{ $user->id }}"
                                                    data-name="{{ $user->first_name }} {{ $user->last_name }}"
                                                    data-email="{{ $user->email }}"
                                                    {{ $latestInterview->user_id == $user->id ? 'selected' : '' }}>
                                                {{ $user->first_name }} {{ $user->last_name }}
                                            </option>
                                        @endforeach
                                        <option value="other" {{ !$latestInterview->user_id ? 'selected' : '' }}>Anders</option>
                                    </select>
                                    <input type="text"
                                           name="interviewer_name_custom"
                                           id="edit_interviewer_name_custom"
                                           class="kt-input mt-2 {{ !$latestInterview->user_id ? '' : 'hidden' }}"
                                           placeholder="Naam interviewer"
                                           value="{{ !$latestInterview->user_id ? ($latestInterview->interviewer_name ?? '') : '' }}"
                                           minlength="2"
                                           maxlength="255"
                                           pattern="[a-zA-ZÀ-ÿĀ-žА-яа-я\s\-'\.]+">
                                    <div id="edit_interviewer_name_custom_error" class="text-xs mt-1 hidden text-red-500"></div>
                                    <input type="hidden" name="interviewer_name" id="edit_interviewer_name" value="{{ $latestInterview->interviewer_name }}">
                                    <input type="hidden" name="user_id" id="edit_user_id" value="{{ $latestInterview->user_id }}">
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-36 text-secondary-foreground font-normal">Interviewer e-mail</td>
                                <td class="text-foreground font-normal">
                                    <div class="relative" style="position: relative; width: 100%;">
                                        <input type="email"
                                               name="interviewer_email"
                                               id="edit_interviewer_email"
                                               class="kt-input"
                                               pattern="[a-zA-Z0-9._%25+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}"
                                               placeholder="voorbeeld@email.nl"
                                               value="{{ $latestInterview->interviewer_email ?? '' }}"
                                               {{ !$latestInterview->user_id ? 'required' : 'readonly' }}
                                               style="padding-right: 2.75rem !important; width: 100%;">
                                        <div id="edit_interviewer_email_icon"
                                             class="absolute hidden"
                                             style="position: absolute !important; right: 0.75rem !important; top: 50% !important; transform: translateY(-50%) !important; pointer-events: none !important; z-index: 10 !important; display: flex !important; align-items: center !important; justify-content: center !important; width: 1.25rem !important; height: 1.25rem !important;"></div>
                                    </div>
                                    <div id="edit_interviewer_email_error" class="text-xs mt-1 hidden text-red-500"></div>
                                    <small class="text-muted-foreground text-xs mt-1 block">E-mailadres is verplicht bij andere interviewer</small>
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-36 text-secondary-foreground font-normal align-top">Notities</td>
                                <td class="text-foreground font-normal">
                                    <textarea name="notes" id="edit_notes" class="kt-input" rows="4">{{ $latestInterview->notes ?? '' }}</textarea>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="kt-modal-footer">
                    <button type="button" class="kt-btn kt-btn-outline" data-kt-modal-dismiss="true">Annuleren</button>
                    <button type="submit" class="kt-btn kt-btn-primary">Interview bijwerken</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Cancel Interview Modal -->
@if($latestInterview && $latestInterview->status !== 'cancelled')
<div class="kt-modal" data-kt-modal="true" id="cancel_interview_modal" tabindex="-1" style="display: none; z-index: 9999;">
    <div class="kt-modal-dialog kt-modal-dialog-centered" style="display: none;">
        <div class="kt-modal-content">
            <div class="kt-modal-header">
                <h3 class="kt-modal-title">Interview annuleren</h3>
                <button type="button" class="kt-btn kt-btn-icon kt-btn-sm" data-kt-modal-dismiss="true">
                    <i class="ki-filled ki-cross"></i>
                </button>
            </div>
            <form action="{{ route('admin.skillmatching.vacancies.candidate.interview.cancel', ['vacancy' => $vacancy->id, 'candidate' => $candidate->id, 'interview' => $latestInterview->id]) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="kt-modal-body">
                    <p class="text-foreground">Weet u zeker dat u dit interview wilt annuleren?</p>
                    <p class="text-sm text-muted-foreground mt-2">Het interview wordt niet verwijderd, maar de status wordt aangepast naar "Geannuleerd".</p>
                </div>
                <div class="kt-modal-footer">
                    <button type="button" class="kt-btn" data-kt-modal-dismiss="true" style="background-color: #16a34a !important; border-color: #16a34a !important; color: white !important;" onmouseover="this.style.backgroundColor='#15803d'" onmouseout="this.style.backgroundColor='#16a34a'">Nee, behouden</button>
                    <button type="submit" class="kt-btn" style="background-color: #dc2626 !important; border-color: #dc2626 !important; color: white !important;" onmouseover="this.style.backgroundColor='#b91c1c'" onmouseout="this.style.backgroundColor='#dc2626'">Ja, annuleren</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Stage Management Modal -->
<div class="kt-modal" data-kt-modal="true" id="stage_modal" tabindex="-1" style="display: none; z-index: 9999;">
    <div class="kt-modal-dialog kt-modal-dialog-centered" style="display: none; max-width: 1080px; width: 100%;">
        <div class="kt-modal-content">
            <form id="stage_form" method="POST" novalidate>
                @csrf
                <input type="hidden" name="_method" value="PUT">
                <div class="kt-modal-header">
                    <h2 class="kt-modal-title">Stage Beheren</h2>
                    <button type="button" class="kt-btn kt-btn-icon kt-btn-sm" data-kt-modal-dismiss="true">
                        <i class="ki-filled ki-cross"></i>
                    </button>
                </div>
                <div class="kt-modal-body">
                    <div class="kt-card-table kt-scrollable-x-auto pb-3">
                        <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                            <tr>
                                <td class="min-w-36 text-secondary-foreground font-normal">Status *</td>
                                <td class="text-foreground font-normal">
                                    <select name="status" id="stage_status" class="kt-input" required>
                                        <option value="PENDING">In afwachting</option>
                                        <option value="SCHEDULED">Ingepland</option>
                                        <option value="IN_PROGRESS">Bezig</option>
                                        <option value="COMPLETED">Voltooid</option>
                                        <option value="SKIPPED">Overgeslagen</option>
                                        <option value="CANCELED">Geannuleerd</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-36 text-secondary-foreground font-normal">Uitkomst</td>
                                <td class="text-foreground font-normal">
                                    <select name="outcome" id="stage_outcome" class="kt-input">
                                        <option value="">Geen</option>
                                        <option value="PASS">Geslaagd</option>
                                        <option value="FAIL">Niet geslaagd</option>
                                        <option value="ON_HOLD">On hold</option>
                                        <option value="ACCEPTED">Geaccepteerd</option>
                                        <option value="DECLINED">Afgewezen</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-36 text-secondary-foreground font-normal">Type</td>
                                <td class="text-foreground font-normal">
                                    <select name="type" id="stage_type_input" class="kt-input">
                                        <option value="">Selecteer type</option>
                                        <option value="phone">Telefoon</option>
                                        <option value="video">Microsoft Teams / Zoom</option>
                                        <option value="onsite">Op locatie</option>
                                        <option value="assessment">Assessment</option>
                                        <option value="final">Eindgesprek</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-36 text-secondary-foreground font-normal">Duur (minuten)</td>
                                <td class="text-foreground font-normal">
                                    <input type="number" name="duration" id="stage_duration" class="kt-input" min="5" max="480">
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-36 text-secondary-foreground font-normal">Locatie</td>
                                <td class="text-foreground font-normal">
                                    <select name="location_type" id="stage_location_type" class="kt-input">
                                        <option value="">Selecteer locatie</option>
                                        <option value="online">Online / Digitaal</option>
                                        @if($company)
                                            @php
                                                $mainLocation = $company->mainLocation;
                                                $hasMainLocationInList = $mainLocation && $companyLocations->contains('id', $mainLocation->id);
                                                $hasCompanyAddress = $company->street || $company->city;
                                            @endphp
                                            @if($mainLocation && !$hasMainLocationInList)
                                                @php
                                                    $mainAddress = trim(($mainLocation->street ?? '') . ' ' . ($mainLocation->house_number ?? '') . ($mainLocation->house_number_extension ? '-' . $mainLocation->house_number_extension : ''));
                                                    $mainAddress = trim($mainAddress . ' ' . ($mainLocation->postal_code ?? '') . ' ' . ($mainLocation->city ?? ''));
                                                    $mainDisplayName = $mainLocation->name;
                                                    if ($mainLocation->city) {
                                                        $mainDisplayName .= ' - ' . $mainLocation->city;
                                                    }
                                                    $mainDisplayName .= ' (Hoofdadres)';
                                                @endphp
                                                <option value="{{ $mainLocation->id }}" data-name="{{ $mainLocation->name }}" data-address="{{ $mainAddress }}">{{ $mainDisplayName }}</option>
                                            @elseif(!$mainLocation && $hasCompanyAddress)
                                                @php
                                                    $companyAddress = trim(($company->street ?? '') . ' ' . ($company->house_number ?? '') . ($company->house_number_extension ? '-' . $company->house_number_extension : ''));
                                                    $companyAddress = trim($companyAddress . ' ' . ($company->postal_code ?? '') . ' ' . ($company->city ?? ''));
                                                    $companyDisplayName = $company->name;
                                                    if ($company->city) {
                                                        $companyDisplayName .= ' - ' . $company->city;
                                                    }
                                                    $companyDisplayName .= ' (Hoofdadres)';
                                                @endphp
                                                <option value="company_main" data-name="{{ $company->name }}" data-address="{{ $companyAddress }}">{{ $companyDisplayName }}</option>
                                            @endif
                                        @endif
                                        @foreach($companyLocations as $location)
                                            @php
                                                $isMain = $mainLocation && $location->id === $mainLocation->id;
                                                $locationAddress = trim(($location->street ?? '') . ' ' . ($location->house_number ?? '') . ($location->house_number_extension ? '-' . $location->house_number_extension : ''));
                                                $locationAddress = trim($locationAddress . ' ' . ($location->postal_code ?? '') . ' ' . ($location->city ?? ''));
                                                $locationDisplayName = $location->name;
                                                if ($location->city) {
                                                    $locationDisplayName .= ' - ' . $location->city;
                                                }
                                                if ($isMain) {
                                                    $locationDisplayName .= ' (Hoofdadres)';
                                                }
                                            @endphp
                                            <option value="{{ $location->id }}" data-name="{{ $location->name }}" data-address="{{ $locationAddress }}">{{ $locationDisplayName }}</option>
                                        @endforeach
                                        <option value="other">Anders</option>
                                    </select>
                                    <input type="text" name="location_custom" id="stage_location_custom" class="kt-input mt-2 hidden" placeholder="Adres">
                                    <input type="hidden" name="company_location_id" id="stage_company_location_id">
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-36 text-secondary-foreground font-normal">Gepland op</td>
                                <td class="text-foreground font-normal">
                                    <div class="kt-input">
                                        <i class="ki-outline ki-calendar"></i>
                                        <input class="grow"
                                               id="stage_scheduled_at_display"
                                               data-kt-date-picker="true"
                                               data-kt-date-picker-input-mode="true"
                                               data-kt-date-picker-position-to-input="left"
                                               data-kt-date-picker-format="dd-mm-yyyy"
                                               placeholder="Selecteer datum"
                                               readonly
                                               type="text"/>
                                        <input type="hidden"
                                               name="scheduled_at"
                                               id="stage_scheduled_at_hidden"
                                               value=""/>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-36 text-secondary-foreground font-normal">Tijd</td>
                                <td class="text-foreground font-normal">
                                    <input type="text"
                                           name="scheduled_time"
                                           id="stage_scheduled_time"
                                           class="kt-input"
                                           placeholder="hh:mm"
                                           maxlength="5"
                                           pattern="[0-9]{2}:[0-9]{2}">
                                    <small class="text-muted-foreground text-xs mt-1 block">Voer tijd in als hh:mm (bijv. 14:30)</small>
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-36 text-secondary-foreground font-normal">Interviewer</td>
                                <td class="text-foreground font-normal">
                                    <select name="interviewer_id" id="stage_interviewer_id" class="kt-input">
                                        <option value="">Selecteer interviewer</option>
                                        @foreach($companyUsers as $user)
                                            <option value="{{ $user->id }}" data-name="{{ $user->first_name }} {{ $user->last_name }}" data-email="{{ $user->email }}">{{ $user->first_name }} {{ $user->last_name }}</option>
                                        @endforeach
                                        <option value="other">Anders</option>
                                    </select>
                                    <input type="text"
                                           name="interviewer_name_custom"
                                           id="stage_interviewer_name_custom"
                                           class="kt-input mt-2 hidden"
                                           placeholder="Naam interviewer"
                                           minlength="2"
                                           maxlength="255"
                                           pattern="[a-zA-ZÀ-ÿĀ-žА-яа-я\s\-'\.]+">
                                    <input type="hidden" name="interviewer_name" id="stage_interviewer_name">
                                    <input type="hidden" name="user_id" id="stage_user_id">
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-36 text-secondary-foreground font-normal">Interviewer e-mail</td>
                                <td class="text-foreground font-normal">
                                    <div class="relative" style="position: relative; width: 100%;">
                                        <input type="email"
                                               name="interviewer_email"
                                               id="stage_interviewer_email"
                                               class="kt-input"
                                               pattern="[a-zA-Z0-9._%25+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}"
                                               placeholder="voorbeeld@email.nl"
                                               readonly
                                               style="padding-right: 2.75rem !important; width: 100%;">
                                        <div id="stage_interviewer_email_icon"
                                             class="absolute hidden"
                                             style="position: absolute !important; right: 0.75rem !important; top: 50% !important; transform: translateY(-50%) !important; pointer-events: none !important; z-index: 10 !important; display: flex !important; align-items: center !important; justify-content: center !important; width: 1.25rem !important; height: 1.25rem !important;">
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-36 text-secondary-foreground font-normal align-top">Notities</td>
                                <td class="text-foreground font-normal">
                                    <textarea name="notes" id="stage_notes" class="kt-input" rows="4"></textarea>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="kt-modal-footer">
                    <button type="button" class="kt-btn kt-btn-outline" data-kt-modal-dismiss="true">Annuleren</button>
                    <button type="submit" class="kt-btn kt-btn-primary">Opslaan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal toggle functionality
    const modalToggleButtons = document.querySelectorAll('[data-kt-modal-toggle]');
    const modalDismissButtons = document.querySelectorAll('[data-kt-modal-dismiss]');
    const modals = document.querySelectorAll('.kt-modal');

    modalToggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modalId = this.getAttribute('data-kt-modal-toggle');
            const modal = document.getElementById(modalId);
            if (modal) {
                openModal(modal);
            }
        });
    });

    modalDismissButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.kt-modal');
            if (modal) {
                closeModal(modal);
            }
        });
    });

    function openModal(modal) {
        // Show modal - use the existing CSS structure
        modal.style.display = 'block';
        modal.style.position = 'fixed';
        modal.style.inset = '0';
        modal.style.zIndex = '9999';

        const dialog = modal.querySelector('.kt-modal-dialog');
        if (dialog) {
            // Show dialog
            dialog.style.display = 'block';

            // Center dialog using margin auto with flexbox on parent
            modal.style.display = 'flex';
            modal.style.alignItems = 'center';
            modal.style.justifyContent = 'center';

            dialog.style.position = 'relative';
            dialog.style.insetInlineStart = 'auto';
            dialog.style.top = 'auto';
            dialog.style.transform = 'none';
            dialog.style.translate = 'none';
            dialog.style.maxWidth = '90%';
            dialog.style.width = 'auto';
            dialog.style.margin = '0';
        }
        document.body.style.overflow = 'hidden';

        // Create backdrop with blur
        let backdrop = document.querySelector('.kt-modal-backdrop');
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.className = 'kt-modal-backdrop';
            backdrop.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 9998;';
            document.body.appendChild(backdrop);
            backdrop.addEventListener('click', function() {
                closeModal(modal);
            });
        }

        // Add ESC key handler
        const escHandler = function(e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                closeModal(modal);
                document.removeEventListener('keydown', escHandler);
            }
        };
        document.addEventListener('keydown', escHandler);
        modal._escHandler = escHandler;

        // Initialize datepicker for new interview modal
        if (modal.id === 'interview_modal') {
            setTimeout(function() {
                const dateInput = modal.querySelector('#scheduled_at_display');
                if (dateInput && typeof KTComponents !== 'undefined' && KTComponents.init) {
                    KTComponents.init();
                    // Reinitialize datepicker after modal opens
                    setTimeout(function() {
                        if (dateInput._flatpickr) {
                            dateInput._flatpickr.destroy();
                        }
                        if (typeof flatpickr !== 'undefined') {
                            flatpickr(dateInput, {
                                dateFormat: 'Y-m-d',
                                altInput: true,
                                altFormat: 'd-m-Y',
                                onChange: function(selectedDates, dateStr, instance) {
                                    const hiddenInput = modal.querySelector('#scheduled_at_hidden');
                                    const hiddenInputAlt = modal.querySelector('#scheduled_at_hidden_alt');
                                    if (hiddenInput) hiddenInput.value = dateStr;
                                    if (hiddenInputAlt) hiddenInputAlt.value = dateStr;
                                }
                            });
                        }
                    }, 100);
                }
            }, 100);
        }

        // Initialize datepicker for edit interview modal
        if (modal.id === 'edit_interview_modal') {
            setTimeout(function() {
                const dateInput = modal.querySelector('#edit_scheduled_at_display');
                if (dateInput) {
                    // Destroy existing flatpickr instance if any
                    if (dateInput._flatpickr) {
                        dateInput._flatpickr.destroy();
                    }

                    // Try to initialize datepicker, but don't fail if flatpickr is not available
                    setTimeout(function() {
                        if (typeof flatpickr !== 'undefined' && flatpickr) {
                            // Parse the current value if it exists (format: d-m-Y)
                            let defaultDate = null;
                            if (dateInput.value) {
                                try {
                                    // Try to parse d-m-Y format
                                    const parts = dateInput.value.split('-');
                                    if (parts.length === 3) {
                                        defaultDate = new Date(parseInt(parts[2]), parseInt(parts[1]) - 1, parseInt(parts[0]));
                                    }
                                } catch (e) {
                                }
                            }

                            try {
                                const fp = flatpickr(dateInput, {
                                    dateFormat: 'Y-m-d',
                                    altInput: true,
                                    altFormat: 'd-m-Y',
                                    defaultDate: defaultDate,
                                    appendTo: modal.querySelector('.kt-modal-body') || modal,
                                    static: false,
                                    onChange: function(selectedDates, dateStr, instance) {
                                        const hiddenInput = modal.querySelector('#edit_scheduled_at_hidden');
                                        const hiddenInputAlt = modal.querySelector('#edit_scheduled_at_hidden_alt');
                                        if (hiddenInput) hiddenInput.value = dateStr;
                                        if (hiddenInputAlt) hiddenInputAlt.value = dateStr;
                                    }
                                });

                                // Ensure the calendar appears above the modal
                                if (fp && fp.calendarContainer) {
                                    fp.calendarContainer.style.zIndex = '99999';
                                }
                            } catch (e) {
                            }
                        }
                    }, 300);
                }
            }, 200);
        }

        // Location dropdown handler for interview modal
        const locationType = modal.querySelector('#location_type');
        if (locationType) {
            locationType.addEventListener('change', function() {
                const customInput = modal.querySelector('#location_custom');
                const companyLocationId = modal.querySelector('#company_location_id');
                if (this.value === 'other') {
                    if (customInput) customInput.classList.remove('hidden');
                    if (companyLocationId) companyLocationId.value = '';
                } else if (this.value === 'online') {
                    if (customInput) customInput.classList.add('hidden');
                    if (companyLocationId) companyLocationId.value = '';
                } else {
                    if (customInput) customInput.classList.add('hidden');
                    if (companyLocationId) companyLocationId.value = this.value;
                }
            });
        }

        // Location dropdown handler for edit interview modal
        const editLocationType = modal.querySelector('#edit_location_type');
        if (editLocationType) {
            editLocationType.addEventListener('change', function() {
                const customInput = modal.querySelector('#edit_location_custom');
                const companyLocationId = modal.querySelector('#edit_company_location_id');
                if (this.value === 'other') {
                    if (customInput) customInput.classList.remove('hidden');
                    if (companyLocationId) companyLocationId.value = '';
                } else if (this.value === 'online') {
                    if (customInput) customInput.classList.add('hidden');
                    if (companyLocationId) companyLocationId.value = '';
                } else {
                    if (customInput) customInput.classList.add('hidden');
                    if (companyLocationId) companyLocationId.value = this.value;
                }
            });
        }

        // Interviewer dropdown handler for interview modal
        const interviewerId = modal.querySelector('#interviewer_id');
        if (interviewerId) {
            interviewerId.addEventListener('change', function() {
                const nameInput = modal.querySelector('#interviewer_name');
                const emailInput = modal.querySelector('#interviewer_email');
                const userId = modal.querySelector('#user_id');
                const customNameInput = modal.querySelector('#interviewer_name_custom');

                if (this.value === 'other') {
                    if (customNameInput) customNameInput.classList.remove('hidden');
                    if (emailInput) {
                        emailInput.removeAttribute('readonly');
                        emailInput.setAttribute('required', 'required');
                    }
                    if (nameInput) nameInput.value = '';
                    if (userId) userId.value = '';
                } else {
                    if (customNameInput) customNameInput.classList.add('hidden');
                    const selectedOption = this.options[this.selectedIndex];
                    if (selectedOption) {
                        if (nameInput) nameInput.value = selectedOption.getAttribute('data-name') || '';
                        if (emailInput) {
                            emailInput.value = selectedOption.getAttribute('data-email') || '';
                            emailInput.setAttribute('readonly', 'readonly');
                            emailInput.removeAttribute('required');
                        }
                        if (userId) userId.value = this.value;
                    }
                }
            });
        }

        // Interviewer dropdown handler for edit interview modal
        const editInterviewerId = modal.querySelector('#edit_interviewer_id');
        if (editInterviewerId) {
            editInterviewerId.addEventListener('change', function() {
                const nameInput = modal.querySelector('#edit_interviewer_name');
                const emailInput = modal.querySelector('#edit_interviewer_email');
                const userId = modal.querySelector('#edit_user_id');
                const customNameInput = modal.querySelector('#edit_interviewer_name_custom');

                if (this.value === 'other') {
                    if (customNameInput) customNameInput.classList.remove('hidden');
                    if (emailInput) {
                        emailInput.removeAttribute('readonly');
                        emailInput.setAttribute('required', 'required');
                    }
                    if (nameInput) nameInput.value = '';
                    if (userId) userId.value = '';
                } else {
                    if (customNameInput) customNameInput.classList.add('hidden');
                    const selectedOption = this.options[this.selectedIndex];
                    if (selectedOption) {
                        if (nameInput) nameInput.value = selectedOption.getAttribute('data-name') || '';
                        if (emailInput) {
                            emailInput.value = selectedOption.getAttribute('data-email') || '';
                            emailInput.setAttribute('readonly', 'readonly');
                            emailInput.removeAttribute('required');
                        }
                        if (userId) userId.value = this.value;
                    }
                }
            });
        }

        // Time input formatting
        const timeInputs = modal.querySelectorAll('input[name="scheduled_time"], input[name="scheduled_time"][id^="edit"]');
        timeInputs.forEach(timeInput => {
            let previousValue = timeInput.value || '';
            let previousCursorPosition = 0;

            timeInput.addEventListener('keydown', function(e) {
                // Store previous value and cursor position before any changes
                previousValue = this.value;
                previousCursorPosition = this.selectionStart;
            });

            timeInput.addEventListener('input', function(e) {
                const currentCursorPosition = this.selectionStart;
                let value = this.value;

                // Get the raw digits from the current value
                const digitsOnly = value.replace(/\D/g, '');

                // Limit to 4 digits
                const limitedDigits = digitsOnly.slice(0, 4);

                // Format based on digit count
                let formatted = '';
                if (limitedDigits.length === 0) {
                    formatted = '';
                } else if (limitedDigits.length === 1) {
                    formatted = limitedDigits;
                } else if (limitedDigits.length === 2) {
                    formatted = limitedDigits + ':';
                } else if (limitedDigits.length === 3) {
                    formatted = limitedDigits.slice(0, 2) + ':' + limitedDigits.slice(2);
                } else {
                    formatted = limitedDigits.slice(0, 2) + ':' + limitedDigits.slice(2, 4);
                }

                // Calculate new cursor position
                // Count digits in previous value before cursor
                const prevDigitsOnly = previousValue.replace(/\D/g, '');
                let digitsBeforeCursor = 0;
                for (let i = 0; i < previousCursorPosition && i < previousValue.length; i++) {
                    if (/\d/.test(previousValue[i])) {
                        digitsBeforeCursor++;
                    }
                }

                // Determine if we added or removed digits
                const digitDiff = limitedDigits.length - prevDigitsOnly.length;

                // Adjust cursor position based on what happened
                if (digitDiff > 0) {
                    // Added digits - move cursor forward
                    digitsBeforeCursor += digitDiff;
                } else if (digitDiff < 0) {
                    // Removed digits - try to maintain position
                    // Don't change digitsBeforeCursor, let it stay where it was
                }

                // Find the corresponding position in the formatted string
                let newCursorPosition = 0;
                let digitsCounted = 0;

                for (let i = 0; i < formatted.length; i++) {
                    if (/\d/.test(formatted[i])) {
                        digitsCounted++;
                        if (digitsCounted > digitsBeforeCursor) {
                            newCursorPosition = i;
                            break;
                        } else if (digitsCounted === digitsBeforeCursor) {
                            // Position after this digit
                            newCursorPosition = i + 1;
                        }
                    }
                }

                // If we haven't found a position yet, place at the end
                if (digitsCounted <= digitsBeforeCursor) {
                    newCursorPosition = formatted.length;
                }

                // Ensure cursor position is within bounds
                newCursorPosition = Math.min(newCursorPosition, formatted.length);

                this.value = formatted;

                // Set cursor position after a small delay to ensure DOM is updated
                setTimeout(() => {
                    this.setSelectionRange(newCursorPosition, newCursorPosition);
                }, 0);

                // Update previous value and cursor position for next iteration
                previousValue = formatted;
                previousCursorPosition = newCursorPosition;
            });

            timeInput.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedText = (e.clipboardData || window.clipboardData).getData('text');
                const digitsOnly = pastedText.replace(/\D/g, '').slice(0, 4);

                let formatted = '';
                if (digitsOnly.length === 0) {
                    formatted = '';
                } else if (digitsOnly.length === 1) {
                    formatted = digitsOnly;
                } else if (digitsOnly.length === 2) {
                    formatted = digitsOnly + ':';
                } else if (digitsOnly.length === 3) {
                    formatted = digitsOnly.slice(0, 2) + ':' + digitsOnly.slice(2);
                } else {
                    formatted = digitsOnly.slice(0, 2) + ':' + digitsOnly.slice(2, 4);
                }

                this.value = formatted;
                setTimeout(() => {
                    this.setSelectionRange(formatted.length, formatted.length);
                }, 0);
            });

            timeInput.addEventListener('blur', function() {
                const value = this.value.replace(/\D/g, '');
                if (value.length === 4) {
                    const hours = parseInt(value.slice(0, 2));
                    const minutes = parseInt(value.slice(2, 4));
                    if (hours <= 23 && minutes <= 59) {
                        this.value = value.slice(0, 2) + ':' + value.slice(2, 4);
                    } else {
                        // Invalid time, try to fix or clear
                        if (hours > 23) {
                            this.value = '23:59';
                        } else if (minutes > 59) {
                            this.value = value.slice(0, 2) + ':59';
                        } else {
                            this.value = '';
                        }
                    }
                } else if (value.length > 0 && value.length < 4) {
                    // Incomplete time, clear it
                    this.value = '';
                }
            });
        });
    }

    function closeModal(modal) {
        modal.style.display = 'none';
        const dialog = modal.querySelector('.kt-modal-dialog');
        if (dialog) {
            dialog.style.display = 'none';
        }
        document.body.style.overflow = '';
        const backdrop = document.querySelector('.kt-modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
        // Remove ESC key handler if it exists
        if (modal._escHandler) {
            document.removeEventListener('keydown', modal._escHandler);
            delete modal._escHandler;
        }
    }

    // Handle edit interview form submission for cancelled interviews
    const editInterviewForm = document.getElementById('edit_interview_form');
    if (editInterviewForm) {
        const interviewStatus = editInterviewForm.getAttribute('data-interview-status');

        editInterviewForm.addEventListener('submit', function(e) {
            // If interview is cancelled, ask for confirmation to reactivate
            if (interviewStatus === 'cancelled') {
                e.preventDefault();

                // Create reactivation confirmation modal
                const reactivationModal = document.createElement('div');
                reactivationModal.className = 'kt-modal';
                reactivationModal.id = 'reactivate_interview_modal';
                reactivationModal.setAttribute('data-kt-modal', 'true');
                reactivationModal.style.cssText = 'display: none; z-index: 9999;';
                reactivationModal.innerHTML = '<div class="kt-modal-dialog kt-modal-dialog-centered" style="display: none;"><div class="kt-modal-content"><div class="kt-modal-header"><h3 class="kt-modal-title">Interview reactiveren</h3><button type="button" class="kt-btn kt-btn-icon kt-btn-sm" id="close_reactivate_modal"><i class="ki-filled ki-cross"></i></button></div><div class="kt-modal-body"><p class="text-foreground">Dit interview is momenteel geannuleerd. Weet u zeker dat u dit interview weer actief wilt maken?</p><p class="text-sm text-muted-foreground mt-2"><strong>Na het opslaan:</strong></p><ul class="text-sm text-muted-foreground mt-2 list-disc list-inside"><li>De status wordt aangepast naar "Gepland"</li><li>Er worden e-mails verzonden naar de kandidaat en interviewer</li><li>De interview wordt weer actief in het systeem</li></ul></div><div class="kt-modal-footer"><button type="button" class="kt-btn kt-btn-outline" id="cancel_reactivate_btn">Nee, annuleren</button><button type="button" class="kt-btn kt-btn-primary" id="confirm_reactivate_btn">Ja, reactiveren en opslaan</button></div></div></div>';

                document.body.appendChild(reactivationModal);

                // Open modal using the same function as other modals
                openModal(reactivationModal);

                // Handle close button
                const closeBtn = reactivationModal.querySelector('#close_reactivate_modal');
                if (closeBtn) {
                    closeBtn.addEventListener('click', function() {
                        closeModal(reactivationModal);
                    });
                }

                // Handle cancel button
                const cancelBtn = reactivationModal.querySelector('#cancel_reactivate_btn');
                if (cancelBtn) {
                    cancelBtn.addEventListener('click', function() {
                        closeModal(reactivationModal);
                    });
                }

                // Handle confirm button
                const confirmBtn = reactivationModal.querySelector('#confirm_reactivate_btn');
                if (confirmBtn) {
                    confirmBtn.addEventListener('click', function() {
                        // Add hidden input to indicate reactivation
                        let hiddenInput = editInterviewForm.querySelector('input[name="reactivate"]');
                        if (!hiddenInput) {
                            hiddenInput = document.createElement('input');
                            hiddenInput.type = 'hidden';
                            hiddenInput.name = 'reactivate';
                            editInterviewForm.appendChild(hiddenInput);
                        }
                        hiddenInput.value = '1';

                        // Close modal
                        closeModal(reactivationModal);

                        // Submit the form
                        editInterviewForm.submit();
                    });
                }
            }
        });
    }

    // Timeline auto refresh functionality
    const timelineAutoRefresh = document.getElementById('timeline_auto_refresh');
    const timelineContent = document.getElementById('timeline_content');
    let timelineRefreshInterval = null;

    if (timelineAutoRefresh && timelineContent) {
        // Get current URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const matchId = urlParams.get('match_id');
        const applicationId = urlParams.get('application_id');
        const vacancyId = {{ $vacancy->id }};
        const candidateId = {{ $candidate->id }};

        // Function to refresh timeline
        function refreshTimeline() {
            const url = new URL('{{ route("admin.skillmatching.vacancies.candidate.timeline", ["vacancy" => $vacancy->id, "candidate" => $candidate->id]) }}', window.location.origin);
            if (matchId) {
                url.searchParams.append('match_id', matchId);
            } else if (applicationId) {
                url.searchParams.append('application_id', applicationId);
            }

            fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.timeline) {
                    // Rebuild timeline HTML
                    let timelineHTML = '<div class="flex flex-col">';
                    data.timeline.forEach((item, index) => {
                        timelineHTML += `
                            <div class="flex items-start relative">
                                ${index < data.timeline.length - 1 ? '<div class="w-9 start-0 top-9 absolute bottom-0 rtl:-translate-x-1/2 translate-x-1/2 border-s border-s-input"></div>' : ''}
                                <div class="flex items-center justify-center shrink-0 rounded-full bg-accent/60 border border-input size-9 text-secondary-foreground">
                                    <i class="ki-filled ${item.icon} text-base"></i>
                                </div>
                                <div class="ps-2.5 mb-7 text-base grow">
                                    <div class="flex flex-col">
                                        <div class="text-sm text-foreground">
                                            ${item.title}
                                            ${item.description ? '<span class="text-secondary-foreground"> - ' + item.description + '</span>' : ''}
                                        </div>
                                        <span class="text-xs text-secondary-foreground">
                                            ${item.date_human} (${item.date_formatted})
                                        </span>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    timelineHTML += '</div>';
                    timelineContent.innerHTML = timelineHTML;
                }
            })
            .catch(error => {
            });
        }

        // Handle auto refresh toggle
        timelineAutoRefresh.addEventListener('change', function() {
            if (this.checked) {
                // Start auto refresh every 10 seconds
                timelineRefreshInterval = setInterval(refreshTimeline, 10000);
            } else {
                // Stop auto refresh
                if (timelineRefreshInterval) {
                    clearInterval(timelineRefreshInterval);
                    timelineRefreshInterval = null;
                }
            }
        });

        // Start auto refresh if checked by default
        if (timelineAutoRefresh.checked) {
            timelineRefreshInterval = setInterval(refreshTimeline, 10000);
        }
    }

    // Stage management functionality
    window.openStageModal = function(stageId) {
        const modal = document.getElementById('stage_modal');
        const form = document.getElementById('stage_form');
        const stageStatus = document.getElementById('stage_status');
        const stageOutcome = document.getElementById('stage_outcome');
        const stageTypeInput = document.getElementById('stage_type_input');
        const stageDuration = document.getElementById('stage_duration');
        const stageLocationType = document.getElementById('stage_location_type');
        const stageLocationCustom = document.getElementById('stage_location_custom');
        const stageCompanyLocationId = document.getElementById('stage_company_location_id');
        const stageScheduledAt = document.getElementById('stage_scheduled_at_display');
        const stageScheduledAtHidden = document.getElementById('stage_scheduled_at_hidden');
        const stageScheduledTime = document.getElementById('stage_scheduled_time');
        const stageInterviewerId = document.getElementById('stage_interviewer_id');
        const stageInterviewerNameCustom = document.getElementById('stage_interviewer_name_custom');
        const stageInterviewerName = document.getElementById('stage_interviewer_name');
        const stageInterviewerEmail = document.getElementById('stage_interviewer_email');
        const stageUserId = document.getElementById('stage_user_id');
        const stageNotes = document.getElementById('stage_notes');

        // Set form action
        form.action = `/admin/stage-instances/${stageId}`;

        // Reset form
        stageStatus.value = 'PENDING';
        stageOutcome.value = '';
        if (stageTypeInput) stageTypeInput.value = '';
        if (stageDuration) stageDuration.value = '';
        if (stageLocationType) stageLocationType.value = '';
        if (stageLocationCustom) {
            stageLocationCustom.value = '';
            stageLocationCustom.classList.add('hidden');
        }
        if (stageCompanyLocationId) stageCompanyLocationId.value = '';
        stageScheduledAt.value = '';
        stageScheduledAtHidden.value = '';
        if (stageScheduledTime) stageScheduledTime.value = '';
        if (stageInterviewerId) stageInterviewerId.value = '';
        if (stageInterviewerNameCustom) {
            stageInterviewerNameCustom.value = '';
            stageInterviewerNameCustom.classList.add('hidden');
        }
        if (stageInterviewerName) stageInterviewerName.value = '';
        if (stageInterviewerEmail) {
            stageInterviewerEmail.value = '';
            stageInterviewerEmail.setAttribute('readonly', 'readonly');
        }
        stageNotes.value = '';

        // Fetch stage data
        fetch(`/admin/stage-instances/${stageId}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.stage) {
                stageStatus.value = data.stage.status || 'PENDING';
                stageOutcome.value = data.stage.outcome || '';
                if (data.stage.scheduled_at) {
                    const date = new Date(data.stage.scheduled_at);
                    // Format as dd-mm-yyyy for display
                    const day = String(date.getDate()).padStart(2, '0');
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const year = date.getFullYear();
                    const dateStr = `${day}-${month}-${year}`;
                    stageScheduledAt.value = dateStr;
                    stageScheduledAtHidden.value = date.toISOString().split('T')[0];
                    // Update Flatpickr if it's already initialized, otherwise wait for initialization
                    setTimeout(() => {
                        if (stageScheduledAt._flatpickr) {
                            stageScheduledAt._flatpickr.setDate(date, false);
                        }
                    }, 300);
                } else {
                    stageScheduledAt.value = '';
                    stageScheduledAtHidden.value = '';
                    setTimeout(() => {
                        if (stageScheduledAt._flatpickr) {
                            stageScheduledAt._flatpickr.clear();
                        }
                    }, 300);
                }
                stageNotes.value = data.stage.notes || '';
                const artifacts = data.stage.artifacts || {};
                if (stageTypeInput) stageTypeInput.value = artifacts.type || '';
                if (stageDuration) stageDuration.value = artifacts.duration || '';
                if (stageLocationType) {
                    const locationTypeValue = artifacts.location_type || artifacts.company_location_id || '';
                    stageLocationType.value = locationTypeValue;
                }
                if (stageCompanyLocationId) {
                    stageCompanyLocationId.value = artifacts.company_location_id || '';
                }
                if (stageLocationCustom && artifacts.location_custom) {
                    stageLocationCustom.value = artifacts.location_custom;
                }
                if (stageScheduledTime) {
                    stageScheduledTime.value = artifacts.scheduled_time || '';
                }
                if (stageInterviewerId) {
                    stageInterviewerId.value = artifacts.interviewer_id || artifacts.user_id || '';
                    handleStageInterviewerChange(stageInterviewerId.value);
                }
                if (stageInterviewerName) {
                    stageInterviewerName.value = artifacts.interviewer_name || '';
                }
                if (stageInterviewerEmail) {
                    stageInterviewerEmail.value = artifacts.interviewer_email || '';
                    if (artifacts.interviewer_id) {
                        stageInterviewerEmail.setAttribute('readonly', 'readonly');
                    } else {
                        stageInterviewerEmail.removeAttribute('readonly');
                    }
                }
                if (stageLocationType) {
                    handleStageLocationChange(stageLocationType.value);
                }
            }
        })
        .catch(error => {
        });

        // Open modal
        openModal(modal);

        // Monitor datepicker input value and update hidden input - works with ANY calendar library
        const hiddenInput = document.getElementById('stage_scheduled_at_hidden');
        if (!stageScheduledAt || !hiddenInput) return;

        // Function to parse display value and update hidden input
        const updateHiddenFromDisplay = function() {
            if (!stageScheduledAt || !stageScheduledAt.value) {
                return;
            }
            
            const displayValue = stageScheduledAt.value.trim();
            let isoDate = null;
            
            // Try ISO format first (YYYY-MM-DD) - if it's already in ISO format, use it directly
            const isoMatch = displayValue.match(/(\d{4})-(\d{1,2})-(\d{1,2})/);
            if (isoMatch) {
                const year = isoMatch[1];
                const month = String(isoMatch[2]).padStart(2, '0');
                const day = String(isoMatch[3]).padStart(2, '0');
                isoDate = `${year}-${month}-${day}`;
            } else {
                // Try European format (d-m-Y or dd-mm-yyyy)
                const dateMatch = displayValue.match(/(\d{1,2})-(\d{1,2})-(\d{4})/);
                if (dateMatch) {
                    const day = String(dateMatch[1]).padStart(2, '0');
                    const month = String(dateMatch[2]).padStart(2, '0');
                    const year = dateMatch[3];
                    isoDate = `${year}-${month}-${day}`;
                }
            }
            
            if (isoDate && hiddenInput.value !== isoDate) {
                hiddenInput.value = isoDate;
            }
        };

        // Monitor input value changes using multiple methods
        let lastValue = stageScheduledAt.value || '';
        
        // Method 1: Input event listener
        stageScheduledAt.addEventListener('input', function() {
            updateHiddenFromDisplay();
            lastValue = this.value;
        });

        // Method 2: Change event listener
        stageScheduledAt.addEventListener('change', function() {
            updateHiddenFromDisplay();
            lastValue = this.value;
        });

        // Method 3: Polling - check every 100ms for value changes
        const pollInterval = setInterval(function() {
            const currentValue = stageScheduledAt.value || '';
            if (currentValue !== lastValue && currentValue) {
                lastValue = currentValue;
                // Force update immediately
                updateHiddenFromDisplay();
                // Also check again after a short delay to catch any delayed updates
                setTimeout(function() {
                    if (stageScheduledAt.value && stageScheduledAt.value !== lastValue) {
                        lastValue = stageScheduledAt.value;
                        updateHiddenFromDisplay();
                    }
                }, 200);
            }
        }, 100);

        // Method 4: Use event delegation on document to catch ALL clicks
        const clickHandler = function(e) {
            // Check if click is on any calendar element or day
            const calendar = e.target.closest('[data-vc="calendar"], [data-vc], .flatpickr-calendar, .vc, [data-vc-day], .flatpickr-day');
            if (calendar) {
                // Wait a bit for calendar to process the click and update the input
                setTimeout(function() {
                    updateHiddenFromDisplay();
                }, 200);
                // Also check again after a longer delay
                setTimeout(function() {
                    updateHiddenFromDisplay();
                }, 500);
            }
        };
        document.addEventListener('click', clickHandler, true);

        // Clean up when modal closes
        const originalClose = window.closeModal;
        if (typeof originalClose === 'function') {
            window.closeModal = function(modalElement) {
                if (modalElement === modal) {
                    clearInterval(pollInterval);
                    document.removeEventListener('click', clickHandler, true);
                }
                originalClose(modalElement);
            };
        }

        // Handle form submission via AJAX
        const originalSubmit = form.onsubmit;
        form.onsubmit = function(e) {
            e.preventDefault();

            // Ensure date is correctly set in hidden input before submission
            updateHiddenFromDisplay(); // Force update before submission
            
            const stageScheduledTime = document.getElementById('stage_scheduled_time');
            const hasTime = stageScheduledTime && stageScheduledTime.value && stageScheduledTime.value.trim() !== '';

            // First, try to get the date from Flatpickr
            if (stageScheduledAt && stageScheduledAt._flatpickr) {
                const selectedDates = stageScheduledAt._flatpickr.selectedDates;
                if (selectedDates && selectedDates.length > 0) {
                    // Use the selected date from Flatpickr
                    stageScheduledAtHidden.value = selectedDates[0].toISOString().split('T')[0];
                } else if (stageScheduledAt.value) {
                    // Try to parse the display value if Flatpickr doesn't have a date selected
                    updateHiddenFromDisplay();
                }
            } else if (stageScheduledAt && stageScheduledAt.value) {
                // If Flatpickr is not initialized but there's a display value, parse it
                updateHiddenFromDisplay();
            }

            // Only use today's date if we have time but NO date at all (neither from Flatpickr nor from display value)
            if (hasTime && (!stageScheduledAtHidden.value || stageScheduledAtHidden.value.trim() === '')) {
                const today = new Date();
                stageScheduledAtHidden.value = today.toISOString().split('T')[0];
            }

            const formData = new FormData(form);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (csrfToken) {
                formData.append('_token', csrfToken);
            }

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData,
            })
            .then(response => {
                if (response.ok) {
                    return response.json();
                }
                return response.json().then(err => Promise.reject(err));
            })
            .then(data => {
                if (data.success) {
                    closeModal(modal);
                    // Reload page to show updated stages
                    window.location.reload();
                } else {
                    alert('Er is een fout opgetreden: ' + (data.message || 'Onbekende fout'));
                }
            })
            .catch(error => {
                alert('Er is een fout opgetreden bij het bijwerken van de stage.');
            });
        };
    };

    function handleStageLocationChange(value) {
        const customInput = document.getElementById('stage_location_custom');
        if (!customInput) {
            return;
        }
        if (value === 'other') {
            customInput.classList.remove('hidden');
        } else {
            customInput.classList.add('hidden');
        }
    }

    function handleStageInterviewerChange(value) {
        const customNameInput = document.getElementById('stage_interviewer_name_custom');
        const emailInput = document.getElementById('stage_interviewer_email');
        const hiddenName = document.getElementById('stage_interviewer_name');
        const stageUserId = document.getElementById('stage_user_id');
        if (!customNameInput || !emailInput) {
            return;
        }
        if (value === 'other') {
            customNameInput.classList.remove('hidden');
            emailInput.removeAttribute('readonly');
            if (hiddenName) {
                hiddenName.value = customNameInput.value || '';
            }
            if (stageUserId) {
                stageUserId.value = '';
            }
        } else {
            customNameInput.classList.add('hidden');
            const selectedOption = document.querySelector(`#stage_interviewer_id option[value="${value}"]`);
            if (selectedOption) {
                if (hiddenName) {
                    hiddenName.value = selectedOption.getAttribute('data-name') || '';
                }
                if (stageUserId) {
                    stageUserId.value = value;
                }
                const email = selectedOption.getAttribute('data-email');
                emailInput.value = email || '';
                emailInput.setAttribute('readonly', 'readonly');
            }
        }
    }

    const stageLocationSelect = document.getElementById('stage_location_type');
    if (stageLocationSelect) {
        stageLocationSelect.addEventListener('change', function() {
            handleStageLocationChange(this.value);
            const companyInput = document.getElementById('stage_company_location_id');
            if (companyInput) {
                // Only set company_location_id if it's a numeric ID (not 'online', 'other', or 'company_main')
                companyInput.value = /^\d+$/.test(this.value) ? this.value : '';
            }
            if (this.value !== 'other') {
                const customInput = document.getElementById('stage_location_custom');
                if (customInput) {
                    customInput.value = '';
                }
            }
        });
    }

    const stageScheduledTimeInput = document.getElementById('stage_scheduled_time');
    if (stageScheduledTimeInput) {
        stageScheduledTimeInput.addEventListener('input', function() {
            // Only set today's date if time is entered AND there is absolutely no date set
            // Do NOT overwrite an existing date that was chosen by the user
            const stageScheduledAtHidden = document.getElementById('stage_scheduled_at_hidden');
            const stageScheduledAt = document.getElementById('stage_scheduled_at_display');
            // Check if there's really no date (neither in hidden input nor in Flatpickr)
            const hasDateInHidden = stageScheduledAtHidden && stageScheduledAtHidden.value && stageScheduledAtHidden.value.trim() !== '';
            const hasDateInFlatpickr = stageScheduledAt && stageScheduledAt._flatpickr &&
                                        stageScheduledAt._flatpickr.selectedDates &&
                                        stageScheduledAt._flatpickr.selectedDates.length > 0;
            const hasDateInDisplay = stageScheduledAt && stageScheduledAt.value && stageScheduledAt.value.trim() !== '';
            if (this.value && this.value.trim() !== '' && !hasDateInHidden && !hasDateInFlatpickr && !hasDateInDisplay) {
                // Only set today's date if there's absolutely no date anywhere
                const today = new Date();
                const dateStr = today.toISOString().split('T')[0];
                if (stageScheduledAtHidden) {
                    stageScheduledAtHidden.value = dateStr;
                }
                // Update Flatpickr if it exists
                if (stageScheduledAt && stageScheduledAt._flatpickr) {
                    stageScheduledAt._flatpickr.setDate(today, false);
                } else if (stageScheduledAt) {
                    // Format as dd-mm-yyyy for display
                    const day = String(today.getDate()).padStart(2, '0');
                    const month = String(today.getMonth() + 1).padStart(2, '0');
                    const year = today.getFullYear();
                    stageScheduledAt.value = `${day}-${month}-${year}`;
                }
            }
        });
    }

    const stageInterviewerSelect = document.getElementById('stage_interviewer_id');
    if (stageInterviewerSelect) {
        stageInterviewerSelect.addEventListener('change', function() {
            handleStageInterviewerChange(this.value);
        });
    }

    const stageInterviewerNameCustomField = document.getElementById('stage_interviewer_name_custom');
    if (stageInterviewerNameCustomField) {
        stageInterviewerNameCustomField.addEventListener('input', function() {
            const hiddenName = document.getElementById('stage_interviewer_name');
            if (hiddenName) {
                hiddenName.value = this.value;
            }
        });
    }
    
    // Initialize accordion for appointments - always use manual toggle for reliability
    function toggleAccordionItem(button) {
        const targetId = button.getAttribute('data-kt-accordion-toggle');
        if (!targetId) return;
        
        const content = document.querySelector(targetId);
        const item = button.closest('.kt-accordion-item');
        
        if (!content || !item) return;
        
        const isExpanded = item.getAttribute('aria-expanded') === 'true';
        
        if (isExpanded) {
            // Close
            content.style.display = 'none';
            item.setAttribute('aria-expanded', 'false');
            item.classList.remove('active');
        } else {
            // Open
            content.style.display = 'block';
            item.setAttribute('aria-expanded', 'true');
            item.classList.add('active');
        }
    }
    
    function initAppointmentAccordion() {
        const accordionElement = document.querySelector('[data-kt-accordion="true"]');
        if (!accordionElement) return;
        
        // Use event delegation on the accordion container
        accordionElement.addEventListener('click', function(e) {
            // Check if click is on button or any child (including SVG)
            const button = e.target.closest('[data-kt-accordion-toggle]');
            if (!button) return;
            
            e.preventDefault();
            e.stopPropagation();
            
            toggleAccordionItem(button);
        }, true); // Use capture phase
        
        // Also add direct listeners to buttons as backup
        const toggleButtons = accordionElement.querySelectorAll('[data-kt-accordion-toggle]');
        toggleButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleAccordionItem(this);
            }, true); // Use capture phase
        });
    }
    
    // Initialize immediately and also after delays
    initAppointmentAccordion();
    setTimeout(initAppointmentAccordion, 200);
    setTimeout(initAppointmentAccordion, 500);

    // Chat functionality is now in chat.js (loaded globally)
    // Only chat history functions remain here
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function openChatHistory() {
        const matchId = @json($match ? $match->id : null);
        const applicationId = @json($application ? $application->id : null);
        
        let url = '{{ route("admin.chat.history") }}?';
        if (matchId) {
            url += `match_id=${matchId}`;
        } else if (applicationId) {
            url += `application_id=${applicationId}`;
        }

        fetch(url)
            .then(response => response.json())
            .then(data => {
                renderChatHistory(data);
                const modal = document.getElementById('chat_history_modal');
                const dialog = modal ? modal.querySelector('.kt-modal-dialog') : null;
                if (modal && dialog) {
                    modal.style.display = 'block';
                    dialog.style.display = 'block';
                    modal.classList.add('open');
                }
            })
            .catch(error => {
                console.error('Error loading chat history:', error);
            });
    }

    function renderChatHistory(data) {
        const container = document.getElementById('chat_history_messages');
        if (!container) return;

        if (!data.messages || data.messages.length === 0) {
            container.innerHTML = '<div class="p-4 text-center text-muted-foreground">Geen chat historie beschikbaar</div>';
            return;
        }

        container.innerHTML = data.messages.map(msg => {
            const isFromUser = msg.is_from_user;
            return `
                <div class="flex ${isFromUser ? 'justify-end' : 'justify-start'} mb-4">
                    <div class="flex items-start gap-2 ${isFromUser ? 'flex-row-reverse' : ''} max-w-[70%]">
                        <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                            <span class="text-primary text-xs font-semibold">${msg.sender_name.charAt(0).toUpperCase()}</span>
                        </div>
                        <div class="${isFromUser ? 'bg-primary text-white' : 'bg-muted'} rounded-lg p-3">
                            <div class="text-sm">${escapeHtml(msg.message)}</div>
                            <div class="text-xs mt-1 ${isFromUser ? 'text-primary-foreground/70' : 'text-muted-foreground'}">${msg.time}</div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        // Scroll to bottom
        container.scrollTop = container.scrollHeight;
    }
    
    // Chat button event listener
    const chatButton = document.getElementById('chat_with_candidate_btn');
    console.log('🔍 Chat button element:', chatButton);
    
    if (chatButton) {
        chatButton.addEventListener('click', function(e) {
            console.log('🔍 Chat button clicked!');
            e.preventDefault();
            e.stopPropagation();
            
            const candidateId = this.getAttribute('data-candidate-id');
            const matchId = this.getAttribute('data-match-id');
            const applicationId = this.getAttribute('data-application-id');
            const type = this.getAttribute('data-type');
            
            console.log('🔍 Button data:', { candidateId, matchId, applicationId, type });
            
            const matchOrAppId = matchId || applicationId || null;
            const chatType = type || null;
            
            console.log('🔍 Calling openChatWithCandidate:', { candidateId, matchOrAppId, chatType });
            console.log('🔍 Function exists?', typeof window.openChatWithCandidate);
            
            if (window.openChatWithCandidate) {
                window.openChatWithCandidate(candidateId, matchOrAppId, chatType);
            } else {
                console.error('❌ openChatWithCandidate function not found!');
            }
        });
        console.log('✅ Chat button event listener attached');
    } else {
        console.error('❌ Chat button not found!');
    }
});
</script>
@endpush


<!-- Chat History Modal -->
<div class="kt-modal" data-kt-modal="true" id="chat_history_modal" tabindex="-1" style="display: none; z-index: 9999;">
    <div class="kt-modal-dialog kt-modal-dialog-centered" style="max-width: 800px; display: none;">
        <div class="kt-modal-content">
            <div class="kt-modal-header">
                <h2 class="kt-modal-title">Chat Historie</h2>
                <button type="button" class="kt-btn kt-btn-icon kt-btn-sm" data-kt-modal-dismiss="true">
                    <i class="ki-filled ki-cross"></i>
                </button>
            </div>
            <div class="kt-modal-body">
                <div id="chat_history_messages" class="max-h-[600px] overflow-y-auto p-4">
                    <!-- Chat history will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
