@extends('admin.layouts.app')

@section('title', 'Vacature Details - ' . $vacancy->title)

@section('content')

@php
    $status = (string)($vacancy->status ?? '');
@endphp

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
    <!-- Container -->
    <div class="kt-container-fixed">
        <div class="flex flex-col items-center gap-2 lg:gap-3.5 py-4 lg:pt-5 lg:pb-10">
            @if($vacancy->company && $vacancy->company->logo_blob)
                <div class="rounded-lg shrink-0 inline-block" style="background: transparent; padding: 3px;">
                    <img class="rounded-lg w-auto object-contain bg-transparent dark:bg-transparent" style="height: 80px; display: block; padding: 8px;" src="{{ route('admin.companies.logo', $vacancy->company) }}" alt="{{ $vacancy->company->name }}">
                </div>
            @elseif($vacancy->company)
                <div class="rounded-lg border-3 border-primary h-[100px] w-[100px] lg:h-[150px] lg:w-[150px] shrink-0 flex items-center justify-center bg-primary/10 text-primary text-2xl font-semibold">
                    {{ strtoupper(substr($vacancy->company->name, 0, 2)) }}
                </div>
            @else
                <div class="rounded-lg border-3 border-primary h-[100px] w-[100px] lg:h-[150px] lg:w-[150px] shrink-0 flex items-center justify-center bg-primary/10 text-primary text-2xl font-semibold">
                    <i class="ki-filled ki-briefcase text-3xl"></i>
                </div>
            @endif
            <div class="flex items-center gap-1.5">
                <div class="text-xl lg:text-2xl leading-6 font-semibold text-mono">
                    {{ $vacancy->title }}
                </div>
            </div>
            <div class="flex flex-wrap justify-center gap-1 lg:gap-4.5 text-sm">
                @if($vacancy->company)
                    <div class="flex gap-1.25 items-center">
                        <x-heroicon-o-building-office-2 class="w-4 h-4 text-muted-foreground" />
                        <span class="text-secondary-foreground font-medium">{{ $vacancy->company->name }}</span>
                    </div>
                @endif
                @if($vacancy->branch)
                    <div class="flex gap-1.25 items-center">
                        <i class="ki-filled ki-tag text-muted-foreground text-sm"></i>
                        <span class="text-secondary-foreground font-medium">{{ $vacancy->branch->name }}</span>
                    </div>
                @endif
                <div class="flex gap-1.25 items-center">
                    @if($status === 'Open')
                        <span class="kt-badge kt-badge-sm kt-badge-success">Open</span>
                    @elseif($status === 'Gesloten')
                        <span class="kt-badge kt-badge-sm kt-badge-danger">Gesloten</span>
                    @elseif($status === 'In behandeling')
                        <span class="kt-badge kt-badge-sm kt-badge-warning">In behandeling</span>
                    @else
                        <span class="kt-badge kt-badge-sm kt-badge-secondary">{{ $status ?: '-' }}</span>
                    @endif
                </div>
                @if($vacancy->location)
                    <div class="flex gap-1.25 items-center">
                        <i class="ki-filled ki-geolocation text-muted-foreground text-sm"></i>
                        <span class="text-secondary-foreground font-medium">{{ $vacancy->location }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="flex items-center flex-wrap md:flex-nowrap lg:items-center justify-between gap-3 lg:gap-6 mb-5 lg:mb-10 mt-5">
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.vacancies.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
        <div class="flex items-center gap-2.5">
            @can('edit-vacancies')
                <a href="{{ route('admin.vacancies.edit', $vacancy) }}" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-notepad-edit me-2"></i>
                    Bewerken
                </a>
            @endcan
        </div>
    </div>
</div>

<!-- Tabs -->
<div class="kt-container-fixed">
    <div class="kt-card">
        <div class="kt-tabs kt-tabs-line justify-between px-5 mb-2.5" data-kt-tabs="true">
            <div class="flex items-center gap-5">
                <button class="kt-tab-toggle py-5 active" data-kt-tab-toggle="#vacancy_tab_overview">
                    <span class="kt-tab-title">Overzicht</span>
                </button>
                <button class="kt-tab-toggle py-5" data-kt-tab-toggle="#vacancy_tab_matches">
                    <span class="kt-tab-title">
                        Matches
                        @if($matches->count() > 0)
                            <span class="kt-badge kt-badge-sm kt-badge-primary ms-2">{{ $matches->count() }}</span>
                        @endif
                    </span>
                </button>
                <button class="kt-tab-toggle py-5" data-kt-tab-toggle="#vacancy_tab_applications">
                    <span class="kt-tab-title">
                        Sollicitaties
                        @if($applications->count() > 0)
                            <span class="kt-badge kt-badge-sm kt-badge-info ms-2">{{ $applications->count() }}</span>
                        @endif
                    </span>
                </button>
            </div>
        </div>

        <!-- Tab Content: Overview -->
        <div class="kt-tab-content active" id="vacancy_tab_overview">
            <div class="kt-card-content">
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-5 lg:gap-7.5">
                    <!-- Vacature -->
                    <div class="kt-card">
                        <div class="kt-card-header">
                            <h3 class="kt-card-title">Vacature</h3>
                        </div>
                        <div class="kt-card-table kt-scrollable-x-auto pb-3">
                            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                                <tr>
                                    <td class="min-w-56 text-secondary-foreground font-normal">Titel</td>
                                    <td class="min-w-48 w-full text-foreground font-normal">{{ $vacancy->title }}</td>
                                </tr>
                                <tr>
                                    <td class="text-secondary-foreground font-normal">Status</td>
                                    <td class="text-foreground font-normal">
                                        @if($status === 'Open')
                                            <span class="kt-badge kt-badge-sm kt-badge-success">Open</span>
                                        @elseif($status === 'Gesloten')
                                            <span class="kt-badge kt-badge-sm kt-badge-danger">Gesloten</span>
                                        @elseif($status === 'In behandeling')
                                            <span class="kt-badge kt-badge-sm kt-badge-warning">In behandeling</span>
                                        @else
                                            <span class="kt-badge kt-badge-sm kt-badge-secondary">{{ $status ?: '-' }}</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-secondary-foreground font-normal">Bedrijf</td>
                                    <td class="text-foreground font-normal">{{ $vacancy->company?->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-secondary-foreground font-normal">Branch</td>
                                    <td class="text-foreground font-normal">{{ $vacancy->branch?->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-secondary-foreground font-normal">Locatie</td>
                                    <td class="text-foreground font-normal">{{ $vacancy->location ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-secondary-foreground font-normal align-top">Contactpersoon</td>
                                    <td class="text-foreground font-normal">
                                        @php
                                            $contactUser = $vacancy->contactUser;
                                            $contactName = $contactUser ? trim(($contactUser->first_name ?? '') . ' ' . ($contactUser->middle_name ?? '') . ' ' . ($contactUser->last_name ?? '')) : $vacancy->contact_name;
                                            $contactEmail = $contactUser ? $contactUser->email : $vacancy->contact_email;
                                            $contactPhone = $contactUser ? $contactUser->phone : $vacancy->contact_phone;
                                            $contactPhoto = $contactUser ? $contactUser->photo_blob : $vacancy->contact_photo_blob;
                                        @endphp
                                        @if($contactName || $contactEmail || $contactPhone || $contactPhoto)
                                            <div class="flex items-start gap-3">
                                                @if($contactPhoto)
                                                    @if($contactUser)
                                                        <img src="{{ route('admin.users.photo', $contactUser) }}" alt="Contactpersoon avatar" class="w-12 h-12 rounded-full object-cover border-2 border-input shrink-0 mt-0.5">
                                                    @else
                                                        <img src="{{ route('admin.vacancies.contact-photo', $vacancy) }}" alt="Contactpersoon avatar" class="w-12 h-12 rounded-full object-cover border-2 border-input shrink-0 mt-0.5">
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
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-secondary-foreground font-normal">Dienstverband</td>
                                    <td class="text-foreground font-normal">{{ $vacancy->employment_type ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-secondary-foreground font-normal">Salarisrange</td>
                                    <td class="text-foreground font-normal">{{ $vacancy->salary_range ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Datums & opties -->
                    <div class="kt-card">
                        <div class="kt-card-header">
                            <h3 class="kt-card-title">Datums & opties</h3>
                        </div>
                        <div class="kt-card-table kt-scrollable-x-auto pb-3">
                            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                                <tr>
                                    <td class="min-w-56 text-secondary-foreground font-normal">Publicatie</td>
                                    <td class="min-w-48 w-full text-foreground font-normal">{{ optional($vacancy->publication_date)->format('d-m-Y') ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-secondary-foreground font-normal">Sluiting</td>
                                    <td class="text-foreground font-normal">{{ optional($vacancy->closing_date)->format('d-m-Y') ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-secondary-foreground font-normal">Werkuren</td>
                                    <td class="text-foreground font-normal">{{ $vacancy->working_hours ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-secondary-foreground font-normal">Reiskosten</td>
                                    <td class="text-foreground font-normal">{{ $vacancy->travel_expenses ? 'Vergoed' : 'Niet vergoed' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-secondary-foreground font-normal">Remote</td>
                                    <td class="text-foreground font-normal">{{ $vacancy->remote_work ? 'Mogelijk' : 'Niet mogelijk' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-secondary-foreground font-normal">Taal</td>
                                    <td class="text-foreground font-normal">{{ $vacancy->language ?? 'Nederlands' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="kt-card mt-5 lg:mt-7.5">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">Beschrijving</h3>
                    </div>
                    <div class="kt-card-content">
                        <div class="prose dark:prose-invert max-w-none text-secondary-foreground">
                            {!! nl2br(e($vacancy->description)) !!}
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-5 lg:gap-7.5 mt-5 lg:mt-7.5">
                    <div class="kt-card">
                        <div class="kt-card-header"><h3 class="kt-card-title">Vereisten</h3></div>
                        <div class="kt-card-content">
                            <div class="text-secondary-foreground">{!! nl2br(e($vacancy->requirements ?? '-')) !!}</div>
                        </div>
                    </div>
                    <div class="kt-card">
                        <div class="kt-card-header"><h3 class="kt-card-title">Aanbod</h3></div>
                        <div class="kt-card-content">
                            <div class="text-secondary-foreground">{!! nl2br(e($vacancy->offer ?? '-')) !!}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Content: Matches -->
        <div class="kt-tab-content hidden" id="vacancy_tab_matches">
            <div class="kt-card-content">
                @if($matches->count() > 0)
                    <div class="grid grid-cols-1 gap-4">
                        @foreach($matches as $match)
                            @php
                                $candidate = $match->candidate;
                                $score = $match->match_score ?? 0;
                                $scoreClass = $score >= 70 ? 'match-score-high' : ($score >= 40 ? 'match-score-medium' : 'match-score-low');
                            @endphp
                            <div class="kt-card hover:shadow-lg transition-shadow cursor-pointer" onclick="window.location.href='{{ route('admin.vacancies.candidate', ['vacancy' => $vacancy->id, 'candidate' => $candidate->id, 'type' => 'match', 'match_id' => $match->id]) }}'">
                                <div class="kt-card-content">
                                    <div class="flex items-center justify-between gap-4">
                                        <div class="flex items-center gap-4 flex-1">
                                            @if($candidate->photo_blob)
                                                <div class="rounded-full h-12 w-12 shrink-0 overflow-hidden border-2 border-primary">
                                                    <img class="w-full h-full object-cover" src="{{ route('admin.candidates.photo', $candidate) }}" alt="{{ $candidate->first_name }} {{ $candidate->last_name }}">
                                                </div>
                                            @else
                                                <div class="rounded-full h-12 w-12 shrink-0 flex items-center justify-center bg-primary/10 border-2 border-primary">
                                                    <span class="text-primary font-semibold text-base">
                                                        {{ strtoupper(substr($candidate->first_name ?? '?', 0, 1) . substr($candidate->last_name ?? '?', 0, 1)) }}
                                                    </span>
                                                </div>
                                            @endif
                                            <div class="flex flex-col gap-1 flex-1">
                                                <div class="font-semibold text-mono text-base">
                                                    {{ $candidate->first_name }} {{ $candidate->last_name }}
                                                </div>
                                                <div class="text-sm text-secondary-foreground">
                                                    {{ $candidate->email }}
                                                </div>
                                                @if($candidate->phone)
                                                    <div class="text-sm text-muted-foreground">
                                                        <i class="ki-filled ki-phone text-xs"></i> {{ $candidate->phone }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <div class="text-right">
                                                <div class="text-xs text-muted-foreground mb-1">Match Score</div>
                                                <div class="text-2xl font-bold {{ $scoreClass }}">
                                                    {{ number_format($score, 0) }}%
                                                </div>
                                            </div>
                                            <div>
                                                <i class="ki-filled ki-right text-xl text-muted-foreground"></i>
                                            </div>
                                        </div>
                                    </div>
                                    @if($match->status)
                                        <div class="mt-3 pt-3 border-t border-border">
                                            <span class="kt-badge kt-badge-sm kt-badge-outline">
                                                Status: {{ $match->status }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-10">
                        <i class="ki-filled ki-information-2 text-4xl text-muted-foreground mb-4"></i>
                        <p class="text-muted-foreground">Geen matches gevonden voor deze vacature.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Tab Content: Applications -->
        <div class="kt-tab-content hidden" id="vacancy_tab_applications">
            <div class="kt-card-content">
                @if($applications->count() > 0)
                    <div class="grid grid-cols-1 gap-4">
                        @foreach($applications as $application)
                            @php
                                $candidate = $application->candidate;
                            @endphp
                            <div class="kt-card hover:shadow-lg transition-shadow cursor-pointer" onclick="window.location.href='{{ route('admin.vacancies.candidate', ['vacancy' => $vacancy->id, 'candidate' => $candidate->id, 'type' => 'application', 'application_id' => $application->id]) }}'">
                                <div class="kt-card-content">
                                    <div class="flex items-center justify-between gap-4">
                                        <div class="flex items-center gap-4 flex-1">
                                            @if($candidate->photo_blob)
                                                <div class="rounded-full h-12 w-12 shrink-0 overflow-hidden border-2 border-info">
                                                    <img class="w-full h-full object-cover" src="{{ route('admin.candidates.photo', $candidate) }}" alt="{{ $candidate->first_name }} {{ $candidate->last_name }}">
                                                </div>
                                            @else
                                                <div class="rounded-full h-12 w-12 shrink-0 flex items-center justify-center bg-info/10 border-2 border-info">
                                                    <span class="text-info font-semibold text-base">
                                                        {{ strtoupper(substr($candidate->first_name ?? '?', 0, 1) . substr($candidate->last_name ?? '?', 0, 1)) }}
                                                    </span>
                                                </div>
                                            @endif
                                            <div class="flex flex-col gap-1 flex-1">
                                                <div class="font-semibold text-mono text-base">
                                                    {{ $candidate->first_name }} {{ $candidate->last_name }}
                                                </div>
                                                <div class="text-sm text-secondary-foreground">
                                                    {{ $candidate->email }}
                                                </div>
                                                @if($candidate->phone)
                                                    <div class="text-sm text-muted-foreground">
                                                        <i class="ki-filled ki-phone text-xs"></i> {{ $candidate->phone }}
                                                    </div>
                                                @endif
                                                <div class="text-xs text-muted-foreground mt-1">
                                                    <i class="ki-filled ki-calendar text-xs"></i> 
                                                    Sollicitatie: {{ $application->created_at->format('d-m-Y H:i') }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <span class="kt-badge kt-badge-sm kt-badge-info">
                                                Zelf gereageerd
                                            </span>
                                            <div>
                                                <i class="ki-filled ki-right text-xl text-muted-foreground"></i>
                                            </div>
                                        </div>
                                    </div>
                                    @if($application->status)
                                        <div class="mt-3 pt-3 border-t border-border">
                                            <span class="kt-badge kt-badge-sm kt-badge-outline">
                                                Status: {{ $application->status }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-10">
                        <i class="ki-filled ki-information-2 text-4xl text-muted-foreground mb-4"></i>
                        <p class="text-muted-foreground">Geen sollicitaties gevonden voor deze vacature.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if URL has a hash for tab activation
    const hash = window.location.hash;
    if (hash) {
        // Wait a bit for tabs to initialize
        setTimeout(function() {
            const tabButton = document.querySelector(`[data-kt-tab-toggle="${hash}"]`);
            if (tabButton) {
                tabButton.click();
            }
        }, 100);
    }
});
</script>
@endpush

@endsection
