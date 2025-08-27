@extends('admin.layouts.app')

@section('title', 'Nieuwe Rol')

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
                        <i class="fas fa-user-shield me-2"></i> Nieuwe Rol Aanmaken
                    </h5>
                    <a href="{{ route('admin.roles.index') }}" class="material-btn material-btn-secondary">
                        <i class="fas fa-arrow-left"></i> Terug naar Overzicht
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.roles.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="name" class="material-form-label">Rol Naam *</label>
                                    <input type="text" 
                                           class="material-form-control @error('name') is-invalid @enderror" 
                                           id="name" 
                                           name="name" 
                                           value="{{ old('name') }}" 
                                           required>
                                    @error('name')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="material-text-muted">Gebruik kebab-case (bijv. 'content-editor')</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="description" class="material-form-label">Beschrijving</label>
                                    <textarea class="material-form-textarea @error('description') is-invalid @enderror" 
                                              id="description" 
                                              name="description" 
                                              rows="3">{{ old('description') }}</textarea>
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
                                        <div class="card permission-group-card">
                                            <div class="card-header">
                                                <h6 class="mb-0 text-capitalize">{{ $group }}</h6>
                                            </div>
                                            <div class="card-body">
                                                @foreach($groupPermissions as $permission)
                                                    <div class="material-form-check">
                                                        <input class="form-check-input material-form-check-input" 
                                                               type="checkbox" 
                                                               name="permissions[]" 
                                                               value="{{ $permission->name }}" 
                                                               id="permission_{{ $permission->id }}"
                                                               {{ in_array($permission->name, old('permissions', [])) ? 'checked' : '' }}>
                                                        <label class="form-check-label material-form-check-label" for="permission_{{ $permission->id }}">
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
                                Rol Aanmaken
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
</script>
@endsection
