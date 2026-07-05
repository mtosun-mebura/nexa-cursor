@extends('admin.layouts.app')

@push('styles')
@include('admin.handleiding.partials.styles')
@endpush

@section('title', $page['title'])

@section('content')
<div class="grid gap-5 lg:gap-7.5 lg:grid-cols-[minmax(0,17rem)_minmax(0,1fr)] items-start">
    <aside class="handleiding-layout-aside lg:sticky lg:top-24">
        @include('admin.handleiding.partials.nav', ['pages' => $pages, 'activeSlug' => $slug])
    </aside>
    <div class="handleiding-layout-main min-w-0">
        <article class="kt-card w-full min-w-0">
            <div class="kt-card-header border-b border-border">
                <div class="flex flex-wrap items-start gap-3 w-full">
                    <span class="flex size-11 items-center justify-center rounded-xl bg-primary/10 text-primary shrink-0">
                        <i class="ki-filled {{ $page['icon'] ?? 'ki-document' }} text-xl"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs uppercase tracking-wide text-muted-foreground mb-1">Handleiding</p>
                        <h1 class="kt-card-title text-2xl mb-1">{{ $page['title'] }}</h1>
                        @if(!empty($page['summary']))
                            <p class="text-sm text-muted-foreground mb-0">{{ $page['summary'] }}</p>
                        @endif
                    </div>
                    @if(!empty($page['estimated_minutes']))
                        <span class="kt-badge kt-badge-light shrink-0">± {{ $page['estimated_minutes'] }} min</span>
                    @endif
                </div>
            </div>
            <div class="kt-card-content handleiding-prose max-w-none">
                @include('admin.handleiding.pages.' . $slug)
            </div>
        </article>
    </div>
</div>
@endsection
