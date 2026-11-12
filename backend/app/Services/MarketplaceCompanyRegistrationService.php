<?php

namespace App\Services;

use App\Models\Company;
use App\Models\GeneralSetting;
use App\Models\Module;
use App\Models\User;
use App\Services\Payout\PayoutIdentityService;
use App\Services\PlatformBilling\TenantSubscriptionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Spatie\Permission\Models\Role;

/**
 * Zelfregistratie van taxibedrijven op het fee-only Marketplace-pakket.
 */
class MarketplaceCompanyRegistrationService
{
    public const PACKAGE_KEY = 'marketplace';

    public const ROLE = 'marketplace';

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
     *   phone: string,
     *   city: string,
     *   iban: string,
     *   kvk_number?: string|null,
     *   contact_first_name?: string|null,
     *   contact_last_name?: string|null
     * }  $data
     * @return array{company: Company, user: User, created_user: bool, code_result: array{ok: bool, status: int, message: string, retry_after?: int}}
     */
    public function register(array $data, string $ip, bool $sendLoginCode = true): array
    {
        $package = $this->pricing->packageByKey(self::PACKAGE_KEY);
        if ($package === null) {
            throw new RuntimeException('Marketplace-pakket ontbreekt in de prijsconfiguratie.');
        }

        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $companyName = trim((string) ($data['company_name'] ?? ''));
        $city = trim((string) ($data['city'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));
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
        if ($phone === '') {
            throw ValidationException::withMessages([
                'phone' => ['Vul een telefoonnummer in.'],
            ]);
        }
        if ($city === '' || mb_strlen($city) < 2) {
            throw ValidationException::withMessages([
                'city' => ['Vul de plaats in waar het bedrijf zich bevindt.'],
            ]);
        }

        $iban = (string) ($data['iban'] ?? '');
        if (trim($iban) === '') {
            throw ValidationException::withMessages([
                'iban' => ['Vul een IBAN in.'],
            ]);
        }
        if (! is_valid_iban($iban)) {
            throw ValidationException::withMessages([
                'iban' => ['Voer een geldig IBAN in.'],
            ]);
        }

        $kvkNumber = $this->normalizeKvkNumber($data['kvk_number'] ?? null);
        if (array_key_exists('kvk_number', $data) && trim((string) ($data['kvk_number'] ?? '')) !== '' && $kvkNumber === null) {
            throw ValidationException::withMessages([
                'kvk_number' => ['KVK-nummer moet 8 cijfers bevatten (bijv. 12345678).'],
            ]);
        }

        $this->assertNotDuplicateRegistration($email, $companyName, $city, $kvkNumber);

        return DB::transaction(function () use ($data, $email, $companyName, $city, $phone, $iban, $kvkNumber, $ip, $sendLoginCode) {
            $attributes = [
                'name' => $companyName,
                'email' => $email,
                'phone' => $phone,
                'city' => $city,
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
            ];
            if ($kvkNumber !== null) {
                $attributes['kvk_number'] = $kvkNumber;
            }

            $company = Company::query()->create($attributes);

            $this->attachTaxiModule($company);
            $this->subscriptions->syncBillingPackageFromCompany($company, true);
            $this->subscriptions->syncPackageAddonsFromCompany($company, []);

            // Afzendernaam = bedrijfsnaam; From-adres blijft NEXA Suite (geen eigen SMTP).
            GeneralSetting::set('MAIL_FROM_NAME', $companyName, (int) $company->id);

            $provisioned = $this->onboarding->provisionCompanyAdmin($company, false);
            $user = $provisioned['user'];

            // Eén rol: marketplace = company-admin + chauffeur (rechten verschillen van company-admin).
            Role::findOrCreate(self::ROLE, 'web');
            Role::findOrCreate(self::ROLE, 'api');
            app(UserRoleAssignmentService::class)->syncWebRoles($user, [self::ROLE]);

            $payouts = app(PayoutIdentityService::class);
            $payouts->setCompanyRegistrationIban($company, $user, $iban);
            $payouts->setDriverIban($user, $iban);

            $codeResult = $sendLoginCode
                ? $this->firstLogin->requestCode($email, $ip, true)
                : ['ok' => true, 'status' => 200, 'message' => ''];

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

    /**
     * Hard-block bij duidelijk duplicaat: zelfde e-mail, zelfde KvK, ofzelfde genormaliseerde naam+plaats.
     */
    private function assertNotDuplicateRegistration(
        string $email,
        string $companyName,
        string $city,
        ?string $kvkNumber
    ): void {
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

        if ($kvkNumber !== null) {
            $existingKvk = Company::query()->where('kvk_number', $kvkNumber)->exists();
            if ($existingKvk) {
                throw ValidationException::withMessages([
                    'kvk_number' => ['Er bestaat al een bedrijf met dit KVK-nummer. Log in of neem contact op met NEXA.'],
                ]);
            }
        }

        $nameKey = $this->normalizeCompanyName($companyName);
        $cityKey = $this->normalizePlace($city);
        if ($nameKey === '' || $cityKey === '') {
            return;
        }

        $cityCompact = str_replace(' ', '', $cityKey);
        $candidates = Company::query()
            ->select(['id', 'name', 'city'])
            ->whereNotNull('name')
            ->whereNotNull('city')
            ->whereRaw('LOWER(REPLACE(TRIM(city), ?, ?)) = ?', [' ', '', $cityCompact])
            ->get();

        foreach ($candidates as $candidate) {
            if ($this->normalizeCompanyName((string) $candidate->name) !== $nameKey) {
                continue;
            }
            if ($this->normalizePlace((string) $candidate->city) !== $cityKey) {
                continue;
            }

            throw ValidationException::withMessages([
                'company_name' => ['Er is al een bedrijf geregistreerd met deze naam in deze plaats. Log in of neem contact op met NEXA.'],
                'city' => ['Er is al een bedrijf geregistreerd met deze naam in deze plaats.'],
            ]);
        }
    }

    /** Lowercase, witruimte inklappen, juridische suffixen (BV/NV/VOF/Ltd) strippen. */
    private function normalizeCompanyName(string $name): string
    {
        $name = mb_strtolower(trim($name));
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;
        $name = preg_replace(
            '/\b(b\.?\s*v\.?|n\.?\s*v\.?|v\.?\s*o\.?\s*f\.?|ltd\.?|inc\.?|eenmanszaak)\b/u',
            '',
            $name
        ) ?? $name;
        $name = preg_replace('/[^\p{L}\p{N}\s]/u', '', $name) ?? $name;

        return trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
    }

    /** Lowercase + witruimte inklappen. */
    private function normalizePlace(string $place): string
    {
        $place = mb_strtolower(trim($place));

        return trim(preg_replace('/\s+/u', ' ', $place) ?? $place);
    }

    private function normalizeKvkNumber(mixed $kvk): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $kvk) ?? '';
        if (strlen($digits) !== 8) {
            return null;
        }

        return $digits;
    }
}
