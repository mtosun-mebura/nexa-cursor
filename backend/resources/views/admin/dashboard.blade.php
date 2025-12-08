@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
@if($isCompanyView && $selectedCompany)
    {{-- Company Profile View --}}
    @include('admin.dashboard.company-profile', ['company' => $selectedCompany, 'stats' => $stats])
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
    <div class="grid gap-5 xl:grid-cols-3">
        <div class="kt-card xl:col-span-2 overflow-hidden bg-gradient-to-r from-[#0f172a] via-[#111827] to-[#0b1324] text-white">
            <div class="kt-card-body p-7 lg:p-10">
                <div class="flex flex-col gap-6">
                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-6">
                        <div class="space-y-3">
                            <div class="text-xs uppercase tracking-[0.18em] text-white/60">Metronic Dark Sidebar</div>
                            <h1 class="text-3xl font-semibold leading-tight">
                                Platform overzicht
                            </h1>
                            <p class="text-sm text-white/70">
                                Direct inzicht in gebruikers, vacatures, matches, interviews en inkomsten.
                            </p>
                        </div>
                        <div class="flex flex-col items-start md:items-end gap-3">
                            <span class="kt-badge kt-badge-light">{{ now()->translatedFormat('d M Y') }}</span>
                            <div class="flex items-center gap-2 text-sm text-white/70">
                                <i class="ki-filled ki-profile-circle text-lg"></i>
                                {{ auth()->user()->first_name ?? auth()->user()->email }}
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="kt-card bg-white/5 ring-1 ring-white/10">
                            <div class="kt-card-body flex items-center justify-between gap-3 p-4">
                                <div>
                                    <div class="text-sm text-white/70">Gebruikers</div>
                                    <div class="text-2xl font-semibold">{{ $stats['total_users'] ?? 0 }}</div>
                                </div>
                                <span class="size-10 rounded-full bg-white/10 flex items-center justify-center">
                                    <i class="ki-filled ki-people text-lg"></i>
                                </span>
                            </div>
                        </div>
                        <div class="kt-card bg-white/5 ring-1 ring-white/10">
                            <div class="kt-card-body flex items-center justify-between gap-3 p-4">
                                <div>
                                    <div class="text-sm text-white/70">Vacatures</div>
                                    <div class="text-2xl font-semibold">{{ $stats['total_vacancies'] ?? 0 }}</div>
                                </div>
                                <span class="size-10 rounded-full bg-white/10 flex items-center justify-center">
                                    <i class="ki-filled ki-briefcase text-lg"></i>
                                </span>
                            </div>
                        </div>
                        <div class="kt-card bg-white/5 ring-1 ring-white/10">
                            <div class="kt-card-body flex items-center justify-between gap-3 p-4">
                                <div>
                                    <div class="text-sm text-white/70">Matches</div>
                                    <div class="text-2xl font-semibold">{{ $stats['total_matches'] ?? 0 }}</div>
                                </div>
                                <span class="size-10 rounded-full bg-white/10 flex items-center justify-center">
                                    <i class="ki-filled ki-abstract-38 text-lg"></i>
                                </span>
                            </div>
                        </div>
                        <div class="kt-card bg-white/5 ring-1 ring-white/10">
                            <div class="kt-card-body flex items-center justify-between gap-3 p-4">
                                <div>
                                    <div class="text-sm text-white/70">Inkomsten</div>
                                    <div class="text-2xl font-semibold">€{{ number_format((float)($financials['total_revenue'] ?? 0), 2, ',', '.') }}</div>
                                </div>
                                <span class="size-10 rounded-full bg-white/10 flex items-center justify-center">
                                    <i class="ki-filled ki-wallet text-lg"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('admin.users.index') }}" class="kt-btn kt-btn-light kt-btn-sm">Gebruikers</a>
                        <a href="{{ route('admin.vacancies.index') }}" class="kt-btn kt-btn-light kt-btn-sm">Vacatures</a>
                        <a href="{{ route('admin.matches.index') }}" class="kt-btn kt-btn-light kt-btn-sm">Matches</a>
                        @if($isSuperAdmin)
                            <a href="{{ route('admin.payments.index') }}" class="kt-btn kt-btn-light kt-btn-sm">Betalingen</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="kt-card h-full">
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
        <div class="kt-card">
            <div class="kt-card-body flex items-start justify-between gap-4 p-6">
                <div class="space-y-1">
                    <div class="text-sm text-secondary-foreground">Gebruikers</div>
                    <div class="text-2xl font-semibold text-mono">{{ $stats['total_users'] ?? 0 }}</div>
                    <div class="text-xs text-secondary-foreground">Nieuwe accounts en admins</div>
                </div>
                <span class="size-12 rounded-full bg-primary/10 flex items-center justify-center">
                    <i class="ki-filled ki-people text-primary text-xl"></i>
                </span>
            </div>
        </div>
        <div class="kt-card">
            <div class="kt-card-body flex items-start justify-between gap-4 p-6">
                <div class="space-y-1">
                    <div class="text-sm text-secondary-foreground">Bedrijven</div>
                    <div class="text-2xl font-semibold text-mono">{{ $stats['total_companies'] ?? 0 }}</div>
                    <div class="text-xs text-secondary-foreground">Actieve klanten in het platform</div>
                </div>
                <span class="size-12 rounded-full bg-success/10 flex items-center justify-center">
                    <i class="ki-filled ki-abstract-26 text-success text-xl"></i>
                </span>
            </div>
        </div>
        <div class="kt-card">
            <div class="kt-card-body flex items-start justify-between gap-4 p-6">
                <div class="space-y-1">
                    <div class="text-sm text-secondary-foreground">Vacatures</div>
                    <div class="text-2xl font-semibold text-mono">{{ $stats['total_vacancies'] ?? 0 }}</div>
                    <div class="flex items-center gap-2 text-xs text-secondary-foreground">
                        <span class="inline-flex items-center gap-1">
                            <span class="size-2 rounded-full bg-success"></span> {{ $stats['active_vacancies'] ?? 0 }} actief
                        </span>
                    </div>
                </div>
                <span class="size-12 rounded-full bg-warning/10 flex items-center justify-center">
                    <i class="ki-filled ki-briefcase text-warning text-xl"></i>
                </span>
            </div>
        </div>
        <div class="kt-card">
            <div class="kt-card-body flex items-start justify-between gap-4 p-6">
                <div class="space-y-1">
                    <div class="text-sm text-secondary-foreground">Matches</div>
                    <div class="text-2xl font-semibold text-mono">{{ $stats['total_matches'] ?? 0 }}</div>
                    <div class="text-xs text-secondary-foreground">{{ $stats['pending_matches'] ?? 0 }} openstaand</div>
                </div>
                <span class="size-12 rounded-full bg-info/10 flex items-center justify-center">
                    <i class="ki-filled ki-abstract-38 text-info text-xl"></i>
                </span>
            </div>
        </div>
        <div class="kt-card">
            <div class="kt-card-body flex items-start justify-between gap-4 p-6">
                <div class="space-y-1">
                    <div class="text-sm text-secondary-foreground">Interviews</div>
                    <div class="text-2xl font-semibold text-mono">{{ $stats['total_interviews'] ?? 0 }}</div>
                    <div class="text-xs text-secondary-foreground">{{ $stats['completed_interviews'] ?? 0 }} afgerond</div>
                </div>
                <span class="size-12 rounded-full bg-danger/10 flex items-center justify-center">
                    <i class="ki-filled ki-calendar text-danger text-xl"></i>
                </span>
            </div>
        </div>
        <div class="kt-card">
            <div class="kt-card-body flex items-start justify-between gap-4 p-6">
                <div class="space-y-1">
                    <div class="text-sm text-secondary-foreground">Omzet</div>
                    <div class="text-2xl font-semibold text-mono">€{{ number_format((float)($financials['total_revenue'] ?? 0), 0, ',', '.') }}</div>
                    <div class="text-xs text-secondary-foreground">Gem. ticket €{{ number_format((float)($financials['average_ticket'] ?? 0), 2, ',', '.') }}</div>
                </div>
                <span class="size-12 rounded-full bg-primary/10 flex items-center justify-center">
                    <i class="ki-filled ki-wallet text-primary text-xl"></i>
                </span>
            </div>
        </div>
    </div>

    <!-- Pipeline & Activity -->
    <div class="grid gap-5 lg:grid-cols-3">
        <div class="kt-card lg:col-span-1">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    <i class="ki-filled ki-route me-2"></i>
                    Pipeline status
                </h3>
            </div>
            <div class="kt-card-body space-y-6">
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
        <div class="kt-card lg:col-span-2">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    <i class="ki-filled ki-activity me-2"></i>
                    Recente activiteit
                </h3>
            </div>
            <div class="kt-card-body grid md:grid-cols-2 gap-4">
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
                                        {{ $match->user->first_name ?? 'Kandidaat' }} → {{ $match->vacancy->title ?? 'Vacature' }}
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
    <div class="grid gap-5 lg:grid-cols-3">
        <div class="kt-card min-w-full">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    <i class="ki-filled ki-people me-2"></i>
                    Recente gebruikers
                </h3>
                <a href="{{ route('admin.users.index') }}" class="kt-btn kt-btn-sm kt-btn-outline">
                    Bekijk alle
                </a>
            </div>
            <div class="kt-card-content p-0">
                <div class="kt-table-responsive">
                    <table class="kt-table align-middle">
                        <thead>
                            <tr>
                                <th class="min-w-48">Naam</th>
                                <th class="min-w-48">E-mail</th>
                                <th class="min-w-32">Datum</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recent_users ?? [] as $user)
                            <tr>
                                <td>
                                    <div class="font-medium text-foreground">
                                        {{ $user->first_name }} {{ $user->last_name }}
                                    </div>
                                    <div class="text-xs text-secondary-foreground">
                                        {{ $user->company->name ?? 'Geen bedrijf' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="text-sm text-secondary-foreground">
                                        {{ $user->email }}
                                    </div>
                                </td>
                                <td>
                                    <div class="text-sm text-muted-foreground">
                                        {{ $user->created_at->format('d-m-Y') }}
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted-foreground py-5">
                                    Geen recente gebruikers
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

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
                            <span class="size-2 rounded-full bg-success"></span>
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
});
</script>
@endpush
@endif
@endsection
