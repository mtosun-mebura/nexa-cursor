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
    $gapPx = 30;
    $cardWidthForSize = function($sz) {
        return match($sz ?? 'normal') {
            'small' => 300,
            'large' => 500,
            'max', 'total_width' => null,
            default => 400,
        };
    };
    $rows = [];
    $currentRow = [];
    foreach ($items as $i => $card) {
        $sz = $card['card_size'] ?? 'normal';
        if ($sz === 'total_width') {
            if (!empty($currentRow)) {
                $rows[] = ['type' => 'normal', 'items' => $currentRow];
                $currentRow = [];
            }
            $widthPx = null;
            $hasMax = false;
            if (!empty($rows) && ($rows[count($rows) - 1]['type'] ?? '') === 'normal') {
                $prevItems = $rows[count($rows) - 1]['items'];
                $sum = 0;
                foreach ($prevItems as $prev) {
                    $pw = $prev['card_width_px'] ?? null;
                    if ($pw === null) { $hasMax = true; break; }
                    $sum += $pw;
                }
                if (!$hasMax && count($prevItems) > 0) {
                    $widthPx = $sum + (count($prevItems) - 1) * $gapPx;
                }
            }
            $rows[] = ['type' => 'total_width', 'item' => $card, 'index' => $i, 'width_px' => $widthPx];
        } else {
            $currentRow[] = ['card' => $card, 'index' => $i, 'card_width_px' => $cardWidthForSize($sz)];
        }
    }
    if (!empty($currentRow)) {
        $rows[] = ['type' => 'normal', 'items' => $currentRow];
    }
@endphp
@if($count > 0)
<style>
  /* Zichtbare lege regels en hr in cards – inline zodat het altijd wordt toegepast */
  .cards-ronde-hoeken-text .prose p:empty,
  .cards-ronde-hoeken-text .cards-ronde-hoeken-prose p:empty,
  .cards-ronde-hoeken-text .prose p.cards-ronde-hoeken-empty-p,
  .cards-ronde-hoeken-text .cards-ronde-hoeken-prose p.cards-ronde-hoeken-empty-p {
    min-height: 1.5em !important;
    height: auto !important;
    display: block !important;
    padding-top: 10px !important;
    padding-bottom: 10px !important;
    margin: 20px !important;
    overflow: visible !important;
  }
  .cards-ronde-hoeken-text .prose hr,
  .cards-ronde-hoeken-text .cards-ronde-hoeken-prose hr {
    margin-top: 20px !important;
    margin-bottom: 20px !important;
  }
