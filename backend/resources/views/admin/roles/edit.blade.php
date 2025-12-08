@extends('admin.layouts.app')

@section('title', 'Rol Bewerken')

@section('content')




<div class="kt-container-fixed">
    <div class="row">
        <div class="col-12">
            <div class="kt-container-fixed">
    <div class="flex flex-col items-stretch grow">
        <form[^>]*class="[^"]*"
                    <form action="{{ route('admin.roles.update', $role) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="w-full">
                        <div class="flex items-center py-3">
                            <label for="name" class="kt-form-label flex items-center gap-1 max-w-56">
                                Rol Naam *
                            </label>
                            <input type="text" 
                                           class="kt-input @error('name') is-invalid @enderror" 
                                           id="name" 
                                           name="name" 
                                           value="{{ old('name', $role->
                            @error('name') is-invalid @enderror
                        </div>
                    </div></div>
                            
                            <div class="col-md-6">
                                <div class="w-full">
                        <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                            <label class="kt-form-label flex items-center gap-1 max-w-56">

                                    <label for="description" class="kt-form-label">Beschrijving</label>
                                    <textarea class="material-form-textarea @error('description') is-invalid @enderror" 
                                              id="description" 
                                              name="description" 
                                              rows="4">{{ old('description', $role->description) }}</textarea>
                                    @error('description')
                                        <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                    @enderror
                                
                        </div>
                    </div></div>
                        </div>

                        <div class="mb-4">
                            <h6 class="material-section-title">
                                <i class="fas fa-key me-2"></i>
                                Rechten Toewijzen
                            </h6>
                            
                            @error('permissions')
                                <div class="kt-alert kt-alert-danger">{{ $message }}</div>
                            @enderror

                            <div class="row">
                                @foreach($permissions as $group => $groupPermissions)
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card border">
                                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0 text-capitalize">{{ $group }}</h6>
                                                <div class="form-check">
                                                    <input class="form-check-input" 
                                                           type="checkbox" 
                                                           id="select_all_{{ $group }}"
                                                           onchange="selectAllInGroup('{{ $group }}')">
                                                    <label class="form-check-label small" for="select_all_{{ $group }}">
                                                        Alles
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="kt-card-content grid gap-5">
                                                @foreach($groupPermissions as $permission)
                                                    <div class="form-check">
                                                        <input class="form-check-input" 
                                                               type="checkbox" 
                                                               name="permissions[]" 
                                                               value="{{ $permission->name }}" 
                                                               id="permission_{{ $permission->id }}"
                                                               data-group="{{ $group }}"
                                                               {{ in_array($permission->name, old('permissions', $role->permissions->pluck('name')->toArray())) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="permission_{{ $permission->id }}">
                                                            {{ ucfirst(str_replace('-', ' ', $permission->name)) }}
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2.5">
                            <a href="{{ route('admin.roles.index') }}" class="kt-btn kt-btn-outline">
                                <i class="fas fa-times"></i>
                                Annuleren
                            </a>
                            <button type="submit" class="kt-btn kt-btn-primary">
                                <i class="fas fa-save"></i>
                                Wijzigingen Opslaan
                            </button>
                        </div>
                    </form>
                </div>
    </div>
</div>

<script>
// Select all permissions in a group
function selectAllInGroup(groupName) {
    const checkboxes = document.querySelectorAll(`[data-group="${groupName}"]`);
    const selectAllCheckbox = document.getElementById(`select_all_${groupName}`);
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
}

// Update "select all" checkboxes on page load
document.addEventListener('DOMContentLoaded', function() {
    const groups = @json(array_keys($permissions->toArray()));
    
    groups.forEach(group => {
        const checkboxes = document.querySelectorAll(`[data-group="${group}"]`);
        const selectAllCheckbox = document.getElementById(`select_all_${group}`);
        const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
        
        if (checkedCount === checkboxes.length && checkboxes.length > 0) {
            selectAllCheckbox.checked = true;
        } else if (checkedCount > 0) {
            selectAllCheckbox.indeterminate = true;
        }
    });
});
</script>
@endsection
