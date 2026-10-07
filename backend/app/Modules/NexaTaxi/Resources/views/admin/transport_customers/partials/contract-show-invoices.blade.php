        {{-- Facturen --}}
        <div id="transport-contract-invoices-card" class="kt-card kt-card-grid w-full min-w-0">
            <div class="kt-card-header flex flex-wrap items-center justify-between gap-2 px-5 py-5">
                <h3 class="kt-card-title mb-0">Facturen ({{ $contractInvoices->count() }})</h3>
                <div class="flex gap-2 shrink-0">
                    @if($contractInvoices->isNotEmpty())
                    <a href="{{ route('admin.taxi.transport_contract_invoices.export', [$customer->id, $contract->id]) }}" class="kt-btn kt-btn-sm kt-btn-outline">
                        Export CSV
                    </a>
                    @endif
                </div>
            </div>
            <div class="kt-card-content p-0 min-w-0">
                @can('rides.update')
                @php
                    $invoicePeriodValue = old('period', $defaultInvoicePeriod);
                    if (! is_string($invoicePeriodValue) || ! preg_match('/^\d{4}-\d{2}$/', $invoicePeriodValue)) {
                        $invoicePeriodValue = $defaultInvoicePeriod;
                    }
                    [$invoicePeriodYear, $invoicePeriodMonth] = array_map('intval', explode('-', $invoicePeriodValue));
                    $invoicePeriodYearOptions = range(now()->year - 3, now()->year + 1);
                @endphp
                <form method="POST"
                      action="{{ route('admin.taxi.transport_contract_invoices.generate', [$customer->id, $contract->id]) }}"
                      id="transport-contract-invoice-generate-form"
                      class="px-3 sm:px-5 py-4 border-b border-border flex flex-wrap items-end gap-3">
                    @csrf
                    <div>
                        <label class="text-sm text-secondary-foreground block mb-1" for="contract-invoice-period-month">Periode</label>
                        <div class="flex items-center gap-2">
                            <select id="contract-invoice-period-month" class="kt-select w-40" required aria-label="Maand">
                                @foreach(range(1, 12) as $monthNum)
                                <option value="{{ sprintf('%02d', $monthNum) }}" @selected($monthNum === $invoicePeriodMonth)>
                                    {{ \Carbon\Carbon::create(2000, $monthNum, 1)->locale('nl')->translatedFormat('F') }}
                                </option>
                                @endforeach
                            </select>
                            <select id="contract-invoice-period-year" class="kt-select w-28" required aria-label="Jaar">
                                @foreach($invoicePeriodYearOptions as $yearOption)
                                <option value="{{ $yearOption }}" @selected($yearOption === $invoicePeriodYear)>{{ $yearOption }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="period" id="contract-invoice-period" value="{{ $invoicePeriodValue }}">
                        </div>
                    </div>
                    <label class="kt-label flex items-center gap-2 text-sm shrink-0 mb-0" for="contract-invoice-send-email">
                        <input type="checkbox" name="send_email" id="contract-invoice-send-email" value="1" class="kt-switch kt-switch-sm shrink-0" @checked(old('send_email'))>
                        <span>Direct verzenden per e-mail</span>
                    </label>
                    <button type="submit" class="kt-btn kt-btn-primary ml-auto shrink-0">Maandfactuur genereren</button>
                </form>
                @endcan
                <div class="kt-scrollable-x-auto admin-table-scroll-wrap contract-invoices-table-wrap">
                    <table id="transport-contract-invoices-table" class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full">
                        <thead>
                            <tr>
                                <th data-label="Nummer">Nummer</th>
                                <th data-label="Periode">Periode</th>
                                <th data-label="Datum">Datum</th>
                                <th data-label="Totaal">Totaal</th>
                                <th data-label="Status">Status</th>
                                <th class="contract-invoices-table__actions-col text-secondary-foreground font-normal text-center" data-label="Acties">Acties</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($contractInvoices as $invoice)
                            <tr>
                                <td><span class="font-medium text-foreground">{{ $invoice->invoice_number }}</span></td>
                                <td class="text-muted-foreground">{{ $invoice->billing_period }}</td>
                                <td class="text-muted-foreground whitespace-nowrap">{{ $invoice->invoice_date?->format('d-m-Y') }}</td>
                                <td class="text-muted-foreground">&euro; {{ number_format($invoice->total_amount, 2, ',', '.') }}</td>
                                <td>
                                    @if($invoice->status === 'paid')
                                        <span class="kt-badge kt-badge-success kt-badge-sm">Betaald</span>
                                    @elseif($invoice->status === 'sent')
                                        <span class="kt-badge kt-badge-light kt-badge-sm">Verzonden</span>
                                    @elseif($invoice->status === 'draft')
                                        <span class="kt-badge kt-badge-secondary kt-badge-sm">Concept</span>
                                    @else
                                        <span class="kt-badge kt-badge-secondary kt-badge-sm">{{ ucfirst($invoice->status) }}</span>
                                    @endif
                                </td>
                                <td class="contract-invoices-table__actions-col" data-no-row-link onclick="event.stopPropagation();">
                                    <div class="kt-menu flex justify-center" data-kt-menu="true">
                                        <div class="kt-menu-item"
                                             data-kt-menu-item-offset="0, 10px"
                                             data-kt-menu-item-placement="bottom-end"
                                             data-kt-menu-item-placement-rtl="bottom-start"
                                             data-kt-menu-item-toggle="dropdown"
                                             data-kt-menu-item-trigger="click">
                                            <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" type="button" aria-label="Acties">
                                                <svg class="w-5 h-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z"/>
                                                </svg>
                                            </button>
                                            <div class="kt-menu-dropdown kt-menu-default w-[175px] min-w-[175px]" data-kt-menu-dismiss="true">
                                                <div class="kt-menu-item">
                                                    <a class="kt-menu-link" href="{{ route('admin.taxi.transport_contract_invoices.pdf', [$customer->id, $contract->id, $invoice->id]) }}" target="_blank" rel="noopener">
                                                        <span class="kt-menu-icon">
                                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                                                        </span>
                                                        <span class="kt-menu-title">PDF</span>
                                                    </a>
                                                </div>
                                                @can('rides.update')
                                                @if($invoice->status !== 'paid')
                                                <div class="kt-menu-item">
                                                    <form method="POST" action="{{ route('admin.taxi.transport_contract_invoices.mark_paid', [$customer->id, $contract->id, $invoice->id]) }}" class="contents">
                                                        @csrf
                                                        <button type="submit" class="kt-menu-link w-full text-left border-0 bg-transparent">
                                                            <span class="kt-menu-icon">
                                                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                                            </span>
                                                            <span class="kt-menu-title">Betaald</span>
                                                        </button>
                                                    </form>
                                                </div>
                                                @endif
                                                @if($invoice->status !== 'sent' && $invoice->status !== 'paid')
                                                <div class="kt-menu-item">
                                                    <form method="POST" action="{{ route('admin.taxi.transport_contract_invoices.send', [$customer->id, $contract->id, $invoice->id]) }}" class="contents">
                                                        @csrf
                                                        <button type="submit" class="kt-menu-link w-full text-left border-0 bg-transparent">
                                                            <span class="kt-menu-icon">
                                                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg>
                                                            </span>
                                                            <span class="kt-menu-title">Verzenden</span>
                                                        </button>
                                                    </form>
                                                </div>
                                                @endif
                                                @if($invoice->status === 'draft')
                                                <div class="kt-menu-separator"></div>
                                                <div class="kt-menu-item">
                                                    <form method="POST" action="{{ route('admin.taxi.transport_contract_invoices.destroy', [$customer->id, $contract->id, $invoice->id]) }}" class="contents" onsubmit="return confirm('Conceptfactuur verwijderen? Daarna kun je opnieuw genereren met de bijgewerkte prijs.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="kt-menu-link w-full text-left text-danger border-0 bg-transparent">
                                                            <span class="kt-menu-icon">
                                                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                                            </span>
                                                            <span class="kt-menu-title">Verwijderen</span>
                                                        </button>
                                                    </form>
                                                </div>
                                                @endif
                                                @endcan
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted-foreground py-6">Nog geen facturen voor dit abonnement.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
