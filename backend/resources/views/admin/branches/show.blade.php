@extends('admin.layouts.app')

@section('title', 'Branch Details - ' . $branch->name)

@section('content')

@php
    $canViewBranch = auth()->user()->hasRole('super-admin') || auth()->user()->can('view-branches');
    $canEditBranch = auth()->user()->hasRole('super-admin') || auth()->user()->can('edit-branches');
@endphp

<style>
    .hero-bg { background-image: url('{{ asset('assets/media/images/2600x1200/bg-1.png') }}'); }
    .dark .hero-bg { background-image: url('{{ asset('assets/media/images/2600x1200/bg-1-dark.png') }}'); }
</style>

<div class="bg-center bg-cover bg-no-repeat hero-bg">
    <div class="kt-container-fixed">
        <div class="flex flex-col items-center gap-2 lg:gap-3.5 py-4 lg:pt-5 lg:pb-10">
            <div class="rounded-full border-3 border-green-500 size-[100px] shrink-0 flex items-center justify-center bg-primary/10">
                @if($branch->icon)
                    @if(is_string($branch->icon) && str_starts_with($branch->icon, 'heroicon-'))
                        <x-dynamic-component :component="$branch->icon" class="w-10 h-10" style="color: {{ $branch->color ?? 'var(--color-primary)' }};" />
                    @else
                        <i class="{{ $branch->icon }} text-3xl" style="color: {{ $branch->color ?? 'var(--color-primary)' }};"></i>
                    @endif
                @else
                    <i class="ki-filled ki-tag text-3xl text-primary"></i>
                @endif
            </div>

            <div class="flex items-center gap-2">
                <div class="text-lg leading-5 font-semibold text-mono">{{ $branch->name }}</div>
            </div>

            <div class="flex flex-wrap justify-center gap-1 lg:gap-4.5 text-sm">
                <div class="flex gap-1.25 items-center">
                    <i class="ki-filled ki-briefcase text-muted-foreground text-sm"></i>
                    <span class="text-secondary-foreground font-medium">{{ $branch->vacancies_count ?? 0 }} vacatures</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="flex items-center flex-wrap md:flex-nowrap lg:items-center justify-between gap-3 lg:gap-6 mb-5 lg:mb-10">
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.skillmatching.branches.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>

        <div class="flex items-center gap-2.5">
            @if($canEditBranch)
                <form action="{{ route('admin.skillmatching.branches.toggle-status', $branch) }}" method="POST" id="toggle-status-form" class="inline">
                    @csrf
                    <label class="kt-label flex items-center">
                        <input type="checkbox" class="kt-switch kt-switch-sm" id="toggle-status-checkbox" {{ $branch->is_active ? 'checked' : '' }}/>
                        <span class="ms-2">Actief</span>
                    </label>
                </form>
            @else
                <label class="kt-label flex items-center">
                    <input type="checkbox" class="kt-switch kt-switch-sm" {{ $branch->is_active ? 'checked' : '' }} disabled/>
                    <span class="ms-2">Actief</span>
                </label>
            @endif

            @if($canEditBranch)
                <span class="text-orange-500">|</span>
                <a href="{{ route('admin.skillmatching.branches.edit', $branch) }}" class="kt-btn kt-btn-primary ml-auto">
                    <i class="ki-filled ki-notepad-edit me-2"></i>
                    Bewerken
                </a>
            @endif
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5 lg:gap-7.5">
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Branch</h3>
            </div>
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Naam</td>
                        <td class="min-w-48 w-full text-foreground font-normal">{{ $branch->name }}</td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">Slug</td>
                        <td class="text-foreground font-normal"><code>{{ $branch->slug ?? '-' }}</code></td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">Beschrijving</td>
                        <td class="text-foreground font-normal">{{ $branch->description ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">Vacatures</td>
                        <td class="text-foreground font-normal">{{ $branch->vacancies_count ?? 0 }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Instellingen</h3>
            </div>
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Status</td>
                        <td class="min-w-48 w-full text-foreground font-normal">
                            @if($branch->is_active)
                                <span class="kt-badge kt-badge-sm kt-badge-success">Actief</span>
                            @else
                                <span class="kt-badge kt-badge-sm kt-badge-danger">Inactief</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">Icoon</td>
                        <td class="text-foreground font-normal">
                            @if($branch->icon)
                                <i class="{{ $branch->icon }} me-2" style="color: {{ $branch->color ?? 'inherit' }};"></i>
                                <span class="text-secondary-foreground">{{ $branch->icon }}</span>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">Kleur</td>
                        <td class="text-foreground font-normal">
                            @if($branch->color)
                                <span class="inline-flex items-center gap-2">
                                    <span class="size-4 rounded border border-input" style="background-color: {{ $branch->color }};"></span>
                                    {{ $branch->color }}
                                </span>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="kt-card mt-5 lg:mt-7.5">
        <div class="kt-card-header">
            <h3 class="kt-card-title">Vacatures in deze branch</h3>
            <a href="{{ route('admin.skillmatching.vacancies.index') }}" class="kt-btn kt-btn-sm kt-btn-outline">Bekijk alle</a>
        </div>
        <div class="kt-card-content">
            <div class="kt-scrollable-x-auto">
                <table class="kt-table table-auto kt-table-border align-middle text-sm">
                    <thead>
                        <tr>
                            <th class="min-w-[250px] text-secondary-foreground font-normal">Vacature</th>
                            <th class="min-w-[200px] text-secondary-foreground font-normal">Bedrijf</th>
                            <th class="min-w-[120px] text-secondary-foreground font-normal">Status</th>
                            <th class="min-w-[120px] text-secondary-foreground font-normal">Datum</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentVacancies ?? [] as $vacancy)
                            @php $status = $vacancy->status ?? 'onbekend'; @endphp
                            <tr>
                                <td class="text-foreground font-medium">
                                    <a class="hover:text-primary" href="{{ route('admin.skillmatching.vacancies.show', $vacancy) }}">
                                        {{ $vacancy->title }}
                                    </a>
                                </td>
                                <td class="text-foreground font-normal">{{ $vacancy->company->name ?? 'N/A' }}</td>
                                <td>
                                    @if($status === 'active')
                                        <span class="kt-badge kt-badge-sm kt-badge-success">Actief</span>
                                    @elseif($status === 'draft')
                                        <span class="kt-badge kt-badge-sm kt-badge-warning">Concept</span>
                                    @elseif($status === 'closed')
                                        <span class="kt-badge kt-badge-sm kt-badge-danger">Gesloten</span>
                                    @else
                                        <span class="kt-badge kt-badge-sm kt-badge-muted">{{ ucfirst($status) }}</span>
                                    @endif
                                </td>
                                <td class="text-foreground font-normal">{{ optional($vacancy->created_at)->format('d-m-Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted-foreground py-8">Geen vacatures gevonden</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Functies binnen branch -->
    <div class="kt-card mt-5 lg:mt-7.5" id="branch-functions-card">
        <div class="kt-card-header flex-wrap gap-3 py-4">
            <h3 class="kt-card-title">Functies</h3>
            <div id="branch-functions-flash" class="text-sm text-muted-foreground"></div>
            @if($canEditBranch)
                <form class="flex items-center gap-2 ms-auto"
                      method="POST"
                      action="{{ route('admin.skillmatching.branches.functions.store', $branch) }}"
                      data-branch-functions-create-form
                      data-validate="true">
                    @csrf
                    <input type="text"
                           name="name"
                           class="kt-input w-[320px] @error('name') border-destructive @enderror"
                           placeholder="Nieuwe functie (bijv. Digital Marketeer)"
                           required>
                    <button type="submit" class="kt-btn kt-btn-primary">
                        <i class="ki-filled ki-plus me-1"></i>
                        Toevoegen
                    </button>
                </form>
            @endif
        </div>
        <div class="kt-card-content">
            <div class="kt-scrollable-x-auto">
                <table class="kt-table table-auto kt-table-border align-middle text-sm">
                    <thead>
                        <tr>
                            <th class="min-w-[250px] text-secondary-foreground font-normal">Functie</th>
                            <th class="min-w-[200px] text-secondary-foreground font-normal">Sleutel</th>
                            @if($canEditBranch)
                                <th class="text-end text-secondary-foreground font-normal w-[50px]">Acties</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody id="branch-functions-tbody">
                        @forelse($branch->functions ?? [] as $function)
                            <tr data-branch-function-id="{{ $function->id }}" data-branch-function-key="{{ $function->name }}">
                                <td class="text-foreground font-medium" data-branch-function-display>{{ $function->display_name }}</td>
                                <td class="text-muted-foreground font-normal" data-branch-function-code>
                                    <code class="px-2 py-1 rounded text-xs font-mono">{{ $function->name }}</code>
                                </td>
                                @if($canEditBranch)
                                    <td class="text-end w-[50px]" onclick="event.stopPropagation();">
                                        <div class="flex items-center justify-end gap-2" data-branch-function-actions>
                                            <form method="POST"
                                                  action="{{ route('admin.skillmatching.branches.functions.update', [$branch, $function]) }}"
                                                  class="hidden flex items-center gap-2"
                                                  data-branch-functions-update-form
                                                  data-validate="true">
                                                @csrf
                                                @method('PUT')
                                                <input type="text"
                                                       name="name"
                                                       class="kt-input kt-input-sm w-[200px]"
                                                       value="{{ $function->display_name }}"
                                                       required>
                                                <button type="submit" class="kt-btn kt-btn-sm kt-btn-outline" title="Opslaan">
                                                    <i class="ki-filled ki-check"></i>
                                                </button>
                                                <button type="button" class="kt-btn kt-btn-sm kt-btn-outline" data-branch-function-cancel-btn title="Annuleren">
                                                    <i class="ki-filled ki-cross-circle"></i>
                                                </button>
                                            </form>
                                            <div class="flex items-center gap-2" data-branch-function-display-actions>
                                                <button type="button" class="kt-btn kt-btn-sm kt-btn-outline" data-branch-function-edit-btn title="Bewerken">
                                                    <i class="ki-filled ki-notepad-edit"></i>
                                                </button>
                                                <form method="POST"
                                                      action="{{ route('admin.skillmatching.branches.functions.destroy', [$branch, $function]) }}"
                                                      class="inline-flex"
                                                      data-branch-functions-delete-form
                                                      data-branch-function-delete-form-wrapper
                                                      onsubmit="return confirm('Weet je zeker dat je deze functie wilt verwijderen?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="kt-btn kt-btn-sm kt-btn-outline text-danger" title="Verwijderen">
                                                        <i class="ki-filled ki-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canEditBranch ? 3 : 2 }}" class="text-center text-muted-foreground py-8">
                                    Geen functies gevonden voor deze branch.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
@if($canEditBranch)
<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkbox = document.getElementById('toggle-status-checkbox');
    const form = document.getElementById('toggle-status-form');
    if (!checkbox || !form) return;

    checkbox.addEventListener('change', function(e) {
        e.preventDefault();

        const formData = new FormData(form);
        const url = form.action;
        const originalChecked = this.checked;

        fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || formData.get('_token')
            },
            credentials: 'same-origin'
        })
        .then(r => r.ok ? r.json() : Promise.reject(new Error('Network response was not ok')))
        .then(data => {
            if (data.success) window.location.reload();
        })
        .catch(err => {
            console.error(err);
            checkbox.checked = !originalChecked;
            alert('Fout: status wijzigen is mislukt.');
        });
    });
});
</script>
@endif
<script src="{{ asset('assets/js/form-validation.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const card = document.getElementById('branch-functions-card');
    if (!card) return;

    const tbody = document.getElementById('branch-functions-tbody');
    const flash = document.getElementById('branch-functions-flash');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    function setFlash(message, isError = false) {
        if (!flash) return;
        flash.textContent = message || '';
        flash.classList.toggle('text-danger', !!isError);
        flash.classList.toggle('text-muted-foreground', !isError);
        if (message) {
            window.clearTimeout(setFlash._t);
            setFlash._t = window.setTimeout(() => { flash.textContent = ''; }, 3500);
        }
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function sortRows() {
        if (!tbody) return;
        const rows = Array.from(tbody.querySelectorAll('tr[data-branch-function-id]'));
        rows.sort((a, b) => {
            const ak = (a.getAttribute('data-branch-function-key') || '').toLowerCase();
            const bk = (b.getAttribute('data-branch-function-key') || '').toLowerCase();
            return ak.localeCompare(bk, 'nl');
        });
        rows.forEach(r => tbody.appendChild(r));
    }

    function toggleEditMode(tr, showEdit) {
        const displayActions = tr.querySelector('[data-branch-function-display-actions]');
        const updateForm = tr.querySelector('[data-branch-functions-update-form]');
        const deleteFormWrapper = tr.querySelector('[data-branch-function-delete-form-wrapper]');
        
        if (!displayActions || !updateForm) return;
        
        if (showEdit) {
            // Hide edit button, keep delete button visible
            const editBtn = displayActions.querySelector('[data-branch-function-edit-btn]');
            if (editBtn) editBtn.classList.add('hidden');
            
            // Show update form (input + save + cancel)
            updateForm.classList.remove('hidden');
            updateForm.classList.add('flex');
            
            const input = updateForm.querySelector('input[name="name"]');
            if (input) {
                setTimeout(() => {
                    input.focus();
                    input.select();
                }, 10);
            }
        } else {
            // Show edit button again
            const editBtn = displayActions.querySelector('[data-branch-function-edit-btn]');
            if (editBtn) editBtn.classList.remove('hidden');
            
            // Hide update form
            updateForm.classList.add('hidden');
            updateForm.classList.remove('flex');
        }
    }

    // Initialize all rows to display mode on page load
    if (tbody) {
        const rows = tbody.querySelectorAll('tr[data-branch-function-id]');
        rows.forEach(row => toggleEditMode(row, false));
    }

    // Function to attach event listeners to a row
    function attachRowListeners(tr) {
        const editBtn = tr.querySelector('[data-branch-function-edit-btn]');
        const cancelBtn = tr.querySelector('[data-branch-function-cancel-btn]');
        const updateForm = tr.querySelector('[data-branch-functions-update-form]');
        const input = updateForm?.querySelector('input[name="name"]');
        
        if (editBtn) {
            editBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleEditMode(tr, true);
            });
        }
        
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const displayName = tr.querySelector('[data-branch-function-display]')?.textContent || '';
                if (input) input.value = displayName;
                toggleEditMode(tr, false);
            });
        }
        
        // Submit form on Enter key
        if (input) {
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (updateForm) {
                        updateForm.requestSubmit();
                    }
                }
            });
        }
    }

    // Attach listeners to existing rows
    if (tbody) {
        const rows = tbody.querySelectorAll('tr[data-branch-function-id]');
        rows.forEach(row => attachRowListeners(row));
    }

    function buildRow(fn) {
        const id = fn.id;
        const name = fn.name;
        const display = fn.display_name;
        const updateUrl = fn.update_url;
        const destroyUrl = fn.destroy_url;

        const tr = document.createElement('tr');
        tr.setAttribute('data-branch-function-id', String(id));
        tr.setAttribute('data-branch-function-key', String(name));

        tr.innerHTML = `
            <td class="text-foreground font-medium" data-branch-function-display>${escapeHtml(display)}</td>
            <td class="text-muted-foreground font-normal" data-branch-function-code><code class="px-2 py-1 rounded text-xs font-mono">${escapeHtml(name)}</code></td>
            <td class="text-end w-[50px]" onclick="event.stopPropagation();">
                <div class="flex items-center justify-end gap-2" data-branch-function-actions>
                    <form method="POST" action="${escapeHtml(updateUrl)}" class="hidden flex items-center gap-2" data-branch-functions-update-form data-validate="true">
                        <input type="hidden" name="_token" value="${escapeHtml(csrf || '')}">
                        <input type="hidden" name="_method" value="PUT">
                        <input type="text" name="name" class="kt-input kt-input-sm w-[200px]" value="${escapeHtml(display)}" required>
                        <button type="submit" class="kt-btn kt-btn-sm kt-btn-outline" title="Opslaan">
                            <i class="ki-filled ki-check"></i>
                        </button>
                        <button type="button" class="kt-btn kt-btn-sm kt-btn-outline" data-branch-function-cancel-btn title="Annuleren">
                            <i class="ki-filled ki-cross-circle"></i>
                        </button>
                    </form>
                    <div class="flex items-center gap-2" data-branch-function-display-actions>
                        <button type="button" class="kt-btn kt-btn-sm kt-btn-outline" data-branch-function-edit-btn title="Bewerken">
                            <i class="ki-filled ki-notepad-edit"></i>
                        </button>
                        <form method="POST" action="${escapeHtml(destroyUrl)}" class="inline-flex" data-branch-functions-delete-form data-branch-function-delete-form-wrapper onsubmit="return confirm('Weet je zeker dat je deze functie wilt verwijderen?');">
                            <input type="hidden" name="_token" value="${escapeHtml(csrf || '')}">
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="kt-btn kt-btn-sm kt-btn-outline text-danger" title="Verwijderen">
                                <i class="ki-filled ki-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </td>
        `;

        return tr;
    }

    async function submitAjax(form) {
        const formData = new FormData(form);
        const url = form.action;

        const res = await fetch(url, {
            method: (form.getAttribute('method') || 'POST').toUpperCase(),
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
            },
            credentials: 'same-origin',
        });

        let json = null;
        try { json = await res.json(); } catch (_) {}

        if (!res.ok) {
            const msg = (json && (json.message || json.error)) || 'Opslaan is mislukt.';
            throw { status: res.status, json, message: msg };
        }

        return json;
    }

    // Create
    card.addEventListener('submit', async function (e) {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;

        if (form.hasAttribute('data-branch-functions-create-form')) {
            e.preventDefault();
            try {
                const json = await submitAjax(form);
                if (json?.function && tbody) {
                    const newRow = buildRow(json.function);
                    tbody.appendChild(newRow);
                    attachRowListeners(newRow);
                    sortRows();
                }
                const input = form.querySelector('input[name=\"name\"]');
                if (input) input.value = '';
                setFlash(json?.message || 'Functie toegevoegd.');
            } catch (err) {
                setFlash(err?.message || 'Toevoegen is mislukt.', true);
            }
        }
    });

    // Update/Delete (delegation)
    card.addEventListener('submit', async function (e) {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;

        if (form.hasAttribute('data-branch-functions-update-form') || form.hasAttribute('data-branch-functions-delete-form')) {
            e.preventDefault();

            // keep confirm() behavior for delete
            if (form.hasAttribute('data-branch-functions-delete-form')) {
                // confirm already handled by onsubmit attr; if it returned false we wouldn't be here
            }

            try {
                const json = await submitAjax(form);
                const tr = form.closest('tr[data-branch-function-id]');

                if (form.hasAttribute('data-branch-functions-delete-form')) {
                    if (tr) tr.remove();
                    setFlash(json?.message || 'Functie verwijderd.');
                    return;
                }

                if (json?.function && tr) {
                    // Refresh only the updated row so it returns to display mode.
                    const destroyUrl = tr.querySelector('form[data-branch-functions-delete-form]')?.action || '';
                    const updateUrl = form.action || '';
                    const refreshed = buildRow({
                        id: json.function.id,
                        name: json.function.name,
                        display_name: json.function.display_name,
                        update_url: updateUrl,
                        destroy_url: destroyUrl,
                    });
                    tr.replaceWith(refreshed);
                    attachRowListeners(refreshed);
                    sortRows();
                    // Ensure new row is in display mode (not edit mode)
                    toggleEditMode(refreshed, false);
                }

                setFlash(json?.message || 'Functie bijgewerkt.');
            } catch (err) {
                setFlash(err?.message || 'Opslaan is mislukt.', true);
            }
        }
    }, true);
});
</script>
@endpush

@push('styles')
<style>
    .kt-table-border-dashed tbody tr { border-bottom: none !important; }
    .kt-table-border-dashed tbody tr td { padding-top: 12px; padding-bottom: 12px; vertical-align: top; }
    
    /* Code element styling voor dark mode compatibiliteit */
    [data-branch-function-code] code {
        background-color: rgba(0, 0, 0, 0.05) !important;
        color: #1f2937 !important;
        border: 1px solid rgba(0, 0, 0, 0.1) !important;
    }
    
    .dark [data-branch-function-code] code {
        background-color: rgba(255, 255, 255, 0.1) !important;
        color: #e5e7eb !important;
        border-color: rgba(255, 255, 255, 0.2) !important;
    }
</style>
@endpush
@endsection
