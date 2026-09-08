@php
    $companyModel = $company ?? null;
    $addonCatalog = app(\App\Services\NexaPricingService::class)->modulesCatalog();
    $rawAddons = old('package_addons', is_array($companyModel->package_addons ?? null) ? $companyModel->package_addons : []);
    $addonRecords = \App\Support\TenantPackageAddon::normalizeRecords(is_array($rawAddons) ? $rawAddons : []);
    $addonSelections = \App\Support\TenantPackageAddon::normalizeSelections($addonRecords);
    $entitlements = app(\App\Services\CompanyEntitlementService::class);
    $previewCompany = $companyModel ? clone $companyModel : new \App\Models\Company;
    $previewCompany->package_key = old('package_key', $companyModel->package_key ?? null);
    $previewCompany->package_addons = $addonRecords;
    $clientLimitLabel = $entitlements->contractClientLimitLabel($previewCompany);
    $minStart = \App\Support\TenantPackageAddon::earliestStartDate()->toDateString();
    $maxStart = \App\Support\TenantPackageAddon::latestStartDate()->toDateString();
    $todayStart = $minStart;
    $nextMonthStart = $maxStart;
    $addonsInTrial = false;
    if ($companyModel?->id && $companyModel->billingProfile) {
        $addonsInTrial = app(\App\Services\PlatformBilling\TenantSubscriptionService::class)->isInTrial($companyModel->billingProfile);
    } elseif (! $companyModel?->id) {
        $addonsInTrial = true;
    }
