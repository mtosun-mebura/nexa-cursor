@extends('admin.layouts.app')

@section('title', $group->name)

@section('content')
@php
    $groupWorkspaceTab = request('tab', 'gegevens');
    if ($errors->has('transport_passenger_id') || $errors->has('valid_from')) {
        $groupWorkspaceTab = 'leden';
    }
    if (! in_array($groupWorkspaceTab, ['gegevens', 'leden', 'route'], true)) {
        $groupWorkspaceTab = 'gegevens';
    }
    $groupWorkspaceMemberCount = $activeMembers->count();
    $workspaceTitle = $group->name;
    $workspaceSubtitle = match ($groupWorkspaceTab) {
        'leden' => 'Passagiers in deze groep',
        'route' => 'Geplande stops en tijden',
        default => 'Adres, aankomsttijd en terugweg',
    };
@endphp

<div class="kt-container-fixed min-w-0">
    @include('taxi::admin.transport_groups.partials.group-workspace-header')
    @include('taxi::admin.transport_customers.partials.contract-workspace-styles')

    @if($errors->any())
        <div class="kt-alert kt-alert-danger mb-5 mt-5" role="alert">
            <ul class="list-disc list-inside mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-5 lg:gap-7.5 pt-5">
        @if($groupWorkspaceTab === 'gegevens')
            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
                    <h3 class="kt-card-title mb-0">Groepsgegevens</h3>
                    @can('rides.update')
                    <a href="{{ transport_admin_url_with_return(route('admin.taxi.transport_groups.edit', [$customer->id, $contract->id, $group->id]), url()->full()) }}" class="kt-btn kt-btn-sm kt-btn-outline shrink-0">
                        Bewerken
                    </a>
                    @endcan
                </div>
                <div class="kt-card-content p-0">
                    <div class="px-3 sm:px-5 pb-3 min-w-0">
                        <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground w-full">
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-medium">Vertrekadres</td>
                                <td>
                                    @if($group->departure_address)
                                        {{ $group->departure_address }}
                                    @else
                                        <span class="text-muted-foreground">Eerste ophaalstop</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-medium">Eindlocatie</td>
                                <td>{{ $group->destination_address }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-medium">Aankomsttijd heenweg</td>
                                <td>{{ substr($group->destination_arrival_time, 0, 5) }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-medium">Terugweg</td>
                                <td>
                                    @if($group->has_return_trip)
                                        <span class="kt-badge kt-badge-success kt-badge-sm">Aan</span>
                                        @if($group->return_pickup_time)
                                            <span class="ms-2">ophalen {{ substr($group->return_pickup_time, 0, 5) }}</span>
                                        @endif
                                        <span class="text-muted-foreground ms-1">(+{{ (int) ($group->return_boarding_delay_minutes ?? 15) }} min instaptijd)</span>
                                    @else
                                        <span class="text-muted-foreground">Uit</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-medium">Status</td>
                                <td>
                                    @if($group->active)
                                        <span class="kt-badge kt-badge-success kt-badge-sm">Actief</span>
                                    @else
                                        <span class="kt-badge kt-badge-secondary kt-badge-sm">Inactief</span>
                                    @endif
                                </td>
                            </tr>
                            @if($group->notes)
                            <tr>
                                <td class="text-secondary-foreground font-medium">Notities</td>
                                <td class="whitespace-pre-wrap">{{ $group->notes }}</td>
                            </tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        @elseif($groupWorkspaceTab === 'leden')
            <div class="kt-card kt-card-grid w-full min-w-0">
                <div class="kt-card-header flex flex-wrap items-center justify-between gap-2 px-5 py-5">
                    <h3 class="kt-card-title mb-0" id="transport-group-members-title">Leden ({{ $activeMembers->count() }})</h3>
                    @can('rides.update')
                    <button type="button"
                            class="kt-btn kt-btn-primary kt-btn-sm shrink-0"
                            id="transport-group-add-members-open"
                            aria-controls="transport-group-add-members-modal"
                            aria-expanded="false">
                        <i class="ki-filled ki-plus-squared me-1"></i>
                        Leden toevoegen
                    </button>
                    @endcan
                </div>
                <div class="kt-card-content p-0 min-w-0" id="transport-group-members-panel">
                    @include('taxi::admin.transport_groups.partials.members-table')
                </div>
            </div>
        @else
            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header flex flex-wrap items-center justify-between gap-2 px-5 py-5">
                    <div class="min-w-0">
                        <h3 class="kt-card-title mb-0">Route</h3>
                        <p class="text-sm text-muted-foreground mt-1.5 mb-0">Overzicht van heenweg en terugweg. Open de routeplanner om te herberekenen of vast te zetten.</p>
                    </div>
                    @can('rides.view')
                    <a href="{{ transport_admin_url_with_return(route('admin.taxi.transport_groups.route.edit', [$customer->id, $contract->id, $group->id]), url()->full()) }}" class="kt-btn kt-btn-primary kt-btn-sm shrink-0">
                        <i class="ki-filled ki-route me-1"></i>
                        Routeplanner
                    </a>
                    @endcan
                </div>
                <div class="kt-card-content p-0 min-w-0" id="transport-group-route-panel">
                    @include('taxi::admin.transport_groups.partials.route-panel')
                </div>
            </div>
        @endif
    </div>
</div>

@can('rides.update')
<div id="transport-group-add-members-modal"
     class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-900/45 backdrop-blur-md p-4"
     role="dialog"
     aria-modal="true"
     aria-labelledby="transport-group-add-members-modal-title"
     aria-hidden="true">
    <div class="w-full max-w-3xl max-h-[min(90vh,44rem)] flex flex-col rounded-xl border border-input bg-background shadow-xl overflow-hidden">
        <div class="flex items-center justify-between gap-3 border-b border-input px-6 py-4 shrink-0">
            <h3 id="transport-group-add-members-modal-title" class="text-lg font-semibold text-foreground mb-0">
                Leden toevoegen
            </h3>
            <button type="button"
                    class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost"
                    data-transport-group-add-members-close
                    aria-label="Sluiten">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>
        <div id="transport-group-add-members-modal-body" class="flex flex-col flex-1 min-h-0 overflow-hidden">
            @include('taxi::admin.transport_groups.partials.add-members-modal-body')
        </div>
    </div>
</div>
@endcan
@endsection

@push('styles')
<style>
    #transport-group-add-members-modal .transport-group-passenger-picker__list {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        max-height: min(22rem, 50vh);
        overflow-y: auto;
        padding: 0.25rem;
        border-radius: 0.75rem;
        border: 1px solid var(--border);
    }

    #transport-group-add-members-modal .transport-group-passenger-picker__item {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 0.75rem 0.875rem;
        border-radius: 0.625rem;
        border: 1px solid transparent;
        background: var(--background);
        cursor: pointer;
        transition: border-color 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease;
    }

    #transport-group-add-members-modal .transport-group-passenger-picker__item:hover {
        border-color: color-mix(in oklab, var(--primary) 25%, var(--border));
        background: color-mix(in oklab, var(--primary) 4%, var(--background));
    }

    #transport-group-add-members-modal .transport-group-passenger-picker__item:has(input:checked) {
        border-color: color-mix(in oklab, var(--primary) 45%, var(--border));
        background: color-mix(in oklab, var(--primary) 8%, var(--background));
        box-shadow: 0 0 0 1px color-mix(in oklab, var(--primary) 12%, transparent);
    }

    #transport-group-add-members-modal .transport-group-passenger-picker__item.is-hidden {
        display: none;
    }

    #transport-group-add-members-modal .transport-group-passenger-picker__name {
        display: block;
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--foreground);
        line-height: 1.35;
    }

    #transport-group-add-members-modal .transport-group-passenger-picker__address {
        display: block;
        margin-top: 0.125rem;
        font-size: 0.75rem;
        color: var(--muted-foreground);
        line-height: 1.4;
        word-break: break-word;
    }

    #content #transport-group-members-table .transport-group-members-table__actions-col {
        width: 4.5rem !important;
        min-width: 4.5rem !important;
        max-width: 4.5rem !important;
        padding-inline: 0.375rem !important;
        text-align: center !important;
        vertical-align: middle !important;
        white-space: nowrap;
    }

    #transport-group-route-panel .route-stop-destination-row > td {
        background-color: rgba(16, 185, 129, 0.12);
    }
    .dark #transport-group-route-panel .route-stop-destination-row > td {
        background-color: rgba(16, 185, 129, 0.16);
    }

    #transport-group-route-panel .route-leg-tabs {
        display: flex;
        gap: 0.65rem;
        flex-wrap: wrap;
    }

    #transport-group-route-panel .route-leg-tab {
        flex: 1 1 10rem;
        min-width: 9rem;
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
        padding: 0.8rem 1rem;
        border: 1.5px solid transparent;
        border-radius: 0.75rem;
        text-decoration: none;
        color: inherit;
        transition: border-color 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease, filter 0.15s ease;
    }

    /* Heenweg: blauw/sky */
    #transport-group-route-panel .route-leg-tab[data-leg="heen"] {
        background: color-mix(in oklab, #0ea5e9 16%, var(--background));
        border-color: color-mix(in oklab, #0ea5e9 48%, var(--border));
    }

    #transport-group-route-panel .route-leg-tab[data-leg="heen"] .route-leg-tab__label {
        color: #0369a1;
    }

    .dark #transport-group-route-panel .route-leg-tab[data-leg="heen"] .route-leg-tab__label {
        color: #7dd3fc;
    }

    #transport-group-route-panel .route-leg-tab[data-leg="heen"]:hover {
        background: color-mix(in oklab, #0ea5e9 24%, var(--background));
        border-color: color-mix(in oklab, #0ea5e9 65%, var(--border));
    }

    #transport-group-route-panel .route-leg-tab[data-leg="heen"].is-active {
        background: color-mix(in oklab, #0ea5e9 32%, var(--background));
        border-color: #38bdf8;
        box-shadow: 0 0 0 1px color-mix(in oklab, #0ea5e9 40%, transparent);
    }

    /* Terugweg: amber/oranje */
    #transport-group-route-panel .route-leg-tab[data-leg="terug"] {
        background: color-mix(in oklab, #f59e0b 16%, var(--background));
        border-color: color-mix(in oklab, #f59e0b 48%, var(--border));
    }

    #transport-group-route-panel .route-leg-tab[data-leg="terug"] .route-leg-tab__label {
        color: #b45309;
    }

    .dark #transport-group-route-panel .route-leg-tab[data-leg="terug"] .route-leg-tab__label {
        color: #fcd34d;
    }

    #transport-group-route-panel .route-leg-tab[data-leg="terug"]:hover {
        background: color-mix(in oklab, #f59e0b 24%, var(--background));
        border-color: color-mix(in oklab, #f59e0b 65%, var(--border));
    }

    #transport-group-route-panel .route-leg-tab[data-leg="terug"].is-active {
        background: color-mix(in oklab, #f59e0b 32%, var(--background));
        border-color: #fbbf24;
        box-shadow: 0 0 0 1px color-mix(in oklab, #f59e0b 40%, transparent);
    }

    #transport-group-route-panel .route-leg-tab__label {
        font-size: 0.875rem;
        font-weight: 650;
        line-height: 1.25;
    }

    #transport-group-route-panel .route-leg-tab__hint {
        font-size: 0.7rem;
        color: var(--muted-foreground);
        line-height: 1.3;
    }

    .dark #transport-group-route-panel .route-leg-tab[data-leg="heen"].is-active .route-leg-tab__hint,
    .dark #transport-group-route-panel .route-leg-tab[data-leg="terug"].is-active .route-leg-tab__hint {
        color: color-mix(in oklab, var(--muted-foreground) 65%, #fff);
    }

    #transport-group-route-panel .route-stops-collapse__toggle {
        display: flex;
        width: 100%;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.85rem 1rem;
        border: 0;
        border-top: 1.5px solid transparent;
        font-size: 0.875rem;
        font-weight: 650;
        cursor: pointer;
        text-align: left;
        transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    }

    #transport-group-route-panel .route-stops-collapse[data-leg="heen"] .route-stops-collapse__toggle {
        background: color-mix(in oklab, #0ea5e9 12%, var(--background));
        border-top-color: color-mix(in oklab, #0ea5e9 35%, var(--border));
        color: #0369a1;
    }

    .dark #transport-group-route-panel .route-stops-collapse[data-leg="heen"] .route-stops-collapse__toggle {
        background: color-mix(in oklab, #0ea5e9 18%, var(--background));
        color: #7dd3fc;
    }

    #transport-group-route-panel .route-stops-collapse[data-leg="heen"] .route-stops-collapse__toggle:hover {
        background: color-mix(in oklab, #0ea5e9 22%, var(--background));
    }

    #transport-group-route-panel .route-stops-collapse[data-leg="terug"] .route-stops-collapse__toggle {
        background: color-mix(in oklab, #f59e0b 12%, var(--background));
        border-top-color: color-mix(in oklab, #f59e0b 35%, var(--border));
        color: #b45309;
    }

    .dark #transport-group-route-panel .route-stops-collapse[data-leg="terug"] .route-stops-collapse__toggle {
        background: color-mix(in oklab, #f59e0b 18%, var(--background));
        color: #fcd34d;
    }

    #transport-group-route-panel .route-stops-collapse[data-leg="terug"] .route-stops-collapse__toggle:hover {
        background: color-mix(in oklab, #f59e0b 22%, var(--background));
    }

    #transport-group-route-panel .route-stops-collapse[data-leg="heen"] .route-stops-collapse__chevron {
        color: #0284c7;
    }

    .dark #transport-group-route-panel .route-stops-collapse[data-leg="heen"] .route-stops-collapse__chevron {
        color: #7dd3fc;
    }

    #transport-group-route-panel .route-stops-collapse[data-leg="terug"] .route-stops-collapse__chevron {
        color: #d97706;
    }

    .dark #transport-group-route-panel .route-stops-collapse[data-leg="terug"] .route-stops-collapse__chevron {
        color: #fcd34d;
    }

    #transport-group-route-panel .route-stops-collapse__chevron {
        font-size: 0.75rem;
        transition: transform 0.15s ease;
    }

    #transport-group-route-panel .route-stops-collapse__chevron.is-open {
        transform: rotate(180deg);
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-route-stops-toggle]');
        if (!toggle) return;

        var root = toggle.closest('[data-route-stops-collapse]');
        if (!root) return;

        var panel = root.querySelector('[data-route-stops-panel]');
        var chevron = toggle.querySelector('.route-stops-collapse__chevron');
        var labelOpen = toggle.querySelector('[data-route-stops-label-open]');
        var labelClosed = toggle.querySelector('[data-route-stops-label-closed]');
        if (!panel) return;

        var open = toggle.getAttribute('aria-expanded') === 'true';
        var nextOpen = !open;
        toggle.setAttribute('aria-expanded', nextOpen ? 'true' : 'false');
        panel.classList.toggle('hidden', !nextOpen);
        if (nextOpen) {
            panel.removeAttribute('hidden');
        } else {
            panel.setAttribute('hidden', 'hidden');
        }
        if (chevron) chevron.classList.toggle('is-open', nextOpen);
        if (labelOpen) labelOpen.classList.toggle('hidden', !nextOpen);
        if (labelClosed) labelClosed.classList.toggle('hidden', nextOpen);
    });
})();
</script>
@can('rides.update')
<script>
(function () {
    var openBtn = document.getElementById('transport-group-add-members-open');
    var modal = document.getElementById('transport-group-add-members-modal');
    var membersPanel = document.getElementById('transport-group-members-panel');
    var routePanel = document.getElementById('transport-group-route-panel');
    var membersTitle = document.getElementById('transport-group-members-title');
    var memberModalBody = document.getElementById('transport-group-add-members-modal-body');

    function getPassengerPickerPanel() {
        return document.getElementById('transport-group-passenger-picker-panel');
    }

    function bindPassengerPickerSearch(root) {
        if (!root) return;

        var searchInput = root.querySelector('[data-transport-group-passenger-search]');
        var items = root.querySelectorAll('[data-passenger-picker-item]');
        var emptyHint = root.querySelector('[data-transport-group-passenger-empty]');
        var countHint = root.querySelector('[data-transport-group-passenger-count]');

        if (!searchInput || items.length === 0) return;

        if (searchInput.dataset.searchBound === '1') return;
        searchInput.dataset.searchBound = '1';

        searchInput.addEventListener('input', function () {
            var query = searchInput.value.trim().toLowerCase();
            var visibleCount = 0;

            items.forEach(function (item) {
                var haystack = item.getAttribute('data-search-text') || '';
                var visible = query === '' || haystack.indexOf(query) !== -1;
                item.classList.toggle('is-hidden', !visible);
                if (visible) visibleCount += 1;
            });

            if (emptyHint) {
                emptyHint.classList.toggle('hidden', visibleCount > 0 || query === '');
            }
            if (countHint) {
                countHint.classList.toggle('hidden', query !== '' && visibleCount === 0);
            }
        });
    }

    function resetPassengerPicker(root) {
        if (!root) return;

        var searchInput = root.querySelector('[data-transport-group-passenger-search]');
        if (searchInput) searchInput.value = '';

        root.querySelectorAll('[data-passenger-picker-item].is-hidden').forEach(function (item) {
            item.classList.remove('is-hidden');
        });

        root.querySelectorAll('input[name="transport_passenger_id[]"]').forEach(function (input) {
            input.checked = false;
        });

        var emptyHint = root.querySelector('[data-transport-group-passenger-empty]');
        var countHint = root.querySelector('[data-transport-group-passenger-count]');
        if (emptyHint) emptyHint.classList.add('hidden');
        if (countHint) countHint.classList.remove('hidden');
    }

    function openModal() {
        if (!modal || !openBtn) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('aria-hidden', 'false');
        openBtn.setAttribute('aria-expanded', 'true');
        var searchInput = document.getElementById('transport-group-passenger-search');
        if (searchInput) searchInput.focus();
    }

    function closeModal() {
        if (!modal || !openBtn) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('aria-hidden', 'true');
        openBtn.setAttribute('aria-expanded', 'false');
        resetPassengerPicker(getPassengerPickerPanel());
        openBtn.focus();
    }

    function refreshMemberModal(data) {
        if (memberModalBody && data.member_modal_html) {
            memberModalBody.innerHTML = data.member_modal_html;
            bindPassengerPickerSearch(getPassengerPickerPanel());
            return;
        }

        var pickerPanel = getPassengerPickerPanel();
        if (pickerPanel && data.passengers_picker_html) {
            pickerPanel.innerHTML = data.passengers_picker_html;
            bindPassengerPickerSearch(pickerPanel);
        }
    }

    if (memberModalBody) {
        bindPassengerPickerSearch(getPassengerPickerPanel());
    }

    if (openBtn && modal) {
        openBtn.addEventListener('click', openModal);
        modal.addEventListener('click', function (e) {
            if (e.target === modal || e.target.closest('[data-transport-group-add-members-close]')) {
                closeModal();
            }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
        });
    }

    @if($errors->has('transport_passenger_id') || $errors->has('valid_from'))
    openModal();
    @endif

    function showLiveFlash(message, type) {
        var existing = document.getElementById('transport-group-live-flash');
        if (existing) existing.remove();

        var alert = document.createElement('div');
        alert.id = 'transport-group-live-flash';
        alert.className = 'kt-alert kt-alert-' + (type || 'success') + ' mb-5 mt-5';
        alert.setAttribute('role', 'alert');
        alert.innerHTML = '<i class="ki-filled ki-' + (type === 'danger' ? 'cross-circle' : 'check-circle') + ' me-2"></i> ' + message;

        var container = document.querySelector('#content .kt-container-fixed.min-w-0');
        var nav = container ? container.querySelector('.contract-workspace-nav') : null;
        if (nav && nav.parentNode) {
            nav.parentNode.insertBefore(alert, nav.nextSibling);
        } else if (container) {
            container.insertBefore(alert, container.firstChild.nextSibling);
        }
    }

    function applyMemberChangePayload(data) {
        if (membersPanel && data.members_html) {
            membersPanel.innerHTML = data.members_html;
        }
        if (routePanel && data.route_html) {
            routePanel.innerHTML = data.route_html;
        }
        refreshMemberModal(data);
        if (membersTitle && typeof data.members_count === 'number') {
            membersTitle.textContent = 'Leden (' + data.members_count + ')';
            var countBadge = document.querySelector('.contract-workspace-nav__tab[aria-current="page"] .contract-workspace-nav__count');
            if (countBadge) countBadge.textContent = String(data.members_count);
        }
        if (data.success) {
            showLiveFlash(data.success, 'success');
        }
    }

    function memberFormErrorMessage(payload) {
        if (payload && payload.errors) {
            return Object.values(payload.errors).flat().join(' ');
        }

        return (payload && payload.message) ? payload.message : 'Opslaan mislukt.';
    }

    function submitMemberForm(form) {
        var submitBtn = form.querySelector('[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;

        return fetch(form.action, {
            method: (form.getAttribute('method') || 'POST').toUpperCase(),
            body: new FormData(form),
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    if (!response.ok) {
                        throw data;
                    }

                    return data;
                });
            })
            .then(function (data) {
                applyMemberChangePayload(data);
                if (form.id === 'transport-group-add-members-form') {
                    resetPassengerPicker(getPassengerPickerPanel());
                    closeModal();
                }
            })
            .catch(function (error) {
                showLiveFlash(memberFormErrorMessage(error), 'danger');
            })
            .finally(function () {
                if (submitBtn) submitBtn.disabled = false;
            });
    }

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (!/\/groepen\/\d+\/leden/.test(form.action)) return;

        var isDelete = form.querySelector('input[name="_method"][value="DELETE"]');
        if (isDelete && !form.adminConfirmAccepted) {
            event.preventDefault();
            if (typeof window.showAdminConfirm === 'function') {
                window.showAdminConfirm({
                    title: 'Passagier verwijderen',
                    message: 'Passagier uit deze groep halen?',
                    confirmLabel: 'Verwijderen'
                }).then(function (ok) {
                    if (!ok) {
                        return;
                    }
                    form.adminConfirmAccepted = true;
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                    queueMicrotask(function () { form.adminConfirmAccepted = false; });
                });
                return;
            }
            if (!window.confirm('Passagier uit deze groep halen?')) {
                return;
            }
        }

        if (form.id === 'transport-group-add-members-form') {
            var checkedPassengers = form.querySelectorAll('input[name="transport_passenger_id[]"]:checked');
            if (!checkedPassengers.length) {
                event.preventDefault();
                showLiveFlash('Selecteer minimaal één passagier.', 'danger');
                return;
            }
        }

        event.preventDefault();
        submitMemberForm(form);
    });
})();
</script>
@endcan
@endpush
