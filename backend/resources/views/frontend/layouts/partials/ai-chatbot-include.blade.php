@php
    $aiChatPage = (isset($page) && is_object($page)) ? $page : null;
    $aiChatCompanyId = $aiChatCompanyId
        ?? (filled($aiChatPage->company_id ?? null) ? (int) $aiChatPage->company_id : null);
    $aiChatModule = $aiChatPage?->module_name;
    $aiChatConfig = $aiChatConfig ?? app(\App\Services\AiChatAssistantService::class)->frontendConfig(
        $aiChatModule,
        $aiChatCompanyId
    );
@endphp
@include('frontend.components.ai-chatbot', ['aiChatConfig' => $aiChatConfig])
