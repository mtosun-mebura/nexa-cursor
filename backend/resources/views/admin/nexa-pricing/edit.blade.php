@extends('admin.layouts.app')

@section('title', 'Paketten')

@section('content')
@php
    $packages = old('packages', $pricing['packages'] ?? []);
    if (! is_array($packages) || $packages === []) {
        $packages = [['name' => '', 'audience' => '', 'price' => '', 'offer' => '', 'free_months' => 0, 'period' => 'per maand', 'badge' => '', 'highlighted' => false, 'cta_text' => 'Aanvragen', 'cta_url' => '/contact', 'features' => []]];
    }
    $featureCatalog = app(\App\Services\NexaPricingService::class)->featureCatalog(['packages' => $packages]);
    $addons = old('addons', $pricing['addons'] ?? []);
    if (! is_array($addons)) {
        $addons = [];
    }
    $website = old('website', $pricing['website'] ?? []);
    if (! is_array($website)) {
        $website = [];
    }
@endphp
@include('admin.platform-billing.partials.form-switch-styles')
<style>
    .nexa-pricing-feature-toggle {
        --nexa-pricing-toggle-border: color-mix(in oklab, var(--input) 68%, var(--muted-foreground) 32%);
        border: 1px solid var(--nexa-pricing-toggle-border);
        background-color: var(--background);
        box-shadow: 0 1px 2px 0 color-mix(in oklab, rgb(0 0 0 / 0.05) 100%, transparent);
    }
    .nexa-pricing-feature-toggle:hover {
        border-color: color-mix(in oklab, rgb(34 197 94) 35%, var(--nexa-pricing-toggle-border));
    }
    .nexa-pricing-feature-toggle:has(.nexa-pricing-feature-included:not(:checked)) {
        opacity: 0.45;
    }
    .nexa-pricing-feature-toggle:has(.nexa-pricing-feature-included:not(:checked)) .ki-check {
        opacity: 0;
    }
    .nexa-pricing-feature-toggle:has(.nexa-pricing-feature-included:checked) {
        border-color: color-mix(in oklab, rgb(34 197 94) 45%, var(--nexa-pricing-toggle-border));
        background: color-mix(in oklab, rgb(34 197 94) 12%, transparent);
    }
    #nexa-pricing-summary .nexa-pricing-summary-table-wrap {
        overflow: hidden;
        width: 22.875rem;
        max-width: 100%;
        border: 1px solid var(--border);
        border-radius: calc(var(--radius) + 4px);
    }
    #nexa-pricing-summary .kt-table {
        width: 100%;
        min-width: 0;
        table-layout: fixed;
    }
    #nexa-pricing-summary .kt-table :is(th, td) {
        border-bottom: 1px solid var(--border);
        padding-inline: 0.75rem;
        white-space: nowrap;
    }
    #nexa-pricing-summary .kt-table tbody tr:last-child td {
        border-bottom: none;
    }
