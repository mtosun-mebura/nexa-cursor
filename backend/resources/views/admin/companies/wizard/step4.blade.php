@extends('admin.companies.wizard.layout')

@section('title', 'Stap 4 — Modules')

@section('wizard_content')
<form method="post" action="{{ route('admin.companies.wizard.submit-step', [$company, 4]) }}">
    @csrf
    <x-error-card :errors="$errors" />

    <div class="kt-card min-w-full mb-6">
        <div class="kt-card-header">
            <h3 class="kt-card-title">Modules voor deze tenant</h3>
        </div>
        <p class="text-sm text-secondary-foreground px-6 pt-2 pb-3 mb-0 max-w-3xl">
            Vink de modules aan die dit bedrijf mag gebruiken. Niet-geïnstalleerde of niet-actieve modules worden automatisch geïnstalleerd en geactiveerd waar mogelijk.
        </p>
        @if($allModules->isEmpty())
            <div class="px-6 pb-6">
                <p class="text-sm text-muted-foreground">Er zijn nog geen modules in de database. Registreer modules via <a href="{{ route('admin.modules.index') }}" class="font-medium text-primary underline underline-offset-2 hover:text-primary/90">Modules</a>.</p>
            </div>
        @else
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                    <thead>
                        <tr>
                            <th class="w-12"></th>
                            <th>Module</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($allModules as $mod)
                            @php
                                $attached = $company->modules->contains('id', $mod->id);
                            @endphp
                            <tr>
                                <td>
                                    <input type="checkbox" class="kt-checkbox" name="module_ids[]" value="{{ $mod->id }}" {{ $attached ? 'checked' : '' }}>
                                </td>
                                <td class="font-medium">{{ $mod->display_name }} <span class="text-muted-foreground text-xs">({{ $mod->name }})</span></td>
                                <td>
                                    @if($mod->installed && $mod->active)
                                        <span class="kt-badge kt-badge-sm kt-badge-success">Actief</span>
                                    @elseif($mod->installed)
                                        <span class="kt-badge kt-badge-sm kt-badge-warning">Geïnstalleerd</span>
                                    @else
                                        <span class="kt-badge kt-badge-sm kt-badge-outline">Niet geïnstalleerd</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <x-wizard.footer-actions :current-step="$currentStep" :company="$company">
        <button type="submit" class="kt-btn kt-btn-primary">
            Volgende
            <i class="ki-filled ki-arrow-right ms-2"></i>
        </button>
    </x-wizard.footer-actions>
</form>
@endsection
