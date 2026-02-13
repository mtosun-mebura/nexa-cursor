@php
    $items = $block['data']['items'] ?? [];
    $style = $block['data']['style'] ?? 'unordered';
@endphp
@if(!empty($items))
<div class="website-block website-block-list mb-6">
    @if($style === 'ordered')
    <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300 prose prose-lg max-w-none">
        @foreach($items as $item)
        <li>{!! $item !!}</li>
        @endforeach
    </ol>
    @else
    <ul class="list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300 prose prose-lg max-w-none">
        @foreach($items as $item)
        <li>{!! $item !!}</li>
        @endforeach
    </ul>
    @endif
</div>
@endif
