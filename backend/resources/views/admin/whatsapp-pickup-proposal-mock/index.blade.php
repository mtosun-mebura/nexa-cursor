@extends('admin.layouts.app')

@section('title', 'WhatsApp ophaalvoorstel test')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <div>
            <h1 class="text-xl font-medium leading-none text-mono">WhatsApp ophaalvoorstel test</h1>
            <p class="text-sm text-muted-foreground mt-2 mb-0 max-w-3xl">
                Simuleer een klantantwoord op een ophaalvoorstel, alsof WhatsApp de webhook heeft aangeroepen.
                Voorstellen uit de chauffeur-app verschijnen hier automatisch. Op productie doet de echte Meta-webhook dit.
            </p>
        </div>
        <span class="inline-flex items-center rounded-full border border-border px-3 py-1 text-xs font-medium {{ $mockAllowed ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300' : 'bg-muted text-muted-foreground' }}">
            Omgeving: {{ $environmentLabel }}
        </span>
    </div>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5" id="whatsapp-mock-flash">
            <div class="kt-alert-content">{{ session('success') }}</div>
        </div>
    @endif

    @if($errors->any())
        <div class="kt-alert kt-alert-danger mb-5">
            <div class="kt-alert-content">
                <ul class="list-disc list-inside mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div id="whatsapp-mock-live-flash" class="kt-alert kt-alert-success mb-5" hidden>
        <div class="kt-alert-content"></div>
    </div>

    <div class="kt-card mb-6">
        <div class="kt-card-header">
            <h3 class="kt-card-title text-base">Hoe deze test werkt</h3>
        </div>
        <div class="kt-card-content text-sm text-muted-foreground space-y-2">
            <p class="mb-0">
                Een voorstel vanuit de chauffeur-app komt hier in de lijst, met een mock-<code>wamid</code>
                als WhatsApp lokaal niet verstuurt. Testdata maakt daarnaast <strong>twee</strong> openstaande
                voorstellen op hetzelfde nummer
                (<code>{{ \App\Modules\NexaTaxi\Services\WhatsAppPickupProposalMockService::MOCK_PHONE }}</code>).
                Accepteren/weigeren stuurt hetzelfde JSON-bericht als Meta, inclusief <code>context.id</code>.
                Alleen de rit van die <code>wamid</code> mag van status veranderen.
            </p>
            @if($mockAllowed)
                <p class="mb-0">Mocken staat <strong>aan</strong> op deze omgeving (lokaal/test). Er gaat niets naar WhatsApp.</p>
            @else
                <p class="mb-0">
                    Mocken staat <strong>uit</strong>: dit is productie. Antwoorden komen binnen via
                    <code>https://nexasuite.nl/api/whatsapp/webhook</code>.
                </p>
            @endif
            @if(! $companyId)
                <p class="mb-0 text-amber-700 dark:text-amber-300">Kies links in de zijbalk een tenant voordat je testdata aanmaakt.</p>
            @endif
            @if($schemaError)
                <p class="mb-0 text-destructive">Taxi-schema niet beschikbaar: {{ $schemaError }}</p>
            @elseif(! $schemaReady)
                <p class="mb-0 text-destructive">Taxi-tabellen ontbreken. Voer <code>php artisan modules:migrate taxi</code> uit.</p>
            @endif
        </div>
    </div>

    @if($mockAllowed && $schemaReady)
        <div class="flex flex-wrap items-center gap-2 mb-6">
            <form method="POST" action="{{ route('admin.whatsapp-pickup-proposal-mock.seed') }}">
                @csrf
                <button type="submit" class="kt-btn kt-btn-primary" @disabled(! $companyId)>
                    Testdata aanmaken
                </button>
            </form>
            <button type="button" id="whatsapp-mock-bulk-delete" class="kt-btn kt-btn-destructive" hidden disabled>
                Geselecteerde verwijderen
            </button>
            @if($hasSeededRides)
                <form method="POST" action="{{ route('admin.whatsapp-pickup-proposal-mock.clear') }}"
                      onsubmit="return confirm('Gegenereerde WhatsApp-testritten wissen? Voorstellen uit de chauffeur-app blijven staan.');">
                    @csrf
                    <button type="submit" class="kt-btn kt-btn-outline">Testdata wissen</button>
                </form>
            @endif
        </div>
    @endif

    <div class="kt-card"
         id="whatsapp-mock-list"
         data-feed-url="{{ route('admin.whatsapp-pickup-proposal-mock.feed') }}"
         data-delete-url="{{ route('admin.whatsapp-pickup-proposal-mock.destroy') }}"
         data-simulate-url="{{ route('admin.whatsapp-pickup-proposal-mock.simulate') }}"
         data-csrf="{{ csrf_token() }}"
         data-mock-allowed="{{ $mockAllowed ? '1' : '0' }}"
         data-fingerprint="{{ $listFingerprint }}">
        <div class="kt-card-header flex flex-wrap items-center justify-between gap-2">
            <h3 class="kt-card-title text-base mb-0">Voorstellen</h3>
            <span class="text-xs text-muted-foreground" id="whatsapp-mock-live-label">Live · nieuwste bovenaan</span>
        </div>
        <div class="kt-card-content p-0" id="whatsapp-mock-list-body">
            @include('admin.whatsapp-pickup-proposal-mock.partials.table', [
                'rides' => $rides,
                'mockAllowed' => $mockAllowed,
                'proposalLabels' => $proposalLabels,
                'rideStatusLabels' => $rideStatusLabels,
            ])
        </div>
    </div>
