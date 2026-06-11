@if(app(\App\Services\AiChatAssistantService::class)->canShowAdminChat())
    <button type="button"
            data-ai-chat-toggle
            class="kt-btn kt-btn-ghost kt-btn-icon size-8 hover:bg-background shrink-0"
            aria-label="Open taxi-assistent"
            aria-expanded="false"
            title="Taxi-assistent">
        @include('frontend.components.ai-chatbot-icon', ['class' => 'w-[22px] h-[22px]'])
    </button>
@endif
