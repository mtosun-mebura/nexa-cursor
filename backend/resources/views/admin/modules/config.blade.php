@extends('admin.layouts.app')

@section('title', 'Module configuratie – ' . $module->getDisplayName())

@push('styles')
<style>
    /* Checkbox styling consistent met andere admin pagina's */
    .module-config-checkbox {
        width: 20px !important;
        height: 20px !important;
        min-width: 20px !important;
        min-height: 20px !important;
        border-width: 1px !important;
        border-color: #555555;
        color: #555555;
        padding-right: 0 !important;
        cursor: pointer;
        appearance: none !important;
        -webkit-appearance: none !important;
        -moz-appearance: none !important;
        background-color: transparent !important;
        transition: all 0.2s !important;
    }

    .module-config-checkbox:hover {
        border-color: #555555;
        background-color: rgba(85, 85, 85, 0.1);
    }

    .module-config-checkbox:checked {
        border-color: #10b981 !important;
        background-color: transparent !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 20 20' fill='none'%3E%3Cpath fill-rule='evenodd' d='M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z' clip-rule='evenodd' fill='%2310b981'/%3E%3C/svg%3E") !important;
        background-position: center !important;
        background-repeat: no-repeat !important;
        background-size: 20px 20px !important;
        border-width: 1px !important;
        color: #10b981 !important;
    }

    .module-config-checkbox:focus-visible {
        --tw-ring-color: #555555;
        --tw-ring-offset-width: 2px;
        outline: 2px solid transparent;
        outline-offset: 2px;
    }

    /* Code element styling voor dark mode compatibiliteit */
    code {
        background-color: rgba(0, 0, 0, 0.05) !important;
        color: #1f2937 !important;
        border: 1px solid rgba(0, 0, 0, 0.1) !important;
    }

    .dark code {
        background-color: rgba(255, 255, 255, 0.1) !important;
        color: #e5e7eb !important;
        border-color: rgba(255, 255, 255, 0.2) !important;
    }

    /* Meer ruimte boven card headers op deze pagina */
    .module-config-form .kt-card-header {
        padding-top: 1.5rem;
    }
