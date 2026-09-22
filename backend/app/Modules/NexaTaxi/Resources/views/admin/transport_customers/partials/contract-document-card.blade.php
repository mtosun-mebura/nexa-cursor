@php
    $latestContract = ($latestContract ?? null);
    $carrierCompany = $carrierCompany ?? $tenantCompany ?? null;
    $tenantName = trim((string) ($carrierCompany->name ?? ''));
    if ($tenantName === '') {
        $tenantName = 'NEXA Taxi';
    }
    $typeKey = $customer->organizationTypeKey();
    $typeLabel = $customer->organizationTypeLabel();
    $addressLines = $customer->billingAddressLines();
    $isActive = (bool) $customer->active;
    $period = 'Nog geen abonnement';
    $contractTitle = 'Vervoersovereenkomst';
    if ($latestContract) {
        $contractTitle = trim((string) $latestContract->name) !== ''
            ? (string) $latestContract->name
            : $contractTitle;
        $from = $latestContract->start_date?->format('d-m-Y');
        $until = $latestContract->end_date?->format('d-m-Y');
        $period = ($from ?: 'n.t.b.').' – '.($until ?: 'open einde');
    }
    $showHref = route('admin.taxi.transport_customers.show', $customer->id);
@endphp
<a
    href="{{ $showHref }}"
    class="transport-contract-doc {{ $isActive ? 'is-active' : 'is-inactive' }}"
    data-contract-card
    data-customer-id="{{ $customer->id }}"
    aria-label="Bekijk contract van {{ $customer->name }}"
>
    <div class="transport-contract-doc__banner" aria-hidden="true">
        <span class="transport-contract-doc__status">
            <span class="transport-contract-doc__status-dot"></span>
            {{ $isActive ? 'Actief contract' : 'Inactief contract' }}
        </span>
        <span class="transport-contract-doc__ref">Nr. {{ $customer->debtor_number ?: $customer->id }}</span>
    </div>
    <div class="transport-contract-doc__body">
        <div class="transport-contract-doc__letterhead">
            <p class="transport-contract-doc__kicker">Officiële overeenkomst</p>
            <span class="transport-contract-doc__type transport-contract-doc__type--{{ $typeKey }}">{{ $typeLabel }}</span>
        </div>
        <h3 class="transport-contract-doc__title">{{ $customer->name }}</h3>
        <p class="transport-contract-doc__subtitle">{{ $contractTitle }}</p>

        <dl class="transport-contract-doc__parties">
            <div>
                <dt>Opdrachtgever</dt>
                <dd>{{ $customer->name }}</dd>
            </div>
            <div>
                <dt>Vervoerder</dt>
                <dd>{{ $tenantName }}</dd>
            </div>
        </dl>

        @if($addressLines !== [])
            <p class="transport-contract-doc__section-label">Vestigingsgegevens</p>
            <p class="transport-contract-doc__address">
                @foreach($addressLines as $line)
                    {{ $line }}@if(! $loop->last)<br>@endif
                @endforeach
            </p>
        @endif

        <div class="transport-contract-doc__contact">
            <p class="transport-contract-doc__section-label">Contactpersoon</p>
            <p class="transport-contract-doc__contact-name">{{ $customer->contact_name ?: '—' }}</p>
            @if($customer->contact_email)
                <p>{{ $customer->contact_email }}</p>
            @endif
            @if($customer->contact_phone)
                <p>{{ $customer->contact_phone }}</p>
            @endif
        </div>

        <p class="transport-contract-doc__meta">Looptijd: {{ $period }}</p>

        <div class="transport-contract-doc__sign">
            <div class="transport-contract-doc__sign-col">
                <svg class="transport-contract-doc__signature" viewBox="0 0 160 36" aria-hidden="true">
                    <path d="M4 26c12-18 22-4 28-8 8-5 10-14 22-12 9 2 8 16 18 16 11 0 12-14 24-13 10 1 9 14 20 13 8-1 12-10 22-9 6 .6 10 6 16 8" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                    <path d="M38 30h86" fill="none" stroke="currentColor" stroke-width="0.8" opacity="0.45"/>
                </svg>
                <span>Namens opdrachtgever</span>
                <strong>{{ $customer->contact_name ?: $customer->name }}</strong>
            </div>
            <div class="transport-contract-doc__sign-col transport-contract-doc__sign-col--seal">
                <span class="transport-contract-doc__seal" aria-hidden="true">
                    <span>{{ $isActive ? 'Getekend' : 'Inactief' }}</span>
                </span>
                <span>Namens {{ $tenantName }}</span>
                <strong>Vervoerder</strong>
            </div>
        </div>
    </div>
</a>
