@php
    $aiChatConfig = $aiChatConfig ?? app(\App\Services\AiChatAssistantService::class)->frontendConfig();
    $chatRootClass = $chatRootClass ?? '';
    $chatPanelPositionClass = $chatPanelPositionClass ?? 'top-16 md:top-20 right-3 sm:right-4';
@endphp
@once
<link href="{{ asset('assets/vendors/keenicons/styles.bundle.css') }}" rel="stylesheet" />
@endonce
<style>
    .ai-chat-panel {
        position: fixed !important;
        z-index: 100251 !important;
        left: auto !important;
        width: min(32rem, calc(100vw - 1.5rem)) !important;
        height: min(40rem, calc(100vh - 6rem)) !important;
        transform-origin: top right !important;
    }
    .ai-chat-panel.ai-chat-panel--expanded {
        width: min(52rem, calc(100vw - 2rem)) !important;
        height: min(58rem, calc(100vh - 4.5rem)) !important;
    }
</style>
<!-- AI Chatbot (paneel; trigger staat in de header naast het thema-icoon) -->
<div x-data="aiChatbot(@js($aiChatConfig))"
     class="ai-chat-root {{ $chatRootClass }}">

    <!-- Chat Window -->
    <div x-show="isOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="transform opacity-0 scale-95 -translate-y-2"
         x-transition:enter-end="transform opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="transform opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="transform opacity-0 scale-95 -translate-y-2"
         @click.outside="if (!$event.target.closest('[data-ai-chat-toggle]')) closeChat()"
         :class="{ 'ai-chat-panel--expanded': isExpanded }"
         class="ai-chat-panel fixed {{ $chatPanelPositionClass }} rounded-lg flex flex-col">

        <!-- Chat Header -->
        <div class="ai-chat-panel__header p-4 rounded-t-lg flex items-center justify-between shrink-0">
            <div class="flex items-center space-x-2 min-w-0">
                <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 bg-white/20">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="font-semibold text-white truncate" x-text="config.title"></h3>
                    <p class="text-xs ai-chat-panel__header-subtitle truncate" x-text="config.subtitle"></p>
                </div>
            </div>
            <div class="flex items-center gap-0.5 shrink-0 ml-2">
                <button type="button"
                        @click.stop="toggleExpand()"
                        class="ai-chat-panel__icon-btn"
                        :aria-label="isExpanded ? 'Chat verkleinen' : 'Chat vergroten'"
                        :title="isExpanded ? 'Verkleinen' : 'Vergroten'">
                    <svg x-show="!isExpanded" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v2M16 4h2a2 2 0 012 2v2M8 20H6a2 2 0 01-2-2v-2M16 20h2a2 2 0 002-2v-2"></path>
                    </svg>
                    <svg x-show="isExpanded" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9H6V6M14 9h4V6M10 15H6v3M14 15h4v3"></path>
                    </svg>
                </button>
                <button type="button"
                        @click.stop="clearChat()"
                        class="ai-chat-panel__icon-btn"
                        :disabled="isTyping"
                        aria-label="Chat wissen"
                        title="Chat wissen">
                    <i class="ki-eraser ki-duotone text-xl leading-none" aria-hidden="true"></i>
                </button>
                <button type="button" @click="toggleChat()"
                        class="ai-chat-panel__icon-btn"
                        aria-label="Sluit chat"
                        title="Sluiten">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Chat Messages -->
        <div class="ai-chat-panel__messages flex-1 min-h-0 overflow-y-auto p-4 space-y-4"
             x-ref="messagesContainer">
            <template x-for="message in messages" :key="message.id">
                <div class="flex" :class="message.sender === 'user' ? 'justify-end' : 'justify-start'">
                    <div class="ai-chat-bubble max-w-[85%] px-4 py-2 rounded-lg"
                         :class="[
                             message.sender === 'user' ? 'ai-chat-bubble--user' : 'ai-chat-bubble--ai',
                             isExpanded ? 'ai-chat-bubble--expanded' : '',
                         ]">
                        <p class="text-sm whitespace-pre-wrap" x-show="message.sender === 'user'" x-text="message.text"></p>
                        <div class="text-sm ai-chat-message" x-show="message.sender !== 'user'" x-html="formatChatMessage(message.text)"></div>
                        <p class="ai-chat-bubble__time text-xs mt-1" x-text="message.time"></p>
                    </div>
                </div>
            </template>

            <!-- Typing Indicator -->
            <div x-show="isTyping" class="flex justify-start">
                <div class="ai-chat-typing px-4 py-2 rounded-lg">
                    <div class="flex space-x-1">
                        <div class="ai-chat-typing__dot w-2 h-2 rounded-full animate-bounce"></div>
                        <div class="ai-chat-typing__dot w-2 h-2 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                        <div class="ai-chat-typing__dot w-2 h-2 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chat Input -->
        <div class="ai-chat-panel__footer p-4 shrink-0 rounded-b-lg">
            <!-- Standaard tekstinvoer -->
            <form x-show="!activeQuoteInput()" @submit.prevent="sendMessage()" class="flex space-x-2">
                <input type="text"
                       x-model="newMessage"
                       x-ref="messageInput"
                       class="input flex-1 text-sm min-w-0"
                       :disabled="isTyping"
                       :placeholder="config.requiresTenant ? 'Selecteer eerst een tenant…' : 'Typ je vraag...'"
                       autocomplete="off">
                <button type="submit"
                        :disabled="!canSubmitTextInput()"
                        class="btn btn-primary px-3 py-2 text-sm disabled:opacity-50 disabled:cursor-not-allowed shrink-0">
                    <svg class="w-4 h-4 ai-chat-send-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                </button>
            </form>

            <!-- Adres met Google-suggesties -->
            <form x-show="activeQuoteInput()?.type === 'address'" @submit.prevent="submitStructuredInput()" class="space-y-2">
                <div class="relative ai-chat-address-field">
                    <input type="text"
                           x-ref="addressInput"
                           x-model="addressQuery"
                           @input="onAddressInput()"
                           @focus="onAddressInput()"
                           @keydown.escape="addressSuggestionsOpen = false"
                           class="input w-full text-sm"
                           :placeholder="activeQuoteInput()?.placeholder || 'Zoek adres…'"
                           :disabled="isTyping"
                           autocomplete="off">
                    <div x-show="addressLoading"
                         class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-muted-foreground">
                        …
                    </div>
                    <div x-show="addressSuggestionsOpen"
                         x-cloak
                         class="ai-chat-address-suggestions">
                        <template x-for="(item, index) in addressSuggestions" :key="index">
                            <button type="button"
                                    class="ai-chat-address-suggestion"
                                    @mousedown.prevent="selectAddressSuggestion(item)"
                                    x-text="item.label"></button>
                        </template>
                    </div>
                </div>
                <button type="submit"
                        :disabled="!canSubmitStructuredInput()"
                        class="btn btn-primary w-full text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                    Adres bevestigen
                </button>
                <p x-show="config.googleMapsApiKey && !addressSelectedFromSuggestions && addressQuery.trim().length >= 3"
                   class="text-xs text-muted-foreground">
                    Kies een adres uit de suggesties om te bevestigen.
                </p>
            </form>

            <!-- Datum/tijd picker -->
            <form x-show="activeQuoteInput()?.type === 'datetime'" @submit.prevent="submitStructuredInput()" class="space-y-2">
                <input type="datetime-local"
                       x-ref="datetimeInput"
                       x-model="datetimeValue"
                       class="input w-full text-sm ai-chat-datetime-input"
                       :min="activeQuoteInput()?.min || ''"
                       :disabled="isTyping">
                <button type="submit"
                        :disabled="!canSubmitStructuredInput()"
                        class="btn btn-primary w-full text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                    Datum en tijd bevestigen
                </button>
            </form>

            <!-- Bagage per type -->
            <form x-show="activeQuoteInput()?.type === 'baggage'" @submit.prevent="submitStructuredInput()" class="space-y-3">
                <p class="text-xs text-muted-foreground">Kies je bagage per type (0 is ook goed).</p>
                <div class="space-y-2 max-h-52 overflow-y-auto pr-1">
                    <template x-for="item in baggageInputItems()" :key="item.key">
                        <div class="rounded-lg border border-default-medium/60 bg-neutral-primary p-3">
                            <div class="text-sm font-semibold text-heading" x-text="item.title"></div>
                            <div class="text-xs text-muted-foreground" x-show="item.subtitle" x-text="item.subtitle"></div>
                            <div class="mt-2 inline-flex items-center gap-2">
                                <button type="button"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-default-medium"
                                        @click="adjustBaggageQty(item.key, -1, item.max)">−</button>
                                <span class="min-w-[1.25rem] text-center text-sm font-semibold tabular-nums"
                                      x-text="baggageQty[item.key] || 0"></span>
                                <button type="button"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-default-medium"
                                        @click="adjustBaggageQty(item.key, 1, item.max)">+</button>
                            </div>
                        </div>
                    </template>
                </div>
                <div x-show="baggageSpecialItems().length" class="space-y-2">
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" class="rounded border-default-medium" x-model="baggageShowSpecial">
                        <span>Bijzondere bagage meenemen?</span>
                    </label>
                    <div x-show="baggageShowSpecial" class="space-y-2 max-h-40 overflow-y-auto pr-1">
                        <template x-for="item in baggageSpecialItems()" :key="'special-' + item.key">
                            <div class="rounded-lg border border-default-medium/60 bg-neutral-primary p-3">
                                <div class="text-sm font-semibold text-heading" x-text="item.title"></div>
                                <div class="mt-2 inline-flex items-center gap-2">
                                    <button type="button"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-default-medium"
                                            @click="adjustSpecialBaggageQty(item.key, -1, item.max)">−</button>
                                    <span class="min-w-[1.25rem] text-center text-sm font-semibold tabular-nums"
                                          x-text="specialBaggageQty[item.key] || 0"></span>
                                    <button type="button"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-default-medium"
                                            @click="adjustSpecialBaggageQty(item.key, 1, item.max)">+</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                <button type="submit"
                        :disabled="!canSubmitStructuredInput()"
                        class="btn btn-primary w-full text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                    Bagage bevestigen
                </button>
            </form>

            <!-- Tekstveld (opmerkingen, contactgegevens) -->
            <form x-show="activeQuoteInput()?.type === 'text'" @submit.prevent="submitStructuredInput()" class="space-y-2">
                <input :type="activeQuoteInput()?.inputType || 'text'"
                       x-ref="remarksInput"
                       x-model="remarksValue"
                       class="input w-full text-sm"
                       :placeholder="activeQuoteInput()?.placeholder || 'Typ je antwoord…'"
                       :disabled="isTyping"
                       :inputmode="activeQuoteInput()?.inputMode || null"
                       :autocomplete="activeQuoteInput()?.autocomplete || 'off'">
                <button type="submit"
                        :disabled="!canSubmitStructuredInput()"
                        class="btn btn-primary w-full text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                    Bevestigen
                </button>
            </form>

            <!-- Aantal (personen) -->
            <form x-show="activeQuoteInput()?.type === 'number'" @submit.prevent="submitStructuredInput()" class="space-y-2">
                <input type="number"
                       x-ref="numberInput"
                       x-model="numberValue"
                       class="input w-full text-sm"
                       :min="activeQuoteInput()?.min ?? 0"
                       :max="activeQuoteInput()?.max ?? 20"
                       :placeholder="activeQuoteInput()?.placeholder || 'Aantal'"
                       :disabled="isTyping">
                <button type="submit"
                        :disabled="!canSubmitStructuredInput()"
                        class="btn btn-primary w-full text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                    Bevestigen
                </button>
            </form>
        </div>
    </div>
</div>
