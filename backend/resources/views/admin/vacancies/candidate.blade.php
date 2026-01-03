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
                $returnUrl = route('admin.vacancies.show', $vacancy);
                if ($type === 'match' && $match) {
                    $returnUrl = route('admin.vacancies.show', $vacancy) . '#vacancy_tab_matches';
                } elseif ($type === 'application' && $application) {
                    $returnUrl = route('admin.vacancies.show', $vacancy) . '#vacancy_tab_applications';
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
                @if(!$latestInterview || ($latestInterview && $latestInterview->status !== 'scheduled'))
                    <button type="button" class="kt-btn kt-btn-primary" data-kt-modal-toggle="interview_modal">
                        Interview inplannen
                    </button>
                @else
                    <button type="button" class="kt-btn kt-btn-primary" disabled style="opacity: 0.5; cursor: not-allowed;">
                        Interview inplannen
                    </button>
                @endif
            @endif
            @if($rawStatus !== 'rejected')
                <form action="{{ route('admin.vacancies.candidate.reject', ['vacancy' => $vacancy->id, 'candidate' => $candidate->id]) }}" method="POST" class="inline">
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
            @if($rawStatus !== 'accepted' && $rawStatus !== 'rejected')
                <form action="{{ route('admin.vacancies.candidate.accept', ['vacancy' => $vacancy->id, 'candidate' => $candidate->id]) }}" method="POST" class="inline">
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

            <!-- Interview Information -->
            @if($latestInterview)
            <div class="kt-card {{ $latestInterview->status === 'cancelled' ? 'opacity-60' : '' }}">
                <div class="kt-card-header">
                    <h3 class="kt-card-title {{ $latestInterview->status === 'cancelled' ? 'text-muted-foreground' : '' }}">Interview</h3>
                    <div class="flex items-center gap-2">
                        @if($latestInterview->status !== 'cancelled')
                        <button type="button" class="kt-btn kt-btn-icon" data-kt-modal-toggle="cancel_interview_modal" title="Interview annuleren" style="background-color: transparent !important; border: none !important; color: #dc2626 !important; width: 24px !important; height: 24px !important; padding: 0 !important; min-width: 24px !important;">
                            <i class="ki-filled ki-cross-circle" style="font-size: 18px !important;"></i>
                        </button>
                        @endif
                        <button type="button" class="kt-btn kt-btn-icon" data-kt-modal-toggle="edit_interview_modal" title="Interview bewerken" style="background-color: transparent !important; border: none !important; width: 24px !important; height: 24px !important; padding: 0 !important; min-width: 24px !important;">
                            <i class="ki-filled ki-notepad-edit" style="font-size: 18px !important;"></i>
                        </button>
                    </div>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="text-secondary-foreground font-normal {{ $latestInterview->status === 'cancelled' ? 'text-muted-foreground' : '' }}" style="width: calc(var(--spacing) * 50);">Datum</td>
                            <td class="text-foreground font-normal {{ $latestInterview->status === 'cancelled' ? 'text-muted-foreground' : '' }}">{{ $latestInterview->scheduled_at ? $latestInterview->scheduled_at->format('d-m-Y') : '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal {{ $latestInterview->status === 'cancelled' ? 'text-muted-foreground' : '' }}" style="width: calc(var(--spacing) * 50);">Tijd</td>
                            <td class="text-foreground font-normal {{ $latestInterview->status === 'cancelled' ? 'text-muted-foreground' : '' }}">{{ $latestInterview->scheduled_at ? $latestInterview->scheduled_at->format('H:i') : '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal {{ $latestInterview->status === 'cancelled' ? 'text-muted-foreground' : '' }}" style="width: calc(var(--spacing) * 50);">Type</td>
                            <td class="text-foreground font-normal {{ $latestInterview->status === 'cancelled' ? 'text-muted-foreground' : '' }}">
                                @php
                                    $typeMap = [
                                        'phone' => 'Telefoon',
                                        'video' => 'Video',
                                        'onsite' => 'Op locatie',
                                        'assessment' => 'Assessment',
                                        'final' => 'Eindgesprek',
                                    ];
                                @endphp
                                {{ $typeMap[$latestInterview->type] ?? ucfirst($latestInterview->type) }}
                            </td>
                        </tr>
                        @if($latestInterview->location)
                        <tr>
                            <td class="text-secondary-foreground font-normal {{ $latestInterview->status === 'cancelled' ? 'text-muted-foreground' : '' }}" style="width: calc(var(--spacing) * 50);">Locatie</td>
                            <td class="text-foreground font-normal {{ $latestInterview->status === 'cancelled' ? 'text-muted-foreground' : '' }}">{{ $latestInterview->location }}</td>
                        </tr>
                        @endif
                        @if($latestInterview->interviewer_name)
                        <tr>
                            <td class="text-secondary-foreground font-normal {{ $latestInterview->status === 'cancelled' ? 'text-muted-foreground' : '' }}" style="width: calc(var(--spacing) * 50);">Interviewer</td>
                            <td class="text-foreground font-normal {{ $latestInterview->status === 'cancelled' ? 'text-muted-foreground' : '' }}">{{ $latestInterview->interviewer_name }}</td>
                        </tr>
                        @endif
                        @if($latestInterview->interviewer_email)
                        <tr>
                            <td class="text-secondary-foreground font-normal {{ $latestInterview->status === 'cancelled' ? 'text-muted-foreground' : '' }}" style="width: calc(var(--spacing) * 50);">E-mail</td>
                            <td class="text-foreground font-normal {{ $latestInterview->status === 'cancelled' ? 'text-muted-foreground' : '' }}">{{ $latestInterview->interviewer_email }}</td>
                        </tr>
                        @endif
                        @if($latestInterview->duration)
                        <tr>
                            <td class="text-secondary-foreground font-normal {{ $latestInterview->status === 'cancelled' ? 'text-muted-foreground' : '' }}" style="width: calc(var(--spacing) * 50);">Duur</td>
                            <td class="text-foreground font-normal {{ $latestInterview->status === 'cancelled' ? 'text-muted-foreground' : '' }}">{{ $latestInterview->duration }} minuten</td>
                        </tr>
                        @endif
                        @if($latestInterview->status)
                        <tr>
                            <td class="text-secondary-foreground font-normal {{ $latestInterview->status === 'cancelled' ? 'text-muted-foreground' : '' }}" style="width: calc(var(--spacing) * 50);">Status</td>
                            <td class="text-foreground font-normal">
                                @php
                                    $statusMap = [
                                        'scheduled' => ['label' => 'Gepland', 'color' => 'info'],
                                        'confirmed' => ['label' => 'Bevestigd', 'color' => 'success'],
                                        'completed' => ['label' => 'Voltooid', 'color' => 'success'],
                                        'cancelled' => ['label' => 'Geannuleerd', 'color' => 'danger'],
                                        'rescheduled' => ['label' => 'Herpland', 'color' => 'warning'],
                                    ];
                                    $interviewStatusInfo = $statusMap[$latestInterview->status] ?? ['label' => ucfirst($latestInterview->status), 'color' => 'secondary'];
                                @endphp
                                <span class="kt-badge kt-badge-sm kt-badge-{{ $interviewStatusInfo['color'] }}">{{ $interviewStatusInfo['label'] }}</span>
                            </td>
                        </tr>
                        @endif
                        @if($latestInterview->notes)
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top {{ $latestInterview->status === 'cancelled' ? 'text-muted-foreground' : '' }}" style="width: calc(var(--spacing) * 50);">Notities</td>
                            <td class="text-foreground font-normal {{ $latestInterview->status === 'cancelled' ? 'text-muted-foreground' : '' }}">{{ $latestInterview->notes }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
            @endif

        </div>

        <!-- Right Column -->
        <div class="space-y-5">
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
                                <a href="{{ route('admin.vacancies.show', $vacancy) }}" class="text-primary hover:underline">{{ $vacancy->title }}</a>
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
            @if(!empty($timeline))
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
                                            @if($item['description'])
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
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Interview Modal -->
<div class="kt-modal" data-kt-modal="true" id="interview_modal" tabindex="-1" style="display: none; z-index: 9999;">
    <div class="kt-modal-dialog kt-modal-dialog-centered" style="display: none;">
        <div class="kt-modal-content">
            <form action="{{ route('admin.vacancies.candidate.interview', ['vacancy' => $vacancy->id, 'candidate' => $candidate->id]) }}" method="POST" novalidate>
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
                                        <option value="video">Video</option>
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
                                        @foreach($companyLocations as $location)
                                            <option value="{{ $location->id }}" data-name="{{ $location->name }}" data-address="{{ $location->address ?? '' }}">{{ $location->name }}</option>
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
            <form action="{{ route('admin.vacancies.candidate.reject', ['vacancy' => $vacancy->id, 'candidate' => $candidate->id]) }}" method="POST">
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
            <form action="{{ route('admin.vacancies.candidate.interview.update', ['vacancy' => $vacancy->id, 'candidate' => $candidate->id, 'interview' => $latestInterview->id]) }}" method="POST" novalidate id="edit_interview_form" data-interview-status="{{ $latestInterview->status }}">
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
                                        <option value="video" {{ $latestInterview->type === 'video' ? 'selected' : '' }}>Video</option>
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
                                        @foreach($companyLocations as $location)
                                            <option value="{{ $location->id }}" 
                                                    data-name="{{ $location->name }}" 
                                                    data-address="{{ $location->address ?? '' }}"
                                                    {{ $latestInterview->company_location_id == $location->id ? 'selected' : '' }}>
                                                {{ $location->name }}
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
            <form action="{{ route('admin.vacancies.candidate.interview.cancel', ['vacancy' => $vacancy->id, 'candidate' => $candidate->id, 'interview' => $latestInterview->id]) }}" method="POST">
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
                                    console.error('Error parsing date:', e);
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
                                console.error('Error initializing flatpickr:', e);
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
            const url = new URL('{{ route("admin.vacancies.candidate.timeline", ["vacancy" => $vacancy->id, "candidate" => $candidate->id]) }}', window.location.origin);
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
                console.error('Error refreshing timeline:', error);
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
});
</script>
@endpush

@endsection
