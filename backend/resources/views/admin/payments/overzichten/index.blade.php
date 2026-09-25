@extends('admin.layouts.app')

@section('title', 'Overzichten')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <div class="min-w-0 flex-1">
            <h1 class="text-xl font-medium leading-none text-mono">Overzichten</h1>
            <p class="text-sm text-muted-foreground mt-2 mb-0 leading-relaxed">
                Download facturen, inkomsten of belastingoverzichten voor een maand, kwartaal, halfjaar of jaar.
                @if($company)
                    Geldt voor <strong>{{ $company->name }}</strong>.
                @endif
            </p>
        </div>
    </div>

    @if(! $company)
        <div class="kt-alert kt-alert-warning mb-5">
            Selecteer eerst een tenant in de zijbalk om overzichten te downloaden.
        </div>
    @endif

    <form id="financial-overview-form" method="POST" action="{{ route('admin.payments.overzichten.download') }}" class="grid gap-5">
        @csrf

        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header px-5 py-5">
                <h3 class="kt-card-title mb-0">Type overzicht</h3>
            </div>
            <div class="kt-card-content p-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($reportTypes as $key => $meta)
                        <label class="flex items-start gap-3 rounded-lg border border-border p-4 cursor-pointer has-[:checked]:border-primary">
                            <input type="radio" class="kt-radio mt-0.5" name="report_type" value="{{ $key }}"
                                   {{ $selectedReport === $key ? 'checked' : '' }}>
                            <span>
                                <span class="block text-sm font-medium text-foreground">{{ $meta['label'] }}</span>
                                <span class="block text-xs text-muted-foreground mt-1">{{ $meta['description'] }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header px-5 py-5">
                <h3 class="kt-card-title mb-0">Periode</h3>
            </div>
            <div class="kt-card-content p-5 min-w-0">
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                    <tr>
                        <td class="min-w-40 text-secondary-foreground font-normal">Indeling</td>
                        <td>
                            <select name="period_type" id="overview-period-type" class="kt-select w-full max-w-md">
                                @foreach($periodTypes as $value => $label)
                                    <option value="{{ $value }}" {{ $selectedPeriodType === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr class="overview-row-year">
                        <td class="text-secondary-foreground font-normal">Jaar</td>
                        <td>
                            <select name="year" id="overview-year" class="kt-select w-full max-w-xs">
                                @foreach($years as $year)
                                    <option value="{{ $year }}" {{ (int) $selectedYear === (int) $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr class="overview-row-month">
                        <td class="text-secondary-foreground font-normal">Maand</td>
                        <td>
                            <select name="month" id="overview-month" class="kt-select w-full max-w-xs">
                                @foreach($months as $num => $label)
                                    <option value="{{ $num }}" {{ (int) $selectedMonth === (int) $num ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr class="overview-row-quarter">
                        <td class="text-secondary-foreground font-normal">Kwartaal</td>
                        <td>
                            <select name="quarter" id="overview-quarter" class="kt-select w-full max-w-md">
                                <option value="1" {{ (int) $selectedQuarter === 1 ? 'selected' : '' }}>1e kwartaal (januari–maart)</option>
                                <option value="2" {{ (int) $selectedQuarter === 2 ? 'selected' : '' }}>2e kwartaal (april–juni)</option>
                                <option value="3" {{ (int) $selectedQuarter === 3 ? 'selected' : '' }}>3e kwartaal (juli–september)</option>
                                <option value="4" {{ (int) $selectedQuarter === 4 ? 'selected' : '' }}>4e kwartaal (oktober–december)</option>
                            </select>
                        </td>
                    </tr>
                    <tr class="overview-row-half">
                        <td class="text-secondary-foreground font-normal">Halfjaar</td>
                        <td>
                            <select name="half" id="overview-half" class="kt-select w-full max-w-md">
                                <option value="1" {{ (int) $selectedHalf === 1 ? 'selected' : '' }}>1e helft (januari–juni)</option>
                                <option value="2" {{ (int) $selectedHalf === 2 ? 'selected' : '' }}>2e helft (juli–december)</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal align-top pt-4">Van</td>
                        <td class="pt-4">
                            @include('admin.partials.date-picker-input', [
                                'name' => 'start_date',
                                'id' => 'overview-start-date',
                                'value' => $startDate,
                                'wrapperClass' => 'w-full max-w-xs',
                                'required' => true,
                            ])
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal align-top pt-4">Tot</td>
                        <td class="pt-4">
                            @include('admin.partials.date-picker-input', [
                                'name' => 'end_date',
                                'id' => 'overview-end-date',
                                'value' => $endDate,
                                'wrapperClass' => 'w-full max-w-xs',
                                'required' => true,
                            ])
                            <p class="text-xs text-muted-foreground mt-2 mb-0 max-w-xl">
                                Bij maand, kwartaal, halfjaar en jaar worden deze datums automatisch gezet. Bij een vrije periode kies je ze zelf.
                            </p>
                        </td>
                    </tr>
                </table>

                @if($company)
                    <p id="overview-period-summary" class="text-sm text-secondary-foreground mt-4 mb-0"
                       data-overview-preview-url="{{ route('admin.payments.overzichten.preview') }}">
                        @if($period)
                            Geselecteerde periode: <strong>{{ $period->label }}</strong>
                            ({{ $period->start->format('d-m-Y') }} t/m {{ $period->end->format('d-m-Y') }}).
                            @if(!empty($counts))
                                Facturen: {{ $counts['invoices'] ?? 0 }} ·
                                Inkomstenposten: {{ $counts['income'] ?? 0 }}
                                (€ {{ number_format((float) ($counts['income_amount'] ?? 0), 2, ',', '.') }}) ·
                                Factuurregister: {{ $counts['audit'] ?? 0 }}.
                            @endif
                        @endif
                    </p>
                @endif
            </div>
        </div>

        <div class="admin-form-actions flex flex-wrap items-center justify-end gap-2.5">
            <button type="submit" class="kt-btn kt-btn-outline" formmethod="GET" formaction="{{ route('admin.payments.overzichten') }}" {{ $company ? '' : 'disabled' }}>
                Periode tonen
            </button>
            <button type="submit" class="kt-btn kt-btn-primary" {{ $company ? '' : 'disabled' }}>
                Downloaden
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var typeEl = document.getElementById('overview-period-type');
    var yearEl = document.getElementById('overview-year');
    var monthEl = document.getElementById('overview-month');
    var quarterEl = document.getElementById('overview-quarter');
    var halfEl = document.getElementById('overview-half');
    var startEl = document.getElementById('overview-start-date');
    var endEl = document.getElementById('overview-end-date');
    var summaryEl = document.getElementById('overview-period-summary');
    if (!typeEl || !startEl || !endEl) return;

    var previewUrl = summaryEl ? summaryEl.getAttribute('data-overview-preview-url') : '';
    var previewTimer = null;
    var previewAbort = null;

    function pad(n) { return String(n).padStart(2, '0'); }
    function formatNl(d) { return pad(d.getDate()) + '-' + pad(d.getMonth() + 1) + '-' + d.getFullYear(); }
    function lastDay(year, monthIndex) { return new Date(year, monthIndex + 1, 0); }

    function syncRows() {
        var type = typeEl.value;
        document.querySelectorAll('.overview-row-year').forEach(function (el) { el.style.display = type === 'custom' ? 'none' : ''; });
        document.querySelectorAll('.overview-row-month').forEach(function (el) { el.style.display = type === 'month' ? '' : 'none'; });
        document.querySelectorAll('.overview-row-quarter').forEach(function (el) { el.style.display = type === 'quarter' ? '' : 'none'; });
        document.querySelectorAll('.overview-row-half').forEach(function (el) { el.style.display = type === 'half_year' ? '' : 'none'; });
    }

    function applyDates() {
        var type = typeEl.value;
        if (type === 'custom') return;
        var year = parseInt(yearEl.value, 10);
        var start, end;
        if (type === 'month') {
            var month = parseInt(monthEl.value, 10) - 1;
            start = new Date(year, month, 1);
            end = lastDay(year, month);
        } else if (type === 'quarter') {
            var q = parseInt(quarterEl.value, 10);
            var qStart = (q - 1) * 3;
            start = new Date(year, qStart, 1);
            end = lastDay(year, qStart + 2);
        } else if (type === 'half_year') {
            var half = parseInt(halfEl.value, 10);
            start = new Date(year, half === 1 ? 0 : 6, 1);
            end = lastDay(year, half === 1 ? 5 : 11);
        } else {
            start = new Date(year, 0, 1);
            end = new Date(year, 11, 31);
        }
        startEl.value = formatNl(start);
        endEl.value = formatNl(end);
    }

    function renderSummary(data) {
        if (!summaryEl || !data) return;
        summaryEl.replaceChildren();
        summaryEl.append('Geselecteerde periode: ');
        var strong = document.createElement('strong');
        strong.textContent = data.label || '';
        summaryEl.append(strong);
        summaryEl.append(
            ' (' + (data.start || '') + ' t/m ' + (data.end || '') + '). ' +
            'Facturen: ' + (data.invoices ?? 0) + ' · ' +
            'Inkomstenposten: ' + (data.income ?? 0) +
            ' (€ ' + (data.income_amount || '0,00') + ') · ' +
            'Factuurregister: ' + (data.audit ?? 0) + '.'
        );
    }

    function refreshCounts() {
        if (!previewUrl || !summaryEl) return;
        if (previewTimer) clearTimeout(previewTimer);
        previewTimer = setTimeout(function () {
            if (previewAbort) previewAbort.abort();
            previewAbort = new AbortController();
            summaryEl.setAttribute('aria-busy', 'true');
            var params = new URLSearchParams({
                period_type: typeEl.value,
                year: yearEl ? yearEl.value : '',
                month: monthEl ? monthEl.value : '',
                quarter: quarterEl ? quarterEl.value : '',
                half: halfEl ? halfEl.value : '',
                start_date: startEl.value,
                end_date: endEl.value
            });
            fetch(previewUrl + '?' + params.toString(), {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: previewAbort.signal
            }).then(function (response) {
                if (!response.ok) throw new Error('preview');
                return response.json();
            }).then(function (data) {
                renderSummary(data);
            }).catch(function (err) {
                if (err && err.name === 'AbortError') return;
            }).finally(function () {
                summaryEl.removeAttribute('aria-busy');
            });
        }, 180);
    }

    [typeEl, yearEl, monthEl, quarterEl, halfEl].forEach(function (el) {
        if (!el) return;
        el.addEventListener('change', function () {
            syncRows();
            applyDates();
            refreshCounts();
        });
    });
    [startEl, endEl].forEach(function (el) {
        el.addEventListener('change', refreshCounts);
        el.addEventListener('input', refreshCounts);
    });
    syncRows();
});
</script>
@endpush
