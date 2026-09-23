@php
    use App\Support\TenantConfigCapability;

    $configCapabilities = TenantConfigCapability::definitions();
    $grantsByUserId = $grantsByUserId ?? [];
    $configAccessUsers = $configAccessUsers ?? collect();
    $configAccessFormAction = $configAccessFormAction ?? route('admin.companies.config-access.update', $company);
    $configAccessRedirect = $configAccessRedirect ?? null;
@endphp

@include('admin.settings.partials.collapsible-section-assets')

@once
@push('styles')
<style>
    /* Config-access: volle kaartbreedte, bredere gebruikerskolom */
    #content #config-access .settings-collapsible-body.config-access-body {
        padding-inline: 0 !important;
    }
    #content #config-access .kt-card-content {
        padding-inline: 0 !important;
    }
    #content form .config-access-table.config-access-table,
    #content .config-access-table {
        table-layout: auto !important;
        width: 100% !important;
        min-width: 100% !important;
    }
    #content form .config-access-table .config-access-user-col,
    #content .config-access-table .config-access-user-col {
        width: 16rem;
        min-width: 16rem !important;
        max-width: 20rem;
    }
    #content form .config-access-table th:not(.config-access-user-col),
    #content form .config-access-table td:not(.config-access-user-col) {
        width: auto;
        min-width: 4.5rem;
        padding-inline: 0.75rem;
    }
</style>
@endpush
@endonce

<div id="company-config-access-collapsible-root">
<div class="kt-card w-full min-w-0 mb-6 overflow-hidden settings-collapsible-card settings-collapsible-card--collapsed company-show-section" id="config-access">
    @include('admin.settings.partials.collapsible-header', [
        'titleHtml' => 'Toegang tot configuraties',
        'headerClass' => 'px-5 py-5',
    ])
    <div class="settings-collapsible-body config-access-body">
        <div class="kt-card-content py-5 px-0 min-w-0">
            <p class="text-sm text-secondary-foreground mb-4 break-words px-5">
                Domein, modules, website, mailserver, Google SEO, WhatsApp en Mollie zijn standaard alleen voor super-admin.
                Kies hier een gebruiker van deze tenant en vink de configuraties aan die die persoon zelf mag invullen.
            </p>

            @if($configAccessUsers->isEmpty())
                <p class="text-sm text-muted-foreground mb-0 mx-5 rounded-lg border border-dashed border-input px-4 py-3">
                    Er zijn nog geen gebruikers voor deze tenant. Maak eerst een gebruiker aan, daarna kun je toegang geven.
                </p>
            @else
                <form method="post" action="{{ $configAccessFormAction }}" class="min-w-0">
                    @csrf
                    @method('PUT')
                    @if($configAccessRedirect)
                        <input type="hidden" name="redirect_to" value="{{ $configAccessRedirect }}">
                    @endif
                    <div class="overflow-x-auto min-w-0">
                        <table class="kt-table kt-table-border-dashed admin-fluid-table align-middle text-sm w-full config-access-table">
                            <thead>
                                <tr class="text-secondary-foreground font-medium">
                                    <th class="config-access-user-col text-start">Gebruiker</th>
                                    @foreach($configCapabilities as $capability)
                                        <th class="text-center whitespace-nowrap">{{ $capability['label'] }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($configAccessUsers as $accessUser)
                                    @php
                                        $userGrants = $grantsByUserId[$accessUser->id] ?? [];
                                        $displayName = trim(($accessUser->first_name ?? '').' '.($accessUser->last_name ?? ''));
                                        if ($displayName === '') {
                                            $displayName = $accessUser->email;
                                        }
                                    @endphp
                                    <tr>
                                        <td class="align-top config-access-user-col">
                                            <div class="font-medium text-foreground">{{ $displayName }}</div>
                                            <div class="text-xs text-muted-foreground break-all">{{ $accessUser->email }}</div>
                                        </td>
                                        @foreach($configCapabilities as $capability)
                                            <td class="text-center align-middle">
                                                <label class="kt-label inline-flex items-center justify-center mb-0 cursor-pointer">
                                                    <input
                                                        type="checkbox"
                                                        class="kt-checkbox"
                                                        name="grants[{{ $accessUser->id }}][]"
                                                        value="{{ $capability['key'] }}"
                                                        {{ in_array($capability['key'], $userGrants, true) ? 'checked' : '' }}
                                                        title="{{ $capability['hint'] }}"
                                                    >
                                                </label>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 px-5">
                        <button type="submit" class="kt-btn kt-btn-primary">
                            Toegang opslaan
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
</div>
