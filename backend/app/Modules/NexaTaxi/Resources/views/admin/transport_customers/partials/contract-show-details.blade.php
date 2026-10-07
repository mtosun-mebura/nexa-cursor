<div class="kt-card w-full min-w-0">
            <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
                <h3 class="kt-card-title mb-0">Abonnementsgegevens</h3>
            </div>
            <div class="kt-card-content p-0">
                <div class="px-3 sm:px-5 pb-3 min-w-0">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground contract-detail-table w-full">
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">Naam</td>
                            <td>{{ $contract->name }}</td>
                        </tr>
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">Planningkleur</td>
                            <td>
                                <span class="inline-flex items-center gap-2">
                                    <span
                                        class="inline-block h-4 w-4 rounded border"
                                        style="background-color: {{ $contract->planningColorHex() }}; border-color: {{ $contract->planningColorHex() }};"
                                    ></span>
                                    {{ $contract->planningColorHex() }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">Status</td>
                            <td>
                                @if($contract->status === 'active')
                                    <span class="kt-badge kt-badge-success kt-badge-sm">Actief</span>
                                @elseif($contract->status === 'paused')
                                    <span class="kt-badge kt-badge-warning kt-badge-sm">Gepauzeerd</span>
                                @else
                                    <span class="kt-badge kt-badge-secondary kt-badge-sm">Beëindigd</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">Periode</td>
                            <td>
                                {{ $contract->start_date ? \Carbon\Carbon::parse($contract->start_date)->format('d-m-Y') : '—' }}
                                &rarr;
                                {{ $contract->end_date ? \Carbon\Carbon::parse($contract->end_date)->format('d-m-Y') : 'doorlopend' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">Facturatiemodel</td>
                            <td>
                                @if($contract->billing_model === 'fixed_monthly') Vast maandbedrag
                                @elseif($contract->billing_model === 'per_ride') Per rit
                                @else Hybride @endif
                            </td>
                        </tr>
                        @if(!is_null($contract->monthly_amount) && $contract->billing_model !== 'per_ride')
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">Maandbedrag</td>
                            <td>&euro; {{ number_format($contract->monthly_amount, 2, ',', '.') }} excl. BTW</td>
                        </tr>
                        @endif
                        @if(!is_null($contract->price_per_ride) && $contract->billing_model !== 'fixed_monthly')
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">Prijs per rit</td>
                            <td>&euro; {{ number_format($contract->price_per_ride, 2, ',', '.') }} excl. BTW</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">BTW</td>
                            <td>{{ $contract->tax_rate }}%</td>
                        </tr>
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">Factuurdag</td>
                            <td>{{ $contract->invoice_day }}e van de maand</td>
                        </tr>
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">Betalingstermijn</td>
                            <td>{{ $contract->payment_terms_days }} dagen</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
