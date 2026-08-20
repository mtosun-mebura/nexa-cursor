@extends('admin.layouts.app')

@include('admin.settings.partials.collapsible-section-assets')

@section('title', 'Configuraties')

@push('scripts')
<script src="{{ asset('assets/js/form-validation.js') }}"></script>
@endpush

@push('styles')
<style>
    .test-email-feedback-slot .field-feedback {
        margin-top: 0;
        padding-top: 0.15rem;
        line-height: 1.25;
    }
    #mail .kt-card-footer {
        padding-bottom: 0.5rem;
    }
    #test-email-btn .test-email-spinner {
        display: none;
        width: 1rem;
        height: 1rem;
        flex-shrink: 0;
        animation: test-email-spin 0.7s linear infinite;
    }
    #test-email-btn.is-loading .test-email-spinner {
        display: block;
    }
    #test-email-btn.is-loading .test-email-send-icon {
        display: none;
    }
    @keyframes test-email-spin {
        to { transform: rotate(360deg); }
    }
    #test-email-status[data-ok="1"] {
        color: #16a34a;
    }
    #test-email-status[data-ok="0"] {
        color: #dc2626;
    }
</style>
@endpush

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">
            Configuraties
        </h1>
    </div>

    @if(session('settings_tenant_save_notice'))
        <div class="mb-5 flex gap-3 rounded-lg border-2 border-orange-700 bg-orange-950 px-4 py-3 text-sm text-orange-50 shadow-md dark:border-orange-600 dark:bg-orange-950 dark:text-orange-50 dark:shadow-lg dark:shadow-orange-950/50" role="alert">
            <i class="ki-filled ki-information mt-0.5 shrink-0 text-2xl text-orange-300"></i>
            <div class="min-w-0 leading-relaxed font-medium text-orange-50">{{ session('settings_tenant_save_notice') }}</div>
        </div>
    @endif

    <!-- Success Alert -->
    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5" id="success-alert" role="alert">
            <i class="ki-filled ki-check-circle me-2"></i>
            {{ session('success') }}
        </div>
        @if(session('tenant_sync_report'))
            <div class="mb-5">
                @include('admin.settings.partials.tenant-sync-report', ['report' => session('tenant_sync_report')])
            </div>
        @endif
    @endif

    @if(session('success'))
        <div id="settings-success-toast"
             class="admin-fixed-toast rounded-lg border border-emerald-300/60 bg-emerald-50 text-emerald-900 dark:bg-emerald-950/90 dark:text-emerald-100 dark:border-emerald-700/50 px-4 py-3 opacity-0 translate-y-2 pointer-events-none transition-all duration-300"
             role="status"
             aria-live="polite"
             aria-atomic="true">
            <div class="flex items-start gap-2">
                <i class="ki-filled ki-check-circle text-emerald-600 mt-0.5"></i>
                <div class="text-sm font-medium">{{ session('success') }}</div>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="kt-alert kt-alert-danger mb-5" role="alert">
            <i class="ki-filled ki-information me-2"></i>
            <strong>Er zijn validatiefouten opgetreden:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-5 lg:gap-7.5" id="settings-collapsible-root">
    @if($tenantScopedSettingsActive ?? $adminTenantScopedActive ?? false)
        <!-- Mail Server Instellingen -->
        <div class="kt-card min-w-full settings-collapsible-card settings-collapsible-card--collapsed" id="mail">
            @include('admin.settings.partials.collapsible-header', ['titleHtml' => '<i class="ki-filled ki-sms me-2"></i> Mail Server Instellingen'])
            <div class="settings-collapsible-body">
            <div class="kt-card-table kt-scrollable-x-auto pb-0">
                <form method="POST" action="{{ route('admin.settings.mail.update') }}" data-validate="true">
                    @csrf
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Mailer *</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <select class="kt-select @error('MAIL_MAILER') border-destructive @enderror" 
                                            id="MAIL_MAILER" name="MAIL_MAILER" required>
                                        <option value="log" {{ old('MAIL_MAILER', $mailSettings['MAIL_MAILER']) === 'log' ? 'selected' : '' }}>Log (alleen loggen)</option>
                                        <option value="smtp" {{ old('MAIL_MAILER', $mailSettings['MAIL_MAILER']) === 'smtp' ? 'selected' : '' }}>SMTP</option>
                                        <option value="sendmail" {{ old('MAIL_MAILER', $mailSettings['MAIL_MAILER']) === 'sendmail' ? 'selected' : '' }}>Sendmail</option>
                                        <option value="mailgun" {{ old('MAIL_MAILER', $mailSettings['MAIL_MAILER']) === 'mailgun' ? 'selected' : '' }}>Mailgun</option>
                                        <option value="ses" {{ old('MAIL_MAILER', $mailSettings['MAIL_MAILER']) === 'ses' ? 'selected' : '' }}>Amazon SES</option>
                                        <option value="postmark" {{ old('MAIL_MAILER', $mailSettings['MAIL_MAILER']) === 'postmark' ? 'selected' : '' }}>Postmark</option>
                                        <option value="resend" {{ old('MAIL_MAILER', $mailSettings['MAIL_MAILER']) === 'resend' ? 'selected' : '' }}>Resend</option>
                                    </select>
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Selecteer de mail transport methode</div>
                                @error('MAIL_MAILER')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">SMTP Host</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="text" 
                                           class="kt-input @error('MAIL_HOST') border-destructive @enderror" 
                                           id="MAIL_HOST" 
                                           name="MAIL_HOST" 
                                           value="{{ old('MAIL_HOST', $mailSettings['MAIL_HOST']) }}" 
                                           placeholder="smtp.example.com">
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">SMTP server hostname</div>
                                @error('MAIL_HOST')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">SMTP Poort</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="number" 
                                           class="kt-input @error('MAIL_PORT') border-destructive @enderror" 
                                           id="MAIL_PORT" 
                                           name="MAIL_PORT" 
                                           value="{{ old('MAIL_PORT', $mailSettings['MAIL_PORT']) }}" 
                                           placeholder="587" 
                                           min="1" 
                                           max="65535">
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Meestal 587 (TLS) of 465 (SSL)</div>
                                @error('MAIL_PORT')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Encryptie</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <select class="kt-select @error('MAIL_ENCRYPTION') border-destructive @enderror" 
                                            id="MAIL_ENCRYPTION" 
                                            name="MAIL_ENCRYPTION">
                                        <option value="tls" {{ old('MAIL_ENCRYPTION', $mailSettings['MAIL_ENCRYPTION']) === 'tls' ? 'selected' : '' }}>TLS</option>
                                        <option value="ssl" {{ old('MAIL_ENCRYPTION', $mailSettings['MAIL_ENCRYPTION']) === 'ssl' ? 'selected' : '' }}>SSL</option>
                                        <option value="null" {{ old('MAIL_ENCRYPTION', $mailSettings['MAIL_ENCRYPTION']) === 'null' || empty(old('MAIL_ENCRYPTION', $mailSettings['MAIL_ENCRYPTION'])) ? 'selected' : '' }}>Geen</option>
                                    </select>
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Encryptie type voor SMTP verbinding</div>
                                @error('MAIL_ENCRYPTION')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">SMTP Gebruikersnaam</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="text" 
                                           class="kt-input @error('MAIL_USERNAME') border-destructive @enderror" 
                                           id="MAIL_USERNAME" 
                                           name="MAIL_USERNAME" 
                                           value="{{ old('MAIL_USERNAME', $mailSettings['MAIL_USERNAME']) }}" 
                                           placeholder="your-username">
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">SMTP authenticatie gebruikersnaam</div>
                                @error('MAIL_USERNAME')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">SMTP Wachtwoord</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="password" 
                                           class="kt-input @error('MAIL_PASSWORD') border-destructive @enderror" 
                                           id="MAIL_PASSWORD" 
                                           name="MAIL_PASSWORD" 
                                           value="" 
                                           placeholder="Laat leeg om niet te wijzigen">
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Laat leeg om het huidige wachtwoord te behouden</div>
                                @error('MAIL_PASSWORD')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">From Adres *</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="email" 
                                           class="kt-input @error('MAIL_FROM_ADDRESS') border-destructive @enderror" 
                                           id="MAIL_FROM_ADDRESS" 
                                           name="MAIL_FROM_ADDRESS" 
                                           value="{{ old('MAIL_FROM_ADDRESS', $mailSettings['MAIL_FROM_ADDRESS']) }}" 
                                           placeholder="noreply@nexa-skillmatching.nl" 
                                           required>
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">E-mailadres waarvan emails worden verzonden</div>
                                @error('MAIL_FROM_ADDRESS')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">From Naam *</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="text" 
                                           class="kt-input @error('MAIL_FROM_NAME') border-destructive @enderror" 
                                           id="MAIL_FROM_NAME" 
                                           name="MAIL_FROM_NAME" 
                                           value="{{ old('MAIL_FROM_NAME', $mailSettings['MAIL_FROM_NAME']) }}" 
                                           placeholder="NEXA Skillmatching" 
                                           required>
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Naam die wordt getoond als afzender</div>
                                @error('MAIL_FROM_NAME')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    </table>
                    <div class="kt-card-footer flex justify-between items-start gap-5 pt-4 pb-2 border-t border-border">
                        <button type="submit" class="kt-btn kt-btn-primary mt-6">
                            <i class="ki-filled ki-check me-2"></i> Mail Instellingen Opslaan
                        </button>
                        <div class="flex flex-col min-w-0">
                            <label for="test-email-input" class="kt-form-label text-sm mb-1">Test Email</label>
                            <div class="flex items-start gap-2.5">
                                <div class="flex flex-col min-w-0 w-64">
                                    <div class="relative">
                                        <input type="email" class="kt-input w-full" id="test-email-input"
                                               placeholder="test@example.com">
                                    </div>
                                    <div class="test-email-feedback-slot min-h-5">
                                        <div class="field-feedback text-xs text-destructive leading-snug" data-field="test-email-input"></div>
                                    </div>
                                </div>
                                <button type="button" class="kt-btn kt-btn-outline shrink-0 inline-flex items-center gap-1.5" id="test-email-btn">
                                    <svg class="test-email-spinner" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity="0.25"></circle>
                                        <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                                    </svg>
                                    <i class="ki-filled ki-send test-email-send-icon"></i>
                                    <span class="test-email-btn-label">Verstuur Test</span>
                                </button>
                                <span id="test-email-status" class="hidden inline-flex items-center justify-center shrink-0 w-8 h-8 mt-0.5" role="status" aria-live="polite"></span>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            </div>
        </div>

        <!-- Google SEO Instellingen -->
        <div class="kt-card min-w-full settings-collapsible-card settings-collapsible-card--collapsed" id="seo">
            @include('admin.settings.partials.collapsible-header', ['titleHtml' => '<i class="ki-filled ki-abstract-26 me-2"></i> Google SEO &amp; Search Console'])
            <div class="settings-collapsible-body">
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <p class="text-sm text-muted-foreground px-3 sm:px-5 pt-4 mb-0 max-w-3xl">
                    Koppel uw betaalde Google SEO-stack: Search Console API (service account), Analytics (GA4), Tag Manager en site-verificatie.
                    Sitemap: <a href="{{ route('sitemap') }}" target="_blank" rel="noopener" class="text-primary underline">{{ route('sitemap') }}</a>
                </p>
                <form method="POST" action="{{ route('admin.settings.seo.update') }}" data-validate="true" id="google-seo-settings-form">
                    @csrf
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">Search Console API</td>
                            <td class="min-w-48 w-full">
                                <input type="hidden" name="GOOGLE_SEARCH_CONSOLE_ENABLED" value="0">
                                <label class="kt-label flex items-center gap-2 mb-3" for="GOOGLE_SEARCH_CONSOLE_ENABLED">
                                    <input type="checkbox"
                                           class="kt-switch kt-switch-sm shrink-0"
                                           id="GOOGLE_SEARCH_CONSOLE_ENABLED"
                                           name="GOOGLE_SEARCH_CONSOLE_ENABLED"
                                           value="1"
                                           {{ old('GOOGLE_SEARCH_CONSOLE_ENABLED', $seoSettings['GOOGLE_SEARCH_CONSOLE_ENABLED'] ?? '0') === '1' ? 'checked' : '' }}>
                                    <span class="text-sm text-secondary-foreground">API-koppeling actief (service account)</span>
                                </label>
                                @if(!empty($seoSettings['service_account_configured']) && ($seoSettings['service_account_configured'] ?? '') === '1')
                                    <p class="text-xs text-success mb-2">
                                        Service account gekoppeld{{ !empty($seoSettings['service_account_client_email']) ? ': '.$seoSettings['service_account_client_email'] : '' }}
                                    </p>
                                @endif
                                <label class="block text-sm font-medium text-secondary-foreground mb-1" for="GOOGLE_SEARCH_CONSOLE_SERVICE_ACCOUNT_JSON">Service account JSON</label>
                                <textarea id="GOOGLE_SEARCH_CONSOLE_SERVICE_ACCOUNT_JSON"
                                          name="GOOGLE_SEARCH_CONSOLE_SERVICE_ACCOUNT_JSON"
                                          rows="4"
                                          class="kt-input w-full font-mono text-xs"
                                          placeholder='Plak hier het JSON-bestand uit Google Cloud (IAM → Service accounts → Keys). Laat leeg om de huidige sleutel te behouden.'></textarea>
                                <p class="text-xs text-muted-foreground mt-1 mb-3">Voeg het service account e-mailadres toe als <strong>gebruiker</strong> in Google Search Console (property-instellingen).</p>
                                <div class="flex flex-wrap gap-2 mb-2">
                                    <button type="button" class="kt-btn kt-btn-sm kt-btn-outline" id="google-seo-test-btn">
                                        Test koppeling
                                    </button>
                                    <button type="button" class="kt-btn kt-btn-sm kt-btn-outline" id="google-seo-sitemap-btn">
                                        Sitemap indienen
                                    </button>
                                </div>
                                <p id="google-seo-api-feedback" class="text-xs mb-0 hidden" role="status" aria-live="polite"></p>
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Google Search Console Property</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="text" 
                                           class="kt-input @error('GOOGLE_SEO_PROPERTY_ID') border-destructive @enderror" 
                                           id="GOOGLE_SEO_PROPERTY_ID" 
                                           name="GOOGLE_SEO_PROPERTY_ID" 
                                           value="{{ old('GOOGLE_SEO_PROPERTY_ID', $seoSettings['GOOGLE_SEO_PROPERTY_ID'] ?? '') }}" 
                                           placeholder="sc-domain:example.com">
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Property in Search Console: <code>sc-domain:example.com</code> of <code>https://www.example.com/</code></div>
                                @error('GOOGLE_SEO_PROPERTY_ID')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Sitemap-pad</td>
                            <td class="min-w-48 w-full">
                                <input type="text"
                                       class="kt-input @error('GOOGLE_SEARCH_CONSOLE_SITEMAP_PATH') border-destructive @enderror"
                                       id="GOOGLE_SEARCH_CONSOLE_SITEMAP_PATH"
                                       name="GOOGLE_SEARCH_CONSOLE_SITEMAP_PATH"
                                       value="{{ old('GOOGLE_SEARCH_CONSOLE_SITEMAP_PATH', $seoSettings['GOOGLE_SEARCH_CONSOLE_SITEMAP_PATH'] ?? 'sitemap.xml') }}"
                                       placeholder="sitemap.xml">
                                <div class="text-xs text-muted-foreground mt-1">Publieke URL voor Google: {{ url('/sitemap.xml') }} (standaard)</div>
                                <label class="kt-label flex items-center gap-2 mt-3 mb-0" for="GOOGLE_SEARCH_CONSOLE_AUTO_SITEMAP">
                                    <input type="hidden" name="GOOGLE_SEARCH_CONSOLE_AUTO_SITEMAP" value="0">
                                    <input type="checkbox"
                                           class="kt-checkbox shrink-0"
                                           id="GOOGLE_SEARCH_CONSOLE_AUTO_SITEMAP"
                                           name="GOOGLE_SEARCH_CONSOLE_AUTO_SITEMAP"
                                           value="1"
                                           {{ old('GOOGLE_SEARCH_CONSOLE_AUTO_SITEMAP', $seoSettings['GOOGLE_SEARCH_CONSOLE_AUTO_SITEMAP'] ?? '1') === '1' ? 'checked' : '' }}>
                                    <span class="text-sm text-muted-foreground">Sitemap automatisch indienen na opslaan</span>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Google Analytics Tracking ID</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="text" 
                                           class="kt-input @error('GOOGLE_ANALYTICS_ID') border-destructive @enderror" 
                                           id="GOOGLE_ANALYTICS_ID" 
                                           name="GOOGLE_ANALYTICS_ID" 
                                           value="{{ old('GOOGLE_ANALYTICS_ID', $seoSettings['GOOGLE_ANALYTICS_ID'] ?? '') }}" 
                                           placeholder="G-XXXXXXXXXX of UA-XXXXXXXXX-X">
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Google Analytics Tracking ID (GA4: G-XXXXXXXXXX of Universal: UA-XXXXXXXXX-X)</div>
                                @error('GOOGLE_ANALYTICS_ID')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Google Tag Manager ID</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="text" 
                                           class="kt-input @error('GOOGLE_TAG_MANAGER_ID') border-destructive @enderror" 
                                           id="GOOGLE_TAG_MANAGER_ID" 
                                           name="GOOGLE_TAG_MANAGER_ID" 
                                           value="{{ old('GOOGLE_TAG_MANAGER_ID', $seoSettings['GOOGLE_TAG_MANAGER_ID'] ?? '') }}" 
                                           placeholder="GTM-XXXXXXX">
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Google Tag Manager Container ID (GTM-XXXXXXX)</div>
                                @error('GOOGLE_TAG_MANAGER_ID')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">Meta Description (Standaard)</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <textarea rows="3" 
                                              class="kt-input pt-1 @error('META_DESCRIPTION') border-destructive @enderror" 
                                              id="META_DESCRIPTION" 
                                              name="META_DESCRIPTION" 
                                              placeholder="Standaard meta description voor de website">{{ old('META_DESCRIPTION', $seoSettings['META_DESCRIPTION'] ?? '') }}</textarea>
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Standaard meta description die wordt gebruikt wanneer er geen specifieke beschrijving is ingesteld</div>
                                @error('META_DESCRIPTION')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Meta Keywords</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="text" 
                                           class="kt-input @error('META_KEYWORDS') border-destructive @enderror" 
                                           id="META_KEYWORDS" 
                                           name="META_KEYWORDS" 
                                           value="{{ old('META_KEYWORDS', $seoSettings['META_KEYWORDS'] ?? '') }}" 
                                           placeholder="keyword1, keyword2, keyword3">
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Standaard meta keywords, gescheiden door komma's</div>
                                @error('META_KEYWORDS')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Site Verification Code</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="text" 
                                           class="kt-input @error('GOOGLE_SITE_VERIFICATION') border-destructive @enderror" 
                                           id="GOOGLE_SITE_VERIFICATION" 
                                           name="GOOGLE_SITE_VERIFICATION" 
                                           value="{{ old('GOOGLE_SITE_VERIFICATION', $seoSettings['GOOGLE_SITE_VERIFICATION'] ?? '') }}" 
                                           placeholder="abc123def456">
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Google Search Console site verification code</div>
                                @error('GOOGLE_SITE_VERIFICATION')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    </table>
                    <div class="kt-card-footer flex justify-end items-center gap-5 pt-5 border-t border-border">
                        <button type="submit" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-check me-2"></i> SEO Instellingen Opslaan
                        </button>
                    </div>
                </form>
            </div>
            </div>
        </div>

        <!-- Google Maps Instellingen -->
        <div class="kt-card min-w-full settings-collapsible-card settings-collapsible-card--collapsed" id="maps">
            @include('admin.settings.partials.collapsible-header', ['titleHtml' => '<i class="ki-filled ki-geolocation me-2"></i> Google Maps Configuratie'])
            <div class="settings-collapsible-body">
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <form method="POST" action="{{ route('admin.settings.maps.update') }}" data-validate="true">
                    @csrf
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Google Maps API Key *</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="text" 
                                           class="kt-input @error('GOOGLE_MAPS_API_KEY') border-destructive @enderror" 
                                           id="GOOGLE_MAPS_API_KEY" 
                                           name="GOOGLE_MAPS_API_KEY" 
                                           value="{{ old('GOOGLE_MAPS_API_KEY', $mapsSettings['GOOGLE_MAPS_API_KEY'] ?? '') }}" 
                                           placeholder="AIzaSy..." 
                                           required>
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Google Maps JavaScript API key voor kaarten en geocoding</div>
                                @error('GOOGLE_MAPS_API_KEY')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Map ID (optioneel)</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="text"
                                           class="kt-input @error('GOOGLE_MAPS_MAP_ID') border-destructive @enderror"
                                           id="GOOGLE_MAPS_MAP_ID"
                                           name="GOOGLE_MAPS_MAP_ID"
                                           value="{{ old('GOOGLE_MAPS_MAP_ID', $mapsSettings['GOOGLE_MAPS_MAP_ID'] ?? '') }}"
                                           placeholder="bijv. abc123def456">
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Voor Advanced Markers (geen deprecation-warning). Aanmaken in <a href="https://console.cloud.google.com/google/maps-apis/studio/maps" target="_blank" rel="noopener" class="underline">Google Cloud Console → Map Management</a></div>
                                @error('GOOGLE_MAPS_MAP_ID')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Standaard Zoom Level</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="number" 
                                           class="kt-input @error('GOOGLE_MAPS_ZOOM') border-destructive @enderror" 
                                           id="GOOGLE_MAPS_ZOOM" 
                                           name="GOOGLE_MAPS_ZOOM" 
                                           value="{{ old('GOOGLE_MAPS_ZOOM', $mapsSettings['GOOGLE_MAPS_ZOOM'] ?? '12') }}" 
                                           placeholder="12" 
                                           min="1" 
                                           max="20">
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Standaard zoom level voor kaarten (1-20, standaard: 12)</div>
                                @error('GOOGLE_MAPS_ZOOM')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Standaard Centrum (Latitude)</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="text" 
                                           class="kt-input @error('GOOGLE_MAPS_CENTER_LAT') border-destructive @enderror" 
                                           id="GOOGLE_MAPS_CENTER_LAT" 
                                           name="GOOGLE_MAPS_CENTER_LAT" 
                                           value="{{ old('GOOGLE_MAPS_CENTER_LAT', $mapsSettings['GOOGLE_MAPS_CENTER_LAT'] ?? '52.3676') }}" 
                                           placeholder="52.3676">
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Standaard latitude voor kaart centrum (bijv. 52.3676 voor Amsterdam)</div>
                                @error('GOOGLE_MAPS_CENTER_LAT')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Standaard Centrum (Longitude)</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="text" 
                                           class="kt-input @error('GOOGLE_MAPS_CENTER_LNG') border-destructive @enderror" 
                                           id="GOOGLE_MAPS_CENTER_LNG" 
                                           name="GOOGLE_MAPS_CENTER_LNG" 
                                           value="{{ old('GOOGLE_MAPS_CENTER_LNG', $mapsSettings['GOOGLE_MAPS_CENTER_LNG'] ?? '4.9041') }}" 
                                           placeholder="4.9041">
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Standaard longitude voor kaart centrum (bijv. 4.9041 voor Amsterdam)</div>
                                @error('GOOGLE_MAPS_CENTER_LNG')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Map Type</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <select class="kt-select @error('GOOGLE_MAPS_TYPE') border-destructive @enderror" 
                                            id="GOOGLE_MAPS_TYPE" 
                                            name="GOOGLE_MAPS_TYPE">
                                        <option value="roadmap" {{ old('GOOGLE_MAPS_TYPE', $mapsSettings['GOOGLE_MAPS_TYPE'] ?? 'roadmap') === 'roadmap' ? 'selected' : '' }}>Roadmap</option>
                                        <option value="satellite" {{ old('GOOGLE_MAPS_TYPE', $mapsSettings['GOOGLE_MAPS_TYPE'] ?? 'roadmap') === 'satellite' ? 'selected' : '' }}>Satellite</option>
                                        <option value="hybrid" {{ old('GOOGLE_MAPS_TYPE', $mapsSettings['GOOGLE_MAPS_TYPE'] ?? 'roadmap') === 'hybrid' ? 'selected' : '' }}>Hybrid</option>
                                        <option value="terrain" {{ old('GOOGLE_MAPS_TYPE', $mapsSettings['GOOGLE_MAPS_TYPE'] ?? 'roadmap') === 'terrain' ? 'selected' : '' }}>Terrain</option>
                                    </select>
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Standaard kaart type</div>
                                @error('GOOGLE_MAPS_TYPE')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    </table>
                    <div class="kt-card-footer flex justify-end items-center gap-5 pt-5 border-t border-border">
                        <button type="submit" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-check me-2"></i> Maps Instellingen Opslaan
                        </button>
                    </div>
                </form>
            </div>
            </div>
        </div>

        <!-- Google Reviews (zelfde Maps API-sleutel; Places API moet ingeschakeld zijn) -->
        <div class="kt-card min-w-full settings-collapsible-card settings-collapsible-card--collapsed" id="google-reviews">
            <style>
            #google-reviews input[type="number"]::-webkit-outer-spin-button,
            #google-reviews input[type="number"]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
            #google-reviews input[type="number"] { -moz-appearance: textfield; appearance: textfield; }
            #google-reviews .grw-cache-hours-input { width: 4.5rem; min-width: 4.5rem; padding-right: 0.5rem !important; }
            </style>
            @include('admin.settings.partials.collapsible-header', ['titleHtml' => '<i class="ki-filled ki-star me-2"></i> Google Reviews'])
            <div class="settings-collapsible-body">
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <form method="POST" action="{{ route('admin.settings.google-reviews.update') }}" data-validate="true" id="google-reviews-form">
                    @csrf
                    <p class="text-sm text-muted-foreground mb-4 p-2">Toon Google-reviews in een carousel op de website. Vul <strong>ofwel</strong> het Place ID in (uit Google Maps/Business Profile) <strong>ofwel</strong> de bedrijfsnaam; bij bedrijfsnaam wordt gezocht en het eerste resultaat gebruikt. Dezelfde Maps API-sleutel wordt gebruikt; zorg dat de <strong>Places API</strong> is ingeschakeld.</p>
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Place ID</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="text"
                                           class="kt-input @error('google_reviews_place_id') border-destructive @enderror"
                                           id="google_reviews_place_id"
                                           name="google_reviews_place_id"
                                           value="{{ old('google_reviews_place_id', $googleReviewsPlaceId ?? '') }}"
                                           maxlength="255"
                                           placeholder="ChIJ...">
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Optioneel. Google Place ID (bijv. ChIJ...) van je bedrijf. Heeft voorrang op bedrijfsnaam.</div>
                                @error('google_reviews_place_id')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Bedrijfsnaam</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="text"
                                           class="kt-input @error('google_reviews_business_name') border-destructive @enderror"
                                           id="google_reviews_business_name"
                                           name="google_reviews_business_name"
                                           value="{{ old('google_reviews_business_name', $googleReviewsBusinessName ?? '') }}"
                                           maxlength="255"
                                           placeholder="bijv. Nexa Taxi Amsterdam">
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Optioneel. Wordt gebruikt als Place ID leeg is; zoekt op naam en neemt het eerste resultaat (regio NL).</div>
                                @error('google_reviews_business_name')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Carousel-titel</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="text"
                                           class="kt-input @error('google_reviews_section_title') border-destructive @enderror"
                                           id="google_reviews_section_title"
                                           name="google_reviews_section_title"
                                           value="{{ old('google_reviews_section_title', $googleReviewsSectionTitle ?? '') }}"
                                           maxlength="255"
                                           placeholder="Standaard: Wat anderen zeggen">
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Tekst boven de review-slider op de website. Laat leeg voor de standaardtekst.</div>
                                @error('google_reviews_section_title')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Achtergrondkleur sectie</td>
                            <td class="min-w-48 w-full">
                                @php
                                    $__grSettingsBg = trim((string) old('google_reviews_section_background', $googleReviewsSectionBackground ?? ''));
                                    $__grSettingsBgPicker = $__grSettingsBg !== '' ? \App\Services\GoogleReviewsService::normalizeHexColor($__grSettingsBg) : '';
                                    if ($__grSettingsBgPicker === '') {
                                        $__grSettingsBgPicker = '#f3f4f6';
                                    }
                                @endphp
                                <div class="flex items-center gap-2 relative" style="position: relative; width: 100%;">
                                    <input type="color"
                                           id="google_reviews_section_background_picker"
                                           class="h-9 w-14 cursor-pointer rounded border border-input bg-background p-1 shrink-0"
                                           value="{{ $__grSettingsBgPicker }}"
                                           title="Kies achtergrondkleur"
                                           aria-label="Achtergrondkleur Google Reviews-sectie">
                                    <input type="text"
                                           class="kt-input font-mono text-sm flex-1 min-w-0 max-w-xs @error('google_reviews_section_background') border-destructive @enderror"
                                           id="google_reviews_section_background"
                                           name="google_reviews_section_background"
                                           value="{{ $__grSettingsBg }}"
                                           maxlength="7"
                                           placeholder="Leeg = standaard (#f3f4f6)"
                                           pattern="^#?([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})?$">
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Hex (#RGB of #RRGGBB). Leeg = standaard thema-achtergrond.</div>
                                @error('google_reviews_section_background')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Aantal reviews</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="number"
                                           class="kt-input w-24 @error('google_reviews_count') border-destructive @enderror"
                                           id="google_reviews_count"
                                           name="google_reviews_count"
                                           value="{{ old('google_reviews_count', $googleReviewsCount ?? 5) }}"
                                           min="1"
                                           max="5">
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Hoeveel reviews getoond worden in de carousel (1–5). De Google Places API levert maximaal 5 reviews per plaats. Het getal “Gebaseerd op X beoordelingen” is het totaal aantal beoordelingen van Google.</div>
                                @error('google_reviews_count')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Min. sterren</td>
                            <td class="min-w-48 w-full">
                                <input type="hidden" name="google_reviews_min_stars" id="google_reviews_min_stars" value="{{ old('google_reviews_min_stars', $googleReviewsMinStars ?? 1) }}">
                                <div class="grw-admin-star-picker flex items-center gap-1" role="group" aria-label="Minimaal aantal sterren">
                                    @php $minStarsVal = (int) old('google_reviews_min_stars', $googleReviewsMinStars ?? 1); @endphp
                                    @for($s = 1; $s <= 5; $s++)
                                        <button type="button"
                                                class="grw-admin-star w-8 h-8 rounded p-0 flex items-center justify-center text-xl text-muted-foreground hover:text-yellow-500 dark:hover:text-yellow-400 transition-colors focus:outline-none {{ $s <= $minStarsVal ? 'text-yellow-500 dark:text-yellow-400' : '' }}"
                                                data-value="{{ $s }}"
                                                aria-label="Minimaal {{ $s }} {{ $s === 1 ? 'ster' : 'sterren' }}">
                                            <span class="grw-admin-star-icon">★</span>
                                        </button>
                                    @endfor
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Alleen reviews met dit aantal sterren of meer tonen. Klik een ster om te selecteren.</div>
                                @error('google_reviews_min_stars')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Cacheduur (uren)</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="number"
                                           class="kt-input grw-cache-hours-input @error('google_reviews_cache_hours') border-destructive @enderror"
                                           id="google_reviews_cache_hours"
                                           name="google_reviews_cache_hours"
                                           value="{{ old('google_reviews_cache_hours', $googleReviewsCacheHours ?? '24') }}"
                                           min="1"
                                           max="168"
                                           size="3"
                                           inputmode="numeric"
                                           pattern="[0-9]*">
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Hoe lang reviews gecached worden (1–168 uur)</div>
                                @error('google_reviews_cache_hours')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    </table>
                    <div class="kt-card-footer flex justify-end items-center gap-5 pt-5 border-t border-border">
                        <button type="submit" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-check me-2"></i> Google Reviews Opslaan
                        </button>
                    </div>
                </form>
                <script>
                (function() {
                    var picker = document.querySelector('#google-reviews .grw-admin-star-picker');
                    var hidden = document.getElementById('google_reviews_min_stars');
                    if (!picker || !hidden) return;
                    var buttons = picker.querySelectorAll('.grw-admin-star');
                    function updateStars(value) {
                        var v = parseInt(value, 10) || 1;
                        v = Math.max(1, Math.min(5, v));
                        hidden.value = v;
                        buttons.forEach(function(btn) {
                            var starVal = parseInt(btn.getAttribute('data-value'), 10);
                            if (starVal <= v) {
                                btn.classList.add('text-yellow-500', 'dark:text-yellow-400');
                                btn.classList.remove('text-muted-foreground');
                            } else {
                                btn.classList.remove('text-yellow-500', 'dark:text-yellow-400');
                                btn.classList.add('text-muted-foreground');
                            }
                        });
                    }
                    buttons.forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            updateStars(btn.getAttribute('data-value'));
                        });
                    });
                    updateStars(hidden.value);
                })();
                (function() {
                    var input = document.getElementById('google_reviews_cache_hours');
                    if (input) {
                        input.addEventListener('input', function() {
                            var v = this.value.replace(/\D/g, '');
                            if (v.length > 3) v = v.slice(0, 3);
                            this.value = v === '' ? '' : Math.min(parseInt(v, 10) || 0, 999);
                        });
                    }
                })();
                (function() {
                    var pick = document.getElementById('google_reviews_section_background_picker');
                    var hex = document.getElementById('google_reviews_section_background');
                    if (!pick || !hex) return;
                    pick.addEventListener('input', function() {
                        hex.value = pick.value;
                    });
                    hex.addEventListener('input', function() {
                        var valBg = (hex.value || '').trim();
                        if (valBg === '') {
                            pick.value = '#f3f4f6';
                            return;
                        }
                        var h = valBg[0] === '#' ? valBg : '#' + valBg;
                        if (/^#([A-Fa-f0-9]{3})$/.test(h)) {
                            h = '#' + h[1] + h[1] + h[2] + h[2] + h[3] + h[3];
                        }
                        if (/^#([A-Fa-f0-9]{6})$/.test(h)) {
                            pick.value = h.toLowerCase();
                        }
                    });
                })();
                </script>
            </div>
            </div>
        </div>

        <!-- WhatsApp: tenant widget / click-to-chat (Business API staat onder Algemene configuraties) -->
        <div class="kt-card min-w-full settings-collapsible-card settings-collapsible-card--collapsed" id="whatsapp">
            @include('admin.settings.partials.collapsible-header', ['titleHtml' => '<i class="ki-filled ki-whatsapp me-2"></i> WhatsApp (tenant)'])
            <div class="settings-collapsible-body">
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <div class="px-5 pb-3 text-xs text-muted-foreground" style="padding-top: 10px;">
                    Widget en optionele click-to-chat voor deze tenant.
                    De <strong>WhatsApp Business API</strong> (token / Phone Number ID) configureer je platform-breed onder
                    <a href="{{ route('admin.settings.general.index') }}#whatsapp" class="underline">Algemene configuraties</a>.
                    @if(!empty($whatsappPlatformConfigured))
                        <span class="text-emerald-700 dark:text-emerald-300">Platform-API is actief — boekingsberichten gaan automatisch via Cloud API; click-to-chat is uitgeschakeld.</span>
                    @endif
                </div>
                <form method="POST" action="{{ route('admin.settings.whatsapp.update') }}" data-validate="true">
                    @csrf
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td colspan="2" class="pt-2">
                                <div class="rounded-lg border border-border bg-background px-4 py-3">
                                    <div class="text-sm font-semibold text-secondary-foreground">WhatsApp Direct (zonder Business API)</div>
                                    <div class="text-xs text-muted-foreground mt-1">Alleen relevant als de platform Business API niet is geconfigureerd.</div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">WhatsApp Direct inschakelen</td>
                            <td class="min-w-48 w-full">
                                <label class="inline-flex items-center gap-2">
                                    <input type="hidden" name="WHATSAPP_CLICK_TO_CHAT_ENABLED" value="0">
                                    <input type="checkbox"
                                           class="kt-checkbox"
                                           id="WHATSAPP_CLICK_TO_CHAT_ENABLED"
                                           name="WHATSAPP_CLICK_TO_CHAT_ENABLED"
                                           value="1"
                                           {{ old('WHATSAPP_CLICK_TO_CHAT_ENABLED', $whatsappSettings['WHATSAPP_CLICK_TO_CHAT_ENABLED'] ?? '0') === '1' ? 'checked' : '' }}
                                           @if(!empty($whatsappPlatformConfigured)) disabled @endif>
                                    <span class="text-sm text-secondary-foreground">Fallback: boekingsknop opent WhatsApp (alleen zonder Business API)</span>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">WhatsApp Nummer (zonder Business API)</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="tel"
                                           class="kt-input @error('WHATSAPP_CLICK_TO_CHAT_NUMBER') border-destructive @enderror"
                                           id="WHATSAPP_CLICK_TO_CHAT_NUMBER"
                                           name="WHATSAPP_CLICK_TO_CHAT_NUMBER"
                                           value="{{ old('WHATSAPP_CLICK_TO_CHAT_NUMBER', $whatsappSettings['WHATSAPP_CLICK_TO_CHAT_NUMBER'] ?? '') }}"
                                           placeholder="0612345678 of +31612345678"
                                           autocomplete="tel">
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Nummer voor wa.me-fallback (zonder Business API).</div>
                                @error('WHATSAPP_CLICK_TO_CHAT_NUMBER')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" class="pt-4">
                                <div class="rounded-lg border border-border bg-background px-4 py-3">
                                    <div class="text-sm font-semibold text-secondary-foreground">Boekingsmelding naar bedrijf</div>
                                    <div class="text-xs text-muted-foreground mt-1">
                                        Ontvangernummer voor WhatsApp bij nieuwe boekingen (template “dispatch”).
                                        Aan/uit staat onder Algemene configuraties → WhatsApp Business API →
                                        <a href="{{ route('admin.settings.general.index') }}#whatsapp-booking-templates" class="underline">Boekingssjablonen</a>.
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">WhatsApp-nummer bedrijf</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="tel"
                                           class="kt-input @error('WHATSAPP_COMPANY_BOOKING_NOTIFY_NUMBER') border-destructive @enderror"
                                           id="WHATSAPP_COMPANY_BOOKING_NOTIFY_NUMBER"
                                           name="WHATSAPP_COMPANY_BOOKING_NOTIFY_NUMBER"
                                           value="{{ old('WHATSAPP_COMPANY_BOOKING_NOTIFY_NUMBER', $whatsappSettings['WHATSAPP_COMPANY_BOOKING_NOTIFY_NUMBER'] ?? '') }}"
                                           placeholder="0612345678 of +31612345678"
                                           autocomplete="tel">
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Leeg = geen bericht naar het bedrijf, ook als de platform-optie aan staat.</div>
                                @error('WHATSAPP_COMPANY_BOOKING_NOTIFY_NUMBER')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" class="pt-4">
                                <div class="rounded-lg border border-border bg-background px-4 py-3">
                                    <div class="text-sm font-semibold text-secondary-foreground">Frontend WhatsApp Widget</div>
                                    <div class="text-xs text-muted-foreground mt-1">Toont rechtsonder op de frontend een WhatsApp-icoon.</div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Widget tonen op frontend</td>
                            <td class="min-w-48 w-full">
                                <label class="inline-flex items-center gap-2">
                                    <input type="hidden" name="WHATSAPP_WIDGET_ENABLED" value="0">
                                    <input type="checkbox"
                                           class="kt-checkbox"
                                           id="WHATSAPP_WIDGET_ENABLED"
                                           name="WHATSAPP_WIDGET_ENABLED"
                                           value="1"
                                           {{ old('WHATSAPP_WIDGET_ENABLED', $whatsappSettings['WHATSAPP_WIDGET_ENABLED'] ?? '0') === '1' ? 'checked' : '' }}>
                                    <span class="text-sm text-secondary-foreground">WhatsApp widget rechtsonder weergeven</span>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Widget telefoonnummer</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="tel"
                                           class="kt-input @error('WHATSAPP_WIDGET_PHONE') border-destructive @enderror"
                                           id="WHATSAPP_WIDGET_PHONE"
                                           name="WHATSAPP_WIDGET_PHONE"
                                           value="{{ old('WHATSAPP_WIDGET_PHONE', $whatsappSettings['WHATSAPP_WIDGET_PHONE'] ?? '') }}"
                                           placeholder="0612345678 of +31612345678"
                                           autocomplete="tel">
                                </div>
                                @error('WHATSAPP_WIDGET_PHONE')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">Widget standaardbericht</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <textarea rows="3"
                                              class="kt-input pt-1 @error('WHATSAPP_WIDGET_DEFAULT_MESSAGE') border-destructive @enderror"
                                              id="WHATSAPP_WIDGET_DEFAULT_MESSAGE"
                                              name="WHATSAPP_WIDGET_DEFAULT_MESSAGE"
                                              placeholder="Hallo, ik heb een vraag over jullie diensten.">{{ old('WHATSAPP_WIDGET_DEFAULT_MESSAGE', $whatsappSettings['WHATSAPP_WIDGET_DEFAULT_MESSAGE'] ?? 'Hallo, ik heb een vraag over jullie diensten.') }}</textarea>
                                </div>
                                @error('WHATSAPP_WIDGET_DEFAULT_MESSAGE')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    </table>
                    <div class="kt-card-footer flex justify-end items-center gap-5 pt-5 border-t border-border">
                        <button type="submit" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-check me-2"></i> WhatsApp tenant opslaan
                        </button>
                    </div>
                </form>
            </div>
            </div>
        </div>

        <div class="kt-card min-w-full settings-collapsible-card settings-collapsible-card--collapsed" id="mollie">
            @include('admin.settings.partials.collapsible-header', ['titleHtml' => '<i class="ki-filled ki-dollar me-2"></i> Mollie (tenant)'])
            <div class="settings-collapsible-body">
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <div class="px-5 pb-3 text-xs text-muted-foreground" style="padding-top: 10px;">
                    Eigen Mollie-omgeving van <strong>deze tenant</strong>. Betalingen in de chauffeur-app (QR) en optioneel bij websiteboekingen gaan via deze API-sleutel.
                    Het geld komt op de Mollie-rekening van het taxibedrijf, niet op die van Nexa.
                    Dit is niet de SaaS-facturatie van het platform.
                </div>
                <form method="POST" action="{{ route('admin.settings.mollie.update') }}" data-validate="true">
                    @csrf
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top pt-4">Status</td>
                            <td class="min-w-48 w-full pt-4">
                                @if(!empty($mollieSummary['configured']) && !empty($mollieSummary['is_active']))
                                    <span class="text-sm text-emerald-700 dark:text-emerald-300">Actief{{ !empty($mollieSummary['test_mode']) ? ' (testmodus)' : '' }}</span>
                                @elseif(!empty($mollieSummary['provider']))
                                    <span class="text-sm text-amber-700 dark:text-amber-300">Opgeslagen, maar niet actief of zonder geldige sleutel</span>
                                @else
                                    <span class="text-sm text-muted-foreground">Nog niet ingesteld</span>
                                @endif
                                @if(!empty($mollieSummary['api_key_preview']))
                                    <div class="text-xs text-muted-foreground mt-1">Huidige sleutel: <code class="text-xs">{{ $mollieSummary['api_key_preview'] }}</code></div>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Mollie API-sleutel{{ empty($mollieSummary['configured']) ? ' *' : '' }}</td>
                            <td class="min-w-48 w-full">
                                <input type="password"
                                       class="kt-input @error('mollie_api_key') border-destructive @enderror"
                                       id="mollie_api_key"
                                       name="mollie_api_key"
                                       value="{{ old('mollie_api_key') }}"
                                       autocomplete="new-password"
                                       placeholder="{{ !empty($mollieSummary['configured']) ? 'Leeg laten om de huidige sleutel te behouden' : 'test_… of live_…' }}">
                                <div class="text-xs text-muted-foreground mt-1">
                                    Uit het Mollie-dashboard van de tenant (Developers → API-keys). Begint met <code>test_</code> of <code>live_</code>.
                                    @if(!empty($mollieSummary['configured']))
                                        Leeg laten om de bestaande sleutel te houden.
                                    @endif
                                </div>
                                @error('mollie_api_key')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Actief</td>
                            <td class="min-w-48 w-full">
                                <label class="inline-flex items-center gap-2">
                                    <input type="hidden" name="mollie_is_active" value="0">
                                    <input type="checkbox"
                                           class="kt-checkbox"
                                           name="mollie_is_active"
                                           value="1"
                                           {{ old('mollie_is_active', (!isset($mollieSummary['provider']) || ($mollieSummary['is_active'] ?? true)) ? '1' : '0') === '1' ? 'checked' : '' }}>
                                    <span class="text-sm text-secondary-foreground">Gebruik deze Mollie-omgeving voor betalingen van deze tenant</span>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Testmodus</td>
                            <td class="min-w-48 w-full">
                                <label class="inline-flex items-center gap-2">
                                    <input type="hidden" name="mollie_test_mode" value="0">
                                    <input type="checkbox"
                                           class="kt-checkbox"
                                           name="mollie_test_mode"
                                           value="1"
                                           {{ old('mollie_test_mode', !empty($mollieSummary['test_mode']) ? '1' : '0') === '1' ? 'checked' : '' }}>
                                    <span class="text-sm text-secondary-foreground">Testomgeving (wordt automatisch aan gezet bij een test_-sleutel)</span>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Betalen in chauffeur-app</td>
                            <td class="min-w-48 w-full">
                                <label class="inline-flex items-center gap-2">
                                    <input type="hidden" name="mollie_driver_payments" value="0">
                                    <input type="checkbox"
                                           class="kt-checkbox"
                                           name="mollie_driver_payments"
                                           value="1"
                                           {{ old('mollie_driver_payments', !empty($mollieDriverPaymentsEnabled) ? '1' : '0') === '1' ? 'checked' : '' }}>
                                    <span class="text-sm text-secondary-foreground">Chauffeur toont een Mollie QR-code; het bedrag gaat naar de rekening van deze tenant</span>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Direct betalen bij boeking</td>
                            <td class="min-w-48 w-full">
                                <label class="inline-flex items-center gap-2">
                                    <input type="hidden" name="mollie_booking_payments" value="0">
                                    <input type="checkbox"
                                           class="kt-checkbox"
                                           name="mollie_booking_payments"
                                           value="1"
                                           {{ old('mollie_booking_payments', !empty($mollieBookingPaymentsEnabled) ? '1' : '0') === '1' ? 'checked' : '' }}>
                                    <span class="text-sm text-secondary-foreground">Klant betaalt via Mollie na het bevestigen van de websiteboeking</span>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Webhook-URL</td>
                            <td class="min-w-48 w-full">
                                <input type="url"
                                       class="kt-input @error('mollie_webhook_url') border-destructive @enderror"
                                       name="mollie_webhook_url"
                                       value="{{ old('mollie_webhook_url', $mollieSummary['webhook_url'] ?? '') }}"
                                       placeholder="{{ $defaultTaxiWebhookUrl ?? url('/api/taxi/webhooks/mollie') }}">
                                <div class="text-xs text-muted-foreground mt-1">
                                    Optioneel. Lokaal (<code>localhost</code>) kan Mollie niet bereiken; de chauffeur-app volgt de betaling via polling.
                                    Standaard: <code class="text-xs break-all">{{ $defaultTaxiWebhookUrl ?? url('/api/taxi/webhooks/mollie') }}</code>
                                </div>
                                @error('mollie_webhook_url')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    </table>
                    <div class="kt-card-footer flex justify-end items-center gap-5 pt-5 border-t border-border">
                        <button type="submit" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-check me-2"></i> Mollie tenant opslaan
                        </button>
                    </div>
                </form>
            </div>
            </div>
        </div>

        @endif

        <div class="kt-card min-w-full settings-collapsible-card settings-collapsible-card--collapsed" id="tenant-sync">
            @include('admin.settings.partials.collapsible-header', ['titleHtml' => '<i class="ki-filled ki-cloud-change me-2"></i> Omgeving-sync (tenant en NEXA SaaS)'])
            <div class="settings-collapsible-body">
            <div class="kt-card-content px-6 pb-4 space-y-6">
                @include('admin.settings.partials.heading-with-info', [
                    'tag' => 'p',
                    'class' => 'text-sm text-secondary-foreground m-0',
                    'title' => 'Configureer de doel-database en push een bron-tenant of de NEXA SaaS-website naar die omgeving.',
                    'infoId' => 'tenant-sync-intro-info',
                    'info' => 'Stel hier de <strong>doel-database</strong> in (bijv. productie). Daarna kun je een <strong>bron-tenant</strong> of de <strong>NEXA SaaS-website</strong> naar die database pushen. Tenant: de rij in <code>companies</code> plus alle rijen op tabellen met <code>company_id</code> voor dat bedrijf (alleen toevoegen, nieuwe id’s). NEXA SaaS: centrale <code>website_pages</code> (<code>company_id</code> null) en <code>nexa_pricing</code> (upsert). Bestanden gaan niet over het net — gebruik daarvoor ZIP-export/import.',
                ])

                @php
                    $tenantSyncTableCount = count($tenantSyncScope['tables_with_company_id'] ?? []);
                    $tenantSyncScopeInfo = 'Overzicht van tabellen die op <strong>deze</strong> omgeving meegaan bij volledige tenant-sync. Driver: <code>' . e($tenantSyncScope['driver'] ?? '?') . '</code>. Altijd mee: de <code>companies</code>-rij. Tabellen met <code>company_id</code> worden per gekozen tenant gekopieerd. Globale vereisten (modules, themes) gaan eerst. Nexa Taxi-tabellen alleen als de module aan de tenant gekoppeld is. Uitgesloten tabellen staan onderaan.';
                @endphp
                <div class="rounded-md border border-border bg-muted/30 settings-collapsible-section settings-collapsible-card--collapsed" id="tenant-sync-tables">
                    <div class="settings-collapsible-header px-3 py-2">
                        <div class="flex items-center gap-1 min-w-0">
                            <button type="button"
                                    class="settings-collapsible-toggle flex min-w-0 flex-1 items-center justify-between gap-3 border-0 bg-transparent p-0 text-start cursor-pointer min-h-[2rem]"
                                    aria-expanded="false">
                                <span class="kt-card-title mb-0 flex-1 min-w-0 text-sm font-medium">
                                    Tabellen op deze omgeving
                                    <span class="font-normal text-muted-foreground">({{ $tenantSyncTableCount }})</span>
                                </span>
                                <span class="settings-collapsible-chevron shrink-0 text-muted-foreground" aria-hidden="true">
                                    <i class="ki-filled ki-down settings-collapsible-icon-down text-base" aria-hidden="true"></i>
                                    <i class="ki-filled ki-up settings-collapsible-icon-up text-base" aria-hidden="true"></i>
                                </span>
                            </button>
                            @include('admin.settings.partials.info-hover-icon', [
                                'id' => 'tenant-sync-scope-info',
                                'content' => $tenantSyncScopeInfo,
                            ])
                        </div>
                    </div>
                    <div class="settings-collapsible-body px-3 pb-3 text-xs text-secondary-foreground">
                    <p class="mb-1"><span class="text-foreground font-medium">Altijd mee:</span> {{ $tenantSyncScope['company_row'] ?? 'companies' }}</p>
                    <p class="mb-1"><span class="text-foreground font-medium">Met <code class="font-mono">company_id</code> ({{ $tenantSyncTableCount }} tabellen):</span></p>
                    <div class="max-h-40 overflow-y-auto rounded border border-border/80 bg-background px-2 py-1.5 font-mono text-[11px] leading-relaxed text-foreground">
                        @php $syncTables = $tenantSyncScope['tables_with_company_id'] ?? []; @endphp
                        @forelse ($syncTables as $t)
                            <span class="inline-block me-2 mb-0.5">{{ $t }}</span>
                        @empty
                            <span class="text-destructive">Geen tabellen gevonden (controleer database).</span>
                        @endforelse
                    </div>
                    @php $prerequisiteSyncTables = $tenantSyncScope['prerequisite_tables'] ?? []; @endphp
                    @if ($prerequisiteSyncTables !== [])
                        <p class="mt-2 mb-1"><span class="text-foreground font-medium">Globale vereisten vóór tenant-data</span> (FK-parents zoals <code class="font-mono">modules</code>):</p>
                        <p class="font-mono text-[11px] text-foreground break-all">{{ implode(', ', $prerequisiteSyncTables) }}</p>
                    @endif
                    @php $taxiModuleSyncTables = $tenantSyncScope['taxi_module_tables'] ?? []; @endphp
                    @if ($taxiModuleSyncTables !== [])
                        <p class="mt-2 mb-1"><span class="text-foreground font-medium">Nexa Taxi</span> (schema <code class="font-mono">nexa_taxi</code>, alleen als module aan tenant gekoppeld is):</p>
                        <p class="font-mono text-[11px] text-foreground break-all">{{ implode(', ', $taxiModuleSyncTables) }}</p>
                    @endif
                    @php $paymentSyncTables = $tenantSyncScope['payment_company_scoped_tables'] ?? []; @endphp
                    @if ($paymentSyncTables !== [])
                        <p class="mt-2 mb-1"><span class="text-foreground font-medium">Betaling &amp; facturatie</span> (altijd mee bij sync als tabel bestaat):</p>
                        <p class="font-mono text-[11px] text-foreground break-all">{{ implode(', ', $paymentSyncTables) }}</p>
                    @endif
                    @if (! empty($tenantSyncScope['company_domains_note'] ?? ''))
                        <p class="mt-2 mb-1"><span class="text-foreground font-medium">Tenant-domeinen</span></p>
                        <p class="text-[11px] text-muted-foreground mt-0 mb-0">{{ $tenantSyncScope['company_domains_note'] }}</p>
                    @endif
                    @php $aiChatSyncTables = $tenantSyncScope['ai_chat_tables'] ?? []; @endphp
                    @if ($aiChatSyncTables !== [] || ! empty($tenantSyncScope['ai_chat_settings_note'] ?? ''))
                        <p class="mt-2 mb-1"><span class="text-foreground font-medium">AI-chatbot</span> (berichten + tenant-instellingen):</p>
                        @if ($aiChatSyncTables !== [])
                            <p class="font-mono text-[11px] text-foreground break-all">{{ implode(', ', $aiChatSyncTables) }}</p>
                        @endif
                        @if (! empty($tenantSyncScope['ai_chat_settings_note'] ?? ''))
                            <p class="text-[11px] text-muted-foreground mt-1 mb-0">{{ $tenantSyncScope['ai_chat_settings_note'] }}</p>
                        @endif
                    @endif
                    <p class="mt-2 mb-0"><span class="text-foreground font-medium">Expliciet uitgesloten</span> (config <code class="font-mono">tenant_sync.excluded_tables</code>):</p>
                    <p class="mt-0.5 font-mono text-[11px] text-muted-foreground break-all">{{ implode(', ', $tenantSyncScope['excluded_tables'] ?? []) }}</p>
                    </div>
                </div>

                @php
                    $tenantSyncTargets = $tenantSyncTargets ?? collect();
                    $tenantSyncActiveTargetId = (int) ($tenantSyncActiveTarget->id ?? 0);
                    $tenantSyncHasTargets = $tenantSyncTargets->isNotEmpty();
                @endphp
                <div class="rounded-md border border-border bg-muted/20 px-4 py-4 space-y-3" id="tenant-sync-target-picker">
                    @include('admin.settings.partials.heading-with-info', [
                        'tag' => 'h4',
                        'class' => 'text-sm font-medium text-foreground mb-0',
                        'title' => 'Doel-omgevingen',
                        'infoId' => 'tenant-sync-target-picker-info',
                        'info' => 'Beheer meerdere doel-omgevingen (bijv. <strong>Productie</strong>, <strong>Acceptatie</strong>). Kies hieronder de <strong>actieve omgeving</strong>: dat is de omgeving waar de test-verbinding en de volledige tenant-sync naartoe schrijven. De velden eronder tonen en bewerken de gekozen omgeving.',
                    ])
                    <div class="flex flex-wrap items-end gap-3">
                        <div class="grow min-w-[240px]">
                            <label for="tenant-sync-target-select" class="text-sm text-secondary-foreground block mb-1">Actieve omgeving (sync-doel)</label>
                            <form method="POST" action="{{ route('admin.settings.tenant-sync.target.activate') }}" id="tenant-sync-activate-form" class="m-0">
                                @csrf
                                <select name="tenant_sync_target_id" id="tenant-sync-target-select" class="kt-select w-full" @disabled(! $tenantSyncHasTargets)>
                                    @forelse ($tenantSyncTargets as $t)
                                        <option value="{{ $t->id }}" @selected($t->id === $tenantSyncActiveTargetId)>
                                            {{ $t->name }}{{ $t->ssh_enabled ? ' · SSH' : '' }}{{ $t->push_enabled ? ' · push aan' : '' }}
                                        </option>
                                    @empty
                                        <option value="">— Nog geen omgeving toegevoegd —</option>
                                    @endforelse
                                </select>
                            </form>
                        </div>
                        <form method="POST" action="{{ route('admin.settings.tenant-sync.target.create') }}" class="m-0">
                            @csrf
                            <button type="submit" class="kt-btn kt-btn-outline">
                                <i class="ki-filled ki-plus me-2"></i> Nieuwe omgeving
                            </button>
                        </form>
                        @if ($tenantSyncHasTargets)
                            <form method="POST" action="{{ route('admin.settings.tenant-sync.target.delete') }}" class="m-0"
                                  id="tenant-sync-delete-form"
                                  onsubmit="return confirm('Weet je zeker dat je deze omgeving wilt verwijderen?');">
                                @csrf
                                <input type="hidden" name="tenant_sync_target_id" value="{{ $tenantSyncActiveTargetId }}">
                                <button type="submit" class="kt-btn kt-btn-outline text-destructive">
                                    <i class="ki-filled ki-trash me-2"></i> Verwijder
                                </button>
                            </form>
                        @endif
                    </div>
                    @unless ($tenantSyncHasTargets)
                        <p class="text-xs text-secondary-foreground m-0">Vul hieronder de gegevens in en klik op <strong>Opslaan</strong> om je eerste doel-omgeving aan te maken.</p>
                    @endunless
                </div>

                <form method="POST" action="{{ route('admin.settings.tenant-sync.update') }}" id="tenant-sync-settings-form" class="space-y-4" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="tenant_sync_target_id" value="{{ old('tenant_sync_target_id', $tenantSyncSettings['tenant_sync_target_id'] ?? 0) }}">
                    <div>
                        <label for="tenant_sync_name" class="text-sm text-secondary-foreground block mb-1">Naam van de omgeving <span class="text-destructive">*</span></label>
                        <input type="text" name="tenant_sync_name" id="tenant_sync_name" class="kt-input w-full text-sm"
                               value="{{ old('tenant_sync_name', $tenantSyncSettings['tenant_sync_name'] ?? '') }}"
                               placeholder="bijv. Productie" maxlength="120" autocomplete="off">
                        @error('tenant_sync_name')
                            <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    @if ($errors->any())
                        <div class="rounded-md border border-destructive/60 bg-destructive/10 px-3 py-2 text-sm text-destructive" role="alert">
                            <p class="font-medium mb-1">Opslaan mislukt — controleer de velden:</p>
                            <ul class="list-disc ps-5 space-y-0.5">
                                @foreach ($errors->all() as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @php
                        $tenantSyncUrlPrefill = trim((string) ($tenantSyncTargetDatabaseUrlPrefill ?? ''));
                        $tenantSyncSshEnabled = old('tenant_sync_ssh_enabled', ($tenantSyncSettings['tenant_sync_ssh_enabled'] ?? false) ? '1' : '0') === '1';
                    @endphp
                    <div id="tenant-sync-direct-fields" class="space-y-3 {{ $tenantSyncSshEnabled ? 'hidden' : '' }}">
                        @include('admin.settings.partials.label-with-info', [
                            'for' => 'tenant_sync_target_database_url',
                            'label' => 'Database-URL (doel)',
                            'infoId' => 'tenant-sync-direct-info',
                            'info' => 'Directe verbinding zonder SSH. Vul de volledige database-URL en het wachtwoord apart in.',
                        ])
                        <div class="relative">
                            <input type="text" name="tenant_sync_target_database_url" id="tenant_sync_target_database_url"
                                   class="kt-input w-full font-mono text-xs pe-10"
                                   value="{{ old('tenant_sync_target_database_url', $tenantSyncSettings['tenant_sync_target_database_url'] ?? '') }}"
                                   placeholder="pgsql://gebruiker@192.168.2.41:5432/nexa"
                                   autocomplete="off"
                                   data-prefill-url="{{ $tenantSyncUrlPrefill }}"
                                   @disabled($tenantSyncSshEnabled)>
                            <button type="button"
                                    id="tenant-sync-url-prefill-btn"
                                    class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost absolute end-1 top-1/2 z-[2] -translate-y-1/2 {{ $tenantSyncUrlPrefill === '' ? 'opacity-40 pointer-events-none' : '' }}"
                                    aria-label="Vul voorgestelde database-URL in"
                                    title="{{ $tenantSyncUrlPrefill !== '' ? 'Vul in vanuit TENANT_SYNC_TARGET_DATABASE_URL, DB_URL of database-configuratie' : 'Geen voorgestelde URL (.env TENANT_SYNC_TARGET_DATABASE_URL of DB_* leeg)' }}"
                                    @if($tenantSyncUrlPrefill === '') disabled @endif>
                                <i class="ki-filled ki-notepad-edit text-sm"></i>
                            </button>
                        </div>
                        @include('admin.settings.partials.tenant-sync-password-field', [
                            'name' => 'tenant_sync_target_database_password',
                            'id' => 'tenant_sync_target_database_password',
                            'clearFlagName' => 'tenant_sync_target_database_password_clear',
                            'label' => 'Database-wachtwoord',
                            'inputClass' => 'kt-input w-full font-mono text-xs',
                            'hasStored' => $tenantSyncSettings['tenant_sync_has_database_password'] ?? false,
                            'placeholder' => ($tenantSyncSettings['tenant_sync_has_database_password'] ?? false) ? '•••••••• (opgeslagen — laat leeg om te behouden)' : 'Wachtwoord van de database-gebruiker',
                            'hint' => 'Speciale tekens worden automatisch URL-encoded (bijv. <code class="text-xs">Welkom01!</code> → <code class="text-xs">Welkom01%21</code>).',
                        ])
                    </div>

                    <div class="rounded-md border border-border p-4 space-y-3" id="tenant-sync-ssh-panel">
                        <div class="flex items-center justify-between gap-2 min-w-0">
                            <label class="inline-flex items-center gap-2 mb-0 min-w-0">
                                <input type="hidden" name="tenant_sync_ssh_enabled" value="0">
                                <input type="checkbox" name="tenant_sync_ssh_enabled" value="1" id="tenant_sync_ssh_enabled" class="kt-checkbox"
                                       @checked($tenantSyncSshEnabled)>
                                <span class="text-sm font-medium text-foreground">Via SSH-tunnel verbinden</span>
                            </label>
                            @include('admin.settings.partials.info-hover-icon', [
                                'id' => 'tenant-sync-ssh-info',
                                'content' => 'Voor servers waar Postgres alleen op <code>127.0.0.1</code> luistert. De backend opent SSH en bouwt daarna zelf de database-verbinding (velden hieronder).',
                            ])
                        </div>
                        <div id="tenant-sync-ssh-fields" class="space-y-3 {{ $tenantSyncSshEnabled ? '' : 'hidden' }}">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label for="tenant_sync_ssh_host" class="text-sm text-secondary-foreground block mb-1">SSH-host</label>
                                    <input type="text" name="tenant_sync_ssh_host" id="tenant_sync_ssh_host" class="kt-input w-full text-sm"
                                           value="{{ old('tenant_sync_ssh_host', $tenantSyncSettings['tenant_sync_ssh_host'] ?? '') }}"
                                           placeholder="192.168.2.41" autocomplete="off">
                                </div>
                                <div>
                                    <label for="tenant_sync_ssh_port" class="text-sm text-secondary-foreground block mb-1">SSH-poort</label>
                                    <input type="number" name="tenant_sync_ssh_port" id="tenant_sync_ssh_port" class="kt-input w-full text-sm" min="1" max="65535"
                                           value="{{ old('tenant_sync_ssh_port', $tenantSyncSettings['tenant_sync_ssh_port'] ?? '22') }}">
                                </div>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label for="tenant_sync_ssh_username" class="text-sm text-secondary-foreground block mb-1">SSH-gebruiker</label>
                                    <input type="text" name="tenant_sync_ssh_username" id="tenant_sync_ssh_username" class="kt-input w-full text-sm"
                                           value="{{ old('tenant_sync_ssh_username', $tenantSyncSettings['tenant_sync_ssh_username'] ?? '') }}"
                                           placeholder="ubuntu" autocomplete="username">
                                </div>
                                @include('admin.settings.partials.tenant-sync-password-field', [
                                    'name' => 'tenant_sync_ssh_password',
                                    'id' => 'tenant_sync_ssh_password',
                                    'clearFlagName' => 'tenant_sync_ssh_password_clear',
                                    'label' => 'SSH-wachtwoord',
                                    'hasStored' => $tenantSyncSettings['tenant_sync_has_ssh_password'] ?? false,
                                    'placeholder' => ($tenantSyncSettings['tenant_sync_has_ssh_password'] ?? false) ? '•••••••• (opgeslagen — laat leeg om te behouden)' : 'SSH-wachtwoord',
                                    'hint' => 'Speciale tekens worden automatisch URL-encoded (bijv. <code class="text-xs">Welkom01!</code> → <code class="text-xs">Welkom01%21</code>).',
                                ])
                            </div>

                            <div id="tenant-sync-ssh-db-fields" class="rounded-md border border-border/80 bg-muted/30 p-3 space-y-3">
                                @include('admin.settings.partials.heading-with-info', [
                                    'title' => 'Postgres op de server (via tunnel)',
                                    'infoId' => 'tenant-sync-ssh-db-info',
                                    'info' => 'De database-URL hierboven wordt bij SSH niet gebruikt; deze gegevens bepalen de verbinding via de tunnel. Postgres-host op server: bij Docker-deploy meestal <code>127.0.0.1</code> poort <code>5432</code> (niet de containernaam <code>db</code>).',
                                ])
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label for="tenant_sync_ssh_db_username" class="text-sm text-secondary-foreground block mb-1">Database-gebruiker</label>
                                        <input type="text" name="tenant_sync_ssh_db_username" id="tenant_sync_ssh_db_username"
                                               class="kt-input w-full font-mono text-xs"
                                               value="{{ old('tenant_sync_ssh_db_username', $tenantSyncSettings['tenant_sync_ssh_db_username'] ?? '') }}"
                                               placeholder="nexa" autocomplete="off">
                                    </div>
                                    <div>
                                        <label for="tenant_sync_ssh_db_database" class="text-sm text-secondary-foreground block mb-1">Database-naam</label>
                                        <input type="text" name="tenant_sync_ssh_db_database" id="tenant_sync_ssh_db_database"
                                               class="kt-input w-full font-mono text-xs"
                                               value="{{ old('tenant_sync_ssh_db_database', $tenantSyncSettings['tenant_sync_ssh_db_database'] ?? '') }}"
                                               placeholder="nexa" autocomplete="off">
                                    </div>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        @include('admin.settings.partials.tenant-sync-password-field', [
                                            'name' => 'tenant_sync_ssh_db_password',
                                            'id' => 'tenant_sync_ssh_db_password',
                                            'clearFlagName' => 'tenant_sync_ssh_db_password_clear',
                                            'label' => 'Database-wachtwoord',
                                            'inputClass' => 'kt-input w-full font-mono text-xs',
                                            'hasStored' => $tenantSyncSettings['tenant_sync_has_ssh_db_password'] ?? false,
                                            'placeholder' => ($tenantSyncSettings['tenant_sync_has_ssh_db_password'] ?? false) ? '•••••••• (opgeslagen — laat leeg om te behouden)' : 'Postgres-wachtwoord op de server',
                                            'hint' => 'Speciale tekens worden automatisch URL-encoded (bijv. <code class="text-xs">Welkom01!</code> → <code class="text-xs">Welkom01%21</code>).',
                                        ])
                                    </div>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label for="tenant_sync_ssh_remote_db_host" class="text-sm text-secondary-foreground block mb-1">Postgres-host op server</label>
                                        <input type="text" name="tenant_sync_ssh_remote_db_host" id="tenant_sync_ssh_remote_db_host" class="kt-input w-full font-mono text-xs"
                                               value="{{ old('tenant_sync_ssh_remote_db_host', $tenantSyncSettings['tenant_sync_ssh_remote_db_host'] ?? '127.0.0.1') }}"
                                               placeholder="127.0.0.1">
                                    </div>
                                    <div>
                                        <label for="tenant_sync_ssh_remote_db_port" class="text-sm text-secondary-foreground block mb-1">Postgres-poort op server</label>
                                        <input type="number" name="tenant_sync_ssh_remote_db_port" id="tenant_sync_ssh_remote_db_port" class="kt-input w-full text-sm" min="1" max="65535"
                                               value="{{ old('tenant_sync_ssh_remote_db_port', $tenantSyncSettings['tenant_sync_ssh_remote_db_port'] ?? '5432') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <label class="inline-flex items-center gap-2">
                        <input type="hidden" name="tenant_sync_push_enabled" value="0">
                        <input type="checkbox" name="tenant_sync_push_enabled" value="1" class="kt-checkbox"
                               @if(old('tenant_sync_push_enabled', ($tenantSyncSettings['tenant_sync_push_enabled'] ?? false) ? '1' : '0') === '1') checked @endif>
                        <span class="text-sm text-secondary-foreground">Push/sync naar doel-database toestaan</span>
                    </label>
                    <div class="flex flex-wrap gap-2 items-center">
                        <button type="submit" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-check me-2"></i> Opslaan
                        </button>
                        <button type="button" id="tenant-sync-test-btn" class="kt-btn kt-btn-outline">
                            <i class="ki-filled ki-verify me-2"></i> Test verbinding
                        </button>
                    </div>
                    <div id="tenant-sync-test-result" class="hidden rounded-md border px-3 py-2 text-sm" role="status" aria-live="polite"></div>
                </form>

                <div class="border-t border-border pt-6">
                    @include('admin.settings.partials.heading-with-info', [
                        'tag' => 'h4',
                        'class' => 'text-sm font-medium text-foreground mb-2',
                        'title' => 'Sync uitvoeren',
                        'infoId' => 'tenant-sync-run-info',
                        'info' => 'Kies het bedrijf (tenant) of <strong>NEXA SaaS</strong> (centrale website) op <strong>deze</strong> omgeving. Push moet aan staan en productie-push mag alleen als je dat in .env expliciet toestaat. Tenant: volledige tenant-data (alleen toevoegen). NEXA SaaS: centrale <code>website_pages</code> en <code>nexa_pricing</code> (bestaande rijen op doel worden bijgewerkt).',
                    ])
                    <p class="text-sm text-secondary-foreground mb-4">
                        Synchroniseert naar:
                        @if (! empty($tenantSyncActiveTarget))
                            <span class="kt-badge kt-badge-sm kt-badge-primary align-middle">{{ $tenantSyncActiveTarget->name }}</span>
                        @else
                            <span class="text-destructive">geen omgeving gekozen — voeg er eerst één toe.</span>
                        @endif
                    </p>
                    <form id="tenant-sync-run-form" method="POST" action="{{ route('admin.settings.tenant-sync.run') }}" class="space-y-4" novalidate>
                        @csrf
                        <div>
                            <label for="source_company_id" class="text-sm text-secondary-foreground block mb-1">Bron <span class="text-destructive">*</span></label>
                            <select name="source_company_id" id="source_company_id" class="kt-select tenant-sync-company-select @error('source_company_id') border-destructive @enderror">
                                <option value="" disabled @selected(old('source_company_id') === null || old('source_company_id') === '')>— Kies een bron —</option>
                                <option value="nexa" @selected((string) old('source_company_id') === 'nexa')>NEXA SaaS (centrale website)</option>
                                @foreach ($companiesForSync ?? [] as $c)
                                    <option value="{{ $c->id }}" @selected((string) old('source_company_id') === (string) $c->id)>{{ $c->name }} (id {{ $c->id }})</option>
                                @endforeach
                            </select>
                            @error('source_company_id')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                            <div id="tenant-sync-ajax-error-source_company_id" class="text-xs text-destructive mt-1 hidden" role="alert"></div>
                            @if (($companiesForSync ?? collect())->isEmpty())
                                <div class="text-xs text-muted-foreground mt-1">Geen tenant-bedrijven gevonden. Je kunt wél de NEXA SaaS-website synchroniseren.</div>
                            @endif
                        </div>
                        <label class="inline-flex items-start gap-2">
                            <input type="checkbox" name="confirm_full_sync" value="1" id="confirm_full_sync" class="kt-checkbox mt-0.5 @error('confirm_full_sync') border-destructive @enderror"
                                   @checked(old('confirm_full_sync') === '1')>
                            <span class="text-sm text-secondary-foreground">Ik bevestig dat ik naar de geconfigureerde doel-database wil schrijven. Tenant: alleen toevoegen. NEXA SaaS: centrale pagina’s en prijzen worden bijgewerkt.</span>
                        </label>
                        @error('confirm_full_sync')
                            <div class="text-xs text-destructive">{{ $message }}</div>
                        @enderror
                        <div id="tenant-sync-ajax-error-confirm_full_sync" class="text-xs text-destructive mt-1 hidden" role="alert"></div>
                        <div class="flex flex-col gap-3 min-w-0 w-full">
                            <div class="flex flex-wrap items-start gap-3 min-w-0 w-full">
                                <button type="submit" id="tenant-sync-submit-btn" class="kt-btn kt-btn-primary shrink-0"
                                        style="padding-top: 2px;">
                                    <i class="ki-filled ki-cloud-add me-2"></i> Start sync
                                </button>
                            </div>
                            <span id="tenant-sync-submit-status" class="block min-w-0 max-w-full w-full text-xs min-h-[2.125rem] break-words" aria-live="polite"></span>
                        </div>
                    </form>
                </div>

                <div class="border-t border-border pt-6 mt-2">
                    @include('admin.settings.partials.heading-with-info', [
                        'tag' => 'h4',
                        'class' => 'text-sm font-medium text-foreground mb-4',
                        'title' => 'ZIP-export / -import',
                        'infoId' => 'tenant-sync-zip-info',
                        'info' => 'Kies een <strong>tenant</strong> of <strong>NEXA SaaS</strong>. Tenant: bestanden, website_pages en tenant-instellingen (<code>tenant-export-</code>, bundle <code>tenant_media</code>). NEXA SaaS: centrale website-pagina’s (company_id null), media en <code>nexa_pricing</code> (<code>nexa-saas-website-</code>, bundle <code>nexa_saas_website</code>).',
                    ])
                    <div>
                        <label for="tenant-sync-zip-company-id" class="text-sm text-secondary-foreground block mb-1">Bron (tenant of NEXA SaaS)</label>
                        <select id="tenant-sync-zip-company-id" class="kt-select tenant-sync-company-select" aria-describedby="tenant-sync-zip-company-error" aria-invalid="false">
                            <option value="">— Kies een bron —</option>
                            <option value="nexa">NEXA SaaS (centrale website)</option>
                            @foreach (($companiesForSync ?? []) as $c)
                                <option value="{{ $c->id }}">{{ $c->name }} (id {{ $c->id }})</option>
                            @endforeach
                        </select>
                        <p id="tenant-sync-zip-company-error" class="mt-1.5 hidden text-sm text-destructive" role="alert"></p>
                    </div>

                    <div class="max-w-2xl mt-6 space-y-6">
                        <div class="rounded-md border border-border bg-muted/30 px-3 py-3 space-y-3">
                            @include('admin.settings.partials.heading-with-info', [
                                'title' => 'Export (ZIP)',
                                'infoId' => 'tenant-sync-zip-export-info',
                                'info' => 'Download of importeer één ZIP met <code>manifest.json</code>. Tenant: publieke bestanden onder <code>files/…</code>, private facturen onder <code>private_files/…</code>. NEXA SaaS: alleen centrale website-pagina’s, gerefereerde media en prijzen. Import van een tenant-ZIP overschrijft <code>website_pages</code> per slug voor het gekozen bedrijf. Import van een NEXA SaaS-ZIP overschrijft centrale pagina’s (company_id null).',
                            ])
                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" id="tenant-files-export-btn" class="kt-btn kt-btn-outline">
                                    <i class="ki-filled ki-file-down me-2"></i> Download ZIP
                                </button>
                            </div>
                            <form method="POST" action="{{ route('admin.settings.tenant-storage-bundle.import') }}" enctype="multipart/form-data" class="space-y-3" id="tenant-files-import-form">
                                @csrf
                                <input type="hidden" name="company_id" id="tenant-files-import-company-id" value="">
                                <div>
                                    @include('admin.settings.partials.label-with-info', [
                                        'for' => 'tenant-files-bundle-input',
                                        'label' => 'ZIP importeren',
                                        'infoId' => 'tenant-sync-zip-upload-info',
                                        'info' => 'Max. ' . (int) floor((int) config('upload.tenant_bundle_max_kb', 512000) / 1024) . ' MB per upload. Bij <strong>413 Request Entity Too Large</strong>: zet op de server in nginx <code>client_max_body_size 512M;</code> (zie <code>deploy/nginx-nexa.conf</code>) en herbouw de backend-container na deploy.',
                                    ])
                                    <input type="file" name="bundle" id="tenant-files-bundle-input" accept=".zip,application/zip" class="kt-input w-full text-sm py-1.5">
                                </div>
                                <button type="submit" class="kt-btn kt-btn-primary" id="tenant-files-import-submit">
                                    <i class="ki-filled ki-file-up me-2"></i> Importeer ZIP
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss success alert after 5 seconds
    const successAlert = document.getElementById('success-alert');
    if (successAlert) {
        setTimeout(function() {
            successAlert.style.transition = 'opacity 0.3s ease-out';
            successAlert.style.opacity = '0';
            setTimeout(function() {
                successAlert.remove();
            }, 300);
        }, 5000);
    }

    const successToast = document.getElementById('settings-success-toast');
    if (successToast) {
        if (successToast.parentElement !== document.body) {
            document.body.appendChild(successToast);
        }
        requestAnimationFrame(function() {
            successToast.classList.remove('opacity-0', 'translate-y-2', 'pointer-events-none');
            successToast.style.pointerEvents = 'auto';
        });

        setTimeout(function() {
            successToast.classList.add('opacity-0', 'translate-y-2', 'pointer-events-none');
            setTimeout(function() {
                successToast.remove();
            }, 300);
        }, 4000);
    }

    // Test email functionality
    const testEmailBtn = document.getElementById('test-email-btn');
    const testEmailInput = document.getElementById('test-email-input');
    const testEmailStatus = document.getElementById('test-email-status');

    function setTestEmailStatus(ok, message) {
        if (!testEmailStatus) {
            return;
        }
        const label = ok ? 'Test e-mail succesvol verzonden' : 'Test e-mail mislukt';
        const color = ok ? '#16a34a' : '#dc2626';
        testEmailStatus.classList.remove('hidden');
        testEmailStatus.dataset.ok = ok ? '1' : '0';
        testEmailStatus.style.color = color;
        testEmailStatus.setAttribute('aria-label', label);
        testEmailStatus.setAttribute('title', message || label);
        testEmailStatus.innerHTML = ok
            ? '<svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
            : '<svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
    }

    function clearTestEmailStatus() {
        if (!testEmailStatus) {
            return;
        }
        testEmailStatus.classList.add('hidden');
        testEmailStatus.removeAttribute('title');
        testEmailStatus.removeAttribute('aria-label');
        testEmailStatus.removeAttribute('data-ok');
        testEmailStatus.style.color = '';
        testEmailStatus.innerHTML = '';
    }
    
    if (testEmailBtn && testEmailInput) {
        testEmailBtn.addEventListener('click', function() {
            const email = testEmailInput.value.trim();
            
            if (!email) {
                setTestEmailStatus(false, 'Vul een e-mailadres in om te testen.');
                return;
            }
            
            if (!email.includes('@')) {
                setTestEmailStatus(false, 'Vul een geldig e-mailadres in.');
                return;
            }

            clearTestEmailStatus();
            testEmailBtn.disabled = true;
            testEmailBtn.classList.add('is-loading');
            testEmailBtn.setAttribute('aria-busy', 'true');
            var testEmailBtnLabel = testEmailBtn.querySelector('.test-email-btn-label');
            if (testEmailBtnLabel) {
                testEmailBtnLabel.textContent = 'Verzenden...';
            }
            
            fetch('{{ route("admin.settings.mail.test") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    test_email: email
                })
            })
            .then(function(response) {
                return response.json().then(function(data) {
                    return { ok: response.ok, data: data };
                });
            })
            .then(function(result) {
                const data = result.data || {};
                const success = result.ok && data.success === true;
                setTestEmailStatus(success, data.message || (success
                    ? 'Test e-mail succesvol verzonden.'
                    : 'Test e-mail kon niet worden verzonden.'));
            })
            .catch(function(error) {
                console.error('Error:', error);
                setTestEmailStatus(false, 'Er is een fout opgetreden bij het testen van de email.');
            })
            .finally(function() {
                testEmailBtn.disabled = false;
                testEmailBtn.classList.remove('is-loading');
                testEmailBtn.removeAttribute('aria-busy');
                var testEmailBtnLabel = testEmailBtn.querySelector('.test-email-btn-label');
                if (testEmailBtnLabel) {
                    testEmailBtnLabel.textContent = 'Verstuur Test';
                }
            });
        });
    }

    const tenantSyncTestBtn = document.getElementById('tenant-sync-test-btn');
    const tenantSyncSettingsForm = document.getElementById('tenant-sync-settings-form');
    const tenantSyncUrlInput = document.getElementById('tenant_sync_target_database_url');
    const tenantSyncUrlPrefillBtn = document.getElementById('tenant-sync-url-prefill-btn');
    const tenantSyncTestResult = document.getElementById('tenant-sync-test-result');
    const tenantSyncSshEnabled = document.getElementById('tenant_sync_ssh_enabled');
    const tenantSyncSshFields = document.getElementById('tenant-sync-ssh-fields');
    const tenantSyncDirectFields = document.getElementById('tenant-sync-direct-fields');
    function syncTenantSyncConnectionMode() {
        var sshOn = tenantSyncSshEnabled && tenantSyncSshEnabled.checked;
        if (tenantSyncSshFields) {
            tenantSyncSshFields.classList.toggle('hidden', !sshOn);
        }
        if (tenantSyncDirectFields) {
            tenantSyncDirectFields.classList.toggle('hidden', !!sshOn);
            tenantSyncDirectFields.querySelectorAll('input, button, select, textarea').forEach(function(el) {
                if (el.id === 'tenant-sync-url-prefill-btn') {
                    return;
                }
                el.disabled = !!sshOn;
            });
        }
        if (tenantSyncUrlInput) {
            tenantSyncUrlInput.disabled = !!sshOn;
        }
        if (tenantSyncUrlPrefillBtn) {
            tenantSyncUrlPrefillBtn.disabled = !!sshOn || tenantSyncUrlPrefillBtn.classList.contains('opacity-40');
        }
    }
    if (tenantSyncSshEnabled) {
        tenantSyncSshEnabled.addEventListener('change', syncTenantSyncConnectionMode);
        syncTenantSyncConnectionMode();
    }
    var tenantSyncTargetSelect = document.getElementById('tenant-sync-target-select');
    var tenantSyncActivateForm = document.getElementById('tenant-sync-activate-form');
    if (tenantSyncTargetSelect && tenantSyncActivateForm) {
        tenantSyncTargetSelect.addEventListener('change', function() {
            tenantSyncActivateForm.submit();
        });
    }
    function updateTenantSyncPasswordClearVisibility(wrap) {
        if (!wrap) {
            return;
        }
        var input = wrap.querySelector('.tenant-sync-password-input');
        var btn = wrap.querySelector('.tenant-sync-password-clear');
        if (!input || !btn) {
            return;
        }
        var hasStored = wrap.getAttribute('data-has-stored') === '1';
        var hasValue = String(input.value || '').trim() !== '';
        var cleared = wrap.getAttribute('data-cleared') === '1';
        btn.classList.toggle('hidden', !(hasValue || (hasStored && !cleared)));
    }
    function bindTenantSyncPasswordClear(wrap) {
        if (!wrap) {
            return;
        }
        var input = wrap.querySelector('.tenant-sync-password-input');
        var btn = wrap.querySelector('.tenant-sync-password-clear');
        var clearFlag = btn ? document.getElementById(btn.getAttribute('data-clear-flag')) : null;
        if (!input || !btn || !clearFlag) {
            return;
        }
        function refresh() {
            updateTenantSyncPasswordClearVisibility(wrap);
        }
        input.addEventListener('input', refresh);
        btn.addEventListener('click', function() {
            input.value = '';
            clearFlag.value = '1';
            wrap.setAttribute('data-has-stored', '0');
            wrap.setAttribute('data-cleared', '1');
            refresh();
            input.focus();
        });
        refresh();
    }
    document.querySelectorAll('.tenant-sync-password-field').forEach(bindTenantSyncPasswordClear);
    function encodeTenantSyncPasswordValue(value) {
        if (!value || !String(value).trim()) {
            return value;
        }
        var decoded = String(value);
        try {
            if (/%[0-9A-Fa-f]{2}/.test(decoded)) {
                decoded = decodeURIComponent(decoded);
            }
        } catch (e) {
            decoded = String(value);
        }
        if (!/[^A-Za-z0-9\-._~]/.test(decoded)) {
            return decoded;
        }
        return encodeURIComponent(decoded);
    }
    function applyTenantSyncPasswordEncoding(inputEl) {
        if (!inputEl || !inputEl.value || !String(inputEl.value).trim()) {
            return;
        }
        var encoded = encodeTenantSyncPasswordValue(inputEl.value);
        if (encoded !== inputEl.value) {
            inputEl.value = encoded;
        }
    }
    function bindTenantSyncPasswordAutoEncode(inputId) {
        var el = document.getElementById(inputId);
        if (!el) {
            return;
        }
        el.addEventListener('blur', function() {
            applyTenantSyncPasswordEncoding(el);
        });
    }
    ['tenant_sync_target_database_password', 'tenant_sync_ssh_password', 'tenant_sync_ssh_db_password'].forEach(bindTenantSyncPasswordAutoEncode);
    if (tenantSyncSettingsForm) {
        tenantSyncSettingsForm.addEventListener('submit', function() {
            document.querySelectorAll('.tenant-sync-password-field[data-cleared="1"]').forEach(function(wrap) {
                var input = wrap.querySelector('.tenant-sync-password-input');
                var flag = wrap.querySelector('.tenant-sync-password-clear');
                if (input) {
                    input.value = '';
                }
                if (flag) {
                    var clearInput = document.getElementById(flag.getAttribute('data-clear-flag'));
                    if (clearInput) {
                        clearInput.value = '1';
                    }
                }
            });
        });
    }
    function appendTenantSyncSettingsToFormData(fd) {
        if (!tenantSyncSettingsForm) {
            return;
        }
        var syncFd = new FormData(tenantSyncSettingsForm);
        syncFd.forEach(function(value, key) {
            if (key === '_token') {
                return;
            }
            fd.append(key, value);
        });
    }
    if (tenantSyncUrlPrefillBtn && tenantSyncUrlInput) {
        tenantSyncUrlPrefillBtn.addEventListener('click', function() {
            const prefill = (tenantSyncUrlInput.getAttribute('data-prefill-url') || '').trim();
            if (!prefill) {
                return;
            }
            tenantSyncUrlInput.value = prefill;
            tenantSyncUrlInput.dispatchEvent(new Event('input', { bubbles: true }));
            tenantSyncUrlInput.focus();
        });
    }
    function showTenantSyncTestMessage(ok, text) {
        if (!tenantSyncTestResult) {
            window.alert((ok ? '✓ ' : '✗ ') + text);
            return;
        }
        tenantSyncTestResult.classList.remove('hidden', 'border-emerald-300', 'bg-emerald-50', 'text-emerald-900', 'border-destructive/60', 'bg-destructive/10', 'text-destructive');
        if (ok) {
            tenantSyncTestResult.classList.add('border-emerald-300', 'bg-emerald-50', 'text-emerald-900');
        } else {
            tenantSyncTestResult.classList.add('border-destructive/60', 'bg-destructive/10', 'text-destructive');
        }
        tenantSyncTestResult.textContent = (ok ? '✓ ' : '✗ ') + text;
    }
    if (tenantSyncTestBtn && tenantSyncUrlInput) {
        tenantSyncTestBtn.addEventListener('click', function() {
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const token = csrfMeta ? csrfMeta.getAttribute('content') : '';
            if (!token) {
                showTenantSyncTestMessage(false, 'CSRF-token ontbreekt in de pagina; herlaad de pagina.');
                return;
            }
            const url = tenantSyncUrlInput.value.trim();
            if (!url) {
                showTenantSyncTestMessage(false, 'Vul eerst een database-URL in (of sla op en test met de opgeslagen URL).');
                return;
            }
            const fd = new FormData();
            fd.append('_token', token);
            appendTenantSyncSettingsToFormData(fd);
            tenantSyncTestBtn.disabled = true;
            const label = tenantSyncTestBtn.innerHTML;
            tenantSyncTestBtn.innerHTML = '<i class="ki-filled ki-arrows-circle me-2"></i> Bezig…';
            fetch('{{ route("admin.settings.tenant-sync.test") }}', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token,
                },
            })
                .then(function(r) {
                    return r.text().then(function(text) {
                        var data = null;
                        try {
                            data = text ? JSON.parse(text) : null;
                        } catch (e) {
                            throw new Error('Antwoord is geen JSON (HTTP ' + r.status + '). Controleer of je ingelogd bent en de route bereikbaar is.');
                        }
                        return { status: r.status, data: data };
                    });
                })
                .then(function(res) {
                    var d = res.data || {};
                    if (d.success) {
                        showTenantSyncTestMessage(true, d.message || 'Verbinding OK.');
                    } else {
                        showTenantSyncTestMessage(false, d.message || ('HTTP ' + res.status));
                    }
                })
                .catch(function(err) {
                    showTenantSyncTestMessage(false, err && err.message ? err.message : 'Netwerkfout of ongeldig antwoord.');
                })
                .finally(function() {
                    tenantSyncTestBtn.disabled = false;
                    tenantSyncTestBtn.innerHTML = label;
                });
        });
    }

    var tenantSyncRunForm = document.getElementById('tenant-sync-run-form');
    var tenantSyncSubmitBtn = document.getElementById('tenant-sync-submit-btn');
    var tenantSyncSubmitStatus = document.getElementById('tenant-sync-submit-status');
    var tenantSyncSubmitDefaultHtml = tenantSyncSubmitBtn ? tenantSyncSubmitBtn.innerHTML.trim() : '';

    function clearTenantSyncAjaxUi() {
        ['source_company_id', 'confirm_full_sync'].forEach(function(field) {
            var el = document.getElementById('tenant-sync-ajax-error-' + field);
            if (el) {
                el.textContent = '';
                el.classList.add('hidden');
            }
        });
        var sel = document.getElementById('source_company_id');
        if (sel) sel.classList.remove('border-destructive');
        var cb = document.getElementById('confirm_full_sync');
        if (cb) cb.classList.remove('border-destructive');
    }

    function applyTenantSyncValidationErrors(errors) {
        if (!errors || typeof errors !== 'object') return;
        Object.keys(errors).forEach(function(field) {
            var msgs = errors[field];
            if (!msgs || !msgs.length) return;
            var el = document.getElementById('tenant-sync-ajax-error-' + field);
            if (el) {
                el.textContent = msgs[0];
                el.classList.remove('hidden');
            }
            if (field === 'source_company_id') {
                var sel = document.getElementById('source_company_id');
                if (sel) sel.classList.add('border-destructive');
            }
            if (field === 'confirm_full_sync') {
                var cb = document.getElementById('confirm_full_sync');
                if (cb) cb.classList.add('border-destructive');
            }
        });
    }

    function tenantSyncRowIsNotable(row) {
        if (!row) return false;
        var inserted = Number(row.inserted || 0);
        var updated = Number(row.updated || 0);
        var skipped = Number(row.skipped || 0);
        var status = row.status || 'ok';
        return (inserted + updated + skipped) > 0
            || status === 'error' || status === 'warning' || status === 'skipped'
            || !!row.error;
    }

    function renderTenantSyncReport(container, report) {
        if (!container || !report) return;

        container.textContent = '';
        container.className = 'block w-full text-xs min-h-[2.125rem]';

        var wrap = document.createElement('div');
        wrap.className = 'tenant-sync-report rounded-md border border-border bg-muted/20 p-4 text-xs leading-snug text-left w-full max-w-4xl';

        var summary = document.createElement('p');
        summary.className = 'font-medium text-foreground mb-3';
        summary.textContent = report.summary || 'Tenant-sync voltooid.';
        wrap.appendChild(summary);

        if (report.errors && report.errors.length) {
            var errTitle = document.createElement('p');
            errTitle.className = 'text-xs font-semibold uppercase tracking-wide text-destructive mb-2';
            errTitle.textContent = 'Fouten';
            wrap.appendChild(errTitle);

            var errList = document.createElement('ul');
            errList.className = 'space-y-1.5 mb-4';
            report.errors.forEach(function(error) {
                var li = document.createElement('li');
                li.className = 'text-destructive';
                li.textContent = (error.section || 'Algemeen') + ': ' + (error.message || '');
                errList.appendChild(li);
            });
            wrap.appendChild(errList);
        }

        (report.sections || []).forEach(function(section) {
            var rows = (section.rows || []).filter(tenantSyncRowIsNotable);
            if (!rows.length) return;

            var block = document.createElement('div');
            block.className = 'mb-4 last:mb-0';

            var title = document.createElement('p');
            title.className = 'text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-2';
            title.textContent = section.title || 'Overig';
            block.appendChild(title);

            var list = document.createElement('ul');
            list.className = 'space-y-1.5';

            rows.forEach(function(row) {
                list.appendChild(buildTenantSyncReportRowLi(row));
            });

            block.appendChild(list);
            wrap.appendChild(block);
        });

        if (report.notes && report.notes.length) {
            var notesWrap = document.createElement('div');
            notesWrap.className = 'mt-4 pt-3 border-t border-border/70 space-y-1';

            var notesTitle = document.createElement('p');
            notesTitle.className = 'text-xs font-semibold uppercase tracking-wide text-muted-foreground';
            notesTitle.textContent = 'Opmerkingen';
            notesWrap.appendChild(notesTitle);

            report.notes.forEach(function(note) {
                var p = document.createElement('p');
                p.className = 'text-muted-foreground mb-0';
                p.textContent = note;
                notesWrap.appendChild(p);
            });

            wrap.appendChild(notesWrap);
        }

        var ok = document.createElement('div');
        ok.className = 'flex items-start gap-2 text-emerald-600 dark:text-emerald-400 font-medium mb-3';
        ok.innerHTML = '<i class="ki-filled ki-check-circle text-lg shrink-0 mt-0.5" aria-hidden="true"></i><span>Sync voltooid</span>';

        container.appendChild(ok);
        container.appendChild(wrap);
    }

    function buildTenantSyncReportRowLi(row) {
        var li = document.createElement('li');
        li.className = 'flex flex-col gap-0.5 sm:flex-row sm:flex-wrap sm:items-baseline sm:gap-x-3';

        var label = document.createElement('span');
        label.className = 'font-mono text-foreground min-w-[12rem]';
        label.textContent = row.label || '—';

        var counts = document.createElement('span');
        counts.className = 'text-muted-foreground';
        counts.textContent = '+' + (row.inserted || 0) + ' toegevoegd, ~' + (row.updated || 0) + ' bijgewerkt, -' + (row.skipped || 0) + ' overgeslagen';

        li.appendChild(label);
        li.appendChild(counts);

        if (row.error) {
            var err = document.createElement('span');
            err.className = 'text-destructive sm:basis-full';
            err.textContent = row.error;
            li.appendChild(err);
        }

        return li;
    }

    function initTenantSyncProgressUi(container) {
        if (!container) return null;

        container.textContent = '';
        container.className = 'block min-w-0 max-w-full w-full text-xs min-h-[2.125rem] break-words';

        var shell = document.createElement('div');
        shell.className = 'tenant-sync-progress-shell';

        var meter = document.createElement('div');
        meter.className = 'tenant-sync-progress-meter';
        meter.innerHTML =
            '<div class="tenant-sync-progress-meter-label">' +
                '<span class="tenant-sync-progress-meter-status">Bezig met synchroniseren…</span>' +
                '<span class="tenant-sync-progress-meter-percent">0%</span>' +
            '</div>' +
            '<div class="tenant-sync-progress-meter-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">' +
                '<div class="tenant-sync-progress-meter-fill"></div>' +
            '</div>';

        var wrap = document.createElement('div');
        wrap.className = 'tenant-sync-progress rounded-md border border-border bg-muted/20 p-4 text-left min-w-0 max-w-full';

        var toolbar = document.createElement('div');
        toolbar.className = 'tenant-sync-progress-toolbar';

        var heading = document.createElement('p');
        heading.className = 'tenant-sync-progress-heading font-medium text-foreground mb-0 flex items-start gap-2 min-w-0 flex-1';
        heading.innerHTML = '<i class="ki-filled ki-arrows-circle text-sm animate-spin shrink-0 mt-0.5" aria-hidden="true"></i><span class="min-w-0 break-words">Sync bezig…</span>';

        var toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'tenant-sync-progress-toggle';
        toggle.setAttribute('aria-expanded', 'false');
        toggle.textContent = 'Uitklappen';

        toolbar.appendChild(heading);
        toolbar.appendChild(toggle);

        var body = document.createElement('div');
        body.className = 'tenant-sync-progress-body';

        var list = document.createElement('ul');
        list.className = 'tenant-sync-progress-list space-y-1.5';
        list.setAttribute('aria-live', 'polite');

        body.appendChild(list);
        wrap.appendChild(toolbar);
        wrap.appendChild(body);
        shell.appendChild(meter);
        shell.appendChild(wrap);
        container.appendChild(shell);

        var progressUi = {
            shell: shell,
            wrap: wrap,
            heading: heading,
            list: list,
            body: body,
            toggle: toggle,
            meter: meter,
            meterFill: meter.querySelector('.tenant-sync-progress-meter-fill'),
            meterTrack: meter.querySelector('.tenant-sync-progress-meter-track'),
            meterPercent: meter.querySelector('.tenant-sync-progress-meter-percent'),
            meterStatus: meter.querySelector('.tenant-sync-progress-meter-status'),
            sections: {},
            percent: 0,
            expanded: false,
        };

        toggle.addEventListener('click', function() {
            progressUi.expanded = !progressUi.expanded;
            wrap.classList.toggle('is-expanded', progressUi.expanded);
            toggle.setAttribute('aria-expanded', progressUi.expanded ? 'true' : 'false');
            toggle.textContent = progressUi.expanded ? 'Inklappen' : 'Uitklappen';
            if (progressUi.expanded && body) {
                body.scrollTop = 0;
            }
        });

        setTenantSyncProgressPercent(progressUi, 0, 'Bezig met synchroniseren…');

        return progressUi;
    }

    function setTenantSyncProgressPercent(progressUi, percent, statusText) {
        if (!progressUi) return;
        var value = Math.max(0, Math.min(100, Math.round(Number(percent) || 0)));
        progressUi.percent = value;
        if (progressUi.meterFill) {
            progressUi.meterFill.style.width = value + '%';
        }
        if (progressUi.meterTrack) {
            progressUi.meterTrack.setAttribute('aria-valuenow', String(value));
        }
        if (progressUi.meterPercent) {
            progressUi.meterPercent.textContent = value + '%';
        }
        if (statusText && progressUi.meterStatus) {
            progressUi.meterStatus.textContent = statusText;
        }
    }

    function appendTenantSyncProgressSection(progressUi, sectionTitle) {
        if (!progressUi || !sectionTitle || progressUi.sections[sectionTitle]) return;

        progressUi.sections[sectionTitle] = true;

        var li = document.createElement('li');
        li.className = 'tenant-sync-progress-item text-[11px] font-semibold uppercase tracking-wide text-muted-foreground pt-1';
        li.textContent = sectionTitle;
        prependTenantSyncProgressItem(progressUi, li);
    }

    function prependTenantSyncProgressItem(progressUi, li) {
        if (!progressUi || !li) return;
        li.classList.add('tenant-sync-progress-item');
        if (progressUi.list.firstChild) {
            progressUi.list.insertBefore(li, progressUi.list.firstChild);
        } else {
            progressUi.list.appendChild(li);
        }
        if (progressUi.body && !progressUi.expanded) {
            progressUi.body.scrollTop = 0;
        }
    }

    function appendTenantSyncProgressItem(progressUi, li) {
        prependTenantSyncProgressItem(progressUi, li);
    }

    function handleTenantSyncProgressEvent(progressUi, event) {
        if (!progressUi || !event || !event.type) return;

        if (event.type === 'progress') {
            var percent = typeof event.percent === 'number' ? event.percent : progressUi.percent;
            var status = 'Bezig met synchroniseren…';
            if (event.total > 0) {
                status = 'Voortgang ' + (event.done || 0) + ' / ' + event.total;
            }
            setTenantSyncProgressPercent(progressUi, percent, status);
            return;
        }

        if (event.type === 'step') {
            var stepLi = document.createElement('li');
            stepLi.className = 'flex items-start gap-2 text-foreground';
            var stepIcon = document.createElement('i');
            stepIcon.className = 'ki-filled ki-check-circle text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5';
            stepIcon.setAttribute('aria-hidden', 'true');
            var stepText = document.createElement('span');
            stepText.className = 'min-w-0 break-words';
            stepText.textContent = event.label || 'Stap voltooid';
            stepLi.appendChild(stepIcon);
            stepLi.appendChild(stepText);
            prependTenantSyncProgressItem(progressUi, stepLi);
            return;
        }

        if (event.type === 'note') {
            var noteLi = document.createElement('li');
            noteLi.className = 'text-muted-foreground text-[11px] break-words';
            noteLi.textContent = event.note || '';
            prependTenantSyncProgressItem(progressUi, noteLi);
            return;
        }

        if (event.type === 'row') {
            var row = event.row || {};
            if (!tenantSyncRowIsNotable(row)) return;

            var rowLi = buildTenantSyncReportRowLi(row);
            if (event.section) {
                var sectionTag = document.createElement('span');
                sectionTag.className = 'text-[10px] uppercase tracking-wide text-muted-foreground mr-2';
                sectionTag.textContent = event.section;
                if (rowLi.firstChild) {
                    rowLi.insertBefore(sectionTag, rowLi.firstChild);
                } else {
                    rowLi.appendChild(sectionTag);
                }
            }

            prependTenantSyncProgressItem(progressUi, rowLi);
            return;
        }

        if (event.type === 'summary' && event.summary) {
            progressUi.summaryText = event.summary;
        }
    }

    function finishTenantSyncProgressUi(progressUi, success, message) {
        if (!progressUi) return;

        setTenantSyncProgressPercent(
            progressUi,
            success ? 100 : Math.max(progressUi.percent || 0, 1),
            success ? 'Sync voltooid' : 'Sync mislukt'
        );
        if (progressUi.meterFill) {
            progressUi.meterFill.classList.toggle('is-success', !!success);
            progressUi.meterFill.classList.toggle('is-error', !success);
        }

        if (progressUi.heading) {
            progressUi.heading.className = 'tenant-sync-progress-heading font-medium mb-0 flex items-start gap-2 min-w-0 flex-1 ' + (success ? 'text-emerald-600 dark:text-emerald-400' : 'text-destructive');
            var safeMessage = String(message || (success ? (progressUi.summaryText || 'Sync voltooid.') : 'Sync mislukt.'));
            progressUi.heading.innerHTML = success
                ? '<i class="ki-filled ki-check-circle text-base shrink-0 mt-0.5" aria-hidden="true"></i><span class="min-w-0 break-words"></span>'
                : '<i class="ki-filled ki-information text-base shrink-0 mt-0.5" aria-hidden="true"></i><span class="min-w-0 break-words"></span>';
            var messageSpan = progressUi.heading.querySelector('span');
            if (messageSpan) {
                messageSpan.textContent = safeMessage;
            }
        }
    }

    function consumeTenantSyncStream(response, onEvent) {
        if (!response.body || typeof response.body.getReader !== 'function') {
            return response.text().then(function(text) {
                var data = null;
                try {
                    data = text ? JSON.parse(text) : null;
                } catch (e) {
                    throw new Error('Ongeldig antwoord van de server (HTTP ' + response.status + ').');
                }
                return { ok: response.ok, status: response.status, data: data, streamed: false };
            });
        }

        var reader = response.body.getReader();
        var decoder = new TextDecoder();
        var buffer = '';
        var completeEvent = null;

        function processBuffer(finalChunk) {
            if (finalChunk) {
                buffer += finalChunk;
            }
            var lines = buffer.split('\n');
            buffer = lines.pop() || '';
            lines.forEach(function(line) {
                line = line.trim();
                if (!line) return;
                var event = JSON.parse(line);
                if (event.type === 'complete') {
                    completeEvent = event;
                    return;
                }
                onEvent(event);
            });
        }

        function pump() {
            return reader.read().then(function(result) {
                if (result.done) {
                    if (buffer.trim() !== '') {
                        processBuffer('');
                    }
                    return {
                        ok: response.ok,
                        status: response.status,
                        data: completeEvent || {},
                        streamed: true,
                    };
                }
                processBuffer(decoder.decode(result.value, { stream: true }));
                return pump();
            });
        }

        return pump().catch(function(err) {
            if (err instanceof SyntaxError) {
                throw new Error('Ongeldige stream van de server.');
            }
            throw err;
        });
    }

    function setTenantSyncStatusSuccess(message, report) {
        if (!tenantSyncSubmitStatus) return;
        if (report) {
            renderTenantSyncReport(tenantSyncSubmitStatus, report);
            return;
        }
        tenantSyncSubmitStatus.textContent = '';
        var wrap = document.createElement('span');
        wrap.className = 'inline-flex items-start gap-1.5 min-w-0 max-w-full text-emerald-600 dark:text-emerald-400 font-medium';
        var icon = document.createElement('i');
        icon.className = 'ki-filled ki-check-circle text-lg shrink-0 mt-0.5';
        icon.setAttribute('aria-hidden', 'true');
        var txt = document.createElement('span');
        txt.className = 'min-w-0 break-words';
        txt.textContent = message || 'Sync voltooid.';
        wrap.appendChild(icon);
        wrap.appendChild(txt);
        tenantSyncSubmitStatus.appendChild(wrap);
    }

    function setTenantSyncStatusError(message) {
        if (!tenantSyncSubmitStatus) return;
        tenantSyncSubmitStatus.textContent = '';
        var wrap = document.createElement('span');
        wrap.className = 'inline-flex items-start gap-1.5 min-w-0 max-w-full text-destructive font-medium';
        var icon = document.createElement('i');
        icon.className = 'ki-filled ki-information text-lg shrink-0 mt-0.5';
        icon.setAttribute('aria-hidden', 'true');
        var txt = document.createElement('span');
        txt.className = 'min-w-0 break-words';
        txt.textContent = message || 'Er is een fout opgetreden.';
        wrap.appendChild(icon);
        wrap.appendChild(txt);
        tenantSyncSubmitStatus.appendChild(wrap);
    }

    function setTenantSyncStatusIdle() {
        if (tenantSyncSubmitStatus) tenantSyncSubmitStatus.textContent = '';
    }

    if (tenantSyncRunForm && tenantSyncSubmitBtn && tenantSyncSubmitStatus) {
        tenantSyncRunForm.addEventListener('submit', function(ev) {
            ev.preventDefault();
            if (tenantSyncSubmitBtn.disabled) return;

            var csrfMeta = document.querySelector('meta[name="csrf-token"]');
            var token = csrfMeta ? csrfMeta.getAttribute('content') : '';
            if (!token) {
                setTenantSyncStatusIdle();
                setTenantSyncStatusError('CSRF-token ontbreekt; herlaad de pagina.');
                return;
            }

            clearTenantSyncAjaxUi();
            setTenantSyncStatusIdle();

            var sel = document.getElementById('source_company_id');
            var cb = document.getElementById('confirm_full_sync');
            var fd = new FormData(tenantSyncRunForm);

            tenantSyncSubmitBtn.disabled = true;
            tenantSyncSubmitBtn.setAttribute('aria-busy', 'true');
            tenantSyncSubmitBtn.innerHTML = '<span class="inline-flex items-center gap-2"><i class="ki-filled ki-cloud-add" aria-hidden="true"></i><i class="ki-filled ki-arrows-circle text-sm animate-spin" aria-hidden="true"></i><span> Bezig…</span></span>';

            if (sel) sel.disabled = true;
            if (cb) cb.disabled = true;

            var progressUi = initTenantSyncProgressUi(tenantSyncSubmitStatus);

            fetch('{{ route("admin.settings.tenant-sync.run") }}', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/x-ndjson',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Tenant-Sync-Stream': '1',
                    'X-CSRF-TOKEN': token,
                },
            })
                .then(function(r) {
                    var contentType = (r.headers.get('content-type') || '').toLowerCase();
                    if (contentType.indexOf('ndjson') === -1) {
                        return r.text().then(function(text) {
                            var data = null;
                            try {
                                data = text ? JSON.parse(text) : null;
                            } catch (e) {
                                throw new Error('Ongeldig antwoord van de server (HTTP ' + r.status + ').');
                            }
                            return { ok: r.ok, status: r.status, data: data, streamed: false };
                        });
                    }
                    return consumeTenantSyncStream(r, function(event) {
                        handleTenantSyncProgressEvent(progressUi, event);
                    });
                })
                .then(function(res) {
                    var d = res.data || {};
                    if (res.streamed) {
                        if (d.success) {
                            finishTenantSyncProgressUi(progressUi, true, d.message || 'Sync voltooid.');
                            if (cb) cb.checked = false;
                            return;
                        }
                        finishTenantSyncProgressUi(progressUi, false, d.message || ('Fout (HTTP ' + res.status + ')'));
                        return;
                    }
                    if (res.ok && d.success) {
                        setTenantSyncStatusSuccess(d.message || 'Sync voltooid.', d.report || (d.result && d.result.report));
                        if (cb) cb.checked = false;
                        return;
                    }
                    var hasFieldErrors = d.errors && typeof d.errors === 'object' && Object.keys(d.errors).length > 0;
                    if (hasFieldErrors) {
                        applyTenantSyncValidationErrors(d.errors);
                    }
                    if (!hasFieldErrors) {
                        finishTenantSyncProgressUi(progressUi, false, d.message || ('Fout (HTTP ' + res.status + ')'));
                    } else {
                        setTenantSyncStatusIdle();
                    }
                })
                .catch(function(err) {
                    finishTenantSyncProgressUi(progressUi, false, err && err.message ? err.message : 'Netwerkfout.');
                })
                .finally(function() {
                    tenantSyncSubmitBtn.disabled = false;
                    tenantSyncSubmitBtn.removeAttribute('aria-busy');
                    tenantSyncSubmitBtn.innerHTML = tenantSyncSubmitDefaultHtml;
                    if (sel) sel.disabled = false;
                    if (cb) cb.disabled = false;
                });
        });
    }

    var tenantZipCompanySel = document.getElementById('tenant-sync-zip-company-id');
    var tenantZipCompanyErr = document.getElementById('tenant-sync-zip-company-error');
    var tenantStorageExportUrl = @json(route('admin.settings.tenant-storage-bundle.export'));
    var tenantFilesExportBtn = document.getElementById('tenant-files-export-btn');
    var tenantFilesImportForm = document.getElementById('tenant-files-import-form');
    var tenantFilesImportHid = document.getElementById('tenant-files-import-company-id');
    function clearTenantZipCompanyError() {
        if (!tenantZipCompanyErr) return;
        tenantZipCompanyErr.textContent = '';
        tenantZipCompanyErr.classList.add('hidden');
        if (tenantZipCompanySel) {
            tenantZipCompanySel.setAttribute('aria-invalid', 'false');
        }
    }
    function showTenantZipCompanyError(message) {
        if (!tenantZipCompanyErr) return;
        tenantZipCompanyErr.textContent = message;
        tenantZipCompanyErr.classList.remove('hidden');
        if (tenantZipCompanySel) {
            tenantZipCompanySel.setAttribute('aria-invalid', 'true');
            try { tenantZipCompanySel.focus(); } catch (e) {}
        }
    }
    if (tenantZipCompanySel) {
        tenantZipCompanySel.addEventListener('change', clearTenantZipCompanyError);
    }
    if (tenantFilesExportBtn && tenantZipCompanySel) {
        tenantFilesExportBtn.addEventListener('click', function() {
            var id = tenantZipCompanySel.value;
            if (!id) {
                showTenantZipCompanyError('Selecteer een tenant of NEXA SaaS om de ZIP te downloaden.');
                return;
            }
            clearTenantZipCompanyError();
            window.location.href = tenantStorageExportUrl + '?company_id=' + encodeURIComponent(id);
        });
    }
    if (tenantFilesImportForm && tenantZipCompanySel && tenantFilesImportHid) {
        tenantFilesImportForm.addEventListener('submit', function(ev) {
            var id = tenantZipCompanySel.value;
            if (!id) {
                ev.preventDefault();
                showTenantZipCompanyError('Selecteer een tenant of NEXA SaaS om de ZIP te importeren.');
                return;
            }
            clearTenantZipCompanyError();
            tenantFilesImportHid.value = id;
        });
    }

    function googleSeoApiCall(url, feedbackEl, btn) {
        var token = document.querySelector('meta[name="csrf-token"]');
        if (!token) return;
        var original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = 'Bezig…';
        feedbackEl.classList.remove('hidden', 'text-destructive', 'text-success');
        feedbackEl.textContent = 'Bezig met Google API…';
        fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token.getAttribute('content'),
            },
        })
            .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
            .then(function(res) {
                feedbackEl.textContent = (res.data && res.data.message) ? res.data.message : 'Klaar.';
                feedbackEl.classList.toggle('text-success', res.ok);
                feedbackEl.classList.toggle('text-destructive', !res.ok);
            })
            .catch(function() {
                feedbackEl.textContent = 'Netwerkfout bij contact met de server.';
                feedbackEl.classList.add('text-destructive');
            })
            .finally(function() {
                btn.disabled = false;
                btn.innerHTML = original;
            });
    }

    var seoTestBtn = document.getElementById('google-seo-test-btn');
    var seoSitemapBtn = document.getElementById('google-seo-sitemap-btn');
    var seoFeedback = document.getElementById('google-seo-api-feedback');
    if (seoTestBtn && seoFeedback) {
        seoTestBtn.addEventListener('click', function() {
            googleSeoApiCall('{{ route('admin.settings.seo.test') }}', seoFeedback, seoTestBtn);
        });
    }
    if (seoSitemapBtn && seoFeedback) {
        seoSitemapBtn.addEventListener('click', function() {
            googleSeoApiCall('{{ route('admin.settings.seo.submit-sitemap') }}', seoFeedback, seoSitemapBtn);
        });
    }
});
</script>
@endsection
