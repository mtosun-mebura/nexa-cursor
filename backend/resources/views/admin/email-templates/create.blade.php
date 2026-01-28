@extends('admin.layouts.app')

@section('title', 'Nieuw E-mail Template')

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                Nieuw E-mail Template
            </h1>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.email-templates.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="kt-alert kt-alert-danger mb-5">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.email-templates.store') }}" method="POST">
        @csrf
        
        <div class="grid gap-5 lg:gap-7.5">
            <!-- Basic Information Card -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <h5 class="kt-card-title">Basis Informatie</h5>
                </div>
                <div class="kt-card-content">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Naam *</td>
                            <td class="min-w-48 w-full">
                                <input type="text" 
                                       class="kt-input @error('name') border-destructive @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name') }}" 
                                       required>
                                @error('name')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Onderwerp *</td>
                            <td>
                                <input type="text" 
                                       class="kt-input @error('subject') border-destructive @enderror" 
                                       id="subject" 
                                       name="subject" 
                                       value="{{ old('subject') }}" 
                                       required>
                                @error('subject')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Type *</td>
                            <td>
                                <select class="kt-select @error('type') border-destructive @enderror" 
                                        id="type" 
                                        name="type" 
                                        required>
                                    <option value="">Selecteer type</option>
                                    @foreach($allowedTypes as $typeKey)
                                        <option value="{{ $typeKey }}" {{ old('type') == $typeKey ? 'selected' : '' }}>{{ $typeLabels[$typeKey] ?? $typeKey }}</option>
                                    @endforeach
                                </select>
                                @error('type')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Status</td>
                            <td>
                                <select class="kt-select @error('is_active') border-destructive @enderror" 
                                        id="is_active" 
                                        name="is_active">
                                    <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Actief</option>
                                    <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactief</option>
                                </select>
                                @error('is_active')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Beschrijving</td>
                            <td>
                                <textarea class="kt-input @error('description') border-destructive @enderror" 
                                          id="description" 
                                          name="description" 
                                          rows="4">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- HTML Content Card -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <h5 class="kt-card-title">HTML Inhoud *</h5>
                </div>
                <div class="kt-card-content">
                    <div class="mb-3">
                        <label for="html_content" class="kt-form-label mb-2">HTML Inhoud</label>
                        <textarea class="kt-input @error('html_content') border-destructive @enderror" 
                                  id="html_content" 
                                  name="html_content" 
                                  rows="10" 
                                  required>{{ old('html_content', $defaultHtmlTemplate ?? '') }}</textarea>
                        @error('html_content')
                            <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="text-xs text-muted-foreground">
                        <p class="mb-2"><strong>Beschikbare variabelen:</strong></p>
                        <div class="space-y-1.5">
                            @if(isset($templateVariables) && is_array($templateVariables))
                                @foreach($templateVariables as $variable => $description)
                                    <div class="flex items-center gap-2">
                                        <code class="px-2 py-1 bg-muted rounded text-foreground font-mono text-xs font-semibold">{{ '{' }}{{ '{' }}{{ $variable }}{{ '}' }}{{ '}' }}</code>
                                        <span class="text-foreground">{{ $description }}</span>
                                    </div>
                                @endforeach
                            @else
                                <span class="text-muted-foreground">Geen variabelen beschikbaar</span>
                            @endif
                        </div>
                        <p class="text-xs text-muted-foreground mt-3">
                            <strong>Tip:</strong> Gebruik deze variabelen in je template met dubbele accolades, bijvoorbeeld: <code class="px-1 py-0.5 bg-muted rounded text-foreground font-mono text-xs">{{ '{' }}{{ '{' }}USER_NAME{{ '}' }}{{ '}' }}</code>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Text Content Card -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <h5 class="kt-card-title">Tekst Inhoud (Plain Text)</h5>
                </div>
                <div class="kt-card-content">
                    <div class="mb-3">
                        <label for="text_content" class="kt-form-label mb-2">Tekst Inhoud</label>
                        <textarea class="kt-input pt-2 @error('text_content') border-destructive @enderror" 
                                  id="text_content" 
                                  name="text_content" 
                                  rows="30">{{ old('text_content') }}</textarea>
                        @error('text_content')
                            <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="text-xs text-muted-foreground">
                        Tekstversie voor e-mail clients die geen HTML ondersteunen. Laat leeg om automatisch te genereren vanuit HTML.
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-end gap-2.5 mt-5">
            <a href="{{ route('admin.email-templates.index') }}" class="kt-btn kt-btn-outline">
                Annuleren
            </a>
            <button type="submit" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-check me-2"></i>
                Template Opslaan
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const htmlContentTextarea = document.getElementById('html_content');
    const textContentTextarea = document.getElementById('text_content');
    
    function autoResizeTextarea(textarea) {
        if (!textarea) return;
        
        // Reset height to calculate scrollHeight correctly
        textarea.style.height = 'auto';
        
        // Calculate height based on content; add small buffer so no scrollbar appears
        const scrollHeight = textarea.scrollHeight;
        const lineHeight = parseInt(window.getComputedStyle(textarea).lineHeight) || 20;
        const minHeight = lineHeight * parseInt(textarea.getAttribute('rows') || 10);
        const buffer = 12;
        
        // Set height to scrollHeight + buffer, but at least minHeight
        textarea.style.height = Math.max(scrollHeight + buffer, minHeight) + 'px';
    }
    
    // Auto-resize on load
    if (htmlContentTextarea) {
        autoResizeTextarea(htmlContentTextarea);
        htmlContentTextarea.addEventListener('input', function() {
            autoResizeTextarea(this);
        });
    }
    
    if (textContentTextarea) {
        autoResizeTextarea(textContentTextarea);
        textContentTextarea.addEventListener('input', function() {
            autoResizeTextarea(this);
        });
    }
});
</script>
@endpush

@endsection
