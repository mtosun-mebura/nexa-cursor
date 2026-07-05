@extends('admin.layouts.app')

@section('title', $group->name)

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <div>
            <h1 class="text-xl font-medium leading-none text-mono">{{ $group->name }}</h1>
            <p class="text-sm text-muted-foreground pt-2">{{ $contract->name }} · {{ $customer->name }}</p>
            <div class="pt-3 flex flex-wrap gap-2">
                <a href="{{ $backUrl }}" class="kt-btn kt-btn-outline">
                    <i class="ki-filled ki-arrow-left me-2"></i>
                    Terug
                </a>
            </div>
        </div>
        @can('rides.update')
        <a href="{{ transport_admin_url_with_return(route('admin.taxi.transport_groups.edit', [$customer->id, $contract->id, $group->id]), url()->full()) }}" class="kt-btn kt-btn-outline shrink-0">Bewerken</a>
        @endcan
    </div>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5" role="alert">
            <i class="ki-filled ki-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="kt-alert kt-alert-danger mb-5" role="alert">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-5 lg:gap-7.5">

        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header"><h3 class="kt-card-title mb-0">Groepsgegevens</h3></div>
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
                            <td class="text-secondary-foreground font-medium">Aankomsttijd</td>
                            <td>{{ substr($group->destination_arrival_time, 0, 5) }}</td>
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

        <div class="kt-card kt-card-grid w-full min-w-0">
            <div class="kt-card-header flex flex-wrap items-center justify-between gap-2">
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

        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header flex flex-wrap items-center justify-between gap-2">
                <h3 class="kt-card-title mb-0">Route</h3>
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

    </div>
</div>

@can('rides.update')
<div id="transport-group-add-members-modal"
     class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4"
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
        background: color-mix(in oklab, var(--muted) 18%, transparent);
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
</style>
@endpush

@push('scripts')
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
        alert.className = 'kt-alert kt-alert-' + (type || 'success') + ' mb-5';
        alert.setAttribute('role', 'alert');
        alert.innerHTML = '<i class="ki-filled ki-' + (type === 'danger' ? 'cross-circle' : 'check-circle') + ' me-2"></i> ' + message;

        var pageHeader = document.querySelector('#content .kt-container-fixed.min-w-0 > .flex.flex-wrap.items-center.justify-between');
        if (pageHeader && pageHeader.parentNode) {
            pageHeader.parentNode.insertBefore(alert, pageHeader.nextSibling);
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
        if (isDelete && !window.confirm('Passagier uit deze groep halen?')) {
            event.preventDefault();
            return;
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