@endphp
<tr>
    <td class="text-secondary-foreground font-normal align-top">Aanvullende modules</td>
    <td>
        @if($addonsInTrial)
            <p class="text-xs text-muted-foreground mb-3">Tijdens de proefperiode zijn alle aanvullende modules te gebruiken. Zet een module aan om die ná de proefperiode te houden. Zet hem uit vóór het abonnement ingaat: dan stopt hij kosteloos, zonder factuur. Het pakket zelf kan niet worden opgezegd.</p>
        @else
            <p class="text-xs text-muted-foreground mb-3">Extra’s binnen het abonnement. Een nieuwe module krijgt een ingangsdatum (vanaf vandaag of de 1e van volgende maand). Opzeggen gaat per de 1e van volgende maand: de module blijft deze maand actief. Het pakket zelf kan niet worden opgezegd.</p>
        @endif
        <div class="space-y-3" data-package-addons>
            @foreach($addonCatalog as $addon)
                @php
                    $addonKey = $addon['key'];
                    $record = $addonRecords[$addonKey] ?? \App\Support\TenantPackageAddon::emptyRecord();
                    $quantity = (int) ($record['quantity'] ?? 0);
                    $pendingCancel = \App\Support\TenantPackageAddon::isPendingCancel($record);
                    $pendingDecrease = \App\Support\TenantPackageAddon::isPendingDecrease($record);
                    $startValue = $record['starts_at'] ?? $todayStart;
                    $isQuantity = ($addon['type'] ?? '') === \App\Support\TenantPackageAddon::TYPE_QUANTITY;
                    $isActive = $quantity > 0;
                    $price = (int) ($addon['price'] ?? 0);
                    $addonName = $addon['name'] ?? $addon['label'] ?? $addonKey;
                @endphp
                <div class="border border-border rounded-lg p-3" data-package-addon="{{ $addonKey }}">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            @if($isQuantity)
                                <div class="text-sm font-medium text-foreground">{{ $addonName }}</div>
                                <p class="text-xs text-muted-foreground mt-1 mb-0">{{ $addon['description'] ?? '' }} (+ € {{ $price }} / maand per bundel)</p>
                            @else
                                <label class="kt-label flex items-start gap-2 mb-0" for="package-addon-{{ $addonKey }}">
                                    <input type="hidden" name="package_addons[{{ $addonKey }}][quantity]" value="0">
                                    <input type="checkbox"
                                           name="package_addons[{{ $addonKey }}][quantity]"
                                           id="package-addon-{{ $addonKey }}"
                                           value="1"
                                           class="kt-switch kt-switch-sm shrink-0 mt-0.5"
                                           data-package-addon-toggle
                                           @checked($isActive)>
                                    <span>
                                        <span class="text-sm font-medium text-foreground">{{ $addonName }}</span>
                                        <span class="block text-xs text-muted-foreground font-normal mt-1">{{ $addon['description'] ?? '' }} (+ € {{ $price }} / maand)</span>
                                    </span>
                                </label>
                            @endif
                        </div>
                        @if($isQuantity)
                            <div class="flex items-center gap-2">
                                <input class="kt-input w-20 tabular-nums"
                                       type="number"
                                       name="package_addons[{{ $addonKey }}][quantity]"
                                       value="{{ $quantity }}"
                                       min="0"
                                       max="50"
                                       step="1"
                                       inputmode="numeric"
                                       data-package-addon-quantity>
                                <span class="text-xs text-muted-foreground">bundels</span>
                            </div>
                        @endif
                    </div>
                    @if(! $addonsInTrial)
                    <div class="mt-3 pt-3 border-t border-border {{ $isActive ? '' : 'hidden' }}" data-package-addon-start>
                        <label class="text-xs font-medium text-foreground" for="package-addon-{{ $addonKey }}-start">Ingangsdatum</label>
                        <div class="mt-1.5 flex flex-wrap items-center gap-2">
                            <input class="kt-input w-44"
                                   type="date"
                                   name="package_addons[{{ $addonKey }}][starts_at]"
                                   id="package-addon-{{ $addonKey }}-start"
                                   value="{{ $startValue }}"
                                   min="{{ $minStart }}"
                                   max="{{ $maxStart }}">
                            <button type="button" class="kt-btn kt-btn-sm kt-btn-outline" data-package-addon-date="{{ $todayStart }}" onclick="var i=this.closest('[data-package-addon]').querySelector('input[type=date]'); if(i) i.value=this.getAttribute('data-package-addon-date');">Vandaag</button>
                            <button type="button" class="kt-btn kt-btn-sm kt-btn-outline" data-package-addon-date="{{ $nextMonthStart }}" onclick="var i=this.closest('[data-package-addon]').querySelector('input[type=date]'); if(i) i.value=this.getAttribute('data-package-addon-date');">1e volgende maand</button>
                        </div>
                        @error('package_addons.'.$addonKey.'.starts_at')
                            <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    @endif
                    @if($pendingCancel && ! empty($record['starts_at']))
                        <p class="text-xs text-muted-foreground mt-2 mb-0">Opgezegd per {{ \Carbon\Carbon::parse($record['starts_at'])->translatedFormat('j F Y') }}. Blijft tot die datum actief.</p>
                    @elseif($pendingDecrease && ! empty($record['starts_at']))
                        <p class="text-xs text-muted-foreground mt-2 mb-0">Vanaf {{ \Carbon\Carbon::parse($record['starts_at'])->translatedFormat('j F Y') }}: {{ $quantity }} bundel{{ $quantity === 1 ? '' : 's' }}. Deze maand blijft {{ (int) ($record['active_quantity'] ?? 0) }} actief.</p>
                    @endif
                </div>
            @endforeach
        </div>
        <p class="text-xs text-muted-foreground mt-3 mb-0">Effectief contractklantenlimiet: <span class="font-medium text-foreground">{{ $clientLimitLabel }}</span></p>
        @error('package_addons')
            <div class="text-xs text-destructive mt-1">{{ $message }}</div>
        @enderror
        <script>
            (function () {
                var root = document.querySelector('[data-package-addons]');
                if (!root || root.dataset.bound === '1') return;
                root.dataset.bound = '1';
                function syncCard(card) {
                    var startWrap = card.querySelector('[data-package-addon-start]');
                    if (!startWrap) return;
                    var toggle = card.querySelector('[data-package-addon-toggle]');
                    var qty = card.querySelector('[data-package-addon-quantity]');
                    var active = toggle ? toggle.checked : (qty ? Number(qty.value || 0) > 0 : false);
                    startWrap.classList.toggle('hidden', !active);
                }
                root.querySelectorAll('[data-package-addon]').forEach(syncCard);
                root.addEventListener('change', function (event) {
                    var card = event.target.closest('[data-package-addon]');
                    if (card) syncCard(card);
                });
                root.addEventListener('input', function (event) {
                    var card = event.target.closest('[data-package-addon]');
                    if (card) syncCard(card);
                });
                root.addEventListener('click', function (event) {
                    var btn = event.target.closest('[data-package-addon-date]');
                    if (!btn) return;
                    event.preventDefault();
                    var card = btn.closest('[data-package-addon]');
                    var input = card && card.querySelector('input[type="date"]');
                    if (input) input.value = btn.getAttribute('data-package-addon-date') || '';
                });
            })();
        </script>
    </td>
</tr>
