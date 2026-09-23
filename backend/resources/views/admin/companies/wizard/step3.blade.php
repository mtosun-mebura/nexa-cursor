@extends('admin.companies.wizard.layout')

@section('title', 'Stap 3 — Domein')

@section('wizard_content')
    <x-error-card :errors="$errors" />

    <div class="kt-card min-w-full mb-6">
        <div class="kt-card-header px-5 py-5">
            <h3 class="kt-card-title mb-0">Tenant-domein</h3>
        </div>
        <div class="kt-card-content p-5 space-y-4">
            <p class="text-sm text-secondary-foreground mb-0 pb-2">
                Vul de hostnaam in waarmee klanten deze tenant bereiken (bijv. <code class="text-xs">klant.jouwdomein.nl</code>). Zorg dat DNS naar deze server wijst. SSL volgt via je hosting.
            </p>

            <p id="company-domains-empty" class="text-sm text-muted-foreground mb-0 rounded-lg border border-dashed border-input px-4 py-3 {{ $company->domains->isNotEmpty() ? 'hidden' : '' }}">
                Nog geen domeinen gekoppeld.
            </p>

            <div id="company-domains-list-wrap" class="flex flex-col gap-2 {{ $company->domains->isEmpty() ? 'hidden' : '' }}">
                <div id="company-domains-list" class="flex flex-col gap-2">
                    @include('admin.companies.partials.domain-table-rows', ['company' => $company])
                </div>
            </div>

            @can('edit-companies')
            <form
                id="company-domain-add-form"
                action="{{ route('admin.companies.domains.store', $company) }}"
                method="post"
                class="flex flex-col gap-4 {{ $company->domains->isNotEmpty() ? 'pt-5 border-t border-border' : 'rounded-xl border border-input bg-muted/15 p-4 sm:p-5' }}"
            >
                @csrf
                <div class="flex flex-col gap-1.5">
                    <label for="domain_host" class="text-sm font-medium text-foreground">Nieuwe hostnaam</label>
                    <input
                        type="text"
                        name="host"
                        id="domain_host"
                        value="{{ old('host') }}"
                        class="kt-input w-full @error('host') border-destructive @enderror"
                        placeholder="bijv. taxi.voorbeeld.nl"
                        autocomplete="off"
                        @error('host') data-server-error="1" @enderror
                    >
                    <div id="domain-host-error-ajax" class="text-xs text-destructive hidden" role="alert"></div>
                    @error('host')
                        <div class="text-xs text-destructive" data-validation-error="1" data-validation-error-for="host">{{ $message }}</div>
                    @enderror
                </div>
                <div class="flex flex-col gap-2">
                    <span class="text-sm font-medium text-foreground">Primair domein</span>
                    <input type="hidden" name="is_primary" value="0">
                    <label class="kt-label flex items-center gap-2.5 mb-0 cursor-pointer w-fit">
                        <input type="checkbox" name="is_primary" value="1" class="kt-switch kt-switch-sm shrink-0" {{ old('is_primary') ? 'checked' : '' }}>
                        <span class="text-sm text-secondary-foreground">Instellen als primair domein</span>
                    </label>
                </div>
                <div>
                    <button type="submit" class="kt-btn kt-btn-primary w-full sm:w-auto">
                        <i class="ki-filled ki-plus me-2"></i>
                        Domein toevoegen
                    </button>
                </div>
            </form>
            @endcan
        </div>
    </div>

    <form method="post" action="{{ route('admin.companies.wizard.submit-step', [$company, 3]) }}" id="wizard-step3-continue-form">
        @csrf
        <x-wizard.footer-actions :current-step="$currentStep" :company="$company">
            <button type="submit" name="skip_domain" value="1" class="kt-btn kt-btn-outline" formnovalidate>
                Geen domein nu — verder
            </button>
            <button type="submit" class="kt-btn kt-btn-primary">
                Volgende
                <i class="ki-filled ki-arrow-right ms-2"></i>
            </button>
        </x-wizard.footer-actions>
    </form>

    @can('edit-companies')
        @include('admin.companies.partials.domain-list-scripts')
    @endcan
@endsection