</style>
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-col gap-5 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">Paketten</h1>
        <p class="text-sm text-muted-foreground">Maandpakketten voor nexasuite.nl én NEXA-facturatie, inclusief de functies die de software afdwingt en aanvullende modules (GPS, extra contractklanten, Vloot). Alleen zichtbaar voor super-admins.</p>
        <div class="pt-3 flex flex-wrap gap-2">
            <a href="{{ $websitePageUrl }}" class="kt-btn kt-btn-outline" target="_blank" rel="noopener">
                <i class="ki-filled ki-exit-right-corner me-2"></i>
                Bekijk /prijzen
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.nexa-pricing.update') }}">
        @csrf
        @method('PUT')

        <div class="grid gap-5 lg:gap-7.5">
            <x-error-card :errors="$errors" />

            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header">
                    <h3 class="kt-card-title mb-0">Teksten boven de pakketten</h3>
                </div>
                <div class="kt-card-content p-0">
                    <div class="px-3 sm:px-5 pb-3 min-w-0">
                        <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">Boventitel</td>
                                <td class="min-w-48 w-full">
                                    <input class="kt-input w-full" type="text" name="eyebrow" value="{{ old('eyebrow', $pricing['eyebrow'] ?? '') }}">
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal">Titel</td>
                                <td>
                                    <input class="kt-input w-full" type="text" name="title" value="{{ old('title', $pricing['title'] ?? '') }}">
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal align-top">Subtitel</td>
                                <td>
                                    <textarea class="kt-input w-full" name="subtitle" rows="3">{{ old('subtitle', $pricing['subtitle'] ?? '') }}</textarea>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal">Voetnoot (btw / termijn)</td>
                                <td>
                                    <input class="kt-input w-full" type="text" name="vat_note" value="{{ old('vat_note', $pricing['vat_note'] ?? '') }}">
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="space-y-5">
                <div class="kt-card w-full min-w-0" id="nexa-pricing-summary">
                    <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
                        <h3 class="kt-card-title mb-0">Overzicht prijzen</h3>
                    </div>
                    <div class="kt-card-content p-5">
                        <p class="text-sm text-muted-foreground mb-3">Deze prijzen gelden voor NEXA-facturatie. Wijzigingen in de pakketten hieronder verschijnen direct in deze tabel.</p>
                        <div class="nexa-pricing-summary-table-wrap">
                            <div class="kt-scrollable-x-auto">
                                <table class="kt-table kt-table-border-dashed admin-keep-table-layout align-middle text-sm">
                                    <thead>
                                        <tr>
                                            <th class="text-secondary-foreground font-normal">Pakket</th>
                                            <th class="text-secondary-foreground font-normal text-end">Maandprijs</th>
                                        </tr>
                                    </thead>
                                    <tbody id="nexa-pricing-summary-body"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="space-y-5" id="nexa-pricing-packages">
                    @foreach($packages as $i => $package)
                    @include('admin.nexa-pricing.partials.package-card', ['i' => $i, 'package' => $package, 'featureCatalog' => $featureCatalog])
                    @endforeach
                </div>
                <button type="button" class="kt-btn kt-btn-sm kt-btn-outline" id="nexa-pricing-package-add">+ Pakket toevoegen</button>
            </div>

            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header">
                    <h3 class="kt-card-title mb-0">Website (eenmalig)</h3>
                </div>
                <div class="kt-card-content p-0">
                    <div class="px-3 sm:px-5 pb-3 min-w-0">
                        <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">Titel</td>
                                <td class="min-w-48 w-full">
                                    <input class="kt-input w-full" type="text" name="website[title]" value="{{ $website['title'] ?? '' }}">
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal">Prijs <span class="text-destructive">*</span></td>
                                <td>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <input class="kt-input w-28" type="text" name="website[price_prefix]" value="{{ $website['price_prefix'] ?? 'vanaf' }}" placeholder="vanaf">
                                        <span class="text-sm">€</span>
                                        <input class="kt-input w-28 tabular-nums" type="text" name="website[price_label]" value="{{ $website['price_label'] ?? '' }}" required>
                                        <input class="kt-input w-36" type="text" name="website[period]" value="{{ $website['period'] ?? 'eenmalig' }}" placeholder="eenmalig">
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal align-top">Aanbiedingsprijs</td>
                                <td>
                                    <input class="kt-input w-40" type="text" name="website[offer]" value="{{ $website['offer'] ?? '' }}" placeholder="bijv. 499">
                                    <p class="text-xs text-muted-foreground mt-1">Optionele lagere prijs. Leeg = de normale eenmalige prijs. Een getal wordt als € …,- getoond.</p>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal align-top">Toelichting</td>
                                <td>
                                    <textarea class="kt-input w-full" name="website[subtitle]" rows="3">{{ $website['subtitle'] ?? '' }}</textarea>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal align-top">Kenmerken</td>
                                <td>
                                    <p class="text-xs text-muted-foreground mb-2">Aangevinkte regels krijgen een vinkje op de website.</p>
                                    @php
                                        $websiteFeatures = $website['features'] ?? [];
                                        if (! empty($website['features_text']) && is_string($website['features_text'])) {
                                            $websiteFeatures = preg_split('/\r\n|\r|\n/', $website['features_text']) ?: [];
                                        }
                                        if (! is_array($websiteFeatures)) {
                                            $websiteFeatures = [];
                                        }
                                        $websiteFeatures = array_values(array_filter(array_map('strval', $websiteFeatures), static fn ($line) => trim($line) !== ''));
                                        if ($websiteFeatures === []) {
                                            $websiteFeatures = [''];
                                        }
                                    @endphp
                                    <div class="nexa-pricing-feature-list space-y-2" data-feature-prefix="website[features]">
                                        @foreach($websiteFeatures as $feature)
                                            @include('admin.nexa-pricing.partials.feature-row', ['name' => 'website[features][]', 'value' => $feature])
                                        @endforeach
                                    </div>
                                    <button type="button" class="nexa-pricing-feature-add kt-btn kt-btn-sm kt-btn-outline mt-2">+ Kenmerk toevoegen</button>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal">Knop</td>
                                <td>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                        <input class="kt-input w-full" type="text" name="website[cta_text]" value="{{ $website['cta_text'] ?? '' }}" placeholder="Knoptekst">
                                        <input class="kt-input w-full" type="text" name="website[cta_url]" value="{{ $website['cta_url'] ?? '/contact' }}" placeholder="/contact">
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header">
                    <h3 class="kt-card-title mb-0">Aanvullende modules</h3>
                </div>
                <div class="kt-card-content p-5 lg:p-6 space-y-3">
                    <p class="text-sm text-muted-foreground mb-0">Vaste modules die je per bedrijf bij het abonnement kunt zetten. GPS-trackers bouwen we later; de module kun je nu al activeren. Extra contractklanten verhogen het Business-limiet met 10 per bundel. Vloot maakt contractklanten onbeperkt.</p>
                    @foreach(app(\App\Services\NexaPricingService::class)->modulesCatalog($pricing) as $module)
                        @php
                            $posted = old('modules.'.$module['key'], []);
                            $moduleName = is_array($posted) && isset($posted['name']) ? $posted['name'] : $module['name'];
                            $modulePrice = is_array($posted) && isset($posted['price']) ? $posted['price'] : $module['price'];
                            $moduleDescription = is_array($posted) && isset($posted['description']) ? $posted['description'] : $module['description'];
                        @endphp
                        <div class="border border-border rounded-lg p-3 space-y-2">
                            <input type="hidden" name="modules[{{ $module['key'] }}][key]" value="{{ $module['key'] }}">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div class="text-sm font-medium text-foreground">{{ $module['label'] }}</div>
                                <code class="text-[11px] text-muted-foreground">{{ $module['code'] }}</code>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-[1fr_8rem_1.6fr] gap-2">
                                <input class="kt-input w-full" type="text" name="modules[{{ $module['key'] }}][name]" value="{{ $moduleName }}" placeholder="Naam">
                                <div class="flex items-center gap-1">
                                    <span class="text-sm text-muted-foreground">€</span>
                                    <input class="kt-input w-full tabular-nums" type="number" name="modules[{{ $module['key'] }}][price]" value="{{ $modulePrice }}" min="0" max="9999" step="1" inputmode="numeric">
                                </div>
                                <input class="kt-input w-full" type="text" name="modules[{{ $module['key'] }}][description]" value="{{ $moduleDescription }}" placeholder="Toelichting">
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header">
                    <h3 class="kt-card-title mb-0">Extra opties</h3>
                </div>
                <div class="kt-card-content p-5 lg:p-6 space-y-3" id="nexa-pricing-addons">
                    @foreach($addons as $i => $addon)
                    <div class="nexa-pricing-addon grid grid-cols-1 md:grid-cols-[1fr_1fr_1.4fr_auto] gap-2 items-start border border-border rounded-lg p-3">
                        <input class="kt-input w-full" type="text" name="addons[{{ $i }}][name]" value="{{ $addon['name'] ?? '' }}" placeholder="Naam">
                        <input class="kt-input w-full" type="text" name="addons[{{ $i }}][price]" value="{{ $addon['price'] ?? '' }}" placeholder="Prijs">
                        <input class="kt-input w-full" type="text" name="addons[{{ $i }}][description]" value="{{ $addon['description'] ?? '' }}" placeholder="Toelichting">
                        <button type="button" class="nexa-pricing-addon-remove kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-destructive" title="Verwijderen" aria-label="Optie verwijderen"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
                    </div>
                    @endforeach
                    <button type="button" class="kt-btn kt-btn-sm kt-btn-outline" id="nexa-pricing-addon-add">+ Optie toevoegen</button>
                </div>
            </div>
        </div>

        <div class="admin-form-actions flex flex-wrap items-center justify-end gap-2.5 mt-5 w-full min-w-0">
            <a href="{{ $websitePageUrl }}" class="kt-btn kt-btn-outline" target="_blank" rel="noopener">Website bekijken</a>
            <button type="submit" class="kt-btn kt-btn-primary">Opslaan</button>
        </div>
    </form>
