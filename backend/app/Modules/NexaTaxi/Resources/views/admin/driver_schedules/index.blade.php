@extends('admin.layouts.app')

@section('title', 'Chauffeurplanning')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="admin-calendar-toolbar">
        <div class="admin-calendar-toolbar__top">
            <div class="min-w-0">
                <h1 class="admin-calendar-toolbar__title">Chauffeurplanning</h1>
                <p class="admin-calendar-toolbar__subtitle">
                    Week {{ $weekStart->locale('nl')->translatedFormat('d M') }} – {{ $weekEnd->locale('nl')->translatedFormat('d M Y') }}
                </p>
            </div>
            <div class="admin-calendar-toolbar__actions">
                <a href="{{ route('admin.taxi.driver_schedules.index', array_filter(['week' => $prevWeek, 'driver_id' => $driverFilter, 'vehicle_id' => $vehicleFilter])) }}" class="kt-btn kt-btn-outline" aria-label="Vorige week">← Vorige week</a>
                <a
                    href="{{ route('admin.taxi.driver_schedules.index', array_filter(['week' => $todayWeek, 'driver_id' => $driverFilter, 'vehicle_id' => $vehicleFilter])) }}"
                    class="kt-btn {{ $isCurrentWeek ? 'kt-btn-primary' : 'kt-btn-outline' }}"
                    @if($isCurrentWeek) aria-current="date" @endif
                >Vandaag</a>
                <a href="{{ route('admin.taxi.driver_schedules.index', array_filter(['week' => $nextWeek, 'driver_id' => $driverFilter, 'vehicle_id' => $vehicleFilter])) }}" class="kt-btn kt-btn-outline" aria-label="Volgende week">Volgende week →</a>
                @if($canManage)
                    <a href="{{ route('admin.taxi.driver_schedules.create') }}" class="kt-btn kt-btn-primary">Nieuwe dienst</a>
                @endif
            </div>
        </div>
        <form method="GET" action="{{ route('admin.taxi.driver_schedules.index') }}" class="admin-calendar-toolbar__filters" id="driver-schedule-filters">
            <label class="sr-only" for="driver-schedule-week-filter">Week</label>
            @include('taxi::admin.transport_customers.partials.date-picker-input', [
                'name' => 'week',
                'id' => 'driver-schedule-week-filter',
                'value' => $weekStart->format('Y-m-d'),
                'wrapperClass' => 'admin-calendar-toolbar__date w-40',
                'placeholder' => 'Selecteer week',
            ])
            <select name="driver_id" class="kt-select kt-select-sm" aria-label="Chauffeur">
                <option value="">Alle chauffeurs</option>
                @foreach($chauffeurs as $chauffeur)
                    <option value="{{ $chauffeur->id }}" @selected((int) $driverFilter === (int) $chauffeur->id)>
                        {{ trim($chauffeur->first_name.' '.$chauffeur->last_name) }}
                    </option>
                @endforeach
            </select>
            <select name="vehicle_id" class="kt-select kt-select-sm" aria-label="Voertuig">
                <option value="">Alle voertuigen</option>
                @foreach($vehicles as $vehicle)
                    <option value="{{ $vehicle->id }}" @selected((int) $vehicleFilter === (int) $vehicle->id)>
                        {{ $vehicle->fleetLabel() }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    @if($superAdminNeedsTenant)
        <div class="kt-alert kt-alert-light border border-input mb-5 p-4" role="status">
            Selecteer een tenant om chauffeurs en voertuigen te filteren en nieuwe diensten in te plannen.
        </div>
    @endif

    <div class="grid gap-4 lg:grid-cols-7" id="driver-schedule-week-grid">
        @php
            $scheduleDetails = [];
        @endphp
        @foreach($days as $day)
        <div class="kt-card min-w-0{{ $day['isToday'] ? ' border-2 border-primary' : '' }}">
            <div class="kt-card-header driver-schedule-day-header flex flex-nowrap items-center justify-between gap-1 px-3 py-3">
                <h3 class="kt-card-title text-sm mb-0 min-w-0 truncate{{ $day['isToday'] ? ' text-primary' : '' }}">{{ $day['label'] }}</h3>
                @if($canManage)
                    <a href="{{ route('admin.taxi.driver_schedules.create', ['date' => $day['date']->format('Y-m-d')]) }}"
                       class="kt-btn kt-btn-sm kt-btn-ghost shrink-0 px-1.5"
                       aria-label="Dienst toevoegen op {{ $day['label'] }}">+</a>
                @endif
            </div>
            <div class="kt-card-content p-5 space-y-2 min-h-32">
                @forelse($day['items'] as $item)
                    @php
                        $scheduleDetails[(string) $item['id']] = $item;
                    @endphp
                    <button
                        type="button"
                        class="driver-schedule-card w-full text-left rounded border border-input px-3 py-2 text-xs cursor-pointer transition hover:brightness-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                        data-driver-schedule="{{ $item['id'] }}"
                        aria-haspopup="dialog"
                    >
                        <div class="font-medium text-foreground">{{ $item['driver'] }}</div>
                        <div class="text-muted-foreground">{{ $item['time'] }}</div>
                        <div class="text-muted-foreground">{{ $item['vehicle'] }}</div>
                    </button>
                @empty
                    <p class="text-xs text-muted-foreground mb-0">Geen diensten</p>
                @endforelse
            </div>
        </div>
        @endforeach
    </div>
</div>

<script type="application/json" id="driver-schedule-details-data">@json($scheduleDetails ?? new \stdClass())</script>

<div id="driver-schedule-modal"
     class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-900/45 backdrop-blur-md p-4"
     role="dialog"
     aria-modal="true"
     aria-labelledby="driver-schedule-modal-title"
     aria-hidden="true">
    <div class="w-full max-w-lg max-h-[min(90vh,40rem)] flex flex-col rounded-xl border border-input bg-white dark:bg-[#0b0f19] shadow-xl overflow-hidden">
        <div class="flex items-start justify-between gap-3 border-b border-input px-5 py-5 shrink-0">
            <div class="min-w-0">
                <h3 id="driver-schedule-modal-title" class="text-lg font-semibold text-foreground mb-0 break-words" data-schedule-modal-title>Dienst</h3>
            </div>
            <button type="button"
                    class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0"
                    data-schedule-modal-close
                    aria-label="Sluiten">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>
        <div class="flex-1 min-h-0 overflow-y-auto px-5 py-5 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <div>
                    <div class="text-xs text-muted-foreground mb-0.5">Datum</div>
                    <div class="font-medium text-foreground" data-schedule-modal-date></div>
                </div>
                <div>
                    <div class="text-xs text-muted-foreground mb-0.5">Tijd</div>
                    <div class="font-medium text-foreground" data-schedule-modal-time></div>
                </div>
                <div>
                    <div class="text-xs text-muted-foreground mb-0.5">Chauffeur</div>
                    <div class="font-medium text-foreground" data-schedule-modal-driver></div>
                </div>
                <div>
                    <div class="text-xs text-muted-foreground mb-0.5">Voertuig</div>
                    <div class="font-medium text-foreground" data-schedule-modal-vehicle></div>
                </div>
            </div>
            <div>
                <div class="text-xs text-muted-foreground mb-0.5">Notitie</div>
                <div class="text-sm text-foreground whitespace-pre-wrap" data-schedule-modal-notes></div>
            </div>
        </div>
        <div class="flex flex-wrap items-center justify-end gap-2 border-t border-input px-5 py-5 shrink-0">
            <button type="button" class="kt-btn kt-btn-outline" data-schedule-modal-close>Sluiten</button>
            @if($canManage)
                <form method="POST" class="hidden" data-schedule-modal-delete-form>
                    @csrf
                    @method('DELETE')
                </form>
                <button type="button" class="kt-btn kt-btn-outline text-destructive" data-schedule-modal-delete>Verwijderen</button>
                <a href="#" class="kt-btn kt-btn-primary hidden" data-schedule-modal-edit>Bewerken</a>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('driver-schedule-modal');
    if (!modal) {
        return;
    }

    var details = {};
    var detailsNode = document.getElementById('driver-schedule-details-data');
    if (detailsNode) {
        try {
            details = JSON.parse(detailsNode.textContent || '{}') || {};
        } catch (err) {
            details = {};
        }
    }

    var titleEl = modal.querySelector('[data-schedule-modal-title]');
    var dateEl = modal.querySelector('[data-schedule-modal-date]');
    var timeEl = modal.querySelector('[data-schedule-modal-time]');
    var driverEl = modal.querySelector('[data-schedule-modal-driver]');
    var vehicleEl = modal.querySelector('[data-schedule-modal-vehicle]');
    var notesEl = modal.querySelector('[data-schedule-modal-notes]');
    var editEl = modal.querySelector('[data-schedule-modal-edit]');
    var deleteBtn = modal.querySelector('[data-schedule-modal-delete]');
    var deleteForm = modal.querySelector('[data-schedule-modal-delete-form]');
    var lastFocus = null;
    var currentDetail = null;

    function openModal(detail) {
        lastFocus = document.activeElement;
        currentDetail = detail || {};
        titleEl.textContent = currentDetail.driver || 'Dienst';
        dateEl.textContent = currentDetail.date || '—';
        timeEl.textContent = currentDetail.time || '—';
        driverEl.textContent = currentDetail.driver || '—';
        vehicleEl.textContent = currentDetail.vehicle || '—';
        notesEl.textContent = currentDetail.notes || '—';

        if (editEl) {
            if (currentDetail.edit_url) {
                editEl.href = currentDetail.edit_url;
                editEl.classList.remove('hidden');
            } else {
                editEl.href = '#';
                editEl.classList.add('hidden');
            }
        }
        if (deleteForm && currentDetail.delete_url) {
            deleteForm.action = currentDetail.delete_url;
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
        var closeBtn = modal.querySelector('[data-schedule-modal-close]');
        if (closeBtn) {
            closeBtn.focus();
        }
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
        if (lastFocus && typeof lastFocus.focus === 'function') {
            lastFocus.focus();
        }
    }

    document.querySelectorAll('[data-driver-schedule]').forEach(function (card) {
        card.addEventListener('click', function () {
            var id = String(card.getAttribute('data-driver-schedule') || '');
            openModal(details[id] || {});
        });
    });

    modal.querySelectorAll('[data-schedule-modal-close]').forEach(function (btn) {
        btn.addEventListener('click', closeModal);
    });

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    if (deleteBtn && deleteForm) {
        deleteBtn.addEventListener('click', async function () {
            var ok = true;
            if (typeof window.showAdminConfirm === 'function') {
                ok = await window.showAdminConfirm({
                    title: 'Dienst verwijderen',
                    message: 'Weet je zeker dat je deze ingeplande dienst wilt verwijderen?',
                    confirmLabel: 'Verwijderen'
                });
            } else {
                ok = window.confirm('Weet je zeker dat je deze ingeplande dienst wilt verwijderen?');
            }
            if (ok) {
                deleteForm.submit();
            }
        });
    }
});
</script>
@endpush