</style>
<section class="py-12 md:py-16 bg-white dark:bg-gray-900" aria-label="Cards">
    <div class="container-custom flex flex-col gap-5 lg:gap-7.5 items-center">
        @foreach($rows as $row)
            @if($row['type'] === 'normal')
        <div class="flex flex-row flex-wrap gap-5 lg:gap-7.5 items-stretch justify-center w-full">
                @foreach($row['items'] as $entry)
                    @php
                        $card = $entry['card'];
                        $i = $entry['index'];
                        $cardWidthPx = $entry['card_width_px'];
                        $imgUrl = $url($card['image_url'] ?? '');
                        $textVisible = $visibility[$sectionKey.'_item_'.$i] ?? true;
                        $fontSize = isset($card['font_size']) ? max(10, min(24, (int) $card['font_size'])) : 14;
                        $fontStyle = $card['font_style'] ?? 'normal';
                        $textAlign = $card['text_align'] ?? 'left';
                        $imagePadding = isset($card['image_padding']) ? max(0, min(30, (int) $card['image_padding'])) : 2;
                        $imagePadding = (int) (round($imagePadding / 2) * 2);
                        $imageBgColor = trim((string) ($card['image_bg_color'] ?? ''));
                        if ($imageBgColor !== '' && !preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $imageBgColor)) { $imageBgColor = ''; }
                        $textColor = trim((string) ($card['text_color'] ?? ''));
                        if ($textColor !== '' && !preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $textColor)) { $textColor = ''; }
                        $textAlignClass = match($textAlign) { 'center' => 'text-center', 'right' => 'text-right', default => 'text-left' };
                        $textStyle = '--card-fs: ' . $fontSize . 'px;';
                        if ($fontStyle === 'bold') $textStyle .= ' font-weight: 700; font-style: normal;';
                        elseif ($fontStyle === 'italic') $textStyle .= ' font-style: italic; font-weight: 400;';
                        else $textStyle .= ' font-weight: 400; font-style: normal;';
                        if ($textColor !== '') $textStyle .= ' color: ' . e($textColor) . ';';
                    @endphp
            <div class="flex items-stretch flex-shrink-0" @if($cardWidthPx !== null) style="width: {{ $cardWidthPx }}px; max-width: 100%;" @else style="flex: 1; min-width: 200px; max-width: 100%;" @endif>
                        @include('frontend.website.partials.cards-ronde-hoeken-card', ['card' => $card, 'i' => $i, 'sectionKey' => $sectionKey, 'visibility' => $visibility, 'imgUrl' => $imgUrl, 'textVisible' => $textVisible, 'imagePadding' => $imagePadding, 'imageBgColor' => $imageBgColor, 'textAlignClass' => $textAlignClass, 'textStyle' => $textStyle])
            </div>
                @endforeach
        </div>
            @else
        <div class="flex flex-row flex-wrap gap-5 lg:gap-7.5 items-stretch justify-center w-full" @if($row['width_px'] !== null) style="width: {{ $row['width_px'] }}px; max-width: 100%;" @endif>
                @php
                    $card = $row['item'];
                    $i = $row['index'];
                    $imgUrl = $url($card['image_url'] ?? '');
                    $textVisible = $visibility[$sectionKey.'_item_'.$i] ?? true;
                    $fontSize = isset($card['font_size']) ? max(10, min(24, (int) $card['font_size'])) : 14;
                    $fontStyle = $card['font_style'] ?? 'normal';
                    $textAlign = $card['text_align'] ?? 'left';
                    $imagePadding = isset($card['image_padding']) ? max(0, min(30, (int) $card['image_padding'])) : 2;
                    $imagePadding = (int) (round($imagePadding / 2) * 2);
                    $imageBgColor = trim((string) ($card['image_bg_color'] ?? ''));
                    if ($imageBgColor !== '' && !preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $imageBgColor)) { $imageBgColor = ''; }
                    $textColor = trim((string) ($card['text_color'] ?? ''));
                    if ($textColor !== '' && !preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $textColor)) { $textColor = ''; }
                    $textAlignClass = match($textAlign) { 'center' => 'text-center', 'right' => 'text-right', default => 'text-left' };
                    $textStyle = '--card-fs: ' . $fontSize . 'px;';
                    if ($fontStyle === 'bold') $textStyle .= ' font-weight: 700; font-style: normal;';
                    elseif ($fontStyle === 'italic') $textStyle .= ' font-style: italic; font-weight: 400;';
                    else $textStyle .= ' font-weight: 400; font-style: normal;';
                    if ($textColor !== '') $textStyle .= ' color: ' . e($textColor) . ';';
                @endphp
            <div class="flex items-stretch flex-shrink-0 w-full" style="min-width: 0;">
                        @include('frontend.website.partials.cards-ronde-hoeken-card', ['card' => $card, 'i' => $i, 'sectionKey' => $sectionKey, 'visibility' => $visibility, 'imgUrl' => $imgUrl, 'textVisible' => $textVisible, 'imagePadding' => $imagePadding, 'imageBgColor' => $imageBgColor, 'textAlignClass' => $textAlignClass, 'textStyle' => $textStyle])
            </div>
        </div>
            @endif
        @endforeach
    </div>
</section>
@endif
