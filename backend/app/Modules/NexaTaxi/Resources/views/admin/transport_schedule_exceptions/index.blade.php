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
            <form method="POST" action="{{ route('admin.taxi.transport_schedule_exceptions.store') }}" class="grid gap-3 md:grid-cols-2 lg:grid-cols-4 items-end">
                @csrf
                <div>
                    <label class="text-sm text-secondary-foreground block mb-1">Datum</label>
                    @include('taxi::admin.transport_customers.partials.date-picker-input', [
                        'name' => 'exception_date',
                        'value' => old('exception_date'),
                        'required' => true,
                        'wrapperClass' => 'w-full',
                    ])
                </div>
                <div>
                    <label class="text-sm text-secondary-foreground block mb-1">Omschrijving</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="kt-input w-full" maxlength="200" placeholder="Bijv. Hemelvaartsdag" required>
                </div>
                <div>
                    <label class="text-sm text-secondary-foreground block mb-1">Scope</label>
                    <select name="transport_contract_id" class="kt-select w-full">
                        <option value="">Hele bedrijf</option>
                        @foreach($contracts as $contract)
                            <option value="{{ $contract->id }}" @selected(old('transport_contract_id') == $contract->id)>{{ $contract->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="kt-btn kt-btn-primary">Toevoegen</button>
            </form>
        </div>
    </div>
    @endcan

    <div class="kt-card kt-card-grid w-full min-w-0">
        <div class="kt-card-header">
            <h3 class="kt-card-title mb-0">Overzicht</h3>
        </div>
        <div class="kt-card-content p-0 min-w-0">
            <div class="kt-scrollable-x-auto admin-table-scroll-wrap">
                <table class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full">
                    <thead>
                        <tr>
                            <th>Datum</th>
                            <th>Omschrijving</th>
                            <th>Scope</th>
                            <th>Status</th>
                            <th></th>
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
                            <td>
                                @can('rides.delete')
                                <form method="POST" action="{{ route('admin.taxi.transport_schedule_exceptions.destroy', $exception->id) }}" onsubmit="return confirm('Uitzonderingsdag verwijderen?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="kt-btn kt-btn-xs kt-btn-outline text-destructive">Verwijderen</button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted-foreground py-6">Geen uitzonderingsdagen ingesteld.</td>
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
</div>
@endsection
