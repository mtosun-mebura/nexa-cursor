@php
    $authUser = auth()->user();
    $roleNames = $authUser ? $authUser->assignedRoleNames() : [];
    $eligibility = app(\App\Modules\NexaTaxi\Services\TaxiDriverEligibilityService::class);
    $isSuperAdmin = $authUser && $authUser->isSuperAdmin();
    $showChauffeurAppLaunch = $isSuperAdmin || ($authUser && $eligibility->rolesIncludeChauffeur($roleNames));
    $showContractAppLaunch = $isSuperAdmin || ($authUser && $eligibility->rolesIncludeContract($roleNames));
    $chauffeurAppUrl = '/taxi/chauffeur';
    $contractAppUrl = '/taxi/contract';
@endphp

@if($showChauffeurAppLaunch || $showContractAppLaunch)
<style>
    .dashboard-app-launch-btn--chauffeur {
        background-color: #f97316;
        border-color: #f97316;
        color: #fff !important;
    }
    .dashboard-app-launch-btn--chauffeur:hover,
    .dashboard-app-launch-btn--chauffeur:focus-visible {
        background-color: #ea580c;
        border-color: #ea580c;
        color: #fff !important;
    }
    .dashboard-app-launch-btn--contract {
        background-color: #2563eb;
        border-color: #2563eb;
        color: #fff !important;
    }
    .dashboard-app-launch-btn--contract:hover,
    .dashboard-app-launch-btn--contract:focus-visible {
        background-color: #1d4ed8;
        border-color: #1d4ed8;
        color: #fff !important;
    }
</style>
<div class="kt-card dashboard-app-launch-bar">
    <div class="kt-card-content p-5">
        <div class="flex flex-wrap items-center justify-center gap-3">
            @if($showChauffeurAppLaunch)
                <a href="{{ $chauffeurAppUrl }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="kt-btn dashboard-app-launch-btn dashboard-app-launch-btn--chauffeur"
                   aria-label="Open chauffeur-app in een nieuw tabblad">
                    <i class="ki-filled ki-car me-2" aria-hidden="true"></i>
                    Chauffeur-app
                </a>
            @endif
            @if($showContractAppLaunch)
                <a href="{{ $contractAppUrl }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="kt-btn dashboard-app-launch-btn dashboard-app-launch-btn--contract"
                   aria-label="Open contract-app in een nieuw tabblad">
                    <i class="ki-filled ki-briefcase me-2" aria-hidden="true"></i>
                    Contract-app
                </a>
            @endif
        </div>
    </div>
</div>
@endif
