@extends('admin.layouts.app')

@section('title', 'Permissie Bewerken')

@push('scripts')
<script src="{{ asset('assets/js/form-validation.js') }}"></script>
@endpush

@section('content')

<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">
            Permissie Bewerken
        </h1>
        <a href="{{ route('admin.permissions.index') }}" class="kt-btn kt-btn-outline shrink-0">
            <i class="ki-filled ki-arrow-left me-2"></i>
            Terug
        </a>
    </div>

    <form action="{{ route('admin.permissions.update', $permission) }}" method="POST" data-validate="true" id="edit-permission-form" novalidate>
        @csrf
        @method('PUT')

        <div class="grid gap-5 lg:gap-7.5">
            <x-error-card :errors="$errors" />

            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header">
                    <h3 class="kt-card-title mb-0">Basis Informatie</h3>
                </div>
                <div class="kt-card-content p-0">
                    <div class="px-3 sm:px-5 pb-3 min-w-0">
                        <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">Naam *</td>
                                <td class="min-w-48 w-full">
                                    <div class="relative">
                                        <input type="text"
                                               name="name"
                                               class="kt-input @error('name') border-destructive @enderror"
                                               value="{{ old('name', $permission->name) }}"
                                               placeholder="bijv. view-users, create-vacancies"
                                               required>
                                    </div>
                                    <div class="field-feedback text-xs mt-1 hidden" data-field="name"></div>
                                    @error('name')
                                        <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                    @enderror
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal align-top">Omschrijving</td>
                                <td class="min-w-48 w-full">
                                    <div class="relative">
                                        <textarea name="description"
                                                  rows="4"
                                                  class="kt-input pt-1 @error('description') border-destructive @enderror"
                                                  placeholder="Beschrijf wat deze permissie doet...">{{ old('description', $permission->description) }}</textarea>
                                    </div>
                                    <div class="field-feedback text-xs mt-1 hidden" data-field="description"></div>
                                    @error('description')
                                        <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                    @enderror
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="admin-form-actions flex flex-wrap items-center justify-end gap-2.5 mt-5 w-full min-w-0">
                <a href="{{ route('admin.permissions.index') }}" class="kt-btn kt-btn-outline">
                    Annuleren
                </a>
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-check me-2"></i>
                    Wijzigingen Opslaan
                </button>
            </div>
        </div>
    </form>
</div>

@endsection
