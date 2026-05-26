@extends('admin.layouts.app')

@section('title', 'Kandidaten - ' . $vacancy->title)

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-col gap-5 pb-7.5">
        <div class="flex flex-wrap items-center justify-between gap-5">
            <div>
                <h1 class="text-xl font-medium leading-none text-mono mb-1">
                    Kandidaten voor: {{ $vacancy->title }}
                </h1>
                @if($vacancy->company)
                    <p class="text-sm text-muted-foreground">{{ $vacancy->company->name }}</p>
                @endif
            </div>
            <div class="kt-toggle-group kt-toggle-group-sm" data-kt-tabs="true">
                <a class="kt-btn kt-btn-icon active" data-kt-tab-toggle="#candidates_cards" href="#">
                    <i class="ki-filled ki-category"></i>
                </a>
                <a class="kt-btn kt-btn-icon" data-kt-tab-toggle="#candidates_list" href="#">
                    <i class="ki-filled ki-row-horizontal"></i>
                </a>
            </div>
        </div>
        <div class="flex items-center">
            <a href="{{ route('admin.skillmatching.matches.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug naar Matches
            </a>
        </div>
    </div>

    @if($candidates->count() > 0)
        <div class="flex flex-col items-stretch gap-5 lg:gap-7.5">
            <!-- Cards View -->
            <div id="candidates_cards">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 lg:gap-7.5">
                    @foreach($candidates as $match)
                        @if($match->candidate)
                            <div class="kt-card cursor-pointer candidate-card" data-candidate-id="{{ $match->candidate_id }}" onclick="window.location.href='{{ route('admin.skillmatching.matches.show', $match) }}'">
                                <div class="kt-card-content p-5">
                                    <div class="flex flex-col items-center text-center">
                                        <!-- Avatar -->
                                        <div class="mb-3">
                                            @php
                                                $borderColor = '';
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
                                                        $borderColor = 'border-blue-500';
                                                        break;
                                                    default:
                                                        $borderColor = 'border-input';
                                                }
                                            @endphp
                                            @if($match->candidate->photo_blob)
                                                <div class="rounded-full overflow-hidden border-2 {{ $borderColor }} ring-2 ring-background mx-auto" style="width: 112px; height: 112px; min-width: 112px; min-height: 112px;">
                                                    <img class="w-full h-full object-cover" src="{{ route('admin.candidates.photo', $match->candidate) }}" alt="{{ $match->candidate->first_name }} {{ $match->candidate->last_name }}">
                                                </div>
                                            @else
                                                <div class="rounded-full bg-accent/60 border-2 {{ $borderColor }} ring-2 ring-background flex items-center justify-center mx-auto" style="width: 112px; height: 112px; min-width: 112px; min-height: 112px;">
                                                    <span class="text-2xl font-semibold text-secondary-foreground">
                                                        {{ strtoupper(substr($match->candidate->first_name ?? 'K', 0, 1) . substr($match->candidate->last_name ?? '', 0, 1)) }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                        
                                        <!-- Name -->
                                        <h3 class="text-base font-semibold text-foreground mb-2">
                                            {{ $match->candidate->first_name }} {{ $match->candidate->last_name }} (K)
                                        </h3>
                                        
                                        <!-- Email and Phone -->
                                        <div class="flex items-center justify-center gap-4 flex-wrap mb-4">
                                            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                                <i class="ki-filled ki-sms text-xs"></i>
                                                <span>{{ $match->candidate->email }}</span>
                                            </div>
                                            @if($match->candidate->phone)
                                                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                                    <i class="ki-filled ki-phone text-xs"></i>
                                                    <span>{{ $match->candidate->phone }}</span>
                                                </div>
                                            @endif
                                        </div>
                                        
                                        <!-- Status and Match Score -->
                                        <div class="flex items-center justify-center gap-3 w-full mt-auto">
                                            <!-- Status -->
                                            <div class="flex items-center gap-2">
                                                @switch($match->status)
                                                    @case('pending')
                                                        <span class="kt-badge kt-badge-sm kt-badge-warning">In afwachting</span>
                                                        @break
                                                    @case('accepted')
                                                        <span class="kt-badge kt-badge-sm kt-badge-success">Geaccepteerd</span>
                                                        @break
                                                    @case('rejected')
                                                        <span class="kt-badge kt-badge-sm kt-badge-danger">Afgewezen</span>
                                                        @break
                                                    @case('interview')
                                                        <span class="kt-badge kt-badge-sm kt-badge-info">Interview</span>
                                                        @break
                                                    @default
                                                        <span class="kt-badge kt-badge-sm kt-badge-secondary">{{ ucfirst($match->status) }}</span>
                                                @endswitch
                                            </div>
                                            
                                            <!-- Match Score -->
                                            @if($match->match_score)
                                                <div class="shrink-0">
                                                    <span class="kt-badge kt-badge-sm {{ $match->match_score >= 80 ? 'kt-badge-success' : ($match->match_score >= 60 ? 'kt-badge-warning' : 'kt-badge-danger') }}">
                                                        {{ $match->match_score }}% Match
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            <!-- List View -->
            <div class="hidden" id="candidates_list">
                <div class="flex flex-col gap-5 lg:gap-7.5">
                    @foreach($candidates as $match)
                        @if($match->candidate)
                            <div class="kt-card p-7.5 cursor-pointer candidate-card" data-candidate-id="{{ $match->candidate_id }}" onclick="window.location.href='{{ route('admin.skillmatching.matches.show', $match) }}'">
                                <div class="flex items-center gap-5">
                                    <!-- Avatar -->
                                    <div class="shrink-0">
                                        @php
                                            $borderColor = '';
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
                                                    $borderColor = 'border-blue-500';
                                                    break;
                                                default:
                                                    $borderColor = 'border-input';
                                            }
                                        @endphp
                                        @if($match->candidate->photo_blob)
                                            <div class="rounded-full overflow-hidden border-2 {{ $borderColor }} ring-2 ring-background" style="width: 112px; height: 112px; min-width: 112px; min-height: 112px;">
                                                <img class="w-full h-full object-cover" src="{{ route('admin.candidates.photo', $match->candidate) }}" alt="{{ $match->candidate->first_name }} {{ $match->candidate->last_name }}">
                                            </div>
                                        @else
                                            <div class="rounded-full bg-accent/60 border-2 {{ $borderColor }} ring-2 ring-background flex items-center justify-center" style="width: 112px; height: 112px; min-width: 112px; min-height: 112px;">
                                                <span class="text-2xl font-semibold text-secondary-foreground">
                                                    {{ strtoupper(substr($match->candidate->first_name ?? 'K', 0, 1) . substr($match->candidate->last_name ?? '', 0, 1)) }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <!-- Content -->
                                    <div class="flex-1 min-w-0">
                                        <!-- Name -->
                                        <h3 class="text-base font-semibold text-foreground mb-2">
                                            {{ $match->candidate->first_name }} {{ $match->candidate->last_name }} (K)
                                        </h3>
                                        
                                        <!-- Email and Phone -->
                                        <div class="flex items-center gap-4 flex-wrap mb-4">
                                            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                                <i class="ki-filled ki-sms text-xs"></i>
                                                <span>{{ $match->candidate->email }}</span>
                                            </div>
                                            @if($match->candidate->phone)
                                                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                                    <i class="ki-filled ki-phone text-xs"></i>
                                                    <span>{{ $match->candidate->phone }}</span>
                                                </div>
                                            @endif
                                        </div>
                                        
                                        <!-- Status and Match Score -->
                                        <div class="flex items-center justify-start gap-3">
                                            <!-- Status -->
                                            <div class="flex items-center gap-2">
                                                @switch($match->status)
                                                    @case('pending')
                                                        <span class="kt-badge kt-badge-sm kt-badge-warning">In afwachting</span>
                                                        @break
                                                    @case('accepted')
                                                        <span class="kt-badge kt-badge-sm kt-badge-success">Geaccepteerd</span>
                                                        @break
                                                    @case('rejected')
                                                        <span class="kt-badge kt-badge-sm kt-badge-danger">Afgewezen</span>
                                                        @break
                                                    @case('interview')
                                                        <span class="kt-badge kt-badge-sm kt-badge-info">Interview</span>
                                                        @break
                                                    @default
                                                        <span class="kt-badge kt-badge-sm kt-badge-secondary">{{ ucfirst($match->status) }}</span>
                                                @endswitch
                                            </div>
                                            
                                            <!-- Match Score -->
                                            @if($match->match_score)
                                                <div class="shrink-0">
                                                    <span class="kt-badge kt-badge-sm {{ $match->match_score >= 80 ? 'kt-badge-success' : ($match->match_score >= 60 ? 'kt-badge-warning' : 'kt-badge-danger') }}">
                                                        {{ $match->match_score }}% Match
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    @else
        <div class="kt-card min-w-full">
            <div class="kt-card-content">
                <div class="flex flex-col items-center justify-center py-10">
                    <i class="ki-filled ki-information-5 text-4xl text-muted-foreground mb-4"></i>
                    <p class="text-muted-foreground">Geen kandidaten gevonden voor deze vacature.</p>
                </div>
            </div>
        </div>
    @endif
</div>

@push('styles')
<style>
    .candidate-card {
        transition: all 0.2s ease;
    }
    .candidate-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
</style>
@endpush

@endsection
