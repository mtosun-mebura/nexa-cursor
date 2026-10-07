@extends('admin.layouts.app')

@section('title', 'Payout onboarding')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <div>
            <h1 class="text-xl font-medium leading-none text-mono mb-1">Payout onboarding</h1>
            <p class="text-sm text-muted-foreground mb-0">Bij tenant-aanmaak wordt automatisch een payout-identiteit aangemaakt. Vul alleen de bankrekening in; we bewaren uitsluitend *** + laatste 4 cijfers.</p>
        </div>
        @if(auth()->user()->hasRole('super-admin'))
            <a href="{{ route('admin.payment-providers.index') }}" class="kt-btn kt-btn-outline shrink-0">Naar betalingsproviders</a>
        @endif
    </div>

    @if(!$company)
        <div class="kt-card">
            <div class="kt-card-content p-5">
                <p class="text-sm text-muted-foreground mb-0">Selecteer eerst een tenant om payout-onboarding te beheren.</p>
            </div>
        </div>
    @else
        <div class="grid gap-5 lg:gap-7.5">
            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
                    <h3 class="kt-card-title mb-0">{{ $company->name }}</h3>
                    <span class="text-xs text-muted-foreground">Settlement party: taxibedrijf (rechtspersoon)</span>
                </div>
                <div class="kt-card-content p-5 space-y-4">
                    @if($payload)
                        <div class="px-0 min-w-0">
                            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground w-full">
                                <tr>
                                    <td class="min-w-56 text-secondary-foreground font-normal">Status</td>
                                    <td class="min-w-48 w-full font-medium text-foreground">{{ $payload['capability_status'] }}</td>
                                </tr>
                                <tr>
                                    <td class="min-w-56 text-secondary-foreground font-normal">Bankrekening (weergave)</td>
                                    <td class="min-w-48 w-full font-medium text-foreground">{{ $payload['masked_destination'] ?: 'Nog niet ingevuld' }}</td>
                                </tr>
                                <tr>
                                    <td class="min-w-56 text-secondary-foreground font-normal">Settlement-ready</td>
                                    <td class="min-w-48 w-full font-medium text-foreground">{{ $payload['can_receive_settlement'] ? 'Ja' : 'Nee' }}</td>
                                </tr>
                                <tr>
                                    <td class="min-w-56 text-secondary-foreground font-normal">Laatste sync</td>
                                    <td class="min-w-48 w-full font-medium text-foreground">{{ $payload['last_synced_at'] ?: '—' }}</td>
                                </tr>
                            </table>
                        </div>

                        @if($payload['pending_destination_change'])
                            <div class="rounded-lg border border-border px-3 py-2.5 text-sm">
                                Rekeningwijziging in afwachting
                                @if(!empty($identity->pending_masked_destination))
                                    (nieuw: {{ $identity->pending_masked_destination }})
                                @endif
                                @if($payload['destination_change_eligible_at'])
                                    — het nieuwe nummer wordt gebruikt vanaf {{ \Illuminate\Support\Carbon::parse($payload['destination_change_eligible_at'])->timezone(config('app.timezone'))->format('d-m-Y H:i') }}.
                                @endif
                            </div>
                        @endif

                        <div class="flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('admin.payout-identities.sync', $identity) }}">
                                @csrf
                                <button type="submit" class="kt-btn kt-btn-outline kt-btn-sm">Status synchroniseren</button>
                            </form>
                            @if($payload['pending_destination_change'] && ! $payload['destination_change_cooling'])
                                <form method="POST" action="{{ route('admin.payout-identities.destination.apply', $identity) }}">
                                    @csrf
                                    <button type="submit" class="kt-btn kt-btn-primary kt-btn-sm">Wijziging nu activeren</button>
                                </form>
                            @endif
                        </div>
                    @else
                        <p class="text-sm text-muted-foreground mb-0">Payout-identiteit wordt automatisch aangemaakt bij tenant-aanmaak. Vul hieronder de bankrekening in.</p>
                    @endif
                </div>
            </div>

            <form method="POST" action="{{ route('admin.payout-identities.bank-account') }}" data-validate="true" novalidate>
                @csrf
                <div class="kt-card w-full min-w-0">
                    <div class="kt-card-header px-5 py-5">
                        <h3 class="kt-card-title mb-0">{{ filled($identity?->masked_destination) ? 'Bankrekening wijzigen' : 'Bankrekening invullen' }}</h3>
                    </div>
                    <div class="kt-card-content p-0">
                        <div class="px-3 sm:px-5 pb-3 min-w-0">
                            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                                <tr>
                                    <td class="min-w-56 text-secondary-foreground font-normal">IBAN</td>
                                    <td class="min-w-48 w-full">
                                        <input type="text" name="iban" id="iban" class="kt-input @error('iban') border-destructive @enderror" placeholder="NL91 ABNA 0417 1643 00" value="{{ old('iban') }}" autocomplete="off" required>
                                        <p class="text-xs text-muted-foreground mt-1 mb-0">Wordt niet opgeslagen. We tonen en bewaren alleen *** + laatste 4 cijfers (bijv. ***4300).</p>
                                        @error('iban')
                                            <p class="text-sm text-destructive mt-1 mb-0">{{ $message }}</p>
                                        @enderror
                                    </td>
                                </tr>
                                @if(filled($identity?->masked_destination))
                                <tr>
                                    <td class="min-w-56 text-secondary-foreground font-normal">Huidig wachtwoord</td>
                                    <td class="min-w-48 w-full">
                                        <input type="password" name="password" id="bank_password" class="kt-input @error('password') border-destructive @enderror" autocomplete="current-password" required>
                                        <p class="text-xs text-muted-foreground mt-1 mb-0">Verplicht bij wijziging. Het nieuwe rekeningnummer wordt pas na {{ $coolingOffHours }} uur gebruikt voor uitbetalingen.</p>
                                        @error('password')
                                            <p class="text-sm text-destructive mt-1 mb-0">{{ $message }}</p>
                                        @enderror
                                    </td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
                <div class="admin-form-actions flex flex-wrap items-center justify-end gap-2.5 mt-5 w-full min-w-0">
                    <button type="submit" class="kt-btn kt-btn-primary">Opslaan</button>
                </div>
            </form>

            @if(auth()->user()->hasRole('super-admin'))
            <form method="POST" action="{{ route('admin.payout-identities.company.ensure') }}" data-validate="true" novalidate>
                @csrf
                <div class="kt-card w-full min-w-0">
                    <div class="kt-card-header px-5 py-5">
                        <h3 class="kt-card-title mb-0">Geavanceerd: provider-metadata (super-admin)</h3>
                    </div>
                    <div class="kt-card-content p-0">
                        <div class="px-3 sm:px-5 pb-3 min-w-0">
                            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                                <tr>
                                    <td class="min-w-56 text-secondary-foreground font-normal">Provider organization ID</td>
                                    <td class="min-w-48 w-full">
                                        <input type="text" name="provider_organization_id" id="provider_organization_id" class="kt-input" value="{{ old('provider_organization_id', $identity?->provider_organization_id) }}" autocomplete="off">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="min-w-56 text-secondary-foreground font-normal">Provider account ID</td>
                                    <td class="min-w-48 w-full">
                                        <input type="text" name="provider_account_id" id="provider_account_id" class="kt-input" value="{{ old('provider_account_id', $identity?->provider_account_id) }}" autocomplete="off">
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="admin-form-actions flex flex-wrap items-center justify-end gap-2.5 mt-5 w-full min-w-0">
                    <button type="submit" class="kt-btn kt-btn-outline">Provider-metadata opslaan</button>
                </div>
            </form>
            @endif

            @if($identity)
            <form method="POST" action="{{ route('admin.payout-identities.disable', $identity) }}" data-validate="true" novalidate>
                @csrf
                <div class="kt-card w-full min-w-0">
                    <div class="kt-card-header px-5 py-5">
                        <h3 class="kt-card-title mb-0">Uitschakelen</h3>
                    </div>
                    <div class="kt-card-content p-0">
                        <div class="px-3 sm:px-5 pb-3 min-w-0">
                            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                                <tr>
                                    <td class="min-w-56 text-secondary-foreground font-normal">Huidig wachtwoord</td>
                                    <td class="min-w-48 w-full">
                                        <input type="password" name="password" id="disable_password" class="kt-input" required autocomplete="current-password">
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="admin-form-actions flex flex-wrap items-center justify-end gap-2.5 mt-5 w-full min-w-0">
                    <button type="submit" class="kt-btn kt-btn-destructive">Payout uitschakelen</button>
                </div>
            </form>
            @endif

            <div class="kt-card w-full min-w-0">
                <div class="kt-card-content p-5">
                    <p class="text-sm text-muted-foreground mb-0">
                        App-API: <code class="text-xs">GET/PUT /api/tenant/payout-identity</code> (company-admin).
                        Independent-driver payouts:
                        <strong>{{ $allowIndependentDriverPayouts ? 'config aan (nog niet productie-klaar)' : 'uit (default)' }}</strong>.
                    </p>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/form-validation.js') }}"></script>
@endpush
