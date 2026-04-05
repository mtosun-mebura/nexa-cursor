@extends('admin.companies.wizard.layout')

@section('title', 'Stap 5 — Gebruikers & rollen')

@section('wizard_content')
<form method="post" action="{{ route('admin.companies.wizard.submit-step', [$company, 5]) }}">
    @csrf
    <div class="kt-card min-w-full mb-6">
        <div class="kt-card-header">
            <h3 class="kt-card-title">Gebruikers & rollen</h3>
        </div>
        <div class="kt-card-table kt-scrollable-x-auto pb-3">
            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table">
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Uitleg</td>
                    <td class="min-w-48 w-full">
                        <p class="text-sm text-secondary-foreground mb-3">
                            Koppel gebruikers aan dit bedrijf en wijs rollen toe via het gebruikersbeheer. Gebruikers krijgen alleen toegang tot data van hun tenant (en module-rechten) volgens de rollen.
                        </p>
                        <ul class="list-disc list-inside text-sm text-secondary-foreground space-y-1 mb-4">
                            <li>Maak een eerste bedrijfsbeheerder of medewerkers aan.</li>
                            <li>Hieronder zie je alle gebruikers van dit bedrijf; na toevoegen verschijnen ze in de lijst (ververs de pagina als je een ander tabblad hebt gebruikt).</li>
                            <li>Controleer permissies onder <strong>Rollen</strong> in het admin-menu.</li>
                        </ul>
                        <div class="flex flex-wrap items-center gap-2">
                            @can('create-users')
                                <a href="{{ route('admin.users.create', ['from_wizard' => 1, 'wizard_company' => $company->id, 'wizard_step' => $currentStep]) }}" class="kt-btn kt-btn-outline">
                                    <i class="ki-filled ki-plus me-2"></i>
                                    Nieuwe gebruiker
                                </a>
                            @endcan
                            <a href="{{ route('admin.companies.wizard.step', [$company, $currentStep]) }}" class="kt-btn kt-btn-light">
                                <i class="ki-filled ki-arrows-circle me-2"></i>
                                Lijst verversen
                            </a>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="kt-card min-w-full mb-6">
        <div class="kt-card-header flex flex-wrap items-center justify-between gap-2">
            <h3 class="kt-card-title">Gebruikers van dit bedrijf</h3>
            <span class="text-xs text-muted-foreground">{{ $companyUsers->count() }} {{ $companyUsers->count() === 1 ? 'gebruiker' : 'gebruikers' }}</span>
        </div>
        <div class="kt-card-table kt-scrollable-x-auto pb-3">
            @if($companyUsers->isEmpty())
                <p class="text-sm text-secondary-foreground py-4 ps-6">
                    Nog geen gebruikers gekoppeld aan dit bedrijf. Voeg er een toe met <strong>Nieuwe gebruiker</strong>.
                </p>
            @else
                <table class="kt-table kt-table-border-dashed align-middle text-sm w-full">
                    <thead>
                        <tr class="text-left text-muted-foreground">
                            <th class="py-2 pe-3 font-medium">Naam</th>
                            <th class="py-2 pe-3 font-medium">E-mail</th>
                            <th class="py-2 pe-3 font-medium">Rollen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($companyUsers as $u)
                            <tr>
                                <td class="py-2 pe-3 text-foreground align-middle">
                                    {{ $u->first_name }} {{ $u->last_name }}
                                </td>
                                <td class="py-2 pe-3 text-muted-foreground align-middle">{{ $u->email }}</td>
                                <td class="py-2 pe-3 text-muted-foreground align-middle">
                                    <div class="flex flex-wrap items-center gap-1.5 min-h-[2.25rem]">
                                        @forelse($u->roles->unique('name') as $r)
                                            <span class="inline-flex items-center rounded-md border border-border px-2 py-0.5 text-xs leading-5">{{ ucfirst(str_replace('-', ' ', $r->name)) }}</span>
                                        @empty
                                            <span class="text-muted-foreground">—</span>
                                        @endforelse
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <x-wizard.footer-actions :current-step="$currentStep" :company="$company">
        <button type="submit" class="kt-btn kt-btn-primary">
            Volgende
            <i class="ki-filled ki-arrow-right ms-2"></i>
        </button>
    </x-wizard.footer-actions>
</form>
@endsection
