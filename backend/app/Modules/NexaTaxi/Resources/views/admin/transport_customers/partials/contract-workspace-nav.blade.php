@php
    /** @var \App\Modules\NexaTaxi\Models\TransportCustomer $customer */
    /** @var \App\Modules\NexaTaxi\Models\TransportContract $contract */
    $activeTab = $contractWorkspaceTab ?? 'overzicht';
    $counts = $contractWorkspaceCounts ?? [];
    $overzichtUrl = route('admin.taxi.transport_customers.contract_show', [$customer->id, $contract->id]);
    $facturenUrl = route('admin.taxi.transport_customers.contract_show', [
        $customer->id,
        $contract->id,
        'tab' => 'facturen',
    ]);
    $tabs = [
        [
            'key' => 'overzicht',
            'label' => 'Overzicht',
            'hint' => 'Abonnement & SEPA',
            'url' => $overzichtUrl,
            'count' => null,
        ],
        [
            'key' => 'passagiers',
            'label' => 'Passagiers',
            'hint' => 'Personen die meereizen',
            'url' => route('admin.taxi.transport_passengers.index', [$customer->id, $contract->id]),
            'count' => $counts['passengers'] ?? null,
        ],
        [
            'key' => 'groepen',
            'label' => 'Groepen',
            'hint' => 'Vaste routes heen/terug',
            'url' => route('admin.taxi.transport_groups.index', [$customer->id, $contract->id]),
            'count' => $counts['groups'] ?? null,
        ],
        [
            'key' => 'ritten',
            'label' => 'Individuele ritten',
            'hint' => 'Eenmalige contractritten',
            'url' => route('admin.taxi.transport_individual_bookings.index', [$customer->id, $contract->id]),
            'count' => $counts['bookings'] ?? null,
        ],
        [
            'key' => 'facturen',
            'label' => 'Facturen',
            'hint' => 'Maandfacturen',
            'url' => $facturenUrl,
            'count' => $counts['invoices'] ?? null,
        ],
    ];
@endphp

<nav class="contract-workspace-nav" aria-label="Abonnement-onderdelen">
    <div class="contract-workspace-nav__scroll">
        @foreach($tabs as $tab)
            <a href="{{ $tab['url'] }}"
               class="contract-workspace-nav__tab{{ $activeTab === $tab['key'] ? ' is-active' : '' }}"
               @if($activeTab === $tab['key']) aria-current="page" @endif>
                <span class="contract-workspace-nav__label">
                    {{ $tab['label'] }}
                    @if($tab['count'] !== null)
                        <span class="contract-workspace-nav__count">{{ $tab['count'] }}</span>
                    @endif
                </span>
                <span class="contract-workspace-nav__hint">{{ $tab['hint'] }}</span>
            </a>
        @endforeach
    </div>
</nav>
