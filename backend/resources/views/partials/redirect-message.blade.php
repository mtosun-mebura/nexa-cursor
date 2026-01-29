{{--
  Herbruikbare meld-pagina: toon bericht, 5 seconden countdown en link.
  Variabelen: $title, $message, $redirectUrl, $redirectLabel
--}}
<div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm max-w-md mx-auto text-center">
  <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ $title }}</h1>
  <p class="text-gray-600 dark:text-gray-400 mb-4">{{ $message }}</p>
  <p class="text-sm text-gray-500 dark:text-gray-500 mb-4" id="countdown-text">
    Je wordt over <span id="countdown">5</span> seconden automatisch doorgestuurd.
  </p>
  <a href="{{ $redirectUrl }}" id="redirect-link" class="inline-flex items-center justify-center rounded-lg px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white font-medium transition-colors">
    {{ $redirectLabel }}
  </a>
</div>
<script>
(function() {
  var seconds = 5;
  var redirectUrl = {{ json_encode($redirectUrl) }};
  var el = document.getElementById('countdown');
  var textEl = document.getElementById('countdown-text');
  var interval = setInterval(function() {
    seconds--;
    if (el) el.textContent = seconds;
    if (seconds <= 0) {
      clearInterval(interval);
      window.location.href = redirectUrl;
    }
  }, 1000);
})();
</script>
