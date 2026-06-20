@php
    $fieldValue = old('planning_color', $value ?? '#3b82f6');
    if (! preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $fieldValue)) {
        $fieldValue = '#3b82f6';
    }
@endphp
<tr>
    <td class="text-secondary-foreground font-normal">Planningkleur</td>
    <td>
        <div class="flex flex-wrap items-center gap-3">
            <input
                type="color"
                name="planning_color"
                value="{{ $fieldValue }}"
                class="h-10 w-14 cursor-pointer rounded border border-input p-1"
                title="Kleur in planningsoverzicht"
                aria-label="Planningkleur abonnement"
            >
            <span class="text-xs text-muted-foreground">Wordt gebruikt als achtergrondkleur in het planningsoverzicht.</span>
        </div>
    </td>
</tr>
