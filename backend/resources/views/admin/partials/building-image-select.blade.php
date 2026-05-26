@php
    $selectId = $selectId ?? ('building-image-select-' . uniqid());
    $opts = [
        1 => ['label' => 'Oranje gevel', 'src' => asset('assets/media/company-buildings/1.png')],
        2 => ['label' => 'Twee torens', 'src' => asset('assets/media/company-buildings/2.png')],
        3 => ['label' => 'Wit minimalisme', 'src' => asset('assets/media/company-buildings/3.png')],
    ];
    $optsForJs = [];
    foreach ($opts as $k => $o) {
        $optsForJs[] = ['value' => (int) $k, 'label' => $o['label'], 'src' => $o['src']];
    }
    $raw = old('building_image', isset($company) && $company ? $company->building_image : null);
    $selected = ($raw === '' || $raw === null) ? null : (int) $raw;
    if ($selected !== null && ! array_key_exists($selected, $opts)) {
        $selected = null;
    }
@endphp
<div class="building-image-select relative max-w-md" id="{{ $selectId }}" data-building-image-select>
    <input type="hidden" name="building_image" value="{{ $selected ?? '' }}" data-building-image-input autocomplete="off">
    {{-- Gesloten staat: alleen tekst; plaatjes alleen in het open paneel (voorkomt dat thumbnails uit de knop “steken”). --}}
    <button type="button"
        class="kt-input flex items-center w-full text-left justify-between gap-2 py-2 px-3 min-h-0"
        data-building-image-trigger
        aria-haspopup="listbox"
        aria-expanded="false"
        aria-controls="{{ $selectId }}-listbox">
        <span class="truncate text-sm text-foreground min-w-0 flex-1" data-building-image-label>
            {{ $selected ? $opts[$selected]['label'] : 'Kies een gebouw…' }}
        </span>
        <i class="ki-filled ki-down text-muted-foreground shrink-0 transition-transform duration-200" data-building-image-chevron aria-hidden="true"></i>
    </button>
    <ul id="{{ $selectId }}-listbox"
        role="listbox"
        class="absolute z-50 mt-1 w-full max-h-[min(70vh,480px)] overflow-y-auto overflow-x-hidden rounded-md border border-border bg-background shadow-lg p-2 hidden"
        data-building-image-panel
        hidden>
        <li role="option"
            data-value=""
            class="flex flex-col gap-2 px-2 py-2.5 rounded-md cursor-pointer hover:bg-muted/80 text-sm {{ $selected === null ? 'bg-muted/50' : '' }}"
            data-building-image-option>
            <span class="block w-full aspect-[16/10] max-h-36 rounded-md border border-dashed border-border bg-muted/40 flex items-center justify-center text-xs text-muted-foreground">Geen afbeelding</span>
            <span class="font-medium">Geen plaatje</span>
        </li>
        @foreach($opts as $val => $o)
            <li role="option"
                data-value="{{ $val }}"
                class="flex flex-col gap-2 px-2 py-2.5 rounded-md cursor-pointer hover:bg-muted/80 text-sm {{ $selected === $val ? 'bg-muted/50' : '' }}"
                data-building-image-option>
                <img src="{{ $o['src'] }}" alt="" class="block w-full max-h-40 object-cover rounded-md border border-border" loading="lazy" decoding="async">
                <span class="font-medium">{{ $o['label'] }}</span>
            </li>
        @endforeach
    </ul>
</div>
<p class="text-xs text-muted-foreground mt-1">Optioneel. Gebruikt o.a. voor het hoofdkantoor op het bedrijfsprofiel.</p>
@error('building_image')
    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
@enderror
<script>
(function() {
    var root = document.getElementById(@json($selectId));
    if (!root || root.dataset.buildingImageInit === '1') return;
    root.dataset.buildingImageInit = '1';
    var input = root.querySelector('[data-building-image-input]');
    var trigger = root.querySelector('[data-building-image-trigger]');
    var panel = root.querySelector('[data-building-image-panel]');
    var labelEl = root.querySelector('[data-building-image-label]');
    var chevron = root.querySelector('[data-building-image-chevron]');
    var opts = @json($optsForJs);

    function setLabel(selectedVal) {
        if (!selectedVal) {
            labelEl.textContent = 'Kies een gebouw…';
            return;
        }
        var found = opts.find(function(o) { return o.value === selectedVal; });
        if (!found) return;
        labelEl.textContent = found.label;
    }

    function open() {
        panel.hidden = false;
        panel.classList.remove('hidden');
        trigger.setAttribute('aria-expanded', 'true');
        if (chevron) chevron.classList.add('rotate-180');
    }
    function close() {
        panel.hidden = true;
        panel.classList.add('hidden');
        trigger.setAttribute('aria-expanded', 'false');
        if (chevron) chevron.classList.remove('rotate-180');
    }
    function toggle() {
        if (panel.hidden) open(); else close();
    }

    trigger.addEventListener('click', function(e) {
        e.preventDefault();
        toggle();
    });

    root.querySelectorAll('[data-building-image-option]').forEach(function(row) {
        row.addEventListener('click', function() {
            var v = row.getAttribute('data-value');
            input.value = v === '' ? '' : v;
            setLabel(v === '' ? null : parseInt(v, 10));
            close();
        });
    });

    document.addEventListener('click', function(e) {
        if (!root.contains(e.target)) close();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !panel.hidden) close();
    });
})();
</script>
