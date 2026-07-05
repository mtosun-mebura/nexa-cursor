@php
    $aiChatService = app(\App\Services\AiChatAssistantService::class);
@endphp
@if($aiChatService->canShowAdminChat())
    @include('frontend.components.ai-chatbot', [
        'aiChatConfig' => $aiChatService->adminConfig(),
        'chatRootClass' => 'ai-chat-root--admin',
        'chatPanelPositionClass' => 'md:top-[4.75rem] md:right-4 max-md:inset-x-0 max-md:bottom-0 max-md:top-auto',
    ])
@endif
