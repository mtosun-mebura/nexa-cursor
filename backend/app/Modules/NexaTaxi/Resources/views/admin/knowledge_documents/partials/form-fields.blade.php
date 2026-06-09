@php
    $document = $document ?? null;
    $selectedCategory = old('category', $document->category ?? 'diensten');
@endphp

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
    <label for="knowledge-content" class="text-sm font-medium text-mono">Inhoud</label>
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
    <p class="text-secondary-foreground text-xs">Minimaal 20 tekens. Houd antwoorden bondig en feitelijk. Links en opmaak zijn toegestaan.</p>
</div>

@push('styles')
<style>
    .knowledge-document-form .flowbite-wysiwyg-wrapper {
        max-width: 100% !important;
    }
</style>
@endpush
