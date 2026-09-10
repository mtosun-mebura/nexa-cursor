@extends('admin.layouts.app')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex items-center flex-wrap justify-between gap-3 mb-6 mt-5">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-mono">Upgrade</h1>
            <p class="text-sm text-secondary-foreground mt-1">
                Platformbrede Nexa-versie en software-stack bijwerken met live voortgang en tests.
                Geldt voor de volledige installatie, niet per tenant.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="kt-badge kt-badge-secondary kt-badge-sm">Platform-breed</span>
            <span class="kt-badge kt-badge-primary text-base px-3 py-1.5">Huidige release: {{ $releaseVersion }}</span>
        </div>
    </div>

    <div class="flex flex-col gap-5 mb-5">
        <div class="kt-card min-w-0">
            <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
                <h3 class="kt-card-title mb-0">Geïnstalleerde stack</h3>
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
            <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
                <h3 class="kt-card-title mb-0">Laravel bijwerken</h3>
            </div>
            <div class="kt-card-body space-y-4 px-5 pt-5 pb-3 lg:px-6 lg:pt-6">
                <p class="text-sm text-secondary-foreground mb-0" id="laravel-upgrade-status-text">Beschikbare Laravel-updates ophalen…</p>
                <div class="flex flex-wrap gap-2 pt-5 pb-0">
                    <button type="button" id="btn-laravel-minor" class="kt-btn kt-btn-primary hidden" @disabled(!$webUpgradeEnabled) disabled>
                        <i class="ki-filled ki-arrow-up me-1"></i>
                        Minor-update
                    </button>
                    <button type="button" id="btn-laravel-major" class="kt-btn kt-btn-success hidden" @disabled(!$webUpgradeEnabled) disabled>
                        <i class="ki-filled ki-rocket me-1"></i>
                        Major-update
                    </button>
                </div>
                <div id="laravel-upgrade-progress" class="hidden"></div>
                <div id="laravel-upgrade-result" class="hidden rounded-md border border-border bg-muted/20 p-4 text-sm"></div>
            </div>
        </div>

        <div class="kt-card min-w-0">
            <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
                <h3 class="kt-card-title mb-0">PHP in Docker bijwerken</h3>
            </div>
            <div class="kt-card-body space-y-4 px-5 pt-5 pb-3 lg:px-6 lg:pt-6">
                <p class="text-sm text-secondary-foreground mb-0">
                    Werkt de PHP-basisimage in de Dockerfiles bij naar de nieuwste officiële <code>php:X.Y-cli</code>,
                    tuigt daarna de hele Docker-stack opnieuw op (build waar de image is veranderd) en draait de testdoorloop.
                </p>
                <p class="text-sm text-secondary-foreground mb-0" id="php-upgrade-status-text">Status ophalen…</p>
                <div class="flex flex-wrap gap-2 pt-5 pb-0">
                    <button type="button" id="btn-php-docker-upgrade" class="kt-btn kt-btn-outline" @disabled(!$webUpgradeEnabled) disabled>
                        <i class="ki-filled ki-docker me-1"></i>
                        PHP in Docker bijwerken
                    </button>
                </div>
                <div id="php-upgrade-progress" class="hidden"></div>
                <div id="php-upgrade-result" class="hidden rounded-md border border-border bg-muted/20 p-4 text-sm"></div>
            </div>
        </div>

        <div class="kt-card min-w-0">
            <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
                <h3 class="kt-card-title mb-0">Overige packages bijwerken</h3>
            </div>
            <div class="kt-card-body space-y-4 p-5 lg:p-6">
                <p class="text-sm text-secondary-foreground">
                    Bekijk eerst welke overige packages bijgewerkt kunnen worden (Composer, NPM, migraties).
                    Laravel en PHP hebben eigen knoppen hierboven. Bij succes wordt de platform-release verhoogd.
                </p>
                @unless($webUpgradeEnabled)
                    <div class="kt-alert kt-alert-warning">
                        <div class="kt-alert-content">Web-upgrades zijn uitgeschakeld via <code>NEXA_WEB_UPGRADE_ENABLED</code>.</div>
                    </div>
                @endunless

                <details class="upgrade-docker-note rounded-md border border-border bg-background/60 p-3 lg:p-4 text-sm">
                    <summary class="upgrade-docker-note-summary flex cursor-pointer items-center gap-2 font-medium text-mono select-none">
                        <i class="ki-filled ki-code text-base text-secondary-foreground shrink-0" aria-hidden="true"></i>
                        <span class="flex-1 min-w-0">Laravel: minor vs major</span>
                        <i class="ki-filled ki-down upgrade-docker-chevron text-sm text-secondary-foreground shrink-0" aria-hidden="true"></i>
                    </summary>
                    <div class="mt-3 space-y-4 text-secondary-foreground">
                        <p class="mb-0">
                            Gebruik de knoppen <strong>Minor-update</strong> en <strong>Major-update</strong> hierboven.
                            Beide draaien daarna automatisch migraties, tests en het opnieuw optuigen van de Docker-stack.
                        </p>
                        <div>
                            <p class="mb-1 font-semibold text-mono">Minor (binnen de huidige major)</p>
                            <p class="mb-0">
                                Voert <code>composer update laravel/framework --with-all-dependencies</code> uit binnen de
                                huidige constraint. Geen sprong naar een nieuwe major.
                            </p>
                        </div>
                        <div>
                            <p class="mb-1 font-semibold text-mono">Major (nieuwe Laravel-versie)</p>
                            <p class="mb-0">
                                Zet de Composer-constraint op de volgende major, installeert die versie, en controleert
                                of PHP hoog genoeg is. Bij falende installatie of tests gaan de Composer-bestanden terug.
                                Controleer bij een major alsnog de officiële upgrade-guide voor breaking changes.
                            </p>
                        </div>
                    </div>
                </details>

                <details class="upgrade-docker-note rounded-md border border-border bg-background/60 p-3 lg:p-4 text-sm">
                    <summary class="upgrade-docker-note-summary flex cursor-pointer items-center gap-2 font-medium text-mono select-none">
                        <i class="ki-filled ki-docker text-base text-secondary-foreground shrink-0" aria-hidden="true"></i>
                        <span class="flex-1 min-w-0">PHP &amp; PostgreSQL bijwerken (via Docker)</span>
                        <i class="ki-filled ki-down upgrade-docker-chevron text-sm text-secondary-foreground shrink-0" aria-hidden="true"></i>
                    </summary>
                    <div class="mt-3 space-y-4 text-secondary-foreground">
                        <p class="mb-0">
                            PHP kun je nu automatisch bijwerken met de knop <strong>PHP in Docker bijwerken</strong> hierboven.
                            Die zet de <code>FROM php:…-cli</code> regel in de Dockerfiles, tuigt de Docker-stack opnieuw op
                            (build waar nodig) en draait daarna tests. PostgreSQL blijft handmatig: een major-upgrade vereist een data-migratie.
                        </p>

                        <div>
                            <p class="mb-1 font-semibold text-mono">1. PHP (automatisch via de knop)</p>
                            <p class="mb-0">
                                De knop haalt de nieuwste stabiele PHP 8-lijn op, schrijft <code>backend/Dockerfile</code>
                                en <code>backend/Dockerfile.prod</code>, voert <code>docker compose up -d --build</code>
                                uit en rondt daarna <code>php artisan test</code> af. Een eenmalige
                                <code>docker compose up -d</code> is nodig zodat de Docker-socket in de container hangt.
                            </p>
                        </div>

                        <div>
                            <p class="mb-1 font-semibold text-mono">2. PostgreSQL upgraden (bijv. pg16 → pg17)</p>
                            <p class="mb-1">
                                Een major-upgrade vereist een <strong>data-migratie</strong> — het volume is versiegebonden, dus een
                                nieuwe image start niet zomaar op oude data. Maak eerst een backup:
                            </p>
                            <pre class="upgrade-docker-code">docker compose exec db pg_dumpall -U nexa > backup-$(date +%F).sql</pre>
                            <p class="mb-1">Pas de image aan in <code>docker-compose.postgres.yml</code>:</p>
                            <pre class="upgrade-docker-code">- image: pgvector/pgvector:pg16
