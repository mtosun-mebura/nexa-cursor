@extends('admin.layouts.app')

@section('title', 'Rol Bewerken')

@section('content')
<style>
    :root {
        --primary-color: #2196f3;
        --primary-light: #64b5f6;
        --primary-dark: #1976d2;
        --primary-hover: #42a5f5;
    }
</style>

@include('admin.material-design-template')

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="material-card">
                <div class="card-header">
                    <h5>
                        <i class="fas fa-user-shield me-2"></i> Rol Bewerken: {{ $role->name }}
                    </h5>
                    <a href="{{ route('admin.roles.index') }}" class="material-btn material-btn-secondary">
                        <i class="fas fa-arrow-left"></i> Terug naar Overzicht
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.roles.update', $role) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="name" class="material-form-label">Rol Naam *</label>
                                    <input type="text" 
                                           class="material-form-control @error('name') is-invalid @enderror" 
                                           id="name" 
                                           name="name" 
                                           value="{{ old('name', $role->name) }}" 
                                           required
                                           {{ in_array($role->name, ['super-admin', 'company-admin', 'staff', 'candidate']) ? 'readonly' : '' }}>
                                    @error('name')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    @if(in_array($role->name, ['super-admin', 'company-admin', 'staff', 'candidate']))
                                        <div class="material-text-muted">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            Systeem rollen kunnen niet worden hernoemd
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="description" class="material-form-label">Beschrijving</label>
                                    <textarea class="material-form-textarea @error('description') is-invalid @enderror" 
                                              id="description" 
                                              name="description" 
                                              rows="3">{{ old('description', $role->description) }}</textarea>
                                    @error('description')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h6 class="material-section-title">
                                <i class="fas fa-key me-2"></i>
                                Rechten Toewijzen
                            </h6>
                            
                            @error('permissions')
                                <div class="material-alert material-alert-danger">{{ $message }}</div>
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
                                            <div class="card-body">
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

                        <div class="material-form-actions">
                            <a href="{{ route('admin.roles.index') }}" class="material-btn material-btn-secondary">
                                <i class="fas fa-times"></i>
                                Annuleren
                            </a>
                            <button type="submit" class="material-btn material-btn-primary">
                                <i class="fas fa-save"></i>
                                Wijzigingen Opslaan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
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
