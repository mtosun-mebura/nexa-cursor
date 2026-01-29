@extends('admin.layouts.app')

@section('title', 'Branch Bewerken')

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-col gap-5 pb-7.5">
        <div class="flex flex-wrap items-center justify-between gap-5">
            <h1 class="text-xl font-medium leading-none text-mono">
                Branch Bewerken
            </h1>
        </div>
        <div class="flex items-center">
            <a href="{{ route('admin.skillmatching.branches.show', $branch) }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
    </div>

    <form action="{{ route('admin.skillmatching.branches.update', $branch) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid gap-5 lg:gap-7.5">
            <x-error-card :errors="$errors" />

            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Branch informatie
                    </h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">
                                Naam *
                            </td>
                            <td class="min-w-48 w-full">
                                <input type="text"
                                       class="kt-input @error('name') border-destructive @enderror"
                                       name="name"
                                       value="{{ old('name', $branch->name) }}"
                                       required>
                                @error('name')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>

                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Slug
                            </td>
                            <td>
                                <input type="text"
                                       class="kt-input @error('slug') border-destructive @enderror"
                                       name="slug"
                                       value="{{ old('slug', $branch->slug) }}"
                                       placeholder="Automatisch gegenereerd">
                                <div class="text-xs text-muted-foreground mt-1">Laat leeg om automatisch te genereren op basis van de naam</div>
                                @error('slug')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>

                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                Beschrijving
                            </td>
                            <td>
                                <textarea class="kt-input pt-1 @error('description') border-destructive @enderror"
                                          name="description"
                                          rows="4"
                                          placeholder="Beschrijving...">{{ old('description', $branch->description) }}</textarea>
                                @error('description')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>

                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Kleur
                            </td>
                            <td>
                                <div class="flex items-center gap-3">
                                    <input type="color"
                                           class="kt-input @error('color') border-destructive @enderror"
                                           name="color"
                                           value="{{ old('color', $branch->color ?? '#007bff') }}"
                                           style="width: 60px; height: 40px; padding: 0;">
                                    <span class="text-xs text-muted-foreground">{{ old('color', $branch->color ?? '#007bff') }}</span>
                                </div>
                                @error('color')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>

                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Icoon
                            </td>
                            <td>
                                @php
                                    $currentIcon = old('icon', $branch->icon);
                                    $isHeroicon = is_string($currentIcon) && str_starts_with($currentIcon, 'heroicon-');
                                @endphp

                                <input type="hidden" name="icon" id="icon-input" value="{{ $currentIcon }}">

                                <button type="button" id="icon-picker-open" class="kt-btn kt-btn-outline kt-btn-sm inline-flex items-center gap-2 px-3">
                                    <span class="flex items-center gap-2 min-w-0">
                                        <span id="icon-preview" class="shrink-0">
                                            @if($currentIcon)
                                                @if($isHeroicon)
                                                    <x-dynamic-component :component="$currentIcon" class="w-5 h-5 text-muted-foreground" />
                                                @else
                                                    <i class="{{ $currentIcon }} text-lg text-muted-foreground"></i>
                                                @endif
                                            @else
                                                <i class="ki-filled ki-picture text-lg text-muted-foreground"></i>
                                            @endif
                                        </span>
                                        <span id="icon-label" class="truncate text-secondary-foreground max-w-[260px]">
                                            {{ $currentIcon ?: 'Kies een icoon' }}
                                        </span>
                                    </span>
                                    <i class="ki-filled ki-down text-muted-foreground"></i>
                                </button>

                                <div class="text-xs text-muted-foreground mt-1">Klik om een Heroicon te kiezen</div>
                                @error('icon')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>

                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Status
                            </td>
                            <td>
                                <label class="kt-label flex items-center">
                                    <input type="checkbox"
                                           class="kt-switch kt-switch-sm"
                                           name="is_active"
                                           value="1"
                                           {{ old('is_active', $branch->is_active) ? 'checked' : '' }}>
                                    <span class="ms-2">Actief</span>
                                </label>
                                <div class="text-xs text-muted-foreground mt-1">Deze branch is beschikbaar voor gebruik</div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5">
                <a href="{{ route('admin.skillmatching.branches.show', $branch) }}" class="kt-btn kt-btn-outline">
                    <i class="ki-filled ki-cross me-2"></i>
                    Annuleren
                </a>
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-check me-2"></i>
                    Wijzigingen Opslaan
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Icon Picker Modal -->
@php
    $heroicons = collect();
    try {
        $svgDir = base_path('vendor/blade-ui-kit/blade-heroicons/resources/svg');
        if (\Illuminate\Support\Facades\File::isDirectory($svgDir)) {
            $heroicons = collect(\Illuminate\Support\Facades\File::files($svgDir))
                ->map(fn($f) => pathinfo($f, PATHINFO_FILENAME))
                ->filter(fn($name) => is_string($name) && preg_match('/^[a-z]-[a-z0-9-]+$/', $name))
                ->values();
        }
    } catch (\Throwable $e) {
        $heroicons = collect();
    }
@endphp

