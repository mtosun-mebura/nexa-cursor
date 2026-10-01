<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\Company;
use App\Models\Module;
use App\Models\TaxiNetworkPartnership;
use App\Models\User;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Modules\NexaTaxi\Models\RideDispatchOffer;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\Vehicle;
use App\Modules\NexaTaxi\Module as TaxiModule;
use App\Modules\NexaTaxi\Support\TaxiDispatchSchema;
use App\Services\ModuleDatabaseService;
use App\Services\UserRoleAssignmentService;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Lokale demo voor NEXA Network: twee tenants, partnership, GPS-radius en testritten.
 */
class TaxiNetworkDemoSeedService
{
    public const PASSWORD = 'NetworkTest2026!';

    public const OWNER_SLUG = 'network-owner-taxi';

    public const PARTNER_SLUG = 'network-partner-taxi';

    public const OWNER_ADMIN_EMAIL = 'network.owner.admin@nexa.test';

    public const OWNER_DRIVER_EMAIL = 'network.owner.driver@nexa.test';

    public const PARTNER_ADMIN_EMAIL = 'network.partner.admin@nexa.test';

    public const PARTNER_NEAR_EMAIL = 'network.partner.near@nexa.test';

    public const PARTNER_FAR_EMAIL = 'network.partner.far@nexa.test';

    /** Amsterdam CS — ophaalpunt van de testritten */
    public const PICKUP_LAT = 52.3789;

    public const PICKUP_LNG = 4.9003;

    public function __construct(
        protected ModuleDatabaseService $moduleDb,
        protected UserRoleAssignmentService $roles,
        protected TaxiDispatchSettingsService $dispatchSettings,
        protected TaxiNetworkPartnershipService $partnerships,
    ) {}

    /**
     * @return array{
     *   owner: Company,
     *   partner: Company,
     *   accounts: list<array{role: string, email: string, password: string, company: string}>,
     *   rides: list<array{id: int, label: string, status: string}>,
     *   network: array{mode: string, radius_km: int, fallback_seconds: int, partnership: string},
     *   connection: string
     * }
     */
    public function ensure(bool $freshRides = true): array
    {
        $conn = $this->taxiConnection();
        TaxiDispatchSchema::ensureVehicleIdColumn($conn);

        $owner = $this->ensureCompany(
            self::OWNER_SLUG,
            'Network Owner Taxi',
            self::OWNER_ADMIN_EMAIL,
            'Owner-tenant: deelt ritten naar partners via NEXA Network.'
        );
        $partner = $this->ensureCompany(
            self::PARTNER_SLUG,
            'Network Partner Taxi',
            self::PARTNER_ADMIN_EMAIL,
            'Partner-tenant: ontvangt network-offers binnen max. radius.'
        );

        $this->attachTaxiModule($owner);
        $this->attachTaxiModule($partner);

        $ownerAdmin = $this->ensureAdmin($owner, self::OWNER_ADMIN_EMAIL, 'Owner', 'Admin');
        $partnerAdmin = $this->ensureAdmin($partner, self::PARTNER_ADMIN_EMAIL, 'Partner', 'Admin');

        $ownerDriver = $this->ensureDriver($owner, $conn, [
            'email' => self::OWNER_DRIVER_EMAIL,
            'first_name' => 'Owner',
            'last_name' => 'Chauffeur',
            'phone' => '0610000001',
            'plate' => 'NET-OWN-1',
            'vehicle' => 'Owner Bus',
            'lat' => 52.3795,
            'lng' => 4.9010,
        ]);

        $partnerNear = $this->ensureDriver($partner, $conn, [
            'email' => self::PARTNER_NEAR_EMAIL,
            'first_name' => 'Partner',
            'last_name' => 'Dichtbij',
            'phone' => '0610000002',
            'plate' => 'NET-NEAR-1',
            'vehicle' => 'Partner Near',
            'lat' => 52.3850, // ~0.8 km van CS
            'lng' => 4.9050,
        ]);

        $partnerFar = $this->ensureDriver($partner, $conn, [
            'email' => self::PARTNER_FAR_EMAIL,
            'first_name' => 'Partner',
            'last_name' => 'Verweg',
            'phone' => '0610000003',
            'plate' => 'NET-FAR-1',
            'vehicle' => 'Partner Far',
            'lat' => 52.5200, // ~16 km — buiten 10 km radius
            'lng' => 4.8900,
        ]);

        $this->configureNetwork($owner, $partner);
        $rides = $this->seedRides($conn, $owner, $ownerDriver, $freshRides);

        return [
            'owner' => $owner,
            'partner' => $partner,
            'accounts' => [
                ['role' => 'Admin Owner (admin + Chauffeur dispatch)', 'email' => $ownerAdmin->email, 'password' => self::PASSWORD, 'company' => $owner->name],
                ['role' => 'Chauffeur Owner (hand over → network)', 'email' => $ownerDriver->email, 'password' => self::PASSWORD, 'company' => $owner->name],
                ['role' => 'Admin Partner', 'email' => $partnerAdmin->email, 'password' => self::PASSWORD, 'company' => $partner->name],
                ['role' => 'Chauffeur Partner dichtbij (≤ radius)', 'email' => $partnerNear->email, 'password' => self::PASSWORD, 'company' => $partner->name],
                ['role' => 'Chauffeur Partner verweg (> radius)', 'email' => $partnerFar->email, 'password' => self::PASSWORD, 'company' => $partner->name],
            ],
            'rides' => $rides,
            'network' => [
                'mode' => $this->dispatchSettings->networkMode($owner->id),
                'radius_km' => $this->dispatchSettings->networkMaxRadiusKm($owner->id),
                'fallback_seconds' => $this->dispatchSettings->networkFallbackSeconds($owner->id),
                'partnership' => sprintf(
                    '%s → %s (accepted)',
                    $owner->name,
                    $partner->name
                ),
            ],
            'connection' => $conn,
        ];
    }

