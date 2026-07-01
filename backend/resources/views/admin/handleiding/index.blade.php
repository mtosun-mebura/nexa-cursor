@extends('admin.layouts.app')

@push('styles')
@include('admin.handleiding.partials.styles')
@endpush

@section('title', 'Handleiding')

@section('content')
<div class="grid gap-5 lg:gap-7.5 lg:grid-cols-[minmax(0,17rem)_minmax(0,1fr)] items-start">
    <aside class="handleiding-layout-aside lg:sticky lg:top-24">
        @include('admin.handleiding.partials.nav', ['pages' => $pages, 'activeSlug' => null])
    </aside>
    <div class="handleiding-layout-main min-w-0">
        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="kt-card-title text-2xl mb-1">Handleiding</h1>
                    <p class="text-sm text-muted-foreground mb-0">
                        Stap-voor-stap uitleg van Nexa, met schermvoorbeelden en praktische tips.
                    </p>
                </div>
                <span class="kt-badge kt-badge-light shrink-0">{{ count($pages) }} {{ count($pages) === 1 ? 'onderwerp' : 'onderwerpen' }}</span>
            </div>
            <div class="kt-card-content">
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach($pages as $pageSlug => $pageItem)
                        <a href="{{ route('admin.handleiding.show', $pageSlug) }}"
                           class="group flex flex-col rounded-xl border border-border bg-muted/10 p-5 transition-all hover:border-primary/40 hover:bg-accent/30 hover:shadow-sm">
                            <div class="mb-3 flex items-center gap-3">
                                <span class="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                    <i class="ki-filled {{ $pageItem['icon'] ?? 'ki-document' }} text-lg"></i>
                                </span>
                                @if(!empty($pageItem['estimated_minutes']))
                                    <span class="text-xs text-muted-foreground ms-auto">± {{ $pageItem['estimated_minutes'] }} min</span>
                                @endif
                            </div>
                            <h2 class="text-base font-semibold text-foreground group-hover:text-primary mb-1">{{ $pageItem['title'] }}</h2>
                            <p class="text-sm text-muted-foreground mb-4 grow">{{ $pageItem['summary'] ?? '' }}</p>
                            <span class="text-sm font-medium text-primary inline-flex items-center gap-1">
                                Lezen
                                <i class="ki-filled ki-arrow-right text-xs transition-transform group-hover:translate-x-0.5"></i>
                            </span>
                        </a>
                    @endforeach
                </div>

                <div class="handleiding-tip mt-6">
                    <strong class="text-foreground">Tip:</strong> deze handleiding wordt uitgebreid. Heeft u een onderwerp gemist?
                    Laat het weten aan uw beheerder — nieuwe pagina's worden hier toegevoegd.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
