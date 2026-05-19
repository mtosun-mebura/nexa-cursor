@extends('admin.layouts.app')

@section('title', 'Configuraties')

@push('scripts')
<script src="{{ asset('assets/js/form-validation.js') }}"></script>
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
    @endif

    @if(session('success'))
        <div id="settings-success-toast"
             class="fixed top-5 right-5 z-[120] max-w-md w-[calc(100%-2.5rem)] sm:w-auto rounded-lg border border-emerald-300/60 bg-emerald-50 text-emerald-900 shadow-lg px-4 py-3 opacity-0 translate-y-2 pointer-events-none transition-all duration-300">
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

    @include('admin.settings.partials.tenant-scope-notice')

    <div class="grid gap-5 lg:gap-7.5">
        <!-- Mail Server Instellingen -->
        <div class="kt-card min-w-full" id="mail">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    <i class="ki-filled ki-sms me-2"></i> Mail Server Instellingen
                </h3>
            </div>
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
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
                    <div class="kt-card-footer flex justify-between items-center gap-5 pt-5 border-t border-border">
                        <button type="submit" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-check me-2"></i> Mail Instellingen Opslaan
                        </button>
                        <div class="flex items-end gap-2.5">
                            <div class="flex flex-col">
                                <label for="test-email-input" class="kt-form-label text-sm mb-1">Test Email</label>
                                <div class="relative">
                                    <input type="email" class="kt-input" id="test-email-input" 
                                           placeholder="test@example.com">
                                </div>
                            </div>
                            <button type="button" class="kt-btn kt-btn-outline" id="test-email-btn">
                                <i class="ki-filled ki-send me-2"></i> Verstuur Test
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Google SEO Instellingen -->
        <div class="kt-card min-w-full" id="seo">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    <i class="ki-filled ki-abstract-26 me-2"></i> Google SEO Account Gegevens
                </h3>
            </div>
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <form method="POST" action="{{ route('admin.settings.seo.update') }}" data-validate="true">
                    @csrf
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Google Search Console Property ID</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="text" 
                                           class="kt-input @error('GOOGLE_SEO_PROPERTY_ID') border-destructive @enderror" 
                                           id="GOOGLE_SEO_PROPERTY_ID" 
                                           name="GOOGLE_SEO_PROPERTY_ID" 
                                           value="{{ old('GOOGLE_SEO_PROPERTY_ID', $seoSettings['GOOGLE_SEO_PROPERTY_ID'] ?? '') }}" 
                                           placeholder="sc-domain:example.com">
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Google Search Console property ID (bijv. sc-domain:example.com)</div>
                                @error('GOOGLE_SEO_PROPERTY_ID')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
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

        <!-- Google Maps Instellingen -->
        <div class="kt-card min-w-full" id="maps">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    <i class="ki-filled ki-geolocation me-2"></i> Google Maps Configuratie
                </h3>
            </div>
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

        <!-- Google Reviews (zelfde Maps API-sleutel; Places API moet ingeschakeld zijn) -->
        <div class="kt-card min-w-full" id="google-reviews">
            <style>
            #google-reviews input[type="number"]::-webkit-outer-spin-button,
            #google-reviews input[type="number"]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
            #google-reviews input[type="number"] { -moz-appearance: textfield; appearance: textfield; }
            #google-reviews .grw-cache-hours-input { width: 4.5rem; min-width: 4.5rem; padding-right: 0.5rem !important; }
            </style>
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    <i class="ki-filled ki-star me-2"></i> Google Reviews
                </h3>
            </div>
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

        <!-- WhatsApp Business Instellingen -->
        <div class="kt-card min-w-full" id="whatsapp">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    <i class="ki-filled ki-whatsapp me-2"></i> WhatsApp Business Configuratie
                </h3>
            </div>
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <form method="POST" action="{{ route('admin.settings.whatsapp.update') }}" data-validate="true">
                    @csrf
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">WhatsApp Business API Token</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="text" 
                                           class="kt-input @error('WHATSAPP_API_TOKEN') border-destructive @enderror" 
                                           id="WHATSAPP_API_TOKEN" 
                                           name="WHATSAPP_API_TOKEN" 
                                           value="{{ old('WHATSAPP_API_TOKEN', $whatsappSettings['WHATSAPP_API_TOKEN'] ?? '') }}" 
                                           placeholder="EAAxxxxxxxxxxxx">
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">WhatsApp Business API access token</div>
                                @error('WHATSAPP_API_TOKEN')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">WhatsApp Business Phone Number ID</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="text" 
                                           class="kt-input @error('WHATSAPP_PHONE_NUMBER_ID') border-destructive @enderror" 
                                           id="WHATSAPP_PHONE_NUMBER_ID" 
                                           name="WHATSAPP_PHONE_NUMBER_ID" 
                                           value="{{ old('WHATSAPP_PHONE_NUMBER_ID', $whatsappSettings['WHATSAPP_PHONE_NUMBER_ID'] ?? '') }}" 
                                           placeholder="123456789012345">
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">WhatsApp Business Phone Number ID</div>
                                @error('WHATSAPP_PHONE_NUMBER_ID')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">WhatsApp Business Account ID</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="text" 
                                           class="kt-input @error('WHATSAPP_BUSINESS_ACCOUNT_ID') border-destructive @enderror" 
                                           id="WHATSAPP_BUSINESS_ACCOUNT_ID" 
                                           name="WHATSAPP_BUSINESS_ACCOUNT_ID" 
                                           value="{{ old('WHATSAPP_BUSINESS_ACCOUNT_ID', $whatsappSettings['WHATSAPP_BUSINESS_ACCOUNT_ID'] ?? '') }}" 
                                           placeholder="123456789012345">
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">WhatsApp Business Account ID</div>
                                @error('WHATSAPP_BUSINESS_ACCOUNT_ID')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">WhatsApp API Version</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="text" 
                                           class="kt-input @error('WHATSAPP_API_VERSION') border-destructive @enderror" 
                                           id="WHATSAPP_API_VERSION" 
                                           name="WHATSAPP_API_VERSION" 
                                           value="{{ old('WHATSAPP_API_VERSION', $whatsappSettings['WHATSAPP_API_VERSION'] ?? 'v18.0') }}" 
                                           placeholder="v18.0">
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">WhatsApp Business API versie (bijv. v18.0)</div>
                                @error('WHATSAPP_API_VERSION')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Webhook Verify Token</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="text" 
                                           class="kt-input @error('WHATSAPP_WEBHOOK_VERIFY_TOKEN') border-destructive @enderror" 
                                           id="WHATSAPP_WEBHOOK_VERIFY_TOKEN" 
                                           name="WHATSAPP_WEBHOOK_VERIFY_TOKEN" 
                                           value="{{ old('WHATSAPP_WEBHOOK_VERIFY_TOKEN', $whatsappSettings['WHATSAPP_WEBHOOK_VERIFY_TOKEN'] ?? '') }}" 
                                           placeholder="your-verify-token">
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Token voor webhook verificatie</div>
                                @error('WHATSAPP_WEBHOOK_VERIFY_TOKEN')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">Standaard Bericht Template</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <textarea rows="4" 
                                              class="kt-input pt-1 @error('WHATSAPP_DEFAULT_MESSAGE') border-destructive @enderror" 
                                              id="WHATSAPP_DEFAULT_MESSAGE" 
                                              name="WHATSAPP_DEFAULT_MESSAGE" 
                                              placeholder="Hallo, bedankt voor uw interesse...">{{ old('WHATSAPP_DEFAULT_MESSAGE', $whatsappSettings['WHATSAPP_DEFAULT_MESSAGE'] ?? '') }}</textarea>
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Standaard bericht template dat wordt gebruikt bij automatische WhatsApp berichten</div>
                                @error('WHATSAPP_DEFAULT_MESSAGE')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" class="pt-4">
                                <div class="rounded-lg border border-border bg-background px-4 py-3">
                                    <div class="text-sm font-semibold text-secondary-foreground">WhatsApp Direct (zonder Business API)</div>
                                    <div class="text-xs text-muted-foreground mt-1">Gebruik alleen een telefoonnummer om bij het versturen van de boeking direct WhatsApp te openen met een voorgestelde samenvatting.</div>
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
                                           {{ old('WHATSAPP_CLICK_TO_CHAT_ENABLED', $whatsappSettings['WHATSAPP_CLICK_TO_CHAT_ENABLED'] ?? '0') === '1' ? 'checked' : '' }}>
                                    <span class="text-sm text-secondary-foreground">Boekingsknop opent ook WhatsApp-bericht</span>
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
                                <div class="text-xs text-muted-foreground mt-1">Zelfde controle als het widgetnummer; wordt opgeslagen als +31… voor <code class="text-xs">wa.me</code>.</div>
                                @error('WHATSAPP_CLICK_TO_CHAT_NUMBER')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" class="pt-4">
                                <div class="rounded-lg border border-border bg-background px-4 py-3">
                                    <div class="text-sm font-semibold text-secondary-foreground">Frontend WhatsApp Widget</div>
                                    <div class="text-xs text-muted-foreground mt-1">Toont rechtsonder op de frontend een WhatsApp-icoon. Klanten kunnen dan kiezen tussen bellen of een WhatsApp-bericht starten.</div>
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
                                <div class="text-xs text-muted-foreground mt-1">Zelfde controle als “WhatsApp Nummer (zonder Business API)”; wordt opgeslagen als +31… voor <code class="text-xs">tel:</code> en <code class="text-xs">wa.me</code>.</div>
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
                                <div class="text-xs text-muted-foreground mt-1">Deze tekst wordt voorgesteld wanneer iemand via de frontend-widget op “Bericht sturen” klikt.</div>
                                @error('WHATSAPP_WIDGET_DEFAULT_MESSAGE')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    </table>
                    <div class="kt-card-footer flex justify-end items-center gap-5 pt-5 border-t border-border">
                        <button type="submit" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-check me-2"></i> WhatsApp Instellingen Opslaan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="kt-card min-w-full" id="tenant-sync">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    <i class="ki-filled ki-cloud-change me-2"></i> Omgeving-sync (tenant)
                </h3>
            </div>
            <div class="kt-card-content px-6 pb-4 space-y-6">
                <p class="text-sm text-secondary-foreground">
                    Stel hier de <strong>doel-database</strong> in (bijv. productie). Daarna kun je een <strong>bron-tenant</strong> (bedrijf op deze omgeving) naar die database <em>toevoegen</em>:
                    de rij in <code class="text-xs">companies</code> plus alle rijen op tabellen met <code class="text-xs">company_id</code> voor dat bedrijf.
                    Bestaande rijen op doel worden niet overschreven; bron-<code class="text-xs">id</code>-waarden worden niet overgenomen (nieuwe id’s + FK-remapping waar mogelijk).
                    Rollen/rechten (Spatie) worden niet meegekopieerd. Alleen de <strong>hoofd-databaseverbinding</strong> van de URL; geen bestanden over het net.
                </p>

                <div class="rounded-md border border-border bg-muted/30 px-3 py-3 text-xs text-secondary-foreground">
                    <p class="font-medium text-foreground mb-2">Tabellen op <strong>deze</strong> omgeving (driver: <code class="font-mono">{{ $tenantSyncScope['driver'] ?? '?' }}</code>)</p>
                    <p class="mb-1"><span class="text-foreground font-medium">Altijd mee:</span> {{ $tenantSyncScope['company_row'] ?? 'companies' }}</p>
                    <p class="mb-1"><span class="text-foreground font-medium">Met <code class="font-mono">company_id</code> ({{ count($tenantSyncScope['tables_with_company_id'] ?? []) }} tabellen):</span></p>
                    <div class="max-h-40 overflow-y-auto rounded border border-border/80 bg-background px-2 py-1.5 font-mono text-[11px] leading-relaxed text-foreground">
                        @php $syncTables = $tenantSyncScope['tables_with_company_id'] ?? []; @endphp
                        @forelse ($syncTables as $t)
                            <span class="inline-block me-2 mb-0.5">{{ $t }}</span>
                        @empty
                            <span class="text-destructive">Geen tabellen gevonden (controleer database).</span>
                        @endforelse
                    </div>
                    <p class="mt-2 mb-0"><span class="text-foreground font-medium">Expliciet uitgesloten</span> (config <code class="font-mono">tenant_sync.excluded_tables</code>):</p>
                    <p class="mt-0.5 font-mono text-[11px] text-muted-foreground break-all">{{ implode(', ', $tenantSyncScope['excluded_tables'] ?? []) }}</p>
                </div>

                <form method="POST" action="{{ route('admin.settings.tenant-sync.update') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="tenant_sync_target_database_url" class="text-sm text-secondary-foreground block mb-1">Database-URL (doel)</label>
                        <input type="text" name="tenant_sync_target_database_url" id="tenant_sync_target_database_url"
                               class="kt-input w-full font-mono text-xs"
                               value="{{ old('tenant_sync_target_database_url', $tenantSyncSettings['tenant_sync_target_database_url'] ?? '') }}"
                               placeholder="pgsql://user:pass@host:5432/database_of_mysql_url">
                        <p class="text-xs text-muted-foreground mt-1">Ook via .env: <code class="text-xs">TENANT_SYNC_TARGET_DATABASE_URL</code>. Speciale tekens in gebruikersnaam of wachtwoord (zoals <code class="text-xs">@</code>, <code class="text-xs">:</code>, <code class="text-xs">/</code>) moeten <strong>URL-encoded</strong> zijn, bijv. <code class="text-xs">@</code> → <code class="text-xs">%40</code>, anders wordt de host verkeerd geparsed.</p>
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
                    <h4 class="text-sm font-medium text-foreground mb-2">Volledige tenant-sync uitvoeren</h4>
                    <p class="text-xs text-muted-foreground mb-4">
                        Kies het bedrijf (tenant) op <strong>deze</strong> omgeving. Push moet aan staan en productie-push mag alleen als je dat in .env expliciet toestaat.
                    </p>
                    <form id="tenant-sync-run-form" method="POST" action="{{ route('admin.settings.tenant-sync.run') }}" class="space-y-4" novalidate>
                        @csrf
                        <div>
                            <label for="source_company_id" class="text-sm text-secondary-foreground block mb-1">Bron-tenant (bedrijf) <span class="text-destructive">*</span></label>
                            <select name="source_company_id" id="source_company_id" class="kt-select w-full max-w-xl @error('source_company_id') border-destructive @enderror">
                                <option value="" disabled @selected(old('source_company_id') === null || old('source_company_id') === '')>— Kies een bedrijf —</option>
                                @foreach ($companiesForSync ?? [] as $c)
                                    <option value="{{ $c->id }}" @selected((string) old('source_company_id') === (string) $c->id)>{{ $c->name }} (id {{ $c->id }})</option>
                                @endforeach
                            </select>
                            @error('source_company_id')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                            <div id="tenant-sync-ajax-error-source_company_id" class="text-xs text-destructive mt-1 hidden" role="alert"></div>
                            @if (($companiesForSync ?? collect())->isEmpty())
                                <div class="text-xs text-destructive mt-1">Geen bedrijven gevonden om te synchroniseren.</div>
                            @endif
                        </div>
                        <label class="inline-flex items-start gap-2">
                            <input type="checkbox" name="confirm_full_sync" value="1" id="confirm_full_sync" class="kt-checkbox mt-0.5 @error('confirm_full_sync') border-destructive @enderror"
                                   @checked(old('confirm_full_sync') === '1')>
                            <span class="text-sm text-secondary-foreground">Ik bevestig dat ik naar de geconfigureerde doel-database wil schrijven (alleen toevoegen, geen overschrijven op bestaande pk’s).</span>
                        </label>
                        @error('confirm_full_sync')
                            <div class="text-xs text-destructive">{{ $message }}</div>
                        @enderror
                        <div id="tenant-sync-ajax-error-confirm_full_sync" class="text-xs text-destructive mt-1 hidden" role="alert"></div>
                        <div class="flex flex-wrap items-center gap-3">
                            <button type="submit" id="tenant-sync-submit-btn" class="kt-btn kt-btn-primary"
                                    @if (($companiesForSync ?? collect())->isEmpty()) disabled @endif>
                                <i class="ki-filled ki-cloud-add me-2"></i> Start tenant-sync
                            </button>
                            <span id="tenant-sync-submit-status" class="inline-flex items-center gap-2 text-sm min-h-[2.125rem] max-w-xl" aria-live="polite"></span>
                        </div>
                    </form>
                </div>

                <div class="border-t border-border pt-6 mt-2">
                    <h4 class="text-sm font-medium text-foreground mb-2">ZIP-export / -import (volledige tenant)</h4>
                    <p class="text-xs text-muted-foreground mb-4">
                        Eén bundle per bedrijf: <strong class="text-foreground">bestanden</strong> (o.a. website-media uit pagina’s en uit tenant-instellingen, logo-pad, CV’s, factuur-PDF’s),
                        <strong class="text-foreground">website_pages</strong> in het manifest, en <strong class="text-foreground">tenant-general_settings</strong> (mail, SEO, Maps, enz.; geen platform-sync-keys).
                        Bestandsnaam begint met <code class="font-mono text-[11px]">tenant-export-</code>. Manifest: <code class="font-mono text-[11px]">bundle_type</code> <code class="font-mono text-[11px]">tenant_media</code>, <code class="font-mono text-[11px]">bundle_version</code> 2.
                        Oudere ZIP’s (alleen bestanden, versie 1) blijven importeerbaar.
                    </p>
                    <div class="space-y-6 max-w-2xl">
                        <div>
                            <label for="tenant-sync-zip-company-id" class="text-sm text-secondary-foreground block mb-1">Tenant (bedrijf)</label>
                            <select id="tenant-sync-zip-company-id" class="kt-select w-full" aria-describedby="tenant-sync-zip-company-error" aria-invalid="false">
                                <option value="">— Kies een bedrijf —</option>
                                @foreach (($companiesForSync ?? []) as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }} (id {{ $c->id }})</option>
                                @endforeach
                            </select>
                            <p id="tenant-sync-zip-company-error" class="mt-1.5 hidden text-sm text-destructive" role="alert"></p>
                        </div>

                        <div class="rounded-md border border-border bg-muted/30 px-3 py-3 space-y-3">
                            <h5 class="text-sm font-medium text-foreground m-0">Tenant-export (ZIP)</h5>
                            <p class="text-xs text-muted-foreground m-0">
                                Download of importeer één ZIP met <code class="font-mono text-[11px]">manifest.json</code>.
                                Publieke bestanden staan onder <code class="font-mono text-[11px]">files/…</code> (komt in <code class="font-mono text-[11px]">storage/app/public</code> met dezelfde mappenstructuur).
                                Versleutelde website-carouselbestanden staan onder <code class="font-mono text-[11px]">private_files/…</code> (komt in <code class="font-mono text-[11px]">storage/app/…</code>).
                                Import overschrijft <code class="font-mono text-[11px]">website_pages</code> per slug/module voor het gekozen bedrijf, zet tenant-instellingen, en schrijft alle bestanden terug.
                                Voor alleen databaserijen naar een andere database: gebruik hierboven <strong class="text-foreground">Volledige tenant-sync</strong> (die neemt o.a. <code class="font-mono text-[11px]">general_settings</code> per tenant automatisch mee als die tabel <code class="font-mono text-[11px]">company_id</code> heeft).
                            </p>
                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" id="tenant-files-export-btn" class="kt-btn kt-btn-outline">
                                    <i class="ki-filled ki-file-down me-2"></i> Download tenant-ZIP
                                </button>
                            </div>
                            <form method="POST" action="{{ route('admin.settings.tenant-storage-bundle.import') }}" enctype="multipart/form-data" class="space-y-3" id="tenant-files-import-form">
                                @csrf
                                <input type="hidden" name="company_id" id="tenant-files-import-company-id" value="">
                                <div>
                                    <label for="tenant-files-bundle-input" class="text-sm text-secondary-foreground block mb-1">Tenant-ZIP importeren</label>
                                    <input type="file" name="bundle" id="tenant-files-bundle-input" accept=".zip,application/zip" class="kt-input w-full text-sm py-1.5">
                                    <p class="text-xs text-muted-foreground mt-1">Max. 500 MB per upload. Bestaande bestanden met dezelfde relatieve padnaam worden overschreven.</p>
                                </div>
                                <button type="submit" class="kt-btn kt-btn-primary" id="tenant-files-import-submit"
                                        @if (($companiesForSync ?? collect())->isEmpty()) disabled @endif>
                                    <i class="ki-filled ki-file-up me-2"></i> Importeer tenant-ZIP
                                </button>
                            </form>
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
        requestAnimationFrame(function() {
            successToast.classList.remove('opacity-0', 'translate-y-2', 'pointer-events-none');
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
    
    if (testEmailBtn && testEmailInput) {
        testEmailBtn.addEventListener('click', function() {
            const email = testEmailInput.value.trim();
            
            if (!email) {
                alert('Vul een e-mailadres in om te testen.');
                return;
            }
            
            if (!email.includes('@')) {
                alert('Vul een geldig e-mailadres in.');
                return;
            }
            
            // Disable button during request
            testEmailBtn.disabled = true;
            testEmailBtn.innerHTML = '<i class="ki-filled ki-arrows-circle"></i> Verzenden...';
            
            fetch('{{ route("admin.settings.mail.test") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    test_email: email
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✓ ' + data.message);
                } else {
                    alert('✗ ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Er is een fout opgetreden bij het testen van de email.');
            })
            .finally(() => {
                testEmailBtn.disabled = false;
                testEmailBtn.innerHTML = '<i class="ki-filled ki-send me-2"></i> Verstuur Test';
            });
        });
    }

    const tenantSyncTestBtn = document.getElementById('tenant-sync-test-btn');
    const tenantSyncUrlInput = document.getElementById('tenant_sync_target_database_url');
    const tenantSyncTestResult = document.getElementById('tenant-sync-test-result');
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
            fd.append('tenant_sync_target_database_url', url);
            fd.append('_token', token);
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

    function setTenantSyncStatusSuccess(message) {
        if (!tenantSyncSubmitStatus) return;
        tenantSyncSubmitStatus.textContent = '';
        var wrap = document.createElement('span');
        wrap.className = 'inline-flex items-start gap-1.5 text-emerald-600 dark:text-emerald-400 font-medium';
        var icon = document.createElement('i');
        icon.className = 'ki-filled ki-check-circle text-lg shrink-0 mt-0.5';
        icon.setAttribute('aria-hidden', 'true');
        var txt = document.createElement('span');
        txt.textContent = message || 'Sync voltooid.';
        wrap.appendChild(icon);
        wrap.appendChild(txt);
        tenantSyncSubmitStatus.appendChild(wrap);
    }

    function setTenantSyncStatusError(message) {
        if (!tenantSyncSubmitStatus) return;
        tenantSyncSubmitStatus.textContent = '';
        var wrap = document.createElement('span');
        wrap.className = 'inline-flex items-start gap-1.5 text-destructive font-medium';
        var icon = document.createElement('i');
        icon.className = 'ki-filled ki-information text-lg shrink-0 mt-0.5';
        icon.setAttribute('aria-hidden', 'true');
        var txt = document.createElement('span');
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

            fetch('{{ route("admin.settings.tenant-sync.run") }}', {
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
                            throw new Error('Ongeldig antwoord van de server (HTTP ' + r.status + ').');
                        }
                        return { ok: r.ok, status: r.status, data: data };
                    });
                })
                .then(function(res) {
                    var d = res.data || {};
                    if (res.ok && d.success) {
                        setTenantSyncStatusSuccess(d.message || 'Sync voltooid.');
                        if (cb) cb.checked = false;
                        return;
                    }
                    var hasFieldErrors = d.errors && typeof d.errors === 'object' && Object.keys(d.errors).length > 0;
                    if (hasFieldErrors) {
                        applyTenantSyncValidationErrors(d.errors);
                    }
                    if (!hasFieldErrors) {
                        setTenantSyncStatusError(d.message || ('Fout (HTTP ' + res.status + ')'));
                    } else {
                        setTenantSyncStatusIdle();
                    }
                })
                .catch(function(err) {
                    setTenantSyncStatusError(err && err.message ? err.message : 'Netwerkfout.');
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
                showTenantZipCompanyError('Selecteer een tenant (bedrijf) om de ZIP te downloaden.');
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
                showTenantZipCompanyError('Selecteer een tenant (bedrijf) om de tenant-ZIP te importeren.');
                return;
            }
            clearTenantZipCompanyError();
            tenantFilesImportHid.value = id;
        });
    }
});
</script>
@endsection