    public function taxiConnection(): string
    {
        $this->moduleDb->ensureModuleStorageReady('taxi');

        return $this->moduleDb->getModuleConnectionName('taxi');
    }

    private function ensureCompany(string $slug, string $name, string $email, string $description): Company
    {
        $company = Company::query()->firstOrNew(['slug' => $slug]);
        $company->name = $name;
        $company->slug = $slug;
        $company->is_active = true;
        $company->industry = 'Logistiek & Transport';
        $company->city = 'Amsterdam';
        $company->latitude = (string) self::PICKUP_LAT;
        $company->longitude = (string) self::PICKUP_LNG;
        $company->email = $email;
        $company->package_key = 'business';
        $company->description = $description;
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

    private function ensureAdmin(Company $company, string $email, string $first, string $last): User
    {
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'api']);

        $admin = $this->upsertUser($email, [
            'first_name' => $first,
            'last_name' => $last,
            'phone' => '0611111100',
            'company_id' => $company->id,
        ]);
        $this->roles->syncWebRoles($admin, ['company-admin']);
        $this->grantTaxiPermissions($admin);

        return $admin->fresh();
    }

    /**
     * @param  array{
     *   email: string,
     *   first_name: string,
     *   last_name: string,
     *   phone: string,
     *   plate: string,
     *   vehicle: string,
     *   lat: float,
     *   lng: float
     * }  $def
     */
    private function ensureDriver(Company $company, string $conn, array $def): User
    {
        Role::firstOrCreate(['name' => 'chauffeur', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'chauffeur', 'guard_name' => 'api']);

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
                'license_plate' => $def['plate'],
            ],
            [
                'name' => $def['vehicle'],
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

        $now = now();
        DriverAvailability::on($conn)->updateOrCreate(
            ['driver_id' => $driver->id],
            [
                'company_id' => $company->id,
                'vehicle_id' => $vehicle->id,
                'is_online' => true,
                'lat' => $def['lat'],
                'lng' => $def['lng'],
                'location_updated_at' => $now,
                'last_seen_at' => $now,
            ]
        );

        return $driver->fresh();
    }

    private function configureNetwork(Company $owner, Company $partner): void
    {
        // Owner: auto-mode + 10 km radius + 60s fallback (snel te testen).
        $this->dispatchSettings->setNetworkMode(TaxiDispatchSettingsService::NETWORK_MODE_AUTO, $owner->id);
        $this->dispatchSettings->setNetworkMaxRadiusKm(10, $owner->id);
        $this->dispatchSettings->setNetworkFallbackSeconds(60, $owner->id);

        // Partner: network aan (manual) zodat zij ook uit kunnen handen geven.
        $this->dispatchSettings->setNetworkMode(TaxiDispatchSettingsService::NETWORK_MODE_MANUAL, $partner->id);
        $this->dispatchSettings->setNetworkMaxRadiusKm(10, $partner->id);
        $this->dispatchSettings->setNetworkFallbackSeconds(60, $partner->id);

        $invite = $this->partnerships->ensureActiveInviteCode($partner->id);
        $invite->forceFill(['auto_accept' => true])->save();

        $existing = TaxiNetworkPartnership::query()
            ->where('owner_company_id', $owner->id)
            ->where('partner_company_id', $partner->id)
            ->first();

        if ($existing?->status === TaxiNetworkPartnership::STATUS_ACCEPTED) {
            $this->partnerships->syncOwnerPartnerIds($owner->id);

            return;
        }

        if ($existing) {
            $existing->forceFill([
                'status' => TaxiNetworkPartnership::STATUS_ACCEPTED,
                'accepted_at' => now(),
                'declined_at' => null,
                'revoked_at' => null,
                'invite_code_id' => $invite->id,
            ])->save();
            $this->partnerships->syncOwnerPartnerIds($owner->id);

            return;
        }

        $this->partnerships->redeemInviteCode($owner->id, (string) $invite->code);
    }

    /**
     * @return list<array{id: int, label: string, status: string}>
     */
    private function seedRides(string $conn, Company $owner, User $ownerDriver, bool $fresh): array
    {
        if ($fresh) {
            $oldIds = RideRequest::on($conn)
                ->where('company_id', $owner->id)
                ->where('customer_note', 'like', '%[network-demo]%')
                ->pluck('id')
                ->all();
            if ($oldIds !== []) {
                RideDispatchOffer::on($conn)->whereIn('ride_request_id', $oldIds)->delete();
                RideRequest::on($conn)->whereIn('id', $oldIds)->delete();
            }
        }

        $now = now();
        $ttl = $this->dispatchSettings->offerTtlSeconds($owner->id);
        $expires = $now->copy()->addSeconds($ttl);
        $created = [];

        // 1) Geaccepteerd door owner-chauffeur → in app “Naar network” testen.
        $handover = RideRequest::on($conn)->create([
            'company_id' => $owner->id,
            'driver_id' => $ownerDriver->id,
            'vehicle_id' => DriverAvailability::vehicleIdForDriver($conn, $ownerDriver->id),
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'Stationsplein 15, Amsterdam Centraal',
            'dropoff_address' => 'Leidseplein 26, Amsterdam',
            'pickup_lat' => self::PICKUP_LAT,
            'pickup_lng' => self::PICKUP_LNG,
            'dropoff_lat' => 52.3641,
            'dropoff_lng' => 4.8828,
            'distance_meters' => 3200,
            'duration_seconds' => 720,
            'passengers' => 2,
            'pickup_at' => $now->copy()->addMinutes(25),
            'quoted_price' => 28.50,
            'payment_method' => RideRequest::PAYMENT_METHOD_DRIVER,
            'payment_status' => RideRequest::PAYMENT_STATUS_PENDING,
            'customer_name' => 'Demo Network Klant',
            'customer_email' => 'network.klant@example.test',
            'customer_phone' => '06-10002000',
            'customer_note' => '[network-demo] Geaccepteerd — gebruik “Naar network” in de chauffeur-app',
        ]);
        RideDispatchOffer::on($conn)->create([
            'ride_request_id' => $handover->id,
            'company_id' => $owner->id,
            'driver_id' => $ownerDriver->id,
            'status' => RideDispatchOffer::STATUS_ACCEPTED,
            'wave' => 1,
            'offered_at' => $now->copy()->subMinutes(2),
            'expires_at' => $expires,
            'responded_at' => $now->copy()->subMinute(),
        ]);
        $created[] = ['id' => (int) $handover->id, 'label' => 'Hand-over naar network', 'status' => $handover->status];

        // 2) Open aanbod eigen vloot (auto-fallback na 60s als niemand claimt).
        $auto = RideRequest::on($conn)->create([
            'company_id' => $owner->id,
            'driver_id' => null,
            'status' => RideRequest::STATUS_OFFERED,
            'pickup_address' => 'Damrak 1, Amsterdam',
            'dropoff_address' => 'Museumplein 6, Amsterdam',
            'pickup_lat' => 52.3765,
            'pickup_lng' => 4.8970,
            'dropoff_lat' => 52.3570,
            'dropoff_lng' => 4.8810,
            'distance_meters' => 4100,
            'duration_seconds' => 900,
            'passengers' => 1,
            'pickup_at' => $now->copy()->addHour(),
            'quoted_price' => 34.00,
            'payment_method' => RideRequest::PAYMENT_METHOD_DRIVER,
            'payment_status' => RideRequest::PAYMENT_STATUS_PENDING,
            'customer_name' => 'Demo Auto Fallback',
            'customer_email' => 'network.auto@example.test',
            'customer_phone' => '06-10003000',
            'customer_note' => '[network-demo] Open offer — na fallback (60s) + expire naar partners binnen radius',
        ]);
        RideDispatchOffer::on($conn)->create([
            'ride_request_id' => $auto->id,
            'company_id' => $owner->id,
            'driver_id' => $ownerDriver->id,
            'status' => RideDispatchOffer::STATUS_PENDING,
            'wave' => 1,
            'offered_at' => $now,
            'expires_at' => $expires,
            'responded_at' => null,
        ]);
        $created[] = ['id' => (int) $auto->id, 'label' => 'Auto-fallback (eigen vloot eerst)', 'status' => $auto->status];

        // 3) Extra open rit om inbox te vullen.
        $inbox = RideRequest::on($conn)->create([
            'company_id' => $owner->id,
            'driver_id' => null,
            'status' => RideRequest::STATUS_OFFERED,
            'pickup_address' => 'Nieuwmarkt 4, Amsterdam',
            'dropoff_address' => 'Amstelstation, Amsterdam',
            'pickup_lat' => 52.3723,
            'pickup_lng' => 4.9007,
            'dropoff_lat' => 52.3464,
            'dropoff_lng' => 4.9178,
            'distance_meters' => 4800,
            'duration_seconds' => 1100,
            'passengers' => 3,
            'pickup_at' => $now->copy()->addMinutes(50),
            'quoted_price' => 41.25,
            'payment_method' => RideRequest::PAYMENT_METHOD_DRIVER,
            'payment_status' => RideRequest::PAYMENT_STATUS_PENDING,
            'customer_name' => 'Demo Inbox Klant',
            'customer_email' => 'network.inbox@example.test',
            'customer_phone' => '06-10004000',
            'customer_note' => '[network-demo] Extra inbox-rit voor owner-chauffeur',
        ]);
        RideDispatchOffer::on($conn)->create([
            'ride_request_id' => $inbox->id,
            'company_id' => $owner->id,
            'driver_id' => $ownerDriver->id,
            'status' => RideDispatchOffer::STATUS_PENDING,
            'wave' => 1,
            'offered_at' => $now,
            'expires_at' => $expires,
            'responded_at' => null,
        ]);
        $created[] = ['id' => (int) $inbox->id, 'label' => 'Extra inbox-rit', 'status' => $inbox->status];

        return $created;
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
}