</div>

<div id="whatsapp-mock-message-modal" class="whatsapp-mock-message-modal" hidden>
    <div class="whatsapp-mock-message-modal__backdrop" data-close-message-modal tabindex="-1"></div>
    <div class="whatsapp-mock-message-modal__panel" role="dialog" aria-modal="true" aria-labelledby="whatsapp-mock-message-title">
        <div class="flex items-start justify-between gap-3 mb-3">
            <div>
                <h3 id="whatsapp-mock-message-title" class="text-base font-medium mb-0">WhatsApp-bericht</h3>
                <p class="text-xs text-muted-foreground mb-0 mt-1" id="whatsapp-mock-message-meta"></p>
            </div>
            <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" data-close-message-modal aria-label="Sluiten">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>
        <pre id="whatsapp-mock-message-body" class="whatsapp-mock-message-modal__body"></pre>
    </div>
</div>
<div id="whatsapp-mock-icon-tooltip" class="whatsapp-mock-icon-tooltip" hidden></div>
@endsection

@push('styles')
<style>
#content #whatsapp-mock-table .whatsapp-mock-table__actions-col {
    width: 8.75rem !important;
    min-width: 8.75rem !important;
    max-width: 8.75rem !important;
    padding-inline: 0.25rem !important;
    text-align: center !important;
    vertical-align: middle !important;
    white-space: nowrap;
}
#content #whatsapp-mock-table .whatsapp-mock-table__actions-col .kt-btn-icon {
    width: 1.85rem;
    height: 1.85rem;
}
#content #whatsapp-mock-table .whatsapp-mock-table__check-col {
    width: 2.75rem !important;
    min-width: 2.75rem !important;
    max-width: 2.75rem !important;
    padding: 0 !important;
    text-align: center !important;
    vertical-align: middle !important;
}
#content #whatsapp-mock-table .whatsapp-mock-table__check-col .kt-label {
    display: flex !important;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-height: 2.5rem;
    margin: 0 !important;
    padding: 0 !important;
}
#content #whatsapp-mock-table .whatsapp-mock-table__check-col .kt-checkbox {
    margin: 0 !important;
    display: block;
}
.whatsapp-mock-icon-tooltip {
    position: fixed;
    z-index: 80;
    pointer-events: none;
    background: var(--mono, #111827);
    color: var(--mono-foreground, #fff);
    font-size: 0.75rem;
    line-height: 1.2;
    font-weight: 600;
    padding: 0.4rem 0.6rem;
    border-radius: 0.4rem;
    white-space: nowrap;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.18);
}
html.dark .whatsapp-mock-icon-tooltip {
    border: 1px solid var(--border);
}
.whatsapp-mock-message-modal {
    position: fixed;
    inset: 0;
    z-index: 70;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.25rem;
}
.whatsapp-mock-message-modal[hidden] {
    display: none !important;
}
.whatsapp-mock-message-modal__backdrop {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}
html.dark .whatsapp-mock-message-modal__backdrop {
    background: rgba(2, 6, 23, 0.55);
}
.whatsapp-mock-message-modal__panel {
    position: relative;
    width: min(36rem, 100%);
    max-height: min(80vh, 40rem);
    overflow: auto;
    background: var(--background, #fff);
    color: var(--foreground);
    border: 1px solid var(--border);
    border-radius: 0.85rem;
    padding: 1.15rem 1.25rem 1.25rem;
    box-shadow: 0 24px 48px rgba(15, 23, 42, 0.2);
}
.whatsapp-mock-message-modal__body {
    margin: 0;
    white-space: pre-wrap;
    word-break: break-word;
    font-size: 0.8125rem;
    line-height: 1.5;
    background: var(--muted, rgba(148, 163, 184, 0.12));
    border-radius: 0.6rem;
    padding: 0.85rem 1rem;
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    const root = document.getElementById('whatsapp-mock-list');
    if (!root) {
        return;
    }

    const body = document.getElementById('whatsapp-mock-list-body');
    const bulkBtn = document.getElementById('whatsapp-mock-bulk-delete');
    const liveLabel = document.getElementById('whatsapp-mock-live-label');
    const liveFlash = document.getElementById('whatsapp-mock-live-flash');
    const feedUrl = root.getAttribute('data-feed-url');
    const deleteUrl = root.getAttribute('data-delete-url');
    const simulateUrl = root.getAttribute('data-simulate-url');
    const csrf = root.getAttribute('data-csrf') || (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    const mockAllowed = root.getAttribute('data-mock-allowed') === '1';
    let fingerprint = root.getAttribute('data-fingerprint') || '';
    let selectedIds = new Set();
    let pollTimer = null;

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function statusClass(ride) {
        if (ride.pending) {
            return 'bg-amber-500/15 text-amber-800 dark:text-amber-300';
        }
        if (ride.proposal_status === 'accepted') {
            return 'bg-emerald-500/15 text-emerald-800 dark:text-emerald-300';
        }
        if (ride.declined) {
            return 'bg-destructive/10 text-destructive';
        }
        return 'bg-muted text-muted-foreground';
    }

    function actionButtons(ride) {
        let html = '<div class="flex items-center justify-center gap-0.5">';
        html += viewMessageButton(ride);
        if (mockAllowed && ride.pending) {
            html += simulateForm(ride.id, 'accept', 'Accepteren', '<i class="ki-filled ki-check text-emerald-600 dark:text-emerald-400"></i>');
            html += simulateForm(ride.id, 'decline', 'Weigeren', '<i class="ki-filled ki-cross text-destructive"></i>');
        }
        if (mockAllowed && (ride.pending || ride.declined)) {
            html += simulateForm(ride.id, 'remark', 'Opmerking', '<i class="ki-filled ki-message-text text-muted-foreground"></i>');
        }
        html += '</div>';
        return html;
    }

    function viewMessageButton(ride) {
        return (
            '<button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost whatsapp-mock-view-message" data-tooltip="Bericht bekijken" aria-label="Bericht bekijken" data-ride-id="' +
            escapeHtml(ride.id) +
            '" data-message="' +
            escapeHtml(ride.message_preview || '') +
            '"><i class="ki-filled ki-eye text-muted-foreground"></i></button>'
        );
    }

    function simulateForm(rideId, action, label, iconHtml) {
        return (
            '<form method="POST" action="' + escapeHtml(simulateUrl) + '">' +
            '<input type="hidden" name="_token" value="' + escapeHtml(csrf) + '">' +
            '<input type="hidden" name="ride_id" value="' + escapeHtml(rideId) + '">' +
            '<input type="hidden" name="action" value="' + escapeHtml(action) + '">' +
            '<button type="submit" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" data-tooltip="' + escapeHtml(label) + '" aria-label="' + escapeHtml(label) + '">' +
            iconHtml +
            '</button>' +
            '</form>'
        );
    }

    function render(rides) {
        if (!rides.length) {
            body.innerHTML =
                '<p class="text-sm text-muted-foreground px-5 py-6 mb-0">' +
                'Nog geen voorstellen. Stuur een ophaalvoorstel vanuit de chauffeur-app, of kies een tenant en klik op <strong>Testdata aanmaken</strong>.' +
                '</p>';
            syncBulkButton();
            return;
        }

        const rows = rides.map(function (ride) {
            const checked = selectedIds.has(String(ride.id)) ? ' checked' : '';
            return (
                '<tr data-ride-id="' + escapeHtml(ride.id) + '">' +
                '<td class="text-center whatsapp-mock-table__check-col" data-no-row-link>' +
                '<label class="kt-label mb-0 inline-flex items-center justify-center cursor-pointer">' +
                '<input type="checkbox" class="kt-checkbox whatsapp-mock-row-check" value="' + escapeHtml(ride.id) + '"' + checked + ' aria-label="Selecteer rit #' + escapeHtml(ride.id) + '">' +
                '</label></td>' +
                '<td class="whitespace-nowrap">' +
                '<div class="font-medium text-foreground">#' + escapeHtml(ride.id) + '</div>' +
                '<div class="text-xs text-muted-foreground">' + escapeHtml(ride.status_label) + '</div></td>' +
                '<td class="whitespace-nowrap"><span class="text-xs text-muted-foreground">' +
                escapeHtml(ride.source === 'testdata' ? 'Testdata' : 'Chauffeur-app') +
                '</span></td>' +
                '<td><div>' + escapeHtml(ride.customer_name) + '</div>' +
                '<div class="text-xs text-muted-foreground">' + escapeHtml(ride.customer_phone) + '</div></td>' +
                '<td><span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium ' + statusClass(ride) + '">' +
                escapeHtml(ride.proposal_label || '—') + '</span></td>' +
                '<td class="max-w-[10rem]"><code class="text-xs break-all line-clamp-2" title="' + escapeHtml(ride.wamid || '') + '">' + escapeHtml(ride.wamid || '—') + '</code></td>' +
                '<td class="max-w-[8rem] text-xs text-muted-foreground truncate" title="' + escapeHtml(ride.remark || '') + '">' + escapeHtml(ride.remark || '—') + '</td>' +
                '<td class="whatsapp-mock-table__actions-col whitespace-nowrap" data-no-row-link>' + actionButtons(ride) + '</td>' +
                '</tr>'
            );
        }).join('');

        const allChecked = rides.length > 0 && rides.every(function (ride) {
            return selectedIds.has(String(ride.id));
        });

        body.innerHTML =
            '<div class="kt-scrollable-x-auto admin-table-scroll-wrap">' +
            '<table class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full" id="whatsapp-mock-table">' +
            '<thead><tr>' +
            '<th class="text-center whatsapp-mock-table__check-col" data-label="">' +
            '<label class="kt-label mb-0 inline-flex items-center justify-center cursor-pointer">' +
            '<input type="checkbox" class="kt-checkbox" id="whatsapp-mock-select-all" aria-label="Alles selecteren"' +
            (allChecked ? ' checked' : '') + '>' +
            '</label></th>' +
            '<th data-label="Rit">Rit</th>' +
            '<th data-label="Bron">Bron</th>' +
            '<th data-label="Klant / nummer">Klant / nummer</th>' +
            '<th data-label="Voorstelstatus">Voorstelstatus</th>' +
            '<th data-label="wamid">wamid</th>' +
            '<th data-label="Opmerking">Opmerking</th>' +
            '<th class="text-center whatsapp-mock-table__actions-col" data-label="Acties">Acties</th>' +
            '</tr></thead><tbody>' + rows + '</tbody></table></div>';

        bindChecks();
        syncBulkButton();
        hideIconTooltip();
    }

    function bindChecks() {
        const selectAll = document.getElementById('whatsapp-mock-select-all');
        if (selectAll) {
            selectAll.addEventListener('change', function () {
                document.querySelectorAll('.whatsapp-mock-row-check').forEach(function (box) {
                    box.checked = selectAll.checked;
                    if (selectAll.checked) {
                        selectedIds.add(box.value);
                    } else {
                        selectedIds.delete(box.value);
                    }
                });
                syncBulkButton();
            });
        }
        document.querySelectorAll('.whatsapp-mock-row-check').forEach(function (box) {
            box.addEventListener('change', function () {
                if (box.checked) {
                    selectedIds.add(box.value);
                } else {
                    selectedIds.delete(box.value);
                }
                syncBulkButton();
            });
        });
    }

    function syncBulkButton() {
        if (!bulkBtn) {
            return;
        }
        const hasSelection = selectedIds.size > 0;
        bulkBtn.hidden = !hasSelection;
        bulkBtn.disabled = !hasSelection;
        bulkBtn.textContent = hasSelection
            ? 'Geselecteerde verwijderen (' + selectedIds.size + ')'
            : 'Geselecteerde verwijderen';
    }

    function showFlash(message) {
        if (!liveFlash) {
            return;
        }
        const content = liveFlash.querySelector('.kt-alert-content');
        if (content) {
            content.textContent = message;
        }
        liveFlash.hidden = false;
        const pageFlash = document.getElementById('whatsapp-mock-flash');
        if (pageFlash) {
            pageFlash.hidden = true;
        }
    }

    async function poll() {
        if (document.hidden) {
            return;
        }
        try {
            const res = await fetch(feedUrl, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!res.ok) {
                return;
            }
            const data = await res.json();
            const next = data.fingerprint || '';
            if (next === fingerprint) {
                if (liveLabel) {
                    liveLabel.textContent = 'Live · nieuwste bovenaan';
                }
                return;
            }
            fingerprint = next;
            root.setAttribute('data-fingerprint', fingerprint);
            const visibleIds = new Set((data.rides || []).map(function (ride) { return String(ride.id); }));
            selectedIds.forEach(function (id) {
                if (!visibleIds.has(id)) {
                    selectedIds.delete(id);
                }
            });
            render(data.rides || []);
            if (liveLabel) {
                liveLabel.textContent = 'Live · bijgewerkt';
            }
        } catch (e) {
            /* stil blijven pollen */
        }
    }

    if (bulkBtn) {
        bulkBtn.addEventListener('click', async function () {
            const ids = Array.from(selectedIds);
            if (!ids.length) {
                return;
            }
            if (!confirm('Geselecteerde voorstellen uit deze lijst verwijderen? Testdata wordt gewist; chauffeur-ritten blijven bestaan.')) {
                return;
            }
            bulkBtn.disabled = true;
            try {
                const res = await fetch(deleteUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ ride_ids: ids.map(Number) }),
                });
                const data = await res.json().catch(function () { return {}; });
                if (!res.ok) {
                    showFlash(data.message || 'Verwijderen mislukt.');
                    return;
                }
                selectedIds = new Set();
                fingerprint = '';
                showFlash(data.message || 'Voorstellen verwijderd.');
                await poll();
            } catch (e) {
                showFlash('Verwijderen mislukt.');
            } finally {
                syncBulkButton();
            }
        });
    }

    function hideIconTooltip() {
        const tip = document.getElementById('whatsapp-mock-icon-tooltip');
        if (tip) {
            tip.hidden = true;
            tip.textContent = '';
            tip.style.visibility = '';
        }
    }

    function showIconTooltip(btn) {
        const tip = document.getElementById('whatsapp-mock-icon-tooltip');
        const label = btn.getAttribute('data-tooltip') || '';
        if (!tip || !label) {
            return;
        }
        tip.textContent = label;
        tip.style.visibility = 'hidden';
        tip.hidden = false;
        tip.style.left = '0px';
        tip.style.top = '0px';
        const rect = btn.getBoundingClientRect();
        const tipRect = tip.getBoundingClientRect();
        let left = rect.left + (rect.width / 2) - (tipRect.width / 2);
        left = Math.max(8, Math.min(left, window.innerWidth - tipRect.width - 8));
        let top = rect.top - tipRect.height - 8;
        if (top < 8) {
            top = rect.bottom + 8;
        }
        tip.style.left = left + 'px';
        tip.style.top = top + 'px';
        tip.style.visibility = 'visible';
    }

    function closeMessageModal() {
        const modal = document.getElementById('whatsapp-mock-message-modal');
        if (modal) {
            modal.hidden = true;
        }
    }

    function openMessageModal(btn) {
        const modal = document.getElementById('whatsapp-mock-message-modal');
        const bodyEl = document.getElementById('whatsapp-mock-message-body');
        const metaEl = document.getElementById('whatsapp-mock-message-meta');
        if (!modal || !bodyEl) {
            return;
        }
        const rideId = btn.getAttribute('data-ride-id') || '';
        const message = btn.getAttribute('data-message') || '';
        bodyEl.textContent = message !== '' ? message : 'Geen berichtinhoud beschikbaar.';
        if (metaEl) {
            metaEl.textContent = rideId ? ('Rit #' + rideId) : '';
        }
        hideIconTooltip();
        modal.hidden = false;
    }

    bindChecks();
    syncBulkButton();
    pollTimer = window.setInterval(poll, 3000);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            poll();
        }
    });

    root.addEventListener('pointerover', function (ev) {
        const btn = ev.target.closest('[data-tooltip]');
        if (!btn || !root.contains(btn)) {
            return;
        }
        showIconTooltip(btn);
    });
    root.addEventListener('pointerout', function (ev) {
        const btn = ev.target.closest('[data-tooltip]');
        if (!btn) {
            return;
        }
        if (ev.relatedTarget && btn.contains(ev.relatedTarget)) {
            return;
        }
        hideIconTooltip();
    });
    root.addEventListener('click', function (ev) {
        const viewBtn = ev.target.closest('.whatsapp-mock-view-message');
        if (!viewBtn) {
            return;
        }
        ev.preventDefault();
        openMessageModal(viewBtn);
    });

    const messageModal = document.getElementById('whatsapp-mock-message-modal');
    if (messageModal) {
        messageModal.addEventListener('click', function (ev) {
            if (ev.target.closest('[data-close-message-modal]')) {
                closeMessageModal();
            }
        });
    }
    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape') {
            closeMessageModal();
            hideIconTooltip();
        }
    });
})();
</script>
@endpush
