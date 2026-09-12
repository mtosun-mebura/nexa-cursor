@extends('admin.layouts.app')

@include('admin.settings.partials.collapsible-section-assets')

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
    <p class="text-sm text-secondary-foreground -mt-3 mb-6">
        Git-deploy installeert de code in de repo, maar start Laravel- of PHP-upgrades niet automatisch.
        De Nexa-release gaat één patch omhoog na een geslaagde web-upgrade (Laravel, PHP of overige packages).
    </p>

    <div class="flex flex-col gap-5 mb-5" id="upgrade-collapsible-root">
        <div class="kt-card min-w-0 settings-collapsible-card settings-collapsible-card--collapsed" id="upgrade-installed-stack">
            @include('admin.settings.partials.collapsible-header', [
                'titleHtml' => 'Geïnstalleerde stack',
                'headerClass' => 'px-5 py-5',
            ])
            <div class="settings-collapsible-body">
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
        </div>

        <div class="kt-card min-w-0">
            <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
                <h3 class="kt-card-title mb-0">Laravel bijwerken</h3>
            </div>
            <div class="kt-card-body space-y-4 px-5 pt-5 pb-3 lg:px-6 lg:pt-6">
                <p class="text-sm text-secondary-foreground mb-0" id="laravel-upgrade-status-text">Beschikbare Laravel-updates ophalen…</p>
                <div class="flex flex-wrap gap-2 pt-5 pb-0">
                    <button type="button" id="btn-laravel-minor" class="kt-btn kt-btn-primary hidden" @disabled(!$webUpgradeEnabled) disabled>
                        <i class="ki-filled ki-laravel me-1" aria-hidden="true"></i>
                        Minor-update
                    </button>
                    <button type="button" id="btn-laravel-major" class="kt-btn kt-btn-success hidden" @disabled(!$webUpgradeEnabled) disabled>
                        <i class="ki-filled ki-laravel me-1" aria-hidden="true"></i>
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
                        <svg class="upgrade-php-icon me-1" viewBox="0 0 24 14" aria-hidden="true" focusable="false">
                            <ellipse class="upgrade-php-icon-shape" cx="12" cy="7" rx="11" ry="6.2"/>
                            <text class="upgrade-php-icon-word" x="12" y="9.7" text-anchor="middle" font-size="7.4" font-weight="700" font-style="italic" font-family="Georgia, 'Times New Roman', serif">php</text>
                        </svg>
                        PHP in Docker bijwerken
                    </button>
                </div>
                <div id="php-upgrade-progress" class="hidden"></div>
                <div id="php-upgrade-result" class="hidden rounded-md border border-border bg-muted/20 p-4 text-sm"></div>
            </div>
        </div>

        <div class="kt-card min-w-0">
            <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
                <h3 class="kt-card-title mb-0">PostgreSQL in Docker bijwerken</h3>
            </div>
            <div class="kt-card-body space-y-4 px-5 pt-5 pb-3 lg:px-6 lg:pt-6">
                <p class="text-sm text-secondary-foreground mb-0">
                    Minor pullt de nieuwste <code>pgvector/pgvector</code>-image van de huidige major.
                    Major zet een nieuwe major (bijv. pg16 → pg17) op een <strong>nieuw datavolume</strong> na een
                    <code>pg_dumpall</code>-backup. Bij een fout gaan de compose-bestanden terug en start de oude versie weer.
                </p>
                <p class="text-sm text-secondary-foreground mb-0" id="postgres-upgrade-status-text">Status ophalen…</p>
                <div class="flex flex-wrap gap-2 pt-5 pb-0">
                    <button type="button" id="btn-postgres-minor" class="kt-btn kt-btn-primary" @disabled(!$webUpgradeEnabled) disabled>
                        <span class="upgrade-pg-icon me-1" aria-hidden="true"><span class="upgrade-pg-icon-word">PG</span></span>
                        Minor-update
                    </button>
                    <button type="button" id="btn-postgres-major" class="kt-btn kt-btn-success" @disabled(!$webUpgradeEnabled) disabled>
                        <span class="upgrade-pg-icon me-1" aria-hidden="true"><span class="upgrade-pg-icon-word">PG</span></span>
                        Major-update
                    </button>
                </div>
                <div id="postgres-upgrade-progress" class="hidden"></div>
                <div id="postgres-upgrade-result" class="hidden rounded-md border border-border bg-muted/20 p-4 text-sm"></div>
            </div>
        </div>

        <div class="kt-card min-w-0">
            <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
                <h3 class="kt-card-title mb-0">Docker-containers</h3>
            </div>
            <div class="kt-card-body space-y-4 px-5 pt-5 pb-3 lg:px-6 lg:pt-6">
                <p class="text-sm text-secondary-foreground mb-0">
                    Herstart de stack of bouw images opnieuw. De admin is kort even niet bereikbaar.
                    Na opkomst verschijnt een groene melding in de header.
                </p>
                <p class="text-sm text-secondary-foreground mb-0">
                    Dit wijzigt Laravel, PHP of de Nexa-release niet.
                </p>
                <p class="text-sm text-secondary-foreground mb-0 pt-4" id="docker-upgrade-status-text">Status ophalen…</p>
                <div id="docker-container-table" class="kt-scrollable-x-auto admin-table-scroll-wrap hidden">
                    <table class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full mb-0">
                        <colgroup>
                            <col class="admin-table__check-col">
                            <col>
                            <col>
                            <col>
                            <col>
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="admin-table__check-col text-center" data-no-row-link data-label="">
                                    <label class="kt-label mb-0 inline-flex items-center justify-center cursor-pointer">
                                        <input type="checkbox"
                                               class="kt-checkbox"
                                               id="docker-container-select-all"
                                               title="Alle containers selecteren"
                                               aria-label="Alle containers selecteren">
                                    </label>
                                </th>
                                <th data-label="Service">Service</th>
                                <th data-label="Container">Container</th>
                                <th data-label="Image">Image</th>
                                <th data-label="Status">Status</th>
                            </tr>
                        </thead>
                        <tbody id="docker-container-rows"></tbody>
                    </table>
                </div>
                <div class="space-y-3 pt-5">
                    <p class="text-sm font-medium text-mono mb-0">Commando op container</p>
                    <p class="text-sm text-secondary-foreground mb-0">
                        Voert <code>docker exec</code> uit op één container van deze stack, bijvoorbeeld <code>php -v</code> of <code>psql --version</code>.
                    </p>
                    <div class="flex flex-wrap items-end gap-2">
                        <div class="min-w-[11rem]">
                            <label for="docker-exec-service" class="mb-1 block text-xs text-muted-foreground">Container</label>
                            <select id="docker-exec-service" class="kt-input w-full" disabled aria-label="Container voor commando">
                                <option value="">Kies een container</option>
                            </select>
                        </div>
                        <div class="min-w-[14rem] flex-1">
                            <label for="docker-exec-command" class="mb-1 block text-xs text-muted-foreground">Commando</label>
                            <input type="text"
                                   id="docker-exec-command"
                                   class="kt-input w-full font-mono"
                                   placeholder="php -v"
                                   autocomplete="off"
                                   maxlength="4000"
                                   disabled>
                        </div>
                        <button type="button" id="btn-docker-exec" class="kt-btn kt-btn-outline" disabled>
                            <i class="ki-filled ki-code me-1" aria-hidden="true"></i>
                            Uitvoeren
                        </button>
                    </div>
                    <pre id="docker-exec-output" class="upgrade-exec-output hidden mb-0" hidden></pre>
                </div>
                <div class="flex flex-wrap gap-2 pt-5 pb-0">
                    <button type="button" id="btn-docker-restart" class="kt-btn kt-btn-primary" disabled>
                        <i class="ki-filled ki-arrows-circle me-1"></i>
                        Containers herstarten
                    </button>
                    <button type="button" id="btn-docker-rebuild" class="kt-btn kt-btn-outline" @disabled(!$webUpgradeEnabled) disabled>
                        <i class="ki-filled ki-setting-2 me-1"></i>
                        Images opnieuw bouwen
                    </button>
                </div>
                <div id="docker-upgrade-progress" class="hidden"></div>
                <div id="docker-upgrade-result" class="hidden rounded-md border border-border bg-muted/20 p-4 text-sm"></div>
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
                                Controleert eerst of bestaande packages Laravel-major ondersteunen.
                                Constraints van incompatibele packages (zoals <code>laravel/tinker</code>)
                                worden meegenomen in <strong>één</strong> Composer-update samen met Laravel,
                                eerst als dry-run, daarna zonder Artisan-scripts (die draaien pas als vendor
                                consistent is). Ontbreekt een compatible versie, dan stopt de upgrade voordat
                                vendor wordt aangepast. Bij falen gaan de Composer-bestanden terug.
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
                            PHP kun je automatisch bijwerken met de knop <strong>PHP in Docker bijwerken</strong> hierboven.
                            PostgreSQL heeft eigen knoppen <strong>Minor-update</strong> en <strong>Major-update</strong>:
                            eerst een <code>pg_dumpall</code>-backup, daarna image/volume-wissel. Bij een fout start de oude versie weer.
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
                            <p class="mb-1 font-semibold text-mono">2. PostgreSQL (automatisch via de knoppen)</p>
                            <p class="mb-0">
                                <strong>Minor</strong> pullt de huidige tag (bijv. <code>pg16</code>) opnieuw.
                                <strong>Major</strong> schrijft <code>pgvector/pgvector:pg17</code> (of de volgende major) in
                                <code>docker-compose.postgres.yml</code> en <code>docker-compose.deploy.yml</code>,
                                zet een nieuw volume <code>…_postgres_data_pg17</code> in, herstelt de dump en laat het oude
                                volume staan. Mislukt de restore, dan gaan de compose-bestanden terug en komt de oude database weer online.
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
            <button type="button"
                    id="btn-upgrade-history-delete"
                    class="kt-btn kt-btn-sm kt-btn-ghost"
                    data-url="{{ route('admin.settings.upgrade.history.destroy') }}"
                    title="Geselecteerde regels verwijderen"
                    aria-label="Geselecteerde regels verwijderen (0)"
                    @disabled($upgradeHistory->isEmpty())>
                <i class="ki-filled ki-trash" aria-hidden="true"></i>
                <span class="upgrade-history-delete-count" aria-hidden="true">(<span id="upgrade-history-selected-count">0</span>)</span>
            </button>
        </div>
        <div class="kt-card-body p-5 lg:p-6 min-w-0">
            <div class="kt-scrollable-x-auto admin-table-scroll-wrap">
                <table class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full" id="upgrade-history-table">
                    <colgroup>
                        <col class="admin-table__check-col">
                        <col>
                        <col>
                        <col>
                        <col>
                        <col>
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="admin-table__check-col text-center" data-no-row-link data-label="">
                                <label class="kt-label mb-0 inline-flex items-center justify-center cursor-pointer">
                                    <input type="checkbox"
                                           class="kt-checkbox"
                                           id="upgrade-history-select-all"
                                           title="Alle regels selecteren"
                                           aria-label="Alle regels selecteren"
                                           @disabled($upgradeHistory->isEmpty())>
                                </label>
                            </th>
                            <th data-label="Datum">Datum</th>
                            <th data-label="Van">Van</th>
                            <th data-label="Naar">Naar</th>
                            <th data-label="Status">Status</th>
                            <th data-label="Door">Door</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($upgradeHistory as $log)
                            @php
                                $isRunning = $log->status === \App\Models\SystemUpgradeLog::STATUS_RUNNING;
                            @endphp
                            <tr data-id="{{ $log->id }}">
                                <td class="admin-table__check-col text-center" data-no-row-link data-label="">
                                    <label class="kt-label mb-0 inline-flex items-center justify-center {{ $isRunning ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer' }}">
                                        <input type="checkbox"
                                               class="kt-checkbox upgrade-history-row-check"
                                               value="{{ $log->id }}"
                                               aria-label="Selecteer upgrade van {{ $log->started_at?->timezone(config('app.timezone'))->format('d-m-Y H:i') }}"
                                               @disabled($isRunning)
                                               @if($isRunning) title="Upgrade is nog bezig" @endif>
                                    </label>
                                </td>
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
                            <tr class="upgrade-history-empty">
                                <td colspan="6" class="text-center text-secondary-foreground py-6">Nog geen upgrades uitgevoerd.</td>
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
    .upgrade-php-icon {
        width: 1.4rem;
        height: 0.82rem;
        display: inline-block;
        flex-shrink: 0;
        vertical-align: -0.12em;
        overflow: visible;
    }
    .upgrade-php-icon-shape {
        fill: currentColor;
    }
    .upgrade-php-icon-word {
        fill: var(--background);
    }
    .upgrade-pg-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.4rem;
        height: 0.82rem;
        padding: 0 0.2rem;
        border-radius: 0.2rem;
        font-size: 0.55rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        line-height: 1;
        background: currentColor;
        vertical-align: 0.05em;
    }
    .upgrade-pg-icon-word {
        color: var(--background);
    }
    .upgrade-exec-output {
        margin: 0;
        padding: 0.6rem 0.75rem;
        border-radius: 0.375rem;
        border: 1px solid var(--border);
        font-family: ui-monospace, monospace;
        font-size: 0.75rem;
        line-height: 1.5;
        white-space: pre-wrap;
        word-break: break-word;
        color: var(--foreground);
        max-height: 16rem;
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: color-mix(in srgb, var(--muted-foreground) 45%, transparent) transparent;
    }
    .upgrade-exec-output::-webkit-scrollbar {
        width: 8px;
    }
    .upgrade-exec-output::-webkit-scrollbar-track {
        background: transparent;
    }
    .upgrade-exec-output::-webkit-scrollbar-thumb {
        background-color: color-mix(in srgb, var(--muted-foreground) 40%, transparent);
        border-radius: 9999px;
        border: 2px solid transparent;
        background-clip: padding-box;
    }
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

    #docker-container-table col.admin-table__check-col,
    #docker-container-table th.admin-table__check-col,
    #docker-container-table td.admin-table__check-col,
    #upgrade-history-table col.admin-table__check-col,
    #upgrade-history-table th.admin-table__check-col,
    #upgrade-history-table td.admin-table__check-col {
        width: 2.75rem;
        min-width: 2.75rem;
        max-width: 2.75rem;
        padding-inline: 0.375rem !important;
        text-align: center;
        vertical-align: middle;
    }

    #content #docker-container-table .admin-fluid-table :is(th, td),
    #content #upgrade-history-table.admin-fluid-table :is(th, td) {
        vertical-align: middle;
    }

    #docker-container-table .admin-table__check-col .kt-label,
    #upgrade-history-table .admin-table__check-col .kt-label {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        min-height: 2rem;
        margin: 0;
    }

    #docker-container-table .admin-table__check-col .kt-checkbox,
    #upgrade-history-table .admin-table__check-col .kt-checkbox {
        margin: 0;
    }

    #btn-upgrade-history-delete {
        border: 0 !important;
        box-shadow: none !important;
        background-color: transparent !important;
        padding-inline: 0.35rem;
        height: auto;
        min-height: 2.25rem;
        gap: 0.3rem;
        align-items: center;
        font-variant-numeric: tabular-nums;
        color: #ef4444 !important;
    }
    #btn-upgrade-history-delete:hover,
    #btn-upgrade-history-delete:focus,
    #btn-upgrade-history-delete:focus-visible,
    #btn-upgrade-history-delete:active {
        background-color: transparent !important;
        color: #dc2626 !important;
    }
    #btn-upgrade-history-delete:disabled {
        opacity: 0.45;
        color: #ef4444 !important;
    }
    #btn-upgrade-history-delete i,
    #btn-upgrade-history-delete:hover i,
    #btn-upgrade-history-delete:focus i,
    #btn-upgrade-history-delete:active i,
    #btn-upgrade-history-delete:disabled i {
        font-size: 1.45rem !important;
        line-height: 1 !important;
        color: inherit !important;
    }
    #btn-upgrade-history-delete i::before,
    #btn-upgrade-history-delete i::after {
        color: inherit !important;
    }
    #btn-upgrade-history-delete .upgrade-history-delete-count {
        font-size: 0.9375rem !important;
        line-height: 1 !important;
        font-weight: 600;
        color: inherit !important;
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
function announceUpgradeSuccess(message) {
    if (typeof window.queueAdminHeaderFlash === 'function') {
        window.queueAdminHeaderFlash('success', message);
    }
}

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
        resultEl.className = 'rounded-md border p-4 text-sm whitespace-pre-wrap break-words ' + (success
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
                                    announceUpgradeSuccess(event.message || 'Upgrade is succesvol verwerkt.');
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
    var dockerStatusUrl = @json(route('admin.settings.upgrade.docker-status'));
    var dockerRunUrl = @json(route('admin.settings.upgrade.docker-run'));
    var dockerExecUrl = @json(route('admin.settings.upgrade.docker-exec'));
    var postgresStatusUrl = @json(route('admin.settings.upgrade.postgres-status'));
    var postgresRunUrl = @json(route('admin.settings.upgrade.postgres-run'));

    var laravelStatusEl = document.getElementById('laravel-upgrade-status-text');
    var phpStatusEl = document.getElementById('php-upgrade-status-text');
    var postgresStatusEl = document.getElementById('postgres-upgrade-status-text');
    var dockerStatusEl = document.getElementById('docker-upgrade-status-text');
    var dockerRowsEl = document.getElementById('docker-container-rows');
    var dockerTableEl = document.getElementById('docker-container-table');
    var dockerSelectAllEl = document.getElementById('docker-container-select-all');
    var btnLaravelMinor = document.getElementById('btn-laravel-minor');
    var btnLaravelMajor = document.getElementById('btn-laravel-major');
    var btnPhp = document.getElementById('btn-php-docker-upgrade');
    var btnPostgresMinor = document.getElementById('btn-postgres-minor');
    var btnPostgresMajor = document.getElementById('btn-postgres-major');
    var btnDockerRestart = document.getElementById('btn-docker-restart');
    var btnDockerRebuild = document.getElementById('btn-docker-rebuild');
    var btnDockerExec = document.getElementById('btn-docker-exec');
    var dockerExecServiceEl = document.getElementById('docker-exec-service');
    var dockerExecCommandEl = document.getElementById('docker-exec-command');
    var dockerExecOutputEl = document.getElementById('docker-exec-output');
    var laravelProgressEl = document.getElementById('laravel-upgrade-progress');
    var laravelResultEl = document.getElementById('laravel-upgrade-result');
    var phpProgressEl = document.getElementById('php-upgrade-progress');
    var phpResultEl = document.getElementById('php-upgrade-result');
    var postgresProgressEl = document.getElementById('postgres-upgrade-progress');
    var postgresResultEl = document.getElementById('postgres-upgrade-result');
    var dockerProgressEl = document.getElementById('docker-upgrade-progress');
    var dockerResultEl = document.getElementById('docker-upgrade-result');
    var laravelStatus = null;
    var phpStatus = null;
    var postgresStatus = null;
    var dockerStatus = null;

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function laravelIconHtml() {
        return '<i class="ki-filled ki-laravel me-1" aria-hidden="true"></i>';
    }

    function phpIconHtml() {
        return '<svg class="upgrade-php-icon me-1" viewBox="0 0 24 14" aria-hidden="true" focusable="false">' +
            '<ellipse class="upgrade-php-icon-shape" cx="12" cy="7" rx="11" ry="6.2"/>' +
            '<text class="upgrade-php-icon-word" x="12" y="9.7" text-anchor="middle" font-size="7.4" font-weight="700" font-style="italic" font-family="Georgia, \'Times New Roman\', serif">php</text>' +
            '</svg>';
    }

    function pgIconHtml() {
        return '<span class="upgrade-pg-icon me-1" aria-hidden="true"><span class="upgrade-pg-icon-word">PG</span></span>';
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
        resultEl.className = 'rounded-md border p-4 text-sm whitespace-pre-wrap break-words ' + (success
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
        if (btnDockerRestart) {
            btnDockerRestart.disabled = busy || !(dockerStatus && dockerStatus.can_restart);
        }
        if (btnDockerRebuild) {
            btnDockerRebuild.disabled = busy || !webUpgradeEnabled || !(dockerStatus && dockerStatus.can_rebuild);
        }
        if (btnPostgresMinor) {
            btnPostgresMinor.disabled = busy || !webUpgradeEnabled || !(postgresStatus && postgresStatus.can_minor);
        }
        if (btnPostgresMajor) {
            btnPostgresMajor.disabled = busy || !webUpgradeEnabled || !(postgresStatus && postgresStatus.can_major);
        }
        var execReady = !busy && !!(dockerStatus && dockerStatus.can_exec);
        if (dockerExecServiceEl) dockerExecServiceEl.disabled = !execReady;
        if (dockerExecCommandEl) dockerExecCommandEl.disabled = !execReady;
        if (btnDockerExec) {
            btnDockerExec.disabled = !execReady || !(dockerExecServiceEl && dockerExecServiceEl.value) || !(dockerExecCommandEl && dockerExecCommandEl.value.trim());
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
        return '<strong class="font-mono font-semibold text-foreground">' + escapeText(value || '—') + '</strong>';
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
            var html = 'Er is een nieuwe major-versie: Laravel ' + current +
                ' → ' + versionMark(data.major_target) + '.';
            var pkgs = Array.isArray(data.incompatible_packages) ? data.incompatible_packages : [];
            if (pkgs.length) {
                html += ' Incompatibele packages (' +
                    pkgs.map(escapeText).join(', ') +
                    ') gaan in dezelfde Composer-update mee als Laravel, eerst als dry-run en zonder Artisan-scripts tijdens het schrijven van vendor.';
            } else {
                html += ' Daarna volgen migraties, tests en het opnieuw optuigen van de Docker-stack (build indien nodig).';
            }
            html += ' Bij falen gaan composer.json en composer.lock terug.';
            return html;
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
                ? laravelIconHtml() + 'Minor-update naar ' + escapeText(data.minor_target)
                : laravelIconHtml() + 'Minor-update';
        }
        if (btnLaravelMajor) {
            var showMajor = !!(data && data.can_major && data.major_target);
            btnLaravelMajor.classList.toggle('hidden', !showMajor);
            btnLaravelMajor.innerHTML = (data && data.major_target)
                ? laravelIconHtml() + 'Major-update naar ' + escapeText(data.major_target)
                : laravelIconHtml() + 'Major-update';
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
            btnPhp.innerHTML = phpIconHtml() + escapeText(data.button_label);
        }
        setBusy(false);
    }

    function applyPostgresStatus(data) {
        postgresStatus = data || null;
        if (postgresStatusEl) {
            postgresStatusEl.innerHTML = data && data.message
                ? ('Image: ' + versionMark(data.current_tag || '—') + '. ' + escapeText(data.message))
                : 'Kon PostgreSQL-status niet laden.';
        }
        if (btnPostgresMinor) {
            btnPostgresMinor.innerHTML = pgIconHtml() + escapeText((data && data.minor_label) || 'Minor-update');
        }
        if (btnPostgresMajor) {
            btnPostgresMajor.innerHTML = pgIconHtml() + escapeText((data && data.major_label) || 'Major-update');
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
                    announceUpgradeSuccess((event && event.message) || 'Laravel-upgrade is succesvol verwerkt.');
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
                    announceUpgradeSuccess((event && event.message) || 'PHP-upgrade is succesvol verwerkt.');
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

    function applyDockerStatus(data) {
        dockerStatus = data || null;
        if (dockerStatusEl) {
            dockerStatusEl.innerHTML = data
                ? escapeText(data.message || '')
                : 'Kon Docker-status niet laden.';
        }
        if (data && data.flash) {
            announceUpgradeSuccess(data.flash);
            if (typeof window.showAdminHeaderFlash === 'function') {
                window.showAdminHeaderFlash('success', data.flash);
            }
        }
        renderDockerContainers(data && Array.isArray(data.containers) ? data.containers : []);
        setBusy(false);
    }

    function renderDockerContainers(containers) {
        if (!dockerRowsEl || !dockerTableEl) return;
        dockerRowsEl.innerHTML = '';
        if (!containers.length) {
            dockerTableEl.classList.add('hidden');
            syncDockerSelectAll();
            fillDockerExecSelect([]);
            return;
        }
        dockerTableEl.classList.remove('hidden');
        containers.forEach(function (row) {
            var tr = document.createElement('tr');
            var running = String(row.state || '').toLowerCase() === 'running';
            var service = String(row.service || '');
            tr.innerHTML =
                '<td class="admin-table__check-col text-center" data-no-row-link data-label="">' +
                '<label class="kt-label mb-0 inline-flex items-center justify-center cursor-pointer">' +
                '<input type="checkbox" class="kt-checkbox docker-container-check" value="' + escapeText(service) + '"' +
                ' checked aria-label="Selecteer ' + escapeText(service) + '">' +
                '</label></td>' +
                '<td data-label="Service">' + escapeText(service) + '</td>' +
                '<td data-label="Container" class="font-mono text-xs">' + escapeText(row.name) + '</td>' +
                '<td data-label="Image" class="font-mono text-xs break-all">' + escapeText(row.image) + '</td>' +
                '<td data-label="Status">' +
                '<span class="kt-badge ' + (running ? 'kt-badge-success' : 'kt-badge-destructive') + '">' +
                escapeText(row.status || row.state) + '</span></td>';
            dockerRowsEl.appendChild(tr);
        });
        dockerRowsEl.querySelectorAll('.docker-container-check').forEach(function (input) {
            input.addEventListener('change', syncDockerSelectAll);
        });
        syncDockerSelectAll();
        fillDockerExecSelect(containers);
    }

    function fillDockerExecSelect(containers) {
        if (!dockerExecServiceEl) return;
        var previous = dockerExecServiceEl.value;
        dockerExecServiceEl.innerHTML = '';
        var placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = containers.length ? 'Kies een container' : 'Geen containers';
        dockerExecServiceEl.appendChild(placeholder);
        containers.forEach(function (row) {
            var option = document.createElement('option');
            option.value = String(row.service || '');
            var running = String(row.state || '').toLowerCase() === 'running';
            option.textContent = (row.service || row.name || '—') + (running ? '' : ' (gestopt)');
            option.disabled = !running || !option.value;
            dockerExecServiceEl.appendChild(option);
        });
        if (previous && Array.from(dockerExecServiceEl.options).some(function (opt) { return opt.value === previous && !opt.disabled; })) {
            dockerExecServiceEl.value = previous;
        }
        setBusy(false);
    }

    function dockerRowChecks() {
        return dockerRowsEl ? dockerRowsEl.querySelectorAll('.docker-container-check') : [];
    }

    function syncDockerSelectAll() {
        var checks = dockerRowChecks();
        var checked = 0;
        checks.forEach(function (el) {
            if (el.checked) checked++;
        });
        if (!dockerSelectAllEl) return;
        dockerSelectAllEl.disabled = checks.length === 0;
        dockerSelectAllEl.checked = checks.length > 0 && checked === checks.length;
        dockerSelectAllEl.indeterminate = checked > 0 && checked < checks.length;
    }

    function selectedDockerServices() {
        var checks = dockerRowChecks();
        var selected = [];
        checks.forEach(function (el) {
            if (el.checked && el.value) selected.push(el.value);
        });
        if (selected.length === checks.length) {
            return [];
        }
        return selected;
    }

    function loadDockerStatus() {
        return fetchJson(dockerStatusUrl).then(applyDockerStatus).catch(function (err) {
            if (dockerStatusEl) dockerStatusEl.textContent = err.message || 'Kon Docker-status niet laden.';
            dockerStatus = null;
            setBusy(false);
        });
    }

    function loadPostgresStatus() {
        return fetchJson(postgresStatusUrl).then(applyPostgresStatus).catch(function (err) {
            if (postgresStatusEl) postgresStatusEl.textContent = err.message || 'Kon PostgreSQL-status niet laden.';
            postgresStatus = null;
            setBusy(false);
        });
    }

    function runDocker(action) {
        var isRebuild = action === 'rebuild';
        var services = isRebuild ? [] : selectedDockerServices();
        var checks = dockerRowChecks();
        var selectedCount = 0;
        checks.forEach(function (el) { if (el.checked) selectedCount++; });
        if (!isRebuild && (checks.length === 0 || selectedCount === 0)) {
            if (typeof window.showAdminHeaderFlash === 'function') {
                window.showAdminHeaderFlash('warning', 'Selecteer minstens één container om te herstarten.');
            } else {
                alert('Selecteer minstens één container om te herstarten.');
            }
            return;
        }
        var title = isRebuild ? 'Docker-images opnieuw bouwen' : 'Docker-containers herstarten';
        var restartScope = services.length
            ? (services.length === 1 ? 'Container ' + services[0] : 'Containers ' + services.join(', '))
            : 'Alle Docker-containers van deze stack';
        var message = isRebuild
            ? 'Images worden opnieuw gebouwd en de hele stack start daarna opnieuw. De admin is kort even niet bereikbaar. Doorgaan?'
            : restartScope + ' ' + (services.length === 1 ? 'wordt' : 'worden') + ' herstart. De admin is kort even niet bereikbaar. Doorgaan?';
        var successMessage = isRebuild
            ? 'Docker-stack is opnieuw gebouwd en herstart.'
            : (services.length === 0
                ? 'Docker-containers zijn herstart.'
                : (services.length === 1
                    ? 'Docker-container ' + services[0] + ' is herstart.'
                    : 'Docker-containers ' + services.join(', ') + ' zijn herstart.'));

        confirmUpgrade(title, message, isRebuild ? 'Opnieuw bouwen' : 'Herstarten').then(function (ok) {
            if (!ok) return;
            setBusy(true);
            if (dockerResultEl) {
                dockerResultEl.classList.add('hidden');
                dockerResultEl.textContent = '';
            }
            var list = initProgress(dockerProgressEl, isRebuild ? 'Docker-stack opnieuw bouwen…' : 'Docker-containers herstarten…');
            var payload = { action: action };
            if (!isRebuild) payload.services = services;
            postStream(dockerRunUrl, payload, list).then(function (outcome) {
                if (outcome && outcome.complete && !outcome.reconnect) {
                    return outcome;
                }
                appendNote(list, 'Verbinding verbroken tijdens herstart — wachten tot de stack weer online is…');
                return waitForAdmin(dockerStatusUrl, applyDockerStatus, list).then(function (status) {
                    return {
                        complete: {
                            success: true,
                            message: (status && status.flash) || successMessage,
                        }
                    };
                });
            }).then(function (outcome) {
                var event = outcome && outcome.complete;
                var success = !!(event && event.success);
                var text = (event && event.message) || (success ? successMessage : 'Docker-actie mislukt.');
                showPanelResult(dockerResultEl, success, text);
                if (success) {
                    announceUpgradeSuccess(text);
                    setTimeout(function () { window.location.reload(); }, 1200);
                }
            }).catch(function (err) {
                showPanelResult(dockerResultEl, false, err.message || 'Docker-actie mislukt.');
            }).finally(function () {
                setBusy(false);
                loadDockerStatus();
            });
        });
    }

    function runPostgres(channel) {
        var can = channel === 'major'
            ? (postgresStatus && postgresStatus.can_major)
            : (postgresStatus && postgresStatus.can_minor);
        if (!can) {
            return;
        }
        var target = channel === 'major'
            ? (postgresStatus && postgresStatus.major_target)
            : (postgresStatus && postgresStatus.minor_target);
        var title = channel === 'major' ? 'PostgreSQL major-update' : 'PostgreSQL minor-update';
        var message = channel === 'major'
            ? 'Er wordt eerst een pg_dumpall-backup gemaakt. Daarna start PostgreSQL op een nieuw volume als ' + (target || 'de volgende major') + '. Bij een fout gaat de oude versie weer aan. De admin kan kort haperen. Doorgaan?'
            : 'Er wordt eerst een pg_dumpall-backup gemaakt. Daarna wordt image ' + (target || 'pgvector') + ' opnieuw gepulld. Bij een fout blijft de huidige data staan. Doorgaan?';

        confirmUpgrade(title, message, 'Upgraden').then(function (ok) {
            if (!ok) return;
            setBusy(true);
            if (postgresResultEl) {
                postgresResultEl.classList.add('hidden');
                postgresResultEl.textContent = '';
            }
            var list = initProgress(postgresProgressEl, 'PostgreSQL-upgrade bezig…');
            postStream(postgresRunUrl, { channel: channel }, list).then(function (outcome) {
                var event = outcome && outcome.complete;
                var success = !!(event && event.success);
                showPanelResult(postgresResultEl, success, (event && event.message) || (success ? 'Klaar.' : 'PostgreSQL-upgrade mislukt.'));
                if (success) {
                    announceUpgradeSuccess((event && event.message) || 'PostgreSQL-upgrade is succesvol verwerkt.');
                    setTimeout(function () { window.location.reload(); }, 1200);
                }
            }).catch(function (err) {
                showPanelResult(postgresResultEl, false, err.message || 'PostgreSQL-upgrade mislukt.');
            }).finally(function () {
                setBusy(false);
                loadPostgresStatus();
                loadDockerStatus();
            });
        });
    }

    function showDockerExecOutput(text, success) {
        if (!dockerExecOutputEl) return;
        dockerExecOutputEl.hidden = false;
        dockerExecOutputEl.classList.remove('hidden');
        dockerExecOutputEl.textContent = text || '';
        dockerExecOutputEl.classList.toggle('text-destructive', success === false);
    }

    function runDockerExec() {
        var service = dockerExecServiceEl ? dockerExecServiceEl.value : '';
        var command = dockerExecCommandEl ? dockerExecCommandEl.value.trim() : '';
        if (!service || !command) {
            if (typeof window.showAdminHeaderFlash === 'function') {
                window.showAdminHeaderFlash('warning', 'Kies een container en voer een commando in.');
            }
            return;
        }
        setBusy(true);
        showDockerExecOutput('Bezig…', true);
        fetch(dockerExecUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ service: service, command: command }),
        }).then(function (response) {
            return response.json().then(function (payload) {
                return { ok: response.ok, payload: payload };
            });
        }).then(function (result) {
            var payload = result.payload || {};
            var data = payload.data || {};
            var output = data.output || payload.message || 'Geen uitvoer.';
            var header = (data.service || service) + ' · exit ' + (data.exit_code == null ? '?' : data.exit_code) + '\n';
            showDockerExecOutput(header + output, payload.success !== false && result.ok);
            if (payload.success === false && !data.output) {
                showDockerExecOutput(payload.message || 'Commando mislukt.', false);
            }
        }).catch(function (err) {
            showDockerExecOutput(err.message || 'Commando mislukt.', false);
        }).finally(function () {
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
    if (btnPostgresMinor) {
        btnPostgresMinor.addEventListener('click', function () { runPostgres('minor'); });
    }
    if (btnPostgresMajor) {
        btnPostgresMajor.addEventListener('click', function () { runPostgres('major'); });
    }
    if (btnDockerRestart) {
        btnDockerRestart.addEventListener('click', function () { runDocker('restart'); });
    }
    if (btnDockerRebuild) {
        btnDockerRebuild.addEventListener('click', function () { runDocker('rebuild'); });
    }
    if (btnDockerExec) {
        btnDockerExec.addEventListener('click', function () { runDockerExec(); });
    }
    if (dockerExecServiceEl) {
        dockerExecServiceEl.addEventListener('change', function () { setBusy(false); });
    }
    if (dockerExecCommandEl) {
        dockerExecCommandEl.addEventListener('input', function () { setBusy(false); });
        dockerExecCommandEl.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                runDockerExec();
            }
        });
    }
    if (dockerSelectAllEl) {
        dockerSelectAllEl.addEventListener('change', function () {
            var on = dockerSelectAllEl.checked;
            dockerRowChecks().forEach(function (el) { el.checked = on; });
            dockerSelectAllEl.indeterminate = false;
        });
    }

    (function initUpgradeHistory() {
        var table = document.getElementById('upgrade-history-table');
        var tbody = table ? table.querySelector('tbody') : null;
        var selectAll = document.getElementById('upgrade-history-select-all');
        var deleteBtn = document.getElementById('btn-upgrade-history-delete');
        if (!table || !tbody || !deleteBtn) {
            return;
        }

        function historyChecks() {
            return Array.from(table.querySelectorAll('.upgrade-history-row-check:not(:disabled)'));
        }

        function selectedHistoryIds() {
            return historyChecks().filter(function (el) { return el.checked; }).map(function (el) {
                return parseInt(el.value, 10);
            }).filter(function (id) { return id > 0; });
        }

        function setHistoryDeleteCount(n) {
            var countEl = document.getElementById('upgrade-history-selected-count');
            if (countEl) {
                countEl.textContent = String(n);
            }
            deleteBtn.setAttribute('aria-label', 'Geselecteerde regels verwijderen (' + n + ')');
            deleteBtn.setAttribute('title', n === 1
                ? '1 geselecteerde regel verwijderen'
                : n + ' geselecteerde regels verwijderen');
        }

        function syncHistorySelection() {
            var boxes = historyChecks();
            var selected = boxes.filter(function (el) { return el.checked; });
            if (selectAll) {
                selectAll.disabled = boxes.length === 0;
                selectAll.checked = boxes.length > 0 && selected.length === boxes.length;
                selectAll.indeterminate = selected.length > 0 && selected.length < boxes.length;
            }
            deleteBtn.disabled = selected.length === 0;
            setHistoryDeleteCount(selected.length);
        }

        function showEmptyHistory() {
            tbody.innerHTML = '<tr class="upgrade-history-empty"><td colspan="6" class="text-center text-secondary-foreground py-6">Nog geen upgrades uitgevoerd.</td></tr>';
            if (selectAll) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
                selectAll.disabled = true;
            }
            deleteBtn.disabled = true;
            setHistoryDeleteCount(0);
        }

        table.addEventListener('change', function (e) {
            var target = e.target;
            if (!target) {
                return;
            }
            if (target === selectAll) {
                var on = selectAll.checked;
                historyChecks().forEach(function (el) { el.checked = on; });
                selectAll.indeterminate = false;
                syncHistorySelection();
                return;
            }
            if (target.classList && target.classList.contains('upgrade-history-row-check')) {
                syncHistorySelection();
            }
        });

        deleteBtn.addEventListener('click', function () {
            var ids = selectedHistoryIds();
            var url = deleteBtn.getAttribute('data-url');
            if (!ids.length || !url || deleteBtn.disabled) {
                return;
            }
            var message = ids.length === 1
                ? 'Deze regel uit de upgradegeschiedenis verwijderen?'
                : 'Deze ' + ids.length + ' regels uit de upgradegeschiedenis verwijderen?';
            var runDelete = function () {
                deleteBtn.disabled = true;
                fetch(url, {
                    method: 'DELETE',
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                    body: JSON.stringify({ ids: ids }),
                })
                    .then(function (response) {
                        return response.json().then(function (data) {
                            return { ok: response.ok, data: data };
                        }).catch(function () {
                            return { ok: false, data: { message: 'Verwijderen mislukt.' } };
                        });
                    })
                    .then(function (result) {
                        if (!result.ok || !(result.data && result.data.success)) {
                            throw new Error((result.data && result.data.message) || 'Verwijderen mislukt.');
                        }
                        ids.forEach(function (id) {
                            var row = tbody.querySelector('tr[data-id="' + id + '"]');
                            if (row) {
                                row.remove();
                            }
                        });
                        if (!tbody.querySelector('tr[data-id]')) {
                            showEmptyHistory();
                        } else {
                            syncHistorySelection();
                        }
                        if (typeof window.showAdminHeaderFlash === 'function') {
                            window.showAdminHeaderFlash('success', result.data.message);
                        }
                    })
                    .catch(function (err) {
                        if (typeof window.showAdminHeaderFlash === 'function') {
                            window.showAdminHeaderFlash('error', err.message || 'Verwijderen mislukt.');
                        }
                        syncHistorySelection();
                    });
            };

            if (typeof window.showAdminConfirm === 'function') {
                window.showAdminConfirm({
                    title: 'Regels verwijderen',
                    message: message,
                    confirmLabel: 'Verwijderen',
                    destructive: true,
                }).then(function (ok) {
                    if (ok) {
                        runDelete();
                    }
                });
                return;
            }
            if (window.confirm(message)) {
                runDelete();
            }
        });

        syncHistorySelection();
    })();

    loadLaravelStatus();
    loadPhpStatus();
    loadPostgresStatus();
    loadDockerStatus();
})();
</script>
@endpush
