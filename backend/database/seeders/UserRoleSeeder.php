<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or find roles
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $companyAdminRole = Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        $staffRole = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $candidateRole = Role::firstOrCreate(['name' => 'candidate', 'guard_name' => 'web']);

        // Create or find users
        $superAdmin = User::firstOrCreate(
            ['email' => 'm.tosun@mebura.nl'],
            [
                'first_name' => 'Mehmet',
                'last_name' => 'Tosun',
                'password' => bcrypt('password123'),
                'email_verified_at' => now(),
            ]
        );

        $companyAdmin = User::firstOrCreate(
            ['email' => 'mali@tosun.nl'],
            [
                'first_name' => 'Mali',
                'last_name' => 'Tosun',
                'password' => bcrypt('password123'),
                'email_verified_at' => now(),
            ]
        );

        // Assign roles to users
        $superAdmin->syncRoles([$superAdminRole]);
        $companyAdmin->syncRoles([$companyAdminRole]);

        // Ensure Mehmet Tosun has super-admin role
        $mehmetUser = User::where('email', 'm.tosun@mebura.nl')->first();
        if ($mehmetUser) {
            $mehmetUser->syncRoles([$superAdminRole]);
        }

        $this->command->info('Users and roles assigned successfully!');
        $this->command->info('Super Admin: m.tosun@mebura.nl (password: password123)');
        $this->command->info('Company Admin: mali@tosun.nl (password: password123)');
    }
}
