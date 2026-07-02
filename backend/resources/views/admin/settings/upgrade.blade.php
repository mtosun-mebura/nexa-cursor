@extends('admin.layouts.app')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex items-center flex-wrap justify-between gap-3 mb-6 mt-5">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-mono">Upgrade</h1>
            <p class="text-sm text-secondary-foreground mt-1">Platformversie en software-stack bijwerken met live voortgang en tests.</p>
        </div>
        <span class="kt-badge kt-badge-primary text-base px-3 py-1.5">Huidige release: {{ $releaseVersion }}</span>
    </div>

    <div class="flex flex-col gap-5 mb-5">
        <div class="kt-card min-w-0">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Geïnstalleerde stack</h3>
            </div>
            <div class="kt-card-body p-5 lg:p-6 min-w-0">
                <div class="kt-scrollable-x-auto admin-table-scroll-wrap">
                    <table class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full">
                        <thead>
                            <tr>
                                <th data-label="Component">Component</th>
                                <th data-label="Versie">Versie</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stack as $item)
                                <tr>
                                    <td data-label="Component">{{ $item['label'] }}</td>
                                    <td data-label="Versie" class="font-mono text-xs sm:text-sm break-all">{{ $item['value'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="kt-card min-w-0">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Upgrade uitvoeren</h3>
            </div>
            <div class="kt-card-body space-y-4 p-5 lg:p-6">
                <p class="text-sm text-secondary-foreground">
                    Met één klik worden Composer- en NPM-dependencies bijgewerkt, migraties gedraaid en unit tests uitgevoerd.
                    Bij succes wordt de Nexa-release automatisch verhoogd.
                </p>
                @unless($webUpgradeEnabled)
                    <div class="kt-alert kt-alert-warning">
                        <div class="kt-alert-content">Web-upgrades zijn uitgeschakeld via <code>NEXA_WEB_UPGRADE_ENABLED</code>.</div>
                    </div>
                @endunless
                <label class="flex items-start gap-2 text-sm">
                    <input type="checkbox" id="upgrade-confirm" class="mt-0.5" @disabled(!$webUpgradeEnabled)>
                    <span>Ik begrijp dat dependencies worden bijgewerkt en tests worden uitgevoerd.</span>
                </label>
                <button type="button" id="btn-run-upgrade" class="kt-btn kt-btn-primary" @disabled(!$webUpgradeEnabled)>
                    <i class="ki-filled ki-arrow-up me-1"></i>
                    Upgrade naar nieuwste versies
                </button>
                <div id="upgrade-progress" class="hidden"></div>
                <div id="upgrade-result" class="hidden rounded-md border border-border bg-muted/20 p-4 text-sm"></div>
            </div>
        </div>
    </div>

    <div class="kt-card min-w-0">
        <div class="kt-card-header">
            <h3 class="kt-card-title">Upgradegeschiedenis</h3>
        </div>
        <div class="kt-card-body p-5 lg:p-6 min-w-0">
            <div class="kt-scrollable-x-auto admin-table-scroll-wrap">
                <table class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full" id="upgrade-history-table">
                    <thead>
                        <tr>
                            <th data-label="Datum">Datum</th>
                            <th data-label="Van">Van</th>
                            <th data-label="Naar">Naar</th>
                            <th data-label="Status">Status</th>
                            <th data-label="Door">Door</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($upgradeHistory as $log)
                            <tr>
                                <td data-label="Datum">{{ $log->started_at?->timezone(config('app.timezone'))->format('d-m-Y H:i') }}</td>
                                <td data-label="Van" class="font-mono">{{ $log->from_release }}</td>
                                <td data-label="Naar" class="font-mono">{{ $log->to_release ?? '—' }}</td>
                                <td data-label="Status">
                                    @if($log->status === 'success')
                                        <span class="kt-badge kt-badge-success kt-badge-sm">Geslaagd</span>
                                    @elseif($log->status === 'failed')
                                        <span class="kt-badge kt-badge-danger kt-badge-sm" title="{{ $log->error_message }}">Mislukt</span>
                                    @else
                                        <span class="kt-badge kt-badge-warning kt-badge-sm">Bezig</span>
                                    @endif
                                </td>
                                <td data-label="Door">{{ $log->triggeredBy?->first_name ?? $log->triggeredBy?->email ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-secondary-foreground py-6">Nog geen upgrades uitgevoerd.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .upgrade-progress {
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        background: color-mix(in srgb, var(--muted) 25%, transparent);
        padding: 1rem;
    }
    .upgrade-progress-list {
        list-style: none;
        margin: 0.75rem 0 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
        max-height: 18rem;
        overflow-y: auto;
    }
    .upgrade-progress-item {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        font-size: 0.8125rem;
    }
    .upgrade-progress-note {
        font-size: 0.6875rem;
        color: var(--muted-foreground);
        font-family: ui-monospace, monospace;
        word-break: break-word;
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var btn = document.getElementById('btn-run-upgrade');
    var confirmEl = document.getElementById('upgrade-confirm');
    var progressEl = document.getElementById('upgrade-progress');
    var resultEl = document.getElementById('upgrade-result');
    if (!btn || !progressEl) return;

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function initProgressUi() {
        progressEl.classList.remove('hidden');
        progressEl.innerHTML =
            '<div class="upgrade-progress">' +
            '<p class="font-medium flex items-center gap-2 mb-0">' +
            '<i class="ki-filled ki-arrows-circle animate-spin" aria-hidden="true"></i>' +
            '<span>Upgrade bezig…</span></p>' +
            '<ul class="upgrade-progress-list" id="upgrade-progress-list" aria-live="polite"></ul>' +
            '</div>';
        return document.getElementById('upgrade-progress-list');
    }

    function appendStep(list, label, status) {
        if (!list) return;
        var li = document.createElement('li');
        li.className = 'upgrade-progress-item';
        var icon = status === 'failed'
            ? 'ki-cross-circle text-destructive'
            : (status === 'skipped' ? 'ki-information-2 text-muted-foreground' : 'ki-check-circle text-emerald-600');
        li.innerHTML = '<i class="ki-filled ' + icon + ' shrink-0 mt-0.5" aria-hidden="true"></i><span>' + label + '</span>';
        list.appendChild(li);
        li.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }

    function appendNote(list, note) {
        if (!list || !note) return;
        var li = document.createElement('li');
        li.className = 'upgrade-progress-note';
        li.textContent = note;
        list.appendChild(li);
    }

    function showResult(success, message) {
        if (!resultEl) return;
        resultEl.classList.remove('hidden');
        resultEl.className = 'rounded-md border p-4 text-sm ' + (success
            ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-800 dark:text-emerald-200'
            : 'border-destructive/40 bg-destructive/10 text-destructive');
        resultEl.textContent = message;
    }

    btn.addEventListener('click', function () {
        if (!confirmEl || !confirmEl.checked) {
            alert('Vink de bevestiging aan om de upgrade te starten.');
            return;
        }

        btn.disabled = true;
        if (resultEl) {
            resultEl.classList.add('hidden');
            resultEl.textContent = '';
        }

        var list = initProgressUi();

        fetch(@json(route('admin.settings.upgrade.run')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
                'X-System-Upgrade-Stream': '1',
            },
            body: JSON.stringify({ confirm_upgrade: true }),
        }).then(function (response) {
            if (!response.ok || !response.body) {
                throw new Error('Upgrade kon niet worden gestart.');
            }

            var reader = response.body.getReader();
            var decoder = new TextDecoder();
            var buffer = '';

            function pump() {
                return reader.read().then(function (chunk) {
                    if (chunk.done) return;
                    buffer += decoder.decode(chunk.value, { stream: true });
                    var lines = buffer.split('\n');
                    buffer = lines.pop() || '';
                    lines.forEach(function (line) {
                        line = line.trim();
                        if (!line) return;
                        try {
                            var event = JSON.parse(line);
                            if (event.type === 'step') {
                                appendStep(list, event.label || 'Stap', event.status || 'done');
                            } else if (event.type === 'note') {
                                appendNote(list, event.note);
                            } else if (event.type === 'complete') {
                                showResult(!!event.success, event.message || '');
                                if (event.success) {
                                    setTimeout(function () { window.location.reload(); }, 1200);
                                }
                            }
                        } catch (e) { /* ignore partial json */ }
                    });
                    return pump();
                });
            }

            return pump();
        }).catch(function (err) {
            showResult(false, err.message || 'Upgrade mislukt.');
        }).finally(function () {
            btn.disabled = false;
        });
    });
})();
</script>
@endpush
