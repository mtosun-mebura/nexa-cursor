<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Branch;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BranchModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function branch_has_vacancies_relationship()
    {
        $branch = Branch::create([
            'name' => 'Test Branch',
            'slug' => 'test-branch',
            'is_active' => true,
        ]);
        
        $vacancy = Vacancy::factory()->create([
            'branch_id' => $branch->id,
        ]);
        
        $this->assertTrue($branch->vacancies->contains($vacancy));
    }

    /** @test */
    public function branch_can_be_created()
    {
        $branch = Branch::create([
            'name' => 'IT Branch',
            'slug' => 'it-branch',
            'description' => 'IT and Software',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        
        $this->assertDatabaseHas('branches', [
            'name' => 'IT Branch',
            'slug' => 'it-branch',
        ]);
    }

    /** @test */
    public function branch_can_be_updated()
    {
        $branch = Branch::create([
            'name' => 'Old Name',
            'slug' => 'old-slug',
        ]);
        
        $branch->update([
            'name' => 'New Name',
            'slug' => 'new-slug',
        ]);
        
        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'name' => 'New Name',
            'slug' => 'new-slug',
        ]);
    }

    /** @test */
    public function branch_can_be_deleted()
    {
        $branch = Branch::create([
            'name' => 'Test Branch',
            'slug' => 'test-branch',
        ]);
        
        $branchId = $branch->id;
        $branch->delete();
        
        $this->assertDatabaseMissing('branches', ['id' => $branchId]);
    }

    /** @test */
    public function branch_is_active_is_boolean()
    {
        $branch = Branch::create([
            'name' => 'Test Branch',
            'slug' => 'test-branch',
            'is_active' => true,
        ]);
        
        $this->assertIsBool($branch->is_active);
        $this->assertTrue($branch->is_active);
    }

    /** @test */
    public function branch_sort_order_is_integer()
    {
        $branch = Branch::create([
            'name' => 'Test Branch',
            'slug' => 'test-branch',
            'sort_order' => 5,
        ]);
        
        $this->assertIsInt($branch->sort_order);
        $this->assertEquals(5, $branch->sort_order);
    }
}




