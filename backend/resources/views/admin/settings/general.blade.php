@extends('admin.layouts.app')

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

        @if(session('error'))
            <div class="kt-alert kt-alert-danger mb-5">
                <div class="kt-alert-content">
                    {{ session('error') }}
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
                    <img alt="Favicon" class="w-12 h-12 object-contain" src="{{ route('admin.settings.favicon') }}?t={{ time() }}" id="favicon-preview-top" />
                @else
                    <span class="text-sm text-muted-foreground italic py-2" id="favicon-preview-placeholder">Geen favicon geüpload</span>
                    <img alt="Favicon" class="w-12 h-12 object-contain hidden" src="" id="favicon-preview-top" />
                @endif
            </div>
        </div>

        <form action="{{ route('admin.settings.general.update') }}" method="POST" enctype="multipart/form-data" id="general-settings-form">
            @csrf
        <div class="kt-card mb-8">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Logo & Favicon</h3>
            </div>
            <div class="kt-card-content">
                    <input type="hidden" name="logo_mode" id="logo-mode-input" value="{{ old('logo_mode', $logoMode ?? 'single') }}">

                    <!-- Logo mode: één logo of apart light/dark -->
                    <div class="mb-6">
                        <label class="kt-form-label mb-2">Logo</label>
                        <p class="text-sm text-muted-foreground mb-3">Het logo wordt gebruikt in de admin-sidebar en op de frontend (header en footer).</p>
                        @php
                            $hasLogo = $logo && Storage::disk('public')->exists($logo);
                            $hasLogoDark = !empty($logoDark) && Storage::disk('public')->exists($logoDark);
                            $useLightDark = ($logoMode ?? 'single') === 'light_dark';
                            $logoLightUrl = $hasLogo ? route('admin.settings.logo') : null;
                            $logoDarkUrl = ($hasLogo && $useLightDark && $hasLogoDark) ? route('admin.settings.logo-dark') : $logoLightUrl;
                        @endphp
                        <div class="flex flex-col gap-2 mb-4">
                            <span class="text-sm text-muted-foreground">Eén logo voor beide modi</span>
                            <div class="flex flex-wrap items-center gap-3">
                                <input type="checkbox" id="logo-mode-toggle" class="kt-switch kt-switch-sm" {{ old('logo_mode', $logoMode ?? 'single') === 'light_dark' ? 'checked' : '' }} aria-label="Apart logo voor light en dark mode">
                                <span class="text-sm text-muted-foreground">Apart logo voor light en dark mode</span>
                            </div>
                        </div>

                        @if($logoLightUrl)
                        <p class="text-sm font-medium text-muted-foreground mb-2">Zo ziet het logo eruit in de sidebar en op de frontend (wisselt mee met light/dark modus)</p>
                        <div class="flex items-center gap-3 mb-4 p-3 rounded-lg border border-border bg-muted/30">
                            <img alt="Logo light" class="logo-light w-auto max-w-[140px] object-contain dark:hidden" style="height: {{ $logoSize }}px;" src="{{ $logoLightUrl }}" id="settings-live-preview-light" />
                            <img alt="Logo dark" class="logo-dark w-auto max-w-[140px] object-contain hidden dark:block" style="height: {{ $logoSize }}px;" src="{{ $logoDarkUrl }}" id="settings-live-preview-dark" />
                        </div>
                        @endif

                        <!-- Light mode logo (standaard) -->
                        <p class="text-sm font-medium text-muted-foreground mb-2">Light mode (standaard)</p>
                        <div class="flex flex-wrap sm:flex-nowrap gap-5 lg:gap-7.5 max-w-96 w-full mb-4">
                            @if($logo && Storage::disk('public')->exists($logo))
                                <img alt="Logo Preview" class="h-[35px] mt-2" 
                                     src="{{ route('admin.settings.logo') }}" 
                                     id="logo-preview"/>
                            @else
                                <img alt="Logo Preview" class="h-[35px] mt-2 hidden" 
                                     src="" 
                                     id="logo-preview"/>
                            @endif
                            <div class="flex bg-center w-full p-5 lg:p-7 bg-no-repeat bg-[length:550px] border border-input rounded-xl border-dashed branding-bg" id="logo-upload-area">
                                <div class="flex flex-col place-items-center place-content-center text-center rounded-xl w-full">
                                    <div class="flex items-center mb-2.5">
                                        <div class="relative size-11 shrink-0">
                                            <svg class="w-full h-full stroke-primary/10 fill-light" fill="none" height="48" viewbox="0 0 44 48" width="44" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M16 2.4641C19.7128 0.320509 24.2872 0.320508 28 2.4641L37.6506 8.0359C41.3634 10.1795 43.6506 14.141 43.6506 18.4282V29.5718C43.6506 33.859 41.3634 37.8205 37.6506 39.9641L28 45.5359C24.2872 47.6795 19.7128 47.6795 16 45.5359L6.34937 39.9641C2.63655 37.8205 0.349365 33.859 0.349365 29.5718V18.4282C0.349365 14.141 2.63655 10.1795 6.34937 8.0359L16 2.4641Z" fill=""></path>
                                                <path d="M16.25 2.89711C19.8081 0.842838 24.1919 0.842837 27.75 2.89711L37.4006 8.46891C40.9587 10.5232 43.1506 14.3196 43.1506 18.4282V29.5718C43.1506 33.6804 40.9587 37.4768 37.4006 39.5311L27.75 45.1029C24.1919 47.1572 19.8081 47.1572 16.25 45.1029L6.59937 39.5311C3.04125 37.4768 0.849365 33.6803 0.849365 29.5718V18.4282C0.849365 14.3196 3.04125 10.5232 6.59937 8.46891L16.25 2.89711Z" stroke="" stroke-opacity="0.2"></path>
                                            </svg>
                                            <div class="absolute leading-none left-2/4 top-2/4 -translate-y-2/4 -translate-x-2/4">
                                                <i class="ki-filled ki-picture text-xl ps-px text-primary"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <a class="text-mono text-xs font-medium hover:text-primary mb-px cursor-pointer" id="logo-upload-link">
                                        Klik of Sleep & Drop
                                    </a>
                                    <span class="text-xs text-secondary-foreground text-nowrap">
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

                        <!-- Dark mode logo (alleen zichtbaar bij "Apart logo voor light en dark mode") -->
                        <div id="logo-dark-block" class="{{ ($logoMode ?? 'single') === 'light_dark' ? '' : 'hidden' }}">
                            <p class="text-sm font-medium text-muted-foreground mb-2">Dark mode</p>
                            <div class="flex flex-wrap sm:flex-nowrap gap-5 lg:gap-7.5 max-w-96 w-full">
                                @if(!empty($logoDark) && Storage::disk('public')->exists($logoDark))
                                    <img alt="Dark logo preview" class="h-[35px] mt-2" 
                                         src="{{ route('admin.settings.logo-dark') }}?t={{ time() }}" 
                                         id="logo-dark-preview"/>
                                @else
                                    <img alt="Dark logo preview" class="h-[35px] mt-2 hidden" 
                                         src="" 
                                         id="logo-dark-preview"/>
                                @endif
                                <div class="flex bg-center w-full p-5 lg:p-7 bg-no-repeat bg-[length:550px] border border-input rounded-xl border-dashed branding-bg" id="logo-dark-upload-area">
                                    <div class="flex flex-col place-items-center place-content-center text-center rounded-xl w-full">
                                        <div class="flex items-center mb-2.5">
                                            <div class="relative size-11 shrink-0">
                                                <svg class="w-full h-full stroke-primary/10 fill-light" fill="none" height="48" viewbox="0 0 44 48" width="44" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M16 2.4641C19.7128 0.320509 24.2872 0.320508 28 2.4641L37.6506 8.0359C41.3634 10.1795 43.6506 14.141 43.6506 18.4282V29.5718C43.6506 33.859 41.3634 37.8205 37.6506 39.9641L28 45.5359C24.2872 47.6795 19.7128 47.6795 16 45.5359L6.34937 39.9641C2.63655 37.8205 0.349365 33.859 0.349365 29.5718V18.4282C0.349365 14.141 2.63655 10.1795 6.34937 8.0359L16 2.4641Z" fill=""></path>
                                                    <path d="M16.25 2.89711C19.8081 0.842838 24.1919 0.842837 27.75 2.89711L37.4006 8.46891C40.9587 10.5232 43.1506 14.3196 43.1506 18.4282V29.5718C43.1506 33.6804 40.9587 37.4768 37.4006 39.5311L27.75 45.1029C24.1919 47.1572 19.8081 47.1572 16.25 45.1029L6.59937 39.5311C3.04125 37.4768 0.849365 33.6803 0.849365 29.5718V18.4282C0.849365 14.3196 3.04125 10.5232 6.59937 8.46891L16.25 2.89711Z" stroke="" stroke-opacity="0.2"></path>
                                                </svg>
                                                <div class="absolute leading-none left-2/4 top-2/4 -translate-y-2/4 -translate-x-2/4">
                                                    <i class="ki-filled ki-picture text-xl ps-px text-primary"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <a class="text-mono text-xs font-medium hover:text-primary mb-px cursor-pointer" id="logo-dark-upload-link">
                                            Klik of Sleep & Drop
                                        </a>
                                        <span class="text-xs text-secondary-foreground text-nowrap">
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

                    <!-- Logo Size -->
                    <div class="mb-6">
                        <label for="logo_size" class="kt-form-label mb-2">Logo grootte (px)</label>
                        <p class="text-sm text-muted-foreground mb-3">Stel de hoogte van het logo in pixels in.</p>
                        <select name="logo_size" id="logo_size" class="kt-input" required>
                            @foreach(range(26, 50, 2) as $size)
                                <option value="{{ $size }}" {{ $logoSize == (string)$size ? 'selected' : '' }}>{{ $size }}px</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Favicon Upload -->
                    <div class="mb-6">
                        <label class="kt-form-label mb-2">Favicon</label>
                        <p class="text-sm text-muted-foreground mb-3">Het favicon wordt gebruikt in de browser tab.</p>
                        
                        <div class="flex flex-wrap sm:flex-nowrap gap-5 lg:gap-7.5 max-w-96 w-full">
                            @if($favicon && Storage::disk('public')->exists($favicon))
                                <img alt="Favicon Preview" class="w-16 h-16 mt-2 object-contain" 
                                     src="{{ route('admin.settings.favicon') }}" 
                                     id="favicon-preview"/>
                            @else
                                <img alt="Favicon Preview" class="w-16 h-16 mt-2 object-contain hidden" 
                                     src="" 
                                     id="favicon-preview"/>
                            @endif
                            <div class="flex bg-center w-full p-5 lg:p-7 bg-no-repeat bg-[length:550px] border border-input rounded-xl border-dashed branding-bg" id="favicon-upload-area">
                                <div class="flex flex-col place-items-center place-content-center text-center rounded-xl w-full">
                                    <div class="flex items-center mb-2.5">
                                        <div class="relative size-11 shrink-0">
                                            <svg class="w-full h-full stroke-primary/10 fill-light" fill="none" height="48" viewbox="0 0 44 48" width="44" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M16 2.4641C19.7128 0.320509 24.2872 0.320508 28 2.4641L37.6506 8.0359C41.3634 10.1795 43.6506 14.141 43.6506 18.4282V29.5718C43.6506 33.859 41.3634 37.8205 37.6506 39.9641L28 45.5359C24.2872 47.6795 19.7128 47.6795 16 45.5359L6.34937 39.9641C2.63655 37.8205 0.349365 33.859 0.349365 29.5718V18.4282C0.349365 14.141 2.63655 10.1795 6.34937 8.0359L16 2.4641Z" fill=""></path>
                                                <path d="M16.25 2.89711C19.8081 0.842838 24.1919 0.842837 27.75 2.89711L37.4006 8.46891C40.9587 10.5232 43.1506 14.3196 43.1506 18.4282V29.5718C43.1506 33.6804 40.9587 37.4768 37.4006 39.5311L27.75 45.1029C24.1919 47.1572 19.8081 47.1572 16.25 45.1029L6.59937 39.5311C3.04125 37.4768 0.849365 33.6803 0.849365 29.5718V18.4282C0.849365 14.3196 3.04125 10.5232 6.59937 8.46891L16.25 2.89711Z" stroke="" stroke-opacity="0.2"></path>
                                            </svg>
                                            <div class="absolute leading-none left-2/4 top-2/4 -translate-y-2/4 -translate-x-2/4">
                                                <i class="ki-filled ki-picture text-xl ps-px text-primary"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <a class="text-mono text-xs font-medium hover:text-primary mb-px cursor-pointer" id="favicon-upload-link">
                                        Klik of Sleep & Drop
                                    </a>
                                    <span class="text-xs text-secondary-foreground text-nowrap">
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
                    </div>

            </div>
        </div>
        <!-- Algemene opties. Applicatienaam, omschrijving en Mijn-omgeving-knop staan per module onder Modules Beheer > [module] > Configureren. -->
        <div class="kt-card mb-8">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Algemene opties</h3>
            </div>
            <div class="kt-card-content">
                    <div class="mb-6 flex flex-wrap items-center gap-3">
                        <label class="kt-form-label mb-0">AI-assistent tonen</label>
                        <input type="checkbox" name="ai_chat_enabled" id="ai_chat_enabled" class="kt-switch kt-switch-sm" value="1" {{ old('ai_chat_enabled', $aiChatEnabled ?? '0') === '1' ? 'checked' : '' }}>
                        <span class="text-sm text-muted-foreground">Toon de zwevende AI-chatknop op de frontend (alle thema's).</span>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="kt-btn kt-btn-primary">Opslaan</button>
                    </div>
            </div>
        </div>

        <!-- Formulier succesbericht (informatieaanvraag / contactformulier op de website) -->
        <div class="kt-card mb-8">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Formulier succesbericht</h3>
            </div>
            <div class="kt-card-content">
                <p class="text-sm text-muted-foreground mb-4">Teksten en icoon of plaatje die bezoekers zien nadat ze een formulier succesvol hebben verzonden. Geldt voor alle formulieren op de website. Kies een plaatje <em>of</em> een icoon; bij een geüploade plaatje heeft het icoon geen effect.</p>
                    <div class="mb-4">
                        <label for="info_request_success_title" class="kt-form-label mb-2">Hoofdtekst</label>
                        <input type="text" name="info_request_success_title" id="info_request_success_title" class="kt-input w-full max-w-xl" value="{{ old('info_request_success_title', $infoRequestSuccessTitle ?? 'Uw bericht is verstuurd. We nemen zo snel mogelijk contact met u op.') }}" maxlength="500" placeholder="Uw bericht is verstuurd. We nemen zo snel mogelijk contact met u op.">
                        <p class="text-xs text-muted-foreground mt-1">Grote regel onder het icoon/plaatje (max. 500 tekens)</p>
                    </div>
                    <div class="mb-4">
                        <label for="info_request_success_subtitle" class="kt-form-label mb-2">Ondertitel</label>
                        <input type="text" name="info_request_success_subtitle" id="info_request_success_subtitle" class="kt-input w-full max-w-xl" value="{{ old('info_request_success_subtitle', $infoRequestSuccessSubtitle ?? 'Er wordt binnenkort contact met u opgenomen.') }}" maxlength="500" placeholder="Er wordt binnenkort contact met u opgenomen.">
                        <p class="text-xs text-muted-foreground mt-1">Kleinere regel eronder (max. 500 tekens)</p>
                    </div>
                    <div class="mb-4">
                        <label for="info_request_success_footer" class="kt-form-label mb-2">Footertekst</label>
                        <input type="text" name="info_request_success_footer" id="info_request_success_footer" class="kt-input w-full max-w-xl" value="{{ old('info_request_success_footer', $infoRequestSuccessFooter ?? 'Uw bericht is succesvol verzonden.') }}" maxlength="500" placeholder="Uw bericht is succesvol verzonden.">
                        <p class="text-xs text-muted-foreground mt-1">Regel onderaan de bedanktmelding (max. 500 tekens)</p>
                    </div>
                    <div class="mb-4">
                        <div class="flex items-center gap-3 flex-nowrap">
                            <input type="checkbox" name="info_request_success_texts_enabled" id="info_request_success_texts_enabled" class="kt-switch kt-switch-sm shrink-0" value="0" {{ old('info_request_success_texts_enabled', $infoRequestSuccessTextsEnabled ?? '1') === '0' ? 'checked' : '' }}>
                            <label for="info_request_success_texts_enabled" class="kt-form-label mb-0">Teksten uitschakelen</label>
                        </div>
                        <p class="text-sm text-muted-foreground mt-1">Verberg de hoofdtekst, ondertitel en footertekst in de bedanktmelding (alleen icoon/plaatje blijft zichtbaar)</p>
                    </div>

                    <div class="mb-4">
                        <label class="kt-form-label mb-2">Plaatje of icoon</label>
                        <p class="text-sm text-muted-foreground mb-3">Upload een afbeelding of kies een icoon. Bij een geüploade afbeelding wordt het icoon niet getoond.</p>
                        <div class="flex flex-wrap gap-6 items-start">
                            <div class="flex flex-col gap-2">
                                <span class="text-xs font-medium text-secondary-foreground">Plaatje</span>
                                <div class="flex flex-wrap sm:flex-nowrap gap-5 lg:gap-7.5 max-w-96 w-full items-start">
                                    <div id="success-image-preview-wrap" class="flex flex-col items-center {{ (!empty($infoRequestSuccessImage) && Storage::disk('public')->exists($infoRequestSuccessImage)) ? '' : 'hidden' }}">
                                        <img alt="Success preview" class="h-[200px] w-auto object-contain rounded-lg border border-input shrink-0 cursor-pointer hover:opacity-90 transition-opacity" src="{{ (!empty($infoRequestSuccessImage) && Storage::disk('public')->exists($infoRequestSuccessImage)) ? route('admin.settings.success-image').'?t='.time() : '' }}" id="success-image-preview" title="Klik om groot te bekijken"/>
                                        <button type="button" class="kt-btn kt-btn-sm kt-btn-outline kt-btn-icon text-destructive mt-2" id="success-image-remove-btn" title="Plaatje verwijderen" aria-label="Plaatje verwijderen">
                                            <i class="ki-filled ki-trash text-lg"></i>
                                        </button>
                                    </div>
                                    <div class="flex flex-col flex-1 min-w-[180px]" id="success-image-upload-wrap">
                                        <div class="flex bg-center w-full p-5 lg:p-7 bg-no-repeat bg-[length:550px] border border-input rounded-xl border-dashed branding-bg cursor-pointer hover:border-primary transition-colors" id="success-image-upload-area" role="button" tabindex="0" title="Klik of sleep een afbeelding">
                                            <div class="flex flex-col place-items-center place-content-center text-center rounded-xl w-full pointer-events-none">
                                                <i class="ki-filled ki-picture text-2xl text-primary mb-1"></i>
                                                <span class="text-mono text-xs font-medium text-primary">Klik of Sleep & Drop</span>
                                                <span class="text-xs text-secondary-foreground mt-0.5">SVG, PNG, JPG, GIF, WebP (max. 5MB)</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <input type="file" name="info_request_success_image_file" id="success-image-input" accept="image/jpeg,image/png,image/jpg,image/gif,image/svg+xml,image/webp" class="hidden">
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
                            <div class="flex flex-col gap-2">
                                <span class="text-xs font-medium text-secondary-foreground">Icoon (als geen plaatje)</span>
                                <div class="flex items-start gap-3">
                                    <div class="flex items-center justify-center w-16 h-16 rounded-lg border border-input bg-muted/30 shrink-0" id="success-icon-preview">
                                        <i class="ki-filled {{ old('info_request_success_icon', $infoRequestSuccessIcon ?? 'ki-check-circle') }} text-3xl text-green-600"></i>
                                    </div>
                                    <select name="info_request_success_icon" id="info_request_success_icon" class="kt-select w-56" data-kt-select="true">
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
                            </div>
                            <div class="flex flex-col gap-2">
                                <span class="text-xs font-medium text-secondary-foreground">Grootte</span>
                                <select name="info_request_success_icon_size" id="info_request_success_icon_size" class="kt-select w-24">
                                    @foreach([48, 64, 80, 96, 120] as $px)
                                        <option value="{{ $px }}" {{ (old('info_request_success_icon_size', $infoRequestSuccessSize ?? '80')) == (string)$px ? 'selected' : '' }}>{{ $px }}px</option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-muted-foreground">Grootte icoon of plaatje</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="kt-btn kt-btn-primary">Opslaan</button>
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
    
    if (logoInput && logoUploadArea && logoUploadLink) {
        // Click to upload
        logoUploadLink.addEventListener('click', function(e) {
            e.preventDefault();
            logoInput.click();
        });
        
        logoUploadArea.addEventListener('click', function(e) {
            if (e.target === logoUploadArea || e.target.closest('#logo-upload-area')) {
                logoInput.click();
            }
        });
        
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
    if (logoDarkInput && logoDarkUploadArea && logoDarkUploadLink) {
        logoDarkUploadLink.addEventListener('click', function(e) { e.preventDefault(); logoDarkInput.click(); });
        logoDarkUploadArea.addEventListener('click', function(e) {
            if (e.target === logoDarkUploadArea || e.target.closest('#logo-dark-upload-area')) logoDarkInput.click();
        });
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
                    document.querySelectorAll('.logo-dark').forEach(img => { img.src = logoUrl; });
                    if (logoModeInput) { logoModeInput.value = 'light_dark'; }
                    if (logoModeToggle) { logoModeToggle.checked = true; }
                    if (logoDarkBlock) { logoDarkBlock.classList.remove('hidden'); }
                } else alert(data.message || 'Fout bij uploaden dark logo.');
            })
            .catch(err => { console.error(err); alert(err.message || 'Fout bij uploaden dark logo.'); });
        }
    }
    
    // Favicon upload handling
    const faviconInput = document.getElementById('favicon-input');
    const faviconUploadArea = document.getElementById('favicon-upload-area');
    const faviconUploadLink = document.getElementById('favicon-upload-link');
    const faviconPreview = document.getElementById('favicon-preview');
    
    if (faviconInput && faviconUploadArea && faviconUploadLink) {
        // Click to upload
        faviconUploadLink.addEventListener('click', function(e) {
            e.preventDefault();
            faviconInput.click();
        });
        
        faviconUploadArea.addEventListener('click', function(e) {
            if (e.target === faviconUploadArea || e.target.closest('#favicon-upload-area')) {
                faviconInput.click();
            }
        });
        
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
    
    if (successImageInput && successImageUploadArea) {
        successImageUploadArea.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            successImageInput.click();
        });
        successImageUploadArea.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); successImageInput.click(); }
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
