@extends('admin.companies.wizard.layout')

@section('title', 'Stap 10 — Afronden')

@section('wizard_content')
<form method="post" action="{{ route('admin.companies.wizard.submit-step', [$company, 10]) }}">
    @csrf
    <div class="kt-card min-w-full mb-6">
        <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
            <h3 class="kt-card-title mb-0">Samenvatting</h3>
        </div>
        <div class="kt-card-table kt-scrollable-x-auto pb-3">
            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table">
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Pakket</td>
                    <td class="min-w-48 w-full font-medium text-mono">{{ $packageLabel ?: '—' }}</td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Company-admin</td>
                    <td class="min-w-48 w-full">
                        {{ trim(($company->contact_first_name ?? '').' '.($company->contact_last_name ?? '')) ?: '—' }}
                        &lt;{{ $company->email }}&gt;
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Bedrijf</td>
                    <td class="min-w-48 w-full font-medium text-mono">{{ $company->name }}</td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Vestigingen</td>
                    <td class="min-w-48 w-full">{{ $company->vestigingenDisplayCount() }}</td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Domeinen</td>
                    <td class="min-w-48 w-full">{{ $company->domains->isNotEmpty() ? implode(', ', $company->domains->pluck('host')->all()) : '—' }}</td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Modules</td>
                    <td class="min-w-48 w-full">{{ $company->modules->isNotEmpty() ? implode(', ', $company->modules->pluck('display_name')->all()) : '—' }}</td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Mailserver</td>
                    <td class="min-w-48 w-full">{{ !empty($mailConfigured) ? 'Eigen tenant-mailserver' : 'NEXA Suite-mailserver' }}</td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Google SEO</td>
                    <td class="min-w-48 w-full">{{ !empty($seoConfigured) ? 'Ingesteld' : 'Nog niet ingesteld' }}</td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Mollie</td>
                    <td class="min-w-48 w-full">{{ !empty($mollieConfigured) ? 'Gekoppeld' : 'Nog niet ingesteld' }}</td>
                </tr>
            </table>
        </div>
        <p class="text-sm text-secondary-foreground px-5 lg:px-6 pt-2 pb-5 mb-0">
            Na afronden wordt de company-admin aangemaakt en ontvangt {{ $company->email }} de welkomstmail
            met uitleg voor de eerste login via een eenmalige code (geen wachtwoord in de mail). Daarna kun je dit bedrijf verder beheren vanuit het bedrijfsdetail.
        </p>
    </div>

    <x-wizard.footer-actions :current-step="$currentStep" :company="$company">
        <button type="submit" class="kt-btn kt-btn-primary">
            <i class="ki-filled ki-verify me-2"></i>
            Afronden en naar bedrijf
        </button>
    </x-wizard.footer-actions>
</form>
@endsection
