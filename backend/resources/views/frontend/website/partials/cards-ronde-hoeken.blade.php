@php
    $items = $items ?? [];
    $visibility = $visibility ?? [];
    $sectionKey = $sectionKey ?? 'cards_ronde_hoeken';
    $count = count($items);
    $url = function($u) {
        if (empty($u)) return '';
        $u = trim((string) $u);
        return (strpos($u, 'http') === 0 || strpos($u, '//') === 0) ? $u : url($u);
    };
@endphp
@if($count > 0)
<section class="py-12 md:py-16 bg-white dark:bg-gray-900" aria-label="Cards">
    <div class="container-custom">
        <div class="flex flex-row flex-wrap gap-5 lg:gap-7.5 items-stretch justify-center">
            @foreach($items as $i => $card)
                @php
                    $imgUrl = $url($card['image_url'] ?? '');
                    $textVisible = $visibility[$sectionKey.'_item_'.$i] ?? true;
                    $fontSize = isset($card['font_size']) ? max(10, min(24, (int) $card['font_size'])) : 14;
                    $fontStyle = $card['font_style'] ?? 'normal';
                    $cardSize = $card['card_size'] ?? 'normal';
                    $textAlign = $card['text_align'] ?? 'left';
                    // Vaste breedtes in px: small=300, normal=400, large=500, max=flex 1
                    $cardWidthPx = match($cardSize) {
                        'small' => 300,
                        'large' => 500,
                        'max' => null,
                        default => 400,
                    };
                    $textAlignClass = match($textAlign) {
                        'center' => 'text-center',
                        'right' => 'text-right',
                        default => 'text-left',
                    };
                    $textStyle = 'font-size: ' . $fontSize . 'px;';
                    if ($fontStyle === 'bold') $textStyle .= ' font-weight: 700; font-style: normal;';
                    elseif ($fontStyle === 'italic') $textStyle .= ' font-style: italic; font-weight: 400;';
                    else $textStyle .= ' font-weight: 400; font-style: normal;';
                @endphp
                <div class="flex items-stretch flex-shrink-0" @if($cardWidthPx !== null) style="width: {{ $cardWidthPx }}px; max-width: 100%;" @else style="flex: 1; min-width: 200px; max-width: 100%;" @endif>
                    <article class="kt-card w-full shadow-md border border-gray-200 dark:border-gray-600 rounded-xl overflow-hidden bg-white dark:bg-gray-800 flex flex-col" style="min-height: 0;">
                        @if($imgUrl !== '')
                        <div class="flex-shrink-0 block overflow-hidden">
                            <img alt="" class="w-full h-auto object-cover block" src="{{ $imgUrl }}" loading="lazy">
                        </div>
                        @endif
                        @if($textVisible && trim((string) ($card['text'] ?? '')) !== '')
                        <div class="p-5 flex-grow text-gray-700 dark:text-gray-300 leading-relaxed prose dark:prose-invert max-w-none prose-p:my-1 prose-ul:my-1 prose-ol:my-1 {{ $textAlignClass }}" style="{{ $textStyle }}">{!! $card['text'] !!}</div>
                        @endif
                    </article>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
