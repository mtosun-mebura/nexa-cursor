@php
    $sectionTitle = $sectionData['title'] ?? 'Informatie aanvragen';
    $template = $emailTemplate ?? null;
    $inputClass = 'w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-500 dark:focus:border-blue-500';
    $formFields = $formFields ?? collect();
    $hasFields = $formFields->isNotEmpty();
    $successTitle = \App\Models\GeneralSetting::get('info_request_success_title', 'Uw bericht is verstuurd. We nemen zo snel mogelijk contact met u op.');
    $successSubtitle = \App\Models\GeneralSetting::get('info_request_success_subtitle', 'Er wordt binnenkort contact met u opgenomen.');
    $successFooter = \App\Models\GeneralSetting::get('info_request_success_footer', 'Uw bericht is succesvol verzonden.');
    $successTextsEnabled = \App\Models\GeneralSetting::get('info_request_success_texts_enabled', '1') === '1';
    $successImagePath = \App\Models\GeneralSetting::get('info_request_success_image');
    $successSize = (int) \App\Models\GeneralSetting::get('info_request_success_icon_size', '80');
    $successImageSizePercent = (int) \App\Models\GeneralSetting::get('info_request_success_image_size_percent', '80');
    if ($successImageSizePercent < 10) {
        $successImageSizePercent = 10;
    }
    if ($successImageSizePercent > 100) {
        $successImageSizePercent = 100;
    }
    if ($successSize < 32) $successSize = 32;
    if ($successSize > 200) $successSize = 200;
    $hasSuccessImage = $successImagePath && \Storage::disk('public')->exists($successImagePath);
    $embeddedInTextBlock = $embeddedInTextBlock ?? false;
    $infoRequestAction = \Illuminate\Support\Facades\Route::has('frontend.send-info-request')
        ? route('frontend.send-info-request')
        : '#';
    $infoRequestFormTimeFields = \App\Http\Controllers\Frontend\InfoRequestController::formTimeFields();
