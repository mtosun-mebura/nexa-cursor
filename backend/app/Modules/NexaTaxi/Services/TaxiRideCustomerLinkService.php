<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\User;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Traits\UsesModuleDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TaxiRideCustomerLinkService
{
    use UsesModuleDatabase;

    /**
     * Koppel gast-boekingen (zelfde e-mail, geen customer_user_id) aan het ingelogde account.
     */
    public function linkOrphanRidesForUser(User $user, ?int $companyId = null): int
    {
        $email = mb_strtolower(trim((string) ($user->email ?? '')));
        if ($email === '') {
            return 0;
        }

        $conn = $this->moduleConnection();
        if (! Schema::connection($conn)->hasTable('ride_requests')
            || ! Schema::connection($conn)->hasColumn('ride_requests', 'customer_user_id')) {
            return 0;
        }

        $companyId = $companyId ?? $this->resolveCompanyIdForUser($user);

        $query = RideRequest::on($conn)
            ->whereNull('customer_user_id')
            ->whereRaw('LOWER(TRIM(customer_email)) = ?', [$email]);

        if ($companyId > 0) {
            $query->where('company_id', $companyId);
        }

        return (int) $query->update(['customer_user_id' => (int) $user->id]);
    }

    /**
     * Maak een klantaccount aan op basis van gast-boekingen (zelfde e-mail, geen account).
     * Retourneert null wanneer er geen geschikte boeking is of het e-mailadres al aan een ander account hangt.
     */
    public function provisionCustomerFromGuestBookings(string $email, ?int $companyId = null): ?User
    {
        $normalizedEmail = mb_strtolower(trim($email));
        if ($normalizedEmail === '') {
            return null;
        }

        $ride = $this->findLatestOrphanRideForEmail($normalizedEmail, $companyId);
        if (! $ride) {
            return null;
        }

        $rideCompanyId = (int) ($ride->company_id ?? 0);
        $effectiveCompanyId = ($companyId !== null && $companyId > 0)
            ? $companyId
            : ($rideCompanyId > 0 ? $rideCompanyId : null);

        $existing = $this->findCustomerByEmail($normalizedEmail, $effectiveCompanyId);
        if ($existing) {
            if (! $existing->password_must_be_set && ! $this->userHasKlantRole($existing, $effectiveCompanyId)) {
                return null;
            }

            $this->linkOrphanRidesForUser($existing, $effectiveCompanyId);

            return $existing;
        }

        [$firstName, $lastName] = $this->splitCustomerName((string) ($ride->customer_name ?? ''));

        $userData = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $normalizedEmail,
            'company_id' => $effectiveCompanyId,
            'password' => Str::password(64),
            'password_must_be_set' => true,
            'email_verified_at' => null,
            'is_active' => true,
        ];

        $conn = $this->moduleConnection();
        if (Schema::connection($conn)->hasColumn('ride_requests', 'customer_phone')) {
            $phone = trim((string) ($ride->customer_phone ?? ''));
            if ($phone !== '') {
                $userData['phone'] = $phone;
            }
        }

        $user = User::query()->create($userData);
        $this->assignKlantRole($user, $effectiveCompanyId);
        $this->linkOrphanRidesForUser($user, $effectiveCompanyId);

        return $user;
    }

    protected function findLatestOrphanRideForEmail(string $normalizedEmail, ?int $companyId): ?RideRequest
    {
        $conn = $this->moduleConnection();
        if (! Schema::connection($conn)->hasTable('ride_requests')
            || ! Schema::connection($conn)->hasColumn('ride_requests', 'customer_user_id')) {
            return null;
        }

        $query = RideRequest::on($conn)
            ->whereNull('customer_user_id')
            ->whereRaw('LOWER(TRIM(customer_email)) = ?', [$normalizedEmail]);

        if ($companyId !== null && $companyId > 0) {
            $query->where('company_id', $companyId);
        }

        return $query->orderByDesc('id')->first();
    }

    protected function findCustomerByEmail(string $normalizedEmail, ?int $companyId): ?User
    {
        $query = User::query()->whereRaw('LOWER(email) = ?', [$normalizedEmail]);
        if ($companyId !== null && $companyId > 0) {
            $query->where('company_id', $companyId);
        }

        return $query->first();
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function splitCustomerName(string $customerName): array
    {
        $customerName = trim(preg_replace('/\s+/u', ' ', $customerName) ?? '');
        if ($customerName === '') {
            return ['', ''];
        }

        $parts = explode(' ', $customerName, 2);

        return [
            $parts[0],
            $parts[1] ?? '',
        ];
    }

    protected function assignKlantRole(User $user, ?int $companyId): void
    {
        $role = Role::firstOrCreate(['name' => 'klant', 'guard_name' => 'web']);
        $registrar = app(PermissionRegistrar::class);
        $previousTeamId = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId($companyId !== null && $companyId > 0 ? $companyId : null);
        $user->assignRole($role);
        $user->unsetRelation('roles');
        $registrar->setPermissionsTeamId($previousTeamId);
        $registrar->forgetCachedPermissions();
    }

    protected function userHasKlantRole(User $user, ?int $companyId): bool
    {
        if (in_array('klant', $user->webRoleNames(), true)) {
            return true;
        }

        if ($companyId === null || $companyId <= 0) {
            return false;
        }

        $registrar = app(PermissionRegistrar::class);
        $previousTeamId = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId($companyId);

        try {
            return $user->hasRole('klant');
        } finally {
            $registrar->setPermissionsTeamId($previousTeamId);
        }
    }

    protected function resolveCompanyIdForUser(User $user): ?int
    {
        if ($user->company_id) {
            return (int) $user->company_id;
        }

        if (app()->bound('resolved_tenant_id')) {
            $resolved = (int) app('resolved_tenant_id');
            if ($resolved > 0) {
                return $resolved;
            }
        }

        return null;
    }
}
