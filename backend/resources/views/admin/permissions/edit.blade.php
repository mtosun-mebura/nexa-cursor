@extends('admin.layouts.app')

@section('title', 'Recht Bewerken')

@section('content')




<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono mb-3">
                {{ $title ?? "Pagina" }}
            </h1>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.' . str_replace(['admin.', '.create', '.edit', '.show'], ['', '.index', '.index', '.index'], request()->route()->getName())) }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
    </div>

    <div class="grid gap-5 lg:gap-7.5">
        <div class="w-full">
            <div class="kt-container-fixed">
    <div class="flex flex-col items-stretch grow">
        <form[^>]*class="[^"]*"
                    <form action="{{ route('admin.permissions.update', $permission) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="lg:col-span-6">
                                <div class="w-full">
                        <div class="flex items-center py-3">
                            <label for="name" class="kt-form-label flex items-center gap-1 max-w-56">
                                Recht Naam *
                            </label>
                            <input type="text" 
                                           class="kt-input @error('name') is-invalid @enderror" 
                                           id="name" 
                                           name="name" 
                                           value="{{ old('name', $permission->
                            @error('name') is-invalid @enderror
                        </div>
                    </div></div>
                            
                            <div class="lg:col-span-6">
                                <div class="w-full">
                        <div class="flex items-center py-3">
                            <label for="group" class="kt-form-label flex items-center gap-1 max-w-56">
                                Groep *
                            </label>
                            <input type="text" 
                                           class="kt-input @error('group') is-invalid @enderror" 
                                           id="group" 
                                           name="group" 
                                           value="{{ old('group', $permission->
                            @error('group') is-invalid @enderror
                        </div>
                    </div></div>
                        </div>

                        <div class="w-full">
                        <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                            <label class="kt-form-label flex items-center gap-1 max-w-56">

                            <label for="description" class="kt-form-label">Beschrijving</label>
                            <textarea class="material-form-textarea @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="4">{{ old('description', $permission->description) }}</textarea>
                            @error('description')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="flex items-center justify-end gap-2.5">
                            <a href="{{ route('admin.permissions.index') }}" class="kt-btn kt-btn-outline">
                                <i class="fas fa-times"></i>
                                Annuleren
                            </a>
                            <button type="submit" class="kt-btn kt-btn-primary">
                                <i class="fas fa-save"></i>
                                Wijzigingen Opslaan
                            </button>
                        
                        </div>
                    </div></form>
                </div>
    </div>
</div>
@endsection
