@extends('admin.layouts.app')

@section('title', "Website Pagina's")

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">Website Pagina's</h1>
        <a href="{{ route('admin.website-pages.create') }}" class="kt-btn kt-btn-primary">
            <i class="ki-filled ki-plus me-2"></i> Nieuwe pagina
        </a>
    </div>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5">
            <i class="ki-filled ki-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif

    <div class="kt-card">
        <div class="kt-card-table kt-scrollable-x-auto">
            <table class="kt-table">
                <thead>
                    <tr>
                        <th>Volgorde</th>
                        <th>Titel</th>
                        <th>Slug</th>
                        <th>Type</th>
                        <th>Module (bij welke module)</th>
                        <th>Thema</th>
                        <th>Status</th>
                        <th class="text-end">Acties</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pages as $page)
                        <tr>
                            <td>{{ $page->sort_order }}</td>
                            <td>{{ $page->title }}</td>
                            <td><code>{{ $page->slug }}</code></td>
                            <td>{{ $page->page_type }}</td>
                            <td>{{ $page->module_name ?? '—' }}</td>
                            <td>{{ $page->theme?->name ?? '—' }}</td>
                            <td>
                                @if($page->is_active)
                                    <span class="kt-badge kt-badge-success">Actief</span>
                                @else
                                    <span class="kt-badge kt-badge-secondary">Inactief</span>
                                @endif
                            </td>
                            <td class="text-end relative">
                                <div class="website-pages-actions-menu flex justify-end">
                                    <div class="relative">
                                        <button type="button" class="website-pages-actions-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" aria-label="Acties" aria-expanded="false" aria-haspopup="true">
                                            <i class="ki-filled ki-dots-vertical text-lg"></i>
                                        </button>
                                        <div class="website-pages-actions-dropdown kt-menu-dropdown kt-menu-default hidden absolute end-0 top-full z-[100] mt-1 min-w-[175px] rounded-lg border border-border bg-background py-1 shadow-lg" role="menu">
                                            <div class="kt-menu-item">
                                                <a class="kt-menu-link" href="{{ route('admin.website-pages.preview', $page) }}" target="_blank" rel="noopener">
                                                    <span class="kt-menu-icon">
                                                        <i class="ki-filled ki-eye"></i>
                                                    </span>
                                                    <span class="kt-menu-title">Voorbeeld</span>
                                                </a>
                                            </div>
                                            <div class="kt-menu-separator"></div>
                                            <div class="kt-menu-item">
                                                <a class="kt-menu-link" href="{{ route('admin.website-pages.edit', $page) }}">
                                                    <span class="kt-menu-icon">
                                                        <i class="ki-filled ki-pencil"></i>
                                                    </span>
                                                    <span class="kt-menu-title">Bewerken</span>
                                                </a>
                                            </div>
                                            <div class="kt-menu-separator"></div>
                                            <div class="kt-menu-item">
                                                <form action="{{ route('admin.website-pages.destroy', $page) }}" method="POST" class="block" onsubmit="return confirm('Pagina verwijderen?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="kt-menu-link w-full text-left text-destructive">
                                                        <span class="kt-menu-icon">
                                                            <i class="ki-filled ki-trash"></i>
                                                        </span>
                                                        <span class="kt-menu-title">Verwijderen</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted-foreground py-8">Nog geen website-pagina's. Maak er een aan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('styles')
<style>
    .website-pages-actions-dropdown.is-open { display: block !important; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var toggles = document.querySelectorAll('.website-pages-actions-toggle');
    var openDropdown = null;

    function closeAll() {
        if (openDropdown) {
            openDropdown.classList.remove('is-open');
            openDropdown.classList.add('hidden');
            openDropdown.style.cssText = '';
            openDropdown.previousElementSibling.setAttribute('aria-expanded', 'false');
            openDropdown = null;
        }
    }

    function positionDropdown(btn, dropdown) {
        var rect = btn.getBoundingClientRect();
        var w = 175;
        dropdown.style.position = 'fixed';
        dropdown.style.left = (rect.right - w) + 'px';
        dropdown.style.top = (rect.bottom + 6) + 'px';
        dropdown.style.minWidth = w + 'px';
        dropdown.style.zIndex = '99999';
    }

    toggles.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            var wrapper = btn.closest('.website-pages-actions-menu');
            var dropdown = wrapper.querySelector('.website-pages-actions-dropdown');

            if (dropdown && dropdown.classList.contains('is-open')) {
                closeAll();
                return;
            }
            closeAll();
            positionDropdown(btn, dropdown);
            dropdown.classList.add('is-open');
            dropdown.classList.remove('hidden');
            btn.setAttribute('aria-expanded', 'true');
            openDropdown = dropdown;
        });
    });

    document.addEventListener('click', function() { closeAll(); });
});
</script>
@endpush
@endsection
