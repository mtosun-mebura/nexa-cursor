@extends('admin.layouts.app')

@section('title', 'E-mail rit geaccepteerd')

@section('content')
<style>
    .dispatch-customer-email-plain-text {
        height: calc(1.375rem * 14 + 0.75rem);
        min-height: calc(1.375rem * 14 + 0.75rem);
        max-height: calc(1.375rem * 14 + 0.75rem);
        overflow-y: auto;
        resize: vertical;
        field-sizing: fixed;
    }
</style>
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center gap-5 pb-7.5">
        <a href="{{ $dispatchSettingsUrl }}" class="kt-btn kt-btn-outline">
            <i class="ki-filled ki-arrow-left me-2"></i>Terug naar dispatch
        </a>
        <h1 class="text-xl font-medium leading-none text-mono">E-mail: rit geaccepteerd</h1>
    </div>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5"><i class="ki-filled ki-check-circle me-2"></i>{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="kt-alert kt-alert-danger mb-5">
            <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    @if($usesGlobalFallback && $companyId)
        <div class="kt-alert kt-alert-info mb-5">
            Je bewerkt nu de <strong>standaardtekst</strong>. Na opslaan wordt een <strong>eigen versie voor dit bedrijf</strong> aangemaakt;
            die wordt gebruikt voor klanten van deze tenant.
        </div>
    @endif

    <form action="{{ route('admin.taxi.dispatch_settings.customer_accept_email.update') }}" method="POST" class="grid gap-5" data-validate="true" novalidate>
        @csrf
        @method('PUT')
        <input type="hidden" name="uses_global_fallback" value="{{ $usesGlobalFallback ? '1' : '0' }}">

        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Onderwerp</h3>
            </div>
            <div class="kt-card-content">
                <input type="text" name="subject" class="kt-input w-full @error('subject') border-destructive @enderror"
                       value="{{ old('subject', $template->subject) }}" required>
                @error('subject')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                <p class="text-xs text-muted-foreground mt-2">Bijv. <code>Uw taxirit is geaccepteerd – {{ '{' }}{{ '{' }} COMPANY_NAME {{ '}' }}{{ '}' }}</code></p>
            </div>
        </div>

        <div class="kt-card">
            <div class="kt-card-header flex-wrap gap-2">
                <h3 class="kt-card-title">HTML-inhoud</h3>
                <p class="text-xs text-muted-foreground mb-0">Wordt verstuurd naar de klant wanneer een chauffeur de rit accepteert (e-mail aan staat bij dispatch).</p>
            </div>
            <div class="kt-card-content">
                <textarea name="html_content" id="html_content" class="kt-input w-full font-mono text-sm @error('html_content') border-destructive @enderror"
                          rows="18" required>{{ old('html_content', $template->html_content) }}</textarea>
                @error('html_content')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Platte tekst (optioneel)</h3>
            </div>
            <div class="kt-card-content">
                <textarea name="text_content" class="kt-input w-full font-mono text-sm dispatch-customer-email-plain-text @error('text_content') border-destructive @enderror"
                          rows="14" wrap="soft">{{ old('text_content', $template->text_content) }}</textarea>
                @error('text_content')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                <p class="text-xs text-muted-foreground mt-2">Fallback voor e-mailclients zonder HTML. Leeg = afgeleid van HTML.</p>
            </div>
        </div>

        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Beschikbare variabelen</h3>
            </div>
            <div class="kt-card-content">
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach($variableLabels as $key => $label)
                        <div class="flex items-start gap-2 text-sm">
                            <code class="shrink-0 px-1.5 py-0.5 rounded bg-muted text-xs font-mono">{{ '{' }}{{ '{' }} {{ $key }} {{ '}' }}{{ '}' }}</code>
                            <span class="text-muted-foreground">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
                @if($canEditEmailTemplatesModule && (! $usesGlobalFallback || auth()->user()->hasRole('super-admin')))
                    <p class="text-xs text-muted-foreground mt-4 mb-0">
                        Geavanceerd beheer (testmail, ontvanger):
                        <a href="{{ route('admin.email-templates.edit', $template) }}" class="text-primary underline">E-mail templates-module</a>
                    </p>
                @endif
            </div>
        </div>

        <div class="flex justify-end gap-2.5">
            <a href="{{ $dispatchSettingsUrl }}" class="kt-btn kt-btn-outline">Annuleren</a>
            <button type="submit" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-check me-2"></i>Opslaan
            </button>
        </div>
    </form>
</div>

@include('admin.email-templates.partials.tinymce-html-editor')
@endsection