<div id="icon-picker-modal" class="hidden fixed inset-0 z-[9999]">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" data-icon-picker-close="true"></div>
    <div class="relative h-full w-full flex items-center justify-center p-4">
        <div class="kt-card w-[95vw] max-w-4xl max-h-[90vh] flex flex-col">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Kies een Heroicon</h3>
                <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" data-icon-picker-close="true" aria-label="Sluiten">
                    <i class="ki-filled ki-cross text-lg"></i>
                </button>
            </div>
            <div class="kt-card-body overflow-y-auto min-h-0">
                <div class="flex flex-col gap-4">
                    <div id="icon-picker-search-wrap" class="kt-input">
                        <i class="ki-filled ki-magnifier"></i>
                        <input id="icon-picker-search" type="text" placeholder="Zoek icoon..." />
                    </div>

                    <div class="max-h-[60vh] overflow-y-auto border border-input rounded-lg p-3">
                        <div id="icon-picker-grid" class="grid grid-cols-6 sm:grid-cols-8 md:grid-cols-10 lg:grid-cols-12 gap-2">
                            @foreach($heroicons as $file)
                                @php
                                    $prefix = substr($file, 0, 1);
                                    $name = substr($file, 2);
                                    $component = "heroicon-{$prefix}-{$name}";
                                @endphp
                                <button type="button"
                                        class="kt-btn kt-btn-outline kt-btn-sm p-2 flex items-center justify-center"
                                        data-icon-item="true"
                                        data-icon-component="{{ $component }}"
                                        data-icon-label="{{ $component }}"
                                        title="{{ $component }}">
                                    <x-dynamic-component :component="$component" class="w-5 h-5 text-muted-foreground" />
                                </button>
                            @endforeach
                        </div>
                        @if($heroicons->isEmpty())
                            <div class="text-sm text-muted-foreground">Geen Heroicons gevonden.</div>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    .kt-table-border-dashed tbody tr { border-bottom: none !important; }
    .kt-table-border-dashed tbody tr,
    .kt-table-border-dashed tbody tr td { height: auto; min-height: 48px; }
    .kt-table-border-dashed tbody tr td { padding-top: 12px; padding-bottom: 12px; vertical-align: top; }
    .kt-table-border-dashed tbody tr td:first-child { display: flex; vertical-align: middle; padding-top: 8px; padding-bottom: 0; line-height: 40px; height: 40px; }
    .kt-table-border-dashed tbody tr td:last-child { vertical-align: top; padding-top: 12px; }
    .kt-table-border-dashed tbody tr td.align-top { vertical-align: top !important; padding-top: 18px; }
    .kt-table-border-dashed tbody tr td.align-top:first-child { line-height: normal; height: auto; padding-top: 18px; }

    /* Icon picker search input: smaller + 10px side margins */
    #icon-picker-search-wrap {
        width: 520px !important;
        max-width: calc(100% - 20px) !important;
        margin-top: 10px !important;
        margin-left: 10px !important;
        margin-right: 10px !important;
        align-self: center;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const nameInput = document.querySelector('input[name="name"]');
    const slugInput = document.querySelector('input[name="slug"]');
    if (!nameInput || !slugInput) return;

    let slugManuallyEdited = false;

    function slugify(value) {
        return (value || '')
            .toString()
            .normalize('NFD')                 // split accented chars
            .replace(/[\u0300-\u036f]/g, '')  // remove accents
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9]+/g, '-')      // non-alnum -> dash
            .replace(/-+/g, '-')              // collapse dashes
            .replace(/^-|-$/g, '');           // trim dashes
    }

    // If user edits slug, stop auto-updating it
    slugInput.addEventListener('input', function () {
        slugManuallyEdited = true;
    });

    // Auto-update slug when name changes (unless slug was manually edited)
    nameInput.addEventListener('input', function () {
        if (slugManuallyEdited) return;
        slugInput.value = slugify(nameInput.value);
    });
});
</script>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('icon-picker-modal');
    const openBtn = document.getElementById('icon-picker-open');
    const iconInput = document.getElementById('icon-input');
    const iconLabel = document.getElementById('icon-label');
    const iconPreview = document.getElementById('icon-preview');
    const search = document.getElementById('icon-picker-search');

    if (!modal || !openBtn || !iconInput || !iconLabel || !iconPreview) return;

    function openModal() {
        modal.classList.remove('hidden');
        setTimeout(() => search?.focus(), 0);
    }
    function closeModal() {
        modal.classList.add('hidden');
    }

    openBtn.addEventListener('click', openModal);
    modal.addEventListener('click', function (e) {
        if (e.target && e.target.closest && e.target.closest('[data-icon-picker-close="true"]')) closeModal();
    });
    document.addEventListener('keydown', function (e) {
        if (!modal.classList.contains('hidden') && e.key === 'Escape') closeModal();
    });

    // Filter icons
    if (search) {
        search.addEventListener('input', function () {
            const q = (search.value || '').toLowerCase().trim();
            document.querySelectorAll('[data-icon-item="true"]').forEach(btn => {
                const label = (btn.getAttribute('data-icon-label') || '').toLowerCase();
                btn.style.display = !q || label.includes(q) ? '' : 'none';
            });
        });
    }

    // Select icon
    modal.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-icon-item="true"]');
        if (!btn) return;
        const component = btn.getAttribute('data-icon-component');
        const label = btn.getAttribute('data-icon-label');
        if (!component) return;

        iconInput.value = component;
        iconLabel.textContent = label || component;

        // Update preview by cloning the svg from the button
        const svg = btn.querySelector('svg');
        iconPreview.innerHTML = '';
        if (svg) {
            iconPreview.appendChild(svg.cloneNode(true));
            const cloned = iconPreview.querySelector('svg');
            if (cloned) {
                cloned.classList.add('w-5', 'h-5', 'text-muted-foreground');
            }
        }

        closeModal();
    });
});
</script>
@endpush
