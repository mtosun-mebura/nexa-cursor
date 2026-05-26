@php $text = $block['data']['text'] ?? ''; @endphp
@if($text !== '')
<div class="website-block website-block-paragraph mb-6">
    <div class="prose prose-lg dark:prose-invert max-w-none">
        {!! $text !!}
    </div>
</div>
@endif
