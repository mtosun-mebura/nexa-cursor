<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\User;
use App\Modules\NexaTaxi\Models\TransportCustomerPortalUser;
use App\Modules\NexaTaxi\Models\TransportPassenger;
use App\Modules\NexaTaxi\Models\TransportPassengerGuardian;
use Illuminate\Support\Collection;
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

        if (! $link) {
            return null;
        }

        if (! in_array($link->portal_role, [
            TransportCustomerPortalUser::ROLE_CONTRACTANT,
            TransportCustomerPortalUser::ROLE_CONTRACTOUDER,
        ], true)) {
            return null;
        }

        if (! $this->hasPortalSpatieRole($user, (int) $link->company_id, (string) $link->portal_role)) {
            return null;
        }

        return [
            'company_id' => (int) $link->company_id,
            'transport_customer_id' => (int) $link->transport_customer_id,
            'portal_role' => (string) $link->portal_role,
            'portal_link' => $link,
        ];
    }

    public function isContractant(array $context): bool
    {
        return ($context['portal_role'] ?? '') === TransportCustomerPortalUser::ROLE_CONTRACTANT;
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

    private function hasPortalSpatieRole(User $user, int $companyId, string $portalRole): bool
    {
        $expected = $portalRole === TransportCustomerPortalUser::ROLE_CONTRACTANT
            ? self::SPATIE_CONTRACTANT
            : self::SPATIE_CONTRACTOUDER;

        $registrar = app(PermissionRegistrar::class);
        $previousTeamId = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId($companyId > 0 ? $companyId : null);

        try {
            return $user->hasRole($expected)
                || $user->hasRole($expected, 'api')
                || $user->hasRole([self::SPATIE_CONTRACTANT, self::SPATIE_CONTRACTOUDER]);
        } finally {
            $registrar->setPermissionsTeamId($previousTeamId);
        }
    }
}
