@extends('admin.layouts.app')

@include('admin.settings.partials.collapsible-section-assets')

@section('content')
<div class="kt-container-fixed">
    <div class="kt-container-fixed mt-5">
        <div class="flex items-center flex-wrap md:flex-nowrap lg:items-center justify-between gap-3 lg:gap-6 mb-5 lg:mb-10 mt-5">
            <div class="flex items-center gap-2.5">
                <h1 class="text-2xl lg:text-3xl font-bold text-mono">Algemene configuraties</h1>
            </div>
        </div>

        @if(session('success'))
            <div class="kt-alert kt-alert-success mb-5">
                <div class="kt-alert-content">
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="kt-alert kt-alert-danger mb-5">
                <div class="kt-alert-content">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <!-- Huidige logo en favicon bovenaan gecentreerd -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-8 sm:gap-12 py-8 mb-8 rounded-xl bg-muted/30 dark:bg-muted/10 border border-border">
            <div class="flex flex-col items-center gap-2">
                <span class="text-sm font-medium text-muted-foreground">Huidige logo</span>
                @if($logo && Storage::disk('public')->exists($logo))
                    <img alt="Logo" class="h-[35px] w-auto object-contain" src="{{ route('admin.settings.logo') }}?t={{ time() }}" id="logo-preview-top" />
                @else
                    <span class="text-sm text-muted-foreground italic py-2" id="logo-preview-placeholder">Geen logo geüpload</span>
                    <img alt="Logo" class="h-[35px] w-auto object-contain hidden" src="" id="logo-preview-top" />
                @endif
            </div>
            <div class="flex flex-col items-center gap-2">
                <span class="text-sm font-medium text-muted-foreground">Huidige favicon</span>
                @if($favicon && Storage::disk('public')->exists($favicon))
                    <img alt="Favicon" class="w-12 h-12 object-contain" src="{{ $faviconDisplayUrl ?? route('admin.settings.favicon') }}" id="favicon-preview-top" />
                @else
                    <span class="text-sm text-muted-foreground italic py-2" id="favicon-preview-placeholder">Geen favicon geüpload</span>
                    <img alt="Favicon" class="w-12 h-12 object-contain hidden" src="" id="favicon-preview-top" />
                @endif
            </div>
        </div>

        <form action="{{ route('admin.settings.general.update') }}" method="POST" enctype="multipart/form-data" id="general-settings-form">
            @csrf
        <div id="general-settings-collapsible-root">
        @php
            $hasLogo = $logo && Storage::disk('public')->exists($logo);
            $hasLogoDark = !empty($logoDark) && Storage::disk('public')->exists($logoDark);
            $useLightDark = ($logoMode ?? 'single') === 'light_dark';
            $logoLightUrl = $hasLogo ? route('admin.settings.logo') : null;
            $logoDarkUrl = ($hasLogo && $useLightDark && $hasLogoDark) ? route('admin.settings.logo-dark') : $logoLightUrl;
        @endphp
        <div class="kt-card mb-8 settings-collapsible-card settings-collapsible-card--collapsed">
            @include('admin.settings.partials.collapsible-header', ['titleHtml' => 'Logo & Favicon'])
            <div class="settings-collapsible-body">
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table">
                    <tbody>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal align-top">Logo</td>
                        <td class="min-w-48 w-full align-top">
                            <input type="hidden" name="logo_mode" id="logo-mode-input" value="{{ old('logo_mode', $logoMode ?? 'single') }}">
                            <div class="mb-0">
                                <p class="text-sm text-muted-foreground mb-3">Het logo wordt gebruikt in de admin-sidebar en op de frontend (header en footer).</p>
                                <div class="flex flex-col gap-2 mb-4">
                                    <span class="text-sm text-muted-foreground">Eén logo voor beide modi</span>
                                    <div class="flex flex-wrap items-center gap-3">
                                        <input type="checkbox" id="logo-mode-toggle" class="kt-switch kt-switch-sm" {{ old('logo_mode', $logoMode ?? 'single') === 'light_dark' ? 'checked' : '' }} aria-label="Apart logo voor light en dark mode">
                                        <span class="text-sm text-muted-foreground">Apart logo voor light en dark mode</span>
                                    </div>
                                </div>

                                @if($logoLightUrl)
                                <p class="text-sm font-medium text-muted-foreground mb-2">Zo ziet het logo eruit in de sidebar en op de frontend (wisselt mee met light/dark modus)</p>
                                <div id="settings-live-preview-wrap" class="flex items-center justify-start gap-3 mb-4 p-3 rounded-lg border border-border bg-muted/30">
                                    <img alt="Logo light" class="logo-light w-auto max-w-[140px] object-contain dark:hidden" style="height: {{ $logoSize }}px;" src="{{ $logoLightUrl }}" id="settings-live-preview-light" />
                                    <img alt="Logo dark" class="logo-dark w-auto max-w-[140px] object-contain hidden dark:block" style="height: {{ $logoSize }}px;" src="{{ $logoDarkUrl }}" id="settings-live-preview-dark" />
                                </div>
                                @endif

                                <p class="text-sm font-medium text-muted-foreground mb-2">Light mode (standaard)</p>
                                <div class="max-w-96 w-full flex flex-col gap-3 items-center mb-4">
                                    <div class="flex flex-col items-center gap-2 w-full">
                                        @if($hasLogo)
                                            <img alt="Logo Preview" class="h-[35px] w-auto object-contain"
                                                 src="{{ route('admin.settings.logo') }}"
                                                 id="logo-preview"/>
                                        @else
                                            <img alt="Logo Preview" class="h-[35px] w-auto object-contain hidden"
                                                 src=""
                                                 id="logo-preview"/>
                                        @endif
                                        <button type="button" class="kt-btn kt-btn-sm kt-btn-outline kt-btn-icon text-destructive {{ $hasLogo ? '' : 'hidden' }}" id="logo-light-remove-btn" title="Light logo verwijderen" aria-label="Light logo verwijderen">
                                            <i class="ki-filled ki-trash text-lg"></i>
                                        </button>
                                    </div>
                                    <div class="flex flex-col items-center justify-center w-full p-5 lg:p-7 border border-input rounded-xl border-dashed bg-muted/30 min-h-[130px] min-w-0 cursor-pointer hover:border-primary transition-colors" id="logo-upload-area" role="button" tabindex="0">
                                        <div class="flex flex-col place-items-center place-content-center text-center w-full pointer-events-none">
                                            <div class="flex items-center mb-2.5">
                                                <div class="relative size-11 shrink-0 flex items-center justify-center">
                                                    <i class="ki-filled ki-picture text-2xl text-primary"></i>
                                                </div>
                                            </div>
                                            <a class="text-mono text-xs font-medium hover:text-primary mb-px cursor-pointer pointer-events-auto" id="logo-upload-link">
                                                Klik of Sleep &amp; Drop
                                            </a>
                                            <span class="text-xs text-muted-foreground">
                                                SVG, PNG, JPG, GIF (max. 2MB)
                                            </span>
                                        </div>
                                    </div>
                                    <input type="file"
                                           name="logo"
                                           id="logo-input"
                                           accept="image/jpeg,image/png,image/jpg,image/gif,image/svg+xml"
                                           class="hidden">
                                </div>
                                <p class="text-xs text-muted-foreground mt-1 mb-4">Ondersteunde formaten: JPEG, PNG, JPG, GIF, SVG (max. 2MB)</p>

                                <div id="logo-dark-block" class="{{ ($logoMode ?? 'single') === 'light_dark' ? '' : 'hidden' }}">
                                    <p class="text-sm font-medium text-muted-foreground mb-2">Dark mode</p>
                                    <div class="max-w-96 w-full flex flex-col gap-3 items-center">
                                        <div class="flex flex-col items-center w-full gap-2">
                                            @if($hasLogoDark)
                                                <img alt="Dark logo preview" class="h-[35px] w-auto object-contain"
                                                     src="{{ route('admin.settings.logo-dark') }}?t={{ time() }}"
                                                     id="logo-dark-preview"/>
                                            @else
                                                <img alt="Dark logo preview" class="h-[35px] w-auto object-contain hidden"
                                                     src=""
                                                     id="logo-dark-preview"/>
                                            @endif
                                            <button type="button" class="kt-btn kt-btn-sm kt-btn-outline kt-btn-icon text-destructive {{ $hasLogoDark ? '' : 'hidden' }}" id="logo-dark-remove-btn" title="Dark logo verwijderen" aria-label="Dark logo verwijderen">
                                                <i class="ki-filled ki-trash text-lg"></i>
                                            </button>
                                        </div>
                                        <div class="flex flex-col items-center justify-center w-full p-5 lg:p-7 border border-input rounded-xl border-dashed bg-muted/30 min-h-[130px] min-w-0 cursor-pointer hover:border-primary transition-colors" id="logo-dark-upload-area" role="button" tabindex="0">
                                            <div class="flex flex-col place-items-center place-content-center text-center w-full pointer-events-none">
                                                <div class="flex items-center mb-2.5">
                                                    <div class="relative size-11 shrink-0 flex items-center justify-center">
                                                        <i class="ki-filled ki-picture text-2xl text-primary"></i>
                                                    </div>
                                                </div>
                                                <a class="text-mono text-xs font-medium hover:text-primary mb-px cursor-pointer pointer-events-auto" id="logo-dark-upload-link">
                                                    Klik of Sleep &amp; Drop
                                                </a>
                                                <span class="text-xs text-muted-foreground">
                                                    SVG, PNG, JPG, GIF (max. 2MB)
                                                </span>
                                            </div>
                                        </div>
                                        <input type="file"
                                               id="logo-dark-input"
                                               accept="image/jpeg,image/png,image/jpg,image/gif,image/svg+xml"
                                               class="hidden">
                                    </div>
                                    <p class="text-xs text-muted-foreground mt-1">Ondersteunde formaten: JPEG, PNG, JPG, GIF, SVG (max. 2MB)</p>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal align-top">Logo grootte (px)</td>
                        <td class="min-w-48 w-full align-top">
                            <p class="text-sm text-muted-foreground mb-3">Stel de hoogte van het logo in pixels in.</p>
                            <select name="logo_size" id="logo_size" class="kt-input" required>
                                @foreach(range(26, 50, 2) as $size)
                                    <option value="{{ $size }}" {{ $logoSize == (string)$size ? 'selected' : '' }}>{{ $size }}px</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal align-top">Favicon</td>
                        <td class="min-w-48 w-full align-top">
                            <p class="text-sm text-muted-foreground mb-3">Het favicon wordt gebruikt in de browser tab.</p>
                            <div class="max-w-96 w-full flex flex-col gap-3">
                                @if($favicon && Storage::disk('public')->exists($favicon))
                                    <img alt="Favicon Preview" class="w-16 h-16 object-contain self-start"
                                         src="{{ $faviconDisplayUrl ?? route('admin.settings.favicon') }}"
                                         id="favicon-preview"/>
                                @else
                                    <img alt="Favicon Preview" class="w-16 h-16 object-contain self-start hidden"
                                         src=""
                                         id="favicon-preview"/>
                                @endif
                                <div class="flex flex-col items-center justify-center w-full p-5 lg:p-7 border border-input rounded-xl border-dashed bg-muted/30 min-h-[130px] min-w-0 cursor-pointer hover:border-primary transition-colors" id="favicon-upload-area" role="button" tabindex="0">
                                    <div class="flex flex-col place-items-center place-content-center text-center w-full pointer-events-none">
                                        <div class="flex items-center mb-2.5">
                                            <div class="relative size-11 shrink-0 flex items-center justify-center">
                                                <i class="ki-filled ki-picture text-2xl text-primary"></i>
                                            </div>
                                        </div>
                                        <a class="text-mono text-xs font-medium hover:text-primary mb-px cursor-pointer pointer-events-auto" id="favicon-upload-link">
                                            Klik of Sleep &amp; Drop
                                        </a>
                                        <span class="text-xs text-muted-foreground">
                                            ICO, PNG, JPG (max. 2MB)
                                        </span>
                                    </div>
                                </div>
                                <input type="file"
                                       name="favicon"
                                       id="favicon-input"
                                       accept="image/x-icon,image/png,image/jpeg"
                                       class="hidden">
                            </div>
                            <p class="text-xs text-muted-foreground mt-1">Ondersteunde formaten: ICO, PNG, JPG (max. 2MB)</p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" class="px-4 sm:px-6 pb-6 pt-2 align-top">
                            <div class="flex justify-end">
                                <button type="submit" class="kt-btn kt-btn-primary">
                                    <i class="ki-filled ki-check me-2"></i> Opslaan
                                </button>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
            </div>
        </div>
        <!-- Algemene opties. Applicatienaam, omschrijving en Mijn-omgeving-knop staan per module onder Modules Beheer > [module] > Configureren. -->
        <div class="kt-card mb-8 settings-collapsible-card settings-collapsible-card--collapsed">
            @include('admin.settings.partials.collapsible-header', ['titleHtml' => 'Algemene opties'])
            <div class="settings-collapsible-body">
            <div class="kt-card-table kt-scrollable-x-auto pb-3 admin-form-general-options-section">
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table">
                    <colgroup>
                        <col class="admin-form-label-col">
                        <col>
                    </colgroup>
                    <tbody>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal align-top">Admin footertekst</td>
                        <td class="min-w-48 w-full min-w-0 align-top">
                            <input type="text" name="admin_footer_brand" id="admin_footer_brand" class="kt-input w-full max-w-xl" value="{{ old('admin_footer_brand', $adminFooterBrand ?? 'Nexa Skillmatching') }}" maxlength="255" placeholder="Nexa Skillmatching">
                            <p class="text-xs text-muted-foreground mt-1 max-w-full">Tekst rechts van het jaartal in de footer van het admin-panel (bijv. <span class="font-mono">{{ date('Y') }}© Nexa Skillmatching</span>). Het jaar wordt automatisch bijgewerkt.</p>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal align-top">AI-assistent tonen</td>
                        <td class="min-w-48 w-full min-w-0 align-top">
                            <div class="flex flex-wrap items-center gap-3 w-full min-w-0 max-w-full">
                                <input type="checkbox" name="ai_chat_enabled" id="ai_chat_enabled" class="kt-switch kt-switch-sm shrink-0" value="1" {{ old('ai_chat_enabled', $aiChatEnabled ?? '0') === '1' ? 'checked' : '' }}>
                                <span class="text-sm text-muted-foreground min-w-0">Toon het AI-icoon rechtsboven in de header (naast licht/donker) op de publieke website en het portaal.</span>
                            </div>
                        </td>
                    </tr>
                    @foreach($aiChatModules ?? [] as $aiChatModule)
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal align-top">
                            {{ $aiChatModule->display_name ?: $aiChatModule->name }} — webhook (n8n)
                        </td>
                        <td class="min-w-48 w-full min-w-0 align-top">
                            <input type="url"
                                   name="ai_chat_webhooks[{{ $aiChatModule->name }}]"
                                   id="ai_chat_webhook_{{ $aiChatModule->name }}"
                                   class="kt-input w-full max-w-2xl"
                                   value="{{ old('ai_chat_webhooks.'.$aiChatModule->name, $aiChatModuleWebhooks[$aiChatModule->name] ?? '') }}"
                                   placeholder="{{ $aiChatModuleWebhookDefaults[$aiChatModule->name] ?? '' }}">
                            @if($loop->first)
                            <p class="text-xs text-muted-foreground mt-1 max-w-full">Per module de n8n-webhook waarmee de AI-chat praat op de frontend van die module. Leeg laten = standaard uit configuratie. Berichten gaan via de backend; het antwoord wordt in de chat getoond.</p>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 sm:px-6 pb-6 pt-2 flex justify-end">
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-check me-2"></i> Opslaan
                </button>
            </div>
            </div>
        </div>

        <!-- Formulier succesbericht (informatieaanvraag / contactformulier op de website) -->
        <div class="kt-card mb-8 settings-collapsible-card settings-collapsible-card--collapsed">
            @include('admin.settings.partials.collapsible-header', ['titleHtml' => 'Formulier succesbericht'])
            <div class="settings-collapsible-body">
            <div class="kt-card-table kt-scrollable-x-auto pb-3 admin-form-success-section">
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table">
                    <colgroup>
                        <col class="admin-form-label-col">
                        <col>
                    </colgroup>
                    <tbody>
                    <tr class="admin-form-success-intro">
                        <td colspan="2" class="px-4 sm:px-6 pt-6 pb-2 align-top">
                            <p class="text-sm text-muted-foreground mb-0 max-w-full">Teksten en icoon of plaatje die bezoekers zien nadat ze een formulier succesvol hebben verzonden. Geldt voor alle formulieren op de website. Kies een plaatje <em>of</em> een icoon; bij een geüploade plaatje heeft het icoon geen effect.</p>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal align-top">Hoofdtekst</td>
                        <td class="min-w-48 w-full align-top">
                            <input type="text" name="info_request_success_title" id="info_request_success_title" class="kt-input w-full max-w-xl" value="{{ old('info_request_success_title', $infoRequestSuccessTitle ?? 'Uw bericht is verstuurd. We nemen zo snel mogelijk contact met u op.') }}" maxlength="500" placeholder="Uw bericht is verstuurd. We nemen zo snel mogelijk contact met u op.">
                            <p class="text-xs text-muted-foreground mt-1">Grote regel onder het icoon/plaatje (max. 500 tekens)</p>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal align-top">Ondertitel</td>
                        <td class="min-w-48 w-full align-top">
                            <input type="text" name="info_request_success_subtitle" id="info_request_success_subtitle" class="kt-input w-full max-w-xl" value="{{ old('info_request_success_subtitle', $infoRequestSuccessSubtitle ?? 'Er wordt binnenkort contact met u opgenomen.') }}" maxlength="500" placeholder="Er wordt binnenkort contact met u opgenomen.">
                            <p class="text-xs text-muted-foreground mt-1">Kleinere regel eronder (max. 500 tekens)</p>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal align-top">Footertekst</td>
                        <td class="min-w-48 w-full align-top">
                            <input type="text" name="info_request_success_footer" id="info_request_success_footer" class="kt-input w-full max-w-xl" value="{{ old('info_request_success_footer', $infoRequestSuccessFooter ?? 'Uw bericht is succesvol verzonden.') }}" maxlength="500" placeholder="Uw bericht is succesvol verzonden.">
                            <p class="text-xs text-muted-foreground mt-1">Regel onderaan de bedanktmelding (max. 500 tekens)</p>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal align-top">Teksten uitschakelen</td>
                        <td class="min-w-48 w-full align-top">
                            <div class="flex flex-wrap items-center gap-3 w-full min-w-0">
                                <input type="checkbox" name="info_request_success_texts_enabled" id="info_request_success_texts_enabled" class="kt-switch kt-switch-sm shrink-0" value="0" {{ old('info_request_success_texts_enabled', $infoRequestSuccessTextsEnabled ?? '1') === '0' ? 'checked' : '' }}>
                                <span class="text-sm text-muted-foreground min-w-0">Verberg de hoofdtekst, ondertitel en footertekst in de bedanktmelding (alleen icoon/plaatje blijft zichtbaar)</span>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal align-top">Plaatje of icoon</td>
                        <td class="min-w-48 w-full min-w-0 align-top">
                            <p class="text-sm text-muted-foreground mb-3">Upload een afbeelding of kies een icoon. Bij een geüploade afbeelding wordt het icoon niet getoond.</p>
                        <div class="admin-success-media-row flex flex-wrap gap-6 items-start w-full min-w-0 max-w-full">
                            <div class="flex flex-col gap-2 w-full sm:w-auto min-w-0 max-w-full">
                                <span class="text-xs font-medium text-secondary-foreground">Plaatje</span>
                                <div class="flex flex-wrap gap-5 lg:gap-7.5 w-full max-w-full min-w-0 items-start">
                                    <div id="success-image-preview-wrap" class="flex flex-col items-center gap-2 shrink-0 w-full min-w-0 max-w-full {{ (!empty($infoRequestSuccessImage) && Storage::disk('public')->exists($infoRequestSuccessImage)) ? '' : 'hidden' }}">
                                        @php
                                            $successImagePreviewPercent = max(10, min(100, (int) old('info_request_success_image_size_percent', $infoRequestSuccessImageSizePercent ?? 80)));
                                            $previewContext = $infoRequestFormPreviewContext ?? ['width_percent' => 100, 'layout' => 'text_block_half', 'label' => 'Standaard (100% sectiebreedte)'];
                                            $previewWidthPercent = max(30, min(100, (int) ($previewContext['width_percent'] ?? 100)));
                                            $previewLayout = ($previewContext['layout'] ?? 'text_block_half') === 'standalone' ? 'standalone' : 'text_block_half';
                                            $previewContextLabel = (string) ($previewContext['label'] ?? 'Voorbeeld op de website');
                                        @endphp
                                        <div id="admin-success-website-preview-root"
                                             class="admin-success-website-preview admin-success-website-preview--{{ $previewLayout === 'standalone' ? 'standalone' : 'text-block-half' }}"
                                             style="--section-width-pct: {{ $previewWidthPercent }}; --preview-ref-viewport: 90rem;"
                                             data-layout="{{ $previewLayout }}">
                                            @if(!empty($infoRequestFormPreviewContexts) && count($infoRequestFormPreviewContexts) > 1)
                                                <label for="info-request-preview-context-select" class="sr-only">Pagina voor voorbeeld</label>
                                                <select id="info-request-preview-context-select" class="kt-select admin-success-website-preview-context-select w-full max-w-md mb-2">
                                                    @foreach($infoRequestFormPreviewContexts as $ctx)
                                                        <option value="{{ $ctx['id'] }}"
                                                                data-width-percent="{{ (int) $ctx['width_percent'] }}"
                                                                data-layout="{{ $ctx['layout'] }}"
                                                                {{ ($previewContext['id'] ?? '') === ($ctx['id'] ?? '') ? 'selected' : '' }}>{{ $ctx['label'] }}</option>
                                                    @endforeach
                                                </select>
                                            @endif
                                            <p class="admin-success-website-preview-label" id="admin-success-website-preview-label">Voorbeeld op de website · {{ $previewContextLabel }}</p>
                                            <div class="admin-success-website-preview-scaler">
                                                <div class="admin-success-website-preview-stage">
                                                    <div class="admin-success-website-preview-inner">
                                                        <div class="admin-success-website-preview-grid">
                                                            <div class="admin-success-website-preview-grid-spacer" aria-hidden="true"></div>
                                                            <div class="admin-success-website-preview-form-col">
                                                                <div class="admin-success-website-preview-success flex flex-col items-center justify-center text-center">
                                                                    <div class="admin-success-website-preview-image-wrap w-full flex justify-center" aria-hidden="true">
                                                                        <img alt="Success preview" class="admin-success-website-preview-image info-request-success-image h-auto w-auto object-contain max-w-full rounded-lg" style="width: {{ $successImagePreviewPercent }}%;" src="{{ (!empty($infoRequestSuccessImage) && Storage::disk('public')->exists($infoRequestSuccessImage)) ? route('admin.settings.success-image').'?t='.time() : '' }}" id="success-image-preview" title="Klik om groot te bekijken"/>
                                                                    </div>
                                                                    <p id="success-preview-title" class="admin-success-website-preview-text admin-success-website-preview-text--title {{ old('info_request_success_texts_enabled', $infoRequestSuccessTextsEnabled ?? '1') === '0' ? 'hidden' : '' }}">{{ old('info_request_success_title', $infoRequestSuccessTitle ?? '') }}</p>
                                                                    <p id="success-preview-subtitle" class="admin-success-website-preview-text admin-success-website-preview-text--subtitle {{ old('info_request_success_texts_enabled', $infoRequestSuccessTextsEnabled ?? '1') === '0' ? 'hidden' : '' }}">{{ old('info_request_success_subtitle', $infoRequestSuccessSubtitle ?? '') }}</p>
                                                                    <p id="success-preview-footer" class="admin-success-website-preview-text admin-success-website-preview-text--footer {{ old('info_request_success_texts_enabled', $infoRequestSuccessTextsEnabled ?? '1') === '0' ? 'hidden' : '' }}">{{ old('info_request_success_footer', $infoRequestSuccessFooter ?? '') }}</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="kt-btn kt-btn-sm kt-btn-outline kt-btn-icon text-destructive" id="success-image-remove-btn" title="Plaatje verwijderen" aria-label="Plaatje verwijderen">
                                            <i class="ki-filled ki-trash text-lg"></i>
                                        </button>
                                    </div>
                                    <div class="flex flex-col flex-1 min-w-[180px]" id="success-image-upload-wrap">
                                        <div class="flex flex-col items-center justify-center w-full p-5 lg:p-7 border border-input rounded-xl border-dashed bg-muted/30 min-h-[130px] min-w-0 cursor-pointer hover:border-primary transition-colors" id="success-image-upload-area" role="button" tabindex="0" title="Klik of sleep een afbeelding">
                                            <div class="flex flex-col place-items-center place-content-center text-center w-full pointer-events-none">
                                                <i class="ki-filled ki-picture text-2xl text-primary mb-1"></i>
                                                <span class="text-mono text-xs font-medium text-primary">Klik of Sleep &amp; Drop</span>
                                                <span class="text-xs text-muted-foreground mt-0.5">SVG, PNG, JPG, GIF, WebP (max. 5MB)</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <input type="file" name="info_request_success_image_file" id="success-image-input" accept="image/jpeg,image/png,image/jpg,image/gif,image/svg+xml,image/webp" class="hidden">
                                <div class="mt-2 w-full max-w-xs">
                                    <label for="info_request_success_image_size_percent" class="text-xs font-medium text-secondary-foreground block mb-1">Plaatjegrootte</label>
                                    <select name="info_request_success_image_size_percent" id="info_request_success_image_size_percent" class="kt-select w-full sm:w-32 max-w-full">
                                        @foreach([25, 50, 75, 80, 100] as $pct)
                                            <option value="{{ $pct }}" {{ (old('info_request_success_image_size_percent', $infoRequestSuccessImageSizePercent ?? '80')) == (string) $pct ? 'selected' : '' }}>{{ $pct }}%</option>
                                        @endforeach
                                    </select>
                                    <p class="text-xs text-muted-foreground mt-1">Breedte t.o.v. het formulier; schaal op desktopbreedte (1440px) uit de page builder</p>
                                </div>
                                <p id="success-image-error" class="text-sm text-destructive mt-2 hidden" role="alert"></p>
                                {{-- Modal: plaatje groot bekijken --}}
                                <div id="success-image-modal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/60 backdrop-blur-sm" aria-hidden="true" role="dialog" aria-modal="true" aria-label="Afbeelding groot">
                                    <div class="relative max-h-[90vh] max-w-[90vw] p-4" id="success-image-modal-inner">
                                        <button type="button" id="success-image-modal-close" class="absolute -top-2 -right-2 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-background border border-input text-foreground shadow-md hover:bg-muted" aria-label="Sluiten">
                                            <i class="ki-filled ki-cross text-xl"></i>
                                        </button>
                                        <img id="success-image-modal-img" src="" alt="Grote weergave" class="max-h-[85vh] w-auto max-w-full object-contain rounded-lg shadow-xl">
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col gap-2 w-full sm:w-auto min-w-0 max-w-full">
                                <span class="text-xs font-medium text-secondary-foreground">Icoon (als geen plaatje)</span>
                                <div class="flex flex-wrap items-start gap-3 w-full min-w-0">
                                    <div class="flex items-center justify-center w-16 h-16 rounded-lg border border-input bg-muted/30 shrink-0" id="success-icon-preview">
                                        <i class="ki-filled {{ old('info_request_success_icon', $infoRequestSuccessIcon ?? 'ki-check-circle') }} text-3xl text-green-600"></i>
                                    </div>
                                    <select name="info_request_success_icon" id="info_request_success_icon" class="kt-select w-full sm:w-56 max-w-full min-w-0" data-kt-select="true">
                                        @php
                                            $successIcons = [
                                                'ki-filled ki-check-circle' => 'Vink in cirkel',
                                                'ki-filled ki-check' => 'Vinkje',
                                                'ki-filled ki-like' => 'Duim omhoog',
                                                'ki-filled ki-love' => 'Hart (like)',
                                                'ki-filled ki-heart' => 'Hart',
                                                'ki-filled ki-star' => 'Ster',
                                                'ki-filled ki-sms' => 'Bericht',
                                                'ki-filled ki-rocket' => 'Raket',
                                            ];
                                            $currentIcon = old('info_request_success_icon', $infoRequestSuccessIcon ?? 'ki-filled ki-check-circle');
                                        @endphp
                                        @foreach($successIcons as $class => $label)
                                            <option value="{{ $class }}" {{ $currentIcon === $class ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mt-2 w-full max-w-xs">
                                    <label for="info_request_success_icon_size" class="text-xs font-medium text-secondary-foreground block mb-1">Icoon grootte</label>
                                    <select name="info_request_success_icon_size" id="info_request_success_icon_size" class="kt-select w-full sm:w-24 max-w-full">
                                        @foreach([48, 64, 80, 96, 120] as $px)
                                            <option value="{{ $px }}" {{ (old('info_request_success_icon_size', $infoRequestSuccessSize ?? '80')) == (string)$px ? 'selected' : '' }}>{{ $px }}px</option>
                                        @endforeach
                                    </select>
                                    <p class="text-xs text-muted-foreground mt-1">Alleen van toepassing als er geen plaatje is geüpload</p>
                                </div>
                            </div>
                        </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" class="px-4 sm:px-6 pb-6 pt-2 align-top">
                            <div class="flex justify-end">
                                <button type="submit" class="kt-btn kt-btn-primary">
                                    <i class="ki-filled ki-check me-2"></i> Opslaan
                                </button>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
            </div>
        </div>

        </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Logo mode toggle: één logo vs apart light/dark
    const logoModeToggle = document.getElementById('logo-mode-toggle');
    const logoModeInput = document.getElementById('logo-mode-input');
    const logoDarkBlock = document.getElementById('logo-dark-block');
    if (logoModeToggle && logoModeInput && logoDarkBlock) {
        logoModeToggle.addEventListener('change', function() {
            const isLightDark = logoModeToggle.checked;
            logoModeInput.value = isLightDark ? 'light_dark' : 'single';
            logoDarkBlock.classList.toggle('hidden', !isLightDark);
        });
    }

    // Logo upload handling (light mode)
    const logoInput = document.getElementById('logo-input');
    const logoUploadArea = document.getElementById('logo-upload-area');
    const logoUploadLink = document.getElementById('logo-upload-link');
    const logoPreview = document.getElementById('logo-preview');
    
    if (logoInput && logoUploadArea && logoUploadLink && typeof window.bindAdminDropzoneClick === 'function') {
        window.bindAdminDropzoneClick(logoUploadArea, logoInput, logoUploadLink, { clearInputFirst: false });
    }

    if (logoInput && logoUploadArea && logoUploadLink) {
        // Drag and drop
        logoUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            logoUploadArea.classList.add('border-primary');
        });
        
        logoUploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            logoUploadArea.classList.remove('border-primary');
        });
        
        logoUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            logoUploadArea.classList.remove('border-primary');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleLogoFile(files[0]);
            }
        });
        
        // File input change
        logoInput.addEventListener('change', function(e) {
            if (this.files && this.files.length > 0) {
                handleLogoFile(this.files[0]);
            }
        });
        
        function handleLogoFile(file) {
            console.log('handleLogoFile called with file:', file.name, file.type, file.size);
            
            // Validate file type
            const allowedTypes = ['image/svg+xml', 'image/png', 'image/jpeg', 'image/jpg', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                alert('Alleen SVG, PNG, JPG en GIF bestanden zijn toegestaan.');
                logoInput.value = '';
                return;
            }
            
            // Validate file size (max 2MB)
            if (file.size > 2 * 1024 * 1024) {
                alert('Het bestand mag maximaal 2MB groot zijn.');
                logoInput.value = '';
                return;
            }
            
            // Create preview immediately
            const reader = new FileReader();
            reader.onload = function(e) {
                logoPreview.src = e.target.result;
                logoPreview.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
            
            // Upload logo immediately via AJAX
            const formData = new FormData();
            formData.append('logo', file);
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (csrfToken) {
                formData.append('_token', csrfToken.getAttribute('content'));
            } else {
                console.error('CSRF token not found!');
                alert('CSRF token niet gevonden. Ververs de pagina en probeer opnieuw.');
                return;
            }
            
            console.log('Starting logo upload to:', '{{ route("admin.settings.upload-logo") }}');
            fetch('{{ route("admin.settings.upload-logo") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'Network response was not ok');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success && logoPreview) {
                    const logoUrl = data.logo_url + '?t=' + new Date().getTime();
                    logoPreview.src = logoUrl;
                    logoPreview.classList.remove('hidden');
                    const logoLightRemoveBtnEl = document.getElementById('logo-light-remove-btn');
                    if (logoLightRemoveBtnEl) logoLightRemoveBtnEl.classList.remove('hidden');
                    const liveWrap = document.getElementById('settings-live-preview-wrap');
                    if (liveWrap) liveWrap.classList.remove('hidden');
                    const liveLightEl = document.getElementById('settings-live-preview-light');
                    if (liveLightEl) liveLightEl.src = logoUrl;
                    const logoPreviewTop = document.getElementById('logo-preview-top');
                    const logoPlaceholder = document.getElementById('logo-preview-placeholder');
                    if (logoPreviewTop) {
                        logoPreviewTop.src = logoUrl;
                        logoPreviewTop.classList.remove('hidden');
                    }
                    if (logoPlaceholder) logoPlaceholder.classList.add('hidden');
                    console.log('Logo (light) succesvol geüpload.');
                    const sidebarLight = document.querySelectorAll('.logo-light, .default-logo, .small-logo');
                    sidebarLight.forEach(img => { img.src = logoUrl; });
                } else {
                    alert(data.message || 'Er is een fout opgetreden bij het uploaden van het logo.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert(error.message || 'Er is een fout opgetreden bij het uploaden van het logo.');
            });
        }
    }

    // Dark logo upload
    const logoDarkInput = document.getElementById('logo-dark-input');
    const logoDarkUploadArea = document.getElementById('logo-dark-upload-area');
    const logoDarkUploadLink = document.getElementById('logo-dark-upload-link');
    const logoDarkPreview = document.getElementById('logo-dark-preview');
    if (logoDarkInput && logoDarkUploadArea && logoDarkUploadLink && typeof window.bindAdminDropzoneClick === 'function') {
        window.bindAdminDropzoneClick(logoDarkUploadArea, logoDarkInput, logoDarkUploadLink, { clearInputFirst: false });
    }

    if (logoDarkInput && logoDarkUploadArea && logoDarkUploadLink) {
        logoDarkUploadArea.addEventListener('dragover', function(e) { e.preventDefault(); e.stopPropagation(); logoDarkUploadArea.classList.add('border-primary'); });
        logoDarkUploadArea.addEventListener('dragleave', function(e) { e.preventDefault(); e.stopPropagation(); logoDarkUploadArea.classList.remove('border-primary'); });
        logoDarkUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            logoDarkUploadArea.classList.remove('border-primary');
            if (e.dataTransfer.files.length > 0) handleDarkLogoFile(e.dataTransfer.files[0]);
        });
        logoDarkInput.addEventListener('change', function() {
            if (this.files && this.files.length > 0) handleDarkLogoFile(this.files[0]);
        });
        function handleDarkLogoFile(file) {
            const allowedTypes = ['image/svg+xml', 'image/png', 'image/jpeg', 'image/jpg', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                alert('Alleen SVG, PNG, JPG en GIF bestanden zijn toegestaan.');
                logoDarkInput.value = '';
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                alert('Het bestand mag maximaal 2MB groot zijn.');
                logoDarkInput.value = '';
                return;
            }
            if (logoDarkPreview) {
                const reader = new FileReader();
                reader.onload = function(e) { logoDarkPreview.src = e.target.result; logoDarkPreview.classList.remove('hidden'); };
                reader.readAsDataURL(file);
            }
            const formData = new FormData();
            formData.append('logo', file);
            formData.append('logo_type', 'dark');
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (csrfToken) formData.append('_token', csrfToken.getAttribute('content'));
            else { alert('CSRF token niet gevonden. Ververs de pagina.'); return; }
            fetch('{{ route("admin.settings.upload-logo") }}', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(response => response.ok ? response.json() : response.json().then(d => { throw new Error(d.message || 'Upload mislukt'); }))
            .then(data => {
                if (data.success) {
                    const logoUrl = data.logo_url + '?t=' + new Date().getTime();
                    if (logoDarkPreview) { logoDarkPreview.src = logoUrl; logoDarkPreview.classList.remove('hidden'); }
                    const logoDarkRemoveBtn = document.getElementById('logo-dark-remove-btn');
                    if (logoDarkRemoveBtn) logoDarkRemoveBtn.classList.remove('hidden');
                    document.querySelectorAll('.logo-dark').forEach(img => { img.src = logoUrl; });
                    if (logoModeInput) { logoModeInput.value = 'light_dark'; }
                    if (logoModeToggle) { logoModeToggle.checked = true; }
                    if (logoDarkBlock) { logoDarkBlock.classList.remove('hidden'); }
                } else alert(data.message || 'Fout bij uploaden dark logo.');
            })
            .catch(err => { console.error(err); alert(err.message || 'Fout bij uploaden dark logo.'); });
        }
    }

    const logoDarkRemoveBtn = document.getElementById('logo-dark-remove-btn');
    if (logoDarkRemoveBtn) {
        logoDarkRemoveBtn.addEventListener('click', function() {
            const csrf = document.querySelector('meta[name="csrf-token"]');
            if (!csrf) return;
            const fd = new FormData();
            fd.append('_token', csrf.getAttribute('content'));
            fetch('{{ route("admin.settings.remove-logo-dark") }}', {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) return;
                const darkPrev = document.getElementById('logo-dark-preview');
                if (darkPrev) {
                    darkPrev.src = '';
                    darkPrev.classList.add('hidden');
                }
                logoDarkRemoveBtn.classList.add('hidden');
                const darkIn = document.getElementById('logo-dark-input');
                if (darkIn) darkIn.value = '';
                if (logoModeInput) logoModeInput.value = 'single';
                if (logoModeToggle) logoModeToggle.checked = false;
                if (logoDarkBlock) logoDarkBlock.classList.add('hidden');
                var liveLight = document.getElementById('settings-live-preview-light');
                var liveDark = document.getElementById('settings-live-preview-dark');
                if (liveLight && liveDark) {
                    liveDark.src = liveLight.src;
                }
                document.querySelectorAll('.logo-dark').forEach(function(img) {
                    if (liveLight) img.src = liveLight.src;
                });
            });
        });
    }

    const logoLightRemoveBtn = document.getElementById('logo-light-remove-btn');
    if (logoLightRemoveBtn) {
        logoLightRemoveBtn.addEventListener('click', function() {
            const csrf = document.querySelector('meta[name="csrf-token"]');
            if (!csrf) return;
            const fd = new FormData();
            fd.append('_token', csrf.getAttribute('content'));
            fetch('{{ route("admin.settings.remove-logo-light") }}', {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) return;
                const lp = document.getElementById('logo-preview');
                if (lp) {
                    lp.src = '';
                    lp.classList.add('hidden');
                }
                logoLightRemoveBtn.classList.add('hidden');
                const li = document.getElementById('logo-input');
                if (li) li.value = '';
                const liveWrap = document.getElementById('settings-live-preview-wrap');
                if (liveWrap) liveWrap.classList.add('hidden');
                const logoPreviewTop = document.getElementById('logo-preview-top');
                const logoPlaceholder = document.getElementById('logo-preview-placeholder');
                if (logoPreviewTop) {
                    logoPreviewTop.src = '';
                    logoPreviewTop.classList.add('hidden');
                }
                if (logoPlaceholder) logoPlaceholder.classList.remove('hidden');
                document.querySelectorAll('.logo-light, .default-logo, .small-logo').forEach(function(img) {
                    img.removeAttribute('src');
                });
            });
        });
    }
    
    // Favicon upload handling
    const faviconInput = document.getElementById('favicon-input');
    const faviconUploadArea = document.getElementById('favicon-upload-area');
    const faviconUploadLink = document.getElementById('favicon-upload-link');
    const faviconPreview = document.getElementById('favicon-preview');
    
    if (faviconInput && faviconUploadArea && faviconUploadLink && typeof window.bindAdminDropzoneClick === 'function') {
        window.bindAdminDropzoneClick(faviconUploadArea, faviconInput, faviconUploadLink, { clearInputFirst: false });
    }

    if (faviconInput && faviconUploadArea && faviconUploadLink) {
        // Drag and drop
        faviconUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            faviconUploadArea.classList.add('border-primary');
        });
        
        faviconUploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            faviconUploadArea.classList.remove('border-primary');
        });
        
        faviconUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            faviconUploadArea.classList.remove('border-primary');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFaviconFile(files[0]);
            }
        });
        
        // File input change
        faviconInput.addEventListener('change', function(e) {
            if (this.files && this.files.length > 0) {
                handleFaviconFile(this.files[0]);
            }
        });
        
        function handleFaviconFile(file) {
            console.log('handleFaviconFile called with file:', file.name, file.type, file.size);
            
            // Validate file type
            const allowedTypes = ['image/x-icon', 'image/png', 'image/jpeg', 'image/jpg'];
            if (!allowedTypes.includes(file.type)) {
                alert('Alleen ICO, PNG en JPG bestanden zijn toegestaan.');
                faviconInput.value = '';
                return;
            }
            
            // Validate file size (max 2MB)
            if (file.size > 2 * 1024 * 1024) {
                alert('Het bestand mag maximaal 2MB groot zijn.');
                faviconInput.value = '';
                return;
            }
            
            // Create preview immediately
            const reader = new FileReader();
            reader.onload = function(e) {
                faviconPreview.src = e.target.result;
                faviconPreview.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
            
            // Upload favicon immediately via AJAX
            const formData = new FormData();
            formData.append('favicon', file);
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (csrfToken) {
                formData.append('_token', csrfToken.getAttribute('content'));
            } else {
                console.error('CSRF token not found!');
                alert('CSRF token niet gevonden. Ververs de pagina en probeer opnieuw.');
                return;
            }
            
            console.log('Starting favicon upload to:', '{{ route("admin.settings.upload-favicon") }}');
            fetch('{{ route("admin.settings.upload-favicon") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            })
            .then(response => {
                console.log('Favicon upload response status:', response.status);
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'Network response was not ok');
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Favicon upload response:', data);
                if (data.success && faviconPreview) {
                    const faviconUrl = data.favicon_url + '?t=' + new Date().getTime();
                    faviconPreview.src = faviconUrl;
                    faviconPreview.classList.remove('hidden');
                    const faviconPreviewTop = document.getElementById('favicon-preview-top');
                    const faviconPlaceholder = document.getElementById('favicon-preview-placeholder');
                    if (faviconPreviewTop) {
                        faviconPreviewTop.src = faviconUrl;
                        faviconPreviewTop.classList.remove('hidden');
                    }
                    if (faviconPlaceholder) faviconPlaceholder.classList.add('hidden');
                    console.log('Favicon succesvol geüpload.');
                } else {
                    alert(data.message || 'Er is een fout opgetreden bij het uploaden van het favicon.');
                }
            })
            .catch(error => {
                console.error('Error uploading favicon:', error);
                alert(error.message || 'Er is een fout opgetreden bij het uploaden van het favicon.');
                // Keep the preview even if upload fails
            });
        }
    }
    
    // Success image upload (formulier succesbericht)
    const successImageInput = document.getElementById('success-image-input');
    const successImageUploadArea = document.getElementById('success-image-upload-area');
    const successImagePreview = document.getElementById('success-image-preview');
    const successImagePreviewWrap = document.getElementById('success-image-preview-wrap');
    const successImageRemoveBtn = document.getElementById('success-image-remove-btn');
    const successImageErrorEl = document.getElementById('success-image-error');
    
    function showSuccessImageError(msg) {
        if (successImageErrorEl) {
            successImageErrorEl.textContent = msg || 'Upload mislukt.';
            successImageErrorEl.classList.remove('hidden');
        }
        if (typeof alert !== 'undefined') alert(msg || 'Upload mislukt.');
    }
    function clearSuccessImageError() {
        if (successImageErrorEl) {
            successImageErrorEl.textContent = '';
            successImageErrorEl.classList.add('hidden');
        }
    }
    
    function handleSuccessImageFile(file) {
        if (!successImageInput) return;
        clearSuccessImageError();
        const allowed = ['image/svg+xml', 'image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/webp'];
        if (!allowed.includes(file.type)) {
            showSuccessImageError('Ongeldig bestandstype. Alleen SVG, PNG, JPG, GIF en WebP zijn toegestaan.');
            successImageInput.value = '';
            return;
        }
        var maxBytes = 5 * 1024 * 1024;
        if (file.size > maxBytes) {
            showSuccessImageError('Het bestand is te groot. Maximaal 5MB toegestaan. Uw bestand is ' + Math.round(file.size / 1024) + ' KB.');
            successImageInput.value = '';
            return;
        }
        var formData = new FormData();
        formData.append('info_request_success_image', file);
        var csrf = document.querySelector('meta[name="csrf-token"]');
        if (csrf) formData.append('_token', csrf.getAttribute('content'));
        fetch('{{ route("admin.settings.upload-success-image") }}', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function(r) {
            var status = r.status;
            return r.text().then(function(text) {
                var data;
                try { data = text ? JSON.parse(text) : {}; } catch (e) {
                    if (status === 413) throw new Error('Het bestand is te groot. Maximaal 5MB toegestaan.');
                    throw new Error('Upload mislukt. De server gaf een onverwachte reactie (status ' + status + ').');
                }
                if (r.ok) return data;
                var msg = (data.errors && data.errors.info_request_success_image && data.errors.info_request_success_image[0])
                    ? data.errors.info_request_success_image[0]
                    : (data.message || 'Upload mislukt. Controleer het bestand (max. 5MB, JPEG/PNG/GIF/SVG/WebP).');
                throw new Error(msg);
            });
        })
        .then(function(data) {
            if (data.success && successImagePreview && successImagePreviewWrap) {
                successImagePreview.src = (data.image_url || '') + '?t=' + Date.now();
                successImagePreviewWrap.classList.remove('hidden');
                if (typeof applySuccessImagePreviewSize === 'function') {
                    applySuccessImagePreviewSize();
                }
                clearSuccessImageError();
            } else {
                showSuccessImageError(data.message || 'Upload mislukt.');
            }
        })
        .catch(function(err) {
            showSuccessImageError(err && err.message ? err.message : 'Upload mislukt. Controleer het bestand (max. 5MB) of probeer het later opnieuw.');
        });
        successImageInput.value = '';
    }
    
    if (successImageInput && successImageUploadArea && typeof window.bindAdminUploadAreaClick === 'function') {
        window.bindAdminUploadAreaClick(successImageUploadArea, successImageInput, { clearInputFirst: false });
    }

    if (successImageInput && successImageUploadArea) {
        successImageUploadArea.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                if (typeof window.openAdminFilePicker === 'function') {
                    window.openAdminFilePicker(successImageInput, { clearInputFirst: false });
                } else {
                    successImageInput.click();
                }
            }
        });
        successImageUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            successImageUploadArea.classList.add('border-primary');
        });
        successImageUploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            successImageUploadArea.classList.remove('border-primary');
        });
        successImageUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            successImageUploadArea.classList.remove('border-primary');
            var files = e.dataTransfer && e.dataTransfer.files;
            if (files && files.length > 0) handleSuccessImageFile(files[0]);
        });
        successImageInput.addEventListener('change', function() {
            if (this.files && this.files.length > 0) handleSuccessImageFile(this.files[0]);
        });
    }
    
    // Modal: plaatje groot bekijken
    var successImageModal = document.getElementById('success-image-modal');
    var successImageModalImg = document.getElementById('success-image-modal-img');
    var successImageModalClose = document.getElementById('success-image-modal-close');
    if (successImagePreview && successImageModal && successImageModalImg) {
        successImagePreview.addEventListener('click', function() {
            var src = this.src || this.getAttribute('src');
            if (!src) return;
            successImageModalImg.src = src;
            successImageModal.classList.remove('hidden');
            successImageModal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        });
    }
    function closeSuccessImageModal() {
        if (successImageModal) {
            successImageModal.classList.add('hidden');
            successImageModal.classList.remove('flex');
            document.body.style.overflow = '';
        }
    }
    if (successImageModalClose) successImageModalClose.addEventListener('click', closeSuccessImageModal);
    if (successImageModal) {
        successImageModal.addEventListener('click', function(e) {
            if (e.target === successImageModal) closeSuccessImageModal();
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && successImageModal && !successImageModal.classList.contains('hidden')) closeSuccessImageModal();
        });
    }
    
    if (successImageRemoveBtn && successImagePreviewWrap) {
        successImageRemoveBtn.addEventListener('click', function() {
            var token = document.querySelector('meta[name="csrf-token"]');
            fetch('{{ route("admin.settings.remove-success-image") }}', {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token ? token.getAttribute('content') : ''
                }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    successImagePreviewWrap.classList.add('hidden');
                    if (successImagePreview) successImagePreview.src = '';
                }
            });
        });
    }
    
    // Icon preview update (formulier succesbericht)
    const successIconSelect = document.getElementById('info_request_success_icon');
    const successIconPreview = document.getElementById('success-icon-preview');
    const successIconSizeSelect = document.getElementById('info_request_success_icon_size');
    const successImageSizeSelect = document.getElementById('info_request_success_image_size_percent');

    function applySuccessImagePreviewSize() {
        if (!successImagePreview || !successImageSizeSelect) return;
        var pct = parseInt(successImageSizeSelect.value, 10);
        if (isNaN(pct)) pct = 80;
        pct = Math.max(10, Math.min(100, pct));
        successImagePreview.style.width = pct + '%';
    }

    if (successImageSizeSelect) {
        successImageSizeSelect.addEventListener('change', applySuccessImagePreviewSize);
        applySuccessImagePreviewSize();
    }

    var successPreviewTitle = document.getElementById('success-preview-title');
    var successPreviewSubtitle = document.getElementById('success-preview-subtitle');
    var successPreviewFooter = document.getElementById('success-preview-footer');
    var successTitleInput = document.getElementById('info_request_success_title');
    var successSubtitleInput = document.getElementById('info_request_success_subtitle');
    var successFooterInput = document.getElementById('info_request_success_footer');
    var successTextsToggle = document.getElementById('info_request_success_texts_enabled');

    function applySuccessPreviewTexts() {
        var textsEnabled = !successTextsToggle || !successTextsToggle.checked;
        [successPreviewTitle, successPreviewSubtitle, successPreviewFooter].forEach(function(el) {
            if (el) el.classList.toggle('hidden', !textsEnabled);
        });
        if (textsEnabled) {
            if (successPreviewTitle && successTitleInput) successPreviewTitle.textContent = successTitleInput.value;
            if (successPreviewSubtitle && successSubtitleInput) successPreviewSubtitle.textContent = successSubtitleInput.value;
            if (successPreviewFooter && successFooterInput) successPreviewFooter.textContent = successFooterInput.value;
        }
    }

    [successTitleInput, successSubtitleInput, successFooterInput].forEach(function(input) {
        if (input) input.addEventListener('input', applySuccessPreviewTexts);
    });
    if (successTextsToggle) successTextsToggle.addEventListener('change', applySuccessPreviewTexts);
    applySuccessPreviewTexts();

    var previewContextRoot = document.getElementById('admin-success-website-preview-root');
    var previewContextSelect = document.getElementById('info-request-preview-context-select');
    var previewContextLabel = document.getElementById('admin-success-website-preview-label');

    function applyPreviewContextFromSelect() {
        if (!previewContextRoot || !previewContextSelect) return;
        var opt = previewContextSelect.options[previewContextSelect.selectedIndex];
        if (!opt) return;
        var widthPct = parseInt(opt.getAttribute('data-width-percent') || '100', 10);
        if (isNaN(widthPct)) widthPct = 100;
        widthPct = Math.max(30, Math.min(100, widthPct));
        var layout = opt.getAttribute('data-layout') || 'text_block_half';
        previewContextRoot.style.setProperty('--section-width-pct', String(widthPct));
        previewContextRoot.setAttribute('data-layout', layout);
        previewContextRoot.classList.toggle('admin-success-website-preview--standalone', layout === 'standalone');
        previewContextRoot.classList.toggle('admin-success-website-preview--text-block-half', layout !== 'standalone');
        if (previewContextLabel) {
            previewContextLabel.textContent = 'Voorbeeld op de website · ' + (opt.textContent || '');
        }
    }

    if (previewContextSelect) {
        previewContextSelect.addEventListener('change', applyPreviewContextFromSelect);
    }

    function applySuccessIconPreviewSize() {
        if (!successIconPreview || !successIconSizeSelect) return;
        var px = parseInt(successIconSizeSelect.value, 10);
        if (isNaN(px)) px = 80;
        successIconPreview.style.width = px + 'px';
        successIconPreview.style.height = px + 'px';
    }

    if (successIconSizeSelect) {
        successIconSizeSelect.addEventListener('change', applySuccessIconPreviewSize);
        applySuccessIconPreviewSize();
    }

    if (successIconSelect && successIconPreview) {
        const iconEl = successIconPreview.querySelector('i');
        if (iconEl) {
            successIconSelect.addEventListener('change', function() {
                iconEl.className = this.value + ' text-3xl text-green-600';
            });
        }
    }
    
    // Logo size change handler - save immediately on change
    const logoSizeSelect = document.getElementById('logo_size');
    if (logoSizeSelect) {
        logoSizeSelect.addEventListener('change', function() {
            const logoSize = this.value;
            console.log('Logo size changed to:', logoSize);
            
            const formData = new FormData();
            formData.append('logo_size', logoSize);
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (csrfToken) {
                formData.append('_token', csrfToken.getAttribute('content'));
            } else {
                console.error('CSRF token not found!');
                return;
            }
            
            fetch('{{ route("admin.settings.logo-size.update") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'Network response was not ok');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    console.log('Logo grootte succesvol bijgewerkt.');
                    
                    // Update sidebar logo height immediately
                    const sidebarLogos = document.querySelectorAll('.default-logo, .small-logo');
                    sidebarLogos.forEach(img => {
                        img.style.height = data.logo_size + 'px';
                    });
                } else {
                    alert(data.message || 'Er is een fout opgetreden bij het bijwerken van de logo grootte.');
                }
            })
            .catch(error => {
                console.error('Error updating logo size:', error);
                alert(error.message || 'Er is een fout opgetreden bij het bijwerken van de logo grootte.');
            });
        });
    }
});
</script>
@endpush
