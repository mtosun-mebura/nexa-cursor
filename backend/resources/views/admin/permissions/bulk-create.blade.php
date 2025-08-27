@extends('admin.layouts.app')

@section('title', 'Rechten Bulk Aanmaken')

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
                        <i class="fas fa-key me-2"></i> Rechten Bulk Aanmaken
                    </h5>
                    <a href="{{ route('admin.permissions.index') }}" class="material-btn material-btn-secondary">
                        <i class="fas fa-arrow-left"></i> Terug naar Overzicht
                    </a>
                </div>
                <div class="card-body">
                    <div class="material-alert material-alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Bulk Aanmaken:</strong> Maak snel meerdere rechten aan voor een module. 
                        Het systeem zal automatisch rechten aanmaken in het formaat: [actie]-[module].
                    </div>

                    <form action="{{ route('admin.permissions.bulk-store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="material-form-group">
                                    <label for="module" class="material-form-label">Module Naam *</label>
                                    <input type="text" 
                                           class="material-form-control @error('module') is-invalid @enderror" 
                                           id="module" 
                                           name="module" 
                                           value="{{ old('module') }}" 
                                           placeholder="bijv. users, vacancies, companies"
                                           required>
                                    @error('module')
                                        <div class="material-invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="material-text-muted">Gebruik meervoud (bijv. 'users' in plaats van 'user')</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="material-form-label">Acties *</label>
                            @error('actions')
                                <div class="material-alert material-alert-danger">{{ $message }}</div>
                            @enderror

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card border">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">Basis CRUD Acties</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-check">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       name="actions[]" 
                                                       value="view" 
                                                       id="action_view"
                                                       {{ in_array('view', old('actions', [])) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="action_view">
                                                    <strong>View</strong> - Bekijken van items
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       name="actions[]" 
                                                       value="create" 
                                                       id="action_create"
                                                       {{ in_array('create', old('actions', [])) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="action_create">
                                                    <strong>Create</strong> - Nieuwe items aanmaken
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       name="actions[]" 
                                                       value="edit" 
                                                       id="action_edit"
                                                       {{ in_array('edit', old('actions', [])) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="action_edit">
                                                    <strong>Edit</strong> - Bestaande items bewerken
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       name="actions[]" 
                                                       value="delete" 
                                                       id="action_delete"
                                                       {{ in_array('delete', old('actions', [])) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="action_delete">
                                                    <strong>Delete</strong> - Items verwijderen
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card border">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">Specifieke Acties</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-check">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       name="actions[]" 
                                                       value="approve" 
                                                       id="action_approve"
                                                       {{ in_array('approve', old('actions', [])) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="action_approve">
                                                    <strong>Approve</strong> - Items goedkeuren
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       name="actions[]" 
                                                       value="schedule" 
                                                       id="action_schedule"
                                                       {{ in_array('schedule', old('actions', [])) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="action_schedule">
                                                    <strong>Schedule</strong> - Items inplannen
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       name="actions[]" 
                                                       value="send" 
                                                       id="action_send"
                                                       {{ in_array('send', old('actions', [])) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="action_send">
                                                    <strong>Send</strong> - Items versturen
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h6 class="material-section-title">Voorbeeld Rechten die worden aangemaakt:</h6>
                            <div class="material-alert material-alert-secondary">
                                <div id="permission-preview">
                                    <em>Voer een module naam in om een voorbeeld te zien...</em>
                                </div>
                            </div>
                        </div>

                        <div class="material-form-actions">
                            <a href="{{ route('admin.permissions.index') }}" class="material-btn material-btn-secondary">
                                <i class="fas fa-times"></i>
                                Annuleren
                            </a>
                            <button type="submit" class="material-btn material-btn-primary">
                                <i class="fas fa-save"></i>
                                Rechten Aanmaken
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('module').addEventListener('input', function() {
    const module = this.value.trim();
    const preview = document.getElementById('permission-preview');
    
    if (module) {
        const actions = ['view', 'create', 'edit', 'delete', 'approve', 'schedule', 'send'];
        const selectedActions = Array.from(document.querySelectorAll('input[name="actions[]"]:checked'))
            .map(cb => cb.value);
        
        if (selectedActions.length > 0) {
            const permissions = selectedActions.map(action => `${action}-${module}`).join('<br>');
            preview.innerHTML = permissions;
        } else {
            preview.innerHTML = '<em>Selecteer minimaal één actie...</em>';
        }
    } else {
        preview.innerHTML = '<em>Voer een module naam in om een voorbeeld te zien...</em>';
    }
});

// Update preview when checkboxes change
document.querySelectorAll('input[name="actions[]"]').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        document.getElementById('module').dispatchEvent(new Event('input'));
    });
});
</script>
@endsection
