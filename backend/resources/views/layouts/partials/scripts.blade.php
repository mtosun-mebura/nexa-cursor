<!-- Scripts -->
<script src="{{ asset('assets/js/core.bundle.js') }}" data-navigate-once></script>
<script src="{{ asset('assets/vendors/ktui/ktui.min.js') }}" data-navigate-once></script>
<script src="{{ asset('assets/vendors/apexcharts/apexcharts.min.js') }}" data-navigate-once></script>
@if(isset($demoNumber))
<script src="{{ asset('assets/js/layouts/demo' . $demoNumber . '.js') }}" data-navigate-once></script>
@else
<script src="{{ asset('assets/js/layouts/demo1.js') }}" data-navigate-once></script>
@endif

<!-- Compiled App Scripts -->
@vite(['resources/js/app.js'])

<!-- Search Input Clear Button (for all admin pages) -->
<script src="{{ asset('assets/js/search-input-clear.js') }}"></script>

<!-- KT-Select Placeholder Styling (for consistent dropdown styling) -->
<script src="{{ asset('assets/js/kt-select-placeholder-styling.js') }}"></script>

<!-- Stack for page-specific scripts -->
@stack('scripts')
<!-- End of Scripts -->
