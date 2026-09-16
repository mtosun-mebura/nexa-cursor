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
            <form method="POST" action="{{ route('admin.taxi.transport_schedule_exceptions.store') }}" class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-end gap-3 w-full min-w-0">
                @csrf
                <div class="w-full min-w-0 sm:w-fit shrink-0">
                    <label class="text-sm text-secondary-foreground block mb-1">Datum</label>
                    @include('taxi::admin.transport_customers.partials.date-picker-input', [
                        'name' => 'exception_date',
                        'value' => old('exception_date'),
                        'required' => true,
                        'wrapperClass' => 'w-full sm:w-[10.5rem] min-w-0 shrink-0',
                    ])
                </div>
                <div class="w-full min-w-0 max-w-lg">
                    <label class="text-sm text-secondary-foreground block mb-1">Omschrijving</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="kt-input w-full min-w-0 max-w-full" maxlength="200" placeholder="Bijv. Hemelvaartsdag" required>
                </div>
                <div class="w-full min-w-0 sm:w-fit sm:min-w-[10rem]">
                    <label class="text-sm text-secondary-foreground block mb-1">Scope</label>
                    <select name="transport_contract_id" class="kt-select w-full min-w-0">
                        <option value="">Hele bedrijf</option>
                        @foreach($contracts as $contract)
                            <option value="{{ $contract->id }}" @selected(old('transport_contract_id') == $contract->id)>{{ $contract->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="kt-btn kt-btn-sm kt-btn-primary w-full sm:w-auto shrink-0">Toevoegen</button>
            </form>
        </div>
    </div>
    @endcan

    <div class="kt-card kt-card-grid w-full min-w-0">
        <div class="kt-card-header">
            <h3 class="kt-card-title mb-0">Overzicht</h3>
        </div>
        <div class="kt-card-content p-0 min-w-0">
            @if($exceptions->isEmpty())
                <p class="p-5 text-sm text-muted-foreground mb-0">Geen uitzonderingsdagen ingesteld.</p>
            @else
            <div class="kt-scrollable-x-auto admin-table-scroll-wrap transport-schedule-exceptions-table-wrap">
                <table id="transport-schedule-exceptions-table" class="kt-table kt-table-border align-middle text-sm w-full min-w-0">
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
                        @foreach($exceptions as $exception)
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
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
            @if($exceptions->hasPages())
            <div class="px-3 sm:px-5 py-4">{{ $exceptions->links() }}</div>
            @endif
        </div>
    </div>

    @can('rides.delete')
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
    var form = document.getElementById('schedule-exception-delete-form');
    if (!form) return;

    document.querySelectorAll('[data-schedule-exception-delete]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var label = btn.getAttribute('data-label') || 'deze uitzonderingsdag';
            var action = btn.getAttribute('data-action') || '';
            var message = 'Weet je zeker dat je ' + label + ' wilt verwijderen?';
            var runDelete = function () {
                form.action = action;
                form.submit();
            };
            if (typeof window.showAdminConfirm === 'function') {
                window.showAdminConfirm({
                    title: 'Uitzonderingsdag verwijderen',
                    message: message,
                    confirmLabel: 'Verwijderen'
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
