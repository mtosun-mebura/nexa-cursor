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
    if ($successSize < 32) $successSize = 32;
    if ($successSize > 200) $successSize = 200;
    $hasSuccessImage = $successImagePath && \Storage::disk('public')->exists($successImagePath);
    $embeddedInTextBlock = $embeddedInTextBlock ?? false;
    $infoRequestAction = \Illuminate\Support\Facades\Route::has('frontend.send-info-request')
        ? route('frontend.send-info-request')
        : '#';
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
    </style>
    <div class="{{ $embeddedInTextBlock ? 'w-full' : 'container-custom' }}">
        <div class="{{ $embeddedInTextBlock ? 'w-full' : 'max-w-3xl mx-auto' }}">
            @if($sectionTitle)
            <h2 class="info-req-animate-left text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-6 text-center">{{ $sectionTitle }}</h2>
            @endif
            <p class="info-req-animate-right text-gray-600 dark:text-gray-300 mb-6 text-center">Vul het formulier in en wij nemen contact met u op.</p>
            <form id="info-request-form-{{ $sectionKey }}" action="{{ $infoRequestAction }}" method="POST" class="info-req-animate-bottom space-y-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-6 shadow-sm {{ session('info_request_sent') ? 'hidden' : '' }}" novalidate>
                @csrf
                <input type="hidden" name="template_id" value="{{ $template->id }}">
                {{-- Honeypot: verborgen voor bezoekers, bots vullen dit vaak in --}}
                <div class="absolute -left-[9999px] w-px h-px overflow-hidden opacity-0 pointer-events-none" aria-hidden="true">
                    <label for="info-request-website-{{ $sectionKey }}">Website (laat leeg)</label>
                    <input type="text" id="info-request-website-{{ $sectionKey }}" name="company_website" value="" tabindex="-1" autocomplete="off">
                </div>
                <input type="hidden" name="form_time_token" value="{{ \Illuminate\Support\Facades\Crypt::encryptString((string) now()->timestamp) }}">
                @if($hasFields)
                    @php
                        $firstTwo = $formFields->take(2);
                        $rest = $formFields->slice(2);
                    @endphp
                    @if($firstTwo->count() === 2)
                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach($firstTwo as $field)
                                <div>
                                    <label for="email-template-{{ $field->name }}-{{ $sectionKey }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $field->label }}{{ $field->is_required ? ' *' : '' }}</label>
                                    <input type="{{ $field->validation_rule === 'email' ? 'email' : 'text' }}" id="email-template-{{ $field->name }}-{{ $sectionKey }}" name="{{ $field->name }}" value="{{ old($field->name) }}" {{ $field->is_required ? 'required' : '' }} class="{{ $inputClass }} @error($field->name) border-red-500 dark:border-red-500 @enderror">
                                    <span class="form-field-error block text-sm text-red-600 dark:text-red-400 mt-1" data-field="{{ $field->name }}" role="alert">@error($field->name){{ $message }}@enderror</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        @foreach($firstTwo as $field)
                            <div>
                                <label for="email-template-{{ $field->name }}-{{ $sectionKey }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $field->label }}{{ $field->is_required ? ' *' : '' }}</label>
                                <input type="{{ $field->validation_rule === 'email' ? 'email' : 'text' }}" id="email-template-{{ $field->name }}-{{ $sectionKey }}" name="{{ $field->name }}" value="{{ old($field->name) }}" {{ $field->is_required ? 'required' : '' }} class="{{ $inputClass }} @error($field->name) border-red-500 dark:border-red-500 @enderror">
                                <span class="form-field-error block text-sm text-red-600 dark:text-red-400 mt-1" data-field="{{ $field->name }}" role="alert">@error($field->name){{ $message }}@enderror</span>
                            </div>
                        @endforeach
                    @endif
                    @foreach($rest as $field)
                        <div>
                            <label for="email-template-{{ $field->name }}-{{ $sectionKey }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $field->label }}{{ $field->is_required ? ' *' : '' }}</label>
                            @if(in_array($field->validation_rule, [null, ''], true) && str_contains(strtolower($field->label), 'omschrijving'))
                                <textarea id="email-template-{{ $field->name }}-{{ $sectionKey }}" name="{{ $field->name }}" rows="5" {{ $field->is_required ? 'required' : '' }} class="{{ $inputClass }} @error($field->name) border-red-500 dark:border-red-500 @enderror">{{ old($field->name) }}</textarea>
                            @else
                                <input type="{{ $field->validation_rule === 'email' ? 'email' : 'text' }}" id="email-template-{{ $field->name }}-{{ $sectionKey }}" name="{{ $field->name }}" value="{{ old($field->name) }}" {{ $field->is_required ? 'required' : '' }} class="{{ $inputClass }} @error($field->name) border-red-500 dark:border-red-500 @enderror">
                            @endif
                            <span class="form-field-error block text-sm text-red-600 dark:text-red-400 mt-1" data-field="{{ $field->name }}" role="alert">@error($field->name){{ $message }}@enderror</span>
                        </div>
                    @endforeach
                @else
                    {{-- Fallback: vaste velden --}}
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="email-template-voornaam-{{ $sectionKey }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Voornaam *</label>
                            <input type="text" id="email-template-voornaam-{{ $sectionKey }}" name="voornaam" value="{{ old('voornaam') }}" required class="{{ $inputClass }} @error('voornaam') border-red-500 dark:border-red-500 @enderror">
                            <span class="form-field-error block text-sm text-red-600 dark:text-red-400 mt-1" data-field="voornaam" role="alert">@error('voornaam'){{ $message }}@enderror</span>
                        </div>
                        <div>
                            <label for="email-template-achternaam-{{ $sectionKey }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Achternaam *</label>
                            <input type="text" id="email-template-achternaam-{{ $sectionKey }}" name="achternaam" value="{{ old('achternaam') }}" required class="{{ $inputClass }} @error('achternaam') border-red-500 dark:border-red-500 @enderror">
                            <span class="form-field-error block text-sm text-red-600 dark:text-red-400 mt-1" data-field="achternaam" role="alert">@error('achternaam'){{ $message }}@enderror</span>
                        </div>
                    </div>
                    <div>
                        <label for="email-template-email-{{ $sectionKey }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">E-mailadres *</label>
                        <input type="email" id="email-template-email-{{ $sectionKey }}" name="email" value="{{ old('email') }}" required class="{{ $inputClass }} @error('email') border-red-500 dark:border-red-500 @enderror">
                        <span class="form-field-error block text-sm text-red-600 dark:text-red-400 mt-1" data-field="email" role="alert">@error('email'){{ $message }}@enderror</span>
                    </div>
                    <div>
                        <label for="email-template-telefoon-{{ $sectionKey }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Telefoonnummer</label>
                        <input type="text" id="email-template-telefoon-{{ $sectionKey }}" name="telefoon" value="{{ old('telefoon') }}" class="{{ $inputClass }} @error('telefoon') border-red-500 dark:border-red-500 @enderror">
                        <span class="form-field-error block text-sm text-red-600 dark:text-red-400 mt-1" data-field="telefoon" role="alert">@error('telefoon'){{ $message }}@enderror</span>
                    </div>
                    <div>
                        <label for="email-template-omschrijving-{{ $sectionKey }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Omschrijving / vraag *</label>
                        <textarea id="email-template-omschrijving-{{ $sectionKey }}" name="omschrijving" rows="5" required class="{{ $inputClass }} @error('omschrijving') border-red-500 dark:border-red-500 @enderror">{{ old('omschrijving') }}</textarea>
                        <span class="form-field-error block text-sm text-red-600 dark:text-red-400 mt-1" data-field="omschrijving" role="alert">@error('omschrijving'){{ $message }}@enderror</span>
                    </div>
                @endif
                <div class="pt-2">
                    <button type="submit" class="info-request-submit-btn w-full sm:w-auto inline-flex justify-center items-center font-medium rounded-lg px-5 py-2.5 bg-blue-600 text-white hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        Versturen
                    </button>
                </div>
            </form>
            <div id="info-request-success-{{ $sectionKey }}" class="mt-6 {{ session('info_request_sent') ? '' : 'hidden' }}" role="status">
                <div class="flex flex-col items-center justify-center text-center">
                    @if($hasSuccessImage)
                        <div class="mb-4" aria-hidden="true">
                            <img src="{{ app(\App\Services\WebsiteBuilderService::class)->storageUrlToDisplayUrl('/storage/'.$successImagePath) }}" alt="" class="h-[300px] w-auto object-contain max-w-full">
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
                    var errorEl = document.getElementById('info-request-error-{{ $sectionKey }}');
                    var submitBtn = form.querySelector('.info-request-submit-btn');
                    var errorBorderClass = 'border-red-500';
                    var darkErrorBorderClass = 'dark:border-red-500';

                    function clearErrors() {
                        errorEl.classList.add('hidden');
                        errorEl.textContent = '';
                        form.querySelectorAll('.form-field-error').forEach(function (span) { span.textContent = ''; });
                        form.querySelectorAll('[name]').forEach(function (input) {
                            input.classList.remove(errorBorderClass, darkErrorBorderClass);
                        });
                    }

                    function showFieldErrors(errors) {
                        clearErrors();
                        Object.keys(errors).forEach(function (name) {
                            var msg = Array.isArray(errors[name]) ? errors[name][0] : errors[name];
                            var span = form.querySelector('.form-field-error[data-field="' + name + '"]');
                            var input = form.querySelector('[name="' + name + '"]');
                            if (span) span.textContent = msg;
                            if (input) { input.classList.add(errorBorderClass, darkErrorBorderClass); }
                        });
                    }

                    form.addEventListener('submit', function (e) {
                        e.preventDefault();
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
                                    form.classList.add('hidden');
                                    successEl.classList.remove('hidden');
                                    form.reset();
                                } else if (res.status === 422 && data.errors) {
                                    showFieldErrors(data.errors);
                                } else {
                                    errorEl.textContent = data.message || 'Er is een fout opgetreden. Probeer het later opnieuw.';
                                    errorEl.classList.remove('hidden');
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
