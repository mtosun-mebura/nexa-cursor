import axios from 'axios';
import Alpine from 'alpinejs';
import { registerAiChatbot } from './ai-chatbot';

window.axios = axios;
window.Alpine = Alpine;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

registerAiChatbot(Alpine);

// Start Alpine.js
Alpine.start();
