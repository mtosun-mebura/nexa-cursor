@php
    $aiChatConfig = app(\App\Services\AiChatAssistantService::class)->frontendConfig();
@endphp
@include('frontend.components.ai-chatbot', ['aiChatConfig' => $aiChatConfig])
