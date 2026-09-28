<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/**
 * Server-side public self-registration. Never trusts client role/company/admin flags.
 */
class PublicRegistrationService
{
    public const ACCOUNT_CUSTOMER = 'customer';

    public const ACCOUNT_DRIVER = 'driver';

    public const ACCOUNT_COMPANY = 'company';

    public const ROLE_CUSTOMER = 'klant';

    public const ROLE_DRIVER_PENDING = 'driver_pending';

    /**
     * @param  array{
     *     email: string,
     *     password: string,
     *     first_name?: ?string,
     *     middle_name?: ?string,
     *     last_name?: ?string,
     *     account_type?: string,
     * }  $input
     * @return array{user: User, account_type: string, role: string}
     */
    public function register(array $input): array
    {
        $accountType = $this->normalizeAccountType($input['account_type'] ?? self::ACCOUNT_CUSTOMER);

        if ($accountType === self::ACCOUNT_COMPANY) {
            throw ValidationException::withMessages([
                'account_type' => ['Bedrijfsregistratie is nog niet beschikbaar via deze API.'],
            ]);
        }

        $email = $this->normalizeEmail((string) $input['email']);

        return DB::transaction(function () use ($input, $accountType, $email) {
            $user = new User;
            $user->forceFill([
                'email' => $email,
                'password' => Hash::make((string) $input['password']),
                'first_name' => $this->nullableString($input['first_name'] ?? null),
                'middle_name' => $this->nullableString($input['middle_name'] ?? null),
                'last_name' => $this->nullableString($input['last_name'] ?? null),
                'company_id' => null,
                'is_active' => $accountType === self::ACCOUNT_CUSTOMER,
                'email_verified_at' => null,
            ]);
            $user->save();

            $roleName = $accountType === self::ACCOUNT_DRIVER
                ? self::ROLE_DRIVER_PENDING
                : self::ROLE_CUSTOMER;

            $this->assignCanonicalRole($user, $roleName);

            return [
                'user' => $user->fresh(),
                'account_type' => $accountType,
                'role' => $roleName,
            ];
        });
    }

    public function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    public function normalizeAccountType(mixed $raw): string
    {
        $type = strtolower(trim((string) $raw));
        if ($type === '' || $type === 'klant') {
            return self::ACCOUNT_CUSTOMER;
        }

        return match ($type) {
            self::ACCOUNT_CUSTOMER, 'customer' => self::ACCOUNT_CUSTOMER,
            self::ACCOUNT_DRIVER, 'chauffeur' => self::ACCOUNT_DRIVER,
            self::ACCOUNT_COMPANY, 'taxi_company', 'business' => self::ACCOUNT_COMPANY,
            default => throw ValidationException::withMessages([
                'account_type' => ['Ongeldig accounttype. Kies customer of driver.'],
            ]),
        };
    }

    private function assignCanonicalRole(User $user, string $roleName): void
    {
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'api']);

        $user->syncRoles([$role]);
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
