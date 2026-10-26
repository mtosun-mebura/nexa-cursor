@extends('admin.layouts.app')

@section('title', 'Monitoring')

@section('content')
@php
    $sectionUrl = function (string $sectie) use ($period) {
        return route('admin.monitoring.index', ['sectie' => $sectie, 'periode' => $period]);
    };
    $money = fn (float $amount) => '€'.number_format($amount, 2, ',', '.');
    $jobAttention = (int) $queue['stuck'] + (int) $queue['failed'];
    $splitStamp = function (?string $value): array {
        $value = trim((string) $value);
        if (preg_match('/^(\d{2}-\d{2}-\d{4}) (\d{2}:\d{2}(?::\d{2})?)$/', $value, $parts)) {
            return ['date' => $parts[1], 'time' => $parts[2]];
        }

        return ['date' => $value, 'time' => null];
    };
@endphp

<div class="flex flex-col gap-5 lg:gap-7.5 w-full min-w-0">
    <div class="pb-2">
        <h1 class="text-xl font-medium leading-none text-mono">Monitoring</h1>
        <p class="text-sm text-muted-foreground mt-1 mb-0">Controleer of taken doorlopen, waar het vastloopt, en hoe de inkomsten lopen.</p>
    </div>

    <nav class="customer-section-nav grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3 lg:gap-4 w-full min-w-0" aria-label="Monitoringonderdelen">
        <a href="{{ $sectionUrl('jobs') }}" class="customer-section-tile{{ $section === 'jobs' ? ' is-active' : '' }}" @if($section === 'jobs') aria-current="page" @endif>
            <span class="customer-section-tile__label">Jobs</span>
            <span class="customer-section-tile__value">{{ $jobAttention > 0 ? $jobAttention : (int) $queue['running'] }}</span>
            <span class="customer-section-tile__meta">{{ (int) $queue['pending'] }} in wachtrij · {{ (int) $queue['running'] }} bezig · {{ (int) $queue['stuck'] }} vast · {{ (int) $queue['failed'] }} mislukt</span>
        </a>
        <a href="{{ $sectionUrl('fouten') }}" class="customer-section-tile{{ $section === 'fouten' ? ' is-active' : '' }}" @if($section === 'fouten') aria-current="page" @endif>
            <span class="customer-section-tile__label">Fouten</span>
            <span class="customer-section-tile__value">{{ (int) $unresolvedErrors }}</span>
            <span class="customer-section-tile__meta">Onopgelost in het foutenlog</span>
        </a>
        <a href="{{ $sectionUrl('processen') }}" class="customer-section-tile{{ $section === 'processen' ? ' is-active' : '' }}" @if($section === 'processen') aria-current="page" @endif>
            <span class="customer-section-tile__label">Processen</span>
            <span class="customer-section-tile__value">{{ (int) $processes['stalled_count'] }}</span>
            <span class="customer-section-tile__meta">{{ (int) $processes['scheduled_count'] }} geplande taken · aantal dat aandacht vraagt</span>
        </a>
        <a href="{{ $sectionUrl('inkomsten') }}" class="customer-section-tile{{ $section === 'inkomsten' ? ' is-active' : '' }}" @if($section === 'inkomsten') aria-current="page" @endif>
            <span class="customer-section-tile__label">Inkomsten</span>
            <span class="customer-section-tile__value">{{ $money((float) $income['total']) }}</span>
            <span class="customer-section-tile__meta">{{ $income['label'] }}</span>
        </a>
    </nav>

    <div class="customer-section-panel{{ $section === 'jobs' ? ' is-active' : '' }}" id="monitoring-section-jobs">
        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header px-5 py-5 flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0">
                    <h3 class="kt-card-title mb-0">Jobs</h3>
                    <p class="text-sm text-muted-foreground mt-1.5 mb-0">{{ $queue['summary'] }} Verbinding: {{ $queue['connection'] }}.</p>
                </div>
                <form method="POST" action="{{ route('admin.monitoring.jobs.restart-stuck') }}" class="shrink-0" data-admin-confirm="Vastgelopen jobs vrijgeven en mislukte jobs van de laatste 7 dagen opnieuw in de wachtrij zetten?" data-admin-confirm-title="Jobs herstarten" data-admin-confirm-label="Herstarten">
                    @csrf
                    <input type="hidden" name="periode" value="{{ $period }}">
                    <button type="submit" class="kt-btn kt-btn-primary">Herstart vastgelopen jobs</button>
                </form>
            </div>
            <div class="kt-card-content p-0 min-w-0">
                <div id="monitoring-jobs-banner" class="px-5 pt-5" data-reserved="@json($queue['reserved'])" @if(empty($queue['running_banner'])) hidden @endif>
                    <div class="monitoring-run-banner is-running" role="status" aria-live="polite">
                        <span class="monitoring-run-banner__bar" aria-hidden="true"></span>
                        <span class="monitoring-run-banner__text" data-monitoring-jobs-banner-text>{{ $queue['running_banner'] }}</span>
                    </div>
                </div>
                <div class="px-5 pt-5 pb-3">
                    <p class="text-sm text-muted-foreground mb-0">Alleen vastgelopen jobs en mislukte jobs van de laatste 7 dagen worden herstart. Jobs die een betaling, incasso, factuur of terugbetaling uitvoeren worden niet herstart, om dubbele afschrijvingen te voorkomen.</p>
                </div>
                <div class="kt-scrollable-x-auto admin-table-scroll-wrap">
                    <table class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full">
                        <colgroup>
                            <col style="width: 44%">
                            <col style="width: 12%">
                            <col style="width: 11%">
                            <col style="width: 14%">
                            <col style="width: 8%">
                            <col style="width: 11%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th data-label="Job">Job</th>
                                <th data-label="Wachtrij" class="whitespace-nowrap">Wachtrij</th>
                                <th data-label="Status" class="whitespace-nowrap">Status</th>
                                <th data-label="Sinds" class="whitespace-nowrap">Sinds</th>
                                <th data-label="Pogingen" class="whitespace-nowrap">Pogingen</th>
                                <th data-label="Actie" class="whitespace-nowrap">Actie</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($queue['rows'] as $row)
                                <tr>
                                    <td class="font-medium text-foreground">{{ $row['label'] }}</td>
                                    <td class="text-muted-foreground whitespace-nowrap">{{ $row['queue'] }}</td>
                                    <td class="whitespace-nowrap">
                                        @if(in_array($row['kind'], ['stuck', 'failed'], true))
                                            <span class="kt-badge kt-badge-danger kt-badge-sm">{{ $row['status'] }}</span>
                                        @elseif($row['kind'] === 'running')
                                            <span class="kt-badge kt-badge-primary kt-badge-sm">{{ $row['status'] }}</span>
                                        @else
                                            <span class="kt-badge kt-badge-secondary kt-badge-sm">{{ $row['status'] }}</span>
                                        @endif
                                    </td>
                                    <td class="text-muted-foreground whitespace-nowrap">{{ $row['since'] }}</td>
                                    <td class="text-muted-foreground whitespace-nowrap">{{ $row['attempts'] === null ? '—' : $row['attempts'] }}</td>
                                    <td class="whitespace-nowrap">
                                        @if($row['financial'])
                                            <span class="text-sm text-muted-foreground">Niet herstart</span>
                                        @elseif($row['retry_uuid'])
                                            <form method="POST" action="{{ route('admin.monitoring.jobs.retry', $row['retry_uuid']) }}" data-admin-confirm="Deze mislukte job opnieuw in de wachtrij zetten?" data-admin-confirm-title="Job herstarten" data-admin-confirm-label="Herstarten">
                                                @csrf
                                                <input type="hidden" name="periode" value="{{ $period }}">
                                                <button type="submit" class="kt-btn kt-btn-sm kt-btn-outline">Herstart</button>
                                            </form>
                                        @elseif($row['release_id'])
                                            <form method="POST" action="{{ route('admin.monitoring.jobs.release', $row['release_id']) }}" data-admin-confirm="Deze vastgelopen job vrijgeven zodat de worker hem opnieuw pakt?" data-admin-confirm-title="Job vrijgeven" data-admin-confirm-label="Vrijgeven">
                                                @csrf
                                                <input type="hidden" name="periode" value="{{ $period }}">
                                                <button type="submit" class="kt-btn kt-btn-sm kt-btn-outline">Vrijgeven</button>
                                            </form>
                                        @else
                                            <span class="text-sm text-muted-foreground">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted-foreground py-8">Geen jobs in de wachtrij en geen mislukte jobs.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="customer-section-panel{{ $section === 'fouten' ? ' is-active' : '' }}" id="monitoring-section-fouten">
        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header px-5 py-5">
                <h3 class="kt-card-title mb-0">Fouten</h3>
                <p class="text-sm text-muted-foreground mt-1.5 mb-0">Recente regels uit het foutenlog. Onopgeloste fouten geven een melding in de admin en een badge bij Monitoring.</p>
            </div>
            <div class="kt-card-content p-0 min-w-0">
                <div class="kt-scrollable-x-auto admin-table-scroll-wrap">
                    <table class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full">
                        <colgroup>
                            <col style="width: 18%">
                            <col style="width: 14%">
                            <col>
                            <col style="width: 11%">
                            <col style="width: 18%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th data-label="Tijd">Tijd</th>
                                <th data-label="Bron">Bron</th>
                                <th data-label="Bericht">Bericht</th>
                                <th data-label="Status">Status</th>
                                <th data-label="Actie">Actie</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($errorLogs as $error)
                                @php
                                    $errorContextText = '';
                                    if (is_array($error->context) && $error->context !== []) {
                                        $errorContextText = \Illuminate\Support\Str::limit(json_encode($error->context, JSON_UNESCAPED_UNICODE), 180);
                                    }
                                    $errorCopyText = $errorContextText !== ''
                                        ? trim((string) $error->message)."\n".$errorContextText
                                        : (string) $error->message;
                                @endphp
                                <tr>
                                    <td class="text-muted-foreground whitespace-nowrap">{{ $error->created_at?->timezone('Europe/Amsterdam')->format('d-m-Y H:i') }}</td>
                                    <td class="text-muted-foreground">{{ $error->source }}</td>
                                    <td class="text-foreground">
                                        <div class="flex items-start gap-2 min-w-0">
                                            <div class="min-w-0 grow" data-monitoring-error-text>
                                                <div class="break-words">{{ $error->message }}</div>
                                                @if($errorContextText !== '')
                                                    <div class="text-xs text-muted-foreground mt-1 break-all">{{ $errorContextText }}</div>
                                                @endif
                                            </div>
                                            <button type="button"
                                                    class="monitoring-error-copy inline-flex items-center justify-center shrink-0 size-7 bg-transparent border-0 p-0 shadow-none cursor-pointer text-muted-foreground hover:text-foreground"
                                                    data-copy-text="{{ $errorCopyText }}"
                                                    aria-label="Kopiëren">
                                                <i class="ki-filled ki-copy text-sm pointer-events-none" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        @if($error->resolved)
                                            <span class="kt-badge kt-badge-success kt-badge-sm">Opgelost</span>
                                        @else
                                            <span class="kt-badge kt-badge-danger kt-badge-sm">Open</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($error->resolved)
                                            <span class="text-sm text-muted-foreground">—</span>
                                        @else
                                            <form method="POST" action="{{ route('admin.monitoring.errors.resolve', $error) }}">
                                                @csrf
                                                <input type="hidden" name="periode" value="{{ $period }}">
                                                <button type="submit" class="kt-btn kt-btn-sm kt-btn-outline">Markeer opgelost</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted-foreground py-8">Geen fouten vastgelegd.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="customer-section-panel{{ $section === 'processen' ? ' is-active' : '' }}" id="monitoring-section-processen">
        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header px-5 py-5">
                <h3 class="kt-card-title mb-0">Wachtrij</h3>
                <p class="text-sm text-muted-foreground mt-1.5 mb-0">{{ $queue['summary'] }}</p>
            </div>
            <div class="kt-card-content p-5">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    <div class="rounded-lg border border-border px-4 py-3">
                        <div class="text-sm text-muted-foreground">In wachtrij</div>
                        <div class="text-lg font-semibold text-foreground">{{ (int) $queue['pending'] }}</div>
                    </div>
                    <div class="rounded-lg border border-border px-4 py-3">
                        <div class="text-sm text-muted-foreground">Bezig</div>
                        <div class="text-lg font-semibold text-foreground">{{ (int) $queue['running'] }}</div>
                    </div>
                    <div class="rounded-lg border border-border px-4 py-3">
                        <div class="text-sm text-muted-foreground">Vastgelopen</div>
                        <div class="text-lg font-semibold text-foreground">{{ (int) $queue['stuck'] }}</div>
                    </div>
                    <div class="rounded-lg border border-border px-4 py-3">
                        <div class="text-sm text-muted-foreground">Mislukt</div>
                        <div class="text-lg font-semibold text-foreground">{{ (int) $queue['failed'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header px-5 py-5">
                <h3 class="kt-card-title mb-0">Geplande taken</h3>
                <p class="text-sm text-muted-foreground mt-1.5 mb-0">Schema uit de planner. Tijdens een run verschijnt een balk zodra de taak start. Na afloop blijft het tijdstip van afronden staan.</p>
            </div>
            <div class="kt-card-content p-0 min-w-0">
                <div class="kt-scrollable-x-auto admin-table-scroll-wrap">
                    <table class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full">
                        <colgroup>
                            <col style="width: 44%">
                            <col style="width: 18%">
                            <col style="width: 14%">
                            <col style="width: 12%">
                            <col style="width: 12%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th data-label="Taak">Taak</th>
                                <th data-label="Schema">Schema</th>
                                <th data-label="Volgende start" class="whitespace-nowrap">Volgende start</th>
                                <th data-label="Laatste succes">Laatste succes</th>
                                <th data-label="Status" class="whitespace-nowrap">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($processes['tasks'] as $task)
                                @php
                                    $bannerText = $task['running'] ? $task['running_text'] : $task['finished_text'];
                                    $bannerTone = $task['running'] ? 'running' : ($task['finished_text'] ? ($task['status'] === 'Mislukt' ? 'fail' : 'ok') : 'none');
                                @endphp
                                <tr @if($task['key'] !== '') data-monitoring-task="{{ $task['key'] }}" data-run-started="{{ $task['started_at'] }}" data-run-finished="{{ $task['finished_at'] }}" @endif>
                                    <td class="font-medium text-foreground">
                                        <div>{{ $task['name'] }}</div>
                                        <div class="monitoring-run-banner {{ $bannerTone === 'running' ? 'is-running' : 'is-finished' }} {{ $bannerTone === 'fail' ? 'is-fail' : 'is-ok' }}" data-monitoring-banner role="status" aria-live="polite" @if($bannerTone === 'none') hidden @endif>
                                            <span class="monitoring-run-banner__bar" aria-hidden="true"></span>
                                            <span class="monitoring-run-banner__text" data-monitoring-banner-text>{{ $bannerText }}</span>
                                        </div>
                                    </td>
                                    <td class="text-muted-foreground">{{ $task['frequency'] }}</td>
                                    <td class="text-muted-foreground">
                                        @include('admin.monitoring.partials.stamp', ['stamp' => $splitStamp($task['next_run'])])
                                    </td>
                                    <td class="text-muted-foreground" data-monitoring-last-success>
                                        @include('admin.monitoring.partials.stamp', ['stamp' => $splitStamp($task['last_success'])])
                                    </td>
                                    <td>
                                        <span class="{{ $task['badge'] }}" data-monitoring-status>{{ $task['status'] }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted-foreground py-8">Geen geplande taken gevonden.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header px-5 py-5">
                <h3 class="kt-card-title mb-0">Job-batches</h3>
                <p class="text-sm text-muted-foreground mt-1.5 mb-0">Open batches die langer dan twee uur pending blijven, gelden als vastgelopen.</p>
            </div>
            <div class="kt-card-content p-0 min-w-0">
                <div class="kt-scrollable-x-auto admin-table-scroll-wrap">
                    <table class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full">
                        <thead>
                            <tr>
                                <th data-label="Naam">Naam</th>
                                <th data-label="Totaal">Totaal</th>
                                <th data-label="Open">Open</th>
                                <th data-label="Mislukt">Mislukt</th>
                                <th data-label="Gestart">Gestart</th>
                                <th data-label="Status">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($processes['batches'] as $batch)
                                <tr>
                                    <td class="font-medium text-foreground">{{ $batch['name'] }}</td>
                                    <td>{{ $batch['total'] }}</td>
                                    <td>{{ $batch['pending'] }}</td>
                                    <td>{{ $batch['failed'] }}</td>
                                    <td class="text-muted-foreground">{{ $batch['started'] }}</td>
                                    <td>
                                        @if($batch['stalled'] || $batch['failed'] > 0)
                                            <span class="kt-badge kt-badge-danger kt-badge-sm">{{ $batch['status'] }}</span>
                                        @elseif($batch['status'] === 'Afgerond')
                                            <span class="kt-badge kt-badge-success kt-badge-sm">{{ $batch['status'] }}</span>
                                        @else
                                            <span class="kt-badge kt-badge-secondary kt-badge-sm">{{ $batch['status'] }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted-foreground py-8">Geen job-batches.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="customer-section-panel{{ $section === 'inkomsten' ? ' is-active' : '' }}" id="monitoring-section-inkomsten">
        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header px-5 py-5 flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0">
                    <h3 class="kt-card-title mb-0">Inkomsten</h3>
                    <p class="text-sm text-muted-foreground mt-1.5 mb-0">{{ $income['label'] }}: <span class="font-semibold text-foreground">{{ $money((float) $income['total']) }}</span></p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach(['dag' => 'Dag', 'maand' => 'Maand', 'jaar' => 'Jaar'] as $key => $label)
                        <a href="{{ route('admin.monitoring.index', ['sectie' => 'inkomsten', 'periode' => $key]) }}" class="kt-btn kt-btn-sm {{ $period === $key ? 'kt-btn-primary' : 'kt-btn-outline' }}">{{ $label }}</a>
                    @endforeach
                </div>
            </div>
            <div class="kt-card-content p-5">
                <div id="monitoring_income_chart"></div>
                <p class="text-sm text-muted-foreground mt-4 mb-0">{{ $income['source'] }}</p>
            </div>
        </div>
    </div>
</div>
@endsection

@if($section === 'inkomsten')
@include('layouts.partials.apexcharts')
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var el = document.querySelector('#monitoring_income_chart');
    if (!el || typeof ApexCharts === 'undefined') {
        return;
    }
    var labels = @json($income['labels']);
    var data = @json($income['data']);
    var chart = new ApexCharts(el, {
        series: [{ name: 'Inkomsten', data: data.length > 0 ? data : [0] }],
        chart: { height: 280, type: 'area', toolbar: { show: false } },
        dataLabels: { enabled: false },
        legend: { show: false },
        stroke: { curve: 'smooth', width: 3, colors: ['var(--color-primary)'] },
        xaxis: {
            categories: labels.length > 0 ? labels : ['Geen data'],
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: { style: { colors: 'var(--color-secondary-foreground)', fontSize: '12px' } }
        },
        yaxis: {
            min: 0,
            forceNiceScale: true,
            labels: {
                style: { colors: 'var(--color-secondary-foreground)', fontSize: '12px' },
                formatter: function (value) {
                    return '€' + Number(value || 0).toFixed(0);
                }
            }
        },
        colors: ['var(--color-primary)'],
        fill: { type: 'gradient', gradient: { opacityFrom: 0.25, opacityTo: 0 } },
        grid: { borderColor: 'var(--color-border)', strokeDashArray: 5 },
        tooltip: {
            y: {
                formatter: function (value) {
                    return '€' + Number(value || 0).toLocaleString('nl-NL', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
            }
        }
    });
    chart.render();
});
</script>
@endpush
@endif

@push('styles')
<style>
    .monitoring-run-banner {
        position: relative;
        overflow: hidden;
        display: inline-block;
        width: fit-content;
        max-width: 100%;
        margin-top: 0.45rem;
        margin-inline-start: 0;
        margin-inline-end: auto;
        border-radius: 0.375rem;
        padding: 0.4rem 0.7rem;
        font-size: 0.75rem;
        font-weight: 600;
        line-height: 1.35;
        white-space: nowrap;
        word-break: keep-all;
        overflow-wrap: normal;
    }
    .monitoring-run-banner[hidden] {
        display: none !important;
    }
    .monitoring-run-banner.is-running {
        color: #0c4a6e;
        background: #e0f2fe;
        border: 1px solid #38bdf8;
    }
    .monitoring-run-banner.is-finished.is-ok {
        color: #065f46;
        background: #d1fae5;
        border: 1px solid #34d399;
    }
    .monitoring-run-banner.is-finished.is-fail {
        color: #991b1b;
        background: #fee2e2;
        border: 1px solid #f87171;
    }
    .monitoring-run-banner__bar {
        display: none;
    }
    .monitoring-run-banner.is-running .monitoring-run-banner__bar {
        display: block;
        position: absolute;
        inset: 0 auto 0 0;
        width: 35%;
        background: linear-gradient(90deg, rgba(14, 165, 233, 0), rgba(14, 165, 233, 0.55), rgba(14, 165, 233, 0));
        animation: monitoring-run-slide 1.15s linear infinite;
        pointer-events: none;
    }
    .monitoring-run-banner__text {
        position: relative;
        z-index: 1;
    }
    @keyframes monitoring-run-slide {
        from { transform: translateX(-120%); }
        to { transform: translateX(320%); }
    }
    html.dark .monitoring-run-banner.is-running {
        color: #e0f2fe;
        background: #082f49;
        border-color: #0284c7;
    }
    html.dark .monitoring-run-banner.is-running .monitoring-run-banner__bar {
        background: linear-gradient(90deg, rgba(125, 211, 252, 0), rgba(125, 211, 252, 0.55), rgba(125, 211, 252, 0));
    }
    html.dark .monitoring-run-banner.is-finished.is-ok {
        color: #d1fae5;
        background: #064e3b;
        border-color: #059669;
    }
    html.dark .monitoring-run-banner.is-finished.is-fail {
        color: #fee2e2;
        background: #7f1d1d;
        border-color: #ef4444;
    }
    @media (prefers-reduced-motion: reduce) {
        .monitoring-run-banner.is-running .monitoring-run-banner__bar {
            animation: none;
            width: 100%;
            opacity: 0.35;
        }
    }
    #monitoring-jobs-banner[hidden] {
        display: none !important;
    }
    /* appearance-none in styles.css sets width/height 0. That class must stay off this button. */
    .monitoring-error-copy {
        width: 1.75rem;
        height: 1.75rem;
        min-width: 1.75rem;
        min-height: 1.75rem;
        background: transparent;
        box-shadow: none;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var url = @json(route('admin.monitoring.status'));
    var intervalMs = 2000;
    var runHoldMs = 2500;
    var timer = null;
    var runs = {};
    var jobsSeen = {};
    var jobsLive = {};
    var jobsHoldTimer = null;

    function setStackedStamp(el, value) {
        var raw = String(value || '');
        var match = raw.match(/^(\d{2}-\d{2}-\d{4}) (\d{2}:\d{2}(?::\d{2})?)$/);
        var wrap = document.createElement('span');
        var date = document.createElement('span');
        wrap.className = 'monitoring-stamp text-xs leading-snug';
        date.className = 'block';
        date.textContent = match ? match[1] : raw;
        wrap.appendChild(date);
        if (match) {
            var time = document.createElement('span');
            time.className = 'block';
            time.textContent = match[2];
            wrap.appendChild(time);
        }
        el.replaceChildren(wrap);
    }

    function isNewer(next, prev) {
        if (!next) {
            return false;
        }
        if (!prev) {
            return true;
        }
        return String(next) > String(prev);
    }

    function runState(key) {
        if (!runs[key]) {
            runs[key] = {
                started_at: '',
                finished_at: '',
                timer: null,
                holding: false,
                pending: null
            };
        }
        return runs[key];
    }

    function paintBanner(row, mode, text) {
        var banner = row.querySelector('[data-monitoring-banner]');
        var textEl = banner ? banner.querySelector('[data-monitoring-banner-text]') : null;
        if (!banner || !textEl) {
            return;
        }
        if (mode === 'none' || !text) {
            banner.hidden = true;
            return;
        }
        textEl.textContent = text;
        banner.hidden = false;
        banner.classList.toggle('is-finished', mode !== 'running');
        banner.classList.toggle('is-ok', mode === 'ok');
        banner.classList.toggle('is-fail', mode === 'fail');
        if (mode === 'running') {
            banner.classList.remove('is-running');
            void banner.offsetWidth;
            banner.classList.add('is-running');
            return;
        }
        banner.classList.remove('is-running');
    }

    function finishedPending(task) {
        var text = task.finished_text || '';
        if (!text) {
            return null;
        }
        return {
            text: text,
            mode: task.run_status === 'failed' ? 'fail' : 'ok'
        };
    }

    function startedLabel(task) {
        var name = task.name || '';
        if (name) {
            return name + ' is gestart';
        }
        return task.running_text || 'Taak is gestart';
    }

    function applyTask(row, task) {
        var key = row.getAttribute('data-monitoring-task');
        if (!key) {
            return;
        }
        var state = runState(key);
        var started = task.started_at || '';
        var finished = task.finished_at || '';
        var status = row.querySelector('[data-monitoring-status]');
        var lastSuccess = row.querySelector('[data-monitoring-last-success]');
        var newStart = isNewer(started, state.started_at);
        var newFinish = isNewer(finished, state.finished_at);

        if (status) {
            status.textContent = task.status || '';
            if (task.badge) {
                status.className = task.badge;
            }
        }
        if (lastSuccess && typeof task.last_success === 'string') {
            setStackedStamp(lastSuccess, task.last_success);
        }

        if (newStart) {
            state.started_at = started;
            paintBanner(row, 'running', startedLabel(task));
            if (task.run_status === 'running') {
                state.pending = null;
            } else {
                state.finished_at = finished;
                state.pending = finishedPending(task);
            }
            state.holding = true;
            if (state.timer) {
                window.clearTimeout(state.timer);
            }
            state.timer = window.setTimeout(function () {
                state.holding = false;
                state.timer = null;
                if (state.pending && state.pending.text) {
                    paintBanner(row, state.pending.mode, state.pending.text);
                }
            }, runHoldMs);
            return;
        }

        if (state.holding) {
            if (task.run_status === 'running') {
                state.pending = null;
                return;
            }
            if (finished) {
                state.finished_at = finished;
            }
            state.pending = finishedPending(task);
            return;
        }

        if (newFinish && task.run_status !== 'running') {
            state.finished_at = finished;
            var pending = finishedPending(task);
            if (pending) {
                paintBanner(row, pending.mode, pending.text);
            }
        }
    }

    function showJobsStarted(name) {
        var jobsBanner = document.getElementById('monitoring-jobs-banner');
        var jobsText = jobsBanner ? jobsBanner.querySelector('[data-monitoring-jobs-banner-text]') : null;
        var strip = jobsBanner ? jobsBanner.querySelector('.monitoring-run-banner') : null;
        if (!jobsBanner || !jobsText) {
            return;
        }
        jobsText.textContent = (name || 'Job') + ' is gestart';
        jobsBanner.hidden = false;
        if (!strip) {
            return;
        }
        strip.classList.remove('is-finished', 'is-ok', 'is-fail', 'is-running');
        void strip.offsetWidth;
        strip.classList.add('is-running');
    }

    function hideJobsBanner() {
        var jobsBanner = document.getElementById('monitoring-jobs-banner');
        if (jobsBanner) {
            jobsBanner.hidden = true;
        }
    }

    function applyJobs(data) {
        var reserved = data.jobs && Array.isArray(data.jobs.reserved) ? data.jobs.reserved : [];
        var live = {};
        var fresh = [];
        reserved.forEach(function (job) {
            if (!job || job.id == null) {
                return;
            }
            var id = String(job.id);
            var at = job.reserved_at || '';
            live[id] = job;
            if (jobsSeen[id] == null || isNewer(at, jobsSeen[id])) {
                fresh.push(job);
            }
        });
        Object.keys(live).forEach(function (id) {
            jobsSeen[id] = live[id].reserved_at || jobsSeen[id] || '';
        });
        jobsLive = live;

        if (fresh.length > 0) {
            showJobsStarted(fresh[0].name);
            if (jobsHoldTimer) {
                window.clearTimeout(jobsHoldTimer);
            }
            jobsHoldTimer = window.setTimeout(function () {
                jobsHoldTimer = null;
                if (Object.keys(jobsLive).length === 0) {
                    hideJobsBanner();
                }
            }, runHoldMs);
            return;
        }

        if (jobsHoldTimer) {
            return;
        }

        if (Object.keys(live).length === 0) {
            hideJobsBanner();
        }
    }

    function apply(data) {
        var byKey = {};
        (data.tasks || []).forEach(function (task) {
            if (task && task.key) {
                byKey[task.key] = task;
            }
        });
        document.querySelectorAll('[data-monitoring-task]').forEach(function (row) {
            var task = byKey[row.getAttribute('data-monitoring-task')];
            if (task) {
                applyTask(row, task);
            }
        });
        applyJobs(data);
    }

    function tick() {
        if (document.hidden) {
            return;
        }
        fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('status');
            }
            return response.json();
        }).then(apply).catch(function () {});
    }

    function start() {
        if (timer !== null) {
            return;
        }
        timer = window.setInterval(tick, intervalMs);
    }

    function stop() {
        if (timer === null) {
            return;
        }
        window.clearInterval(timer);
        timer = null;
    }

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            stop();
            return;
        }
        tick();
        start();
    });

    document.querySelectorAll('[data-monitoring-task]').forEach(function (row) {
        var key = row.getAttribute('data-monitoring-task');
        if (!key) {
            return;
        }
        var state = runState(key);
        state.started_at = row.getAttribute('data-run-started') || '';
        state.finished_at = row.getAttribute('data-run-finished') || '';
    });

    var jobsBanner = document.getElementById('monitoring-jobs-banner');
    if (jobsBanner) {
        var seeded = [];
        try {
            seeded = JSON.parse(jobsBanner.getAttribute('data-reserved') || '[]');
        } catch (e) {
            seeded = [];
        }
        if (Array.isArray(seeded)) {
            seeded.forEach(function (job) {
                if (job && job.id != null) {
                    jobsSeen[String(job.id)] = job.reserved_at || '';
                }
            });
        }
    }

    if (!document.hidden) {
        start();
    }
});
</script>
@endpush

