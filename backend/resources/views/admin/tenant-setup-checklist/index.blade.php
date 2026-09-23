@extends('admin.layouts.app')

@section('title', 'Tenant configureren')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-col items-start gap-3 pb-7.5">
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="text-xl font-medium leading-none text-mono mb-0">Tenant configureren</h1>
            <span class="kt-badge kt-badge-light">Alleen super-admin</span>
        </div>
        <p class="text-sm text-muted-foreground mb-0 max-w-3xl">
            Checklist voor een nieuwe tenant met eigen domein: DNS (inclusief MX), Coolify + HTTPS, daarna koppelen in Nexa Suite.
            Vink stappen af terwijl je werkt — de voortgang blijft op dit apparaat bewaard.
            Huidig VPS-IP voor A-records: <code class="text-xs font-mono">{{ $vpsIp }}</code>
            (<a href="{{ route('admin.settings.upgrade.index') }}" class="text-primary hover:underline">wijzigen op Upgrade</a>).
            @if(!empty($exampleCompany))
                Domeinvoorbeeld voor <strong>{{ $exampleCompany->name }}</strong>:
                <code class="text-xs font-mono">{{ $exampleDomain }}</code>
                (naam aaneen + .nl).
            @else
                Selecteer een tenant om een domeinvoorbeeld op bedrijfsnaam te tonen (bijv. Taxi Nexa → <code class="text-xs font-mono">taxinexa.nl</code>).
            @endif
        </p>
    </div>

    <div class="kt-card w-full min-w-0 mb-6 overflow-hidden">
        <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
            <div>
                <h2 class="kt-card-title mb-0">Voortgang</h2>
                <p class="text-xs text-muted-foreground mb-0 mt-1" id="tenant-setup-progress-label">0 van {{ count($steps) }} stappen afgerond</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" id="tenant-setup-reset" class="kt-btn kt-btn-outline kt-btn-sm">
                    Checklist resetten
                </button>
            </div>
        </div>
        <div class="kt-card-content p-5">
            <div class="h-2 w-full rounded-full bg-muted overflow-hidden" aria-hidden="true">
                <div id="tenant-setup-progress-bar" class="h-full rounded-full bg-primary transition-all" style="width: 0%;"></div>
            </div>
            <p class="text-xs text-muted-foreground mb-0 mt-3">
                Tip: DNS A-records moeten naar <code class="font-mono">{{ $vpsIp }}</code> wijzen. MX/SPF/DKIM komen van de mailprovider van de klant.
            </p>
        </div>
    </div>

    <ol class="flex flex-col gap-5 list-none p-0 m-0" id="tenant-setup-checklist">
        @foreach($steps as $index => $step)
            @php $stepNum = $index + 1; @endphp
            <li class="kt-card w-full min-w-0 overflow-hidden tenant-setup-step" data-step-id="{{ $step['id'] }}">
                <div class="kt-card-header flex flex-wrap items-start justify-between gap-3 px-5 py-5">
                    <div class="flex items-start gap-3 min-w-0">
                        <span class="tenant-setup-step-num flex size-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary">
                            {{ $stepNum }}
                        </span>
                        <div class="min-w-0">
                            <h3 class="kt-card-title mb-0 text-base">{{ $step['title'] }}</h3>
                            <p class="text-sm text-muted-foreground mb-0 mt-1">{{ $step['summary'] }}</p>
                        </div>
                    </div>
                    <label class="kt-label mb-0 inline-flex items-center gap-2 cursor-pointer shrink-0">
                        <input type="checkbox"
                               class="kt-checkbox tenant-setup-step-check"
                               data-step-id="{{ $step['id'] }}"
                               aria-label="Stap {{ $stepNum }} afgerond: {{ $step['title'] }}">
                        <span class="text-sm text-secondary-foreground">Afgerond</span>
                    </label>
                </div>
                <div class="kt-card-content p-5 pt-5">
                    <ul class="flex flex-col gap-2 list-none p-0 m-0">
                        @foreach($step['items'] as $item)
                            <li class="flex items-start gap-2 text-sm text-foreground">
                                <i class="ki-filled ki-check-circle text-primary/70 text-base mt-0.5 shrink-0" aria-hidden="true"></i>
                                <span>
                                    @if(is_string($item))
                                        {{ $item }}
                                    @elseif(!empty($item['text']))
                                        {{ $item['text'] }}
                                    @else
                                        {{ $item['before'] ?? '' }}
                                        @if(!empty($item['link_url']) && !empty($item['link_label']))
                                            <a href="{{ $item['link_url'] }}" class="text-primary font-medium hover:underline">{{ $item['link_label'] }}</a>
                                        @endif
                                        {{ $item['after'] ?? '' }}
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                    @if(!empty($step['tip']))
                        <p class="text-xs text-muted-foreground mb-0 mt-4 rounded-lg border border-border bg-muted/20 px-3 py-2">
                            <span class="font-medium text-foreground">Tip:</span> {{ $step['tip'] }}
                        </p>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
