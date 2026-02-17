{{-- Flowbite-style WYSIWYG (Tiptap). Variabelen: $editorId, $name, $value, $placeholder, $textareaId. Hoogte 300px; afbeelding/document via bladeren. --}}
@php
    $editorId = $editorId ?? ('wysiwyg-' . bin2hex(random_bytes(4)));
    $name = $name ?? '';
    $value = $value ?? '';
    $textareaId = $textareaId ?? ($editorId . '-input');
    $placeholder = $placeholder ?? '';
    $prefix = $editorId;
@endphp
<div class="flowbite-wysiwyg-wrapper w-full max-w-4xl border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 min-w-0" data-flowbite-wysiwyg data-editor-id="{{ $editorId }}" data-upload-image-url="{{ route('admin.website-pages.upload-hero-image') }}" data-upload-document-url="{{ route('admin.website-pages.upload-wysiwyg-document') }}">
    <input type="file" class="hidden flowbite-wysiwyg-image-input" accept="image/*" data-editor-id="{{ $editorId }}" aria-hidden="true">
    <input type="file" class="hidden flowbite-wysiwyg-document-input" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv" data-editor-id="{{ $editorId }}" aria-hidden="true">
    <div class="p-2 border-b border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/80 min-w-0 overflow-x-auto rounded-t-xl">
        <div class="flex flex-wrap items-center justify-start gap-1 min-w-max">
            <button type="button" id="{{ $prefix }}-toggleBold" class="p-1.5 text-gray-600 dark:text-gray-400 rounded hover:bg-gray-200 dark:hover:bg-gray-600" title="Vet"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5h4.5a3.5 3.5 0 1 1 0 7H8m0-7v7m0-7H6m2 7h6.5a3.5 3.5 0 1 1 0 7H8m0-7v7m0 0H6"/></svg></button>
            <button type="button" id="{{ $prefix }}-toggleItalic" class="p-1.5 text-gray-600 dark:text-gray-400 rounded hover:bg-gray-200 dark:hover:bg-gray-600" title="Cursief"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8.874 19 6.143-14M6 19h6.33m-.66-14H18"/></svg></button>
            <button type="button" id="{{ $prefix }}-toggleUnderline" class="p-1.5 text-gray-600 dark:text-gray-400 rounded hover:bg-gray-200 dark:hover:bg-gray-600" title="Onderstrepen"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M6 19h12M8 5v9a4 4 0 0 0 8 0V5M6 5h4m4 0h4"/></svg></button>
            <button type="button" id="{{ $prefix }}-toggleStrike" class="p-1.5 text-gray-600 dark:text-gray-400 rounded hover:bg-gray-200 dark:hover:bg-gray-600" title="Doorhalen"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 6.2V5h12v1.2M7 19h6m.2-14-1.677 6.523M9.6 19l1.029-4M5 5l6.523 6.523M19 19l-7.477-7.477"/></svg></button>
            <button type="button" id="{{ $prefix }}-toggleHighlight" class="p-1.5 text-gray-600 dark:text-gray-400 rounded hover:bg-gray-200 dark:hover:bg-gray-600" title="Markeren"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M9 20H5.5c-.27614 0-.5-.2239-.5-.5v-3c0-.2761.22386-.5.5-.5h13c.2761 0 .5.2239.5.5v3c0 .2761-.2239.5-.5.5H18m-6-1 1.42 1.8933c.04.0534.12.0534.16 0L15 19m-7-6 3.9072-9.76789c.0335-.08381.1521-.08381.1856 0L16 13m-8 0H7m1 0h1.5m6.5 0h-1.5m1.5 0h1m-7-3.00001h4"/></svg></button>
            <button type="button" id="{{ $prefix }}-toggleCode" class="p-1.5 text-gray-600 dark:text-gray-400 rounded hover:bg-gray-200 dark:hover:bg-gray-600" title="Code"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8 8-4 4 4 4m8 0 4-4-4-4m-2-3-4 14"/></svg></button>
            <span class="w-px h-5 bg-gray-300 dark:bg-gray-500 mx-0.5"></span>
            <button type="button" id="{{ $prefix }}-toggleLink" class="p-1.5 text-gray-600 dark:text-gray-400 rounded hover:bg-gray-200 dark:hover:bg-gray-600" title="Link"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.213 9.787a3.391 3.391 0 0 0-4.795 0l-3.425 3.426a3.39 3.39 0 0 0 4.795 4.794l.321-.304m-.321-4.49a3.39 3.39 0 0 0 4.795 0l3.424-3.426a3.39 3.39 0 0 0-4.794-4.795l-1.028.961"/></svg></button>
            <button type="button" id="{{ $prefix }}-removeLink" class="p-1.5 text-gray-600 dark:text-gray-400 rounded hover:bg-gray-200 dark:hover:bg-gray-600" title="Link verwijderen"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M13.2131 9.78732c-.6359-.63557-1.4983-.99259-2.3974-.99259-.89911 0-1.76143.35702-2.39741.99259l-3.4253 3.42528C4.35719 13.8485 4 14.7108 4 15.61c0 .8992.35719 1.7616.99299 2.3974.63598.6356 1.4983.9926 2.39742.9926.89912 0 1.76144-.357 2.39742-.9926l.32157-.3043m-.32157-4.4905c.63587.6358 1.49827.993 2.39747.993.8991 0 1.7615-.3572 2.3974-.993l3.4243-3.42528c.6358-.63585.993-1.49822.993-2.39741 0-.89919-.3572-1.76156-.993-2.39741C17.3712 4.357 16.509 4 15.6101 4c-.899 0-1.7612.357-2.397.9925l-1.0278.96062m7.3873 14.04678-1.7862-1.7862m0 0L16 16.4274m1.7864 1.7863 1.7862-1.7863m-1.7862 1.7863L16 20"/></svg></button>
            <span class="w-px h-5 bg-gray-300 dark:bg-gray-500 mx-0.5"></span>
            <button type="button" id="{{ $prefix }}-alignLeft" class="p-1.5 text-gray-600 dark:text-gray-400 rounded hover:bg-gray-200 dark:hover:bg-gray-600" title="Links uitlijnen"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6h8m-8 4h12M6 14h8m-8 4h12"/></svg></button>
            <button type="button" id="{{ $prefix }}-alignCenter" class="p-1.5 text-gray-600 dark:text-gray-400 rounded hover:bg-gray-200 dark:hover:bg-gray-600" title="Centreren"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6h8M6 10h12M8 14h8M6 18h12"/></svg></button>
            <button type="button" id="{{ $prefix }}-alignRight" class="p-1.5 text-gray-600 dark:text-gray-400 rounded hover:bg-gray-200 dark:hover:bg-gray-600" title="Rechts uitlijnen"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 6h-8m8 4H6m12 4h-8m8 4H6"/></svg></button>
            <span class="w-px h-5 bg-gray-300 dark:bg-gray-500 mx-0.5"></span>
            <button type="button" id="{{ $prefix }}-toggleList" class="p-1.5 text-gray-600 dark:text-gray-400 rounded hover:bg-gray-200 dark:hover:bg-gray-600" title="Opsomming"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M9 8h10M9 12h10M9 16h10M4.99 8H5m-.02 4h.01m0 4H5"/></svg></button>
            <button type="button" id="{{ $prefix }}-toggleOrderedList" class="p-1.5 text-gray-600 dark:text-gray-400 rounded hover:bg-gray-200 dark:hover:bg-gray-600" title="Genummerde lijst"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6h8m-8 6h8m-8 6h8M4 16a2 2 0 1 1 3.321 1.5L4 20h5M4 5l2-1v6m-2 0h4"/></svg></button>
            <button type="button" id="{{ $prefix }}-toggleBlockquote" class="p-1.5 text-gray-600 dark:text-gray-400 rounded hover:bg-gray-200 dark:hover:bg-gray-600" title="Citaat"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11V8a1 1 0 0 0-1-1H6a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1Zm0 0v2a4 4 0 0 1-4 4H5m14-6V8a1 1 0 0 0-1-1h-3a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1Zm0 0v2a4 4 0 0 1-4 4h-1"/></svg></button>
            <button type="button" id="{{ $prefix }}-toggleHR" class="p-1.5 text-gray-600 dark:text-gray-400 rounded hover:bg-gray-200 dark:hover:bg-gray-600" title="Horizontale lijn"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M5 12h14"/></svg></button>
            <span class="w-px h-5 bg-gray-300 dark:bg-gray-500 mx-0.5"></span>
            <button type="button" id="{{ $prefix }}-addImage" class="p-1.5 text-gray-600 dark:text-gray-400 rounded hover:bg-gray-200 dark:hover:bg-gray-600" title="Afbeelding (bladeren)"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m3 16 5-7 6 6.5m6.5 2.5L16 13l-4.286 6M14 10h.01M4 19h16a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Z"/></svg></button>
            <button type="button" id="{{ $prefix }}-addDocument" class="p-1.5 text-gray-600 dark:text-gray-400 rounded hover:bg-gray-200 dark:hover:bg-gray-600" title="Document (bladeren)"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></button>
            <span class="w-px h-5 bg-gray-300 dark:bg-gray-500 mx-0.5"></span>
            <button type="button" id="{{ $prefix }}-undo" class="p-1.5 text-gray-600 dark:text-gray-400 rounded hover:bg-gray-200 dark:hover:bg-gray-600" title="Ongedaan"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg></button>
            <button type="button" id="{{ $prefix }}-redo" class="p-1.5 text-gray-600 dark:text-gray-400 rounded hover:bg-gray-200 dark:hover:bg-gray-600" title="Opnieuw"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10h-10a8 8 0 00-8 8v2M21 10l-6 6m6-6l-6-6"/></svg></button>
        </div>
        <div class="flex flex-wrap items-center justify-start gap-1 mt-1.5 pt-1.5 border-t border-gray-200 dark:border-gray-600 min-w-max">
            <span class="text-xs text-gray-500 dark:text-gray-400 mr-1 shrink-0">Format:</span>
            <button type="button" id="{{ $prefix }}-setParagraph" class="px-2 py-1 text-xs rounded hover:bg-gray-200 dark:hover:bg-gray-600 shrink-0 text-gray-700 dark:text-gray-900" title="Paragraaf">P</button>
            <button type="button" id="{{ $prefix }}-setH1" class="px-2 py-1 text-xs font-bold rounded hover:bg-gray-200 dark:hover:bg-gray-600 shrink-0 text-gray-700 dark:text-gray-900" title="Kop 1">H1</button>
            <button type="button" id="{{ $prefix }}-setH2" class="px-2 py-1 text-xs font-bold rounded hover:bg-gray-200 dark:hover:bg-gray-600 shrink-0 text-gray-700 dark:text-gray-900" title="Kop 2">H2</button>
            <button type="button" id="{{ $prefix }}-setH3" class="px-2 py-1 text-xs font-bold rounded hover:bg-gray-200 dark:hover:bg-gray-600 shrink-0 text-gray-700 dark:text-gray-900" title="Kop 3">H3</button>
            <span class="w-px h-5 bg-gray-300 dark:bg-gray-500 mx-1"></span>
            <span class="text-xs text-gray-500 dark:text-gray-400 mr-1 shrink-0">Lettergrootte:</span>
            <select id="{{ $prefix }}-fontSize" class="kt-input text-xs py-1 px-2 h-8 min-w-0 w-20 rounded border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" title="Lettergrootte">
                <option value="">Standaard</option>
                @foreach(range(10, 40, 2) as $px)
                <option value="{{ $px }}px">{{ $px }}px</option>
                @endforeach
            </select>
            <span class="text-xs text-gray-500 dark:text-gray-400 ml-1 mr-1 shrink-0">Lettertype:</span>
            <select id="{{ $prefix }}-fontFamily" class="kt-input text-xs py-1 px-2 h-8 min-w-0 w-32 rounded border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" title="Lettertype">
                <option value="">Standaard</option>
                <option value="sans-serif">Sans-serif</option>
                <option value="serif">Serif</option>
                <option value="monospace">Monospace</option>
                <option value="Inter, sans-serif">Inter</option>
                <option value="Georgia, serif">Georgia</option>
            </select>
        </div>
    </div>
    <div class="flowbite-wysiwyg-content px-3 py-2 text-sm text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-800 focus-within:ring-1 focus-within:ring-blue-500 rounded-b-xl overflow-auto min-w-0" style="min-height: 300px; max-height: 300px;" data-editor-content></div>
    <textarea name="{{ $name }}" id="{{ $textareaId }}" class="hidden flowbite-wysiwyg-textarea" data-editor-input>{{ $value }}</textarea>
</div>
