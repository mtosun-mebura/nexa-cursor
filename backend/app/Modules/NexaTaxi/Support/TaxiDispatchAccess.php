<?php

namespace App\Modules\NexaTaxi\Support;

use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Losse schermen binnen chauffeurdispatch en GPS, toewijsbaar per rol.
 * Zichtbaarheid volgt alleen deze rechten (plus super-admin).
 */
final class TaxiDispatchAccess
{
    /**
     * @return list<array{key: string, permission: string, label: string, group: string}>
     */
    public static function sections(): array
    {
        return [
            ['key' => 'accept_timer', 'permission' => 'dispatch.accept_timer.view', 'label' => 'Acceptatietimer', 'group' => 'dispatch'],
            ['key' => 'booking_notifications', 'permission' => 'dispatch.booking_notifications.view', 'label' => 'Boekingsmeldingen', 'group' => 'dispatch'],
            ['key' => 'customer_login', 'permission' => 'dispatch.customer_login.view', 'label' => 'Mijn Taxi – klant inlogcode', 'group' => 'dispatch'],
            ['key' => 'customer_accept', 'permission' => 'dispatch.customer_accept.view', 'label' => 'Klantmelding bij acceptatie', 'group' => 'dispatch'],
            ['key' => 'payments', 'permission' => 'dispatch.payments.view', 'label' => 'Betalingen (Mollie)', 'group' => 'dispatch'],
            ['key' => 'network', 'permission' => 'dispatch.network.view', 'label' => 'NEXA Network', 'group' => 'dispatch'],
            ['key' => 'gps_vehicles', 'permission' => 'gps_tracking.view', 'label' => 'GPS-tracker: Voertuigen', 'group' => 'gps'],
            ['key' => 'gps_settings', 'permission' => 'gps_tracking_settings.view', 'label' => 'GPS-tracker: Configuratie', 'group' => 'gps'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function permissionNames(): array
    {
        return array_column(self::sections(), 'permission');
    }

    /**
     * @return list<string>
     */
    public static function dispatchPermissionNames(): array
    {
        $names = [];
        foreach (self::sections() as $section) {
            if ($section['group'] === 'dispatch') {
                $names[] = $section['permission'];
            }
        }

        return $names;
    }

    /**
     * @return list<string>
     */
    public static function dispatchMenuPermissions(): array
    {
        return self::dispatchPermissionNames();
    }

    /**
     * Vaste rollen krijgen de deelrechten die de oude gates feitelijk al gaven.
     *
     * super-admin en company-admin: rides.view én vehicles.view openden elk dispatch-scherm en beide GPS-pagina's.
     * marketplace: het dispatch-scherm toont voor dat pakket alleen NEXA Network.
     * demo: menu-sleutel dispatch_settings; GPS staat niet in de demo-menu-sleutels.
     * chauffeur, staff, candidate, klant, driver_pending, contractant, contractouder en chauffeur-inkomsten: geen admin-dispatch.
     *
     * @return array<string, list<string>>
     */
    public static function rolePresetPermissions(): array
    {
        return [
            'super-admin' => self::permissionNames(),
            'company-admin' => self::permissionNames(),
            'marketplace' => ['dispatch.network.view'],
            'demo' => self::dispatchPermissionNames(),
        ];
    }

    public static function syncKnownRolePresets(): void
    {
        self::ensurePermissions();

        foreach (self::rolePresetPermissions() as $roleName => $permissions) {
            if ($permissions === []) {
                continue;
            }

            $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role === null) {
                continue;
            }

            $role->givePermissionTo($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public static function ensurePermissions(): void
    {
        foreach (self::permissionNames() as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    public static function withoutGranular(Collection $permissions): Collection
    {
        $names = array_flip(self::permissionNames());

        return $permissions
            ->reject(function ($permission) use ($names) {
                $name = is_object($permission) ? (string) ($permission->name ?? '') : (string) $permission;

                return isset($names[$name]);
            })
            ->values();
    }

    public static function allows(?User $user, string $permission): bool
    {
        if ($user === null || $permission === '') {
            return false;
        }

        try {
            return $user->can($permission);
        } catch (\Throwable) {
            return false;
        }
    }

    public static function isSuper(?User $user): bool
    {
        return $user !== null && ($user->hasRole('super-admin') || $user->isSuperAdmin());
    }

    public static function sees(?User $user, string $key): bool
    {
        if ($user === null) {
            return false;
        }
        if (self::isSuper($user)) {
            return true;
        }

        foreach (self::sections() as $section) {
            if ($section['key'] === $key) {
                return self::allows($user, $section['permission']);
            }
        }

        return false;
    }

    /**
     * @return array<string, bool>
     */
    public static function visibleMap(?User $user): array
    {
        $map = [];
        foreach (self::sections() as $section) {
            $map[$section['key']] = self::sees($user, $section['key']);
        }

        return $map;
    }

    public static function seesDispatchScreen(?User $user): bool
    {
        if (self::isSuper($user)) {
            return true;
        }

        foreach (self::sections() as $section) {
            if ($section['group'] === 'dispatch' && self::sees($user, $section['key'])) {
                return true;
            }
        }

        return false;
    }

    public static function authorizeDispatchScreen(): void
    {
        if (! self::seesDispatchScreen(auth()->user())) {
            abort(403, 'Geen rechten voor chauffeur dispatch.');
        }
    }

    public static function canMutate(?User $user, string $key): bool
    {
        if (self::isSuper($user)) {
            return true;
        }

        foreach (self::sections() as $section) {
            if ($section['key'] === $key) {
                return self::allows($user, $section['permission']);
            }
        }

        return false;
    }

    public static function authorizeMutate(string $key): void
    {
        if (! self::canMutate(auth()->user(), $key)) {
            abort(403, 'Geen rechten voor deze actie.');
        }
    }
}
