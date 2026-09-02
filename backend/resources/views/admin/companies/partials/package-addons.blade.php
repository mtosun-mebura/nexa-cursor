@php
    $companyModel = $company ?? null;
    $addonCatalog = app(\App\Services\NexaPricingService::class)->modulesCatalog();
    $addonSelections = old('package_addons', is_array($companyModel->package_addons ?? null) ? $companyModel->package_addons : []);
    $addonSelections = \App\Support\TenantPackageAddon::normalizeSelections(is_array($addonSelections) ? $addonSelections : []);
    $entitlements = app(\App\Services\CompanyEntitlementService::class);
    $previewCompany = $companyModel ? clone $companyModel : new \App\Models\Company;
    $previewCompany->package_key = old('package_key', $companyModel->package_key ?? null);
    $previewCompany->package_addons = $addonSelections;
    $clientLimitLabel = $entitlements->contractClientLimitLabel($previewCompany);
    $extraClientsDef = collect($addonCatalog)->firstWhere('key', \App\Support\TenantPackageAddon::EXTRA_CLIENTS);
    $gpsDef = collect($addonCatalog)->firstWhere('key', \App\Support\TenantPackageAddon::GPS_TRACKING);
    $vlootDef = collect($addonCatalog)->firstWhere('key', \App\Support\TenantPackageAddon::FLEET);
@endphp
<tr>
    <td class="text-secondary-foreground font-normal align-top">Aanvullende modules</td>
    <td>
        <p class="text-xs text-muted-foreground mb-3">Extra’s binnen het abonnement. GPS-trackers tonen de live kaart in het menu. Extra contractklanten verhogen het limiet. Vloot maakt contractklanten onbeperkt.</p>
        <div class="space-y-3">
            <div class="border border-border rounded-lg p-3">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <div class="text-sm font-medium text-foreground">{{ $extraClientsDef['name'] ?? 'Extra contractklanten' }}</div>
                        <p class="text-xs text-muted-foreground mt-1 mb-0">{{ $extraClientsDef['description'] ?? 'Elke bundel telt +10 contractklanten.' }} (+ € {{ (int) ($extraClientsDef['price'] ?? 49) }} / maand per bundel)</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <input class="kt-input w-20 tabular-nums" type="number" name="package_addons[{{ \App\Support\TenantPackageAddon::EXTRA_CLIENTS }}]" value="{{ (int) $addonSelections[\App\Support\TenantPackageAddon::EXTRA_CLIENTS] }}" min="0" max="50" step="1" inputmode="numeric">
                        <span class="text-xs text-muted-foreground">bundels</span>
                    </div>
                </div>
            </div>
            <div class="border border-border rounded-lg p-3">
                <label class="kt-label flex items-start gap-2 mb-0" for="package-addon-gps">
                    <input type="hidden" name="package_addons[{{ \App\Support\TenantPackageAddon::GPS_TRACKING }}]" value="0">
                    <input type="checkbox" name="package_addons[{{ \App\Support\TenantPackageAddon::GPS_TRACKING }}]" id="package-addon-gps" value="1" class="kt-switch kt-switch-sm shrink-0 mt-0.5" @checked((int) $addonSelections[\App\Support\TenantPackageAddon::GPS_TRACKING] === 1)>
                    <span>
                        <span class="text-sm font-medium text-foreground">{{ $gpsDef['name'] ?? 'GPS-trackers' }}</span>
                        <span class="block text-xs text-muted-foreground font-normal mt-1">{{ $gpsDef['description'] ?? 'Taxi’s live volgen op de kaart via GPS.' }} (+ € {{ (int) ($gpsDef['price'] ?? 19) }} / maand)</span>
                    </span>
                </label>
            </div>
            <div class="border border-border rounded-lg p-3">
                <label class="kt-label flex items-start gap-2 mb-0" for="package-addon-vloot">
                    <input type="hidden" name="package_addons[{{ \App\Support\TenantPackageAddon::FLEET }}]" value="0">
                    <input type="checkbox" name="package_addons[{{ \App\Support\TenantPackageAddon::FLEET }}]" id="package-addon-vloot" value="1" class="kt-switch kt-switch-sm shrink-0 mt-0.5" @checked((int) $addonSelections[\App\Support\TenantPackageAddon::FLEET] === 1)>
                    <span>
                        <span class="text-sm font-medium text-foreground">{{ $vlootDef['name'] ?? 'Vloot' }}</span>
                        <span class="block text-xs text-muted-foreground font-normal mt-1">{{ $vlootDef['description'] ?? 'Onbeperkt contractklanten.' }} (vanaf € {{ (int) ($vlootDef['price'] ?? 249) }} / maand)</span>
                    </span>
                </label>
            </div>
        </div>
        <p class="text-xs text-muted-foreground mt-3 mb-0">Effectief contractklantenlimiet: <span class="font-medium text-foreground">{{ $clientLimitLabel }}</span></p>
        @error('package_addons')
            <div class="text-xs text-destructive mt-1">{{ $message }}</div>
        @enderror
    </td>
</tr>
