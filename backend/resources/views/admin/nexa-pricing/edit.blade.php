@extends('admin.layouts.app')

@section('title', 'Prijzen')

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
</style>
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-col gap-5 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">Prijzen</h1>
        <p class="text-sm text-muted-foreground">Maandpakketten en de eenmalige websiteprijs voor nexasuite.nl. Alleen zichtbaar voor super-admins.</p>
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
    });

    packagesRoot?.addEventListener('click', function (e) {
        const removePackage = e.target.closest('.nexa-pricing-package-remove');
        if (removePackage) {
            const card = removePackage.closest('.nexa-pricing-package');
            if (card && packagesRoot.querySelectorAll('.nexa-pricing-package').length > 1) {
                card.remove();
            }
        }
    });

    packagesRoot?.addEventListener('input', function (e) {
        if (e.target.matches('.nexa-pricing-feature-row input[type="text"]')) {
            syncFeatureText(e.target);
        }
    });

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
