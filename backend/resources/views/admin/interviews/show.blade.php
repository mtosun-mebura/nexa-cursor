@extends('admin.layouts.app')

@section('title', 'Interview Details - #' . $interview->id)

@section('content')

<style>
    .hero-bg {
        background-image: url('{{ asset('assets/media/images/2600x1200/bg-1.png') }}');
    }
    .dark .hero-bg {
        background-image: url('{{ asset('assets/media/images/2600x1200/bg-1-dark.png') }}');
    }
    /* Notities en feedback tekst links uitlijnen en witte ruimte verwijderen */
    .kt-card-content .kt-input {
        text-align: left !important;
        padding-left: 1rem !important;
        padding-right: 1rem !important;
    }
    .kt-card-content .kt-input:empty {
        display: none;
    }
</style>

<div class="bg-center bg-cover bg-no-repeat hero-bg">
    <div class="kt-container-fixed">
        <div class="flex flex-col items-center gap-2 lg:gap-3.5 py-4 lg:pt-5 lg:pb-10">
            @php
                $borderColor = 'border-primary';
                $bgColor = 'bg-primary/10';
                $textColor = 'text-primary';
                switch($interview->status) {
                    case 'scheduled':
                        $borderColor = 'border-blue-500';
                        $bgColor = 'bg-blue-500/10';
                        $textColor = 'text-blue-500';
                        break;
                    case 'completed':
                        $borderColor = 'border-green-500';
                        $bgColor = 'bg-green-500/10';
                        $textColor = 'text-green-500';
                        break;
                    case 'cancelled':
                        $borderColor = 'border-red-500';
                        $bgColor = 'bg-red-500/10';
                        $textColor = 'text-red-500';
                        break;
                    default:
                        $borderColor = 'border-yellow-500';
                        $bgColor = 'bg-yellow-500/10';
                        $textColor = 'text-yellow-500';
                }
            @endphp
            <div class="rounded-full border-3 {{ $borderColor }} size-[100px] shrink-0 flex items-center justify-center {{ $bgColor }} {{ $textColor }} text-2xl font-semibold">
                <i class="ki-filled ki-calendar text-4xl"></i>
            </div>
            <div class="flex items-center gap-1.5">
                <div class="text-xl lg:text-2xl leading-6 font-semibold text-mono">
                    Interview #{{ $interview->id }}
                </div>
            </div>
            <div class="flex flex-wrap justify-center gap-1 lg:gap-4.5 text-sm">
                <div class="flex gap-1.25 items-center">
                    <i class="ki-filled ki-calendar-tick text-base"></i>
                    <span class="text-secondary-foreground">
                        {{ $interview->scheduled_at ? $interview->scheduled_at->format('d-m-Y H:i') : 'Niet gepland' }}
                    </span>
                </div>
                <div class="flex gap-1.25 items-center">
                    <span class="kt-badge kt-badge-sm kt-badge-{{ $interview->status == 'scheduled' ? 'info' : ($interview->status == 'completed' ? 'success' : ($interview->status == 'cancelled' ? 'danger' : 'warning')) }}">
                        {{ ucfirst($interview->status ?? 'Onbekend') }}
                    </span>
                </div>
                <div class="flex gap-1.25 items-center">
                    <i class="ki-filled ki-user text-base"></i>
                    <span class="text-secondary-foreground">
                        {{ trim(($interview->match->candidate->first_name ?? '') . ' ' . ($interview->match->candidate->last_name ?? '')) ?: 'Onbekend' }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="flex items-center flex-wrap md:flex-nowrap lg:items-center justify-between gap-3 lg:gap-6 mb-5 lg:mb-10">
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.interviews.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
        <div class="flex items-center gap-2.5">
            @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('edit-interviews'))
            <a href="{{ route('admin.interviews.edit', $interview) }}" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-notepad-edit me-2"></i>
                Bewerken
            </a>
            @endif
        </div>
    </div>

    <div class="flex flex-col xl:flex-row gap-5 lg:gap-7.5 items-stretch">
        <!-- Interview Informatie -->
        <div class="kt-card flex-1">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    Interview Informatie
                </h3>
            </div>
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal align-top">
                            Kandidaat
                        </td>
                        <td class="min-w-48 w-full text-foreground font-normal">
                            @if($interview->match && $interview->match->candidate)
                                <a href="{{ route('admin.candidates.show', $interview->match->candidate) }}" class="text-primary hover:underline">
                                    {{ trim($interview->match->candidate->first_name . ' ' . $interview->match->candidate->last_name) }}
                                </a>
                            @else
                                Onbekend
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal align-top">
                            Vacature
                        </td>
                        <td class="text-foreground font-normal">
                            @if($interview->match && $interview->match->vacancy)
                                <a href="{{ route('admin.vacancies.show', $interview->match->vacancy) }}" class="text-primary hover:underline">
                                    {{ $interview->match->vacancy->title }}
                                </a>
                            @else
                                Onbekend
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal align-top">
                            Bedrijf
                        </td>
                        <td class="text-foreground font-normal">
                            @if($interview->company)
                                <a href="{{ route('admin.companies.show', $interview->company) }}" class="text-primary hover:underline">
                                    {{ $interview->company->name }}
                                </a>
                            @elseif($interview->match && $interview->match->vacancy && $interview->match->vacancy->company)
                                <a href="{{ route('admin.companies.show', $interview->match->vacancy->company) }}" class="text-primary hover:underline">
                                    {{ $interview->match->vacancy->company->name }}
                                </a>
                            @else
                                Onbekend
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal align-top">
                            Type
                        </td>
                        <td class="text-foreground font-normal">
                            {{ ucfirst($interview->type ?? 'Onbekend') }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal align-top">
                            Status
                        </td>
                        <td class="text-foreground font-normal">
                            <span class="kt-badge kt-badge-sm kt-badge-{{ $interview->status == 'scheduled' ? 'info' : ($interview->status == 'completed' ? 'success' : ($interview->status == 'cancelled' ? 'danger' : 'warning')) }}">
                                {{ ucfirst($interview->status ?? 'Onbekend') }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal align-top">
                            Geplande Datum & Tijd
                        </td>
                        <td class="text-foreground font-normal">
                            {{ $interview->scheduled_at ? $interview->scheduled_at->format('d-m-Y H:i') : 'Niet gepland' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal align-top">
                            Duur (minuten)
                        </td>
                        <td class="text-foreground font-normal">
                            {{ $interview->duration ?? 'Niet opgegeven' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal align-top">
                            Locatie
                        </td>
                        <td class="text-foreground font-normal">
                            {{ $interview->location ?? 'Niet opgegeven' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal align-top">
                            Interviewer Naam
                        </td>
                        <td class="text-foreground font-normal">
                            {{ $interview->interviewer_name ?? 'Niet opgegeven' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal align-top">
                            Interviewer E-mail
                        </td>
                        <td class="text-foreground font-normal">
                            @if($interview->interviewer_email)
                                <a href="mailto:{{ $interview->interviewer_email }}" class="text-primary hover:underline">
                                    {{ $interview->interviewer_email }}
                                </a>
                            @else
                                Niet opgegeven
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Notities & Feedback -->
        @if($interview->notes || $interview->feedback)
        <div class="kt-card flex-1">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    Notities & Feedback
                </h3>
            </div>
            <div class="kt-card-content">
                @if($interview->notes)
                <div class="mb-5">
                    <h4 class="text-sm font-semibold text-secondary-foreground mb-2">Notities</h4>
                    <div class="kt-input pt-3 pb-3 px-4 break-words text-left" style="min-height: 100px; white-space: pre-wrap; text-align: left;">
                        {{ trim($interview->notes) }}
                    </div>
                </div>
                @endif

                @if($interview->feedback)
                <div>
                    <h4 class="text-sm font-semibold text-secondary-foreground mb-2">Feedback</h4>
                    <div class="kt-input pt-3 pb-3 px-4 break-words text-left" style="min-height: 100px; white-space: pre-wrap; text-align: left;">
                        {{ trim($interview->feedback) }}
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

@endsection
