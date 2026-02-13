@php
    $url = $block['data']['url'] ?? '';
    $caption = $block['data']['caption'] ?? '';
@endphp
@if($url !== '')
<figure class="website-block website-block-image mb-6">
    <div class="rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
        <img src="{{ $url }}" alt="{{ $caption ?: 'Afbeelding' }}" class="w-full h-auto object-cover">
    </div>
    @if($caption !== '')
    <figcaption class="mt-2 text-sm text-center text-gray-500 dark:text-gray-400">{{ $caption }}</figcaption>
    @endif
</figure>
@endif
