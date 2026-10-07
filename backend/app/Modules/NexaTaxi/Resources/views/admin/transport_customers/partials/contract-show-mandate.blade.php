        {{-- SEPA-mandaat --}}
        @php
            $mandateSaved = $mandate && filled($mandate->iban);
            $mandateEditMode = ! $mandateSaved || $errors->hasAny([
                'account_holder', 'iban', 'bic', 'mandate_reference', 'status', 'signed_at',
            ]);
            $mandateStatusLabels = [
                'pending' => 'In behandeling',
                'active' => 'Actief',
                'revoked' => 'Ingetrokken',
            ];
        @endphp
        <div class="kt-card w-full min-w-0" id="transport-contract-mandate-card">
            <div class="kt-card-header flex items-center justify-between gap-2 px-5 py-5">
                <h3 class="kt-card-title mb-0">SEPA-mandaat (automatisch incasso)</h3>
                @can('rides.update')
                @if($mandateSaved)
                <button type="button"
                        id="transport-contract-mandate-edit-btn"
                        class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost {{ $mandateEditMode ? 'hidden' : '' }}"
                        aria-label="SEPA-mandaat bewerken">
                    <i class="ki-filled ki-pencil"></i>
                </button>
                @endif
                @endcan
            </div>
            <div class="kt-card-content p-0">
                @if($mandateSaved)
                <div id="transport-contract-mandate-view" class="px-3 sm:px-5 pb-3 min-w-0 {{ $mandateEditMode ? 'hidden' : '' }}">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground contract-detail-table w-full">
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">Rekeninghouder</td>
                            <td>{{ $mandate->account_holder }}</td>
                        </tr>
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">IBAN</td>
                            <td>{{ $mandate->iban }}</td>
                        </tr>
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">BIC</td>
                            <td>{{ $mandate->bic ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">Mandaatreferentie</td>
                            <td>{{ $mandate->mandate_reference ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">Status mandaat</td>
                            <td>
                                @if(($mandate->status ?? 'pending') === 'active')
                                    <span class="kt-badge kt-badge-success kt-badge-sm">{{ $mandateStatusLabels['active'] }}</span>
                                @elseif(($mandate->status ?? 'pending') === 'revoked')
                                    <span class="kt-badge kt-badge-secondary kt-badge-sm">{{ $mandateStatusLabels['revoked'] }}</span>
                                @else
                                    <span class="kt-badge kt-badge-warning kt-badge-sm">{{ $mandateStatusLabels['pending'] }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">Ondertekend op</td>
                            <td>{{ $mandate->signed_at?->format('d-m-Y') ?: '—' }}</td>
                        </tr>
                    </table>
                </div>
                @endif

                @can('rides.update')
                <form method="POST"
                      action="{{ route('admin.taxi.transport_customers.mandate_save', [$customer->id, $contract->id]) }}"
                      id="transport-contract-mandate-form"
                      class="{{ $mandateSaved && ! $mandateEditMode ? 'hidden' : '' }}">
                    @csrf
                    <div class="px-3 sm:px-5 pb-3 min-w-0">
                        <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground contract-detail-table wizard-onboarding-form-table w-full">
                            <tr>
                                <td class="contract-detail-table__label text-secondary-foreground font-normal">Rekeninghouder</td>
                                <td class="min-w-48 w-full">
                                    <input type="text" name="account_holder" value="{{ old('account_holder', optional($mandate)->account_holder) }}" class="kt-input w-full" maxlength="200" required>
                                </td>
                            </tr>
                            <tr>
                                <td class="contract-detail-table__label text-secondary-foreground font-normal">IBAN</td>
                                <td class="min-w-48 w-full">
                                    <input type="text"
                                           name="iban"
                                           value="{{ old('iban', optional($mandate)->iban) }}"
                                           class="kt-input w-full @error('iban') border-destructive @enderror"
                                           maxlength="64"
                                           placeholder="NL00 BANK 0000 0000 00"
                                           required>
                                    <div class="text-xs text-muted-foreground mt-1">Nederlands IBAN: 18 tekens (bijv. NL91 ABNA 0417 1643 00).</div>
                                    @error('iban')
                                        <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                    @enderror
                                </td>
                            </tr>
                            <tr>
                                <td class="contract-detail-table__label text-secondary-foreground font-normal">BIC</td>
                                <td>
                                    <input type="text" name="bic" value="{{ old('bic', optional($mandate)->bic) }}" class="kt-input w-full" maxlength="64">
                                </td>
                            </tr>
                            <tr>
                                <td class="contract-detail-table__label text-secondary-foreground font-normal">Mandaatreferentie</td>
                                <td>
                                    <input type="text" name="mandate_reference" value="{{ old('mandate_reference', optional($mandate)->mandate_reference) }}" class="kt-input w-full" maxlength="64">
                                </td>
                            </tr>
                            <tr>
                                <td class="contract-detail-table__label text-secondary-foreground font-normal">Status mandaat</td>
                                <td>
                                    <select name="status" class="kt-select w-full">
                                        <option value="pending" @selected(old('status', optional($mandate)->status ?? 'pending') === 'pending')>In behandeling</option>
                                        <option value="active" @selected(old('status', optional($mandate)->status) === 'active')>Actief</option>
                                        <option value="revoked" @selected(old('status', optional($mandate)->status) === 'revoked')>Ingetrokken</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class="contract-detail-table__label text-secondary-foreground font-normal">Ondertekend op</td>
                                <td>
                                    @include('taxi::admin.transport_customers.partials.date-picker-input', [
                                        'name' => 'signed_at',
                                        'value' => old('signed_at', optional($mandate)->signed_at?->format('Y-m-d') ?? ''),
                                    ])
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="px-3 sm:px-5 pb-5 flex justify-end gap-2">
                        @if($mandateSaved)
                        <button type="button" id="transport-contract-mandate-cancel-btn" class="kt-btn kt-btn-outline">Annuleren</button>
                        @endif
                        <button type="submit" class="kt-btn kt-btn-primary">Mandaat opslaan</button>
                    </div>
                </form>
                @elseif(! $mandateSaved)
                <div class="px-3 sm:px-5 pb-5 text-sm text-muted-foreground">
                    Nog geen SEPA-mandaat ingesteld.
                </div>
                @endcan
            </div>
        </div>
