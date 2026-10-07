<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ModuleSchemaService;
use Database\Seeders\ApplicationBootstrapSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RoleSeederPreservesPasswordTest extends TestCase
{
    #[Test]
    public function seeder_creates_superadmin_with_default_password_when_missing(): void
    {
        $this->artisan('db:seed', ['--class' => RoleSeeder::class]);

        $user = User::query()->where('email', ModuleSchemaService::SUPERADMIN_EMAIL)->first();

        $this->assertNotNull($user);
        $this->assertTrue(Hash::check(ModuleSchemaService::SUPERADMIN_PASSWORD, $user->password));
    }

    #[Test]
    public function seeder_does_not_reset_existing_superadmin_password(): void
    {
        User::factory()->create([
            'email' => ModuleSchemaService::SUPERADMIN_EMAIL,
            'password' => 'custom-secret-password',
            'first_name' => 'Custom',
            'last_name' => 'Name',
        ]);

        $this->artisan('db:seed', ['--class' => RoleSeeder::class]);
        $this->artisan('nexa:ensure-bootstrap');

        $user = User::query()->where('email', ModuleSchemaService::SUPERADMIN_EMAIL)->first();

        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('custom-secret-password', $user->password));
        $this->assertFalse(Hash::check(ModuleSchemaService::SUPERADMIN_PASSWORD, $user->password));
        $this->assertSame('Custom', $user->first_name);
        $this->assertSame('Name', $user->last_name);
    }

    #[Test]
    public function application_bootstrap_never_resets_any_existing_user_password(): void
    {
        $super = User::factory()->create([
            'email' => ModuleSchemaService::SUPERADMIN_EMAIL,
            'password' => 'super-custom-pass',
        ]);
        $tenantAdmin = User::factory()->create([
            'email' => 'tenant-admin@example.test',
            'password' => 'tenant-custom-pass',
        ]);
        $driver = User::factory()->create([
            'email' => 'driver@example.test',
            'password' => 'driver-custom-pass',
        ]);

        $before = [
            $super->id => $super->getRawOriginal('password'),
            $tenantAdmin->id => $tenantAdmin->getRawOriginal('password'),
            $driver->id => $driver->getRawOriginal('password'),
        ];

        $this->artisan('db:seed', ['--class' => ApplicationBootstrapSeeder::class, '--force' => true]);
        $this->artisan('nexa:ensure-bootstrap');

        foreach ($before as $id => $hash) {
            $fresh = User::query()->find($id);
            $this->assertNotNull($fresh);
            $this->assertSame($hash, $fresh->getRawOriginal('password'), "Password hash changed for user {$id}");
        }

        $this->assertTrue(Hash::check('super-custom-pass', $super->fresh()->password));
        $this->assertTrue(Hash::check('tenant-custom-pass', $tenantAdmin->fresh()->password));
        $this->assertTrue(Hash::check('driver-custom-pass', $driver->fresh()->password));
    }
}
