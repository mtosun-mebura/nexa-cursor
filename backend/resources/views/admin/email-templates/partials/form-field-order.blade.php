{{-- Alleen tonen bij type informatieaanvraag. Kies velden en volgorde voor formulier en testformulier. --}}
@if(isset($isInfoRequestType) && $isInfoRequestType && isset($allFormFieldsPool) && $allFormFieldsPool->isNotEmpty())
@php
    $ordered = $formFields ?? collect();
    $orderedIds = $ordered->pluck('id')->all();
    $poolNotInOrder = $allFormFieldsPool->filter(fn($f) => !in_array($f->id, $orderedIds, true));
@endphp
<div class="kt-card" id="form-field-order-card">
    <div class="kt-card-header">
        <h5 class="kt-card-title">Formuliervelden en volgorde</h5>
    </div>
    <div class="kt-card-content">
        <p class="text-sm text-muted-foreground mb-4">
            Kies welke velden in het formulier (website en testmail) getoond worden en in welke volgorde. Gebruik in de HTML-inhoud de variabelen op basis van de <strong>slug</strong> (bijv. <code>@{{ VOORNAAM }}</code> voor slug <code>voornaam</code>).
        </p>
        <div id="form-field-order-list" class="space-y-2 mb-4">
            @foreach($ordered as $f)
            @php
                $varKey = strtoupper(str_replace('-', '_', $f->name));
                $varDisplay = '{{ ' . $varKey . ' }}';
            @endphp
            <div class="flex items-center gap-2 py-2 px-3 rounded-lg border border-border bg-muted/20" data-field-id="{{ $f->id }}">
                <span class="form-field-drag-handle cursor-grab active:cursor-grabbing touch-none p-1 -ml-1 rounded text-muted-foreground hover:text-foreground select-none" title="Sleep om volgorde te wijzigen" aria-label="Volgorde wijzigen" role="button" tabindex="0">⋮⋮</span>
                <span class="font-medium text-foreground">{{ $f->label }}</span>
                <code class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-white px-2 py-0.5 rounded">{{ $f->name }}</code>
                <span class="text-xs text-muted-foreground">→ <code>{{ $varDisplay }}</code></span>
                <div class="ml-auto flex items-center gap-1">
                    <button type="button" class="form-field-order-up kt-btn kt-btn-sm kt-btn-ghost" title="Omhoog"><i class="ki-filled ki-arrow-up"></i></button>
                    <button type="button" class="form-field-order-down kt-btn kt-btn-sm kt-btn-ghost" title="Omlaag"><i class="ki-filled ki-arrow-down"></i></button>
                    <button type="button" class="form-field-order-remove kt-btn kt-btn-sm kt-btn-ghost text-destructive" title="Verwijderen"><i class="ki-filled ki-trash"></i></button>
                </div>
                <input type="hidden" name="form_field_order[]" value="{{ $f->id }}">
            </div>
            @endforeach
        </div>
        @if($poolNotInOrder->isNotEmpty())
        <div class="flex items-center gap-2">
            <label for="form-field-order-add" class="text-sm text-secondary-foreground">Veld toevoegen:</label>
            <select id="form-field-order-add" class="kt-select w-auto max-w-xs">
                <option value="">— Kies een veld —</option>
                @foreach($poolNotInOrder as $f)
                <option value="{{ $f->id }}" data-label="{{ e($f->label) }}" data-name="{{ e($f->name) }}">{{ $f->label }} ({{ $f->name }})</option>
                @endforeach
            </select>
        </div>
        @else
        <p class="text-xs text-muted-foreground">Alle beschikbare velden zijn toegevoegd. <a href="{{ route('admin.email-templates.form-fields.index') }}" class="text-primary underline">Formulier velden</a> om nieuwe velden aan te maken.</p>
        @endif
    </div>
</div>
@endif
