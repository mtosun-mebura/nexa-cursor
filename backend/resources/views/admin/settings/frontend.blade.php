@extends('admin.layouts.app')

@section('title', 'Front-end configuraties')

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">
            Front-end configuraties
        </h1>
    </div>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5" id="success-alert" role="alert">
            <i class="ki-filled ki-check-circle me-2"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="kt-alert kt-alert-danger mb-5" role="alert">
            <i class="ki-filled ki-information me-2"></i>
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="kt-alert kt-alert-danger mb-5" role="alert">
            <i class="ki-filled ki-information me-2"></i>
            <strong>Er zijn validatiefouten opgetreden:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-5 lg:gap-7.5">
        <!-- Coming Soon pagina -->
        <div class="kt-card min-w-full">
            <div class="kt-card-header flex flex-wrap items-start justify-between gap-3 py-5">
                <div>
                    <h3 class="kt-card-title">
                        <i class="ki-filled ki-calendar-tick me-2"></i> Coming Soon-pagina
                    </h3>
                    <p class="text-sm text-muted-foreground mt-1">Deze pagina wordt getoond op de homepage zolang er geen actieve module is. Logo en favicon komen uit Algemene configuraties.</p>
                </div>
                <a href="{{ route('admin.settings.frontend.preview') }}" target="_blank" rel="noopener noreferrer" class="kt-btn kt-btn-outline shrink-0">
                    <i class="ki-filled ki-eye me-2"></i> Voorbeeld pagina
                </a>
            </div>
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <form method="POST" action="{{ route('admin.settings.coming-soon.update') }}" id="coming-soon-form">
                    @csrf
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Titel *</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="text"
                                           class="kt-input @error('coming_soon_title') border-destructive @enderror"
                                           id="coming_soon_title"
                                           name="coming_soon_title"
                                           value="{{ old('coming_soon_title', $comingSoonSettings['coming_soon_title'] ?? '') }}"
                                           placeholder="We zijn bijna live"
                                           required>
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Hoofdtitel op de coming soon-pagina</div>
                                @error('coming_soon_title')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">Tekst *</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <textarea rows="4"
                                              class="kt-input pt-1 @error('coming_soon_text') border-destructive @enderror"
                                              id="coming_soon_text"
                                              name="coming_soon_text"
                                              placeholder="Onze website wordt op dit moment voor u klaargemaakt..."
                                              required>{{ old('coming_soon_text', $comingSoonSettings['coming_soon_text'] ?? '') }}</textarea>
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Korte uitleg onder de titel</div>
                                @error('coming_soon_text')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">Secundaire tekst</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="text"
                                           class="kt-input @error('coming_soon_secondary_text') border-destructive @enderror"
                                           id="coming_soon_secondary_text"
                                           name="coming_soon_secondary_text"
                                           value="{{ old('coming_soon_secondary_text', $comingSoonSettings['coming_soon_secondary_text'] ?? '') }}"
                                           placeholder="Heeft u vragen? Neem gerust contact met ons op.">
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Optionele regel onder de hoofdtekst</div>
                                @error('coming_soon_secondary_text')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">E-mail tonen</td>
                            <td class="min-w-48 w-full">
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox"
                                           name="coming_soon_show_email"
                                           value="1"
                                           {{ old('coming_soon_show_email', $comingSoonSettings['coming_soon_show_email'] ?? '1') === '1' ? 'checked' : '' }}
                                           class="kt-checkbox rounded border-input">
                                    <span class="text-sm">Toon contact-e-mail op de coming soon-pagina</span>
                                </label>
                                <div class="text-xs text-muted-foreground mt-1">Als uitgeschakeld wordt het e-mailadres niet getoond</div>
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Contact e-mailadres</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="email"
                                           class="kt-input @error('coming_soon_contact_email') border-destructive @enderror"
                                           id="coming_soon_contact_email"
                                           name="coming_soon_contact_email"
                                           value="{{ old('coming_soon_contact_email', $comingSoonSettings['coming_soon_contact_email'] ?? '') }}"
                                           placeholder="info@voorbeeld.nl">
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Wordt alleen getoond als "E-mail tonen" aanstaat</div>
                                @error('coming_soon_contact_email')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Label bij e-mail</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="text"
                                           class="kt-input @error('coming_soon_contact_label') border-destructive @enderror"
                                           id="coming_soon_contact_label"
                                           name="coming_soon_contact_label"
                                           value="{{ old('coming_soon_contact_label', $comingSoonSettings['coming_soon_contact_label'] ?? 'E-mail') }}"
                                           placeholder="E-mail">
                                </div>
                                @error('coming_soon_contact_label')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">Footertekst</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="text"
                                           class="kt-input @error('coming_soon_footer_text') border-destructive @enderror"
                                           id="coming_soon_footer_text"
                                           name="coming_soon_footer_text"
                                           value="{{ old('coming_soon_footer_text', $comingSoonSettings['coming_soon_footer_text'] ?? '') }}"
                                           placeholder="© {year} {site}. Binnenkort beschikbaar.">
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Gebruik <code>{year}</code> voor het jaar en <code>{site}</code> voor de sitenaam.</div>
                                @error('coming_soon_footer_text')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    </table>
                    <div class="kt-card-footer flex justify-end items-center gap-5 pt-5 border-t border-border">
                        <button type="submit" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-check me-2"></i> Coming soon-instellingen opslaan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const successAlert = document.getElementById('success-alert');
    if (successAlert) {
        setTimeout(function() {
            successAlert.style.transition = 'opacity 0.3s ease-out';
            successAlert.style.opacity = '0';
            setTimeout(function() { successAlert.remove(); }, 300);
        }, 5000);
    }
});
</script>
@endsection
