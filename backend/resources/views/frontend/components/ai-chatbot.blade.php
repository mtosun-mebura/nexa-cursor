@php
    $aiChatConfig = $aiChatConfig ?? app(\App\Services\AiChatAssistantService::class)->frontendConfig();
@endphp
<style>
    .ai-chat-root {
        z-index: 100250 !important;
        isolation: isolate;
        pointer-events: none;
    }
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
     class="ai-chat-root fixed inset-0 pointer-events-none">

    <!-- Chat Window -->
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="transform opacity-0 scale-95 -translate-y-2"
         x-transition:enter-end="transform opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="transform opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="transform opacity-0 scale-95 -translate-y-2"
         @click.outside="if (!$event.target.closest('[data-ai-chat-toggle]')) closeChat()"
         :class="{ 'ai-chat-panel--expanded': isExpanded }"
         class="ai-chat-panel pointer-events-auto fixed top-16 md:top-20 right-3 sm:right-4 rounded-lg flex flex-col overflow-hidden">

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
        <div class="ai-chat-panel__messages flex-1 overflow-y-auto p-4 space-y-4"
             x-ref="messagesContainer">
            <template x-for="message in messages" :key="message.id">
                <div class="flex" :class="message.sender === 'user' ? 'justify-end' : 'justify-start'">
                    <div class="ai-chat-bubble max-w-[85%] px-4 py-2 rounded-lg"
                         :class="[
                             message.sender === 'user' ? 'ai-chat-bubble--user' : 'ai-chat-bubble--ai',
                             isExpanded ? 'ai-chat-bubble--expanded' : '',
                         ]">
                        <p class="text-sm whitespace-pre-wrap" x-show="message.sender === 'user'" x-text="message.text"></p>
                        <p class="text-sm whitespace-pre-wrap" x-show="message.sender !== 'user'" x-html="formatChatMessage(message.text)"></p>
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
        <div class="ai-chat-panel__footer p-4 shrink-0">
            <form @submit.prevent="sendMessage()" class="flex space-x-2">
                <input type="text"
                       x-model="newMessage"
                       placeholder="Typ je vraag..."
                       class="input flex-1 text-sm min-w-0"
                       :disabled="isTyping">
                <button type="submit"
                        :disabled="!newMessage.trim() || isTyping"
                        class="btn btn-primary px-3 py-2 text-sm disabled:opacity-50 disabled:cursor-not-allowed shrink-0">
                    <svg class="w-4 h-4 ai-chat-send-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>
