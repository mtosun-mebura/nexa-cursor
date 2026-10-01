@extends('admin.layouts.app')

@section('title', 'Platform settlements')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <div>
            <h1 class="text-xl font-medium leading-none text-mono mb-0">Platform settlements</h1>
            <p class="text-sm text-muted-foreground mt-2 mb-0">Marketplace &amp; network uitbetalingswachtrij</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.payment-flows.guide') }}" class="kt-btn kt-btn-outline kt-btn-sm">Uitleg</a>
            <form method="POST" action="{{ route('admin.payment-flows.settlements.process') }}">
                @csrf
                <button type="submit" class="kt-btn kt-btn-primary kt-btn-sm">Verwerk wachtrij nu</button>
            </form>
        </div>
    </div>

    <div class="kt-card w-full min-w-0 mb-5">
        <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
            <h2 class="kt-card-title mb-0">Filter</h2>
            <form method="GET" class="flex flex-wrap gap-2 items-center">
                <select name="status" class="kt-select admin-field-fit" data-kt-select="true" onchange="this.form.submit()">
                    <option value="">Alle statussen</option>
                    @foreach($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected($statusFilter === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="kt-card-content p-0">
            <div class="admin-table-scroll-wrap px-3 sm:px-5 pb-3">
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground w-full">
                    <thead>
                        <tr>
                            <th class="text-secondary-foreground font-normal" data-label="ID">ID</th>
                            <th class="text-secondary-foreground font-normal" data-label="Rit">Rit</th>
                            <th class="text-secondary-foreground font-normal" data-label="Model">Model</th>
                            <th class="text-secondary-foreground font-normal" data-label="Bruto">Bruto</th>
                            <th class="text-secondary-foreground font-normal" data-label="Fee">Fee</th>
                            <th class="text-secondary-foreground font-normal" data-label="Netto">Netto / split</th>
                            <th class="text-secondary-foreground font-normal" data-label="Status">Status</th>
                            <th class="text-secondary-foreground font-normal" data-label="Acties">Acties</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($settlements as $row)
                            <tr>
                                <td data-label="ID">#{{ $row->id }}</td>
                                <td data-label="Rit">#{{ $row->ride_request_id }}</td>
                                <td data-label="Model">
                                    <span class="kt-badge kt-badge-outline kt-badge-sm">{{ $row->model }}</span>
                                </td>
                                <td data-label="Bruto">€{{ number_format((float) $row->gross_amount, 2, ',', '.') }}</td>
                                <td data-label="Fee">
                                    €{{ number_format((float) $row->nexa_fee_amount, 2, ',', '.') }}
                                    <span class="text-xs text-muted-foreground">({{ (int) $row->nexa_fee_percent }}%)</span>
                                </td>
                                <td data-label="Netto / split">
                                    @if($row->model === 'network')
                                        <span class="block text-xs">A €{{ number_format((float) $row->owner_share_amount, 2, ',', '.') }}</span>
                                        <span class="block text-xs">B €{{ number_format((float) $row->fulfiller_share_amount, 2, ',', '.') }}</span>
                                    @else
                                        €{{ number_format((float) $row->net_amount, 2, ',', '.') }}
                                    @endif
                                </td>
                                <td data-label="Status">
                                    <span class="kt-badge kt-badge-light kt-badge-sm">{{ $row->statusLabel() }}</span>
                                    @if($row->last_error)
                                        <p class="text-xs text-destructive mt-1 mb-0 max-w-xs">{{ \Illuminate\Support\Str::limit($row->last_error, 120) }}</p>
                                    @endif
                                </td>
                                <td data-label="Acties">
                                    <div class="flex flex-wrap gap-1">
                                        @if($row->status !== 'paid_out')
                                            <form method="POST" action="{{ route('admin.payment-flows.settlements.retry', $row) }}">
                                                @csrf
                                                <button type="submit" class="kt-btn kt-btn-outline kt-btn-xs">Opnieuw</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.payment-flows.settlements.force-paid', $row) }}" onsubmit="return confirm('Alleen als de overboeking buiten NEXA al is gedaan. Doorgaan?');">
                                                @csrf
                                                <button type="submit" class="kt-btn kt-btn-ghost kt-btn-xs">Forceer betaald</button>
                                            </form>
                                        @else
                                            <span class="text-xs text-muted-foreground">—</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted-foreground p-5">Geen settlements.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($settlements->hasPages())
                <div class="px-5 py-4">{{ $settlements->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
