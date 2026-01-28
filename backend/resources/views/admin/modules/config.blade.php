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
</style>
@endpush

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-col gap-5 pb-7.5">
        <div class="flex flex-wrap items-center justify-between gap-5">
            <div class="flex items-center gap-3">
                <i class="{{ $module->getIcon() }} text-2xl text-primary"></i>
                <div>
                    <h1 class="text-xl font-medium leading-none text-mono">
                        {{ $module->getDisplayName() }}
                    </h1>
                    <p class="text-sm text-muted-foreground mt-0.5">Onderdelen in sidebar selecteren</p>
                </div>
            </div>
        </div>
        <div class="flex items-center">
            <a href="{{ route('admin.modules.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
    </div>

    <form action="{{ route('admin.modules.config.store', $moduleName) }}" method="POST">
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
                @if(!empty($availableItems))
                    <div class="kt-card-footer flex items-center justify-end gap-2 pt-3">
                        <a href="{{ route('admin.modules.index') }}" class="kt-btn kt-btn-outline">
                            Annuleren
                        </a>
                        <button type="submit" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-check me-2"></i>
                            Opslaan
                        </button>
                    </div>
                @endif
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

@endsection
