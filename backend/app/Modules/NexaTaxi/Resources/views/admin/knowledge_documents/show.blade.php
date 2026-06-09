@extends('admin.layouts.app')

@section('title', $document->title)

@section('content')
@php
    $canEditDocument = auth()->user()->hasRole('super-admin') || auth()->user()->can('ai_chatbot.update') || auth()->user()->can('rides.update') || auth()->user()->can('vehicles.update');
@endphp

@if(session('success'))
    <div class="kt-alert kt-alert-success mb-5" role="alert">
        <i class="ki-filled ki-check-circle me-2"></i> {{ session('success') }}
    </div>
@endif

<style>
    .hero-bg {
        background-image: url('{{ asset('assets/media/images/2600x1200/bg-1.png') }}');
    }
    .dark .hero-bg {
        background-image: url('{{ asset('assets/media/images/2600x1200/bg-1-dark.png') }}');
    }
</style>

<div class="bg-center bg-cover bg-no-repeat hero-bg">
    <div class="kt-container-fixed min-w-0">
        <div class="flex flex-col items-center gap-2 lg:gap-3.5 py-4 lg:pt-5 lg:pb-10 px-3">
            <div class="rounded-lg border-3 border-primary h-[100px] w-[100px] lg:h-[120px] lg:w-[120px] shrink-0 flex items-center justify-center bg-primary/10 text-primary">
                <i class="ki-filled ki-technology-2 text-3xl lg:text-4xl"></i>
            </div>

            <div class="text-xl lg:text-2xl leading-6 font-semibold text-mono text-center">
                {{ $document->title }}
            </div>

            <div class="flex flex-wrap justify-center gap-1 lg:gap-4.5 text-sm max-w-full">
                <div class="flex gap-1.25 items-center">
                    <i class="ki-filled ki-category text-muted-foreground text-sm shrink-0"></i>
                    <span class="kt-badge kt-badge-outline kt-badge-sm">
                        {{ $categoryLabels[$document->category] ?? $document->category }}
                    </span>
                </div>
                <div class="flex gap-1.25 items-center">
                    <i class="ki-filled ki-calendar text-muted-foreground text-sm shrink-0"></i>
                    <span class="text-secondary-foreground font-medium">
                        Aangemaakt {{ $document->created_at?->format('d-m-Y H:i') ?? '—' }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="kt-container-fixed min-w-0">
    <div class="flex items-center flex-wrap md:flex-nowrap lg:items-center justify-between gap-3 lg:gap-6 mb-5 lg:mb-10">
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.taxi.knowledge_documents.index') }}" class="kt-btn kt-btn-outline shrink-0">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
        @if($canEditDocument)
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.taxi.knowledge_documents.edit', $document) }}" class="kt-btn kt-btn-primary shrink-0">
                <i class="ki-filled ki-notepad-edit me-2"></i>
                Bewerken
            </a>
        </div>
        @endif
    </div>

    <div class="kt-card w-full min-w-0">
        <div class="kt-card-header">
            <h3 class="kt-card-title mb-0">Inhoud</h3>
        </div>
        <div class="kt-card-content px-3 sm:px-5 py-5 min-w-0">
            <div class="knowledge-document-content text-foreground text-sm leading-relaxed break-words">{!! $document->content !!}</div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    #content .knowledge-document-content {
        background-color: transparent;
    }

    #content .knowledge-document-content :is(p, ul, ol) {
        margin-bottom: 0.75rem;
    }

    #content .knowledge-document-content a {
        color: var(--primary);
        text-decoration: underline;
    }
</style>
@endpush
