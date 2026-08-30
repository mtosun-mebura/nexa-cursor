<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\User;
use App\Modules\NexaTaxi\Models\TransportCustomer;
use App\Modules\NexaTaxi\Models\TransportCustomerPortalUser;
use App\Modules\NexaTaxi\Models\TransportPassenger;
use App\Modules\NexaTaxi\Models\TransportPassengerGuardian;
use App\Services\ModuleDatabaseService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

class TaxiContractPortalAccessService
{
    public const SPATIE_CONTRACTANT = 'contractant';

    public const SPATIE_CONTRACTOUDER = 'contractouder';

    /**
     * @return array{
     *     company_id: int,
     *     transport_customer_id: int,
     *     portal_role: string,
     *     portal_link: TransportCustomerPortalUser
     * }|null
     */
    public function resolveContext(string $conn, User $user): ?array
    {
        app(TaxiContractvervoerSchemaService::class)->ensureContractPortalTables($conn);

        $link = TransportCustomerPortalUser::on($conn)
            ->where('user_id', $user->id)
            ->where('active', true)
            ->orderByDesc('id')
            ->first();

        if ($link && $this->isValidPortalRole((string) $link->portal_role)) {
            return $this->contextFromLink($link);
        }

        if (TransportCustomerPortalUser::on($conn)->where('user_id', $user->id)->exists()) {
            return null;
        }

        $portalRole = $this->spatiePortalRole($user);
        if ($portalRole === null) {
            return null;
        }

        $link = $this->provisionPortalLink($conn, $user, $portalRole);

        return $link ? $this->contextFromLink($link) : null;
    }

    public function isContractant(array $context): bool
    {
        return ($context['portal_role'] ?? '') === TransportCustomerPortalUser::ROLE_CONTRACTANT;
    }

    public function userMayAccessPortal(User $user, ?string $conn = null): bool
    {
        if ($this->spatiePortalRole($user) !== null) {
            return true;
        }

        if ($conn === null || $conn === '') {
            $conn = app(ModuleDatabaseService::class)->getModuleConnectionName('taxi');
        }

        app(TaxiContractvervoerSchemaService::class)->ensureContractPortalTables($conn);

        return TransportCustomerPortalUser::on($conn)
            ->where('user_id', $user->id)
            ->where('active', true)
            ->exists();
    }

    /**
     * Passagiers die deze portaluser mag zien.
     *
     * @return Collection<int, TransportPassenger>
     */
    public function visiblePassengers(string $conn, array $context): Collection
    {
        $customerId = (int) $context['transport_customer_id'];
        $companyId = (int) $context['company_id'];

        $query = TransportPassenger::on($conn)
            ->where('company_id', $companyId)
            ->where('active', true)
            ->whereHas('contract', function ($q) use ($customerId) {
                $q->where('transport_customer_id', $customerId)
                    ->whereIn('status', ['active', 'paused']);
            })
            ->orderBy('last_name')
            ->orderBy('first_name');

        if (! $this->isContractant($context)) {
            $passengerIds = TransportPassengerGuardian::on($conn)
                ->where('user_id', $context['portal_link']->user_id)
                ->where('company_id', $companyId)
                ->pluck('transport_passenger_id');

            $query->whereIn('id', $passengerIds);
        }

        return $query->get();
    }

    public function canAccessPassenger(string $conn, array $context, int $passengerId): bool
    {
        return $this->visiblePassengers($conn, $context)->contains(fn ($p) => (int) $p->id === $passengerId);
    }

    public function assignPortalRole(User $user, string $portalRole, int $companyId): void
    {
        $roleName = $portalRole === TransportCustomerPortalUser::ROLE_CONTRACTANT
            ? self::SPATIE_CONTRACTANT
            : self::SPATIE_CONTRACTOUDER;

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

        $registrar = app(PermissionRegistrar::class);
        $previousTeamId = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId($companyId > 0 ? $companyId : null);

        try {
            if (! $user->hasRole($roleName)) {
                $user->assignRole($roleName);
            }
            $user->unsetRelation('roles');
        } finally {
            $registrar->setPermissionsTeamId($previousTeamId);
            $registrar->forgetCachedPermissions();
        }
    }

    /**
     * @return array{
     *     company_id: int,
     *     transport_customer_id: int,
     *     portal_role: string,
     *     portal_link: TransportCustomerPortalUser
     * }
     */
    private function contextFromLink(TransportCustomerPortalUser $link): array
    {
        return [
            'company_id' => (int) $link->company_id,
            'transport_customer_id' => (int) $link->transport_customer_id,
            'portal_role' => (string) $link->portal_role,
            'portal_link' => $link,
        ];
    }

    private function isValidPortalRole(string $role): bool
    {
        return in_array($role, [
            TransportCustomerPortalUser::ROLE_CONTRACTANT,
            TransportCustomerPortalUser::ROLE_CONTRACTOUDER,
        ], true);
    }

    /**
     * Team-agnostisch, zelfde bron als het gebruikersscherm (web-rollen).
     */
    private function spatiePortalRole(User $user): ?string
    {
        $names = array_map(
            static fn ($name) => strtolower(trim((string) $name)),
            $user->webRoleNames()
        );

        if (in_array(self::SPATIE_CONTRACTANT, $names, true)) {
            return TransportCustomerPortalUser::ROLE_CONTRACTANT;
        }

        if (in_array(self::SPATIE_CONTRACTOUDER, $names, true)) {
            return TransportCustomerPortalUser::ROLE_CONTRACTOUDER;
        }

        return null;
    }

    private function provisionPortalLink(string $conn, User $user, string $portalRole): ?TransportCustomerPortalUser
    {
        $companyId = (int) $user->company_id;
        if ($companyId <= 0) {
            return null;
        }

        $query = TransportCustomer::on($conn)
            ->where('company_id', $companyId)
            ->orderBy('id');

        if (Schema::connection($conn)->hasColumn('transport_customers', 'active')) {
            $query->where(function ($q) {
                $q->where('active', true)->orWhereNull('active');
            });
        }

        $customer = $query->first();
        if (! $customer) {
            return null;
        }

        return TransportCustomerPortalUser::on($conn)->updateOrCreate(
            [
                'transport_customer_id' => $customer->id,
                'user_id' => $user->id,
            ],
            [
                'company_id' => $companyId,
                'portal_role' => $portalRole,
                'active' => true,
            ]
        );
    }
}
