@php
    $tableData = $sections[$sectionKey] ?? [];
    $tableHex = static function ($value, string $fallback): string {
        $v = trim((string) $value);
        if (preg_match('/^#([0-9a-fA-F]{3})$/', $v, $m)) {
            return '#'.$m[1][0].$m[1][0].$m[1][1].$m[1][1].$m[1][2].$m[1][2];
        }
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $v)) {
            return $v;
        }

        return $fallback;
    };
    $tableList = static function (array $data, string $listKey, string $rowSide): array {
        $items = [];
        $raw = $data[$listKey] ?? null;
        if (is_array($raw)) {
            foreach ($raw as $item) {
                if (is_string($item)) {
                    $items[] = trim($item);
                } elseif (is_array($item)) {
                    $items[] = trim((string) ($item['text'] ?? ''));
                }
            }
        }
        $hasOwnList = is_array($raw);
        if ($hasOwnList) {
            return $items !== [] ? $items : [''];
        }
        $fromRows = [];
        $rows = isset($data['rows']) && is_array($data['rows']) ? array_values($data['rows']) : [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $fromRows[] = trim((string) ($row[$rowSide] ?? ''));
        }

        return $fromRows !== [] ? $fromRows : [''];
    };
    $consItems = $tableList($tableData, 'cons', 'left');
    $prosItems = $tableList($tableData, 'pros', 'right');
    $leftColor = $tableHex($tableData['left_color'] ?? '', '#dc2626');
    $rightColor = $tableHex($tableData['right_color'] ?? '', '#16a34a');
    $fieldId = str_replace([':', '.'], ['-', '-'], $sectionKey);
    $layout = old('home_sections.'.$sectionKey.'.layout', $tableData['layout'] ?? 'columns') === 'stack' ? 'stack' : 'columns';
    $widthOptions = [100, 90, 80, 70, 60, 50, 40, 30];
    $leftWidth = (int) old('home_sections.'.$sectionKey.'.left_width_percent', $tableData['left_width_percent'] ?? 50);
    $rightWidth = (int) old('home_sections.'.$sectionKey.'.right_width_percent', $tableData['right_width_percent'] ?? 50);
    if ($leftWidth < 30 || $leftWidth > 100) {
        $leftWidth = 50;
    }
    if ($rightWidth < 30 || $rightWidth > 100) {
        $rightWidth = 50;
    }
