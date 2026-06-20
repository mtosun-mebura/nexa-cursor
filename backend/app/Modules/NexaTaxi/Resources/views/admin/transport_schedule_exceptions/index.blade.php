@extends('admin.layouts.app')

@section('title', 'Planning uitzonderingen')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <div>
            <h1 class="text-xl font-medium leading-none text-mono">Feestdagen &amp; uitzonderingen</h1>
            <p class="text-sm text-muted-foreground pt-2">Dagen waarop geen groepsritten worden gegenereerd.</p>
        </div>
        <a href="{{ route('admin.taxi.transport_planning.index') }}" class="kt-btn kt-btn-outline shrink-0">Naar planning</a>
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

    @can('rides.create')
    <div class="kt-card mb-5">
        <div class="kt-card-header">
            <h3 class="kt-card-title mb-0">Nieuwe uitzonderingsdag</h3>
        </div>
        <div class="kt-card-content p-4">
            <form method="POST" action="{{ route('admin.taxi.transport_schedule_exceptions.store') }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div class="w-fit max-w-full shrink-0">
                    <label class="text-sm text-secondary-foreground block mb-1">Datum</label>
                    @include('taxi::admin.transport_customers.partials.date-picker-input', [
                        'name' => 'exception_date',
                        'value' => old('exception_date'),
                        'required' => true,
                        'wrapperClass' => 'w-[10.5rem] shrink-0',
                    ])
                </div>
                <div class="w-fit min-w-[28rem] max-w-lg shrink-0">
                    <label class="text-sm text-secondary-foreground block mb-1">Omschrijving</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="kt-input w-full min-w-[28rem]" maxlength="200" placeholder="Bijv. Hemelvaartsdag" required>
                </div>
                <div class="w-fit min-w-[10rem] shrink-0">
                    <label class="text-sm text-secondary-foreground block mb-1">Scope</label>
                    <select name="transport_contract_id" class="kt-select min-w-[10rem] w-full">
                        <option value="">Hele bedrijf</option>
                        @foreach($contracts as $contract)
                            <option value="{{ $contract->id }}" @selected(old('transport_contract_id') == $contract->id)>{{ $contract->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="kt-btn kt-btn-sm kt-btn-primary shrink-0">Toevoegen</button>
            </form>
        </div>
    </div>
    @endcan

    <div class="kt-card kt-card-grid w-full min-w-0">
        <div class="kt-card-header">
            <h3 class="kt-card-title mb-0">Overzicht</h3>
        </div>
        <div class="kt-card-content p-0 min-w-0">
            <div class="kt-scrollable-x-auto admin-table-scroll-wrap transport-schedule-exceptions-table-wrap">
                <table id="transport-schedule-exceptions-table" class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full">
                    <thead>
                        <tr>
                            <th>Datum</th>
                            <th>Omschrijving</th>
                            <th>Scope</th>
                            <th>Status</th>
                            @can('rides.delete')
                            <th class="transport-schedule-exceptions-table__actions-col text-secondary-foreground font-normal text-center" data-label="Acties"></th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($exceptions as $exception)
                        <tr>
                            <td class="whitespace-nowrap">{{ $exception->exception_date?->format('d-m-Y') }}</td>
                            <td>{{ $exception->name }}</td>
                            <td class="text-muted-foreground">{{ $exception->contract?->name ?? 'Hele bedrijf' }}</td>
                            <td>
                                @if($exception->active)
                                    <span class="kt-badge kt-badge-success kt-badge-sm">Actief</span>
                                @else
                                    <span class="kt-badge kt-badge-secondary kt-badge-sm">Inactief</span>
                                @endif
                            </td>
                            @can('rides.delete')
                            <td class="transport-schedule-exceptions-table__actions-col">
                                <button type="button"
                                        class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost text-destructive"
                                        title="Verwijderen"
                                        aria-label="Verwijderen"
                                        data-schedule-exception-delete
                                        data-action="{{ route('admin.taxi.transport_schedule_exceptions.destroy', $exception->id) }}"
                                        data-label="{{ $exception->name }} ({{ $exception->exception_date?->format('d-m-Y') }})">
                                    <i class="ki-filled ki-trash"></i>
                                </button>
                            </td>
                            @endcan
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ auth()->user()->can('rides.delete') ? 5 : 4 }}" class="text-center text-muted-foreground py-6">Geen uitzonderingsdagen ingesteld.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($exceptions->hasPages())
            <div class="px-3 sm:px-5 py-4">{{ $exceptions->links() }}</div>
            @endif
        </div>
    </div>

    @can('rides.delete')
    <div id="schedule-exception-delete-modal"
         class="fixed inset-0 z-[100000] hidden items-center justify-center bg-black/60 backdrop-blur-sm px-4"
         role="dialog"
         aria-modal="true"
         aria-labelledby="schedule-exception-delete-modal-title">
        <div class="bg-background rounded-lg w-full max-w-md border border-border shadow-xl overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-border">
                <h3 id="schedule-exception-delete-modal-title" class="text-lg font-semibold text-foreground flex items-center gap-2 mb-0">
                    <i class="ki-filled ki-trash text-destructive"></i>
                    Uitzonderingsdag verwijderen
                </h3>
                <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0" data-schedule-exception-delete-close aria-label="Sluiten">
                    <i class="ki-filled ki-cross text-muted-foreground"></i>
                </button>
            </div>
            <div class="p-6">
                <p class="text-sm text-foreground mb-6">
                    Weet je zeker dat je <strong id="schedule-exception-delete-label"></strong> wilt verwijderen?
                </p>
                <div class="flex gap-2.5">
                    <button type="button" class="kt-btn kt-btn-outline flex-1 justify-center" data-schedule-exception-delete-close>
                        Annuleren
                    </button>
                    <button type="button" class="kt-btn kt-btn-danger flex-1 justify-center" id="schedule-exception-delete-confirm">
                        <i class="ki-filled ki-trash me-2"></i>
                        Verwijderen
                    </button>
                </div>
            </div>
        </div>
    </div>

    <form id="schedule-exception-delete-form" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>
    @endcan
