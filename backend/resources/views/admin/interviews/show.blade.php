@extends('admin.layouts.app')

@section('title', 'Interview Details - #' . $interview->id)

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono mb-3">
                Interview Details
            </h1>
            <div class="mt-3 flex gap-2">
                <a href="{{ route('admin.agenda.index') }}" class="kt-btn kt-btn-outline">
                    <i class="ki-filled ki-arrow-left me-2"></i>
                    Terug naar Agenda
                </a>
                <a href="{{ route('admin.interviews.index') }}" class="kt-btn kt-btn-outline">
                    <i class="ki-filled ki-arrow-left me-2"></i>
                    Terug naar Overzicht
                </a>
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.interviews.edit', $interview) }}" class="kt-btn kt-btn-warning">
                <i class="ki-filled ki-notepad-edit me-2"></i>
                Bewerken
            </a>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="flex flex-col items-stretch grow">
        <!-- Interview Informatie -->
        <div class="kt-card min-w-full pb-2.5">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    Interview Informatie
                </h3>
            </div>
            <div class="kt-card-content grid gap-5">
                <div class="w-full">
                    <div class="flex items-center py-3">
                        <label class="kt-form-label flex items-center gap-1 max-w-56">
                            Kandidaat
                        </label>
                        <div class="flex-1">
                            {{ trim(($interview->match->candidate->first_name ?? '') . ' ' . ($interview->match->candidate->last_name ?? '')) ?: 'Onbekend' }}
                        </div>
                    </div>
                </div>

                <div class="w-full">
                    <div class="flex items-center py-3">
                        <label class="kt-form-label flex items-center gap-1 max-w-56">
                            Vacature
                        </label>
                        <div class="flex-1">
                            {{ $interview->match->vacancy->title ?? 'Onbekend' }}
                        </div>
                    </div>
                </div>

                <div class="w-full">
                    <div class="flex items-center py-3">
                        <label class="kt-form-label flex items-center gap-1 max-w-56">
                            Bedrijf
                        </label>
                        <div class="flex-1">
                            {{ $interview->company->name ?? ($interview->match->vacancy->company->name ?? 'Onbekend') }}
                        </div>
                    </div>
                </div>

                <div class="w-full">
                    <div class="flex items-center py-3">
                        <label class="kt-form-label flex items-center gap-1 max-w-56">
                            Type
                        </label>
                        <div class="flex-1">
                            {{ ucfirst($interview->type ?? 'Onbekend') }}
                        </div>
                    </div>
                </div>

                <div class="w-full">
                    <div class="flex items-center py-3">
                        <label class="kt-form-label flex items-center gap-1 max-w-56">
                            Status
                        </label>
                        <div class="flex-1">
                            <span class="kt-badge kt-badge-{{ $interview->status == 'scheduled' ? 'info' : ($interview->status == 'completed' ? 'success' : ($interview->status == 'cancelled' ? 'danger' : 'warning')) }}">
                                {{ ucfirst($interview->status ?? 'Onbekend') }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="w-full">
                    <div class="flex items-center py-3">
                        <label class="kt-form-label flex items-center gap-1 max-w-56">
                            Geplande Datum & Tijd
                        </label>
                        <div class="flex-1">
                            {{ $interview->scheduled_at ? $interview->scheduled_at->format('d-m-Y H:i') : 'Niet gepland' }}
                        </div>
                    </div>
                </div>

                <div class="w-full">
                    <div class="flex items-center py-3">
                        <label class="kt-form-label flex items-center gap-1 max-w-56">
                            Duur (minuten)
                        </label>
                        <div class="flex-1">
                            {{ $interview->duration ?? 'Niet opgegeven' }}
                        </div>
                    </div>
                </div>

                <div class="w-full">
                    <div class="flex items-center py-3">
                        <label class="kt-form-label flex items-center gap-1 max-w-56">
                            Locatie
                        </label>
                        <div class="flex-1">
                            {{ $interview->location ?? 'Niet opgegeven' }}
                        </div>
                    </div>
                </div>

                <div class="w-full">
                    <div class="flex items-center py-3">
                        <label class="kt-form-label flex items-center gap-1 max-w-56">
                            Interviewer Naam
                        </label>
                        <div class="flex-1">
                            {{ $interview->interviewer_name ?? 'Niet opgegeven' }}
                        </div>
                    </div>
                </div>

                <div class="w-full">
                    <div class="flex items-center py-3">
                        <label class="kt-form-label flex items-center gap-1 max-w-56">
                            Interviewer E-mail
                        </label>
                        <div class="flex-1">
                            {{ $interview->interviewer_email ?? 'Niet opgegeven' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notities & Feedback -->
        @if($interview->notes || $interview->feedback)
        <div class="kt-card min-w-full pb-2.5">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    Notities & Feedback
                </h3>
            </div>
            <div class="kt-card-content grid gap-5">
                @if($interview->notes)
                <div class="w-full">
                    <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                        <label class="kt-form-label flex items-center gap-1 max-w-56">
                            Notities
                        </label>
                        <div class="flex-1">
                            <div class="kt-input pt-1" style="min-height: 100px;">
                                {{ $interview->notes }}
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                @if($interview->feedback)
                <div class="w-full">
                    <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                        <label class="kt-form-label flex items-center gap-1 max-w-56">
                            Feedback
                        </label>
                        <div class="flex-1">
                            <div class="kt-input pt-1" style="min-height: 100px;">
                                {{ $interview->feedback }}
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
