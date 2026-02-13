@php
    $text = $block['data']['text'] ?? '';
    $level = (int) ($block['data']['level'] ?? 2);
    $level = max(1, min(3, $level));
    $tag = 'h' . $level;
@endphp
@if($text !== '')
<div class="website-block website-block-header mb-6">
    <{{ $tag }} class="font-bold text-gray-900 dark:text-white mb-2" style="font-family: var(--theme-font-heading);">
        @if($level === 1) <span class="text-3xl md:text-4xl">{{ $text }}</span>
        @elseif($level === 2) <span class="text-2xl md:text-3xl">{{ $text }}</span>
        @else <span class="text-xl md:text-2xl">{{ $text }}</span>
        @endif
    </{{ $tag }}>
</div>
@endif