@push('scripts')
<script>
function monitoringCopyWithTextarea(text) {
    var area = document.createElement('textarea');
    area.value = text;
    area.setAttribute('readonly', '');
    area.style.position = 'fixed';
    area.style.top = '0';
    area.style.left = '0';
    area.style.opacity = '0';
    document.body.appendChild(area);
    area.focus();
    area.select();
    var copied = false;
    try {
        copied = document.execCommand('copy');
    } catch (e) {
        copied = false;
    }
    document.body.removeChild(area);
    return copied;
}

function monitoringShowCopied(button) {
    var icon = button.querySelector('i');
    if (icon) {
        icon.classList.remove('ki-copy');
        icon.classList.add('ki-check');
    }
    button.setAttribute('aria-label', 'Gekopieerd');
    window.clearTimeout(button._copyResetTimer);
    button._copyResetTimer = window.setTimeout(function () {
        if (icon) {
            icon.classList.remove('ki-check');
            icon.classList.add('ki-copy');
        }
        button.setAttribute('aria-label', 'Kopiëren');
    }, 1500);
}

document.addEventListener('click', function (event) {
    var button = event.target && event.target.closest ? event.target.closest('#monitoring-section-fouten .monitoring-error-copy') : null;
    if (!button) {
        return;
    }
    event.preventDefault();
    var text = button.getAttribute('data-copy-text') || '';
    if (!text) {
        return;
    }
    if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
        navigator.clipboard.writeText(text).then(function () {
            monitoringShowCopied(button);
        }).catch(function () {
            if (monitoringCopyWithTextarea(text)) {
                monitoringShowCopied(button);
            }
        });
        return;
    }
    if (monitoringCopyWithTextarea(text)) {
        monitoringShowCopied(button);
    }
});
</script>
@endpush
