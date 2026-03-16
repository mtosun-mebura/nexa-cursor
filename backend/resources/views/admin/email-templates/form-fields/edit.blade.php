@extends('admin.layouts.app')

@section('title', $isCreate ? 'Formulierveld toevoegen' : 'Formulierveld bewerken')

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-col gap-3 pb-7.5 items-start">
        <h1 class="text-xl font-medium leading-none text-mono">
            {{ $isCreate ? 'Formulierveld toevoegen' : 'Formulierveld bewerken' }}
        </h1>
        <a href="{{ route('admin.email-templates.form-fields.index') }}" class="kt-btn kt-btn-sm kt-btn-outline">
            <i class="ki-filled ki-arrow-left me-2"></i>
            Terug
        </a>
    </div>

    @if($errors->any())
        <div class="kt-alert kt-alert-danger mb-5">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ $isCreate ? route('admin.email-templates.form-fields.store') : route('admin.email-templates.form-fields.update', $field) }}" method="POST">
        @csrf
        @if(!$isCreate)
            @method('PUT')
        @endif
        <div class="kt-card">
            <div class="kt-card-content">
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Label *</td>
                        <td class="min-w-48 w-full">
                            <input type="text"
                                   class="kt-input @error('label') border-destructive @enderror"
                                   id="form-field-label"
                                   name="label"
                                   value="{{ old('label', $field->label) }}"
                                   required
                                   placeholder="bijv. Voornaam *"
                                   autocomplete="off">
                            @error('label')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Naam (slug) *</td>
                        <td class="min-w-48 w-full">
                            <input type="text"
                                   class="kt-input @error('name') border-destructive @enderror"
                                   id="form-field-name"
                                   name="name"
                                   value="{{ old('name', $field->name) }}"
                                   required
                                   placeholder="bijv. voornaam, email_aanvraag"
                                   pattern="[a-z_]+"
                                   title="Alleen kleine letters en underscores (geen cijfers)">
                            @error('name')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                            <p class="text-xs text-muted-foreground mt-1">Wordt de request-key bij POST en de variabelenaam in de e-mail (bijv. <code>@{{ VOORNAAM }}</code>). Alleen kleine letters en underscores. De slug wordt automatisch uit het label gegenereerd (spaties → _; geen cijfers).</p>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">Verplicht</td>
                        <td>
                            <label class="kt-label flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_required" value="1" class="kt-switch kt-switch-sm" {{ old('is_required', $field->is_required) ? 'checked' : '' }}>
                                <span class="text-foreground">Verplicht (wordt bij POST gecontroleerd)</span>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">Validatieregel</td>
                        <td>
                            <input type="text"
                                   class="kt-input @error('validation_rule') border-destructive @enderror"
                                   name="validation_rule"
                                   value="{{ old('validation_rule', $field->validation_rule) }}"
                                   placeholder="email, tel, number of regex:/^[0-9]+$/">
                            @error('validation_rule')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                            <p class="text-xs text-muted-foreground mt-1">Standaard: <code>email</code>, <code>tel</code>, <code>number</code>. Of aangepaste regex: <code>regex:/patroon/</code>. Wordt bij POST gecontroleerd.</p>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">Sorteervolgorde</td>
                        <td>
                            <input type="number" class="kt-input w-24" name="sort_order" value="{{ old('sort_order', $field->sort_order) }}" min="0" step="10">
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="flex items-center justify-end gap-2.5 mt-5">
            <a href="{{ route('admin.email-templates.form-fields.index') }}" class="kt-btn kt-btn-outline">Annuleren</a>
            <button type="submit" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-check me-2"></i>
                {{ $isCreate ? 'Toevoegen' : 'Opslaan' }}
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var labelEl = document.getElementById('form-field-label');
    var nameEl = document.getElementById('form-field-name');
    if (!labelEl || !nameEl) return;
    function labelToSlug(text) {
        return (text || '').toLowerCase()
            .replace(/\s+/g, '_')
            .replace(/[^a-z_]/g, '');
    }
    labelEl.addEventListener('input', function() {
        nameEl.value = labelToSlug(this.value);
    });
});
</script>
@endpush
@endsection
