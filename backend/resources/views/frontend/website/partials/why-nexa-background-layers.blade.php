@if(($whyBg['light_image_url'] ?? '') !== '')
<div class="absolute inset-0 bg-cover bg-center bg-no-repeat pointer-events-none dark:hidden" style="background-image: url({{ json_encode($whyBg['light_image_url']) }});" aria-hidden="true"></div>
@endif
@if(($whyBg['dark_image_url'] ?? '') !== '')
<div class="absolute inset-0 hidden dark:block bg-cover bg-center bg-no-repeat pointer-events-none" style="background-image: url({{ json_encode($whyBg['dark_image_url']) }});" aria-hidden="true"></div>
@endif
