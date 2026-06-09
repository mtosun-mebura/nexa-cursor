@extends('admin.layouts.app')

@section('title', 'Nieuw kennisdocument')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono mb-3">Nieuw kennisdocument</h1>
        <a href="{{ route('admin.taxi.knowledge_documents.index') }}" class="kt-btn kt-btn-outline shrink-0 w-fit">
            <i class="ki-filled ki-arrow-left me-2"></i>Terug
        </a>
    </div>

    <form action="{{ route('admin.taxi.knowledge_documents.store') }}" method="POST" class="kt-card w-full min-w-0 knowledge-document-form">
        @csrf
        <div class="kt-card-content px-3 sm:px-5 py-5 grid gap-5">
            @include('taxi::admin.knowledge_documents.partials.form-fields')
            <div class="admin-form-actions flex flex-wrap items-center justify-end gap-2.5 w-full min-w-0">
                <a href="{{ route('admin.taxi.knowledge_documents.index') }}" class="kt-btn kt-btn-outline">Annuleren</a>
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-check me-2"></i>Opslaan
                </button>
            </div>
        </div>
    </form>
</div>
@include('taxi::admin.knowledge_documents.partials.form-wysiwyg-scripts')
@endsection
