@php
    $inputClass = 'w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-500 dark:focus:border-blue-500';
    $formFields = $formFields ?? collect();
    $hasFields = $formFields->isNotEmpty();
    $sectionKey = 'preview';
@endphp
{{-- Zelfde structuur als frontend.website.components.email-template-section (1-op-1 weergave) --}}
<div class="admin-form-preview-shell rounded-xl border border-border bg-muted/30 p-3 sm:p-5">
    <div class="admin-form-preview-frame w-full max-w-full sm:max-w-2xl mx-auto py-4 sm:py-6 md:py-8 bg-gray-50 dark:bg-gray-800/50 rounded-lg px-2 sm:px-4">
    <div class="w-full">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-6 text-center">Informatie aanvragen</h2>
            <p class="text-gray-600 dark:text-gray-300 mb-6 text-center">Vul het formulier in en wij nemen contact met u op.</p>
            <div class="space-y-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4 sm:p-6 shadow-sm">
                <div id="frontend-preview-fields" class="space-y-4">
                    @if($hasFields)
                        @php
                            $firstTwo = $formFields->take(2);
                            $rest = $formFields->slice(2);
                        @endphp
                        @if($firstTwo->count() === 2)
                            <div class="grid gap-4 sm:grid-cols-2">
                                @foreach($firstTwo as $field)
                                    <div class="form-preview-field-row" data-field-id="{{ $field->id }}">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $field->label }}{{ $field->is_required ? ' *' : '' }}</label>
                                        @if(in_array($field->validation_rule, [null, ''], true) && str_contains(strtolower($field->label), 'omschrijving'))
                                            <textarea rows="5" disabled readonly class="{{ $inputClass }} opacity-75"></textarea>
                                        @else
                                            <input type="{{ $field->validation_rule === 'email' ? 'email' : 'text' }}" disabled readonly class="{{ $inputClass }} opacity-75" value="">
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            @foreach($firstTwo as $field)
                                <div class="form-preview-field-row" data-field-id="{{ $field->id }}">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $field->label }}{{ $field->is_required ? ' *' : '' }}</label>
                                    @if(in_array($field->validation_rule, [null, ''], true) && str_contains(strtolower($field->label), 'omschrijving'))
                                        <textarea rows="5" disabled readonly class="{{ $inputClass }} opacity-75"></textarea>
                                    @else
                                        <input type="{{ $field->validation_rule === 'email' ? 'email' : 'text' }}" disabled readonly class="{{ $inputClass }} opacity-75" value="">
                                    @endif
                                </div>
                            @endforeach
                        @endif
                        @foreach($rest as $field)
                            <div class="form-preview-field-row" data-field-id="{{ $field->id }}">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $field->label }}{{ $field->is_required ? ' *' : '' }}</label>
                                @if(in_array($field->validation_rule, [null, ''], true) && str_contains(strtolower($field->label), 'omschrijving'))
                                    <textarea rows="5" disabled readonly class="{{ $inputClass }} opacity-75"></textarea>
                                @else
                                    <input type="{{ $field->validation_rule === 'email' ? 'email' : 'text' }}" disabled readonly class="{{ $inputClass }} opacity-75" value="">
                                @endif
                            </div>
                        @endforeach
                    @else
                        {{-- Fallback: zelfde vaste velden als frontend (grid voor eerste twee, rest vol) --}}
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Voornaam *</label>
                                <input type="text" disabled readonly class="{{ $inputClass }} opacity-75" value="">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Achternaam *</label>
                                <input type="text" disabled readonly class="{{ $inputClass }} opacity-75" value="">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">E-mailadres *</label>
                            <input type="email" disabled readonly class="{{ $inputClass }} opacity-75" value="">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Telefoonnummer</label>
                            <input type="text" disabled readonly class="{{ $inputClass }} opacity-75" value="">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Omschrijving / vraag *</label>
                            <textarea rows="5" disabled readonly class="{{ $inputClass }} opacity-75"></textarea>
                        </div>
                    @endif
                </div>
                <div class="pt-2">
                    <button type="button" disabled class="w-full sm:w-auto inline-flex justify-center items-center font-medium rounded-lg px-5 py-2.5 bg-blue-600 text-white hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition-colors">
                        Versturen
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
