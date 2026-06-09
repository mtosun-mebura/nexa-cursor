@php
    $aiChatConfig = app(\App\Services\AiChatAssistantService::class)->frontendConfig();
@endphp
@once
    @push('styles')
        <link href="{{ asset('assets/vendors/keenicons/styles.bundle.css') }}" rel="stylesheet" />
    @endpush
@endonce
@include('frontend.components.ai-chatbot', ['aiChatConfig' => $aiChatConfig])
