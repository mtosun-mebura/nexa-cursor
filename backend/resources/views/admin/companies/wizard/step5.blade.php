@extends('admin.companies.wizard.layout')

@section('title', __('Stap 5 — Gebruikers & rollen'))

@section('wizard_content')
<form method="post" action="{{ route('admin.companies.wizard.submit-step', [$company, 5]) }}">
    @csrf
    <div class="kt-card min-w-full mb-6 overflow-hidden">
        <div class="kt-card-header">
            <h3 class="kt-card-title">{{ __('Gebruikers & rollen') }}</h3>
        </div>
        <div class="px-5 sm:px-6 py-4 min-w-0 max-w-full">
            <p class="text-sm text-secondary-foreground mb-3 break-words">
                {!! __('Bij afronden van de wizard wordt automatisch een :role aangemaakt. Gebruikersnaam is het e-mailadres van de contactpersoon (:email). Die persoon ontvangt de welkomstmail. De eerste login gaat via een eenmalige code per e-mail, daarna kiest hij of zij zelf een wachtwoord.', ['role' => '<strong>company-admin</strong>', 'email' => '<strong class="break-all">'.e($company->email).'</strong>']) !!}
            </p>
            <ul class="list-disc ps-5 text-sm text-secondary-foreground space-y-1 mb-4 break-words">
                <li>{!! __('Optioneel: extra medewerkers via :button.', ['button' => '<strong>'.e(__('Nieuwe gebruiker')).'</strong>']) !!}</li>
                <li>{{ __('De eerste inlog toont «Eerste keer inloggen»: code aanvragen, daarna een eigen wachtwoord kiezen.') }}</li>
                <li>{{ __('Daarna opent de welkomsthandleiding voor het gekozen pakket.') }}</li>
            </ul>
            <div class="flex flex-wrap items-center gap-2">
                @can('create-users')
                    <a href="{{ route('admin.users.create', ['from_wizard' => 1, 'wizard_company' => $company->id, 'wizard_step' => $currentStep, 'company_id' => $company->id]) }}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-plus me-2"></i>
                        {{ __('Nieuwe gebruiker') }}
                    </a>
                @endcan
                <a href="{{ route('admin.companies.wizard.step', [$company, $currentStep]) }}" class="kt-btn kt-btn-light">
                    <i class="ki-filled ki-arrows-circle me-2"></i>
                    {{ __('Lijst verversen') }}
                </a>
            </div>
        </div>
    </div>

    <div class="kt-card min-w-full mb-6">
        <div class="kt-card-header flex flex-wrap items-center justify-between gap-2">
            <h3 class="kt-card-title">{{ __('Gebruikers van dit bedrijf') }}</h3>
            <span class="text-xs text-muted-foreground">{{ $companyUsers->count() }} {{ $companyUsers->count() === 1 ? 'gebruiker' : 'gebruikers' }}</span>
        </div>
        <div class="kt-card-table kt-scrollable-x-auto pb-3">
            @if($companyUsers->isEmpty())
                <p class="text-sm text-secondary-foreground py-4 ps-6">
                    {!! __('Nog geen gebruikers gekoppeld aan dit bedrijf. Voeg er een toe met :button.', ['button' => '<strong>'.e(__('Nieuwe gebruiker')).'</strong>']) !!}
                </p>
            @else
                <table class="kt-table kt-table-border-dashed align-middle text-sm w-full">
                    <thead>
                        <tr class="text-left text-muted-foreground">
                            <th class="py-2 pe-3 font-medium">{{ __('Naam') }}</th>
                            <th class="py-2 pe-3 font-medium">{{ __('E-mail') }}</th>
                            <th class="py-2 pe-3 font-medium">{{ __('Rollen') }}</th>
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
                                        @forelse($u->webRoleNames() as $roleName)
                                            <span class="inline-flex items-center rounded-md border border-border px-2 py-0.5 text-xs leading-5">{{ ucfirst(str_replace('-', ' ', $roleName)) }}</span>
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
            {{ __('Volgende') }}
            <i class="ki-filled ki-arrow-right ms-2"></i>
        </button>
    </x-wizard.footer-actions>
</form>

@if(auth()->user()?->isSuperAdmin())
    @include('admin.companies.partials.config-access', [
        'company' => $company,
        'configAccessUsers' => $configAccessUsers ?? collect(),
        'grantsByUserId' => $grantsByUserId ?? [],
        'configAccessRedirect' => request()->fullUrl(),
    ])
@endif
@endsection
