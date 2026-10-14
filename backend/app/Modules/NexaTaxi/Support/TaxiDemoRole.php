<?php

namespace App\Modules\NexaTaxi\Support;

use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Rol die demofuncties in de app aanzet (ritsimulator e.d.).
 */
class TaxiDemoRole
{
    public const NAME = 'demo';

    public static function ensure(?string $connection = null): void
    {
        $conn = $connection ?: (string) config('database.default');

        try {
            Role::on($conn)->firstOrCreate(['name' => self::NAME, 'guard_name' => 'web']);
            Role::on($conn)->firstOrCreate(['name' => self::NAME, 'guard_name' => 'api']);
        } catch (\Throwable) {
            // Rechten-tabellen ontbreken op deze verbinding.
        }
    }

    public static function userHas(User $user): bool
    {
        self::ensure($user->getConnectionName());

        $names = array_map(
            static fn ($name) => strtolower(trim((string) $name)),
            $user->webRoleNames()
        );

        return in_array(self::NAME, $names, true);
    }
}
