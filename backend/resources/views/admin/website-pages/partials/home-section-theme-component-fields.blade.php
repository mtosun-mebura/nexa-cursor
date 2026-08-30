@php
    $themeComp = $comp ?? null;
    $themeCompId = strtolower(trim((string) ($themeComp->id ?? $compId ?? '')));
    $themeCompData = is_array($sectionData ?? null) ? $sectionData : [];
    $themeDefaults = $themeCompId !== ''
        ? app(\App\Services\FrontendComponentService::class)->defaultSectionData($themeCompId)
        : [];
    $themeMerged = array_merge($themeDefaults, $themeCompData);
    $themeItems = isset($themeMerged['items']) && is_array($themeMerged['items']) && $themeMerged['items'] !== []
        ? array_values($themeMerged['items'])
        : array_values($themeDefaults['items'] ?? []);
    $themeField = function (string $name, $value, string $label, string $type = 'text') use ($sectionKey) {
        $id = 'theme-comp-'.md5($sectionKey.$name);
        $inputName = 'home_sections['.$sectionKey.']['.$name.']';
        $val = is_string($value) || is_numeric($value) ? (string) $value : '';
        echo '<tr>';
        echo '<td class="min-w-40 text-secondary-foreground font-normal align-top pt-3">'.e($label).'</td>';
        echo '<td class="w-full pt-2 pb-2">';
        if ($type === 'textarea') {
            echo '<textarea class="kt-input w-full min-h-20" name="'.e($inputName).'" id="'.e($id).'" rows="3">'.e($val).'</textarea>';
        } else {
            echo '<input class="kt-input w-full" type="text" name="'.e($inputName).'" id="'.e($id).'" value="'.e($val).'">';
        }
        echo '</td></tr>';
    };
    $hasIntro = $themeCompId !== '';
