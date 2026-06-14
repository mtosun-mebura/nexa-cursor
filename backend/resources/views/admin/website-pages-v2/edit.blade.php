@extends('admin.layouts.app')

@section('title', 'Page Builder v2 – '.$page->title)

@push('styles')
<style>
    #content .kt-container-fixed {
        max-width: none;
        padding-left: 0;
        padding-right: 0;
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
