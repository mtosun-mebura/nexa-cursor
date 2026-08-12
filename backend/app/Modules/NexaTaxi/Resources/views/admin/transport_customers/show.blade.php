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

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5" role="alert">
            <i class="ki-filled ki-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif

    <div class="grid gap-5 lg:gap-7.5">

        {{-- Klantdetails --}}
        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header">
                <h3 class="kt-card-title mb-0">Klantdetails</h3>
            </div>
            <div class="kt-card-content p-0">
                <div class="px-3 sm:px-5 pb-3 min-w-0">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground w-full">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-medium">Status</td>
                            <td>
                                @if($customer->active)
                                    <span class="kt-badge kt-badge-success kt-badge-sm">Actief</span>
                                @else
                                    <span class="kt-badge kt-badge-secondary kt-badge-sm">Inactief</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-medium">Contactpersoon</td>
                            <td>{{ $customer->contact_name ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-medium">E-mail</td>
                            <td>{{ $customer->contact_email ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-medium">Telefoon</td>
                            <td>{{ $customer->contact_phone ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-medium">Debiteurnummer</td>
                            <td>{{ $customer->debtor_number ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-medium">Factuuradres</td>
                            <td>
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
                            <td class="text-secondary-foreground font-medium">Notities</td>
                            <td class="whitespace-pre-wrap">{{ $customer->notes }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        {{-- Contractportaal --}}
        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header">
                <div class="min-w-0">
                    <h3 class="kt-card-title mb-0">Contractportaal</h3>
                    <p class="text-sm text-muted-foreground mt-1.5 mb-0">
                        App voor schoolcontact / ouders:
                        <a href="{{ url('/taxi/contract') }}" target="_blank" rel="noopener" class="text-primary hover:underline break-all">/taxi/contract</a>
                    </p>
                </div>
            </div>
            <div class="kt-card-content p-0 min-w-0">
                <div class="portal-users-stack divide-y">
                    @if(($portalUsers ?? collect())->isNotEmpty())
                        @foreach($portalUsers as $portalUser)
                            @php
                                $u = ($portalUserModels ?? collect())->get($portalUser->user_id);
                                $linkedPassengers = collect($guardianMap ?? [])->get((int) $portalUser->user_id, []);
                                $portalName = $u ? trim($u->first_name.' '.$u->last_name) : 'Gebruiker #'.$portalUser->user_id;
                            @endphp
                            <div class="px-3 sm:px-5 py-4 min-w-0">
                                <form method="POST" action="{{ route('admin.taxi.transport_customers.portal.update', [$customer->id, $portalUser->id]) }}" class="min-w-0">
                                    @csrf
                                    @method('PUT')
                                    <div class="flex flex-wrap items-start justify-between gap-2 mb-3">
                                        <div class="min-w-0">
                                            <div class="font-medium text-foreground break-words">{{ $portalName }}</div>
                                            <div class="text-sm text-muted-foreground break-all">{{ $u?->email ?? '—' }}</div>
                                        </div>
                                        @if($portalUser->active)
                                            <span class="kt-badge kt-badge-success kt-badge-sm shrink-0">Actief</span>
                                        @else
                                            <span class="kt-badge kt-badge-secondary kt-badge-sm shrink-0">Inactief</span>
                                        @endif
                                    </div>
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
                                    @method('DELETE')
                                </form>
                                @endcan
                            </div>
                        @endforeach
                    @else
                        <div class="px-3 sm:px-5 py-4">
                            <p class="text-sm text-muted-foreground mb-0">Nog geen portaalgebruikers gekoppeld.</p>
                        </div>
                    @endif

                    @can('rides.update')
                    <div class="px-3 sm:px-5 py-4 min-w-0" id="portal-user-add-section">
                        <h4 class="text-sm font-medium text-foreground mb-3">Portaalgebruiker toevoegen</h4>
                    @if(session('success'))
                        <div class="kt-alert kt-alert-success mb-4" role="alert">
                            <i class="ki-filled ki-check-circle me-2"></i> {{ session('success') }}
                        </div>
                    @endif
                    @if($errors->any())
                        <div class="kt-alert kt-alert-danger mb-4" role="alert">
                            <ul class="list-disc list-inside mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form method="POST" action="{{ route('admin.taxi.transport_customers.portal.store', $customer->id) }}" class="min-w-0" id="portal-user-add-form" novalidate>
                        @csrf
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
                                    <select id="portal-existing-user" name="existing_user_id" class="kt-select w-full" @if($manualMode) disabled @endif>
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
                                </td>
                            </tr>
                            <tr class="portal-manual-fields" @if(! $manualMode) hidden @endif>
                                <td class="text-secondary-foreground font-normal">Wachtwoord <span class="text-danger">*</span></td>
                                <td>
                                    <input id="portal-password" type="password" name="password" class="kt-input w-full" autocomplete="new-password" placeholder="Minimaal 8 tekens" maxlength="100" @if(! $manualMode) disabled @endif>
                                    @error('password')
                                        <p class="text-xs text-danger mt-1.5 mb-0">{{ $message }}</p>
                                    @enderror
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
                    @endcan
                </div>
            </div>
        </div>

        {{-- Abonnementen --}}
        <div class="kt-card kt-card-grid w-full min-w-0">
            <div class="kt-card-header">
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

        {{-- Verstoringen / meldingen voor contractportaal --}}
        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header">
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
                                            <input type="datetime-local" name="starts_at" class="kt-input w-full" value="{{ optional($announcement->starts_at)->format('Y-m-d\\TH:i') }}">
                                        </div>
                                        <div>
                                            <label class="text-sm text-muted-foreground">Einde</label>
                                            <input type="datetime-local" name="ends_at" class="kt-input w-full" value="{{ optional($announcement->ends_at)->format('Y-m-d\\TH:i') }}">
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

                <div class="px-3 sm:px-5 py-4 border-t min-w-0">
                    <h4 class="font-medium text-foreground mb-3">Nieuwe melding</h4>
                    <form method="POST" action="{{ route('admin.taxi.transport_customers.announcements.store', $customer->id) }}" class="space-y-3">
                        @csrf
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
                                <input type="datetime-local" name="starts_at" class="kt-input w-full" value="{{ old('starts_at') }}">
                            </div>
                            <div>
                                <label class="text-sm text-muted-foreground">Einde (optioneel)</label>
                                <input type="datetime-local" name="ends_at" class="kt-input w-full" value="{{ old('ends_at') }}">
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
@endsection

@push('styles')
<style>
    #content #transport-customer-contracts-table .transport-customers-table__actions-col {
        width: 4.5rem !important;
        min-width: 4.5rem !important;
        max-width: 4.5rem !important;
        padding-inline: 0.375rem !important;
        text-align: center !important;
        vertical-align: middle !important;
        white-space: nowrap;
    }

    /* Zelfde lijnkleur als .kt-card-header (divide-y gebruikt border-bottom) */
    #content .portal-users-stack.divide-y > :not(:last-child) {
        border-bottom-style: solid !important;
        border-bottom-width: 1px !important;
        border-bottom-color: var(--border) !important;
    }

    /* Voorkom dat admin-table CSS [hidden] overschrijft */
    #content #portal-user-add-form tr.portal-manual-fields[hidden],
    #content #portal-user-add-form tr.portal-existing-fields[hidden] {
        display: none !important;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
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
    var passwordInput = document.getElementById('portal-password');
    var manualRows = document.querySelectorAll('#portal-user-add-form .portal-manual-fields');
    var existingRows = document.querySelectorAll('#portal-user-add-form .portal-existing-fields');

    function setFieldEnabled(el, enabled) {
        if (!el) {
            return;
        }
        el.disabled = !enabled;
        el.required = false;
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
                var pw = passwordInput ? String(passwordInput.value || '') : '';
                if (pw.length < 8) {
                    messages.push('Wachtwoord moet minimaal 8 tekens zijn.');
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
