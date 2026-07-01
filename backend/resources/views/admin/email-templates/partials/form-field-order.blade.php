{{-- Alleen tonen bij type informatieaanvraag. Kies velden en volgorde voor formulier en testformulier. --}}
@if(isset($isInfoRequestType) && $isInfoRequestType && isset($allFormFieldsPool) && $allFormFieldsPool->isNotEmpty())
@php
    $ordered = $formFields ?? collect();
    $orderedIds = $ordered->pluck('id')->all();
    $poolNotInOrder = $allFormFieldsPool->filter(fn($f) => !in_array($f->id, $orderedIds, true));
    $emailTemplate = $emailTemplate ?? null;
@endphp
<div class="kt-card w-full min-w-0" id="form-field-order-card">
    <div class="kt-card-header">
        <h5 class="kt-card-title mb-0">Formuliervelden en volgorde</h5>
    </div>
    <div class="kt-card-content min-w-0">
        <p class="text-sm text-muted-foreground mb-4">
            Kies welke velden in het formulier (website en testmail) getoond worden en in welke volgorde. Stel per veld in of het verplicht is (standaard uit <a href="{{ route('admin.email-templates.form-fields.index') }}" class="text-primary underline">Formulier velden</a>).
        </p>
        <div id="form-field-order-list" class="space-y-2 mb-4 min-w-0">
            @foreach($ordered as $f)
            @php
                $varKey = strtoupper(str_replace('-', '_', $f->name));
                $varDisplay = '{{ ' . $varKey . ' }}';
                $isRequired = $emailTemplate ? $emailTemplate->isFormFieldRequired($f) : (bool) $f->is_required;
            @endphp
            <div class="form-field-order-row flex flex-wrap items-center gap-2 py-2 px-3 rounded-lg border border-border bg-muted/20 min-w-0" data-field-id="{{ $f->id }}" data-default-required="{{ $f->is_required ? '1' : '0' }}">
                <span class="form-field-drag-handle shrink-0 cursor-grab active:cursor-grabbing touch-none p-1 -ml-1 rounded text-muted-foreground hover:text-foreground select-none leading-none" title="Sleep om volgorde te wijzigen" aria-label="Volgorde wijzigen" role="button" tabindex="0">⋮⋮</span>
                <div class="form-field-order-row-meta min-w-0 flex-1 flex flex-wrap items-center gap-x-2 gap-y-0">
                    <span class="font-medium text-foreground break-words leading-tight">{{ $f->label }}</span>
                    <code class="form-field-order-slug text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-white px-2 py-0.5 rounded shrink-0 leading-none">{{ $f->name }}</code>
                    <span class="form-field-order-var text-xs text-muted-foreground break-all leading-tight">→ <code>{{ $varDisplay }}</code></span>
                </div>
                <label class="form-field-order-required flex items-center gap-2 shrink-0 text-xs text-muted-foreground whitespace-nowrap cursor-pointer" title="Verplicht veld in dit template">
                    <span>Verplicht</span>
                    <input type="checkbox"
                           class="kt-switch kt-switch-sm shrink-0 form-field-required-toggle"
                           name="form_field_required[{{ $f->id }}]"
                           value="1"
                           {{ $isRequired ? 'checked' : '' }}>
                </label>
                <div class="form-field-order-row-actions flex items-center gap-1 shrink-0">
                    <button type="button" class="form-field-order-up kt-btn kt-btn-sm kt-btn-ghost" title="Omhoog"><i class="ki-filled ki-arrow-up"></i></button>
                    <button type="button" class="form-field-order-down kt-btn kt-btn-sm kt-btn-ghost" title="Omlaag"><i class="ki-filled ki-arrow-down"></i></button>
                    <button type="button" class="form-field-order-remove kt-btn kt-btn-sm kt-btn-ghost text-destructive" title="Verwijderen"><i class="ki-filled ki-trash"></i></button>
                </div>
                <input type="hidden" name="form_field_order[]" value="{{ $f->id }}">
            </div>
            @endforeach
        </div>
        @if($poolNotInOrder->isNotEmpty())
        <div class="flex flex-wrap items-center gap-2">
            <label for="form-field-order-add" class="text-sm text-secondary-foreground shrink-0">Veld toevoegen:</label>
            <select id="form-field-order-add" class="kt-select w-full sm:w-auto max-w-full sm:max-w-xs min-w-0">
                <option value="">— Kies een veld —</option>
                @foreach($poolNotInOrder as $f)
                <option value="{{ $f->id }}" data-label="{{ e($f->label) }}" data-name="{{ e($f->name) }}" data-required="{{ $f->is_required ? '1' : '0' }}">{{ $f->label }} ({{ $f->name }})</option>
                @endforeach
            </select>
        </div>
        @else
        <p class="text-xs text-muted-foreground">Alle beschikbare velden zijn toegevoegd. <a href="{{ route('admin.email-templates.form-fields.index') }}" class="text-primary underline">Formulier velden</a> om nieuwe velden aan te maken.</p>
        @endif
    </div>
</div>
@endif
