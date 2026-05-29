@if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite(['resources/css/app.css', 'resources/js/frontend-app.js'])
@else
    @php
        $viteCss = glob(public_path('build/assets/app-*.css')) ?: [];
        $viteJs = glob(public_path('build/assets/frontend-app-*.js'))
            ?: glob(public_path('build/assets/app-*.js'))
            ?: [];
    @endphp
    @foreach ($viteCss as $cssPath)
        <link rel="stylesheet" href="{{ asset('build/assets/'.basename($cssPath)) }}">
    @endforeach
    @foreach ($viteJs as $jsPath)
        <script type="module" src="{{ asset('build/assets/'.basename($jsPath)) }}"></script>
    @endforeach
@endif