@endphp
<div class="home-section-card-body px-3 sm:px-5 pb-4">
    <p class="text-xs font-semibold uppercase tracking-wide text-primary mb-3">Thema: {{ $themeComp->theme_name ?? '—' }}</p>
    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
        @if($hasIntro)
            @php
                $themeField('eyebrow', $themeMerged['eyebrow'] ?? '', 'Boventitel');
                $themeField('title', $themeMerged['title'] ?? '', $themeCompId === 'vue_material.author_header' ? 'Naam' : 'Titel');
                $themeField('subtitle', $themeMerged['subtitle'] ?? '', $themeCompId === 'vue_material.author_header' ? 'Functie' : 'Subtitel', 'textarea');
            @endphp
        @endif
        @if($themeCompId === 'play.video_spotlight')
            @php
                $themeField('video_url', $themeMerged['video_url'] ?? '', 'Video-URL (YouTube)');
                $themeField('cta_label', $themeMerged['cta_label'] ?? '', 'Overlay-tekst');
            @endphp
        @endif
        @if($themeCompId === 'landwind.feature_checklist')
            @php $themeField('image_url', $themeMerged['image_url'] ?? '', 'Afbeelding-URL'); @endphp
        @endif
        @if($themeCompId === 'play.about_overlap')
            @php
                $themeField('image_url', $themeMerged['image_url'] ?? '', 'Foto 1 (URL)');
                $themeField('image_url_2', $themeMerged['image_url_2'] ?? '', 'Foto 2 (URL)');
                $themeField('body', $themeMerged['body'] ?? '', 'Tekst', 'textarea');
                $themeField('cta_label', $themeMerged['cta_label'] ?? '', 'Knoptekst');
                $themeField('cta_url', $themeMerged['cta_url'] ?? '', 'Knop-URL');
            @endphp
        @endif
        @if($themeCompId === 'play.contact_split')
            @php
                $themeField('address', $themeMerged['address'] ?? '', 'Adres');
                $themeField('phone', $themeMerged['phone'] ?? '', 'Telefoon');
                $themeField('email', $themeMerged['email'] ?? '', 'E-mail');
                $themeField('cta_label', $themeMerged['cta_label'] ?? '', 'Knoptekst');
            @endphp
        @endif
        @if($themeCompId === 'vue_material.author_header')
            @php
                $themeField('image_url', $themeMerged['image_url'] ?? '', 'Foto-URL');
                $themeField('bio', $themeMerged['bio'] ?? '', 'Bio', 'textarea');
            @endphp
        @endif
    </table>

    @if($themeCompId === 'landwind.faq')
        <p class="text-xs font-medium text-foreground mt-4 mb-2">Vragen</p>
        @foreach(array_pad($themeItems, 4, ['question' => '', 'answer' => '']) as $i => $row)
            <div class="mb-3 rounded-lg border border-border p-3 space-y-2">
                <input class="kt-input w-full" type="text" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][question]" value="{{ $row['question'] ?? '' }}" placeholder="Vraag">
                <textarea class="kt-input w-full min-h-16" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][answer]" rows="2" placeholder="Antwoord">{{ $row['answer'] ?? '' }}</textarea>
            </div>
        @endforeach
    @elseif($themeCompId === 'landwind.trusted_by')
        <p class="text-xs font-medium text-foreground mt-4 mb-2">Merknamen</p>
        <div class="grid gap-2 sm:grid-cols-2">
            @foreach(array_pad($themeItems, 5, ['name' => '']) as $i => $row)
                <input class="kt-input w-full" type="text" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][name]" value="{{ $row['name'] ?? '' }}" placeholder="Merk {{ $i + 1 }}">
            @endforeach
        </div>
    @elseif($themeCompId === 'landwind.feature_checklist')
        <p class="text-xs font-medium text-foreground mt-4 mb-2">Vinkjes</p>
        @foreach(array_pad($themeItems, 4, ['text' => '']) as $i => $row)
            <input class="kt-input w-full mb-2" type="text" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][text]" value="{{ $row['text'] ?? '' }}" placeholder="Punt {{ $i + 1 }}">
        @endforeach
    @elseif(in_array($themeCompId, ['landwind.stats_strip', 'vue_material.stats_counters'], true))
        <p class="text-xs font-medium text-foreground mt-4 mb-2">Cijfers</p>
        @foreach(array_pad($themeItems, 4, ['value' => '', 'suffix' => '', 'label' => '', 'decimals' => '0']) as $i => $row)
            <div class="mb-3 grid gap-2 sm:grid-cols-4">
                <input class="kt-input w-full" type="text" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][value]" value="{{ $row['value'] ?? '' }}" placeholder="Getal">
                <input class="kt-input w-full" type="text" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][suffix]" value="{{ $row['suffix'] ?? '' }}" placeholder="Suffix">
                <input class="kt-input w-full sm:col-span-2" type="text" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][label]" value="{{ $row['label'] ?? '' }}" placeholder="Label">
                @if($themeCompId === 'vue_material.stats_counters')
                    <input type="hidden" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][decimals]" value="{{ $row['decimals'] ?? '0' }}">
                @endif
            </div>
        @endforeach
    @elseif($themeCompId === 'play.team')
        <p class="text-xs font-medium text-foreground mt-4 mb-2">Teamleden</p>
        @foreach(array_pad($themeItems, 3, ['name' => '', 'role' => '', 'initials' => '']) as $i => $row)
            <div class="mb-3 grid gap-2 sm:grid-cols-3">
                <input class="kt-input w-full" type="text" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][name]" value="{{ $row['name'] ?? '' }}" placeholder="Naam">
                <input class="kt-input w-full" type="text" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][role]" value="{{ $row['role'] ?? '' }}" placeholder="Rol">
                <input class="kt-input w-full" type="text" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][initials]" value="{{ $row['initials'] ?? '' }}" placeholder="Initialen">
            </div>
        @endforeach
    @elseif($themeCompId === 'play.blog_preview')
        <p class="text-xs font-medium text-foreground mt-4 mb-2">Berichten</p>
        @foreach(array_pad($themeItems, 3, ['title' => '', 'excerpt' => '', 'date' => '', 'image_url' => '', 'url' => '']) as $i => $row)
            <div class="mb-3 rounded-lg border border-border p-3 space-y-2">
                <input class="kt-input w-full" type="text" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][title]" value="{{ $row['title'] ?? '' }}" placeholder="Titel">
                <textarea class="kt-input w-full min-h-16" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][excerpt]" rows="2" placeholder="Excerpt">{{ $row['excerpt'] ?? '' }}</textarea>
                <div class="grid gap-2 sm:grid-cols-3">
                    <input class="kt-input w-full" type="text" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][date]" value="{{ $row['date'] ?? '' }}" placeholder="Datum">
                    <input class="kt-input w-full" type="text" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][image_url]" value="{{ $row['image_url'] ?? '' }}" placeholder="Foto-URL">
                    <input class="kt-input w-full" type="text" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][url]" value="{{ $row['url'] ?? '' }}" placeholder="Link">
                </div>
            </div>
        @endforeach
    @elseif($themeCompId === 'vue_material.elevated_cards')
        <p class="text-xs font-medium text-foreground mt-4 mb-2">Kaarten</p>
        @foreach(array_pad($themeItems, 3, ['title' => '', 'text' => '', 'accent' => '']) as $i => $row)
            <div class="mb-3 rounded-lg border border-border p-3 space-y-2">
                <input class="kt-input w-full" type="text" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][title]" value="{{ $row['title'] ?? '' }}" placeholder="Kaarttitel">
                <textarea class="kt-input w-full min-h-16" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][text]" rows="2" placeholder="Tekst">{{ $row['text'] ?? '' }}</textarea>
                <input class="kt-input w-32" type="text" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][accent]" value="{{ $row['accent'] ?? '' }}" placeholder="#e91e63">
            </div>
        @endforeach
    @elseif($themeCompId === 'vue_material.quote_cards')
        <p class="text-xs font-medium text-foreground mt-4 mb-2">Quotes</p>
        @foreach(array_pad($themeItems, 3, ['quote' => '', 'author' => '', 'role' => '']) as $i => $row)
            <div class="mb-3 rounded-lg border border-border p-3 space-y-2">
                <textarea class="kt-input w-full min-h-16" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][quote]" rows="2" placeholder="Quote">{{ $row['quote'] ?? '' }}</textarea>
                <div class="grid gap-2 sm:grid-cols-2">
                    <input class="kt-input w-full" type="text" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][author]" value="{{ $row['author'] ?? '' }}" placeholder="Naam">
                    <input class="kt-input w-full" type="text" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][role]" value="{{ $row['role'] ?? '' }}" placeholder="Functie">
                </div>
            </div>
        @endforeach
    @elseif($themeCompId === 'vue_material.info_pills')
        <p class="text-xs font-medium text-foreground mt-4 mb-2">Pills</p>
        @foreach(array_pad($themeItems, 3, ['label' => '', 'title' => '', 'text' => '']) as $i => $row)
            <div class="mb-3 rounded-lg border border-border p-3 space-y-2">
                <input class="kt-input w-full" type="text" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][label]" value="{{ $row['label'] ?? '' }}" placeholder="Pill">
                <input class="kt-input w-full" type="text" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][title]" value="{{ $row['title'] ?? '' }}" placeholder="Paneeltitel">
                <textarea class="kt-input w-full min-h-16" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][text]" rows="2" placeholder="Tekst">{{ $row['text'] ?? '' }}</textarea>
            </div>
        @endforeach
    @elseif($themeCompId === 'vue_material.author_header')
        <p class="text-xs font-medium text-foreground mt-4 mb-2">Socials</p>
        @foreach(array_pad($themeItems, 3, ['label' => '', 'url' => '']) as $i => $row)
            <div class="mb-2 grid gap-2 sm:grid-cols-2">
                <input class="kt-input w-full" type="text" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][label]" value="{{ $row['label'] ?? '' }}" placeholder="Label">
                <input class="kt-input w-full" type="text" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][url]" value="{{ $row['url'] ?? '' }}" placeholder="URL">
            </div>
        @endforeach
    @endif
</div>
