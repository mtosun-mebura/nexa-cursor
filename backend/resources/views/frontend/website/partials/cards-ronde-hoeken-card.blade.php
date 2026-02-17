@php
    $card = $card ?? [];
    $i = $i ?? 0;
    $sectionKey = $sectionKey ?? 'cards_ronde_hoeken';
    $visibility = $visibility ?? [];
    $imgUrl = $imgUrl ?? '';
    $textVisible = $textVisible ?? true;
    $imagePadding = $imagePadding ?? 2;
    $imageBgColor = $imageBgColor ?? '';
    $textAlignClass = $textAlignClass ?? 'text-left';
    $textStyle = $textStyle ?? '';
@endphp
<article class="kt-card w-full shadow-md border border-gray-200 dark:border-gray-600 rounded-xl overflow-hidden flex flex-col {{ $imageBgColor !== '' ? '' : 'bg-white dark:bg-gray-800' }}" style="min-height: 0;@if($imageBgColor !== '') background-color: {{ $imageBgColor }};@endif">
    @if($imgUrl !== '')
    <div class="flex-shrink-0 block overflow-hidden" style="padding: {{ $imagePadding }}px;">
        <img alt="" class="w-full h-auto object-cover block" src="{{ $imgUrl }}" loading="lazy">
    </div>
    @endif
    @if($textVisible && trim((string) ($card['text'] ?? '')) !== '')
    @php
        $cardText = trim((string) ($card['text'] ?? ''));
        if ($cardText !== '') {
            $stripped = strip_tags($cardText);
            if ($stripped === $cardText) {
                if (str_contains($cardText, "\n")) {
                    $cardText = nl2br(e($cardText));
                } else {
                    $escaped = e($cardText);
                    if (str_contains($cardText, 'Wachttarief')) {
                        $cardText = str_replace('Wachttarief', '<hr>Wachttarief', $escaped);
                    } else {
                        $cardText = $escaped;
                    }
                }
            } else {
                $cardText = preg_replace('/<p>(\s*|<br\s*\/?>)\s*<\/p>/u', '<p class="cards-ronde-hoeken-empty-p"></p>', $cardText);
            }
        }
    @endphp
    <div class="p-5 flex-grow leading-relaxed cards-ronde-hoeken-text {{ $textAlignClass }} {{ ($card['text_color'] ?? '') === '' ? 'text-gray-700 dark:text-gray-300' : '' }}" style="{{ $textStyle }}">
        <div class="prose dark:prose-invert max-w-none prose-p:my-1 prose-ul:my-1 prose-ol:my-1 prose-ul:list-disc prose-ul:pl-6 prose-ol:list-decimal prose-ol:pl-6 prose-li:my-0.5 prose-strong:font-bold prose-a:text-blue-600 dark:prose-a:text-blue-400 cards-ronde-hoeken-prose">
            {!! $cardText !!}
        </div>
    </div>
    @endif
</article>
