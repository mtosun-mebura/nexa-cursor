@php
    $package = is_array($package ?? null) ? $package : [];
    $features = $package['features'] ?? [];
    if (! empty($package['features_text']) && is_string($package['features_text'])) {
        $features = preg_split('/\r\n|\r|\n/', $package['features_text']) ?: [];
    }
    if (! is_array($features)) {
        $features = [];
    }
    $features = array_values(array_filter(array_map('strval', $features), static fn ($line) => trim($line) !== ''));
    $catalog = isset($featureCatalog) && is_array($featureCatalog) ? $featureCatalog : $features;
    $catalog = array_values(array_filter(array_map('strval', $catalog), static fn ($line) => trim($line) !== ''));
    $emptyCatalog = $catalog === [];
    if ($emptyCatalog) {
        $catalog = [''];
    }
    $highlightedId = 'package-highlighted-'.$i;
@endphp
<div class="nexa-pricing-package kt-card w-full min-w-0" data-package-index="{{ $i }}">
    <div class="kt-card-header flex items-center justify-between gap-2">
        <h3 class="kt-card-title mb-0">Pakket</h3>
        <button type="button" class="nexa-pricing-package-remove kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-destructive" title="Pakket verwijderen" aria-label="Pakket verwijderen">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
    </div>
    <div class="kt-card-content p-0">
        <div class="px-3 sm:px-5 pb-3 min-w-0">
            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Naam <span class="text-destructive">*</span></td>
                    <td class="min-w-48 w-full">
                        <input class="kt-input w-full" type="text" name="packages[{{ $i }}][name]" value="{{ $package['name'] ?? '' }}" required data-package-name>
                    </td>
                </tr>
                <tr>
                    <td class="text-secondary-foreground font-normal">Voor wie</td>
                    <td>
                        <input class="kt-input w-full" type="text" name="packages[{{ $i }}][audience]" value="{{ $package['audience'] ?? '' }}">
                    </td>
                </tr>
                <tr>
                    <td class="text-secondary-foreground font-normal">Maandprijs <span class="text-destructive">*</span></td>
                    <td>
                        <div class="inline-flex items-stretch">
                            <span class="inline-flex items-center shrink-0 px-2.5 border border-input border-r-0 rounded-l-md bg-muted/50 text-sm font-medium text-foreground">€</span>
                            <input class="kt-input w-28 rounded-l-none tabular-nums" type="text" name="packages[{{ $i }}][price]" value="{{ $package['price'] ?? '' }}" inputmode="decimal" required data-package-price>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="text-secondary-foreground font-normal align-top">Aanbiedingsprijs</td>
                    <td>
                        <input class="kt-input w-40" type="text" name="packages[{{ $i }}][offer]" value="{{ $package['offer'] ?? '' }}" placeholder="bijv. 30">
                        <p class="text-xs text-muted-foreground mt-1">Optionele lagere prijs. Leeg = de normale maandprijs. Een getal wordt als € …,- getoond.</p>
                    </td>
                </tr>
                <tr>
                    <td class="text-secondary-foreground font-normal">Gratis maanden</td>
                    <td>
                        <input class="kt-input w-28 tabular-nums" type="number" name="packages[{{ $i }}][free_months]" value="{{ (int) ($package['free_months'] ?? 0) }}" min="0" max="24" step="1" inputmode="numeric">
                        <p class="text-xs text-muted-foreground mt-1">0 = geen. 1 = “1 maand gratis, daarna € …,-”.</p>
                    </td>
                </tr>
                <tr>
                    <td class="text-secondary-foreground font-normal">Periode</td>
                    <td>
                        <input class="kt-input w-full" type="text" name="packages[{{ $i }}][period]" value="{{ $package['period'] ?? 'per maand' }}">
                    </td>
                </tr>
                <tr>
                    <td class="text-secondary-foreground font-normal">Badge</td>
                    <td>
                        <input class="kt-input w-full" type="text" name="packages[{{ $i }}][badge]" value="{{ $package['badge'] ?? '' }}">
                    </td>
                </tr>
                <tr>
                    <td class="text-secondary-foreground font-normal">Knoptekst</td>
                    <td>
                        <input class="kt-input w-full" type="text" name="packages[{{ $i }}][cta_text]" value="{{ $package['cta_text'] ?? 'Aanvragen' }}">
                    </td>
                </tr>
                <tr>
                    <td class="text-secondary-foreground font-normal">Knop-URL</td>
                    <td>
                        <input class="kt-input w-full" type="text" name="packages[{{ $i }}][cta_url]" value="{{ $package['cta_url'] ?? '/contact' }}">
                    </td>
                </tr>
                <tr>
                    <td class="text-secondary-foreground font-normal">Aanbevolen</td>
                    <td>
                        <input type="hidden" name="packages[{{ $i }}][highlighted]" value="0">
                        <label class="kt-label flex items-center gap-2 mb-0" for="{{ $highlightedId }}">
                            <input type="checkbox" name="packages[{{ $i }}][highlighted]" id="{{ $highlightedId }}" value="1" class="kt-switch kt-switch-sm shrink-0" @checked(! empty($package['highlighted']))>
                            <span class="text-sm text-muted-foreground">Uitgelicht in de vergelijkingstabel</span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <td class="text-secondary-foreground font-normal align-top">Kenmerken</td>
                    <td>
                        <p class="text-xs text-muted-foreground mb-2">Alle kenmerken staan in elk pakket. Alleen aangevinkte regels horen bij dit pakket en krijgen een vinkje op de website.</p>
                        <div class="nexa-pricing-feature-list space-y-2" data-feature-prefix="packages[{{ $i }}][features]">
                            @foreach($catalog as $feature)
                                @include('admin.nexa-pricing.partials.feature-row', [
                                    'name' => 'packages['.$i.'][features][]',
                                    'value' => $feature,
                                    'included' => $emptyCatalog ? true : in_array($feature, $features, true),
                                ])
                            @endforeach
                        </div>
                        <button type="button" class="nexa-pricing-feature-add kt-btn kt-btn-sm kt-btn-outline mt-2">+ Kenmerk toevoegen</button>
                    </td>
                </tr>
                @include('admin.nexa-pricing.partials.package-entitlements', ['i' => $i, 'package' => $package])
            </table>
        </div>
    </div>
</div>
