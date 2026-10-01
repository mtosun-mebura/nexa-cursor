<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Module;
use App\Models\User;
use App\Services\PlatformBilling\TenantSubscriptionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Zelfregistratie van taxibedrijven op het fee-only Marketplace-pakket.
 */
class MarketplaceCompanyRegistrationService
{
    public const PACKAGE_KEY = 'marketplace';

    public function __construct(
        protected TenantOnboardingService $onboarding,
        protected TenantSubscriptionService $subscriptions,
        protected AdminFirstLoginService $firstLogin,
        protected NexaPricingService $pricing,
    ) {}

    /**
     * @param  array{
     *   company_name: string,
     *   email: string,
     *   phone?: string|null,
     *   city?: string|null,
     *   contact_first_name?: string|null,
     *   contact_last_name?: string|null
     * }  $data
     * @return array{company: Company, user: User, created_user: bool, code_result: array{ok: bool, status: int, message: string, retry_after?: int}}
     */
    public function register(array $data, string $ip): array
    {
        $package = $this->pricing->packageByKey(self::PACKAGE_KEY);
        if ($package === null) {
            throw new RuntimeException('Marketplace-pakket ontbreekt in de prijsconfiguratie.');
        }

        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $companyName = trim((string) ($data['company_name'] ?? ''));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'email' => ['Vul een geldig e-mailadres in.'],
            ]);
        }
        if ($companyName === '') {
            throw ValidationException::withMessages([
                'company_name' => ['Vul de bedrijfsnaam in.'],
            ]);
        }

        $existingUser = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        if ($existingUser) {
            throw ValidationException::withMessages([
                'email' => ['Dit e-mailadres is al in gebruik. Log in met een code of wachtwoord.'],
            ]);
        }

        $existingCompany = Company::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        if ($existingCompany) {
            throw ValidationException::withMessages([
                'email' => ['Er bestaat al een bedrijf met dit e-mailadres. Log in of neem contact op met NEXA.'],
            ]);
        }

        return DB::transaction(function () use ($data, $email, $companyName, $ip) {
            $company = Company::query()->create([
                'name' => $companyName,
                'email' => $email,
                'phone' => isset($data['phone']) ? trim((string) $data['phone']) : null,
                'city' => isset($data['city']) ? trim((string) $data['city']) : null,
                'contact_first_name' => isset($data['contact_first_name'])
                    ? trim((string) $data['contact_first_name'])
                    : null,
                'contact_last_name' => isset($data['contact_last_name'])
                    ? trim((string) $data['contact_last_name'])
                    : null,
                'contact_email' => $email,
                'is_active' => true,
                'accepts_nexa_suite_bookings' => true,
                'package_key' => self::PACKAGE_KEY,
                'industry' => 'Taxi',
            ]);

            $this->attachTaxiModule($company);
            $this->subscriptions->syncBillingPackageFromCompany($company, true);
            $this->subscriptions->syncPackageAddonsFromCompany($company, []);

            $provisioned = $this->onboarding->provisionCompanyAdmin($company, false);
            $user = $provisioned['user'];

            $codeResult = $this->firstLogin->requestCode($email, $ip);

            return [
                'company' => $company->fresh(),
                'user' => $user->fresh(),
                'created_user' => (bool) ($provisioned['created'] ?? false),
                'code_result' => $codeResult,
            ];
        });
    }

    private function attachTaxiModule(Company $company): void
    {
        if (! Schema::hasTable('modules') || ! Schema::hasTable('company_module')) {
            return;
        }

        $module = Module::query()->whereRaw('LOWER(name) = ?', ['taxi'])->first();
        if ($module === null) {
            return;
        }
        if (! $company->modules()->where('modules.id', $module->id)->exists()) {
            $company->modules()->attach($module->id);
        }
    }
}
