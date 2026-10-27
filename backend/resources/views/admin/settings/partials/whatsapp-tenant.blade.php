{{--
  WhatsApp (tenant): widget / click-to-chat / contactnummers.
  Verwacht: $whatsappSettings, $whatsappPlatformConfigured
  Optioneel: $whatsappCardCollapsed (bool), $whatsappReturnTo (url), $whatsappFormIdPrefix (string)
--}}
@php
    $whatsappCardCollapsed = $whatsappCardCollapsed ?? true;
    $whatsappReturnTo = $whatsappReturnTo ?? null;
    $prefix = $whatsappFormIdPrefix ?? 'wa';
    $cardClass = 'kt-card min-w-full settings-collapsible-card'.($whatsappCardCollapsed ? ' settings-collapsible-card--collapsed' : '');
@endphp
<div class="{{ $cardClass }}" id="whatsapp">
    @include('admin.settings.partials.collapsible-header', ['titleHtml' => '<i class="ki-filled ki-whatsapp me-2"></i> ' . __('WhatsApp (tenant)')])
    <div class="settings-collapsible-body">
        <div class="kt-card-table kt-scrollable-x-auto pb-3">
            <div class="px-5 pb-3 text-xs text-muted-foreground" style="padding-top: 10px;">
                {{ __('Widget en optionele click-to-chat voor deze tenant.') }}
                {!! __('De :api (token / Phone Number ID) configureer je platform-breed onder :link.', [
                    'api' => '<strong>WhatsApp Business API</strong>',
                    'link' => '<a href="'.e(route('admin.settings.general.index')).'#whatsapp" class="underline">'.e(__('Algemene configuraties')).'</a>',
                ]) !!}
                @if(!empty($whatsappPlatformConfigured))
                    <span class="text-emerald-700 dark:text-emerald-300">{{ __('Platform-API is actief — boekingsberichten gaan automatisch via Cloud API; click-to-chat is uitgeschakeld.') }}</span>
                @endif
            </div>
            <form method="POST" action="{{ route('admin.settings.whatsapp.update') }}" data-validate="true">
                @csrf
                @if($whatsappReturnTo)
                    <input type="hidden" name="return_to" value="{{ $whatsappReturnTo }}">
                @endif
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                    <tr>
                        <td colspan="2" class="pt-2">
                            <div class="rounded-lg border border-border bg-background px-4 py-3">
                                <div class="text-sm font-semibold text-secondary-foreground">{{ __('WhatsApp Direct (zonder Business API)') }}</div>
                                <div class="text-xs text-muted-foreground mt-1">{{ __('Alleen relevant als de platform Business API niet is geconfigureerd.') }}</div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">{{ __('WhatsApp Direct inschakelen') }}</td>
                        <td class="min-w-48 w-full">
                            <label class="inline-flex items-center gap-2">
                                <input type="hidden" name="WHATSAPP_CLICK_TO_CHAT_ENABLED" value="0">
                                <input type="checkbox"
                                       class="kt-checkbox"
                                       id="{{ $prefix }}-WHATSAPP_CLICK_TO_CHAT_ENABLED"
                                       name="WHATSAPP_CLICK_TO_CHAT_ENABLED"
                                       value="1"
                                       {{ old('WHATSAPP_CLICK_TO_CHAT_ENABLED', $whatsappSettings['WHATSAPP_CLICK_TO_CHAT_ENABLED'] ?? '0') === '1' ? 'checked' : '' }}
                                       @if(!empty($whatsappPlatformConfigured)) disabled @endif>
                                <span class="text-sm text-secondary-foreground">{{ __('Fallback: boekingsknop opent WhatsApp (alleen zonder Business API)') }}</span>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">{{ __('WhatsApp Nummer (zonder Business API)') }}</td>
                        <td class="min-w-48 w-full">
                            <div class="relative">
                                <input type="tel"
                                       class="kt-input @error('WHATSAPP_CLICK_TO_CHAT_NUMBER') border-destructive @enderror"
                                       id="{{ $prefix }}-WHATSAPP_CLICK_TO_CHAT_NUMBER"
                                       name="WHATSAPP_CLICK_TO_CHAT_NUMBER"
                                       value="{{ old('WHATSAPP_CLICK_TO_CHAT_NUMBER', $whatsappSettings['WHATSAPP_CLICK_TO_CHAT_NUMBER'] ?? '') }}"
                                       placeholder="{{ __('0612345678 of +31612345678') }}"
                                       autocomplete="tel">
                            </div>
                            <div class="text-xs text-muted-foreground mt-1">{{ __('Nummer voor wa.me-fallback (zonder Business API).') }}</div>
                            @error('WHATSAPP_CLICK_TO_CHAT_NUMBER')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" class="pt-4">
                            <div class="rounded-lg border border-border bg-background px-4 py-3">
                                <div class="text-sm font-semibold text-secondary-foreground">{{ __('Boekingsmelding naar bedrijf') }}</div>
                                <div class="text-xs text-muted-foreground mt-1">
                                    {{ __('Ontvangernummer voor WhatsApp bij nieuwe boekingen (template “dispatch”).') }}
                                    {!! __('Aan/uit staat onder :general → WhatsApp Business API → :link.', [
                                        'general' => e(__('Algemene configuraties')),
                                        'link' => '<a href="'.e(route('admin.settings.general.index')).'#whatsapp-booking-templates" class="underline">'.e(__('Boekingssjablonen')).'</a>',
                                    ]) !!}
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">{{ __('WhatsApp-nummer bedrijf') }}</td>
                        <td class="min-w-48 w-full">
                            <div class="relative">
                                <input type="tel"
                                       class="kt-input @error('WHATSAPP_COMPANY_BOOKING_NOTIFY_NUMBER') border-destructive @enderror"
                                       id="{{ $prefix }}-WHATSAPP_COMPANY_BOOKING_NOTIFY_NUMBER"
                                       name="WHATSAPP_COMPANY_BOOKING_NOTIFY_NUMBER"
                                       value="{{ old('WHATSAPP_COMPANY_BOOKING_NOTIFY_NUMBER', $whatsappSettings['WHATSAPP_COMPANY_BOOKING_NOTIFY_NUMBER'] ?? '') }}"
                                       placeholder="{{ __('0612345678 of +31612345678') }}"
                                       autocomplete="tel">
                            </div>
                            <div class="text-xs text-muted-foreground mt-1">{{ __('Leeg = geen bericht naar het bedrijf, ook als de platform-optie aan staat.') }}</div>
                            @error('WHATSAPP_COMPANY_BOOKING_NOTIFY_NUMBER')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" class="pt-4">
                            <div class="rounded-lg border border-border bg-background px-4 py-3">
                                <div class="text-sm font-semibold text-secondary-foreground">{{ __('Frontend WhatsApp Widget') }}</div>
                                <div class="text-xs text-muted-foreground mt-1">{{ __('Toont rechtsonder op de frontend een WhatsApp-icoon (contactnummer van deze tenant).') }}</div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Widget tonen op frontend') }}</td>
                        <td class="min-w-48 w-full">
                            <label class="inline-flex items-center gap-2">
                                <input type="hidden" name="WHATSAPP_WIDGET_ENABLED" value="0">
                                <input type="checkbox"
                                       class="kt-checkbox"
                                       id="{{ $prefix }}-WHATSAPP_WIDGET_ENABLED"
                                       name="WHATSAPP_WIDGET_ENABLED"
                                       value="1"
                                       {{ old('WHATSAPP_WIDGET_ENABLED', $whatsappSettings['WHATSAPP_WIDGET_ENABLED'] ?? '0') === '1' ? 'checked' : '' }}>
                                <span class="text-sm text-secondary-foreground">{{ __('WhatsApp widget rechtsonder weergeven') }}</span>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Widget telefoonnummer') }}</td>
                        <td class="min-w-48 w-full">
                            <div class="relative">
                                <input type="tel"
                                       class="kt-input @error('WHATSAPP_WIDGET_PHONE') border-destructive @enderror"
                                       id="{{ $prefix }}-WHATSAPP_WIDGET_PHONE"
                                       name="WHATSAPP_WIDGET_PHONE"
                                       value="{{ old('WHATSAPP_WIDGET_PHONE', $whatsappSettings['WHATSAPP_WIDGET_PHONE'] ?? '') }}"
                                       placeholder="{{ __('0612345678 of +31612345678') }}"
                                       autocomplete="tel">
                            </div>
                            <div class="text-xs text-muted-foreground mt-1">{{ __('Het contactnummer dat bezoekers openen vanaf de website.') }}</div>
                            @error('WHATSAPP_WIDGET_PHONE')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal align-top">{{ __('Widget standaardbericht') }}</td>
                        <td class="min-w-48 w-full">
                            <div class="relative">
                                <textarea rows="3"
                                          class="kt-input pt-1 @error('WHATSAPP_WIDGET_DEFAULT_MESSAGE') border-destructive @enderror"
                                          id="{{ $prefix }}-WHATSAPP_WIDGET_DEFAULT_MESSAGE"
                                          name="WHATSAPP_WIDGET_DEFAULT_MESSAGE"
                                          placeholder="Hallo, ik heb een vraag over jullie diensten.">{{ old('WHATSAPP_WIDGET_DEFAULT_MESSAGE', $whatsappSettings['WHATSAPP_WIDGET_DEFAULT_MESSAGE'] ?? 'Hallo, ik heb een vraag over jullie diensten.') }}</textarea>
                            </div>
                            @error('WHATSAPP_WIDGET_DEFAULT_MESSAGE')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                        </td>
                    </tr>
                </table>
                <div class="kt-card-footer flex flex-wrap justify-between items-center gap-3 pt-5 border-t border-border">
                    @if(!empty($whatsappBackUrl))
                        <a href="{{ $whatsappBackUrl }}" class="kt-btn kt-btn-outline inline-flex items-center gap-2">
                            <i class="ki-filled ki-arrow-left text-base" aria-hidden="true"></i>
                            {{ __('Terug naar Tenant configureren') }}
                        </a>
                    @else
                        <span class="hidden sm:block" aria-hidden="true"></span>
                    @endif
                    <button type="submit" class="kt-btn kt-btn-primary">
                        <i class="ki-filled ki-check me-2"></i> {{ __('WhatsApp tenant opslaan') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
