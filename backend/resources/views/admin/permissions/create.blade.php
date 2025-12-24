@extends('admin.layouts.app')

@section('title', 'Nieuwe Permissie')

@push('scripts')
<script src="{{ asset('assets/js/form-validation.js') }}"></script>
@endpush


@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-col gap-5 pb-7.5">
        <div class="flex flex-wrap items-center justify-between gap-5">
            <h1 class="text-xl font-medium leading-none text-mono">
                Nieuw Recht
            </h1>
        </div>
        <div class="flex items-center">
            <a href="{{ route('admin.permissions.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
    </div>

    <form action="{{ route('admin.permissions.store') }}" method="POST" data-validate="true">
        @csrf

        <div class="grid gap-5 lg:gap-7.5">
            <x-error-card :errors="$errors" />

            <!-- Basis Informatie -->
            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Basis Informatie</h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Naam *</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="text" 
                                           name="name" 
                                           class="kt-input @error('name') border-destructive @enderror" 
                                           value="{{ old('name') }}"
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
                            <td>
                                <div class="relative">
                                    <textarea name="description" 
                                              rows="4" 
                                              class="kt-input pt-1 @error('description') border-destructive @enderror"
                                              placeholder="Beschrijf wat deze permissie doet...">{{ old('description') }}</textarea>
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


            <div class="flex items-center justify-end gap-2.5">
                <a href="{{ route('admin.permissions.index') }}" class="kt-btn kt-btn-outline">
                    Annuleren
                </a>
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-check me-2"></i>
                    Permissie Aanmaken
                </button>
            </div>
        </div>
    </form>
</div>

@endsection
