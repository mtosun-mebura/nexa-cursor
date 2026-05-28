@props([
    'name',
    'id',
    'clearFlagName',
    'label',
    'inputClass' => 'kt-input w-full text-sm',
    'hasStored' => false,
    'placeholder' => '',
    'hint' => null,
])
<div class="tenant-sync-password-field" data-has-stored="{{ $hasStored ? '1' : '0' }}">
    <label for="{{ $id }}" class="text-sm text-secondary-foreground block mb-1">{{ $label }}</label>
    <div class="relative">
        <input type="password"
               name="{{ $name }}"
               id="{{ $id }}"
               class="{{ $inputClass }} pe-9 tenant-sync-password-input"
               autocomplete="new-password"
               placeholder="{{ $placeholder }}">
        <input type="hidden" name="{{ $clearFlagName }}" id="{{ $clearFlagName }}" value="0">
        <button type="button"
                class="tenant-sync-password-clear kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost absolute end-1 top-1/2 z-[2] -translate-y-1/2 hidden"
                data-password-input="{{ $id }}"
                data-clear-flag="{{ $clearFlagName }}"
                aria-label="Wachtwoord wissen"
                title="Wachtwoord wissen"
                tabindex="-1">
            <i class="ki-filled ki-cross text-sm"></i>
        </button>
    </div>
    @if ($hint)
        <p class="text-xs text-muted-foreground mt-1">{!! $hint !!}</p>
        <p class="text-xs text-muted-foreground mt-0.5">Typ het wachtwoord normaal (bijv. <code class="text-xs">Welkom01!</code>). Na tab uit het veld wordt het optioneel URL-encoded getoond; bij opslaan wordt het echte wachtwoord gebruikt.</p>
    @endif
</div>
