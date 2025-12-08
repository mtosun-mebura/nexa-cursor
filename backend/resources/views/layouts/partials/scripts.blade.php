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

<!-- Stack for page-specific scripts -->
@stack('scripts')
<!-- End of Scripts -->
