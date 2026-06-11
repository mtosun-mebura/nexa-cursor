<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminProfileWithoutSkillmatchingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_profile_loads_when_skillmatching_tables_are_missing(): void
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        Schema::dropIfExists('skills');
        Schema::dropIfExists('experiences');
        Schema::dropIfExists('cv_files');

        $response = $this->actingAs($user)->get(route('admin.profile'));

        $response->assertOk();
        $response->assertViewHas('candidateProfileEnabled', false);
    }
}
