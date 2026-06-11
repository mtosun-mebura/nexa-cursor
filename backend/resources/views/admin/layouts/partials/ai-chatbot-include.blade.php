@php
    $aiChatService = app(\App\Services\AiChatAssistantService::class);
@endphp
@if($aiChatService->canShowAdminChat())
    @include('frontend.components.ai-chatbot', [
        'aiChatConfig' => $aiChatService->adminConfig(),
        'chatRootClass' => 'ai-chat-root--admin',
        'chatPanelPositionClass' => 'top-[4.75rem] right-4',
    ])
@endif
