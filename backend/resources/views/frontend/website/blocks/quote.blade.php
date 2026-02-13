@php
    $text = $block['data']['text'] ?? '';
    $caption = $block['data']['caption'] ?? '';
@endphp
@if($text !== '')
<blockquote class="website-block website-block-quote mb-6 pl-4 border-l-4 border-opacity-50" style="border-color: var(--theme-primary);">
    <div class="prose prose-lg dark:prose-invert max-w-none text-gray-700 dark:text-gray-300">
        {!! $text !!}
    </div>
    @if($caption !== '')
    <cite class="block mt-2 text-sm text-gray-500 dark:text-gray-400 not-italic">— {{ $caption }}</cite>
    @endif
</blockquote>
@endif
