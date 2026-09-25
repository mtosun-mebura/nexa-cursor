{{--
  Zet html.dark vóór paint zodat de meld-pagina de actieve modus volgt.
  $flavor: 'admin' (kt-theme) | 'frontend' (theme / website-theme)
--}}
@php $flavor = $flavor ?? 'admin'; @endphp
<script>
(function () {
  var root = document.documentElement;
  if (!root) return;
  var mode = 'light';
  @if(($flavor ?? 'admin') === 'admin')
  if (localStorage.getItem('kt-theme')) {
    mode = localStorage.getItem('kt-theme');
  } else if (root.hasAttribute('data-kt-theme-mode')) {
    mode = root.getAttribute('data-kt-theme-mode') || 'light';
  }
  if (mode === 'system') {
    mode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }
  root.setAttribute('data-kt-theme-mode', mode === 'dark' ? 'dark' : 'light');
  @else
  var stored = localStorage.getItem('website-theme') || localStorage.getItem('theme');
  if (stored === 'dark') mode = 'dark';
  else if (stored === 'light') mode = 'light';
  else mode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  @endif
  root.classList.remove('light', 'dark');
  root.classList.add(mode === 'dark' ? 'dark' : 'light');
  root.style.colorScheme = mode === 'dark' ? 'dark' : 'light';
})();
</script>
