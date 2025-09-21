<!-- AI Chatbot -->
<div x-data="aiChatbot()" 
     class="fixed bottom-4 right-4 z-50"
     x-init="init()">
    
    <!-- Chat Toggle Button -->
    <button @click="toggleChat()" 
            class="bg-primary-600 hover:bg-primary-700 text-white rounded-full p-4 shadow-lg transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
            :class="{ 'animate-pulse': isTyping }"
            aria-label="Open AI assistent">
        <svg x-show="!isOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
        </svg>
        <svg x-show="isOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
    </button>
    
    <!-- Chat Window -->
    <div x-show="isOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="transform opacity-0 scale-95 translate-y-4"
         x-transition:enter-end="transform opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="transform opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="transform opacity-0 scale-95 translate-y-4"
         class="absolute bottom-16 right-0 w-80 h-96 bg-white dark:bg-secondary-800 rounded-lg shadow-xl border border-secondary-200 dark:border-secondary-700 flex flex-col">
        
        <!-- Chat Header -->
        <div class="bg-primary-600 text-white p-4 rounded-t-lg flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <div class="w-8 h-8 bg-primary-500 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold">AI Assistent</h3>
                    <p class="text-xs text-primary-200">Altijd beschikbaar</p>
                </div>
            </div>
            <button @click="toggleChat()" 
                    class="text-primary-200 hover:text-white transition-colors duration-200"
                    aria-label="Sluit chat">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <!-- Chat Messages -->
        <div class="flex-1 overflow-y-auto p-4 space-y-4" 
             x-ref="messagesContainer"
             @scroll.window="scrollToBottom()">
            <template x-for="message in messages" :key="message.id">
                <div class="flex" :class="message.sender === 'user' ? 'justify-end' : 'justify-start'">
                    <div class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg"
                         :class="message.sender === 'user' 
                             ? 'bg-primary-600 text-white' 
                             : 'bg-secondary-100 dark:bg-secondary-700 text-secondary-900 dark:text-white'">
                        <p class="text-sm" x-text="message.text"></p>
                        <p class="text-xs mt-1 opacity-70" x-text="message.time"></p>
                    </div>
                </div>
            </template>
            
            <!-- Typing Indicator -->
            <div x-show="isTyping" class="flex justify-start">
                <div class="bg-secondary-100 dark:bg-secondary-700 text-secondary-900 dark:text-white px-4 py-2 rounded-lg">
                    <div class="flex space-x-1">
                        <div class="w-2 h-2 bg-secondary-400 rounded-full animate-bounce"></div>
                        <div class="w-2 h-2 bg-secondary-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                        <div class="w-2 h-2 bg-secondary-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Chat Input -->
        <div class="p-4 border-t border-secondary-200 dark:border-secondary-700">
            <form @submit.prevent="sendMessage()" class="flex space-x-2">
                <input type="text" 
                       x-model="newMessage"
                       placeholder="Typ je vraag..."
                       class="flex-1 input-field text-sm"
                       :disabled="isTyping">
                <button type="submit" 
                        :disabled="!newMessage.trim() || isTyping"
                        class="btn-primary px-3 py-2 text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function aiChatbot() {
    return {
        isOpen: false,
        isTyping: false,
        newMessage: '',
        messages: [
            {
                id: 1,
                sender: 'ai',
                text: 'Hallo! Ik ben je AI assistent. Ik kan je helpen bij het zoeken naar vacatures, het verbeteren van je CV, of het beantwoorden van vragen over solliciteren. Hoe kan ik je vandaag helpen?',
                time: new Date().toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' })
            }
        ],
        
        init() {
            // Load saved messages from localStorage
            const savedMessages = localStorage.getItem('ai-chat-messages');
            if (savedMessages) {
                this.messages = JSON.parse(savedMessages);
            }
        },
        
        toggleChat() {
            this.isOpen = !this.isOpen;
            if (this.isOpen) {
                this.$nextTick(() => {
                    this.scrollToBottom();
                });
            }
        },
        
        async sendMessage() {
            if (!this.newMessage.trim()) return;
            
            const userMessage = {
                id: Date.now(),
                sender: 'user',
                text: this.newMessage,
                time: new Date().toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' })
            };
            
            this.messages.push(userMessage);
            this.newMessage = '';
            this.isTyping = true;
            
            // Save to localStorage
            this.saveMessages();
            
            // Scroll to bottom
            this.scrollToBottom();
            
            // Simulate AI response
            setTimeout(() => {
                this.generateAIResponse(userMessage.text);
            }, 1000);
        },
        
        async generateAIResponse(userMessage) {
            try {
                // In a real implementation, this would call your AI API
                const response = await this.callAIAPI(userMessage);
                this.messages.push({
                    id: Date.now(),
                    sender: 'ai',
                    text: response,
                    time: new Date().toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' })
                });
            } catch (error) {
                this.messages.push({
                    id: Date.now(),
                    sender: 'ai',
                    text: 'Sorry, er is een fout opgetreden. Probeer het later opnieuw.',
                    time: new Date().toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' })
                });
            }
            
            this.isTyping = false;
            this.saveMessages();
            this.scrollToBottom();
        },
        
        async callAIAPI(message) {
            // Placeholder for AI API call
            // In a real implementation, you would call your AI service here
            const responses = [
                'Dat is een interessante vraag! Laat me je helpen met het zoeken naar relevante vacatures.',
                'Ik begrijp je vraag. Hier zijn enkele tips die je kunnen helpen bij je sollicitatie.',
                'Goede vraag! Ik kan je helpen bij het verbeteren van je CV of het voorbereiden op interviews.',
                'Ik zie dat je geïnteresseerd bent in dit onderwerp. Laat me je wat meer informatie geven.',
                'Dat is een veelgestelde vraag. Hier is wat je moet weten...'
            ];
            
            return responses[Math.floor(Math.random() * responses.length)];
        },
        
        scrollToBottom() {
            this.$nextTick(() => {
                const container = this.$refs.messagesContainer;
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            });
        },
        
        saveMessages() {
            localStorage.setItem('ai-chat-messages', JSON.stringify(this.messages));
        }
    }
}
</script>



