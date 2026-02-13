@extends('frontend.layouts.website')

@section('title', $page->title . ' - ' . ($branding['site_name'] ?? config('app.name')))
@section('description', $page->meta_description ?? Str::limit(strip_tags($page->content), 160))

@section('content')
@php
    $useSectionLayout = $useModernHomeLayout ?? false;
    $isAtomV2 = ($themeSlug ?? '') === 'atom-v2';
    $isNextlyTemplate = ($themeSlug ?? '') === 'nextly-template';
    $isNextLandingVpn = ($themeSlug ?? '') === 'next-landing-vpn';
@endphp
@if($useSectionLayout)
    @if($isAtomV2)
        @include('frontend.website.partials.atom-v2-home')
    @elseif($isNextlyTemplate)
        @include('frontend.website.partials.nextly-home')
    @elseif($isNextLandingVpn)
        @include('frontend.website.partials.next-landing-vpn-home')
    @else
        {{-- Modern thema: hero, stats, Waarom Nexa, Wat Wij Bieden, vacatures, CTA (of alleen hero+footer voor niet-home) --}}
        @include('frontend.website.partials.modern-home')
    @endif
@else
<div class="container-custom py-10 md:py-16">
    <article class="w-full max-w-7xl mx-auto">
        <h1 class="kt-page-title text-gray-900 dark:text-white mb-6" style="font-family: var(--theme-font-heading);">{{ $page->title }}</h1>
        @php $blocks = $page->getContentBlocks(); @endphp
        @if($blocks !== null)
            <div class="grid grid-cols-12 gap-4 md:gap-6">
                @foreach($blocks as $block)
                    @php
                        $type = $block['type'] ?? 'paragraph';
                        $width = $block['width'] ?? 'full';
                        $colClass = match($width) {
                            'half' => 'col-span-12 md:col-span-6',
                            'third' => 'col-span-12 md:col-span-4',
                            default => 'col-span-12',
                        };
                    @endphp
                    <div class="{{ $colClass }} w-full min-w-0">
                        @if(view()->exists('frontend.website.blocks.' . $type))
                            @include('frontend.website.blocks.' . $type, ['block' => $block])
                        @else
                            @include('frontend.website.blocks.paragraph', ['block' => $block])
                        @endif
                    </div>
                @endforeach
            </div>
        @elseif($page->content)
            <div class="prose prose-lg dark:prose-invert max-w-none">
                {!! $page->content !!}
            </div>
        @endif

        @if($showContactForm ?? false)
            <div class="mt-12 pt-12 border-t border-gray-200 dark:border-gray-700">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Stuur ons een bericht</h2>
                @include('frontend.website.partials.contact-form')
            </div>
        @endif
    </article>
</div>
@endif
@endsection