+ image: pgvector/pgvector:pg17</pre>
                            <p class="mb-1">Verwijder het oude datavolume en herstel de backup in de nieuwe versie:</p>
                            <pre class="upgrade-docker-code">docker compose down
docker volume rm nexa_postgres_data
docker compose up -d db
cat backup-YYYY-MM-DD.sql | docker compose exec -T db psql -U nexa -d nexa</pre>
                            <p class="mb-0 text-destructive">
                                Let op: doe dit in een onderhoudsvenster en verifieer altijd eerst dat de backup geldig is.
                            </p>
                        </div>
                    </div>
                </details>

                <button type="button" id="btn-run-upgrade" class="kt-btn kt-btn-primary" @disabled(!$webUpgradeEnabled)>
                    <i class="ki-filled ki-arrow-up me-1"></i>
                    Upgrade naar nieuwste versies
                </button>

                <div id="upgrade-preview" class="hidden rounded-md border border-border bg-muted/10 p-4 lg:p-5 space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="font-medium mb-1">Beschikbare updates</p>
                            <p class="text-sm text-secondary-foreground mb-0">
                                Release na succesvolle upgrade:
                                <span class="font-mono" id="upgrade-release-current">{{ $releaseVersion }}</span>
                                →
                                <span class="font-mono" id="upgrade-release-target">—</span>
                            </p>
                        </div>
                        <button type="button" id="btn-preview-cancel" class="kt-btn kt-btn-light kt-btn-sm">Annuleren</button>
                    </div>

                    <div id="upgrade-preview-loading" class="hidden text-sm text-secondary-foreground flex items-center gap-2">
                        <i class="ki-filled ki-arrows-circle animate-spin" aria-hidden="true"></i>
                        <span>Updates controleren…</span>
                    </div>

                    <div id="upgrade-preview-error" class="hidden kt-alert kt-alert-danger">
                        <div class="kt-alert-content" id="upgrade-preview-error-text"></div>
                    </div>

                    <div id="upgrade-preview-content" class="hidden space-y-3">
                        <div class="kt-scrollable-x-auto admin-table-scroll-wrap">
                            <table class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full" id="upgrade-selection-table">
                                <thead>
                                    <tr>
                                        <th class="upgrade-selection-col" id="upgrade-selection-header">
                                            <div class="upgrade-selection-cell">
                                                <span id="upgrade-select-all-wrap" class="upgrade-select-all-wrap">
                                                    <label class="upgrade-selection-checkbox" title="Alles selecteren">
                                                        <input type="checkbox" id="upgrade-select-all" class="kt-checkbox">
                                                    </label>
                                                </span>
                                            </div>
                                        </th>
                                        <th data-label="Component">Component</th>
                                        <th data-label="Huidig">Huidig</th>
                                        <th data-label="Nieuw">Nieuw</th>
                                    </tr>
                                </thead>
                                <tbody id="upgrade-selection-body"></tbody>
                            </table>
                        </div>

                        <label class="upgrade-confirm-label flex items-start gap-2.5 text-sm">
                            <input type="checkbox" id="upgrade-confirm" class="kt-checkbox shrink-0">
                            <span>Ik begrijp dat de geselecteerde onderdelen worden bijgewerkt.</span>
                        </label>

                        <div class="flex flex-wrap gap-2">
                            <button type="button" id="btn-start-upgrade" class="kt-btn kt-btn-primary" disabled>
                                <i class="ki-filled ki-rocket me-1"></i>
                                Upgrade starten
                            </button>
                        </div>
                    </div>
                </div>

                <div id="upgrade-progress" class="hidden"></div>
                <div id="upgrade-result" class="hidden rounded-md border border-border bg-muted/20 p-4 text-sm"></div>
            </div>
        </div>
    </div>

    <div class="kt-card min-w-0">
        <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
            <h3 class="kt-card-title mb-0">Upgradegeschiedenis</h3>
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
        padding: 0 0.35rem 0 0;
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
        max-height: 18rem;
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: color-mix(in srgb, var(--foreground) 55%, transparent) transparent;
    }
    .upgrade-progress-list::-webkit-scrollbar {
        width: 8px;
    }
    .upgrade-progress-list::-webkit-scrollbar-track,
    .upgrade-progress-list::-webkit-scrollbar-track-piece,
    .upgrade-progress-list::-webkit-scrollbar-corner {
        background: transparent;
    }
    .upgrade-progress-list::-webkit-scrollbar-thumb {
        background-color: color-mix(in srgb, var(--foreground) 55%, transparent);
        border-radius: 9999px;
    }
    .upgrade-progress-list::-webkit-scrollbar-thumb:hover {
        background-color: color-mix(in srgb, var(--foreground) 75%, transparent);
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
    .upgrade-selection-muted {
        color: var(--muted-foreground);
    }
    .upgrade-selection-target {
        font-family: ui-monospace, monospace;
        font-size: 0.8125rem;
    }

    #upgrade-selection-table thead th,
    #upgrade-selection-table tbody td {
        text-align: center;
        vertical-align: middle;
    }

    #upgrade-selection-table tbody td[colspan] {
        text-align: center;
    }

    #upgrade-selection-table .upgrade-selection-col {
        width: 9.75rem;
        min-width: 9.75rem;
        max-width: 9.75rem;
        padding-left: 0.625rem;
        padding-right: 0.625rem;
        text-align: center;
        vertical-align: middle;
    }

    #upgrade-selection-table .upgrade-selection-cell {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        min-height: 2rem;
    }

    #upgrade-selection-table .upgrade-selection-checkbox {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin: 0;
        cursor: pointer;
        line-height: 0;
    }

    #upgrade-selection-table .kt-checkbox,
    .upgrade-confirm-label .kt-checkbox {
        width: 1.125rem;
        height: 1.125rem;
        min-width: 1.125rem;
        min-height: 1.125rem;
        margin: 0;
        border-width: 1px;
        border-radius: 0.3rem;
        border-color: color-mix(in srgb, var(--foreground) 28%, var(--border));
        background-color: var(--background);
        cursor: pointer;
        transition: border-color 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease;
        appearance: none;
        -webkit-appearance: none;
        padding: 0;
    }

    #upgrade-selection-table .kt-checkbox:hover,
    .upgrade-confirm-label .kt-checkbox:hover {
        border-color: color-mix(in srgb, var(--primary) 55%, var(--border));
        background-color: color-mix(in srgb, var(--primary) 6%, var(--background));
    }

    #upgrade-selection-table .kt-checkbox:checked,
    .upgrade-confirm-label .kt-checkbox:checked {
        border-color: var(--primary);
        background-color: var(--primary);
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12' fill='none'%3E%3Cpath d='M10.2 3.3a.6.6 0 0 1 0 .85L5.35 9a.6.6 0 0 1-.85 0L1.8 5.3a.6.6 0 1 1 .85-.85l2.2 2.2 4.5-4.5a.6.6 0 0 1 .85 0Z' fill='white'/%3E%3C/svg%3E");
        background-position: center;
        background-repeat: no-repeat;
        background-size: 0.7rem 0.7rem;
    }

    #upgrade-selection-table .kt-checkbox:focus-visible,
    .upgrade-confirm-label .kt-checkbox:focus-visible {
        outline: none;
        box-shadow: 0 0 0 2px color-mix(in srgb, var(--primary) 25%, transparent);
    }

    #upgrade-selection-table .kt-checkbox:indeterminate {
        border-color: var(--primary);
        background-color: var(--primary);
        background-image: none;
    }

    #upgrade-selection-table .kt-checkbox:indeterminate::after {
        content: '';
        display: block;
        width: 0.55rem;
        height: 0.125rem;
        margin: 0.4rem auto 0;
        border-radius: 9999px;
        background: #fff;
    }

    #upgrade-selection-table .upgrade-status-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: auto;
        max-width: none;
        min-height: 1.5rem;
        padding: 0.2rem 0.5rem !important;
        font-size: 0.6875rem !important;
        line-height: 1 !important;
        font-weight: 600 !important;
        letter-spacing: 0.01em;
        white-space: nowrap !important;
        text-align: center;
        border-radius: 0.375rem !important;
    }

    .upgrade-confirm-label .kt-checkbox {
        margin-top: 0.1rem;
    }

    .upgrade-docker-note summary {
        list-style: none;
    }
    .upgrade-docker-note summary::-webkit-details-marker {
        display: none;
    }
    .upgrade-docker-note-summary:hover .upgrade-docker-chevron {
        color: var(--primary);
    }
    .upgrade-docker-note .upgrade-docker-chevron {
        transition: transform 0.2s ease, color 0.15s ease;
    }
    .upgrade-docker-note[open] .upgrade-docker-chevron {
        transform: rotate(180deg);
    }
    .upgrade-docker-code {
        margin: 0.35rem 0 0.5rem;
        padding: 0.6rem 0.75rem;
        border-radius: 0.375rem;
        background: color-mix(in srgb, var(--muted) 40%, transparent);
        border: 1px solid var(--border);
        font-family: ui-monospace, monospace;
        font-size: 0.75rem;
        line-height: 1.5;
        white-space: pre-wrap;
        word-break: break-word;
        color: var(--foreground);
    }

    .kt-btn-success {
        background-color: #10b981;
        border-color: #10b981;
        color: #fff;
    }
    .kt-btn-success:hover:not(:disabled) {
        background-color: #059669;
        border-color: #059669;
        color: #fff;
    }
    .dark .kt-btn-success {
        background-color: #059669;
        border-color: #059669;
        color: #fff;
    }
    .dark .kt-btn-success:hover:not(:disabled) {
        background-color: #047857;
        border-color: #047857;
        color: #fff;
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var btnPreview = document.getElementById('btn-run-upgrade');
    var btnStart = document.getElementById('btn-start-upgrade');
    var btnCancel = document.getElementById('btn-preview-cancel');
    var confirmEl = document.getElementById('upgrade-confirm');
    var selectAllWrapEl = document.getElementById('upgrade-select-all-wrap');
    var previewEl = document.getElementById('upgrade-preview');
    var previewLoadingEl = document.getElementById('upgrade-preview-loading');
    var previewContentEl = document.getElementById('upgrade-preview-content');
    var previewErrorEl = document.getElementById('upgrade-preview-error');
    var previewErrorTextEl = document.getElementById('upgrade-preview-error-text');
    var selectionBodyEl = document.getElementById('upgrade-selection-body');
    var releaseTargetEl = document.getElementById('upgrade-release-target');
    var progressEl = document.getElementById('upgrade-progress');
    var resultEl = document.getElementById('upgrade-result');
    var selectAllEl = document.getElementById('upgrade-select-all');
    var previewItems = [];

    if (!btnPreview || !progressEl || !previewEl) return;

    var groupLabels = {
        composer: 'Composer',
        runtime: 'Runtime',
        npm: 'NPM / Frontend',
        build: 'Build',
        database: 'Database',
        quality: 'Kwaliteit',
    };

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function setPreviewVisible(visible) {
        previewEl.classList.toggle('hidden', !visible);
    }

    function resetPreviewState() {
        previewLoadingEl.classList.add('hidden');
        previewContentEl.classList.add('hidden');
        previewErrorEl.classList.add('hidden');
        if (previewErrorTextEl) previewErrorTextEl.textContent = '';
        if (selectionBodyEl) selectionBodyEl.innerHTML = '';
        if (confirmEl) confirmEl.checked = false;
        if (selectAllEl) selectAllEl.checked = false;
        previewItems = [];
        syncStartButton();
    }

    function selectedItemIds() {
        if (!selectionBodyEl) return [];
        return Array.prototype.slice.call(
            selectionBodyEl.querySelectorAll('input.upgrade-item-checkbox:checked')
        ).map(function (input) {
            return input.value;
        });
    }

    function syncSelectAllState() {
        if (!selectAllEl || !selectionBodyEl) return;
        var checkboxes = selectionBodyEl.querySelectorAll('input.upgrade-item-checkbox');
        var checked = selectionBodyEl.querySelectorAll('input.upgrade-item-checkbox:checked');
        selectAllEl.indeterminate = checked.length > 0 && checked.length < checkboxes.length;
        selectAllEl.checked = checkboxes.length > 0 && checked.length === checkboxes.length;
        syncStartButton();
    }

    function syncStartButton() {
        if (!btnStart) return;
        var hasSelection = selectedItemIds().length > 0;
        var confirmed = confirmEl && confirmEl.checked;
        btnStart.disabled = !hasSelection || !confirmed;
    }

    function statusBadgeClass(status) {
        if (status === 'Actueel' || status === 'Geen openstaande' || status === 'Geïnstalleerd') {
            return 'kt-badge-success';
        }
        if (status === 'Via Docker' || status === 'Eigen knop') {
            return 'kt-badge-info';
        }
        if (status === 'Niet nodig') {
            return 'kt-badge-secondary';
        }
        if (status === 'Niet beschikbaar') {
            return 'kt-badge-warning';
        }

        return 'kt-badge-secondary';
    }

    function renderPreview(data) {
        if (!selectionBodyEl || !data) return;

        previewItems = Array.isArray(data.items) ? data.items : [];
        if (releaseTargetEl && data.release) {
            releaseTargetEl.textContent = data.release.after_success || '—';
        }

        var hasSelectable = previewItems.some(function (item) {
            return item.selectable === true;
        });

        if (selectAllWrapEl) {
            selectAllWrapEl.classList.toggle('hidden', !hasSelectable);
        }
        if (selectAllEl) {
            selectAllEl.checked = false;
            selectAllEl.indeterminate = false;
        }

        var lastGroup = '';
        var html = '';

        previewItems.forEach(function (item) {
            if (item.group !== lastGroup) {
                lastGroup = item.group;
                html += '<tr class="bg-muted/20">' +
                    '<td colspan="4" class="text-xs font-semibold uppercase tracking-wide text-secondary-foreground">' +
                    escapeHtml(groupLabels[item.group] || item.group) +
                    '</td></tr>';
            }

            var selectable = item.selectable === true;
            var target = item.target || '—';
            var targetClass = selectable
                ? 'upgrade-selection-target text-emerald-700 dark:text-emerald-300'
                : 'upgrade-selection-muted';
            var firstCol = selectable
                ? '<div class="upgrade-selection-cell"><label class="upgrade-selection-checkbox">' +
                    '<input type="checkbox" class="kt-checkbox upgrade-item-checkbox" value="' + escapeHtml(item.id) + '"' +
                    (item.default_selected ? ' checked' : '') + '>' +
                    '</label></div>'
                : '<div class="upgrade-selection-cell"><span class="kt-badge kt-badge-sm upgrade-status-badge ' +
                    statusBadgeClass(item.status || '') + '" title="' + escapeHtml(item.status || '') + '">' +
                    escapeHtml(item.status || '—') + '</span></div>';

            html += '<tr>' +
                '<td data-label="Selectie" class="upgrade-selection-col">' + firstCol + '</td>' +
                '<td data-label="Component">' + escapeHtml(item.label) + '</td>' +
                '<td data-label="Huidig" class="font-mono text-xs sm:text-sm">' + escapeHtml(item.current) + '</td>' +
                '<td data-label="Nieuw" class="' + targetClass + '">' + escapeHtml(target) + '</td>' +
                '</tr>';
        });

        selectionBodyEl.innerHTML = html;
        selectionBodyEl.querySelectorAll('input.upgrade-item-checkbox').forEach(function (input) {
            input.addEventListener('change', syncSelectAllState);
        });

        syncSelectAllState();
        previewContentEl.classList.remove('hidden');
    }

    function loadPreview() {
        resetPreviewState();
        setPreviewVisible(true);
        previewLoadingEl.classList.remove('hidden');
        btnPreview.disabled = true;

        if (resultEl) {
            resultEl.classList.add('hidden');
            resultEl.textContent = '';
        }
        progressEl.classList.add('hidden');
        progressEl.innerHTML = '';

        fetch(@json(route('admin.settings.upgrade.preview')), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        }).then(function (response) {
            return response.json().then(function (payload) {
                if (!response.ok || !payload.success) {
                    throw new Error(payload.message || 'Kon updates niet ophalen.');
                }
                return payload.data;
            });
        }).then(function (data) {
            previewLoadingEl.classList.add('hidden');
            renderPreview(data);
        }).catch(function (err) {
            previewLoadingEl.classList.add('hidden');
            previewErrorEl.classList.remove('hidden');
            if (previewErrorTextEl) {
                previewErrorTextEl.textContent = err.message || 'Kon updates niet ophalen.';
            }
        }).finally(function () {
            btnPreview.disabled = false;
        });
    }

    function initProgressUi() {
        setPreviewVisible(false);
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

    function runUpgrade(selections) {
        if (!selections.length) {
            alert('Selecteer minimaal één item om te upgraden.');
            return;
        }

        if (!confirmEl || !confirmEl.checked) {
            alert('Vink de bevestiging aan om de upgrade te starten.');
            return;
        }

        btnStart.disabled = true;
        btnPreview.disabled = true;

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
            body: JSON.stringify({
                confirm_upgrade: true,
                selections: selections,
            }),
        }).then(function (response) {
            if (!response.ok || !response.body) {
                return response.json().catch(function () {
                    throw new Error('Upgrade kon niet worden gestart.');
                }).then(function (payload) {
                    throw new Error(payload.message || 'Upgrade kon niet worden gestart.');
                });
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
            btnStart.disabled = false;
            btnPreview.disabled = false;
            syncStartButton();
        });
    }

    btnPreview.addEventListener('click', loadPreview);

    if (btnCancel) {
        btnCancel.addEventListener('click', function () {
            setPreviewVisible(false);
            resetPreviewState();
        });
    }

        if (selectAllEl) {
            selectAllEl.addEventListener('change', function () {
                if (!selectionBodyEl) return;
                selectionBodyEl.querySelectorAll('input.upgrade-item-checkbox').forEach(function (input) {
                    input.checked = selectAllEl.checked;
                });
                selectAllEl.indeterminate = false;
                syncStartButton();
            });
        }

    if (confirmEl) {
        confirmEl.addEventListener('change', syncStartButton);
    }

    if (btnStart) {
        btnStart.addEventListener('click', function () {
            runUpgrade(selectedItemIds());
        });
    }
})();

