@extends('admin.layouts.app')

@section('title', 'Metronic demo1 (Vue playground)')

@push('styles')
    <link rel="stylesheet" href="{{ asset('metronic-v9.4.13/demo1/assets/vendors/keenicons/styles.bundle.css') }}">
    <link rel="stylesheet" href="{{ asset('metronic-v9.4.13/demo1/assets/css/styles.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('metronic-v9.4.13/demo1/assets/vendors/ktui/ktui.min.js') }}"></script>
    <script src="{{ asset('metronic-v9.4.13/demo1/assets/js/core.bundle.js') }}"></script>
    @vite('resources/js/metronic-vue-demo1.ts')
@endpush

@section('content')
    <div class="kt-container-fixed py-6">
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    Metronic demo1 Vue playground
                </h3>
                <div class="kt-card-toolbar">
                    <span class="kt-badge kt-badge-info">super-admin only</span>
                </div>
            </div>
            <div class="kt-card-content">
                <div id="metronic-vue-demo1-app"></div>
            </div>
        </div>
    </div>
@endsection

