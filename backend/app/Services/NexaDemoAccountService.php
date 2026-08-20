<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Module;
use App\Models\User;
use App\Modules\NexaTaxi\Models\Vehicle;
use App\Modules\NexaTaxi\Module as TaxiModule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class NexaDemoAccountService
{
    public function roleName(): string
    {
        return (string) config('nexa_demo.role', 'demo');
    }

    /**
     * @return list<string>
     */
    public function permissionNames(): array
    {
        return array_values(array_filter(array_map(
            'strval',
            (array) config('nexa_demo.permissions', [])
        )));
    }

    /**
     * @return list<string>
     */
    public function menuKeys(): array
    {
        return array_values(array_filter(array_map(
            'strval',
            (array) config('nexa_demo.menu_keys', [])
        )));
    }

    public function isDemoUser(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        $email = strtolower(trim((string) config('nexa_demo.email', 'demo@nexasuite.nl')));
        if ($email !== '' && strcasecmp((string) $user->email, $email) === 0) {
            return true;
        }

        return in_array($this->roleName(), $user->webRoleNames(), true);
    }

    public function demoCompanyId(): ?int
    {
        $slug = (string) config('nexa_demo.company_slug', 'nexa-taxi-demo');
        $id = Company::query()->where('slug', $slug)->value('id');

        return $id ? (int) $id : null;
    }

    public function isDemoCompanyId(?int $companyId): bool
    {
        if ($companyId === null || $companyId <= 0) {
            return false;
        }

        $demoId = $this->demoCompanyId();

        return $demoId !== null && $demoId === $companyId;
    }

    public function shouldSuppressOutgoingMail(): bool
    {
        if (! config('nexa_demo.enabled', true)) {
            return false;
        }

        $user = auth()->user();

        return $user instanceof User && $this->isDemoUser($user);
    }

    /**
     * @return array{company: ?Company, user: ?User, created: bool}
     */
    public function ensure(): array
    {
        if (! config('nexa_demo.enabled', true)) {
            return ['company' => null, 'user' => null, 'created' => false];
        }
        if (! Schema::hasTable('users') || ! Schema::hasTable('companies')) {
            return ['company' => null, 'user' => null, 'created' => false];
        }

        $email = strtolower(trim((string) config('nexa_demo.email', 'demo@nexasuite.nl')));
        $password = (string) config('nexa_demo.password', 'DemoTaxi2026!');
        $companyName = (string) config('nexa_demo.company_name', 'Nexa Taxi Demo');
        $companySlug = (string) config('nexa_demo.company_slug', 'nexa-taxi-demo');

        $company = Company::query()->firstOrCreate(
            ['slug' => $companySlug],
            [
                'name' => $companyName,
                'is_active' => true,
                'industry' => 'Logistiek & Transport',
                'city' => 'Amsterdam',
                'description' => 'Open demo-omgeving van Nexa Taxi. Speel met ritten, voertuigen en dispatch.',
            ]
        );
        $company->name = $companyName;
        $company->slug = $companySlug;
        $company->is_active = true;
        $company->save();

        $this->attachConfiguredModules($company);
        $this->ensureDemoRole();

        $userAttrs = [
            'first_name' => (string) config('nexa_demo.first_name', 'Demo'),
            'last_name' => (string) config('nexa_demo.last_name', 'Gebruiker'),
            'password' => Hash::make($password),
            'email_verified_at' => now(),
            'company_id' => $company->id,
        ];
        if (Schema::hasColumn('users', 'is_active')) {
            $userAttrs['is_active'] = true;
        }

        $user = User::query()->firstOrNew(['email' => $email]);
        $created = ! $user->exists;
        $user->fill($userAttrs);
        $user->save();

        $this->assignDemoRole($user);
        $this->ensureTaxiPermissions($user);

        return ['company' => $company, 'user' => $user, 'created' => $created];
    }

    /**
     * Zet account + wachtwoord terug en veeg operationele data van het demobedrijf.
     *
     * @return array{company: ?Company, user: ?User, created: bool}
     */
    public function reset(): array
    {
        $result = $this->ensure();
        if ($result['company'] === null || $result['user'] === null) {
            return $result;
        }

        $this->purgeOperationalData($result['company']);
        $this->removeExtraUsers($result['company'], $result['user']);
        $this->seedSampleVehicles($result['company']);

        return $result;
    }

    private function attachConfiguredModules(Company $company): void
    {
        if (! Schema::hasTable('modules') || ! Schema::hasTable('company_module')) {
            return;
        }

        $wanted = array_map(
            fn ($name) => strtolower(trim((string) $name)),
            (array) config('nexa_demo.modules', ['taxi'])
        );
        $wanted = array_values(array_filter($wanted));

        foreach ($wanted as $name) {
            $module = Module::query()->whereRaw('LOWER(name) = ?', [$name])->first();
            if ($module === null) {
                continue;
            }
            if (! $company->modules()->where('modules.id', $module->id)->exists()) {
                $company->modules()->attach($module->id);
            }
        }
    }

    private function ensureDemoRole(): void
    {
        $name = $this->roleName();
        Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => $name, 'guard_name' => 'api']);
    }

    private function assignDemoRole(User $user): void
    {
        if (! class_exists(Role::class) || ! Role::query()->where('name', $this->roleName())->where('guard_name', 'web')->exists()) {
            return;
        }

        app(UserRoleAssignmentService::class)->syncWebRoles($user, [$this->roleName()]);
    }

    private function ensureTaxiPermissions(User $user): void
    {
        $names = $this->permissionNames();
        if ($names === []) {
            $names = (new TaxiModule)->registerPermissions();
        }
        if ($names === []) {
            return;
        }

        foreach ($names as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $role = Role::query()->where('name', $this->roleName())->where('guard_name', 'web')->first();
        if ($role) {
            $role->syncPermissions($names);
        }

        $registrar = app(PermissionRegistrar::class);
        $previousTeamId = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId($user->company_id ? (int) $user->company_id : null);
        try {
            $user->syncPermissions($names);
        } catch (\Throwable) {
            // Permissies bestaan mogelijk nog niet op deze guard/connectie.
        } finally {
            $registrar->setPermissionsTeamId($previousTeamId);
            $registrar->forgetCachedPermissions();
        }
    }

    private function removeExtraUsers(Company $company, User $keep): void
    {
        User::query()
            ->where('company_id', $company->id)
            ->where('id', '!=', $keep->id)
            ->each(function (User $extra): void {
                $extra->delete();
            });
    }

    private function purgeOperationalData(Company $company): void
    {
        $conn = $this->taxiConnection();
        if ($conn === null) {
            return;
        }

        $companyId = (int) $company->id;
        $rideIds = $this->idsByCompany($conn, 'ride_requests', $companyId);
        $passengerIds = $this->idsByCompany($conn, 'transport_passengers', $companyId);
        $groupIds = $this->idsByCompany($conn, 'transport_groups', $companyId);
        $templateIds = $this->idsByCompany($conn, 'transport_route_templates', $companyId);
        $customerIds = $this->idsByCompany($conn, 'transport_customers', $companyId);
        $documentIds = $this->idsByCompany($conn, 'knowledge_documents', $companyId);

        $this->deleteWhereIn($conn, 'ride_stops', 'ride_request_id', $rideIds);
        $this->deleteWhereIn($conn, 'ride_request_notification_logs', 'ride_request_id', $rideIds);
        $this->deleteWhereIn($conn, 'ride_dispatch_offers', 'ride_request_id', $rideIds);
        $this->deleteWhereIn($conn, 'ride_payments', 'ride_request_id', $rideIds);
        $this->deleteWhereIn($conn, 'transport_group_members', 'transport_group_id', $groupIds);
        $this->deleteWhereIn($conn, 'transport_route_stops', 'transport_route_template_id', $templateIds);
        $this->deleteWhereIn($conn, 'transport_passenger_guardians', 'transport_passenger_id', $passengerIds);
        $this->deleteWhereIn($conn, 'transport_passenger_absences', 'transport_passenger_id', $passengerIds);
        $this->deleteWhereIn($conn, 'knowledge_chunks', 'document_id', $documentIds);

        foreach ([
            'transport_assignments',
            'transport_occurrences',
            'transport_individual_bookings',
            'transport_announcements',
            'transport_schedule_exceptions',
            'ride_dispatch_offers',
            'ride_payments',
            'ride_requests',
            'transport_groups',
            'transport_route_templates',
            'transport_passengers',
            'transport_contracts',
            'transport_payment_mandates',
            'transport_customer_portal_users',
            'transport_customers',
            'driver_availability',
            'knowledge_documents',
            'vehicles',
        ] as $table) {
            $this->deleteByCompanyId($conn, $table, $companyId);
        }
    }

    private function seedSampleVehicles(Company $company): void
    {
        $conn = $this->taxiConnection();
        if ($conn === null || ! Schema::connection($conn)->hasTable('vehicles')) {
            return;
        }

        $samples = [
            [
                'name' => 'Demo personenauto',
                'type' => Vehicle::TYPE_CAR,
                'license_plate' => 'G-DEMO-1',
                'seats' => 4,
                'person_range' => Vehicle::PERSON_RANGE_1_4,
                'base_fare' => 3.50,
                'price_per_km' => 2.40,
                'price_per_min' => 0.45,
                'min_fare' => 8.00,
            ],
            [
                'name' => 'Demo busje',
                'type' => Vehicle::TYPE_VAN,
                'license_plate' => 'G-DEMO-8',
                'seats' => 8,
                'person_range' => Vehicle::PERSON_RANGE_5_8,
                'base_fare' => 6.00,
                'price_per_km' => 2.90,
                'price_per_min' => 0.55,
                'min_fare' => 12.00,
            ],
        ];

        foreach ($samples as $sample) {
            Vehicle::on($conn)->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'license_plate' => $sample['license_plate'],
                ],
                array_merge($sample, [
                    'company_id' => $company->id,
                    'active' => true,
                ])
            );
        }
    }

    private function taxiConnection(): ?string
    {
        $db = app(ModuleDatabaseService::class);
        if ($db->supportsModuleDatabases()) {
            try {
                $db->ensureModuleStorageReady('taxi');
                $conn = $db->getModuleConnectionName('taxi');
                if (is_string($conn) && $conn !== '' && Schema::connection($conn)->hasTable('vehicles')) {
                    return $conn;
                }
            } catch (\Throwable) {
                // Geen taxi-schema beschikbaar.
            }
        }

        if (Schema::hasTable('vehicles')) {
            return (string) config('database.default');
        }

        return null;
    }

    /**
     * @return list<int>
     */
    private function idsByCompany(string $conn, string $table, int $companyId): array
    {
        if (! Schema::connection($conn)->hasTable($table) || ! Schema::connection($conn)->hasColumn($table, 'company_id')) {
            return [];
        }

        return DB::connection($conn)->table($table)->where('company_id', $companyId)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @param  list<int>  $ids
     */
    private function deleteWhereIn(string $conn, string $table, string $column, array $ids): void
    {
        if ($ids === [] || ! Schema::connection($conn)->hasTable($table) || ! Schema::connection($conn)->hasColumn($table, $column)) {
            return;
        }

        DB::connection($conn)->table($table)->whereIn($column, $ids)->delete();
    }

    private function deleteByCompanyId(string $conn, string $table, int $companyId): void
    {
        if (! Schema::connection($conn)->hasTable($table) || ! Schema::connection($conn)->hasColumn($table, 'company_id')) {
            return;
        }

        DB::connection($conn)->table($table)->where('company_id', $companyId)->delete();
    }
}