</div>

<template id="nexa-pricing-package-template">
@include('admin.nexa-pricing.partials.package-card', ['i' => '__INDEX__', 'package' => ['name' => '', 'audience' => '', 'price' => '', 'offer' => '', 'free_months' => 0, 'period' => 'per maand', 'badge' => '', 'highlighted' => false, 'cta_text' => 'Aanvragen', 'cta_url' => '/contact', 'features' => []], 'featureCatalog' => []])
</template>
<template id="nexa-pricing-feature-template">
@include('admin.nexa-pricing.partials.feature-row', ['name' => '__NAME__', 'value' => '', 'included' => true])
</template>
<template id="nexa-pricing-addon-template">
<div class="nexa-pricing-addon grid grid-cols-1 md:grid-cols-[1fr_1fr_1.4fr_auto] gap-2 items-start border border-border rounded-lg p-3">
    <input class="kt-input w-full" type="text" name="addons[__INDEX__][name]" value="" placeholder="Naam">
    <input class="kt-input w-full" type="text" name="addons[__INDEX__][price]" value="" placeholder="Prijs">
    <input class="kt-input w-full" type="text" name="addons[__INDEX__][description]" value="" placeholder="Toelichting">
    <button type="button" class="nexa-pricing-addon-remove kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-destructive" title="Verwijderen" aria-label="Optie verwijderen"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
