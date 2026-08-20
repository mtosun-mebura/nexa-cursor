{{-- Verborgen voor bezoekers; bots vullen dit vaak in. Inline CSS zodat het niet van Tailwind afhangt. --}}
<div class="public-form-honeypot" aria-hidden="true" style="position:absolute!important;left:-10000px!important;top:auto!important;width:1px!important;height:1px!important;overflow:hidden!important;opacity:0!important;pointer-events:none!important;">
    <label for="{{ $honeypotId }}">Bedrijfswebsite</label>
    <input type="text"
           id="{{ $honeypotId }}"
           name="{{ \App\Services\PublicFormProtection::HONEYPOT_FIELD }}"
           value=""
           tabindex="-1"
           autocomplete="off">
</div>
