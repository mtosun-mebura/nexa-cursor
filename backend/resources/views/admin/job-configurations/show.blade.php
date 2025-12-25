@extends('admin.layouts.app')

@section('title', 'Job Configuratie Details - ' . $jobConfiguration->value)

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
            <div class="rounded-full border-3 {{ $inUse ? 'border-orange-500' : 'border-green-500' }} size-[100px] shrink-0 flex items-center justify-center bg-primary/10">
                <i class="ki-filled ki-setting-3 text-4xl text-primary"></i>
            </div>
            <div class="flex items-center gap-1.5">
                <div class="text-lg leading-5 font-semibold text-mono">
                    {{ $jobConfiguration->value }}
                </div>
                <span class="kt-badge kt-badge-sm kt-badge-info">{{ $jobConfiguration->type_display }}</span>
            </div>
            <div class="flex flex-wrap justify-center gap-1 lg:gap-4.5 text-sm">
                <div class="flex gap-1.25 items-center">
                    <i class="ki-filled ki-briefcase text-muted-foreground text-sm"></i>
                    <span class="text-secondary-foreground font-medium">
                        {{ $usageCount ?? 0 }} vacature(s)
                    </span>
                </div>
                <div class="flex gap-1.25 items-center">
                    <i class="ki-filled ki-building text-muted-foreground text-sm"></i>
                    <span class="text-secondary-foreground font-medium">
                        {{ $jobConfiguration->company ? $jobConfiguration->company->name : 'Globaal' }}
                    </span>
                </div>
                <div class="flex gap-1.25 items-center">
                    <i class="ki-filled ki-calendar text-muted-foreground text-sm"></i>
                    <span class="text-secondary-foreground font-medium">
                        {{ $jobConfiguration->created_at->format('d-m-Y') }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="flex items-center flex-wrap md:flex-nowrap lg:items-center justify-between gap-3 lg:gap-6 mb-5 lg:mb-10">
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.job-configurations.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
        <div class="flex items-center gap-2.5">
            @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('edit-job-configurations'))
                <span class="text-orange-500">|</span>
                <a href="{{ route('admin.job-configurations.edit', $jobConfiguration) }}" class="kt-btn kt-btn-warning">
                    <i class="ki-filled ki-pencil me-2"></i>
                    Bewerken
                </a>
            @endif
            @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('delete-job-configurations'))
                @if(!$inUse)
                <form action="{{ route('admin.job-configurations.destroy', $jobConfiguration) }}" 
                      method="POST" 
                      class="inline"
                      onsubmit="return confirm('Weet je zeker dat je deze configuratie wilt verwijderen?')">
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
                        <td class="min-w-56 text-secondary-foreground font-normal">Type</td>
                        <td class="min-w-48 w-full">
                            <span class="kt-badge kt-badge-sm kt-badge-info">{{ $jobConfiguration->type_display }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Waarde</td>
                        <td class="min-w-48 w-full">
                            <span class="text-foreground font-medium">{{ $jobConfiguration->value }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Bedrijf</td>
                        <td class="min-w-48 w-full">
                            @if($jobConfiguration->company)
                                <span class="kt-badge kt-badge-sm kt-badge-secondary">{{ $jobConfiguration->company->name }}</span>
                            @else
                                <span class="kt-badge kt-badge-sm kt-badge-primary">Globaal</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">In Gebruik</td>
                        <td class="min-w-48 w-full">
                            @if($inUse)
                                <span class="kt-badge kt-badge-sm kt-badge-warning">
                                    Ja ({{ $usageCount }} vacature(s))
                                </span>
                            @else
                                <span class="kt-badge kt-badge-sm kt-badge-success">Nee</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Aangemaakt</td>
                        <td class="min-w-48 w-full">
                            <span class="text-foreground">{{ $jobConfiguration->created_at->format('d-m-Y H:i') }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Laatst bijgewerkt</td>
                        <td class="min-w-48 w-full">
                            <span class="text-foreground">{{ $jobConfiguration->updated_at->format('d-m-Y H:i') }}</span>
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

@endsection