@endphp
<div class="kt-card home-section-card home-section-card--component home-section-card--module @if($isCardCollapsed) home-section-card--collapsed @endif" data-section="{{ $sectionKey }}">
    <div class="kt-card-header home-section-header home-section-header--component flex items-center justify-between gap-2">
        <span class="home-section-drag-handle cursor-grab active:cursor-grabbing touch-none p-1 -ml-1 rounded text-muted-foreground hover:text-foreground" title="Sleep om volgorde te wijzigen" aria-label="Volgorde wijzigen"><svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" /></svg></span>
        <h3 class="kt-card-title">Voor- en nadelen (Algemeen)</h3>
        <div class="flex items-center gap-1 shrink-0">
            <input type="hidden" name="home_sections[visibility][{{ $sectionKey }}]" id="visibility-{{ $sectionKey }}" value="{{ $vis('') ? '1' : '0' }}">
            <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground" data-target="visibility-{{ $sectionKey }}" title="{{ $vis('') ? 'Verbergen op website' : 'Tonen op website' }}" aria-label="Zichtbaarheid">@if($vis(''))<svg class="w-5 h-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-5 h-5 text-current opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
            <button type="button" class="home-section-collapse-toggle kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground" title="Inklappen" aria-label="Sectie inklappen of uitklappen"><svg class="w-5 h-5 text-current home-section-collapse-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg></button>
            <button type="button" class="home-section-component-remove kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-destructive" title="Component van pagina verwijderen" aria-label="Component verwijderen"><svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
        </div>
    </div>
    <div class="home-section-card-body kt-card-table p-4 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
                <label class="text-sm text-muted-foreground">Titel</label>
                <input type="text" class="kt-input mt-1 w-full text-sm" name="home_sections[{{ $sectionKey }}][title]" value="{{ old('home_sections.'.$sectionKey.'.title', $tableData['title'] ?? '') }}">
            </div>
            <div>
                <label class="text-sm text-muted-foreground">Subtitel</label>
                <input type="text" class="kt-input mt-1 w-full text-sm" name="home_sections[{{ $sectionKey }}][subtitle]" value="{{ old('home_sections.'.$sectionKey.'.subtitle', $tableData['subtitle'] ?? '') }}">
            </div>
            <div>
                <label class="text-sm text-muted-foreground">Plaatsing</label>
                <select class="kt-input mt-1 w-full text-sm" name="home_sections[{{ $sectionKey }}][layout]">
                    <option value="columns" @selected($layout === 'columns')>Naast elkaar</option>
                    <option value="stack" @selected($layout === 'stack')>Onder elkaar</option>
                </select>
            </div>
        </div>
        <div class="nexa-comparison-block rounded-lg border overflow-hidden" style="border-color: color-mix(in srgb, {{ $leftColor }} 42%, var(--border));">
            <div class="px-4 py-3 font-semibold text-sm" style="background: color-mix(in srgb, {{ $leftColor }} 12%, transparent); color: {{ $leftColor }};">
                {{ old('home_sections.'.$sectionKey.'.left_heading', $tableData['left_heading'] ?? 'Nadelen') ?: 'Nadelen' }}
            </div>
            <div class="p-4 space-y-3">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm text-muted-foreground">Koptekst</label>
                        <input type="text" class="kt-input mt-1 w-full text-sm" name="home_sections[{{ $sectionKey }}][left_heading]" value="{{ old('home_sections.'.$sectionKey.'.left_heading', $tableData['left_heading'] ?? 'Nadelen') }}">
                    </div>
                    <div>
                        <label class="text-sm text-muted-foreground">Kleur</label>
                        <div class="flex items-center gap-2 mt-1">
                            <input type="color" class="h-9 w-14 cursor-pointer rounded border border-input bg-background p-1 shrink-0" value="{{ $leftColor }}" title="Kleur nadelen" data-sync-to="comparison-{{ $fieldId }}-left-color">
                            <input type="text" id="comparison-{{ $fieldId }}-left-color" class="kt-input font-mono text-sm" name="home_sections[{{ $sectionKey }}][left_color]" value="{{ old('home_sections.'.$sectionKey.'.left_color', $tableData['left_color'] ?? $leftColor) }}" placeholder="#dc2626" maxlength="7">
                        </div>
                    </div>
                    <div>
                        <label class="text-sm text-muted-foreground">Breedte</label>
                        <select class="kt-input mt-1 w-full text-sm" name="home_sections[{{ $sectionKey }}][left_width_percent]">
                            @foreach($widthOptions as $pct)
                            <option value="{{ $pct }}" @selected($leftWidth === $pct)>{{ $pct }}%</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-muted-foreground mt-1">Telt op met de andere kolom. De rest is lege ruimte links en rechts.</p>
                    </div>
                </div>
                <div class="space-y-2 nexa-comparison-table-list" data-section-key="{{ $sectionKey }}" data-list-key="cons">
                    @foreach($consItems as $i => $text)
                    <div class="nexa-comparison-table-item flex gap-2 items-center" data-item-index="{{ $i }}">
                        <input type="text" class="kt-input w-full text-sm" name="home_sections[{{ $sectionKey }}][cons][{{ $i }}][text]" value="{{ old('home_sections.'.$sectionKey.'.cons.'.$i.'.text', $text) }}" placeholder="Bijv. Klanten haken af zonder online boeking">
                        <button type="button" class="nexa-comparison-table-item-remove kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Verwijderen" aria-label="Verwijderen"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                    </div>
                    @endforeach
                </div>
                <button type="button" class="kt-btn kt-btn-xs kt-btn-outline nexa-comparison-table-item-add" data-section-key="{{ $sectionKey }}" data-list-key="cons" data-item-label="Nadeel">+ Nadeel toevoegen</button>
            </div>
        </div>
        <div class="nexa-comparison-block rounded-lg border overflow-hidden" style="border-color: color-mix(in srgb, {{ $rightColor }} 42%, var(--border));">
            <div class="px-4 py-3 font-semibold text-sm" style="background: color-mix(in srgb, {{ $rightColor }} 12%, transparent); color: {{ $rightColor }};">
                {{ old('home_sections.'.$sectionKey.'.right_heading', $tableData['right_heading'] ?? 'Voordelen') ?: 'Voordelen' }}
            </div>
            <div class="p-4 space-y-3">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm text-muted-foreground">Koptekst</label>
                        <input type="text" class="kt-input mt-1 w-full text-sm" name="home_sections[{{ $sectionKey }}][right_heading]" value="{{ old('home_sections.'.$sectionKey.'.right_heading', $tableData['right_heading'] ?? 'Voordelen') }}">
                    </div>
                    <div>
                        <label class="text-sm text-muted-foreground">Kleur</label>
                        <div class="flex items-center gap-2 mt-1">
                            <input type="color" class="h-9 w-14 cursor-pointer rounded border border-input bg-background p-1 shrink-0" value="{{ $rightColor }}" title="Kleur voordelen" data-sync-to="comparison-{{ $fieldId }}-right-color">
                            <input type="text" id="comparison-{{ $fieldId }}-right-color" class="kt-input font-mono text-sm" name="home_sections[{{ $sectionKey }}][right_color]" value="{{ old('home_sections.'.$sectionKey.'.right_color', $tableData['right_color'] ?? $rightColor) }}" placeholder="#16a34a" maxlength="7">
                        </div>
                    </div>
                    <div>
                        <label class="text-sm text-muted-foreground">Breedte</label>
                        <select class="kt-input mt-1 w-full text-sm" name="home_sections[{{ $sectionKey }}][right_width_percent]">
                            @foreach($widthOptions as $pct)
                            <option value="{{ $pct }}" @selected($rightWidth === $pct)>{{ $pct }}%</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-muted-foreground mt-1">Telt op met de andere kolom. De rest is lege ruimte links en rechts.</p>
                    </div>
                </div>
                <div class="space-y-2 nexa-comparison-table-list" data-section-key="{{ $sectionKey }}" data-list-key="pros">
                    @foreach($prosItems as $i => $text)
                    <div class="nexa-comparison-table-item flex gap-2 items-center" data-item-index="{{ $i }}">
                        <input type="text" class="kt-input w-full text-sm" name="home_sections[{{ $sectionKey }}][pros][{{ $i }}][text]" value="{{ old('home_sections.'.$sectionKey.'.pros.'.$i.'.text', $text) }}" placeholder="Bijv. 24/7 een rit vastleggen op jouw site">
                        <button type="button" class="nexa-comparison-table-item-remove kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Verwijderen" aria-label="Verwijderen"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                    </div>
                    @endforeach
                </div>
                <button type="button" class="kt-btn kt-btn-xs kt-btn-outline nexa-comparison-table-item-add" data-section-key="{{ $sectionKey }}" data-list-key="pros" data-item-label="Voordeel">+ Voordeel toevoegen</button>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    if (window.__nexaComparisonTableBound) return;
    window.__nexaComparisonTableBound = true;
    function reindexList(list) {
        list.querySelectorAll('.nexa-comparison-table-item').forEach(function (item, index) {
            item.setAttribute('data-item-index', String(index));
            var input = item.querySelector('input[type="text"]');
            var sectionKey = list.getAttribute('data-section-key') || '';
            var listKey = list.getAttribute('data-list-key') || 'cons';
            if (input) input.setAttribute('name', 'home_sections[' + sectionKey + '][' + listKey + '][' + index + '][text]');
        });
    }
    document.addEventListener('click', function (event) {
        var addBtn = event.target.closest && event.target.closest('.nexa-comparison-table-item-add');
        if (addBtn) {
            var sectionKey = addBtn.getAttribute('data-section-key');
            var listKey = addBtn.getAttribute('data-list-key') || 'cons';
            var itemLabel = addBtn.getAttribute('data-item-label') || 'Punt';
            var list = addBtn.parentElement && addBtn.parentElement.querySelector('.nexa-comparison-table-list[data-list-key="' + listKey + '"]');
            if (!list) return;
            var index = list.querySelectorAll('.nexa-comparison-table-item').length;
            var wrap = document.createElement('div');
            wrap.className = 'nexa-comparison-table-item flex gap-2 items-center';
            wrap.setAttribute('data-item-index', String(index));
            wrap.innerHTML = '<input type="text" class="kt-input w-full text-sm" name="home_sections[' + sectionKey + '][' + listKey + '][' + index + '][text]" value=""><button type="button" class="nexa-comparison-table-item-remove kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive shrink-0" title="Verwijderen" aria-label="Verwijderen"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>';
            list.appendChild(wrap);
            return;
        }
        var removeBtn = event.target.closest && event.target.closest('.nexa-comparison-table-item-remove');
        if (removeBtn) {
            var item = removeBtn.closest('.nexa-comparison-table-item');
            var list = removeBtn.closest('.nexa-comparison-table-list');
            if (!item || !list) return;
            if (list.querySelectorAll('.nexa-comparison-table-item').length <= 1) {
                var input = item.querySelector('input[type="text"]');
                if (input) input.value = '';
                return;
            }
            item.remove();
            reindexList(list);
        }
    });
    document.addEventListener('input', function (event) {
        var color = event.target;
        if (!(color instanceof HTMLInputElement) || color.type !== 'color' || !color.getAttribute('data-sync-to')) return;
        var hex = document.getElementById(color.getAttribute('data-sync-to'));
        if (hex) hex.value = color.value;
    });
})();
</script>
