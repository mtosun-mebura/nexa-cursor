@php
    $selectedRoles = old('roles', $selectedRoles ?? []);
    if (! is_array($selectedRoles)) {
        $selectedRoles = [];
    }
    $roles = \App\Support\WebRoleFormOptions::dedupe(collect($roles ?? []));
@endphp
<div class="flex flex-col gap-2.5 @error('roles') ring-1 ring-destructive rounded-lg p-3 @enderror @error('roles.*') ring-1 ring-destructive rounded-lg p-3 @enderror" data-required-checkbox-group="roles">
    @forelse($roles as $role)
        @if($role->name !== 'super-admin' || auth()->user()->hasRole('super-admin'))
            <label class="inline-flex items-center gap-2">
                <input type="checkbox"
                       class="kt-checkbox"
                       name="roles[]"
                       value="{{ $role->name }}"
                       data-checkbox-group="roles"
                       {{ in_array($role->name, $selectedRoles, true) ? 'checked' : '' }}>
                <span class="text-sm text-secondary-foreground">{{ ucfirst(str_replace('-', ' ', $role->name)) }}</span>
            </label>
        @endif
    @empty
        <p class="text-sm text-muted-foreground">Geen rollen beschikbaar.</p>
    @endforelse
</div>
<p class="text-xs text-muted-foreground mt-1">Selecteer één of meer rollen, bijv. company admin én chauffeur.</p>
@error('roles')
    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
@enderror
@error('roles.*')
    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
@enderror