</div>
@endsection

@push('styles')
<style>
    .tenant-setup-step.is-done {
        opacity: 0.72;
    }
    .tenant-setup-step.is-done .tenant-setup-step-num {
        background: color-mix(in srgb, var(--primary, #3b82f6) 18%, transparent);
    }
    .tenant-setup-step.is-done .kt-card-title {
        text-decoration: line-through;
        text-decoration-thickness: 1px;
    }
    .tenant-setup-step .kt-checkbox.tenant-setup-step-check {
        border-color: #16a34a;
    }
    .tenant-setup-step .kt-checkbox.tenant-setup-step-check:checked,
    .tenant-setup-step .kt-checkbox.tenant-setup-step-check:indeterminate {
        background-color: #16a34a !important;
        border-color: #16a34a !important;
        color: #ffffff !important;
    }
    .tenant-setup-step .kt-checkbox.tenant-setup-step-check:focus-visible {
        --tw-ring-color: #16a34a;
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var storageKey = 'nexa-tenant-setup-checklist-v1';
    var checks = Array.prototype.slice.call(document.querySelectorAll('.tenant-setup-step-check'));
    var progressBar = document.getElementById('tenant-setup-progress-bar');
    var progressLabel = document.getElementById('tenant-setup-progress-label');
    var resetBtn = document.getElementById('tenant-setup-reset');
    var total = checks.length;

    function loadState() {
        try {
            var raw = localStorage.getItem(storageKey);
            if (!raw) return {};
            var parsed = JSON.parse(raw);
            return parsed && typeof parsed === 'object' ? parsed : {};
        } catch (e) {
            return {};
        }
    }

    function saveState(state) {
        try {
            localStorage.setItem(storageKey, JSON.stringify(state));
        } catch (e) { /* ignore quota */ }
    }

    function updateProgress() {
        var done = checks.filter(function (el) { return el.checked; }).length;
        var pct = total ? Math.round((done / total) * 100) : 0;
        if (progressBar) {
            progressBar.style.width = pct + '%';
        }
        if (progressLabel) {
            progressLabel.textContent = done + ' van ' + total + ' stappen afgerond';
        }
        checks.forEach(function (el) {
            var card = el.closest('.tenant-setup-step');
            if (card) {
                card.classList.toggle('is-done', el.checked);
            }
        });
    }

    function applyState(state) {
        checks.forEach(function (el) {
            var id = el.getAttribute('data-step-id');
            el.checked = !!state[id];
        });
        updateProgress();
    }

    function currentState() {
        var state = {};
        checks.forEach(function (el) {
            state[el.getAttribute('data-step-id')] = el.checked;
        });
        return state;
    }

    applyState(loadState());

    checks.forEach(function (el) {
        el.addEventListener('change', function () {
            saveState(currentState());
            updateProgress();
        });
    });

    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            var message = 'Alle afgevinkte stappen wissen?';
            var doReset = function () {
                applyState({});
                saveState({});
            };
            if (typeof window.showAdminConfirm === 'function') {
                window.showAdminConfirm({
                    title: 'Checklist resetten',
                    message: message,
                    confirmLabel: 'Resetten'
                }).then(function (ok) {
                    if (ok) doReset();
                });
                return;
            }
            if (window.confirm(message)) {
                doReset();
            }
        });
    }
})();
</script>
@endpush
