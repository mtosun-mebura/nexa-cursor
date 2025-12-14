<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use App\Models\User;
use App\Models\Branch;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class BranchesTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        
        $this->user = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);
        $this->user->assignRole($role);
    }

    /**
     * Test branches index page loads correctly
     */
    public function test_branches_index_page_loads()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/admin/branches')
                    ->assertSee('Branches Beheer')
                    ->assertSee('Toon')
                    ->assertSee('branches');
        });
    }

    /**
     * Test branches can be created
     */
    public function test_can_create_branch()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/admin/branches/create')
                    ->assertSee('Nieuwe Branch')
                    ->type('name', 'IT & Software')
                    ->type('description', 'IT en software development')
                    ->press('Opslaan')
                    ->assertPathIs('/admin/branches')
                    ->assertSee('IT & Software');
        });
    }

    /**
     * Test branches can be searched
     */
    public function test_can_search_branches()
    {
        Branch::create(['name' => 'IT Branch', 'slug' => 'it', 'is_active' => true]);
        Branch::create(['name' => 'Marketing Branch', 'slug' => 'marketing', 'is_active' => true]);
        
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/admin/branches')
                    ->type('search', 'IT')
                    ->press('Enter')
                    ->assertSee('IT Branch')
                    ->assertDontSee('Marketing Branch');
        });
    }

    /**
     * Test branches can be filtered by status
     */
    public function test_can_filter_branches_by_status()
    {
        Branch::create(['name' => 'Active Branch', 'slug' => 'active', 'is_active' => true]);
        Branch::create(['name' => 'Inactive Branch', 'slug' => 'inactive', 'is_active' => false]);
        
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/admin/branches')
                    ->select('status', 'active')
                    ->waitForText('Active Branch')
                    ->assertSee('Active Branch')
                    ->assertDontSee('Inactive Branch');
        });
    }

    /**
     * Test branches table is sortable
     */
    public function test_branches_table_is_sortable()
    {
        Branch::create(['name' => 'Zebra Branch', 'slug' => 'zebra', 'sort_order' => 3]);
        Branch::create(['name' => 'Alpha Branch', 'slug' => 'alpha', 'sort_order' => 1]);
        
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/admin/branches')
                    ->click('@sort-name')
                    ->waitForText('Alpha Branch')
                    ->assertSeeIn('table tbody tr:first-child', 'Alpha Branch');
        });
    }

    /**
     * Test pagination works
     */
    public function test_pagination_works()
    {
        Branch::factory()->count(30)->create();
        
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/admin/branches?per_page=10')
                    ->assertSee('Toon 1 tot 10')
                    ->select('per_page', '25')
                    ->waitForText('Toon 1 tot 25')
                    ->assertSee('Toon 1 tot 25');
        });
    }

    /**
     * Test reset filters button works
     */
    public function test_reset_filters_works()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/admin/branches?status=active&search=test')
                    ->click('@reset-filters')
                    ->assertPathIs('/admin/branches')
                    ->assertQueryStringMissing('status')
                    ->assertQueryStringMissing('search');
        });
    }
}







