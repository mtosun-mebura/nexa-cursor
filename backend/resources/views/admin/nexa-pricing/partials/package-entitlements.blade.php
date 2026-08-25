@php
    $package = is_array($package ?? null) ? $package : [];
    $packageKey = trim((string) ($package['key'] ?? ''));
    $entitlements = \App\Support\TenantPackageCapability::normalize(
        is_array($package['entitlements'] ?? null) ? $package['entitlements'] : [],
        $packageKey !== '' ? $packageKey : \App\Support\TenantPackageCapability::slugFromName((string) ($package['name'] ?? ''))
    );
    $maxDrivers = (int) ($entitlements[\App\Support\TenantPackageCapability::MAX_DRIVERS] ?? 3);
    $unlimitedDrivers = $maxDrivers <= 0;
    $maxContractClients = (int) ($entitlements[\App\Support\TenantPackageCapability::MAX_CONTRACT_CLIENTS] ?? 0);
    $prefix = 'packages['.$i.'][entitlements]';
@endphp
<tr>
    <td class="text-secondary-foreground font-normal align-top">Sleutel (code)</td>
    <td>
        <input class="kt-input w-full font-mono text-sm" type="text" name="packages[{{ $i }}][key]" value="{{ $packageKey }}" data-package-key maxlength="80" placeholder="start" autocomplete="off">
        <p class="text-xs text-muted-foreground mt-1 mb-0">Stabiele code voor dit pakket, bijvoorbeeld <code>start</code>. Koppel bedrijven hieraan. Niet wijzigen als er al bedrijven aan hangen.</p>
    </td>
</tr>
<tr>
    <td class="text-secondary-foreground font-normal align-top pt-5">Functies (voor de software)</td>
    <td class="pt-5">
        <p class="text-xs text-muted-foreground mb-3">Deze schakelaars bepalen wat het systeem toestaat. Marketingkenmerken hierboven zijn alleen voor de website. Aanvullende modules (GPS, extra contractklanten, Vloot) stel je per bedrijf in.</p>
        <div class="space-y-4">
            @foreach(\App\Support\TenantPackageCapability::definitions() as $definition)
                @if($definition['key'] === \App\Support\TenantPackageCapability::MAX_DRIVERS)
                    <div class="border border-border rounded-lg p-3">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <div class="text-sm font-medium text-foreground">{{ $definition['label'] }}</div>
                                <p class="text-xs text-muted-foreground mt-1 mb-0">{{ $definition['hint'] }}</p>
                            </div>
                            <code class="text-[11px] text-muted-foreground">{{ $definition['code'] }}</code>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 mt-3">
                            <input class="kt-input w-24 tabular-nums" type="number" name="{{ $prefix }}[max_drivers]" value="{{ $unlimitedDrivers ? 0 : $maxDrivers }}" min="0" max="9999" step="1" inputmode="numeric" data-max-drivers-input>
                            <input type="hidden" name="{{ $prefix }}[max_drivers_unlimited]" value="0">
                            <label class="kt-label flex items-center gap-2 mb-0" for="package-unlimited-{{ $i }}">
                                <input type="checkbox" name="{{ $prefix }}[max_drivers_unlimited]" id="package-unlimited-{{ $i }}" value="1" class="kt-switch kt-switch-sm shrink-0" data-max-drivers-unlimited @checked($unlimitedDrivers)>
                                <span class="text-sm text-muted-foreground">Onbeperkt</span>
                            </label>
                        </div>
                        <p class="text-xs text-muted-foreground mt-2 mb-0">0 of Onbeperkt = geen limiet. Anders stopt het aanmaken bij dit aantal, met een melding in het gebruikersformulier.</p>
                    </div>
                @elseif($definition['key'] === \App\Support\TenantPackageCapability::MAX_CONTRACT_CLIENTS)
                    <div class="border border-border rounded-lg p-3">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <div class="text-sm font-medium text-foreground">{{ $definition['label'] }}</div>
                                <p class="text-xs text-muted-foreground mt-1 mb-0">{{ $definition['hint'] }}</p>
                            </div>
                            <code class="text-[11px] text-muted-foreground">{{ $definition['code'] }}</code>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 mt-3">
                            <input class="kt-input w-24 tabular-nums" type="number" name="{{ $prefix }}[max_contract_clients]" value="{{ $maxContractClients }}" min="0" max="9999" step="1" inputmode="numeric">
                        </div>
                        <p class="text-xs text-muted-foreground mt-2 mb-0">0 = geen contractklanten (Start/Pro). Business standaard 10. Extra bundels (+10) en Vloot (onbeperkt) tel je per bedrijf bij de aanvullende modules.</p>
                    </div>
                @else
                    @php
                        $boolId = 'package-cap-'.$i.'-'.$definition['key'];
                        $enabled = ! empty($entitlements[$definition['key']]);
                    @endphp
                    <div class="border border-border rounded-lg p-3">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <label class="kt-label flex items-start gap-2 mb-0" for="{{ $boolId }}">
                                <input type="hidden" name="{{ $prefix }}[{{ $definition['key'] }}]" value="0">
                                <input type="checkbox" name="{{ $prefix }}[{{ $definition['key'] }}]" id="{{ $boolId }}" value="1" class="kt-switch kt-switch-sm shrink-0 mt-0.5" @checked($enabled)>
                                <span>
                                    <span class="text-sm font-medium text-foreground">{{ $definition['label'] }}</span>
                                    <span class="block text-xs text-muted-foreground font-normal mt-1">{{ $definition['hint'] }}</span>
                                </span>
                            </label>
                            <code class="text-[11px] text-muted-foreground">{{ $definition['code'] }}</code>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </td>
</tr>
