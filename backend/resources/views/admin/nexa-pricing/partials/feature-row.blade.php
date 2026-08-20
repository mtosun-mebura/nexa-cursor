@php
    $value = (string) ($value ?? '');
    $name = (string) ($name ?? '');
    $included = (bool) ($included ?? true);
@endphp
<div class="nexa-pricing-feature-row flex items-center gap-2 min-w-0">
    <label class="nexa-pricing-feature-toggle shrink-0 inline-flex items-center justify-center size-8 rounded-md border border-border bg-background cursor-pointer hover:border-green-500/50" title="Kenmerk voor dit pakket">
        <input type="checkbox" class="nexa-pricing-feature-included sr-only" @checked($included)>
        <i class="ki-filled ki-check text-green-500 text-base" aria-hidden="true"></i>
        <span class="sr-only">Kenmerk voor dit pakket</span>
    </label>
    <input class="kt-input w-full min-w-0" type="text" name="{{ $name }}" value="{{ $value }}" placeholder="Kenmerk" maxlength="255" data-skip-validation-wrapper="1">
    <button type="button" class="nexa-pricing-feature-remove kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Kenmerk verwijderen" aria-label="Kenmerk verwijderen">
        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
    </button>
</div>
