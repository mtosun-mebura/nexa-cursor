@extends('admin.layouts.app')

@section('title', 'Page Builder v2 – '.$page->title)

@push('styles')
<style>
    /* Volledige hoogte: geen pagina-scroll, toolbar blijft zichtbaar */
    body:has(#website-page-builder-v2) {
        height: 100dvh;
        overflow: hidden;
    }

    body:has(#website-page-builder-v2) > .flex.grow,
    body:has(#website-page-builder-v2) .kt-wrapper {
        min-height: 0;
        overflow: hidden;
    }

    body:has(#website-page-builder-v2) main#content {
        overflow: visible;
        padding-top: 0;
        min-height: 0;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    body:has(#website-page-builder-v2) .kt-footer {
        display: none;
    }

    body:has(#website-page-builder-v2) #content .kt-container-fixed {
        max-width: none;
        padding: 0;
        flex: 1;
        min-height: 0;
        display: flex;
        flex-direction: column;
    }

    #website-page-builder-v2 {
        flex: 1;
        min-height: 0;
        display: flex;
        flex-direction: column;
    }
</style>
@endpush

@section('content')
<div class="kt-container-fixed min-w-0">
    <div id="website-page-builder-v2"></div>
</div>

<script>
    window.__WEBSITE_BUILDER_V2__ = @json($bootstrap);
</script>
@vite(['resources/js/website-page-builder-v2.ts'])
@endsection