@endphp
@if($template)
<section id="info-request-section-{{ $sectionKey }}" class="info-request-section {{ $embeddedInTextBlock ? 'w-full min-w-0 pt-0 pb-16 md:pb-20' : 'py-16 md:py-20' }}">
    <style>
        .info-request-section .info-req-animate-left,
        .info-request-section .info-req-animate-right,
        .info-request-section .info-req-animate-bottom {
            opacity: 0;
            transition: opacity 0.55s ease-out, transform 0.55s ease-out;
        }
        .info-request-section .info-req-animate-left { transform: translateX(-2rem); transition-delay: 0ms; }
        .info-request-section .info-req-animate-right { transform: translateX(2rem); transition-delay: 80ms; }
        .info-request-section .info-req-animate-bottom { transform: translateY(2rem); transition-delay: 160ms; }
        .info-request-section.in-view .info-req-animate-left,
        .info-request-section.in-view .info-req-animate-right,
        .info-request-section.in-view .info-req-animate-bottom {
            opacity: 1;
            transform: translate(0);
        }
        .info-request-section .info-request-input-wrap .info-request-field-status {
            top: 50%;
            transform: translateY(-50%);
        }
        .info-request-section .info-request-input-wrap--textarea .info-request-field-status {
            top: 0.75rem;
            transform: none;
        }
        .info-request-section .info-request-input.info-request-input--valid:focus {
            --tw-ring-color: rgb(34 197 94 / 0.45);
        }
        .info-request-section .info-request-textarea {
            resize: vertical;
            overflow: auto;
            scrollbar-width: thin;
            scrollbar-color: rgb(156 163 175) transparent;
        }
        .dark .info-request-section .info-request-textarea {
            scrollbar-color: rgb(107 114 128) transparent;
        }
        .info-request-section .info-request-textarea::-webkit-scrollbar {
            width: 8px;
        }
        .info-request-section .info-request-textarea::-webkit-scrollbar-track {
            background: transparent;
        }
        .info-request-section .info-request-textarea::-webkit-scrollbar-thumb {
            background-color: rgb(156 163 175);
            border-radius: 9999px;
            border: 2px solid transparent;
            background-clip: content-box;
        }
        .dark .info-request-section .info-request-textarea::-webkit-scrollbar-thumb {
            background-color: rgb(107 114 128);
        }
        .info-request-section .info-request-textarea::-webkit-scrollbar-thumb:hover {
            background-color: rgb(107 114 128);
        }
        .dark .info-request-section .info-request-textarea::-webkit-scrollbar-thumb:hover {
            background-color: rgb(156 163 175);
        }
        .info-request-section .info-request-char-count--limit {
            color: rgb(220 38 38);
        }
        .dark .info-request-section .info-request-char-count--limit {
            color: rgb(248 113 113);
        }
        .info-request-section .info-request-success-image {
            border-radius: 0.5rem;
        }
    </style>
    <div class="{{ $embeddedInTextBlock ? 'w-full' : 'website-section-inner' }}">
        <div class="{{ $embeddedInTextBlock ? 'w-full' : 'w-full max-w-full sm:max-w-3xl mx-auto' }}">
            <div id="info-request-intro-{{ $sectionKey }}" class="info-request-intro {{ session('info_request_sent') ? 'hidden' : '' }}">
                @if($sectionTitle)
                <h2 class="info-req-animate-left text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-6 text-center">{{ $sectionTitle }}</h2>
                @endif
                <p class="info-req-animate-right text-gray-600 dark:text-gray-300 mb-6 text-center">Vul het formulier in en wij nemen contact met u op.</p>
            </div>
            <form id="info-request-form-{{ $sectionKey }}" action="{{ $infoRequestAction }}" method="POST" data-info-request-form class="info-req-animate-bottom space-y-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4 sm:p-6 shadow-sm {{ session('info_request_sent') ? 'hidden' : '' }}" novalidate>
                @csrf
                <input type="hidden" name="template_id" value="{{ $template->id }}">
                {{-- Honeypot: verborgen voor bezoekers, bots vullen dit vaak in --}}
                <div class="absolute -left-[9999px] w-px h-px overflow-hidden opacity-0 pointer-events-none" aria-hidden="true">
                    <label for="info-request-website-{{ $sectionKey }}">Website (laat leeg)</label>
                    <input type="text" id="info-request-website-{{ $sectionKey }}" name="company_website" value="" tabindex="-1" autocomplete="off">
                </div>
                <input type="hidden" name="form_time" value="{{ $infoRequestFormTimeFields['form_time'] }}">
                <input type="hidden" name="form_time_token" value="{{ $infoRequestFormTimeFields['form_time_token'] }}">
                @if($hasFields)
                    @php
                        $firstTwo = $formFields->take(2);
                        $rest = $formFields->slice(2);
                    @endphp
                    @if($firstTwo->count() === 2)
                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach($firstTwo as $field)
                                @include('frontend.website.components.partials.info-request-form-field', ['field' => $field, 'sectionKey' => $sectionKey, 'inputClass' => $inputClass, 'emailTemplate' => $template])
                            @endforeach
                        </div>
                    @else
                        @foreach($firstTwo as $field)
                            @include('frontend.website.components.partials.info-request-form-field', ['field' => $field, 'sectionKey' => $sectionKey, 'inputClass' => $inputClass, 'emailTemplate' => $template])
                        @endforeach
                    @endif
                    @foreach($rest as $field)
                        @include('frontend.website.components.partials.info-request-form-field', ['field' => $field, 'sectionKey' => $sectionKey, 'inputClass' => $inputClass, 'emailTemplate' => $template])
                    @endforeach
                @else
                    {{-- Fallback: vaste velden --}}
                    <div class="grid gap-4 sm:grid-cols-2">
                        @include('frontend.website.components.partials.info-request-form-field', ['name' => 'voornaam', 'label' => 'Voornaam', 'required' => true, 'validationRule' => 'text', 'sectionKey' => $sectionKey, 'inputClass' => $inputClass])
                        @include('frontend.website.components.partials.info-request-form-field', ['name' => 'achternaam', 'label' => 'Achternaam', 'required' => true, 'validationRule' => 'text', 'sectionKey' => $sectionKey, 'inputClass' => $inputClass])
                    </div>
                    @include('frontend.website.components.partials.info-request-form-field', ['name' => 'email', 'label' => 'E-mailadres', 'required' => true, 'validationRule' => 'email', 'sectionKey' => $sectionKey, 'inputClass' => $inputClass])
                    @include('frontend.website.components.partials.info-request-form-field', ['name' => 'telefoon', 'label' => 'Telefoonnummer', 'required' => true, 'validationRule' => 'tel', 'sectionKey' => $sectionKey, 'inputClass' => $inputClass])
                    @include('frontend.website.components.partials.info-request-form-field', ['name' => 'omschrijving', 'label' => 'Omschrijving / vraag', 'required' => true, 'validationRule' => 'textarea', 'isTextarea' => true, 'sectionKey' => $sectionKey, 'inputClass' => $inputClass])
                @endif
                <div class="pt-2">
                    <button type="submit" class="info-request-submit-btn w-full sm:w-auto inline-flex justify-center items-center font-medium rounded-lg px-5 py-2.5 bg-blue-600 text-white hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        Versturen
                    </button>
                </div>
            </form>
            <div id="info-request-success-{{ $sectionKey }}" class="{{ session('info_request_sent') ? '' : 'mt-6' }} {{ session('info_request_sent') ? '' : 'hidden' }}" role="status">
                <div class="flex flex-col items-center justify-center text-center">
                    @if($hasSuccessImage)
                        <div class="mb-4" aria-hidden="true">
                            <img src="{{ app(\App\Services\WebsiteBuilderService::class)->storageUrlToDisplayUrl('/storage/'.$successImagePath) }}" alt="" class="info-request-success-image h-auto w-auto object-contain max-w-full rounded-lg" style="width: {{ $successImageSizePercent }}%;">
                        </div>
                    @else
                        <span class="inline-flex items-center justify-center rounded-full bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400 mb-4" style="width: {{ $successSize }}px; height: {{ $successSize }}px;" aria-hidden="true">
                            <svg class="shrink-0" style="width: {{ (int)($successSize * 0.5) }}px; height: {{ (int)($successSize * 0.5) }}px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </span>
                    @endif
                    @if($successTextsEnabled)
                    <p class="info-request-success-message text-lg font-medium text-gray-900 dark:text-white">@if(session('info_request_sent')){{ $successTitle }}@endif</p>
                    <p class="info-request-success-subtitle mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $successSubtitle }}</p>
                    <p class="info-request-success-footer mt-1 text-sm text-gray-500 dark:text-gray-500">{{ $successFooter }}</p>
                    @endif
                </div>
            </div>
            <p id="info-request-error-{{ $sectionKey }}" class="mt-4 text-center text-red-600 dark:text-red-400 font-medium hidden" role="alert"></p>
            @if($errors->any())
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var form = document.getElementById('info-request-form-{{ $sectionKey }}');
                    if (form && form.querySelector('.border-red-500')) {
                        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            </script>
            @endif
            <script>
                (function () {
                    var form = document.getElementById('info-request-form-{{ $sectionKey }}');
                    if (!form) return;
                    var successEl = document.getElementById('info-request-success-{{ $sectionKey }}');
                    var introEl = document.getElementById('info-request-intro-{{ $sectionKey }}');
                    var errorEl = document.getElementById('info-request-error-{{ $sectionKey }}');
                    var submitBtn = form.querySelector('.info-request-submit-btn');
                    if (window.NexaInfoRequestFormValidation && !form._infoRequestValidation) {
                        form._infoRequestValidation = window.NexaInfoRequestFormValidation.init(form);
                    }
                    var validation = form._infoRequestValidation;

                    function clearErrors() {
                        errorEl.classList.add('hidden');
                        errorEl.textContent = '';
                        if (validation) {
                            validation.clearValidation();
                        }
                    }

                    function showFieldErrors(errors) {
                        clearErrors();
                        if (validation) {
                            validation.showServerErrors(errors);
                            return;
                        }
                        Object.keys(errors).forEach(function (name) {
                            var msg = Array.isArray(errors[name]) ? errors[name][0] : errors[name];
                            var span = form.querySelector('.form-field-error[data-field="' + name + '"]');
                            var input = form.querySelector('[name="' + name + '"]');
                            if (span) span.textContent = msg;
                            if (input) {
                                input.classList.add('border-red-500', 'dark:border-red-500');
                            }
                        });
                    }

                    function refreshFormTimeFields(data) {
                        if (!data || data.form_time === undefined || !data.form_time_token) {
                            return;
                        }
                        var timeInput = form.querySelector('[name="form_time"]');
                        var tokenInput = form.querySelector('[name="form_time_token"]');
                        if (timeInput) timeInput.value = data.form_time;
                        if (tokenInput) tokenInput.value = data.form_time_token;
                    }

                    form.addEventListener('submit', function (e) {
                        e.preventDefault();
                        if (validation && !validation.validateAll()) {
                            var firstInvalid = form.querySelector('.info-request-icon-invalid:not(.hidden)');
                            if (firstInvalid) {
                                var invalidField = firstInvalid.closest('.info-request-field');
                                if (invalidField) {
                                    invalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                }
                            }
                            return;
                        }
                        clearErrors();
                        successEl.classList.add('hidden');
                        submitBtn.disabled = true;
                        var body = new FormData(form);
                        fetch(form.action, {
                            method: 'POST',
                            body: body,
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        }).then(function (res) {
                            return res.json().then(function (data) {
                                if (res.ok && data.success) {
                                    var msgEl = successEl.querySelector('.info-request-success-message');
                                    if (msgEl) msgEl.textContent = data.message || 'Uw bericht is succesvol verzonden.';
                                    if (introEl) introEl.classList.add('hidden');
                                    form.classList.add('hidden');
                                    successEl.classList.remove('hidden', 'mt-6');
                                    form.reset();
                                } else if (res.status === 422 && data.errors) {
                                    showFieldErrors(data.errors);
                                } else {
                                    errorEl.textContent = data.message || 'Er is een fout opgetreden. Probeer het later opnieuw.';
                                    errorEl.classList.remove('hidden');
                                    refreshFormTimeFields(data);
                                }
                            }).catch(function () {
                                errorEl.textContent = 'Er is een fout opgetreden. Probeer het later opnieuw.';
                                errorEl.classList.remove('hidden');
                            });
                        }).catch(function () {
                            errorEl.textContent = 'Er is een fout opgetreden. Probeer het later opnieuw.';
                            errorEl.classList.remove('hidden');
                        }).finally(function () {
                            submitBtn.disabled = false;
                        });
                    });
                })();
            </script>
            <script>
                (function () {
                    var section = document.getElementById('info-request-section-{{ $sectionKey }}');
                    if (!section) return;
                    var opts = { rootMargin: '0px 0px -60px 0px', threshold: 0.1 };
                    if (typeof window.nexaObserveWhenVisible === 'function') {
                        window.nexaObserveWhenVisible(section, function () {
                            section.classList.add('in-view');
                        }, opts);
                        return;
                    }
                    var observer = new IntersectionObserver(function (entries) {
                        entries.forEach(function (entry) {
                            if (entry.isIntersecting) {
                                section.classList.add('in-view');
                            }
                        });
                    }, opts);
                    observer.observe(section);
                })();
            </script>
        </div>
    </div>
</section>
@endif
