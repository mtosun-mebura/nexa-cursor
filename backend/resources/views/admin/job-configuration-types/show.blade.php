@extends('admin.layouts.app')

@section('title', 'Job Configuratie Type Details - ' . $jobConfigurationType->display_name)

@push('styles')
<style>
    .hero-bg {
        background-image: url('{{ asset('assets/media/images/2600x1200/bg-1.png') }}');
    }
    .dark .hero-bg {
        background-image: url('{{ asset('assets/media/images/2600x1200/bg-1-dark.png') }}');
    }
</style>
@endpush

@section('content')

<div class="bg-center bg-cover bg-no-repeat hero-bg">
    <div class="kt-container-fixed">
        <div class="flex flex-col items-center gap-2 lg:gap-3.5 py-4 lg:pt-5 lg:pb-10">
            <div class="rounded-full border-3 {{ $jobConfigurationType->is_active ? 'border-green-500' : 'border-red-500' }} size-[100px] shrink-0 flex items-center justify-center bg-primary/10">
                <i class="ki-filled ki-tag text-4xl text-primary"></i>
            </div>
            <div class="flex items-center gap-1.5">
                <div class="text-lg leading-5 font-semibold text-mono">
                    {{ $jobConfigurationType->display_name }}
                </div>
                @if($jobConfigurationType->is_active)
                    <span class="kt-badge kt-badge-sm kt-badge-success">Actief</span>
                @else
                    <span class="kt-badge kt-badge-sm kt-badge-danger">Inactief</span>
                @endif
            </div>
            <div class="flex flex-wrap justify-center gap-1 lg:gap-4.5 text-sm">
                <div class="flex gap-1.25 items-center">
                    <i class="ki-filled ki-setting-3 text-muted-foreground text-sm"></i>
                    <span class="text-secondary-foreground font-medium">
                        {{ $configurationsCount ?? 0 }} configuraties
                    </span>
                </div>
                <div class="flex gap-1.25 items-center">
                    <i class="ki-filled ki-calendar text-muted-foreground text-sm"></i>
                    <span class="text-secondary-foreground font-medium">
                        {{ $jobConfigurationType->created_at->format('d-m-Y') }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="flex items-center flex-wrap md:flex-nowrap lg:items-center justify-between gap-3 lg:gap-6 mb-5 lg:mb-10">
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.job-configuration-types.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
        <div class="flex items-center gap-2.5">
            @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('edit-job-configurations'))
            @php
                $canDeactivate = ($configurationsCount ?? 0) == 0;
            @endphp
            <form action="{{ route('admin.job-configuration-types.toggle-status', $jobConfigurationType) }}" method="POST" id="toggle-status-form" class="inline">
                @csrf
                <label class="kt-label flex items-center">
                    <input type="checkbox" 
                           class="kt-switch kt-switch-sm" 
                           id="toggle-status-checkbox"
                           {{ $jobConfigurationType->is_active ? 'checked' : '' }}
                           {{ ($jobConfigurationType->is_active && !$canDeactivate) ? 'disabled' : '' }}
                           title="{{ ($jobConfigurationType->is_active && !$canDeactivate) ? 'Kan niet worden gedeactiveerd omdat het in gebruik is door ' . ($configurationsCount ?? 0) . ' configuratie(s)' : '' }}"/>
                    <span class="ms-2">Actief</span>
                </label>
            </form>
            <span class="text-orange-500">|</span>
            <a href="{{ route('admin.job-configuration-types.edit', $jobConfigurationType) }}" class="kt-btn kt-btn-warning">
                <i class="ki-filled ki-pencil me-2"></i>
                Bewerken
            </a>
            @endif
            @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('delete-job-configurations'))
                @if(($configurationsCount ?? 0) == 0)
                <form action="{{ route('admin.job-configuration-types.destroy', $jobConfigurationType) }}" 
                      method="POST" 
                      class="inline"
                      onsubmit="return confirm('Weet je zeker dat je dit type wilt verwijderen?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="kt-btn kt-btn-danger">
                        <i class="ki-filled ki-trash me-2"></i>
                        Verwijderen
                    </button>
                </form>
                @endif
            @endif
        </div>
    </div>

    <div class="grid gap-5 lg:gap-7.5">
        <!-- Basic Information -->
        <div class="kt-card min-w-full">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Basis Informatie</h3>
            </div>
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Weergave Naam</td>
                        <td class="min-w-48 w-full">
                            <span class="text-foreground font-medium">{{ $jobConfigurationType->display_name }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Naam</td>
                        <td class="min-w-48 w-full">
                            <code class="text-sm">{{ $jobConfigurationType->name }}</code>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal align-top">Beschrijving</td>
                        <td class="min-w-48 w-full">
                            <span class="text-foreground">{{ $jobConfigurationType->description ?: '-' }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Status</td>
                        <td class="min-w-48 w-full">
                            @if($jobConfigurationType->is_active)
                                <span class="kt-badge kt-badge-sm kt-badge-success">Actief</span>
                            @else
                                <span class="kt-badge kt-badge-sm kt-badge-danger">Inactief</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Volgorde</td>
                        <td class="min-w-48 w-full">
                            <span class="text-foreground">{{ $jobConfigurationType->sort_order }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Configuraties</td>
                        <td class="min-w-48 w-full">
                            <span class="kt-badge kt-badge-sm kt-badge-info">{{ $configurationsCount ?? 0 }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Aangemaakt</td>
                        <td class="min-w-48 w-full">
                            <span class="text-foreground">{{ $jobConfigurationType->created_at->format('d-m-Y H:i') }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Laatst bijgewerkt</td>
                        <td class="min-w-48 w-full">
                            <span class="text-foreground">{{ $jobConfigurationType->updated_at->format('d-m-Y H:i') }}</span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .kt-btn-danger {
        background-color: #ef4444 !important;
        color: white !important;
    }
    .kt-btn-danger:hover {
        background-color: #dc2626 !important;
    }
    .dark .kt-btn-danger {
        background-color: #dc2626 !important;
    }
    .dark .kt-btn-danger:hover {
        background-color: #b91c1c !important;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleCheckbox = document.getElementById('toggle-status-checkbox');
    const toggleForm = document.getElementById('toggle-status-form');
    
    if (toggleCheckbox && toggleForm) {
        toggleCheckbox.addEventListener('change', function() {
            toggleForm.submit();
        });
    }
});
</script>
@endpush

@endsection
