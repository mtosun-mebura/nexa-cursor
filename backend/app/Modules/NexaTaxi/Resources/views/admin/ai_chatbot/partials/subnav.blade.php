@php
    $knowledgeActive = request()->routeIs('admin.taxi.knowledge_documents.*');
    $settingsActive = request()->routeIs('admin.taxi.ai_chatbot.settings.*');
@endphp
<nav class="flex flex-wrap gap-2 mb-6 border-b border-border pb-3" aria-label="AI-chatbot navigatie">
    <a href="{{ route('admin.taxi.knowledge_documents.index') }}"
       class="kt-btn kt-btn-sm {{ $knowledgeActive ? 'kt-btn-primary' : 'kt-btn-outline' }}">
        Kennisbank
    </a>
    @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('ai_chatbot.update') || auth()->user()->can('rides.update') || auth()->user()->can('vehicles.update'))
    <a href="{{ route('admin.taxi.ai_chatbot.settings.edit') }}"
       class="kt-btn kt-btn-sm {{ $settingsActive ? 'kt-btn-primary' : 'kt-btn-outline' }}">
        Instellingen
    </a>
    @endif
</nav>
