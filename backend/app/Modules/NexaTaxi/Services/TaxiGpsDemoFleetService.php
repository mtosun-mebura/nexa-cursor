<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\Company;
use App\Models\Module;
use App\Models\User;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Modules\NexaTaxi\Models\Vehicle;
use App\Modules\NexaTaxi\Module as TaxiModule;
use App\Modules\NexaTaxi\Support\TaxiDispatchSchema;
use App\Services\ModuleDatabaseService;
use App\Services\UserRoleAssignmentService;
use App\Support\TenantPackageAddon;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TaxiGpsDemoFleetService
{
    public const COMPANY_SLUG = 'qa-gps-taxi';

    public const COMPANY_NAME = 'QA GPS Taxi';

    public const ADMIN_EMAIL = 'qa.gps@nexa.test';

    public const PASSWORD = 'PakketTest2026!';

    public const OFFLINE_PIN = '1234';

    public function __construct(
        protected ModuleDatabaseService $moduleDb,
        protected UserRoleAssignmentService $roles,
        protected TaxiGpsTrackingSettingsService $gpsSettings,
        protected TaxiGpsRoadPathService $roads,
    ) {}

    /**
     * @return array{
     *     company: Company,
     *     admin: User,
     *     drivers: list<User>,
     *     vehicles: list<Vehicle>,
     *     connection: string
     * }
     */
    public function ensure(): array
    {
        $conn = $this->taxiConnection();
        TaxiDispatchSchema::ensureVehicleIdColumn($conn);

        $company = $this->ensureCompany();
        $this->attachTaxiModule($company);
        $admin = $this->ensureAdmin($company);
        $fleet = $this->ensureVehiclesAndDrivers($company, $conn);

        if (! $this->gpsSettings->hasOfflineCode((int) $company->id)) {
            $this->gpsSettings->setOfflineCode(self::OFFLINE_PIN, (int) $company->id);
        }

        return [
            'company' => $company,
            'admin' => $admin,
            'drivers' => $fleet['drivers'],
            'vehicles' => $fleet['vehicles'],
            'connection' => $conn,
        ];
    }

    /**
     * Schuif elk voertuig een stukje langs een vaste lus in Amsterdam.
     *
     * @param  array<int, array{driver_id: int, vehicle_id: int, step: int}>  $states
     * @return array<int, array{driver_id: int, vehicle_id: int, step: int}>
     */
    public function tick(string $connection, int $companyId, array $states = [], int $intervalSeconds = 1): array
    {
        TaxiDispatchSchema::ensureVehicleIdColumn($connection);

        $now = now();
        $intervalSeconds = max(1, $intervalSeconds);
        $next = [];
        foreach ($this->fleetDefinitions() as $index => $def) {
            $state = $states[$index] ?? ['driver_id' => 0, 'vehicle_id' => 0, 'step' => $index * 18];
            $step = (int) ($state['step'] ?? 0);
            $path = $this->pathFor($index, $def['route']);
            $distance = $step * $def['speed_mps'] * $intervalSeconds;
            $point = $this->roads->pointAndHeading($path, $distance);

            $driverId = (int) ($state['driver_id'] ?? 0);
            $vehicleId = (int) ($state['vehicle_id'] ?? 0);
            if ($driverId <= 0 || $vehicleId <= 0) {
                continue;
            }

            DriverAvailability::on($connection)->updateOrCreate(
                ['driver_id' => $driverId],
                [
                    'company_id' => $companyId,
                    'vehicle_id' => $vehicleId,
                    'is_online' => true,
                    'lat' => $point[0],
                    'lng' => $point[1],
                    'location_updated_at' => $now,
                    'last_seen_at' => $now,
                ]
            );

            $next[$index] = [
                'driver_id' => $driverId,
                'vehicle_id' => $vehicleId,
                'step' => $step + 1,
            ];
        }

        return $next;
    }

    /**
     * @return list<array{
     *     email: string,
     *     first_name: string,
     *     last_name: string,
     *     phone: string,
     *     vehicle_name: string,
     *     license_plate: string,
     *     speed_mps: float,
     *     route: list<array{0: float, 1: float}>
     * }>
     */
    public function fleetDefinitions(): array
    {
        return [
            [
                'email' => 'qa.gps.driver1@nexa.test',
                'first_name' => 'Ahmed',
                'last_name' => 'Hassan',
                'phone' => '0612340001',
                'vehicle_name' => 'Mercedes E-Klasse',
                'license_plate' => '12-GPS-1',
                'speed_mps' => 11.0,
                'route' => [
                    [52.3790, 4.9003],
                    [52.3764, 4.8995],
                    [52.3731, 4.8932],
                    [52.3708, 4.8928],
                    [52.3726, 4.8986],
                    [52.3758, 4.9018],
                ],
            ],
            [
                'email' => 'qa.gps.driver2@nexa.test',
                'first_name' => 'Lisa',
                'last_name' => 'de Vries',
                'phone' => '0612340002',
                'vehicle_name' => 'Tesla Model 3',
                'license_plate' => '34-GPS-2',
                'speed_mps' => 13.0,
                'route' => [
                    [52.3738, 4.8920],
                    [52.3712, 4.8924],
                    [52.3686, 4.8936],
                    [52.3674, 4.8972],
                    [52.3690, 4.9004],
                    [52.3718, 4.8992],
                    [52.3734, 4.8956],
                ],
            ],
            [
                'email' => 'qa.gps.driver3@nexa.test',
                'first_name' => 'Marco',
                'last_name' => 'Jansen',
                'phone' => '0612340003',
                'vehicle_name' => 'Volkswagen Passat',
                'license_plate' => '56-GPS-3',
                'speed_mps' => 10.0,
                'route' => [
                    [52.3748, 4.8890],
                    [52.3732, 4.8864],
                    [52.3710, 4.8846],
                    [52.3694, 4.8872],
                    [52.3706, 4.8908],
                    [52.3728, 4.8916],
                ],
            ],
            [
                'email' => 'qa.gps.driver4@nexa.test',
                'first_name' => 'Soraya',
                'last_name' => 'El Idrissi',
                'phone' => '0612340004',
                'vehicle_name' => 'Toyota Prius',
                'license_plate' => '78-GPS-4',
                'speed_mps' => 14.0,
                'route' => [
                    [52.3724, 4.9012],
                    [52.3706, 4.9040],
                    [52.3688, 4.9032],
                    [52.3676, 4.8998],
                    [52.3694, 4.8968],
                    [52.3718, 4.8982],
                ],
            ],
        ];
    }

    private function ensureCompany(): Company
    {
        $company = Company::query()->firstOrNew(['slug' => self::COMPANY_SLUG]);
        $company->name = self::COMPANY_NAME;
        $company->slug = self::COMPANY_SLUG;
        $company->is_active = true;
        $company->industry = 'Logistiek & Transport';
        $company->city = 'Amsterdam';
        $company->latitude = '52.3728';
        $company->longitude = '4.8936';
        $company->email = self::ADMIN_EMAIL;
        $company->package_key = 'pro';
        $company->package_addons = [
            TenantPackageAddon::GPS_TRACKING => 1,
            TenantPackageAddon::EXTRA_CLIENTS => 0,
            TenantPackageAddon::FLEET => 0,
        ];
        $company->description = 'QA-omgeving om live GPS-trackers te beoordelen. Vier voertuigen rijden in Amsterdam.';
        $company->save();

        return $company->fresh();
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

    private function ensureAdmin(Company $company): User
    {
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'api']);

        $admin = $this->upsertUser(self::ADMIN_EMAIL, [
            'first_name' => 'QA',
            'last_name' => 'GPS',
            'phone' => '0612340099',
            'company_id' => $company->id,
        ]);

        $this->roles->syncWebRoles($admin, ['company-admin']);
        $this->grantTaxiPermissions($admin);

        return $admin->fresh();
    }

    /**
     * @return array{drivers: list<User>, vehicles: list<Vehicle>}
     */
    private function ensureVehiclesAndDrivers(Company $company, string $conn): array
    {
        Role::firstOrCreate(['name' => 'chauffeur', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'chauffeur', 'guard_name' => 'api']);

        $drivers = [];
        $vehicles = [];
        $now = now();

        foreach ($this->fleetDefinitions() as $index => $def) {
            $driver = $this->upsertUser($def['email'], [
                'first_name' => $def['first_name'],
                'last_name' => $def['last_name'],
                'phone' => $def['phone'],
                'company_id' => $company->id,
            ]);
            $this->roles->syncWebRoles($driver, ['chauffeur']);

            $vehicle = Vehicle::on($conn)->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'license_plate' => $def['license_plate'],
                ],
                [
                    'name' => $def['vehicle_name'],
                    'type' => Vehicle::TYPE_CAR,
                    'seats' => 4,
                    'person_range' => Vehicle::PERSON_RANGE_1_4,
                    'active' => true,
                    'base_fare' => 3.50,
                    'price_per_km' => 2.40,
                    'price_per_min' => 0.45,
                    'min_fare' => 8.00,
                ]
            );

            $path = $this->pathFor($index, $def['route']);
            $start = $this->roads->pointAndHeading($path, $index * 18 * $def['speed_mps']);
            DriverAvailability::on($conn)->updateOrCreate(
                ['driver_id' => $driver->id],
                [
                    'company_id' => $company->id,
                    'vehicle_id' => $vehicle->id,
                    'is_online' => true,
                    'lat' => $start[0],
                    'lng' => $start[1],
                    'location_updated_at' => $now,
                    'last_seen_at' => $now,
                ]
            );

            $drivers[] = $driver;
            $vehicles[] = $vehicle;
        }

        return ['drivers' => $drivers, 'vehicles' => $vehicles];
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function upsertUser(string $email, array $attrs): User
    {
        $user = User::query()->firstOrNew(['email' => $email]);
        $user->fill(array_merge($attrs, [
            'password' => self::PASSWORD,
            'email_verified_at' => now(),
        ]));
        if (Schema::hasColumn('users', 'is_active')) {
            $user->is_active = true;
        }
        if (Schema::hasColumn('users', 'must_change_password')) {
            $user->must_change_password = false;
        }
        if (Schema::hasColumn('users', 'password_must_be_set')) {
            $user->password_must_be_set = false;
        }
        $user->save();

        return $user;
    }

    private function grantTaxiPermissions(User $user): void
    {
        $names = (new TaxiModule)->registerPermissions();
        foreach ($names as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $registrar = app(PermissionRegistrar::class);
        $previousTeamId = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId($user->company_id ? (int) $user->company_id : null);
        try {
            $user->syncPermissions($names);
        } finally {
            $registrar->setPermissionsTeamId($previousTeamId);
            $registrar->forgetCachedPermissions();
        }
    }

    /**
     * @param  list<array{0: float, 1: float}>  $waypoints
     * @return list<array{0: float, 1: float}>
     */
    private function pathFor(int $index, array $waypoints): array
    {
        if (! isset($this->roadPaths[$index])) {
            $this->roadPaths[$index] = $this->roads->loopPoints($waypoints);
        }

        return $this->roadPaths[$index];
    }

    public function taxiConnection(): string
    {
        $this->moduleDb->ensureModuleStorageReady('taxi');

        return $this->moduleDb->getModuleConnectionName('taxi');
    }

    /** @var array<int, list<array{0: float, 1: float}>> */
    private array $roadPaths = [];
}
