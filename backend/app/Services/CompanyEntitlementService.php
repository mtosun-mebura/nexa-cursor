<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Models\TransportCustomer;
use App\Modules\NexaTaxi\Services\TaxiDriverEligibilityService;
use App\Services\PlatformBilling\TenantSubscriptionService;
use App\Support\TenantPackageAddon;
use App\Support\TenantPackageCapability;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Pakketrechten van een tenant. Geen gekoppeld pakket = geen extra beperking
 * (bestaande bedrijven blijven werken tot een super-admin een pakket kiest).
 */
class CompanyEntitlementService
{
    public function __construct(
        protected NexaPricingService $pricing,
        protected TaxiDriverEligibilityService $drivers
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function packageFor(?Company $company): ?array
    {
        if (! $company) {
            return null;
        }
        $key = trim((string) ($company->package_key ?? ''));
        if ($key === '') {
            return null;
        }

        return $this->pricing->packageByKey($key);
    }

    /**
     * @return array<string, int|bool>
     */
    public function entitlementsFor(?Company $company): array
    {
        $package = $this->packageFor($company);
        if (! $package) {
            return [];
        }
        $key = (string) ($package['key'] ?? '');
        $raw = is_array($package['entitlements'] ?? null) ? $package['entitlements'] : [];

        return TenantPackageCapability::normalize($raw, $key);
    }

    public function allows(?Company $company, string $capability): bool
    {
        if ($capability === TenantPackageCapability::GPS_TRACKING) {
            if ($this->entitlementsFor($company) === []) {
                return true;
            }
            if ($this->companyIsInTrial($company)) {
                return true;
            }

            return $this->hasAddon($company, TenantPackageAddon::GPS_TRACKING);
        }

        $entitlements = $this->entitlementsFor($company);
        if ($entitlements === []) {
            return true;
        }
        if (! array_key_exists($capability, $entitlements)) {
            return true;
        }

        return (bool) $entitlements[$capability];
    }

    public function allowsCompanyId(?int $companyId, string $capability): bool
    {
        if (! $companyId) {
            return true;
        }

        return $this->allows(Company::query()->find($companyId), $capability);
    }

    /**
     * null = onbeperkt (geen pakket, of max_drivers = 0).
     */
    public function maxDrivers(?Company $company): ?int
    {
        $entitlements = $this->entitlementsFor($company);
        if ($entitlements === []) {
            return null;
        }
        $max = (int) ($entitlements[TenantPackageCapability::MAX_DRIVERS] ?? 0);

        return $max > 0 ? $max : null;
    }

    public function chauffeurCount(Company $company): int
    {
        return $this->drivers->buildChauffeurQuery((int) $company->id)->count();
    }

    /**
     * @param  list<string>  $roleNames
     */
    public function assertCanAssignChauffeurRoles(Company $company, array $roleNames, ?User $existingUser = null): void
    {
        if (! $this->drivers->rolesIncludeChauffeur($roleNames)) {
            return;
        }

        $max = $this->maxDrivers($company);
        if ($max === null) {
            return;
        }

        if ($existingUser && $existingUser->company_id === $company->id && $this->drivers->isChauffeurForCompany($existingUser, (int) $company->id)) {
            return;
        }

        $count = $this->chauffeurCount($company);
        if ($count < $max) {
            return;
        }

        $packageName = (string) ($this->packageFor($company)['name'] ?? 'dit pakket');

        throw ValidationException::withMessages([
            'roles' => 'Het '.$packageName.'-pakket staat maximaal '.$max.' chauffeur'.($max === 1 ? '' : 's').' toe. Dit bedrijf heeft er al '.$count.'. Upgrade het pakket om meer chauffeurs aan te maken.',
        ]);
    }

    /**
     * @return array<string, int>
     */
    public function addonSelectionsFor(?Company $company): array
    {
        if (! $company) {
            return TenantPackageAddon::normalizeSelections([]);
        }
        $raw = is_array($company->package_addons ?? null) ? $company->package_addons : [];

        return TenantPackageAddon::effectiveSelections($raw);
    }

    public function addonQuantity(?Company $company, string $key): int
    {
        return (int) ($this->addonSelectionsFor($company)[$key] ?? 0);
    }

    public function hasAddon(?Company $company, string $key): bool
    {
        if ($this->companyIsInTrial($company) && in_array($key, TenantPackageAddon::keys(), true)) {
            return true;
        }

        return $this->addonQuantity($company, $key) > 0;
    }

    /**
     * null = onbeperkt (geen pakket of Vloot). 0 = geen contractklanten.
     */
    public function maxContractClients(?Company $company): ?int
    {
        $entitlements = $this->entitlementsFor($company);
        if ($entitlements === []) {
            return null;
        }
        if (! $this->allows($company, TenantPackageCapability::CONTRACT_TRANSPORT)) {
            return 0;
        }
        if ($this->hasAddon($company, TenantPackageAddon::FLEET)) {
            return null;
        }
        $base = (int) ($entitlements[TenantPackageCapability::MAX_CONTRACT_CLIENTS] ?? 0);
        $extra = $this->addonQuantity($company, TenantPackageAddon::EXTRA_CLIENTS) * TenantPackageAddon::EXTRA_CLIENTS_PER_PACK;
        $total = max(0, $base) + $extra;

        return $total;
    }

    public function contractClientCount(Company $company): int
    {
        try {
            $conn = app(ModuleDatabaseService::class)->getModuleConnectionName('taxi');
            if (! Schema::connection($conn)->hasTable('transport_customers')) {
                return 0;
            }

            return TransportCustomer::on($conn)
                ->where('company_id', (int) $company->id)
                ->where('active', true)
                ->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    public function assertCanUseContractTransport(?Company $company): void
    {
        if ($this->allows($company, TenantPackageCapability::CONTRACT_TRANSPORT)) {
            return;
        }

        throw ValidationException::withMessages([
            'package' => $this->deniedMessage(TenantPackageCapability::CONTRACT_TRANSPORT, $company),
        ]);
    }

    public function assertCanCreateContractCustomer(Company $company): void
    {
        $this->assertCanUseContractTransport($company);

        $max = $this->maxContractClients($company);
        if ($max === null) {
            return;
        }
        $count = $this->contractClientCount($company);
        if ($count < $max) {
            return;
        }

        $packageName = (string) ($this->packageFor($company)['name'] ?? 'dit pakket');
        $hint = $max === 0
            ? 'Contractvervoer zit niet in dit pakket.'
            : 'Het '.$packageName.'-pakket staat maximaal '.$max.' contractklant'.($max === 1 ? '' : 'en').' toe. Dit bedrijf heeft er al '.$count.'. Voeg de module Extra contractklanten (+10 voor € 49) toe, of kies Vloot voor een onbeperkt aantal.';

        throw ValidationException::withMessages([
            'name' => $hint,
        ]);
    }

    /**
     * @param  list<string>  $roleNames
     */
    public function assertCanAssignCompanyAdminRoles(Company $company, array $roleNames, ?User $existingUser = null): void
    {
        $normalized = array_map(static fn ($name) => strtolower(trim((string) $name)), $roleNames);
        if (! in_array('company-admin', $normalized, true)) {
            return;
        }
        if ($this->allows($company, TenantPackageCapability::MULTIPLE_ADMINS)) {
            return;
        }
        if ($existingUser && (int) $existingUser->company_id === (int) $company->id && $this->userHasCompanyAdminRole($existingUser, (int) $company->id)) {
            return;
        }
        if ($this->companyAdminCount($company) < 1) {
            return;
        }

        throw ValidationException::withMessages([
            'roles' => $this->deniedMessage(TenantPackageCapability::MULTIPLE_ADMINS, $company),
        ]);
    }

    public function companyAdminCount(Company $company): int
    {
        return $this->usersWithRoleCount((int) $company->id, ['company-admin']);
    }

    public function assertAllows(?Company $company, string $capability, string $field = 'package'): void
    {
        if ($this->allows($company, $capability)) {
            return;
        }

        throw ValidationException::withMessages([
            $field => $this->deniedMessage($capability, $company),
        ]);
    }

    /**
     * GET in de admin: pakketblokkade als zichtbare banner i.p.v. stille redirect naar het dashboard.
     */
    public static function adminGetDeniedRedirect(ValidationException $e, Request $request): ?RedirectResponse
    {
        if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
            return null;
        }
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return null;
        }
        if (! $request->is('admin') && ! $request->is('admin/*')) {
            return null;
        }

        $message = $e->errors()['package'][0] ?? null;
        if (! is_string($message) || trim($message) === '') {
            return null;
        }

        $dashboard = route('admin.dashboard');
        $previous = url()->previous($dashboard);
        $current = $request->fullUrl();
        $target = ($previous === $current || $previous === $request->url())
            ? $dashboard
            : $previous;

        return redirect()->to($target)->with('warning', $message);
    }

    public function jsonDenied(?Company $company, string $capability, int $status = 403): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'message' => $this->deniedMessage($capability, $company),
        ], $status);
    }

    public function contractClientLimitLabel(?Company $company): string
    {
        $max = $this->maxContractClients($company);
        if ($max === null) {
            return 'Onbeperkt';
        }
        if ($max === 0) {
            return 'Geen (geen contractvervoer)';
        }

        return (string) $max;
    }

    public function deniedMessage(string $capability, ?Company $company = null): string
    {
        $packageName = (string) ($this->packageFor($company)['name'] ?? 'dit pakket');

        return match ($capability) {
            TenantPackageCapability::MOLLIE_PAYMENTS => 'Betalen via Mollie zit niet in het '.$packageName.'-pakket. Upgrade naar Pro of Business om online betalingen te gebruiken.',
            TenantPackageCapability::INVOICE_PDF => 'Een factuur als pdf versturen zit niet in het '.$packageName.'-pakket.',
            TenantPackageCapability::WEBSITE_BOOKING => 'Online boeken via de website zit niet in het '.$packageName.'-pakket.',
            TenantPackageCapability::DISPATCH => 'Dispatch (toewijzen en opnieuw uitzetten) zit niet in het '.$packageName.'-pakket. Upgrade naar Pro of Business.',
            TenantPackageCapability::DRIVER_APP => 'De chauffeur-app zit niet in het '.$packageName.'-pakket. Upgrade naar Pro of Business.',
            TenantPackageCapability::CONTRACT_TRANSPORT => 'Contractvervoer zit niet in het '.$packageName.'-pakket. Upgrade naar Business.',
            TenantPackageCapability::CONTRACT_PORTAL => 'Het contractportaal zit niet in het '.$packageName.'-pakket. Upgrade naar Business.',
            TenantPackageCapability::MULTIPLE_ADMINS => 'Het '.$packageName.'-pakket staat maximaal één beheerder toe. Upgrade naar Pro of Business voor meerdere beheerders.',
            TenantPackageCapability::MONTHLY_INVOICE_SEPA => 'Maandfactuur en SEPA-mandaat zitten niet in het '.$packageName.'-pakket. Upgrade naar Business.',
            TenantPackageCapability::GPS_TRACKING => 'GPS-trackers zitten niet in dit abonnement. Activeer de aanvullende module GPS-trackers (+ € 19 per maand).',
            default => 'Deze functie zit niet in het '.$packageName.'-pakket.',
        };
    }

    /**
     * @param  list<string>  $roleNames
     */
    private function usersWithRoleCount(int $companyId, array $roleNames): int
    {
        if ($companyId <= 0 || $roleNames === []) {
            return 0;
        }
        $pivot = DB::getTablePrefix().config('permission.table_names.model_has_roles');
        $rolesTable = DB::getTablePrefix().config('permission.table_names.roles');
        $teamKey = config('permission.column_names.team_foreign_key') ?: 'company_id';
        $morphTypes = array_values(array_unique(array_filter([
            User::class,
            (new User)->getMorphClass(),
        ])));
        $normalized = array_map(static fn ($name) => strtolower(trim((string) $name)), $roleNames);

        return (int) User::query()
            ->where('company_id', $companyId)
            ->whereExists(function ($sub) use ($companyId, $pivot, $rolesTable, $teamKey, $morphTypes, $normalized) {
                $sub->select(DB::raw('1'))
                    ->from($pivot)
                    ->join($rolesTable, $rolesTable.'.id', '=', $pivot.'.role_id')
                    ->whereColumn($pivot.'.model_id', 'users.id')
                    ->whereIn($pivot.'.model_type', $morphTypes)
                    ->where(function ($q) use ($pivot, $teamKey, $companyId) {
                        $q->where($pivot.'.'.$teamKey, $companyId)
                            ->orWhere(function ($q2) use ($pivot, $teamKey, $companyId) {
                                $q2->whereNull($pivot.'.'.$teamKey)
                                    ->where('users.company_id', $companyId);
                            });
                    })
                    ->whereIn($rolesTable.'.guard_name', ['web', 'api'])
                    ->where(function ($q) use ($rolesTable, $normalized) {
                        foreach ($normalized as $i => $slug) {
                            $method = $i === 0 ? 'whereRaw' : 'orWhereRaw';
                            $q->{$method}('LOWER(TRIM('.$rolesTable.'.name)) = ?', [$slug]);
                        }
                    });
            })
            ->count();
    }

    private function userHasCompanyAdminRole(User $user, int $companyId): bool
    {
        return $this->usersWithRoleCount($companyId, ['company-admin']) > 0
            && User::query()
                ->whereKey($user->id)
                ->where('company_id', $companyId)
                ->whereExists(function ($sub) use ($companyId) {
                    $pivot = DB::getTablePrefix().config('permission.table_names.model_has_roles');
                    $rolesTable = DB::getTablePrefix().config('permission.table_names.roles');
                    $teamKey = config('permission.column_names.team_foreign_key') ?: 'company_id';
                    $sub->select(DB::raw('1'))
                        ->from($pivot)
                        ->join($rolesTable, $rolesTable.'.id', '=', $pivot.'.role_id')
                        ->whereColumn($pivot.'.model_id', 'users.id')
                        ->where(function ($q) use ($pivot, $teamKey, $companyId) {
                            $q->where($pivot.'.'.$teamKey, $companyId)
                                ->orWhereNull($pivot.'.'.$teamKey);
                        })
                        ->whereRaw('LOWER(TRIM('.$rolesTable.'.name)) = ?', ['company-admin']);
                })
                ->exists();
    }

    private function companyIsInTrial(?Company $company): bool
    {
        if (! $company || ! $company->id) {
            return false;
        }

        try {
            $profile = $company->billingProfile;
            if (! $profile) {
                return false;
            }

            return app(TenantSubscriptionService::class)->isInTrial($profile);
        } catch (\Throwable) {
            return false;
        }
    }
}