</style>
@endpush

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <div class="flex items-center gap-3">
            <i class="{{ $module->getIcon() }} text-2xl text-primary"></i>
            <div>
                <h1 class="text-xl font-medium leading-none text-mono">
                    {{ $module->getDisplayName() }}
                </h1>
                <p class="text-sm text-muted-foreground mt-0.5">Onderdelen in sidebar en tenant koppelen</p>
            </div>
        </div>
        <a href="{{ route('admin.modules.index') }}" class="kt-btn kt-btn-outline">
            <i class="ki-filled ki-arrow-left me-2"></i>
            Terug
        </a>
    </div>

    <form action="{{ route('admin.modules.config.store', $moduleName) }}" method="POST" class="module-config-form">
        @csrf

        <div class="grid gap-5 lg:gap-7.5">
            <x-error-card :errors="$errors" />

            @if(session('success'))
                <div class="kt-alert kt-alert-success" id="success-alert" role="alert">
                    <i class="ki-filled ki-check-circle me-2"></i>
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="kt-alert kt-alert-danger" id="error-alert" role="alert">
                    <i class="ki-filled ki-cross-circle me-2"></i>
                    {{ session('error') }}
                </div>
            @endif

            <!-- Tenant: welk bedrijf hoort bij deze module -->
            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Tenant</h3>
                    <p class="text-sm text-muted-foreground mt-1">Koppel deze module aan een bedrijf. Als de module actief is, wordt bij inloggen in het beheer automatisch deze tenant gekozen (sidebar en formulieren).</p>
                </div>
                <div class="kt-card-content">
                    <div class="mb-0">
                        <label for="company_id" class="kt-form-label mb-2">Bedrijf</label>
                        <select name="company_id" id="company_id" class="kt-input w-full max-w-md">
                            <option value="">— Geen tenant (alle bedrijven) —</option>
                            @foreach($companies ?? [] as $company)
                                <option value="{{ $company->id }}" {{ (int) old('company_id', $company_id ?? 0) === (int) $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Applicatie naam en omschrijving (meta, header, logo's) -->
            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Applicatie</h3>
                    <p class="text-sm text-muted-foreground mt-1">Naam en omschrijving van de applicatie voor deze module. Wordt gebruikt in meta-tags, header en als alt-tekst bij het logo zodra de module actief is.</p>
                </div>
                <div class="kt-card-content">
                    <div class="mb-6">
                        <label for="app_name" class="kt-form-label mb-2">Naam van de applicatie</label>
                        <input type="text" name="app_name" id="app_name" class="kt-input w-full max-w-md" value="{{ old('app_name', $app_name ?? '') }}" placeholder="{{ config('app.name') }}">
                        <p class="text-xs text-muted-foreground mt-1">Wordt o.a. getoond in de footer, in de titel en als alt-tekst bij het logo.</p>
                    </div>
                    <div class="mb-6">
                        <label for="app_description" class="kt-form-label mb-2">Omschrijving</label>
                        <textarea name="app_description" id="app_description" class="kt-input w-full max-w-md min-h-[100px]" rows="4" placeholder="Korte omschrijving van de applicatie...">{{ old('app_description', $app_description ?? '') }}</textarea>
                        <p class="text-xs text-muted-foreground mt-1">Gebruikt o.a. in meta description voor zoekmachines.</p>
                    </div>
                    @php
                        $dashOld = old('dashboard_link_visible', $dashboard_link_visible ?? false);
                        $dashChecked = $dashOld === true || $dashOld === 1 || $dashOld === '1' || $dashOld === 'true';
                    @endphp
                    <div class="mb-6 flex flex-wrap items-center gap-3">
                        <label class="kt-form-label mb-0">Knop Mijn-omgeving tonen</label>
                        {{-- Eén POST-veld: hidden. kt-switch + dubbele name= veroorzaakte array/false boolean in Laravel → sleutel werd nooit betrouwbaar opgeslagen. --}}
                        <input type="hidden" name="dashboard_link_visible" id="dashboard_link_visible_hidden" value="{{ $dashChecked ? '1' : '0' }}">
                        <input type="checkbox" id="dashboard_link_visible" class="kt-switch kt-switch-sm" value="1" {{ $dashChecked ? 'checked' : '' }} autocomplete="off">
                        <span class="text-sm text-muted-foreground">Toon de knop in de header die naar het dashboard gaat.</span>
                    </div>
                    <div class="mb-6">
                        <label for="dashboard_link_label" class="kt-form-label mb-2">Naam van de Mijn-omgeving</label>
                        <input type="text" name="dashboard_link_label" id="dashboard_link_label" class="kt-input w-full max-w-md" value="{{ old('dashboard_link_label', $dashboard_link_label ?? 'Mijn Nexa') }}" placeholder="Mijn Nexa">
                        <p class="text-xs text-muted-foreground mt-1">Tekst van de knop in de header (bijv. "Mijn Nexa", "Mijn omgeving").</p>
                    </div>
                </div>
            </div>

            <!-- Module Configuratie -->
            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Onderdelen in menu</h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    @if(empty($availableItems))
                        <div class="p-5 text-center">
                            <p class="text-sm text-muted-foreground">Deze module heeft geen configureerbare onderdelen.</p>
                        </div>
                    @else
                        <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                            <thead>
                                <tr>
                                    <th class="min-w-[50px] text-secondary-foreground font-normal">Actief</th>
                                    <th class="min-w-[200px] text-secondary-foreground font-normal">Onderdeel</th>
                                    <th class="text-secondary-foreground font-normal">Beschrijving</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($availableItems as $item)
                                    <tr>
                                        <td>
                                            <input type="checkbox"
                                                   name="enabled_menu_items[]"
                                                   value="{{ $item['key'] }}"
                                                   class="module-config-checkbox"
                                                   id="menu-item-{{ $item['key'] }}"
                                                   {{ isset($enabledKeys[$item['key']]) ? 'checked' : '' }}>
                                        </td>
                                        <td>
                                            <label for="menu-item-{{ $item['key'] }}" class="flex items-center gap-2 cursor-pointer">
                                                <i class="{{ $item['icon'] ?? 'ki-filled ki-element-11' }} text-lg text-muted-foreground"></i>
                                                <span class="font-medium text-foreground">{{ $item['title'] }}</span>
                                            </label>
                                        </td>
                                        <td>
                                            <span class="text-xs text-muted-foreground">
                                                @if(isset($item['route']))
                                                    Route: <code class="px-1 py-0.5 rounded text-xs">{{ $item['route'] }}</code>
                                                @endif
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5">
                <a href="{{ route('admin.modules.index') }}" class="kt-btn kt-btn-outline">Annuleren</a>
                <button type="submit" class="kt-btn kt-btn-primary"><i class="ki-filled ki-check me-2"></i>Opslaan</button>
            </div>

            <!-- Info Card -->
            <div class="kt-card min-w-full" style="background-color: rgba(59, 130, 246, 0.08); border: 1px solid rgba(59, 130, 246, 0.25);">
                <div class="kt-card-content p-5 lg:px-7 lg:py-6">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            <i class="ki-filled ki-information-5 text-2xl" style="color: rgb(59, 130, 246);"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-semibold mb-1 text-foreground">Hoe werkt module configuratie?</h4>
                            <p class="text-sm text-muted-foreground">
                                Vink aan welke onderdelen bij deze module in het Beheer-menu getoond worden. Uitgevinkte onderdelen blijven beschikbaar via hun eigen route, maar staan niet onder deze module in de sidebar.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
(function () {
    var form = document.querySelector('form.module-config-form');
    var cb = document.getElementById('dashboard_link_visible');
    var hidden = document.getElementById('dashboard_link_visible_hidden');
    if (!form || !cb || !hidden) return;
    function syncDashboardHidden() {
        hidden.value = cb.checked ? '1' : '0';
    }
    cb.addEventListener('change', syncDashboardHidden);
    form.addEventListener('submit', syncDashboardHidden);
})();
</script>

@endsection
