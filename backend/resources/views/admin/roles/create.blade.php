@extends('admin.layouts.app')

@section('title', 'Nieuwe Rol')

@section('content')




<div class="kt-container-fixed">
    <div class="row">
        <div class="col-12">
            <div class="kt-container-fixed">
    <div class="flex flex-col items-stretch grow">
        <form[^>]*class="[^"]*"
                    <form action="{{ route('admin.roles.store') }}" method="POST">
                        @csrf
                        
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
                                           value="{{ old('name') }}" 
                                           required>
                            @error('name') is-invalid @enderror
                        </div>
                    </div></div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="w-full">
                        <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                            <label class="kt-form-label flex items-center gap-1 max-w-56">

                                    <label for="description" class="kt-form-label">Beschrijving</label>
                                    <textarea class="material-form-textarea @error('description') is-invalid @enderror" 
                                              id="description" 
                                              name="description" 
                                              rows="4">{{ old('description') }}</textarea>
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
                                        <div class="card permission-group-card">
                                            <div class="card-header">
                                                <h6 class="mb-0 text-capitalize">{{ $group }}</h6>
                                            </div>
                                            <div class="kt-card-content grid gap-5">
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

                        <div class="flex items-center justify-end gap-2.5">
                            <a href="{{ route('admin.roles.index') }}" class="kt-btn kt-btn-outline">
                                <i class="fas fa-times"></i>
                                Annuleren
                            </a>
                            <button type="submit" class="kt-btn kt-btn-primary">
                                <i class="fas fa-save"></i>
                                Rol Aanmaken
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
</script>
@endsection
