@extends('admin.layouts.app')

@section('title', 'Sollicitaties - ' . $vacancy->title)

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                {{ $vacancy->title }}
            </h1>
            <div class="mt-3">
                <a href="{{ route('admin.skillmatching.vacancies.index') }}" class="kt-btn kt-btn-outline">
                    <i class="ki-filled ki-arrow-left me-2"></i>
                    Terug naar Overzicht
                </a>
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.skillmatching.vacancies.show', $vacancy->id) }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-eye me-2"></i>
                Vacature Details
            </a>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="kt-container-fixed">
        <div class="kt-alert kt-alert-success mb-5 auto-dismiss" role="alert" id="success-alert">
            <i class="ki-filled ki-check-circle me-2"></i>
            {{ session('success') }}
        </div>
    </div>
@endif

<div class="kt-container-fixed">
    <div class="kt-card">
        <div class="kt-card-header">
            <h3 class="kt-card-title">
                Sollicitaties ({{ $applications->count() }})
            </h3>
        </div>
        <div class="kt-card-content p-0">
            @if($applications->count() > 0)
                <div class="overflow-x-auto">
                    <table class="kt-table" id="applications_table">
                        <thead>
                            <tr>
                                <th class="px-5 py-3 min-w-[200px]">
                                    <span class="kt-table-col">
                                        <span class="kt-table-col-label">
                                            Kandidaat
                                        </span>
                                    </span>
                                </th>
                                <th class="px-5 py-3 min-w-[150px]">
                                    <span class="kt-table-col">
                                        <span class="kt-table-col-label">
                                            E-mail
                                        </span>
                                    </span>
                                </th>
                                <th class="px-5 py-3 min-w-[120px]">
                                    <span class="kt-table-col">
                                        <span class="kt-table-col-label">
                                            Status
                                        </span>
                                    </span>
                                </th>
                                <th class="px-5 py-3 min-w-[120px]">
                                    <span class="kt-table-col">
                                        <span class="kt-table-col-label">
                                            Match Score
                                        </span>
                                    </span>
                                </th>
                                <th class="px-5 py-3 min-w-[140px]">
                                    <span class="kt-table-col">
                                        <span class="kt-table-col-label">
                                            Sollicitatie Datum
                                        </span>
                                    </span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($applications as $application)
                            <tr class="cursor-pointer hover:bg-muted/50" onclick="window.location.href='{{ route('admin.users.show', ['user' => $application->user->id, 'from_applications' => '1', 'vacancy_id' => $vacancy->id]) }}'">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        @if($application->user->photo_blob)
                                            <div class="w-10 h-10 rounded-full overflow-hidden bg-muted flex items-center justify-center">
                                                <img src="{{ $application->user && $application->user->photo_blob ? route('secure.photo', ['token' => $application->user->getPhotoToken()]) : asset('assets/media/avatars/300-2.png') }}" 
                                                     alt="{{ $application->user->first_name }} {{ $application->user->last_name }}"
                                                     class="w-full h-full object-cover">
                                            </div>
                                        @else
                                            <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
                                                <span class="text-primary font-medium text-sm">
                                                    {{ strtoupper(substr($application->user->first_name ?? 'U', 0, 1) . substr($application->user->last_name ?? '', 0, 1)) }}
                                                </span>
                                            </div>
                                        @endif
                                        <div>
                                            <div class="leading-none font-medium text-sm text-mono">
                                                @php
                                                    $fullName = trim(($application->user->first_name ?? '') . ' ' . ($application->user->middle_name ?? '') . ' ' . ($application->user->last_name ?? '')) ?: 'Onbekend';
                                                    $isCandidate = $application->user->hasRole('candidate');
                                                @endphp
                                                {{ $fullName }}{{ $isCandidate ? ' (K)' : '' }}
                                            </div>
                                            @if($application->user->phone)
                                                <span class="text-xs text-muted-foreground">{{ $application->user->phone }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="text-sm text-foreground">{{ $application->user->email }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    @php
                                        $statusColors = [
                                            'matched' => 'kt-badge-primary',
                                            'submitted' => 'kt-badge-success',
                                            'interview' => 'kt-badge-warning',
                                            'offer' => 'kt-badge-info',
                                            'rejected' => 'kt-badge-destructive',
                                        ];
                                        $statusLabels = [
                                            'matched' => 'Gematched',
                                            'submitted' => 'Ingediend',
                                            'interview' => 'Gesprek',
                                            'offer' => 'Aanbod',
                                            'rejected' => 'Afgewezen',
                                        ];
                                        $statusColor = $statusColors[$application->status] ?? 'kt-badge-secondary';
                                        $statusLabel = $statusLabels[$application->status] ?? ucfirst($application->status);
                                    @endphp
                                    <span class="kt-badge {{ $statusColor }} kt-badge-outline rounded-[30px]">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    @if($application->match_score)
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-medium text-foreground">{{ number_format($application->match_score, 1) }}%</span>
                                            <div class="flex-1 bg-muted rounded-full h-2 max-w-[100px]">
                                                <div class="bg-primary h-2 rounded-full" style="width: {{ min($application->match_score, 100) }}%"></div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-sm text-muted-foreground">-</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <span class="text-sm text-foreground">
                                        {{ $application->created_at->format('d-m-Y H:i') }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <i class="ki-filled ki-abstract-26 text-6xl text-muted-foreground mb-4"></i>
                    <h5 class="text-lg font-semibold text-foreground mb-2">Geen sollicitaties gevonden</h5>
                    <p class="text-sm text-muted-foreground">Er zijn nog geen sollicitaties ontvangen voor deze vacature.</p>
                </div>
            @endif
        </div>
    </div>
</div>

@endsection

