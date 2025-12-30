@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
@if($isCompanyView && $selectedCompany)
    {{-- Company Profile View --}}
    @include('admin.dashboard.company-profile', ['company' => $selectedCompany, 'stats' => $stats ?? []])
@elseif($isCompanyView && !$selectedCompany)
    {{-- Tenant geselecteerd maar company niet gevonden --}}
    <div class="kt-card">
        <div class="kt-card-content">
            <div class="alert alert-warning">
                <i class="ki-filled ki-information-5"></i>
                De geselecteerde tenant kon niet worden gevonden. Selecteer een andere tenant of ga terug naar het overzicht.
            </div>
            <a href="{{ route('admin.dashboard') }}" class="kt-btn kt-btn-primary mt-4">
                Terug naar dashboard
            </a>
        </div>
    </div>
@else
    {{-- Super Admin Dashboard View --}}
<div class="flex flex-col gap-7.5">
    @php
        $matchRate = ($stats['total_matches'] ?? 0) > 0
            ? round((($stats['total_matches'] ?? 0) - ($stats['pending_matches'] ?? 0)) / max(1, $stats['total_matches']) * 100, 1)
            : 0;
        $activeVacancyRate = ($stats['total_vacancies'] ?? 0) > 0
            ? round(($stats['active_vacancies'] ?? 0) / max(1, $stats['total_vacancies']) * 100, 1)
            : 0;
        $completedInterviewsRate = ($stats['total_interviews'] ?? 0) > 0
            ? round(($stats['completed_interviews'] ?? 0) / max(1, $stats['total_interviews']) * 100, 1)
            : 0;
        $maxRevenue = max(1, $revenue_trend->max('total') ?? 0);
        $isSuperAdmin = auth()->user()->hasRole('super-admin');
    @endphp

    <!-- Hero + Revenue -->
    <div class="grid gap-5 grid-cols-1 xl:grid-cols-5">
        <div class="kt-card xl:col-span-4 overflow-hidden bg-gradient-to-r from-slate-50 via-slate-100 to-slate-50 dark:from-[#0f172a] dark:via-[#111827] dark:to-[#0b1324] text-foreground dark:text-white w-full">
            <div class="kt-card-body p-7 lg:p-10">
                <div class="flex flex-col gap-6">
                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-6">
                        <div class="space-y-3">
                            <h1 class="text-3xl font-semibold leading-tight text-foreground dark:text-white">
                                Nexa overzicht
                            </h1>
                            <p class="text-sm text-secondary-foreground dark:text-white/70">
                                Direct inzicht in gebruikers, vacatures, matches, interviews en inkomsten.
                            </p>
                        </div>
                        <div class="flex flex-col items-start md:items-end gap-3">
                            <span class="kt-badge kt-badge-light">{{ now()->translatedFormat('d M Y') }}</span>
                            <div class="flex items-center gap-2 text-sm text-secondary-foreground dark:text-white/70">
                                <i class="ki-filled ki-profile-circle text-lg"></i>
                                {{ auth()->user()->first_name ?? auth()->user()->email }}
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="kt-card bg-background/50 dark:bg-white/5 ring-1 ring-border dark:ring-white/10">
                            <div class="kt-card-body flex items-center justify-between gap-3 p-4">
                                <div>
                                    <div class="text-sm text-secondary-foreground dark:text-white/70">Gebruikers</div>
                                    <div class="text-2xl font-semibold text-foreground dark:text-white">{{ $stats['total_users'] ?? 0 }}</div>
                                </div>
                                <span class="size-10 rounded-full bg-primary/10 dark:bg-white/10 flex items-center justify-center">
                                    <i class="ki-filled ki-people text-lg text-primary dark:text-white"></i>
                                </span>
                            </div>
                        </div>
                        <div class="kt-card bg-background/50 dark:bg-white/5 ring-1 ring-border dark:ring-white/10">
                            <div class="kt-card-body flex items-center justify-between gap-3 p-4">
                                <div>
                                    <div class="text-sm text-secondary-foreground dark:text-white/70">Vacatures</div>
                                    <div class="text-2xl font-semibold text-foreground dark:text-white">{{ $stats['total_vacancies'] ?? 0 }}</div>
                                </div>
                                <span class="size-10 rounded-full bg-primary/10 dark:bg-white/10 flex items-center justify-center">
                                    <i class="ki-filled ki-briefcase text-lg text-primary dark:text-white"></i>
                                </span>
                            </div>
                        </div>
                        <div class="kt-card bg-background/50 dark:bg-white/5 ring-1 ring-border dark:ring-white/10">
                            <div class="kt-card-body flex items-center justify-between gap-3 p-4">
                                <div>
                                    <div class="text-sm text-secondary-foreground dark:text-white/70">Matches</div>
                                    <div class="text-2xl font-semibold text-foreground dark:text-white">{{ $stats['total_matches'] ?? 0 }}</div>
                                </div>
                                <span class="size-10 rounded-full bg-primary/10 dark:bg-white/10 flex items-center justify-center">
                                    <i class="ki-filled ki-abstract-38 text-lg text-primary dark:text-white"></i>
                                </span>
                            </div>
                        </div>
                        <div class="kt-card bg-background/50 dark:bg-white/5 ring-1 ring-border dark:ring-white/10">
                            <div class="kt-card-body flex items-center justify-between gap-3 p-4">
                                <div>
                                    <div class="text-sm text-secondary-foreground dark:text-white/70">Inkomsten</div>
                                    <div class="text-2xl font-semibold text-foreground dark:text-white">€{{ number_format((float)($financials['total_revenue'] ?? 0), 2, ',', '.') }}</div>
                                </div>
                                <span class="size-10 rounded-full bg-primary/10 dark:bg-white/10 flex items-center justify-center">
                                    <i class="ki-filled ki-wallet text-lg text-primary dark:text-white"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="kt-card h-full w-full">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    <i class="ki-filled ki-chart-line-up-2 me-2"></i>
                    Inkomsten
                </h3>
                <div class="flex gap-5">
                    <div class="text-sm text-secondary-foreground">
                        Totale omzet: <span class="font-semibold text-foreground">€{{ number_format((float)($financials['total_revenue'] ?? 0), 2, ',', '.') }}</span>
                    </div>
                </div>
            </div>
            <div class="kt-card-body flex flex-col justify-end items-stretch grow px-3 py-1">
                <div id="earnings_chart"></div>
            </div>
            <div class="kt-card-footer">
                <div class="grid grid-cols-2 gap-4 w-full">
                    <div class="flex items-center justify-between rounded-lg border border-input px-4 py-3">
                        <div class="text-sm text-secondary-foreground">Betaald</div>
                        <div class="text-lg font-semibold text-success">{{ $financials['paid_payments'] ?? 0 }}</div>
                    </div>
                    <div class="flex items-center justify-between rounded-lg border border-input px-4 py-3">
                        <div class="text-sm text-secondary-foreground">Openstaand</div>
                        <div class="text-lg font-semibold text-warning">{{ $financials['pending_payments'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6 gap-5">
        <div class="kt-card w-full">
            <div class="kt-card-body flex items-start justify-between gap-4 p-6">
                <div class="space-y-1 flex-1 min-w-0">
                    <div class="text-sm text-secondary-foreground">Gebruikers</div>
                    <div class="text-2xl font-semibold text-mono">{{ $stats['total_users'] ?? 0 }}</div>
                    <div class="text-xs text-secondary-foreground">Totaal aantal gebruikers</div>
                </div>
                <span class="size-12 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                    <i class="ki-filled ki-people text-primary text-xl"></i>
                </span>
            </div>
        </div>
        <div class="kt-card w-full">
            <div class="kt-card-body flex items-start justify-between gap-4 p-6">
                <div class="space-y-1 flex-1 min-w-0">
                    <div class="text-sm text-secondary-foreground">Bedrijven</div>
                    <div class="text-2xl font-semibold text-mono">{{ $stats['total_companies'] ?? 0 }}</div>
                    <div class="text-xs text-secondary-foreground">Actieve klanten</div>
                </div>
                <span class="size-12 rounded-full bg-success/10 flex items-center justify-center shrink-0">
                    <i class="ki-filled ki-abstract-26 text-success text-xl"></i>
                </span>
            </div>
        </div>
        <div class="kt-card w-full">
            <div class="kt-card-body flex items-start justify-between gap-4 p-6">
                <div class="space-y-1 flex-1 min-w-0">
                    <div class="text-sm text-secondary-foreground">Vacatures</div>
                    <div class="text-2xl font-semibold text-mono">{{ $stats['total_vacancies'] ?? 0 }}</div>
                    <div class="text-xs text-secondary-foreground">
                        <span class="inline-flex items-center gap-1">
                            <span class="size-2 rounded-full bg-success"></span> {{ $stats['active_vacancies'] ?? 0 }} actief
                        </span>
                    </div>
                </div>
                <span class="size-12 rounded-full bg-warning/10 flex items-center justify-center shrink-0">
                    <i class="ki-filled ki-briefcase text-warning text-xl"></i>
                </span>
            </div>
        </div>
        <div class="kt-card w-full">
            <div class="kt-card-body flex items-start justify-between gap-4 p-6">
                <div class="space-y-1 flex-1 min-w-0">
                    <div class="text-sm text-secondary-foreground">Kandidaten</div>
                    <div class="text-2xl font-semibold text-mono">{{ $stats['candidates'] ?? 0 }}</div>
                    <div class="text-xs text-secondary-foreground">Ingeschreven kandidaten</div>
                </div>
                <span class="size-12 rounded-full bg-info/10 flex items-center justify-center shrink-0">
                    <i class="ki-filled ki-profile-user text-info text-xl"></i>
                </span>
            </div>
        </div>
        <div class="kt-card w-full">
            <div class="kt-card-body flex items-start justify-between gap-4 p-6">
                <div class="space-y-1 flex-1 min-w-0">
                    <div class="text-sm text-secondary-foreground">Matches</div>
                    <div class="text-2xl font-semibold text-mono">{{ $stats['total_matches'] ?? 0 }}</div>
                    <div class="text-xs text-secondary-foreground">
                        <span class="inline-flex items-center gap-1">
                            <span class="size-2 rounded-full bg-warning"></span> {{ $stats['pending_matches'] ?? 0 }} openstaand
                        </span>
                    </div>
                </div>
                <span class="size-12 rounded-full bg-info/10 flex items-center justify-center shrink-0">
                    <i class="ki-filled ki-abstract-38 text-info text-xl"></i>
                </span>
            </div>
        </div>
        <div class="kt-card w-full">
            <div class="kt-card-body flex items-start justify-between gap-4 p-6">
                <div class="space-y-1 flex-1 min-w-0">
                    <div class="text-sm text-secondary-foreground">Interviews</div>
                    <div class="text-2xl font-semibold text-mono">{{ $stats['total_interviews'] ?? 0 }}</div>
                    <div class="text-xs text-secondary-foreground">
                        {{ $stats['interviews_leading_to_match'] ?? 0 }} tot match
                    </div>
                </div>
                <span class="size-12 rounded-full bg-danger/10 flex items-center justify-center shrink-0">
                    <i class="ki-filled ki-calendar text-danger text-xl"></i>
                </span>
            </div>
        </div>
    </div>

    @if($isSuperAdmin && !session('selected_tenant'))
    <!-- Uitgebreide Statistieken voor Super Admin -->
    <div class="grid gap-5 lg:grid-cols-2">
        <!-- Match Statussen -->
        <div class="kt-card w-full">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    <i class="ki-filled ki-abstract-38 me-2"></i>
                    Match Statussen
                </h3>
            </div>
            <div class="kt-card-body">
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex items-center justify-between p-4 rounded-lg border border-input">
                        <div class="flex items-center gap-3">
                            <span class="size-10 rounded-full bg-warning/10 flex items-center justify-center">
                                <i class="ki-filled ki-time text-warning"></i>
                            </span>
                            <div>
                                <div class="text-sm text-secondary-foreground">In Afwachting</div>
                                <div class="text-xl font-semibold text-mono">{{ $stats['pending_matches'] ?? 0 }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between p-4 rounded-lg border border-input">
                        <div class="flex items-center gap-3">
                            <span class="size-10 rounded-full bg-success/10 flex items-center justify-center">
                                <i class="ki-filled ki-check-circle text-success"></i>
                            </span>
                            <div>
                                <div class="text-sm text-secondary-foreground">Geaccepteerd</div>
                                <div class="text-xl font-semibold text-mono">{{ $stats['accepted_matches'] ?? 0 }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between p-4 rounded-lg border border-input">
                        <div class="flex items-center gap-3">
                            <span class="size-10 rounded-full bg-danger/10 flex items-center justify-center">
                                <i class="ki-filled ki-cross-circle text-danger"></i>
                            </span>
                            <div>
                                <div class="text-sm text-secondary-foreground">Afgewezen</div>
                                <div class="text-xl font-semibold text-mono">{{ $stats['rejected_matches'] ?? 0 }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between p-4 rounded-lg border border-input">
                        <div class="flex items-center gap-3">
                            <span class="size-10 rounded-full bg-info/10 flex items-center justify-center">
                                <i class="ki-filled ki-calendar text-info"></i>
                            </span>
                            <div>
                                <div class="text-sm text-secondary-foreground">Interview</div>
                                <div class="text-xl font-semibold text-mono">{{ $stats['interview_matches'] ?? 0 }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between p-4 rounded-lg border border-input col-span-2">
                        <div class="flex items-center gap-3">
                            <span class="size-10 rounded-full bg-primary/10 flex items-center justify-center">
                                <i class="ki-filled ki-check text-primary"></i>
                            </span>
                            <div>
                                <div class="text-sm text-secondary-foreground">Aangenomen</div>
                                <div class="text-xl font-semibold text-mono">{{ $stats['hired_matches'] ?? 0 }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Interviews Statistieken -->
        <div class="kt-card w-full">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    <i class="ki-filled ki-calendar me-2"></i>
                    Interviews Overzicht
                </h3>
            </div>
            <div class="kt-card-body space-y-4">
                <div class="flex items-center justify-between p-4 rounded-lg border border-input">
                    <div class="flex items-center gap-3">
                        <span class="size-10 rounded-full bg-primary/10 flex items-center justify-center">
                            <i class="ki-filled ki-calendar text-primary"></i>
                        </span>
                        <div>
                            <div class="text-sm text-secondary-foreground">Totaal Interviews</div>
                            <div class="text-xl font-semibold text-mono">{{ $stats['total_interviews'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-between p-4 rounded-lg border border-input">
                    <div class="flex items-center gap-3">
                        <span class="size-10 rounded-full bg-success/10 flex items-center justify-center">
                            <i class="ki-filled ki-check-circle text-success"></i>
                        </span>
                        <div>
                            <div class="text-sm text-secondary-foreground">Afgerond</div>
                            <div class="text-xl font-semibold text-mono">{{ $stats['completed_interviews'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-between p-4 rounded-lg border border-input">
                    <div class="flex items-center gap-3">
                        <span class="size-10 rounded-full bg-info/10 flex items-center justify-center">
                            <i class="ki-filled ki-abstract-38 text-info"></i>
                        </span>
                        <div>
                            <div class="text-sm text-secondary-foreground">Leidend tot Match</div>
                            <div class="text-xl font-semibold text-mono">{{ $stats['interviews_leading_to_match'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gebruikers en Vacatures per Bedrijf -->
    <div class="grid gap-5 lg:grid-cols-2">
        <div class="kt-card w-full">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    <i class="ki-filled ki-people me-2"></i>
                    Gebruikers per Bedrijf
                </h3>
            </div>
            <div class="kt-card-content p-0">
                <div class="kt-table-responsive">
                    <table class="kt-table align-middle">
                        <thead>
                            <tr>
                                <th class="min-w-48">Bedrijf</th>
                                <th class="min-w-24 text-right">Aantal Gebruikers</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stats['users_per_company'] ?? [] as $item)
                            <tr>
                                <td>
                                    <div class="font-medium text-foreground">{{ $item['company_name'] }}</div>
                                </td>
                                <td class="text-right">
                                    <div class="text-sm font-semibold text-mono">{{ $item['user_count'] }}</div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted-foreground py-5">
                                    Geen data beschikbaar
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="kt-card w-full">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    <i class="ki-filled ki-briefcase me-2"></i>
                    Vacatures per Bedrijf
                </h3>
            </div>
            <div class="kt-card-content p-0">
                <div class="kt-table-responsive">
                    <table class="kt-table align-middle">
                        <thead>
                            <tr>
                                <th class="min-w-48">Bedrijf</th>
                                <th class="min-w-24 text-right">Aantal Vacatures</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stats['vacancies_per_company'] ?? [] as $item)
                            <tr>
                                <td>
                                    <div class="font-medium text-foreground">{{ $item['company_name'] }}</div>
                                </td>
                                <td class="text-right">
                                    <div class="text-sm font-semibold text-mono">{{ $item['vacancy_count'] }}</div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted-foreground py-5">
                                    Geen data beschikbaar
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Facturen en Opbrengsten Grafiek -->
    <div class="kt-card w-full">
        <div class="kt-card-header">
            <h3 class="kt-card-title">
                <i class="ki-filled ki-chart-line-up-2 me-2"></i>
                Facturen en Opbrengsten per Jaar
            </h3>
            <div class="flex gap-5">
                <div class="text-sm text-secondary-foreground">
                    {{ now()->year }}: <span class="font-semibold text-foreground">{{ $stats['current_year_invoices'] ?? 0 }} facturen</span>
                </div>
                <div class="text-sm text-secondary-foreground">
                    Opbrengst: <span class="font-semibold text-success">€{{ number_format((float)($stats['current_year_revenue'] ?? 0), 2, ',', '.') }}</span>
                </div>
            </div>
        </div>
        <div class="kt-card-body flex flex-col justify-end items-stretch grow px-3 py-1">
            <div id="invoices_revenue_chart"></div>
        </div>
    </div>
    @endif

    <!-- Pipeline & Activity -->
    <div class="grid gap-5 lg:grid-cols-3">
        <div class="kt-card lg:col-span-1 flex flex-col w-full">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    <i class="ki-filled ki-route me-2"></i>
                    Pipeline status
                </h3>
            </div>
            <div class="kt-card-body space-y-6 flex-1 p-6">
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-secondary-foreground">Match succes</span>
                        <span class="font-semibold">{{ $matchRate }}%</span>
                    </div>
                    <div class="h-2 rounded-full bg-slate-200 dark:bg-slate-800">
                        <div class="h-2 rounded-full bg-primary" style="width: {{ $matchRate }}%"></div>
                    </div>
                    <p class="text-xs text-secondary-foreground">Openstaande matches: {{ $stats['pending_matches'] ?? 0 }}</p>
                </div>
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-secondary-foreground">Actieve vacatures</span>
                        <span class="font-semibold">{{ $activeVacancyRate }}%</span>
                    </div>
                    <div class="h-2 rounded-full bg-slate-200 dark:bg-slate-800">
                        <div class="h-2 rounded-full bg-success" style="width: {{ $activeVacancyRate }}%"></div>
                    </div>
                    <p class="text-xs text-secondary-foreground">{{ $stats['active_vacancies'] ?? 0 }} actief van {{ $stats['total_vacancies'] ?? 0 }}</p>
                </div>
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-secondary-foreground">Interview afronding</span>
                        <span class="font-semibold">{{ $completedInterviewsRate }}%</span>
                    </div>
                    <div class="h-2 rounded-full bg-slate-200 dark:bg-slate-800">
                        <div class="h-2 rounded-full bg-info" style="width: {{ $completedInterviewsRate }}%"></div>
                    </div>
                    <p class="text-xs text-secondary-foreground">{{ $stats['completed_interviews'] ?? 0 }} afgerond van {{ $stats['total_interviews'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        <div class="kt-card lg:col-span-2 flex flex-col w-full">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    <i class="ki-filled ki-activity me-2"></i>
                    Recente activiteit
                </h3>
            </div>
            <div class="kt-card-body grid md:grid-cols-2 gap-4 flex-1 p-6">
                <div class="space-y-4">
                    <div class="flex items-center justify-between text-sm text-secondary-foreground">
                        <span class="font-semibold text-foreground">Recente matches</span>
                        <a href="{{ route('admin.matches.index') }}" class="kt-btn kt-btn-xs kt-btn-outline">Alle matches</a>
                    </div>
                    <div class="divide-y divide-border">
                        @forelse($recent_matches as $match)
                            <div class="py-3 flex items-start gap-3">
                                <span class="size-10 rounded-full bg-info/10 text-info flex items-center justify-center">
                                    <i class="ki-filled ki-abstract-38"></i>
                                </span>
                                <div class="flex flex-col">
                                    <div class="font-semibold text-sm">
                                        {{ $match->candidate->first_name ?? 'Kandidaat' }} → {{ $match->vacancy->title ?? 'Vacature' }}
                                    </div>
                                    <div class="text-xs text-secondary-foreground">
                                        {{ ucfirst($match->status ?? 'onbekend') }} • {{ optional($match->created_at)->format('d-m-Y') }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="py-3 text-sm text-secondary-foreground">Geen recente matches</div>
                        @endforelse
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="flex items-center justify-between text-sm text-secondary-foreground">
                        <span class="font-semibold text-foreground">Aankomende interviews</span>
                        <a href="{{ route('admin.interviews.index') }}" class="kt-btn kt-btn-xs kt-btn-outline">Alle interviews</a>
                    </div>
                    <div class="divide-y divide-border">
                        @forelse($upcoming_interviews as $interview)
                            <div class="py-3 flex items-start gap-3">
                                <span class="size-10 rounded-full bg-primary/10 text-primary flex items-center justify-center">
                                    <i class="ki-filled ki-calendar"></i>
                                </span>
                                <div class="flex flex-col">
                                    <div class="font-semibold text-sm">
                                        {{ $interview->match->vacancy->title ?? 'Vacature' }}
                                    </div>
                                    <div class="text-xs text-secondary-foreground">
                                        {{ optional($interview->scheduled_at)->format('d-m H:i') }} • {{ $interview->type ?? 'Interview' }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="py-3 text-sm text-secondary-foreground">Geen geplande interviews</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data tables -->
    <div class="grid gap-5 lg:grid-cols-2">
        <div class="kt-card min-w-full">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    <i class="ki-filled ki-briefcase me-2"></i>
                    Vacatures & bedrijven
                </h3>
                <a href="{{ route('admin.vacancies.index') }}" class="kt-btn kt-btn-sm kt-btn-outline">
                    Bekijk alle
                </a>
            </div>
            <div class="kt-card-content p-0">
                <div class="kt-table-responsive">
                    <table class="kt-table align-middle">
                        <thead>
                            <tr>
                                <th class="min-w-64">Vacature</th>
                                <th class="min-w-48">Bedrijf</th>
                                <th class="min-w-32">Datum</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recent_vacancies ?? [] as $vacancy)
                            <tr>
                                <td>
                                    <div class="font-medium text-foreground">
                                        {{ $vacancy->title }}
                                    </div>
                                    <div class="text-xs text-secondary-foreground">
                                        {{ $vacancy->category->name ?? 'Geen categorie' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="text-sm text-secondary-foreground">
                                        {{ $vacancy->company->name ?? 'N/A' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="text-sm text-muted-foreground">
                                        {{ $vacancy->created_at->format('d-m-Y') }}
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted-foreground py-5">
                                    Geen recente vacatures
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-border px-6 py-4">
                    <div class="flex items-center justify-between text-sm text-secondary-foreground">
                        <div class="flex items-center gap-2">
                            <i class="ki-filled ki-abstract-26 text-success text-base"></i>
                            Nieuwste bedrijven
                        </div>
                        <a href="{{ route('admin.companies.index') }}" class="text-primary hover:underline">Alle bedrijven</a>
                    </div>
                    <div class="mt-3 flex flex-col gap-2">
                        @forelse($recent_companies ?? [] as $company)
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-semibold text-foreground">{{ $company->name }}</span>
                                <span class="text-secondary-foreground">{{ $company->created_at->format('d-m') }}</span>
                            </div>
                        @empty
                            <div class="text-sm text-secondary-foreground">Geen nieuwe bedrijven</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="kt-card min-w-full">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    <i class="ki-filled ki-wallet me-2"></i>
                    Recente betalingen
                </h3>
                @if($isSuperAdmin)
                    <a href="{{ route('admin.payments.index') }}" class="kt-btn kt-btn-sm kt-btn-outline">
                        Bekijk alle
                    </a>
                @endif
            </div>
            <div class="kt-card-content p-0">
                <div class="kt-table-responsive">
                    <table class="kt-table align-middle">
                        <thead>
                            <tr>
                                <th class="min-w-40">Bedrijf</th>
                                <th class="min-w-24">Bedrag</th>
                                <th class="min-w-24">Status</th>
                                <th class="min-w-32">Datum</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recent_payments ?? [] as $payment)
                                <tr>
                                    <td>
                                        <div class="font-medium text-foreground">
                                            {{ $payment->company->name ?? 'Onbekend' }}
                                        </div>
                                        <div class="text-xs text-secondary-foreground">
                                            #{{ $payment->id }}
                                        </div>
                                    </td>
                                    <td class="text-sm font-semibold">
                                        €{{ number_format((float)$payment->amount, 2, ',', '.') }}
                                    </td>
                                    <td>
                                        <span class="kt-badge kt-badge-sm {{ $payment->status === 'paid' ? 'kt-badge-success' : 'kt-badge-warning' }}">
                                            {{ ucfirst($payment->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="text-sm text-muted-foreground">
                                            {{ optional($payment->paid_at ?? $payment->created_at)->format('d-m-Y') }}
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted-foreground py-5">
                                        Geen betalingen gevonden
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Prepare revenue data for chart
    const revenueData = @json($revenue_trend->map(function($point) {
        return $point['total'];
    })->values()->all());
    
    const revenueCategories = @json($revenue_trend->map(function($point) {
        return $point['label'];
    })->values()->all());
    
    // Calculate max value for y-axis
    const maxRevenue = Math.max(...(revenueData.length > 0 ? revenueData : [1]));
    const yAxisMax = Math.ceil(maxRevenue * 1.2 / 1000) * 1000; // Round up to nearest 1000
    
    // Initialize Earnings Chart
    const earningsChartElement = document.querySelector('#earnings_chart');
    if (earningsChartElement && typeof ApexCharts !== 'undefined') {
        const options = {
            series: [{
                name: 'Inkomsten',
                data: revenueData.length > 0 ? revenueData : [0]
            }],
            chart: {
                height: 250,
                type: 'area',
                toolbar: {
                    show: false
                }
            },
            dataLabels: {
                enabled: false
            },
            legend: {
                show: false
            },
            stroke: {
                curve: 'smooth',
                show: true,
                width: 3,
                colors: ['var(--color-primary)']
            },
            xaxis: {
                categories: revenueCategories.length > 0 ? revenueCategories : ['Geen data'],
                axisBorder: {
                    show: false
                },
                maxTicks: 12,
                axisTicks: {
                    show: false
                },
                labels: {
                    style: {
                        colors: 'var(--color-muted-foreground)',
                        fontSize: '12px'
                    }
                },
                crosshairs: {
                    position: 'front',
                    stroke: {
                        color: 'var(--color-primary)',
                        width: 1,
                        dashArray: 3
                    }
                },
                tooltip: {
                    enabled: false
                }
            },
            yaxis: {
                min: 0,
                max: yAxisMax,
                tickAmount: 5,
                axisTicks: {
                    show: false
                },
                labels: {
                    style: {
                        colors: 'var(--color-muted-foreground)',
                        fontSize: '12px'
                    },
                    formatter: function(value) {
                        return '€' + (value / 1000).toFixed(0) + 'K';
                    }
                }
            },
            tooltip: {
                enabled: true,
                custom: function({series, seriesIndex, dataPointIndex, w}) {
                    const value = series[seriesIndex][dataPointIndex];
                    const month = w.globals.seriesX[seriesIndex][dataPointIndex];
                    const monthName = revenueCategories[dataPointIndex] || month;
                    const formattedValue = new Intl.NumberFormat('nl-NL', {
                        style: 'currency',
                        currency: 'EUR'
                    }).format(value);
                    
                    return `
                        <div class="flex flex-col gap-2 p-3.5">
                            <div class="font-medium text-2sm text-white">${monthName}, {{ now()->year }} Inkomsten</div>
                            <div class="flex items-center gap-1.5">
                                <div class="font-semibold text-md text-mono">${formattedValue}</div>
                            </div>
                        </div>
                    `;
                }
            },
            markers: {
                size: 0,
                colors: 'var(--color-primary)',
                strokeColors: 'var(--color-primary)',
                strokeWidth: 4,
                strokeOpacity: 1,
                fillOpacity: 1,
                shape: 'circle',
                radius: 2,
                hover: {
                    size: 8
                }
            },
            fill: {
                gradient: {
                    enabled: true,
                    opacityFrom: 0.25,
                    opacityTo: 0
                }
            },
            grid: {
                borderColor: 'var(--color-border)',
                strokeDashArray: 5,
                clipMarkers: false,
                yaxis: {
                    lines: {
                        show: true
                    }
                },
                xaxis: {
                    lines: {
                        show: false
                    }
                }
            }
        };
        
        const chart = new ApexCharts(earningsChartElement, options);
        chart.render();
    }

    @if($isSuperAdmin && !session('selected_tenant'))
    // Initialize Invoices & Revenue Chart
    const invoicesRevenueChartElement = document.querySelector('#invoices_revenue_chart');
    if (invoicesRevenueChartElement && typeof ApexCharts !== 'undefined') {
        const invoicesData = @json(($stats['invoices_per_year'] ?? [])->pluck('invoice_count')->values()->all());
        const revenueDataYear = @json(($stats['invoices_per_year'] ?? [])->pluck('total_revenue')->values()->all());
        const yearCategories = @json(($stats['invoices_per_year'] ?? [])->pluck('year')->values()->all());
        
        const maxRevenueYear = Math.max(...(revenueDataYear.length > 0 ? revenueDataYear : [1]));
        const yAxisMaxYear = Math.ceil(maxRevenueYear * 1.2 / 1000) * 1000;
        
        const optionsYear = {
            series: [
                {
                    name: 'Aantal Facturen',
                    type: 'column',
                    data: invoicesData.length > 0 ? invoicesData : [0]
                },
                {
                    name: 'Opbrengsten (€)',
                    type: 'line',
                    data: revenueDataYear.length > 0 ? revenueDataYear : [0]
                }
            ],
            chart: {
                height: 350,
                type: 'line',
                toolbar: {
                    show: false
                }
            },
            stroke: {
                width: [0, 3],
                curve: 'smooth'
            },
            plotOptions: {
                bar: {
                    columnWidth: '50%'
                }
            },
            dataLabels: {
                enabled: true,
                enabledOnSeries: [1]
            },
            legend: {
                show: true,
                position: 'top'
            },
            xaxis: {
                categories: yearCategories.length > 0 ? yearCategories : ['Geen data'],
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                },
                labels: {
                    style: {
                        colors: 'var(--color-muted-foreground)',
                        fontSize: '12px'
                    }
                }
            },
            yaxis: [
                {
                    title: {
                        text: 'Aantal Facturen',
                        style: {
                            color: 'var(--color-muted-foreground)',
                            fontSize: '12px'
                        }
                    },
                    labels: {
                        style: {
                            colors: 'var(--color-muted-foreground)',
                            fontSize: '12px'
                        }
                    }
                },
                {
                    opposite: true,
                    title: {
                        text: 'Opbrengsten (€)',
                        style: {
                            color: 'var(--color-muted-foreground)',
                            fontSize: '12px'
                        }
                    },
                    min: 0,
                    max: yAxisMaxYear,
                    labels: {
                        style: {
                            colors: 'var(--color-muted-foreground)',
                            fontSize: '12px'
                        },
                        formatter: function(value) {
                            return '€' + (value / 1000).toFixed(0) + 'K';
                        }
                    }
                }
            ],
            tooltip: {
                shared: true,
                intersect: false,
                custom: function({series, seriesIndex, dataPointIndex, w}) {
                    const year = w.globals.seriesX[seriesIndex][dataPointIndex];
                    const invoices = series[0][dataPointIndex];
                    const revenue = series[1][dataPointIndex];
                    const formattedRevenue = new Intl.NumberFormat('nl-NL', {
                        style: 'currency',
                        currency: 'EUR'
                    }).format(revenue);
                    
                    return `
                        <div class="flex flex-col gap-2 p-3.5">
                            <div class="font-medium text-2sm text-white">Jaar ${year}</div>
                            <div class="flex items-center gap-2">
                                <span class="size-2 rounded-full bg-primary"></span>
                                <span class="text-sm">Facturen: <strong>${invoices}</strong></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="size-2 rounded-full bg-success"></span>
                                <span class="text-sm">Opbrengsten: <strong>${formattedRevenue}</strong></span>
                            </div>
                        </div>
                    `;
                }
            },
            colors: ['var(--color-primary)', 'var(--color-success)'],
            grid: {
                borderColor: 'var(--color-border)',
                strokeDashArray: 5,
                yaxis: {
                    lines: {
                        show: true
                    }
                },
                xaxis: {
                    lines: {
                        show: false
                    }
                }
            }
        };
        
        const chartYear = new ApexCharts(invoicesRevenueChartElement, optionsYear);
        chartYear.render();
    }
    @endif
});
</script>
@endpush
@endif
@endsection
