<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class BranchTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create permissions
        Permission::firstOrCreate(['name' => 'view-branches', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create-branches', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'edit-branches', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete-branches', 'guard_name' => 'web']);
        
        // Create super-admin role
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdminRole->givePermissionTo(['view-branches', 'create-branches', 'edit-branches', 'delete-branches']);
        
        // Create super admin user
        $this->superAdmin = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);
        $this->superAdmin->assignRole('super-admin');
        
        // Create regular user
        $this->user = User::factory()->create([
            'email' => 'user@test.com',
            'password' => bcrypt('password'),
        ]);
    }

    /** @test */
    public function super_admin_can_view_branches_index()
    {
        $response = $this->actingAs($this->superAdmin)
            ->get('/admin/branches');
        
        $response->assertStatus(200);
        $response->assertViewIs('admin.branches.index');
    }

    /** @test */
    public function regular_user_cannot_view_branches_without_permission()
    {
        $response = $this->actingAs($this->user)
            ->get('/admin/branches');
        
        $response->assertStatus(403);
    }

    /** @test */
    public function super_admin_can_create_branch()
    {
        $branchData = [
            'name' => 'IT & Software',
            'slug' => 'it-software',
            'description' => 'IT en software development',
            'is_active' => true,
            'sort_order' => 1,
        ];
        
        $response = $this->actingAs($this->superAdmin)
            ->post('/admin/branches', $branchData);
        
        $response->assertRedirect('/admin/branches');
        $this->assertDatabaseHas('branches', ['name' => 'IT & Software']);
    }

    /** @test */
    public function super_admin_can_update_branch()
    {
        $branch = Branch::create([
            'name' => 'Test Branch',
            'slug' => 'test-branch',
            'is_active' => true,
        ]);
        
        $response = $this->actingAs($this->superAdmin)
            ->put("/admin/branches/{$branch->id}", [
                'name' => 'Updated Branch',
                'slug' => 'updated-branch',
                'is_active' => true,
            ]);
        
        $response->assertRedirect('/admin/branches');
        $this->assertDatabaseHas('branches', ['name' => 'Updated Branch']);
    }

    /** @test */
    public function super_admin_can_delete_branch()
    {
        $branch = Branch::create([
            'name' => 'Test Branch',
            'slug' => 'test-branch',
            'is_active' => true,
        ]);
        
        $response = $this->actingAs($this->superAdmin)
            ->delete("/admin/branches/{$branch->id}");
        
        $response->assertRedirect('/admin/branches');
        $this->assertDatabaseMissing('branches', ['id' => $branch->id]);
    }

    /** @test */
    public function branches_index_shows_pagination()
    {
        // Create more than 25 branches to test pagination
        Branch::factory()->count(30)->create();
        
        $response = $this->actingAs($this->superAdmin)
            ->get('/admin/branches?per_page=10');
        
        $response->assertStatus(200);
        $response->assertViewHas('branches');
    }

    /** @test */
    public function branches_can_be_filtered_by_status()
    {
        Branch::create(['name' => 'Active Branch', 'slug' => 'active', 'is_active' => true]);
        Branch::create(['name' => 'Inactive Branch', 'slug' => 'inactive', 'is_active' => false]);
        
        $response = $this->actingAs($this->superAdmin)
            ->get('/admin/branches?status=active');
        
        $response->assertStatus(200);
        $branches = $response->viewData('branches');
        $this->assertTrue($branches->every(fn($branch) => $branch->is_active));
    }

    /** @test */
    public function branches_can_be_searched()
    {
        Branch::create(['name' => 'IT Branch', 'slug' => 'it', 'is_active' => true]);
        Branch::create(['name' => 'Marketing Branch', 'slug' => 'marketing', 'is_active' => true]);
        
        $response = $this->actingAs($this->superAdmin)
            ->get('/admin/branches?search=IT');
        
        $response->assertStatus(200);
        $branches = $response->viewData('branches');
        $this->assertTrue($branches->contains('name', 'IT Branch'));
    }

    /** @test */
    public function branches_can_be_sorted()
    {
        Branch::create(['name' => 'Zebra Branch', 'slug' => 'zebra', 'sort_order' => 3]);
        Branch::create(['name' => 'Alpha Branch', 'slug' => 'alpha', 'sort_order' => 1]);
        Branch::create(['name' => 'Beta Branch', 'slug' => 'beta', 'sort_order' => 2]);
        
        $response = $this->actingAs($this->superAdmin)
            ->get('/admin/branches?sort_by=name&sort_order=asc');
        
        $response->assertStatus(200);
        $branches = $response->viewData('branches');
        $this->assertEquals('Alpha Branch', $branches->first()->name);
    }
}










