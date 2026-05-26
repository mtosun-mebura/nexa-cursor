@php
    $url = $block['data']['url'] ?? '';
    $caption = $block['data']['caption'] ?? '';
    $alignment = $block['data']['alignment'] ?? 'center';
@endphp
@if($url !== '')
@php
    $alignClass = match($alignment) {
        'left' => 'float-left mr-4 mb-4 max-w-[280px]',
        'right' => 'float-right ml-4 mb-4 max-w-[280px]',
        'full' => 'w-full clear-both',
        default => 'mx-auto block clear-both',
    };
@endphp
<figure class="website-block website-block-image-aligned mb-6 clear-both {{ $alignment === 'left' || $alignment === 'right' ? '' : 'overflow-hidden' }}">
    <div class="{{ $alignClass }} rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 {{ $alignment === 'center' || $alignment === 'full' ? 'w-full' : '' }}">
        <img src="{{ $url }}" alt="{{ $caption ?: 'Afbeelding' }}" class="w-full h-auto object-cover">
    </div>
    @if($caption !== '')
    <figcaption class="mt-2 text-sm text-gray-500 dark:text-gray-400 {{ $alignment === 'center' || $alignment === 'full' ? 'text-center' : '' }}">{{ $caption }}</figcaption>
    @endif
</figure>
@endif
