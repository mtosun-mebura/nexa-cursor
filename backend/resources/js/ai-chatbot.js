export function registerAiChatbot(Alpine) {
    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-ai-chat-toggle]')) {
            return;
        }
        event.preventDefault();
        window.dispatchEvent(new CustomEvent('ai-chat-toggle'));
    });

    Alpine.data('aiChatbot', (config) => ({
        config: config || {},
        isOpen: false,
        isExpanded: false,
        isTyping: false,
        newMessage: '',
        messages: [],
        sessionId: '',

        init() {
            const greeting = this.config.greeting || 'Hallo! Hoe kan ik je helpen?';
            const storageKey = this.config.storageKey || 'ai-chat-messages';
            this.config.storageKey = storageKey;
            const sessionStorageKey = `${storageKey}-session`;
            this.sessionId = localStorage.getItem(sessionStorageKey) || '';
            if (!this.sessionId) {
                this.sessionId = typeof crypto !== 'undefined' && crypto.randomUUID
                    ? crypto.randomUUID()
                    : `sess-${Date.now()}`;
                localStorage.setItem(sessionStorageKey, this.sessionId);
            }

            this._onToggle = () => this.toggleChat();
            this._onEscape = (event) => {
                if (event.key === 'Escape' && this.isOpen) {
                    this.closeChat();
                }
            };
            window.addEventListener('ai-chat-toggle', this._onToggle);
            document.addEventListener('keydown', this._onEscape);

            const savedMessages = localStorage.getItem(storageKey);
            if (savedMessages) {
                try {
                    this.messages = JSON.parse(savedMessages);
                } catch (error) {
                    this.messages = [];
                }
            }

            if (!Array.isArray(this.messages) || this.messages.length === 0) {
                this.messages = [this.createGreetingMessage(greeting)];
            }
        },

        toggleChat() {
            this.isOpen = !this.isOpen;
            this.syncHeaderTriggerState();
            if (this.isOpen) {
                this.$nextTick(() => {
                    this.scrollToBottom();
                });
            }
        },

        closeChat() {
            if (!this.isOpen) {
                return;
            }
            this.isOpen = false;
            this.isExpanded = false;
            this.syncHeaderTriggerState();
        },

        toggleExpand() {
            this.isExpanded = !this.isExpanded;
            this.$nextTick(() => {
                this.scrollToBottom();
            });
        },

        clearChat() {
            if (this.isTyping) {
                return;
            }

            const greeting = this.config.greeting || 'Hallo! Hoe kan ik je helpen?';
            this.messages = [this.createGreetingMessage(greeting)];
            this.newMessage = '';

            const storageKey = this.config.storageKey || 'ai-chat-messages';
            const sessionStorageKey = `${storageKey}-session`;
            this.sessionId = typeof crypto !== 'undefined' && crypto.randomUUID
                ? crypto.randomUUID()
                : `sess-${Date.now()}`;
            localStorage.setItem(sessionStorageKey, this.sessionId);
            this.saveMessages();
            this.$nextTick(() => {
                this.scrollToBottom();
            });
        },

        createGreetingMessage(text) {
            return {
                id: Date.now(),
                sender: 'ai',
                text,
                time: new Date().toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' }),
            };
        },

        syncHeaderTriggerState() {
            window.dispatchEvent(new CustomEvent('ai-chat-open-changed', {
                detail: { isOpen: this.isOpen, isTyping: this.isTyping },
            }));
            document.querySelectorAll('[data-ai-chat-toggle]').forEach((button) => {
                button.setAttribute('aria-expanded', this.isOpen ? 'true' : 'false');
                button.classList.toggle('bg-gray-100', this.isOpen);
                button.classList.toggle('dark:bg-gray-800', this.isOpen);
                button.classList.toggle('text-gray-900', this.isOpen);
                button.classList.toggle('dark:text-white', this.isOpen);
            });
        },

        async sendMessage() {
            if (!this.newMessage.trim()) {
                return;
            }

            const userMessage = {
                id: Date.now(),
                sender: 'user',
                text: this.newMessage,
                time: new Date().toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' }),
            };

            this.messages.push(userMessage);
            const outgoing = this.newMessage;
            this.newMessage = '';
            this.isTyping = true;
            this.syncHeaderTriggerState();
            this.saveMessages();
            this.scrollToBottom();

            try {
                const response = await this.callAssistantAPI(outgoing);
                this.messages.push({
                    id: Date.now() + 1,
                    sender: 'ai',
                    text: response,
                    time: new Date().toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' }),
                });
            } catch (error) {
                const fallback = 'Sorry, er is een fout opgetreden. Probeer het later opnieuw.';
                const detail = error instanceof Error && error.message && error.message !== 'Assistant request failed'
                    ? error.message
                    : fallback;
                this.messages.push({
                    id: Date.now() + 1,
                    sender: 'ai',
                    text: detail,
                    time: new Date().toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' }),
                });
            }

            this.isTyping = false;
            this.syncHeaderTriggerState();
            this.saveMessages();
            this.scrollToBottom();
        },

        buildHistory() {
            return this.messages
                .filter((message) => message.sender === 'user' || message.sender === 'ai')
                .slice(-10)
                .map((message) => ({
                    role: message.sender === 'user' ? 'user' : 'assistant',
                    text: message.text,
                }));
        },

        async callAssistantAPI(message) {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const response = await fetch(this.config.endpoint || '/ai-chat/message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    message: message,
                    history: this.buildHistory(),
                    module: this.config.module || 'default',
                    sessionId: this.sessionId,
                }),
            });

            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.success || !data.reply) {
                throw new Error(data.error || 'Assistant request failed');
            }

            return data.reply;
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
            localStorage.setItem(this.config.storageKey || 'ai-chat-messages', JSON.stringify(this.messages));
        },

        formatChatMessage(text) {
            if (!text || typeof text !== 'string') {
                return '';
            }

            const escaped = text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');

            return escaped
                .replace(/\[([^\]]+)\]\(([^)]+)\)/g, (match, label, url) => {
                    const safeUrl = this.sanitizeChatUrl(url);
                    if (!safeUrl) {
                        return label;
                    }

                    const safeLabel = label
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;');

                    const external = safeUrl.startsWith('http') && !this.isSameOriginUrl(safeUrl);
                    const attrs = external
                        ? ' target="_blank" rel="noopener noreferrer"'
                        : '';

                    return `<a href="${safeUrl}" class="ai-chat-link"${attrs}>${safeLabel}</a>`;
                })
                .replace(/\n/g, '<br>');
        },

        sanitizeChatUrl(url) {
            const trimmed = String(url).trim();
            if (!/^(https?:\/\/|mailto:|tel:|\/|#)/i.test(trimmed)) {
                return null;
            }

            return trimmed.replace(/"/g, '%22');
        },

        isSameOriginUrl(url) {
            if (url.startsWith('/') || url.startsWith('#')) {
                return true;
            }

            try {
                const parsed = new URL(url, window.location.origin);
                return parsed.origin === window.location.origin;
            } catch (error) {
                return false;
            }
        },
    }));
}
