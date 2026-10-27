@php
    $latestContract = ($latestContract ?? null);
    $carrierCompany = $carrierCompany ?? $tenantCompany ?? null;
    $forceDelete = (bool) ($forceDelete ?? false);
    $tenantName = trim((string) ($carrierCompany->name ?? ''));
    if ($tenantName === '') {
        $tenantName = __('NEXA Taxi');
    }
    $typeKey = $customer->organizationTypeKey();
    $typeLabel = $customer->organizationTypeLabel();
    $addressLines = $customer->billingAddressLines();
    $isArchived = $customer->isArchived();
    $isActive = ! $isArchived && (bool) $customer->active;
    $period = __('Nog geen abonnement');
    $contractTitle = __('Vervoersovereenkomst');
    if ($latestContract) {
        $contractTitle = trim((string) $latestContract->name) !== ''
            ? (string) $latestContract->name
            : $contractTitle;
        $from = $latestContract->start_date?->format('d-m-Y');
        $until = $latestContract->end_date?->format('d-m-Y');
        $period = ($from ?: __('n.t.b.')).' – '.($until ?: __('open einde'));
    }
    $showHref = route('admin.taxi.transport_customers.show', $customer->id);
    $statusLabel = $isArchived ? __('Gearchiveerd') : ($isActive ? __('Actief contract') : __('Inactief contract'));
    $docClass = $isArchived ? 'is-archived' : ($isActive ? 'is-active' : 'is-inactive');
    $deleteAction = $forceDelete
        ? route('admin.taxi.transport_customers.force_destroy', $customer->id)
        : route('admin.taxi.transport_customers.destroy', $customer->id);
    $deleteMode = $forceDelete ? 'force' : 'archive';
    $deleteTitle = $forceDelete ? __('Definitief verwijderen') : __('Naar archief');
@endphp
<article
    class="transport-contract-doc-wrap"
    data-contract-card
    data-customer-id="{{ $customer->id }}"
