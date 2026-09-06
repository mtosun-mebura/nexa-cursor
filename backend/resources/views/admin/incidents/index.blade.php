@extends('admin.layouts.app')

@section('title', $bootstrap['is_super_admin'] ? 'Incidenten' : 'Incident melden')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div id="incident-app" class="incident-app-root min-w-0"></div>
</div>
@endsection

@push('scripts')
<script>
    window.__INCIDENT_APP__ = @json($bootstrap);
</script>
@vite('resources/js/incident-app.ts')
@endpush
