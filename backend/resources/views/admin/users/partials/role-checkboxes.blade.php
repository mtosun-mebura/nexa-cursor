@php
    $selectedRoles = old('roles', $selectedRoles ?? []);
    if ($selectedRoles instanceof \Illuminate\Support\Collection) {
        $selectedRoles = $selectedRoles->all();
    }
    if (! is_array($selectedRoles)) {
        $selectedRoles = [];
    }
    $roles = \App\Support\WebRoleFormOptions::dedupe(collect($roles ?? []));
    $readonly = (bool) ($readonly ?? false);
@endphp
@if($readonly)
    <div class="flex flex-wrap gap-1.5">
        @forelse($selectedRoles as $roleName)
            <span class="kt-badge kt-badge-info">{{ ucfirst(str_replace('-', ' ', (string) $roleName)) }}</span>
            <input type="hidden" name="roles[]" value="{{ $roleName }}">
        @empty
            <span class="text-sm text-muted-foreground">Geen rollen</span>
        @endforelse
    </div>
    <p class="text-xs text-muted-foreground mt-2 mb-0">Alleen een super-admin kan rollen wijzigen. Je kunt je eigen rollen niet aanpassen.</p>
@else
<div class="grid grid-cols-2 gap-x-6 @error('roles') ring-1 ring-destructive rounded-lg p-3 @enderror @error('roles.*') ring-1 ring-destructive rounded-lg p-3 @enderror" data-required-checkbox-group="roles">
    @forelse($roles as $role)
        @if($role->name !== 'super-admin' || auth()->user()->hasRole('super-admin'))
            <label class="inline-flex items-center gap-2 min-w-0">
                <input type="checkbox"
                       class="kt-checkbox"
                       name="roles[]"
                       value="{{ $role->name }}"
                       data-checkbox-group="roles"
                       {{ \App\Support\WebRoleFormOptions::isSelected((string) $role->name, $selectedRoles) ? 'checked' : '' }}>
                <span class="text-sm text-secondary-foreground">{{ ucfirst(str_replace('-', ' ', $role->name)) }}</span>
            </label>
        @endif
    @empty
        <p class="text-sm text-muted-foreground col-span-2 mb-0">Geen rollen beschikbaar.</p>
    @endforelse
</div>
<p class="text-xs text-muted-foreground mt-1">Selecteer één of meer rollen, bijv. company admin én chauffeur.</p>
@endif
@error('roles')
    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
@enderror
@error('roles.*')
    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
@enderror
