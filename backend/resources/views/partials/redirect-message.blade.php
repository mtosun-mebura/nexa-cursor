{{--
  Herbruikbare meld-pagina: toon bericht, countdown in seconden en link.
  Variabelen: $title, $message, $redirectUrl, $redirectLabel, $redirectSeconds (optioneel, default 5)
--}}
@php
  $seconds = (int) ($redirectSeconds ?? 5);
  $seconds = $seconds >= 1 ? $seconds : 5;
  $redirectUrl = $redirectUrl ?? url('/admin/login');
@endphp
<div class="redirect-card" id="redirect-message-container">
  @if(!empty($title))
    <p class="redirect-card__title" role="heading" aria-level="1">{{ $title }}</p>
  @endif
  <p class="redirect-card__text">{{ $message }}</p>
  <p class="redirect-card__countdown" id="countdown-text">
    Je wordt over <span id="countdown">{{ $seconds }}</span> seconden automatisch doorgestuurd.
  </p>
  <a href="{{ $redirectUrl }}" id="redirect-link" class="redirect-card__btn">
    {{ $redirectLabel }}
  </a>
</div>
<script>
(function() {
  var seconds = {{ $seconds }};
  var redirectUrl = {!! json_encode($redirectUrl) !!};
  var el = document.getElementById('countdown');
  function doRedirect() {
    try {
      window.location.replace(redirectUrl);
    } catch (e) {
      window.location.href = redirectUrl;
    }
  }
  if (!redirectUrl || typeof redirectUrl !== 'string') return;
  var interval = setInterval(function() {
    seconds--;
    if (el) el.textContent = Math.max(0, seconds);
    if (seconds <= 0) {
      clearInterval(interval);
      doRedirect();
    }
  }, 1000);
})();
</script>
