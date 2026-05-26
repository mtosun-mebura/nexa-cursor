@extends('admin.companies.wizard.layout')

@section('title', 'Stap 3 — Domein')

@section('wizard_content')
<form method="post" action="{{ route('admin.companies.wizard.submit-step', [$company, 3]) }}">
    @csrf
    <x-error-card :errors="$errors" />

    <div class="kt-card min-w-full mb-6">
        <div class="kt-card-header">
            <h3 class="kt-card-title">Tenant-domein</h3>
        </div>
        <div class="px-6 pt-2 pb-3 space-y-4">
            <p class="text-sm text-secondary-foreground mb-0">
                Vul de hostnaam in waarmee klanten deze tenant bereiken (bijv. <code class="text-xs">klant.jouwdomein.nl</code>). Zorg dat DNS naar deze server wijst. SSL volgt via je hosting.
            </p>
            @if($company->domains->isNotEmpty())
                <div class="kt-alert kt-alert-info">
                    <p class="text-sm font-medium mb-2">Bestaande domeinen:</p>
                    <ul class="list-disc list-inside text-sm">
                        @foreach($company->domains as $d)
                            <li class="font-mono">{{ $d->host }} @if($d->is_primary)(primair)@endif</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
        <div class="kt-card-table kt-scrollable-x-auto pb-3">
            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table">
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Nieuwe hostnaam (optioneel)</td>
                    <td class="min-w-48 w-full">
                        <input type="text" id="domain_host" name="host" class="kt-input @error('host') border-destructive @enderror" value="{{ old('host') }}" placeholder="bijv. taxi.voorbeeld.nl" autocomplete="off" @error('host') data-server-error="1" @enderror>
                        @error('host')<div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="host">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Primair domein</td>
                    <td class="min-w-48 w-full">
                        <input type="hidden" name="is_primary" value="0">
                        <label class="kt-label flex items-center gap-2 mb-0">
                            <input type="checkbox" name="is_primary" value="1" class="kt-switch kt-switch-sm" {{ old('is_primary') ? 'checked' : '' }}>
                            Primair domein voor deze tenant
                        </label>
                    </td>
                </tr>
            </table>
        </div>
    </div>

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
@endsection
