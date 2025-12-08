@extends('admin.layouts.app')

@section('title', 'Nieuwe Categorie')

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

    <div class="kt-card min-w-full pb-2.5">
                <div class="kt-card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-plus me-2"></i> Nieuwe Categorie
                    </h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.categories.index') }}" class="kt-btn kt-btn-outline">
                            <i class="fas fa-arrow-left me-2"></i> Terug naar Overzicht
                        </a>
                    </div>
                </div>
                <div class="kt-card-content grid gap-5">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <div>
                                <strong>Er zijn fouten opgetreden:</strong>
                                <ul class="mb-0 mt-2">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <form action="{{ route('admin.categories.store') }}" method="POST">
                        @csrf
                        
                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="lg:col-span-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Naam *</label>
                                    <input type="text" 
                                           class="form-control @error('name') is-invalid @enderror" 
                                           id="name" 
                                           name="name" 
                                           value="{{ old('name') }}" 
                                           required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="lg:col-span-6">
                                <div class="mb-3">
                                    <label for="slug" class="form-label">Slug</label>
                                    <input type="text" 
                                           class="form-control @error('slug') is-invalid @enderror" 
                                           id="slug" 
                                           name="slug" 
                                           value="{{ old('slug') }}" 
                                           placeholder="Automatisch gegenereerd">
                                    <div class="form-text">Laat leeg om automatisch te genereren op basis van de naam</div>
                                    @error('slug')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="lg:col-span-12">
                                <div class="mb-3">
                                    <label for="description" class="form-label">Beschrijving</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" 
                                              id="description" 
                                              name="description" 
                                              rows="4">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="lg:col-span-6">
                                <div class="mb-3">
                                    <label for="color" class="form-label">Kleur</label>
                                    <input type="color" 
                                           class="form-control @error('color') is-invalid @enderror" 
                                           id="color" 
                                           name="color" 
                                           value="{{ old('color', '#007bff') }}">
                                    @error('color')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="lg:col-span-6">
                                <div class="mb-3">
                                    <label for="icon" class="form-label">Icoon</label>
                                    <input type="text" 
                                           class="form-control @error('icon') is-invalid @enderror" 
                                           id="icon" 
                                           name="icon" 
                                           value="{{ old('icon') }}" 
                                           placeholder="bijv. fas fa-briefcase">
                                    <div class="form-text">FontAwesome icoon class (bijv. fas fa-briefcase)</div>
                                    @error('icon')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="lg:col-span-6">
                                <div class="mb-3">
                                    <label for="group" class="form-label">Groep</label>
                                    <input type="text" 
                                           class="form-control @error('group') is-invalid @enderror" 
                                           id="group" 
                                           name="group" 
                                           value="{{ old('group') }}" 
                                           placeholder="bijv. Vacatures">
                                    <div class="form-text">Groep om categorieën te organiseren</div>
                                    @error('group')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="lg:col-span-6">
                                <div class="mb-3">
                                    <label for="sort_order" class="form-label">Sorteervolgorde</label>
                                    <input type="number" 
                                           class="form-control @error('sort_order') is-invalid @enderror" 
                                           id="sort_order" 
                                           name="sort_order" 
                                           value="{{ old('sort_order', 0) }}" 
                                           min="0">
                                    <div class="form-text">Lager nummer = hoger in de lijst</div>
                                    @error('sort_order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="lg:col-span-6">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="is_active" 
                                               name="is_active" 
                                               value="1" 
                                               {{ old('is_active', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            Actief
                                        </label>
                                    </div>
                                    <div class="form-text">Deze categorie is beschikbaar voor gebruik</div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.categories.index') }}" class="kt-btn kt-btn-outline">
                                <i class="fas fa-times me-2"></i> Annuleren
                            </a>
                            <button type="submit" class="kt-btn kt-btn-primary">
                                <i class="fas fa-save me-2"></i> Opslaan
                            </button>
                        </div>
                    </form>
                </div>
    </div>
</div>
@endsection