>
    <a
        href="{{ $showHref }}"
        class="transport-contract-doc {{ $docClass }}"
        aria-label="{{ __('Bekijk contract van :name', ['name' => $customer->name]) }}"
    >
        <div class="transport-contract-doc__banner" aria-hidden="true">
            <span class="transport-contract-doc__status">
                <span class="transport-contract-doc__status-dot"></span>
                {{ $statusLabel }}
            </span>
            <span class="transport-contract-doc__ref">{{ __('Nr. :id', ['id' => $customer->debtor_number ?: $customer->id]) }}</span>
        </div>
        <div class="transport-contract-doc__body">
            <div class="transport-contract-doc__letterhead">
                <p class="transport-contract-doc__kicker">{{ __('Officiële overeenkomst') }}</p>
                <span class="transport-contract-doc__type transport-contract-doc__type--{{ $typeKey }}">{{ __($typeLabel) }}</span>
            </div>
            <h3 class="transport-contract-doc__title">{{ $customer->name }}</h3>
            <p class="transport-contract-doc__subtitle">{{ $contractTitle }}</p>

            <dl class="transport-contract-doc__parties">
                <div>
                    <dt>{{ __('Opdrachtgever') }}</dt>
                    <dd>{{ $customer->name }}</dd>
                </div>
                <div>
                    <dt>{{ __('Vervoerder') }}</dt>
                    <dd>{{ $tenantName }}</dd>
                </div>
            </dl>

            @if($addressLines !== [])
                <p class="transport-contract-doc__section-label">{{ __('Vestigingsgegevens') }}</p>
                <p class="transport-contract-doc__address">
                    @foreach($addressLines as $line)
                        {{ $line }}@if(! $loop->last)<br>@endif
                    @endforeach
                </p>
            @endif

            <div class="transport-contract-doc__contact">
                <p class="transport-contract-doc__section-label">{{ __('Contactpersoon') }}</p>
                <p class="transport-contract-doc__contact-name">{{ $customer->contact_name ?: '—' }}</p>
                @if($customer->contact_email)
                    <p>{{ $customer->contact_email }}</p>
                @endif
                @if($customer->contact_phone)
                    <p>{{ $customer->contact_phone }}</p>
                @endif
            </div>

            <p class="transport-contract-doc__meta">{{ __('Looptijd: :period', ['period' => $period]) }}</p>
            @if($isArchived && $customer->archived_at)
                <p class="transport-contract-doc__meta">{{ __('Gearchiveerd: :date', ['date' => $customer->archived_at->format('d-m-Y')]) }}</p>
            @endif

            <div class="transport-contract-doc__sign">
                <div class="transport-contract-doc__sign-col">
                    <svg class="transport-contract-doc__signature" viewBox="0 0 160 36" aria-hidden="true">
                        <path d="M4 26c12-18 22-4 28-8 8-5 10-14 22-12 9 2 8 16 18 16 11 0 12-14 24-13 10 1 9 14 20 13 8-1 12-10 22-9 6 .6 10 6 16 8" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                        <path d="M38 30h86" fill="none" stroke="currentColor" stroke-width="0.8" opacity="0.45"/>
                    </svg>
                    <span>{{ __('Namens opdrachtgever') }}</span>
                    <strong>{{ $customer->contact_name ?: $customer->name }}</strong>
                </div>
                <div class="transport-contract-doc__sign-col transport-contract-doc__sign-col--seal">
                    <span class="transport-contract-doc__seal" aria-hidden="true">
                        <span>{{ $isArchived ? __('Archief') : ($isActive ? __('Getekend') : __('Inactief')) }}</span>
                    </span>
                    <span>{{ __('Namens :tenant', ['tenant' => $tenantName]) }}</span>
                    <strong>{{ __('Vervoerder') }}</strong>
                </div>
            </div>
        </div>
    </a>

    <div class="transport-contract-doc__actions" onclick="event.stopPropagation();">
        @if($isArchived)
            @can('rides.update')
            <div class="transport-contract-doc__action-col">
                <form
                    method="POST"
                    action="{{ route('admin.taxi.transport_customers.restore', $customer->id) }}"
                    class="transport-contract-doc__restore-form"
                >
                    @csrf
                    <button
                        type="submit"
                        class="transport-contract-doc__action-btn transport-contract-doc__restore"
                        title="{{ __('Contract herstellen en activeren') }}"
                        aria-label="{{ __('Contract herstellen en activeren: :name', ['name' => $customer->name]) }}"
                    >
                        <i class="ki-filled ki-arrows-circle" aria-hidden="true"></i>
                    </button>
                </form>
                <span class="transport-contract-doc__action-label">{{ __('Herstellen') }}</span>
            </div>
            <div class="transport-contract-doc__action-col">
                <label class="transport-contract-doc__keep-past">
                    <input
                        type="checkbox"
                        class="kt-switch kt-switch-sm"
                        name="keep_past_rides"
                        value="1"
                        @checked($customer->archive_keep_past_rides)
                        data-archive-keep-past
                        data-action="{{ route('admin.taxi.transport_customers.archive_keep_past_rides', $customer->id) }}"
                        aria-label="{{ __('Verleden ritten zichtbaar houden in planning en agenda') }}"
                        title="{{ __('Verleden ritten zichtbaar houden in planning/agenda (alleen vóór vandaag)') }}"
                    >
                </label>
                <span class="transport-contract-doc__action-label">{{ __('Verleden ritten') }}</span>
            </div>
            @endcan
        @endif
        @can('rides.delete')
        <div class="transport-contract-doc__action-col">
            <button
                type="button"
                class="transport-contract-doc__action-btn transport-contract-doc__delete"
                data-transport-customer-delete
                data-delete-mode="{{ $deleteMode }}"
                data-action="{{ $deleteAction }}"
                data-label="{{ $customer->name }}"
                title="{{ $deleteTitle }}"
                aria-label="{{ $deleteTitle }}: {{ $customer->name }}"
            >
                <i class="ki-filled ki-trash" aria-hidden="true"></i>
            </button>
            <span class="transport-contract-doc__action-label">{{ $forceDelete ? __('Verwijderen') : __('Archiveren') }}</span>
        </div>
        @endcan
    </div>
</article>
