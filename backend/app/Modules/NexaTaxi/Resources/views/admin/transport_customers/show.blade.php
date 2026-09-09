@extends('admin.layouts.app')

@section('title', $customer->name)

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <div>
            <h1 class="text-xl font-medium leading-none text-mono">{{ $customer->name }}</h1>
            <div class="pt-3">
                <a href="{{ route('admin.taxi.transport_customers.index') }}" class="kt-btn kt-btn-outline">
                    <i class="ki-filled ki-arrow-left me-2"></i>
                    Terug
                </a>
            </div>
        </div>
        <div class="flex gap-2 shrink-0">
            @can('rides.update')
            <a href="{{ route('admin.taxi.transport_customers.edit', $customer->id) }}" class="kt-btn kt-btn-outline">
                Bewerken
            </a>
            @endcan
            @can('rides.create')
            <a href="{{ route('admin.taxi.transport_customers.contract_create', $customer->id) }}" class="kt-btn kt-btn-primary">
                <svg class="w-4 h-4 me-2 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Nieuw abonnement
            </a>
            @endcan
        </div>
    </div>

    @php
        $portalCount = ($portalUsers ?? collect())->count();
        $contractCount = ($contracts ?? collect())->count();
        $announcementCount = ($announcements ?? collect())->count();
        $customerSection = old('section', request('section', 'portaal'));
        if (! in_array($customerSection, ['portaal', 'abonnementen', 'meldingen'], true)) {
            $customerSection = 'portaal';
        }
        $customerSectionUrl = fn (string $section) => request()->fullUrlWithQuery(['section' => $section]);
    @endphp

    <div class="grid gap-5 lg:gap-7.5">

        {{-- Klantdetails --}}
        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header px-5 py-5">
                <h3 class="kt-card-title mb-0">Klantdetails</h3>
            </div>
            <div class="kt-card-content p-0">
                <div class="px-3 sm:px-5 pb-3 min-w-0">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table customer-detail-table w-full">
                        <tr>
                            <td class="min-w-40 sm:min-w-56 text-secondary-foreground font-medium">Status</td>
                            <td class="min-w-0">
                                @if($customer->active)
                                    <span class="kt-badge kt-badge-success kt-badge-sm">Actief</span>
                                @else
                                    <span class="kt-badge kt-badge-secondary kt-badge-sm">Inactief</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-40 sm:min-w-56 text-secondary-foreground font-medium">Contactpersoon</td>
                            <td class="min-w-0">{{ $customer->contact_name ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="min-w-40 sm:min-w-56 text-secondary-foreground font-medium">E-mail</td>
                            <td class="min-w-0">{{ $customer->contact_email ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="min-w-40 sm:min-w-56 text-secondary-foreground font-medium">Telefoon</td>
                            <td class="min-w-0">{{ $customer->contact_phone ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="min-w-40 sm:min-w-56 text-secondary-foreground font-medium">Debiteurnummer</td>
                            <td class="min-w-0">{{ $customer->debtor_number ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="min-w-40 sm:min-w-56 text-secondary-foreground font-medium">Factuuradres</td>
                            <td class="min-w-0">
                                @if($customer->billing_address)
                                    {{ $customer->billing_address }}<br>
                                    {{ $customer->billing_postal_code }} {{ $customer->billing_city }}<br>
                                    {{ $customer->billing_country }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        @if($customer->notes)
                        <tr>
                            <td class="min-w-40 sm:min-w-56 text-secondary-foreground font-medium">Notities</td>
                            <td class="min-w-0 whitespace-pre-wrap">{{ $customer->notes }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <nav class="customer-section-nav grid grid-cols-1 sm:grid-cols-3 gap-3 lg:gap-4 w-full min-w-0" aria-label="Klantonderdelen" data-customer-sections>
            <a
                href="{{ $customerSectionUrl('portaal') }}"
                class="customer-section-tile{{ $customerSection === 'portaal' ? ' is-active' : '' }}"
                data-customer-section="portaal"
                @if($customerSection === 'portaal') aria-current="page" @endif
            >
                <span class="customer-section-tile__label">Contractportaal</span>
                <span class="customer-section-tile__value">{{ $portalCount }}</span>
                <span class="customer-section-tile__meta">{{ $portalCount === 1 ? 'gebruiker' : 'gebruikers' }} · contract-app</span>
            </a>
            <a
                href="{{ $customerSectionUrl('abonnementen') }}"
                class="customer-section-tile{{ $customerSection === 'abonnementen' ? ' is-active' : '' }}"
                data-customer-section="abonnementen"
                @if($customerSection === 'abonnementen') aria-current="page" @endif
            >
                <span class="customer-section-tile__label">Abonnementen</span>
                <span class="customer-section-tile__value">{{ $contractCount }}</span>
                <span class="customer-section-tile__meta">{{ $contractCount === 1 ? 'abonnement' : 'abonnementen' }} · planning &amp; facturatie</span>
            </a>
            <a
                href="{{ $customerSectionUrl('meldingen') }}"
                class="customer-section-tile{{ $customerSection === 'meldingen' ? ' is-active' : '' }}"
                data-customer-section="meldingen"
                @if($customerSection === 'meldingen') aria-current="page" @endif
            >
                <span class="customer-section-tile__label">Verstoringen &amp; meldingen</span>
                <span class="customer-section-tile__value">{{ $announcementCount }}</span>
                <span class="customer-section-tile__meta">{{ $announcementCount === 1 ? 'melding' : 'meldingen' }} · banner in de app</span>
            </a>
        </nav>

        {{-- Contractportaal --}}
        @php
            $openPortalAddForm = $errors->any() && old('section') === 'portaal';
        @endphp
        <div class="customer-section-panel{{ $customerSection === 'portaal' ? ' is-active' : '' }}" data-customer-section-panel="portaal" id="customer-section-portaal">
        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header px-5 py-5 flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0">
                    <h3 class="kt-card-title mb-0">Contractportaal</h3>
                    <p class="text-sm text-muted-foreground mt-1.5 mb-0">
                        App voor schoolcontact / ouders:
                        <a href="{{ url('/taxi/contract') }}" target="_blank" rel="noopener" class="text-primary hover:underline break-all">/taxi/contract</a>
                    </p>
                </div>
                @can('rides.update')
                <button
                    type="button"
                    class="kt-btn kt-btn-primary shrink-0"
                    id="portal-user-add-toggle"
                    data-portal-add-open
                    aria-expanded="{{ $openPortalAddForm ? 'true' : 'false' }}"
                    aria-controls="portal-user-add-section"
                    @if($openPortalAddForm) hidden @endif
                >
                    <svg class="w-4 h-4 me-2 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Gebruiker toevoegen
                </button>
                @endcan
            </div>
            <div class="kt-card-content p-0 min-w-0">
                <div class="portal-users-stack p-5 flex flex-col gap-4">
                    @if(($portalUsers ?? collect())->isNotEmpty())
                        @foreach($portalUsers as $portalUser)
                            @php
                                $u = ($portalUserModels ?? collect())->get($portalUser->user_id);
                                $linkedPassengers = collect($guardianMap ?? [])->get((int) $portalUser->user_id, []);
                                $portalName = $u ? trim($u->first_name.' '.$u->last_name) : 'Gebruiker #'.$portalUser->user_id;
                                $portalRoleLabel = ($portalUser->portal_role === 'contractant') ? 'Contractant' : 'Contractouder';
                            @endphp
                            <div class="portal-user-block portal-user-block--collapsed rounded-xl min-w-0" data-portal-user-block>
                                <button
                                    type="button"
                                    class="portal-user-block-toggle flex w-full items-start justify-between gap-3 px-5 py-5 text-start"
                                    aria-expanded="false"
                                >
                                    <span class="min-w-0">
                                        <span class="block font-medium text-foreground break-words">{{ $portalName }}</span>
                                        <span class="block text-sm text-muted-foreground break-all">{{ $u?->email ?? '—' }}</span>
                                        <span class="mt-1.5 inline-block text-xs text-muted-foreground">{{ $portalRoleLabel }}</span>
                                    </span>
                                    <span class="flex items-center gap-2 shrink-0 pt-0.5">
                                        @if($portalUser->active)
                                            <span class="kt-badge kt-badge-success kt-badge-sm">Actief</span>
                                        @else
                                            <span class="kt-badge kt-badge-secondary kt-badge-sm">Inactief</span>
                                        @endif
                                        <span class="portal-user-block-chevron text-muted-foreground" aria-hidden="true">
                                            <i class="ki-filled ki-down text-base"></i>
                                        </span>
                                    </span>
                                </button>
                                <div class="portal-user-block-body px-5 pb-5">
                                <form method="POST" action="{{ route('admin.taxi.transport_customers.portal.update', [$customer->id, $portalUser->id]) }}" class="min-w-0">
                                    @csrf
                                    <input type="hidden" name="section" value="portaal">
                                    @method('PUT')
                                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                                        <tr>
                                            <td class="min-w-40 sm:min-w-56 text-secondary-foreground font-normal align-top pt-3">Rol</td>
                                            <td class="min-w-0 w-full">
                                                <select name="portal_role" class="kt-select w-full" required>
                                                    <option value="contractant" @selected($portalUser->portal_role === 'contractant')>Contractant (alle cliënten)</option>
                                                    <option value="contractouder" @selected($portalUser->portal_role === 'contractouder')>Contractouder (gekoppelde cliënten)</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-secondary-foreground font-normal">Status</td>
                                            <td>
                                                <input type="hidden" name="active" value="0">
                                                <label class="kt-label flex items-center gap-2 mb-0">
                                                    <input type="checkbox" name="active" value="1" class="kt-switch kt-switch-sm shrink-0" @checked($portalUser->active)>
                                                    <span class="text-sm text-muted-foreground">Actief</span>
                                                </label>
                                            </td>
                                        </tr>
                                        @if(($passengers ?? collect())->isNotEmpty())
                                        <tr>
                                            <td class="text-secondary-foreground font-normal align-top pt-3">Cliënten</td>
                                            <td class="min-w-0">
                                                <p class="text-xs text-muted-foreground mb-2">Bij Contractouder bepalen deze vinkjes de toegang. Bij Contractant heeft de gebruiker sowieso toegang tot alle cliënten.</p>
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                    @foreach($passengers as $passenger)
                                                        <label class="kt-label flex items-start gap-2 mb-0 min-w-0">
                                                            <input
                                                                type="checkbox"
                                                                name="passenger_ids[]"
                                                                value="{{ $passenger->id }}"
                                                                class="kt-checkbox shrink-0 mt-0.5"
                                                                @checked(in_array((int) $passenger->id, $linkedPassengers, true))
                                                            >
                                                            <span class="text-sm text-foreground break-words">{{ $passenger->full_name }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </td>
                                        </tr>
                                        @endif
                                    </table>
                                    @can('rides.update')
                                    <div class="flex flex-wrap items-center justify-end gap-2 mt-4">
                                        <button type="submit" class="kt-btn kt-btn-primary kt-btn-sm">Opslaan</button>
                                        <button
                                            type="submit"
                                            form="portal-user-destroy-{{ $portalUser->id }}"
                                            class="kt-btn kt-btn-outline kt-btn-sm"
                                            onclick="return confirm('Portaalgebruiker ontkoppelen?');"
                                        >Ontkoppelen</button>
                                    </div>
                                    @endcan
                                </form>
                                @can('rides.update')
                                <form
                                    id="portal-user-destroy-{{ $portalUser->id }}"
                                    method="POST"
                                    action="{{ route('admin.taxi.transport_customers.portal.destroy', [$customer->id, $portalUser->id]) }}"
                                    class="hidden"
                                >
                                    @csrf
                                    <input type="hidden" name="section" value="portaal">
                                    @method('DELETE')
                                </form>
                                @endcan
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-sm text-muted-foreground mb-0">Nog geen portaalgebruikers gekoppeld.</p>
                    @endif
                </div>
            </div>
        </div>

        @can('rides.update')
        <div
            class="kt-card w-full min-w-0{{ $openPortalAddForm ? '' : ' portal-user-add-card--collapsed' }}"
            id="portal-user-add-section"
            @unless($openPortalAddForm) hidden @endunless
        >
            <div class="kt-card-header px-5 py-5 flex flex-wrap items-center justify-between gap-3">
                <h3 class="kt-card-title mb-0">Gebruiker toevoegen</h3>
                <button
                    type="button"
                    class="kt-btn kt-btn-outline kt-btn-sm shrink-0"
                    id="portal-user-add-cancel"
                    data-portal-add-close
                >
                    Annuleren
                </button>
            </div>
            <div class="kt-card-content p-0 min-w-0">
                <div class="px-3 sm:px-5 pb-5 min-w-0">
                    @if($openPortalAddForm)
                        <div class="kt-alert kt-alert-danger mb-4 mt-4" role="alert">
                            <ul class="list-disc list-inside mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form method="POST" action="{{ route('admin.taxi.transport_customers.portal.store', $customer->id) }}" class="min-w-0" id="portal-user-add-form" novalidate>
                        @csrf
                        <input type="hidden" name="section" value="portaal">
                        @php
                            $selectedExistingUserId = (int) old('existing_user_id', 0);
                            $userMode = old('user_mode', $selectedExistingUserId > 0 ? 'existing' : 'new');
                            $manualMode = $userMode !== 'existing';
                        @endphp
                        <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                            <tr>
                                <td class="min-w-40 sm:min-w-56 text-secondary-foreground font-normal align-top pt-3">Gebruiker</td>
                                <td class="min-w-0 w-full">
                                    <div class="flex flex-col sm:flex-row gap-3 sm:gap-5">
                                        <label class="kt-label flex items-center gap-2 mb-0">
                                            <input type="radio" name="user_mode" value="new" class="kt-radio shrink-0" id="portal-mode-new" @checked($manualMode)>
                                            <span class="text-sm text-foreground">Nieuwe gebruiker</span>
                                        </label>
                                        <label class="kt-label flex items-center gap-2 mb-0">
                                            <input type="radio" name="user_mode" value="existing" class="kt-radio shrink-0" id="portal-mode-existing" @checked(! $manualMode)>
                                            <span class="text-sm text-foreground">Bestaande gebruiker</span>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                            <tr class="portal-existing-fields" @if($manualMode) hidden @endif>
                                <td class="text-secondary-foreground font-normal align-top pt-3">Kies gebruiker <span class="text-danger">*</span></td>
                                <td class="min-w-0 w-full">
                                    <select
                                        id="portal-existing-user"
                                        name="existing_user_id"
                                        class="kt-select"
                                        data-kt-select="true"
                                        data-kt-select-enable-search="true"
                                        data-kt-select-placeholder="Selecteer een gebruiker"
                                        data-kt-select-search-placeholder="Zoek op naam of e-mail…"
                                        data-kt-select-dropdown-strategy="fixed"
                                        data-kt-select-config='{"placeholder":"Selecteer een gebruiker","searchPlaceholder":"Zoek op naam of e-mail…","height":256,"dropdownStrategy":"fixed","dropdownWidth":"28rem","dropdownZindex":200,"closeOnEnter":true}'
                                        aria-label="Kies bestaande gebruiker"
                                        @if($manualMode) disabled @endif
                                    >
                                        <option value="">— Selecteer —</option>
                                        @foreach(($tenantUsers ?? collect()) as $tenantUser)
                                            @php
                                                $tenantLabel = trim(($tenantUser->first_name ?? '').' '.($tenantUser->last_name ?? ''));
                                                $tenantLabel = $tenantLabel !== '' ? $tenantLabel : 'Gebruiker #'.$tenantUser->id;
                                            @endphp
                                            <option
                                                value="{{ $tenantUser->id }}"
                                                @selected($selectedExistingUserId === (int) $tenantUser->id)
                                            >
                                                {{ $tenantLabel }} — {{ $tenantUser->email }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('existing_user_id')
                                        <p class="text-xs text-danger mt-1.5 mb-0">{{ $message }}</p>
                                    @enderror
                                </td>
                            </tr>
                            <tr class="portal-manual-fields" @if(! $manualMode) hidden @endif>
                                <td class="text-secondary-foreground font-normal">Voornaam <span class="text-danger">*</span></td>
                                <td>
                                    <input id="portal-first-name" type="text" name="first_name" class="kt-input w-full" value="{{ old('first_name') }}" maxlength="100" autocomplete="given-name" @if(! $manualMode) disabled @endif>
                                    @error('first_name')
                                        <p class="text-xs text-danger mt-1.5 mb-0">{{ $message }}</p>
                                    @enderror
                                </td>
                            </tr>
                            <tr class="portal-manual-fields" @if(! $manualMode) hidden @endif>
                                <td class="text-secondary-foreground font-normal">Achternaam</td>
                                <td>
                                    <input id="portal-last-name" type="text" name="last_name" class="kt-input w-full" value="{{ old('last_name') }}" maxlength="100" autocomplete="family-name" @if(! $manualMode) disabled @endif>
                                </td>
                            </tr>
                            <tr class="portal-manual-fields" @if(! $manualMode) hidden @endif>
                                <td class="text-secondary-foreground font-normal">E-mail <span class="text-danger">*</span></td>
                                <td>
                                    <input id="portal-email" type="email" name="email" class="kt-input w-full" value="{{ old('email') }}" maxlength="200" autocomplete="email" @if(! $manualMode) disabled @endif>
                                    @error('email')
                                        <p class="text-xs text-danger mt-1.5 mb-0">{{ $message }}</p>
                                    @enderror
                                    <p class="text-xs text-muted-foreground mt-1.5 mb-0">Er gaat een welkomstmail uit zonder wachtwoord. De gebruiker vraagt in het contractportaal een eenmalige inlogcode aan.</p>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal">Rol <span class="text-danger">*</span></td>
                                <td>
                                    <select id="portal-role" name="portal_role" class="kt-select w-full" required>
                                        <option value="contractant" @selected(old('portal_role') === 'contractant')>Contractant (alle cliënten)</option>
                                        <option value="contractouder" @selected(old('portal_role', 'contractouder') === 'contractouder')>Contractouder (gekoppelde cliënten)</option>
                                    </select>
                                </td>
                            </tr>
                            @if(($passengers ?? collect())->isNotEmpty())
                            <tr id="portal-new-passengers">
                                <td class="text-secondary-foreground font-normal align-top pt-3">Cliënten</td>
                                <td class="min-w-0">
                                    <p class="text-xs text-muted-foreground mb-2">Bij Contractouder bepalen deze vinkjes de toegang. Bij Contractant heeft de gebruiker sowieso toegang tot alle cliënten.</p>
                                    <div class="portal-passenger-picker space-y-2" data-portal-passengers>
                                        <label class="kt-label flex items-center gap-2 mb-3">
                                            <input type="checkbox" class="kt-checkbox shrink-0 portal-passenger-select-all" aria-label="Selecteer alle cliënten">
                                            <span class="text-sm font-medium text-foreground">Selecteer allemaal</span>
                                        </label>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            @foreach($passengers as $passenger)
                                                <label class="kt-label flex items-start gap-2 mb-0 min-w-0">
                                                    <input type="checkbox" name="passenger_ids[]" value="{{ $passenger->id }}" class="kt-checkbox shrink-0 mt-0.5 portal-passenger-check" @checked(collect(old('passenger_ids', []))->contains($passenger->id))>
                                                    <span class="text-sm text-foreground break-words">{{ $passenger->full_name }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endif
                        </table>
                        <div class="flex flex-wrap items-center justify-end gap-2 mt-4">
                            <button type="submit" class="kt-btn kt-btn-primary">Toevoegen / koppelen</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endcan
        </div>

        {{-- Abonnementen --}}
        <div class="customer-section-panel{{ $customerSection === 'abonnementen' ? ' is-active' : '' }}" data-customer-section-panel="abonnementen" id="customer-section-abonnementen">
        <div class="kt-card kt-card-grid w-full min-w-0">
            <div class="kt-card-header px-5 py-5">
                <h3 class="kt-card-title mb-0">Abonnementen</h3>
            </div>
            <div class="kt-card-content p-0 min-w-0">
                <div class="kt-scrollable-x-auto admin-table-scroll-wrap">
                    <table id="transport-customer-contracts-table" class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full">
                        <thead>
                            <tr>
                                <th data-label="Naam">Naam</th>
                                <th data-label="Facturatiemodel">Facturatie</th>
                                <th data-label="Periode">Periode</th>
                                <th data-label="Status">Status</th>
                                <th class="transport-customers-table__actions-col text-secondary-foreground font-normal text-center" data-label="Acties"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($contracts as $contract)
                            <tr
                                data-row-href="{{ route('admin.taxi.transport_customers.contract_show', [$customer->id, $contract->id]) }}"
                                class="cursor-pointer hover:bg-muted/40"
                                tabindex="0"
                                role="link"
                                aria-label="Bekijk abonnement {{ $contract->name }}"
                            >
                                <td>
                                    <span class="font-medium text-foreground">{{ $contract->name }}</span>
                                </td>
                                <td class="text-muted-foreground">
                                    @if($contract->billing_model === 'fixed_monthly')
                                        Vast maandbedrag
                                    @elseif($contract->billing_model === 'per_ride')
                                        Per rit
                                    @else
                                        Hybride
                                    @endif
                                </td>
                                <td class="text-muted-foreground">
                                    {{ $contract->start_date ? \Carbon\Carbon::parse($contract->start_date)->format('d-m-Y') : '—' }}
                                    &rarr;
                                    {{ $contract->end_date ? \Carbon\Carbon::parse($contract->end_date)->format('d-m-Y') : 'doorlopend' }}
                                </td>
                                <td>
                                    @if($contract->status === 'active')
                                        <span class="kt-badge kt-badge-success kt-badge-sm">Actief</span>
                                    @elseif($contract->status === 'paused')
                                        <span class="kt-badge kt-badge-warning kt-badge-sm">Gepauzeerd</span>
                                    @else
                                        <span class="kt-badge kt-badge-secondary kt-badge-sm">Beëindigd</span>
                                    @endif
                                </td>
                                <td class="transport-customers-table__actions-col" data-no-row-link onclick="event.stopPropagation();">
                                    <a href="{{ route('admin.taxi.transport_customers.contract_show', [$customer->id, $contract->id]) }}" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" title="Bekijken" aria-label="Bekijken">
                                        <i class="ki-filled ki-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted-foreground py-8">
                                    Nog geen abonnementen. <a href="{{ route('admin.taxi.transport_customers.contract_create', $customer->id) }}" class="text-primary hover:underline">Nieuw abonnement aanmaken</a>.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        </div>

        {{-- Verstoringen / meldingen voor contractportaal --}}
        <div class="customer-section-panel{{ $customerSection === 'meldingen' ? ' is-active' : '' }}" data-customer-section-panel="meldingen" id="customer-section-meldingen">
        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header px-5 py-5">
                <div class="min-w-0">
                    <h3 class="kt-card-title mb-0">Verstoringen &amp; meldingen</h3>
                    <p class="text-sm text-muted-foreground mt-1.5 mb-0">
                        Zichtbaar als banner in de contract-app voor deze klant.
                    </p>
                </div>
            </div>
            <div class="kt-card-content p-0 min-w-0">
                @if(($announcements ?? collect())->isNotEmpty())
                    <div class="divide-y">
                        @foreach($announcements as $announcement)
                            <div class="px-3 sm:px-5 py-4 min-w-0">
                                <form method="POST" action="{{ route('admin.taxi.transport_customers.announcements.update', [$customer->id, $announcement->id]) }}" class="space-y-3">
                                    @csrf
                                    <input type="hidden" name="section" value="meldingen">
                                    @method('PUT')
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <div class="font-medium text-foreground">{{ $announcement->title }}</div>
                                            <div class="text-sm text-muted-foreground">
                                                @if($announcement->is_active)
                                                    <span class="kt-badge kt-badge-success kt-badge-sm">Actief</span>
                                                @else
                                                    <span class="kt-badge kt-badge-secondary kt-badge-sm">Uit</span>
                                                @endif
                                                <span class="ml-1">{{ strtoupper($announcement->severity) }}</span>
                                            </div>
                                        </div>
                                        <button type="submit" form="announcement-delete-{{ $announcement->id }}" class="kt-btn kt-btn-sm kt-btn-ghost text-danger" onclick="return confirm('Melding verwijderen?');">Verwijderen</button>
                                    </div>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <label class="text-sm text-muted-foreground">Titel</label>
                                            <input type="text" name="title" class="kt-input w-full" value="{{ old('title', $announcement->title) }}" required maxlength="200">
                                        </div>
                                        <div>
                                            <label class="text-sm text-muted-foreground">Ernst</label>
                                            <select name="severity" class="kt-select w-full" required>
                                                <option value="info" @selected($announcement->severity === 'info')>Info</option>
                                                <option value="warning" @selected($announcement->severity === 'warning')>Waarschuwing</option>
                                                <option value="critical" @selected($announcement->severity === 'critical')>Kritiek</option>
                                            </select>
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label class="text-sm text-muted-foreground">Tekst</label>
                                            <textarea name="body" class="kt-textarea w-full" rows="2" maxlength="2000">{{ old('body', $announcement->body) }}</textarea>
                                        </div>
                                        <div>
                                            <label class="text-sm text-muted-foreground">Start</label>
                                            @include('taxi::admin.transport_customers.partials.datetime-picker-input', [
                                                'name' => 'starts_at',
                                                'value' => old('starts_at', $announcement->starts_at),
                                                'placeholder' => 'Selecteer datum en tijd',
                                            ])
                                        </div>
                                        <div>
                                            <label class="text-sm text-muted-foreground">Einde</label>
                                            @include('taxi::admin.transport_customers.partials.datetime-picker-input', [
                                                'name' => 'ends_at',
                                                'value' => old('ends_at', $announcement->ends_at),
                                                'placeholder' => 'Selecteer datum en tijd',
                                            ])
                                        </div>
                                    </div>
                                    <label class="inline-flex items-center gap-2 text-sm">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" name="is_active" value="1" class="kt-checkbox" @checked($announcement->is_active)>
                                        Actief
                                    </label>
                                    <div>
                                        <button type="submit" class="kt-btn kt-btn-sm kt-btn-primary">Opslaan</button>
                                    </div>
                                </form>
                                <form id="announcement-delete-{{ $announcement->id }}" method="POST" action="{{ route('admin.taxi.transport_customers.announcements.destroy', [$customer->id, $announcement->id]) }}" class="hidden">
                                    @csrf
                                    <input type="hidden" name="section" value="meldingen">
                                    @method('DELETE')
                                </form>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="px-3 sm:px-5 py-4">
                        <p class="text-sm text-muted-foreground mb-0">Nog geen verstoringen of meldingen.</p>
                    </div>
                @endif

                <div class="px-3 sm:px-5 py-4 border-t border-border min-w-0">
                    <h4 class="font-medium text-foreground mb-3">Nieuwe melding</h4>
                    <form method="POST" action="{{ route('admin.taxi.transport_customers.announcements.store', $customer->id) }}" class="space-y-3">
                        @csrf
                        <input type="hidden" name="section" value="meldingen">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="text-sm text-muted-foreground">Titel</label>
                                <input type="text" name="title" class="kt-input w-full" required maxlength="200" value="{{ old('title') }}">
                            </div>
                            <div>
                                <label class="text-sm text-muted-foreground">Ernst</label>
                                <select name="severity" class="kt-select w-full" required>
                                    <option value="info" @selected(old('severity', 'warning') === 'info')>Info</option>
                                    <option value="warning" @selected(old('severity', 'warning') === 'warning')>Waarschuwing</option>
                                    <option value="critical" @selected(old('severity') === 'critical')>Kritiek</option>
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="text-sm text-muted-foreground">Tekst</label>
                                <textarea name="body" class="kt-textarea w-full" rows="2" maxlength="2000" placeholder="Bijv. vertraging door wegwerkzaamheden…">{{ old('body') }}</textarea>
                            </div>
                            <div>
                                <label class="text-sm text-muted-foreground">Start (optioneel)</label>
                                @include('taxi::admin.transport_customers.partials.datetime-picker-input', [
                                    'name' => 'starts_at',
                                    'value' => old('starts_at'),
                                    'placeholder' => 'Selecteer datum en tijd',
                                ])
                            </div>
                            <div>
                                <label class="text-sm text-muted-foreground">Einde (optioneel)</label>
                                @include('taxi::admin.transport_customers.partials.datetime-picker-input', [
                                    'name' => 'ends_at',
                                    'value' => old('ends_at'),
                                    'placeholder' => 'Selecteer datum en tijd',
                                ])
                            </div>
                        </div>
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" class="kt-checkbox" @checked(old('is_active', true))>
                            Direct actief
                        </label>
                        <div>
                            <button type="submit" class="kt-btn kt-btn-sm kt-btn-primary">Plaatsen</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        </div>

    </div>
</div>
@endsection

@push('styles')
<style>
    #content .customer-section-nav {
        min-width: 0;
    }

    #content .customer-section-tile {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.2rem;
        min-width: 0;
        padding: 1.25rem;
        border: 1px solid var(--border);
        border-radius: calc(var(--radius, 0.5rem) + 4px);
        background: var(--card, var(--background));
        color: inherit;
        text-decoration: none;
        box-shadow: none;
        transition: border-color 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease;
    }

    #content .customer-section-tile:hover {
        border-color: color-mix(in oklab, var(--primary) 35%, var(--border));
        color: inherit;
    }

    #content .customer-section-tile.is-active {
        border-color: color-mix(in oklab, var(--primary) 50%, var(--border));
        background: color-mix(in oklab, var(--primary) 8%, var(--background));
        box-shadow: 0 0 0 1px color-mix(in oklab, var(--primary) 28%, transparent);
    }

    #content .customer-section-tile__label {
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--foreground);
        line-height: 1.3;
    }

    #content .customer-section-tile__value {
        font-size: 1.5rem;
        font-weight: 650;
        line-height: 1.2;
        color: var(--mono, var(--foreground));
        letter-spacing: -0.02em;
    }

    #content .customer-section-tile.is-active .customer-section-tile__value {
        color: var(--primary);
    }

    #content .customer-section-tile__meta {
        font-size: 0.8125rem;
        color: var(--muted-foreground);
        line-height: 1.35;
    }

    #content .customer-section-panel.is-active {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
        min-width: 0;
    }

    #content .customer-section-panel:not(.is-active) {
        display: none !important;
    }

    #content #portal-user-add-section[hidden],
    #content #portal-user-add-section.portal-user-add-card--collapsed,
    #content #portal-user-add-toggle[hidden] {
        display: none !important;
    }

    #content .customer-detail-table,
    #content #portal-user-add-form .wizard-onboarding-form-table {
        width: 100%;
        table-layout: fixed;
    }

    #content .customer-detail-table td:first-child,
    #content #portal-user-add-form .wizard-onboarding-form-table td:first-child {
        width: 14rem;
        min-width: 14rem;
        max-width: 14rem;
        vertical-align: top;
    }

    #content .customer-detail-table td:nth-child(2),
    #content #portal-user-add-form .wizard-onboarding-form-table td:nth-child(2) {
        min-width: 0;
        overflow-wrap: break-word;
    }

    #content #transport-customer-contracts-table .transport-customers-table__actions-col {
        width: 4.5rem !important;
        min-width: 4.5rem !important;
        max-width: 4.5rem !important;
        padding-inline: 0.375rem !important;
        text-align: center !important;
        vertical-align: middle !important;
        white-space: nowrap;
    }

    /* Portaalgebruiker-kaarten: tint, ronde hoeken, inklapbaar */
    #content .portal-user-block {
        background-color: color-mix(in srgb, var(--muted) 72%, var(--background));
        border: 1px solid color-mix(in srgb, var(--border) 85%, transparent);
    }
    html.dark #content .portal-user-block,
    .dark #content .portal-user-block {
        background-color: color-mix(in srgb, var(--muted) 38%, #0b1220);
    }
    #content .portal-user-block-toggle {
        border: 0;
        background: transparent;
        color: inherit;
        cursor: pointer;
        border-radius: 0.75rem;
        width: 100%;
    }
    #content .portal-user-block:not(.portal-user-block--collapsed) .portal-user-block-toggle {
        border-bottom: 1px solid color-mix(in srgb, var(--border) 85%, transparent);
        border-radius: 0.75rem 0.75rem 0 0;
    }
    #content .portal-user-block-toggle:hover {
        color: var(--color-primary, #3b82f6);
    }
    #content .portal-user-block-chevron i {
        display: inline-block;
        transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1);
    }
    #content .portal-user-block:not(.portal-user-block--collapsed) .portal-user-block-chevron i {
        transform: rotate(180deg);
    }
    #content .portal-user-block--collapsed > .portal-user-block-body {
        display: none !important;
    }

    /* Voorkom dat admin-table CSS [hidden] overschrijft */
    #content #portal-user-add-form tr.portal-manual-fields[hidden],
    #content #portal-user-add-form tr.portal-existing-fields[hidden] {
        display: none !important;
    }

    #portal-existing-user + .kt-select-wrapper,
    .kt-select-wrapper:has(#portal-existing-user) {
        width: fit-content !important;
        max-width: 100%;
        position: relative !important;
        overflow: visible !important;
        height: auto !important;
    }

    #portal-existing-user + .kt-select-wrapper .kt-select-display,
    #portal-existing-user + .kt-select-wrapper [data-kt-select-display],
    .kt-select-wrapper:has(#portal-existing-user) .kt-select-display,
    .kt-select-wrapper:has(#portal-existing-user) [data-kt-select-display] {
        width: max-content !important;
        max-width: 100%;
    }

    /* Niet position forceren: Popper zet absolute/fixed. min-width:100% is bij
       position:fixed de viewportbreedte — daarom een vaste, compacte breedte. */
    #portal-existing-user + .kt-select-wrapper .kt-select-dropdown,
    #portal-existing-user + .kt-select-wrapper [data-kt-select-dropdown],
    .kt-select-wrapper:has(#portal-existing-user) .kt-select-dropdown,
    .kt-select-wrapper:has(#portal-existing-user) [data-kt-select-dropdown] {
        width: min(28rem, calc(100vw - 2.5rem)) !important;
        min-width: 16rem !important;
        max-width: min(28rem, calc(100vw - 2.5rem)) !important;
        z-index: 200 !important;
        box-sizing: border-box !important;
    }

    #portal-existing-user + .kt-select-wrapper .kt-select-dropdown:not(.open),
    #portal-existing-user + .kt-select-wrapper [data-kt-select-dropdown]:not(.open),
    .kt-select-wrapper:has(#portal-existing-user) .kt-select-dropdown:not(.open),
    .kt-select-wrapper:has(#portal-existing-user) [data-kt-select-dropdown]:not(.open) {
        display: none !important;
        height: 0 !important;
        max-height: 0 !important;
        overflow: hidden !important;
        visibility: hidden !important;
        pointer-events: none !important;
        opacity: 0 !important;
    }

    #portal-existing-user + .kt-select-wrapper .kt-select-dropdown.open,
    #portal-existing-user + .kt-select-wrapper [data-kt-select-dropdown].open,
    .kt-select-wrapper:has(#portal-existing-user) .kt-select-dropdown.open,
    .kt-select-wrapper:has(#portal-existing-user) [data-kt-select-dropdown].open {
        display: flex !important;
        flex-direction: column !important;
        max-height: min(16rem, 50vh) !important;
        overflow: hidden !important;
        visibility: visible !important;
        pointer-events: auto !important;
        opacity: 1 !important;
        height: auto !important;
    }

    #portal-existing-user + .kt-select-wrapper .kt-select-options,
    #portal-existing-user + .kt-select-wrapper [data-kt-select-options],
    .kt-select-wrapper:has(#portal-existing-user) .kt-select-options,
    .kt-select-wrapper:has(#portal-existing-user) [data-kt-select-options] {
        flex: 1 1 auto;
        min-height: 0 !important;
        max-height: min(14rem, 45vh) !important;
        overflow-x: hidden !important;
        overflow-y: auto !important;
        overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch;
        width: 100% !important;
        min-width: 0 !important;
        max-width: 100% !important;
        scrollbar-width: thin;
        scrollbar-color: color-mix(in srgb, var(--muted-foreground) 50%, transparent) var(--popover);
    }

    #portal-existing-user + .kt-select-wrapper .kt-select-options::-webkit-scrollbar,
    #portal-existing-user + .kt-select-wrapper [data-kt-select-options]::-webkit-scrollbar,
    .kt-select-wrapper:has(#portal-existing-user) .kt-select-options::-webkit-scrollbar,
    .kt-select-wrapper:has(#portal-existing-user) [data-kt-select-options]::-webkit-scrollbar {
        width: 0.5rem;
        height: 0.5rem;
        background: transparent;
    }

    #portal-existing-user + .kt-select-wrapper .kt-select-options::-webkit-scrollbar-track,
    #portal-existing-user + .kt-select-wrapper [data-kt-select-options]::-webkit-scrollbar-track,
    #portal-existing-user + .kt-select-wrapper .kt-select-options::-webkit-scrollbar-track-piece,
    #portal-existing-user + .kt-select-wrapper [data-kt-select-options]::-webkit-scrollbar-track-piece,
    #portal-existing-user + .kt-select-wrapper .kt-select-options::-webkit-scrollbar-corner,
    #portal-existing-user + .kt-select-wrapper [data-kt-select-options]::-webkit-scrollbar-corner,
    .kt-select-wrapper:has(#portal-existing-user) .kt-select-options::-webkit-scrollbar-track,
    .kt-select-wrapper:has(#portal-existing-user) [data-kt-select-options]::-webkit-scrollbar-track,
    .kt-select-wrapper:has(#portal-existing-user) .kt-select-options::-webkit-scrollbar-track-piece,
    .kt-select-wrapper:has(#portal-existing-user) [data-kt-select-options]::-webkit-scrollbar-track-piece,
    .kt-select-wrapper:has(#portal-existing-user) .kt-select-options::-webkit-scrollbar-corner,
    .kt-select-wrapper:has(#portal-existing-user) [data-kt-select-options]::-webkit-scrollbar-corner {
        background: var(--popover, transparent) !important;
        box-shadow: none !important;
        border: none !important;
    }

    #portal-existing-user + .kt-select-wrapper .kt-select-options::-webkit-scrollbar-thumb,
    #portal-existing-user + .kt-select-wrapper [data-kt-select-options]::-webkit-scrollbar-thumb,
    .kt-select-wrapper:has(#portal-existing-user) .kt-select-options::-webkit-scrollbar-thumb,
    .kt-select-wrapper:has(#portal-existing-user) [data-kt-select-options]::-webkit-scrollbar-thumb {
        background-color: color-mix(in srgb, var(--muted-foreground) 40%, transparent);
        border-radius: 9999px;
        border: 2px solid transparent;
        background-clip: padding-box;
    }

    #portal-existing-user + .kt-select-wrapper .kt-select-options::-webkit-scrollbar-thumb:hover,
    #portal-existing-user + .kt-select-wrapper [data-kt-select-options]::-webkit-scrollbar-thumb:hover,
    .kt-select-wrapper:has(#portal-existing-user) .kt-select-options::-webkit-scrollbar-thumb:hover,
    .kt-select-wrapper:has(#portal-existing-user) [data-kt-select-options]::-webkit-scrollbar-thumb:hover {
        background-color: color-mix(in srgb, var(--muted-foreground) 65%, transparent);
    }

    #portal-existing-user + .kt-select-wrapper .kt-select-option,
    #portal-existing-user + .kt-select-wrapper [data-kt-select-option],
    #portal-existing-user + .kt-select-wrapper .kt-select-option-text,
    .kt-select-wrapper:has(#portal-existing-user) .kt-select-option,
    .kt-select-wrapper:has(#portal-existing-user) [data-kt-select-option],
    .kt-select-wrapper:has(#portal-existing-user) .kt-select-option-text {
        min-width: 0 !important;
        max-width: 100% !important;
        width: 100% !important;
        box-sizing: border-box !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
        word-break: normal !important;
        word-wrap: normal !important;
    }

    #content .kt-card:has(#portal-user-add-form) > .kt-card-content {
        overflow: visible !important;
    }

    #portal-existing-user + .kt-select-wrapper .kt-select-search,
    #portal-existing-user + .kt-select-wrapper .kt-select-search .kt-input,
    #portal-user-add-form input[data-kt-select-search] {
        width: 100% !important;
        max-width: 100% !important;
        min-width: 0 !important;
        box-sizing: border-box !important;
        padding-inline: 0.75rem !important;
    }
</style>
@endpush

@push('scripts')
<script>
(function() {
    document.addEventListener('click', function(event) {
        if (event.target.closest('[data-portal-add-open]')) {
            event.preventDefault();
            var section = document.getElementById('portal-user-add-section');
            var toggle = document.getElementById('portal-user-add-toggle');
            if (!section) {
                return;
            }
            section.hidden = false;
            section.classList.remove('portal-user-add-card--collapsed');
            if (toggle) {
                toggle.hidden = true;
                toggle.setAttribute('aria-expanded', 'true');
            }
            section.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            var first = document.getElementById('portal-first-name');
            if (first && !first.disabled) {
                window.setTimeout(function() { first.focus(); }, 80);
            }
            return;
        }
        if (event.target.closest('[data-portal-add-close]')) {
            event.preventDefault();
            var addSection = document.getElementById('portal-user-add-section');
            var addToggle = document.getElementById('portal-user-add-toggle');
            if (!addSection) {
                return;
            }
            addSection.hidden = true;
            addSection.classList.add('portal-user-add-card--collapsed');
            if (addToggle) {
                addToggle.hidden = false;
                addToggle.setAttribute('aria-expanded', 'false');
            }
        }
    });
})();
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', function(event) {
        var tile = event.target.closest('[data-customer-sections] a[data-customer-section]');
        if (!tile || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }
        var section = tile.getAttribute('data-customer-section');
        if (!section) {
            return;
        }
        event.preventDefault();
        document.querySelectorAll('[data-customer-sections] a[data-customer-section]').forEach(function(item) {
            var active = item.getAttribute('data-customer-section') === section;
            item.classList.toggle('is-active', active);
            if (active) {
                item.setAttribute('aria-current', 'page');
            } else {
                item.removeAttribute('aria-current');
            }
        });
        document.querySelectorAll('[data-customer-section-panel]').forEach(function(panel) {
            panel.classList.toggle('is-active', panel.getAttribute('data-customer-section-panel') === section);
        });
        var url = new URL(window.location.href);
        url.searchParams.set('section', section);
        window.history.replaceState({}, '', url.toString());
    });

    document.querySelectorAll('#transport-customer-contracts-table tr[data-row-href]').forEach(function(row) {
        row.addEventListener('click', function(event) {
            if (event.target.closest('[data-no-row-link]')) {
                return;
            }
            window.location.href = row.getAttribute('data-row-href');
        });

        row.addEventListener('keydown', function(event) {
            if (event.key === 'Enter' || event.key === ' ') {
                if (event.target.closest('[data-no-row-link]')) {
                    return;
                }
                event.preventDefault();
                window.location.href = row.getAttribute('data-row-href');
            }
        });
    });

    var modeNew = document.getElementById('portal-mode-new');
    var modeExisting = document.getElementById('portal-mode-existing');
    var existingSelect = document.getElementById('portal-existing-user');
    var firstNameInput = document.getElementById('portal-first-name');
    var lastNameInput = document.getElementById('portal-last-name');
    var emailInput = document.getElementById('portal-email');
    var passwordInput = null;
    var manualRows = document.querySelectorAll('#portal-user-add-form .portal-manual-fields');
    var existingRows = document.querySelectorAll('#portal-user-add-form .portal-existing-fields');

    function setFieldEnabled(el, enabled) {
        if (!el) {
            return;
        }
        el.disabled = !enabled;
        el.required = false;

        var wrapper = el.closest('[data-kt-select-wrapper], .kt-select-wrapper');
        if (!wrapper) {
            return;
        }
        wrapper.classList.toggle('pointer-events-none', !enabled);
        wrapper.classList.toggle('opacity-60', !enabled);
        wrapper.setAttribute('aria-disabled', enabled ? 'false' : 'true');
        var display = wrapper.querySelector('[data-kt-select-display]');
        if (display) {
            if (enabled) {
                display.removeAttribute('tabindex');
            } else {
                display.setAttribute('tabindex', '-1');
            }
        }
    }

    function syncPortalUserMode() {
        var isExisting = !!(modeExisting && modeExisting.checked);
        manualRows.forEach(function(row) {
            row.hidden = isExisting;
        });
        existingRows.forEach(function(row) {
            row.hidden = !isExisting;
        });

        setFieldEnabled(existingSelect, isExisting);
        setFieldEnabled(firstNameInput, !isExisting);
        setFieldEnabled(lastNameInput, !isExisting);
        setFieldEnabled(emailInput, !isExisting);
        setFieldEnabled(passwordInput, !isExisting);

        if (passwordInput && isExisting) {
            passwordInput.value = '';
        }
    }

    if (modeNew) {
        modeNew.addEventListener('change', syncPortalUserMode);
    }
    if (modeExisting) {
        modeExisting.addEventListener('change', syncPortalUserMode);
    }
    syncPortalUserMode();

    function portalExistingUserWrapper() {
        if (!existingSelect) {
            return null;
        }
        if (existingSelect.nextElementSibling && existingSelect.nextElementSibling.classList.contains('kt-select-wrapper')) {
            return existingSelect.nextElementSibling;
        }
        return existingSelect.closest('.kt-select-wrapper');
    }

    function closePortalExistingUserDropdown() {
        if (!existingSelect) {
            return;
        }
        if (typeof window.KTSelect !== 'undefined' && typeof window.KTSelect.getInstance === 'function') {
            var instance = window.KTSelect.getInstance(existingSelect);
            if (instance && typeof instance.closeDropdown === 'function') {
                instance.closeDropdown();
            }
        }
        var wrapper = portalExistingUserWrapper();
        if (!wrapper) {
            return;
        }
        var dropdown = wrapper.querySelector('.kt-select-dropdown, [data-kt-select-dropdown]');
        if (dropdown) {
            dropdown.classList.remove('open');
            dropdown.classList.add('hidden');
            dropdown.style.display = 'none';
            dropdown.style.opacity = '0';
            dropdown.style.visibility = 'hidden';
        }
        var display = wrapper.querySelector('.kt-select-display, [data-kt-select-display]');
        if (display) {
            display.classList.remove('active');
            display.setAttribute('aria-expanded', 'false');
        }
    }

    if (existingSelect) {
        existingSelect.addEventListener('change', closePortalExistingUserDropdown);
        var existingWrapper = portalExistingUserWrapper();
        if (existingWrapper) {
            existingWrapper.addEventListener('change', closePortalExistingUserDropdown);
            existingWrapper.addEventListener('click', function(event) {
                if (!event.target.closest('[data-kt-select-option]')) {
                    return;
                }
                window.setTimeout(closePortalExistingUserDropdown, 0);
            });
        }
    }

    var addForm = document.getElementById('portal-user-add-form');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            syncPortalUserMode();

            var isExisting = !!(modeExisting && modeExisting.checked);
            var messages = [];

            if (isExisting) {
                if (!existingSelect || !existingSelect.value) {
                    messages.push('Kies een bestaande gebruiker.');
                }
            } else {
                if (!firstNameInput || !String(firstNameInput.value || '').trim()) {
                    messages.push('Voornaam is verplicht.');
                }
                if (!emailInput || !String(emailInput.value || '').trim()) {
                    messages.push('E-mailadres is verplicht.');
                }
            }

            if (messages.length) {
                e.preventDefault();
                var box = document.getElementById('portal-user-add-client-errors');
                if (!box) {
                    box = document.createElement('div');
                    box.id = 'portal-user-add-client-errors';
                    box.className = 'kt-alert kt-alert-danger mb-4';
                    box.setAttribute('role', 'alert');
                    var heading = addForm.parentElement && addForm.parentElement.querySelector('h4');
                    if (heading && heading.parentElement) {
                        heading.insertAdjacentElement('afterend', box);
                    } else {
                        addForm.insertAdjacentElement('beforebegin', box);
                    }
                }
                box.innerHTML = '<ul class="list-disc list-inside mb-0">' + messages.map(function(m) {
                    return '<li>' + m + '</li>';
                }).join('') + '</ul>';
                box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });
    }

    document.querySelectorAll('.portal-users-stack select[name="portal_role"]').forEach(function(select) {
        select.addEventListener('change', function() {
            if (select.value !== 'contractant') {
                return;
            }
            var form = select.closest('form');
            if (!form) {
                return;
            }
            form.querySelectorAll('input[name="passenger_ids[]"]').forEach(function(cb) {
                cb.checked = true;
            });
            var selectAll = form.querySelector('.portal-passenger-select-all');
            if (selectAll) {
                selectAll.checked = true;
                selectAll.indeterminate = false;
            }
        });
    });

    document.querySelectorAll('[data-portal-passengers]').forEach(function(picker) {
        var selectAll = picker.querySelector('.portal-passenger-select-all');
        var checks = Array.prototype.slice.call(picker.querySelectorAll('.portal-passenger-check'));
        if (!selectAll || !checks.length) {
            return;
        }

        function syncSelectAllState() {
            var checkedCount = checks.filter(function(cb) { return cb.checked; }).length;
            selectAll.checked = checkedCount === checks.length;
            selectAll.indeterminate = checkedCount > 0 && checkedCount < checks.length;
        }

        selectAll.addEventListener('change', function() {
            checks.forEach(function(cb) {
                cb.checked = selectAll.checked;
            });
            selectAll.indeterminate = false;
        });

        checks.forEach(function(cb) {
            cb.addEventListener('change', syncSelectAllState);
        });

        syncSelectAllState();
    });
});
</script>
@endpush
