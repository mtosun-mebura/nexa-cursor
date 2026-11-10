<div class="kt-card w-full min-w-0">
            <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
                <h3 class="kt-card-title mb-0">{{ __('Abonnementsgegevens') }}</h3>
            </div>
            <div class="kt-card-content p-0">
                <div class="px-3 sm:px-5 pb-3 min-w-0">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground contract-detail-table w-full">
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">{{ __('Naam') }}</td>
                            <td>{{ $contract->name }}</td>
                        </tr>
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">{{ __('Planningkleur') }}</td>
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
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">{{ __('Status') }}</td>
                            <td>
                                @if($contract->status === 'active')
                                    <span class="kt-badge kt-badge-success kt-badge-sm">{{ __('Actief') }}</span>
                                @elseif($contract->status === 'paused')
                                    <span class="kt-badge kt-badge-warning kt-badge-sm">{{ __('Gepauzeerd') }}</span>
                                @else
                                    <span class="kt-badge kt-badge-secondary kt-badge-sm">{{ __('Beëindigd') }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">{{ __('Periode') }}</td>
                            <td>
                                {{ $contract->start_date ? \Carbon\Carbon::parse($contract->start_date)->format('d-m-Y') : '—' }}
                                &rarr;
                                {{ $contract->end_date ? \Carbon\Carbon::parse($contract->end_date)->format('d-m-Y') : __('doorlopend') }}
                            </td>
                        </tr>
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">{{ __('Facturatiemodel') }}</td>
                            <td>
                                @if($contract->billing_model === 'fixed_monthly') {{ __('Vast maandbedrag') }}
                                @elseif($contract->billing_model === 'per_ride') {{ __('Per rit') }}
                                @else {{ __('Hybride') }} @endif
                            </td>
                        </tr>
                        @if(!is_null($contract->monthly_amount) && $contract->billing_model !== 'per_ride')
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">{{ __('Maandbedrag') }}</td>
                            <td>{{ __('€ :amount excl. BTW', ['amount' => number_format($contract->monthly_amount, 2, ',', '.')]) }}</td>
                        </tr>
                        @endif
                        @if(!is_null($contract->price_per_ride) && $contract->billing_model !== 'fixed_monthly')
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">{{ __('Prijs per rit') }}</td>
                            <td>{{ __('€ :amount excl. BTW', ['amount' => number_format($contract->price_per_ride, 2, ',', '.')]) }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">{{ __('BTW') }}</td>
                            <td>{{ $contract->tax_rate }}%</td>
                        </tr>
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">{{ __('Factuurdag') }}</td>
                            <td>{{ __(':daye van de maand', ['day' => $contract->invoice_day]) }}</td>
                        </tr>
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">{{ __('Betalingstermijn') }}</td>
                            <td>{{ __(':days dagen', ['days' => $contract->payment_terms_days]) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
