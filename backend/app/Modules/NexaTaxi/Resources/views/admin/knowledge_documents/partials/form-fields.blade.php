@php
    $document = $document ?? null;
    $selectedCategory = old('category', $document->category ?? 'diensten');
@endphp

@include('taxi::admin.knowledge_documents.partials.content-list-styles')

<div class="grid gap-2">
    <label for="title" class="text-sm font-medium text-mono">Titel</label>
    <input type="text" name="title" id="title" class="kt-input w-full min-w-0 @error('title') border-destructive @enderror"
           value="{{ old('title', $document->title ?? '') }}" required maxlength="255"
           placeholder="Bijv. Luchthavenvervoer">
    @error('title')<span class="text-destructive text-sm">{{ $message }}</span>@enderror
</div>

<div class="grid gap-2">
    <label for="category" class="text-sm font-medium text-mono">Categorie</label>
    <select name="category" id="category" class="kt-select w-full sm:w-64 max-w-md min-w-0 @error('category') border-destructive @enderror" required>
        @foreach($categoryLabels as $value => $label)
            <option value="{{ $value }}" {{ $selectedCategory === $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    @error('category')<span class="text-destructive text-sm">{{ $message }}</span>@enderror
</div>

<div class="grid gap-2 min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <label for="knowledge-content" class="text-sm font-medium text-mono">Inhoud</label>
        <div class="flex flex-wrap items-center gap-3">
            <label class="inline-flex items-center gap-2.5 text-sm text-secondary-foreground cursor-pointer select-none">
                <input type="checkbox"
                       id="knowledge-content-shorten"
                       class="kt-switch kt-switch-sm shrink-0"
                       value="1"
                       role="switch"
                       aria-label="Tekst inkorten">
                <span>Tekst inkorten</span>
            </label>
            <button type="button"
                    id="knowledge-content-format-btn"
                    class="kt-btn kt-btn-primary kt-btn-sm inline-flex items-center"
                    data-format-url="{{ route('admin.taxi.knowledge_documents.format_content') }}"
                    data-default-label="Tekst opmaken">
                <i class="ki-filled ki-document me-1.5" aria-hidden="true"></i>
                Tekst opmaken
            </button>
        </div>
    </div>
    @include('admin.website-pages.partials.flowbite-wysiwyg', [
        'editorId' => 'knowledge-content-editor',
        'name' => 'content',
        'value' => old('content', $document->content ?? ''),
        'placeholder' => 'Korte, duidelijke tekst die de AI-assistent kan gebruiken als antwoord.',
        'textareaId' => 'knowledge-content',
        'contentMinHeightPx' => 400,
        'contentMaxHeightPx' => 640,
    ])
    @error('content')<span class="text-destructive text-sm">{{ $message }}</span>@enderror
    <p class="text-secondary-foreground text-xs">
        Minimaal 20 tekens. <strong>Tekst opmaken</strong> toont de inhoud direct opgemaakt in de editor (koppen, alinea’s, opsommingen).
        Met <strong>Tekst inkorten</strong> aan maak je een korte versie; zet het schuifje uit en klik opnieuw op <strong>Tekst opmaken</strong> om de volledige tekst terug te zien.
    </p>
</div>

@push('styles')
<style>
    .knowledge-document-form .flowbite-wysiwyg-wrapper {
        max-width: 100% !important;
    }
    .knowledge-document-form .flowbite-wysiwyg-content .ProseMirror {
        min-height: inherit;
    }
</style>
@endpush