(function () {
    var webUpgradeEnabled = @json((bool) $webUpgradeEnabled);
    var laravelStatusUrl = @json(route('admin.settings.upgrade.laravel-status'));
    var laravelRunUrl = @json(route('admin.settings.upgrade.laravel-run'));
    var laravelFinalizeUrl = @json(route('admin.settings.upgrade.laravel-finalize'));
    var phpStatusUrl = @json(route('admin.settings.upgrade.php-status'));
    var phpRunUrl = @json(route('admin.settings.upgrade.php-run'));
    var phpFinalizeUrl = @json(route('admin.settings.upgrade.php-finalize'));

    var laravelStatusEl = document.getElementById('laravel-upgrade-status-text');
    var phpStatusEl = document.getElementById('php-upgrade-status-text');
    var btnLaravelMinor = document.getElementById('btn-laravel-minor');
    var btnLaravelMajor = document.getElementById('btn-laravel-major');
    var btnPhp = document.getElementById('btn-php-docker-upgrade');
    var laravelProgressEl = document.getElementById('laravel-upgrade-progress');
    var laravelResultEl = document.getElementById('laravel-upgrade-result');
    var phpProgressEl = document.getElementById('php-upgrade-progress');
    var phpResultEl = document.getElementById('php-upgrade-result');
    var laravelStatus = null;
    var phpStatus = null;

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function confirmUpgrade(title, message, label) {
        if (typeof window.showAdminConfirm === 'function') {
            return window.showAdminConfirm({
                title: title,
                message: message,
                confirmLabel: label || 'Starten',
                destructive: false,
            });
        }
        return Promise.resolve(window.confirm(message));
    }

    function initProgress(progressEl, title) {
        if (!progressEl) return null;
        progressEl.classList.remove('hidden');
        progressEl.innerHTML =
            '<div class="upgrade-progress">' +
            '<p class="font-medium flex items-center gap-2 mb-0">' +
            '<i class="ki-filled ki-arrows-circle animate-spin" aria-hidden="true"></i>' +
            '<span>' + title + '</span></p>' +
            '<ul class="upgrade-progress-list" aria-live="polite"></ul>' +
            '</div>';
        return progressEl.querySelector('.upgrade-progress-list');
    }

    function appendStep(list, label, status) {
        if (!list) return;
        var li = document.createElement('li');
        li.className = 'upgrade-progress-item';
        var icon = status === 'failed'
            ? 'ki-cross-circle text-destructive'
            : (status === 'skipped' ? 'ki-information-2 text-muted-foreground' : 'ki-check-circle text-emerald-600');
        li.innerHTML = '<i class="ki-filled ' + icon + ' shrink-0 mt-0.5" aria-hidden="true"></i><span></span>';
        li.querySelector('span').textContent = label;
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

    function showPanelResult(resultEl, success, message) {
        if (!resultEl) return;
        resultEl.classList.remove('hidden');
        resultEl.className = 'rounded-md border p-4 text-sm ' + (success
            ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-800 dark:text-emerald-200'
            : 'border-destructive/40 bg-destructive/10 text-destructive');
        resultEl.textContent = message;
    }

    function setBusy(busy) {
        if (btnLaravelMinor) btnLaravelMinor.disabled = busy || !webUpgradeEnabled || !(laravelStatus && laravelStatus.can_minor);
        if (btnLaravelMajor) btnLaravelMajor.disabled = busy || !webUpgradeEnabled || !(laravelStatus && laravelStatus.can_major);
        if (btnPhp) {
            var phpOk = phpStatus && (phpStatus.can_run || phpStatus.pending_finalize);
            btnPhp.disabled = busy || !webUpgradeEnabled || !phpOk;
            btnPhp.classList.toggle('kt-btn-success', !!phpOk);
            btnPhp.classList.toggle('kt-btn-outline', !phpOk);
            btnPhp.classList.remove('kt-btn-primary');
        }
    }

    function escapeText(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function versionMark(value) {
        return '<span class="font-mono">' + escapeText(value || '—') + '</span>';
    }

    function laravelCopyHtml(data) {
        if (data.pending_finalize) {
            return escapeText(data.message || 'De Docker-stack is herstart. De Laravel-upgrade wordt nu afgerond.');
        }
        var current = versionMark(data.current);
        if (data.can_minor && data.minor_target) {
            return 'Er is een nieuwe minor-versie: Laravel ' + current +
                ' → ' + versionMark(data.minor_target) +
                '. Daarna volgen migraties, tests en het opnieuw optuigen van de Docker-stack (build indien nodig).';
        }
        if (data.can_major && data.major_target) {
            return 'Er is een nieuwe major-versie: Laravel ' + current +
                ' → ' + versionMark(data.major_target) +
                '. Daarna volgen migraties, tests en het opnieuw optuigen van de Docker-stack (build indien nodig). Bij falen gaan composer.json en composer.lock terug.';
        }
        if (data.major_target && data.major_blocked_reason) {
            return 'Laravel ' + current + ' heeft geen nieuwere minor. Major naar ' +
                versionMark(data.major_target) + ' is nog niet mogelijk: ' +
                escapeText(data.major_blocked_reason);
        }
        return 'Laravel ' + current + ' is actueel. Er is geen nieuwere minor of major.';
    }

    function applyLaravelStatus(data) {
        laravelStatus = data || null;
        if (laravelStatusEl) {
            if (data) {
                laravelStatusEl.innerHTML = laravelCopyHtml(data);
            } else {
                laravelStatusEl.textContent = 'Kon Laravel-status niet laden.';
            }
        }
        if (btnLaravelMinor) {
            var showMinor = !!(data && data.can_minor && data.minor_target);
            btnLaravelMinor.classList.toggle('hidden', !showMinor);
            btnLaravelMinor.innerHTML = showMinor
                ? '<i class="ki-filled ki-arrow-up me-1"></i>Minor-update naar ' + escapeText(data.minor_target)
                : '<i class="ki-filled ki-arrow-up me-1"></i>Minor-update';
        }
        if (btnLaravelMajor) {
            var showMajor = !!(data && data.can_major && data.major_target);
            btnLaravelMajor.classList.toggle('hidden', !showMajor);
            btnLaravelMajor.innerHTML = (data && data.major_target)
                ? '<i class="ki-filled ki-rocket me-1"></i>Major-update naar ' + escapeText(data.major_target)
                : '<i class="ki-filled ki-rocket me-1"></i>Major-update';
        }
        setBusy(false);
    }

    function applyPhpStatus(data) {
        phpStatus = data || null;
        if (phpStatusEl) {
            phpStatusEl.textContent = data && data.message
                ? ('Draaiend: ' + (data.current_php || '—') + (data.dockerfile_tag ? ' (image php:' + data.dockerfile_tag + ')' : '') + '. ' + data.message)
                : 'Kon PHP-status niet laden.';
        }
        if (btnPhp && data && data.button_label) {
            btnPhp.innerHTML = '<i class="ki-filled ki-docker me-1"></i>' + data.button_label;
        }
        setBusy(false);
    }

    function fetchJson(url) {
        return fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        }).then(function (response) {
            return response.json().then(function (payload) {
                if (!response.ok || !payload.success) {
                    throw new Error(payload.message || 'Kon status niet ophalen.');
                }
                return payload.data;
            });
        });
    }

    function consumeNdjson(response, list) {
        if (!response.ok || !response.body) {
            return response.json().catch(function () {
                throw new Error('Upgrade kon niet worden gestart.');
            }).then(function (payload) {
                throw new Error(payload.message || 'Upgrade kon niet worden gestart.');
            });
        }

        var reader = response.body.getReader();
        var decoder = new TextDecoder();
        var buffer = '';
        var complete = null;
        var sawReconnect = false;

        function pump() {
            return reader.read().then(function (chunk) {
                if (chunk.done) return { complete: complete, reconnect: sawReconnect };
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
                        } else if (event.type === 'reconnect') {
                            sawReconnect = true;
                            appendNote(list, 'Container wordt herstart. De pagina wacht tot de admin weer online is…');
                        } else if (event.type === 'complete') {
                            complete = event;
                        }
                    } catch (e) { /* ignore partial json */ }
                });
                return pump();
            });
        }

        return pump();
    }

    function postStream(url, body, list) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
                'X-System-Upgrade-Stream': '1',
            },
            body: JSON.stringify(body || {}),
        }).then(function (response) {
            return consumeNdjson(response, list);
        });
    }

    function sleep(ms) {
        return new Promise(function (resolve) { setTimeout(resolve, ms); });
    }

    function waitForAdmin(statusUrl, applyFn, list) {
        var attempts = 0;
        function tick() {
            attempts += 1;
            if (attempts > 90) {
                return Promise.reject(new Error('De admin kwam niet terug na de herstart. Controleer docker compose logs.'));
            }
            return fetch(statusUrl, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            }).then(function (response) {
                if (!response.ok) throw new Error('not ready');
                return response.json();
            }).then(function (payload) {
                if (!payload || !payload.success) throw new Error('not ready');
                if (typeof applyFn === 'function') {
                    applyFn(payload.data);
                }
                return payload.data;
            }).catch(function () {
                if (attempts === 1 || attempts % 3 === 0) {
                    appendNote(list, 'Wachten op herstart… (' + attempts + ')');
                }
                return sleep(4000).then(tick);
            });
        }
        return sleep(3000).then(tick);
    }

    function runLaravel(channel) {
        var target = channel === 'major'
            ? (laravelStatus && laravelStatus.major_target)
            : (laravelStatus && laravelStatus.minor_target);
        var title = channel === 'major' ? 'Laravel major-update' : 'Laravel minor-update';
        var message = channel === 'major'
            ? 'Laravel wordt naar ' + (target || 'de volgende major') + ' gezet. Daarna volgen migraties, tests en het opnieuw optuigen van de Docker-stack (build indien nodig). Bij falen gaan de Composer-bestanden terug. Doorgaan?'
            : 'Laravel wordt binnen de huidige major bijgewerkt naar ' + (target || 'de nieuwste patch') + '. Daarna volgen migraties, tests en het opnieuw optuigen van de Docker-stack (build indien nodig). Doorgaan?';

        confirmUpgrade(title, message, 'Upgraden').then(function (ok) {
            if (!ok) return;
            setBusy(true);
            if (laravelResultEl) {
                laravelResultEl.classList.add('hidden');
                laravelResultEl.textContent = '';
            }
            var list = initProgress(laravelProgressEl, 'Laravel-upgrade bezig…');
            var start = laravelStatus && laravelStatus.pending_finalize
                ? postStream(laravelFinalizeUrl, {}, list)
                : postStream(laravelRunUrl, { channel: channel }, list);

            start.then(function (outcome) {
                if (outcome && outcome.complete && !outcome.reconnect) {
                    return outcome;
                }
                appendNote(list, 'Verbinding verbroken tijdens herbouw — wachten tot de stack weer online is…');
                return waitForAdmin(laravelStatusUrl, applyLaravelStatus, list).then(function (status) {
                    if (status && status.pending_finalize) {
                        appendStep(list, 'Docker-stack afronden', 'running');
                        return postStream(laravelFinalizeUrl, {}, list);
                    }
                    return { complete: { success: true, message: 'Laravel-upgrade is afgerond; de stack is weer online.' } };
                });
            }).then(function (outcome) {
                var event = outcome && outcome.complete;
                var success = !!(event && event.success);
                showPanelResult(laravelResultEl, success, (event && event.message) || (success ? 'Klaar.' : 'Upgrade mislukt.'));
                if (success) {
                    setTimeout(function () { window.location.reload(); }, 1200);
                }
            }).catch(function (err) {
                showPanelResult(laravelResultEl, false, err.message || 'Laravel-upgrade mislukt.');
            }).finally(function () {
                setBusy(false);
                loadLaravelStatus();
            });
        });
    }

    function runPhp() {
        var phpOk = phpStatus && (phpStatus.can_run || phpStatus.pending_finalize);
        if (!phpOk) {
            return;
        }
        var label = (phpStatus && phpStatus.button_label) || 'PHP in Docker bijwerken';
        var message = 'De PHP-image in Docker wordt bijgewerkt. Daarna wordt de hele Docker-stack opnieuw opgetuigd (build indien nodig). De admin is kort even niet bereikbaar; daarna volgen tests. Doorgaan?';

        confirmUpgrade(label, message, 'Upgraden').then(function (ok) {
            if (!ok) return;
            setBusy(true);
            if (phpResultEl) {
                phpResultEl.classList.add('hidden');
                phpResultEl.textContent = '';
            }
            var list = initProgress(phpProgressEl, 'PHP-upgrade bezig…');
            var start = phpStatus && phpStatus.pending_finalize
                ? postStream(phpFinalizeUrl, {}, list)
                : postStream(phpRunUrl, {}, list);

            start.then(function (outcome) {
                if (outcome && outcome.complete && !outcome.reconnect) {
                    return outcome;
                }
                appendNote(list, 'Verbinding verbroken tijdens herbouw — wachten tot de stack weer online is…');
                return waitForAdmin(phpStatusUrl, applyPhpStatus, list).then(function (status) {
                    if (status && status.pending_finalize) {
                        appendStep(list, 'Stabiliteitstests starten', 'running');
                        return postStream(phpFinalizeUrl, {}, list);
                    }
                    return { complete: { success: true, message: 'PHP-container is weer online.' } };
                });
            }).then(function (outcome) {
                var event = outcome && outcome.complete;
                var success = !!(event && event.success);
                showPanelResult(phpResultEl, success, (event && event.message) || (success ? 'Klaar.' : 'PHP-upgrade mislukt.'));
                if (success) {
                    setTimeout(function () { window.location.reload(); }, 1200);
                }
            }).catch(function (err) {
                showPanelResult(phpResultEl, false, err.message || 'PHP-upgrade mislukt.');
            }).finally(function () {
                setBusy(false);
                loadPhpStatus();
            });
        });
    }

    function loadLaravelStatus() {
        return fetchJson(laravelStatusUrl).then(applyLaravelStatus).catch(function (err) {
            if (laravelStatusEl) laravelStatusEl.textContent = err.message || 'Kon Laravel-status niet laden.';
            if (btnLaravelMinor) btnLaravelMinor.classList.add('hidden');
            if (btnLaravelMajor) btnLaravelMajor.classList.add('hidden');
            laravelStatus = null;
            setBusy(false);
        });
    }

    function loadPhpStatus() {
        return fetchJson(phpStatusUrl).then(applyPhpStatus).catch(function (err) {
            if (phpStatusEl) phpStatusEl.textContent = err.message || 'Kon PHP-status niet laden.';
            phpStatus = null;
            setBusy(false);
        });
    }

    if (btnLaravelMinor) {
        btnLaravelMinor.addEventListener('click', function () { runLaravel('minor'); });
    }
    if (btnLaravelMajor) {
        btnLaravelMajor.addEventListener('click', function () { runLaravel('major'); });
    }
    if (btnPhp) {
        btnPhp.addEventListener('click', function () { runPhp(); });
    }

    loadLaravelStatus();
    loadPhpStatus();
})();
</script>
@endpush