</div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form[action="{{ route('admin.nexa-pricing.update') }}"]');
    const packagesRoot = document.getElementById('nexa-pricing-packages');
    const addonsRoot = document.getElementById('nexa-pricing-addons');
    const packageTpl = document.getElementById('nexa-pricing-package-template');
    const featureTpl = document.getElementById('nexa-pricing-feature-template');
    const addonTpl = document.getElementById('nexa-pricing-addon-template');

    function nextIndex(root, selector) {
        return root.querySelectorAll(selector).length;
    }

    function htmlToNode(html) {
        const wrap = document.createElement('div');
        wrap.innerHTML = html.trim();
        return wrap.firstElementChild;
    }

    function isPackageFeatureList(list) {
        return !!(list && list.closest('.nexa-pricing-package'));
    }

    function packageLists() {
        if (!packagesRoot) return [];
        return Array.from(packagesRoot.querySelectorAll('.nexa-pricing-package .nexa-pricing-feature-list'));
    }

    function setFeatureNames(list) {
        const prefix = list.getAttribute('data-feature-prefix') || '';
        list.querySelectorAll('.nexa-pricing-feature-row input[type="text"]').forEach(function (input) {
            input.name = prefix + '[]';
        });
    }

    function addFeatureRow(list, included) {
        if (!list || !featureTpl) return null;
        const prefix = list.getAttribute('data-feature-prefix') || '';
        const html = featureTpl.innerHTML.replaceAll('__NAME__', prefix + '[]');
        const node = htmlToNode(html);
        const checkbox = node.querySelector('.nexa-pricing-feature-included');
        if (checkbox) checkbox.checked = included !== false;
        list.appendChild(node);
        return node;
    }

    function addFeatureRowToPackageCatalog(sourceList) {
        packageLists().forEach(function (list) {
            addFeatureRow(list, list === sourceList);
        });
    }

    function copyCatalogToPackageList(targetList) {
        const lists = packageLists();
        const sourceList = lists.find(function (list) { return list !== targetList; });
        if (!sourceList || !targetList) return;
        targetList.innerHTML = '';
        sourceList.querySelectorAll('.nexa-pricing-feature-row').forEach(function (row) {
            const clone = row.cloneNode(true);
            const checkbox = clone.querySelector('.nexa-pricing-feature-included');
            if (checkbox) checkbox.checked = false;
            targetList.appendChild(clone);
        });
        if (!targetList.querySelector('.nexa-pricing-feature-row')) {
            addFeatureRow(targetList, false);
        }
        setFeatureNames(targetList);
    }

    function syncFeatureText(sourceInput) {
        const sourceList = sourceInput.closest('.nexa-pricing-feature-list');
        const sourceRow = sourceInput.closest('.nexa-pricing-feature-row');
        if (!isPackageFeatureList(sourceList) || !sourceRow) return;
        const index = Array.from(sourceList.querySelectorAll('.nexa-pricing-feature-row')).indexOf(sourceRow);
        if (index < 0) return;
        packageLists().forEach(function (list) {
            if (list === sourceList) return;
            const input = list.querySelectorAll('.nexa-pricing-feature-row')[index]?.querySelector('input[type="text"]');
            if (input) input.value = sourceInput.value;
        });
    }

    function removePackageFeatureRow(sourceRow) {
        const sourceList = sourceRow.closest('.nexa-pricing-feature-list');
        const index = Array.from(sourceList.querySelectorAll('.nexa-pricing-feature-row')).indexOf(sourceRow);
        if (index < 0) return;
        packageLists().forEach(function (list) {
            const rows = list.querySelectorAll('.nexa-pricing-feature-row');
            if (rows.length > 1) {
                rows[index]?.remove();
                return;
            }
            const row = rows[0];
            const input = row?.querySelector('input[type="text"]');
            const included = row?.querySelector('.nexa-pricing-feature-included');
            if (input) input.value = '';
            if (included) included.checked = false;
        });
    }

    document.getElementById('nexa-pricing-package-add')?.addEventListener('click', function () {
        if (!packagesRoot || !packageTpl) return;
        const html = packageTpl.innerHTML.replaceAll('__INDEX__', String(nextIndex(packagesRoot, '.nexa-pricing-package')));
        const node = htmlToNode(html);
        packagesRoot.appendChild(node);
        const newList = node.querySelector('.nexa-pricing-feature-list');
        copyCatalogToPackageList(newList);
        bindDriverLimit(node);
        refreshPricingSummary();
    });

    packagesRoot?.addEventListener('click', function (e) {
        const removePackage = e.target.closest('.nexa-pricing-package-remove');
        if (removePackage) {
            const card = removePackage.closest('.nexa-pricing-package');
            if (card && packagesRoot.querySelectorAll('.nexa-pricing-package').length > 1) {
                card.remove();
                refreshPricingSummary();
            }
        }
    });

    function bindDriverLimit(root) {
        (root || document).querySelectorAll('[data-max-drivers-unlimited]').forEach(function (cb) {
            if (cb.dataset.bound === '1') return;
            cb.dataset.bound = '1';
            const card = cb.closest('.nexa-pricing-package');
            const input = card ? card.querySelector('[data-max-drivers-input]') : null;
            function sync() {
                if (!input) return;
                input.readOnly = cb.checked;
                if (cb.checked) input.value = '0';
            }
            cb.addEventListener('change', sync);
            sync();
        });
    }

    function slugifyPackageName(value) {
        return String(value || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .slice(0, 80);
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, function (char) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char];
        });
    }

    function parseMonthlyAmount(value) {
        var stripped = String(value || '').trim().replace(/^€\s*/u, '').replace(/,-$/, '').replace(/\s+/g, '');
        if (/^\d+,\d{1,2}$/.test(stripped)) {
            stripped = stripped.replace(',', '.');
        } else if (/^\d{1,3}(\.\d{3})+,\d{1,2}$/.test(stripped)) {
            stripped = stripped.replace(/\./g, '').replace(',', '.');
        }
        var amount = parseFloat(stripped);
        return isFinite(amount) ? Math.max(0, amount) : NaN;
    }

    function formatMonthlyAmount(value) {
        var raw = String(value || '').trim();
        if (raw === '') {
            return '—';
        }
        var amount = parseMonthlyAmount(raw);
        if (!isFinite(amount)) {
            return '—';
        }
        if (Math.abs(amount - Math.round(amount)) < 0.001) {
            return '€ ' + Math.round(amount) + ',-';
        }
        return '€ ' + amount.toLocaleString('nl-NL', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function refreshPricingSummary() {
        var body = document.getElementById('nexa-pricing-summary-body');
        if (!body || !packagesRoot) {
            return;
        }
        var rows = [];
        packagesRoot.querySelectorAll('.nexa-pricing-package').forEach(function (card) {
            var name = (card.querySelector('[data-package-name]')?.value || '').trim() || 'Naamloos pakket';
            var price = card.querySelector('[data-package-price]')?.value || '';
            rows.push(
                '<tr>' +
                    '<td class="text-foreground">' + escapeHtml(name) + '</td>' +
                    '<td class="text-end tabular-nums text-foreground">' + escapeHtml(formatMonthlyAmount(price)) + '</td>' +
                '</tr>'
            );
        });
        body.innerHTML = rows.join('') || '<tr><td colspan="2" class="p-5 text-muted-foreground">Nog geen pakketten</td></tr>';
    }

    packagesRoot?.addEventListener('input', function (e) {
        if (e.target.matches('[data-package-name], [data-package-price]')) {
            refreshPricingSummary();
        }
        if (!e.target.matches('[data-package-name]')) return;
        const card = e.target.closest('.nexa-pricing-package');
        const keyInput = card ? card.querySelector('[data-package-key]') : null;
        if (!keyInput || keyInput.dataset.manual === '1') return;
        if (keyInput.value !== '' && keyInput.dataset.autofil !== '1') return;
        const slug = slugifyPackageName(e.target.value);
        keyInput.value = slug;
        keyInput.dataset.autofil = slug ? '1' : '';
    });

    packagesRoot?.addEventListener('input', function (e) {
        if (!e.target.matches('[data-package-key]')) return;
        e.target.dataset.manual = '1';
        e.target.dataset.autofil = '';
    });

    packagesRoot?.addEventListener('input', function (e) {
        if (e.target.matches('.nexa-pricing-feature-row input[type="text"]')) {
            syncFeatureText(e.target);
        }
    });

    bindDriverLimit(packagesRoot);
    refreshPricingSummary();

    document.getElementById('nexa-pricing-addon-add')?.addEventListener('click', function () {
        if (!addonsRoot || !addonTpl) return;
        const html = addonTpl.innerHTML.replaceAll('__INDEX__', String(nextIndex(addonsRoot, '.nexa-pricing-addon')));
        addonsRoot.insertBefore(htmlToNode(html), this);
    });

    addonsRoot?.addEventListener('click', function (e) {
        const btn = e.target.closest('.nexa-pricing-addon-remove');
        if (!btn) return;
        btn.closest('.nexa-pricing-addon')?.remove();
    });

    document.addEventListener('click', function (e) {
        const addBtn = e.target.closest('.nexa-pricing-feature-add');
        if (addBtn) {
            const list = addBtn.parentElement?.querySelector('.nexa-pricing-feature-list');
            if (isPackageFeatureList(list)) {
                addFeatureRowToPackageCatalog(list);
            } else {
                addFeatureRow(list, true);
            }
            return;
        }
        const removeBtn = e.target.closest('.nexa-pricing-feature-remove');
        if (!removeBtn) return;
        const list = removeBtn.closest('.nexa-pricing-feature-list');
        const row = removeBtn.closest('.nexa-pricing-feature-row');
        if (!list || !row) return;
        if (isPackageFeatureList(list)) {
            removePackageFeatureRow(row);
            return;
        }
        if (list.querySelectorAll('.nexa-pricing-feature-row').length > 1) {
            row.remove();
            return;
        }
        const input = row.querySelector('input[type="text"]');
        const included = row.querySelector('.nexa-pricing-feature-included');
        if (input) input.value = '';
        if (included) included.checked = true;
    });

    form?.addEventListener('submit', function () {
        form.querySelectorAll('.nexa-pricing-feature-row').forEach(function (row) {
            const included = row.querySelector('.nexa-pricing-feature-included');
            const input = row.querySelector('input[type="text"]');
            if (included && input && !included.checked) {
                input.disabled = true;
            }
        });
    });
});
</script>
@endsection
