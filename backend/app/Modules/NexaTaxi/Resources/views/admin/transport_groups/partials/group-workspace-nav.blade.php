@php
    $activeTab = $groupWorkspaceTab ?? 'gegevens';
    $memberCount = $groupWorkspaceMemberCount ?? null;
    $gegevensUrl = route('admin.taxi.transport_groups.show', [
        $customer->id,
        $contract->id,
        $group->id,
        'tab' => 'gegevens',
    ]);
    $ledenUrl = route('admin.taxi.transport_groups.show', [
        $customer->id,
        $contract->id,
        $group->id,
        'tab' => 'leden',
    ]);
    $routeUrl = route('admin.taxi.transport_groups.show', [
        $customer->id,
        $contract->id,
        $group->id,
        'tab' => 'route',
    ]);
    $tabs = [
        [
            'key' => 'gegevens',
            'label' => 'Groepsgegevens',
            'hint' => 'Adres, tijden, terugweg',
            'url' => $gegevensUrl,
            'count' => null,
        ],
        [
            'key' => 'leden',
            'label' => 'Leden',
            'hint' => 'Passagiers in deze groep',
            'url' => $ledenUrl,
            'count' => $memberCount,
        ],
        [
            'key' => 'route',
            'label' => 'Route',
            'hint' => 'Stops, tijden & chauffeur',
            'url' => $routeUrl,
            'count' => null,
        ],
    ];
@endphp

<nav class="contract-workspace-nav" aria-label="Groepsonderdelen">
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