</div>
@endsection

@push('scripts')
@can('rides.delete')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('schedule-exception-delete-modal');
    var form = document.getElementById('schedule-exception-delete-form');
    var labelEl = document.getElementById('schedule-exception-delete-label');
    var confirmBtn = document.getElementById('schedule-exception-delete-confirm');
    if (!modal || !form || !labelEl || !confirmBtn) return;

    var escHandler = null;

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        if (escHandler) {
            document.removeEventListener('keydown', escHandler, true);
            escHandler = null;
        }
    }

    function openModal(action, label) {
        form.action = action;
        labelEl.textContent = label;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        confirmBtn.focus();
        escHandler = function (e) {
            if (e.key === 'Escape') {
                e.preventDefault();
                closeModal();
            }
        };
        document.addEventListener('keydown', escHandler, true);
    }

    document.querySelectorAll('[data-schedule-exception-delete]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openModal(btn.getAttribute('data-action') || '', btn.getAttribute('data-label') || 'deze uitzonderingsdag');
        });
    });

    modal.querySelectorAll('[data-schedule-exception-delete-close]').forEach(function (btn) {
        btn.addEventListener('click', closeModal);
    });

    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });

    confirmBtn.addEventListener('click', function () {
        form.submit();
    });
});
</script>
@endcan
@endpush

@push('styles')
<style>
    #content #transport-schedule-exceptions-table .transport-schedule-exceptions-table__actions-col {
        width: 3rem !important;
        min-width: 3rem !important;
        max-width: 3rem !important;
        padding-inline: 0.25rem !important;
        text-align: center !important;
        vertical-align: middle !important;
        white-space: nowrap;
    }
</style>
@endpush
