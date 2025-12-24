@extends('admin.layouts.app')

@section('title', 'Match Details - #' . $match->id)

@section('content')

<style>
    .hero-bg {
        background-image: url('{{ asset('assets/media/images/2600x1200/bg-1.png') }}');
    }
    .dark .hero-bg {
        background-image: url('{{ asset('assets/media/images/2600x1200/bg-1-dark.png') }}');
    }
</style>

<div class="bg-center bg-cover bg-no-repeat hero-bg">
    <div class="kt-container-fixed">
        <div class="flex flex-col items-center gap-2 lg:gap-3.5 py-4 lg:pt-5 lg:pb-10">
            @php
                $borderColor = 'border-primary';
                switch($match->status) {
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
            @if($match->candidate && $match->candidate->photo_blob)
                <div class="rounded-full shrink-0 overflow-hidden border-4 {{ $borderColor }} shadow-lg" style="width: 120px; height: 120px;">
                    <img class="w-full h-full object-cover" src="{{ route('admin.candidates.photo', $match->candidate) }}" alt="{{ $match->candidate->first_name }} {{ $match->candidate->last_name }}">
                </div>
            @elseif($match->candidate)
                <div class="rounded-full border-4 {{ $borderColor }} h-[120px] w-[120px] shrink-0 flex items-center justify-center bg-primary/10 text-primary text-3xl font-semibold">
                    {{ strtoupper(substr($match->candidate->first_name ?? 'K', 0, 1) . substr($match->candidate->last_name ?? '', 0, 1)) }}
                </div>
            @else
                <div class="rounded-full border-4 {{ $borderColor }} h-[120px] w-[120px] shrink-0 flex items-center justify-center bg-primary/10 text-primary text-3xl font-semibold">
                    <i class="ki-filled ki-user text-4xl"></i>
                </div>
            @endif
            <div class="flex items-center gap-1.5">
                <div class="text-xl lg:text-2xl leading-6 font-semibold text-mono">
                    @if($match->candidate)
                        {{ $match->candidate->first_name }} {{ $match->candidate->last_name }} (K)
                    @else
                        Match #{{ $match->id }}
                    @endif
                </div>
            </div>
            <div class="flex flex-wrap justify-center gap-1 lg:gap-4.5 text-sm">
                @if($match->candidate && $match->candidate->email)
                    <div class="flex items-center gap-1.5">
                        <i class="ki-filled ki-sms text-base"></i>
                        <a href="mailto:{{ $match->candidate->email }}" class="text-foreground hover:text-primary">{{ $match->candidate->email }}</a>
                    </div>
                @endif
                @if($match->candidate && $match->candidate->phone)
                    <div class="flex items-center gap-1.5">
                        <i class="ki-filled ki-phone text-base"></i>
                        <span class="text-foreground">{{ $match->candidate->phone }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="flex items-center gap-2.5 mb-5 lg:mb-10">
        <a href="{{ route('admin.matches.index') }}" class="kt-btn kt-btn-outline">
            <i class="ki-filled ki-arrow-left me-2"></i>
            Terug
        </a>
    </div>
</div>

<div class="kt-container-fixed">
    <!-- begin: grid -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5 lg:gap-7.5">
        <!-- Left Column: Vacature Informatie and Match Details -->
        <div class="flex flex-col gap-5 lg:gap-7.5">
            <!-- Vacature Informatie -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Vacature Informatie
                    </h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">
                                Titel
                            </td>
                            <td class="min-w-48 w-full text-foreground font-normal">
                                @if($match->vacancy)
                                    {{ $match->vacancy->title }}
                                @else
                                    <span class="text-muted-foreground">Vacature niet gevonden</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Bedrijf
                            </td>
                            <td class="text-foreground font-normal">
                                @if($match->vacancy && $match->vacancy->company)
                                    <a class="text-foreground hover:text-primary" href="{{ route('admin.companies.show', $match->vacancy->company) }}">
                                        {{ $match->vacancy->company->name }}
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Locatie
                            </td>
                            <td class="text-foreground font-normal">
                                @if($match->vacancy && $match->vacancy->location)
                                    {{ $match->vacancy->location }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                Contactpersoon
                            </td>
                            <td class="text-foreground font-normal">
                                @if($match->vacancy)
                                    @php
                                        $contactUser = $match->vacancy->contactUser;
                                        $contactName = $contactUser ? trim(($contactUser->first_name ?? '') . ' ' . ($contactUser->middle_name ?? '') . ' ' . ($contactUser->last_name ?? '')) : $match->vacancy->contact_name;
                                        $contactEmail = $contactUser ? $contactUser->email : $match->vacancy->contact_email;
                                        $contactPhone = $contactUser ? $contactUser->phone : $match->vacancy->contact_phone;
                                        $contactPhoto = $contactUser ? $contactUser->photo_blob : $match->vacancy->contact_photo_blob;
                                    @endphp
                                    @if($contactName || $contactEmail || $contactPhone || $contactPhoto)
                                        <div class="flex items-start gap-3">
                                            @if($contactPhoto)
                                                @if($contactUser)
                                                    <img src="{{ route('admin.users.photo', $contactUser) }}" alt="Contactpersoon avatar" class="w-12 h-12 rounded-full object-cover border-2 border-input shrink-0 mt-0.5">
                                                @else
                                                    <img src="{{ route('admin.vacancies.contact-photo', $match->vacancy) }}" alt="Contactpersoon avatar" class="w-12 h-12 rounded-full object-cover border-2 border-input shrink-0 mt-0.5">
                                                @endif
                                            @endif
                                            <div class="flex flex-col gap-1">
                                                @if($contactName)
                                                    <div class="font-medium">{{ $contactName }}</div>
                                                @endif
                                                @if($contactEmail)
                                                    <div>
                                                        <a href="mailto:{{ $contactEmail }}" class="text-primary hover:underline">{{ $contactEmail }}</a>
                                                    </div>
                                                @endif
                                                @if($contactPhone)
                                                    <div>
                                                        <a href="tel:{{ $contactPhone }}" class="text-primary hover:underline">{{ $contactPhone }}</a>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        -
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Type
                            </td>
                            <td class="text-foreground font-normal">
                                @if($match->vacancy && $match->vacancy->employment_type)
                                    {{ ucfirst($match->vacancy->employment_type) }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Salaris
                            </td>
                            <td class="text-foreground font-normal">
                                @if($match->vacancy && $match->vacancy->salary_range)
                                    {{ $match->vacancy->salary_range }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Match Details -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Match Details
                    </h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">
                                Match Score
                            </td>
                            <td class="min-w-48 w-full text-foreground font-normal">
                                @if($match->match_score)
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 bg-muted rounded-full h-2">
                                            <div class="h-2 rounded-full bg-primary" 
                                                 style="width: {{ $match->match_score }}%"></div>
                                        </div>
                                        <span class="text-sm font-medium">{{ $match->match_score }}%</span>
                                    </div>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Status
                            </td>
                            <td class="text-foreground font-normal">
                                @php
                                    $statusColors = [
                                        'pending' => 'warning',
                                        'accepted' => 'success',
                                        'rejected' => 'danger',
                                        'interview_scheduled' => 'info',
                                        'hired' => 'success'
                                    ];
                                    $statusColor = $statusColors[$match->status] ?? 'secondary';
                                @endphp
                                <span class="kt-badge kt-badge-sm kt-badge-{{ $statusColor }}">
                                    {{ ucfirst(str_replace('_', ' ', $match->status)) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                AI Aanbeveling
                            </td>
                            <td class="text-foreground font-normal">
                                @if($match->ai_recommendation)
                                    @php
                                        $recommendationLabels = [
                                            'strong_match' => 'Sterke match',
                                            'good_match' => 'Goede match',
                                            'moderate_match' => 'Matige match',
                                            'weak_match' => 'Zwakke match',
                                            'not_recommended' => 'Niet aanbevolen'
                                        ];
                                    @endphp
                                    {{ $recommendationLabels[$match->ai_recommendation] ?? ucfirst(str_replace('_', ' ', $match->ai_recommendation)) }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Sollicitatiedatum
                            </td>
                            <td class="text-foreground font-normal">
                                @if($match->application_date)
                                    {{ \Carbon\Carbon::parse($match->application_date)->format('d-m-Y') }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Aangemaakt op
                            </td>
                            <td class="text-foreground font-normal">
                                {{ $match->created_at->format('d-m-Y H:i') }}
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Laatst bijgewerkt
                            </td>
                            <td class="text-foreground font-normal">
                                {{ $match->updated_at->format('d-m-Y H:i') }}
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Column: Activity -->
        <div class="kt-card" id="activity_2024">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    Activity
                </h3>
                <div class="flex items-center gap-2">
                    <label class="group text-2sm font-medium inline-flex items-center gap-2">
                        <span class="inline-flex items-center gap-2">
                            Auto refresh:
                            <span class="group-has-checked:hidden">
                                Off
                            </span>
                            <span class="hidden group-has-checked:inline">
                                On
                            </span>
                        </span>
                        <input checked="" class="kt-switch kt-switch-sm" name="auto_refresh" id="auto_refresh" type="checkbox" value="1"/>
                    </label>
                </div>
            </div>
            <div class="kt-card-content" id="activity_content">
                @include('admin.matches.partials.activity', ['activities' => $activities ?? []])
            </div>
        </div>
    </div>
    <!-- end: grid -->

    <!-- Notities & AI Analyse - Full Width -->
    <div class="mt-5 lg:mt-7.5">
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    Notities & AI Analyse
                </h3>
            </div>
            <div class="kt-card-content">
                <div class="flex flex-col gap-5">
                    @if($match->notes)
                        <div>
                            <h4 class="text-sm font-semibold text-secondary-foreground mb-2">Notities</h4>
                            <div class="text-sm text-foreground whitespace-pre-wrap">{{ $match->notes }}</div>
                        </div>
                    @endif
                    @if($match->ai_analysis)
                        <div>
                            <h4 class="text-sm font-semibold text-secondary-foreground mb-2">AI Analyse</h4>
                            <div class="text-sm text-foreground whitespace-pre-wrap">{{ $match->ai_analysis }}</div>
                        </div>
                    @endif
                    @if(!$match->notes && !$match->ai_analysis)
                        <div class="text-sm text-muted-foreground">Geen notities of AI analyse beschikbaar</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Remove all borders between table rows in show forms */
    .kt-table-border-dashed tbody tr {
        border-bottom: none !important;
    }
    /* Uniform row height for all table rows */
    .kt-table-border-dashed tbody tr,
    .kt-table-border-dashed tbody tr td {
        height: auto;
        min-height: 48px;
    }
    .kt-table-border-dashed tbody tr td {
        padding-top: 12px;
        padding-bottom: 12px;
        vertical-align: top;
    }
    
    /* Labels (first column) should align with top of content */
    .kt-table-border-dashed tbody tr td:first-child {
        vertical-align: top;
        padding-top: 12px;
    }
    
    /* Content (second column) should align with top */
    .kt-table-border-dashed tbody tr td:last-child {
        vertical-align: top;
        padding-top: 12px;
    }
</style>
@endpush

@push('scripts')
<script>
    let autoRefreshInterval = null;
    const autoRefreshCheckbox = document.getElementById('auto_refresh');
    const activityContent = document.getElementById('activity_content');
    const matchId = {{ $match->id }};

    function refreshActivity() {
        fetch(`{{ route('admin.matches.show', $match) }}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(response => {
            if (response.headers.get('content-type')?.includes('application/json')) {
                return response.json();
            }
            return response.text().then(html => {
                // Parse the HTML and extract the activity content
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newActivityContent = doc.querySelector('#activity_content');
                if (newActivityContent) {
                    return { html: newActivityContent.innerHTML };
                }
                return { html: '' };
            });
        })
        .then(data => {
            if (data.html) {
                activityContent.innerHTML = data.html;
            }
        })
        .catch(error => {
            console.error('Error refreshing activity:', error);
        });
    }

    function startAutoRefresh() {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
        }
        autoRefreshInterval = setInterval(refreshActivity, 60000); // 1 minute
    }

    function stopAutoRefresh() {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
        }
    }

    if (autoRefreshCheckbox) {
        // Start auto refresh if checkbox is checked
        if (autoRefreshCheckbox.checked) {
            startAutoRefresh();
        }

        // Toggle auto refresh on checkbox change
        autoRefreshCheckbox.addEventListener('change', function() {
            if (this.checked) {
                startAutoRefresh();
            } else {
                stopAutoRefresh();
            }
        });
    }

    // Cleanup on page unload
    window.addEventListener('beforeunload', function() {
        stopAutoRefresh();
    });
</script>
@endpush
