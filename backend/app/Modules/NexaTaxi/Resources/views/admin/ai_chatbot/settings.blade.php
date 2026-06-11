@extends('admin.layouts.app')

@section('title', 'AI-chatbot instellingen')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <div class="min-w-0 flex-1">
            <h1 class="text-xl font-medium leading-none text-mono">AI-chatbot</h1>
            <p class="text-sm text-muted-foreground mt-2 mb-0 leading-relaxed">
                Pas standaardteksten van de website-assistent aan. Geldt per bedrijf (tenant).
            </p>
        </div>
    </div>

    @include('taxi::admin.ai_chatbot.partials.subnav')

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5" role="alert">
            <i class="ki-filled ki-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="kt-alert kt-alert-danger mb-5" role="alert">
            <i class="ki-filled ki-information-2 me-2"></i> {{ session('error') }}
        </div>
    @endif

    @if($noTenantSelected ?? false)
        <div class="kt-alert kt-alert-warning" role="alert">
            Selecteer een bedrijf om AI-chatbot teksten per tenant te beheren.
        </div>
    @else
        <form method="POST" action="{{ route('admin.taxi.ai_chatbot.settings.update') }}" class="grid gap-5 lg:gap-7.5 w-full min-w-0">
            @csrf
            @method('PUT')

            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header">
                    <h3 class="kt-card-title mb-0">Chatvenster</h3>
                </div>
                <div class="kt-card-content p-0">
                    <div class="px-3 sm:px-5 pb-3 min-w-0">
                        <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal align-top pt-4">Welkomstbericht</td>
                                <td class="min-w-48 w-full pt-4">
                                    <textarea id="greeting" name="greeting" rows="3"
                                              class="kt-input w-full @error('greeting') border-destructive @enderror">{{ old('greeting', $messages['greeting'] ?? '') }}</textarea>
                                    <p class="text-xs text-muted-foreground mt-1">Eerste bericht dat bezoekers zien bij het openen van de assistent.</p>
                                    @error('greeting')
                                        <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                    @enderror
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">Titel</td>
                                <td class="min-w-48 w-full">
                                    <input type="text" id="title" name="title"
                                           class="kt-input w-full max-w-md @error('title') border-destructive @enderror"
                                           value="{{ old('title', $messages['title'] ?? '') }}">
                                    @error('title')
                                        <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                    @enderror
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">Subtitel</td>
                                <td class="min-w-48 w-full">
                                    <input type="text" id="subtitle" name="subtitle"
                                           class="kt-input w-full max-w-md @error('subtitle') border-destructive @enderror"
                                           value="{{ old('subtitle', $messages['subtitle'] ?? '') }}">
                                    @error('subtitle')
                                        <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                    @enderror
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header">
                    <h3 class="kt-card-title mb-0">Antwoorden</h3>
                </div>
                <div class="kt-card-content p-0">
                    <div class="px-3 sm:px-5 pb-3 min-w-0">
                        <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal align-top pt-4">Geen informatie gevonden</td>
                                <td class="min-w-48 w-full pt-4">
                                    <textarea id="not_found_message" name="not_found_message" rows="3"
                                              class="kt-input w-full @error('not_found_message') border-destructive @enderror">{{ old('not_found_message', $messages['not_found_message'] ?? '') }}</textarea>
                                    <p class="text-xs text-muted-foreground mt-1">
                                        Standaard: {{ $defaults['not_found_message'] ?? '' }}
                                    </p>
                                    @error('not_found_message')
                                        <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                    @enderror
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal align-top pt-4">Assistent niet bereikbaar</td>
                                <td class="min-w-48 w-full pt-4">
                                    <textarea id="unavailable_message" name="unavailable_message" rows="3"
                                              class="kt-input w-full @error('unavailable_message') border-destructive @enderror">{{ old('unavailable_message', $messages['unavailable_message'] ?? '') }}</textarea>
                                    <p class="text-xs text-muted-foreground mt-1">
                                        Bij technische storingen of time-outs. Standaard: {{ $defaults['unavailable_message'] ?? '' }}
                                    </p>
                                    @error('unavailable_message')
                                        <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                    @enderror
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal align-top pt-4">Geen toegang tot bedrijfsgegevens</td>
                                <td class="min-w-48 w-full pt-4">
                                    <textarea id="live_data_denied_message" name="live_data_denied_message" rows="3"
                                              class="kt-input w-full @error('live_data_denied_message') border-destructive @enderror">{{ old('live_data_denied_message', $messages['live_data_denied_message'] ?? '') }}</textarea>
                                    <p class="text-xs text-muted-foreground mt-1">
                                        Wanneer een bezoeker vraagt naar ritten of andere interne gegevens.
                                    </p>
                                    @error('live_data_denied_message')
                                        <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                    @enderror
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="admin-form-actions flex flex-wrap items-center justify-end gap-2.5 w-full min-w-0">
                <a href="{{ route('admin.taxi.knowledge_documents.index') }}" class="kt-btn kt-btn-outline">Annuleren</a>
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-check me-2"></i>Opslaan
                </button>
            </div>
        </form>
    @endif
</div>
@endsection
