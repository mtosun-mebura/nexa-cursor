@extends('admin.companies.wizard.layout')

@section('title', 'Stap 8 — Google SEO & reviews')

@section('wizard_content')
@include('admin.settings.partials.collapsible-section-assets')
<form method="post" action="{{ route('admin.companies.wizard.submit-step', [$company, 8]) }}">
    @csrf
    <x-error-card :errors="$errors" />

    <div id="wizard-google-collapsible-root">
    <div class="kt-card min-w-full mb-6 settings-collapsible-card" id="wizard-google-seo">
        @include('admin.settings.partials.collapsible-header', [
            'titleHtml' => 'Google SEO &amp; Search Console',
            'headerClass' => 'px-5 py-5',
        ])
        <div class="settings-collapsible-body">
        <div class="kt-card-content p-5 lg:p-6">
            <p class="text-sm text-secondary-foreground mb-0">
                Koppel Analytics, Tag Manager, Search Console en site-verificatie voor deze tenant.
                Velden mogen leeg blijven; de stap moet wel worden doorlopen.
            </p>
        </div>
        <div class="kt-card-table kt-scrollable-x-auto pb-3">
            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table">
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Search Console API</td>
                    <td class="min-w-48 w-full">
                        <input type="hidden" name="GOOGLE_SEARCH_CONSOLE_ENABLED" value="0">
                        <label class="kt-label flex items-center gap-2 mb-3">
                            <input type="checkbox" class="kt-switch kt-switch-sm shrink-0" name="GOOGLE_SEARCH_CONSOLE_ENABLED" value="1" {{ old('GOOGLE_SEARCH_CONSOLE_ENABLED', $seoSettings['GOOGLE_SEARCH_CONSOLE_ENABLED'] ?? '0') === '1' ? 'checked' : '' }}>
                            <span class="text-sm text-secondary-foreground">API-koppeling actief (service account)</span>
                        </label>
                        @if(($seoSettings['service_account_configured'] ?? '') === '1')
                            <p class="text-xs text-success mb-2">Service account gekoppeld{{ !empty($seoSettings['service_account_client_email']) ? ': '.$seoSettings['service_account_client_email'] : '' }}</p>
                        @endif
                        <textarea name="GOOGLE_SEARCH_CONSOLE_SERVICE_ACCOUNT_JSON" rows="4" class="kt-input w-full font-mono text-xs" placeholder="Plak hier het JSON-bestand. Laat leeg om de huidige sleutel te behouden."></textarea>
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Search Console property</td>
                    <td class="min-w-48 w-full">
                        <input type="text" class="kt-input" name="GOOGLE_SEO_PROPERTY_ID" value="{{ old('GOOGLE_SEO_PROPERTY_ID', $seoSettings['GOOGLE_SEO_PROPERTY_ID'] ?? '') }}" placeholder="sc-domain:example.com">
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Sitemap-pad</td>
                    <td class="min-w-48 w-full">
                        <input type="text" class="kt-input" name="GOOGLE_SEARCH_CONSOLE_SITEMAP_PATH" value="{{ old('GOOGLE_SEARCH_CONSOLE_SITEMAP_PATH', $seoSettings['GOOGLE_SEARCH_CONSOLE_SITEMAP_PATH'] ?? 'sitemap.xml') }}" placeholder="sitemap.xml">
                        <input type="hidden" name="GOOGLE_SEARCH_CONSOLE_AUTO_SITEMAP" value="0">
                        <label class="kt-label flex items-center gap-2 mt-3 mb-0">
                            <input type="checkbox" class="kt-checkbox shrink-0" name="GOOGLE_SEARCH_CONSOLE_AUTO_SITEMAP" value="1" {{ old('GOOGLE_SEARCH_CONSOLE_AUTO_SITEMAP', $seoSettings['GOOGLE_SEARCH_CONSOLE_AUTO_SITEMAP'] ?? '1') === '1' ? 'checked' : '' }}>
                            <span class="text-sm text-muted-foreground">Sitemap automatisch indienen na opslaan</span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Google Analytics</td>
                    <td class="min-w-48 w-full">
                        <input type="text" class="kt-input" name="GOOGLE_ANALYTICS_ID" value="{{ old('GOOGLE_ANALYTICS_ID', $seoSettings['GOOGLE_ANALYTICS_ID'] ?? '') }}" placeholder="G-XXXXXXXXXX">
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Google Tag Manager</td>
                    <td class="min-w-48 w-full">
                        <input type="text" class="kt-input" name="GOOGLE_TAG_MANAGER_ID" value="{{ old('GOOGLE_TAG_MANAGER_ID', $seoSettings['GOOGLE_TAG_MANAGER_ID'] ?? '') }}" placeholder="GTM-XXXXXXX">
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Meta description</td>
                    <td class="min-w-48 w-full">
                        <textarea rows="3" class="kt-input pt-1" name="META_DESCRIPTION">{{ old('META_DESCRIPTION', $seoSettings['META_DESCRIPTION'] ?? '') }}</textarea>
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Meta keywords</td>
                    <td class="min-w-48 w-full">
                        <input type="text" class="kt-input" name="META_KEYWORDS" value="{{ old('META_KEYWORDS', $seoSettings['META_KEYWORDS'] ?? '') }}" placeholder="keyword1, keyword2">
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Site verification</td>
                    <td class="min-w-48 w-full">
                        <input type="text" class="kt-input" name="GOOGLE_SITE_VERIFICATION" value="{{ old('GOOGLE_SITE_VERIFICATION', $seoSettings['GOOGLE_SITE_VERIFICATION'] ?? '') }}">
                    </td>
                </tr>
            </table>
        </div>
        </div>
    </div>

    <div class="kt-card min-w-full mb-6 settings-collapsible-card" id="wizard-google-reviews">
        @include('admin.settings.partials.collapsible-header', [
            'titleHtml' => 'Google Reviews',
            'headerClass' => 'px-5 py-5',
        ])
        <div class="settings-collapsible-body">
        <div class="kt-card-content p-5 lg:p-6">
            <p class="text-sm text-secondary-foreground mb-0">
                Toon reviews op de website. Vul Place ID of bedrijfsnaam in. De platform Maps API-sleutel wordt gebruikt.
            </p>
        </div>
        <div class="kt-card-table kt-scrollable-x-auto pb-3">
            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table">
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Place ID</td>
                    <td class="min-w-48 w-full">
                        <input type="text" class="kt-input" name="google_reviews_place_id" value="{{ old('google_reviews_place_id', $googleReviewsPlaceId ?? '') }}" placeholder="ChIJ...">
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Bedrijfsnaam</td>
                    <td class="min-w-48 w-full">
                        <input type="text" class="kt-input" name="google_reviews_business_name" value="{{ old('google_reviews_business_name', $googleReviewsBusinessName ?? '') }}" placeholder="bijv. {{ $company->name }}">
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Carousel-titel</td>
                    <td class="min-w-48 w-full">
                        <input type="text" class="kt-input" name="google_reviews_section_title" value="{{ old('google_reviews_section_title', $googleReviewsSectionTitle ?? '') }}" placeholder="Wat anderen zeggen">
                    </td>
                </tr>
            </table>
        </div>
        </div>
    </div>
    </div>

    <x-wizard.footer-actions :current-step="$currentStep" :company="$company">
        <button type="submit" name="skip_config" value="1" class="kt-btn kt-btn-outline">
            Overslaan
        </button>
        <button type="submit" class="kt-btn kt-btn-primary">
            Volgende
            <i class="ki-filled ki-arrow-right ms-2"></i>
        </button>
    </x-wizard.footer-actions>
</form>
@endsection
