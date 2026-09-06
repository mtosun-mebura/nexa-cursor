<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ModuleSchemaService;
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
}
