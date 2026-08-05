{{-- Platform WhatsApp Business API (Configuraties → Algemene configuraties) --}}
<div class="kt-card mb-8 settings-collapsible-card settings-collapsible-card--collapsed" id="whatsapp">
    @include('admin.settings.partials.collapsible-header', ['titleHtml' => '<i class="ki-filled ki-whatsapp me-2"></i> WhatsApp Business API (platform)'])
    <div class="settings-collapsible-body">
        <div class="kt-card-table kt-scrollable-x-auto pb-3">
            <div class="px-5 pb-3 text-xs text-muted-foreground" style="padding-top: 10px;">
                Eén WhatsApp Business-account voor de hele SaaS. Boekings- en ritmeldingen van alle tenants worden hierover verstuurd.
                In de <strong>berichttekst</strong> staat de tenantnaam (bijv. Taxi Royaal). De WhatsApp-profielnaam in de chat is die van het Meta-telefoonnummer — die kan Meta niet per tenant wijzigen.
            </div>
            @if(is_array($whatsappConnectionStatus ?? null))
                @if(!empty($whatsappConnectionStatus['ok']))
                    <div class="mx-5 mb-4 rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-300">
                        Verbinding OK
                        @if(!empty($whatsappConnectionStatus['meta']['verified_name']) || !empty($whatsappConnectionStatus['meta']['display_phone_number']))
                            —
                            {{ $whatsappConnectionStatus['meta']['verified_name'] ?? '' }}
                            {{ !empty($whatsappConnectionStatus['meta']['display_phone_number']) ? '('.$whatsappConnectionStatus['meta']['display_phone_number'].')' : '' }}
                        @endif
                    </div>
                @else
                    <div class="mx-5 mb-4 rounded-lg border border-destructive/40 bg-destructive/10 px-4 py-3 text-sm text-destructive">
                        WhatsApp API-verbinding mislukt: {{ $whatsappConnectionStatus['error'] ?? 'Onbekende fout' }}
                        <div class="mt-1 text-xs opacity-90">Vernieuw de token (permanente System User token) en sla opnieuw op.</div>
                    </div>
                @endif
            @endif
            <form method="POST" action="{{ route('admin.settings.whatsapp.platform.update') }}" data-validate="true">
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
                                       value="{{ old('WHATSAPP_API_TOKEN', $whatsappPlatformSettings['WHATSAPP_API_TOKEN'] ?? '') }}"
                                       placeholder="EAAxxxxxxxxxxxx"
                                       autocomplete="off">
                            </div>
                            <div class="text-xs text-muted-foreground mt-1">
                                Permanente <strong>System User token</strong> (Meta Business Suite). Leeg laten bij opslaan behoudt de bestaande token.
                            </div>
                            @error('WHATSAPP_API_TOKEN')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Phone Number ID</td>
                        <td class="min-w-48 w-full">
                            <div class="relative">
                                <input type="text"
                                       class="kt-input @error('WHATSAPP_PHONE_NUMBER_ID') border-destructive @enderror"
                                       id="WHATSAPP_PHONE_NUMBER_ID"
                                       name="WHATSAPP_PHONE_NUMBER_ID"
                                       value="{{ old('WHATSAPP_PHONE_NUMBER_ID', $whatsappPlatformSettings['WHATSAPP_PHONE_NUMBER_ID'] ?? '') }}"
                                       placeholder="123456789012345"
                                       data-validate-as="text"
                                       autocomplete="off"
                                       inputmode="numeric">
                            </div>
                            <div class="text-xs text-muted-foreground mt-1">Meta Phone Number ID (cijfer-ID), geen telefoonnummer.</div>
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
                                       value="{{ old('WHATSAPP_BUSINESS_ACCOUNT_ID', $whatsappPlatformSettings['WHATSAPP_BUSINESS_ACCOUNT_ID'] ?? '') }}"
                                       placeholder="123456789012345"
                                       autocomplete="off">
                            </div>
                            @error('WHATSAPP_BUSINESS_ACCOUNT_ID')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">API-versie</td>
                        <td class="min-w-48 w-full">
                            <div class="relative">
                                <input type="text"
                                       class="kt-input @error('WHATSAPP_API_VERSION') border-destructive @enderror"
                                       id="WHATSAPP_API_VERSION"
                                       name="WHATSAPP_API_VERSION"
                                       value="{{ old('WHATSAPP_API_VERSION', $whatsappPlatformSettings['WHATSAPP_API_VERSION'] ?? 'v18.0') }}"
                                       placeholder="v18.0">
                            </div>
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
                                       value="{{ old('WHATSAPP_WEBHOOK_VERIFY_TOKEN', $whatsappPlatformSettings['WHATSAPP_WEBHOOK_VERIFY_TOKEN'] ?? '') }}"
                                       placeholder="your-verify-token"
                                       autocomplete="off">
                            </div>
                            <div class="text-xs text-muted-foreground mt-1">
                                Zelfde waarde als in Meta → Configure Webhooks → Verify token.
                                Callback URL: <code class="text-xs">https://nexasuite.nl/api/whatsapp/webhook</code>
                            </div>
                            @error('WHATSAPP_WEBHOOK_VERIFY_TOKEN')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal align-top">Standaardbericht</td>
                        <td class="min-w-48 w-full">
                            <div class="relative">
                                <textarea rows="4"
                                          class="kt-input pt-1 @error('WHATSAPP_DEFAULT_MESSAGE') border-destructive @enderror"
                                          id="WHATSAPP_DEFAULT_MESSAGE"
                                          name="WHATSAPP_DEFAULT_MESSAGE"
                                          placeholder="Hallo, bedankt voor uw interesse...">{{ old('WHATSAPP_DEFAULT_MESSAGE', $whatsappPlatformSettings['WHATSAPP_DEFAULT_MESSAGE'] ?? '') }}</textarea>
                            </div>
                            @error('WHATSAPP_DEFAULT_MESSAGE')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                        </td>
                    </tr>
                </table>
                <div class="kt-card-footer flex justify-end items-center gap-5 pt-5 border-t border-border px-5">
                    <button type="submit" class="kt-btn kt-btn-primary">
                        <i class="ki-filled ki-check me-2"></i> WhatsApp Business opslaan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
