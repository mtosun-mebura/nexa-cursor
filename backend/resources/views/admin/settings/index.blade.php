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

    <!-- Success Alert -->
    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5" id="success-alert" role="alert">
            <i class="ki-filled ki-check-circle me-2"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="kt-alert kt-alert-danger mb-5" role="alert">
            <i class="ki-filled ki-information me-2"></i>
            {{ session('error') }}
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

        <!-- WhatsApp Business Instellingen -->
        <div class="kt-card min-w-full" id="whatsapp">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    <i class="ki-filled ki-chat me-2"></i> WhatsApp Business Configuratie
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
                    </table>
                    <div class="kt-card-footer flex justify-end items-center gap-5 pt-5 border-t border-border">
                        <button type="submit" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-check me-2"></i> WhatsApp Instellingen Opslaan
                        </button>
                    </div>
                </form>
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
});
</script>
@endsection
